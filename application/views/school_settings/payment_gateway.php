<div class="row">
	<div class="col-md-3">
        <?php include 'sidebar.php'; ?>
    </div>
	<div class="col-md-9">
		<section class="panel">
			<div class="tabs-custom">
				<ul class="nav nav-tabs">
					<li class="active">
						<a href="#mpesa_stk" data-toggle="tab"><i class="fas fa-mobile-alt"></i> M-Pesa STK Push</a>
					</li>
					<li>
						<a href="#mpesa_paybill" data-toggle="tab"><i class="fas fa-building"></i> M-Pesa Paybill (C2B)</a>
					</li>
					<li>
						<a href="#pesapal" data-toggle="tab"><i class="fas fa-credit-card"></i> Pesapal</a>
					</li>
				</ul>
				
				<div class="tab-content">
					
					<!-- M-Pesa STK Push Tab -->
					<div class="tab-pane box active" id="mpesa_stk">
						<?php echo form_open('school_settings/mpesa_stk_save', array('class' => 'form-horizontal frm-submit-msg', 'id' => 'mpesaStkForm'));?>
						<input type="hidden" name="branch_id" value="<?=$branch_id?>">
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Consumer Key <span class="required">*</span></label>
							<div class="col-md-6">
								<input type="text" class="form-control" name="consumer_key" value="<?=$config['consumer_key'] ?? ''?>" required>
								<span class="help-block">From Safaricom Daraja API</span>
								<span class="error"></span>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Consumer Secret <span class="required">*</span></label>
							<div class="col-md-6">
								<input type="text" class="form-control" name="consumer_secret" value="<?=$config['consumer_secret'] ?? ''?>" required>
								<span class="help-block">From Safaricom Daraja API</span>
								<span class="error"></span>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Shortcode/Paybill <span class="required">*</span></label>
							<div class="col-md-6">
								<input type="text" class="form-control" name="shortcode" value="<?=$config['shortcode'] ?? '174379'?>" required>
								<span class="help-block">M-Pesa Paybill Number (e.g., 174379)</span>
								<span class="error"></span>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Passkey <span class="required">*</span></label>
							<div class="col-md-6">
								<input type="text" class="form-control" name="passkey" value="<?=$config['passkey'] ?? ''?>" required>
								<span class="help-block">From Safaricom Daraja API</span>
								<span class="error"></span>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Callback URL</label>
							<div class="col-md-6">
								<input type="text" class="form-control" value="<?=base_url('payment_callback')?>" readonly>
								<span class="help-block">Register this URL with Safaricom</span>
							</div>
						</div>
						
						<div class="form-group">
							<div class="col-md-offset-3 col-md-6 mb-md">
								<div class="checkbox-replace">
									<label class="i-checks">
										<input type="checkbox" name="environment" value="sandbox" <?=(isset($config['environment']) && $config['environment'] == 'sandbox') ? 'checked' : ''?>>
										<i></i> Sandbox Mode (Test Environment)
									</label>
								</div>
							</div>
						</div>
						
						<div class="form-group">
							<div class="col-md-offset-3 col-md-6 mb-md">
								<div class="checkbox-replace">
									<label class="i-checks">
										<input type="checkbox" name="mpesa_stk_status" <?=(isset($config['mpesa_stk_status']) && $config['mpesa_stk_status'] == 1 ? 'checked' : '')?>>
										<i></i> Enable M-Pesa STK Push
									</label>
								</div>
							</div>
						</div>
						
						<footer class="panel-footer">
							<div class="row">
								<div class="col-md-3 col-sm-offset-3">
									<button type="submit" class="btn btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
										<i class="fas fa-save"></i> <?=translate('save');?>
									</button>
								</div>
							</div>
						</footer>
						<?php echo form_close();?>
					</div>
					
					<!-- M-Pesa Paybill Tab -->
					<div class="tab-pane box" id="mpesa_paybill">
						<?php echo form_open('school_settings/mpesa_paybill_save', array('class' => 'form-horizontal frm-submit-msg', 'id' => 'mpesaPaybillForm'));?>
						<input type="hidden" name="branch_id" value="<?=$branch_id?>">
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Paybill Number <span class="required">*</span></label>
							<div class="col-md-6">
								<input type="text" class="form-control" name="paybill_number" value="<?=$config['paybill_number'] ?? '174379'?>" required>
								<span class="help-block">M-Pesa Paybill Number</span>
								<span class="error"></span>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Validation URL</label>
							<div class="col-md-6">
								<input type="text" class="form-control" value="<?=base_url('mpesa/validation')?>" readonly>
								<span class="help-block">Register this URL with Safaricom</span>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Confirmation URL</label>
							<div class="col-md-6">
								<input type="text" class="form-control" value="<?=base_url('mpesa/confirmation')?>" readonly>
								<span class="help-block">Register this URL with Safaricom</span>
							</div>
						</div>
						
						<div class="form-group">
							<div class="col-md-offset-3 col-md-6 mb-md">
								<div class="checkbox-replace">
									<label class="i-checks">
										<input type="checkbox" name="mpesa_paybill_status" <?=(isset($config['mpesa_paybill_status']) && $config['mpesa_paybill_status'] == 1 ? 'checked' : '')?>>
										<i></i> Enable M-Pesa Paybill (C2B)
									</label>
								</div>
							</div>
						</div>
						
						<footer class="panel-footer">
							<div class="row">
								<div class="col-md-3 col-sm-offset-3">
									<button type="submit" class="btn btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
										<i class="fas fa-save"></i> <?=translate('save');?>
									</button>
								</div>
							</div>
						</footer>
						<?php echo form_close();?>
					</div>
					
					<!-- Pesapal Tab -->
					<div class="tab-pane box" id="pesapal">
						<?php echo form_open('school_settings/pesapal_save', array('class' => 'form-horizontal frm-submit-msg', 'id' => 'pesapalForm'));?>
						<input type="hidden" name="branch_id" value="<?=$branch_id?>">
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Consumer Key <span class="required">*</span></label>
							<div class="col-md-6">
								<input type="text" class="form-control" name="pesapal_consumer_key" value="<?=$config['pesapal_consumer_key'] ?? ''?>" required>
								<span class="help-block">From Pesapal Merchant Portal</span>
								<span class="error"></span>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Consumer Secret <span class="required">*</span></label>
							<div class="col-md-6">
								<input type="text" class="form-control" name="pesapal_consumer_secret" value="<?=$config['pesapal_consumer_secret'] ?? ''?>" required>
								<span class="help-block">From Pesapal Merchant Portal</span>
								<span class="error"></span>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-3 control-label">IPN URL</label>
							<div class="col-md-6">
								<input type="text" class="form-control" value="<?=base_url('pesapal/ipn')?>" readonly>
								<span class="help-block">Register this URL with Pesapal</span>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-3 control-label">Return URL</label>
							<div class="col-md-6">
								<input type="text" class="form-control" value="<?=base_url('pesapal/return_url')?>" readonly>
								<span class="help-block">Customer redirect after payment</span>
							</div>
						</div>
						
						<div class="form-group">
							<div class="col-md-offset-3 col-md-6 mb-md">
								<div class="checkbox-replace">
									<label class="i-checks">
										<input type="checkbox" name="pesapal_sandbox" <?=(isset($config['pesapal_sandbox']) && $config['pesapal_sandbox'] == 1 ? 'checked' : '')?>>
										<i></i> Sandbox Mode (Test Environment)
									</label>
								</div>
							</div>
						</div>
						
						<div class="form-group">
							<div class="col-md-offset-3 col-md-6 mb-md">
								<div class="checkbox-replace">
									<label class="i-checks">
										<input type="checkbox" name="pesapal_status" <?=(isset($config['pesapal_status']) && $config['pesapal_status'] == 1 ? 'checked' : '')?>>
										<i></i> Enable Pesapal
									</label>
								</div>
							</div>
						</div>
						
						<footer class="panel-footer">
							<div class="row">
								<div class="col-md-3 col-sm-offset-3">
									<button type="submit" class="btn btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
										<i class="fas fa-save"></i> <?=translate('save');?>
									</button>
								</div>
							</div>
						</footer>
						<?php echo form_close();?>
					</div>
				</div>
			</div>
		</section>
	</div>
	<script type="text/javascript">
$(document).ready(function() {
    // M-Pesa STK Form Submission
    $('#mpesaStkForm').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var formData = $form.serialize();
        
        $btn.button('loading');
        
        $.ajax({
            url: '<?=base_url("school_settings/mpesa_stk_save")?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    swal({
                        title: "Success!",
                        text: response.message,
                        type: "success",
                        confirmButtonClass: "btn btn-default",
                        buttonsStyling: false,
                        timer: 3000
                    });
                } else {
                    var errorMsg = '';
                    if (response.error) {
                        $.each(response.error, function(key, value) {
                            errorMsg += value + '\n';
                        });
                    } else {
                        errorMsg = response.message || 'An error occurred';
                    }
                    swal({
                        title: "Error!",
                        text: errorMsg,
                        type: "error",
                        confirmButtonClass: "btn btn-default",
                        buttonsStyling: false
                    });
                }
                $btn.button('reset');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                swal({
                    title: "Error!",
                    text: "Network error. Please try again.",
                    type: "error",
                    confirmButtonClass: "btn btn-default",
                    buttonsStyling: false
                });
                $btn.button('reset');
            }
        });
    });
    
    // M-Pesa Paybill Form Submission
    $('#mpesaPaybillForm').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var formData = $form.serialize();
        
        $btn.button('loading');
        
        $.ajax({
            url: '<?=base_url("school_settings/mpesa_paybill_save")?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    swal({
                        title: "Success!",
                        text: response.message,
                        type: "success",
                        confirmButtonClass: "btn btn-default",
                        buttonsStyling: false,
                        timer: 3000
                    });
                } else {
                    var errorMsg = '';
                    if (response.error) {
                        $.each(response.error, function(key, value) {
                            errorMsg += value + '\n';
                        });
                    } else {
                        errorMsg = response.message || 'An error occurred';
                    }
                    swal({
                        title: "Error!",
                        text: errorMsg,
                        type: "error",
                        confirmButtonClass: "btn btn-default",
                        buttonsStyling: false
                    });
                }
                $btn.button('reset');
            },
            error: function() {
                swal({
                    title: "Error!",
                    text: "Network error. Please try again.",
                    type: "error",
                    confirmButtonClass: "btn btn-default",
                    buttonsStyling: false
                });
                $btn.button('reset');
            }
        });
    });
    
    // Pesapal Form Submission
    $('#pesapalForm').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var formData = $form.serialize();
        
        $btn.button('loading');
        
        $.ajax({
            url: '<?=base_url("school_settings/pesapal_save")?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    swal({
                        title: "Success!",
                        text: response.message,
                        type: "success",
                        confirmButtonClass: "btn btn-default",
                        buttonsStyling: false,
                        timer: 3000
                    });
                } else {
                    var errorMsg = '';
                    if (response.error) {
                        $.each(response.error, function(key, value) {
                            errorMsg += value + '\n';
                        });
                    } else {
                        errorMsg = response.message || 'An error occurred';
                    }
                    swal({
                        title: "Error!",
                        text: errorMsg,
                        type: "error",
                        confirmButtonClass: "btn btn-default",
                        buttonsStyling: false
                    });
                }
                $btn.button('reset');
            },
            error: function() {
                swal({
                    title: "Error!",
                    text: "Network error. Please try again.",
                    type: "error",
                    confirmButtonClass: "btn btn-default",
                    buttonsStyling: false
                });
                $btn.button('reset');
            }
        });
    });
});
</script>
</div>

<style>
.help-block {
    font-size: 12px;
    color: #777;
    margin-top: 5px;
}
.required {
    color: #e74c3c;
}
</style>