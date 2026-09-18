<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-users"></i> <?=translate('children_homework')?></h4>
            </header>
            <div class="panel-body">
                <?php if (empty($children_homework)): ?>
                    <div class="alert alert-info"><?=translate('no_homework_found')?></div>
                <?php else: ?>
                    <?php foreach ($children_homework as $child_name => $homeworks): ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title"><?=htmlspecialchars($child_name)?></h4>
                        </div>
                        <div class="panel-body">
                            <?php if (empty($homeworks)): ?>
                                <p class="text-muted"><?=translate('no_homework_assigned')?></p>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th><?=translate('subject')?></th>
                                            <th><?=translate('due_date')?></th>
                                            <th><?=translate('submission_status')?></th>
                                            <th><?=translate('grade')?></th>
                                            <th><?=translate('feedback')?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($homeworks as $hw): ?>
                                        <tr>
                                            <td><?=htmlspecialchars($hw['subject_name'])?></td>
                                            <td><?=_d($hw['date_of_submission'])?></td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'submitted' => 'info',
                                                    'late' => 'warning',
                                                    'returned' => 'danger',
                                                    'resubmitted' => 'primary'
                                                ];
                                                $status = $hw['submission_status'] ?? 'pending';
                                                $class = $status_class[$status] ?? 'warning';
                                                ?>
                                                <span class="label label-<?=$class?>-custom"><?=ucfirst($status)?></span>
                                            </td>
                                            <td><?=$hw['grade'] ?? '-'?>%</td>
                                            <td><?=nl2br(htmlspecialchars(substr($hw['feedback'] ?? '', 0, 100)))?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>