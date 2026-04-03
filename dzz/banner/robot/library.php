<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G,$_GET,$appid;

    $data=C::t('pichome_vapp')->fetchByAppid($appid);
    $url = 'index.php?mod=pichome&op=fileview&id=' . $appid . '#appid=' . $appid;
    $url = 'index.php?mod=pichome&op=fileview&id='.$appid.'#appid=' . $appid;
    if ($_G['setting']['pathinfo']) $path = C::t('pichome_route')->fetch_path_by_url($url);
    else $path = '';
    if ($path) {
        $data['url'] = $path;
    } else {
        $data['url'] = $url;
    }

    $data['fdateline']=dgmdate($data['dateline'],'Y-m-d H:i:s');



    $sql = " appid=%s";

    $params = ['pichome_resources',$appid];

    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $perpage = isset($_GET['perpage']) ? intval($_GET['perpage']) : 10;
    $start = ($page - 1) * $perpage;
    $limitsql = "limit $start," . $perpage;
    $rids=[];
    $dataresources=[];
    if($count=DB::result_first("select count(*) from %t where $sql ",$params)) {
        foreach (DB::fetch_all(" select rid from %t where $sql  order by dateline DESC $limitsql", $params) as $value) {
            $rids[] = $value['rid'];
        }
        if (!empty($rids)) {
            $dataresources = C::t('pichome_resources')->getdatasbyrids($rids, 1);
        }
        $multi= multi($count, $perpage, $page, $data['url'],'text-center');
    }

    //print_r($dataresources);

    include template('robot/library/index');
    exit();