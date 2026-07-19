<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_isUserCenterAuthEnabled($options = null) {
    if ($options === null) {
        $options = Mirai_opt();
    }
    if (!Mirai_featureEnabled('user_center')) {
        return false;
    }
    return !isset($options->enableFrontendLogin) || $options->enableFrontendLogin === '1';
}

function Mirai_clearAuthCodeSession() {
    unset($_SESSION['mirai_auth_code'], $_SESSION['mirai_auth_mail'], $_SESSION['mirai_auth_time']);
}

function Mirai_validateAuthCode($mail, $code, $ttl = 600) {
    if (!isset($_SESSION['mirai_auth_code']) || (string)$_SESSION['mirai_auth_code'] !== (string)$code || $_SESSION['mirai_auth_mail'] !== $mail) {
        return ['success' => false, 'msg' => '验证码错误或已失效'];
    }
    if (!isset($_SESSION['mirai_auth_time']) || time() - $_SESSION['mirai_auth_time'] > $ttl) {
        Mirai_clearAuthCodeSession();
        return ['success' => false, 'msg' => '验证码已过期，请重新获取'];
    }
    return ['success' => true];
}