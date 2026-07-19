<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_payCreateOrder($payload) {
    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');

    if (!Mirai_payDbCheck()) {
        return '';
    }
    $orderNo = '';
    for ($i = 0; $i < 5; $i++) {
        $candidate = Mirai_payGenerateOrderNo();
        $exists = $db->fetchRow($db->select('id')->from($ordersTable)->where('order_no = ?', $candidate)->limit(1));
        if (empty($exists)) {
            $orderNo = $candidate;
            break;
        }
    }
    if ($orderNo === '') {
        return '';
    }
    $uid = isset($payload['uid']) ? (int)$payload['uid'] : 0;
    $cid = isset($payload['cid']) ? (int)$payload['cid'] : 0;
    $amount = isset($payload['amount']) ? (float)$payload['amount'] : 0;
    $orderType = isset($payload['order_type']) ? (string)$payload['order_type'] : 'read';
    $paymentMethod = isset($payload['payment_method']) ? (string)$payload['payment_method'] : 'balance';
    $status = isset($payload['status']) ? trim((string)$payload['status']) : 'pending';
    if (!in_array($status, ['pending', 'paid', 'closed'], true)) {
        $status = 'pending';
    }
    $guestToken = $uid > 0 ? '' : Mirai_payGuestToken();
    $productTitle = isset($payload['product_title']) ? trim((string)$payload['product_title']) : '';
    $ipAddress = isset($payload['ip_address']) ? trim((string)$payload['ip_address']) : Mirai_getClientIp();
    $db->query($db->insert($ordersTable)->rows([
        'order_no' => $orderNo,
        'uid' => $uid,
        'cid' => $cid,
        'author_id' => isset($payload['author_id']) ? (int)$payload['author_id'] : 0,
        'order_type' => $orderType,
        'product_title' => $productTitle,
        'payment_method' => $paymentMethod,
        'amount' => round($amount, 2),
        'income_price' => 0,
        'income_status' => 0,
        'status' => $status,
        'trade_no' => '',
        'ip_address' => $ipAddress,
        'guest_token' => $guestToken,
        'meta' => isset($payload['meta']) ? json_encode($payload['meta'], JSON_UNESCAPED_UNICODE) : '',
        'created' => time(),
        'paid_at' => $status === 'paid' ? time() : 0
    ]));
    return $orderNo;
}

function Mirai_payGetOrder($orderNo) {
    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');

    if (!Mirai_payDbCheck()) {
        return null;
    }
    return $db->fetchRow($db->select()->from($ordersTable)->where('order_no = ?', $orderNo)->limit(1));
}

function Mirai_payOrderTitle($order) {
    if (is_array($order) && isset($order['product_title']) && $order['product_title'] !== '') {
        return (string)$order['product_title'];
    }
    $meta = Mirai_payOrderMeta($order);
    if (!empty($meta['title'])) {
        return (string)$meta['title'];
    }
    $orderType = is_array($order) && isset($order['order_type']) ? (string)$order['order_type'] : '';
    return Mirai_payOrderTypeLabel($orderType);
}

function Mirai_payOrderMeta($order) {
    if (!is_array($order)) {
        return [];
    }
    if (!isset($order['meta'])) {
        return [];
    }
    $raw = (string)$order['meta'];
    if ($raw === '') {
        return [];
    }
    $meta = json_decode($raw, true);
    if (!is_array($meta)) {
        return [];
    }
    return $meta;
}

function Mirai_payOrderIp($order) {
    if (is_array($order) && isset($order['ip_address']) && $order['ip_address'] !== '') {
        return (string)$order['ip_address'];
    }
    $meta = Mirai_payOrderMeta($order);
    return isset($meta['ip']) ? (string)$meta['ip'] : '';
}

function Mirai_payCommissionRate($order) {
    $globalRate = (float)Mirai_payGetOption('payCommissionRate', '0');
    $globalRate = max(0, min(100, $globalRate));
    $cid = isset($order['cid']) ? (int)$order['cid'] : 0;
    if ($cid <= 0) {
        return $globalRate;
    }
    $settings = Mirai_payPostSettings($cid);
    if (isset($settings['commission_rate']) && $settings['commission_rate'] >= 0) {
        return max(0, min(100, (float)$settings['commission_rate']));
    }
    return $globalRate;
}

function Mirai_payFinalizeAccess($order) {
    $cid = isset($order['cid']) ? (int)$order['cid'] : 0;
    if ($cid > 0) {
        Mirai_paySetCookie(Mirai_payCookieKey($cid), '1', time() + 31536000);
        $_COOKIE[Mirai_payCookieKey($cid)] = '1';
    }
}

function Mirai_payMarkOrderPaid($orderNo, $tradeNo = '') {
    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');

    if (!Mirai_payDbCheck()) {
        return false;
    }

    $orderLockKey = 'mirai_pay_order_' . $orderNo;
    if (!Mirai_payAcquireLock($orderLockKey, 10)) {
        return false;
    }

    try {
        $order = $db->fetchRow($db->select()->from($ordersTable)->where('order_no = ?', $orderNo)->limit(1));
        if (!$order) {
            return false;
        }

        if ($order['status'] === 'paid') {
            Mirai_payFinalizeAccess($order);
            return true;
        }

        if ($order['status'] !== 'pending') {
            return false;
        }

        if (Mirai_payIsOrderExpired($order)) {
            Mirai_payLog("订单已过期，拒绝支付标记 订单:{$orderNo}", 'warn');
            return false;
        }

        $orderType = isset($order['order_type']) ? (string)$order['order_type'] : '';
        $cid = isset($order['cid']) ? (int)$order['cid'] : 0;
        $uid = isset($order['uid']) ? (int)$order['uid'] : 0;

        if (in_array($orderType, ['read', 'partial'], true) && $cid > 0) {
            if ($uid > 0) {
                $existingOrder = $db->fetchRow($db->select('id')->from($ordersTable)
                    ->where('cid = ?', $cid)
                    ->where('uid = ?', $uid)
                    ->where('status = ?', 'paid')
                    ->where('order_no != ?', $orderNo)
                    ->limit(1));
                if ($existingOrder) {
                    Mirai_payLog("用户已购买该文章，拒绝重复支付 订单:{$orderNo} cid:{$cid} uid:{$uid}", 'warn');
                    return false;
                }
            } else {
                $guestToken = Mirai_payGuestToken();
                $currentIp = Mirai_getClientIp();
                if ($guestToken !== '' && $currentIp !== '') {
                    $existingOrders = $db->fetchAll($db->select('meta')->from($ordersTable)
                        ->where('cid = ?', $cid)
                        ->where('guest_token = ?', $guestToken)
                        ->where('status = ?', 'paid')
                        ->where('order_no != ?', $orderNo));
                    foreach ($existingOrders as $existingOrder) {
                        $meta = Mirai_payOrderMeta($existingOrder);
                        $orderIp = isset($meta['ip']) ? (string)$meta['ip'] : '';
                        if ($orderIp !== '' && $orderIp === $currentIp) {
                            Mirai_payLog("游客已购买该文章，拒绝重复支付 订单:{$orderNo} cid:{$cid} guest_token:{$guestToken}", 'warn');
                            return false;
                        }
                    }
                }
            }
        }

        $db->query('BEGIN');

        $tradeNo = trim((string)$tradeNo);
        
        // 计算分成金额（如果适用）
        $incomePrice = 0;
        $incomeStatus = 0;
        if (in_array($order['order_type'], ['read', 'partial'], true)) {
            $authorId = (int)$order['author_id'];
            $buyerId = (int)$order['uid'];
            if ($authorId > 0 && $authorId !== $buyerId) {
                $rate = Mirai_payCommissionRate($order);
                if ($rate > 0) {
                    $commission = round(((float)$order['amount']) * $rate / 100, 2);
                    if ($commission > 0) {
                        $incomePrice = $commission;
                        $incomeStatus = 0;
                    }
                }
            }
        }
        
        // 原子更新：同时更新订单状态和分成金额
        $updateData = [
            'status' => 'paid',
            'trade_no' => $tradeNo,
            'paid_at' => time(),
            'income_price' => $incomePrice,
            'income_status' => $incomeStatus
        ];
        
        $result = $db->query($db->update($ordersTable)
            ->rows($updateData)
            ->where('order_no = ?', $orderNo)
            ->where('status = ?', 'pending'));
        
        if (!$result || $result == 0) {
            throw new Exception('Failed to update order status to paid.');
        }

        $order = Mirai_payGetOrder($orderNo);
        if (!$order || $order['status'] !== 'paid') {
            throw new Exception('Order status verification failed.');
        }

        if ($order['order_type'] === 'recharge' && (int)$order['uid'] > 0) {
            if (!Mirai_payAdjustBalance((int)$order['uid'], (float)$order['amount'], 'recharge', '在线充值', $orderNo)) {
                throw new Exception('Failed to adjust balance for recharge.');
            }
        }

        if ($order['order_type'] === 'vip' && (int)$order['uid'] > 0) {
            $meta = Mirai_payOrderMeta($order);
            $payVipLevel = isset($meta['vip_level']) ? (int)$meta['vip_level'] : 1;
            $payVipTime = isset($meta['vip_time']) ? (int)$meta['vip_time'] : 0;
            $purchaseType = isset($meta['purchase_type']) ? (string)$meta['purchase_type'] : 'new';
            
            Mirai_vipProcessOrderPaid((int)$order['uid'], $payVipLevel, $payVipTime, $purchaseType);
        }

        $db->query('COMMIT');
        Mirai_payFinalizeAccess($order);
        return true;
    } catch (Exception $e) {
        $db->query('ROLLBACK');
        return false;
    } finally {
        Mirai_payReleaseLock($orderLockKey);
    }
}

function Mirai_payMarkOrderPending($orderNo) {
    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');

    if (!Mirai_payDbCheck()) {
        return false;
    }

    $orderNo = trim((string)$orderNo);
    if ($orderNo === '') {
        return false;
    }

    $lockKey = 'mirai_pay_pending_' . $orderNo;
    if (!Mirai_payAcquireLock($lockKey, 5)) {
        return false;
    }

    try {
        $db->query('BEGIN');
        $order = $db->fetchRow($db->select('status')->from($ordersTable)->where('order_no = ?', $orderNo)->limit(1));
        if (!$order) {
            $db->query('ROLLBACK');
            return false;
        }

        $status = isset($order['status']) ? (string)$order['status'] : '';
        if ($status === 'pending') {
            $db->query('COMMIT');
            return true;
        }
        if ($status !== 'closed') {
            $db->query('ROLLBACK');
            return false;
        }

        $db->query($db->update($ordersTable)->rows([
            'status' => 'pending',
            'created' => time()
        ])->where('order_no = ?', $orderNo));

        $db->query('COMMIT');
        return true;
    } catch (Exception $e) {
        $db->query('ROLLBACK');
        return false;
    } finally {
        Mirai_payReleaseLock($lockKey);
    }
}
