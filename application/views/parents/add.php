<div class="row">
	<div class="col-md-12">
		<section class="panel">
			<?php echo form_open_multipart($this->uri->uri_string()); ?>
			<header class="panel-heading">
				<div class="panel-btn">
					<a href="javascript:void(0);" onclick="openImportModal()" class="btn btn-circle btn-default mb-sm">
						<i class="fas fa-plus-circle"></i> <?=translate('multiple_import')?>
					</a>
				</div>
				<h4 class="panel-title">
					<i class="far fa-user-circle"></i> <?=translate('add_parent')?>
				</h4>
			</header>
			<div class="panel-body">
<?php if (is_superadmin_loggedin()) { ?>
				<!-- academic details-->
				<div class="headers-line mt-md">
					<i class="fas fa-school"></i> <?=translate('academic_details')?>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
							<?php
								$arrayBranch = $this->app_lib->getSelectList('branch');
								echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' id='branch_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"><?php echo form_error('branch_id'); ?></span>
						</div>
					</div>
				</div>
<?php } ?>
				<!-- parents details -->
				<div class="headers-line mt-md">
					<i class="fas fa-user-check"></i> <?=translate('parents_details')?>
				</div>
				<div class="row">
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('name')?> <span class="required">*</span></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-user"></i></span>
								<input class="form-control" name="name" type="text" value="<?=set_value('name')?>" autocomplete="off" />
							</div>
							<span class="error"><?php echo form_error('name'); ?></span>
						</div>
					</div>
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('relation')?> <span class="required">*</span></label>
							<input type="text" class="form-control" name="relation" value="<?=set_value('relation')?>" autocomplete="off" />
						</div>
						<span class="error"><?php echo form_error('relation'); ?></span>
					</div>
				</div>

				<div class="row">
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('father_name')?></label>
							<input class="form-control" name="father_name" type="text" value="<?=set_value('father_name')?>" autocomplete="off" />
						</div>
					</div>
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('mother_name')?></label>
							<input type="text" class="form-control" name="mother_name" value="<?=set_value('mother_name')?>" autocomplete="off" />
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('occupation')?> <span class="required">*</span></label>
							<input type="text" class="form-control" name="occupation" value="<?=set_value('occupation')?>" autocomplete="off" />
							<span class="error"><?php echo form_error('occupation'); ?></span>
						</div>
					</div>
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('income')?></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fas fa-calculator"></i></span>
								<input type="text" class="form-control" name="income" value="<?=set_value('income')?>" autocomplete="off" />
							</div>
							<span class="error"><?php echo form_error('income'); ?></span>
						</div>
					</div>
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('education')?></label>
							<input type="text" class="form-control" name="education" value="<?=set_value('education')?>" autocomplete="off" />
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('city')?></label>
							<input type="text" class="form-control" name="city" value="<?=set_value('city')?>" autocomplete="off" />
						</div>
					</div>
					<div class="col-md-3 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('state')?></label>
							<input type="text" class="form-control" name="state" value="<?=set_value('state')?>" autocomplete="off" />
						</div>
					</div>
					<div class="col-md-3 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('mobile_no')?> <span class="required">*</span></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fas fa-phone-volume"></i></span>
								<input type="text" class="form-control" name="mobileno" value="<?=set_value('mobileno')?>" autocomplete="off" />
							</div>
							<span class="error"><?php echo form_error('mobileno'); ?></span>
						</div>
					</div>
					<div class="col-md-3 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('email')?></label>
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-envelope-open"></i></span>
								<input type="text" class="form-control" name="email" id="email" value="<?=set_value('email')?>" autocomplete="off" />
							</div>
							<span class="error"><?php echo form_error('email'); ?></span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('address')?></label>
							<textarea name="address" rows="2" class="form-control" aria-required="true"><?=set_value('address')?></textarea>
						</div>
					</div>
				</div>

				<!--custom fields details-->
				<div class="row" id="customFields">
					<?php echo render_custom_Fields('parents'); ?>
				</div>

				<div class="row">
					<div class="col-md-12 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('profile_picture')?></label>
							<input type="file" name="user_photo" class="dropify" />
							<span class="error"><?php echo form_error('user_photo'); ?></span>
						</div>
					</div>
				</div>
				<div class="<?=$getBranch['grd_generate'] == 1 || $getBranch['grd_generate'] == "" ? 'hidden-div' : ''?>" id="grdLogin">
					<!-- login details -->
					<div class="headers-line mt-md">
						<i class="fas fa-user-lock"></i> <?=translate('login_details')?>
					</div>

					<div class="row mb-lg">
						<div class="col-md-6 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('username')?> <span class="required">*</span></label>
								<div class="input-group">
									<span class="input-group-addon"><i class="far fa-user"></i></span>
									<input type="text" class="form-control" name="username" value="<?=set_value('username')?>" autocomplete="off" />
								</div>
								<span class="error"><?php echo form_error('username'); ?></span>
							</div>
						</div>
						<div class="col-md-3 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('password')?> <span class="required">*</span></label>
								<div class="input-group">
									<span class="input-group-addon"><i class="fas fa-unlock-alt"></i></span>
									<input type="password" class="form-control" name="password" value="<?=set_value('password')?>" />
								</div>
								<span class="error"><?php echo form_error('password'); ?></span>
							</div>
						</div>
						<div class="col-md-3 mb-sm">
							<div class="form-group">
								<label class="control-label"><?=translate('retype_password')?> <span class="required">*</span></label>
								<div class="input-group">
									<span class="input-group-addon"><i class="fas fa-unlock-alt"></i></span>
									<input type="password" class="form-control" name="retype_password" value="<?=set_value('retype_password')?>" />
								</div>
								<span class="error"><?php echo form_error('retype_password'); ?></span>
							</div>
						</div>
					</div>
				</div>
				
				<!-- social links -->
				<div class="headers-line">
					<i class="fas fa-globe"></i> <?=translate('social_links')?>
				</div>

				<div class="row mb-lg">
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label">Facebook</label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fab fa-facebook-f"></i></span>
								<input type="url" class="form-control" name="facebook" value="<?=set_value('facebook')?>" placeholder="eg: https://www.facebook.com/username" autocomplete="off" />
							</div>
							<?php echo form_error('facebook', '<label class="error">', '</label>'); ?>
						</div>
					</div>
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label">Twitter</label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fab fa-twitter"></i></span>
								<input type="url" class="form-control" name="twitter" value="<?=set_value('twitter')?>" placeholder="eg: https://www.twitter.com/username" autocomplete="off" />
							</div>
							<?php echo form_error('twitter', '<label class="error">', '</label>'); ?>
						</div>
					</div>
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label">Linkedin</label>
							<div class="input-group">
								<span class="input-group-addon"><i class="fab fa-linkedin-in"></i></span>
								<input type="url" class="form-control" name="linkedin" value="<?=set_value('linkedin')?>" placeholder="eg: https://www.linkedin.com/username" autocomplete="off" />
							</div>
							<?php echo form_error('linkedin', '<label class="error">', '</label>'); ?>
						</div>
					</div>
				</div>
			</div>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-offset-10 col-md-2">
						<button type="submit" name="submit" value="save" class="btn btn btn-default btn-block">
							<i class="fas fa-plus-circle"></i> <?=translate('save')?>
						</button>
					</div>
				</div>
			</footer>
			<?php echo form_close();?>
		</section>
	</div>
</div>

<!-- Multiple Import Modal -->
<div id="multipleImport" class="zoom-anim-dialog modal-block modal-block-lg mfp-hide">
    <section class="panel">
        <?php echo form_open_multipart('parents/csv_import', array('class' => 'form-horizontal', 'id' => 'importCSV')); ?>
        <div class="panel-heading">
            <h4 class="panel-title"><i class="fas fa-plus-circle"></i> <?php echo translate('multiple_import'); ?></h4>
        </div>
        <div class="panel-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong><?php echo translate('instructions'); ?>:</strong><br/>
                1. <?php echo translate('download_sample_file_first'); ?><br/>
                2. <?php echo translate('fill_parent_details_carefully'); ?><br/>
                3. <?php echo translate('required_fields'); ?>: Name, Relation, MobileNo, Email, Username, Password
                <br/><br/>
                <a href="<?=base_url('parents/csv_Sampledownloader')?>" class="btn btn-sm btn-default">
                    <i class='fas fa-file-download'></i> <?=translate('download_sample_file')?>
                </a>
            </div>
            
            <?php if (is_superadmin_loggedin()) { ?>
            <div class="form-group">
                <label class="control-label col-md-3"><?=translate('branch')?> <span class="required">*</span></label>
                <div class="col-md-9">
                    <?php
                        $arrayBranch = $this->app_lib->getSelectList('branch');
                        echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' id='branchID_mod'
                        data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
                    ?>
                    <span class="error"></span>
                </div>
            </div>
            <?php } ?>
            
            <div class="form-group">
                <label class="control-label col-md-3"><?=translate('select_csv_file')?> <span class="required">*</span></label>
                <div class="col-md-9">
                    <input type="file" name="userfile" class="dropify" data-height="70" data-allowed-file-extensions="csv" />
                    <span class="error"></span>
                </div>
            </div>
            
            <div id="errorList" class="alert alert-danger" style="display: none;"></div>
        </div>
        <footer class="panel-footer">
            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary" id="importBtn" data-loading-text="<i class='fas fa-spinner fa-spin'></i> <?=translate('processing')?>">
                        <i class="fas fa-upload"></i> <?php echo translate('import'); ?>
                    </button>
                    <button type="button" class="btn btn-default modal-dismiss"><?php echo translate('close'); ?></button>
                </div>
            </div>
        </footer>
        <?php echo form_close(); ?>
    </section>
</div>

<script type="text/javascript">
    function openImportModal() {
        if (typeof $.magnificPopup !== 'undefined') {
            $.magnificPopup.open({
                items: {
                    src: '#multipleImport',
                    type: 'inline'
                },
                removalDelay: 300
            });
        } else {
            alert('Modal library not loaded. Please refresh the page.');
        }
    }
    
    function showAlert(message, type) {
        var alertClass = (type == 'success') ? 'alert-success' : 'alert-danger';
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
            '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' +
            '<i class="fas fa-' + (type == 'success' ? 'check-circle' : 'exclamation-triangle') + '"></i> ' +
            message + '</div>';
        $('body').append(alertHtml);
        setTimeout(function() {
            $('.alert-dismissible').fadeOut(function() { $(this).remove(); });
        }, 5000);
    }
    
    $(document).ready(function() {
        $('#importCSV').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            var $btn = $('#importBtn');
            var originalText = $btn.html();
            
            $btn.prop('disabled', true);
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            $('#errorList').hide().html('');
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                    
                    if (response.status == 'success') {
                        showAlert(response.message, 'success');
                        setTimeout(function() {
                            $.magnificPopup.close();
                            location.reload();
                        }, 2000);
                    } else if (response.status == 'partial') {
                        $('#errorList').html('<div class="alert alert-warning">' + response.message + '</div>').show();
                        showAlert('Import completed with warnings', 'warning');
                    } else {
                        $('#errorList').html('<div class="alert alert-danger">' + response.message + '</div>').show();
                        showAlert(response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                    
                    var errorMsg = 'Server error occurred. Please try again.';
                    if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message) errorMsg = response.message;
                        } catch(e) {
                            errorMsg = 'Server error: ' + xhr.status;
                        }
                    }
                    $('#errorList').html('<div class="alert alert-danger">' + errorMsg + '</div>').show();
                    showAlert(errorMsg, 'error');
                }
            });
        });
    });
</script>