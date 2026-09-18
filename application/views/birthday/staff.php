<?php $widget = (is_superadmin_loggedin() ? '' : 'col-md-offset-3 '); ?>
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
								echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' data-plugin-selectTwo required
								data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
						</div>
					</div>
				<?php endif; ?>
					<div class="<?php echo $widget ?>col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?php echo translate('date'); ?> <span class="required">*</span></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fas fa-calendar-check"></i></span>
								<input type="text" class="form-control daterange" name="daterange" value="<?php echo set_value('daterange', date("Y/m/d") . ' - ' . date("Y/m/d")); ?>" required />
							</div>
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
				<div class="panel-btn">
					<button class="btn btn-default btn-circle" id="sendWishes" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
						<i class="fas fa-comment-dots"></i> <?=translate('send_wishes')?>
					</button>
					<small class="text-muted ml-sm" id="creditInfo">
						<!-- Credit info will appear here -->
					</small>
				</div>
				<h4 class="panel-title"><i class="fas fa-user-graduate"></i> <?php echo translate('staff') . " " . translate('list');?></h4>
			</header>
			<div class="panel-body mb-md">
				<input type="hidden" name="branch_id" id="branchID" value="<?php echo $branch_id ?>">
				<table class="table table-bordered table-condensed table-hover table-export">
					<thead>
						<tr>
							<th width="10" class="no-sort">
								<div class="checkbox-replace">
									<label class="i-checks"><input type="checkbox" id="selectAllchkbox"><i></i></label>
								</div>
							</th>
							<th class="no-sort"><?=translate('photo')?></th>
							<th><?=translate('name')?></th>
							<th><?=translate('birthday')?></th>
							<th><?=translate('age')?></th>
							<th><?=translate('designation')?></th>
							<th><?=translate('role')?></th>
							<th><?=translate('mobile_no')?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($students as $row): ?>
						<tr>
							<td class="checked-area">
								<div class="checkbox-replace">
									<label class="i-checks">
										<input type="checkbox" class="cb_bulk_sms" id="<?=$row['id']?>"><i></i>
									</label>
								</div>
							</td>
							<td class="center"><img src="<?php echo get_image_url('student', $row['photo']); ?>" height="50"></td>
							<td><?php echo $row['name'];?></td>
							<td><?php echo _d($row['birthday']);?></td>
							<td>
							<?php
								if(!empty($row['birthday'])){
									$birthday = new DateTime($row['birthday']);
									$today = new DateTime('today');
									$age = $birthday->diff($today)->y;
									echo html_escape($age);
								} else {
									echo "N/A";
								}
							?>
							</td>
							<td><?php echo $row['designation_name'];?></td>
							<td><?php echo $row['role_name'];?></td>
							<td><?php echo $row['mobileno'];?></td>
						</tr>
						<?php endforeach;?>
					</tbody>
				</table>
			</div>
		</section>
		<?php endif;?>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var currentBranchID = $("#branchID").val();
    
    // Load credit balance on page load
    function loadCreditBalance(branchID) {
        if (!branchID) return;
        
        currentBranchID = branchID;
        $('#creditInfo').html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        
        $.ajax({
            url: base_url + "sendsmsmail/get_sms_balance",
            type: "POST",
            dataType: "JSON",
            data: { branch_id: branchID },
            success: function(response) {
                if (response.success) {
                    var balanceText = 'Available SMS Credits: <strong>' + response.balance + '</strong>';
                    if (response.balance < 10) {
                        balanceText += ' <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Low Balance</span>';
                    }
                    $('#creditInfo').html(balanceText);
                    
                    // Update branch hidden field
                    $('#branchID').val(response.branch_id);
                }
            },
            error: function() {
                $('#creditInfo').html('<span class="text-danger">Failed to load credits</span>');
            }
        });
    }
    
    // Initial load
    loadCreditBalance(currentBranchID);
    
    // Update when branch changes (for superadmin)
    $('select[name="branch_id"]').on('change', function() {
        loadCreditBalance($(this).val());
    });
    
    // Select/Deselect all functionality
    $('#selectAllchkbox').on('change', function() {
        $('.cb_bulk_sms').prop('checked', this.checked);
        updateSelectedCount();
    });
    
    // Update count when checkboxes change
    $('.cb_bulk_sms').on('change', function() {
        updateSelectedCount();
        
        // Uncheck select all if any checkbox is unchecked
        if (!this.checked) {
            $('#selectAllchkbox').prop('checked', false);
        }
    });
    
    // Update selected count display
    function updateSelectedCount() {
        var count = $('.cb_bulk_sms:checked').length;
        if (count > 0) {
            $('#sendWishes').prop('disabled', false);
            $('#selectedCount').remove();
            $('#sendWishes').after('<span id="selectedCount" class="badge badge-success ml-sm">' + count + ' selected</span>');
        } else {
            $('#sendWishes').prop('disabled', false);
            $('#selectedCount').remove();
        }
    }
    
    // Send Wishes button click
    $('#sendWishes').on('click', function() {
        var btn = $(this);
        var branchID = $("#branchID").val();
        var arrayID = [];
        var count = 0;
        
        $("input[type='checkbox'].cb_bulk_sms").each(function (index) {
            if(this.checked) {
                arrayID.push($(this).attr('id'));
                count++;
            }
        });
        
        if (count == 0) {
            swal({
                title: "<?php echo translate('error')?>",
                text: "Please select at least one staff member.",
                type: "error",
                buttonsStyling: false,
                confirmButtonClass: "btn btn-default swal2-btn-default"
            });
            return;
        }
        
        // First, check credits and get template
        checkCreditsAndSend('staff', arrayID, branchID, count, btn);
    });
    
    // Function to check credits and send (identical to student version)
    function checkCreditsAndSend(type, arrayID, branchID, count, btn) {
        btn.button('loading');
        
        $.ajax({
            url: base_url + "birthday/check_credits",
            type: "POST",
            dataType: "JSON",
            data: { 
                type: type,
                branch_id: branchID,
                recipient_count: count,
                recipient_ids: arrayID
            },
            success: function(response) {
                btn.button('reset');
                
                if (!response.success) {
                    if (response.insufficient_credits) {
                        showCreditError(response);
                    } else {
                        swal({
                            title: "<?php echo translate('error')?>",
                            text: response.message,
                            type: "error",
                            buttonsStyling: false,
                            confirmButtonClass: "btn btn-default swal2-btn-default"
                        });
                    }
                    return;
                }
                
                // Show confirmation with credit info
                var confirmText = "<div class='text-left'>";
                confirmText += "<p><strong>Send birthday wishes to " + count + " staff member(s)?</strong></p>";
                confirmText += "<div class='credit-info p-sm bg-light rounded'>";
                confirmText += "<p><i class='fas fa-sms text-primary'></i> Estimated Cost: <strong>" + response.estimated_cost + " SMS credits</strong></p>";
                confirmText += "<p><i class='fas fa-wallet text-success'></i> Current Balance: <strong>" + response.current_balance + " credits</strong></p>";
                confirmText += "<p><i class='fas fa-coins text-warning'></i> Remaining: <strong class='" + (response.remaining >= 0 ? 'text-success' : 'text-danger') + "'>" + response.remaining + " credits</strong></p>";
                
                if (response.warning_level === 'low') {
                    confirmText += "<p class='text-warning'><i class='fas fa-exclamation-triangle'></i> Low credit warning</p>";
                } else if (response.warning_level === 'critical') {
                    confirmText += "<p class='text-danger'><i class='fas fa-exclamation-circle'></i> Critical credit level</p>";
                }
                confirmText += "</div>";
                
                if (response.remaining < 0) {
                    confirmText += "<div class='alert alert-danger mt-sm'><i class='fas fa-ban'></i> <strong>Insufficient credits!</strong> Please purchase more SMS credits.</div>";
                }
                confirmText += "</div>";
                
                swal({
                    title: "<?php echo translate('confirmation')?>",
                    html: confirmText,
                    type: response.remaining < 0 ? "error" : "warning",
                    showCancelButton: response.remaining >= 0,
                    confirmButtonClass: "btn btn-default swal2-btn-default",
                    cancelButtonClass: "btn btn-default swal2-btn-default",
                    confirmButtonText: response.remaining < 0 ? "<?php echo translate('purchase_credits')?>" : "<?php echo translate('send_now')?>",
                    cancelButtonText: "<?php echo translate('cancel')?>",
                    buttonsStyling: false,
                }).then((result) => {
                    if (result.value) {
                        if (response.remaining < 0) {
                            // Redirect to purchase page
                            window.location.href = base_url + "sendsmsmail/purchase?branch=" + branchID;
                        } else {
                            // Send wishes
                            sendBirthdayWishes(type, arrayID, branchID, btn);
                        }
                    }
                });
            },
            error: function() {
                btn.button('reset');
                swal({
                    title: "<?php echo translate('error')?>",
                    text: "Failed to check SMS credits. Please try again.",
                    type: "error",
                    buttonsStyling: false,
                    confirmButtonClass: "btn btn-default swal2-btn-default"
                });
            }
        });
    }
    
    // Show credit error with purchase option
    function showCreditError(response) {
        swal({
            title: "<?php echo translate('insufficient_credits')?>",
            html: "<div class='text-left'>" +
                  "<p><strong>You don't have enough SMS credits.</strong></p>" +
                  "<div class='alert alert-danger'>" +
                  "<p><i class='fas fa-ban'></i> Needed: <strong>" + response.estimated_cost + " credits</strong></p>" +
                  "<p><i class='fas fa-wallet'></i> Available: <strong>" + response.current_balance + " credits</strong></p>" +
                  "<p><i class='fas fa-minus-circle'></i> Shortfall: <strong>" + response.shortfall + " credits</strong></p>" +
                  "</div>" +
                  "<p>Would you like to purchase more SMS credits?</p>" +
                  "</div>",
            type: "error",
            showCancelButton: true,
            confirmButtonClass: "btn btn-success swal2-btn-default",
            cancelButtonClass: "btn btn-default swal2-btn-default",
            confirmButtonText: "<?php echo translate('purchase_now')?>",
            cancelButtonText: "<?php echo translate('cancel')?>",
            buttonsStyling: false,
        }).then((result) => {
            if (result.value) {
                window.location.href = base_url + "sendsmsmail/purchase?branch=" + response.branch_id;
            }
        });
    }
    
    // Send birthday wishes
    function sendBirthdayWishes(type, arrayID, branchID, btn) {
        var endpoint = type === 'student' ? 'studentWishes' : 'staffWishes';
        
        swal({
            title: "<?php echo translate('sending')?>",
            text: "Please wait while we send birthday wishes...",
            type: "info",
            showConfirmButton: false,
            allowOutsideClick: false,
            onOpen: () => {
                swal.showLoading();
                
                $.ajax({
                    url: base_url + "birthday/" + endpoint,
                    type: "POST",
                    dataType: "JSON",
                    data: { 
                        array_id: arrayID,
                        branch_id: branchID
                    },
                    success: function(data) {
                        swal.close();
                        
                        var icon = data.status === 'success' ? 'success' : 'error';
                        var title = data.status === 'success' ? "<?php echo translate('successfully')?>" : "<?php echo translate('error')?>";
                        
                        swal({
                            title: title,
                            text: data.message,
                            type: icon,
                            buttonsStyling: false,
                            showCloseButton: true,
                            focusConfirm: false,
                            confirmButtonClass: "btn btn-default swal2-btn-default",
                            showCancelButton: data.status === 'success',
                            cancelButtonText: "<?php echo translate('view_reports')?>",
                            cancelButtonClass: "btn btn-info swal2-btn-default",
                        }).then((result) => {
                            if (result.dismiss === swal.DismissReason.cancel) {
                                // View sent messages
                                window.location.href = base_url + "sendsmsmail/sent_messages?branch=" + branchID;
                            } else if (result.value && data.status === 'success') {
                                // Reload page
                                location.reload();
                            }
                        });
                        
                        // Update credit balance after sending
                        if (data.status === 'success') {
                            setTimeout(function() {
                                loadCreditBalance(branchID);
                            }, 1000);
                        }
                    },
                    error: function() {
                        swal.close();
                        swal({
                            title: "<?php echo translate('error')?>",
                            text: "Failed to send birthday wishes. Please try again.",
                            type: "error",
                            buttonsStyling: false,
                            confirmButtonClass: "btn btn-default swal2-btn-default"
                        });
                    }
                });
            }
        });
    }
    
    // Initialize selected count
    updateSelectedCount();
});
</script>
