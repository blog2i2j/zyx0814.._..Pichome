<?php
namespace dzz\publish\classes;
use \core as C;
use \DB as DB;

class vappdeleteafter
{
    public function run($data)
    {
       // $data = ['appid' => $appid, 'isdel' => true];
        $appid=$data['appid'];

        if(empty($appid)) return;
        //处理库删除
        foreach(DB::fetch_all("SELECT * FROM %t WHERE  ptype='3' and pval = %s",array('publish_list',$appid)) as $value){
            C::t('#publish#publish_list')->delete_by_id($value['id'],true);
        }
    }

}