<?php
namespace dzz\imageSearchAli\classes;

use \core as C;
use \DB as DB;
use \IO as IO;


class imageSearchdel{

    public function run(&$data){

        $setting = getglobal('setting/imageSearchAli_setting');
        if(!$setting['status']) return;
        $api=new \apiClient($setting);
        if($data['rids']) {
            $ids=array();
            foreach(DB::fetch_all("select * from %t where rid IN(%n)",array('image_search_ali',$data['rids'])) as $value){
                $ids[]=$value['id'];
            }
            if(C::t('#imageSearchAli#image_search_ali')->delete($ids)){
                $api->docDelete($data['rids']);
            }


        }
    }
}
