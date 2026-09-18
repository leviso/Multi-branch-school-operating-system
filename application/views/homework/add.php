<?php
$branch_id = $this->application_model->get_branch_id();
if (is_superadmin_loggedin() && $this->input->post('branch_id')) {
    $branch_id = $this->input->post('branch_id');
}
?>
<div class="row">
	<div class="col-md-12">
		<section class="panel">
			<?php echo form_open_multipart($this->uri->uri_string(), array('class' => 'form-bordered form-horizontal frm-submit-data'));?>
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-plus-circle"></i> <?=translate('add') . " " . translate('homework')?></h4>
			</header>
			<div class="panel-body mb-md">
				<div class="mt-md"></div>
				<?php if (is_superadmin_loggedin()): ?>
					<div class="form-group">
						<label class="control-label col-md-3"><?=translate('branch')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
								$arrayBranch = $this->app_lib->getSelectList('branch');
								echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' id='branch_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
				<?php endif; ?>
				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('class')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<?php
							$arrayClass = $this->app_lib->getClass($branch_id);
							echo form_dropdown("class_id", $arrayClass, set_value('class_id'), "class='form-control' id='class_id' onchange='getSectionByClass(this.value,0)'
							data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
						?>
						<span class="error"></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('section')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<?php
							$arraySection = $this->app_lib->getSections(set_value('class_id'), true);
							echo form_dropdown("section_id", $arraySection, set_value('section_id'), "class='form-control' id='section_id'
							data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
						?>
						<span class="error"></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('subject')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<?php
							if(!empty(set_value('class_id'))) {
								$arraySubject = array("" => translate('select'));
								$assigns = $this->db->get_where('subject_assign', array('class_id' => set_value('class_id'), 'section_id' => set_value('section_id')))->result();
								foreach ($assigns as $row){
									$arraySubject[$row->subject_id] = get_type_name_by_id('subject', $row->subject_id);
								}
							} else {
								$arraySubject = array("" => translate('select_class_first'));
							}
							echo form_dropdown("subject_id", $arraySubject, set_value('subject_id'), "class='form-control' id='subject_id'
							data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
						?>
						<span class="error"></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('date_of_homework')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<div class="input-group">
							<span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
							<input type="text" class="form-control" name="date_of_homework" id="date_of_homework" value="<?=set_value('date_of_homework')?>" autocomplete="off" data-plugin-datepicker
							data-plugin-options='{ "todayHighlight" : true }' />
						</div>
						<span class="error"></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('date_of_submission')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<div class="input-group">
							<span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
							<input type="text" class="form-control" name="date_of_submission" value="<?=set_value('date_of_submission')?>" autocomplete="off" data-plugin-datepicker
							data-plugin-options='{ "todayHighlight" : true }' />
						</div>
						<span class="error"></span>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-offset-3 col-md-6">
						<div class="checkbox-replace">
							<label class="i-checks"><input type="checkbox" name="published_later" id="published_later"><i></i> Published later</label>
						</div>
					</div>
					<div class="col-md-12 mt-sm"></div>
					<label class="col-md-3 control-label">Schedule Date <span class="required">*</span></label>
					<div class="col-md-6">
						<div class="input-group">
							<span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
							<input type="text" class="form-control" name="schedule_date" id="schedule_date" disabled="" autocomplete="off" value="<?=set_value('schedule_date')?>" data-plugin-datepicker
							data-plugin-options='{ "todayHighlight" : true }' />
						</div>
						<span class="error"></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('homework')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<textarea name="homework" class="summernote"></textarea>
						<span class="error"></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Attachment File <span class="required">*</span></label>
					<div class="col-md-6">
						<div class="fileupload fileupload-new" data-provides="fileupload">
							<div class="input-append">
								<div class="uneditable-input">
									<i class="fas fa-file fileupload-exists"></i>
									<span class="fileupload-preview"></span>
								</div>
								<span class="btn btn-default btn-file">
									<span class="fileupload-exists">Change</span>
									<span class="fileupload-new">Select file</span>
									<input type="file" name="attachment_file" />
								</span>
								<a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload">Remove</a>
							</div>
						</div>
						<span class="error"></span>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-offset-3 col-md-6">
						<div class="checkbox-replace">
							<label class="i-checks"><input type="checkbox" name="notification_sms" checked=""><i></i> <?=translate('send_notification_sms')?></label>
						</div>
					</div>
				</div>
				
				<!-- Differentiated Learning / Targeting Section -->
				<div class="form-group">
					<label class="control-label col-md-3"><?=translate('target_students')?></label>
					<div class="col-md-9">
						<div class="panel panel-default">
							<div class="panel-heading">
								<h4 class="panel-title">
									<input type="checkbox" name="enable_targeting" id="enable_targeting" value="1">
									<?=translate('assign_to_specific_students_or_groups')?>
								</h4>
							</div>
							<div class="panel-body" id="targeting_panel" style="display: none;">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label"><?=translate('target_group')?></label>
											<select name="target_group" id="target_group" class="form-control">
												<option value="all"><?=translate('all_students')?></option>
												<option value="remedial"><?=translate('remedial')?> (Below 40%)</option>
												<option value="standard"><?=translate('standard')?> (40% - 70%)</option>
												<option value="advanced"><?=translate('advanced')?> (Above 70%)</option>
												<option value="individual"><?=translate('individual_students')?></option>
											</select>
										</div>
									</div>
								</div>
								
								<div class="row" id="student_list_container" style="display: none;">
									<div class="col-md-12">
										<div class="form-group">
											<label class="control-label"><?=translate('select_students')?></label>
											<select name="target_students[]" id="target_students" class="form-control" multiple="multiple" 
												data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="10" style="width: 100%;">
											</select>
											<small class="text-muted"><?=translate('hold_ctrl_to_select_multiple')?></small>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-offset-3 col-md-2">
						<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
							<i class="fas fa-plus-circle"></i> <?=translate('save')?>
						</button>
					</div>
				</div>
			</footer>
			<?php echo form_close(); ?>
		</section>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    // Branch change for superadmin
    $('#branch_id').on('change', function() {
        var branchID = $(this).val();
        getClassByBranch(branchID);
        $('#subject_id').html('').append('<option value=""><?=translate("select")?></option>');
        $('.sms-credit-warning, .sms-credit-success').remove();
    });

    // Section change
    $('#section_id').on('change', function() {
        var classID = $('#class_id').val();
        var sectionID = $(this).val();
        $.ajax({
            url: base_url + 'subject/getByClassSection',
            type: 'POST',
            data: { classID: classID, sectionID: sectionID },
            success: function (data) {
                $('#subject_id').html(data);
                // Trigger credit check after subject loads
                if ($('input[name="notification_sms"]').is(':checked')) {
                    setTimeout(checkHomeworkSMSCredits, 300);
                }
            }
        });
        
        if ($('#enable_targeting').is(':checked') && $('#target_group').val() == 'individual') {
            loadTargetingStudents();
        }
    });

    // Class change
    $('#class_id').on('change', function() {
        if ($('#enable_targeting').is(':checked') && $('#target_group').val() == 'individual') {
            loadTargetingStudents();
        }
    });

    // Subject change - trigger credit check
    $('#subject_id').on('change', function() {
        if ($('input[name="notification_sms"]').is(':checked')) {
            setTimeout(checkHomeworkSMSCredits, 300);
        }
    });

    // Published later
    $('#published_later').on('change', function() {
        if($(this).is(':checked') ){
            var date_of_homework = $('#date_of_homework').val();
            $('#schedule_date').val(date_of_homework);
            $('#schedule_date').prop("disabled", false);
        } else {
            $('#schedule_date').val("");
            $('#schedule_date').prop("disabled", true);
        }
    });

    // SMS notification checkbox
    $('input[name="notification_sms"]').on('change', function() {
        if ($(this).is(':checked')) {
            checkHomeworkSMSCredits();
        } else {
            $('.sms-credit-warning, .sms-credit-success').remove();
        }
    });

    // Targeting enable/disable
    $('#enable_targeting').on('change', function() {
        if ($(this).is(':checked')) {
            $('#targeting_panel').slideDown();
            if ($('#target_group').val() == 'individual') {
                loadTargetingStudents();
            }
            if ($('input[name="notification_sms"]').is(':checked')) {
                setTimeout(checkHomeworkSMSCredits, 500);
            }
        } else {
            $('#targeting_panel').slideUp();
            if ($('input[name="notification_sms"]').is(':checked')) {
                setTimeout(checkHomeworkSMSCredits, 500);
            }
        }
    });
    
    // Target group change
    $('#target_group').on('change', function() {
        if ($(this).val() == 'individual') {
            $('#student_list_container').slideDown();
            loadTargetingStudents();
        } else {
            $('#student_list_container').slideUp();
        }
        if ($('input[name="notification_sms"]').is(':checked')) {
            setTimeout(checkHomeworkSMSCredits, 500);
        }
    });
    
    // Load targeting students via AJAX
    function loadTargetingStudents() {
        var class_id = $('#class_id').val();
        var section_id = $('#section_id').val();
        var branch_id = '<?php echo $branch_id; ?>';
        
        <?php if (is_superadmin_loggedin()): ?>
        branch_id = $('#branch_id').val();
        <?php endif; ?>
        
        if (class_id && section_id && branch_id && $('#target_group').val() == 'individual') {
            var $select = $('#target_students');
            $select.html('<option value="">Loading students...</option>');
            $select.prop('disabled', true);
            
            $.ajax({
                url: base_url + 'homework/get_students_by_class_section',
                type: 'POST',
                data: { class_id: class_id, section_id: section_id, branch_id: branch_id },
                dataType: 'json',
                success: function(response) {
                    var options = '';
                    if (response.students && response.students.length > 0) {
                        $.each(response.students, function(index, student) {
                            options += '<option value="' + student.id + '">' + student.name + ' (' + student.register_no + ')</option>';
                        });
                    } else {
                        options += '<option value="" disabled>No students found in this class/section</option>';
                    }
                    $select.html(options);
                    $select.prop('disabled', false);
                    
                    if ($.fn.select2 && $select.data('plugin-selectTwo')) {
                        $select.select2('destroy');
                        $select.select2({ placeholder: 'Select students', allowClear: true, width: '100%' });
                    }
                },
                error: function() {
                    $select.html('<option value="" disabled>Error loading students. Please try again.</option>');
                    $select.prop('disabled', false);
                }
            });
        }
    }

    // SMS credit check function (respects subject enrollment and targeting)
    function checkHomeworkSMSCredits() {
        var branch_id = <?php echo is_superadmin_loggedin() ? "$('#branch_id').val()" : $branch_id; ?>;
        var class_id = $('#class_id').val();
        var section_id = $('#section_id').val();
        var subject_id = $('#subject_id').val();
        var enable_targeting = $('#enable_targeting').is(':checked') ? '1' : '0';
        var target_group = $('#target_group').val();
        var target_students = $('#target_students').val();
        
        if (!branch_id || !class_id || !section_id || !subject_id) {
            $('.sms-credit-warning, .sms-credit-success').remove();
            return;
        }
        
        var smsCheckbox = $('input[name="notification_sms"]');
        var originalLabel = smsCheckbox.next('i').next().text();
        smsCheckbox.next('i').next().text('Checking credits...');
        $('.sms-credit-warning, .sms-credit-success').remove();
        
        $.ajax({
            url: base_url + 'homework/check_homework_sms_credits',
            type: 'POST',
            data: {
                branch_id: branch_id,
                class_id: class_id,
                section_id: section_id,
                subject_id: subject_id,
                enable_targeting: enable_targeting,
                target_group: target_group,
                target_students: target_students
            },
            dataType: 'json',
            success: function(response) {
                smsCheckbox.next('i').next().text(originalLabel);
                
                if (!response.success) {
                    var warning = $('<div class="alert alert-warning alert-dismissible fade in sms-credit-warning">' +
                        '<button type="button" class="close" data-dismiss="alert">×</button>' +
                        '<i class="icon fa fa-warning"></i> <strong>Warning:</strong> ' + (response.message || 'Unable to check credits') +
                        '</div>');
                    $('input[name="notification_sms"]').closest('.form-group').after(warning);
                    toastr.warning(response.message, 'Insufficient Credits');
                } else {
                    var targeted_text = '';
                    if (response.targeting_enabled) {
                        if (response.target_group == 'individual') {
                            targeted_text = ' <strong>(Targeted: ' + response.student_count + ' selected students)</strong>';
                        } else {
                            targeted_text = ' <strong>(Targeted: ' + response.student_count + ' students)</strong>';
                        }
                    }
                    var success = $('<div class="alert alert-success alert-dismissible fade in sms-credit-success">' +
                        '<button type="button" class="close" data-dismiss="alert">×</button>' +
                        '<i class="icon fa fa-check"></i> ' +
                        'SMS Credits Available: <strong>' + (response.credits_available || 0) + '</strong>. ' +
                        'Estimated Cost: <strong>' + (response.credits_needed || 0) + '</strong>. ' +
                        'Students: ' + (response.student_count || 0) + targeted_text +
                        '</div>');
                    $('input[name="notification_sms"]').closest('.form-group').after(success);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", xhr.responseText);
                smsCheckbox.next('i').next().text(originalLabel);
                var errorAlert = $('<div class="alert alert-danger alert-dismissible fade in sms-credit-warning">' +
                    '<button type="button" class="close" data-dismiss="alert">×</button>' +
                    '<i class="icon fa fa-exclamation-circle"></i> Error checking credits. Please try again.' +
                    '</div>');
                $('input[name="notification_sms"]').closest('.form-group').after(errorAlert);
            }
        });
    }

    // Form submit handler with credit check
    $('form.frm-submit-data').on('submit', function(e) {
        var notificationCheckbox = $('input[name="notification_sms"]');
        
        if (notificationCheckbox.is(':checked')) {
            var branch_id = <?php echo is_superadmin_loggedin() ? "$('#branch_id').val()" : $branch_id; ?>;
            var class_id = $('#class_id').val();
            var section_id = $('#section_id').val();
            var subject_id = $('#subject_id').val();
            var enable_targeting = $('#enable_targeting').is(':checked') ? '1' : '0';
            var target_group = $('#target_group').val();
            var target_students = $('#target_students').val();
            
            if (!branch_id || !class_id || !section_id || !subject_id) {
                return true;
            }
            
            var submitBtn = $(this).find('button[type="submit"]');
            var originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Checking credits...');
            submitBtn.prop('disabled', true);
            
            $.ajax({
                url: base_url + 'homework/check_homework_sms_credits',
                type: 'POST',
                data: {
                    branch_id: branch_id, 
                    class_id: class_id, 
                    section_id: section_id,
                    subject_id: subject_id,
                    enable_targeting: enable_targeting, 
                    target_group: target_group,
                    target_students: target_students
                },
                dataType: 'json',
                success: function(response) {
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                    
                    if (!response.success) {
                        e.preventDefault();
                        if (confirm(response.message + '\n\nDo you want to:\n• Save WITHOUT SMS (Press OK)\n• Purchase credits (Press Cancel)')) {
                            notificationCheckbox.prop('checked', false);
                            $('form.frm-submit-data').submit();
                        } else {
                            window.location.href = base_url + 'sendsmsmail/purchase?branch=' + branch_id;
                        }
                    }
                },
                error: function() {
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                    if (confirm('Unable to check credits. Continue anyway?')) {
                        return true;
                    } else {
                        e.preventDefault();
                    }
                }
            });
            return false;
        }
    });

    // Helper functions
    if (typeof getClassByBranch !== 'function') {
        function getClassByBranch(branchID) {
            if (branchID) {
                $.ajax({
                    url: base_url + 'ajax/getClassByBranch',
                    type: 'POST',
                    data: {'branch_id': branchID},
                    success: function (data) {
                        $('#class_id').html(data);
                        getSectionByClass($('#class_id').val(), 0);
                    }
                });
            } else {
                $('#class_id').html('<option value=""><?=translate("select")?></option>');
                $('#section_id').html('<option value=""><?=translate("select")?></option>');
            }
        }
    }

    if (typeof getSectionByClass !== 'function') {
        function getSectionByClass(classID, sectionID) {
            if (classID) {
                $.ajax({
                    url: base_url + 'ajax/getSectionByClass',
                    type: 'POST',
                    data: {'class_id': classID},
                    success: function (data) {
                        $('#section_id').html(data);
                        if (sectionID != 0) $('#section_id').val(sectionID);
                        $('#section_id').trigger('change');
                    }
                });
            } else {
                $('#section_id').html('<option value=""><?=translate("select")?></option>');
            }
        }
    }

    // Datepicker and validation
    $('[data-plugin-datepicker]').datepicker({ format: 'yyyy-mm-dd', todayHighlight: true, autoclose: true, startDate: new Date() });
    $('.summernote').summernote({ height: 200 });
    
    // Initial credit check
    if ($('input[name="notification_sms"]').is(':checked')) {
        setTimeout(checkHomeworkSMSCredits, 1000);
    }
});
</script>

<style>
.sms-credit-warning, .sms-credit-success { margin-top: 10px; margin-bottom: 10px; animation: fadeIn 0.5s; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>