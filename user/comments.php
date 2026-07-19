<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$db = \Typecho\Db::get();
?>
<div class="user-module module-comments">
    <div class="module-header">
        <div class="module-title">我的评论</div>
    </div>

    <div class="comment-list">
        <?php
        $comments = $db->fetchAll($db->select()->from('table.comments')
            ->where('authorId = ?', $this->user->uid)
            ->order('created', \Typecho\Db::SORT_DESC)
            ->limit(20));
        ?>

        <?php if (empty($comments)): ?>
            <div class="empty-state">你还没有发表过评论</div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <?php 
                $parentPost = $db->fetchRow($db->select('title', 'slug')->from('table.contents')->where('cid = ?', $comment['cid']));
                if (!$parentPost) continue;
                ?>
                <div class="user-comment-item">
                    <div class="comment-header">
                        <span class="comment-time"><?php echo (new \Typecho\Date($comment['created']))->format('Y-m-d H:i'); ?></span>
                        <span class="comment-on">评论于 <a href="<?php echo \Typecho\Router::url('post', $parentPost, $this->options->index); ?>"><?php echo $parentPost['title']; ?></a></span>
                    </div>
                    <div class="comment-content">
                        <?php echo $comment['text']; ?>
                    </div>
                    <div class="comment-status <?php echo $comment['status'] == 'approved' ? 'status-approved' : 'status-waiting-comment'; ?>">
                        <?php echo $comment['status'] == 'approved' ? '已通过' : '待审核'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>