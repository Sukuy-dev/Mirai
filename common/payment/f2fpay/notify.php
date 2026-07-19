<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_f2fpay_handleNotify() {
    $secureConfig = Mirai_getPaymentConfig();
    if (empty($secureConfig['available'])) {
        return 'fail';
    }

    $options = Mirai_opt();
    $f2fAppId = $options->f2fAppId;
    $f2fPublicKey = $options->f2fPublicKey;
    $f2fPrivateKey = $options->f2fPrivateKey;

    if (empty($f2fAppId) || empty($f2fPrivateKey) || empty($f2fPublicKey)) {
        Mirai_payLog("f2fpay配置缺失 请在主题设置中配置支付宝当面付密钥", 'error');
        return 'fail';
    }

    $f2fPrivateKey = Mirai_payFormatRsaKey($f2fPrivateKey, 'private');
    $f2fPublicKey = Mirai_payFormatRsaKey($f2fPublicKey, 'public');

    $aopDir = __DIR__ . '/aop';
    $includePath = get_include_path();
    if (strpos($includePath, $aopDir) === false) {
        set_include_path($includePath . PATH_SEPARATOR . $aopDir);
    }
    require_once $aopDir . '/AopClient.php';

    $aop = new \AopClient();
    $aop->appId = $f2fAppId;
    $aop->rsaPrivateKey = $f2fPrivateKey;
    $aop->alipayrsaPublicKey = $f2fPublicKey;
    $aop->signType = 'RSA2';

    $verified = $aop->rsaCheckV1($_POST, NULL, "RSA2");
    
    if ($verified) {
        $out_trade_no = isset($_POST['out_trade_no']) ? (string)$_POST['out_trade_no'] : '';
        $trade_no = isset($_POST['trade_no']) ? (string)$_POST['trade_no'] : '';
        $trade_status = isset($_POST['trade_status']) ? (string)$_POST['trade_status'] : '';
        if ($out_trade_no === '' || $trade_no === '' || $trade_status === '') {
            return 'fail';
        }
        $total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
        
        if ($trade_status == 'TRADE_SUCCESS' || $trade_status == 'TRADE_FINISHED') {
            $order = Mirai_payGetOrder($out_trade_no);
            if (empty($order)) {
                return 'fail';
            }
            
            $orderAmount = (float)$order['amount'];
            $orderAmountCents = (int)round($orderAmount * 100);
            $paidAmountCents = (int)round($total_amount * 100);
            if ($orderAmountCents !== $paidAmountCents) {
                return 'fail';
            }
            
            if (Mirai_payMarkOrderPaid($out_trade_no, $trade_no)) {
                return 'success';
            }
        }
    }
    
    return 'fail';
}
