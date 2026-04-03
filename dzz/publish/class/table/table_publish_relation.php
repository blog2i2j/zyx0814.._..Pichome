<?php
if(!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class table_publish_relation extends dzz_table
{
    public function __construct()
    {

        $this->_table = 'publish_relation';
        $this->_pk = 'id';
        $this->_pre_cache_key = 'publish_relation_';
        $this->_cache_ttl = 60*60;

        parent::__construct();
    }

    public function update_by_pid($pid,$rpids=array()){
        $orpids=$this->fetch_rpid_by_pid($pid);
        $adds=array_diff($rpids,$orpids);
        $dels=array_diff($orpids,$rpids);
        if($dels){
            $ids=array();
            foreach(DB::fetch_all("SELECT id FROM %t WHERE pid=%d and rpid IN(%n)",array($this->_table,$pid,$dels)) as $value){
                $ids[]=$value['id'];
            }
            parent::delete($ids);
        }
        $i=0;
        if($adds){
            foreach($adds as $rpid){
                if($this->insertData(['pid'=>$pid,'rpid'=>$rpid])){
                    $i++;
                }
            }
        }
        return $i;
    }
    public function insertData($data){
        if($id = DB::result_first("select id from %t where pid = %d and rpid = %d", array($this->_table,$data['pid'],$data['rpid']))){
            return parent::update($id,['dateline'=>TIMESTAMP]);
        }else{
            if(empty($data['dateline'])){
                $data['dateline']=TIMESTAMP;
            }
            return parent::insert($data,1);
        }
    }

    public function fetch_pid_by_rpid($rpid){
        $pids = [];
        foreach(DB::fetch_all("select pid from %t where rpid = %d", array($this->_table,$rpid)) as $value){
            $pids[] = $value['pid'];
        }
        return $pids;
    }
    public function fetch_rpid_by_pid($pid){
        $rpids = [];
        foreach(DB::fetch_all("select rpid from %t where pid = %d", array($this->_table,$pid)) as $value){
            $rpids[] = $value['rpid'];
        }
        return $rpids;
    }

    public function delete_by_pid($pid){
        $i=0;
        foreach(DB::fetch_all("select id from %t where pid = %d", array($this->_table,$pid)) as $value){
            if( parent::delete($value['id'])){
                $i++;
            }
        }
        return $i;
    }

    public function delete_by_rpid($rpids,$pid=0){
        if(!is_array($rpids)) $rpids=array($rpids);
        $i=0;
        $sql="1";
        $params=array($this->_table);
        if($pid){
            $sql.=" and pid=%d";
            $params[]=$pid;
        }
        $sql.=" and rpid IN(%n)";
        $params[]=$rpids;
        foreach(DB::fetch_all("select * from %t where $sql", $params) as $value){
            if(parent::delete($value['id'])){
                $this->update_rpids_by_pid($value['pid']);
                $i++;
            }
        }
        return $i;
    }
    public function update_rpids_by_pid($pid){
        $rpids = $this->fetch_rpid_by_pid($pid);
        if(C::t('#publish#publish_list')->update($pid,['rpids'=>implode(',',$rpids)])){
            C::t('#publish#publish_list')->clear_cache_by_id($pid);
            return true;
        }
        return false;
    }
}