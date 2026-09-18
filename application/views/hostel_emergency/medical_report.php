<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fa fa-file-medical"></i> <?=translate('student_medical_report')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                        <select id="branch_filter" class="form-control" style="display: inline-block; width: auto; margin-right: 10px;">
                            <option value=""><?=translate('all_branches')?></option>
                            <?php foreach($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" 
                                    <?php echo (isset($selected_branch_id) && $selected_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo html_escape($branch['school_name'] . ' (' . $branch['name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <!-- Report Header with School Details -->
                <div class="text-center" style="margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #333;">
                    <!-- School Logo -->
                    <?php 
                    $logo_path = FCPATH . 'uploads/school_logo/' . $branch_id . '.png';
                    if(file_exists($logo_path)): ?>
                    <img src="<?=base_url('uploads/school_logo/' . $branch_id . '.png'); ?>" alt="School Logo" style="max-height: 80px; margin-bottom: 10px;">
                    <?php endif; ?>
                    
                    <!-- School Name -->
                    <h2 style="margin: 5px 0; font-weight: bold;"><?php echo html_escape($school_details['school_name'] ?? $branch_name->school_name ?? $branch_name->name ?? 'School'); ?></h2>
                    
                    <!-- School Address -->
                    <p style="margin: 5px 0;">
                        <i class="fa fa-map-marker"></i> <?php echo html_escape($school_details['address'] ?? ''); ?>
                    </p>
                    
                    <!-- School Contact -->
                    <p style="margin: 5px 0;">
                        <i class="fa fa-phone"></i> <?php echo html_escape($school_details['mobileno'] ?? ''); ?> &nbsp;|&nbsp;
                        <i class="fa fa-envelope"></i> <?php echo html_escape($school_details['email'] ?? ''); ?>
                    </p>
                    
                    <!-- Report Title -->
                    <h3 style="margin: 15px 0 5px 0; color: #337ab7;"><?=translate('student_medical_report')?></h3>
                    
                    <!-- Report Meta Info -->
                    <p style="margin: 5px 0; font-size: 12px; color: #666;">
                        <?=translate('generated_on')?>: <?php echo $generated_date; ?> | 
                        <?=translate('students_with_medical_conditions')?>: <strong><?php echo count($students); ?></strong>
                    </p>
                </div>
                
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-bordered table-hover table-export" id="medical_report_table" style="width: 100%; font-size: 12px;">
                        <thead>
                            <tr>
                                <th style="width: 3%;">#</th>
                                <th style="width: 8%;"><?=translate('register_no')?></th>
                                <th style="width: 10%;"><?=translate('student_name')?></th>
                                <th style="width: 6%;"><?=translate('class')?></th>
                                <th style="width: 6%;"><?=translate('section')?></th>
                                <th style="width: 8%;"><?=translate('hostel')?></th>
                                <th style="width: 6%;"><?=translate('room')?></th>
                                <th style="width: 6%;"><?=translate('blood_group')?></th>
                                <th style="width: 12%;"><?=translate('allergies')?></th>
                                <th style="width: 12%;"><?=translate('chronic_conditions')?></th>
                                <th style="width: 10%;"><?=translate('medications')?></th>
                                <th style="width: 10%;"><?=translate('emergency_contact')?></th>
                                <th style="width: 10%;"><?=translate('emergency_phone')?></th>
                                <th style="width: 10%;"><?=translate('last_updated')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach($students as $student): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo html_escape($student['register_no']); ?></td>
                                <td><?php echo html_escape($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo html_escape($student['class_name']); ?></td>
                                <td><?php echo html_escape($student['section_name']); ?></td>
                                <td><?php echo html_escape($student['hostel_name']); ?></td>
                                <td><?php echo html_escape($student['room_name']); ?></td>
                                <td>
                                    <?php if($student['blood_group']): ?>
                                    <span class="label label-info"><?php echo $student['blood_group']; ?></span>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo html_escape($student['allergies']) ?: '-'; ?></td>
                                <td><?php echo html_escape($student['chronic_conditions']) ?: '-'; ?></td>
                                <td><?php echo html_escape($student['medications']) ?: '-'; ?></td>
                                <td><?php echo html_escape($student['emergency_contact_name']) ?: '-'; ?></td>
                                <td><?php echo html_escape($student['emergency_contact_phone']) ?: '-'; ?></td>
                                <td>
                                    <?php if($student['last_updated']): ?>
                                    <?php echo date('d M Y', strtotime($student['last_updated'])); ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($students)): ?>
                            <tr>
                                <td colspan="14" class="text-center"><?=translate('no_students_with_medical_conditions')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Footer -->
                <?php if(!empty($students)): ?>
                <div class="row mt-md" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <strong><i class="fa fa-info-circle"></i> <?=translate('summary')?>:</strong>
                            <span class="label label-primary"><?=translate('total_students_with_conditions')?>: <?php echo count($students); ?></span>
                            <?php 
                            $blood_count = 0;
                            $allergy_count = 0;
                            $condition_count = 0;
                            foreach($students as $s) {
                                if($s['blood_group']) $blood_count++;
                                if($s['allergies']) $allergy_count++;
                                if($s['chronic_conditions']) $condition_count++;
                            }
                            ?>
                            <span class="label label-info"><?=translate('blood_group_recorded')?>: <?php echo $blood_count; ?></span>
                            <span class="label label-warning"><?=translate('allergies_recorded')?>: <?php echo $allergy_count; ?></span>
                            <span class="label label-danger"><?=translate('chronic_conditions_recorded')?>: <?php echo $condition_count; ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <!-- ============================================ -->
                <!-- ADD RESOLUTION STATISTICS HERE (RIGHT HERE) -->
                <!-- ============================================ -->
                <?php if(isset($resolution_stats)): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title"><i class="fa fa-check-circle"></i> Resolution Statistics</h4>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Resolved Incidents</span>
                                                <span class="info-box-number"><?php echo $resolution_stats['resolved_count'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Active Incidents</span>
                                                <span class="info-box-number"><?php echo $resolution_stats['active_count'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-blue"><i class="fa fa-hourglass-half"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Avg Resolution Time</span>
                                                <span class="info-box-number"><?php echo round($resolution_stats['avg_resolution_hours'] ?? 0); ?> hrs</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-red"><i class="fa fa-percent"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Resolution Rate</span>
                                                <span class="info-box-number">
                                                    <?php 
                                                    $rate = ($resolution_stats['total_incidents'] > 0) 
                                                        ? round(($resolution_stats['resolved_count'] / $resolution_stats['total_incidents']) * 100) 
                                                        : 0;
                                                    echo $rate; ?>%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <!-- ============================================ -->

                
                <!-- Footer with signature lines -->
                <div class="row" style="margin-top: 40px;">
                    <div class="col-md-4 text-center">
                        <hr style="width: 80%;">
                        <p><strong><?=translate('principal_signature')?></strong></p>
                    </div>
                    <div class="col-md-4 text-center">
                        <hr style="width: 80%;">
                        <p><strong><?=translate('matron_signature')?></strong></p>
                    </div>
                    <div class="col-md-4 text-center">
                        <hr style="width: 80%;">
                        <p><strong><?=translate('school_stamp')?></strong></p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<style>
/* Print styles for A4 paper */
@media print {
    .panel-heading .text-right,
    .dt-buttons,
    .dataTables_filter,
    .dataTables_paginate,
    .dataTables_info,
    #branch_filter,
    .btn-group,
    .dataTables_length,
    header .pull-right,
    .box-tools {
        display: none !important;
    }
    
    .panel {
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .panel-body {
        padding: 0 !important;
    }
    
    .table {
        width: 100% !important;
        font-size: 9pt !important;
        border-collapse: collapse !important;
    }
    
    .table th, 
    .table td {
        border: 1px solid #000 !important;
        padding: 4px !important;
    }
    
    .table-responsive {
        overflow: visible !important;
    }
    
    @page {
        size: A4 landscape;
        margin: 1cm;
    }
    
    body {
        margin: 0;
        padding: 0;
        width: 100%;
    }
    
    .label {
        border: none !important;
        padding: 1px 4px !important;
        background-color: #f0f0f0 !important;
        color: #000 !important;
    }
    
    hr {
        border-top: 1px solid #000 !important;
    }
}

/* Screen styles */
@media screen {
    .table {
        font-size: 12px;
    }
    
    .table th, 
    .table td {
        white-space: nowrap;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
}
</style>

<script type="text/javascript">
var base_url = '<?=base_url();?>';

$(document).ready(function() {
    // Branch filter - Redirect to new URL
    $('#branch_filter').change(function() {
        var branch_id = $(this).val();
        var url = base_url + 'hostel_emergency/medical_report';
        if (branch_id) {
            window.location.href = url + '?branch_id=' + branch_id;
        } else {
            window.location.href = url;
        }
    });
    
    // Initialize DataTable if available
    if ($.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('.table-export')) {
            $('.table-export').DataTable().destroy();
        }
        
        $('.table-export').DataTable({
            "pageLength": 50,
            "scrollX": true,
            "autoWidth": false,
            "language": {
                "search": "<?=translate('search')?>:",
                "lengthMenu": "<?=translate('show')?> _MENU_ <?=translate('entries')?>",
                "info": "<?=translate('showing')?> _START_ <?=translate('to')?> _END_ <?=translate('of')?> _TOTAL_ <?=translate('entries')?>",
                "zeroRecords": "<?=translate('no_records_found')?>",
                "emptyTable": "<?=translate('no_data_available')?>"
            },
            "dom": '<"top"Bf>rt<"bottom"lip>',
            "buttons": [
                {
                    extend: 'excel',
                    title: 'Medical_Report_<?php echo date('Y-m-d'); ?>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    title: 'Medical_Report_<?php echo date('Y-m-d'); ?>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdf',
                    title: 'Medical Report',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: ':visible'
                    },
                    customize: function(doc) {
                        doc.defaultStyle.fontSize = 8;
                        doc.styles.tableHeader.fontSize = 9;
                        doc.styles.title.fontSize = 14;
                        doc.content[0].text = '<?php echo html_escape($school_details['school_name'] ?? $branch_name->school_name ?? ''); ?>';
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                    }
                },
                {
                    extend: 'print',
                    title: '<?php echo html_escape($school_details['school_name'] ?? $branch_name->school_name ?? 'School'); ?> - Medical Report',
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ]
        });
    }
});
</script>