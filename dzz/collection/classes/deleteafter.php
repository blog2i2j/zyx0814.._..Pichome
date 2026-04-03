<?php

namespace dzz\collection\classes;

use \core as C;
use \DB as DB;


class deleteafter
{

    public function run($data)
    {
        if(!$data['deluid'])$data['deluid'] = getglobal('uid');
        if(!$data['delusername'])$data['delusername'] = getglobal('username');
        C::t('pichome_collectlist')->delete_by_rids($data['rids'],$data['deluid'],$data['deluername']);

    }



}