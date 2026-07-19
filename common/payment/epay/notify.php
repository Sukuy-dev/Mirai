<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_epay_handleNotify() {
    $options = Mirai_opt();
    $epayApi = $options->epayApi;
    $epayPid = $options->epayPid;
    $epayVersion = $options->epayVersion;
    
    if (empty($epayApi) || empty($epayPid)) {
        Mirai_payLog("epay配置缺失 API或PID为空", 'error');
        return 'fail';
    }
    
    if (substr($epayApi, -1) !== '/') {
        $epayApi .= '/';
    }

    $verified = false;
    $trade_no = '';
    $out_trade_no = '';
    $total_amount = 0;

    if ($epayVersion == '1') {
        $epayKey = $options->epayKey;
        if (empty($epayKey)) {
            Mirai_payLog("epay MD5配置缺失 请在主题设置中配置商户密钥", 'error');
            return 'fail';
        }
        
        require_once __DIR__ . '/lib/EpayCore.class.php';
        $config = [
            'pid' => $epayPid,
            'key' => $epayKey,
            'apiurl' => $epayApi,
        ];
        $epay = new \Mirai\Payment\Epay\V1\EpayCore($config);
        $verified = $epay->verifyNotify();
        if ($verified) {
            $out_trade_no = isset($_GET['out_trade_no']) ? $_GET['out_trade_no'] : '';
            $trade_no = isset($_GET['trade_no']) ? $_GET['trade_no'] : '';
            $total_amount = isset($_GET['money']) ? (float)$_GET['money'] : 0;
            if (isset($_GET['trade_status']) && $_GET['trade_status'] != 'TRADE_SUCCESS') {
                return 'fail';
            }
        }
    } else {
        $epayPlatformPublicKey = $options->epayPlatformPublicKey;
        $epayMerchantPrivateKey = $options->epayMerchantPrivateKey;
        
        if (empty($epayPlatformPublicKey) || empty($epayMerchantPrivateKey)) {
            Mirai_payLog("epay RSA配置缺失 请在主题设置中配置公钥和私钥", 'error');
            return 'fail';
        }
        
        require_once __DIR__ . '/lib/EpayCoreV2.class.php';

        $epayPlatformPublicKey = Mirai_payFormatRsaKey($epayPlatformPublicKey, 'public');
        $epayMerchantPrivateKey = Mirai_payFormatRsaKey($epayMerchantPrivateKey, 'private');

        $config = ['apiurl' => $epayApi, 'pid' => $epayPid, 'platform_public_key' => $epayPlatformPublicKey, 'merchant_private_key' => $epayMerchantPrivateKey];
        $epay = new \Mirai\Payment\Epay\V2\EpayCore($config);
        
        $data = $_GET;
        if (empty($data)) $data = $_POST;
        
        try {
            if ($epay->verify($data)) {
                $verified = true;
                $out_trade_no = isset($data['out_trade_no']) ? $data['out_trade_no'] : ''; 
                $trade_no = isset($data['trade_no']) ? $data['trade_no'] : '';
                $total_amount = isset($data['money']) ? (float)$data['money'] : 0;
                
                if (isset($data['trade_status']) && $data['trade_status'] != 'TRADE_SUCCESS') {
                     return 'fail';
                }
            }
        } catch (Exception $e) {
            Mirai_payLog("epay V2验签异常 错误:{$e->getMessage()}", 'error');
            return 'fail';
        }
    }

    if ($verified && !empty($out_trade_no)) {
        $order = Mirai_payGetOrder($out_trade_no);
        if (empty($order)) {
            Mirai_payLog("epay订单不存在 订单:{$out_trade_no}", 'error');
            return 'fail';
        }
        
        $orderAmount = (float)$order['amount'];
        $orderAmountCents = (int)round($orderAmount * 100);
        $paidAmountCents = (int)round($total_amount * 100);
        if ($orderAmountCents !== $paidAmountCents) {
            Mirai_payLog("epay金额不匹配 订单:{$out_trade_no} 支付:{$total_amount} 订单:{$orderAmount}", 'error');
            return 'fail';
        }
        
        if (Mirai_payMarkOrderPaid($out_trade_no, $trade_no)) {
            return 'success';
        } else {
            Mirai_payLog("epay标记支付失败 订单:{$out_trade_no}", 'error');
            return 'fail';
        }
    }

    $dataJson = json_encode($_GET, JSON_UNESCAPED_UNICODE);
    Mirai_payLog("epay通知验签失败 GET:{$dataJson}", 'error');
    return 'fail';
}
