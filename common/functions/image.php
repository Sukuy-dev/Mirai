<?php
/**
 * Mirai未来主题 - 图片处理函数模块
 * 
 * 包含：图片URL处理、图片提取、Picture标签生成、懒加载等
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_pregImages($content, $limit = 0) {
    if (empty($content)) return [];
    
    static $cache = [];
    // 使用完整内容的 MD5 作为缓存键，避免冲突
    $cache_key = md5($content) . '_' . $limit;
    
    if (isset($cache[$cache_key])) return $cache[$cache_key];
    
    // 限制缓存大小，防止内存泄漏
    if (count($cache) >= 100) {
        $cache = array_slice($cache, -80, null, true);
    }
    
    $images = [];
    $patterns = [
        '/<img[^>]+src\s*=\s*["\']?([^"\'\s>]+)["\']?[^>]*>/i',
        // Markdown 图片语法（支持换行和空格）
        '/!\[.*?\]\(([^\)]+)\)/s'
    ];
    
    if ($limit === 1) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $m)) {
                $url = isset($m[1]) ? trim($m[1]) : '';
                // 移除可能的引号和标题
                $url = preg_replace('/\s+["\'].*$/s', '', $url);
                if ($url) {
                    $normalized = Mirai_normalizeUrl($url);
                    if ($normalized) {
                        return $cache[$cache_key] = [$normalized];
                    }
                }
            }
        }
        return $cache[$cache_key] = [];
    }
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            $urls = $matches[1] ?? [];
            foreach ($urls as $url) {
                $url = trim($url);
                // 移除 Markdown 图片中的标题部分（如 "title"）
                $url = preg_replace('/\s+["\'].*$/s', '', $url);
                if ($url) {
                    $normalized = Mirai_normalizeUrl($url);
                    if ($normalized && !in_array($normalized, $images, true)) {
                        $images[] = $normalized;
                        if ($limit > 0 && count($images) >= $limit) break 2;
                    }
                }
            }
        }
    }
    
    return $cache[$cache_key] = $images;
}

function Mirai_fileExistsCached($path) {
    static $cache = [];
    if (isset($cache[$path])) return $cache[$path];
    
    // 使用 LRU 策略，当缓存达到上限时保留最新的 150 个
    if (count($cache) >= 200) {
        $cache = array_slice($cache, -150, null, true);
    }
    
    return $cache[$path] = file_exists($path);
}

function Mirai_getLocalFilePath($url) {
    static $cache = [];
    if (empty($url)) return false;

    $clean_url = strtok($url, '?#');
    $cache_key = crc32($clean_url);

    if (isset($cache[$cache_key])) return $cache[$cache_key];
    if (count($cache) >= 100) {
        $cache = array_slice($cache, -80, null, true);
    }

    $path = '';
    
    // 相对路径
    if (strpos($clean_url, '://') === false && strpos($clean_url, '//') !== 0) {
        $path = $clean_url;
    } else {
        $host = parse_url($clean_url, PHP_URL_HOST);
        $siteHost = parse_url(Mirai_getSiteUrl(), PHP_URL_HOST);
        
        if ($host !== $siteHost) {
            return $cache[$cache_key] = false;
        }
        
        $urlPath = parse_url($clean_url, PHP_URL_PATH);
        $sitePath = parse_url(Mirai_getSiteUrl(), PHP_URL_PATH) ?: '/';
        
        if (strpos($urlPath, $sitePath) === 0) {
            $path = substr($urlPath, strlen($sitePath));
        } else {
            $path = $urlPath;
        }
    }
    
    $file_path = __TYPECHO_ROOT_DIR__ . '/' . ltrim($path, '/');
    return $cache[$cache_key] = $file_path;
}

function Mirai_getImageSizes($context = 'content', $customSizes = '') {
    if (!empty($customSizes)) {
        return $customSizes;
    }
    
    $sizes_map = [
        'article-cover' => '(max-width: 480px) 100vw, (max-width: 768px) 100vw, (max-width: 1200px) 60vw, 800px',
        'list-thumb' => '(max-width: 480px) 120px, (max-width: 768px) 150px, 200px',
        'recommend-main' => '(max-width: 768px) 100vw, (max-width: 1200px) 60vw, 800px',
        'recommend-item' => '(max-width: 768px) 100vw, (max-width: 1200px) 40vw, 400px',
        'recommend-card' => '(max-width: 480px) 50vw, (max-width: 768px) 25vw, 200px',
        'related' => '(max-width: 480px) 50vw, (max-width: 768px) 33vw, (max-width: 1200px) 25vw, 200px',
        'content' => '(max-width: 480px) 100vw, (max-width: 768px) 100vw, (max-width: 1200px) 70vw, 800px',
        'avatar' => '120px',
        'qrcode' => '200px',
        'sidebar-avatar' => '120px',
        'curated-main' => '(max-width: 768px) 100vw, 50vw',
        'curated-sub' => '(max-width: 768px) 100vw, 50vw',
    ];
    
    return isset($sizes_map[$context]) ? $sizes_map[$context] : $sizes_map['content'];
}

function Mirai_generatePictureTag($image, $lazyLoading, $alt = '', $class = 'lazyload', $width = '', $height = '', $options = []) {
    $image = $image ?: $lazyLoading;
    $alt = (is_string($alt) ? trim($alt) : '') ?: '图片';
    
    $context = $options['context'] ?? 'content';
    $isPriority = !empty($options['priority']);
    $itemprop = $options['itemprop'] ?? '';
    $title = $options['title'] ?? '';
    $srcset = $options['srcset'] ?? '';
    
    $sizes = Mirai_getImageSizes($context, $options['sizes'] ?? '');
    $local_path = Mirai_getLocalFilePath($image);
    $ext = strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION));
    
    $sources_html = '';
    $fallback_image = $image;
    
    // 生成picture标签的source元素
    if ($local_path && !in_array($ext, ['svg', 'gif']) && !$srcset && Mirai_fileExistsCached($local_path)) {
        foreach (['avif', 'webp'] as $fmt) {
            if ($ext === $fmt) continue;
            $target_path = preg_replace('/\.(jpg|jpeg|jpe|png|webp|avif)$/i', '.' . $fmt, $local_path);
            if ($target_path !== $local_path && Mirai_fileExistsCached($target_path)) {
                $target_src = preg_replace('/\.(jpg|jpeg|jpe|png|webp|avif)([?#].*)?$/i', '.' . $fmt . '$2', $image);
                $sources_html .= '<source srcset="' . htmlspecialchars((string)$target_src, ENT_QUOTES, 'UTF-8') . '" sizes="' . $sizes . '" type="image/' . $fmt . '">';
            }
        }
        
        // 回退逻辑：WebP/AVIF原图寻找兼容格式
        if (in_array($ext, ['avif', 'webp'])) {
            foreach (['.jpg', '.png'] as $try_ext) {
                $try_path = preg_replace('/\.(avif|webp)$/i', $try_ext, $local_path);
                if (Mirai_fileExistsCached($try_path)) {
                    $original_source = '<source srcset="' . htmlspecialchars((string)$image, ENT_QUOTES, 'UTF-8') . '" sizes="' . $sizes . '" type="image/' . $ext . '">';
                    // 如果原图是 AVIF，放到最前面；如果原图是 WebP，放到 AVIF 后面（即追加）
                    if ($ext === 'avif') {
                        $sources_html = $original_source . $sources_html;
                    } else {
                        $sources_html = $sources_html . $original_source;
                    }
                    $fallback_image = preg_replace('/\.(avif|webp)([?#].*)?$/i', $try_ext . '$2', $image);
                    break;
                }
            }
        }
    }
    
    $use_picture = $sources_html || $fallback_image !== $image;
    $html = '';

    // 确定 data-src 应该使用的格式：优先使用第一个 source 的格式，保持一致性
    $dataSrcImage = $fallback_image;

    if ($use_picture) {
        $html .= '<picture class="picture-wrapper"' . ($itemprop ? ' itemscope itemtype="https://schema.org/ImageObject"' : '') . '>' . $sources_html;
    }

    $all_classes = $class ? explode(' ', trim($class)) : [];
    if (!$isPriority && !in_array('lazyload', $all_classes)) {
        $all_classes[] = 'lazyload';
    }

    // 获取默认缩略图作为错误回退
    $defaultThumb = Mirai_getDefaultThumb();
    $onerrorHandler = 'this.onerror=null;this.src=\'' . htmlspecialchars((string)$defaultThumb, ENT_QUOTES, 'UTF-8') . '\';';
    
    $img_attrs = [
        'class="' . htmlspecialchars((string)implode(' ', $all_classes), ENT_QUOTES, 'UTF-8') . '"',
        $width ? 'width="' . htmlspecialchars((string)$width, ENT_QUOTES, 'UTF-8') . '"' : '',
        $height ? 'height="' . htmlspecialchars((string)$height, ENT_QUOTES, 'UTF-8') . '"' : '',
        $isPriority ? 'src="' . htmlspecialchars((string)$fallback_image, ENT_QUOTES, 'UTF-8') . '"' : 'src="' . htmlspecialchars((string)$lazyLoading, ENT_QUOTES, 'UTF-8') . '"',
        $isPriority ? '' : 'data-src="' . htmlspecialchars((string)$dataSrcImage, ENT_QUOTES, 'UTF-8') . '"',
        $srcset ? ($isPriority ? 'srcset="' . htmlspecialchars((string)$srcset, ENT_QUOTES, 'UTF-8') . '"' : 'data-srcset="' . htmlspecialchars((string)$srcset, ENT_QUOTES, 'UTF-8') . '"') : '',
        $isPriority ? 'fetchpriority="high"' : '',
        'decoding="async"',
        'sizes="' . $sizes . '"',
        'alt="' . htmlspecialchars((string)$alt, ENT_QUOTES, 'UTF-8') . '"',
        $title ? 'title="' . htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') . '"' : '',
        $itemprop ? 'itemprop="contentUrl"' : '',
        'onerror="' . $onerrorHandler . '"',
    ];
    
    $html .= '<img ' . implode(' ', array_filter($img_attrs)) . '>';
    $html .= $use_picture ? '</picture>' : '';
    
    // noscript降级方案 - 使用与 data-src 相同的图片，保持一致性
    $noscript_classes = array_filter($all_classes, fn($c) => $c !== 'lazyload');
    $noscript_attrs = [
        $noscript_classes ? 'class="' . htmlspecialchars((string)implode(' ', $noscript_classes), ENT_QUOTES, 'UTF-8') . '"' : '',
        $width ? 'width="' . htmlspecialchars((string)$width, ENT_QUOTES, 'UTF-8') . '"' : '',
        $height ? 'height="' . htmlspecialchars((string)$height, ENT_QUOTES, 'UTF-8') . '"' : '',
        'src="' . htmlspecialchars((string)$dataSrcImage, ENT_QUOTES, 'UTF-8') . '"',
        'alt="' . htmlspecialchars((string)$alt, ENT_QUOTES, 'UTF-8') . '"',
        $title ? 'title="' . htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') . '"' : '',
        $itemprop ? 'itemprop="contentUrl"' : '',
        'decoding="async"',
        'onerror="' . $onerrorHandler . '"',
    ];
    
    $html .= '<noscript><img ' . implode(' ', array_filter($noscript_attrs)) . '></noscript>';
    
    return $html;
}

function Mirai_parseAttributes($tag) {
    $attributes = [];
    
    if (class_exists('DOMDocument')) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml version="1.0" encoding="UTF-8"?><html><body>' . $tag . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $element = $dom->getElementsByTagName('body')->item(0);
        if ($element && $element->firstChild) {
            $element = $element->firstChild;
            if ($element->nodeType === XML_ELEMENT_NODE) {
                foreach ($element->attributes as $attr) {
                    $attributes[strtolower($attr->nodeName)] = $attr->nodeValue;
                }
                return $attributes;
            }
        }
    }
    
    $pattern = '/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i';
    if (preg_match_all($pattern, $tag, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            $value = '';
            if (!empty($match[2])) {
                $value = $match[2];
            } elseif (!empty($match[3])) {
                $value = $match[3];
            } elseif (!empty($match[4])) {
                $value = $match[4];
            }
            $attributes[$name] = $value;
        }
    }
    return $attributes;
}

function Mirai_cleanImageText($text) {
    $text = trim((string)$text);
    if ($text === '') return '';
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    if (preg_match('/\b(?:alt|title)\s*=/i', $text)) return '';
    $text = str_replace(['"', "'"], '', $text);
    return trim(preg_replace('/\s+/', ' ', $text));
}

function Mirai_convertImagesToPicture($content, $articleTitle = '') {
    if (empty($content)) return '';
    
    static $cache = [];
    $cacheKey = crc32($content . $articleTitle);
    if (isset($cache[$cacheKey])) return $cache[$cacheKey];
    if (count($cache) >= 20) {
        $cache = array_slice($cache, -15, null, true);
    }

    $placeholders = [];
    $counter = 0;
    $content = preg_replace_callback('/<(pre|code)[^>]*>.*?<\/\1>/si', function($m) use (&$placeholders, &$counter) {
        $p = '{{CODE_' . $counter++ . '}}';
        $placeholders[$p] = $m[0];
        return $p;
    }, $content);
    
    $lazyLoading = Mirai_getDefaultLazyLoading();
    $imageIndex = 0;
    $newContent = preg_replace_callback('/<picture[^>]*>.*?<\/picture>|(<img[^>]+>)/is', function($m) use ($articleTitle, &$imageIndex, $lazyLoading) {
        if (empty($m[1])) return $m[0];
        
        $attrs = Mirai_parseAttributes($m[1]);
        $src = trim($attrs['src'] ?? '');
        $imageIndex++;
        
        $attrs['alt'] = Mirai_cleanImageText($attrs['alt'] ?? '');
        $attrs['title'] = Mirai_cleanImageText($attrs['title'] ?? '');

        if (empty($attrs['alt'])) {
            $attrs['alt'] = !empty($attrs['title']) ? $attrs['title'] : ($articleTitle ? $articleTitle . ' - 图片' . $imageIndex : '文章配图 - 图片' . $imageIndex);
        }
        
        return Mirai_generatePictureTag(
            $src,
            $lazyLoading,
            $attrs['alt'],
            !empty($attrs['class']) ? $attrs['class'] : 'lazyload',
            $attrs['width'] ?? '',
            $attrs['height'] ?? '',
            [
                'context' => 'content',
                'sizes' => $attrs['sizes'] ?? '',
                'title' => $attrs['title'] ?? '',
                'srcset' => $attrs['srcset'] ?? '',
                'itemprop' => $attrs['itemprop'] ?? '',
            ]
        );
    }, $content);
    
    // 增加换行符以优化源代码阅读体验（避免重复添加）
    $newContent = preg_replace('/<\/(p|div|h[1-6]|ul|ol|li|blockquote|table|tr|section|article|header|footer)>(?![\r\n])/i', "$0\n", $newContent);
    
    // 恢复代码块
    foreach ($placeholders as $p => $code) {
        $newContent = str_replace($p, $code, $newContent);
    }
    
    return $cache[$cacheKey] = $newContent;
}