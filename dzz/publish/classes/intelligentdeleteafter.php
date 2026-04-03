<?php
namespace dzz\publish\classes;
use \core as C;
use \DB as DB;

class intelligentdeleteafter
{
    public function run($data)
    {
       // $data = ['appid' => $appid, 'isdel' => true];
        $tid=$data['tid'];

        if(empty($tid)) return;
        //处理库删除
        foreach(DB::fetch_all("SELECT * FROM %t WHERE  ptype='5' and pval = %d",array('publish_list',$tid)) as $value){

            C::t('#publish#publish_list')->delete_by_id($value['id'],true);
        }

    }

}