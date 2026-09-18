<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <div class="tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#list" data-toggle="tab"><i class="fas fa-wrench"></i> <?=translate('repair_requests')?></a>
                    </li>
                    <?php if (get_permission('hostel_repairs', 'is_add') || get_permission('hostel', 'is_add')): ?>
                    <li>
                        <a href="#create" data-toggle="tab"><i class="far fa-edit"></i> <?=translate('new_repair_request')?></a>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="tab-content">
                    <!-- LIST TAB -->
                    <div id="list" class="tab-pane active">
                        <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                        <div class="row mb-md">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?=translate('select_branch')?></label>
                                    <select class="form-control" id="branch_filter" onchange="window.location.href='<?=base_url('hostels/repairs')?>?branch_id='+this.value">
                                        <option value=""><?=translate('select')?></option>
                                        <?php foreach($branches as $branch): ?>
                                        <option value="<?=$branch['id']?>" <?=($branch_id == $branch['id']) ? 'selected' : ''?>>
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
                                        <th>#</th>
                                        <?php if (is_superadmin_loggedin()): ?>
                                        <th><?=translate('branch')?></th>
                                        <?php endif; ?>
                                        <th><?=translate('repair_code')?></th>
                                        <th><?=translate('item')?>/<?=translate('room')?></th>
                                        <th><?=translate('issue')?></th>
                                        <th><?=translate('priority')?></th>
                                        <th><?=translate('status')?></th>
                                        <th><?=translate('reported_by')?></th>
                                        <th><?=translate('reported_date')?></th>
                                        <th><?=translate('assigned_to')?></th>
                                        <th><?=translate('action')?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                $count = 1;
                                foreach($repairs as $repair): 
                                ?>
                                    <tr>
                                        <td><?php echo $count++; ?>
                                        <?php if (is_superadmin_loggedin()): ?>
                                        <td><?php echo $repair['branch_name']; ?>
                                        <?php endif; ?>
                                        <td><?php echo $repair['repair_code']; ?>
                                        <td>
                                        <?php 
                                        $display_text = '';
                                        
                                        // Show item if exists
                                        if(!empty($repair['item_name'])) {
                                            $display_text .= '<strong>' . $repair['item_name'] . '</strong>';
                                            if(!empty($repair['item_code'])) {
                                                $display_text .= '<br><small>Code: ' . $repair['item_code'] . '</small>';
                                            }
                                        }
                                        
                                        // Show room if exists (add separator if both exist)
                                        if(!empty($repair['room_name'])) {
                                            if(!empty($repair['item_name'])) {
                                                $display_text .= '<br><small>Room: ' . $repair['room_name'] . '</small>';
                                            } else {
                                                $display_text .= '<strong>Room:</strong> ' . $repair['room_name'];
                                            }
                                        }
                                        
                                        // Show hostel if room exists
                                        if(!empty($repair['hostel_name']) && !empty($repair['room_name'])) {
                                            $display_text .= '<br><small>Hostel: ' . $repair['hostel_name'] . '</small>';
                                        }
                                        
                                        // If nothing found
                                        if(empty($display_text)) {
                                            $display_text = 'N/A';
                                        }
                                        
                                        echo $display_text;
                                        ?>
                                    </td>
                                        
                                        <td><?php echo substr($repair['issue_description'], 0, 50) . (strlen($repair['issue_description']) > 50 ? '...' : ''); ?>
                                        <td>
                                            <?php 
                                            $priority_badge = '';
                                            switch($repair['priority']) {
                                                case 'emergency': $priority_badge = '<span class="label label-danger">Emergency</span>'; break;
                                                case 'high': $priority_badge = '<span class="label label-warning">High</span>'; break;
                                                case 'medium': $priority_badge = '<span class="label label-info">Medium</span>'; break;
                                                case 'low': $priority_badge = '<span class="label label-default">Low</span>'; break;
                                            }
                                            echo $priority_badge;
                                            ?>
                                        
                                        <td>
                                            <?php 
                                            $status_badge = '';
                                            switch($repair['status']) {
                                                case 'pending': $status_badge = '<span class="label label-warning">Pending</span>'; break;
                                                case 'approved': $status_badge = '<span class="label label-info">Approved</span>'; break;
                                                case 'assigned': $status_badge = '<span class="label label-primary">Assigned</span>'; break;
                                                case 'in_progress': $status_badge = '<span class="label label-info">In Progress</span>'; break;
                                                case 'completed': $status_badge = '<span class="label label-success">Completed</span>'; break;
                                                case 'cancelled': $status_badge = '<span class="label label-danger">Cancelled</span>'; break;
                                            }
                                            echo $status_badge;
                                            ?>
                                        
                                        <td><?php echo $repair['reported_by_name']; ?>
                                        <td><?php echo date('d M Y', strtotime($repair['reported_date'])); ?>
                                        <td>
                                            <select class="form-control input-sm" onchange="updateRepairTechnician(<?=$repair['id']?>, this.value)">
                                                <option value=""><?=translate('select')?></option>
                                                <?php foreach($technicians as $tech): ?>
                                                <option value="<?=$tech['id']?>" <?=($repair['assigned_to'] == $tech['id']) ? 'selected' : ''?>>
                                                    <?=$tech['name']?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        
                                        <td>
                                            <button type="button" class="btn btn-default btn-circle icon" onclick="viewRepairDetails(<?=$repair['id']?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-info btn-circle icon" onclick="manageParts(<?=$repair['id']?>, '<?=$repair['repair_code']?>')" title="Manage Parts">
                                                <i class="fas fa-microchip"></i>
                                            </button>
                                            <?php if ($repair['status'] != 'completed' && (get_permission('hostel_repairs', 'is_edit') || get_permission('hostel', 'is_edit'))): ?>
                                            <button type="button" class="btn btn-primary btn-circle icon" onclick="updateRepairStatus(<?=$repair['id']?>)">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                        
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CREATE TAB -->
                    <?php if (get_permission('hostel_repairs', 'is_add') || get_permission('hostel', 'is_add')): ?>
                    <div id="create" class="tab-pane">
                        <?php echo form_open('hostels/add_repair_request', array('class' => 'form-horizontal form-bordered frm-submit')); ?>
                            <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('branch')?> <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <select name="branch_id" class="form-control" required>
                                        <option value=""><?=translate('select')?></option>
                                        <?php foreach($branches as $branch): ?>
                                        <option value="<?=$branch['id']?>" <?=($branch_id == $branch['id']) ? 'selected' : ''?>>
                                            <?=$branch['name']?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="error"></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('item')?></label>
                                <div class="col-md-6">
                                    <select name="item_id" class="form-control" id="repair_item_id">
                                        <option value=""><?=translate('select_item_optional')?></option>
                                        <?php foreach($items as $item): ?>
                                        <option value="<?=$item['id']?>"><?=$item['name']?> (<?=$item['item_code']?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('room')?></label>
                                <div class="col-md-6">
                                    <select name="room_id" class="form-control">
                                        <option value=""><?=translate('select_room_optional')?></option>
                                        <?php foreach($rooms as $room): ?>
                                        <option value="<?=$room['id']?>"><?=$room['name']?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('issue_description')?> <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <textarea name="issue_description" class="form-control" rows="4" required></textarea>
                                    <span class="error"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-md-3 control-label"><?=translate('priority')?> <span class="required">*</span></label>
                                <div class="col-md-6">
                                    <select name="priority" class="form-control" required>
                                        <option value="low"><?=translate('low')?></option>
                                        <option value="medium"><?=translate('medium')?></option>
                                        <option value="high"><?=translate('high')?></option>
                                        <option value="emergency"><?=translate('emergency')?></option>
                                    </select>
                                    <span class="error"></span>
                                </div>
                            </div>
                            
                            <footer class="panel-footer">
                                <div class="row">
                                    <div class="col-md-offset-3 col-md-2">
                                        <button type="submit" class="btn btn-default btn-block">
                                            <i class="fas fa-plus-circle"></i> <?=translate('submit_request')?>
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

<!-- Repair Details Modal -->
<div class="modal fade" id="repairModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?=translate('repair_details')?></h4>
            </div>
            <div class="modal-body" id="repairDetailsContent">
                <div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('close')?></button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?=translate('update_repair_status')?></h4>
            </div>
            <?php echo form_open('hostels/update_repair_status', array('class' => 'frm-submit')); ?>
                <input type="hidden" name="repair_id" id="status_repair_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?=translate('status')?></label>
                        <select name="status" class="form-control" required>
                            <option value="pending"><?=translate('pending')?></option>
                            <option value="approved"><?=translate('approved')?></option>
                            <option value="assigned"><?=translate('assigned')?></option>
                            <option value="in_progress"><?=translate('in_progress')?></option>
                            <option value="completed"><?=translate('completed')?></option>
                            <option value="cancelled"><?=translate('cancelled')?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('notes')?></label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
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

<!-- Parts Management Modal (SINGLE COPY - FIXED) -->
<div class="modal fade" id="partsModal" tabindex="-1" role="dialog" aria-labelledby="partsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="partsModalLabel">
                    <i class="fas fa-microchip"></i> Repair Parts - <span id="parts_repair_code"></span>
                </h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="parts_repair_id">
                
                <!-- Add Part Form -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">Add Part</h4>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Part Name *</label>
                                    <input type="text" class="form-control" id="part_name" placeholder="e.g., Screw, Bulb, Lock">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Quantity *</label>
                                    <input type="number" class="form-control" id="part_quantity" value="1" min="1">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Unit Cost (KES) *</label>
                                    <input type="text" class="form-control" id="part_unit_cost" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Supplier</label>
                                    <input type="text" class="form-control" id="part_supplier" placeholder="Supplier name">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-primary btn-block" onclick="addRepairPart()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Parts List Table -->
                <div class="table-responsive mt-md">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="30%">Part Name</th>
                                <th width="10%">Quantity</th>
                                <th width="15%">Unit Cost (KES)</th>
                                <th width="15%">Total (KES)</th>
                                <th width="20%">Supplier</th>
                                <th width="10%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="parts_list_tbody">
                            <tr><td colspan="6" class="text-center">Click on a repair to load parts...<\/td><\/tr>
                        </tbody>
                        <tfoot id="parts_tfoot" style="display:none;">
                            <tr>
                                <th colspan="3" class="text-right">Total Parts Cost:</th>
                                <th colspan="3" id="total_parts_cost">KES 0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('close')?></button>
            </div>
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

function viewRepairDetails(id) {
    $('#repairDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Loading...</p></div>');
    $('#repairModal').modal('show');
    
    var branchId = $('#branch_filter').val() || '<?=$branch_id?>';
    
    $.ajax({
        url: base_url + 'hostels/get_repair_details/' + id,
        type: 'GET',
        data: { branch_id: branchId },
        success: function(data) {
            $('#repairDetailsContent').html(data);
        },
        error: function() {
            $('#repairDetailsContent').html('<div class="alert alert-danger"><?=translate('error_loading_details')?></div>');
        }
    });
}

function updateRepairStatus(id) {
    $('#status_repair_id').val(id);
    $('#statusModal').modal('show');
}

function updateRepairTechnician(repairId, technicianId) {
    $.ajax({
        url: base_url + 'hostels/assign_repair_technician',
        type: 'POST',
        data: {
            repair_id: repairId,
            technician_id: technicianId,
            csrf_test_name: csrf_token
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                toastr.success('<?=translate('technician_assigned_successfully')?>');
            } else {
                toastr.error('<?=translate('failed_to_assign_technician')?>');
            }
        },
        error: function() {
            toastr.error('<?=translate('server_error')?>');
        }
    });
}

// ========== PARTS MANAGEMENT FUNCTIONS ==========

function manageParts(repairId, repairCode) {
    console.log('Opening parts modal for repair:', repairId, repairCode);
    
    // Set values
    $('#parts_repair_id').val(repairId);
    $('#parts_repair_code').text(repairCode);
    
    // Clear form fields
    $('#part_name').val('');
    $('#part_quantity').val('1');
    $('#part_unit_cost').val('');
    $('#part_supplier').val('');
    
    // Reset table
    $('#parts_list_tbody').html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading parts...<\/td><\/tr>');
    $('#parts_tfoot').hide();
    
    // Show modal
    $('#partsModal').modal('show');
    
    // Load parts
    loadRepairParts(repairId);
}

function loadRepairParts(repairId) {
    console.log('Loading parts for repair:', repairId);
    
    $.ajax({
        url: base_url + 'hostels/get_repair_parts/' + repairId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Parts response:', response);
            var html = '';
            var totalCost = 0;
            
            if (response.length > 0) {
                for (var i = 0; i < response.length; i++) {
                    var part = response[i];
                    var partTotal = parseFloat(part.quantity) * parseFloat(part.unit_cost);
                    totalCost += partTotal;
                    
                    html += '<tr>' +
                        '<td>' + escapeHtml(part.part_name) + '<\/td>' +
                        '<td class="text-center">' + part.quantity + '<\/td>' +
                        '<td class="text-right">' + formatNumber(part.unit_cost) + '<\/td>' +
                        '<td class="text-right">' + formatNumber(partTotal) + '<\/td>' +
                        '<td>' + (part.supplier ? escapeHtml(part.supplier) : '-') + '<\/td>' +
                        '<td class="text-center"><button class="btn btn-danger btn-xs" onclick="deleteRepairPart(' + part.id + ')"><i class="fas fa-trash"></i> Delete<\/button><\/td>' +
                        '<\/tr>';
                }
                $('#parts_tfoot').show();
            } else {
                html = '<tr><td colspan="6" class="text-center">No parts added yet for this repair<\/td><\/tr>';
                $('#parts_tfoot').hide();
            }
            
            $('#parts_list_tbody').html(html);
            $('#total_parts_cost').text('KES ' + formatNumber(totalCost));
        },
        error: function(xhr, status, error) {
            console.error('Error loading parts:', error);
            $('#parts_list_tbody').html('<tr><td colspan="6" class="text-center text-danger">Error loading parts: ' + error + '<\/td><\/tr>');
            $('#parts_tfoot').hide();
        }
    });
}

function addRepairPart() {
    var repairId = $('#parts_repair_id').val();
    var partName = $('#part_name').val();
    var quantity = $('#part_quantity').val();
    var unitCost = $('#part_unit_cost').val();
    var supplier = $('#part_supplier').val();
    
    // Validation
    if (!partName) {
        alert('Please enter part name');
        $('#part_name').focus();
        return;
    }
    if (!quantity || quantity < 1) {
        alert('Please enter valid quantity');
        $('#part_quantity').focus();
        return;
    }
    if (!unitCost || parseFloat(unitCost) <= 0) {
        alert('Please enter valid unit cost');
        $('#part_unit_cost').focus();
        return;
    }
    
    // Show loading on button
    var addBtn = $('#partsModal .btn-primary');
    var originalText = addBtn.html();
    addBtn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    
    $.ajax({
        url: base_url + 'hostels/add_repair_part',
        type: 'POST',
        data: {
            repair_id: repairId,
            part_name: partName,
            quantity: quantity,
            unit_cost: unitCost,
            supplier: supplier,
            '<?=$this->security->get_csrf_token_name();?>': csrf_token
        },
        dataType: 'json',
        success: function(response) {
            console.log('Add part response:', response);
            addBtn.html(originalText).prop('disabled', false);
            
            if (response.status === 'success') {
                // Clear form
                $('#part_name').val('');
                $('#part_quantity').val('1');
                $('#part_unit_cost').val('');
                $('#part_supplier').val('');
                // Reload parts list
                loadRepairParts(repairId);
                // Focus on part name for next entry
                $('#part_name').focus();
                alert('Part added successfully');
            } else {
                var errorMsg = response.message || 'Failed to add part';
                alert('Error: ' + errorMsg);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            addBtn.html(originalText).prop('disabled', false);
            alert('Server error: ' + error);
        }
    });
}

function deleteRepairPart(partId) {
    if (confirm('Are you sure you want to delete this part?')) {
        var repairId = $('#parts_repair_id').val();
        
        $.ajax({
            url: base_url + 'hostels/delete_repair_part/' + partId,
            type: 'POST',
            data: {
                '<?=$this->security->get_csrf_token_name();?>': csrf_token
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadRepairParts(repairId);
                    alert('Part deleted successfully');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Server error');
            }
        });
    }
}

function formatNumber(num) {
    if (isNaN(num)) num = 0;
    return parseFloat(num).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
</script>