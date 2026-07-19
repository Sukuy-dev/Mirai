<?php

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (!Mirai_isMobile()) {
    return;
}

$options = $this->options;
$frontendLoginEnabled = Mirai_isUserCenterAuthEnabled($options);

$initialTheme = isset($this->options->themeMode) && $this->options->themeMode !== 'auto' ? $this->options->themeMode : 'light';
?>
<div class="gt-mobile-overlay" id="mobileOverlay" onclick="closeAllMobile()" role="presentation"></div>
<div class="gt-mobile-sidebar" id="mobileSidebar" role="navigation" aria-label="移动端菜单">
    <div class="gt-mobile-sidebar-header">
        <?php if($this->user->hasLogin()): ?>
            <?php $userCenterUrl = Mirai_getPageUrlBySlug('user'); ?>
            <a href="<?php echo htmlspecialchars($userCenterUrl ?: $this->options->profileUrl()); ?>" class="header-item">
                <div class="item-icon"><i class="ri-user-settings-line"></i></div>
                <div class="item-text">个人中心</div>
            </a>
            <a href="<?php $this->options->logoutUrl(); ?>" class="header-item">
                <div class="item-icon"><i class="ri-logout-box-line"></i></div>
                <div class="item-text">退出登录</div>
            </a>
        <?php elseif ($frontendLoginEnabled): ?>
            <?php
            $sidebarAllowRegister = $options->allowRegister;
            ?>
            <a href="javascript:;" onclick="window.openLoginModal('login')" class="header-item">
                <div class="item-icon"><i class="ri-user-line"></i></div>
                <div class="item-text">登录</div>
            </a>
            <?php if ($sidebarAllowRegister): ?>
            <a href="javascript:;" onclick="window.openLoginModal('register')" class="header-item">
                <div class="item-icon"><i class="ri-user-add-line"></i></div>
                <div class="item-text">注册</div>
            </a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="javascript:;" onclick="toggleTheme()" class="header-item sidebar-theme-btn" id="mobileThemeToggle">
             <div class="item-icon" id="mobileThemeIcon">
                <i class="ri-sun-line theme-icon-sun" style="display: <?php echo $initialTheme === 'light' ? 'inline-block' : 'none'; ?>"></i>
                <i class="ri-moon-line theme-icon-moon" style="display: <?php echo $initialTheme === 'dark' ? 'inline-block' : 'none'; ?>"></i>
             </div>
             <div class="item-text sidebar-theme-text" id="mobileThemeText"><?php echo $initialTheme === 'light' ? '夜间模式' : '日间模式'; ?></div>
        </a>
    </div>
    <div class="gt-mobile-sidebar-body">
        <nav class="gt-mobile-nav-list" aria-label="移动端导航">
            <?php echo Mirai_getNavigationMenu('mobile'); ?>
        </nav>
        <?php if ($options->mobileSidebarShowWidgets === '1'): ?>
            <?php renderMobileSidebarWidgets($options); ?>
        <?php endif; ?>
    </div>
</div>