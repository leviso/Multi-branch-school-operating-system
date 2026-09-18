<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li class="active">
				<a href="#list" data-toggle="tab"><i class="fas fa-list-ul"></i> <?=translate('room_list')?></a>
			</li>
<?php if (get_permission('hostel_room', 'is_add')): ?>
			<li>
				<a href="#create" data-toggle="tab"><i class="far fa-edit"></i> <?=translate('create') . " " . translate('room')?></a>
			</li>
<?php endif; ?>
			<li>
				<a href="#inventory" data-toggle="tab"><i class="fas fa-boxes"></i> <?=translate('room_inventory')?></a>
			</li>
		</ul>
		
		<div class="tab-content">
			<!-- LIST TAB -->
			<div id="list" class="tab-pane active">
				<table class="table table-bordered table-hover table-export">
					<thead>
						<tr>
							<th><?=translate('sl')?></th>
							<?php if (is_superadmin_loggedin()): ?>
							<th><?=translate('branch')?></th>
							<?php endif; ?>
							<th><?=translate('room_name')?></th>
							<th><?=translate('hostel_name')?></th>
							<th><?=translate('category')?></th>
							<th><?=translate('no_of_beds')?></th>
							<th><?=translate('cost_per_bed')?></th>
							<th><?=translate('remarks')?></th>
							<th><?=translate('action')?></th>
						</tr>
					</thead>
					<tbody>
						<?php $count = 1; foreach($roomlist as $row): ?>
						<tr>
							<td><?php echo $count++;?></td>
							<?php if (is_superadmin_loggedin()): ?>
							<td><?php echo $row['branch_name'];?></td>
							<?php endif; ?>
							<td><?php echo $row['name'];?></td>
							<td><?php echo get_type_name_by_id('hostel', $row['hostel_id']);?></td>
							<td><?php echo get_type_name_by_id('hostel_category', $row['category_id']);?></td>
							<td><?php echo $row['no_beds'];?></td>
							<td><?php echo $global_config['currency_symbol'] . $row['bed_fee']; ?></td>
							<td><?php echo $row['remarks'];?></td>
							<td>
							<?php if (get_permission('hostel_room', 'is_edit')): ?>
								<a href="<?=base_url('hostels/edit_room/' . $row['id'])?>" class="btn btn-default btn-circle icon">
									<i class="fas fa-pen-nib"></i>
								</a>
							<?php endif; ?>
							<?php if (get_permission('hostel_room', 'is_delete')): ?>
								<?php echo btn_delete('hostels/delete_room/'. $row['id']);?>
							<?php endif; ?>
							
							<button type="button" class="btn btn-info btn-circle icon" 
									onclick="showRoomInventory(<?=$row['id']?>, '<?=htmlspecialchars($row['name'])?>')"
									title="View Room Inventory">
								<i class="fas fa-boxes"></i>
							</button>
							</td>
						</tr>
						<?php endforeach;?>
					</tbody>
				</table>
			</div>
			
			<!-- CREATE TAB -->
<?php if (get_permission('hostel_room', 'is_add')): ?>
			<div id="create" class="tab-pane">
				<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered frm-submit')); ?>
					<?php if (is_superadmin_loggedin()): ?>
						<div class="form-group">
							<label class="control-label col-md-3"><?=translate('branch')?> <span class="required">*</span></label>
							<div class="col-md-6">
								<?php
									$arrayBranch = $this->app_lib->getSelectList('branch');
									echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' data-width='100%' id='branch_id'
									data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
								?>
								<span class="error"></span>
							</div>
						</div>
					<?php endif; ?>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('room_name')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="name" value="<?=set_value('name')?>" />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('hostel_name')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
								$arrayHostel = $this->app_lib->getSelectByBranch('hostel', $branch_id, false);
								echo form_dropdown("hostel_id", $arrayHostel, set_value('hostel_id'), "class='form-control' id='hostel_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('category')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
								$arrayCategory = $this->app_lib->getSelectByBranch('hostel_category', $branch_id, false, array('type' => 'room'));
								echo form_dropdown("category_id", $arrayCategory, set_value('category_id'), "class='form-control' id='category_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('no_of_beds')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<input type="text" class="form-control" value="<?=set_value('number_of_beds')?>" name="number_of_beds" />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('cost_per_bed')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<input type="text" class="form-control" value="<?=set_value('bed_fee')?>" name="bed_fee" />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('remarks')?></label>
						<div class="col-md-6 mb-md">
							<textarea class="form-control" rows="2" name="remarks"><?=set_value('remarks')?></textarea>
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

			<!-- INVENTORY TAB -->
			<div id="inventory" class="tab-pane">
				<div class="row mb-md">
					<div class="col-md-4">
						<div class="form-group">
							<label><?=translate('select_room')?></label>
							<select class="form-control" id="inventory_room_select" onchange="loadRoomInventory(this.value)">
								<option value=""><?=translate('select')?></option>
								<?php foreach($roomlist as $row): ?>
								<option value="<?=$row['id']?>"><?=$row['name']?> (<?=get_type_name_by_id('hostel', $row['hostel_id'])?>)</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				
				<div class="table-responsive">
				<table class="table table-bordered table-hover">
					<thead>
						<tr>
							<th><?=translate('item_code')?></th>
							<th><?=translate('item_name')?></th>
							<th><?=translate('category')?></th>
							<th><?=translate('condition')?></th>
							<th><?=translate('status')?></th>
							<th><?=translate('assigned_to')?></th>
						</tr>
					</thead>
					<tbody id="room_inventory_tbody">
						<tr>
							<td colspan="6" class="text-center"><?=translate('select_room_to_view_inventory')?></td>
						</tr>
					</tbody>
				</table>
			</div>
			
			</div>
		</div>
	</div>
</section>

<!-- Room Inventory Modal -->
<div class="modal fade" id="roomInventoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fas fa-boxes"></i> <span id="modalRoomName"></span> - <?=translate('inventory')?></h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?=translate('item_code')?></th>
                                <th><?=translate('item_name')?></th>
                                <th><?=translate('category')?></th>
                                <th><?=translate('condition')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('assigned_to')?></th>
                            </tr>
                        </thead>
                        <tbody id="modalInventoryBody">
                            <td><td colspan="6" class="text-center"><?=translate('select_room_to_view_inventory')?>?</td></td>
                        </tbody>
                    </td>
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

// ========== FUNCTION TO LOAD ROOM INVENTORY IN THE TAB ==========
function loadRoomInventory(roomId) {
    console.log('loadRoomInventory called with roomId:', roomId);
    
    if (!roomId) {
        $('#room_inventory_tbody').html('<tr><td colspan="6" class="text-center"><?=translate('select_room_to_view_inventory')?></td></tr>');
        return;
    }
    
    // Show loading
    $('#room_inventory_tbody').html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
    
    $.ajax({
        url: base_url + 'hostels/get_room_inventory',
        type: 'POST',
        data: {
            room_id: roomId,
            '<?=$this->security->get_csrf_token_name();?>': csrf_token
        },
        dataType: 'json',
        success: function(response) {
            console.log('Response received:', response);
            
            var html = '';
            
            if (response && response.length > 0) {
                for (var i = 0; i < response.length; i++) {
                    var item = response[i];
                    
                    var conditionBadge = '';
                    switch(item.condition) {
                        case 'new': conditionBadge = '<span class="label label-success">New</span>'; break;
                        case 'good': conditionBadge = '<span class="label label-info">Good</span>'; break;
                        case 'fair': conditionBadge = '<span class="label label-warning">Fair</span>'; break;
                        case 'poor': conditionBadge = '<span class="label label-danger">Poor</span>'; break;
                        case 'damaged': conditionBadge = '<span class="label label-danger">Damaged</span>'; break;
                        default: conditionBadge = '<span class="label label-default">' + (item.condition || 'N/A') + '</span>';
                    }
                    
                    var statusBadge = '';
                    switch(item.status) {
                        case 'available': statusBadge = '<span class="label label-success">Available</span>'; break;
                        case 'assigned': statusBadge = '<span class="label label-primary">Assigned</span>'; break;
                        case 'maintenance': statusBadge = '<span class="label label-warning">Maintenance</span>'; break;
                        default: statusBadge = '<span class="label label-default">' + (item.status || 'N/A') + '</span>';
                    }
                    
                    // FIXED: Proper table row with closing tags
                    html += '<tr>' +
                        '<td>' + (item.item_code || 'N/A') + '</td>' +
                        '<td>' + (item.name || 'N/A') + '</td>' +
                        '<td>' + (item.category_name || 'N/A') + '</td>' +
                        '<td>' + conditionBadge + '</td>' +
                        '<td>' + statusBadge + '</td>' +
                        '<td>' + (item.student_name || 'Available') + '</td>' +
                        '</tr>';
                }
            } else {
                html = '<tr><td colspan="6" class="text-center"><?=translate('no_information_available')?></td></tr>';
            }
            
            console.log('Generated HTML:', html);
            $('#room_inventory_tbody').html(html);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            $('#room_inventory_tbody').html('<tr><td colspan="6" class="text-center text-danger">Error loading inventory: ' + error + '</td></tr>');
        }
    });
}

// ========== FUNCTION TO SHOW MODAL WITH ROOM INVENTORY ==========
function showRoomInventory(roomId, roomName) {
    console.log('showRoomInventory called - Room ID:', roomId, 'Room Name:', roomName);
    
    if (!roomId) {
        alert('No room selected');
        return;
    }
    
    $('#modalRoomName').text(roomName);
    $('#roomInventoryModal').modal('show');
    $('#modalInventoryBody').html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
    
    $.ajax({
        url: base_url + 'hostels/get_room_inventory',
        type: 'POST',
        data: {
            room_id: roomId,
            '<?=$this->security->get_csrf_token_name();?>': csrf_token
        },
        dataType: 'json',
        success: function(response) {
            console.log('Modal response:', response);
            var html = '';
            
            if (response.length > 0) {
                $.each(response, function(i, item) {
                    var conditionBadge = '';
                    switch(item.condition) {
                        case 'new': conditionBadge = '<span class="label label-success">New</span>'; break;
                        case 'good': conditionBadge = '<span class="label label-info">Good</span>'; break;
                        case 'fair': conditionBadge = '<span class="label label-warning">Fair</span>'; break;
                        case 'poor': conditionBadge = '<span class="label label-danger">Poor</span>'; break;
                        case 'damaged': conditionBadge = '<span class="label label-danger">Damaged</span>'; break;
                        default: conditionBadge = '<span class="label label-default">' + (item.condition || 'N/A') + '</span>';
                    }
                    
                    var statusBadge = '';
                    switch(item.status) {
                        case 'available': statusBadge = '<span class="label label-success">Available</span>'; break;
                        case 'assigned': statusBadge = '<span class="label label-primary">Assigned</span>'; break;
                        case 'maintenance': statusBadge = '<span class="label label-warning">Maintenance</span>'; break;
                        default: statusBadge = '<span class="label label-default">' + (item.status || 'N/A') + '</span>';
                    }
                    
                    html += '<tr>' +
                        '<td>' + (item.item_code || 'N/A') + '</td>' +
                        '<td>' + (item.name || 'N/A') + '</td>' +
                        '<td>' + (item.category_name || 'N/A') + '</td>' +
                        '<td>' + conditionBadge + '</td>' +
                        '<td>' + statusBadge + '</td>' +
                        '<td>' + (item.student_name || 'Available') + '</td>' +
                        '</tr>';
                });
            } else {
                html = '<tr><td colspan="6" class="text-center"><?=translate('no_information_available')?></td></tr>';
            }
            $('#modalInventoryBody').html(html);
        },
        error: function(xhr, status, error) {
            console.error('Modal AJAX Error:', error);
            $('#modalInventoryBody').html('<tr><td colspan="6" class="text-center text-danger">Error loading inventory: ' + error + '</td></tr>');
        }
    });
}

$(document).ready(function () {
    $('#branch_id').on("change", function(){    
        var branchID = $(this).val();
        $.ajax({
            url: base_url + 'ajax/getDataByBranch',
            type: 'POST',
            data: {
                table: 'hostel',
                branch_id: branchID
            },
            success: function (data) {
                $('#hostel_id').html(data);
            }
        });

        $.ajax({
            url: base_url + 'hostels/getCategoryByBranch',
            type: 'POST',
            data:{
                branch_id: branchID,
                type: 'room'
            },
            success: function (data) {
                $('#category_id').html(data);
            }
        });
    });
});
</script>