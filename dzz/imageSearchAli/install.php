<?php
/*
 * //应用安装文件；
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if(!defined('IN_OAOOA') || !defined('IN_ADMIN')) {
	exit('Access Denied');
}

$sql = <<<EOF
CREATE TABLE IF NOT EXISTS dzz_image_search_ali (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rid char(32) NOT NULL,
  md5 char(32) NOT NULL,
  aid int(11) UNSIGNED NOT NULL DEFAULT '0',
  ext char(60) DEFAULT NULL COMMENT '文件后缀',
  status tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0,待入库；1入库成功；2入库失败',
  retry int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '重试次数',
  dateline int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间（如果文件修改时间会变）',
  getmd5 tinyint(1) NOT NULL DEFAULT '0',
  appid char(6) NOT NULL COMMENT 'appid',
  PRIMARY KEY (id) USING BTREE,
  KEY rid (rid) USING BTREE,
  KEY retry (status,retry) USING BTREE
) ENGINE=MyISAM;

EOF;
runquery($sql);

$finish = true;  //结束时必须加入此句，告诉应用安装程序已经完成自定义的安装流程
