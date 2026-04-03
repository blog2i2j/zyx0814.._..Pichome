<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G;
include libfile( 'class/uploadhandler' );

$options = array( 'accept_file_types' => '/\.(gif|jpe?g|png|svg|webp)$/i',

    'upload_dir' => $_G[ 'setting' ][ 'attachdir' ] . 'cache/',

    'upload_url' => $_G[ 'setting' ][ 'attachurl' ] . 'cache/',

    'thumbnail' => array( 'max-width' => 40, 'max-height' => 40 ) );

$upload_handler = new uploadhandler( $options );
updatesession();
exit();
