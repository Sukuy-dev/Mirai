<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_payAdminGetOrders($page = 1, $pageSize = 20, $filters = []) {
    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');

    if (!Mirai_payTableExists($ordersTable)) {
        return ['list' => [], 'total' => 0];
    }

    $select = $db->select()->from($ordersTable);
    $countSelect = $db->select(['COUNT(*)' => 'num'])->from($ordersTable);

    if (!empty($filters['status'])) {
        $select->where('status = ?', $filters['status']);
        $countSelect->where('status = ?', $filters['status']);
    }
    if (!empty($filters['order_type'])) {
        $select->where('order_type = ?', $filters['order_type']);
        $countSelect->where('order_type = ?', $filters['order_type']);
    }
    if (!empty($filters['payment_method'])) {
        $select->where('payment_method = ?', $filters['payment_method']);
        $countSelect->where('payment_method = ?', $filters['payment_method']);
    }
    if (!empty($filters['uid'])) {
        $select->where('uid = ?', (int)$filters['uid']);
        $countSelect->where('uid = ?', (int)$filters['uid']);
    }
    if (!empty($filters['order_no'])) {
        $select->where('order_no LIKE ?', '%' . $filters['order_no'] . '%');
        $countSelect->where('order_no LIKE ?', '%' . $filters['order_no'] . '%');
    }
    if (!empty($filters['keyword'])) {
        $keyword = '%' . trim((string)$filters['keyword']) . '%';
        $select->where('order_no LIKE ? OR trade_no LIKE ?', $keyword, $keyword);
        $countSelect->where('order_no LIKE ? OR trade_no LIKE ?', $keyword, $keyword);
    }

    $total = $db->fetchObject($countSelect)->num;
    $list = $db->fetchAll($select->order('id', \Typecho\Db::SORT_DESC)->page((int)$page, (int)$pageSize));

    return ['list' => $list, 'total' => (int)$total];
}

function Mirai_payAdminGetWallets($page = 1, $pageSize = 20, $uids = []) {
    $db = \Typecho\Db::get();
    $walletsTable = Mirai_payTable('wallets');

    if (!Mirai_payTableExists($walletsTable)) {
        return ['list' => [], 'total' => 0];
    }

    $uids = array_values(array_filter(array_map('intval', (array)$uids), function($uid) {
        return $uid > 0;
    }));
    if (!empty($uids)) {
        $list = $db->fetchAll($db->select('uid', 'balance')->from($walletsTable)->where('uid IN ?', $uids));
        return ['list' => $list, 'total' => count($list)];
    }

    $list = $db->fetchAll($db->select()->from($walletsTable)->order('id', \Typecho\Db::SORT_DESC)->page((int)$page, (int)$pageSize));
    $total = $db->fetchObject($db->select('COUNT(*) AS num')->from($walletsTable))->num;

    return ['list' => $list, 'total' => (int)$total];
}

function Mirai_payAdminGetUsersMap($uids = []) {
    $uids = array_values(array_filter(array_map('intval', (array)$uids), function($uid) {
        return $uid > 0;
    }));
    if (empty($uids)) {
        return [];
    }
    $db = \Typecho\Db::get();
    $users = $db->fetchAll($db->select('uid', 'name', 'screenName')->from('table.users')->where('uid IN ?', $uids));
    $map = [];
    foreach ($users as $user) {
        $uid = (int)$user['uid'];
        $map[$uid] = $user;
    }
    return $map;
}

function Mirai_payAdminAdjustBalance($uid, $amount, $type, $remark) {
    $uid = (int)$uid;
    if ($uid <= 0) {
        return ['success' => false, 'msg' => '用户ID无效'];
    }
    $amount = (float)$amount;
    if ($amount == 0) {
        return ['success' => false, 'msg' => '金额不能为0'];
    }

    $result = Mirai_payAdjustBalance($uid, $amount, $type, $remark, 'admin_' . time());

    if ($result) {
        return ['success' => true, 'msg' => '操作成功'];
    }
    return ['success' => false, 'msg' => '操作失败'];
}

function Mirai_payAdminGetStatistics() {
    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');
    $walletsTable = Mirai_payTable('wallets');

    $stats = [
        'total_orders' => 0,
        'paid_orders' => 0,
        'total_amount' => 0,
        'today_orders' => 0,
        'today_amount' => 0,
        'total_balance' => 0,
    ];

    if (Mirai_payTableExists($ordersTable)) {
        $stats['total_orders'] = (int)$db->fetchObject($db->select('COUNT(*) AS num')->from($ordersTable))->num;
        $stats['paid_orders'] = (int)$db->fetchObject($db->select('COUNT(*) AS num')->from($ordersTable)->where('status = ?', 'paid'))->num;
        $stats['total_amount'] = (float)$db->fetchObject($db->select('SUM(amount) AS total')->from($ordersTable)->where('status = ?', 'paid'))->total;

        $todayDate = new \Typecho\Date();
        $todayStart = strtotime($todayDate->format('Y-m-d'));
        $stats['today_orders'] = (int)$db->fetchObject($db->select('COUNT(*) AS num')->from($ordersTable)->where('status = ?', 'paid')->where('paid_at >= ?', $todayStart))->num;
        $stats['today_amount'] = (float)$db->fetchObject($db->select('SUM(amount) AS total')->from($ordersTable)->where('status = ?', 'paid')->where('paid_at >= ?', $todayStart))->total;
    }

    if (Mirai_payTableExists($walletsTable)) {
        $stats['total_balance'] = (float)$db->fetchObject($db->select('SUM(balance) AS total')->from($walletsTable))->total;
    }

    return $stats;
}
