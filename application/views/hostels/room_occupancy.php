<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fas fa-bed"></i> <?=translate('room_occupancy_report')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <!-- Export buttons will be added by DataTables table-export class -->
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <!-- Branch Filter for Superadmin -->
                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                <div class="row mb-md">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?=translate('select_branch')?></label>
                            <select class="form-control" id="branch_filter" onchange="window.location.href='<?=base_url('hostels/room_occupancy')?>?branch_id='+this.value">
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
                
                <!-- Dynamic Filters -->
                <div class="row mb-md">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?=translate('hostel')?></label>
                            <select class="form-control" id="hostel_filter" onchange="applyFilters()">
                                <option value=""><?=translate('all_hostels')?></option>
                                <?php foreach($report_data as $hostel_data): ?>
                                <option value="<?php echo $hostel_data['hostel']['id']; ?>">
                                    <?php echo $hostel_data['hostel']['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?=translate('status')?></label>
                            <select class="form-control" id="status_filter" onchange="applyFilters()">
                                <option value=""><?=translate('all_status')?></option>
                                <option value="Full"><?=translate('full')?></option>
                                <option value="Almost Full"><?=translate('almost_full')?></option>
                                <option value="Available"><?=translate('available')?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?=translate('occupancy_rate')?></label>
                            <select class="form-control" id="occupancy_filter" onchange="applyFilters()">
                                <option value=""><?=translate('all')?></option>
                                <option value="high"><?=translate('high_occupancy')?> (>80%)</option>
                                <option value="medium"><?=translate('medium_occupancy')?> (50-80%)</option>
                                <option value="low"><?=translate('low_occupancy')?> (<50%)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-default btn-block" onclick="resetFilters()">
                                <i class="fas fa-undo-alt"></i> <?=translate('reset_filters')?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Overall Summary Cards -->
                <div class="row mb-md">
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($total_hostels = count($report_data)); ?></h3>
                                <p class="text-muted"><?=translate('total_hostels')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($total_rooms); ?></h3>
                                <p class="text-muted"><?=translate('total_rooms')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($total_beds); ?></h3>
                                <p class="text-muted"><?=translate('total_beds')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($total_students); ?> / <?php echo number_format($total_beds); ?></h3>
                                <p class="text-muted"><?=translate('occupancy')?> (<?php echo $overall_occupancy; ?>%)</p>
                                <div class="progress progress-sm">
                                    <div class="progress-bar progress-bar-success" style="width: <?php echo $overall_occupancy; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hostel-wise Report Container -->
                <div id="report_container">
                    <?php foreach($report_data as $hostel_data): ?>
                    <div class="panel panel-default mt-lg hostel-section" data-hostel-id="<?php echo $hostel_data['hostel']['id']; ?>">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <i class="fas fa-building"></i> <?php echo $hostel_data['hostel']['name']; ?>
                                <span class="pull-right">
                                    <span class="label label-info"><?php echo $hostel_data['totals']['rooms']; ?> <?=translate('rooms')?></span>
                                    <span class="label label-primary"><?php echo $hostel_data['totals']['beds']; ?> <?=translate('beds')?></span>
                                    <span class="label label-success"><?php echo $hostel_data['totals']['students']; ?> <?=translate('students')?></span>
                                    <span class="label label-<?php echo $hostel_data['totals']['occupancy'] >= 90 ? 'danger' : ($hostel_data['totals']['occupancy'] >= 70 ? 'warning' : 'success'); ?>">
                                        <?php echo $hostel_data['totals']['occupancy']; ?>% <?=translate('occupied')?>
                                    </span>
                                </span>
                            </h4>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <!-- CRITICAL: table-export class added here for DataTables -->
                                <table class="table table-bordered table-hover table-export" id="room_table_<?php echo $hostel_data['hostel']['id']; ?>">
                                    <thead>
                                        <tr>
                                            <th width="15%"><?=translate('room')?></th>
                                            <th width="10%"><?=translate('capacity')?></th>
                                            <th width="10%"><?=translate('occupied')?></th>
                                            <th width="10%"><?=translate('available')?></th>
                                            <th width="15%"><?=translate('status')?></th>
                                            <th width="40%"><?=translate('students')?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($hostel_data['rooms'] as $room_data): ?>
                                        <tr class="room-row" data-status="<?php echo $room_data['status']; ?>" data-occupancy="<?php echo $room_data['occupancy_percent']; ?>" data-room-id="<?php echo $room_data['room']['id']; ?>">
                                            <td>
                                                <strong><?php echo $room_data['room']['name']; ?></strong>
                                                <?php if(!empty($room_data['room']['room_number'])): ?>
                                                <br><small class="text-muted">No. <?php echo $room_data['room']['room_number']; ?></small>
                                                <?php endif; ?>
                                             </div>
                                            <td><?php echo number_format($room_data['capacity']); ?> <?=translate('beds')?> </div>
                                            <td><?php echo number_format($room_data['occupied']); ?> <?=translate('students')?> </div>
                                            <td><?php echo number_format($room_data['available']); ?> <?=translate('beds')?> </div>
                                            <td>
                                                <?php 
                                                $status_class = $room_data['status'] == 'Full' ? 'danger' : ($room_data['status'] == 'Almost Full' ? 'warning' : 'success');
                                                ?>
                                                <span class="label label-<?php echo $status_class; ?>">
                                                    <?php echo translate(str_replace(' ', '_', strtolower($room_data['status']))); ?>
                                                </span>
                                                <br><small><?php echo $room_data['occupancy_percent']; ?>% occupied</small>
                                                <div class="progress progress-sm mt-xs">
                                                    <div class="progress-bar progress-bar-<?php echo $status_class; ?>" style="width: <?php echo $room_data['occupancy_percent']; ?>%"></div>
                                                </div>
                                             </div>
                                            <td>
                                                <?php if(!empty($room_data['students'])): ?>
                                                    <ul class="list-unstyled mb-none">
                                                    <?php foreach($room_data['students'] as $student): ?>
                                                        <li>
                                                            <a href="#" onclick="showStudentDetails(<?php echo $student['id']; ?>); return false;" class="student-link">
                                                                <i class="fas fa-user-graduate"></i> 
                                                                <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                                                                <small>(<?php echo $student['register_no']; ?>)</small>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                    </ul>
                                                    <button class="btn btn-xs btn-primary mt-sm" onclick="viewAllStudents(<?php echo $room_data['room']['id']; ?>, '<?php echo addslashes($room_data['room']['name']); ?>')">
                                                        <i class="fas fa-users"></i> <?=translate('view_all_students')?>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted"><?=translate('no_students_assigned')?></span>
                                                <?php endif; ?>
                                             </div>
                                         </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="active">
                                            <th><strong><?=translate('total_for')?> <?php echo $hostel_data['hostel']['name']; ?></strong></th>
                                            <th><?php echo number_format($hostel_data['totals']['beds']); ?> <?=translate('beds')?></th>
                                            <th><?php echo number_format($hostel_data['totals']['students']); ?> <?=translate('students')?></th>
                                            <th><?php echo number_format($hostel_data['totals']['beds'] - $hostel_data['totals']['students']); ?> <?=translate('available')?></th>
                                            <th colspan="2">
                                                <div class="progress">
                                                    <div class="progress-bar progress-bar-success" style="width: <?php echo $hostel_data['totals']['occupancy']; ?>%">
                                                        <?php echo $hostel_data['totals']['occupancy']; ?>% <?=translate('occupied')?>
                                                    </div>
                                                </div>
                                            </th>
                                          </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if(empty($report_data)): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle"></i> <?=translate('no_hostels_found')?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fas fa-user-graduate"></i> <?=translate('student_details')?></h4>
            </div>
            <div class="modal-body" id="studentDetailsContent">
                <div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('close')?></button>
            </div>
        </div>
    </div>
</div>

<!-- Room Students Modal -->
<div class="modal fade" id="roomStudentsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fas fa-users"></i> <?=translate('students_in_room')?> - <span id="roomName"></span></h4>
            </div>
            <div class="modal-body" id="roomStudentsContent">
                <div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('close')?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';

function applyFilters() {
    var hostelFilter = $('#hostel_filter').val();
    var statusFilter = $('#status_filter').val();
    var occupancyFilter = $('#occupancy_filter').val();
    
    $('.hostel-section').each(function() {
        var hostelId = $(this).data('hostel-id');
        var showHostel = true;
        
        if (hostelFilter && hostelId != hostelFilter) {
            showHostel = false;
        }
        
        if (showHostel) {
            $(this).show();
            
            $(this).find('.room-row').each(function() {
                var roomStatus = $(this).data('status');
                var roomOccupancy = parseInt($(this).data('occupancy'));
                var showRoom = true;
                
                if (statusFilter && roomStatus != statusFilter) {
                    showRoom = false;
                }
                
                if (occupancyFilter) {
                    if (occupancyFilter == 'high' && roomOccupancy <= 80) showRoom = false;
                    if (occupancyFilter == 'medium' && (roomOccupancy < 50 || roomOccupancy > 80)) showRoom = false;
                    if (occupancyFilter == 'low' && roomOccupancy >= 50) showRoom = false;
                }
                
                if (showRoom) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            var visibleRooms = $(this).find('.room-row:visible').length;
            if (visibleRooms === 0) {
                $(this).hide();
            }
        } else {
            $(this).hide();
        }
    });
}

function resetFilters() {
    $('#hostel_filter').val('');
    $('#status_filter').val('');
    $('#occupancy_filter').val('');
    applyFilters();
}

function showStudentDetails(studentId) {
    $('#studentDetailsModal').modal('show');
    $('#studentDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading student details...</div>');
    
    $.ajax({
        url: base_url + 'hostels/get_student_details/' + studentId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                $('#studentDetailsContent').html('<div class="alert alert-danger">' + data.error + '</div>');
                return;
            }
            
            var html = '<div class="table-responsive">';
            html += '<table class="table table-bordered table-striped">';
            html += '<tbody>';
            html += '<tr><th width="35%"><?=translate('student_name')?></th><td>' + (data.fullname || data.first_name + ' ' + data.last_name) + '</tr>';
            html += '<tr><th><?=translate('register_no')?></th><td>' + (data.register_no || 'N/A') + '</tr>';
            html += '<tr><th><?=translate('class')?></th><td>' + (data.class_name || 'N/A') + ' ' + (data.section_name ? '(' + data.section_name + ')' : '') + '</tr>';
            html += '<tr><th><?=translate('roll')?></th><td>' + (data.roll || 'N/A') + '</tr>';
            html += '<tr><th><?=translate('gender')?></th><td>' + (data.gender ? data.gender.charAt(0).toUpperCase() + data.gender.slice(1) : 'N/A') + '</tr>';
            html += '<tr><th><?=translate('mobile_no')?></th><td>' + (data.mobileno || 'N/A') + '</tr>';
            html += '<tr><th><?=translate('email')?></th><td>' + (data.email || 'N/A') + '</tr>';
            html += '<tr><th><?=translate('guardian')?></th><td>' + (data.parent_name || 'N/A') + '</tr>';
            html += '<tr><th><?=translate('parent_contact')?></th><td>' + (data.parent_mobile || 'N/A') + '</tr>';
            html += '<tr><th><?=translate('guardian_relation')?></th><td>' + (data.relation || 'N/A') + '</tr>';
            html += '<tr><th><?=translate('room')?></th><td>' + (data.room_name || 'N/A') + '</tr>';
            html += '<tr><th><?=translate('hostel')?></th><td>' + (data.hostel_name || 'N/A') + '</tr>';
            html += '<tr><th><?=translate('status')?></th><td>' + (data.active == 1 ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>') + '</tr>';
            html += '</tbody>';
            html += '</table>';
            html += '</div>';
            
            $('#studentDetailsContent').html(html);
        },
        error: function() {
            $('#studentDetailsContent').html('<div class="alert alert-danger"><?=translate('error_loading_details')?></div>');
        }
    });
}

function viewAllStudents(roomId, roomName) {
    $('#roomName').text(roomName);
    $('#roomStudentsModal').modal('show');
    $('#roomStudentsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading students...</div>');
    
    $.ajax({
        url: base_url + 'hostels/get_room_students/' + roomId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.length > 0) {
                var html = '<div class="table-responsive">';
                html += '<table class="table table-bordered table-striped table-export">';
                html += '<thead><tr><th>#</th><th><?=translate('student_name')?></th><th><?=translate('register_no')?></th><th><?=translate('class')?></th><th><?=translate('gender')?></th><th><?=translate('parent_contact')?></th></tr></thead><tbody>';
                for (var i = 0; i < data.length; i++) {
                    var student = data[i];
                    html += '<tr>';
                    html += '<td>' + (i + 1) + '</td>';
                    html += '<td><a href="#" onclick="showStudentDetails(' + student.id + '); return false;">' + student.fullname + '</a></td>';
                    html += '<td>' + student.register_no + '</td>';
                    html += '<td>' + (student.class_name || 'N/A') + ' ' + (student.section_name ? '(' + student.section_name + ')' : '') + '</td>';
                    html += '<td>' + (student.gender ? student.gender.charAt(0).toUpperCase() + student.gender.slice(1) : 'N/A') + '</td>';
                    html += '<td>' + (student.parent_mobile || 'N/A') + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table></div>';
                $('#roomStudentsContent').html(html);
                
                // Initialize DataTable for the modal table
                if ($.fn.DataTable) {
                    $('#roomStudentsContent .table-export').DataTable({
                        "paging": true,
                        "ordering": true,
                        "info": true,
                        "searching": true,
                        "pageLength": 10,
                        "dom": 'Bfrtip',
                        "buttons": [
                            { extend: 'copy', className: 'btn-default' },
                            { extend: 'excel', className: 'btn-default' },
                            { extend: 'pdf', className: 'btn-default' },
                            { extend: 'print', className: 'btn-default' }
                        ]
                    });
                }
            } else {
                $('#roomStudentsContent').html('<div class="alert alert-info text-center"><?=translate('no_students_in_this_room')?></div>');
            }
        },
        error: function() {
            $('#roomStudentsContent').html('<div class="alert alert-danger"><?=translate('error_loading_students')?></div>');
        }
    });
}

$(document).ready(function() {
    // Initialize DataTables on all tables with table-export class
    if ($.fn.DataTable) {
        $('.table-export').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    "paging": true,
                    "ordering": true,
                    "info": true,
                    "searching": true,
                    "pageLength": 25,
                    "language": {
                        "search": "<?=translate('search')?>:",
                        "lengthMenu": "<?=translate('show')?> _MENU_ <?=translate('entries')?>",
                        "info": "<?=translate('showing')?> _START_ <?=translate('to')?> _END_ <?=translate('of')?> _TOTAL_ <?=translate('entries')?>"
                    },
                    "dom": 'Bfrtip',
                    "buttons": [
                        { extend: 'copy', className: 'btn-default' },
                        { extend: 'excel', className: 'btn-default' },
                        { extend: 'pdf', className: 'btn-default' },
                        { extend: 'print', className: 'btn-default' }
                    ]
                });
            }
        });
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
.hostel-section {
    break-inside: avoid;
    page-break-inside: avoid;
}
.student-link {
    cursor: pointer;
    color: #337ab7;
}
.student-link:hover {
    text-decoration: underline;
}
.btn-xs {
    padding: 1px 5px;
    font-size: 11px;
}
.dataTables_wrapper .dt-buttons {
    margin-bottom: 10px;
}
@media print {
    .btn-group, .form-group, #branch_filter, #hostel_filter, #status_filter, #occupancy_filter, .btn, .dt-buttons {
        display: none;
    }
    .panel {
        border: 1px solid #ddd;
        break-inside: avoid;
    }
}
</style>