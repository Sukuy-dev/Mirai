<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$security = \Widget\Security::alloc();
$token = $security->getToken('api');
$domainText = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '-';
$options = \Typecho\Widget::widget('Widget_Options');
$adminThemeUrl = rtrim($options->siteUrl, '/') . '/usr/themes/' . $options->theme;
?>
<script>
var MIRAI_ADMIN_CONFIG = {
    token: '<?php echo $token; ?>',
    apiUrl: '<?php echo $options->index; ?>'
};
</script>
<link rel="stylesheet" href="<?php echo $adminThemeUrl; ?>/assets/RemixIcon/4.9.1/remixicon.css">
<link rel="stylesheet" href="<?php echo $adminThemeUrl; ?>/assets/css/admin.css?v=<?php echo time(); ?>">
<script src="<?php echo $adminThemeUrl; ?>/assets/js/admin.js?v=<?php echo time(); ?>" defer></script>
<div class="mirai-config-container">
    <div class="mirai-config-aside">
        <div class="mirai-config-card">
            <div class="mirai-config-logo">
                <img src="<?php echo $adminThemeUrl; ?>/assets/images/mirai.png" alt="Mirai Logo">
            </div>
            <ul class="mirai-config-tabs">
                <li class="item" data-tab="about"><i class="ri-information-line"></i>关于主题</li>
                <li class="item" data-tab="license"><i class="ri-shield-keyhole-line"></i>许可激活</li>
                <li class="item-group">
                <div class="group-title"><i class="ri-settings-3-line"></i><span>全局设置</span></div>
                <ul class="submenu">
                    <li class="item" data-tab="basic"><i class="ri-global-line"></i>网站设置</li>
                    <li class="item" data-tab="nav"><i class="ri-navigation-line"></i>导航设置</li>
                    <li class="item" data-tab="footer"><i class="ri-layout-bottom-line"></i>底部设置</li>
                    <li class="item" data-tab="other"><i class="ri-more-line"></i>自定义设置</li>
                </ul>
            </li>
                <li class="item-group">
                    <div class="group-title"><i class="ri-seo-line"></i><span>SEO设置</span></div>
                    <ul class="submenu">
                        <li class="item" data-tab="seo"><i class="ri-search-line"></i>SEO设置</li>
                        <li class="item" data-tab="speed"><i class="ri-speed-line"></i>网站加速</li>
                    </ul>
                </li>
                <li class="item-group">
                    <div class="group-title"><i class="ri-palette-line"></i><span>外观设置</span></div>
                    <ul class="submenu">
                        <li class="item" data-tab="home"><i class="ri-home-4-line"></i>首页设置</li>
                        <li class="item" data-tab="theme"><i class="ri-paint-brush-line"></i>主题样式</li>
                        <li class="item" data-tab="aside"><i class="ri-side-bar-line"></i>边栏设置</li>
                    </ul>
                </li>
                <li class="item-group">
                    <div class="group-title"><i class="ri-article-line"></i><span>文章设置</span></div>
                    <ul class="submenu">
                        <li class="item" data-tab="article"><i class="ri-file-text-line"></i>基本设置</li>
                        <li class="item" data-tab="article_extend"><i class="ri-more-line"></i>扩展设置</li>
                    </ul>
                </li>
                <li class="item-group">
                    <div class="group-title"><i class="ri-user-settings-line"></i><span>用户设置</span></div>
                    <ul class="submenu">
                        <li class="item" data-tab="auth"><i class="ri-shield-user-line"></i>注册登录</li>
                    </ul>
                </li>
                <li class="item-group">
                    <div class="group-title"><i class="ri-tools-line"></i><span>高级功能</span></div>
                    <ul class="submenu">
                        <li class="item" data-tab="pay_read"><i class="ri-lock-star-line"></i>付费阅读</li>
                        <li class="item" data-tab="pay_vip"><i class="ri-vip-crown-line"></i>会员等级</li>
                        <li class="item" data-tab="pay_discount"><i class="ri-percent-line"></i>会员折扣</li>
                        <li class="item" data-tab="pay_gateway"><i class="ri-wallet-3-line"></i>收款接口</li>
                        <li class="item" data-tab="pay_config"><i class="ri-settings-3-line"></i>支付配置</li>
                        <li class="item" data-tab="pay_recharge"><i class="ri-coins-line"></i>充值提现</li>
                    </ul>
                </li>
                <li class="item-group">
                    <div class="group-title"><i class="ri-function-line"></i><span>功能扩展</span></div>
                    <ul class="submenu">
                        <li class="item" data-tab="comment"><i class="ri-message-3-line"></i>评论设置</li>
                        <li class="item" data-tab="ip"><i class="ri-map-pin-line"></i>IP 接口</li>
                        <li class="item" data-tab="smtp"><i class="ri-mail-send-line"></i>邮箱设置</li>
                        <li class="item" data-tab="editor"><i class="ri-edit-box-line"></i>编辑器设置</li>
                        <li class="item" data-tab="notification"><i class="ri-notification-3-line"></i>通知设置</li>
                        <li class="item" data-tab="friendship"><i class="ri-links-line"></i>友情链接</li>
                        <li class="item" data-tab="search"><i class="ri-search-line"></i>搜索设置</li>
                    </ul>
                </li>
                <li class="item" data-tab="backup"><i class="ri-save-3-line"></i>备份恢复</li>
            </ul>
        </div>
    </div>
</div>
<div class="typecho-option mirai-option mirai-tab-about">
    <?php echo MiraiCore_Plugin::buildAbout(); ?>
</div>

<div class="typecho-option mirai-option mirai-tab-license">
    <?php require_once 'license-tab.php'; ?>
</div>

</div>