<?php
/* @authorcode  codestrings
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 */

if(!defined('IN_OAOOA') || !defined('IN_ADMIN')) {
    exit('Access Denied');
}

//卸载

$sql = <<<EOF
DELETE FROM `dzz_setting` where skey = 'imageSearchAli_setting';
DROP TABLE IF EXISTS dzz_image_search_ali ;
EOF;

runquery($sql);

$finish = true;