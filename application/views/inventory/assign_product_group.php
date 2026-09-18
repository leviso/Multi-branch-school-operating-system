<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title"><?php echo translate('assign_products_to_report_groups'); ?></h4>
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
		
		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title"><?=translate('uniform_products')?></h4>
					</div>
					<div class="panel-body">
						<?php echo form_open('inventory/assign_product_to_group'); ?>
							<input type="hidden" name="branch_id" value="<?=$branch_id?>">
							<div class="form-group">
								<label><?=translate('select_product')?> <span class="required">*</span></label>
								<select name="product_id" class="form-control" required data-plugin-selectTwo>
									<option value=""><?=translate('select')?></option>
									<?php foreach($uniform_products as $product): ?>
									<option value="<?=$product->id?>"><?=html_escape($product->name)?> (<?=$product->code?>)</option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group">
								<label><?=translate('assign_to_sections')?></label>
								<?php foreach($uniform_groups as $group): ?>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="group_ids[]" value="<?=$group->id?>" <?= (isset($assigned_groups[$product->id]) && in_array($group->id, $assigned_groups[$product->id])) ? 'checked' : '' ?>>
										<?=html_escape($group->group_name)?>
									</label>
								</div>
								<?php endforeach; ?>
							</div>
							<button type="submit" class="btn btn-primary"><?=translate('save_assignment')?></button>
						<?php echo form_close(); ?>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title"><?=translate('consumable_products')?></h4>
					</div>
					<div class="panel-body">
						<?php echo form_open('inventory/assign_product_to_group'); ?>
							<input type="hidden" name="branch_id" value="<?=$branch_id?>">
							<div class="form-group">
								<label><?=translate('select_product')?> <span class="required">*</span></label>
								<select name="product_id" class="form-control" required data-plugin-selectTwo>
									<option value=""><?=translate('select')?></option>
									<?php foreach($consumable_products as $product): ?>
									<option value="<?=$product->id?>"><?=html_escape($product->name)?> (<?=$product->code?>)</option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group">
								<label><?=translate('assign_to_sections')?></label>
								<?php foreach($consumable_groups as $group): ?>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="group_ids[]" value="<?=$group->id?>" <?= (isset($assigned_groups[$product->id]) && in_array($group->id, $assigned_groups[$product->id])) ? 'checked' : '' ?>>
										<?=html_escape($group->group_name)?>
									</label>
								</div>
								<?php endforeach; ?>
							</div>
							<button type="submit" class="btn btn-primary"><?=translate('save_assignment')?></button>
						<?php echo form_close(); ?>
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
            window.location.href = '<?=base_url('inventory/assign_product_to_group')?>?branch_id=' + branch_id;
        }
    });
});
</script>