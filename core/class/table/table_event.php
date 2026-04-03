<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class table_event extends dzz_table
{
    public function __construct()
    {

        $this->_table = 'event';
        $this->_pk = 'id';

        parent::__construct();
    }

    //添加群组动态
    public function addevent($data)
    {
        if (empty($data)) return false;
        if(empty(getglobal('uid'))) return false;
        if(empty($data['event_body'])) return false;
        $eventArr = array(
            'appid' => $data['appid'],
            'appname' => $data['appname'],
            'id'=>$data['id'],
            'idtype'=>$data['idtype'],
            'name'=>$data['name'],
            'event_body' => $data['event_body'],
            'event_data' => is_array($data['event_data'])?json_encode($data['event_data']):$data['event_data'],
            'uid' => isset($data['uid'])?intval($data['uid']):getglobal('uid'),
            'username' => isset($data['username'])?$data['username']:getglobal('username'),
            'dateline' => isset($data['dateline'])?intval($data['dateline']):TIMESTAMP,

            'state'=>intval($data['state']),
            'views'=>intval($data['views']),
        );
        if ($eid = parent::insert($eventArr, 1)) {
            return $eid;
        } else {
            return false;
        }
    }

    public function delete_by_appid($appid)
    {
        $i=0;
        if(empty($appid)) return $i;
        foreach(DB::fetch_all("select eid from %t where appid=%s",array($this->_table,$appid)) as $value){
            if(parent::delete($value['eid'])){
                $i++;
            }
        }
        return $i;
    }
    public function delete_by_idtype($idtype,$id='')
    {
        $i=0;
        if(empty($idtype)) return $i;
        $params=array($this->_table,$idtype);
        $sql="idtype=%s";
        $params[]=$idtype;
        if($id){
            $sql.=" and id=%s";
            $params[]=$id;
        }
        foreach(DB::fetch_all("select eid from %t where $sql",$params) as $value){
            if(parent::delete($value['eid'])){
                $i++;
            }
        }
        return $i;
    }




    public function fetch_all_by_eid($gid,$start=0,$limit=0,$state=1)
    {
        $gid = intval($gid);
        $time = date('Y-m-d');
        $starttime = strtotime($time);
        $endtime = $starttime + 3600 * 24;
        $events = array();
		$limitsql = $limit ? DB::limit($start, $limit) : '';
		$sql="gid = %d and dateline > %d and dateline < %d ";
		if($state){
			$sql.=" and state>0";
		}
        foreach (DB::fetch_all("select * from %t where $sql order by dateline desc $limitsql", array($this->_table, $gid, $starttime, $endtime)) as $v) {
            $v=self::format_event_data($v);

            $events[] = $v;
        }
        return $events;
    }

    public function emoji_decode($str)
    {
        $text = json_encode($str); //暴露出unicode
        $text = preg_replace_callback('/\\\\\\\\/i', function ($str) {
            return '\\';
        }, $text); //将两条斜杠变成一条，其他不动
        return json_decode($text);
    }

    //根据fid查询评论
    public function fetch_comment_by_fid($fid, $count = false, $start = 0, $limit = 0,$state=1)
    {
        $fid = intval($fid);
        $params = array($this->_table, $fid, 1);
        $limitsql = $limit ? DB::limit($start, $limit) : '';
		$sql="pfid = %d  and `type`= %d";
		if($state){
			$sql.=" and state>0";
		}
        if ($count) {
            return DB::result_first("select count(*) from %t where $sql", $params);
        }
        $events = array();
        foreach (DB::fetch_all("select * from %t where $sql order by dateline desc $limitsql", $params) as $v) {
            $v=self::format_event_data($v);
            $uids[] = $v['uid'];
            $events[] = $v;
        }
        if (count($events)) {
            $events = self::result_events_has_avatarstatusinfo($uids, $events);
        }


        return $events;
    }

    //根据fid查询评论
    public function fetch_comment_by_rid($rid, $count = false, $start = 0, $limit = 0,$state=1)
    {
        $rid = trim($rid);
        $params = array($this->_table, $rid, 1);
        $limitsql = $limit ? DB::limit($start, $limit) : '';
		$sql="rid = %s and `type`= %d";
		if($state){
			$sql.=" and state>0";
		}
        if ($count) {
            return DB::result_first("select count(*) from %t where  $sql", $params);
        }
        $uid = array();
        $events = array();
        foreach (DB::fetch_all("select * from %t where $sql order by dateline desc $limitsql", $params) as $v) {
           $v=self::format_event_data($v);
            $uids[] = $v['uid'];
            $events[] = $v;
        }
        if (count($events)) {
            $events = self::result_events_has_avatarstatusinfo($uids, $events);
        }

        return $events;
    }

    //根据rid查询动态,如果rid为目录，也不查询目录下级动态
    public function fetch_by_rid($rids, $start = 0, $limit = 0, $count = false, $type = false,$gids=array(),$state=1)
    {
		 if (!is_array($rids)) $rids = (array)$rids;
		
		$sql = "1";
		$sqlor=array();
		$params= array($this->table);
		
		$sql.=" and e.rid IN(%n)";
		$params[]=$rids;
		if($gids){
			 $sql .= ' and e.gid IN(%n) ';
			$params[]=$gids;
		}	
        if ($type) {
            $type = $type - 1;
            $sql .= ' and e.type = ' . $type;
        }
		if($state){
			$sql .= ' and e.state>0';
		}
        if ($count) {
            return DB::result_first("select count(*) from %t e where $sql", $params);
        }
        $limitsql = $limit ? DB::limit($start, $limit) : '';
        $events = array();
        $uids = array();
        foreach (DB::fetch_all("select e.* from %t e  where $sql order by e.dateline desc $limitsql", $params) as $v) {
            $v=self::format_event_data($v);
            $uids[] = $v['uid'];
            $events[] = $v;
        }
		
        $events = self::result_events_has_avatarstatusinfo($uids, $events);
        return $events;
    }
	 //根据文件夹id查询动态
    public function fetch_by_fid($fid, $counts = false, $start = 0, $limit = 0, $rid = '',$type = false,$gids=array(),$state=1)
    {
        global $_G;
		$sql='1';
		$sqlor=array();
		$fpath=C::t('resources_path')->fetch($fid);
		$params= array($this->table,'resources_path');
		if($gids){
			 $sql .= " and e.gid IN(%n) ";
			$params[]=$gids;
		}	
		if((defined('VAPP_ROOTFID') && VAPP_ROOTFID==$fid) && $_G['adminid']!=1 && !in_array($_G['uid'],$_G['vapp']['mids'])){
			$folder=C::t('folder')->fetch($fid);
			$cgids=array($folder['gid']);
			foreach(C::t('organization')->fetch_all_part_org($folder['gid']) as $value){
				$cgids[]=$value['orgid'];
			}
			$sql.=" and (e.uid=%d OR e.gid IN(%n))";
			$params[]=$_G['uid'];
			$params[]=$cgids;
		}else{
			$sqlor[] = " p.pathkey LIKE %s ";
			$params[] =   str_replace('_','\_',$fpath['pathkey']) . '%';
		
        
			if($rid){
				$sqlor[]=" e.rid = %s";
				$params[]=$rid;
			}
			if($sqlor){
				$sql.=" and (".implode(" OR ",$sqlor).")";
			}
		}
		
        if ($type) {
            $type = $type - 1;
            $sql .= " and e.type = " . $type ;
        } 
		if($state){
			$sql .= ' and e.state>0';
		}
        if ($counts) {
            return DB::result_first("select count(*) from %t e LEFT JOIN %t p ON p.fid=e.pfid where $sql", $params);
        }
        $limitsql = $limit ? DB::limit($start, $limit) : '';
        $events = array();
        $uids = array();
       
        foreach (DB::fetch_all("select * from %t e LEFT JOIN %t p ON p.fid=e.pfid where $sql order by e.dateline desc $limitsql", $params) as $v) {
            $v=self::format_event_data($v);
            $uids[] = $v['uid'];
            $events[] = $v;
        }
        $events = self::result_events_has_avatarstatusinfo($uids, $events);
        return $events;
    }
    //根据文件夹id查询动态
    public function fetch_by_pfid_rid($fid, $counts = false, $start = 0, $limit = 0, $rid = '', $type = false,$gids=array(),$state=1)
    {
        $sql='1';
		$params = array($this->_table,'resources_path');
		if($gids){
			 $sql .= ' and e.gid IN(%n) ';
			$params[]=$gids;
		}	
		if(is_array($fid)){
			$sqlor = array();
			foreach($fid as $v){
				$fpath=C::t('resources_path')->fetch($v);
				$sqlor[]=" p.pathkey LIKE %s ";
				$params[]= str_replace('_','\_',$fpath['pathkey']) . '%';
			}
		}else{
			$fpath=C::t('resources_path')->fetch($fid);
			$sqlor = array(" p.pathkey LIKE %s ");
			$params[]= str_replace('_','\_',$fpath['pathkey']) . '%';
		}
        
		if($rid){
			if(is_array($rid)){
				$sqlor[]=" e.rid IN (%n)";
			}else{
				$sqlor[]=" e.rid = %s";
			}
			$params[]=$rid;
		}
		if($sqlor){
			$sql.=" and (".implode(" OR ",$sqlor).")";
		}
		
        if ($type) {
            $type = $type - 1;
            $sql .= " and e.type = " . $type ;
        } 
		if($state){
			$sql .= ' and e.state>0';
		}
        if ($counts) {
            return DB::result_first("select count(*) from %t e LEFT JOIN %t p ON p.fid=e.pfid where $sql", $params);
        }
        $limitsql = $limit ? DB::limit($start, $limit) : '';
        $events = array();
        $uids = array();
       
        foreach (DB::fetch_all("select * from %t e LEFT JOIN %t p ON p.fid=e.pfid where $sql order by e.dateline desc $limitsql", $params) as $v) {
            $v=self::format_event_data($v);
            $uids[] = $v['uid'];
            $events[] = $v;
        }
        $events = self::result_events_has_avatarstatusinfo($uids, $events);
        return $events;
    }

    public function result_events_has_avatarstatusinfo($uids, $events)
    {
        $uids = array_unique($uids);
        $avatars = array();
        foreach (DB::fetch_all("select u.avatarstatus,u.uid,s.svalue from %t u left join %t s on u.uid=s.uid and s.skey=%s where u.uid in(%n)", array('user', 'user_setting', 'headerColor', $uids)) as $v) {
            if ($v['avatarstatus'] == 1) {
                $avatars[$v['uid']]['avatarstatus'] = 1;
            } else {
                $avatars[$v['uid']]['avatarstatus'] = 0;
                $avatars[$v['uid']]['headerColor'] = $v['svalue'];
            }
        }
        $fevents = array();
        foreach ($events as $v) {
            $v['avatarstatus'] = $avatars[$v['uid']]['avatarstatus'];
            if (!$avatars[$v['uid']]['avatarstatus'] && isset($avatars[$v['uid']]['headerColor'])) {
                $v['headerColor'] = $avatars[$v['uid']]['headerColor'];
            }
            $fevents[] = $v;
        }
        return $fevents;
    }

    //查询该文件最近的动态
    public function fetch_by_ridlast($rid)
    {
        $event = array();
        $result = DB::fetch_first("select * from %t where rid = %s and `type` = %d", array($this->_table, $rid, 0));
        $body_data = unserialize($result['body_data']);
        $body_data['msg'] = dzzcode($body_data['msg']);
        $event = array(
            'details' => lang($result['event_body'], $body_data),
            'fdate' => dgmdate($result['dateline'], 'u'),
        );
        return $event;
    }

    //查询当前用户所有动态
    public function fetch_all_event($start = 0, $limit = 0, $condition = array(), $ordersql = '', $count = false,$force = false)
    {
        $limitsql = $limit ? DB::limit($start, $limit) : '';
        $wheresql = ' 1 ';
        $uid = getglobal('uid');
        $params = array($this->_table, 'folder');
        $explorer_setting = get_resources_some_setting();//获取系统设置
        $powerarr = perm_binPerm::getPowerArr();
        //用户条件
        $usercondition = array();
//		print_r($condition);
        //如果筛选条件没有用户限制，默认查询当前用户网盘数据
        if (!isset($condition['uidval'])) {
            //用户自己的文件
            if (!$force && $explorer_setting['useronperm']) {//判断当前用户存储是否开启，如果开启则查询当前用户网盘数据
                $usercondition ['nogid'] = " e.gid=0 and e.uid=%d ";
                $params[] = $uid;
            }
        } else {
        	$uids = $condition['uidval'][0];
        	if($force && count($uids) > 0){
        		$usercondition ['nogid'] = " e.uid IN(%n) ";
                $params[] = $uids;
        	}else{
        		
	            if (in_array($uid, $uids)) {
	                if ($explorer_setting['useronperm']) {//判断当前用户存储是否开启，如果开启则查询当前用户网盘数据
	                    $usercondition ['nogid'] = " e.gid=0 and e.uid=%d ";
	                    $params[] = $uid;
	                }
	            }
	            if (count($uids) > 0) {//群组用户限制
	                $usercondition ['hasgid'] = " (e.uid in(%n)) ";
	            }
        	}
           
        }

        if (isset($usercondition['nogid'])) $wheresql .= ' and (' . $usercondition ['nogid'] . ') ';

		if(!$force){
	        //群组条件后需判断有无用户条件
	        $orgcondition = array();
	        $orgids = C::t('organization')->fetch_all_orgid();//获取所有有管理权限的部门，并排除已关闭的群组或机构
	        //我管理的群组或部门
	        if ($orgids['orgids_admin']) {
	
	            $orgcondition[] = "  e.gid IN (%n) ";
	
	            $params[] = $orgids['orgids_admin'];
	        }
	        //我参与的群组
//	        if ($orgids['orgids_member']) {
//	            $orgcondition[] = "  (e.gid IN(%n) and ((f.perm_inherit & %d) OR (e.uid=%d and f.perm_inherit & %d))) ";
//	            $params[] = $orgids['orgids_member'];
//	            $params[] = $powerarr['read2'];
//	            $params[] = $uid;
//	            $params[] = $powerarr['read1'];
//	        }
//			
//	        if ($orgcondition) {//如果有群组条件
//	            $or = isset($usercondition ['nogid']) ? 'or' : 'and';//判断是否有网盘数据
//	            if ($usercondition ['hasgid']) {//如果有网盘数据，则与群组条件组合为或的关系
//	                $wheresql .= " $or ((" . implode(' OR ', $orgcondition) . ") and " . $usercondition ['hasgid'] . ") ";
//	                $params[] = $uids;
//	            } else {
//	                $wheresql .= " $or (" . implode(' OR ', $orgcondition) . ") ";
//	            }
//	            $wheresql = '(' . $wheresql . ')';
//	        } else {
//	            if (!isset($usercondition ['nogid'])) {
//	                $wheresql .= ' and 0 ';
//	            }
//	        }
        }
        //解析搜索条件
        if ($condition && is_string($condition)) {//字符串条件语句
            $wheresql .= $condition;
        } elseif (is_array($condition)) {
            foreach ($condition as $k => $v) {
                if (!is_array($v)) {
                    $connect = 'and';
                    $wheresql .= $connect . ' e.' . $k . " = %s ";
                    $params[] = $v;
                } else {
                    $relative = isset($v[1]) ? $v[1] : '=';
                    $connect = isset($v[2]) ? $v[2] : 'and';
                    if ($relative == 'in') {
                        $wheresql .= $connect . "  e." . $k . " in (%n) ";
                        $params[]=$v[0];
                    } elseif ($relative == 'nowhere') {
                        continue;
                    } elseif ($relative == 'stringsql') {
                        $wheresql .= $connect . " " . $v[0] . " ";
                    } elseif ($relative == 'like') {
                        $wheresql .= $connect . " e." . $k . " like %s ";
                        $params[] = '%' . $v[0] . '%';
                    } else {
                        $wheresql .= $connect . ' e.' . $k . ' = %s ';
                        $params[]=$v[0] ;
                    }

                }
            }
        }
        if ($count) {
            return DB::result_first("select count(*) from %t e left join %t f on e.pfid = f.fid where  $wheresql  $ordersql", $params);
        }
        $uids = array();
        $events = array();

        foreach (DB::fetch_all("select e.* from %t e left join %t f on e.pfid = f.fid where  $wheresql  $ordersql  $limitsql", $params) as $v) {
          
			$v=self::format_event_data($v);
            $uids[] = $v['uid'];
            $events[] = $v;
        }
        $events = self::result_events_has_avatarstatusinfo($uids, $events);
        return $events;

    }
	public function format_event_data($v){
		$v['body_data'] = unserialize($v['body_data']);
		$v['body_data']['msg'] = self::emoji_decode($v['body_data']['msg']);
		$v['body_data']['msg'] = preg_replace_callback("/@\[(.+?):(.+?)\]/i", function ($matches) {
			global $at_users, $_G;
			include_once  libfile('function/organization');
			if (strpos($matches[2], 'g') !== false) {
				$gid = str_replace('g', '', $matches[2]);
				if (($org = C::t('organization') -> fetch($gid)) && checkAtPerm($gid)) {//判定用户有没有权限@此部门
					$uids = getUserByOrgid($gid, true, array(), true);
					foreach ($uids as $uid) {
						if ($uid != $_G['uid'])
							$at_users[] = $uid;
					}
					return '[org=' . $gid . '] @' . $org['orgname'] . '[/org]';
				} else {
					return '';
				}
			} else {
				$uid = str_replace('u', '', $matches[2]);
				if (($user = C::t('user') -> fetch($uid)) && $user['uid'] != $_G['uid']) {
					$at_users[] = $user['uid'];
					return '[uid=' . $user['uid'] . ']@' . $user['username'] . '[/uid]';
				} else {
					return $matches[0];
				}
			}
		},$v['body_data']['msg']);
		$v['body_data']['msg'] = dzzcode($v['body_data']['msg']);
		$v['body_data']['uid']=$v['uid'];
		$v['body_data']['username']='';
		$v['do_lang'] = lang($v['do']);
		//处理hash值
		$v['body_data']['hash']=preg_replace_callback("/^#group&gid=(\d+)$/",function($matches){
			$gid=$matches[1];
			$org=C::t('organization')->fetch($gid);
			return '#group&gid='.$gid.'&fid='.$org['fid'];
		},$v['body_data']['hash']);
		$v['details'] = lang($v['event_body'], $v['body_data']);
		$v['details'] = preg_replace("/\{.+?\}/i",'',$v['details']);
		$v['fdate'] = dgmdate($v['dateline'], 'u');
		return $v;
	}

    //删除评论
    public function delete_comment_by_id($id)
    {
        $id = intval($id);
        $uid = getglobal('uid');
        if (!$comment = parent::fetch($id)) {
            return array('error' => lang('comment_not_exists'));
        }
        //检测删除权限
        $pfid = $comment['pfid'];
        if ($folder = C::t('folder')->fetch($pfid)) {
            if(($uid != $comment['uid']) && !perm_check::checkperm_Container($folder['fid'], 'delete2') && !($uid == $folder['uid'] && perm_check::checkperm_Container($folder['fid'], 'delete1'))) {
                return array('error' => lang('no_privilege'));
            }
        }
        if (parent::delete($id)) {
            return array('success' => true);
        } else {
            return array('error' => lang('delete_error'));
        }

    }

    /*
     * #group&do=file&gid=1&fid=13
     * #group&gid=1
     * #home&fid=1
     * #home&do=file&fid=11
     * */
    public function get_showtpl_hash_by_gpfid($pfid, $gid = 0)
    {
		return '#home&fid=' . $pfid;
        $hash = '';
        //判断是否是群组内操作
        if ($gid > 0) {
            $gfid = DB::result_first("select fid from %t where orgid = %d", array('organization', $gid));
            //判断是否是群组跟目录
            if ($pfid == $gfid) {
                //$hash=MOD_URL.'#group&gid='.$gid;
                $hash = '#group&gid=' . $gid;
            } else {
                //$hash=MOD_URL.'#group&do=file&gid='.$gid.'&fid='.$pfid;
                $hash = '#group&do=file&gid=' . $gid . '&fid=' . $pfid;
            }
        } else {
            $hfid = DB::result_first("select pfid from %t where fid = %d", array('folder', $pfid));
            //判断是否是个人根目录
            if ($hfid == 0) {
                //$hash=getglobal('siteurl').MOD_URL.'#home&fid='.$pfid;
                $hash = '#home&fid=' . $pfid;
            } else {
                //$hash=getglobal('siteurl').MOD_URL.'#home&do=file&fid='.$pfid;
                $hash = '#home&do=file&fid=' . $pfid;
            }
        }
        return $hash;
    }

    public function update_event_by_pfid($pfid, $opfid)
    {
        DB::update($this->_table, array('pfid' => $opfid), array('pfid' => $opfid));
    }

}