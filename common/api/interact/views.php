<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_ajaxViews() {
    $cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
    
    if ($cid <= 0) {
        return ['code' => 1, 'success' => false, 'message' => '参数错误'];
    }
    
    $db = \Typecho\Db::get();
    
    $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
    
    if (!$row) {
        return ['code' => 1, 'success' => false, 'message' => '文章不存在'];
    }
    
    $views = isset($row['views']) ? intval($row['views']) : 0;
    
    return ['code' => 0, 'success' => true, 'views' => $views];
}

function Mirai_ajaxAddViews() {
    $cid = isset($_POST['cid']) ? intval($_POST['cid']) : 0;
    
    if ($cid <= 0) {
        return ['code' => 1, 'success' => false, 'message' => '参数错误'];
    }
    
    // 1. 后端 Cookie 防刷校验
    $cookieName = 'mirai_viewed_' . $cid;
    
    // 获取当前阅读量
    $getViews = function($db, $cid) {
        $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
        return isset($row['views']) ? intval($row['views']) : 0;
    };
    
    $db = \Typecho\Db::get();

    if (isset($_COOKIE[$cookieName])) {
        $views = $getViews($db, $cid);
        return ['code' => 0, 'success' => true, 'message' => '已统计(Cached)', 'views' => $views];
    }

    $row = $db->fetchRow($db->select('cid')->from('table.contents')->where('cid = ?', $cid));
    if (!$row) {
        return ['code' => 1, 'success' => false, 'message' => '文章不存在'];
    }
    
    // 原子更新 views + 1
    $db->query($db->update('table.contents')
        ->expression('views', 'views + 1')
        ->where('cid = ?', $cid));
    
    // 获取更新后的阅读量
    $views = $getViews($db, $cid);

    setcookie($cookieName, '1', time() + 86400, '/');
        
    return ['code' => 0, 'success' => true, 'message' => '统计成功', 'views' => $views];
}
