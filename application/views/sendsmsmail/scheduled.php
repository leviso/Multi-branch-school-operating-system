<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title"><i class="fas fa-calendar-alt"></i> <?= translate('scheduled_messages') ?></h4>
    </header>
    <div class="panel-body">
        <?php if (empty($scheduled_messages)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?= translate('no_scheduled_messages') ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-condensed mb-none">
                    <thead>
                        <tr>
                            <th><?= translate('campaign_name') ?></th>
                            <th><?= translate('type') ?></th>
                            <th><?= translate('recipients') ?></th>
                            <th><?= translate('scheduled_for') ?></th>
                            <th><?= translate('branch') ?></th>
                            <th><?= translate('created_on') ?></th>
                            <th><?= translate('status') ?></th>
                            <th><?= translate('last_updated') ?></th>
                            <th class="no-export"><?= translate('action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scheduled_messages as $message): 
                            $branch = $this->db->get_where('branch', array('id' => $message['branch_id']))->row();
                            
                            // Determine status
                            $status_badge = '';
                            $status_text = '';
                            
                            switch($message['posting_status']) {
                                case 1: // Scheduled
                                    $status_badge = 'badge-warning';
                                    $status_text = 'Scheduled';
                                    break;
                                case 0: // Processing
                                    $status_badge = 'badge-info';
                                    $status_text = 'Processing';
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
                                default:
                                    $status_badge = 'badge-secondary';
                                    $status_text = 'Unknown';
                            }
                            
                            // Check if this is a duplicate based on hash
                            $is_duplicate = false;
                            if (!empty($message['duplicate_hash'])) {
                                // Count how many messages have this same hash
                                $this->db->where('duplicate_hash', $message['duplicate_hash']);
                                $this->db->where('id !=', $message['id']);
                                $duplicate_count = $this->db->count_all_results('bulk_sms_email');
                                $is_duplicate = ($duplicate_count > 0);
                            }
                        ?>
                            <tr <?= $is_duplicate ? 'class="duplicate-row"' : '' ?>>
                                <td>
                                    <?= htmlspecialchars($message['campaign_name']) ?>
                                    <?php if ($is_duplicate): ?>
                                        <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Possible duplicate</small>
                                    <?php endif; ?>
                                    <?php if (!empty($message['transaction_id'])): ?>
                                        <br><small class="text-muted">ID: <?= $message['transaction_id'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($message['message_type'] == 1): ?>
                                        <span class="badge badge-primary">SMS</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Email</span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= ($message['send_type'] == 'scheduled') ? 'Scheduled' : 'Immediate' ?>
                                    </small>
                                </td>
                                <td>
                                    <?= $message['total_thread'] ?> recipients
                                    <?php if ($message['successfully_sent'] > 0): ?>
                                        <br><small class="text-success">Sent: <?= $message['successfully_sent'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d M Y h:i A', strtotime($message['schedule_time'])) ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php
                                        $scheduled_time = strtotime($message['schedule_time']);
                                        $now = time();
                                        $diff = $scheduled_time - $now;
                                        
                                        if ($message['posting_status'] == 2 || $message['posting_status'] == 3 || $message['posting_status'] == 4) {
                                            // Already processed
                                            echo '<span class="text-info">Processed</span>';
                                        } elseif ($diff < 0) {
                                            if ($message['posting_status'] == 1) {
                                                echo '<span class="text-danger">Overdue by ' . abs(ceil($diff / 60)) . ' minutes</span>';
                                            } else {
                                                echo '<span class="text-muted">Overdue</span>';
                                            }
                                        } else {
                                            if ($diff < 3600) {
                                                echo 'In ' . ceil($diff / 60) . ' minutes';
                                            } elseif ($diff < 86400) {
                                                echo 'In ' . floor($diff / 3600) . ' hours';
                                            } else {
                                                echo 'In ' . floor($diff / 86400) . ' days';
                                            }
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td><?= $branch ? htmlspecialchars($branch->name) : 'N/A' ?></td>
                                <td><?= date('d M Y', strtotime($message['created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $status_badge ?>"><?= $status_text ?></span>
                                    <?php if ($message['processing_lock'] == 1): ?>
                                        <br><small class="text-warning"><i class="fas fa-lock"></i> Locked</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($message['updated_at'])): ?>
                                        <?= date('d M Y h:i A', strtotime($message['updated_at'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td class="no-export">
                                    <?php if (get_permission('sendsmsmail', 'is_view')): ?>
                                        <a href="<?= base_url('sendsmsmail/view_scheduled/' . $message['id']) ?>" 
                                           class="btn btn-default btn-xs mb-xs" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (get_permission('sendsmsmail', 'is_delete')): ?>
                                        <?php if ($message['posting_status'] == 1): ?>
                                            <!-- Can cancel scheduled messages that are still pending -->
                                            <a href="<?= base_url('sendsmsmail/cancel_scheduled/' . $message['id']) ?>" 
                                               class="btn btn-danger btn-xs mb-xs" 
                                               onclick="return confirm('<?= translate('are_you_sure_cancel_scheduled') ?>')">
                                                <i class="fas fa-times"></i> <?= translate('cancel') ?>
                                            </a>
                                        <?php elseif (in_array($message['posting_status'], [2, 3, 4])): ?>
                                            <!-- Can delete completed/failed messages -->
                                            <a href="<?= base_url('sendsmsmail/delete_message/' . $message['id']) ?>" 
                                               class="btn btn-danger btn-xs mb-xs" 
                                               onclick="return confirm('Are you sure you want to delete this message record?')">
                                                <i class="fas fa-trash"></i> <?= translate('delete') ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($message['posting_status'] == 3 && get_permission('sendsmsmail', 'is_add')): ?>
                                        <!-- Can retry failed messages -->
                                        <a href="<?= base_url('sendsmsmail/retry_scheduled/' . $message['id']) ?>" 
                                           class="btn btn-warning btn-xs mb-xs" 
                                           onclick="return confirm('Retry sending this failed message?')">
                                            <i class="fas fa-redo"></i> Retry
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Add summary information -->
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="alert alert-info">
                            <strong>Total Scheduled:</strong> <?= count($scheduled_messages) ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning">
                            <strong>Pending:</strong> 
                            <?= count(array_filter($scheduled_messages, function($m) { 
                                return $m['posting_status'] == 1; 
                            })) ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-success">
                            <strong>Completed:</strong> 
                            <?= count(array_filter($scheduled_messages, function($m) { 
                                return $m['posting_status'] == 2; 
                            })) ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-danger">
                            <strong>Failed:</strong> 
                            <?= count(array_filter($scheduled_messages, function($m) { 
                                return in_array($m['posting_status'], [3, 4]); 
                            })) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.duplicate-row {
    background-color: #fff3cd !important;
    border-left: 3px solid #ffc107;
}

.duplicate-row:hover {
    background-color: #ffeaa7 !important;
}
</style>

<script>
$(document).ready(function() {
    // Auto-refresh scheduled messages every 30 seconds
    setInterval(function() {
        $.ajax({
            url: '<?= base_url("sendsmsmail/check_scheduled_status") ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.has_updates) {
                    // Show notification and reload
                    showNotification('info', 'Scheduled messages updated', 'Refreshing list...');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            },
            error: function() {
                // Silently fail
            }
        });
    }, 30000); // 30 seconds
    
    // Add click handler for duplicate rows
    $('.duplicate-row').on('click', function() {
        var messageId = $(this).find('td:first').data('message-id');
        if (messageId) {
            // Show duplicate details
            showDuplicateDetails(messageId);
        }
    });
});

function showNotification(type, title, message) {
    // Simple notification function
    var alertClass = '';
    var icon = '';
    
    switch(type) {
        case 'info':
            alertClass = 'alert-info';
            icon = 'fa-info-circle';
            break;
        case 'success':
            alertClass = 'alert-success';
            icon = 'fa-check-circle';
            break;
        case 'error':
            alertClass = 'alert-danger';
            icon = 'fa-exclamation-triangle';
            break;
    }
    
    // Remove existing notifications
    $('.auto-notification').remove();
    
    // Create new notification
    var notification = $('<div class="alert ' + alertClass + ' alert-dismissible auto-notification" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa ' + icon + '"></i> ' + title + '</strong><br>' + message +
                        '</div>');
    
    $('body').append(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        notification.alert('close');
    }, 5000);
}

function showDuplicateDetails(messageId) {
    $.ajax({
        url: '<?= base_url("sendsmsmail/get_duplicate_details/") ?>' + messageId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var modalHtml = '<div class="modal fade" id="duplicateModal" tabindex="-1" role="dialog">' +
                               '<div class="modal-dialog modal-lg" role="document">' +
                               '<div class="modal-content">' +
                               '<div class="modal-header">' +
                               '<h4 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Duplicate Message Details</h4>' +
                               '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                               '</div>' +
                               '<div class="modal-body">' +
                               '<div class="alert alert-warning">' +
                               '<i class="fas fa-info-circle"></i> This message appears to be a duplicate of other messages.' +
                               '</div>' +
                               '<table class="table table-bordered">' +
                               '<thead><tr><th>ID</th><th>Campaign Name</th><th>Scheduled Time</th><th>Status</th><th>Action</th></tr></thead>' +
                               '<tbody>';
                
                $.each(response.duplicates, function(index, duplicate) {
                    modalHtml += '<tr>' +
                                '<td>' + duplicate.id + '</td>' +
                                '<td>' + duplicate.campaign_name + '</td>' +
                                '<td>' + duplicate.schedule_time + '</td>' +
                                '<td>' + getStatusBadge(duplicate.posting_status) + '</td>' +
                                '<td><a href="<?= base_url("sendsmsmail/view_scheduled/") ?>' + duplicate.id + '" class="btn btn-xs btn-default">View</a></td>' +
                                '</tr>';
                });
                
                modalHtml += '</tbody></table></div></div></div></div>';
                
                // Remove existing modal
                $('#duplicateModal').remove();
                
                // Add new modal
                $('body').append(modalHtml);
                
                // Show modal
                $('#duplicateModal').modal('show');
            }
        }
    });
}

function getStatusBadge(status) {
    switch(status) {
        case 1: return '<span class="badge badge-warning">Scheduled</span>';
        case 2: return '<span class="badge badge-success">Completed</span>';
        case 3: return '<span class="badge badge-danger">Failed</span>';
        case 4: return '<span class="badge badge-warning">Partial</span>';
        default: return '<span class="badge badge-secondary">Unknown</span>';
    }
}
</script>