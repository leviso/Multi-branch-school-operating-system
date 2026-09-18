

<?php
// Add these 2 lines at the top
$csrf_token = $this->security->get_csrf_hash();
$base_url = base_url();
?>
<?php if (is_superadmin_loggedin()): ?>
<div class="row mb-lg">
    <div class="col-md-3">
        <div class="form-group">
            <label><?=translate('branch')?></label>
            <select class="form-control" id="branchSelector">
                <option value="">All Branches</option>
                <?php 
                $branches = $this->db->order_by('name')->get('branch')->result_array();
                $selected_branch = $this->session->userdata('selected_branch');
                foreach ($branches as $branch): 
                ?>
                <option value="<?=$branch['id']?>" <?=($selected_branch == $branch['id']) ? 'selected' : ''?>>
                    <?=html_escape($branch['school_name'])?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary" id="applyBranchFilter">Apply Filter</button>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-file-medical"></i> Individual Academic Recovery Plan (IARP)
                    <span class="label label-info"><?=$iarp['plan_code']?></span>
                </h4>
                <div class="panel-btn">
                    <?php if ($iarp['status'] == 'draft'): ?>
                    <a href="<?=base_url('stars/activate_iarp/' . $iarp['id'])?>" class="btn btn-success" onclick="return confirm('Activate this IARP? Parent will be notified.')">
                        <i class="fas fa-play"></i> Activate IARP
                    </a>
                    <?php endif; ?>
                    <?php if ($iarp['status'] == 'active'): ?>
                    <a href="<?=base_url('stars/close_recovery/' . $iarp['id'])?>" class="btn btn-danger" onclick="return confirm('Close this recovery plan? This will mark it as completed.')">
                        <i class="fas fa-check-circle"></i> Close Recovery
                    </a>
                    <?php endif; ?>
                    <button onclick="window.print();" class="btn btn-default">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </header>
            <div class="panel-body">
                
                <!-- IARP Summary -->
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Student:</strong> <?=$iarp['first_name']?> <?=$iarp['last_name']?> (<?=$iarp['register_no']?>)<br>
                            <strong>Class/Section:</strong> <?=$iarp['class_name']?> <?=$iarp['section_name']?><br>
                            <strong>Plan Status:</strong> 
                            <span class="label label-<?=($iarp['status'] == 'active') ? 'success' : (($iarp['status'] == 'completed') ? 'info' : 'warning')?>">
                                <?=strtoupper($iarp['status'])?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Start Date:</strong> <?=date('d M Y', strtotime($iarp['start_date']))?><br>
                            <strong>Target End Date:</strong> <?=date('d M Y', strtotime($iarp['target_end_date']))?><br>
                            <strong>Created By:</strong> <?=$iarp['created_by_name']?>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#missingTopics" aria-controls="missingTopics" role="tab" data-toggle="tab">
                            <i class="fas fa-book"></i> Missing Topics (<?=count($iarp['missing_topics'])?>)
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#resources" aria-controls="resources" role="tab" data-toggle="tab">
                            <i class="fas fa-file-alt"></i> Resources (<?=count($iarp['resources'])?>)
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#progress" aria-controls="progress" role="tab" data-toggle="tab">
                            <i class="fas fa-chart-line"></i> Progress
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#mentorship" aria-controls="mentorship" role="tab" data-toggle="tab">
                            <i class="fas fa-users"></i> Peer Mentorship
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- Missing Topics Tab -->
                    <div role="tabpanel" class="tab-pane active" id="missingTopics">
                        <div class="table-responsive mt-md">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Topic</th>
                                        <th>Priority</th>
                                        <th>Est. Hours</th>
                                        <th>Assigned Teacher</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($iarp['missing_topics'] as $topic): ?>
                                    <tr id="topicRow_<?=$topic['id']?>">
                                        <td><?=$topic['subject_name']?></td>
                                        <td><?=$topic['topic_name']?></td>
                                        <td>
                                            <span class="label label-<?=($topic['priority'] == 'high') ? 'danger' : (($topic['priority'] == 'medium') ? 'warning' : 'info')?>">
                                                <?=strtoupper($topic['priority'])?>
                                            </span>
                                        </td>
                                        <td><?=$topic['estimated_hours']?> hrs</td>
                                        <td>
                                            <?php if ($topic['assigned_teacher_id']): ?>
                                                <?=html_escape($topic['teacher_name'])?>
                                                <?php if (is_admin_loggedin() || is_superadmin_loggedin()): ?>
                                                    <br>
                                                    <button class="btn btn-xs btn-warning reassign-teacher mt-xs" 
                                                        data-topic-id="<?=$topic['id']?>" 
                                                        data-current-teacher="<?=html_escape($topic['teacher_name'])?>">
                                                    <i class="fas fa-exchange-alt"></i> Reassign
                                                </button>
                                                <?php endif; ?>
                                            <?php elseif ($iarp['status'] == 'active'): ?>
                                                <select class="form-control assign-teacher input-sm" data-topic-id="<?=$topic['id']?>" style="width: 160px;">
                                                    <option value="">-- Select Teacher --</option>
                                                    <?php if (!empty($teachers)): ?>
                                                        <?php foreach ($teachers as $teacher): ?>
                                                        <option value="<?=$teacher['id']?>"><?=html_escape($teacher['name'])?></option>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <option value="" disabled>No teachers available</option>
                                                    <?php endif; ?>
                                                </select>
                                            <?php elseif ($iarp['status'] == 'draft'): ?>
                                                <span class="text-muted"><i class="fas fa-lock"></i> Activate IARP first</span>
                                            <?php else: ?>
                                                <span class="text-muted">Plan completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="label label-<?=($topic['status'] == 'completed') ? 'success' : (($topic['status'] == 'in_progress') ? 'info' : 'default')?>">
                                                <?=strtoupper($topic['status'])?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($topic['status'] == 'completed'): ?>
                                                <span class="label label-success">Completed</span>
                                            <?php elseif ($topic['assigned_teacher_id'] && $iarp['status'] == 'active'): ?>
                                                <!-- Has teacher assigned AND IARP is Active - show complete button -->
                                                <button class="btn btn-xs btn-success mark-complete" data-topic-id="<?=$topic['id']?>">
                                                    <i class="fas fa-check"></i> Complete
                                                </button>
                                            <?php elseif ($iarp['status'] == 'active' && !$topic['assigned_teacher_id']): ?>
                                                <!-- IARP Active but no teacher assigned yet -->
                                                <span class="text-muted">Assign teacher first</span>
                                            <?php elseif ($iarp['status'] == 'draft'): ?>
                                                <span class="text-muted">Activate first</span>
                                            <?php elseif ($iarp['status'] == 'completed'): ?>
                                                <span class="text-muted">Plan completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Resources Tab -->
                    <div role="tabpanel" class="tab-pane" id="resources">
                        <div class="mt-md">
                            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addResourceModal">
                                <i class="fas fa-plus"></i> Add Resource
                            </button>
                            <div class="clearfix"></div>
                            
                            <div class="table-responsive mt-md">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Title</th>
                                            <th>Subject</th>
                                            <th>Description</th>
                                            <th>Supplied By</th>
                                            <th>Date</th>
                                            <th>Download</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($iarp['resources'] as $res): ?>
                                        <tr>
                                            <td>
                                                <span class="label label-info"><?=ucfirst($res['resource_type'])?></span>
                                            </td>
                                            <td><?=$res['title']?></td>
                                            <td><?=get_type_name_by_id('subject', $res['subject_id'], 'name')?></td>
                                            <td><?=$res['description']?></td>
                                            <td><?=get_type_name_by_id('staff', $res['supplied_by'], 'name')?></td>
                                            <td><?=date('d M Y', strtotime($res['supplied_at']))?></td>
                                            <td>
                                                <?php if ($res['enc_file_name']): ?>
                                                <a href="<?=base_url('stars/download_resource/' . $res['id'])?>" class="btn btn-xs btn-default">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                <!-- Progress Tab -->
                <div role="tabpanel" class="tab-pane" id="progress">
                    <div class="mt-md">
                        
                        <?php if ($iarp['status'] == 'active'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Update weekly progress every Friday.
                        </div>
                        
                        <!-- Toggle between Simple and Detailed Mode (Optional Feature) -->
                        <div class="mb-md" style="background: #f9f9f9; padding: 10px; border-radius: 4px;">
                            <label class="checkbox-inline">
                                <input type="checkbox" id="toggleDetailedProgress"> 
                                <strong>Enable Subtopics Tracking</strong>
                            </label>
                            <small class="text-muted ml-md">Track progress at subtopic level for more granular reporting</small>
                        </div>
                        
                        <!-- ========== SIMPLE PROGRESS FORM (Existing - Topics Only) ========== -->
                        <div id="simpleProgressForm">
                            <form id="progressForm">
                                <input type="hidden" name="iarp_id" value="<?=$iarp['id']?>">
                                <input type="hidden" name="student_id" value="<?=$iarp['student_id']?>">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Week Number</label>
                                            <input type="number" name="week_number" class="form-control" 
                                                value="<?=count($iarp['progress']) + 1?>" required min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Topics Covered This Week</label>
                                            <input type="number" name="topics_covered" class="form-control" required min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Teacher Comments</label>
                                            <textarea name="teacher_comments" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save Weekly Progress
                                </button>
                            </form>
                        </div>
                        
                        <!-- ========== DETAILED PROGRESS FORM (New - Subtopics Level) ========== -->
                        <div id="detailedProgressForm" style="display: none;">
                            <form id="detailedProgressFormSubmit">
                                <input type="hidden" name="iarp_id" value="<?=$iarp['id']?>">
                                <input type="hidden" name="student_id" value="<?=$iarp['student_id']?>">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Week Number</label>
                                            <input type="number" name="week_number" class="form-control" 
                                                value="<?=count($iarp['progress']) + 1?>" required min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-group">
                                            <label>Select Subtopic(s) Completed This Week</label>
                                            <select name="subtopics_completed[]" multiple class="form-control" style="height: 150px;">
                                                <?php 
                                                // Get all pending subtopics for this IARP
                                                $subtopics_sql = "SELECT cs.*, ct.topic_name, ct.id as topic_id
                                                                FROM curriculum_subtopics cs
                                                                INNER JOIN curriculum_topics ct ON ct.id = cs.topic_id
                                                                INNER JOIN iarp_missing_topics imt ON imt.topic_id = cs.topic_id
                                                                WHERE imt.iarp_id = ? AND imt.status != 'completed'
                                                                ORDER BY ct.topic_name, cs.subtopic_order";
                                                $subtopics_list = $this->db->query($subtopics_sql, array($iarp['id']))->result_array();
                                                if (!empty($subtopics_list)):
                                                    foreach ($subtopics_list as $st):
                                                ?>
                                                <option value="<?=$st['id']?>"><?=html_escape($st['topic_name'])?> - <?=html_escape($st['subtopic_name'])?> (<?=$st['expected_hours']?> hrs)</option>
                                                <?php 
                                                    endforeach;
                                                else:
                                                ?>
                                                <option value="" disabled>No subtopics available. Add subtopics first.</option>
                                                <?php endif; ?>
                                            </select>
                                            <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple subtopics</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Teacher Comments</label>
                                            <textarea name="teacher_comments" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Subtopic Progress
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Progress Table -->
                        <div class="table-responsive mt-lg">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="8%">Week</th>
                                        <th width="15%">Period</th>
                                        <th width="30%">Topics/Subtopics Covered</th>
                                        <th width="30%">Remaining</th>
                                        <th width="8%">Improvement</th>
                                        <th width="9%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($weekly_progress)): ?>
                                    <tr class="text-center">
                                        <td colspan="6" class="text-muted">No progress records yet.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($weekly_progress as $prog): ?>
                                        <tr>
                                            <td class="text-center"><strong>Week <?=$prog['week_number']?></strong></td>
                                            <td><?=date('d M', strtotime($prog['week_start_date']))?> - <?=date('d M', strtotime($prog['week_end_date']))?></td>
                                            <td>
                                                <?php if (!empty($prog['completed_items'])): ?>
                                                    <?php foreach ($prog['completed_items'] as $item): ?>
                                                        <div>
                                                            <i class="fas fa-check-circle text-success"></i>
                                                            <?=html_escape($item['topic_name'])?> - <?=html_escape($item['subtopic_name'])?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($prog['remaining_items'])): ?>
                                                    <?php foreach ($prog['remaining_items'] as $item): ?>
                                                        <div>
                                                            <i class="fas fa-hourglass-half text-warning"></i>
                                                            <?=html_escape($item['topic_name'])?> - <?=html_escape($item['subtopic_name'])?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-success">All completed! 🎉</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="progress" style="margin-bottom: 0; height: 6px; width: 80px; display: inline-block;">
                                                    <div class="progress-bar progress-bar-success" role="progressbar" 
                                                        style="width: <?=round($prog['improvement_percentage'], 1)?>%;">
                                                    </div>
                                                </div>
                                                <small><?=round($prog['improvement_percentage'], 1)?>%</small>
                                            </td>
                                            <td>
                                                <span class="label label-<?=($prog['status'] == 'on_track') ? 'success' : (($prog['status'] == 'slightly_behind') ? 'warning' : 'danger')?>">
                                                    <?=str_replace('_', ' ', ucfirst(html_escape($prog['status'])))?>
                                                </span>
                                            </td>
                                        </tr>
                                        <!-- Teacher Comments Row -->
                                        <tr class="active">
                                            <td colspan="6" style="background: #f9f9f9;">
                                                <strong><i class="fas fa-comment"></i> Teacher Comments:</strong> <?=nl2br(html_escape($prog['teacher_comments']))?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                    
                    <!-- Mentorship Tab -->
                    <div role="tabpanel" class="tab-pane" id="mentorship">
                        <div class="mt-md">
                            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addMentorshipModal">
                                <i class="fas fa-user-plus"></i> Assign Mentor
                            </button>
                            <div class="clearfix"></div>
                            
                            <div class="table-responsive mt-md">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Mentor Student</th>
                                            <th>Subject</th>
                                            <th>Assigned Date</th>
                                            <th>Meetings</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mentorshipTable">
                                        <?php 
                                        $this->db->where('transfer_student_id', $iarp['student_id']);
                                        $mentorships = $this->db->get('peer_mentorships')->result_array();
                                        foreach ($mentorships as $ms): 
                                        ?>
                                        <tr>
                                            <td><?=get_type_name_by_id('student', $ms['mentor_student_id'], 'first_name')?> 
                                                <?=get_type_name_by_id('student', $ms['mentor_student_id'], 'last_name')?></td>
                                            <td><?=get_type_name_by_id('subject', $ms['subject_id'], 'name')?></td>
                                            <td><?=date('d M Y', strtotime($ms['assigned_date']))?></td>
                                            <td><?=$ms['meetings_count']?></td>
                                            <td>
                                                <span class="label label-<?=($ms['status'] == 'active') ? 'success' : 'default'?>">
                                                    <?=strtoupper($ms['status'])?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-xs btn-info log-meeting" data-mentorship-id="<?=$ms['id']?>">
                                                    <i class="fas fa-calendar-plus"></i> Log Meeting
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </section>
    </div>
    <!-- Reassign Teacher Modal -->
<div class="modal fade" id="reassignTeacherModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="fas fa-exchange-alt"></i> Reassign Teacher</h4>
            </div>
            <div class="modal-body">
                <form id="reassignTeacherForm">
                    <input type="hidden" name="missing_topic_id" id="reassign_topic_id">
                    <div class="form-group">
                        <label>Current Teacher</label>
                        <input type="text" class="form-control" id="current_teacher_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>New Teacher <span class="required">*</span></label>
                        <select name="teacher_id" id="new_teacher_id" class="form-control" required>
                            <option value="">-- Select New Teacher --</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?=$teacher['id']?>"><?=html_escape($teacher['name'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reason for Reassignment</label>
                        <textarea name="reason" id="reassign_reason" class="form-control" rows="2" placeholder="e.g., Teacher left the school, workload adjustment..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitReassignBtn">Reassign Teacher</button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Add Resource Modal -->
<div class="modal fade" id="addResourceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fas fa-file-alt"></i> Add Resource</h4>
            </div>
            <div class="modal-body">
                <form id="resourceForm" enctype="multipart/form-data">
                    <input type="hidden" name="iarp_id" value="<?=$iarp['id']?>">
                    <input type="hidden" name="student_id" value="<?=$iarp['student_id']?>">
                    <input type="hidden" name="csrf_test_name" value="<?=$csrf_token?>">
                    
                    <div class="form-group">
                        <label>Resource Type</label>
                        <select name="resource_type" class="form-control" required>
                            <option value="notes">Notes</option>
                            <option value="textbook">Textbook</option>
                            <option value="practical_record">Practical Record</option>
                            <option value="worksheet">Worksheet</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject_id" class="form-control">
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $sub): ?>
                            <option value="<?=$sub['id']?>"><?=$sub['name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>File (PDF/DOC/Image)</label>
                        <input type="file" name="resource_file" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitResourceBtn">Add Resource</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Mentorship Modal -->
<div class="modal fade" id="addMentorshipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fas fa-user-plus"></i> Assign Peer Mentor</h4>
            </div>
            <div class="modal-body">
                <form id="mentorshipForm">
                    <input type="hidden" name="transfer_student_id" value="<?=$iarp['student_id']?>">
                    <input type="hidden" name="iarp_id" value="<?=$iarp['id']?>">
                     <input type="hidden" name="csrf_test_name" value="<?=$csrf_token?>">
                    
                    <div class="form-group">
                        <label>Mentor Student</label>
                        <select name="mentor_student_id" id="mentorStudentSelect" class="form-control" required>
                            <option value="">Loading available students...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject (Optional)</label>
                        <select name="subject_id" class="form-control">
                            <option value="">-- All Subjects --</option>
                            <?php foreach ($subjects as $sub): ?>
                            <option value="<?=$sub['id']?>"><?=html_escape($sub['name'])?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Leave empty for general mentorship</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitMentorshipBtn">Assign Mentor</button>
            </div>
        </div>
    </div>
</div>

<script>
var base_url = '<?=$base_url?>';
var csrf_token = '<?=$csrf_token?>';

$(document).ready(function() {
    
    // ========== ASSIGN TEACHER ==========
    $('.assign-teacher').on('change', function() {
        var $select = $(this);
        var topicId = $select.data('topic-id');
        var teacherId = $select.val();
        var teacherName = $select.find('option:selected').text();
        
        if (!teacherId) {
            return;
        }
        
        $select.prop('disabled', true);
        
        $.ajax({
            url: base_url + 'stars/assign_teacher',
            type: 'POST',
            data: {
                missing_topic_id: topicId,
                teacher_id: teacherId,
                csrf_test_name: csrf_token
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var $td = $select.closest('td');
                    $td.html('<span>' + response.teacher_name + '</span>');
                    
                    var $statusCell = $select.closest('tr').find('td:nth-child(6)');
                    $statusCell.html('<span class="label label-info">IN PROGRESS</span>');
                    
                    var $actionsCell = $select.closest('tr').find('td:last-child');
                    $actionsCell.html('<button class="btn btn-xs btn-success mark-complete" data-topic-id="' + topicId + '"><i class="fas fa-check"></i> Complete</button>');
                    
                    showNotification('Teacher assigned successfully', 'success');
                } else {
                    showNotification(response.message || 'Failed to assign teacher', 'error');
                    $select.prop('disabled', false);
                }
            },
            error: function() {
                showNotification('Server error. Please try again.', 'error');
                $select.prop('disabled', false);
            }
        });
    });
    
    // ========== MARK TOPIC COMPLETE ==========
    $(document).on('click', '.mark-complete', function() {
        var $btn = $(this);
        var topicId = $btn.data('topic-id');
        var $row = $('#topicRow_' + topicId);
        
        if (confirm('Mark this topic as completed?')) {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: base_url + 'stars/complete_topic',
                type: 'POST',
                data: {
                    missing_topic_id: topicId,
                    csrf_test_name: csrf_token
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(400, function() {
                            $(this).remove();
                            
                            var $countBadge = $('a[href="#missingTopics"]');
                            var currentCount = parseInt($countBadge.text().match(/\d+/)) || 0;
                            var newCount = currentCount - 1;
                            $countBadge.html('<i class="fas fa-book"></i> Missing Topics (' + newCount + ')');
                            
                            if (newCount === 0) {
                                $('#missingTopics .table-responsive').html('<div class="alert alert-success text-center">All topics completed! You can now close this recovery plan.</div>');
                            }
                        });
                        showNotification('Topic marked as completed', 'success');
                    } else {
                        showNotification(response.message || 'Failed to mark topic', 'error');
                        $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Complete');
                    }
                },
                error: function() {
                    showNotification('Server error. Please try again.', 'error');
                    $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Complete');
                }
            });
        }
    });
    
    // ========== REASSIGN TEACHER (Modal Handler) ==========
    // Open modal when reassign button is clicked
    $(document).on('click', '.reassign-teacher', function(e) {
        e.preventDefault();
        
        var topicId = $(this).data('topic-id');
        var currentTeacher = $(this).data('current-teacher');
        
        console.log('Reassign button clicked - Topic ID:', topicId);
        
        // Set values in modal
        $('#reassign_topic_id').val(topicId);
        $('#current_teacher_name').val(currentTeacher);
        $('#new_teacher_id').val('');
        $('#reassign_reason').val('');
        
        // Show modal using Bootstrap
        $('#reassignTeacherModal').modal('show');
    });
    
    // Submit reassign
    $('#submitReassignBtn').off('click').on('click', function() {
        var $btn = $(this);
        var topicId = $('#reassign_topic_id').val();
        var newTeacherId = $('#new_teacher_id').val();
        var reason = $('#reassign_reason').val();
        
        if (!newTeacherId) {
            alert('Please select a new teacher');
            return;
        }
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Reassigning...');
        
        $.ajax({
            url: base_url + 'stars/reassign_teacher',
            type: 'POST',
            data: {
                missing_topic_id: topicId,
                teacher_id: newTeacherId,
                reason: reason,
                csrf_test_name: csrf_token
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#reassignTeacherModal').modal('hide');
                    showNotification('Teacher reassigned successfully', 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showNotification(response.message || 'Reassignment failed', 'error');
                    $btn.prop('disabled', false).html('Reassign Teacher');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr.responseText);
                showNotification('Server error. Please try again.', 'error');
                $btn.prop('disabled', false).html('Reassign Teacher');
            }
        });
    });
    
    // ========== WEEKLY PROGRESS ==========
    $('#progressForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: base_url + 'stars/update_progress',
            type: 'POST',
            data: $(this).serialize() + '&csrf_test_name=' + csrf_token,
            success: function(response) {
                if (response.success) {
                    showNotification('Weekly progress saved', 'success');
                    location.reload();
                }
            }
        });
    });
    
    // ========== ADD RESOURCE ==========
    $('#submitResourceBtn').click(function() {
        var $btn = $(this);
        
        // Get fresh CSRF token from the form
        var csrfToken = $('input[name="csrf_test_name"]').val();
        if (!csrfToken) {
            // Try to get from meta tag or global variable
            csrfToken = typeof csrf_token !== 'undefined' ? csrf_token : '';
        }
        
        console.log('CSRF Token being sent:', csrfToken);
        
        var formData = new FormData($('#resourceForm')[0]);
        formData.append('csrf_test_name', csrfToken);
        
        // Validate required fields
        var resourceType = $('select[name="resource_type"]').val();
        var title = $('input[name="title"]').val();
        
        if (!resourceType) {
            showNotification('Please select resource type', 'error');
            return;
        }
        if (!title) {
            showNotification('Please enter title', 'error');
            return;
        }
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
        
        $.ajax({
            url: base_url + 'stars/add_resource',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.success) {
                    $('#addResourceModal').modal('hide');
                    showNotification('Resource added successfully', 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showNotification(response.message || 'Failed to add resource', 'error');
                    $btn.prop('disabled', false).html('Add Resource');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr.status);
                console.error('Response:', xhr.responseText);
                
                if (xhr.status === 403) {
                    showNotification('Session expired. Refreshing page...', 'warning');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showNotification('Server error. Please try again.', 'error');
                }
                $btn.prop('disabled', false).html('Add Resource');
            }
        });
    });
    
    // ========== LOAD MENTORS ==========
    $('#addMentorshipModal').on('show.bs.modal', function() {
        var classId = '<?=$iarp['class_id']?>';
        var transferStudentId = '<?=$iarp['student_id']?>';
        var branchId = '<?=$iarp['branch_id'] ?? $filter_branch_id?>';
        
        console.log('Loading mentors - Class ID:', classId, 'Branch ID:', branchId);
        
        if (!classId) {
            $('#mentorStudentSelect').html('<option value="" disabled>No class assigned to this student</option>');
            return;
        }
        
        $('#mentorStudentSelect').html('<option value="">Loading students...</option>');
        
        $.ajax({
            url: base_url + 'stars/get_available_mentors',
            type: 'POST',
            data: {
                class_id: classId,
                transfer_student_id: transferStudentId,
                branch_id: branchId,
                csrf_test_name: csrf_token
            },
            dataType: 'json',
            success: function(data) {
                console.log('Mentors response:', data);
                
                var options = '<option value="">-- Select Mentor --</option>';
                if (data && data.length > 0) {
                    $.each(data, function(i, mentor) {
                        options += '<option value="' + mentor.id + '">' + 
                                mentor.first_name + ' ' + mentor.last_name + 
                                ' (Roll: ' + (mentor.roll || 'N/A') + ')</option>';
                    });
                } else {
                    options = '<option value="" disabled>No other students available in this class</option>';
                }
                $('#mentorStudentSelect').html(options);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#mentorStudentSelect').html('<option value="" disabled>Error loading students</option>');
            }
        });
    });
    
    // ========== ASSIGN MENTOR ==========
   // Assign mentor
    $('#submitMentorshipBtn').click(function() {
        var $btn = $(this);
        var mentorId = $('#mentorStudentSelect').val();
        
        if (!mentorId) {
            showNotification('Please select a mentor student', 'error');
            return;
        }
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Assigning...');
        
        $.ajax({
            url: base_url + 'stars/add_mentorship',
            type: 'POST',
            data: $('#mentorshipForm').serialize() + '&csrf_test_name=' + csrf_token,  // ← CSRF token here
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addMentorshipModal').modal('hide');
                    showNotification('Mentor assigned successfully', 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showNotification(response.message || 'Failed to assign mentor', 'error');
                    $btn.prop('disabled', false).html('Assign Mentor');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr.status, xhr.responseText);
                showNotification('Server error. Please try again.', 'error');
                $btn.prop('disabled', false).html('Assign Mentor');
            }
        });
    });
    
    // ========== LOG MEETING ==========
    // Log meeting - with better error handling
    $(document).on('click', '.log-meeting', function() {
        var $btn = $(this);
        var mentorshipId = $btn.data('mentorship-id');
        var topics = prompt('Enter topics covered in this meeting:');
        
        if (topics && topics.trim()) {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: base_url + 'stars/log_meeting',
                type: 'POST',
                data: {
                    mentorship_id: mentorshipId,
                    topics_covered: topics,
                    csrf_test_name: csrf_token
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Meeting logged successfully', 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showNotification(response.message || 'Failed to log meeting', 'error');
                        $btn.prop('disabled', false).html('<i class="fas fa-calendar-plus"></i> Log Meeting');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    showNotification('Server error. Please try again.', 'error');
                    $btn.prop('disabled', false).html('<i class="fas fa-calendar-plus"></i> Log Meeting');
                }
            });
        }
    });
        
    // ========== BRANCH FILTER ==========
    $('#applyBranchFilter').click(function() {
        var branchId = $('#branchSelector').val();
        var $btn = $(this);
        var originalText = $btn.html();
        
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
                    alert('Failed to apply filter: ' + (response.message || 'Unknown error'));
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('Error applying filter.');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ========== SUBTOPICS TOGGLE FUNCTIONALITY ==========
// Toggle between simple and detailed progress forms
$('#toggleDetailedProgress').change(function() {
    if ($(this).is(':checked')) {
        $('#simpleProgressForm').hide();
        $('#detailedProgressForm').show();
        // Store preference in localStorage
        localStorage.setItem('subtopic_tracking_enabled', '1');
    } else {
        $('#simpleProgressForm').show();
        $('#detailedProgressForm').hide();
        localStorage.setItem('subtopic_tracking_enabled', '0');
    }
});

// Check localStorage for saved preference on page load
$(document).ready(function() {
    var savedPreference = localStorage.getItem('subtopic_tracking_enabled');
    if (savedPreference === '1') {
        $('#toggleDetailedProgress').prop('checked', true);
        $('#simpleProgressForm').hide();
        $('#detailedProgressForm').show();
    }
});

// Handle detailed progress submission
$('#detailedProgressFormSubmit').submit(function(e) {
    e.preventDefault();
    
    var $btn = $(this).find('button[type="submit"]');
    var selectedSubtopics = $('select[name="subtopics_completed[]"]').val();
    
    if (!selectedSubtopics || selectedSubtopics.length === 0) {
        alert('Please select at least one subtopic completed this week');
        return;
    }
    
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    
    $.ajax({
        url: base_url + 'stars/update_subtopic_progress',
        type: 'POST',
        data: $(this).serialize() + '&csrf_test_name=' + csrf_token,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('Subtopic progress saved successfully', 'success');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showNotification(response.message || 'Failed to save progress', 'error');
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Subtopic Progress');
            }
        },
        error: function(xhr) {
            console.error('AJAX Error:', xhr.responseText);
            showNotification('Server error. Please try again.', 'error');
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Subtopic Progress');
        }
    });
});
});

// ========== HELPER FUNCTIONS (Outside document.ready) ==========
// Helper function for notifications - SIMPLE VERSION
function showNotification(message, type) {
    // Simple alert for debugging
    alert(message);
    
    // Or use console log
    console.log('Notification:', type, '-', message);
}


</script>