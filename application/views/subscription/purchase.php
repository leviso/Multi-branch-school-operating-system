<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-shopping-cart"></i> <?=translate('purchase_subscription')?>
                </h4>
            </header>
            
            <div class="panel-body">
                
                <?php if (!empty($current_subscription)): ?>
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> <?=translate('current_subscription')?></h4>
                    <div class="row">
                        <div class="col-md-4">
                            <strong><?=translate('plan')?>:</strong> <?=html_escape($current_subscription->plan_name)?>
                        </div>
                        <div class="col-md-4">
                            <strong><?=translate('valid_until')?>:</strong> <?=_d($current_subscription->end_date)?>
                        </div>
                        <div class="col-md-4">
                            <strong><?=translate('status')?>:</strong> 
                            <span class="label label-success"><?=translate($current_subscription->status)?></span>
                        </div>
                    </div>
                    <hr>
                    <p class="mb-none"><i class="fas fa-exclamation-triangle text-warning"></i> <?=translate('purchase_new_subscription_will_replace_current')?></p>
                </div>
                <?php endif; ?>
                
                <!-- Plans Grid -->
                <div class="row">
                    <?php 
                    $col_class = (count($plans) == 3) ? 'col-md-4' : ((count($plans) == 2) ? 'col-md-6' : 'col-md-4');
                    foreach ($plans as $plan):
                    
                    // Check if this is the current active plan
                    $is_current_plan = (!empty($current_subscription) && $current_subscription->plan_id == $plan->id);
                    
                    // Check if subscription is active or trial
                    $has_active_subscription = !empty($current_subscription) && in_array($current_subscription->status, ['active', 'trial']);
                    
                    // Determine button state
                    if ($has_active_subscription && $is_current_plan):
                        // Currently on this plan
                        $button_disabled = true;
                        $button_text = '<i class="fas fa-check-circle"></i> ' . translate('current_plan');
                        $button_class = 'btn-success';
                    elseif ($has_active_subscription && !$is_current_plan):
                        // Has active subscription but different plan - can upgrade
                        $button_disabled = false;
                        $button_text = '<i class="fas fa-arrow-up"></i> ' . translate('upgrade_to_this_plan');
                        $button_class = 'btn-warning';
                    else:
                        // No active subscription - can purchase
                        $button_disabled = false;
                        $button_text = '<i class="fas fa-shopping-cart"></i> ' . translate('purchase_now');
                        $button_class = 'btn-primary';
                    endif;
                    ?>
                    <div class="<?=$col_class?>">
                        <div class="panel panel-default plan-card">
                            <div class="panel-heading text-center">
                                <h3 class="panel-title"><?=html_escape($plan->name)?></h3>
                                <?php if (!empty($plan->description)): ?>
                                <p class="text-muted mb-none"><?=html_escape($plan->description)?></p>
                                <?php endif; ?>
                                
                                <?php if ($is_current_plan && $has_active_subscription): ?>
                                <span class="label label-success" style="margin-top: 10px; display: inline-block;">
                                    <i class="fas fa-check"></i> <?=translate('active_plan')?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="panel-body text-center">
                                <div class="pricing">
                                    <h2 class="text-success">KES <?=number_format($plan->price_monthly)?></h2>
                                    <p><?=translate('per_month')?></p>
                                    
                                    <?php if ($plan->price_termly > 0): ?>
                                    <h4>KES <?=number_format($plan->price_termly)?> / <?=translate('term')?></h4>
                                    <?php endif; ?>
                                    
                                    <?php if ($plan->price_yearly > 0): ?>
                                    <h4>KES <?=number_format($plan->price_yearly)?> / <?=translate('year')?></h4>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="plan-features text-left">
                                    <p><i class="fas fa-check text-success"></i> <?=translate('max_students')?>: <strong><?=$plan->max_students ?: translate('unlimited')?></strong></p>
                                    <p><i class="fas fa-check text-success"></i> <?=translate('max_staff')?>: <strong><?=$plan->max_staff ?: translate('unlimited')?></strong></p>
                                    <p><i class="fas fa-check text-success"></i> <?=translate('sms_units')?>: <strong><?=$plan->max_sms_units ?: translate('unlimited')?></strong></p>
                                    <p><i class="fas fa-check text-success"></i> <?=translate('includes_modules')?>:</p>
                                    <ul class="list-unstyled">
                                        <?php 
                                        if (!empty($plan->modules)):
                                            $module_names = array_column($plan->modules, 'name');
                                            $display_modules = array_slice($module_names, 0, 5);
                                            foreach ($display_modules as $module):
                                        ?>
                                        <li><i class="fas fa-angle-right text-primary"></i> <?=html_escape($module)?></li>
                                        <?php 
                                            endforeach; 
                                            if (count($module_names) > 5):
                                        ?>
                                        <li><em>+ <?=(count($module_names) - 5)?> <?=translate('more')?></em></li>
                                        <?php 
                                            endif;
                                        endif; 
                                        ?>
                                    </ul>
                                </div>
                                
                                <?php if ($plan->name == 'Trial'): ?>
                                <!-- Trial Plan - Always show as disabled/info -->
                                <button class="btn btn-default btn-block" disabled style="cursor: not-allowed; opacity: 0.6;">
                                    <i class="fas fa-info-circle"></i> <?=translate('trial_plan_info')?>
                                </button>
                                <small class="text-muted"><?=translate('trial_automatically_assigned')?></small>
                                
                                <?php else: ?>
                                <!-- Paid Plans -->
                                <button class="btn <?=$button_class?> btn-block btn-purchase" 
                                        data-plan-id="<?=$plan->id?>"
                                        data-plan-name="<?=html_escape($plan->name)?>"
                                        data-monthly="<?=$plan->price_monthly?>"
                                        data-termly="<?=$plan->price_termly?>"
                                        data-yearly="<?=$plan->price_yearly?>"
                                        <?=($button_disabled ? 'disabled style="cursor: not-allowed; opacity: 0.7;"' : '')?>>
                                    <?=$button_text?>
                                </button>
                                
                                <?php if ($has_active_subscription && $is_current_plan): ?>
                                <small class="text-success"><?=translate('expires_on')?> <?=_d($current_subscription->end_date)?></small>
                                <?php elseif ($has_active_subscription && !$is_current_plan): ?>
                                <small class="text-warning"><?=translate('upgrade_will_replace_current')?></small>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Purchase Modal -->
<div class="modal fade" id="purchaseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open('subscription/initiate_purchase', array('id' => 'purchaseForm')); ?>
            <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?=translate('purchase_subscription')?></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?=translate('plan')?>: <strong id="selected-plan-name"></strong></label>
                    <input type="hidden" name="plan_id" id="plan_id">
                </div>
                
                <div class="form-group">
                    <label><?=translate('billing_cycle')?> <span class="required">*</span></label>
                    <select class="form-control" name="billing_cycle" id="billing_cycle" required>
                        <option value="monthly"><?=translate('monthly')?></option>
                        <option value="termly"><?=translate('termly')?></option>
                        <option value="yearly"><?=translate('yearly')?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?=translate('amount_to_pay')?>:</label>
                    <p class="form-control-static"><strong id="amount-display" class="text-success"></strong></p>
                </div>
                
                <div class="form-group">
                    <label><?=translate('mpesa_phone')?> <span class="required">*</span></label>
                    <input type="text" class="form-control" name="phone" placeholder="2547XXXXXXXX" required>
                    <span class="help-block"><?=translate('enter_mpesa_registered_phone')?></span>
                </div>
                
                <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('cancel')?></button>
                <button type="submit" class="btn btn-primary" id="pay-btn">
                    <i class="fas fa-lock"></i> <?=translate('pay_now')?>
                </button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?=translate('payment_status')?></h4>
            </div>
            <div class="modal-body text-center">
                <div id="status-message"></div>
                <div id="status-spinner" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p><?=translate('checking_payment_status')?>...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.plan-card {
    transition: all 0.3s;
    margin-bottom: 20px;
    height: 100%;
}
.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.plan-features {
    min-height: 180px;
}
.plan-features ul {
    padding-left: 15px;
}
.plan-features ul li {
    margin-bottom: 3px;
}
.pricing h2 {
    margin: 0;
    font-size: 32px;
}
</style>

<script type="text/javascript">
var base_url = '<?php echo rtrim(base_url(), '/'); ?>';

$(document).ready(function() {
    "use strict";
    
    var checkout_id = '';
    var subscription_id = '';
    var statusCheckInterval = null;
    
    // Plan button click handler
    $('.btn-purchase').click(function() {
        var plan_id = $(this).data('plan-id');
        var plan_name = $(this).data('plan-name');
        var monthly = parseFloat($(this).data('monthly')) || 0;
        var termly = parseFloat($(this).data('termly')) || 0;
        var yearly = parseFloat($(this).data('yearly')) || 0;
        
        $('#plan_id').val(plan_id);
        $('#selected-plan-name').text(plan_name);
        
        // Store prices
        $('#billing_cycle').data('monthly', monthly);
        $('#billing_cycle').data('termly', termly);
        $('#billing_cycle').data('yearly', yearly);
        
        updateAmount();
        $('#purchaseModal').modal('show');
    });
    
    // Billing cycle change handler
    $('#billing_cycle').change(function() {
        updateAmount();
    });
    
    function updateAmount() {
        var cycle = $('#billing_cycle').val();
        var amount = $('#billing_cycle').data(cycle);
        
        amount = parseFloat(amount) || 0;
        
        if (amount > 0) {
            $('#amount-display').text('KES ' + amount.toFixed(2));
        } else {
            $('#amount-display').text('KES 0.00');
        }
    }
    
    // Form submission handler
    $('#purchaseForm').submit(function(e) {
        e.preventDefault();
        
        var phone = $('input[name="phone"]').val().trim();
        if (!phone) {
            alert('<?php echo translate('please_enter_phone_number'); ?>');
            return;
        }
        
        $('#pay-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php echo translate('processing'); ?>...');
        
        // Get CSRF token
        var csrf_token_name = '<?php echo $this->security->get_csrf_token_name(); ?>';
        var csrf_hash = '<?php echo $this->security->get_csrf_hash(); ?>';
        
        // Serialize form and add CSRF
        var formData = $(this).serialize();
        formData += '&' + csrf_token_name + '=' + encodeURIComponent(csrf_hash);
        
        console.log('Sending purchase request...');
        console.log('Base URL:', base_url);
        
        $.ajax({
            url: base_url + '/subscription/initiate_purchase',
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                console.log('Purchase response:', response);
                
                if (response.success) {
                    checkout_id = response.checkout_request_id;
                    subscription_id = response.subscription_id;
                    
                    $('#purchaseModal').modal('hide');
                    $('#statusModal').modal('show');
                    $('#status-message').html('<div class="alert alert-success">' + response.message + '</div>');
                    $('#status-spinner').show();
                    
                    // Start checking payment status
                    startStatusCheck();
                } else {
                    alert(response.message || '<?php echo translate('payment_failed'); ?>');
                    $('#pay-btn').prop('disabled', false).html('<i class="fas fa-lock"></i> <?php echo translate('pay_now'); ?>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response Text:', xhr.responseText);
                alert('<?php echo translate('error_processing_payment'); ?>');
                $('#pay-btn').prop('disabled', false).html('<i class="fas fa-lock"></i> <?php echo translate('pay_now'); ?>');
            }
        });
    });
    
    // Status check function
    function startStatusCheck() {
        var checks = 0;
        var maxChecks = 30;
        var isCompleted = false;
        
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }
        
        statusCheckInterval = setInterval(function() {
            if (isCompleted) return;
            
            checks++;
            console.log('Status check #' + checks);
            console.log('checkout_id:', checkout_id);
            console.log('subscription_id:', subscription_id);
            
            // Don't proceed if IDs are missing
            if (!checkout_id || !subscription_id) {
                console.error('Missing checkout_id or subscription_id');
                return;
            }
            
            $.ajax({
                url: base_url + '/subscription/check_payment_status',
                type: 'POST',
                data: {
                    checkout_request_id: checkout_id,
                    subscription_id: subscription_id
                },
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    console.log('Status response:', response);
                    
                    if (response.success && response.status == 'completed') {
                        isCompleted = true;
                        clearInterval(statusCheckInterval);
                        $('#status-spinner').hide();
                        $('#status-message').html(
                            '<div class="alert alert-success">' +
                            '<i class="fas fa-check-circle"></i> ' +
                            (response.message || 'Payment successful!') +
                            '</div>'
                        );
                        setTimeout(function() {
                            window.location.href = base_url + '/dashboard';
                        }, 2000);
                        return;
                    } 
                    else if (response.status == 'cancelled' || response.status == 'failed') {
                        isCompleted = true;
                        clearInterval(statusCheckInterval);
                        $('#status-spinner').hide();
                        $('#status-message').html(
                            '<div class="alert alert-warning">' +
                            '<i class="fas fa-exclamation-triangle"></i> ' +
                            (response.message || 'Payment failed') +
                            '</div>'
                        );
                        return;
                    }
                    else if (checks >= maxChecks) {
                        isCompleted = true;
                        clearInterval(statusCheckInterval);
                        $('#status-spinner').hide();
                        $('#status-message').html(
                            '<div class="alert alert-info">' +
                            '<i class="fas fa-info-circle"></i> ' +
                            'Payment is taking longer. Check dashboard later.' +
                            '</div>'
                        );
                        return;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response Text:', xhr.responseText);
                    // Don't stop on error - continue checking
                }
            });
        }, 4000);
    }
    
    // Clear interval when modal is hidden
    $('#statusModal').on('hidden.bs.modal', function() {
        console.log('Status modal hidden, clearing interval');
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }
    });
});
</script>
<style>
.plan-card {
    transition: all 0.3s;
    margin-bottom: 20px;
    height: 100%;
}
.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.plan-features {
    min-height: 180px;
}
.plan-features ul {
    padding-left: 15px;
}
.plan-features ul li {
    margin-bottom: 3px;
}
.pricing h2 {
    margin: 0;
    font-size: 32px;
}
.btn:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}
.label {
    padding: 5px 10px;
    font-size: 12px;
}
</style>