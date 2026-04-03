<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G,$_GET,$id,$data;


    $sql = "1";
    $sql = " from %t  r ";
    $wheresql="1";
    $params = ['pichome_resources'];
    $para=[];
    $page = (isset($_GET['page']) && intval($_GET['page'])) ? intval($_GET['page']) : 1;
    $perpage = isset($_GET['perpage']) ? intval($_GET['perpage']) : 10;
    $start = ($page - 1) * $perpage;
    $limitsql = "limit $start," . $perpage;

    $stdata = C::t('#intelligent#intelligent')->fetch_by_tid($data['pval']);


    //范围条件
    $vappids=array();
    if($stdata['searchRange']){
        $vappids = array_keys($stdata['searchRange']['appids']);
        foreach($stdata['searchRange']['folders'] as $pathkey=>$v){
            $vappids[] = $v['appid'];
        }
        $appid = $vappids;
    }
    else{
        foreach (DB::fetch_all("select appid,path,view,type from %t where isdelete = 0", array('pichome_vapp')) as $v) {
            if ($v['type'] != 3 && !IO::checkfileexists($v['path'],1)) {
                continue;
            }
            if (C::t('pichome_vapp')->getpermbypermdata($v['view'],$v['appid'])) {
                $vappids[] = $v['appid'];
            }
        }
        $appid = $vappids;
    }
    if ($appid) {
        $wheresql .= ' and r.appid in(%n)';
        $para[] = $appid;
    }else{
        $wheresql .= ' and 0 ';
    }

    //处理范围分类

    if($stdata['searchRange']['folders']){
        $pathkeys = array_keys($stdata['searchRange']['folders']);
    }else{
        $pathkeys=array();
    }
    if($pathkeys){
        $sql .= " LEFT JOIN %t frx on frx.rid = r.rid ";
        $params[] = 'pichome_folderresources';
        $warr=array();
        foreach($pathkeys as $pathkey){
            $warr[]= " frx.pathkey like %s ";
            $para[]=str_replace('_','\_',$pathkey).'%';
        }
        if($warr){
            $wheresql .= ' and ('.implode(' or ',$warr).')';
        }
    }
    // print_r($stdata);
    //处理名称包含
    if($stdata['extra'] && $stdata['extra']['searchName']){
        $arr=explode(' ',$stdata['extra']['searchName']);
        $osqlarr=array();

        foreach($arr as $v){
            $nsqlarr=array();
            if(strpos($v,'+')!==false){
                $andarr=explode('+',$v);
                foreach($andarr as $v1){
                    if(empty($v1)) continue;
                    $nsqlarr[]= " r.name like %s ";
                    $para[] = '%'.str_replace('_','\_',$v1).'%';
                }
                if($andarr){
                    $osqlarr[]= '('.implode(' and ',$nsqlarr).')';
                }
            }else{
                $osqlarr[]= " r.name like %s ";
                $para[] = '%'.str_replace('_','\_',$v).'%';
            }
        }
        if($osqlarr){
            $wheresql .= ' and ('.implode(' or ',$osqlarr).')';
        }
    }
    //处理评分
    if($stdata['extra'] && $stdata['extra']['grade'] && !in_array('0',$stdata['extra']['grade'])){
        $wheresql .= ' and r.grade in(%s) ';
        $para[]=$stdata['extra']['grade'];
    }

    //处理标签范围

    if($stdata['tags']){
        $tagtids=array();
        $tagnames = explode(',',$stdata['tags']);

        foreach(DB::fetch_all("select tid from %t where tagname IN(%n)",array('pichome_tag',$tagnames)) as $v2){
            $tagtids[$v2['tid']] = $v2['tid'];
        }
        foreach(DB::fetch_all("select langflag from %t where state>0",array('language')) as $value){
            $table='lang_'.strtolower(str_replace('-','_',$value['langflag']));
            foreach(DB::fetch_all("select * from %t where idtype='8' and filed='tagname' and svalue IN(%n)",array($table, $tagnames)) as $v2){
                $tagtids[$v2['idvalue']] = $v2['idvalue'];
            }
        }
        if($tagtids){
            $sql .= "left join %t rts on r.rid = rts.rid ";
            $params[] = 'pichome_resourcestag';
            $wheresql .= ' and rts.tid in(%n) ';
            $para[] = $tagtids;
        }
    }

    //处理后缀条件
    if($stdata['exts']){
        $exts=explode(',',$stdata['exts']);
        if($exts) {
            $wheresql .= ' and r.ext in(%n) ';
            $para[] = explode(',', $stdata['exts']);
        }
    }
    $rids=[];
    $dataresources=[];
    if (!empty($para)) $params = array_merge($params, $para);
    //print_r($params);exit("select count(*) $sql where $wheresql ");
    if($count=DB::result_first("select count(*) $sql where $wheresql ",$params)) {
        foreach (DB::fetch_all(" select r.rid $sql where $wheresql  order by r.dateline DESC $limitsql", $params) as $value) {
            $rids[] = $value['rid'];
        }
        if (!empty($rids)) {
            $dataresources = C::t('pichome_resources')->getdatasbyrids($rids, 1);
        }
        $multi= multi($count, $perpage, $page, $data['url'],'text-center');
    }

    //print_r($dataresources);

    include template('robot/intelligent/index');
    exit();