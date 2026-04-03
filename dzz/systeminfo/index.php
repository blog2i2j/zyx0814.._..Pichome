<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 *
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */
if (!defined('IN_OAOOA')) {//所有的php文件必须加上此句，防止被外部调用
    exit('Access Denied');
}
Hook::listen('adminlogin');
$navtitle = lang('manage_tool');
$do = isset($_GET['do']) ? trim($_GET['do']) : '';
if ($do == 'header') {
    $list[] = array(
        'id' => 'systeminfo',
        'name' => lang('system_info'),
        'url' => 'index.php?mod=systeminfo',
    );
    $list[] = array(
        'id' => 'library',
        'name' => lang('library_manage'),
        'url' => 'index.php?mod=pichome&op=library',
    );
    if (defined('LICENSE_VERSION') && LICENSE_VERSION == 'Enterprise') {
        $list[] = array(
            'id' => 'tab',
            'name' => lang('tabgroup_setting'),
            'url' => 'index.php?mod=tab&op=admin',
        );
    }
    $list[] = array(
        'id' => 'alonepage',
        'name' => lang('page_manage'),
        'url' => 'index.php?mod=alonepage',
    );
    $list[] = array(
        'id' => 'banner',
        'name' => lang('banner_manage'),
        'url' => 'index.php?mod=banner&op=admin',
    );
    $list[] = array(
        'id' => 'manage',
        'name' => lang('manage_tool'),
        'url' => 'index.php?mod=manage',
    );
    exit(json_encode(array('data' => $list)));
} elseif ($do == 'authorize') {//授权信息
    include_once libfile('function/cache');
    $operation = isset($_GET['operation']) ? trim($_GET['operation']) : '';
    if ($operation) {
        if (isset($_FILES['file'])) {
            $files = $_FILES['file'];
            $licdata = file_get_contents($files['tmp_name']);
            if (!$licdata) exit(json_encode(array('error' => true)));
            C::t('setting')->update('sitelicensedata', $licdata);
            updatecache('setting');
            exit(json_encode(array('success' => true)));
        } else {
            exit(json_encode(array('error' => true)));
        }
    }
} elseif ($do == 'updateauth') {
    include_once libfile('function/cache');
    $username = isset($_GET['username']) ? trim($_GET['username']) : '';
    $password = isset($_GET['password']) ? trim($_GET['password']) : '';
    $mcode = getglobal('setting/machinecode');
    $datastr = $username . "\t" . $password . "\t" . $mcode;
    $data = dzzencode($datastr, $mcode, 0, 4);
    $authurl = APP_CHECK_URL . 'authlicense/getauth/' . $mcode . '/' . $data . '/' . TIMESTAMP;
    $response = json_decode(dfsockopen($authurl, 0, '', '', FALSE, '', 3), true);
    if (isset($response['authcode'])) {
        C::t('setting')->update('sitelicensedata', $response['authcode']);
        updatecache('setting');

    }
    if (isset($response['error'])) exit(json_encode(array('error' => $response['error'])));
    else exit(json_encode(array('success' => true)));

} else {
    $list = array(
        'data'=>array(
            'title' => lang('data_manage'),
            'lists' => array()
        ),
        'system' => array(
            'title' => lang('manage_tool'),
            'lists' => array()
        ),

        'app'=>array(
            'title' => lang('app_extensions'),
            'lists' => array()
        )
    );
    $list['data']['lists'][] = array(
        'identifier' => 'library',
        'name' => lang('library_file'),
        'desc'=>lang('library_file_desc'),
        'img' => 'dzz/images/app/library.png',
        'url' => 'index.php?mod=pichome&op=library',
    );
    $list['data']['lists'][] = array(
        'identifier' => 'alonepage',
        'name' => lang('alonepage_manage'),
        'desc'=>lang('alonepage_manage_desc'),
        'img' => 'dzz/images/app/alonepage.png',
        'url' => 'index.php?mod=alonepage',
    );
    $list['data']['lists'][] = array(
        'id' => 'intelligent',
        'name' => lang('intelligent'),
        'desc'=>lang('intelligent_desc'),
        'url' => 'index.php?mod=intelligent&op=setting',
        'img' => 'dzz/images/app/intelligent.png'
    );
    $list['data']['lists'][] = array(
        'identifier' => 'publish',
        'name' => lang('publish_manage'),
        'desc'=>lang('publish_manage_desc'),
        'img' => 'dzz/images/app/publish.png',
        'url' => 'index.php?mod=publish&op=admin',
    );
    $list['data']['lists'][] = array(
        'identifier' => 'banner',
        'name' => lang('banner_manage'),
        'desc'=>lang('banner_manage_desc'),
        'img' => 'dzz/images/app/banner.png',
        'url' => 'index.php?mod=banner&op=admin',
    );
    if (defined('LICENSE_VERSION') && LICENSE_VERSION == 'Enterprise') {
        $list['data']['lists'][] = array(
            'identifier' => 'tab',
            'name' => lang('tabgroup_manage'),
            'desc'=>lang('tabgroup_manage_desc'),
            'img' => 'data/attachment/appico/201712/21/tab.png',
            'url' => 'index.php?mod=tab&op=admin',
        );
    }

    //系统设置
    $list['system']['lists'][] = array(
        'id' => 'setting',
        'name' => lang('system_config'),
        'desc'=>lang('system_config_desc'),
        'url' => 'admin.php?mod=setting',
        'img' => 'dzz/images/app/setting.png'
    );
    //机构和用户,团队版
    if (defined('PICHOME_LIENCE')>0) {
        $list['system']['lists'][] = array(
            'id' => 'orguser',
            'name' => lang('user_manage'),
            'desc'=>lang('user_manage_desc'),
            'url' => 'admin.php?mod=orguser',
            'img' => 'dzz/images/app/user.png'
        );
    }
    $list['system']['lists'][] = array(
        'id' => 'system',
        'name' => lang('system_tools'),
        'desc'=>lang('system_tools_desc'),
        'url' => 'admin.php?mod=system',
        'img' => 'dzz/images/app/systemTool.png'
    );
    $list['system']['lists'][] = array(
        'id' => 'systemlog',
        'name' => lang('system_log'),
        'desc'=>lang('system_log_desc'),
        'url' => 'admin.php?mod=systemlog',
        'img' => 'dzz/images/app/log.png'
    );



    $list['system']['lists'][] = array(
        'identifier' => 'shares',
        'name' => lang('share_manage'),
        'desc'=>lang('share_manage_desc'),
        'img' => 'dzz/images/app/share.png',
        'url' => 'index.php?mod=shares&op=admin',
    );
    $list['system']['lists'][] = array(
        'id' => 'storagesetting',
        'name' => lang('storage_manage'),
        'desc'=>lang('storage_manage_desc'),
        'url' => 'index.php?mod=pichome&op=storagesetting',
        'img' => 'dzz/images/app/storage.png'
    );
    $list['system']['lists'][] = array(
        'id' => 'search',
        'name' => lang('search_setting'),
        'desc'=>lang('search_setting_desc'),
        'url' => 'index.php?mod=search&op=setting',
        'img' => 'dzz/images/app/searchset.png'
    );
    $list['system']['lists'][] = array(
        'id' => 'lang',
        'name' => lang('international'),
        'desc'=>lang('international_desc'),
        'url' => 'index.php?mod=lang&op=admin',
        'img' => 'dzz/images/app/lang.png'
    );
    if (defined('PICHOME_LIENCE') && PICHOME_LIENCE >0) {
        $list['system']['lists'][] = array(
            'id' => 'stats',
            'name' => lang('stats'),
            'desc' => lang('stats_desc'),
            'url' => 'index.php?mod=stats',
            'img' => 'dzz/images/app/stats.png'
        );
    }
    if (defined('LICENSE_VERSION') && LICENSE_VERSION == 'Enterprise') {
        $list['system']['lists'][] = array(
            'id' => 'fileCollect',
            'name' => lang('collection_manage'),
            'desc'=>lang('collection_manage_desc'),
            'url' => 'index.php?mod=fileCollect&op=setting',
            'img' => 'dzz/images/app/filecollect.png'
        );
    }


    $appdata = DB::fetch_all("select appid,appname,appdesc,appico,appurl,app_path,identifier,appadminurl,showadmin from %t where ((`group`=3 and isshow>0) OR appadminurl!='')  and `available`>0 order by appid", array('app_market'));

    foreach ($appdata as $k => $v) {
        if (!$v['showadmin']) continue;
        if (!defined('PICHOME_LIENCE')) {
            if ($v['identifier'] == 'orguser') continue;
            if ($v['identifier'] == 'fileCollect') continue;
        }
        if (defined('LICENSE_VERSION') && LICENSE_VERSION == 'Enterprise') {

        }elseif(defined('LICENSE_VERSION') && LICENSE_VERSION == 'Team') {
            if($v['identifier']=='fileCollect') continue;
            if($v['identifier']=='tab') continue;
        }else{
            if($v['identifier']=='fileCollect') continue;
            if($v['identifier']=='stats') continue;
            if($v['identifier']=='orguser') continue;
            if($v['identifier']=='tab') continue;
        }

        if ($v['appico'] != 'dzz/images/default/icodefault.png' && !preg_match("/^(http|ftp|https|mms)\:\/\/(.+?)/i", $v['appico'])) {
            $v['appico'] = $_G['setting']['attachurl'] . $v['appico'];
        }
        $v['name'] = $v['appname'];
        $v['img'] = $v['appico'];
        $v['desc'] = $v['appdesc'];
        $v['url'] = $v['appadminurl'] ? replace_canshu($v['appadminurl']) : replace_canshu($v['appurl']);

//        if ('appname' != lang('appname', array(), null, ($v['app_path'] ? $v['app_path'] : 'dzz') . '/' . $v['identifier'])) {
//            $v['name'] = lang('appname', array(), null, ($v['app_path'] ? $v['app_path'] : 'dzz') . '/' . $v['identifier']);
//        }

        if(!in_array($v['identifier'],array_column($list['system']['lists'],'id')) && !in_array($v['identifier'],array_column($list['data']['lists'],'id'))){
            $list['app']['lists'][] = $v;
        }
    }


    $list_json = json_encode(array_values($list));
    $version_name = defined('LICENSE_VERSION') ? lang(LICENSE_VERSION) : lang('Home');
    $versioncode = explode('.',CORE_VERSION);
    unset($versioncode[0]);
    $version = implode('.',$versioncode);

    $limitusernum = defined('LICENSE_LIMIT') ? LICENSE_LIMIT : 1;
    if (defined('NOLIMITUSER')) $limitusernum = lang('unlimited');
    $authdate = defined('LICENSE_CTIME') ? dgmdate(LICENSE_CTIME, 'Y-m-d H:i:s') : '';
    include template('page/index');
}
	
    
    