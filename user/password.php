<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="user-module module-password">
    <div class="module-header">
        <div class="module-title">修改密码</div>
    </div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
<?php endif; ?>

    <form method="post" action="" class="password-form">
        <input type="hidden" name="action" value="update_password">
        
        <div class="form-group">
            <label>当前密码</label>
            <input type="password" name="current_password" required class="form-control" autocomplete="current-password" placeholder="请输入当前登录密码">
        </div>
        
        <div class="form-group">
            <label>新密码</label>
            <input type="password" name="password" required class="form-control" minlength="6" autocomplete="new-password" placeholder="请输入新密码（至少6位）">
        </div>

        <div class="form-group">
            <label>确认新密码</label>
            <input type="password" name="confirm" required class="form-control" minlength="6" autocomplete="new-password" placeholder="请再次输入新密码">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">修改密码</button>
        </div>
    </form>
</div>
