<?php
$fileuri = DZZ_ROOT.'/img/a1723851338547_8774.jpg';
// 使用 pathinfo 函数获取文件信息
$file_info = pathinfo($fileuri);

// 取出文件名（不包括扩展名）
$filename = $file_info['filename'];

// 取出文件扩展名
$extension = $file_info['extension'];
include_once  DZZ_ROOT.'./dzz/core/class/class_image.php';
$thumppath = DZZ_ROOT.'./imgthumb/'.$filename.'.'.$extension;
$targetpath = dirname($thumppath);
dmkdir($targetpath);
$image_size = getimagesize($fileuri);
$thumbsize = resizeImageWithMinSize($image_size[0], $image_size[1]);
$bsize =  calculateImageSize($fileuri);

function calculateImageSize($imagePath) {
    // 获取图片的宽度和高度
    list($width, $height, $type) = getimagesize($imagePath);

    if ($width === false || $height === false) {
        throw new Exception("无法获取图片尺寸");
    }

    // 确定每像素字节数
    switch ($type) {
        case IMAGETYPE_JPEG:
            $bytesPerPixel = 3; // JPEG 通常是 24 位颜色
            break;
        case IMAGETYPE_PNG:
            // PNG 可以是 24 位或 32 位颜色
            $info = getimagesize($imagePath, $info);
            if (isset($info['channels']) && $info['channels'] == 4) {
                $bytesPerPixel = 4; // 32 位颜色
            } else {
                $bytesPerPixel = 3; // 24 位颜色
            }
            break;
        default:
            throw new Exception("不支持的图片格式");
    }

    // 计算图片大小
    $imageSize = $width * $height * $bytesPerPixel;

    return $imageSize;
}

$image = new image();
try {
    $thumb = $image->Thumb($fileuri, $thumppath, $thumbsize['width'], $thumbsize['height'], 1, 0);
    if ($thumb) {
        $image_path = $thumppath;

// 创建图像资源
        $image = imagecreatefromjpeg($image_path);

// 检查是否成功创建图像资源
        if ($image !== false) {
            $angles = [90, 180, 270, 360];
            foreach ($angles as $angle) {
                // 旋转图像
                $rotated_image = imagerotate($image, $angle, 0);

                // 检查是否成功旋转图像
                if ($rotated_image !== false) {
                    // 保存旋转后的图像
                    $output_path = $targetpath.'/'.$filename."_$angle".'.'.$extension;
                    imagejpeg($rotated_image, $output_path);

                    // 释放内存
                    imagedestroy($rotated_image);

                    echo "图像旋转 {$angle} 度成功，保存路径: {$output_path}<br>";
                } else {
                    echo "图像旋转 {$angle} 度失败！<br>";
                }
            }
        } else {
            echo "无法创建图像资源！";
        }

    } else {
        $thumbpath = false;

    }
} catch (\Exception $e) {
    $thumbpath = false;


}
function resizeImageWithMinSize($originalWidth, $originalHeight, $minSize = 480) {
    // 确定原图的最长边和最短边
    $longestSide = max($originalWidth, $originalHeight);
    $shortestSide = min($originalWidth, $originalHeight);

    // 计算缩放比例，确保新的最短边不小于最小尺寸
    $ratio = $minSize / $shortestSide;

    // 根据缩放比例计算新的宽度和高度
    $newWidth = $originalWidth * $ratio;
    $newHeight = $originalHeight * $ratio;

    return ['width' => round($newWidth), 'height' => round($newHeight)];
}
