<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fas fa-user-tag"></i> <?=translate('staff_room_assignments')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <button class="btn btn-primary" onclick="showAssignModal()">
                            <i class="fas fa-plus-circle"></i> <?=translate('assign_staff')?>
                        </button>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('staff_name')?></th>
                                <th><?=translate('hostel')?></th>
                                <th><?=translate('room')?></th>
                                <th><?=translate('role')?></th>
                                <th><?=translate('primary')?></th>
                                <th><?=translate('assigned_date')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach($assignments as $ass): ?>
                            <tr>
                                <td><?php echo $count++; ?>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo get_type_name_by_id('branch', $ass['branch_id']); ?>
                                <?php endif; ?>
                                <td><?php echo $ass['staff_name']; ?>
                                <td><?php echo $ass['hostel_name']; ?>
                                <td><?php echo $ass['room_name']; ?>
                                <td>
                                    <span class="label label-<?php 
                                        echo $ass['role'] == 'warden' ? 'danger' : 
                                            ($ass['role'] == 'matron' ? 'warning' : 
                                            ($ass['role'] == 'house_parent' ? 'info' : 'default')); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ass['role'])); ?>
                                    </span>
                                
                                <td>
                                    <?php if($ass['is_primary']): ?>
                                    <span class="label label-success">Primary</span>
                                    <?php else: ?>
                                    <span class="label label-default">Secondary</span>
                                    <?php endif; ?>
                                
                                <td><?php echo date('d M Y', strtotime($ass['assigned_date'])); ?>
                                <td>
                                    <?php if($ass['is_active']): ?>
                                    <span class="label label-success">Active</span>
                                    <?php else: ?>
                                    <span class="label label-danger">Inactive</span>
                                    <?php endif; ?>
                                
                                <td>
                                    <button class="btn btn-default btn-circle icon" onclick="editAssignment(<?=$ass['id']?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                   <button class="btn btn-danger btn-circle icon" onclick="removeAssignment(<?=$ass['id']?>, '<?=htmlspecialchars($ass['staff_name'])?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                
                             `
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?=translate('assign_staff_to_room')?></h4>
            </div>
            <?php echo form_open('hostels/save_staff_assignment', array('class' => 'frm-submit', 'id' => 'assignmentForm')); ?>
            <input type="hidden" name="assignment_id" id="assignment_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?=translate('staff')?> <span class="required">*</span></label>
                        <select name="staff_id" class="form-control" required>
                            <option value=""><?=translate('select')?></option>
                            <?php foreach($staff_list as $staff): ?>
                            <option value="<?=$staff['id']?>"><?=$staff['name']?> (<?=$staff['staff_id']?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('room')?> <span class="required">*</span></label>
                        <select name="room_id" class="form-control" required>
                            <option value=""><?=translate('select')?></option>
                            <?php foreach($rooms as $room): ?>
                            <option value="<?=$room['id']?>"><?=$room['name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('role')?> <span class="required">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="warden">Warden</option>
                            <option value="matron">Matron</option>
                            <option value="house_parent">House Parent</option>
                            <option value="caretaker">Caretaker</option>
                            <option value="inspector">Inspector</option>
                        </select>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="is_primary" value="1"> Primary Assignment
                        </label>
                    </div>
                    <div class="form-group">
                        <label><?=translate('assigned_date')?></label>
                        <input type="text" name="assigned_date" class="form-control datepicker" value="<?=date('Y-m-d')?>">
                    </div>
                    <div class="form-group">
                        <label><?=translate('end_date')?></label>
                        <input type="text" name="end_date" class="form-control datepicker" placeholder="Optional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><?=translate('assign')?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('cancel')?></button>
                </div>
            <?php echo form_close();?>
        </div>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var csrf_token = '<?=$this->security->get_csrf_hash();?>';

$(document).ready(function() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });
});

function showAssignModal() {
    $('#assignment_id').val('');
    $('#assignmentForm')[0].reset();
    $('#assignModal').modal('show');
}

function editAssignment(id) {
    console.log('Editing assignment ID:', id);
    
    $.ajax({
        url: base_url + 'hostels/get_staff_assignment',
        type: 'POST',
        data: {
            id: id,
            '<?=$this->security->get_csrf_token_name();?>': csrf_token
        },
        dataType: 'json',
        success: function(response) {
            console.log('Assignment data:', response);
            
            if (response && response.id) {
                $('#assignment_id').val(response.id);
                $('select[name="staff_id"]').val(response.staff_id).trigger('change');
                $('select[name="room_id"]').val(response.room_id).trigger('change');
                $('select[name="role"]').val(response.role).trigger('change');
                $('input[name="is_primary"]').prop('checked', response.is_primary == 1);
                $('input[name="assigned_date"]').val(response.assigned_date);
                $('input[name="end_date"]').val(response.end_date);
                $('#assignModal').modal('show');
            } else {
                alert('Failed to load assignment data');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('Error loading assignment: ' + error);
        }
    });
}

function removeAssignment(id, name) {
    console.log('Removing assignment - ID:', id, 'Staff:', name);
    
    if (confirm('Are you sure you want to remove ' + name + ' from this assignment?')) {
        // Show loading indicator
        var btn = $('button[onclick="removeAssignment(' + id + ', \'' + name + '\')"]');
        var originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.ajax({
            url: base_url + 'hostels/remove_staff_assignment',
            type: 'POST',
            data: {
                id: id,
                '<?=$this->security->get_csrf_token_name();?>': csrf_token
            },
            dataType: 'json',
            success: function(response) {
                console.log('Remove response:', response);
                btn.html(originalHtml).prop('disabled', false);
                
                if (response.status === 'success') {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message || 'Failed to remove assignment');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error - Status:', status);
                console.error('Response Text:', xhr.responseText);
                btn.html(originalHtml).prop('disabled', false);
                
                var errorMsg = 'Error removing assignment: ' + error;
                if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMsg = response.message || errorMsg;
                    } catch(e) {
                        errorMsg = xhr.statusText;
                    }
                }
                alert(errorMsg);
            }
        });
    }
}
</script>