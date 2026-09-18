<!-- application/views/online_admission/communication_log.php -->
<?php if (empty($communications)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No communication records found.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Direction</th>
                    <th>Message</th>
                    <th>Recipient</th>
                    <th>Status</th>
                    <th>Sent By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($communications as $comm): ?>
                <tr>
                    <td><?= date('d M Y H:i', strtotime($comm['created_at'])) ?></td>
                    <td>
                        <?php 
                        switch($comm['type']) {
                            case 'sms': echo '<span class="badge badge-info">SMS</span>'; break;
                            case 'email': echo '<span class="badge badge-primary">Email</span>'; break;
                            case 'note': echo '<span class="badge badge-secondary">Note</span>'; break;
                            default: echo '<span class="badge badge-light">' . $comm['type'] . '</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        switch($comm['direction']) {
                            case 'to_parent': echo '<span class="badge badge-success">To Parent</span>'; break;
                            case 'from_parent': echo '<span class="badge badge-warning">From Parent</span>'; break;
                            case 'internal': echo '<span class="badge badge-secondary">Internal</span>'; break;
                            default: echo '<span class="badge badge-light">' . $comm['direction'] . '</span>';
                        }
                        ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($comm['message'])) ?></td>
                    <td><?= $comm['recipient'] ?: 'N/A' ?></td>
                    <td>
                        <?php 
                        switch($comm['status']) {
                            case 'sent': echo '<span class="badge badge-success">Sent</span>'; break;
                            case 'failed': echo '<span class="badge badge-danger">Failed</span>'; break;
                            case 'pending': echo '<span class="badge badge-warning">Pending</span>'; break;
                            default: echo '<span class="badge badge-light">' . $comm['status'] . '</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        $sent_by = $this->db->select('name')->where('id', $comm['sent_by'])->get('staff')->row();
                        echo $sent_by ? $sent_by->name : 'System';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>