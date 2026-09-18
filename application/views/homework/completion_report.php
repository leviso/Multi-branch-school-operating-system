<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-bar"></i> <?=translate('homework_completion_report')?></h4>
                <div class="panel-btn">
                    <a href="<?=base_url('homework/export_report_excel?'. http_build_query($_GET))?>" class="btn btn-default btn-circle">
                        <i class="fas fa-file-excel"></i> <?=translate('export_excel')?>
                    </a>
                    <a href="<?=base_url('homework/print_report?'. http_build_query($_GET))?>" class="btn btn-default btn-circle" target="_blank">
                        <i class="fas fa-print"></i> <?=translate('print')?>
                    </a>
                </div>
            </header>
            <div class="panel-body">
                <?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal', 'method' => 'get'));?>
                <div class="row mb-sm">
                    <!-- BRANCH FILTER FOR SUPERADMIN -->
                    <?php if ($is_superadmin): ?>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('branch')?></label>
                            <?php
                            $branch_options = array('' => translate('all_branches'));
                            $branches = $this->db->select('id, name')->order_by('name')->get('branch')->result_array();
                            foreach ($branches as $branch) {
                                $branch_options[$branch['id']] = $branch['name'];
                            }
                            $selected_branch = $this->input->get('branch_id');
                            echo form_dropdown("branch_id", $branch_options, $selected_branch, "class='form-control' id='branch_filter'");
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('report_type')?></label>
                            <?php
                            $report_options = array(
                                'homework' => translate('by_homework'),
                                'student' => translate('by_student')
                            );
                            echo form_dropdown("report_type", $report_options, $report_type, "class='form-control' onchange='this.form.submit()'");
                            ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('class')?></label>
                            <?php
                            $class_options = array('' => translate('all_classes'));
                            foreach ($classes as $class) {
                                $class_options[$class['id']] = $class['name'];
                            }
                            echo form_dropdown("class_id", $class_options, $class_id, "class='form-control' id='class_filter'");
                            ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('section')?></label>
                            <select name="section_id" class="form-control" id="section_filter">
                                <option value=""><?=translate('all_sections')?></option>
                                <?php foreach ($sections as $section): ?>
                                <option value="<?=$section['id']?>" <?=($section_id == $section['id']) ? 'selected' : ''?>>
                                    <?=htmlspecialchars($section['name'])?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mb-sm">
                    <?php if ($report_type == 'homework'): ?>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('date_range')?></label>
                            <input type="text" name="daterange" class="form-control" id="daterange" value="<?=($date_from && $date_to) ? date('d/m/Y', strtotime($date_from)) . ' - ' . date('d/m/Y', strtotime($date_to)) : ''?>">
                            <input type="hidden" name="date_from" id="date_from" value="<?=$date_from?>">
                            <input type="hidden" name="date_to" id="date_to" value="<?=$date_to?>">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-offset-<?=($report_type == 'homework') ? '7' : '9'?> col-md-2">
                        <button type="submit" class="btn btn-default btn-block"><i class="fas fa-filter"></i> <?=translate('filter')?></button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </section>
        
        <!-- Summary Cards -->
        <div class="row mb-sm">
            <div class="col-md-3">
                <div class="panel panel-primary">
                    <div class="panel-body text-center">
                        <h3><?=$summary['total_homework']?></h3>
                        <p><?=translate('total_homework')?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-body text-center">
                        <h3><?=$summary['total_submissions']?>/<?=$summary['total_students']?></h3>
                        <p><?=translate('submissions')?></p>
                        <small><?=$summary['overall_submission_rate']?>% <?=translate('completion_rate')?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-success">
                    <div class="panel-body text-center">
                        <h3><?=$summary['total_graded']?>/<?=$summary['total_submissions']?></h3>
                        <p><?=translate('graded')?></p>
                        <small><?=$summary['overall_grading_rate']?>% <?=translate('grading_rate')?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-warning">
                    <div class="panel-body text-center">
                        <h3><?=round($summary['overall_submission_rate'], 1)?>%</h3>
                        <p><?=translate('overall_completion')?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Report Table -->
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-list"></i> <?=translate('report_details')?></h4>
            </header>
            <div class="panel-body">
                <table class="table table-bordered table-striped table-export">
                    <thead>
                        <tr>
                            <?php if ($report_type == 'student'): ?>
                            <th><?=translate('student_name')?></th>
                            <th><?=translate('register_no')?></th>
                            <th><?=translate('class')?></th>
                            <th><?=translate('section')?></th>
                            <th><?=translate('total_homework')?></th>
                            <th><?=translate('submitted')?></th>
                            <th><?=translate('late')?></th>
                            <th><?=translate('graded')?></th>
                            <th><?=translate('completion_rate')?></th>
                            <th><?=translate('avg_grade')?></th>
                            <?php else: ?>
                            <th><?=translate('subject')?></th>
                            <th><?=translate('due_date')?></th>
                            <th><?=translate('class')?></th>
                            <th><?=translate('section')?></th>
                            <th><?=translate('total_students')?></th>
                            <th><?=translate('submissions')?></th>
                            <th><?=translate('submission_rate')?></th>
                            <th><?=translate('graded')?></th>
                            <th><?=translate('grading_rate')?></th>
                            <th><?=translate('late')?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_data)): ?>
                        <tr>
                            <td colspan="10" class="text-center"><?=translate('no_data_found')?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php if ($report_type == 'student'): ?>
                                <td><?=htmlspecialchars($row['student_name'])?></td>
                                <td><?=$row['register_no']?></td>
                                <td><?=htmlspecialchars($row['class_name'])?></td>
                                <td><?=htmlspecialchars($row['section_name'])?></td>
                                <td class="text-center"><?=$row['total_homework']?></td>
                                <td class="text-center"><?=$row['submitted_count']?></td>
                                <td class="text-center"><?=$row['late_count']?></td>
                                <td class="text-center"><?=$row['graded_count']?></td>
                                <td class="text-center">
                                    <span class="label label-<?=($row['completion_rate'] >= 80) ? 'success' : (($row['completion_rate'] >= 50) ? 'warning' : 'danger')?>-custom">
                                        <?=$row['completion_rate']?>%
                                    </span>
                                </td>
                                <td class="text-center"><?=round($row['avg_grade'] ?? 0, 1)?></td>
                                <?php else: ?>
                                <td><?=htmlspecialchars($row['subject_name'])?></td>
                                <td><?=_d($row['date_of_homework'])?></td>
                                <td><?=htmlspecialchars($row['class_name'])?></td>
                                <td><?=htmlspecialchars($row['section_name'])?></td>
                                <td class="text-center"><?=$row['total_students']?></td>
                                <td class="text-center"><?=$row['submissions_count']?></td>
                                <td class="text-center">
                                    <span class="label label-<?=($row['submission_rate'] >= 80) ? 'success' : (($row['submission_rate'] >= 50) ? 'warning' : 'danger')?>-custom">
                                        <?=$row['submission_rate']?>%
                                    </span>
                                </td>
                                <td class="text-center"><?=$row['graded_count']?></td>
                                <td class="text-center">
                                    <span class="label label-<?=($row['grading_rate'] >= 80) ? 'success' : (($row['grading_rate'] >= 50) ? 'warning' : 'danger')?>-custom">
                                        <?=$row['grading_rate']?>%
                                    </span>
                                </td>
                                <td class="text-center"><?=$row['late_count']?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <?php if ($report_type == 'student'): ?>
                            <th colspan="4" class="text-right"><?=translate('total')?>: </th>
                            <th class="text-center"><?=array_sum(array_column($report_data, 'total_homework'))?></th>
                            <th class="text-center"><?=array_sum(array_column($report_data, 'submitted_count'))?></th>
                            <th class="text-center"><?=array_sum(array_column($report_data, 'late_count'))?></th>
                            <th class="text-center"><?=array_sum(array_column($report_data, 'graded_count'))?></th>
                            <th colspan="2"></th>
                            <?php else: ?>
                            <th colspan="4" class="text-right"><?=translate('total')?>: </th>
                            <th class="text-center"><?=array_sum(array_column($report_data, 'total_students'))?></th>
                            <th class="text-center"><?=array_sum(array_column($report_data, 'submissions_count'))?></th>
                            <th></th>
                            <th class="text-center"><?=array_sum(array_column($report_data, 'graded_count'))?></th>
                            <th></th>
                            <th class="text-center"><?=array_sum(array_column($report_data, 'late_count'))?></th>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    <?php if ($is_superadmin): ?>
    // Branch filter change - reload page with selected branch
    $('#branch_filter').on('change', function() {
        var branch_id = $(this).val();
        var current_url = window.location.href.split('?')[0];
        var params = new URLSearchParams(window.location.search);
        
        if (branch_id) {
            params.set('branch_id', branch_id);
        } else {
            params.delete('branch_id');
        }
        
        window.location.href = current_url + '?' + params.toString();
    });
    <?php endif; ?>
    
    // Class filter - load sections
    $('#class_filter').on('change', function() {
        var class_id = $(this).val();
        var branch_id = '<?=$branch_id?>';
        
        if (class_id) {
            $.ajax({
                url: base_url + 'homework/get_sections_by_class',
                type: 'POST',
                data: { class_id: class_id, branch_id: branch_id },
                dataType: 'json',
                success: function(response) {
                    var options = '<option value=""><?=translate('all_sections')?></option>';
                    $.each(response.sections, function(index, section) {
                        options += '<option value="' + section.id + '">' + section.name + '</option>';
                    });
                    $('#section_filter').html(options);
                }
            });
        } else {
            $('#section_filter').html('<option value=""><?=translate('all_sections')?></option>');
        }
    });
    
    <?php if ($report_type == 'homework'): ?>
    // Date range picker
    $('#daterange').daterangepicker({
        locale: { format: 'DD/MM/YYYY' },
        autoUpdateInput: false
    });
    
    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        $('#date_from').val(picker.startDate.format('YYYY-MM-DD'));
        $('#date_to').val(picker.endDate.format('YYYY-MM-DD'));
    });
    
    $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        $('#date_from').val('');
        $('#date_to').val('');
    });
    <?php endif; ?>
});
</script>