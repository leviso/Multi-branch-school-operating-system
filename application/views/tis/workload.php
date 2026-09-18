<?php $widget = (is_superadmin_loggedin() ? 4 : 6); ?>
<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><?=translate('workload_management')?></h4>
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
                <h4 class="panel-title"><i class="fas fa-tachometer-alt"></i> <?=translate('workload_distribution')?></h4>
            </header>
            <div class="panel-body">
                <!-- Workload Summary Cards -->
                <div class="row mb-lg">
                    <div class="col-md-3">
                        <div class="panel panel-default bg-success" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $workload_distribution->normal_count ?? 0; ?></h2>
                                <p class="mb-none"><?=translate('normal_workload')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $workload_distribution->underutilized_count ?? 0; ?></h2>
                                <p class="mb-none"><?=translate('underutilized')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo $workload_distribution->overloaded_count ?? 0; ?></h2>
                                <p class="mb-none"><?=translate('overloaded')?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default bg-info" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; margin-bottom: 0;">
                            <div class="panel-body text-center">
                                <h2 class="mt-none mb-none"><?php echo round($workload_distribution->avg_score ?? 0, 1); ?>%</h2>
                                <p class="mb-none"><?=translate('average_workload')?></p>
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
                                <th><?=translate('weekly_hours')?></th>
                                <th><?=translate('total_classes')?></th>
                                <th><?=translate('total_students')?></th>
                                <th><?=translate('workload_score')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            foreach($teachers as $teacher): 
                                $workload = isset($workload_data[$teacher->id]) ? $workload_data[$teacher->id] : null;
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
                                <td><?php echo $workload->weekly_hours_assigned ?? 0; ?> hrs</td>
                                <td><?php echo $workload->total_classes ?? 0; ?></td>
                                <td><?php echo $workload->total_students ?? 0; ?></td>
                                <td>
                                    <?php $score = round($workload->workload_score ?? 0, 1); ?>
                                    <div class="progress" style="height: 20px;">
                                        <?php
                                        $bar_class = 'success';
                                        if($score > 100) $bar_class = 'danger';
                                        elseif($score > 80) $bar_class = 'warning';
                                        ?>
                                        <div class="progress-bar progress-bar-<?php echo $bar_class; ?>" role="progressbar" style="width: <?php echo min(100, $score); ?>%;">
                                            <?php echo $score; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'success';
                                    $status_text = 'Normal';
                                    if($workload->status == 'overloaded') {
                                        $status_class = 'danger';
                                        $status_text = 'Overloaded';
                                    } elseif($workload->status == 'underutilized') {
                                        $status_class = 'warning';
                                        $status_text = 'Underutilized';
                                    }
                                    ?>
                                    <span class="label label-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
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
</style>