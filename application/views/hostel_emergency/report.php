<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-plus-circle"></i> <?=translate('report_incident')?></h4>
            </header>
            <div class="panel-body">
                <!-- REMOVE frm-submit class - use normal form submission -->
                <form action="<?=base_url('hostel_emergency/report')?>" method="post" id="incident_form">
                    
                    <!-- CSRF Token -->
                    <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>">
                    
                    <?php if(is_superadmin_loggedin() && isset($branches)): ?>
                    <div class="form-group">
                        <label><?=translate('branch')?> <span class="required">*</span></label>
                        <select name="branch_id" id="branch_id" class="form-control" required>
                            <option value=""><?=translate('select_branch')?></option>
                            <?php foreach($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo (isset($selected_branch_id) && $selected_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo html_escape($branch['school_name'] . ' (' . $branch['name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('emergency_type')?> <span class="required">*</span></label>
                                <select name="emergency_type_id" id="emergency_type_id" class="form-control" required>
                                    <option value=""><?=translate('select_emergency_type')?></option>
                                    <?php foreach($emergency_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>">
                                            <?php echo html_escape($type['name']); ?>
                                            (<?php echo ucfirst(translate($type['priority'])); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('severity')?> <span class="required">*</span></label>
                                <select name="severity" class="form-control" required>
                                    <option value=""><?=translate('select_severity')?></option>
                                    <option value="critical"><?=translate('critical')?></option>
                                    <option value="high"><?=translate('high')?></option>
                                    <option value="medium"><?=translate('medium')?></option>
                                    <option value="low"><?=translate('low')?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?=translate('title')?> <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="<?=translate('enter_incident_title')?>">
                    </div>
                    
                    <div class="form-group">
                        <label><?=translate('description')?> <span class="required">*</span></label>
                        <textarea name="description" class="form-control" rows="4" required placeholder="<?=translate('describe_the_incident_in_detail')?>"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?=translate('hostel')?></label>
                                <select name="hostel_id" id="hostel_id" class="form-control">
                                    <option value=""><?=translate('select_hostel')?></option>
                                    <?php if(!empty($hostels)): ?>
                                        <?php foreach($hostels as $hostel): ?>
                                            <option value="<?php echo $hostel['id']; ?>">
                                                <?php echo html_escape($hostel['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value=""><?=translate('no_hostels_found')?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?=translate('room')?></label>
                                <select name="room_id" id="room_id" class="form-control">
                                    <option value=""><?=translate('select_room')?></option>
                                    <?php if(!empty($rooms)): ?>
                                        <?php foreach($rooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>">
                                                <?php echo html_escape($room['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?=translate('affected_students')?></label>
                                <div class="student-checkbox-group" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background: #f9f9f9;">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="select_all_students"> 
                                            <strong><?=translate('select_all_students')?></strong>
                                        </label>
                                    </div>
                                    <hr style="margin: 5px 0;">
                                    <div id="students_list">
                                        <?php if(!empty($students)): ?>
                                            <?php foreach($students as $student): ?>
                                            <div class="checkbox" data-room-id="<?php echo $student['room_id']; ?>" data-hostel-id="<?php echo $student['hostel_id']; ?>">
                                                <label>
                                                    <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" class="student_checkbox">
                                                    <strong><?php echo html_escape($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo html_escape($student['register_no']); ?> | <?php echo html_escape($student['room_name']); ?></small>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-muted"><?=translate('no_students_in_hostel')?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <small class="text-muted"><?=translate('select_all_students_affected_by_this_incident')?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('location_details')?></label>
                                <input type="text" name="location_details" class="form-control" placeholder="<?=translate('specific_location_room_no_building')?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('estimated_duration')?></label>
                                <input type="text" name="estimated_duration" class="form-control" placeholder="<?=translate('e_g_2_hours_till_tomorrow')?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?=translate('action_taken')?></label>
                        <textarea name="action_taken" class="form-control" rows="2" placeholder="<?=translate('immediate_action_taken_if_any')?>"></textarea>
                    </div>
                    
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="send_sms" value="1" checked>
                            <i class="fa fa-envelope"></i> <?=translate('send_sms_notification_to_relevant_recipients')?>
                        </label>
                    </div>
                    
                    <div class="form-group mt-md">
                        <button type="submit" class="btn btn-primary" name="save_incident" value="1">
                            <i class="fas fa-save"></i> <?=translate('report_incident')?>
                        </button>
                        <a href="<?=base_url('hostel_emergency/incidents')?>" class="btn btn-default">
                            <i class="fas fa-times"></i> <?=translate('cancel')?>
                        </a>
                    </div>
                    
                </form>
            </div>
        </section>
    </div>
</div>

<style>
.student-checkbox-group {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background: #f9f9f9;
}
.student-checkbox-group .checkbox {
    margin: 8px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
}
.student-checkbox-group .checkbox:last-child {
    border-bottom: none;
}
</style>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var csrf_token = '<?=$this->security->get_csrf_hash();?>';

$(document).ready(function() {
    // Select All functionality
    $('#select_all_students').change(function() {
        $('.student_checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Update Select All when individual checkboxes change
    $('.student_checkbox').change(function() {
        if ($('.student_checkbox:checked').length === $('.student_checkbox').length) {
            $('#select_all_students').prop('checked', true);
        } else {
            $('#select_all_students').prop('checked', false);
        }
    });
    
    // Filter students by hostel and room
    $('#hostel_id').change(function() {
        var hostel_id = $(this).val();
        var room_dropdown = $('#room_id');
        
        if (hostel_id) {
            room_dropdown.html('<option value=""><?=translate('loading')?>...</option>');
            
            $.ajax({
                url: base_url + 'hostels/getRoomByHostel',
                type: 'POST',
                data: {
                    hostel_id: hostel_id,
                    '<?=$this->security->get_csrf_token_name();?>': csrf_token
                },
                success: function(data) {
                    if (data && data.trim() !== '') {
                        room_dropdown.html(data);
                    } else {
                        room_dropdown.html('<option value=""><?=translate('no_rooms_found')?></option>');
                    }
                    filterStudentsByRoom(room_dropdown.val());
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    room_dropdown.html('<option value=""><?=translate('error_loading_rooms')?></option>');
                }
            });
        } else {
            room_dropdown.html('<option value=""><?=translate('select_room')?></option>');
            <?php if(!empty($rooms)): ?>
                <?php foreach($rooms as $room): ?>
                    room_dropdown.append('<option value="<?php echo $room['id']; ?>"><?php echo html_escape($room['name']); ?></option>');
                <?php endforeach; ?>
            <?php endif; ?>
            showAllStudents();
        }
    });
    
    $('#room_id').change(function() {
        filterStudentsByRoom($(this).val());
    });
    
    function filterStudentsByRoom(room_id) {
        if (room_id) {
            $('.student-checkbox-group .checkbox').each(function() {
                var studentRoomId = $(this).data('room-id');
                if (studentRoomId == room_id) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            $('#select_all_students').prop('checked', false);
        } else {
            showAllStudents();
        }
    }
    
    function showAllStudents() {
        $('.student-checkbox-group .checkbox').show();
    }
    
    // Branch filter reload
    $('#branch_id').change(function() {
        var branch_id = $(this).val();
        if (branch_id) {
            window.location.href = base_url + 'hostel_emergency/report?branch_id=' + branch_id;
        }
    });
    
    // No AJAX form submission - let the form submit normally
    // The browser will handle redirect naturally
});
</script>