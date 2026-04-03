<?php
/*
 * 应用卸载程序示例
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 *
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */

if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
$navtitle=lang('appname');
$op="admin";
Hook::listen('adminlogin');
include_once libfile('function/cache');
if(!$setting = C::t('setting')->fetch('imageSearchAli_setting',true)){
    $setting=array();
}

if(submitcheck('submit')){
    $newsetting = [
        'apiurl'=>trim($_GET['apiurl']),
        'apikey'=>$_GET['apikey'] ? trim($_GET['apikey']):'',
        'apikeyDashVector'=>$_GET['apikeyDashVector'] ? trim($_GET['apikeyDashVector']):'',
        'endpoint'=>$_GET['endpoint'] ? trim($_GET['endpoint']):'',
        'exts'=>$_GET['exts'] ? trim($_GET['exts']):'',
        'status'=>$_GET['status'] ? intval($_GET['status']):0,
        'limit'=>$_GET['limit'] ? intval($_GET['limit']):0,
    ];
    foreach($newsetting as $k=>$v){
        $newsetting[$k] = !is_array($v)?getstr($v):$v;

    }

    if(C::t('setting')->update('imageSearchAli_setting',$newsetting)){
        $imageSearchFlag=intval(getglobal('setting/imageSearchFlag'));
        if($newsetting['status']>0){
            C::t('setting')->update('imageSearchFlag',$imageSearchFlag | 2);
        }else{
            C::t('setting')->update('imageSearchFlag',$imageSearchFlag & ~2);
        }
        updatecache('setting');
       exit(json_encode(array('success'=>true)));
    }
}else{
    $newsetting=array();


    if(!isset($setting['status'])){
        $newsetting['status']=0;
    }
    if(!isset($setting['limit']) || $setting['limit']<0 || $setting['limit']>100){
        $newsetting['limit']=50;
    }
    if(empty($setting['exts'])){
        //普通图片格式
        $newsetting['exts']='jpg,png,gif,jpeg,bmp,webp'
            .',art,arw,cr2,crw,dng,eps,eps2,eps3,ps,ps2,ps3,psd,svg,tga,tif,tiff,ai,iiq,cdr' //特殊格式
            .',avi,rm,rmvb,mkv,mov,wmv,asf,mpg,mpe,mpeg,mp4,m4v,mpeg,f4v,vob,ogv,mts,m2ts,3gp,webm,flv,wav,vqf,ra,mxf' //视频类格式
            .',pdf,doc,docx,rtf,odt,htm,html,mht,txt,ppt,pptx,pps,ppsx,odp,xls,xlsx,ods,csv';//文档类格式
    }

    $setting=array_merge($setting,$newsetting);

    if($newsetting){
        $newsetting=array_merge($setting,$newsetting);
        C::t('setting')->update('imageSearchAli_setting',$newsetting);
        updatecache('setting');
    }
    include template('admin/page/main');
}
exit();