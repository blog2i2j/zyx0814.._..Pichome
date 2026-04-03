<?php

class baiLian {

    private $apikey='';//apikey
    private $apiurl='https://dashscope.aliyuncs.com/compatible-mode/v1';//数据接收计数器

    private $params=[];
    private $data_buffer='';
    private $total_tokens=0;
    private $config=null;
    public function __construct($config=null) {
      if(empty($config)){
          $config=C::t('setting')->fetch('setting_bailian',true);
      }

      if($config['apikey']) $this->apikey = $config['apikey'];

      if($config['apiurl']) $this->apiurl = $config['apiurl'];

      $this->config=$config;
    }
    //列出本地可用的模型。
    public function modelList(){
        $data=array();
        foreach(DB::fetch_all("select * from %t where 1 order by vendor asc ,dateline desc",array('bailian_model')) as $value){

            $data[$value['model']]=$value;
        }
        return $data;
    }


    //生成chat completion
    public function chat($model,$messages=array(),$stream=true,$options=array()){
        $headers = array(
            'Content-Type: application/json'
        );
        if($this->apikey){
            $headers[]= 'Authorization: Bearer '.$this->apikey;
        }
        $params=array(
            'model'=>$model,
            'messages'=>$messages,
            'stream'=>$stream,
            'stream_options'=>array(
                'include_usage'=>true
            )
        );

        if($options){
            $params=array(array_merge($params,$options));
        }

        $url=$this->apiurl.'/chat/completions';
        //runlog('bailian111',json_encode($params,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        if($stream){
            $result=$this->streamRequest($url,$headers,json_encode($params,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

        }else{
            $result=$this->request($url,'POST',json_encode($params,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),$headers);
        }
        //runlog('bailian111',json_encode($result,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        return $result;
    }
    public function generate($model,$prompt='',$images=[],$stream=false,$options=array()){
        $headers = array(
            'Content-Type: application/json'
        );
        if($this->apikey){
            $headers[]= 'Authorization: Bearer '.$this->apikey;
        }
        $params=array(
            'model'=>$model,
        );

        $contents=array();

        if($images){
            foreach($images as $image){
                $contents[]=array(
                    "type"=>'image_url',
                    'image_url'=>array(
                        'url'=>$image
                    )
                );
            }
        }
        if($prompt){
            $contents[]=array(
                "type"=>'text',
                'text'=>$prompt
            );
        }
        $message=array();
        $messages[]=array(
            'role'=>'user',
            'content'=>$contents
        );
        $params['messages']=$messages;
        foreach($options as $key =>$val){
            $params[$key]=$val;
        }

        $url=$this->apiurl.'/chat/completions';

        if($stream){
            $result=$this->streamRequest($url,$headers,json_encode($params,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        }else{
            //echo json_encode($params,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

            $result=$this->request($url,'POST',json_encode($params,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),$headers);
        }
        return $result;
    }
    public function getAiData($params){
        $suffix='';
        $imgurl=$params['imgurl'];
        $promptdata=$params['promptdata'];
        $prompts=$promptdata['prompts'];
        if($promptdata['cate'] == 1){
            $suffix=lang('ai_tag_template_end');
        }

        //获取第一个prompt
        $prompt0=array_shift($prompts);
        $prompt1=$prompts[0];
        if($prompt1['model']){
            $suffix0='';
        }else{
            $suffix0=$suffix;
        }
        $imagebase64=$this->getImageData($imgurl);

        $ret0=$this->generate($prompt0['model'],$prompt0['prompt'].$suffix0,[$imagebase64]);
        $data=array(
            'code'=>$ret0['code'],
            'message'=>$ret0['message'],
            'content'=>$ret0['choices'][0]['message']['content'],
            'totaltoken'=>$ret0['usage']['total_tokens']
        );

       // runlog('bailian',json_encode($prompt0,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) .'==='.json_encode($ret0,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        if($ret0['error']){
            if(is_array($ret0['error'])) {
                $data = array();
                if (isset($ret['error']['code'])) {
                    $data['code'] = $ret0['error']['code'];
                }
                if (isset($ret['error']['message'])) {
                    $data['message'] = $ret0['error']['message'];
                }
            }else{
                $data=array(
                    'code'=>500,
                    'message'=>$ret0['error'],
                );
            }

            \dzz_process::unlock($params['processname']);
            return $data;
        }
        //其他优化代码
        $prefix='"'.$data['content'].'" ';

        foreach($prompts as $prompt){
           if(empty($prompt['model'])) continue;
            $ret=$this->generate($prompt['model'],$prefix.$prompt['prompt'].$suffix);

            $prompt['prompt']=$prefix.$prompt['prompt'].$suffix;
           // runlog('bailian',$prefix.json_encode($prompt,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) .'==='.json_encode($ret,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
            if($ret['error']) continue;
            $response1=$ret['choices'][0]['message']['content'];
            if(empty($response1)) continue;
            $prefix='"'. $response1.'"';

            $data['totaltoken']+=$ret['usage']['total_tokens'];
            $data['content']= $response1;
        }
        \dzz_process::unlock($params['processname']);
        return $data;
    }
    public function getChatData($params){
        $this->params=$params;

        $messages=$this->getMessages($params);

        $model=$this->config['chatModel'];

        if(empty($model)){
            return array('error'=>'Chat Model missing');
        }
        $ret=$this->chat($model,$messages);
        return $ret;
    }
    public function callback($ch,$data) {

//        if(empty($data)){
//            return strlen($data);
//        }


        $arr=explode('data: ',$data);

        foreach($arr as $v) {
            if (empty($v)) continue;
            if (trim($v)=='[DONE]') {//已经全部结束

                $questions = [
                    [
                        'content' => json_encode([
                            "role" => "assistant",
                            "content" => $this->data_buffer,
                        ], JSON_UNESCAPED_UNICODE),
                        'totaltoken' => $this->total_tokens
                    ]
                ];
                $this->insetMessageData($questions);

                \dzz_process::unlock($this->params['processname']);
                $this->end();
                return strlen($data);
            }
            $result = json_decode(trim($v), TRUE);

            if (isset($result['error'])) {

                \dzz_process::unlock($this->params['processname']);
                $this->end($result['error']['message']);
                return strlen($data);
            }

            if(is_array($result['choices'])) {
                if ($result['choices']) {
                    $content = $result['choices'][0]['delta']['content'];
                } else {
                    $content = '';
                }
                if ($result['usage'] && $result['usage']['total_tokens']) {
                    $this->total_tokens += $result['usage']['total_tokens'];
                }
                $this->data_buffer .= $content;
                $this->write($content);
            }


        }
        return strlen($data);
    }
    public  function write($content = NULL, $flush=TRUE){
        if($content != NULL){
            echo 'data: '.json_encode(['time'=>date('Y-m-d H:i:s'), 'content'=>$content], JSON_UNESCAPED_UNICODE).PHP_EOL.PHP_EOL;
        }

        if($flush){
            flush();
        }
    }

    public  function end($content = NULL){
        $this->data_buffer=0;
        echo "message: close" . PHP_EOL;
        if(!empty($content)){
            echo 'data: '.json_encode(['time'=>date('Y-m-d H:i:s'), 'content'=>$content], JSON_UNESCAPED_UNICODE).PHP_EOL.PHP_EOL;
        }
        echo 'retry: 86400000'.PHP_EOL;
        echo 'event: close'.PHP_EOL;
        echo 'data: Connection closed'.PHP_EOL.PHP_EOL;
        flush();
    }


    public function getImageData($imgUrl,$prefix=1)
    {
        if(!$imgedata = file_get_contents($imgUrl)){
            return false;
        }
        $base64 = base64_encode($imgedata);
        if(!$prefix){
            return $base64;
        }

        if (!$arr = getimagesize($imgUrl)) return false;
        $mime = $arr['mime'];
        if (!in_array($mime, array('image/jpeg', 'image/png', 'image/bmp', 'image/webp', 'image/gif'))) return false;

        return "data:" . $mime . ";base64," . $base64;

    }
    private function getMessages($params)
    {

        $messages=[];
        $messagedatas = $this->getMessageData($params);
        if ($messagedatas) {
            foreach ($messagedatas as $v) {
                $messagedata = json_decode($v, true);
                $messages[] = $messagedata;
            }
            $newtext = [
                "role" => "user",
                "content" =>$params['question']
            ];
            $messages[] = $newtext;
            $questions = [
                ['content'=> json_encode($newtext)]
            ];
            $this->insetMessageData($questions);
        } else {

            $contents=array();
            if($params['imgurl']){
                if($imagedata =  $this->getImageData($params['imgurl'])){
                    $contents[]=array(
                        'type'=>'image_url',
                        'image_url'=>array(
                            'url'=>$imagedata
                        )
                    );
                }
                $contents[]=array(
                    'type'=>'text',
                    'text'=>$params['question']
                );
                $newtext = [
                    "role" => "user",
                    "content" =>  $contents,
                ];
            }else{
                $newtext = [
                    "role" => "user",
                    "content" =>  $params['question']
                ];
            }


            $messages[]=$newtext;
            $questions = [
                ['content'=> json_encode($newtext)]
            ];
            $this->insetMessageData($questions);
        }

        return $messages;

    }

    private function getMessageData($params)
    {   $messagedatas = [];
        foreach(C::t('#aliBaiLian#bailian_chat')->fetchContentByIdvalue($params['idval'],$params['idtype']) as $v){
            $messagedatas[] = $v['content'];
        }
        return $messagedatas;
    }

    private function insetMessageData($messagedatas){

        foreach($messagedatas as $v){

            $cdata = json_decode($v['content'],true);
            if(!$cdata['role']) continue;
            $setarr = [
                'idval'=>$this->params['idval'],
                'idtype'=>$this->params['idtype'],
                'role'=>$cdata['role'],
                'content'=>$v['content'],
                'totaltoken'=>isset($v['totaltoken']) ? intval($v['totaltoken']):0
            ];
            C::t('#aliBaiLian#bailian_chat')->insertData($setarr);
        }

    }
    public  function request($url,$method='GET',$param = array(),$headers=array(),$raw=0)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 注意：在生产环境中应启用 SSL 验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 注意：同上
        if($method=='POST' && $param) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1200);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
       // curl_setopt($ch, CURLINFO_HEADER_OUT , 1);
       // curl_setopt($ch, CURLOPT_HEADER  , true);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($status == 0) {
            $ret = array('error'=> curl_error($ch));
        } else {
            $ret = $raw?$result:json_decode($result, true);

        }
        curl_close($ch);

        return $ret;
    }

    public function streamRequest(string $url, $headers = [], $postData = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // 不将响应保存为字符串，直接处理
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 注意：在生产环境中应启用 SSL 验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 注意：同上
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, is_array($postData) || !empty($postData));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, [$this,'callback']);


        // 执行请求并获取响应
        curl_exec($ch);

        // 检查是否有错误发生
        if (curl_errno($ch)) {
            return array('error'=>curl_error($ch));
        }
        // 关闭 cURL 句柄
        curl_close($ch);
        return true;
    }

}


