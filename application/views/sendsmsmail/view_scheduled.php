<?php defined('BASEPATH') or exit('No direct script access allowed'); 
$message = isset($message_data) ? $message_data : [];
?>

<section class="panel">
    <header class="panel-heading">
        <div class="row">
            <div class="col-md-6">
                <h4 class="panel-title">
                    <i class="fas fa-eye"></i> <?= translate('view_scheduled_message') ?>
                    <small class="text-muted">#<?= htmlspecialchars($message['id'] ?? 'N/A') ?></small>
                </h4>
            </div>
            <div class="col-md-6 text-right">
                <a href="<?= base_url('sendsmsmail/scheduled') ?>" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left"></i> <?= translate('back_to_scheduled') ?>
                </a>
            </div>
        </div>
    </header>
    
    <div class="panel-body">
        <?php if (empty($message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= translate('message_not_found') ?>
            </div>
        <?php else: 
            $branch = $this->db->get_where('branch', array('id' => $message['branch_id']))->row();
            $status_badge = '';
            $status_text = '';
            
            switch($message['posting_status']) {
                case 0: // Processing
                    $status_badge = 'badge-info';
                    $status_text = 'Processing';
                    break;
                case 1: // Scheduled
                    $status_badge = 'badge-warning';
                    $status_text = 'Scheduled';
                    break;
                case 2: // Completed
                    $status_badge = 'badge-success';
                    $status_text = 'Completed';
                    break;
                case 3: // Failed
                    $status_badge = 'badge-danger';
                    $status_text = 'Failed';
                    break;
                case 4: // Partially Sent
                    $status_badge = 'badge-warning';
                    $status_text = 'Partially Sent';
                    break;
                case 5: // Cancelled
                    $status_badge = 'badge-secondary';
                    $status_text = 'Cancelled';
                    break;
                default:
                    $status_badge = 'badge-secondary';
                    $status_text = 'Unknown';
            }
            
            // Check for duplicates
            $duplicates = [];
            if (!empty($message['duplicate_hash'])) {
                $this->db->where('duplicate_hash', $message['duplicate_hash']);
                $this->db->where('id !=', $message['id']);
                $duplicates = $this->db->get('bulk_sms_email')->result_array();
            }
        ?>
        
        <!-- Message Overview -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="alert <?= $status_badge ?>">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="alert-heading">
                                <i class="fas fa-sms"></i> <?= htmlspecialchars($message['campaign_name']) ?>
                                <span class="badge <?= $status_badge ?> ml-2"><?= $status_text ?></span>
                            </h4>
                            <p class="mb-0">
                                <strong>Type:</strong> 
                                <span class="badge badge-primary">SMS</span>
                                <span class="badge badge-info ml-1"><?= $message['send_type'] === 'immediate' ? 'Immediate' : 'Scheduled' ?></span>
                                
                                <?php if (!empty($message['transaction_id'])): ?>
                                    <span class="badge badge-secondary ml-1">ID: <?= $message['transaction_id'] ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <?php if (get_permission('sendsmsmail', 'is_edit') && $message['posting_status'] == 1): ?>
                                <a href="<?= base_url('sendsmsmail/edit_scheduled/' . $message['id']) ?>" 
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> <?= translate('edit') ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (get_permission('sendsmsmail', 'is_delete')): ?>
                                <?php if ($message['posting_status'] == 1): ?>
                                    <!-- Cancel scheduled message -->
                                    <a href="<?= base_url('sendsmsmail/cancel_scheduled/' . $message['id']) ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('<?= translate('are_you_sure_cancel_scheduled') ?>')">
                                        <i class="fas fa-times"></i> <?= translate('cancel') ?>
                                    </a>
                                <?php else: ?>
                                    <!-- Delete completed/failed message -->
                                    <a href="<?= base_url('sendsmsmail/delete_message/' . $message['id']) ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this message record?')">
                                        <i class="fas fa-trash"></i> <?= translate('delete') ?>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Duplicate Warning -->
        <?php if (!empty($duplicates)): ?>
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Duplicate Messages Detected</h5>
                    <p>This message appears to be a duplicate of <?= count($duplicates) ?> other message(s):</p>
                    <ul class="mb-0">
                        <?php foreach ($duplicates as $dup): ?>
                        <li>
                            <a href="<?= base_url('sendsmsmail/view_scheduled/' . $dup['id']) ?>">
                                ID <?= $dup['id'] ?>: <?= htmlspecialchars($dup['campaign_name']) ?> 
                                (<?= date('d M Y H:i', strtotime($dup['schedule_time'])) ?>)
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main Details -->
        <div class="row">
            <!-- Left Column: Message Details -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-info-circle"></i> Message Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th width="40%">Campaign Name</th>
                                    <td><?= htmlspecialchars($message['campaign_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Message Type</th>
                                    <td>
                                        <span class="badge badge-primary">SMS</span>
                                        <span class="badge badge-info ml-1"><?= $message['send_type'] === 'immediate' ? 'Immediate' : 'Scheduled' ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>SMS Gateway</th>
                                    <td><?= htmlspecialchars($message['sms_gateway'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Branch</th>
                                    <td><?= $branch ? htmlspecialchars($branch->name) : 'N/A' ?></td>
                                </tr>
                                <tr>
                                    <th>Recipient Type</th>
                                    <td>
                                        <?php 
                                        switch($message['recipient_type']) {
                                            case 1: echo 'Group'; break;
                                            case 2: echo 'Individual'; break;
                                            case 3: echo 'Class'; break;
                                            default: echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total Recipients</th>
                                    <td>
                                        <strong><?= $message['total_thread'] ?></strong>
                                        <?php if ($message['successfully_sent'] > 0): ?>
                                            <span class="text-success ml-2">
                                                (Sent: <?= $message['successfully_sent'] ?>)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Credits Used</th>
                                    <td>
                                        <?php if ($message['credits_used'] > 0): ?>
                                            <span class="badge badge-primary"><?= $message['credits_used'] ?> credits</span>
                                        <?php else: ?>
                                            <span class="text-muted">Not deducted yet</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Timing Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-clock"></i> Timing Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th width="40%">Scheduled For</th>
                                    <td>
                                        <?= date('d M Y H:i A', strtotime($message['schedule_time'])) ?>
                                        <?php if ($message['posting_status'] == 1): 
                                            $scheduled_time = strtotime($message['schedule_time']);
                                            $now = time();
                                            $diff = $scheduled_time - $now;
                                            
                                            if ($diff < 0): ?>
                                                <br><small class="text-danger">Overdue by <?= abs(ceil($diff / 60)) ?> minutes</small>
                                            <?php elseif ($diff < 3600): ?>
                                                <br><small class="text-warning">In <?= ceil($diff / 60) ?> minutes</small>
                                            <?php elseif ($diff < 86400): ?>
                                                <br><small class="text-info">In <?= floor($diff / 3600) ?> hours</small>
                                            <?php else: ?>
                                                <br><small class="text-success">In <?= floor($diff / 86400) ?> days</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created On</th>
                                    <td><?= date('d M Y H:i A', strtotime($message['created_at'])) ?></td>
                                </tr>
                                <?php if (!empty($message['updated_at'])): ?>
                                <tr>
                                    <th>Last Updated</th>
                                    <td><?= date('d M Y H:i A', strtotime($message['updated_at'])) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($message['processed_at'])): ?>
                                <tr>
                                    <th>Processed At</th>
                                    <td><?= date('d M Y H:i A', strtotime($message['processed_at'])) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($message['processing_lock'] == 1 && !empty($message['processing_started'])): ?>
                                <tr class="table-warning">
                                    <th>Processing Lock</th>
                                    <td>
                                        <i class="fas fa-lock text-warning"></i> Locked since 
                                        <?= date('H:i:s', strtotime($message['processing_started'])) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Message Content -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-comment-alt"></i> Message Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="control-label"><strong>Message:</strong></label>
                            <div class="well" style="background: #f8f9fa; padding: 15px; border-radius: 5px; min-height: 200px;">
                                <?= nl2br(htmlspecialchars($message['message'])) ?>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="alert alert-light">
                                    <strong>Message Length:</strong><br>
                                    <?= strlen($message['message']) ?> characters
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-light">
                                    <strong>SMS Parts:</strong><br>
                                    <?php 
                                    $chars = strlen($message['message']);
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
                        </div>
                        
                        <?php if (!empty($message['duplicate_hash'])): ?>
                        <div class="alert alert-info mt-3">
                            <strong><i class="fas fa-fingerprint"></i> Duplicate Hash:</strong><br>
                            <code style="font-size: 11px;"><?= $message['duplicate_hash'] ?></code>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recipients Details -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-users"></i> Recipients Information</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $recipients_details = json_decode($message['recipients_details'] ?? '', true);
                        
                        if (!empty($recipients_details)): 
                        ?>
                            <?php if (isset($recipients_details['role'])): ?>
                            <div class="form-group">
                                <label class="control-label"><strong>Selected Roles:</strong></label>
                                <div class="well" style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
                                    <?php 
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
                                    echo implode(', ', $role_names);
                                    ?>
                                </div>
                            </div>
                            <?php elseif (isset($recipients_details['class'])): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label"><strong>Class:</strong></label>
                                        <div class="well" style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
                                            <?php 
                                            $class = $this->db->get_where('class', array('id' => $recipients_details['class']))->row();
                                            echo $class ? htmlspecialchars($class->name) : 'N/A';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label"><strong>Sections:</strong></label>
                                        <div class="well" style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
                                            <?php 
                                            $section_names = [];
                                            foreach ($recipients_details['sections'] as $section_id) {
                                                $section = $this->db->get_where('section', array('id' => $section_id))->row();
                                                if ($section) $section_names[] = $section->name;
                                            }
                                            echo implode(', ', $section_names);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No specific recipient details stored.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Additional Info -->
                        <?php if (!empty($message['additional'])): 
                            $additional = json_decode($message['additional'], true);
                            if (is_array($additional) && count($additional) > 0):
                        ?>
                            <div class="mt-3">
                                <button class="btn btn-info btn-sm" type="button" data-toggle="collapse" data-target="#recipientList">
                                    <i class="fas fa-list"></i> View <?= count($additional) ?> Recipients
                                </button>
                                
                                <div class="collapse mt-2" id="recipientList">
                                    <div class="card card-body">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Mobile</th>
                                                    <th>Email</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($additional as $recipient): ?>
                                                <tr>
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
                        <?php endif; endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Delivery Logs (if any) -->
        <?php 
        $this->db->where('message_id', $message['id']);
        $delivery_logs = $this->db->get('sms_email_delivery_logs')->result_array();
        
        if (!empty($delivery_logs)): 
        ?>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-history"></i> Delivery Logs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Recipient Contact</th>
                                        <th>Status</th>
                                        <th>Gateway Response</th>
                                        <th>Sent At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($delivery_logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['recipient_contact']) ?></td>
                                        <td>
                                            <?php if ($log['status'] == 'sent'): ?>
                                                <span class="badge badge-success">Sent</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars(substr($log['gateway_response'] ?? '', 0, 100)) ?></small>
                                        </td>
                                        <td><?= date('d M Y H:i', strtotime($log['sent_at'])) ?></td>
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
        
        <?php endif; ?>
    </div>
    
    <footer class="panel-footer">
        <div class="row">
            <div class="col-md-12 text-right">
                <a href="<?= base_url('sendsmsmail/scheduled') ?>" class="btn btn-default">
                    <i class="fas fa-arrow-left"></i> <?= translate('back_to_scheduled') ?>
                </a>
            </div>
        </div>
    </footer>
</section>

<script>
$(document).ready(function() {
    // Auto-refresh if message is still scheduled
    <?php if (isset($message['posting_status']) && $message['posting_status'] == 1): ?>
    setInterval(function() {
        $.ajax({
            url: '<?= base_url("sendsmsmail/check_message_status/" . ($message['id'] ?? 0)) ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status_changed) {
                    location.reload();
                }
            }
        });
    }, 30000); // 30 seconds
    <?php endif; ?>
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
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.table th {
    background-color: #f8f9fa;
}
</style>