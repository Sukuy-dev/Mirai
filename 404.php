<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->is404 = true; ?>
<?php $this->need('header.php'); ?>
<article class="gt-article-main">
    <div class="main-inner-content gt-center-content">
        <div class="gt-empty">
            <img src="<?php $this->options->themeUrl('assets/images/empty.svg'); ?>" alt="404" class="gt-empty-img" width="120" height="120">
            <h1 class="gt-empty-title">404</h1>
            <p class="gt-empty-desc">抱歉，您访问的页面不存在或已被删除</p>
        </div>
    </div>
</article>
<?php $this->need('footer.php'); ?>