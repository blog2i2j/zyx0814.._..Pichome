<?php

if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
require_once(DZZ_ROOT . './dzz/imageSearch/class/class_apiClient.php' );
ignore_user_abort(true);
set_time_limit(0);
$locked = true;
$processnum =  10;
for ($i = 0; $i < $processnum; $i++) {
    $processname = 'LOCK_IMGSEARCH_ADD' . $i;
    if (!dzz_process::islocked($processname, 60 * 15)) {
        $locked = false;
        break;
    }
}
if ($locked) {
    exit(json_encode( array('error'=>'进程已被锁定请稍后再试')));
}
$video_exts=array('mp4','flv','avi','wmv','rmvb','rm','3gp','mkv','mpg','mpeg','mov','vob','m4v','webm','ogg','ogv','ogm','ogx','ogm','ogv','ogx','mts','m2ts','ts','m2t');
global $_G;
$limit=100;
$start = $i * $limit;
$id=intval($_GET['id']);
$setting =  getglobal('setting/imageSearchAli_setting');
$api=new apiClient($setting);
$sql="status!='1' and  retry<3";
$param=array('image_search_ali');
if($id){
    $sql.=" and id=%d";
    $param[]=$id;
    $limit=1;
}
foreach(DB::fetch_all("select * from %t where $sql order by dateline limit $start,$limit",$param) as $value){
    $processname1 = 'LOCK_IMGSEARCH_ADD' . $value['id'];
    if (dzz_process::islocked($processname1, 60*5)) {
        continue;
    }
    $resourcesdata = C::t('pichome_resources')->fetch($value['rid']);

    if(!$resourcesdata){
        DB::delete('image_search_ali',array('id'=>$value['id']));
        continue;
    }
    if($value['aid'] && !$data=C::t('attachment')->fetch($value['aid'])){
        DB::delete('image_search_ali',array('id'=>$value['id']));
        continue;
    }
    $isvideo=0;
    if(in_array($resourcesdata['ext'],$video_exts)){
        $isvideo=1;
    }
    if($isvideo){
        $url=IO::getFileUri($value['rid']);

        $ret=$api->media_add(
            array(
                'rid'=>$value['rid'],
                'url'=>$url
            )
        );
    }else {
        $url = C::t('pichome_resources')->geticondata_by_rid($value['rid'],1);

        if (!$content = (file_get_contents($url))) {
            DB::update('image_search_ali', array('retry' => $value['retry'] + 1, 'dateline' => TIMESTAMP), array('id' => $value['id']));
            continue;
        }
        $ret = $api->media_add(
            array(
                'rid' => $value['rid'],
                'base64' => base64_encode($content)
            )
        );

    }
    print_r($ret);

    if($ret['status']=='success'){
        DB::update('image_search_ali',array('status'=>1,'dateline'=>TIMESTAMP),array('id'=>$value['id']));
    }else{
        runlog('imageSearchAli',$value['rid'].'===='.json_encode($ret, JSON_UNESCAPED_UNICODE));
        DB::update('image_search_ali',array('status'=>2,'retry'=>$value['retry']+1,'dateline'=>TIMESTAMP),array('id'=>$value['id']));
    }
    dzz_process::unlock($processname1);
}
if(!empty($processname)) dzz_process::unlock($processname);
if(DB::result_first("select COUNT(*) from %t where $sql ",$param)){
    include template('common/header_reload');
    echo "<script>window.location.reload();</script>";
    include template('common/footer_reload');
}
exit('success');