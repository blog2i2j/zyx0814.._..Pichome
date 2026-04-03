<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G,$id,$data;
    $data = C::t('publish_list')->fetch_by_id($id,1);
    $_G['setting']['metakeywords']=$data['metakeywords']?$data['metakeywords']:$_G['setting']['metakeywords'];
    $_G['setting']['metadescription']=$data['metadescription']?$data['metadescription']:$_G['setting']['metadescription'];
    if($_G['setting']['pathinfo']){
        $url = $_G['siteurl'].$data['address'];
    }else{
        $url = $_G['siteurl'].'index.php?mod=publish&id='.$id;
    }
    $data['url']=$url;
    $data['fdateline']=dgmdate($data['dateline'],'Y-m-d H:i:s');
    //单文件时，处理

        $perm = C::t('publish_list')->getPermById($id);
        $data['resourcesdata']=C::t('pichome_resources')->fetch_by_rid($data['pval'],0,0,$perm);
        $data['pval'] = Pencode(array('path'=>$data['pval'],'perm'=>$perm,'ishare'=>0,'isadmin'=>1),7200);
        $data['resourcesdata']['fileurl'] = IO::getFileUri($data['resourcesdata']['rid']);


    include template('robot/singlefile/index');
    exit();