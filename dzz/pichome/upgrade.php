<?php
if ($id = DB::result_first("select id from %t where addons = %s", array('hooks', 'dzz\aiXhimage\classes\ImagetagAnddes'))) {
    DB::update('hooks', ['addons' => 'dzz\aiXhimage\classes\ImagetagAnddesc'],['id'=>$id]);
    require_once libfile('function/cache');
    clearHooksCache();
}
exit('success');