<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
global $_G;
$uid = $_G['uid'];
$operation = isset($_GET['operation']) ? trim($_GET['operation']) : '';
if($operation == 'list'){
    $perpage = isset($_GET['perpage']) ? intval($_GET['perpage']) : 150;//每页数量
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;//页码数
    $start = ($page - 1) * $perpage; //开始条数
    $limit = isset($_GET['limit']) ? intval($_GET['limit']):0;
    if($limit){
        //计算开始位置
        $start = $start+$perpage - $limit;
        $perpage = $limit;
    }
    $limitsql = "limit $start,$perpage";
    $total = 0; //总条数
    $keyword = isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '';
    $params=['my_file'];
    $para = [];
    $wheresql = ' 1 ';
    if($keyword){
        $wheresql .= ' and filename like %s ';
        $param[] = '%'.$keyword.'%';
    }
    $type = isset($_GET['type']) ? getstr($_GET['type']):'';
    if($type){
        $wheresql .= ' and source =%s ';
        $param[] = $type;
    }
    $date = isset($_GET['date']) ? trim($_GET['date']):'';
    if($date){
        $dateline = explode('_', $date);
        if ($dateline[0]) {
            $wheresql .= " and dateline >= %d ";
            $param[] = strtotime($dateline[0]);
        }
        if ($dateline[1]) {
            $wheresql .= " and dateline < %d";
            $param[] = strtotime($dateline[1]) + 24 * 60 * 60;
        }
    }
    if($param) $params = array_merge($params,$param);
    $count = DB::result_first("select count(id) from %t where $wheresql",$params);

    $data = [];
    foreach(DB::fetch_all("select * from %t where $wheresql order by dateline desc $limitsql",$params) as $v){
        $v['icondata'] = $_G['siteurl'].'index.php?mod=io&op=getThumb&path='.dzzencode('attach::'.$v['aid']);
        $v['fdate'] = dgmdate($v['dateline'],'Y-m-d H:i:s');
		$v['dpath']=dzzencode('attach::'.$v['aid'], '', 0, 0);
		$v['ext']=$v['filetype'];
		$v['open']=getOpenUrl($v);
        $data[] = $v;
    }
    $return = array(
        'total' => $total,
        'next'=>$next,
        'data' => $data ? $data : array(),
        'perpage' => $perpage,
    );
    exit(json_encode($return));
}else{
    include template('/page/index');
}

