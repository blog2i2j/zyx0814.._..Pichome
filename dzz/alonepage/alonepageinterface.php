<?php
if (!defined('IN_OAOOA')) {//所有的php文件必须加上此句，防止被外部调用
    exit('Access Denied');
}
//管理权限进入
Hook::listen('adminlogin');
global $_G;
if (isset($_G['setting'])) $setting = $_G['setting'];
else  $setting = C::t('setting')->fetch_all();
$navtitle=lang('manager_center_page_manage');
$themeid = isset($_G['setting']['pichometheme']) ? intval($_G['setting']['pichometheme']):1;

$do = isset($_GET['do']) ? trim($_GET['do']):'';
updatesession();
if($do == 'addpage'){//新建单页
    if(submitcheck('addpage')){
        $pagename = isset($_GET['pagename']) ? getstr($_GET['pagename']):'';
        $id = isset($_GET['id']) ? intval($_GET['id']):0;
        if(!$pagename) exit(json_encode(['success'=>false,'msg'=>lang('lowser_page_name')]));
        if($id &&  DB::result_first("select id from %t where pagename = %s and id != %d",['pichome_templatepage',$pagename,$id])){
            exit(json_encode(['success'=>false,'msg'=>lang('page_name_exist')]));
        }elseif(DB::result_first("select id from %t where pagename = %s and id != %d ",['pichome_templatepage',$pagename,$id])){
            exit(json_encode(['success'=>false,'msg'=>lang('page_name_exist')]));
        }
        $setarr = [
            'pagename'=>$pagename,
            'dateline'=>TIMESTAMP
        ];
        if($id) $setarr['id'] = $id;
        if($setarr['id'] = C::t('pichome_templatepage')->insertdata($setarr)){
            $address = $_GET['address'] ? trim($_GET['address']):'';
            $url = 'index.php?mod=alonepage&op=view&id='.$setarr['id'].'#id='.$setarr['id'];
            $shorturl = C::t('pichome_route')->update_path_by_url($url,$address);
            if($setting['pathinfo'] && $shorturl) $setarr['url']=$shorturl;
            else $setarr['url']=$url;
            $setarr['dateline']=dgmdate(TIMESTAMP, 'Y-m-d H:i:s');

        }

        exit(json_encode(['success'=>true,'data'=>$setarr]));
    }else{
        $id = isset($_GET['id']) ? intval($_GET['id']):0;
        $pagedata = C::t('pichome_templatepage')->fetch($id);
        Hook::listen('lang_parse',$pagedata,['getAlonepageLangData']);
        Hook::listen('lang_parse',$pagedata,['getAlonepageLangKey']);
        $url = 'index.php?mod=alonepage&op=view&id='.$id.'#id='.$id;
        if($setting['pathinfo']) $path = C::t('pichome_route')->fetch_path_by_url($url);
        else $path = '';
        if($path){
            $pagedata['url'] = $path;
        }else{
            $pagedata['url'] = $url;
        }
        exit(json_encode(['success'=>true,'data'=>$pagedata]));
    }

}elseif($do == 'pageRename'){//单页改名
    $newname= isset($_GET['newname']) ? getstr($_GET['newname'],255):'';
    $id = isset($_GET['id']) ? intval($_GET['id']):0;
    if(!$newname) exit(json_encode(['success'=>false,'msg'=>lang('name_cannot_empty')]));
    C::t('pichome_templatepage')->update($id,['pagename'=>$newname]);
    exit(json_encode(['success'=>true,'newname'=>$newname]));
}elseif($do == 'pageDelete'){//删除单页
    $id = isset($_GET['id']) ? intval($_GET['id']):0;
    C::t('pichome_templatepage')->delete_by_id($id);
    exit(json_encode(['success'=>true]));
}elseif($do == 'pageRestore'){//删除单页
    $id = isset($_GET['id']) ? intval($_GET['id']):0;
    C::t('pichome_templatepage')->restore_by_id($id);
    exit(json_encode(['success'=>true]));
}elseif($do == 'deltag'){//删除标签位
    $tid = isset($_GET['tid']) ? intval($_GET['tid']):0;
    C::t('pichome_templatetag')->delete_by_tid($tid);
    exit(json_encode(['success'=>true]));
}elseif($do == 'deltagdata'){//删除标签数据
    $tdid = isset($_GET['tdid']) ? intval($_GET['tdid']):0;
    C::t('pichome_templatetagdata')->delete_by_id($tdid);
    exit(json_encode(['success'=>true]));
}elseif($do == 'pagelist'){//单页列表
    $page= isset($_GET['page']) ? intval($_GET['page']):1;
    $perpage= isset($_GET['perpage']) ? intval($_GET['perpage']):20;
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']):'';
    $start = ($page-1)*$perpage;
    $data = [];
    $total=0;
    $isdelete = isset($_GET['isdelete']) ? intval($_GET['isdelete']):0;
    $sql=" isdelete=%d";
    $params=array('pichome_templatepage',$isdelete);
    if($keyword){
        $sql.=" and pagename like %s";
        $params[]='%'.$keyword.'%';
    }
    if($total=DB::result_first("select count(*) from %t where $sql",$params)) {
        foreach (DB::fetch_all("select * from %t where $sql order by dateline DESC limit $start,$perpage", $params) as $v) {
            $v['dateline'] = dgmdate($v['dateline'], 'Y-m-d H:i:s');
            $url = 'index.php?mod=alonepage&op=view&id=' . $v['id'] . '#id=' . $v['id'];
            $path = C::t('pichome_route')->update_path_by_url($url);

            if ($path) {
                $v['url'] = $_G['siteurl'] . $path;
            } else {
                $v['url'] = $_G['siteurl'] . $url;
            }
            $data[] = $v;
        }
    }
    Hook::listen('lang_parse',$data,['getAlonepageLangData',1]);
    exit(json_encode(['success'=>true,'data'=>$data,'total'=>$total]));
}elseif($do == 'geturlqrcode'){//获取链接二维码
    $id = isset($_GET['id']) ? intval($_GET['id']) : '';
    $url = 'index.php?mod=alonepage&op=view&id='.$id.'#id='.$id;
    $qrcode = C::t('pichome_route')->getQRcodeByUrl($url);
    exit(json_encode(['success'=>true,'qrcode'=>$qrcode]));
}elseif($do == 'sortpage'){//单页排序
    $ids = isset($_GET['ids']) ? trim($_GET['ids']):'';
    $idarr = explode(',',$ids);
    foreach($idarr as $k=>$v){
        C::t('pichome_templatepage')->update($v,['disp'=>$k]);
    }
    exit(json_encode(['success'=>true]));
}elseif($do == 'sorttag'){//标签位排序
    $tids = isset($_GET['tids']) ? trim($_GET['tids']):'';
    $tidarr = explode(',',$tids);
    foreach($tidarr as $k=>$v){
        C::t('pichome_templatetag')->update($v,['disp'=>$k]);
    }
    exit(json_encode(['success'=>true]));
}elseif($do == 'setpage'){//设置单页内容
    $id = isset($_GET['id']) ? intval($_GET['id']):0;
    $page = C::t('pichome_templatepage')->fetch($id);
    if(!$page) exit(json_encode(['success'=>false,'msg'=>lang('page_not_exist')]));
    $pagedata = $_GET['data'];

    $pagetag = [
        'tid'=>$pagedata['tid'] ? intval($pagedata['tid']):0,
        'tagtype'=>$pagedata['type'],
        'tagname'=>$pagedata['title'] ? getstr($pagedata['title']):'',
        'dateline'=>TIMESTAMP,
        'disp'=>isset($pagedata['disp']) ? intval($pagedata['disp']):0,
        'showtitle'=>isset($pagedata['showtitle']) ? intval($pagedata['showtitle']):0,
        'pageid'=>$id,
    ];
    $pagedata['tid'] = C::t('pichome_templatetag')->insertdata($pagetag);
    $pagedata['tagname'] = $pagetag['tagname'];
    Hook::listen('lang_parse',$pagedata,['getAlonepagetagLangKey']);
    if($pagedata['tid']){
        $tagtheme = [
            'themeid'=>$themeid,
            'style'=>$pagedata['style'] ? serialize($pagedata['style']):'',
            'tid'=>$pagedata['tid'],
        ];
        C::t('pichome_templatetagtheme')->insertdata($tagtheme);
        foreach($pagedata['data'] as $k=>$v){
            $tagdata = [
                'id'=>$v['tdid'],
                'tid'=>$pagedata['tid'],
                'tdata'=>$v['data'],
                'type'=>$pagedata['type'],
                'tdataname'=>$v['name'] ? getstr($v['name']):$pagetag['tagname'],
                'cachetime'=>isset($v['data'][0]['time'])?intval($v['data'][0]['time']):0,
                'disp'=>$k
            ];

            $pagedata['data'][$k]['tdid'] = C::t('pichome_templatetagdata')->insertdata($tagdata);
            $pagedata['data'][$k]['id'] = $pagedata['data'][$k]['tdid'];
            Hook::listen('lang_parse',$pagedata['data'][$k],['getAlonpagetagdataLangKey',$pagedata['type']]);
            //更新缓存数据
            if($pagetag['tagtype'] == 'file_rec' || $pagetag['tagtype'] == 'db_ids'){
                if(!$_G['config']['filterFileByTabPerm'])dfsockopen(getglobal('localurl') . 'misc.php?mod=updatepagedata&tdid='.$pagedata['data'][$k]['tdid'], 0, '', '', false, '', 1);
            }
        }

        exit(json_encode(['success'=>true,'data'=>$pagedata]));
    }else{
        exit(json_encode(['success'=>false,'msg'=>lang('save_unsuccess')]));
    }



}elseif($do=='upload'){//上传图片
    include libfile( 'class/uploadhandler' );

    $options = array( 'accept_file_types' => '/\.(gif|jpe?g|png|svg)$/i',

        'upload_dir' => $_G[ 'setting' ][ 'attachdir' ] . 'cache/',

        'upload_url' => $_G[ 'setting' ][ 'attachurl' ] . 'cache/',

        'thumbnail' => array( 'max-width' => 40, 'max-height' => 40 ) );

    $upload_handler = new uploadhandler( $options );
    updatesession();
    exit();
} elseif($do=='uploadmedia'){//上传图片
    include libfile( 'class/uploadhandler' );

    $options = array( 'accept_file_types' => '/\.(mp4|flv|mp3|webm|ogg|aac))$/i',

        'upload_dir' => $_G[ 'setting' ][ 'attachdir' ] . 'cache/',

        'upload_url' => $_G[ 'setting' ][ 'attachurl' ] . 'cache/',

        'thumbnail' => array( 'max-width' => 40, 'max-height' => 40 ) );

    $upload_handler = new uploadhandler( $options );
    updatesession();
    exit();
}elseif($do == 'uploadico'){//设置图标
    $tid = isset($_GET['tid']) ? intval($_GET['tid']):0;
    $aid = isset($_GET['aid']) ? intval($_GET['aid']):0;
    $tagdata = C::t('pichome_templatetag')->fetch($tid);
    if(!$tagdata){
        exit(json_encode(array('success'=>false,'msg'=>lang('data_is_not_exixts'))));
    }else{
        if($tagdata['aid']){
            C::t('attachment')->delete_by_aid($tagdata['aid']);
        }else{
            C::t('attachment')->addcopy_by_aid($aid);
        }
    }
    exit(json_encode(array('success'=>true)));
}elseif($do == 'getapporsources'){//获取库列表或数据源列表
    $stype = isset($_GET['stype']) ? intval($_GET['stype']):0;
    $data = [];
    if($stype){
        $params = ['pichome_smartdata'];
        $wheresql = ' 1 ';
        //兼容图组
        if($stype == 2){
            $wheresql .= ' and stype = %d';
            $params[] = 1;
        }else{
            $wheresql .= ' and stype = %d';
            $params[] = 0;
        }
        foreach(DB::fetch_all("select * from %t where $wheresql ",$params) as $v){
            $data[] = array('id'=>$v['id'],'name'=>$v['name']);
        }

    }else{
        foreach(DB::fetch_all("select * from %t where isdelete < 1",['pichome_vapp']) as $v){
            if ($v['type'] != 3 && !IO::checkfileexists($v['path'],1)) {
                continue;
            }
            Hook::listen('lang_parse',$v,['getVappLangData']);
            $data[] = array('id'=>$v['appid'],'name'=>$v['appname'],'appid'=>$v['appid']);
        }

    }
    exit(json_encode(['success'=>true,'data'=>$data]));
}elseif($do == 'getmouldstyle'){//获取模块样式
    $mould = isset($_GET['mould']) ? trim($_GET['mould']):'slide';
    $themedata = $_G['setting']['pichomethemedata'][$themeid];
    $returndata = [];
    foreach($themedata['themestyle'] as $k=>$v){
        foreach($v as $k1=>$v1){
           if($v1['title']) $v1['title'] = lang($v1['title']);
            if(isset($v1['size'])){
                foreach ($v1['size'] as $k2=>$v2){
                    $v2['title'] = lang($v2['title']);
                    $v1['size'][$k2] = $v2;
                }
            }
            $v[$k1] = $v1;
        }
        $returndata[$k] = $v;
    }
    /*if(isset($themedata['themestyle'])){
        $returndata = $themedata['themestyle'];
    }*/
    exit(json_encode(['success'=>true,'data'=>$returndata,'themeid'=>$themeid]));
}elseif($do == 'typecollection'){//获取ku,单页，栏目,专辑
    $library = array();
    $library = DB::fetch_all("select * from %t  where isdelete = 0 order by `disp` asc,dateline desc", array('pichome_vapp'));
    Hook::listen('lang_parse',$library,['getVappLangData',1]);
    $alonepage = DB::fetch_all("select * from %t where 1 order by disp asc,dateline asc ",['pichome_templatepage']);
    Hook::listen('lang_parse',$alonepage,['getAlonepageLangData',1]);
    $banner = C::t('pichome_banner')->getbannerlist(0,1);

    $search = array();
    $search[] = array('id'=> '0','bannername'=> lang('all'));
    foreach($banner['top'] as $v){
        if($v['btype'] == 0 || $v['btype'] == 4){
            $search[] = $v;
        }
    }
    $tabstatus = 0;
    $tabgroupdata = [];
    Hook::listen('checktab', $tabstatus);
    if ($tabstatus) {//获取有tab数据
        Hook::listen('gettabgroupdata', $tabgroupdata);
    }
    $tabs = [];
    foreach($tabgroupdata as $k=>$tab) {
        if ($tab['available']) {
            $tabs[] = $tab;
        }
    }
    exit(json_encode(array('tab' => $tabs,'library' => $library,'alonepage'=>$alonepage,'banner'=>$banner['top'],'search'=>$search)));

}elseif($do == 'getpagecontent'){//获取单页内容
    $id = isset($_GET['id']) ? intval($_GET['id']):0;
    $pagedata = C::t('pichome_templatepage')->fetch_data_by_id($id);
    exit(json_encode(['success'=>true,'data'=>$pagedata]));
}elseif($do == 'getCollectList'){
    $limit=20;
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $ids= isset($_GET['ids']) ? dintval($_GET['ids'],true) : [];
    $data = [];
    $sql = "pstatus=1 and ptype=6 ";
    $params = array('publish_list');
    if ($q) {
        $sql .= " and pname like %s";
        $params[] = "%" . $q . "%";
    }
    foreach (DB::fetch_all("select * from %t where $sql limit $limit", $params) as $v) {
        $data[$v['id']] = array('id' => $v['id'], 'ptype' => $v['ptype'], 'name' => $v['pname']);
    }
    if($ids){
        foreach(DB::fetch_all("select * from %t where id IN(%n)",array('publish_list',$ids) ) as $v){
            $data[$v['id']]=array('id'=>$v['id'],'ptype'=>$v['ptype'],'name'=>$v['pname']);
        }
    }
    exit(json_encode(['success' => true, 'data' => array_values($data)]));
}elseif($do == 'getSearchList'){ //获取所有栏目中支持搜索的栏目列表，目前支持搜索的栏目有：库，智能数据，合集，发布库，发布智能数据
    $limit=20;
    if (!isset($_G['pathinfo'])) $pathinfo = C::t('setting')->fetch('pathinfo');
    else $pathinfo = $_G['pathinfo'];
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $ids= isset($_GET['ids']) ? dintval($_GET['ids'],true) : [];
    $sql = "select b.bannername,b.id,b.btype,b.bdata,p.ptype,p.pname as pid from %t b LEFT JOIN %t p on b.bdata=p.id and p.pstatus='1' and (p.ptype='3' OR p.ptype='5' OR p.ptype='6')";
    $params = array('pichome_banner','publish_list');
    $data = [];

    $wheresql="b.btype='0' OR b.btype='1' OR (b.btype='6' and !isnull(p.id))";

    if($ids){
        $wheresql .= " OR b.id IN(%n)";
        $params[] = $ids;
    }

    if ($q) {
        $wheresql = "(".$wheresql.") and b.bannername like %s";
        $params[] = "%" . $q . "%";
    }

    foreach (DB::fetch_all("$sql where $wheresql limit $limit", $params) as $v) {
        if ($v['btype'] == 3) {
            $url = $v['bdata'];
        } elseif ($v['btype'] == 1) {
            $url = 'index.php?mod=intelligent&tid=' .$v['bdata'];
        } elseif ($v['btype'] == 6) {
            $url = 'index.php?mod=publish&id=' .$v['bdata'];
        } elseif ($v['btype'] == 4) {
            $url = 'index.php?mod=banner&op=index&id=tb_' . $v['bdata'] . '#id=tb_' . $v['bdata'];
        } else {
            $url = 'index.php?mod=banner&op=index&id=' . $v['bdata'] . '#id=' . $v['bdata'];
        }

        if ($pathinfo) $path = C::t('pichome_route')->fetch_path_by_url($url);
        else $path = '';
        if ($path) {
            $searchurl = $path;
        } else {
            $searchurl = $url;
        }
        $data[$v['id']] = array('id' => $v['id'], 'bdata'=>$v['bdata'],'btype' => $v['btype'], 'ptype'=>$v['ptype'],'name' => $v['bannername'],'url'=>$searchurl);
    }

    exit(json_encode(['success' => true, 'data' => array_values($data)]));
}elseif($do == 'getPublishList'){
    $limit=200;
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $ids= isset($_GET['ids']) ? dintval($_GET['ids'],true) : [];
    $data = [];
    $sql = "pstatus=1 ";
    $params = array('publish_list');
    if ($q) {
        $sql .= " and pname like %s";
        $params[] = "%" . $q . "%";
    }
    foreach (DB::fetch_all("select * from %t where $sql limit $limit", $params) as $v) {

        $data[$v['id']] = array('id' => $v['id'], 'ptype' => $v['ptype'], 'name' => $v['pname']);
    }
    if($ids){
        foreach(DB::fetch_all("select * from %t where id IN(%n)",array('publish_list',$ids) ) as $v){
            $data[$v['id']]=array('id'=>$v['id'],'ptype'=>$v['ptype'],'name'=>$v['pname']);
        }
    }
    exit(json_encode(['success' => true, 'data' => array_values($data)]));
}