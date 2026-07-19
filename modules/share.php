<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div id="mirai-share-modal">
    <div class="share-mask" onclick="MiraiShare.close()"></div>
    <div class="share-container">
        <div class="share-title">分享到</div>
        <div class="share-options">
            <div class="share-item" onclick="MiraiShare.to('qq')">
                <div class="share-icon icon-qq"><i class="ri-qq-fill"></i></div>
                QQ
            </div>
            <div class="share-item" onclick="MiraiShare.to('wechat')">
                <div class="share-icon icon-wechat"><i class="ri-wechat-fill"></i></div>
                微信
            </div>
            <div class="share-item" onclick="MiraiShare.to('weibo')">
                <div class="share-icon icon-weibo"><i class="ri-weibo-fill"></i></div>
                微博
            </div>
            <div class="share-item" onclick="MiraiShare.to('qzone')">
                <div class="share-icon icon-qzone"><i class="ri-qq-fill"></i></div>
                QQ空间
            </div>
            <div class="share-item" onclick="MiraiShare.poster()">
                <div class="share-icon icon-poster"><i class="ri-image-fill"></i></div>
                海报
            </div>
            <div class="share-item" onclick="MiraiShare.copy()">
                <div class="share-icon icon-link"><i class="ri-link"></i></div>
                复制链接
            </div>
        </div>
        <div class="share-close-btn" onclick="MiraiShare.close()">关闭</div>
    </div>
</div>

<div id="mirai-poster-modal" style="display:none">
    <div class="poster-mask" onclick="window.MiraiShare&&MiraiShare.closePoster()"></div>
    <div class="poster-content">
        <div class="poster-card">
            <div class="poster-close" onclick="window.MiraiShare&&MiraiShare.closePoster()" title="关闭"><i class="ri-close-line"></i></div>
            <img id="mirai-generated-poster" src="">
            <div class="poster-tip">长按保存图片</div>
        </div>
    </div>
</div>

<div id="mirai-wechat-modal">
    <div class="wechat-mask" onclick="MiraiShare.closeWechat()"></div>
    <div class="wechat-content">
        <div class="wechat-title">微信扫一扫分享</div>
        <div id="mirai-wechat-qrcode" class="wechat-qr"></div>
        <div class="wechat-desc">打开微信，点击底部的"发现"，<br>使用"扫一扫"即可分享给好友。</div>
        <div class="wechat-close" onclick="MiraiShare.closeWechat()"><i class="ri-close-line"></i></div>
    </div>
</div>

<?php if (!isset($this->is404) || !$this->is404): ?>
<?php
    $share_title = '';
    $share_author = '';
    $share_cover = '';
    $share_date = '';
    $share_desc = '';
    $share_cat = '';
    $share_url = '';

    if ($this->is('post') || $this->is('page')) {
        $share_title = $this->title;
        $share_author = $this->options->authorName ? $this->options->authorName : $this->author->screenName;
        $share_cover = Mirai_normalizeUrl($this->fields->cover ? $this->fields->cover : Mirai_getPostCover($this));
        ob_start();
        $this->date('Y-m-d');
        $share_date = ob_get_clean();
        $share_desc = $this->fields->description ? $this->fields->description : ($this->fields->excerpt ? $this->fields->excerpt : Mirai_getPostExcerpt($this, 140));
        $share_cat = count($this->categories) > 0 ? $this->categories[0]['name'] : '';
        $share_url = $this->permalink;
    } elseif ($this->is('archive')) {
        ob_start();
        $this->archiveTitle(':', '', '');
        $share_title = ob_get_clean();
        $share_author = $this->options->authorName; // Site author
        $share_cover = Mirai_normalizeUrl($this->options->logoImage); // Site logo
        $share_date = ''; // No date for archives
        $share_desc = $this->getDescription() ? $this->getDescription() : ($this->options->siteDescription ?: $this->options->description);
        if ($this->is('category')) {
            $share_cat = $this->getArchiveTitle();
        }

        $share_url = $this->archiveUrl;
    }
    ?>
<div id="mirai-share-data" style="display:none"
    data-title="<?php echo htmlspecialchars($share_title ?? ''); ?>"
    data-url="<?php echo htmlspecialchars($share_url ?? ''); ?>"
    data-author="<?php echo htmlspecialchars($share_author ?? ''); ?>"
    data-cover="<?php echo htmlspecialchars($share_cover ?? ''); ?>"
    data-logo="<?php echo htmlspecialchars($this->options->logoImage ? Mirai_normalizeUrl($this->options->logoImage) : $this->options->themeUrl . '/assets/images/logo.png'); ?>"
    data-date="<?php echo htmlspecialchars($share_date ?? ''); ?>"
    data-desc="<?php echo htmlspecialchars($share_desc ?? ''); ?>"
    data-cat="<?php echo htmlspecialchars($share_cat ?? ''); ?>"
></div>
<?php endif; ?>
<canvas id="mirai-poster-canvas" style="display:none"></canvas>

<script>
    window.shareArticle = function() {
        if (typeof MiraiShare !== 'undefined') return MiraiShare.open();
        
        var script = document.createElement('script');
        script.src = '<?php $this->options->themeUrl("assets/js/share.js"); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>';
        script.onload = function() {
            if (typeof MiraiShare !== 'undefined') {
                MiraiShare.init();
                MiraiShare.open();
            }
        };
        document.body.appendChild(script);
    };
</script>