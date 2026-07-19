<?php
/**
 * Mirai 主题 - IP 归属地功能
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_getClientIp() {
    static $cachedIp = null;
    if ($cachedIp !== null) {
        return $cachedIp;
    }
    
    $ip = '';
    
    // 按优先级检查各种代理头
    $keys = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_ALI_CDN_REAL_IP',      // 阿里云 CDN
        'HTTP_X_FORWARDED_FOR',      // 通用代理（可能包含多个 IP）
        'HTTP_X_REAL_IP',            // Nginx 代理
        'HTTP_CLIENT_IP',            // 某些代理
        'REMOTE_ADDR',               // 直接连接
    ];
    
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            
            // X-Forwarded-For 可能包含多个 IP（如：client, proxy1, proxy2），取第一个
            if ($key === 'HTTP_X_FORWARDED_FOR' && strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            
            // 验证 IP 格式
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $cachedIp = $ip;
                return $cachedIp;
            }
        }
    }
    
    // 最后尝试 getenv 方式（某些环境变量不在 $_SERVER 中）
    $envKeys = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR',
    ];
    
    foreach ($envKeys as $key) {
        $ip = getenv($key);
        if ($ip !== false && $ip !== '') {
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $cachedIp = $ip;
                return $cachedIp;
            }
        }
    }
    
    // 返回 REMOTE_ADDR 作为后备（不验证是否为公网IP，确保始终有返回值）
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($remoteAddr !== '') {
        $cachedIp = $remoteAddr;
        return $cachedIp;
    }
    
    // 最后的后备，返回本地地址
    return '0.0.0.0';
}

function Mirai_fetchIpLocation($ip) {
    // 简单的内存缓存，避免同一次请求重复查询
    static $cache = [];
    if (isset($cache[$ip])) {
        return $cache[$ip];
    }
    
    $options = Mirai_opt();
    $apiType = $options->ipLocationApi ?: 'pconline';
    $customApi = $options->ipLocationCustomApi;
    $enablePolling = !empty($options->ipLocationPolling);

    $apis = [];
    
    // 根据设置构建 API 列表
    if ($apiType === 'custom' && $customApi) {
        $apis[] = 'custom';
        // 仅当选择自定义API且启用轮询时，才添加太平洋作为备用
        if ($enablePolling) {
            $apis[] = 'pconline';
        }
    } else {
        // 选择太平洋时，直接使用太平洋，不启用轮询
        $apis[] = 'pconline';
    }

    foreach ($apis as $type) {
        $result = null;
        switch ($type) {
            case 'custom':
                $result = Mirai_getIpFromCustom($ip, $customApi);
                break;
            case 'pconline':
                $result = Mirai_getIpFromPconline($ip);
                break;
        }
        
        if ($result) {
            $cache[$ip] = $result;
            return $result;
        }
    }

    return null;
}

function Mirai_getIpFromPconline($ip) {
    $url = "https://whois.pconline.com.cn/ipJson.jsp?ip={$ip}&json=true";
    $response = Mirai_httpGet($url);
    
    if (!$response) return null;
    
    // 太平洋接口返回的是 GBK 编码
    $response = mb_convert_encoding($response, 'UTF-8', 'GBK');
    
    $json = json_decode($response, true);
    if (!$json) {
        // 尝试去除可能存在的 callback
        $response = trim($response);
        if (preg_match('/^\{.*\}$/s', $response)) {
            $json = json_decode($response, true);
        }
    }
    
    if (isset($json['pro']) || isset($json['city'])) {
        return [
            'country' => '中国', // 太平洋主要返回省市，默认中国，除非是国外 IP
            'province' => $json['pro'] ?? '',
            'city' => $json['city'] ?? '',
            'addr' => $json['addr'] ?? '' // addr 通常包含省市
        ];
    }
    
    return null;
}

function Mirai_getIpFromCustom($ip, $apiUrl) {
    // 替换 IP 占位符
    $url = $apiUrl;
    if (strpos($url, 'ip_address') !== false) {
        $url = str_replace('ip_address', $ip, $url);
    } else {
        // 智能追加逻辑
        // 如果以 = 结尾，直接追加 (如 ?ip=)
        if (substr($url, -1) === '=') {
            $url .= $ip;
        } else {

            $separator = (strpos($url, '?') !== false) ? '&' : '?';

            $url .= $separator . 'ip=' . $ip;
        }
    }
    
    $response = Mirai_httpGet($url);
    if (!$response) return null;
    
    // 移除可能存在的 BOM 头
    $response = preg_replace('/^\xEF\xBB\xBF/', '', $response);
    
    $json = json_decode($response, true);
    
    // 尝试智能解析常见的字段
    $country = '';
    $province = '';
    $city = '';
    
    if ($json) {
        // 1. 优先检查 data 字段 (针对 Tmini 等接口)
        $data = $json['data'] ?? $json; // 如果没有 data 字段，就用根数组
        
        // 2. 常见字段名匹配
        $country = $data['country'] ?? $data['country_name'] ?? $data['nation'] ?? '';
        $province = $data['province'] ?? $data['region'] ?? $data['regionName'] ?? $data['pro'] ?? '';
        $city = $data['city'] ?? '';
        
        // 如果第一轮没找到，尝试在根目录找 (防止 data 字段存在但为空，而数据在根目录的情况，虽然少见)
        if (!$country && !$province && !$city && isset($json['country'])) {
             $country = $json['country'] ?? '';
             $province = $json['province'] ?? $json['region'] ?? '';
             $city = $json['city'] ?? '';
        }

        if (!$country && !$province && !$city && isset($data['addr'])) {
             // 只有 addr 字段的情况
             return ['addr' => $data['addr']];
        }
    } else {
        // 可能是纯文本
        return ['addr' => trim($response)];
    }
    
    if ($country || $province || $city) {
        $result = [
            'country' => $country,
            'province' => $province,
            'city' => $city
        ];

        if (isset($data['latitude'])) $result['latitude'] = $data['latitude'];
        if (isset($data['longitude'])) $result['longitude'] = $data['longitude'];
        if (isset($data['lat'])) $result['latitude'] = $data['lat'];
        if (isset($data['lng'])) $result['longitude'] = $data['lng'];
        
        // 尝试提取街道/区域
        if (isset($data['district'])) $result['district'] = $data['district'];
        if (isset($data['street'])) $result['street'] = $data['street'];
        if (isset($data['isp'])) $result['isp'] = $data['isp'];
        
        // 尝试提取风险/代理信息
        // tmini 格式: risk: { is_proxy: "否" }
        if (isset($data['risk']) && is_array($data['risk'])) {
            if (isset($data['risk']['is_proxy'])) $result['is_proxy'] = $data['risk']['is_proxy'];
            if (isset($data['risk']['risk_level'])) $result['risk_level'] = $data['risk']['risk_level'];
        }
        
        // 其他常见的代理字段
        if (isset($data['is_proxy'])) $result['is_proxy'] = $data['is_proxy'];
        if (isset($data['proxy'])) $result['is_proxy'] = $data['proxy'];
        
        // 保存原始数据以便后续使用
        $result['raw_data'] = $data;
        
        return $result;
    }
    
    return null;
}

function Mirai_formatIpLocation($data, $format) {
    $country = $data['country'] ?? '';
    $province = $data['province'] ?? '';
    $city = $data['city'] ?? '';
    $addr = $data['addr'] ?? '';

    $province = str_replace(['省', '市'], '', $province);
    $city = str_replace(['市'], '', $city);
    
    // 特殊处理直辖市
    if (in_array($province, ['北京', '上海', '天津', '重庆'])) {
        $city = ''; // 直辖市不显示城市名，或者城市名与省名相同
    }
    
    // 如果只有 addr
    if (empty($province) && empty($city) && !empty($addr)) {
        return $addr;
    }

    switch ($format) {
        case 'country':
            return $country ?: ($province ?: $city);
        case 'province':
            return $province ?: $country;
        case 'city':
            return $city ?: $province;
        case 'province_city':
        default:
            if ($province && $city) {
                return $province . ' ' . $city;
            }
            return $province ?: ($city ?: $country);
    }
}

function Mirai_httpGet($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // 启用 SSL 验证，确保 HTTPS 请求的安全性
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // 3秒超时
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    $result = curl_exec($ch);
    if ($result === false) {
        curl_close($ch);
        return null;
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status >= 400) {
        return null;
    }
    return $result;
}
