<?php
if(!empty($_GET['robot']) || (!empty($_config['seo']) && !empty(IS_ROBOT))){
    require MOD_PATH.'/robot/index.php';
    exit();
}
    if(!$patharr=Pdecode($_GET['path'])){
        exit('Access Denied');
    }

    global $_G;
    $rid = $patharr['path'];

    $perm = $patharr['perm'];

    $data=C::t('pichome_resources')->fetch($rid);
    $content=IO::getFileContent($rid);

   include template('page/preview');
   exit();
//include template('page/main');