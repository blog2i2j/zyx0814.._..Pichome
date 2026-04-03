<?php
/* @authorcode  codestrings
  * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 * 
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */
if(!defined('IN_OAOOA') || !defined('IN_ADMIN')) {
	exit('Access Denied');
}

$sql = <<<EOF
CREATE TABLE IF NOT EXISTS pichome_my_file (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  uid int(10) UNSIGNED NOT NULL DEFAULT '0',
  username char(30) NOT NULL DEFAULT '',
  source varchar(30) NOT NULL DEFAULT '' COMMENT '来源',
  aid int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文件AID',
  filetype char(15) NOT NULL DEFAULT '' COMMENT '文件类型',
  filename varchar(255) NOT NULL DEFAULT '' COMMENT '文件名称',
  dateline int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (id) USING BTREE,
  KEY uid (uid) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

EOF;

runquery($sql);
$finish = true;
