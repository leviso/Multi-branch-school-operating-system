<?php if (is_superadmin_loggedin() && isset($branches)): ?>
<div class="row mb-md">
    <div class="col-md-4">
        <div class="form-group">
            <label><?=translate('select_branch')?></label>
            <select class="form-control" id="branch_filter" onchange="window.location.href='<?=base_url('hostels/inventory_reports')?>?branch_id='+this.value">
                <option value=""><?=translate('select')?></option>
                <?php foreach($branches as $branch): ?>
                <option value="<?=$branch['id']?>" <?=($selected_branch_id == $branch['id']) ? 'selected' : ''?>>
                    <?=$branch['name']?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-bar"></i> <?=translate('inventory_reports')?></h4>
            </header>
            <div class="panel-body">
                <!-- Filter Form -->
                <form method="get" class="form-inline mb-md">
                    <div class="form-group">
                        <label><?=translate('category')?></label>
                        <select name="category_id" class="form-control" onchange="this.form.submit()">
                            <option value=""><?=translate('all_categories')?></option>
                            <?php foreach($categories as $cat): ?>
                            <option value="<?=$cat['id']?>" <?=($selected_category == $cat['id']) ? 'selected' : ''?>>
                                <?=$cat['name']?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <a href="<?=base_url('hostels/inventory_reports')?>" class="btn btn-default">Reset</a>
                    </div>
                    <div class="form-group pull-right">
                        <button class="btn btn-success" onclick="exportReport('excel')"><i class="fas fa-file-excel"></i> Export Excel</button>
                        <button class="btn btn-danger" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                    </div>
                </form>
                
                <!-- Summary Cards -->
                <div class="row mb-md">
                    <div class="col-md-3">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h3><?php echo number_format($total_items); ?></h3>
                                <p class="text-muted">Total Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h3><?php echo number_format($total_value, 2); ?></h3>
                                <p class="text-muted">Total Value (KES)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h3><?php echo number_format($assigned_items); ?></h3>
                                <p class="text-muted">Assigned Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <h3><?php echo number_format($damaged_items); ?></h3>
                                <p class="text-muted">Damaged Items</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Inventory Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-export" id="inventory_report_table">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Room</th>
                                <th>Assigned To</th>
                                <th>Condition</th>
                                <th>Status</th>
                                <th>Purchase Cost</th>
                                <th>Current Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                            <tr>
                                <td><?php echo $item['item_code']; ?>
                                <td><?php echo $item['name']; ?>
                                <td><?php echo $item['category_name']; ?>
                                <td><?php echo $item['room_name'] ?? '-'; ?>
                                <td><?php echo $item['student_name'] ?? 'Available'; ?>
                                <td>
                                    <span class="label label-<?php 
                                        echo $item['condition'] == 'new' ? 'success' : 
                                            ($item['condition'] == 'good' ? 'info' : 
                                            ($item['condition'] == 'fair' ? 'warning' : 'danger')); 
                                    ?>">
                                        <?php echo ucfirst($item['condition']); ?>
                                    </span>
                                
                                <td>
                                    <span class="label label-<?php 
                                        echo $item['status'] == 'available' ? 'success' : 
                                            ($item['status'] == 'assigned' ? 'primary' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($item['purchase_cost'], 2); ?></td>
                                <td><?php echo number_format($item['current_value'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
function exportReport(type) {
    var table = document.getElementById('inventory_report_table');
    var html = table.outerHTML;
    
    if (type === 'excel') {
        var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
        var link = document.createElement('a');
        link.download = 'inventory_report.xls';
        link.href = url;
        link.click();
    }
}
</script>