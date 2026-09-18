<?php $widget = (is_superadmin_loggedin() ? 4 : 6); ?>
<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><?=translate('teacher_risk_analysis')?></h4>
            </header>
            <?php echo form_open($this->uri->uri_string(), array('class' => 'validate'));?>
            <div class="panel-body">
                <div class="row mb-sm">
                    <?php if (is_superadmin_loggedin()): ?>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
                            <?php
                                $arrayBranch = $this->app_lib->getSelectList('branch');
                                echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id', $branch_id), "class='form-control' onchange='this.form.submit()'
                                data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-offset-10 col-md-2">
                        <button type="submit" name="search" value="1" class="btn btn-default btn-block"> <i class="fas fa-filter"></i> <?=translate('filter')?></button>
                    </div>
                </div>
            </footer>
            <?php echo form_close();?>
        </section>

        <?php if (isset($teachers)): ?>
        <section class="panel appear-animation" data-appear-animation="<?=$global_config['animations'];?>" data-appear-animation-delay="100">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-exclamation-triangle"></i> <?=translate('risk_summary')?></h4>
            </header>
            <div class="panel-body">
                <!-- Risk Summary Cards -->
                <div class="row mb-lg">
                    <div class="col-md-4">
                        <div class="panel panel-default bg-success" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $risk_distribution->low_count ?? 0; ?></h2>
                                <p class="mb-none"><i class="fas fa-check-circle"></i> <?=translate('low_risk')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default bg-warning" style="background: linear-gradient(135deg, #ffc107 0%, #f6d365 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $risk_distribution->medium_count ?? 0; ?></h2>
                                <p class="mb-none"><i class="fas fa-exclamation-circle"></i> <?=translate('medium_risk')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default bg-danger" style="background: linear-gradient(135deg, #dc3545 0%, #fa709a 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $risk_distribution->high_count ?? 0; ?></h2>
                                <p class="mb-none"><i class="fas fa-bell"></i> <?=translate('high_risk')?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('teacher_name')?></th>
                                <th><?=translate('risk_level')?></th>
                                <th><?=translate('risk_score')?></th>
                                <th><?=translate('risk_factors')?></th>
                                <th><?=translate('performance')?></th>
                                <th><?=translate('workload')?></th>
                                <th><?=translate('recommended_action')?></th>
                                <th><?=translate('alert_status')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            foreach($teachers as $teacher): 
                                $risk_item = isset($risk_data[$teacher->id]) ? $risk_data[$teacher->id] : null;
                                $risk = $risk_item ? $risk_item['risk'] : null;
                                $perf = $risk_item ? $risk_item['performance'] : null;
                                $workload = $risk_item ? $risk_item['workload'] : null;
                                
                                $risk_factors = array();
                                if($risk && !empty($risk->risk_factors)) {
                                    $risk_factors = json_decode($risk->risk_factors, true);
                                    if(!is_array($risk_factors)) $risk_factors = array();
                                }
                                
                                $row_class = '';
                                if($risk && $risk->risk_level == 'high') $row_class = 'danger';
                                elseif($risk && $risk->risk_level == 'medium') $row_class = 'warning';
                            ?>
                            <tr> class="<?php echo $row_class; ?>">
                                <td><?php echo $count++; ?></td>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo get_type_name_by_id('branch', $teacher->branch_id); ?></td>
                                <?php endif; ?>
                                <td>
                                    <strong><?php echo htmlspecialchars($teacher->name, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                    <small class="text-muted"><?php echo $teacher->staff_id; ?></small>
                                </td>
                                <td>
                                    <?php
                                    $risk_level = isset($risk->risk_level) ? $risk->risk_level : 'low';
                                    $risk_badge = 'success';
                                    if($risk_level == 'medium') $risk_badge = 'warning';
                                    if($risk_level == 'high') $risk_badge = 'danger';
                                    ?>
                                    <span class="label label-<?php echo $risk_badge; ?>"><?php echo ucfirst($risk_level); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo isset($risk->prediction_score) ? round($risk->prediction_score, 1) : 0; ?></strong>
                                    <div class="progress mt-sm mb-none" style="height: 5px;">
                                        <?php 
                                        $score = isset($risk->prediction_score) ? $risk->prediction_score : 0;
                                        $bar_class = 'success';
                                        if($score >= 60) $bar_class = 'danger';
                                        elseif($score >= 30) $bar_class = 'warning';
                                        ?>
                                        <div class="progress-bar progress-bar-<?php echo $bar_class; ?>" style="width: <?php echo min(100, $score); ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if(!empty($risk_factors)): ?>
                                    <ul class="list-unstyled mb-none">
                                        <?php foreach($risk_factors as $factor): ?>
                                        <li><small><i class="fas fa-times-circle text-danger"></i> <?php echo htmlspecialchars($factor, ENT_QUOTES, 'UTF-8'); ?></small></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $perf_rating = isset($perf->rating) ? $perf->rating : 'D';
                                    $rating_badge = 'danger';
                                    if($perf_rating == 'A') $rating_badge = 'success';
                                    elseif($perf_rating == 'B') $rating_badge = 'info';
                                    elseif($perf_rating == 'C') $rating_badge = 'warning';
                                    ?>
                                    <span class="label label-<?php echo $rating_badge; ?>"><?php echo $perf_rating; ?></span>
                                    <small>(<?php echo isset($perf->total_score) ? round($perf->total_score, 1) : 0; ?>%)</small>
                                </td>
                                <td>
                                    <?php
                                    $workload_status = isset($workload->status) ? $workload->status : 'normal';
                                    $status_class = 'success';
                                    if($workload_status == 'overloaded') $status_class = 'danger';
                                    elseif($workload_status == 'underutilized') $status_class = 'warning';
                                    ?>
                                    <span class="label label-<?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $workload_status)); ?></span>
                                </td>
                                <td>
                                    <small><?php echo isset($risk->recommended_action) ? htmlspecialchars($risk->recommended_action, ENT_QUOTES, 'UTF-8') : 'Monitor regularly'; ?></small>
                                </td>
                                <td>
                                    <?php if(isset($risk->alert_sent) && $risk->alert_sent == 1): ?>
                                    <span class="label label-info"><i class="fas fa-check"></i> Alert Sent</span>
                                    <?php else: ?>
                                    <span class="label label-default">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($teachers)): ?>
                            <tr>
                                <td colspan="<?php echo (is_superadmin_loggedin() ? 10 : 9); ?>" class="text-center"><?=translate('no_records_found')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <?php if(is_admin_loggedin() || is_superadmin_loggedin()): ?>
        <?php if(!empty($high_risk_teachers)): ?>
        <section class="panel appear-animation" data-appear-animation="<?=$global_config['animations'];?>" data-appear-animation-delay="100">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-tasks"></i> <?=translate('risk_intervention_actions')?></h4>
            </header>
            <div class="panel-body">
                <div class="list-group">
                    <?php foreach($high_risk_teachers as $teacher): ?>
                    <div class="list-group-item">
                        <div class="row">
                            <div class="col-md-4">
                                <strong><?php echo htmlspecialchars($teacher->name, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div class="col-md-5">
                                <small>Risk Score: <?php echo isset($teacher->prediction_score) ? round($teacher->prediction_score, 1) : 0; ?></small>
                            </div>
                            <div class="col-md-3 text-right">
                                <button type="button" class="btn btn-xs btn-primary" onclick="scheduleReview(<?php echo $teacher->teacher_id; ?>)">
                                    <i class="fas fa-calendar-alt"></i> Schedule Review
                                </button>
                                <button type="button" class="btn btn-xs btn-success" onclick="sendAlert(<?php echo $teacher->teacher_id; ?>)">
                                    <i class="fas fa-bell"></i> Send Alert
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.mb-lg { margin-bottom: 20px; }
.mt-sm { margin-top: 5px; }
.mb-none { margin-bottom: 0; }
</style>

<script>
function scheduleReview(teacher_id) {
    if(confirm('Schedule performance review for this teacher?')) {
        $.ajax({
            url: '<?php echo base_url("employee/schedule_review"); ?>',
            type: 'POST',
            data: {teacher_id: teacher_id},
            dataType: 'json',
            success: function(response) {
                if(response.status == 'success') {
                    alert('Review scheduled successfully');
                } else {
                    alert(response.message || 'Error scheduling review');
                }
            },
            error: function() {
                alert('Server error. Please try again.');
            }
        });
    }
}

function sendAlert(teacher_id) {
    if(confirm('Send risk alert to admin?')) {
        $.ajax({
            url: '<?php echo base_url("employee/send_risk_alert"); ?>',
            type: 'POST',
            data: {teacher_id: teacher_id},
            dataType: 'json',
            success: function(response) {
                if(response.status == 'success') {
                    alert('Alert sent successfully');
                    location.reload();
                } else {
                    alert(response.message || 'Error sending alert');
                }
            },
            error: function() {
                alert('Server error. Please try again.');
            }
        });
    }
}
</script>