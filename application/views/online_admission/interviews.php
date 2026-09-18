<!-- application/views/online_admission/interviews.php -->
<!-- INTERVIEWS PAGE NAVIGATION -->
<div class="row mb-md">
    <div class="col-md-12">
        <div class="btn-toolbar" role="toolbar">
            <!-- Left buttons -->
            <div class="btn-group mr-2" role="group">
                <a href="<?=base_url('online_admission')?>" class="btn btn-default">
                    <i class="fas fa-list"></i> All Admissions
                </a>
                
                <!-- Only show if there are pending admissions -->
                <?php 
                $pending_count = $this->db->where('status', 1)
                    ->where('branch_id', $this->application_model->get_branch_id())
                    ->count_all_results('online_admission');
                if ($pending_count > 0):
                ?>
                <a href="<?=base_url('online_admission')?>" class="btn btn-warning">
                    <i class="fas fa-clock"></i> Pending (<?=$pending_count?>)
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Center: Quick stats -->
            <div class="mr-2">
                <span class="badge badge-info">Scheduled: 
                    <?=array_reduce($interviews, function($carry, $item) {
                        return $carry + (in_array($item['status'], ['scheduled', 'rescheduled']) ? 1 : 0);
                    }, 0)?>
                </span>
                <span class="badge badge-success ml-1">Completed: 
                    <?=array_reduce($interviews, function($carry, $item) {
                        return $carry + ($item['status'] == 'completed' ? 1 : 0);
                    }, 0)?>
                </span>
                <span class="badge badge-warning ml-1">Needs Decision: 
                    <?=array_reduce($interviews, function($carry, $item) {
                        return $carry + ($item['status'] == 'completed' && $item['admission_status'] == 5 ? 1 : 0);
                    }, 0)?>
                </span>
                <span class="badge badge-danger ml-1">Today: 
                    <?=array_reduce($interviews, function($carry, $item) {
                        return $carry + ($item['interview_date'] == date('Y-m-d') ? 1 : 0);
                    }, 0)?>
                </span>
            </div>
        </div>
    </div>
</div>
<!-- end of INTERVIEWS PAGE NAVIGATION -->
<?php $widget = (is_superadmin_loggedin() ? "col-md-6" : "col-md-offset-3 col-md-6"); ?>
<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title">
                            <i class="fas fa-calendar-alt"></i> Admission Interviews
                        </h4>
                        <!-- ADD THIS SMALL NAV: -->
                        <small class="text-muted">
                            <a href="<?=base_url('online_admission')?>"><i class="fas fa-list"></i> Admissions</a> 
                            → <a href="<?=base_url('online_admission/interviews')?>"><i class="fas fa-calendar-alt"></i> Interviews</a>
                            <?php if(isset($_GET['status'])): ?>
                                → <span class="text-primary"><?=ucfirst($_GET['status'])?></span>
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-right">
                        <!-- ADD THIS BUTTON: -->
                        <a href="<?=base_url('online_admission')?>" class="btn btn-default btn-sm mr-2">
                            <i class="fas fa-arrow-left"></i> Back to Admissions
                        </a>
                    </div>
                    <div class="col-md-6 text-right">
                        <!-- Status Filter -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-filter"></i> Status: 
                                <?= ucfirst(isset($status) && $status ? $status : 'All') ?>
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="<?=base_url('online_admission/interviews')?>">All Interviews</a></li>
                                <li class="divider"></li>
                                <li><a href="<?=base_url('online_admission/interviews?status=scheduled')?>">Scheduled</a></li>
                                <li><a href="<?=base_url('online_admission/interviews?status=completed')?>">Completed</a></li>
                                <li><a href="<?=base_url('online_admission/interviews?status=cancelled')?>">Cancelled</a></li>
                                <li><a href="<?=base_url('online_admission/interviews?status=no_show')?>">No Show</a></li>
                                <li><a href="<?=base_url('online_admission/interviews?status=rescheduled')?>">Rescheduled</a></li>
                                <li class="divider"></li>
                                <li><a href="<?=base_url('online_admission/interviews?status=completed&decision=pending')?>">Needs Decision (Completed)</a></li>
                            </ul>
                        </div>
                        
                        <!-- Date Range Filter -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-default btn-sm" id="daterange-btn">
                                <i class="far fa-calendar-alt"></i> 
                                <span id="date-range-text">Date Range</span>
                                <i class="fas fa-caret-down"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Interviews Table -->
            <div class="panel-body">
                <?php if (empty($interviews)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No interviews found for the selected criteria.
                        <a href="<?=base_url('online_admission')?>" class="btn btn-xs btn-primary pull-right">
                            <i class="fas fa-arrow-left"></i> Back to Admissions
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-condensed mb-none">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Admission</th>
                                    <th>Class</th>
                                    <th>Interview Date & Time</th>
                                    <th>Interviewer</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Outcome</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach ($interviews as $interview): ?>
                                <?php
                                // Status badge
                                $status_badge = '';
                                switch($interview['status']) {
                                    case 'scheduled': 
                                        $status_badge = '<span class="badge badge-info">Scheduled</span>'; 
                                        break;
                                    case 'completed': 
                                        $status_badge = '<span class="badge badge-success">Completed</span>'; 
                                        break;
                                    case 'cancelled': 
                                        $status_badge = '<span class="badge badge-danger">Cancelled</span>'; 
                                        break;
                                    case 'no_show': 
                                        $status_badge = '<span class="badge badge-warning">No Show</span>'; 
                                        break;
                                    case 'rescheduled': 
                                        $status_badge = '<span class="badge badge-primary">Rescheduled</span>'; 
                                        break;
                                }
                                
                                // Outcome badge
                                $outcome_badge = '';
                                switch($interview['outcome']) {
                                    case 'recommended': 
                                        $outcome_badge = '<span class="badge badge-success">Recommended</span>'; 
                                        break;
                                    case 'not_recommended': 
                                        $outcome_badge = '<span class="badge badge-danger">Not Recommended</span>'; 
                                        break;
                                    case 'waitlist': 
                                        $outcome_badge = '<span class="badge badge-warning">Waitlist</span>'; 
                                        break;
                                    case 'pending': 
                                        $outcome_badge = '<span class="badge badge-secondary">Pending</span>'; 
                                        break;
                                }
                                
                                // Admission status indicator
                                $admission_status_text = '';
                                switch($interview['admission_status']) {
                                    case 1: $admission_status_text = '<span class="badge badge-secondary">Pending</span>'; break;
                                    case 2: $admission_status_text = '<span class="badge badge-success">Approved</span>'; break;
                                    case 3: $admission_status_text = '<span class="badge badge-danger">Declined</span>'; break;
                                    case 4: $admission_status_text = '<span class="badge badge-info">Interview Scheduled</span>'; break;
                                    case 5: $admission_status_text = '<span class="badge badge-warning">Interview Completed</span>'; break;
                                }
                                ?>
                                <tr>
                                    <td><?= $count++; ?></td>
                                    <td>
                                        <strong><?= $interview['first_name'] . ' ' . $interview['last_name'] ?></strong><br>
                                        <small class="text-muted">Parent: <?= $interview['guardian_name'] ?></small>
                                    </td>
                                    <td>
                                        <!-- ADMISSION LINK -->
                                        <a href="<?=base_url('online_admission/approved/' . $interview['admission_id'])?>" 
                                        class="btn btn-xs btn-info" title="View admission">
                                            <i class="fas fa-external-link-alt"></i> View
                                        </a><br>
                                        <small class="text-muted">
                                            Status: <?= $admission_status_text ?>
                                            <?php if ($interview['status'] == 'completed' && $interview['admission_status'] != 5): ?>
                                                <br><small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> Needs update
                                                </small>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <!-- end of admission link -->

                                    <td><?= $interview['class_name'] ?></td>
                                    <td>
                                        <strong><?= date('d M Y', strtotime($interview['interview_date'])) ?></strong><br>
                                        <?= date('h:i A', strtotime($interview['interview_time'])) ?>
                                    </td>
                                    <td><?= $interview['interviewer_name'] ?: 'Not Assigned' ?></td>
                                    <td><?= $interview['location'] ?></td>
                                    <td><?= ucfirst($interview['interview_type']) ?></td>
                                    <td>
                                        <?= $status_badge ?>
                                        <?php if ($interview['status'] == 'completed' && $interview['admission_status'] != 5): ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> Admission status not updated!
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $outcome_badge ?></td>
                                    <td>
                                
                                    <div class="btn-group">
                                        <!-- View Button - Always visible -->
                                        <a href="<?= base_url('online_admission/interview/' . $interview['id']) ?>" 
                                        class="btn btn-default btn-circle" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if (!in_array($interview['admission_status'], [2, 3])): ?>
                                            <!-- ========== ACTIVE STATE: NO FINAL DECISION MADE ========== -->
                                            <?php if (get_permission('online_admission', 'is_edit')): ?>
                                                <button type="button" class="btn btn-default btn-circle dropdown-toggle" 
                                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="<?=base_url('online_admission/approved/' . $interview['admission_id'])?>">
                                                        <i class="fas fa-user-graduate text-primary"></i> View Admission
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    
                                                    <?php if ($interview['admission_status'] == 4 && in_array($interview['status'], ['scheduled', 'rescheduled'])): ?>
                                                        <!-- STATUS 4: Interview Scheduled + Interview Scheduled/Rescheduled -->
                                                        
                                                        <!-- Complete Interview Button -->
                                                        <a class="dropdown-item text-success" href="#" 
                                                        onclick="completeInterviewSingle(<?= $interview['id'] ?>, '<?= htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']) ?>', '<?= date('d M Y', strtotime($interview['interview_date'])) ?>', '<?= date('h:i A', strtotime($interview['interview_time'])) ?>')">
                                                            <i class="fas fa-check-square"></i> Mark as Completed
                                                        </a>
                                                        
                                                        <!-- Send Reminder -->
                                                        <a class="dropdown-item" href="#" 
                                                        onclick="sendReminderSingle(<?= $interview['id'] ?>)">
                                                            <i class="fas fa-bell text-warning"></i> Send Reminder
                                                        </a>
                                                        
                                                        <!-- Reschedule -->
                                                        <a class="dropdown-item" href="#" 
                                                        data-toggle="modal" data-target="#rescheduleModal<?= $interview['id'] ?>">
                                                            <i class="fas fa-calendar-alt text-primary"></i> Reschedule
                                                        </a>
                                                        
                                                        <!-- Cancel Interview -->
                                                        <a class="dropdown-item text-danger" href="#" 
                                                        onclick="cancelInterviewSingle(<?= $interview['id'] ?>)">
                                                            <i class="fas fa-times-circle"></i> Cancel Interview
                                                        </a>
                                                        
                                                        <div class="dropdown-divider"></div>
                                                        
                                                        <!-- Mark as No-Show -->
                                                        <a class="dropdown-item text-warning" href="#" 
                                                        onclick="markNoShowSingle(<?= $interview['id'] ?>)">
                                                            <i class="fas fa-user-times"></i> Mark as No-Show
                                                        </a>
                                                        
                                                    <?php elseif ($interview['admission_status'] == 5 && $interview['status'] == 'completed'): ?>
                                                        <!-- STATUS 5: Interview Completed + Interview Completed -->
                                                        
                                                        <?php if (get_permission('online_admission', 'is_add')): ?>
                                                            <?php if ($interview['outcome'] == 'recommended'): ?>
                                                                <a class="dropdown-item text-success" href="#" 
                                                                onclick="approveAfterInterviewSingle(<?= $interview['id'] ?>, '<?= htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']) ?>')">
                                                                    <i class="fas fa-check-circle"></i> Approve Admission
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (in_array($interview['outcome'], ['not_recommended', 'pending', 'waitlist'])): ?>
                                                                <a class="dropdown-item text-danger" href="#" 
                                                                onclick="declineAfterInterviewSingle(<?= $interview['id'] ?>, '<?= htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']) ?>')">
                                                                    <i class="fas fa-times-circle"></i> Decline Admission
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($interview['admission_status'] == 5 && $interview['status'] == 'completed'): ?>
                                                            <!-- Already in status 5 - no fix needed -->
                                                        <?php elseif ($interview['status'] == 'completed' && $interview['admission_status'] != 5): ?>
                                                            <!-- Fix admission status -->
                                                            <a class="dropdown-item text-warning" href="#" 
                                                            onclick="fixAdmissionStatusSingle(<?= $interview['id'] ?>, <?= $interview['admission_id'] ?>)">
                                                                <i class="fas fa-sync-alt"></i> Fix Admission Status
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                    <?php endif; ?>
                                                    
                                                    <!-- No Show Action (if status is no_show) -->
                                                    <?php if ($interview['status'] == 'no_show'): ?>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item" href="#" 
                                                        data-toggle="modal" data-target="#noShowActionModal<?= $interview['id'] ?>">
                                                            <i class="fas fa-redo"></i> Take Action
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Communication Log (always visible) -->
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="#" 
                                                    onclick="viewCommunicationLog(<?= $interview['id'] ?>)">
                                                        <i class="fas fa-comments text-info"></i> Communication Log
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            
                                        <?php else: ?>
                                            <!-- ========== READ-ONLY STATE: FINAL DECISION MADE ========== -->
                                            <button class="btn btn-secondary btn-circle" disabled title="Read Only - Final Decision Made">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                            
                                            <!-- View Student Button (only if approved) -->
                                            <?php if ($interview['admission_status'] == 2): ?>
                                                <?php 
                                                // Try to get student ID
                                                $student_query = $this->db->select('s.id')
                                                    ->from('student s')
                                                    ->join('online_admission oa', 'oa.first_name = s.first_name AND oa.last_name = s.last_name', 'left')
                                                    ->where('oa.id', $interview['admission_id'])
                                                    ->limit(1)
                                                    ->get();
                                                
                                                if ($student_query->num_rows() > 0) {
                                                    $student_id = $student_query->row()->id;
                                                ?>
                                                <a href="<?= base_url('student/view/' . $student_id) ?>" 
                                                class="btn btn-success btn-circle" title="View Student">
                                                    <i class="fas fa-user-graduate"></i>
                                                </a>
                                                <?php } ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                        <!-- Reschedule Modal for each interview -->
                                        <?php if (isset($interviewers) && is_array($interviewers)): ?>
                                        <div class="modal fade" id="rescheduleModal<?= $interview['id'] ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                        <h4 class="modal-title">Reschedule Interview</h4>
                                                    </div>
                                                    <form method="post" action="<?= base_url('online_admission/reschedule_interview/' . $interview['id']) ?>">
                                                    <input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>">   
                                                    <div class="modal-body">
                                                            <div class="form-group">
                                                                <label class="control-label">New Date *</label>
                                                                <input type="text" class="form-control datepicker" 
                                                                       name="interview_date" value="<?= date('Y-m-d', strtotime($interview['interview_date'])) ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="control-label">New Time *</label>
                                                                <input type="text" class="form-control timepicker" 
                                                                       name="interview_time" value="<?= date('H:i', strtotime($interview['interview_time'])) ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="control-label">Reason for Reschedule *</label>
                                                                <textarea class="form-control" name="reschedule_reason" rows="3" 
                                                                          placeholder="Why are you rescheduling this interview?" required></textarea>
                                                            </div>
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" name="change_interviewer" id="change_interviewer<?= $interview['id'] ?>">
                                                                <label class="form-check-label" for="change_interviewer<?= $interview['id'] ?>">
                                                                    Change Interviewer
                                                                </label>
                                                            </div>
                                                            <div class="form-group" id="interviewer_select<?= $interview['id'] ?>" style="display:none; margin-top:10px;">
                                                                <label class="control-label">Select Interviewer</label>
                                                                <select class="form-control" name="interviewer_id">
                                                                    <option value="">Select Interviewer</option>
                                                                    <?php foreach($interviewers as $interviewer): ?>
                                                                    <option value="<?= $interviewer['id'] ?>" <?= ($interview['interviewer_id'] == $interviewer['id']) ? 'selected' : '' ?>>
                                                                        <?= $interviewer['name'] ?>
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
                                        <?php endif; ?>
                                        
                                        <!-- No Show Action Modal -->
                                        <div class="modal fade" id="noShowActionModal<?= $interview['id'] ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                        <h4 class="modal-title">No-Show Action Required</h4>
                                                    </div>
                                                    <form method="post" action="<?= base_url('online_admission/handle_no_show/' . $interview['id']) ?>">
                                                    <input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>"> 
                                                    <div class="modal-body">
                                                            <p><strong>Student:</strong> <?= $interview['first_name'] . ' ' . $interview['last_name'] ?></p>
                                                            <p><strong>Interview Date:</strong> <?= date('d M Y', strtotime($interview['interview_date'])) ?></p>
                                                            <p><strong>Interview Time:</strong> <?= date('h:i A', strtotime($interview['interview_time'])) ?></p>
                                                            
                                                            <div class="form-group">
                                                                <label class="control-label">Action *</label>
                                                                <select class="form-control" name="no_show_action" required>
                                                                    <option value="">Select Action</option>
                                                                    <option value="reschedule">Reschedule Interview</option>
                                                                    <option value="decline">Decline Admission</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div id="rescheduleOptions<?= $interview['id'] ?>" style="display:none;">
                                                                <div class="form-group">
                                                                    <label class="control-label">New Date *</label>
                                                                    <input type="text" class="form-control datepicker" 
                                                                           name="reschedule_date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="control-label">New Time *</label>
                                                                    <input type="text" class="form-control timepicker" 
                                                                           name="reschedule_time" value="10:00">
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
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Showing <?= count($interviews) ?> interview(s). Click on student name to view details.
                        </small>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php if (count($interviews) > 0): ?>
                            <a href="<?= base_url('reports/interview_report') ?>" class="btn btn-default">
                                <i class="fas fa-chart-bar"></i> Generate Report
                            </a>
                            <button class="btn btn-default" onclick="window.print()">
                                <i class="fas fa-print"></i> Print List
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </footer>
        </section>
    </div>
</div>

<!-- Communication Log Modal -->
<div class="modal fade" id="communicationLogModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fas fa-comments"></i> Communication Log
                </h4>
            </div>
            <div class="modal-body" id="communicationLogContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>
<!-- Confirmation and Alert Modals -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="confirmModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmModalConfirm">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="alertModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="alertModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Initialize datepicker
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        startDate: '0d'
    });
    
    // Initialize timepicker
    if (typeof $.fn.timepicker !== 'undefined') {
        $('.timepicker').timepicker({
            showMeridian: false,
            minuteStep: 15
        });
    }
    
    // Date range picker
    if (typeof moment !== 'undefined' && typeof $.fn.daterangepicker !== 'undefined') {
        $('#daterange-btn').daterangepicker(
            {
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                startDate: moment().subtract(6, 'days'),
                endDate: moment()
            },
            function(start, end) {
                $('#date-range-text').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                var currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('from_date', start.format('YYYY-MM-DD'));
                currentUrl.searchParams.set('to_date', end.format('YYYY-MM-DD'));
                window.location.href = currentUrl.toString();
            }
        );
    }
    
    // Toggle change interviewer select
    $(document).on('change', 'input[name="change_interviewer"]', function() {
        var modalId = $(this).closest('.modal').attr('id');
        var interviewId = modalId.replace('rescheduleModal', '');
        
        if ($(this).is(':checked')) {
            $('#interviewer_select' + interviewId).show();
            $('#interviewer_select' + interviewId + ' select').prop('required', true);
        } else {
            $('#interviewer_select' + interviewId).hide();
            $('#interviewer_select' + interviewId + ' select').prop('required', false).val('');
        }
    });
    
    // No-show action change
    $(document).on('change', 'select[name="no_show_action"]', function() {
        var form = $(this).closest('form');
        var interviewId = form.attr('action').split('/').pop();
        if ($(this).val() === 'reschedule') {
            $('#rescheduleOptions' + interviewId).show();
        } else {
            $('#rescheduleOptions' + interviewId).hide();
        }
    });
});

// ========== COMPLETE INTERVIEW ==========
function completeInterviewSingle(interviewId, studentName, interviewDate, interviewTime) {
    showConfirm(
        'Complete Interview',
        'Are you sure you want to mark this interview as completed?<br><br>' +
        '<strong>Student:</strong> ' + studentName + '<br>' +
        '<strong>Date:</strong> ' + interviewDate + '<br>' +
        '<strong>Time:</strong> ' + interviewTime + '<br><br>' +
        'This will:<br>' +
        '• Set interview status to "completed"<br>' +
        '• Update admission status to 5 (Interview Completed)<br>' +
        '• Enable post-interview decision buttons',
        function() {
            $.ajax({
                url: '<?=base_url("online_admission/complete_interview/")?>' + interviewId,
                type: 'POST',
                data: {
                    <?=$this->security->get_csrf_token_name()?>: getCsrfToken()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('Success', 'Interview marked as completed!');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('Error', response.message);
                    }
                },
                error: function(xhr) {
                    showAlert('Error', 'Server error: ' + xhr.statusText);
                }
            });
        }
    );
}

// ========== SEND REMINDER ==========
function sendReminderSingle(interviewId) {
    showConfirm(
        'Send Reminder',
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
                success: function(response) {
                    if (response.success) {
                        showAlert('Success', 'Reminder sent successfully!');
                    } else {
                        showAlert('Error', response.message);
                    }
                },
                error: function(xhr) {
                    showAlert('Error', 'Server error: ' + xhr.statusText);
                }
            });
        }
    );
}

// ========== CANCEL INTERVIEW ==========
function cancelInterviewSingle(interviewId) {
    showPrompt(
        'Cancel Interview',
        'Please provide a reason for cancellation:',
        'Reason for cancellation...',
        function(reason) {
            if (!reason || reason.trim() === '') {
                showAlert('Error', 'Cancellation reason is required.');
                return;
            }
            
            showConfirm(
                'Confirm Cancellation',
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
                        success: function(response) {
                            if (response.success) {
                                showAlert('Success', 'Interview cancelled successfully!');
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                showAlert('Error', response.message);
                            }
                        },
                        error: function(xhr) {
                            showAlert('Error', 'Server error: ' + xhr.statusText);
                        }
                    });
                }
            );
        }
    );
}

// ========== MARK AS NO-SHOW ==========
function markNoShowSingle(interviewId) {
    showConfirm(
        'Mark as No-Show',
        'Mark this interview as No-Show?<br><br>' +
        'This indicates the candidate did not attend the scheduled interview.<br>' +
        'You will need to select an action (reschedule or decline) after marking.',
        function() {
            // Update via AJAX
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
                        showAlert('Success', 'Interview marked as No-Show. Please take action.');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('Error', response.message);
                    }
                },
                error: function(xhr) {
                    showAlert('Error', 'Server error: ' + xhr.statusText);
                }
            });
        }
    );
}

// ========== APPROVE AFTER INTERVIEW ==========
function approveAfterInterviewSingle(interviewId, studentName) {
    showConfirm(
        'Approve Admission',
        'Approve admission for ' + studentName + '?<br><br>' +
        'This will:<br>' +
        '• Create student account<br>' +
        '• Send approval SMS/Email<br>' +
        '• Update admission status to approved',
        function() {
            window.location.href = '<?=base_url("online_admission/approve_after_interview/")?>' + interviewId;
        }
    );
}

// ========== DECLINE AFTER INTERVIEW ==========
function declineAfterInterviewSingle(interviewId, studentName) {
    showConfirm(
        'Decline Admission',
        'Decline admission for ' + studentName + '?<br><br>' +
        'This will:<br>' +
        '• Update admission status to declined<br>' +
        '• Send decline notification SMS<br>' +
        '• Cannot be undone',
        function() {
            window.location.href = '<?=base_url("online_admission/decline_after_interview/")?>' + interviewId;
        }
    );
}

// ========== FIX ADMISSION STATUS ==========
function fixAdmissionStatusSingle(interviewId, admissionId) {
    showConfirm(
        'Fix Admission Status',
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
                        showAlert('Error', response.message);
                    }
                },
                error: function(xhr) {
                    showAlert('Error', 'Server error: ' + xhr.statusText);
                }
            });
        }
    );
}

function viewCommunicationLog(interviewId) {
    $.ajax({
        url: '<?=base_url("online_admission/get_communication_log/")?>' + interviewId,
        type: 'GET',
        dataType: 'html',
        beforeSend: function() {
            $('#communicationLogContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Loading...</p></div>');
        },
        success: function(response) {
            $('#communicationLogContent').html(response);
            $('#communicationLogModal').modal('show');
        },
        error: function() {
            $('#communicationLogContent').html('<div class="alert alert-danger">Failed to load communication log.</div>');
            $('#communicationLogModal').modal('show');
        }
    });
}

// Add CSRF token to all AJAX requests
$(document).ajaxSend(function(e, xhr, options) {
    if (options.type === 'POST') {
        xhr.setRequestHeader('X-CSRF-Token', $('input[name="csrf_test_name"]').val());
    }
});

// Get CSRF token helper
function getCsrfToken() {
    return $('input[name="<?=$this->security->get_csrf_token_name()?>"]').val();
}

// Modal helper functions (copy from interview_view.php if not present)
function showAlert(title, message) {
    $('#alertModalTitle').text(title || 'Notification');
    $('#alertModalBody').html(message || '');
    $('#alertModal').modal('show');
}

function showConfirm(title, message, confirmCallback, cancelCallback) {
    $('#confirmModalTitle').text(title || 'Confirm Action');
    $('#confirmModalBody').html(message || 'Are you sure?');
    
    $('#confirmModalConfirm').off('click').on('click', function() {
        $('#confirmModal').modal('hide');
        if (typeof confirmCallback === 'function') confirmCallback();
    });
    
    $('#confirmModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
        if (typeof cancelCallback === 'function') cancelCallback();
    });
    
    $('#confirmModal').modal('show');
}
</script>