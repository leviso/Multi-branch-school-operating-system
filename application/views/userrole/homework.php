<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="far fa-folder"></i> <?php echo translate('homework'); ?></h4>
            </header>
            <div class="panel-body">
                <?php if (empty($homeworklist)): ?>
                    <div class="alert alert-info"><?=translate('no_homework_found')?></div>
                <?php else: ?>
                <section class="panel-group mt-md" id="accordion">
                    <?php foreach ($homeworklist as $key => $row): ?>
                    <?php 
                    $is_submitted = !empty($row['submission_id']);
                    $is_late = (strtotime($row['date_of_submission']) < strtotime(date('Y-m-d')));
                    $can_submit = (!$is_submitted && !$is_late);
                    ?>
                    <div class="panel panel-accordion">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#<?php echo $row['id']; ?>">
                                    <i class="far fa-sticky-note"></i> <?php echo $row['subject_name']; ?> - <?=_d($row['date_of_homework'])?>
                                    <?php if ($is_submitted): ?>
                                        <span class="label label-success-custom pull-right"><?=translate('submitted')?></span>
                                    <?php elseif ($is_late): ?>
                                        <span class="label label-danger-custom pull-right"><?=translate('late')?></span>
                                    <?php else: ?>
                                        <span class="label label-warning-custom pull-right"><?=translate('pending')?></span>
                                    <?php endif; ?>
                                </a>
                            </h4>
                        </div>
                        <div id="<?php echo $row['id']; ?>" class="accordion-body collapse">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <ul class="nav nav-stacked">
                                            <li><i class="far fa-calendar"></i> <span class="text-weight-semibold"><?=translate('date_of_homework')?></span> : <?=_d($row['date_of_homework'])?></li>
                                            <li><i class="far fa-calendar"></i> <span class="text-weight-semibold"><?=translate('date_of_submission')?></span> : <?=_d($row['date_of_submission'])?></li>
                                            <?php if ($is_late): ?>
                                            <li><span class="text-danger"><i class="fas fa-exclamation-triangle"></i> <?=translate('overdue')?></span></li>
                                            <?php endif; ?>
                                        </ul>
                                        <ul class="nav nav-stacked mt-md">
                                            <li><span class="text-weight-semibold"><?=translate('subject')?></span> : <?=$row['subject_name']?></li>
                                            <li><span class="text-weight-semibold"><?=translate('class')?></span> : <?=$row['class_name']?></li>
                                            <li><span class="text-weight-semibold"><?=translate('section')?></span> : <?=$row['section_name']?></li>
                                            <?php if (!empty($row['document'])): ?>
                                            <li><span class="text-weight-semibold"><?=translate('documents')?></span> : 
                                                <a href="<?=base_url('homework/download/' . $row['id'])?>" class="btn btn-default btn-circle icon">
                                                    <i class="fas fa-cloud-download-alt"></i>
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                        
                                        <!-- Submission Status -->
                                        <div class="mt-md">
                                            <span class="text-weight-semibold"><?=translate('submission_status')?></span> : 
                                            <?php 
                                            $status_badge = [
                                                'submitted' => 'info',
                                                'late' => 'warning',
                                                'returned' => 'danger',
                                                'resubmitted' => 'primary'
                                            ];
                                            $current_status = $row['submission_status'] ?? 'not_submitted';
                                            $badge_class = $status_badge[$current_status] ?? 'default';
                                            ?>
                                            <span class="label label-<?=$badge_class?>-custom">
                                                <?=ucfirst(str_replace('_', ' ', $current_status))?>
                                            </span>
                                        </div>
                                        
                                        <!-- Feedback Display -->
                                        <?php if (!empty($row['feedback'])): ?>
                                        <div class="mt-md">
                                            <span class="text-weight-semibold"><?=translate('teacher_feedback')?></span> : 
                                            <?=nl2br(htmlspecialchars($row['feedback']))?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($row['grade'])): ?>
                                        <div class="mt-md">
                                            <span class="text-weight-semibold"><?=translate('grade')?></span> : 
                                            <strong><?=$row['grade']?>%</strong>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Submit Button -->
                                        <?php if ($can_submit): ?>
                                        <div class="mt-md">
                                            <button onclick="showModal(<?php echo $row['id']; ?>)" class="btn btn-primary btn-sm">
                                                <i class="fas fa-upload"></i> <?=translate('submit_homework')?>
                                            </button>
                                        </div>
                                        <?php elseif ($is_submitted && !$is_late): ?>
                                        <div class="mt-md">
                                            <span class="text-success"><i class="fas fa-check-circle"></i> <?=translate('submitted_successfully')?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<!-- Submit Modal -->
<div class="zoom-anim-dialog modal-block mfp-hide" id="modal">
    <section class="panel">
        <header class="panel-heading">
            <h4 class="panel-title"><i class="fas fa-upload"></i> <?php echo translate('assignment'); ?></h4>
        </header>
        <?php echo form_open_multipart('userrole/assignment_upload', array('class' => 'frm-submit-data'));?>
        <input type="hidden" id="homeworkID" name="homework_id">
        <input type="hidden" id="assigmentID" name="assigment_id">
        <div class="panel-body">
            <div class="form-group">
                <label class="control-label"><?php echo translate('message') ?></label>
                <textarea name="message" id="message" class="form-control" rows="4" placeholder="<?=translate('write_your_answer_here')?>"></textarea>
                <span class="error"></span>
            </div>
            <div class="form-group">
                <label class="control-label"><?php echo translate('attachment_file') ?> <span class="required">*</span></label>
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
                <input type="hidden" id="old_file" name="old_file">
                <span class="error"></span>
            </div>
        </div>
        <footer class="panel-footer">
            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary" data-loading-text="<i class='fas fa-spinner fa-spin'></i> <?=translate('processing')?>">
                        <?php echo translate('submit'); ?>
                    </button>
                    <button class="btn btn-default modal-dismiss"><?php echo translate('close'); ?></button>
                </div>
            </div>
        </footer>
        <?php echo form_close(); ?>
    </section>
</div>

<script type="text/javascript">
function showModal(id) {
    $(".error").html("");
    $("#message").val("");
    $("#homeworkID").val(id);
    $.ajax({
        url: base_url + 'userrole/getHomeworkAssignment',
        type: 'POST',
        dataType: "json",
        data: { 'id': id },
        success: function(data) {
            $("#old_file").val(data.file_name);
            $("#assigmentID").val(data.id);
            $("#message").val(data.message);
        }
    });
    mfp_modal('#modal');
}
</script>