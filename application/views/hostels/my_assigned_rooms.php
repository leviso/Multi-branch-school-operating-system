<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title">
                            <i class="fas fa-door-open"></i> <?=translate('my_assigned_rooms')?>
                        </h4>
                        <p class="mt-sm text-muted">Welcome, <?php echo $staff_name; ?></p>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-download"></i> <?=translate('export')?> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="#" onclick="exportTableToExcel()"><i class="fas fa-file-excel"></i> Excel</a></li>
                                <li><a href="#" onclick="window.print()"><i class="fas fa-print"></i> Print</a></li>
                                <li><a href="#" onclick="copyTableToClipboard()"><i class="fas fa-copy"></i> Copy</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <?php if (empty($assigned_rooms)): ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?=translate('no_rooms_assigned')?>
                        <br><small>Please contact the administrator to assign rooms to you.</small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="assignedRoomsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?=translate('hostel')?></th>
                                    <th><?=translate('room')?></th>
                                    <th><?=translate('occupancy')?></th>
                                    <th><?=translate('students')?></th>
                                    <th><?=translate('pending_repairs')?></th>
                                    <th><?=translate('last_inspection')?></th>
                                    <th><?=translate('action')?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; foreach($assigned_rooms as $room): ?>
                                <tr id="room_row_<?php echo $room['room_id']; ?>">
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo $room['hostel_name']; ?></td>
                                    <td><?php echo $room['room_name']; ?> (<?php echo $room['room_number'] ?? 'N/A'; ?>)</td>
                                    <td>
                                        <?php 
                                        $occupancy_percent = $room['total_beds'] > 0 ? round(($room['occupancy'] / $room['total_beds']) * 100) : 0;
                                        ?>
                                        <div class="progress progress-sm" style="min-width: 100px;">
                                            <div class="progress-bar progress-bar-<?php 
                                                echo $occupancy_percent >= 90 ? 'danger' : ($occupancy_percent >= 70 ? 'warning' : 'success'); 
                                            ?>" style="width: <?php echo $occupancy_percent; ?>%">
                                                <?php echo $room['occupancy']; ?>/<?php echo $room['total_beds']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($room['students'])): ?>
                                            <ul class="list-unstyled mb-none">
                                            <?php foreach($room['students'] as $student): ?>
                                                <li>
                                                    <i class="fas fa-user-graduate"></i> 
                                                    <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                                                    <br><small class="text-muted">Reg: <?php echo $student['register_no']; ?></small>
                                                </li>
                                            <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="text-muted">No students assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($room['pending_repairs'] > 0): ?>
                                            <span class="label label-danger"><?php echo $room['pending_repairs']; ?> pending</span>
                                        <?php else: ?>
                                            <span class="label label-success">All resolved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($room['last_inspection']): ?>
                                            <div>
                                                <span class="label label-<?php 
                                                    echo $room['last_inspection']->overall_score >= 75 ? 'success' : 
                                                        ($room['last_inspection']->overall_score >= 60 ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo number_format($room['last_inspection']->overall_score, 1); ?>% (Grade <?php echo $room['last_inspection']->grade; ?>)
                                                </span>
                                                <br><small><?php echo date('d M Y', strtotime($room['last_inspection']->inspection_date)); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No inspections yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">                                                                
                                    <!-- Conduct Inspection -->
                                    <a href="<?=base_url('hostels/create_inspection?room_id='.$room['room_id'])?>" 
                                    class="btn btn-primary btn-sm" 
                                    title="Conduct Inspection">
                                        <i class="fas fa-clipboard-list"></i>
                                    </a>
                                    
                                    <!-- Report Issue (Repair) -->
                                    <button type="button" 
                                            class="btn btn-warning btn-sm" 
                                            title="Report Issue"
                                            onclick="showRepairModal(<?php echo $room['room_id']; ?>, '<?php echo addslashes($room['room_name']); ?>')">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
var dataTableInitialized = false;

function exportTableToExcel() {
    var table = document.getElementById('assignedRoomsTable');
    var html = table.outerHTML;
    var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    var link = document.createElement('a');
    link.download = 'my_assigned_rooms.xls';
    link.href = url;
    link.click();
}
function storeRoomAndViewInventory(roomId, roomName) {
    // Store room ID in localStorage
    localStorage.setItem('selectedRoomId', roomId);
    localStorage.setItem('selectedRoomName', roomName);
    window.location.href = base_url + 'hostels/room';
    return false;
}
function copyTableToClipboard() {
    var table = document.getElementById('assignedRoomsTable');
    var range = document.createRange();
    range.selectNode(table);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    document.execCommand('copy');
    window.getSelection().removeAllRanges();
    alert('Table copied to clipboard');
}


function showRepairModal(roomId, roomName) {
    // Get fresh CSRF token
    var csrfToken = $('meta[name="csrf_token"]').attr('content') || '<?=$this->security->get_csrf_hash();?>';
    var csrfName = '<?=$this->security->get_csrf_token_name();?>';
    
    var modalHtml = '<div class="modal fade" id="quickRepairModal" tabindex="-1" role="dialog">';
    modalHtml += '<div class="modal-dialog" role="document">';
    modalHtml += '<div class="modal-content">';
    modalHtml += '<div class="modal-header">';
    modalHtml += '<button type="button" class="close" data-dismiss="modal">&times;</button>';
    modalHtml += '<h4 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Report Issue for ' + roomName + '</h4>';
    modalHtml += '</div>';
    modalHtml += '<form id="quickRepairForm" action="<?=base_url('hostels/add_repair_request')?>" method="post">';
    modalHtml += '<div class="modal-body">';
    modalHtml += '<input type="hidden" name="room_id" value="' + roomId + '">';
    modalHtml += '<input type="hidden" name="' + csrfName + '" value="' + csrfToken + '">';
    modalHtml += '<div class="form-group">';
    modalHtml += '<label>Issue Description <span class="required">*</span></label>';
    modalHtml += '<textarea name="issue_description" class="form-control" rows="4" required></textarea>';
    modalHtml += '</div>';
    modalHtml += '<div class="form-group">';
    modalHtml += '<label>Priority <span class="required">*</span></label>';
    modalHtml += '<select name="priority" class="form-control" required>';
    modalHtml += '<option value="low">Low</option>';
    modalHtml += '<option value="medium">Medium</option>';
    modalHtml += '<option value="high">High</option>';
    modalHtml += '<option value="emergency">Emergency</option>';
    modalHtml += '</select>';
    modalHtml += '</div>';
    modalHtml += '</div>';
    modalHtml += '<div class="modal-footer">';
    modalHtml += '<button type="submit" class="btn btn-primary">Submit Repair</button>';
    modalHtml += '<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>';
    modalHtml += '</div>';
    modalHtml += '</form>';
    modalHtml += '</div>';
    modalHtml += '</div>';
    modalHtml += '</div>';
    
    // Remove existing modal if any
    $('#quickRepairModal').remove();
    $('body').append(modalHtml);
    
    // Handle form submission manually
    $('#quickRepairForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Repair request submitted successfully');
                    $('#quickRepairModal').modal('hide');
                    location.reload();
                } else {
                    var errorMsg = response.message || 'Failed to submit repair';
                    if (response.error) {
                        errorMsg = Object.values(response.error).join('\n');
                    }
                    alert('Error: ' + errorMsg);
                }
                submitBtn.prop('disabled', false).html(originalText);
            },
            error: function(xhr, status, error) {
                alert('Server error: ' + error);
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    $('#quickRepairModal').modal('show');
}
function viewRoomInventory(roomId, roomName) {
    // Store room ID in session storage
    sessionStorage.setItem('selectedRoomId', roomId);
    sessionStorage.setItem('selectedRoomName', roomName);
    window.location.href = base_url + 'hostels/room';
    return false;
}

// On rooms page load, check for stored room ID
$(document).ready(function() {
    var selectedRoomId = sessionStorage.getItem('selectedRoomId');
    if (selectedRoomId && window.location.href.indexOf('hostels/room') > -1) {
        // Switch to inventory tab
        $('a[href="#inventory"]').tab('show');
        setTimeout(function() {
            $('#inventory_room_select').val(selectedRoomId).trigger('change');
            sessionStorage.removeItem('selectedRoomId');
        }, 500);
    }
});

$(document).ready(function() {
    // Check if DataTable is already initialized
    if ($.fn.DataTable && !dataTableInitialized) {
        if ($.fn.DataTable.isDataTable('#assignedRoomsTable')) {
            $('#assignedRoomsTable').DataTable().destroy();
        }
        
        $('#assignedRoomsTable').DataTable({
            "paging": true,
            "ordering": true,
            "info": true,
            "searching": true,
            "pageLength": 25,
            "language": {
                "search": "<?=translate('search')?>:",
                "lengthMenu": "<?=translate('show')?> _MENU_ <?=translate('entries')?>",
                "info": "<?=translate('showing')?> _START_ <?=translate('to')?> _END_ <?=translate('of')?> _TOTAL_ <?=translate('entries')?>"
            }
        });
        dataTableInitialized = true;
    }
});

</script>

<style>
.progress {
    margin-bottom: 0;
}
.label {
    font-size: 85%;
}
.btn-group .btn-sm, .action-buttons .btn-sm {
    margin: 0 2px;
    padding: 4px 8px;
}
.action-buttons {
    white-space: nowrap;
}
</style>