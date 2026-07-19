<?php
/**
 * Mirai Theme - Content Processing Functions Module
 * 内容处理函数模块
 * 
 * 包含：阅读时间计算、内容截取、Markdown处理、RSS输出等
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_stripMarkdown($content) {
    $content = strip_tags($content);
    
    $patterns = [
        '/```[\s\S]*?```/',        // 代码块
        '/`[^`]+`/',                // 行内代码
        '/!\[[^\]]*\]\([^\)]+\)/',  // 图片
        '/^#{1,6}\s+/m',            // 标题
        '/^>\s*/m',                 // 引用
        '/^[\-\*\+]\s+/m',          // 列表
        '/^\d+\.\s+/m',             // 有序列表
        '/^[\-\*_]{3,}\s*$/m',      // 分割线
    ];
    $content = preg_replace($patterns, '', $content);
    
    // 处理链接 [text](url) -> text
    $content = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $content);
    
    // 处理强调 **text**, __text__, *text*, _text_, ~~text~~
    $content = preg_replace('/([*_~]{1,2})([^*_~]+)\1/', '$2', $content);
    
    $content = preg_replace('/\s+/', ' ', $content);
    return trim($content);
}

function Mirai_subContent($content, $length = 200, $stripTags = true) {
    if ($stripTags) {
        $content = strip_tags($content);
    }
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    if (mb_strlen($content, 'UTF-8') <= $length) {
        return $content;
    }
    
    return mb_substr($content, 0, $length, 'UTF-8') . '...';
}

function Mirai_addNofollowToExternalLinks($content) {
    return preg_replace_callback('/<a\s+([^>]*?)href\s*=\s*(?|"([^"]+)"|\'([^\']+)\'|([^\s>]+))([^>]*?)>/i', function($matches) {
        $before_href = $matches[1];
        $href = $matches[2];
        $after_href = $matches[3];
        
        // 使用统一的内部链接检测函数
        $is_internal = Mirai_isInternalUrl($href);
        
        if (!$is_internal) {
            $rel_added = false;
            $new_rel = 'noopener nofollow';
            if (preg_match('/rel=["\'](.*?)["\']/i', $before_href, $m)) {
                $rel_val = $m[1];
                if (stripos($rel_val, 'noopener') === false) $rel_val .= ' noopener';
                if (stripos($rel_val, 'nofollow') === false) $rel_val .= ' nofollow';
                $before_href = str_replace($m[0], 'rel="' . trim($rel_val) . '"', $before_href);
                $rel_added = true;
            }
            
            if (!$rel_added && preg_match('/rel=["\'](.*?)["\']/i', $after_href, $m)) {
                $rel_val = $m[1];
                if (stripos($rel_val, 'noopener') === false) $rel_val .= ' noopener';
                if (stripos($rel_val, 'nofollow') === false) $rel_val .= ' nofollow';
                $after_href = str_replace($m[0], 'rel="' . trim($rel_val) . '"', $after_href);
                $rel_added = true;
            }
            
            if (!$rel_added) {
                $after_href = ' rel="' . $new_rel . '"' . $after_href;
            }
        }
        
        return '<a ' . $before_href . 'href="' . htmlspecialchars((string)$href, ENT_QUOTES, 'UTF-8') . '"' . $after_href . '>';
    }, $content);
}

function Mirai_handleRssFeed($type) {
    $options = Mirai_opt();
    $rssEnabled = Mirai_featureEnabled('seo') && (!isset($options->rssEnable) || $options->rssEnable === '1');
    if (!$rssEnabled) {
        throw new Exception('RSS订阅已禁用');
    }
    require_once dirname(__DIR__) . '/modules/rss.php';
    $rss = new Mirai_Rss();
    if ($type === 'feed') {
        $rss->renderRss2();
    } else {
        $rss->renderAtom();
    }
    exit;
}