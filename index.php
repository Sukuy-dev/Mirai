<?php
/**
 * 【Mirai未来主题是一款简约优雅的Typecho主题】
 * 【作者QQ】：1461139506
 * 【作者博客】：https://www.sukuy.com
 * 【PHP版本】：PHP 7.4+，建议PHP 8.3或8.4
 * 【Typecho版本】：1.3.0及以上
 * 
 * @package Mirai未来主题
 * @author 苏酷伊Sukuy
 * @version 1.0.2
 * @link https://sukuy.com
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$this->need('header.php');
if (!$this->is('index')) {
    $this->need('modules/breadcrumb.php');
}
?>
<div class="article-list-main">
    <?php 
    $showRecommend = $this->is('index') && $this->options->recommendEnable === '1';
    if ($showRecommend && $this->options->recommendFirstPageOnly === '1') {
        $showRecommend = ($this->request->get('page', 1) <= 1);
    }
    ?>
    <?php if ($showRecommend): ?>
        <?php $this->need('modules/recommend.php'); ?>
    <?php endif; ?>
    <h2 class="gt-cms-title-h3">最新文章</h2>
    <?php
    $gridLayoutValue = Mirai_getFeatureValue($this->options->gridLayout ? $this->options->gridLayout : '3', '3', 'grid_layout');
    $singleColumnEnabled = ($gridLayoutValue === '1');
    ?>
    <section id="article-list-container" class="article-list <?php echo $singleColumnEnabled ? 'single-column' : ''; ?>" data-grid="<?php echo $gridLayoutValue; ?>">
    <?php 
    if ($this->is('index')) {
        $page = $this->request->get('page', 1);
        if ($page < 1) $page = 1;
        
        $pageSize = $this->options->pageSize ? intval($this->options->pageSize) : 5;
        if ($pageSize < 1) $pageSize = 5;
        
        $posts = Mirai_getHomePosts($page, $pageSize);
    } else {
        $posts = null;
    }
    
    if ($this->is('index') && !empty($posts)): 
        $articleIndex = 0;
        foreach ($posts as $post):
            $articleIndex++;
            $isFirst = ($articleIndex === 1 && $page === 1);
            if (!isset($post['author']) && isset($post['screenName'])) {
                $post['author'] = $post['screenName'];
            }
            
            Mirai_renderPostItem($post, ['isWidget' => false, 'isFirst' => $isFirst, 'isIndex' => true]);
        endforeach;
    elseif ($this->have()): 
        $articleIndex = 0;
        while($this->next()): 
            $articleIndex++;
            $isFirst = ($articleIndex === 1 && !isset($_GET['page']));
            Mirai_renderPostItem($this, ['isWidget' => true, 'isFirst' => $isFirst]);
        endwhile;
    else: ?>
        <div class="gt-empty">
            <img src="<?php $this->options->themeUrl('assets/images/empty.svg'); ?>" alt="暂无文章" width="120" height="120">
            <p>暂无文章</p>
        </div>
    <?php endif; ?>
    </section>
    <?php 
    if ($this->is('index') && !empty($posts)): 
        try {
            $db = \Typecho\Db::get();
            $select = $db->select('COUNT(*)')->from('table.contents')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', 'post')
                ->where('table.contents.created <= ?', time())
                ->where('table.contents.password IS NULL OR table.contents.password = ?', '');
            
            $excludedIds = Mirai_getRecommendExcludedIds();
            if (!empty($excludedIds)) {
                $select->where('table.contents.cid NOT IN ?', $excludedIds);
            }
            
            $totalPosts = $db->fetchObject($select)->{'COUNT(*)'};
            $totalPages = $pageSize > 0 ? ceil($totalPosts / $pageSize) : 1;
        } catch (Exception $e) {
            $totalPages = 1;
        }
        
        $that = $this;
        echo Mirai_customPagination($page, $totalPages, function($p) use ($that) {
            return Mirai_getPageUrl($that, $p);
        });
        
    elseif ($this->have()): 
        $current = $this->getCurrentPage();
        $total = ceil($this->getTotal() / $this->parameter->pageSize);
        
        $that = $this;
        echo Mirai_customPagination($current, $total, function($p) use ($that) {
            return Mirai_getPageUrl($that, $p);
        });
    endif; ?>
    <?php if ($this->is('index')): ?>
        <?php $this->need('modules/catrec.php'); ?>
    <?php endif; ?>
</div>
<?php $this->need('footer.php'); ?>