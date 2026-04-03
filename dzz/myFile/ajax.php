<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
$do = isset($_GET['do']) ? trim($_GET['do']):'';
if($do == 'rename'){
    $id = isset($_GET['id']) ? intval($_GET['id']):0;
    $newname = isset($_GET['name']) ? getstr($_GET['name']):'';
    if(!$data = C::t('my_file')->fetch($id)) exit(json_encode(['success'=>false,'msg'=>'操作对象不存在']));
    if($data['filename'] == $newname.'.'.$data['filetype']) exit(json_encode(['success'=>false,'msg'=>'操作未更改']));
    if(C::t('my_file')->update($id,['filename'=>$newname.'.'.$data['filetype']])){
        exit(json_encode(['success'=>true]));
    }else{
        exit(json_encode(['success'=>false,'msg'=>'操作未更改']));
    }
}elseif($do == 'del'){
    $id = isset($_GET['id']) ? intval($_GET['id']):0;
    C::t('my_file')->delete_by_id($id);
    exit(json_encode(['success'=>true]));
}