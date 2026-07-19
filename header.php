<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE html>
<?php
$themeMode = isset($this->options->themeMode) ? $this->options->themeMode : 'auto';
$initialTheme = 'light';
if ($themeMode !== 'auto') {
    $initialTheme = $themeMode;
}
?>
<html lang="zh-CN" data-theme="<?php echo htmlspecialchars($initialTheme); ?>" data-bs-theme="<?php echo htmlspecialchars($initialTheme); ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="x-dns-prefetch-control" content="on">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=3.0, user-scalable=yes, viewport-fit=cover">
    <meta name="applicable-device" content="pc,mobile">
    <meta name="color-scheme" content="light dark">
    <meta name="HandheldFriendly" content="true">
    <meta name="MobileOptimized" content="320">
<?php if (isset($this->is404) && $this->is404): ?>
    <meta name="robots" content="noindex, nofollow">
<?php else: ?>
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<?php endif; ?>
<?php
    $seoData = Mirai_getSeoData($this);
    Mirai_renderSeoMeta($seoData, $this);
?>
<?php
$rssEnabled = Mirai_featureEnabled('seo') && (!isset($this->options->rssEnable) || $this->options->rssEnable === '1');
if ($rssEnabled):
    $rssSiteTitle = $this->options->siteTitle ?: $this->options->title;
?>
    <link rel="alternate" type="application/rss+xml" title="<?php echo htmlspecialchars($rssSiteTitle); ?> - RSS Feed" href="<?php echo rtrim($this->options->index, '/') . '/rss'; ?>">
    <link rel="alternate" type="application/atom+xml" title="<?php echo htmlspecialchars($rssSiteTitle); ?> - Atom Feed" href="<?php echo rtrim($this->options->index, '/') . '/atom'; ?>">
<?php endif; ?>
    <link rel="preload" as="style" href="<?php $this->options->themeUrl('assets/RemixIcon/4.9.1/remixicon.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/RemixIcon/4.9.1/remixicon.css'); ?>">
    <link rel="preload" as="style" href="<?php $this->options->themeUrl('assets/css/mirai.css'); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/mirai.css'); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>">
<?php if ($this->is('author')): ?>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/mirai.author.css'); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>">
<?php endif; ?>
    <?php 
    $userCenterEnabled = Mirai_featureEnabled('user_center');
    $frontendLoginEnabled = Mirai_isUserCenterAuthEnabled($this->options);
    ?>
<?php if ($this->is('single') || $this->is('page') || $this->is('archive')): ?>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/lightbox.css'); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>">
<?php endif; ?>
<?php 

if ($this->is('page') && $this->template === 'links.php'): 
?>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/links.css'); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>">
<?php endif; ?>
<?php 
if ($this->is('index')) {
    $preloadCover = null;
    
    $preloadCid = Mirai_getFirstRecommendCid($this->options);
    if ($preloadCid) {
        $preloadPost = Mirai_getRecommendPost($preloadCid);
        if ($preloadPost) {
            $preloadCover = Mirai_getPostCover($preloadPost);
        }
    }
    
    if (!$preloadCover) {
        $firstPosts = Mirai_getHomePosts(1, 1);
        if (!empty($firstPosts) && isset($firstPosts[0])) {
            $preloadCover = Mirai_getPostCover($firstPosts[0]);
        }
    }
    
    if ($preloadCover) {
        echo '<link rel="preload" as="image" href="' . htmlspecialchars(Mirai_normalizeUrl($preloadCover)) . '" fetchpriority="high">' . "\n";
    }
}
?>
<?php 
    Mirai_renderSchema($seoData, $this);
?>
    <?php
    $apiToken = \Widget\Security::alloc()->getToken('api');
    $tokenCookieName = 'mirai_api_token';
    $secure = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    setcookie($tokenCookieName, $apiToken, [
        'expires' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    ?>
    <script>const MIRAI_CONFIG={HOME_URL:"<?php $this->options->siteUrl(); ?>",INDEX_URL:"<?php echo htmlspecialchars($this->options->index); ?>",TEMPLATE_URL:"<?php echo rtrim($this->options->themeUrl, '/') . '/'; ?>",VERSION:"<?php echo MIRAI_THEME_VERSION_TEXT; ?>",themeMode:"<?php echo htmlspecialchars($this->options->themeMode ?? 'auto'); ?>",baiduPushEnable:"<?php echo htmlspecialchars($this->options->baiduPushEnable ?? '0'); ?>",indexNowEnable:"<?php echo htmlspecialchars($this->options->indexNowEnable ?? '0'); ?>",API_TOKEN:"",cid:<?php echo ($this->is('post') || $this->is('page')) ? $this->cid : 'null'; ?>};</script>
    <?php if ($themeMode === 'auto'): ?><script>(function(){const a=window.matchMedia('(prefers-color-scheme: dark)').matches,b=localStorage.getItem('theme'),c=localStorage.getItem('theme_manual_toggle'),d=c?b:a?'dark':'light';d&&(document.documentElement.setAttribute('data-theme',d),document.documentElement.setAttribute('data-bs-theme',d));})();</script><?php endif; ?>
<?php Mirai_customCssVars($this); ?>
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/lazysizes/5.3.2/lazysizes.min.js'); ?>" async></script>
    <?php Mirai_customHead(); ?>
</head>
<body>
<header class="gt-header" role="banner">
    <section>
        <button class="nav-link gt-hover menu-btn d-md-none" onclick="toggleMobileSidebar()" aria-label="切换菜单">
            <i class="ri-function-line"></i>
            <i class="ri-close-line" style="display:none"></i>
        </button>
        <?php $logoAriaLabel = $this->options->siteTitle ? $this->options->siteTitle : $this->options->title; ?>
        <?php if ($this->is('index')): ?>
        <h1 class="home" aria-label="<?php echo htmlspecialchars($logoAriaLabel); ?>">
            <a href="<?php $this->options->siteUrl(); ?>" rel="home" class="gt-hover">
                <?php Mirai_renderLogo($this->options); ?>
                <span class="visually-hidden"><?php echo htmlspecialchars($logoAriaLabel); ?></span>
            </a>
        </h1>
        <?php else: ?>
        <div class="site-logo" aria-label="<?php echo htmlspecialchars($logoAriaLabel); ?>">
            <a href="<?php $this->options->siteUrl(); ?>" rel="home" class="gt-hover">
                <?php Mirai_renderLogo($this->options); ?>
            </a>
        </div>
        <?php endif; ?>
        <nav class="nav-center" role="navigation" aria-label="主导航">
            <a class="nav-link gt-hover" href="<?php $this->options->siteUrl(); ?>" aria-label="首页">首页</a>
            <?php echo Mirai_getNavigationMenu(); ?>
        </nav>
        
        <div class="nav-right" role="toolbar" aria-label="工具栏">
            <button class="nav-link gt-hover search-btn" onclick="openSearch()" title="搜索" aria-label="打开搜索">
                <i class="ri-search-line"></i>
            </button>
            <?php 
            if($this->user->hasLogin()): 
            ?>
            <?php if($userCenterEnabled): ?>
            <?php $userCenterUrl = Mirai_getPageUrlBySlug('user'); ?>
            <a class="nav-link gt-hover user-btn d-md-flex" href="<?php echo htmlspecialchars($userCenterUrl ?: $this->options->siteUrl); ?>" title="<?php echo $userCenterUrl ? '用户中心' : '返回首页'; ?>" aria-label="<?php echo $userCenterUrl ? '用户中心' : '返回首页'; ?>">
                <i class="ri-user-settings-line"></i>
            </a>
            <?php else: ?>
            <a class="nav-link gt-hover user-btn d-md-flex" href="<?php $this->options->siteUrl(); ?>" title="返回首页" aria-label="返回首页">
                <i class="ri-user-settings-line"></i>
            </a>
            <?php endif; ?>
            <?php elseif ($frontendLoginEnabled): ?>
            <button class="nav-link gt-hover user-btn d-md-flex" onclick="window.openLoginModal()" title="登录" aria-label="登录">
                <i class="ri-user-line"></i>
            </button>
            <?php endif; ?>
            <button class="nav-link gt-hover theme-btn d-md-flex" onclick="toggleTheme()" title="切换主题" aria-label="切换主题模式">
                <i class="ri-sun-line theme-icon-sun" style="display: <?php echo $initialTheme === 'light' ? 'inline-block' : 'none'; ?>"></i>
                <i class="ri-moon-line theme-icon-moon" style="display: <?php echo $initialTheme === 'dark' ? 'inline-block' : 'none'; ?>"></i>
            </button>
        </div>
    </section>
</header>
<?php $this->need('modules/search.php'); ?>
<main role="main" class="main">
    <section class="main-inner <?php echo (isset($this->hideThemeSidebar) && $this->hideThemeSidebar) ? 'no-aside' : ($this->options->asidePosition == 'left' ? 'aside-left' : 'aside-right'); ?> <?php if($this->is('index')) echo 'home-page'; ?>">
        <div class="main-inner-content">