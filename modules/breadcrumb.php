<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<nav class="gt-breadcrumb" aria-label="Breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?php $this->options->siteUrl(); ?>" itemprop="item" title="首页">
            <span itemprop="name">首页</span>
        </a>
        <meta itemprop="position" content="1" />
    </span>
    <span aria-hidden="true">&gt;</span>

    <?php if ($this->is('index')): ?>
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span class="current" itemprop="name">文章列表</span>
            <meta itemprop="position" content="2" />
        </span>
        
    <?php elseif ($this->is('category')): ?>
        <?php 
        $categoryId = $this->mid ?? 0;
        if (!$categoryId) {
            $categoryId = isset($this->categories[0]['mid']) ? $this->categories[0]['mid'] : 0;
        }
        if ($categoryId > 0) {
            $list = Mirai_getBreadcrumbListRecursive($categoryId);
            $count = count($list);
            $position = 2;
            foreach ($list as $index => $item) {
                echo '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
                if ($index === $count - 1) {
                    echo '<span class="current" itemprop="name">' . htmlspecialchars($item['name']) . '</span>';
                    echo '<meta itemprop="position" content="' . $position . '" />';
                } else {
                    echo '<a href="' . $item['item'] . '" itemprop="item" title="' . htmlspecialchars($item['name']) . '">';
                    echo '<span itemprop="name">' . htmlspecialchars($item['name']) . '</span></a>';
                    echo '<meta itemprop="position" content="' . $position . '" />';
                    echo '<span aria-hidden="true">&gt;</span>';
                }
                echo '</span>';
                $position++;
            }
        } else {
            echo '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
            echo '<span class="current" itemprop="name">'; 
            $this->archiveTitle('', '', ''); 
            echo '</span>';
            echo '<meta itemprop="position" content="2" />';
            echo '</span>';
        }
        ?>
        
    <?php elseif ($this->is('tag')): ?>
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span class="current" itemprop="name">标签：<?php $this->archiveTitle('', '', ''); ?></span>
            <meta itemprop="position" content="2" />
        </span>
        
    <?php elseif ($this->is('search')): ?>
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span class="current" itemprop="name">搜索：<?php $this->archiveTitle('', '', ''); ?></span>
            <meta itemprop="position" content="2" />
        </span>
        
    <?php elseif ($this->is('post') || $this->is('page')): ?>
        <?php 
        $position = 2;
        if ($this->is('post') && !empty($this->categories)): 
            $list = Mirai_getBreadcrumbListRecursive($this->categories[0]['mid']);
            foreach ($list as $item) {
                echo '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
                echo '<a href="' . $item['item'] . '" itemprop="item" title="' . htmlspecialchars($item['name']) . '">';
                echo '<span itemprop="name">' . htmlspecialchars($item['name']) . '</span></a>';
                echo '<meta itemprop="position" content="' . $position . '" />';
                echo '</span>';
                echo '<span aria-hidden="true">&gt;</span>';
                $position++;
            }
        endif; 
        ?>
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span class="current" itemprop="name"><?php $this->title(); ?></span>
            <meta itemprop="position" content="<?php echo $position; ?>" />
        </span>
        
    <?php else: ?>
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span class="current" itemprop="name"><?php $this->archiveTitle('', '', ''); ?></span>
            <meta itemprop="position" content="2" />
        </span>
    <?php endif; ?>
</nav>
