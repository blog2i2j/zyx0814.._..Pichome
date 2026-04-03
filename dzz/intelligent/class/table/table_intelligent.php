<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 *
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */
if(!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class table_intelligent extends dzz_table
{
    public function __construct() {

        $this->_table = 'intelligent';
        $this->_pk    = 'tid';
        $this->_pre_cache_key = 'intelligent_';
        $this->_cache_ttl = 60*60;

        parent::__construct();
    }
    public function insert_by_tid($setarr){
        if($tid=parent::insert($setarr,1)){
            $this->clear_cache('fetch_all_data');
        }
        return $tid;
    }
    public function update_by_tid($tid,$setarr){
        if($ret=parent::update($tid,$setarr)){
            $this->delete_cache_by_tid($tid);
        }
        return $ret;
    }
    public function delete_by_tid($tid){
        if($ret=parent::delete($tid)){
            $hookdata = ['tid' => $tid, 'delusid' => getglobal('uid'),'delusername'=>getglobal('username')];
            Hook::listen('intelligentdeleteafter', $hookdata);
            $this->delete_cache_by_tid($tid);
        }
        return $ret;
    }
    function delete_cache_by_tid($tid){
        $cachekeys=array('fetch_by_tid_'.$tid,'fetch_all_data','getFidsBysearchRange_'.$tid);
        $this->clear_cache($cachekeys);
    }

    public function fetch_all_data(){

        $data=array();
        $cachekey = 'fetch_all_data';
        if($data=$this->fetch_cache($cachekey)){
            return $data;
        }else {
            foreach (DB::fetch_all("select * from %t where 1 order by disp asc,dateline desc", array($this->_table)) as $value) {
                if ($value['screen']) {
                    $value['screen'] = json_decode($value['screen'], true);
                } else {
                    $value['screen'] = array();
                }
                if ($value['pagesetting']) {
                    $value['pagesetting'] = json_decode($value['pagesetting'], true);
                    if ($value['pagesetting']['layout']) $value['layout'] = $value['pagesetting']['layout'];
                    else {
                        $value['layout'] = 'waterFall';
                    }
                } else {
                    $value['pagesetting'] = array();
                    $value['layout'] = 'waterFall';
                }
                if ($value['searchRange']) {
                    $arr = $this->getFidsBysearchRange($value['searchRange']);

                    $appnames = array();
                    foreach ($arr['appids'] as $v) {
                        $appnames[] = $v['appname'];
                    }
                    foreach ($arr['folders'] as $v) {
                        $appnames[] = $v['appname'] . '-' . $v['fname'];
                    }
                    $value['searchRange_names'] = implode(',', $appnames);
                    $value['searchRange'] = $arr;
                } else {
                    $value['searchRange'] = array();
                    $value['searchRange_names'] = lang('all_library');
                }
                if ($value['extra']) {
                    $value['extra'] = json_decode($data['extra'], true);

                } else {
                    $value['extra'] = [];

                }
                $data[$value['tid']] = $value;
            }
            if($data) $this->store_cache($cachekey, $data);
        }
       // Hook::listen('lang_parse',$data,['getSearchtemplateLangData',1]);
        return $data;
    }
    public function fetch_by_tid($tid){
        $cachekey = 'fetch_by_tid_'.$tid;
        if(0 && $data=$this->fetch_cache($cachekey)){
            return $data;
        }else {
            $value = parent::fetch($tid);
            if ($value['screen']) {
                $value['screen'] = json_decode($value['screen'], true);
            } else {
                $value['screen'] = array();
            }
            if ($value['pagesetting']) {
                $value['pagesetting'] = json_decode($value['pagesetting'], true);
                if ($value['pagesetting']['layout']) $value['layout'] = $value['pagesetting']['layout'];
                else {
                    $value['layout'] = 'waterFall';
                }
            } else {
                $value['pagesetting'] = array();
                $value['layout'] = 'waterFall';
            }
            if ($value['searchRange']) {
                $arr = $this->getFidsBysearchRange($value['searchRange']);

                $appnames = array();
                foreach ($arr['appids'] as $v) {
                    $appnames[] = $v['appname'];
                }
                foreach ($arr['folders'] as $v) {
                    $appnames[] = $v['appname'] . '-' . $v['fname'];
                }
                $value['searchRange_names'] = implode(',', $appnames);
                $value['searchRange'] = $arr;
            } else {
                $value['searchRange'] = array();
                $value['searchRange_names'] = lang('all_library');
            }
            if ($value['extra']) {
                $value['extra'] = json_decode($value['extra'], true);

            } else {
                $value['extra'] = [];

            }
            $this->store_cache($cachekey, $value);
        }
        return $value;
    }

    public function getEditSearchRange($range,$tid=0){
        if (empty($range)) {
            return array(
                'ids' => [],
                'expanded' => [],
                'data' => []
            );
        }

        $expanded = [];
        $items = array();
        $fids = explode(',', $range);

        foreach ($fids as $fid) {
            $length = strlen($fid);
            if ($length == 6) {
                $expanded[$fid] = $fid;
                $app = C::t('pichome_vapp')->fetch($fid);
                $items[$fid] = [
                    'id' => $fid,
                    'text' => $app['appname'],
                    'type' => 'library'
                ];
            } elseif ($length > 6) {
                $appid = substr($fid, 0, 6);
                $expanded[$appid] = $appid;
                $str = '';
                $app = C::t('pichome_vapp')->fetch($appid);
                $items[$appid . $str] = [
                    'id' => $appid,
                    'text' => $app['appname'],
                    'type' => 'library'
                ];
                $fid = substr($fid, 6);

                $arr = str_split($fid, 19);
                foreach ($arr as $v) {
                    $folder = C::t('pichome_folder')->fetch($v);
                    $str .= $v;
                    $expanded[$appid . $str] = $appid . $str;
                    $items[$appid . $str] = [
                        'id' => $appid . $str,
                        'text' => $folder['fname'],
                        'type' => 'folder'
                    ];
                }

            }
        }
        return array(
            'ids' => $fids,
            'expanded' => array_values($expanded),
            'data' => $items
        );

    }

    public function getFidsBysearchRange($range,$tid=0){

        if(empty($range)) {
            return  array(
                'appids'=>[],
                'folders'=>[],
            );
        }
        $cachekey = 'getFidsBysearchRange_'.$tid;
        if($data=$this->fetch_cache($cachekey)){
            return $data;
        }else {
            $appids = [];
            $folders = array();
            $ids = explode(',', $range);

            foreach ($ids as $fid) {
                $length = strlen($fid);
                if ($length == 6) {
                    if ($app = C::t('pichome_vapp')->fetch($fid)) {
                        $appids[$fid] = array(
                            'appid' => $app['appid'],
                            'appname' => $app['appname']
                        );
                    }
                } elseif ($length > 6) {
                    $appid = substr($fid, 0, 6);
                    $app = C::t('pichome_vapp')->fetch($appid);
                    $fid = substr($fid, 6);

                    $arr = str_split($fid, 19);
                    $fnames = array();
                    foreach ($arr as $v) {
                        if ($folder = C::t('pichome_folder')->fetch($v)) {
                            $fnames[] = $folder['fname'];
                        }
                    }

                    $folder['appname'] = $app['appname'];
                    $folder['pathname'] = $fnames;

                    $folders[$fid] = $folder;
                }
            }
            $data = array(
                'appids' => $appids,
                'folders' => $folders
            );
            $this->store_cache($cachekey, $data);
            return $data;
        }
    }

}
