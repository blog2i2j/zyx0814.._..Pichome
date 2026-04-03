<?php
$appid = isset($_GET['appid']) ? trim($_GET['appid']):'';
if(!$appid){
    exit('appid is empty');
}
$tagText = 'tag_'.$appid.'.txt';
$handle = fopen($tagText,'a+');
foreach (DB::fetch_all("select rt.tid,t.tagname from %t rt left join %t t on rt.tid = t.tid 
                        where rt.appid = %s",array('pichome_resources_tag','pichome_tag',$appid)) as $v){
 fwrite($handle,$v['tagname']."\n");
}
fclose($handle);
exit('success');