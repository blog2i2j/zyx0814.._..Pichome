<?php
if (!defined('IN_OAOOA') ||  !defined('PICHOME_LIENCE')) {
    exit('Access Denied');
}
$sid = isset($_GET['sid']) ? dzzdecode($_GET['sid'],'',0):'';
$sharedata = C::t('pichome_share')->fetch_by_idandtype($sid,2);

$locationurl = getglobal('siteurl').'index.php?mod=collection&op=detail&shareid='.dzzencode($sharedata['filepath'],'',0,0).'#id=all';

header("Location:".$locationurl);
exit();