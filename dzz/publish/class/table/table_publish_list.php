<?php

if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class table_publish_list extends dzz_table
{
    public function __construct()
    {

        $this->_table = 'publish_list';
        $this->_pk = 'id';
        $this->_pre_cache_key = 'publish_list_';
        $this->_cache_ttl = 60 * 60;

        parent::__construct();
    }

    public function insert_by_id($setarr)
    {

        if ($id = parent::insert($setarr, 1)) {
            //处理附件
            if ($setarr['aids']) {
                $aids = explode(',', $setarr['aids']);
                C::t('attachment')->addcopy_by_aid($aids);
            }
            //处理模板计数
            if ($setarr['tid']) {
                C::t('publish_template')->add_use_by_id($setarr['tid']);
            }
            //处理合集
            if ($setarr['ptype'] != 6) {//防止合集嵌套
                if ($setarr['rpids']) {
                    $rpids = explode(',', $setarr['rpids']);
                } else {
                    $rpids = array();
                }
                C::t('publish_relation')->update_by_pid($id, $rpids);
            }
            //处理地址
            if ($setarr['address']) {
                $url = 'index.php?mod=publish&id=' . $id;
                $address = C::t('pichome_route')->update_path_by_url($url, $setarr['address'], true);
                parent::update($id, ['address' => $address]);
            }
            $this->clear_cache_by_id($id);
            return $id;
        } else {
            return false;
        }

    }

    public function clear_cache_by_id($id)
    {
        $cachekeys = array('fetch_by_id_' . $id,'fetch_by_id_1_' . $id,'publish_perm_' . $id);
        return $this->clear_cache($cachekeys);
    }

    public function update_by_id($id, $setarr)
    {
        if (!$data = parent::fetch($id)) {
            return false;
        }

        if ($setarr['address'] && $data['address'] != $setarr['address']) {
            $url = 'index.php?mod=publish&id=' . $id;
            $address = C::t('pichome_route')->update_path_by_url($url, $setarr['address'], true);
            if ($address) {
                $setarr['address'] = $address;
            }
        }

        if ($ret = parent::update($id, $setarr)) {
            //处理附件
            if ($setarr['aids']) {
                $aids = explode(',', $setarr['aids']);

                $oaids = array();
                if ($data['aids']) {
                    $oaids = explode(',', $data['aids']);
                }
                $daids = array_diff($oaids, $aids);
                $iaids = array_diff($aids, $oaids);
                if ($iaids) {
                    C::t('attachment')->addcopy_by_aid($iaids);
                }
                if ($daids) {
                    C::t('attachment')->addcopy_by_aid($daids, -1);
                }
            }
            //处理模板计数
            if (isset($setarr['tid']) && $setarr['tid'] != $data['tid']) {
                C::t('publish_template')->add_use_by_id($setarr['tid'], $data['tid']);
            }
            //处理合集
            if ($setarr['ptype'] != 6) {//防止合集嵌套
                if ($setarr['rpids']) {
                    $rpids = explode(',', $setarr['rpids']);
                } else {
                    $rpids = array();
                }
                C::t('publish_relation')->update_by_pid($id, $rpids);
            }
            $this->clear_cache_by_id($id);

            return $ret;
        } else {
            return false;
        }

    }

    public function fetch_by_id($id,$hots=0) {
        global $_G;
        $cachekey = 'fetch_by_id_'.($hots?'1_':'') . $id;
        if ($data = $this->fetch_cache($cachekey)) {
            return $data;
        }
        else {
            $data = parent::fetch($id);
            if ($data['filter']) {
                $data['filter'] = unserialize($data['filter']);
            }

            //用户权限部分开始
            $data['view'] = unserialize($data['view']);
            $data['download'] = unserialize($data['download']);
            $data['share'] = unserialize($data['share']);
            //访问权限用户
            $vorgids = [];
            if (isset($data['view']['uids'])) {
                $vuidarr = $data['view']['uids'];
                //获取所有用户名
                $usernamearr = [];
                foreach (DB::fetch_all("select uid,username from %t where uid in(%n)", array('user', $vuidarr)) as $v) {
                    $usernamearr[] = ['uid' => $v['uid'], 'text' => $v['username']];
                }
                $data['view']['uids'] = $usernamearr;
                //获取所有的机构部门id
                $vorgids = [];
                $hasorgiduids = [];
                foreach (DB::fetch_all("select orgid,uid from %t where uid in(%n)", array('organization_user', $vuidarr)) as $v) {
                    $vorgids[] = $v['orgid'];
                    $hasorgiduids[] = $v['uid'];
                }
                $vorgids = array_unique($vorgids);
                $hasorgiduids = array_unique($hasorgiduids);
                $vothers = array_diff($vuidarr, $hasorgiduids);
                if ($vothers) {
                    $vorgids[] = 'other';
                }

            }

            if (isset($data['view']['groups'])) {

                $viewgroups = $data['view']['groups'];
                $vorgarr = [];
                $data['view']['groups'] = $vorgarr;
                if (in_array('other', $viewgroups)) {
                    $otherindex = array_search('other', $viewgroups);
                    unset($viewgroups[$otherindex]);
                    $vorgarr[] = ['orgid' => 'other', 'text' => lang('no_institution_users')];
                }

                foreach (DB::fetch_all("select orgname,orgid from %t where orgid in(%n)", ['organization', $viewgroups]) as $v) {
                    $vorgarr[] = ['orgid' => $v['orgid'], 'text' => $v['orgname']];
                }
                $data['view']['groups'] = $vorgarr;
                $vorgids = array_merge($vorgids, $viewgroups);
            }
            if ($vorgids) {
                $vvorgids = $vorgids;
                if (in_array('other', $vorgids)) {
                    $otherindex = array_search('other', $vorgids);
                    unset($vorgids[$otherindex]);
                }
                $tmporgids = [];
                foreach (DB::fetch_all("select pathkey from %t where orgid in(%n)", array('organization', $vorgids)) as $vo) {
                    $torgids = explode('-', str_replace('_', '', $vo['pathkey']));
                    $tmporgids = array_merge($tmporgids, $torgids);
                }
                $tmporgids = array_merge($tmporgids, $vvorgids);
                $tmporgids = array_unique(array_filter($tmporgids));
                $data['view']['expanded'] = $tmporgids;
            }
            //下载权限用户
            $dorgids = [];
            if (isset($data['download']['uids'])) {
                $duidarr = $data['download']['uids'];
                //获取所有用户名
                $usernamearr = [];
                foreach (DB::fetch_all("select uid,username from %t where uid in(%n)", array('user', $duidarr)) as $v) {
                    $usernamearr[] = ['uid' => $v['uid'], 'text' => $v['username']];
                }
                $data['download']['uids'] = $usernamearr;
                $hasorgiduids = [];
                foreach (DB::fetch_all("select orgid,uid from %t where uid in(%n)", array('organization_user', $duidarr)) as $ov) {
                    $dorgids[] = $ov['orgid'];
                    $hasorgiduids[] = $ov['uid'];
                }
                $dorgids = array_unique($dorgids);
                $hasorgiduids = array_unique($hasorgiduids);
                $dothers = array_diff($duidarr, $hasorgiduids);
                if ($dothers) {
                    $dorgids[] = 'other';
                }

            }

            if (isset($data['download']['groups'])) {
                $dgroups = $data['download']['groups'];
                $groupdatas = [];
                if (in_array('other', $dgroups)) {
                    $otherindex = array_search('other', $dgroups);
                    unset($dgroups[$otherindex]);
                    $groupdatas[] = ['orgid' => 'other', 'text' => lang('no_institution_users')];
                }

                foreach (DB::fetch_all("select orgname,orgid from %t where orgid in(%n)", ['organization', $dgroups]) as $v) {
                    $groupdatas[] = ['orgid' => $v['orgid'], 'text' => $v['orgname']];
                }
                $data['download']['groups'] = $groupdatas;
                $dorgids = array_merge($dorgids, $dgroups);
            }

            if ($dorgids) {
                $ddorgids = $dorgids;
                if (in_array('other', $dorgids)) {
                    $otherindex = array_search('other', $dorgids);
                    unset($dorgids[$otherindex]);
                }
                $tmporgids = [];
                foreach (DB::fetch_all("select pathkey from %t where orgid in(%n)", array('organization', $dorgids)) as $vo) {
                    $torgids = explode('-', str_replace('_', '', $vo['pathkey']));
                    $tmporgids = array_merge($tmporgids, $torgids);
                }
                $tmporgids = array_merge($tmporgids, $ddorgids);
                $tmporgids = array_unique(array_filter($tmporgids));
                $data['download']['expanded'] = $tmporgids;
            }
            //分享权限用户
            $sorgids = [];
            if (isset($data['share']['uids'])) {
                $suidarr = $data['share']['uids'];
                //获取所有用户名
                $usernamearr = [];
                foreach (DB::fetch_all("select uid,username from %t where uid in(%n)", array('user', $suidarr)) as $v) {
                    $usernamearr[] = ['uid' => $v['uid'], 'text' => $v['username']];
                }
                $data['share']['uids'] = $usernamearr;
                $hasorgiduids = [];
                foreach (DB::fetch_all("select orgid,uid from %t where uid in(%n)", array('organization_user', $suidarr)) as $ov) {
                    $sorgids[] = $ov['orgid'];
                    $hasorgiduids[] = $ov['uid'];
                }
                $sorgids = array_unique($sorgids);
                $hasorgiduids = array_unique($hasorgiduids);
                $sothers = array_diff($suidarr, $hasorgiduids);
                if ($sothers) {
                    $sorgids[] = 'other';
                }
            }
            if (isset($data['share']['groups'])) {
                $sgroups = $data['share']['groups'];
                $groupdatas = [];
                if (in_array('other', $dgroups)) {
                    $otherindex = array_search('other', $sgroups);
                    unset($sgroups[$otherindex]);
                    $groupdatas[] = ['orgid' => 'other', 'text' => lang('no_institution_users')];
                }

                foreach (DB::fetch_all("select orgname,orgid from %t where orgid in(%n)", ['organization', $sgroups]) as $v) {
                    $groupdatas[] = ['orgid' => $v['orgid'], 'text' => $v['orgname']];
                }
                $data['share']['groups'] = $groupdatas;
                $sorgids = array_merge($sorgids, $sgroups);
            }
            if ($sorgids) {
                $ssorgids = $sorgids;
                if (in_array('other', $sorgids)) {
                    $otherindex = array_search('other', $sorgids);
                    unset($sorgids[$otherindex]);
                }
                $tmporgids = [];
                foreach (DB::fetch_all("select pathkey from %t where orgid in(%n)", array('organization', $sorgids)) as $vo) {
                    $torgids = explode('-', str_replace('_', '', $vo['pathkey']));
                    $tmporgids = array_merge($tmporgids, $torgids);
                }
                $tmporgids = array_merge($tmporgids, $ssorgids);
                $tmporgids = array_unique(array_filter($tmporgids));
                $data['share']['expanded'] = $tmporgids;
            }
            if($data['pageset']){
                $data['pageset'] = unserialize($data['pageset']);
            }else{
                $data['pageset'] = array();
            }
            //处理aid数据
            foreach ($data['pageset'] as $k => $v){
                //兼容js类型
                if(is_numeric($v)){
                    $data['pageset'][$k]=intval($v);
                }
                if($k==='_file_cover') {
                    $tmparr=array();
                    $aid=is_array($v)?intval($v['aid']):intval($v);
                    if ($aid) {
                        $tmparr[] = [
                            'aid' => $aid,
                            'src' => IO::getFileUri('attach::' . $aid),
                        ];
                    } else {//获取默认封面图片
                        $cover=self::getCover($id);
                        $tmparr[]=[
                            'aid' => $cover['aid'],
                            'src' => $cover['src'],
                        ];
                    }
                    $data['pageset']['_file_cover'] = $tmparr;
                }else {
                    $karr = explode('_', $k);
                    if ($karr[0] . $karr[1] == 'file') {
                        $tmparr = [];
                        foreach ($v as $k1 => $v1) {
                            if ($k1 === 'src') continue;
                            $aid = 0;
                            if ($k1 === 'aid') {
                                $aid = intval($v1);
                            } elseif (intval($v1) > 0) {
                                $aid = intval($v1);
                            }
                            if ($aid) {
                                $attachment = C::t('attachment')->fetch($aid);
                                $tmparr[] = [
                                    'aid' => $aid,
                                    'src' => IO::getFileUri('attach::' . $aid),
                                ];
                            } else {
                                $tmparr[] = $v1;
                            }
                        }

                        $data['pageset'][$k] = $tmparr;
                    }
                    elseif ($karr[0] . $karr[1] == 'html') {
                        if($v=='<p><br></p>'){//处理编辑器空时会自动带入这段字符的问题
                            $data['pageset'][$k]='';
                        }
                    }
                    elseif ($karr[0] . $karr[1] == 'textarea') {
                        $data['pageset'][$k . '_html'] = \helper_security::checkhtml(nl2br($v));
                    }
                }
            }
            if(!isset($data['pageset']['_file_cover']) && $data['dtype']<6){
                $cover=self::getCover($id);
                $tmparr=array();
                $tmparr[]=[
                    'aid' => $cover['aid'],
                    'src' => $cover['src'],
                ];
                $data['pageset']['_file_cover'] =$tmparr;
            }
            if( $data['ptype']==6 && $data['flag']>0 && !isset($data['pageset']['condition'])) {
                $condition=array(
                    'keyword'=>'',
                    'ptype'=>['1','2','3','4','5'],
                    'starttime'=>'',
                    'endtime'=>''
                );
                $data['pageset']['condition']=$condition;
            }
            $data['collection'] = array();

            if ($data['rpids']) {
                $rpids = explode(',', $data['rpids']);
                foreach (parent::fetch_all($rpids) as $value) {
                    $data['collection'][] = $value['id'];
                }
            }
            //处理地址

            $url = 'index.php?mod=publish&id=' . $data['id'];
            $address = C::t('pichome_route')->update_path_by_url($url, $data['address']);
            if ($address) {
                $data['address'] = $address;
            }

            if($hots){
                $data['hots']=DB::result_first("select COUNT(*) from %t where idtype='6' and idval=%d",array('stats_view',$data['id']));
            }
            if ($data) {
                $this->store_cache($cachekey, $data);
            }
            return $data;
        }
    }
    public function getCover($id){
        global $_G;
        $data=parent::fetch($id);
        $permarr=['read1','read2','download1','download2','share'];
        $perm=perm::setPerm($permarr,1);
        switch ($data['ptype']){
            case 1; //单文件
                $resourcesdata=C::t('pichome_resources')->fetch_by_rid($data['pval'],1,1,$perm);
                if($resourcesdata['icondata']){
                   return [
                        'aid'=>0,
                        'src'=>str_replace($_G['siteurl'],'',$resourcesdata['icondata']),
                    ];
                }else{
                    return [
                        'aid'=>0,
                        'src'=>'index.php?mod=io&op=createThumb&path='.$resourcesdata['dpath'].'&size=small',
                    ];
                }
                break;
            case 2: //多文件
                $rids=explode(',',$data['pval']);

                foreach($rids as $rid){
                    $resourcesdata=C::t('pichome_resources')->fetch_by_rid($rid,1,1,$perm);
                    if($resourcesdata['icondata']){
                        return [
                            'aid'=>0,
                            'src'=>str_replace($_G['siteurl'],'',$resourcesdata['icondata']),
                        ];
                    }
                }
                if($rids[0]){
                    $resourcesdata=C::t('pichome_resources')->fetch_by_rid($rids[0],1,1,$perm);
                    return [
                        'aid'=>0,
                        'src'=>'index.php?mod=io&op=createThumb&path='.$resourcesdata['dpath'].'&size=small',
                    ];
                }
                break;
            case 3://库
                $rids=array();
                foreach(DB::fetch_all("select rid from %t where appid=%s order by dateline DESC",array('pichome_resources',$data['pval'])) as $value) {
                    $rid=$value['rid'];
                    $rids[]=$rid;
                    $resourcesdata=C::t('pichome_resources')->fetch_by_rid($rid,1,1,$perm);
                    if($resourcesdata['icondata']){
                        return [
                            'aid'=>0,
                            'src'=>str_replace($_G['siteurl'],'',$resourcesdata['icondata']),
                        ];
                    }
                }
                if($rids[0]){
                    $resourcesdata=C::t('pichome_resources')->fetch_by_rid($rids[0],1,1,$perm);
                    return [
                        'aid'=>0,
                        'src'=>'index.php?mod=io&op=createThumb&path='.$resourcesdata['dpath'].'&size=small',
                    ];
                }
                break;
            case 4://单页
                return [
                    'aid'=>0,
                    'src'=>'',
                ];
                break;
            case 5://智能数据
                $sql = " from %t r ";
                $params=array('pichome_resources');
                $wheresql = '1';
                $para=array();
                $vappids = array();
                $stdata = C::t('#intelligent#intelligent')->fetch_by_tid($data['pval']);
                if ($stdata['searchRange']) {
                    $appids = array_keys($stdata['searchRange']['appids']);
                    foreach ($stdata['searchRange']['folders'] as $pathkey => $v) {
                        $appids[] = $v['appid'];
                    }
                    $vappids = $appids;
                }else{
                    foreach (DB::fetch_all("select appid,path,view,type from %t where isdelete = 0", array('pichome_vapp')) as $v) {
                        if ($v['type'] != 3 && !IO::checkfileexists($v['path'],1)) {
                            continue;
                        }
                        if (C::t('pichome_vapp')->getpermbypermdata($v['view'],$v['appid'])) {
                            $vappids[] = $v['appid'];
                        }
                    }
                }

                if ($vappids) {
                    $wheresql .= ' and r.appid in(%n)';
                    $para[] = $vappids;

                    //处理范围分类

                    if ($stdata['searchRange']['folders']) {
                        $pathkeys = array_keys($stdata['searchRange']['folders']);
                    } else {
                        $pathkeys = array();
                    }
                    if ($pathkeys) {
                        $sql .= " LEFT JOIN %t frx on frx.rid = r.rid ";
                        $params[] = 'pichome_folderresources';
                        $warr = array();
                        foreach ($pathkeys as $pathkey) {
                            $warr[] = " frx.pathkey like %s ";
                            $para[] = str_replace('_', '\_', $pathkey) . '%';
                        }
                        if ($warr) {
                            $wheresql .= ' and (' . implode(' or ', $warr) . ')';
                        }
                    }

                    //处理名称包含
                    if ($stdata['extra'] && $stdata['extra']['searchName']) {
                        $arr = explode(' ', $stdata['extra']['searchName']);
                        $osqlarr = array();

                        foreach ($arr as $v) {
                            $nsqlarr = array();
                            if (strpos($v, '+') !== false) {
                                $andarr = explode('+', $v);
                                foreach ($andarr as $v1) {
                                    if (empty($v1)) continue;
                                    $nsqlarr[] = " r.name like %s ";
                                    $para[] = '%' . str_replace('_', '\_', $v1) . '%';
                                }
                                if ($andarr) {
                                    $osqlarr[] = '(' . implode(' and ', $nsqlarr) . ')';
                                }
                            } else {
                                $osqlarr[] = " r.name like %s ";
                                $para[] = '%' . str_replace('_', '\_', $v) . '%';
                            }
                        }
                        if ($osqlarr) {
                            $wheresql .= ' and (' . implode(' or ', $osqlarr) . ')';
                        }
                    }
                    //处理评分
                    if ($stdata['extra'] && $stdata['extra']['grade'] && !in_array('0', $stdata['extra']['grade'])) {
                        $wheresql .= ' and r.grade in(%s) ';
                        $para[] = $stdata['extra']['grade'];
                    }

                    //处理标签范围

                    if ($stdata['tags']) {
                        $tagtids = array();
                        $tagnames = explode(',', $stdata['tags']);

                        foreach (DB::fetch_all("select tid from %t where tagname IN(%n)", array('pichome_tag', $tagnames)) as $v2) {
                            $tagtids[$v2['tid']] = $v2['tid'];
                        }
                        foreach (DB::fetch_all("select langflag from %t where state>0", array('language')) as $value) {
                            $table = 'lang_' . strtolower(str_replace('-', '_', $value['langflag']));
                            foreach (DB::fetch_all("select * from %t where idtype='8' and filed='tagname' and svalue IN(%n)", array($table, $tagnames)) as $v2) {
                                $tagtids[$v2['idvalue']] = $v2['idvalue'];
                            }
                        }
                        if ($tagtids) {
                            $sql .= "left join %t rts on r.rid = rts.rid ";
                            $params[] = 'pichome_resourcestag';
                            $wheresql .= ' and rts.tid in(%n) ';
                            $para[] = $tagtids;
                        }
                    }
                }else{
                    $wheresql .= ' and 0 ';
                }
                if($para) {
                    $params = array_merge($params, $para);
                }

                $ordersql = ' order by r.dateline DESC limit 100';
                $rids=array();
                foreach(DB::fetch_all("select r.rid $sql where $wheresql $ordersql", $params) as $value) {
                    $rid=$value['rid'];
                    $rids[]=$rid;
                    $resourcesdata=C::t('pichome_resources')->fetch_by_rid($rid,1,1,$perm);

                    if($resourcesdata['icondata']){
                        return [
                            'aid'=>0,
                            'src'=>str_replace($_G['siteurl'],'',$resourcesdata['icondata']),
                        ];
                    }
                }
                if($rids[0]){
                    $resourcesdata=C::t('pichome_resources')->fetch_by_rid($rids[0],1,1,$perm);
                    return [
                        'aid'=>0,
                        'src'=>'index.php?mod=io&op=createThumb&path='.$resourcesdata['dpath'].'&size=small',
                    ];
                }
                break;
            case 6://合集
                return [
                    'aid'=>0,
                    'src'=>'',
                ];
                break;
        }
        return [
            'aid'=>0,
            'src'=>'',
        ];
    }
    public function getPermById($id, $uid = 0)
    {

        global $_G;
        if(empty($uid)) $uid=$_G['uid'];
        $cachekey = 'publish_perm_' . $id.'_'.$uid;
        if($perm=$this->fetch_cache($cachekey)){
            return $perm;
        }elseif ($_G['adminid'] == 1){
            $permarr=['read1','read2','download1','download2','share'];
            $perm=perm::setPerm($permarr,1);
            $this->store_cache($cachekey,$perm);
            return $perm;
        }
        $permdata = DB::fetch_first("select view,download,share from %t where id = %d", [$this->_table, $id]);
        foreach ($permdata as $k => $v) {
            if ($v) {
                $permdata[$k] = unserialize($v);
            }
        }
        $uid = $uid ? $uid : $_G['uid'];

        $uorgids = [];
        if ($uid) {
            //获取用户机构部门数据
            foreach (DB::fetch_all("select ou.orgid,o.pathkey from %t ou left join %t o on o.orgid=ou.orgid 
                where ou.uid = %d", array('organization_user', 'organization', $uid)) as $value) {
                $tmporgids = explode('-', str_replace('_', '', $value['pathkey']));
                $torgids = [];
                if($tmporgids) {
                    $torgids = array_merge($torgids, $tmporgids);
                }
                $torgids = array_unique(array_filter($torgids));
                $uorgids = array_merge($uorgids, $torgids);
            }
        }
        $perm=1;
        $permarr=array();

        foreach($permdata as $k=>$v) {
            $hasperm=0;
            $hasother = false;
            //判断是否包含无用户组用户
            if (isset($v['groups'])) {
                if (in_array('other', $v['groups'])) {
                    $otherindex = array_search('other', $v['groups']);
                    unset($v['groups'][$otherindex]);
                    $hasother = true;
                }
            }
            if(!is_array($v)){
                if($v==1){ //任何人
                    $hasperm=1;
                }
            }elseif($hasother && empty($uorgids)){
                $hasperm=1;
            }elseif($v['uids'] && in_array($uid, $v['uids'])) {
                   $haseperm=1;
            }elseif($v['groups'] && $uorgids) {
                $intersectarr = array_intersect($v['groups'], $uorgids);
                if (!empty($intersectarr)) $hasperm=1;
            }
            if($hasperm){
                if($k=='view'){
                    $permarr[]='read1';
                    $permarr[]='read2';
                }elseif($k=='download'){
                    $permarr[]='download1';
                    $permarr[]='download2';
                }elseif($k=='share'){
                    $permarr[]='share';
                }
            }
        }
        $perm=perm::setPerm($permarr,$perm);
        $this->store_cache($cachekey,$perm);
        return $perm;
    }

    public function checkpermById($id, $uid = 0, $perm = 'view')
    {
        global $_G;
        if ($_G['adminid'] == 1) return true;
        $permdata = DB::result_first("select $perm from %t where id = %d", [$this->_table, $id]);
        if ($permdata) $permdata = unserialize($permdata);
        else return false;
        if(!is_array($permdata)){
            if($permdata==1){
                return true;
            }else{
                return false;
            }
        }
        $uid = $uid ? $uid : $_G['uid'];
        $uorgids = [];
        if ($uid) {
            //获取用户机构部门数据
            foreach (DB::fetch_all("select ou.orgid,o.pathkey from %t ou left join %t o on o.orgid=ou.orgid 
                where ou.uid = %d", array('organization_user', 'organization', $uid)) as $v) {
                $tmporgids = explode(',', str_replace('-', '', $v['pathkey']));
                $torgids = [];
                foreach ($tmporgids as $ov) {
                    $tmpgid = explode('_', $ov);
                    $torgids = array_merge($torgids, $tmpgid);
                }
                $torgids = array_unique(array_filter($torgids));
                $uorgids = array_merge($uorgids, $torgids);
            }
        }
        $hasother = false;
        //判断是否包含无用户组用户
        if (isset($permdata['groups'])) {
            if (in_array('other', $permdata['groups'])) {
                $otherindex = array_search('other', $permdata['groups']);
                unset($permdata['groups'][$otherindex]);
                $hasother = true;
            }
        }
        //判断有权限用户中是否有当前用户
        if ($permdata['uids'] || $hasother) {
            //查询无组用户
            if ($hasother) {
                foreach (DB::fetch_all("select u.uid from %t u left join %t ou on u.uid=ou.uid where 1", array('user', 'organization_user')) as $u) {
                    $permdata['uids'][] = $u['uid'];
                }
            }
            if (in_array($uid, $permdata['uids'])) return true;
        }
        //判断有权限组中是否有当前用户
        if ($permdata['groups']) {
            $intersectarr = array_intersect($permdata['groups'], $uorgids);
            if (!empty($intersectarr)) return true;
        }
        return false;
    }

    //删除发布
    public function delete_by_id($id,$force=false)
    {
        if (!$data = parent::fetch($id)) {
            return false;
        }
        if(!$force && $data['pstatus']<2){
           return parent::update($id, ['pstatus' => 2]);
        }
        if ($ret = parent::delete($id)) {
            //处理aids;
            if ($data['aids']) {
                $aids = explode(',', $data['aids']);
                C::t('attachment')->addcopy_by_aid($aids, -1);
            }
            //处理tid
            if ($data['tid']) {
                C::t('#publish#publish_template')->add_use_by_id(0, $data['tid']);
            }
            //处理集合
            if ($data['ptype'] != 6) {
                C::t('#publish#publish_relation')->delete_by_pid($data['id']);
            } else {
                C::t('#publish#publish_relation')->delete_by_rpid($data['id']);
            }
            //处理地址
            if ($data['address']) {
                C::t('pichome_route')->delete_by_path($data['address']);
            }
            $this->clear_cache_by_id($id);
        }
        return $ret;
    }
}



