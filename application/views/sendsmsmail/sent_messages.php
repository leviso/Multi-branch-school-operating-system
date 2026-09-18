<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title">
            <i class="fas fa-paper-plane"></i> Sent Messages
        </h4>
        
        <?php if (is_superadmin_loggedin()): ?>
        <!-- Superadmin Filter Controls -->
        <div class="panel-filter mt-3">
            <div class="row">
                <div class="col-md-8">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-default filter-btn active" data-filter="all">
                            <i class="fas fa-list"></i> All Messages
                        </button>
                        <button type="button" class="btn btn-sm btn-default filter-btn" data-filter="sms">
                            <i class="fas fa-comment"></i> SMS Only
                        </button>
                        <button type="button" class="btn btn-sm btn-default filter-btn" data-filter="email">
                            <i class="fas fa-envelope"></i> Email Only
                        </button>
                        <button type="button" class="btn btn-sm btn-default filter-btn" data-filter="scheduled">
                            <i class="fas fa-clock"></i> Scheduled
                        </button>
                        <button type="button" class="btn btn-sm btn-default filter-btn" data-filter="immediate">
                            <i class="fas fa-bolt"></i> Immediate
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <select class="form-control form-control-sm" id="branchFilter">
                            <option value="all">All Branches</option>
                            <?php foreach ($all_branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>" 
                                <?= ($selected_branch == $branch['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($branch['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </header>
    
    <div class="panel-body">
        <?php if (empty($sent_messages)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No sent messages found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="sentMessagesTable">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">Campaign Details</th>
                            <th width="10%">Sender</th>
                            <th width="20%">Message</th>
                            <th width="10%">Type</th>
                            <th width="10%">Recipients</th>
                            <th width="15%">Recipient Details</th>
                            <th width="10%">Timing</th>
                            <th width="5%">Status</th>
                            <th width="10%">Branch</th>
                            <?php if (is_superadmin_loggedin()): ?>
                            <th width="5%">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sent_messages as $index => $message): 
                            // Status colors
                            $status_class = '';
                            $status_text = '';
                            
                            switch($message['posting_status']) {
                                case 2:
                                    $status_class = 'success';
                                    $status_text = 'Completed';
                                    break;
                                case 3:
                                    $status_class = 'danger';
                                    $status_text = 'Failed';
                                    break;
                                case 4:
                                    $status_class = 'warning';
                                    $status_text = 'Partial';
                                    break;
                                default:
                                    $status_class = 'secondary';
                                    $status_text = 'Unknown';
                            }
                            
                            // Determine message type class
                            $message_type_class = ($message['message_type'] == 1) ? 'sms' : 'email';
                            $send_type_class = ($message['send_type'] == 'scheduled') ? 'scheduled' : 'immediate';
                            
                            // Get sender info
                            $sender_name = isset($sender_info[$message['created_by'] ?? 0]) ? 
                                          $sender_info[$message['created_by'] ?? 0]['name'] : 
                                          'System';
                            $sender_role = isset($sender_info[$message['created_by'] ?? 0]) ? 
                                          $sender_info[$message['created_by'] ?? 0]['role'] : 
                                          'Automated';
                        ?>
                            <tr class="message-row" 
                                data-type="<?= $message_type_class ?>"
                                data-send-type="<?= $send_type_class ?>"
                                data-branch="<?= $message['branch_id'] ?>">
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($message['campaign_name']) ?></strong>
                                    <?php if (!empty($message['sms_gateway'])): ?>
                                        <br><small class="text-muted">Gateway: <?= htmlspecialchars($message['sms_gateway']) ?></small>
                                    <?php endif; ?>
                                    <?php if ($message['message_type'] == 2 && !empty($message['email_subject'])): ?>
                                        <br><small class="text-muted">Subject: <?= htmlspecialchars($message['email_subject']) ?></small>
                                    <?php endif; ?>
                                    <?php if ($message['message_type'] == 1 && !empty($message['credits_used'])): ?>
                                        <br><small class="text-muted">Credits: <?= $message['credits_used'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="sender-info">
                                        <strong><?= htmlspecialchars($sender_name) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($sender_role) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="message-content">
                                        <!-- Collapsible Message Preview -->
                                        <div class="message-preview">
                                            <?= nl2br(htmlspecialchars(substr($message['message'], 0, 80))) ?>
                                            <?php if (strlen($message['message']) > 80): ?>
                                                ... 
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Full Message (Collapsible) -->
                                        <?php if (strlen($message['message']) > 80): ?>
                                        <div class="collapse full-message" id="fullMessage<?= $message['id'] ?>">
                                            <div class="well well-sm mt-2" style="background: #f8f9fa; padding: 10px; border-radius: 4px;">
                                                <?= nl2br(htmlspecialchars($message['message'])) ?>
                                            </div>
                                        </div>
                                        <a href="#fullMessage<?= $message['id'] ?>" 
                                           class="btn btn-xs btn-default mt-1 toggle-message" 
                                           data-toggle="collapse" 
                                           data-message-id="<?= $message['id'] ?>">
                                            <i class="fas fa-chevron-down"></i> Show Full
                                        </a>
                                        <?php endif; ?>
                                        
                                        <div class="message-meta">
                                            <small class="text-muted">
                                                Length: <?= strlen($message['message']) ?> chars
                                                <?php if ($message['message_type'] == 1): ?>
                                                    <?php 
                                                    $is_unicode = false;
                                                    for ($i = 0; $i < strlen($message['message']); $i++) {
                                                        if (ord($message['message'][$i]) > 127) {
                                                            $is_unicode = true;
                                                            break;
                                                        }
                                                    }
                                                    $chars_per_sms = $is_unicode ? 70 : 160;
                                                    $sms_parts = ceil(strlen($message['message']) / $chars_per_sms);
                                                    ?>
                                                    | Parts: <?= $sms_parts ?> (<?= $is_unicode ? 'Unicode' : 'GSM' ?>)
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($message['message_type'] == 1): ?>
                                        <span class="badge badge-primary">SMS</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Email</span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= ucfirst($message['send_type']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="recipient-count">
                                        <span class="badge badge-light" style="font-size: 14px;">
                                            <?= $message['successfully_sent'] ?>/<?= $message['total_thread'] ?>
                                        </span>
                                        <?php if ($message['posting_status'] == 4): ?>
                                            <br><small class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                <?= ($message['total_thread'] - $message['successfully_sent']) ?> failed
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="recipient-details">
                                        <!-- Recipient Summary -->
                                        <div class="recipient-summary">
                                            <?php if (isset($recipient_counts[$message['id']])): ?>
                                                <small>
                                                    <?= $recipient_counts[$message['id']]['sent'] ?> sent,
                                                    <?= $recipient_counts[$message['id']]['failed'] ?> failed
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Full Recipient List (Collapsible) -->
                                        <div class="collapse full-recipients" id="recipients<?= $message['id'] ?>">
                                            <div class="well well-sm mt-2" style="background: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto;">
                                                <?php if (isset($recipient_lists[$message['id']])): ?>
                                                    <table class="table table-sm table-borderless mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Contact</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($recipient_lists[$message['id']] as $recipient): ?>
                                                            <tr>
                                                                <td>
                                                                    <small><?= htmlspecialchars($recipient['contact']) ?></small>
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-<?= ($recipient['status'] == 'sent') ? 'success' : 'danger' ?> badge-sm">
                                                                        <?= ucfirst($recipient['status']) ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php else: ?>
                                                    <small class="text-muted">No recipient details available</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Toggle Button -->
                                        <?php if (isset($recipient_lists[$message['id']]) && count($recipient_lists[$message['id']]) > 0): ?>
                                        <div class="btn-group btn-group-xs mt-1" role="group">
                                            <a href="#recipients<?= $message['id'] ?>" 
                                               class="btn btn-default toggle-recipients" 
                                               data-toggle="collapse" 
                                               data-message-id="<?= $message['id'] ?>">
                                                <i class="fas fa-users"></i> Details
                                            </a>
                                            <a href="<?= base_url('sendsmsmail/view_delivery_logs/' . $message['id']) ?>" 
                                               class="btn btn-info" target="_blank" title="View Full Logs">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($message['schedule_time'])): ?>
                                        <div class="timing-info">
                                            <small>
                                                <strong>Scheduled:</strong><br>
                                                <?= date('d M Y', strtotime($message['schedule_time'])) ?><br>
                                                <?= date('h:i A', strtotime($message['schedule_time'])) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    <div class="sent-time mt-1">
                                        <small>
                                            <strong>Sent:</strong><br>
                                            <?= date('d M Y', strtotime($message['updated_at'])) ?><br>
                                            <?= date('h:i A', strtotime($message['updated_at'])) ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $status_class ?>" style="font-size: 12px; padding: 5px 8px;">
                                        <?= $status_text ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="branch-info">
                                        <strong><?= isset($branch_names[$message['branch_id']]) ? htmlspecialchars($branch_names[$message['branch_id']]) : 'N/A' ?></strong>
                                        <?php if (isset($branch_names[$message['branch_id']])): ?>
                                            <br><small class="text-muted">ID: <?= $message['branch_id'] ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td>
                                    <div class="btn-group btn-group-xs">
                                        <a href="<?= base_url('sendsmsmail/view_scheduled/' . $message['id']) ?>" 
                                           class="btn btn-default" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($message['posting_status'] == 1): // Only for scheduled ?>
                                        <a href="<?= base_url('sendsmsmail/edit_scheduled/' . $message['id']) ?>" 
                                           class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Filter Summary -->
            <div class="filter-summary mt-3">
                <div class="alert alert-light">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Showing:</strong> 
                            <span id="filterCount"><?= count($sent_messages) ?></span> messages
                        </div>
                        <div class="col-md-4">
                            <strong>SMS:</strong> 
                            <span id="smsCount"><?= $stats['sms_count'] ?? 0 ?></span> | 
                            <strong>Email:</strong> 
                            <span id="emailCount"><?= $stats['email_count'] ?? 0 ?></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Scheduled:</strong> 
                            <span id="scheduledCount"><?= $stats['scheduled_count'] ?? 0 ?></span> | 
                            <strong>Immediate:</strong> 
                            <span id="immediateCount"><?= $stats['immediate_count'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#sentMessagesTable').DataTable({
        "pageLength": 25,
        "order": [[0, "asc"]],
        "responsive": true,
        "columnDefs": [
            { "orderable": true, "targets": [0, 1, 3, 4, 5, 7, 8] },
            { "orderable": false, "targets": [2, 6, 9, 10] }
        ],
        "language": {
            "search": "Search messages:",
            "lengthMenu": "Show _MENU_ messages"
        }
    });
    
    // Toggle message content
    $('.toggle-message').on('click', function() {
        var $icon = $(this).find('i');
        if ($icon.hasClass('fa-chevron-down')) {
            $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $(this).html('<i class="fas fa-chevron-up"></i> Show Less');
        } else {
            $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $(this).html('<i class="fas fa-chevron-down"></i> Show Full');
        }
    });
    
    // Toggle recipient details
    $('.toggle-recipients').on('click', function() {
        var $icon = $(this).find('i');
        if ($icon.hasClass('fa-users')) {
            $icon.removeClass('fa-users').addClass('fa-times');
            $(this).html('<i class="fas fa-times"></i> Close');
        } else {
            $icon.removeClass('fa-times').addClass('fa-users');
            $(this).html('<i class="fas fa-users"></i> Details');
        }
    });
    
    <?php if (is_superadmin_loggedin()): ?>
    // Filter buttons
    $('.filter-btn').on('click', function() {
        var filter = $(this).data('filter');
        
        // Update button states
        $('.filter-btn').removeClass('active btn-primary').addClass('btn-default');
        $(this).removeClass('btn-default').addClass('active btn-primary');
        
        // Filter the table
        if (filter === 'all') {
            table.columns().search('').draw();
            $('.message-row').show();
        } else if (filter === 'sms' || filter === 'email') {
            table.column(4).search(filter === 'sms' ? 'SMS' : 'Email').draw();
        } else if (filter === 'scheduled' || filter === 'immediate') {
            var searchTerm = filter === 'scheduled' ? 'Scheduled' : 'Immediate';
            // This would need a column for send_type, or use custom filtering
            $('.message-row').each(function() {
                var sendType = $(this).data('send-type');
                $(this).toggle(sendType === filter);
            });
            table.draw(false); // Redraw without losing current filters
        }
        
        updateFilterSummary();
    });
    
    // Branch filter
    $('#branchFilter').on('change', function() {
        var branchId = $(this).val();
        
        if (branchId === 'all') {
            table.column(9).search('').draw();
        } else {
            table.column(9).search(branchId).draw();
        }
        
        updateFilterSummary();
    });
    
    // Update filter summary
    function updateFilterSummary() {
        var visibleRows = $('.message-row:visible').length;
        var smsCount = $('.message-row:visible[data-type="sms"]').length;
        var emailCount = $('.message-row:visible[data-type="email"]').length;
        var scheduledCount = $('.message-row:visible[data-send-type="scheduled"]').length;
        var immediateCount = $('.message-row:visible[data-send-type="immediate"]').length;
        
        $('#filterCount').text(visibleRows);
        $('#smsCount').text(smsCount);
        $('#emailCount').text(emailCount);
        $('#scheduledCount').text(scheduledCount);
        $('#immediateCount').text(immediateCount);
    }
    
    // Initialize filter summary
    updateFilterSummary();
    <?php endif; ?>
});
</script>

<style>
.panel-filter {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.filter-btn {
    transition: all 0.3s ease;
}

.filter-btn.active {
    font-weight: 600;
}

.message-content .well,
.recipient-details .well {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    margin-top: 5px;
}

.toggle-message,
.toggle-recipients {
    font-size: 11px;
    padding: 2px 6px;
}

.badge-sm {
    font-size: 9px;
    padding: 2px 5px;
}

.timing-info, .sent-time {
    font-size: 11px;
    line-height: 1.2;
}

.btn-group-xs > .btn {
    padding: 1px 5px;
    font-size: 10px;
    line-height: 1.2;
}

.filter-summary .alert {
    font-size: 12px;
    padding: 8px 12px;
}

.dataTables_filter input {
    margin-left: 5px;
}

.table-sm {
    font-size: 10px;
}

.table-sm th, .table-sm td {
    padding: 4px;
}
</style>