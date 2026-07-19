<?php
/**
 * Mirai Theme - Views Helper Module
 * 阅读量显示工具模块
 * 
 * 提供统一的阅读量格式化、渲染和缓存功能
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_renderViews($views, $options = []) {
    $views = (int)$views;
    
    $defaults = [
        'showZero' => true,
        'class' => '',
        'id' => '',
        'format' => true
    ];
    $opts = array_merge($defaults, $options);
    
    // 不显示0值的情况
    if (!$opts['showZero'] && $views <= 0) {
        return '';
    }
    
    // 格式化数字
    $display = $opts['format'] ? Mirai_formatNumber($views, 'views') : $views;
    
    // 构建属性
    $attrs = [];
    if ($opts['class']) {
        $attrs['class'] = $opts['class'];
    }
    if ($opts['id']) {
        $attrs['id'] = $opts['id'];
    }
    if ($views > 0) {
        $attrs['data-views'] = $views;
    }
    
    // 构建属性字符串
    $attrStr = '';
    foreach ($attrs as $k => $v) {
        $attrStr .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
    }
    
    return sprintf(
        '<span%s><i class="ri-eye-line"></i>%s</span>',
        $attrStr,
        $display
    );
}

function Mirai_calculateTotalViews($posts) {
    if (empty($posts)) {
        return 0;
    }
    
    $total = 0;
    foreach ($posts as $post) {
        $total += (int)($post['views'] ?? 0);
    }
    
    return $total;
}