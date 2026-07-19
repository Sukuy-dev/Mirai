<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
function Mirai_customCssVars($archive) {
    $options = Mirai_opt();

    $themeColor = trim(Mirai_getFeatureValue($options->themeColor ? $options->themeColor : '#007fff', '#007fff', 'theme_color'));
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $themeColor)) {
        $themeColor = '#007fff';
    }
    
    $fontColor = trim(Mirai_getFeatureValue($options->fontColor ? $options->fontColor : '#34495e', '#34495e', 'font_color'));
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $fontColor)) {
        $fontColor = '#34495e';
    }

    $borderRadius = $options->borderRadius ? $options->borderRadius : '0.5rem';
    if (!preg_match('/^(\d+(\.\d+)?(px|rem|em|%|vw|vh)|0)$/', $borderRadius)) {
        $borderRadius = '0.5rem';
    }
    
    $asideWidth = $options->asideWidth ? $options->asideWidth : '318px';
    if (!preg_match('/^(\d+(\.\d+)?(px|rem|em|%|vw|vh)|0)$/', $asideWidth)) {
        $asideWidth = '318px';
    }
    
    $mainMaxWidth = $options->mainMaxWidth ? $options->mainMaxWidth : '1288px';
    if (!preg_match('/^(\d+(\.\d+)?(px|rem|em|%|vw|vh)|0)$/', $mainMaxWidth)) {
        $mainMaxWidth = '1288px';
    }
    
    $navRadiusMultiplier = $options->navRadiusMultiplier ? $options->navRadiusMultiplier : '2';
    if (!preg_match('/^\d+(\.\d+)?$/', $navRadiusMultiplier)) {
        $navRadiusMultiplier = '2';
    }
    
    $enableAnimation = Mirai_getFeatureValue($options->enableAnimation !== null ? $options->enableAnimation : '1', '0', 'animation');
    $themeR = hexdec(substr($themeColor, 1, 2));
    $themeG = hexdec(substr($themeColor, 3, 2));
    $themeB = hexdec(substr($themeColor, 5, 2));
    $fontR = hexdec(substr($fontColor, 1, 2));
    $fontG = hexdec(substr($fontColor, 3, 2));
    $fontB = hexdec(substr($fontColor, 5, 2));
    $scrollbarEnable = $options->enableScrollbar !== null ? $options->enableScrollbar : '1';
    $scrollbarCSS = '';
    if ($scrollbarEnable === '1') {
        $scrollbarColor = 'rgba('.$themeR.','.$themeG.','.$themeB.',0.28)';
        $scrollbarHoverColor = 'rgba('.$themeR.','.$themeG.','.$themeB.',0.56)';
        $scrollbarCSS = '*{scrollbar-width:thin !important;-ms-overflow-style:auto !important;}*::-webkit-scrollbar{display:block !important;width:6px !important;height:6px !important;}*::-webkit-scrollbar-thumb{background:'.$scrollbarColor.' !important;border-radius:3px !important;}*::-webkit-scrollbar-thumb:hover{background:'.$scrollbarHoverColor.' !important;}*::-webkit-scrollbar-track{background:transparent !important;}html{scrollbar-color:'.$scrollbarColor.' transparent !important;}';
    } else {
        $scrollbarCSS = '*{scrollbar-width:none !important;-ms-overflow-style:none !important;}*::-webkit-scrollbar{display:none !important;}';
    }

    echo '<style>';
    echo ':root{';
    echo '--gt-main-color:' . htmlspecialchars($themeColor, ENT_QUOTES, 'UTF-8') . ';';
    echo '--gt-main-rgb:'.$themeR.','.$themeG.','.$themeB.';';
    echo '--gt-font-color:' . htmlspecialchars($fontColor, ENT_QUOTES, 'UTF-8') . ';';
    echo '--gt-main-radius:' . htmlspecialchars($borderRadius, ENT_QUOTES, 'UTF-8') . ';';
    echo '--gt-nav-radius-multiplier:' . htmlspecialchars($navRadiusMultiplier, ENT_QUOTES, 'UTF-8') . ';';
    echo '--gt-aside-width:' . htmlspecialchars($asideWidth, ENT_QUOTES, 'UTF-8') . ';';
    echo '--gt-main-max-width:' . htmlspecialchars($mainMaxWidth, ENT_QUOTES, 'UTF-8') . ';';
    echo '--gt-main-color-88:rgba('.$themeR.','.$themeG.','.$themeB.',0.88);';
    echo '--gt-main-color-66:rgba('.$themeR.','.$themeG.','.$themeB.',0.66);';
    echo '--gt-main-color-56:rgba('.$themeR.','.$themeG.','.$themeB.',0.56);';
    echo '--gt-main-color-28:rgba('.$themeR.','.$themeG.','.$themeB.',0.28);';
    echo '--gt-main-color-16:rgba('.$themeR.','.$themeG.','.$themeB.',0.16);';
    echo '--gt-main-color-10:rgba('.$themeR.','.$themeG.','.$themeB.',0.10);';
    echo '--gt-main-color-6:rgba('.$themeR.','.$themeG.','.$themeB.',0.06);';
    echo '--gt-main-color-light:rgba('.$themeR.','.$themeG.','.$themeB.',0.5);';
    
    echo '--gt-font-88:rgba('.$fontR.','.$fontG.','.$fontB.',0.88);';
    echo '--gt-font-66:rgba('.$fontR.','.$fontG.','.$fontB.',0.66);';
    echo '--gt-font-56:rgba('.$fontR.','.$fontG.','.$fontB.',0.56);';
    echo '--gt-font-28:rgba('.$fontR.','.$fontG.','.$fontB.',0.28);';
    echo '--gt-font-16:rgba('.$fontR.','.$fontG.','.$fontB.',0.16);';
    echo '--gt-font-6:rgba('.$fontR.','.$fontG.','.$fontB.',0.06);';
    echo '}';

    echo '[data-theme="dark"]{';
    echo '--gt-font-color:#e8e6e3;';
    echo '--gt-font-88:rgba(232,230,227,0.88);';
    echo '--gt-font-66:rgba(232,230,227,0.72);';
    echo '--gt-font-56:rgba(232,230,227,0.62);';
    echo '--gt-font-28:rgba(232,230,227,0.32);';
    echo '--gt-font-16:rgba(232,230,227,0.18);';
    echo '--gt-font-6:rgba(232,230,227,0.08);';
    echo '}';

    if ($enableAnimation !== '1') {
        echo '.gt-animation,.gt-animation.gt-animation-init{animation:none!important;opacity:1!important;}.gt-article-item:nth-child(n){animation-delay:0s!important;}.gt-animation{transition:none!important;transform:none!important;}';
    }

    echo $scrollbarCSS;

    echo '.mirai-logo-dark{display:none!important;}';
    echo '[data-theme="dark"] .mirai-logo-light{display:none!important;}';
    echo '[data-theme="dark"] .mirai-logo-dark{display:inline-block!important;}';
    
    echo "</style>\n";
}

function Mirai_customHead() {
    $options = Mirai_opt();
    if (!empty($options->customHead)) {
        echo $options->customHead;
    }
}

function Mirai_customFooterCode() {
    $options = Mirai_opt();
    if (!empty($options->customFooterCode)) {
        echo $options->customFooterCode;
    }
}

function Mirai_adminHead() {
    $options = \Typecho\Widget::widget('Widget_Options');
    $security = \Typecho\Widget::widget('Widget_Security');

    echo '<script>';
    echo 'window.MIRAI_ADMIN_CONFIG = {';
    echo '  apiUrl: "' . $options->siteUrl . '",';
    echo '  token: "' . $security->getToken('api') . '"';
    echo '};';
    echo '</script>';
    
    echo '<link rel="stylesheet" href="' . Mirai_getThemeUrl() . '/assets/RemixIcon/4.9.1/remixicon.css">';
}