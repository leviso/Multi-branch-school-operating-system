<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fas fa-clipboard-list"></i> <?=translate('inspection_details')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?=base_url('hostels/inspections')?>" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> <?=translate('back_to_list')?>
                        </a>
                        <a href="<?=base_url('hostels/inspection_reports')?>" class="btn btn-info">
                            <i class="fas fa-chart-bar"></i> <?=translate('reports')?>
                        </a>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <!-- Summary Cards -->
                <div class="row mb-md">
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo number_format($inspection['overall_score'], 1); ?>%</h3>
                                <p class="text-muted">Overall Score</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><span class="label label-<?php 
                                    echo $inspection['grade'] == 'EE1' || $inspection['grade'] == 'EE2' ? 'success' : 
                                        ($inspection['grade'] == 'ME1' || $inspection['grade'] == 'ME2' ? 'info' : 
                                        ($inspection['grade'] == 'AE1' || $inspection['grade'] == 'AE2' ? 'warning' : 'danger')); 
                                ?> label-lg"><?php echo $inspection['grade']; ?></span></h3>
                                <p class="text-muted">Grade Level</p>
                                <small><?php echo $inspection['grade_name'] ?? ''; ?></small><br>
                                <small><?php echo $inspection['grade_descriptor'] ?? ''; ?> (<?php echo $inspection['grade_points'] ?? ''; ?> Points)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo date('d M Y H:i', strtotime($inspection['inspection_date'])); ?></h3>
                                <p class="text-muted">Inspection Date</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default text-center">
                            <div class="panel-body">
                                <h3><?php echo count($inspection['issues']); ?></h3>
                                <p class="text-muted">Issues Found</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Basic Information -->
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tbody>
                                <tr><th width="40%">Room</th><td><?php echo $inspection['room_name']; ?></td></td>
                                <tr><th>Hostel</th><td><?php echo $inspection['hostel_name']; ?></td></tr>
                                <tr><th>Inspection Type</th><td><span class="label label-info"><?php echo ucfirst($inspection['inspection_type']); ?></span></td></tr>
                                <tr><th>Inspector</th><td><?php echo $inspection['inspector_name']; ?></td></tr>
                                <tr><th>Remarks</th><td><?php echo nl2br($inspection['remarks']); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">Score Distribution</div>
                            <div class="panel-body">
                                <canvas id="scoreChart" style="height: 200px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
               <!-- Checklist Scores -->
                <div class="headers-line mt-md">
                    <i class="fas fa-check-double"></i> <?=translate('inspection_checklist')?>
                </div>
                <div class="table-responsive mt-md">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?=translate('category')?></th>
                                <th><?=translate('item')?></th>
                                <th><?=translate('score')?></th>
                                <th><?=translate('max_score')?></th>
                                <th><?=translate('comments')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inspection['scores'])): ?>
                                <?php foreach($inspection['scores'] as $score): ?>
                                <tr>
                                    <td><?php echo ucfirst($score['category']); ?>
                                    <td><?php echo $score['item_name']; ?>
                                    <td>
                                        <?php 
                                        $score_class = $score['score'] >= 4 ? 'success' : ($score['score'] >= 3 ? 'warning' : 'danger');
                                        ?>
                                        <span class="label label-<?php echo $score_class; ?>"><?php echo $score['score']; ?></span>
                                    
                                    <td><?php echo $score['max_score']; ?>
                                    <td><?php echo $score['comments']; ?>
                                `
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center"><?=translate('no_checklist_items_found')?>
                                `
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Issues Found -->
                <?php if(!empty($inspection['issues'])): ?>
                <div class="headers-line mt-md">
                    <i class="fas fa-exclamation-triangle"></i> <?=translate('issues_found')?>
                </div>
                <div class="table-responsive mt-md">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Issue Description</th>
                                <th>Priority</th>
                                <th>Repair Ticket</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($inspection['issues'] as $issue): ?>
                            <tr>
                                <td><?php echo $issue['issue_description']; ?>
                                <td>
                                    <span class="label label-<?php 
                                        echo $issue['priority'] == 'emergency' ? 'danger' : 
                                            ($issue['priority'] == 'high' ? 'warning' : 
                                            ($issue['priority'] == 'medium' ? 'info' : 'default')); 
                                    ?>">
                                        <?php echo ucfirst($issue['priority']); ?>
                                    </span>
                                
                                <td>
                                    <?php if($issue['repair_id']): ?>
                                    <a href="<?=base_url('hostels/repairs')?>" class="btn btn-xs btn-info">View Repair</a>
                                    <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                
                                <td>
                                    <span class="label label-<?php echo $issue['resolved_date'] ? 'success' : 'warning'; ?>">
                                        <?php echo $issue['resolved_date'] ? 'Resolved' : 'Pending'; ?>
                                    </span>
                                
                             `
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- Photos -->
                <?php if(!empty($inspection['photos'])): ?>
                <div class="headers-line mt-md">
                    <i class="fas fa-camera"></i> <?=translate('inspection_photos')?>
                </div>
                <div class="row mt-md">
                    <?php foreach($inspection['photos'] as $photo): ?>
                    <div class="col-md-3">
                        <img src="<?=base_url('uploads/inspections/' . $photo['photo'])?>" class="img-responsive img-thumbnail">
                        <p class="text-center small"><?=$photo['caption']?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var ctx = document.getElementById('scoreChart').getContext('2d');
var scoreChart = new Chart(ctx, {
    type: 'radar',
    data: {
        labels: [<?php 
            $labels = array();
            foreach($inspection['scores'] as $score) {
                $labels[] = '"' . addslashes($score['item_name']) . '"';
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Score',
            data: [<?php 
                $values = array();
                foreach($inspection['scores'] as $score) {
                    $values[] = ($score['score'] / $score['max_score']) * 100;
                }
                echo implode(',', $values);
            ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2
        }]
    },
    options: {
        scales: {
            r: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});
</script>