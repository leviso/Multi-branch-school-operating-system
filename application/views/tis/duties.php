<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-clipboard-list"></i> <?=translate('my_duties')?></h4>
            </header>
            <div class="panel-body">
                <!-- Duty Statistics Cards -->
                <div class="row mb-lg">
                    <div class="col-md-3">
                        <div class="panel panel-default bg-warning" style="background: linear-gradient(135deg, #ffc107 0%, #f6d365 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $stats['pending']; ?></h2>
                                <p class="mb-none"><i class="fas fa-clock"></i> <?=translate('pending')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default bg-success" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $stats['completed']; ?></h2>
                                <p class="mb-none"><i class="fas fa-check-circle"></i> <?=translate('completed')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default bg-danger" style="background: linear-gradient(135deg, #dc3545 0%, #fa709a 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $stats['overdue']; ?></h2>
                                <p class="mb-none"><i class="fas fa-exclamation-triangle"></i> <?=translate('overdue')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default bg-info" style="background: linear-gradient(135deg, #17a2b8 0%, #4facfe 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $total_points; ?></h2>
                                <p class="mb-none"><i class="fas fa-star"></i> <?=translate('total_points')?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?=translate('duty_name')?></th>
                                <th><?=translate('description')?></th>
                                <th><?=translate('assigned_date')?></th>
                                <th><?=translate('due_date')?></th>
                                <th><?=translate('points')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            foreach($duties as $duty): 
                            ?>
                            <tr class="<?php echo ($duty->due_date < date('Y-m-d') && $duty->status == 'pending') ? 'danger' : ''; ?>">
                                <td><?php echo $count++; ?></td>
                                <td><strong><?php echo htmlspecialchars($duty->duty_name, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo nl2br(htmlspecialchars($duty->description, ENT_QUOTES, 'UTF-8')); ?></td>
                                <td><?php echo date('d M Y', strtotime($duty->assigned_date)); ?></td>
                                <td>
                                    <?php echo date('d M Y', strtotime($duty->due_date)); ?>
                                    <?php if($duty->due_date < date('Y-m-d') && $duty->status == 'pending'): ?>
                                    <span class="label label-danger ml-sm">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge"><?php echo $duty->points; ?></span></td>
                                <td>
                                    <?php
                                    $status_class = 'warning';
                                    if($duty->status == 'completed') $status_class = 'success';
                                    elseif($duty->status == 'in_progress') $status_class = 'info';
                                    elseif($duty->status == 'cancelled') $status_class = 'default';
                                    ?>
                                    <span class="label label-<?php echo $status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $duty->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($duty->status == 'pending'): ?>
                                    <button type="button" class="btn btn-xs btn-success" onclick="updateDutyStatus(<?php echo $duty->id; ?>, 'completed')">
                                        <i class="fas fa-check"></i> Complete
                                    </button>
                                    <button type="button" class="btn btn-xs btn-info" onclick="updateDutyStatus(<?php echo $duty->id; ?>, 'in_progress')">
                                        <i class="fas fa-play"></i> Start
                                    </button>
                                    <?php elseif($duty->status == 'in_progress'): ?>
                                    <button type="button" class="btn btn-xs btn-success" onclick="updateDutyStatus(<?php echo $duty->id; ?>, 'completed')">
                                        <i class="fas fa-check"></i> Complete
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($duties)): ?>
                            <tr>
                                <td colspan="8" class="text-center"><?=translate('no_duties_assigned')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<style>
.mb-lg { margin-bottom: 20px; }
.ml-sm { margin-left: 5px; }
</style>

<script>
function updateDutyStatus(duty_id, status) {
    if(confirm('Update duty status to ' + status + '?')) {
        $.ajax({
            url: '<?php echo base_url("employee/update_duty_status"); ?>',
            type: 'POST',
            data: {duty_id: duty_id, status: status},
            dataType: 'json',
            success: function(response) {
                if(response.status == 'success') {
                    location.reload();
                } else {
                    alert(response.message || 'Error updating status');
                }
            },
            error: function() {
                alert('Server error. Please try again.');
            }
        });
    }
}
</script>