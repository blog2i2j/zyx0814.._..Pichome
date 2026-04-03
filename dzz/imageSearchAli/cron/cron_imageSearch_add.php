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
require_once(DZZ_ROOT . './dzz/imageSearchAli/class/class_apiClientAli.php' );
ignore_user_abort(true);
set_time_limit(0);
global $_G;
$limit=100;
$processname = 'LOCK_IMGSEARCHALI_ADD';
$locked=true;
if (!dzz_process::islocked($processname, 60*150)) {
    $locked=false;
}
if ($locked) {
    runlog('imageSearchAli',$processname.'进程已被锁定请稍后再试');
}else{
    $video_exts=array('mp4','mpeg','mpg','avi','webm','flv','mkv','mov');
    $setting =  getglobal('setting/imageSearchAli_setting');
    $api=new \apiClientAli($setting);
    foreach(DB::fetch_all("select * from %t where status!='1' and retry<11 order by retry limit %d",array('image_search_ali',$limit)) as $value){
        $processname1 = 'LOCK_IMGSEARCHALI_ITEM' . $value['id'];
        if ( dzz_process::islocked($processname1, 60*120)) {
            continue;
        }
        $resourcesdata = C::t('pichome_resources')->fetch($value['rid']);

        if(!$resourcesdata){
            if(C::t('#imageSearchAli#image_search_ali')->delete($value['id'])){
                $api->docDelete($value['rid']);
            }
            continue;
        }
        if($value['aid'] && !$data=C::t('attachment')->fetch($value['aid'])){
            if(C::t('#imageSearchAli#image_search_ali')->delete($value['id'])){
                $api->docDelete($value['rid']);
            }
            continue;
        }


        $fields=array(
            'appid'=>$resourcesdata['appid'],
            'name'=>$resourcesdata['name'],
            'isdelete'=>$resourcesdata['isdelete']?true:false,
        );
        $pathkeys=array();
        foreach(DB::fetch_all("select * from %t where rid=%s and appid=%s",array('pichome_folderresources',$resourcesdata['rid'],$resourcesdata['appid'])) as $v){
            $pathkeys[]=$v['pathkey'];
        }
        if($pathkeys){
            $fileds['pathkey']=implode(',',$pathkeys);
        }
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
        $ret=$api->media_add($resourcesdata['rid'],$inputs,$fields);
        if($ret['code']===0){
            DB::update('image_search_ali',array('status'=>1,'dateline'=>TIMESTAMP),array('id'=>$value['id']));
        }else{
            runlog('imageSearchAli',$value['rid'].'===='.json_encode($ret, JSON_UNESCAPED_UNICODE));
            DB::update('image_search_ali',array('status'=>2,'retry'=>$value['retry']+1,'dateline'=>TIMESTAMP),array('id'=>$value['id']));
        }
        dzz_process::unlock($processname1);
        if($thumbpath){
            IO::delete($thumbpath,true);
        }
    }
    dzz_process::unlock($processname);
}