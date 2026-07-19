<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_payTable($name) {
    $db = \Typecho\Db::get();
    return $db->getPrefix() . 'mirai_pay_' . $name;
}

function Mirai_payGetOption($key, $default = '') {
    $options = Mirai_opt();
    return isset($options->$key) && $options->$key !== '' ? $options->$key : $default;
}

function Mirai_payEnabled() {
    return Mirai_payGetOption('payEnable', '0') === '1';
}

function Mirai_payGuestMode() {
    return Mirai_payGetOption('payGuestMode', 'login');
}

function Mirai_payGuestAllowed() {
    return Mirai_payGuestMode() === 'guest';
}

function Mirai_payBalanceName() {
    return '余额';
}

function Mirai_payCurrencyName() {
    return '元';
}

function Mirai_payRechargeLimit() {
    $config = trim((string)Mirai_payGetOption('payRechargeLimit', '0.01-10000'));
    if (strpos($config, '-') !== false) {
        list($min, $max) = explode('-', $config, 2);
        $min = (float)trim($min);
        $max = (float)trim($max);
    } else {
        $min = 0.01;
        $max = (float)$config;
    }
    $min = $min > 0 ? $min : 0.01;
    if ($max < $min) {
        $temp = $max;
        $max = $min;
        $min = $temp;
    }
    return ['min' => $min, 'max' => $max];
}

function Mirai_payOrderExpireSeconds() {
    $expire = (int)Mirai_payGetOption('payOrderExpireTime', 1800);
    $expire = max(60, min(86400, $expire));
    return $expire;
}

function Mirai_payIsOrderExpired($order) {
    if (!is_array($order)) {
        return true;
    }
    $status = isset($order['status']) ? (string)$order['status'] : '';
    if ($status !== 'pending') {
        return false;
    }
    $created = isset($order['created']) ? (int)$order['created'] : 0;
    if ($created <= 0) {
        return true;
    }
    $expireSeconds = Mirai_payOrderExpireSeconds();
    return (time() - $created) > $expireSeconds;
}

function Mirai_payTableExists($table) {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    try {
        $db = \Typecho\Db::get();
        $row = $db->fetchRow($db->query("SHOW TABLES LIKE '" . $table . "'"));
        $cache[$table] = !empty($row);
        return $cache[$table];
    } catch (Exception $e) {
        $cache[$table] = false;
        return false;
    }
}

function Mirai_payDbCheck(): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }
    $checked = Mirai_payTableExists(Mirai_payTable('orders')) && Mirai_payTableExists(Mirai_payTable('wallets'));
    return $checked;
}

function Mirai_payValidOrderNo($orderNo) {
    return preg_match('/^MR[A-Fa-f0-9]{20}$/', $orderNo) === 1;
}

function Mirai_payGenerateOrderNo(): string {
    try {
        $random = bin2hex(random_bytes(4));
    } catch (Exception $e) {
        $random = substr(md5(uniqid('mirai', true)), 0, 8);
    }
    $date = new \Typecho\Date();
    return 'MR' . $date->format('Ymd') . strtoupper($random) . mt_rand(1000, 9999);
}

function Mirai_payNormalizeAmount($value, $default = 0.0) {
    if (is_string($value)) {
        $value = trim($value);
        $value = str_replace('，', ',', $value);
        if (strpos($value, ',') !== false) {
            if (strpos($value, '.') === false) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        }
        $value = preg_replace('/\s+/', '', $value);
    }
    if (!is_numeric($value)) {
        return (float)$default;
    }
    $amount = (float)$value;
    if (!is_finite($amount)) {
        return (float)$default;
    }
    return round($amount, 2);
}

function Mirai_payOrderTypeLabel($type) {
    $map = [
        'read' => '付费阅读',
        'partial' => '部分内容',
        'recharge' => '余额充值',
        'vip' => '开通会员',
        'other' => '其他'
    ];
    return $map[$type] ?? '其他';
}

function Mirai_payMethodLabel($method) {
    $map = [
        'balance' => '余额支付',
        'wechat' => '微信支付',
        'alipay' => '支付宝支付',
        'qq' => 'QQ支付',
        'f2fpay' => '支付宝当面付',
        'epay' => '易支付'
    ];
    return $map[$method] ?? $method;
}

function Mirai_payGatewayLabel($gateway) {
    $map = [
        'epay' => '易支付',
        'f2fpay' => '支付宝当面付',
        'balance' => '余额支付'
    ];
    return $map[$gateway] ?? $gateway;
}

function Mirai_payOrderStatusLabel($status) {
    $map = [
        'pending' => '待支付',
        'paid' => '已支付',
        'closed' => '已关闭'
    ];
    return $map[$status] ?? '未知';
}

function Mirai_payGetOrderDisplayStatus($order) {
    if (!is_array($order)) {
        return 'closed';
    }
    $status = isset($order['status']) ? (string)$order['status'] : '';
    if ($status === 'paid') {
        return 'paid';
    }
    if ($status === 'closed') {
        return 'closed';
    }
    if ($status === 'pending') {
        if (Mirai_payIsOrderExpired($order)) {
            return 'closed';
        }
        return 'pending';
    }
    return 'closed';
}

function Mirai_payBuildApiUrl($api, $params = []) {
    $site = rtrim(Mirai_opt()->siteUrl, '/');
    $url = $site . '/?mirai_api=' . rawurlencode($api);
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }
    return $url;
}

function Mirai_payGuestToken() {
    $key = 'mirai_pay_guest_token';
    $secret = defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : 'mirai_pay_secret_key';

    if (!empty($_COOKIE[$key])) {
        list($token, $signature) = explode('|', $_COOKIE[$key] . '|');
        if ($signature === hash_hmac('sha256', $token, $secret)) {
            return $token;
        }
    }

    try {
        $token = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $token = bin2hex(uniqid('mri_', true) . mt_rand(10000, 99999));
    }

    $signature = hash_hmac('sha256', $token, $secret);
    $cookieValue = $token . '|' . $signature;

    $secure = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    setcookie($key, $cookieValue, [
        'expires' => time() + 31536000,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    $_COOKIE[$key] = $cookieValue;
    return $token;
}

function Mirai_payCookieKey($cid) {
    return 'mirai_paid_' . (int)$cid;
}

function Mirai_paySetCookie($name, $value, $expires) {
    $secure = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    setcookie($name, (string)$value, [
        'expires' => (int)$expires,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function Mirai_payDbAcquireLock($key, $timeout = 5) {
    $key = trim((string)$key);
    if ($key === '' || strlen($key) > 64) {
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $key)) {
        return false;
    }
    try {
        $db = \Typecho\Db::get();
        $adapter = $db->getAdapter();
        $quotedKey = $adapter->quoteValue($key);
        $row = $db->fetchRow($db->query("SELECT GET_LOCK(" . $quotedKey . ", " . (int)$timeout . ") AS lock_ok"));
        return !empty($row) && isset($row['lock_ok']) && (int)$row['lock_ok'] === 1;
    } catch (Exception $e) {
        return false;
    }
}

function Mirai_payDbReleaseLock($key) {
    $key = trim((string)$key);
    if ($key === '' || strlen($key) > 64) {
        return;
    }
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $key)) {
        return;
    }
    try {
        $db = \Typecho\Db::get();
        $adapter = $db->getAdapter();
        $quotedKey = $adapter->quoteValue($key);
        $db->query("SELECT RELEASE_LOCK(" . $quotedKey . ")");
    } catch (Exception $e) {
    }
}

function Mirai_payAcquireLock($key, $timeout = 5) {
    if (Mirai_payDbAcquireLock($key, $timeout)) {
        return true;
    }
    return Mirai_payFileLock($key, $timeout);
}

function Mirai_payReleaseLock($key) {
    Mirai_payDbReleaseLock($key);
    Mirai_payFileUnlock($key);
}

function Mirai_payFileLock($key, $timeout = 5) {
    $lockDir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/locks/';
    if (!is_dir($lockDir)) {
        @mkdir($lockDir, 0755, true);
    }
    $lockFile = $lockDir . md5($key) . '.lock';
    $fp = @fopen($lockFile, 'c');
    if (!$fp) {
        return false;
    }
    $startTime = time();
    while (true) {
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $GLOBALS['_mirai_pay_locks'][$key] = $fp;
            return true;
        }
        if (time() - $startTime >= $timeout) {
            fclose($fp);
            return false;
        }
        usleep(100000);
    }
}

function Mirai_payFileUnlock($key) {
    if (isset($GLOBALS['_mirai_pay_locks'][$key])) {
        $fp = $GLOBALS['_mirai_pay_locks'][$key];
        flock($fp, LOCK_UN);
        fclose($fp);
        unset($GLOBALS['_mirai_pay_locks'][$key]);
    }
}

function Mirai_payLog($message, $level = 'info') {
    $logDir = __TYPECHO_ROOT_DIR__ . '/usr/uploads/logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $date = new \Typecho\Date();
    $logFile = $logDir . 'pay_' . $date->format('Y-m-d') . '.log';
    $time = $date->format('Y-m-d H:i:s');
    $level = strtoupper($level);
    $logMessage = "[{$time}] [{$level}] {$message}" . PHP_EOL;

    @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

function Mirai_payFormatRsaKey($key, $type = 'public') {
    $key = preg_replace('/[\-\s\r\n]+(BEGIN|END) (RSA )?(PUBLIC|PRIVATE) KEY[\-\s\r\n]+/', '', $key);
    $key = str_replace(["\r", "\n", " "], '', $key);
    return $key;
}

function Mirai_payGatewayEnabled($gateway) {
    if ($gateway === 'epay') {
        return Mirai_payGetOption('epayEnable', '0') === '1';
    }
    if ($gateway === 'f2fpay') {
        return Mirai_payGetOption('f2fEnable', '0') === '1';
    }
    return false;
}

function Mirai_payGatewayConfigured($gateway) {
    if ($gateway === 'epay') {
        $pid = trim((string)Mirai_payGetOption('epayPid', ''));
        $api = trim((string)Mirai_payGetOption('epayApi', ''));
        if ($pid === '' || $api === '') {
            return false;
        }
        $version = trim((string)Mirai_payGetOption('epayVersion', '2'));
        if ($version === '2') {
            $platformPublicKey = trim((string)Mirai_payGetOption('epayPlatformPublicKey', ''));
            $merchantPrivateKey = trim((string)Mirai_payGetOption('epayMerchantPrivateKey', ''));
            return $platformPublicKey !== '' && $merchantPrivateKey !== '';
        }
        $key = trim((string)Mirai_payGetOption('epayKey', ''));
        return $key !== '';
    }
    if ($gateway === 'f2fpay') {
        $appId = trim((string)Mirai_payGetOption('f2fAppId', ''));
        $privateKey = trim((string)Mirai_payGetOption('f2fPrivateKey', ''));
        $publicKey = trim((string)Mirai_payGetOption('f2fPublicKey', ''));
        return $appId !== '' && $privateKey !== '' && $publicKey !== '';
    }
    return false;
}

function Mirai_payChannelGateway($channel) {
    $channel = (string)$channel;
    if ($channel === 'wechat') {
        $gateway = trim((string)Mirai_payGetOption('payWechatGateway', 'epay'));
    } elseif ($channel === 'alipay') {
        $gateway = trim((string)Mirai_payGetOption('payAlipayGateway', 'epay'));
    } elseif ($channel === 'qq') {
        $gateway = trim((string)Mirai_payGetOption('payQqGateway', 'none'));
    } else {
        return '';
    }
    $validGateways = ['epay', 'f2fpay'];
    if (!in_array($gateway, $validGateways, true)) {
        return '';
    }
    if ($gateway === 'f2fpay' && $channel !== 'alipay') {
        return '';
    }
    return $gateway;
}

function Mirai_payAvailableOnlineChannels() {
    $channels = [];
    $alipayGateway = Mirai_payChannelGateway('alipay');
    if ($alipayGateway !== '' && Mirai_payGatewayEnabled($alipayGateway) && Mirai_payGatewayConfigured($alipayGateway)) {
        $channels[] = 'alipay';
    }
    $wechatGateway = Mirai_payChannelGateway('wechat');
    if ($wechatGateway !== '' && Mirai_payGatewayEnabled($wechatGateway) && Mirai_payGatewayConfigured($wechatGateway)) {
        $channels[] = 'wechat';
    }
    $qqGateway = Mirai_payChannelGateway('qq');
    if ($qqGateway !== '' && Mirai_payGatewayEnabled($qqGateway) && Mirai_payGatewayConfigured($qqGateway)) {
        $channels[] = 'qq';
    }
    return array_values(array_unique($channels));
}

function Mirai_payMethods() {
    $methods = ['balance'];
    foreach (Mirai_payAvailableOnlineChannels() as $channel) {
        $methods[] = $channel;
    }
    return array_values(array_unique($methods));
}
