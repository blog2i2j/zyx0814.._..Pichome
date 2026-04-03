<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
$navtitle=lang('template_manage');
$do=($_GET['do'])??'';

if($do=='edit'){
    $id=intval($_GET['id']);

   $setarr=array(
       'tname'=>getstr($_GET['tname']),
       'tdesc'=>getstr($_GET['tdesc'],255),
       'tflag'=>trim($_GET['tflag']),
       'ttype'=>intval($_GET['ttype']),
       'tdir'=>trim($_GET['tdir']),
       'exts'=>trim($_GET['exts'])
   );
   //判断值
    $error=[];
    if(empty($setarr['tname'])){
        exit(json_encode(['success'=>false,'msg'=>lang('template_name').lang('forcesecques_cannot_empty')]));
    }
    if(empty($setarr['tdir'])){
        exit(json_encode(['success'=>false,'msg'=>lang('template_tdir').lang('forcesecques_cannot_empty')]));
    }
    if(empty($setarr['tflag'])){
        exit(json_encode(['success'=>false,'msg'=>lang('template_tflag').lang('forcesecques_cannot_empty')]));
    }
    if($id || ($id=DB::result_first("select id from %t where tdir=%s and tflag=%s",array('publish_template',$setarr['tdir'],$setarr['tflag'])))){
        C::t('publish_template')->update($id,$setarr);
    }else{

       $setarr['dateline']=TIMESTAMP;
       $id= C::t('publish_template')->insert($setarr,1);
    }
    if($data=C::t('publish_template')->fetch($id)){
        $data['fdateline']=$data['dateline']?dgmdate($data['dateline'],'Y-m-d H:i:s'):'-';
        exit(json_encode(['success'=>true,'data'=>$data]));
    }
    exit(json_encode(['success'=>false,'msg'=>lang('do_failed')]));

}
elseif($do=='delete') {
    $id=intval($_GET['id']);
    $cuse=DB::result_first("select COUNT(*) from %t where tid=%d",array('publish_list',$id));
    if($cuse>0){
        exit(json_encode(['success'=>false,'msg'=>lang('template_delete_cuse_error')]));
    }
    if(C::t('publish_template')->delete($id)){
        exit(json_encode(['success'=>true]));
    }else{
        exit(json_encode(['success'=>false,'msg'=>lang('do_failed')]));
    }
}
elseif($do=='export'){
    require_once  DZZ_ROOT . './admin/function/function_admin.php';
    $tid = intval($_GET['id']);
    $app = C::t('publish_template') -> fetch($tid);
    if (!$app) {
        showmessage('application_nonentity');
    }


    unset($app['cuse']);
    unset($app['id']);
    unset($app['dateline']);

    $apparray = array();

    $apparray['template'] = $app;
    $apparray['version'] = strip_tags($_G['setting']['version']);

    exportdata('Pichome! template', $app['tdir'] ? $app['tdir'].'_'.$app['tflag'] : random(5), $apparray);
    exit();
}
elseif($do=='import'){
    $aid=intval($_GET['aid']);
    $fileCotent=IO::getFileContent('attach::'.$aid);
    $arr=getimportdata('Pichome! template',0,0,$fileCotent);

    if(!is_array($arr)) {
        exit(json_encode(['success'=>false,'msg'=>lang('do_failed')]));
    }
    $setarr=$arr['template'];
    if(empty($setarr['tname'])){
        exit(json_encode(['success'=>false,'msg'=>lang('template_name').lang('forcesecques_cannot_empty')]));
    }
    if(empty($setarr['tdir'])){
        exit(json_encode(['success'=>false,'msg'=>lang('template_tdir').lang('forcesecques_cannot_empty')]));
    }
    if(empty($setarr['tflag'])){
        exit(json_encode(['success'=>false,'msg'=>lang('template_tflag').lang('forcesecques_cannot_empty')]));
    }
    //查询是否重复

    if($id=DB::result_first("select id from %t where tdir=%s and tflag=%s",array('publish_template',$setarr['tdir'],$setarr['tflag']))){
        C::t('publish_template')->update($id,$setarr);
    }else{
        $setarr['dateline']=TIMESTAMP;
        $id= C::t('publish_template')->insert($setarr,1);
    }
    if($data=C::t('publish_template')->fetch($id)){
        $data['fdateline']=$data['dateline']?dgmdate($data['dateline'],'Y-m-d H:i:s'):'-';
        exit(json_encode(['success'=>true,'data'=>$data]));
    }
    exit(json_encode(['success'=>false,'msg'=>lang('do_failed')]));

}
elseif($do=='list'){

    $sql="1";
    $params=array('publish_template');
    $page=intval($_GET['page'])??1;
    $perpage=intval($_GET['perpage'])??20;
    $start=($page-1)*$perpage;
    if(!empty($_GET['keyword']) && ($keyword=trim($_GET['keyword']))){
        $sql.=" AND (tname LIKE %s OR tdesc LIKE %s  OR  tflag=%s OR  tdir=%s)";
        $params[]='%'.$keyword.'%';
        $params[]='%'.$keyword.'%';
        $params[]=$keyword;
        $params[]=$keyword;

    }
    $data=array();

    if($count=DB::result_first("SELECT COUNT(*) FROM %t WHERE $sql",$params)){
        foreach(DB::fetch_all("SELECT * FROM %t WHERE $sql ORDER BY dateline DESC LIMIT $start,$perpage",$params) as $value){
            $value['fdateline']=$value['dateline']?dgmdate($value['dateline'],'Y-m-d H:i:s'):'-';
            $data[]=$value;
        }
    }

    exit(json_encode(array('success'=>true,'data'=>array_values($data),'total'=>$count)));
}
elseif($do == 'uploadxml'){//上传文件图标类
    include libfile( 'class/uploadhandler' );

    $options = array( 'accept_file_types' => '/\.xml$/i',

        'upload_dir' => $_G[ 'setting' ][ 'attachdir' ] . 'cache/',

        'upload_url' => $_G[ 'setting' ][ 'attachurl' ] . 'cache/',

        'thumbnail' => array( 'max-width' => 40, 'max-height' => 40 ) );

    $upload_handler = new uploadhandler( $options );
    updatesession();
    exit();
}
else{
    include template('main/page/template');

}
exit();
