<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_api_auth_register() {
    $options = Mirai_opt();
    $db = \Typecho\Db::get();
    
    // 检查用户中心和注册登录功能是否启用
    if (!Mirai_isUserCenterAuthEnabled($options)) {
        throw new Exception('用户中心功能已禁用');
    }
    
    if (!$options->allowRegister) throw new Exception('注册功能未开启');
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $mail = isset($_POST['mail']) ? trim($_POST['mail']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    
    if (empty($name) || empty($mail) || empty($password)) throw new Exception('请填写完整信息');
    if ($password !== $confirm) throw new Exception('两次密码不一致');
    if (strlen($password) < 6) throw new Exception('密码长度至少6位');
    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 20) throw new Exception('用户名长度需在2-20个字符之间');
    if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]+$/u', $name)) throw new Exception('用户名只能包含中文、字母、数字和下划线');
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) throw new Exception('邮箱格式不正确');
    
    // 验证码检查
    if ($options->enableEmailVerify === '1') {
        if (empty($code)) throw new Exception('请输入验证码');
        $check = Mirai_validateAuthCode($mail, $code);
        if (!$check['success']) {
            throw new Exception($check['msg']);
        }
        Mirai_clearAuthCodeSession();
    }
    
    $checkName = $db->fetchRow($db->select()->from('table.users')->where('name = ?', $name));
    if ($checkName) throw new Exception('用户名已存在');
    
    $checkMail = $db->fetchRow($db->select()->from('table.users')->where('mail = ?', $mail));
    if ($checkMail) throw new Exception('邮箱已存在');
    
    $hasher = new \Utils\PasswordHash(8, true);
    $data = [
        'name' => $name,
        'screenName' => $name,
        'mail' => $mail,
        'password' => $hasher->HashPassword($password),
        'created' => time(),
        'group' => 'subscriber'
    ];
    
    $db->query($db->insert('table.users')->rows($data));
    
    // 自动登录
    $user = Mirai_user();
    $user->login($name, $password, false);
    
    return ['code' => 0, 'msg' => '注册成功', 'success' => true, 'reload' => true];
}
