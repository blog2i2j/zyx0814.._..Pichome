<?php
namespace core\dzz;

use \core as C;
use \core\dzz\Hook as Hook;
use \DB as DB;
use \IO as IO;
class ucheck{
    public function run($data){
        $limitusernum = defined('LICENSE_LIMIT') ? LICENSE_LIMIT : 1;
        $unum=DB::result_first("select count(*) from ".DB::table('user'));
        if($unum>=$limitusernum){
            $data['error']=lang('license_user_exceed');
        }
    }
}
