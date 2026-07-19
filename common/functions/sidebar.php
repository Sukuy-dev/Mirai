<?php
/**
 * Mirai Theme - Sidebar Functions Module
 * 边栏渲染函数模块
 *
 * 包含：边栏数据获取、文章列表、评论、管理员信息、标签云等渲染函数
 * 以及移动端边栏小工具渲染函数
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_parseAsideModuleOrder($config) {
    $defaultOrder = ['admin', 'hot', 'recent', 'tags', 'comments'];
    
    if (empty($config)) {
        return $defaultOrder;
    }
    
    $lines = explode("\n", $config);
    $parsed = [];
    
    foreach ($lines as $line) {
        $module = trim($line);
        if (in_array($module, $defaultOrder)) {
            $parsed[] = $module;
        }
    }
    
    return !empty($parsed) ? $parsed : $defaultOrder;
}

function Mirai_getSidebarPosts($limit = 5, $orderBy = 'views') {
    $db = \Typecho\Db::get();
    $limit = (int)$limit;
    $orderField = ($orderBy === 'created') ? 'created' : 'views';
    $select = $db->select('cid', 'title', 'slug', 'text', 'created', 'cover', 'authorId', 'views')
        ->from('table.contents')
        ->where('status = ?', 'publish')
        ->where('type = ?', 'post')
        ->where('created <= ?', time())
        ->order($orderField, \Typecho\Db::SORT_DESC)
        ->limit($limit);
    
    $posts = $db->fetchAll($select);
    
    if (empty($posts)) {
        return [];
    }
    
    $cids = array_column($posts, 'cid');

    $categoryData = $db->fetchAll(
        $db->select('table.relationships.cid', 'table.metas.slug')
            ->from('table.relationships')
            ->join('table.metas', 'table.relationships.mid = table.metas.mid', \Typecho\Db::LEFT_JOIN)
            ->where('table.relationships.cid IN ?', $cids)
            ->where('table.metas.type = ?', 'category')
    );
    
    $categoryMap = [];
    foreach ($categoryData as $cat) {
        if (!isset($categoryMap[$cat['cid']])) {
            $categoryMap[$cat['cid']] = $cat['slug'];
        }
    }
    
    $options = Mirai_opt()->index;
    $result = [];
    foreach ($posts as $row) {
        $row['category'] = $categoryMap[$row['cid']] ?? null;
        $row['permalink'] = \Typecho\Router::url('post', $row, $options);
        $result[] = $row;
    }
    
    return $result;
}

function Mirai_getSidebarHotPosts($limit = 5) {
    return Mirai_getSidebarPosts($limit, 'views');
}

function Mirai_getSidebarRecentPosts($limit = 5) {
    return Mirai_getSidebarPosts($limit, 'created');
}

function Mirai_getSidebarTags($limit = 30, $sort = 'mid') {
    $db = \Typecho\Db::get();
    
    $select = $db->select('mid', 'name', 'slug', 'count')->from('table.metas')
        ->where('type = ?', 'tag')
        ->where('count > ?', 0);
        
    if ($sort == 'count') {
        $select->order('count', \Typecho\Db::SORT_DESC);
    } else if ($sort == 'rand') {
        $select->order('RAND()');
    } else if ($sort == 'name') {
        $select->order('name', \Typecho\Db::SORT_ASC);
    } else {
        $select->order('mid', \Typecho\Db::SORT_DESC);
    }
    
    $select->limit($limit);
    
    $tags = $db->fetchAll($select);
    
    $data = [];
    $options = Mirai_opt();
    foreach ($tags as $tag) {
        $tag['permalink'] = \Typecho\Router::url('tag', $tag, $options->index);
        $data[] = $tag;
    }
    
    return $data;
}

function Mirai_getSidebarRecentComments() {
    $options = Mirai_opt();
    $limit = intval($options->commentsListSize ?? 10);
    
    $commentsWidget = \Widget\Comments\Recent::alloc(['pageSize' => $limit]);
    
    $data = [];
    while ($commentsWidget->next()) {
        $avatar = Mirai_getUserAvatar($commentsWidget->authorId);

        $data[] = [
            'coid' => $commentsWidget->coid,
            'cid' => $commentsWidget->cid,
            'author' => $commentsWidget->author,
            'authorId' => $commentsWidget->authorId,
            'mail' => $commentsWidget->mail,
            'url' => $commentsWidget->url,
            'text' => $commentsWidget->text,
            'created' => $commentsWidget->created,
            'permalink' => $commentsWidget->permalink,
            'title' => $commentsWidget->title,
            'avatar' => $avatar,
        ];
    }
    
    return $data;
}

function Mirai_getSidebarAdminInfo() {
    $options = Mirai_opt();
    $adminBio = $options->adminBio;
    $adminBgImage = $options->adminBgImage;

    $db = \Typecho\Db::get();
    $admin = $db->fetchRow($db->select()->from('table.users')->where('group = ?', 'administrator')->limit(1));

    $adminName = $admin['screenName'] ?? '管理员';
    $adminUid = $admin['uid'] ?? 0;
    if (empty($adminBio)) $adminBio = $admin['bio'] ?? '这里什么也没写。';

    $avatarUrl = Mirai_getUserAvatar($adminUid);
    
    return [
        'name' => $adminName,
        'bio' => $adminBio,
        'avatar' => $avatarUrl,
        'bgImage' => $adminBgImage ? Mirai_normalizeUrl($adminBgImage) : null
    ];
}

if (!function_exists('Mirai_getSidebarIcon')) {
    function Mirai_getSidebarIcon($icon) {
        if ($icon === 'hot') {
            return '<i class="ri-fire-fill"></i>';
        } elseif ($icon === 'recent') {
            return '<i class="ri-file-list-3-line"></i>';
        }
        return '<i class="' . $icon . '"></i>';
    }
}

if (!function_exists('renderSidebarArticleList')) {
    function renderSidebarArticleList($posts, $title, $icon, $index = 0, $style = 'cover') {
        if (empty($posts)) {
            return;
        }
        
        $style = in_array($style, ['cover', 'text']) ? $style : 'cover';
        $delay = ($index + 1) * 0.08;
        
        if ($style === 'text') {
            renderSidebarTextArticleList($posts, $title, $icon, $delay);
            return;
        }
        
        ?>
        <section class="gt-aside-item articles gt-animation gt-animation-init" style="animation-delay: <?php echo $delay; ?>s;">
            <div class="sky-h3"><?php echo Mirai_getSidebarIcon($icon); ?> <?php echo $title; ?></div>
            <div class="gt-aside-articles gt-aside-articles-cover">
                <?php 
                $lazyLoading = Mirai_getDefaultLazyLoading();
                foreach ($posts as $post):
                    $cover = Mirai_getPostCover($post, true);
                ?>
                    <a href="<?php echo $post['permalink']; ?>" class="gt-aside-article-item">
                        <div class="gt-aside-article-cover">
                            <?php echo Mirai_generatePictureTag($cover, $lazyLoading, $post['title'], 'gt-aside-article-img', '320', '180', ['context' => 'aside-article']); ?>
                            <div class="gt-aside-article-info">
                                <div class="gt-aside-article-title"><?php echo $post['title']; ?></div>
                                <div class="gt-aside-article-meta">
                                    <?php echo Mirai_renderViews($post['views'], ['class' => 'gt-aside-article-views']); ?>
                                    <span class="gt-aside-article-time"><?php echo (new \Typecho\Date($post['created']))->format('Y-m-d'); ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('renderSidebarTextArticleList')) {
    function renderSidebarTextArticleList($posts, $title, $icon, $delay = 0.08) {
        ?>
        <section class="gt-aside-item articles gt-animation gt-animation-init" style="animation-delay: <?php echo $delay; ?>s;">
            <div class="sky-h3"><?php echo Mirai_getSidebarIcon($icon); ?> <?php echo $title; ?></div>
            <div class="gt-aside-articles gt-aside-articles-text">
                <?php foreach ($posts as $index => $post): ?>
                    <a href="<?php echo $post['permalink']; ?>" class="gt-aside-article-text-item">
                        <span class="gt-aside-article-rank"><?php echo $index + 1; ?></span>
                        <span class="gt-aside-article-text-title"><?php echo $post['title']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('renderSidebarRecentComments')) {
    function renderSidebarRecentComments($comments, $index = 0) {
        if (empty($comments)) {
            return;
        }
        $delay = ($index + 1) * 0.08;
        ?>
        <section class="gt-aside-item recent-comments gt-animation gt-animation-init" style="animation-delay: <?php echo $delay; ?>s;">
            <div class="sky-h3"><i class="ri-message-3-line"></i> 最新评论</div>
            <div class="gt-aside-comments">
                <?php foreach ($comments as $comment): ?>
                    <a href="<?php echo $comment['permalink']; ?>" class="gt-aside-comment-item" title="<?php echo htmlspecialchars($comment['title'] ?? ''); ?>" rel="nofollow">
                        <img class="gt-aside-comment-avatar" src="<?php echo $comment['avatar']; ?>" alt="<?php echo htmlspecialchars($comment['author']); ?>的头像" width="40" height="40" loading="lazy">
                        <div class="gt-aside-comment-content">
                            <div class="gt-aside-comment-meta">
                                <span class="gt-aside-comment-author"><?php echo htmlspecialchars($comment['author']); ?></span>
                                <span class="gt-aside-comment-time"><?php echo Mirai_formatTime($comment['created']); ?></span>
                            </div>
                            <div class="gt-aside-comment-text"><?php echo htmlspecialchars(strip_tags($comment['text'])); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('renderSidebarAdminInfo')) {
    function renderSidebarAdminInfo($adminInfo, $avatarSize = 120, $index = 0) {
        $delay = ($index + 1) * 0.08;
        $hasBgImage = !empty($adminInfo['bgImage']);
        ?>
        <section class="gt-aside-item user gt-animation gt-animation-init <?php echo $hasBgImage ? 'has-bg-image' : ''; ?>" style="animation-delay: <?php echo $delay; ?>s;">
            <?php if ($hasBgImage): ?>
            <div class="gt-aside-user-bg">
                <img src="<?php echo htmlspecialchars($adminInfo['bgImage']); ?>" alt="背景图" loading="lazy">
                <div class="gt-aside-user-overlay"></div>
            </div>
            <?php endif; ?>
            <img class="gt-aside-avatar" src="<?php echo $adminInfo['avatar']; ?>" alt="<?php echo $adminInfo['name']; ?>的头像" width="<?php echo $avatarSize; ?>" height="<?php echo $avatarSize; ?>">
            <span class="gt-aside-author"><?php echo $adminInfo['name']; ?></span>
            <?php if (!empty($adminInfo['bio'])): ?>
            <span class="gt-aside-bio"><?php echo nl2br(htmlspecialchars($adminInfo['bio'])); ?></span>
            <?php endif; ?>
        </section>
        <?php
    }
}

if (!function_exists('renderSidebarTags')) {
    function renderSidebarTags($tags, $index = 0) {
        $delay = ($index + 1) * 0.08;
        ?>
        <section class="gt-aside-item tags gt-animation gt-animation-init" style="animation-delay: <?php echo $delay; ?>s;">
            <div class="sky-h3"><i class="ri-price-tag-3-line"></i> 标签云</div>
            <div class="gt-aside-tags">
                <?php if (!empty($tags)): ?>
                    <?php foreach ($tags as $tag): ?>
                        <a href="<?php echo $tag['permalink']; ?>" rel="tag" title="查看 <?php echo htmlspecialchars($tag['name']); ?> 相关文章"><span><?php echo $tag['name']; ?></span></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span>暂无标签</span>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('renderSidebarModules')) {
    function renderSidebarModules($options, $avatarSize = 120) {
        $moduleOrder = Mirai_parseAsideModuleOrder($options->asideModuleOrder);
        foreach ($moduleOrder as $module):
            if ($module === 'admin' && $options->asideShowAdmin !== '0'):
                $adminInfo = Mirai_getSidebarAdminInfo();
                renderSidebarAdminInfo($adminInfo, $avatarSize, 0);
            elseif ($module === 'hot' && $options->asideShowHotPosts !== '0'):
                $hotPosts = Mirai_getSidebarHotPosts(intval($options->asideHotPostsNumber ?? 5));
                renderSidebarArticleList($hotPosts, '热门文章', 'hot', 1, $options->asideHotPostsStyle ?? 'cover');
            elseif ($module === 'recent' && $options->asideShowRecent !== '0'):
                $recentPosts = Mirai_getSidebarRecentPosts(intval($options->asideRecentPostsNumber ?? 5));
                renderSidebarArticleList($recentPosts, '最新文章', 'recent', 2, $options->asideRecentPostsStyle ?? 'cover');
            elseif ($module === 'tags' && $options->asideShowTags !== '0'):
                $tags = Mirai_getSidebarTags(intval($options->asideTagsNumber ?? 30), $options->asideTagsSort ?? 'mid');
                renderSidebarTags($tags, 3);
            elseif ($module === 'comments' && $options->asideShowRecentComments !== '0'):
                $recentComments = Mirai_getSidebarRecentComments();
                renderSidebarRecentComments($recentComments, 4);
            endif;
        endforeach;
    }
}

if (!function_exists('renderMobileSidebarWidgets')) {
    function renderMobileSidebarWidgets($options) {
        ?>
        <div>
            <?php renderSidebarModules($options, 80); ?>
        </div>
        <?php
    }
}
