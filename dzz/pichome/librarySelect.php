<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 * 
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */
	
if(!defined('IN_OAOOA')) {
	exit('Access Denied');
}
include_once libfile('function/organization');
$ismobile=helper_browser::ismobile();
$uid =isset($_GET['uid'])?intval($_GET['uid']):$_G['uid'];
$zero=$_GET['zero']?urldecode($_GET['zero']):lang('no_institution_users');

$limit=1000;
if($_GET['do']=='tree'){
	$id=trim($_GET['id']);
	$data=array();
	if($_GET['id']=='#'){
		if ($_G['adminid'] == 1) {
			$vappdatas = DB::fetch_all("select * from %t  where isdelete = %d order by disp", array('pichome_vapp', 0));
		} else {
			$vappdatas = DB::fetch_all("select v.* from %t vm left join %t v on v.appid = vm.appid where vm.uid = %d and v.isdelete = %d order by v.disp",
				array('pichome_vappmember', 'pichome_vapp', $_G['uid'], 0));
		}
		Hook::listen("lang_parse", $vappdatas, ['getVappLangData', 1]);
		foreach($vappdatas as $value){
			$pfid='';
			$foldersum=DB::result_first("select COUNT(*) from %t where appid=%s and pfid=%s",array('pichome_folder',$value['appid'],$pfid));
			$arr = array(
				'id'=>$value['appid'],
				'text'=>$value['appname'],
				'label'=>$value['appname'],
				'disabled'=>false,
				"type"=>'library',
				'isLeaf'=>$foldersum?false:true
			);
			$data[]=$arr;
		}
		
	}else{

		if(strlen($id)==6){//为库时
			$folderdatas=DB::fetch_all("select * from %t where appid=%s and pfid=%s",array('pichome_folder',$id,''));

			Hook::listen('lang_parse',$folderdatas,['getFolderLangKey',1]);

			foreach($folderdatas as $value){
				$subnum=DB::result_first("select COUNT(*) from %t where appid=%s and pfid=%s",array('pichome_folder',$id,$value['fid']));
				$arr = array(
					'id'=>$value['appid'].$value['pathkey'],
                    'fid'=>$value['fid'],
                    'appid'=>$value['appid'],
					'text'=>$value['fname'],
					'label'=>$value['fname'],
					'disabled'=>false,
					"type"=>'folder',
					'isLeaf'=>$subnum?false:true
				);
				$data[]=$arr;
			}
		}elseif(strlen($id)==19){//为目录时
			$folderdatas=DB::fetch_all("select * from %t where  pfid=%s",array('pichome_folder',$id));
			Hook::listen('lang_parse',$folderdatas,['getFolderLangKey',1]);

			foreach($folderdatas as $value){
				$subnum=DB::result_first("select COUNT(*) from %t where pfid=%s",array('pichome_folder',$value['fid']));
				$arr = array(
                    'id'=>$value['appid'].$value['pathkey'],
                    'fid'=>$value['fid'],
                    'appid'=>$value['appid'],
					'text'=>$value['fname'],
					'label'=>$value['fname'],
					'disabled'=>false,
					"type"=>'folder',
					'isLeaf'=>$subnum?false:true
				);

				$data[]=$arr;
			}
		}

	}
	exit(json_encode($data));
}elseif($_GET['do']=='search'){

	$str=trim($_GET['str']);

	//搜索用户
	$data=array();
	if ($_G['adminid'] == 1) {
		$vappdatas = DB::fetch_all("select * from %t  where isdelete = %d order by disp", array('pichome_vapp', 0));
	} else {
		$vappdatas = DB::fetch_all("select v.* from %t vm left join %t v on v.appid = vm.appid where vm.uid = %d and v.isdelete = %d order by v.disp",
			array('pichome_vappmember', 'pichome_vapp', $_G['uid'], 0));
	}
	Hook::listen('lang_parse',$vappdatas,['getVappLangData',1]);
	foreach($vappdatas as $value){
		if(strpos($value['appname'],$str)!==false){
			$data[$value['appid']]=$value['appid'];
		}
	}
	//处理分类

	$table_sf = 'lang_'.str_replace('-', '_', $_G['language']);
	$sql="flang.svalue like %s";
	$str='%'.$str.'%';
	$params=array('pichome_folder',$table_sf,$str);
	foreach(DB::fetch_all("select f.fid from %t f LEFT JOIN %t flang ON f.fid=flang.idtype and flang.idtype='9' and flang.filed='fname' where $sql",$params) as $value){
		$data[$value['fid']]=$value['fid'];
	}

	$temp=array();
	foreach($data as $value){
		$temp[]=$value;
	}
	exit(json_encode($temp));
}