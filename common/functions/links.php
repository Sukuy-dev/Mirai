<?php
/**
 * Mirai - 友情链接功能模块
 * 
 * 包含：链接数据获取、页面选项处理、链接渲染等功能
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_getLinksPageOptions($archive) {
    $options = (is_object($archive) && isset($archive->options) && is_object($archive->options))
        ? $archive->options
        : Typecho_Widget::widget('Widget_Options');
    $fields = (is_object($archive) && isset($archive->fields) && is_object($archive->fields))
        ? $archive->fields
        : new stdClass();
    
    $themeOptions = [
        'showCategoryNav' => isset($options->linksShowCategoryNav) ? $options->linksShowCategoryNav == '1' : true,
        'targetBlank' => isset($options->linksTargetBlank) ? $options->linksTargetBlank == '1' : true,
        'nofollow' => isset($options->linksNofollow) ? $options->linksNofollow == '1' : true,
        'showRecommend' => isset($options->linksShowRecommend) ? $options->linksShowRecommend == '1' : true,
        'recommendTitle' => isset($options->linksRecommendTitle) && trim((string)$options->linksRecommendTitle) !== '' ? trim((string)$options->linksRecommendTitle) : '推荐站点',
        'showRecommendPosts' => isset($options->linksShowRecommendPosts) ? $options->linksShowRecommendPosts == '1' : true,
        'recommendPostsTitle' => isset($options->linksRecommendPostsTitle) && trim((string)$options->linksRecommendPostsTitle) !== '' ? trim((string)$options->linksRecommendPostsTitle) : '推荐文章',
    ];
    
    $defaults = [
        'categories' => isset($fields->linkCategories) ? array_filter(explode(',', $fields->linkCategories)) : [],
        'orderBy' => isset($fields->orderBy) ? $fields->orderBy : 'sort',
        'order' => isset($fields->order) ? $fields->order : 'ASC',
        'limit' => isset($fields->linkLimit) ? intval($fields->linkLimit) : 0,
        'showCategoryNav' => isset($fields->showCategoryNav) ? $fields->showCategoryNav === '1' : $themeOptions['showCategoryNav'],
        'targetBlank' => isset($fields->targetBlank) ? $fields->targetBlank === '1' : $themeOptions['targetBlank'],
        'nofollow' => isset($fields->nofollow) ? $fields->nofollow === '1' : $themeOptions['nofollow'],
        'showRecommend' => isset($fields->showRecommend) ? $fields->showRecommend === '1' : $themeOptions['showRecommend'],
        'recommendTitle' => isset($fields->recommendTitle) && trim((string)$fields->recommendTitle) !== '' ? trim((string)$fields->recommendTitle) : $themeOptions['recommendTitle'],
        'showRecommendPosts' => isset($fields->showRecommendPosts) ? $fields->showRecommendPosts === '1' : $themeOptions['showRecommendPosts'],
        'recommendPostsTitle' => isset($fields->recommendPostsTitle) && trim((string)$fields->recommendPostsTitle) !== '' ? trim((string)$fields->recommendPostsTitle) : $themeOptions['recommendPostsTitle'],
    ];
    
    return $defaults;
}

function Mirai_getPopularLinks() {
    $options = Typecho_Widget::widget('Widget_Options');
    
    if (!isset($options->popularSitesConfig) || empty($options->popularSitesConfig)) {
        return [];
    }
    
    $idList = array_filter(array_map('intval', explode(',', $options->popularSitesConfig)));
    if (empty($idList)) {
        return [];
    }
    
    $idList = array_slice($idList, 0, 10);
    
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    
    try {
        $links = $db->fetchAll($db->select()
            ->from($prefix . 'mirai_links')
            ->where('lid IN ?', $idList)
            ->where('visible = ?', 'Y'));
        
        $linkMap = [];
        foreach ($links as $link) {
            $linkMap[$link['lid']] = $link;
        }
        
        $result = [];
        foreach ($idList as $id) {
            if (isset($linkMap[$id])) {
                $link = $linkMap[$id];
                $result[] = [
                    'id' => $link['lid'],
                    'name' => htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'),
                    'url' => $link['url'],
                    'description' => htmlspecialchars($link['description'] ?: '', ENT_QUOTES, 'UTF-8'),
                    'image' => $link['image'] ?: ''
                ];
            }
        }
        
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

function Mirai_getLinksRecommendPosts($limit = 5) {
    $options = Typecho_Widget::widget('Widget_Options');
    
    if (!isset($options->recommendPostsConfig) || empty($options->recommendPostsConfig)) {
        return [];
    }
    
    $idList = array_filter(array_map('intval', explode(',', $options->recommendPostsConfig)));
    if (empty($idList)) {
        return [];
    }
    
    $idList = array_slice($idList, 0, min($limit, 5));
    
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    
    try {
        $posts = $db->fetchAll($db->select('cid', 'title', 'slug', 'created')
            ->from($prefix . 'contents')
            ->where('cid IN ?', $idList)
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish'));

        $postMap = [];
        foreach ($posts as $post) {
            $postMap[$post['cid']] = $post;
        }

        $result = [];
        foreach ($idList as $id) {
            if (isset($postMap[$id])) {
                $post = $postMap[$id];
                $result[] = [
                    'id' => $post['cid'],
                    'title' => htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'),
                    'url' => Typecho_Router::url('post', $post, Typecho_Widget::widget('Widget_Options')->index),
                    'created' => $post['created']
                ];
            }
        }

        return $result;
    } catch (Exception $e) {
        return [];
    }
}

function Mirai_getLinksData($options) {
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    
    try {
        $select = $db->select()
            ->from($prefix . 'mirai_links')
            ->where('visible = ?', 'Y');
        
        if (!empty($options['categories'])) {
            $select->where('category IN ?', $options['categories']);
        }
        
        $orderBy = in_array($options['orderBy'], ['sort', 'created', 'updated', 'name']) 
            ? $options['orderBy'] 
            : 'sort';
        $order = $options['order'] === 'DESC' ? Typecho_Db::SORT_DESC : Typecho_Db::SORT_ASC;
        $select->order($orderBy, $order);
        
        if ($options['limit'] > 0) {
            $select->limit($options['limit']);
        }
        
        $links = $db->fetchAll($select);
        
        $catIds = array_unique(array_filter(array_column($links, 'category')));
        $catMap = [];
        if (!empty($catIds)) {
            $cats = $db->fetchAll($db->select()->from($prefix . 'metas')->where('mid IN ?', $catIds));
            foreach ($cats as $cat) {
                $catMap[$cat['mid']] = $cat;
            }
        }

        $grouped = [];
        foreach ($links as $link) {
            $catId = $link['category'] ?: 0;
            if (!isset($grouped[$catId])) {
                $catName = $catId > 0 && isset($catMap[$catId]) ? $catMap[$catId]['name'] : '默认分类';
                $catDesc = $catId > 0 && isset($catMap[$catId]) ? $catMap[$catId]['description'] : '';
                $grouped[$catId] = [
                    'id' => $catId,
                    'name' => $catName,
                    'description' => $catDesc,
                    'links' => []
                ];
            }
            $grouped[$catId]['links'][] = Mirai_formatLinkData($link, $options);
        }
        
        return array_values($grouped);
        
    } catch (Exception $e) {
        return [];
    }
}

function Mirai_formatLinkData($link, $options) {
    return [
        'id' => $link['lid'],
        'name' => htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'),
        'url' => $link['url'],
        'description' => htmlspecialchars($link['description'] ?: '', ENT_QUOTES, 'UTF-8'),
        'image' => $link['image'] ?: '',
        'target' => $options['targetBlank'] ? '_blank' : '_self',
        'rel' => Mirai_buildLinkRel($options),
        'category' => $link['category']
    ];
}

function Mirai_buildLinkRel($options) {
    $rel = ['noopener'];
    
    if ($options['nofollow']) {
        $rel[] = 'nofollow';
    }
    
    if ($options['targetBlank']) {
        $rel[] = 'noreferrer';
    }
    
    return implode(' ', $rel);
}

function Mirai_getHomeLinks() {
    $options = Typecho_Widget::widget('Widget_Options');
    if (($options->homeLinksEnable ?? '0') != '1' || empty($options->homeLinksIds)) return [];
    
    $idList = array_filter(array_map('intval', explode(',', $options->homeLinksIds)));
    if (empty($idList)) return [];
    
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    
    try {
        $links = $db->fetchAll($db->select()->from($prefix . 'mirai_links')->where('lid IN ?', $idList)->where('visible = ?', 'Y')->order('sort', Typecho_Db::SORT_ASC));
        $linkMap = array_column($links, null, 'lid');
        
        $result = [];
        foreach ($idList as $id) {
            if (isset($linkMap[$id])) {
                $link = $linkMap[$id];
                $result[] = [
                    'id' => $link['lid'],
                    'name' => htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'),
                    'url' => $link['url'],
                    'description' => htmlspecialchars($link['description'] ?: '', ENT_QUOTES, 'UTF-8'),
                    'image' => $link['image'] ?: ''
                ];
            }
        }
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

function Mirai_renderHomeLinks() {
    $links = Mirai_getHomeLinks();
    if (empty($links)) {
        return;
    }
    
    $options = Typecho_Widget::widget('Widget_Options');
    $moreUrl = $options->homeLinksMoreUrl ?? '';
    $moreText = trim($options->homeLinksMoreText ?? '') ?: '更多友链';
    $targetBlank = ($options->linksTargetBlank ?? '1') == '1';
    $nofollow = ($options->linksNofollow ?? '1') == '1';
    
    $rel = ['noopener'];
    if ($nofollow) $rel[] = 'nofollow';
    if ($targetBlank) $rel[] = 'noreferrer';
    $relAttr = implode(' ', $rel);
    $targetAttr = $targetBlank ? ' target="_blank"' : '';
    
    echo '<section class="gt-home-links-section"><h2 class="gt-cms-title-h3">友情链接</h2><div class="gt-home-links-wrapper"><div class="gt-home-links-list">';
    
    foreach ($links as $link) {
        echo '<a href="' . $link['url'] . '"' . $targetAttr . ' rel="' . $relAttr . '" class="gt-home-link-item" title="' . $link['description'] . '"><span class="gt-home-link-name">' . $link['name'] . '</span></a>';
    }
    
    if ($moreUrl) {
        echo '<a href="' . $moreUrl . '" class="gt-home-link-item gt-home-link-more" title="查看更多友情链接"><span class="gt-home-link-name">' . $moreText . '</span></a>';
    }
    
    echo '</div></div></section>';
}
