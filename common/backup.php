<?php

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Mirai_Backup {
    const THEME_NAME = 'Mirai';
    private static $backupDir = __DIR__ . '/../backups';

    private static $excludedKeys = ['licenseCode'];

    public static function init() {
        if (!is_dir(self::$backupDir)) {
            @mkdir(self::$backupDir, 0755, true);
        }
    }

    public static function handleBackupAction() {
        $action = $_POST['mirai_backup_action'] ?? '';
        $file = $_POST['backup_file'] ?? '';
        $result = ['success' => false, 'message' => '未知操作'];

        try {
            self::init();
            switch ($action) {
                case 'backup':
                    $result = self::doBackup();
                    break;
                case 'restore':
                    $result = self::doRestore($file);
                    break;
                case 'delete':
                    $result = self::doDelete($file);
                    break;
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit;
    }

    private static function doBackup() {
        $db = Typecho_Db::get();
        $options = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'theme:' . self::THEME_NAME));
        
        if (!$options) {
            return ['success' => false, 'message' => '未找到主题设置数据，请先保存一次主题设置'];
        }

        $value = $options['value'];
        $decodedOptions = json_decode($value, true);
        if (!is_array($decodedOptions)) {
            $decodedOptions = @unserialize($value);
        }
        
        if (is_array($decodedOptions)) {
            $filteredOptions = array_diff_key($decodedOptions, array_flip(self::$excludedKeys));
            $options['value'] = serialize($filteredOptions);
        }

        $fileName = self::THEME_NAME . '_Backup_' . (new \Typecho\Date())->format('Ymd_His') . '.json';
        $filePath = self::$backupDir . '/' . $fileName;
        
        if (file_put_contents($filePath, json_encode($options, JSON_UNESCAPED_UNICODE))) {
            return ['success' => true, 'message' => '备份成功：' . $fileName];
        }
        return ['success' => false, 'message' => '写入备份文件失败，请检查目录权限'];
    }

    private static function doRestore($file) {
        if (empty($file)) {
            return ['success' => false, 'message' => '未指定备份文件'];
        }

        $path = realpath(self::$backupDir . '/' . $file);
        if (!$path || strpos($path, realpath(self::$backupDir)) !== 0 || !file_exists($path)) {
            return ['success' => false, 'message' => '无效的备份文件'];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return ['success' => false, 'message' => '无法读取备份文件'];
        }

        $data = json_decode($content, true);
        if (!$data || !isset($data['value'])) {
            return ['success' => false, 'message' => '备份文件格式错误'];
        }

        $optionsValue = $data['value'];
        $backupOptions = json_decode($optionsValue, true);
        if (!is_array($backupOptions)) {
            $backupOptions = @unserialize($optionsValue);
        }
        
        if (!is_array($backupOptions)) {
            return ['success' => false, 'message' => '备份配置解析失败'];
        }
        
        $db = Typecho_Db::get();
        $themeKey = 'theme:' . self::THEME_NAME;
        
        $current = $db->fetchRow($db->select()->from('table.options')->where('name = ?', $themeKey));
        $currentOptions = [];
        if ($current) {
            $currentValue = $current['value'];
            $currentOptions = json_decode($currentValue, true);
            if (!is_array($currentOptions)) {
                $currentOptions = @unserialize($currentValue);
            }
        }
        if (!is_array($currentOptions)) {
            $currentOptions = [];
        }
        
        $mergedOptions = array_merge($currentOptions, $backupOptions);
        $serializedValue = serialize($mergedOptions);
        
        if ($current) {
            $affected = $db->query($db->update('table.options')
                ->rows(['value' => $serializedValue])
                ->where('name = ?', $themeKey));
        } else {
            $affected = $db->query($db->insert('table.options')
                ->rows([
                    'name' => $themeKey,
                    'user' => 0,
                    'value' => $serializedValue
                ]));
        }
        
        if ($affected === false) {
            return ['success' => false, 'message' => '数据库更新失败'];
        }
        
        return ['success' => true, 'message' => '主题设置已恢复。'];
    }

    private static function doDelete($file) {
        if (empty($file)) {
            return ['success' => false, 'message' => '未指定要删除的备份文件'];
        }

        $path = realpath(self::$backupDir . '/' . $file);
        if ($path && strpos($path, realpath(self::$backupDir)) === 0 && unlink($path)) {
            return ['success' => true, 'message' => '备份文件已删除'];
        }
        return ['success' => false, 'message' => '删除失败，文件可能不存在或权限不足'];
    }

    public static function render() {
        self::init();
        $files = glob(self::$backupDir . '/*.json');
        rsort($files);
        ?>
        <div class="mirai-backup-wrap" style="padding:20px; background:#fff; border:1px solid #ddd;">
            <h3>主题设置备份恢复</h3>
            <p style="color:#666; font-size:12px; margin-bottom:15px;">
                提示：备份仅包含主题设置，不包含许可信息。
            </p>
            <input type="hidden" name="mirai_backup_action" value="">
            <input type="hidden" name="backup_file" value="">
            <button type="button" class="btn primary" onclick="miraiDoAction('backup')">创建当前备份</button>
            <hr style="margin:15px 0; border:none; border-top:1px solid #eee;">
            <h4 style="margin-bottom:10px;">已有备份</h4>
            <ul style="list-style:none; padding:0; margin:0;">
                <?php if (empty($files)): ?>
                    <li style="color:#999; padding:10px 0;">暂无备份文件</li>
                <?php else: ?>
                    <?php foreach ($files as $file): 
                        $name = basename($file);
                        $time = (new \Typecho\Date(filemtime($file)))->format('Y-m-d H:i:s');
                        $size = filesize($file);
                        $sizeStr = $size < 1024 ? $size . ' B' : ($size < 1024 * 1024 ? round($size / 1024, 1) . ' KB' : round($size / (1024 * 1024), 1) . ' MB');
                    ?>
                        <li style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; padding:8px; background:#f9f9f9; border-radius:4px;">
                            <div>
                                <strong><?php echo htmlspecialchars($name); ?></strong>
                                <br><span style="color:#999; font-size:12px;"><?php echo $time; ?> (<?php echo $sizeStr; ?>)</span>
                            </div>
                            <div>
                                <button type="button" class="btn s" onclick="miraiDoAction('restore', '<?php echo htmlspecialchars($name); ?>')">恢复</button>
                                <button type="button" class="btn s error" onclick="miraiDoAction('delete', '<?php echo htmlspecialchars($name); ?>')">删除</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <script>
        function miraiDoAction(action, file) {
            if (!confirm('确定要执行此操作吗？')) return;
            
            var btn = event.target;
            var originalText = btn.innerText;
            btn.innerText = '处理中...';
            btn.disabled = true;
            
            var formData = new FormData();
            formData.append('mirai_backup_action', action);
            if (file) {
                formData.append('backup_file', file);
            }

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(res) {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.text();
            })
            .then(function(text) {
                btn.innerText = originalText;
                btn.disabled = false;
                
                var jsonMatch = text.match(/\{"success".*?\}/);
                if (jsonMatch) {
                    try {
                        var data = JSON.parse(jsonMatch[0]);
                        alert(data.message);
                        if (data.success) {
                            location.reload();
                        }
                    } catch (e) {
                        alert('操作失败：JSON解析错误');
                        console.error('JSON parse error:', e);
                    }
                } else {
                    alert('操作失败：未找到JSON响应');
                    console.error('Response:', text);
                }
            })
            .catch(function(error) {
                btn.innerText = originalText;
                btn.disabled = false;
                console.error('Error:', error);
                alert('操作失败：网络错误');
            });
        }
        </script>
        <?php
    }
}
