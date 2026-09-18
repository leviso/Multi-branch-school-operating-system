<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-building"></i> <?=translate('branch_subscriptions')?>
                </h4>
            </header>
            
            <div class="panel-body">
                <!-- Branch Selection -->
                <div class="row mb-lg">
                    <div class="col-md-8 col-md-offset-2">
                        <form method="get" action="<?=base_url('subscription_admin/branch_subscriptions')?>" class="form-horizontal">
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('select_branch')?></label>
                                <div class="col-md-7">
                                    <select class="form-control" name="branch_id" onchange="this.form.submit()">
                                        <option value="">-- <?=translate('select_branch')?> --</option>
                                        <?php foreach ($branches as $b): ?>
                                        <option value="<?=$b->id?>" <?=($selected_branch == $b->id) ? 'selected' : ''?>>
                                            <?=html_escape($b->name)?> (<?=html_escape($b->school_name)?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <noscript>
                                        <button type="submit" class="btn btn-primary"><?=translate('go')?></button>
                                    </noscript>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($selected_branch && isset($branch_info) && $branch_info): ?>
                
                <!-- Branch Info Panel -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fas fa-info-circle"></i> <?=translate('branch_information')?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered table-condensed">
                                            <tr>
                                                <th width="40%"><?=translate('branch_name')?></th>
                                                <td><?=html_escape($branch_info->name)?></td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('school_name')?></th>
                                                <td><?=html_escape($branch_info->school_name)?></td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('email')?></th>
                                                <td><?=html_escape($branch_info->email)?></td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('mobile_no')?></th>
                                                <td><?=html_escape($branch_info->mobileno)?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-bordered table-condensed">
                                            <tr>
                                                <th width="40%"><?=translate('subscription_status')?></th>
                                                <td>
                                                    <?php 
                                                    $status_class = 'default';
                                                    if ($branch_info->subscription_status == 'active') $status_class = 'success';
                                                    elseif ($branch_info->subscription_status == 'trial') $status_class = 'info';
                                                    elseif ($branch_info->subscription_status == 'expired') $status_class = 'danger';
                                                    ?>
                                                    <span class="label label-<?=$status_class?>"><?=translate($branch_info->subscription_status)?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('city')?></th>
                                                <td><?=html_escape($branch_info->city)?></td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('address')?></th>
                                                <td><?=html_escape($branch_info->address)?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Current Subscription -->
                <?php if (!empty($current_subscription)): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fas fa-check-circle"></i> <?=translate('current_subscription')?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered table-condensed">
                                            <tr>
                                                <th width="40%"><?=translate('plan_name')?></th>
                                                <td><strong><?=html_escape($current_subscription->plan_name)?></strong></td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('billing_cycle')?></th>
                                                <td><?=translate($current_subscription->billing_cycle)?></td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('start_date')?></th>
                                                <td><?=_d($current_subscription->start_date)?></td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('end_date')?></th>
                                                <td><?=_d($current_subscription->end_date)?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-bordered table-condensed">
                                            <tr>
                                                <th width="40%"><?=translate('amount_paid')?></th>
                                                <td>KES <?=number_format($current_subscription->amount_paid, 2)?></td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('payment_status')?></th>
                                                <td>
                                                    <?php if ($current_subscription->payment_status == 'paid'): ?>
                                                        <span class="label label-success"><?=translate('paid')?></span>
                                                    <?php else: ?>
                                                        <span class="label label-warning"><?=translate($current_subscription->payment_status)?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><?=translate('auto_renew')?></th>
                                                <td>
                                                    <?php if ($current_subscription->auto_renew): ?>
                                                        <span class="label label-info"><?=translate('yes')?></span>
                                                    <?php else: ?>
                                                        <span class="label label-default"><?=translate('no')?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Assign New Subscription Form -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fas fa-plus-circle"></i> <?=translate('assign_new_subscription')?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <form id="assignForm" class="form-horizontal">
                                    <input type="hidden" name="branch_id" value="<?=$selected_branch?>">
                                    
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?=translate('select_plan')?> <span class="required">*</span></label>
                                        <div class="col-md-6">
                                            <select class="form-control" name="plan_id" id="plan_id" required>
                                                <option value="">-- <?=translate('select_plan')?> --</option>
                                                <?php foreach ($plans as $plan): ?>
                                                <option value="<?=$plan->id?>" 
                                                        data-monthly="<?=$plan->price_monthly?>"
                                                        data-termly="<?=$plan->price_termly?>"
                                                        data-yearly="<?=$plan->price_yearly?>">
                                                    <?=html_escape($plan->name)?> - KES <?=number_format($plan->price_monthly)?>/<?=translate('month')?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?=translate('billing_cycle')?> <span class="required">*</span></label>
                                        <div class="col-md-6">
                                            <select class="form-control" name="billing_cycle" id="billing_cycle" required>
                                                <option value="monthly"><?=translate('monthly')?></option>
                                                <option value="termly"><?=translate('termly')?></option>
                                                <option value="yearly"><?=translate('yearly')?></option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?=translate('start_date')?></label>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control datepicker" name="start_date" value="<?=date('Y-m-d')?>" autocomplete="off">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" id="amount_display" style="display: none;">
                                        <label class="col-md-3 control-label"><?=translate('amount')?></label>
                                        <div class="col-md-6">
                                            <p class="form-control-static"><strong id="amount_value" class="text-success">KES 0.00</strong></p>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="col-md-6 col-md-offset-3">
                                            <button type="button" class="btn btn-primary" id="assignBtn">
                                                <i class="fas fa-check-circle"></i> <?=translate('assign_subscription')?>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Subscription History -->
                <?php if (!empty($subscription_history)): ?>
                <div class="row mt-lg">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fas fa-history"></i> <?=translate('subscription_history')?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th><?=translate('plan')?></th>
                                                <th class="text-center"><?=translate('billing_cycle')?></th>
                                                <th class="text-center"><?=translate('start_date')?></th>
                                                <th class="text-center"><?=translate('end_date')?></th>
                                                <th class="text-right"><?=translate('amount')?></th>
                                                <th class="text-center"><?=translate('status')?></th>
                                                <th class="text-center"><?=translate('payment_status')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subscription_history as $sub): ?>
                                            <tr>
                                                <td><?=html_escape($sub->plan_name)?></td>
                                                <td class="text-center"><?=translate($sub->billing_cycle)?></td>
                                                <td class="text-center"><?=_d($sub->start_date)?></td>
                                                <td class="text-center"><?=_d($sub->end_date)?></td>
                                                <td class="text-right">KES <?=number_format($sub->amount_paid, 2)?></td>
                                                <td class="text-center">
                                                    <?php 
                                                    $status_class = 'default';
                                                    if ($sub->status == 'active') $status_class = 'success';
                                                    elseif ($sub->status == 'expired') $status_class = 'danger';
                                                    elseif ($sub->status == 'trial') $status_class = 'info';
                                                    elseif ($sub->status == 'cancelled') $status_class = 'warning';
                                                    ?>
                                                    <span class="label label-<?=$status_class?>"><?=translate($sub->status)?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($sub->payment_status == 'paid'): ?>
                                                        <span class="label label-success"><?=translate('paid')?></span>
                                                    <?php else: ?>
                                                        <span class="label label-warning"><?=translate($sub->payment_status)?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-arrow-up"></i> <?=translate('select_branch_first')?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    "use strict";
    
    // Initialize datepicker
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
    
    // Show amount when plan and cycle selected
    $('#plan_id, #billing_cycle').change(function() {
        var plan_id = $('#plan_id').val();
        var cycle = $('#billing_cycle').val();
        
        if (plan_id) {
            var selected = $('#plan_id option:selected');
            var amount = selected.data(cycle);
            
            if (amount > 0) {
                $('#amount_value').text('KES ' + amount.toFixed(2));
                $('#amount_display').show();
            } else {
                $('#amount_display').hide();
            }
        } else {
            $('#amount_display').hide();
        }
    });
    
    // Assign subscription
    $('#assignBtn').click(function() {
        var plan_id = $('#plan_id').val();
        if (!plan_id) {
            alert('Please select a plan');
            return;
        }
        
        if (!confirm('Are you sure you want to assign this subscription?')) {
            return;
        }
        
        // Get CSRF token values
        var csrf_token_name = '<?=$this->security->get_csrf_token_name();?>';
        var csrf_hash = '<?=$this->security->get_csrf_hash();?>';
        
        var formData = {
            branch_id: $('input[name="branch_id"]').val(),
            plan_id: plan_id,
            billing_cycle: $('#billing_cycle').val(),
            start_date: $('input[name="start_date"]').val()
        };
        
        // Add CSRF token to data
        formData[csrf_token_name] = csrf_hash;
        
        $('#assignBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: base_url + 'subscription_admin/assign_subscription',
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    } else {
                        alert(response.message);
                    }
                    
                    // Reload page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Failed to assign subscription');
                    } else {
                        alert(response.message || 'Failed to assign subscription');
                    }
                    $('#assignBtn').prop('disabled', false).html('<i class="fas fa-check-circle"></i> <?=translate('assign_subscription')?>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                
                var errorMsg = 'Failed to assign subscription. ';
                if (status === 'timeout') {
                    errorMsg += 'Request timed out.';
                } else if (xhr.status === 403) {
                    errorMsg += 'Security token validation failed.';
                } else if (xhr.status === 404) {
                    errorMsg += 'Endpoint not found.';
                } else {
                    errorMsg += 'Please try again.';
                }
                
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert(errorMsg);
                }
                
                $('#assignBtn').prop('disabled', false).html('<i class="fas fa-check-circle"></i> <?=translate('assign_subscription')?>');
            }
        });
    });
});
</script>