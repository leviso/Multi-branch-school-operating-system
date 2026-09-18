<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-calendar-week"></i> My Weekly Progress Report
                </h4>
            </header>
            <div class="panel-body">
                
                <?php if (empty($iarp)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x"></i>
                    <h4>No Active Recovery Plan</h4>
                    <p>You do not have an active academic recovery plan at this time.</p>
                </div>
                <?php elseif (empty($progress_records)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x"></i>
                    <h4>No Progress Records Yet</h4>
                    <p>Your weekly progress will appear here as your teachers update it.</p>
                </div>
                <?php else: ?>
                
                <!-- Progress Overview Card -->
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h3><?=$total_topics ?? 0?></h3>
                            <p>Total Topics</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-success"><?=$completed_topics ?? 0?></h3>
                            <p>Completed</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-warning"><?=($total_topics ?? 0) - ($completed_topics ?? 0)?></h3>
                            <p>Remaining</p>
                        </div>
                    </div>
                    <div class="progress" style="height: 25px; margin-top: 10px;">
                        <div class="progress-bar progress-bar-success progress-bar-striped" 
                             role="progressbar" 
                             style="width: <?=$progress_percent ?? 0?>%;">
                            <?=$progress_percent ?? 0?>% Complete
                        </div>
                    </div>
                </div>
                
                <!-- Weekly Progress Table with Details -->
                <div class="headers-line mt-md">
                    <i class="fas fa-list-alt"></i> Weekly Breakdown
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="8%">Week</th>
                                <th width="15%">Period</th>
                                <th width="35%">✅ Work Completed</th>
                                <th width="35%">⏳ Work Remaining</th>
                                <th width="7%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($progress_records as $prog): ?>
                            <tr class="<?=($prog['status'] == 'completed') ? 'success' : ''?>">
                                <td class="text-center">
                                    <strong>Week <?=$prog['week_number']?></strong>
                                    <?php if ($prog['topics_covered_count'] > 0): ?>
                                        <br>
                                        <span class="label label-info"><?=$prog['topics_covered_count']?> item(s)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?=date('d M', strtotime($prog['week_start_date']))?> - <?=date('d M', strtotime($prog['week_end_date']))?>
                                </td>
                                
                                <!-- Completed Work Column -->
                                <td>
                                    <?php if (!empty($prog['completed_items'])): ?>
                                        <?php 
                                        $current_topic = '';
                                        foreach ($prog['completed_items'] as $item):
                                            $show_topic = ($item['topic_name'] != $current_topic);
                                            $current_topic = $item['topic_name'];
                                        ?>
                                            <?php if ($show_topic): ?>
                                                <div class="topic-header mt-sm"><strong><?=html_escape($item['topic_name'])?></strong></div>
                                            <?php endif; ?>
                                            <div class="subtopic-item">
                                                <i class="fas fa-check-circle text-success"></i>
                                                <?=html_escape($item['subtopic_name'])?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No work recorded this week</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Remaining Work Column -->
                                <td>
                                    <?php if (!empty($prog['remaining_items'])): ?>
                                        <?php 
                                        $current_topic = '';
                                        foreach ($prog['remaining_items'] as $item):
                                            $show_topic = ($item['topic_name'] != $current_topic);
                                            $current_topic = $item['topic_name'];
                                        ?>
                                            <?php if ($show_topic): ?>
                                                <div class="topic-header mt-sm"><strong><?=html_escape($item['topic_name'])?></strong></div>
                                            <?php endif; ?>
                                            <div class="subtopic-item">
                                                <i class="fas fa-hourglass-half text-warning"></i>
                                                <?=html_escape($item['subtopic_name'])?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-success">All completed! 🎉</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Status Column -->
                                <td class="text-center">
                                    <span class="label label-<?=($prog['status'] == 'on_track') ? 'success' : (($prog['status'] == 'slightly_behind') ? 'warning' : 'danger')?>">
                                        <?=str_replace('_', ' ', ucfirst($prog['status']))?>
                                    </span>
                                    <?php if ($prog['improvement_percentage'] > 0): ?>
                                        <br>
                                        <small class="text-muted">+<?=$prog['improvement_percentage']?>%</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <!-- Teacher Comments Row -->
                            <?php if (!empty($prog['teacher_comments'])): ?>
                            <tr class="active">
                                <td colspan="5" style="background: #f9f9f9;">
                                    <i class="fas fa-comment"></i> 
                                    <strong>Teacher's Feedback:</strong> <?=nl2br(html_escape($prog['teacher_comments']))?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php endif; ?>
                
            </div>
        </section>
    </div>
</div>

<style>
.topic-header {
    font-weight: bold;
    margin-top: 8px;
    margin-bottom: 4px;
    color: #2c3e50;
}
.subtopic-item {
    margin-left: 20px;
    margin-bottom: 3px;
    font-size: 13px;
}
.table-responsive .label {
    font-size: 10px;
    padding: 2px 5px;
}
.progress-bar {
    line-height: 25px;
    font-size: 12px;
}
</style>