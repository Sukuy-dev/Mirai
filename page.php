<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<?php $this->need('modules/breadcrumb.php'); ?>

<article class="gt-article-main">
    <header>
        <h1><?php $this->title() ?></h1>
    </header>
    
    <div class="article-content-container">
        <div class="article-content">
            <?php 
            $content = $this->content;
            try {
                $content = Mirai_convertImagesToPicture($content, $this->title);
                if (Mirai_featureEnabled('seo') && (string)$this->options->nofollowExternalLinks !== '0') {
                    $content = Mirai_addNofollowToExternalLinks($content);
                }
            } catch (Exception $e) {
            }
            echo $content;
            ?>
        </div>
    </div>
</article>
<?php $this->need('footer.php'); ?>
