<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-file-medical"></i> Active Recovery Plans (IARP)
                </h4>
            </header>
            <div class="panel-body">
                
                <?php if (empty($active_iarps)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x"></i>
                    <h4>No Active Recovery Plans</h4>
                    <p>Start by creating a transfer assessment and generating an IARP.</p>
                    <a href="<?=base_url('stars/assessments')?>" class="btn btn-primary">
                        <i class="fas fa-clipboard-list"></i> View Assessments
                    </a>
                </div>
                <?php else: ?>
                
                <div class="table-responsive">
                    <div class="export_title">Active Recovery Plans Report</div>
                    <table class="table table-bordered table-hover table-export">
                        <thead>
                            <tr>
                                <th>Plan Code</th>
                                <th>Student</th>
                                <th>Register No</th>
                                <th>Class/Section</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Start Date</th>
                                <th>Target End Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_iarps as $iarp): ?>
                            <?php
                            $total_topics = $iarp['missing_topics_count'] + $iarp['completed_topics_count'];
                            $progress_percent = $total_topics > 0 ? round(($iarp['completed_topics_count'] / $total_topics) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="label label-info"><?=$iarp['plan_code']?></span>
                                </td>
                                <td>
                                    <strong><?=$iarp['first_name']?> <?=$iarp['last_name']?></strong>
                                </td>
                                <td><?=$iarp['register_no']?></td>
                                <td><?=$iarp['class_name']?> <?=$iarp['section_name']?></td>
                                <td width="200">
                                    <?php if ($iarp['status'] == 'active'): ?>
                                        <div class="progress" style="margin-bottom: 5px;">
                                            <div class="progress-bar progress-bar-success" role="progressbar" 
                                                style="width: <?=$progress_percent?>%;">
                                                <?=$progress_percent?>%
                                            </div>
                                        </div>
                                        <small>
                                            <?=$iarp['completed_topics_count']?>/<?=$iarp['missing_topics_count'] + $iarp['completed_topics_count']?> topics completed
                                        </small>
                                        <?php if ($iarp['missing_topics_count'] > 0): ?>
                                            <br>
                                            <small class="text-muted">
                                                Pending: <?=$iarp['missing_topics_count']?> topic(s)
                                            </small>
                                        <?php endif; ?>
                                    <?php elseif ($iarp['status'] == 'draft'): ?>
                                        <span class="text-muted">Not started</span>
                                    <?php elseif ($iarp['status'] == 'completed'): ?>
                                        <span class="text-success">✅ Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?=date('d M Y', strtotime($iarp['start_date']))?></td>
                                <td><?=date('d M Y', strtotime($iarp['target_end_date']))?></td>
                                 <td>
                                    <div class="btn-group">
                                        <a href="<?=base_url('stars/view_iarp/' . $iarp['id'])?>" class="btn btn-xs btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($iarp['status'] == 'draft'): ?>
                                        <a href="<?=base_url('stars/activate_iarp/' . $iarp['id'])?>" class="btn btn-xs btn-success" 
                                           onclick="return confirm('Activate this IARP? Parent will be notified.')">
                                            <i class="fas fa-play"></i> Activate
                                        </a>
                                        <?php endif; ?>
                                    </div>
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