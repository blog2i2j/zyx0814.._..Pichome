<?php
echo dgmdate(1724829410,'Y-m-d H:i:s');
die;
if(!isset($_GET['count'])){
    $count = DB::result_first("select count(DISTINCT fid) from %t where 1  ",['pichome_folderresources']);
}else{
    $count = intval($_GET['count']);
}
$i = isset($_GET['i']) ? intval($_GET['i']) : 1;
$perpage = 1000;
$start = ($i - 1) * $perpage;
$j = 0;
foreach(DB::fetch_all("select DISTINCT fid from %t where 1  limit $start,$perpage",['pichome_folderresources']) as $value){
    $pathkey = DB::result_first("select pathkey from %t where fid = %s",['pichome_folder',$value['fid']]);
    if($pathkey){
        DB::update('pichome_folderresources',['pathkey'=>$pathkey],['fid'=>$value['fid']]);
        $j++;
    }
}
if ($j >= $perpage) {
    $complatei = ($i - 1) * $perpage + $j;
    $i++;
    $msg='升级完成';
    $next =  getglobal('siteurl').'index.php?mod=pichome&op=test&i=' . $i.'&count='.$count;
    show_msg($msg."[ $complatei/$count] ", $next);
} else {
    exit('升级结束');
}

function show_msg($message, $url_forward = '', $time = 1, $noexit = 0, $notice = '')
{

    if ($url_forward) {
        $url_forward = $_GET['from'] ? $url_forward . '&from=' . rawurlencode($_GET['from']) . '&frommd5=' . rawurlencode($_GET['frommd5']) : $url_forward;
        $message = "<a href=\"$url_forward\">$message (跳转中...)</a><br>$notice<script>setTimeout(\"window.location.href ='$url_forward';\", $time);</script>";
    }

    show_header();
    print<<<END
	<table>
	<tr><td>$message</td></tr>
	</table>
END;
    show_footer();
    !$noexit && exit();
}


function show_header()
{
    global $config;

    $nowarr = array($_GET['step'] => ' class="current"');
    if (in_array($_GET['step'], array('waitingdb', 'prepare'))) {
        $nowarr = array('sql' => ' class="current"');
    }
    print<<<END
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=$config[charset]" />
	<title> 数据库升级程序 </title>
	<style type="text/css">
	* {font-size:12px; font-family: Verdana, Arial, Helvetica, sans-serif; line-height: 1.5em; word-break: break-all; }
	body { text-align:center; margin: 0; padding: 0; background: #F5FBFF; }
	.bodydiv { margin: 40px auto 0; width:720px; text-align:left; border: solid #86B9D6; border-width: 5px 1px 1px; background: #FFF; }
	h1 { font-size: 18px; margin: 1px 0 0; line-height: 50px; height: 50px; background: #E8F7FC; color: #5086A5; padding-left: 10px; }
	#menu {width: 100%; margin: 10px auto; text-align: center; }
	#menu td { height: 30px; line-height: 30px; color: #999; border-bottom: 3px solid #EEE; }
	.current { font-weight: bold; color: #090 !important; border-bottom-color: #F90 !important; }
	input { border: 1px solid #B2C9D3; padding: 5px; background: #F5FCFF; }
	#footer { font-size: 10px; line-height: 40px; background: #E8F7FC; text-align: center; height: 38px; overflow: hidden; color: #5086A5; margin-top: 20px; }
	</style>
	</head>
	<body>
	<div class="bodydiv">
	<h1>欧奥数据库升级工具</h1>
	<div style="width:90%;margin:0 auto;">
	<table id="menu">
	<tr>
	<td{$nowarr[start]}>升级开始</td>
	<td{$nowarr[sql]}>数据库结构添加与更新</td>
	<td{$nowarr[data]}>数据更新</td>
	<td{$nowarr[delete]}>数据库结构删除</td>
	<td{$nowarr[cache]}>升级完成</td>
	</tr>
	</table>
	<br>
END;
}

function show_footer()
{
    print<<<END
	</div>
	<div id="footer">Copyright © 2012-2021 oaooa.com All Rights Reserved.</div>
	</div>
	<br>
	</body>
	</html>
END;
}