<?php
/*
 * @copyright   QiaoQiaoShiDai Internet Technology(Shanghai)Co.,Ltd
 * @license     https://www.oaooa.com/licenses/
 * 
 * @link        https://www.oaooa.com
 * @author      zyx(zyx@oaooa.com)
 */

define('APPTYPEID', 1);
define('CURSCRIPT', 'dzz');
define('DZZSCRIPT', basename(__FILE__));
define('BASESCRIPT', basename(__FILE__));
$routefile = 'data/cache/'. 'route.php';
$routes = require_once $routefile;

if(isset($routes['pathinfo'])){
    if ((!isset($_SERVER['PATH_INFO']) || !$_SERVER['PATH_INFO'])&& isset($_SERVER['REQUEST_URI'])) {
        $_SERVER['PATH_INFO'] = strstr($_SERVER['REQUEST_URI'], '?', true);
        if ($_SERVER['PATH_INFO'] === false) {
            $_SERVER['PATH_INFO'] = $_SERVER['REQUEST_URI'];
        }
    }
    $pathInfo = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO']):'';

    if (strpos($pathInfo, '/') === 0) {
        $pathInfo = substr($pathInfo, 1);
    }

    $url = array_search($pathInfo,$routes);
    if($url){
        $queryString = parse_url($url, PHP_URL_QUERY);
        $queryString1 = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

        $hash = parse_url($url, PHP_URL_FRAGMENT);
        $hashparam=null;
        parse_str($queryString, $_GET);
        $get=array();
        if(!empty($queryString1)) parse_str($queryString1, $get);
        if(is_array($get)){
            if($_GET){
                $_GET=array_merge($_GET,$get);
            }else{
                $_GET=$get;
            }
        }
        if(isset($_GET['keyword'])){

        }
        if ($hash) {
            parse_str($hash, $hashparam);
        }
        if(isset($hashparam)) $_GET['hashparams'] = json_encode($hashparam);

    }

}

require __DIR__.'/core/dzzstart.php';
