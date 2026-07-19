<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$db = \Typecho\Db::get();

// 初始化变量
$cid = $this->request->get('cid');
$post = null;
$title = '';
$text = '';
$cover = '';
$excerpt = '';
$keywords = '';
$description = '';
$currentCategory = 0;
$tags = '';
$editorType = isset($this->options->miraiEditorType) ? $this->options->miraiEditorType : 'default';
$useEditorMd = Mirai_featureEnabled('editor') && $editorType === 'editormd';
$editorConfig = [
    'height' => isset($this->options->miraiEditorHeight) ? max(360, intval($this->options->miraiEditorHeight)) : 640,
    'theme' => isset($this->options->miraiEditorTheme) ? $this->options->miraiEditorTheme : 'default',
    'previewTheme' => isset($this->options->miraiEditorPreviewTheme) ? $this->options->miraiEditorPreviewTheme : 'default',
    'editorTheme' => isset($this->options->miraiEditorEditorTheme) ? $this->options->miraiEditorEditorTheme : 'default',
];
$editorUploadUrl = \Widget\Security::alloc()->getTokenUrl(\Typecho\Common::url('/action/upload', $this->options->index));

// 如果是编辑模式，获取文章数据
if ($cid) {
    $post = $db->fetchRow($db->select()->from('table.contents')
        ->where('cid = ?', $cid)
        ->where('authorId = ?', $this->user->uid)
        ->where('type = ?', 'post'));
        
    if ($post) {
        $title = $post['title'];
        $text = $post['text'];
        
        // 获取封面 (从 contents 表的 cover 字段)
        $cover = $post['cover'] ?? '';
        
        // 获取SEO字段 (从 mirai_contents_edk 表)
        $prefix = $db->getPrefix();
        $edkTable = $prefix . 'mirai_contents_edk';
        if ($db->fetchRow("SHOW TABLES LIKE '{$edkTable}'")) {
            $edkData = $db->fetchRow($db->select()->from('table.mirai_contents_edk')->where('cid = ?', $cid));
            if ($edkData) {
                $excerpt = $edkData['excerpt'] ?? '';
                $keywords = $edkData['keywords'] ?? '';
                $description = $edkData['description'] ?? '';
            }
        }
        
        // 获取分类
        $categoryRel = $db->fetchRow($db->select('table.relationships.mid')->from('table.relationships')
            ->join('table.metas', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $cid)
            ->where('table.metas.type = ?', 'category')
            ->limit(1));
        if ($categoryRel) {
            $currentCategory = $categoryRel['mid'];
        }
        
        // 获取标签
        $tagRels = $db->fetchAll($db->select('name')->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $cid)
            ->where('table.metas.type = ?', 'tag'));
        $tags = implode(',', array_column($tagRels, 'name'));
    }
}
?>
<div class="user-module module-write">
    <div class="module-header">
        <div class="module-title"><?php echo $post ? '编辑文章' : '发布投稿'; ?></div>
    </div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
<?php endif; ?>

    <form method="post" action="" class="write-form">
        <input type="hidden" name="action" value="write_post">
        <?php if ($post): ?>
            <input type="hidden" name="cid" value="<?php echo $cid; ?>">
        <?php endif; ?>
        
        <div class="write-layout">
            <div class="write-main">
                <div class="form-group">
                    <input type="text" name="title" placeholder="请输入文章标题（选填）" class="form-control write-title" value="<?php echo htmlspecialchars($title); ?>">
                </div>

                <div class="form-group">
                    <textarea id="user-write-text" name="text" placeholder="在此输入文章内容...（选填）" class="form-control editor-area" rows="15"><?php echo htmlspecialchars($text); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="field-label">文章摘要</label>
                    <textarea name="excerpt" placeholder="输入文章摘要，如不填写将自动截取文章内容" class="form-control" rows="3"><?php echo htmlspecialchars($excerpt); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="field-label">文章标签</label>
                    <input type="text" name="tags" placeholder="多个标签用英文逗号分隔" class="form-control" value="<?php echo htmlspecialchars($tags); ?>">
                </div>
                
                <div class="form-group seo-fields">
                    <label class="field-label">文章关键词</label>
                    <input type="text" name="keywords" placeholder="多个关键词用英文逗号分隔" class="form-control" value="<?php echo htmlspecialchars($keywords); ?>">
                </div>
                
                <div class="form-group seo-fields">
                    <label class="field-label">文章描述</label>
                    <textarea name="description" placeholder="输入文章描述，建议150-200字" class="form-control" rows="2"><?php echo htmlspecialchars($description); ?></textarea>
                </div>
            </div>

            <div class="write-sidebar">
                <div class="sidebar-section">
                    <h4 class="section-title">发布设置</h4>
                    
                    <div class="form-group">
                        <label>选择分类</label>
                        <select name="category" class="form-control">
                            <option value="0">请选择分类...</option>
                            <?php
                            $categories = $db->fetchAll($db->select()->from('table.metas')
                                ->where('type = ?', 'category')
                                ->order('order', \Typecho\Db::SORT_ASC));
                            foreach ($categories as $category) {
                                $selected = ($category['mid'] == $currentCategory) ? 'selected' : '';
                                echo '<option value="' . $category['mid'] . '" ' . $selected . '>' . $category['name'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>文章封面</label>
                        <div class="cover-upload-wrapper">
                            <input type="text" name="cover" id="cover-input" placeholder="请输入图片URL或点击上传" class="form-control" value="<?php echo htmlspecialchars($cover); ?>">
                            <input type="hidden" name="cover_cid" id="cover-cid" value="">
                            <input type="file" id="cover-file-input" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('cover-file-input').click()">上传</button>
                        </div>
                        <div class="cover-preview" id="cover-preview" style="<?php echo $cover ? '' : 'display: none;'; ?>">
                            <img id="cover-preview-img" src="<?php echo htmlspecialchars($cover); ?>" alt="封面预览">
                            <button type="button" class="cover-remove" onclick="removeCover()" title="删除封面">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="cover-upload-progress" id="cover-progress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill" id="cover-progress-fill"></div>
                            </div>
                            <span class="progress-text">上传中...</span>
                        </div>
                    </div>
                </div>

                <div class="sidebar-section">
                    <div class="form-actions form-actions-row">
                        <button type="submit" name="do" value="draft" class="btn btn-secondary">存为草稿</button>
                        <button type="submit" name="do" value="publish" class="btn btn-primary"><?php echo $post ? '保存并发布' : '立即发布'; ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php if ($useEditorMd): ?>
<link rel="stylesheet" href="<?php $this->options->themeUrl('common/lib/editor.md/css/editormd.min.css'); ?>">
<script>
(function () {
    var editorConfig = <?php echo json_encode($editorConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var uploadUrl = <?php echo json_encode($editorUploadUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var libPath = <?php echo json_encode(rtrim($this->options->themeUrl, '/') . '/common/lib/editor.md/lib/', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var jqueryUrl = <?php echo json_encode(rtrim($this->options->adminUrl, '/') . '/js/jquery.js', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var editorScriptUrl = <?php echo json_encode(rtrim($this->options->themeUrl, '/') . '/common/lib/editor.md/editormd.min.js', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    function loadScript(url, callback) {
        var script = document.createElement('script');
        script.src = url;
        script.onload = callback;
        script.onerror = callback;
        document.head.appendChild(script);
    }

    function ensureDependencies(callback) {
        var hasJquery = !!window.jQuery;
        var hasEditorMd = typeof window.editormd === 'function';

        if (hasJquery && hasEditorMd) {
            callback();
            return;
        }

        var next = function () {
            if (typeof window.editormd === 'function') {
                callback();
                return;
            }
            loadScript(editorScriptUrl, callback);
        };

        if (!hasJquery) {
            loadScript(jqueryUrl, next);
            return;
        }

        next();
    }

    function initMiraiUserWriteEditor() {
        if (typeof window.editormd !== 'function') {
            return;
        }

        var textarea = document.getElementById('user-write-text');
        if (!textarea || textarea.dataset.editorReady === '1') {
            return;
        }

        var editorContainer = document.createElement('div');
        editorContainer.id = 'mirai-user-write-editormd';
        textarea.parentNode.insertBefore(editorContainer, textarea);
        editorContainer.appendChild(textarea);
        textarea.style.display = 'none';
        textarea.dataset.editorReady = '1';

        var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        var isDarkMode = currentTheme === 'dark';
        var resolvedTheme = editorConfig.theme || 'default';
        var resolvedPreviewTheme = editorConfig.previewTheme || 'default';
        var resolvedEditorTheme = editorConfig.editorTheme || 'default';

        if (isDarkMode) {
            if (resolvedTheme === 'default') {
                resolvedTheme = 'dark';
            }
            if (resolvedPreviewTheme === 'default') {
                resolvedPreviewTheme = 'dark';
            }
            if (resolvedEditorTheme === 'default') {
                resolvedEditorTheme = 'pastel-on-dark';
            }
        }

        var miraiUserEditor = window.editormd(editorContainer.id, {
            width: '100%',
            height: editorConfig.height || 640,
            path: libPath,
            theme: resolvedTheme,
            previewTheme: resolvedPreviewTheme,
            editorTheme: resolvedEditorTheme,
            markdown: textarea.value || '',
            watch: false,
            lineNumbers: false,
            codeFold: false,
            saveHTMLToTextarea: false,
            searchReplace: false,
            htmlDecode: true,
            taskList: true,
            tocm: false,
            tex: false,
            flowChart: false,
            sequenceDiagram: false,
            autoFocus: false,
            imageUpload: true,
            imageFormats: ['jpg', 'jpeg', 'gif', 'png', 'avif', 'webp'],
            imageUploadURL: uploadUrl,
            syncScrolling: 'single',
            toolbarIcons: function () {
                return [
                    'bold', 'del', 'italic', 'quote', '|',
                    'h1', 'h2', 'h3', '|',
                    'list-ul', 'list-ol', 'hr', '|',
                    'link', 'image', 'code', 'code-block', 'table', '|',
                    'search', 'watch', 'preview', 'fullscreen', 'help'
                ];
            },
            onchange: function () {
                textarea.value = this.getMarkdown();
            }
        });

        var form = document.querySelector('.write-form');
        if (form) {
            form.addEventListener('submit', function () {
                if (miraiUserEditor && typeof miraiUserEditor.getMarkdown === 'function') {
                    textarea.value = miraiUserEditor.getMarkdown();
                }
            });
        }
    }

    function initCoverUpload() {
        var fileInput = document.getElementById('cover-file-input');
        var coverInput = document.getElementById('cover-input');
        var coverCidInput = document.getElementById('cover-cid');
        var preview = document.getElementById('cover-preview');
        var previewImg = document.getElementById('cover-preview-img');
        var progress = document.getElementById('cover-progress');
        var progressFill = document.getElementById('cover-progress-fill');
        
        if (!fileInput) return;
        
        var uploadUrl = <?php echo json_encode($editorUploadUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        var deleteUrl = <?php echo json_encode(\Widget\Security::alloc()->getTokenUrl(\Typecho\Common::url('/action/contents-attachment-edit', $this->options->index)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        
        fileInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;
            
            if (!file.type.match(/^image\/(jpeg|jpg|png|gif|webp|avif)$/i)) {
                alert('请选择有效的图片文件 (jpg, jpeg, png, gif, webp, avif)');
                return;
            }
            
            progress.style.display = 'block';
            progressFill.style.width = '10%';
            
            var url = new URL(uploadUrl);
            var cid = document.querySelector('input[name="cid"]');
            if (cid && cid.value) {
                url.searchParams.append('cid', cid.value);
            }
            
            var formData = new FormData();
            formData.append('file', file);
            
            fetch(url.toString(), {
                method: 'POST',
                body: formData
            }).then(function(response) {
                progressFill.style.width = '80%';
                if (response.ok) {
                    return response.json();
                } else {
                    throw new Error('上传失败');
                }
            }).then(function(data) {
                progressFill.style.width = '100%';
                setTimeout(function() {
                    progress.style.display = 'none';
                }, 500);
                
                if (data && data[1]) {
                    var attachment = data[1];
                    coverInput.value = attachment.url;
                    if (attachment.cid) {
                        coverCidInput.value = attachment.cid;
                    }
                    previewImg.src = attachment.url;
                    preview.style.display = 'block';
                } else if (data && data.url) {
                    coverInput.value = data.url;
                    previewImg.src = data.url;
                    preview.style.display = 'block';
                } else {
                    alert('上传失败：返回数据格式错误');
                }
            }).catch(function(error) {
                progress.style.display = 'none';
                alert('上传失败：' + error.message);
            });
        });
        
        coverInput.addEventListener('input', function() {
            var url = this.value.trim();
            if (url) {
                previewImg.src = url;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });
    }

    window.removeCover = function() {
        var coverInput = document.getElementById('cover-input');
        var coverCidInput = document.getElementById('cover-cid');
        var preview = document.getElementById('cover-preview');
        var fileInput = document.getElementById('cover-file-input');
        var deleteUrl = <?php echo json_encode(\Widget\Security::alloc()->getTokenUrl(\Typecho\Common::url('/action/contents-attachment-edit', $this->options->index)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        
        var cid = coverCidInput ? coverCidInput.value : '';
        if (cid) {
            if (!confirm('确定删除此内容资源吗？此操作将不可逆')) {
                return;
            }
            
            var formData = new FormData();
            formData.append('do', 'delete');
            formData.append('cid', cid);
            
            fetch(deleteUrl, {
                method: 'POST',
                body: formData
            }).then(function(response) {
                clearCoverUI();
            }).catch(function(error) {
                clearCoverUI();
                console.log('删除附件失败:', error);
            });
        } else {
            clearCoverUI();
        }
        
        function clearCoverUI() {
            if (coverInput) coverInput.value = '';
            if (coverCidInput) coverCidInput.value = '';
            if (preview) preview.style.display = 'none';
            if (fileInput) fileInput.value = '';
        }
    };

    function bootEditor() {
        ensureDependencies(initMiraiUserWriteEditor);
        initCoverUpload();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootEditor);
    } else {
        bootEditor();
    }
})();
</script>
<?php endif; ?>
