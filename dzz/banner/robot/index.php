<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}

$bannerlist=C::t('pichome_banner')->getBannerList();

$topmenu='';
if($bannerlist['top']){
    $topmenu=getBannerTmpl($bannerlist['top']);
}
$bottommenu='';
if($bannerlist['bottom']){
    $bottommenu=getBannerTmpl($bannerlist['bottom']);
}
$current=C::t('pichome_banner')->getFistBanner($bannerlist['top']);
if(empty($current)){
    $current=C::t('pichome_banner')->getFistBanner($bannerlist['bottom']);
}

if(!empty($current)) {
    switch ($current['btype']) {
        case '0': //库
            $appid=$current['bdata'];
            require MOD_PATH . '/robot/library.php';
            break;
        case '1': //智能数据
            $tid=$current['bdata'];
            require MOD_PATH . '/robot/intelligent.php';
            break;

        case '2': //单页
            $pageid=$current['bdata'];
            require MOD_PATH . '/robot/alonepage.php';
            break;
        case '3': //连接

            break;
        case '4': //专辑

            break;
        case '6': //发布
            $id = $current['bdata'];
            require MOD_PATH . '/robot/publish/index.php';
            exit();
            break;

    }
    include template('robot/index');
    exit();
}