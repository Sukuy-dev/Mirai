<?php
/**
 * Mirai Theme - Helper Functions Module
 * 辅助函数模块
 * 
 * 包含：HTML转义、时间格式化、设备检测、头像处理等通用工具函数
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_getDefaultAvatar() {
    return Mirai_getThemeUrl() . '/assets/images/default.png';
}

function Mirai_getUserAvatar($userId = null) {

    if ($userId === null) {
        $user = Mirai_user();
        if ($user->hasLogin()) {
            $userId = $user->uid;
        } else {
            return Mirai_getDefaultAvatar();
        }
    }

    if (!is_numeric($userId)) {
        return Mirai_getDefaultAvatar();
    }

    $db = \Typecho\Db::get();
    $user = $db->fetchRow($db->select('avatar')->from('table.users')->where('uid = ?', (int)$userId));
    
    if (!empty($user['avatar'])) {
        return Mirai_normalizeUrl($user['avatar']);
    }
    
    return Mirai_getDefaultAvatar();
}

function Mirai_uploadAvatar($file, $userId) {

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '上传失败，请重试'];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => '无法识别图片格式'];
    }
    
    $mimeType = $imageInfo['mime'];
    $imageType = $imageInfo[2];
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => '仅支持 JPG、PNG、GIF、WebP 格式的图片'];
    }

    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '图片大小不能超过 2MB'];
    }

    $uploadDir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = image_type_to_extension($imageType) ?: '.jpg';
    $filename = 'avatar_' . $userId . '_' . time() . $extension;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => '文件保存失败'];
    }

    $options = Mirai_opt();
    $url = \Typecho_Common::url('usr/uploads/avatars/' . $filename, $options->siteUrl);
    
    return ['success' => true, 'url' => $url, 'message' => '上传成功'];
}

function Mirai_deleteOldAvatar($avatarUrl) {
    if (empty($avatarUrl)) return;
    
    $options = Mirai_opt();
    $siteUrl = $options->siteUrl;

    if (strpos($avatarUrl, $siteUrl) !== false) {

        $relativePath = str_replace($siteUrl, '', $avatarUrl);
        $relativePath = ltrim($relativePath, '/');

        if (strpos($relativePath, '..') !== false) {
            return; // 禁止路径遍历
        }

        if (strpos($relativePath, 'usr/uploads/avatars/') !== 0) {
            return;
        }
        
        $path = __TYPECHO_ROOT_DIR__ . '/' . $relativePath;
        $path = preg_replace('#/+#', '/', $path);
        
        if (file_exists($path) && is_file($path)) {
            @unlink($path);
        }
    }
}

function Mirai_formatISODate($timestamp) {
    if (class_exists('\Typecho\Date')) {
        return (new \Typecho\Date($timestamp))->format('c');
    } elseif (class_exists('Typecho_Date')) {
        return (new Typecho_Date($timestamp))->format('c');
    }

    $options = Mirai_opt();
    $timezone = $options->timezone; 
    $localTime = $timestamp + $timezone;
    $datePart = gmdate('Y-m-d\TH:i:s', $localTime);
    $offsetHours = floor($timezone / 3600);
    $offsetMinutes = floor(($timezone % 3600) / 60);
    $offsetPart = sprintf('%+03d:%02d', $offsetHours, $offsetMinutes);
    return $datePart . $offsetPart;
}

function Mirai_isMobile() {
    return preg_match('/Android|iPhone|iPad|iPod|BlackBerry|IEMobile/i', $_SERVER['HTTP_USER_AGENT']);
}

function Mirai_formatTime($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . '天前';
    } else {
        if (class_exists('\Typecho\Date')) {
            return (new \Typecho\Date($timestamp))->format('Y-m-d');
        } elseif (class_exists('Typecho_Date')) {
            return (new Typecho_Date($timestamp))->format('Y-m-d');
        }
        $options = Mirai_opt();
        return gmdate('Y-m-d', $timestamp + $options->timezone);
    }
}

function Mirai_formatNumber($num, $type = 'auto') {
    $num = (int)$num;
    
    if ($type === 'views') {
        if ($num >= 10000) return round($num / 10000, 1) . 'W';
        if ($num >= 1000) return round($num / 1000, 1) . 'K';
        return (string)$num;
    }
    
    if ($num >= 100000000) {
        return round($num / 100000000, 1) . '亿';
    } elseif ($num >= 10000) {
        return round($num / 10000, 1) . '万';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return (string)$num;
}

function Mirai_renderLogo($options) {
    $siteTitle = $options->siteTitle ?: $options->title;
    
    $logoImage = !empty($options->logoImage) ? $options->logoImage : 'usr/themes/Mirai/assets/images/logo.png';
    $darkLogoImage = !empty($options->darkLogoImage) ? $options->darkLogoImage : '';
    $logoAlt = !empty($options->logoAlt) ? $options->logoAlt : $siteTitle;
    $logoHeight = !empty($options->logoHeight) ? intval($options->logoHeight) : 40;
    
    $logoUrl = Mirai_normalizeUrl($logoImage);
    $heightAttr = ' height="' . $logoHeight . '"';
    
    if ($darkLogoImage) {
        $darkLogoUrl = Mirai_normalizeUrl($darkLogoImage);
        echo '<img class="mirai-logo-light" src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($logoAlt) . '"' . $heightAttr . '>';
        echo '<img class="mirai-logo-dark" src="' . htmlspecialchars($darkLogoUrl) . '" alt="' . htmlspecialchars($logoAlt) . '"' . $heightAttr . '>';
    } else {
        echo '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($logoAlt) . '"' . $heightAttr . '>';
    }
}