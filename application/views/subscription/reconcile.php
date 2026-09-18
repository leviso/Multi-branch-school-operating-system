<div class="row">
    <div class="col-md-12">
        <div class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-check-double"></i> <?=translate('payment_reconciliation')?>
                </h4>
            </header>
            
            <div class="panel-body">
                <!-- Statistics Cards -->
                <div class="row mb-lg">
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h3 class="text-warning"><?=$stats->pending?></h3>
                                <p><?=translate('pending_transactions')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h3 class="text-danger"><?=$stats->failed?></h3>
                                <p><?=translate('failed_transactions')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h3 class="text-success"><?=$stats->reconciled?></h3>
                                <p><?=translate('reconciled_transactions')?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="row mb-md">
                    <div class="col-md-12">
                        <div class="btn-group">
                            <a href="<?=base_url('admin/reconcile')?>" class="btn btn-default <?=!$this->input->get('status') ? 'active' : ''?>">
                                <?=translate('all')?>
                            </a>
                            <a href="<?=base_url('admin/reconcile?status=pending')?>" class="btn btn-default <?=$this->input->get('status') == 'pending' ? 'active' : ''?>">
                                <?=translate('pending')?>
                            </a>
                            <a href="<?=base_url('admin/reconcile?status=failed')?>" class="btn btn-default <?=$this->input->get('status') == 'failed' ? 'active' : ''?>">
                                <?=translate('failed')?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?=translate('branch')?></th>
                                <th><?=translate('plan')?></th>
                                <th><?=translate('amount')?></th>
                                <th><?=translate('payment_method')?></th>
                                <th><?=translate('checkout_id')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('created_at')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="9" class="text-center"><?=translate('no_transactions_found')?></td>
                            </tr>
                            <?php else: foreach ($transactions as $trans): ?>
                            <tr>
                                <td><?=$trans->id?></td>
                                <td><?=html_escape($trans->branch_name)?><br>
                                    <small><?=html_escape($trans->branch_email)?></small>
                                </td>
                                <td><?=html_escape($trans->plan_id)?></td>
                                <td>KES <?=number_format($trans->amount, 2)?></td>
                                <td><?=strtoupper($trans->payment_method)?></td>
                                <td>
                                    <small><?=html_escape($trans->checkout_request_id)?></small>
                                </td>
                                <td>
                                    <span class="label label-<?=$trans->status == 'pending' ? 'warning' : 'danger'?>">
                                        <?=translate($trans->status)?>
                                    </span>
                                </td>
                                <td><?=_d($trans->created_at)?></td>
                                <td>
                                    <a href="<?=base_url('admin/reconcile/view/'.$trans->id)?>" 
                                       class="btn btn-primary btn-xs">
                                        <i class="fas fa-eye"></i> <?=translate('view')?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>