<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
// _input_文本 _fulltext_富文本 _number_数字 _array_数组 _file_文件
global $_G;
$operation = isset($_GET['operation']) ? trim($_GET['operation']) : '';
$uid = $_G['uid'];
if($operation == 'basic'){//新建和编辑
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if(submitcheck('submit')) {
        //验证数据完整性
        if($id && empty($_GET['pname'])){
            exit(json_encode(['code'=>0,'msg'=>'publish_pname_empty']));
        }
        if(!in_array($_GET['ptype'],array(1,2,3,4,5,6))){
            exit(json_encode(['code'=>0,'msg'=>'publish_ptype_error']));
        }

        //处理自定义数据pageset，以及特殊类型字段
        $pageset = isset($_GET['pageset']) ? $_GET['pageset'] :'';
        $aids=array();
        if(is_array($pageset)) {
            foreach ($pageset as $k => $v) {
                $karr = explode('_', $k);
                if ($karr[0].$karr[1] == 'file') {//处理文件类数据
                    if (is_array($v)) {
                        if($v['aid']>0) {
                            $aids[]=intval($v['aid']);
                        }
                    }elseif(!empty($v['src'])){
                        $src=$v['src'];
                        $src=preg_replace_callback("/&path=(\w+)/i", function ($matches) {
                            $dpath=$matches[1];
                            $patharr=Pdecode($dpath);
                            return '&path='.Pencode($patharr,0);
                        },$src);
                        $pageset[$k]['src']=$src;
                    } elseif(intval($v)>0) {
                        $aids[] = intval($v);
                    }
                } elseif ($karr[0].$karr[1] == 'html') {//处理富文本类数据
                    $ret = handleHtmlData($v);
                    if ($ret['aids']) {
                        $aids = array_merge($aids, $ret['aids']);
                        $pageset[$k] = $ret['data'];
                    }
                } elseif ($karr[0] . $karr[1] == 'textarea') {
                    $v = \helper_security::checkhtml(nl2br($v));
                }
            }
        }
        $setarr = array(
            'pname' => $_GET['pname'] ? getstr($_GET['pname'], 255):'',
            'ptype'=> $_GET['ptype'] ? intval($_GET['ptype']):0,
            'pval' => $_GET['pval'] ? trim($_GET['pval']):'',
           // 'pdesc' => $_GET['pdesc'] ? getstr($_GET['pdesc'], 255):'',
            'metakeywords' => $_GET['metakeywords'] ? getstr($_GET['metakeywords'], 255):'',
            'metadescription' => $_GET['metadescription'] ? getstr($_GET['metadescription'], 255):'',
            'view'=> $_GET['view'] ? serialize($_GET['view']):0,
            'download'=> $_GET['download'] ? serialize($_GET['download']):0,
            'share'=> $_GET['share'] ? serialize($_GET['share']):0,
            'filter'=> $_GET['filter'] ? serialize($_GET['filter']):'',
            'pageset'=> is_array($pageset) ? serialize($pageset):'',
            'extra'=> $_GET['extra'] ? serialize($_GET['extra']):'',
            'uid'=>$_G['uid'],
            'username'=>$_G['username'],
            'address'=> $_GET['address'] ? getstr($_GET['address'], 30):'',
            'updatedate'=>TIMESTAMP,
            'aids'=> $aids ? implode(',',array_unique($aids)) : '',
            'rpids'=> $_GET['rpids'] ? implode(',',$_GET['rpids']):'',
        );
        if(isset($_GET['pstatus'])){
            $setarr['pstatus']= intval($_GET['pstatus']);
        }
        if(isset($_GET['tid'])){
            $setarr['tid']=intval($_GET['tid']);
        }
        if($_GET['ptype']==6){//防止合集嵌套
            $setarr['rpids']='';
            if(isset($_GET['flag'])){
                $setarr['flag']=intval($_GET['flag']);
            }
        }

        if($id){
            unset($setarr['pval']);
            unset($setarr['ptype']);
            $ret=C::t('publish_list')->update_by_id($id,$setarr);
        }
        else{
            $setarr['dateline']=TIMESTAMP;
            if(intval($setarr['ptype']) == 3){
                $setarr['filter'] = [
                    [
                        'key' => 'classify',
                        'label' => lang('classify'),
                        'checked' => 1,
                    ],
                    [
                        'key' => 'tag',
                        'label' => lang('label'),
                        'checked' => 1,
                    ],
                    [
                        'key' => 'color',
                        'label' => lang('fs_color'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'link',
                        'label' => lang('fs_link'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'desc',
                        'label' => lang('note'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'duration',
                        'label' => lang('duration'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'size',
                        'label' => lang('size'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'ext',
                        'label' => lang('type'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'shape',
                        'label' => lang('shape'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'grade',
                        'label' => lang('grade'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'btime',
                        'label' => lang('add_time'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'dateline',
                        'label' => lang('modify_time'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'mtime',
                        'label' => lang('creation_time'),
                        'checked' => 1
                    ]
    
                ];
            }elseif(intval($setarr['ptype'] == 5)){
                $setarr['filter'] = [

                    [
                        'key' => 'tag',
                        'label' => lang('label'),
                        'checked' => 1,
                    ],
                    [
                        'key' => 'color',
                        'label' => lang('fs_color'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'link',
                        'label' => lang('fs_link'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'desc',
                        'label' => lang('note'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'duration',
                        'label' => lang('duration'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'size',
                        'label' => lang('size'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'ext',
                        'label' => lang('type'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'shape',
                        'label' => lang('shape'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'grade',
                        'label' => lang('grade'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'btime',
                        'label' => lang('add_time'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'dateline',
                        'label' => lang('modify_time'),
                        'checked' => 1
                    ],
                    [
                        'key' => 'mtime',
                        'label' => lang('creation_time'),
                        'checked' => 1
                    ]

                ];
            }
            if(is_array($setarr['filter'])) $setarr['filter'] = serialize($setarr['filter']);
            if(!$id = C::t('publish_list')->insert_by_id($setarr,1)){
                exit(json_encode(['code'=>0,'msg'=>'publish_add_error']));
            }

        }
        $url = 'index.php?mod=publish&id=' . $id;
        $address = C::t('pichome_route')->update_path_by_url($url, $setarr['address'], true);
        exit(json_encode(['code'=>1,'msg'=>'success','id'=>$id,'address'=>$address]));
    }else{
        if(!$data = C::t('publish_list')->fetch_by_id($id)){
           exit(json_encode(['code'=>0,'msg'=>'publish_not_exists']));
        }
        exit(json_encode(['code'=>1,'data'=>$data]));
    }

}
elseif($operation == 'del'){//删除
    $ids = isset($_GET['ids']) ? dintval($_GET['ids'],true) : [];
    $dels=[];
    foreach($ids as $id) {
       if(C::t('publish_list')->delete_by_id($id)){
           $dels[]=$id;
       }
    }
     exit(json_encode(['code'=>1,'data'=>array_values($dels)]));

}elseif($operation == 'changstatus'){//改变发布状态
    $ids = isset($_GET['ids']) ? dintval($_GET['ids'],true) : [];
    $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
     C::t('publish_list')->update($ids, ['pstatus' => $status]);
    exit(json_encode(['code' => 1, 'msg' => lang('change_status_success'), 'status' => $status]));
}
elseif($operation == 'list'){//获取发布列表
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $perpage = isset($_GET['perpage']) ? intval($_GET['perpage']) : 30;
    $ptype = isset($_GET['ptype']) ? intval($_GET['ptype']) :0;
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    $start = ($page - 1) * $perpage;
    $limitsql = "limit $start," . $perpage;
    $order = isset($_GET['ordertype']) ? trim($_GET['ordertype']) : 0;
    $asc = isset($_GET['asc']) ? trim($_GET['asc']) : 0;
    switch ($order){
        case 0:
            $orderby = "order by l.dateline " . ($asc ? 'asc' : 'desc');
            break;
        case 1:
            $orderby = "order by l.cview " . ($asc ? 'asc' : 'desc');
            break;
        case 2:
            $orderby = "order by l.cdownload " . ($asc ? 'asc' : 'desc');
            break;
        default:
            $orderby = "order by l.dateline desc";
            break;
    }

    $params = ['publish_list','publish_template'];
    $wheresql = " where 1 ";
    //发布类型
    if($ptype==7) {
        $wheresql .= " and l.pstatus='2'";
    }elseif($ptype){
        $params[] = $ptype;
        $wheresql .= " and l.ptype = %d and l.pstatus<2";
    }else{
        $wheresql .= " and l.pstatus<2";
    }
    if($keyword){
        $params[] = '%'.$keyword.'%';
        $wheresql .= " and l.pname like %s";
    }

    $data =[];
    if($total=DB::result_first("select count(*) from %t l LEFT JOIN %t t ON l.tid=t.id ".$wheresql,$params)) {
        foreach (DB::fetch_all("select l.*,t.tname from %t l LEFT JOIN %t t ON l.tid=t.id " . $wheresql . " " . $orderby . " " . $limitsql, $params) as $value) {
            $value['fdateline'] = dgmdate($value['dateline'], 'Y-m-d H:i:s');
            $value['updatedate'] = dgmdate($value['updatedate'], 'Y-m-d H:i:s');
            //处理地址
            $url='index.php?mod=publish&id=' . $value['id'];
            $value['address']=C::t('pichome_route')->update_path_by_url($url,$value['address']);
            if(strpos($value['url'],'http') === false){
                $value['url'] = $_G['siteurl'] . $value['address'];
            }else{
                $value['url'] =  $_G['siteurl'] .$url;
            }
            $value['pstatus']=intval($value['pstatus']==2?0:$value['pstatus']);

            if($value['rpids']){
                $rpids = explode(',',$value['rpids']);
                $value['rpdatas']= C::t('publish_list')->fetch_all($rpids);
            }

            $data[] = $value;
        }
    }
    $next = false;
    if ($page*$perpage < $total) {
        $next = true;
    }
    $return = array(
        'total' => $total,
        'next'=>$next,
        'data' => $data ? $data : array(),
        'param' => array(
            'order' => $order,
            'page' => $page,
            'perpage' => $perpage,
            'total' => $total,
            'asc' => $asc,
            'keyword' => $keyword
        )
    );

    updatesession();
    exit(json_encode(array('data' => $return)));
}
elseif($operation == 'upload'){
    include libfile( 'class/uploadhandler' );

    $options = array( 'accept_file_types' => '/\.(gif|jpe?g|png|webp|svg|webp|mp4)$/i',

        'upload_dir' => $_G[ 'setting' ][ 'attachdir' ] . 'cache/',

        'upload_url' => $_G[ 'setting' ][ 'attachurl' ] . 'cache/',

        'thumbnail' => array( 'max-width' => 40, 'max-height' => 40 ) );

    $upload_handler = new uploadhandler( $options );
    updatesession();
    exit();
}
elseif($operation=='uploadimg'){//上传图片
    include libfile( 'class/uploadhandler' );

    $options = array( 'accept_file_types' => '/\.(gif|jpe?g|webp|png|svg)$/i',

        'upload_dir' => $_G[ 'setting' ][ 'attachdir' ] . 'cache/',

        'upload_url' => $_G[ 'setting' ][ 'attachurl' ] . 'cache/',

        'thumbnail' => array( 'max-width' => 40, 'max-height' => 40 ) );

    $upload_handler = new uploadhandler( $options );
    updatesession();
    exit();

}elseif($operation=='uploadmedia') {//上传视频
    include libfile('class/uploadhandler');

    $options = array('accept_file_types' => '/\.(mp4|flv|mp3|webm|ogg|aac))$/i',

        'upload_dir' => $_G['setting']['attachdir'] . 'cache/',

        'upload_url' => $_G['setting']['attachurl'] . 'cache/',

        'thumbnail' => array('max-width' => 40, 'max-height' => 40));

    $upload_handler = new uploadhandler($options);
    updatesession();
    exit();
}
elseif($operation=='getCollectOptions') {
    $pids = isset($_GET['pids']) ? dintval($_GET['pids'], true) : array();
    if($pids) $pids=array_unique($pids);
    $data = [];
    $sql = "ptype!=6 ";
    $params = array('publish_list');
    $rpids=array();
    if($pids){
        $sql .= " and id in(%n)";
        $params[] = $pids;
    }
    foreach (DB::fetch_all("select * from %t where $sql ", $params) as $v) {
       if($v['rpids']){
           $arr=explode(',',$v['rpids']);
           if($rpids){
               $rpids=array_intersect($rpids,$arr);
           }else{
               $rpids=$arr;
           }
       }else{
           $rpids=array();
           break;
       }
    }
    exit(json_encode(['success' => true, 'data' => array_values($rpids)]));
}
elseif($operation == 'batchCollect'){
    $pids = isset($_GET['pids']) ? dintval($_GET['pids'], true) : array();
    $rpids = isset($_GET['rpids']) ? dintval($_GET['rpids'], true) : array();
    if($pids) $pids=array_unique($pids);
    if($rpids) $rpids=array_unique($rpids);
    if(count($pids)==1){
        if(C::t('publish_list')->update($pids[0],array('rpids'=>implode(',',$rpids)))){
            C::t('publish_relation')->update_by_pid($pids[0], $rpids);
        }
    }else{
        //先找到公共的合集
        $gg=array();
        foreach (C::t('publish_list')->fetch_all($pids) as $v) {
            if($v['rpids']){
                $arr=explode(',',$v['rpids']);
                if($gg){
                    $gg=array_intersect($gg,$arr);
                }else{
                    $gg=$arr;
                }
            }else{
                $gg=array();
                break;
            }
        }
        foreach(C::t('publish_list')->fetch_all($pids) as $value){
            $arr=array();
            if($value['rpids']){
                $arr=explode(',',$value['rpids']);
                if($gg){
                  $arr=array_diff($arr,$gg);
                }
                $ipids=array_unique(array_merge($arr,$rpids));
            }else{
                $ipids=$rpids;
            }
            if(C::t('publish_list')->update($value['id'],array('rpids'=>implode(',',$ipids)))){
                C::t('publish_relation')->update_by_pid($value['id'], $ipids);
            }
        }
    }
    exit(json_encode(['success' => true]));
}elseif($operation == 'getCollectList'){
    $limit=20;
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    $data = [];
    $sql = " ptype=6 and flag<1 ";
    $params = array('publish_list');
    if ($q) {
        $sql .= " and pname like %s";
        $params[] = "%" . $q . "%";
    }
    foreach (DB::fetch_all("select * from %t where $sql limit $limit", $params) as $v) {
        $data[$v['id']] = array('id' => $v['id'], 'ptype' => $v['ptype'], 'name' => $v['pname']);
    }

    exit(json_encode(['success' => true, 'data' => array_values($data)]));
} else {

    include template('main/page/main');
    exit();
}
