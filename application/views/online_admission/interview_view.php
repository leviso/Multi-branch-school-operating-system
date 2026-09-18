<!-- application/views/online_admission/interview_view.php -->

<div class="row mb-3">
    <div class="col-md-12">
        <div class="btn-group" role="group">
            <a href="<?=base_url('online_admission/interviews')?>" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> Back to Interviews
            </a>
            <a href="<?=base_url('online_admission/approved/' . $interview['admission_id'])?>" class="btn btn-info">
                <i class="fas fa-user-graduate"></i> View Admission
            </a>
            <a href="<?=base_url('online_admission')?>" class="btn btn-default">
                <i class="fas fa-list"></i> All Admissions
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title">
                            <i class="fas fa-user-graduate"></i> Interview Details
                        </h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?=base_url('online_admission/interviews')?>" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Interviews
                        </a>
                    </div>
                </div>
            </header>
            
            <div class="panel-body">
                <!-- Status Alert -->
                <?php 
                // Show warning ONLY when interview is completed but admission status is NOT 5
                // AND admission is NOT already approved/declined (2/3)
                if ($interview['status'] == 'completed' && 
                    $interview['admission_status'] != 5 && 
                    !in_array($interview['admission_status'], [2, 3])): 
                ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Workflow Incomplete:</strong> Interview is marked as completed but system didn't update admission status automatically.
                    <button class="btn btn-xs btn-warning pull-right" onclick="fixAdmissionStatusSingle(<?= $interview['id'] ?>, <?= $interview['admission_id'] ?>)">
                        <i class="fas fa-sync-alt"></i> Update to Status 5
                    </button>
                </div>
                <?php endif; ?>

                <?php 
                // Show info when interview completed AND admission status is 5 (ready for decision)
                if ($interview['status'] == 'completed' && $interview['admission_status'] == 5): 
                ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Ready for Decision:</strong> Interview completed. Admission is now ready for final approval/decline decision.
                </div>
                <?php endif; ?>
                
                <!-- Basic Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-user"></i> Student Information</h4>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Student Name</th>
                                        <td><?= $interview['first_name'] . ' ' . $interview['last_name'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Parent/Guardian</th>
                                        <td><?= $interview['guardian_name'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Contact Number</th>
                                        <td>
                                            <?= $interview['grd_mobile_no'] ?: $interview['mobile_no'] ?>
                                            <?php if ($interview['grd_mobile_no']): ?>
                                                <br><small class="text-muted">Guardian's Number</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?= $interview['email'] ?: $interview['grd_email'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Class Applied</th>
                                        <td><?= $interview['class_name'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Application Status</th>
                                        <td>
                                            <?php 
                                            switch($interview['admission_status']) {
                                                case 1: echo '<span class="badge badge-secondary">Pending</span>'; break;
                                                case 2: echo '<span class="badge badge-success">Approved</span>'; break;
                                                case 3: echo '<span class="badge badge-danger">Declined</span>'; break;
                                                case 4: echo '<span class="badge badge-info">Interview Scheduled</span>'; break;
                                                case 5: echo '<span class="badge badge-warning">Interview Completed</span>'; break;
                                                default: echo '<span class="badge badge-secondary">Unknown</span>';
                                            }
                                            ?>
                                            <?php if ($interview['status'] == 'completed' && $interview['admission_status'] != 5): ?>
                                                <br><small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> Needs update to "Interview Completed"
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-calendar-alt"></i> Interview Details</h4>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Interview Date</th>
                                        <td><?= date('d M Y', strtotime($interview['interview_date'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Interview Time</th>
                                        <td><?= date('h:i A', strtotime($interview['interview_time'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Interview Type</th>
                                        <td><?= ucfirst($interview['interview_type']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Location</th>
                                        <td><?= $interview['location'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Interviewer</th>
                                        <td><?= $interview['interviewer_name'] ?: 'Not Assigned' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <?php 
                                            switch($interview['status']) {
                                                case 'scheduled': 
                                                    echo '<span class="badge badge-info">Scheduled</span>'; 
                                                    break;
                                                case 'completed': 
                                                    echo '<span class="badge badge-success">Completed</span>'; 
                                                    break;
                                                case 'cancelled': 
                                                    echo '<span class="badge badge-danger">Cancelled</span>'; 
                                                    break;
                                                case 'no_show': 
                                                    echo '<span class="badge badge-warning">No Show</span>'; 
                                                    break;
                                                case 'rescheduled': 
                                                    echo '<span class="badge badge-primary">Rescheduled</span>'; 
                                                    break;
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Outcome</th>
                                        <td>
                                            <?php 
                                            switch($interview['outcome']) {
                                                case 'recommended': 
                                                    echo '<span class="badge badge-success">Recommended</span>'; 
                                                    break;
                                                case 'not_recommended': 
                                                    echo '<span class="badge badge-danger">Not Recommended</span>'; 
                                                    break;
                                                case 'waitlist': 
                                                    echo '<span class="badge badge-warning">Waitlist</span>'; 
                                                    break;
                                                case 'pending': 
                                                    echo '<span class="badge badge-secondary">Pending</span>'; 
                                                    break;
                                                default: 
                                                    echo '<span class="badge badge-light">Not Set</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes & Actions -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4><i class="fas fa-sticky-note"></i> Interview Notes</h4>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($interview['notes'])): ?>
                                    <div class="well"><?= nl2br(htmlspecialchars($interview['notes'])) ?></div>
                                <?php else: ?>
                                    <p class="text-muted">No notes available.</p>
                                <?php endif; ?>
                                
                                <?php if (!empty($interview['recommendation_notes'])): ?>
                                    <h5>Recommendation Notes:</h5>
                                    <div class="well"><?= nl2br(htmlspecialchars($interview['recommendation_notes'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="text-center">
                            <?php if (!in_array($interview['admission_status'], [2, 3])): ?>
                                <!-- ========== ACTIVE STATE: NO FINAL DECISION MADE ========== -->
                                
                                <?php if ($interview['admission_status'] == 4 && in_array($interview['status'], ['scheduled', 'rescheduled'])): ?>
                                    <!-- STATUS 4 (Interview Scheduled) + Interview Scheduled/Rescheduled -->
                                    
                                    <?php if (get_permission('online_admission', 'is_edit')): ?>
                                        <button class="btn btn-success complete-interview-btn" 
                                                data-id="<?= $interview['id'] ?>"
                                                data-student="<?= htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']) ?>"
                                                data-date="<?= date('d M Y', strtotime($interview['interview_date'])) ?>"
                                                data-time="<?= date('h:i A', strtotime($interview['interview_time'])) ?>">
                                            <i class="fas fa-check-circle"></i> Complete Interview
                                        </button>
                                        
                                        <button class="btn btn-warning send-reminder-btn" data-id="<?= $interview['id'] ?>">
                                            <i class="fas fa-bell"></i> Send Reminder
                                        </button>
                                        
                                        <button class="btn btn-primary show-reschedule-btn" data-id="<?= $interview['id'] ?>">
                                            <i class="fas fa-calendar-alt"></i> Reschedule
                                        </button>
                                        
                                        <button class="btn btn-danger cancel-interview-btn" data-id="<?= $interview['id'] ?>">
                                            <i class="fas fa-times-circle"></i> Cancel Interview
                                        </button>
                                        
                                        <button class="btn btn-info mark-noshow-btn" data-id="<?= $interview['id'] ?>">
                                            <i class="fas fa-user-times"></i> Mark as No-Show
                                        </button>
                                    <?php endif; ?>
                                    
                                <?php elseif ($interview['admission_status'] == 5 && $interview['status'] == 'completed'): ?>
                                    <!-- STATUS 5 (Interview Completed) + Interview Completed -->
                                    
                                    <?php if (get_permission('online_admission', 'is_add')): ?>
                                        <?php if ($interview['outcome'] == 'recommended'): ?>
                                            <a href="<?=base_url('online_admission/approve_after_interview/' . $interview['id'])?>" 
                                            class="btn btn-success confirm-action"
                                            data-message="Approve admission for <?= htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']) ?>?">
                                                <i class="fas fa-check-circle"></i> Approve Admission
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($interview['outcome'], ['not_recommended', 'pending', 'waitlist'])): ?>
                                            <a href="<?=base_url('online_admission/decline_after_interview/' . $interview['id'])?>" 
                                            class="btn btn-danger confirm-action"
                                            data-message="Decline admission for <?= htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']) ?>?">
                                                <i class="fas fa-times-circle"></i> Decline Admission
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                <?php endif; ?>
                                
                                <!-- Update Status Button (always visible unless final decision) -->
                                <?php if (get_permission('online_admission', 'is_edit')): ?>
                                    <button class="btn btn-info" data-toggle="modal" data-target="#updateInterviewModal">
                                        <i class="fas fa-edit"></i> Update Status
                                    </button>
                                <?php endif; ?>
                                
                                <!-- No-show Action Button -->
                                <?php if ($interview['status'] == 'no_show' && get_permission('online_admission', 'is_edit')): ?>
                                    <button class="btn btn-warning" onclick="showNoShowActionModal(<?= $interview['id'] ?>)">
                                        <i class="fas fa-redo"></i> Take Action
                                    </button>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <!-- ========== READ-ONLY STATE: FINAL DECISION MADE ========== -->
                                <div class="alert alert-<?= $interview['admission_status'] == 2 ? 'success' : 'danger' ?> col-md-8 offset-md-2">
                                    <i class="fas fa-<?= $interview['admission_status'] == 2 ? 'check-circle' : 'times-circle' ?>"></i> 
                                    <strong>Final Decision Made:</strong> 
                                    Admission has been <strong><?= $interview['admission_status'] == 2 ? 'APPROVED' : 'DECLINED' ?></strong>.
                                    
                                    <?php if ($interview['admission_status'] == 2): ?>
                                        <br><small class="text-muted">
                                            <i class="fas fa-user-graduate"></i> Student record created.
                                            <i class="fas fa-lock ml-2"></i> Interview record is now read-only.
                                        </small>
                                    <?php else: ?>
                                        <br><small class="text-muted">
                                            <i class="fas fa-ban"></i> Admission closed.
                                            <i class="fas fa-lock ml-2"></i> Interview record is now read-only.
                                        </small>
                                    <?php endif; ?>
                                </div>
                            
                               <!-- View Student Button (only if approved) -->
                                <?php if ($interview['admission_status'] == 2): ?>
                                    <?php 
                                    // Try to find student by name and branch
                                    $this->db->select('id');
                                    $this->db->from('student');
                                    $this->db->where('first_name', $interview['first_name']);
                                    $this->db->where('last_name', $interview['last_name']);
                                    $this->db->where('branch_id', $interview['branch_id']);
                                    $this->db->order_by('id', 'DESC');
                                    $this->db->limit(1);
                                    $student_query = $this->db->get();
                                    
                                    $student_id = $student_query->num_rows() > 0 ? $student_query->row()->id : null;
                                    ?>
                                    
                                    <?php if (!empty($student_id)): ?>
                                        <a href="<?= base_url('student/view/' . $student_id) ?>" class="btn btn-success">
                                            <i class="fas fa-eye"></i> View Student
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled title="Student record not found or not yet created">
                                            <i class="fas fa-exclamation-circle"></i> Student Record Not Found
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Communication Log -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4><i class="fas fa-comments"></i> Communication Log</h4>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <button class="btn btn-xs btn-primary view-log-btn" data-id="<?= $interview['id'] ?>">
                                            <i class="fas fa-history"></i> View Full Log
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($communications)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Type</th>
                                                    <th>Direction</th>
                                                    <th>Message</th>
                                                    <th>Status</th>
                                                    <th>Sent By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($communications, 0, 5) as $comm): ?>
                                                <tr>
                                                    <td><?= date('d M Y H:i', strtotime($comm['created_at'])) ?></td>
                                                    <td>
                                                        <?php 
                                                        switch($comm['type']) {
                                                            case 'sms': echo '<span class="badge badge-info">SMS</span>'; break;
                                                            case 'email': echo '<span class="badge badge-primary">Email</span>'; break;
                                                            case 'note': echo '<span class="badge badge-secondary">Note</span>'; break;
                                                            default: echo '<span class="badge badge-light">' . $comm['type'] . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        switch($comm['direction']) {
                                                            case 'to_parent': echo '<span class="badge badge-success">To Parent</span>'; break;
                                                            case 'from_parent': echo '<span class="badge badge-warning">From Parent</span>'; break;
                                                            case 'internal': echo '<span class="badge badge-secondary">Internal</span>'; break;
                                                            default: echo '<span class="badge badge-light">' . $comm['direction'] . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?= substr(strip_tags($comm['message']), 0, 100) . '...' ?></td>
                                                    <td>
                                                        <?php 
                                                        switch($comm['status']) {
                                                            case 'sent': echo '<span class="badge badge-success">Sent</span>'; break;
                                                            case 'failed': echo '<span class="badge badge-danger">Failed</span>'; break;
                                                            case 'pending': echo '<span class="badge badge-warning">Pending</span>'; break;
                                                            default: echo '<span class="badge badge-light">' . $comm['status'] . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $sent_by = $this->db->select('name')->where('id', $comm['sent_by'])->get('staff')->row();
                                                        echo $sent_by ? $sent_by->name : 'System';
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php if (count($communications) > 5): ?>
                                            <div class="text-center">
                                                <a href="#" onclick="viewCommunicationLog(<?= $interview['id'] ?>)" class="btn btn-xs btn-default">
                                                    View All <?= count($communications) ?> Communications
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No communication records found.</p>
                                <?php endif; ?>
                                
                                <!-- Add Note Form -->
                                <form method="post" action="<?=base_url('online_admission/add_communication_note/' . $interview['id'])?>" class="mt-3">
                                    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                                    <div class="form-group">
                                        <label>Add Internal Note</label>
                                        <textarea name="note" class="form-control" rows="3" placeholder="Add a note about this interview..." required></textarea>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Add Note
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Update Interview Modal -->
<div class="modal fade" id="updateInterviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Update Interview Status</h4>
            </div>
            <form id="updateInterviewForm" method="post" action="<?=base_url('online_admission/update_interview/' . $interview['id'])?>">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label">Status *</label>
                        <select class="form-control" name="status" required>
                            <option value="scheduled" <?= $interview['status'] == 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="completed" <?= $interview['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $interview['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="no_show" <?= $interview['status'] == 'no_show' ? 'selected' : '' ?>>No Show</option>
                            <option value="rescheduled" <?= $interview['status'] == 'rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Outcome *</label>
                        <select class="form-control" name="outcome" required>
                            <option value="pending" <?= $interview['outcome'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="recommended" <?= $interview['outcome'] == 'recommended' ? 'selected' : '' ?>>Recommended</option>
                            <option value="not_recommended" <?= $interview['outcome'] == 'not_recommended' ? 'selected' : '' ?>>Not Recommended</option>
                            <option value="waitlist" <?= $interview['outcome'] == 'waitlist' ? 'selected' : '' ?>>Waitlist</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($interview['notes']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Recommendation Notes</label>
                        <textarea class="form-control" name="recommendation_notes" rows="3"><?= htmlspecialchars($interview['recommendation_notes'] ?? '') ?></textarea>
                    </div>
                    
                    <?php if ($interview['status'] != 'completed'): ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="send_followup" id="send_followup" value="1">
                        <label class="form-check-label" for="send_followup">
                            Send follow-up SMS after status change
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                 <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <?php if (!in_array($interview['admission_status'], [2, 3])): ?>
                        <button type="submit" class="btn btn-primary">Update Interview</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled>
                            <i class="fas fa-lock"></i> Read Only - Final Decision Made
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- No Show Action Modal -->
<div class="modal fade" id="noShowActionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">No-Show Action Required</h4>
            </div>
            <form method="post" action="<?=base_url('online_admission/handle_no_show/' . $interview['id'])?>" id="noShowActionForm">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <div class="modal-body">
                    <p><strong>Student:</strong> <?= $interview['first_name'] . ' ' . $interview['last_name'] ?></p>
                    <p><strong>Interview Date:</strong> <?= date('d M Y', strtotime($interview['interview_date'])) ?></p>
                    <p><strong>Interview Time:</strong> <?= date('h:i A', strtotime($interview['interview_time'])) ?></p>
                    
                    <div class="form-group">
                        <label class="control-label">Action *</label>
                        <select class="form-control" name="no_show_action" required onchange="toggleRescheduleOptions()">
                            <option value="">Select Action</option>
                            <option value="reschedule">Reschedule Interview</option>
                            <option value="decline">Decline Admission</option>
                        </select>
                    </div>
                    
                    <div id="rescheduleOptions" style="display:none;">
                        <div class="form-group">
                            <label class="control-label">New Date *</label>
                            <input type="text" class="form-control datepicker" name="reschedule_date" 
                                   value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="control-label">New Time *</label>
                            <input type="text" class="form-control timepicker" name="reschedule_time" value="10:00">
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Candidate did not attend the scheduled interview. Please select an action.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Take Action</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Reschedule Interview</h4>
            </div>
            <form method="post" action="<?= base_url('online_admission/reschedule_interview/' . $interview['id']) ?>" id="rescheduleForm">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label">New Date *</label>
                        <input type="text" class="form-control datepicker" name="interview_date" 
                               value="<?= date('Y-m-d', strtotime($interview['interview_date'])) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="control-label">New Time *</label>
                        <input type="text" class="form-control timepicker" name="interview_time" 
                               value="<?= date('H:i', strtotime($interview['interview_time'])) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Reason for Reschedule *</label>
                        <textarea class="form-control" name="reschedule_reason" rows="3" 
                                  placeholder="Why are you rescheduling this interview?" required></textarea>
                    </div>
                    <div class="form-check">
                       <input type="checkbox" class="form-check-input" name="change_interviewer" id="change_interviewer" value="1">
                        <label class="form-check-label" for="change_interviewer">
                            Change Interviewer
                        </label>
                    </div>
                   <!-- In the reschedule modal form -->
                    <div class="form-group" id="interviewer_select" style="display:none; margin-top:10px;">
                        <label class="control-label">Select Interviewer *</label>
                        <select class="form-control" name="interviewer_id" required>
                            <option value="">Select Interviewer</option>
                            <?php foreach($interviewers as $int): ?>
                            <option value="<?= $int['id'] ?>" <?= ($interview['interviewer_id'] == $int['id']) ? 'selected' : '' ?>>
                                <?= $int['name'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> A reschedule notification SMS will be sent to the parent/guardian.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reschedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <!-- ========== CONFIRMATION MODAL ========== -->
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalTitle">Confirm Action</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="confirmModalBody">
                    <!-- Message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmModalConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== ALERT MODAL ========== -->
    <div class="modal fade" id="alertModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalTitle">Notification</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="alertModalBody">
                    <!-- Message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== PROMPT MODAL ========== -->
    <div class="modal fade" id="promptModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="promptModalTitle">Input Required</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="promptModalMessage"></p>
                    <div class="form-group">
                        <input type="text" class="form-control" id="promptModalInput" placeholder="Enter value">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="promptModalSubmit">Submit</button>
                </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
    // ========== MODAL HELPER FUNCTIONS ==========
// Get CSRF token
function getCsrfToken() {
    return '<?php echo $this->security->get_csrf_hash(); ?>';
}

// Alert Modal (replaces alert())
function showAlert(title, message) {
    $('#alertModalTitle').text(title || 'Notification');
    $('#alertModalBody').html(message || '');
    $('#alertModal').modal('show');
}

// Confirm Modal (replaces confirm())
function showConfirm(title, message, confirmCallback, cancelCallback) {
    $('#confirmModalTitle').text(title || 'Confirm Action');
    $('#confirmModalBody').html(message || 'Are you sure?');
    
    // Remove any existing click handlers
    $('#confirmModalConfirm').off('click');
    
    // Set new click handler
    $('#confirmModalConfirm').on('click', function() {
        $('#confirmModal').modal('hide');
        if (typeof confirmCallback === 'function') {
            confirmCallback();
        }
    });
    
    // Handle cancel/close
    $('#confirmModal').off('hidden.bs.modal');
    $('#confirmModal').on('hidden.bs.modal', function() {
        if (typeof cancelCallback === 'function') {
            cancelCallback();
        }
    });
    
    $('#confirmModal').modal('show');
}

// Prompt Modal (replaces prompt())
function showPrompt(title, message, placeholder, submitCallback, cancelCallback) {
    $('#promptModalTitle').text(title || 'Input Required');
    $('#promptModalMessage').text(message || 'Please enter a value:');
    $('#promptModalInput').val('').attr('placeholder', placeholder || '');
    
    // Remove any existing click handlers
    $('#promptModalSubmit').off('click');
    
    // Set new click handler
    $('#promptModalSubmit').on('click', function() {
        var value = $('#promptModalInput').val();
        $('#promptModal').modal('hide');
        if (typeof submitCallback === 'function') {
            submitCallback(value);
        }
    });
    
    // Handle Enter key
    $('#promptModalInput').off('keypress');
    $('#promptModalInput').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#promptModalSubmit').click();
        }
    });
    
    // Handle cancel/close
    $('#promptModal').off('hidden.bs.modal');
    $('#promptModal').on('hidden.bs.modal', function() {
        if (typeof cancelCallback === 'function') {
            cancelCallback();
        }
    });
    
    $('#promptModal').modal('show');
    $('#promptModalInput').focus();
}

// ========== AJAX RESPONSE HANDLER ==========
function handleAjaxResponse(xhr, successCallback, errorCallback) {
    try {
        var response = JSON.parse(xhr.responseText);
        if (response.success) {
            if (response.redirect) {
                window.location.href = response.redirect;
            } else {
                if (response.message) {
                    showAlert('Success', response.message);
                }
                if (successCallback) {
                    setTimeout(function() {
                        successCallback(response);
                    }, 1500);
                }
            }
        } else {
            showAlert('Error', 'Error: ' + (response.message || 'Unknown error'));
            if (errorCallback) errorCallback(response);
        }
    } catch (e) {
        // Not JSON - controller redirected with set_alert()
        if (xhr.responseText.includes('set_alert')) {
            location.reload(); // Reload to see the set_alert message
        } else {
            showAlert('Error', 'Server error: ' + xhr.statusText);
            if (errorCallback) errorCallback();
        }
    }
}
$(document).ready(function() {
    // Get CSRF token
    var csrf_token = $('meta[name="csrf-token"]').length ? $('meta[name="csrf-token"]').attr('content') : 
                     $('input[name="<?=$this->security->get_csrf_token_name()?>"]').val();
    
    // Initialize datepicker and timepicker
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        startDate: '0d'
    });
    
    if (typeof $.fn.timepicker !== 'undefined') {
        $('.timepicker').timepicker({
            showMeridian: false,
            minuteStep: 15
        });
    }
    
    // Toggle interviewer select
    
        $('#change_interviewer').change(function() {
            if ($(this).is(':checked')) {
                $('#interviewer_select').show();
                $('#interviewer_select select').prop('required', true);
            } else {
                $('#interviewer_select').hide();
                $('#interviewer_select select').prop('required', false).val('');
            }
        });

        // Initialize on page load
        if ($('#change_interviewer').is(':checked')) {
            $('#interviewer_select').show();
            $('#interviewer_select select').prop('required', true);
        }
    // Update Interview Form Submission
$('#updateInterviewForm').submit(function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    var formUrl = $(this).attr('action');
    
    $.ajax({
        url: formUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            $('#updateInterviewModal .btn-primary').html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
        },
        success: function(response) {
            if (response.success) {
                $('#updateInterviewModal').modal('hide');
                showAlert('Success', response.message || 'Interview updated successfully!');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showAlert('Error', response.message || 'Failed to update interview.');
                $('#updateInterviewModal .btn-primary').html('Update Interview').prop('disabled', false);
            }
        },
        error: function(xhr) {
            // Handle controller redirect with set_alert
            if (xhr.responseText.includes('set_alert')) {
                $('#updateInterviewModal').modal('hide');
                location.reload();
            } else {
                showAlert('Error', 'Server error: ' + xhr.statusText);
                $('#updateInterviewModal .btn-primary').html('Update Interview').prop('disabled', false);
            }
        }
    });
});
  // ========== COMPLETE INTERVIEW BUTTON ==========
    $(document).on('click', '.complete-interview-btn', function() {
        var interviewId = $(this).data('id');
        var studentName = $(this).data('student');
        var interviewDate = $(this).data('date');
        var interviewTime = $(this).data('time');
        
        var message = `<strong>Student:</strong> ${studentName}<br>
                    <strong>Date:</strong> ${interviewDate}<br>
                    <strong>Time:</strong> ${interviewTime}<br><br>
                    This will:<br>
                    • Set interview status to "completed"<br>
                    • Update admission status to 5 (Interview Completed)<br>
                    • Enable post-interview decision buttons<br><br>
                    <strong>Are you sure?</strong>`;
        
        showConfirm('Complete Interview', message, function() {
            completeInterviewSingle(interviewId);
        });
    });
    
    // ========== SEND REMINDER ==========
$(document).on('click', '.send-reminder-btn', function() {
    var interviewId = $(this).data('id');
    var button = $(this);
    
    showConfirm('Send Reminder', 
        'Send reminder SMS to parent/guardian?<br><br>' +
        'This will notify them about the upcoming interview.',
        function() {
            $.ajax({
                url: '<?=base_url("online_admission/send_reminder")?>',
                type: 'POST',
                data: {
                    <?=$this->security->get_csrf_token_name()?>: getCsrfToken(),
                    interview_id: interviewId
                },
                dataType: 'json',
                beforeSend: function() {
                    button.html('<i class="fas fa-spinner fa-spin"></i> Sending...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('Success', 'Reminder sent successfully!');
                    } else {
                        showAlert('Error', 'Error: ' + response.message);
                    }
                    button.html('<i class="fas fa-bell"></i> Send Reminder').prop('disabled', false);
                },
                error: function(xhr) {
                    showAlert('Error', 'Server error: ' + xhr.statusText);
                    button.html('<i class="fas fa-bell"></i> Send Reminder').prop('disabled', false);
                }
            });
        }
    );
});
    
    // ========== CANCEL INTERVIEW ==========
$(document).on('click', '.cancel-interview-btn', function() {
    var interviewId = $(this).data('id');
    var button = $(this);
    
    showPrompt('Cancel Interview', 
        'Please provide a reason for cancellation:',
        'Reason for cancellation...',
        function(reason) {
            if (!reason || reason.trim() === '') {
                showAlert('Error', 'Cancellation reason is required.');
                return;
            }
            
            showConfirm('Confirm Cancellation',
                'Are you sure you want to cancel this interview?<br><br>' +
                '<strong>Reason:</strong> ' + reason,
                function() {
                    $.ajax({
                        url: '<?=base_url("online_admission/cancel_interview")?>',
                        type: 'POST',
                        data: {
                            <?=$this->security->get_csrf_token_name()?>: getCsrfToken(),
                            interview_id: interviewId,
                            reason: reason.trim()
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            button.html('<i class="fas fa-spinner fa-spin"></i> Cancelling...').prop('disabled', true);
                        },
                        success: function(response) {
                            if (response.success) {
                                showAlert('Success', 'Interview cancelled successfully!');
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                showAlert('Error', 'Error: ' + response.message);
                                button.html('<i class="fas fa-times-circle"></i> Cancel Interview').prop('disabled', false);
                            }
                        },
                        error: function(xhr) {
                            showAlert('Error', 'Server error: ' + xhr.statusText);
                            button.html('<i class="fas fa-times-circle"></i> Cancel Interview').prop('disabled', false);
                        }
                    });
                }
            );
        }
    );
});
    
   // ========== MARK AS NO-SHOW ==========
// Replace the entire mark-noshow-btn handler (lines 201-223) with:
$(document).on('click', '.mark-noshow-btn', function() {
    var interviewId = $(this).data('id');
    
    showConfirm('Mark as No-Show',
        'Mark this interview as No-Show?<br><br>' +
        'This indicates the candidate did not attend the scheduled interview.<br>' +
        'You will need to select an action (reschedule or decline) after marking.',
        function() {
            // Update via AJAX first
            $.ajax({
                url: '<?=base_url("online_admission/update_interview_ajax/")?>' + interviewId,
                type: 'POST',
                data: {
                    <?=$this->security->get_csrf_token_name()?>: getCsrfToken(),
                    status: 'no_show',
                    outcome: 'pending'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show action modal
                        $('#noShowActionModal').modal('show');
                    } else {
                        showAlert('Error', 'Error: ' + response.message);
                    }
                }
            });
        }
    );
});

// Add this to handle the no-show form submission:
$('#noShowActionForm').submit(function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    var formUrl = $(this).attr('action');
    
    $.ajax({
        url: formUrl,
        type: 'POST',
        data: formData,
        beforeSend: function() {
            $('#noShowActionModal .btn-primary').html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        },
        success: function(response) {
            // Handle HTML response (controller uses set_alert and redirect)
            $('#noShowActionModal').modal('hide');
            location.reload();
        },
        error: function(xhr) {
            showAlert('Error', 'Server error: ' + xhr.statusText);
            $('#noShowActionModal .btn-primary').html('Take Action').prop('disabled', false);
        }
    });
});

// Update toggleRescheduleOptions to work with the correct selectors
function toggleRescheduleOptions() {
    var action = $('#noShowActionSelect').val();
        if (action === 'reschedule') {
        $('#rescheduleOptions').show();
        $('input[name="reschedule_date"], input[name="reschedule_time"]').prop('required', true);
    } else {
        $('#rescheduleOptions').hide();
        $('input[name="reschedule_date"], input[name="reschedule_time"]').prop('required', false);
    }
}
    
    // ========== SHOW RESCHEDULE MODAL ==========
    $(document).on('click', '.show-reschedule-btn', function() {
        $('#rescheduleModal').modal('show');
    });
    
    // ========== VIEW COMMUNICATION LOG ==========
    $(document).on('click', '.view-log-btn', function() {
        var interviewId = $(this).data('id');
        $('#communicationLogContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Loading...</p></div>');
        $('#communicationLogModal').modal('show');
        
        // Since get_communication_log endpoint doesn't exist, show existing communications
        $('#communicationLogContent').html(`
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Direction</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Sent By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($communications as $comm): ?>
                        <tr>
                            <td><?= date('d M Y H:i', strtotime($comm['created_at'])) ?></td>
                            <td>
                                <?php 
                                switch($comm['type']) {
                                    case 'sms': echo '<span class="badge badge-info">SMS</span>'; break;
                                    case 'email': echo '<span class="badge badge-primary">Email</span>'; break;
                                    case 'note': echo '<span class="badge badge-secondary">Note</span>'; break;
                                    default: echo '<span class="badge badge-light">' . $comm['type'] . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                switch($comm['direction']) {
                                    case 'to_parent': echo '<span class="badge badge-success">To Parent</span>'; break;
                                    case 'from_parent': echo '<span class="badge badge-warning">From Parent</span>'; break;
                                    case 'internal': echo '<span class="badge badge-secondary">Internal</span>'; break;
                                    default: echo '<span class="badge badge-light">' . $comm['direction'] . '</span>';
                                }
                                ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($comm['message'])) ?></td>
                            <td>
                                <?php 
                                switch($comm['status']) {
                                    case 'sent': echo '<span class="badge badge-success">Sent</span>'; break;
                                    case 'failed': echo '<span class="badge badge-danger">Failed</span>'; break;
                                    case 'pending': echo '<span class="badge badge-warning">Pending</span>'; break;
                                    default: echo '<span class="badge badge-light">' . $comm['status'] . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $sent_by = $this->db->select('name')->where('id', $comm['sent_by'])->get('staff')->row();
                                echo $sent_by ? $sent_by->name : 'System';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        `);
    });
});

// ========== COMPLETE INTERVIEW FUNCTION ==========
function completeInterviewSingle(interviewId) {
    var button = $('.complete-interview-btn[data-id="' + interviewId + '"]');
    var studentName = button.data('student');
    
    showConfirm('Complete Interview',
        'Mark interview as completed for ' + studentName + '?<br><br>' +
        'This will:<br>' +
        '• Set interview status to "completed"<br>' +
        '• Update admission status to 5 (Interview Completed)<br>' +
        '• Enable post-interview decision buttons',
        function() {
            button.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
            
            $.ajax({
                url: '<?=base_url("online_admission/complete_interview/")?>' + interviewId,
                type: 'POST',
                data: {
                    <?=$this->security->get_csrf_token_name()?>: getCsrfToken()
                },
                dataType: 'json',
                beforeSend: function() {
                    // Show loading overlay
                    $('body').append('<div class="loading-overlay"><div class="spinner"></div></div>');
                },
                success: function(response) {
                    $('.loading-overlay').remove();
                    
                    if (response.success) {
                        showAlert('Success', response.message || 'Interview completed successfully!');
                        setTimeout(function() {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                location.reload();
                            }
                        }, 1500);
                    } else {
                        showAlert('Error', response.message || 'Failed to complete interview.');
                        button.html('<i class="fas fa-check-circle"></i> Complete Interview').prop('disabled', false);
                    }
                },
                // In completeInterviewSingle function, update the error section:
                error: function(xhr, status, error) {
                    $('.loading-overlay').remove();
                    
                    // Handle different response types
                    if (xhr.status === 0) {
                        showAlert('Error', 'Network error. Please check your connection.');
                    } else if (xhr.status === 500) {
                        showAlert('Error', 'Server error. Please try again later.');
                    } else {
                        // Check if it's a controller redirect with set_alert
                        if (xhr.responseText.includes('set_alert')) {
                            showAlert('Success', 'Interview completed!');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            try {
                                var errorResponse = JSON.parse(xhr.responseText);
                                showAlert('Error', errorResponse.message || 'Unknown error');
                            } catch (e) {
                                showAlert('Error', 'Error completing interview. Please try again.');
                            }
                        }
                    }
                    
                    button.html('<i class="fas fa-check-circle"></i> Complete Interview').prop('disabled', false);
                }
            });
        },
        function() {
            // Cancel callback - do nothing
            console.log('Cancelled');
        }
    );
}


// ========== FIX ADMISSION STATUS ==========
function fixAdmissionStatusSingle(interviewId, admissionId) {
    showConfirm('Fix Admission Status',
        'Fix admission status?<br><br>' +
        'This will update the admission status to 5 (Interview Completed) so you can make a decision.',
        function() {
            $.ajax({
                url: '<?=base_url("online_admission/fix_admission_status/")?>' + admissionId,
                type: 'POST',
                data: {
                    <?=$this->security->get_csrf_token_name()?>: getCsrfToken(),
                    interview_id: interviewId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('Success', 'Admission status fixed! You can now make a decision.');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('Error', 'Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    showAlert('Error', 'Server error: ' + xhr.statusText);
                }
            });
        }
    );
}

// ========== NO-SHOW ACTION HANDLER ==========

function showNoShowActionModal(interviewId) {
    $('#noShowActionModal').modal('show');
}

// Add Communication Log Modal to HTML (if not exists)
if ($('#communicationLogModal').length === 0) {
    $('body').append(`
        <div class="modal fade" id="communicationLogModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Communication Log</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="communicationLogContent"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `);
}

$('#rescheduleForm').submit(function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    var formUrl = $(this).attr('action');
    var submitBtn = $(this).find('.btn-primary');
    
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
    
    $.ajax({
        url: formUrl,
        type: 'POST',
        data: formData,
        success: function(response) {
            $('#rescheduleModal').modal('hide');
            // If controller uses set_alert and redirects, reload
            if (response.includes('set_alert') || response.includes('location.href')) {
                location.reload();
            } else {
                try {
                    var jsonResponse = JSON.parse(response);
                    if (jsonResponse.success) {
                        showAlert('Success', jsonResponse.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('Error', jsonResponse.message);
                    }
                } catch (e) {
                    // Not JSON, reload to see set_alert
                    location.reload();
                }
            }
        },
        error: function(xhr, status, error) {
            submitBtn.html('Reschedule').prop('disabled', false);
            showAlert('Error', 'Reschedule failed: ' + (xhr.responseText || error));
        }
    });
});

$(document).ready(function() {
    // ========== DISABLE BUTTONS IF FINAL DECISION MADE ==========
    <?php if (in_array($interview['admission_status'], [2, 3])): ?>
    // Disable all interactive buttons
    $('.complete-interview-btn, .send-reminder-btn, .cancel-interview-btn, ' +
      '.mark-noshow-btn, .show-reschedule-btn, [data-target="#updateInterviewModal"]')
      .prop('disabled', true)
      .addClass('disabled')
      .css('opacity', '0.6');
    
    // Prevent modal opening for update
    $('#updateInterviewModal').on('show.bs.modal', function(e) {
        e.preventDefault();
        showAlert(
            'Read Only', 
            'Interview record is read-only. Final decision has been made.'
        );
        return false;
    });
    
    // Replace decision buttons with disabled versions
    $('.btn-success[href*="approve_after_interview"], .btn-danger[href*="decline_after_interview"]')
      .replaceWith(function() {
          return '<button class="btn btn-secondary" disabled>' + 
                 '<i class="fas fa-lock"></i> ' + 
                 $(this).text() + 
                 '</button>';
      });
    <?php endif; ?>
    
    // ========== CONFIRMATION FOR DECISION BUTTONS ==========
    $(document).on('click', '.confirm-action', function(e) {
        e.preventDefault();
        var message = $(this).data('message');
        var url = $(this).attr('href');
        
        showConfirm('Confirm Decision', message, function() {
            window.location.href = url;
        });
    });
    
    // ========== PERMISSION-BASED DISABLING ==========
    // Check permissions and disable if needed
    <?php if (!get_permission('online_admission', 'is_edit')): ?>
    $('.complete-interview-btn, .send-reminder-btn, .cancel-interview-btn, ' +
      '.mark-noshow-btn, .show-reschedule-btn, [data-target="#updateInterviewModal"]')
      .prop('disabled', true)
      .addClass('disabled')
      .attr('title', 'Permission required: online_admission/is_edit');
    <?php endif; ?>
    
    <?php if (!get_permission('online_admission', 'is_add')): ?>
    $('.btn-success[href*="approve_after_interview"], .btn-danger[href*="decline_after_interview"]')
      .prop('disabled', true)
      .addClass('disabled')
      .attr('title', 'Permission required: online_admission/is_add');
    <?php endif; ?>
});

</script>

<style>
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.badge {
    font-size: 85%;
}

.complete-interview-btn, .send-reminder-btn, .cancel-interview-btn {
    margin: 2px;
}

/* Modal styling */
.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.modal-title {
    color: #495057;
    font-weight: 600;
}

#confirmModal .modal-content,
#alertModal .modal-content,
#promptModal .modal-content {
    border-radius: 8px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

#confirmModal .btn-primary,
#alertModal .btn-primary,
#promptModal .btn-primary {
    background-color: #007bff;
    border-color: #007bff;
    min-width: 100px;
}

#confirmModal .btn-secondary,
#alertModal .btn-secondary,
#promptModal .btn-secondary {
    min-width: 100px;
}

/* Alert messages in modals */
.modal-body strong {
    color: #495057;
}

.modal-body .alert {
    margin-bottom: 0;
}

/* Button spacing */
.complete-interview-btn,
.send-reminder-btn,
.cancel-interview-btn,
.mark-noshow-btn {
    margin: 2px;
    min-width: 160px;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.badge {
    font-size: 85%;
}

/* Disabled state styling */
.btn.disabled, .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

/* Permission warning */
.btn[title*="Permission"] {
    position: relative;
}

.btn[title*="Permission"]:hover:after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
}

/* Final decision alert */
.alert-success, .alert-danger {
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #28a745;
}

.alert-danger {
    border-left-color: #dc3545;
}

/* Button spacing */
.btn {
    margin: 3px;
    min-width: 160px;
}

/* Responsive buttons */
@media (max-width: 768px) {
    .btn {
        display: block;
        width: 100%;
        margin-bottom: 5px;
    }
}
/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-overlay .spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
