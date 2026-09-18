<div class="row">
    <div class="col-md-12">
        <!-- Filter Panel -->
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-filter"></i> <?php echo translate('filter_products'); ?></h4>
            </header>
            <div class="panel-body">
                <form method="get" action="<?php echo base_url('inventory/product_report'); ?>" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label"><?php echo translate('category'); ?></label>
                                <select name="category_id" class="form-control">
                                    <option value="all"><?php echo translate('all_categories'); ?></option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label"><?php echo translate('stock_status'); ?></label>
                                <select name="stock_filter" class="form-control">
                                    <option value="all" <?php echo ($stock_filter == 'all') ? 'selected' : ''; ?>><?php echo translate('all_products'); ?></option>
                                    <option value="low" <?php echo ($stock_filter == 'low') ? 'selected' : ''; ?>>Low Stock</option>
                                    <option value="out" <?php echo ($stock_filter == 'out') ? 'selected' : ''; ?>>Out of Stock</option>
                                    <option value="healthy" <?php echo ($stock_filter == 'healthy') ? 'selected' : ''; ?>>Healthy Stock</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label"><?php echo translate('search'); ?></label>
                                <input type="text" name="search" class="form-control" placeholder="<?php echo translate('search_by_name_or_code'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <?php echo translate('filter'); ?></button>
                                    <a href="<?php echo base_url('inventory/product_report'); ?>" class="btn btn-default"><i class="fas fa-undo"></i> <?php echo translate('reset'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Products Table with Export (Matching your issue.php style) -->
        <section class="panel">
            <div class="panel-body">
                <div class="export_title"><?php echo translate('product_report'); ?></div>
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
                            <th><?php echo translate('purchase_price'); ?></th>
                            <th><?php echo translate('sales_price'); ?></th>
                            <th><?php echo translate('stock_status'); ?></th>
                            <th><?php echo translate('action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="<?php echo is_superadmin_loggedin() ? '11' : '10'; ?>" class="text-center"><?php echo translate('no_products_found'); ?></td>
                        </tr>
                        <?php else: ?>
                        <?php $i = 1; foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
<?php if (is_superadmin_loggedin()): ?>
                            <td><?php echo get_type_name_by_id('branch', $product['branch_id']); ?></td>
<?php endif; ?>
                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['code']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td class="text-center">
                                <?php if ($product['stock_status'] == 'out'): ?>
                                <span class="label label-danger-custom">0 <?php echo !empty($product['unit_name']) ? $product['unit_name'] : ''; ?></span>
                                <?php elseif ($product['stock_status'] == 'low'): ?>
                                <span class="label label-warning-custom"><?php echo $product['available_stock'] . ' ' . ($product['unit_name'] ?? ''); ?></span>
                                <?php else: ?>
                                <span class="label label-success-custom"><?php echo $product['available_stock'] . ' ' . ($product['unit_name'] ?? ''); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $product['reorder_point'] > 0 ? $product['reorder_point'] : '-'; ?></td>
                            <td class="text-right"><?php echo number_format($product['purchase_price'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($product['sales_price'], 2); ?></td>
                            <td>
                                <span class="label label-<?php echo $product['stock_status_class']; ?>-custom">
                                    <?php echo $product['stock_status_text']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo base_url('inventory/product_edit/' . $product['id']); ?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" title="<?php echo translate('edit'); ?>">
                                    <i class="fas fa-pen-nib"></i>
                                </a>
                                <a href="<?php echo base_url('inventory/purchase'); ?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" title="<?php echo translate('purchase'); ?>">
                                    <i class="fas fa-shopping-cart"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
    // Initialize tooltips
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>