<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title">
            <i class="fas fa-clipboard-list"></i> 
            <?= translate('delivery_logs') ?>
            <?php if (isset($message)): ?>
                - <?= $message['campaign_name'] ?>
            <?php endif; ?>
        </h4>
    </header>
    <div class="panel-body">
        <?php if (empty($delivery_logs)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?= translate('no_delivery_logs_found') ?>
            </div>
        <?php else: ?>
            <table class="table table-bordered table-hover table-condensed mb-none">
                <thead>
                    <tr>
                        <th><?= translate('recipient') ?></th>
                        <th><?= translate('contact') ?></th>
                        <th><?= translate('status') ?></th>
                        <th><?= translate('gateway_response') ?></th>
                        <th><?= translate('sent_at') ?></th>
                        <th><?= translate('branch') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($delivery_logs as $log): ?>
                        <tr>
                            <td>
                                <?php 
                                // Get recipient name based on type
                                // You might need to enhance this based on your recipient types
                                echo 'Recipient ID: ' . $log['recipient_id'];
                                ?>
                            </td>
                            <td><?= $log['recipient_contact'] ?></td>
                            <td>
                                <?php 
                                $badge_class = 'secondary';
                                if ($log['status'] == 'sent' || $log['status'] == 'delivered') {
                                    $badge_class = 'success';
                                } elseif ($log['status'] == 'failed' || $log['status'] == 'undelivered') {
                                    $badge_class = 'danger';
                                }
                                ?>
                                <span class="badge badge-<?= $badge_class ?>">
                                    <?= ucfirst($log['status']) ?>
                                </span>
                            </td>
                            <td>
                                <small><?= substr($log['gateway_response'], 0, 50) ?>...</small>
                            </td>
                            <td><?= date('d M Y h:i A', strtotime($log['sent_at'])) ?></td>
                            <td><?= $log['branch_name'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="<?= base_url('sendsmsmail/scheduled') ?>" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> <?= translate('back_to_scheduled') ?>
            </a>
        </div>
    </div>
</section>