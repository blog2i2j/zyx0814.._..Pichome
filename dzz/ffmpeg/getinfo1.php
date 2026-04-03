<?php
ignore_user_abort(true);
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
use dzz\ffmpeg\classes\info as info;
$info =new info;
$data = C::t('pichome_resources')->fetch_data_by_rid($_GET['rid']);
$ret=$info->run($data);
print_r($ret);
exit('success');