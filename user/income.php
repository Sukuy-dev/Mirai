<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$uid = (int)$this->user->uid;
$income = Mirai_payGetAuthorIncome($uid);

$ordersPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$ordersPageSize = 10;
$ordersStatus = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$ordersData = Mirai_payGetAuthorIncomeOrders($uid, $ordersPage, $ordersPageSize, $ordersStatus);
$orders = $ordersData['list'];
$ordersTotal = $ordersData['total'];

$security = \Widget\Security::alloc();
$token = $security->getToken('api');
?>
<div class="user-module module-income">
    <div class="module-header">
        <div class="module-title">收益中心</div>
    </div>
    
    <div class="income-stats">
        <div class="stat-item">
            <div class="stat-label">总收益</div>
            <div class="stat-value">￥<?php echo number_format($income['total'], 2); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">已转入</div>
            <div class="stat-value success">￥<?php echo number_format($income['withdrawn'], 2); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">转入中</div>
            <div class="stat-value warning">￥<?php echo number_format($income['pending'], 2); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">可转入</div>
            <div class="stat-value primary">￥<?php echo number_format($income['available'], 2); ?></div>
        </div>
    </div>

    <div class="module-header" style="margin-top:20px;">
        <div class="module-title">收益转入</div>
    </div>
    
    <?php if ($income['available'] <= 0): ?>
        <div class="alert alert-success">
            暂无可转入收益。
        </div>
    <?php else: ?>
        <form class="income-transfer-form" id="incomeTransferForm" method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-row">
                <div class="income-transfer-info">
                    <div class="income-transfer-label">可转入金额</div>
                    <div class="income-transfer-amount">￥<?php echo number_format($income['available'], 2); ?></div>
                    <div class="income-transfer-hint">将全部可转入收益转入余额</div>
                </div>
            </div>
            <div class="form-row form-row-actions">
                <button type="submit" class="btn btn-primary">全部转入余额</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="module-header" style="margin-top:20px;">
        <div class="module-title">收益明细</div>
        <div class="module-actions">
            <select id="incomeStatusFilter" onchange="filterIncomeStatus(this.value)">
                <option value="">全部状态</option>
                <option value="0" <?php echo $ordersStatus === 0 ? 'selected' : ''; ?>>未转入</option>
                <option value="1" <?php echo $ordersStatus === 1 ? 'selected' : ''; ?>>已转入</option>
                <option value="2" <?php echo $ordersStatus === 2 ? 'selected' : ''; ?>>转入中</option>
            </select>
        </div>
    </div>
    
    <div class="post-list-table">
        <?php if (empty($orders)): ?>
            <div class="empty-state">暂无收益记录</div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                $incomeStatusLabel = Mirai_payIncomeStatusLabel((int)$order['income_status']);
                $incomeStatusClass = (int)$order['income_status'] === 0 ? 'status-waiting' : ((int)$order['income_status'] === 1 ? 'status-publish' : 'status-waiting');
                ?>
                <div class="user-post-item">
                    <div class="post-info">
                        <div class="post-title">
                            <?php echo htmlspecialchars($order['product_title'] ?: '付费内容'); ?>
                            <span class="status <?php echo $incomeStatusClass; ?>"><?php echo htmlspecialchars($incomeStatusLabel); ?></span>
                        </div>
                        <div class="post-meta">
                            <span class="time"><?php echo $order['paid_at'] > 0 ? (new \Typecho\Date((int)$order['paid_at']))->format('Y-m-d H:i') : '-'; ?></span>
                            <span class="stats">订单金额：￥<?php echo number_format((float)$order['amount'], 2); ?></span>
                            <span class="stats primary">分成：￥<?php echo number_format((float)$order['income_price'], 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($ordersTotal > $ordersPageSize): ?>
                <?php
                $ordersTotalPages = ceil($ordersTotal / $ordersPageSize);
                echo Mirai_customPagination(
                    $ordersPage,
                    $ordersTotalPages,
                    function($p) use ($ordersStatus) {
                        $url = '/user/income?page=' . $p;
                        if ($ordersStatus !== null) $url .= '&status=' . $ordersStatus;
                        return \Typecho\Common::url($url, $this->options->index);
                    }
                );
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('incomeTransferForm');
    var isSubmitting = false;
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (isSubmitting) {
                alert('正在处理中，请勿重复点击');
                return;
            }
            if (!confirm('确定将收益转入余额吗？')) {
                return;
            }
            isSubmitting = true;
            var btn = form.querySelector('button[type="submit"]');
            var originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '处理中...';
            
            var formData = new FormData(form);
            formData.set('_ajax', '1');
            
            fetch('<?php echo Mirai_payBuildApiUrl("income_transfer"); ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    alert(data.msg || '转入成功');
                    window.location.reload();
                } else {
                    alert(data.msg || '转入失败');
                    btn.disabled = false;
                    btn.textContent = originalText;
                    isSubmitting = false;
                }
            })
            .catch(function(err) {
                alert('网络错误，请稍后重试');
                btn.disabled = false;
                btn.textContent = originalText;
                isSubmitting = false;
            });
        });
    }
})();

function filterIncomeStatus(status) {
    var url = '/user/income';
    var params = [];
    if (status !== '') params.push('status=' + status);
    params.push('page=1');
    window.location.href = '<?php echo $this->options->index; ?>' + url + '?' + params.join('&');
}
</script>