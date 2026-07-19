<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_payFieldsByCid($cid) {
    static $cache = [];
    $cid = (int)$cid;
    if ($cid <= 0) {
        return [];
    }
    if (isset($cache[$cid])) {
        return $cache[$cid];
    }
    $db = \Typecho\Db::get();
    $rows = $db->fetchAll($db->select()->from('table.fields')->where('cid = ?', $cid));
    $data = [];
    foreach ($rows as $row) {
        $name = isset($row['name']) ? (string)$row['name'] : '';
        if ($name === '') {
            continue;
        }
        $value = '';
        if (isset($row['str_value']) && $row['str_value'] !== null && $row['str_value'] !== '') {
            $value = (string)$row['str_value'];
        } elseif (isset($row['int_value']) && $row['int_value'] !== null) {
            $value = (string)$row['int_value'];
        } elseif (isset($row['float_value']) && $row['float_value'] !== null) {
            $value = (string)$row['float_value'];
        }
        $data[$name] = $value;
    }
    $cache[$cid] = $data;
    return $cache[$cid];
}

function Mirai_payPostSettings($cid) {
    static $cache = [];
    $cid = (int)$cid;
    if (isset($cache[$cid])) {
        return $cache[$cid];
    }

    $fields = Mirai_payFieldsByCid($cid);
    $pick = function(array $keys, $default = '') use ($fields) {
        foreach ($keys as $key) {
            if (isset($fields[$key]) && trim((string)$fields[$key]) !== '') {
                return $fields[$key];
            }
        }
        return $default;
    };
    $mode = trim((string)$pick(['pay_mode', 'payMode'], 'none'));
    if (!in_array($mode, ['none', 'read', 'partial'], true)) {
        $mode = 'none';
    }
    $originalPrice = Mirai_payNormalizeAmount($pick(['pay_price', 'payPrice'], 0));
    $commission = (float)$pick(['pay_commission_rate', 'payCommissionRate'], -1);
    $loginRequired = trim((string)$pick(['pay_login_required', 'payLoginRequired'], '1'));
    $purchaseMethod = trim((string)$pick(['pay_purchase_method', 'payPurchaseMethod'], 'both'));
    if (!in_array($purchaseMethod, ['both', 'online', 'balance'], true)) {
        $purchaseMethod = 'both';
    }

    $vipDiscountMode = trim((string)$pick(['pay_vip_discount_mode'], 'default'));
    if (!in_array($vipDiscountMode, ['default', 'custom', 'free_for_vip', 'no_discount'], true)) {
        $vipDiscountMode = 'default';
    }

    $vipCustomPrices = [
        1 => $pick(['pay_vip_1_price'], '') !== '' ? Mirai_payNormalizeAmount($pick(['pay_vip_1_price'], '')) : null,
        2 => $pick(['pay_vip_2_price'], '') !== '' ? Mirai_payNormalizeAmount($pick(['pay_vip_2_price'], '')) : null,
        3 => $pick(['pay_vip_3_price'], '') !== '' ? Mirai_payNormalizeAmount($pick(['pay_vip_3_price'], '')) : null,
    ];

    $user = Mirai_user();
    $price = $originalPrice;
    $vipPrice = null;

    if ($user->hasLogin() && $originalPrice > 0) {
        $vipStatus = Mirai_vipCheckUserValid($user->uid);
        if ($vipStatus['is_valid'] && $vipStatus['level'] > 0) {
            $vipPrice = Mirai_vipApplyDiscount($originalPrice, $vipStatus['level'], $vipDiscountMode, $vipCustomPrices);
            $price = $vipPrice;
        }
    }

    $result = [
        'mode' => $mode,
        'price' => round(max(0, $price), 2),
        'original_price' => round(max(0, $originalPrice), 2),
        'vip_price' => $vipPrice !== null ? round(max(0, $vipPrice), 2) : null,
        'vip_discount_mode' => $vipDiscountMode,
        'vip_1_price' => $vipCustomPrices[1],
        'vip_2_price' => $vipCustomPrices[2],
        'vip_3_price' => $vipCustomPrices[3],
        'commission_rate' => $commission,
        'login_required' => $loginRequired === '1',
        'purchase_method' => $purchaseMethod,
    ];

    $cache[$cid] = $result;
    return $result;
}

function Mirai_payAvailableForPost($settings) {
    if (!is_array($settings)) {
        return false;
    }
    $mode = isset($settings['mode']) ? (string)$settings['mode'] : 'none';
    if ($mode === 'none') {
        return false;
    }
    $price = isset($settings['price']) ? (float)$settings['price'] : 0;
    return $price > 0;
}

function Mirai_payAvailableMethodsForPost($settings, $uid = 0) {
    $uid = (int)$uid;
    $allMethods = Mirai_payMethods();
    $purchaseMethod = isset($settings['purchase_method']) ? (string)$settings['purchase_method'] : 'both';
    $methods = [];
    foreach ($allMethods as $method) {
        if ($purchaseMethod === 'online' && $method === 'balance') {
            continue;
        }
        if ($purchaseMethod === 'balance' && $method !== 'balance') {
            continue;
        }
        if ($uid <= 0 && $method === 'balance') {
            continue;
        }
        $methods[] = $method;
    }
    return array_values(array_unique($methods));
}

function Mirai_payHasPaid($cid, $uid = 0) {
    static $cache = [];
    $cid = (int)$cid;
    $uid = (int)$uid;
    if ($cid <= 0) {
        return false;
    }
    $cacheKey = $cid . '_' . $uid;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    if ($uid > 0 && isset($_COOKIE[Mirai_payCookieKey($cid)])) {
        $cache[$cacheKey] = true;
        return true;
    }

    $db = \Typecho\Db::get();

    if ($uid > 0) {
        $vipStatus = Mirai_vipCheckUserValid($uid);
        if ($vipStatus['is_valid'] && $vipStatus['level'] > 0) {
            $settings = Mirai_payPostSettings($cid);
            $vipDiscountMode = isset($settings['vip_discount_mode']) ? $settings['vip_discount_mode'] : 'default';
            $originalPrice = isset($settings['original_price']) ? (float)$settings['original_price'] : 0;

            if ($originalPrice > 0) {
                if ($vipDiscountMode === 'free_for_vip') {
                    $cache[$cacheKey] = true;
                    return true;
                }
                
                if ($vipDiscountMode === 'no_discount') {
                } else {
                    $vipCustomPrices = [
                        1 => Mirai_payNormalizeAmount($settings['vip_1_price'] ?? 0),
                        2 => Mirai_payNormalizeAmount($settings['vip_2_price'] ?? 0),
                        3 => Mirai_payNormalizeAmount($settings['vip_3_price'] ?? 0),
                    ];
                    $vipPrice = Mirai_vipApplyDiscount($originalPrice, $vipStatus['level'], $vipDiscountMode, $vipCustomPrices);
                    if ($vipPrice <= 0) {
                        $cache[$cacheKey] = true;
                        return true;
                    }
                }
            }
        }
    }

    $ordersTable = Mirai_payTable('orders');
    if (!Mirai_payDbCheck()) {
        $cache[$cacheKey] = false;
        return false;
    }
    if ($uid > 0) {
        $row = $db->fetchRow($db->select('id')->from($ordersTable)->where('cid = ?', $cid)->where('uid = ?', $uid)->where('status = ?', 'paid')->limit(1));
        $cache[$cacheKey] = !empty($row);
        return $cache[$cacheKey];
    }
    $guestToken = Mirai_payGuestToken();
    $currentIp = Mirai_getClientIp();
    if ($currentIp === '') {
        $cache[$cacheKey] = false;
        return false;
    }
    $orders = $db->fetchAll($db->select('meta')->from($ordersTable)->where('cid = ?', $cid)->where('guest_token = ?', $guestToken)->where('status = ?', 'paid'));
    $hasTokenMatch = false;
    foreach ($orders as $order) {
        $meta = Mirai_payOrderMeta($order);
        $orderIp = isset($meta['ip']) ? (string)$meta['ip'] : '';
        $hasTokenMatch = true;
        if ($orderIp !== '' && $orderIp === $currentIp) {
            $cache[$cacheKey] = true;
            return true;
        }
    }
    if ($hasTokenMatch) {
        $cache[$cacheKey] = true;
        return true;
    }
    $cache[$cacheKey] = false;
    return false;
}

function Mirai_payUnwrapShortcode($content) {
    return preg_replace('/\[pay\]([\s\S]*?)\[\/pay\]/i', '$1', (string)$content);
}

function Mirai_payFilterPostContent($content, $widget, $canAccess) {
    $content = (string)$content;
    $rawContent = $content;
    $settings = Mirai_payPostSettings($widget->cid);
    if (!$canAccess && isset($widget->user, $widget->authorId) && (int)$widget->user->uid > 0 && (int)$widget->user->uid === (int)$widget->authorId) {
        $canAccess = true;
    }
    $plainContent = Mirai_payUnwrapShortcode($content);
    $inlineToken = '<!--MIRAI_PAYBOX_INLINE-->';
    if (!Mirai_payAvailableForPost($settings)) {
        return ['content' => $plainContent, 'locked' => false, 'settings' => $settings, 'inline' => false];
    }
    if ($canAccess) {
        return ['content' => $plainContent, 'locked' => false, 'settings' => $settings, 'inline' => false];
    }
    if ($settings['mode'] === 'partial') {
        if (preg_match('/\[pay\][\s\S]*?\[\/pay\]/i', $rawContent)) {
            $replaceFirst = '<div class="mirai-pay-lock-placeholder"></div>' . $inlineToken;
            $content = preg_replace('/\[pay\][\s\S]*?\[\/pay\]/i', $replaceFirst, $rawContent, 1);
            $content = preg_replace('/\[pay\][\s\S]*?\[\/pay\]/i', '<div class="mirai-pay-lock-placeholder"></div>', $content);
        } else {
            return ['content' => $plainContent, 'locked' => false, 'settings' => $settings, 'inline' => false];
        }
        return ['content' => $content, 'locked' => true, 'settings' => $settings, 'inline' => true];
    }
    if ($settings['mode'] === 'read') {
        $content = $inlineToken;
        return ['content' => $content, 'locked' => true, 'settings' => $settings, 'inline' => true];
    }
    return ['content' => $plainContent, 'locked' => false, 'settings' => $settings, 'inline' => false];
}

function Mirai_payRenderBoxHead($settings, $layout, $title = '') {
    $html = '<div class="mirai-paybox mirai-paybox-' . htmlspecialchars($layout, ENT_QUOTES, 'UTF-8') . '">';
    if ($title !== '') {
        $html .= '<div class="mirai-paybox-title">' . $title . '</div>';
    }
    return $html;
}

function Mirai_payRenderPurchaseBox($widget, $settings, $uid, $token, $layout = 'default') {
    if (!Mirai_payAvailableForPost($settings)) {
        return '';
    }
    if ($uid > 0 && isset($widget->authorId) && (int)$widget->authorId === (int)$uid) {
        return '';
    }
    $hasPaid = Mirai_payHasPaid($widget->cid, $uid);
    $balance = $uid > 0 ? (float)Mirai_payGetWallet($uid)['balance'] : 0;
    $methods = Mirai_payAvailableMethodsForPost($settings, $uid);
    $displayPrice = max(0, Mirai_payNormalizeAmount(isset($settings['price']) ? $settings['price'] : 0));
    $payNotice = trim((string)Mirai_payGetOption('payNotice', ''));
    
    $options = Mirai_opt();
    $frontendLoginEnabled = Mirai_isUserCenterAuthEnabled($options);
    
    if ($hasPaid) {
        $html = Mirai_payRenderBoxHead($settings, $layout, '你已购买当前内容');
    } else {
        if ($frontendLoginEnabled) {
            $loginBtn = '<button type="button" class="btn btn-primary mirai-paybox-submit" style="width:auto;min-width:120px;padding:0 24px;" onclick="if(window.openLoginModal){openLoginModal();}return false;">立即登录</button>';
        } else {
            $loginBtn = '<p class="mirai-paybox-login-disabled">当前站点未启用登录功能，请联系站长</p>';
        }

        $needLoginCases = [
            ['condition' => $settings['login_required'] && $uid <= 0, 'title' => '该内容需要登录后购买'],
            ['condition' => $settings['purchase_method'] === 'balance' && $uid <= 0, 'title' => '该文章仅支持余额购买，请先登录'],
            ['condition' => $uid <= 0 && !Mirai_payGuestAllowed(), 'title' => '当前仅支持登录用户购买'],
        ];

        foreach ($needLoginCases as $case) {
            if ($case['condition']) {
                $html = Mirai_payRenderBoxHead($settings, $layout, $case['title']);
                $html .= '<div class="mirai-paybox-actions">';
                if ($payNotice !== '') {
                    $html .= '<div class="mirai-paybox-desc">' . nl2br(htmlspecialchars($payNotice, ENT_QUOTES, 'UTF-8')) . '</div>';
                }
                $html .= $loginBtn;
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            }
        }

        if ($settings['mode'] === 'read') {
            $boxTitle = '本文为付费阅读内容';
        } else {
            $boxTitle = '此处内容需付费后阅读';
        }
        
        $db = \Typecho\Db::get();
        $isVipDiscount = false;
        $vipPriceDisplay = null;
        $originalPrice = isset($settings['original_price']) ? (float)$settings['original_price'] : $displayPrice;
        $vipDiscountMode = isset($settings['vip_discount_mode']) ? $settings['vip_discount_mode'] : 'default';

        if ($uid > 0) {
            $preCalculatedVipPrice = isset($settings['vip_price']) ? (float)$settings['vip_price'] : null;
            if ($preCalculatedVipPrice !== null && $preCalculatedVipPrice < $originalPrice) {
                $isVipDiscount = true;
                $vipPriceDisplay = $preCalculatedVipPrice;
            }
        }
        
        $html = Mirai_payRenderBoxHead($settings, $layout, $boxTitle);

        $iconBaseUrl = Mirai_getThemeUrl() . '/assets/images/';
        $methodData = [];
        foreach ($methods as $method) {
            $iconMap = [
                'wechat' => 'wechat-pay.svg',
                'alipay' => 'alipay.svg',
                'qq' => 'QQ-Pay.svg',
                'balance' => 'balance.svg'
            ];
            $iconFile = isset($iconMap[$method]) ? $iconMap[$method] : '';
            $methodData[] = [
                'value' => $method,
                'label' => Mirai_payMethodLabel($method),
                'icon' => $iconFile ? ($iconBaseUrl . $iconFile) : ''
            ];
        }
        $orderTypeLabel = $settings['mode'] === 'read' ? '付费阅读' : '付费内容';
        $orderTitle = isset($widget->title) ? $widget->title : '付费内容';
        $html .= '<form class="mirai-pay-form mirai-pay-form-post" method="post" action="' . htmlspecialchars(Mirai_payBuildApiUrl('pay_create_order'), ENT_QUOTES, 'UTF-8') . '" target="_self" data-pay-methods="' . htmlspecialchars(json_encode($methodData), ENT_QUOTES, 'UTF-8') . '" data-order-type-label="' . htmlspecialchars($orderTypeLabel, ENT_QUOTES, 'UTF-8') . '" data-order-title="' . htmlspecialchars($orderTitle, ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<input type="hidden" name="cid" value="' . (int)$widget->cid . '">';
        $html .= '<input type="hidden" name="order_type" value="' . htmlspecialchars($settings['mode'], ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<input type="hidden" name="payment_method" value="' . htmlspecialchars(isset($methods[0]) ? (string)$methods[0] : '', ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<input type="hidden" name="amount" value="' . htmlspecialchars(number_format($displayPrice, 2, '.', ''), ENT_QUOTES, 'UTF-8') . '">';
        if (empty($methods)) {
            $html .= '<div class="mirai-paybox-empty">当前文章暂无可用支付方式</div>';
            $html .= '</form>';
            $html .= '</div>';
            return $html;
        }
        $finalPrice = ($isVipDiscount && $vipPriceDisplay !== null) ? $vipPriceDisplay : $displayPrice;
        $html .= '<div class="mirai-paybox-layout">';
        $html .= '<div class="mirai-paybox-price-area">';
        if ($isVipDiscount && $vipPriceDisplay !== null && $vipPriceDisplay >= 0) {
            $html .= '<span class="mirai-paybox-badge"><svg viewBox="0 0 1024 1024" width="14" height="14" class="mirai-paybox-badge-icon"><path d="M306.8928 123.5968h407.7056c27.8016 0 54.1696 12.3904 71.936 33.7408l177.1008 212.992c29.8496 35.8912 28.672 88.32-2.816 122.8288l-377.2416 413.696c-37.12 40.704-101.2736 40.6528-138.3424-0.1024L65.5872 489.1136c-31.232-34.4064-32.512-86.4768-3.0208-122.368l172.1344-209.1008a93.5424 93.5424 0 0 1 72.192-34.048z" fill="#8C7BFD"/><path d="M511.8464 687.0528h-0.2048c-14.2336-0.0512-27.8016-5.7856-37.7856-15.9232L277.9136 471.5008c-20.5824-20.992-20.2752-54.6304 0.6656-75.2128 20.992-20.5824 54.6304-20.2752 75.2128 0.6656l158.3104 161.2288 160.8192-161.4848c20.736-20.8384 54.4256-20.8896 75.264-0.1536 20.8384 20.736 20.8896 54.4256 0.1536 75.264l-198.7584 199.6288c-9.984 9.984-23.552 15.616-37.7344 15.616z" fill="#FFE37B"/></svg>会员专享</span>';
            $html .= '<span class="mirai-paybox-price-num">' . number_format($vipPriceDisplay, 1) . '</span>';
            $html .= '<span class="mirai-paybox-price-old">' . number_format($originalPrice, 2) . '</span>';
        } else {
            $html .= '<span class="mirai-paybox-price-num">' . number_format($finalPrice, 1) . '</span>';
        }
        $html .= '</div>';
        $vipLevelsCount = Mirai_vipGetLevelsCount();
        if ($vipLevelsCount > 0) {
            $html .= '<div class="mirai-paybox-vip-list">';
            for ($level = 1; $level <= $vipLevelsCount; $level++) {
                $name = Mirai_vipGetName($level);
                $icon = 'ri-vip-crown-fill';
                $lvPrice = Mirai_vipApplyDiscount($originalPrice, $level, $vipDiscountMode, [1 => $settings['vip_1_price'], 2 => $settings['vip_2_price'], 3 => $settings['vip_3_price']]);
                $priceText = $lvPrice <= 0 ? '免费' : '¥' . number_format($lvPrice, 2);
                $html .= '<div class="mirai-paybox-vip-item">';
                $html .= '<i class="' . htmlspecialchars($icon) . '"></i><span>' . htmlspecialchars($name) . '</span>';
                $html .= '<strong>' . $priceText . '</strong>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $payNoticeExtra = trim((string)Mirai_payGetOption('payNoticeExtra', ''));
        if ($payNoticeExtra !== '') {
            $html .= '<div class="mirai-paybox-notice-extra">' . nl2br(htmlspecialchars($payNoticeExtra, ENT_QUOTES, 'UTF-8')) . '</div>';
        }
        if ($payNotice !== '') {
            $html .= '<div class="mirai-paybox-desc">' . nl2br(htmlspecialchars($payNotice, ENT_QUOTES, 'UTF-8')) . '</div>';
        }
        $html .= '<button type="submit" class="btn btn-primary mirai-paybox-submit">立即购买</button>';
        if ($uid <= 0) {
            $html .= '<p class="mirai-paybox-tip">您当前未登录！建议登陆后购买，可保存购买订单</p>';
        }
        $html .= '</div>';
        $html .= '</form>';
    }
    $html .= '</div>';
    return $html;
}