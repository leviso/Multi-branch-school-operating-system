<?php $widget = (is_superadmin_loggedin() ? 4 : 6); ?>
<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title"> <?php echo translate('weekly_stock_consumption_report'); ?></h4>
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
				<div class="col-md-<?php echo $widget; ?>">		
					<div class="form-group">
						<label class="control-label"><?php echo translate('term'); ?> <span class="required">*</span></label>
						<?php
							$termList = array();
							foreach($terms as $term){
								$termList[$term->id] = $term->name . ($term->is_active ? ' (Active)' : '');
							}
							echo form_dropdown("term_id", $termList, set_value('term_id'), "class='form-control'
							data-plugin-selectTwo data-width='100%'");
						?>
					</div>
				</div>
				<div class="col-md-<?php echo $widget; ?>">		
					<div class="form-group">
						<label class="control-label"><?php echo translate('year'); ?></label>
						<?php
							echo form_dropdown("year", $year_list, set_value('year', date('Y')), "class='form-control'
							data-plugin-selectTwo data-width='100%'");
						?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="alert alert-info">
						<i class="fas fa-info-circle"></i> 
						<?php echo translate('weekly_consumption_note'); ?>
						<button type="submit" name="calculate" value="1" class="btn btn-primary btn-sm pull-right">
							<i class="fas fa-calculator"></i> <?=translate('calculate_consumption')?>
						</button>
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

<?php if (isset($report_data) && !empty($report_data['sections'])): ?>
<section class="panel appear-animation" data-appear-animation="<?php echo $global_config['animations'];?>" data-appear-animation-delay="100">
	<header class="panel-heading">
		<div class="row">
			<div class="col-md-8">
				<h4 class="panel-title"><i class="fas fa-chart-line"></i> <?php echo translate('weekly_stock_consumption') . " " . translate('report'); ?></h4>
			</div>
			<div class="col-md-4 text-right">
				<a href="<?=base_url('inventory/export_weekly_consumption_excel?branch_id='.$branch_id.'&term_id='.$selected_term.'&year='.$selected_year)?>" class="btn btn-success btn-sm">
					<i class="fas fa-file-excel"></i> <?=translate('export_excel')?>
				</a>
				<a href="javascript:void(0)" onclick="window.print()" class="btn btn-info btn-sm">
					<i class="fas fa-print"></i> <?=translate('print')?>
				</a>
			</div>
		</div>
	</header>
	<div class="panel-body">
		<div class="export_title"><?php echo translate('weekly_stock_consumption') . " " . translate('report'); ?></div>
		
		<?php foreach($report_data['sections'] as $section_name => $products): ?>
		<h4 class="mt-lg"><?php echo html_escape($section_name); ?></h4>
		<div class="table-responsive">
			<table class="table table-bordered table-hover table-condensed table-export" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th width="5%"><?php echo translate('sl'); ?></th>
						<th width="20%"><?php echo translate('items'); ?></th>
						<?php for($week = 1; $week <= $report_data['total_weeks']; $week++): ?>
						<th width="5%"><?php echo 'WEEK ' . $week; ?></th>
						<?php endfor; ?>
					</tr>
				</thead>
				<tbody>
					<?php 
					if (!empty($products)){ 
						foreach ($products as $row):
					?>	
					<tr>
						<td style="text-align:center"><?php echo $row['serial_no']; ?></td>
						<td><?php echo html_escape($row['product_name']); ?></td>
						<?php for($week = 1; $week <= $report_data['total_weeks']; $week++): ?>
						<td><?php echo isset($row['weekly_data'][$week]) ? html_escape($row['weekly_data'][$week]) : '-'; ?></td>
						<?php endfor; ?>
					</tr>
					<?php 
						endforeach;
					} else {
						echo '<tr><td colspan="' . ($report_data['total_weeks'] + 2) . '" class="text-center">' . translate('no_information_available') . 'NonNull</td><tr>';
					}
					?>
				</tbody>
				dable
		</div>
		<?php endforeach; ?>
		
		<?php if($report_data['term']): ?>
		<div class="alert alert-info mt-md">
			<strong><?=translate('term')?>:</strong> <?=$report_data['term']->name?> | 
			<strong><?=translate('total_weeks')?>:</strong> <?=$report_data['total_weeks']?> | 
			<strong><?=translate('report_date')?>:</strong> <?=date('d/m/Y')?>
		</div>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>