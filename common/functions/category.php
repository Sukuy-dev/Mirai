<?php
/**
 * Mirai Theme - Category Functions Module
 * 分类相关函数模块
 * 包含：分类设置解析、分类缓存、分类主分类获取、面包屑导航等
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_parseCategorySettings($options, $optionName) {
    $settings = [];
    if (!empty($options->$optionName)) {
        $lines = explode("\n", $options->$optionName);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode('|', $line, 2);
            if (count($parts) < 2) continue;
            
            $mid = trim($parts[0]);
            $value = trim($parts[1]);
            
            if (!empty($mid) && !empty($value)) {
                $settings[$mid] = $value;
            }
        }
    }
    return $settings;
}

function Mirai_parseCategoryCovers($options) {
    return Mirai_parseCategorySettings($options, 'categoryCovers');
}

function Mirai_parseCategoryDescs($options) {
    return Mirai_parseCategorySettings($options, 'categoryDescs');
}

function Mirai_getCategoryCache() {
    static $categoryCache = null;
    
    if ($categoryCache !== null) {
        return $categoryCache;
    }
    
    try {
        $db = \Typecho\Db::get();
        $categories = $db->fetchAll($db->select()->from('table.metas')->where('type = ?', 'category'));
        
        $categoryCache = [];
        foreach ($categories as $category) {
            $categoryCache[$category['mid']] = $category;
        }
    } catch (Exception $e) {
        $categoryCache = [];
    }
    
    return $categoryCache;
}

function Mirai_getPostCategory($cid) {
    if (empty($cid)) return null;
    
    try {
        $db = \Typecho\Db::get();
        // 获取该文章的第一个分类
        $category = $db->fetchRow($db->select('slug')
            ->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $cid)
            ->where('table.metas.type = ?', 'category')
            ->limit(1));
            
        return $category ? $category['slug'] : null;
    } catch (Exception $e) {
        return null;
    }
}

function Mirai_getBreadcrumbListRecursive($categoryId, $position = 2, $depth = 0) {
    static $maxDepth = 10;
    
    if ($depth >= $maxDepth) {
        return [];
    }
    
    $categoryCache = Mirai_getCategoryCache();
    
    if (!isset($categoryCache[$categoryId])) {
        return [];
    }
    
    $category = $categoryCache[$categoryId];
    $options = Mirai_opt();
    $categoryUrl = \Typecho\Router::url('category', $category, $options->index);
    
    $list = [];
    
    if (!empty($category['parent'])) {
        $list = Mirai_getBreadcrumbListRecursive($category['parent'], $position, $depth + 1);
        $position = $position + count($list);
    }
    
    $list[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'name' => $category['name'],
        'item' => $categoryUrl
    ];
    
    return $list;
}
