<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
$ismobile=helper_browser::ismobile();
$ptype = isset($_GET['ptype']) ? intval($_GET['ptype']) : 0;
$value = isset($_GET['value']) ? trim($_GET['value']) : 0;
$flag=intval($_GET['flag']);
if($ptype==1 &&  !preg_match("/^\w{32}$/i",$value)){
    if($arr=Pdecode($value)){
        $value = $arr['path'];
    }else{
        showmessage('file_not_exists');
    }
}
$tdata = C::t('publish_template')->fetch_by_ttype($ptype);
//处理发布名称
$ext='';
switch($ptype){
    case 1://单文件
        $resourcesdata=C::t('pichome_resources')->fetch($value);
        $navtitle=$resourcesdata['name'];
        $ext=$resourcesdata['ext'];
        break;
    case 2://多文件
        $rids=explode(',',$value);
        $resourcesdata=C::t('pichome_resources')->fetch($rids[0]);
        $navtitle=$resourcesdata['name'].' '.lang('deng');
        break;
    case 3://库
        $library=C::t('pichome_vapp')->fetch($value);
        $navtitle=$library['appname'];
        break;
    case 4://单页
        $page=C::t('pichome_templatepage')->fetch($value);
        $navtitle=$page['pagename'];
        break;
    case 5://智能数据
        $page=C::t('#intelligent#intelligent')->fetch($value);
        $navtitle=$page['title'];
        break;
    case 6://合集
        $navtitle=lang('publish_collect_create');
        break;
}
$sql="ttype=%d";
$params=array('publish_template',$ptype);
if($ext){
    $sql.=" AND (exts='' OR find_in_set(%s,exts))";
    $params[]=$ext;
}

$tdata=array();
foreach(DB::fetch_all("select * from %t where $sql order by cuse DESC",$params) as $v){
    $v['cover']=MOD_PATH.'/template/'.$v['tdir'].'/'.$v['tflag'].'/thumb.jpg';
    $tdata[]=$v;
}

$tdata = json_encode($tdata);
include template('choose/page/template');
exit();