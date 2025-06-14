<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
@set_time_limit(0);
@ini_set('max_execution_time', 0);
require_once(DZZ_ROOT . './dzz/class/class_encode.php');
require_once libfile('function/user', '', 'user');

use \core as C;
use \DB as DB;
use \IO as IO;
use \ImagePalette as ImagePalette;
use \Color as Color;

class billfishxport
{

    private $path = '';//待执行数据path
    private $appid = 0;//库id
    private $uid = 0;//用户id
    private $username = null;//用户名
    private $filenum = 0;//总文件数
    private $checklimit = 1000;
    private $onceexportnum = 100;
    private $getinfonum = 0;
    private $readtxt = DZZ_ROOT . './data/attachment/cache/';
    private $exportstatus = 0;
    private $donum = 0;
    private $lastid = '';
    private $charset = 'UTF-8';
    private $version = '';
    private $notallowext = '';
    private $db = null;
    private $defaultperm = 0;
    private $processname = '';
    public $palette = [
        0xfff8e1, 0xf57c00, 0xffd740, 0xb3e5fc, 0x607d8b, 0xd7ccc8,
        0xff80ab, 0x4e342e, 0x9e9e9e, 0x66bb6a, 0xaed581, 0x18ffff,
        0xffe0b2, 0xc2185b, 0x00bfa5, 0x00e676, 0x0277bd, 0x26c6da,
        0x7c4dff, 0xea80fc, 0x512da8, 0x7986cb, 0x00e5ff, 0x0288d1,
        0x69f0ae, 0x3949ab, 0x8e24aa, 0x40c4ff, 0xdd2c00, 0x283593,
        0xaeea00, 0xffa726, 0xd84315, 0x82b1ff, 0xab47bc, 0xd4e157,
        0xb71c1c, 0x880e4f, 0x00897b, 0x689f38, 0x212121, 0xffff00,
        0x827717, 0x8bc34a, 0xe0f7fa, 0x304ffe, 0xd500f9, 0xec407a,
        0x6200ea, 0xffab00, 0xafb42b, 0x6a1b9a, 0x616161, 0x8d6e63,
        0x80cbc4, 0x8c9eff, 0xffeb3b, 0xffe57f, 0xfff59d, 0xff7043,
        0x1976d2, 0x5c6bc0, 0x64dd17, 0xffd600
    ];

    public function __construct($data = array())
    {
        //获取导入记录表基本数据
        if (strpos($data['path'], ':') === false) {
            $bz = 'dzz';
            $did = 1;
        } else {
            $patharr = explode(':', $data['path']);
            $bz = $patharr[0];
            $did = $patharr[1];

        }

        if ($bz == 'dzz') $did = 1;
        if (!is_numeric($did) || $did < 2) {
            $this->path = str_replace('/', BS, $data['path']);
            $this->path = str_replace('dzz::', '', $data['path']);
        } else {
            $this->iscloud = true;
            $this->path = $data['path'];
        }
        $this->appid = $data['appid'];
        $this->processname = 'PICHOMEVAPPISDEL_'.$this->appid;
        $this->uid = $data['uid'];
        $this->username = $data['username'];
        $this->exportstatus = $data['state'];
        $this->donum = $data['donum'];
        $this->filenum = $data['filenum'];
        $this->lastid = $data['lastid'];
        $this->version = $data['version'];
        $this->defaultperm = $data['perm'] ? intval($data['perm']) : 0;
        if ($data['charset']) $this->charset = $data['charset'];
        //尝试连接数据库
        $connect = $this->connect_db();
        if (!is_object($connect)) {
            return $connect;
        } else {
            $this->db = $connect;
        }
    }

    public function getDbfilepath()
    {
        if ($this->iscloud) {
            $dbpath = $this->path . '/.bf/billfish.db';
            $dbfileparh = IO::getStream($dbpath);
            $cachedb = $this->readtxt . 'billfishexport' . md5($this->path) . '.db';
            if (!is_file($cachedb)) {
                if (!file_put_contents($cachedb, file_get_contents($dbfileparh))) {
                    return array('error' => 'connect db failer');
                } else {
                    return $cachedb;
                }
            } else {
                $cachedbinfo = IO::getMeta($cachedb);
                $dbfileinfo = IO::getMeta($dbfileparh);
                if ($cachedbinfo['dateline'] < $dbfileinfo['dateline']) {
                    if (!file_put_contents($cachedb, file_get_contents($dbfileparh))) {
                        return array('error' => 'connect db failer');
                    } else {
                        return $cachedb;
                    }
                } else {
                    return $cachedb;
                }
            }
        } else {
            return $this->path . BS . '.bf' . BS . 'billfish.db';
        }

    }

    public function connect_db()
    {


        $dbfile = $this->getDbfilepath();
        if (isset($dbfile['error'])) {
            return array('error' => $dbfile['error']);
        }
        $dsn = 'sqlite:' . $dbfile;
        try {
            return new PDO($dsn);
        } catch (PDOException $e) {
            return array('error' => $e->getMessage());
        }

    }

    public function fetch($sql)
    {
        $q = $this->db->query($sql);
        $rows = $q->fetch(PDO::FETCH_ASSOC);
        return $rows;
    }

    public function fetch_all($sql)
    {
        $q = $this->db->query($sql);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    public function initExport()
    {
        if(dzz_process::getlocked($this->processname)) exit('vapp isdeleted');
        //修改导入状态为1
        $versionsql = " SELECT version from library where 1";
        $versions = $this->fetch($versionsql);
        $this->version = $versions['version'];
        C::t('pichome_vapp')->update($this->appid, array('state' => 1, 'version' => intval($this->version)));
        if ($this->version > 30) {
            //查询res_join_tag是否有文件id索引
            $fecthsql = "SELECT * FROM sqlite_master WHERE type = 'index'";
            $indexdata = $this->fetch_all($fecthsql);
            $indexarr = array_column($indexdata, 'name');
            //如果标签表iid没有索引创建res_join_tag_iid_idx索引
            if (!in_array('res_join_tag_id', $indexarr)) {
                $createsql = "CREATE INDEX res_join_tag_id ON bf_tag_join_file (file_id ASC )";
                $this->db->query($createsql);
            }

            //查询待导入文件数
            // $sql = "select count(f.id) as num from bf_file f left join bf_material m on f.id = m.file_id where m.is_recycle =0 ";
            $sql = "select count(id) as num from bf_file where 1";
            $data = $this->fetch($sql);
            $this->filenum = $data['num'];
        } elseif ($this->version == 30) {
            //查询res_join_tag是否有文件id索引
            $fecthsql = "SELECT * FROM sqlite_master WHERE type = 'index'";
            $indexdata = $this->fetch_all($fecthsql);
            $indexarr = array_column($indexdata, 'name');
            //如果标签表iid没有索引创建res_join_tag_iid_idx索引
            if (!in_array('res_join_tag_id', $indexarr)) {
                $createsql = "CREATE INDEX res_join_tag_id ON bf_tag_join_file (tag_id ASC )";
                $this->db->query($createsql);
            }

            //查询待导入文件数
            // $sql = "select count(f.id) as num from bf_file f left join bf_material m on f.id = m.file_id where m.is_recycle =0 ";
            $sql = "select count(id) as num from bf_file where 1";
            $data = $this->fetch($sql);
            $this->filenum = $data['num'];
        } else {
            //查询res_join_tag是否有文件id索引
            $fecthsql = "SELECT * FROM sqlite_master WHERE type = 'index'";
            $indexdata = $this->fetch_all($fecthsql);
            $indexarr = array_column($indexdata, 'name');
            //如果标签表iid没有索引创建res_join_tag_iid_idx索引
            if (!in_array('res_join_tag_iid', $indexarr)) {
                $createsql = "CREATE INDEX res_join_tag_iid ON res_join_tag ( iid ASC )";
                $this->db->query($createsql);
            }

            //查询待导入文件数
            //$sql = "select count(s.id) as num from source s left join res_prop rp on s.id = rp.iid where rp.action =0 ";
            $sql = "select count(id) as num from source where 1 ";
            $data = $this->fetch($sql);
            $this->filenum = $data['num'];
        }
        //如果没有数据，视为导入成功
        if (!$this->filenum) {
            C::t('pichome_vapp')->update($this->appid, array('state' => 4));
        } else {
            C::t('pichome_vapp')->update($this->appid, array('state' => 2, 'filenum' => $this->filenum, 'donum' => 0, 'percent' => 0, 'lastid' => 0));
        }
        return array('success' => true);
    }

    //获取文件可访问的真实地址
    public function getFileRealFileName($filepath, $filename)
    {
        $charsetarr = ['GBK', 'GB18030'];
        $returnname = $filename;
        if (!is_file($filepath . BS . $filename)) {
            foreach ($charsetarr as $v) {
                $filemetadataname = diconv($filename, CHARSET, $v);
                if (is_file($filepath . BS . $filemetadataname)) {
                    $returnname = $filemetadataname;
                    break;
                }
            }
        }
        return $returnname;

    }

    public function execExport($force = false)
    {

        if ($this->version < 30) {
            $this->oldexport($force);
        } elseif($this->version == 30){
            $this->export($force);
        }else{
            $this->export3($force);
        }

    }
    public function export3($force = false)
    {
        //开始页数
        if ($this->lastid) {
            $start = $this->lastid;
        } else $start = 1;
        $lastid = $start;
        $start = ($start - 1) * $this->onceexportnum;
        $sql = "select f.*,m.w,m.h,m.is_recycle,m.thumb_tid,m.colors,mu.comments_detail,mu.note,mu.score,mu.origin from bf_file f
         left join  bf_material_v2 m on m.file_id = f.id 
         left join bf_material_userdata mu on mu.file_id=f.id
         where 1 limit $start,$this->onceexportnum";
        $datas = $this->fetch_all($sql);

        foreach ($datas as $v) {
            if(dzz_process::getlocked($this->processname)) exit('vapp isdeleted');
            //查询文件是否在回收站
            $id = $v['id'];//文件id
            $rid = md5($this->appid . $id);

            //如果文件在回收站
            if ($v['is_recycle'] > 0) {
                //如果已经有数据,标记为已删除
                if (DB::fetch_first("select count(rid) from %t where rid = %s", array('pichome_resources', $rid))) {
                    C::t('pichome_resources')->update($rid, array('isdelete' => 1));
                }
                //文件总数减1
                $this->filenum -= 1;
            }
            else {
                //获取文件后缀
                $ext = strtolower(substr(strrchr($v['name'], '.'), 1));
                //获取文件类型
                $type = getTypeByExt($ext);

                //出入主表数据
                $setarr = [
                    'rid' => $rid,
                    'uid' => $this->uid,
                    'username' => $this->username,
                    'appid' => $this->appid,
                    'ext' => $ext,
                    'type' => $type,
                    'name' => $v['name'],
                    'mtime' => ($v['mtime']) ? $v['mtime'] * 1000 : $v['born'] * 1000,
                    'dateline' => $v['ctime'] ? $v['ctime'] * 1000 : $v['born'] * 1000,
                    'btime' => $v['born'] ? $v['born'] * 1000 : $v['mtime'] * 1000,
                    'size' => $v['file_size'],
                    'width' => $v['w'],
                    'height' => $v['h'],
                    'grade' => $v['score'],
                    'apptype' => 2,
                    'hasthumb' => $v['thumb_tid'] ? 1 : 0,
                    'lastdate' => strtotime('now')
                ];
                //处理目录数据

                if ($v['pid']) {
                    $folderdata = $this->getFolderfid($v['pid']);
                }

                //如果为新数据，写入默认权限
                if (!DB::result_first("select count(id) from %t where rid = %s and appid = %s", ['billfish_record', $rid, $this->appid])) {
                    $setarr['level'] = isset($folderdata['level']) ? intval($folderdata['level']) : $this->defaultperm;
                }
                //数据插入主表
                if (C::t('#billfish#billfish_record')->inser_data($v['id'], $setarr)) {
                    if ($setarr['hasthumb']) {
                        $thumbdir = dechex($v['id']);

                        if (strlen($thumbdir) < 2) {
                            $thumbdir = str_pad($thumbdir, 2, 0, STR_PAD_LEFT);
                        } elseif (strlen($thumbdir) > 2) {
                            $thumbdir = substr($thumbdir, -2);
                        }
                        $thumbdir = (string)$thumbdir;
                        $pathdir = ($this->iscloud) ? $this->path . '/.bf/.preview/' . $thumbdir . '/' : $this->path . BS . '.bf' . BS . '.preview' . BS . $thumbdir . BS;
                        $savepatdir = str_replace(array(DZZ_ROOT, BS), array('', '/'), $pathdir);
                        $smallfile = $pathdir . $v['id'] . '.small.webp';


                    }
                    //定义属性表变量
                    $attrdata = [];
                    $attrdata['desc'] = $v['note'];
                    $attrdata['link'] = $v['origin'];
                    //将名字记入搜索字段
                    $attrdata['searchval'] = $setarr['name'] . getstr($attrdata['desc'],255) . $attrdata['link'];
                    //处理目录数据
                    if ($folderdata) {
                        $fid = $folderdata['fid'];
                        $folderarr = [
                            'fid' => $fid,
                            'appid' => $this->appid,
                            'rid' => $rid
                        ];
                        C::t('pichome_folderresources')->insert($folderarr);
                        $attrdata['path'] = $folderdata['dirpath'] . BS . $setarr['name'];
                        C::t('pichome_resources')->update($rid, ['fids' => $fid]);
                    } else {
                        $attrdata['path'] = $setarr['name'];
                        DB::delete('pichome_folderresources', ['rid' => $rid]);
                        C::t('pichome_resources')->update($rid, ['level' => $this->defaultperm, 'fids' => '']);
                    }

                    //目录数据处理完成
                    $attrdata['path'] = ($this->iscloud) ? $attrdata['path'] : $this->getFileRealFileName($this->path, $attrdata['path']);
                    //转码路径 记入属性表
                    //$p = new Encode_Core();
                    //$this->charset = $p->get_encoding($attrdata['path']);
                    //if (CHARSET != $this->charset) $attrdata['path'] = diconv($attrdata['path'],CHARSET, $this->charset);;

                    //标签数据开始

                    //查询文件标签id
                    $sql = "select tag_id from bf_tag_join_file where file_id = $id";//耗时最多
                    $tiddata = $this->fetch_all($sql);

                    $tids = [];
                    foreach ($tiddata as $val) {
                        $tids[] = $val['tag_id'];
                    }


                   // if (!empty($tids)) {
                        $tidstr = dimplode($tids);
                       /* //查询标签分类数据
                        $sql = "select jg.gid,g.name from bf_tag_join_group jg 
                            left join bf_tag_group g on g.id = jg.gid 
                            where jg.tag_id in($tidstr) group by jg.gid";
                        $taggroupdata = $this->fetch_all($sql);
                        $relationgroupdata = [];
                        //插入标签分类关系表及pichome分类返回原分类id对应pichome标签分类id
                        foreach ($taggroupdata as $val) {
                            $tmpreturn = C::t('#billfish#billfish_taggrouprecord')->insert_data($val, $this->appid);
                            $relationgroupdata[$tmpreturn['bcid']] = $tmpreturn['cid'];
                        }*/
                        //处理标签表数据
                        //查询标签名称,id 插入标签对照表 返回原标签id对应pichome标签id 将标签加入searchval
                        $sql = " select id,name from bf_tag_v2 t where t.id in($tidstr)";
                        $tagdata = $this->fetch_all($sql);

                        $tagrelativedata = [];
                        $taggroups = [];
                        foreach ($tagdata as $val) {
                            $tagsetarr = ['name' => $val['name'], 'lid' => $val['id']];
                            $tmptagrelativedata = C::t('#billfish#billfish_tagrecord')->insert_data($tagsetarr, $this->appid);
                            $tagrelativedata[$tmptagrelativedata['lid']] = $tmptagrelativedata['tid'];
                            $attrdata['searchval'] .= $val['name'];
                           // if ($val['gid']) $taggroups[] = ['gid' => $val['gid'], 'lid' => $val['id']];
                        }

                        //处理标签文件关系数据
                        $ftids = array_values($tagrelativedata);
                        $ftids = empty($ftids) ? []:$ftids;
                        $oattrtag = [];
                        //查询pichome是否有标签数据
                        foreach(DB::fetch_all("select tid from %t where rid = %s", array('pichome_resourcestag', $rid)) as $tago){
                            $oattrtag[] = $tago['tid'];
                        }

                       // if ($oattrtag) {
                            $ottids = $oattrtag;

                            //取得删除的标签
                            $deltids = array_diff($ottids,$ftids);
                            if (!empty($deltids)) C::t('pichome_resourcestag')->delete_by_ridtid($rid, $deltids);
                            //取得插入的标签
                            $inserttids  = array_diff($ftids,$ottids );
                       // }

                        //插入标签关系表
                        foreach ($inserttids as $val) {
                            $tagresourcesattr = ['tid' => $val, 'rid' => $rid, 'appid' => $this->appid];
                            C::t('pichome_resourcestag')->insert($tagresourcesattr);
                        }
                        //更新属性表标签数据
                        $attrdata['tag'] = implode(',', $ftids);
                   // }


                    //标签数据结束

                    //开始处理颜色数据
                    DB::delete('pichome_palette', array('rid' => $rid));
                    $colorstr = $v['colors'];
                    $colorarr = explode('|',$colorstr);
                    foreach($colorarr as $colorval){
                        $tmpcolor = explode(',',$colorval);
                        if($tmpcolor[0] > 0){
                            $intcolor = $tmpcolor[1];
                            //获取颜色十六进制值
                            $colorhex = $this->dec2hex($intcolor);
                            $colorhexarr = str_split($colorhex,2);
                            array_shift($colorhexarr);
                            $colorhexarr = array_reverse($colorhexarr);
                            $colorhex = implode('',$colorhexarr);
                            //获取整型颜色值
                            $intcolor = hexdec($colorhex);
                            $intcolorsarr[] = $intcolor;
                            $rgbcolor = hex2rgb($colorhex);
                            //$rgbarr = [$rgbcolor['r'],$rgbcolor['g'],$rgbcolor['b']];
                            // $color = new Color($rgbarr);
                            $palettesnum[] = $p = $this->getPaletteNumber($intcolor);
                            $pcolorarr = ['rid' => $rid,
                                'color' => $tmpcolor[1],
                                'weight' => $tmpcolor[0],
                                'r' => $rgbcolor['r'],
                                'g' => $rgbcolor['g'],
                                'b' => $rgbcolor['b'],
                                'p' => $p
                            ];
                            C::t('pichome_palette')->insert($pcolorarr);
                        }


                    }
                    $isgray = $this->isgray($intcolorsarr);
                    $attrcolorsetarr = [
                        'gray' => $isgray,
                        'colors' => implode(',', array_unique($palettesnum))
                    ];
                   // C::t('pichome_resources_attr')->update($rid, $attrsetarr);
                    //颜色数据处理结束

                    $attrdata = array_merge($attrdata,$attrcolorsetarr);

                    //处理标注数据

                    //删除原标注数据
                    C::t('pichome_comments')->delete_by_rid($rid);
                    if ($v['comments_detail']) {
                        $commentdata = json_decode($v['comments_detail'], true);
                        foreach ($commentdata as $commentval) {
                            $tcommentval['id'] = random(13) . $this->appid;
                            $tentval['appid'] = $this->appid;
                            $tcommentval['rid'] = $rid;
                            $tcommentval['x'] = number_format($commentval['x'], 2);
                            $tcommentval['y'] = number_format($commentval['y'], 2);
                            $tcommentval['width'] = number_format($commentval['cx'], 2);
                            $tcommentval['height'] = number_format($commentval['cy'], 2);
                            $tcommentval['annotation'] = $commentval['comment'];
                            try {
                                C::t('pichome_comments')->insert($tcommentval);
                                $setarr['searchval'] .= $tcommentval['annotation'];
                            } catch (Exception $e) {

                            }

                        }

                    }
                    //标注数据处理结束

                    //处理音视频时长数据
                    //查询音频时长
                    $sql = "select duration from bf_material_video where file_id = $id";
                    $videodata = $this->fetch($sql);
                    if (isset($videodata['duration'])) $attrdata['duration'] = $videodata['duration'];
                    //查询视频时长
                    $sql = "select duration from bf_material_audio where file_id = $id";
                    $audiodata = $this->fetch($sql);
                    if (isset($audiodata['duration'])) $attrdata['duration'] = $audiodata['duration'];
                    //时长处理结束

                    //插入属性表数据
                    $attrdata['rid'] = $rid;
                    $attrdata['appid'] = $this->appid;

                    $a = C::t('pichome_resources_attr')->insert($attrdata);
                    $this->donum += 1;

                } else {
                    //文件总数减1
                   // $this->filenum -= 1;
                }
            }


            //导入百分比
            $percent = floor(($this->donum / $this->filenum) * 100);
            $percent = ($percent > 100) ? 100 : $percent;
            $state = ($percent >= 100) ? 3 : 2;
            if ($state == 3) {
                $lastid = 0;
                $percent = 0;
                $this->donum = 0;
            }
            //记录导入起始位置，以备中断后从此处,更改导入状态
            C::t('pichome_vapp')->update($this->appid, array('percent' => $percent, 'donum' => $this->donum, 'state' => $state, 'filenum' => $this->filenum));

        }
        if ($state == 2) {
            $lastid = $lastid + 1;
            C::t('pichome_vapp')->update($this->appid, array('lastid' => $lastid));
        }

        return array('success' => true);
    }
    public function dec2hex($number)
    {

        $i = 0;
        $hex = array();
        while($i < 8) {
            if($number == 0) {
                array_push($hex, '0');
            }
            else {

                array_push($hex, dechex(bcmod($number, '16')));

                $number = bcdiv($number, '16', 0);

            }
            $i++;
        }

        krsort($hex);

        return implode($hex);

    }
    public function export($force = false)
    {
        //开始页数
        if ($this->lastid) {
            $start = $this->lastid;
        } else $start = 1;
        $lastid = $start;
        $start = ($start - 1) * $this->onceexportnum;
        $sql = "select f.*,m.w,m.h,m.is_recycle,m.thumb_tid,mu.comments_detail,mu.note,mu.score,mu.origin from bf_file f
         left join  bf_material m on m.file_id = f.id 
         left join bf_material_userdata mu on mu.file_id=f.id
         where 1 limit $start,$this->onceexportnum";
        $datas = $this->fetch_all($sql);

        foreach ($datas as $v) {
            if(dzz_process::getlocked($this->processname)) exit('vapp isdeleted');
            //查询文件是否在回收站
            $id = $v['id'];//文件id
            $rid = md5($this->appid . $id);

            //如果文件在回收站
            if ($v['is_recycle'] > 0) {
                //如果已经有数据,标记为已删除
                if (DB::fetch_first("select count(rid) from %t where rid = %s", array('pichome_resources', $rid))) {
                    C::t('pichome_resources')->update($rid, array('isdelete' => 1));
                }
                //文件总数减1
                $this->filenum -= 1;
            } else {
                //获取文件后缀
                $ext = strtolower(substr(strrchr($v['name'], '.'), 1));
                //获取文件类型
                $type = getTypeByExt($ext);

                //出入主表数据
                $setarr = [
                    'rid' => $rid,
                    'uid' => $this->uid,
                    'username' => $this->username,
                    'appid' => $this->appid,
                    'ext' => $ext,
                    'type' => $type,
                    'name' => $v['name'],
                    'mtime' => ($v['mtime']) ? $v['mtime'] * 1000 : $v['born'] * 1000,
                    'dateline' => $v['ctime'] ? $v['ctime'] * 1000 : $v['born'] * 1000,
                    'btime' => $v['born'] ? $v['born'] * 1000 : $v['mtime'] * 1000,
                    'size' => $v['file_size'],
                    'width' => $v['w'],
                    'height' => $v['h'],
                    'grade' => $v['score'],
                    'apptype' => 2,
                    'hasthumb' => $v['thumb_tid'] ? 1 : 0,
                    'lastdate' => strtotime('now')
                ];
                //处理目录数据
                if ($v['pid']) {
                    $folderdata = $this->getFolderfid($v['pid']);
                }
                //如果为新数据，写入默认权限
                if (!DB::result_first("select count(id) from %t where rid = %s and appid = %s", ['billfish_record', $rid, $this->appid])) {
                    $setarr['level'] = isset($folderdata['level']) ? intval($folderdata['level']) : $this->defaultperm;
                }
                //数据插入主表
                if (C::t('#billfish#billfish_record')->inser_data($v['id'], $setarr)) {
                   /* if ($setarr['hasthumb']) {
                        $thumbdir = dechex($v['id']);
                        if (strlen($thumbdir) < 2) {
                            $thumbdir = str_pad($thumbdir, 2, 0, STR_PAD_LEFT);
                        } elseif (strlen($thumbdir) > 2) {
                            $thumbdir = substr($thumbdir, -2);
                        }
                        $thumbdir = (string)$thumbdir;
                        $pathdir = ($this->iscloud) ? $this->path . '/.bf/.preview/' . $thumbdir . '/' : $this->path . BS . '.bf' . BS . '.preview' . BS . $thumbdir . BS;
                        $savepatdir = str_replace(array(DZZ_ROOT, BS), array('', '/'), $pathdir);
                        $smallfile = $pathdir . $v['id'] . '.small.webp';
                        $thumbrecorddata = [
                            'rid' => $rid,
                            'ext' => $setarr['ext'],
                            'filesize'=>$setarr['size'],
                            'width'=>$setarr['width'],
                            'height'=>$setarr['height']
                        ];
                        if (IO::checkfileexists($smallfile)) {
                            //获取图片信息，以取得宽高
                            $imgdata = @getimagesize($smallfile);
                            $swidth = isset($imgdata[0]) ? $imgdata[0] : 0;
                            $sheight = isset($imgdata[1]) ? $imgdata[1] : 0;
                            $thumbrecorddata['spath'] =  $savepatdir . $v['id'] . '.small.webp';
                            $thumbrecorddata['sstatus'] =  1;
                            $thumbrecorddata['swidth'] =  $swidth;
                            $thumbrecorddata['sheight'] =  $sheight;
                        }
                        if (in_array($setarr['ext'], explode(',', getglobal('config/pichomespecialimgext')))) {
                            $hdfile = $pathdir . $v['id'] . '.hd.webp';
                            if (IO::checkfileexists($hdfile)) {
                                $thumbrecorddata = [
                                    'opath' => $savepatdir . $v['id'] . '.hd.webp',
                                ];
                            }
                        }
                        C::t('thumb_record')->insert_data($thumbrecorddata);

                    }*/
                    //定义属性表变量
                    $attrdata = [];
                    $attrdata['desc'] = $v['note'];
                    $attrdata['link'] = $v['origin'];
                    //将名字记入搜索字段
                    $attrdata['searchval'] = $setarr['name'] . getstr($attrdata['desc'],255) . $attrdata['link'];
                    //处理目录数据
                    if ($folderdata) {
                        $fid = $folderdata['fid'];
                        $folderarr = [
                            'fid' => $fid,
                            'appid' => $this->appid,
                            'rid' => $rid
                        ];
                        C::t('pichome_folderresources')->insert($folderarr);
                        $attrdata['path'] = $folderdata['dirpath'] . BS . $setarr['name'];
                        C::t('pichome_resources')->update($rid, ['fids' => $fid]);
                    } else {
                        $attrdata['path'] = $setarr['name'];
                        DB::delete('pichome_folderresources', ['rid' => $rid]);
                        C::t('pichome_resources')->update($rid, ['level' => $this->defaultperm, 'fids' => '']);
                    }

                    //目录数据处理完成
                    $attrdata['path'] = ($this->iscloud) ? $attrdata['path'] : $this->getFileRealFileName($this->path, $attrdata['path']);
                    //转码路径 记入属性表
                    //$p = new Encode_Core();
                    //$this->charset = $p->get_encoding($attrdata['path']);
                    //if (CHARSET != $this->charset) $attrdata['path'] = diconv($attrdata['path'],CHARSET, $this->charset);;

                    //标签数据开始

                    //查询文件标签id

                    $sql = "select tag_id from bf_tag_join_file where file_id = $id";//耗时最多
                    $tiddata = $this->fetch_all($sql);

                    $tids = [];
                    foreach ($tiddata as $val) {
                        $tids[] = $val['tag_id'];
                    }

                    if (!empty($tids)) {
                        $tidstr = dimplode($tids);
                        //查询标签分类数据
                        $sql = "select jg.gid,g.name from bf_tag_join_group jg 
                            left join bf_tag_group g on g.id = jg.gid 
                            where jg.tag_id in($tidstr) group by jg.gid";
                        $taggroupdata = $this->fetch_all($sql);
                        $relationgroupdata = [];
                        //插入标签分类关系表及pichome分类返回原分类id对应pichome标签分类id
                        foreach ($taggroupdata as $val) {
                            $tmpreturn = C::t('#billfish#billfish_taggrouprecord')->insert_data($val, $this->appid);
                            $relationgroupdata[$tmpreturn['bcid']] = $tmpreturn['cid'];
                        }
                        //处理标签表数据
                        //查询标签名称,id 插入标签对照表 返回原标签id对应pichome标签id 将标签加入searchval
                        $sql = " select t.id,t.name,j.gid from bf_tag t 
                            left join  bf_tag_join_group j on j.tag_id = t.id where t.id in($tidstr)";
                        $tagdata = $this->fetch_all($sql);
                        $tagrelativedata = [];
                        $taggroups = [];
                        foreach ($tagdata as $val) {
                            $tagsetarr = ['name' => $val['name'], 'lid' => $val['id']];
                            $tmptagrelativedata = C::t('#billfish#billfish_tagrecord')->insert_data($tagsetarr, $this->appid);
                            $tagrelativedata[$tmptagrelativedata['lid']] = $tmptagrelativedata['tid'];
                            $attrdata['searchval'] .= $val['name'];
                            if ($val['gid']) $taggroups[] = ['gid' => $val['gid'], 'lid' => $val['id']];
                        }

                        //处理标签与分类关系数据 查询原标签分类和标签id 插入pichome标签分类对应标签id
                        //$sql = "select gid,lid from tag_join_group where lid in($tidstr)";
                        //$taggroups = $this->fetch_all($sql);
                        foreach ($taggroups as $val) {
                            $tagrelarr = [
                                'tid' => $tagrelativedata[$val['lid']],
                                'cid' => $relationgroupdata[$val['gid']],
                                'appid' => $this->appid,
                            ];
                            C::t('pichome_tagrelation')->insert($tagrelarr);
                        }
                        //处理标签文件关系数据
                        $inserttids = $ftids = array_values($tagrelativedata);
                        //查询pichome是否有标签数据
                        $oattrtag = DB::result_first("select tag from %t where rid = %s", array('pichome_resources_attr', $rid));
                        if ($oattrtag) {
                            $ottids = explode(',', $oattrtag);
                            //取得删除的标签
                            $deltids = array_diff($ottids,$ftids);
                            if (!empty($deltids)) C::t('pichome_resourcestag')->delete_by_ridtid($rid, $deltids);
                            //取得插入的标签
                            $inserttids  = array_diff($ftids,$ottids);
                        }
                        //插入标签关系表
                        foreach ($inserttids as $val) {
                            $tagresourcesattr = ['tid' => $val, 'rid' => $rid, 'appid' => $this->appid];
                            C::t('pichome_resourcestag')->insert($tagresourcesattr);
                        }
                        //更新属性表标签数据
                        $attrdata['tag'] = implode(',', $ftids);
                    }


                    //标签数据结束

                    //开始处理颜色数据
                    //查询颜色数据
                    $sql = "select * from bf_material_color where file_id = $id";
                    $bcolordata = $this->fetch_all($sql);
                    //删除原颜色数据
                    DB::delete('pichome_palette', array('rid' => $rid));
                    $intcolorsarr = $palettenums = [];
                    foreach ($bcolordata as $val) {
                        $color = new \Color($val['color']);
                        //获取颜色整型值
                        $intcolor = $color->toInt();
                        $intcolorsarr[] = $intcolor;
                        $palettesnum[] = $p = $this->getPaletteNumber($intcolor);
                        $colorarr = ['rid' => $rid,
                            'color' => $val['color'],
                            'weight' => $val['percent'],
                            'r' => $val['r'],
                            'g' => $val['g'],
                            'b' => $val['b'],
                            'p' => $p
                        ];
                        C::t('pichome_palette')->insert($colorarr);
                    }
                    $isgray = $this->isgray($intcolorsarr);
                    $attrsetarr = [
                        'gray' => $isgray,
                        'colors' => implode(',', $palettesnum)
                    ];
                    $attrdata = array_merge($attrdata,$attrsetarr);


                    //处理标注数据

                    //删除原标注数据
                    C::t('pichome_comments')->delete_by_rid($rid);
                    if ($v['comments_detail']) {
                        $commentdata = json_decode($v['comments_detail'], true);
                        foreach ($commentdata as $commentval) {
                            $tcommentval['id'] = random(13) . $this->appid;
                            $tentval['appid'] = $this->appid;
                            $tcommentval['rid'] = $rid;
                            $tcommentval['x'] = number_format($commentval['x'], 2);
                            $tcommentval['y'] = number_format($commentval['y'], 2);
                            $tcommentval['width'] = number_format($commentval['cx'], 2);
                            $tcommentval['height'] = number_format($commentval['cy'], 2);
                            $tcommentval['annotation'] = $commentval['comment'];
                            try {
                                C::t('pichome_comments')->insert($tcommentval);
                                $setarr['searchval'] .= $tcommentval['annotation'];
                            } catch (Exception $e) {

                            }

                        }

                    }
                    //标注数据处理结束

                    //处理音视频时长数据
                    //查询音频时长
                    $sql = "select duration from bf_material_video where file_id = $id";
                    $videodata = $this->fetch($sql);
                    if (isset($videodata['duration'])) $attrdata['duration'] = $videodata['duration'];
                    //查询视频时长
                    $sql = "select duration from bf_material_audio where file_id = $id";
                    $audiodata = $this->fetch($sql);
                    if (isset($audiodata['duration'])) $attrdata['duration'] = $audiodata['duration'];
                    //时长处理结束

                    //插入属性表数据
                    $attrdata['rid'] = $rid;
                    $attrdata['appid'] = $this->appid;
                    C::t('pichome_resources_attr')->insert($attrdata);


                    $this->donum += 1;

                } else {
                    //文件总数减1
                    //$this->filenum -= 1;
                }
            }


            //导入百分比
            $percent = floor(($this->donum / $this->filenum) * 100);

            $percent = ($percent > 100) ? 100 : $percent;
            $state = ($percent >= 100) ? 3 : 2;
            if ($state == 3) {
                $lastid = 0;
                $percent = 0;
                $this->donum = 0;
            }
            //记录导入起始位置，以备中断后从此处,更改导入状态
            C::t('pichome_vapp')->update($this->appid, array('percent' => $percent, 'donum' => $this->donum, 'state' => $state, 'filenum' => $this->filenum));

        }
        if ($state == 2) {
            $lastid = $lastid + 1;
            C::t('pichome_vapp')->update($this->appid, array('lastid' => $lastid));
        }

        return array('success' => true);
    }

    public function getPaletteNumber($colors, $palette = array())
    {

        if (empty($palette)) $palette = $this->palette;
        $arr = array();

        if (is_array($colors)) {
            $isarray = 1;
        } else {
            $colors = (array)$colors;
            $isarray = 0;
        }

        foreach ($colors as $color) {
            $bestColor = 0x000000;
            $bestDiff = PHP_INT_MAX;
            $color = new Color($color);
            foreach ($palette as $key => $wlColor) {
                // calculate difference (don't sqrt)
                $diff = $color->getDiff($wlColor);
                // see if we got a new best
                if ($diff < $bestDiff) {
                    $bestDiff = $diff;
                    $bestColor = $wlColor;
                }
            }
            unset($color);
            $arr[] = array_search($bestColor, $palette);
        }
        return $isarray ? $arr : $arr[0];
    }

    //判断图片纯色
    public function isgray($colors)
    {
        $i = 0;
        if (count($colors) < 1) return 0;
        foreach ($colors as $color) {
            $color = new Color($color);
            $rgb = $color->toRGB();
            unset($color);
            if (abs($rgb[0] - $rgb[1]) < 10 && abs($rgb[2] - $rgb[1]) < 10) {
                $i++;
            }
            unset($rgb);
        }
        if ($i == count($colors)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function oldexport($force = false)
    {
        //开始页数
        if ($this->lastid) {
            $start = $this->lastid;
        } else $start = 1;
        $lastid = $start;
        $start = ($start - 1) * $this->onceexportnum;
        $sql = "select s.*,p.fid,p.score,p.title,p.origin,p.note,p.action from source s 
         left join res_prop p on p.iid = s.id 
         where 1 limit $start,$this->onceexportnum";
        $datas = $this->fetch_all($sql);

        foreach ($datas as $v) {
            if(dzz_process::getlocked($this->processname)) exit('vapp isdeleted');
            //查询文件是否在回收站
            $id = $v['id'];//文件id
            $rid = md5($this->appid . $id);

            //如果文件在回收站
            if ($v['action'] > 0) {
                //如果已经有数据,标记为已删除
                if (DB::fetch_first("select count(rid) from %t where rid = %s", array('pichome_resources', $rid))) {
                    C::t('pichome_resources')->update($rid, array('isdelete' => 1));
                }
                //文件总数减1
                $this->filenum -= 1;
            } else {
                //获取文件后缀
                $ext = strtolower(substr(strrchr($v['name'], '.'), 1));
                //获取文件类型
                $type = getTypeByExt($ext);

                //入主表数据
                $setarr = [
                    'rid' => $rid,
                    'uid' => $this->uid,
                    'username' => $this->username,
                    'appid' => $this->appid,
                    'ext' => $ext,
                    'type' => $type,
                    'name' => $v['name'],
                    'mtime' => ($v['born']) ? $v['born'] * 1000 : $v['lastw'] * 1000,
                    'dateline' => $v['lastw'] * 1000,
                    'btime' => TIMESTAMP * 1000,
                    'size' => $v['size'],
                    'width' => $v['width'],
                    'height' => $v['height'],
                    'grade' => $v['score'],
                    'apptype' => 2,
                    'hasthumb' => $v['thumb'] ? 1 : 0,
                    'thumb' => $v['thumb'],
                    'lastdate' => strtotime('now')
                ];
                $folderdata = [];
                if ($v['fid']) {
                    $folderdata = $this->getFolderfid($v['fid']);


                }
                //如果为新数据，写入默认权限
                if (!DB::result_first("select count(id) from %t where rid = %s and appid = %s", ['billfish_record', $rid, $this->appid])) {
                    $setarr['level'] = isset($folderdata['level']) ? intval($folderdata['level']) : $this->defaultperm;
                }
                //数据插入主表
                if (C::t('#billfish#billfish_record')->inser_data($v['id'], $setarr)) {
                    //定义属性表变量
                    $attrdata = [];
                    $attrdata['desc'] = $v['note'];
                    $attrdata['link'] = $v['origin'];
                    //将名字记入搜索字段
                    $attrdata['searchval'] = $setarr['name'] . getstr($attrdata['desc'],255) . $attrdata['link'];
                    //处理目录数据
                    if ($folderdata) {
                        //处理目录数据
                        $fid = $folderdata['fid'];
                        $folderarr = [
                            'fid' => $fid,
                            'appid' => $this->appid,
                            'rid' => $rid
                        ];
                        C::t('pichome_folderresources')->insert($folderarr);

                        $attrdata['path'] = $folderdata['dirpath'] . BS . $setarr['name'];
                        C::t('pichome_resources')->update($rid, ['fids' => $fid]);
                    } else {
                        $attrdata['path'] = $setarr['name'];
                        DB::delete('pichome_folderresources', ['rid' => $rid]);
                        C::t('pichome_resources')->update($rid, ['level' => $this->defaultperm, 'fids' => '']);
                    }
                    //目录数据处理完成
                    $attrdata['path'] = ($this->iscloud) ? $attrdata['path'] : $this->getFileRealFileName($this->path, $attrdata['path']);
                    //转码路径 记入属性表
                    //$p = new Encode_Core();
                    //$this->charset = $p->get_encoding($attrdata['path']);
                    //if (CHARSET != $this->charset) $attrdata['path'] = diconv($attrdata['path'],CHARSET, $this->charset);;

                    //标签数据开始

                    //查询文件标签id

                    $sql = "select lid from res_join_tag where iid = $id";//耗时最多
                    $tiddata = $this->fetch_all($sql);

                    $tids = [];
                    foreach ($tiddata as $val) {
                        $tids[] = $val['lid'];
                    }
                    if (!empty($tids)) {
                        $tidstr = dimplode($tids);
                        //查询标签分类数据
                        $sql = "select jg.gid,g.name from tag_join_group jg 
                            left join taggrp g on g.id = jg.gid 
                            where jg.lid in($tidstr) group by jg.gid";
                        $taggroupdata = $this->fetch_all($sql);
                        $relationgroupdata = [];
                        //插入标签分类关系表及pichome分类返回原分类id对应pichome标签分类id
                        foreach ($taggroupdata as $val) {
                            $tmpreturn = C::t('#billfish#billfish_taggrouprecord')->insert_data($val, $this->appid);
                            $relationgroupdata[$tmpreturn['bcid']] = $tmpreturn['cid'];
                        }
                        //处理标签表数据
                        //查询标签名称,id 插入标签对照表 返回原标签id对应pichome标签id 将标签加入searchval
                        $sql = " select t.id,t.name,j.gid from tag t 
                            left join  tag_join_group j on j.lid = t.id where t.id in($tidstr)";
                        $tagdata = $this->fetch_all($sql);
                        $tagrelativedata = [];
                        $taggroups = [];
                        foreach ($tagdata as $val) {
                            $tagsetarr = ['name' => $val['name'], 'lid' => $val['id']];
                            $tmptagrelativedata = C::t('#billfish#billfish_tagrecord')->insert_data($tagsetarr, $this->appid);
                            $tagrelativedata[$tmptagrelativedata['lid']] = $tmptagrelativedata['tid'];
                            $attrdata['searchval'] .= $val['name'];
                            if ($val['gid']) $taggroups[] = ['gid' => $val['gid'], 'lid' => $val['id']];
                        }

                        //处理标签与分类关系数据 查询原标签分类和标签id 插入pichome标签分类对应标签id
                        //$sql = "select gid,lid from tag_join_group where lid in($tidstr)";
                        //$taggroups = $this->fetch_all($sql);
                        foreach ($taggroups as $val) {
                            $tagrelarr = [
                                'tid' => $tagrelativedata[$val['lid']],
                                'cid' => $relationgroupdata[$val['gid']],
                                'appid' => $this->appid,
                            ];
                            C::t('pichome_tagrelation')->insert($tagrelarr);
                        }
                        //处理标签文件关系数据
                        $inserttids = $ftids = array_values($tagrelativedata);
                        //查询pichome是否有标签数据
                        $oattrtag = DB::result_first("select tag from %t where rid = %s", array('pichome_resources_attr', $rid));
                        if ($oattrtag) {
                            $ottids = explode(',', $oattrtag);
                            //取得删除的标签
                            $deltids = array_diff($ottids,$ftids);
                            if (!empty($deltids)) C::t('pichome_resourcestag')->delete_by_ridtid($rid, $deltids);
                            //取得插入的标签
                            $inserttids  = array_diff($ftids,$ottids);
                        }
                        //插入标签关系表
                        foreach ($inserttids as $val) {
                            $tagresourcesattr = ['tid' => $val, 'rid' => $rid, 'appid' => $this->appid];
                            C::t('pichome_resourcestag')->insert($tagresourcesattr);
                        }
                        //更新属性表标签数据
                        $attrdata['tag'] = implode(',', $ftids);
                    }
                    //标签数据结束

                    //开始处理颜色数据
                    //查询颜色数据
                    $sql = "select * from colour where iid = $id";
                    $bcolordata = $this->fetch_all($sql);

                    //删除原颜色数据
                    DB::delete('pichome_palette', array('rid' => $rid));
                    $intcolorsarr = $palettenums = [];
                    foreach ($bcolordata as $val) {
                        $color = new \Color($val['bf_clr']);
                        //获取颜色整型值
                        $intcolor = $color->toInt();
                        $intcolorsarr[] = $intcolor;
                        $palettesnum[] = $p = $this->getPaletteNumber($intcolor);
                        $colorarr = ['rid' => $rid,
                            'color' => $val['bf_clr'],
                            'weight' => $val['percent'],
                            'r' => $val['r'],
                            'g' => $val['g'],
                            'b' => $val['b'],
                            'p' => $p
                        ];
                        C::t('pichome_palette')->insert($colorarr);
                    }
                    $isgray = $this->isgray($intcolorsarr);
                    $attrsetarr = [
                        'gray' => $isgray,
                        'colors' => implode(',', $palettesnum)
                    ];
                   $attrdata = array_merge($attrdata,$attrsetarr);


                    //处理标注数据
                    $sql = "select * from comment where iid = $id";
                    $commentdata = $this->fetch_all($sql);
                    //删除原标注数据
                    C::t('pichome_comments')->delete_by_rid($rid);
                    if ($commentdata) {
                        foreach ($commentdata as $commentval) {
                            $tcommentval['id'] = random(13) . $this->appid;
                            $tentval['appid'] = $this->appid;
                            $tcommentval['rid'] = $rid;
                            $tcommentval['x'] = number_format($commentval['x'], 2);
                            $tcommentval['y'] = number_format($commentval['y'], 2);
                            $tcommentval['width'] = number_format($commentval['cx'], 2);
                            $tcommentval['height'] = number_format($commentval['cy'], 2);
                            $tcommentval['annotation'] = $commentval['comment'];
                            try {
                                C::t('pichome_comments')->insert($tcommentval);
                                $setarr['searchval'] .= $tcommentval['annotation'];
                            } catch (Exception $e) {

                            }

                        }

                    }
                    //标注数据处理结束

                    //处理音视频时长数据
                    //查询音频时长
                    $sql = "select duration from video where iid = $id";
                    $videodata = $this->fetch($sql);
                    if (isset($videodata['duration'])) $attrdata['duration'] = $videodata['duration'];
                    //查询视频时长
                    $sql = "select duration from audio where iid = $id";
                    $audiodata = $this->fetch($sql);
                    if (isset($audiodata['duration'])) $attrdata['duration'] = $audiodata['duration'];
                    //时长处理结束

                    //插入属性表数据
                    $attrdata['rid'] = $rid;
                    $attrdata['appid'] = $this->appid;
                    C::t('pichome_resources_attr')->insert($attrdata);


                    //颜色数据处理结束
                    $this->donum += 1;

                } else {
                    //文件总数减1
                   // $this->filenum -= 1;
                }
            }


            //导入百分比
            $percent = floor(($this->donum / $this->filenum) * 100);
            $percent = ($percent > 100) ? 100 : $percent;
            $state = ($percent >= 100) ? 3 : 2;
            if ($state == 3) {
                $lastid = 0;
                $percent = 0;
                $this->donum = 0;
            }

            //记录导入起始位置，以备中断后从此处,更改导入状态
            C::t('pichome_vapp')->update($this->appid, array('percent' => $percent, 'donum' => $this->donum, 'state' => $state, 'filenum' => $this->filenum));
        }

        $lastid = $lastid + 1;
        C::t('pichome_vapp')->update($this->appid, array('lastid' => $lastid));
        return array('success' => true);

    }

    public function getFolderfid($bfid, $dirpath = '')
    {
        $parentfolderdata = [];
        if ($this->version < 30) {
            $sql = "select * from folder where id = $bfid";
        } else {
            $sql = "select * from bf_folder where id = $bfid";
        }

        $folderdata = $this->fetch($sql);
        $dirpath = $folderdata['name'] . ($dirpath ? BS . $dirpath : '');
        if ($folderdata['pid']) {
            $parentfolderdata = $this->getFolderfid($folderdata['pid'], $dirpath);

        }
        if ($parentfolderdata['dirpath']) $dirpath = $parentfolderdata['dirpath'];
        $setarr = [
            'pfid' => isset($parentfolderdata['fid']) ? $parentfolderdata['fid'] : '',
            'fname' => $folderdata['name'],
            'appid' => $this->appid,
            'disp' => ($this->version < 30) ? $folderdata['seq'] : round($folderdata['seq'] * 1000000000),

        ];
        $perm = isset($parentfolderdata['level']) ? intval($parentfolderdata['level']) : $this->defaultperm;
        $return = C::t('#billfish#billfish_folderrecord')->insert_data($bfid, $setarr, $perm);
        $return['dirpath'] = $dirpath;
        return $return;
    }

    //校验文件
    public function execCheckFile()
    {
        if ($this->exportstatus == 3) {
            $total = DB::result_first("select count(rid) from %t where appid = %s ", array('pichome_resources', $this->appid));
            //校验文件
            $this->check_file($total);
        }
        return true;
    }

    public function check_file($total)
    {
        if ($this->lastid < 1) $this->lastid = 1;
        $limitsql = ($this->lastid - 1) * $this->checklimit . ',' . $this->checklimit;

        $delrids = [];
        $data = DB::fetch_all("select rid,name from %t where appid = %s order by lastdate asc limit $limitsql ", array('pichome_resources', $this->appid));
        if (empty($data)) {
            //校验完成后更新目录文件数
            foreach (DB::fetch_all("select count(rf.id) as num,f.fid  from %t f left join %t rf on rf.fid=f.fid where f.appid = %s group by f.fid", array('pichome_folder', 'pichome_folderresources', $this->appid)) as $v) {
                C::t('pichome_folder')->update($v['fid'], array('filenum' => $v['num']));
            }

            //检查不存在的目录删除
            $foldertotal = DB::result_first("select count(id) from %t where appid = %s", array('billfish_folderrecord', $this->appid));
            $this->check_notexists_folder($foldertotal);
            //检查不存在的标签删除
            $tagtotal = DB::result_first("select count(id) from %t where appid = %s", array('billfish_tagrecord', $this->appid));
            $this->check_notexists_tag($tagtotal);
            if ($this->version < 30) {
                //删除创建的索引
                $sql = 'DROP INDEX res_join_tag_iid';
                $this->db->query($sql);
            } else {
                $sql = 'DROP INDEX res_join_tag_id';
                $this->db->query($sql);
            }


            $hascatnum = DB::result_first("SELECT count(DISTINCT rid) FROM %t where appid = %s", array('pichome_folderresources', $this->appid));
            $nosubfilenum = $total - $hascatnum;
            C::t('pichome_vapp')->update($this->appid, array('percent' => 0, 'state' => 4, 'lastid' => 0, 'donum' => 0, 'filenum' => $total, 'dateline' => strtotime('now'), 'nosubfilenum' => $nosubfilenum));
            if ($this->iscloud) {
                @unlink($this->readtxt . 'billfishexport' . md5($this->path) . '.db');
            }
            return true;
        }
        foreach ($data as $v) {
            if(dzz_process::getlocked($this->processname)) exit('vapp isdeleted');
            $rid = $v['rid'];
            $iid = DB::result_first("select bid from %t where rid = %s and appid = %s", array('billfish_record', $rid, $this->appid));
            if (!$iid) {
                $delrids[] = $rid;
            } else {
                if ($this->version < 30) {
                    $sql = "select count(s.id) as num from source s left join res_prop rp on s.id = rp.iid where rp.action =0 and s.id = $iid";
                } elseif($this->version == 30) {
                    //查询billfish中是否有该数据
                    $sql = "select count(f.id) as num from bf_file f left join bf_material m on f.id = m.file_id where m.is_recycle =0 and  f.id = $iid";
                }else{
                    $sql = "select count(f.id) as num from bf_file f left join bf_material_v2 m on f.id = m.file_id where m.is_recycle =0 and  f.id = $iid";
                }
                $numdata = $this->fetch($sql);
                if (!isset($numdata['num']) || !$numdata['num']) {
                    $delrids[] = $rid;
                }
            }

        }
        if (!empty($delrids)) {
            $this->filenum = $this->filenum - count($delrids);
            //如果有需要删除的，删除后，则重新查询上一页数据
            C::t('pichome_resources')->delete_by_rid($delrids);
            if ($this->lastid == 1) {
                $percent = round(($this->checklimit / $total) * 100);
            } else {
                $percent = round((($this->lastid - 1) * $this->checklimit / $total) * 100);
            }
            C::t('pichome_vapp')->update($this->appid, array('lastid' => $this->lastid, 'percent' => $percent, 'state' => 3, 'filenum' => $this->filenum));
        } else {
            if ($this->lastid == 1) {
                $percent = round(($this->checklimit / $total) * 100);
            } else {
                $percent = round((($this->lastid - 1) * $this->checklimit / $total) * 100);
            }
            $percent = ($percent > 100) ? 100 : $percent;
            C::t('pichome_vapp')->update($this->appid, array('lastid' => $this->lastid + 1, 'percent' => $percent, 'state' => 3));
        }

    }

    //检查目录数据
    public function check_notexists_folder($total)
    {
        $tmpkey = 'pichomecheckfolder' . $this->appid;
        $folderstart = C::t('cache')->fetch($tmpkey);
        if (!$folderstart) {
            $folderstart = 0;

        }
        if ($folderstart < $total) {
            $bfids = [];
            //检查不存在的目录删除
            foreach (DB::fetch_all("select bfr.bfid as bfid from %t f left join %t bfr on bfr.fid=f.fid where f.appid = %s limit $folderstart,100", array('pichome_folder', 'billfish_folderrecord', $this->appid)) as $v) {
                $bfids[] = $v['bfid'];
            }
            if (empty($bfids)) {
                C::t('cache')->delete($tmpkey);
                return true;
            }

            $bfidstr = dimplode($bfids);
            if ($this->version < 30) {
                $sql = "select id from folder where id in($bfidstr)";
            } else {
                $sql = "select id from bf_folder where id in($bfidstr)";
            }
            //查询不存的目录

            $bfolder = $this->fetch_all($sql);
            $blfids = [];
            foreach ($bfolder as $val) {
                $blfids[] = $val['id'];
            }
            $delfids = array_diff($bfids, $blfids);
            if (!empty($delfids)) C::t('#billfish#billfish_folderrecord')->delete_by_bfid($delfids, $this->appid);

            $folderstart += 100;
            $setarr = ['cachekey' => $tmpkey, 'cachevalue' => $folderstart, 'dateline' => time()];
            C::t('cache')->insert($setarr);
            $this->check_notexists_folder($total);
        } else {
            C::t('cache')->delete($tmpkey);
            return true;
        }


    }

    //检查标签对照表数据
    public function check_notexists_tag($total)
    {
        $tmpkey = 'pichomechecktag' . $this->appid;
        $tagstart = C::t('cache')->fetch($tmpkey);
        if (!$tagstart) {
            $tagstart = 0;

        }
        if ($tagstart < $total) {
            $lids = [];
            foreach (DB::fetch_all("select lid from %t where appid = %s limit $tagstart,100 ", array('billfish_tagrecord', $this->appid)) as $v) {
                $lids[] = $v['lid'];
            }
            if (empty($lids)) {
                C::t('cache')->delete($tmpkey);
                return true;
            }
            $lidstr = dimplode(',', $lids);
            if ($this->version < 30) {
                $sql = "select id from tag where id in($lidstr)";
            } else $sql = "select id from bf_tag where id in($lidstr)";
            $blids = [];
            foreach ($this->fetch_all($sql) as $v) {
                $blids[] = $v['id'];
            }
            $deblids = array_diff($lids, $blids);
            if (!empty($deblids)) DB::delete('billfish_tagrecord', 'lid in(' . dimplode($deblids) . ')');
            $tagstart += 100;
            $setarr = ['cachekey' => $tmpkey, 'cachevalue' => $tagstart, 'dateline' => time()];
            C::t('cache')->insert($setarr);
            $this->check_notexists_tag($total);
        } else {
            C::t('cache')->delete($tmpkey);
            return true;
        }


    }
}