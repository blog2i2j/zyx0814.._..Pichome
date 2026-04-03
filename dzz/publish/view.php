<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
$pid = isset($_GET['id']) ? intval($_GET['id']) : 0;
$data = C::t('publish_list')->fetch($pid);
$data['filter'] = unserialize($data['filter']);
$data['pageset'] = unserialize($data['pageset']);
$fdata = json_encode($data);

/*$template = '';
if($data['ptype'] == 3){
    $filter = json_encode($data['filter']);
    $template = 'library';
}*/
$tpldata = C::t('publish_template')->fetch($data['tid']);
// print_r($data);
// die;
//处理模板语言包
$lang = array();
if(file_exists(DZZ_ROOT.'./dzz/publish/template/'.$tpldata['tdir'].'/'.$tpldata['tflag'].'/language/'.$_G['language'].'/lang.php')){
    include_once(DZZ_ROOT.'./dzz/publish/template/'.$tpldata['tdir'].'/'.$tpldata['tflag'].'/language/'.$_G['language'].'/lang.php');
}

include template($tpldata['tdir'].'/'.$tpldata['tflag'].'/view/page/main');
exit();
