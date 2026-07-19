<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$wallet = Mirai_payGetWallet((int)$this->user->uid);
$balance = isset($wallet['balance']) ? (float)$wallet['balance'] : 0;
$logsPage = isset($_GET['logs_page']) ? max(1, (int)$_GET['logs_page']) : 1;
$logsPageSize = 10;
$logsData = Mirai_payUserWalletLogs((int)$this->user->uid, $logsPage, $logsPageSize);
$logs = $logsData['list'];
$logsTotal = $logsData['total'];
$onlineMethods = Mirai_payAvailableOnlineChannels();
$iconBaseUrl = Mirai_getThemeUrl() . '/assets/images/';
$iconMap = [
    'wechat' => 'wechat-pay.svg',
    'alipay' => 'alipay.svg',
    'qq' => 'QQ-Pay.svg'
];
$methodData = [];
foreach ($onlineMethods as $method) {
    $iconFile = isset($iconMap[$method]) ? $iconMap[$method] : '';
    $methodData[] = [
        'value' => (string)$method,
        'label' => Mirai_payMethodLabel($method),
        'icon' => $iconFile !== '' ? ($iconBaseUrl . $iconFile) : ''
    ];
}
$security = \Widget\Security::alloc();
$token = $security->getToken('api');

$withdrawalsPage = isset($_GET['wpage']) ? max(1, (int)$_GET['wpage']) : 1;
$withdrawalsPageSize = 5;
$withdrawalsData = Mirai_payGetWithdrawals((int)$this->user->uid, $withdrawalsPage, $withdrawalsPageSize);
$withdrawals = $withdrawalsData['list'];
$withdrawalsTotal = $withdrawalsData['total'];
$minWithdraw = (float)Mirai_payGetOption('withdrawMinAmount', '10');
?>
<div class="user-module module-wallet">
    <div class="module-header">
        <div class="module-title">余额充值</div>
    </div>
    <div class="mirai-pay-wallet-balance">
        当前余额：<?php echo number_format($balance, 2); ?>
    </div>
    <form class="mirai-pay-form mirai-pay-form-recharge" method="post" action="<?php echo htmlspecialchars(Mirai_payBuildApiUrl('pay_create_order'), ENT_QUOTES, 'UTF-8'); ?>" data-pay-methods="<?php echo htmlspecialchars(json_encode($methodData), ENT_QUOTES, 'UTF-8'); ?>" data-order-type-label="充值订单" data-order-title="余额充值">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="order_type" value="recharge">
        <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars(isset($onlineMethods[0]) ? (string)$onlineMethods[0] : '', ENT_QUOTES, 'UTF-8'); ?>">
        <div class="mirai-paybox-row" style="margin-bottom:12px;">
            <input class="mirai-paybox-amount" type="number" name="amount" min="0.01" step="0.01" placeholder="输入充值金额" required>
        </div>
        <?php if (empty($onlineMethods)): ?>
            <div class="mirai-paybox-empty">当前未启用可用在线收款通道，请联系管理员配置。</div>
        <?php endif; ?>
        <div class="mirai-paybox-row" style="margin-bottom:12px;">
            <button type="submit" class="btn btn-primary mirai-paybox-submit" <?php echo empty($onlineMethods) ? 'disabled' : ''; ?>>立即充值</button>
        </div>
    </form>
    
    <div class="module-header" style="margin-top:20px;">
        <div class="module-title">申请提现</div>
    </div>
    
    <?php if ($balance < $minWithdraw): ?>
        <div class="alert alert-warning">
            余额不足最低提现额度（￥<?php echo number_format($minWithdraw, 2); ?>），暂无法申请提现。
        </div>
    <?php else: ?>
        <form class="withdraw-form" id="withdrawForm" method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-row">
                <label class="form-label">提现金额</label>
                <div class="form-input">
                    <input type="number" name="amount" id="withdrawAmount" min="<?php echo $minWithdraw; ?>" max="<?php echo $balance; ?>" step="0.01" placeholder="最低￥<?php echo number_format($minWithdraw, 2); ?>" required>
                    <span class="form-hint">可提现：￥<?php echo number_format($balance, 2); ?></span>
                </div>
            </div>
            <div class="form-row">
                <label class="form-label">收款方式</label>
                <div class="form-input">
                    <select name="account_type" id="withdrawAccountType" required>
                        <option value="">请选择</option>
                        <option value="alipay">支付宝-账号</option>
                        <option value="alipay_qr">支付宝-二维码</option>
                        <option value="wechat">微信-账号</option>
                        <option value="wechat_qr">微信-二维码</option>
                        <option value="bank">银行卡</option>
                    </select>
                </div>
            </div>
            <div class="form-row account-no-row" id="accountNoRow">
                <label class="form-label">收款账号</label>
                <div class="form-input">
                    <input type="text" name="account_no" id="withdrawAccountNo" placeholder="请输入收款账号" required>
                </div>
            </div>
            <div class="form-row qr-code-row" id="qrCodeRow" style="display:none;">
                <label class="form-label">收款码</label>
                <div class="form-input qr-code-input-wrapper">
                    <div class="qr-code-upload-section">
                        <input type="file" name="qr_code" id="qrCodeInput" accept="image/*" required>
                        <span class="form-hint">请上传微信或支付宝收款码图片</span>
                    </div>
                    <div class="qr-code-preview" id="qrCodePreview"></div>
                </div>
            </div>
            <div class="form-row">
                <label class="form-label">收款账户名</label>
                <div class="form-input">
                    <input type="text" name="account_name" id="withdrawAccountName" placeholder="请输入收款账户真实姓名" required>
                </div>
            </div>
            <div class="form-row form-row-actions">
                <label class="form-label"></label>
                <div class="form-input">
                    <button type="submit" class="btn btn-primary">提交申请</button>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <div class="module-header" style="margin-top:20px;">
        <div class="module-title">提现记录</div>
    </div>
    
    <div class="post-list-table">
        <?php if (empty($withdrawals)): ?>
            <div class="empty-state">暂无提现记录</div>
        <?php else: ?>
            <?php foreach ($withdrawals as $withdrawal): ?>
                <?php
                $statusLabel = Mirai_payWithdrawalStatusLabel((int)$withdrawal['status']);
                $statusClassMap = [
                    0 => 'status-waiting',
                    1 => 'status-publish',
                    2 => 'status-waiting',
                    3 => 'status-draft'
                ];
                $statusClass = isset($statusClassMap[(int)$withdrawal['status']]) ? $statusClassMap[(int)$withdrawal['status']] : 'status-waiting';
                $accountTypeLabel = [
                    'alipay' => '支付宝-账号',
                    'alipay_qr' => '支付宝-二维码',
                    'wechat' => '微信-账号',
                    'wechat_qr' => '微信-二维码',
                    'bank' => '银行卡'
                ];
                $accountTypeText = isset($accountTypeLabel[$withdrawal['account_type']]) ? $accountTypeLabel[$withdrawal['account_type']] : $withdrawal['account_type'];
                ?>
                <div class="user-post-item">
                    <div class="post-info">
                        <div class="post-title">
                            提现 ￥<?php echo number_format((float)$withdrawal['amount'], 2); ?>
                            <span class="status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                        </div>
                        <div class="post-meta">
                            <span class="time"><?php echo $withdrawal['created'] > 0 ? (new \Typecho\Date((int)$withdrawal['created']))->format('Y-m-d H:i') : '-'; ?></span>
                            <span class="stats"><?php echo htmlspecialchars($accountTypeText); ?></span>
                            <span class="stats"><?php echo htmlspecialchars($withdrawal['account_name']); ?></span>
                            <?php if ((int)$withdrawal['status'] === 0): ?>
                                <a href="javascript:;" class="cancel-withdraw" data-id="<?php echo (int)$withdrawal['id']; ?>">取消</a>
                            <?php endif; ?>
                        </div>
                        <?php if ($withdrawal['admin_remark']): ?>
                            <div class="post-excerpt">管理员备注：<?php echo htmlspecialchars($withdrawal['admin_remark']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($withdrawalsTotal > $withdrawalsPageSize): ?>
                <?php
                $withdrawalsTotalPages = ceil($withdrawalsTotal / $withdrawalsPageSize);
                echo Mirai_customPagination(
                    $withdrawalsPage,
                    $withdrawalsTotalPages,
                    function($p) {
                        return \Typecho\Common::url('/user/wallet?wpage=' . $p, $this->options->index);
                    }
                );
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="module-header" style="margin-top:18px;">
        <div class="module-title">余额明细</div>
    </div>
    <div class="post-list-table">
        <?php if (empty($logs)): ?>
            <div class="empty-state">暂无余额明显</div>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <?php $isIncome = (float)$log['amount'] >= 0; ?>
                <div class="user-post-item">
                    <div class="post-info">
                        <div class="post-title"><?php echo htmlspecialchars($log['remark'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="post-meta">
                            <span class="time"><?php echo (new \Typecho\Date((int)$log['created']))->format('Y-m-d H:i'); ?></span>
                            <span class="status status-<?php echo $isIncome ? 'publish' : 'waiting'; ?>"><?php echo $isIncome ? '收入' : '支出'; ?></span>
                            <span class="stats"><?php echo ($isIncome ? '+' : '') . number_format((float)$log['amount'], 2); ?></span>
                            <span class="stats">余额 <?php echo number_format((float)$log['balance_after'], 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($logsTotal > $logsPageSize): ?>
                <?php
                $logsTotalPages = ceil($logsTotal / $logsPageSize);
                echo Mirai_customPagination(
                    $logsPage,
                    $logsTotalPages,
                    function($p) {
                        return \Typecho\Common::url('/user/wallet?logs_page=' . $p, $this->options->index);
                    }
                );
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>


<script>
(function() {
    var accountTypeSelect = document.getElementById('withdrawAccountType');
    var qrCodeRow = document.getElementById('qrCodeRow');
    var qrCodeInput = document.getElementById('qrCodeInput');
    var qrCodePreview = document.getElementById('qrCodePreview');
    var accountNoRow = document.getElementById('accountNoRow');
    var accountNoInput = document.getElementById('withdrawAccountNo');
    
    function updateFormFields() {
        var val = accountTypeSelect ? accountTypeSelect.value : '';
        var isQrCode = val === 'wechat_qr' || val === 'alipay_qr';
        var isAccount = val === 'wechat' || val === 'alipay' || val === 'bank';
        if (qrCodeRow) {
            qrCodeRow.style.display = isQrCode ? 'flex' : 'none';
        }
        if (qrCodeInput) {
            qrCodeInput.required = isQrCode;
            if (!isQrCode) {
                qrCodeInput.value = '';
                if (qrCodePreview) qrCodePreview.innerHTML = '';
            }
        }
        if (accountNoRow) {
            accountNoRow.style.display = isAccount ? 'flex' : 'none';
        }
        if (accountNoInput) {
            accountNoInput.required = isAccount;
            if (!isAccount) accountNoInput.value = '';
        }
    }
    
    if (accountTypeSelect) {
        accountTypeSelect.addEventListener('change', updateFormFields);
        updateFormFields();
    }

    if (qrCodeInput && qrCodePreview) {
        qrCodeInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    qrCodePreview.innerHTML = '<img src="' + e.target.result + '" alt="收款码预览">';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    var form = document.getElementById('withdrawForm');
    var isSubmitting = false;
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (isSubmitting) {
                alert('正在提交中，请勿重复点击');
                return;
            }
            isSubmitting = true;
            var btn = form.querySelector('button[type="submit"]');
            var originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '提交中...';
            
            var formData = new FormData(form);
            formData.set('_ajax', '1');
            
            fetch('<?php echo Mirai_payBuildApiUrl("balance_withdraw_create"); ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    alert(data.msg || '提现申请已提交');
                    window.location.reload();
                } else {
                    alert(data.msg || '提交失败');
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
    
    document.querySelectorAll('.cancel-withdraw').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('确定取消该提现申请吗？')) return;
            
            var id = el.getAttribute('data-id');
            var formData = new FormData();
            formData.set('id', id);
            formData.set('token', '<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>');
            formData.set('_ajax', '1');
            
            fetch('<?php echo Mirai_payBuildApiUrl("balance_withdraw_cancel"); ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    alert(data.msg || '已取消');
                    window.location.reload();
                } else {
                    alert(data.msg || '取消失败');
                }
            })
            .catch(function(err) {
                alert('网络错误，请稍后重试');
            });
        });
    });
})();
</script>
