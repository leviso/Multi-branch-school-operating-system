<?php
// Check if IARP exists (passed from controller)
$has_iarp = isset($has_iarp) ? $has_iarp : false;
?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-chart-line"></i> My Academic Recovery Plan
                </h4>
            </header>
            <div class="panel-body">

                <!-- Display flash messages -->
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
                
                <?php if (!$has_iarp): ?>
                
                <!-- ========== EMPTY STATE - NO IARP FOUND ========== -->
                <div class="alert alert-info text-center" style="padding: 40px 20px;">
                    <i class="fas fa-info-circle fa-4x" style="color: #17a2b8;"></i>
                    <h4 style="margin-top: 15px;">No Recovery Plan Found</h4>
                    <p>You don't have an active academic recovery plan at this time.</p>
                    <p>If you are a transfer student or need academic support, please contact your class teacher.</p>
                </div>
                <!-- ========== END EMPTY STATE ========== -->
                
                <?php else: ?>
                
                <!-- ========== EXISTING IARP CONTENT ========== -->
                
                <!-- IARP Summary -->
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Plan Code:</strong> <?=html_escape($iarp['plan_code'])?><br>
                            <strong>Status:</strong> 
                            <span class="label label-<?=($iarp['status'] == 'active') ? 'success' : 'info'?>">
                                <?=strtoupper(html_escape($iarp['status']))?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Start Date:</strong> <?=date('d M Y', strtotime($iarp['start_date']))?><br>
                            <strong>Target End Date:</strong> <?=date('d M Y', strtotime($iarp['target_end_date']))?>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Overview -->
                <div class="headers-line">
                    <i class="fas fa-chart-simple"></i> My Progress
                </div>
                
                <div class="row mt-md">
                    <div class="col-md-12">
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar progress-bar-success progress-bar-striped" 
                                 role="progressbar" 
                                 style="width: <?=$progress_percent?>%;">
                                <?=$progress_percent?>% Complete
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-md">
                    <div class="col-md-3 text-center">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <h3><?=$total_topics?></h3>
                                <p>Total Topics</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <h3 class="text-success"><?=$completed_topics?></h3>
                                <p>Completed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <h3 class="text-info"><?=$in_progress_topics?></h3>
                                <p>In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <h3 class="text-warning"><?=$pending_topics?></h3>
                                <p>Pending</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Topics List -->
                <div class="headers-line mt-md">
                    <i class="fas fa-book"></i> Topics to Cover
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Topic</th>
                                <th>Priority</th>
                                <th>Teacher</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_topics)): ?>
                                <?php foreach ($all_topics as $topic): ?>
                                <tr>
                                    <td><?=html_escape($topic['subject_name'])?></td>
                                    <td><?=html_escape($topic['topic_name'])?></td>
                                    <td>
                                        <span class="label label-<?=($topic['priority'] == 'high') ? 'danger' : (($topic['priority'] == 'medium') ? 'warning' : 'info')?>">
                                            <?=strtoupper(html_escape($topic['priority']))?>
                                        </span>
                                    </td>
                                    <td><?=html_escape($topic['teacher_name'] ?? 'Not assigned')?></td>
                                    <td>
                                        <span class="label label-<?=($topic['status'] == 'completed') ? 'success' : (($topic['status'] == 'in_progress') ? 'info' : 'default')?>">
                                            <?=strtoupper(html_escape($topic['status']))?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No topics found. Your recovery plan is complete! 🎉</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Weekly Progress -->
                <?php if (!empty($progress_records)): ?>
                <div class="headers-line mt-md">
                    <i class="fas fa-calendar-week"></i> My Weekly Progress
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="10%">Week</th>
                                <th width="15%">Period</th>
                                <th width="35%">What I Completed</th>
                                <th width="35%">What's Remaining</th>
                                <th width="5%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($progress_records as $prog): ?>
                            <tr>
                                <td class="text-center"><strong>Week <?=$prog['week_number']?></strong></td>
                                <td><?=date('d M Y', strtotime($prog['week_start_date']))?> - <?=date('d M Y', strtotime($prog['week_end_date']))?></td>
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
                                <td class="text-center">
                                    <span class="label label-<?=($prog['status'] == 'on_track') ? 'success' : 'warning'?>">
                                        <?=str_replace('_', ' ', ucfirst($prog['status']))?>
                                    </span>
                                </td>
                            </tr>
                            <!-- Teacher Comments Row -->
                            <tr class="active">
                                <td colspan="5" style="background: #f9f9f9;">
                                    <strong><i class="fas fa-comment"></i> Teacher's Note:</strong> <?=nl2br(html_escape($prog['teacher_comments']))?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-warning mt-md">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note:</strong> Work with your assigned teachers to complete these topics. 
                    Your progress will be updated weekly.
                </div>
                
                <?php endif; ?>
                <!-- ========== END IARP CONTENT ========== -->
                
            </div>
        </section>
    </div>
</div>