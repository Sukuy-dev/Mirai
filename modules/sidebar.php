<?php
/**
 * 桌面端边栏
 * 移动端不渲染此文件
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (Mirai_isMobile()) {
    return;
}

$options = $this->options;

// 全局开关：禁用桌面端边栏
if ($options->asideEnable === '0') {
    return;
}
?>
<aside class="gt-aside" aria-label="边栏">
    <div class="sticky-aside">
        <?php renderSidebarModules($options, 120); ?>
    </div>
</aside>