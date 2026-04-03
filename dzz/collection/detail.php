<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 *
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */

if (!defined('IN_OAOOA') ||  !defined('PICHOME_LIENCE')) {
    exit('Access Denied');
}

$ismobile = helper_browser::ismobile();
$clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
$isshare = 0;
if(!$clid){
	if(!$patharr=Pdecode($_GET['sid'])){
        showmessage('share not found or expired');
    }
    $shareid=dzzencode($patharr['path'],'',0,0);
    $clid =$patharr['path'];
    if($clid) $isshare = 1;
    else exit('Access Denied');
}else{
    Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
    ($_G['adminid'] == 1 || !isset($_G['config']['pichomeclosecollect']) || !$_G['config']['pichomeclosecollect']) ? '': exit('Access Denied');
}

global $_G;
$uid = $_G['uid'];
//获取当前用户收藏夹权限
$perm = C::t('pichome_collectuser')->get_perm_by_clid($clid);
if(!$isshare && $perm < 1){
    exit('Access Denied');
}
$collectdata = C::t('pichome_collect')->fetch($clid);
Hook::listen('lang_parse',$collectdata,['getCollectLangData']);
Hook::listen('lang_parse',$collectdata,['getCollectLangKey']);
$collectdata['nocatnum'] = DB::result_first("select count(id) from %t where clid = %d and cid = 0",array('pichome_collectlist',$clid));
$collectdata['usernum'] = DB::result_first("select count(id) from %t where clid = %s",array('pichome_collectuser',$clid));
$collectdata = json_encode($collectdata);
// print_r($collectdata);
// die;
//主题
$theme = GetThemeColor();

//显示子分类内容
	$ImageExpanded = C::t('user_setting')->fetch_by_skey('pichomecollectimageexpanded',$uid);
//筛选
$screen = C::t('user_setting')->fetch_by_skey('pichomecollectuserscreen', $uid);
$screen = $screen ? intval($screen) : 0;

$setting = $_G['setting'];
$pichomepagesetting = $setting['pichomepagesetting'] ? $setting['pichomepagesetting'] : [];
$pagesetting = $setting['pichomepagecollectsetting'] ? $setting['pichomepagecollectsetting'] : [];
$pagesetting['opentype'] = $pichomepagesetting['opentype'];
//排序方式
$pichomesortfileds = C::t('user_setting')->fetch_by_skey('pichomecollectsortfileds', $_G['uid']);
//显示信息
$pichomeshowfileds = C::t('user_setting')->fetch_by_skey('pichomecollectshowfileds', $_G['uid']);
//布局类型
$pichomelayout = C::t('user_setting')->fetch_by_skey('pichomecollectlayout', $_G['uid']);
if ($pichomesortfileds) {
    $sortdatarr = unserialize($pichomesortfileds);
    $sortfilearr = ['btime' => 1, 'mtime' => 2, 'dateline' => 3, 'name' => 4, 'size' => 5, 'grade' => 6, 'duration' => 7, 'whsize' => 8];
    $pagesetting['sort'] = $sortfilearr[$sortdatarr['filed']];
    $pagesetting['desc'] = $sortdatarr['sort'];
}
if ($pichomelayout) {
    $layout = unserialize($pichomelayout);
    $pagesetting['layout'] = $layout['layout'];
}
if ($pichomeshowfileds) {
    $pichomeshowfileds = unserialize($pichomeshowfileds);
    $pagesetting['show'] = $pichomeshowfileds['filed'];
    $pagesetting['other'] = $pichomeshowfileds['other'];
}
$template = 1;
if ($pagesetting['template']) {
    $template = $pagesetting['template'];
}
$pagesetting = json_encode($pagesetting);
updatesession();
if ($ismobile) {
    include template('mobile/details');
} else {
    include template('pc/details');
}


    