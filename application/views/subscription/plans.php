<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-cubes"></i> <?=translate('subscription_plans')?>
                </h4>
                <div class="panel-btn">
                    <a href="<?=base_url('subscription_admin/plan_form')?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle"></i> <?=translate('add_plan')?>
                    </a>
                </div>
            </header>
            <div class="panel-body">
                <div class="table-responsive mt-lg">
                    <table class="table table-bordered table-hover table-condensed mb-none table-export">
                        <thead>
                            <tr>
                                <th width="50"><?=translate('sl')?></th>
                                <th><?=translate('plan_name')?></th>
                                <th><?=translate('description')?></th>
                                <th class="text-right"><?=translate('monthly')?></th>
                                <th class="text-right"><?=translate('termly')?></th>
                                <th class="text-right"><?=translate('yearly')?></th>
                                <th class="text-center"><?=translate('max_students')?></th>
                                <th class="text-center"><?=translate('max_staff')?></th>
                                <th class="text-center"><?=translate('sms_units')?></th>
                                <th><?=translate('modules')?></th>
                                <th class="text-center"><?=translate('status')?></th>
                                <th class="no-sort"><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            if (!empty($plans)): 
                            foreach ($plans as $plan): 
                            ?>
                            <tr>
                                <td><?=$count++?></td>
                                <td><strong><?=html_escape($plan->name)?></strong></td>
                                <td><?=html_escape($plan->description)?></td>
                                <td class="text-right"><?=number_format($plan->price_monthly, 2)?></td>
                                <td class="text-right"><?=number_format($plan->price_termly, 2)?></td>
                                <td class="text-right"><?=number_format($plan->price_yearly, 2)?></td>
                                <td class="text-center"><?=$plan->max_students ?: '∞'?></td>
                                <td class="text-center"><?=$plan->max_staff ?: '∞'?></td>
                                <td class="text-center"><?=$plan->max_sms_units ?: '∞'?></td>
                                <td>
                                    <?php 
                                    if (!empty($plan->modules)):
                                        $module_names = array_column($plan->modules, 'name');
                                        $display = array_slice($module_names, 0, 3);
                                        echo html_escape(implode(', ', $display));
                                        if (count($module_names) > 3) echo ' + ' . (count($module_names) - 3) . ' more';
                                    endif;
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($plan->is_active): ?>
                                        <span class="label label-success"><?=translate('active')?></span>
                                    <?php else: ?>
                                        <span class="label label-danger"><?=translate('inactive')?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="<?=base_url('subscription_admin/plan_form/' . $plan->id)?>" class="btn btn-default btn-circle icon">
                                        <i class="fas fa-pen-nib"></i>
                                    </a>
                                    <?php if ($plan->is_active): ?>
                                    <a href="<?=base_url('subscription_admin/plan_delete/' . $plan->id)?>" 
                                       class="btn btn-danger btn-circle icon" 
                                       onclick="return confirm('<?=translate('confirm_delete')?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                            endforeach;
                            else: 
                            ?>
                            <tr>
                                <td colspan="12" class="text-center"><?=translate('no_information_available')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    "use strict";
    
    // Check if DataTable is already initialized
    var table = $('.table-export');
    
    if (!$.fn.DataTable.isDataTable(table)) {
        table.DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copyHtml5',
                    text: '<i class="fas fa-copy"></i> Copy',
                    title: 'Subscription Plans'
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    title: 'Subscription Plans'
                },
                {
                    extend: 'csvHtml5',
                    text: '<i class="fas fa-file-csv"></i> CSV',
                    title: 'Subscription Plans'
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    title: 'Subscription Plans',
                    orientation: 'landscape'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print'
                }
            ],
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']]
        });
    }
});
</script>