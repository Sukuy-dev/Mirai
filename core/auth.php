<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!defined('MIRAI_CORE_LOADER_READY') && !(defined('MIRAI_AUTH_SKIP_ENFORCE') && MIRAI_AUTH_SKIP_ENFORCE)) {
    header('HTTP/1.1 503 Service Unavailable');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>安全校验失败</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h1 style="color:red;">警告：非法篡改 !</h1><p>安全校验失败：检测到核心加载器丢失，请复原文件或重新安装主题。</p><p>如有疑问请联系：<br>QQ：1461139506<br>微信：Sakura1086 &nbsp;&nbsp; 邮箱：support@sukuy.com</p></body></html>';
    exit;
}
if (!class_exists('Typecho_Plugin') || !Typecho_Plugin::exists('MiraiCore')) {
    header('HTTP/1.1 503 Service Unavailable');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>MiraiCore Required</title></head><body><h1>MiraiCore插件未启用</h1><p>请先启用MiraiCore插件后再使用Mirai未来主题。</p><p>如有任何疑问请联系<br>QQ：1461139506<br>微信：Sakura1086</p></body></html>';
    exit;
}
if (!defined('MIRAI_AUTH_SERVER')) define('MIRAI_AUTH_SERVER', 'https://auth.sukuy.com');
if (!defined('MIRAI_AUTH_APP_UID')) define('MIRAI_AUTH_APP_UID', '1');
if (!defined('MIRAI_CORE_AUTH_LOADED')) {
    define('MIRAI_CORE_AUTH_LOADED', true);
}
if (!function_exists('Mirai_getAuthConfig')) {
    function Mirai_getAuthConfig() {
        $themeOptions = null;
        try {
            if (class_exists('Typecho_Widget')) {
                $options = Typecho_Widget::widget('Widget_Options');
                $themeOptions = $options;
            }
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }
        $server = is_string(MIRAI_AUTH_SERVER) ? trim(MIRAI_AUTH_SERVER) : '';
        if ($server !== '') {
            $server = rtrim($server, '/');
        }
        $appUid = is_string(MIRAI_AUTH_APP_UID) ? trim(MIRAI_AUTH_APP_UID) : '';
        $publicKey = '';
        $insecure = false;
        $license = defined('MIRAI_AUTH_LICENSE') ? (string)MIRAI_AUTH_LICENSE : '';
        if ($license === '') {
            $envLicense = getenv('MIRAI_AUTH_LICENSE');
            if (is_string($envLicense)) {
                $license = $envLicense;
            }
        }
        if ($license === '') {
            try {
                $db = \Typecho\Db::get();
                $prefix = $db->getPrefix();
                $authTable = $prefix . 'mirai_auth';
                $tableExists = $db->fetchRow($db->query("SHOW TABLES LIKE '" . $authTable . "'"));
                if ($tableExists) {
                    $authData = $db->fetchRow($db->select('license')->from($authTable)->limit(1));
                    if ($authData && !empty($authData['license'])) {
                        $license = (string)$authData['license'];
                    }
                }
            } catch (Exception $e) {
            } catch (Throwable $e) {
            }
        }
        if ($license === '' && is_object($themeOptions) && isset($themeOptions->licenseCode)) {
            $license = (string)$themeOptions->licenseCode;
            if ($license !== '') {
                try {
                    $db = \Typecho\Db::get();
                    $prefix = $db->getPrefix();
                    $authTable = $prefix . 'mirai_auth';
                    $tableExists = $db->fetchRow($db->query("SHOW TABLES LIKE '" . $authTable . "'"));
                    if ($tableExists) {
                        $exists = $db->fetchRow($db->select('id')->from($authTable)->limit(1));
                        if ($exists) {
                            $db->query($db->update($authTable)->rows(['license' => $license, 'updated' => time()]));
                        } else {
                            $db->query($db->insert($authTable)->rows(['license' => $license, 'created' => time(), 'updated' => time()]));
                        }
                    }
                } catch (Exception $e) {
                } catch (Throwable $e) {
                }
            }
        }
        $cacheDays = defined('MIRAI_AUTH_CACHE_DAYS') ? (int)MIRAI_AUTH_CACHE_DAYS : 3;
        $timeout = defined('MIRAI_AUTH_TIMEOUT') ? (int)MIRAI_AUTH_TIMEOUT : 3;
        if ($cacheDays < 1) $cacheDays = 1;
        if ($timeout < 1) $timeout = 3;
        return [
            'server' => $server,
            'appUid' => $appUid,
            'appKey' => '', 
            'license' => $license,
            'publicKey' => $publicKey,
            'cacheDays' => $cacheDays,
            'timeout' => $timeout,
            'insecure' => $insecure
        ];
    }
}

if (!function_exists('Mirai_authFetchPublicKey')) {
    function Mirai_authFetchPublicKey($config) {
        if (empty($config['server'])) {
            return '';
        }
        $url = $config['server'] . '/check.php?public_key=1';
        $response = Mirai_authRequest($url, $config['timeout'], !empty($config['insecure']));
        if (!is_string($response) || $response === '') {
            return '';
        }
        $data = json_decode($response, true);
        if (!is_array($data)) {
            return '';
        }
        if (isset($data['public_key']) && is_string($data['public_key']) && $data['public_key'] !== '') {
            return str_replace("\\n", "\n", $data['public_key']);
        }
        return '';
    }
}
if (!function_exists('Mirai_authVerifyJwt')) {
    function Mirai_authVerifyJwt($token, $publicKey, $host, $appUid) {
        if (!is_string($token) || $token === '' || !is_string($publicKey) || $publicKey === '') {
            return false;
        }
        if (!function_exists('openssl_verify')) {
            return false;
        }
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        [$headB64, $payloadB64, $sigB64] = $parts;
        $headerJson = Mirai_authBase64UrlDecode($headB64);
        $payloadJson = Mirai_authBase64UrlDecode($payloadB64);
        if (!is_string($headerJson) || !is_string($payloadJson)) {
            return false;
        }
        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);
        if (!is_array($header) || !is_array($payload)) {
            return false;
        }
        if (!isset($header['alg']) || $header['alg'] !== 'RS256') {
            return false;
        }
        $signature = Mirai_authBase64UrlDecode($sigB64);
        $data = $headB64 . '.' . $payloadB64;
        $verified = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            return false;
        }
        $payloadExp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
        if ($payloadExp > 0 && time() > $payloadExp) {
            return false;
        }
        if (isset($payload['app_uid']) && (string)$payload['app_uid'] !== (string)$appUid) {
            return false;
        }
        $payloadDomain = isset($payload['domain']) ? (string)$payload['domain'] : '';
        $hostNorm = Mirai_authNormalizeHost($host);
        $hostPortNorm = Mirai_authNormalizeHostWithPort($host);
        $payloadNorm = Mirai_authNormalizeHost($payloadDomain);
        $payloadPortNorm = Mirai_authNormalizeHostWithPort($payloadDomain);
        if ($payloadNorm !== '' && $hostNorm !== '' && $payloadNorm !== $hostNorm) {
            if ($payloadPortNorm !== '' && $hostPortNorm !== '' && $payloadPortNorm !== $hostPortNorm) {
                return false;
            }
        }
        return $payload;
    }
}
if (!function_exists('Mirai_checkAuthorization')) {
    function Mirai_checkAuthorization($force = false) {
        static $checkResult = null;
        if (!$force && $checkResult !== null) {
            return $checkResult;
        }

        if (isset($_GET['mirai_force_verify'])) {
            $force = true;
        }
        $config = Mirai_getAuthConfig();
        $checkResult = Mirai_checkAuthorizationInternal($config, $force, false);
        return $checkResult;
    }
}
if (!function_exists('Mirai_checkAuthorizationInternal')) {
    function Mirai_checkAuthorizationInternal($config, $force = false, $isTrial = false) {
        if (defined('MIRAI_TAMPER_DETECTED') && MIRAI_TAMPER_DETECTED) {
            return ['ok' => false, 'msg' => '安全校验失败：检测到非法篡改'];
        }
        if (!is_array($config)) {
            return ['ok' => false, 'msg' => '许可配置缺失'];
        }
        if ($isTrial) {
            $config['license'] = '__trial__';
        } elseif (empty($config['license'])) {
            return Mirai_checkAuthorizationInternal($config, $force, true);
        }
        if ($config['server'] === '' || $config['appUid'] === '') {
            return ['ok' => false, 'msg' => '许可配置缺失'];
        }
        if (!preg_match('/^\d+$/', (string)$config['appUid'])) {
            return ['ok' => false, 'msg' => '应用UID必须为纯数字'];
        }
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
        if ($host === '') {
            return ['ok' => false, 'msg' => '域名解析失败'];
        }
        $cachePath = Mirai_authCachePath($config, $host);
        if (!$force) {
            $cache = Mirai_authLoadCache($cachePath);
            if (is_array($cache)) {
                $cachedAt = isset($cache['fetched_at']) ? (int)$cache['fetched_at'] : 0;
                $cacheValid = $cachedAt > 0 && (time() - $cachedAt) <= ($config['cacheDays'] * 86400);
                if ($cacheValid) {
                    $token = $cache['token'] ?? '';
                    $publicKey = $cache['public_key'] ?? $config['publicKey'];
                    $payload = Mirai_authVerifyJwt($token, $publicKey, $host, $config['appUid']);
                    if (is_array($payload)) {
                        $exp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
                        $isBanned = isset($payload['banned']) && $payload['banned'] === true;
                        if ($exp > 0 && time() > $exp) {
                            if ($isTrial) {
                                return ['ok' => false, 'msg' => '试用已到期，请输入有效许可密钥继续使用'];
                            }
                            $cache = null;
                        } elseif ($isBanned) {
                            if ($isTrial) {
                                return ['ok' => false, 'msg' => '许可已被封禁，请联系管理员'];
                            }
                            $cache = null;
                        } else {
                            return ['ok' => true, 'payload' => $payload, 'from_cache' => true, 'trial' => $isTrial];
                        }
                    }
                }
            }
        }
        $publicKey = $config['publicKey'];
        if ($publicKey === '') {
            $publicKey = Mirai_authFetchPublicKey($config);
        } else {
            $publicKey = str_replace("\\n", "\n", $publicKey);
        }
        if ($publicKey === '') {
            return ['ok' => false, 'msg' => '无法获取公钥'];
        }
        $queryParams = ['url' => $host, 'app_uid' => $config['appUid']];
        if ($isTrial) {
            $queryParams['trial'] = '1';
        } else {
            $queryParams['authcode'] = $config['license'];
        }
        $url = $config['server'] . '/check.php?' . http_build_query($queryParams);
        $response = Mirai_authRequest($url, $config['timeout'], !empty($config['insecure']));
        if (!is_string($response) || $response === '') {
            return ['ok' => false, 'msg' => '许可服务器无响应'];
        }
        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'msg' => '许可响应异常'];
        }
        if (!isset($data['code']) || (string)$data['code'] !== '1') {
            $msg = isset($data['msg']) ? (string)$data['msg'] : ($isTrial ? '试用验证失败' : '许可验证失败');
            return ['ok' => false, 'msg' => $msg];
        }
        $token = $data['token'] ?? '';
        $payload = Mirai_authVerifyJwt($token, $publicKey, $host, $config['appUid']);
        if (!is_array($payload)) {
            return ['ok' => false, 'msg' => $isTrial ? '试用校验失败' : '许可密钥不匹配或已失效'];
        }
        $exp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
        if ($isTrial && $exp > 0 && time() > $exp) {
            return ['ok' => false, 'msg' => '试用已到期，请输入有效许可密钥继续使用'];
        }

        $cacheData = [
            'token' => $token,
            'public_key' => $publicKey,
            'fetched_at' => time()
        ];

        Mirai_authSaveCache($cachePath, $cacheData);
        return ['ok' => true, 'payload' => $payload, 'from_cache' => false, 'trial' => $isTrial];
    }
}
if (!function_exists('Mirai_checkAuthorizationWithLicense')) {
    function Mirai_checkAuthorizationWithLicense($license, $force = true) {
        $config = Mirai_getAuthConfig();
        $config['license'] = is_string($license) ? trim($license) : '';
        return Mirai_checkAuthorizationInternal($config, $force);
    }
}
if (!function_exists('Mirai_authGetStatus')) {
    function Mirai_authGetStatus($force = false) {
        $result = Mirai_checkAuthorization($force);
        $payload = is_array($result) && isset($result['payload']) ? $result['payload'] : [];
        $exp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
        $daysLeft = $exp > 0 ? max(0, (int)ceil(($exp - time()) / 86400)) : null;
        $status = [
            'ok' => is_array($result) && !empty($result['ok']),
            'msg' => is_array($result) && isset($result['msg']) ? (string)$result['msg'] : '',
            'payload' => is_array($payload) ? $payload : [],
            'expires_at' => $exp > 0 ? $exp : null,
            'days_left' => $daysLeft,
            'from_cache' => is_array($result) && !empty($result['from_cache'])
        ];
        $config = Mirai_getAuthConfig();
        if ($force && empty($status['ok'])) {
            $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
            if ($host !== '') {
                Mirai_authClearCache($config, $host);
            }
        }
        $renewUrl = '';
        if (!empty($config['server']) && !empty($config['license'])) {
            $renewUrl = $config['server'] . '/renew.php?authcode=' . urlencode($config['license']);
        }
        $status['renew_url'] = $renewUrl;
        return $status;
    }
}

if (!function_exists('Mirai_coreAuthEnforce')) {
    function Mirai_coreAuthEnforce() {
        if (defined('MIRAI_AUTH_SKIP_ENFORCE') && MIRAI_AUTH_SKIP_ENFORCE) {
            return;
        }
        if (php_sapi_name() === 'cli') return;
        if (isset($_GET['mirai_api'])) {
            return;
        }
        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
        if (stripos($requestUri, '/user/auth/ajax') !== false) {
            return;
        }
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $adminPath = '/' . trim((string)__TYPECHO_ADMIN_DIR__, '/');
        if (stripos($script, $adminPath . '/login.php') !== false || stripos($script, $adminPath . '/register.php') !== false) {
            return;
        }
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $isAction = $requestUri !== '' && stripos($requestUri, '/action/') !== false;
        $isAdmin = ($script !== '' && stripos($script, $adminPath . '/') !== false) || $isAction;
        $isThemeOptions = $isAdmin && (
            stripos($script, $adminPath . '/options-theme.php') !== false ||
            stripos($requestUri, $adminPath . '/options-theme.php') !== false
        );
        $isThemeEditor = $isAdmin && (
            stripos($script, $adminPath . '/theme-editor.php') !== false ||
            stripos($requestUri, $adminPath . '/theme-editor.php') !== false
        );
        $isThemeAction = $isAdmin && stripos($requestUri, '/action/themes-edit') !== false;
        try {
            $overrideLicense = null;
            if (($isThemeOptions || $isThemeAction) && $method === 'POST' && isset($_POST['licenseCode'])) {
                $overrideLicense = trim((string)$_POST['licenseCode']);
            }
            if (is_string($overrideLicense) && $overrideLicense !== '') {
                $result = Mirai_checkAuthorizationWithLicense($overrideLicense, true);
            } else {
                $result = Mirai_checkAuthorization();
            }
        } catch (Exception $e) {
            $result = ['ok' => false, 'msg' => '系统错误: ' . $e->getMessage()];
        } catch (Error $e) {
            $result = ['ok' => false, 'msg' => '致命错误: ' . $e->getMessage()];
        }
        if (!is_array($result) || empty($result['ok'])) {
            $msg = is_array($result) && isset($result['msg']) ? (string)$result['msg'] : '许可校验失败';
            $requestUriLower = strtolower($requestUri);
            $isDeveloperAuthApi = (strpos($requestUriLower, '/user/auth/ajax') !== false);
            if ($isDeveloperAuthApi) {
                if (!headers_sent()) {
                    header('HTTP/1.1 403 Forbidden');
                    header('Content-Type: application/json; charset=UTF-8');
                }
                echo json_encode([
                    'code' => -1,
                    'msg'  => $msg !== '' ? $msg : '许可验证失败',
                    'success' => false
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($isAdmin) {
                if (($isThemeOptions || $isThemeEditor || $isThemeAction) && $method === 'POST') {
                    if (!headers_sent()) {
                        header('HTTP/1.1 403 Forbidden');
                    }
                    Mirai_renderAdminAuthModal($msg, true);
                    exit;
                }
                if ($isThemeEditor) {
                    Mirai_renderAdminAuthModal($msg, true);
                    return;
                }
                if ($isThemeOptions) {
                    return;
                }
                return;
            }
            if (!headers_sent()) {
                header('HTTP/1.1 403 Forbidden');
            }
            Mirai_renderSiteAuthModal($msg, true);
            return;
        }
    }
}
if (!function_exists('Mirai_getDynamicConfigKey')) {

    function Mirai_getDynamicConfigKey($payload) {
        if (!is_array($payload)) {
            return null;
        }
        
        if (isset($payload['config_key']) && is_string($payload['config_key'])) {
            $key = trim($payload['config_key']);

            if (preg_match('/^[0-9a-f]{64}$/i', $key)) {
                return $key;
            }
        }
        
        return null;
    }
}

if (!function_exists('Mirai_getSecureConfig')) {
    function Mirai_getSecureConfig($type) {
        static $memoryCache = [];
        
        if (isset($memoryCache[$type])) {
            return $memoryCache[$type];
        }

        $status = Mirai_checkAuthorization();
        if (empty($status['ok']) || empty($status['payload'])) {
            return null;
        }
        
        $payload = $status['payload'];

        $configKey = Mirai_getDynamicConfigKey($payload);
        if ($configKey === null) {
            return null;
        }

        if (!isset($payload['secure_config']['data'], $payload['secure_config']['iv'], $payload['secure_config']['tag'])) {
            return null;
        }
        
        $encrypted = $payload['secure_config'];
        $decrypted = openssl_decrypt(
            base64_decode($encrypted['data']),
            'aes-256-gcm',
            hex2bin($configKey),
            OPENSSL_RAW_DATA,
            base64_decode($encrypted['iv']),
            base64_decode($encrypted['tag'])
        );
        
        if ($decrypted === false) {
            return null;
        }
        
        $allConfigs = json_decode($decrypted, true);
        if (!is_array($allConfigs)) {
            return null;
        }
        
        if (isset($allConfigs[$type])) {
            $memoryCache[$type] = $allConfigs[$type];
            return $allConfigs[$type];
        }
        
        return null;
    }
}

if (!function_exists('Mirai_getPaymentConfig')) {
    function Mirai_getPaymentConfig() {
        $config = Mirai_getSecureConfig('payment');
        if ($config === null || empty($config['enabled'])) {
            return ['available' => false, 'msg' => '支付功能需要有效的许可验证'];
        }
        return ['available' => true, 'enabled' => true];
    }
}

if (!function_exists('Mirai_getMailConfig')) {
    function Mirai_getMailConfig() {
        $config = Mirai_getSecureConfig('mail');
        if ($config === null || empty($config['enabled'])) {
            return ['available' => false, 'msg' => '邮件功能需要有效的许可验证'];
        }
        return ['available' => true, 'enabled' => true];
    }
}