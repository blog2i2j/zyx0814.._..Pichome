<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 * 
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */
if (!defined('IN_OAOOA') || !defined('IN_ADMIN') || !defined('PICHOME_LIENCE')) {
    @header( 'HTTP/1.1 404 Not Found' );
    @header( 'Status: 404 Not Found' );
    exit('File not found');
}
$navtitle= lang('appname');
$orgtree = array();
$add=checkUserLimit();
if ($_G['adminid'] != 1) {
	//获取用户的有权限的部门树
	$orgids = C::t('organization_admin') -> fetch_orgids_by_uid($_G['uid']);
	foreach ($orgids as $orgid) {
		$arr = C::t('organization')->fetch_parent_by_orgid($orgid, true);
		$count = count($arr);
		if ($orgtree[$arr[$count - 1]]) {
			if (count($orgtree[$arr[$count - 1]]) > $count)
				$orgtree[$arr[count($arr) - 1]] = $arr;
		} else {
			$orgtree[$arr[$count - 1]] = $arr;
		}
	}
}
$orgtree = json_encode($orgtree);
require_once(__DIR__.'/dist/index.html');
?>