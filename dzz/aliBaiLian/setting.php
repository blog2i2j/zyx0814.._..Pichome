<?php

if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
/*$content='根据图片内容和要求，我将给出以下10个关键词作为图片标签： "秋景", "河流", "黄叶", "红岩", "晴天", "自然", "生态", "植物", "地质", "户外" 这些标签概括了图片的主要元素：秋季的景色、河水、黄色树叶以及红色的岩石地貌。同时也包含了天气状况（晴天）、场景环境（自然景观，户外活动）和植被类型等方面的信息。 标签1: 秋景 标签2: 河流 标签3: 黄叶';
echo $content;
$content=strip_tags($content);
$content = str_replace('、',',',$content);
$content = str_replace('，',',',$content);
$content = str_replace("\n",',',$content);
$content = str_replace("：",':',$content);
$content = preg_replace('/标签\d+:/', ',', $content);
$content = str_replace('标签:', ',', $content);
$tags = explode(',',$content);
$tags=array_unique($tags);
print_R($tags);
$tids = [];
foreach ($tags as $v) {

    $v = trim($v);
    $v = str_replace(['[',']',',','，','.','。','"',"\n"],'',$v);
    $v = trim($v);
    $v = preg_replace("/^\d+\s+/",'',$v);
    $v = preg_replace("/^\d+/",'',$v);
    $v = trim($v);
    if ($v) {
        if(mb_strlen($v)>6) continue;
        echo "[".$v."]";
    }
}

exit('ddd');*/

Hook::listen('adminlogin');
$appname=lang('appname');
$navtitle=lang('setting');

$do = isset($_GET['do']) ? trim($_GET['do']) : '';
if($do == 'addPrompt'){
    $cate = isset($_GET['cate']) ? intval($_GET['cate']) : 0;
    $name = isset($_GET['name']) ? trim($_GET['name']) : '';
    $prompts=$_GET['prompts'];
    $prompt=is_array($prompts)?$prompts[0]:array('model'=>'','prompt'=>'');
    if(!$name || empty($prompt['model']) || empty($prompt['prompt'])){
        exit(json_encode(array('success'=>false,'error'=>lang('please_input_all_info'))));
    }else{
        $setarr = array(
            'name'=>$name,
            'prompts'=>$prompts,
            'cate'=>$cate,
            'disp'=>DB::result_first("select max(disp) from %t where cate = %d",['bailian_imageprompt',$cate])+1,
            'isdefault'=>0,
            'status'=>isset($_GET['status']) ? intval($_GET['status']):0
        );

        $id = C::t('bailian_imageprompt')->insertData($setarr);
        if($id){
            exit(json_encode(array('success'=>true)));
        }else{
            exit(json_encode(array('success'=>false,'error'=>lang('add_unsuccess'))));
        }
    }
}elseif($do == 'editPrompt'){
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $name = isset($_GET['name']) ? trim($_GET['name']) : '';

    $prompts=$_GET['prompts'];
    $prompt=is_array($prompts)?$prompts[0]:array('model'=>'','prompt'=>'');
    if(!$name || empty($prompt['model']) || empty($prompt['prompt'])){
        exit(json_encode(array('success'=>false,'error'=>lang('please_input_all_info'))));
    }else{
        $setarr = array(
            'name'=>$name,
            'prompts'=>$prompts,
        );

        $ret = C::t('bailian_imageprompt')->editById($id,$setarr);
        if(isset($ret['error'])){
            exit(json_encode(array('success'=>false,'error'=>$ret['error'])));
        }else{
            exit(json_encode(array('success'=>true)));
        }
    }
}elseif($do == 'editModel'){

    $name = isset($_GET['name']) ? trim($_GET['name']) : '';
    $model = trim($_GET['model']);


    if(!$name || !$model){
        exit(json_encode(array('success'=>false,'error'=>lang('please_input_all_info'))));
    }else{
        $setarr = array(
            'name'=>$name,
            'model'=>$model,
            'description'=>htmlspecialchars($_GET['description']),
            'vendor'=>htmlspecialchars($_GET['vendor']),
            'type'=>htmlspecialchars($_GET['type']),
        );
       $ret = C::t('bailian_model')->insert_by_model($setarr);

        if(!$ret){
            exit(json_encode(array('success'=>false,'error'=>'Failure')));
        }else{
            $ret['fdateline']=dgmdate($ret['dateline'],'Y-m-d H:i:s');

            exit(json_encode(array('success'=>true,'data'=>$ret)));
        }
    }
}elseif($do == 'modelList'){
    $data=array();
    $perpage=isset($_GET['perpage'])?intval($_GET['perpage']):20;
    $page=isset($_GET['page'])?intval($_GET['page']):1;
    $keyword=trim($_GET['keyword']);
    $start=($page-1)*$perpage;
    $sql="1";
    $params=array('bailian_model');
    if($keyword){

        $sql.=" and name like %s OR model like %s";
        $params[]='%'.$keyword.'%';
        $params[]='%'.$keyword.'%';
    }
    if($count=DB::result_first("select COUNT(*) from %t where $sql",$params)){
        foreach(DB::fetch_all("select * from %t where $sql order by dateline desc limit $start,$perpage",$params) as $value){
            $value['fdateline']=dgmdate($value['dateline'],'Y-m-d H:i:s');
            $data[]=$value;
        }
    }
    exit(json_encode(array('success'=>true,'data'=>$data,'count'=>$count)));
}elseif($do == 'modelDelete'){
    if(C::t('bailian_model')->delete($_GET['model'])){
        exit(json_encode(array('success'=>true)));
    }else{
        exit(json_encode(array('success'=>false,'error'=>lang('del_unsuccess'))));
    }

}elseif($do == 'sortPrompt'){
    $ids = isset($_GET['ids']) ? $_GET['ids'] : '';
    $ids = explode(',',$ids);
    C::t('bailian_imageprompt')->sortByIds($ids);
    exit(json_encode(array('success'=>true)));
}elseif($do == 'setStatus'){
    $status = intval($_GET['status']);
    C::t('bailian_imageprompt')->setStatusById($_GET['id'],$status);
    exit(json_encode(array('success'=>true)));
}elseif($do == 'getPromptByCate'){
    $cate = isset($_GET['cate']) ? intval($_GET['cate']) : 0;
    $data = C::t('bailian_imageprompt')->fetchPromptByCate($cate);
    exit(json_encode(array('success'=>true,'data'=>$data)));
}else{
    include libfile('function/cache');
    if (submitcheck('settingsumbit')) {
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;

        $apiurl = trim($_GET['apiurl']);

        if($status && empty($apiurl)){
            showmessage(lang('config_not_complete'), dreferer(), array(), array('alert' => 'error'));
        }
        $arr=array(
            'apikey'=>trim($_GET['apikey']),
            'apiurl'=>trim($_GET['apiurl']),
            'chatModel'=>trim($_GET['chatModel']),
            'status'=>intval($_GET['status'])
        );
        C::t('setting')->update('setting_bailian',$arr);
        updatecache('setting');
        exit(json_encode(array('success'=>true)));
    }else{
        $setting=C::t('setting')->fetch('setting_bailian',true);
        if(!$setting['status']) $setting['status'] = 0;

        if(empty($setting['apiurl'])) $setting['apiurl']='https://dashscope.aliyuncs.com/compatible-mode/v1';
        //获取当前的模型
        $bailian=new \baiLian();
        $models=array();
        if($ret=$bailian->modelList()){
            $models=($ret);
        }
        include template('setting');
        exit();
    }
}
