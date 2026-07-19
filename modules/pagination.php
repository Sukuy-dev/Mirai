<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function Mirai_customPagination($current, $total, $linkGenerator, $options = []) {
    if ($total <= 1) return '';

    $linkCallback = function($page) use ($linkGenerator) {
        return is_callable($linkGenerator) ? $linkGenerator($page) : $linkGenerator . ($page > 1 ? '?page=' . $page : '');
    };

    $opts = array_merge(['endSize' => 1, 'midSize' => 1], $options);

    $pages = [];
    $dots = false;
    for ($i = 1; $i <= $total; $i++) {
        $isCurrent = $i == $current;
        $show = $isCurrent || $i <= $opts['endSize'] || $i > $total - $opts['endSize'] || ($i >= $current - $opts['midSize'] && $i <= $current + $opts['midSize']);
        
        if ($show) {
            $pages[] = $isCurrent 
                ? '<span class="p-num cur" aria-current="page">' . $i . '</span>' 
                : '<a href="' . $linkCallback($i) . '" class="p-num" aria-label="第' . $i . '页">' . $i . '</a>';
            $dots = true;
        } elseif ($dots) {
            $pages[] = '<span class="p-num p-dots" aria-hidden="true">...</span>';
            $dots = false;
        }
    }

    $prev = $current > 1 
        ? '<a href="' . $linkCallback($current - 1) . '" class="p-num p-arr" rel="prev" aria-label="上一页"><i class="ri-arrow-left-s-line"></i></a>'
        : '<span class="p-num p-arr dis" aria-label="上一页" aria-disabled="true"><i class="ri-arrow-left-s-line"></i></span>';
    
    $next = $current < $total
        ? '<a href="' . $linkCallback($current + 1) . '" class="p-num p-arr" rel="next" aria-label="下一页"><i class="ri-arrow-right-s-line"></i></a>'
        : '<span class="p-num p-arr dis" aria-label="下一页" aria-disabled="true"><i class="ri-arrow-right-s-line"></i></span>';

    return '<nav class="p-wrap" aria-label="分页">' . $prev . implode('', $pages) . $next . '</nav>';
}
