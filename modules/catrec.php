<?php
/**
 * 底部分类推荐文章模块
 * 在首页底部显示指定分类的推荐文章
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!Mirai_featureEnabled('home_category_recommend')) return;
if ($this->options->footerCategoryEnable != '1') return;

$catIds = $this->options->footerCategoryIds;
if (empty($catIds)) return;
$catIds = explode(',', $catIds);

$options = $this->options;

// 获取设置
$categoryNum = intval($options->footerCategoryNum ?? 5);
if ($categoryNum < 1) $categoryNum = 5;
$categorySort = $options->footerCategorySort ?? 'created';

// 解析指定文章设置
$specificPostsMap = [];
if (!empty($options->footerCategorySpecificPosts)) {
    $lines = explode("\n", $options->footerCategorySpecificPosts);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '|') === false) continue;
        $parts = explode('|', $line, 2);
        if (count($parts) < 2) continue;
        $catId = trim($parts[0]);
        $cids = array_map('trim', explode(',', $parts[1]));
        $cids = array_filter($cids, 'is_numeric');
        if (!empty($catId) && !empty($cids)) {
            $specificPostsMap[$catId] = array_values($cids);
        }
    }
}


$categoriesData = [];

$db = \Typecho\Db::get();

foreach ($catIds as $mid) {
        $mid = trim($mid);
        if (empty($mid)) continue;
        
        $category = $db->fetchRow($db->select()->from('table.metas')
            ->where('mid = ?', $mid)
            ->where('type = ?', 'category'));
        if (!$category) continue;
        
        $catUrl = \Typecho\Router::url('category', $category, $options->index);
        
        $posts = [];
        $specificCids = $specificPostsMap[$mid] ?? [];
        
        // 1. 首先获取指定的文章
        if (!empty($specificCids)) {
            $specificCids = array_map('intval', $specificCids);
            $specificPosts = $db->fetchAll($db->select('table.contents.cid', 'table.contents.title', 'table.contents.slug', 'table.contents.created', 'table.contents.authorId', 'table.contents.views', 'table.contents.likes', 'table.contents.text', 'table.contents.cover')
                ->from('table.contents')
                ->where('table.contents.cid IN ?', $specificCids)
                ->where('table.contents.status = ?', 'publish'));
            
            // 按用户指定的顺序排序
            $orderedPosts = [];
            foreach ($specificCids as $cid) {
                foreach ($specificPosts as $post) {
                    if ($post['cid'] == $cid) {
                        $orderedPosts[] = $post;
                        break;
                    }
                }
            }
            $posts = $orderedPosts;
        }
        
        // 2. 如果指定文章数量不足，按排序方式补充
        $remainingNum = $categoryNum - count($posts);
        if ($remainingNum > 0) {
            $excludeCids = array_column($posts, 'cid');
            
            $query = $db->select('table.contents.cid', 'table.contents.title', 'table.contents.slug', 'table.contents.created', 'table.contents.authorId', 'table.contents.views', 'table.contents.likes', 'table.contents.text', 'table.contents.cover')
                ->from('table.contents')
                ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                ->where('table.relationships.mid = ?', $mid)
                ->where('table.contents.status = ?', 'publish')
                ->limit($remainingNum);
            
            // 排除已添加的指定文章
            if (!empty($excludeCids)) {
                $query = $query->where('table.contents.cid NOT IN ?', $excludeCids);
            }
            
            // 设置排序方式
            switch ($categorySort) {
                case 'views':
                    $query = $query->order('table.contents.views', \Typecho\Db::SORT_DESC);
                    break;
                case 'random':
                    $query = $query->order('RAND()');
                    break;
                case 'created':
                default:
                    $query = $query->order('table.contents.created', \Typecho\Db::SORT_DESC);
                    break;
            }
            
            $additionalPosts = $db->fetchAll($query);
            $posts = array_merge($posts, $additionalPosts);
        }
        
        // 为每篇文章添加分类 slug 信息，确保 Router::url 生成正确的链接
        foreach ($posts as &$p) {
            if (!isset($p['category'])) {
                $p['category'] = $category['slug'];
            }
        }
        unset($p); // 取消引用

        // 处理文章permalink
        foreach ($posts as &$post) {
            $post['permalink'] = \Typecho\Router::url('post', $post, $options->index);
        }
        
        // 获取分类封面
        $cover = $options->logThumb ?: (Mirai_getThemeUrl() . '/assets/images/thumb.svg');
        $customCovers = Mirai_parseCategoryCovers($options);
        if (isset($customCovers[$mid])) {
            $cover = $customCovers[$mid];
        } elseif (!empty($posts[0])) {
            $cover = Mirai_getPostCover($posts[0], true);
        }
        $cover = Mirai_normalizeUrl($cover);
        
        // 获取分类描述
        $desc = $category['description'];
        $customDescs = Mirai_parseCategoryDescs($options);
        if (isset($customDescs[$mid])) $desc = $customDescs[$mid];
        
        // 计算总浏览量（使用统一函数）
        $totalViews = Mirai_calculateTotalViews($posts);
        
        $categoriesData[] = [
            'mid' => $mid,
            'name' => $category['name'],
            'slug' => $category['slug'],
            'count' => $category['count'],
            'description' => $desc,
            'cover' => $cover,
            'catUrl' => $catUrl,
            'totalViews' => $totalViews,
            'posts' => $posts
        ];
    }

if (empty($categoriesData)) return;
?>

<section class="gt-cms-section">
    <h2 class="gt-cms-title-h3">分类推荐</h2>
    <div class="gt-cms-grid">
        <?php foreach ($categoriesData as $catIndex => $catData): ?>
        <div class="gt-cms-card gt-animation gt-animation-init" style="animation-delay: <?php echo ($catIndex + 1) * 0.1; ?>s;">
            <div class="gt-cms-header">
                <div class="gt-cms-cover">
                    <?php echo Mirai_generatePictureTag($catData['cover'], Mirai_getDefaultLazyLoading(), $catData['name'], 'lazyload', '400', '225'); ?>
                    <?php echo Mirai_renderViews($catData['totalViews'], ['class' => 'gt-cms-views']); ?>
                </div>
                <div class="gt-cms-info">
                    <div class="gt-cms-meta">
                        <a href="<?php echo $catData['catUrl']; ?>" class="gt-cms-name"><?php echo $catData['name']; ?></a>
                        <a href="<?php echo $catData['catUrl']; ?>" class="gt-cms-more">&gt;更多文章</a>
                    </div>
                    <p class="gt-cms-desc"><?php echo $catData['description'] ?: '暂无描述'; ?></p>
                    <div class="gt-cms-stats">
                        <span class="gt-cms-count"><i class="ri-article-line"></i> <?php echo $catData['count']; ?>篇文章</span>
                    </div>
                </div>
            </div>
            <div class="gt-cms-body">
                <?php if (!empty($catData['posts'])): ?>
                <ul class="gt-cms-list">
                    <?php foreach ($catData['posts'] as $post): ?>
                    <li>
                        <a href="<?php echo $post['permalink']; ?>" title="<?php echo $post['title']; ?>">
                            <?php echo $post['title']; ?>
                        </a>
                        <div class="gt-cms-post-meta">
                            <span class="gt-cms-date"><?php echo (new \Typecho\Date($post['created']))->format('Y-m-d'); ?></span>
                            <?php echo Mirai_renderViews($post['views'], ['showZero' => false, 'class' => 'gt-cms-post-views']); ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="gt-cms-empty"><i class="ri-emotion-sad-line"></i> 暂无文章</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php Mirai_renderHomeLinks(); ?>
