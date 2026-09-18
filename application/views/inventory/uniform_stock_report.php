<?php $widget = (is_superadmin_loggedin() ? 4 : 6); ?>
<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title"> <?php echo translate('uniform_stock_balance_report'); ?></h4>
	</header>
    <?php echo form_open($this->uri->uri_string(), array('class' => 'validate')); ?>
		<div class="panel-body">
			<div class="row mb-sm">
				<?php if (is_superadmin_loggedin() ): ?>
					<div class="col-md-4">
						<div class="form-group">
							<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
							<?php
								$arrayBranch = $this->app_lib->getSelectList('branch');
								echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
						</div>
					</div>
				<?php endif; ?>
				<div class="col-md-<?php echo $widget; ?> mb-lg">		
					<div class="form-group">
						<label class="control-label"><?php echo translate('term'); ?> <span class="required">*</span></label>
						<?php
							$termList = array();
							$terms = $this->db->get('exam_term')->result();
							foreach($terms as $term){
								$termList[$term->id] = $term->name;
							}
							echo form_dropdown("term_id", $termList, set_value('term_id'), "class='form-control'
							data-plugin-selectTwo data-width='100%'");
						?>
					</div>
				</div>
			</div>
		</div>
		<footer class="panel-footer">
			<div class="row">
				<div class="col-md-offset-10 col-md-2">
					<button type="submit" name="search" value="1" class="btn btn btn-default btn-block"> <i class="fas fa-filter"></i> <?php echo translate('filter'); ?></button>
				</div>
			</div>
		</footer>
	<?php echo form_close(); ?>
</section>

<?php if (isset($report_data) && !empty($report_data)): ?>
<section class="panel appear-animation" data-appear-animation="<?php echo $global_config['animations'];?>" data-appear-animation-delay="100">
	<header class="panel-heading">
		<div class="row">
			<div class="col-md-8">
				<h4 class="panel-title"><i class="fas fa-chart-line"></i> <?php echo translate('uniform_stock_balance') . " " . translate('report'); ?></h4>
			</div>
			<div class="col-md-4 text-right">
				<a href="<?=base_url('inventory/export_uniform_stock_excel?branch_id='.$branch_id.'&term_id='.$selected_term)?>" class="btn btn-success btn-sm">
					<i class="fas fa-file-excel"></i> <?=translate('export_excel')?>
				</a>
				<a href="javascript:void(0)" onclick="window.print()" class="btn btn-info btn-sm">
					<i class="fas fa-print"></i> <?=translate('print')?>
				</a>
			</div>
		</div>
	</header>
	<div class="panel-body">
		<div class="export_title"><?php echo translate('uniform_stock_balance') . " " . translate('report'); ?></div>
		
		<div class="row">
			<div class="col-md-6">
				<table class="table table-bordered table-hover table-condensed table-export" width="100%">
					<thead>
						<tr><th colspan="5" style="text-align:center; background:#337ab7; color:white;">SENIOR</th></tr>
						<tr style="background:#f5f5f5;">
							<th width="10%">NO:</th><th width="30%">ITEMS</th><th width="25%">DESCRIPTION</th><th width="20%">SIZE</th><th width="15%">QUANTITY</th>
						</tr>
					</thead>
					<tbody>
						<?php if(!empty($report_data['SENIOR'])): ?>
							<?php foreach($report_data['SENIOR'] as $item): ?>
							<tr>
								<td style="text-align:center"><?php echo $item['serial_no']; ?></td>
								<td><?php echo html_escape($item['product_name']); ?></td>
								<td><?php echo html_escape($item['description']); ?></td>
								<td><?php echo html_escape($item['size']); ?></td>
								<td><?php echo $item['quantity']; ?></td>
							</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr><td colspan="5" class="text-center"><?php echo translate('no_information_available'); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="col-md-6">
				<table class="table table-bordered table-hover table-condensed table-export" width="100%">
					<thead>
						<tr><th colspan="5" style="text-align:center; background:#5cb85c; color:white;">JUNIOR</th></tr>
						<tr style="background:#f5f5f5;">
							<th width="10%">NO:</th><th width="30%">ITEMS</th><th width="25%">DESCRIPTION</th><th width="20%">SIZE</th><th width="15%">QUANTITY</th>
						</tr>
					</thead>
					<tbody>
						<?php if(!empty($report_data['JUNIOR'])): ?>
							<?php foreach($report_data['JUNIOR'] as $item): ?>
							<tr>
								<td style="text-align:center"><?php echo $item['serial_no']; ?></td>
								<td><?php echo html_escape($item['product_name']); ?></td>
								<td><?php echo html_escape($item['description']); ?></td>
								<td><?php echo html_escape($item['size']); ?></td>
								<td><?php echo $item['quantity']; ?></td>
							</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr><td colspan="5" class="text-center"><?php echo translate('no_information_available'); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>