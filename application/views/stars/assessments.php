<?php
// Get branch ID for filtering
$branch_id = $this->application_model->get_branch_id();
$is_superadmin = is_superadmin_loggedin();

// Define CSRF and base_url for JavaScript
$csrf_token = $this->security->get_csrf_hash();
$base_url = base_url();
?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-exchange-alt"></i> Transfer Assessments - Student Transition & Academic Recovery
                </h4>
                <div class="panel-btn">
                    <div class="btn-group">
                        <!-- Online Application Button -->
                        <a href="<?=base_url('online_admission/index')?>" class="btn btn-circle btn-info">
                            <i class="fas fa-globe"></i> Online Application
                        </a>
                        <!-- Manual Transfer Button (preserves existing flow) -->
                        <a href="<?=base_url('student/add?source=stars_transfer')?>" class="btn btn-circle btn-success">
                            <i class="fas fa-user-plus"></i> Manual Transfer
                        </a>
                    </div>
                </div>
            </header>
            
            <div class="panel-body">
                <!-- ========== ALERT DISPLAY ========== -->
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-check-circle"></i> <?php echo $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($this->session->flashdata('warning')): ?>
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $this->session->flashdata('warning'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>
            <!-- ========== END ALERT DISPLAY ========== -->
            
                
                <!-- ========== BRANCH FILTER (Superadmin Only) ========== -->
                <?php if ($is_superadmin): ?>
                <div class="row mb-lg">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label"><?=translate('branch')?></label>
                            <select class="form-control" id="branchFilter">
                                <option value="">All Branches</option>
                                <?php 
                                $branches = $this->db->order_by('name', 'ASC')->get('branch')->result_array();
                                $selected_branch = $this->session->userdata('selected_branch') ?: '';
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
                                <span class="label label-info"><i class="fas fa-globe"></i> Online</span>
                                <span class="label label-primary"><i class="fas fa-user-edit"></i> Manual</span>
                                <span class="label label-warning">Pending</span>
                                <span class="label label-success">Recovery Active</span>
                                <span class="label label-default">Closed</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- ========== STATUS FILTER (All Users) ========== -->
                <div class="row mb-md">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('status')?></label>
                            <select class="form-control" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="pending">Pending Assessment</option>
                                <option value="assessing">Under Assessment</option>
                                <option value="gap_analysis">Gap Analysis Complete</option>
                                <option value="recovery_plan">Recovery Plan Active</option>
                                <option value="monitoring">Monitoring</option>
                                <option value="closed">Closed</option>
                                <option value="declined">Declined</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('class')?></label>
                            <select class="form-control" id="classFilter">
                                <option value="">All Classes</option>
                                <?php 
                                // Get branch ID with proper isolation
                                $filter_branch_id = null;
                                if (is_superadmin_loggedin()) {
                                    $selected_branch = $this->session->userdata('selected_branch');
                                    if (!empty($selected_branch)) {
                                        $filter_branch_id = $selected_branch;
                                    }
                                } else {
                                    $filter_branch_id = $this->application_model->get_branch_id();
                                }
                                
                                // Query classes only for the filtered branch
                                if (!empty($filter_branch_id)) {
                                    $this->db->where('branch_id', $filter_branch_id);
                                }
                                $classes = $this->db->order_by('name_numeric', 'ASC')->get('class')->result_array();
                                foreach ($classes as $class): 
                                ?>
                                <option value="<?=$class['id']?>"><?=$class['name']?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label"><?=translate('search')?></label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Student name, register no, or guardian...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-default btn-block" id="resetFilters">
                                <i class="fas fa-undo-alt"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- ========== ASSESSMENTS TABLE ========== -->
                <div class="export_title">Transfer Assessments Report - <?=date('d M Y')?></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-export" id="assessmentsTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Student Name</th>
                                <th width="80">Source</th>
                                <th>Guardian</th>
                                <th>Class</th>
                                <th>Transfer Date</th>
                                <th width="120">Status</th>
                                <th width="150">Progress</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assessmentsTableBody">
                            <?php if (empty($assessments)): ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    <div class="alert alert-info mb-none">
                                        <i class="fas fa-info-circle"></i> 
                                        No transfer assessments found.
                                        <br><br>
                                        <a href="<?=base_url('online_admission/index')?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-globe"></i> Start from Online Application
                                        </a>
                                        <a href="<?=base_url('student/add?source=stars_transfer')?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-user-plus"></i> Add Manual Transfer
                                        </a>
                                    </div>
                                 </div>
                                 </td>
                             </tr>
                            <?php else: ?>
                            <?php 
                            $counter = 1;
                            foreach ($assessments as $a): 
                            // Determine status badge color
                            $status_badge = [
                                'pending' => 'warning',
                                'assessing' => 'info',
                                'gap_analysis' => 'primary',
                                'recovery_plan' => 'success',
                                'monitoring' => 'info',
                                'closed' => 'default',
                                'declined' => 'danger'
                            ];
                            $badge = $status_badge[$a['status']] ?? 'default';
                            
                            // Calculate progress percentage
                            $progress_percent = 0;
                            if (isset($a['total_topics']) && $a['total_topics'] > 0) {
                                $progress_percent = round(($a['covered_topics'] / $a['total_topics']) * 100, 1);
                            }
                            ?>
                            <tr data-status="<?=$a['status']?>" data-class="<?=$a['joining_class_id']?>" 
                                data-name="<?=strtolower($a['first_name'] . ' ' . $a['last_name'])?>"
                                data-register="<?=$a['register_no']?>"
                                data-guardian="<?=strtolower($a['guardian_name'])?>">
                                <td><?=$counter++?></td>
                                <td>
                                    <strong><?=html_escape($a['first_name'])?> <?=html_escape($a['last_name'])?></strong><br>
                                    <small class="text-muted"><?=$a['register_no'] ?? 'No reg no'?></small>
                                </td>
                                <td class="text-center">
                                    <?php if (isset($a['is_manual_admission']) && $a['is_manual_admission'] == 1): ?>
                                        <span class="label label-primary"><i class="fas fa-user-edit"></i> Manual</span>
                                    <?php else: ?>
                                        <span class="label label-info"><i class="fas fa-globe"></i> Online</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?=html_escape($a['guardian_name'])?><br>
                                    <small class="text-muted"><?=$a['grd_mobile_no'] ?? $a['mobile_no'] ?? ''?></small>
                                </td>
                                <td>
                                    <?=html_escape($a['class_name'])?> 
                                    <?=html_escape($a['section_name'] ?? '')?>
                                </td>
                                <td><?=date('d M Y', strtotime($a['transfer_date']))?></td>
                                <td>
                                    <span class="label label-<?=$badge?>">
                                        <?=ucfirst(str_replace('_', ' ', $a['status']))?>
                                    </span>
                                    <?php if ($a['status'] == 'recovery_plan' && isset($a['plan_code'])): ?>
                                    <br>
                                    <small class="text-muted">Plan: <?=$a['plan_code']?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                       <!-- DEBUG: Remove after testing -->
                                    <?php if ($progress_percent > 0): ?>
                                    <div class="progress" style="margin-bottom: 0; height: 8px;">
                                        <div class="progress-bar progress-bar-success" role="progressbar" 
                                             style="width: <?=$progress_percent?>%;">
                                        </div>
                                    </div>
                                    <small><?=$progress_percent?>% complete</small>
                                    <?php else: ?>
                                    <small class="text-muted">Not started</small>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <div class="btn-group btn-group-xs">
                                        <!-- Assess Button -->
                                        <a href="<?=base_url('stars/assessment_detail/' . $a['id'])?>" 
                                           class="btn btn-default" title="Assess">
                                            <i class="fas fa-edit"></i> Assess
                                        </a>
                                        
                                        <!-- Gap Report Button (only if gap analysis exists) -->
                                        <?php if (in_array($a['status'], ['gap_analysis', 'recovery_plan', 'monitoring'])): ?>
                                        <a href="<?=base_url('stars/gap_report/' . $a['id'])?>" 
                                           class="btn btn-info" title="Gap Report">
                                            <i class="fas fa-chart-line"></i> Gaps
                                        </a>
                                        <?php endif; ?>
                                        
                                        <!-- Generate IARP Button -->
                                        <?php if (in_array($a['status'], ['gap_analysis', 'assessing'])): ?>
                                        <a href="<?=base_url('stars/generate_iarp/' . $a['id'])?>" 
                                           class="btn btn-success" title="Generate IARP"
                                           onclick="return confirm('Generate IARP for this student?')">
                                            <i class="fas fa-file-medical"></i> IARP
                                        </a>
                                        <?php endif; ?>
                                        
                                        <!-- View IARP Button -->
                                        <?php if (isset($a['iarp_id']) && $a['iarp_id']): ?>
                                        <a href="<?=base_url('stars/view_iarp/' . $a['iarp_id'])?>" 
                                           class="btn btn-primary" title="View IARP">
                                            <i class="fas fa-eye"></i> View Plan
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- ========== PAGINATION (if needed) ========== -->
                <?php if (isset($pagination) && !empty($pagination)): ?>
                <div class="row mt-md">
                    <div class="col-md-12 text-center">
                        <?=$pagination?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </section>
    </div>
</div>

<script>
// Define CSRF and base_url for JavaScript
var base_url = '<?=$base_url?>';
var csrf_token = '<?=$csrf_token?>';

$(document).ready(function() {
    var $tbody = $('#assessmentsTableBody');
    
    // ========== BRANCH FILTER (Superadmin) ==========
    $('#applyBranchFilter').on('click', function() {
        var branchId = $('#branchFilter').val();
        
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
                    alert('Failed to apply branch filter');
                }
            },
            error: function() {
                alert('Failed to apply branch filter');
            }
        });
    });
    
    // ========== STATUS, CLASS, SEARCH FILTERS ==========
    function filterTable() {
        var status = $('#statusFilter').val();
        var classId = $('#classFilter').val();
        var searchTerm = $('#searchInput').val().toLowerCase();
        
        var visibleCount = 0;
        
        $tbody.find('tr').each(function() {
            var $row = $(this);
            var show = true;
            
            // Skip if it's the "no data" row
            if ($row.find('td').length === 1 && $row.find('td').text().indexOf('No transfer') !== -1) {
                return;
            }
            
            // Status filter
            if (status && $row.data('status') !== status) {
                show = false;
            }
            
            // Class filter
            if (classId && $row.data('class') != classId) {
                show = false;
            }
            
            // Search filter
            if (searchTerm) {
                var name = $row.data('name') || '';
                var register = $row.data('register') || '';
                var guardian = $row.data('guardian') || '';
                
                if (name.indexOf(searchTerm) === -1 && 
                    register.indexOf(searchTerm) === -1 && 
                    guardian.indexOf(searchTerm) === -1) {
                    show = false;
                }
            }
            
            if (show) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });
        
        // Show "no results" message if needed
        if (visibleCount === 0 && $tbody.find('tr:visible').length === 0) {
            if ($tbody.find('.no-results-row').length === 0) {
                $tbody.append('<tr class="no-results-row"><td colspan="9" class="text-center text-muted">No matching assessments found</td></tr>');
            }
        } else {
            $tbody.find('.no-results-row').remove();
        }
    }
    
    // Apply filters on change
    $('#statusFilter, #classFilter').on('change', filterTable);
    $('#searchInput').on('keyup', filterTable);
    
    // Reset all filters
    $('#resetFilters').on('click', function() {
        $('#statusFilter').val('');
        $('#classFilter').val('');
        $('#searchInput').val('');
        filterTable();
    });
    
    // Initialize tooltips if function exists
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[title]').tooltip({ placement: 'top' });
    }
});

// Helper function for delete confirmation
function confirmDelete(url) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the assessment and all related data!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    } else {
        if (confirm('Are you sure? This will delete the assessment and all related data!')) {
            window.location.href = url;
        }
    }
    return false;
}
</script>

<style>
.action-buttons .btn-group {
    white-space: nowrap;
}
.action-buttons .btn {
    margin: 2px;
    padding: 3px 8px;
    font-size: 11px;
}
.label {
    font-size: 11px;
    padding: 3px 6px;
}
.progress {
    background-color: #f5f5f5;
    border-radius: 4px;
}
.table-responsive {
    overflow-x: auto;
}
#assessmentsTable th {
    white-space: nowrap;
}
</style>