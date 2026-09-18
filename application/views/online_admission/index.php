<?php $widget = (is_superadmin_loggedin() ? "col-md-6" : "col-md-offset-3 col-md-6"); ?>
<div class="row">
	<div class="col-md-12">
		<section class="panel">
			<header class="panel-heading">
				<h4 class="panel-title"><?=translate('select_ground')?></h4>
			</header>
			<?php echo form_open($this->uri->uri_string(), array('class' => 'validate'));?>
			<div class="panel-body">
				<div class="row mb-sm">
				<?php if (is_superadmin_loggedin() ): ?>
					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
							<?php
								$arrayBranch = $this->app_lib->getSelectList('branch');
								echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' onchange='getClassByBranch(this.value)'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
						</div>
					</div>
				<?php endif; ?>
					<div class="<?php echo $widget; ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('class')?> <span class="required">*</span></label>
							<?php
								$arrayClass = $this->app_lib->getClass($branch_id);
								echo form_dropdown("class_id", $arrayClass, set_value('class_id'), "class='form-control' id='class_id' onchange='getSectionByClass(this.value,1)'
								required data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
						</div>
					</div>
				</div>
			</div>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-offset-10 col-md-2">
						<button type="submit" name="search" value="1" class="btn btn-default btn-block"> <i class="fas fa-filter"></i> <?=translate('filter')?></button>
					</div>
				</div>
			</footer>
			<?php echo form_close();?>
		</section>

		<?php if (isset($students)):?>
		<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations'] ?>" data-appear-animation-delay="100">
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-user-graduate"></i> <?php echo translate('online_admission') . " " . translate('list');?></h4>
			</header>
			<div class="panel-body mb-md">
				<table class="table table-bordered table-condensed table-hover table-export">
					<thead>
						<tr>
							<th width="80"><?=translate('sl')?></th>
							<th><?=translate('name')?></th>
							<th><?=translate('gender')?></th>
							<th><?=translate('class')?></th>
							<th><?=translate('mobile_no')?></th>
						<?php
						$show_custom_fields = custom_form_table('student', $branch_id);
						if (count($show_custom_fields)) {
							foreach ($show_custom_fields as $fields) {
						?>
							<th><?=$fields['field_label']?></th>
						<?php } } ?>
							<th><?=translate('status')?></th>
							<th><?=translate('payment_status')?></th>
							<th><?=translate('apply_date')?></th>
							<th><?=translate('action')?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$count = 1;
						foreach($students as $row): 
							?>
						<tr>
							<td><?php echo $count++;  ?></td>
							<td><?php echo $row['first_name'] . " " . $row['last_name'];?></td>
							<td><?php echo ucfirst($row['gender']);?></td>
							<td><?php echo $row['class_name'];?></td>
							<td><?php echo $row['mobile_no'];?></td>
						<?php
						if (count($show_custom_fields)) {
							foreach ($show_custom_fields as $fields) {
						?>
							<td><?php echo get_table_custom_field_value($fields['id'], $row['id']);?></td>
						<?php } } ?>
							<td>
								<?php
								if ($row['status'] == 1)
									$status = '<span class="label label-warning-custom text-xs">' . translate('apply') . '</span>';
								else if ($row['status']  == 2)
									$status = '<span class="label label-success-custom text-xs">' . translate('approved') . '</span>';
								else if ($row['status']  == 3)
									$status = '<span class="label label-danger-custom text-xs">' . translate('declined') . '</span>';
								echo ($status);
								?>
							</td>
							<td>
								<?php
								$paymentStatus = "";
								if ($row['payment_status'] == 0){
									$paymentStatus = '<span class="label label-danger-custom text-xs">' . translate('unpaid') . '</span>';
								} else if ($row['status']  == 1) {
									$paymentStatus = '<span class="label label-success-custom text-xs">' . translate('paid') . '</span>';
								}
								echo ($paymentStatus);
								?>
							</td>
							<td><?php echo _d($row['apply_date']) . " <br> " . date("h:m A", strtotime($row['apply_date']));?></td>
							
							<td class="action" style="min-width: 180px;">
								<?php if ($row['status'] == 1): ?>
								<a href="<?=base_url('online_admission/approved/'.$row['id'])?>" class="btn btn-sm btn-primary">Review</a>
								<?php elseif ($row['status'] == 4): ?>
								<a href="<?=base_url('online_admission/interview_view/'.$row['id'])?>" class="btn btn-sm btn-info">View Interview</a>
								<?php elseif ($row['status'] == 5): ?>
								<a href="<?=base_url('online_admission/approved/'.$row['id'])?>" class="btn btn-sm btn-warning">Review Result</a>
								<?php else: ?>
								<a href="<?=base_url('online_admission/approved/'.$row['id'])?>" class="btn btn-sm btn-secondary">View</a>
								<?php endif; ?>

								<!-- ========== NEW DOWNLOAD BUTTON ========== -->
								<?php if (!empty($row['doc'])): ?>
								<a href="<?=base_url('online_admission/download_doc/'.$row['id'])?>" 
								class="btn btn-sm btn-success" 
								target="_blank"
								title="Download Document">
									<i class="fas fa-download"></i> Doc
								</a>
								<?php else: ?>
								<button class="btn btn-sm btn-default" disabled title="No document uploaded">
									<i class="fas fa-ban"></i> No Doc
								</button>
								<?php endif; ?>

								<?php if (get_permission('online_admission', 'is_delete')): ?>
								<a href="<?=base_url('online_admission/delete/'.$row['id'])?>" 
								class="btn btn-sm btn-danger" 
								onclick="return confirm('Delete this record?')">
									<i class="fas fa-trash"></i>
								</a>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach;?>
					</tbody>
				</table>
			</div>
		</section>
		<?php endif;?>
	</div>
</div>

<div class="zoom-anim-dialog modal-block modal-block-primary mfp-hide" id="quickView">
	<section class="panel">
		<header class="panel-heading">
			<h4 class="panel-title">
				<i class="far fa-user-circle"></i> <?=translate('quick_view')?>
			</h4>
		</header>
		<div class="panel-body">
			<div class="quick_image">
				<img alt="" class="user-img-circle" id="quick_image" src="<?=base_url('uploads/app_image/defualt.png')?>" width="120" height="120">
			</div>
			<div class="text-center">
				<h4 class="text-weight-semibold mb-xs" id="quick_full_name"></h4>
				<p><?=translate('student')?> / <span id="quick_category"></p>
			</div>
			<div class="table-responsive mt-md mb-md">
				<table class="table table-striped table-bordered table-condensed mb-none">
					<tbody>
						<tr>
							<th><?=translate('register_no')?></th>
							<td><span id="quick_register_no"></span></td>
							<th><?=translate('roll')?></th>
							<td><span id="quick_roll"></span></td>
						</tr>
						<tr>
							<th><?=translate('admission_date')?></th>
							<td><span id="quick_admission_date"></span></td>
							<th><?=translate('date_of_birth')?></th>
							<td><span id="quick_date_of_birth"></span></td>
						</tr>
						<tr>
							<th><?=translate('blood_group')?></th>
							<td><span id="quick_blood_group"></span></td>
							<th><?=translate('religion')?></th>
							<td><span id="quick_religion"></span></td>
						</tr>
						<tr>
							<th><?=translate('email')?></th>
							<td colspan="3"><span id="quick_email"></span></td>
						</tr>
						<tr>
							<th><?=translate('mobile_no')?></th>
							<td><span id="quick_mobile_no"></span></td>
							<th><?=translate('state')?></th>
							<td><span id="quick_state"></span></td>
						</tr>
						<tr class="quick-address">
							<th><?=translate('address')?></th>
							<td colspan="3" height="80px;"><span id="quick_address"></span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<footer class="panel-footer">
			<div class="row">
				<div class="col-md-12 text-right">
					<button class="btn btn-default modal-dismiss"><?=translate('close')?></button>
				</div>
			</div>
		</footer>
	</section>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // ========== 1. INITIALIZE DATE/TIME PICKERS ==========
    function initializePickers(modalElement) {
        $(modalElement).find('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            startDate: '0d'
        });
        
        if (typeof $.fn.timepicker !== 'undefined') {
            $(modalElement).find('.timepicker').timepicker({
                showMeridian: false,
                minuteStep: 15
            });
        }
    }
    
    // ========== 2. FIX SCHEDULE INTERVIEW BUTTON CLICK ==========
    // Remove ALL existing handlers and use this single handler
    $(document).off('click', '[data-target^="#scheduleModal"]').on('click', '[data-target^="#scheduleModal"]', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation(); // CRITICAL: Stop all other handlers
        
        var modalId = $(this).data('target');
        var $modal = $(modalId);
        
        // Initialize date/time pickers before showing
        initializePickers($modal);
        
        // Show modal with proper settings
        $modal.modal({
            backdrop: 'static', // Don't close on backdrop click
            keyboard: false     // Don't close with ESC key
        });
        
        return false;
    });
    
    // ========== 3. FIX MODAL SHOWN EVENT ==========
    $(document).on('shown.bs.modal', '.modal', function() {
        // Re-initialize pickers when modal is shown
        initializePickers(this);
    });
    
    // ========== 4. SCHEDULE INTERVIEW FORM SUBMISSION ==========
    $(document).off('submit', '.schedule-interview-form').on('submit', '.schedule-interview-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var modal = form.closest('.modal');
        
        Swal.fire({
            title: 'Schedule Interview',
            text: 'Are you sure you want to schedule this interview? SMS credits will be used.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, schedule',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve, reject) {
                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        dataType: 'json',
                        success: function(response) {
                            resolve(response);
                        },
                        error: function(xhr) {
                            reject('Server error: ' + xhr.status);
                        }
                    });
                });
            }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                if (result.value.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        html: result.value.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        modal.modal('hide');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    });
                } else if (result.value.status === 'warning') {
                    Swal.fire({
                        title: 'Partial Success',
                        html: result.value.message,
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        if (result.value.redirect) {
                            window.location.href = result.value.redirect;
                        } else {
                            modal.modal('hide');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        }
                    });
                } else {
                    Swal.fire('Error!', result.value.message, 'error');
                }
            }
        });
    });
    
    // ========== 5. DECLINE FUNCTION ==========
    window.confirm_decline = function(delete_url) {
        Swal.fire({
            title: "<?php echo translate('are_you_sure');?>",
            text: "Do You Want To Decline This Applicant?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn btn-default swal2-btn-default",
            cancelButtonClass: "btn btn-default swal2-btn-default",
            confirmButtonText: "<?php echo translate('yes_continue');?>",
            cancelButtonText: "<?php echo translate('cancel');?>",
            buttonsStyling: false,
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: delete_url,
                    type: "POST",
                    data: {
                        csrf_test_name: $('input[name="csrf_test_name"]').val()
                    },
                    success: function(data) {
                        Swal.fire({
                            title: "<?php echo translate('successfully');?>",
                            text: "Applicant Declined.",
                            buttonsStyling: false,
                            showCloseButton: true,
                            focusConfirm: false,
                            confirmButtonClass: "btn btn-default swal2-btn-default",
                            icon: "success"
                        }).then((result) => {
                            if (result.value) {
                                location.reload();
                            }
                        });
                    },
                    error: function() {
                        Swal.fire({
                            title: "Error!",
                            text: "Failed to decline applicant.",
                            icon: "error"
                        });
                    }
                });
            }
        });
    };
});
</script>
<style>
.action .btn {
    margin: 2px;
    min-width: 60px;
}
.action .btn-sm {
    padding: 3px 8px;
    font-size: 12px;
}
.action .btn-success {
    background-color: #28a745;
    border-color: #28a745;
}
.action .btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}
.action .btn-default[disabled] {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>