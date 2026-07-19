<?php
/**
 * Mirai Theme - Post Functions Module
 * 文章相关函数模块
 * 
 * 包含：文章封面、摘要、标签、相关文章、作者信息、版权声明、打赏等
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_getAuthorArchiveUrl($authorId) {
    $authorId = (int)$authorId;
    if ($authorId <= 0) {
        return '';
    }

    $options = Mirai_opt();
    try {
        return \Typecho\Router::url('author', ['uid' => $authorId], $options->index);
    } catch (\Throwable $e) {
    }

    return rtrim((string)$options->index, '/') . '/author/' . $authorId . '/';
}

function Mirai_getAuthorHtml($authorName, $authorMail = '', $authorId = null, $displayEmail = false) {
    $themeAuthorName = Mirai_getOption('authorName', '');

    $displayName = $themeAuthorName ?: $authorName;
    $authorUrl = Mirai_getAuthorArchiveUrl($authorId);
    $safeName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');

    $avatarUrl = $authorId ? Mirai_getUserAvatar($authorId) : Mirai_getDefaultAvatar();
    $avatarHtml = '<img class="gt-author-mini-avatar" src="' . htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $safeName . '" loading="lazy">';

    if ($authorUrl === '') {
        return $avatarHtml . $safeName;
    }

    $safeAuthorUrl = htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8');
    $html = '<a class="gt-author-link" href="' . $safeAuthorUrl . '">' . $avatarHtml . $safeName . '</a>';

    if ($displayEmail && $authorMail !== '') {
        $html .= '<a class="gt-author-mail-link" href="' . $safeAuthorUrl . '">' . htmlspecialchars($authorMail, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    return $html;
}

function Mirai_getPostCover($widget, $lightweight = false) {
    static $cache = [];
    
    // 统一转为对象
    $widget = is_array($widget) ? (object)$widget : $widget;
    $cid = $widget->cid ?? 0;
    $cacheKey = $cid . '_' . ($lightweight ? '1' : '0');
    
    // 使用 cid 缓存封面（仅当有 cid 时）
    if ($cid && isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    
    $defaultCover = Mirai_getDefaultThumb();
    
    // 1. 优先使用 cover 字段
    if (!empty($widget->cover)) {
        return $cid ? ($cache[$cacheKey] = $widget->cover) : $widget->cover;
    }
    
    // 2. 从内容提取图片
    $content = !empty($widget->text) ? $widget->text : (!empty($widget->content) ? $widget->content : '');
    if (!empty($content)) {
        $images = Mirai_pregImages($content, 1);
        if (!empty($images)) {
            return $cid ? ($cache[$cacheKey] = $images[0]) : $images[0];
        }
    }
    
    // 3. 返回默认封面
    return $cid ? ($cache[$cacheKey] = $defaultCover) : $defaultCover;
}

function Mirai_getPostExcerpt($widget, $length = 200) {
    if (is_array($widget)) {
        $widget = (object)$widget;
    }
    
    $edkExcerpt = Mirai_getEdkField($widget, 'excerpt');
    if (!empty($edkExcerpt)) {
        return Mirai_subContent(preg_replace('/\[pay\][\s\S]*?\[\/pay\]/i', '', (string)$edkExcerpt), $length);
    }
    
    if (isset($widget->content) && !empty($widget->content)) {
        return Mirai_subContent(preg_replace('/\[pay\][\s\S]*?\[\/pay\]/i', '', (string)$widget->content), $length);
    }
    
    if (isset($widget->text) && !empty($widget->text)) {
        return Mirai_subContent(Mirai_stripMarkdown(preg_replace('/\[pay\][\s\S]*?\[\/pay\]/i', '', (string)$widget->text)), $length);
    }
    
    return '';
}

function Mirai_getEdkField($widget, $fieldName) {
    // 支持 excerpt、keywords 和 description 字段
    if (!in_array($fieldName, ['excerpt', 'keywords', 'description'])) {
        return null;
    }
    
    if (!isset($widget->cid)) {
        return null;
    }
    
    $cid = is_object($widget) ? $widget->cid : ($widget['cid'] ?? 0);
    if (empty($cid)) {
        return null;
    }
    
    // 检查缓存中是否已有 EDK 数据
    static $edkCache = [];
    $cacheKey = 'edk_' . $cid;
    
    if (!isset($edkCache[$cacheKey])) {
        try {
            $db = \Typecho\Db::get();
            $prefix = $db->getPrefix();
            $edkTable = $prefix . 'mirai_contents_edk';
            
            // 查询 excerpt、keywords 和 description 字段
            $edk = $db->fetchRow($db->select('excerpt', 'keywords', 'description')->from($edkTable)->where('cid = ?', $cid));
            $edkCache[$cacheKey] = $edk ?: [];
        } catch (Exception $e) {
            $edkCache[$cacheKey] = [];
        }
    }
    
    $value = $edkCache[$cacheKey][$fieldName] ?? null;
    if ($value === null || $value === '') {
        return $value;
    }
    if ($fieldName === 'excerpt' || $fieldName === 'description') {
        return html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return $value;
}

function Mirai_getTags($widget) {
    if (!$widget->tags) return '';

    $html = '<div class="tag-list">';
    $i = 0;
    foreach ($widget->tags as $tag) {
        $tagClass = 'tag-' . (($i % 6) + 1);
        $html .= '<a class="' . $tagClass . '" href="' . $tag['permalink'] . '" rel="tag" title="查看 ' . $tag['name'] . ' 下的文章">' . $tag['name'] . '</a> ';
        $i++;
    }
    $html .= '</div>';

    return $html;
}

function Mirai_getRelatedPosts($widget, $limit = 6) {
    $options = Mirai_opt();
    $matchType = $options->relatedMatchType ?: 'tag';
    $sortType = $options->relatedSortType ?: 'date';
    $relatedNum = (int)($options->relatedNum ?: 6);
    $fillRandom = $options->relatedFillRandom ?: '1';
    
    $db = \Typecho\Db::get();
    $prefix = $db->getPrefix();
    
    $currentCategories = [];
    if ($widget->categories && is_array($widget->categories)) {
        $currentCategories = array_column($widget->categories, 'mid');
        $currentCategories = array_filter($currentCategories, 'is_numeric');
    }
    
    $currentTags = [];
    $tags = $widget->tags;
    if (is_array($tags) && !empty($tags)) {
        $currentTags = array_column($tags, 'mid');
        $currentTags = array_filter($currentTags, 'is_numeric');
    } else {
        $tagRelationships = $db->fetchAll(
            $db->select('mid')
               ->from('table.relationships')
               ->where('cid = ?', $widget->cid)
        );
        if (!empty($tagRelationships)) {
            $allTagIds = array_column($tagRelationships, 'mid');
            $tags = $db->fetchAll(
                $db->select('mid')
                   ->from('table.metas')
                   ->where('mid IN ?', $allTagIds)
                   ->where('type = ?', 'tag')
            );
            $currentTags = array_column($tags, 'mid');
            $currentTags = array_filter($currentTags, 'is_numeric');
        }
    }
    
    $relatedCids = [];
    
    switch ($matchType) {
        case 'tag':
            if (!empty($currentTags)) {
                // 使用参数化查询防止 SQL 注入
                $select = $db->select('DISTINCT cid')
                    ->from('table.relationships')
                    ->where('mid IN ?', $currentTags)
                    ->where('cid != ?', $widget->cid)
                    ->order('cid', \Typecho\Db::SORT_DESC);
                $relatedCids = array_column($db->fetchAll($select), 'cid');
            }
            break;
            
        case 'sort':
            if (!empty($currentCategories)) {
                // 使用参数化查询防止 SQL 注入
                $select = $db->select('DISTINCT cid')
                    ->from('table.relationships')
                    ->where('mid IN ?', $currentCategories)
                    ->where('cid != ?', $widget->cid)
                    ->order('cid', \Typecho\Db::SORT_DESC);
                $relatedCids = array_column($db->fetchAll($select), 'cid');
            }
            break;
            
        case 'tag_sort':
            $allRelatedCids = [];
            
            if (!empty($currentTags)) {
                // 使用参数化查询防止 SQL 注入
                $select = $db->select('DISTINCT cid')
                    ->from('table.relationships')
                    ->where('mid IN ?', $currentTags)
                    ->where('cid != ?', $widget->cid)
                    ->order('cid', \Typecho\Db::SORT_DESC);
                $tagRelatedCids = array_column($db->fetchAll($select), 'cid');
                $allRelatedCids = array_merge($allRelatedCids, $tagRelatedCids);
            }
            
            if (!empty($currentCategories)) {
                // 使用参数化查询防止 SQL 注入
                $select = $db->select('DISTINCT cid')
                    ->from('table.relationships')
                    ->where('mid IN ?', $currentCategories)
                    ->where('cid != ?', $widget->cid)
                    ->order('cid', \Typecho\Db::SORT_DESC);
                $catRelatedCids = array_column($db->fetchAll($select), 'cid');
                $allRelatedCids = array_merge($allRelatedCids, $catRelatedCids);
            }
            
            $relatedCids = array_unique($allRelatedCids);
            break;
    }
    
    $orderField = 'table.contents.created';
    $orderDirection = \Typecho\Db::SORT_DESC;
    $useRandomSort = false;
    
    switch ($sortType) {
        case 'date':
            $orderField = 'table.contents.created';
            $orderDirection = \Typecho\Db::SORT_DESC;
            break;
        case 'views':
            $orderField = 'table.contents.views';
            $orderDirection = \Typecho\Db::SORT_DESC;
            break;
        case 'comments':
            $orderField = 'table.contents.commentsNum';
            $orderDirection = \Typecho\Db::SORT_DESC;
            break;
        case 'random':
            $useRandomSort = true;
            $orderField = 'table.contents.created';
            $orderDirection = \Typecho\Db::SORT_DESC;
            break;
    }
    
    $relatedPosts = [];
    
    if (!empty($relatedCids)) {
        $fetchLimit = $useRandomSort ? min($relatedNum * 3, 50) : $relatedNum;
        
        $sql = $db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.cid != ?', $widget->cid)
            ->where('table.contents.cid IN ?', $relatedCids)
            ->limit($fetchLimit)
            ->order($orderField, $orderDirection);
        
        $relatedPosts = $db->fetchAll($sql);
        
        if ($useRandomSort && !empty($relatedPosts)) {
            shuffle($relatedPosts);
            $relatedPosts = array_slice($relatedPosts, 0, $relatedNum);
        }
    }
    
    $currentCount = count($relatedPosts);
    if ($fillRandom == '1' && $currentCount < $relatedNum) {
        $needCount = $relatedNum - $currentCount;
        
        $existingCids = [$widget->cid];
        if (!empty($relatedPosts)) {
            $existingCids = array_merge($existingCids, array_column($relatedPosts, 'cid'));
        }
        
        $totalPosts = $db->fetchObject($db->select('COUNT(*)')->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.cid NOT IN ?', $existingCids)
        )->{'COUNT(*)'};
        
        if ($totalPosts > 0) {
            $offset = ($widget->cid * 17) % max(1, $totalPosts - $needCount);
            
            $randomSql = $db->select()->from('table.contents')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', 'post')
                ->where('table.contents.cid NOT IN ?', $existingCids)
                ->limit($needCount)
                ->offset($offset)
                ->order('table.contents.created', \Typecho\Db::SORT_DESC);
            
            $randomPosts = $db->fetchAll($randomSql);
            $relatedPosts = array_merge($relatedPosts, $randomPosts);
        }
    }
    
    if (empty($relatedPosts)) {
        $sql = $db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.cid != ?', $widget->cid)
            ->limit($relatedNum)
            ->order($orderField, $orderDirection);
        
        $relatedPosts = $db->fetchAll($sql);
    }
    
    if (!empty($relatedPosts)) {
        $relatedCids = array_column($relatedPosts, 'cid');
        $categoryData = $db->fetchAll(
            $db->select('table.relationships.cid', 'table.metas.slug')
                ->from('table.relationships')
                ->join('table.metas', 'table.relationships.mid = table.metas.mid', \Typecho\Db::LEFT_JOIN)
                ->where('table.relationships.cid IN ?', $relatedCids)
                ->where('table.metas.type = ?', 'category')
        );
        $categoryMap = [];
        foreach ($categoryData as $cat) {
            if (!isset($categoryMap[$cat['cid']])) {
                $categoryMap[$cat['cid']] = $cat['slug'];
            }
        }
        foreach ($relatedPosts as &$post) {
            if (!isset($post['category'])) {
                $post['category'] = $categoryMap[$post['cid']] ?? null;
            }
        }
        unset($post);
    }
    
    return $relatedPosts;
}

function Mirai_renderRelatedPosts($widget) {
    $options = Mirai_opt();
    
    if (!isset($options->relatedEnable) || !$options->relatedEnable) {
        return '';
    }
    
    $posts = Mirai_getRelatedPosts($widget);
    
    if (empty($posts)) {
        return '';
    }
    
    $title = $options->relatedTitle ?: '推荐阅读';
    $lazyLoading = Mirai_getDefaultLazyLoading();
    $siteUrl = Mirai_getSiteUrl();
    
    echo '<section class="gt-related-posts">' . "\n";
    echo '<div class="gt-related-header">' . "\n";
    echo '<i class="ri-book-open-line"></i>' . "\n";
    echo '<span>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>' . "\n";
    echo '</div>' . "\n";
    echo '<div class="gt-related-grid">' . "\n";
    
    foreach ($posts as $post) {

        $url = \Typecho\Router::url('post', $post, $options->index);
        $postTitle = htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8');
        $cover = Mirai_getPostCover($post, true);
        
        $picture_html = Mirai_generatePictureTag($cover, $lazyLoading, $postTitle, 'lazyload', '200', '113', ['context' => 'related']);
        
        echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="gt-related-card" title="' . $postTitle . '">' . "\n";
        echo '<div class="gt-related-thumb">' . "\n";
        echo $picture_html . "\n";
        echo '</div>' . "\n";
        echo '<div class="gt-related-title">' . $postTitle . '</div>' . "\n";
        echo '</a>' . "\n";
    }
    echo '</div>' . "\n";
    echo '</section>' . "\n";
}

function Mirai_copyright($widget) {
    // 如果是数组，转换为对象
    if (is_array($widget)) {
        $widget = (object)$widget;
    }
    $options = Mirai_opt();
    
    if ($options->displayCopyright != '1') {
        return '';
    }
    
    $customContent = $options->copyrightCustomContent;
    
    if (empty($customContent)) {
        return '';
    }

    $customContent = htmlspecialchars((string)$customContent, ENT_QUOTES, 'UTF-8');
    
    $siteTitle = isset($options->siteTitle) && $options->siteTitle ? $options->siteTitle : $options->title;
    $siteUrl = Mirai_getSiteUrl();
    $authorName = isset($widget->author->screenName) ? $widget->author->screenName : 'Unknown';
    $postTitle = $widget->title;
    $postUrl = $widget->permalink;
    $content = str_replace([
        '{{site_name}}',
        '{{site_url}}',
        '{{post_author}}',
        '{{post_title}}',
        '{{post_url}}'
    ], [
        htmlspecialchars((string)$siteTitle, ENT_QUOTES, 'UTF-8'),
        '<a href="' . htmlspecialchars((string)$siteUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string)$siteUrl, ENT_QUOTES, 'UTF-8') . '</a>',
        htmlspecialchars((string)$authorName, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string)$postTitle, ENT_QUOTES, 'UTF-8'),
        '<a href="' . htmlspecialchars((string)$postUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string)$postUrl, ENT_QUOTES, 'UTF-8') . '</a>'
    ], $customContent);
    
    if (function_exists('Mirai_addNofollowToExternalLinks')) {
        $content = Mirai_addNofollowToExternalLinks($content);
    }

    $html = '<section class="article-copyright">';
    $html .= '<div class="copyright-header">';
    $html .= '<i class="ri-shield-check-line"></i>';
    $html .= '<span>版权声明</span>';
    $html .= '</div>';
    $html .= '<div class="copyright-content">' . nl2br($content) . '</div>';
    $html .= '</section>';
    
    return $html;
}

function Mirai_getHomePosts($page, $pageSize) {
    // 获取文章列表置顶配置
    $options = Mirai_opt();
    $listTopEnable = !empty($options->listTopEnable) && $options->listTopEnable === '1';
    
    // 获取文章列表置顶ID列表
    $stickyIds = [];
    if ($listTopEnable && !empty($options->listTopIds)) {
        $lines = explode("\n", $options->listTopIds);
        foreach ($lines as $line) {
            $cid = intval(trim($line));
            if ($cid > 0) {
                $stickyIds[] = $cid;
            }
        }
    }
    
    try {
        $db = \Typecho\Db::get();
        
        // 如果是第一页且启用了列表置顶，先获取置顶文章
        $topPosts = [];
        if ($page == 1 && $listTopEnable && !empty($stickyIds)) {
            $topSelect = $db->select()->from('table.contents')
                ->where('table.contents.cid IN ?', $stickyIds)
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', 'post')
                ->where('table.contents.created <= ?', time())
                ->where('table.contents.password IS NULL OR table.contents.password = ?', '');
            
            $topSelect->select('table.contents.*', 'table.users.screenName', 'table.users.mail as authorMail', 'table.contents.authorId')
                ->join('table.users', 'table.contents.authorId = table.users.uid', \Typecho\Db::LEFT_JOIN);
            
            $topPosts = $db->fetchAll($topSelect);
            
            // 按配置的顺序排序置顶文章
            if (!empty($topPosts)) {
                $topPostsMap = [];
                foreach ($topPosts as $post) {
                    $topPostsMap[$post['cid']] = $post;
                }
                $topPosts = [];
                foreach ($stickyIds as $cid) {
                    if (isset($topPostsMap[$cid])) {
                        $topPostsMap[$cid]['isSticky'] = true; // 标记为置顶
                        $topPosts[] = $topPostsMap[$cid];
                    }
                }
            }
        }
        
        $select = $db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.created <= ?', time())
            ->where('table.contents.password IS NULL OR table.contents.password = ?', '');
        
        // 排除已在推荐区域显示的文章
        $excludedIds = Mirai_getRecommendExcludedIds();
        if (!empty($excludedIds)) {
            $select->where('table.contents.cid NOT IN ?', $excludedIds);
        }
        
        // 如果在列表显示置顶文章，排除置顶文章（避免重复）
        if ($listTopEnable && !empty($stickyIds)) {
            $select->where('table.contents.cid NOT IN ?', $stickyIds);
        }
        
        // Join users table to get author name
        $select->select('table.contents.*', 'table.users.screenName', 'table.users.mail as authorMail', 'table.contents.authorId')
            ->join('table.users', 'table.contents.authorId = table.users.uid', \Typecho\Db::LEFT_JOIN);
        
        // Apply sorting (Always Latest)
        $select->order('table.contents.created', \Typecho\Db::SORT_DESC);
        
        // 计算偏移量和限制

        $topCount = count($topPosts);
        if ($page == 1 && $listTopEnable && $topCount > 0) {
            // 第一页需要为置顶文章腾出位置
            $select->limit(max(1, $pageSize - $topCount));
        } else {
            $offset = ($page - 1) * $pageSize;
            if ($listTopEnable && $topCount > 0) {
                $offset = max(0, $offset - $topCount);
            }
            $select->offset($offset)->limit($pageSize);
        }
        
        $posts = $db->fetchAll($select);
        
        if (!empty($posts)) {
            $cids = array_column($posts, 'cid');

            $edkTable = $db->getPrefix() . 'mirai_contents_edk';
            $edkData = $db->fetchAll($db->select('cid', 'excerpt', 'keywords', 'description')->from($edkTable)->where('cid IN ?', $cids));

            $excerptMap = [];
            $seoMap = [];
            foreach ($edkData as $edk) {
                $cid = $edk['cid'];
                if (!empty($edk['excerpt'])) {
                    $excerptMap[$cid] = $edk['excerpt'];
                }
                if (!empty($edk['keywords'])) {
                    $seoMap[$cid]['keywords'] = $edk['keywords'];
                }
                if (!empty($edk['description'])) {
                    $seoMap[$cid]['description'] = $edk['description'];
                }
            }
            $fieldMap = [];
            foreach ($posts as $post) {
                if (!empty($post['cover'])) {
                    $fieldMap[$post['cid']] = $post['cover'];
                }
            }

            $options = Mirai_opt();
            $metaRelationships = $db->fetchAll($db->select('table.relationships.cid', 'table.metas.name', 'table.metas.slug', 'table.metas.mid', 'table.metas.type')
                ->from('table.relationships')
                ->join('table.metas', 'table.relationships.mid = table.metas.mid', \Typecho\Db::LEFT_JOIN)
                ->where('table.relationships.cid IN ?', $cids)
                ->where('table.metas.type IN ?', ['category', 'tag']));
            
            $categoryMap = [];
            $tagMap = [];
            foreach ($metaRelationships as $rel) {
                $metaItem = [
                    'name' => $rel['name'],
                    'slug' => $rel['slug'],
                    'mid'  => $rel['mid'],
                    'permalink' => \Typecho\Router::url($rel['type'], $rel, $options->index)
                ];
                
                if ($rel['type'] === 'category') {
                    $categoryMap[$rel['cid']] ??= [];
                    $categoryMap[$rel['cid']][] = $metaItem;
                } else {
                    $tagMap[$rel['cid']] ??= [];
                    $tagMap[$rel['cid']][] = $metaItem;
                }
            }

            $attachments = $db->fetchAll($db->select('parent', 'text')->from('table.contents')
                ->where('parent IN ?', $cids)
                ->where('type = ?', 'attachment')
                ->order('order', \Typecho\Db::SORT_ASC));
            
            $attachmentMap = [];
            foreach ($attachments as $att) {
                if (!isset($attachmentMap[$att['parent']])) {
                    $data = @unserialize($att['text']);
                    if ($data && isset($data['mime']) && strpos($data['mime'], 'image/') === 0) {
                        $siteUrl = Mirai_getSiteUrl();
                        $attachmentMap[$att['parent']] = $siteUrl . '/' . ltrim($data['path'], '/');
                    }
                }
            }
            
            // 注入数据到文章数组
            foreach ($posts as &$post) {
                $cid = $post['cid'];
                // 注入封面
                if (!empty($post['cover'])) {
                    $post['thumb'] = $post['cover'];
                }
                // 注入附件图
                if (isset($attachmentMap[$cid])) {
                    $post['attachment_url'] = $attachmentMap[$cid];
                }
                // 注入分类和标签
                $post['categories'] = $categoryMap[$cid] ?? [];
                $post['tags'] = $tagMap[$cid] ?? [];
                
                // 确保 Router::url 能正确生成链接（如果永久链接包含 {category}）
                if (!empty($post['categories'])) {
                    $post['category'] = $post['categories'][0]['slug'];
                }

                // 确保阅读量为整数（views已直接在table.contents中）
                $post['views'] = isset($post['views']) ? intval($post['views']) : 0;
                // 注入文章摘要
                if (isset($excerptMap[$cid])) {
                    $post['excerpt'] = $excerptMap[$cid];
                }
                // 注入文章关键词和描述
                if (isset($seoMap[$cid])) {
                    if (isset($seoMap[$cid]['keywords'])) {
                        $post['keywords'] = $seoMap[$cid]['keywords'];
                    }
                    if (isset($seoMap[$cid]['description'])) {
                        $post['description'] = $seoMap[$cid]['description'];
                    }
                }
            }
            unset($post);
        }
        
        // 如果在列表显示置顶文章，将置顶文章合并到列表开头
        if ($page == 1 && $listTopEnable && !empty($topPosts)) {
            // 为置顶文章注入相同的数据
            $topCids = array_column($topPosts, 'cid');
            
            // 批量获取置顶文章的分类和标签
            $topMetaRelationships = $db->fetchAll($db->select('table.relationships.cid', 'table.metas.name', 'table.metas.slug', 'table.metas.mid', 'table.metas.type')
                ->from('table.relationships')
                ->join('table.metas', 'table.relationships.mid = table.metas.mid', \Typecho\Db::LEFT_JOIN)
                ->where('table.relationships.cid IN ?', $topCids)
                ->where('table.metas.type IN ?', ['category', 'tag']));
            
            $topCategoryMap = [];
            $topTagMap = [];
            foreach ($topMetaRelationships as $rel) {
                $metaItem = [
                    'name' => $rel['name'],
                    'slug' => $rel['slug'],
                    'mid'  => $rel['mid'],
                    'permalink' => \Typecho\Router::url($rel['type'], $rel, $options->index)
                ];
                
                if ($rel['type'] === 'category') {
                    $topCategoryMap[$rel['cid']] ??= [];
                    $topCategoryMap[$rel['cid']][] = $metaItem;
                } else {
                    $topTagMap[$rel['cid']] ??= [];
                    $topTagMap[$rel['cid']][] = $metaItem;
                }
            }
            
            // 为置顶文章注入数据
            foreach ($topPosts as &$topPost) {
                $cid = $topPost['cid'];
                // 注入封面
                if (isset($fieldMap[$cid])) {
                    $topPost['thumb'] = $fieldMap[$cid];
                }
                // 注入附件图
                if (isset($attachmentMap[$cid])) {
                    $topPost['attachment_url'] = $attachmentMap[$cid];
                }
                // 注入分类和标签
                $topPost['categories'] = $topCategoryMap[$cid] ?? [];
                $topPost['tags'] = $topTagMap[$cid] ?? [];
                
                if (!empty($topPost['categories'])) {
                    $topPost['category'] = $topPost['categories'][0]['slug'];
                }
                
                $topPost['views'] = isset($topPost['views']) ? intval($topPost['views']) : 0;
                
                if (isset($excerptMap[$cid])) {
                    $topPost['excerpt'] = $excerptMap[$cid];
                }
                if (isset($seoMap[$cid])) {
                    if (isset($seoMap[$cid]['keywords'])) {
                        $topPost['keywords'] = $seoMap[$cid]['keywords'];
                    }
                    if (isset($seoMap[$cid]['description'])) {
                        $topPost['description'] = $seoMap[$cid]['description'];
                    }
                }
            }
            unset($topPost);
            
            // 将置顶文章合并到列表开头
            $posts = array_merge($topPosts, $posts);
        }

        return $posts;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * 渲染文章卡片
 */
function Mirai_renderPostItem($post, $options = []) {
    $opt = Mirai_opt();
    $isWidget = $options['isWidget'] ?? false;
    $isFirst = $options['isFirst'] ?? false;
    
    // 获取文章列表置顶ID列表
    static $stickyIds = null;
    if ($stickyIds === null) {
        if (!empty($opt->listTopEnable) && !empty($opt->listTopIds)) {
             $stickyIds = explode(',', str_replace(["\n", "\r"], ',', $opt->listTopIds));
        } else {
             $stickyIds = [];
        }
    }
    
    $cid = $isWidget ? ($post->cid ?? 0) : ($post['cid'] ?? 0);
    $isSticky = !empty($stickyIds) && in_array($cid, $stickyIds);
    
    // 统一数据格式
    $title = $isWidget ? $post->title : $post['title'];
    $permalink = $isWidget ? $post->permalink : \Typecho\Router::url('post', $post, $opt->index);
    $created = $isWidget ? $post->created : $post['created'];
    $author = $isWidget ? $post->author->screenName : (isset($post['author']) ? $post['author'] : 'Unknown');
    $authorId = $isWidget ? ($post->author->uid ?? 0) : ($post['authorId'] ?? 0);
    
    // 封面图
    $cover = Mirai_getPostCover($post);
    $lazyLoading = Mirai_getDefaultLazyLoading();
    
    // 摘要
    $excerpt = Mirai_getPostExcerpt($post, 120);
    
    // 浏览量 - 直接使用已注入的数据，避免重复查询
    $views = $isWidget ? ($post->views ?? 0) : ($post['views'] ?? 0);
    
    // 样式类
    $classes = ['gt-article-item', 'gt-animation', 'gt-animation-init'];
    if (($isWidget && isset($post->sticky) && $post->sticky) || $isSticky) $classes[] = 'active';
    
    // 分类
    $categories = [];
    if ($isWidget) {
        if ($post->categories) $categories = $post->categories;
    } else {
        if (isset($post['categories'])) $categories = $post['categories'];
    }
    
    // 标签
    $tags = [];
    if ($isWidget) {
        $tags = $post->tags;
    } else {
        $tags = isset($post['tags']) ? $post['tags'] : [];
    }
    
    // 输出 HTML
    echo '<article class="' . implode(' ', $classes) . '" itemscope itemtype="https://schema.org/Article">' . "\n";
    echo '  <section class="gt-article-banner">' . "\n";
    
    echo '    ' . Mirai_generatePictureTag($cover, $lazyLoading, $title, 'lazyload', '800', '450', ['context' => 'article-cover', 'itemprop' => 'image', 'priority' => $isFirst]) . "\n";
    
    if (!empty($categories)) {
        foreach ($categories as $category) {
            echo '    <a class="gt-article-cat sky-h6 banner-cat" href="' . $category['permalink'] . '" title="查看 ' . $category['name'] . ' 分类下的文章">' . $category['name'] . '</a>' . "\n";
            break; 
        }
    }
    
    echo '  </section>' . "\n";
    
    $gridLayoutValue = $opt->gridLayout ? $opt->gridLayout : '3';
    $isSingleColumn = ($gridLayoutValue === '1');
    if ($isSingleColumn) {
        echo '  <div class="gt-article-content">' . "\n";
    }
    
    // 根据页面类型选择H2或H3
    $isIndex = $options['isIndex'] ?? false;
    $titleTag = $isIndex ? 'h3' : 'h2';
    
    echo '    <header><' . $titleTag . ' class="sky-h4 title" itemprop="headline">' . "\n";
    echo '      <a href="' . $permalink . '" itemprop="url">' . $title . '</a>' . "\n";
    echo '    </' . $titleTag . '></header>' . "\n";
    
    echo '    <div class="gt-article-excerpt" itemprop="description">' . $excerpt . '</div>' . "\n";

    if (!empty($tags)) {
        // 获取标签显示设置
        $tagDisplayMode = Mirai_getOption('tagDisplayMode', 'custom');
        $tagDisplayNum = intval(Mirai_getOption('tagDisplayNum', '3'));
        if ($tagDisplayNum < 1) $tagDisplayNum = 3;
        
        echo '    <div class="gt-article-tags">' . "\n";
        $tagCount = 0;
        foreach ($tags as $tag) {
            // 根据显示模式判断是否继续输出
            if ($tagDisplayMode === 'custom' && $tagCount >= $tagDisplayNum) break;
            echo '      <a class="gt-article-tag tag-' . (($tagCount % 5) + 1) . '" href="' . $tag['permalink'] . '" rel="tag" title="查看 ' . htmlspecialchars($tag['name']) . ' 相关文章">' . $tag['name'] . '</a>' . "\n";
            $tagCount++;
        }
        echo '    </div>' . "\n";
    }
    
    // 点赞数
    $likes = $isWidget ? ($post->likes ?? 0) : ($post['likes'] ?? 0);
    // 评论数
    $commentsNum = $isWidget ? ($post->commentsNum ?? 0) : ($post['commentsNum'] ?? 0);
    
    echo '    <div class="gt-article-taglist gt-ellipsis">' . "\n";
    $authorName = $isWidget ? $post->author->screenName : $author;
    $authorMail = $isWidget ? ($post->author->mail ?? '') : ($post['authorMail'] ?? '');
    $authorHtml = Mirai_getAuthorHtml($authorName, $authorMail, $authorId, false);
    echo '      <span class="gt-article-author sky-h6" itemprop="author">' . $authorHtml . '</span>' . "\n";
    
    echo '      <time class="sky-h6" itemprop="datePublished" datetime="' . Mirai_formatISODate($created) . '">' . Mirai_formatTime($created) . '</time>' . "\n";
    echo '      ' . Mirai_renderViews($views, ['class' => 'gt-article-views sky-h6']) . "\n";
    
    echo '      <span class="gt-article-likes sky-h6"><i class="ri-thumb-up-line"></i>' . Mirai_formatNumber($likes) . '</span>' . "\n";
    
    echo '      <span class="gt-article-comments sky-h6"><i class="ri-message-3-line"></i>' . Mirai_formatNumber($commentsNum) . '</span>' . "\n";
    
    echo '    </div>' . "\n";
    
    if ($isSingleColumn) {
        echo '  </div>' . "\n";
    }
    
    echo '</article>' . "\n";
}
