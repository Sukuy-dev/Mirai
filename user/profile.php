<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$currentAvatar = Mirai_getUserAvatar($this->user->uid);
$currentCover = !empty($this->user->cover) ? $this->user->cover : '';
?>
<div class="user-module module-profile">
    <div class="module-header">
        <div class="module-title">个人资料</div>
    </div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
<?php endif; ?>

    <form method="post" action="" class="profile-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">
        
        <div class="form-group avatar-group">
            <label>头像</label>
            <div class="avatar-upload-wrapper">
                <div class="avatar-preview">
                    <img src="<?php echo $currentAvatar; ?>" alt="当前头像" id="avatarPreview">
                </div>
                <div class="avatar-upload-input">
                    <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control">
                    <p class="help-text">支持 JPG、PNG、GIF、WebP 格式，大小不超过 2MB</p>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>昵称</label>
            <input type="text" name="screenName" value="<?php $this->user->screenName(); ?>" required class="form-control">
        </div>

        <div class="form-group">
            <label>邮箱</label>
            <input type="email" name="mail" value="<?php $this->user->mail(); ?>" required class="form-control">
        </div>

        <div class="form-group">
            <label>个人网站</label>
            <input type="url" name="url" value="<?php echo htmlspecialchars($this->user->url ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="你的个人网站">
        </div>

        <div class="form-group">
            <label>个人简介</label>
            <input type="text" name="motto" value="<?php echo htmlspecialchars($this->user->motto ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="介绍你自己，最多100字符" maxlength="100">
        </div>

        <div class="form-group cover-group">
            <label>主页背景图</label>
            <input type="text" name="cover" value="<?php echo htmlspecialchars($currentCover, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="输入图片URL，留空使用默认背景图">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">保存修改</button>
        </div>
    </form>
</div>

<script>
// 头像预览
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>
