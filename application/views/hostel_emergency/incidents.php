<?php if($this->session->flashdata('msg')): ?>
    <?php echo $this->session->flashdata('msg'); ?>
<?php endif; ?>
<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fa fa-exclamation-triangle"></i> <?=translate('emergency_incidents')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php if(get_permission('hostel_emergency', 'can_add')): ?>
                        <a href="<?=base_url('hostel_emergency/report?branch_id=' . ($selected_branch_id ?? $branch_id ?? ''))?>" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> <?=translate('report_incident')?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <!-- Statistics Cards -->
                <?php if(isset($stats)): ?>
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="small-box" style="background-color: #dd4b39; color: #fff; border-radius: 3px; padding: 15px;">
                            <div class="inner">
                                <h3><?php echo $stats['critical'] ?? 0; ?></h3>
                                <p><?=translate('critical_incidents')?></p>
                            </div>
                            <div class="icon"><i class="fa fa-bell"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box" style="background-color: #f39c12; color: #fff; border-radius: 3px; padding: 15px;">
                            <div class="inner">
                                <h3><?php echo $stats['reported'] ?? 0; ?></h3>
                                <p><?=translate('active_incidents')?></p>
                            </div>
                            <div class="icon"><i class="fa fa-clock-o"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box" style="background-color: #00a65a; color: #fff; border-radius: 3px; padding: 15px;">
                            <div class="inner">
                                <h3><?php echo $stats['resolved'] ?? 0; ?></h3>
                                <p><?=translate('resolved_incidents')?></p>
                            </div>
                            <div class="icon"><i class="fa fa-check-circle"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box" style="background-color: #3c8dbc; color: #fff; border-radius: 3px; padding: 15px;">
                            <div class="inner">
                                <h3><?php echo $stats['total'] ?? 0; ?></h3>
                                <p><?=translate('total_incidents')?></p>
                            </div>
                            <div class="icon"><i class="fa fa-list"></i></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Superadmin Branch Filter -->
                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?=translate('branch')?></label>
                            <select id="branch_filter" class="form-control">
                                <option value=""><?=translate('all_branches')?></option>
                                <?php foreach($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" 
                                        <?php echo (isset($selected_branch_id) && $selected_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                        <?php echo html_escape($branch['school_name'] . ' (' . $branch['name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-3">
                        <select id="status_filter" class="form-control">
                            <option value="all"><?=translate('all_status')?></option>
                            <option value="reported"><?=translate('reported')?></option>
                            <option value="investigating"><?=translate('investigating')?></option>
                            <option value="resolved"><?=translate('resolved')?></option>
                            <option value="closed"><?=translate('closed')?></option>
                            <option value="false_alarm"><?=translate('false_alarm')?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="severity_filter" class="form-control">
                            <option value="all"><?=translate('all_severity')?></option>
                            <option value="critical"><?=translate('critical')?></option>
                            <option value="high"><?=translate('high')?></option>
                            <option value="medium"><?=translate('medium')?></option>
                            <option value="low"><?=translate('low')?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button id="apply_filters" class="btn btn-default"><?=translate('apply')?></button>
                        <button id="reset_filters" class="btn btn-default"><?=translate('reset')?></button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('incident_code')?></th>
                                <th><?=translate('type')?></th>
                                <th><?=translate('title')?></th>
                                <th><?=translate('student')?></th>
                                <th><?=translate('severity')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('reported_at')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach($incidents as $incident): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                                    <td>
                                        <?php 
                                        // Display branch name or school name
                                        $branch_display = !empty($incident['branch_name']) ? $incident['branch_name'] : 
                                                        (!empty($incident['school_name']) ? $incident['school_name'] : 'N/A');
                                        echo html_escape($branch_display);
                                        ?>
                                    </td>
                                    <?php endif; ?>
                                    <td><span class="label label-info"><?php echo $incident['incident_code']; ?></span></td>
                                    <td>
                                        <i class="fa <?php echo $incident['icon'] ?? 'fa-exclamation-circle'; ?>"></i>
                                        <?php echo html_escape($incident['emergency_type']); ?>
                                    </td>
                                    <td><?php echo html_escape($incident['title']); ?></td>
                                    
                                    <!-- STUDENT COLUMN - FIXED FOR MULTIPLE STUDENTS -->
                                    <td>
                                        <?php if($incident['student_id']): ?>
                                            <?php 
                                            $student_ids = explode(',', $incident['student_id']);
                                            $student_names = array();
                                            foreach($student_ids as $sid) {
                                                $student = $this->db->select('first_name, last_name, register_no')
                                                    ->where('id', trim($sid))
                                                    ->get('student')
                                                    ->row();
                                                if($student) {
                                                    $student_names[] = $student->first_name . ' ' . $student->last_name . '<br><small>(' . $student->register_no . ')</small>';
                                                }
                                            }
                                            echo implode('<br>', $student_names);
                                            ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <!-- END OF STUDENT COLUMN -->
                                    
                                    <td>
                                        <?php
                                        $severity_class = [
                                            'critical' => 'danger',
                                            'high' => 'warning',
                                            'medium' => 'info',
                                            'low' => 'success'
                                        ];
                                        ?>
                                        <span class="label label-<?php echo $severity_class[$incident['severity']] ?? 'default'; ?>">
                                            <?php echo ucfirst(translate($incident['severity'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'reported' => 'danger',
                                            'investigating' => 'warning',
                                            'resolved' => 'success',
                                            'closed' => 'default',
                                            'false_alarm' => 'info'
                                        ];
                                        ?>
                                        <span class="label label-<?php echo $status_class[$incident['status']] ?? 'default'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', translate($incident['status']))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y H:i', strtotime($incident['reported_at'])); ?></td>
                                    <td>
                                        <a href="<?=base_url('hostel_emergency/view/' . $incident['id']); ?>" 
                                        class="btn btn-default btn-circle icon">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            
                            
                            <?php if(empty($incidents)): ?>
                            <tr>
                                <td colspan="<?php echo (is_superadmin_loggedin() && isset($branches)) ? '10' : '9'; ?>" class="text-center"><?=translate('no_records_found')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var csrf_token = '<?=$this->security->get_csrf_hash();?>';

$(document).ready(function() {
    $('#apply_filters').click(function() {
        var status = $('#status_filter').val();
        var severity = $('#severity_filter').val();
        var branch = $('#branch_filter').val();
        var url = base_url + 'hostel_emergency/incidents';
        var params = [];
        
        if (status != 'all') params.push('status=' + status);
        if (severity != 'all') params.push('severity=' + severity);
        if (branch) params.push('branch_id=' + branch);
        
        if (params.length > 0) {
            window.location.href = url + '?' + params.join('&');
        } else {
            window.location.href = url;
        }
    });
    
    $('#reset_filters').click(function() {
        window.location.href = base_url + 'hostel_emergency/incidents';
    });
    
    $('#branch_filter').change(function() {
        $('#apply_filters').click();
    });
});
</script>