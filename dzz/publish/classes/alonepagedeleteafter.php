<?php
namespace dzz\publish\classes;
use \core as C;
use \DB as DB;

class alonepagedeleteafter
{
    public function run($data)
    {
       // $data = ['id' => $appid, 'isdel' => true];
        $id=$data['id'];

        if(empty($id)) return;
        //处理库删除
        foreach(DB::fetch_all("SELECT * FROM %t WHERE  ptype='4' and pval = %d",array('publish_list',$id)) as $value){
            C::t('#publish#publish_list')->delete_by_id($value['id'],true);
        }

    }

}