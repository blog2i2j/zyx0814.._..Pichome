<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G,$id,$data;
    $data = C::t('publish_list')->fetch_by_id($id,1);
    $_G['setting']['metakeywords']=!empty($data['metakeywords'])?$data['metakeywords']:$_G['setting']['metakeywords'];
    $_G['setting']['metadescription']=!empty($data['metadescription'])?$data['metadescription']:$_G['setting']['metadescription'];

    $data['fdateline']=dgmdate($data['dateline'],'Y-m-d H:i:s');
    //多文件时，处理
    $perm = C::t('publish_list')->getPermById($id);

    $rids = explode(',',$data['pval']);
    $dataresources = C::t('pichome_resources')->getdatasbyrids($rids,1,$perm);
    //print_r($dataresources);

    include template('robot/multifile/index');
    exit();