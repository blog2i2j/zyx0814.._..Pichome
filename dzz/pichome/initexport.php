<?php
    if (!defined('IN_OAOOA')) {
        exit('Access Denied');
    }
    @set_time_limit(0);
    @ini_set('memory_limit', -1);
    @ini_set('max_execution_time', 0);
    
    $appid = isset($_GET['appid']) ? trim($_GET['appid']):0;
    $force = isset($_GET['force']) ? intval($_GET['force']):0;
    $data = C::t('pichome_vapp')->fetch($appid);
    if(!$data) exit(json_encode(array('error'=>'no data')));
    if(($data['state'] > 1 &&  $data['state'] < 4) || $data['isdelete'] != 0) exit(json_encode(array('error'=>'export is runing or is deleted')));
    if($data['type'] == 0){
        include_once dzz_libfile('eagleexport');
        $eagleexport = new eagleexport($data);
        $return = $eagleexport->initExport();
    }elseif($data['type'] == 1  ){
        include_once dzz_libfile('localexport');
        $localexport = new localexport($data);
        $return = $localexport->initExport();
    }elseif ($data['type'] == 2){
        include_once DZZ_ROOT.'dzz'.BS.'billfish'.BS.'class'.BS.'class_billfishexport.php';
        $billfishxport = new billfishxport($data);
        $return = $billfishxport->initExport();
    }
    exit(json_encode(array('success'=>true)));
    