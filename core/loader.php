<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (defined('MIRAI_CORE_LOADER_READY')) {
    return;
}

if (defined('MIRAI_CORE_AUTH_LOADED') || function_exists('Mirai_licenseEnsureCoreLoaded')) {
    header('HTTP/1.1 503 Service Unavailable');
    exit('Security Violation: Invalid Core Loader Bootstrap.');
}
if (!function_exists('Mirai_verifyMultiPointIntegrity')) {
    function Mirai_verifyMultiPointIntegrity() {
        static $isVerified = null;
        if ($isVerified !== null) {
            return $isVerified;
        }

        $checkpoints = [
            'functions' => [
                'path' => dirname(__DIR__) . '/functions.php',
                'hash' => defined('MIRAI_FUNCTIONS_SHA256') ? MIRAI_FUNCTIONS_SHA256 : '95f8dd334844a4b9c69a165e285dcbf9de65332eec191b23f54c51cdbac652d4',
                'require' => "require_once __DIR__ . '/core/loader.php'"
            ],
            'init' => [
                'path' => dirname(__DIR__) . '/common/init.php',
                'hash' => defined('MIRAI_INIT_SHA256') ? MIRAI_INIT_SHA256 : '51626e3e0cffbfcd052f7737bd5d7932564fddcc61688561e3e53d5b06591582',
                'require' => "Mirai_licenseEnsureCoreLoaded"
            ],
            'auth_public' => [
                'path' => __DIR__ . '/auth-public.php',
                'hash' => defined('MIRAI_AUTH_PUBLIC_SHA256') ? MIRAI_AUTH_PUBLIC_SHA256 : '2fd3020c545cb68ae9c919e8ed0d2482410550644ecc5effb773609b142f8d63',
                'require' => null
            ],
            'payapi' => [
                'path' => dirname(__DIR__) . '/common/functions/pay/api.php',
                'hash' => defined('MIRAI_PAYAPI_SHA256') ? MIRAI_PAYAPI_SHA256 : '13c6f6f0ff6abdc256c013be0e474441fe1f04c3d29f1dcead0cd5877f23edd0',
                'require' => null
            ],
            'license_tab' => [
                'path' => dirname(__DIR__) . '/common/config/license-tab.php',
                'hash' => defined('MIRAI_LICENSE_TAB_SHA256') ? MIRAI_LICENSE_TAB_SHA256 : 'b1e385c117c9eb6c8b3edf94a7fd74f5044155fabdba030ab58ac887d50bca31',
                'require' => null
            ],
            'mirai_core_plugin' => [
                'path' => __TYPECHO_ROOT_DIR__ . '/usr/plugins/MiraiCore/Plugin.php',
                'hash' => defined('MIRAI_CORE_PLUGIN_SHA256') ? MIRAI_CORE_PLUGIN_SHA256 : 'f28a5944f89a7d43ff25c4c8332c7d43573c6c16d062cdab1814413793f2c9ba',
                'require' => null
            ],
            'mirai_core_about' => [
                'path' => __TYPECHO_ROOT_DIR__ . '/usr/plugins/MiraiCore/About.php',
                'hash' => defined('MIRAI_CORE_ABOUT_SHA256') ? MIRAI_CORE_ABOUT_SHA256 : '3d2c2cd593883ac784142c6210ba0471b16a7649782c6c56c221d0c5c2940f57',
                'require' => null
            ],
        ];
        
        foreach ($checkpoints as $name => $checkpoint) {
            if (!is_file($checkpoint['path']) || !is_readable($checkpoint['path'])) {
                $isVerified = false;
                return false;
            }
            $content = @file_get_contents($checkpoint['path']);
            if ($content === false) {
                $isVerified = false;
                return false;
            }


            $normalized = preg_replace('/[\x00-\x20\x7f]/', '', $content);
            $normalized = preg_replace('/\s+/u', '', $normalized);

            if ($checkpoint['require']) {
                $normalizedRequire = preg_replace('/[\x00-\x20\x7f]/', '', $checkpoint['require']);
                $normalizedRequire = preg_replace('/\s+/u', '', $normalizedRequire);
                if (strpos($normalized, $normalizedRequire) === false) {
                    $isVerified = false;
                    return false;
                }
            }
            
            if ($checkpoint['hash']) {
                $actualHash = hash('sha256', $normalized);
                if (!hash_equals(strtolower($checkpoint['hash']), strtolower($actualHash))) {
                    $isVerified = false;
                    return false;
                }
            }
        }
        
        $isVerified = true;
        return true;
    }
}

if (!function_exists('Mirai_detectTamper')) {
    function Mirai_detectTamper() {
        $indicators = [];
        
        $authFuncs = ['Mirai_checkAuthorization_bypass', 'Mirai_authBypass', 'Mirai_licenseBypass'];
        foreach ($authFuncs as $func) {
            if (function_exists($func)) {
                $indicators[] = 'suspicious_function:' . $func;
            }
        }
        
        if (extension_loaded('xdebug') && (isset($_GET['XDEBUG_SESSION']) || isset($_COOKIE['XDEBUG_SESSION']))) {
            $indicators[] = 'debug_tool:xdebug';
        }
        
        $suspiciousEnv = ['MIRAI_AUTH_BYPASS', 'MIRAI_SKIP_AUTH', 'MIRAI_DEV_MODE'];
        foreach ($suspiciousEnv as $env) {
            if (getenv($env) !== false || isset($_ENV[$env])) {
                $indicators[] = 'env_injection:' . $env;
            }
        }
        
        if (!empty($indicators)) {
            Mirai_reportTamperAttempt($indicators);
            return false;
        }
        
        return true;
    }
}

if (!function_exists('Mirai_reportTamperAttempt')) {
    function Mirai_reportTamperAttempt($indicators) {
        $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
        $data = [
            'host' => $host,
            'indicators' => is_array($indicators) ? implode(',', $indicators) : $indicators,
            'time' => time(),
        ];
        
        $server = defined('MIRAI_AUTH_SERVER') ? MIRAI_AUTH_SERVER : 'https://auth.sukuy.com';
        $url = rtrim($server, '/') . '/report/tamper.php';
        
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_TIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0
            ]);
            @curl_exec($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($data),
                    'timeout' => 2
                ]
            ]);
            @file_get_contents($url, false, $context);
        }
    }
}

if (!Mirai_verifyMultiPointIntegrity()) {
    header('HTTP/1.1 503 Service Unavailable');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>安全校验失败</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h1 style="color:red;">警告：非法篡改 !</h1><p>安全校验失败：检测到主题文件已被修改或损坏，请复原文件或重新安装主题。</p><p>如有疑问请联系：<br>QQ：1461139506<br>微信：Sakura1086 &nbsp;&nbsp; 邮箱：support@sukuy.com</p></body></html>';
    exit;
}

if (!Mirai_detectTamper()) {
    define('MIRAI_TAMPER_DETECTED', true);
}

define('MIRAI_RUNTIME_TOKEN', bin2hex(random_bytes(32)));
define('MIRAI_CORE_LOADER_READY', true);

require_once __DIR__ . '/auth-public.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/license.php';

if (!defined('MIRAI_CORE_AUTH_LOADED') || !function_exists('Mirai_licenseEnsureCoreLoaded')) {
    header('HTTP/1.1 503 Service Unavailable');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>安全校验失败</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h1 style="color:red;">警告：非法篡改 !</h1><p>安全校验失败：检测到主题授权模块未能正常加载，请复原文件或重新安装主题。</p><p>如有疑问请联系：<br>QQ：1461139506<br>微信：Sakura1086 &nbsp;&nbsp; 邮箱：support@sukuy.com</p></body></html>';
    exit;
}