<?php
if (!defined('IN_OAOOA') ||  !defined('PICHOME_LIENCE')) {
    exit('Access Denied');
}
$overt = getglobal('setting/overt');
if(!$overt && !$overt = C::t('setting')->fetch('overt')){
    Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
}
updatesession();
$operation = isset($_GET['operation']) ? trim($_GET['operation']) : '';
$clid = isset($_GET['clid']) ? intval($_GET['clid']):0;
$isall = isset($_GET['isall']) ? intval($_GET['isall']):0;
$isshare = 0;
if(!$clid && !$isall){
    $clid = dzzdecode($_GET['shareid'],'',0);
    if($clid) $isshare = 1;
}else{
    Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
    ($_G['adminid'] == 1 || !isset($_G['config']['pichomeclosecollect']) || !$_G['config']['pichomeclosecollect']) ? '': exit('Access Denied');
}
global $_G;
if ($operation == 'searchmenu_num') {
    if ($clid) {
        $perm = C::t('pichome_collectuser')->get_perm_by_clid($clid);
        if ($perm < 1) exit(json_encode(array()));
    }
    $hassub = isset($_GET['hassub']) ? intval($_GET['hassub']) : 0;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    //是否获取标签数量
    $hasnum = isset($_GET['hasnum']) ? intval($_GET['hasnum']) : 0;
    $prepage = 15;
    $pagelimit = 'limit ' . ($page - 1) * $prepage . ',' . $prepage;
   
    $tagkeyword = isset($_GET['tagkeyword']) ? htmlspecialchars($_GET['tagkeyword']) : '';
    $skey = isset($_GET['skey']) ? trim($_GET['skey']) : '';

    if ($skey == 'tag') {
        $sql = "  %t cl  left join  %t rt on cl.rid=rt.rid left join %t r on rt.rid=r.rid ";
        $params = ['pichome_collectlist','pichome_resourcestag', 'pichome_resources'];
    } else {
        $sql = "  %t cl left join %t r on cl.rid=r.rid ";
        $params = ['pichome_collectlist','pichome_resources'];
    }
    $wheresql = " r.isdelete = 0 and r.level <= %d ";
    $ulevel = $_G['uid'] ? $_G['pichomelevel'] : 0;
    $para[] = $ulevel;
    $cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
    $hassub = isset($_GET['hassub']) ? intval($_GET['hassub']) : 0;
    $uid = getglobal('uid');
    if ($clid) {
        $wheresql .= ' and cl.clid = %d ';
        $para[] = $clid;
    } else {
        $clids = [];
        foreach(DB::fetch_all("select clid from %t where uid = %d and perm > %d",array('pichome_collectuser',$uid,0)) as $v){
            $clids[] = $v['clid'];
        }
        if(!empty($clids)){
            $wheresql .= ' and cl.uid = %d and cl.clid in(%n)';
            $para[] = $uid;
            $para[] = $clids;
        }else{
            $wheresql .= ' and 0';
        }
    }
    if ($cid && $cid > 0) {
        if ($hassub) {
            $cids = C::t('pichome_collectcat')->fetch_cid_by_pcid($cid);
            $wheresql .= " and cl.cid in(%n) ";
            $para[] = $cids;
        } else {
            $wheresql .= " and cl.cid = %d ";
            $para[] = $cid;
        }
    } elseif ($clid && $cid == -1) {
        $wheresql .= " and cl.cid = %d ";
        $para[] = 0;
    }
    //添加日期
    if (isset($_GET['btime'])) {
        $btime = explode('_', $_GET['btime']);
        $bstart = strtotime($btime[0]);
        $bend = strtotime($btime[1]) + 24 * 60 * 60;
        if ($bstart) {
            $wheresql .= " and r.btime > %d";
            //将时间补足13位
            $para[] = $bstart * 1000;
        }
        if ($bend) {
            $wheresql .= " and r.btime < %d";
            //将时间补足13位
            $para[] = $bend * 1000;
        }
    }
    //修改日期
    if (isset($_GET['dateline'])) {
        $dateline = explode('_', $_GET['dateline']);
        $dstart = strtotime($dateline[0]);
        $dend = strtotime($dateline[1]) + 24 * 60 * 60;
        if ($dstart) {
            $wheresql .= " and r.dateline > %d";
            //将时间补足13位
            $para[] = $dstart * 1000;
        }

        if ($dend) {
            $wheresql .= " and r.dateline < %d";
            //将时间补足13位
            $para[] = $dend * 1000;
        }
    }
    //创建日期
    if (isset($_GET['mtime'])) {
        $mtime = explode('_', $_GET['mtime']);
        $mstart = strtotime($mtime[0]);
        $mend = strtotime($mtime[1]) + 24 * 60 * 60;
        if ($mstart) {
            $wheresql .= " and r.mtime > %d";
            //将时间补足13位
            $para[] = $mstart * 1000;
        }

        if ($mend) {
            $wheresql .= " and r.mtime < %d";
            //将时间补足13位
            $para[] = $mend * 1000;
        }
    }
    //评分条件
    if (isset($_GET['grade'])) {
        $grade = trim($_GET['grade']);
        $grades = explode(',', $grade);
        $wheresql .= " and r.grade in(%n)";
        $para[] = $grades;
    }
    //类型条件
    if (isset($_GET['ext'])) {
        $ext = trim($_GET['ext']);
        $exts = explode(',', $ext);
        $wheresql .= " and r.ext in(%n)";
        $para[] = $exts;
    }

    //时长条件
    if (isset($_GET['duration'])) {
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= " left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        $durationarr = explode('_', $_GET['duration']);
        $dunit = isset($_GET['dunit']) ? trim($_GET['dunit']) : 's';
        if ($durationarr[0]) {
            $wheresql .= " and ra.duration >= %d";
            $para[] = ($dunit == 'm') ? $durationarr[0] * 60 : $durationarr[0];
        }

        if ($durationarr[1]) {
            $wheresql .= " and ra.duration <= %d";
            $para[] = ($dunit == 'm') ? $durationarr[1] * 60 : $durationarr[1];
        }
    }
    //标注条件
    if (isset($_GET['comments'])) {
        $sql .= "  left join %t c on r.rid = c.rid";
        $params[] = 'pichome_comments';
        $comments = intval($_GET['comments']);
        $cval = isset($_GET['cval']) ? trim($_GET['cval']) : '';
        if (!$comments) {
            $wheresql .= " and  isnull(c.annotation) ";
        } else {
            if ($cval) {
                $cvalarr = explode(',', $cval);
                $cvalwhere = [];
                foreach ($cvalarr as $cv) {
                    $cvalwhere[] = " c.annotation like %s";
                    $para[] = '%' . $cv . '%';
                }
                $wheresql .= " and (" . implode(" or ", $cvalwhere) . ")";
            } else {
                $wheresql .= " and  !isnull(c.annotation)";
            }
        }
    }
    //注释条件
    if (isset($_GET['desc'])) {
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= " left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        $desc = intval($_GET['desc']);
        $descval = isset($_GET['descval']) ? trim($_GET['descval']) : '';
        if (!$desc) {
            $wheresql .= " and  (isnull(ra.desc) or ra.desc='') ";
        } else {
            if ($descval) {
                $descvalarr = explode(',', $descval);
                $descvalwhere = [];
                foreach ($descvalarr as $dv) {
                    $descvalwhere[] = "  ra.desc  like %s";
                    $para[] = '%' . $dv . '%';
                }
                $wheresql .= " and (" . implode(" or ", $descvalwhere) . ")";
            } else {
                $wheresql .= " and ra.desc !=''";
            }
        }
    }
    //链接条件
    if (isset($_GET['link'])) {
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= " left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        $link = intval($_GET['link']);
        $linkval = isset($_GET['linkval']) ? trim($_GET['linkval']) : '';
        if (!$link) {
            $wheresql .= " and  (isnull(ra.link) or ra.link='') ";
        } else {
            if ($linkval) {
                $linkvalarr = explode(',', $linkval);
                $linkvalwhere = [];
                foreach ($linkvalarr as $lv) {
                    $linkvalwhere[] = "  ra.link  like %s";
                    $para[] = '%' . $lv . '%';
                }
                $wheresql .= " and (" . implode(" or ", $linkvalwhere) . ")";
            } else {
                $wheresql .= " and  ra.link !='' ";
            }
        }
    }
    //形状条件
    if (isset($_GET['shape'])) {
        $shape = trim($_GET['shape']);
        $shapes = explode(',', $shape);
        $shapewherearr = [];
        foreach ($shapes as $v) {
            switch ($v) {
                case 7://方图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = 100;
                    break;
                case 8://横图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) > %d and  round((r.width / r.height) * 100) < 250';
                    $para[] = 100;
                    break;
                case 5://细长横图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) >= %d';
                    $para[] = 250;
                    break;
                case 6://细长竖图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) <= %d';
                    $para[] = 40;
                    break;
                case 9://竖图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) < %d and round((r.width / r.height) * 100) > %d';
                    $para[] = 100;
                    $para[] = 40;
                    break;
                case 1://4:3
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = (4 / 3) * 100;
                    break;
                case 2://3:4
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = (3 / 4) * 100;
                    break;
                case 3://16:9
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = (16 / 9) * 100;
                    break;
                case 4://9:16
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = (9 / 16) * 100;
                    break;
                /*case 10:
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = ($swidth / $sheight) * 100;
                    break;*/
            }
        }
        if (isset($_GET['shapesize'])) {
            $shapesize = trim($_GET['shapesize']);
            $shapesizes = explode(':', $shapesize);
            $swidth = intval($shapesizes[0]);
            $sheight = intval($shapesizes[1]);
            $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
            $para[] = ($swidth / $sheight) * 100;
        }
        if ($shapewherearr) {
            $wheresql .= " and (" . implode(" or ", $shapewherearr) . ")";
        }
    }

    //尺寸条件
    if (isset($_GET['wsize']) || isset($_GET['hsize'])) {
        $wsizearr = explode('_', $_GET['wsize']);
        $hsizearr = explode('_', $_GET['hsize']);
        if ($wsizearr[0]) {
            $wheresql .= " and r.width >= %d";
            $para[] = intval($wsizearr[0]);
        }
        if ($wsizearr[1]) {
            $wheresql .= " and r.width <= %d";
            $para[] = intval($wsizearr[1]);
        }
        if ($hsizearr[0]) {
            $wheresql .= " and r.height >= %d";
            $para[] = intval($hsizearr[0]);
        }
        if ($hsizearr[1]) {
            $wheresql .= " and r.height <= %d";
            $para[] = intval($hsizearr[1]);
        }
    }

    //大小条件
    if (isset($_GET['size'])) {
        $size = explode('_', $_GET['size']);
        $unit = isset($_GET['unit']) ? intval($_GET['unit']) : 1;
        switch ($unit) {
            case 0://b
                $size[0] = $size[0];
                $size[1] = $size[1];
                break;
            case 1://kb
                $size[0] = $size[0] * 1024;
                $size[1] = $size[1] * 1024;
                break;
            case 2://mb
                $size[0] = $size[0] * 1024 * 1024;
                $size[1] = $size[1] * 1024 * 1024;
                break;
            case 3://gb
                $size[0] = $size[0] * 1024 * 1024 * 1024;
                $size[1] = $size[1] * 1024 * 1024 * 1024;
                break;
        }
        if ($size[0]) {
            $wheresql .= " and r.szie > %d";
            $para[] = $size[0];
        }
        if ($size[1]) {
            $wheresql .= " and r.size < %d";
            $para[] = $size[1];
        }
    }
    $lang = '';
    Hook::listen('lang_parse',$lang,['checklang']);
    if($lang) $wheresql .= " and (r.lang = '".$_G['language']."' or r.lang = 'all' ) ";
    //关键词条件
    $keyword = isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '';
    if ($keyword) {
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= " left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        if($lang && !in_array('lang_search',$params)){
            $sql .= " LEFT JOIN %t lang ON lang.idvalue=r.rid and lang.lang = %s ";
            $params[] = 'lang_search';
            $params[] = $lang;
        }
        $keywords = array();
        $arr1 = explode('+', $keyword);
        foreach ($arr1 as $value1) {
            $value1 = trim($value1);
            $arr2 = explode(' ', $value1);
            $arr3 = array();
            foreach ($arr2 as $value2) {

                $arr3[] = "ra.searchval LIKE %s";
                $para[] = '%' . $value2 . '%';
                if($lang){
                    $arr3[] = "lang.svalue LIKE %s";
                    $para[] = '%' . $value2 . '%';
                }
            }
            $keywords[] = "(" . implode(" OR ", $arr3) . ")";
        }
        if ($keywords) {
            $wheresql .= " and (" . implode(" AND ", $keywords) . ")";
        }
    }
    //颜色条件
    if (isset($_GET['color'])) {
        $persion = isset($_GET['persion']) ? intval($_GET['persion']) : 0;
        $maxColDist = 764.8339663572415;
        $similarity = 50 + (50 / 100) * $persion;
        $color = trim($_GET['color']);
        $rgbcolor = hex2rgb($color);
        $sql .= "  left join %t p on r.rid = p.rid ";
        $params[] = 'pichome_palette';
        $wheresql .= "and round((%d-sqrt((((2+(p.r+%d)/2)/256)*(pow((%d-p.r),2))+(4*pow((%d-p.g),2)) + (((2+(255-(p.r+%d)/2))/256))*(pow((%d-p.b), 2)))))/%d,4)*p.weight >= 85";
        if (!empty($para)) $para = array_merge($para, array($maxColDist, $rgbcolor['r'], $rgbcolor['r'], $rgbcolor['g'], $rgbcolor['r'], $rgbcolor['b'], $maxColDist, $similarity));
        else  $para = array($maxColDist, $rgbcolor['r'], $rgbcolor['r'], $rgbcolor['g'], $rgbcolor['r'], $rgbcolor['b'], $maxColDist, $similarity);
    }
    //标签条件
    if (isset($_GET['tag'])) {
        $tagwherearr = [];
        $tagrelative = isset($_GET['tagrelative']) ? intval($_GET['tagrelative']) : 1;
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= " left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        $tag = trim($_GET['tag']);
        if ($tag == -1) {
            $wheresql .= " and ra.tag =  '' ";
        } else {
            $tagval = explode(',', trim($_GET['tag']));
            if (!empty($tagval)) {
                $seltagdata = [];

                foreach (DB::fetch_all("select tagname,tid from %t where tid in(%n) ", array('pichome_tag', $tagval)) as $tv) {
                    $seltagdata[] = array('tagname' => $tv['tagname'], 'tid' => intval($tv['tid']));

                }

            }
            foreach ($tagval as $v) {
                $tagwherearr[] = " find_in_set(%d,ra.tag)";
                $para[] = $v;
            }
            if ($tagrelative) {
                $wheresql .= " and (" . implode(" or ", $tagwherearr) . ")";
            } else {
                $wheresql .= " and (" . implode(" and ", $tagwherearr) . ")";
            }
        }

    }
    $shapedataarr = array(
        1 => array(
            'start' => 'round((4 / 3) * 100)',
            'end' => '',
            'val' => 1,
            'lablename' => '4:3'
        ),
        2 => array(
            'start' => 'round((3 / 4) * 100)',
            'end' => '',
            'val' => 2,
            'lablename' => '3:4'
        ),
        3 => array(
            'start' => 'round((16 / 9) * 100)',
            'end' => '',
            'val' => 3,
            'lablename' => '16:9'
        ),
        4 => array(
            'start' => 'round((9 / 16) * 100)',
            'end' => '',
            'val' => 4,
            'lablename' => '9:16'
        ),
        5 => array(
            'start' => 250,
            'end' => '',
            'val' => 5,
            'lablename' => lang('elongated_horizontal_chart')
        ),
        6 => array(
            'start' => 0,
            'end' => 40,
            'val' => 6,
            'lablename' => lang('elongated_vertical_view')
        ),
        7 => array(
            'start' => 100,
            'end' => '',
            'val' => 7,
            'lablename' => lang('square_diagram')
        ),
        8 => array(
            'start' => 100,
            'end' => 250,
            'val' => 8,
            'lablename' => lang('horizontal_chart')
        ),
        9 => array(
            'start' => 40,
            'end' => 100,
            'val' => 9,
            'lablename' => lang('portrait_view')
        )

    );
    $timedataarr = array(
        1 => array(
            'start' => strtotime(date("Y-m-d", time())) * 1000,
            'end' => (strtotime(date("Y-m-d", time())) + 24 * 60 * 60) * 1000,
            'val' => 1,
            'label' => lang('filter_range_day'),
        ),
        -1 => array(
            'start' => strtotime(date("Y-m-d", strtotime("-1 day"))) * 1000,
            'end' => (strtotime(date("Y-m-d", time())) + 24 * 60 * 60) * 1000,
            'val' => -1,
            'label' => lang('filter_range_yesterday'),
        ),
        -7 => array(
            'start' => strtotime(date("Y-m-d", strtotime("-7 day"))) * 1000,
            'end' => (strtotime(date("Y-m-d", time())) + 24 * 60 * 60) * 1000,
            'val' => -7,
            'label' => lang('filter_range_week')
        ),
        -30 => array(
            'start' => strtotime(date("Y-m-d", strtotime("-30 day"))) * 1000,
            'end' => (strtotime(date("Y-m-d", time())) + 24 * 60 * 60) * 1000,
            'val' => -30,
            'label' =>  lang('filter_range_month')
        ),
        -90 => array(
            'start' => strtotime(date("Y-m-d", strtotime("-90 day"))) * 1000,
            'end' => (strtotime(date("Y-m-d", time())) + 24 * 60 * 60) * 1000,
            'val' => -90,
            'label' =>  lang('filter_range_month3')
        ),
        -365 => array(
            'start' => strtotime(date("Y-m-d", strtotime("-365 day"))) * 1000,
            'end' => (strtotime(date("Y-m-d", time())) + 24 * 60 * 60) * 1000,
            'val' => -365,
            'label' => lang('filter_range_year')
        ),
    );
    //标签统计
    if ($skey == 'tag') {
        $tagkeyword = isset($_GET['tagkeyword']) ? trim($_GET['tagkeyword']) : '';
        if ($tagkeyword) {
            $sql .= "  left join %t t on t.tid=rt.tid ";
            $params[] = 'pichome_tag';
            $wheresql .= "  and t.tagname LIKE %s ";
            $para[] = '%' . $tagkeyword . '%';
        }
        $tagdata = [];
        //每个标签对应文件个数
        $tdata = [];
        //所有符合条件标签id
        $tids = [];

        if (!$hasnum) {
            $sql .= ' left join %t t1 on t1.tid = rt.tid ';
            $params[] = 'pichome_tag';
            if (!empty($para)) $params = array_merge($params, $para);
            foreach (DB::fetch_all("select distinct rt.tid,t1.tagname from $sql where $wheresql  $pagelimit", $params) as $v) {
                $tagdata[$v['tid']]['tagname'] = $v['tagname'];
            }
        } else {
            $fparams = $params;
            if (!empty($para)) $params = array_merge($params, $para);
            foreach (DB::fetch_all("select distinct rt.tid from $sql where $wheresql  $pagelimit", $params) as $v) {
                $tids[] = $v['tid'];
            }
            $sql .= ' left join %t t1 on t1.tid = rt.tid ';
            $fparams[] = 'pichome_tag';
            $wheresql .= ' and rt.tid in(%n) ';
            $para[] = $tids;
            if (!empty($para)) $fparams = array_merge($fparams, $para);
            foreach (DB::fetch_all("select rt.tid,t1.tagname from $sql where $wheresql", $fparams) as $v) {
                if (!isset($tagdata[$v['tid']])) {
                    $tagdata[$v['tid']]['tagname'] = $v['tagname'];
                    $tagdata[$v['tid']]['num'] = 1;
                } else {
                    $tagdata[$v['tid']]['num'] += 1;
                }
            }

        }
        $tids = array_keys($tagdata);
        $finish = (count($tids) >= 15) ? false : true;

        //最后返回数组
        $data = [];
        //含分类标签数据数组
        $catdata = [];

        //标签不含分类数据
        $alltagdata = $tagdata;
        $data['finish'] = $finish;
        $data['alltagdata'] = $alltagdata;
        $data['tgdata'] = $seltagdata;
    } elseif ($skey == 'shape') {
        if ($hasnum) {
            //形状统计
            $presql = ' case ';
            $prepara = [];
            foreach ($shapedataarr as $sv) {
                if ($sv['start'] && $sv['end'] === '') {
                    $presql .= ' when round((r.width/r.height) * 100) = %i  then %d ';
                    $prepara[] = $sv['start'];
                } else {
                    $presql .= ' when round((r.width/r.height) * 100) > %d ' . (($sv['end']) ? ' and    round((r.width/r.height)*100) <= %d then %d' : ' then %d');
                    $prepara[] = $sv['start'];
                }

                if ($sv['end']) $prepara[] = $sv['end'];
                $prepara[] = $sv['val'];
            }
            if ($presql) {
                $presql .= ' end as %s';
                $prepara[] = 'shapedata';
            }

            if (!empty($para)) $params = array_merge($params, $para);
            if (!empty($prepara)) $shapeparams = array_merge($prepara, $params);

            foreach (DB::fetch_all("select  $presql FROM $sql where $wheresql", $shapeparams) as $value) {
                if (!isset($data[$value['shapedata']]) && $shapedataarr[$value['shapedata']]['val']) {
                    $data[$value['shapedata']]['num'] = 1;
                    $data[$value['shapedata']]['lablename'] = $shapedataarr[$value['shapedata']]['lablename'];
                    $data[$value['shapedata']]['val'] = $shapedataarr[$value['shapedata']]['val'];
                } elseif ($data[$value['shapedata']]['num']) {
                    $data[$value['shapedata']]['num']++;
                }
            }
            //将3:4 9:16 细长竖图归类到竖图
            $data[9]['num'] = ($data[9]['num'] ? $data[9]['num'] : 0) + ($data[2]['num'] ? $data[2]['num'] : 0) + ($data[4]['num'] ? $data[4]['num'] : 0) + ($data[6]['num'] ? $data[6]['num'] : 0);

            if ($data[9]['num']) {
                $data[9]['lablename'] = $shapedataarr[9]['lablename'];
                $data[9]['val'] = $shapedataarr[9]['val'];
            } else {
                unset($data[9]);
            }
            //将4:3 16:9 细长横图图归类到横图
            $data[8]['num'] = ($data[8]['num'] ? $data[8]['num'] : 0) + ($data[1]['num'] ? $data[1]['num'] : 0) + ($data[3]['num'] ? $data[3]['num'] : 0) + ($data[5]['num'] ? $data[5]['num'] : 0);
            if ($data[8]['num']) {
                $data[8]['val'] = $shapedataarr[8]['val'];
                $data[8]['lablename'] = $shapedataarr[8]['lablename'];
            } else {
                unset($data[8]);
            }
        } else {
            $data = $shapedataarr;
        }

    } elseif ($skey == 'grade') {
        //评分统计
        if (!empty($para)) $params = array_merge($params, $para);
        $data = DB::fetch_all("select count(cl.rid) as num,r.grade  from $sql   where $wheresql group by r.grade", $params);
    } elseif ($skey == 'ext') {
        //类型统计
        if (!empty($para)) $params = array_merge($params, $para);

        $data = DB::fetch_all("select count(cl.rid) as num,r.ext from $sql   where $wheresql group by r.ext", $params);

    } elseif ($skey == 'btime') {
        //添加时间
        $presql = ' case ';
        $prepara = [];
        foreach ($timedataarr as $sv) {
            $presql .= ' when r.btime >= %d ' . (($sv['end']) ? ' and  r.btime < %d then %d' : ' then %d');
            $prepara[] = $sv['start'];
            if ($sv['end']) $prepara[] = $sv['end'];
            $prepara[] = $sv['val'];
        }
        if ($presql) {
            $presql .= ' end as %s';
            $prepara[] = 'btimedata';
        }
        if (!empty($para)) $params = array_merge($params, $para);
        if (!empty($prepara)) $params = array_merge($prepara, $params);
        foreach (DB::fetch_all("select  $presql  FROM $sql  where$wheresql", $params) as $value) {
            if (!$value['btimedata']) continue;
            if (!isset($data[$value['btimedata']])) {
                $data[$value['btimedata']]['num'] = 1;
                $data[$value['btimedata']]['val'] = $timedataarr[$value['btimedata']]['val'];
                $data[$value['btimedata']]['label'] = $timedataarr[$value['btimedata']]['label'];
            } else {
                $data[$value['btimedata']]['num']++;
            }
        }
        //将今天昨天归类到最近七天，将最近七天归到最近30天，将近30天归到最近90天，将最近90天归到最近365天
        //将今天昨天归类到最近七天，将最近七天归到最近30天，将近30天归到最近90天，将最近90天归到最近365天
        $data[-7]['num'] = (isset($data[-7]['num']) ? intval($data[-7]['num']) : 0) + (isset($data[1]['num']) ? intval($data[1]['num']) : 0) + (isset($data[-1]['num']) ? intval($data[-1]['num']) : 0);
        if ($data[-7]['num']) $data[-7] = array('num' => $data[-7]['num'], 'val' => $timedataarr[-7]['val'], 'label' => $timedataarr[-7]['label']);
        $data[-30]['num'] = (isset($data[-30]['num']) ? intval($data[-30]['num']) : 0) + (isset($data[-7]['num']) ? intval($data[-7]['num']) : 0);
        if ($data[-30]['num']) $data[-30] = array('num' => $data[-30]['num'], 'val' => $timedataarr[-30]['val'], 'label' => $timedataarr[-30]['label']);
        $data[-90]['num'] = (isset($data[-90]['num']) ? intval($data[-90]['num']) : 0) + (isset($data[-30]['num']) ? intval($data[-30]['num']) : 0);
        if ($data[-90]['num']) $data[-90] = array('num' => $data[-90]['num'], 'val' => $timedataarr[-90]['val'], 'label' => $timedataarr[-90]['label']);
        $data[-365]['num'] = (isset($data[-365]['num']) ? intval($data[-365]['num']) : 0) + (isset($data[-90]['num']) ? intval($data[-90]['num']) : 0);
        if ($data[-365]['num']) $data[-365] = array('num' => $data[-365]['num'], 'val' => $timedataarr[-365]['val'], 'label' => $timedataarr[-365]['label']);
        foreach ($data as $k => $v) {
            if ($v['num'] == 0) unset($data[$k]);
        }
        krsort($data);
    } elseif ($skey == 'mtime') {
        //创建时间
        $presql = ' case ';
        $prepara = [];
        foreach ($timedataarr as $sv) {
            $presql .= ' when r.mtime >= %d ' . (($sv['end']) ? ' and  r.mtime < %d then %d' : ' then %d');
            $prepara[] = $sv['start'];
            if ($sv['end']) $prepara[] = $sv['end'];
            $prepara[] = $sv['val'];
        }
        if ($presql) {
            $presql .= ' end as %s';
            $prepara[] = 'mtimedata';
        }
        if (!empty($para)) $params = array_merge($params, $para);
        if (!empty($prepara)) $params = array_merge($prepara, $params);
        foreach (DB::fetch_all("select  $presql FROM $sql  where $wheresql", $params) as $value) {
            if (!$value['mtimedata']) continue;
            if (!isset($data[$value['mtimedata']])) {
                $data[$value['mtimedata']]['num'] = 1;
                $data[$value['mtimedata']]['val'] = $timedataarr[$value['mtimedata']]['val'];
                $data[$value['mtimedata']]['label'] = $timedataarr[$value['mtimedata']]['label'];
            } else {
                $data[$value['mtimedata']]['num']++;
            }
        }
        //将今天昨天归类到最近七天，将最近七天归到最近30天，将近30天归到最近90天，将最近90天归到最近365天
        $data[-7]['num'] = (isset($data[-7]['num']) ? intval($data[-7]['num']) : 0) + (isset($data[1]['num']) ? intval($data[1]['num']) : 0) + (isset($data[-1]['num']) ? intval($data[-1]['num']) : 0);
        if ($data[-7]['num']) $data[-7] = array('num' => $data[-7]['num'], 'val' => $timedataarr[-7]['val'], 'label' => $timedataarr[-7]['label']);
        $data[-30]['num'] = (isset($data[-30]['num']) ? intval($data[-30]['num']) : 0) + (isset($data[-7]['num']) ? intval($data[-7]['num']) : 0);
        if ($data[-30]['num']) $data[-30] = array('num' => $data[-30]['num'], 'val' => $timedataarr[-30]['val'], 'label' => $timedataarr[-30]['label']);
        $data[-90]['num'] = (isset($data[-90]['num']) ? intval($data[-90]['num']) : 0) + (isset($data[-30]['num']) ? intval($data[-30]['num']) : 0);
        if ($data[-90]['num']) $data[-90] = array('num' => $data[-90]['num'], 'val' => $timedataarr[-90]['val'], 'label' => $timedataarr[-90]['label']);
        $data[-365]['num'] = (isset($data[-365]['num']) ? intval($data[-365]['num']) : 0) + (isset($data[-90]['num']) ? intval($data[-90]['num']) : 0);
        if ($data[-365]['num']) $data[-365] = array('num' => $data[-365]['num'], 'val' => $timedataarr[-365]['val'], 'label' => $timedataarr[-365]['label']);
        foreach ($data as $k => $v) {
            if ($v['num'] == 0) unset($data[$k]);
        }
        krsort($data);
    } elseif ($skey == 'dateline') {
        //修改时间
        $presql = ' case ';
        $prepara = [];
        foreach ($timedataarr as $sv) {
            $presql .= ' when r.dateline >= %d ' . (($sv['end']) ? ' and  r.dateline < %d then %d' : ' then %d');
            $prepara[] = $sv['start'];
            if ($sv['end']) $prepara[] = $sv['end'];
            $prepara[] = $sv['val'];
        }
        if ($presql) {
            $presql .= ' end as %s';
            $prepara[] = 'datelinedata';
        }
        if (!empty($para)) $params = array_merge($params, $para);
        if (!empty($prepara)) $params = array_merge($prepara, $params);
        foreach (DB::fetch_all("select  $presql FROM $sql  where$wheresql ", $params) as $value) {
            if (!$value['datelinedata']) continue;
            if (!isset($data[$value['datelinedata']])) {
                $data[$value['datelinedata']]['num'] = 1;
                $data[$value['datelinedata']]['val'] = $timedataarr[$value['datelinedata']]['val'];
                $data[$value['datelinedata']]['label'] = $timedataarr[$value['datelinedata']]['label'];
            } else {
                $data[$value['datelinedata']]['num']++;
            }
        }
        //将今天昨天归类到最近七天，将最近七天归到最近30天，将近30天归到最近90天，将最近90天归到最近365天
        //将今天昨天归类到最近七天，将最近七天归到最近30天，将近30天归到最近90天，将最近90天归到最近365天
        $data[-7]['num'] = (isset($data[-7]['num']) ? intval($data[-7]['num']) : 0) + (isset($data[1]['num']) ? intval($data[1]['num']) : 0) + (isset($data[-1]['num']) ? intval($data[-1]['num']) : 0);
        if ($data[-7]['num']) $data[-7] = array('num' => $data[-7]['num'], 'val' => $timedataarr[-7]['val'], 'label' => $timedataarr[-7]['label']);
        $data[-30]['num'] = (isset($data[-30]['num']) ? intval($data[-30]['num']) : 0) + (isset($data[-7]['num']) ? intval($data[-7]['num']) : 0);
        if ($data[-30]['num']) $data[-30] = array('num' => $data[-30]['num'], 'val' => $timedataarr[-30]['val'], 'label' => $timedataarr[-30]['label']);
        $data[-90]['num'] = (isset($data[-90]['num']) ? intval($data[-90]['num']) : 0) + (isset($data[-30]['num']) ? intval($data[-30]['num']) : 0);
        if ($data[-90]['num']) $data[-90] = array('num' => $data[-90]['num'], 'val' => $timedataarr[-90]['val'], 'label' => $timedataarr[-90]['label']);
        $data[-365]['num'] = (isset($data[-365]['num']) ? intval($data[-365]['num']) : 0) + (isset($data[-90]['num']) ? intval($data[-90]['num']) : 0);
        if ($data[-365]['num']) $data[-365] = array('num' => $data[-365]['num'], 'val' => $timedataarr[-365]['val'], 'label' => $timedataarr[-365]['label']);
        foreach ($data as $k => $v) {
            if ($v['num'] == 0) unset($data[$k]);
        }
        krsort($data);
    }
    exit(json_encode($data));
}
elseif ($operation == 'search_menu') {
    $uid = getglobal('uid');
    $ulevel = $uid ? $_G['pichomelevel'] : 0;
    $skey = isset($_GET['skey']) ? trim($_GET['skey']) : '';
    if ($clid) {
        $perm = C::t('pichome_collectuser')->get_perm_by_clid($clid);
        if ($perm < 1) exit(json_encode(array()));
    }
    if ($skey == 'tag') {

        $sql = "select count(DISTINCT(rt.tid)) as num  from  %t rt   left join %t r on rt.rid=r.rid left join %t cl on cl.rid=r.rid";


        $params = ['pichome_resourcestag', 'pichome_resources','pichome_collectlist'];

    } else {
        exit(json_encode(array()));
    }
    $wheresql = " r.isdelete = 0   and r.level <= %d ";
    $para[] = $ulevel;
    $cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
    $hassub = isset($_GET['hassub']) ? intval($_GET['hassub']) : 0;

    $hassub = isset($_GET['hassub']) ? intval($_GET['hassub']) : 0;
    if ($clid) {
        $wheresql .= ' and cl.clid = %d ';
        $para[] = $clid;
    } else {
        $clids = [];
        foreach(DB::fetch_all("select clid from %t where uid = %d and perm > %d",array('pichome_collectuser',$uid,0)) as $v){
            $clids[] = $v['clid'];
        }
        if(!empty($clids)){
            $wheresql .= ' and cl.uid = %d and cl.clid in(%n)';
            $para[] = $uid;
            $para[] = $clids;
        }else{
            $wheresql .= ' and 0';
        }
    }
    if ($cid && $cid > 0) {
        if ($hassub) {
            $cids = C::t('pichome_collectcat')->fetch_cid_by_pcid($cid);
            $wheresql .= " and cl.cid in(%n) ";
            $para[] = $cids;
        } else {
            $wheresql .= " and cl.cid = %d ";
            $para[] = $cid;
        }
    } elseif ($clid && $cid == -1) {
        $wheresql .= " and cl.cid = %d ";
        $para[] = 0;
    }
    //关键词条件
    $keyword = isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '';
    if ($keyword) {
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= "  left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        $keywords = array();
        $arr1 = explode('+', $keyword);
        foreach ($arr1 as $value1) {
            $value1 = trim($value1);
            $arr2 = explode(' ', $value1);
            $arr3 = array();
            foreach ($arr2 as $value2) {
                $arr3[] = "r.name LIKE %s";
                $para[] = '%' . $value2 . '%';
                $arr3[] = "ra.link LIKE %s";
                $para[] = '%' . $value2 . '%';
                $arr3[] = "ra.desc LIKE %s";
                $para[] = '%' . $value2 . '%';
                $arr3[] = "ra.searchval LIKE %s";
                $para[] = '%' . $value2 . '%';
            }
            $keywords[] = "(" . implode(" OR ", $arr3) . ")";
        }
        if ($keywords) {
            $wheresql .= " and (" . implode(" AND ", $keywords) . ")";
        }
    }
    //颜色条件
    if (isset($_GET['color'])) {
        $persion = isset($_GET['persion']) ? intval($_GET['persion']) : 0;
        $maxColDist = 764.8339663572415;
        $similarity = 50 + (50 / 100) * $persion;
        $color = trim($_GET['color']);
        $rgbcolor = hex2rgb($color);
        $sql .= "  left join %t p on r.rid = p.rid ";
        $params[] = 'pichome_palette';
        $wheresql .= "and round((%d-sqrt((((2+(p.r+%d)/2)/256)*(pow((%d-p.r),2))+(4*pow((%d-p.g),2)) + (((2+(255-(p.r+%d)/2))/256))*(pow((%d-p.b), 2)))))/%d,4)*p.weight > 85";
        if (!empty($para)) $para = array_merge($para, array($maxColDist, $rgbcolor['r'], $rgbcolor['r'], $rgbcolor['g'], $rgbcolor['r'], $rgbcolor['b'], $maxColDist, $similarity));
        else  $para = array($maxColDist, $rgbcolor['r'], $rgbcolor['r'], $rgbcolor['g'], $rgbcolor['r'], $rgbcolor['b'], $maxColDist, $similarity);
    }
    //标签条件
    if (isset($_GET['tag'])) {
        $tagwherearr = [];
        $tagrelative = isset($_GET['tagrelative']) ? intval($_GET['tagrelative']) : 1;
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= "  left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        $tag = trim($_GET['tag']);
        if ($tag == -1) {
            $wheresql .= " and ra.tag =  '' ";
        } else {
            $tagval = explode(',', trim($_GET['tag']));
            foreach ($tagval as $v) {
                $tagwherearr[] = " find_in_set(%d,ra.tag)";
                $para[] = $v;
            }
            if ($tagrelative) {
                $wheresql .= " and (" . implode(" or ", $tagwherearr) . ")";
            } else {
                $wheresql .= " and (" . implode(" and ", $tagwherearr) . ")";
            }
        }

    }

    //时长条件
    if (isset($_GET['duration'])) {
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= "  left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        $durationarr = explode('_', $_GET['duration']);
        $dunit = isset($_GET['dunit']) ? trim($_GET['dunit']) : 's';
        if ($durationarr[0]) {
            $wheresql .= " and ra.duration >= %d";
            $para[] = ($dunit == 'm') ? $durationarr[0] * 60 : $durationarr[0];
        }

        if ($durationarr[1]) {
            $wheresql .= " and ra.duration <= %d";
            $para[] = ($dunit == 'm') ? $durationarr[1] * 60 : $durationarr[1];
        }
    }
    //标注条件
    if (isset($_GET['comments'])) {
        $sql .= "  left join %t c on r.rid = c.rid";
        $params[] = 'pichome_comments';
        $comments = intval($_GET['comments']);
        $cval = isset($_GET['cval']) ? trim($_GET['cval']) : '';
        if (!$comments) {
            $wheresql .= " and  isnull(c.annotation) ";
        } else {
            if ($cval) {
                $cvalarr = explode(',', $cval);
                $cvalwhere = [];
                foreach ($cvalarr as $cv) {
                    $cvalwhere[] = " c.annotation like %s";
                    $para[] = '%' . $cv . '%';
                }
                $wheresql .= " and (" . implode(" or ", $cvalwhere) . ")";
            } else {
                $wheresql .= " and  !isnull(c.annotation)";
            }
        }
    }
    //注释条件
    if (isset($_GET['desc'])) {
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= " left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        $desc = intval($_GET['desc']);
        $descval = isset($_GET['descval']) ? trim($_GET['descval']) : '';
        if (!$desc) {
            $wheresql .= " and  (isnull(ra.desc) or ra.desc='') ";
        } else {
            if ($descval) {
                $descvalarr = explode(',', $descval);
                $descvalwhere = [];
                foreach ($descvalarr as $dv) {
                    $descvalwhere[] = "  ra.desc  like %s";
                    $para[] = '%' . $dv . '%';
                }
                $wheresql .= " and (" . implode(" or ", $descvalwhere) . ")";
            } else {
                $wheresql .= " and ra.desc !=''";
            }
        }
    }
    //链接条件
    if (isset($_GET['link'])) {
        if (!in_array('pichome_resources_attr', $params)) {
            $sql .= " left join %t ra on r.rid = ra.rid";
            $params[] = 'pichome_resources_attr';
        }
        $link = intval($_GET['link']);
        $linkval = isset($_GET['linkval']) ? trim($_GET['linkval']) : '';
        if (!$link) {
            $wheresql .= " and  (isnull(ra.link) or ra.link='') ";
        } else {
            if ($linkval) {
                $linkvalarr = explode(',', $linkval);
                $linkvalwhere = [];
                foreach ($linkvalarr as $lv) {
                    $linkvalwhere[] = "  ra.link  like %s";
                    $para[] = '%' . $lv . '%';
                }
                $wheresql .= " and (" . implode(" or ", $linkvalwhere) . ")";
            } else {
                $wheresql .= " and  ra.link !='' ";
            }
        }
    }
    //形状条件
    if (isset($_GET['shape'])) {
        $shape = trim($_GET['shape']);
        $shapes = explode(',', $shape);

        $shapewherearr = [];
        foreach ($shapes as $v) {
            switch ($v) {
                case 7://方图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = 100;
                    break;
                case 8://横图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) > %d and  round((r.width / r.height) * 100) < 250';
                    $para[] = 100;
                    break;
                case 5://细长横图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) >= %d';
                    $para[] = 250;
                    break;
                case 6://细长竖图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) <= %d';
                    $para[] = 40;
                    break;
                case 9://竖图
                    $shapewherearr[] = '  round((r.width / r.height) * 100) < %d and round((r.width / r.height) * 100) > %d';
                    $para[] = 100;
                    $para[] = 40;
                    break;
                case 1://4:3
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = (4 / 3) * 100;
                    break;
                case 2://3:4
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = (3 / 4) * 100;
                    break;
                case 3://16:9
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = (16 / 9) * 100;
                    break;
                case 4://9:16
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = (9 / 16) * 100;
                    break;
                /*case 10:
                    $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
                    $para[] = ($swidth / $sheight) * 100;
                    break;*/
            }
        }
        if (isset($_GET['shapesize'])) {
            $shapesize = trim($_GET['shapesize']);
            $shapesizes = explode(':', $shapesize);
            $swidth = intval($shapesizes[0]);
            $sheight = intval($shapesizes[1]);
            $shapewherearr[] = '  round((r.width / r.height) * 100) = %d';
            $para[] = ($swidth / $sheight) * 100;
        }
        if ($shapewherearr) {
            $wheresql .= " and (" . implode(" or ", $shapewherearr) . ")";
        }
    }
    //评分条件
    if (isset($_GET['grade'])) {
        $grade = trim($_GET['grade']);
        $grades = explode(',', $grade);
        $wheresql .= " and r.grade in(%n)";
        $para[] = $grades;
    }
    //类型条件
    if (isset($_GET['ext'])) {
        $ext = trim($_GET['ext']);
        $exts = explode(',', $ext);
        $wheresql .= " and r.ext in(%n)";
        $para[] = $exts;
    }
    //添加日期
    if (isset($_GET['btime'])) {
        $btime = explode('_', $_GET['btime']);
        $bstart = strtotime($btime[0]);
        $bend = strtotime($btime[1]) + 24 * 60 * 60;
        if ($bstart) {
            $wheresql .= " and r.btime > %d";
            //将时间补足13位
            $para[] = $bstart * 1000;
        }
        if ($bend) {
            $wheresql .= " and r.btime < %d";
            //将时间补足13位
            $para[] = $bend * 1000;
        }
    }
    //修改日期
    if (isset($_GET['dateline'])) {
        $dateline = explode('_', $_GET['dateline']);
        $dstart = strtotime($dateline[0]);
        $dend = strtotime($dateline[1]) + 24 * 60 * 60;
        if ($dstart) {
            $wheresql .= " and r.dateline > %d";
            //将时间补足13位
            $para[] = $dstart * 1000;
        }

        if ($dend) {
            $wheresql .= " and r.dateline < %d";
            //将时间补足13位
            $para[] = $dend * 1000;
        }
    }
    //创建日期
    if (isset($_GET['mtime'])) {
        $mtime = explode('_', $_GET['mtime']);
        $mstart = strtotime($mtime[0]);
        $mend = strtotime($mtime[1]) + 24 * 60 * 60;
        if ($mstart) {
            $wheresql .= " and r.mtime > %d";
            //将时间补足13位
            $para[] = $mstart * 1000;
        }

        if ($mend) {
            $wheresql .= " and r.mtime < %d";
            //将时间补足13位
            $para[] = $mend * 1000;
        }
    }
    //尺寸条件
    if (isset($_GET['wsize']) || isset($_GET['hsize'])) {
        $wsizearr = explode('_', $_GET['wsize']);
        $hsizearr = explode('_', $_GET['hsize']);
        if ($wsizearr[0]) {
            $wheresql .= " and r.width >= %d";
            $para[] = intval($wsizearr[0]);
        }
        if ($wsizearr[1]) {
            $wheresql .= " and r.width <= %d";
            $para[] = intval($wsizearr[1]);
        }
        if ($hsizearr[0]) {
            $wheresql .= " and r.height >= %d";
            $para[] = intval($hsizearr[0]);
        }
        if ($hsizearr[1]) {
            $wheresql .= " and r.height <= %d";
            $para[] = intval($hsizearr[1]);
        }
    }

    //大小条件
    if (isset($_GET['size'])) {
        $size = explode('_', $_GET['size']);
        $unit = isset($_GET['unit']) ? intval($_GET['unit']) : 1;
        switch ($unit) {
            case 0://b
                $size[0] = $size[0];
                $size[1] = $size[1];
                break;
            case 1://kb
                $size[0] = $size[0] * 1024;
                $size[1] = $size[1] * 1024;
                break;
            case 2://mb
                $size[0] = $size[0] * 1024 * 1024;
                $size[1] = $size[1] * 1024 * 1024;
                break;
            case 3://gb
                $size[0] = $size[0] * 1024 * 1024 * 1024;
                $size[1] = $size[1] * 1024 * 1024 * 1024;
                break;
        }
        if ($size[0]) {
            $wheresql .= " and r.szie > %d";
            $para[] = $size[0];
        }
        if ($size[1]) {
            $wheresql .= " and r.size < %d";
            $para[] = $size[1];
        }
    }

    $wheresql .= " and r.isdelete = 0 ";
    $data = array();
    if ($skey == 'tag') {

        $tagkeyword = isset($_GET['tagkeyword']) ? trim($_GET['tagkeyword']) : '';
        if ($tagkeyword) {
            $sql .= "  left join %t t on t.tid=rt.tid ";
            $params[] = 'pichome_tag';
            $wheresql .= "  and t.tagname LIKE %s ";
            $para[] = '%' . $tagkeyword . '%';
        }
        //最后返回数组
        $data = [];
    }
    exit(json_encode($data));
} elseif ($operation == 'setshow') {//设置显示字段
    $showfileds = isset($_GET['showfileds']) ? trim($_GET['showfileds']) : '';
    $other = isset($_GET['other']) ? trim($_GET['other']) : '';
    $uid = getglobal('uid');
    if (!$uid) exit(json_encode(array('error' => true)));
    C::t('user_setting')->update_by_skey('pichomecollectshowfileds', serialize(array('filed' => $showfileds, 'other' => $other)), $uid);
    exit(json_encode(array('success' => true)));
} elseif ($operation == 'setsort') {//设置排序方式
    $sortfiled = isset($_GET['sortfiled']) ? trim($_GET['sortfiled']) : '';
    $allowsortarr = ['name', 'size', 'whsize', 'ext', 'size', 'grade', 'filesize', 'mtime', 'dateline', 'btime', 'duration'];
    if (!in_array($sortfiled, $allowsortarr)) exit(json_encode(array('error' => true)));
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';
    $uid = getglobal('uid');
    if (!$uid) exit(json_encode(array('error' => true)));

    C::t('user_setting')->update_by_skey('pichomecollectsortfileds', serialize(array('filed' => $sortfiled, 'sort' => $sort)), $uid);
    exit(json_encode(array('success' => true)));

} elseif ($operation == 'setlayout') {//设置布局方式
    $layout = isset($_GET['layout']) ? trim($_GET['layout']) : '';
    $uid = getglobal('uid');
    if (!$uid) exit(json_encode(array('error' => true)));
    C::t('user_setting')->update_by_skey('pichomecollectlayout', serialize(array('layout' => $layout)), $uid);
    exit(json_encode(array('success' => true)));
} elseif ($operation == 'screensetting') {//筛选设置
    $uid = getglobal('uid');
    if (!$uid) exit(json_encode(array('error' => true)));
    if (!submitcheck('settingsubmit')) {
        $screen = C::t('user_setting')->fetch_by_skey('pichomecollectuserscreen', $uid);
    } else {
        $screen = isset($_GET['screen']) ? trim($_GET['screen']) : '';
        $uid = getglobal('uid');
        C::t('user_setting')->update_by_skey('pichomecollectuserscreen', $screen, $uid);
        exit(json_encode(array('success' => true)));
    }
} elseif ($operation == 'getscreen') {//获取筛选项
    $tagval = $_GET['tag'] ? explode(',', trim($_GET['tag'])) : [];
    $shape = $_GET['shape'] ? trim($_GET['shape']) : '';
    
    $shapelable = [];
    if (!empty($shapes)) {
        $shapes = explode(',', $shape);
        foreach($shapes as $s){
            $shapelable[] = $shapedataarr[$s];
        }
    }

    $tagdata = [];
    if (!empty($tagval)) {

        foreach (DB::fetch_all("select tagname,tid from %t where tid in(%n) ", array('pichome_tag', $tagval)) as $tv) {
            $tagdata[] = array('tagname' => $tv['tagname'], 'tid' => intval($tv['tid']));
        }

    }
    $folderdata = [];
    $cid = isset($_GET['cid']) ? intval($_GET['cid']):0;
    if($cid){
        $pathkey = DB::result_first("select pathkey from %t where cid = %d",array('pichome_collectcat',$cid));
        $folderdata = explode('-',$pathkey);
    }
    if (isset($_G['setting']['pichomefilterfileds'])) {
        exit(json_encode(array('success' => true, 'data' => $_G['setting']['pichomefilterfileds'], 'tagdata' => $tagdata, 'shape' => $shapelable,'catdata'=>$folderdata)));
    } else {
        $setting = C::t('setting')->fetch_all('pichomefilterfileds');
        exit(json_encode(array('success' => true, 'data' => $setting['pichomefilterfileds'], 'tagdata' => $tagdata, 'shape' => $shapelable,'folderdata'=>$folderdata)));
    }


} elseif ($operation == 'expandedsetting') {
    if (submitcheck('settingsubmit')) {
        $pichomeimageexpanded = $_GET['pichomeimageexpanded'];
        C::t('user_setting')->update_by_skey('pichomecollectimageexpanded', $pichomeimageexpanded, $uid);
        exit(json_encode(array('success' => true)));
    }
} elseif ($operation == 'getexpandedkeys') {
    $folderdata = [];
    $cid = isset($_GET['cid']) ? intval($_GET['cid']):0;
    if($cid){
        $pathkey = DB::result_first("select pathkey from %t where cid = %d",array('pichome_collectcat',$cid));
        $result = str_replace("_", "", $pathkey);
        $folderdata = explode('-',$result);
    }

    exit(json_encode(array('data'=>$folderdata)));
} elseif ($operation == 'getcollectcat') {//收藏分类树
    //分类id
    $pcid = isset($_GET['pcid']) ? intval($_GET['pcid']) : 0;
	$level = isset($_GET['level']) ? intval($_GET['level']) : 1;
	$data = array();
	if(!$level){
		$collectdata = C::t('pichome_collect')->fetch($clid);
		$collectdata['nocatnum'] = DB::result_first("select count(id) from %t where clid = %d and cid = 0",array('pichome_collectlist',$clid));
		$data[] = array(
			'cid'=>'all',
			'catname'=>lang('all_favorites'),
			'pcatname'=>lang('all_favorites'),
			'leaf'=>true,
			'clid'=>0,
			'filenum'=>$collectdata['filenum']
		);
		$data[] = array(
			'cid'=>'not',
			'catname'=>lang('uncategorized'),
			'pcatname'=>lang('uncategorized'),
			'leaf'=>true,
			'clid'=>0,
			'filenum'=>$collectdata['nocatnum']
		);
	}
	foreach(C::t('pichome_collectcat')->fetch_by_clid_pcid($clid, $pcid) as $value){
		$value['cid'] = intval($value['cid']);
		$data[] = $value;
	}
    exit(json_encode(array('success' => $data)));
} elseif ($operation == 'searchcat') {//搜索符合条件的分类
    $keyword = isset($_GET['keyword']) ? getstr($_GET['keyword'], 30) : '';
    $returndata = C::t('pichome_collectcat')->search_by_catname($keyword, $clid);
    exit(json_encode(array('success' => $returndata)));

} elseif ($operation == 'getcollectuser') {//获取收藏夹下的用户
    $keyword = isset($_GET['keyword']) ? getstr($_GET['keyword'], 30) : '';
    $params = array('pichome_collectuser', 'user', $clid);
    $wheresql = '';
    if ($keyword) {
        $wheresql .= ' and u.username like %s';
        $params[] = '%' . $keyword . '%';
    }
    $data = [];

    foreach (DB::fetch_all("select cu.*,u.username,u.adminid from %t cu left join %t u on cu.uid=u.uid where cu.clid = %d $wheresql "
        , $params) as $v) {
        $v['icon'] = avatar_block($v['uid']);
        $data[] = $v;
    }
    exit(json_encode(array('success' => $data)));

}
