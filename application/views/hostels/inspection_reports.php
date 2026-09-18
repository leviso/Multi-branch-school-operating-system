<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-bar"></i> <?=translate('inspection_reports')?></h4>
            </header>
            <div class="panel-body">
                <!-- Filter Form -->
                <form method="get" class="form-inline mb-md">
                    <div class="form-group">
                        <label><?=translate('room')?></label>
                        <select name="room_id" class="form-control">
                            <option value=""><?=translate('all_rooms')?></option>
                            <?php foreach($rooms as $room): ?>
                            <option value="<?=$room['id']?>" <?=($this->input->get('room_id') == $room['id']) ? 'selected' : ''?>>
                                <?=$room['name']?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=translate('period')?></label>
                        <input type="text" name="start_date" class="form-control datepicker" placeholder="Start Date" value="<?=$start_date?>">
                        <input type="text" name="end_date" class="form-control datepicker" placeholder="End Date" value="<?=$end_date?>">
                    </div>
                    <button type="submit" class="btn btn-default">Generate Report</button>
                </form>
                
                <!-- Score Chart -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">Room Performance (Kenyan CBC Grading)</div>
                            <div class="panel-body">
                                <canvas id="roomChart" style="height: 400px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Inspections Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-export">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Inspector</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>Issues</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($inspections as $insp): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($insp['inspection_date'])); ?>
                                <td><?php echo $insp['room_name']; ?>
                                <td><?php echo ucfirst($insp['inspection_type']); ?>
                                <td><?php echo $insp['inspector_name']; ?>
                                <td><?php echo number_format($insp['overall_score'], 1); ?>%
                                <td>
                                    <?php 
                                    // Convert old 8-4-4 grades to CBC format
                                    $display_grade = $insp['grade'];
                                    $grade_class = '';
                                    
                                    if($insp['grade'] == 'A') {
                                        $display_grade = 'EE2';
                                        $grade_class = 'success';
                                    } elseif($insp['grade'] == 'B') {
                                        $display_grade = 'ME1';
                                        $grade_class = 'info';
                                    } elseif($insp['grade'] == 'C') {
                                        $display_grade = 'ME2';
                                        $grade_class = 'info';
                                    } elseif($insp['grade'] == 'D') {
                                        $display_grade = 'AE1';
                                        $grade_class = 'warning';
                                    } elseif($insp['grade'] == 'F') {
                                        $display_grade = 'BE1';
                                        $grade_class = 'danger';
                                    } elseif($insp['grade'] == 'EE1' || $insp['grade'] == 'EE2') {
                                        $display_grade = $insp['grade'];
                                        $grade_class = 'success';
                                    } elseif($insp['grade'] == 'ME1' || $insp['grade'] == 'ME2') {
                                        $display_grade = $insp['grade'];
                                        $grade_class = 'info';
                                    } elseif($insp['grade'] == 'AE1' || $insp['grade'] == 'AE2') {
                                        $display_grade = $insp['grade'];
                                        $grade_class = 'warning';
                                    } else {
                                        $display_grade = $insp['grade'];
                                        $grade_class = 'danger';
                                    }
                                    ?>
                                    <span class="label label-<?php echo $grade_class; ?>">
                                        <?php echo $display_grade; ?>
                                    </span>
                                </td>
                                <td><?php echo $insp['issue_count'] ?? 0; ?>
                             `
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php 
$labels = array();
$values = array();
$background_colors = array();
$border_colors = array();

foreach($chart_data as $data) {
    $labels[] = '"' . addslashes($data['room']) . '"';
    $values[] = round($data['average'], 1);
    
    $avg_score = round($data['average'], 1);
    if ($avg_score >= 75) {
        $background_colors[] = 'rgba(40, 167, 69, 0.5)';
        $border_colors[] = 'rgba(40, 167, 69, 1)';
    } elseif ($avg_score >= 58) {
        $background_colors[] = 'rgba(0, 123, 255, 0.5)';
        $border_colors[] = 'rgba(0, 123, 255, 1)';
    } elseif ($avg_score >= 31) {
        $background_colors[] = 'rgba(255, 193, 7, 0.5)';
        $border_colors[] = 'rgba(255, 193, 7, 1)';
    } else {
        $background_colors[] = 'rgba(220, 53, 69, 0.5)';
        $border_colors[] = 'rgba(220, 53, 69, 1)';
    }
}
?>

var ctx = document.getElementById('roomChart').getContext('2d');
var roomChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', $labels); ?>],
        datasets: [{
            label: 'Average Score (%)',
            data: [<?php echo implode(',', $values); ?>],
            backgroundColor: [<?php echo "'" . implode("','", $background_colors) . "'"; ?>],
            borderColor: [<?php echo "'" . implode("','", $border_colors) . "'"; ?>],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Score (%)'
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        var score = context.raw;
                        var gradeText = '';
                        if (score >= 75) {
                            gradeText = ' - Exceeding Expectations (EE)';
                        } else if (score >= 58) {
                            gradeText = ' - Meeting Expectations (ME)';
                        } else if (score >= 31) {
                            gradeText = ' - Approaching Expectations (AE)';
                        } else {
                            gradeText = ' - Below Expectations (BE)';
                        }
                        return score + '%' + gradeText;
                    }
                }
            }
        }
    }
});

$('.datepicker').datepicker({
    format: 'yyyy-mm-dd',
    autoclose: true
});
</script>