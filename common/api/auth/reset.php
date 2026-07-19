<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_api_auth_reset_step2() {
    $options = Mirai_opt();
    $db = \Typecho\Db::get();
    
    // 检查用户中心和注册登录功能是否启用
    if (!Mirai_isUserCenterAuthEnabled($options)) {
        throw new Exception('用户中心功能已禁用');
    }
    
    $mail = isset($_POST['mail']) ? trim($_POST['mail']) : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    
    if (empty($mail) || empty($code) || empty($password)) throw new Exception('信息不完整');
    if ($password !== $confirm) throw new Exception('两次密码不一致');
    
    $check = Mirai_validateAuthCode($mail, $code);
    if (!$check['success']) {
        throw new Exception($check['msg']);
    }

    $user = $db->fetchRow($db->select()->from('table.users')->where('mail = ?', $mail));
    if (!$user) throw new Exception('用户不存在');

    $hasher = new \Utils\PasswordHash(8, true);
    $newPassword = $hasher->HashPassword($password);

    $db->query($db->update('table.users')->rows(['password' => $newPassword])->where('uid = ?', $user['uid']));

    // 清除所有验证码相关 Session
    Mirai_clearAuthCodeSession();
    return ['code' => 0, 'msg' => '密码重置成功，请登录', 'success' => true];
}

function Mirai_api_auth_validate_code() {
    $mail = isset($_POST['mail']) ? trim($_POST['mail']) : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';

    if (empty($mail) || empty($code)) {
        throw new Exception('信息不完整');
    }

    $check = Mirai_validateAuthCode($mail, $code);
    if (!$check['success']) {
        throw new Exception($check['msg']);
    }

    return ['code' => 0, 'msg' => '验证成功', 'success' => true];
}