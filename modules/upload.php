<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_isUserUpload()
{
    // 检查 referer 判断是否来自用户投稿页面
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    if (strpos($referer, '/user/write') !== false) {
        return true;
    }
    
    // 检查当前请求路径
    $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (strpos($requestUri, '/user/write') !== false) {
        return true;
    }
    
    return false;
}

/**
 * 获取上传目录
 * @return string
 */
function Mirai_getUploadDir()
{
    // 用户投稿上传到独立目录
    if (Mirai_isUserUpload()) {
        return '/usr/uploads/mirai-user-uploads';
    }
    
    // 后台上传使用默认目录
    return defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : '/usr/uploads';
}

/**
 * 上传处理函数
 * @param array $file 上传的文件信息
 * @return array|false
 */
function Mirai_uploadHandle($file)
{
    if (empty($file['name'])) {
        return false;
    }

    $ext = Mirai_getSafeName($file['name']);
    
    // 检查文件类型
    $allowedTypes = ['jpg', 'jpeg', 'gif', 'png', 'webp', 'avif', 'bmp', 'ico', 'mp4', 'webm', 'mov', 'avi', 'mkv', 'mp3', 'wav', 'ogg', 'pdf', 'doc', 'docx', 'zip', 'rar'];
    if (!in_array(strtolower($ext), $allowedTypes)) {
        return false;
    }

    // 获取文件命名方式设置
    $namingType = 'default';
    try {
        $options = Typecho_Widget::widget('Widget_Options');
        $namingType = isset($options->uploadFileNaming) ? $options->uploadFileNaming : 'default';
    } catch (Exception $e) {
        // 使用默认命名方式
    }

    $date = new Typecho_Date();
    $uploadDir = Mirai_getUploadDir();
    $path = Typecho_Common::url(
        $uploadDir,
        defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__
    ) . '/' . $date->year . '/' . $date->month;

    // 创建上传目录
    if (!is_dir($path)) {
        if (!Mirai_makeUploadDir($path)) {
            return false;
        }
    }

    // 根据命名方式生成文件名
    if ($namingType === 'original') {
        // 使用原文件名（安全处理后）
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        // 清理文件名中的特殊字符
        $originalName = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}_-]/u', '', $originalName);
        // 限制长度
        $originalName = substr($originalName, 0, 50);
        // 如果文件名为空，使用默认命名
        if (empty($originalName)) {
            $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        } else {
            // 检查文件是否已存在，如果存在则添加数字后缀
            $fileName = $originalName . '.' . $ext;
            $filePath = $path . '/' . $fileName;
            $counter = 1;
            while (file_exists($filePath)) {
                $fileName = $originalName . '_' . $counter . '.' . $ext;
                $filePath = $path . '/' . $fileName;
                $counter++;
            }
        }
    } else {
        // Typecho 默认命名方式（随机哈希）
        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
    }

    $filePath = $path . '/' . $fileName;

    // 移动上传文件
    if (isset($file['tmp_name'])) {
        if (!@move_uploaded_file($file['tmp_name'], $filePath)) {
            return false;
        }
    } else {
        return false;
    }

    // 返回相对路径
    return [
        'name' => $file['name'],
        'path' => ltrim(str_replace(__TYPECHO_ROOT_DIR__, '', $filePath), '/'),
        'size' => $file['size'] ?? filesize($filePath),
        'type' => $ext,
        'mime' => $file['type'] ?? mime_content_type($filePath)
    ];
}

/**
 * 修改处理函数
 * @param array $content 原文件信息
 * @param array $file 新上传的文件
 * @return array|false
 */
function Mirai_modifyHandle($content, $file)
{
    if (empty($file['name'])) {
        return false;
    }

    $ext = Mirai_getSafeName($file['name']);
    
    // 保持原文件类型一致
    if ($content['attachment']->type != $ext) {
        return false;
    }

    $path = Typecho_Common::url(
        $content['attachment']->path,
        defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__
    );
    
    $dir = dirname($path);

    // 确保目录存在
    if (!is_dir($dir)) {
        if (!Mirai_makeUploadDir($dir)) {
            return false;
        }
    }

    // 删除旧文件
    @unlink($path);

    // 写入新文件
    if (isset($file['tmp_name'])) {
        if (!@move_uploaded_file($file['tmp_name'], $path)) {
            return false;
        }
    } elseif (isset($file['bytes'])) {
        if (!file_put_contents($path, $file['bytes'])) {
            return false;
        }
    } else {
        return false;
    }

    return [
        'name' => $content['attachment']->name,
        'path' => $content['attachment']->path,
        'size' => $file['size'] ?? filesize($path),
        'type' => $content['attachment']->type,
        'mime' => $content['attachment']->mime
    ];
}

/**
 * 获取安全的文件名
 */
function Mirai_getSafeName(&$name)
{
    $name = str_replace(['"', '<', '>'], '', $name);
    $name = str_replace('\\', '/', $name);
    $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
    $info = pathinfo($name);
    $name = substr($info['basename'], 1);
    return isset($info['extension']) ? strtolower($info['extension']) : '';
}

/**
 * 创建上传目录
 */
function Mirai_makeUploadDir($path)
{
    $path = preg_replace("/\\\\+/", '/', $path);
    $current = rtrim($path, '/');
    $last = $current;

    while (!is_dir($current) && false !== strpos($path, '/')) {
        $last = $current;
        $current = dirname($current);
    }

    if ($last == $current) {
        return true;
    }

    if (!@mkdir($last, 0755, true)) {
        return false;
    }

    return Mirai_makeUploadDir($path);
}

/**
 * 上传支付二维码图片
 * 专门用于支付系统的收款二维码上传
 * 
 * @param array $file 上传的文件信息
 * @return array ['success' => bool, 'msg' => string, 'url' => string, 'path' => string]
 */
function Mirai_payUploadQrCode($file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'msg' => '上传文件无效'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        return ['success' => false, 'msg' => '只支持 JPG、PNG、GIF、WEBP 格式的图片'];
    }

    $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'msg' => '上传的文件不是有效的图片'];
    }

    $mimeType = $imageInfo['mime'];
    if (!in_array($mimeType, $allowedMimes, true)) {
        return ['success' => false, 'msg' => '文件类型不被允许'];
    }

    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'msg' => '图片大小不能超过 2MB'];
    }

    $maxWidth = 2000;
    $maxHeight = 2000;
    if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
        return ['success' => false, 'msg' => '图片尺寸不能超过 ' . $maxWidth . 'x' . $maxHeight . ' 像素'];
    }

    $uploadDir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/pay/qr/' . date('Y/m');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $htaccessPath = __TYPECHO_ROOT_DIR__ . '/usr/uploads/pay/qr/.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccessContent = "<IfModule mod_php.c>\nphp_flag engine off\n</IfModule>\n<IfModule mod_php7.c>\nphp_flag engine off\n</IfModule>\n<IfModule mod_php8.c>\nphp_flag engine off\n</IfModule>\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8\nRemoveType .php .phtml .php3 .php4 .php5 .php7 .php8\n<FilesMatch \"\\.(?i:php|phtml|php3|php4|php5|php7|php8)$\">\nOrder Deny,Allow\nDeny from all\n</FilesMatch>\n";
        @file_put_contents($htaccessPath, $htaccessContent);
    }

    $filename = 'qr_' . date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.png';
    $uploadPath = $uploadDir . '/' . $filename;
    $webPath = '/usr/uploads/pay/qr/' . date('Y/m') . '/' . $filename;

    $srcImage = null;
    switch ($imageInfo[2]) {
        case IMAGETYPE_JPEG:
            $srcImage = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $srcImage = @imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_GIF:
            $srcImage = @imagecreatefromgif($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $srcImage = @imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            return ['success' => false, 'msg' => '不支持的图片格式'];
    }

    if ($srcImage === false) {
        return ['success' => false, 'msg' => '图片处理失败'];
    }

    $width = imagesx($srcImage);
    $height = imagesy($srcImage);
    $dstImage = imagecreatetruecolor($width, $height);
    if ($dstImage === false) {
        imagedestroy($srcImage);
        return ['success' => false, 'msg' => '图片处理失败'];
    }

    imagealphablending($dstImage, false);
    imagesavealpha($dstImage, true);
    $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
    imagefill($dstImage, 0, 0, $transparent);
    imagecopy($dstImage, $srcImage, 0, 0, 0, 0, $width, $height);

    $saveResult = imagepng($dstImage, $uploadPath, 8);
    imagedestroy($srcImage);
    imagedestroy($dstImage);

    if (!$saveResult) {
        return ['success' => false, 'msg' => '图片保存失败'];
    }

    chmod($uploadPath, 0644);

    return ['success' => true, 'url' => $webPath, 'path' => $uploadPath];
}
