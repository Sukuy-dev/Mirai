<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$options = Mirai_opt();
$epayApi = $options->epayApi;
$epayPid = $options->epayPid;
$epayVersion = $options->epayVersion;

if (empty($epayApi) || empty($epayPid)) {
    Mirai_payLog("epay同步回调配置缺失 API或PID为空", 'error');
    header('Location: ' . rtrim($options->siteUrl, '/'));
    exit;
}

$secureConfig = Mirai_getPaymentConfig();
if (empty($secureConfig['available'])) {
    Mirai_payLog("epay同步回调 许可验证失败: " . ($secureConfig['msg'] ?? '未取得有效许可'), 'error');
    header('Location: ' . rtrim($options->siteUrl, '/'));
    exit;
}

if (substr($epayApi, -1) !== '/') {
    $epayApi .= '/';
}

$verified = false;
$orderNo = '';
$tradeNo = '';

if ($epayVersion == '1') {
    require_once __DIR__ . '/lib/EpayCore.class.php';
    $epayKey = $options->epayKey;
    if (empty($epayKey)) {
        Mirai_payLog("epay同步回调 V1配置缺失 请在主题设置中配置商户密钥", 'error');
        header('Location: ' . rtrim($options->siteUrl, '/'));
        exit;
    }
    $config = [
        'pid' => $epayPid,
        'key' => $epayKey,
        'apiurl' => $epayApi,
    ];
    $epay = new \Mirai\Payment\Epay\V1\EpayCore($config);
    if ($epay->verifyReturn()) {
        $verified = true;
        $orderNo = isset($_GET['out_trade_no']) ? $_GET['out_trade_no'] : '';
        $tradeNo = isset($_GET['trade_no']) ? $_GET['trade_no'] : '';
    }
} else {
    require_once __DIR__ . '/lib/EpayCoreV2.class.php';
    $platformPublicKey = $options->epayPlatformPublicKey;
    $merchantPrivateKey = $options->epayMerchantPrivateKey;

    if (empty($platformPublicKey) || empty($merchantPrivateKey)) {
        Mirai_payLog("epay同步回调 V2配置缺失 公钥或私钥为空", 'error');
        header('Location: ' . rtrim($options->siteUrl, '/'));
        exit;
    }

    $platformPublicKey = Mirai_payFormatRsaKey($platformPublicKey, 'public');
    $merchantPrivateKey = Mirai_payFormatRsaKey($merchantPrivateKey, 'private');

    $config = [
        'apiurl' => $epayApi,
        'pid' => $epayPid,
        'platform_public_key' => $platformPublicKey,
        'merchant_private_key' => $merchantPrivateKey
    ];
    $epay = new \Mirai\Payment\Epay\V2\EpayCore($config);
    
    $data = $_GET;
    if (empty($data)) $data = $_POST;
    
    try {
        if ($epay->verify($data)) {
            $verified = true;
            $orderNo = isset($data['out_trade_no']) ? $data['out_trade_no'] : '';
            $tradeNo = isset($data['trade_no']) ? $data['trade_no'] : '';
            
            if (isset($data['trade_status']) && $data['trade_status'] != 'TRADE_SUCCESS') {
                $verified = false;
            }
        }
    } catch (Exception $e) {
        Mirai_payLog("epay同步回调 V2验签异常 错误:{$e->getMessage()}", 'error');
        $verified = false;
    }
}

if ($verified && !empty($orderNo)) {
    $order = Mirai_payGetOrder($orderNo);
    $tradeStatus = isset($_GET['trade_status']) ? (string)$_GET['trade_status'] : (isset($_POST['trade_status']) ? (string)$_POST['trade_status'] : '');
    if ($order && $tradeStatus === 'TRADE_SUCCESS') {
        $paidAmount = null;
        if (isset($_GET['money'])) {
            $paidAmount = (float)$_GET['money'];
        } elseif (isset($_POST['money'])) {
            $paidAmount = (float)$_POST['money'];
        }
        if ($paidAmount !== null) {
            $orderAmountInCents = (int)round((float)$order['amount'] * 100);
            $paidAmountInCents = (int)round($paidAmount * 100);
            if ($orderAmountInCents !== $paidAmountInCents) {
                Mirai_payLog("epay同步回调金额不匹配 订单:{$orderNo} 支付:{$paidAmount} 订单:{$order['amount']}", 'error');
                header('Location: ' . rtrim($options->siteUrl, '/'));
                exit;
            }
        }
        $markResult = Mirai_payMarkOrderPaid($orderNo, $tradeNo);
        if ($markResult !== true) {
            Mirai_payLog("epay同步回调标记支付失败 订单:{$orderNo}", 'error');
        }
        $url = Mirai_api_payOrderTargetUrl($order);
        header('Location: ' . $url);
        exit;
    } else {
        Mirai_payLog("epay同步回调订单状态异常 订单:{$orderNo} 状态:{$tradeStatus}", 'error');
    }
} else {
    $dataJson = json_encode($_GET, JSON_UNESCAPED_UNICODE);
    Mirai_payLog("epay同步回调验签失败 GET:{$dataJson}", 'error');
}

header('Location: ' . $options->siteUrl);
exit;
