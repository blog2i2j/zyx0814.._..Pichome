<?php
namespace dzz\imageSearchAli\classes;

use \core as C;
use \DB as DB;
use \IO as IO;

class allowSearch{
    public function run(&$data){
        $setting = getglobal('setting/imageSearchAli_setting');
        $exts=$setting['exts']?(explode(',',$setting['exts'])):array();
        if(!$setting['status'] || empty($data['ext']) || (!in_array($data['ext'],$exts))){
            $data['allowImageSearch']=0;
        }else{
            $data['allowImageSearch']=1;
        }
    }
}