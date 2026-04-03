<?php
namespace dzz\imageColor\classes;

use \core as C;
use \DB as DB;
use \IO as IO;
use \dzz\exif\classes\getInfo as info;
class getthumb{

    public function run($data){
        $meta=$data;
        if(!in_array($meta['ext'],explode(',',getglobal('config/imagickthumext')))) return '';

       /* if (is_numeric($meta['path'])) {
            $target = C::t('thumb_cache')->fetch_path_by_aid($meta['path']);
            if($target)return array($target);
        }*/
        if (!extension_loaded('imagick')) {
            return '';
        } else{
            if(in_array($meta['ext'],array('ico','psd','png','tg+a','tiff','tif','cr2'))) $prefix='png';
            elseif(in_array($meta['ext'],array('ai','eps'))) $prefix='ai';
            else $prefix='';

            if($target=self::getThumb($meta,$prefix)){
                    return array($target);
            }

        }
    }
    public function getThumb($meta,$prefix=''){

        global $_G;

        if($meta['aid']){
            $attachment = IO::getMeta('attach::'.$meta['aid']);
        }else{
            $attachment = IO::getMeta($meta['rid']);
        }
        if(!$prefix) $prefix = 'jpg';
        $thumbext = 'webp';
        if(in_array($meta['ext'],array('gif'))){
            $thumbext = $meta['ext'];
        }
        $thumbpath = $this->getthumbpath('pichomethumb');
        if($meta['aid'])$thumbname = md5($meta['aid'].$meta['thumbsign']).'_original.'.$thumbext;
        else $thumbname = md5($meta['path'].$meta['thumbsign']).'_original.'.$thumbext;
        $target = $thumbpath.$thumbname;
        //本地路径
        $jpg=$_G['setting']['attachdir'].$target;

        $dirpath=dirname($jpg);
        dmkdir($dirpath);
        $file=IO::getStream($attachment['path']);
        try{
            $im=new \Imagick($file);
            //$icc_rgb = file_get_contents(DZZ_ROOT.'./dzz/imagick/icc/sRGB_v4_ICC_preference.icc');
            //$im->profileImage('icc', $icc_rgb);
            unset($icc_rgb);
            $this->autoRotateImage($im);
            $im->setIteratorIndex(0);
            $owidth=$im->getImageWidth();
            $oheight=$im->getImageHeight();
            //$whsize = $this->getImageThumbsize($owidth,$oheight,$meta['thumbwidth'],$meta['thumbheight']);

            if($owidth>$_G['setting']['thumbsize']['large']['width'] && $oheight>$_G['setting']['thumbsize']['large']['height']){
                $width=1920;
                $height=ceil($width*$oheight/$owidth);
            }else{
                $width=$owidth;
                $height=$oheight;
            }

            if($prefix=='ai'){
                $prefix='png';
                if($width<1920){
                    $width=1920;
                    $height=ceil($width*$oheight/$owidth);
                }
            }
            $im->scaleImage($width, $height, false);
            $im->stripImage(); //去除图片信息
            $im->setImageCompressionQuality(80); //图片质量

            $im->writeImage(($prefix?$prefix.':':'').$jpg);

            if($imginfo=@getimagesize($jpg)){
                if(!$meta['tmpfile']) {
                    $defaultspace = $_G['setting']['defaultspacesetting'];
                    if ($defaultspace['bz'] != 'dzz') {
                        //组合云端保存位置
                        $cloudpath = $defaultspace['bz'].':'.$defaultspace['did'] . ':/' .$target;
                        $filepath = IO::moveThumbFile($cloudpath, $jpg);
                        if (!isset($filepath['error'])) {
                            @unlink($jpg);
                            return $cloudpath;
                        } else {
                            runlog('imagick', 'uneable move  file to target:' . $jpg . $cloudpath);
                            return '';
                        }
                    }
                    return 'dzz::'.$target;
                }else{
                    return 'dzz::'.$target;
                }
            }else{
                runlog('imagick','uneable get size  file:'.$jpg);
                return '';
            }
        }catch(\Exception $e){
            $message= $e->getMessage();
            $message = diconv($message,'GBK','UTF-8');
            runlog('imagick',$message.' file:'.$file);
        };
        return '';
    }
    public static function getPath($dir = 'imagick')
    {
        global $_G;
        $target1 = $dir . '/index.html';
        $target_attach = $_G['setting']['attachdir'] .'./'. $target1;
        $targetpath = dirname($target_attach);
        dmkdir($targetpath);
        return $dir;
    }
    public static function scaleImage($width,$height,$owidth,$oheight) {
        if($owidth>$width && $oheight>$height){
            $or=$owidth/$oheight;
            $r=$width/$height;
            if($or>$r){
                if($oheight<$height){
                    $height=$oheight;
                    $width=$owidth;
                }else{
                    $width=ceil($height*$or);
                }

            }else{
                if($owidth<$width){
                    $height=$oheight;
                    $width=$owidth;
                }else{
                    $height=ceil($width/$or);
                }
            }

        }else{
            $width=$owidth;
            $height=$oheight;
        }
        //Return the results
        return array($width,$height);
    }

    public static function getthumbpath($dir = 'pichomethumb'){
        $subdir = $subdir1 = $subdir2 = '';
        $subdir1 = date('Ym');
        $subdir2 = date('d');
        $subdir = $subdir1 . '/' . $subdir2 . '/';
        // $target1 = $dir . '/' . $subdir . 'index.html';
        $target = $dir . '/' . $subdir;
        return $target;
    }
    public static function autoRotateImage($imgick) {
        $orientation = $imgick->getImageOrientation();
        switch ($orientation) {
            case \imagick::ORIENTATION_TOPRIGHT:
                // 水平翻转
                $imgick->flopImage();
                break;
            case \imagick::ORIENTATION_BOTTOMRIGHT:
                // 旋转180度
                $imgick->rotateImage(new ImagickPixel(), 180);
                break;
            case \imagick::ORIENTATION_BOTTOMLEFT:
                // 垂直翻转
                $imgick->flipImage();
                break;
            case \imagick::ORIENTATION_LEFTTOP:
                // 旋转90度并水平翻转
                $imgick->rotateImage(new ImagickPixel(), 90);
                $imgick->flopImage();
                break;
            case \imagick::ORIENTATION_RIGHTTOP:
                // 旋转90度
                $imgick->rotateImage(new ImagickPixel(), 90);
                break;
            case \imagick::ORIENTATION_RIGHTBOTTOM:
                // 旋转270度并水平翻转
                $imgick->rotateImage(new ImagickPixel(), 270);
                $imgick->flopImage();
                break;
            case \imagick::ORIENTATION_LEFTBOTTOM:
                // 旋转270度
                $imgick->rotateImage(new ImagickPixel(), 270);
                break;
            case \imagick::ORIENTATION_TOPLEFT:
                break;
        }

        // Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!
        $imgick->setImageOrientation(\imagick::ORIENTATION_TOPLEFT);
    }
}