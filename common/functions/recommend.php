<?php
/**
 * Mirai Theme - Recommend Functions Module
 * 推荐模块函数
 * 
 * 包含：推荐文章获取、推荐区域渲染等
 */
 
function Mirai_getRecommendPost($cid) {
    $cid = (int)$cid;
    if ($cid <= 0) return null;
    
    $db = \Typecho\Db::get();
    $post = $db->fetchRow($db->select()->from('table.contents')
        ->where('cid = ?', $cid)
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post'));
        
    if ($post) {
        $post['category'] = Mirai_getPostCategory($post['cid']);
        $post['permalink'] = \Typecho\Router::url('post', $post, Mirai_opt()->index);
        return $post;
    }
    
    return null;
}

$_mirai_recommend_excluded_ids = [];

function Mirai_setRecommendExcludedIds($ids) {
    global $_mirai_recommend_excluded_ids;
    $_mirai_recommend_excluded_ids = is_array($ids) ? $ids : [];
}

function Mirai_getRecommendExcludedIds() {
    global $_mirai_recommend_excluded_ids;
    return $_mirai_recommend_excluded_ids;
}

function Mirai_getRecommendPostsBatch($cids) {
    if (empty($cids)) return [];
    
    $cids = array_filter(array_map('intval', $cids), function($cid) { return $cid > 0; });
    if (empty($cids)) return [];
    
    $cids = array_values(array_unique($cids));
    
    $db = \Typecho\Db::get();
    $options = Mirai_opt();
    
    $rows = $db->fetchAll($db->select()->from('table.contents')
        ->where('cid IN ?', $cids)
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post'));
    
    if (empty($rows)) return [];
    
    $postMap = [];
    foreach ($rows as $row) {
        $postMap[$row['cid']] = $row;
    }
    
    $validCids = array_keys($postMap);
    
    $categoryData = $db->fetchAll(
        $db->select('table.relationships.cid', 'table.metas.slug')
            ->from('table.relationships')
            ->join('table.metas', 'table.relationships.mid = table.metas.mid', \Typecho\Db::LEFT_JOIN)
            ->where('table.relationships.cid IN ?', $validCids)
            ->where('table.metas.type = ?', 'category')
    );
    
    $categoryMap = [];
    foreach ($categoryData as $cat) {
        if (!isset($categoryMap[$cat['cid']])) {
            $categoryMap[$cat['cid']] = $cat['slug'];
        }
    }
    
    foreach ($postMap as &$post) {
        $post['category'] = $categoryMap[$post['cid']] ?? null;
        $post['permalink'] = \Typecho\Router::url('post', $post, $options->index);
    }
    unset($post);
    
    return $postMap;
}

function Mirai_getFirstRecommendCid($options) {
    if (!Mirai_featureEnabled('home_category_recommend') || empty($options->recommendEnable)) {
        return null;
    }
    
    $topEnable = $options->recommendTopEnable === '1';
    if ($topEnable && !empty($options->recommendTopIds)) {
        foreach (explode("\n", $options->recommendTopIds) as $line) {
            $cid = intval(trim($line));
            if ($cid > 0) return $cid;
        }
    }
    
    if (!empty($options->recommendContent)) {
        foreach (explode("\n", $options->recommendContent) as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '|') !== false) continue;
            $cid = intval($line);
            if ($cid > 0) return $cid;
        }
    }
    
    return null;
}