<?php
// Ensure user has permission to view this homework's branch
if (!is_superadmin_loggedin() && $homework->branch_id != get_loggedin_branch_id()) {
    access_denied();
}
?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-comments"></i> <?=translate('homework_discussion')?> - <?=htmlspecialchars($homework->subject_name)?>
                </h4>
                <div class="panel-btn">
                    <a href="<?=base_url('homework/comments')?>" class="btn btn-default btn-circle">
                        <i class="fas fa-arrow-left"></i> <?=translate('back')?>
                    </a>
                </div>
            </header>
            <div class="panel-body">
                
                <!-- Homework Info Bar -->
                <div class="well well-sm">
                    <div class="row">
                        <div class="col-md-8">
                            <strong><?=translate('homework')?>:</strong> <?=nl2br(htmlspecialchars($homework->description))?>
                        </div>
                        <div class="col-md-4 text-right">
                            <strong><?=translate('due_date')?>:</strong> <?=_d($homework->date_of_submission)?><br>
                            <strong><?=translate('total_comments')?>:</strong> <span class="badge badge-info"><?=$comment_count?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Comment Form - Only show if user can comment -->
                <?php 
                $can_comment = false;
                if (is_teacher_loggedin() || is_superadmin_loggedin()) {
                    $can_comment = true;
                } elseif (is_student_loggedin()) {
                    // Check if student belongs to this homework's class/section
                    $student_id = get_loggedin_user_id();
                    $enroll = $this->db->select('class_id, section_id')
                                        ->where('student_id', $student_id)
                                        ->where('session_id', get_session_id())
                                        ->where('branch_id', $homework->branch_id)
                                        ->get('enroll')
                                        ->row();
                    if ($enroll && $enroll->class_id == $homework->class_id && $enroll->section_id == $homework->section_id) {
                        $can_comment = true;
                    }
                } elseif (is_parent_loggedin()) {
                    // Parents can view but not comment (optional - change as needed)
                    $can_comment = false;
                }
                ?>
                
                <?php if ($can_comment): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?=translate('add_comment')?></h4>
                    </div>
                    <div class="panel-body">
                        <form id="comment-form" class="form-horizontal">
                            <input type="hidden" name="homework_id" value="<?=$homework->id?>">
                            <input type="hidden" name="parent_id" id="parent_id" value="">
                            
                            <div class="form-group">
                                <div class="col-md-12">
                                    <textarea name="comment" id="comment" class="form-control" rows="4" placeholder="<?=translate('write_your_comment_here')?>"></textarea>
                                    <span class="error"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-md-12">
                                    <div class="fileupload fileupload-new" data-provides="fileupload">
                                        <div class="input-append">
                                            <div class="uneditable-input">
                                                <i class="fas fa-file fileupload-exists"></i>
                                                <span class="fileupload-preview"></span>
                                            </div>
                                            <span class="btn btn-default btn-file">
                                                <span class="fileupload-exists"><?=translate('change')?></span>
                                                <span class="fileupload-new"><?=translate('attach_file')?></span>
                                                <input type="file" name="attachment">
                                            </span>
                                            <a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload"><?=translate('remove')?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> <?=translate('post_comment')?>
                                    </button>
                                    <button type="button" class="btn btn-default" id="cancel-reply" style="display: none;">
                                        <i class="fas fa-times"></i> <?=translate('cancel_reply')?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <?=translate('comments_view_only_message')?>
                </div>
                <?php endif; ?>
                
                <!-- Comments List -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <i class="fas fa-comments"></i> <?=translate('comments')?> 
                            <span class="badge"><?=$comment_count?></span>
                        </h4>
                    </div>
                    <div class="panel-body" id="comments-list">
                        <?php if (empty($comments)): ?>
                            <div class="alert alert-info"><?=translate('no_comments_yet')?></div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <!-- Only show comments from same branch -->
                                <?php if ($comment['branch_id'] == $homework->branch_id || is_superadmin_loggedin()): ?>
                                <div class="comment-item" id="comment-<?=$comment['id']?>" data-branch="<?=$comment['branch_id']?>">
                                    <div class="comment-avatar">
                                        <?php if ($comment['author_avatar']): ?>
                                            <img src="<?=base_url('uploads/images/' . $comment['author_avatar'])?>" class="img-circle" width="50">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user-circle fa-3x"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <strong class="comment-author">
                                                <?=htmlspecialchars($comment['author_name'])?>
                                                <small class="comment-type label label-<?=($comment['created_by_type'] == 'teacher') ? 'primary' : (($comment['created_by_type'] == 'admin') ? 'danger' : 'default')?>-custom">
                                                    <?=ucfirst($comment['created_by_type'])?>
                                                </small>
                                            </strong>
                                            <span class="comment-date"><?=date('d/m/Y H:i', strtotime($comment['created_at']))?></span>
                                            <?php 
                                            $can_delete = false;
                                            if (is_superadmin_loggedin()) {
                                                $can_delete = true;
                                            } elseif (is_teacher_loggedin() && ($user_type == 'teacher' || $user_type == 'admin')) {
                                                $can_delete = true;
                                            } elseif ($user_id == $comment['created_by'] && $user_type == $comment['created_by_type']) {
                                                $can_delete = true;
                                            }
                                            ?>
                                            <?php if ($can_delete): ?>
                                                <button class="btn btn-xs btn-danger pull-right delete-comment" data-id="<?=$comment['id']?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($can_comment): ?>
                                                <button class="btn btn-xs btn-default pull-right reply-button" data-id="<?=$comment['id']?>" data-author="<?=htmlspecialchars($comment['author_name'])?>">
                                                    <i class="fas fa-reply"></i> <?=translate('reply')?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <div class="comment-body">
                                            <?=nl2br(htmlspecialchars($comment['comment']))?>
                                            <?php if ($comment['attachments']): ?>
                                                <div class="comment-attachment">
                                                    <a href="<?=base_url('homework/download_comment_attachment?file=' . urlencode($comment['attachments']))?>" class="btn btn-xs btn-default">
                                                        <i class="fas fa-paperclip"></i> <?=translate('download_attachment')?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Replies -->
                                        <?php if (!empty($comment['replies'])): ?>
                                            <div class="replies-list">
                                                <?php foreach ($comment['replies'] as $reply): ?>
                                                    <?php if ($reply['branch_id'] == $homework->branch_id || is_superadmin_loggedin()): ?>
                                                    <div class="reply-item" id="comment-<?=$reply['id']?>">
                                                        <div class="reply-avatar">
                                                            <?php if ($reply['author_avatar']): ?>
                                                                <img src="<?=base_url('uploads/images/' . $reply['author_avatar'])?>" class="img-circle" width="35">
                                                            <?php else: ?>
                                                                <i class="fas fa-user-circle fa-2x"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="reply-content">
                                                            <div class="reply-header">
                                                                <strong><?=htmlspecialchars($reply['author_name'])?></strong>
                                                                <small class="reply-type label label-<?=($reply['created_by_type'] == 'teacher') ? 'primary' : (($reply['created_by_type'] == 'admin') ? 'danger' : 'default')?>-custom">
                                                                    <?=ucfirst($reply['created_by_type'])?>
                                                                </small>
                                                                <span class="reply-date"><?=date('d/m/Y H:i', strtotime($reply['created_at']))?></span>
                                                                <?php 
                                                                $can_delete_reply = false;
                                                                if (is_superadmin_loggedin()) {
                                                                    $can_delete_reply = true;
                                                                } elseif (is_teacher_loggedin() && ($user_type == 'teacher' || $user_type == 'admin')) {
                                                                    $can_delete_reply = true;
                                                                } elseif ($user_id == $reply['created_by'] && $user_type == $reply['created_by_type']) {
                                                                    $can_delete_reply = true;
                                                                }
                                                                ?>
                                                                <?php if ($can_delete_reply): ?>
                                                                    <button class="btn btn-xs btn-danger pull-right delete-comment" data-id="<?=$reply['id']?>">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="reply-body">
                                                                <?=nl2br(htmlspecialchars($reply['comment']))?>
                                                                <?php if ($reply['attachments']): ?>
                                                                    <div class="reply-attachment">
                                                                        <a href="<?=base_url('homework/download_comment_attachment?file=' . urlencode($reply['attachments']))?>" class="btn btn-xs btn-default">
                                                                            <i class="fas fa-paperclip"></i> <?=translate('download_attachment')?>
                                                                        </a>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit comment form
    $('#comment-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> <?=translate('posting')?>...').prop('disabled', true);
        
        $.ajax({
            url: base_url + 'homework/add_comment',
            type: 'POST',
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.status == 'success') {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('<?=translate('an_error_occurred')?>');
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Reply to comment
    $('.reply-button').on('click', function() {
        var commentId = $(this).data('id');
        var authorName = $(this).data('author');
        
        $('#parent_id').val(commentId);
        $('#comment').focus();
        $('#comment').attr('placeholder', '<?=translate('reply_to')?> ' + authorName + '...');
        $('#cancel-reply').show();
        
        $('html, body').animate({
            scrollTop: $('#comment-form').offset().top - 100
        }, 500);
    });
    
    // Cancel reply
    $('#cancel-reply').on('click', function() {
        $('#parent_id').val('');
        $('#comment').attr('placeholder', '<?=translate('write_your_comment_here')?>');
        $(this).hide();
    });
    
    // Delete comment
    $('.delete-comment').on('click', function() {
        var commentId = $(this).data('id');
        
        if (confirm('<?=translate('are_you_sure_delete_comment')?>')) {
            $.ajax({
                url: base_url + 'homework/delete_comment',
                type: 'POST',
                data: { comment_id: commentId },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 'success') {
                        toastr.success(response.message);
                        $('#comment-' + commentId).fadeOut(300, function() {
                            $(this).remove();
                            if ($('#comments-list .comment-item').length == 0) {
                                $('#comments-list').html('<div class="alert alert-info"><?=translate('no_comments_yet')?></div>');
                            }
                        });
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        }
    });
});
</script>

<style>
.comment-item, .reply-item {
    padding: 15px;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
}
.comment-item {
    background-color: #f9f9f9;
    border-radius: 5px;
}
.reply-item {
    margin-left: 60px;
    background-color: #fff;
    border-left: 3px solid #ddd;
    padding-left: 20px;
}
.comment-avatar, .reply-avatar {
    float: left;
    margin-right: 15px;
}
.comment-content, .reply-content {
    overflow: hidden;
}
.comment-header, .reply-header {
    margin-bottom: 8px;
}
.comment-author, .reply-header strong {
    font-size: 14px;
    font-weight: bold;
}
.comment-date, .reply-date {
    font-size: 11px;
    color: #999;
    margin-left: 10px;
}
.comment-type, .reply-type {
    font-size: 10px;
    margin-left: 8px;
    padding: 2px 6px;
}
.comment-body, .reply-body {
    font-size: 13px;
    line-height: 1.5;
}
.comment-attachment, .reply-attachment {
    margin-top: 8px;
}
.avatar-placeholder {
    width: 50px;
    height: 50px;
    background: #f0f0f0;
    border-radius: 50%;
    text-align: center;
    line-height: 50px;
}
.reply-button, .delete-comment {
    margin-left: 5px;
}
.replies-list {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px dashed #ddd;
}
</style>