<?php
if(!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class table_publish_template extends dzz_table
{
    public function __construct()
    {

        $this->_table = 'publish_template';
        $this->_pk = 'id';
        $this->_pre_cache_key = 'publish_template_';
        $this->_cache_ttl = 60*60;

        parent::__construct();
    }

    public function fetch_by_ttype($ttype){
        $data=array();
        foreach(DB::fetch_all("select * from %t where ttype = %d",array($this->_table,$ttype)) as $value){
            $value['cover']='dzz/publish/template/'.$value['tdir'].'/'.$value['tflag'].'/thumb.png';
            $data[]=$value;
        }
        return $data;
    }

    public function add_use_by_id($id=0,$oid=0){//前面id增加1，后面id减少1
        $i=0;
        if($oid) {
            if(DB::query("update %t set cuse=IF(cuse<1,0,cuse-%d) where id IN(%n)", array($this->_table, 1, $oid))){
                $i++;
            }
        }
        if($id){
            if(DB::query("update %t set cuse=cuse+%d where id = %d", array($this->_table, 1, $id))){
                $i++;
            }
        }
        return $i;
    }
}