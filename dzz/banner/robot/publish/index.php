<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}

global $_G,$id;

$data = C::t('publish_list')->fetch($id);
$navtitle=$data['pname'];
if($ismobile=helper_browser::ismobile()){
    $ispc=0;
}else{
    $ispc=1;
}

if(!C::t('publish_list')->checkpermById($id)){
    showmessage('no_perm');
}
if($data['pstatus']<1 && $_G['adminid']!=1){
    showmessage('no_perm');
}
$pageset=array();
if($data['pageset']){
    $pageset=unserialize($data['pageset']);
}
if($pageset['topNavigation'] || ($pageset['bottomEnable'] && $pageset['bottomNavigation'])){
    $bannerlist=C::t('pichome_banner')->getBannerList();

    $topmenu='';
    if($bannerlist['top']){
        $topmenu=getBannerTmpl($bannerlist['top']);
    }
    $bottommenu='';
    if($bannerlist['bottom']){
        $bottommenu=getBannerTmpl($bannerlist['bottom']);
    }

}
switch ($data['ptype']){
    case '1':
        require MOD_PATH.'/robot/publish/singlefile.php';
        break;
    case '2':
        require MOD_PATH.'/robot/publish/multifile.php';
        break;
    case '3':
        require MOD_PATH.'/robot/publish/library.php';
        break;
    case '4':
        require MOD_PATH.'/robot/publish/alonepage.php';
        break;
    case '5':
        require MOD_PATH.'/robot/publish/intelligent.php';
        break;
    case '6':
        require MOD_PATH.'/robot/publish/collect.php';
        break;
}
exit();