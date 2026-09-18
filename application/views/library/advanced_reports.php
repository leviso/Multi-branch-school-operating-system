<?php
// Debug - remove after testing
error_log("=== View Debug ===");
error_log("Report Type: " . $report_type);
error_log("Branch ID in view: " . $branch_id);
error_log("Is Superadmin in view: " . ($is_superadmin ? 'Yes' : 'No'));
error_log("Report Data Count: " . count($report_data));
if (!empty($report_data)) {
    error_log("First record branch: " . ($report_data[0]['branch_id'] ?? 'N/A'));
}
?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <div class="tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="<?=$report_type == 'class_issued' ? 'active' : ''?>">
                        <a href="<?=base_url('library/advanced_reports?report_type=class_issued')?>"><i class="fas fa-users"></i> <?=translate('books_issued_by_class')?></a>
                    </li>
                    <li class="<?=$report_type == 'section_wise' ? 'active' : ''?>">
                        <a href="<?=base_url('library/advanced_reports?report_type=section_wise')?>"><i class="fas fa-layer-group"></i> <?=translate('section_wise_library_stats')?></a>
                    </li>
                    <li class="<?=$report_type == 'category_issued' ? 'active' : ''?>">
                        <a href="<?=base_url('library/advanced_reports?report_type=category_issued')?>"><i class="fas fa-tags"></i> <?=translate('books_issued_by_category')?></a>
                    </li>
                    <li class="<?=$report_type == 'not_returned' ? 'active' : ''?>">
                        <a href="<?=base_url('library/advanced_reports?report_type=not_returned')?>"><i class="fas fa-clock"></i> <?=translate('books_not_returned')?></a>
                    </li>
                    <li class="<?=$report_type == 'lost_damaged' ? 'active' : ''?>">
                        <a href="<?=base_url('library/advanced_reports?report_type=lost_damaged')?>"><i class="fas fa-exclamation-triangle"></i> <?=translate('lost_damaged_books')?></a>
                    </li>
                    <li class="<?=$report_type == 'inventory_summary' ? 'active' : ''?>">
                        <a href="<?=base_url('library/advanced_reports?report_type=inventory_summary')?>"><i class="fas fa-chart-pie"></i> <?=translate('inventory_summary')?></a>
                    </li>
                    <li class="<?=$report_type == 'borrower_history' ? 'active' : ''?>">
                        <a href="<?=base_url('library/advanced_reports?report_type=borrower_history')?>"><i class="fas fa-history"></i> <?=translate('borrower_history')?></a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <!-- Filter Section -->
                        <div class="panel-body">
                            <?php echo form_open($this->uri->uri_string(), array('method' => 'get', 'class' => 'form-horizontal')); ?>
                            <div class="row mb-sm">
                                <!-- Branch Filter for Superadmin -->
                                <?php if ($is_superadmin): ?>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label"><?=translate('branch')?></label>
                                        <select name="branch_id" id="branch_filter" class="form-control">
                                            <option value=""><?=translate('all_branches')?></option>
                                            <?php
                                            $branches = $this->db->select('id, name')->get('branch')->result_array();
                                            foreach ($branches as $b): ?>
                                            <option value="<?=$b['id']?>" <?=($this->input->get('branch_id') == $b['id']) ? 'selected' : ''?>>
                                                <?=htmlspecialchars($b['name'])?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Class Filter -->
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label"><?=translate('class')?></label>
                                        <select name="class_id" id="class_filter" class="form-control">
                                            <option value=""><?=translate('all_classes')?></option>
                                            <?php foreach ($classes as $class): ?>
                                            <option value="<?=$class['id']?>" <?=($class_id == $class['id']) ? 'selected' : ''?>>
                                                <?=htmlspecialchars($class['name'])?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Section Filter (for class_issued and section_wise reports) -->
                                <?php if (in_array($report_type, ['class_issued', 'section_wise'])): ?>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label"><?=translate('section')?></label>
                                        <select name="section_id" id="section_filter" class="form-control">
                                            <option value=""><?=translate('all_sections')?></option>
                                            <?php foreach ($sections as $section): ?>
                                            <option value="<?=$section['id']?>" <?=($section_id == $section['id']) ? 'selected' : ''?>>
                                                <?=htmlspecialchars($section['name'])?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Category Filter (for category_issued report) -->
                                <?php if ($report_type == 'category_issued'): ?>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label"><?=translate('category')?></label>
                                        <select name="category_id" class="form-control">
                                            <option value=""><?=translate('all_categories')?></option>
                                            <?php foreach ($categories as $cat): ?>
                                            <option value="<?=$cat['id']?>" <?=($category_id == $cat['id']) ? 'selected' : ''?>>
                                                <?=htmlspecialchars($cat['name'])?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Date Range Filters -->
                                <?php if (in_array($report_type, ['class_issued', 'category_issued', 'borrower_history'])): ?>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label"><?=translate('date_from')?></label>
                                        <input type="text" name="date_from" class="form-control datepicker" value="<?=$date_from?>" data-plugin-datepicker>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label"><?=translate('date_to')?></label>
                                        <input type="text" name="date_to" class="form-control datepicker" value="<?=$date_to?>" data-plugin-datepicker>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-default btn-block"><i class="fas fa-filter"></i> <?=translate('filter')?></button>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <a href="<?=base_url('library/advanced_reports?report_type=' . $report_type)?>" class="btn btn-default btn-block"><i class="fas fa-sync-alt"></i> <?=translate('reset')?></a>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="report_type" value="<?=$report_type?>">
                            <?php echo form_close(); ?>
                        </div>
                        
                        <!-- Report Content -->
                        <div class="panel-body">
                            <?php if ($report_type == 'class_issued'): ?>
                                <!-- Books Issued by Class Report (with Section Filter) -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-export">
                                        <thead>
                                            <tr>
                                                <th><?=translate('sl')?></th>
                                                <?php if ($is_superadmin): ?>
                                                <th><?=translate('branch')?></th>
                                                <?php endif; ?>
                                                <th><?=translate('student_name')?></th>
                                                <th><?=translate('register_no')?></th>
                                                <th><?=translate('class')?></th>
                                                <th><?=translate('section')?></th>
                                                <th><?=translate('book_title')?></th>
                                                <th><?=translate('copy_number')?></th>
                                                <th><?=translate('book_code')?></th>
                                                <th><?=translate('author')?></th>
                                                <th><?=translate('issue_date')?></th>
                                                <th><?=translate('due_date')?></th>
                                                <th><?=translate('days_overdue')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $count = 1;
                                            if (!empty($report_data) && is_array($report_data)):
                                                foreach ($report_data as $row): 
                                            ?>
                                            <tr>
                                                <td class="text-center"><?=$count++?></td>
                                                <?php if ($is_superadmin): ?>
                                                <td><?=htmlspecialchars($row['branch_name'] ?? get_type_name_by_id('branch', $row['branch_id']))?></td>
                                                <?php endif; ?>
                                                <td><?=htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))?></td>
                                                <td><?=$row['register_no'] ?? 'N/A'?></td>
                                                <td><?=htmlspecialchars($row['class_name'] ?? 'N/A')?></td>
                                                <td><?=htmlspecialchars($row['section_name'] ?? 'N/A')?></td>
                                                <td><?=htmlspecialchars($row['title'] ?? 'N/A')?></td>
                                                <td class="text-center">
                                                    <?php 
                                                    $copy_number = '';
                                                    if (!empty($row['copy_id'])) {
                                                        // Branch isolated query
                                                        $this->db->select('copy_number')
                                                                ->where('id', $row['copy_id']);
                                                        if (!$is_superadmin) {
                                                            $this->db->where('branch_id', $branch_id);
                                                        }
                                                        $copy = $this->db->get('book_copies')->row();
                                                        $copy_number = $copy->copy_number ?? '';
                                                    }
                                                    echo !empty($copy_number) ? '<span class="label label-info-custom" style="font-family: monospace;">' . $copy_number . '</span>' : '—';
                                                    ?>
                                                </td>
                                                <td><?=$row['book_code'] ?? 'N/A'?></td>
                                                <td><?=htmlspecialchars($row['author'] ?? 'N/A')?></td>
                                                <td><?=!empty($row['date_of_issue']) ? date('d/m/Y', strtotime($row['date_of_issue'])) : 'N/A'?></td>
                                                <td><?=!empty($row['date_of_expiry']) ? date('d/m/Y', strtotime($row['date_of_expiry'])) : 'N/A'?></td>
                                                <td class="text-center">
                                                    <?php if (isset($row['days_overdue']) && $row['days_overdue'] > 0): ?>
                                                    <span class="label label-danger-custom"><?=$row['days_overdue']?> <?=translate('days')?></span>
                                                    <?php else: ?>
                                                    <span class="label label-success-custom"><?=translate('on_time')?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php 
                                                endforeach;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="<?=$is_superadmin ? 12 : 11?>" class="text-center"><?=translate('no_data_found')?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                            <?php elseif ($report_type == 'section_wise'): ?>
                                <!-- Section Wise Library Statistics Report -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-export">
                                        <thead>
                                            <tr>
                                                <th><?=translate('sl')?></th>
                                                <?php if ($is_superadmin): ?>
                                                <th><?=translate('branch')?></th>
                                                <?php endif; ?>
                                                <th><?=translate('class')?></th>
                                                <th><?=translate('section')?></th>
                                                <th><?=translate('total_students')?></th>
                                                <th><?=translate('books_issued')?></th>
                                                <th><?=translate('books_returned')?></th>
                                                <th><?=translate('books_lost')?></th>
                                                <th><?=translate('books_damaged')?></th>
                                                <th><?=translate('books_overdue')?></th>
                                                <th><?=translate('avg_books_per_student')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $count = 1;
                                            $total_issued = 0;
                                            $total_returned = 0;
                                            $total_lost = 0;
                                            $total_damaged = 0;
                                            $total_overdue = 0;
                                            $total_students = 0;
                                            
                                            if (!empty($report_data) && is_array($report_data)):
                                                foreach ($report_data as $row): 
                                                    $total_issued += $row['books_issued'];
                                                    $total_returned += $row['books_returned'];
                                                    $total_lost += $row['books_lost'];
                                                    $total_damaged += $row['books_damaged'];
                                                    $total_overdue += $row['books_overdue'];
                                                    $total_students += $row['total_students'];
                                            ?>
                                            <tr>
                                                <td class="text-center"><?=$count++?></td>
                                                <?php if ($is_superadmin): ?>
                                                <td><?=htmlspecialchars($row['branch_name'] ?? get_type_name_by_id('branch', $row['branch_id']))?></td>
                                                <?php endif; ?>
                                                <td><?=htmlspecialchars($row['class_name'])?></td>
                                                <td><?=htmlspecialchars($row['section_name'])?></td>
                                                <td class="text-center"><?=$row['total_students']?></td>
                                                <td class="text-center">
                                                    <span class="label label-primary-custom"><?=$row['books_issued']?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="label label-success-custom"><?=$row['books_returned']?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="label label-danger-custom"><?=$row['books_lost']?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="label label-warning-custom"><?=$row['books_damaged']?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($row['books_overdue'] > 0): ?>
                                                    <span class="label label-danger-custom"><?=$row['books_overdue']?></span>
                                                    <?php else: ?>
                                                    <span class="label label-success-custom">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><?=$row['avg_books_per_student']?></td>
                                            </tr>
                                            <?php 
                                                endforeach;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="<?=$is_superadmin ? 11 : 10?>" class="text-center"><?=translate('no_data_found')?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <?php if (!empty($report_data) && count($report_data) > 1): ?>
                                        <tfoot>
                                            <tr>
                                                <th colspan="<?=$is_superadmin ? 4 : 3?>" class="text-right"><?=translate('total')?>:</th>
                                                <th class="text-center"><?=$total_students?></th>
                                                <th class="text-center"><?=$total_issued?></th>
                                                <th class="text-center"><?=$total_returned?></th>
                                                <th class="text-center"><?=$total_lost?></th>
                                                <th class="text-center"><?=$total_damaged?></th>
                                                <th class="text-center"><?=$total_overdue?></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                        <?php endif; ?>
                                    </table>
                                </div>
                                
                            <?php elseif ($report_type == 'category_issued'): ?>
                                <!-- Books Issued by Category Report -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-export">
                                        <thead>
                                            <tr>
                                                <th><?=translate('sl')?></th>
                                                <?php if ($is_superadmin): ?>
                                                <th><?=translate('branch')?></th>
                                                <?php endif; ?>
                                                <th><?=translate('category')?></th>
                                                <th><?=translate('student_name')?></th>
                                                <th><?=translate('register_no')?></th>
                                                <th><?=translate('class')?></th>
                                                <th><?=translate('section')?></th>
                                                <th><?=translate('book_title')?></th>
                                                <th><?=translate('author')?></th>
                                                <th><?=translate('issue_date')?></th>
                                                <th><?=translate('due_date')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $count = 1;
                                            if (!empty($report_data) && is_array($report_data)):
                                                foreach ($report_data as $row): 
                                            ?>
                                            <tr>
                                                <td class="text-center"><?=$count++?></td>
                                                <?php if ($is_superadmin): ?>
                                                <td><?=htmlspecialchars($row['branch_name'] ?? get_type_name_by_id('branch', $row['branch_id']))?></td>
                                                <?php endif; ?>
                                                <td><?=htmlspecialchars($row['category_name'] ?? 'N/A')?></td>
                                                <td><?=htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))?></td>
                                                <td><?=$row['register_no'] ?? 'N/A'?></td>
                                                <td><?=htmlspecialchars($row['class_name'] ?? 'N/A')?></td>
                                                <td><?=htmlspecialchars($row['section_name'] ?? 'N/A')?></td>
                                                <td><?=htmlspecialchars($row['title'])?></td>
                                                <td><?=htmlspecialchars($row['author'])?></td>
                                                <td><?=!empty($row['date_of_issue']) ? date('d/m/Y', strtotime($row['date_of_issue'])) : 'N/A'?></td>
                                                <td><?=!empty($row['date_of_expiry']) ? date('d/m/Y', strtotime($row['date_of_expiry'])) : 'N/A'?></td>
                                            </tr>
                                            <?php 
                                                endforeach;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="<?=$is_superadmin ? 11 : 10?>" class="text-center"><?=translate('no_data_found')?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                            <?php elseif ($report_type == 'not_returned'): ?>
                                <!-- Books Not Returned Report -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-export">
                                        <thead>
                                            <tr>
                                                <th><?=translate('sl')?></th>
                                                <?php if ($is_superadmin): ?>
                                                <th><?=translate('branch')?></th>
                                                <?php endif; ?>
                                                <th><?=translate('borrower_name')?></th>
                                                <th><?=translate('borrower_id')?></th>
                                                <th><?=translate('book_title')?></th>
                                                <th><?=translate('copy_number')?></th>
                                                <th><?=translate('book_code')?></th>
                                                <th><?=translate('author')?></th>
                                                <th><?=translate('issue_date')?></th>
                                                <th><?=translate('due_date')?></th>
                                                <th><?=translate('days_overdue')?></th>
                                                <th><?=translate('estimated_fine')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $count = 1;
                                            $fine_per_day = $this->db->select('fine_per_day')->where('branch_id', $branch_id)->get('library_settings')->row()->fine_per_day ?? 5;
                                            
                                            if (!empty($report_data) && is_array($report_data)):
                                                foreach ($report_data as $row): 
                                                    // Fix borrower name - get from database if missing
                                                    $borrower_name = $row['borrower_name'] ?? '';
                                                    $borrower_id = $row['borrower_id_no'] ?? '';
                                                    
                                                    if (empty($borrower_name) && !empty($row['user_id'])) {
                                                        if ($row['role_id'] == 7) {
                                                            $student = $this->application_model->getStudentDetails($row['user_id']);
                                                            $borrower_name = $student['first_name'] . ' ' . $student['last_name'];
                                                            $borrower_id = $student['register_no'];
                                                        } else {
                                                            $staff = $this->db->select('name, staff_id')->where('id', $row['user_id'])->get('staff')->row();
                                                            if ($staff) {
                                                                $borrower_name = $staff->name;
                                                                $borrower_id = $staff->staff_id;
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Get copy number
                                                    $copy_number = '';
                                                    if (!empty($row['copy_id'])) {
                                                        $copy = $this->db->select('copy_number')->where('id', $row['copy_id'])->get('book_copies')->row();
                                                        $copy_number = $copy->copy_number ?? '';
                                                    }
                                                    
                                                    // Calculate fine
                                                    $days_overdue = (int)($row['days_overdue'] ?? 0);
                                                    $calculated_fine = $days_overdue * $fine_per_day;
                                            ?>
                                            <tr>
                                                <td class="text-center"><?=$count++?></td>
                                                <?php if ($is_superadmin): ?>
                                                <td><?=htmlspecialchars($row['branch_name'] ?? get_type_name_by_id('branch', $row['branch_id']))?></td>
                                                <?php endif; ?>
                                                <td class="text-center"><?=htmlspecialchars($borrower_name ?: 'N/A')?></td>
                                                <td class="text-center"><?=htmlspecialchars($borrower_id ?: 'N/A')?></td>
                                                <td><?=htmlspecialchars($row['title'] ?? 'N/A')?></td>
                                                
                                                <!-- COPY NUMBER COLUMN -->
                                                <td class="text-center">
                                                    <?php if (!empty($copy_number)): ?>
                                                    <span class="label label-info-custom" style="font-family: monospace;"><?=$copy_number?></span>
                                                    <?php else: ?>
                                                    —
                                                    <?php endif; ?>
                                                </td>
                                                <!-- END COPY NUMBER -->
                                                
                                                <td><?=$row['book_code'] ?? 'N/A'?></td>
                                                <td><?=htmlspecialchars($row['author'] ?? 'N/A')?></td>
                                                <td><?=!empty($row['date_of_issue']) ? date('d/m/Y', strtotime($row['date_of_issue'])) : 'N/A'?></td>
                                                <td><?=!empty($row['date_of_expiry']) ? date('d/m/Y', strtotime($row['date_of_expiry'])) : 'N/A'?></td>
                                                <td class="text-center">
                                                    <?php if ($days_overdue > 0): ?>
                                                    <span class="label label-danger-custom"><?=$days_overdue?> <?=translate('days')?></span>
                                                    <?php else: ?>
                                                    <span class="label label-success-custom"><?=translate('on_time')?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right"><?=$global_config['currency_symbol'] . number_format($calculated_fine, 2)?></td>
                                            </tr>
                                            <?php 
                                                endforeach;
                                            endif;
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                            <?php elseif ($report_type == 'lost_damaged'): ?>
                                <!-- Lost/Damaged Books Report -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="panel panel-danger">
                                            <div class="panel-heading">
                                                <h4 class="panel-title"><i class="fas fa-times-circle"></i> <?=translate('lost_damaged_copies')?></h4>
                                            </div>
                                            <div class="panel-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th><?=translate('sl')?></th>
                                                                <?php if ($is_superadmin): ?>
                                                                <th><?=translate('branch')?></th>
                                                                <?php endif; ?>
                                                                <th><?=translate('book_title')?></th>
                                                                <th><?=translate('copy_number')?></th>
                                                                <th><?=translate('barcode')?></th>
                                                                <th><?=translate('condition')?></th>
                                                                <th><?=translate('notes')?></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php $count = 1; foreach ($report_data['copies'] ?? array() as $copy): ?>
                                                            <tr>
                                                                <td class="text-center"><?=$count++?></td>
                                                                <?php if ($is_superadmin): ?>
                                                                <td><?=htmlspecialchars($copy['branch_name'] ?? get_type_name_by_id('branch', $copy['branch_id']))?></td>
                                                                <?php endif; ?>
                                                                <td><?=htmlspecialchars($copy['title'])?></td>
                                                                <td><?=$copy['copy_number']?></td>
                                                                <td><?=$copy['barcode']?></td>
                                                                <td><span class="label label-danger-custom"><?=ucfirst($copy['condition'])?></span></td>
                                                                <td><?=htmlspecialchars($copy['damage_reason'] ?? '')?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                            <?php if (empty($report_data['copies'])): ?>
                                                            <tr><td colspan="<?=$is_superadmin ? 13 : 12?>" class="text-center"><?=translate('no_lost_damaged_copies')?></td></tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="panel panel-warning">
                                            <div class="panel-heading">
                                                <h4 class="panel-title"><i class="fas fa-money-bill-wave"></i> <?=translate('fine_penalties')?></h4>
                                            </div>
                                            <div class="panel-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th><?=translate('sl')?></th>
                                                                <?php if ($is_superadmin): ?>
                                                                <th><?=translate('branch')?></th>
                                                                <?php endif; ?>
                                                                <th><?=translate('book_title')?></th>
                                                                <th><?=translate('copy_number')?></th>
                                                                <th><?=translate('borrower')?></th>
                                                                <th><?=translate('penalty')?></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php $count = 1; foreach ($report_data['issues'] ?? array() as $issue): ?>
                                                            <tr>
                                                                <td class="text-center"><?=$count++?></td>
                                                                <?php if ($is_superadmin): ?>
                                                                <td><?=htmlspecialchars($issue['branch_name'] ?? get_type_name_by_id('branch', $issue['branch_id']))?></td>
                                                                <?php endif; ?>
                                                                <td><?=htmlspecialchars($issue['title'])?></td>
                                                                <td class="text-center">
                                                                    <?php 
                                                                    $copy_number = '';
                                                                    if (!empty($row['copy_id'])) {
                                                                        // Branch isolated query
                                                                        $this->db->select('copy_number')
                                                                                ->where('id', $row['copy_id']);
                                                                        if (!$is_superadmin) {
                                                                            $this->db->where('branch_id', $branch_id);
                                                                        }
                                                                        $copy = $this->db->get('book_copies')->row();
                                                                        $copy_number = $copy->copy_number ?? '';
                                                                    }
                                                                    echo !empty($copy_number) ? '<span class="label label-info-custom" style="font-family: monospace;">' . $copy_number . '</span>' : '—';
                                                                    ?>
                                                                </td>
                                                                <td><?=htmlspecialchars($issue['borrower_name'] ?? 'N/A')?></td>
                                                                <td><?=$global_config['currency_symbol'] . number_format($issue['penalty'] ?? 0, 2)?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                            <?php if (empty($report_data['issues'])): ?>
                                                            <tr><td colspan="<?=$is_superadmin ? 5 : 4?>" class="text-center"><?=translate('no_penalties')?></td></tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php elseif ($report_type == 'inventory_summary'): ?>
                                <!-- Inventory Summary Report -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="panel panel-primary">
                                            <div class="panel-body text-center">
                                                <h2><?=$report_data['totals']['unique_titles'] ?? 0?></h2>
                                                <p><?=translate('unique_book_titles')?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="panel panel-info">
                                            <div class="panel-body text-center">
                                                <h2><?=$report_data['totals']['total_copies'] ?? 0?></h2>
                                                <p><?=translate('total_copies')?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="panel panel-success">
                                            <div class="panel-body text-center">
                                                <h2><?=$report_data['totals']['total_available'] ?? 0?></h2>
                                                <p><?=translate('available_copies')?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><?=translate('inventory_by_category')?></h4>
                                    </div>
                                    <div class="panel-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-export">
                                                <thead>
                                                    <tr>
                                                        <th><?=translate('sl')?></th>
                                                        <?php if ($is_superadmin): ?>
                                                        <th><?=translate('branch')?></th>
                                                        <?php endif; ?>
                                                        <th><?=translate('category')?></th>
                                                        <th><?=translate('unique_titles')?></th>
                                                        <th><?=translate('total_copies')?></th>
                                                        <th><?=translate('issued')?></th>
                                                        <th><?=translate('available')?></th>
                                                        <th><?=translate('availability_rate')?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $count = 1; foreach ($report_data['by_category'] as $cat): ?>
                                                    <?php $availability_rate = $cat['total_copies'] > 0 ? round(($cat['total_available'] / $cat['total_copies']) * 100, 1) : 0; ?>
                                                    <tr>
                                                        <td class="text-center"><?=$count++?></td>
                                                        <?php if ($is_superadmin): ?>
                                                        <td><?=htmlspecialchars($cat['branch_name'] ?? get_type_name_by_id('branch', $cat['branch_id']))?></td>
                                                        <?php endif; ?>
                                                        <td><?=htmlspecialchars($cat['category_name'])?></td>
                                                        <td class="text-center"><?=$cat['total_books']?></td>
                                                        <td class="text-center"><?=$cat['total_copies']?></td>
                                                        <td class="text-center"><?=$cat['total_issued']?></td>
                                                        <td class="text-center"><?=$cat['total_available']?></td>
                                                        <td class="text-center">
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar progress-bar-success" style="width: <?=$availability_rate?>%;">
                                                                    <?=$availability_rate?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php elseif ($report_type == 'borrower_history'): ?>
                                <!-- Borrower History Report -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-export">
                                        <thead>
                                            <tr>
                                                <th><?=translate('sl')?></th>
                                                <?php if ($is_superadmin): ?>
                                                <th><?=translate('branch')?></th>
                                                <?php endif; ?>
                                                <th><?=translate('borrower_name')?></th>
                                                <th><?=translate('borrower_id')?></th>
                                                <th><?=translate('book_title')?></th>
                                                <th><?=translate('copy_number')?></th>
                                                <th><?=translate('book_code')?></th>
                                                <th><?=translate('author')?></th>
                                                <th><?=translate('issue_date')?></th>
                                                <th><?=translate('due_date')?></th>
                                                <th><?=translate('return_date')?></th>
                                                <th><?=translate('status')?></th>
                                                <th><?=translate('fine')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $count = 1;
                                            if (!empty($report_data) && is_array($report_data)):
                                                foreach ($report_data as $row): 
                                            ?>
                                            <tr>
                                                <td class="text-center"><?=$count++?></td>
                                                <?php if ($is_superadmin): ?>
                                                <td><?=htmlspecialchars($row['branch_name'] ?? get_type_name_by_id('branch', $row['branch_id']))?></td>
                                                <?php endif; ?>
                                                <td><?=htmlspecialchars($row['borrower_name'] ?? 'N/A')?></td>
                                                <td><?=$row['borrower_id_no'] ?? 'N/A'?></td>
                                                <td><?=htmlspecialchars($row['title'] ?? 'N/A')?></td>
                                                <td class="text-center">
                                                    <?php 
                                                    $copy_number = '';
                                                    if (!empty($row['copy_id'])) {
                                                        // Branch isolated query
                                                        $this->db->select('copy_number')
                                                                ->where('id', $row['copy_id']);
                                                        if (!$is_superadmin) {
                                                            $this->db->where('branch_id', $branch_id);
                                                        }
                                                        $copy = $this->db->get('book_copies')->row();
                                                        $copy_number = $copy->copy_number ?? '';
                                                    }
                                                    echo !empty($copy_number) ? '<span class="label label-info-custom" style="font-family: monospace;">' . $copy_number . '</span>' : '—';
                                                    ?>
                                                </td>
                                                <td><?=$row['book_code'] ?? 'N/A'?></td>
                                                <td><?=htmlspecialchars($row['author'] ?? 'N/A')?></td>
                                                <td><?=!empty($row['date_of_issue']) ? date('d/m/Y', strtotime($row['date_of_issue'])) : 'N/A'?></td>
                                                <td><?=!empty($row['date_of_expiry']) ? date('d/m/Y', strtotime($row['date_of_expiry'])) : 'N/A'?></td>
                                                <td><?=!empty($row['return_date']) ? date('d/m/Y', strtotime($row['return_date'])) : '—'?></td>
                                                <td class="text-center">
                                                    <?php
                                                    $status = $row['status'] ?? 0;
                                                    $status_class = [
                                                        0 => 'warning',
                                                        1 => 'success',
                                                        2 => 'danger',
                                                        3 => 'info'
                                                    ];
                                                    $class = $status_class[$status] ?? 'default';
                                                    $status_text = $row['status_text'] ?? 'Unknown';
                                                    ?>
                                                    <span class="label label-<?=$class?>-custom"><?=$status_text?></span>
                                                </td>
                                                <td class="text-center"><?=$global_config['currency_symbol'] . number_format($row['fine_amount'] ?? 0, 2)?></td>
                                            </tr>
                                            <?php 
                                                endforeach;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="<?=$is_superadmin ? 12 : 11?>" class="text-center"><?=translate('no_data_found')?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Initialize datepickers
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
    
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
    
    // Dynamic section loading based on selected class
    $('#class_filter').on('change', function() {
        var class_id = $(this).val();
        var $section_select = $('#section_filter');
        var report_type = '<?=$report_type?>';
        
        // Only load sections for reports that need them
        if (report_type == 'class_issued' || report_type == 'section_wise') {
            if (class_id) {
                $.ajax({
                    url: base_url + 'library/get_sections_for_report',
                    type: 'POST',
                    data: { class_id: class_id },
                    dataType: 'json',
                    success: function(response) {
                        $section_select.html('<option value=""><?=translate('all_sections')?></option>');
                        if (response.sections && response.sections.length > 0) {
                            $.each(response.sections, function(index, section) {
                                var selected = (section.id == '<?=$section_id?>') ? 'selected' : '';
                                $section_select.append('<option value="' + section.id + '" ' + selected + '>' + section.name + '</option>');
                            });
                        }
                    }
                });
            } else {
                $section_select.html('<option value=""><?=translate('all_sections')?></option>');
            }
        }
    });
});
</script>