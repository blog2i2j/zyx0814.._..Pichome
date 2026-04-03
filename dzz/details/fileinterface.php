<?php
if (!defined('IN_OAOOA')) {//所有的php文件必须加上此句，防止被外部调用
    exit('Access Denied');
}
updatesession();
global $_G;
$operation = isset($_GET['operation']) ? trim($_GET['operation']) : '';

if(!$patharr=Pdecode($_GET['path'])){
    exit(json_encode(array('status'=>2,'error'=>lang('no_perm'))));
}
$rid = $patharr['path'];
$isshare = $patharr['isshare'];
$perm = $patharr['perm'];
$isadmin = $patharr['isadmin'];
if(!perm::check('edit2',$perm)){
    exit(json_encode(array('status'=>2,'error'=>lang('no_perm'))));
}
$resourcesdata = C::t('pichome_resources')->fetch($rid);
$appid = $resourcesdata['appid'];

if($operation == 'save'){
    $flag = isset($_GET['flag']) ? trim($_GET['flag']):'';
    $val = trim($_GET['val']);
    $attrs = array(
        $flag => htmlspecialchars($val)
    );
    if(strpos($flag,'tabgroup_') === 0){
        $gid = intval(str_replace('tabgroup_','',$flag));
        $datatids = [];
        foreach(DB::fetch_all("select tid from %t where rid = %s and gid = %d",array('pichome_resourcestab',$rid,$gid)) as $v){
            $datatids[] = $v['tid'];
        }
        $ntids = $attrs[$flag] ? explode(',',$attrs[$flag]):[];
        $dtids = array_diff($datatids,$ntids);
        if($dtids){
            //删除对应文件的标签
            C::t('pichome_resourcestab')->delete_by_rids_tids($rid,$dtids);
        }
        $addtids = array_diff($ntids,$datatids);
        foreach($addtids as $v){
            if(!$v) continue;
            $rtag = ['appid' => $appid, 'rid' => $rid, 'tid' => $v,'gid'=>$gid];
            C::t('pichome_resourcestab')->insert($rtag);
        }
    }
    elseif($flag == 'sys'){//保存摄影师的值
        $olddata = C::t('pichome_sys')->fetch_by_rid($rid);
        if($olddata){
            $oldvalues = array_keys($olddata);
        }else{
            $oldvalues = [];
        }
        $nsysdata = ($attrs['sys']) ? explode(',',$attrs['sys']):[];
        if(!$olddata) $olddata = [];
        //删除的值
        $deldata = array_diff($oldvalues,$nsysdata);
        if($deldata){
            $delids = [];
            foreach($deldata as $val){
                $delids[] = $olddata[$val];
            }
            C::t('pichome_sys')->delete($delids);
        }
        //新增的值
        $adddata = array_diff($nsysdata,$oldvalues);
        foreach($adddata as $v){
            $v = getstr($v);
            if(!$v) continue;
            $rtag = ['appid' => $appid, 'rid' => $rid, 'labelname' => $v];
            C::t('pichome_sys')->insert_data($rtag);
        }
        $returndata = ['rid'=>$rid,'sys'=>$nsysdata];
    }
    elseif($flag == 'tag'){
        $attrdata = C::t('pichome_resources_attr')->fetch($rid);
        $datatags = explode(',',$attrdata['tag']);
        $ntags = explode(',',$attrs['tag']);
        $dtags = array_diff($datatags,$ntags);
        if($dtags){
            //删除对应文件的标签
            C::t('pichome_resourcestag')->delete_by_rids_tids($rid,$dtags);
        }
        $addtags = array_diff($ntags,$datatags);
        foreach($addtags as $v){
            if(!$v) continue;
            $rtag = ['appid' => $attrdata['appid'], 'rid' => $rid, 'tid' => $v];
            C::t('pichome_resourcestag')->insert($rtag);
        }
        $attrs = [
            'tag' => implode(',',$ntags)
        ];
        C::t('pichome_resources_attr')->update_by_rid($appid,$rid,$attrs);
        $tagdatas = [];
        foreach(DB::fetch_all("select tagname,tid from %t where tid in(%n)",array('pichome_tag',$ntags)) as $tv){
            Hook::listen('lang_parse',$tv,['getTagLangData']);
            $tagdatas[] = ['tid'=>$tv['tid'],'tagname'=>$tv['tagname']];
        }
        Hook::listen('lang_parse',$rids,['updateResourcesSearchvalData']);
        $returndata = ['rid'=>$rid,'tag'=>$tagdatas];
        $hookdata = ['appid'=>$attrdata['appid'],'rid'=>$rids];
        Hook::listen('updateeagleattrafter',$hookdata);
    }
    elseif($flag == 'fid'){
        $resourcesdata = C::t('pichome_resources')->fetch($rid);
        $datafolders = explode(',',$resourcesdata['fids']);
        $nfolders = explode(',',$val);

        $dfolders = array_diff($datafolders,$nfolders);
        if($dfolders){
            //删除对应文件的目录
            C::t('pichome_folderresources')->delete_by_ridfid($rid,$dfolders);
        }
        $addfolderss = array_diff($nfolders,$datafolders);
        foreach($addfolderss as $v){
            $rfolder = ['appid' => $resourcesdata['appid'], 'rid' => $rid, 'fid' => $v];
            C::t('pichome_folderresources')->insert($rfolder);
        }
        $attrs = [
            'fids' => implode(',',$nfolders),
            'lastdate'=>TIMESTAMP,
            'isdelete'=>($addfolderss) ? 0:$resourcesdata['isdelete']
        ];
        if($addfolderss){
            $attrs['isdelete']= 0;
            $ofids=array_unique(array_diff($datafolders,$nfolders));
            $ofids = array_diff($datafolders,$dfolders);
            $rfidarr = explode(',', $ofids);
            C::t('pichome_folder')->add_filenum_by_fid($rfidarr, 1);
        }else{
            $attrs['isdelete'] = $resourcesdata['isdelete'];
        }
        C::t('pichome_resources')->update_by_rids($appid,$rid,$attrs);
        $foldernames = [];
        foreach(DB::fetch_all("select fid,fname,pathkey from %t where fid in(%n)",array('pichome_folder',$nfolders)) as $fv){
            $foldernames[] = ['fid'=>$fv['fid'],'fname'=>$fv['fname'],'pathkey'=>$fv['pathkey']];
        }
        $returndata[] = ['rid'=>$rid,'isdelete'=>$attrs['isdelete'],'foldernames'=>$foldernames];
        $hookdata = ['appid'=>$resourcesdata['appid'],'rid'=>$rid];
        Hook::listen('updateeagleattrafter',$hookdata);
    }
    elseif($flag == 'grade'){
        $attrs['lastdate']=TIMESTAMP;
        C::t('pichome_resources')->update_by_rids($appid,$rid,$attrs);
        $returndata[] = ['rid'=>$rid,'grade'=>$val];
        $hookdata = ['appid'=>$appid,'rid'=>$rid];
        Hook::listen('updateeagleattrafter',$hookdata);
    }
    else{
        $form = C::t('form_setting')->fetch_by_flag($flag);
        if($form){
            //判断字段类型
            $formtype = $form['type'];
            $updatesearchval = 1;
            switch($formtype){
                case 'inputselect':
                    $attrs[$flag] = $val;
                    $updatesearchval = 0;
                    break;
                case 'inputmultiselect':
                    $attrs[$flag] = implode(',',$val);
                    $updatesearchval = 0;
                    break;
                case 'time':
                case 'blue':
                    $attrs[$flag] = $val;
                    $updatesearchval = 0;
                    break;
                default :
                    $attrs[$flag] = $val;

            }
          
            C::t('pichome_resourcesattr')->update_by_skey($rid,$attrs);
            if($updatesearchval){
                $hookarr = ['rid' => $rid, 'flag' => $flag, 'value' => $attrs[$flag], 'type' => $form['type']];
                Hook::listen('lang_parse', $hookarr, ['saveResourcesattrLangeData']);
                C::t('pichome_resourcesattr')->update_searchattr_by_rid($rid);
            }

            $returndata[] = ['rid'=>$rid,$flag=>$val];
        }
        else{
            $resourcesattrdata = DB::fetch_first("select r.name,attr.link,attr.desc from %t r left join %t attr on r.rid = attr.rid where r.rid = %s",
                array('pichome_resources','pichome_resources_attr',$rid));
            $annotationdatas = C::t('pichome_comments')->fetch_annotation_by_rid($rid);
            if($flag == 'name'){
                $name=$resourcesdata['name'];
                if($val){
                    C::t('pichome_resources')->update_by_rids($appid,$rid,$attrs);
                    $name=$val;
                }
                $hookdata = ['appid'=>$appid,'rid'=>$rid];
                Hook::listen('updateeagleattrafter',$hookdata);
                $returndata[] = ['rid'=>$rid,'name'=>$name];
            }elseif($flag == 'desc'){
                //$attrs['searchval'] = $resourcesattrdata['link'].$resourcesattrdata['name'].getstr($val,255).implode('',$annotationdatas);
                C::t('pichome_resources_attr')->update_by_rids($appid,$rid,$attrs);
                $hookdata = ['appid'=>$appid,'rid'=>$rid];
                Hook::listen('updateeagleattrafter',$hookdata);

            }elseif($flag == 'link'){
                /* $attrs['searchval'] = $resourcesattrdata['name'].getstr($resourcesattrdata['desc'],255).htmlspecialchars($val).implode('',$annotationdatas);*/
                C::t('pichome_resources_attr')->update_by_rid($appid,$rid,$attrs);
                $hookdata = ['appid'=>$appid,'rid'=>$rid];
                Hook::listen('updateeagleattrafter',$hookdata);
            }elseif($flag == 'lang'){
                C::t('pichome_resources')->update_by_rids($appid,$rid,$attrs);
            }
            $returndata[] = ['rid'=>$rid,$flag=>$val];

        }

    }
    exit(json_encode(array('success' => true,'data'=>$returndata)));

}elseif($operation == 'getRigehtdata'){//右侧标签
    $flag = isset($_GET['flag']) ? trim($_GET['flag']) : '';
    $oneself_tid=isset($_GET['tids']) ? explode(',',$_GET['tids']) : array();//当前rid对应的所有标签tid
    $cid = isset($_GET['cid']) ? trim($_GET['cid']):0;
    $appid = isset($_GET['appid']) ? trim($_GET['appid']):'';
    if($cid){
        $tagdata = array();
        /*  $groupdata = C::t('pichome_taggroup')->fetch_tagcatandnum_by_pcid($appid,$cid);
          $gids = array();
          $tagdata = array();
          foreach ($groupdata as $v) {
              $cids[] = $v['cid'];
              $tagdata[$v['cid']]['name'] = $v['name'];
          }*/

        foreach (DB::fetch_all("select t.*,tg.cid from %t tg left join %t t on t.tid=tg.tid left join %t vt  
                on vt.tid = tg.tid where tg.cid =%s and tg.appid = %s order by t.initial,vt.hots DESC ",
            array('pichome_tagrelation', 'pichome_tag','pichome_vapp_tag',$cid,$appid)) as $val) {
            if(in_array($val['tid'],$oneself_tid)){
                $val['yes'] = 1;
            }else{
                $val['yes'] = 0;
            }
            Hook::listen('lang_parse',$val,['getTagLangData']);
            // $tagdata[$val['gid']]['val'][] = $val;
            $tagdata[] = $val;
        }
        exit(json_encode(array('success' => true,'arr' => $tagdata)));
    }else{

        $tags_all_new = array();//带字幕的所有标签
        $recent = array();//最近使用
        $all = array();//所有标签
        $tags_all = array();
        $tag_cat = array();

        foreach(DB::fetch_all("select t.*,vt.appid,vt.hots from %t vt left join %t t on t.tid=vt.tid where vt.appid=%s order by 
                t.initial,vt.hots DESC",array('pichome_vapp_tag','pichome_tag',$appid)) as $value){
            Hook::listen('lang_parse',$value,['getTagLangData']);
            if(!isset($tags_all[$value['initial']])) $tags[$value['initial']]=array();
            if($value['initial'])$tags_all[$value['initial']][$value['tid']]=$value;

        }
        if(count($tags_all['#']) > 0){
            $all_new=array_shift($tags_all);
            $tags_all['#'] = $all_new;
        }
        //最近使用数据
        $renctentdata = C::t('pichome_searchrecent')->fetch_recent_tag_by_appid($appid);
        $recenttids = array_keys($renctentdata);
        foreach($tags_all as $key => $val){
            foreach($val as $k => $v){
                if(in_array($v['tid'],$oneself_tid)){
                    $val[$k]['yes'] = 1;
                }else{
                    $val[$k]['yes'] = 0;
                }
                if($v['hots'] > 0 && in_array($k,$recenttids)){
                    $val[$k]['dateline'] = $renctentdata[$k];
                    $recent[] = $val[$k];
                }
                $all[$v['tid']] = $val[$k];
            }

            $tags_all_new[$key] = $val;
        }

        $recent_dateline = array_column($recent, 'dateline');
        array_multisort($recent_dateline,SORT_DESC,$recent );
        exit(json_encode(array('success' => true,'data' => $tags_all_new,'recent'=>$recent,'arr'=>$all)));
    }

}elseif($operation == 'label_add'){
    //$flag = isset($_GET['flag']) ? trim($_GET['flag']) : '';
    $tags = isset($_GET['tags']) ? trim($_GET['tags']) : '';
    $appid = isset($_GET['appid']) ? trim($_GET['appid']):'';
    $cid = isset($_GET['cid']) ? trim($_GET['cid']):0;//标签分类id
    $tags = explode(',',$tags);
    $data = array();
    $lang ='';
    Hook::listen('lang_parse',$lang,['checklang']);
    foreach($tags as $v){
        if(preg_match('/^\s*$/',$v)) continue;
        if($result = DB::fetch_first("select * from %t where tagname = %s ",array('pichome_tag',$v))){

            if($cid){
                $tagrelationarr = [
                    'appid'=>$appid,
                    'cid'=>$cid,
                    'tid'=>$result['tid']
                ];
                C::t('pichome_tagrelation')->insert($tagrelationarr);
            }
            $setarr = $result;
            $hots = DB::result_first("select hots from %t where appid = %s and tid = %d",array('pichome_vapp_tag',$appid,$result['tid']));
            if(is_null($hots)){
                $tagvapp = array(
                    'tid'=>$result['tid'],
                    'appid'=>$appid,
                );
                C::t('pichome_vapp_tag')->insert($tagvapp);
                $result['hots'] = 0;
            }else{
                $result['hots'] = intval($hots);

            }
            Hook::listen('lang_parse',$result,['getTagLangKey']);
            $data[] =  $result;
        }else{
            $setarr = array(
                'tagname'=>$v,
                'initial'=>C::t('pichome_tag')->getInitial($v),
                'lang'=>getglobal('language')
            );
            $id =  C::t('pichome_tag')->insert($v,1);
            if($id){
                $setarr['tid'] = $id;
                Hook::listen('lang_parse',$setarr,['setTagLangData']);
                if($cid){
                    $tagrelationarr = [
                        'appid'=>$appid,
                        'cid'=>$cid,
                        'tid'=>$id
                    ];
                    C::t('pichome_tagrelation')->insert($tagrelationarr);
                }

            }

            //将添加的标签添加到库
            $tagvapp = array(
                'tid'=>$id,
                'appid'=>$appid,
                // 'hots'=>1
            );

            C::t('pichome_vapp_tag')->insert($tagvapp);

            $setarr['hots'] = 0;
            Hook::listen('lang_parse',$setarr,['getTagLangKey']);
            $data[] =  $setarr;
        }
    }
    exit(json_encode(array('success' => true,'data'=>$data)));
}


