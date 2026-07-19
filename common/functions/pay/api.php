<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_payInterfaceDispatch($api) {
    $db = \Typecho\Db::get();
    $user = Mirai_user();

    switch ($api) {
        case 'pay_create_order':
            return Mirai_payApiCreateOrder($db, $user);
        case 'pay_query_order':
            return Mirai_payApiQueryOrder($db, $user);
        case 'pay_mark_pending':
            return Mirai_payApiMarkPending($db, $user);
        case 'pay_delete_order':
            return Mirai_payApiDeleteOrder($db, $user);
        case 'pay_notify':
            return Mirai_payApiHandleNotify($db);
        case 'pay_return':
            return Mirai_payApiHandleReturn($db);
        case 'income_stats':
            return Mirai_payApiIncomeStats($db, $user);
        case 'income_orders':
            return Mirai_payApiIncomeOrders($db, $user);
        case 'income_transfer':
            return Mirai_payApiIncomeTransfer($db, $user);
        case 'balance_withdraw_create':
            return Mirai_payApiBalanceWithdrawCreate($db, $user);
        case 'balance_withdraw_cancel':
            return Mirai_payApiBalanceWithdrawCancel($db, $user);
        default:
            return ['code' => -1, 'msg' => '未知支付API', 'success' => false];
    }
}

function Mirai_payApiHandleNotify($db) {
    $gateway = isset($_GET['gateway']) ? trim((string)$_GET['gateway']) : '';

    if ($gateway === 'f2fpay') {
        require_once dirname(__FILE__, 3) . '/payment/f2fpay/notify.php';
        echo Mirai_f2fpay_handleNotify();
        exit;
    } elseif ($gateway === 'epay') {
        require_once dirname(__FILE__, 3) . '/payment/epay/notify.php';
        echo Mirai_epay_handleNotify();
        exit;
    }

    return ['code' => -1, 'msg' => '无效的网关', 'success' => false];
}

function Mirai_payApiHandleReturn($db) {
    $gateway = isset($_GET['gateway']) ? trim((string)$_GET['gateway']) : '';

    if ($gateway === 'epay') {
        require_once dirname(__FILE__, 3) . '/payment/epay/return.php';
        exit;
    }

    header('Location: ' . rtrim(Mirai_opt()->siteUrl, '/'));
    exit;
}

function Mirai_payApiCreateOrder($db, $user) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['code' => -1, 'msg' => '非法请求', 'success' => false];
    }

    $ip = Mirai_getClientIp();
    $rateLimitKey = 'mirai_pay_create_order_limit_' . md5($ip);
    if (!isset($_SESSION[$rateLimitKey])) {
        $_SESSION[$rateLimitKey] = [];
    }
    $now = time();
    $_SESSION[$rateLimitKey] = array_filter($_SESSION[$rateLimitKey], function($timestamp) use ($now) {
        return $timestamp > $now - 60;
    });
    if (count($_SESSION[$rateLimitKey]) >= 10) {
        return ['code' => -1, 'msg' => '请求过于频繁，请稍后再试', 'success' => false];
    }
    $_SESSION[$rateLimitKey][] = $now;

    Mirai_payAutoExpireOrders();

    $cid = isset($_POST['cid']) ? (int)$_POST['cid'] : 0;
    $orderType = isset($_POST['order_type']) ? trim((string)$_POST['order_type']) : 'read';
    $paymentMethod = isset($_POST['payment_method']) ? trim((string)$_POST['payment_method']) : 'balance';
    $amount = isset($_POST['amount']) ? Mirai_payNormalizeAmount($_POST['amount']) : 0;

    $uid = (int)$user->uid;

    if (!in_array($orderType, ['read', 'partial', 'recharge', 'vip'], true)) {
        return ['code' => -1, 'msg' => '订单类型错误', 'success' => false];
    }

    if ($orderType === 'recharge') {
        $limits = Mirai_payRechargeLimit();
        if ($amount < $limits['min'] || $amount > $limits['max']) {
            return ['code' => -1, 'msg' => '充值金额需在 ' . $limits['min'] . ' - ' . $limits['max'] . ' 之间', 'success' => false];
        }
        if ($uid <= 0) {
            return ['code' => -1, 'msg' => '请先登录', 'success' => false];
        }
    } elseif ($orderType === 'vip') {
        $vipLevel = isset($_POST['vip_level']) ? (int)$_POST['vip_level'] : 1;
        $vipTime = isset($_POST['vip_time']) ? (int)$_POST['vip_time'] : 30;
        $vipPurchaseType = isset($_POST['vip_purchase_type']) ? trim((string)$_POST['vip_purchase_type']) : '';
        
        $vipValidation = Mirai_vipValidateOrderParams($uid, $vipLevel, $vipTime, $vipPurchaseType);
        
        if (!$vipValidation['valid']) {
            return ['code' => -1, 'msg' => $vipValidation['error'], 'success' => false];
        }
        
        $amount = $vipValidation['amount'];
        $vipMeta = $vipValidation['meta'];
    } else {
        if ($cid <= 0) {
            return ['code' => -1, 'msg' => '文章ID错误', 'success' => false];
        }
        $settings = Mirai_payPostSettings($cid);
        if (!Mirai_payAvailableForPost($settings)) {
            return ['code' => -1, 'msg' => '该文章不支持付费', 'success' => false];
        }
        $amount = $settings['price'];
        $post = $db->fetchRow($db->select('authorId', 'title')->from('table.contents')->where('cid = ?', $cid)->limit(1));

        if ($uid > 0) {
            if ($post && (int)$post['authorId'] === $uid) {
                return ['code' => -1, 'msg' => '作者无需购买自己的文章', 'success' => false];
            }
        }

        if (Mirai_payHasPaid($cid, $uid)) {
            return ['code' => 0, 'msg' => '已购买', 'success' => true, 'paid' => true];
        }
    }

    if ($paymentMethod === 'balance') {
        if ($uid <= 0) {
            return ['code' => -1, 'msg' => '请先登录', 'success' => false];
        }
        $wallet = Mirai_payGetWallet($uid);
        if ((float)$wallet['balance'] < $amount) {
            return ['code' => -1, 'msg' => '余额不足', 'success' => false];
        }
    }

    $authorId = 0;
    $postTitle = '';
    if ($cid > 0) {
        if (!isset($post)) {
            $post = $db->fetchRow($db->select('authorId', 'title')->from('table.contents')->where('cid = ?', $cid)->limit(1));
        }
        if ($post) {
            $authorId = (int)$post['authorId'];
            $postTitle = $post['title'] ?? '';
        }
    }

    $meta = [
        'ip' => Mirai_getClientIp(),
        'title' => $orderType === 'recharge' ? '余额充值' : ($orderType === 'vip' ? '购买会员' : '')
    ];

    if ($orderType === 'vip') {
        $meta = array_merge($meta, $vipMeta);
    }

    if ($cid > 0 && $orderType !== 'recharge' && $orderType !== 'vip' && $postTitle !== '') {
        $meta['title'] = mb_substr($postTitle, 0, 50);
    }

    $productTitle = '';
    if ($orderType === 'recharge') {
        $productTitle = '余额充值';
    } elseif ($orderType === 'vip') {
        $productTitle = $meta['title'];
    } elseif ($postTitle !== '') {
        $productTitle = mb_substr($postTitle, 0, 100);
    }

    $orderNo = Mirai_payCreateOrder([
        'uid' => $uid,
        'cid' => $cid,
        'author_id' => $authorId,
        'order_type' => $orderType,
        'product_title' => $productTitle,
        'payment_method' => $paymentMethod,
        'amount' => $amount,
        'ip_address' => Mirai_getClientIp(),
        'meta' => $meta
    ]);

    if ($orderNo === '') {
        return ['code' => -1, 'msg' => '创建订单失败', 'success' => false];
    }

    if ($paymentMethod === 'balance') {
        if (!Mirai_payAdjustBalance($uid, -$amount, 'purchase', '购买：' . ($meta['title'] ?? '订单'), $orderNo)) {
            return ['code' => -1, 'msg' => '扣款失败', 'success' => false];
        }
        Mirai_payMarkOrderPaid($orderNo);
        return [
            'code' => 0,
            'msg' => '支付成功',
            'success' => true,
            'paid' => true,
            'order_no' => $orderNo
        ];
    }

    $gateway = Mirai_payChannelGateway($paymentMethod);
    if ($gateway === '') {
        return ['code' => -1, 'msg' => '支付通道不可用', 'success' => false];
    }

    $order = Mirai_payGetOrder($orderNo);
    $paymentDir = dirname(__FILE__, 3) . '/payment/';

    if ($gateway === 'epay') {
        require_once $paymentDir . 'epay/pay.php';
        $result = Mirai_epay_pay($order, $paymentMethod);
    } elseif ($gateway === 'f2fpay') {
        require_once $paymentDir . 'f2fpay/pay.php';
        $result = Mirai_f2fpay_pay($order);
    } else {
        return ['code' => -1, 'msg' => '支付通道未实现', 'success' => false];
    }

    if (!$result['success']) {
        return ['code' => -1, 'msg' => $result['message'] ?? '支付发起失败', 'success' => false];
    }

    $queryToken = Mirai_api_payIssueQueryToken($orderNo);
    $redirectUrl = Mirai_api_payOrderTargetUrl($order);

    $payUrl = $result['content'] ?? '';
    $payType = $result['type'] ?? 'redirect';
    
    if ($payType === 'redirect' && isset($result['url'])) {
        $payUrl = '';
        $redirectUrl = $result['url'];
    }

    return [
        'code' => 0,
        'msg' => '订单创建成功',
        'success' => true,
        'order_no' => $orderNo,
        'pay_url' => $payUrl,
        'pay_type' => $payType,
        'expire_seconds' => isset($result['expire']) ? (int)$result['expire'] : Mirai_payOrderExpireSeconds(),
        'query_token' => $queryToken,
        'url' => $redirectUrl,
        'order' => Mirai_api_payOrderDetail($order)
    ];
}

function Mirai_payApiQueryOrder($db, $user) {
    $ip = Mirai_getClientIp();
    $orderNo = isset($_POST['order_no']) ? trim((string)$_POST['order_no']) : '';
    $rateLimitKey = 'mirai_pay_query_order_limit_' . md5($ip . $orderNo);
    if (!isset($_SESSION[$rateLimitKey])) {
        $_SESSION[$rateLimitKey] = [];
    }
    $now = time();
    $_SESSION[$rateLimitKey] = array_filter($_SESSION[$rateLimitKey], function($timestamp) use ($now) {
        return $timestamp > $now - 60;
    });
    if (count($_SESSION[$rateLimitKey]) >= 20) {
        return ['code' => -1, 'msg' => '查询过于频繁，请稍后再试', 'success' => false];
    }
    $_SESSION[$rateLimitKey][] = $now;

    $queryToken = isset($_POST['query_token']) ? trim((string)$_POST['query_token']) : '';

    if (!Mirai_payValidOrderNo($orderNo)) {
        return ['code' => -1, 'msg' => '订单号格式错误', 'success' => false];
    }

    $order = Mirai_payGetOrder($orderNo);
    if (!$order) {
        return ['code' => -1, 'msg' => '订单不存在', 'success' => false];
    }

    $uid = (int)$user->uid;
    $orderUid = (int)$order['uid'];

    if ($uid > 0 && $orderUid > 0 && $uid !== $orderUid) {
        return ['code' => -1, 'msg' => '无权查询该订单', 'success' => false];
    }

    if ($orderUid <= 0) {
        if ($queryToken !== '' && !Mirai_api_payCheckQueryToken($order, $queryToken)) {
            return ['code' => -1, 'msg' => '查询令牌无效', 'success' => false];
        }
        $guestToken = Mirai_payGuestToken();
        if ((string)$order['guest_token'] !== $guestToken) {
            return ['code' => -1, 'msg' => '无权查询该订单', 'success' => false];
        }
    }

    $order = Mirai_api_payTrySyncOrderPaid($order);
    $paid = (string)$order['status'] === 'paid';
    $redirectUrl = Mirai_api_payOrderTargetUrl($order);

    return [
        'code' => 0,
        'success' => true,
        'paid' => $paid,
        'status' => (string)$order['status'],
        'order_no' => $orderNo,
        'url' => $redirectUrl,
        'order' => Mirai_api_payOrderDetail($order)
    ];
}

function Mirai_payApiMarkPending($db, $user) {
    $orderNo = isset($_POST['order_no']) ? trim((string)$_POST['order_no']) : '';
    if (!Mirai_payValidOrderNo($orderNo)) {
        return ['code' => -1, 'msg' => '订单号格式错误', 'success' => false];
    }

    $order = Mirai_payGetOrder($orderNo);
    if (!$order) {
        return ['code' => -1, 'msg' => '订单不存在', 'success' => false];
    }

    try {
        Mirai_api_payEnsureOrderOwner($order);
    } catch (Exception $e) {
        return ['code' => -1, 'msg' => $e->getMessage(), 'success' => false];
    }

    if (Mirai_payMarkOrderPending($orderNo)) {
        return ['code' => 0, 'msg' => '已重新激活', 'success' => true];
    } else {
        return ['code' => -1, 'msg' => '操作失败，订单可能不是已关闭状态', 'success' => false];
    }
}

function Mirai_payApiDeleteOrder($db, $user) {
    $orderNo = isset($_POST['order_no']) ? trim((string)$_POST['order_no']) : '';
    if (!Mirai_payValidOrderNo($orderNo)) {
        return ['code' => -1, 'msg' => '订单号格式错误', 'success' => false];
    }

    $order = Mirai_payGetOrder($orderNo);
    if (!$order) {
        return ['code' => -1, 'msg' => '订单不存在', 'success' => false];
    }

    try {
        Mirai_api_payEnsureOrderOwner($order);
    } catch (Exception $e) {
        return ['code' => -1, 'msg' => $e->getMessage(), 'success' => false];
    }

    if (!in_array($order['status'], ['pending', 'closed'], true)) {
        return ['code' => -1, 'msg' => '仅支持删除未支付或已关闭订单', 'success' => false];
    }

    $ordersTable = Mirai_payTable('orders');
    $query = $db->delete($ordersTable)->where('order_no = ?', $orderNo);
    if ((int)$order['uid'] > 0) {
        $query->where('uid = ?', (int)$order['uid']);
    } else {
        $query->where('guest_token = ?', (string)$order['guest_token']);
    }
    $db->query($query);

    return ['code' => 0, 'msg' => '订单已删除', 'success' => true];
}

function Mirai_api_payOrderTargetUrl($order) {
    $target = rtrim(Mirai_opt()->siteUrl, '/');
    if (!is_array($order)) {
        return $target;
    }
    if ((int)$order['cid'] > 0) {
        $db = \Typecho\Db::get();
        $post = $db->fetchRow($db->select('cid', 'slug', 'title')->from('table.contents')->where('cid = ?', (int)$order['cid'])->limit(1));
        if ($post) {
            return \Typecho\Router::url('post', $post, Mirai_opt()->index);
        }
    }
    if ((int)$order['uid'] > 0) {
        return \Typecho\Common::url('/user/orders', Mirai_opt()->index);
    }
    return $target;
}

function Mirai_api_payAmountMatched($order, $notifyAmount) {
    if (!is_array($order) || $notifyAmount === null) {
        return false;
    }
    $orderAmountInCents = (int)round((float)$order['amount'] * 100);
    $paidAmountInCents = (int)round((float)$notifyAmount * 100);
    return $orderAmountInCents === $paidAmountInCents;
}

function Mirai_api_payCanSyncOrderNow($orderNo) {
    $orderNo = trim((string)$orderNo);
    if ($orderNo === '') {
        return false;
    }
    if (!isset($_SESSION['mirai_pay_sync_times']) || !is_array($_SESSION['mirai_pay_sync_times'])) {
        $_SESSION['mirai_pay_sync_times'] = [];
    }
    $times = $_SESSION['mirai_pay_sync_times'];
    $now = time();
    foreach ($times as $k => $v) {
        if (!is_numeric($v) || (int)$v < $now - 7200) {
            unset($times[$k]);
        }
    }
    $last = isset($times[$orderNo]) ? (int)$times[$orderNo] : 0;
    if ($last > 0 && ($now - $last) < 4) {
        $_SESSION['mirai_pay_sync_times'] = $times;
        return false;
    }
    $times[$orderNo] = $now;
    $_SESSION['mirai_pay_sync_times'] = $times;
    return true;
}

function Mirai_api_payTrySyncOrderPaid($order) {
    if (!is_array($order)) {
        return $order;
    }
    if (isset($order['status']) && (string)$order['status'] === 'paid') {
        return $order;
    }
    $method = isset($order['payment_method']) ? trim((string)$order['payment_method']) : '';
    if ($method !== 'alipay' || Mirai_payChannelGateway('alipay') !== 'f2fpay') {
        return $order;
    }
    $orderNo = isset($order['order_no']) ? trim((string)$order['order_no']) : '';
    if ($orderNo === '' || !Mirai_api_payCanSyncOrderNow($orderNo)) {
        return $order;
    }
    $paymentDir = dirname(__FILE__, 3) . '/payment/';
    require_once $paymentDir . 'f2fpay/pay.php';
    $query = Mirai_f2fpay_query($orderNo);
    if (!is_array($query) || empty($query['success']) || empty($query['paid'])) {
        return $order;
    }
    $amount = isset($query['total_amount']) ? $query['total_amount'] : null;
    if (!Mirai_api_payAmountMatched($order, $amount)) {
        return $order;
    }
    $tradeNo = isset($query['trade_no']) ? (string)$query['trade_no'] : '';
    if (!Mirai_payMarkOrderPaid($orderNo, $tradeNo)) {
        return $order;
    }
    $latest = Mirai_payGetOrder($orderNo);
    return is_array($latest) ? $latest : $order;
}

function Mirai_api_payIssueQueryToken($orderNo) {
    $orderNo = trim((string)$orderNo);
    if ($orderNo === '') {
        return '';
    }

    try {
        $token = bin2hex(random_bytes(16));
    } catch (Throwable $e) {
        $token = md5($orderNo . uniqid('mirai_pay_', true) . mt_rand(10000, 99999));
    }

    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');
    $db->query($db->update($ordersTable)->rows(['query_token' => $token])->where('order_no = ?', $orderNo));

    return $token;
}

function Mirai_api_payCheckQueryToken($order, $token) {
    $token = trim((string)$token);
    if (empty($order) || $token === '') {
        return false;
    }

    if (Mirai_payIsOrderExpired($order)) {
        return false;
    }

    return isset($order['query_token']) && hash_equals((string)$order['query_token'], $token);
}

function Mirai_api_payEnsureOrderOwner($order) {
    if (!is_array($order)) {
        throw new Exception('订单不存在');
    }
    $user = Mirai_user();
    $uid = $user->hasLogin() ? (int)$user->uid : 0;
    $orderUid = (int)$order['uid'];
    if ($orderUid > 0) {
        if ($uid <= 0 || $uid !== $orderUid) {
            throw new Exception('无权查看该订单');
        }
        return;
    }
    $guestToken = Mirai_payGuestToken();
    if ((string)$order['guest_token'] !== $guestToken) {
        throw new Exception('无权查看该订单');
    }
}

function Mirai_payAutoExpireOrders() {
    $db = \Typecho\Db::get();
    $prefix = $db->getPrefix();
    $option = $db->fetchRow($db->select('value')->from($prefix . 'options')->where('name = ?', 'mirai_pay_last_expire_check')->where('user = ?', 0));
    $lastCheck = $option ? (int)$option['value'] : 0;

    $now = time();
    $expireSeconds = Mirai_payOrderExpireSeconds();
    $checkInterval = max(300, min(3600, $expireSeconds / 2));

    if (($now - $lastCheck) < $checkInterval) {
        return;
    }

    try {
        $ordersTable = Mirai_payTable('orders');
        $expireTime = $now - $expireSeconds;

        $db->query($db->update($ordersTable)
            ->rows(['status' => 'closed'])
            ->where('status = ?', 'pending')
            ->where('created < ?', $expireTime)
        );

        if ($lastCheck > 0) {
            $db->query($db->update($prefix . 'options')->rows(['value' => $now])->where('name = ?', 'mirai_pay_last_expire_check')->where('user = ?', 0));
        } else {
            $db->query($db->insert($prefix . 'options')->rows(['name' => 'mirai_pay_last_expire_check', 'user' => 0, 'value' => $now]));
        }
    } catch (Exception $e) {
    }
}

function Mirai_api_payOrderDetail($order) {
    if (!is_array($order)) {
        return [];
    }
    $created = isset($order['created']) ? (int)$order['created'] : 0;
    $paidAt = isset($order['paid_at']) ? (int)$order['paid_at'] : 0;
    $orderType = isset($order['order_type']) ? (string)$order['order_type'] : '';
    $paymentMethod = isset($order['payment_method']) ? (string)$order['payment_method'] : '';
    $iconMap = [
        'wechat' => 'wechat-pay.svg',
        'alipay' => 'alipay.svg',
        'qq' => 'QQ-Pay.svg',
        'balance' => 'balance.svg'
    ];
    $iconFile = isset($iconMap[$paymentMethod]) ? $iconMap[$paymentMethod] : '';
    $iconUrl = $iconFile ? (Mirai_getThemeUrl() . '/assets/images/' . $iconFile) : '';
    $incomePrice = isset($order['income_price']) ? (float)$order['income_price'] : 0;
    $incomeStatus = isset($order['income_status']) ? (int)$order['income_status'] : 0;
    $displayStatus = Mirai_payGetOrderDisplayStatus($order);
    return [
        'order_no' => isset($order['order_no']) ? (string)$order['order_no'] : '',
        'title' => Mirai_payOrderTitle($order),
        'order_type' => $orderType,
        'order_type_label' => Mirai_payOrderTypeLabel($orderType),
        'payment_method' => $paymentMethod,
        'payment_method_label' => Mirai_payMethodLabel($paymentMethod),
        'payment_method_icon' => $iconUrl,
        'amount' => number_format((float)$order['amount'], 2, '.', ''),
        'income_price' => number_format($incomePrice, 2, '.', ''),
        'income_status' => $incomeStatus,
        'income_status_label' => Mirai_payIncomeStatusLabel($incomeStatus),
        'status' => $displayStatus,
        'status_label' => Mirai_payOrderStatusLabel($displayStatus),
        'created' => $created,
        'created_text' => $created > 0 ? (new \Typecho\Date($created))->format('Y-m-d H:i:s') : '',
        'paid_at' => $paidAt,
        'paid_at_text' => $paidAt > 0 ? (new \Typecho\Date($paidAt))->format('Y-m-d H:i:s') : '',
        'ip' => Mirai_payOrderIp($order),
        'trade_no' => isset($order['trade_no']) ? (string)$order['trade_no'] : ''
    ];
}