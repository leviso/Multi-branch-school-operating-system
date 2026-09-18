<?php
// Get branch ID for filtering
$branch_id = $this->application_model->get_branch_id();
$is_superadmin = is_superadmin_loggedin();
$csrf_token = $this->security->get_csrf_hash();
$base_url = base_url();
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-chart-line"></i> STARS Dashboard - Student Transition & Academic Recovery
                </h4>
            </div>
            <div class="panel-body">
                
                <!-- Branch Filter (Superadmin Only) -->
                <?php if ($is_superadmin): ?>
                <div class="row mb-lg">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label"><?=translate('branch')?></label>
                            <select class="form-control" id="branchFilter">
                                <option value="">All Branches</option>
                                <?php 
                                $branches = $this->db->order_by('name', 'ASC')->get('branch')->result_array();
                                $selected_branch = $this->session->userdata('selected_branch');
                                foreach ($branches as $branch): 
                                ?>
                                <option value="<?=$branch['id']?>" <?=($selected_branch == $branch['id']) ? 'selected' : ''?>>
                                    <?=html_escape($branch['school_name'])?> (<?=html_escape($branch['name'])?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block" id="applyBranchFilter">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <span class="label label-info">All Branches Mode</span>
                                <?php if (!empty($selected_branch)): ?>
                                <span class="label label-success">Filtered: <?=html_escape(get_type_name_by_id('branch', $selected_branch, 'school_name'))?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <a href="<?=base_url('stars/assessments?status=pending')?>" class="card-hover-link">
                            <div class="panel panel-default card-hover">
                                <div class="panel-body text-center">
                                    <i class="fas fa-user-plus fa-3x text-primary"></i>
                                    <h3 class="mt-sm"><?=$stats['pending_assessments']?></h3>
                                    <p class="text-muted">Pending Assessments</p>
                                    <span class="label label-primary">View</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?=base_url('stars/active_iarp')?>" class="card-hover-link">
                            <div class="panel panel-default card-hover">
                                <div class="panel-body text-center">
                                    <i class="fas fa-chalkboard-teacher fa-3x text-warning"></i>
                                    <h3 class="mt-sm"><?=$stats['active_recoveries']?></h3>
                                    <p class="text-muted">Active Recoveries</p>
                                    <span class="label label-warning">View</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default card-hover">
                            <div class="panel-body text-center">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                                <h3 class="mt-sm"><?=$stats['gap_summary']['green']?></h3>
                                <p class="text-muted">Low Gap (Green)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default card-hover">
                            <div class="panel-body text-center">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                                <h3 class="mt-sm"><?=$stats['gap_summary']['red']?></h3>
                                <p class="text-muted">Critical Gap (Red)</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gap Severity Chart -->
                <div class="row mt-lg">
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h4 class="panel-title">Gap Severity Distribution</h4>
                            </div>
                            <div class="panel-body">
                                <canvas id="gapChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h4 class="panel-title">At-Risk Students (Red Alert)</h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Register No</th>
                                                <th>Student Name</th>
                                                <th>Class</th>
                                                <th>Gap %</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($stats['at_risk_students'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No at-risk students</td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($stats['at_risk_students'] as $student): ?>
                                            <tr>
                                                <td><?=html_escape($student['register_no'])?></td>
                                                <td><?=html_escape($student['first_name'])?> <?=html_escape($student['last_name'])?></td>
                                                <td><?=html_escape($student['class_name'])?></td>
                                                <td>
                                                    <span class="label label-danger"><?=round($student['gap_percentage'], 1)?>%</span>
                                            </td>
                                                <td>
                                                    <a href="<?=base_url('stars/gap_report/' . $student['transfer_assessment_id'])?>" class="btn btn-xs btn-info">
                                                        <i class="fas fa-chart-line"></i> View Gaps
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mt-lg">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <h4 class="panel-title">Quick Actions</h4>
                            </div>
                            <div class="panel-body">
                                <div class="btn-group">
                                    <a href="<?=base_url('stars/assessments')?>" class="btn btn-primary">
                                        <i class="fas fa-list"></i> All Assessments
                                    </a>
                                    <a href="<?=base_url('stars/active_iarp')?>" class="btn btn-warning">
                                        <i class="fas fa-file-medical"></i> Active Plans
                                    </a>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-user-plus"></i> New Transfer Admission <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li>
                                                <a href="<?=base_url('online_admission/index')?>">
                                                    <i class="fas fa-globe"></i> Online Application
                                                </a>
                                            </li>
                                            <li>
                                                <a href="<?=base_url('student/add?source=stars_transfer')?>">
                                                    <i class="fas fa-user-edit"></i> Manual Transfer
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <a href="<?=base_url('stars/manage_topics')?>" class="btn btn-info">
                                        <i class="fas fa-book"></i> Manage Curriculum Topics
                                    </a>
                                    <a href="<?=base_url('stars/reports')?>" class="btn btn-default">
                                        <i class="fas fa-chart-pie"></i> Reports
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var base_url = '<?=$base_url?>';
var csrf_token = '<?=$csrf_token?>';

$(document).ready(function() {
    // Gap Severity Chart
    var ctx = document.getElementById('gapChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Low Gap (Green)', 'Medium Gap (Amber)', 'Critical Gap (Red)'],
            datasets: [{
                data: [<?=$stats['gap_summary']['green']?>, <?=$stats['gap_summary']['amber']?>, <?=$stats['gap_summary']['red']?>],
                backgroundColor: ['#2ecc71', '#f39c12', '#e74c3c'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    
    <?php if ($is_superadmin): ?>
    // Branch filter for superadmin
    $('#applyBranchFilter').click(function() {
        var branchId = $('#branchFilter').val();
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Applying...');
        
        $.ajax({
            url: base_url + 'stars/set_branch_filter',
            type: 'POST',
            data: { 
                branch_id: branchId, 
                csrf_test_name: csrf_token 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to apply filter');
                    $btn.prop('disabled', false).html('<i class="fas fa-filter"></i> Filter');
                }
            },
            error: function() {
                alert('Error applying filter');
                $btn.prop('disabled', false).html('<i class="fas fa-filter"></i> Filter');
            }
        });
    });
    <?php endif; ?>
});
</script>

<style>
.card-hover {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.card-hover-link {
    text-decoration: none !important;
    color: inherit;
    display: block;
}
.card-hover-link:hover {
    text-decoration: none !important;
}
.label {
    font-size: 11px;
    padding: 3px 6px;
}
.progress {
    background-color: #f5f5f5;
    border-radius: 4px;
}
.btn-group .btn {
    margin-right: 5px;
}
</style>