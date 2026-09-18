<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-check-double"></i> <?=translate('manage_submissions')?>
                </h4>
                <div class="panel-btn">
                    <a href="<?=base_url('homework')?>" class="btn btn-default btn-circle">
                        <i class="fas fa-arrow-left"></i> <?=translate('back')?>
                    </a>
                </div>
            </header>
            <div class="panel-body">
                <!-- Homework Info -->
                <div class="well well-sm">
                    <div class="row">
                        <div class="col-md-6">
                            <strong><?=translate('subject')?>:</strong> <?=htmlspecialchars($subject->name ?? '')?><br>
                            <strong><?=translate('class')?>:</strong> <?=$class->name ?? ''?> (<?=$section->name ?? ''?>)
                        </div>
                        <div class="col-md-6 text-right">
                            <strong><?=translate('submission_deadline')?>:</strong> <?=_d($homework->date_of_submission)?><br>
                            <strong><?=translate('total_students')?>:</strong> <?=count($submissions)?>
                        </div>
                    </div>
                </div>
                
                <!-- Submissions Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th><?=translate('student')?></th>
                                <th><?=translate('register_no')?></th>
                                <th><?=translate('submission_status')?></th>
                                <th><?=translate('submitted_at')?></th>
                                <th><?=translate('attachment')?></th>
                                <th><?=translate('message')?></th>
                                <th width="15%"><?=translate('grade')?></th>
                                <th width="25%"><?=translate('feedback')?></th>
                                <th width="10%"><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                            <tr id="row-<?=$sub['id']?>">
                                <td><?=htmlspecialchars($sub['first_name'] ?? '')?> <?=htmlspecialchars($sub['last_name'] ?? '')?></td>
                                <td><?=$sub['register_no'] ?? 'N/A'?></td>
                                <td>
                                    <?php 
                                    $status_class = [
                                        'submitted' => 'label-info',
                                        'late' => 'label-warning',
                                        'reviewed' => 'label-primary',
                                        'returned' => 'label-danger',
                                        'resubmitted' => 'label-success'
                                    ];
                                    $class = $status_class[$sub['submission_status']] ?? 'label-default';
                                    ?>
                                    <select class="form-control input-sm status-select" data-id="<?=$sub['id']?>" style="width:110px">
                                        <option value="submitted" <?=($sub['submission_status'] == 'submitted') ? 'selected' : ''?>>Submitted</option>
                                        <option value="reviewed" <?=($sub['submission_status'] == 'reviewed') ? 'selected' : ''?>>Reviewed</option>
                                        <option value="returned" <?=($sub['submission_status'] == 'returned') ? 'selected' : ''?>>Returned</option>
                                        <option value="late" <?=($sub['submission_status'] == 'late') ? 'selected' : ''?>>Late</option>
                                    </select>
                                </td>
                                <td><?=!empty($sub['created_at']) ? date('d/m/Y H:i', strtotime($sub['created_at'])) : 'N/A'?></td>
                                <td>
                                    <?php if (!empty($sub['enc_name'])): ?>
                                    <a href="<?=base_url('homework/download_submitted?file=' . urlencode($sub['enc_name']))?>" class="btn btn-xs btn-default" target="_blank">
                                        <i class="fas fa-download"></i> <?=translate('download')?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted"><?=translate('no_file')?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="message-cell" style="max-width:200px">
                                    <span class="message-preview"><?=htmlspecialchars(substr($sub['message'] ?? '', 0, 50))?></span>
                                    <?php if (strlen($sub['message'] ?? '') > 50): ?>
                                    <a href="javascript:void(0)" class="view-message" data-message="<?=htmlspecialchars($sub['message'] ?? '')?>">[more]</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="number" class="form-control input-sm grade-input" 
                                           data-id="<?=$sub['id']?>" value="<?=$sub['grade']?>" 
                                           min="0" max="100" step="0.5" style="width:80px">
                                </td>
                                <td>
                                    <textarea class="form-control feedback-textarea" 
                                              data-id="<?=$sub['id']?>" rows="2" 
                                              style="width:100%"><?=htmlspecialchars($sub['feedback'] ?? '')?></textarea>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success save-feedback" data-id="<?=$sub['id']?>">
                                        <i class="fas fa-save"></i> <?=translate('save')?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="9" class="text-center"><?=translate('no_submissions_found')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Message Modal -->
<div class="zoom-anim-dialog modal-block mfp-hide" id="message-modal">
    <section class="panel">
        <header class="panel-heading">
            <h4 class="panel-title"><?=translate('student_message')?></h4>
        </header>
        <div class="panel-body">
            <div id="full-message"></div>
        </div>
        <footer class="panel-footer">
            <div class="row">
                <div class="col-md-12 text-right">
                    <button class="btn btn-default modal-dismiss"><?=translate('close')?></button>
                </div>
            </div>
        </footer>
    </section>
</div>

<script>
$(document).ready(function() {
    // View full message
    $('.view-message').on('click', function() {
        var message = $(this).data('message');
        $('#full-message').html('<div class="well">' + nl2br(escapeHtml(message)) + '</div>');
        mfp_modal('#message-modal');
    });
    
    // Save feedback
    $('.save-feedback').on('click', function() {
        var id = $(this).data('id');
        var grade = $('.grade-input[data-id="' + id + '"]').val();
        var feedback = $('.feedback-textarea[data-id="' + id + '"]').val();
        var status = $('.status-select[data-id="' + id + '"]').val();
        var btn = $(this);
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.ajax({
            url: base_url + 'homework/save_feedback',
            type: 'POST',
            data: {
                submission_id: id,
                grade: grade,
                feedback: feedback,
                submission_status: status
            },
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') {
                    toastr.success(res.message);
                    // Update status display
                    var statusText = $('.status-select[data-id="' + id + '"] option:selected').text();
                    $('.status-select[data-id="' + id + '"]').closest('td').prevAll('td:first').find('.label')
                        .removeClass().addClass('label label-' + getStatusClass(status.toLowerCase()));
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                toastr.error('<?=translate('an_error_occurred')?>');
            },
            complete: function() {
                btn.html('<i class="fas fa-save"></i> <?=translate('save')?>').prop('disabled', false);
            }
        });
    });
    
    function getStatusClass(status) {
        var classes = {
            'submitted': 'info',
            'late': 'warning',
            'reviewed': 'primary',
            'returned': 'danger'
        };
        return classes[status] || 'default';
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        }).replace(/\n/g, '<br>');
    }
    
    function nl2br(str) {
        return str.replace(/\n/g, '<br>');
    }
});
</script>

<style>
.message-preview {
    font-size: 12px;
    color: #666;
}
.view-message {
    font-size: 11px;
    margin-left: 5px;
}
.grade-input {
    text-align: center;
}
.feedback-textarea {
    resize: vertical;
    font-size: 12px;
}
</style>