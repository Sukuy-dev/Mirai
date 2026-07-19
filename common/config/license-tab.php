<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$status = null;
$config = [];
try {
    if (function_exists('Mirai_authGetStatus')) {
        $status = Mirai_authGetStatus(false);
    }
    if (function_exists('Mirai_getAuthConfig')) {
        $config = Mirai_getAuthConfig();
    }
} catch (Exception $e) {} catch (Error $e) {}

$isValid = false;
$payload = [];
$expDate = '未知';
$daysLeft = 0;
$licenseKey = '';
$sourceText = '实时';
$isTrial = false;
$isBanned = false;
$isLongTerm = false;

if ($status !== null) {
    $isValid = !empty($status['ok']);
    $payload = $status['payload'] ?? [];
    $exp = $status['expires_at'] ?? 0;
    $payloadExp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
    $isLongTerm = $payloadExp === 0 || $exp === PHP_INT_MAX;
    $expDate = $isLongTerm ? '长期有效' : ($exp > 0 ? (new \Typecho\Date($exp))->format('Y-m-d H:i:s') : '未知');
    $daysLeft = $status['days_left'] ?? null;
    $licenseKey = $config['license'] ?? '';
    $sourceText = !empty($status['from_cache']) ? '缓存' : '实时';
    $isTrial = isset($payload['plan']) && (string)$payload['plan'] === 'trial';
    $isBanned = isset($payload['banned']) && $payload['banned'] === true;
}

if (!$isValid) {
    $pillDisplay = '未许可';
} elseif ($isBanned) {
    $pillDisplay = '被封禁';
} elseif ($isTrial) {
    $pillDisplay = '试用版';
} else {
    $pillDisplay = '赞助版';
}

if (!$isValid) {
    $planDisplay = '未激活';
} elseif ($isTrial) {
    $planDisplay = '试用版';
} else {
    $planDisplay = '赞助版';
}

?>

<div class="mirai-license-container">
    <?php if (!$isValid): ?>
    <div class="mirai-license-activate-panel">
        <div class="mirai-license-pricing-section">
            <div class="mirai-license-pricing-card">
                <div class="mirai-license-pricing-badge">赞助版</div>
                <div class="mirai-license-price-main">
                    <span class="mirai-license-price-currency">¥</span>
                    <span class="mirai-license-price-value">79</span>
                </div>
                <div class="mirai-license-contact-info">
                    <div class="mirai-license-contact-desc">
                        <p>主题制作不易，感谢您的赞助</p>
                        <p>若有疑问，请通过以下方式联系</p>
                        <p style="margin-top:4px;color:#ef4444;font-size:12px;">随着主题不断更新完善，价格也会不断调整</p>
                    </div>
                    <div class="mirai-license-contact-item">
                        <i class="ri-qq-line"></i>
                        <span class="contact-label">QQ：</span>
                        <span>1461139506</span>
                    </div>
                    <div class="mirai-license-contact-item">
                        <i class="ri-wechat-line"></i>
                        <span class="contact-label">微信：</span>
                        <span>Sakura1086</span>
                    </div>

                </div>
            </div>
        </div>

        <div class="mirai-license-activate-section">
            <div class="mirai-license-activate-header">
                <div class="mirai-license-activate-icon">
                    <i class="ri-shield-keyhole-line"></i>
                </div>
                <h2 class="mirai-license-activate-title">激活主题</h2>
                <p class="mirai-license-activate-desc">输入您的许可密钥以激活赞助版</p>
            </div>
            
            <div class="mirai-license-activate-form">
                <div class="mirai-license-input-wrapper">
                    <label class="mirai-license-input-label">许可密钥</label>
                    <input type="text" 
                           name="licenseCode" 
                           id="licenseCode" 
                           class="mirai-license-input-field" 
                           placeholder="请输入 License Key"
                           value="<?php echo htmlspecialchars($licenseKey); ?>"
                           autocomplete="off">
                </div>
                
                <div class="mirai-license-message" id="mirai-license-message" style="display: none;"></div>
                
                <div class="mirai-license-activate-actions">
                    <button type="button" class="mirai-license-btn primary" id="mirai-license-verify-btn">立即激活</button>
                </div>
                
                <div class="mirai-license-activate-footer">
                    <span>还没有许可密钥？</span>
                    <button type="button" class="mirai-license-link" onclick="showContactModal()">获取密钥</button>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="mirai-license-hero is-valid" id="mirai-license-hero">
        <div class="mirai-license-hero-main">
            <div class="mirai-license-hero-icon">
                <i class="ri-shield-check-line"></i>
            </div>
            <div class="mirai-license-hero-text">
                <div class="mirai-license-hero-title" id="mirai-license-title"><?php echo $isTrial ? 'Mirai 主题试用中' : 'Mirai 主题已激活'; ?></div>
                <div class="mirai-license-hero-desc" id="mirai-license-desc"><?php echo $isTrial ? '当前为主题试用期间，感谢您的使用' : '感谢您对Mirai未来主题的赞助与支持'; ?></div>
                <div class="mirai-license-hero-meta">
                    <span class="mirai-license-pill <?php echo $isValid ? ($isBanned ? 'banned' : 'valid') : 'invalid'; ?>" id="mirai-license-pill"><?php echo htmlspecialchars($pillDisplay); ?></span>
                    <span class="mirai-license-pill light" id="mirai-license-source"><?php echo $sourceText; ?></span>
                </div>
            </div>
        </div>
        <div class="mirai-license-hero-actions">
            <button type="button" class="mirai-license-btn primary" id="mirai-license-verify-btn">刷新状态</button>
            <?php if ($isTrial): ?>
            <button type="button" class="mirai-license-btn success" id="mirai-license-activate-btn">许可激活</button>
            <?php endif; ?>
            <button type="button" class="mirai-license-btn ghost" id="mirai-license-check-update-btn">检查更新</button>
        </div>
    </div>

    <div class="mirai-license-message" id="mirai-license-message" style="display: none;"></div>

    <div class="mirai-license-grid">
        <div class="mirai-license-card">
            <div class="label">许可域名</div>
            <div class="value" id="mirai-license-domain"><?php echo htmlspecialchars($payload['domain'] ?? $_SERVER['HTTP_HOST']); ?></div>
        </div>
        <div class="mirai-license-card">
            <div class="label">许可版本</div>
            <div class="value" id="mirai-license-plan"><?php echo htmlspecialchars($planDisplay); ?></div>
        </div>
        <div class="mirai-license-card">
            <div class="label">有效时间</div>
            <div class="value" id="mirai-license-expire"><?php echo htmlspecialchars($expDate); ?></div>
        </div>
        <div class="mirai-license-card">
            <div class="label">剩余天数</div>
            <div class="value" id="mirai-license-days"><?php echo $daysLeft !== null ? $daysLeft . ' 天' : '长期有效'; ?></div>
        </div>
    </div>
    
    <?php if ($isTrial): ?>
    <div class="mirai-license-upgrade-panel">
        <div class="mirai-license-upgrade-content">
            <div class="mirai-license-upgrade-left">
                <div class="mirai-license-upgrade-header">
                    <i class="ri-vip-crown-line"></i>
                    <span>升级到赞助版</span>
                </div>
                <div class="mirai-license-upgrade-price">
                    <span class="currency">¥</span>
                    <span class="amount">79</span>
                </div>
            </div>
            <div class="mirai-license-upgrade-contact">
                <div class="contact-item">
                    <i class="ri-qq-line"></i>
                    <span>1461139506</span>
                </div>
                <div class="contact-item">
                    <i class="ri-wechat-line"></i>
                    <span>Sakura1086</span>
                </div>

            </div>
            <div class="mirai-license-upgrade-actions">
                <button type="button" class="mirai-license-btn primary" onclick="showContactModal()">立即获取许可</button>
            </div>
            <div class="mirai-license-upgrade-notice">
                * 随着主题不断更新完善，价格也会不断调整
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="mirai-activate-modal" id="mirai-activate-modal">
        <div class="mirai-activate-dialog">
            <div class="mirai-activate-header">
                <h3>激活 Mirai 主题</h3>
                <button type="button" class="mirai-activate-close" aria-label="关闭">&times;</button>
            </div>
            <div class="mirai-activate-body">
                <p class="mirai-activate-desc">请输入您的许可密钥以激活主题</p>
                <div class="mirai-activate-input-wrapper">
                    <input type="text" 
                           id="mirai-activate-license-input" 
                           class="mirai-activate-input" 
                           placeholder="请输入许可密钥 (License Key)"
                           autocomplete="off">
                </div>
                <div class="mirai-activate-message" id="mirai-activate-message" style="display: none;"></div>
            </div>
            <div class="mirai-activate-footer">
                <button type="button" class="mirai-activate-btn primary" id="mirai-activate-submit-btn">
                    <span class="mirai-btn-text">验证并激活</span>
                    <span class="mirai-btn-spinner" aria-hidden="true"></span>
                </button>
                <button type="button" class="mirai-activate-btn success" onclick="showContactModal()">
                    获取许可
                </button>
                <button type="button" class="mirai-activate-btn ghost" id="mirai-activate-close-btn">取消</button>
            </div>
        </div>
    </div>

    <div class="mirai-update-modal" id="mirai-update-modal">
        <div class="mirai-update-dialog">
            <div class="mirai-update-header">
                <h3>主题更新</h3>
                <button type="button" class="mirai-update-close" aria-label="关闭">&times;</button>
            </div>
            <div class="mirai-update-body">
                <div class="mirai-update-version" id="mirai-update-version"></div>
                <div class="mirai-update-log" id="mirai-update-log"></div>
                <div class="mirai-update-status" id="mirai-update-status" style="font-size:13px;color:#64748b;"></div>
            </div>
            <div class="mirai-update-footer">
                <a href="#" target="_blank" class="mirai-update-btn" id="mirai-update-download" style="display:none;">
                    下载更新包
                </a>
                <button type="button" class="mirai-update-btn ghost" id="mirai-update-close-btn">关闭</button>
            </div>
        </div>
    </div>
</div>

<div class="mirai-contact-modal" id="mirai-contact-modal" style="display: none;" onclick="if(event.target===this)closeContactModal()">
    <div class="mirai-contact-dialog">
        <h3>获取许可 <button type="button" class="mirai-contact-close" onclick="closeContactModal()">&times;</button></h3>
        <div class="mirai-contact-body">
            <p>如需获取 Mirai 主题许可，请通过以下方式联系：</p>
            <div class="mirai-contact-item"><i class="ri-qq-line"></i>QQ：1461139506</div>
            <div class="mirai-contact-item"><i class="ri-wechat-line"></i>微信：Sakura1086</div>

        </div>
        <div class="mirai-contact-footer">
            <button type="button" class="mirai-contact-btn" onclick="closeContactModal()">知道了</button>
        </div>
    </div>
</div>

<script>
function showContactModal(){document.getElementById('mirai-contact-modal').style.display='flex'}
function closeContactModal(){document.getElementById('mirai-contact-modal').style.display='none'}
</script>

<input type="hidden" name="licenseCode" id="hiddenLicenseCode" value="<?php echo htmlspecialchars($licenseKey); ?>">