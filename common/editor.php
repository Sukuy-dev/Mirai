<?php
/**
 * Mirai 集成 Editor.md 编辑器
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Mirai_Editor {

    public static function getEditorType() {
        try {
            $options = \Typecho\Widget::widget('Widget\Options');
        } catch (Exception $e) {
            $options = \Widget\Options::alloc();
        }
        $type = isset($options->miraiEditorType) ? $options->miraiEditorType : 'default';
        return Mirai_featureEnabled('editor') ? $type : 'default';
    }

    public static function isEditorMdEnabled() {
        return Mirai_featureEnabled('editor') && self::getEditorType() === 'editormd';
    }

    public static function renderEditorMd($content) {
        try {
            $options = \Typecho\Widget::widget('Widget\Options');
        } catch (Exception $e) {
            $options = \Widget\Options::alloc();
        }
        $themeUrl = Mirai_getThemeUrl();

        $editorConfig = self::getEditorConfig();
        
        ?>
        <link rel="stylesheet" href="<?php echo $themeUrl; ?>/common/lib/editor.md/css/editormd.min.css" />
        <style>
            .editormd-fullscreen { z-index: 99999; }
            .editormd { margin-bottom: 15px; }
            #text { display: none; }
            .typecho-post-area > .col-mb-9 .typecho-label { display: none; }
        </style>
        
        <script src="<?php echo $themeUrl; ?>/common/lib/editor.md/editormd.min.js"></script>

        <script>
        (function() {
            var miraiEditor;
            var editorConfig = <?php echo json_encode($editorConfig); ?>;
            
            // 等待 DOM 完全加载
            $(document).ready(function() {
                // 找到原始的textarea
                var originalTextarea = $('#text');
                if (originalTextarea.length === 0) {
                    console.error('Original textarea #text not found');
                    return;
                }
                
                // 在原始textarea的位置创建编辑器容器
                var editorContainer = $('<div id="mirai-editormd"></div>');
                originalTextarea.after(editorContainer);
                
                // 将原始textarea移动到编辑器容器内（但保持隐藏）
                editorContainer.append(originalTextarea);
                
                // 初始化 Editor.md
                miraiEditor = editormd("mirai-editormd", {
                    width: "100%",
                    height: editorConfig.height || 745,
                    path: "<?php echo $themeUrl; ?>/common/lib/editor.md/lib/",
                    theme: editorConfig.theme || "default",
                    previewTheme: editorConfig.previewTheme || "default",
                    editorTheme: editorConfig.editorTheme || "default",
                    markdown: $("#text").val(),
                    watch: true,
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
                    imageFormats: ["jpg", "jpeg", "gif", "png", "avif"],
                    imageUploadURL: "<?php echo \Widget\Security::alloc()->getTokenUrl(\Typecho\Common::url('/action/upload', $options->index)); ?>",
                    syncScrolling: "single",
                    toolbarIcons: function() {
                        return [
                            "bold", "del", "italic", "quote", "|",
                            "h1", "h2", "h3", "|",
                            "list-ul", "list-ol", "hr", "|",
                            "link", "image", "code", "code-block", "table", "|",
                            "search", "watch", "preview", "fullscreen", "help"
                        ];
                    },
                    onload: function() {
                        // 隐藏原始 textarea 的 label
                        $('label[for="text"]').hide();
                    },
                    onchange: function() {
                        // 同步内容到原始 textarea
                        $("#text").val(this.getMarkdown());
                    }
                });
                
                // 表单提交前同步内容
                $('form[name="write_post"], form[name="write_page"]').on('submit', function() {
                    if (miraiEditor) {
                        $("#text").val(miraiEditor.getMarkdown());
                    }
                });
                
                // Typecho 文件插入接口
                if (typeof Typecho !== 'undefined') {
                    Typecho.insertFileToEditor = function(file, url, isImage) {
                        if (miraiEditor) {
                            var value = isImage ? 
                                '![' + file + '](' + url + ')' : 
                                '[' + file + '](' + url + ')';
                            miraiEditor.insertValue(value);
                        }
                    };
                    
                    // Typecho 上传完成回调
                    Typecho.uploadComplete = function(attachment) {
                        Typecho.insertFileToEditor(attachment.title, attachment.url, attachment.isImage);
                    };
                }
            });
        })();
        </script>
        <?php
    }

    private static function getEditorConfig() {
        try {
            $options = \Typecho\Widget::widget('Widget\Options');
        } catch (Exception $e) {
            $options = \Widget\Options::alloc();
        }
        
        return [
            'height' => isset($options->miraiEditorHeight) ? intval($options->miraiEditorHeight) : 745,
            'theme' => isset($options->miraiEditorTheme) ? $options->miraiEditorTheme : 'default',
            'previewTheme' => isset($options->miraiEditorPreviewTheme) ? $options->miraiEditorPreviewTheme : 'default',
            'editorTheme' => isset($options->miraiEditorEditorTheme) ? $options->miraiEditorEditorTheme : 'default',
        ];
    }
}

function Mirai_registerEditorHooks() {
    if (Mirai_featureEnabled('editor') && Mirai_Editor::isEditorMdEnabled()) {
        // 使用匿名函数方式注册钩子
        \Typecho\Plugin::factory('admin/write-post.php')->richEditor = function($post) {
            Mirai_Editor::renderEditorMd($post);
        };
        
        \Typecho\Plugin::factory('admin/write-page.php')->richEditor = function($page) {
            Mirai_Editor::renderEditorMd($page);
        };
    }
}