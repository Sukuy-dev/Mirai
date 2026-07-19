<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$uid = (int)$this->user->uid;
Mirai_vipCheckExpired($uid);
$vipInfo = Mirai_vipGetUserInfo($uid);
$vipLevel = $vipInfo['level'];
$vipExpDate = $vipInfo['exp_date'];
$isPermanent = $vipInfo['is_permanent'];
$isExpired = $vipInfo['is_expired'];
$daysRemaining = $vipInfo['days_remaining'];
$shouldNotifyExpire = Mirai_vipShouldNotifyExpire($uid);

$options = \Typecho\Widget::widget('Widget_Options');
$vipEnable = true;
if (isset($options->vipEnable) && $options->vipEnable === '0') {
    $vipEnable = false;
}
$vipLevelsCount = 3;
$renewDiscount = (float)Mirai_payGetOption('vipRenewDiscount', '0.9');
$vipTopBenefit = isset($options->vipDiscount_3) ? $options->vipDiscount_3 : 'free';
$vipTopBenefitText = ($vipTopBenefit === 'free') ? '全站内容免费畅读' : '全站内容享受专属折扣';

$timeUnits = [
    30 => '包月',
    90 => '季度',
    180 => '半年',
    365 => '年度',
    0 => '永久'
];

$vipData = [];
for ($i = 1; $i <= $vipLevelsCount; $i++) {
    $vipData[$i] = [
        'name' => Mirai_vipGetName($i),
        'desc' => Mirai_vipGetDesc($i),
        'prices' => []
    ];
    foreach ($timeUnits as $days => $label) {
        $basePrice = Mirai_vipGetPrice($i, $days);
        if ($basePrice > 0) {
            $renewPrice = Mirai_vipGetRenewPrice($i, $days);
            $upgradePrice = ($vipLevel > 0 && !$isExpired && $vipLevel < $i) ? Mirai_vipGetUpgradePrice($vipLevel, $i, $days, $daysRemaining) : 0;
            $vipData[$i]['prices'][$days] = [
                'label' => $label,
                'base' => $basePrice,
                'renew' => $renewPrice,
                'upgrade' => $upgradePrice
            ];
        }
    }
}

$currentVipName = $vipLevel > 0 && isset($vipData[$vipLevel]) ? $vipData[$vipLevel]['name'] : '';

$onlineMethods = Mirai_payAvailableOnlineChannels();
$allPayMethods = Mirai_payMethods();
$methodData = [];
$iconBaseUrl = Mirai_getThemeUrl() . '/assets/images/';
$iconMap = ['wechat' => 'wechat-pay.svg', 'alipay' => 'alipay.svg', 'qq' => 'QQ-Pay.svg', 'balance' => 'balance.svg'];
foreach ($allPayMethods as $method) {
    $iconFile = isset($iconMap[$method]) ? $iconMap[$method] : '';
    $methodData[] = [
        'value' => (string)$method,
        'label' => Mirai_payMethodLabel($method),
        'icon' => $iconFile ? ($iconBaseUrl . $iconFile) : ''
    ];
}
$defaultMethod = !empty($onlineMethods) ? $onlineMethods[0] : 'balance';

$userHasVip = ($vipLevel > 0 && !$isExpired);
$userCanUpgrade = ($userHasVip && $vipLevel < $vipLevelsCount);
$userCanRenew = ($userHasVip && !$isPermanent);
$userIsTopVip = ($userHasVip && $vipLevel >= $vipLevelsCount);

$showPurchaseSection = $vipEnable && (!$userIsTopVip || !$isPermanent || $isExpired);
?>
<div class="user-module module-vip">
    <div class="module-header">
        <div class="module-title">会员中心</div>
    </div>

    <div class="vip-status-card <?php echo $vipLevel > 0 ? 'is-vip level-' . $vipLevel : ''; ?>">
        <div class="vip-status-body">
            <?php if ($userHasVip): ?>
                <div class="vip-status-main">
                    <span class="vip-badge level-<?php echo $vipLevel; ?>"><i class="ri-vip-crown-2-fill"></i> <?php echo htmlspecialchars($currentVipName); ?></span>
                    <?php if ($isPermanent): ?>
                        <span class="vip-exp-tag permanent">长期有效</span>
                    <?php else: ?>
                        <span class="vip-exp-tag">有效时间：<?php echo date('Y-m-d', strtotime($vipExpDate)); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($userCanUpgrade || $userCanRenew): ?>
                <div class="vip-status-hints">
                    <?php if ($userCanUpgrade): ?>
                        <span class="hint-upgrade"><i class="ri-lightbulb-line"></i> 升级按剩余天数补差价，到期时间不变</span>
                    <?php endif; ?>
                    <?php if ($userCanRenew): ?>
                        <span class="hint-renew"><i class="ri-gift-line"></i> 续费享 <?php echo round($renewDiscount * 10, 1); ?> 折</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="vip-status-main">
                    <span class="vip-badge none"><i class="ri-user-line"></i> 普通用户</span>
                </div>
                <p class="vip-exp">开通会员，畅享专属特权与折扣</p>
            <?php endif; ?>
        </div>
        <?php if ($shouldNotifyExpire && !$isPermanent): ?>
            <a href="#vip-plans" class="btn btn-warning btn-sm">即将到期，续费</a>
        <?php elseif (!$userHasVip && $vipEnable): ?>
            <a href="#vip-plans" class="btn btn-primary btn-sm">立即开通</a>
        <?php endif; ?>
    </div>

    <?php if ($shouldNotifyExpire && !$isPermanent): ?>
    <div class="vip-expire-notice">
        <i class="ri-alarm-warning-line"></i>
        您的会员将于 <strong><?php echo $daysRemaining; ?></strong> 天后到期（<?php echo date('Y年m月d日', strtotime($vipExpDate)); ?>），请及时续费以继续享受会员特权。
    </div>
    <?php endif; ?>

    <?php if ($showPurchaseSection && $vipEnable): ?>
    <div id="vip-plans" class="module-header vip-plans-title">
        <div class="module-title">
            <?php if ($isExpired): ?>
                重新开通会员
            <?php elseif ($userCanUpgrade): ?>
                升级 / 续费会员
            <?php elseif ($userCanRenew): ?>
                续费会员
            <?php else: ?>
                开通会员
            <?php endif; ?>
        </div>
    </div>

    <div class="vip-plans">
        <?php foreach ($vipData as $level => $data):
            $isCurrentLevel = ($vipLevel === $level && !$isExpired);
            $isUpgrade = ($userHasVip && $vipLevel < $level);
            $isRenew = ($vipLevel === $level && !$isExpired);
            $isNew = (!$userHasVip || $isExpired);
            $isDowngrade = ($userHasVip && !$isExpired && $vipLevel > $level);
            $canShow = (!$isCurrentLevel || ($isCurrentLevel && !$isPermanent)) && !$isDowngrade;

            if (!$canShow) continue;
        ?>
        <div class="vip-plan-card <?php echo $level === $vipLevelsCount ? 'premium' : ''; ?> <?php echo $isUpgrade ? 'upgrade' : ''; ?> <?php echo $isRenew ? 'renew' : ''; ?>">
            <div class="plan-header">
                <h4>
                    <?php echo htmlspecialchars($data['name']); ?>
                    <?php if ($isUpgrade): ?>
                        <span class="plan-tag upgrade">升级</span>
                    <?php elseif ($isRenew): ?>
                        <span class="plan-tag renew">续费</span>
                    <?php elseif ($isNew): ?>
                        <span class="plan-tag new">开通</span>
                    <?php endif; ?>
                </h4>
                <div class="plan-desc"><?php echo $data['desc']; ?></div>
            </div>
            <div class="plan-body">
                <div class="plan-options">
                    <?php if ($isUpgrade): ?>
                        <?php
                        $hasUpgradeOptions = false;
                        foreach ($data['prices'] as $upDays => $upPriceInfo):
                            $upPrice = $upPriceInfo['upgrade'];
                            if ($upDays === 0) continue;
                            if ($upPrice <= 0) continue;
                            $hasUpgradeOptions = true;
                            $displayLabel = $upPriceInfo['label'];
                        ?>
                        <label class="plan-option">
                            <input type="radio" name="vip_plan"
                                value="v<?php echo $level; ?>_upgrade_<?php echo $upDays; ?>"
                                data-level="<?php echo $level; ?>"
                                data-level-name="<?php echo htmlspecialchars($data['name']); ?>"
                                data-time="<?php echo $upDays; ?>"
                                data-time-label="<?php echo htmlspecialchars($displayLabel); ?>"
                                data-price="<?php echo $upPrice; ?>"
                                data-type="upgrade"
                                data-original-price="<?php echo $upPriceInfo['base']; ?>">
                            <span class="plan-name">
                                <span class="plan-option-type">补差价</span>
                                <?php echo $displayLabel; ?>
                            </span>
                            <span class="plan-price">￥<?php echo number_format($upPrice, 2); ?></span>
                        </label>
                        <?php endforeach; ?>

                        <?php $permanentUpPrice = Mirai_vipGetUpgradePrice($vipLevel, $level, 0, $daysRemaining); ?>
                        <?php if ($permanentUpPrice > 0): ?>
                        <label class="plan-option plan-option-highlight">
                            <input type="radio" name="vip_plan"
                                value="v<?php echo $level; ?>_permanent"
                                data-level="<?php echo $level; ?>"
                                data-level-name="<?php echo htmlspecialchars($data['name']); ?>"
                                data-time="0"
                                data-time-label="永久"
                                data-price="<?php echo $permanentUpPrice; ?>"
                                data-type="upgrade_permanent"
                                data-original-price="<?php echo $data['prices'][0]['base'] ?? 0; ?>">
                            <span class="plan-name">
                                <span class="plan-option-type highlight">推荐</span>
                                升级为永久会员
                            </span>
                            <span class="plan-price">￥<?php echo number_format($permanentUpPrice, 2); ?></span>
                        </label>
                        <?php endif; ?>

                        <?php if (!$hasUpgradeOptions && $permanentUpPrice <= 0): ?>
                        <div class="plan-empty-hint">暂无可用的升级方案</div>
                        <?php endif; ?>

                    <?php else: ?>
                        <?php foreach ($data['prices'] as $days => $priceInfo):
                            $displayPrice = $isNew ? $priceInfo['base'] : ($isRenew ? $priceInfo['renew'] : $priceInfo['base']);
                            $originalPrice = $priceInfo['base'];
                            $hasDiscount = ($displayPrice < $originalPrice && $originalPrice > 0);
                        ?>
                        <label class="plan-option">
                            <input type="radio" name="vip_plan"
                                value="v<?php echo $level; ?>_<?php echo $days; ?>"
                                data-level="<?php echo $level; ?>"
                                data-level-name="<?php echo htmlspecialchars($data['name']); ?>"
                                data-time="<?php echo $days; ?>"
                                data-time-label="<?php echo htmlspecialchars($priceInfo['label']); ?>"
                                data-price="<?php echo $displayPrice; ?>"
                                data-type="<?php echo $isRenew ? 'renew' : 'new'; ?>"
                                data-original-price="<?php echo $originalPrice; ?>">
                            <span class="plan-name"><?php echo $priceInfo['label']; ?></span>
                            <span class="plan-price-wrap">
                                <?php if ($hasDiscount): ?>
                                    <del class="plan-original-price">￥<?php echo number_format($originalPrice, 2); ?></del>
                                <?php endif; ?>
                                <span class="plan-price">￥<?php echo number_format($displayPrice, 2); ?></span>
                                <?php if ($hasDiscount): ?>
                                    <span class="plan-save-tag">省￥<?php echo number_format($originalPrice - $displayPrice, 2); ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <form class="mirai-pay-form vip-pay-form" method="post"
          action="<?php echo htmlspecialchars(Mirai_payBuildApiUrl('pay_create_order'), ENT_QUOTES, 'UTF-8'); ?>"
          data-pay-methods="<?php echo htmlspecialchars(json_encode($methodData), ENT_QUOTES, 'UTF-8'); ?>"
          data-order-type-label=""
          data-order-title="">
        <input type="hidden" name="token" value="<?php echo \Widget\Security::alloc()->getToken('api'); ?>">
        <input type="hidden" name="order_type" value="vip">
        <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($defaultMethod, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="amount" id="vip_amount" value="">
        <input type="hidden" name="vip_level" id="vip_level" value="">
        <input type="hidden" name="vip_time" id="vip_time" value="">
        <input type="hidden" name="vip_purchase_type" id="vip_purchase_type" value="">

        <div class="vip-submit-area">
            <button type="submit" class="btn btn-primary btn-lg vip-btn-pay" id="btn-pay-vip" disabled>
                <i class="ri-shopping-cart-line"></i> 选择套餐后支付
            </button>
        </div>
    </form>
    <?php elseif ($userIsTopVip && $isPermanent): ?>
    <div class="vip-max-status">
        <div class="vip-max-icon"><i class="ri-vip-crown-2-fill"></i></div>
        <h4><?php echo htmlspecialchars($currentVipName); ?> · 长期有效</h4>
        <p>您已拥有最高级永久会员权益，<?php echo $vipTopBenefitText; ?></p>
    </div>
    <?php endif; ?>
</div>