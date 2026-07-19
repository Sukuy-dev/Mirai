<?php
/**
 * Mirai Theme - Email Module
 * 邮件发送模块
 * 
 * 包含：SMTP邮件发送、邮件模板、评论通知、投稿通知、验证码发送等功能
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Mirai_EmailTpl
{
    private static function build($title, $content)
    {
        $opts = \Typecho\Widget::widget('Widget_Options');
        $siteName = htmlspecialchars(Mirai_getSiteTitle());
        $siteUrl = $opts->siteUrl;
        $year = (new \Typecho\Date())->format('Y');
        
        return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title) . '</title>
<style>body{margin:0;padding:0;background:#fff;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"PingFang SC","Microsoft YaHei",sans-serif;line-height:1.6;color:#333}.container{max-width:600px;margin:0 auto;padding:40px 20px}.header{text-align:center;margin-bottom:30px}.header h1{margin:0;font-size:24px;font-weight:600}.header a{color:#333;text-decoration:none}.divider{height:2px;background:#333;margin:20px 0}.content{font-size:15px;color:#555}.content p{margin:0 0 16px}.content a{color:#2563eb;text-decoration:none}.code-box{background:#f8f9fa;padding:24px;text-align:center;margin:20px 0}.code{font-size:32px;font-weight:700;color:#2563eb;letter-spacing:8px;font-family:monospace}.hint{color:#888;font-size:14px;margin-top:16px}.footer{margin-top:40px;padding-top:20px;border-top:1px solid #eee;text-align:center;font-size:13px;color:#999}.footer a{color:#666;text-decoration:none}</style>
</head>
<body>
<div class="container">
<div class="header">
<h1><a href="' . $siteUrl . '">' . $siteName . '</a></h1>
</div>
<div class="divider"></div>
<div class="content">
<h2 style="margin:0 0 20px;font-size:18px;font-weight:600;color:#333">' . htmlspecialchars($title) . '</h2>
' . $content . '
</div>
<div class="footer">
<p>此邮件由系统自动发送，请勿回复</p>
<p><a href="' . $siteUrl . '">访问网站</a></p>
<p>&copy; ' . $year . ' ' . $siteName . '</p>
</div>
</div>
</body>
</html>';
    }

    public static function test()
    {
        return self::build(
            '测试邮件',
            '<p>感谢使用 <strong>Mirai 未来主题</strong></p>' .
            '<p>当您收到这封邮件时，说明 SMTP 邮件服务配置正确，邮件发送功能已正常运行。</p>' .
            '<p style="color:#888;font-size:14px;">发送时间：' . (new \Typecho\Date())->format('Y-m-d H:i:s') . '</p>'
        );
    }

    public static function verifyCode($code, $type = 'register')
    {
        $typeText = $type === 'register' ? '注册账号' : '重置密码';
        return self::build(
            $typeText . '验证码',
            '<p>您正在进行 <strong>' . $typeText . '</strong> 操作，验证码如下：</p>' .
            '<div class="code-box">' .
            '<div style="color:#888;font-size:13px;margin-bottom:8px;">验证码</div>' .
            '<div class="code">' . $code . '</div>' .
            '</div>' .
            '<p class="hint">验证码有效期为 10 分钟，请勿告知他人。</p>' .
            '<p class="hint">如非本人操作，请忽略此邮件。</p>'
        );
    }

    public static function commentNotifyAdmin($comment)
    {
        $commentTime = isset($comment->created) ? (new \Typecho\Date($comment->created))->format('Y-m-d H:i') : '';
        
        return self::build(
            '文章收到新评论',
            '<p>您的文章收到了一条新评论：</p>' .
            '<p style="background:#f8f9fa;padding:12px 16px;margin:16px 0;">' .
            '<strong>评论文章：</strong><a href="' . $comment->permalink . '">' . htmlspecialchars($comment->title) . '</a>' .
            '</p>' .
            '<div style="background:#f8f9fa;border-left:3px solid #2563eb;padding:16px;margin:16px 0;">' .
            '<div style="font-size:13px;color:#666;margin-bottom:8px;">' .
            '<strong>' . htmlspecialchars($comment->author) . '</strong> <span style="color:#999;">' . htmlspecialchars($comment->mail) . '</span>' .
            '</div>' .
            '<div style="font-size:14px;color:#555;line-height:1.7;">' . $comment->text . '</div>' .
            '</div>' .
            '<p style="color:#888;font-size:13px;">时间：' . $commentTime . ' &nbsp;|&nbsp; IP：' . htmlspecialchars($comment->ip ?? '未知') . '</p>' .
            '<p style="text-align:center;margin-top:24px;"><a href="' . $comment->permalink . '" style="display:inline-block;padding:10px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:4px;font-size:14px;">查看评论</a></p>'
        );
    }

    public static function commentReply($comment, $parentComment)
    {
        $commentTime = isset($comment->created) ? (new \Typecho\Date($comment->created))->format('Y-m-d H:i') : '';
        
        return self::build(
            '您的评论有了新回复',
            '<p>Hi, <strong>' . htmlspecialchars($parentComment['author']) . '</strong></p>' .
            '<p>您在文章《<a href="' . $comment->permalink . '">' . htmlspecialchars($comment->title) . '</a>》中的评论收到了新回复：</p>' .
            '<div style="background:#f8f9fa;border-left:3px solid #999;padding:16px;margin:16px 0;">' .
            '<div style="font-size:13px;color:#888;margin-bottom:8px;">您的原评论</div>' .
            '<div style="font-size:14px;color:#555;line-height:1.7;">' . $parentComment['text'] . '</div>' .
            '</div>' .
            '<div style="background:#f8f9fa;border-left:3px solid #2563eb;padding:16px;margin:16px 0;">' .
            '<div style="font-size:13px;color:#666;margin-bottom:8px;">' .
            '<strong>' . htmlspecialchars($comment->author) . '</strong> 回复 <span style="color:#999;">' . $commentTime . '</span>' .
            '</div>' .
            '<div style="font-size:14px;color:#555;line-height:1.7;">' . $comment->text . '</div>' .
            '</div>' .
            '<p style="text-align:center;margin-top:24px;"><a href="' . $comment->permalink . '" style="display:inline-block;padding:10px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:4px;font-size:14px;">查看详情</a></p>'
        );
    }

    public static function submission($title, $authorName, $status, $adminUrl)
    {
        $statusMap = [
            'publish' => ['text' => '已发布', 'color' => '#059669'],
            'waiting' => ['text' => '待审核', 'color' => '#d97706'],
            'draft' => ['text' => '草稿', 'color' => '#6b7280'],
            'private' => ['text' => '私密', 'color' => '#7c3aed'],
        ];
        $statusInfo = $statusMap[$status] ?? ['text' => $status, 'color' => '#6b7280'];
        
        return self::build(
            '新投稿通知',
            '<p>站点收到了新的投稿请求：</p>' .
            '<div style="background:#f8f9fa;padding:16px;margin:16px 0;">' .
            '<div style="font-size:12px;color:#888;margin-bottom:4px;">文章标题</div>' .
            '<div style="font-size:16px;color:#333;font-weight:500;">' . htmlspecialchars($title) . '</div>' .
            '</div>' .
            '<p><strong>作者：</strong>' . htmlspecialchars($authorName) . '</p>' .
            '<p><strong>状态：</strong><span style="color:' . $statusInfo['color'] . ';">' . $statusInfo['text'] . '</span></p>' .
            '<p><strong>时间：</strong>' . (new \Typecho\Date())->format('Y-m-d H:i') . '</p>' .
            '<p style="text-align:center;margin-top:24px;"><a href="' . $adminUrl . '" style="display:inline-block;padding:10px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:4px;font-size:14px;">前往审核</a></p>'
        );
    }

    public static function vipExpireNotify($vipName, $daysRemaining, $expDate, $renewUrl)
    {
        return self::build(
            '会员即将到期提醒',
            '<p>尊敬的会员，您好！</p>' .
            '<p>您的 <strong>' . htmlspecialchars($vipName) . '</strong> 将于 <strong>' . $daysRemaining . '</strong> 天后到期。</p>' .
            '<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:16px;margin:16px 0;">' .
            '<div style="font-size:14px;color:#856404;">' .
            '<strong>到期时间：</strong>' . htmlspecialchars($expDate) .
            '</div>' .
            '</div>' .
            '<p>为了确保您能继续享受会员特权，请及时续费。</p>' .
            '<p style="text-align:center;margin-top:24px;"><a href="' . $renewUrl . '" style="display:inline-block;padding:10px 24px;background:#ff6600;color:#fff;text-decoration:none;border-radius:4px;font-size:14px;">立即续费</a></p>' .
            '<p style="color:#888;font-size:13px;margin-top:20px;">此邮件为系统自动发送，请勿回复。</p>'
        );
    }
}

class Mirai_Mail
{
    private static $instance = null;
    private $mail = null;
    private $options = null;
    private $lastError = '';
    private $logEnabled = false;

    private function __construct()
    {
        $this->options = Mirai_opt();
        $this->logEnabled = (defined('__MIRAI_MAIL_LOG__') && __MIRAI_MAIL_LOG__);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPHPMailer()
    {
        if ($this->mail !== null) {
            return $this->mail;
        }

        $host = $this->options->smtpHost;
        $port = $this->options->smtpPort;
        $user = $this->options->smtpUser;
        $mailConfig = function_exists('Mirai_getMailConfig') ? Mirai_getMailConfig() : ['available' => false];
        if (empty($mailConfig['available']) || empty($mailConfig['enabled'])) {
            $this->lastError = '邮件功能需要有效的许可验证';
            return false;
        }
        $pass = $this->options->smtpPass;
        
        $secure = $this->options->smtpSecure;

        if (empty($host) || empty($user) || empty($pass)) {
            $this->lastError = 'SMTP配置不完整，请在主题设置中配置SMTP密码';
            return false;
        }

        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $libPath = __DIR__ . '/../lib/PHPMailer/';
            require_once $libPath . 'Exception.php';
            require_once $libPath . 'PHPMailer.php';
            require_once $libPath . 'SMTP.php';
        }

        try {
            $this->mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $this->mail->isSMTP();
            $this->mail->Host = $host;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $user;
            $this->mail->Password = $pass;
            $this->mail->SMTPSecure = ($secure == 'none') ? '' : $secure;
            $this->mail->Port = $port;
            $this->mail->CharSet = 'UTF-8';
            $this->mail->setFrom($user, Mirai_getSiteTitle());

            $this->log('PHPMailer实例创建成功');
            return $this->mail;
        } catch (Exception $e) {
            $this->lastError = 'PHPMailer初始化失败: ' . $e->getMessage();
            $this->log($this->lastError);
            return false;
        }
    }

    public function send($to, $subject, $content)
    {
        $mail = $this->getPHPMailer();
        if (!$mail) {
            return false;
        }

        try {
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $content;

            $result = $mail->send();

            if ($result) {
                $this->log("邮件发送成功: To={$to}, Subject={$subject}");
            } else {
                $this->lastError = $mail->ErrorInfo;
                $this->log("邮件发送失败: To={$to}, Error={$this->lastError}");
            }

            $mail->clearAddresses();
            return $result;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $this->log("邮件发送异常: To={$to}, Error={$this->lastError}");
            return false;
        }
    }

    public function sendTest($to)
    {
        $subject = '[' . Mirai_getSiteTitle() . '] SMTP发信测试';
        $content = Mirai_EmailTpl::test();
        return $this->send($to, $subject, $content);
    }

    public function sendVerifyCode($to, $code, $type = 'register')
    {
        $typeText = $type === 'register' ? '注册验证码' : '重置密码验证码';
        $subject = '[' . Mirai_getSiteTitle() . '] ' . $typeText;
        $content = Mirai_EmailTpl::verifyCode($code, $type);
        return $this->send($to, $subject, $content);
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function reset()
    {
        $this->mail = null;
        $this->lastError = '';
    }

    private function log($message)
    {
        if (!$this->logEnabled) {
            return;
        }

        $logFile = dirname(__DIR__, 2) . '/logs/mail.log';
        if (!is_dir(dirname($logFile))) {
            @mkdir(dirname($logFile), 0755, true);
        }

        $time = (new \Typecho\Date())->format('Y-m-d H:i:s');
        $logMessage = "[{$time}] {$message}\n";
    }
}

function Mirai_mail()
{
    return Mirai_Mail::getInstance();
}

function Mirai_getSiteTitle()
{
    $options = Mirai_opt();
    // 优先使用自定义发件人名称
    if (!empty($options->smtpFromName)) {
        return $options->smtpFromName;
    }
    // 回退到主题设置的站点标题
    if (!empty($options->siteTitle)) {
        return $options->siteTitle;
    }
    return '';
}

function Mirai_sendMail($to, $subject, $content)
{
    return Mirai_mail()->send($to, $subject, $content);
}

function Mirai_commentNotification($comment)
{
    $options = Mirai_opt();
    $db = \Typecho\Db::get();

    // 1. 新评论通知博主
    if (!empty($options->commentNotifyBlogger) && $options->commentNotifyBlogger == '1') {
        // 默认通知 SMTP 邮箱（博主）
        $adminEmail = $options->smtpUser;

        // 尝试获取文章作者邮箱作为通知对象
        try {
            $post = $db->fetchRow($db->select('authorId')->from('table.contents')->where('cid = ?', $comment->cid));
            if ($post) {
                $author = $db->fetchRow($db->select('mail')->from('table.users')->where('uid = ?', $post['authorId']));
                if ($author && !empty($author['mail'])) {
                    $adminEmail = $author['mail'];
                }
            }
        } catch (Exception $e) {
        }

        // 如果评论者不是博主自己，则发送通知
        if ($comment->mail != $adminEmail) {
            $subject = '[' . Mirai_getSiteTitle() . '] 您的文章有了新评论';
            $html = Mirai_EmailTpl::commentNotifyAdmin($comment);

            Mirai_sendMail($adminEmail, $subject, $html);
        }
    }

    // 检查是否开启了评论回复通知
    if (empty($options->commentReplyNotify) || $options->commentReplyNotify == '0') {
        return;
    }

    // 必须是回复评论（有父级评论ID）
    if ($comment->parent > 0) {
        // 获取父级评论信息
        $parentComment = $db->fetchRow($db->select()->from('table.comments')->where('coid = ?', $comment->parent));

        // 如果父级评论存在，且有邮箱
        if ($parentComment && !empty($parentComment['mail'])) {
            // 如果是自己回复自己，不通知
            if ($parentComment['mail'] == $comment->mail) {
                return;
            }

            // 准备邮件内容
            $subject = '[' . Mirai_getSiteTitle() . '] 您的评论有了新回复';
            $html = Mirai_EmailTpl::commentReply($comment, $parentComment);

            // 发送邮件
            Mirai_sendMail($parentComment['mail'], $subject, $html);
        }
    }
}

function Mirai_submissionNotification($contents, $edit)
{
    $options = Mirai_opt();

    // 检查开关
    if (empty($options->submissionNotify) || $options->submissionNotify == '0') {
        return;
    }

    // 如果是管理员发布，则忽略
    $user = Mirai_user();
    if ($user->pass('administrator', true)) {
        return;
    }

    $adminEmail = $options->smtpUser;
    if (empty($adminEmail)) return;

    $title = $contents['title'] ?? '无标题';
    $status = $contents['status'] ?? 'unknown';

    $subject = '[' . Mirai_getSiteTitle() . '] 有新的投稿：' . $title;
    $html = Mirai_EmailTpl::submission($title, $user->screenName, $status, $options->adminUrl('manage-posts.php', true));

    Mirai_sendMail($adminEmail, $subject, $html);
}

function Mirai_sendVerifyCodeMail($mail, $code, $type = 'register')
{
    return Mirai_mail()->sendVerifyCode($mail, $code, $type);
}

function Mirai_api_send_code_with_check()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $options = Mirai_opt();
    $db = \Typecho\Db::get();

    // 检查用户中心和注册登录功能是否启用
    if (!Mirai_isUserCenterAuthEnabled($options)) {
        return ['success' => false, 'msg' => '用户中心功能已禁用', 'code' => -1];
    }

    $mail = isset($_POST['mail']) ? trim($_POST['mail']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : 'reset';

    if (empty($mail)) {
        return ['success' => false, 'msg' => '请输入邮箱地址', 'code' => -1];
    }
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'msg' => '邮箱格式不正确', 'code' => -1];
    }

    // 检查邮箱是否存在
    $checkMail = $db->fetchRow($db->select()->from('table.users')->where('mail = ?', $mail));

    if ($type === 'register') {
        if ($checkMail) {
            return ['success' => false, 'msg' => '该邮箱已被注册', 'code' => -1];
        }
    } else {
        if (!$checkMail) {
            return ['success' => false, 'msg' => '该邮箱未注册', 'code' => -1];
        }
    }

    // IP 频率限制
    $interval = intval($options->verifyCodeInterval ?? 60);
    if ($interval < 10) $interval = 10;
    if ($interval > 3600) $interval = 3600;

    $ip = Mirai_getClientIp();
    $cacheDir = dirname(__DIR__, 2) . '/cache/ip_rate_limit/';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $ipFile = $cacheDir . md5($ip);

    if (file_exists($ipFile)) {
        $lastSendTime = (int)@file_get_contents($ipFile);
        $remaining = $interval - (time() - $lastSendTime);
        if ($remaining > 0) {
            return ['success' => false, 'msg' => "操作过于频繁，请在 {$remaining} 秒后重试", 'code' => 429, 'remaining' => $remaining];
        }
    }

    // 生成验证码 (继续使用 Session 存储验证码本身)
    $code = rand(100000, 999999);
    $_SESSION['mirai_auth_code'] = $code;
    $_SESSION['mirai_auth_mail'] = $mail;
    $_SESSION['mirai_auth_time'] = time();

    if (Mirai_sendVerifyCodeMail($mail, $code, $type)) {
        // 记录本次IP发送时间
        @file_put_contents($ipFile, time(), LOCK_EX);
        return ['success' => true, 'msg' => '验证码已发送', 'code' => 0, 'interval' => $interval];
    } else {
        // 发送失败，清除session
        Mirai_clearAuthCodeSession();
        return ['success' => false, 'msg' => '发送失败，请检查邮件配置', 'code' => -1];
    }
}

function Mirai_api_send_test_mail_with_check()
{
    // 权限检查：必须是管理员
    $user = Mirai_user();
    if (!$user->pass('administrator', true)) {
        return ['success' => false, 'msg' => '权限不足', 'code' => -1];
    }

    $to = isset($_POST['email']) ? trim($_POST['email']) : (isset($_GET['test_mail']) ? trim($_GET['test_mail']) : '');
    
    // 如果为空，回退到管理员邮箱
    if (empty($to)) {
        $to = $user->mail; 
        if (empty($to)) {
            $to = Mirai_opt()->smtpUser;
        }
    }
    
    if (empty($to)) {
        return ['success' => false, 'msg' => '请先配置收件人邮箱', 'code' => -1];
    }
    
    // 使用统一发信模块发送测试邮件
    $mailer = Mirai_mail();
    $result = $mailer->sendTest($to);
    
    if ($result) {
        return ['success' => true, 'msg' => '测试邮件已成功发送至 ' . $to, 'code' => 0];
    } else {
        $error = $mailer->getLastError();
        if (empty($error)) {
            $error = '未知错误';
        }
        return ['success' => false, 'msg' => '发送失败: ' . $error, 'code' => -1];
    }
}

function Mirai_sendVipExpireNotifyEmail($uid, $vipName, $daysRemaining, $expDate)
{
    $options = Mirai_opt();
    
    if (empty($options->vipExpireEmailNotify) || $options->vipExpireEmailNotify !== '1') {
        return false;
    }
    
    $db = \Typecho\Db::get();
    $user = $db->fetchRow($db->select('mail', 'name')->from('table.users')->where('uid = ?', (int)$uid));
    
    if (!$user || empty($user['mail'])) {
        return false;
    }
    
    $siteUrl = rtrim($options->siteUrl, '/');
    $renewUrl = $siteUrl . '/user/vip';
    
    $subject = '[' . Mirai_getSiteTitle() . '] 会员即将到期提醒';
    $html = Mirai_EmailTpl::vipExpireNotify($vipName, $daysRemaining, $expDate, $renewUrl);
    
    return Mirai_sendMail($user['mail'], $subject, $html);
}

function Mirai_checkAndSendVipExpireNotify($uid)
{
    $options = Mirai_opt();
    
    if (empty($options->vipExpireEmailNotify) || $options->vipExpireEmailNotify !== '1') {
        return false;
    }
    
    if (!function_exists('Mirai_vipShouldNotifyExpire') || !function_exists('Mirai_vipGetUserInfo')) {
        return false;
    }
    
    $cacheKey = 'mirai_vip_expire_notify_' . $uid;
    $cacheDir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/cache/vip_notify/';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . md5($cacheKey) . '.txt';
    
    $today = date('Y-m-d');
    if (file_exists($cacheFile)) {
        $lastNotify = trim(file_get_contents($cacheFile));
        if ($lastNotify === $today) {
            return false;
        }
    }
    
    if (!Mirai_vipShouldNotifyExpire($uid)) {
        return false;
    }
    
    $vipInfo = Mirai_vipGetUserInfo($uid);
    if ($vipInfo['level'] <= 0 || $vipInfo['is_permanent'] || $vipInfo['is_expired']) {
        return false;
    }
    
    $vipName = function_exists('Mirai_vipGetName') ? Mirai_vipGetName($vipInfo['level']) : '会员';
    $expDate = date('Y年m月d日', strtotime($vipInfo['exp_date']));
    
    $result = Mirai_sendVipExpireNotifyEmail($uid, $vipName, $vipInfo['days_remaining'], $expDate);
    
    if ($result) {
        @file_put_contents($cacheFile, $today, LOCK_EX);
    }
    
    return $result;
}