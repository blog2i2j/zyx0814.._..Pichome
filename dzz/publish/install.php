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
CREATE TABLE IF NOT EXIST `dzz_publish_list` (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  ptype tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1单文件，2多文件，3库，4单页，5智能数据;6合集',
  pval text COMMENT '发布值id',
  pname varchar(255) NOT NULL DEFAULT '' COMMENT '发布名称',
  pdesc varchar(255) NOT NULL DEFAULT '' COMMENT '发布描述',
  tid int(11) DEFAULT NULL COMMENT '模版id',
  flag tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '智能合集',
  uid int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发布用户id',
  username varchar(30) NOT NULL DEFAULT '',
  view text COMMENT '查看权限',
  download text COMMENT '下载权限',
  share text COMMENT '分享权限',
  pageset text COMMENT '偏好设置',
  filter text COMMENT '筛选项',
  extra text COMMENT '拓展参数',
  dateline int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  address char(30) DEFAULT '' COMMENT '短链接地址',
  updatedate int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
  pstatus tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发布状态;0;未发布；1：已发布；2:回收站',
  cview int(11) UNSIGNED DEFAULT '0' COMMENT '查看次数',
  cdownload int(11) UNSIGNED DEFAULT '0' COMMENT '下载次数',
  aids text  COMMENT '相关aids列表',
  rpids text,
  metakeywords varchar(255) NOT NULL DEFAULT '' COMMENT '页面关键词',
  metadescription varchar(255) NOT NULL DEFAULT '' COMMENT '页面描述',
  isdelete tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

CREATE TABLE  IF NOT EXIST `dzz_publish_template` (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  tname char(60) NOT NULL DEFAULT '' COMMENT '模版名称',
  tdesc varchar(255) NOT NULL DEFAULT '' COMMENT '模板描述',
  tflag varchar(60) NOT NULL DEFAULT '' COMMENT '模板标识符',
  ttype tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '模版类型，1单文件，2多文件，3库，4单页，5智能数据;6合集',
  dateline int(11) UNSIGNED DEFAULT '0' COMMENT '添加时间',
  cuse int(11) UNSIGNED NOT NULL DEFAULT '0',
  tdir varchar(60) NOT NULL DEFAULT '',
  exts varchar(1000) NOT NULL DEFAULT '' COMMENT '支持的文件后缀，多个使用逗号隔开，留空不限制',
  tags varchar(1000) NOT NULL DEFAULT '' COMMENT '标签，多个使用逗号隔开',
  version varchar(30) NOT NULL DEFAULT '1.0',
  PRIMARY KEY (id)
) ENGINE=MyISAM;


CREATE TABLE  IF NOT EXIST `dzz_publish_relation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned DEFAULT '0',
  `rpid` int(11) unsigned DEFAULT '0',
  `dateline` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;



INSERT INTO `dzz_publish_template` (`id`, `tname`, `tdesc`, `tflag`, `ttype`, `dateline`, `cuse`, `tdir`, `exts`, `tags`) VALUES
(1, 'Base主题-通用展示模板', '适合任意类型文件发布', 'default', 3, 0, 0, 'library', '', ''),
(3, 'Base主题-默认单页模板', '适合搭建各类型个性化页面', 'default', 4, 0, 0, 'alonepage', '', ''),
(4, 'Base主题-通用文件详情', '适合任意类型文件发布', 'default', 1, 0, 0, 'singlefile', '', ''),
(5, '独立主题-可换色多文件模板', '适合任意类型文件发布', 'simple', 2, 0, 0, 'multifile', '', ''),
(6, '合集默认模板', '合集模板', 'default', 6, 0, 0, 'collect', '', ''),
(7, 'Base主题-通用展示模板', '适合任意类型文件发布', 'default', 5, 0, 0, 'intelligent', '', ''),
(8, 'Base主题-满屏多文件模板', '适合任意类型文件发布', 'default', 2, 0, 0, 'multifile', '', ''),
(9, 'Base主题-知识库、帮助文档模板', '适合文档类文件发布', 'details', 3, 0, 0, 'library', '', ''),
(10, 'Base主题-单文档模板', '只支持txt类文档发布', 'text', 1, 0, 0, 'singlefile', 'txt,css,html,php', ''),
(11, 'Base主题-单文档模板', '只支持markdown文档发布', 'md', 1, 0, 0, 'singlefile', 'md', ''),
(12, 'Base主题-单图模板', '适合视频文件发布', 'video', 1, 0, 0, 'singlefile', 'mp3,mp4,webm,ogv,ogg,wav,m3u8,hls,mpg,mpeg', ''),
(13, 'Base主题-单图模板', '适合图片文件发布', 'image', 1, 0, 0, 'singlefile', 'jpg,png,jpeg,gif,svg,webp,aai,art,arw,bmp,cmyk,cmyka,cr2,crw,dds,dib,djvu,dng,dot,dpx,emf,epdf,epi,eps,eps2,eps3,epsf,epsi,ept,exr,fax,fig,fits,fpx,gplt,gray,graya,hdr,heic,hpgl,hrz,ico,jbig,jng,jp2,jpt,j2c,j2k,jxr,,miff,mono,mng,m2v,mpc,mpr,mrwmmsl,mtv,mvg,nef,pcd,pcds,pcl,pcx,pdb,pef,pes,pfa,pfb,pfm,pgm,picon,pict,pix,png8,png00,png24,png32,png48,png64,pnm,ppm,ps,ps2,ps3,psb,psd,ptif,pwp,rad,raf,rgb,rgb565,rgba,rgf,rla,rle,sfw,sgi,shtml,sid,mrsid,sum,svg,text,tga,tif,tiff,tim,ttf,ubrl,ubrl6,uil,uyvy,vicar,viff,wbmp,wpg,webp,wmf,wpg,x,xbm,xcf,xpm,xwd,x3f,YCbCr,YCbCrA,yuv,sr2,srf,srw,rw2,nrw,mrw,kdc,erf,canvas,caption,clip,clipboard,fractal,gradient,hald,histogram,inline,map,mask,matte,null,pango,plasma,preview,print,scan,scanx,screenshot,stegano,tile,vid,xc,granite,logo,rose,bricks,circles,fff,3fr,ai,iiq,cdr', ''),
(14, 'Base主题-窄屏多文件模板', '适合任意类型文件发布，支持自定义横幅与描述。', 'simple2', 2, 0, 0, 'multifile', '', ''),
(15, '独立主题-可换色大图展示模板', '以大图方式展示、适合图片、视频发布。', 'simple1', 2, 0, 0, 'multifile', 'jpg,png,jpeg,gif,svg,webp,aai,art,arw,bmp,cr2,crw,djvu,dng,dot,dpx,emf,epdf,epi,eps,eps2,eps3,epsf,epsi,ept,exr,fax,fig,fits,fpx,gplt,gray,graya,hdr,heic,hpgl,hrz,ico,jbig,jng,jp2,jpt,j2c,j2k,jxr,,miff,mono,mng,m2v,mpc,mpr,mrwmmsl,mtv,mvg,nef,pcd,pcds,pcl,pcx,pdb,pef,pes,pfa,pfb,pfm,pgm,picon,pict,pix,png8,png00,png24,png32,png48,png64,pnm,ppm,ps,ps2,ps3,psb,psd,ptif,rgb,rgb565,rgba,sfw,tga,tif,tiff,tim,ttf,viff,wbmp,wpg,wmf,wpg,xcf,YCbCr,YCbCrA,srf,srw,rw2,nrw,mrw,erf,canvas,caption,clip,preview,print,scan,scanx,screenshot,logo,rose,circles,fff,3fr,ai,iiq,cdr,mp4,mp3', ''),
(16, 'Base主题-音乐素材库', '适合音乐素材', 'music', 3, 0, 0, 'library', 'mp3,wav', ''),
(17, 'Base主题-文档库', '适合文档类', 'document', 3, 0, 0, 'library', '', ''),
(20, 'Base主题-音乐多文件', '适合音乐素材', 'music', 2, 0, 1, 'multifile', 'aac,ac3,aiff,alac,amr,ape,au,flac,g722,g729,mp1,mp2,mp3,ogg,opus,ra,rm,tta,voc,wav,wma,wv', '', '1.0');

EOF;

runquery($sql);
$finish = true;
