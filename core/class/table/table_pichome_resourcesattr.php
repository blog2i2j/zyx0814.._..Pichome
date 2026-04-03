<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 *
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class table_pichome_resourcesattr extends dzz_table
{
    public function __construct()
    {

        $this->_table = 'pichome_resourcesattr';
        $this->_pk = 'id';

        parent::__construct();
    }

    //删除某项属性
    public function delete_by_id($id)
    {
        if (!$data = parent::fetch($id)) return false;
        if ($ret = parent::delete($id)) {
            $gid = DB::result_first("select gid from %t where rid = %s", array('tab', $data['rid']));
            $cachekey = 'resourcesattr_data_' . $data['rid'];
            $this->clear_cache($cachekey);
        }
        return $ret;
    }

    //更新某项属性
    public function update($id, $setarr, $unbuffered = false, $low_priority = false)
    {
        if (!$data = parent::fetch($id)) return false;
        if ($ret = parent::update($id, $setarr)) {
            $gid = DB::result_first("select gid from %t where rid = %s", array('tab', $setarr['rid']));
           // $solrdata = array('rid' => $setarr['rid'], 'gid' => $gid);
           // \Hook::listen('changetabattrafter_updateindex', $solrdata);
            $cachekey = 'resourcesattr_data_' . $data['rid'];
            $this->clear_cache($cachekey);
            self::update_searchattr_by_rid($data['rid']);
        }
        return $ret;
    }

    //添加属性
    public function insert($setarr, $return_insert_id = false, $replace = false, $silent = false)
    {
        if ($id = DB::result_first("select id from %t where rid=%d and skey=%s ", array($this->_table, $setarr['rid'], $setarr['skey']))) {
            $ret = self::update($id, $setarr);
        } else {
            if ($id = parent::insert($setarr, 1)) {

                $gid = DB::result_first("select gid from %t where rid = %s", array('tab', $setarr['rid']));
                $solrdata = array('rid' => $setarr['rid'], 'gid' => $gid);
               // \Hook::listen('changetabattrafter_updateindex', $solrdata);
                $cachekey = 'resourcesattr_data_' . $setarr['rid'];
                $this->clear_cache($cachekey);
                self::update_searchattr_by_rid($setarr['rid']);

            }
        }
        return $id;
    }

    public function fetchDataByTid($rid, $noskeyarr = array())
    {
        $cachekey = 'tab_attr_data_' . $rid;
        $returndata = array();
        if ($returndata = $this->fetch_cache($cachekey)) {

        } else {
            $params = array($this->_table, $rid);
            $wheresql = ' rid = %s ';
            if ($noskeyarr) {
                $wheresql .= " and skey not in(%n)";
                $params[] = $noskeyarr;
            }
            foreach (DB::fetch_all("select * from %t where $wheresql ", $params) as $val) {

                $returndata[$val['skey']] = $val['svalue'];
            }
            $this->store_cache($cachekey, $returndata);
        }
        return $returndata;
    }

    //查询标签属性
    public function fetch_by_rid($rid, $noskeyarr = array())
    {
        $params = array($this->_table, $rid);
        $wheresql = ' rid = %s ';
        if ($noskeyarr) {
            $wheresql .= " and skey not in(%n)";
            $params[] = $noskeyarr;
        }
        foreach (DB::fetch_all("select * from %t where $wheresql ", $params) as $val) {

            $returndata[$val['skey']] = $val['svalue'];
        }
        Hook::listen('lang_parse', $returndata, ['getTabattrLangData', $rid]);
        return $returndata;

    }

    public function insert_attr($rid, $attrs = array())
    {
        $i = 0;
        foreach ($attrs as $k => $v) {
            $setarr = array('rid' => $rid, 'skey' => $k, 'svalue' => $v);
            if (self::insert($setarr)) {
                $i++;
            }
        }

        return $i;
    }

    //删除某标签属性
    public function delete_by_rid($rid)
    {
        if (!is_array($rid)) $rid = (array)$rid;
        $i = 0;
        foreach (DB::fetch_all("select id,skey,rid from %t where rid in(%n)", array($this->_table, $rid)) as $value) {
            if (self::delete_by_id($value['id'])) {
                //C::t('#tab#tab_rangedate')->delete_by_filed($value['skey'], $value['rid']);
                $i++;
            }
        }
        Hook::listen('lang_parse', $rid, ['delTabattrLangData']);
        return $i;
    }
    //更新属性值
    public function update_by_skey($rid, $skeyarr)
    {
        $i = 0;
        foreach ($skeyarr as $k => $v) {
            $setarr = array('rid' => $rid, 'skey' => $k, 'svalue' => $v);
            if (self::insert($setarr)) {
                $i++;
            }
        }
        return $i;
    }

    //查询某属性值
    public function fetch_by_skey($skey, $rid)
    {
        $params = array($this->_table, $skey, $rid);
        $wheresql = ' skey = %s and rid = %s';
        $svalue = DB::result_first("select svalue from %t where $wheresql", $params);
        $data = [$skey => $svalue];
        Hook::listen('lang_parse', $data, ['getTabattrLangData',$rid]);
        return $data[$skey];
    }

    //删除某属性
    public function delete_by_skey($skey)
    {
        $i = 0;
        if (C::t('form_setting')->delete_by_flag($skey)) {
            foreach (DB::fetch_all("select id from %t where skey = %s", array($this->_table, $skey)) as $value) {
                if (self::delete_by_id($value['id'])) {
                    $i++;
                }
            }
        }

        return $i;
    }

    public function update_val_by_flag($flag, $setarr)
    {
        if (!$flag) return;
        $params = [$this->_table, $flag];
        $wheresql = " skey = %s ";
        //定义原值数据和新值数组
        $newvals = $oldvals = [];
        //查询条件
        $orsql = [];
        //符合替换的条件
        if (isset($setarr['editval'])) {
            foreach ($setarr['editval'] as $v) {
                $newvals[] = $v['newval'];
                $oldvals[] = $v['oldval'];
                $orsql[] = " find_in_set(%s,svalue)";
                $params[] = $v['oldval'];
            }
        }
        //符合删除的条件
        if (isset($setarr['delval'])) {
            foreach ($setarr['delval'] as $v) {
                $orsql[] = " find_in_set(%s,svalue)";
                $params[] = $v;
            }
        }
        if ($orsql) $wheresql .= " and (" . implode(' or ', $orsql) . ')';

        foreach (DB::fetch_all("select id,svalue from %t where $wheresql", $params) as $v) {
            //处理原值为数组
            $svalarr = explode(',', $v['svalue']);
            //移除数组中需要删除的
            foreach ($svalarr as $sk => $sv) {
                if (in_array($sv, $setarr['delval'])) {
                    unset($svalarr[$sk]);
                }
            }
            //替换需要修改的
            foreach ($oldvals as $ok => $ov) {
                if (in_array($ov, $svalarr)) {
                    $index = array_search($ov, $svalarr);
                    $svalarr[$index] = $newvals[$ok];
                }
            }
            $nsval = implode(',', $svalarr);
            $this->update($v['id'], ['svalue' => $nsval]);


        }

    }

    public function update_searchattr_by_rid($rid)
    {
        $resdata = C::t('pichome_resources')->fetch_by_rid($rid);
        if((!$resdata)) return false;
        $attrdata = C::t('pichome_resources_attr')->fetch($rid);
        $resdata = array_merge($resdata,$attrdata);
        $forms = C::t('form_setting')->fetch_flags_by_appid($resdata['appid']);
        $aforms =[];
        foreach($forms as $v){
            $aforms[$v['flag']]=$v;
        }
        $data = self::fetch_by_rid($rid);
        //获取特殊类型值加入到搜索属性
        foreach($aforms as $v){
            if(in_array($v['type'],['inputselect','inputmultiselect'])){
                foreach (DB::fetch_all("select valid from %t   where rid =%d and filed = %s ", array('tab_filedval', $rid, $v['flag'])) as $value) {
                    $o[] = $value['valid'];
                }
                if($o){
                    $valdatas = C::t('form_filedvals')->fetch_by_id($o);
                    $valdata = array_column($valdatas,'filedval');
                    $data[$v['flag']]= implode('', $valdata);
                }
            }
        }
        $svalue = $resdata['name'].getstr($resdata['desc'],255).$resdata['link'];
        foreach ($data as $key => $val) {
            if (empty($val)) continue;
            $form = $aforms[$key];
            if (!in_array($form['type'], array('label', 'textarea', 'select', 'multiselect', 'fulltext','inputselect','inputmultiselect'))) {
                continue;
            }
            if ($form['type'] == 'fulltext') $val = trim(strip_tags($val));
            if ($val) $svalue .= '[' . $key . ']' . $val . '[/' . $key . ']';
        }

        if (empty($svalue)) return false;
        $setarr = array(
            'rid' => $rid,
            'skey' => 'searchattr',
            'svalue' => $svalue,
        );
        if ($id = DB::result_first("select id from %t where rid=%d and skey=%s ", array($this->_table, $setarr['rid'], $setarr['skey']))) {
            parent::update($id, $setarr);
        } else {
            $id = parent::insert($setarr, 1);
        }
        Hook::listen('lang_parse',$rid, ['updateResourcesSearchvalData']);
        return $id;
    }

    public function getSearchvaldataByrid($rid)
    {
        $forms = C::t('form_setting')->fetch_all_data();
        $data = self::fetch_by_rid($rid);
        $svalue = '';
        foreach ($data as $key => $val) {
            if (empty($val)) continue;
            $form = $forms[$key];
            if (!in_array($form['type'], array('label', 'input', 'textarea', 'select', 'multiselect', 'fulltext'))) {
                continue;
            }
            if ($val) $svalue .= '[' . $key . ']' . $val . '[/' . $key . ']';
        }

        if (empty($svalue)) return false;
        $setarr = array(
            'rid' => $rid,
            'skey' => 'searchattr',
            'svalue' => $svalue,
        );
        if ($id = DB::result_first("select id from %t where rid=%d and skey=%s ", array($this->_table, $setarr['rid'], $setarr['skey']))) {
            parent::update($id, $setarr);
        } else {
            $id = parent::insert($setarr, 1);
        }
        return $id;
    }
}

