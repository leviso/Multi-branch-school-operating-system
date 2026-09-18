<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-edit"></i> <?=$title?>
                </h4>
                <div class="panel-btn">
                    <a href="<?=base_url('subscription_admin/plans')?>" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left"></i> <?=translate('back')?>
                    </a>
                </div>
            </header>
            
            <div class="panel-body">
                <?php echo form_open_multipart(current_url(), array('class' => 'form-horizontal form-bordered')); ?>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('plan_name')?> <span class="required">*</span></label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="name" value="<?=set_value('name', isset($plan) ? $plan->name : '')?>" required>
                            <span class="error"><?=form_error('name')?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('description')?></label>
                        <div class="col-md-6">
                            <textarea class="form-control" name="description" rows="3"><?=set_value('description', isset($plan) ? $plan->description : '')?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('billing_cycle')?></label>
                        <div class="col-md-6">
                            <select class="form-control" name="billing_cycle">
                                <option value="monthly" <?=set_select('billing_cycle', 'monthly', (!isset($plan) || $plan->billing_cycle == 'monthly'))?>><?=translate('monthly')?></option>
                                <option value="termly" <?=set_select('billing_cycle', 'termly', (isset($plan) && $plan->billing_cycle == 'termly'))?>><?=translate('termly')?></option>
                                <option value="yearly" <?=set_select('billing_cycle', 'yearly', (isset($plan) && $plan->billing_cycle == 'yearly'))?>><?=translate('yearly')?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('pricing')?></label>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <span class="input-group-addon"><?=translate('monthly')?></span>
                                        <input type="number" step="0.01" class="form-control" name="price_monthly" value="<?=set_value('price_monthly', isset($plan) ? $plan->price_monthly : 0)?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <span class="input-group-addon"><?=translate('termly')?></span>
                                        <input type="number" step="0.01" class="form-control" name="price_termly" value="<?=set_value('price_termly', isset($plan) ? $plan->price_termly : 0)?>">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <span class="input-group-addon"><?=translate('yearly')?></span>
                                        <input type="number" step="0.01" class="form-control" name="price_yearly" value="<?=set_value('price_yearly', isset($plan) ? $plan->price_yearly : 0)?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('limits')?></label>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <span class="input-group-addon"><?=translate('max_students')?></span>
                                        <input type="number" class="form-control" name="max_students" value="<?=set_value('max_students', isset($plan) ? $plan->max_students : 0)?>" placeholder="0 = unlimited">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <span class="input-group-addon"><?=translate('max_staff')?></span>
                                        <input type="number" class="form-control" name="max_staff" value="<?=set_value('max_staff', isset($plan) ? $plan->max_staff : 0)?>" placeholder="0 = unlimited">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <span class="input-group-addon"><?=translate('sms_units')?></span>
                                        <input type="number" class="form-control" name="max_sms_units" value="<?=set_value('max_sms_units', isset($plan) ? $plan->max_sms_units : 0)?>" placeholder="0 = unlimited">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('trial_days')?></label>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="number" class="form-control" name="trial_days" value="<?=set_value('trial_days', isset($plan) ? $plan->trial_days : 0)?>">
                                <span class="input-group-addon"><?=translate('days')?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('modules')?> <span class="required">*</span></label>
                        <div class="col-md-6">
                            <select class="form-control" name="modules[]" id="modules" multiple required style="width: 100%; height: 200px;">
                                <?php foreach ($modules as $module): ?>
                                <option value="<?=$module->id?>" 
                                    <?=set_select('modules[]', $module->id, (isset($plan) && in_array($module->id, $plan->module_ids ?? [])))?>>
                                    <?=$module->name?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="help-block"><?=translate('select_modules_for_plan')?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('status')?></label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" <?=set_checkbox('is_active', '1', (!isset($plan) || $plan->is_active))?>>
                                    <?=translate('active')?>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <footer class="panel-footer">
                        <div class="row">
                            <div class="col-md-2 col-md-offset-3">
                                <button type="submit" name="submit" value="save" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> <?=translate('save')?>
                                </button>
                            </div>
                        </div>    
                    </footer>
                    
                <?php echo form_close(); ?>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    "use strict";
    $('#modules').select2({
        placeholder: '<?=translate('select_modules')?>',
        allowClear: true
    });
});
</script>