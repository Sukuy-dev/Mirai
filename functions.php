<?php
/**
 * 感谢使用 Mirai 未来主题，这是一款为 Typecho 打造的简约优雅、多功能现代化内容管理主题。
 * 作者：苏酷伊 Sukuy
 * 博客：https://www.sukuy.com
 * QQ：1461139506
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (!defined('MIRAI_THEME_VERSION_TEXT')) {
    define('MIRAI_THEME_VERSION_TEXT', '1.0.2');
}
if (!defined('MIRAI_THEME_VERSION')) {
    define('MIRAI_THEME_VERSION', 1002);
}

require_once __DIR__ . '/core/loader.php';
require_once __DIR__ . '/common/functions.php';
require_once __DIR__ . '/common/init.php';
require_once __DIR__ . '/common/control.php';
require_once __DIR__ . '/common/mysql/migration.php';
require_once __DIR__ . '/common/backup.php';
require_once __DIR__ . '/common/editor.php';
require_once __DIR__ . '/modules/upload.php';
require_once __DIR__ . '/modules/pagination.php';
require_once __DIR__ . '/common/config/theme-config.php';

Mirai_registerEditorHooks();

function themeActivate() {
    Mirai_ensureDatabaseSchema();
}