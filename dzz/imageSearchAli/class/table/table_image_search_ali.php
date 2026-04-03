<?php
if(!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class table_image_search_ali extends dzz_table
{
    public function __construct() {

        $this->_table = 'image_search_ali';
        $this->_pk    = 'id';
        $this->_pre_cache_key = 'image_search_ali_';
        $this->_cache_ttl = 600;

        parent::__construct();
    }

    public function insert_data($data){
        if(empty($data['dateline']))  $data['dateline']=TIMESTAMP;
        if($old=DB::fetch_first("select * from %t where rid=%s",array($this->_table,$data['rid']))){
            if(isset($data['aid']) && $old['aid']!=$data['aid']){
                $data['retry']=0;
                $data['status']=0;
            }elseif(isset($data['md5']) && $old['md5']!=$data['md5']){
                $data['retry']=0;
                $data['status']=0;
            }
            if(DB::update($this->_table,$data,array('id'=>$old['id']))){
                return $old['id'];
            }
        }else{
            return DB::insert($this->_table,$data,1);
        }
        return false;
    }

}