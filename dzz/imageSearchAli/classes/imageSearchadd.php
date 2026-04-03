<?php
namespace dzz\imageSearchAli\classes;

use \core as C;
use \DB as DB;
use \IO as IO;

class imageSearchadd{
    // private $exts=array('txt','html','css','');
    public function run(&$data){
        $setting = getglobal('setting/imageSearchAli_setting');
        $exts=$setting['exts']?(explode(',',$setting['exts'])):array();
        if(!$setting['status'] || empty($data['ext']) || (!in_array($data['ext'],$exts))){

        }else{
            $setarr = [
                'rid'=>$data['rid']
            ];
            //如果是普通目录，存储文件md5
            if($data['apptype'] == 1 && $data['getmd5']){
                $setarr['getmd5'] = 1;
            }
            if($data['aid']){
                $setarr['aid'] = $data['aid'];
            }
            $setarr['dateline'] = $data['dateline'];
            $setarr['ext'] = $data['ext'];
            $setarr['appid'] = $data['appid'];
            if($id=C::t('#imageSearchAli#image_search_ali')->insert_data($setarr)){
                dfsockopen(getglobal('localurl') . 'index.php?mod=imageSearchAli&op=cron_add&id='.$id, 0, '', '', false, '', 0.1);
            }
        }
    }
}