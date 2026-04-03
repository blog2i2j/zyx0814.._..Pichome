<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 *
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */

if (!defined('IN_OAOOA') ||  !defined('PICHOME_LIENCE')) {
    @header( 'HTTP/1.1 404 Not Found' );
    @header( 'Status: 404 Not Found' );
    exit('File not found');
}
$ismobile = helper_browser::ismobile();
Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
global $_G;
$uid = $_G['uid'];
($_G['adminid'] == 1 || !isset($_G['config']['pichomeclosecollect']) || !$_G['config']['pichomeclosecollect']) ? '': exit('Access Denied');
$do = isset($_GET['do']) ? trim($_GET['do']) : '';
if ($do == 'getallcovert') {//获取所有收藏缩略图
    $clids = [];
    foreach(DB::fetch_all("select clid from %t where uid = %d and perm > %d",array('pichome_collectuser',$uid,0)) as $v){
        $clids[] = $v['clid'];
    }
    if(!empty($clids)){
        $wheresql .= ' and cl.uid = %d and cl.clid in(%n)';
        $para[] = $uid;
        $para[] = $clids;
    }

    $icondatas = [];
    $count = DB::result_first("select count(id) as num  from %t   where clid in(%n) and uid = %d ",array('pichome_collectlist',$clids,$uid));
    $icondatas['total'] = $count ? $count:0;
    foreach(DB::fetch_all("select cl.rid from %t  cl 
    left join %t r on cl.rid = r.rid where cl.clid in(%n) and cl.uid = %d and r.isdelete = 0 order by cl.dateline desc limit 0,5",
        array('pichome_collectlist','pichome_resources',$clids,$uid)) as $v){
        //$icondata = ;
        $icondatas[] =C::t('pichome_resources')->geticondata_by_rid($v['rid'],1);
    }
    exit(json_encode(array('data' => $icondatas)));
}elseif($do == 'searchcollect') {
    $keyword = isset($_GET['keyword']) ? getstr($_GET['keyword'],30):'';
    $selectsql = 'select c.* from %t c left join %t cu on cu.clid=c.clid';
    $wheresql = 'cu.uid = %d';
    $params = array('pichome_collect', 'pichome_collectuser');
    $para = array($uid);
    if($keyword){
        $lang = '';
        Hook::listen('lang_parse',$lang,['checklang']);
        $wheresql1 = "  c.name like %s ";
        $para[] = '%'.$keyword.'%';

        if($lang){
            $selectsql .= " left join %t lang on c.clid = lang.idvalue and lang.idtype = 17 ";
            $params[] = 'lang_'.$lang;
            $wheresql1 .= " or lang.svalue like %s ";
            $para[] = '%'.$keyword.'%';
        }
        $wheresql = " $wheresql and ( $wheresql1 ) ";
    }
    $params = array_merge($params,$para);
    foreach (DB::fetch_all("$selectsql where   $wheresql order by c.dateline desc",$params) as $v) {
        if($v['lid']){
            if(!preg_match('/^\w{32}$/',$v['covert'])) $coverrid = DB::result_first("select rid from %t where id = %d",array('pichome_collectlist',$v['lid']));
            else $coverrid = $v['covert'];
            $v['covert'] = C::t('pichome_resources')->geticondata_by_rid($coverrid,1);
        }
        if($v['lid1']){
            if(!preg_match('/^\w{32}$/',$v['covert1'])) $coverrid1 = DB::result_first("select rid from %t where id = %d",array('pichome_collectlist',$v['lid1']));
            else $coverrid1 = $v['covert1'];
            $v['covert1'] = C::t('pichome_resources')->geticondata_by_rid($coverrid1,1);
        }
        if($v['lid2']){
            if(!preg_match('/^\w{32}$/',$v['covert2'])) $coverrid2 = DB::result_first("select rid from %t where id = %d",array('pichome_collectlist',$v['lid2']));
            else $coverrid2 = $v['covert2'];
            $v['covert2'] = C::t('pichome_resources')->geticondata_by_rid($coverrid2,1);;
        }
        $collects[] = $v;
    }
    Hook::listen('lang_parse',$collects,['getCollectLangData',1]);
    Hook::listen('lang_parse',$collects,['getCollectLangKey',1]);
    exit(json_encode(array('data'=>$collects)));
} else {
    //主题
    $theme = GetThemeColor();
//库
    $collects = [];
    foreach (DB::fetch_all("select c.* from %t c left join %t cu on cu.clid=c.clid
        where cu.uid = %d  order by c.dateline desc", array('pichome_collect', 'pichome_collectuser', $uid)) as $v) {

        if($v['lid']){
            if(!preg_match('/^\w{32}$/',$v['covert'])) $coverrid = DB::result_first("select rid from %t where id = %d",array('pichome_collectlist',$v['lid']));
            else $coverrid = $v['covert'];
            $v['covert'] = C::t('pichome_resources')->geticondata_by_rid($coverrid,1);
        }
        if($v['lid1']){
            if(!preg_match('/^\w{32}$/',$v['covert1'])) $coverrid1 = DB::result_first("select rid from %t where id = %d",array('pichome_collectlist',$v['lid1']));
            else $coverrid1 = $v['covert1'];
            $v['covert1'] = C::t('pichome_resources')->geticondata_by_rid($coverrid1,1);
        }
        if($v['lid2']){
            if(!preg_match('/^\w{32}$/',$v['covert2'])) $coverrid2 = DB::result_first("select rid from %t where id = %d",array('pichome_collectlist',$v['lid2']));
            else $coverrid2 = $v['covert2'];
            $v['covert2'] = C::t('pichome_resources')->geticondata_by_rid($coverrid2,1);;
        }
        $collects[] = $v;
    }
    //获取用户收藏排序值
    $collectdisp = C::t('user_setting')->fetch_by_skey('pichomecollectdisp', $uid);
    $collectdisparr = unserialize($collectdisp);
    //按clid值进行排序
    if (!empty($collectdisparr)) {
        array_multisort($collects, SORT_ASC, $collectdisparr);
    }
    Hook::listen('lang_parse',$collects,['getCollectLangData',1]);
    Hook::listen('lang_parse',$collects,['getCollectLangKey',1]);
    $collects = json_encode($collects);

    //筛选
    $screen = C::t('user_setting')->fetch_by_skey('pichomeuserscreen', $uid);
    $screen = $screen ? intval($screen) : 0;

    $setting = $_G['setting'];

    $pagesetting = $setting['pichomepagesetting'] ? $setting['pichomepagesetting'] : [];
//排序方式
    $pichomesortfileds = C::t('user_setting')->fetch_by_skey('pichomesortfileds', $_G['uid']);
//显示信息
    $pichomeshowfileds = C::t('user_setting')->fetch_by_skey('pichomeshowfileds', $_G['uid']);
//布局类型
    $pichomelayout = C::t('user_setting')->fetch_by_skey('pichomelayout', $_G['uid']);
    if ($pichomesortfileds) {
        $sortdatarr = unserialize($pichomesortfileds);
        $sortfilearr = ['btime' => 1, 'mtime' => 2, 'dateline' => 3, 'name' => 4, 'size' => 5, 'grade' => 6, 'duration' => 7, 'whsize' => 8];
        $pagesetting['sort'] = $sortfilearr[$sortdatarr['filed']];
        $pagesetting['desc'] = $sortdatarr['sort'];
    }
    if ($pichomelayout) {
        $layout = unserialize($pichomelayout);
        $pagesetting['layout'] = $layout['layout'];
    }
    if ($pichomeshowfileds) {
        $pichomeshowfileds = unserialize($pichomeshowfileds);
        $pagesetting['show'] = $pichomeshowfileds['filed'];
        $pagesetting['other'] = $pichomeshowfileds['other'];
    }
    $template = 1;
    if ($pagesetting['template']) {
        $template = $pagesetting['template'];
    }
    $pagesetting = json_encode($pagesetting);
    if ($ismobile) {
        include template('mobile/index');
    } else {
        include template('pc/index');
    }
}

    