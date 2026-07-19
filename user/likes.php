<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$actionType = 'like';
$actionData = ['emptyMsg' => '你还没有点赞过任何文章'];
?>
<div class="user-module module-likes">
    <div class="module-header">
        <div class="module-title">我的点赞</div>
    </div>
    <?php require __DIR__ . '/actions.php'; ?>
</div>
