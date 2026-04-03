<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G;
$overt = getglobal('setting/overt');
if(!$overt && !$overt = C::t('setting')->fetch('overt')){
    Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
}
if(!empty($_GET['robot']) || (!empty($_config['seo']) && !empty(IS_ROBOT))){
    require MOD_PATH.'/robot/index.php';
    exit();
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$data = C::t('publish_list')->fetch_by_id($id,1);
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

$operation = isset($_GET['operation']) ? trim($_GET['operation']) : '';
if($operation == 'basic'){
    $data = C::t('publish_list')->fetch_by_id($id,1);
    $data['fdateline']=dgmdate($data['dateline'],'Y-m-d H:i:s');
    //单文件时，处理
    if($data['ptype'] == 1){
        $perm = C::t('publish_list')->getPermById($id);
        $data['resourcesdata']=C::t('pichome_resources')->fetch_by_rid($data['pval'],0,0,$perm);
        $data['pval'] = Pencode(array('path'=>$data['pval'],'perm'=>$perm,'ishare'=>0,'isadmin'=>1),7200);
        $data['resourcesdata']['fileurl'] = IO::getFileUri($data['resourcesdata']['rid']);

    }
    exit(json_encode($data));
}else{


    $_G['setting']['metakeywords']=$data['metakeywords']?$data['metakeywords']:$_G['setting']['metakeywords'];
    $_G['setting']['metadescription']=$data['metadescription']?$data['metadescription']:$_G['setting']['metadescription'];
    if($_G['setting']['pathinfo']){
        $url = $_G['siteurl'].$data['address'];
    }else{
        $url = $_G['siteurl'].'index.php?mod=publish&id='.$id;
    }
    $data['url']=$url;
    if($data['ptype'] == 1){
        $perm = C::t('publish_list')->getPermById($id);
        $data['resourcesdata']=C::t('pichome_resources')->fetch_by_rid($data['pval'],0,0,$perm);
        $data['pval'] = Pencode(array('path'=>$data['pval'],'perm'=>$perm,'ishare'=>0,'isadmin'=>1),7200);
        $data['resourcesdata']['fileurl'] = IO::getFileUri($data['resourcesdata']['rid']);
    }
    $data['fdateline']=dgmdate($data['dateline'],'Y-m-d H:i:s');
    $json_pageset=json_encode($data['pageset']??[]);
    $json_data=json_encode($data);
    $tpldata = C::t('publish_template')->fetch($data['tid']);
    $ismobile=helper_browser::ismobile();
    $mobileprefx=$ismobile?'mobile':'pc';

    $tmplatename=DZZ_ROOT.'dzz'.BS.'publish'.BS.'template'.BS.$tpldata['tdir'].BS.$tpldata['tflag'].BS.'view'.BS.$mobileprefx.BS.'page'.BS.'main.htm';
    $tmplatename1=DZZ_ROOT.'dzz'.BS.'publish'.BS.'template'.BS.$tpldata['tdir'].BS.$tpldata['tflag'].BS.'view'.BS.'pc'.BS.'page'.BS.'main.htm';
    if(is_file($tmplatename)) {
        include template($tpldata['tdir'] . '/' . $tpldata['tflag'] . '/view/' . $mobileprefx . '/page/main');
    }elseif(is_file($tmplatename1)){
        include template($tpldata['tdir'] . '/' . $tpldata['tflag'] . '/view/pc/page/main');
    }else{
        include template($tpldata['tdir'].'/'.$tpldata['tflag'].'/view/page/main');
    }

    //添加浏览统计
    $statsdata=array(
        'statstype'=>'1',
        'idtype'=>'6',
        'idval'=>$id,
        'name'=>$data['pname'],
        'isadmin'=>0
    );
    addStatsdata($statsdata);
    exit();
}