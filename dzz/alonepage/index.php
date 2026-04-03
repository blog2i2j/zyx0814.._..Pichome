<?php
    /*
     * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
     * @license     https://www.oaooa.com/licenses/
     *
     * @link        https://www.oaooa.com
     * @author      zyx(zyx@oaooa.com)
     */
//此页的调用地址  index.php?mod=test;
//同目录的其他php文件调用  index.php?mod=test&op=test1;
    if (!defined('IN_OAOOA')) {//所有的php文件必须加上此句，防止被外部调用
        exit('Access Denied');
    }
Hook::listen('adminlogin');
$navtitle=lang('page_manage');
$total0=DB::result_first("SELECT COUNT(*) FROM %t where isdelete='0'",array('pichome_templatepage'));
$total1=DB::result_first("SELECT COUNT(*) FROM %t where isdelete='1'",array('pichome_templatepage'));
include template('page/index');