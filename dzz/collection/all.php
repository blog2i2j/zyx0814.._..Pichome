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

Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
global $_G;
$uid = $_G['uid'];

($_G['adminid'] == 1 || !isset($_G['config']['pichomeclosecollect']) || !$_G['config']['pichomeclosecollect']) ? '': exit('Access Denied');
//主题
$theme = GetThemeColor();


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
if ($ismobile) {
    include template('mobile/all');
} else {
    include template('pc/all');
}


    