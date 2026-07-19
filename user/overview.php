<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$db = \Typecho\Db::get();
$actionTable = $db->getPrefix() . 'mirai_actions';

// 统计文章数（已发布的文章）
$postsCount = $db->fetchObject($db->select('COUNT(*) AS num')
    ->from('table.contents')
    ->where('authorId = ?', $this->user->uid)
    ->where('type = ?', 'post'))->num;

// 统计评论数
$commentsCount = $db->fetchObject($db->select('COUNT(*) AS num')
    ->from('table.comments')
    ->where('authorId = ?', $this->user->uid))->num;

// 统计点赞数
$likesCount = $db->fetchObject($db->select('COUNT(*) AS num')
    ->from($actionTable)
    ->where('uid = ?', $this->user->uid)
    ->where('type = ?', 'like'))->num;

// 统计收藏数
$favoritesCount = $db->fetchObject($db->select('COUNT(*) AS num')
    ->from($actionTable)
    ->where('uid = ?', $this->user->uid)
    ->where('type = ?', 'collect'))->num;
$ordersTable = Mirai_payTable('orders');
$ordersCount = 0;
if (Mirai_payTableExists($ordersTable)) {
    $ordersCount = $db->fetchObject($db->select('COUNT(*) AS num')
        ->from($ordersTable)
        ->where('uid = ?', $this->user->uid))->num;
}
$wallet = Mirai_payGetWallet((int)$this->user->uid);
$walletBalance = isset($wallet['balance']) ? (float)$wallet['balance'] : 0;
?>
<div class="user-module module-overview">
    <div class="module-header">
        <div class="module-title">仪表中心</div>
    </div>

    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="ri-article-line"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $postsCount; ?></span>
                <span class="stat-label">文章总数</span>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="ri-message-3-line"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $commentsCount; ?></span>
                <span class="stat-label">评论总数</span>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="ri-thumb-up-line"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $likesCount; ?></span>
                <span class="stat-label">点赞文章</span>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="ri-star-line"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $favoritesCount; ?></span>
                <span class="stat-label">收藏文章</span>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><i class="ri-shopping-bag-line"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $ordersCount; ?></span>
                <span class="stat-label">订单总数</span>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="ri-wallet-3-line"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?php echo number_format($walletBalance, 2); ?></span>
                <span class="stat-label">余额</span>
            </div>
        </div>
    </div>

    <div class="recent-activity">
        <div class="section-title"><i class="ri-history-line"></i> 最近动态</div>
        <div class="activity-list">
            <?php
            // 获取最近发表的5篇文章
            $recentPosts = $db->fetchAll($db->select()->from('table.contents')
                ->where('authorId = ?', $this->user->uid)
                ->where('type = ?', 'post')
                ->order('created', \Typecho\Db::SORT_DESC)
                ->limit(5));
                
            if (empty($recentPosts)): ?>
                <div class="empty-state">暂无动态</div>
            <?php else: ?>
                <?php foreach ($recentPosts as $post): ?>
                    <div class="activity-item">
                        <span class="activity-icon post"><i class="ri-edit-line"></i></span>
                        <div class="activity-content">
                            <span class="activity-text">发布了文章 <a href="<?php echo \Typecho\Router::url('post', $post, $this->options->index); ?>"><?php echo $post['title']; ?></a></span>
                            <span class="activity-time"><?php echo (new \Typecho\Date($post['created']))->format('Y-m-d H:i'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
