<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-upload"></i> <?=translate('submit_homework')?>
                </h4>
                <div class="panel-btn">
                    <a href="<?=base_url('userrole/homework')?>" class="btn btn-default btn-circle">
                        <i class="fas fa-arrow-left"></i> <?=translate('back')?>
                    </a>
                </div>
            </header>
            <div class="panel-body">
                <!-- Homework Details -->
                <div class="well">
                    <h4><?=htmlspecialchars($subject->name ?? '')?> - <?=_d($homework->date_of_homework)?></h4>
                    <p><strong><?=translate('class')?>:</strong> <?=$class->name ?? ''?> (<?=$section->name ?? ''?>)</p>
                    <p><strong><?=translate('submission_deadline')?>:</strong> <?=_d($homework->date_of_submission)?></p>
                    <p><strong><?=translate('description')?>:</strong></p>
                    <div class="alert alert-info"><?=nl2br(htmlspecialchars($homework->description))?></div>
                    
                    <?php if (!empty($homework->document)): ?>
                    <p><strong><?=translate('attachment')?>:</strong> 
                        <a href="<?=base_url('homework/download/' . $homework->id)?>" class="btn btn-sm btn-default">
                            <i class="fas fa-download"></i> <?=translate('download')?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
                
                <!-- Submission Form -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?=translate('your_submission')?></h4>
                    </div>
                    <div class="panel-body">
                        <?php echo form_open_multipart('homework/save_submission', ['class' => 'frm-submit-data', 'id' => 'submission-form']); ?>
                        <input type="hidden" name="homework_id" value="<?=$homework->id?>">
                        
                        <div class="form-group">
                            <label class="control-label"><?=translate('message')?></label>
                            <textarea name="message" class="form-control" rows="4" placeholder="<?=translate('write_your_answer_here')?>"><?=htmlspecialchars($submission->message ?? '')?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label"><?=translate('attachment_file')?></label>
                            <div class="fileupload fileupload-new" data-provides="fileupload">
                                <div class="input-append">
                                    <div class="uneditable-input">
                                        <i class="fas fa-file fileupload-exists"></i>
                                        <span class="fileupload-preview"></span>
                                    </div>
                                    <span class="btn btn-default btn-file">
                                        <span class="fileupload-exists"><?=translate('change')?></span>
                                        <span class="fileupload-new"><?=translate('select_file')?></span>
                                        <input type="file" name="attachment_file">
                                    </span>
                                    <a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload"><?=translate('remove')?></a>
                                </div>
                            </div>
                            <?php if (!empty($submission->file_name)): ?>
                            <p class="mt-sm text-muted"><?=translate('current_file')?>: 
                                <a href="<?=base_url('homework/download_submitted?file=' . urlencode($submission->enc_name))?>">
                                    <?=htmlspecialchars($submission->file_name)?>
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" data-loading-text="<i class='fas fa-spinner fa-spin'></i> <?=translate('processing')?>">
                                <i class="fas fa-save"></i> <?=translate('submit_homework')?>
                            </button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
                
                <!-- Previous Feedback (if any) -->
                <?php if (!empty($submission->feedback) || !empty($submission->grade)): ?>
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?=translate('teacher_feedback')?></h4>
                    </div>
                    <div class="panel-body">
                        <p><strong><?=translate('grade')?>:</strong> <?=$submission->grade?>%</p>
                        <p><strong><?=translate('feedback')?>:</strong> <?=nl2br(htmlspecialchars($submission->feedback))?></p>
                        <?php if (!empty($submission->feedback_date)): ?>
                        <p class="text-muted"><small><?=translate('feedback_date')?>: <?=date('d/m/Y H:i', strtotime($submission->feedback_date))?></small></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#submission-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var btn = $(this).find('button[type="submit"]');
        var originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> <?=translate('processing')?>').prop('disabled', true);
        
        $.ajax({
            url: base_url + 'homework/save_submission',
            type: 'POST',
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.status == 'success') {
                    toastr.success(response.message);
                    setTimeout(function() {
                        window.location.href = base_url + 'userrole/homework';
                    }, 1500);
                } else {
                    toastr.error(response.message || '<?=translate('submission_failed')?>');
                    btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                toastr.error('<?=translate('an_error_occurred')?>');
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>