<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$db = \Typecho\Db::get();
$userFields = 'uid, name, screenName, mail, url, created, motto, cover';

$authorId = 0;
$authorSlug = '';

if (isset($this->parameter->uid) && (int)$this->parameter->uid > 0) {
    $authorId = (int)$this->parameter->uid;
} elseif (isset($this->author) && is_object($this->author) && isset($this->author->uid)) {
    $authorId = (int)$this->author->uid;
    $authorSlug = isset($this->author->name) ? (string)$this->author->name : '';
} elseif (isset($this->authorId)) {
    $authorId = (int)$this->authorId;
}

if (!$authorSlug && method_exists($this->request, 'getParam')) {
    $authorSlug = (string)$this->request->getParam('name') ?: (string)$this->request->getParam('author');
}
if (!$authorSlug) {
    $pathInfo = method_exists($this->request, 'getPathInfo') ? (string)$this->request->getPathInfo() : '';
    if (preg_match('#/author/([^/]+)/?#', $pathInfo, $matches)) {
        $authorSlug = urldecode($matches[1]);
    }
}

$authorRow = null;
if ($authorId > 0) {
    $authorRow = $db->fetchRow(
        $db->select($userFields)->from('table.users')->where('uid = ?', $authorId)->limit(1)
    );
}
if (!$authorRow && $authorSlug !== '') {
    $authorRow = $db->fetchRow(
        $db->select($userFields)->from('table.users')
            ->where('name = ?', $authorSlug)->orWhere('screenName = ?', $authorSlug)->limit(1)
    );
}

$authorName = '作者';
$authorMail = '';
$authorUrl = '';
$authorCreated = 0;
$authorMotto = '';
$authorCover = '/usr/themes/Mirai/assets/images/banner.webp';

if ($authorRow) {
    $authorId = (int)$authorRow['uid'];
    $authorSlug = (string)$authorRow['name'];
    $authorName = (string)$authorRow['screenName'] ?: $authorSlug ?: '作者';
    $authorMail = (string)$authorRow['mail'];
    $authorUrl = (string)$authorRow['url'];
    $authorCreated = (int)$authorRow['created'];
    $authorMotto = isset($authorRow['motto']) ? trim((string)$authorRow['motto']) : '';
    if (!empty($authorRow['cover'])) {
        $authorCover = Mirai_normalizeUrl((string)$authorRow['cover']);
    }
}

$authorAvatar = $authorId > 0 ? Mirai_getUserAvatar($authorId) : Mirai_getDefaultAvatar();
$authorPostCount = 0;
$authorCommentCount = 0;

if ($authorId > 0) {
    $postStatRow = $db->fetchObject(
        $db->select('COUNT(*) AS postCount')
            ->from('table.contents')
            ->where('authorId = ?', $authorId)
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
    );
    $authorPostCount = (int)$postStatRow->postCount;

    $commentRow = $db->fetchObject(
        $db->select('COUNT(table.comments.coid) AS num')
            ->from('table.comments')
            ->join('table.contents', 'table.comments.cid = table.contents.cid', \Typecho\Db::INNER_JOIN)
            ->where('table.contents.authorId = ?', $authorId)
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.comments.status = ?', 'approved')
    );
    $authorCommentCount = (int)$commentRow->num;
}

$totalPostsInArchive = (int)$this->getTotal();
$currentPage = max(1, (int)$this->getCurrentPage());
$pageSize = max(1, (int)$this->parameter->pageSize);
$totalPages = (int)ceil($totalPostsInArchive / $pageSize);
$hasPosts = $this->have();
$gridLayoutValue = Mirai_getFeatureValue($this->options->gridLayout ? $this->options->gridLayout : '3', '3', 'grid_layout');
$singleColumnEnabled = ($gridLayoutValue === '1');
?>
<?php $this->need('header.php'); ?>

<div class="gt-author-page">
    <section class="gt-author-header">
        <div class="gt-author-cover">
            <img src="<?php echo htmlspecialchars($authorCover, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
            <div class="gt-author-cover-mask"></div>
        </div>
        <div class="gt-author-header-content">
            <div class="gt-author-header-info">
                <div class="gt-author-avatar-wrap">
                    <img src="<?php echo htmlspecialchars($authorAvatar, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                </div>
                <div class="gt-author-main">
                    <h1 class="gt-author-name"><?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php if ($authorMotto !== ''): ?>
                    <p class="gt-author-desc"><?php echo htmlspecialchars($authorMotto, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php else: ?>
                    <p class="gt-author-desc">暂无个人简介</p>
                    <?php endif; ?>
                    <div class="gt-author-meta-line">
                        <span class="gt-author-meta-item">
                            <i class="ri-article-line"></i>
                            <?php echo Mirai_formatNumber($authorPostCount); ?>
                        </span>
                        <span class="gt-author-meta-item">
                            <i class="ri-message-3-line"></i>
                            <?php echo Mirai_formatNumber($authorCommentCount); ?>
                        </span>
                        <?php if ($authorCreated > 0): ?>
                        <span class="gt-author-meta-item">加入于 <?php echo (new \Typecho\Date($authorCreated))->format('Y-m-d'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="gt-author-header-btns">
                    <?php if ($authorUrl !== ''): ?>
                    <a href="<?php echo htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">网站</a>
                    <?php endif; ?>
                    <?php if ($authorMail !== ''): ?>
                    <a href="mailto:<?php echo htmlspecialchars($authorMail, ENT_QUOTES, 'UTF-8'); ?>">邮箱</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="gt-author-posts">
        <div class="article-list-main">
            <section id="article-list-container" class="article-list <?php echo $singleColumnEnabled ? 'single-column' : ''; ?>" data-grid="<?php echo $gridLayoutValue; ?>">
            <?php if ($hasPosts): ?>
                <?php while ($this->next()): ?>
                    <?php Mirai_renderPostItem($this, ['isWidget' => true, 'isFirst' => false, 'isIndex' => false]); ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="gt-empty">
                    <img src="<?php $this->options->themeUrl('assets/images/empty.svg'); ?>" alt="暂无文章" width="120" height="120">
                    <p>该作者暂未发布文章</p>
                </div>
            <?php endif; ?>
            </section>
        </div>
        <?php if ($totalPages > 1): ?>
        <?php echo Mirai_customPagination($currentPage, $totalPages, function ($page) { return Mirai_getPageUrl($this, $page); }); ?>
        <?php endif; ?>
    </section>
</div>

<?php $this->need('footer.php'); ?>