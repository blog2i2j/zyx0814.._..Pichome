<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G,$_GET,$id,$data;
    $data = C::t('publish_list')->fetch_by_id($id,1);
    $_G['setting']['metakeywords']=!empty($data['metakeywords'])?$data['metakeywords']:$_G['setting']['metakeywords'];
    $_G['setting']['metadescription']=!empty($data['metadescription'])?$data['metadescription']:$_G['setting']['metadescription'];
    if($_G['setting']['pathinfo']){
        $url = $_G['siteurl'].$data['address'];
    }else{
        $url = $_G['siteurl'].'index.php?mod=publish&id='.$id;
    }
    $data['url']=$url;
    $data['fdateline']=dgmdate($data['dateline'],'Y-m-d H:i:s');

    $perm = C::t('publish_list')->getPermById($id);

    $sql = " appid=%s";

    $params = ['pichome_resources',$data['pval']];

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
            $dataresources = C::t('pichome_resources')->getdatasbyrids($rids, 1, $perm);
        }
        $multi= multi($count, $perpage, $page, $data['url'],'text-center');
    }

    //print_r($dataresources);

    include template('robot/library/index');
    exit();