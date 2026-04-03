<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 * 
 * @link        https://www.oaooa.com
 */
if(!defined('IN_OAOOA') ) {
    exit('Access Denied');
}
$overt = getglobal('setting/overt');
if(!$overt && !$overt = C::t('setting')->fetch('overt')){
    Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
}
$do=$_GET['do'];
if($do=='submit'){
    // if($setarr['tid']=DB::insert('search_template',$setarr,1)){
 
		// exit(json_encode(array('success'=>true,'data'=>$setarr)));
	// }else{
		// exit(json_encode(array('success'=>false,'msg'=>lang('submit_error'))));
	// }
}else{
    include template('admin/page/main');
    exit();
}
