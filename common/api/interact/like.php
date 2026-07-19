<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function _Mirai_getLikeActionState() {
    $cid = isset($_POST['gid']) ? intval($_POST['gid']) : (isset($_POST['cid']) ? intval($_POST['cid']) : 0);
    if ($cid <= 0) {
        return ['code' => 1, 'message' => '参数错误'];
    }

    $db = \Typecho\Db::get();
    $user = Mirai_user();
    
    $post = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $cid));
    if (!$post) {
        return ['code' => 1, 'message' => '文章不存在'];
    }
    
    $hasLiked = Mirai_isLiked($cid, $user->uid);
    
    return [
        'code' => 0,
        'cid' => $cid,
        'db' => $db,
        'user' => $user,
        'hasLiked' => $hasLiked
    ];
}

function Mirai_ajaxLike() {
    $state = _Mirai_getLikeActionState();
    if ($state['code'] !== 0) {
        return ['code' => $state['code'], 'success' => false, 'message' => $state['message']];
    }
    
    if ($state['hasLiked']) {
        return ['code' => 1, 'success' => false, 'message' => '您已经点赞过了'];
    }
    
    $cid = $state['cid'];
    $db = $state['db'];
    $user = $state['user'];
    $actionsTable = $db->getPrefix() . 'mirai_actions';
    
    $db->query($db->update('table.contents')->expression('likes', 'COALESCE(likes, 0) + 1')->where('cid = ?', $cid));
    $updated = $db->fetchRow($db->select('likes')->from('table.contents')->where('cid = ?', $cid));
    $likes = (int)($updated['likes'] ?? 0);
    
    if ($user->hasLogin()) {
        $db->query($db->insert($actionsTable)->rows([
            'gid' => $cid,
            'uid' => $user->uid,
            'type' => 'like',
            'created' => time()
        ]));
    }
    
    setcookie('mirai_liked_' . $cid, '1', time() + 604800, '/');
    
    return ['code' => 0, 'success' => true, 'message' => '点赞成功', 'likes' => $likes];
}

function Mirai_ajaxUnlike() {
    $state = _Mirai_getLikeActionState();
    if ($state['code'] !== 0) {
        return ['code' => $state['code'], 'success' => false, 'message' => $state['message']];
    }

    if (!$state['hasLiked']) {
        return ['code' => 1, 'success' => false, 'message' => '您还未点赞'];
    }
    
    $cid = $state['cid'];
    $db = $state['db'];
    $user = $state['user'];
    $actionsTable = $db->getPrefix() . 'mirai_actions';

    $db->query($db->update('table.contents')
        ->expression('likes', 'GREATEST(0, COALESCE(likes, 0) - 1)')
        ->where('cid = ?', $cid));
    $updated = $db->fetchRow($db->select('likes')->from('table.contents')->where('cid = ?', $cid));
    $likes = (int)($updated['likes'] ?? 0);
    
    if ($user->hasLogin()) {
        $db->query($db->delete($actionsTable)
            ->where('type = ?', 'like')
            ->where('gid = ?', $cid)
            ->where('uid = ?', $user->uid));
    }
    
    setcookie('mirai_liked_' . $cid, '', time() - 3600, '/');
    
    return ['code' => 0, 'success' => true, 'message' => '已取消点赞', 'likes' => $likes];
}
