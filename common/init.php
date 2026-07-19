<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (function_exists('Mirai_licenseEnsureCoreLoaded')) {
    Mirai_licenseEnsureCoreLoaded();
} else {
    header('HTTP/1.1 503 Service Unavailable');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>安全校验失败</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h1 style="color:red;">警告：非法篡改 !</h1><p>安全校验失败：检测到主题核心文件已被修改或剥离，请复原文件或重新安装主题。</p><p>如有疑问请联系：<br>QQ：1461139506<br>微信：Sakura1086 &nbsp;&nbsp; 邮箱：support@sukuy.com</p></body></html>';
    exit;
}
function themeInit($archive) {
    static $miraiHooksReady = false;
    if (!$miraiHooksReady) {
        Mirai_themeInit($archive);
        $miraiHooksReady = true;
    }
    $pathInfo = $archive->request->getPathInfo();

    if ($pathInfo === '/auth' || $pathInfo === '/auth/status') {
        if (function_exists('Mirai_handleAuthStatusRoute')) {
            Mirai_handleAuthStatusRoute($archive, $pathInfo);
        }
        Mirai_abortNotFound($archive);
    }


    if (preg_match('#^/user(?:/|$)(.*)#', $pathInfo, $matches)) {

        if (preg_match('#^/user/auth(?:/|$)(.*)#', $pathInfo)) {
            Mirai_abortNotFound($archive);
        }
        
        $options = Mirai_opt();
        $userCenterEnabled = Mirai_featureEnabled('user_center');
        
        if (!$userCenterEnabled) {
            Mirai_abortNotFound($archive);
        }
        
        if (method_exists($archive->response, 'setStatus')) {
            $archive->response->setStatus(200);
        }

        $archive->setArchiveTitle('用户中心');
        $archive->setParameter('type', 'page');
        $tab = !empty($matches[1]) ? $matches[1] : 'overview';
        $tab = rtrim($tab, '/');
        $archive->request->setParam('tab', $tab);
        
        $render = function() {
            require __DIR__ . '/../user/index.php';
        };
        $render->call($archive);
        exit;
    }

    $isRssRequest = in_array($pathInfo, ['/rss', '/feed', '/atom', '/feed/atom']);
    
    if ($isRssRequest) {
        $options = Mirai_opt();
        $rssEnabled = Mirai_featureEnabled('seo') && (!isset($options->rssEnable) || $options->rssEnable === '1');
        
        if (!$rssEnabled) {
            throw new \Exception('RSS 订阅已禁用', 404);
        }
        
        if (method_exists($archive->response, 'setStatus')) {
            $archive->response->setStatus(200);
        }
        require_once __DIR__ . '/../modules/rss.php';
        $rss = new Mirai_Rss();
        
        if ($pathInfo === '/rss' || $pathInfo === '/feed') {
            $rss->renderRss2();
        } else {
            $rss->renderAtom();
        }
        exit;
    }

    // API 请求处理
    if (isset($_GET['mirai_api'])) {
        Mirai_handleApi($_GET['mirai_api']);
        exit;
    }
}

function Mirai_abortNotFound($archive) {
    if ($archive && method_exists($archive->response, 'setStatus')) {
        $archive->response->setStatus(404);
    }
    header('HTTP/1.1 404 Not Found');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>页面不存在</p></body></html>';
    exit;
}

if (function_exists('Mirai_coreAuthEnforce')) {
    Mirai_coreAuthEnforce();
}

function threadedComments($comments, $options) {
    if (!function_exists('Mirai_renderComment')) {
        require_once __DIR__ . '/functions/comment.php';
    }

    Mirai_renderComment($comments, $options);
}

if (!defined('MIRAI_CORE_READY')) {
    define('MIRAI_CORE_READY', \Typecho\Plugin::exists('MiraiCore'));
}

if (!function_exists('Mirai_sanitize')) {
    function Mirai_sanitize($value, $allowHtml = false) {
        if (empty($value)) {
            return '';
        }

        if (is_array($value)) {
            return array_map(function($item) use ($allowHtml) {
                return Mirai_sanitize($item, $allowHtml);
            }, $value);
        }

        $value = (string)$value;

        if ($allowHtml) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return htmlspecialchars(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}


function Mirai_themeInit($archive) {
    // 内容处理钩子 - 图片转 picture 标签和摘要处理
    $contentsFactory = \Typecho\Plugin::factory('Widget_Abstract_Contents');
    $prevContentEx = isset($contentsFactory->contentEx) ? $contentsFactory->contentEx : null;
    $prevExcerptEx = isset($contentsFactory->excerptEx) ? $contentsFactory->excerptEx : null;

    $contentsFactory->contentEx = function($content, $widget) use ($prevContentEx) {
        if ($prevContentEx) {
            $content = call_user_func($prevContentEx, $content, $widget);
        }
        return Mirai_convertImagesToPicture($content, $widget->title ?? '');
    };

    $contentsFactory->excerptEx = function($excerpt, $widget) use ($prevExcerptEx) {
        if ($prevExcerptEx) {
            $excerpt = call_user_func($prevExcerptEx, $excerpt, $widget);
        }
        return Mirai_stripMarkdown($excerpt);
    };

    // 评论处理钩子 - IP归属地、评论通知等
    $feedbackFactory = \Typecho\Plugin::factory('Widget_Feedback');
    if (!function_exists('Mirai_getClientIp')) {
        $locationFile = __DIR__ . '/functions/location.php';
        if (file_exists($locationFile)) {
            require_once $locationFile;
        }
    }

    // 评论插入前钩子：使用 Mirai_getClientIp() 获取真实 IP
    if (function_exists('Mirai_filterCommentBeforeInsert')) {
        $prevComment = isset($feedbackFactory->comment) ? $feedbackFactory->comment : null;
        $feedbackFactory->comment = function($comment, $obj) use ($prevComment) {
            if ($prevComment) {
                $comment = call_user_func($prevComment, $comment, $obj);
            }
            $comment = Mirai_filterCommentBeforeInsert($comment, $obj);
            return $comment;
        };
    }
    
    // 评论完成后钩子：处理归属地、通知等
    $commentCallbacks = [];
    if (function_exists('Mirai_onFinishComment')) {
        $commentCallbacks[] = 'Mirai_onFinishComment';
    }
    if (function_exists('Mirai_commentNotification')) {
        $commentCallbacks[] = 'Mirai_commentNotification';
    }
    
    $prevFinishComment = isset($feedbackFactory->finishComment) ? $feedbackFactory->finishComment : null;
    if (!empty($commentCallbacks) || $prevFinishComment) {
        $feedbackFactory->finishComment = function($comment) use ($commentCallbacks, $prevFinishComment) {
            $result = $comment;
            if ($prevFinishComment) {
                $result = call_user_func($prevFinishComment, $result);
            }
            foreach ($commentCallbacks as $callback) {
                $result = call_user_func($callback, $result);
            }
            return $result;
        };
    }
}