<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-chart-line"></i> <?=translate('subscription_revenue_report')?>
                </h4>
                <div class="panel-btn">
                    <form method="get" action="<?=base_url('subscription_admin/revenue')?>" class="form-inline">
                        <div class="form-group">
                            <select name="year" class="form-control input-sm" onchange="this.form.submit()">
                                <?php 
                                $current_year = date('Y');
                                for ($y = $current_year - 3; $y <= $current_year + 1; $y++): 
                                ?>
                                <option value="<?=$y?>" <?=($year == $y) ? 'selected' : ''?>>
                                    <?=$y?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <noscript>
                            <button type="submit" class="btn btn-primary btn-sm"><?=translate('go')?></button>
                        </noscript>
                    </form>
                </div>
            </header>
            
            <div class="panel-body">
                
                <!-- Summary Cards -->
                <div class="row mb-lg">
                    <div class="col-md-3">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?=translate('total_revenue')?></span>
                                <span class="info-box-number">KES <?=number_format($revenue['total'] ?? 0, 2)?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-aqua">
                            <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?=translate('total_transactions')?></span>
                                <span class="info-box-number"><?=array_sum($revenue['counts'] ?? [])?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-yellow">
                            <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?=translate('monthly_average')?></span>
                                <span class="info-box-number">
                                    KES <?=number_format(($revenue['total'] ?? 0) / 12, 2)?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-purple">
                            <span class="info-box-icon"><i class="fas fa-trophy"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?=translate('active_subscriptions')?></span>
                                <span class="info-box-number"><?=$stats->active ?? 0?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue Chart -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fas fa-bar-chart"></i> <?=translate('monthly_revenue')?> - <?=$year?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <canvas id="revenueChart" style="height: 400px; width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats and Breakdown -->
                <div class="row mt-lg">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fas fa-pie-chart"></i> <?=translate('subscription_statistics')?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <table class="table table-bordered table-condensed">
                                    <tr>
                                        <th width="50%"><?=translate('active_subscriptions')?></th>
                                        <td><?=$stats->active ?? 0?></td>
                                    </tr>
                                    <tr>
                                        <th><?=translate('expiring_soon_7_days')?></th>
                                        <td><?=$stats->expiring_soon ?? 0?></td>
                                    </tr>
                                    <tr>
                                        <th><?=translate('expired_subscriptions')?></th>
                                        <td><?=$stats->expired ?? 0?></td>
                                    </tr>
                                    <tr>
                                        <th><?=translate('total_branches')?></th>
                                        <td><?=$stats->total_branches ?? 0?></td>
                                    </tr>
                                    <tr>
                                        <th><?=translate('most_popular_plan')?></th>
                                        <td>
                                            <strong><?=html_escape($stats->most_popular_plan ?? 'N/A')?></strong>
                                            <span class="text-muted">(<?=$stats->most_popular_count ?? 0?> <?=translate('subscriptions')?>)</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fas fa-table"></i> <?=translate('monthly_breakdown')?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <table class="table table-bordered table-condensed">
                                    <thead>
                                        <tr>
                                            <th><?=translate('month')?></th>
                                            <th class="text-right"><?=translate('revenue')?> (KES)</th>
                                            <th class="text-center"><?=translate('transactions')?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($i = 0; $i < 12; $i++): ?>
                                        <tr>
                                            <td><?=$revenue['months'][$i] ?? ''?></td>
                                            <td class="text-right"><?=number_format($revenue['revenue'][$i] ?? 0, 2)?></td>
                                            <td class="text-center"><?=$revenue['counts'][$i] ?? 0?></td>
                                        </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="active">
                                            <th><?=translate('total')?></th>
                                            <th class="text-right">KES <?=number_format($revenue['total'] ?? 0, 2)?></th>
                                            <th class="text-center"><?=array_sum($revenue['counts'] ?? [])?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </section>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    "use strict";
    
    var ctx = document.getElementById('revenueChart').getContext('2d');
    
    var revenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?=json_encode($revenue['months'] ?? [])?>,
            datasets: [{
                label: '<?=translate('revenue')?> (KES)',
                data: <?=json_encode($revenue['revenue'] ?? [])?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'KES ' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>