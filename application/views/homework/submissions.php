<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-check-double"></i> 
                    <?=translate('homework_submissions')?> - <?=$homework->description?>
                </h4>
                <div class="panel-btn">
                    <a href="<?=base_url('homework')?>" class="btn btn-default btn-circle">
                        <i class="fas fa-arrow-left"></i> <?=translate('back')?>
                    </a>
                </div>
            </header>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th><?=translate('student')?></th>
                                <th><?=translate('register_no')?></th>
                                <th><?=translate('submission_status')?></th>
                                <th><?=translate('submitted_at')?></th>
                                <th><?=translate('file')?></th>
                                <th><?=translate('grade')?></th>
                                <th><?=translate('feedback')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><?=$sub['first_name']?> <?=$sub['last_name']?></td>
                                <td><?=$sub['register_no']?></td>
                                <td>
                                    <?php 
                                    $status_class = [
                                        'submitted' => 'label-info',
                                        'late' => 'label-warning',
                                        'returned' => 'label-danger',
                                        'resubmitted' => 'label-primary'
                                    ];
                                    $class = $status_class[$sub['submission_status']] ?? 'label-default';
                                    ?>
                                    <span class="label <?=$class?>"><?=ucfirst($sub['submission_status'])?></span>
                                 </td>
                                <td><?=!empty($sub['created_at']) ? date('d/m/Y H:i', strtotime($sub['created_at'])) : 'N/A'?></td>
                                <td>
                                    <?php if (!empty($sub['enc_name'])): ?>
                                    <a href="<?=base_url('homework/download_submitted?file=' . urlencode($sub['enc_name']))?>" class="btn btn-sm btn-default">
                                        <i class="fas fa-download"></i> <?=translate('download')?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted"><?=translate('no_file')?></span>
                                    <?php endif; ?>
                                 </td>
                                <td>
                                    <input type="number" class="form-control input-sm grade-input" 
                                           data-id="<?=$sub['id']?>" value="<?=$sub['grade']?>" 
                                           min="0" max="100" style="width:80px">
                                 </td>
                                <td>
                                    <textarea class="form-control feedback-textarea" 
                                              data-id="<?=$sub['id']?>" rows="2" 
                                              style="width:200px"><?=htmlspecialchars($sub['feedback'])?></textarea>
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
                                <td colspan="8" class="text-center"><?=translate('no_submissions_found')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.save-feedback').on('click', function() {
        var id = $(this).data('id');
        var grade = $('.grade-input[data-id="' + id + '"]').val();
        var feedback = $('.feedback-textarea[data-id="' + id + '"]').val();
        var status = $('.status-select[data-id="' + id + '"]').val();
        
        $.ajax({
            url: base_url + 'homework/save_submission_feedback',
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
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                toastr.error('Failed to save feedback');
            }
        });
    });
});
</script>