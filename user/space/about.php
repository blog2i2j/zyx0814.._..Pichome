<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 * 
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */
if(!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G;
$about=array();

$ismobile=helper_browser::ismobile();

$about['sitename']=getglobal('setting/sitename');//中文名称,留空不显示
$about['logo']=$_G['setting']['attachurl'].'sitelogo/sitelogo.png';
//站点logo,留空不显示
//
$license=array(

    'license_limit'=>defined('LICENSE_LIMIT')?LICENSE_LIMIT:1,
    'license_version'=>defined('LICENSE_VERSION')?LICENSE_VERSION:lang('home'),
    'license_company'=>defined('LICENSE_COMPANY')?LICENSE_COMPANY:''
);
$ucount=DB::result_first("select COUNT(*) from %t where 1",array('user'));
$versioncode = explode('.',CORE_VERSION);
unset($versioncode[0]);
$version = implode('.',$versioncode);
$version_name=lang(defined('LICENSE_VERSION')?LICENSE_VERSION:'Home').' '.$version;
$about['version'] = $version_name;
// $about['copyright'] = 'lang('Copy_right')';
$lastyear=dgmdate(TIMESTAMP,'Y');
$about['copyright'] = 'Powered By FilePress © 2012-'.$lastyear;
$about['home_page'] = 'https://oaooa.com';
$about['home'] = 'oaooa.com';
exit(json_encode(array('data'=>$about)));
// if ($ismobile && !$_GET['inajax']) {
// 	include template('mobile_about');
// } else {
// 	include template('about');
// }
exit();