<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_api_auth_login() {
    $options = Mirai_opt();
    
    // 检查用户中心和注册登录功能是否启用
    if (!Mirai_isUserCenterAuthEnabled($options)) {
        throw new Exception('用户中心功能已禁用');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('非法请求');
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? $_POST['remember'] : 0;
    
    if (empty($name) || empty($password)) throw new Exception('用户名或密码不能为空');

    $max_attempts = 5;
    $lockout_time = 15 * 60;
    $session_key = 'login_attempts_' . md5($name);
    $ip = Mirai_getClientIp();
    $ip_rate_key = 'login_ip_rate_' . md5($ip);

    if (!isset($_SESSION[$ip_rate_key])) {
        $_SESSION[$ip_rate_key] = ['count' => 0, 'time' => time()];
    }
    $ipRate = $_SESSION[$ip_rate_key];
    if (time() - $ipRate['time'] > 900) {
        $_SESSION[$ip_rate_key] = ['count' => 0, 'time' => time()];
    } elseif ($ipRate['count'] >= 20) {
        throw new Exception('请求过于频繁，请稍后再试');
    }

    if (isset($_SESSION[$session_key])) {
        $attempts = $_SESSION[$session_key];
        if (time() - $attempts['time'] > $lockout_time) {
            unset($_SESSION[$session_key]);
        } elseif ($attempts['count'] >= $max_attempts) {
            throw new Exception('登录失败次数过多，请15分钟后再试');
        }
    }
    
    $user = Mirai_user();
    if ($user->login($name, $password, false)) {
        if (isset($_SESSION[$session_key])) {
            unset($_SESSION[$session_key]);
        }
        if ($remember) {
            \Typecho\Cookie::set('__typecho_remember_user', 1, $options->time + 31536000);
        }
        
        if (function_exists('Mirai_checkAndSendVipExpireNotify')) {
            Mirai_checkAndSendVipExpireNotify($user->uid);
        }
        
        return ['code' => 0, 'msg' => '登录成功', 'success' => true, 'reload' => true];
    } else {
        if (!isset($_SESSION[$session_key])) {
            $_SESSION[$session_key] = ['count' => 1, 'time' => time()];
        } else {
            $_SESSION[$session_key]['count']++;
            $_SESSION[$session_key]['time'] = time();
        }
        if (isset($_SESSION[$ip_rate_key])) {
            $_SESSION[$ip_rate_key]['count']++;
        }
        throw new Exception('用户名或密码错误');
    }
}
