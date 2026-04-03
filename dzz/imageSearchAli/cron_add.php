<?php

if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
require_once(DZZ_ROOT . './dzz/imageSearchAli/class/class_apiClientAli.php' );
ignore_user_abort(true);
set_time_limit(0);
$locked = true;
$processnum =  10;
for ($i = 0; $i < $processnum; $i++) {
    $processname = 'LOCK_IMGSEARCHALI_ADD' . $i;
    if (!dzz_process::islocked($processname, 60 * 15)) {
        $locked = false;
        break;
    }
}
if ($locked) {
    exit(json_encode( array('error'=>$processname.'进程已被锁定请稍后再试')));
}
$video_exts=array('mp4','mpeg','mpg','avi','webm','flv','mkv','mov');
global $_G;
$limit=100;
$start = $i * $limit;
$id=intval($_GET['id']);
$setting =  getglobal('setting/imageSearchAli_setting');
$api=new \apiClientAli($setting);
$api->initCollection();
$sql="1";
$param=array('image_search_ali');
if($id){
    $sql.=" and id=%d";
    $param[]=$id;
    $limit=1;
    $start=0;
}else{
    $sql.=" and status !=1 and retry<10";
}

foreach(DB::fetch_all("select * from %t where $sql order by retry limit $start,$limit",$param) as $value){
    $processname1 = 'LOCK_IMGSEARCHALI_ADD' . $value['id'];
    if (empty($id) && dzz_process::islocked($processname1, 60*5)) {
        continue;
    }

    $resourcesdata = C::t('pichome_resources')->fetch($value['rid']);

    if(!$resourcesdata){
        if(C::t('#imageSearchAli#image_search_ali')->delete($value['id'])){
            $api->docDelete($value['rid']);
        }
        continue;
    }
    if($value['aid'] && !($data=C::t('attachment')->fetch($value['aid']))){
        if(C::t('#imageSearchAli#image_search_ali')->delete($value['id'])){
            $api->docDelete($value['rid']);
        }
        continue;
    }
    $pathkey='';
    if($folderdata=C::t('pichome_folderresources')->fetch($resourcesdata['rid'])){
        $pathkey=$folderdata['pathkey'];
    }
    $fields=array(
        'appid'=>$resourcesdata['appid'],
        'name'=>$resourcesdata['name'],
        'isdelete'=>$resourcesdata['isdelete']?true:false,
        'pathkey'=>$pathkey
    );

    $inputs=array();
    //获取文本信息
    $attr=C::t('pichome_resources_attr')->fetch($resourcesdata['rid']);
    $inputs['text']=$attr['searchval'];

    $isvideo=0;
    if(in_array($resourcesdata['ext'],$video_exts)){
        $isvideo=1;
    }
    if($isvideo){
        $url=IO::getFileUri($value['rid']);
        if(strpos($url,'?')!==false){
            $url.='&filename='.$resourcesdata['name'];
        }
        $inputs['vedio']=$url;

    }else {

        $thumbpath=IO::gettmpThumb($value['rid'], 360, 360,  2);

        if(strpos($thumbpath,'dzz/images/extimg')!==false){
            continue;
        }else{
            $url=IO::getFileUri($thumbpath);
        }

        if($url){
            $inputs['image']=$url;
        }else{
            continue;
        }

    }
    if($_GET['search']){
        unset($inputs['text']);
        print_r($inputs);
        $ret=$api->media_search($inputs);
        print_R($ret);exit('ddd');
    }else{
        print_r($value);
        $ret=$api->media_add($resourcesdata['rid'],$inputs,$fields);
        print_r($ret);
    }

    if($ret['code']===0){

        DB::update('image_search_ali',array('status'=>1,'dateline'=>TIMESTAMP),array('id'=>$value['id']));
    }else{
        runlog('imageSearchAli',$value['rid'].'===='.json_encode($ret, JSON_UNESCAPED_UNICODE));
        DB::update('image_search_ali',array('status'=>2,'retry'=>$value['retry']+1,'dateline'=>TIMESTAMP),array('id'=>$value['id']));
    }
    if($thumbpath){
        IO::delete($thumbpath);
    }
    dzz_process::unlock($processname1);
}
dzz_process::unlock($processname);
if(!$id && DB::result_first("select COUNT(*) from %t where $sql ",$param)){

    include template('common/header_reload');
    echo "<script>window.location.reload();</script>";
    include template('common/footer_reload');
}
exit('success');