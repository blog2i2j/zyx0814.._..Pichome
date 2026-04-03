<?php

if(!$patharr=Pdecode($_GET['path'])){
    exit(json_encode(array('status'=>2,'error'=>lang('no_perm'))));
}
$rid = $patharr['path'];
$isshare = $patharr['isshare'];
$perm = $patharr['perm'];
$isadmin = $patharr['isadmin'];
$ulevel = getglobal('pichomelevel') ? getglobal('pichomelevel') : 0;
if (!$rid) {
    exit(json_encode(array('status'=>2,'error'=>lang('no_perm'))));
}

$resourcesdata = C::t('pichome_resources')->fetch_by_rid($rid,$isshare,1,$perm);
$appdata = C::t('pichome_vapp')->fetch($resourcesdata['appid']);
if($perm){
    $resourcesdata['download'] =perm::check('download2',$perm)?1:0;
    $resourcesdata['share'] =perm::check('share',$perm)?1:0;
    $resourcesdata['view'] =perm::check('read2',$perm)?1:0;
    $resourcesdata['edit'] =perm::check('edit2',$perm)?1:0;
    //if($resourcesdata['edit']) $resourcesdata['dpath']=$_GET['path'];
    $resourcesdata['dpath']=Pencode(array('path' => $resourcesdata['rid'], 'perm' => $perm, 'ishare' => $isshare, 'isadmin' => $isadmin), 7200);
    if($resourcesdata['realfianllypath']){
        $resourcesdata['realfianllypath']=$_G['siteurl'].'index.php?mod=io&op=getStream&path='.$resourcesdata['dpath'];
    }
}
if((!isset($resourcesdata['view']) || !$resourcesdata['view']) && !$isshare && !C::t('pichome_vapp')->getpermbypermdata($appdata['view'],$resourcesdata['appid'])){
    exit(json_encode(array('status'=>2,'error'=>lang('no_perm'))));
}
$resourcesdata['preview']=array();
if(!$resourcesdata['iniframe']){
    $resourcesdata['preview'] = C::t('thumb_preview')->fetchPreviewByRid($rid);
    $resourcesCover = ['spath'=>$resourcesdata['icondata'],'lpath'=>$resourcesdata['originalimg']];
    if($resourcesdata['preview']) array_unshift($resourcesdata['preview'],$resourcesCover);

}

$appdata = C::t('pichome_vapp')->fetch($resourcesdata['appid']);
$data['fileds'] = unserialize($appdata['fileds']);
//获取tab数据
$tabstatus = 0;
Hook::listen('checktab', $tabstatus);
if($tabstatus){
    foreach($data['fileds'] as $v){
        if($v['type'] == 'tabgroup'){
            $gid =  intval(str_replace('tabgroup_','',$v['flag']));
            $tids = [];
            foreach(DB::fetch_all("select tid from %t where rid= %s and gid = %d",array('pichome_resourcestab',$rid,$gid)) as $val){
                $tids[] = $val['tid'];
            }
            Hook::listen('gettab',$tids);
            $data[$v['flag']] = $tids;
        }
    }
}
$resourcesdata = array_merge($resourcesdata,$data);

   include template('robot/index');
   exit();
//include template('page/main');