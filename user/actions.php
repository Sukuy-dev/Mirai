<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 通用用户操作模板（点赞/收藏）
 * 通过 $actionType 和 $actionData 变量传入参数
 */
if (!isset($actionType) || !isset($actionData)) return;

$db = \Typecho\Db::get();
$actionTable = $db->getPrefix() . 'mirai_actions';

$items = $db->fetchAll($db->select('table.contents.*', 'table.users.screenName')
    ->from($actionTable)
    ->join('table.contents', $actionTable . '.gid = table.contents.cid')
    ->join('table.users', 'table.contents.authorId = table.users.uid')
    ->where($actionTable . '.uid = ?', $this->user->uid)
    ->where($actionTable . '.type = ?', $actionType)
    ->order($actionTable . '.created', \Typecho\Db::SORT_DESC));
?>
<div class="post-list-card">
    <?php if (empty($items)): ?>
        <div class="empty-state"><?php echo $actionData['emptyMsg']; ?></div>
    <?php else: ?>
        <div class="card-grid">
        <?php foreach ($items as $post): ?>
            <div class="post-card">
                <div class="post-cover">
                    <a href="<?php echo \Typecho\Router::url('post', $post, $this->options->index); ?>">
                        <img src="<?php echo Mirai_getPostCover($post); ?>" alt="<?php echo $post['title']; ?>">
                    </a>
                </div>
                <div class="post-body">
                    <div class="post-title">
                        <a href="<?php echo \Typecho\Router::url('post', $post, $this->options->index); ?>"><?php echo $post['title']; ?></a>
                    </div>
                    <div class="post-meta">
                        <span><i class="ri-user-line"></i> <?php echo $post['screenName']; ?></span>
                        <span><i class="ri-time-line"></i> <?php echo (new \Typecho\Date($post['created']))->format('m-d'); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>