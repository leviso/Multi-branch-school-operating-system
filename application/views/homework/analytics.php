<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-chart-line"></i> <?=translate('homework_analytics')?></h4>
            </header>
            <div class="panel-body">
                <!-- Filter Row -->
                <div class="row mb-lg">
                    <?php if ($is_superadmin && !empty($all_branches)): ?>
                    <div class="col-md-3">
                        <label><?=translate('branch')?></label>
                        <select id="branch_filter" class="form-control">
                            <option value="all"><?=translate('all_branches')?></option>
                            <?php foreach ($all_branches as $branch): ?>
                            <option value="<?=$branch['id']?>"><?=htmlspecialchars($branch['name'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <label><?=translate('select_subject')?></label>
                        <select id="subject_filter" class="form-control">
                            <option value=""><?=translate('select_subject')?></option>
                            <?php if (!empty($subjects)): ?>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?=$subject['id']?>" data-branch="<?=$subject['branch_id']?>">
                                    <?php if ($is_superadmin): ?>
                                        [<?=htmlspecialchars($subject['branch_name'] ?? 'N/A')?>] 
                                    <?php endif; ?>
                                    <?=htmlspecialchars($subject['name'])?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled><?=translate('no_subjects_found')?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label><?=translate('period')?></label>
                        <select id="period_filter" class="form-control">
                            <option value="month"><?=translate('this_month')?></option>
                            <option value="term"><?=translate('this_term')?></option>
                            <option value="year"><?=translate('this_year')?></option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button id="refresh_chart" class="btn btn-primary btn-block">
                            <i class="fas fa-sync-alt"></i> <?=translate('refresh')?>
                        </button>
                    </div>
                </div>
                
                <!-- Chart -->
                <div class="row">
                    <div class="col-md-12">
                        <canvas id="homeworkChart" style="height: 400px; width: 100%;"></canvas>
                    </div>
                </div>
                
                <!-- Summary Statistics Cards -->
                <div class="row mt-lg" id="summary_cards">
                    <?php if (!empty($stats)): ?>
                        <?php foreach ($stats as $data): ?>
                        <div class="col-md-4 col-lg-3 stat-card" data-branch="<?=$data['branch_id']?>">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title text-center">
                                        <?=htmlspecialchars($data['subject_name'])?>
                                        <?php if ($is_superadmin): ?>
                                            <br><small class="text-muted"><?=htmlspecialchars($data['branch_name'])?></small>
                                        <?php endif; ?>
                                    </h4>
                                </div>
                                <div class="panel-body text-center">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-md">
                                                <i class="fas fa-book-open"></i>
                                                <span class="text-primary" style="font-size: 24px; font-weight: bold;"><?=$data['homework_count']?></span>
                                                <br><small><?=translate('total_homework')?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="mt-sm mb-sm">
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <div class="text-success" style="font-size: 20px; font-weight: bold;"><?=$data['submission_rate']?>%</div>
                                            <small><?=translate('submission_rate')?></small>
                                            <div class="text-muted small">(<?=$data['total_submissions']?>/<?=$data['total_assignments']?>)</div>
                                        </div>
                                        <div class="col-xs-6">
                                            <i class="fas fa-star text-warning"></i>
                                            <div class="text-warning" style="font-size: 20px; font-weight: bold;"><?=$data['grading_rate']?>%</div>
                                            <small><?=translate('grading_rate')?></small>
                                            <div class="text-muted small">(<?=$data['total_graded']?>/<?=$data['total_submissions']?>)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-md-12">
                            <div class="alert alert-info text-center"><?=translate('no_homework_data_available')?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var homeworkChart = null;

function initChart(labels, submissionData, gradingData) {
    var ctx = document.getElementById('homeworkChart').getContext('2d');
    
    if (homeworkChart) {
        homeworkChart.destroy();
    }
    
    if (!labels || labels.length === 0) {
        homeworkChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['<?=translate('no_data')?>'],
                datasets: [
                    {
                        label: '<?=translate('submission_rate')?>',
                        data: [0],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: '<?=translate('grading_rate')?>',
                        data: [0],
                        backgroundColor: 'rgba(255, 206, 86, 0.5)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                }
            }
        });
        return;
    }
    
    homeworkChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '<?=translate('submission_rate')?>',
                    data: submissionData,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: '<?=translate('grading_rate')?>',
                    data: gradingData,
                    backgroundColor: 'rgba(255, 206, 86, 0.6)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: '<?=translate('percentage')?> (%)',
                        font: { weight: 'bold', size: 12 }
                    },
                    ticks: { callback: function(value) { return value + '%'; } }
                },
                x: {
                    title: {
                        display: true,
                        text: '<?=translate('assignment_due_date')?>',
                        font: { weight: 'bold', size: 12 }
                    },
                    ticks: { maxRotation: 45, minRotation: 45 }
                }
            },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 10 } },
                tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': ' + context.raw + '%'; } } }
            }
        }
    });
}

function filterSummaryCardsByBranch() {
    var selectedBranch = $('#branch_filter').val();
    $('.stat-card').each(function() {
        var cardBranch = $(this).data('branch');
        if (selectedBranch === 'all' || cardBranch == selectedBranch) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

function loadSubjectsByBranch() {
    var branch_id = $('#branch_filter').val();
    var subjectSelect = $('#subject_filter');
    
    subjectSelect.html('<option value=""><?=translate('loading')?>...</option>').prop('disabled', true);
    
    $.ajax({
        url: base_url + 'homework/get_subjects_by_branch',
        type: 'POST',
        data: { branch_id: branch_id },
        dataType: 'json',
        success: function(response) {
            if (response.status == 'success') {
                var options = '<option value=""><?=translate('select_subject')?></option>';
                $.each(response.subjects, function(index, subject) {
                    var branchDisplay = response.is_superadmin ? '[' + subject.branch_name + '] ' : '';
                    options += '<option value="' + subject.id + '" data-branch="' + subject.branch_id + '">' + branchDisplay + escapeHtml(subject.name) + '</option>';
                });
                subjectSelect.html(options).prop('disabled', false);
            } else {
                subjectSelect.html('<option value=""><?=translate('no_subjects_found')?></option>').prop('disabled', false);
            }
        },
        error: function() {
            subjectSelect.html('<option value=""><?=translate('failed_to_load_subjects')?></option>').prop('disabled', false);
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

$(document).ready(function() {
    initChart([], [], []);
    
    $('#branch_filter').on('change', function() {
        loadSubjectsByBranch();
        filterSummaryCardsByBranch();
    });
    
    $('#subject_filter').on('change', function() {
        if ($(this).val()) {
            $('#refresh_chart').click();
        }
    });
    
    $('#refresh_chart').on('click', function() {
        var subject_id = $('#subject_filter').val();
        var period = $('#period_filter').val();
        var branch_id = $('#branch_filter').length ? $('#branch_filter').val() : '';
        var btn = $(this);
        
        if (!subject_id) {
            toastr.warning('<?=translate('please_select_subject')?>');
            return;
        }
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> <?=translate('loading')?>').prop('disabled', true);
        
        $.ajax({
            url: base_url + 'homework/get_analytics_data',
            type: 'POST',
            data: { subject_id: subject_id, period: period, branch_id: branch_id },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    initChart(response.labels, response.submission, response.grading);
                    toastr.success('<?=translate('chart_updated')?>');
                } else {
                    toastr.error(response.message || '<?=translate('failed_to_load_data')?>');
                }
            },
            error: function() {
                toastr.error('<?=translate('failed_to_load_data')?>');
            },
            complete: function() {
                btn.html('<i class="fas fa-sync-alt"></i> <?=translate('refresh')?>').prop('disabled', false);
            }
        });
    });
    
    if ($('#branch_filter').length && $('#branch_filter').val() !== 'all') {
        loadSubjectsByBranch();
        filterSummaryCardsByBranch();
    }
});
</script>

<style>
.mb-lg { margin-bottom: 20px; }
.mt-lg { margin-top: 20px; }
.mb-md { margin-bottom: 15px; }
.mt-sm { margin-top: 10px; }
.mb-sm { margin-bottom: 10px; }
.stat-card { margin-bottom: 20px; }
.stat-card .panel { height: 100%; margin-bottom: 0; }
.stat-card .panel-heading { background-color: #f5f5f5; border-bottom: 1px solid #ddd; }
.stat-card hr { margin: 10px 0; }
canvas { max-height: 400px; width: 100%; }
</style>