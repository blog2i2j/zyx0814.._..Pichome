<?php
if (!defined('IN_OAOOA')) {
    exit('Access Denied');
}

class  table_pichome_templatetagdata extends dzz_table
{
    public function __construct()
    {

        $this->_table = 'pichome_templatetagdata';
        $this->_pk = 'id';
        $this->_pre_cache_key = 'pichome_templatetagdata';
        //$this->_cache_ttl = 3600;
        parent::__construct();
    }
    //新建或修改单页
    public function insertdata($setarr){
        $olddata = [];
        $id = 0;
        if($setarr['id']) {
            $id = $setarr['id'];
            unset($setarr['id']);
            $olddata = parent::fetch($id);

        }
        $type = $setarr['type'];
        unset($setarr['type']);
        if($id){
            $setarr['id'] = $id;
        }

        if($setarr['tdata']){
            switch ($type){
                case 'rich_text':
                    $setarr['tdata'] = getcontentdata($setarr['tdata'],$olddata['tdata']);
                    break;
                case 'question':
                    if($olddata['tdata']){
                        $olddata['tdata'] = unserialize($olddata['tdata']);
                    }
                    foreach($setarr['tdata'] as $k=>$v){
                        $setarr['tdata'][$k]['answer']= getcontentdata($setarr['tdata'][$k]['answer'],$olddata['tdata'][$k]['answer']);
                    }
                    $setarr['tdata'] = serialize($setarr['tdata']);
                    break;
                default :
                    $naids =  [];
                    foreach($setarr['tdata'] as $v){
                        if(!empty($v['aid'])){
                            $naids[] = $v['aid'];
                        }
                    }
                    if($olddata){
                        if($olddata['tdata']){
                            $odata = unserialize($olddata['tdata']);
                        }
                        $oaids = [];
                        if(!empty($odata)) {
                            foreach ($odata as $idata) {
                                if (!empty($idata['aid'])) {
                                    $oaids[] = $idata['aid'];
                                }
                            }
                        }
                        $delaids = array_diff($oaids,$naids);
                        foreach($delaids as $v){
                            C::t('attachment')->delete_by_aid($v);
                        }
                        $naids = array_diff($naids,$oaids);
                        if($naids)  C::t('attachment')->addcopy_by_aid($naids);
                        $daids = array_diff($oaids,$naids);
                        if($daids) C::t('attachment')->addcopy_by_aid($daids,-1);
                    }
                    $setarr['tdata'] = serialize($setarr['tdata']);
                    break;
            }
        }

        if($id){
            if(parent::update($id,$setarr)){
                Hook::listen('lang_parse',$setarr,['setAlonpagetagdataLangData',$type]);
            }
            return $id;
        }else{
            if($id = parent::insert($setarr,1)){
                $setarr['id'] = $id;
                Hook::listen('lang_parse',$setarr,['setAlonpagetagdataLangData',$type]);
                return $id;
            }
        }
    }
    public function parserichtextdata($data){
        $pattern = "/(https?:\/\/)?\w+\.\w+\.\w+\.\w+?(:[0-9]+)?\/index\.php\?mod=io&amp;op=getfileStream&amp;path=(.+)/";
        $data= preg_replace_callback($pattern,function($matchs){

            return 'index.php?mod=io&op=getfileStream&path='.$matchs[3];

        },$data);

       $data= preg_replace_callback('/path=(\w+)&amp;aflag=(attach::\d+)/',function($matchs){
            if(isset($matchs[2])){
                return 'path='.dzzencode($matchs[2]);
            }

        },$data);

        return $data;
    }
    public function fetch_data_by_tidandtagtype($tid,$tagtype){
        $tagdata = [];
        foreach(DB::fetch_all("select * from %t where tid = %d order by disp asc",[$this->_table,$tid]) as $v){

            Hook::listen('lang_parse',$v,['getAlonpagetagdataLangData',$tagtype]);

            if($tagtype == 'rich_text'){
                $v['tdata'] = $this->parserichtextdata($v['tdata']);
            }else{

                $v['tdata'] = unserialize($v['tdata']);

                if($tagtype == 'question'){
                    $v['tdata'][0]['answer'] = $this->parserichtextdata($v['tdata'][0]['answer']);
                }
                foreach($v['tdata'] as $k=>$val){
                    if($val['aid']){
                        $v['tdata'][$k]['img']=($v['tdata'][$k]['imgurl'] =IO::getFileUri('attach::'.$val['aid']));
                    }

                    if(!$val['link']) $val['tdata'][$k]['url'] =  $val['linkval'] ? $val['linkval']:'';
                    else{
                        switch ($val['link']){
                            case 1:
                                $url = 'index.php?mod=pichome&op=fileview#appid='.$val['linkval'];
                                break;
                            case 2:
                                $url =  'index.php?mod=alonepage&op=view#id='.$val['linkval'];
                                break;
                            case 3:
                                if(is_array($val['linkval'])){
                                    $bid=array_pop($val['linkval']);
                                }else{
                                    $bid = $val['linkval'];
                                }
                                $bdata = C::t('pichome_banner')->fetch($bid);

                                if($bdata['btype'] == 4){//专辑
                                    $url = 'index.php?mod=banner&op=index&id=tb_'.$bdata['bdata'].'#id=tb_'.$bdata['bdata'];
                                }elseif($bdata['btype'] == 3){//链接
                                    $url = $bdata['bdata'];
                                }elseif($bdata['btype'] == 1){//智能数据
                                    $url = 'index.php?mod=intelligent&tid='. $bdata['bdata'];
                                 }elseif($bdata['btype'] == 6){//发布
                                    $url = 'index.php?mod=publish&id='. $bdata['bdata'];
                                }else{
                                    $url = 'index.php?mod=banner&op=index&id='.$bdata['bdata'].'#id='.$bdata['bdata'];
                                }

                               // $url = ($bdata['btype'] == 3) ? $bdata['bdata']:'index.php?mod=banner&op=index#id='.$bdata['bdata'];
                                break;
                            case 5:
                                if(is_array($val['linkval'])){
                                    $bid=array_pop($val['linkval']);
                                }else{
                                    $bid = $val['linkval'];
                                }
                                $bdata = C::t('#publish#publish_list')->fetch($bid);
                                if($bdata){
                                    $url = 'index.php?mod=publish&id='.$bdata['id'];
                                }
                                break;
                        }
                        if($url) {
                            if (getglobal('setting/pathinfo')) $path = C::t('pichome_route')->fetch_path_by_url($url);
                            else $path = '';

                            if ($path) {
                                $v['tdata'][$k]['url'] = getglobal('siteurl') . $path;
                            } else {
                                if (preg_match('/^https?:\/\//', $url)) {
                                    $v['tdata'][$k]['url'] = $url;
                                } else {
                                    $v['tdata'][$k]['url'] = getglobal('siteurl') . $url;
                                }

                            }
                        }
                    }
                }

            }

            Hook::listen('lang_parse',$v,['getAlonpagetagdataLangKey',$tagtype]);
            $v['tdid'] = $v['id'];
            unset($v['id']);
            $tagdata[] = $v;
        }

        return $tagdata;
    }


    public function delete_by_tid($tid){
        $i=0;
        foreach(DB::fetch_all("select id from %t where tid = %d",[$this->_table,$tid]) as $v){

            if($this->delete_by_id($v['id'])){
                $i++;
            }
        }
       return $i;
    }
    public function delete_by_id($id){
        if(!is_array($id)) $id = (array)$id;
        $i=0;
        foreach($id as $v){
            if($data=parent::fetch($v)) {
                $tag=C::t('pichome_templatetag')->fetch($data['tid']);
                $type = $tag['tagtype'];
                switch ($type){
                    case 'rich_text':
                        getcontentdata('',$data['tdata']);
                        break;
                    case 'question':
                        $tdata=unserialize($data['tdata']);
                        foreach($tdata as $k=>$value){
                           getcontentdata('',$value['answer']);
                        }
                        break;
                    default :
                        $tdata=unserialize($data['tdata']);
                        $aids =  [];
                        foreach($tdata as $value){
                            if($value['aid']) $aids[] = $value['aid'];
                        }
                        if($aids) C::t('attachment')->addcopy_by_aid($aids,-1);
                        break;
                }
                $cachename = 'templatetagdata_' . $v;
                C::t('cache')->delete_cachedata_by_cachename($cachename);
                HOOK::listen('lang_parse', $v, ['delAlonepagedataLangData']);
                if(parent::delete($v)){
                    $i++;
                }
            }
        }
        return $i;
    }

}