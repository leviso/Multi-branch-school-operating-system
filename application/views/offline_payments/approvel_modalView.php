<?php 
// Get payment data - handle both object and array returns
$payment_data = $this->offline_payments_model->getOfflinePaymentsDetails($payments_id);

if (!$payment_data) {
    echo '<div class="alert alert-danger">Payment record not found</div>';
    return;
}

// Convert to object if array
if (is_array($payment_data)) {
    $payment_data = (object) $payment_data;
}

// Get fee allocation group
$groupID = null;
if (!empty($payment_data->fees_allocation_id)) {
    $group_query = $this->db->select('group_id')->where('id', $payment_data->fees_allocation_id)->get('fee_allocation');
    $groupID = $group_query->row();
}

$currency_symbol = $global_config['currency_symbol'];
$disabled = "";
if ($payment_data->status != 1) {
    $disabled = "disabled";
}

// Role-based access control
$can_approve = (loggedin_role_id() == 4 || is_superadmin_loggedin());
$is_pending = ($payment_data->status == 1);
$show_actions = ($can_approve && $is_pending);
?>
<?php echo form_open('offline_payments/approved', array('id' => 'approvalForm', 'class' => 'approval-form')); ?>
	<header class="panel-heading">
		<h4 class="panel-title"><i class="fas fa-bars"></i> <?php echo translate('details'); ?></h4>
	</header>
	<div class="panel-body">
        <section class="panel pg-fw">
            <div class="panel-body">
                <h5 class="chart-title mb-xs"><?=translate('student_details')?></h5>
                <div class="mt-lg">
					<div class="table-responsive">
						<table class="table borderless mb-none">
							<tbody>
								<tr>
									<th class="text-nowrap"><?php echo translate('student_name'); ?> : </th>
									<td><?php echo html_escape($payment_data->fullname ?? $payment_data->student_name ?? ''); ?></td>
								</tr>
								<tr>
									<th><?php echo translate('class'); ?> : </th>
									<td><?php echo html_escape(($payment_data->class_name ?? '') . ' (' . ($payment_data->section_name ?? '') . ')'); ?></td>
								</tr>
								<tr>
									<th><?php echo translate('mobile'); ?> : </th>
									<td><?php echo html_escape($payment_data->mobileno ?? $payment_data->student_mobile ?? ''); ?></td>
								</tr>
								<tr>
									<th><?php echo translate('email'); ?> : </th>
									<td><?php echo html_escape($payment_data->email ?? ''); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
                </div>
            </div>
        </section>

        <section class="panel pg-fw">
            <div class="panel-body">
                <h5 class="chart-title mb-xs"><?=translate('payment_details')?></h5>
                <div class="mt-lg">
					<div class="table-responsive">
						<table class="table borderless mb-none">
							<tbody>
								<tr>
									<th><?php echo translate('trx_id'); ?> : </th>
									<td><?php echo $payment_data->id; ?></td>
								</tr>
								<tr>
									<th width="120"><?=translate('reviewed_by')?> :</th>
									<td>
										<?php
											if(!empty($payment_data->approved_by)){
											    echo get_type_name_by_id('staff', $payment_data->approved_by);
											} else {
											    echo translate('unreviewed');
											}
										?>
									</td>
								</tr>
								<tr>
									<th><?php echo translate('payment_method'); ?> : </th>
									<td><?php echo get_type_name_by_id('offline_payment_types', $payment_data->payment_method); ?></td>
								</tr>
								<tr>
									<th><?php echo translate('fees_group'); ?> : </th>
									<td><?php 
										if (!empty($groupID)) {
											echo get_type_name_by_id('fee_groups', $groupID->group_id);
										} else {
											echo '-';
										}
									?></td>
								</tr>
								<tr>
									<th><?php echo translate('fees_type'); ?> : </th>
									<td><?php echo get_type_name_by_id('fees_type', $payment_data->fees_type_id); ?></td>
								</tr>
								<tr>
									<th><?php echo translate('date_of_submission '); ?> : </th>
									<td><?php echo _d($payment_data->submit_date); ?></td>
								</tr>
								<tr>
									<th><?php echo translate('date_of_payment'); ?> : </th>
									<td><?php echo _d($payment_data->payment_date); ?></td>
								</tr>
								<tr class="text-nowrap">
									<th>Approved / Rejected Date : </th>
									<td><?php echo (empty($payment_data->approve_date) ? '-' : $payment_data->approve_date); ?></td>
								</tr>
								<tr>
									<th><?php echo translate('reference'); ?> : </th>
									<td><?php echo (empty($payment_data->reference) ? 'N/A' : html_escape($payment_data->reference)); ?></td>
								</tr>
								<tr>
									<th><?php echo translate('user') . " " . translate('note'); ?> : </th>
									<td><?php echo (empty($payment_data->note) ? 'N/A' : nl2br(html_escape($payment_data->note))); ?></td>
								</tr>
								<?php if (!empty($payment_data->enc_file_name)) { ?>
								<tr>
									<th><?php echo translate('proof_of_payment'); ?> : </th>
									<td><a class="btn btn-default btn-sm" target="_blank" href="<?=base_url('offline_payments/download/' . $payment_data->id . '/' . $payment_data->enc_file_name)?>"><i class="far fa-arrow-alt-circle-down"></i> <?php echo translate('download'); ?></a></td>
								</tr>
								<?php } ?>
								<tr>
									<th><?php echo translate('paid') . " " . translate('amount'); ?> : </th>
									<td><b><?php echo $currency_symbol . number_format($payment_data->amount, 2); ?></b></td>
								</tr>
								
								<!-- Status Section -->
								<?php if ($show_actions): ?>
								70
									<th><?php echo translate('status'); ?> : </th>
									<th>
										<div class="radio-custom radio-inline">
											<input type="radio" <?php echo $disabled; ?> id="pending" name="status" value="1" <?php echo ($payment_data->status == 1 ? ' checked' : ''); ?>>
											<label for="pending"><?php echo translate('pending'); ?></label>
										</div>
										<div class="radio-custom radio-success radio-inline">
											<input type="radio" <?php echo $disabled; ?> id="paid" name="status" value="2" <?php echo ($payment_data->status == 2 ? ' checked' : ''); ?>>
											<label for="paid"><?php echo translate('approved'); ?></label>
										</div>
										<div class="radio-custom radio-danger radio-inline">
											<input type="radio" <?php echo $disabled; ?> id="suspended" name="status" value="3" <?php echo ($payment_data->status == 3 ? ' checked' : ''); ?>>
											<label for="suspended"><?php echo translate('rejected'); ?></label>
										</div>
										<input type="hidden" name="id" value="<?=$payment_data->id; ?>">
									</th>
								</tr>
								70
									<th><?php echo translate('comments'); ?> : </th>
									<td><textarea class="form-control" name="comments" <?php echo $disabled; ?> rows="3"><?php echo html_escape($payment_data->comments); ?></textarea></td>
								</tr>
								<?php else: ?>
								70
									<th><?php echo translate('status'); ?> : </th>
									<th>
										<?php
											$status_text = '';
											$status_class = '';
											if($payment_data->status == 1) {
												$status_text = translate('pending');
												$status_class = 'label-info-custom';
											} elseif($payment_data->status == 2) {
												$status_text = translate('approved');
												$status_class = 'label-success-custom';
											} elseif($payment_data->status == 3) {
												$status_text = translate('rejected');
												$status_class = 'label-danger-custom';
											}
										?>
										<span class="value label <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
										<input type="hidden" name="id" value="<?=$payment_data->id; ?>">
										<?php if (!$can_approve): ?>
										<div class="alert alert-info mt-sm mb-none">
											<i class="fas fa-lock"></i> Only Accountant can approve or reject payments.
										</div>
										<?php endif; ?>
									</th>
								</tr>
								<?php if (!empty($payment_data->comments)): ?>
								70
									<th><?php echo translate('comments'); ?> : </th>
									<td><?php echo nl2br(html_escape($payment_data->comments)); ?></td>
								</tr>
								<?php endif; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
                </div>
            </div>
        </section>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-12 text-right">
			<?php if ($show_actions && $payment_data->status == 1): ?>
				<button type="submit" class="btn btn-success mr-xs" name="update" value="1">
					<i class="fas fa-check-circle"></i> <?php echo translate('apply'); ?>
				</button>
			<?php endif; ?>
				<button type="button" class="btn btn-default modal-dismiss"><?php echo translate('close'); ?></button>
			</div>
		</div>
	</footer>
<?php echo form_close(); ?>