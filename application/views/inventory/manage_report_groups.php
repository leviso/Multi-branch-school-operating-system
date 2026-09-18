<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title"><?php echo translate('manage_report_groups'); ?></h4>
	</header>
	
	<div class="panel-body">
		<!-- Branch Selection for Superadmin -->
		<?php if (is_superadmin_loggedin()): ?>
		<div class="row mb-lg">
			<div class="col-md-4">
				<div class="form-group">
					<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
					<select class="form-control" id="branch_switcher" data-plugin-selectTwo data-width="100%">
						<option value=""><?=translate('select_branch')?></option>
						<?php foreach($branches as $branch): ?>
						<option value="<?=$branch->id?>" <?=($branch_id == $branch->id) ? 'selected' : ''?>>
							<?=html_escape($branch->name)?>
						</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<div class="alert alert-info">
			<i class="fas fa-info-circle"></i> 
			<?php echo translate('report_groups_help'); ?>
		</div>
		
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title"><?=translate('uniform_sections')?></h4>
					</div>
					<div class="panel-body">
						<?php echo form_open('inventory/manage_report_groups'); ?>
							<input type="hidden" name="branch_id" value="<?=$branch_id?>">
							<input type="hidden" name="group_type" value="uniform_section">
							<div class="form-group">
								<label><?=translate('group_name')?> <span class="required">*</span></label>
								<input type="text" name="group_name" class="form-control" required>
							</div>
							<div class="form-group">
								<label><?=translate('display_order')?></label>
								<input type="number" name="display_order" class="form-control" value="0">
							</div>
							<button type="submit" class="btn btn-primary"><?=translate('add_group')?></button>
						<?php echo form_close(); ?>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title"><?=translate('consumable_sections')?></h4>
					</div>
					<div class="panel-body">
						<?php echo form_open('inventory/manage_report_groups'); ?>
							<input type="hidden" name="branch_id" value="<?=$branch_id?>">
							<input type="hidden" name="group_type" value="consumable_section">
							<div class="form-group">
								<label><?=translate('group_name')?> <span class="required">*</span></label>
								<input type="text" name="group_name" class="form-control" required>
							</div>
							<div class="form-group">
								<label><?=translate('display_order')?></label>
								<input type="number" name="display_order" class="form-control" value="0">
							</div>
							<button type="submit" class="btn btn-primary"><?=translate('add_group')?></button>
						<?php echo form_close(); ?>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Existing Groups List -->
		<div class="row mt-lg">
			<div class="col-md-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title"><?=translate('existing_report_groups')?></h4>
					</div>
					<div class="panel-body">
						<table class="table table-bordered table-hover table-export">
							<thead>
								<tr>
									<th><?=translate('group_name')?></th>
									<th><?=translate('group_type')?></th>
									<th><?=translate('display_order')?></th>
									<th><?=translate('branch')?></th>
									<th><?=translate('action')?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($groups as $group): ?>
								<tr>
									<td><?=html_escape($group->group_name)?></td>
									<td>
										<span class="label <?=($group->group_type == 'uniform_section') ? 'label-primary' : 'label-success'?>">
											<?=($group->group_type == 'uniform_section') ? translate('uniform') : translate('consumable')?>
										</span>
										
									<td><?=$group->display_order?></td>
									<td><?=get_type_name_by_id('branch', $group->branch_id)?></td>
									<td>
										<a href="<?=base_url('inventory/delete_report_group/'.$group->id)?>" class="btn btn-danger btn-xs" onclick="return confirm('<?=translate('delete_confirm')?>')">
											<i class="fas fa-trash"></i> <?=translate('delete')?>
										</a>
										
									</tr>
								<?php endforeach; ?>
								<?php if(empty($groups)): ?>
									<tr>
										<td colspan="5" class="text-center"><?=translate('no_information_available')?>[?
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
$(document).ready(function() {
    $('#branch_switcher').on('change', function() {
        var branch_id = $(this).val();
        if (branch_id) {
            window.location.href = '<?=base_url('inventory/manage_report_groups')?>?branch_id=' + branch_id;
        }
    });
});
</script>