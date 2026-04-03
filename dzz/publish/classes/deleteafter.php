<?php
namespace dzz\publish\classes;
use \core as C;
use \DB as DB;

class deleteafter
{
    public function run($data)
    {
       // $data = ['rids' => $rids, 'deluid' => $uid, 'delusername' => $username];
        $rids=$data['rids'];

        if(empty($rids)) return;
        //处理单文件
        foreach(DB::fetch_all("SELECT * FROM %t WHERE  ptype='1' and pval IN (%n)",array('publish_list',$rids)) as $value){

            C::t('#publish#publish_list')->delete_by_id($value['id'],true);
        }
        //处理多文件
        $wheresql="ptype='2'";
        $params=array('publish_list');
        $arr=array();
        foreach($rids as $rid){
            $arr[]="FIND_IN_SET(%s,pval)";
            $params[]=$rid;
        }
        if($arr){
            $wheresql.=" AND (".implode(' OR ',$arr).")";
        }

        foreach(DB::fetch_all("SELECT * FROM %t WHERE $wheresql",$params) as $value){
            $pval=explode(',',$value['pval']);
            $n=array_diff($pval,$rids);
            $pval=implode(',',$n);
            C::t('#publish#publish_list')->update($value['id'],array('pval'=>$pval));
        }
    }

}