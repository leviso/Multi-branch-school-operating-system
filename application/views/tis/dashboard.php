<?php $widget = (is_superadmin_loggedin() ? 4 : 6); ?>
<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><?=translate('teacher_intelligence_dashboard')?></h4>
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
                <h4 class="panel-title"><i class="fas fa-chalkboard-teacher"></i> <?=translate('teacher_performance_overview')?></h4>
            </header>
            <div class="panel-body">
                <!-- Summary Cards Row -->
                <div class="row mb-lg">
                    <div class="col-md-3">
                        <div class="panel panel-default bg-primary-400" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $summary['total_teachers']; ?></h2>
                                <p class="mb-none"><?=translate('total_teachers')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $summary['overloaded']; ?></h2>
                                <p class="mb-none"><?=translate('overloaded')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $summary['high_performers']; ?></h2>
                                <p class="mb-none"><?=translate('high_performers')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $summary['high_risk']; ?></h2>
                                <p class="mb-none"><?=translate('high_risk')?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('teacher_name')?></th>
                                <th><?=translate('designation')?></th>
                                <th><?=translate('workload_status')?></th>
                                <th><?=translate('performance')?></th>
                                <th><?=translate('risk_level')?></th>
                                <th><?=translate('pending_duties')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            foreach($teachers as $teacher): 
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo get_type_name_by_id('branch', $teacher->branch_id); ?></td>
                                <?php endif; ?>
                                <td>
                                    <strong><?php echo htmlspecialchars($teacher->name, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                    <small class="text-muted"><?php echo $teacher->staff_id; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($teacher->designation_name ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php
                                    $status_class = 'success';
                                    $status_text = 'Normal';
                                    if($teacher->status == 'overloaded') {
                                        $status_class = 'danger';
                                        $status_text = 'Overloaded';
                                    } elseif($teacher->status == 'underutilized') {
                                        $status_class = 'warning';
                                        $status_text = 'Underutilized';
                                    }
                                    ?>
                                    <span class="label label-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    <div class="progress mt-sm mb-none" style="height: 5px;">
                                        <div class="progress-bar progress-bar-<?php echo $status_class; ?>" role="progressbar" style="width: <?php echo round($teacher->workload_score ?? 0, 1); ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $rating_badge = 'danger';
                                    if($teacher->rating == 'A') $rating_badge = 'success';
                                    elseif($teacher->rating == 'B') $rating_badge = 'info';
                                    elseif($teacher->rating == 'C') $rating_badge = 'warning';
                                    ?>
                                    <span class="label label-<?php echo $rating_badge; ?>"><?php echo $teacher->rating ?? 'D'; ?></span>
                                    <small>(<?php echo round($teacher->total_score ?? 0, 1); ?>%)</small>
                                </td>
                                <td>
                                    <?php
                                    $risk_badge = 'success';
                                    if($teacher->risk_level == 'medium') $risk_badge = 'warning';
                                    elseif($teacher->risk_level == 'high') $risk_badge = 'danger';
                                    ?>
                                    <span class="label label-<?php echo $risk_badge; ?>"><?php echo ucfirst($teacher->risk_level ?? 'Low'); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $pending = get_pending_duties_count($teacher->id);
                                    echo $pending;
                                    if($pending > 0): ?>
                                        <span class="badge badge-danger ml-sm"><?php echo $pending; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo base_url('employee/profile/'.$teacher->id); ?>" class="btn btn-xs btn-default">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($teachers)): ?>
                            <tr>
                                <td colspan="<?php echo (is_superadmin_loggedin() ? 9 : 8); ?>" class="text-center"><?=translate('no_records_found')?></td>
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
.mt-sm { margin-top: 5px; }
.mb-none { margin-bottom: 0; }
.mt-none { margin-top: 0; }
.ml-sm { margin-left: 5px; }
</style>