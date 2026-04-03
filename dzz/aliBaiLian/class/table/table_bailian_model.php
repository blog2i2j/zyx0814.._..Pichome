<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class table_bailian_model extends dzz_table
{
    public function __construct()
    {

        $this->_table = 'bailian_model';
        $this->_pk = 'model';
        parent::__construct();
    }

    public function insert_by_model($arr){
        if($data=parent::fetch($arr['model'])){
            parent::update($arr['model'],$arr);
            return $data;
        }else{
            $arr['dateline']=TIMESTAMP;
            if(parent::insert($arr)){
                return $arr;
            }
            return false;
        }
    }

}