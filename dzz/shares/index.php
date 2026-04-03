<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
$sid = dzzdecode($_GET['sid'],'',0);
$sharedata=C::t('pichome_share')->fetch_by_sid($sid);
if(!$sharedata){
    showmessage('share_file_iscancled');
}
if(perm::check('download2',$sharedata['perm'])){
    $sharedata['download'] = 1;
}else{
    $sharedata['download'] = 0;
}
$ret=checkShare($sharedata);
if(!$ret['success']){
    showmessage($ret['msg']);
}
//验证提取码
if ($sharedata['password'] && ($sharedata['password'] != authcode($_G['cookie']['share_pass_' . $sid]))) {
    include template('pc/page/password');
    exit();
}
$filepaths=explode(',',$sharedata['filepath']);
$perm=intval($sharedata['perm']);
if($sharedata['stype']<2 && count($filepaths)>1){
    $datas=array();
    foreach($filepaths as $filepath){
        $resourcesdata=C::t('pichome_resources')->fetch_by_rid($filepath);
        $resourcesdata['viewurl']='index.php?mod=shares&op=detail&path='.Pencode(array('path'=>$filepath,'perm'=>$sharedata['perm'],'sid'=>$sharedata['id']),3600);
        $resourcesdata['downurl']='index.php?mod=shares&op=download&path='.Pencode(array('path'=>$filepath,'perm'=>$sharedata['perm']),86400);
        $resourcesdata['fsize']=formatsize($resourcesdata['size']);

        $resourcesdata['name']=preg_replace("/\.".$resourcesdata['ext']."$/i", "", $resourcesdata['name']);
        if($resourcesdata['ext']){
            $resourcesdata['name']=$resourcesdata['name'].".".$resourcesdata['ext'];
        }
        $datas[]=$resourcesdata;
    }
   // print_r($datas);
    C::t('pichome_share')->add_views_by_id($sid);
    $theme = GetThemeColor();
    include template('pc/page/list');
    exit();
}else{
    $viewurl=C::t('pichome_share')->getViewUrl($sharedata);
    C::t('pichome_share')->add_views_by_id($sid);
    header('Location:'.$viewurl);
    exit();
}






