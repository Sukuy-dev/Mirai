<?php
/**
 * Mirai 主题 - 评论功能模块
 * 
 * 包含：评论回调处理、评论配置、评论渲染、评论状态检查等
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$GLOBALS['MIRAI_COMMENT_IP'] = [];

function Mirai_filterCommentBeforeInsert($comment, $obj) {
    $options = Mirai_opt();
    $guestAllowed = !isset($options->commentsGuestAllowed) || !empty($options->commentsGuestAllowed);
    if (!$guestAllowed) {
        $user = Mirai_user();
        if (!$user->hasLogin()) {
            throw new \Typecho\Exception(_t('请先登录后再发表评论'));
        }
    }

    $rawIp = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));

    if (!function_exists('Mirai_getClientIp')) {
        $file = dirname(__DIR__) . '/functions/location.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }

    $resolvedIp = '';
    if (function_exists('Mirai_getClientIp')) {
        $resolvedIp = trim((string)Mirai_getClientIp());
        if ($resolvedIp === '') {
            $resolvedIp = $rawIp;
        }
    } else {
        $resolvedIp = $rawIp;
    }

    if ($resolvedIp !== '') {
        $comment['ip'] = $resolvedIp;
    }

    $GLOBALS['MIRAI_COMMENT_IP'] = [
        'ip_raw' => $rawIp,
        'ip_resolved' => $resolvedIp
    ];

    return $comment;
}

function Mirai_onFinishComment($comment) {
    if (!function_exists('Mirai_fetchIpLocation')) {
        $file = dirname(__DIR__) . '/functions/location.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            return $comment;
        }
    }

    $options = Mirai_opt();
    if (empty($options->ipLocationEnable)) {
        return $comment;
    }

    $ipData = $GLOBALS['MIRAI_COMMENT_IP'] ?? [];
    $ipRaw = $ipData['ip_raw'] ?? '';
    $ipResolved = $ipData['ip_resolved'] ?? '';
    
    $dbIp = isset($comment->ip) ? trim((string)$comment->ip) : '';
    if ($dbIp === '') {
        return $comment;
    }

    $queryIp = $ipRaw ?: $ipResolved ?: $dbIp;
    $format = $options->ipLocationFormat ?: 'province_city';
    $locationData = Mirai_fetchIpLocation($queryIp);

    if ($locationData) {
        $location = Mirai_formatIpLocation($locationData, $format);
        if ($location) {
            $db = \Typecho\Db::get();
            try {
                $updateData = [
                    'ip_location' => $location,
                    'ip_raw' => $ipRaw ?: $dbIp,
                    'ip_resolved' => $ipResolved ?: $dbIp
                ];
                $ipInfo = [
                    'ip_raw' => $ipRaw ?: $dbIp,
                    'ip_resolved' => $ipResolved ?: $dbIp,
                    'query_ip' => $queryIp,
                    'location' => $locationData
                ];
                $ipInfoJson = json_encode($ipInfo, JSON_UNESCAPED_UNICODE);
                if (is_string($ipInfoJson)) {
                    $updateData['ip_info'] = $ipInfoJson;
                }

                $db->query($db->update('table.comments')
                    ->rows($updateData)
                    ->where('coid = ?', $comment->coid));
            } catch (Throwable $e) {
            }
        }
    }

    $GLOBALS['MIRAI_COMMENT_IP'] = [];

    return $comment;
}

function Mirai_getCommentConfig() {
    static $config = null;
    
    if ($config === null) {
        $options = Mirai_opt();
        
        $config = [
            'showAvatar' => !empty($options->commentsShowAvatar),
            'showUrl' => !empty($options->commentsShowUrl),
            'urlNofollow' => Mirai_featureEnabled('seo') && !empty($options->nofollowExternalLinks),

            'threaded' => !empty($options->commentsThreaded),
            'maxNestingLevels' => min(7, max(2, intval($options->commentsMaxNestingLevels ?: 5))),

            'requireMail' => !empty($options->commentsRequireMail),
            'requireUrl' => !empty($options->commentsRequireUrl),
            'guestAllowed' => !isset($options->commentsGuestAllowed) || !empty($options->commentsGuestAllowed),

            'pageSize' => intval($options->commentsPageSize ?: 10),
            'pageBreak' => !empty($options->commentsPageBreak),
            'globalEnable' => !isset($options->commentsGlobalEnable) || !empty($options->commentsGlobalEnable),
        ];
    }
    
    return $config;
}

function Mirai_renderComment($comments, $options = []) {
    try {
        $config = Mirai_getCommentConfig();
        $isAuthor = $comments->authorId && $comments->authorId == $comments->ownerId;
        $masterClass = $isAuthor ? 'gt-comment-master' : '';
        $currentLevel = intval($comments->levels);
        $canReply = $config['threaded'] && $currentLevel < $config['maxNestingLevels'];
?>
    <div id="<?php $comments->theId(); ?>" class="gt-comment-item <?php echo $masterClass; ?>" data-level="<?php echo $currentLevel; ?>">
        <div class="gt-comment-avatar">
            <?php
            $avatarUrl = '';
            if ($comments->authorId) {
                $avatarUrl = Mirai_getUserAvatar($comments->authorId);
            } else {
                $avatarUrl = Mirai_getDefaultAvatar();
            }
            echo '<img class="avatar" loading="lazy" src="' . htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($comments->author, ENT_QUOTES, 'UTF-8') . '" width="48" height="48" />';
            ?>
        </div>
        <div class="gt-comment-main">
            <div class="gt-comment-head">
                <div class="gt-comment-meta-wrapper">
                    <div class="gt-comment-meta">
                        <span class="gt-comment-nick">
                            <?php Mirai_renderCommentAuthor($comments, $config); ?>
                        </span>
                        <?php if ($isAuthor): ?>
                            <span class="gt-comment-badge">作者</span>
                        <?php endif; ?>
                    </div>
                    <div class="gt-comment-meta-info">
                        <?php
                        $locationHtml = '';
                        $location = $comments->ip_location;
                        $themeOptions = Mirai_opt();
                        if (!empty($themeOptions->ipLocationEnable) && !empty($location)) {
                            $locationHtml = '<span class="gt-comment-tag"><i class="ri-map-pin-line"></i>' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '</span>';
                        }
                        echo $locationHtml;
                        ?>
                    </div>
                </div>
            </div>
            <div class="gt-comment-content">
                <?php Mirai_renderCommentContent($comments, $config); ?>
            </div>
            <div class="gt-comment-footer">
                <time class="gt-comment-time" datetime="<?php echo Mirai_formatISODate($comments->created); ?>">
                    <?php echo Mirai_formatTime($comments->created); ?>
                </time>
                <?php if ($canReply): ?>
                    <div class="gt-comment-actions">
                        <?php $comments->reply(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($comments->children && $config['threaded']): ?>
        <div class="gt-comment-replies">
            <?php $comments->threadedComments(); ?>
        </div>
    <?php endif; ?>
    <?php } catch (Throwable $e) { ?>
        <div class="gt-comment-error">评论渲染错误: <?php echo htmlspecialchars($e->getMessage()); ?></div>
    <?php }
}

function Mirai_renderCommentAuthor($comments, $config) {
    $author = htmlspecialchars($comments->author, ENT_QUOTES, 'UTF-8');
    
    if ($config['showUrl'] && $comments->url) {
        $url = $comments->url;
        if (preg_match('/^https?:\/\//i', $url)) {
            $rel = 'noopener';
            if ($config['urlNofollow']) {
                $rel .= ' nofollow';
            }
            echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="' . $rel . '">' . $author . '</a>';
            return;
        }
    }
    
    echo $author;
}

function Mirai_renderCommentContent($comments, $config) {
    $content = $comments->content;

    if ($comments->parent) {
        static $parentCache = [];
        $parentId = (int)$comments->parent;
        
        if (!isset($parentCache[$parentId])) {
            $db = Typecho_Db::get();
            $parent = $db->fetchRow($db->select('author')->from('table.comments')
                    ->where('coid = ?', $comments->parent));
            $parentCache[$parentId] = $parent;
        } else {
            $parent = $parentCache[$parentId];
        }
        
        if ($parent) {
            $atLink = '<a class="gt-comment-at" href="#comment-' . $comments->parent . '">@' . htmlspecialchars($parent['author'], ENT_QUOTES, 'UTF-8') . '</a> ';
            // 将 @ 链接插入到第一个 <p> 标签内部
            if (preg_match('/^<p>/i', $content)) {
                $content = preg_replace('/^<p>/i', '<p>' . $atLink, $content, 1);
            } else {
                $content = $atLink . $content;
            }
        }
    }

    if (function_exists('Mirai_addNofollowToExternalLinks')) {
        $options = Mirai_opt();
        if (Mirai_featureEnabled('seo') && (string)$options->nofollowExternalLinks !== '0') {
            $content = Mirai_addNofollowToExternalLinks($content);
        }
    }
    echo $content;
}

function Mirai_isCommentClosed($created) {
    return false;
}

function Mirai_getCommentStatusTip($created, $allowComment, $globalEnable = true, $guestAllowed = true, $isLogin = false) {
    if (!$globalEnable) {
        return '全站评论已关闭';
    }
    
    if (!$allowComment) {
        return '此页面未开启评论';
    }

    if (!$guestAllowed && !$isLogin) {
        return '请登录后参与评论';
    }
    
    return null;
}
