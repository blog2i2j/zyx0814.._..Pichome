<?php

if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
$base64 = trim($_GET['base64']);
//获取图片mime
if(preg_match("/data:(.+?);base64,/i",$base64,$matches)){
    $mime = $matches[1];

    $content=base64_decode(str_replace('data:'.$mime.';base64,','', $base64));
}else{//兼容base64不带前缀的情况
    $content=base64_decode($base64);
    $mime = getimagesizefromstring($content);

}
if(empty($mime)){
    $mime='image/png';
}
$ext='';
switch($mime){
    case 'image/jpeg':
        $ext='jpg';
        break;
    case 'image/png':
        $ext='png';
        break;
    case 'image/gif':
        $ext='gif';
        break;
    case 'image/bmp':
        $ext='bmp';
        break;
    case 'image/webp':
        $ext='webp';
        break;
}
$filename='tmpimg_'.TIMESTAMP.random(5).'.'.$ext;
$tmp=getglobal('setting/attachdir').'./cache/'.$filename;

if(!file_put_contents($tmp,$content)) {
    exit(json_encode(array('success'=>false,'msg'=>'failure')));
}
$ret=IO::saveToAttachment($tmp, $filename);
if($ret['aid'])    {
    $img = IO::getFileUri('attach::'.$ret['aid']);
    exit(json_encode(array('success'=>true,'aid'=>$ret['aid'],'img'=>$img,'apath'=>dzzencode('attach::'.$ret['aid'],'',0))));
}
else exit(json_encode(array('success'=>false,'msg'=>'failure')));