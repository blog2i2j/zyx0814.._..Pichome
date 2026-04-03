<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}
global $_G,$id,$data;
    $data = C::t('publish_list')->fetch_by_id($id,1);
    $_G['setting']['metakeywords']=$data['metakeywords']?$data['metakeywords']:$_G['setting']['metakeywords'];
    $_G['setting']['metadescription']=$data['metadescription']?$data['metadescription']:$_G['setting']['metadescription'];
    if($_G['setting']['pathinfo']){
        $url = $_G['siteurl'].$data['address'];
    }else{
        $url = $_G['siteurl'].'index.php?mod=publish&id='.$id;
    }
    $data['url']=$url;
    $data['fdateline']=dgmdate($data['dateline'],'Y-m-d H:i:s');
    $pagedata = C::t('pichome_templatepage')->fetch_pagedata_by_id($data['pval']);

    foreach($pagedata['tags'] as $key => $tag){//循环模块
        $tagtype = $tag['tagtype'];
        if ($tagtype == 'file_rec' || $tagtype == 'db_ids') {//如果是文件推荐
            foreach($tag['data'] as $key1 => $tdata) { //循环tab块数据
                $tagval = $tdata['tdata'];
                $tagval = $tagval[0];
                $tdid = $tdata['tdid'];
                $limitnum = $tagval['number'];
                if (!$_G['config']['filterFileByTabPerm']) {
                    $cachename = 'templatetagdata_' . $tdid;
                } else {
                    $uid = $_G['uid'] ? $_G['uid'] : 'guest';
                    $cachename = 'templatetagdata_' . $tdid . '_' . $uid;
                }
                $processname = 'templatetagdatalock_' . $tdid;
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $perpage = isset($_GET['perpage']) ? intval($_GET['perpage']) : 200;
                if ($tagtype == 'db_ids' && $page == 1 && $limitnum && $perpage > $limitnum) $perpage = $limitnum;
                if ($tagtype == 'db_ids' && $page > 1) {
                    $count = ($page - 1) * $perpage;
                    if ($limitnum && $count > $limitnum) $perpage = 0;
                    elseif ($limitnum && (($count + $perpage) > $limitnum)) {
                        $perpage = (($limitnum - $count) < 0) ? 0 : intval($limitnum - $count);
                    }
                }

                $start = ($page - 1) * $perpage;
                $limitsql = "limit $start," . $perpage;

                if (0 && $tagtype == 'db_ids' && $page == 1 && $tdata['cachetime'] && $cachedata = C::t('cache')->fetch_cachedata_by_cachename($cachename, $tdata['cachetime'])) {
                    $rids = $cachedata;
                } elseif (0 && $tagtype != 'db_ids' && $tdata['cachetime'] && $cachedata = C::t('cache')->fetch_cachedata_by_cachename($cachename, $tdata['cachetime'])) {
                    $rids = $cachedata;
                } else {

                    $sql = " from %t r  ";
                    //$selectsql = "  distinct r.rid,r.name ";
                    $selectsql = "   r.rid,r.name ";
                    $wheresql = " r.appid = %s and r.isdelete = 0 ";
                    $params = ['pichome_resources'];
                    $para[] = trim($tagval['id']);
                    //}
                    $countsql = " count(distinct(r.rid))";

                    if ($tagval['type'] == 2) {//标签
                        $tagarr = explode(',', $tagval['value']);
                        $tids = [];
                        foreach (DB::fetch_all("select tid from %t where tagname in(%n)", array('pichome_tag', $tagarr)) as $tid) {
                            $tids[] = $tid['tid'];
                        }
                        $sql .= "left join %t rt on rt.rid=r.rid ";
                        $params[] = 'pichome_resourcestag';
                        $wheresql .= ' and rt.tid in(%n) ';
                        $para[] = $tids;
                    } elseif ($tagval['type'] == 3) {//评分
                        switch ($tagval['gradetype']) {
                            case 0:
                                $wheresql .= ' and r.grade = %d ';
                                $para[] = intval($tagval['value']);
                                break;
                            case 1:
                                $wheresql .= ' and r.grade != %d ';
                                $para[] = intval($tagval['value']);
                                break;
                            case 2:
                                $wheresql .= ' and r.grade <= %d ';
                                $para[] = intval($tagval['value']);
                                break;
                            case 3:
                                $wheresql .= ' and r.grade >= %d ';
                                $para[] = intval($tagval['value']);
                                break;
                        }
                    } elseif ($tagval['type'] == 4) {//分类
                        $fidarr = $tagval['classify']['checked'];
                        /*$wheresql .= ' and r.fids in(%n) ';
                        $para[] = $fidarr;*/
                        $sql .= "left join %t fr on fr.rid=r.rid ";
                        $params[] = 'pichome_folderresources';
                        $wheresql .= ' and fr.fid in(%n) ';
                        $para[] = $fidarr;
                    }
                    $clang = '';
                    Hook::listen('lang_parse', $clang, ['checklang']);
                    if ($clang) $wheresql .= " and (r.lang = '" . $_G['language'] . "' or r.lang = 'all' ) ";
                    if ($tagval['sort'] == 1) {//最新推荐
                        $ordersql = '  r.dateline desc ';
                    } elseif ($tagval['sort'] == 2) {//热门排序
                        $sql .= ' left join %t v on r.rid=v.idval and v.idtype = 0 ';
                        $selectsql .= " ,v.nums as num  ";
                        $params[] = 'views';
                        $ordersql = '  num desc ,r.dateline desc ';
                    } elseif ($tagval['sort'] == 3) {//名字排序
                        //$ordersql = ' r.dateline desc ';
                        $ordersql = '   cast((r.name) as unsigned) asc, CONVERT((r.name) USING gbk) asc';

                    } elseif ($tagval['type'] == 4) {//最新排序

                        $ordersql = ' r.dateline desc ';
                    } else {
                        $ordersql = ' r.dateline desc ';
                    }
                    $hookdata = ['params' => $params, 'para' => $para, 'wheresql' => $wheresql, 'sql' => $sql];
                    Hook::listen('fileFilter', $hookdata);
                    $params = $hookdata['params'];
                    $para = $hookdata['para'];
                    $wheresql = $hookdata['wheresql'];
                    $sql = $hookdata['sql'];
                    if ($para) $params = array_merge($params, $para);
                    $count = DB::result_first("select $countsql $sql where  $wheresql  ", $params);
                    $rids = [];

                    foreach (DB::fetch_all(" select  $selectsql $sql where  $wheresql  group by r.rid  order by $ordersql  $limitsql", $params) as $value) {
                        $rids[] = $value['rid'];
                    }
                    if ((($tagtype == 'db_ids' && $page == 1) || $tagtype == 'file_rec') && $tdata['cachetime'] && !empty($rids)) {
                        $cachearr = [
                            'cachekey' => $cachename,
                            'cachevalue' => serialize($rids),
                            'dateline' => TIMESTAMP
                        ];
                        C::t('cache')->insert_cachedata_by_cachename($cachearr, $tdata['cachetime'], 1);
                    }
                }
                $resourcesdata = [];
                if (!empty($rids)) {
                    $rdata = C::t('pichome_resources')->getdatasbyrids($rids, 1, $data['perm']);
                    foreach ($rdata as  $value) {
                        $resourcesdata[] = $value;
                    }
                }
                $pagedata['tags'][$key]['data'][$key1]['datas'] = $resourcesdata;
            }
        }
        elseif($tagtype == 'tab_rec'){//如果是专辑推荐
            foreach($tag['data'] as $key1 => $tdata) { //循环tab块数据


                $tagval = $tdata['tdata'][0];

                $tdid=$tdata['tdid'];
                $limitnum = $tagval['number'];
                $cachename = 'templatetagdata_' . $tdid;
                $processname = 'templatetagdatalock_' . $tdid;
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $perpage = isset($_GET['perpage']) ? intval($_GET['perpage']) : 100;
                if ($limitnum && $perpage > $limitnum) $perpage = $limitnum;
                $start = ($page - 1) * $perpage;
                $limitsql = "limit $start," . $perpage;

                if (0 && $tdata['cachetime'] && $cachedata = C::t('cache')->fetch_cachedata_by_cachename($cachename, $tdata['cachetime'])) {
                    $tids = $cachedata;

                } else {

                    // print_r($tagval);die;
                    $sql = " from %t t  ";
                    $selectsql = "   t.tid ";
                    $wheresql = " t.gid = %d and t.isdelete < 1 ";
                    $params = ['tab'];
                    $para = array(intval($tagval['id']));
                    //}
                    $countsql = " count(distinct(t.tid))";
                    if (isset($tagval['classify']['checked'])) {//如果分类有值
                        $cidarr = $tagval['classify']['checked'];
                        $sql .= ' LEFT JOIN %t tabcatrelation ON tabcatrelation.tid = t.tid ';
                        $params[] = 'tab_cat_relation';
                        $wheresql .= ' and tabcatrelation.cid in(%n) ';
                        $para[] = $cidarr;
                    }
                    $ordersql='';
                    if ($tagval['sort'] == 1) {//最新推荐
                        $ordersql = ' order by  t.dateline desc ';
                    } elseif ($tagval['sort'] == 2) {//热门排序
                        $sql .= ' left join %t v on t.tid=v.idval and v.idtype = 2 ';
                        $selectsql .= " ,v.nums as num  ";
                        $params[] = 'views';
                        $ordersql = ' order by num desc ,t.dateline desc ';
                    }


                    if ($para) $params = array_merge($params, $para);
                    $count = DB::result_first("select $countsql $sql where  $wheresql  ", $params);
                    $tiddata = [];
                    /*echo " select  $selectsql $sql where  $wheresql  group by t.tid order by $ordersql  $limitsql";
                     print_r($params);die;*/
                    foreach (DB::fetch_all(" select  $selectsql $sql where  $wheresql  group by t.tid $ordersql  $limitsql", $params) as $value) {
                        $tids[] = $value['tid'];
                    }

                    if (!empty($tids) && $tdata['cachetime']) {
                        $cachearr = [
                            'cachekey' => $cachename,
                            'cachevalue' => serialize($tids),
                            'dateline' => TIMESTAMP
                        ];
                        C::t('cache')->insert_cachedata_by_cachename($cachearr, $tdata['cachetime'], 1);
                    }
                }
                $tabdata=array();
                if (!empty($tids)) {
                    $tabdata = C::t('#tab#tab')->fetch_by_tids($tids, 1);
                }

                $gid = intval($tagval['id']);
                $gdata = C::t('#tab#tab_group')->fetch_by_gid($gid);
                $pagedata['tags'][$key]['data'][$key1]['gdata']=$gdata;
                $pagedata['tags'][$key]['data'][$key1]['tabdata']=$tabdata;

            }
        }
        elseif($tagtype == 'collect_ids'){//如果是专辑推荐
            foreach($tag['data'] as $key1 => $tdata) { //循环tab块数据

                $tagval = $tdata['tdata'][0];

                $tdid=$tdata['tdid'];
                $limitnum = $tagval['number'];
                $cachename = 'templatetagdata_' . $tdid;
                $processname = 'templatetagdatalock_' . $tdid;
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $perpage = isset($_GET['perpage']) ? intval($_GET['perpage']) : 100;
                if ($limitnum && $perpage > $limitnum) $perpage = $limitnum;
                $start = ($page - 1) * $perpage;
                $limitsql = "limit $start," . $perpage;

                if (0 && $tdata['cachetime'] && $cachedata = C::t('cache')->fetch_cachedata_by_cachename($cachename, $tdata['cachetime'])) {
                    $pids = $cachedata;

                } else {

                    $pid=$tagval['id'];
                    $sql="p.pstatus='1' and r.rpid=%d";
                    $param=array('publish_relation','publish_list',$pid);

                    $ordersql="order by p.dateline";
                    $pids=array();
                    if($count=DB::result_first("select COUNT(*) from %t r LEFT JOIN %t p on r.pid=p.id where $sql",$param)) {
                        foreach (DB::fetch_all("select p.id from %t r LEFT JOIN %t p on r.pid=p.id where $sql $ordersql limit $start,$perpage", $param) as $value) {
                            $pids[]=$value['id'];
                        }
                    }


                    if (!empty($pids) && $tdata['cachetime']) {
                        $cachearr = [
                            'cachekey' => $cachename,
                            'cachevalue' => serialize($tids),
                            'dateline' => TIMESTAMP
                        ];
                        C::t('cache')->insert_cachedata_by_cachename($cachearr, $tdata['cachetime'], 1);
                    }
                }
                $datas=array();
                if (!empty($pids)) {
                  foreach($pids as $pid) {
                      $value = C::t('publish_list')->fetch_by_id($pid);
                      $value['dateline'] = dgmdate($value['dateline'], 'Y-m-d H:i:s');
                      if ($value['pageset']['_file_cover'][0]['src']) {
                          $value['img'] = $value['pageset']['_file_cover'][0]['src'];
                      }

                      //处理地址
                      $url = 'index.php?mod=publish&id=' . $value['id'];
                      $value['address'] = C::t('pichome_route')->update_path_by_url($url, $value['address']);
                      if (strpos($value['url'], 'http') === false) {
                          $value['url'] = $_G['siteurl'] . $value['address'];
                      } else {
                          $value['url'] = $_G['siteurl'] . $url;
                      }
                      $datas[] = $value;
                  }
                }
                $pagedata['tags'][$key]['data'][$key1]['datas']=$datas;
            }
        }
        elseif($tagtype == 'collect_rec'){//如果是专辑推荐
            foreach($tag['data'] as $key1 => $tdata) { //循环tab块数据

                $tagval = $tdata['tdata'][0];

                $tdid=$tdata['tdid'];
                $limitnum = $tagval['number'];
                $cachename = 'templatetagdata_' . $tdid;
                $processname = 'templatetagdatalock_' . $tdid;
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $perpage = isset($_GET['perpage']) ? intval($_GET['perpage']) : 100;
                if ($limitnum && $perpage > $limitnum) $perpage = $limitnum;
                $start = ($page - 1) * $perpage;
                $limitsql = "limit $start," . $perpage;

                if (0 && $tdata['cachetime'] && $cachedata = C::t('cache')->fetch_cachedata_by_cachename($cachename, $tdata['cachetime'])) {
                    $pids = $cachedata;

                } else {

                    $pid=$tagval['ids'];
                    $sql="p.pstatus='1' and r.rpid IN(%n)";
                    $param=array('publish_relation','publish_list',$pid);

                    $ordersql="order by p.dateline";
                    $pids=array();
                    if($count=DB::result_first("select COUNT(*) from %t r LEFT JOIN %t p on r.pid=p.id where $sql",$param)) {
                        foreach (DB::fetch_all("select p.id from %t r LEFT JOIN %t p on r.pid=p.id where $sql $ordersql limit $start,$perpage", $param) as $value) {
                            $pids[]=$value['id'];
                        }
                    }


                    if (!empty($pids) && $tdata['cachetime']) {
                        $cachearr = [
                            'cachekey' => $cachename,
                            'cachevalue' => serialize($pids),
                            'dateline' => TIMESTAMP
                        ];
                        C::t('cache')->insert_cachedata_by_cachename($cachearr, $tdata['cachetime'], 1);
                    }
                }
                $datas=array();
                if (!empty($pids)) {
                    foreach($pids as $pid) {
                        $value = C::t('publish_list')->fetch_by_id($pid);
                        $value['dateline'] = dgmdate($value['dateline'], 'Y-m-d H:i:s');
                        if ($value['pageset']['_file_cover'][0]['src']) {
                            $value['img'] = $value['pageset']['_file_cover'][0]['src'];
                        }

                        //处理地址
                        $url = 'index.php?mod=publish&id=' . $value['id'];
                        $value['address'] = C::t('pichome_route')->update_path_by_url($url, $value['address']);
                        if (strpos($value['url'], 'http') === false) {
                            $value['url'] = $_G['siteurl'] . $value['address'];
                        } else {
                            $value['url'] = $_G['siteurl'] . $url;
                        }
                        $datas[] = $value;
                    }
                }
                $pagedata['tags'][$key]['data'][$key1]['datas']=$datas;
            }
        }
    }
    //print_r($pagedata);exit('ddd');
    include template('robot/alonepage/index');
    exit();