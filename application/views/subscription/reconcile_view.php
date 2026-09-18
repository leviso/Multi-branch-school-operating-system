<div class="row">
    <div class="col-md-12">
        <div class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-receipt"></i> <?=translate('transaction_details')?> #<?=$transaction->id?>
                </h4>
            </header>
            
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%"><?=translate('branch')?></th>
                                <td><?=html_escape($transaction->branch_name)?></td>
                            </tr>
                            <tr>
                                <th><?=translate('email')?></th>
                                <td><?=html_escape($transaction->branch_email)?></td>
                            </tr>
                            <tr>
                                <th><?=translate('phone')?></th>
                                <td><?=html_escape($transaction->branch_phone)?></td>
                            </tr>
                            <tr>
                                <th><?=translate('plan')?></th>
                                <td><?=html_escape($plan->name ?? 'N/A')?></td>
                            </tr>
                            <tr>
                                <th><?=translate('amount')?></th>
                                <td class="text-success">KES <?=number_format($transaction->amount, 2)?></td>
                            </tr>
                            <tr>
                                <th><?=translate('payment_method')?></th>
                                <td><?=strtoupper($transaction->payment_method)?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%"><?=translate('checkout_request_id')?></th>
                                <td><small><?=html_escape($transaction->checkout_request_id)?></small></td>
                            </tr>
                            <tr>
                                <th><?=translate('mpesa_receipt')?></th>
                                <td><?=html_escape($transaction->mpesa_receipt ?: '-')?></td>
                            </tr>
                            <tr>
                                <th><?=translate('status')?></th>
                                <td>
                                    <span class="label label-<?=$transaction->status == 'pending' ? 'warning' : ($transaction->status == 'completed' ? 'success' : 'danger')?>">
                                        <?=translate($transaction->status)?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><?=translate('created_at')?></th>
                                <td><?=_dt($transaction->created_at)?></td>
                            </tr>
                            <?php if ($transaction->reconciled_at): ?>
                            <tr>
                                <th><?=translate('reconciled_at')?></th>
                                <td><?=_dt($transaction->reconciled_at)?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                
                <?php if ($transaction->raw_callback_data): ?>
                <div class="row mt-md">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fas fa-code"></i> <?=translate('raw_callback_data')?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <pre style="max-height: 300px; overflow: auto;"><?=html_escape($transaction->raw_callback_data)?></pre>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($transaction->status != 'completed'): ?>
                <div class="row mt-lg">
                    <div class="col-md-12">
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fas fa-check-double"></i> <?=translate('manual_verification')?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <form id="verifyForm" method="post">
                                    <input type="hidden" name="transaction_id" value="<?=$transaction->id?>">
                                    
                                    <div class="form-group">
                                        <label><?=translate('receipt_number')?></label>
                                        <input type="text" name="receipt_number" class="form-control" 
                                               placeholder="Enter M-Pesa receipt number (if available)">
                                        <small class="help-block">Leave blank to use existing or generate manual receipt</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><?=translate('verification_notes')?></label>
                                        <textarea name="notes" class="form-control" rows="3" 
                                                  placeholder="Enter reason for manual verification..."></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> <?=translate('verify_and_activate')?>
                                        </button>
                                        <a href="<?=base_url('admin/reconcile/reject/'.$transaction->id)?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Reject this transaction? The pending subscription will be deleted.')">
                                            <i class="fas fa-times"></i> <?=translate('reject')?>
                                        </a>
                                        <a href="<?=base_url('admin/reconcile')?>" class="btn btn-default">
                                            <i class="fas fa-arrow-left"></i> <?=translate('back')?>
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#verifyForm').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var submitBtn = $(this).find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: '<?=base_url("admin/reconcile/verify")?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    window.location.href = response.redirect || '<?=base_url("admin/reconcile")?>';
                } else {
                    alert(response.message);
                    submitBtn.prop('disabled', false).html('<i class="fas fa-check"></i> Verify and Activate');
                }
            },
            error: function() {
                alert('Error processing request');
                submitBtn.prop('disabled', false).html('<i class="fas fa-check"></i> Verify and Activate');
            }
        });
    });
});
</script>