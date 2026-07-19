<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$db = \Typecho\Db::get();
$security = \Widget\Security::alloc();
$uid = (int)$this->user->uid;
$action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
$message = '';
$messageType = '';
if ($action === 'delete') {
    try {
        $security->protect();
        $orderNo = isset($_GET['order_no']) ? trim((string)$_GET['order_no']) : '';
        if (!preg_match('/^MR[A-Fa-f0-9]{20}$/', $orderNo)) {
            throw new Exception('订单号格式错误');
        }
        $order = Mirai_payGetOrder($orderNo);
        if (!is_array($order) || (int)$order['uid'] !== $uid) {
            throw new Exception('无权删除该订单');
        }
        $orderStatus = isset($order['status']) ? (string)$order['status'] : '';
        if (!in_array($orderStatus, ['closed', 'pending'], true)) {
            throw new Exception('仅支持删除未支付或已关闭订单');
        }
        $ordersTable = Mirai_payTable('orders');
        if (!Mirai_payTableExists($ordersTable)) {
            throw new Exception('订单表不存在');
        }
        $db->query($db->delete($ordersTable)->where('order_no = ?', $orderNo)->where('uid = ?', $uid));
        $message = '订单已删除';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 10;
$ordersData = Mirai_payUserOrders($uid, $page, $pageSize);
$orders = $ordersData['list'];
$total = $ordersData['total'];
$ordersBaseUrl = \Typecho\Common::url('/user/orders', $this->options->index);
?>
<div class="user-module module-orders">
    <div class="module-header">
        <div class="module-title">我的订单</div>
    </div>
    <?php if ($message !== ''): ?>
        <div class="mirai-pay-status <?php echo $messageType === 'success' ? 'is-success' : 'is-error'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <div class="post-list-table">
        <?php if (empty($orders)): ?>
            <div class="empty-state">暂无订单记录</div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                $displayStatus = Mirai_payGetOrderDisplayStatus($order);
                $statusClass = $displayStatus === 'paid' ? 'publish' : 'waiting';
                $statusText = Mirai_payOrderStatusLabel($displayStatus);
                $title = Mirai_payOrderTitle($order);
                $ip = Mirai_payOrderIp($order);
                $detailData = [
                    '商品名称' => $title,
                    '创建时间' => (new \Typecho\Date((int)$order['created']))->format('Y-m-d H:i:s'),
                    '订单号' => isset($order['order_no']) ? (string)$order['order_no'] : '',
                    '支付金额' => number_format((float)$order['amount'], 2) . ' 元',
                    '完成支付时间' => (int)$order['paid_at'] > 0 ? (new \Typecho\Date((int)$order['paid_at']))->format('Y-m-d H:i:s') : '-',
                    '订单状态' => $statusText,
                    '订单类型' => Mirai_payOrderTypeLabel(isset($order['order_type']) ? (string)$order['order_type'] : ''),
                    '支付方式' => Mirai_payMethodLabel(isset($order['payment_method']) ? (string)$order['payment_method'] : ''),
                    '支付流水号' => isset($order['trade_no']) && $order['trade_no'] !== '' ? (string)$order['trade_no'] : '-',
                    'IP' => $ip !== '' ? $ip : '-'
                ];
                $detailJson = htmlspecialchars(json_encode($detailData, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                $deleteUrl = $security->getTokenUrl($ordersBaseUrl . '?action=delete&order_no=' . rawurlencode((string)$order['order_no']) . '&page=' . (int)$page);
                ?>
                <div class="user-post-item">
                    <div class="post-info">
                        <div class="post-title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="post-meta">
                            <span class="time"><?php echo (new \Typecho\Date((int)$order['created']))->format('Y-m-d H:i'); ?></span>
                            <span class="status status-<?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                            <span class="stats"><?php echo htmlspecialchars(Mirai_payOrderTypeLabel((string)$order['order_type']), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="stats"><?php echo number_format((float)$order['amount'], 2) . ' 元'; ?></span>
                            <span class="stats">单号 <?php echo htmlspecialchars($order['order_no'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                    <div class="post-actions">
                        <?php if ((int)$order['cid'] > 0): ?>
                            <?php
                            $post = $db->fetchRow($db->select('cid', 'slug', 'title')->from('table.contents')->where('cid = ?', (int)$order['cid'])->limit(1));
                            ?>
                            <?php if ($post): ?>
                                <a href="<?php echo \Typecho\Router::url('post', $post, $this->options->index); ?>" class="btn-icon" title="查看内容" target="_blank"><i class="ri-external-link-line"></i></a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a href="javascript:;" class="btn-icon js-order-detail-btn" title="订单详情" data-order-detail="<?php echo $detailJson; ?>"><i class="ri-file-list-line"></i></a>
                        <?php if (in_array($displayStatus, ['pending', 'closed'], true)): ?>
                            <a href="<?php echo htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-icon" title="删除失败订单" onclick="return confirm('确定删除该失败订单吗？');"><i class="ri-delete-bin-line"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($total > $pageSize): ?>
                <?php
                $totalPages = ceil($total / $pageSize);
                echo Mirai_customPagination(
                    $page,
                    $totalPages,
                    function($p) {
                        return \Typecho\Common::url('/user/orders?page=' . $p, $this->options->index);
                    }
                );
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<div id="mirai-order-detail-modal">
    <div class="mirai-pay-modal-mask"></div>
    <div class="mirai-pay-method-modal-panel">
        <button type="button" class="mirai-pay-method-modal-close" aria-label="close">×</button>
        <div class="mirai-pay-method-modal-title">订单详情</div>
        <div class="mirai-pay-order-detail"></div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('mirai-order-detail-modal');
    if (!modal) return;
    var mask = modal.querySelector('.mirai-pay-modal-mask');
    var closeBtn = modal.querySelector('.mirai-pay-method-modal-close');
    var detailBox = modal.querySelector('.mirai-pay-order-detail');
    var close = function() {
        modal.style.display = 'none';
        document.body.classList.remove('mirai-pay-modal-open');
    };
    if (mask) mask.addEventListener('click', close);
    if (closeBtn) closeBtn.addEventListener('click', close);
    document.querySelectorAll('.js-order-detail-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var raw = btn.getAttribute('data-order-detail') || '{}';
            var data = {};
            try {
                data = JSON.parse(raw);
            } catch (e) {
                data = {};
            }
            detailBox.innerHTML = '';
            Object.keys(data).forEach(function(key) {
                var row = document.createElement('div');
                row.className = 'mirai-pay-order-detail-row';
                var label = document.createElement('span');
                label.className = 'mirai-pay-order-detail-label';
                label.textContent = key;
                var value = document.createElement('span');
                value.className = 'mirai-pay-order-detail-value';
                value.textContent = data[key] || '-';
                row.appendChild(label);
                row.appendChild(value);
                detailBox.appendChild(row);
            });
            modal.style.display = 'block';
            document.body.classList.add('mirai-pay-modal-open');
        });
    });
});
</script>