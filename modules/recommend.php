<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (!Mirai_featureEnabled('home_category_recommend')) return;
if (!isset($this->options) || $this->options->recommendEnable != '1') return;

$lazyLoading = Mirai_getDefaultLazyLoading();
$defaultCover = Mirai_getDefaultThumb();

$customImages = [
    'left' => ['image' => '', 'url' => ''],
    'right1' => ['image' => '', 'url' => ''],
    'right2' => ['image' => '', 'url' => '']
];

$articleIds = [];

if (!empty($this->options->recommendContent)) {
    $lines = explode("\n", $this->options->recommendContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if (strpos($line, '|') !== false) {
            $parts = explode('|', $line);
            if (count($parts) >= 2) {
                $position = trim($parts[0]);
                $image = trim($parts[1]);

                $alt = '';
                $url = '';

                if (isset($parts[2])) {
                    $param2 = trim($parts[2]);
                    if (!empty($param2) && (strpos($param2, 'http://') === 0 || strpos($param2, 'https://') === 0)) {
                        $url = $param2;
                        $alt = isset($parts[3]) ? trim($parts[3]) : '';
                    } else {
                        $alt = $param2;
                    }
                }

                if (in_array($position, ['left', 'right1', 'right2']) && !empty($image)) {
                    $customImages[$position] = ['image' => $image, 'url' => $url, 'alt' => $alt];
                }
            }
        } else {
            $cid = intval($line);
            if ($cid > 0) {
                $articleIds[] = $cid;
            }
        }
    }
    $articleIds = array_values(array_unique($articleIds));
}

$recommendTopEnable = $this->options->recommendTopEnable === '1';
$recommendTopIds = [];
if ($recommendTopEnable && !empty($this->options->recommendTopIds)) {
    $lines = explode("\n", $this->options->recommendTopIds);
    foreach ($lines as $line) {
        $cid = intval(trim($line));
        if ($cid > 0) {
            $recommendTopIds[] = $cid;
        }
    }
    $recommendTopIds = array_values(array_unique($recommendTopIds));
    $recommendTopIds = array_slice($recommendTopIds, 0, 7);
}

$topLeft = null;
$topRight1 = null;
$topRight2 = null;
$bottomItems = [];

$allNeededCids = [];
if ($recommendTopEnable && !empty($recommendTopIds)) {
    $allNeededCids = array_merge($allNeededCids, $recommendTopIds);
}
$allNeededCids = array_merge($allNeededCids, $articleIds);

$batchPosts = [];
if (!empty($allNeededCids)) {
    $batchPosts = Mirai_getRecommendPostsBatch($allNeededCids);
}

$topPostCids = $recommendTopEnable ? $recommendTopIds : [];

$getPostFunc = function($configId) use (&$topPostCids, &$batchPosts) {
    static $topConsumed = 0;
    
    if (!empty($topPostCids) && $topConsumed < count($topPostCids)) {
        $cid = $topPostCids[$topConsumed];
        $topConsumed++;
        if (isset($batchPosts[$cid])) {
            $post = $batchPosts[$cid];
            $post['isTop'] = true;
            return $post;
        }
    }
    
    if ($configId !== null && isset($batchPosts[$configId])) {
        return $batchPosts[$configId];
    }
    
    return null;
};

$articleIndex = 0;

if (empty($customImages['left']['image'])) {
    $topLeft = $getPostFunc(isset($articleIds[$articleIndex]) ? $articleIds[$articleIndex] : null);
    $articleIndex++;
}
if (empty($customImages['right1']['image'])) {
    $topRight1 = $getPostFunc(isset($articleIds[$articleIndex]) ? $articleIds[$articleIndex] : null);
    $articleIndex++;
}
if (empty($customImages['right2']['image'])) {
    $topRight2 = $getPostFunc(isset($articleIds[$articleIndex]) ? $articleIds[$articleIndex] : null);
    $articleIndex++;
}

for ($i = 0; $i < 4; $i++) {
    if (isset($articleIds[$articleIndex + $i])) {
        $post = $getPostFunc($articleIds[$articleIndex + $i]);
        if ($post) {
            $bottomItems[] = $post;
        }
    }
}

$excludedIds = [];
if ($topLeft && isset($topLeft['cid'])) $excludedIds[] = $topLeft['cid'];
if ($topRight1 && isset($topRight1['cid'])) $excludedIds[] = $topRight1['cid'];
if ($topRight2 && isset($topRight2['cid'])) $excludedIds[] = $topRight2['cid'];
if (!empty($bottomItems)) {
    foreach ($bottomItems as $item) {
        if (isset($item['cid'])) {
            $excludedIds[] = $item['cid'];
        }
    }
}
Mirai_setRecommendExcludedIds($excludedIds);

$hasTopContent = $topLeft || $topRight1 || $topRight2;
$hasCustomImage = !empty($customImages['left']['image']) || !empty($customImages['right1']['image']) || !empty($customImages['right2']['image']);

function Mirai_renderCustomImage($imageUrl, $linkUrl, $class, $width, $height, $alt = '', $lazyLoading = 'lazyload') {
    $imgTag = Mirai_generatePictureTag($imageUrl, $lazyLoading, $alt, '', $width, $height, ['context' => $class, 'priority' => true]);
    $wrapperClass = $class . ' is-image';

    if (!empty($linkUrl)) {
        return '<a href="' . htmlspecialchars($linkUrl) . '" class="' . $wrapperClass . ' active" target="_blank" rel="noopener">' . $imgTag . '</a>';
    } else {
        return '<div class="' . $wrapperClass . '">' . $imgTag . '</div>';
    }
}
?>

<section class="gt-recommend-section">
    <h2 class="gt-cms-title-h3 visually-hidden">精选推荐</h2>
    <?php if ($hasTopContent || $hasCustomImage): ?>
    <div class="gt-recommend-top">
        <?php if (!empty($customImages['left']['image'])): ?>
            <?php echo Mirai_renderCustomImage($customImages['left']['image'], $customImages['left']['url'], 'gt-recommend-main', '800', '450', $customImages['left']['alt'] ?: '精选推荐图片-主图', $lazyLoading); ?>
        <?php elseif ($topLeft): 
            $cover = $topLeft['cover'] ?: $defaultCover;
            $isTop = !empty($topLeft['isTop']);
        ?>
        <a href="<?php echo $topLeft['permalink']; ?>" class="gt-recommend-main active">
            <?php echo Mirai_generatePictureTag($cover, $lazyLoading, $topLeft['title'], '', '800', '450', ['context' => 'recommend-main', 'priority' => true]); ?>
            <?php if ($isTop): ?>
            <span class="gt-recommend-top-badge">置顶</span>
            <?php endif; ?>
            <div class="gt-recommend-overlay">
                <span class="gt-recommend-tag">推荐</span>
                <h3 class="text-truncate"><?php echo $topLeft['title']; ?></h3>
            </div>
        </a>
        <?php endif; ?>
        
        <?php if ($topRight1 || $topRight2 || !empty($customImages['right1']['image']) || !empty($customImages['right2']['image'])): ?>
        <div class="gt-recommend-right">
            <?php if (!empty($customImages['right1']['image'])): ?>
                <?php echo Mirai_renderCustomImage($customImages['right1']['image'], $customImages['right1']['url'], 'gt-recommend-item', '400', '225', $customImages['right1']['alt'] ?: '精选推荐图片-侧边1', $lazyLoading); ?>
            <?php elseif ($topRight1): 
                $cover = $topRight1['cover'] ?: $defaultCover; 
                $isTop = !empty($topRight1['isTop']);
            ?>
            <a href="<?php echo $topRight1['permalink']; ?>" class="gt-recommend-item active">
                <?php echo Mirai_generatePictureTag($cover, $lazyLoading, $topRight1['title'], '', '400', '225', ['context' => 'recommend-item', 'priority' => true]); ?>
                <?php if ($isTop): ?>
                <span class="gt-recommend-top-badge">置顶</span>
                <?php endif; ?>
                <div class="gt-recommend-overlay">
                    <span class="gt-recommend-tag">推荐</span>
                    <h3 class="text-truncate"><?php echo $topRight1['title']; ?></h3>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if (!empty($customImages['right2']['image'])): ?>
                <?php echo Mirai_renderCustomImage($customImages['right2']['image'], $customImages['right2']['url'], 'gt-recommend-item', '400', '225', $customImages['right2']['alt'] ?: '精选推荐图片-侧边2', $lazyLoading); ?>
            <?php elseif ($topRight2): 
                $cover = $topRight2['cover'] ?: $defaultCover; 
                $isTop = !empty($topRight2['isTop']);
            ?>
            <a href="<?php echo $topRight2['permalink']; ?>" class="gt-recommend-item active">
                <?php echo Mirai_generatePictureTag($cover, $lazyLoading, $topRight2['title'], '', '400', '225', ['context' => 'recommend-item', 'priority' => true]); ?>
                <?php if ($isTop): ?>
                <span class="gt-recommend-top-badge">置顶</span>
                <?php endif; ?>
                <div class="gt-recommend-overlay">
                    <span class="gt-recommend-tag">推荐</span>
                    <h3 class="text-truncate"><?php echo $topRight2['title']; ?></h3>
                </div>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($bottomItems)): ?>
    <div class="gt-recommend-bottom">
        <?php foreach ($bottomItems as $index => $item): 
            $delay = ($index + 1) * 0.05;
            $cover = $item['cover'] ?: $defaultCover; 
            $isTop = !empty($item['isTop']);
        ?>
        <a href="<?php echo $item['permalink']; ?>" class="gt-recommend-card gt-animation gt-animation-init active" style="animation-delay: <?php echo $delay; ?>s;">
            <?php if ($isTop): ?>
            <span class="gt-recommend-top-badge">置顶</span>
            <?php endif; ?>
            <div class="gt-recommend-thumb">
                <?php echo Mirai_generatePictureTag($cover, $lazyLoading, $item['title'], '', '200', '113', ['context' => 'recommend-card']); ?>
            </div>
            <h3 class="text-truncate"><?php echo $item['title']; ?></h3>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>