<?php
// This fragment is loaded via AJAX
$csrf_token = $this->security->get_csrf_hash();
?>

<section class="panel">
    <div class="panel-body">
        <form action="<?=base_url('hostel_emergency/save_medical_form'); ?>" method="post" id="medicalForm">
            
            <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$csrf_token;?>">
            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
            <input type="hidden" name="branch_id" value="<?php echo $branch_id; ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=translate('blood_group')?></label>
                        <select name="blood_group" class="form-control">
                            <option value=""><?=translate('select_blood_group')?></option>
                            <option value="A+" <?php echo (isset($medical['blood_group']) && $medical['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo (isset($medical['blood_group']) && $medical['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo (isset($medical['blood_group']) && $medical['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo (isset($medical['blood_group']) && $medical['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                            <option value="AB+" <?php echo (isset($medical['blood_group']) && $medical['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo (isset($medical['blood_group']) && $medical['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                            <option value="O+" <?php echo (isset($medical['blood_group']) && $medical['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo (isset($medical['blood_group']) && $medical['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=translate('allergies')?></label>
                        <textarea name="allergies" class="form-control" rows="2" placeholder="<?=translate('list_any_allergies')?>"><?php echo html_escape($medical['allergies'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=translate('chronic_conditions')?></label>
                        <textarea name="chronic_conditions" class="form-control" rows="2" placeholder="<?=translate('list_any_chronic_conditions')?>"><?php echo html_escape($medical['chronic_conditions'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=translate('medications')?></label>
                        <textarea name="medications" class="form-control" rows="2" placeholder="<?=translate('list_regular_medications')?>"><?php echo html_escape($medical['medications'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="panel panel-default mt-md">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-phone"></i> <?=translate('emergency_contact')?></h4>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?=translate('contact_name')?></label>
                                <input type="text" name="emergency_contact_name" class="form-control" 
                                       value="<?php echo html_escape($medical['emergency_contact_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?=translate('phone_number')?></label>
                                <input type="text" name="emergency_contact_phone" class="form-control" 
                                       value="<?php echo html_escape($medical['emergency_contact_phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?=translate('relation')?></label>
                                <input type="text" name="emergency_contact_relation" class="form-control" 
                                       value="<?php echo html_escape($medical['emergency_contact_relation'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-hospital"></i> <?=translate('insurance_information')?></h4>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('insurance_provider')?></label>
                                <input type="text" name="insurance_provider" class="form-control" 
                                       value="<?php echo html_escape($medical['insurance_provider'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('policy_number')?></label>
                                <input type="text" name="insurance_policy_no" class="form-control" 
                                       value="<?php echo html_escape($medical['insurance_policy_no'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-user-md"></i> <?=translate('doctor_information')?></h4>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?=translate('doctor_name')?></label>
                                <input type="text" name="doctor_name" class="form-control" 
                                       value="<?php echo html_escape($medical['doctor_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?=translate('doctor_phone')?></label>
                                <input type="text" name="doctor_phone" class="form-control" 
                                       value="<?php echo html_escape($medical['doctor_phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?=translate('doctor_address')?></label>
                                <input type="text" name="doctor_address" class="form-control" 
                                       value="<?php echo html_escape($medical['doctor_address'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-md">
                <button type="submit" class="btn btn-primary" name="save_medical" value="1">
                    <i class="fas fa-save"></i> <?=translate('save_medical_info')?>
                </button>
            </div>
            
        </form>
        
        <?php if(!empty($medical) && ($medical['allergies'] || $medical['chronic_conditions'] || $medical['medications'] || $medical['blood_group'])): ?>
        <div class="alert alert-info mt-md">
            <strong><i class="fa fa-info-circle"></i> <?=translate('saved_medical_records')?>:</strong><br>
            <?php if(!empty($medical['blood_group'])): ?>
            <small>🩸 <?=translate('blood_group')?>: <?php echo html_escape($medical['blood_group']); ?></small><br>
            <?php endif; ?>
            <?php if(!empty($medical['allergies'])): ?>
            <small>⚠️ <?=translate('allergies')?>: <?php echo html_escape($medical['allergies']); ?></small><br>
            <?php endif; ?>
            <?php if(!empty($medical['chronic_conditions'])): ?>
            <small>🏥 <?=translate('chronic_conditions')?>: <?php echo html_escape($medical['chronic_conditions']); ?></small><br>
            <?php endif; ?>
            <?php if(!empty($medical['medications'])): ?>
            <small>💊 <?=translate('medications')?>: <?php echo html_escape($medical['medications']); ?></small><br>
            <?php endif; ?>
            <?php if(!empty($medical['last_updated'])): ?>
            <small>📅 <?=translate('last_updated')?>: <?php echo date('d M Y H:i', strtotime($medical['last_updated'])); ?></small>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
$(document).ready(function() {
    $('#medicalForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> <?=translate('saving')?>...').prop('disabled', true);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Reload the medical form to show updated data
                    var studentId = $('input[name="student_id"]').val();
                    var branchId = $('input[name="branch_id"]').val();
                    loadMedicalForm(studentId, branchId);
                } else {
                    alert(response.message || '<?=translate('error_saving')?>');
                    $btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                var errorMsg = '<?=translate('error_saving')?>';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch(e) {}
                alert(errorMsg);
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
</script>