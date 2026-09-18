<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <div class="tabs-custom">
                <ul class="nav nav-tabs">
                    <li>
                        <a href="<?=base_url('library/reports')?>"><i class="fas fa-chart-bar"></i> <?=translate('library_reports')?></a>
                    </li>
                    <li class="active">
                        <a href="#settings" data-toggle="tab"><i class="fas fa-cog"></i> <?=translate('library_settings')?></a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="settings">
                        <?php echo form_open('library/settings', array('class' => 'form-horizontal form-bordered frm-submit-data')); ?>
                        <div class="panel-body">
                            <?php if ($is_superadmin): ?>
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('branch')?> <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <select name="branch_id" id="branch_id" class="form-control">
                                        <option value=""><?=translate('select_branch')?></option>
                                        <?php foreach ($all_branches as $branch): ?>
                                        <option value="<?=$branch['id']?>" <?=($branch_id == $branch['id']) ? 'selected' : ''?>>
                                            <?=htmlspecialchars($branch['name'])?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('fine_per_day')?> (<?=$global_config['currency_symbol']?>) <span class="required">*</span></label>
                                <div class="col-md-3">
                                    <input type="number" step="0.50" class="form-control" name="fine_per_day" value="<?=$settings['fine_per_day']?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('max_borrow_days')?> <span class="required">*</span></label>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="max_borrow_days" value="<?=$settings['max_borrow_days']?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('max_books_student')?> <span class="required">*</span></label>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="max_books_student" value="<?=$settings['max_books_student']?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('max_books_teacher')?> <span class="required">*</span></label>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="max_books_teacher" value="<?=$settings['max_books_teacher']?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('max_books_staff')?> <span class="required">*</span></label>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="max_books_staff" value="<?=$settings['max_books_staff']?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('reservation_expiry_days')?> <span class="required">*</span></label>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="reservation_expiry_days" value="<?=$settings['reservation_expiry_days']?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-md-offset-3 col-md-6">
                                    <div class="checkbox-replace">
                                        <label class="i-checks">
                                            <input type="checkbox" name="auto_calculate_fine" value="1" <?=$settings['auto_calculate_fine'] ? 'checked' : ''?>>
                                            <i></i> <?=translate('auto_calculate_fine')?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-md-offset-3 col-md-6">
                                    <div class="checkbox-replace">
                                        <label class="i-checks">
                                            <input type="checkbox" name="sms_notification_enabled" value="1" <?=$settings['sms_notification_enabled'] ? 'checked' : ''?>>
                                            <i></i> <?=translate('enable_sms_notifications')?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <footer class="panel-footer">
                            <div class="row">
                                <div class="col-md-offset-3 col-md-2">
                                    <button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> <?=translate('processing')?>">
                                        <i class="fas fa-save"></i> <?=translate('save_settings')?>
                                    </button>
                                </div>
                            </div>
                        </footer>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    <?php if ($is_superadmin): ?>
    // Branch filter change - reload page with selected branch
    $('#branch_id').on('change', function() {
        var branch_id = $(this).val();
        if (branch_id) {
            window.location.href = base_url + 'library/settings?branch_id=' + branch_id;
        } else {
            window.location.href = base_url + 'library/settings';
        }
    });
    <?php endif; ?>
});
</script>