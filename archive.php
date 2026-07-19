<?php
/**
 * 归档页面模板（分类、标签、作者、搜索、日期归档）
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
$this->need('modules/breadcrumb.php');
?>
<div class="article-list-main">
    <?php if ($this->is('category')): ?>
    <h1 class="gt-cms-title-h3 visually-hidden"><?php $this->archiveTitle('', '', ''); ?></h1>
    <?php elseif ($this->is('tag')): ?>
    <h1 class="gt-cms-title-h3 visually-hidden">标签：<?php $this->archiveTitle('', '', ''); ?></h1>
    <?php elseif ($this->is('search')): ?>
    <h1 class="gt-cms-title-h3 visually-hidden">搜索：<?php $this->archiveTitle('', '', ''); ?></h1>
    <?php elseif ($this->is('date')): ?>
    <h1 class="gt-cms-title-h3 visually-hidden"><?php $this->archiveTitle('', '', ''); ?></h1>
    <?php endif; ?>
    <?php
    $gridLayoutValue = Mirai_getFeatureValue($this->options->gridLayout ? $this->options->gridLayout : '3', '3', 'grid_layout');
    $singleColumnEnabled = ($gridLayoutValue === '1');
    ?>
    <section id="article-list-container" class="article-list <?php echo $singleColumnEnabled ? 'single-column' : ''; ?>" data-grid="<?php echo $gridLayoutValue; ?>">
    <?php if ($this->have()): ?>
        <?php 
        $articleIndex = 0;
        while($this->next()): 
            $articleIndex++;
            $isFirst = ($articleIndex === 1 && $this->getCurrentPage() === 1);
            Mirai_renderPostItem($this, ['isWidget' => true, 'isFirst' => $isFirst, 'isIndex' => false]);
        endwhile;
        ?>
    <?php else: ?>
        <div class="gt-empty">
            <img src="<?php $this->options->themeUrl('assets/images/empty.svg'); ?>" alt="暂无文章" width="120" height="120">
            <p>暂无文章</p>
        </div>
    <?php endif; ?>
    </section>
    <?php if ($this->have()): ?>
        <?php 
        $current = $this->getCurrentPage();
        $total = ceil($this->getTotal() / $this->parameter->pageSize);
        
        $that = $this;
        echo Mirai_customPagination($current, $total, function($page) use ($that) {
            return Mirai_getPageUrl($that, $page);
        });
        ?>
    <?php endif; ?>
</div>
<?php $this->need('footer.php'); ?>