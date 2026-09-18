<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <input type="hidden" id="current_branch_id" value="<?=$branch_id?>">
            
            <div class="tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#list" data-toggle="tab"><i class="fas fa-list-ul"></i> <?=translate('inventory_items')?></a>
                    </li>
                    <?php if (get_permission('hostel_inventory', 'is_add') || get_permission('hostel', 'is_add')): ?>
                    <li>
                        <a href="#create" data-toggle="tab"><i class="far fa-edit"></i> <?=translate('add_inventory_item')?></a>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="tab-content">
                    <!-- LIST TAB -->
                    <div id="list" class="tab-pane active">
                        <!-- Branch Filter for Superadmin -->
                        <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                        <div class="row mb-md">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?=translate('select_branch')?></label>
                                    <select class="form-control" id="branch_filter" onchange="window.location.href='<?=base_url('hostels/inventory_items')?>?branch_id='+this.value">
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
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-export">
                                <thead>
                                    <tr>
                                        <th><?=translate('sl')?></th>
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
                                        <th><?=translate('action')?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                $count = 1;
                                foreach($inventory_items as $item): 
                                ?>
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
                                            <?php 
                                            $condition_badge = '';
                                            switch($item['condition']) {
                                                case 'new': $condition_badge = '<span class="label label-success">New</span>'; break;
                                                case 'good': $condition_badge = '<span class="label label-info">Good</span>'; break;
                                                case 'fair': $condition_badge = '<span class="label label-warning">Fair</span>'; break;
                                                case 'poor': $condition_badge = '<span class="label label-danger">Poor</span>'; break;
                                                case 'damaged': $condition_badge = '<span class="label label-danger">Damaged</span>'; break;
                                                case 'scrapped': $condition_badge = '<span class="label label-default">Scrapped</span>'; break;
                                                default: $condition_badge = '<span class="label label-default">Unknown</span>';
                                            }
                                            echo $condition_badge;
                                            ?>
                                        
                                        <td>
                                            <?php 
                                            $status_badge = '';
                                            switch($item['status']) {
                                                case 'available': $status_badge = '<span class="label label-success">Available</span>'; break;
                                                case 'assigned': $status_badge = '<span class="label label-primary">Assigned</span>'; break;
                                                case 'maintenance': $status_badge = '<span class="label label-warning">Maintenance</span>'; break;
                                                case 'scrapped': $status_badge = '<span class="label label-default">Scrapped</span>'; break;
                                                case 'lost': $status_badge = '<span class="label label-danger">Lost</span>'; break;
                                                default: $status_badge = '<span class="label label-default">Unknown</span>';
                                            }
                                            echo $status_badge;
                                            ?>
                                        
                                        <td><?php echo $global_config['currency_symbol'] . number_format($item['purchase_cost'], 2); ?>
                                        <td class="action-buttons" style="min-width: 160px;">
                                            <?php $can_edit = (get_permission('hostel_inventory', 'is_edit') || get_permission('hostel', 'is_edit')); ?>
                                            <?php $can_delete = (get_permission('hostel_inventory', 'is_delete') || get_permission('hostel', 'is_delete')); ?>
                                            
                                            <!-- Edit - Always -->
                                            <?php if ($can_edit): ?>
                                            <button type="button" class="btn btn-default btn-circle icon" onclick="editInventoryItem(<?=$item['id']?>)">
                                                <i class="fas fa-pen-nib"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <!-- Assign - Available + Not Damaged -->
                                            <?php if ($item['status'] == 'available' && $item['condition'] != 'damaged' && $can_edit): ?>
                                            <button type="button" class="btn btn-primary btn-circle icon" onclick="showAssignModal(<?=$item['id']?>, '<?=htmlspecialchars($item['name'])?>')">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <!-- Return - Assigned Only -->
                                            <?php if ($item['status'] == 'assigned' && $can_edit): ?>
                                            <button type="button" class="btn btn-warning btn-circle icon" onclick="showReturnModal(<?=$item['id']?>, <?=$item['student_id']?>, '<?=htmlspecialchars($item['name'])?>')">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <!-- Delete - Not Assigned -->
                                            <?php if ($item['status'] != 'assigned' && $can_delete): ?>
                                            <button type="button" class="btn btn-danger btn-circle icon" onclick="deleteInventoryItem(<?=$item['id']?>, '<?=htmlspecialchars($item['name'])?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>

                                        
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CREATE TAB -->
                    <?php if (get_permission('hostel_inventory', 'is_add') || get_permission('hostel', 'is_add')): ?>
                    <div id="create" class="tab-pane">
                        <?php echo form_open('hostels/save_inventory_item', array('class' => 'form-horizontal form-bordered frm-submit')); ?>
                            
                            <?php if (is_superadmin_loggedin()): ?>
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('branch')?> <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <?php
                                        $arrayBranch = $this->app_lib->getSelectList('branch');
                                        echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' id='branch_id'
                                        data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
                                    ?>
                                    <span class="error"></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('item_code')?> <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="item_code" value="<?=set_value('item_code')?>">
                                    <span class="error"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('category')?> <span class="required">*</span></label>
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
                                <label class="col-md-3 control-label"><?=translate('item_name')?> <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="name" value="<?=set_value('name')?>">
                                    <span class="error"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('description')?></label>
                                <div class="col-md-6">
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('manufacturer')?></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="manufacturer">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('model_number')?></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="model_number">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('serial_number')?></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="serial_number">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('room')?></label>
                                <div class="col-md-6">
                                    <select name="room_id" class="form-control">
                                        <option value=""><?=translate('select')?></option>
                                        <?php foreach($rooms as $room): ?>
                                        <option value="<?=$room['id']?>"><?=$room['name']?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('condition')?></label>
                                <div class="col-md-6">
                                    <select name="condition" class="form-control">
                                        <option value="new">New</option>
                                        <option value="good">Good</option>
                                        <option value="fair">Fair</option>
                                        <option value="poor">Poor</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('purchase_date')?></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control datepicker" name="purchase_date" value="<?=date('Y-m-d')?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('purchase_cost')?></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="purchase_cost" value="0.00">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('supplier')?></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="supplier">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('warranty_expiry')?></label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control datepicker" name="warranty_expiry">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('notes')?></label>
                                <div class="col-md-6">
                                    <textarea class="form-control" name="notes" rows="2"></textarea>
                                </div>
                            </div>
                            
                            <footer class="panel-footer">
                                <div class="row">
                                    <div class="col-md-offset-3 col-md-2">
                                        <button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?=translate('edit_inventory_item')?></h4>
            </div>
            <?php echo form_open('hostels/save_inventory_item', array('id' => 'editForm', 'method' => 'post')); ?>
                <input type="hidden" name="item_id" id="edit_item_id">
                <input type="hidden" name="branch_id" id="edit_branch_id" value="<?=$branch_id?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?=translate('item_code')?> <span class="required">*</span></label>
                        <input type="text" class="form-control" name="item_code" id="edit_item_code" readonly>
                    </div>
                    <div class="form-group">
                        <label><?=translate('category')?> <span class="required">*</span></label>
                        <select name="category_id" class="form-control" id="edit_category_id">
                            <option value=""><?=translate('select')?></option>
                            <?php foreach($categories as $cat): ?>
                            <option value="<?=$cat['id']?>"><?=$cat['name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('item_name')?> <span class="required">*</span></label>
                        <input type="text" class="form-control" name="name" id="edit_name">
                    </div>
                    <div class="form-group">
                        <label><?=translate('description')?></label>
                        <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label><?=translate('manufacturer')?></label>
                        <input type="text" class="form-control" name="manufacturer" id="edit_manufacturer">
                    </div>
                    <div class="form-group">
                        <label><?=translate('model_number')?></label>
                        <input type="text" class="form-control" name="model_number" id="edit_model_number">
                    </div>
                    <div class="form-group">
                        <label><?=translate('serial_number')?></label>
                        <input type="text" class="form-control" name="serial_number" id="edit_serial_number">
                    </div>
                    <div class="form-group">
                        <label><?=translate('room')?></label>
                        <select name="room_id" class="form-control" id="edit_room_id">
                            <option value=""><?=translate('select')?></option>
                            <?php foreach($rooms as $room): ?>
                            <option value="<?=$room['id']?>"><?=$room['name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('condition')?></label>
                        <select name="condition" class="form-control" id="edit_condition">
                            <option value="new">New</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('purchase_date')?></label>
                        <input type="text" class="form-control datepicker" name="purchase_date" id="edit_purchase_date">
                    </div>
                    <div class="form-group">
                        <label><?=translate('purchase_cost')?></label>
                        <input type="text" class="form-control" name="purchase_cost" id="edit_purchase_cost">
                    </div>
                    <div class="form-group">
                        <label><?=translate('supplier')?></label>
                        <input type="text" class="form-control" name="supplier" id="edit_supplier">
                    </div>
                    <div class="form-group">
                        <label><?=translate('warranty_expiry')?></label>
                        <input type="text" class="form-control datepicker" name="warranty_expiry" id="edit_warranty_expiry">
                    </div>
                    <div class="form-group">
                        <label><?=translate('notes')?></label>
                        <textarea class="form-control" name="notes" id="edit_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><?=translate('update')?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('cancel')?></button>
                </div>
            <?php echo form_close();?>
        </div>
    </div>
</div>
<!-- Assign Item Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fas fa-user-plus"></i> <?=translate('assign_item_to_student')?></h4>
            </div>
            <?php echo form_open('hostels/assign_inventory_item', array('class' => 'frm-submit')); ?>
                <input type="hidden" name="item_id" id="assign_item_id">
                <input type="hidden" name="branch_id" id="assign_branch_id" value="<?=$branch_id?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?=translate('item')?></label>
                        <input type="text" class="form-control" id="assign_item_name" readonly>
                    </div>
                    <div class="form-group">
                        <label><?=translate('student')?> <span class="required">*</span></label>
                        <select name="student_id" class="form-control" required>
                            <option value=""><?=translate('select')?></option>
                            <?php 
                            // Get branch ID based on user role
                            if (is_superadmin_loggedin()) {
                                $branchID = $this->session->userdata('selected_inventory_branch');
                                if (empty($branchID)) {
                                    $branchID = $this->input->get('branch_id') ?: 1;
                                }
                            } else {
                                $branchID = $this->application_model->get_branch_id();
                            }
                            
                            // Build query
                            $this->db->select('s.id, CONCAT(s.first_name, " ", s.last_name) as name, s.register_no, s.room_id');
                            $this->db->from('student s');
                            $this->db->join('enroll e', 'e.student_id = s.id');
                            $this->db->where('s.hostel_id !=', 0);
                            $this->db->where('s.hostel_id IS NOT NULL');
                            $this->db->where('e.session_id', get_session_id());
                            $this->db->where('s.branch_id', $branchID);
                            $this->db->order_by('s.first_name', 'ASC');
                            
                            $students = $this->db->get()->result_array();
                            
                            if(empty($students)):
                            ?>
                            <option value=""><?=translate('no_students_found')?> - <?=translate('please_allocate_students_to_hostel_first')?></option>
                            <?php 
                            else:
                                foreach($students as $student): 
                            ?>
                            <option value="<?=$student['id']?>">
                                <?=$student['name']?> (<?=$student['register_no']?>) - Room: <?=$student['room_id']?>
                            </option>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('condition_on_assign')?></label>
                        <select name="condition" class="form-control" required>
                            <option value="new">New</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><?=translate('assign')?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('cancel')?></button>
                </div>
            <?php echo form_close();?>
        </div>
    </div>
</div>

<!-- Return Item Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fas fa-undo-alt"></i> <?=translate('return_item')?></h4>
            </div>
            <?php echo form_open('hostels/return_inventory_item', array('class' => 'frm-submit')); ?>
                <input type="hidden" name="item_id" id="return_item_id">
                <input type="hidden" name="student_id" id="return_student_id">
                <input type="hidden" name="branch_id" id="return_branch_id" value="<?=$branch_id?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?=translate('item')?></label>
                        <input type="text" class="form-control" id="return_item_name" readonly>
                    </div>
                    <div class="form-group">
                        <label><?=translate('return_condition')?> <span class="required">*</span></label>
                        <select name="return_condition" class="form-control" required>
                            <option value=""><?=translate('select')?></option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="damaged">Damaged</option>
                            <option value="lost">Lost</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('damage_notes')?></label>
                        <textarea name="damage_notes" class="form-control" rows="3" placeholder="Describe any damage to the item"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><?=translate('return')?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('cancel')?></button>
                </div>
            <?php echo form_close();?>
        </div>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var currentBranchId = '<?=$branch_id?>';

$(document).ready(function() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });
});

function editInventoryItem(id) {
    $.ajax({
        url: base_url + 'hostels/get_inventory_item/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#edit_item_id').val(data.id);
            $('#edit_item_code').val(data.item_code);
            $('#edit_category_id').val(data.category_id).trigger('change');
            $('#edit_name').val(data.name);
            $('#edit_description').val(data.description);
            $('#edit_manufacturer').val(data.manufacturer);
            $('#edit_model_number').val(data.model_number);
            $('#edit_serial_number').val(data.serial_number);
            $('#edit_room_id').val(data.room_id).trigger('change');
            $('#edit_condition').val(data.condition).trigger('change');
            $('#edit_purchase_date').val(data.purchase_date);
            $('#edit_purchase_cost').val(data.purchase_cost);
            $('#edit_supplier').val(data.supplier);
            $('#edit_warranty_expiry').val(data.warranty_expiry);
            $('#edit_notes').val(data.notes);
            // Set branch for superadmin
            if ($('#edit_branch_id').length) {
                $('#edit_branch_id').val(data.branch_id || currentBranchId);
            }
            $('#editModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('Error loading item:', error);
            alert('Failed to load item details');
        }
    });
}

function showAssignModal(itemId, itemName) {
    $('#assign_item_id').val(itemId);
    $('#assign_item_name').val(itemName);
    // Get current branch from filter or default
    var branchId = $('#branch_filter').val() || currentBranchId;
    $('#assign_branch_id').val(branchId);
    $('#assignModal').modal('show');
}

function showReturnModal(itemId, studentId, itemName) {
    $('#return_item_id').val(itemId);
    $('#return_student_id').val(studentId);
    $('#return_item_name').val(itemName);
    // Get current branch from filter or default
    var branchId = $('#branch_filter').val() || currentBranchId;
    $('#return_branch_id').val(branchId);
    $('#returnModal').modal('show');
}

function deleteInventoryItem(id, name) {
    if (confirm("Are you sure you want to delete " + name + "? This action cannot be undone.")) {
        window.location.href = base_url + 'hostels/delete_inventory_item/' + id;
    }
}

// Handle Edit Form Submission
$(document).on('submit', '#editForm', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log('Edit form submitted');
    
    var form = $(this);
    var submitBtn = form.find('button[type="submit"]');
    var originalText = submitBtn.html();
    
    // Disable button
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
    
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
            console.log('Edit response:', response);
            submitBtn.prop('disabled', false).html(originalText);
            
            if (response.status === 'success') {
                alert(response.message);
                $('#editModal').modal('hide');
                location.reload();
            } else {
                var errorMsg = response.message || 'Update failed';
                if (response.error) {
                    errorMsg = Object.values(response.error).join('\n');
                }
                alert('Error: ' + errorMsg);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            submitBtn.prop('disabled', false).html(originalText);
            alert('Server error: ' + error);
        }
    });
    
    return false;
});
</script>