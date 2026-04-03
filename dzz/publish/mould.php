<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$data = C::t('publish_list')->fetch($id);
$navtitle=lang('publish_setting').' - '.$data['pname'];
$operation = isset($_GET['operation']) ? trim($_GET['operation']) : '';
if($operation == 'basic'){
    $data = C::t('publish_list')->fetch_by_id($id);
    //处理合集列表
    $data['collection_options']= array();
    foreach(DB::fetch_all("select id,pname from %t where ptype='6'",array('publish_list')) as $value){
        $data['collection_options'][]=array('value'=>$value['id'],'label'=>$value['pname']);
    }
    $filter = [
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
    if($data['ptype']==5){
        $data['screens'] = $filter;
        if(empty($data['filter'])){
            $data['filter'] = $filter;
        }
        
    }
    if(intval($data['ptype'])==3){
        array_unshift($filter, [
            'key' => 'classify',
            'label' => lang('classify'),
            'checked' => 1,
        ]);
        $data['screens'] = $filter;
    }
    exit(json_encode($data));
}else{

    $tpldata = C::t('publish_template')->fetch($data['tid']);
    //处理模板语言包
    $lang = array();
    if(file_exists(DZZ_ROOT.'./dzz/publish/template/'.$tpldata['tdir'].'/'.$tpldata['tflag'].'/language/'.$_G['language'].'/lang.php')){
        include_once(DZZ_ROOT.'./dzz/publish/template/'.$tpldata['tdir'].'/'.$tpldata['tflag'].'/language/'.$_G['language'].'/lang.php');
    }

    include template($tpldata['tdir'].'/'.$tpldata['tflag'].'/set/page/main');
    exit();
}
