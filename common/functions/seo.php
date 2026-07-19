<?php
/**
 * Mirai Theme - SEO Functions Module
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_seoSchema($data, $archive = null) {
    try {
        $options = Mirai_opt();
        if ($archive === null) {
            $archive = \Typecho\Widget::widget('Widget_Archive');
        }
        $siteUrl = Mirai_getSiteUrl();
        $siteTitle = $options->siteTitle ?: $options->title;
        
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => []
        ];

        $website = [
            '@type' => 'WebSite',
            '@id' => $siteUrl . '#website',
            'url' => $siteUrl,
            'name' => $siteTitle,
        ];
        
        if (!empty($options->siteDescription ?: $options->description)) {
            $website['description'] = $options->siteDescription ?: $options->description;
        }
        
        if ($archive->is('index')) {
            $website['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $siteUrl . '?s={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ];
        }
        $schema['@graph'][] = $website;

        $organization = [
            '@type' => 'Organization',
            '@id' => $siteUrl . '#organization',
            'name' => $siteTitle,
            'url' => $siteUrl,
        ];
        
        if (!empty($options->logoImage)) {
            $organization['logo'] = [
                '@type' => 'ImageObject',
                'url' => Mirai_normalizeUrl($options->logoImage)
            ];
        }
        

        $schema['@graph'][] = $organization;
        
        // 3. Article Schema
        if ($archive->is('single')) {
            $article = [
                '@type' => 'Article',
                '@id' => ($data['url'] ?? $archive->permalink) . '#article',
                'headline' => $archive->title,
                'datePublished' => Mirai_formatISODate($data['date'] ?? $archive->created),
                'dateModified' => Mirai_formatISODate($data['modified_date'] ?? $archive->modified),
                'author' => [
                    '@type' => 'Person',
                    'name' => $data['author'] ?? $archive->author->screenName,
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => $siteTitle,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => Mirai_normalizeUrl(!empty($options->logoImage) ? $options->logoImage : Mirai_getDefaultThumb())
                    ]
                ],
                'isPartOf' => ['@id' => $siteUrl . '#website']
            ];
            
            $cover = $data['image'] ?? Mirai_getPostCover($archive);
            if (!empty($cover)) {
                $article['image'] = ['@type' => 'ImageObject', 'url' => $cover];
            }
            
            if (!empty($data['description'])) {
                $article['description'] = $data['description'];
            }
            $schema['@graph'][] = $article;
            
            // Breadcrumb
            if ($archive->categories) {
                $breadcrumb = [
                    '@type' => 'BreadcrumbList',
                    '@id' => ($data['url'] ?? $archive->permalink) . '#breadcrumb',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => '首页', 'item' => Mirai_getSiteUrl()]
                    ]
                ];
                
                if (!empty($archive->categories)) {
                    foreach (Mirai_getBreadcrumbListRecursive($archive->categories[0]['mid']) as $item) {
                        $breadcrumb['itemListElement'][] = $item;
                    }
                }
                
                $breadcrumb['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => count($breadcrumb['itemListElement']) + 1,
                    'name' => $archive->title,
                    'item' => $data['url'] ?? $archive->permalink
                ];
                $schema['@graph'][] = $breadcrumb;
            }
        }
        
        // 4. CollectionPage Schema
        if ($archive->is('category') || $archive->is('tag')) {
            $archiveName = $archive->name ?? '';
            if ($archiveName === '') {
                ob_start();
                $archive->archiveTitle([
                    'category'  => _t('%s'),
                    'tag'       => _t('%s'),
                    'search'    => _t('%s'),
                    'author'    => _t('%s')
                ], '', '');
                $archiveName = trim(ob_get_clean());
            }
            $pageUrl = $data['url'] ?? Mirai_getPageUrl($archive, 1);
            $breadcrumb = [
                '@type' => 'BreadcrumbList',
                '@id' => $pageUrl . '#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => '首页', 'item' => Mirai_getSiteUrl()]
                ]
            ];
            
            if ($archive->is('category')) {
                foreach (Mirai_getBreadcrumbListRecursive($archive->mid) as $item) {
                    $breadcrumb['itemListElement'][] = $item;
                }
            } else {
                 $breadcrumb['itemListElement'][] = [
                    '@type' => 'ListItem', 'position' => 2, 'name' => '标签: ' . $archiveName, 'item' => $pageUrl
                ];
            }
            $schema['@graph'][] = $breadcrumb;
            
            $schema['@graph'][] = [
                '@type' => 'CollectionPage',
                '@id' => $pageUrl . '#collection',
                'url' => $pageUrl,
                'name' => ($archive->is('category') ? '分类: ' : '标签: ') . $archiveName,
                'description' => $data['description'],
                'isPartOf' => ['@id' => $siteUrl . '#website']
            ];
        }

        // 5. Search Results Schema
        if ($archive->is('search')) {
            $searchUrl = $data['url'] ?? Mirai_getCanonicalUrl($archive);
            $schema['@graph'][] = [
                '@type' => 'BreadcrumbList',
                '@id' => $searchUrl . '#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => '首页', 'item' => Mirai_getSiteUrl()],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => '搜索: ' . $archive->keywords, 'item' => $searchUrl]
                ]
            ];
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    } catch (Exception $e) {
        return '';
    }
}

function Mirai_seoPaginationLinks($archive) {
    if (!$archive || $archive->is('single') || $archive->parameter->type == 404) return;

    static $rendered = false;
    if ($rendered) return;

    $pageSize = intval($archive->parameter->pageSize ?? 10);
    if ($pageSize <= 0) $pageSize = 10;

    $total = ceil($archive->getTotal() / $pageSize);
    if ($total <= 1) return;
    
    $currentPage = $archive->getCurrentPage();
    
    if ($currentPage > 1 && ($prev = Mirai_getPageUrl($archive, $currentPage - 1))) {
        echo '    <link rel="prev" href="' . htmlspecialchars($prev, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    
    if ($currentPage < $total && $archive->hasNext() && ($next = Mirai_getPageUrl($archive, $currentPage + 1))) {
        echo '    <link rel="next" href="' . htmlspecialchars($next, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    
    $rendered = true;
}

function Mirai_getSeoData($archive) {
    $options = Mirai_opt();
    $siteTitle = $options->siteTitle ?: $options->title;
    
    $title = '';
    $keywords = $options->siteKeywords ?: $options->keywords;
    $description = $options->siteDescription ?: $options->description;
    
    if ($archive->is('index')) {
        $title = $siteTitle . ($options->siteSubtitle ? ' - ' . $options->siteSubtitle : '');
    } elseif ($archive->is('post') || $archive->is('page')) {
        // 单篇文章或页面
        ob_start();
        $archive->archiveTitle([
            'category'  => _t('%s'),
            'search'    => _t('%s'),
            'tag'       => _t('%s'),
            'author'    => _t('%s')
        ], '', ' - ');
        echo $siteTitle;
        $title = ob_get_clean();
        
        $customKeywords = Mirai_getEdkField($archive, 'keywords');
        if (!empty($customKeywords)) {
            $keywords = $customKeywords;
        } elseif ($archive->tags) {
            $keywords = implode(',', array_column($archive->tags, 'name'));
        }
        
        $customDesc = Mirai_getEdkField($archive, 'description');
        if (empty($customDesc)) {
            $excerpt = Mirai_getPostExcerpt($archive, 200);
            if (!empty($excerpt)) {
                $customDesc = strip_tags($excerpt);
            }
        }
        if (!empty($customDesc)) {
            $description = $customDesc;
        }
    } else {
        // 归档页面（分类、标签、搜索等）
        ob_start();
        $archive->archiveTitle([
            'category'  => _t('%s'),
            'search'    => _t('%s'),
            'tag'       => _t('%s'),
            'author'    => _t('%s')
        ], '', ' - ');
        echo $siteTitle;
        $title = ob_get_clean();
        
        // 优先使用分类/标签的自定义描述
        if ($archive->is('category') || $archive->is('tag')) {
            $archiveDesc = $archive->getDescription();
            if (!empty($archiveDesc)) {
                $description = $archiveDesc;
            }
        }
    }
    
    $currentPage = $archive->getCurrentPage();
    if ($currentPage > 1) {
        $title .= " (第{$currentPage}页)";
        $description = "第 {$currentPage} 页 - " . $description;
    }
    
    // OG 图片获取：统一使用 SEO 默认图作为兜底
    if ($archive->is('post') || $archive->is('page')) {
        $cover = Mirai_getPostCover($archive);
        $ogImage = ($cover && strpos($cover, 'thumb.svg') === false) ? $cover : Mirai_getSeoDefaultImage();
    } else {
        $ogImage = Mirai_getSeoDefaultImage();
    }
    
    return [
        'title' => $title,
        'keywords' => $keywords,
        'description' => $description,
        'canonical' => Mirai_getCanonicalUrl($archive),
        'ogImage' => Mirai_normalizeUrl($ogImage ?: ''),
        'favicon' => Mirai_normalizeUrl(!empty($options->favicon) ? $options->favicon : (Mirai_getThemeUrl() . '/assets/images/favicon.ico')),
        'themeColor' => Mirai_getFeatureValue($options->themeColor ? $options->themeColor : '#007fff', '#007fff', 'theme_color'),
    ];
}

function Mirai_renderSeoMeta($seoData, $archive) {
    $options = Mirai_opt();
    $seoEnabled = Mirai_featureEnabled('seo');
    $speedEnabled = Mirai_featureEnabled('speed');

    echo '    <title>' . $seoData['title'] . '</title>' . "\n";
    if (!empty($seoData['canonical']) && !$archive->is('404')) echo '    <link rel="canonical" href="' . trim($seoData['canonical']) . '">' . "\n";
    echo '    <link rel="icon" href="' . htmlspecialchars($seoData['favicon']) . '">' . "\n";

    // Apple Touch Icon support
    $appleTouchIcon = !empty($options->appleTouchIcon) ? $options->appleTouchIcon : $seoData['favicon'];
    echo '    <link rel="apple-touch-icon" href="' . htmlspecialchars(Mirai_normalizeUrl($appleTouchIcon)) . '">' . "\n";

    if ($seoData['themeColor']) echo '    <meta name="theme-color" content="' . $seoData['themeColor'] . '">' . "\n";
    if ($seoData['keywords']) echo '    <meta name="keywords" content="' . htmlspecialchars($seoData['keywords']) . '">' . "\n";
    if ($seoData['description']) echo '    <meta name="description" content="' . htmlspecialchars($seoData['description']) . '">' . "\n";

    Mirai_seoPaginationLinks($archive);
    
    // DNS Prefetch & Preconnect
    if ($speedEnabled && isset($options->dnsOptimizationEnable) && $options->dnsOptimizationEnable == '1') {
        $domains = [];
        if (!empty($options->dnsOptimization)) {
            $domains = array_merge($domains, preg_split("/\R/", trim((string)$options->dnsOptimization)));
        }

        $preconnectDomains = [];
        if (!empty($options->preconnectOptimization)) {
            $preconnectDomains = preg_split("/\R/", trim((string)$options->preconnectOptimization));
            foreach ($preconnectDomains as $domain) {
                $domain = trim($domain);
                if ($domain) echo '    <link rel="preconnect" href="//' . htmlspecialchars($domain) . '" crossorigin>' . "\n";
            }
            $domains = array_merge($domains, $preconnectDomains);
        }

        foreach (array_unique(array_map('trim', $domains)) as $dns) {
            if ($dns) echo '    <link rel="dns-prefetch" href="//' . htmlspecialchars($dns) . '">' . "\n";
        }
    }
    
    // Open Graph & Twitter
    if ($seoEnabled && isset($options->openGraphEnable) && $options->openGraphEnable == '1') {
        $og = [
            'og:title' => $seoData['title'],
            'og:description' => $seoData['description'],
            'og:url' => trim($seoData['canonical']),
            'og:type' => $archive->is('post') ? 'article' : 'website',
            'og:site_name' => $options->siteTitle ?: $options->title,
            'og:image' => $seoData['ogImage'],
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $seoData['title'],
            'twitter:description' => $seoData['description'],
            'twitter:image' => $seoData['ogImage']
        ];

        foreach ($og as $property => $content) {
            $nameAttr = strpos($property, 'twitter:') === 0 ? 'name' : 'property';
            echo '    <meta ' . $nameAttr . '="' . $property . '" content="' . htmlspecialchars($content) . '">' . "\n";
        }
        
        if ($archive->is('post')) {
            echo '    <meta property="article:author" content="' . htmlspecialchars($archive->author->screenName) . '">' . "\n";
            echo '    <meta property="article:published_time" content="' . Mirai_formatISODate($archive->created) . '">' . "\n";
            echo '    <meta property="article:modified_time" content="' . Mirai_formatISODate($archive->modified) . '">' . "\n";
            
            if ($archive->category) echo '    <meta property="article:section" content="' . htmlspecialchars($archive->categories[0]['name']) . '">' . "\n";
            if ($archive->tags) {
                foreach ($archive->tags as $tag) {
                    echo '    <meta property="article:tag" content="' . htmlspecialchars($tag['name']) . '">' . "\n";
                }
            }
        }
    }
}

function Mirai_renderSchema($seoData, $archive) {
    $options = Mirai_opt();
    if (!Mirai_featureEnabled('seo')) {
        return;
    }
    // 检查是否启用结构化数据
    if (empty($options->structuredDataEnable) || $options->structuredDataEnable !== '1') {
        return;
    }

    $schemaData = [
        'description' => $seoData['description'],
        'image' => $seoData['ogImage'],
        'url' => $seoData['canonical']
    ];

    echo '    ' . Mirai_seoSchema($schemaData, $archive) . "\n";
}

function Mirai_baiduPush($contents, $edit) {
    $options = Mirai_opt();
    if (!Mirai_featureEnabled('seo')) {
        return;
    }

    // 检查是否开启百度推送接口（主动推送）
    if (empty($options->baiduPushApiEnable) || $options->baiduPushApiEnable == '0') {
        return;
    }

    // 检查是否配置了API
    $api = $options->baiduPushApi;
    if (empty($api)) {
        return;
    }

    // 获取文章链接
    $permalink = '';

    if (isset($edit->permalink)) {
        $permalink = $edit->permalink;
    } elseif (is_object($contents) && isset($contents->permalink)) {
        $permalink = $contents->permalink;
    } elseif (is_array($contents) && isset($contents['cid'])) {
        // 尝试通过 CID 获取链接
        try {
            $db = \Typecho\Db::get();
            $post = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $contents['cid']));
            if ($post) {
                $type = $post['type'];
                $routeExists = (NULL != \Typecho\Router::get($type));
                if ($routeExists) {
                    $permalink = \Typecho\Router::url($type, $post, $options->index);
                }
            }
        } catch (Exception $e) {
            // 获取链接失败
            return;
        }
    }

    if (empty($permalink)) {
        return;
    }

    // 推送到百度
    $urls = [$permalink];
    $ch = curl_init();
    $postData = implode("\n", $urls);

    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);

    $result = curl_exec($ch);
    curl_close($ch);
}

function Mirai_IndexNow_Push($content, $widget) {
    $options = Mirai_opt();
    if ($options->indexNowEnable !== '1') {
        return;
    }
    $key = trim($options->indexNowKey ?? '');
    if (empty($key)) {
        return;
    }

    $url = $widget->permalink;
    $host = parse_url($url, PHP_URL_HOST);
    if (empty($host)) {
        if (method_exists($widget->request, 'getHost')) {
            $host = $widget->request->getHost();
        } elseif (!empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        }
    }

    $data = [
        'host' => $host,
        'key' => $key,
        'urlList' => [$url]
    ];

    $ch = curl_init('https://api.indexnow.org/indexnow');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5秒超时
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 兼容部分环境

    curl_exec($ch);
    curl_close($ch);
}

function Mirai_seoPushOnSave($content, $widget) {
    Mirai_IndexNow_Push($content, $widget);
    Mirai_baiduPush($content, $widget);
}