<?php
/**
 * //cronname:以图搜图入库
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
global $_G;
$limit=100;
$processname = 'LOCK_IMGSEARCHALI_PREPARE';

if (!dzz_process::islocked($processname, 60 * 150)) {
    CronImageSearchAliPrepare($limit);
}
dzz_process::unlock($processname);
function CronImageSearchAliPrepare($limit=100)
{
    $setting = getglobal('setting/imageSearchAli_setting');
    $exts = $setting['exts'] ? (explode(',', $setting['exts'])) : array();

    if (!$setting['status']) {
        return;
    }

    foreach (DB::fetch_all("select r.rid from %t r LEFT JOIN %t s ON r.rid=s.rid  where isnull(s.rid) and r.ext IN(%n) order by r.rid limit %d", array('pichome_resources','image_search_ali', $exts,$limit)) as $value) {

        $data=C::t('pichome_resources')->fetch_by_rid($value['rid']);

        $setarr = [
            'rid'=>$data['rid']
        ];

        if($data['aid']){
            $setarr['aid'] = $data['aid'];
        }
        $setarr['dateline'] = $data['dateline'];
        $setarr['ext'] = $data['ext'];
        $setarr['appid']=$data['appid'];

        C::t('#imageSearchAli#image_search_ali')->insert_data($setarr);
    }
    return true;
}
