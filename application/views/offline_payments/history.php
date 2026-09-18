<?php $widget = (is_superadmin_loggedin() ? 6 : 12); ?>
<?php $currency_symbol = $global_config['currency_symbol']; ?>
<div class="row">
	<div class="col-md-12">
		<section class="panel">
			<header class="panel-heading">
				<h4 class="panel-title"><?=translate('select_ground')?></h4>
			</header>
			<?php echo form_open($this->uri->uri_string(), array('class' => 'validate'));?>
			<div class="panel-body">
				<div class="row mb-sm">
				<?php if (is_superadmin_loggedin() ): ?>
					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
							<?php
								$arrayBranch = $this->app_lib->getSelectList('branch');
								echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' onchange='getClassByBranch(this.value)'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
						</div>
					</div>
				<?php endif; ?>
					<div class="col-md-<?php echo $widget; ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('payments') . " " . translate('status')?></label>
							<?php
								$arrayClass = array(
									'' => translate('select'), 
									'1' => translate('pending'), 
									'2' => translate('approved'), 
									'3' => translate('suspended'), 
								);
								echo form_dropdown("payments_status", $arrayClass, set_value('payments_status'), "class='form-control' id='payments_status'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
						</div>
					</div>
				</div>
			</div>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-offset-10 col-md-2">
						<button type="submit" name="search" value="1" class="btn btn-default btn-block"> <i class="fas fa-filter"></i> <?=translate('filter')?></button>
					</div>
				</div>
			</footer>
			<?php echo form_close();?>
		</section>

<?php if (isset($paymentslist) && !empty($paymentslist)): ?>
		<section class="panel appear-animation" data-appear-animation="<?php echo $global_config['animations'];?>" data-appear-animation-delay="100">
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-list-ol"></i> <?=translate('offline_payments') . " " . translate('list')?>
				</h4>
			</header>
			<div class="panel-body">
				<div class="mb-md mt-md">
					<div class="export_title"><?=translate('offline_payments') . " " . translate('list')?></div>
					<table class="table table-bordered table-condensed table-hover mb-none tbr-top table-export">
						<thead>
							<tr>
								<th><?=translate('trx_id')?></th>
								<th><?=translate('student')?></th>
								<th><?=translate('class')?></th>
								<th><?=translate('register_no')?></th>
								<th><?=translate('payment_date')?></th>
								<th><?=translate('submit_date')?></th>
								<th><?=translate('amount')?></th>
								<th><?=translate('status')?></th>
								<th><?=translate('action')?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($paymentslist as $row):
								// Handle both object and array results
								$payment_id = is_object($row) ? $row->id : $row['id'];
								$fullname = is_object($row) ? ($row->student_name ?? $row->first_name . ' ' . $row->last_name) : ($row['student_name'] ?? $row['first_name'] . ' ' . $row['last_name']);
								$class_name = is_object($row) ? ($row->class_name ?? '') : ($row['class_name'] ?? '');
								$section_name = is_object($row) ? ($row->section_name ?? '') : ($row['section_name'] ?? '');
								$register_no = is_object($row) ? ($row->register_no ?? '') : ($row['register_no'] ?? '');
								$payment_date = is_object($row) ? $row->payment_date : $row['payment_date'];
								$submit_date = is_object($row) ? $row->submit_date : $row['submit_date'];
								$amount = is_object($row) ? $row->amount : $row['amount'];
								$status = is_object($row) ? $row->status : $row['status'];
								?>
								<tr>
									<td><?php echo $payment_id;?></td>
									<td><?php echo html_escape($fullname);?></td>
									<td><?php echo html_escape($class_name . " (" . $section_name . ")");?></td>
									<td><?php echo html_escape($register_no);?></td>
									<td><?php echo _d($payment_date);?></td>
									<td><?php echo _d($submit_date);?></td>
									<td><?php echo $currency_symbol . number_format($amount, 2);?></td>
									<td>
										<?php
											$labelmode = '';
											$status_text = '';
											if($status == 1) {
												$status_text = translate('pending');
												$labelmode = 'label-info-custom';
											} elseif($status == 2) {
												$status_text = translate('approved');
												$labelmode = 'label-success-custom';
											} elseif($status == 3) {
												$status_text = translate('suspended');
												$labelmode = 'label-danger-custom';
											}
											echo "<span class='value label " . $labelmode . " '>" . $status_text . "</span>";
										?>
									</td>
									<td>
										<?php if (get_permission('offline_payments', 'is_view')) { ?>
											<a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getApprovelOfflinePayments('<?= $payment_id ?>')">
												<i class="fas fa-bars"></i>
											</a>
										<?php } ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</section>
<?php elseif (isset($paymentslist) && empty($paymentslist)): ?>
		<div class="alert alert-info"><?=translate('no_records_found')?></div>
<?php endif; ?>
	</div>
</div>

<!-- offline payments view modal -->
<div class="zoom-anim-dialog modal-block modal-block-lg mfp-hide" id="modal">
	<section class="panel" id='quick_view'></section>
</div>

<script type="text/javascript">
	// get payments approvel details
	function getApprovelOfflinePayments(id) {
	    $.ajax({
	        url: base_url + 'offline_payments/getApprovelDetails',
	        type: 'POST',
	        data: {'id': id},
	        dataType: "html",
	        success: function (data) {
				$('#quick_view').html(data);
				mfp_modal('#modal');
	        }
	    });
	}
</script>