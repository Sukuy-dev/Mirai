<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (!function_exists('Mirai_authNormalizeHost')) {
    function Mirai_authNormalizeHost($input) {
        $input = trim((string)$input);
        if ($input === '') return '';
        if (stripos($input, 'http://') === 0 || stripos($input, 'https://') === 0) {
            $parsedHost = parse_url($input, PHP_URL_HOST);
            if (is_string($parsedHost) && $parsedHost !== '') {
                $input = $parsedHost;
            }
        }
        $input = preg_replace('/\s+/', '', $input);
        $input = preg_split('/[\/\?#]/', $input, 2)[0];
        $input = preg_replace('/[:：]\d+$/', '', $input);
        $input = rtrim($input, '.');
        return strtolower($input);
    }
}

if (!function_exists('Mirai_authNormalizeHostWithPort')) {
    
    function Mirai_authNormalizeHostWithPort($input) {
        $input = trim((string)$input);
        if ($input === '') return '';
        if (stripos($input, 'http://') === 0 || stripos($input, 'https://') === 0) {
            $parsedHost = parse_url($input, PHP_URL_HOST);
            $parsedPort = parse_url($input, PHP_URL_PORT);
            if (is_string($parsedHost) && $parsedHost !== '') {
                $input = $parsedHost;
                if (is_int($parsedPort) || ctype_digit((string)$parsedPort)) {
                    $input .= ':' . (string)$parsedPort;
                }
            }
        }
        $input = preg_replace('/\s+/', '', $input);
        $input = preg_split('/[\/\?#]/', $input, 2)[0];
        $input = preg_replace('/：(\d+)$/', ':$1', $input);
        $input = rtrim($input, '.');
        return strtolower($input);
    }
}

if (!function_exists('Mirai_authBase64UrlDecode')) {
    function Mirai_authBase64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

if (!function_exists('Mirai_authCachePath')) {
    function Mirai_authCachePath($config, $host) {
        $base = __TYPECHO_ROOT_DIR__ . '/usr/uploads/mirai-auth-cache';
        if (!is_dir($base)) {
            @mkdir($base, 0755, true);
        }
        $key = md5($host . '|' . $config['appUid'] . '|' . $config['license']);
        return rtrim($base, '/\\') . '/' . $key . '.json';
    }
}

if (!function_exists('Mirai_authLoadCache')) {
    function Mirai_authLoadCache($path) {
        if (!is_string($path) || $path === '' || !is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
}

if (!function_exists('Mirai_authSaveCache')) {
    function Mirai_authSaveCache($path, $data) {
        if (!is_string($path) || $path === '' || !is_array($data)) {
            return false;
        }
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (!is_string($payload)) {
            return false;
        }
        return @file_put_contents($path, $payload, LOCK_EX) !== false;
    }
}

if (!function_exists('Mirai_authClearCache')) {
    function Mirai_authClearCache($config, $host) {
        if (!is_array($config) || !is_string($host) || $host === '') {
            return false;
        }
        if (!isset($config['appUid']) || !isset($config['license'])) {
            return false;
        }
        $path = Mirai_authCachePath($config, $host);
        if (!is_string($path) || $path === '' || !is_file($path)) {
            return false;
        }
        return @unlink($path);
    }
}

if (!function_exists('Mirai_authResolveThemeBuild')) {
    function Mirai_authResolveThemeBuild() {
        $build = defined('MIRAI_THEME_VERSION') ? (string)MIRAI_THEME_VERSION : (defined('MIRAI_VERSION') ? (string)MIRAI_VERSION : '');
        if (preg_match('/^\d+$/', $build) && (int)$build > 0) {
            return $build;
        }
        static $fileBuild = null;
        if ($fileBuild === null) {
            $fileBuild = '';
            $indexFile = dirname(__DIR__) . '/index.php';
            if (is_file($indexFile) && is_readable($indexFile)) {
                $indexContent = @file_get_contents($indexFile);
                if (is_string($indexContent) && $indexContent !== '') {
                    if (preg_match('/define\s*\(\s*[\'"]MIRAI_THEME_VERSION[\'"]\s*,\s*(\d+)\s*\)\s*;/', $indexContent, $m)) {
                        $fileBuild = (string)$m[1];
                    }
                }
            }
        }
        if (preg_match('/^\d+$/', $fileBuild) && (int)$fileBuild > 0) {
            return $fileBuild;
        }
        $versionText = defined('MIRAI_THEME_VERSION_TEXT') ? (string)MIRAI_THEME_VERSION_TEXT : '';
        if ($versionText !== '') {
            $versionDigits = preg_replace('/\D+/', '', $versionText);
            if (preg_match('/^\d+$/', $versionDigits) && (int)$versionDigits > 0) {
                return $versionDigits;
            }
        }
        return '1';
    }
}

if (!function_exists('Mirai_authRequest')) {
    function Mirai_authRequest($url, $timeout = 3, $insecure = false) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (stripos($url, 'https://') === 0) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $insecure ? false : true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $insecure ? 0 : 2);
            }
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            $version = defined('MIRAI_THEME_VERSION_TEXT') ? MIRAI_THEME_VERSION_TEXT : '1.0.0';
            curl_setopt($ch, CURLOPT_USERAGENT, 'MiraiAuth/' . $version);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        }
        $version = defined('MIRAI_THEME_VERSION_TEXT') ? MIRAI_THEME_VERSION_TEXT : '1.0.0';
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'method' => 'GET',
                'header' => "User-Agent: MiraiAuth/" . $version . "\r\n"
            ],
            'ssl' => [
                'verify_peer' => $insecure ? false : true,
                'verify_peer_name' => $insecure ? false : true
            ]
        ]);
        return @file_get_contents($url, false, $context);
    }
}

if (!function_exists('Mirai_authCheckUpdate')) {
    function Mirai_authCheckUpdate($force = false) {
        $config = Mirai_getAuthConfig();
        if ($config['server'] === '' || $config['appUid'] === '') {
            return ['ok' => false, 'msg' => '更新配置缺失（请先配置许可服务器与应用UID）'];
        }
        if (!preg_match('/^\d+$/', (string)$config['appUid'])) {
            return ['ok' => false, 'msg' => '应用UID必须为纯数字'];
        }
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
        if ($host === '') {
            return ['ok' => false, 'msg' => '域名解析失败'];
        }
        $build = Mirai_authResolveThemeBuild();
        $verText = defined('MIRAI_THEME_VERSION_TEXT') ? (string)MIRAI_THEME_VERSION_TEXT : '';
        $url = $config['server'] . '/check.php?ver=' . urlencode($build) .
            '&ver_text=' . urlencode($verText) .
            '&url=' . urlencode($host) .
            '&authcode=' . urlencode((string)($config['license'] ?? '')) .
            '&app_uid=' . urlencode((string)$config['appUid']);
        $response = Mirai_authRequest($url, $config['timeout'], !empty($config['insecure']));
        if (!is_string($response) || $response === '') {
            return ['ok' => false, 'msg' => '更新服务器无响应'];
        }
        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'msg' => '更新响应异常'];
        }
        $code = isset($data['code']) ? (int)$data['code'] : null;
        $download = '';
        if (isset($data['download']) && is_string($data['download']) && trim($data['download']) !== '') {
            $download = trim($data['download']);
        } elseif (isset($data['file']) && is_string($data['file']) && trim($data['file']) !== '') {
            $download = trim($data['file']);
        }
        $log = '';
        if (isset($data['log']) && is_string($data['log']) && trim($data['log']) !== '') {
            $log = $data['log'];
        } elseif (isset($data['uplogs']) && is_string($data['uplogs']) && $data['uplogs'] !== '') {
            $log = $data['uplogs'];
        } elseif (isset($data['uplog']) && is_string($data['uplog'])) {
            $log = $data['uplog'];
        }
        $msg = isset($data['msg']) ? (string)$data['msg'] : '';
        if ($msg !== '') {
            $msg = trim(strip_tags($msg));
        }
        $updateEnabled = isset($data['update_enabled']) ? ((int)$data['update_enabled'] === 1) : ($code !== null);
        $hasUpdate = isset($data['update_available']) ? ((int)$data['update_available'] === 1) : ($code === 1);
        return [
            'ok' => $code !== null,
            'code' => $code,
            'msg' => $msg,
            'ver' => isset($data['ver']) ? (string)$data['ver'] : '',
            'version' => isset($data['version']) ? (string)$data['version'] : '',
            'log' => $log,
            'download' => $download,
            'update_enabled' => $updateEnabled,
            'has_update' => $hasUpdate
        ];
    }
}

if (!function_exists('Mirai_themeAuthStatus')) {
    function Mirai_themeAuthStatus() {
        if (function_exists('Mirai_authGetStatus')) {
            return Mirai_authGetStatus();
        }
        return ['ok' => false, 'msg' => '许可模块未加载'];
    }
}

if (!function_exists('Mirai_renderAuthStatusPage')) {
    function Mirai_renderAuthStatusPage($archive) {
        if (!is_object($archive)) {
            return;
        }
        $status = Mirai_themeAuthStatus();
        $payload = $status['payload'] ?? [];
        $render = function() use ($status, $payload) {
            $this->need('header.php');
            echo '<div class="container" style="padding: 40px 0;">';
            echo '<div class="card" style="padding: 24px;">';
            echo '<h2 style="margin-bottom: 16px;">许可状态</h2>';
            echo '<div style="line-height: 2;">';
            echo '<div>状态：' . htmlspecialchars($status['ok'] ? '已许可' : '未许可', ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
            echo '<div>说明：' . htmlspecialchars($status['ok'] ? '许可验证通过' : ($status['msg'] ?? '许可失败'), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
            echo '<div>许可域名：' . htmlspecialchars($payload['domain'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
            echo '<div>应用 UID：' . htmlspecialchars($payload['app_uid'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
            echo '<div>套餐：' . htmlspecialchars($payload['plan'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
            $expiresAt = isset($status['expires_at']) && $status['expires_at'] ? (new \Typecho\Date($status['expires_at']))->format('Y-m-d H:i:s') : '未知';
            echo '<div>到期时间：' . htmlspecialchars($expiresAt, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
            $daysLeft = $status['days_left'] === null ? '未知' : (string)$status['days_left'];
            echo '<div>剩余天数：' . htmlspecialchars($daysLeft, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
            if (!empty($status['renew_url'])) {
                $renewUrl = htmlspecialchars($status['renew_url'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                echo '<div>续费地址：<a href="' . $renewUrl . '" target="_blank">' . $renewUrl . '</a></div>';
            }
            if ($status['days_left'] !== null && $status['days_left'] <= 7) {
                $tip = $status['days_left'] <= 0 ? '许可已到期，请尽快续费' : '许可即将到期，请及时续费';
                echo '<div style="margin-top:8px;color:#d93026;">' . htmlspecialchars($tip, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>';
            }
            echo '</div></div></div>';
            $this->need('footer.php');
        };
        $render->call($archive);
    }
}

if (!function_exists('Mirai_renderAdminAuthModal')) {
    function Mirai_renderAdminAuthModal($msg, $lock = true) {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        $safeMsg = htmlspecialchars((string)$msg, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $optionsUrl = 'options-theme.php';
        try {
            $options = Typecho_Widget::widget('Widget_Options');
            $adminBaseUrl = rtrim((string)($options->adminUrl ?? ''), '/');
            if ($adminBaseUrl !== '') {
                $optionsUrl = $adminBaseUrl . '/options-theme.php';
            }
        } catch (Exception $e) {
        }
        echo '<style>.mirai-auth-modal{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.55);z-index:99999;padding:24px}.mirai-auth-card{position:relative;background:#fff;border-radius:14px;box-shadow:0 14px 40px rgba(0,0,0,.18);max-width:440px;width:100%;padding:28px;text-align:center;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif}.mirai-auth-close-icon{position:absolute;right:14px;top:12px;border:0;background:transparent;font-size:26px;line-height:26px;cursor:pointer;color:#9aa0a6}.mirai-auth-close-icon:hover{color:#111}.mirai-auth-title{margin:0 0 10px;font-size:20px;color:#111;font-weight:700}.mirai-auth-msg{margin:0 0 12px;font-size:18px;color:#dc2626;font-weight:800}.mirai-auth-desc{margin:0 0 18px;color:#111;font-size:14px;line-height:1.6}.mirai-auth-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}.mirai-auth-btn{display:inline-flex;align-items:center;justify-content:center;padding:8px 16px;border-radius:8px;border:1px solid #d0d7de;background:#2563eb;color:#fff;text-decoration:none;font-size:14px}</style>';
        echo '<div class="mirai-auth-modal" id="mirai-auth-modal"><div class="mirai-auth-card"><button type="button" class="mirai-auth-close-icon" id="mirai-auth-close" aria-label="关闭">×</button><div class="mirai-auth-title">许可验证失败</div><div class="mirai-auth-msg">' . $safeMsg . '</div><div class="mirai-auth-desc">如有疑问请联系<br>QQ：1461139506<br>微信：Sakura1086</div><div class="mirai-auth-actions"><button type="button" class="mirai-auth-btn" id="mirai-auth-ok-btn" style="border:none;cursor:pointer;">我知道了</button></div></div></div>';
        echo '<script>(function(){var modal=document.getElementById("mirai-auth-modal");if(!modal)return;var closeBtn=document.getElementById("mirai-auth-close");var okBtn=document.getElementById("mirai-auth-ok-btn");if(closeBtn){closeBtn.addEventListener("click",function(){modal.style.display="none";});}if(okBtn){okBtn.addEventListener("click",function(){modal.style.display="none";});}var lock=' . ($lock ? 'true' : 'false') . ';if(lock){var forms=document.querySelectorAll("form");for(var i=0;i<forms.length;i++){forms[i].addEventListener("submit",function(e){var licenseInput=this.querySelector("input[name=\'licenseCode\']");if(licenseInput&&licenseInput.value.trim()!==""){return;}e.preventDefault();modal.style.display="flex";});}var links=document.querySelectorAll("a[href]");for(var j=0;j<links.length;j++){links[j].addEventListener("click",function(e){var href=this.getAttribute("href");if(!href||href.indexOf("#")===0)return;e.preventDefault();modal.style.display="flex";});}}})();</script>';
    }
}

if (!function_exists('Mirai_renderSiteAuthModal')) {
    function Mirai_renderSiteAuthModal($msg, $lock = true) {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        $safeMsg = htmlspecialchars((string)$msg, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $optionsUrl = 'options-theme.php';
        try {
            $options = Typecho_Widget::widget('Widget_Options');
            $adminBaseUrl = rtrim((string)($options->adminUrl ?? ''), '/');
            if ($adminBaseUrl !== '') {
                $optionsUrl = $adminBaseUrl . '/options-theme.php';
            }
        } catch (Exception $e) {
        }
        echo '<style>.mirai-site-auth-modal{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.55);z-index:99999;padding:24px}.mirai-site-auth-card{position:relative;background:#fff;border-radius:14px;box-shadow:0 14px 40px rgba(0,0,0,.18);max-width:440px;width:100%;padding:28px;text-align:center;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif}.mirai-site-auth-close-icon{position:absolute;right:14px;top:12px;border:0;background:transparent;font-size:26px;line-height:26px;cursor:pointer;color:#9aa0a6}.mirai-site-auth-close-icon:hover{color:#111}.mirai-site-auth-title{margin:0 0 10px;font-size:20px;color:#111;font-weight:700}.mirai-site-auth-msg{margin:0 0 12px;font-size:18px;color:#dc2626;font-weight:800}.mirai-site-auth-desc{margin:0 0 18px;color:#111;font-size:14px;line-height:1.6}.mirai-site-auth-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}.mirai-site-auth-btn{display:inline-flex;align-items:center;justify-content:center;padding:8px 16px;border-radius:8px;border:1px solid #d0d7de;background:#2563eb;color:#fff;text-decoration:none;font-size:14px}</style>';
        echo '<div class="mirai-site-auth-modal" id="mirai-site-auth-modal"><div class="mirai-site-auth-card"><button type="button" class="mirai-site-auth-close-icon" id="mirai-site-auth-close" aria-label="关闭">×</button><div class="mirai-site-auth-title">许可验证失败</div><div class="mirai-site-auth-msg">' . $safeMsg . '</div><div class="mirai-site-auth-desc">如有疑问请联系<br>QQ：1461139506<br>微信：Sakura1086</div><div class="mirai-site-auth-actions"><button type="button" class="mirai-site-auth-btn" id="mirai-site-auth-ok-btn" style="border:none;cursor:pointer;">我知道了</button></div></div></div>';
        echo '<script>(function(){var modal=document.getElementById("mirai-site-auth-modal");if(!modal)return;var lock=' . ($lock ? 'true' : 'false') . ';var html=document.documentElement;var body=document.body;var prevHtmlOverflow=html?html.style.overflow:"";var prevBodyOverflow=body?body.style.overflow:"";if(lock&&html){html.style.overflow="hidden";if(body){body.style.overflow="hidden";}}var closeBtn=document.getElementById("mirai-site-auth-close");var okBtn=document.getElementById("mirai-site-auth-ok-btn");function closeModal(){modal.style.display="none";if(lock&&html){html.style.overflow=prevHtmlOverflow;if(body){body.style.overflow=prevBodyOverflow;}}}if(closeBtn){closeBtn.addEventListener("click",closeModal);}if(okBtn){okBtn.addEventListener("click",closeModal);}if(lock){var forms=document.querySelectorAll("form");for(var i=0;i<forms.length;i++){forms[i].addEventListener("submit",function(e){e.preventDefault();modal.style.display="flex";});}}})();</script>';
    }
}