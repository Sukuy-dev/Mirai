<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$db = \Typecho\Db::get();
?>
<div class="user-module module-posts">
    <div class="module-header">
        <div class="module-title">我的文章</div>
        <a href="<?php echo \Typecho\Common::url('/user/write', $this->options->index); ?>" class="btn btn-sm btn-primary btn-publish"><i class="ri-add-line"></i> 发布</a>
    </div>

    <div class="post-list-table">
        <?php
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $posts = $db->fetchAll($db->select()->from('table.contents')
            ->where('authorId = ?', $this->user->uid)
            ->where('type = ?', 'post')
            ->order('created', \Typecho\Db::SORT_DESC)
            ->page($page, $pageSize));
            
        $total = $db->fetchObject($db->select('COUNT(cid) AS num')->from('table.contents')
            ->where('authorId = ?', $this->user->uid)
            ->where('type = ?', 'post'))->num;
        ?>

        <?php if (empty($posts)): ?>
            <div class="empty-state">你还没有发布过文章</div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="user-post-item">
                    <div class="post-info">
                        <div class="post-title">
                            <?php if ($post['status'] == 'publish'): ?>
                                <a href="<?php echo \Typecho\Router::url('post', $post, $this->options->index); ?>" target="_blank"><?php echo $post['title']; ?></a>
                            <?php else: ?>
                                <?php echo $post['title']; ?>
                            <?php endif; ?>
                        </div>
                        <div class="post-meta">
                            <span class="time"><?php echo (new \Typecho\Date($post['created']))->format('Y-m-d'); ?></span>
                            <span class="status status-<?php echo $post['status']; ?>">
                                <?php 
                                $statusMap = [
                                    'publish' => '已发布',
                                    'private' => '私密',
                                    'waiting' => '待审核',
                                    'hidden' => '草稿'
                                ];
                                echo isset($statusMap[$post['status']]) ? $statusMap[$post['status']] : $post['status'];
                                ?>
                            </span>
                            <span class="stats">
                                <i class="ri-message-3-line"></i> <?php echo $post['commentsNum']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="post-actions">
                        <?php if ($post['status'] == 'publish'): ?>
                            <a href="<?php echo \Typecho\Router::url('post', $post, $this->options->index); ?>" class="btn-icon" title="查看" target="_blank"><i class="ri-eye-line"></i></a>
                        <?php endif; ?>
                        <a href="<?php echo \Typecho\Common::url('/user/write?cid=' . $post['cid'], $this->options->index); ?>" class="btn-icon" title="编辑"><i class="ri-edit-line"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- 分页 -->
            <?php if ($total > $pageSize): ?>
                <?php 
                $totalPages = ceil($total / $pageSize);
                echo Mirai_customPagination(
                    $page,
                    $totalPages,
                    function($p) {
                        return \Typecho\Common::url('/user/posts?page=' . $p, $this->options->index);
                    }
                );
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
