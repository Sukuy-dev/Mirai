<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (!function_exists('Mirai_licenseEnsureCoreLoaded')) {
    function Mirai_licenseEnsureCoreLoaded() {
        if (!defined('MIRAI_CORE_AUTH_LOADED')) {
            header('HTTP/1.1 503 Service Unavailable');
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>安全校验失败</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h1 style="color:red;">警告：非法篡改 !</h1><p>安全校验失败：检测到主题授权模块被剥离或损坏，请复原文件或重新安装主题。</p><p>如有疑问请联系：<br>QQ：1461139506<br>微信：Sakura1086 &nbsp;&nbsp; 邮箱：support@sukuy.com</p></body></html>';
            exit;
        }
    }
}

if (!function_exists('Mirai_handleAuthStatusRoute')) {
    function Mirai_handleAuthStatusRoute($archive, $pathInfo = null) {
        if (!is_object($archive)) {
            return false;
        }
        if ($pathInfo === null) {
            $pathInfo = $archive->request->getPathInfo();
        }
        if ($pathInfo !== '/auth' && $pathInfo !== '/auth/status') {
            return false;
        }
        $user = null;
        try {
            $user = Mirai_user();
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }
        $allowed = false;
        if (is_object($user) && method_exists($user, 'hasLogin') && $user->hasLogin()) {
            if (method_exists($user, 'pass')) {
                $allowed = $user->pass('administrator', true);
            } else {
                $allowed = true;
            }
        }
        if (!$allowed) {
            Mirai_abortNotFound($archive);
        }
        if (method_exists($archive->response, 'setStatus')) {
            $archive->response->setStatus(200);
        }
        $archive->setArchiveTitle('许可状态');
        $archive->setParameter('type', 'page');
        if (function_exists('Mirai_renderAuthStatusPage')) {
            Mirai_renderAuthStatusPage($archive);
        }
        exit;
    }
}

if (!function_exists('Mirai_handleLicenseApi')) {
    function Mirai_handleLicenseApi($api) {
        switch ($api) {
            case 'license_check_update':
                if (!function_exists('Mirai_authCheckUpdate')) {
                    throw new Exception('许可模块未加载');
                }
                $update = Mirai_authCheckUpdate(true);
                if (!is_array($update)) {
                    throw new Exception('更新检查失败');
                }
                return [
                    'code' => 0,
                    'success' => true,
                    'data' => $update
                ];

            case 'license_verify':
                if (!function_exists('Mirai_authGetStatus')) {
                    throw new Exception('许可模块未加载');
                }
                $status = Mirai_authGetStatus(true);
                if (!is_array($status)) {
                    throw new Exception('许可验证失败');
                }
                if (empty($status['ok']) && isset($status['msg']) && is_string($status['msg'])) {
                    if (strpos($status['msg'], '封禁') !== false) {
                        if (!isset($status['payload']) || !is_array($status['payload'])) {
                            $status['payload'] = [];
                        }
                        $status['payload']['banned'] = true;
                    }
                }
                return [
                    'code' => 0,
                    'success' => true,
                    'data' => $status
                ];

            case 'license_activate':
                if (!function_exists('Mirai_checkAuthorizationWithLicense')) {
                    throw new Exception('许可模块未加载');
                }
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception('非法请求');
                }
                $license = isset($_POST['license']) ? trim($_POST['license']) : '';
                if ($license === '') {
                    throw new Exception('请输入许可密钥');
                }
                $result = Mirai_checkAuthorizationWithLicense($license, true);
                if (!is_array($result) || empty($result['ok'])) {
                    throw new Exception($result['msg'] ?? '许可验证失败，请检查密钥是否正确');
                }
                $db = \Typecho\Db::get();
                $prefix = $db->getPrefix();
                $authTable = $prefix . 'mirai_auth';
                $exists = $db->fetchRow($db->select('id')->from($authTable)->limit(1));
                if ($exists) {
                    $db->query($db->update($authTable)->rows(['license' => $license, 'updated' => time()]));
                } else {
                    $db->query($db->insert($authTable)->rows(['license' => $license, 'created' => time(), 'updated' => time()]));
                }
                return [
                    'code' => 0,
                    'success' => true,
                    'msg' => '激活成功'
                ];
        }

        throw new Exception('未知API');
    }
}