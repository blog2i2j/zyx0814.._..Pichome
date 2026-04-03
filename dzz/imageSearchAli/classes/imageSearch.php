<?php
namespace dzz\imageSearchAli\classes;

use \core as C;
use \DB as DB;
use \IO as IO;

require_once(DZZ_ROOT . './dzz/imageSearchAli/class/class_apiClientAli.php' );

class imageSearch{
    private $cachetime=3600;
    private $limit=500;
    private $video_exts=array('mp4','mpeg','mpg','avi','webm','flv','mkv','mov');

    public function run(&$result,$params=array()){
        if(empty($result['rids']))   $result['rids']=array();
        $setting = C::t('setting')->fetch('imageSearchAli_setting', true);
        if(!$setting['status']){
            return;
        }

        $api=new \apiClientAli($setting);
        $inputs=array();


        if($params['aid']) {
            if (!$attachment = C::t('attachment')->fetch($params['aid'])) {
                return;
            }
            $url = getglobal('localurl') . 'index.php?mod=io&op=createThumb&create=1&size=small&path=' . dzzencode('attach::' . $params['aid']);
            $ext = $attachment['filetype'];
            if (in_array($ext, $this->video_exts)) {
                if ($attachment['filesize'] > 0 && $attachment['filesize'] <= 1024 * 1024 * 10) {
                    $inputs['vedio'] = $url;
                } else {
                    return;
                }

            } else {
                $inputs['image'] = $url;
            }
        }elseif($params['keyword']){
            $inputs['text']=trim($params['keyword']);
        }
        $filters=array();
        if(is_array($params['appids'])){
            $filters[]="appid in (".dimplode($params['appids']).')';
        }

        $limit=$this->limit;

        $distance_threshold=isset($params['distance'])?intval($params['distance']):intval($setting['limit']);

        $maxdistance=$distance_threshold>0?$distance_threshold/100:0;

        //增加缓存

        $cachekey='imageSearchAli_'.md5(json_encode(array_merge($inputs,$filters,(array)$limit,(array)$maxdistance)));

        if($cache=memory('get',$cachekey)){
            $result['rids']=array_keys($cache);
            $result['distances']=$cache;
        }
        elseif($inputs) {

           if(is_array($filters)) $filter=implode(' and ', $filters);
           else $filter='';

            $ret = $api->media_search($inputs,$filter , $limit, $maxdistance);

            if ($ret['code'] === 0) {

                $distances = array();
                foreach ($ret['output'] as $value) {

                    $ad = array(
                        'distance' => $value['score'],
                        'similarity' => intval((1-$value['score']) * 100),
                    );
                    if (isset($value['timestamp'])) {
                        $ad['timestamp'] = ($value['timestamp']);
                    }
                    if (!isset($distances[$value['id']])) {
                        $distances[$value['id']] = array($ad);
                    } else {
                        $distances[$value['id']][] = $ad;
                    }
                }
                if ($distances) {
                    $result['distances'] = $distances;
                    $result['rids'] = array_keys($distances);
                } else {
                    $result['rids'] = array('notfound');
                    $result['distances'] = array();
                }

                memory('set', $cachekey, $result['distances'], $this->cachetime);
            } else {
                $result = array();
                runlog('imageSearchAli', $params['aid'] . '====' . json_encode($ret, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}