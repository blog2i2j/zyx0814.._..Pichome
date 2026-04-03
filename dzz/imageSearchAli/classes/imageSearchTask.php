<?php
namespace dzz\imageSearchAli\classes;

use \core as C;
use \DB as DB;
use \IO as IO;

class imageSearchTask{
    public function run(&$datas,$appid){
        $setting = getglobal('setting/imageSearchAli_setting');
        $exts=$setting['exts']?(explode(',',$setting['exts'])):array();

        if($setting['status']){
            if($total = DB::result_first("SELECT COUNT(*)  FROM %t where appid=%s", array('image_search_ali',$appid))) {
                $completed = DB::result_first("SELECT COUNT(*)  FROM %t where status=1 and appid=%s", array('image_search_ali', $appid));
                $datas[] = ['lablename' => lang('appname', array(), '', 'dzz/imageSearchAli'), 'data' => $completed . '/' . $total];
            }
        }
    }
}