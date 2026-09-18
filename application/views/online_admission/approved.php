<!-- Add this temporarily at top of view file -->
<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">
    <strong>Applicants Summary Info:</strong><br>
    Expected Admission ID: <?=$stuDetails['id'] ?? 'NOT FOUND'?><br>
    Student Name: <?=$stuDetails['first_name'] ?? 'N/A'?> <?=$stuDetails['last_name'] ?? ''?><br>
    
</div>

<?php $branchID = $stuDetails['branch_id']; ?>
<!-- NAVIGATION BAR -->
<div class="row mb-md">
    <div class="col-md-12">
        <div class="btn-group" role="group" aria-label="Navigation">
            <a href="<?=base_url('online_admission');?>" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> Back to Admissions
            </a>
            
            <a href="<?=base_url('online_admission/interviews');?>" class="btn btn-info">
                <i class="fas fa-calendar-alt"></i> View Interviews
            </a>
            
            <?php 
            $status = $stuDetails['status'];
            
            // Only show actions dropdown for status 1,4,5
            if(in_array($status, [1, 4, 5])):  
            ?>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-cog"></i> Actions
                </button>
                <div class="dropdown-menu">
                    <?php if($status == 1): ?>
                        <!-- Check for existing interview first -->
                        <?php 
                        $existing_interview = $this->db->select('id')
                            ->where('admission_id', $stuDetails['id'])
                            ->where('branch_id', $branchID)
                            ->where_in('status', ['scheduled', 'rescheduled'])
                            ->get('online_admission_interviews')
                            ->num_rows();
                        ?>
                        
                        <?php if($existing_interview == 0): ?>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#interviewModal">
                                <i class="fas fa-calendar-plus"></i> Schedule Interview
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        
                        <a class="dropdown-item" href="#" id="declineBtnDropdown">
                            <i class="fas fa-times-circle text-danger"></i> Decline Admission
                        </a>
                    <?php endif; ?>
                    
                    <?php if($status == 4): 
                        // Get active interview ID
                        $interview = $this->db->select('id')
                            ->where('admission_id', $stuDetails['id'])
                            ->where('branch_id', $branchID) // BRANCH CHECK
                            ->where_in('status', ['scheduled', 'rescheduled'])
                            ->get('online_admission_interviews')
                            ->row();
                        if($interview):
                    ?>
                        <a class="dropdown-item" href="<?=base_url('online_admission/interview/'.$interview->id)?>">
                            <i class="fas fa-eye"></i> View Interview
                        </a>
                        <!-- Removed Reschedule from dropdown - only via interview page -->
                    <?php endif; endif; ?>
                    
                    <?php if($status == 5): ?>
                        <a class="dropdown-item" href="#" id="declineBtnDropdown">
                            <i class="fas fa-times-circle text-danger"></i> Decline Admission
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Status Badge -->
        <div class="pull-right mt-sm">
            <?php
            $status_badge = '';
            switch($status) {
                case 1: $status_badge = '<span class="badge badge-secondary">Pending</span>'; break;
                case 2: $status_badge = '<span class="badge badge-success">Approved</span>'; break;
                case 3: $status_badge = '<span class="badge badge-danger">Declined</span>'; break;
                case 4: $status_badge = '<span class="badge badge-info">Interview Scheduled</span>'; break;
                case 5: $status_badge = '<span class="badge badge-warning">Interview Completed</span>'; break;
                default: $status_badge = '<span class="badge badge-dark">Unknown</span>';
            }
            echo $status_badge;
            ?>
            
            <!-- Status-Based Action Buttons (Following Project Context) -->
            <div class="mt-md">
                <?php 
                // ========== STATUS 1: PENDING ==========
                if ($status == 1): ?>
                    <!-- Check if interview already exists -->
                    <?php 
                    $existing_interview = $this->db->select('id')
                        ->where('admission_id', $stuDetails['id'])
                        ->where('branch_id', $branchID)
                        ->where_in('status', ['scheduled', 'rescheduled'])
                        ->get('online_admission_interviews')
                        ->num_rows();
                    
                    if($existing_interview == 0):
                    ?>
                        <button data-toggle="modal" data-target="#interviewModal" class="btn btn-info mr-2">
                            <i class="fas fa-calendar-plus"></i> Schedule Interview
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($status == 1): ?>
                        <button id="approveDirectBtn" class="btn btn-success mr-2" data-action="direct">
                            <i class="fas fa-check"></i> Approve Directly
                        </button>
                        <button id="declineBtn" class="btn btn-danger" data-action="decline">
                            <i class="fas fa-times"></i> Decline
                        </button>
                    <?php endif; ?>

                    <?php if ($status == 5): ?>
                        <button id="approveAfterInterviewBtn" class="btn btn-success mr-2">
                            <i class="fas fa-check"></i> Approve After Interview
                        </button>
                    <?php endif; ?>
                
                <?php 
                // ========== STATUS 4: INTERVIEW SCHEDULED ==========
                elseif ($status == 4): 
                    $interview = $this->db->select('id')
                        ->where('admission_id', $stuDetails['id'])
                        ->where('branch_id', $branchID) // BRANCH CHECK
                        ->where_in('status', ['scheduled', 'rescheduled'])
                        ->get('online_admission_interviews')
                        ->row();
                    if($interview):
                ?>
                    <a href="<?=base_url('online_admission/interview/'.$interview->id)?>" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Interview
                    </a>
                    <!-- NO RESCHEDULE BUTTON HERE - only via interview page -->
                <?php endif; endif; ?>
                
                <?php  
				// ========== STATUS 5: INTERVIEW COMPLETED ==========
				if ($status == 5): 
					// CRITICAL: Check if interview is actually completed
					$interview = $this->db->select('i.*')
						->from('online_admission_interviews i')
						->where('i.admission_id', $stuDetails['id'])
						->where('i.status', 'completed')
						->order_by('i.id', 'DESC')
						->get()
						->row_array();
					
					if (!empty($interview)): 
				?>
					<div class="alert alert-warning mb-3">
						<i class="fas fa-info-circle"></i> 
						<strong>Interview Completed</strong> - Ready for final decision.
						
						<?php if ($interview['outcome'] == 'recommended'): ?>
							<span class="badge badge-success ml-2">Recommended</span>
						<?php elseif ($interview['outcome'] == 'not_recommended'): ?>
							<span class="badge badge-danger ml-2">Not Recommended</span>
						<?php elseif ($interview['outcome'] == 'waitlist'): ?>
							<span class="badge badge-warning ml-2">Waitlist</span>
						<?php else: ?>
							<span class="badge badge-secondary ml-2">Pending Outcome</span>
						<?php endif; ?>
						
						<a href="<?=base_url('online_admission/interview/' . $interview['id'])?>" 
						class="btn btn-xs btn-info ml-2">
							<i class="fas fa-external-link-alt"></i> View Interview Details
						</a>
					</div>
					
					<?php if ($interview['outcome'] == 'recommended'): ?>
						<button id="approveAfterInterviewBtn" class="btn btn-success mr-2" 
								data-action="after_interview">
							<i class="fas fa-check"></i> Approve After Interview
						</button>
					<?php endif; ?>
					
					<?php if (in_array($interview['outcome'], ['not_recommended', 'pending', 'waitlist'])): ?>
						<button id="declineAfterBtn" class="btn btn-danger" data-action="decline_after">
							<i class="fas fa-times"></i> Decline After Interview
						</button>
					<?php endif; ?>
				<?php 
					else: 
						// Interview not completed yet
				?>
					<div class="alert alert-info mb-3">
						<i class="fas fa-clock"></i> 
						<strong>No completed interview found.</strong> Please mark the interview as completed before making a decision.
						<?php 
						$scheduled_interview = $this->db->select('id')
							->from('online_admission_interviews')
							->where('admission_id', $stuDetails['id'])
							->where_in('status', ['scheduled', 'rescheduled'])
							->get()
							->row();
						if ($scheduled_interview): ?>
						<a href="<?=base_url('online_admission/interview/' . $scheduled_interview->id)?>" 
						class="btn btn-xs btn-warning ml-2">
							<i class="fas fa-external-link-alt"></i> Go to Interview
						</a>
						<?php endif; ?>
					</div>
				<?php endif; endif; ?>
                
                <?php 
			// ========== STATUS 2,3: APPROVED/DECLINED (VIEW ONLY) ==========
			if ($status == 2 || $status == 3): ?>
				<div class="alert <?=($status == 2 ? 'alert-success' : 'alert-danger')?>">
					<i class="fas <?=($status == 2 ? 'fa-check-circle' : 'fa-times-circle')?>"></i>
					<strong>
						<?=($status == 2 ? 'ADMISSION APPROVED' : 'ADMISSION DECLINED')?>
					</strong>
					- This application has been processed. View only.
				</div>
			<?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- END NAVIGATION BAR -->

 <!-- Add this modal for SMS credit checking before approval -->
<div class="modal fade" id="smsCreditCheckModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fas fa-sms text-info"></i> SMS Credit Check
                </h4>
            </div>
            <div class="modal-body">
                <div class="text-center" id="smsCheckContent">
                    <i class="fas fa-spinner fa-spin fa-3x text-info mb-3"></i>
                    <h4>Checking SMS credits...</h4>
                    <p>Please wait while we verify SMS credits for notification.</p>
                </div>
                <div class="alert" id="smsCheckResult" style="display:none;">
                    <h4 id="smsCheckTitle"></h4>
                    <p id="smsCheckMessage"></p>
                    <div id="smsCheckDetails"></div>
                </div>
            </div>
            <div class="modal-footer" id="smsCheckActions" style="display:none;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="proceedWithApproval">
                    <i class="fas fa-check-circle"></i> Proceed Anyway
                </button>
                <a href="<?=base_url('sendsmsmail/purchase?branch=' . $stuDetails['branch_id'])?>" 
                   class="btn btn-primary" target="_blank" id="purchaseCreditsBtn">
                    <i class="fas fa-coins"></i> Purchase Credits
                </a>
            </div>
        </div>
    </div>
</div>


<div class="row">
	<div class="col-md-12">
		<section class="panel">
			<?php echo form_open_multipart($this->uri->uri_string(), array('class' => 'frm-submit-data', 'id' => 'mainForm')); ?>
					<input type="hidden" name="csrf_test_name" value="<?php echo $this->security->get_csrf_hash(); ?>">
			
					<!-- SMS Credit Warning Display -->
				<?php 
				$sms_warning = $this->session->flashdata('sms_warning');
				if (!empty($sms_warning)): 
				?>
				<div class="alert alert-warning alert-dismissible fade in mb-lg">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">×</span>
					</button>
					<strong><i class="fas fa-exclamation-triangle"></i> SMS Credit Warning:</strong>
					<pre style="white-space: pre-wrap; margin-top: 10px; background: transparent; border: none;"><?= $sms_warning ?></pre>
					<div class="mt-md">
						<a href="<?=base_url('sendsmsmail/purchase?branch=' . $stuDetails['branch_id'])?>" 
						class="btn btn-warning btn-sm" target="_blank">
							<i class="fas fa-coins"></i> Purchase Credits Now
						</a>
						<button class="btn btn-default btn-sm" data-dismiss="alert">
							<i class="fas fa-check"></i> Continue Anyway
						</button>
					</div>
				</div>
				<?php endif; ?>
					<!-- SMS Credit Status Display -->
			<div id="smsCreditStatus" class="alert alert-sm alert-info" style="display: none; margin-bottom: 20px;">
				<i class="fas fa-spinner fa-spin"></i> Checking SMS credits...
			</div>
			<!-- SMS Credit Status Display End -->

			 <?php if($stuDetails['status'] == 4): 
				$interview = $this->db->select('*')
					->where('admission_id', $stuDetails['id'])
					->where_in('status', ['scheduled', 'rescheduled'])
					->get('online_admission_interviews')
					->row_array();
				if(!empty($interview)):
			?>
			<!-- Reschedule Interview Modal -->
			<div class="modal fade" id="rescheduleModal" tabindex="-1" role="dialog">
				<div class="modal-dialog modal-lg" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal">&times;</button>
							<h4 class="modal-title"><i class="fas fa-calendar-alt"></i> Reschedule Interview</h4>
						</div>
						<div class="modal-body">
							<div class="alert alert-info">
								<i class="fas fa-info-circle"></i> Rescheduling will send a new SMS notification.
							</div>
							
							<input type="hidden" id="reschedule_interview_id" value="<?=$interview['id']?>">
							
							<div class="row">
								<div class="col-md-6 mb-sm">
									<div class="form-group">
										<label class="control-label">Current Date/Time</label>
										<input type="text" class="form-control" readonly 
											value="<?=date('d M Y', strtotime($interview['interview_date']))?> 
											<?=date('h:i A', strtotime($interview['interview_time']))?>">
									</div>
								</div>
								<div class="col-md-6 mb-sm">
									<div class="form-group">
										<label class="control-label">Reschedule Reason</label>
										<input type="text" class="form-control" id="reschedule_reason" 
											placeholder="Reason for rescheduling...">
									</div>
								</div>
							</div>
							
							<div class="row">
								<div class="col-md-6 mb-sm">
									<div class="form-group">
										<label class="control-label">New Interview Date <span class="required">*</span></label>
										<input type="text" class="form-control datepicker" id="reschedule_date" 
											value="<?=date('Y-m-d', strtotime('+1 day'))?>">
									</div>
								</div>
								<div class="col-md-6 mb-sm">
									<div class="form-group">
										<label class="control-label">New Interview Time <span class="required">*</span></label>
										<input type="text" class="form-control timepicker" id="reschedule_time" 
											value="10:00">
									</div>
								</div>
							</div>
							
							<div class="row">
								<div class="col-md-12 mb-sm">
									<div class="form-check">
										<input type="checkbox" class="form-check-input" id="change_interviewer">
										<label class="form-check-label" for="change_interviewer">Change Interviewer</label>
									</div>
								</div>
							</div>
							
							<div class="row" id="interviewer_change_section" style="display:none;">
								<div class="col-md-12 mb-sm">
									<div class="form-group">
										<label class="control-label">New Interviewer</label>
										<select class="form-control" id="reschedule_interviewer_id">
											<option value="">Select Interviewer</option>
											<?php foreach($interviewers as $interviewer): ?>
											<option value="<?=$interviewer['id'];?>" 
												<?=($interviewer['id'] == $interview['interviewer_id']) ? 'selected' : ''?>>
												<?=$interviewer['name'];?> (<?=$interviewer['designation'];?>)
											</option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
							<button type="button" class="btn btn-primary" id="submitReschedule">
								<i class="fas fa-calendar-check"></i> Reschedule Interview
							</button>
						</div>
					</div>
				</div>
			</div>
			<?php endif; endif; ?>
			
			<input type="hidden" name="branch_id" value="<?=$stuDetails['branch_id']; ?>">
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-graduation-cap"></i> <?=translate('student_admission')?></h4>
			</header>
			<div class="panel-body">
				<!-- academic details-->
				<div class="headers-line">
					<i class="fas fa-school"></i> <?=translate('academic_details')?>
				</div>
				<?php
				$academic_year = get_session_id(); 
				$roll = $this->student_fields_model->getStatus('roll', $branchID);
				$admission_date = $this->student_fields_model->getStatus('admission_date', $branchID);
                $v = (2 + floatval($roll['status']) + floatval($admission_date['status']));
                $div = floatval(12 / $v);
				?>
				<div class="row">
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('academic_year')?> <span class="required">*</span></label>
							<?php
								$arrayYear = array("" => translate('select'));
								$years = $this->db->get('schoolyear')->result();
								foreach ($years as $year){
									$arrayYear[$year->id] = $year->school_year;
								}
								echo form_dropdown("year_id", $arrayYear, set_value('year_id', $academic_year), "class='form-control' id='academic_year_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
					
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('register_no')?> <span class="required">*</span></label>
							<input type="text" class="form-control" name="register_no" value="<?=set_value('register_no', $register_id)?>" />
							<span class="error"></span>
						</div>
					</div>
					<?php if ($roll['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('roll')?><?php echo $roll['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<input type="text" class="form-control" name="roll" value="<?=set_value('roll')?>" />
							<span class="error"></span>
						</div>
					</div>
					<?php } if ($admission_date['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('admission_date')?><?php echo $admission_date['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
								<input type="text" class="form-control" name="admission_date" value="<?=set_value('admission_date', date('Y-m-d'))?>" data-plugin-datepicker
								data-plugin-options='{ "todayHighlight" : true }' />
							</div>
							<span class="error"></span>
						</div>
					</div>
					<?php } ?>
				</div>
				<?php
				$category = $this->student_fields_model->getStatus('category', $branchID);
                $v = (3 + floatval($category['status']));
                $div = floatval(12 / $v);
				?>
				<div class="row mb-md">
					<div class="col-md-<?php echo $div; ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
							<input type="text" class="form-control" readonly="" name="branch_name" value="<?=$getBranch['name']?>" />
						</div>
					</div>
					<div class="col-md-<?php echo $div; ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('class')?> <span class="required">*</span></label>
							<?php
								$arrayClass = $this->app_lib->getClass($stuDetails['branch_id']);
								echo form_dropdown("class_id", $arrayClass, set_value('class_id', $stuDetails['class_id']), "class='form-control' id='class_id' onchange='getSectionByClass(this.value,0)'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="col-md-<?php echo $div; ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('section')?> <span class="required">*</span></label>
							<?php
								$arraySection = $this->app_lib->getSections(set_value('class_id', $stuDetails['class_id']), false);
								echo form_dropdown("section_id", $arraySection, set_value('section_id', $stuDetails['section_id']), "class='form-control' id='section_id' 
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
					<?php if ($category['status']) { ?>
					<div class="col-md-<?php echo $div; ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('category')?><?php echo $category['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<?php
								$arrayCategory = $this->app_lib->getStudentCategory($stuDetails['branch_id']);
								echo form_dropdown("category_id", $arrayCategory, set_value('category_id', $stuDetails['category_id']), "class='form-control'
								data-plugin-selectTwo data-width='100%' id='category_id' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
					<?php } ?>
				</div>
				
				<!-- student details -->
				<div class="headers-line mt-md">
					<i class="fas fa-user-check"></i> <?=translate('student_details')?>
				</div>

				<?php
				$last_name = $this->student_fields_model->getStatus('last_name', $branchID);
				$gender = $this->student_fields_model->getStatus('gender', $branchID);
                $v = (1 + floatval($last_name['status']) + floatval($gender['status']));
                $div = floatval(12 / $v);
				?>
				<div class="row">
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"> <?=translate('first_name')?> <span class="required">*</span></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fas fa-user-graduate"></i></span>
								<input type="text" class="form-control" name="first_name" value="<?=set_value('first_name', $stuDetails['first_name'])?>"/>
							</div>
							<span class="error"></span>
						</div>
					</div>
					<?php if ($last_name['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"> <?=translate('last_name')?><?php echo $last_name['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fas fa-user-graduate"></i></span>
								<input type="text" class="form-control" name="last_name" value="<?=set_value('last_name', $stuDetails['last_name'])?>" />
							</div>
							<span class="error"></span>
						</div>
						
					</div>
					<?php } if ($gender['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"> <?=translate('gender')?><?php echo $gender['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<?php
								$arrayGender = array(
									'' => translate('select'),
									'male' => translate('male'),
									'female' => translate('female')
								);
								echo form_dropdown("gender", $arrayGender, set_value('gender', $stuDetails['gender']), "class='form-control' data-plugin-selectTwo
								data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
					<?php } ?>
				</div>

				<div class="row">
					<?php 
					$blood_group = $this->student_fields_model->getStatus('blood_group', $branchID);
					$birthday = $this->student_fields_model->getStatus('birthday', $branchID);
					$v = floatval($blood_group['status']) + floatval($birthday['status']);
					$div = ($v == 0) ? 12 : floatval(12 / $v);

					if ($blood_group['status']) {
					?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('blood_group')?><?php echo $blood_group['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<?php
								$bloodArray = $this->app_lib->getBloodgroup();
								echo form_dropdown("blood_group", $bloodArray, set_value("blood_group", $stuDetails['blood_group']), "class='form-control populate' data-plugin-selectTwo 
								data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
					<?php } if ($birthday['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('birthday')?><?php echo $birthday['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fas fa-birthday-cake"></i></span>
								<input type="text" autocomplete="off" class="form-control" name="birthday" value="<?=set_value('birthday', $stuDetails['birthday'])?>" data-plugin-datepicker
								data-plugin-options='{ "startView": 2 }' />
							</div>
							<span class="error"></span>
						</div>
					</div>
					<?php } ?>
				</div>

				<div class="row">
					<?php 
					$mother_tongue = $this->student_fields_model->getStatus('mother_tongue', $branchID);
					$religion = $this->student_fields_model->getStatus('religion', $branchID);
					$caste = $this->student_fields_model->getStatus('caste', $branchID);
					
					$v = floatval($mother_tongue['status']) + floatval($religion['status']) + floatval($caste['status']);
					$div = ($v == 0) ? 12 : floatval(12 / $v);
					if ($mother_tongue['status']) {
					?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('mother_tongue')?><?php echo $mother_tongue['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<input type="text" class="form-control" name="mother_tongue" value="<?=set_value('mother_tongue', $stuDetails['mother_tongue'])?>" />
							<span class="error"></span>
						</div>
					</div>
					<?php } if ($religion['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('religion')?><?php echo $religion['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<input type="text" class="form-control" name="religion" value="<?=set_value('religion', $stuDetails['religion'])?>" />
							<span class="error"></span>
						</div>
					</div>
					<?php } if ($caste['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('caste')?><?php echo $caste['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<input type="text" class="form-control" name="caste" value="<?=set_value('caste', $stuDetails['caste'])?>" />
							<span class="error"></span>
						</div>
					</div>
					<?php } ?>
				</div>

				<div class="row">
					<?php 
					$student_mobile_no = $this->student_fields_model->getStatus('student_mobile_no', $branchID);
					$student_email = $this->student_fields_model->getStatus('student_email', $branchID);
					$city = $this->student_fields_model->getStatus('city', $branchID);
					$state = $this->student_fields_model->getStatus('state', $branchID);

					$v = floatval($student_mobile_no['status']) + floatval($student_email['status']) + floatval($city['status'])  + floatval($state['status']);
					$div = ($v == 0) ? 12 : floatval(12 / $v);
					if ($student_mobile_no['status']) {
					?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('mobile_no')?><?php echo $student_mobile_no['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fas fa-phone-volume"></i></span>
								<input type="text" class="form-control" name="mobileno" value="<?=set_value('mobileno', $stuDetails['mobile_no'])?>" />
							</div>
							<span class="error"></span>
						</div>
					</div>
					<?php } if ($student_email['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('email')?><?php echo $student_email['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-envelope-open"></i></span>
								<input type="text" class="form-control" name="email" id="email" value="<?=set_value('email', $stuDetails['email'])?>" />
							</div>
							<span class="error"></span>
						</div>
					</div>
					<?php } if ($city['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('city')?><?php echo $city['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<input type="text" class="form-control" name="city" value="<?=set_value('city', $stuDetails['city'])?>" />
							<span class="error"></span>
						</div>
					</div>
					<?php } if ($state['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('state')?><?php echo $state['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<input type="text" class="form-control" name="state" value="<?=set_value('state', $stuDetails['state'])?>" />
							<span class="error"></span>
						</div>
					</div>
					<?php } ?>
				</div>

				<div class="row">
					<?php 
					$present_address = $this->student_fields_model->getStatus('present_address', $branchID);
					$permanent_address = $this->student_fields_model->getStatus('permanent_address', $branchID);
					$v = floatval($present_address['status']) + floatval($permanent_address['status']);
					$div = ($v == 0) ? 12 : floatval(12 / $v);

					if ($present_address['status']) {
						?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('present_address')?><?php echo $present_address['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<textarea name="current_address" rows="2" class="form-control" aria-required="true"><?=set_value('current_address', $stuDetails['present_address'])?></textarea>
							<span class="error"></span>
						</div>
					</div>
					<?php } if ($permanent_address['status']) { ?>
					<div class="col-md-<?php echo $div ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('permanent_address')?><?php echo $permanent_address['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<textarea name="permanent_address" rows="2" class="form-control" aria-required="true"><?=set_value('permanent_address', $stuDetails['permanent_address'])?></textarea>
							<span class="error"></span>
						</div>
					</div>
					<?php } ?>
				</div>

				<!--custom fields details-->
				<div class="row" id="customFields">
					<?php echo render_online_custom_fields('student', $stuDetails['branch_id'], $stuDetails['id']); ?>
				</div>
				
				<div class="row">
					<?php 
					$student_photo = $this->student_fields_model->getStatus('student_photo', $branchID);
					if ($student_photo['status']) {
					?>
					<input type="hidden" name="exist_student_photo" value="<?php echo $stuDetails['student_photo'] ?>">
					<div class="col-md-12 mb-sm">
						<div class="form-group">
							<label for="input-file-now"><?=translate('profile_picture')?><?php echo $student_photo['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<input type="file" name="student_photo" class="dropify" data-default-file="<?=get_image_url('student', $stuDetails['student_photo'])?>" />
							<span class="error"></span>
						</div>
					</div>
					<?php } ?>
				</div>
				<div class="<?=$getBranch['stu_generate'] == 1 || $getBranch['stu_generate'] == "" ? 'hidden-div' : '' ?>" id="stuLogin">
					<!-- login details -->
					<div class="headers-line mt-md">
						<i class="fas fa-user-lock"></i> <?=translate('login_details')?>
					</div>
					<div class="row mb-md">
						<div class="col-md-6 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('username')?> <span class="required">*</span></label>
								<div class="input-group">
									<span class="input-group-addon"><i class="far fa-user"></i></span>
									<input type="text" class="form-control" name="username" id="username" value="<?=set_value('username')?>" />
								</div>
								<span class="error"></span>
							</div>
						</div>
						<div class="col-md-3 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('password')?> <span class="required">*</span></label>
								<div class="input-group">
									<span class="input-group-addon"><i class="fas fa-unlock-alt"></i></span>
									<input type="password" class="form-control" name="password" value="<?=set_value('password')?>" />
								</div>
								<span class="error"></span>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label"><?=translate('retype_password')?> <span class="required">*</span></label>
								<div class="input-group">
									<span class="input-group-addon"><i class="fas fa-unlock-alt"></i></span>
									<input type="password" class="form-control" name="retype_password" value="<?=set_value('retype_password')?>" />
								</div>
								<span class="error"></span>
							</div>
						</div>
					</div>
				</div>

				<?php 
				$guardian_name = $this->student_fields_model->getStatus('guardian_name', $branchID);
				$guardian_relation = $this->student_fields_model->getStatus('guardian_relation', $branchID);
				$father_name = $this->student_fields_model->getStatus('father_name', $branchID);
				$mother_name = $this->student_fields_model->getStatus('mother_name', $branchID);
				$guardian_occupation = $this->student_fields_model->getStatus('guardian_occupation', $branchID);
				$guardian_income = $this->student_fields_model->getStatus('guardian_income', $branchID);
				$guardian_education = $this->student_fields_model->getStatus('guardian_education', $branchID);
				$guardian_city = $this->student_fields_model->getStatus('guardian_city', $branchID);
				$guardian_state = $this->student_fields_model->getStatus('guardian_state', $branchID);
				$guardian_mobile_no = $this->student_fields_model->getStatus('guardian_mobile_no', $branchID);
				$guardian_email = $this->student_fields_model->getStatus('guardian_email', $branchID);
				$guardian_address = $this->student_fields_model->getStatus('guardian_address', $branchID);
				$guardian_photo = $this->student_fields_model->getStatus('guardian_photo', $branchID);

				if ($guardian_name['status'] || $guardian_relation['status'] || $father_name['status'] || $mother_name['status'] || $guardian_occupation['status'] || $guardian_income['status'] || $guardian_education['status'] || $guardian_email['status'] || $guardian_mobile_no['status'] || $guardian_address['status'] || $guardian_photo['status']) {
				?>
				<!--guardian details-->
				<div class="headers-line mt-lg">
					<i class="fas fa-user-tie"></i> <?=translate('guardian_details')?>
				</div>

				<div id="guardian_form">
					<div class="row">
						<?php if ($guardian_name['status']) { ?>
						<div class="col-md-6 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('name')?><?php echo $guardian_name['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input class="form-control" name="grd_name" type="text" value="<?=set_value('grd_name', $stuDetails['guardian_name'])?>">
								<span class="error"></span>
							</div>
						</div>
						<?php } if ($guardian_relation['status']) { ?>
						<div class="col-md-6 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('relation')?><?php echo $guardian_relation['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input type="text" class="form-control" name="grd_relation" value="<?=set_value('grd_relation', $stuDetails['guardian_relation'])?>" />
								<span class="error"></span>
							</div>
						</div>
						<?php } ?>
					</div>

					<div class="row">
						<?php if ($father_name['status']) { ?>
						<div class="col-md-6 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('father_name')?><?php echo $father_name['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input type="text" class="form-control" name="father_name" value="<?=set_value('father_name', $stuDetails['father_name'])?>" />
								<span class="error"></span>
							</div>
						</div>
						<?php } if ($mother_name['status']) { ?>
						<div class="col-md-6 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('mother_name')?><?php echo $mother_name['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input type="text" class="form-control" name="mother_name" value="<?=set_value('mother_name', $stuDetails['mother_name'])?>" />
								<span class="error"></span>
							</div>
						</div>
						<?php } ?>
					</div>
					<div class="row">
						<?php if ($guardian_occupation['status']) { ?>
						<div class="col-md-4 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('occupation')?><?php echo $guardian_occupation['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input class="form-control" name="grd_occupation" value="<?=set_value('grd_occupation', $stuDetails['grd_occupation'])?>" type="text">
								<span class="error"></span>
							</div>
						</div>
						<?php } if ($guardian_income['status']) { ?>
						<div class="col-md-4 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('income')?><?php echo $guardian_income['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input class="form-control" name="grd_income" value="<?=set_value('grd_income', $stuDetails['grd_income'])?>" type="text">
								<span class="error"></span>
							</div>
						</div>
						<?php } if ($guardian_education['status']) { ?>
						<div class="col-md-4 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('education')?><?php echo $guardian_education['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input class="form-control" name="grd_education" value="<?=set_value('grd_education', $stuDetails['grd_education'])?>" type="text">
								<span class="error"></span>
							</div>
						</div>
						<?php } ?>
					</div>

					<div class="row">
						<?php if ($guardian_city['status']) { ?>
						<div class="col-md-3 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('city')?><?php echo $guardian_city['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input class="form-control" name="grd_city" value="<?=set_value('grd_city', $stuDetails['grd_city'])?>" type="text">
								<span class="error"></span>
							</div>
						</div>
						<?php } if ($guardian_state['status']) { ?>
						<div class="col-md-3 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('state')?><?php echo $guardian_state['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input class="form-control" name="grd_state" value="<?=set_value('grd_state', $stuDetails['grd_state'])?>" type="text">
								<span class="error"></span>
							</div>
						</div>
						<?php } if ($guardian_mobile_no['status']) { ?>
						<div class="col-md-3 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('mobile_no')?><?php echo $guardian_mobile_no['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<div class="input-group">
									<span class="input-group-addon"><i class="fas fa-phone-volume"></i></span>
									<input class="form-control" name="grd_mobileno" type="text" value="<?=set_value('grd_mobileno', $stuDetails['grd_mobile_no'])?>">
								</div>
								<span class="error"></span>
							</div>
						</div>
						<?php } if ($guardian_email['status']) { ?>
						<div class="col-md-3 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('email')?><?php echo $guardian_email['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<div class="input-group">
									<span class="input-group-addon"><i class="far fa-envelope-open"></i></span>
									<input type="email" class="form-control" name="grd_email" id="grd_email" value="<?=set_value('grd_email', $stuDetails['grd_email'])?>" />
								</div>
								<span class="error"></span>
							</div>
						</div>
						<?php } ?>
					</div>
					<?php if ($guardian_address['status']) { ?>
					<div class="row">
						<div class="col-md-12 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('address')?><?php echo $guardian_address['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<textarea name="grd_address" rows="2" class="form-control" aria-required="true"><?=set_value('grd_address', $stuDetails['grd_address'])?></textarea>
								<span class="error"></span>
							</div>
						</div>
					</div>
					<?php } ?>
					<div class="row">
						<?php if ($guardian_photo['status']) { ?>
						<input type="hidden" name="exist_guardian_photo" value="<?php echo $stuDetails['grd_photo'] ?>">
						<div class="col-md-12 mb-sm">
							<div class="form-group">
								<label for="input-file-now"><?=translate('guardian_picture')?><?php echo $guardian_photo['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
								<input type="file" name="guardian_photo" class="dropify" data-default-file="<?=get_image_url('parent', $stuDetails['grd_photo'])?>" />
								<span class="error"></span>
							</div>
						</div>
						<?php } ?>
					</div>
					<div class="<?=$getBranch['grd_generate'] == 1 || $getBranch['grd_generate'] == "" ? 'hidden-div' : ''?>" id="grdLogin">
						<div class="row mb-lg">
							<div class="col-md-6 mb-sm">
								<div class="form-group">
									<label class="control-label"><?=translate('usename')?> <span class="required">*</span></label>
									<div class="input-group">
										<span class="input-group-addon"><i class="far fa-user"></i></span>
										<input type="text" class="form-control" name="grd_username" id="grd_username" value="<?=set_value('grd_username')?>" />
									</div>
									<span class="error"></span>
								</div>
							</div>
							<div class="col-md-3 mb-sm">
								<div class="form-group">
									<label class="control-label"><?=translate('password')?> <span class="required">*</span></label>
									<div class="input-group">
										<span class="input-group-addon"><i class="fas fa-unlock-alt"></i></span>
										<input type="password" class="form-control" name="grd_password" value="<?=set_value('grd_password')?>" />
									</div>
									<span class="error"></span>
								</div>
							</div>
							<div class="col-md-3 mb-sm">
								<div class="form-group">
									<label class="control-label"><?=translate('retype_password')?> <span class="required">*</span></label>
									<div class="input-group">
										<span class="input-group-addon"><i class="fas fa-unlock-alt"></i></span>
										<input type="password" class="form-control" name="grd_retype_password" value="<?=set_value('grd_retype_password')?>" />
									</div>
									<span class="error"></span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>

				<!-- transport details -->
				<div class="headers-line">
					<i class="fas fa-bus-alt"></i> <?=translate('transport_details')?>
				</div>

				<div class="row mb-md">
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('transport_route')?></label>
							<?php
								$arrayRoute = $this->app_lib->getSelectByBranch('transport_route', $stuDetails['branch_id']);
								echo form_dropdown("route_id", $arrayRoute, set_value('route_id'), "class='form-control' id='route_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
						</div>
					</div>
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('vehicle_no')?></label>
							<?php
								$arrayVehicle = $this->app_lib->getVehicleByRoute(set_value('route_id'));
								echo form_dropdown("vehicle_id", $arrayVehicle, set_value('vehicle_id'), "class='form-control' id='vehicle_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
						</div>
					</div>
				</div>
				
				<!-- hostel details -->
				<div class="headers-line">
					<i class="fas fa-hotel"></i> <?=translate('hostel_details')?>
				</div>
				
				<div class="row mb-md">
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('hostel_name')?></label>
							<?php
								$arrayHostel = $this->app_lib->getSelectByBranch('hostel', $stuDetails['branch_id']);
								echo form_dropdown("hostel_id", $arrayHostel, set_value('hostel_id'), "class='form-control' id='hostel_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
						</div>
					</div>
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('room_name')?></label>
							<?php
								$arrayRoom = $this->app_lib->getRoomByHostel(set_value('hostel_id'));
								echo form_dropdown("room_id", $arrayRoom, set_value('room_id'), "class='form-control' id='room_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
						</div>
					</div>
				</div>
				
				<?php
				$previous_school_details = $this->student_fields_model->getStatus('previous_school_details', $branchID);
				if ($previous_school_details['status']) {
					$school_name = '';
					$qualification = '';
					$previous_remarks = '';
					if (!empty($stuDetails['previous_school_details'])) {
						$details = json_decode($stuDetails['previous_school_details'], true);
						$school_name = $details['school_name'];
						$qualification = $details['qualification'];
						$previous_remarks = $details['remarks'];
					}
					?>
				<!-- previous school details -->
				<div class="headers-line">
					<i class="fas fa-bezier-curve"></i> <?=translate('previous_school_details')?>
				</div>
				<div class="row">
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('school_name')?><?php echo $previous_school_details['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<input type="text" class="form-control" name="school_name" value="<?=set_value('school_name', $school_name)?>" />
							<span class="error"></span>
						</div>
					</div>
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('qualification')?><?php echo $previous_school_details['required'] == 1 ? ' <span class="required">*</span>' : ''; ?></label>
							<input type="text" class="form-control" name="qualification" value="<?=set_value('qualification', $qualification)?>" />
							<span class="error"></span>
						</div>
					</div>
				</div>
				<div class="row mb-lg">
					<div class="col-md-12">
						<div class="form-group">
							<label class="control-label"><?=translate('remarks')?></label>
							<textarea name="previous_remarks" rows="2" class="form-control"><?=set_value('previous_remarks', $previous_remarks)?></textarea>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
			<!-- Action Buttons in form footer -->
			<footer class="panel-footer">
			<div class="row">
					<div class="col-md-12">
						<div class="pull-left">
							<button onclick="history.go(-1);" class="btn btn-default mr-xs" type="button">
								<i class="fas fa-arrow-left"></i> <?=translate('cancel')?>
							</button>
						</div>
						
						<div class="pull-right">
							<!-- ADD THIS CONDITION: -->
							<?php if ($status == 1 || $status == 5): ?>
								<button type="button" class="btn btn-success" id="submitApproveBtn">
									<i class="fas fa-check-circle"></i> Approve & Enroll
								</button>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</footer>
			<?php echo form_close();?>

			<!-- ========== INTERVIEW MODAL (OUTSIDE MAIN FORM) ========== -->
			<div class="modal fade" id="interviewModal" tabindex="-1" role="dialog" aria-labelledby="interviewModalLabel">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
						<h4 class="modal-title" id="interviewModalLabel">
							<i class="fas fa-calendar-check"></i> Schedule Interview
						</h4>
					</div>
            <div class="modal-body">
                <form id="interviewForm">
                    <!-- CRITICAL FIX: Get fresh CSRF token -->
                    <input type="hidden" name="csrf_test_name" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="branch_id" value="<?=$stuDetails['branch_id']; ?>">
                    <input type="hidden" name="admission_id" value="<?=$stuDetails['id']; ?>">
                    
                    <!-- Add SMS credit warning -->
                    <div class="alert alert-sm alert-warning mb-3" id="interviewSmsWarning" style="display:none;">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <span id="smsWarningText">Checking SMS credits...</span>
                    </div>
                    
                    
								
								<div class="row">
									<div class="col-md-6 mb-sm">
										<div class="form-group">
											<label class="control-label">Interview Date <span class="required">*</span></label>
											<div class="input-group">
												<span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
												<input type="text" class="form-control datepicker" name="interview_date" 
													id="interview_date" value="<?=date('Y-m-d', strtotime('+2 days'));?>" required>
											</div>
										</div>
									</div>
									<div class="col-md-6 mb-sm">
										<div class="form-group">
											<label class="control-label">Interview Time <span class="required">*</span></label>
											<div class="input-group">
												<span class="input-group-addon"><i class="far fa-clock"></i></span>
												<input type="text" class="form-control timepicker" name="interview_time" 
													id="interview_time" value="10:00" required>
											</div>
										</div>
									</div>
								</div>
								
								<div class="row">
									<div class="col-md-6 mb-sm">
										<div class="form-group">
											<label class="control-label">Interview Type <span class="required">*</span></label>
											<select class="form-control" name="interview_type" id="interview_type" required>
												<option value="parent">Parent Only</option>
												<option value="student">Student Only</option>
												<option value="both">Both Parent & Student</option>
											</select>
										</div>
									</div>
									<div class="col-md-6 mb-sm">
										<div class="form-group">
											<label class="control-label">Interviewer <span class="required">*</span></label>
											<select class="form-control" name="interviewer_id" id="interviewer_id" required>
												<option value="">Select Interviewer</option>
												<?php foreach($interviewers as $interviewer): ?>
												<option value="<?=$interviewer['id'];?>">
													<?=$interviewer['name'];?> (<?=$interviewer['designation'];?>)
												</option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
								</div>
								
								<div class="row">
									<div class="col-md-12 mb-sm">
										<div class="form-group">
											<label class="control-label">Location <span class="required">*</span></label>
											<input type="text" class="form-control" name="location" 
												id="location" value="Admissions Office" required>
										</div>
									</div>
								</div>
								
								<div class="row">
									<div class="col-md-12 mb-sm">
										<div class="form-group">
											<label class="control-label">Notes</label>
											<textarea class="form-control" name="interview_notes" 
												id="interview_notes" rows="3"></textarea>
										</div>
									</div>
								</div>
							</form>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
							<button type="button" class="btn btn-primary" id="submitInterview">
								<i class="fas fa-calendar-plus"></i> Schedule Interview
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Reschedule Modal (also outside) if needed -->
			<?php if($stuDetails['status'] == 4): ?>
			<!-- Add reschedule modal here -->
			<?php endif; ?>
		</section>
	</div>
</div>

<script type="text/javascript">
	// ========== DISABLE FORM FOR PROCESSED APPLICATIONS ==========
$(document).ready(function() {
    var admission_status = <?=$stuDetails['status'];?>;
    
    // If status is 2 (Approved) or 3 (Declined), disable the form
    if (admission_status == 2 || admission_status == 3) {
        // Disable all form inputs
        $('#mainForm input, #mainForm select, #mainForm textarea').prop('disabled', true);
        
        // Hide file upload
        $('.dropify').addClass('disabled').dropify('disable');
        
        // Remove submit event handlers
        $('#submitApproveBtn').remove();
        
        // Add "View Only" message
        $('#mainForm').prepend(
            '<div class="alert alert-info mb-3">' +
            '<i class="fas fa-info-circle"></i> ' +
            '<strong>View Only:</strong> This application has been processed and cannot be modified.' +
            '</div>'
        );
    }
});
var base_url = '<?=base_url();?>';
var admission_id = <?=$stuDetails['id'];?>;
var admission_status = <?=$stuDetails['status'];?>;
var csrf_token = '<?= $this->security->get_csrf_hash() ?>';
// ========== SMS CREDIT CHECK BEFORE INTERVIEW SCHEDULING ==========
function checkInterviewSmsCredits() {
    $('#smsWarningText').html('<i class="fas fa-spinner fa-spin"></i> Checking SMS credits...');
    $('#interviewSmsWarning').show().removeClass('alert-danger').addClass('alert-warning');
    
    $.ajax({
        url: base_url + 'online_admission/check_sms_credits',
        type: 'POST',
        data: {
            csrf_test_name: csrf_token,
            branch_id: <?=$stuDetails['branch_id'];?>,
            student_id: admission_id,
            template_id: 11 // Interview invitation template
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.required_credits > 0) {
                    $('#smsWarningText').html(
                        '<i class="fas fa-check-circle text-success"></i> ' +
                        'SMS credits available: ' + response.available_credits + 
                        ' (Required: ' + response.required_credits + ')'
                    );
                    $('#interviewSmsWarning').removeClass('alert-warning').addClass('alert-success');
                } else {
                    $('#smsWarningText').html('SMS notification disabled or no recipients');
                    $('#interviewSmsWarning').removeClass('alert-warning').addClass('alert-info');
                }
            } else {
                $('#smsWarningText').html(
                    '<i class="fas fa-exclamation-triangle text-danger"></i> ' +
                    response.message
                );
                $('#interviewSmsWarning').removeClass('alert-warning').addClass('alert-danger');
            }
        },
        error: function() {
            $('#smsWarningText').html('Failed to check SMS credits');
            $('#interviewSmsWarning').removeClass('alert-warning').addClass('alert-danger');
        }
    });
}

// Call when modal opens
$('#interviewModal').on('show.bs.modal', function() {
    checkInterviewSmsCredits();
});
$(document).ready(function() {
    // ========== INITIALIZE PLUGINS ==========
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
    
    if (typeof $.fn.dropify !== 'undefined') {
        $('.dropify').dropify();
    }
    
    // ========== DISABLE HTML5 VALIDATION ==========
    $('#mainForm').attr('novalidate', 'novalidate');
    
    // ========== FORM VALIDATION FUNCTION ==========
    function validateAdmissionForm() {
        var errors = [];
        var isValid = true;
        
        console.log('=== DEBUG: Validating form ===');
        
        // Clear previous validation errors
        $('#mainForm').find('.is-invalid').removeClass('is-invalid');
        
        // 1. Check basic required fields
        var basicRequired = [
            {name: 'first_name', label: 'First Name'},
            {name: 'register_no', label: 'Register No'},
            {name: 'class_id', label: 'Class'},
            {name: 'section_id', label: 'Section'},
            {name: 'year_id', label: 'Academic Year'}
        ];
        
        basicRequired.forEach(function(field) {
            var $field = $('[name="' + field.name + '"]');
            if ($field.length) {
                var value = $field.val();
                console.log(field.label + ':', value);
                
                if (!value || value.toString().trim() === '' || value === '0') {
                    errors.push(field.label + ' is required');
                    $field.addClass('is-invalid');
                    isValid = false;
                }
            } else {
                console.warn('Field not found:', field.name);
            }
        });
        
        // 2. Check gender if required by system
        var genderField = $('[name="gender"]');
        if (genderField.length && !genderField.val()) {
            // Check if gender validation is enabled in student fields
            var genderRequired = <?=($this->student_fields_model->getStatus('gender', $branchID)['required'] ?? 0) == 1 ? 'true' : 'false'?>;
            if (genderRequired) {
                errors.push('Gender is required');
                genderField.addClass('is-invalid');
                isValid = false;
            }
        }
        
        // 3. Check student username/password (if not auto-generated)
        var stuGenerate = <?=($getBranch['stu_generate'] ?? 0) == 1 ? 'true' : 'false'?>;
        var stuLoginSection = $('#stuLogin');
        
        console.log('Student auto-generate:', stuGenerate);
        console.log('Student login section visible:', stuLoginSection.is(':visible') && !stuLoginSection.hasClass('hidden-div'));
        
        if (!stuGenerate && stuLoginSection.is(':visible') && !stuLoginSection.hasClass('hidden-div')) {
            var username = $('[name="username"]').val();
            var password = $('[name="password"]').val();
            var retypePassword = $('[name="retype_password"]').val();
            
            console.log('Student username:', username);
            console.log('Student password present:', password ? 'YES' : 'NO');
            console.log('Student retype present:', retypePassword ? 'YES' : 'NO');
            
            if (!username || username.trim() === '') {
                errors.push('Student Username is required');
                $('[name="username"]').addClass('is-invalid');
                isValid = false;
            }
            
            if (!password || password.trim() === '') {
                errors.push('Student Password is required');
                $('[name="password"]').addClass('is-invalid');
                isValid = false;
            }
            
            if (password !== retypePassword) {
                errors.push('Student Passwords do not match');
                $('[name="retype_password"]').addClass('is-invalid');
                isValid = false;
            }
        }
        
        // 4. Check guardian username/password (if not auto-generated AND guardian section exists)
        var grdGenerate = <?=($getBranch['grd_generate'] ?? 0) == 1 ? 'true' : 'false'?>;
        var grdLoginSection = $('#grdLogin');
        var guardianForm = $('#guardian_form');
        
        console.log('Guardian auto-generate:', grdGenerate);
        console.log('Guardian section exists:', guardianForm.length > 0);
        console.log('Guardian login section visible:', grdLoginSection.is(':visible') && !grdLoginSection.hasClass('hidden-div'));
        
        if (!grdGenerate && guardianForm.length && guardianForm.is(':visible') && 
            grdLoginSection.is(':visible') && !grdLoginSection.hasClass('hidden-div')) {
            
            var grdUsername = $('[name="grd_username"]').val();
            var grdPassword = $('[name="grd_password"]').val();
            var grdRetypePassword = $('[name="grd_retype_password"]').val();
            
            console.log('Guardian username:', grdUsername);
            console.log('Guardian password present:', grdPassword ? 'YES' : 'NO');
            console.log('Guardian retype present:', grdRetypePassword ? 'YES' : 'NO');
            
            if (!grdUsername || grdUsername.trim() === '') {
                errors.push('Guardian Username is required');
                $('[name="grd_username"]').addClass('is-invalid');
                isValid = false;
            }
            
            if (!grdPassword || grdPassword.trim() === '') {
                errors.push('Guardian Password is required');
                $('[name="grd_password"]').addClass('is-invalid');
                isValid = false;
            }
            
            if (grdPassword !== grdRetypePassword) {
                errors.push('Guardian Passwords do not match');
                $('[name="grd_retype_password"]').addClass('is-invalid');
                isValid = false;
            }
        }
        
        console.log('Validation result:', isValid ? 'PASS' : 'FAIL');
        console.log('Errors found:', errors.length, errors);
        
        return {
            isValid: isValid,
            errors: errors
        };
    }
// ========== STEP 3A: DIRECT APPROVAL (No Interview) ==========
$(document).on('click', '#approveDirectBtn, #approveAfterInterviewBtn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log('=== DIRECT APPROVAL CLICKED ===');
    console.log('Button ID:', $(this).attr('id'));
    console.log('Admission status:', admission_status);
    console.log('Admission ID:', admission_id);
    
    // Get action type from button
    var buttonId = $(this).attr('id');
    var isAfterInterview = (buttonId === 'approveAfterInterviewBtn');
    var actionText = isAfterInterview ? 'Approve After Interview' : 'Approve Directly';
    
    console.log('Action:', actionText);
    
    // SIMPLE CONFIRM - Keep this as is (working)
    if (!confirm(actionText + "?\n\nThis will:\n1. Create student record\n2. Create/link parent record\n3. Create login credentials\n4. Update status to Approved\n5. Send notification\n6. Log communication\n\nAre you sure?")) {
        console.log('User cancelled');
        return;
    }
    
    console.log('User confirmed, making AJAX call');
    
    // Show loading indicator
    var button = $(this);
    var originalHtml = button.html();
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    
    // ========== FIX: Add timeout parameter ==========
    $.ajax({
        url: base_url + 'online_admission/approve/' + admission_id,
        type: 'POST',
        data: { 
            csrf_test_name: csrf_token,
            action_type: isAfterInterview ? 'after_interview' : 'direct'
        },
        dataType: 'json',
        timeout: 30000, // 30 second timeout for SMS gateway
        success: function(response) {
            console.log('Direct approval response:', response);
            
            // Reset button
            button.prop('disabled', false).html(originalHtml);
            
            if (response.success) {
                // ========== FIX: Use alert for consistency (since confirm is working) ==========
                alert('✅ Success: ' + response.message);
                // Redirect to admission list
                window.location.href = base_url + 'online_admission';
            } else {
                alert('❌ Error: ' + response.message);
            }
        },
        // ========== FIX: Single error handler with timeout support ==========
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.log('Response Text:', xhr.responseText);
            
            // Reset button
            button.prop('disabled', false).html(originalHtml);
            
            var errorMsg = "";
            
            // ========== FIX: Handle timeout specifically ==========
            if (status === 'timeout') {
                errorMsg = "⚠️ Request timed out.\n\nThe approval was processed but SMS delivery status is unknown. Please check the admission list to verify.";
            }
            // Handle HTTP errors
            else if (xhr.status === 403) {
                errorMsg = "🔒 Security token expired. Please refresh the page and try again.";
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
            else if (xhr.status === 500) {
                errorMsg = "💥 Server error. Please try again later.";
            }
            else if (xhr.responseText) {
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    errorMsg = errorResponse.message || xhr.statusText;
                } catch (e) {
                    errorMsg = xhr.statusText || "Unknown error occurred";
                }
            } else {
                errorMsg = "Network error. Please check your connection.";
            }
            
            // ========== FIX: Use alert (to match confirm) instead of swal ==========
            alert('❌ ' + errorMsg);
        }
    });
});

  // ========== FORM-BASED APPROVAL (Detailed Form) ==========
$(document).on('click', '#submitApproveBtn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log('Form-based approval button clicked');
    
    // Validate status first
    if(admission_status != 1 && admission_status != 5) {
        swal('Error', 'Cannot approve admission with current status', 'error');
        return;
    }
    
    // Validate form
    var validation = validateAdmissionForm();
    if (!validation.isValid) {
        var errorMessage = 'Please fix the following errors:\n\n' + 
                         validation.errors.join('\n');
        swal('Validation Failed', errorMessage, 'error');
        return;
    }
    
    var submitBtn = $(this);
    var originalText = submitBtn.html();
    
    // Disable button to prevent double click
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    
    // Get FRESH CSRF token
    var freshToken = $('input[name="csrf_test_name"]').val();
    console.log('Using CSRF token:', freshToken ? 'Yes' : 'No');
    
    // ========== ADD TIMEOUT SAFETY ==========
    var requestTimeout = setTimeout(function() {
        if (submitBtn.prop('disabled')) {
            console.error('Request timed out after 30 seconds');
            submitBtn.prop('disabled', false).html(originalText);
            swal.close();
            swal({
                title: "Request Timeout",
                text: "The request took too long. Please try again.",
                type: "error",
                confirmButtonText: "OK"
            });
        }
    }, 30000);
    
    var actionText = (admission_status == 5) 
        ? 'Approve After Interview' 
        : 'Approve with Form Details';
    
    // SweetAlert v1 confirmation
    swal({
        title: actionText + "?",
        text: "This will:\n" +
              "1. Create student record with form data\n" +
              "2. Create/link parent record\n" +
              "3. Create login credentials\n" +
              "4. Update status to Approved (2)\n" +
              "5. Check SMS credits & send notification\n" +
              "6. Log communication\n\n" +
              "Are you sure?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, approve",
        cancelButtonText: "Cancel"
    }, function(isConfirm) {
        if (isConfirm) {
            // Show processing alert
            swal({
                title: "Processing Form Approval...",
                text: "Please wait while we process the approval",
                type: "info",
                showConfirmButton: false
            });
            
            // Prepare form data - include ALL form fields
            var form = document.getElementById('mainForm');
            var formData = new FormData(form);
            formData.append('action_type', 'approve');
            
            // Ensure CSRF token is included
            if (!formData.has('csrf_test_name')) {
                formData.append('csrf_test_name', freshToken);
            }
            
            console.log('Sending AJAX request to:', form.action);
            console.log('Form has fields:', formData.has('first_name'), 
                       formData.has('register_no'),
                       formData.has('csrf_test_name'));
            
            // Send form-based approval
            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 25000,
                beforeSend: function(xhr) {
                    console.log('AJAX request starting with CSRF:', freshToken.substring(0, 10) + '...');
                },
                success: function(response, status, xhr) {
                    console.log('AJAX success! Status:', status);
                    console.log('Response:', response);
                    
                    // Clear timeout
                    clearTimeout(requestTimeout);
                    
                    // Close processing alert
                    swal.close();
                    
                    if (response.status === 'success') {
                        submitBtn.prop('disabled', false).html('Success!');
                        swal({
                            title: "Success!",
                            text: response.message,
                            type: "success",
                            confirmButtonText: "OK"
                        }, function() {
                            // Redirect to admission list
                            window.location.href = base_url + 'online_admission';
                        });
                    } else {
                        // Re-enable button
                        submitBtn.prop('disabled', false).html(originalText);
                        
                        var errorMessage = response.message || "Form approval failed";
                        errorMessage = errorMessage.replace(/<br\s*\/?>/gi, '\n');
                        errorMessage = errorMessage.replace(/<[^>]+>/g, '');
                        
                        swal({
                            title: "Error!",
                            text: errorMessage,
                            type: "error",
                            confirmButtonText: "OK"
                        });
                        
                        // Update CSRF token if response contains one
                        updateCsrfToken();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error! Status:', status);
                    console.log('HTTP Status:', xhr.status);
                    console.log('Error:', error);
                    
                    // Clear timeout
                    clearTimeout(requestTimeout);
                    
                    // Re-enable button
                    submitBtn.prop('disabled', false).html(originalText);
                    
                    // Close processing alert
                    swal.close();
                    
                    var errorMsg = "Error: ";
                    
                    // Handle 403 CSRF error specifically
                    if (xhr.status === 403) {
                        errorMsg = "Security token expired. Please reload the page and try again.";
                        
                        // Force reload after showing error
                        setTimeout(function() {
                            if (confirm('Security token has expired. Reload the page?')) {
                                location.reload();
                            }
                        }, 1500);
                    } else if (xhr.status === 500) {
                        errorMsg = "Server error. Please check logs.";
                    } else if (xhr.responseText) {
                        // Try to extract error
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.message) {
                                errorMsg = errorResponse.message;
                            }
                        } catch (e) {
                            // Not JSON
                            errorMsg = "Server returned: " + xhr.statusText;
                        }
                    } else {
                        errorMsg = "Network error. Please check connection.";
                    }
                    
                    swal({
                        title: "Request Failed",
                        text: errorMsg,
                        type: "error",
                        confirmButtonText: "OK"
                    });
                },
                complete: function(xhr, status) {
                    console.log('AJAX complete. Status:', status);
                    clearTimeout(requestTimeout);
                }
            });
        } else {
            // User canceled
            clearTimeout(requestTimeout);
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
});

 // ========== DECLINE BUTTON HANDLER ==========
$(document).on('click', '#declineBtn, #declineAfterBtn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log('Decline button clicked:', $(this).attr('id'));
    
    if(admission_status != 1 && admission_status != 5) {
        alert('Cannot decline admission with current status');
        return;
    }
    
    if (!confirm('Decline Admission?\n\nThis will:\n1. Update status to Declined\n2. Send rejection notification\n3. Log communication\n\nThis action cannot be undone.')) {
        return;
    }
    
    var button = $(this);
    var originalHtml = button.html();
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    
    // Send decline request
    $.ajax({
        url: base_url + 'online_admission/decline/' + admission_id,
        type: 'POST',
        data: { 
            csrf_test_name: csrf_token,
            confirm: 'yes' 
        },
        dataType: 'json',
        success: function(response) {
            button.prop('disabled', false).html(originalHtml);
            
            if (response.success) {
                alert('Success: ' + response.message);
                window.location.href = base_url + 'online_admission';
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            button.prop('disabled', false).html(originalHtml);
            alert('Server Error: HTTP ' + xhr.status);
        }
    });
});
    
    // ========== INTERVIEW SCHEDULING HANDLER ==========
    $('#submitInterview').click(function() {
        if(admission_status != 1) {
            swal('Error', 'Interview can only be scheduled for pending admissions', 'error');
            return;
        }
        
        var submitBtn = $(this);
        var originalBtnHtml = submitBtn.html();
        
        var formData = {
            csrf_test_name: csrf_token,
            branch_id: <?=$stuDetails['branch_id'];?>,
            admission_id: admission_id,
            interview_date: $('#interview_date').val(),
            interview_time: $('#interview_time').val(),
            interview_type: $('#interview_type').val(),
            interviewer_id: $('#interviewer_id').val(),
            location: $('#location').val(),
            interview_notes: $('#interview_notes').val()
        };
        
        var requiredFields = ['interview_date', 'interview_time', 'interview_type', 'interviewer_id', 'location'];
        var valid = true;
        
        requiredFields.forEach(function(field) {
            if (!formData[field] || formData[field] === '') {
                $('#' + field).addClass('is-invalid');
                valid = false;
            } else {
                $('#' + field).removeClass('is-invalid');
            }
        });
        
        if (!valid) {
            swal('Error', 'Please fill in all required fields', 'error');
            return;
        }
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Scheduling...');
        
        $.ajax({
            url: base_url + 'online_admission/schedule_interview/' + admission_id,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#interviewModal').modal('hide');
                    swal('Success!', response.message, 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    swal('Error', response.message, 'error');
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                }
            },
            error: function(xhr) {
                swal('Server Error', 'Please try again', 'error');
                submitBtn.prop('disabled', false).html(originalBtnHtml);
            }
        });
    });

	// ========== UPDATE CSRF TOKEN ==========
function updateCsrfToken() {
    // Get new CSRF token from meta tag or form
    var newToken = $('meta[name="csrf_token"]').attr('content') || 
                   $('input[name="csrf_test_name"]').val();
    
    if (newToken) {
        csrf_token = newToken;
        console.log('CSRF token updated:', csrf_token.substring(0, 10) + '...');
    }
}

// Update CSRF token on any form submission
$(document).ajaxComplete(function(event, xhr, settings) {
    // Check if response has new CSRF token
    if (xhr.responseText && xhr.responseText.includes('csrf_test_name')) {
        var match = xhr.responseText.match(/name="csrf_test_name" value="([^"]+)"/);
        if (match && match[1]) {
            // Update all CSRF token inputs on page
            $('input[name="csrf_test_name"]').val(match[1]);
            csrf_token = match[1];
        }
    }
});

	// start here but remove later
	
$(document).on('click', '#testSimpleApprove', function() {
    console.log('Testing simple form submission...');
    
    // Get FRESH CSRF token from the form
    var freshToken = $('input[name="csrf_test_name"]').val();
    console.log('Fresh CSRF token:', freshToken ? 'Yes' : 'No');
    
    // Create a minimal form submission
    var formData = new FormData();
    formData.append('first_name', 'Test');
    formData.append('register_no', 'TEST001');
    formData.append('class_id', 1);
    formData.append('section_id', 2);
    formData.append('year_id', 4);
    formData.append('action_type', 'approve');
    formData.append('csrf_test_name', freshToken); // Use fresh token
    
    console.log('Sending to:', $('#mainForm').attr('action'));
    
    $.ajax({
        url: $('#mainForm').attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response, status, xhr) {
            console.log('Simple test success:', response);
            $('#testResult').html('<span style="color:green">✓ Form submit: ' + response.message + '</span>');
            
            // Update CSRF token from response if needed
            updateCsrfToken();
        },
        error: function(xhr, status, error) {
            console.log('Simple test error:', xhr.status, xhr.statusText);
            console.log('Response text:', xhr.responseText);
            
            // Check if it's CSRF error
            if (xhr.responseText && xhr.responseText.includes('csrf') || 
                xhr.responseText.includes('CSRF') ||
                xhr.responseText.includes('403')) {
                $('#testResult').html('<span style="color:red">✗ CSRF Token Error - Page reload needed</span>');
                
                // Force page reload to get new CSRF token
                setTimeout(function() {
                    if (confirm('CSRF token expired. Reload page?')) {
                        location.reload();
                    }
                }, 1000);
            } else {
                $('#testResult').html('<span style="color:red">✗ Form failed: HTTP ' + xhr.status + '</span>');
            }
        }
    });
});

    
    // ========== REAL-TIME FORM VALIDATION ==========
    // Highlight fields as user fills them
    $('#mainForm input, #mainForm select').on('blur', function() {
        var $field = $(this);
        var value = $field.val();
        
        if ($field.is('[required]') && (!value || value.trim() === '')) {
            $field.addClass('is-invalid');
        } else {
            $field.removeClass('is-invalid');
        }
    });
    
    // Password match validation
    $('input[name="retype_password"]').on('keyup', function() {
        var password = $('input[name="password"]').val();
        var retypePassword = $(this).val();
        
        if (password !== retypePassword) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>