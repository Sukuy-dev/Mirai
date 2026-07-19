<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php if (isset($this->options->displayReward) && $this->options->displayReward): ?>
<?php
    $rewardWechat = $this->options->rewardWechat ? Mirai_normalizeUrl($this->options->rewardWechat) : '';
    $rewardAlipay = $this->options->rewardAlipay ? Mirai_normalizeUrl($this->options->rewardAlipay) : '';
?>
<div class="reward-modal" id="rewardModal" onclick="closeRewardModal(event)">
    <div class="reward-modal-content">
        <div class="reward-modal-header">
            <div class="sky-h3">打赏杯咖啡或蜜雪冰城吧</div>
            <button class="reward-close-btn" onclick="closeRewardModal(event, true)">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div class="reward-modal-body">
            <div class="reward-qrcodes">
                <?php if ($rewardWechat): ?>
                <div class="reward-qrcode-item">
                    <div class="reward-qrcode-label">微信扫一扫</div>
                    <div class="reward-qrcode-box wechat">
                        <img src="<?php echo $rewardWechat; ?>" alt="微信赞赏码">
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($rewardAlipay): ?>
                <div class="reward-qrcode-item">
                    <div class="reward-qrcode-label">支付宝扫一扫</div>
                    <div class="reward-qrcode-box alipay">
                        <img src="<?php echo $rewardAlipay; ?>" alt="支付宝赞赏码">
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>