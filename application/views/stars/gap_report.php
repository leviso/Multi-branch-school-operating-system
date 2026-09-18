<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-chart-line"></i> Gap Analysis Report
                </h4>
                <div class="panel-btn">
                    <button onclick="window.print();" class="btn btn-circle btn-default">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </header>
            <div class="panel-body">
                
                <!-- Student Summary Card -->
                <div class="alert <?=($overall_gap > 50) ? 'alert-danger' : (($overall_gap > 25) ? 'alert-warning' : 'alert-success')?>">
                    <div class="row">
                        <div class="col-md-6">
                            <h4><?=$assessment['first_name']?> <?=$assessment['last_name']?></h4>
                            <p><strong>Guardian:</strong> <?=$assessment['guardian_name']?> | <strong>Mobile:</strong> <?=$assessment['grd_mobile_no']?></p>
                            <p><strong>Class:</strong> <?=$assessment['class_name']?> | <strong>Transfer Date:</strong> <?=date('d M Y', strtotime($assessment['transfer_date']))?></p>
                        </div>
                        <div class="col-md-6 text-right">
                            <h2>
                                Overall Gap: 
                                <span class="label <?=($overall_gap > 50) ? 'label-danger' : (($overall_gap > 25) ? 'label-warning' : 'label-success')?>">
                                    <?=$overall_gap?>%
                                </span>
                            </h2>
                            <p class="mt-md">
                                <strong>Recommendation:</strong>
                                <?php if ($overall_gap > 50): ?>
                                    <span class="text-danger">Immediate IARP Required</span>
                                <?php elseif ($overall_gap > 25): ?>
                                    <span class="text-warning">IARP Recommended</span>
                                <?php else: ?>
                                    <span class="text-success">On Track - Minimal Support Needed</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Gap by Subject -->
                <div class="headers-line mt-md">
                    <i class="fas fa-chart-bar"></i> Gap Analysis by Subject
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Total Topics</th>
                                <th>Covered</th>
                                <th>Missing</th>
                                <th>Gap %</th>
                                <th>Severity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gaps as $gap): ?>
                            <tr class="<?=($gap['severity'] == 'red') ? 'danger' : (($gap['severity'] == 'amber') ? 'warning' : 'success')?>">
                                <td><strong><?=$gap['subject_name']?></strong></td>
                                <td><?=$gap['total_topics_class']?></td>
                                <td><?=$gap['covered_topics_student']?></td>
                                <td><?=$gap['missing_topics']?></td>
                                <td>
                                    <div class="progress" style="margin-bottom: 0;">
                                        <div class="progress-bar progress-bar-<?=($gap['severity'] == 'red') ? 'danger' : (($gap['severity'] == 'amber') ? 'warning' : 'success')?>" 
                                             role="progressbar" style="width: <?=$gap['gap_percentage']?>%;">
                                            <?=round($gap['gap_percentage'], 1)?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="label label-<?=($gap['severity'] == 'red') ? 'danger' : (($gap['severity'] == 'amber') ? 'warning' : 'success')?>">
                                        <?=strtoupper($gap['severity'])?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Action Buttons -->
                <div class="row mt-lg">
                    <div class="col-md-12 text-center">
                        <a href="<?=base_url('stars/assessment_detail/' . $transfer_id)?>" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Back to Assessment
                        </a>
                        <a href="<?=base_url('stars/generate_iarp/' . $transfer_id)?>" class="btn btn-success btn-lg">
                            <i class="fas fa-file-alt"></i> Generate IARP
                        </a>
                        <a href="<?=base_url('stars/assessments')?>" class="btn btn-default">
                            <i class="fas fa-list"></i> All Assessments
                        </a>
                    </div>
                </div>
                
            </div>
        </section>
    </div>
</div>

<style>
@media print {
    .panel-btn, .btn, .progress-bar {
        display: none;
    }
    .alert {
        border: 1px solid #ddd;
    }
}
</style>