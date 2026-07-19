<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$options = $this->options;
$popularEnabled = isset($options->searchPopularEnable) && $options->searchPopularEnable === '1';
$popularTitle = $options->searchPopularTitle ?? '热门搜索';
$placeholder = $options->searchPlaceholder ?? '请输入关键字...';

$popularKeywords = [];
if ($popularEnabled && $options->searchPopularKeywords) {
    $popularKeywords = array_slice(array_filter(array_map('trim', explode("\n", trim($options->searchPopularKeywords)))), 0, 20);
}
?>
<div class="gt-search-modal" id="searchModal" role="dialog" aria-modal="true" aria-label="搜索">
    <div class="gt-search-overlay"></div>
    <div class="gt-search-panel">
        <form class="gt-search-form" method="get" action="<?php $options->siteUrl(); ?>">
            <input type="search" id="search-input" name="s" placeholder="<?php echo htmlspecialchars($placeholder); ?>" aria-label="<?php echo htmlspecialchars($placeholder); ?>">
            <button type="submit" aria-label="搜索"><i class="ri-search-line"></i></button>
        </form>
        <?php if ($popularEnabled && $popularKeywords): ?>
        <div class="gt-search-body">
            <div class="gt-search-section" aria-labelledby="popular-title">
                <span id="popular-title" class="gt-search-section-title"><?php echo htmlspecialchars($popularTitle); ?></span>
                <div class="gt-search-tags">
                    <?php foreach ($popularKeywords as $keyword): ?>
                    <a class="gt-search-tag" href="<?php $options->siteUrl(); ?>?s=<?php echo urlencode($keyword); ?>"><?php echo htmlspecialchars($keyword); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>