<?php
/**
 * Mirai Theme - Actions Functions Module
 * 点赞收藏功能模块
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_checkAction($gid, $uid, $type) {
    static $cache = [];
    $cacheKey = (int)$gid . '_' . (int)$uid . '_' . $type;
    
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    
    if (empty($uid)) {
        if ($type === 'like') {
            $cookieName = 'mirai_liked_' . (int)$gid;
            $cache[$cacheKey] = isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === '1';
        } else {
            $cache[$cacheKey] = false;
        }
        return $cache[$cacheKey];
    }
    
    try {
        $db = \Typecho\Db::get();
        $actionsTable = $db->getPrefix() . 'mirai_actions';
        
        $count = $db->fetchObject($db->select('COUNT(*) AS count')
            ->from($actionsTable)
            ->where('gid = ?', (int)$gid)
            ->where('type = ?', $type)
            ->where('uid = ?', (int)$uid))->count;
        
        $cache[$cacheKey] = $count > 0;
        return $cache[$cacheKey];
    } catch (Exception $e) {
        $cache[$cacheKey] = false;
        return false;
    }
}

function Mirai_isLiked($gid, $uid) {
    return Mirai_checkAction($gid, $uid, 'like');
}

function Mirai_isCollected($gid, $uid) {
    return Mirai_checkAction($gid, $uid, 'collect');
}
