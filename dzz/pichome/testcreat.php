<?php
$aspectRatio = isset($_GET['ratio']) ? intval($_GET['ratio']):1;
$minsize = isset($_GET['minsize']) ? intval($_GET['minsize']):960;
function getBackgroundColor($imagePath) {
    $imagick = new Imagick($imagePath);
    $imagick->resizeImage(100, 100, Imagick::FILTER_LANCZOS, 1); // 缩小图像以加快处理速度

    // 获取右下角小区域的颜色
    $cornerWidth = 10; // 右下角区域的宽度
    $cornerHeight = 10; // 右下角区域的高度
    $rightBottomColor = getAverageColor($imagick, 90, 90, 100, 100);

    return $rightBottomColor;
}

function getAverageColor($imagick, $x1, $y1, $x2, $y2) {
    $rTotal = 0;
    $gTotal = 0;
    $bTotal = 0;
    $count = 0;

    for ($x = $x1; $x < $x2; $x++) {
        for ($y = $y1; $y < $y2; $y++) {
            $color = $imagick->getImagePixelColor($x, $y);
            $colorArray = $color->getColor();
            $r = round($colorArray['r']);
            $g = round($colorArray['g']);
            $b = round($colorArray['b']);
            $rTotal += $r;
            $gTotal += $g;
            $bTotal += $b;
            $count++;
        }
    }

    $rAvg = $rTotal / $count;
    $gAvg = $gTotal / $count;
    $bAvg = $bTotal / $count;

    return [$rAvg, $gAvg, $bAvg];
}


function generateThumbnailWithPadding($sourceImagePath, $thumbnailPath, $minWidth = 960, $aspectRatio = [3, 4], $paddingHeight = 50, $quality = 90) {
    // 获取原图的宽度和高度
    list($originalWidth, $originalHeight, $type) = getimagesize($sourceImagePath);

    if ($originalWidth === false || $originalHeight === false) {
        throw new Exception("无法获取图片尺寸");
    }

    // 计算缩放后的尺寸，确保宽高比与原图相同，并且最小宽度为 minWidth
    $newWidth = $minWidth;
    $newHeight = ($minWidth * $originalHeight) / $originalWidth;

    // 计算最终的缩略图高度，确保宽高比为 3:4
    $finalHeight = ($newWidth * $aspectRatio[1]) / $aspectRatio[0];

    // 如果最终高度小于缩放后的高度加上留白区域的高度，则增加留白区域的高度
    if ($finalHeight < $newHeight + $paddingHeight) {
        $finalHeight = $newHeight;
    }

    // 创建一个新的图像资源
    $newImage = imagecreatetruecolor($newWidth, $finalHeight);

    // 获取背景色
    $backgroundColor = getBackgroundColor($sourceImagePath);
    $bgColor = imagecolorallocate($newImage, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);

    // 填充背景色
    imagefilledrectangle($newImage, 0, 0, $newWidth, $finalHeight, $bgColor);

    // 根据原图的类型创建图像资源
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourceImagePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourceImagePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourceImagePath);
            break;
        case IMAGETYPE_BMP:
            $sourceImage = imagecreatefrombmp($sourceImagePath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourceImagePath);
            break;
        default:
            throw new Exception("不支持的图片格式");
    }

    // 使用高质量的重采样滤波器
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    // 保存缩略图，并设置图像质量
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $thumbnailPath, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $thumbnailPath, 9); // PNG 质量范围 0-9，9 是最高质量
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $thumbnailPath);
            break;
        case IMAGETYPE_BMP:
            imagebmp($newImage, $thumbnailPath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($newImage, $thumbnailPath, $quality);
            break;
    }

    // 释放内存
    imagedestroy($newImage);
    imagedestroy($sourceImage);
}
switch ($aspectRatio){
    case 0:
        $aspectRatio = [1,1];
        break;
    case 1:
        $aspectRatio = [3,4];
        break;
   /* case 2:
        $aspectRatio = [4,3];
        break;
    case 3:
        $aspectRatio = [16,9];
        break;
    case 4:
        $aspectRatio = [9,16];
        break;*/
    default :
        $aspectRatio = [3,4];
}
$originalpath = DZZ_ROOT.'./img/';
$thumppath = DZZ_ROOT.'./imgthumb/';
$handle = dir($originalpath);
if ($handle) {
    while (($filename = $handle->read()) !== false) {
        if ($filename != '.' && $filename != '..') {
            $sources = $originalpath . $filename;
            if (is_file($sources)) {
                $file_info = pathinfo($sources);
                // 取出文件名（不包括扩展名）
                $filename = $file_info['filename'];

                // 取出文件扩展名
                $extension = $file_info['extension'];
                $targetpath = dirname($thumppath);
                dmkdir($targetpath);
                $thumppath = $thumppath . $filename . '.' . $extension;
                try {
                    $thumbnailInfo = generateThumbnailWithPadding($sources, $thumppath, 960,$aspectRatio,40);
                    echo "缩略图宽度: " . $thumbnailInfo['width'] . " 像素\n";
                    echo "缩略图高度: " . $thumbnailInfo['height'] . " 像素\n";
                    echo "缩略图类型: " . $thumbnailInfo['type'] . "\n";
                    echo "缩略图大小: " . $thumbnailInfo['fileSize'] . " 字节\n";
                } catch (Exception $e) {
                    echo "错误: " . $e->getMessage() . "\n";
                }
            }
        }
    }
}