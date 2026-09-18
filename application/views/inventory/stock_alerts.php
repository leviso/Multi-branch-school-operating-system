<div class="row">
    <div class="col-md-12">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-body text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                        <h2 class="mt-md"><?php echo $total_low; ?></h2>
                        <p class="text-muted"><?php echo translate('low_stock_products'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-body text-center">
                        <i class="fas fa-times-circle fa-3x text-danger"></i>
                        <h2 class="mt-md"><?php echo $total_out; ?></h2>
                        <p class="text-muted"><?php echo translate('out_of_stock_products'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-body text-center">
                        <i class="fas fa-check-circle fa-3x text-success"></i>
                        <h2 class="mt-md"><?php echo $healthy_count; ?></h2>
                        <p class="text-muted"><?php echo translate('healthy_stock_products'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Out of Stock Products Section -->
        <?php if (!empty($out_of_stock)): ?>
        <section class="panel">
            <header class="panel-heading" style="background-color: #f8d7da; border-color: #f5c6cb;">
                <h4 class="panel-title text-danger">
                    <i class="fas fa-times-circle"></i> <?php echo translate('out_of_stock'); ?> (<?php echo $total_out; ?>)
                </h4>
            </header>
            <div class="panel-body">
                <div class="export_title"><?php echo translate('out_of_stock_products'); ?></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-export nowrap" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th><?=translate('sl')?></th>
<?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
<?php endif; ?>
                                <th><?php echo translate('product_name'); ?></th>
                                <th><?php echo translate('product_code'); ?></th>
                                <th><?php echo translate('category'); ?></th>
                                <th><?php echo translate('current_stock'); ?></th>
                                <th><?php echo translate('reorder_point'); ?></th>
                                <th><?php echo translate('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($out_of_stock as $product): ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
<?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo get_type_name_by_id('branch', $product['branch_id']); ?></td>
<?php endif; ?>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['code']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td class="text-center"><span class="label label-danger-custom">0 <?php echo translate('left'); ?></span></td>
                                <td class="text-center"><?php echo $product['reorder_point']; ?></td>
                                <td class="text-center">
                                    <a href="<?php echo base_url('inventory/purchase'); ?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" title="<?php echo translate('order_now'); ?>">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                    <a href="<?php echo base_url('inventory/product_edit/' . $product['id']); ?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" title="<?php echo translate('edit'); ?>">
                                        <i class="fas fa-pen-nib"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Low Stock Products Section -->
        <?php if (!empty($low_stock)): ?>
        <section class="panel">
            <header class="panel-heading" style="background-color: #fff3cd; border-color: #ffeeba;">
                <h4 class="panel-title text-warning">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo translate('low_stock'); ?> (<?php echo $total_low; ?>)
                </h4>
            </header>
            <div class="panel-body">
                <div class="export_title"><?php echo translate('low_stock_products'); ?></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-export nowrap" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th><?=translate('sl')?></th>
<?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
<?php endif; ?>
                                <th><?php echo translate('product_name'); ?></th>
                                <th><?php echo translate('product_code'); ?></th>
                                <th><?php echo translate('category'); ?></th>
                                <th><?php echo translate('current_stock'); ?></th>
                                <th><?php echo translate('reorder_point'); ?></th>
                                <th><?php echo translate('stock_status'); ?></th>
                                <th><?php echo translate('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($low_stock as $product): 
                                $percentage = ($product['available_stock'] / $product['reorder_point']) * 100;
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
<?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo get_type_name_by_id('branch', $product['branch_id']); ?></td>
<?php endif; ?>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['code']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td class="text-center"><span class="label label-warning-custom"><?php echo $product['available_stock']; ?> <?php echo translate('left'); ?></span></td>
                                <td class="text-center"><?php echo $product['reorder_point']; ?></td>
                                <td class="text-center" style="min-width: 100px;">
                                    <div class="progress" style="height: 8px; margin-bottom: 0; width: 80px; display: inline-block;">
                                        <div class="progress-bar progress-bar-warning" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                    <span class="small"> <?php echo round($percentage); ?>%</span>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo base_url('inventory/purchase'); ?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" title="<?php echo translate('reorder'); ?>">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                    <a href="<?php echo base_url('inventory/product_edit/' . $product['id']); ?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" title="<?php echo translate('edit'); ?>">
                                        <i class="fas fa-pen-nib"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- No Alerts Message -->
        <?php if (empty($out_of_stock) && empty($low_stock)): ?>
        <section class="panel">
            <div class="panel-body text-center" style="padding: 50px;">
                <i class="fas fa-check-circle fa-5x text-success"></i>
                <h3 class="mt-md"><?php echo translate('all_stock_levels_are_healthy'); ?></h3>
                <p class="text-muted"><?php echo translate('no_products_need_reordering'); ?></p>
                <a href="<?php echo base_url('inventory/stockreport'); ?>" class="btn btn-default mt-md">
                    <i class="fas fa-chart-line"></i> <?php echo translate('view_full_stock_report'); ?>
                </a>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>