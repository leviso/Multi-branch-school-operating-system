

<?php
// Validate transfer_id
$transfer_id = $transfer_id ?? 0;
$csrf_token = $this->security->get_csrf_hash();
$base_url = base_url();
$branch_id = $this->application_model->get_branch_id();
$can_edit = get_permission('stars', 'is_edit');

// Check if assessment is closed or declined
$is_readonly = false;
$readonly_message = '';

if ($assessment['status'] == 'closed') {
    $is_readonly = true;
    $readonly_message = 'This assessment is CLOSED. Recovery plan has been completed. View only.';
} elseif ($assessment['status'] == 'declined') {
    $is_readonly = true;
    $readonly_message = 'This assessment is DECLINED. View only.';
} elseif ($assessment['status'] == 'recovery_plan') {
    // Check if IARP is completed
    $this->db->select('status');
    $this->db->where('transfer_assessment_id', $assessment['id']);
    $iarp = $this->db->get('iarp_plans')->row_array();
    if ($iarp && $iarp['status'] == 'completed') {
        $is_readonly = true;
        $readonly_message = 'Recovery plan is COMPLETED. This assessment is now closed.';
    }
}

// Get closure details if closed
$closure = null;
if ($assessment['status'] == 'closed') {
    $this->db->select('cl.*, st.name as closed_by_name');
    $this->db->from('recovery_closure_logs cl');
    $this->db->join('staff st', 'st.id = cl.closed_by', 'left');
    $this->db->where('cl.student_id', $assessment['student_id']);
    $this->db->order_by('cl.closure_date', 'DESC');
    $this->db->limit(1);
    $closure = $this->db->get()->row_array();
}
?>

<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title">
            <i class="fas fa-clipboard-list"></i> 
            Transfer Assessment: <?=html_escape($assessment['first_name'])?> <?=html_escape($assessment['last_name'])?>
        </h4>
    </header>
    
    <div class="panel-body">
        <!-- Student & Assessment Details -->
<div class="alert alert-info">
    <div class="row">
        <div class="col-md-6">
            <strong>Student:</strong> 
            <?php 
            if (!empty($assessment['first_name'])) {
                echo html_escape($assessment['first_name'] . ' ' . ($assessment['last_name'] ?? '')) . ' (' . html_escape($assessment['register_no'] ?? 'N/A') . ')';
            } else {
                echo 'Student not found';
            }
            ?>
            <br>
            <strong>Class:</strong> <?php echo html_escape($assessment['class_name'] ?? 'Not assigned'); ?>
            <br>
            <strong>Assessment Status:</strong> 
            <span class="label label-<?php 
                $status_class = 'warning';
                if ($assessment['status'] == 'closed') $status_class = 'default';
                if ($assessment['status'] == 'declined') $status_class = 'danger';
                if ($assessment['status'] == 'recovery_plan') $status_class = 'success';
                echo $status_class;
            ?>">
                <?php echo strtoupper(html_escape($assessment['status'] ?? 'PENDING')); ?>
            </span>
            <br>
            <strong>Guardian:</strong> <?php echo html_escape($assessment['guardian_name'] ?? 'Not available'); ?>
            <br>
            <strong>Mobile:</strong> <?php echo html_escape($assessment['grd_mobile_no'] ?? $assessment['mobile_no'] ?? 'N/A'); ?>
        </div>
        <div class="col-md-6">
            <strong>Transfer Date:</strong> 
            <?php 
            if (!empty($assessment['transfer_date']) && $assessment['transfer_date'] != '0000-00-00') {
                echo date('d M Y', strtotime($assessment['transfer_date']));
            } else {
                echo 'Not set';
            }
            ?>
            <br>
            <strong>Source:</strong> 
            <?php 
            if (isset($assessment['is_manual_admission']) && $assessment['is_manual_admission'] == 1) {
                echo '<span class="label label-primary">Manual Entry</span>';
            } else {
                echo '<span class="label label-info">Online Application</span>';
            }
            ?>
        </div>
    </div>
</div>
                
        <!-- Readonly Banner -->
        <?php if ($is_readonly): ?>
        <div class="alert alert-warning mb-md">
            <i class="fas fa-lock"></i> 
            <strong><?=$readonly_message?></strong>
            <br><small>No changes can be made to this assessment.</small>
        </div>
        <?php endif; ?>
        
        <!-- Completion Summary -->
        <?php if ($closure): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            <strong>Recovery Completed</strong><br>
            Closed on: <?=date('d M Y', strtotime($closure['closure_date']))?><br>
            Closed by: <?=html_escape($closure['closed_by_name'])?><br>
            Final Gap: <?=$closure['final_gap_percentage']?>% | Topics Mastered: <?=$closure['topics_mastered']?>/<?=$closure['total_topics']?>
        </div>
        <?php endif; ?>
        
        <!-- Topic Coverage Form -->
        <?php echo form_open('stars/save_coverage', ['id' => 'coverageForm']); ?>
        <input type="hidden" name="transfer_id" value="<?=$transfer_id?>">
        <input type="hidden" name="csrf_test_name" value="<?=$csrf_token?>">
        
        <div class="headers-line">
            <i class="fas fa-book"></i> Curriculum Coverage Assessment
            <small class="text-muted">Mark what the student already knows from previous school</small>
        </div>
        
        <?php 
        $current_subject = '';
        if (!empty($syllabus)):
            foreach ($syllabus as $topic): 
                $covered = isset($covered_topics[$topic['id']]);
                $status = $covered ? $covered_topics[$topic['id']]['coverage_status'] : '';
        ?>
            <?php if ($current_subject != $topic['subject_name']): ?>
                <?php if ($current_subject != ''): ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <div class="panel panel-default mt-md">
                    <div class="panel-heading">
                        <strong><?=html_escape($topic['subject_name'])?></strong>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Topic</th>
                                        <th width="200">Coverage Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                <?php $current_subject = $topic['subject_name']; ?>
            <?php endif; ?>
            
            <tr>
                <td><?=html_escape($topic['topic_name'])?></td>
                <td>
                    <select name="coverage[<?=$topic['id']?>]" 
                            class="form-control coverage-select" 
                            data-topic-id="<?=$topic['id']?>" 
                            <?=$is_readonly ? 'disabled' : ''?>>
                        <option value="unknown" <?=$status == 'unknown' ? 'selected' : ''?>>Not Assessed</option>
                        <option value="mastered" <?=$status == 'mastered' ? 'selected' : ''?>>✅ Mastered</option>
                        <option value="partial" <?=$status == 'partial' ? 'selected' : ''?>>⚠️ Partial Knowledge</option>
                        <option value="not_covered" <?=$status == 'not_covered' ? 'selected' : ''?>>❌ Not Covered</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="notes[<?=$topic['id']?>]" 
                           class="form-control" 
                           value="<?=$covered ? htmlspecialchars($covered_topics[$topic['id']]['assessment_notes']) : ''?>"
                           placeholder="Optional notes"
                           <?=$is_readonly ? 'readonly' : ''?>>
                </td>
            </tr>
        <?php 
            endforeach; 
        else: 
        ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                No curriculum topics found for this class. 
                <a href="<?=base_url('stars/manage_topics')?>" class="alert-link">Please add topics first.</a>
            </div>
        <?php endif; ?>
        
        <?php if ($current_subject != ''): ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <footer class="panel-footer">
            <div class="pull-right">
                <?php if (!$is_readonly && $can_edit): ?>
                    <button type="button" class="btn btn-info" id="saveCoverageBtn">
                        <i class="fas fa-save"></i> Save Progress
                    </button>
                    <button type="button" class="btn btn-success" id="runGapAnalysisBtn">
                        <i class="fas fa-chart-line"></i> Run Gap Analysis
                    </button>
                <?php elseif ($is_readonly): ?>
                    <span class="text-muted">
                        <i class="fas fa-lock"></i> This assessment is closed and cannot be edited.
                    </span>
                <?php endif; ?>
            </div>
            <div class="clearfix"></div>
        </footer>
        <?php echo form_close(); ?>
    </div>
</section>

<script>
var base_url = '<?=$base_url?>';
var csrf_token = '<?=$csrf_token?>';
var can_edit = <?=$can_edit ? 'true' : 'false'?>;
var is_readonly = <?=$is_readonly ? 'true' : 'false'?>;

// Helper function for notifications
function showNotification(message, type) {
    if (typeof toastr !== 'undefined') {
        if (type === 'success') toastr.success(message);
        else if (type === 'error') toastr.error(message);
        else toastr.info(message);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({ title: message, icon: type, timer: 2000, showConfirmButton: false });
    } else {
        alert(message);
    }
}

$(document).ready(function() {
    if (is_readonly || !can_edit) {
        return; // Exit if readonly
    }
    
    // Save coverage via AJAX
    $('.coverage-select').on('change', function() {
        var $select = $(this);
        var topicId = $select.data('topic-id');
        var status = $select.val();
        var notes = $('input[name="notes[' + topicId + ']"]').val();
        
        // Show loading indicator
        $select.css('opacity', '0.6');
        
        $.ajax({
            url: base_url + 'stars/save_coverage',
            type: 'POST',
            data: {
                transfer_id: <?=$transfer_id?>,
                topic_id: topicId,
                status: status,
                notes: notes,
                csrf_test_name: csrf_token
            },
            dataType: 'json',
            success: function(response) {
                $select.css('opacity', '1');
                if (response.success) {
                    // Visual feedback
                    $select.css('border-color', '#2ecc71');
                    setTimeout(function() {
                        $select.css('border-color', '');
                    }, 500);
                }
            },
            error: function(xhr) {
                $select.css('opacity', '1');
                if (xhr.status === 403) {
                    alert('Session expired. Please refresh the page.');
                }
            }
        });
    });
    
    // Run Gap Analysis
    $('#runGapAnalysisBtn').click(function() {
        var transferId = $('input[name="transfer_id"]').val();
        
        if (!transferId) {
            alert('Transfer ID not found');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving & Analyzing...');
        
        var savePromises = [];
        
        $('.coverage-select').each(function() {
            var $select = $(this);
            var topicId = $select.data('topic-id');
            var status = $select.val();
            var notes = $('input[name="notes[' + topicId + ']"]').val();
            
            var promise = $.ajax({
                url: base_url + 'stars/save_coverage',
                type: 'POST',
                data: {
                    transfer_id: transferId,
                    topic_id: topicId,
                    status: status,
                    notes: notes,
                    csrf_test_name: csrf_token
                },
                dataType: 'json'
            });
            savePromises.push(promise);
        });
        
        if (savePromises.length === 0) {
            window.location.href = base_url + 'stars/run_gap_analysis/' + transferId;
            return;
        }
        
        $.when.apply($, savePromises).done(function() {
            window.location.href = base_url + 'stars/run_gap_analysis/' + transferId;
        }).fail(function() {
            alert('Failed to save some topics. Please try again.');
            $btn.prop('disabled', false).html(originalText);
        });
    });
    
    // Save Progress button
    $('#saveCoverageBtn').click(function() {
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        var savePromises = [];
        $('.coverage-select').each(function() {
            var $select = $(this);
            var topicId = $select.data('topic-id');
            var status = $select.val();
            var notes = $('input[name="notes[' + topicId + ']"]').val();
            
            var promise = $.ajax({
                url: base_url + 'stars/save_coverage',
                type: 'POST',
                data: {
                    transfer_id: <?=$transfer_id?>,
                    topic_id: topicId,
                    status: status,
                    notes: notes,
                    csrf_test_name: csrf_token
                },
                dataType: 'json'
            });
            savePromises.push(promise);
        });
        
        $.when.apply($, savePromises).done(function() {
            $btn.prop('disabled', false).html(originalText);
            showNotification('All topics saved successfully', 'success');
        }).fail(function() {
            $btn.prop('disabled', false).html(originalText);
            alert('Failed to save some topics. Please try again.');
        });
    });
});
</script>