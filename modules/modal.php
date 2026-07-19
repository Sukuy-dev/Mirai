<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if ($this->user->hasLogin()) {
    return;
}

$index = (string)$this->options->index;
$sep = strpos($index, '?') === false ? '?' : '&';
$apiBase = $index . $sep . 'mirai_api=';
$token = \Widget\Security::alloc()->getToken('api');
$loginModalImage = $this->options->loginModalImage ?? '';
if ($loginModalImage) {
    $loginModalImage = Mirai_normalizeUrl($loginModalImage);
}
$options = Mirai_opt();
$allowRegister = $options->allowRegister;
$enableEmailVerify = $options->enableEmailVerify === '1';
$enableUserAgreement = isset($options->enableUserAgreement) && $options->enableUserAgreement === '1';
$userAgreementUrl = $options->userAgreementUrl ?? '';
$userAgreementName = $options->userAgreementName ?: '用户协议';
$enablePrivacyPolicy = isset($options->enablePrivacyPolicy) && $options->enablePrivacyPolicy === '1';
$privacyPolicyUrl = $options->privacyPolicyUrl ?? '';
$privacyPolicyName = $options->privacyPolicyName ?: '隐私政策';

?>
<div class="login-dialog" id="loginModal" style="display:none;">
  <div class="login-dialog-mask" onclick="closeLoginModal()"></div>
  <div class="login-dialog-content-wrap">
    <div class="login-dialog-content">
      <div class="login-dialog-close" onclick="closeLoginModal()">
        <i class="ri-close-line"></i>
      </div>

      <?php if ($loginModalImage): ?>
      <div class="login-dialog-side-image">
        <img class="login-dialog-side-image-img" src="<?php echo htmlspecialchars($loginModalImage); ?>" alt="登录注册横幅图">
      </div>
      <?php endif; ?>

      <div class="login-dialog-main<?php echo $loginModalImage ? ' has-image' : ''; ?>">
        <div class="login-tab-content">

          <div class="login-tab-pane active" id="tab-login">
            <div class="login-tab-header">
              <div class="login-tab-title-wrap">
                <h2 class="login-tab-title">登录</h2>
                <?php if ($allowRegister): ?>
                <a href="javascript:;" class="login-tab-switch login-tab-switch-sub" data-tab="register">没有账号？立即注册</a>
                <?php endif; ?>
              </div>
            </div>
            <form class="login-dialog-form" id="loginForm" action="<?php echo htmlspecialchars($apiBase . 'auth_login'); ?>" method="post" onsubmit="return handleAjaxLogin(event)">
              <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
              <div class="login-dialog-input-group">
                <input type="text" name="name" placeholder="请输入账号" required pattern="\S+.*" autocomplete="username">
              </div>
              <div class="login-dialog-input-group">
                <input type="password" name="password" placeholder="请输入密码" autocomplete="current-password" required pattern="\S+.*">
              </div>
              <div class="login-dialog-input-group remember">
                <label class="remember-label" for="remember">
                  <input type="checkbox" name="remember" value="1" id="remember">
                  <span class="login-dialog-input-checkbox">
                    <i class="ri-check-line"></i>
                  </span>
                  <span class="login-dialog-input-checkbox-text">记住登录</span>
                </label>
                <a href="javascript:;" class="form-link pull-right" data-tab="reset">忘记密码?</a>
              </div>
              <div class="login-dialog-input-group">
                <button type="submit" class="login-dialog-form-btn" id="loginSubmitBtn">
                  <span class="btn-text">登录</span>
                  <span class="btn-loading" style="display: none;">
                    登录中...
                  </span>
                </button>
              </div>
            </form>
            <?php if ($enableUserAgreement || $enablePrivacyPolicy): ?>
            <div class="login-tab-footer">
              登录即表示同意<?php if ($enableUserAgreement): ?><a class="login-dialog-link" href="javascript:;" onclick="showProtocol('user')"><?php echo htmlspecialchars($userAgreementName); ?></a><?php endif; ?><?php if ($enableUserAgreement && $enablePrivacyPolicy): ?>和<?php endif; ?><?php if ($enablePrivacyPolicy): ?><a class="login-dialog-link" href="javascript:;" onclick="showProtocol('privacy')"><?php echo htmlspecialchars($privacyPolicyName); ?></a><?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <?php if ($allowRegister): ?>
          <div class="login-tab-pane" id="tab-register">
            <div class="login-tab-header">
              <div class="login-tab-title-wrap">
                <h2 class="login-tab-title">注册</h2>
                <a href="javascript:;" class="login-tab-switch login-tab-switch-sub" data-tab="login">已有账号？立即登录</a>
              </div>
            </div>
            <form class="login-dialog-form" id="registerForm" action="<?php echo htmlspecialchars($apiBase . 'auth_register'); ?>" method="post" onsubmit="return handleAjaxRegister(event)">
              <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
              <div class="login-dialog-input-group">
                <input type="text" name="name" placeholder="请输入用户名" required pattern="\S+.*" autocomplete="username">
              </div>
              <div class="login-dialog-input-group">
                <input type="email" name="mail" placeholder="请输入邮箱" required autocomplete="email">
              </div>
              <?php if ($enableEmailVerify): ?>
              <div class="login-dialog-input-group verify-group">
                <div class="verify-input-wrap">
                  <input type="text" name="code" placeholder="请输入验证码" required pattern="\d{6}" maxlength="6">
                  <button type="button" class="send-code-btn" onclick="sendVerifyCode(this, 'register')">获取验证码</button>
                </div>
              </div>
              <?php endif; ?>
              <div class="login-dialog-input-group">
                <input type="password" name="password" placeholder="请输入密码（至少6位）" required minlength="6" autocomplete="new-password">
              </div>
              <div class="login-dialog-input-group">
                <input type="password" name="confirm" placeholder="请确认密码" required minlength="6" autocomplete="new-password">
              </div>
              <div class="login-dialog-input-group">
                <button type="submit" class="login-dialog-form-btn" id="registerSubmitBtn">
                  <span class="btn-text">注册</span>
                  <span class="btn-loading" style="display: none;">
                    注册中...
                  </span>
                </button>
              </div>
            </form>
            <?php if ($enableUserAgreement || $enablePrivacyPolicy): ?>
            <div class="login-tab-footer">
              注册即表示同意<?php if ($enableUserAgreement): ?><a class="login-dialog-link" href="javascript:;" onclick="showProtocol('user')"><?php echo htmlspecialchars($userAgreementName); ?></a><?php endif; ?><?php if ($enableUserAgreement && $enablePrivacyPolicy): ?>和<?php endif; ?><?php if ($enablePrivacyPolicy): ?><a class="login-dialog-link" href="javascript:;" onclick="showProtocol('privacy')"><?php echo htmlspecialchars($privacyPolicyName); ?></a><?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <div class="login-tab-pane" id="tab-reset">
            <div class="login-tab-header">
              <div class="login-tab-title-wrap">
                <h2 class="login-tab-title">找回密码</h2>
                <a href="javascript:;" class="login-tab-switch login-tab-switch-sub" data-tab="login">返回登录</a>
              </div>
            </div>
            <div class="reset-step" id="reset-step-1">
              <form class="login-dialog-form" id="resetForm1" onsubmit="return handleResetStep1(event)">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="login-dialog-input-group">
                  <input type="email" name="mail" placeholder="请输入注册邮箱" required autocomplete="email">
                </div>
                <div class="login-dialog-input-group verify-group">
                  <div class="verify-input-wrap">
                    <input type="text" name="code" placeholder="请输入验证码" required pattern="\d{6}" maxlength="6">
                    <button type="button" class="send-code-btn" onclick="sendVerifyCode(this, 'reset')">获取验证码</button>
                  </div>
                </div>
                <div class="login-dialog-input-group">
                  <button type="submit" class="login-dialog-form-btn" id="reset1SubmitBtn">
                    <span class="btn-text">下一步</span>
                    <span class="btn-loading" style="display: none;">
                      验证中...
                    </span>
                  </button>
                </div>
              </form>
            </div>
            <div class="reset-step" id="reset-step-2" style="display: none;">
              <form class="login-dialog-form" id="resetForm2" action="<?php echo htmlspecialchars($apiBase . 'auth_reset_step2'); ?>" method="post" onsubmit="return handleResetStep2(event)">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="mail" id="reset-mail-hidden">
                <input type="hidden" name="code" id="reset-code-hidden">
                <div class="login-dialog-input-group">
                  <input type="password" name="password" placeholder="请输入新密码（至少6位）" required minlength="6" autocomplete="new-password">
                </div>
                <div class="login-dialog-input-group">
                  <input type="password" name="confirm" placeholder="请确认新密码" required autocomplete="new-password">
                </div>
                <div class="login-dialog-input-group">
                  <button type="submit" class="login-dialog-form-btn" id="reset2SubmitBtn">
                    <span class="btn-text">重置密码</span>
                    <span class="btn-loading" style="display: none;">
                      处理中...
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>
<script>
window.miraiProtocolConfig = {
    userAgreement: {
        enabled: <?php echo $enableUserAgreement ? 'true' : 'false'; ?>,
        url: <?php echo json_encode($userAgreementUrl); ?>,
        name: <?php echo json_encode($userAgreementName); ?>
    },
    privacyPolicy: {
        enabled: <?php echo $enablePrivacyPolicy ? 'true' : 'false'; ?>,
        url: <?php echo json_encode($privacyPolicyUrl); ?>,
        name: <?php echo json_encode($privacyPolicyName); ?>
    }
};
</script>
