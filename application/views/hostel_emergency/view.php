<div class="row">
    <div class="col-md-8">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fa fa-exclamation-triangle"></i> <?=translate('incident_details')?> - <?php echo $incident['incident_code']; ?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php if(get_permission('hostel_emergency', 'can_edit') && $incident['status'] != 'resolved' && $incident['status'] != 'closed'): ?>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#updateStatusModal">
                            <i class="fas fa-pencil"></i> <?=translate('update_status')?>
                        </button>
                        <?php endif; ?>
                        <a href="<?=base_url('hostel_emergency/incidents')?>" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> <?=translate('back')?>
                        </a>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%"><?=translate('incident_code')?></th>
                        <td><span class="label label-info"><?php echo $incident['incident_code']; ?></span></td>
                    </tr>
                    <tr>
                        <th><?=translate('emergency_type')?></th>
                        <td>
                            <i class="fa <?php echo $incident['icon']; ?>"></i>
                            <?php echo html_escape($incident['emergency_type']); ?>
                            (<?php echo ucfirst(translate($incident['type_priority'])); ?>)
                        </td>
                    </tr>
                    <tr>
                        <th><?=translate('title')?></th>
                        <td><?php echo html_escape($incident['title']); ?></td>
                    </tr>
                    <tr>
                        <th><?=translate('description')?></th>
                        <td><?php echo nl2br(html_escape($incident['description'])); ?></td>
                    </tr>
                    <tr>
                        <th><?=translate('severity')?></th>
                        <td>
                            <?php
                            $severity_class = [
                                'critical' => 'danger',
                                'high' => 'warning',
                                'medium' => 'info',
                                'low' => 'success'
                            ];
                            ?>
                            <span class="label label-<?php echo $severity_class[$incident['severity']] ?? 'default'; ?>">
                                <?php echo ucfirst(translate($incident['severity'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?=translate('status')?></th>
                        <td>
                            <?php
                            $status_class = [
                                'reported' => 'danger',
                                'investigating' => 'warning',
                                'resolved' => 'success',
                                'closed' => 'default',
                                'false_alarm' => 'info'
                            ];
                            ?>
                            <span class="label label-<?php echo $status_class[$incident['status']] ?? 'default'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', translate($incident['status']))); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?=translate('location')?></th>
                        <td><?php echo html_escape($incident['location_details'] ?: $incident['room_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?=translate('reported_by')?></th>
                        <td><?php echo html_escape($incident['reported_staff_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?=translate('reported_at')?></th>
                        <td><?php echo date('d M Y H:i:s', strtotime($incident['reported_at'])); ?></td>
                    </tr>
                    <?php if($incident['resolved_at']): ?>
                    <tr>
                        <th><?=translate('resolved_at')?></th>
                        <td><?php echo date('d M Y H:i:s', strtotime($incident['resolved_at'])); ?></td>
                    </tr>
                    <tr>
                        <th><?=translate('resolved_by')?></th>
                        <td><?php echo html_escape($incident['resolved_staff_name']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <!-- ADD AFFECTED STUDENTS ROW HERE -->
                    <tr>
                        <th><?=translate('affected_students')?></th>
                        <td>
                            <?php if($incident['student_id']): ?>
                                <?php 
                                $student_ids = explode(',', $incident['student_id']);
                                $student_names = array();
                                foreach($student_ids as $sid) {
                                    $student = $this->db->select('first_name, last_name, register_no')
                                        ->where('id', trim($sid))
                                        ->get('student')
                                        ->row();
                                    if($student) {
                                        $student_names[] = $student->first_name . ' ' . $student->last_name . ' (' . $student->register_no . ')';
                                    }
                                }
                                echo implode('<br>', $student_names);
                                ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                    <!-- END OF AFFECTED STUDENTS ROW -->
                </table>
                
                <!-- Student Medical Information -->
                <?php if($incident['student_id'] && !empty($medical_info)): ?>
                <div class="panel panel-info mt-md">
                    <div class="panel-heading">
                        <h4 class="panel-title"><i class="fa fa-notes-medical"></i> <?=translate('student_medical_info')?></h4>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr>
                                <th width="30%"><?=translate('affected_students')?></th>
                                <td>
                                    <?php if($incident['student_id']): ?>
                                        <?php 
                                        $student_ids = explode(',', $incident['student_id']);
                                        $student_names = array();
                                        foreach($student_ids as $sid) {
                                            $student = $this->db->select('first_name, last_name, register_no')
                                                ->where('id', trim($sid))
                                                ->get('student')
                                                ->row();
                                            if($student) {
                                                $student_names[] = $student->first_name . ' ' . $student->last_name . ' (' . $student->register_no . ')';
                                            }
                                        }
                                        echo implode(', ', $student_names);
                                        ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?=translate('blood_group')?></th>
                                <td><?php echo $medical_info['blood_group'] ?: 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th><?=translate('allergies')?></th>
                                <td><?php echo nl2br(html_escape($medical_info['allergies'])) ?: 'None reported'; ?></td>
                            </tr>
                            <tr>
                                <th><?=translate('chronic_conditions')?></th>
                                <td><?php echo nl2br(html_escape($medical_info['chronic_conditions'])) ?: 'None reported'; ?></td>
                            </tr>
                            <tr>
                                <th><?=translate('medications')?></th>
                                <td><?php echo nl2br(html_escape($medical_info['medications'])) ?: 'None'; ?></td>
                            </tr>
                            <tr>
                                <th><?=translate('emergency_contact')?></th>
                                <td>
                                    <?php if($medical_info['emergency_contact_name']): ?>
                                        <strong><?php echo html_escape($medical_info['emergency_contact_name']); ?></strong><br>
                                        <?php echo html_escape($medical_info['emergency_contact_phone']); ?>
                                        (<?php echo html_escape($medical_info['emergency_contact_relation']); ?>)
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <div class="col-md-4">
        <!-- SMS Logs -->
        <section class="panel panel-warning">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fa fa-envelope"></i> <?=translate('sms_notifications')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php if(get_permission('hostel_emergency', 'can_edit')): ?>
                        <button type="button" class="btn btn-sm btn-primary" onclick="resendSMS(<?php echo $incident['id']; ?>)">
                            <i class="fa fa-repeat"></i> <?=translate('resend')?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <?php if(!empty($sms_logs)): ?>
                    <ul class="timeline" style="margin: 0; padding: 0; list-style: none;">
                        <?php foreach($sms_logs as $log): ?>
                        <li style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                            <i class="fa <?php echo $log['status'] == 'sent' ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>"></i>
                            <div style="margin-left: 25px;">
                                <strong><?php echo html_escape($log['recipient_name']); ?></strong>
                                <small class="text-muted">(<?php echo html_escape($log['recipient_type']); ?>)</small><br>
                                <small><i class="fa fa-clock-o"></i> <?php echo date('d M H:i', strtotime($log['sent_at'])); ?></small>
                                <p class="mt-sm"><?php echo nl2br(html_escape($log['message_sent'])); ?></p>
                                <span class="label label-<?php echo $log['status'] == 'sent' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($log['status']); ?>
                                </span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-center text-muted"><?=translate('no_sms_sent_yet')?></p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<!-- Resolution Timeline -->
<?php if($incident['resolved_at']): ?>
<div class="panel panel-success mt-md">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-check-circle"></i> Resolution Information</h4>
    </div>
    <div class="panel-body">
        <table class="table table-bordered">
            <tr>
                <th width="30%">Resolved Date</th>
                <td><?php echo date('d M Y H:i:s', strtotime($incident['resolved_at'])); ?></td>
            </tr>
            <tr>
                <th>Resolved By</th>
                <td><?php echo html_escape($incident['resolved_staff_name']); ?></td>
            </tr>
            <tr>
                <th>Action Taken</th>
                <td><?php echo nl2br(html_escape($incident['action_taken'])); ?></td>
            </tr>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Add this button in the header next to Update Status -->
<?php 
$critical_types = array('Fire Emergency', 'Missing Student', 'Security Threat', 'Natural Disaster');
if(in_array($incident['emergency_type'], $critical_types) && get_permission('hostel_emergency', 'can_edit')): 
?>
<button type="button" class="btn btn-danger" onclick="sendMassAlert(<?php echo $incident['id']; ?>)">
    <i class="fa fa-bullhorn"></i> Send Mass Alert
</button>
<?php endif; ?>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?=translate('update_incident_status')?></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?=translate('status')?></label>
                    <select id="new_status" class="form-control">
                        <option value="reported" <?php echo $incident['status'] == 'reported' ? 'selected' : ''; ?>>Reported</option>
                        <option value="investigating" <?php echo $incident['status'] == 'investigating' ? 'selected' : ''; ?>>Investigating</option>
                        <option value="resolved" <?php echo $incident['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $incident['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        <option value="false_alarm" <?php echo $incident['status'] == 'false_alarm' ? 'selected' : ''; ?>>False Alarm</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?=translate('resolution_notes')?></label>
                    <textarea id="resolution_notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('close')?></button>
                <button type="button" class="btn btn-primary" onclick="updateStatus(<?php echo $incident['id']; ?>)">
                    <?=translate('update')?>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var csrf_token = '<?=$this->security->get_csrf_hash();?>';

function updateStatus(id) {
    var status = $('#new_status').val();
    var notes = $('#resolution_notes').val();
    
    console.log("Updating status - ID:", id, "Status:", status, "Notes:", notes); // Debug
    
    $.ajax({
        url: base_url + 'hostel_emergency/update_status',
        type: 'POST',
        data: {
            id: id, 
            status: status, 
            resolution_notes: notes,
            '<?=$this->security->get_csrf_token_name();?>': csrf_token
        },
        dataType: 'json',
        success: function(response) {
            console.log("Response:", response); // Debug
            if(response.status == 'success') {
                alert(response.message || 'Status updated successfully');
                location.reload();
            } else {
                alert(response.message || 'Failed to update status');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", error);
            console.error("Response Text:", xhr.responseText);
            alert('Error updating status: ' + error);
        }
    });
}

function resendSMS(id) {
    if (confirm('Are you sure you want to resend SMS notification?')) {
        $.ajax({
            url: base_url + 'hostel_emergency/resend_sms',
            type: 'POST',
            data: {
                incident_id: id,
                '<?=$this->security->get_csrf_token_name();?>': csrf_token
            },
            dataType: 'json',
            success: function(response) {
                if(response.status == 'success') {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Error resending SMS');
            }
        });
    }
}
function sendMassAlert(id) {
    console.log("Sending mass alert for incident ID:", id); // Debug line
    
    if (confirm('⚠️ WARNING: This will send SMS to ALL parents and staff. Continue?')) {
        $.ajax({
            url: base_url + 'hostel_emergency/send_mass_alert',
            type: 'POST',
            data: {
                incident_id: id,
                '<?=$this->security->get_csrf_token_name();?>': csrf_token
            },
            dataType: 'json',
            success: function(response) {
                console.log("Response:", response); // Debug line
                if(response.status == 'success') {
                    alert(response.message);
                } else {
                    alert('Failed: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                console.error("Response Text:", xhr.responseText);
                alert('Error sending mass alert: ' + error);
            }
        });
    }
}
</script>