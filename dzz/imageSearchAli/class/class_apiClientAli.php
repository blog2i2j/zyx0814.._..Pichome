<?php

class apiClientAli
{
    //百炼大模型
    private $apiurl = "https://dashscope.aliyuncs.com/api/v1/services/embeddings/multimodal-embedding/multimodal-embedding";
    private $apikey = '';//百炼API-KEY
    private $model = 'multimodal-embedding-v1';

    //向量检索服务DashVector:
    private $apikeyDashVector = '';

    private $endpoint = "";

    public $collectionname='pichomeVector';
    function __construct($setting)
    {
        if ($setting['apiurl']) $this->apiurl = $setting['apiurl'];
        if ($setting['apikey']) $this->apikey = $setting['apikey'];
        if ($setting['model']) $this->apikey = $setting['model'];
        if ($setting['apikeyDashVector']) $this->apikeyDashVector = $setting['apikeyDashVector'];
        if ($setting['endpoint']) $this->endpoint = $setting['endpoint'];
        return $this;
    }

    /*
     * @param $arr
     {
          "rid": "string",//rid
          "url": "string",//图片地址或base64编码二选一
          "base64": "string"
        }
     */
    function getBase64ByUrl($imgeurl, $mime = '')
    { //根据文件获取base64编码
        if (!$arr = getimagesize($imgeurl)) return false;
        $mime = $arr['mime'];
        if (!in_array($mime, array('image/jpeg', 'image/png', 'image/bmp', 'image/webp'))) return false;
        $base64 = base64_encode(file_get_contents($imgeurl));
        return "data:" . $mime . ";base64," . $base64;
    }

    public function getEmbedding($arr)
    {
        $url = $this->apiurl;
        $headers = array(
            'Authorization:Bearer ' . $this->apikey,
            'Content-Type: application/json'
        );
        $inputs = array();

        foreach ($arr as $key => $value) {
            if ($key == 'text') {
                $inputs[] = array(
                    'text' => $value
                );
            } elseif ($key == 'image') {

                if ($base64 = $this->getBase64ByUrl($value)) {
                    $inputs[] = array(
                        'image' => $base64
                    );
                }
            } elseif ($key == 'video') {
                $inputs[] = array(
                    'video' => $value
                );
            }

        }

        if (empty($inputs)) return false;

        $post_data = array(
            'model' => $this->model,
            'input' => array(
                'contents'=>$inputs
            )
        );
        $json= json_encode($post_data);

        $ret = self::request($url, 'POST', $json, $headers);
        return $ret;
    }

    public function media_add($rid, $inputs, $fields)
    {

        if ($inputs) {

            $ret = $this->getEmbedding($inputs);
            if ($ret['code'] != 0) return $ret;

            $data=array('id'=>$rid);
            foreach($ret['output']['embeddings'] as $value){
                $data['vectors'][$value['type']]=$value['embedding'];
            }
            $data['fields']=$fields;
            $ret=$this->docInsert(array($data) );
            return $ret;
        }
        return false;

    }
    public function media_search($inputs ,$filter='',$topk=100,$distance=0)
    {

        if ($inputs) {
            $ret = $this->getEmbedding($inputs);
            if ($ret['code'] != 0) return $ret;

            $data=array(
                'topk'=>$topk,
//                'rerank'=>array(
//                    "ranker_name"=>"weighted",
//                    "ranker_params"=>array(
//                        "weights"=>json_encode(array(
//                            //"text"=>0.2,
//                            "image"=>1,
//                            "video"=>1
//                        ))
//                    )
//                )
            );
            if($filter){
                $data['filter']= $filter;
            }

            foreach($ret['output']['embeddings'] as $value){
                $data['vectors'][$value['type']]=array(
                    'vector'=>$value['embedding'],
                    'param'=>array('radius'=>$distance)
                );
            }
            if(empty($data['vectors']['image'])){
                $data['vectors']['image']=$data['vectors']['text'];
            }
            unset($data['vectors']['text']);
            $ret=$this->docQuery($data);
            return $ret;
        }
        return false;
    }

    public function initCollection($collectionname='')
    {
        //查询是否已经有此集合
        if(empty($collectionname)){
            $collectionname=$this->collectionname;
        }
        $ret = $this->infoCollection($collectionname);
        if ($ret['code'] === 0) {
            return $ret;
        }
        //创建集合
        $data = array(
            'name' => $collectionname,
            'vectors_schema' => array(
                'text' => array(
                    'dimension' => 1024,
                    'metric' => 'cosine'
                ),
                'image' => array(
                    'dimension' => 1024,
                    'metric' => 'cosine'
                ),
                'video' => array(
                    'dimension' => 1024,
                    'metric' => 'cosine'
                )
            ),
            'fields_schema' => array(
                'appid' => 'String', //所在库appid
                'pathkey' => 'String', //所在目录路径
                'name' => 'String',//文件名称
                'isdelete' => 'bool'
            )
        );
        $ret = $this->createCollection($collectionname, $data['vectors_schema'], $data['fields_schema']);
        return $ret;
    }

    public function createCollection($collectionname='', $vectors_schema = array(), $fields_schemas = array())
    {
        $url = 'https://' . $this->endpoint . '/v1/collections';
        if(empty($collectionname)){
            $collectionname=$this->collectionname;
        }
        $data = array();
        $data['name'] = $collectionname;
        if (count($vectors_schema) == 1) {

            $data['dimension'] = $vectors_schema['dimension'];
            $data['metric'] = $vectors_schema['metric'] ? $vectors_schema['metric'] : 'Cosine';
        } elseif (count($vectors_schema) > 1) { //m
            foreach ($vectors_schema as $key => $value) {
                $data['vectors_schema'][$key]['dimension'] = $value['dimension'];
                $data['vectors_schema'][$key]['metric'] = $value['metric'] ? $value['metric'] : 'Cosine';
            }
        } else {
            return false;
        }

        if ($fields_schemas) $data['fields_schema'] = $fields_schemas;
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,
            'Content-Type: application/json'
        );
        $ret = self::request($url, 'POST', json_encode($data), $headers);
        return $ret;
    }

    public function deleteCollection($name)
    {

        $url = 'https://' . $this->endpoint . 'v1/collections/' . $name;
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,
        );
        $ret = self::request($url, 'DELETE', array(), $headers);
        return $ret;
    }

    public function listCollection($name)
    {//获取集合列表

        $url = 'https://' . $this->endpoint . '/v1/collections';
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,
        );
        $ret = self::request($url, 'GET', array(), $headers);
        return $ret;
    }

    public function infoCollection($name)
    {//获取集合信息

        $url = 'https://' . $this->endpoint . '/v1/collections/' . $name;
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,
        );
        $ret = self::request($url, 'GET', array(), $headers);
        return $ret;
    }

    public function statsCollection($name)
    {//获取集合统计信息

        $url = 'https://' . $this->endpoint . '/v1/collections/' . $name . '/stats';
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,
        );
        $ret = self::request($url, 'GET', array(), $headers);
        return $ret;
    }

    public function docQuery($arr,$cllection='')
    {
        if(empty($cllection)){
            $cllection=$this->collectionname;
        }
        $url = 'https://' . $this->endpoint . '/v1/collections/' . $cllection . '/query';
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,
            'Content-Type: application/json'
        );
        $data = $arr;

        $ret = self::request($url, 'POST', json_encode($data), $headers);
        return $ret;
    }
    public function docInsert($docs,$cllection='' )
    {
        if(empty($cllection)){
            $cllection=$this->collectionname;
        }
        $url = 'https://' . $this->endpoint . '/v1/collections/' . $cllection . '/docs/upsert';
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,
            'Content-Type: application/json'
        );

        $data = array('docs'=>$docs);

        $ret = self::request($url, 'POST', json_encode($data), $headers);

        return $ret;

    }

    public function docGet( $ids = array() ,$cllection='')
    {
        if (is_array($ids)) $ids = implode(',', $ids);
        if(empty($cllection)){
            $cllection=$this->collectionname;
        }
        $url = 'https://' . $this->endpoint . '/v1/collections/' . $cllection . '/docs?ids=' . $ids;
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,

        );

        $ret = self::request($url, 'GET', array(), $headers);
        return $ret;

    }

    public function docDelete( $ids,$cllection='')
    {
        if (empty($ids)) {
            return false;
        }
        if(empty($cllection)){
            $cllection=$this->collectionname;
        }
        if (!is_array($ids)) $ids = array($ids);
        $url = 'https://' . $this->endpoint . '/v1/collections/' . $cllection . '/docs';
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,
            'Content-Type: application/json'
        );
        $data['ids'] = $ids;
        $ret = self::request($url, 'DELETE', json_encode($data), $headers);
        return $ret;
    }



    function docQueryGroupBy($arr,$collection)
    {
        if(empty($cllection)){
            $cllection=$this->collectionname;
        }
        $url = 'https://' . $this->endpoint . '/v1/collections/' . $collection . '/query';
        $headers = array(
            'dashvector-auth-token:' . $this->apikeyDashVector,
            'Content-Type: application/json'
        );
        $data = $arr;
        $ret = self::request($url, 'POST', json_encode($data), $headers);
        return $ret;
    }

    public function request($url, $method = 'GET', $param = array(), $headers = array(), $raw = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($param) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        }
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1200);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        // curl_setopt($ch, CURLINFO_HEADER_OUT , 1);
        //  curl_setopt($ch, CURLOPT_HEADER  , true);
        $result = curl_exec($ch);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($status == 0) {
            $ret = array('error' => curl_error($ch));
        } else {
            $ret = $raw ? $result : json_decode($result, true);

        }
        curl_close($ch);

        return $ret;
    }
}