<?php defined('BASEPATH') or exit('No direct script access allowed'); 
$message = isset($message_data) ? $message_data : [];
$widget = (is_superadmin_loggedin() ? 4 : 6);

// FIX: Check if recipients_details is already decoded
if (isset($message['recipients_details']) && is_string($message['recipients_details'])) {
    $recipients_details = json_decode($message['recipients_details'], true);
} else {
    $recipients_details = $message['recipients_details'] ?? [];
}

// FIX: Check if additional is already decoded
if (isset($message['additional']) && is_string($message['additional'])) {
    $original_recipients = json_decode($message['additional'], true);
} else {
    $original_recipients = $message['additional'] ?? [];
}

$original_schedule = $message['schedule_time'] ?? '';
?>

<section class="panel">
    <header class="panel-heading">
        <div class="row">
            <div class="col-md-6">
                <h4 class="panel-title">
                    <i class="fas fa-edit"></i> <?= translate('edit_scheduled_message') ?>
                    <small class="text-muted">#<?= htmlspecialchars($message['id'] ?? 'N/A') ?></small>
                </h4>
            </div>
            <div class="col-md-6 text-right">
                <a href="<?= base_url('sendsmsmail/view_scheduled/' . ($message['id'] ?? '')) ?>" class="btn btn-default btn-sm">
                    <i class="fas fa-eye"></i> <?= translate('view_details') ?>
                </a>
                <a href="<?= base_url('sendsmsmail/scheduled') ?>" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left"></i> <?= translate('back_to_scheduled') ?>
                </a>
            </div>
        </div>
    </header>
    
    <div class="panel-body">
        <?php if (empty($message) || ($message['posting_status'] ?? 0) != 1): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                <?= translate('cannot_edit_message') ?>
                <?php if (($message['posting_status'] ?? 0) == 2): ?>
                    <br><small>This message has already been sent.</small>
                <?php elseif (($message['posting_status'] ?? 0) == 3): ?>
                    <br><small>This message failed and cannot be edited.</small>
                <?php elseif (($message['posting_status'] ?? 0) == 5): ?>
                    <br><small>This message has been cancelled.</small>
                <?php endif; ?>
            </div>
        <?php else: ?>
        
        <!-- Status Warning -->
        <div class="alert alert-info mb-4">
            <div class="row">
                <div class="col-md-8">
                    <h5 class="alert-heading">
                        <i class="fas fa-info-circle"></i> Editing Scheduled Message
                    </h5>
                    <p class="mb-0">
                        <strong>Original Schedule:</strong> <?= date('d M Y H:i A', strtotime($original_schedule)) ?>
                        <br>
                        <strong>Recipients:</strong> <?= count($original_recipients) ?> recipients
                        <br>
                        <small class="text-muted">You can only change the schedule time and campaign name. Recipients cannot be modified.</small>
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge badge-warning" style="font-size: 14px; padding: 8px 12px;">
                        <i class="fas fa-clock"></i> Scheduled
                    </span>
                </div>
            </div>
        </div>
        
        <?php echo form_open('sendsmsmail/update_scheduled/' . $message['id'], array('class' => '', 'id' => 'editScheduledForm')); ?>
        <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
        <input type="hidden" name="message_type" value="sms">
        
        <!-- Campaign Details -->
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-bullhorn"></i> Campaign Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-<?= $widget ?> mb-sm">
                                <div class="form-group">
                                    <label class="control-label"><?= translate('campaign_name') ?> <span class="required">*</span></label>
                                    <input type="text" class="form-control" name="campaign_name" 
                                           value="<?= htmlspecialchars(set_value('campaign_name', $message['campaign_name'] ?? '')) ?>" 
                                           required />
                                    <span class="error"><?= form_error('campaign_name') ?></span>
                                </div>
                            </div>
                            
                            <?php if (is_superadmin_loggedin()): ?>
                            <div class="col-md-4 mb-sm">
                                <div class="form-group">
                                    <label class="control-label"><?= translate('branch') ?></label>
                                    <?php
                                    $arrayBranch = $this->app_lib->getSelectList('branch');
                                    $selected_branch = set_value('branch_id', $message['branch_id'] ?? '');
                                    echo form_dropdown("branch_id", $arrayBranch, $selected_branch, 
                                        "class='form-control' data-width='100%' id='branch_id'
                                        data-plugin-selectTwo  data-minimum-results-for-search='Infinity' disabled");
                                    ?>
                                    <small class="text-muted">Branch cannot be changed</small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-sm">
                                <div class="form-group">
                                    <label><?= translate('message') ?></label>
                                    <div class="well" style="background: #f8f9fa; padding: 15px; border-radius: 5px; min-height: 150px;">
                                        <?= nl2br(htmlspecialchars($message['message'] ?? '')) ?>
                                    </div>
                                    <small class="text-muted">Message content cannot be changed. Create a new message if you need different content.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scheduling Section -->
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-calendar-alt"></i> Reschedule Message</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Note:</strong> Changing the schedule time will create a new duplicate check. 
                            If an identical message is already scheduled for the new time, this will be blocked.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-sm">
                                <div class="form-group">
                                    <label class="control-label">New Schedule Date <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                                        <input type="text" class="form-control" name="schedule_date" id="schedule_date" 
                                               value="<?= set_value('schedule_date', date('Y-m-d', strtotime($original_schedule))) ?>" 
                                               data-plugin-datepicker required />
                                    </div>
                                    <span class="error"><?= form_error('schedule_date') ?></span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-sm">
                                <div class="form-group">
                                    <label class="control-label">New Schedule Time <span class="required">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="far fa-clock"></i></span>
                                        <input type="time" name="schedule_time" id="schedule_time" 
                                            class="form-control" 
                                            value="<?= set_value('schedule_time', date('H:i', strtotime($original_schedule))) ?>" 
                                            min="<?= date('H:i', strtotime('+5 minutes')) ?>"
                                            step="300" required /><!-- 5 minute steps -->
                                            
                                    </div>
                                    <span class="error"><?= form_error('schedule_time') ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-light">
                                    <strong>Current Schedule:</strong> <?= date('d M Y H:i A', strtotime($original_schedule)) ?>
                                    <br>
                                    <strong>New Schedule:</strong> <span id="newScheduleDisplay"><?= date('d M Y H:i A', strtotime($original_schedule)) ?></span>
                                    <br>
                                    <small class="text-muted">Schedule must be at least 5 minutes in the future.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recipients Information (Read-only) -->
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-users"></i> Recipients Information</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Display recipient type and details
                        $recipient_type_text = '';
                        $recipient_details_text = '';
                        
                        switch($message['recipient_type'] ?? 0) {
                            case 1: // Group
                                $recipient_type_text = 'Group';
                                if (!empty($recipients_details['role'])) {
                                    $role_names = [];
                                    foreach ($recipients_details['role'] as $role_id) {
                                        switch($role_id) {
                                            case 6: $role_names[] = 'Parents'; break;
                                            case 7: $role_names[] = 'Students'; break;
                                            default: 
                                                $role = $this->db->get_where('roles', array('id' => $role_id))->row();
                                                if ($role) $role_names[] = $role->name;
                                        }
                                    }
                                    $recipient_details_text = 'Roles: ' . implode(', ', $role_names);
                                }
                                break;
                                
                            case 2: // Individual
                                $recipient_type_text = 'Individual';
                                $recipient_details_text = count($original_recipients) . ' selected individuals';
                                break;
                                
                            case 3: // Class
                                $recipient_type_text = 'Class';
                                if (!empty($recipients_details['class'])) {
                                    $class = $this->db->get_where('class', array('id' => $recipients_details['class']))->row();
                                    $section_names = [];
                                    if (!empty($recipients_details['sections'])) {
                                        foreach ($recipients_details['sections'] as $section_id) {
                                            $section = $this->db->get_where('section', array('id' => $section_id))->row();
                                            if ($section) $section_names[] = $section->name;
                                        }
                                    }
                                    $recipient_details_text = 'Class: ' . ($class ? $class->name : 'N/A');
                                    if (!empty($section_names)) {
                                        $recipient_details_text .= ', Sections: ' . implode(', ', $section_names);
                                    }
                                }
                                break;
                        }
                        ?>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="alert alert-light">
                                    <strong>Recipient Type:</strong><br>
                                    <?= $recipient_type_text ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-light">
                                    <strong>Total Recipients:</strong><br>
                                    <?= $message['total_thread'] ?? 0 ?> recipients
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-light">
                                    <strong>Details:</strong><br>
                                    <small><?= $recipient_details_text ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recipients List (Collapsible) -->
                        <?php if (!empty($original_recipients)): ?>
                        <div class="mt-3">
                            <button class="btn btn-info btn-sm" type="button" data-toggle="collapse" data-target="#recipientList">
                                <i class="fas fa-list"></i> View All <?= count($original_recipients) ?> Recipients
                            </button>
                            
                            <div class="collapse mt-2" id="recipientList">
                                <div class="card card-body">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Mobile</th>
                                                <th>Email</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($original_recipients as $index => $recipient): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($recipient['name'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($recipient['mobileno'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($recipient['email'] ?? '') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Note:</strong> Recipients cannot be modified. If you need different recipients, 
                            please cancel this message and create a new one.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Credit Information -->
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-credit-card"></i> Credit Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-light">
                                    <strong>Message Length:</strong> <?= strlen($message['message'] ?? '') ?> characters
                                    <br>
                                    <strong>SMS Parts:</strong> 
                                    <?php 
                                    $chars = strlen($message['message'] ?? '');
                                    $is_unicode = false;
                                    for ($i = 0; $i < $chars; $i++) {
                                        if (ord($message['message'][$i]) > 127) {
                                            $is_unicode = true;
                                            break;
                                        }
                                    }
                                    $chars_per_sms = $is_unicode ? 70 : 160;
                                    $sms_parts = ceil($chars / $chars_per_sms);
                                    echo $sms_parts . ' part(s) (' . ($is_unicode ? 'Unicode' : 'GSM') . ')';
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-light">
                                    <strong>Estimated Cost:</strong> 
                                    <?php 
                                    $estimated_cost = $sms_parts * count($original_recipients) * ($is_unicode ? 2 : 1);
                                    echo '<span class="badge badge-primary" style="font-size: 16px;">' . $estimated_cost . ' credits</span>';
                                    ?>
                                    <br>
                                    <small class="text-muted">
                                        Formula: <?= $sms_parts ?> parts × <?= count($original_recipients) ?> recipients × <?= ($is_unicode ? 2 : 1) ?> credit/part
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="checkbox-replace">
                                        <label class="i-checks">
                                            <input type="checkbox" name="confirm_edit" id="confirm_edit" required>
                                            <i></i> I confirm that I want to reschedule this message
                                        </label>
                                        <span class="error"><?= form_error('confirm_edit') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?= base_url('sendsmsmail/view_scheduled/' . $message['id']) ?>" 
                                   class="btn btn-default">
                                    <i class="fas fa-times"></i> <?= translate('cancel') ?>
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-calendar-check"></i> <?= translate('update_schedule') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php echo form_close(); ?>
        
        <?php endif; ?>
    </div>
</section>

<script>
$(document).ready(function() {
    // Update new schedule display
    function updateScheduleDisplay() {
        var date = $('#schedule_date').val();
        var time = $('#schedule_time').val();
        
        if (date && time) {
            var dateObj = new Date(date + ' ' + time);
            var options = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            var formatted = dateObj.toLocaleDateString('en-US', options);
            $('#newScheduleDisplay').text(formatted);
            
            // Validate schedule is at least 5 minutes in future
            var now = new Date();
            var scheduleTime = new Date(date + ' ' + time);
            var fiveMinutesFromNow = new Date(now.getTime() + 5 * 60000);
            
            if (scheduleTime < fiveMinutesFromNow) {
                $('#newScheduleDisplay').html(formatted + ' <span class="text-danger">(Must be at least 5 minutes in future)</span>');
                $('#submitBtn').prop('disabled', true);
            } else {
                $('#newScheduleDisplay').text(formatted);
                $('#submitBtn').prop('disabled', false);
            }
        }
    }
    
    // Initialize date picker ONLY (remove timepicker)
    $('#schedule_date').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        startDate: new Date()
    }).on('changeDate', function() {
        updateScheduleDisplay();
    });
    
    // Use HTML5 time input instead of timepicker
    $('#schedule_time').on('change', function() {
        updateScheduleDisplay();
    });
    
    // Initial display update
    updateScheduleDisplay();
    
    // Form submission handler - SIMPLIFIED VERSION (no AJAX)
    $('#editScheduledForm').on('submit', function(e) {
        var submitBtn = $('#submitBtn');
        var originalText = submitBtn.html();
        
        // Disable button and show loading
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        
        // Get schedule time validation
        var schedule_date = $('#schedule_date').val();
        var schedule_time = $('#schedule_time').val();
        
        // Check schedule time
        var now = new Date();
        var scheduleTime = new Date(schedule_date + ' ' + schedule_time);
        var fiveMinutesFromNow = new Date(now.getTime() + 5 * 60000);
        
        if (scheduleTime < fiveMinutesFromNow) {
            alert('Schedule time must be at least 5 minutes in the future.');
            submitBtn.prop('disabled', false);
            submitBtn.html(originalText);
            e.preventDefault();
            return false;
        }
        
        // Check confirmation checkbox
        if (!$('#confirm_edit').is(':checked')) {
            alert('Please confirm that you want to reschedule this message.');
            submitBtn.prop('disabled', false);
            submitBtn.html(originalText);
            e.preventDefault();
            return false;
        }
        
        // If all validations pass, allow normal form submission
        // The form will submit via normal POST (not AJAX)
        // This avoids CORS issues
        return true;
    });
    
    // Enable/disable submit button based on confirmation
    $('#confirm_edit').on('change', function() {
        var schedule_date = $('#schedule_date').val();
        var schedule_time = $('#schedule_time').val();
        var now = new Date();
        var scheduleTime = new Date(schedule_date + ' ' + schedule_time);
        var fiveMinutesFromNow = new Date(now.getTime() + 5 * 60000);
        
        if ($(this).is(':checked') && scheduleTime >= fiveMinutesFromNow) {
            $('#submitBtn').prop('disabled', false);
        } else {
            $('#submitBtn').prop('disabled', true);
        }
    });
    
    // Also update when time input changes
    $('#schedule_time').on('input', function() {
        if ($('#confirm_edit').is(':checked')) {
            var schedule_date = $('#schedule_date').val();
            var schedule_time = $(this).val();
            var now = new Date();
            var scheduleTime = new Date(schedule_date + ' ' + schedule_time);
            var fiveMinutesFromNow = new Date(now.getTime() + 5 * 60000);
            
            if (scheduleTime >= fiveMinutesFromNow) {
                $('#submitBtn').prop('disabled', false);
            } else {
                $('#submitBtn').prop('disabled', true);
            }
        }
    });
});
</script>

<style>
.well {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 10px;
}

.card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 20px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 12px 20px;
}

.card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.alert-light {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
}

.input-group-addon {
    background-color: #f8f9fa;
    border: 1px solid #ced4da;
    border-right: none;
}

.form-control:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
}

.table-sm th, .table-sm td {
    padding: 8px;
    font-size: 13px;
}
</style>