<?php
/**
 * 友情链接
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
$this->hideThemeSidebar = true;
?>
<?php
$pageOptions = Mirai_getLinksPageOptions($this);
$linksData = Mirai_getLinksData($pageOptions);
$popularLinks = $pageOptions['showRecommend'] ? Mirai_getPopularLinks() : [];
$recommendPosts = $pageOptions['showRecommendPosts'] ? Mirai_getLinksRecommendPosts(5) : [];
$categoryNav = [];
foreach ($linksData as $category) {
    $catId = isset($category['id']) ? (int)$category['id'] : 0;
    $anchor = $catId > 0 ? 'item-links-cat-' . $catId : 'item-links-cat-default';
    $categoryNav[] = [
        'anchor' => $anchor,
        'name' => isset($category['name']) ? $category['name'] : '默认分类',
    ];
}

$this->need('header.php');
?>
<div class="article-list-main item-links-page item-links-v2">
        <h1 class="visually-hidden"><?php $this->title(); ?></h1>
        <div class="item-shell">
            <?php if (!empty($categoryNav) && $pageOptions['showCategoryNav']): ?>
            <aside class="item-left">
                <ul class="aside-menu">
                    <?php foreach ($categoryNav as $index => $nav): ?>
                    <li class="menu-item">
                        <a href="#<?php echo htmlspecialchars($nav['anchor']); ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>">
                            <span class="menu-text"><?php echo htmlspecialchars($nav['name']); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
            <?php endif; ?>
            <div class="item-right">
                <div class="row g-3 g-xl-4 d-flex">
                    <?php if ($pageOptions['showRecommend']): ?>
                    <div class="col-12 col-md-7 col-xxxl-6 d-flex">
                        <div class="card card-xl flex-fill h-100">
                            <div class="card-header d-flex flex-nowrap text-nowrap gap-2 align-items-center">
                                <div class="h4"><?php echo htmlspecialchars($pageOptions['recommendTitle']); ?></div>
                            </div>
                            <div class="card-body">
                                <div class="recommend-two-columns">
                                    <?php if (!empty($popularLinks)): ?>
                                        <?php
                                        $perColumn = 5;
                                        $column1 = array_slice($popularLinks, 0, $perColumn);
                                        $column2 = array_slice($popularLinks, $perColumn, $perColumn);
                                        ?>
                                        <div class="recommend-column">
                                            <div class="list-number list-row list-bordered">
                                                <?php $index1 = 0; foreach ($column1 as $link): $index1++; ?>
                                                <div class="list-item <?php if ($index1 <= 3) echo 'rank-' . $index1; ?>">
                                                    <div class="list-content w-100">
                                                        <div class="list-body">
                                                            <div class="list-title h-1x">
                                                                <?php echo htmlspecialchars($link['name']); ?><?php if (!empty($link['description'])): ?> - <span class="text-muted"><?php echo htmlspecialchars($link['description']); ?></span><?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener noreferrer" class="list-goto" title="<?php echo htmlspecialchars($link['description'] ?: $link['name']); ?>"></a>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($column2)): ?>
                                        <div class="recommend-column">
                                            <div class="list-number list-row list-bordered list-bordered-start-6">
                                                <?php foreach ($column2 as $link): ?>
                                                <div class="list-item">
                                                    <div class="list-content w-100">
                                                        <div class="list-body">
                                                            <div class="list-title h-1x">
                                                                <?php echo htmlspecialchars($link['name']); ?><?php if (!empty($link['description'])): ?> - <span class="text-muted"><?php echo htmlspecialchars($link['description']); ?></span><?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener noreferrer" class="list-goto" title="<?php echo htmlspecialchars($link['description'] ?: $link['name']); ?>"></a>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <div class="item-empty-horizontal">暂无推荐站点</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($pageOptions['showRecommendPosts']): ?>
                    <div class="col-12 col-md-5 col-xxxl-6 d-flex">
                        <div class="card card-xl flex-fill">
                            <div class="card-header d-flex flex-nowrap text-nowrap gap-2 align-items-center">
                                <div class="h4"><?php echo htmlspecialchars($pageOptions['recommendPostsTitle']); ?></div>
                            </div>
                            <div class="card-body">
                                <div class="list-number list-row list-bordered">
                                    <?php if (!empty($recommendPosts)): ?>
                                        <?php foreach ($recommendPosts as $index => $post): ?>
                                        <div class="list-item <?php if ($index < 3) echo 'rank-' . ($index + 1); ?>">
                                            <div class="list-content w-100">
                                                <div class="list-body">
                                                    <div class="list-title h-1x">
                                                        <?php echo htmlspecialchars($post['title']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="<?php echo htmlspecialchars($post['url']); ?>" class="list-goto" title="<?php echo htmlspecialchars($post['title']); ?>"></a>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <div class="item-empty-horizontal">暂无推荐文章</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($linksData)): ?>
                        <?php foreach ($linksData as $category): ?>
                        <?php
                        $catId = isset($category['id']) ? (int)$category['id'] : 0;
                        $catAnchor = $catId > 0 ? 'item-links-cat-' . $catId : 'item-links-cat-default';
                        ?>
                        <div id="<?php echo htmlspecialchars($catAnchor); ?>" class="col-12">
                            <div class="card card-xl">
                                <div class="card-header d-flex flex-nowrap text-nowrap gap-2 align-items-center">
                                    <div class="h4"><?php echo htmlspecialchars($category['name']); ?></div>
                                    <?php if (!empty($category['description'])): ?>
                                    <div class="text-muted text-sm text-truncate item-category-desc"><?php echo htmlspecialchars($category['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 g-md-3 list-grid list-grid-padding">
                                        <?php foreach ($category['links'] as $link): ?>
                                        <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xxl-2">
                                            <div class="list-item shadow-none">
                                                <a role="button" href="<?php echo htmlspecialchars($link['url']); ?>" target="<?php echo htmlspecialchars($link['target']); ?>" rel="<?php echo htmlspecialchars($link['rel']); ?>" class="link-card-compact">
                                                    <div class="link-card-icon">
                                                        <?php if (!empty($link['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($link['image']); ?>" alt="<?php echo htmlspecialchars($link['name']); ?>">
                                                        <?php else: ?>
                                                        <span class="link-card-fallback"><?php echo htmlspecialchars(mb_substr($link['name'], 0, 1, 'UTF-8')); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="link-card-info">
                                                        <div class="link-card-title"><?php echo htmlspecialchars($link['name']); ?></div>
                                                        <div class="link-card-desc"><?php echo htmlspecialchars($link['description'] ?: '暂无描述'); ?></div>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="card card-xl">
                            <div class="card-body">
                                <div class="item-empty-horizontal">
                                    <span>暂无友情链接</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.body.classList.add('page-template-links');
document.addEventListener('DOMContentLoaded', function () {
    const menuLinks = document.querySelectorAll('.item-links-v2 .item-left .menu-item a');
    menuLinks.forEach((menuLink) => {
        menuLink.addEventListener('click', function (e) {
            e.preventDefault();
            menuLinks.forEach((el) => el.classList.remove('active'));
            this.classList.add('active');
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>
<script>
function openApplyLinkModal() {
    const modal = document.getElementById('applyLinkModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeApplyLinkModal(e) {
    if (e && e.target.id !== 'applyLinkModal') return;
    const modal = document.getElementById('applyLinkModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function handleApplyLinkSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('.gt-apply-link-submit');
    const originalText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>提交中...</span>';
    
    fetch('<?php echo Typecho_Widget::widget("Widget_Options")->index; ?>/action/links-submit', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showApplyLinkMessage(data.message || '提交成功，等待审核', 'success');
            form.reset();
            setTimeout(() => {
                closeApplyLinkModal();
            }, 2000);
        } else {
            showApplyLinkMessage(data.message || '提交失败，请重试', 'error');
        }
    })
    .catch(error => {
        showApplyLinkMessage('网络错误，请稍后重试', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function showApplyLinkMessage(message, type) {
    const existingMsg = document.querySelector('.gt-apply-link-message');
    if (existingMsg) {
        existingMsg.remove();
    }
    
    const msgDiv = document.createElement('div');
    msgDiv.className = 'gt-apply-link-message ' + type;
    msgDiv.innerHTML = '<span>' + message + '</span>';
    
    const form = document.getElementById('applyLinkForm');
    form.insertBefore(msgDiv, form.firstChild);
    setTimeout(() => {
        msgDiv.remove();
    }, 5000);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeApplyLinkModal();
    }
});
</script>

<div class="gt-apply-link" id="applyLinkBtn" title="申请友链" aria-label="申请友链">
    <button onclick="openApplyLinkModal()">
        <i class="ri-add-circle-fill"></i>
    </button>
</div>

<div class="gt-apply-link-modal" id="applyLinkModal" onclick="closeApplyLinkModal(event)">
    <div class="gt-apply-link-box" onclick="event.stopPropagation()">
        <div class="gt-apply-link-header">
            <div class="gt-apply-link-title">
                <h3>申请友链</h3>
            </div>
            <button onclick="closeApplyLinkModal()" class="modal-close-btn" title="关闭">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <form class="gt-apply-link-form" id="applyLinkForm" onsubmit="handleApplyLinkSubmit(event)">
            <input type="hidden" name="_token" value="<?php echo \Widget\Security::alloc()->getToken('links-submit'); ?>">
            
            <?php 
            $applyTips = $this->options->linksApplyTips;
            if (!empty($applyTips)): 
            ?>
            <div class="gt-apply-link-tips">
                <?php echo nl2br(htmlspecialchars($applyTips)); ?>
            </div>
            <?php endif; ?>
            
            <div class="gt-apply-link-row">
                <div class="gt-apply-link-col">
                    <label class="gt-apply-link-label">网站名称 <span class="required">(必填)</span></label>
                    <div class="gt-form-group">
                        <input type="text" name="linkName" placeholder="请输入网站名称" required>
                    </div>
                </div>
                <div class="gt-apply-link-col">
                    <label class="gt-apply-link-label">网站地址 <span class="required">(必填)</span></label>
                    <div class="gt-form-group">
                        <input type="url" name="linkUrl" placeholder="https://..." required>
                    </div>
                </div>
            </div>
            
            <div class="gt-apply-link-row">
                <div class="gt-apply-link-col">
                    <label class="gt-apply-link-label">网站简介</label>
                    <div class="gt-form-group">
                        <input type="text" name="linkDescription" placeholder="一句话介绍网站">
                    </div>
                </div>
                <div class="gt-apply-link-col">
                    <label class="gt-apply-link-label">网站LOGO</label>
                    <div class="gt-form-group">
                        <input type="url" name="linkImage" placeholder="https://...">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="gt-apply-link-submit">
                <span>提交申请</span>
            </button>
        </form>
    </div>
</div>

<?php $this->need('footer.php'); ?>
