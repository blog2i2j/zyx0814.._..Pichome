<?php
$lang =[];
clearstatcache();
set_time_limit(0);
ini_set('memory_limit', -1);
$fromlang = $_GET['from'] ? trim($_GET['from']):'zh-CN';
$tolang = $_GET['to'] ? trim($_GET['to']):'';
if(!$tolang) exit('缺少转换语言参数');
$langpath = DZZ_ROOT.'./core/language/';
//载入语言包
require $langpath.$fromlang.'/lang.php';

$tolangfile = $langpath.$tolang.'/lang.php';
if(!file_exists($tolangfile)){
    $todir = dirname($tolangfile);
    if(!is_dir($todir)){
        mkdir($todir,0777,true);
    }

}
$fromlangarr = $lang;
$lang = [];
// 初始化现有数组
$existingArray = [];
// 检查文件是否存在
if (file_exists($tolangfile)) {
    // 读取现有文件内容
    include $tolangfile;
    // 确保解析后的数组是一个关联数组
    if (!is_array($lang)) {
        $lang = [];
    }
    $existingArray =$lang;
}else{
    $existingArray=[];
}
print_r($existingArray);die;
$i = 1;
foreach($fromlangarr as $k=>$text){
    $hasHtmlTags = containsHtmlTags($text);
    $hasSpecialChars = containsSpecialChars($text);
    if($hasHtmlTags || $hasSpecialChars){
        $dom = new DOMDocument();
    // 抑制加载时的警告
        libxml_use_internal_errors(true);
    // 加载HTML内容
        $dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

    // 获取所有文本节点
        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()');
        foreach ($textNodes as $node) {
            $translatedText = translateText($fromlang,$tolang,$node->nodeValue);
            $node->nodeValue = $translatedText;
        }
        $finalString = '';
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            foreach ($body->childNodes as $child) {
                $finalString .= $dom->saveHTML($child);
            }
        }
        if($hasSpecialChars && !$hasHtmlTags){
            $p = $dom->getElementsByTagName('p')->item(0);
            if($p){
                $finalString = $p->nodeValue;
            }
        }

    }else{
        $finalString = translateText($fromlang,$tolang,$text);
    }
    $finalString = str_replace('"','',$finalString);
    $newArray = [$k=>$finalString];


// 合并新数组，覆盖重复的键
    $combinedArray = array_merge($existingArray, $newArray);
    array_walk_recursive($combinedArray, 'removeZWNBSP');
// 生成 PHP 代码
    $phpCode = "<?php\n\$lang = " . var_export($combinedArray, true) . ";";

// 写入文件
    if (file_put_contents($tolangfile, $phpCode) !== false) {
        $i++;
    } else {
        echo "写入文件时发生错误。\n";
        die;
    }

}
// 定义去除 ZWNBSP 符号的函数
function removeZWNBSP(&$item, $key) {
    if (is_string($item)) {
        $item = preg_replace('/\x{FEFF}/u', '', $item);
    }
}
function translateText($from,$to,$textstr){
    $textstr = urlencode($textstr);
    $url = 'https://api.microsofttranslator.com/V2/Ajax.svc/Translate?appId=DB50E2E9FBE2E92B103E696DCF4E3E512A8826FB&oncomplete=?&text='.$textstr.'&from='.$from.'&to='.$to;
    $result = curl_file_get_contents($url);
    return $result;
}
function containsSpecialChars($string) {
    // 使用正则表达式匹配特殊符号（如转义符号）
    return preg_match('/[\'\\\\=>]/', $string);
}
function containsHtmlTags($string) {
    // 使用正则表达式匹配 HTML 标签
    $htmlTagPattern = '/<[^>]+>/';
    // 检查字符串中是否包含 HTML 标签
    return  preg_match($htmlTagPattern, $string);
}
exit($i);