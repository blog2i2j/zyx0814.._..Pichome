<?php
if(!defined('IN_OAOOA')) {
    exit('Access Denied');
}
class table_pichome_sys extends dzz_table
{
    public function __construct()
    {

        $this->_table = 'pichome_sys';
        $this->_pk = 'id';
        $this->_pre_cache_key = 'pichome_sys';
        $this->_cache_ttl = 3600;
        parent::__construct();
    }
    
    public function fetch_by_rid($rid){
        $returnarr = [];
        foreach(DB::fetch_all("select DISTINCT labelname,id from %t where rid = %s",[$this->_table,$rid]) as $v){
            $returnarr[$v['labelname']] = $v['id'];
        }
        return $returnarr;
    }
    
    public function insert_data($setarr){
        if(DB::result_first("select id from %t where rid = %s and labelname = %s",[$this->_table,$setarr['rid'],$setarr['labelname']])){
            return true;
        }else{
            return parent::insert($setarr);
        }
    }
}