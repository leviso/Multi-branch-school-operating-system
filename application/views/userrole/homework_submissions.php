<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-check-circle"></i> <?php echo translate('my_submissions'); ?></h4>
            </header>
            <div class="panel-body">
                <?php if (empty($submissions)): ?>
                    <div class="alert alert-info"><?=translate('no_submissions_found')?></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th><?=translate('subject')?></th>
                                <th><?=translate('submitted_on')?></th>
                                <th><?=translate('submission_status')?></th>
                                <th><?=translate('attachment')?></th>
                                <th><?=translate('message')?></th>
                                <th><?=translate('due_date')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><?=htmlspecialchars($sub['subject_name'])?></td>
                                <td><?=date('d/m/Y H:i', strtotime($sub['created_at']))?></td>
                                <td>
                                    <?php 
                                    $status_class = [
                                        'submitted' => 'info',
                                        'late' => 'warning',
                                        'returned' => 'danger',
                                        'resubmitted' => 'primary'
                                    ];
                                    $class = $status_class[$sub['submission_status']] ?? 'default';
                                    ?>
                                    <span class="label label-<?=$class?>-custom"><?=ucfirst($sub['submission_status'])?></span>
                                </td>
                                <td>
                                    <?php if (!empty($sub['enc_name'])): ?>
                                    <a href="<?=base_url('userrole/download_submitted?file=' . urlencode($sub['enc_name']))?>" class="btn btn-xs btn-default">
                                        <i class="fas fa-download"></i> <?=translate('download')?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted"><?=translate('no_file')?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="message-cell" style="max-width:250px">
                                    <?=nl2br(htmlspecialchars(substr($sub['message'] ?? '', 0, 100)))?>
                                    <?php if (strlen($sub['message'] ?? '') > 100): ?>
                                    <a href="javascript:void(0)" class="view-message" data-message="<?=htmlspecialchars($sub['message'] ?? '')?>">[more]</a>
                                    <?php endif; ?>
                                </td>
                                <td><?=_d($sub['date_of_submission'])?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<!-- Message Modal -->
<div class="zoom-anim-dialog modal-block mfp-hide" id="message-modal">
    <section class="panel">
        <header class="panel-heading">
            <h4 class="panel-title"><?=translate('submission_message')?></h4>
        </header>
        <div class="panel-body">
            <div id="full-message" class="well"></div>
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
    $('.view-message').on('click', function() {
        var message = $(this).data('message');
        $('#full-message').html('<div class="well">' + nl2br(escapeHtml(message)) + '</div>');
        mfp_modal('#message-modal');
    });
    
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    function nl2br(str) {
        return str.replace(/\n/g, '<br>');
    }
});
</script>