<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once __DIR__ . '/../functions/location.php';
// 引入 auth 通用工具函数
require_once __DIR__ . '/auth/utils.php';

function Mirai_handleApi($api) {
    $bufferLevel = ob_get_level();
    ob_start();
    // 处理 RSS Feed 请求 (无需 Session 和 Token)
    if ($api === 'feed' || $api === 'feed_atom') {
        Mirai_handleRssFeed($api);
    }

    // 开启 Session 用于存储验证码
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $db = \Typecho\Db::get();
    $options = Mirai_opt();

    $response = ['code' => -1, 'msg' => '未知错误', 'success' => false];

    try {

        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $isGetRequest = $requestMethod === 'GET';
        $noAjaxApis = ['pay_notify', 'pay_return'];
        if (!$isGetRequest && !in_array($api, $noAjaxApis)) {

            if (!isset($_POST['_ajax']) || $_POST['_ajax'] !== '1') {
                throw new Exception('非法访问方式');
            }
        }

        $token = '';
        if (isset($_POST['token']) && $_POST['token'] !== '') {
            $token = $_POST['token'];
        } elseif (isset($_GET['token']) && $_GET['token'] !== '') {
            $token = $_GET['token'];
        } elseif (isset($_COOKIE['mirai_api_token']) && $_COOKIE['mirai_api_token'] !== '') {
            $token = $_COOKIE['mirai_api_token'];
        }
        $security = \Widget\Security::alloc();
        // 验证 mirai_api 的 token
        if ($token !== $security->getToken('api')) {
            if (!in_array($api, ['get_views', 'pay_notify', 'pay_return'])) {
                throw new Exception('非法请求');
            }
        }
        switch ($api) {

            case 'auth_login':
                require_once __DIR__ . '/auth/login.php';
                $response = Mirai_api_auth_login();
                break;
            case 'auth_register':
                require_once __DIR__ . '/auth/register.php';
                $response = Mirai_api_auth_register();
                break;
            case 'auth_reset_step2':
                require_once __DIR__ . '/auth/reset.php';
                $response = Mirai_api_auth_reset_step2();
                break;
            case 'auth_validate_code':
                require_once __DIR__ . '/auth/reset.php';
                $response = Mirai_api_auth_validate_code();
                break;
            case 'auth_send_code':
                $result = Mirai_api_send_code_with_check();
                if (!$result['success']) throw new Exception($result['msg']);
                $response = $result;
                break;
            case 'ajaxAddViews':
                require_once __DIR__ . '/interact/views.php';
                $response = Mirai_ajaxAddViews();
                break;
            case 'get_views':
                require_once __DIR__ . '/interact/views.php';
                $response = Mirai_ajaxViews();
                break;
            case 'addlike':
                require_once __DIR__ . '/interact/like.php';
                $response = Mirai_ajaxLike();
                break;
            case 'unlike':
                require_once __DIR__ . '/interact/like.php';
                $response = Mirai_ajaxUnlike();
                break;
            case 'collect_article':
                require_once __DIR__ . '/interact/collect.php';
                $response = Mirai_ajaxToggleCollect();
                break;
            case 'send_test_mail':
                $result = Mirai_api_send_test_mail_with_check();
                if (!$result['success']) throw new Exception($result['msg']);
                $response = $result;
                break;
            case 'pay_create_order':
            case 'pay_query_order':
            case 'pay_mark_pending':
            case 'pay_delete_order':
            case 'pay_notify':
            case 'pay_return':
            case 'income_stats':
            case 'income_orders':
            case 'income_transfer':
            case 'balance_withdraw_create':
            case 'balance_withdraw_cancel':
                $response = Mirai_payInterfaceDispatch($api);
                break;
            case 'license_check_update':
            case 'license_verify':
            case 'license_activate':
                if (!function_exists('Mirai_handleLicenseApi')) {
                    throw new Exception('许可模块未加载');
                }
                $response = Mirai_handleLicenseApi($api);
                break;
            case 'core_repair_db':
                $user = Mirai_user();
                if (!$user->pass('administrator', true)) {
                    throw new Exception('权限不足');
                }
                $plugins = \Typecho\Plugin::export();
                if (!isset($plugins['activated']['MiraiCore'])) {
                    throw new Exception('MiraiCore插件未激活');
                }
                if (!class_exists('MiraiCore_Plugin')) {
                    throw new Exception('MiraiCore插件类未找到');
                }
                $changes = MiraiCore_Plugin::repairDatabaseStructures();
                if (empty($changes)) {
                    $message = '数据库结构已是最新，无需修复';
                } else {
                    $message = '数据库结构补齐完成';
                }
                $response = [
                    'code' => 0,
                    'success' => true,
                    'message' => $message,
                    'details' => $changes
                ];
                break;

            case 'get_users_vip':
                $currentUser = Mirai_user();
                if ($currentUser->hasLogin() && $currentUser->pass('administrator', true)) {
                    $uids = isset($_POST['uids']) ? explode(',', $_POST['uids']) : [];
                    $uids = array_map('intval', array_filter($uids));
                    if (!empty($uids)) {
                        $users = $db->fetchAll($db->select('uid', 'vip_level', 'vip_exp_date')->from('table.users')->where('uid IN ?', $uids));
                        $data = [];
                        foreach ($users as $u) {
                            $level = intval($u['vip_level'] ?? 0);
                            $data[$u['uid']] = [
                                'level' => $level,
                                'name' => $level > 0 ? (function_exists('Mirai_vipGetName') ? Mirai_vipGetName($level) : ['', '一级会员', '二级会员', '三级会员'][$level]) : '',
                                'exp' => $u['vip_exp_date'] ?? ''
                            ];
                        }
                        $response = ['success' => true, 'data' => $data];
                    } else {
                        $response = ['success' => false, 'msg' => 'No UIDs'];
                    }
                } else {
                    $response = ['success' => false, 'msg' => 'Unauthorized'];
                }
                break;

            case 'getBalances':
                $currentUser = Mirai_user();
                if ($currentUser->hasLogin() && $currentUser->pass('administrator', true)) {
                    $uids = isset($_POST['uids']) ? explode(',', $_POST['uids']) : [];
                    $uids = array_map('intval', array_filter($uids));
                    if (!empty($uids)) {
                        if (function_exists('Mirai_payAdminGetWallets')) {
                            $wallets = Mirai_payAdminGetWallets(1, count($uids), $uids);
                            $data = [];
                            foreach ($wallets['list'] as $wallet) {
                                $data[$wallet['uid']] = ['balance' => (float)$wallet['balance']];
                            }
                            $response = ['success' => true, 'data' => $data];
                        } else {
                            $response = ['success' => false, 'msg' => '支付功能未启用'];
                        }
                    } else {
                        $response = ['success' => true, 'data' => []];
                    }
                } else {
                    $response = ['success' => false, 'msg' => 'Unauthorized'];
                }
                break;

            case 'adjustBalance':
                $currentUser = Mirai_user();
                if ($currentUser->hasLogin() && $currentUser->pass('administrator', true)) {
                    $security = \Widget\Security::alloc();
                    $token = isset($_POST['_token']) ? $_POST['_token'] : '';
                    if ($token !== $security->getToken('mirai-balance-adjust')) {
                        $response = ['success' => false, 'msg' => '安全验证失败'];
                    } else {
                        $uid = isset($_POST['uid']) ? (int)$_POST['uid'] : 0;
                        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
                        $type = isset($_POST['type']) ? $_POST['type'] : 'add';
                        $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
                        
                        if ($uid <= 0) {
                            $response = ['success' => false, 'msg' => '用户ID无效'];
                        } elseif ($amount <= 0 || !is_finite($amount)) {
                            $response = ['success' => false, 'msg' => '金额无效'];
                        } elseif ($amount > 999999.99) {
                            $response = ['success' => false, 'msg' => '金额超出限制'];
                        } elseif (function_exists('Mirai_payAdminAdjustBalance')) {
                            $actualAmount = $type === 'add' ? $amount : -$amount;
                            $result = Mirai_payAdminAdjustBalance($uid, $actualAmount, 'admin_adjust', $remark ?: '管理员调整');
                            if (!empty($result['success'])) {
                                $response = ['success' => true, 'msg' => ($type === 'add' ? '增加' : '扣除') . '余额成功'];
                            } else {
                                $response = ['success' => false, 'msg' => $result['msg'] ?? '操作失败'];
                            }
                        } else {
                            $response = ['success' => false, 'msg' => '支付功能未启用'];
                        }
                    }
                } else {
                    $response = ['success' => false, 'msg' => 'Unauthorized'];
                }
                break;

            default:
                throw new Exception('未知API');
        }
    } catch (Throwable $e) {
        $response = ['code' => -1, 'msg' => $e->getMessage(), 'success' => false];
    }

    while (ob_get_level() > $bufferLevel) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}