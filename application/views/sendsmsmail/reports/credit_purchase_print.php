<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- School Header -->
<div style="text-align: center; margin-bottom: 20px;">
    <div class="school-name"><?php echo $this->data['school_name']; ?></div>
    <h3>SMS CREDIT PURCHASE REPORT</h3>
    <p style="margin: 5px 0;">
        <strong>Branch:</strong> <?php echo $branch ? $branch->name : 'All Branches'; ?> | 
        <strong>Period:</strong> <?php echo date('d M Y', strtotime($date_from)); ?> - <?php echo date('d M Y', strtotime($date_to)); ?>
    </p>
    <p style="margin: 5px 0;">
        <strong>Generated:</strong> <?php echo date('d M Y H:i', strtotime($generated_at)); ?> | 
        <strong>By:</strong> <?php echo $generated_by; ?>
    </p>
</div>

<!-- Summary Box -->
<div class="summary-box">
    <h4 style="margin-top: 0; margin-bottom: 10px;">SUMMARY</h4>
    <table style="width: 100%; border: none; margin: 0;">
        <tr>
            <td><strong>Completed:</strong> <?php echo $summary->completed_transactions ?? 0; ?></td>
            <td><strong>Pending:</strong> <?php echo $summary->pending_transactions ?? 0; ?></td>
            <td><strong>Failed:</strong> <?php echo $summary->failed_transactions ?? 0; ?></td>
        </tr>
        <tr>
            <td><strong>Total Amount:</strong> KES <?php echo number_format($summary->total_amount ?? 0, 2); ?></td>
            <td><strong>Total SMS Units:</strong> <?php echo $summary->total_units ?? 0; ?></td>
            <td><strong>Total Transactions:</strong> <?php echo $summary->total_transactions ?? 0; ?></td>
        </tr>
    </table>
</div>

<!-- Transactions Table -->
<table>
    <thead>
        <tr>
            <th width="5%">#</th>
            <?php if (is_superadmin_loggedin() && $branch_id == 'all'): ?>
            <th width="10%">Branch</th>
            <?php endif; ?>
            <th width="15%">Date & Time</th>
            <th width="15%">Receipt No</th>
            <th width="15%">Phone</th>
            <th width="10%">Amount</th>
            <th width="10%">Units</th>
            <th width="10%">Status</th>
            <th width="10%">Credited</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total_amount = 0;
        $total_units = 0;
        $count = 1;
        ?>
        <?php foreach ($report_data as $tx): ?>
        <tr>
            <td class="text-center"><?php echo $count++; ?></td>
            <?php if (is_superadmin_loggedin() && $branch_id == 'all'): ?>
            <td><?php echo $tx->branch_name; ?></td>
            <?php endif; ?>
            <td><?php echo date('d/m/Y H:i', strtotime($tx->created_at)); ?></td>
            <td>
                <?php if ($tx->receipt_number): ?>
                    <?php echo $tx->receipt_number; ?>
                <?php else: ?>
                    <span class="label-warning">Pending</span>
                <?php endif; ?>
            </td>
            <td><?php echo $tx->user_phone; ?></td>
            <td class="text-right"><?php echo number_format($tx->amount, 2); ?></td>
            <td class="text-center"><?php echo $tx->sms_units; ?></td>
            <td>
                <?php 
                if ($tx->status == 'completed') {
                    $total_amount += $tx->amount;
                    $total_units += $tx->sms_units;
                    echo '<span class="label-success">Completed</span>';
                } elseif ($tx->status == 'pending') {
                    echo '<span class="label-warning">Pending</span>';
                } elseif ($tx->status == 'failed') {
                    echo '<span class="label-danger">Failed</span>';
                } else {
                    echo ucfirst($tx->status);
                }
                ?>
            </td>
            <td class="text-center">
                <?php if ($tx->is_credited): ?>
                    <span style="color: green;">✓ Yes</span>
                <?php else: ?>
                    <span style="color: red;">✗ No</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        
        <?php if (empty($report_data)): ?>
        <tr>
            <td colspan="<?php echo (is_superadmin_loggedin() && $branch_id == 'all' ? '9' : '8'); ?>" class="text-center">
                No transactions found for the selected period
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr style="font-weight: bold; background-color: #f2f2f2;">
            <td colspan="<?php echo (is_superadmin_loggedin() && $branch_id == 'all' ? '5' : '4'); ?>" class="text-right">GRAND TOTAL:</td>
            <td class="text-right">KES <?php echo number_format($total_amount, 2); ?></td>
            <td class="text-center"><?php echo $total_units; ?></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<!-- Footer -->
<div class="footer">
    <p>This is a computer-generated report. No signature is required.</p>
    <p>Generated on <?php echo date('d M Y H:i', strtotime($generated_at)); ?> by <?php echo $generated_by; ?></p>
</div>