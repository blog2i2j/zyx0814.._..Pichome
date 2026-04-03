<?php
$microtime = microtime(true);

// 将时间戳转换为毫秒
$timestampInMilliseconds = round($microtime * 1000);
echo $timestampInMilliseconds;die;
if (($body_stream = file_get_contents("php://input"))===FALSE){
    exit("Bad Request");
}
$data = json_decode($body_stream, TRUE);
$vpath = $data['path'];
$lastUpdate = $data['modificationTime'];
$vappname = $data['name'];
$appid = DB::result_first("select appid from %t where path = %s and appname = %s ",['pichome_vapp',$vpath,$vappname]);
if($appid){
    //查询需要更新的数据

}
$rdata = [
    ['id'=>'LRUPGQ3WFEL3V',
        'updateData'=>
            [
                'tags'=> ['vvvv','defg'],
                'name'=>'test12345',
                'star'=>5,
                'updateDateline'=>1739502472750
            ]
    ]
];
echo json_encode($rdata);