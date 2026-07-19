<?php
/**
 * Mirai Theme - Navigation Functions Module
 * 导航菜单函数模块
 * 
 * 包含：桌面导航、移动导航等
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function _Mirai_getCategoryTree() {
    $db = \Typecho\Db::get();
    $categories = $db->fetchAll($db->select()->from('table.metas')
        ->where('type = ?', 'category')
        ->order('order', \Typecho\Db::SORT_ASC));
    
    $categoryMap = [];
    foreach ($categories as $category) {
        $category['children'] = [];
        $categoryMap[$category['mid']] = $category;
    }
    
    $categoryTree = [];
    foreach ($categoryMap as $category) {
        if ($category['parent'] == 0) {
            $categoryTree[] = &$categoryMap[$category['mid']];
        } else if (isset($categoryMap[$category['parent']])) {
            $categoryMap[$category['parent']]['children'][] = &$categoryMap[$category['mid']];
        }
    }
    return $categoryTree;
}

function Mirai_getNavigationMenu($type = 'desktop') {
    $options = Mirai_opt();
    $navSource = (isset($options->navSource) ? $options->navSource : 'category');
    $maxNavItems = (int)((isset($options->maxNavItems) ? $options->maxNavItems : 8));
    $navCount = 0;
    
    // Cache logic removed
    
    $html = '';
    $isMobile = ($type === 'mobile');
    
    if ($navSource == 'category' || $navSource == 'both') {
        $categoryTree = _Mirai_getCategoryTree();
        
        foreach ($categoryTree as $category):
            if ($navCount >= $maxNavItems) break;
            $navCount++;
            
            $categoryUrl = \Typecho\Router::url('category', $category, $options->index);
            $hasChildren = !empty($category['children']);
            
            if ($isMobile) {
                if ($hasChildren) {
                    $html .= '<div class="gt-mobile-nav-item has-children">';
                    $html .= '<a href="' . $categoryUrl . '" class="gt-mobile-nav-link" aria-label="' . htmlspecialchars($category['name']) . '">';
                    $html .= $category['name'];
                    $html .= '</a>';
                    
                    $html .= '<div class="gt-mobile-submenu">';
                    foreach ($category['children'] as $child) {
                        $childUrl = \Typecho\Router::url('category', $child, $options->index);
                        $html .= '<a href="' . $childUrl . '" class="gt-mobile-submenu-item" aria-label="' . htmlspecialchars($child['name']) . '">';
                        $html .= $child['name'];
                        $html .= '</a>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                } else {
                    $html .= '<a href="' . $categoryUrl . '" class="gt-mobile-nav-link" aria-label="' . htmlspecialchars($category['name']) . '">';
                    $html .= $category['name'];
                    $html .= '</a>';
                }
            } else {
                if ($hasChildren) {
                    $html .= '<div class="nav-dropdown" aria-haspopup="true">' . "\n";
                    $html .= '<a class="nav-link gt-hover" href="' . $categoryUrl . '" aria-label="' . htmlspecialchars($category['name']) . '">';
                    $html .= $category['name'];
                    $html .= '<i class="ri-arrow-down-s-line" aria-hidden="true"></i>';
                    $html .= '</a>' . "\n";
                    $html .= '<div class="nav-dropdown-menu" role="menu">' . "\n";
                    foreach ($category['children'] as $child) {
                        $childUrl = \Typecho\Router::url('category', $child, $options->index);
                        $html .= '<a class="nav-dropdown-item" href="' . $childUrl . '" role="menuitem" aria-label="' . htmlspecialchars($child['name']) . '">' . $child['name'] . '</a>' . "\n";
                    }
                    $html .= '</div>' . "\n";
                    $html .= '</div>' . "\n";
                } else {
                    $html .= '<a class="nav-link gt-hover" href="' . $categoryUrl . '" aria-label="' . htmlspecialchars($category['name']) . '">' . $category['name'] . '</a>' . "\n";
                }
            }
        endforeach;
    }
    
    if ($navSource == 'page' || $navSource == 'both') {
        $pagesWidget = \Typecho\Widget::widget('Widget_Contents_Page_List');
        while($pagesWidget->next()): 
            if ($navCount >= $maxNavItems) break;
            $navCount++;
            
            if ($isMobile) {
                // --- 移动端页面链接 ---
                $html .= '<a href="' . $pagesWidget->permalink . '" class="gt-mobile-nav-link">';
                $html .= $pagesWidget->title;
                $html .= '</a>';
            } else {

                $html .= '<a class="nav-link gt-hover" href="' . $pagesWidget->permalink . '" title="' . $pagesWidget->title . '">' . $pagesWidget->title . '</a>' . "\n";
            }
        endwhile;
    }
    
    return $html;
}

function Mirai_getMobileBottomTabItems() {
    $options = Mirai_opt();
    $items = [];

    if (empty($options->mobileBottomTabItems)) {

        $defaultItems = "首页||/\n投稿||#write\n搜索||#search\n友链||/links\n我的||#login";
        $lines = explode("\n", $defaultItems);
    } else {
        $lines = explode("\n", $options->mobileBottomTabItems);
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $parts = explode('||', $line);
        if (count($parts) >= 2) {
            $item = [
                'name' => trim($parts[0]),
                'url' => trim($parts[1]),
                'customSvg' => null
            ];
            // 如果提供了第三个字段，则使用自定义SVG
            if (count($parts) >= 3 && !empty(trim($parts[2]))) {
                $item['customSvg'] = trim($parts[2]);
            }
            $items[] = $item;
        }
    }

    return $items;
}

function Mirai_renderMobileBottomTab() {
    $options = Mirai_opt();

    if (empty($options->mobileBottomTabEnable) || $options->mobileBottomTabEnable !== '1' || !Mirai_isMobile()) {
        return '';
    }

    $items = Mirai_getMobileBottomTabItems();
    if (empty($items)) {
        return '';
    }

    $userCenterEnabled = Mirai_featureEnabled('user_center');
    $frontendLoginEnabled = Mirai_isUserCenterAuthEnabled($options);

    $currentPath = Mirai_getCurrentPath();
    $html = '<nav class="gt-mobile-bottom-tab" id="mobileBottomTab" aria-label="底部导航">';
    $html .= '<div class="gt-mobile-bottom-tab-inner">';

    $user = Mirai_user();
    $isUserLoggedIn = $user->hasLogin();

    foreach ($items as $item) {
        $url = $item['url'];
        $isActive = false;
        $isSearch = ($url === '#search');
        $isLogin = ($url === '#login');
        $isWrite = ($url === '#write');

        if (!$isSearch && !$isLogin && !$isWrite) {
            $isActive = Mirai_isCurrentPage($url, $currentPath);
        }

        if ($isLogin && !$frontendLoginEnabled) {
            continue;
        }

        if ($isWrite && !$isUserLoggedIn && !$frontendLoginEnabled) {
            continue;
        }

        $activeClass = $isActive ? ' active' : '';
        $onclick = '';
        $finalUrl = $url;

        if ($isSearch) {
            $onclick = ' onclick="openSearch(); return false;"';
        } elseif ($isLogin) {
            if ($isUserLoggedIn && $userCenterEnabled) {
                $userCenterUrl = Mirai_getPageUrlBySlug('user');
                $finalUrl = $userCenterUrl ?: Typecho_Common::url('user', $options->index);
            } else {
                $onclick = ' onclick="if(window.openLoginModal){openLoginModal();}return false;"';
            }
        } elseif ($isWrite) {
            $writeUrl = Typecho_Common::url('user/write', $options->index);
            if ($isUserLoggedIn) {
                $finalUrl = $writeUrl;
            } else {
                $onclick = ' onclick="if(window.openLoginModal){openLoginModal();}return false;"';
            }
        }

        $html .= '<a href="' . htmlspecialchars($finalUrl) . '" class="gt-mobile-bottom-tab-item' . $activeClass . '"' . $onclick . ' aria-label="' . htmlspecialchars($item['name']) . '">';

        // 处理图标：支持RemixIcon类名或SVG代码
        $iconHtml = '';
        if (!empty($item['customSvg'])) {
            $customSvg = trim($item['customSvg']);
            // 判断是否为SVG代码（包含<svg标签）
            if (strpos($customSvg, '<svg') !== false) {
                // 使用自定义SVG
                $iconHtml = $customSvg;
            } else {
                // 使用自定义RemixIcon类名
                $iconClass = htmlspecialchars($customSvg);
                $iconHtml = '<i class="' . $iconClass . '"></i>';
            }
        } else {
            // 使用默认RemixIcon
            $iconName = $item['name'];
            $defaultIcons = [
                '首页' => 'ri-home-5-line',
                '投稿' => 'ri-send-plane-line',
                '搜索' => 'ri-search-line',
                '友链' => 'ri-link',
                '我的' => 'ri-user-3-line'
            ];
            $iconClass = $defaultIcons[$iconName] ?? 'ri-more-fill';
            $iconHtml = '<i class="' . $iconClass . '"></i>';
        }
        $html .= $iconHtml;
        $html .= '<span>' . htmlspecialchars($item['name']) . '</span>';
        $html .= '</a>';
    }

    $html .= '</div>';
    $html .= '</nav>';

    return $html;
}

function Mirai_getCurrentPath() {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    // 移除查询参数
    $path = explode('?', $uri)[0];
    return $path;
}

function Mirai_isCurrentPage($url, $currentPath) {
    // 提取路径部分
    if (strpos($url, '://') !== false) {
        // 完整URL
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
    } else {
        // 相对路径
        $path = $url;
    }

    // 标准化路径（确保以/开头）
    if (empty($path) || $path === '') {
        $path = '/';
    }

    // 比较路径（考虑尾部斜杠的差异）
    $pathVariants = [
        $path,
        rtrim($path, '/') ?: '/',
        $path . '/',
    ];

    $currentVariants = [
        $currentPath,
        rtrim($currentPath, '/') ?: '/',
        $currentPath . '/',
    ];

    foreach ($pathVariants as $pv) {
        if (in_array($pv, $currentVariants, true)) {
            return true;
        }
    }

    return false;
}