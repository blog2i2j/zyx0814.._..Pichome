<?php
namespace dzz\imageSearchAli\classes;

use \core as C;
use \DB as DB;
use \IO as IO;

class imageSearchupdate{

    public function run(&$data){

        if($data['rids']) {
            $ids=array();
            foreach(DB::fetch_all("select * from %t where rid IN(%n)",array('image_search_ali',$data['rids'])) as $value){
                $ids[]=$value['id'];
            }
            C::t('#imageSearchAli#image_search_ali')->update($ids,array('status'=>0,'retry'=>0,'dateline'=>TIMESTAMP));
        }
    }
}
