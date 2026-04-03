<?php

if(!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class table_oaooa_auth extends dzz_table
{
    public function __construct() {

        $this->_table = 'oaooa_auth';
        $this->_pk    = 'id';
        parent::__construct();
    }

    public function fetch_all($state = 0){
        $params = array($this->_table);
        $wheresql = " 1 ";
        if($state){
            $wheresql .= " and state = %d ";
            $params[] = $state;
        }
        return DB::fetch_all("select * from %t where $wheresql",$params);
    }
}

