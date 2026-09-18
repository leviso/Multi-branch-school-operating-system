<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-user-graduate"></i> <?=translate('student_borrowing_history')?></h4>
                <div class="panel-btn">
                    <a href="<?=base_url('library/advanced_reports')?>" class="btn btn-default btn-circle">
                        <i class="fas fa-arrow-left"></i> <?=translate('back_to_reports')?>
                    </a>
                </div>
            </header>
            <div class="panel-body">
                <!-- Search Form -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?=translate('search_student')?></h4>
                    </div>
                    <div class="panel-body">
                        <form method="get" class="form-horizontal">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="control-label col-md-3"><?=translate('select_student')?></label>
                                        <div class="col-md-9">
                                            <select name="student_id" id="student_search" class="form-control" style="width: 100%;">
                                                <option value=""><?=translate('search_by_name_or_register_no')?></option>
                                                <?php foreach ($students as $student): ?>
                                                <option value="<?=$student['id']?>" <?=($search_term == $student['id']) ? 'selected' : ''?>>
                                                    <?=htmlspecialchars($student['full_name'])?> (<?=$student['register_no']?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> <?=translate('search')?>
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <a href="<?=base_url('library/student_borrowing_history')?>" class="btn btn-default btn-block">
                                        <i class="fas fa-sync-alt"></i> <?=translate('reset')?>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Student Info (if student selected) -->
                <?php if ($search_term && !empty($report_data)): 
                    // Calculate statistics from the actual data
                    $issued_count = 0;
                    $returned_count = 0;
                    $overdue_count = 0;
                    foreach ($report_data as $row) {
                        if ($row['status'] == 1) {
                            $issued_count++;
                            if ($row['days_overdue'] > 0) {
                                $overdue_count++;
                            }
                        }
                        if ($row['status'] == 3) {
                            $returned_count++;
                        }
                    }
                ?>
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-3">
                            <strong><?=translate('student_name')?>:</strong> 
                            <?=htmlspecialchars(($report_data[0]['first_name'] ?? '') . ' ' . ($report_data[0]['last_name'] ?? ''))?>
                        </div>
                        <div class="col-md-2">
                            <strong><?=translate('register_no')?>:</strong> 
                            <?=$report_data[0]['register_no'] ?? 'N/A'?>
                        </div>
                        <div class="col-md-2">
                            <strong><?=translate('total_books_borrowed')?>:</strong> 
                            <span class="label label-primary-custom"><?=count($report_data)?></span>
                        </div>
                        <div class="col-md-2">
                            <strong><?=translate('currently_issued')?>:</strong> 
                            <span class="label label-info-custom"><?=$issued_count?></span>
                        </div>
                        <div class="col-md-2">
                            <strong><?=translate('returned')?>:</strong> 
                            <span class="label label-success-custom"><?=$returned_count?></span>
                        </div>
                        <div class="col-md-1">
                            <strong><?=translate('overdue')?>:</strong> 
                            <span class="label label-danger-custom"><?=$overdue_count?></span>
                        </div>
                    </div>
                </div>
                <?php elseif ($search_term && empty($report_data)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <?=translate('no_borrowing_history_found')?>
                </div>
                <?php endif; ?>
                
                <!-- Borrowing History Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-export">
                       <thead>
                            <thead>
                                <tr>
                                    <th><?=translate('sl')?></th>
                                    <?php if ($is_superadmin): ?>
                                    <th><?=translate('branch')?></th>
                                    <?php endif; ?>
                                    <th><?=translate('book_title')?></th>
                                    <th><?=translate('copy_number')?></th>
                                    <th><?=translate('book_code')?></th>
                                    <th><?=translate('author')?></th>
                                    <th><?=translate('issue_date')?></th>
                                    <th><?=translate('due_date')?></th>
                                    <th><?=translate('return_date')?></th>
                                    <th><?=translate('status')?></th>
                                    <th><?=translate('fine')?></th>
                                    <th><?=translate('condition')?></th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            if (!empty($report_data)):
                                $fine_per_day = $this->db->select('fine_per_day')->where('branch_id', $branch_id)->get('library_settings')->row()->fine_per_day ?? 5;
                                
                                foreach ($report_data as $row): 
                                    $status = $row['status'] ?? 0;
                                    $expiry = $row['date_of_expiry'] ?? '';
                                    $today = date('Y-m-d');
                                    $is_overdue = ($status == 1 && !empty($expiry) && $expiry < $today);
                                    $days_overdue = $is_overdue ? floor((strtotime($today) - strtotime($expiry)) / 86400) : 0;
                                    $fine_amount = ($status == 3 && !empty($row['fine_amount'])) ? $row['fine_amount'] : ($days_overdue * $fine_per_day);
                                    
                                    // Get copy number and condition in single query
                                    $copy_data = null;
                                    if (!empty($row['copy_id'])) {
                                        $copy_data = $this->db->select('copy_number, condition')->where('id', $row['copy_id'])->get('book_copies')->row();
                                    }
                                    $copy_number = $copy_data->copy_number ?? '';
                                    $condition = $copy_data->condition ?? '—';
                                    if ($condition != '—') $condition = ucfirst($condition);
                                    
                                    // Status label classes
                                    $status_class = [
                                        0 => 'warning', 1 => ($is_overdue ? 'danger' : 'success'), 
                                        2 => 'danger', 3 => 'info'
                                    ];
                                    $status_text = [
                                        0 => 'Pending', 1 => ($is_overdue ? "Overdue ({$days_overdue} days)" : 'Issued'),
                                        2 => 'Rejected', 3 => 'Returned'
                                    ];
                            ?>
                            <tr>
                                <td class="text-center"><?=$count++?></td>
                                <?php if ($is_superadmin): ?>
                                <td class="text-center"><?=htmlspecialchars($row['branch_name'] ?? get_type_name_by_id('branch', $row['branch_id']))?></td>
                                <?php endif; ?>
                                <td><?=htmlspecialchars($row['title'])?></td>
                                <td class="text-center"><?=!empty($copy_number) ? '<span class="label label-info-custom" style="font-family: monospace;">' . $copy_number . '</span>' : '—'?></td>
                                <td><?=$row['book_code'] ?? 'N/A'?></td>
                                <td><?=htmlspecialchars($row['author'])?></td>
                                <td><?=!empty($row['date_of_issue']) ? date('d/m/Y', strtotime($row['date_of_issue'])) : 'N/A'?></td>
                                <td><?=!empty($expiry) ? date('d/m/Y', strtotime($expiry)) : 'N/A'?></td>
                                <td><?=!empty($row['return_date']) ? date('d/m/Y', strtotime($row['return_date'])) : '—'?></td>
                                <td class="text-center"><span class="label label-<?=$status_class[$status]?>-custom"><?=$status_text[$status]?></span></td>
                                <td class="text-center"><?=$global_config['currency_symbol'] . number_format($fine_amount, 2)?></td>
                                <td class="text-center"><span class="label label-<?=($condition == 'Good' || $condition == 'New') ? 'success' : 'warning'?>-custom"><?=$condition?></span></td>
                            </tr>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Initialize Select2 for student search
    $('#student_search').select2({
        placeholder: '<?=translate('search_by_name_or_register_no')?>',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: base_url + 'library/ajax_get_students',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });
    
    // Auto-submit when student is selected
    $('#student_search').on('change', function() {
        if ($(this).val()) {
            window.location.href = base_url + 'library/student_borrowing_history?student_id=' + $(this).val();
        }
    });
});
</script>