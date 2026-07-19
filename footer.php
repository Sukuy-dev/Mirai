<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
        </div>
        <?php 
        $shouldHideSidebar = (isset($this->hideThemeSidebar) && $this->hideThemeSidebar) || 
                             (isset($GLOBALS['_temp_hide_sidebar']) && $GLOBALS['_temp_hide_sidebar']);
        $asideEnable = !isset($this->options->asideEnable) || $this->options->asideEnable !== '0';
        
        if (!$shouldHideSidebar && $asideEnable): 
        ?>
        <?php $this->need('modules/sidebar.php'); ?>
        <?php endif; ?>
        
        <?php $this->need('modules/mobile/sidebar.php'); ?>
    </section>
</main>
<footer class="gt-footer" role="contentinfo">
    <div class="gt-footer-main">
        <div class="gt-footer-grid">
            <div class="gt-footer-brand">
                <?php
                $footerLogoAlt = $this->options->siteTitle ? $this->options->siteTitle : $this->options->title;
                ?>
                <a class="gt-footer-logo" href="<?php $this->options->siteUrl(); ?>" rel="home" aria-label="<?php echo htmlspecialchars($footerLogoAlt); ?>" title="<?php echo htmlspecialchars($footerLogoAlt); ?>">
                    <?php
                    $footerLogoImage = $this->options->logoImage ? $this->options->logoImage : 'usr/themes/Mirai/assets/images/logo.png';
                    $footerLogoUrl = Mirai_normalizeUrl($footerLogoImage);
                    $footerDarkLogoUrl = $this->options->darkLogoImage ? Mirai_normalizeUrl($this->options->darkLogoImage) : '';
                    $footerLogoHeight = !empty($this->options->logoHeight) ? intval($this->options->logoHeight) : 40;
                    ?>
                    <?php if ($footerDarkLogoUrl): ?>
                        <img src="<?php echo htmlspecialchars($footerLogoUrl); ?>" alt="<?php echo htmlspecialchars($footerLogoAlt); ?>" class="gt-footer-logo-img mirai-logo-light" height="<?php echo $footerLogoHeight; ?>">
                        <img src="<?php echo htmlspecialchars($footerDarkLogoUrl); ?>" alt="<?php echo htmlspecialchars($footerLogoAlt); ?>" class="gt-footer-logo-img mirai-logo-dark" height="<?php echo $footerLogoHeight; ?>">
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($footerLogoUrl); ?>" alt="<?php echo htmlspecialchars($footerLogoAlt); ?>" class="gt-footer-logo-img" height="<?php echo $footerLogoHeight; ?>">
                    <?php endif; ?>
                </a>
                <?php 
                    $footerDesc = $this->options->footerDesc;
                    if (!$footerDesc) {
                        $footerDesc = $this->options->siteDescription;
                    }
                ?>
                <?php if ($footerDesc): ?>
                <div class="gt-footer-desc"><?php echo nl2br(htmlspecialchars($footerDesc)); ?></div>
                <?php endif; ?>
            </div>
            <div class="gt-footer-center" aria-label="底部中间内容">
                <div class="gt-footer-custom">
                    <?php Mirai_customFooterCode(); ?>
                </div>
                <div class="gt-footer-bottom">
                    <?php if ($this->options->icpNum): ?>
                    <p class="footer-beian">
                        <span><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow"><?php echo htmlspecialchars($this->options->icpNum); ?></a></span>
                    </p>
                    <?php endif; ?>
                    <div class="footer-copyright">
                        <?php echo $this->options->footerCopyright ?: '本站由 <a href="https://www.sukuy.com/article/mirai-theme" target="_blank" rel="noopener">Mirai未来主题</a> 驱动'; ?>
                    </div>
                </div>
            </div>
            <?php
                $leftQr = $this->options->footerLeftQr ? Mirai_normalizeUrl($this->options->footerLeftQr) : '';
                $rightQr = $this->options->footerRightQr ? Mirai_normalizeUrl($this->options->footerRightQr) : '';
                $qrSize = $this->options->footerQrSize ? $this->options->footerQrSize : '99';
            ?>
            <?php if ($leftQr || $rightQr): ?>
            <div class="gt-footer-qrcodes">
                <?php if ($leftQr): ?>
                <div class="gt-footer-qrcode">
                    <img class="gt-footer-qrcode-img" src="<?php echo $leftQr; ?>" alt="<?php $this->options->footerLeftQrText(); ?>" width="<?php echo $qrSize; ?>" height="<?php echo $qrSize; ?>">
                    <span class="gt-footer-qrcode-text"><?php $this->options->footerLeftQrText(); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($rightQr): ?>
                <div class="gt-footer-qrcode">
                    <img class="gt-footer-qrcode-img" src="<?php echo $rightQr; ?>" alt="<?php $this->options->footerRightQrText(); ?>" width="<?php echo $qrSize; ?>" height="<?php echo $qrSize; ?>">
                    <span class="gt-footer-qrcode-text"><?php $this->options->footerRightQrText(); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</footer>

<?php echo Mirai_renderMobileBottomTab(); ?>
<?php if (Mirai_featureEnabled('seo') && $this->options->codeHighlight == '1' && $this->is('single')): ?>
     <?php if (strpos($this->content, '<pre') !== false): ?>
         <script src="<?php $this->options->themeUrl('assets/js/highlight.min.js'); ?>" defer></script>
     <?php endif; ?>
<?php endif; ?>
<?php if ($this->is('single') || $this->is('page') || $this->is('archive')): ?>
<script src="<?php $this->options->themeUrl('assets/js/lightbox.js'); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>" defer></script>
<?php endif; ?>
<script src="<?php $this->options->themeUrl('assets/js/mirai.js'); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>" defer></script>
<?php 
$frontendLoginEnabled = Mirai_isUserCenterAuthEnabled($this->options);
?>
<?php if (Mirai_featureEnabled('speed') && $this->options->instantPageEnable != '0'): ?>
<script src="<?php $this->options->themeUrl('assets/js/instantpage-5.2.0.js'); ?>" type="module"></script>
<?php endif; ?>
<?php if ($frontendLoginEnabled): ?>
<?php $this->need('modules/modal.php'); ?>
<?php endif; ?>
<?php if ($this->is('post') || $this->is('page')): ?>
<?php $this->need('modules/share.php'); ?>
<?php endif; ?>
<?php $this->footer(); ?>
</body>
</html>