<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-bar"></i> <?=translate('hostel_reports')?></h4>
            </header>
            <div class="panel-body">
                <!-- Branch Filter for Superadmin -->
                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                <div class="row mb-md">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?=translate('branch')?></label>
                            <select class="form-control" id="branch_filter" onchange="window.location.href='<?=base_url('hostels/reports')?>?branch_id='+this.value+'&report_type=<?=$report_type?>'">
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
                
                <!-- Report Type Tabs -->
                <div class="tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="<?=($report_type == 'repairs') ? 'active' : ''?>">
                            <a href="<?=base_url('hostels/reports?report_type=repairs&branch_id='.$branch_id)?>">
                                <i class="fas fa-wrench"></i> <?=translate('repairs_report')?>
                            </a>
                        </li>
                        <li class="<?=($report_type == 'inventory') ? 'active' : ''?>">
                            <a href="<?=base_url('hostels/reports?report_type=inventory&branch_id='.$branch_id)?>">
                                <i class="fas fa-boxes"></i> <?=translate('inventory_report')?>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Filter Form -->
                <form method="get" class="form-inline mb-md" id="filterForm">
                    <input type="hidden" name="report_type" value="<?=$report_type?>">
                    <input type="hidden" name="branch_id" value="<?=$branch_id?>">
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=translate('period')?></label>
                                <select name="period" class="form-control" onchange="this.form.submit()">
                                    <option value="all" <?=($period == 'all') ? 'selected' : ''?>><?=translate('all_time')?></option>
                                    <option value="daily" <?=($period == 'daily') ? 'selected' : ''?>><?=translate('today')?></option>
                                    <option value="weekly" <?=($period == 'weekly') ? 'selected' : ''?>><?=translate('this_week')?></option>
                                    <option value="monthly" <?=($period == 'monthly') ? 'selected' : ''?>><?=translate('this_month')?></option>
                                    <option value="termly" <?=($period == 'termly') ? 'selected' : ''?>><?=translate('this_term')?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=translate('date_range')?></label>
                                <input type="text" name="date_range" class="form-control daterange" value="<?=($start_date && $end_date && $period == 'all') ? date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)) : ''?>" placeholder="<?=translate('select_date_range')?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=translate('hostel')?></label>
                                <select name="hostel_id" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?=translate('all_hostels')?></option>
                                    <?php foreach($hostels as $hostel): ?>
                                    <option value="<?=$hostel['id']?>" <?=($hostel_filter == $hostel['id']) ? 'selected' : ''?>>
                                        <?=$hostel['name']?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=translate('room')?></label>
                                <select name="room_id" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?=translate('all_rooms')?></option>
                                    <?php foreach($rooms as $room): ?>
                                    <option value="<?=$room['id']?>" <?=($room_filter == $room['id']) ? 'selected' : ''?>>
                                        <?=$room['name']?> (<?=$room['hostel_name']?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($report_type == 'repairs'): ?>
                    <div class="row mt-sm">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=translate('status')?></label>
                                <select name="status" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?=translate('all_status')?></option>
                                    <option value="pending" <?=($status_filter == 'pending') ? 'selected' : ''?>><?=translate('pending')?></option>
                                    <option value="in_progress" <?=($status_filter == 'in_progress') ? 'selected' : ''?>><?=translate('in_progress')?></option>
                                    <option value="completed" <?=($status_filter == 'completed') ? 'selected' : ''?>><?=translate('completed')?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=translate('priority')?></label>
                                <select name="priority" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?=translate('all_priorities')?></option>
                                    <option value="low" <?=($priority_filter == 'low') ? 'selected' : ''?>><?=translate('low')?></option>
                                    <option value="medium" <?=($priority_filter == 'medium') ? 'selected' : ''?>><?=translate('medium')?></option>
                                    <option value="high" <?=($priority_filter == 'high') ? 'selected' : ''?>><?=translate('high')?></option>
                                    <option value="emergency" <?=($priority_filter == 'emergency') ? 'selected' : ''?>><?=translate('emergency')?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" class="btn btn-default"><?=translate('apply_filters')?></button>
                            <a href="<?=base_url('hostels/reports?report_type='.$report_type.'&branch_id='.$branch_id)?>" class="btn btn-default"><?=translate('reset')?></a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="row mt-sm">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=translate('category')?></label>
                                <select name="category_id" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?=translate('all_categories')?></option>
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?=$cat['id']?>" <?=($category_filter == $cat['id']) ? 'selected' : ''?>>
                                        <?=$cat['name']?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=translate('condition')?></label>
                                <select name="condition" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?=translate('all_conditions')?></option>
                                    <option value="new" <?=($condition_filter == 'new') ? 'selected' : ''?>><?=translate('new')?></option>
                                    <option value="good" <?=($condition_filter == 'good') ? 'selected' : ''?>><?=translate('good')?></option>
                                    <option value="fair" <?=($condition_filter == 'fair') ? 'selected' : ''?>><?=translate('fair')?></option>
                                    <option value="poor" <?=($condition_filter == 'poor') ? 'selected' : ''?>><?=translate('poor')?></option>
                                    <option value="damaged" <?=($condition_filter == 'damaged') ? 'selected' : ''?>><?=translate('damaged')?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?=translate('status')?></label>
                                <select name="status" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?=translate('all_status')?></option>
                                    <option value="available" <?=($status_filter == 'available') ? 'selected' : ''?>><?=translate('available')?></option>
                                    <option value="assigned" <?=($status_filter == 'assigned') ? 'selected' : ''?>><?=translate('assigned')?></option>
                                    <option value="maintenance" <?=($status_filter == 'maintenance') ? 'selected' : ''?>><?=translate('maintenance')?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 text-right">
                            <button type="submit" class="btn btn-default"><?=translate('apply_filters')?></button>
                            <a href="<?=base_url('hostels/reports?report_type='.$report_type.'&branch_id='.$branch_id)?>" class="btn btn-default"><?=translate('reset')?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
                
                <!-- Summary Cards -->
                <?php if ($report_type == 'repairs'): ?>
                <div class="row mb-md">
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($repairs_summary['total']); ?></h3>
                                <p class="text-muted"><?=translate('total_repairs')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($repairs_summary['pending']); ?></h3>
                                <p class="text-muted"><?=translate('pending')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($repairs_summary['high_priority'] + $repairs_summary['emergency']); ?></h3>
                                <p class="text-muted"><?=translate('high_priority_repairs')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo $global_config['currency_symbol'] . number_format($repairs_summary['total_cost'], 2); ?></h3>
                                <p class="text-muted"><?=translate('total_cost')?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="row mb-md">
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($inventory_summary['total']); ?></h3>
                                <p class="text-muted"><?=translate('total_items')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($inventory_summary['available']); ?></h3>
                                <p class="text-muted"><?=translate('available')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($inventory_summary['assigned']); ?></h3>
                                <p class="text-muted"><?=translate('assigned')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo $global_config['currency_symbol'] . number_format($inventory_summary['total_value'], 2); ?></h3>
                                <p class="text-muted"><?=translate('total_value')?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- ========== DATA TABLE (MOVED UP - ABOVE CHARTS) ========== -->
                <div class="table-responsive mt-md">
                    <?php if ($report_type == 'repairs'): ?>
                    <table class="table table-bordered table-hover table-export" id="reportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('repair_code')?></th>
                                <th><?=translate('item')?>/<?=translate('room')?></th>
                                <th><?=translate('issue')?></th>
                                <th><?=translate('priority')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('reported_date')?></th>
                                <th><?=translate('assigned_to')?></th>
                                <th><?=translate('cost')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach($repairs_data as $repair): ?>
                            <tr>
                                <td><?php echo $count++; ?>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo $repair['branch_name']; ?>
                                <?php endif; ?>
                                <td><?php echo $repair['repair_code']; ?>
                                <td>
                                    <?php 
                                    if(!empty($repair['item_name'])) {
                                        echo $repair['item_name'];
                                    } elseif(!empty($repair['room_name'])) {
                                        echo 'Room: ' . $repair['room_name'];
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                
                                <td><?php echo substr($repair['issue_description'], 0, 60) . (strlen($repair['issue_description']) > 60 ? '...' : ''); ?>
                                <td>
                                    <span class="label label-<?php 
                                        echo $repair['priority'] == 'emergency' ? 'danger' : 
                                            ($repair['priority'] == 'high' ? 'warning' : 
                                            ($repair['priority'] == 'medium' ? 'info' : 'default')); 
                                    ?>">
                                        <?php echo ucfirst($repair['priority']); ?>
                                    </span>
                                
                                <td>
                                    <span class="label label-<?php 
                                        echo $repair['status'] == 'completed' ? 'success' : 
                                            ($repair['status'] == 'pending' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($repair['status']); ?>
                                    </span>
                                
                                <td><?php echo date('d M Y', strtotime($repair['reported_date'])); ?>
                                <td><?php echo $repair['assigned_to_name'] ?? 'Not Assigned'; ?>
                                <td><?php echo number_format($repair['actual_cost'], 2); ?>
                             `
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <table class="table table-bordered table-hover table-export" id="reportTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('item_code')?></th>
                                <th><?=translate('item_name')?></th>
                                <th><?=translate('category')?></th>
                                <th><?=translate('room')?></th>
                                <th><?=translate('assigned_to')?></th>
                                <th><?=translate('condition')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('purchase_cost')?></th>
                                <th><?=translate('current_value')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach($inventory_data as $item): ?>
                            <tr>
                                <td><?php echo $count++; ?>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo $item['branch_name']; ?>
                                <?php endif; ?>
                                <td><?php echo $item['item_code']; ?>
                                <td><?php echo $item['name']; ?>
                                <td><?php echo $item['category_name']; ?>
                                <td><?php echo $item['room_name'] ?? 'Not Assigned'; ?>
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
                                
                                <td><?php echo number_format($item['purchase_cost'], 2); ?>
                                <td><?php echo number_format($item['current_value'], 2); ?>
                             `
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                
                <!-- ========== CHARTS SECTION (MOVED DOWN - BELOW TABLE) ========== -->
                <div class="row mt-md">
                    <?php if ($report_type == 'repairs'): ?>
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading"><?=translate('repairs_by_status')?></div>
                            <div class="panel-body">
                                <canvas id="statusChart" style="height: 250px; width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading"><?=translate('repairs_by_priority')?></div>
                            <div class="panel-body">
                                <canvas id="priorityChart" style="height: 250px; width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading"><?=translate('inventory_by_condition')?></div>
                            <div class="panel-body">
                                <canvas id="conditionChart" style="height: 250px; width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading"><?=translate('inventory_by_status')?></div>
                            <div class="panel-body">
                                <canvas id="invStatusChart" style="height: 250px; width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Monthly Trend Chart (Repairs only) - Below the two charts -->
                <?php if ($report_type == 'repairs'): ?>
                <div class="row mt-md">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading"><?=translate('monthly_repair_trend')?></div>
                            <div class="panel-body">
                                <canvas id="monthlyChart" style="height: 300px; width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    // Initialize date range picker
    if ($.fn.daterangepicker) {
        $('.daterange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'DD MMM YYYY'
            }
        });
        
        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD MMM YYYY') + ' - ' + picker.endDate.format('DD MMM YYYY'));
            $('#filterForm').submit();
        });
        
        $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            $('#filterForm').submit();
        });
    }
    
    // Small delay to ensure canvas is ready
    setTimeout(function() {
        <?php if ($report_type == 'repairs'): ?>
        // Repairs by Status Chart
        var statusCanvas = document.getElementById('statusChart');
        if (statusCanvas) {
            var ctx1 = statusCanvas.getContext('2d');
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Approved', 'Assigned', 'In Progress', 'Completed'],
                    datasets: [{
                        data: [
                            <?php echo $repairs_by_status['pending'] ?? 0; ?>, 
                            <?php echo $repairs_by_status['approved'] ?? 0; ?>, 
                            <?php echo $repairs_by_status['assigned'] ?? 0; ?>, 
                            <?php echo $repairs_by_status['in_progress'] ?? 0; ?>, 
                            <?php echo $repairs_by_status['completed'] ?? 0; ?>
                        ],
                        backgroundColor: ['#ffc107', '#007bff', '#6c757d', '#17a2b8', '#28a745'],
                        borderWidth: 1
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
        
        // Repairs by Priority Chart
        var priorityCanvas = document.getElementById('priorityChart');
        if (priorityCanvas) {
            var ctx2 = priorityCanvas.getContext('2d');
            new Chart(ctx2, {
                type: 'pie',
                data: {
                    labels: ['Low', 'Medium', 'High', 'Emergency'],
                    datasets: [{
                        data: [
                            <?php echo $repairs_by_priority['low'] ?? 0; ?>, 
                            <?php echo $repairs_by_priority['medium'] ?? 0; ?>, 
                            <?php echo $repairs_by_priority['high'] ?? 0; ?>, 
                            <?php echo $repairs_by_priority['emergency'] ?? 0; ?>
                        ],
                        backgroundColor: ['#6c757d', '#17a2b8', '#ffc107', '#dc3545'],
                        borderWidth: 1
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
        
        // Monthly Trend Chart
        var monthlyCanvas = document.getElementById('monthlyChart');
        if (monthlyCanvas) {
            var ctx3 = monthlyCanvas.getContext('2d');
            new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: [<?php 
                        $labels = array();
                        foreach($monthly_repairs as $m) {
                            $labels[] = '"' . $m['month'] . '"';
                        }
                        echo implode(',', $labels);
                    ?>],
                    datasets: [{
                        label: 'Number of Repairs',
                        data: [<?php 
                            $values = array();
                            foreach($monthly_repairs as $m) {
                                $values[] = $m['count'];
                            }
                            echo implode(',', $values);
                        ?>],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Number of Repairs' }
                        }
                    }
                }
            });
        }
        <?php else: ?>
        // Inventory by Condition Chart
        var conditionCanvas = document.getElementById('conditionChart');
        if (conditionCanvas) {
            var ctx1 = conditionCanvas.getContext('2d');
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: ['New', 'Good', 'Fair', 'Poor', 'Damaged'],
                    datasets: [{
                        label: 'Number of Items',
                        data: [
                            <?php echo $inventory_by_condition['new'] ?? 0; ?>, 
                            <?php echo $inventory_by_condition['good'] ?? 0; ?>, 
                            <?php echo $inventory_by_condition['fair'] ?? 0; ?>, 
                            <?php echo $inventory_by_condition['poor'] ?? 0; ?>, 
                            <?php echo $inventory_by_condition['damaged'] ?? 0; ?>
                        ],
                        backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#fd7e14', '#dc3545'],
                        borderWidth: 1
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Number of Items' }
                        }
                    }
                }
            });
        }
        
        // Inventory by Status Chart
        var invStatusCanvas = document.getElementById('invStatusChart');
        if (invStatusCanvas) {
            var ctx2 = invStatusCanvas.getContext('2d');
            new Chart(ctx2, {
                type: 'pie',
                data: {
                    labels: ['Available', 'Assigned', 'Maintenance'],
                    datasets: [{
                        data: [
                            <?php echo $inventory_by_status['available'] ?? 0; ?>, 
                            <?php echo $inventory_by_status['assigned'] ?? 0; ?>, 
                            <?php echo $inventory_by_status['maintenance'] ?? 0; ?>
                        ],
                        backgroundColor: ['#28a745', '#007bff', '#ffc107'],
                        borderWidth: 1
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
        <?php endif; ?>
    }, 200);
});
</script>