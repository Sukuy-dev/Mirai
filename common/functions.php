<?php

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

define('MIRAI_FUNCTIONS_DIR', __DIR__ . '/functions');

require_once MIRAI_FUNCTIONS_DIR . '/core.php';
require_once MIRAI_FUNCTIONS_DIR . '/helper.php';
require_once MIRAI_FUNCTIONS_DIR . '/views.php';
require_once MIRAI_FUNCTIONS_DIR . '/image.php';
require_once MIRAI_FUNCTIONS_DIR . '/content.php';
require_once MIRAI_FUNCTIONS_DIR . '/category.php';
require_once MIRAI_FUNCTIONS_DIR . '/comment.php';
require_once MIRAI_FUNCTIONS_DIR . '/post.php';
require_once MIRAI_FUNCTIONS_DIR . '/seo.php';
require_once MIRAI_FUNCTIONS_DIR . '/navigation.php';
require_once MIRAI_FUNCTIONS_DIR . '/actions.php';
require_once MIRAI_FUNCTIONS_DIR . '/email.php';
require_once MIRAI_FUNCTIONS_DIR . '/pay.php';
require_once MIRAI_FUNCTIONS_DIR . '/vip.php';
require_once dirname(__DIR__) . '/common/api/router.php';
require_once MIRAI_FUNCTIONS_DIR . '/recommend.php';
require_once MIRAI_FUNCTIONS_DIR . '/sidebar.php';
require_once MIRAI_FUNCTIONS_DIR . '/location.php';
require_once MIRAI_FUNCTIONS_DIR . '/links.php';

// 文章撰写页扩展cover、keywords、description、views、excerpt 字段
function Mirai_addPostSidebarFields()
{
    // 检查是否在文章编辑页面
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $isPostPage = strpos($script, 'write-post.php') !== false;
    $isPagePage = strpos($script, 'write-page.php') !== false;

    if (!$isPostPage && !$isPagePage) {
        return;
    }

    // 获取当前文章ID
    $cid = intval($_GET['cid'] ?? 0);

    // 获取字段值
    $db = \Typecho\Db::get();
    $cover = '';
    $views = 0;
    $keywords = '';
    $description = '';
    $excerpt = '';

    if ($cid) {
        $post = $db->fetchRow($db->select('cover', 'views')->from('table.contents')->where('cid = ?', $cid));
        $cover = $post['cover'] ?? '';
        $views = intval($post['views'] ?? 0);

        $edk = $db->fetchRow($db->select()->from('table.mirai_contents_edk')->where('cid = ?', $cid));
        if ($edk) {
            $keywords = $edk['keywords'] ?? '';
            $description = html_entity_decode($edk['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $excerpt = html_entity_decode($edk['excerpt'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
?>
<section class="typecho-post-option">
    <label class="typecho-label">文章封面</label>
    <p><input type="text" name="fields[cover]" value="<?php echo htmlspecialchars($cover); ?>" placeholder="输入图片URL" id="mirai-cover-input" class="w-100 text"></p>
    <p><img id="mirai-cover-preview" style="max-width:100%;<?php echo $cover ? '' : 'display:none;'; ?>" src="<?php echo htmlspecialchars($cover); ?>" alt="封面预览"></p>
</section>

<section class="typecho-post-option">
    <label class="typecho-label">浏览量</label>
    <p><input type="number" name="fields[views]" value="<?php echo $views; ?>" min="0" placeholder="0" class="w-100 text"></p>
</section>

<section class="typecho-post-option">
    <label class="typecho-label">SEO关键词</label>
    <p><input type="text" name="fields[keywords]" value="<?php echo htmlspecialchars($keywords); ?>" placeholder="多个用逗号分隔" class="w-100 text"></p>
</section>

<section class="typecho-post-option">
    <label class="typecho-label">SEO描述</label>
    <p><textarea name="fields[description]" rows="2" placeholder="建议150-200字" class="w-100 text" style="resize:vertical;"><?php echo htmlspecialchars($description); ?></textarea></p>
</section>

<section class="typecho-post-option">
    <label class="typecho-label">文章摘要</label>
    <p><textarea name="fields[excerpt]" rows="3" placeholder="不填则自动从内容提取" class="w-100 text" style="resize:vertical;"><?php echo htmlspecialchars($excerpt); ?></textarea></p>
</section>

<script>
(function() {
    // 封面预览功能
    var coverInput = document.getElementById('mirai-cover-input');
    var coverPreview = document.getElementById('mirai-cover-preview');

    if (coverInput && coverPreview) {
        coverInput.addEventListener('input', function() {
            if (this.value) {
                coverPreview.src = this.value;
                coverPreview.style.display = 'block';
            } else {
                coverPreview.style.display = 'none';
            }
        });

        // 处理图片加载错误
        coverPreview.addEventListener('error', function() {
            if (coverInput.value) {
                this.style.display = 'none';
            }
        });
    }
})();
</script>
<?php
}
\Typecho\Plugin::factory('admin/write-post.php')->option = 'Mirai_addPostSidebarFields';
\Typecho\Plugin::factory('admin/write-page.php')->option = 'Mirai_addPostSidebarFields';
