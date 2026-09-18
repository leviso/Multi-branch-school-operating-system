<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-exclamation-triangle"></i> Emergency Incidents
            <small>Manage hostel emergencies</small>
        </h1>
    </section>

    <section class="content">
        <!-- Stats Boxes -->
        <div class="row">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3><?php echo $stats['critical'] ?? 0; ?></h3>
                        <p>Critical Incidents</p>
                    </div>
                    <div class="icon"><i class="fa fa-bell"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3><?php echo $stats['reported'] ?? 0; ?></h3>
                        <p>Active Incidents</p>
                    </div>
                    <div class="icon"><i class="fa fa-clock-o"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3><?php echo $stats['resolved'] ?? 0; ?></h3>
                        <p>Resolved</p>
                    </div>
                    <div class="icon"><i class="fa fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-blue">
                    <div class="inner">
                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                        <p>Total Incidents</p>
                    </div>
                    <div class="icon"><i class="fa fa-list"></i></div>
                </div>
            </div>
        </div>

        <!-- Add Incident Button -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Incident List</h3>
                        <div class="box-tools pull-right">
                            <a href="<?php echo base_url('admin/hostel_emergency/report'); ?>" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Report New Incident
                            </a>
                        </div>
                    </div>
                    <div class="box-body">
                        <!-- Filters -->
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-3">
                                <select id="status_filter" class="form-control">
                                    <option value="all">All Status</option>
                                    <option value="reported">Reported</option>
                                    <option value="investigating">Investigating</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="severity_filter" class="form-control">
                                    <option value="all">All Severity</option>
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button id="apply_filters" class="btn btn-default">Apply Filters</button>
                                <button id="reset_filters" class="btn btn-default">Reset</button>
                            </div>
                        </div>

                        <!-- Incidents Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Student</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Reported</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($incidents as $incident): ?>
                                    <tr>
                                        <td>
                                            <span class="label label-info"><?php echo $incident['incident_code']; ?></span>
                                        </td>
                                        <td>
                                            <i class="fa <?php echo $incident['icon'] ?? 'fa-exclamation-circle'; ?>"></i>
                                            <?php echo $incident['emergency_type']; ?>
                                        </td>
                                        <td><?php echo $incident['title']; ?></td>
                                        <td>
                                            <?php if($incident['student_id']): ?>
                                                <?php echo $incident['first_name'] . ' ' . $incident['last_name']; ?>
                                                <br><small><?php echo $incident['register_no']; ?></small>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
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
                                                <?php echo ucfirst($incident['severity']); ?>
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
                                                <?php echo ucfirst(str_replace('_', ' ', $incident['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d M Y H:i', strtotime($incident['reported_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo base_url('admin/hostel_emergency/view/' . $incident['id']); ?>" 
                                               class="btn btn-xs btn-primary">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if(empty($incidents)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No incidents found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#apply_filters').click(function() {
        var status = $('#status_filter').val();
        var severity = $('#severity_filter').val();
        var url = '<?php echo base_url("admin/hostel_emergency/incidents"); ?>';
        
        if (status != 'all') url += '?status=' + status;
        if (severity != 'all') url += (status != 'all' ? '&' : '?') + 'severity=' + severity;
        
        window.location.href = url;
    });
    
    $('#reset_filters').click(function() {
        window.location.href = '<?php echo base_url("admin/hostel_emergency/incidents"); ?>';
    });
});
</script>