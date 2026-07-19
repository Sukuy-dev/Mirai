<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_epay_pay($order, $paymentMethod) {
    $secureConfig = Mirai_getPaymentConfig();
    if (empty($secureConfig['available'])) {
        return ['success' => false, 'message' => $secureConfig['msg'] ?? '需要有效的许可验证才能使用支付功能'];
    }

    $options = Mirai_opt();
    
    $epayApi = $options->epayApi;
    $epayPid = $options->epayPid;
    $epayVersion = $options->epayVersion;
    
    if (empty($epayApi) || empty($epayPid)) {
        Mirai_payLog("epay配置缺失 API或PID为空", 'error');
        return ['success' => false, 'message' => '支付配置不完整'];
    }
    
    if (substr($epayApi, -1) !== '/') {
        $epayApi .= '/';
    }

    $notifyUrl = $options->siteUrl . '?mirai_api=pay_notify&gateway=epay';
    $returnUrl = $options->siteUrl . '?mirai_api=pay_return&gateway=epay';

    $type = 'alipay';
    if ($paymentMethod === 'wechat') {
        $type = 'wxpay';
    } elseif ($paymentMethod === 'qq') {
        $type = 'qqpay';
    } elseif ($paymentMethod === 'alipay') {
        $type = 'alipay';
    }

    $expireTime = Mirai_payOrderExpireSeconds();

    if ($epayVersion == '1') {
        $epayKey = $options->epayKey;
        if (empty($epayKey)) {
            Mirai_payLog("epay MD5配置缺失 请在主题设置中配置商户密钥", 'error');
            return ['success' => false, 'message' => '支付配置不完整，请在主题设置中配置易支付商户密钥'];
        }
        
        require_once __DIR__ . '/lib/EpayCore.class.php';
        $epay = new \Mirai\Payment\Epay\V1\EpayCore(['pid' => $epayPid, 'key' => $epayKey, 'apiurl' => $epayApi]);
        
        $clientip = Mirai_getClientIp();
        
        $result = $epay->apiPay([
            'pid' => $epayPid,
            'out_trade_no' => $order['order_no'],
            'type' => $type,
            'name' => $order['product_title'],
            'money' => $order['amount'],
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'clientip' => $clientip
        ]);
        
        if ($result && isset($result['code']) && $result['code'] == 1) {
            $qrcode = $result['qrcode'] ?? '';
            $payurl = $result['payurl'] ?? '';
            $urlscheme = $result['urlscheme'] ?? '';
            
            if (!empty($qrcode)) {
                return [
                    'success' => true,
                    'type' => 'qrcode',
                    'content' => $qrcode,
                    'expire' => $expireTime
                ];
            }

            if (!empty($payurl)) {
                return [
                    'success' => true,
                    'type' => 'redirect',
                    'url' => $payurl,
                    'expire' => $expireTime
                ];
            }

            if (!empty($urlscheme)) {
                return [
                    'success' => true,
                    'type' => 'qrcode',
                    'content' => $urlscheme,
                    'expire' => $expireTime
                ];
            }

            Mirai_payLog("epay发起失败 MD5 订单:{$order['order_no']} qrcode/payurl/urlscheme都为空 响应:" . json_encode($result, JSON_UNESCAPED_UNICODE), 'error');
            return ['success' => false, 'message' => '支付方式返回异常'];
        }
        
        $errMsg = is_array($result) ? ($result['msg'] ?? '支付发起失败') : '支付发起失败';
        $rawResponse = is_array($result) && isset($result['raw']) ? ' 原始响应:' . $result['raw'] : '';
        Mirai_payLog("epay发起失败 MD5 订单:{$order['order_no']} 错误:{$errMsg}{$rawResponse}", 'error');
        return ['success' => false, 'message' => $errMsg];
    } else {
        $epayPlatformPublicKey = $options->epayPlatformPublicKey;
        $epayMerchantPrivateKey = $options->epayMerchantPrivateKey;

        if (empty($epayPlatformPublicKey) || empty($epayMerchantPrivateKey)) {
            Mirai_payLog("epay RSA配置缺失 请在主题设置中配置公钥和私钥", 'error');
            return ['success' => false, 'message' => '支付配置不完整，请在主题设置中配置易支付RSA密钥'];
        }
        
        require_once __DIR__ . '/lib/EpayCoreV2.class.php';

        $epayPlatformPublicKey = Mirai_payFormatRsaKey($epayPlatformPublicKey, 'public');
        $epayMerchantPrivateKey = Mirai_payFormatRsaKey($epayMerchantPrivateKey, 'private');

        $epay = new \Mirai\Payment\Epay\V2\EpayCore(['apiurl' => $epayApi, 'pid' => $epayPid, 'platform_public_key' => $epayPlatformPublicKey, 'merchant_private_key' => $epayMerchantPrivateKey]);
        
        try {
            $result = $epay->apiPay([
                'method' => 'web',
                'device' => 'pc',
                'out_trade_no' => $order['order_no'],
                'type' => $type,
                'name' => $order['product_title'],
                'money' => $order['amount'],
                'notify_url' => $notifyUrl,
                'return_url' => $returnUrl,
                'clientip' => Mirai_getClientIp()
            ]);
            
            $payType = $result['pay_type'] ?? '';
            $payInfo = $result['pay_info'] ?? '';

            if ($payType === 'qrcode' && !empty($payInfo)) {
                return [
                    'success' => true,
                    'type' => 'qrcode',
                    'content' => $payInfo,
                    'expire' => $expireTime
                ];
            }

            if ($payType === 'jump' && !empty($payInfo)) {
                return [
                    'success' => true,
                    'type' => 'redirect',
                    'url' => $payInfo,
                    'expire' => $expireTime
                ];
            }

            Mirai_payLog("epay发起失败 RSA 订单:{$order['order_no']} pay_type:{$payType} 响应:" . json_encode($result, JSON_UNESCAPED_UNICODE), 'error');
            return ['success' => false, 'message' => '支付方式返回异常'];
        } catch (Exception $e) {
            Mirai_payLog("epay异常 RSA 订单:{$order['order_no']} 错误:{$e->getMessage()}", 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
