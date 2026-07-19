<?php
/**
 * Mirai VIP 会员模块
 * 
 * 统一处理所有VIP会员相关功能：
 * - VIP等级和价格配置
 * - VIP状态检查和获取
 * - VIP购买/续费/升级价格计算
 * - VIP订单处理
 * - VIP到期提醒
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

define('MIRAI_VIP_TIME_MONTH', 30);
define('MIRAI_VIP_TIME_QUARTER', 90);
define('MIRAI_VIP_TIME_HALF_YEAR', 180);
define('MIRAI_VIP_TIME_YEAR', 365);
define('MIRAI_VIP_TIME_PERMANENT', 0);

function Mirai_vipGetOption($key, $default = '') {
    $options = Mirai_opt();
    return isset($options->$key) && $options->$key !== '' ? $options->$key : $default;
}

function Mirai_vipEnabled() {
    return Mirai_vipGetOption('vipEnable', '1') === '1';
}

function Mirai_vipGetPurchaseMethod() {
    return Mirai_vipGetOption('vipPurchaseMethod', 'both');
}

function Mirai_vipGetLevelsCount() {
    return 3;
}

function Mirai_vipGetName($level) {
    $level = (int)$level;
    $name = Mirai_vipGetOption("vipName_{$level}", '');
    if (!empty($name)) {
        return $name;
    }
    $names = ['', '一级会员', '二级会员', '三级会员'];
    return isset($names[$level]) ? $names[$level] : "会员 {$level}";
}

function Mirai_vipGetDesc($level) {
    $level = (int)$level;
    
    $desc = Mirai_vipGetOption("vipDesc_{$level}", '');
    if (!empty($desc)) {
        if (preg_match('/<[a-zA-Z][^>]*>/', $desc)) {
            $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><span><div><a>';
            $desc = strip_tags($desc, $allowedTags);
            $desc = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $desc);
            $desc = preg_replace('/javascript\s*:/i', '', $desc);
            return $desc;
        }
        $lines = array_filter(array_map('trim', explode("\n", $desc)));
        if (count($lines) > 1) {
            $html = '<ul class="vip-desc-list">';
            foreach ($lines as $line) {
                $html .= '<li>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $html .= '</ul>';
            return $html;
        }
        return htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
    }
    
    $defaultDescs = [
        1 => '基础会员特权，享专属折扣',
        2 => '高级会员特权，享更多折扣',
        3 => '最高级会员特权，全站免费畅读'
    ];
    
    return isset($defaultDescs[$level]) ? $defaultDescs[$level] : '会员特权';
}

function Mirai_vipGetPrice($level, $time) {
    $level = (int)$level;
    $time = (int)$time;

    $priceKey = "vipPrice_{$level}_{$time}";
    $price = Mirai_vipGetOption($priceKey, '');

    if ($price !== '' && is_numeric($price) && $price > 0) {
        return (float)$price;
    }

    // 默认价格表
    $defaultPrices = [
        1 => [30 => 10, 90 => 25, 180 => 50, 365 => 100, 0 => 299],
        2 => [30 => 20, 90 => 50, 180 => 100, 365 => 200, 0 => 499],
        3 => [30 => 30, 90 => 75, 180 => 150, 365 => 300, 0 => 699]
    ];

    return isset($defaultPrices[$level][$time]) ? $defaultPrices[$level][$time] : 0;
}

function Mirai_vipGetRenewPrice($level, $time) {
    $level = (int)$level;
    $time = (int)$time;
    
    $basePrice = Mirai_vipGetPrice($level, $time);
    if ($basePrice <= 0) {
        return 0;
    }
    
    $renewDiscount = (float)Mirai_vipGetOption('vipRenewDiscount', '0.9');
    $renewDiscount = max(0.1, min(1.0, $renewDiscount));
    $renewPrice = round($basePrice * $renewDiscount, 2);
    
    return max(0.01, $renewPrice);
}

function Mirai_vipGetUpgradePrice($currentLevel, $targetLevel, $targetTime = 30, $remainingDays = 0) {
    $currentLevel = (int)$currentLevel;
    $targetLevel = (int)$targetLevel;
    $targetTime = (int)$targetTime;
    $remainingDays = (int)$remainingDays;
    
    // 无效升级
    if ($currentLevel <= 0 || $targetLevel <= $currentLevel) {
        return Mirai_vipGetPrice($targetLevel, $targetTime);
    }
    
    // 永久会员升级
    if ($targetTime === MIRAI_VIP_TIME_PERMANENT) {
        $targetPrice = Mirai_vipGetPrice($targetLevel, 0);
        $currentPrice = Mirai_vipGetPrice($currentLevel, 0);
        return max(0.01, round($targetPrice - $currentPrice, 2));
    }
    
    // 非永久升级，按剩余天数补差价
    if ($remainingDays <= 0) {
        return 0;
    }
    
    // 计算日单价差
    $baseTime = MIRAI_VIP_TIME_MONTH;
    $currentDailyPrice = Mirai_vipGetPrice($currentLevel, $baseTime) / $baseTime;
    $targetDailyPrice = Mirai_vipGetPrice($targetLevel, $baseTime) / $baseTime;
    
    if ($targetDailyPrice <= $currentDailyPrice) {
        return 0;
    }
    
    $dailyPriceDiff = $targetDailyPrice - $currentDailyPrice;
    $upgradePrice = round($remainingDays * $dailyPriceDiff, 2);
    
    return max(0.01, $upgradePrice);
}

function Mirai_vipGetUserRawData($uid) {
    static $cache = [];
    $uid = (int)$uid;
    
    if ($uid <= 0) {
        return null;
    }
    
    if (isset($cache[$uid])) {
        return $cache[$uid];
    }
    
    $db = \Typecho\Db::get();
    $user = $db->fetchRow($db->select('vip_level', 'vip_exp_date')->from('table.users')->where('uid = ?', $uid));
    
    $cache[$uid] = $user ?: null;
    return $cache[$uid];
}

function Mirai_vipGetUserInfo($uid) {
    static $cache = [];
    $uid = (int)$uid;
    if (isset($cache[$uid])) {
        return $cache[$uid];
    }

    $info = [
        'level' => 0,
        'exp_date' => '',
        'is_permanent' => false,
        'is_expired' => false,
        'days_remaining' => 0,
        'purchase_type' => 'new'
    ];
    
    if ($uid <= 0) {
        return $info;
    }
    
    $user = Mirai_vipGetUserRawData($uid);
    
    if (!$user) {
        return $info;
    }
    
    $info['level'] = (int)$user['vip_level'];
    $info['exp_date'] = $user['vip_exp_date'] ?? '';
    $info['is_permanent'] = ($info['exp_date'] === 'Permanent');
    
    if ($info['level'] > 0 && !$info['is_permanent'] && !empty($info['exp_date'])) {
        $expTimestamp = strtotime($info['exp_date']);
        if ($expTimestamp < time()) {
            $info['is_expired'] = true;
            $info['level'] = 0;
        } else {
            $info['days_remaining'] = ceil(($expTimestamp - time()) / 86400);
        }
    }
    
    if ($info['level'] > 0 && !$info['is_expired']) {
        $info['purchase_type'] = 'renew';
    }

    $cache[$uid] = $info;
    return $info;
}

function Mirai_vipCheckUserValid($uid) {
    static $cache = [];
    $uid = (int)$uid;
    if (isset($cache[$uid])) {
        return $cache[$uid];
    }

    $result = [
        'level' => 0,
        'is_permanent' => false,
        'is_valid' => false
    ];
    
    if ($uid <= 0) {
        return $result;
    }
    
    $user = Mirai_vipGetUserRawData($uid);
    
    if (!$user) {
        return $result;
    }
    
    $vipLevel = (int)($user['vip_level'] ?? 0);
    $vipExpDate = $user['vip_exp_date'] ?? '';
    $isPermanent = ($vipExpDate === 'Permanent');
    $isValid = $isPermanent || ($vipLevel > 0 && !empty($vipExpDate) && strtotime($vipExpDate) > time());
    
    $result['level'] = $vipLevel;
    $result['is_permanent'] = $isPermanent;
    $result['is_valid'] = $isValid;

    $cache[$uid] = $result;
    return $result;
}

function Mirai_vipGetUserLevel($uid) {
    $uid = (int)$uid;
    if ($uid <= 0) {
        return 0;
    }
    $vipStatus = Mirai_vipCheckUserValid($uid);
    return $vipStatus['is_valid'] ? $vipStatus['level'] : 0;
}

function Mirai_vipCheckExpired($uid) {
    $uid = (int)$uid;
    if ($uid <= 0) {
        return false;
    }
    
    static $checked = [];
    if (isset($checked[$uid])) {
        return $checked[$uid];
    }
    
    $user = Mirai_vipGetUserRawData($uid);
    
    if (!$user) {
        $checked[$uid] = false;
        return false;
    }
    
    $vipLevel = (int)($user['vip_level'] ?? 0);
    $vipExpDate = $user['vip_exp_date'] ?? '';
    
    if ($vipLevel <= 0) {
        $checked[$uid] = false;
        return false;
    }
    
    if ($vipExpDate === 'Permanent') {
        $checked[$uid] = false;
        return false;
    }
    
    if (empty($vipExpDate) || strtotime($vipExpDate) < time()) {
        $db = \Typecho\Db::get();
        $db->query($db->update('table.users')->rows(['vip_level' => 0])->where('uid = ?', $uid));
        $checked[$uid] = true;
        return true;
    }
    
    $checked[$uid] = false;
    return false;
}

function Mirai_vipGetPurchaseType($uid, $targetLevel) {
    $uid = (int)$uid;
    $targetLevel = (int)$targetLevel;
    
    $vipInfo = Mirai_vipGetUserInfo($uid);
    $currentLevel = $vipInfo['level'];
    $isExpired = $vipInfo['is_expired'];
    $remainingDays = $vipInfo['days_remaining'] ?? 0;
    
    if ($isExpired || $currentLevel <= 0) {
        return [
            'type' => 'new',
            'price_func' => 'Mirai_vipGetPrice',
            'current_level' => 0,
            'remaining_days' => 0
        ];
    }
    
    if ($currentLevel < $targetLevel) {
        return [
            'type' => 'upgrade',
            'price_func' => 'Mirai_vipGetUpgradePrice',
            'current_level' => $currentLevel,
            'remaining_days' => $remainingDays
        ];
    }
    
    if ($currentLevel === $targetLevel) {
        return [
            'type' => 'renew',
            'price_func' => 'Mirai_vipGetRenewPrice',
            'current_level' => $currentLevel,
            'remaining_days' => $remainingDays
        ];
    }
    
    return [
        'type' => 'new',
        'price_func' => 'Mirai_vipGetPrice',
        'current_level' => $currentLevel,
        'remaining_days' => $remainingDays
    ];
}

function Mirai_vipCalculatePrice($uid, $targetLevel, $time) {
    $purchaseType = Mirai_vipGetPurchaseType($uid, $targetLevel);
    
    switch ($purchaseType['type']) {
        case 'upgrade':
            return Mirai_vipGetUpgradePrice($purchaseType['current_level'], $targetLevel, $time, $purchaseType['remaining_days']);
        case 'renew':
            return Mirai_vipGetRenewPrice($targetLevel, $time);
        default:
            return Mirai_vipGetPrice($targetLevel, $time);
    }
}

function Mirai_vipShouldNotifyExpire($uid) {
    $uid = (int)$uid;
    if ($uid <= 0) {
        return false;
    }
    
    $notifyDays = (int)Mirai_vipGetOption('vipExpireNotifyDays', '7');
    if ($notifyDays <= 0) {
        return false;
    }
    
    $vipInfo = Mirai_vipGetUserInfo($uid);
    if ($vipInfo['level'] <= 0 || $vipInfo['is_permanent'] || $vipInfo['is_expired']) {
        return false;
    }
    
    return $vipInfo['days_remaining'] <= $notifyDays;
}

function Mirai_vipProcessOrderPaid($uid, $payVipLevel, $payVipTime, $purchaseType) {
    $uid = (int)$uid;
    $payVipLevel = (int)$payVipLevel;
    $payVipTime = (int)$payVipTime;
    $purchaseType = (string)$purchaseType;
    
    if ($uid <= 0 || $payVipLevel <= 0) {
        return false;
    }
    
    $db = \Typecho\Db::get();
    $user = $db->fetchRow($db->select('vip_level', 'vip_exp_date')->from('table.users')->where('uid = ?', $uid));
    
    if (!$user) {
        return false;
    }
    
    $now = \Typecho\Date::time();
    $currentDate = date("Y-m-d H:i:s", $now);
    $userVipExpDate = !empty($user['vip_exp_date']) ? $user['vip_exp_date'] : $currentDate;
    $userVipLevel = (int)$user['vip_level'];
    
    // 计算新的VIP状态
    $newVipExpDate = $userVipExpDate;
    $finalLevel = max($payVipLevel, $userVipLevel);
    
    if ($purchaseType === 'upgrade_permanent') {
        // 升级为永久会员
        $newVipExpDate = 'Permanent';
        $finalLevel = $payVipLevel;
    } elseif ($purchaseType === 'upgrade' && $userVipLevel > 0 && $userVipLevel < $payVipLevel) {
        // 普通升级（非永久）：保留剩余天数，只提升等级
        $finalLevel = $payVipLevel;
        if ($userVipExpDate === 'Permanent') {
            // 如果已经是永久会员，保持永久
            $newVipExpDate = 'Permanent';
        } elseif (strtotime($userVipExpDate) > $now) {
            // 未过期，保留原到期时间（按天数补差价，不延长时间）
            $newVipExpDate = $userVipExpDate;
        } else {
            // 已过期，重新计算（这种情况不应该发生，但做兼容处理）
            $newVipExpDate = date("Y-m-d 23:59:59", strtotime("+$payVipTime days"));
        }
    } elseif ($userVipExpDate === 'Permanent') {
        // 已经是永久会员，购买其他套餐（非升级）
        $newVipExpDate = 'Permanent';
        $finalLevel = max($payVipLevel, $userVipLevel);
    } elseif ($payVipTime === 0) {
        // 新购永久会员
        $newVipExpDate = 'Permanent';
        $finalLevel = $payVipLevel;
    } else {
        // 新购或续费（非永久）
        $finalLevel = max($payVipLevel, $userVipLevel);
        
        if (strtotime($userVipExpDate) < $now) {
            $userVipExpDate = $currentDate;
        }
        $newVipExpDate = date("Y-m-d 23:59:59", strtotime("+$payVipTime days", strtotime($userVipExpDate)));
    }
    
    // 更新用户VIP状态
    $db->query($db->update('table.users')->rows([
        'vip_level' => $finalLevel,
        'vip_exp_date' => $newVipExpDate
    ])->where('uid = ?', $uid));
    
    return true;
}

function Mirai_vipGetDiscountMode() {
    return Mirai_vipGetOption('vipDiscountMode', 'percent');
}

function Mirai_vipGetDiscountValue($level) {
    $level = (int)$level;
    
    if ($level === 3) {
        $mode = Mirai_vipGetOption('vipDiscount_3', 'free');
        if ($mode === 'free') {
            return 0; // 免费阅读
        }
        return (float)Mirai_vipGetOption('vipDiscount_3_value', '0');
    }
    
    return (float)Mirai_vipGetOption("vipDiscount_{$level}", '50');
}

function Mirai_vipApplyDiscount($originalPrice, $vipLevel, $discountMode = 'default', $customPrices = []) {
    $originalPrice = (float)$originalPrice;
    $vipLevel = (int)$vipLevel;
    
    if ($vipLevel <= 0 || $originalPrice <= 0) {
        return $originalPrice;
    }
    
    if ($discountMode === 'free_for_vip') {
        return 0;
    }
    
    if ($discountMode === 'no_discount') {
        return $originalPrice;
    }
    
    if ($discountMode === 'custom') {
        if (isset($customPrices[$vipLevel]) && $customPrices[$vipLevel] !== null) {
            if ($customPrices[$vipLevel] <= 0) {
                return 0;
            }
            return min($customPrices[$vipLevel], $originalPrice);
        }
    }
    
    if ($vipLevel === 3 && Mirai_vipGetOption('vipDiscount_3', 'free') === 'free') {
        return 0;
    }
    
    $globalDiscountMode = Mirai_vipGetDiscountMode();
    $discountValue = Mirai_vipGetDiscountValue($vipLevel);
    
    if ($globalDiscountMode === 'percent') {
        $discountValue = max(0, min(100, $discountValue));
        return round($originalPrice * $discountValue / 100, 2);
    } else {
        $discountValue = max(0, $discountValue);
        return max(0, round($originalPrice - $discountValue, 2));
    }
}

function Mirai_vipValidateOrderParams($uid, $vipLevel, $vipTime, $vipPurchaseType = '') {
    $uid = (int)$uid;
    $vipLevel = (int)$vipLevel;
    $vipTime = (int)$vipTime;
    $vipPurchaseType = trim((string)$vipPurchaseType);
    
    $result = [
        'valid' => false,
        'error' => '',
        'amount' => 0,
        'purchase_type' => 'new',
        'meta' => []
    ];
    
    if ($uid <= 0) {
        $result['error'] = '请先登录';
        return $result;
    }
    
    Mirai_vipCheckExpired($uid);
    
    $purchaseType = Mirai_vipGetPurchaseType($uid, $vipLevel);
    
    if ($purchaseType['type'] === 'new' && $purchaseType['current_level'] > $vipLevel) {
        $result['error'] = '无法购买低于当前等级的会员';
        return $result;
    }
    
    if (!empty($vipPurchaseType) && in_array($vipPurchaseType, ['upgrade', 'upgrade_permanent'], true)) {
        if ($purchaseType['type'] !== 'upgrade') {
            $result['error'] = '会员状态已变化，请刷新页面重试';
            return $result;
        }
    }
    
    $actualVipTime = $vipTime;
    if ($vipPurchaseType === 'upgrade' && $vipTime < 0) {
        $actualVipTime = MIRAI_VIP_TIME_MONTH;
    }
    
    $amount = Mirai_vipCalculatePrice($uid, $vipLevel, $actualVipTime);
    
    if ($amount <= 0) {
        $result['error'] = '会员购买金额错误或未配置该套餐';
        return $result;
    }
    
    $finalPurchaseType = $purchaseType['type'];
    if (!empty($vipPurchaseType) && in_array($vipPurchaseType, ['new', 'renew', 'upgrade', 'upgrade_permanent'], true)) {
        $finalPurchaseType = $vipPurchaseType;
    }
    
    $vipName = Mirai_vipGetName($vipLevel);
    $typeLabel = [
        'new' => '开通',
        'renew' => '续费',
        'upgrade' => '升级',
        'upgrade_permanent' => '升级永久'
    ];
    $title = ($typeLabel[$finalPurchaseType] ?? '购买') . $vipName . ($actualVipTime === 0 ? '（永久）' : ($actualVipTime > 0 ? "（{$actualVipTime}天）" : ''));
    
    $result['valid'] = true;
    $result['amount'] = $amount;
    $result['purchase_type'] = $finalPurchaseType;
    $result['meta'] = [
        'vip_level' => $vipLevel,
        'vip_time' => $actualVipTime,
        'purchase_type' => $finalPurchaseType,
        'current_level' => $purchaseType['current_level'],
        'title' => $title
    ];
    
    return $result;
}

function Mirai_vipGetAvailableTimeOptions($level) {
    $level = (int)$level;
    $options = [];
    
    $timeConfigs = [
        MIRAI_VIP_TIME_MONTH => '包月',
        MIRAI_VIP_TIME_QUARTER => '季度',
        MIRAI_VIP_TIME_HALF_YEAR => '半年',
        MIRAI_VIP_TIME_YEAR => '包年',
        MIRAI_VIP_TIME_PERMANENT => '永久'
    ];
    
    foreach ($timeConfigs as $time => $label) {
        $price = Mirai_vipGetPrice($level, $time);
        if ($price > 0) {
            $options[] = [
                'time' => $time,
                'label' => $label,
                'price' => $price
            ];
        }
    }
    
    return $options;
}