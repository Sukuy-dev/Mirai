<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$config = Mirai_getCommentConfig();
$canComment = $config['globalEnable'] && $this->allow('comment') && !Mirai_isCommentClosed($this->created) && ($this->user->hasLogin() || $config['guestAllowed']);
$respondId = $this->respondId;
?>
    <div class="gt-comment-header">
        <div class="gt-comment-title">
            <i class="ri-message-3-line"></i>
            <span>评论</span>
            <em class="gt-comment-count-text"><?php echo intval($this->commentsNum) > 0 ? '共' . $this->commentsNum . '条' : '暂无评论'; ?></em>
        </div>
    </div>

    <div class="gt-comment-form-wrap">
        <?php if($canComment): ?>
        <div id="<?php echo $respondId; ?>" class="respond">
            <form class="gt-comment-form" method="post" action="<?php $this->commentUrl() ?>" id="comment-form" role="form">
                <input type="hidden" name="_" value="<?php echo $this->security->getToken($this->request->getRequestUrl()); ?>">
                <div class="gt-comment-textarea-wrap">
                    <textarea name="text" id="comment-textarea" rows="4" placeholder="文明交流，友善发言" required><?php $this->remember('text'); ?></textarea>
                    <div class="gt-comment-textarea-footer">
                        <div class="gt-comment-submit-wrap">
                            <div class="cancel-comment-reply">
                                <?php $replyTo = $this->request->filter('int')->get('replyTo'); ?>
                                <a id="cancel-comment-reply-link" href="<?php echo $this->permalink; ?>#<?php echo $respondId; ?>" rel="nofollow"<?php echo $replyTo ? '' : ' style="display:none"'; ?> onclick="return TypechoComment.cancelReply();">取消回复</a>
                            </div>
                            <button type="submit" class="submit">
                                <span>发表评论</span>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if(!$this->user->hasLogin()): ?>
                <div class="gt-comment-fields">
                    <div class="gt-comment-field">
                        <i class="ri-user-line"></i>
                        <input type="text" name="author" placeholder="昵称 *" value="<?php $this->remember('author'); ?>" required>
                    </div>
                    <div class="gt-comment-field">
                        <i class="ri-mail-line"></i>
                        <input type="email" name="mail" placeholder="邮箱<?php echo $config['requireMail'] ? ' *' : ''; ?>" value="<?php $this->remember('mail'); ?>"<?php echo $config['requireMail'] ? ' required' : ''; ?>>
                    </div>
                    <?php if ($config['showUrl']): ?>
                    <div class="gt-comment-field">
                        <i class="ri-link"></i>
                        <input type="url" name="url" placeholder="网址<?php echo $config['requireUrl'] ? ' *' : ' (选填)'; ?>" value="<?php $this->remember('url'); ?>"<?php echo $config['requireUrl'] ? ' required' : ''; ?>>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </form>
        </div>
        <?php else: ?>
            <?php $statusTip = Mirai_getCommentStatusTip($this->created, $this->allow('comment'), $config['globalEnable'], $config['guestAllowed'], $this->user->hasLogin()); ?>
            <?php if ($statusTip): ?>
            <div class="gt-comment-closed"><i class="ri-message-3-line"></i><span><?php echo $statusTip; ?></span></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php

    $this->comments('commentPage', $config['pageSize'])->to($comments);
    
    if ($comments->have()): 
    ?>
        <div class="gt-comment-list-wrapper">
            <?php $comments->listComments(['before' => '<div class="gt-comment-list">', 'after' => '</div>']); ?>
            <?php if ($config['pageBreak']): ?>
                <?php
                $db = Typecho_Db::get();
                $select = $db->select(['COUNT(coid)' => 'num'])->from('table.comments')
                    ->where('cid = ?', $this->cid)
                    ->where('status = ?', 'approved')
                    ->where('parent = ?', 0);
                if ($this->options->commentsShowCommentOnly) $select->where('type = ?', 'comment');
                $totalComments = $db->fetchObject($select)->num;
                $totalPages = ceil($totalComments / $config['pageSize']);
                if ($totalPages > 1):
                    $currentPage = 1;
                    if (preg_match('/comment-page-(\d+)/i', $this->request->getPathInfo(), $matches)) $currentPage = intval($matches[1]);
                    echo Mirai_customPagination($currentPage, $totalPages, function($page) {
                        return $this->permalink . ($page > 1 ? '/comment-page-' . $page : '') . '#comments';
                    }, ['outerClass' => 'gt-art-page', 'innerClass' => 'pagination']);
                endif;
                ?>
            <?php endif; ?>
        </div>

    <?php elseif ($canComment): ?>
        <div class="gt-comment-list-wrapper">
            <div class="gt-comment-list">
                <div class="gt-comment-empty">
                    <i class="ri-message-3-line"></i>
                    <span>暂无评论，快来抢沙发吧~</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
