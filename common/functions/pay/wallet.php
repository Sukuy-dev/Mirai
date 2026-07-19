<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_payGetWallet($uid, $force_refresh = false) {
    static $cache = [];
    $uid = (int)$uid;
    if ($uid <= 0) {
        return ['uid' => 0, 'balance' => 0];
    }
    if (!$force_refresh && isset($cache[$uid])) {
        return $cache[$uid];
    }

    $db = \Typecho\Db::get();
    $walletsTable = Mirai_payTable('wallets');
    if (!Mirai_payTableExists($walletsTable)) {
        $cache[$uid] = ['uid' => $uid, 'balance' => 0];
        return $cache[$uid];
    }
    $row = $db->fetchRow($db->select()->from($walletsTable)->where('uid = ?', $uid)->limit(1));
    if (!$row) {
        $db->query($db->insert($walletsTable)->rows(['uid' => $uid, 'balance' => 0, 'updated' => time()]));
        $cache[$uid] = ['uid' => $uid, 'balance' => 0];
        return $cache[$uid];
    }
    $cache[$uid] = $row;
    return $cache[$uid];
}

function Mirai_payAdjustBalance($uid, $amount, $type, $remark, $orderNo = '') {
    $uid = (int)$uid;
    if ($uid <= 0 || $amount == 0) {
        return false;
    }
    $db = \Typecho\Db::get();
    $walletsTable = Mirai_payTable('wallets');
    $logsTable = Mirai_payTable('wallet_logs');

    if (!Mirai_payDbCheck()) {
        return false;
    }

    $orderNo = trim((string)$orderNo);
    $userLockKey = 'mirai_pay_user_' . $uid;
    if (!Mirai_payAcquireLock($userLockKey, 10)) {
        return false;
    }

    try {
        $db->query('BEGIN');

        if ($orderNo !== '') {
            $existingLog = $db->fetchRow($db->select('id')->from($logsTable)->where('order_no = ?', $orderNo)->where('uid = ?', $uid)->where('type = ?', (string)$type)->limit(1));
            if ($existingLog) {
                $db->query('COMMIT');
                return true;
            }
        }

        $wallet = Mirai_payGetWallet($uid, true);

        $before = (float)$wallet['balance'];
        $after = round($before + (float)$amount, 2);

        if ($after < 0) {
            $db->query('ROLLBACK');
            return false;
        }

        $db->query($db->update($walletsTable)->rows(['balance' => $after, 'updated' => time()])->where('uid = ?', $uid));

        $db->query($db->insert($logsTable)->rows([
            'uid' => $uid,
            'type' => (string)$type,
            'amount' => round((float)$amount, 2),
            'balance_before' => $before,
            'balance_after' => $after,
            'remark' => (string)$remark,
            'order_no' => $orderNo,
            'created' => time()
        ]));

        $db->query('COMMIT');
        return true;
    } catch (Exception $e) {
        $db->query('ROLLBACK');
        return false;
    } finally {
        Mirai_payReleaseLock($userLockKey);
    }
}

function Mirai_payUserOrders($uid, $page = 1, $pageSize = 10) {
    $uid = (int)$uid;
    if ($uid <= 0) {
        return ['list' => [], 'total' => 0];
    }
    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');

    if (!Mirai_payDbCheck()) {
        return ['list' => [], 'total' => 0];
    }
    $list = $db->fetchAll($db->select()->from($ordersTable)->where('uid = ?', $uid)->order('id', \Typecho\Db::SORT_DESC)->page((int)$page, (int)$pageSize));
    $total = $db->fetchObject($db->select('COUNT(*) AS num')->from($ordersTable)->where('uid = ?', $uid))->num;
    return ['list' => $list, 'total' => (int)$total];
}

function Mirai_payUserWalletLogs($uid, $page = 1, $pageSize = 10) {
    $uid = (int)$uid;
    if ($uid <= 0) {
        return ['list' => [], 'total' => 0];
    }
    $db = \Typecho\Db::get();
    $logsTable = Mirai_payTable('wallet_logs');
    if (!Mirai_payDbCheck()) {
        return ['list' => [], 'total' => 0];
    }
    $list = $db->fetchAll($db->select()->from($logsTable)->where('uid = ?', $uid)->order('id', \Typecho\Db::SORT_DESC)->page((int)$page, (int)$pageSize));
    $total = $db->fetchObject($db->select('COUNT(*) AS num')->from($logsTable)->where('uid = ?', $uid))->num;
    return ['list' => $list, 'total' => (int)$total];
}
