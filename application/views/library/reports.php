<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <div class="tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="<?=$report_type == 'overdue' ? 'active' : ''?>">
                        <a href="<?=base_url('library/reports?report_type=overdue')?>"><i class="fas fa-clock"></i> <?=translate('overdue_books')?></a>
                    </li>
                    <li class="<?=$report_type == 'popular' ? 'active' : ''?>">
                        <a href="<?=base_url('library/reports?report_type=popular')?>"><i class="fas fa-fire"></i> <?=translate('popular_books')?></a>
                    </li>
                    <li class="<?=$report_type == 'circulation' ? 'active' : ''?>">
                        <a href="<?=base_url('library/reports?report_type=circulation')?>"><i class="fas fa-chart-line"></i> <?=translate('circulation_stats')?></a>
                    </li>
                    <?php if (is_admin_loggedin() || is_superadmin_loggedin()): ?>
                    <li>
                        <a href="<?=base_url('library/settings')?>"><i class="fas fa-cog"></i> <?=translate('library_settings')?></a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <!-- Filter Section (for overdue and popular reports) -->
                        <?php if ($report_type != 'circulation'): ?>
                        <div class="panel-body">
                            <?php echo form_open($this->uri->uri_string(), array('method' => 'get', 'class' => 'form-horizontal')); ?>
                            <div class="row mb-sm">
                                <?php if ($is_superadmin): ?>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label"><?=translate('branch')?></label>
                                        <?php
                                        $branches = $this->db->select('id, name')->get('branch')->result_array();
                                        $branch_options = array('' => translate('all_branches'));
                                        foreach ($branches as $b) {
                                            $branch_options[$b['id']] = $b['name'];
                                        }
                                        echo form_dropdown("branch_id", $branch_options, $this->input->get('branch_id'), "class='form-control' id='branch_filter'");
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-default btn-block"><i class="fas fa-filter"></i> <?=translate('filter')?></button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <a href="<?=base_url('library/reports?report_type=' . $report_type)?>" class="btn btn-default btn-block"><i class="fas fa-sync-alt"></i> <?=translate('reset')?></a>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="report_type" value="<?=$report_type?>">
                            <?php echo form_close(); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Report Content -->
                        <div class="panel-body">
                            <?php if ($report_type == 'overdue'): ?>
                                <!-- Overdue Books Report -->
                                <?php 
                                // Calculate column count for colspan
                                $overdue_colspan = 8; // sl, book_title, book_code, borrower, due_date, days_overdue, estimated_fine, action
                                if ($is_superadmin) $overdue_colspan++; // + branch column
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-export">
                                        <thead>
                                            <tr>
                                                <th><?=translate('sl')?></th>
                                                <?php if ($is_superadmin): ?>
                                                <th><?=translate('branch')?></th>
                                                <?php endif; ?>
                                                <th><?=translate('book_title')?></th>
                                                <th><?=translate('copy_number')?></th>
                                                <th><?=translate('book_code')?></th>
                                                <th><?=translate('borrower')?></th>
                                                <th><?=translate('due_date')?></th>
                                                <th><?=translate('days_overdue')?></th>
                                                <th><?=translate('estimated_fine')?></th>
                                                <th><?=translate('action')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $count = 1;
                                            if (!empty($report_data)):
                                                foreach ($report_data as $row): 
                                            ?>
                                            <tr>
                                                <td class="text-center"><?=$count++?></td>
                                                <?php if ($is_superadmin): ?>
                                                <td class="text-center"><?=htmlspecialchars($row['branch_name'] ?? get_type_name_by_id('branch', $row['branch_id']))?></td>
                                                <?php endif; ?>
                                                <td class="text-center"><?=htmlspecialchars($row['title'])?></td>
                                                
                                                <!-- DEBUG: Uncomment to see raw data -->
                                                    <?php 
                                                    //echo '<pre>'; print_r($row); echo '</pre>'; 
                                                    ?>
                                                <!-- COPY NUMBER COLUMN -->
                                                <td class="text-center">
                                                    <?php 
                                                    if (!empty($row['copy_number'])) {
                                                        echo '<span class="label label-info-custom" style="font-family: monospace; font-size: 11px;">' . $row['copy_number'] . '</span>';
                                                    } else {
                                                        echo '—';
                                                    }
                                                    ?>
                                                </td>
                                                <!-- END COPY NUMBER COLUMN -->
                                                
                                                <td class="text-center"><?=$row['book_code'] ?? 'N/A'?></td>
                                                <td class="text-center">
                                                    <?php 
                                                    if ($row['role_id'] == 7) {
                                                        $student = $this->application_model->getStudentDetails($row['user_id']);
                                                        echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                                                    } else {
                                                        echo htmlspecialchars($row['user_name'] ?? get_type_name_by_id('staff', $row['user_id']));
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center"><?=_d($row['date_of_expiry'])?></td>
                                                <td class="text-center">
                                                    <span class="label label-danger-custom"><?=$row['days_overdue'] ?? 0?> <?=translate('days')?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php 
                                                    // Calculate fine based on days overdue
                                                    $days_overdue = (int)($row['days_overdue'] ?? 0);
                                                    $fine_per_day = 5.00; // Default, will be overridden by settings if available
                                                    
                                                    // Try to get fine_per_day from settings
                                                    if (isset($row['branch_id'])) {
                                                        $settings = $this->db->select('fine_per_day')
                                                                            ->where('branch_id', $row['branch_id'])
                                                                            ->get('library_settings')
                                                                            ->row();
                                                        if ($settings) {
                                                            $fine_per_day = (float)$settings->fine_per_day;
                                                        }
                                                    }
                                                    
                                                    $calculated_fine = $days_overdue * $fine_per_day;
                                                    
                                                    // Use the calculated fine from model if available, otherwise calculate
                                                    $fine = isset($row['calculated_fine']) && $row['calculated_fine'] > 0 
                                                            ? $row['calculated_fine'] 
                                                            : $calculated_fine;
                                                    
                                                    echo $global_config['currency_symbol'] . number_format($fine, 2);
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-success btn-circle" onclick="returnBook(<?=$row['id']?>)">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php 
                                                endforeach;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="<?=$overdue_colspan + 1?>" class="text-center"><?=translate('no_overdue_books')?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                            <?php elseif ($report_type == 'popular'): ?>
                                <!-- Popular Books Report -->
                                <?php 
                                $popular_colspan = 6; // sl, book_title, book_code, author, category, times_borrowed, cover
                                if ($is_superadmin) $popular_colspan++; // + branch column
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-export">
                                        <thead>
                                            <tr>
                                                <th><?=translate('sl')?></th>
                                                <?php if ($is_superadmin): ?>
                                                <th><?=translate('branch')?></th>
                                                <?php endif; ?>
                                                <th><?=translate('book_title')?></th>
                                                <th><?=translate('book_code')?></th>
                                                <th><?=translate('author')?></th>
                                                <th><?=translate('category')?></th>
                                                <th><?=translate('times_borrowed')?></th>
                                                <th><?=translate('cover')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $count = 1;
                                            if (!empty($report_data)):
                                                foreach ($report_data as $row):
                                                    $book = $this->db->get_where('book', array('id' => $row['id']))->row_array();
                                            ?>
                                            <tr>
                                                <td class="text-center"><?=$count++?></td>
                                                <?php if ($is_superadmin): ?>
                                                <td><?=htmlspecialchars($book['branch_name'] ?? get_type_name_by_id('branch', $book['branch_id']))?></td>
                                                <?php endif; ?>
                                                <td><?=htmlspecialchars($row['title'])?></td>
                                                <td><?=$row['book_code'] ?? 'N/A'?></td>
                                                <td><?=htmlspecialchars($row['author'] ?? '')?></td>
                                                <td><?=htmlspecialchars(get_type_name_by_id('book_category', $row['category_id']))?></td>
                                                <td class="text-center">
                                                    <span class="label label-primary-custom"><?=$row['borrow_count']?></span>
                                                </td>
                                                <td class="text-center">
                                                    <img src="<?=$this->application_model->get_book_cover_image($row['cover'])?>" width="50" height="60">
                                                </td>
                                            </tr>
                                            <?php 
                                                endforeach;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="<?=$popular_colspan?>" class="text-center"><?=translate('no_data_available')?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                            <?php elseif ($report_type == 'circulation'): ?>
                                <!-- Circulation Statistics -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="panel panel-primary">
                                            <div class="panel-body text-center">
                                                <i class="fas fa-book fa-3x"></i>
                                                <h2><?=$stats['total_issued'] ?? 0?></h2>
                                                <p><?=translate('books_issued_this_month')?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="panel panel-success">
                                            <div class="panel-body text-center">
                                                <i class="fas fa-undo-alt fa-3x"></i>
                                                <h2><?=$stats['total_returned'] ?? 0?></h2>
                                                <p><?=translate('books_returned_this_month')?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="panel panel-warning">
                                            <div class="panel-body text-center">
                                                <i class="fas fa-money-bill-wave fa-3x"></i>
                                                <h2><?=$global_config['currency_symbol'] . number_format($stats['total_fines_collected'] ?? 0, 2)?></h2>
                                                <p><?=translate('fines_collected_this_month')?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="panel panel-info">
                                            <div class="panel-body text-center">
                                                <i class="fas fa-users fa-3x"></i>
                                                <h2><?=$stats['active_borrowers'] ?? 0?></h2>
                                                <p><?=translate('active_borrowers')?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Circulation Chart -->
                                <div class="panel panel-default mt-lg">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><?=translate('monthly_circulation_trend')?></h4>
                                    </div>
                                    <div class="panel-body">
                                        <canvas id="circulationChart" style="height: 300px; width: 100%;"></canvas>
                                    </div>
                                </div>
                                
                                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                                <script>
                                $(document).ready(function() {
                                    $.ajax({
                                        url: base_url + 'library/get_monthly_circulation',
                                        type: 'POST',
                                        data: { branch_id: '<?=$branch_id?>' },
                                        dataType: 'json',
                                        success: function(response) {
                                            var ctx = document.getElementById('circulationChart').getContext('2d');
                                            new Chart(ctx, {
                                                type: 'line',
                                                data: {
                                                    labels: response.months,
                                                    datasets: [
                                                        {
                                                            label: '<?=translate('books_issued')?>',
                                                            data: response.issued,
                                                            borderColor: 'rgba(54, 162, 235, 1)',
                                                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                                            fill: true
                                                        },
                                                        {
                                                            label: '<?=translate('books_returned')?>',
                                                            data: response.returned,
                                                            borderColor: 'rgba(75, 192, 192, 1)',
                                                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                                            fill: true
                                                        }
                                                    ]
                                                },
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: true
                                                }
                                            });
                                        }
                                    });
                                });
                                </script>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Return Modal -->
<!-- Return Modal -->
<div class="zoom-anim-dialog modal-block mfp-hide" id="returnModal">
    <section class="panel">
        <header class="panel-heading">
            <h4 class="panel-title"><i class="fas fa-exchange-alt"></i> <?=translate('return_book')?></h4>
        </header>
        <?php echo form_open('library/bookReturn', array('class' => 'form-horizontal frm-submit')); ?>
            <div class="panel-body">
                <input type="hidden" name="issue_id" id="return_issue_id">
                <input type="hidden" name="type" id="return_type" value="1">
                
                <div class="form-group">
                    <label class="col-md-3 control-label"><?=translate('type')?> <span class="required">*</span></label>
                    <div class="col-md-9">
                        <select name="type_display" id="return_type_display" class="form-control">
                            <option value="1"><?=translate('return')?></option>
                            <option value="2"><?=translate('renewal')?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label" id="returnDateLabel"><?=translate('return_date')?> <span class="required">*</span></label>
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="date" id="return_date" value="<?=date('Y-m-d')?>" data-plugin-datepicker>
                        <span class="error"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-3 control-label"><?=translate('fine_amount')?></label>
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="fine_amount" id="fine_amount" value="0" readonly>
                        <span class="error"></span>
                    </div>
                </div>
                
                <div class="form-group" id="condition_group">
                    <label class="col-md-3 control-label"><?=translate('book_condition')?></label>
                    <div class="col-md-9">
                        <select name="condition" class="form-control">
                            <option value="good"><?=translate('good')?></option>
                            <option value="fair"><?=translate('fair')?></option>
                            <option value="poor"><?=translate('poor')?></option>
                            <option value="damaged"><?=translate('damaged')?></option>
                        </select>
                    </div>
                </div>
            </div>
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-default"><?=translate('submit')?></button>
                        <button class="btn btn-default modal-dismiss"><?=translate('cancel')?></button>
                    </div>
                </div>
            </footer>
        <?php echo form_close(); ?>
    </section>
</div>

<script type="text/javascript">
function returnBook(issue_id) {
    $('#return_issue_id').val(issue_id);
    $('#return_type').val('1');
    $('#return_type_display').val('1');
    $('#return_date').val('<?=date('Y-m-d')?>');
    
    // Calculate fine via AJAX
    $.ajax({
        url: base_url + 'library/calculate_fine_ajax',
        type: 'POST',
        data: { issue_id: issue_id },
        dataType: 'json',
        success: function(response) {
            $('#fine_amount').val(response.fine);
        },
        error: function() {
            $('#fine_amount').val('0');
        }
    });
    
    $('#condition_group').show();
    $('#returnDateLabel').text('<?=translate('return_date')?>');
    mfp_modal('#returnModal');
}

$(document).ready(function() {
    $('#return_type_display').on('change', function() {
        var type = $(this).val();
        $('#return_type').val(type);
        
        if (type == '1') { // Return
            $('#return_date').val('<?=date('Y-m-d')?>');
            $('#returnDateLabel').text('<?=translate('return_date')?>');
            $('#condition_group').show();
            
            // Recalculate fine
            var issue_id = $('#return_issue_id').val();
            $.ajax({
                url: base_url + 'library/calculate_fine_ajax',
                type: 'POST',
                data: { issue_id: issue_id },
                dataType: 'json',
                success: function(response) {
                    $('#fine_amount').val(response.fine);
                }
            });
        } else { // Renewal
            // For renewal, get current expiry date and add days
            $('#returnDateLabel').text('<?=translate('new_expiry_date')?>');
            $('#condition_group').hide();
            $('#fine_amount').val('0');
        }
    });
});
</script>