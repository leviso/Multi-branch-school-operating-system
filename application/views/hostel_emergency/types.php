<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fa fa-tag"></i> <?=translate('emergency_types')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php if(get_permission('emergency_types', 'can_add')): ?>
                        <button class="btn btn-primary" onclick="showTypeModal()">
                            <i class="fas fa-plus-circle"></i> <?=translate('add_emergency_type')?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?=translate('branch')?></label>
                            <select id="branch_filter" class="form-control">
                                <option value=""><?=translate('all_branches')?></option>
                                <?php foreach($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" 
                                        <?php echo (isset($selected_branch_id) && $selected_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                        <?php echo html_escape($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('name')?></th>
                                <th><?=translate('icon')?></th>
                                <th><?=translate('priority')?></th>
                                <th><?=translate('immediate_sms')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach($types as $type): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                                <td><?php echo html_escape($type['branch_name'] ?? get_type_name_by_id('branch', $type['branch_id'])); ?></td>
                                <?php endif; ?>
                                <td><?php echo html_escape($type['name']); ?></td>
                                <td><i class="fa <?php echo $type['icon']; ?> fa-2x"></i></td>
                                <td>
                                    <span class="label label-<?php 
                                        echo $type['priority'] == 'critical' ? 'danger' : 
                                            ($type['priority'] == 'high' ? 'warning' : 
                                            ($type['priority'] == 'medium' ? 'info' : 'success')); 
                                    ?>">
                                        <?php echo ucfirst(translate($type['priority'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($type['requires_immediate_sms']): ?>
                                    <span class="label label-success"><?=translate('yes')?></span>
                                    <?php else: ?>
                                    <span class="label label-default"><?=translate('no')?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($type['is_active']): ?>
                                    <span class="label label-success"><?=translate('active')?></span>
                                    <?php else: ?>
                                    <span class="label label-danger"><?=translate('inactive')?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(get_permission('emergency_types', 'can_edit')): ?>
                                    <button class="btn btn-default btn-circle icon" onclick="editType(<?=$type['id']?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if(get_permission('emergency_types', 'can_delete')): ?>
                                    <button class="btn btn-danger btn-circle icon" onclick="deleteType(<?=$type['id']?>, '<?=htmlspecialchars($type['name'])?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($types)): ?>
                            <tr>
                                <td colspan="<?php echo (is_superadmin_loggedin() && isset($branches)) ? '8' : '7'; ?>" class="text-center"><?=translate('no_records_found')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Type Modal -->
<div class="modal fade" id="typeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?=translate('add_emergency_type')?></h4>
            </div>
            <?php echo form_open('hostel_emergency/type_save', array('class' => 'frm-submit', 'id' => 'typeForm')); ?>
            <input type="hidden" name="type_id" id="type_id">
                <div class="modal-body">
                    <?php if(is_superadmin_loggedin() && isset($branches)): ?>
                    <div class="form-group">
                        <label><?=translate('branch')?> <span class="required">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            <option value=""><?=translate('select_branch')?></option>
                            <?php foreach($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>">
                                    <?php echo html_escape($branch['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label><?=translate('name')?> <span class="required">*</span></label>
                        <input type="text" name="name" id="type_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?=translate('icon')?></label>
                        <div class="input-group">
                            <input type="text" name="icon" id="type_icon" class="form-control" value="fa-exclamation-triangle">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        </div>
                        <small class="text-muted"><?=translate('fontawesome_icon_class_e_g_fa_exclamation_triangle')?></small>
                    </div>
                    <div class="form-group">
                        <label><?=translate('priority')?></label>
                        <select name="priority" id="type_priority" class="form-control">
                            <option value="low"><?=translate('low')?></option>
                            <option value="medium"><?=translate('medium')?></option>
                            <option value="high"><?=translate('high')?></option>
                            <option value="critical"><?=translate('critical')?></option>
                        </select>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="requires_immediate_sms" id="type_sms" value="1">
                            <?=translate('requires_immediate_sms_notification')?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="is_active" id="type_active" value="1" checked>
                            <?=translate('active')?>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('cancel')?></button>
                    <button type="submit" class="btn btn-primary"><?=translate('save')?></button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var csrf_token = '<?=$this->security->get_csrf_hash();?>';

function showTypeModal() {
    $('#type_id').val('');
    $('#typeForm')[0].reset();
    $('#type_icon').val('fa-exclamation-triangle');
    $('#type_sms').prop('checked', true);
    $('#type_active').prop('checked', true);
    $('#typeModal .modal-title').text('<?=translate('add_emergency_type')?>');
    $('#typeModal').modal('show');
}

function editType(id) {
    console.log('Editing type ID:', id);
    
    $.ajax({
        url: base_url + 'hostel_emergency/get_emergency_type',
        type: 'POST',
        data: {
            id: id,
            '<?=$this->security->get_csrf_token_name();?>': csrf_token
        },
        dataType: 'json',
        success: function(response) {
            console.log('Type data:', response);
            
            if (response && response.id) {
                $('#type_id').val(response.id);
                $('#type_name').val(response.name);
                $('#type_icon').val(response.icon);
                $('#type_priority').val(response.priority);
                $('#type_sms').prop('checked', response.requires_immediate_sms == 1);
                $('#type_active').prop('checked', response.is_active == 1);
                $('#typeModal .modal-title').text('<?=translate('edit_emergency_type')?>');
                $('#typeModal').modal('show');
            } else {
                alert('Failed to load type data');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('Error loading type: ' + error);
        }
    });
}

function deleteType(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?')) {
        $.ajax({
            url: base_url + 'hostel_emergency/type_delete',
            type: 'POST',
            data: {
                id: id,
                '<?=$this->security->get_csrf_token_name();?>': csrf_token
            },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message || 'Failed to delete');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Error deleting type: ' + error);
            }
        });
    }
}

$(document).ready(function() {
    $('#branch_filter').change(function() {
        var branch_id = $(this).val();
        var url = base_url + 'hostel_emergency/types';
        if(branch_id) {
            window.location.href = url + '?branch_id=' + branch_id;
        } else {
            window.location.href = url;
        }
    });
});
</script>