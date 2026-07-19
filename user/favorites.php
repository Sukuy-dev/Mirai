<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$actionType = 'collect';
$actionData = ['emptyMsg' => '你还没有收藏过任何文章'];
?>
<div class="user-module module-favorites">
    <div class="module-header">
        <div class="module-title">我的收藏</div>
    </div>
    <?php require __DIR__ . '/actions.php'; ?>
</div>
