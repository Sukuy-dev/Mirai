<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$groupNames = [
    'subscriber' => '订阅者',
    'contributor' => '贡献者',
    'editor' => '编辑',
    'administrator' => '管理员'
];
$userGroup = isset($groupNames[$this->user->group]) ? $groupNames[$this->user->group] : $this->user->group;
?>
<div class="user-sidebar">
    <div class="user-info-card">
        <div class="user-info-header">
            <div class="user-avatar">
                <?php
                $avatarUrl = Mirai_getUserAvatar($this->user->uid);
                echo '<img src="' . $avatarUrl . '" alt="' . $this->user->screenName . '">';
                ?>
            </div>
            <div class="user-info-main">
                <div class="user-name"><?php $this->user->screenName(); ?></div>
                <div class="user-meta">
                    <span class="user-badge user-group">
                        <i class="ri-vip-crown-2-fill"></i> <?php echo $userGroup; ?>
                    </span>
                    <span class="user-badge user-uid">
                        <i class="ri-id-card-line"></i> UID: <?php echo $this->user->uid; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="user-menu">
        <?php
        $menuItems = [
            'overview' => ['text' => '仪表中心', 'perm' => 'subscriber'],
            'vip' => ['text' => '我的会员', 'perm' => 'subscriber'],
            'wallet' => ['text' => '余额管理', 'perm' => 'subscriber'],
            'content' => ['text' => '创作中心', 'perm' => 'contributor', 'submenu' => [
                'write' => ['text' => '文章投稿'],
                'posts' => ['text' => '我的文章'],
                'income' => ['text' => '收益中心'],
            ]],
            'orders' => ['text' => '订单列表', 'perm' => 'subscriber'],
            'stats' => ['text' => '数据统计', 'perm' => 'subscriber', 'submenu' => [
                    'favorites' => ['text' => '我的收藏'],
                    'comments' => ['text' => '我的评论'],
                ]],
            'profile' => ['text' => '个人资料', 'perm' => 'subscriber'],
            'password' => ['text' => '修改密码', 'perm' => 'subscriber'],
        ];
        $userBaseUrl = \Typecho\Common::url('/user', $this->options->index);
        $currentTab = isset($tab) ? $tab : 'overview';
        ?>
        
        <?php foreach ($menuItems as $key => $item): ?>
            <?php if ($this->user->pass($item['perm'], true)): ?>
                <?php if ($key === 'profile'): ?>
                    <div class="menu-item has-submenu <?php echo ($currentTab == 'profile' || $currentTab == 'password') ? 'active' : ''; ?>" onclick="toggleSubmenu(this)">
                        <i class="ri-user-line"></i>
                        <span><?php echo $item['text']; ?></span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="submenu <?php echo ($currentTab == 'profile' || $currentTab == 'password') ? 'show' : ''; ?>">
                        <a href="<?php echo $userBaseUrl . '/profile'; ?>" class="submenu-item <?php echo $currentTab == 'profile' ? 'active' : ''; ?>">
                            <i class="ri-user-settings-line"></i>
                            <span>基本资料</span>
                        </a>
                        <a href="<?php echo $userBaseUrl . '/password'; ?>" class="submenu-item <?php echo $currentTab == 'password' ? 'active' : ''; ?>">
                            <i class="ri-lock-password-line"></i>
                            <span>修改密码</span>
                        </a>
                    </div>
                <?php elseif ($key === 'content'): ?>
                    <div class="menu-item has-submenu <?php echo ($currentTab == 'write' || $currentTab == 'posts' || $currentTab == 'income') ? 'active' : ''; ?>" onclick="toggleSubmenu(this)">
                        <i class="ri-article-line"></i>
                        <span><?php echo $item['text']; ?></span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="submenu <?php echo ($currentTab == 'write' || $currentTab == 'posts' || $currentTab == 'income') ? 'show' : ''; ?>">
                        <a href="<?php echo $userBaseUrl . '/write'; ?>" class="submenu-item <?php echo $currentTab == 'write' ? 'active' : ''; ?>">
                            <i class="ri-edit-line"></i>
                            <span>文章投稿</span>
                        </a>
                        <a href="<?php echo $userBaseUrl . '/posts'; ?>" class="submenu-item <?php echo $currentTab == 'posts' ? 'active' : ''; ?>">
                            <i class="ri-file-list-3-line"></i>
                            <span>我的文章</span>
                        </a>
                        <a href="<?php echo $userBaseUrl . '/income'; ?>" class="submenu-item <?php echo $currentTab == 'income' ? 'active' : ''; ?>">
                            <i class="ri-money-cny-circle-line"></i>
                            <span>收益中心</span>
                        </a>
                    </div>
                <?php elseif ($key === 'stats'): ?>
                    <div class="menu-item has-submenu <?php echo ($currentTab == 'favorites' || $currentTab == 'likes' || $currentTab == 'comments') ? 'active' : ''; ?>" onclick="toggleSubmenu(this)">
                        <i class="ri-bar-chart-box-line"></i>
                        <span><?php echo $item['text']; ?></span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="submenu <?php echo ($currentTab == 'favorites' || $currentTab == 'likes' || $currentTab == 'comments') ? 'show' : ''; ?>">
                        <a href="<?php echo $userBaseUrl . '/favorites'; ?>" class="submenu-item <?php echo $currentTab == 'favorites' ? 'active' : ''; ?>">
                            <i class="ri-star-line"></i>
                            <span>我的收藏</span>
                        </a>
                        <a href="<?php echo $userBaseUrl . '/likes'; ?>" class="submenu-item <?php echo $currentTab == 'likes' ? 'active' : ''; ?>">
                            <i class="ri-thumb-up-line"></i>
                            <span>我的点赞</span>
                        </a>
                        <a href="<?php echo $userBaseUrl . '/comments'; ?>" class="submenu-item <?php echo $currentTab == 'comments' ? 'active' : ''; ?>">
                            <i class="ri-message-3-line"></i>
                            <span>我的评论</span>
                        </a>
                    </div>
                <?php elseif ($key === 'overview'): ?>
                    <a href="<?php echo $userBaseUrl . '/' . $key; ?>" class="menu-item <?php echo $currentTab == $key ? 'active' : ''; ?>">
                        <i class="ri-dashboard-3-line"></i>
                        <span><?php echo $item['text']; ?></span>
                    </a>
                <?php elseif ($key === 'orders'): ?>
                    <a href="<?php echo $userBaseUrl . '/' . $key; ?>" class="menu-item <?php echo $currentTab == $key ? 'active' : ''; ?>">
                        <i class="ri-file-list-2-line"></i>
                        <span><?php echo $item['text']; ?></span>
                    </a>
                <?php elseif ($key === 'vip'): ?>
                    <a href="<?php echo $userBaseUrl . '/' . $key; ?>" class="menu-item <?php echo $currentTab == $key ? 'active' : ''; ?>">
                        <i class="ri-vip-diamond-line"></i>
                        <span><?php echo $item['text']; ?></span>
                    </a>
                <?php elseif ($key === 'wallet'): ?>
                    <a href="<?php echo $userBaseUrl . '/' . $key; ?>" class="menu-item <?php echo $currentTab == $key ? 'active' : ''; ?>">
                        <i class="ri-wallet-3-line"></i>
                        <span><?php echo $item['text']; ?></span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <a href="<?php $this->options->logoutUrl(); ?>" class="menu-item logout">
            <i class="ri-logout-box-r-line"></i>
            <span>退出登录</span>
        </a>
    </div>
</div>
