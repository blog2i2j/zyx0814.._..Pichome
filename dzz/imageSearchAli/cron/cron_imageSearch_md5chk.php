<?php
/**
 * //cronname:以图搜图Md5校验
 * //week:
 * //day:
 * //hour:
 * //minute:0,5,10,15,20,25,30,35,40,45,50,55
 */
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
ignore_user_abort(true);
set_time_limit(0);
$processname = 'LOCK_IMGSEARCH_MD5CHK';
$locked=true;
$limit=20;
if (!dzz_process::islocked($processname, 60*5)) {
    $locked=false;
}
if ($locked) {
    runlog('imageSearchAli',$processname.'进程已被锁定请稍后再试');
}else {

    $setting = getglobal('setting/imageSearchAli_setting');
    foreach (DB::fetch_all("select ms.* from %t ms left join %t r on ms.rid = r.rid where (ms.md5 ='' or ms.getmd5=1) and  r.isdelete < 1 and r.apptype = 1 limit %d",
        ['image_search_ali', 'pichome_resources', $limit]) as $value) {
        $rid = $value['rid'];
        $file = IO::getStream($rid);
        $filemd5 = md5_file($file);
        if ($value['md5'] != $filemd5) {
            DB::update('image_search_ali', array('md5' => $filemd5, 'getmd5' => 0, 'status' => 0, 'retry' => 0), array('rid' => $rid));
        }

    }
    dzz_process::unlock($processname);
}