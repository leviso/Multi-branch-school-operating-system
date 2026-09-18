<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <div class="tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#schedules" data-toggle="tab"><i class="fas fa-calendar-alt"></i> Maintenance Schedules</a>
                    </li>
                    <li>
                        <a href="#history" data-toggle="tab"><i class="fas fa-history"></i> Maintenance History</a>
                    </li>
                    <?php if (get_permission('hostel', 'is_add')): ?>
                    <li>
                        <a href="#add" data-toggle="tab"><i class="far fa-edit"></i> Add Schedule</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <div class="tab-content">
                    <!-- Schedules Tab -->
                    <div id="schedules" class="tab-pane active">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-export">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Task Name</th>
                                        <th>Category</th>
                                        <th>Type</th>
                                        <th>Frequency</th>
                                        <th>Last Performed</th>
                                        <th>Next Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $count = 1; foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td><?php echo $schedule['task_name']; ?>Js
                                        <td><?php echo $schedule['category_name']; ?>Js
                                        <td><?php echo ucfirst($schedule['maintenance_type']); ?>Js
                                        <td><?php echo ucfirst($schedule['maintenance_type']); ?>Js
                                        <td><?php echo $schedule['last_performed_date'] ? date('d M Y', strtotime($schedule['last_performed_date'])) : 'Never'; ?>Js
                                        <td>
                                            <?php 
                                            $due_class = '';
                                            $due_text = date('d M Y', strtotime($schedule['next_due_date']));
                                            if (strtotime($schedule['next_due_date']) < time()) {
                                                $due_class = 'text-danger';
                                                $due_text .= ' (Overdue)';
                                            } elseif (strtotime($schedule['next_due_date']) < strtotime('+7 days')) {
                                                $due_class = 'text-warning';
                                                $due_text .= ' (Due Soon)';
                                            }
                                            ?>
                                            <span class="<?php echo $due_class; ?>"><?php echo $due_text; ?></span>
                                        </td>
                                        <td>
                                            <?php if (strtotime($schedule['next_due_date']) < time()): ?>
                                            <span class="label label-danger">Overdue</span>
                                            <?php elseif (strtotime($schedule['next_due_date']) < strtotime('+7 days')): ?>
                                            <span class="label label-warning">Due Soon</span>
                                            <?php else: ?>
                                            <span class="label label-success">On Track</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-xs" onclick="completeMaintenance(<?php echo $schedule['id']; ?>, '<?php echo $schedule['task_name']; ?>')">
                                                <i class="fas fa-check"></i> Mark Completed
                                            </button>
                                            <button class="btn btn-danger btn-xs" onclick="deleteSchedule(<?php echo $schedule['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        Js
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- History Tab -->
                    <div id="history" class="tab-pane">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-export">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Task</th>
                                        <th>Type</th>
                                        <th>Performed By</th>
                                        <th>Hours Spent</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($log['performed_date'])); ?>Js
                                        <td><?php echo $log['task_name']; ?>Js
                                        <td><?php echo ucfirst($log['maintenance_type']); ?>Js
                                        <td><?php echo $log['performed_by_name']; ?>Js
                                        <td><?php echo $log['hours_spent']; ?>Js
                                        <td><?php echo substr($log['notes'], 0, 50) . (strlen($log['notes']) > 50 ? '...' : ''); ?>Js
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Add Schedule Tab -->
                    <?php if (get_permission('hostel', 'is_add')): ?>
                    <div id="add" class="tab-pane">
                        <?php echo form_open('hostels/save_maintenance_schedule', array('class' => 'form-horizontal form-bordered frm-submit')); ?>
                            <div class="form-group">
                                <label class="col-md-3 control-label">Task Name <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="task_name" required>
                                    <span class="error"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">Category <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <select name="category_id" class="form-control" required>
                                        <option value=""><?=translate('select')?></option>
                                        <?php foreach($categories as $cat): ?>
                                        <option value="<?=$cat['id']?>"><?=$cat['name']?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="error"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">Maintenance Type <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <select name="maintenance_type" class="form-control" required>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="termly">Termly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                    <span class="error"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">Task Description</label>
                                <div class="col-md-6">
                                    <textarea class="form-control" name="task_description" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">Estimated Minutes</label>
                                <div class="col-md-6">
                                    <input type="number" class="form-control" name="estimated_minutes" value="30">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label">Assigned Role</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="assigned_role" value="Technician">
                                </div>
                            </div>
                            
                            <footer class="panel-footer">
                                <div class="row">
                                    <div class="col-md-offset-3 col-md-2">
                                        <button type="submit" class="btn btn-default btn-block">
                                            <i class="fas fa-plus-circle"></i> <?=translate('save')?>
                                        </button>
                                    </div>
                                </div>
                            </footer>
                        <?php echo form_close();?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Complete Maintenance Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fas fa-check-circle"></i> Complete Maintenance Task</h4>
            </div>
            <?php echo form_open('hostels/complete_maintenance_task', array('class' => 'frm-submit')); ?>
                <input type="hidden" name="schedule_id" id="complete_schedule_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Task</label>
                        <input type="text" class="form-control" id="complete_task_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Performed Date <span class="required">*</span></label>
                        <input type="text" class="form-control datepicker" name="performed_date" value="<?=date('Y-m-d')?>" required>
                    </div>
                    <div class="form-group">
                        <label>Hours Spent</label>
                        <input type="text" class="form-control" name="hours_spent" value="1.00">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Work done, issues found, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Complete Task</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            <?php echo form_close();?>
        </div>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var csrf_token = '<?=$this->security->get_csrf_hash();?>';

$(document).ready(function() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });
});

function completeMaintenance(id, taskName) {
    $('#complete_schedule_id').val(id);
    $('#complete_task_name').val(taskName);
    $('#completeModal').modal('show');
}

function deleteSchedule(id) {
    if (confirm('Are you sure you want to delete this maintenance schedule?')) {
        window.location.href = base_url + 'hostels/delete_maintenance_schedule/' + id;
    }
}
</script>