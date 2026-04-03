<?php
if (!defined('IN_OAOOA') ||  !defined('PICHOME_LIENCE')) {
    exit('Access Denied');
}
updatesession();
Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
global $_G;
($_G['adminid'] == 1 || !isset($_G['config']['pichomeclosecollect']) || !$_G['config']['pichomeclosecollect']) ? '': exit('Access Denied');
$do = isset($_GET['do']) ? trim($_GET['do']):'';

//添加或编辑收藏
if($do == 'addcollect'){
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $setarrdata = [
        'name'=>getstr($_GET['name'],30),
        'uid'=>$_G['uid'],
        'username'=>$_G['username'],
        'dateline'=>TIMESTAMP
    ];
    if($clid) $setarrdata['clid']=$clid;
    if(!$setarrdata['name']) exit(json_encode(array('error'=>lang('name_is_must'))));
    $clid = C::t('pichome_collect')->addcollect($setarrdata);
    if($clid){
        $setarrdata['clid'] = $clid;
        //如果有用户数据添加用户
        $uids = isset($_GET['uids']) ? trim($_GET['uids']):'';
        $uidarr = explode(',',$uids);
        $perm = isset($_GET['perm']) ? intval($_GET['perm']):1;
        if(!empty($uidarr)){
            foreach($uidarr as $v){
                $setarr = [
                    'uid'=>$v,
                    'perm'=>$perm,
                    'clid'=>$clid,
                    'dateline'=>TIMESTAMP
                ];
                C::t('pichome_collectuser')->add_user_to_collect($setarr);
            }
        }
        Hook::listen('lang_parse',$setarrdata,['getCollectLangKey']);
        exit(json_encode(array('success'=>$setarrdata)));
    }else{
        exit(json_encode(array('error'=>lang('svae_is_failer'))));
    }

}elseif($do == 'delcollect'){//删除收藏
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $return = C::t('pichome_collect')->delete_by_clid($clid);
    if(isset($return['error'])){
        exit(json_encode(array('error'=>lang($return['error']))));
    }else{
        exit(json_encode(array('success'=>true)));
    }
}elseif($do == 'addcollectcat'){//添加或编辑收藏分类
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $pcid = isset($_GET['pcid']) ? intval($_GET['pcid']):0;
    $cid = isset($_GET['cid']) ? intval($_GET['cid']):0;
    if($cid){
        $setarrdata = [
            'catname'=>getstr($_GET['catname'],30),
            'cid'=>$cid,
            'pcid'=>$pcid,
            'clid'=>$clid,
            'dateline'=>TIMESTAMP
        ];
    }else{
        $setarrdata = [
            'catname'=>getstr($_GET['catname'],30),
            'uid'=>$_G['uid'],
            'username'=>$_G['username'],
            'dateline'=>TIMESTAMP,
            'pcid'=>$pcid,
            'clid'=>$clid
        ];
    }
    if(!$setarrdata['catname'])   exit(json_encode(array('error'=>lang('name_is_must'))));
    $return = C::t('pichome_collectcat')->add_cat_by_clid($setarrdata);
    if(isset($return['error'])){
        exit(json_encode(array('error'=>lang($return['error']))));
    }else{
        $setarrdata['cid'] = $return;
        Hook::listen('lang_parse',$setarrdata,['getCollectCatLangKey']);
        exit(json_encode(array('success'=>true,'data'=>$setarrdata)));
    }
}elseif($do == 'delcollectcat'){//删除收藏分类
    $cid = isset($_GET['cid']) ? intval($_GET['cid']):0;
    $return = C::t('pichome_collectcat')->delete_by_cid($cid);
    if(isset($return['error'])){
        exit(json_encode(array('error'=>lang($return['error']))));
    }else{
        exit(json_encode(array('success'=>true)));
    }
}elseif($do == 'addfilecollect'){//收藏一个文件
    $rids= isset($_GET['ids']) ? trim($_GET['ids']):'';
    $rids = explode(',',$rids);
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $cid = isset($_GET['cid']) ? intval($_GET['cid']):0;
    $setarrdata = [
        'rid'=>$rids,
        'cid'=>$cid,
        'clid'=>$clid,
        'uid'=>$_G['uid'],
        'username'=>$_G['username'],
        'dateline'=>TIMESTAMP
    ];

    $return = C::t('pichome_collectlist')->add_collect($setarrdata);
    if(isset($return['error'])){
        exit(json_encode(array('error'=>lang($return['error']))));
    }else{
        exit(json_encode(array('success'=>true)));
    }
}elseif($do == 'canclecollect'){//取消收藏
    $lids = isset($_GET['lids']) ? trim($_GET['lids']):'';
    $lids = explode(',',$lids);
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $cid = isset($_GET['cid']) ? intval($_GET['cid']):0;
    $return = C::t('pichome_collectlist')->cancle_filecollect($lids,$clid,$cid);
    if(isset($return['error'])){
        exit(json_encode(array('error'=>lang($return['error']))));
    }else{
        exit(json_encode(array('success'=>true)));
    }
}elseif($do == 'addusertocollect'){//添加用户到收藏
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $objuids = isset($_GET['objuid']) ? trim($_GET['objuid']):'';
    $objuidarr = explode(',',$objuids);
    foreach($objuidarr as $v){
        $setarr = [
            'uid'=>$v,
            'perm'=>isset($_GET['perm']) ? intval($_GET['perm']):1,
            'clid'=>$clid,
            'dateline'=>TIMESTAMP
        ];
        C::t('pichome_collectuser')->add_user_to_collect($setarr);
    }
    exit(json_encode(array('success'=>$setarr)));

}elseif($do == 'delusertocollect'){//删除收藏成员
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $objuid =isset($_GET['objuid']) ? intval($_GET['objuid']):'';
    $return = C::t('pichome_collectuser')->delete_user_to_collect($objuid,$clid);
    if(isset($return['error'])){
        exit(json_encode(array('error'=>lang($return['error']))));
    }else{
        exit(json_encode(array('success'=>true)));
    }
}elseif($do == 'collectdisp'){//收藏排序
    $clids = isset($_GET['clids']) ? trim($_GET['clids']):'';
    $clidarr = explode(',',$clids);
    C::t('user_setting')->update_by_skey('pichomecollectdisp', serialize($clidarr), $_G['uid']);
    exit(json_encode(array('success'=>true)));
}elseif($do == 'collectlist'){//获取收藏和分类列表
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $cid = isset($_GET['cid']) ? intval($_GET['cid']):0;
    $uid = $_G['uid'];
    $data = [];
    if(!$clid){
      foreach(DB::fetch_all("select cl.clid,cl.name,cl.covert from %t cl left join %t cu on cl.clid=cu.clid 
where cu.uid = %d and cu.perm > %d  ",array('pichome_collect','pichome_collectuser',$uid,1)) as $v){
          $tmparr = [];
          foreach(DB::fetch_all("select uid from %t where clid = %d order by perm desc limit 0,3",array('pichome_collectuser',$v['clid'])) as $val){
              $val['icon'] = avatar_block($val['uid']);
              $tmparr[] = $val;
          }
          $v['uids'] = $tmparr;
          $v['leaf'] = DB::result_first("select count(*) from %t where clid = %d",array('pichome_collectcat',$v['clid'])) ? false:true;
          $data[] = $v;
      }
        Hook::listen('lang_parse',$data,['getCollectLangData',1]);
    }elseif($clid){
        foreach(DB::fetch_all("select cid,catname,clid,pathkey from %t 
where clid = %d and pcid = %d",array('pichome_collectcat',$clid,$cid)) as $v){
            $v['leaf'] = DB::result_first("select count(*) from %t where pcid = %d",array('pichome_collectcat',$v['cid'])) ? false:true;
            $data[] = $v;
        }
        Hook::listen('lang_parse',$data,['getCollectcatLangData',1]);
    }
    exit(json_encode(array('success'=>$data)));
}elseif($do == 'searchcollect'){//搜索符合条件的收藏和分类
    $lang = '';
    Hook::listen('lang_parse',$lang,['checklang']);
    $keyword = isset($_GET['keyword']) ? getstr($_GET['keyword'],30):'';
    $params1 = ['pichome_collect'];
    $selectsql1 = "select c.clid from %t c";
    $wheresql1 = " where  c.name like %s ";
    $para1 = array('%'.$keyword.'%');
    if($lang){
        $selectsql1 .= " left join %t lang on c.clid = lang.idvalue and lang.idtype = 17 ";
        $params1[] = 'lang_'.$lang;
        $wheresql1 .= " or lang.svalue like %s ";
        $para1[] = '%'.$keyword.'%';
    }
    $params1 = array_merge($params1,$para1);
    $data=[];
    foreach(DB::fetch_all("$selectsql1 $wheresql1 ",$params1) as $v){
        $data['clid'][] = $v['clid'];
    }
    $params2 = ['pichome_collectcat'];
    $selectsql2 = "select cc.pathkey,cc.clid from %t cc ";
    $wheresql2 = " where  cc.catname like %s ";
    $para2 = array('%'.$keyword.'%');
    if($lang){
        $selectsql2 .= " left join %t lang on cc.cid = lang.idvalue and lang.idtype = 18 ";
        $params2[] = 'lang_'.$lang;
        $wheresql2 .= " or lang.svalue like %s ";
        $para2[] = '%'.$keyword.'%';
    }
    $params2 = array_merge($params2,$para2);
    foreach(DB::fetch_all("$selectsql2 $wheresql2 ",$params2) as $v){
        $data['clid'][]=$v['clid'];
        $v['pathkey'] = str_replace('_','',$v['pathkey']);
        $patharr = explode('-',$v['pathkey']);
        if(isset($data['cids'])){
            $data['cids'] = array_merge($data['cids'],$patharr);
        }else{
            $data['cids'] = $patharr;
        }
    }
    $returndata=array();
    if(is_array($data['clid'])) $returndata['clid'] = array_unique($data['clid']);
    if(is_array($data['cids'])) $returndata['cids'] = array_unique($data['cids']);
    exit(json_encode($returndata));

}elseif($do == 'getuser'){
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']):'';
    $params = array('user');
    $wheresql = ' `status` = 0 ';
    if($keyword){
        $wheresql .= ' and username like %s ';
        $params[] = '%'.$keyword.'%';
    }
    $data = [];
    foreach(DB::fetch_all("select uid,username,adminid from %t where $wheresql",$params) as $v){
        $v['icon'] = avatar_block($v['uid']);
        $data[] = $v;
    }

    exit(json_encode(array('success'=>$data)));
}elseif($do == 'movefiletocollect'){//移动收藏到指定收藏位置
    $oclid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $ocid = isset($_GET['cid']) ? intval($_GET['cid']):0;
    $lids = isset($_GET['ids']) ? explode(',',$_GET['ids']):[];
    $return = C::t('pichome_collectlist')->move_collectfile($lids,$oclid,$ocid);
    if(isset($return['error'])){
        exit(json_encode(array('error'=>lang($return['error']))));
    }else{
        exit(json_encode(array('success'=>true)));
    }
}elseif($do == 'collectfiletocollect'){//收藏已收藏到指定收藏位置
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $cid = isset($_GET['cid']) ? intval($_GET['cid']):0;
    $lids = isset($_GET['ids']) ? explode(',',$_GET['ids']):[];
    $return = C::t('pichome_collectlist')->collect_by_lid($lids,$clid,$cid);
    if(isset($return['error'])){
        exit(json_encode(array('error'=>lang($return['error']))));
    }else{
        exit(json_encode(array('success'=>true)));
    }
}elseif ($do == 'addshare'){//创建分享
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $lid = isset($_GET['lid']) ? intval($_GET['lid']):0;
    if($lid){
        $sharedata = C::t('pichome_share')->add_share($lid,1);
    }else{
        $sharedata = C::t('pichome_share')->add_share($clid,2);
    }
    exit(json_encode(array('success'=>$sharedata['shareurl'])));

}elseif($do == 'setcovert'){
    $clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
    $lid = isset($_GET['lid']) ? intval($_GET['lid']):0;
    $return = C::t('pichome_collect')->set_collect_covert($lid,$clid);
    if(isset($return['error'])){
        exit(json_encode(array('error'=>lang($return['error']))));
    }else{
        exit(json_encode(array('success'=>true)));
    }
}