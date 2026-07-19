<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function _Mirai_f2fpay_initAop() {
    $options = Mirai_opt();
    $f2fAppId = $options->f2fAppId;
    $f2fPrivateKey = $options->f2fPrivateKey;
    $f2fPublicKey = $options->f2fPublicKey;
    
    if (empty($f2fAppId) || empty($f2fPrivateKey) || empty($f2fPublicKey)) {
        return null;
    }

    $f2fPrivateKey = Mirai_payFormatRsaKey($f2fPrivateKey, 'private');
    $f2fPublicKey = Mirai_payFormatRsaKey($f2fPublicKey, 'public');

    $aopDir = __DIR__ . '/aop';
    $includePath = get_include_path();
    if (strpos($includePath, $aopDir) === false) {
        set_include_path($includePath . PATH_SEPARATOR . $aopDir);
    }
    require_once $aopDir . '/SignData.php';
    require_once $aopDir . '/AopClient.php';

    $aop = new \AopClient();
    $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
    $aop->appId = $f2fAppId;
    $aop->rsaPrivateKey = $f2fPrivateKey;
    $aop->alipayrsaPublicKey = $f2fPublicKey;
    $aop->apiVersion = '1.0';
    $aop->signType = 'RSA2';
    $aop->postCharset = 'UTF-8';
    $aop->format = 'json';
    
    return $aop;
}

function Mirai_f2fpay_pay($order) {
    $secureConfig = Mirai_getPaymentConfig();
    if (empty($secureConfig['available'])) {
        return ['success' => false, 'message' => $secureConfig['msg'] ?? '需要有效的许可验证才能使用支付功能'];
    }

    $options = Mirai_opt();
    $f2fAppId = $options->f2fAppId;
    $f2fPublicKey = $options->f2fPublicKey;
    $f2fPrivateKey = $options->f2fPrivateKey;

    if (empty($f2fAppId) || empty($f2fPrivateKey) || empty($f2fPublicKey)) {
        return ['success' => false, 'message' => '支付配置不完整，请在主题设置中配置支付宝当面付密钥'];
    }

    $aop = _Mirai_f2fpay_initAop();
    if (!$aop) {
        return ['success' => false, 'message' => '支付配置不完整，请在主题设置中配置支付宝当面付密钥'];
    }

    require_once __DIR__ . '/aop/request/AlipayTradePrecreateRequest.php';

    $request = new \AlipayTradePrecreateRequest();
    
    $notifyUrl = $options->siteUrl . '?mirai_api=pay_notify&gateway=f2fpay';
    $request->setNotifyUrl($notifyUrl);
    
    $bizContent = [
        'out_trade_no' => $order['order_no'],
        'total_amount' => $order['amount'],
        'subject' => $order['product_title'],
        'body' => 'Payment for ' . $order['product_title'],
        'buyer_ip' => Mirai_getClientIp()
    ];
    
    $request->setBizContent(json_encode($bizContent));
    
    try {
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        
        if (!empty($resultCode) && $resultCode == 10000) {
            $qrCode = $result->$responseNode->qr_code;
            return [
                'success' => true,
                'type' => 'qrcode',
                'content' => $qrCode,
                'expire' => Mirai_payOrderExpireSeconds()
            ];
        } else {
            $errMsg = $result->$responseNode->sub_msg ?? $result->$responseNode->msg ?? 'Unknown error';
            return [
                'success' => false, 
                'message' => $errMsg
            ];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function Mirai_f2fpay_query($orderNo) {
    $options = Mirai_opt();
    $f2fAppId = $options->f2fAppId;
    $f2fPrivateKey = $options->f2fPrivateKey;
    $f2fPublicKey = $options->f2fPublicKey;
    if (empty($f2fAppId) || empty($f2fPrivateKey) || empty($f2fPublicKey)) {
        return ['success' => false];
    }

    $aop = _Mirai_f2fpay_initAop();
    if (!$aop) {
        return ['success' => false];
    }

    require_once __DIR__ . '/aop/request/AlipayTradeQueryRequest.php';

    $request = new \AlipayTradeQueryRequest();
    $bizContent = [
        'out_trade_no' => $orderNo
    ];
    $request->setBizContent(json_encode($bizContent));
    
    try {
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        
        if (!empty($resultCode) && $resultCode == 10000) {
            if ($result->$responseNode->trade_status == 'TRADE_SUCCESS') {
                return [
                    'success' => true,
                    'paid' => true,
                    'total_amount' => $result->$responseNode->total_amount,
                    'trade_no' => $result->$responseNode->trade_no
                ];
            }
        }
    } catch (Exception $e) {
        return ['success' => false];
    }
    return ['success' => false];
}
