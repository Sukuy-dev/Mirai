<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_ajaxToggleCollect() {
    $cid = isset($_POST['gid']) ? intval($_POST['gid']) : (isset($_POST['cid']) ? intval($_POST['cid']) : 0);
    
    if ($cid <= 0) {
        return ['code' => 1, 'success' => false, 'message' => '参数错误'];
    }
    
    $user = Mirai_user();
    
    if (!$user->hasLogin()) {
        return ['code' => -1, 'success' => false, 'message' => '请先登录'];
    }
    
    $db = \Typecho\Db::get();
    $actionsTable = $db->getPrefix() . 'mirai_actions';
    
    // 检查是否已收藏
    $exists = $db->fetchRow($db->select()->from($actionsTable)
        ->where('type = ?', 'collect')
        ->where('gid = ?', $cid)
        ->where('uid = ?', $user->uid));
    
    if ($exists) {
        // 取消收藏
        $db->query($db->delete($actionsTable)
            ->where('type = ?', 'collect')
            ->where('gid = ?', $cid)
            ->where('uid = ?', $user->uid));
            
        return ['code' => 0, 'success' => true, 'action' => 'uncollect', 'message' => '已取消收藏'];
    } else {
        // 添加收藏
        $db->query($db->insert($actionsTable)->rows([
            'gid' => $cid,
            'uid' => $user->uid,
            'type' => 'collect',
            'created' => time()
        ]));
        
        return ['code' => 0, 'success' => true, 'action' => 'collect', 'message' => '收藏成功'];
    }
}
