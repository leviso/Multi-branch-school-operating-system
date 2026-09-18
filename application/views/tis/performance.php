<?php $widget = (is_superadmin_loggedin() ? 4 : 6); ?>
<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><?=translate('teacher_performance_scores')?></h4>
            </header>
            <?php echo form_open($this->uri->uri_string(), array('class' => 'validate'));?>
            <div class="panel-body">
                <div class="row mb-sm">
                    <?php if (is_superadmin_loggedin()): ?>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
                            <?php
                                $arrayBranch = $this->app_lib->getSelectList('branch');
                                echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id', $branch_id), "class='form-control' onchange='this.form.submit()'
                                data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-<?php echo $widget; ?>">
                        <div class="form-group">
                            <label class="control-label"><?=translate('select_term')?></label>
                            <?php
                                $terms = $this->db->where('branch_id', $branch_id)->order_by('id', 'DESC')->get('exam_term')->result();
                                $term_options = array('' => translate('all_terms'));
                                foreach($terms as $term) {
                                    $term_options[$term->id] = $term->name;
                                }
                                echo form_dropdown("term_id", $term_options, set_value('term_id', $selected_term), "class='form-control' onchange='this.form.submit()'
                                data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
                            ?>
                        </div>
                    </div>
                    <div class="col-md-<?php echo $widget; ?>">
                        <div class="form-group">
                            <label class="control-label"><?=translate('rating_filter')?></label>
                            <?php
                                $rating_options = array(
                                    '' => translate('all_ratings'),
                                    'A' => 'A - Excellent',
                                    'B' => 'B - Good',
                                    'C' => 'C - Satisfactory',
                                    'D' => 'D - Needs Improvement'
                                );
                                echo form_dropdown("rating", $rating_options, set_value('rating'), "class='form-control' onchange='this.form.submit()'
                                data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-offset-10 col-md-2">
                        <button type="submit" name="search" value="1" class="btn btn-default btn-block"> <i class="fas fa-filter"></i> <?=translate('filter')?></button>
                    </div>
                </div>
            </footer>
            <?php echo form_close();?>
        </section>

        <?php if (isset($teachers)): ?>
        <section class="panel appear-animation" data-appear-animation="<?=$global_config['animations'];?>" data-appear-animation-delay="100">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-bar"></i> <?=translate('performance_distribution')?></h4>
            </header>
            <div class="panel-body">
                <!-- Performance Distribution Cards -->
                <div class="row mb-lg">
                    <div class="col-md-3">
                        <div class="panel panel-default bg-success" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $stats['A']; ?></h2>
                                <p class="mb-none">A - Excellent</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default bg-info" style="background: linear-gradient(135deg, #17a2b8 0%, #4facfe 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $stats['B']; ?></h2>
                                <p class="mb-none">B - Good</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default bg-warning" style="background: linear-gradient(135deg, #ffc107 0%, #f6d365 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $stats['C']; ?></h2>
                                <p class="mb-none">C - Satisfactory</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default bg-danger" style="background: linear-gradient(135deg, #dc3545 0%, #fa709a 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $stats['D']; ?></h2>
                                <p class="mb-none">D - Needs Improvement</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="panel panel-default mb-lg">
                    <div class="panel-heading">
                        <h4 class="panel-title"><i class="fas fa-trophy"></i> <?=translate('top_performers')?></h4>
                    </div>
                    <div class="panel-body">
                        <div class="list-group">
                            <?php foreach($top_performers as $index => $teacher): ?>
                            <div class="list-group-item">
                                <span class="badge">#<?php echo $index + 1; ?></span>
                                <strong><?php echo htmlspecialchars($teacher->name, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="pull-right">
                                    <span class="label label-success"><?php echo $teacher->rating; ?></span>
                                    <span class="label label-info"><?php echo round($teacher->total_score, 1); ?>%</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($top_performers)): ?>
                            <div class="list-group-item text-center"><?=translate('no_data_found')?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-export">
                        <thead>
                            <tr>
                                <th><?=translate('rank')?></th>
                                <?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('teacher_name')?></th>
                                <th><?=translate('lesson_completion')?></th>
                                <th><?=translate('attendance')?></th>
                                <th><?=translate('student_impact')?></th>
                                <th><?=translate('duty_completion')?></th>
                                <th><?=translate('marking_speed')?></th>
                                <th><?=translate('total_score')?></th>
                                <th><?=translate('rating')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach($teachers as $teacher): 
                                $perf = isset($performance_data[$teacher->id]) ? $performance_data[$teacher->id] : null;
                            ?>
                            <tr>
                                <td><strong>#<?php echo $rank++; ?></strong></td>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo get_type_name_by_id('branch', $teacher->branch_id); ?></td>
                                <?php endif; ?>
                                <td>
                                    <strong><?php echo htmlspecialchars($teacher->name, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                    <small class="text-muted"><?php echo $teacher->staff_id; ?></small>
                                </td>
                                <td>
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar progress-bar-info" style="width: <?php echo $perf->lesson_completion_score ?? 0; ?>%;">
                                            <?php echo round($perf->lesson_completion_score ?? 0, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php $att = $perf->attendance_compliance_score ?? 0; ?>
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar progress-bar-<?php echo $att >= 80 ? 'success' : ($att >= 60 ? 'warning' : 'danger'); ?>" style="width: <?php echo $att; ?>%;">
                                            <?php echo round($att, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar progress-bar-primary" style="width: <?php echo $perf->student_impact_score ?? 0; ?>%;">
                                            <?php echo round($perf->student_impact_score ?? 0, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar progress-bar-warning" style="width: <?php echo $perf->duty_completion_score ?? 0; ?>%;">
                                            <?php echo round($perf->duty_completion_score ?? 0, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar progress-bar-default" style="width: <?php echo $perf->marking_speed_score ?? 0; ?>%;">
                                            <?php echo round($perf->marking_speed_score ?? 0, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><strong><?php echo round($perf->total_score ?? 0, 1); ?>%</strong></td>
                                <td>
                                    <?php
                                    $rating_badge = 'danger';
                                    if($perf->rating == 'A') $rating_badge = 'success';
                                    elseif($perf->rating == 'B') $rating_badge = 'info';
                                    elseif($perf->rating == 'C') $rating_badge = 'warning';
                                    ?>
                                    <span class="label label-<?php echo $rating_badge; ?> label-lg"><?php echo $perf->rating ?? 'D'; ?></span>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($teachers)): ?>
                            <tr>
                                <td colspan="<?php echo (is_superadmin_loggedin() ? 10 : 9); ?>" class="text-center"><?=translate('no_records_found')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<style>
.mb-lg { margin-bottom: 20px; }
</style>