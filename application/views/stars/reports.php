<?php
// Get branch ID for filtering
$branch_id = $this->application_model->get_branch_id();
$is_superadmin = is_superadmin_loggedin();
$csrf_token = $this->security->get_csrf_hash();
$base_url = base_url();
?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-chart-pie"></i> Recovery Reports
                </h4>
                <div class="panel-btn">
                    <a href="<?=base_url('stars/export_report?format=csv')?>" class="btn btn-circle btn-default">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                </div>
            </header>
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
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <i class="fas fa-file-alt fa-3x text-primary"></i>
                                <h3><?=$stats['total']?></h3>
                                <p>Total IARPs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <i class="fas fa-play-circle fa-3x text-success"></i>
                                <h3><?=$stats['active']?></h3>
                                <p>Active Plans</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <i class="fas fa-check-circle fa-3x text-info"></i>
                                <h3><?=$stats['completed']?></h3>
                                <p>Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <i class="fas fa-chart-line fa-3x text-warning"></i>
                                <h3><?=array_sum($severity_stats)?></h3>
                                <p>Total Gaps</p>
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
                                <canvas id="severityChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h4 class="panel-title">Monthly Recoveries Completed</h4>
                            </div>
                            <div class="panel-body">
                                <?php if (empty($monthly_closures)): ?>
                                <div class="alert alert-info text-center">No data available</div>
                                <?php else: ?>
                                <canvas id="monthlyChart" height="200"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Closures Table with Export -->
                <div class="row mt-lg">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <h4 class="panel-title">Recent Recovery Closures</h4>
                            </div>
                            <div class="panel-body">
                                <div class="export_title">STARS Recovery Report - <?=date('d M Y')?></div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-export">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Student Name</th>
                                                <th>Register No</th>
                                                <th>Class</th>
                                                <th>Plan Code</th>
                                                <th class="text-center">Total</th>
                                                <th class="text-center">Mastered Content</th>
                                                <th class="text-center">Rate</th>
                                                <th>Status</th>
                                                <th>Closure Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recent_closures)): ?>
                                                <?php $counter = 1; foreach ($recent_closures as $closure): ?>
                                                <tr>
                                                    <td class="text-center"><?=$counter++?></td>
                                                    <td><?=html_escape($closure['first_name'] ?? '')?> <?=html_escape($closure['last_name'] ?? '')?></td>
                                                    <td><?=html_escape($closure['register_no'] ?? '-')?></td>
                                                    <td><?=html_escape($closure['class_name'] ?? '-')?></td>
                                                    <td><?=html_escape($closure['plan_code'] ?? '-')?></td>
                                                    <td class="text-center"><?=($closure['total_topics'] ?? 0)?></td>
                                                    <td>
                                                        <?php 
                                                        $mastered_list = $closure['mastered_topics_list'] ?? array();
                                                        if (!empty($mastered_list)): 
                                                        ?>
                                                            <ul class="mastered-list">
                                                            <?php foreach ($mastered_list as $item): ?>
                                                                <li><i class="fas fa-check-circle text-success"></i> <?=html_escape($item)?></li>
                                                            <?php endforeach; ?>
                                                            </ul>
                                                        <?php else: ?>
                                                            <span class="text-muted">None</span>
                                                        <?php endif; ?>
                                                        </td>
                                                    <td class="text-center" style="width: 100px;">
                                                        <?php 
                                                        $total = $closure['total_topics'] ?? 0;
                                                        $mastered = $closure['topics_mastered'] ?? 0;
                                                        $rate = ($total > 0) ? round(($mastered / $total) * 100, 1) : 0;
                                                        ?>
                                                        <div class="progress" style="margin-bottom: 0; height: 6px;">
                                                            <div class="progress-bar progress-bar-success" role="progressbar" 
                                                                 style="width: <?=$rate?>%;">
                                                            </div>
                                                        </div>
                                                        <small><?=$rate?>%</small>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php 
                                                        $status = $closure['status'] ?? 'completed';
                                                        $status_class = 'success';
                                                        if ($status == 'active') {
                                                            $status_class = 'warning';
                                                        } elseif ($status == 'draft') {
                                                            $status_class = 'default';
                                                        } elseif ($status == 'completed') {
                                                            $status_class = 'success';
                                                        }
                                                        ?>
                                                        <span class="label label-<?=$status_class?>">
                                                            <?=strtoupper(html_escape($status))?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center"><?=date('d M Y', strtotime($closure['closure_date'] ?? 'now'))?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="10" class="text-center text-muted">No closure records found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var base_url = '<?=$base_url?>';
var csrf_token = '<?=$csrf_token?>';

$(document).ready(function() {
    // Severity Chart
    var severityCtx = document.getElementById('severityChart').getContext('2d');
    new Chart(severityCtx, {
        type: 'doughnut',
        data: {
            labels: ['Low Gap (Green)', 'Medium Gap (Amber)', 'Critical Gap (Red)'],
            datasets: [{
                data: [<?=$severity_stats['green']?>, <?=$severity_stats['amber']?>, <?=$severity_stats['red']?>],
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
    
    <?php if (!empty($monthly_closures)): ?>
    // Monthly Chart
    var monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    var months = <?=json_encode(array_column($monthly_closures, 'month'))?>;
    var counts = <?=json_encode(array_column($monthly_closures, 'count'))?>;
    
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Recoveries Completed',
                data: counts,
                backgroundColor: '#3498db',
                borderColor: '#2980b9',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: { 
                    beginAtZero: true, 
                    stepSize: 1,
                    title: { display: true, text: 'Number of Recoveries' }
                },
                x: { 
                    title: { display: true, text: 'Month' }
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if ($is_superadmin): ?>
    // Branch filter
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
.table-export th,
.table-export td {
    vertical-align: middle;
}
.progress {
    background-color: #f5f5f5;
    border-radius: 4px;
}
.progress-bar {
    border-radius: 4px;
}
.export_title {
    display: none;
}
@media print {
    .export_title {
        display: block;
        text-align: center;
        margin-bottom: 20px;
        font-size: 18px;
        font-weight: bold;
    }
}
.card-hover {
    transition: transform 0.2s;
}
.card-hover:hover {
    transform: translateY(-5px);
}
.label {
    font-size: 11px;
    padding: 3px 6px;
}
</style>