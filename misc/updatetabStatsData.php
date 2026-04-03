<?php
ignore_user_abort(true);
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G;
@set_time_limit(0);
ini_set('memory_limit', -1);
@ini_set('max_execution_time', 0);
$gids = [];
foreach (DB::fetch_all("select gid from %t where isdelete = 0", array('tab')) as $v) {
    $gids[] = $v['gid'];
}
if (empty($gids)) {
    exit('success');
}
$gid = isset($_GET['gid']) ? intval($_GET['gid']):0;
$locked = true;

$processname = 'DZZ_LOCK_PICHOMEUPDATETAB_'.$gid;
if (!dzz_process::islocked($processname, 60*15)) {
    $locked=false;
}
dzz_process::unlock($processname);
if ($locked) {
    exit(json_encode(array('error' => '进程已被锁定请稍后再试')));
}
$limit = 100;
$time = TIMESTAMP+5*3600;
//获取当前专辑的字段数
$tabgroupdata = C::t('#tab#tab_group')->fetch($gid);
$formfiled = unserialize($tabgroupdata['formfiled']);
$tabgroupfileds = [];
foreach($formfiled as $k=>$v){
    if(strpos($k,'tabgroup_')===0 && $v['status']){
        $tabgroupfileds[] = $k;
    }
}

$totalnum = count($tabgroupfileds);
$datas = DB::fetch_all("select t.tid from %t t left join %t ts on t.tid=ts.tid where t.gid =%d and (ts.dateline < %d or ISNULL(ts.dateline)) limit $limit",
    ['tab','tab_stats',$gid,$time]);
if($datas){
    foreach($datas as $v){
        $tid = $v['tid'];
        $processname1 = 'PICHOMEUPDATETAB_' . $tid;
        dzz_process::unlock($processname1);
        //如果当前数据是锁定状态则跳过
        if (dzz_process::islocked($processname1, 60 * 5)) {
            continue;
        }
        $tabdata = C::t('#tab#tab')->fetch($v['tid']);

        $hasdatanum =  DB::result_first("select count(*) from %t where tid = %d and skey in(%n)",['tab_attr',$tid,$tabgroupfileds]);
        //信息完整度
        $infopercent = round(100*$hasdatanum/$totalnum,2);
        $modelnumarr = [];
        //文件和专辑模块数据个数
        foreach(DB::fetch_all("select * from %t where (cate = %d or cate = %d) and gid = %d",array('tab_banner',0,2,$gid)) as $val){
            if($v['cate'] == 0){
                $ulevel = $_G['pichomelevel'];
                $sql = ' from %t r left join %t rtab on rtab.rid = r.rid ';
                $selectsql = "select count(DISTINCT r.rid) ";
                $params = ['pichome_resources', 'pichome_resourcestab'];
                $para = [$tid, $ulevel];
                $wheresql = ' r.isdelete = 0 and rtab.tid = %d and r.level <= %d ';

                $content = unserialize($val['content']);
                if ($content['exts']) {
                    $wheresql .= ' and r.ext in (%n) ';
                    $para[] = explode(',', $content['exts']);
                }
                $lang = '';
                //检查是否开启语言包
                Hook::listen('lang_parse', $lang, ['checklang']);
                if ($content['tags']) {
                    $tagnames = explode(',', $content['tags']);
                    //获取标签id
                    $tagval = [];
                    foreach (DB::fetch_all("select tid from %t where tagname in(%n)", ['pichome_tag', $tagnames]) as $tag) {
                        $tagval[] = $tag['tid'];
                    }
                    if($lang){
                        foreach(DB::fetch_all("select idvalue from %t where idtype=8 and svalue in(%n)",['lang_'.$lang,$tagnames]) as $tag){
                            $tagval[] = $tag['idvalue'];
                        }
                    }
                    $tagval = array_unique($tagval);

                    $tagwheresql = [];
                    foreach ($tagval as $k => $v) {
                        $sql .= ' left join %t rt' . ($k + 1) . ' on rt' . ($k + 1) . '.rid = r.rid  ';
                        $params[] = 'pichome_resourcestag';
                        $tagwheresql[] = '  (rt' . ($k + 1) . '.tid = %d and !isnull(rt' . ($k + 1) . '.tid)) ';
                        $para[] = $v;
                    }
                    if (empty($tagwheresql)) $wheresql .= ' and 0 ';
                    if (count($tagwheresql) > 1) $wheresql .= " and (" . implode(' or ', $tagwheresql) . ')';
                    elseif (count($tagwheresql)) $wheresql .= " and $tagwheresql[0] ";
                }
                $whererangesql = [];
                foreach ($content['range'] as $v) {
                    if ($v['appid']) {
                        $tmpwhererangesql = ' r.appid = %s ';
                        $para[] = $v['appid'];

                        if ($v['fids']) {
                            if (!in_array('pichome_folderresources', $params)) {
                                $sql .= ' LEFT JOIN %t fr on r.rid=fr.rid ';
                                $params[] = 'pichome_folderresources';
                            }
                            $childsqlarr = [];
                            foreach ($v['fids'] as $v1) {
                                $childsqlarr[] = " fr.pathkey like %s ";
                                $tpathkey = DB::result_first("select pathkey from %t where fid = %s", array('pichome_folder', $v1));
                                $para[] = $tpathkey . '%';
                            }
                            if ($childsqlarr) $tmpwhererangesql .= ' and (' . implode(' or ', $childsqlarr) . ')';
                        }
                        $whererangesql[] = $tmpwhererangesql;
                    }
                }
                $params = array_merge($params, $para);
                if ($whererangesql) {
                    $wheresql .= ' and (' . implode(' or ', $whererangesql) . ')';
                }
                $num = DB::result_first( "$selectsql $sql where $wheresql",$params);
                $modelnumarr[] = ['tid'=>$tid,'bid'=>$val['id'],'num'=>$num,'gid'=>$gid];
            }elseif($v['cate'] == 2){
                $content = unserialize($val['content']);

                $rgids = [];
                foreach ($content['range'] as $v) {
                    $rgids[] = $v['gid'];
                }

                $relationtype = $content['relationtype'];
                $rtids = [];
                $selectsql = 'count(DISTINCT(t.tid))';
                $sql = " from %t t  ";
                $params = ['tab'];
                if ($relationtype) {
                    //查询关联档前tid的卡片
                    $crtids = [];
                    foreach (DB::fetch_all("select tid,rtid from %t where (tid = %d and rgid in(%n)) or (rtid=%d and gid in(%n))", ['tab_relation', $tid, $rgids, $tid, $rgids]) as $v) {

                        if ($v['tid'] == $tid) {
                            $crtids[] = $v['rtid'];
                        } else {
                            $crtids[] = $v['tid'];
                        }
                    }

                    $wheresql = " where (t.tid in(select rt.tid from %t rt where rt.rtid in(%n) and rt.rgid in(%n) and rt.gid = %d) or 
                t.tid in(select rt.rtid from %t rt where rt.tid in(%n) and rt.gid in(%n) and rt.rgid = %d))  ";
                    $para[] = 'tab_relation';
                    $para[] = $crtids;
                    $para[] = $rgids;
                    $para[] = $gid;
                    $para[] = 'tab_relation';
                    $para[] = $crtids;
                    $para[] = $rgids;
                    $para[] = $gid;
                } else {
                    $wheresql = " where (t.tid in(select rt.rtid from %t rt where rt.rgid in(%n) and rt.tid = %d) or 
                t.tid in(select rt.tid from %t rt where rt.gid in(%n) and rt.rtid = %d))  ";
                    $para[] = 'tab_relation';
                    $para[] = $rgids;
                    $para[] = $tid;
                    $para[] = 'tab_relation';
                    $para[] = $rgids;
                    $para[] = $tid;
                }


                $params = array_merge($params, $para);
                $num = DB::result_first("select $selectsql $sql  $wheresql  ", $params);
                $modelnumarr[] = ['tid'=>$tid,'bid'=>$val['id'],'num'=>$num,'gid'=>$gid];
            }
        }
        foreach($modelnumarr as $v){
            C::t('#tab#tab_statsmodel')->insertData($v);
        }
        $statsdata = ['tid'=>$tid,'infopercent'=>$infopercent,'gid'=>$gid,'dateline'=>TIMESTAMP];
        C::t('#tab#tab_stats')->insertData($statsdata);
        dzz_process::unlock($processname1);
    }
}else{
    dzz_process::unlock($processname);
}
if(DB::result_first("select count(t.tid) from %t t left join %t ts on t.tid=ts.tid where t.gid =%d and ts.dateline < %d",
    ['tab','tab_stats',$gid,$time])){
    sleep(2);
    dfsockopen(getglobal('localurl').'misc.php?mod=updatetabStatsData&gid='.$gid,0,'','',false,'',1);
}else{
    exit('success');
}

