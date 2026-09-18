<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel <?php echo (is_superadmin_loggedin()) ? 'panel-custom' : 'panel-default'; ?>">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-file-text-o"></i> SMS Credit Purchase Report
                </div>
                <div class="panel-options">
                    <a href="#" onclick="window.print();" class="btn btn-default btn-sm">
                        <i class="fa fa-print"></i> Print
                    </a>
                    <a href="<?php echo base_url('sendsmsmail/credit_purchase_report?output=pdf&date_from=' . $date_from . '&date_to=' . $date_to . '&branch_id=' . $branch_id); ?>" class="btn btn-danger btn-sm">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </a>
                    <a href="<?php echo base_url('sendsmsmail/credit_purchase_report?output=excel&date_from=' . $date_from . '&date_to=' . $date_to . '&branch_id=' . $branch_id); ?>" class="btn btn-success btn-sm">
                        <i class="fa fa-file-excel-o"></i> Excel
                    </a>
                </div>
            </div>
            
            <div class="panel-body">
                <!-- Filter Form -->
                <form method="get" action="<?php echo base_url('sendsmsmail/credit_purchase_report'); ?>" class="form-horizontal mb-lg">
                    <div class="row">
                        <?php if (is_superadmin_loggedin()): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label col-md-4">Branch</label>
                                <div class="col-md-8">
                                    <select class="form-control" name="branch_id" id="branch_filter">
                                        <option value="all">All Branches</option>
                                        <?php foreach ($all_branches as $b): ?>
                                        <option value="<?php echo $b['id']; ?>" <?php echo ($branch_id == $b['id']) ? 'selected' : ''; ?>>
                                            <?php echo $b['name']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label col-md-4">From</label>
                                <div class="col-md-8">
                                    <input type="text" class="form-control datepicker" name="date_from" value="<?php echo $date_from; ?>" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label col-md-4">To</label>
                                <div class="col-md-8">
                                    <input type="text" class="form-control datepicker" name="date_to" value="<?php echo $date_to; ?>" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <div class="col-md-offset-4 col-md-8">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-filter"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Report Header for Printing -->
                <div class="report-header text-center" style="display: none; margin-bottom: 20px;">
                    <h2><?php echo isset($school_name) ? $school_name : 'School Management System'; ?></h2>
                    
                    <h4>SMS Credit Purchase Report</h4>
                    <p>
                        <strong>Branch:</strong> <?php echo $branch ? $branch->name : 'All Branches'; ?> | 
                        <strong>Period:</strong> <?php echo date('d M Y', strtotime($date_from)); ?> - <?php echo date('d M Y', strtotime($date_to)); ?> |
                        <strong>Generated:</strong> <?php echo date('d M Y H:i', strtotime($generated_at)); ?>
                    </p>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-lg">
                    <div class="col-md-3">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Completed</span>
                                <span class="info-box-number"><?php echo $summary->completed_transactions ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-yellow">
                            <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Pending</span>
                                <span class="info-box-number"><?php echo $summary->pending_transactions ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-red">
                            <span class="info-box-icon"><i class="fa fa-times-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Failed</span>
                                <span class="info-box-number"><?php echo $summary->failed_transactions ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-aqua">
                            <span class="info-box-icon"><i class="fa fa-credit-card"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Amount</span>
                                <span class="info-box-number">KES <?php echo number_format($summary->total_amount ?? 0, 2); ?></span>
                                <span class="info-box-text">Units: <?php echo $summary->total_units ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="creditReportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin() && $branch_id == 'all'): ?>
                                <th>Branch</th>
                                <?php endif; ?>
                                <th>Date & Time</th>
                                <th>Receipt No</th>
                                <th>Phone</th>
                                <th>Amount (KES)</th>
                                <th>SMS Units</th>
                                <th>Status</th>
                                <th>Credited</th>
                                <?php if ($report_type == 'detailed'): ?>
                                <th>Checkout ID</th>
                                <?php endif; ?>
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
                                <td><?php echo $count++; ?></td>
                                <?php if (is_superadmin_loggedin() && $branch_id == 'all'): ?>
                                <td><?php echo $tx->branch_name; ?></td>
                                <?php endif; ?>
                                <td><?php echo date('d/m/Y H:i', strtotime($tx->created_at)); ?></td>
                                <td>
                                    <?php if ($tx->receipt_number): ?>
                                        <span class="label label-success"><?php echo $tx->receipt_number; ?></span>
                                    <?php else: ?>
                                        <span class="label label-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $tx->user_phone; ?></td>
                                <td class="text-right"><?php echo number_format($tx->amount, 2); ?></td>
                                <td class="text-center"><?php echo $tx->sms_units; ?></td>
                                <td>
                                    <?php 
                                    $status_class = 'default';
                                    if ($tx->status == 'completed') {
                                        $status_class = 'success';
                                        $total_amount += $tx->amount;
                                        $total_units += $tx->sms_units;
                                    } elseif ($tx->status == 'pending') {
                                        $status_class = 'warning';
                                    } elseif ($tx->status == 'failed') {
                                        $status_class = 'danger';
                                    }
                                    ?>
                                    <span class="label label-<?php echo $status_class; ?>"><?php echo ucfirst($tx->status); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($tx->is_credited): ?>
                                        <i class="fa fa-check-circle text-success"></i>
                                    <?php else: ?>
                                        <i class="fa fa-times-circle text-danger"></i>
                                    <?php endif; ?>
                                </td>
                                <?php if ($report_type == 'detailed'): ?>
                                <td><small><?php echo $tx->checkout_request_id; ?></small></td>
                                <?php endif; ?>
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
                            <tr>
                                <th <?php echo (is_superadmin_loggedin() && $branch_id == 'all') ? 'colspan="6"' : 'colspan="5"'; ?> class="text-right">Total:</th>
                                <th class="text-right">KES <?php echo number_format($total_amount, 2); ?></th>
                                <th class="text-center"><?php echo $total_units; ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <!-- Report Footer for Printing -->
                <div class="report-footer" style="display: none; margin-top: 30px;">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Generated By:</strong> <?php echo $generated_by; ?></p>
                        </div>
                        <div class="col-md-6 text-right">
                            <p><strong>Generated On:</strong> <?php echo date('d M Y H:i', strtotime($generated_at)); ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <p class="text-muted"><em>This is a computer-generated report. No signature is required.</em></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style type="text/css" media="print">
    @page {
        size: A4 landscape;
        margin: 1cm;
    }
    body {
        font-size: 10pt;
    }
    .panel {
        border: none;
        box-shadow: none;
    }
    .panel-heading, .panel-options, form, .info-box {
        display: none;
    }
    .report-header, .report-footer {
        display: block !important;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table th {
        background-color: #f0f0f0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .label-success {
        background-color: #5cb85c !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .label-warning {
        background-color: #f0ad4e !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .label-danger {
        background-color: #d9534f !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .text-success {
        color: #5cb85c !important;
    }
    .text-danger {
        color: #d9534f !important;
    }
</style>

<script type="text/javascript">
$(document).ready(function() {
    // Initialize datepickers
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
});
</script>