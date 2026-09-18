<?php $widget = (is_superadmin_loggedin() ? 4 : 6); ?>
<section class="panel">

    <!-- In-app Messages Container -->
    <div id="smsMessagesContainer">
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade in" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <h4><i class="fa fa-check-circle"></i> Success!</h4>
            <p><?= $success_message ?></p>
            <?php if (isset($sent_count) && isset($total_recipients)): ?>
            <p>Successfully sent to <?= $sent_count ?> of <?= $total_recipients ?> recipient(s).</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade in" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <h4><i class="fa fa-exclamation-triangle"></i> Error!</h4>
            <p><?= $error_message ?></p>
        </div>
        <?php endif; ?>
    </div>
    <!-- end of sms messages container -->
    
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li class="active"> <a href="#sms" data-toggle="tab"> <i class="far fa-comment"></i> SMS</a> </li>
			<li><a href="<?=base_url('sendsmsmail/email')?>"> <i class="far fa-envelope"></i> Email</a> </li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane box active" id="sms">
				<?php echo form_open('sendsmsmail/save', array('class' => 'frm-submit')); ?>
				<input type="hidden" name="message_type" value="<?=$this->uri->segment(2)?>">
				<div class="row">
					<?php if (is_superadmin_loggedin()): ?>
				
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
							<?php
							$arrayBranch = $this->app_lib->getSelectList('branch');
							echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' data-width='100%' id='branch_id'
							data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
					<?php endif; ?>
								<!-- SMS Credit Display Section -->
								<!-- SMS Credit Display Section -->
				<div class="row">
					<div class="col-md-12 mb-sm">
						<div class="alert" id="smsCreditAlert">
							<div class="row">
								<div class="col-md-12 mb-2">
									<small id="branchInfo" class="text-muted float-right">
										<?php if (is_superadmin_loggedin()): ?>
											Branch ID: <?= $this->data['branch_id'] ?>
										<?php endif; ?>
									</small>
								</div>
								<div class="col-md-4 mb-2">
									<strong><i class="fa fa-credit-card"></i> Available Credits:</strong><br>
									<span id="currentSmsBalance" class="h5">
										<?= isset($current_balance) ? $current_balance . ' credits' : 'Loading...' ?>
									</span>
								</div>
								<div class="col-md-4 mb-2">
									<strong><i class="fa fa-calculator"></i> Estimated Cost:</strong><br>
									<span id="estimatedSmsCost" class="h5">0 credits</span>
								</div>
								<div class="col-md-4 mb-2">
									<strong><i class="fa fa-coins"></i> Remaining After Send:</strong><br>
									<span id="remainingBalance" class="h5">
										<?= isset($current_balance) ? $current_balance . ' credits' : '--' ?>
									</span>
								</div>
							</div>
							<div class="row mt-2">
								<div class="col-md-8">
									<small class="text-muted">
										<i class="fa fa-info-circle"></i> SMS Cost: 1 credit per 160 characters (GSM) or 70 characters (Unicode).
									</small>
									<div id="smsCostDetails" class="small text-muted mt-1" style="display: none;">
										<!-- Will be populated dynamically -->
									</div>
								</div>
								<div class="col-md-4 text-right">
									<div id="creditActions" style="display: none;">
										<!-- Will show purchase button when credits are low -->
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- End of SMS Credit Display Section -->

					<div class="col-md-<?=$widget?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('campaign_name')?> <span class="required">*</span></label>
							<input type="text" class="form-control" name="campaign_name" value="" />
							<span class="error"></span>
						</div>
					</div>
					<div class="col-md-<?=$widget?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('template')?></label>
							<?php
								$arrayTemplate = $this->app_lib->getSelectByBranch('bulk_msg_category', $branch_id, false, array('type' => 1));
								echo form_dropdown("sms_template", $arrayTemplate, set_value('sms_template'), "class='form-control' id='sms_template'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 mb-sm">
						<div class="form-group">
							<label><?=translate('message')?> <span class="required">*</span></label>
							<textarea class="form-control" name="message" rows="5" id="message"></textarea>
							<span class="error"></span>
							<div class="pull-right pr-xs pl-xs alert-danger"> 
								<span id="remaining_count"> 160 characters remaining</span> <span id="messages">1 message </span>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('sms_gateway')?> <span class="required">*</span></label>
							<?php
								$arrayGateway = array('' => translate('select'));
								echo form_dropdown("sms_gateway", $arrayGateway, set_value('sms_gateway'), "class='form-control' id='sms_gateway'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label">DLT Template ID</label>
							<input type="text" class="form-control" name="dlt_template_id" value="" placeholder="This field is only required for Indian SMS Gateway (Ex. MSG 91).">
							<span class="error"></span>
						</div>
					</div>
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label"> <?=translate('type')?> <span class="required">*</span></label>
							<?php
							$arrayType = array(
								"" => translate('select'),
								"1" => translate('group'),
								"2" => translate('individual'),
								"3" => translate('class'),
							);
							echo form_dropdown("recipient_type", $arrayType, "", "class='form-control' id='typeID' data-plugin-selectTwo
							data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
				</div>
				<div class="row hidden-div" id="group_div">
					<div class="col-md-12 mb-sm">
						<div class="form-group">
							<label class="control-label">Role <span class="required">*</span></label>
							<?php
								$role_list = $this->app_lib->getRoles(1);
								unset($role_list['']);
								echo form_dropdown("role_group[]", $role_list, "", "class='form-control' multiple id='role_group'
								data-plugin-selectTwo data-width='100%' ");
							?>
							<span class="error"></span>

							<div class="checkbox-replace mt-sm pr-xs pull-right">
								<label class="i-checks"><input type="checkbox" class="chk-sendsmsmail" name="chk_role"><i></i> Select All</label>
							</div>
						</div>
					</div>
				</div>
				<div class="row hidden-div" id="individual_div">
					<div class="col-md-12 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('role')?> <span class="required">*</span></label>
							<?php
								$role_list = $this->app_lib->getRoles(1);
								echo form_dropdown("role_id", $role_list, set_value('role_id'), "class='form-control' id='roleID' onchange='getRecipientsByRole()'
								data-plugin-selectTwo data-width='100%' ");
							?>
							<span class="error"><?=form_error('role')?></span>
						</div>
					</div>
					<div class="col-md-12 mb-sm">
						<div class="form-group">
							<label class="control-label">Name <span class="required">*</span></label>
							<select class="form-control" name="recipients[]" id="recipients" data-plugin-selectTwo multiple >
							
							</select>
							<span class="error"></span>

							<div class="checkbox-replace mt-sm pr-xs pull-right">
								<label class="i-checks"><input type="checkbox" class="chk-sendsmsmail" name="chk_recipients"><i></i> Select All</label>
							</div>
						</div>
					</div>
				</div>
				<div class="row hidden-div" id="class_div">
					<div class="col-md-12 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('class')?> <span class="required">*</span></label>
							<?php
								$arrayClass = $this->app_lib->getClass($branch_id);
								echo form_dropdown("class_id", $arrayClass, set_value('class_id'), "class='form-control' id='class_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"><?=form_error('class')?></span>
						</div>
					</div>
					<div class="col-md-12 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('section')?> <span class="required">*</span></label>
							<select class="form-control" name="section[]" id="section_id" data-plugin-selectTwo multiple >
							</select>
							<span class="error"></span>
							<div class="checkbox-replace mt-sm pr-xs pull-right">
								<label class="i-checks"><input type="checkbox" class="chk-sendsmsmail" name="chk_section"><i></i> Select All</label>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 mb-xs">
						<div class="form-group">
							<div class="checkbox-replace">
								<label class="i-checks"><input type="checkbox" name="send_later" id="send_later"><i></i> Send Later</label>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-8 mb-sm">
						<div class="form-group">
							<label class="control-label">Schedule Date <span class="required">*</span></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
								<input type="text" class="form-control" name="schedule_date" id="schedule_date" disabled value="<?=date('Y-m-d')?>" data-plugin-datepicker />
							</div>
							<span class="error"></span>
						</div>
					</div>
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label">Schedule Time <span class="required">*</span></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-clock"></i></span>
								<input type="text" name="schedule_time" id="schedule_time" disabled data-plugin-timepicker class="form-control"  value="<?=date('H:M a')?>" />
								<span class="error"></span>
							</div>
							<span class="error"></span>
						</div>
					</div>
				</div>
				<div class="mt-md">
					<strong>Dynamic Tag : </strong>
					<a data-value=" {name} " class="btn btn-default btn-xs btn_tag ">{name}</a>
					<a data-value=" {email} " class="btn btn-default btn-xs btn_tag">{email}</a>
					<a data-value=" {mobile_no} " class="btn btn-default btn-xs btn_tag">{mobile_no}</a>
				</div>
				<footer class="panel-footer">
					<div class="row">
						<div class="col-md-offset-10 col-md-2">
			                <button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
			                    <i class="far fa-share-square"></i> <?=translate('send') ?>
			                </button>
						</div>
					</div>
				</footer>
				<?php echo form_close(); ?>
			</div>
			<div class="tab-pane" id="add">
				
			</div>
		</div>
	</div>
</section>

<script type="text/javascript">
// ========== GLOBAL VARIABLES ==========
var isFormSubmitting = false;
var formSubmissionTimer = null;

// ========== MESSAGE HELPER FUNCTIONS ==========
function showMessage(type, title, message, details) {
    var icon = '';
    var alertClass = '';
    
    switch(type) {
        case 'success':
            icon = 'fa-check-circle';
            alertClass = 'alert-success';
            break;
        case 'error':
            icon = 'fa-exclamation-triangle';
            alertClass = 'alert-danger';
            break;
        case 'warning':
            icon = 'fa-exclamation-circle';
            alertClass = 'alert-warning';
            break;
        case 'info':
            icon = 'fa-info-circle';
            alertClass = 'alert-info';
            break;
    }
    
    var messageHtml = 
        '<div class="alert ' + alertClass + ' alert-dismissible fade in" role="alert">' +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">×</span></button>' +
        '<h4><i class="fa ' + icon + '"></i> ' + title + '</h4>' +
        '<p>' + message + '</p>';
    
    if (details && details.sent_count !== undefined && details.total_recipients !== undefined) {
        messageHtml += '<p>Successfully sent to ' + details.sent_count + ' of ' + details.total_recipients + ' recipient(s).</p>';
    }
    if (details && details.credits_used) {
        messageHtml += '<p><strong>Credits Used:</strong> ' + details.credits_used + '</p>';
    }
    
    messageHtml += '</div>';
    
    // Clear previous messages and show new one
    $('#smsMessagesContainer').html(messageHtml).show();
    
    // Scroll to message
    $('html, body').animate({
        scrollTop: $('#smsMessagesContainer').offset().top - 100
    }, 300);
    
    // Auto-dismiss success messages after 5 seconds
    if (type === 'success') {
        setTimeout(function() {
            $('#smsMessagesContainer .alert').alert('close');
        }, 5000);
    }
    
    // console.log('Message shown:', type, title, message);
}

function clearMessages() {
    $('#smsMessagesContainer').empty().hide();
}

function showProcessingMessage(action, recipients_count) {
    showMessage('info', 'Processing...', 
        action + ' SMS to ' + recipients_count + ' recipient(s)...<br>' +
        'Please wait, this may take a moment.',
        null
    );
}

function showSuccessMessage(message, details) {
    showMessage('success', 'Success!', message, details);
}

function showErrorMessage(title, message, isGatewayError) {
    var fullMessage = message;
    if (isGatewayError) {
        fullMessage += '<br><br><div class="alert alert-warning" style="background: rgba(255,255,255,0.3); margin: 10px 0; padding: 8px 12px;">' +
                     '<i class="fa fa-info-circle"></i> <strong>Note:</strong> This is a gateway error. Please contact the system administrator.</div>';
    }
    showMessage('error', title, fullMessage, null);
}

// ========== INITIALIZATION ==========
$(document).ready(function() {
    // console.log('Document ready fired');
    
    // Display any PHP flash messages on page load
    <?php if (isset($success_message)): ?>
    // console.log('PHP success message found:', '<?= addslashes($success_message) ?>');
    setTimeout(function() {
        showSuccessMessage('<?= addslashes($success_message) ?>', {
            sent_count: <?= isset($sent_count) ? $sent_count : 0 ?>,
            total_recipients: <?= isset($total_recipients) ? $total_recipients : 0 ?>
        });
    }, 500);
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    // console.log('PHP error message found:', '<?= addslashes($error_message) ?>');
    setTimeout(function() {
        showErrorMessage('Error', '<?= addslashes($error_message) ?>', false);
    }, 500);
    <?php endif; ?>
    
    // Initialize event handlers with debug
    // console.log('Initializing event handlers...');
    initializeEventHandlers();
    // console.log('Event handlers initialized');
    
    // Initialize SMS credit system
    setTimeout(function() {
        // console.log('Loading SMS balance...');
        loadSmsBalance();
    }, 500);
    
    // Call reset on page load
    resetFormSubmissionState();
    
    // DEBUG: Test click on recipient type dropdown
    $('#typeID').on('change', function() {
        // console.log('typeID changed to:', $(this).val());
    });
    
    // DEBUG: Test message typing
    $('#message').on('keyup', function() {
        // console.log('Message length:', $(this).val().length);
    });
});

// ========== FORM REPOPULATION ==========
function repopulateForm(oldPost) {
    if (oldPost.campaign_name) $('#campaign_name').val(oldPost.campaign_name);
    if (oldPost.message) {
        $('#message').val(oldPost.message);
        $('#message').trigger('keyup');
    }
    if (oldPost.sms_gateway) $('#sms_gateway').val(oldPost.sms_gateway).trigger('change');
    if (oldPost.dlt_template_id) $('[name="dlt_template_id"]').val(oldPost.dlt_template_id);
    
    if (oldPost.recipient_type) {
        $('#typeID').val(oldPost.recipient_type).trigger('change');
        
        setTimeout(function() {
            if (oldPost.recipient_type == '1' && oldPost.role_group) {
                $('#role_group').val(oldPost.role_group).trigger('change');
            }
            if (oldPost.recipient_type == '2') {
                if (oldPost.role_id) {
                    $('#roleID').val(oldPost.role_id).trigger('change');
                    setTimeout(function() {
                        if (oldPost.recipients) {
                            $('#recipients').val(oldPost.recipients).trigger('change');
                        }
                    }, 500);
                }
            }
            if (oldPost.recipient_type == '3') {
                if (oldPost.class_id) {
                    $('#class_id').val(oldPost.class_id).trigger('change');
                    setTimeout(function() {
                        if (oldPost.section) {
                            $('#section_id').val(oldPost.section).trigger('change');
                        }
                    }, 500);
                }
            }
            
            setTimeout(updateCostEstimation, 1000);
        }, 300);
    }
    
    if (oldPost.send_later) {
        $('#send_later').prop('checked', true).trigger('change');
        if (oldPost.schedule_date) $('#schedule_date').val(oldPost.schedule_date);
        if (oldPost.schedule_time) $('#schedule_time').val(oldPost.schedule_time);
    }
}

// ========== FORM SUBMISSION HANDLER ==========
function handleFormSubmit(e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    
    // console.log('Form submission started');
    
    // Check if form is already submitting
    if (isFormSubmitting) {
        showMessage('warning', 'Please Wait',
            'Your previous request is still processing. Please wait...',
            null
        );
        return false;
    }
    
    var form = this;
    var submitBtn = $(form).find('button[type="submit"]');
    var originalBtnText = submitBtn.html();
    
    // Set submitting flag
    isFormSubmitting = true;
    
    // Disable submit button
    submitBtn.prop('disabled', true);
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Validating...');
    
    // Clear previous messages
    clearMessages();
    
    // Get recipient count
    getRecipientCount(function(recipients_count) {
        if (recipients_count === 0) {
            showErrorMessage('No Recipients', 'Please select at least one recipient before sending.');
            resetFormState(form, submitBtn, originalBtnText);
            return;
        }
        
        // Get estimated cost
        var estimated_cost_text = $('#estimatedSmsCost').text();
        var current_balance_text = $('#currentSmsBalance').text();
        var estimated_cost = parseInt(estimated_cost_text.replace(/[^0-9]/g, '')) || 0;
        var current_balance = parseInt(current_balance_text.replace(/[^0-9]/g, '')) || 0;
        var remaining = current_balance - estimated_cost;
        
        // Check credits
        if (estimated_cost > current_balance) {
            var shortfall = estimated_cost - current_balance;
            showMessage('error', 'Insufficient Credits',
                'You need <strong>' + estimated_cost + ' credits</strong> but only have <strong>' + 
                current_balance + ' credits</strong> available.<br>' +
                '<strong>Shortfall:</strong> ' + shortfall + ' credits<br><br>' +
                '<a href="' + base_url + 'sendsmsmail/purchase" class="btn btn-warning btn-sm">' +
                '<i class="fa fa-shopping-cart"></i> Purchase Credits</a>',
                null
            );
            resetFormState(form, submitBtn, originalBtnText);
            return;
        }
        
        // Check if this is a scheduled SMS
        var isScheduled = $('#send_later').is(':checked');
        
        // Proceed with submission (no confirmation popup for immediate sends)
        proceedWithSubmission(form, submitBtn, originalBtnText, recipients_count, isScheduled);
    });
    
    return false;
}

// ========== PROCEED WITH SUBMISSION ==========
function proceedWithSubmission(form, submitBtn, originalBtnText, recipients_count, isScheduled) {
    // Update button text
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> ' + (isScheduled ? 'Scheduling...' : 'Sending...'));
    
    // Show processing message
    showProcessingMessage(isScheduled ? 'Scheduling' : 'Sending', recipients_count);
    
    // Set a timeout to prevent infinite waiting
    formSubmissionTimer = setTimeout(function() {
        showMessage('warning', 'Still Processing',
            'This is taking longer than expected. Please wait...',
            null
        );
    }, 10000);
    
    // Generate a unique submission ID
    var submissionId = 'sub_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    var hiddenField = $('<input type="hidden" name="submission_id" value="' + submissionId + '">');
    $(form).append(hiddenField);
    
    // Store the submission ID
    sessionStorage.setItem('last_sms_submission_id', submissionId);
    
    // Submit the form via AJAX
    submitFormViaAjax(form, submitBtn, originalBtnText, isScheduled);
}

// ========== AJAX SUBMISSION ==========
function submitFormViaAjax(form, submitBtn, originalBtnText, isScheduled) {
    var formData = new FormData(form);
    
    $.ajax({
        url: $(form).attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 30000,
        beforeSend: function(xhr) {
            var token = $('input[name="csrf_token_name"]').val() || $('input[name="csrf_test_name"]').val();
            if (token) {
                xhr.setRequestHeader('X-CSRF-TOKEN', token);
            }
        },
        success: function(response) {
            // console.log('AJAX Response:', response);
            
            // Clear the timeout
            clearTimeout(formSubmissionTimer);
            
            if (response.success) {
                // SUCCESS
                var action = isScheduled ? 'scheduled' : 'sent';
                var details = {
                    sent_count: response.sent_count || 0,
                    total_recipients: response.total_recipients || 0,
                    credits_used: response.credits_used || 0
                };
                
                showSuccessMessage(
                    '<strong>' + response.message + '</strong><br>' +
                    (isScheduled ? 'Your SMS has been scheduled successfully.' : 'Your SMS has been sent successfully.'),
                    details
                );
                
                // Clear form fields
                clearFormFields(form);
                
                // Reload balance after delay
                setTimeout(loadSmsBalance, 1500);
                
                // Reset form state
                setTimeout(function() {
                    resetFormState(form, submitBtn, originalBtnText);
                }, 2000);
                
            } else {
                // ERROR
                var errorTitle = 'Error';
                var errorMessage = response.message || 'An unknown error occurred.';
                var isGatewayError = response.gateway_error || false;
                
                // Determine error type
                if (response.message) {
                    if (response.message.includes('already scheduled') || response.message.includes('duplicate')) {
                        errorTitle = 'Duplicate Message';
                    } else if (response.message.includes('Insufficient credits') || response.message.includes('insufficient credits')) {
                        errorTitle = 'Insufficient Credits';
                    } else if (response.message.includes('gateway') || isGatewayError) {
                        errorTitle = 'Gateway Error';
                        isGatewayError = true;
                    } else if (response.message.includes('validation') || response.message.includes('required')) {
                        errorTitle = 'Validation Error';
                    }
                }
                
                showErrorMessage(errorTitle, errorMessage, isGatewayError);
                
                // Reset form state
                resetFormState(form, submitBtn, originalBtnText);
            }
        },
        error: function(xhr, status, error) {
            console.error('Cost calculation AJAX error:', error);
                        // console.log('Response:', xhr.responseText);
            console.error('AJAX Error:', status, error);
            
            // Clear the timeout
            clearTimeout(formSubmissionTimer);
            
            var errorTitle = 'Network Error';
            var errorMessage = '';
            
            if (status === 'timeout') {
                errorMessage = 'Request timed out. The server is taking too long to respond.<br>' +
                             'Your SMS may have been sent. Please check the sent messages page.';
            } else if (status === 'parsererror') {
                errorMessage = 'Server returned invalid response.<br>' +
                             'Please try again or contact support.';
            } else {
                // console.log('No message entered');
                errorMessage = 'Failed to connect to server.<br>' +
                             'Please check your internet connection and try again.';
            }
            
            showErrorMessage(errorTitle, errorMessage, false);
            
            // Reset form state
            resetFormState(form, submitBtn, originalBtnText);
        },
        complete: function() {
            // Remove the hidden field
            $(form).find('input[name="submission_id"]').remove();
        }
    });
}

// ========== FORM STATE MANAGEMENT ==========
function resetFormState(form, submitBtn, originalText) {
    // console.log('Resetting form state');
    isFormSubmitting = false;
    if (formSubmissionTimer) {
        clearTimeout(formSubmissionTimer);
        formSubmissionTimer = null;
    }
    submitBtn.prop('disabled', false);
    submitBtn.html(originalText);
}

function resetFormSubmissionState() {
    isFormSubmitting = false;
    if (formSubmissionTimer) {
        clearTimeout(formSubmissionTimer);
        formSubmissionTimer = null;
    }
}

function clearFormFields(form) {
    // Don't clear branch or template selectors
    $(form).find('input[name="campaign_name"]').val('');
    $(form).find('textarea[name="message"]').val('');
    $(form).find('input[name="dlt_template_id"]').val('');
    $(form).find('#recipients').val(null).trigger('change');
    $(form).find('#role_group').val(null).trigger('change');
    $(form).find('#section_id').val(null).trigger('change');
    $(form).find('input[name="send_later"]').prop('checked', false);
    $(form).find('#schedule_time').prop('disabled', true);
    $(form).find('#schedule_date').prop("disabled", true);
    
    // Reset SMS counter
    $('#remaining_count').text('160 characters remaining');
    $('#messages').text('1 message');
    
    // Reset cost estimation
    $('#estimatedSmsCost').text('0 credits');
    $('#remainingBalance').text($('#currentSmsBalance').text());
    $('#smsCostDetails').hide();
    removeScheduledSmsWarning();
}

// ========== EVENT HANDLERS ==========
function initializeEventHandlers() {
    // console.log('=== initializeEventHandlers() called ===');
    // Branch change
    $('#branch_id').on('change', function() {
        // console.log('Branch changed to:', $(this).val());
        var branchID = $(this).val();
        getRecipientsByRole();
        getClassByBranch(branchID);
        getSmsGateway();

        $.ajax({
            url: "<?=base_url('sendsmsmail/getTemplateByBranch')?>",
            type: 'POST',
            data: {
                branch_id : branchID,
                type : "sms",
            },
            success: function (data) {
                $('#sms_template').html(data);
            }
        });
        
        // Load balance for new branch
        loadSmsBalance();
    });

    // Recipient type change
    $('#typeID').on('change', function() {
        var val = $(this).val();
        // console.log('Recipient type changed to:', val);
        $('.hidden-div').hide();
        
        if (val == 1) {
            $("#group_div").show('slow');
        } else if (val == 2) {
            $("#individual_div").show('slow');
        } else if (val == 3) {
            $("#class_div").show('slow');
        }
        
        // Update cost estimation
        setTimeout(updateCostEstimation, 300);
    });

    // Check all checkboxes
    $('.chk-sendsmsmail').on('change', function() {
        // console.log('Checkbox changed');
        if($(this).is(':checked') ){
            $(this).parents('.form-group').find('select > option').prop("selected","selected");
            $(this).parents('.form-group').find('select').trigger("change");
        } else {
            $(this).parents('.form-group').find('select').val(null).trigger('change');
        }
        
        // Update cost estimation
        setTimeout(updateCostEstimation, 100);
    });

    // Send later checkbox
    $('#send_later').on('change', function() {
        // console.log('Send later changed:', $(this).is(':checked'));
        if($(this).is(':checked') ){
            $('#schedule_time').prop("disabled", false);
            $('#schedule_date').prop("disabled", false);
            updateScheduledSmsWarning();
        } else {
            $('#schedule_time').prop("disabled", true);
            $('#schedule_date').prop("disabled", true);
            removeScheduledSmsWarning();
        }
    });

    // SMS characters counter
    $('#message').keyup(function(){
        // console.log('Message keyup, length:', this.value.length);
        var chars = this.value.length,
            messages = Math.ceil(chars / 160),
            remaining = messages * 160 - (chars % (messages * 160) || messages * 160);

        $('#remaining_count').text(remaining + ' characters remaining');
        $('#messages').text(messages + ' message');
        
        // Update cost estimation
        updateCostEstimation();
    });

    // Dynamic tags
    $('.btn_tag').on('click', function() {
         // console.log('Tag clicked:', $(this).data("value"));
        var $txt = $("#message");
        var caretPos = $txt[0].selectionStart;
        var textAreaTxt = $txt.val();
        var txtToAdd = $(this).data("value");
        $txt.val(textAreaTxt.substring(0, caretPos) + txtToAdd + textAreaTxt.substring(caretPos) );
        
        // Update cost estimation
        updateCostEstimation();
    });

    // Schedule date/time change
    $('#schedule_date, #schedule_time').on('change', function() {
        // console.log('Schedule changed');
        if ($('#send_later').is(':checked')) {
            checkScheduledDuplicate();
        }
    });

    // Form submission
    $('.frm-submit').on('submit', handleFormSubmit);
     // console.log('All event handlers attached');
    
   // Clear submission state when leaving page
$(window).on('beforeunload', function() {
    isFormSubmitting = false;
    // Add return undefined to prevent browser prompt
    return undefined;
});

    // ADD THESE NEW EVENT HANDLERS FOR RECIPIENT SELECTION:
    
    // Group role selection change
    $('#role_group').on('change', function() {
        // console.log('Role group selection changed');
        setTimeout(updateCostEstimation, 200);
        updateRecipientCountDisplay();
    });
    
    // Individual recipient selection change
    $('#recipients').on('change', function() {
        // console.log('Individual recipients changed');
        setTimeout(updateCostEstimation, 200);
        updateRecipientCountDisplay();
    });
    
    // Class section selection change
    $('#section_id').on('change', function() {
        // console.log('Sections changed');
        setTimeout(updateCostEstimation, 200);
        updateRecipientCountDisplay();
    });
    
    // Role ID change (for individual recipients)
    $('#roleID').on('change', function() {
        // console.log('Role ID changed:', $(this).val());
        setTimeout(updateCostEstimation, 500); // Wait for recipients to load
    });
    
    // Class ID change (for class recipients)
    $('#class_id').on('change', function() {
        // console.log('Class ID changed:', $(this).val());
        setTimeout(updateCostEstimation, 500); // Wait for sections to load
    });
}

// ========== EXISTING FUNCTIONS TO KEEP ==========

function getSmsGateway() {
    var branchID = ($('#branch_id').length ? $('#branch_id').val() : "");
    $.ajax({
        url: "<?=base_url('sendsmsmail/getSmsGateway')?>",
        type: 'POST',
        data: {
            branch_id : branchID
        },
        success: function (data) {
            $('#sms_gateway').html(data);
        }
    });
}

function getRecipientsByRole() {
    var roleID = $('#roleID').val();
    var branchID = ($('#branch_id').length ? $('#branch_id').val() : "");
    if (roleID !== '') {
        $.ajax({
            url: "<?=base_url('sendsmsmail/getRecipientsByRole')?>",
            type: 'POST',
            data: {
                branch_id : branchID,
                role_id : roleID,
            },
            success: function (data) {
                $('#recipients').html(data);
            }
        });
    }
}

function getClassByBranch(branchID) {
    // This function might be referenced elsewhere
    // console.log('getClassByBranch called for branch:', branchID);
    // If you have actual implementation, keep it here
}

$('#class_id').on('change', function() {
    var classID = $(this).val();
    $.ajax({
        url: base_url + 'sendsmsmail/getSectionByClass',
        type: 'POST',
        data: {
            class_id: classID,
        },
        success: function (response) {
            $('#section_id').html(response);
        }
    });
});

$('#sms_template').on('change', function() {
    var templateID = $(this).val();
    $.ajax({
        url: base_url + 'sendsmsmail/getSmsTemplateText',
        type: 'POST',
        data: {
            id: templateID,
        },
        success: function (response) {
            $("#message").val(response).trigger('keyup');
        }
    });
});

// Function to get actual recipient count via AJAX
function getRecipientCount(callback) {
    // console.log('=== getRecipientCount() called ===');
    var type = $('#typeID').val();
    var data = {};
    
    // console.log('Recipient type:', type);
    
    if (!type) {
        // console.log('No type selected, returning 0');
        if (typeof callback === 'function') {
            callback(0);
        }
        return 0;
    }
    
    // BUILD THE DATA PROPERLY - THIS IS CRITICAL!
    switch(type) {
        case '1': // Group
            var selectedRoles = $('#role_group').val();
            // console.log('Selected roles:', selectedRoles);
            if (selectedRoles && selectedRoles.length > 0) {
                data.roles = selectedRoles;
            } else {
                // console.log('No roles selected');
                if (typeof callback === 'function') {
                    callback(0);
                }
                return 0;
            }
            break;
            
        case '2': // Individual
            var selectedRecipients = $('#recipients').val();
            // console.log('Selected recipients:', selectedRecipients);
            if (selectedRecipients && selectedRecipients.length > 0) {
                data.recipients = selectedRecipients;
            } else {
                // console.log('No recipients selected');
                if (typeof callback === 'function') {
                    callback(0);
                }
                return 0;
            }
            break;
            
        case '3': // Class
            var selectedSections = $('#section_id').val();
            // console.log('Selected sections:', selectedSections);
            if (selectedSections && selectedSections.length > 0) {
                data.sections = selectedSections;
            } else {
                // console.log('No sections selected');
                if (typeof callback === 'function') {
                    callback(0);
                }
                return 0;
            }
            break;
    }
    
    // CHECK IF DATA IS EMPTY
    if (Object.keys(data).length === 0) {
        // console.log('No data collected, returning 0');
        if (typeof callback === 'function') {
            callback(0);
        }
        return 0;
    }
    
    // console.log('Making AJAX call with data:', data);
    
    // MAKE THE AJAX CALL
    $.ajax({
        url: base_url + 'sendsmsmail/get_recipient_count',
        type: 'POST',
        data: {
            type: type,
            data: data
        },
        dataType: 'json',
        beforeSend: function(xhr) {
            // console.log('AJAX request sent for recipient count');
            // Add CSRF token
            var token = $('input[name="csrf_token_name"]').val() || $('input[name="csrf_test_name"]').val();
            if (token) {
                xhr.setRequestHeader('X-CSRF-TOKEN', token);
                // console.log('CSRF token added:', token.substring(0, 10) + '...');
            }
        },
        success: function(response) {
            // console.log('AJAX Success Response for recipient count:', response);
            if (response.success && typeof callback === 'function') {
                // console.log('Calling callback with count:', response.count);
                callback(response.count);
            } else {
                // console.log('AJAX response not successful:', response);
                if (typeof callback === 'function') {
                    callback(0);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error for recipient count:', error);
            // console.log('Status:', status);
            // console.log('Response Text:', xhr.responseText);
            
            if (typeof callback === 'function') {
                callback(0);
            }
        }
    });
    
    // Don't return anything here - let AJAX handle it
    return null;
}
function containsUnicode(str) {
    // More accurate Unicode detection
    for (var i = 0; i < str.length; i++) {
        var charCode = str.charCodeAt(i);
        if (charCode > 127) {
            return true;
        }
    }
    
    var unicodePattern = /[^\u0000-\u007F\u00A0-\u00FF\u0100-\u017F\u0180-\u024F]/;
    if (unicodePattern.test(str)) {
        return true;
    }
    
    return false;
}

function loadSmsBalance() {
    $('#currentSmsBalance').html('<i class="fa fa-spinner fa-spin"></i> Loading...');
    
    var postData = {};
    
    // Always send the selected branch for superadmin
    var branchSelect = $('#branch_id');
    if (branchSelect.length && branchSelect.val()) {
        postData.branch_id = branchSelect.val();
    }
    
    $.ajax({
        url: base_url + 'sendsmsmail/get_sms_balance',
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var balanceHtml = '<span class="badge badge-primary" style="font-size: 16px; padding: 8px 12px;">' + 
                                 response.balance + ' credits</span>';
                $('#currentSmsBalance').html(balanceHtml);
                
                // Store branch info
                $('#smsCreditAlert')
                    .data('branch-id', response.branch_id)
                    .data('is-superadmin', response.is_superadmin);
                
                // Update branch info display
                if (response.is_superadmin) {
                    $('#branchInfo').html('Currently viewing Branch ID: <strong>' + response.branch_id + '</strong>');
                } else {
                    $('#branchInfo').html('');
                }
                
                // Update remaining balance display
                $('#remainingBalance').text(response.balance + ' credits');
                
                // Trigger cost estimation if there's a message
                if ($('#message').val().length > 0) {
                    updateCostEstimation();
                }
            } else {
                $('#currentSmsBalance').html('<span class="text-danger">' + response.message + '</span>');
            }
        },
        error: function(xhr, status, error) {
            $('#currentSmsBalance').html('<span class="text-danger">Connection error</span>');
            console.error('AJAX Error:', error);
        }
    });
}

function updateCostEstimation() {
    // console.log('=== updateCostEstimation() called ===');
    var message = $('#message').val();
    
    // Get current branch
    var branch_id = $('#smsCreditAlert').data('branch-id');
    var is_superadmin = $('#smsCreditAlert').data('is-superadmin');

    // console.log('Branch ID from data:', branch_id);
    // console.log('Is superadmin:', is_superadmin);
    
    // For superadmin, always use the selected branch from dropdown
    if (is_superadmin && $('#branch_id').length) {
        var selected_branch = $('#branch_id').val();
        if (selected_branch) {
            branch_id = selected_branch;
            // console.log('Got branch ID from dropdown:', branch_id);
        }
    }
    
    if (!branch_id) {
        // console.log('Calculating cost for message...');
        $('#estimatedSmsCost').html('<span class="text-warning">Select branch</span>');
        $('#remainingBalance').html('<span class="text-warning">--</span>');
        return;
    }
    
    if (message.length > 0) {
        // Show loading for recipient count
        $('#estimatedSmsCost').html('<i class="fa fa-spinner fa-spin"></i> Calculating...');
        
        // Get actual recipient count
         // console.log('Calling getRecipientCount()...');
        getRecipientCount(function(recipients_count) {
           
            // console.log('Recipient count callback received:', recipients_count);
            if (recipients_count > 0) {
                $.ajax({
                    url: base_url + 'sendsmsmail/calculate_estimated_cost',
                    type: 'POST',
                    data: {
                        message: message,
                        recipients_count: recipients_count,
                        branch_id: branch_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        // console.log('Cost calculation AJAX response:', response);
                        if (response.success) {
                            $('#estimatedSmsCost').text(response.estimated_cost + ' credits');
                            $('#remainingBalance').text(response.remaining + ' credits');
                            
                            // Show cost details
                            showCostDetails(message, recipients_count, response.estimated_cost, response.current_balance);
                            
                            // Update credit warning
                            updateCreditWarning(response.current_balance, response.estimated_cost);
                            
                            // Update alert color
                            var creditAlert = $('#smsCreditAlert');
                            creditAlert.removeClass('alert-danger alert-warning alert-success alert-info');
                            
                            if (response.remaining < 0) {
                                creditAlert.addClass('alert-danger');
                            } else if (response.remaining < 10) {
                                creditAlert.addClass('alert-warning');
                            } else {
                                creditAlert.addClass('alert-success');
                            }
                            
                            // Update SMS parts display
                            var chars = message.length;
                            var isUnicode = containsUnicode(message);
                            var charsPerSms = isUnicode ? 70 : 160;
                            var smsParts = Math.ceil(chars / charsPerSms);
                            
                            $('#messages').text(smsParts + ' SMS part' + (smsParts > 1 ? 's' : ''));
                            
                            // Update scheduled SMS warning
                            if ($('#send_later').is(':checked')) {
                                updateScheduledSmsWarning();
                            }
                            
                        } else {
                            $('#estimatedSmsCost').html('<span class="text-warning">' + response.message + '</span>');
                            $('#smsCostDetails').hide();
                        }
                    },
                    error: function() {
                        $('#estimatedSmsCost').html('<span class="text-danger">Error</span>');
                    }
                });
            } else {
                $('#estimatedSmsCost').text('0 credits');
                var currentText = $('#currentSmsBalance').text();
                var currentBalance = currentText.replace(/[^0-9]/g, '') || '0';
                $('#remainingBalance').text(currentBalance + ' credits');
                $('#smsCostDetails').html('<div class="text-muted small">Select recipients to calculate cost</div>').show();
            }
        });
    } else {
        $('#estimatedSmsCost').text('0 credits');
        var currentText = $('#currentSmsBalance').text();
        var currentBalance = currentText.replace(/[^0-9]/g, '') || '0';
        $('#remainingBalance').text(currentBalance + ' credits');
        $('#smsCostDetails').hide();
        removeScheduledSmsWarning();
    }
}

// Function to show detailed cost breakdown
function showCostDetails(message, recipients_count, estimated_cost, current_balance) {
    var detailsDiv = $('#smsCostDetails');
    var chars = message.length;
    var isUnicode = containsUnicode(message);
    var charsPerSms = isUnicode ? 70 : 160;
    var smsParts = Math.ceil(chars / charsPerSms);
    var creditsPerPart = isUnicode ? 2 : 1;
    
    // Calculate actual cost per recipient
    var costPerRecipient = smsParts * creditsPerPart;
    
    var detailsHtml = 
        '<div class="cost-breakdown">' +
        '   <strong>Cost Breakdown:</strong><br>' +
        '   • Message length: ' + chars + ' characters (' + (isUnicode ? 'Unicode' : 'GSM') + ' encoding)<br>' +
        '   • SMS parts: ' + smsParts + ' (' + charsPerSms + ' chars per part)<br>' +
        '   • Recipients: ' + recipients_count + ' person(s)<br>' +
        '   • Cost per recipient: ' + costPerRecipient + ' credit(s) (' + smsParts + ' part(s) × ' + creditsPerPart + ' credit/part)<br>' +
        '   • <strong>Total cost: ' + smsParts + ' parts × ' + recipients_count + ' recipients × ' + creditsPerPart + ' credit(s) = ' + estimated_cost + ' credits</strong>' +
        '</div>';
    
    detailsDiv.html(detailsHtml).show();
}

// Function to update credit warning
function updateCreditWarning(current_balance, estimated_cost) {
    var remaining = current_balance - estimated_cost;
    var actionsDiv = $('#creditActions');
    var creditAlert = $('#smsCreditAlert');
    
    // Clear previous classes
    creditAlert.removeClass('alert-danger alert-warning alert-success alert-info');
    
    if (remaining < 0) {
        creditAlert.addClass('alert-danger');
        actionsDiv.html(
            '<div class="btn-group">' +
            '   <a href="' + base_url + 'sendsmsmail/purchase" class="btn btn-danger btn-sm">' +
            '       <i class="fa fa-exclamation-triangle"></i> Insufficient Credits' +
            '   </a>' +
            '</div>'
        ).show();
        
    } else if (remaining < 10) {
        creditAlert.addClass('alert-warning');
        actionsDiv.html(
            '<a href="' + base_url + 'sendsmsmail/purchase" class="btn btn-warning btn-sm">' +
            '   <i class="fa fa-shopping-cart"></i> Top Up Credits' +
            '</a>'
        ).show();
        
    } else if (remaining < 50) {
        creditAlert.addClass('alert-info');
        actionsDiv.html(
            '<a href="' + base_url + 'sendsmsmail/purchase" class="btn btn-info btn-sm">' +
            '   <i class="fa fa-credit-card"></i> Add Credits' +
            '</a>'
        ).hide();
        
    } else {
        creditAlert.addClass('alert-success');
        actionsDiv.hide();
    }
}

// Function to update recipient count display
// FIX THIS FUNCTION:
function updateRecipientCountDisplay() {
    // console.log('updateRecipientCountDisplay called');
    
    // ADD THE MISSING PARAMETER
    getRecipientCount(function(count) {
        // console.log('Recipient count for display:', count);
        
        // Update UI if needed - FIX THE count VARIABLE ISSUE
        if (count > 0) {
            // Remove any existing display
            $('#recipientCountDisplay').remove();
            
            // Create display
            var displayHtml = '<div id="recipientCountDisplay" class="small text-success mt-1">' +
                             '<i class="fa fa-users"></i> Selected: ' + count + ' recipient(s)' +
                             '</div>';
            
            // Add it after the recipient type dropdown
            $('#typeID').closest('.form-group').append(displayHtml);
            // console.log('Display updated with count:', count);
        } else {
            $('#recipientCountDisplay').remove();
        }
    });
}

// ========== NEW SCHEDULED SMS FUNCTIONS ==========

// Function to check for scheduled duplicate
function checkScheduledDuplicate() {
    if (!$('#send_later').is(':checked')) return;
    
    var fingerprint = generateScheduledFingerprint();
    var schedule_date = $('#schedule_date').val();
    var schedule_time = $('#schedule_time').val();
    
    if (!fingerprint || !schedule_date || !schedule_time) return;
    
    $.ajax({
        url: base_url + 'sendsmsmail/check_scheduled_duplicate',
        type: 'POST',
        data: {
            fingerprint: fingerprint,
            schedule_date: schedule_date,
            schedule_time: schedule_time
        },
        dataType: 'json',
        success: function(response) {
            if (response.duplicate) {
                SmsMessenger.show('warning', 'Duplicate Scheduled Message',
                    'This identical message is already scheduled for the same time.<br>' +
                    'Please modify the message, recipients, or schedule time.',
                    []
                );
            }
        }
    });
}

// Function to generate fingerprint for scheduled duplicate check
function generateScheduledFingerprint() {
    var data = {
        campaign: $('#campaign_name').val(),
        message: $('#message').val(),
        recipient_type: $('#typeID').val(),
        recipients: ''
    };
    
    // Add recipients based on type
    if (data.recipient_type == '1') {
        data.recipients = $('#role_group').val() ? $('#role_group').val().join(',') : '';
    } else if (data.recipient_type == '2') {
        data.recipients = $('#recipients').val() ? $('#recipients').val().join(',') : '';
    } else if (data.recipient_type == '3') {
        data.recipients = $('#section_id').val() ? $('#section_id').val().join(',') : '';
    }
    
    // Simple hash function
    var str = JSON.stringify(data);
    var hash = 0;
    for (var i = 0; i < str.length; i++) {
        var char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return Math.abs(hash).toString();
}

// Function to update scheduled SMS warning
function updateScheduledSmsWarning() {
    var estimated_cost = $('#estimatedSmsCost').text();
    var cost = parseInt(estimated_cost.replace(/[^0-9]/g, '')) || 0;
    
    if (cost > 0) {
        // Add scheduled SMS warning to cost details
        var detailsDiv = $('#smsCostDetails');
        if (detailsDiv.length) {
            var currentHtml = detailsDiv.html();
            if (currentHtml.indexOf('scheduled-warning') === -1) {
                var warningHtml = '<div class="scheduled-warning alert alert-warning mt-2 p-2">' +
                                 '<i class="fa fa-info-circle"></i> <strong>Important:</strong> Credits will be deducted immediately when scheduling.<br>' +
                                 '<small>Credits are reserved to guarantee delivery at the scheduled time.</small>' +
                                 '</div>';
                detailsDiv.append(warningHtml);
            }
        }
    }
}

// Function to remove scheduled SMS warning
function removeScheduledSmsWarning() {
    $('.scheduled-warning').remove();
}



// Initialize on page load
$(document).ready(function() {
    getSmsGateway();
});


// Check if any JavaScript console errors exist
// // console.log('JavaScript initialization complete');
</script>

<style>
/* Animation for messages */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#smsMessagesContainer .alert {
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    border-left: 5px solid;
    animation: slideIn 0.3s ease;
}

#smsMessagesContainer .alert-success {
    border-left-color: #5cb85c;
    background: linear-gradient(to right, #f8fff9, #ffffff);
}

#smsMessagesContainer .alert-danger {
    border-left-color: #d9534f;
    background: linear-gradient(to right, #fff5f5, #ffffff);
}

#smsMessagesContainer .alert-warning {
    border-left-color: #f0ad4e;
    background: linear-gradient(to right, #fffbf0, #ffffff);
}

#smsMessagesContainer .alert-info {
    border-left-color: #5bc0de;
    background: linear-gradient(to right, #f5fbff, #ffffff);
}

#smsMessagesContainer .alert h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-weight: 600;
}

#smsMessagesContainer .message-content {
    font-size: 14px;
    line-height: 1.6;
}

/* Credit display styling */
#smsCreditAlert {
    transition: all 0.3s ease;
    border-left: 5px solid #3498db;
}

#smsCreditAlert.alert-success {
    border-left-color: #2ecc71;
}

#smsCreditAlert.alert-warning {
    border-left-color: #f39c12;
    animation: pulse 1.5s infinite;
}

#smsCreditAlert.alert-danger {
    border-left-color: #e74c3c;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
    100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #smsCreditAlert .col-md-4 {
        margin-bottom: 15px;
    }
    #smsMessagesContainer .alert {
        margin: 10px;
    }
}
</style>