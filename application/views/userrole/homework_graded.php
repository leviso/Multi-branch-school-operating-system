<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-star"></i> <?php echo translate('graded_homework'); ?></h4>
            </header>
            <div class="panel-body">
                <?php if (empty($graded_homework)): ?>
                    <div class="alert alert-info"><?=translate('no_graded_homework_found')?></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th><?=translate('subject')?></th>
                                <th><?=translate('submitted_on')?></th>
                                <th><?=translate('grade')?></th>
                                <th><?=translate('feedback')?></th>
                                <th><?=translate('feedback_date')?></th>
                                <th><?=translate('attachment')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($graded_homework as $graded): ?>
                            <tr>
                                <td><?=htmlspecialchars($graded['subject_name'])?></td>
                                <td><?=date('d/m/Y', strtotime($graded['created_at']))?></td>
                                <td class="text-center">
                                    <span class="label label-success-custom"><?=$graded['grade'] ?? 'N/A'?>%</span>
                                </td>
                                <td style="max-width:300px">
                                    <?=nl2br(htmlspecialchars($graded['feedback'] ?? ''))?>
                                    <?php if (empty($graded['feedback'])): ?>
                                    <span class="text-muted"><?=translate('no_feedback')?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?=!empty($graded['feedback_date']) ? date('d/m/Y H:i', strtotime($graded['feedback_date'])) : 'N/A'?></td>
                                <td>
                                    <?php if (!empty($graded['enc_name'])): ?>
                                    <a href="<?=base_url('userrole/download_submitted?file=' . urlencode($graded['enc_name']))?>" class="btn btn-xs btn-default">
                                        <i class="fas fa-download"></i> <?=translate('download')?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted"><?=translate('no_file')?></span>
                                    <?php endif; ?>
                                </td>
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