<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$db = \Typecho\Db::get();
$user_id = $this->user->uid;

$isLiked = Mirai_isLiked($this->cid, $user_id);
$isCollected = Mirai_isCollected($this->cid, $user_id);
$likeCount = (int)($this->likes ?? 0);
$paySettings = Mirai_payPostSettings($this->cid);
$payCanUse = Mirai_payEnabled() && Mirai_payAvailableForPost($paySettings);
$isPostAuthor = (int)$user_id > 0 && isset($this->authorId) && (int)$this->authorId === (int)$user_id;
$payHasAccess = !$payCanUse ? true : ($isPostAuthor || Mirai_payHasPaid($this->cid, (int)$user_id));
$payToken = \Widget\Security::alloc()->getToken('api');
?>
<?php $this->need('header.php'); ?>
<?php $this->need('modules/breadcrumb.php'); ?>

<div id="main-content">
<article class="gt-article-main">
    <header>
        <h1><?php $this->title() ?></h1>
        <div class="article-meta">
            <div class="article-meta-left">
                <div class="article-author-box">
                    <?php
                    $authorUrl = Mirai_getAuthorArchiveUrl($this->authorId);
                    $avatarUrl = $this->authorId ? Mirai_getUserAvatar($this->authorId) : Mirai_getDefaultAvatar();
                    $authorName = htmlspecialchars($this->author->screenName, ENT_QUOTES, 'UTF-8');
                    ?>
                    <a class="article-author-avatar" href="<?php echo $authorUrl ? htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') : 'javascript:void(0);'; ?>">
                        <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $authorName; ?>" loading="lazy">
                    </a>
                    <div class="article-author-info">
                        <a class="article-author-name" href="<?php echo $authorUrl ? htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') : 'javascript:void(0);'; ?>"><?php echo $authorName; ?></a>
                        <div class="article-publish-time">
                            <time datetime="<?php echo Mirai_formatISODate($this->created); ?>" itemprop="datePublished"><?php $this->date('Y-m-d'); ?></time>
                            <?php if ($this->modified > $this->created): ?>
                            <span class="time-separator"> </span>
                            <time itemprop="dateModified" datetime="<?php echo Mirai_formatISODate($this->modified); ?>">更新于<?php echo (new \Typecho\Date($this->modified))->format('Y-m-d'); ?></time>
                            <?php endif; ?>
                            <span class="time-separator"> </span>
                            <span id="article-views" data-views="<?php echo (int)($this->views ?? 0); ?>"><i class="ri-eye-line"></i><?php echo Mirai_formatNumber($this->views ?? 0, 'views'); ?>阅读</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="article-content-container">
        <?php if ($this->options->displaySummary): ?>
            <?php 
            $excerpt = Mirai_getPostExcerpt($this);
            if (!empty($excerpt)) {
                echo '<div class="article-excerpt"><div class="article-excerpt-title">本文摘要</div><div class="article-excerpt-content">' . nl2br(htmlspecialchars($excerpt)) . '</div></div>';
            } elseif ($this->excerpt) {
                echo '<div class="article-excerpt"><div class="article-excerpt-title">本文摘要</div><div class="article-excerpt-content">';
                $this->excerpt(100, '...');
                echo '</div></div>';
            }
            ?>
        <?php endif; ?>
        
        <div class="article-content">
            <?php
            $content = $this->content;
            $payBoxInlineRendered = false;
            $payBoxHtml = '';
            $payLocked = false;
            try {
                $content = Mirai_convertImagesToPicture($content, $this->title);
                if (Mirai_featureEnabled('seo') && (string)$this->options->nofollowExternalLinks !== '0') {
                    $content = Mirai_addNofollowToExternalLinks($content);
                }
                if ($payCanUse) {
                    $payFiltered = Mirai_payFilterPostContent($content, $this, $payHasAccess);
                    $content = $payFiltered['content'];
                    $payLocked = !empty($payFiltered['locked']);
                    if (!$payHasAccess && $payLocked) {
                        $payBoxHtml = Mirai_payRenderPurchaseBox($this, $paySettings, (int)$user_id, $payToken, 'inline');
                        if (strpos($content, '<!--MIRAI_PAYBOX_INLINE-->') !== false) {
                            $content = str_replace('<!--MIRAI_PAYBOX_INLINE-->', $payBoxHtml, $content);
                            $payBoxInlineRendered = true;
                        }
                    }
                }
            } catch (Exception $e) {
            }
            echo $content;
            ?>
        </div>
        <?php
        if ($payCanUse && !$payHasAccess && $payLocked && !$payBoxInlineRendered) {
            if ($payBoxHtml === '') {
                $payBoxHtml = Mirai_payRenderPurchaseBox($this, $paySettings, (int)$user_id, $payToken);
            }
            echo $payBoxHtml;
        }
        ?>
        <?php echo Mirai_getTags($this); ?>

        <p class="actions-title">觉得内容不错？我要</p>
        <div class="article-actions-bar">
            <button class="action-btn like-btn <?php echo $isLiked ? 'liked' : ''; ?>" onclick="likeArticle(<?php echo $this->cid; ?>)" id="likeBtn">
                <div class="btn-icon-wrap">
                    <i class="ri-thumb-up-line"></i>
                </div>
                <span class="btn-text">点赞 <em class="count"><?php echo $likeCount; ?></em></span>
            </button>

            <?php if (isset($this->options->displayReward) && $this->options->displayReward): ?>
            <button class="action-btn reward-btn" onclick="openRewardModal()">
                <div class="btn-icon-wrap">
                    <i class="ri-red-packet-line"></i>
                </div>
                <span class="btn-text">赞赏</span>
            </button>
            <?php endif; ?>

            <button class="action-btn share-btn" onclick="shareArticle()">
                <div class="btn-icon-wrap">
                    <i class="ri-share-forward-line"></i>
                </div>
                <span class="btn-text">分享</span>
            </button>

            <button class="action-btn collect-btn <?php echo $isCollected ? 'collected' : ''; ?>" onclick="collectArticle(<?php echo $this->cid; ?>)" id="collectBtn">
                <div class="btn-icon-wrap">
                    <i class="ri-star-line"></i>
                </div>
                <span class="btn-text">收藏</span>
            </button>
        </div>
        
        <?php $this->need('modules/reward.php'); ?>
        
    </div>
</article>
</div>

<?php echo Mirai_copyright($this); ?>

<nav class="gt-post-navigation">
    <?php
    $nextPost = $db->fetchRow($db->select('cid', 'title', 'slug', 'created')->from('table.contents')
        ->where('created > ?', $this->created)
        ->where('created <= ?', $this->options->time)
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post')
        ->where("password IS NULL OR password = ''")
        ->order('created', \Typecho\Db::SORT_ASC)
        ->limit(1));
    $prevPost = $db->fetchRow($db->select('cid', 'title', 'slug', 'created')->from('table.contents')
        ->where('created < ?', $this->created)
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post')
        ->where("password IS NULL OR password = ''")
        ->order('created', \Typecho\Db::SORT_DESC)
        ->limit(1));
    ?>
    <?php if ($nextPost): ?>
    <a href="<?php echo \Typecho\Router::url('post', $nextPost, $this->options->index); ?>" class="gt-nav-prev" title="<?php echo htmlspecialchars($nextPost['title'], ENT_QUOTES, 'UTF-8'); ?>">
        <span class="gt-nav-label"><i class="ri-arrow-left-line"></i> 上一篇</span>
        <span class="gt-nav-title"><?php echo htmlspecialchars($nextPost['title'], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>
    <?php else: ?>
    <span class="gt-nav-prev gt-nav-empty"></span>
    <?php endif; ?>
    <?php if ($prevPost): ?>
    <a href="<?php echo \Typecho\Router::url('post', $prevPost, $this->options->index); ?>" class="gt-nav-next" title="<?php echo htmlspecialchars($prevPost['title'], ENT_QUOTES, 'UTF-8'); ?>">
        <span class="gt-nav-label">下一篇 <i class="ri-arrow-right-line"></i></span>
        <span class="gt-nav-title"><?php echo htmlspecialchars($prevPost['title'], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>
    <?php else: ?>
    <span class="gt-nav-next gt-nav-empty"></span>
    <?php endif; ?>
</nav>

<?php echo Mirai_renderRelatedPosts($this); ?>

<section class="gt-comment" id="comment" data-cid="<?php echo $this->cid; ?>">
    <?php $this->need('modules/comments.php'); ?>
</section>

<?php $this->need('footer.php'); ?>