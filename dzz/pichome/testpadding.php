<?php
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

function generateThumbnailWithPadding($sourceImagePath, $thumbnailPath, $minSize = 480, $paddingHeight = 50) {
    // 获取原图的宽度和高度
    list($originalWidth, $originalHeight, $type) = getimagesize($sourceImagePath);

    if ($originalWidth === false || $originalHeight === false) {
        throw new Exception("无法获取图片尺寸");
    }

    // 定义图片类型的映射
    $imageTypes = [
        IMAGETYPE_JPEG => 'JPEG',
        IMAGETYPE_PNG => 'PNG',
        IMAGETYPE_GIF => 'GIF',
        IMAGETYPE_BMP => 'BMP',
        IMAGETYPE_WEBP => 'WEBP'
    ];

    if (!isset($imageTypes[$type])) {
        throw new Exception("不支持的图片格式");
    }

    // 计算初始缩放比例，确保最短边不小于 minSize
    $shortestSide = min($originalWidth, $originalHeight);
    $initialRatio = $minSize / $shortestSide;

    // 初始宽度和高度
    $newWidth = $originalWidth * $initialRatio;
    $newHeight = $originalHeight * $initialRatio;

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

    // 创建一个新的图像资源
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // 复制并调整原图到新图像
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    // 创建一个新的图像资源
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // 复制并调整原图到新图像
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    // 创建最终的图像资源，包括空白区域
    $finalHeight = $newHeight + $paddingHeight;
    $finalImage = imagecreatetruecolor($newWidth, $finalHeight);

    // 获取原图的主要颜色
    $dominantColor = getBackgroundColor($sourceImagePath);
    $backgroundColor = imagecolorallocate($finalImage, $dominantColor[0], $dominantColor[1], $dominantColor[2]);

    // 填充空白区域
    imagefilledrectangle($finalImage, 0, $newHeight, $newWidth, $finalHeight, $backgroundColor);



    // 将缩略图粘贴到新的图像资源中
    imagecopy($finalImage, $newImage, 0, 0, 0, 0, $newWidth, $newHeight);

    // 保存最终的缩略图
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($finalImage, $thumbnailPath, 90); // 质量设置为 90
            break;
        case IMAGETYPE_PNG:
            imagepng($finalImage, $thumbnailPath);
            break;
        case IMAGETYPE_GIF:
            imagegif($finalImage, $thumbnailPath);
            break;
        case IMAGETYPE_BMP:
            imagebmp($finalImage, $thumbnailPath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($finalImage, $thumbnailPath, 90); // 质量设置为 90
            break;
        default:
            throw new Exception("不支持的图片格式");
    }

    // 释放内存
    imagedestroy($newImage);
    imagedestroy($finalImage);
    imagedestroy($sourceImage);

    return [
        'width' => $newWidth,
        'height' => $finalHeight,
        'type' => $imageTypes[$type]
    ];
}

$originalpath = DZZ_ROOT.'./img/';
$thumppath = DZZ_ROOT.'./imgthumb/';
$handle = dir($originalpath);
if ($handle) {
    while (($filename = $handle->read()) !== false) {
        if ($filename != '.' && $filename != '..') {
            $sources = $originalpath . $filename;
            if(is_file($sources)){
                $file_info = pathinfo($sources);
                // 取出文件名（不包括扩展名）
                $filename = $file_info['filename'];

                // 取出文件扩展名
                $extension = $file_info['extension'];
                $targetpath = dirname($thumppath);
                dmkdir($targetpath);
                $thumppath = $thumppath.$filename.'.'.$extension;
                try {
                    $thumbnailInfo = generateThumbnailWithPadding($sources, $thumppath, 960, 50);
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
/*// 示例使用
$sourceImagePath = 'path/to/your/image.jpg'; // 替换为实际图片路径
$thumbnailPath = 'path/to/your/thumbnail.jpg'; // 替换为实际缩略图路径
$minSize = 480;
$maxFileSize = 3 * 1024 * 1024; // 3MB
$paddingHeight = 50; // 空白区域的高度

try {
    $thumbnailInfo = generateThumbnailWithPadding($sourceImagePath, $thumbnailPath, $minSize, $maxFileSize, $paddingHeight);
    echo "缩略图宽度: " . $thumbnailInfo['width'] . " 像素\n";
    echo "缩略图高度: " . $thumbnailInfo['height'] . " 像素\n";
    echo "缩略图类型: " . $thumbnailInfo['type'] . "\n";
    echo "缩略图大小: " . $thumbnailInfo['fileSize'] . " 字节\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}*/
