<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fas fa-clipboard-list"></i> <?=translate('room_inspections')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?=base_url('hostels/create_inspection')?>" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> <?=translate('new_inspection')?>
                        </a>
                        <a href="<?=base_url('hostels/inspection_reports')?>" class="btn btn-info">
                            <i class="fas fa-chart-bar"></i> <?=translate('reports')?>
                        </a>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <!-- Statistics Cards -->
              
            <div class="row mb-md">
                <div class="col-md-3">
                    <div class="panel panel-default">
                        <div class="panel-body text-center">
                            <h3><?php echo isset($stats['total']) ? number_format($stats['total']) : '0'; ?></h3>
                            <p class="text-muted">Total Inspections</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel panel-default">
                        <div class="panel-body text-center">
                            <h3><?php echo isset($stats['average_score']) ? number_format($stats['average_score']) : '0'; ?>%</h3>
                            <p class="text-muted">Average Score</p>
                        </div>
                    </div>
                </div>
                <?php if (isset($stats['by_type']) && !empty($stats['by_type'])): ?>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="row">
                                <?php foreach($stats['by_type'] as $type): ?>
                                <div class="col-md-6">
                                    <strong><?php echo ucfirst($type['inspection_type']); ?>:</strong>
                                    <span class="pull-right"><?php echo $type['count']; ?></span>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar progress-bar-primary" style="width: <?php echo min(100, ($type['count'] / max(1, $stats['total']) * 100)); ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
                
                <!-- Filter Form -->
                <form method="get" class="form-inline mb-md">
                    <div class="form-group">
                        <label><?=translate('room')?></label>
                        <select name="room_id" class="form-control">
                            <option value=""><?=translate('all_rooms')?></option>
                            <?php foreach($rooms as $room): ?>
                            <option value="<?=$room['id']?>" <?=($this->input->get('room_id') == $room['id']) ? 'selected' : ''?>>
                                <?=$room['name']?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('type')?></label>
                        <select name="inspection_type" class="form-control">
                            <option value=""><?=translate('all_types')?></option>
                            <option value="daily" <?=($this->input->get('inspection_type') == 'daily') ? 'selected' : ''?>>Daily</option>
                            <option value="weekly" <?=($this->input->get('inspection_type') == 'weekly') ? 'selected' : ''?>>Weekly</option>
                            <option value="monthly" <?=($this->input->get('inspection_type') == 'monthly') ? 'selected' : ''?>>Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="text" name="start_date" class="form-control datepicker" placeholder="Start Date" value="<?=$this->input->get('start_date')?>">
                    </div>
                    <div class="form-group">
                        <input type="text" name="end_date" class="form-control datepicker" placeholder="End Date" value="<?=$this->input->get('end_date')?>">
                    </div>
                    <button type="submit" class="btn btn-default">Filter</button>
                    <a href="<?=base_url('hostels/inspections')?>" class="btn btn-default">Reset</a>
                </form>
                
                <!-- Inspections Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th>Inspection Code</th>
                                <th>Room</th>
                                <th>Hostel</th>
                                <th>Type</th>
                                <th>Inspector</th>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach($inspections as $insp): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo get_type_name_by_id('branch', $insp['branch_id']); ?></td>
                                <?php endif; ?>
                                <td><?php echo $insp['inspection_code']; ?></td>
                                <td><?php echo $insp['room_name']; ?></td>
                                <td><?php echo $insp['hostel_name']; ?></td>
                                <td>
                                    <span class="label label-<?php 
                                        echo $insp['inspection_type'] == 'daily' ? 'info' : 
                                            ($insp['inspection_type'] == 'weekly' ? 'warning' : 'primary'); 
                                    ?>">
                                        <?php echo ucfirst($insp['inspection_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo $insp['inspector_name']; ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($insp['inspection_date'])); ?></td>
                                <td>
                                    <span class="label label-<?php 
                                        echo $insp['overall_score'] >= 75 ? 'success' : 
                                            ($insp['overall_score'] >= 60 ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo number_format($insp['overall_score'], 1); ?>%
                                    </span>
                                </td>
                                <!-- ========== REPLACE THE GRADE COLUMN HERE ========== -->
                                <td class="grade-cell">
                                <?php 
                                $cbc_grade = $insp['grade'];
                                $grade_class = '';
                                
                                if($cbc_grade == 'EE1' || $cbc_grade == 'EE2') {
                                    $grade_class = 'success';
                                    $grade_level = 'Exceeding Expectations';
                                } elseif($cbc_grade == 'ME1' || $cbc_grade == 'ME2') {
                                    $grade_class = 'info';
                                    $grade_level = 'Meeting Expectations';
                                } elseif($cbc_grade == 'AE1' || $cbc_grade == 'AE2') {
                                    $grade_class = 'warning';
                                    $grade_level = 'Approaching Expectations';
                                } elseif($cbc_grade == 'BE1' || $cbc_grade == 'BE2') {
                                    $grade_class = 'danger';
                                    $grade_level = 'Below Expectations';
                                } else {
                                    $grade_class = 'default';
                                    $grade_level = '';
                                }
                                ?>
                                <span class="label label-<?php echo $grade_class; ?>" style="display: inline-block; white-space: normal;">
                                    <?php echo $cbc_grade; ?><br>
                                    <small><?php echo $grade_level; ?></small>
                                </span>
                            </td>
                                <!-- ========== END GRADE COLUMN ========== -->
                                <td>
                                    <a href="<?=base_url('hostels/view_inspection/'.$insp['id'])?>" class="btn btn-default btn-circle icon">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (get_permission('hostel', 'is_delete')): ?>
                                    <a href="<?=base_url('hostels/delete_inspection/'.$insp['id'])?>" class="btn btn-danger btn-circle icon" onclick="return confirm('Delete this inspection?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
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
$('.datepicker').datepicker({
    format: 'yyyy-mm-dd',
    autoclose: true
});
</script>