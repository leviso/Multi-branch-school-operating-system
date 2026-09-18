<div class="row">
    <!-- Statistics Cards -->
    <div class="col-md-3">
        <div class="panel panel-default card">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="mt-sm"><?php echo number_format($total_items); ?></h2>
                        <p class="text-muted">Total Inventory Items</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <i class="fas fa-boxes fa-3x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="panel panel-default card">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="mt-sm"><?php echo number_format($total_repairs); ?></h2>
                        <p class="text-muted">Total Repairs</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <i class="fas fa-wrench fa-3x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="panel panel-default card">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="mt-sm"><?php echo number_format($pending_urgent); ?></h2>
                        <p class="text-muted">Urgent Repairs</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="panel panel-default card">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="mt-sm"><?php echo number_format($students_in_hostel); ?> / <?php echo number_format($total_beds); ?></h2>
                        <p class="text-muted">Occupancy Rate (<?php echo $total_beds > 0 ? round(($students_in_hostel / $total_beds) * 100) : 0; ?>%)</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <i class="fas fa-bed fa-3x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row - Using Simple Bootstrap Progress Bars instead of Charts -->
<div class="row">
    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-pie"></i> Items by Condition</h4>
            </header>
            <div class="panel-body">
                <?php foreach ($items_by_condition as $item): ?>
                <div class="row mb-sm">
                    <div class="col-md-4">
                        <?php echo ucfirst($item['condition']); ?>
                    </div>
                    <div class="col-md-8">
                        <div class="progress">
                            <div class="progress-bar progress-bar-<?php 
                                echo $item['condition'] == 'new' ? 'success' : 
                                    ($item['condition'] == 'good' ? 'info' : 
                                    ($item['condition'] == 'fair' ? 'warning' : 'danger')); 
                            ?>" role="progressbar" 
                                style="width: <?php echo $total_items > 0 ? round(($item['count'] / $total_items) * 100) : 0; ?>%">
                                <?php echo $item['count']; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    
    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-pie"></i> Items by Status</h4>
            </header>
            <div class="panel-body">
                <?php foreach ($items_by_status as $item): ?>
                <div class="row mb-sm">
                    <div class="col-md-4">
                        <?php echo ucfirst($item['status']); ?>
                    </div>
                    <div class="col-md-8">
                        <div class="progress">
                            <div class="progress-bar progress-bar-<?php 
                                echo $item['status'] == 'available' ? 'success' : 
                                    ($item['status'] == 'assigned' ? 'primary' : 'warning'); 
                            ?>" role="progressbar" 
                                style="width: <?php echo $total_items > 0 ? round(($item['count'] / $total_items) * 100) : 0; ?>%">
                                <?php echo $item['count']; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<!-- Repair Status and Monthly Costs -->
<div class="row">
    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-pie"></i> Repair Status Distribution</h4>
            </header>
            <div class="panel-body">
                <?php foreach ($repairs_by_status as $repair): ?>
                <div class="row mb-sm">
                    <div class="col-md-4">
                        <?php echo ucfirst($repair['status']); ?>
                    </div>
                    <div class="col-md-8">
                        <div class="progress">
                            <div class="progress-bar progress-bar-<?php 
                                echo $repair['status'] == 'completed' ? 'success' : 
                                    ($repair['status'] == 'pending' ? 'warning' : 'info'); 
                            ?>" role="progressbar" 
                                style="width: <?php echo $total_repairs > 0 ? round(($repair['count'] / $total_repairs) * 100) : 0; ?>%">
                                <?php echo $repair['count']; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    
    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-line"></i> Monthly Repair Costs (Last 6 Months)</h4>
            </header>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Cost (KES)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_costs as $cost): ?>
                            <tr>
                                <td><?php echo $cost['month']; ?>
                                <td><?php echo number_format($cost['cost'], 2); ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fas fa-history"></i> Recent Repair Requests</h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?=base_url('hostels/repairs')?>" class="btn btn-xs btn-primary">View All</a>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Repair Code</th>
                                <th>Item/Room</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_repairs)): ?>
                                <?php foreach ($recent_repairs as $repair): ?>
                                <tr>
                                    <td><?php echo $repair['repair_code']; ?>
                                    <td>
                                        <?php 
                                        if (!empty($repair['item_name'])) {
                                            echo $repair['item_name'];
                                        } elseif (!empty($repair['room_name'])) {
                                            echo 'Room: ' . $repair['room_name'];
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    
                                    <td>
                                        <span class="label label-<?php echo $repair['priority'] == 'emergency' ? 'danger' : ($repair['priority'] == 'high' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($repair['priority']); ?>
                                        </span>
                                    
                                    <td>
                                        <span class="label label-<?php echo $repair['status'] == 'completed' ? 'success' : ($repair['status'] == 'pending' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($repair['status']); ?>
                                        </span>
                                    
                                    <td><?php echo date('d M Y', strtotime($repair['reported_date'])); ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">No repair records found</div></td>
                                <tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
    
    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fas fa-box"></i> Recently Added Items</h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?=base_url('hostels/inventory_items')?>" class="btn btn-xs btn-primary">View All</a>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Condition</th>
                                <th>Date Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_items)): ?>
                                <?php foreach ($recent_items as $item): ?>
                                <tr>
                                    <td><?php echo $item['item_code']; ?>
                                    <td><?php echo $item['name']; ?>
                                    <td><?php echo $item['category_name']; ?>
                                    <td>
                                        <span class="label label-<?php echo $item['condition'] == 'new' ? 'success' : ($item['condition'] == 'good' ? 'info' : ($item['condition'] == 'fair' ? 'warning' : 'danger')); ?>">
                                            <?php echo ucfirst($item['condition']); ?>
                                        </span>
                                    
                                    <td><?php echo date('d M Y', strtotime($item['created_at'])); ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">No inventory items found</div></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Hostel Summary -->
<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-building"></i> Hostel & Room Summary</h4>
            </header>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h3><?php echo number_format($total_hostels); ?></h3>
                        <p class="text-muted">Total Hostels</p>
                    </div>
                    <div class="col-md-3">
                        <h3><?php echo number_format($total_rooms); ?></h3>
                        <p class="text-muted">Total Rooms</p>
                    </div>
                    <div class="col-md-3">
                        <h3><?php echo number_format($total_beds); ?></h3>
                        <p class="text-muted">Total Beds</p>
                    </div>
                    <div class="col-md-3">
                        <h3><?php echo number_format($students_in_hostel); ?></h3>
                        <p class="text-muted">Students Currently in Hostel</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>