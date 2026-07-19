<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_payBcAdd($a, $b, $scale = 2) {
    return bcadd((string)$a, (string)$b, $scale);
}

function Mirai_payBcSub($a, $b, $scale = 2) {
    return bcsub((string)$a, (string)$b, $scale);
}

function Mirai_payBcComp($a, $b, $scale = 2) {
    return bccomp((string)$a, (string)$b, $scale);
}

function Mirai_payBcRound($value, $scale = 2) {
    return number_format((float)$value, $scale, '.', '');
}

function Mirai_payIncomeStatusLabel($status) {
    $map = [
        0 => '未转入',
        1 => '已转入',
        2 => '转入中'
    ];
    return $map[$status] ?? '未知';
}

function Mirai_payWithdrawalStatusLabel($status) {
    $map = [
        0 => '待处理',
        1 => '已通过',
        2 => '已拒绝',
        3 => '已取消'
    ];
    return $map[$status] ?? '未知';
}

function Mirai_payGetAuthorIncome($authorId) {
    static $cache = [];
    $authorId = (int)$authorId;
    if ($authorId <= 0) {
        return ['total' => '0.00', 'withdrawn' => '0.00', 'pending' => '0.00', 'available' => '0.00'];
    }
    if (isset($cache[$authorId])) {
        return $cache[$authorId];
    }

    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');

    if (!Mirai_payTableExists($ordersTable)) {
        $cache[$authorId] = ['total' => '0.00', 'withdrawn' => '0.00', 'pending' => '0.00', 'available' => '0.00'];
        return $cache[$authorId];
    }

    $sql = "SELECT SUM(income_price) AS total, SUM(CASE WHEN income_status = 1 THEN income_price ELSE 0 END) AS withdrawn, SUM(CASE WHEN income_status = 2 THEN income_price ELSE 0 END) AS pending FROM {$ordersTable} WHERE author_id = {$authorId} AND status = 'paid'";
    $row = $db->fetchRow($db->query($sql));

    $total = Mirai_payBcRound($row['total'] ?? '0', 2);
    $withdrawn = Mirai_payBcRound($row['withdrawn'] ?? '0', 2);
    $pending = Mirai_payBcRound($row['pending'] ?? '0', 2);
    $available = Mirai_payBcSub($total, Mirai_payBcAdd($withdrawn, $pending, 2), 2);

    $cache[$authorId] = [
        'total' => $total,
        'withdrawn' => $withdrawn,
        'pending' => $pending,
        'available' => $available
    ];
    return $cache[$authorId];
}

function Mirai_payGetAuthorIncomeOrders($authorId, $page = 1, $pageSize = 20, $status = null) {
    $db = \Typecho\Db::get();
    $ordersTable = Mirai_payTable('orders');

    if (!Mirai_payTableExists($ordersTable)) {
        return ['list' => [], 'total' => 0];
    }

    $authorId = (int)$authorId;
    if ($authorId <= 0) {
        return ['list' => [], 'total' => 0];
    }

    $select = $db->select()->from($ordersTable)->where('author_id = ?', $authorId)->where('status = ?', 'paid')->where('income_price > ?', 0);
    $countSelect = $db->select('COUNT(*) AS num')->from($ordersTable)->where('author_id = ?', $authorId)->where('status = ?', 'paid')->where('income_price > ?', 0);

    if ($status !== null) {
        $select->where('income_status = ?', (int)$status);
        $countSelect->where('income_status = ?', (int)$status);
    }

    $total = $db->fetchObject($countSelect)->num;
    $list = $db->fetchAll($select->order('id', \Typecho\Db::SORT_DESC)->page((int)$page, (int)$pageSize));

    return ['list' => $list, 'total' => (int)$total];
}

function Mirai_payGetWithdrawals($uid, $page = 1, $pageSize = 20) {
    $db = \Typecho\Db::get();
    $withdrawalsTable = Mirai_payTable('withdrawals');

    if (!Mirai_payTableExists($withdrawalsTable)) {
        return ['list' => [], 'total' => 0];
    }

    $uid = (int)$uid;
    if ($uid <= 0) {
        return ['list' => [], 'total' => 0];
    }

    $list = $db->fetchAll($db->select()->from($withdrawalsTable)->where('uid = ?', $uid)->where('withdraw_type = ?', 'balance')->order('id', \Typecho\Db::SORT_DESC)->page((int)$page, (int)$pageSize));
    $total = $db->fetchObject($db->select('COUNT(*) AS num')->from($withdrawalsTable)->where('uid = ?', $uid)->where('withdraw_type = ?', 'balance'))->num;

    return ['list' => $list, 'total' => (int)$total];
}

function Mirai_payAdminGetWithdrawals($page = 1, $pageSize = 20, $status = null) {
    $db = \Typecho\Db::get();
    $withdrawalsTable = Mirai_payTable('withdrawals');

    if (!Mirai_payTableExists($withdrawalsTable)) {
        return ['list' => [], 'total' => 0];
    }

    // 只返回余额提现记录
    $select = $db->select()->from($withdrawalsTable)->where('withdraw_type = ?', 'balance');
    $countSelect = $db->select('COUNT(*) AS num')->from($withdrawalsTable)->where('withdraw_type = ?', 'balance');

    if ($status !== null) {
        $select->where('status = ?', (int)$status);
        $countSelect->where('status = ?', (int)$status);
    }

    $total = $db->fetchObject($countSelect)->num;
    $list = $db->fetchAll($select->order('id', \Typecho\Db::SORT_DESC)->page((int)$page, (int)$pageSize));

    return ['list' => $list, 'total' => (int)$total];
}

function Mirai_payApiIncomeStats($db, $user) {
    if (!$user->hasLogin()) {
        return ['code' => -1, 'msg' => '请先登录', 'success' => false];
    }

    $uid = (int)$user->uid;
    $income = Mirai_payGetAuthorIncome($uid);

    return [
        'code' => 0,
        'success' => true,
        'data' => [
            'total_income' => $income['total'],
            'withdrawn_income' => $income['withdrawn'],
            'pending_income' => $income['pending'],
            'available_income' => $income['available']
        ]
    ];
}

function Mirai_payApiIncomeOrders($db, $user) {
    if (!$user->hasLogin()) {
        return ['code' => -1, 'msg' => '请先登录', 'success' => false];
    }

    $uid = (int)$user->uid;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $pageSize = isset($_GET['page_size']) ? min(50, max(1, (int)$_GET['page_size'])) : 20;
    $status = isset($_GET['status']) ? (int)$_GET['status'] : null;

    $result = Mirai_payGetAuthorIncomeOrders($uid, $page, $pageSize, $status);

    $list = [];
    foreach ($result['list'] as $order) {
        $list[] = [
            'order_no' => $order['order_no'],
            'product_title' => $order['product_title'] ?? '',
            'amount' => number_format((float)$order['amount'], 2, '.', ''),
            'income_price' => number_format((float)$order['income_price'], 2, '.', ''),
            'income_status' => (int)$order['income_status'],
            'income_status_label' => Mirai_payIncomeStatusLabel((int)$order['income_status']),
            'created' => (int)$order['created'],
            'created_text' => $order['created'] > 0 ? (new \Typecho\Date((int)$order['created']))->format('Y-m-d H:i:s') : '',
            'paid_at' => (int)$order['paid_at'],
            'paid_at_text' => $order['paid_at'] > 0 ? (new \Typecho\Date((int)$order['paid_at']))->format('Y-m-d H:i:s') : ''
        ];
    }

    return [
        'code' => 0,
        'success' => true,
        'data' => [
            'list' => $list,
            'total' => $result['total'],
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($result['total'] / $pageSize)
        ]
    ];
}

function Mirai_payApiIncomeTransfer($db, $user) {
    if (!$user->hasLogin()) {
        return ['code' => -1, 'msg' => '请先登录', 'success' => false];
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['code' => -1, 'msg' => '非法请求', 'success' => false];
    }

    $uid = (int)$user->uid;
    $ordersTable = Mirai_payTable('orders');
    $lockKey = 'mirai_income_transfer_' . $uid;
    
    if (!Mirai_payAcquireLock($lockKey, 10)) {
        return ['code' => -1, 'msg' => '系统繁忙，请稍后再试', 'success' => false];
    }

    try {
        $db->query('BEGIN');

        $sql = "SELECT SUM(income_price) AS total, SUM(CASE WHEN income_status = 1 THEN income_price ELSE 0 END) AS withdrawn, SUM(CASE WHEN income_status = 2 THEN income_price ELSE 0 END) AS pending FROM {$ordersTable} WHERE author_id = {$uid} AND status = 'paid'";
        $row = $db->fetchRow($db->query($sql));

        $total = Mirai_payBcRound($row['total'] ?? '0', 2);
        $withdrawn = Mirai_payBcRound($row['withdrawn'] ?? '0', 2);
        $pending = Mirai_payBcRound($row['pending'] ?? '0', 2);
        $available = Mirai_payBcSub($total, Mirai_payBcAdd($withdrawn, $pending, 2), 2);

        if (Mirai_payBcComp($available, '0.01') < 0) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '暂无可转入收益', 'success' => false];
        }

        $orders = $db->fetchAll($db->select()->from($ordersTable)->where('author_id = ?', $uid)->where('status = ?', 'paid')->where('income_status = ?', 0)->where('income_price > ?', 0)->order('id', \Typecho\Db::SORT_ASC));

        if (empty($orders)) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '未找到可转入的订单', 'success' => false];
        }

        $orderIds = [];
        $transferAmount = '0.00';
        
        foreach ($orders as $order) {
            $orderIds[] = (int)$order['id'];
            $transferAmount = Mirai_payBcAdd($transferAmount, $order['income_price'], 2);
        }

        $incomeDetail = json_encode([
            'transfer_type' => 'income_to_balance',
            'order_ids' => $orderIds,
            'created' => time()
        ], JSON_UNESCAPED_UNICODE);

        $updatedCount = 0;
        foreach ($orderIds as $orderId) {
            $result = $db->query($db->update($ordersTable)->rows([
                'income_status' => 1,
                'income_detail' => $incomeDetail
            ])->where('id = ?', $orderId)->where('income_status = ?', 0));
            if ($result) {
                $updatedCount++;
            }
        }

        if ($updatedCount !== count($orderIds)) {
            Mirai_payLog("收益转入时部分订单状态更新失败 expected:" . count($orderIds) . " actual:{$updatedCount}", 'warning');
        }

        $adjustResult = Mirai_payAdjustBalance($uid, $transferAmount, 'income_transfer', '收益转入余额', '');
        if (!$adjustResult) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '余额调整失败', 'success' => false];
        }

        $db->query('COMMIT');
        return ['code' => 0, 'msg' => '转入成功', 'success' => true, 'amount' => $transferAmount];
    } catch (Exception $e) {
        $db->query('ROLLBACK');
        Mirai_payLog("收益转入异常 uid:{$uid} error:" . $e->getMessage(), 'error');
        return ['code' => -1, 'msg' => '转入失败，请稍后再试', 'success' => false];
    } finally {
        Mirai_payReleaseLock($lockKey);
    }
}

function Mirai_payApiBalanceWithdrawCreate($db, $user) {
    if (!$user->hasLogin()) {
        return ['code' => -1, 'msg' => '请先登录', 'success' => false];
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['code' => -1, 'msg' => '非法请求', 'success' => false];
    }

    $uid = (int)$user->uid;
    $amount = isset($_POST['amount']) ? round((float)$_POST['amount'], 2) : 0;
    $accountType = isset($_POST['account_type']) ? trim((string)$_POST['account_type']) : '';
    $accountName = isset($_POST['account_name']) ? trim((string)$_POST['account_name']) : '';
    $accountNo = isset($_POST['account_no']) ? trim((string)$_POST['account_no']) : '';
    $remark = isset($_POST['remark']) ? trim((string)$_POST['remark']) : '';

    $minWithdraw = (float)Mirai_payGetOption('withdrawMinAmount', '10');
    if ($amount < $minWithdraw) {
        return ['code' => -1, 'msg' => '最低提现金额为 ' . $minWithdraw . ' 元', 'success' => false];
    }

    $wallet = Mirai_payGetWallet($uid);
    $balance = (float)$wallet['balance'];
    
    if ($balance < $amount) {
        return ['code' => -1, 'msg' => '余额不足', 'success' => false];
    }

    $validAccountTypes = ['alipay', 'wechat', 'bank', 'alipay_qr', 'wechat_qr'];
    if (!in_array($accountType, $validAccountTypes, true)) {
        return ['code' => -1, 'msg' => '账户类型无效', 'success' => false];
    }

    $isQrCode = in_array($accountType, ['alipay_qr', 'wechat_qr'], true);
    if ($accountName === '') {
        return ['code' => -1, 'msg' => '账户信息不完整', 'success' => false];
    }
    if (!$isQrCode && $accountNo === '') {
        return ['code' => -1, 'msg' => '账户信息不完整', 'success' => false];
    }

    $qrCodeUrl = '';
    if ($isQrCode && isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = Mirai_payUploadQrCode($_FILES['qr_code']);
        if (!$uploadResult['success']) {
            return ['code' => -1, 'msg' => $uploadResult['msg'], 'success' => false];
        }
        $qrCodeUrl = $uploadResult['url'];
    }

    if ($isQrCode && $qrCodeUrl === '') {
        return ['code' => -1, 'msg' => '请上传收款二维码', 'success' => false];
    }

    $withdrawalsTable = Mirai_payTable('withdrawals');
    $lockKey = 'mirai_balance_withdrawal_' . $uid;
    
    if (!Mirai_payAcquireLock($lockKey, 10)) {
        return ['code' => -1, 'msg' => '系统繁忙，请稍后再试', 'success' => false];
    }

    try {
        $db->query('BEGIN');

        $existingPending = $db->fetchRow($db->select('id')->from($withdrawalsTable)->where('uid = ?', $uid)->where('status = ?', 0)->where('withdraw_type = ?', 'balance')->limit(1));
        if ($existingPending) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '您有余额提现申请正在处理中，请等待处理完成', 'success' => false];
        }

        $walletsTable = Mirai_payTable('wallets');
        $walletLogsTable = Mirai_payTable('wallet_logs');
        $wallet = $db->fetchRow($db->select()->from($walletsTable)->where('uid = ?', $uid)->limit(1));
        if (!$wallet) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '钱包不存在', 'success' => false];
        }

        $balance = (float)$wallet['balance'];
        $balanceBefore = $balance;
        $balanceAfter = round($balance - $amount, 2);

        if ($balance < $amount) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '余额不足', 'success' => false];
        }

        $updateResult = $db->query($db->update($walletsTable)->rows([
            'balance' => $balanceAfter,
            'updated' => time()
        ])->where('uid = ?', $uid));

        if (!$updateResult) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '余额冻结失败', 'success' => false];
        }
        $db->query($db->insert($walletLogsTable)->rows([
            'uid' => $uid,
            'type' => 'withdraw_freeze',
            'amount' => -$amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'remark' => '提现冻结',
            'order_no' => '',
            'created' => time()
        ]));

        $insertResult = $db->query($db->insert($withdrawalsTable)->rows([
            'uid' => $uid,
            'amount' => $amount,
            'withdraw_type' => 'balance',
            'status' => 0,
            'account_type' => $accountType,
            'account_name' => $accountName,
            'account_no' => $accountNo,
            'qr_code' => $qrCodeUrl,
            'remark' => $remark,
            'admin_remark' => '',
            'created' => time(),
            'processed_at' => 0
        ]));

        $db->query('COMMIT');
        return ['code' => 0, 'msg' => '提现申请已提交', 'success' => true];
    } catch (Exception $e) {
        $db->query('ROLLBACK');
        Mirai_payLog("余额提现申请异常 uid:{$uid} error:" . $e->getMessage(), 'error');
        return ['code' => -1, 'msg' => '提现申请失败，请稍后再试', 'success' => false];
    } finally {
        Mirai_payReleaseLock($lockKey);
    }
}

function Mirai_payApiBalanceWithdrawCancel($db, $user) {
    if (!$user->hasLogin()) {
        return ['code' => -1, 'msg' => '请先登录', 'success' => false];
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['code' => -1, 'msg' => '非法请求', 'success' => false];
    }

    $uid = (int)$user->uid;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        return ['code' => -1, 'msg' => '提现ID无效', 'success' => false];
    }

    $withdrawalsTable = Mirai_payTable('withdrawals');
    $lockKey = 'mirai_balance_withdrawal_' . $uid;
    
    if (!Mirai_payAcquireLock($lockKey, 10)) {
        return ['code' => -1, 'msg' => '系统繁忙，请稍后再试', 'success' => false];
    }

    try {
        $db->query('BEGIN');

        $withdrawal = $db->fetchRow($db->select()->from($withdrawalsTable)->where('id = ?', $id)->where('uid = ?', $uid)->where('withdraw_type = ?', 'balance')->limit(1));
        if (!$withdrawal) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '提现记录不存在', 'success' => false];
        }

        if ((int)$withdrawal['status'] !== 0) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '该提现已处理，无法取消', 'success' => false];
        }

        $amount = (float)$withdrawal['amount'];

        $updateResult = $db->query($db->update($withdrawalsTable)->rows(['status' => 3, 'admin_remark' => '用户主动取消', 'processed_at' => time()])->where('id = ?', $id)->where('status = ?', 0));
        if (!$updateResult) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '状态更新失败，请稍后再试', 'success' => false];
        }

        $adjustResult = Mirai_payAdjustBalance($uid, $amount, 'withdraw_unfreeze', '提现取消退回', '');
        if (!$adjustResult) {
            $db->query('ROLLBACK');
            return ['code' => -1, 'msg' => '余额退回失败', 'success' => false];
        }

        $db->query('COMMIT');
        return ['code' => 0, 'msg' => '提现已取消', 'success' => true];
    } catch (Exception $e) {
        $db->query('ROLLBACK');
        Mirai_payLog("余额提现取消异常 withdrawal_id:{$id} uid:{$uid} error:" . $e->getMessage(), 'error');
        return ['code' => -1, 'msg' => '取消失败，请稍后再试', 'success' => false];
    } finally {
        Mirai_payReleaseLock($lockKey);
    }
}


function Mirai_payAdminProcessBalanceWithdrawal($id, $approve, $adminRemark = '') {
    $db = \Typecho\Db::get();
    $withdrawalsTable = Mirai_payTable('withdrawals');

    if (!Mirai_payTableExists($withdrawalsTable)) {
        return ['success' => false, 'msg' => '提现表不存在'];
    }

    $id = (int)$id;
    if ($id <= 0) {
        return ['success' => false, 'msg' => '提现ID无效'];
    }

    $withdrawal = $db->fetchRow($db->select()->from($withdrawalsTable)->where('id = ?', $id)->limit(1));
    if (!$withdrawal) {
        return ['success' => false, 'msg' => '提现记录不存在'];
    }

    if ((int)$withdrawal['status'] !== 0) {
        return ['success' => false, 'msg' => '该提现已处理'];
    }

    $uid = (int)$withdrawal['uid'];
    $amount = (float)$withdrawal['amount'];
    $lockKey = 'mirai_balance_withdrawal_' . $uid;
    
    if (!Mirai_payAcquireLock($lockKey, 10)) {
        return ['success' => false, 'msg' => '系统繁忙，请稍后再试'];
    }

    try {
        $db->query('BEGIN');

        $withdrawal = $db->fetchRow($db->select()->from($withdrawalsTable)->where('id = ?', $id)->limit(1));
        if (!$withdrawal || (int)$withdrawal['status'] !== 0) {
            $db->query('ROLLBACK');
            return ['success' => false, 'msg' => '提现状态已变更'];
        }

        $newStatus = $approve ? 1 : 2;

        $updateResult = $db->query($db->update($withdrawalsTable)->rows([
            'status' => $newStatus,
            'admin_remark' => trim((string)$adminRemark),
            'processed_at' => time()
        ])->where('id = ?', $id)->where('status = ?', 0));

        $affectedRows = 0;
        if (is_object($updateResult)) {
            $affectedRows = $updateResult->rowCount();
        } elseif (is_numeric($updateResult)) {
            $affectedRows = (int)$updateResult;
        }

        if ($affectedRows === 0) {
            $db->query('ROLLBACK');
            Mirai_payLog("余额提现处理TOCTOU检测: 状态已被其他进程修改 withdrawal_id:{$id}", 'warning');
            return ['success' => false, 'msg' => '提现状态已变更，请刷新页面后重试'];
        }

        if (!$approve) {
            $adjustResult = Mirai_payAdjustBalance($uid, $amount, 'withdraw_unfreeze', '提现拒绝退回', '');
            if (!$adjustResult) {
                $db->query('ROLLBACK');
                Mirai_payLog("余额提现拒绝时退回失败 withdrawal_id:{$id}", 'error');
                return ['success' => false, 'msg' => '余额退回失败'];
            }
        }

        $db->query('COMMIT');
        return ['success' => true, 'msg' => $approve ? '已通过' : '已拒绝'];
    } catch (Exception $e) {
        $db->query('ROLLBACK');
        Mirai_payLog("余额提现处理异常 withdrawal_id:{$id} error:" . $e->getMessage(), 'error');
        return ['success' => false, 'msg' => '处理失败，请稍后再试'];
    } finally {
        Mirai_payReleaseLock($lockKey);
    }
}


function Mirai_payAdminGetBalanceWithdrawalStatistics() {
    $db = \Typecho\Db::get();
    $withdrawalsTable = Mirai_payTable('withdrawals');

    $stats = [
        'pending_amount' => '0.00',
        'pending_count' => 0,
        'approved_amount' => '0.00',
        'approved_count' => 0,
        'total_amount' => '0.00',
        'total_count' => 0
    ];

    if (!Mirai_payTableExists($withdrawalsTable)) {
        return $stats;
    }

    try {
        // 待处理的余额提现
        $pending = $db->fetchRow($db->select('COUNT(*) AS cnt', 'SUM(amount) AS total')
            ->from($withdrawalsTable)
            ->where('withdraw_type = ?', 'balance')
            ->where('status = ?', 0));
        
        $stats['pending_count'] = (int)($pending['cnt'] ?? 0);
        $stats['pending_amount'] = Mirai_payBcRound($pending['total'] ?? '0', 2);

        // 已通过的余额提现
        $approved = $db->fetchRow($db->select('COUNT(*) AS cnt', 'SUM(amount) AS total')
            ->from($withdrawalsTable)
            ->where('withdraw_type = ?', 'balance')
            ->where('status = ?', 1));
        
        $stats['approved_count'] = (int)($approved['cnt'] ?? 0);
        $stats['approved_amount'] = Mirai_payBcRound($approved['total'] ?? '0', 2);

        // 所有余额提现（不包括已取消）
        $all = $db->fetchRow($db->select('COUNT(*) AS cnt', 'SUM(amount) AS total')
            ->from($withdrawalsTable)
            ->where('withdraw_type = ?', 'balance')
            ->where('status != ?', 3));
        
        $stats['total_count'] = (int)($all['cnt'] ?? 0);
        $stats['total_amount'] = Mirai_payBcRound($all['total'] ?? '0', 2);
    } catch (Exception $e) {
        Mirai_payLog("获取余额提现统计失败: " . $e->getMessage(), 'error');
    }

    return $stats;
}
