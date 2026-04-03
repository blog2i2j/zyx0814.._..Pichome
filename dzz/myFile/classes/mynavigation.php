<?php
namespace dzz\myFile\classes;

use \core as C;
use \DB as DB;

class mynavigation
{

    public function run(&$data)
    {
        global $_G;
        $viewsnum = DB::result_first("select COUNT(*) from %t where uid=%d",array('my_file',$_G['uid']));
        $data[] = ['id' => 'myFile', 'name' => lang('my_creation'), 'url' => 'index.php?mod=myfile', 'number' => $viewsnum];
    }
}