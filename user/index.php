<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$userCenterEnabled = Mirai_featureEnabled('user_center');
if (!$userCenterEnabled) {
    $this->response->redirect($this->options->siteUrl);
    exit;
}

$pathInfo = $this->request->getPathInfo();
$tab = '';
if (preg_match('#^/user/([^/]+)#', $pathInfo, $matches)) {
    $tab = $matches[1];
}

if ($tab === 'login' || $tab === 'register') {
    $homeUrl = $this->options->siteUrl;
    $hash = $tab === 'login' ? '#login' : '#register';
    $this->response->redirect($homeUrl . $hash);
    exit;
}

if (!$this->user->hasLogin()) {
    if (!Mirai_isUserCenterAuthEnabled($this->options)) {
        $this->response->redirect($this->options->siteUrl);
    }

    $homeUrl = $this->options->siteUrl;
    echo '<script>
        window.location.href = "' . $homeUrl . '";
        window.addEventListener("load", function() {
            if (window.openLoginModal) window.openLoginModal();
        });
    </script>';
    exit;
}

$db = \Typecho\Db::get();
$user = $this->user;

$message = '';
$messageType = '';

if ($this->request->isPost()) {
    $action = $this->request->get('action');
    
    if ($action == 'write_post' && $user->pass('contributor', true)) {
        $title = $this->request->get('title');
        $text = $this->request->get('text');

        if (!preg_match('/^<!--markdown-->/i', (string)$text)) {
            $text = '<!--markdown-->' . $text;
        }
        
        $category = intval($this->request->get('category'));
        $tags = $this->request->get('tags');
        $cover = $this->request->get('cover');
        $excerpt = $this->request->get('excerpt');
        $keywords = $this->request->get('keywords');
        $description = $this->request->get('description');
        $cid = $this->request->get('cid');
        $do = $this->request->get('do');
        
        // 确定目标状态
        if ($do == 'draft') {
            $targetStatus = 'hidden';
        } elseif ($user->pass('editor', true)) {
            $targetStatus = 'publish';
        } else {
            $targetStatus = 'waiting';
        }
        
        if (empty($title) || empty($text)) {
            $message = '标题和内容不能为空';
            $messageType = 'error';
        } else {
            try {
                $oldCatMid = 0;

                if ($cid) {
                    $exist = $db->fetchRow($db->select()->from('table.contents')
                        ->where('cid = ?', $cid)
                        ->where('authorId = ?', $user->uid)
                        ->where('type = ?', 'post'));
                    if (!$exist) {
                        throw new Exception('文章不存在或无权编辑');
                    }
                    
                    $updateData = [
                        'title' => $title,
                        'text' => $text,
                        'modified' => time(),
                        'status' => $targetStatus
                    ];
                    $db->query($db->update('table.contents')->rows($updateData)->where('cid = ?', $cid));
                    
                    $currCatRel = $db->fetchRow($db->select('table.relationships.mid')->from('table.relationships')
                        ->join('table.metas', 'table.relationships.mid = table.metas.mid')
                        ->where('cid = ?', $cid)
                        ->where('table.metas.type = ?', 'category'));
                    if ($currCatRel) {
                        $oldCatMid = $currCatRel['mid'];
                    }
                    
                    $oldTags = $db->fetchAll($db->select('table.relationships.mid')->from('table.relationships')
                        ->join('table.metas', 'table.relationships.mid = table.metas.mid')
                        ->where('cid = ?', $cid)
                        ->where('table.metas.type = ?', 'tag'));
                        
                    foreach ($oldTags as $ot) {
                        $db->query($db->delete('table.relationships')->where('cid = ?', $cid)->where('mid = ?', $ot['mid']));
                        $db->query($db->update('table.metas')->expression('count', 'count - 1')->where('mid = ?', $ot['mid']));
                    }
                    
                    $message = '文章更新成功';
                    
                } else {
                    $data = [
                        'title'     => $title,
                        'slug'      => NULL,
                        'created'   => time(),
                        'modified'  => time(),
                        'text'      => $text,
                        'authorId'  => $user->uid,
                        'type'      => 'post',
                        'status'    => $targetStatus,
                        'allowComment' => 1,
                        'allowPing' => 1,
                        'allowFeed' => 1
                    ];
            
                    $cid = $db->query($db->insert('table.contents')->rows($data));
                    
                    $db->query($db->update('table.contents')->rows(['slug' => $cid])->where('cid = ?', $cid));
                    
                    $message = '投稿成功';
                }
                
                if (!empty($cover)) {
                    $db->query($db->update('table.contents')->rows(['cover' => $cover])->where('cid = ?', $cid));
                } else {
                    $db->query($db->update('table.contents')->rows(['cover' => ''])->where('cid = ?', $cid));
                }
                
                $prefix = $db->getPrefix();
                $edkTable = $prefix . 'mirai_contents_edk';
                if ($db->fetchRow("SHOW TABLES LIKE '{$edkTable}'")) {
                    $existEdk = $db->fetchRow($db->select()->from('table.mirai_contents_edk')->where('cid = ?', $cid));
                    $edkData = [
                        'excerpt' => $excerpt ?? '',
                        'keywords' => $keywords ?? '',
                        'description' => $description ?? ''
                    ];
                    
                    if (!empty(array_filter($edkData))) {
                        if ($existEdk) {
                            $db->query($db->update('table.mirai_contents_edk')->rows($edkData)->where('cid = ?', $cid));
                        } else {
                            $edkData['cid'] = $cid;
                            $db->query($db->insert('table.mirai_contents_edk')->rows($edkData));
                        }
                    } else if ($existEdk) {
                        $db->query($db->delete('table.mirai_contents_edk')->where('cid = ?', $cid));
                    }
                }
                
                if ($category != $oldCatMid) {
                    if ($oldCatMid > 0) {
                        $db->query($db->delete('table.relationships')->where('cid = ?', $cid)->where('mid = ?', $oldCatMid));
                        $db->query($db->update('table.metas')->expression('count', 'count - 1')->where('mid = ?', $oldCatMid));
                    }
                    if ($category > 0) {
                        $db->query($db->insert('table.relationships')->rows(['cid' => $cid, 'mid' => $category]));
                        $db->query($db->update('table.metas')->expression('count', 'count + 1')->where('mid = ?', $category));
                    }
                }
                
                if (!empty($tags)) {
                    $tagArr = explode(',', str_replace('，', ',', $tags));
                    foreach ($tagArr as $tagName) {
                        $tagName = trim($tagName);
                        if (empty($tagName)) continue;
                        
                        $tag = $db->fetchRow($db->select()->from('table.metas')->where('type = ?', 'tag')->where('name = ?', $tagName));
                        if (!$tag) {
                            $mid = $db->query($db->insert('table.metas')->rows([
                                'name' => $tagName,
                                'slug' => $tagName,
                                'type' => 'tag',
                                'count' => 1
                            ]));
                        } else {
                            $mid = $tag['mid'];
                            $db->query($db->update('table.metas')->expression('count', 'count + 1')->where('mid = ?', $mid));
                        }
                        $db->query($db->insert('table.relationships')->rows(['cid' => $cid, 'mid' => $mid]));
                    }
                }
                
                $messageType = 'success';
            } catch (Exception $e) {
                $message = '操作失败: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
    
    if ($action == 'update_profile') {
        $screenName = $this->request->get('screenName');
        $mail = $this->request->get('mail');
        $url = $this->request->get('url');
        $motto = $this->request->get('motto');
        $cover = $this->request->get('cover');
        
        $updateData = [];
        if (!empty($screenName)) $updateData['screenName'] = $screenName;
        if (!empty($mail)) $updateData['mail'] = $mail;
        $updateData['url'] = $url;
        $updateData['motto'] = $motto;
        $updateData['cover'] = $cover;
        if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = Mirai_uploadAvatar($_FILES['avatar'], $user->uid);
            if ($uploadResult['success']) {
                $oldAvatar = $db->fetchRow($db->select('avatar')->from('table.users')->where('uid = ?', $user->uid));
                if (!empty($oldAvatar['avatar'])) {
                    Mirai_deleteOldAvatar($oldAvatar['avatar']);
                }
                $updateData['avatar'] = $uploadResult['url'];
                $message = '头像更新成功';
                $messageType = 'success';
            } else {
                $message = $uploadResult['message'];
                $messageType = 'error';
            }
        }
        
        if (!empty($updateData)) {
            $db->query($db->update('table.users')->rows($updateData)->where('uid = ?', $user->uid));
            $message = empty($message) ? '资料更新成功' : $message . '，资料更新成功';
            $messageType = 'success';
        }
        if ($messageType === 'success') {
            $this->response->redirect($this->request->getRequestUrl());
            exit;
        }
    }
    
    if ($action == 'update_password') {
        $currentPassword = $this->request->get('current_password');
        $password = $this->request->get('password');
        $confirm = $this->request->get('confirm');
        $userRow = $db->fetchRow($db->select('password')->from('table.users')->where('uid = ?', $user->uid)->limit(1));
        
        if (empty($currentPassword)) {
            $message = '请输入当前密码';
            $messageType = 'error';
        } elseif (empty($userRow) || empty($userRow['password'])) {
            $message = '用户信息异常，请重新登录后重试';
            $messageType = 'error';
        } else {
            $hasher = new \Utils\PasswordHash(8, true);
            if (!$hasher->checkPassword($currentPassword, $userRow['password'])) {
                $message = '当前密码不正确';
                $messageType = 'error';
            } elseif (strlen($password) < 6) {
                $message = '密码长度至少6位';
                $messageType = 'error';
            } elseif ($password !== $confirm) {
                $message = '两次输入的密码不一致';
                $messageType = 'error';
            } elseif ($hasher->checkPassword($password, $userRow['password'])) {
                $message = '新密码不能与当前密码相同';
                $messageType = 'error';
            } else {
                $hash = $hasher->hashPassword($password);
                $db->query($db->update('table.users')->rows(['password' => $hash])->where('uid = ?', $user->uid));
                $this->response->redirect($this->request->getRequestUrl());
                exit;
            }
        }
    }
}

$this->hideThemeSidebar = true;

$this->need('header.php');

if (empty($tab)) {
    $tab = $this->request->get('tab', 'overview');
}
?>

<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/mirai.user.css'); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>">
<script src="<?php $this->options->themeUrl('assets/js/mirai.user.js'); ?>?v=<?php echo MIRAI_THEME_VERSION_TEXT; ?>" defer></script>

<script>
function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    if (submenu && submenu.classList.contains('submenu')) {
        submenu.classList.toggle('show');
        element.classList.toggle('active');
    }
}
</script>

<div class="mirai-user-center container">
    <div class="user-center-layout <?php echo $tab === 'write' ? 'is-write-mode' : ''; ?>">
        <?php if ($tab !== 'write'): ?>
        <?php require __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>
        
        <div class="user-content">
            <?php 
            $allowedTabs = ['overview', 'posts', 'write', 'orders', 'wallet', 'income', 'comments', 'likes', 'favorites', 'profile', 'password', 'vip'];
            
            if (in_array($tab, $allowedTabs)) {
                require __DIR__ . '/' . $tab . '.php';
            } else {
                require __DIR__ . '/overview.php';
            }
            ?>
        </div>
    </div>
</div>

<?php $this->need('footer.php'); ?>
