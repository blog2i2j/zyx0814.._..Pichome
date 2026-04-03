<?php
ignore_user_abort(true);
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
@set_time_limit(0);
ini_set('memory_limit', -1);
@ini_set('max_execution_time', 0);

$locked = true;
/*for($i=0;$i<1;$i++){
    $processname = 'DZZ_LOCK_PICHOMEGETINFO'.$i;
    if (!dzz_process::islocked($processname, 60*60)) {
        $locked=false;
        break;
    }
}*/
$i = 0;
$processname = 'DZZ_LOCK_PICHOMEGETINFO'.$i;
$limit = 100;
$start=$i*$limit;
if (!dzz_process::islocked($processname, 60*30)) {
    $locked=false;
}
if ($locked) {
   // exit(json_encode( array('error'=>'进程已被锁定请稍后再试')));
}
$exts = explode(',',$_G['config']['pichomeffmpeggetvieoinfoext']);

$datas = DB::fetch_all("select r.* from %t attr LEFT JOIN %t r ON r.rid=attr.rid where attr.isget = 0 and r.ext in(%n) 
 limit $start,$limit",array('pichome_resources_attr','pichome_resources',$exts));

use dzz\ffmpeg\classes\info as info;
$info =new info;
if($datas){
    foreach($datas as $v){
        $processname1 = 'PICHOMEGETINFO_'.$v['rid'];
        if (dzz_process::islocked($processname1, 60*5)) {
            continue;
        }
        $data = C::t('pichome_resources')->fetch_data_by_rid($v['rid']);

        $info->run($data);
        dzz_process::unlock($processname1);

    }
    dzz_process::unlock($processname);

}else{
    dzz_process::unlock($processname);
}

exit('success');