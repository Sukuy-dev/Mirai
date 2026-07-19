<?php
/**
 * Mirai Theme - Core Functions Module
 * 核心功能函数模块
 * 
 * 包含：全局配置、URL处理、页面判断等核心功能
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_opt() {
    static $options = null;
    if ($options === null) {
        $options = \Typecho\Widget::widget('Widget_Options');
    }
    return $options;
}

function Mirai_user() {
    static $user = null;
    if ($user === null) {
        $user = \Typecho_Widget::widget('Widget_User');
    }
    return $user;
}

function Mirai_featureEnabled($feature) {
    $options = Mirai_opt();
    
    switch ($feature) {
        case 'seo':
            // SEO功能：检查是否有任何SEO相关功能启用
            return !empty($options->openGraphEnable) 
                || !empty($options->structuredDataEnable)
                || !empty($options->codeHighlight)
                || !empty($options->rssEnable)
                || !empty($options->nofollowExternalLinks);
            
        case 'user_center':
            return !isset($options->enableUserCenter) || $options->enableUserCenter === '1';
            
        case 'speed':
            // 加速功能：DNS优化或预加载
            return (isset($options->dnsOptimizationEnable) && $options->dnsOptimizationEnable === '1')
                || (isset($options->instantPageEnable) && $options->instantPageEnable === '1');
            
        case 'editor':
            // 编辑器功能目前默认启用
            return true;
            
        case 'home_category_recommend':
            return isset($options->recommendEnable) && $options->recommendEnable === '1';
            
        default:
            // 默认启用未知功能（向后兼容）
            return true;
    }
}

function Mirai_getFeatureValue($value, $default, $feature) {
    return Mirai_featureEnabled($feature) ? $value : $default;
}

function Mirai_getPageUrl($archive, $page) {
    $options = Mirai_opt();
    
    // 如果是单页（文章/独立页面），直接返回 permalink
    if ($archive->is('single')) {
        return $archive->permalink;
    }

    // 尝试使用 getArchiveUrl 获取基础URL
    $tryGetArchiveUrl = function() use ($archive) {
        if (method_exists($archive, 'getArchiveUrl')) {
            return $archive->getArchiveUrl();
        }
        return null;
    };

    // 第1页，直接使用 getArchiveUrl
    if ($page <= 1) {
        $url = $tryGetArchiveUrl();
        if ($url) {
            return $url;
        }
    }
    
    // 尝试构建分页路由名称
    $routeName = '';
    if ($archive->is('index')) $routeName = 'index_page';
    elseif ($archive->is('category')) $routeName = 'category_page';
    elseif ($archive->is('tag')) $routeName = 'tag_page';
    elseif ($archive->is('search')) $routeName = 'search_page';
    elseif ($archive->is('author')) $routeName = 'author_page';
    elseif ($archive->is('date')) {
        if ($archive->is('year')) $routeName = 'archive_year_page';
        elseif ($archive->is('month')) $routeName = 'archive_month_page';
        elseif ($archive->is('day')) $routeName = 'archive_day_page';
    }
    
    if ($routeName) {
        try {
            // 准备参数
            $params = array(
                'page' => $page,
            );
            
            // 日期归档参数
            if ($archive->is('date')) {
                $params['year'] = $archive->year;
                if ($archive->is('month') || $archive->is('day')) {
                    $params['month'] = $archive->month;
                }
                if ($archive->is('day')) {
                    $params['day'] = $archive->day;
                }
            }
            
            // 获取正确的 Slug (仅非首页/搜索页)
            $slug = '';
            if (!$archive->is('index') && !$archive->is('search')) {
                try {
                    if (method_exists($archive, 'getArchiveSlug')) {
                        $slug = $archive->getArchiveSlug();
                    }
                } catch (Error $e) {
                    // 忽略初始化错误
                } catch (Exception $e) {
                    // 忽略其他异常
                }

                // 如果 getArchiveSlug 为空，尝试回退到 slug (可能不准确，但在某些情况下有效)
                if (empty($slug) && isset($archive->slug)) {
                    $slug = $archive->slug;
                }
            }
            
            // 确保 slug 存在才添加参数
            if (!empty($slug)) {
                $params['slug'] = $slug;
                $params['category'] = $slug; // 兼容
            }
            
            // 尝试获取 mid (如果存在)
            if (isset($archive->mid)) {
                $params['mid'] = $archive->mid;
            }
            
            // 搜索关键字
            if ($archive->is('search')) {
                $params['keywords'] = $archive->keywords;
            }
            
            return \Typecho\Router::url($routeName, $params, $options->index);
        } catch (Exception $e) {
            // ignore
        }
    }

    $url = $tryGetArchiveUrl();
    if ($url) {
        return $url;
    }
    
    return $archive->permalink;
}

function Mirai_getCanonicalUrl($archive = null) {
    try {
        $options = Mirai_opt();
        if ($archive === null) {
            $archive = \Typecho\Widget::widget('Widget_Archive');
        }
        
        if (!$archive) {
            return rtrim($options->siteUrl, '/') . '/';
        }
        
        $currentPage = method_exists($archive, 'getCurrentPage') ? $archive->getCurrentPage() : 1;
        return Mirai_getPageUrl($archive, $currentPage);
        
    } catch (Exception $e) {
        $options = Mirai_opt();
        return rtrim($options->siteUrl, '/') . '/';
    }
}

function Mirai_getPageUrlBySlug($slug) {
    // 特殊处理用户中心
    if ($slug === 'user') {
        $options = Mirai_opt();
        return \Typecho\Common::url('/user', $options->index);
    }

    try {
        $db = \Typecho\Db::get();
        $page = $db->fetchRow($db->select()->from('table.contents')
            ->where('type = ?', 'page')
            ->where('slug = ?', $slug)
            ->where('status = ?', 'publish')
            ->limit(1));
        
        if ($page) {
            $options = Mirai_opt();
            return \Typecho\Router::url('page', $page, $options->index);
        }
    } catch (Exception $e) {
        // 查询失败
    }
    
    return '';
}

function Mirai_getScheme() {
    static $scheme = null;
    if ($scheme !== null) return $scheme;
    
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $scheme = 'https';
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
        if (in_array($proto, ['http', 'https'], true)) {
            $scheme = $proto;
        }
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        $scheme = 'https';
    }
    return $scheme;
}

function Mirai_getSiteUrl() {
    return rtrim(Mirai_opt()->siteUrl, '/');
}

function Mirai_getThemeUrl() {
    return rtrim(Mirai_opt()->themeUrl, '/');
}

function Mirai_getDefaultThumb() {
    $options = Mirai_opt();
    if (!empty($options->logThumb)) {
        return Mirai_normalizeUrl($options->logThumb);
    }
    return Mirai_getThemeUrl() . '/assets/images/thumb.svg';
}

function Mirai_getSeoDefaultImage() {
    $options = Mirai_opt();

    if (!empty($options->seoDefaultImage)) {
        return Mirai_normalizeUrl($options->seoDefaultImage);
    }

    return Mirai_getThemeUrl() . '/assets/images/og-image.webp';
}

function Mirai_getDefaultLazyLoading() {
    $options = Mirai_opt();
    if (!empty($options->lazyLoading)) {
        return Mirai_normalizeUrl($options->lazyLoading);
    }
    return Mirai_getThemeUrl() . '/assets/images/lazy-loading.webp';
}

function Mirai_normalizeUrl($url, $options = []) {
    $url = trim($url, '"\' ');
    if (empty($url)) {
        return '';
    }
    
    $opt = Mirai_opt();
    $defaultOptions = [
        'siteUrl' => Mirai_getSiteUrl(),
        'unifyProtocol' => true,
        'removeSitePrefix' => false,
        'forceAbsolute' => true,
    ];
    $options = array_merge($defaultOptions, $options);
    $siteUrl = $options['siteUrl'];
    
    // 缓存键
    static $cache = [];
    $cacheKey = crc32($url . $siteUrl . ($options['unifyProtocol'] ? '1' : '0'));
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    
    // 处理协议相对URL（//example.com）
    if (strpos($url, '//') === 0) {
        $result = ($options['unifyProtocol'] ? Mirai_getScheme() . ':' : '') . $url;
        return $cache[$cacheKey] = $result;
    }
    
    // 处理完整URL（http:// 或 https://）
    if (preg_match('~^https?://~i', $url)) {
        // 协议统一：确保同站点URL使用当前协议
        if ($options['unifyProtocol']) {
            $urlHost = parse_url($url, PHP_URL_HOST);
            $siteHost = parse_url($siteUrl, PHP_URL_HOST);
            if ($urlHost === $siteHost) {
                $urlScheme = parse_url($url, PHP_URL_SCHEME);
                $currentScheme = Mirai_getScheme();
                if ($urlScheme !== $currentScheme) {
                    $url = preg_replace('/^https?:/', $currentScheme . ':', $url);
                }
            }
        }
        return $cache[$cacheKey] = $url;
    }
    
    // 移除已存在的站点URL前缀（用于处理已规范化的URL）
    if ($options['removeSitePrefix'] && strpos($url, $siteUrl) === 0) {
        $url = substr($url, strlen($siteUrl));
    }
    
    // 相对路径转换为完整URL
    if ($options['forceAbsolute']) {
        $url = $siteUrl . '/' . ltrim($url, '/');
    }
    
    return $cache[$cacheKey] = $url;
}

function Mirai_isInternalUrl($url) {
    if (empty($url)) {
        return true;
    }
    
    // 特殊协议视为内部链接
    $protocols = ['mailto:', 'tel:', 'sms:', 'javascript:'];
    foreach ($protocols as $protocol) {
        if (stripos($url, $protocol) === 0) {
            return true;
        }
    }
    
    $url = trim($url);
    
    // 相对路径视为内部链接
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0 && strpos($url, '//') !== 0) {
        return true;
    }
    
    // 解析URL
    $urlParts = parse_url($url);
    $siteUrl = Mirai_getSiteUrl();
    $siteParts = parse_url($siteUrl);
    
    // 获取核心域名（移除www.前缀）
    $urlHost = isset($urlParts['host']) ? preg_replace('/^www\./i', '', $urlParts['host']) : '';
    $siteHost = isset($siteParts['host']) ? preg_replace('/^www\./i', '', $siteParts['host']) : '';
    
    // 如果没有host或host相同，视为内部链接
    if (empty($urlHost) || $urlHost === $siteHost) {
        return true;
    }
    
    // 子域名也视为内部链接（如 cdn.example.com）
    if (substr($urlHost, -strlen('.' . $siteHost)) === '.' . $siteHost) {
        return true;
    }
    
    return false;
}

function Mirai_getOption($key, $default = null) {
    $options = Mirai_opt();
    return isset($options->$key) ? $options->$key : $default;
}
