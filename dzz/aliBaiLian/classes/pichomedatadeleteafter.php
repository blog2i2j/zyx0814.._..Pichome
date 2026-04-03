<?php
namespace dzz\aliBaiLian\classes;

use \C as C;
class pichomedatadeleteafter
{
    public function run($data){
        if(!$data['rids']) return true;
        foreach($data['rids'] as $rid){
            C::t('#aliBaiLian#bailian_chat')->delContentByIdvalueAndNotuid($rid,1);
            C::t('ai_imageparse')->deleteByRid($rid);
        }
    }
}