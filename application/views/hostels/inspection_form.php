<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-clipboard-list"></i> <?=translate('new_inspection')?></h4>
            </header>
            <div class="panel-body">
           <?php echo form_open('hostels/save_inspection', array('class' => 'custom-inspection-form', 'id' => 'inspectionForm')); ?>
    
            <!-- Add hidden CSRF token -->
            <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>">
            
            <!-- Add submission token to prevent duplicate -->
            <input type="hidden" name="submission_token" value="<?php echo uniqid(); ?>">
            
            <!-- Branch selection for Superadmin -->
           <?php if (is_superadmin_loggedin() && isset($branches)): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?=translate('branch')?> <span class="required">*</span></label>
                        <select class="form-control" name="branch_id" id="branch_select" 
                                onchange="window.location.href='<?=base_url('hostels/create_inspection')?>?branch_id='+this.value">
                            <option value=""><?=translate('select')?></option>
                            <?php foreach($branches as $branch): ?>
                            <option value="<?=$branch['id']?>" <?=($selected_branch_id == $branch['id']) ? 'selected' : ''?>>
                                <?=$branch['name']?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php endif; ?>
                
   
              <!-- Add this hidden token to prevent duplicate submission -->
                <input type="hidden" name="submission_token" value="<?php echo uniqid(); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('room')?> <span class="required">*</span></label>
                                <select name="room_id" id="room_id" class="form-control" required>
                                    <option value=""><?=translate('select')?></option>
                                    <?php foreach($rooms as $room): ?>
                                    <option value="<?=$room['id']?>"><?=$room['name']?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('inspection_type')?> <span class="required">*</span></label>
                                <select name="inspection_type" id="inspection_type" class="form-control" required>
                                    <option value="daily">Daily Inspection</option>
                                    <option value="weekly">Weekly Inspection</option>
                                    <option value="monthly">Monthly Inspection</option>
                                    <option value="surprise">Surprise Inspection</option>
                                </select>
                                <span class="error"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('inspection_date')?> <span class="required">*</span></label>
                                <input type="text" name="inspection_date" class="form-control datepicker" value="<?=date('Y-m-d H:i')?>" required>
                                <span class="error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('template')?></label>
                                <select name="template_id" id="template_id" class="form-control">
                                    <option value=""><?=translate('select_template')?></option>
                                    <?php foreach($templates as $template): ?>
                                    <option value="<?=$template['id']?>"><?=$template['name']?> (<?=$template['item_count']?> items)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div id="checklist_container" style="display:none;">
                        <div class="headers-line mt-md">
                            <i class="fas fa-check-double"></i> <?=translate('inspection_checklist')?>
                        </div>
                        <div id="checklist_items" class="mt-md"></div>
                    </div>
                    
                    <div id="issues_container" style="display:none;">
                        <div class="headers-line mt-md">
                            <i class="fas fa-exclamation-triangle"></i> <?=translate('issues_found')?>
                        </div>
                        <div id="issues_list" class="mt-md"></div>
                        <button type="button" class="btn btn-sm btn-default mt-sm" onclick="addIssue()">
                            <i class="fas fa-plus"></i> <?=translate('add_issue')?>
                        </button>
                    </div>
                    
                    <div class="form-group mt-md">
                        <label><?=translate('general_remarks')?></label>
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <footer class="panel-footer mt-md">
                        <div class="row">
                            <div class="col-md-offset-10 col-md-2">
                                <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                                    <i class="fas fa-save"></i> <?=translate('save_inspection')?>
                                </button>
                            </div>
                        </div>
                    </footer>
                <?php echo form_close();?>
            </div>
        </section>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var csrf_token = '<?=$this->security->get_csrf_hash();?>';
var issueCount = 0;
var isSubmitting = false;

$(document).ready(function() {
    // Initialize datepicker
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
    
    // ========== LOAD TEMPLATE ITEMS WHEN TEMPLATE IS SELECTED ==========
    $('#template_id').change(function() {
        var templateId = $(this).val();
        console.log('Template selected:', templateId);
        
        if (templateId) {
            // Show loading indicator
            $('#checklist_container').show();
            $('#checklist_items').html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Loading checklist items...</div>');
            
            $.ajax({
                url: base_url + 'hostels/get_template_items',
                type: 'POST',
                data: {
                    template_id: templateId,
                    '<?=$this->security->get_csrf_token_name();?>': csrf_token
                },
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    console.log('AJAX Response:', response);
                    console.log('Response length:', response.length);
                    
                    if (response && response.length > 0) {
                        generateChecklistTable(response);
                        $('#issues_container').show();
                    } else {
                        $('#checklist_items').html('<div class="alert alert-warning">No checklist items found for this template. Please add items to the template first.</div>');
                        $('#issues_container').hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.log('Response Text:', xhr.responseText);
                    $('#checklist_items').html('<div class="alert alert-danger">Error loading checklist: ' + error + '<br>Please check the console for details.</div>');
                    $('#issues_container').hide();
                }
            });
        } else {
            $('#checklist_container').hide();
            $('#issues_container').hide();
            $('#checklist_items').html('');
        }
    });
    
// ========== HANDLE FORM SUBMISSION ==========
var isSubmitting = false;

$('#inspectionForm').on('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log('Form submission started');
    
    // Prevent double submission
    if (isSubmitting) {
        console.log('Already submitting, ignoring...');
        return false;
    }
    
    // Validate required fields
    var roomId = $('#room_id').val();
    var inspectionType = $('#inspection_type').val();
    var templateId = $('#template_id').val();
    
    if (!roomId) {
        alert('Please select a room');
        $('#room_id').focus();
        return false;
    }
    
    if (!inspectionType) {
        alert('Please select inspection type');
        $('#inspection_type').focus();
        return false;
    }
    
    if (!templateId) {
        alert('Please select a template');
        $('#template_id').focus();
        return false;
    }
    
    // Validate scores are selected
    var scoresValid = true;
    var scoreSelects = $('.score-select');
    
    if (scoreSelects.length === 0) {
        alert('Please select a template to load checklist items');
        return false;
    }
    
    scoreSelects.each(function() {
        if (!$(this).val()) {
            scoresValid = false;
            $(this).css('border', '1px solid red');
        } else {
            $(this).css('border', '');
        }
    });
    
    if (!scoresValid) {
        alert('Please select a score for all checklist items');
        return false;
    }
    
    // Set submitting flag
    isSubmitting = true;
    
    // Disable submit button
    var submitBtn = $('#submitBtn');
    var originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    
    // Collect form data
    var formData = new FormData(this);
     // For superadmin, ensure branch_id is included
    <?php if (is_superadmin_loggedin()): ?>
    var branchId = $('#branch_select').val();
    if (!branchId) {
        alert('Please select a branch');
        return false;
    }
    formData.append('branch_id', branchId);
    <?php endif; ?>
    
    formData.append('<?=$this->security->get_csrf_token_name();?>', csrf_token);
    
    console.log('Sending AJAX request to:', $(this).attr('action'));
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 30000,
        success: function(response) {
            console.log('Save response:', response);
            
            if (response.status === 'success') {
                alert(response.message);
                window.location.href = base_url + 'hostels/inspections';
            } else {
                var errorMsg = response.message || 'Failed to save inspection';
                if (response.error) {
                    if (typeof response.error === 'object') {
                        errorMsg = Object.values(response.error).join('\n');
                    } else {
                        errorMsg = response.error;
                    }
                }
                alert('Error: ' + errorMsg);
                // Reset submitting flag
                isSubmitting = false;
                submitBtn.prop('disabled', false).html(originalText);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.error('Response Text:', xhr.responseText);
            
            var errorMsg = 'Server error: ' + error;
            alert(errorMsg);
            
            // Reset submitting flag
            isSubmitting = false;
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
    
    return false;
});
});

// ========== GENERATE CHECKLIST TABLE ==========
function generateChecklistTable(items) {
    var html = '<div class="table-responsive">';
    html += '<table class="table table-bordered table-striped">';
    html += '<thead>';
    html += '<tr>';
    html += '<th width="25%">Category</th>';
    html += '<th width="35%">Item</th>';
    html += '<th width="20%">Score (1-5)</th>';
    html += '<th width="20%">Comments</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';
    
    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var category = item.category ? item.category.charAt(0).toUpperCase() + item.category.slice(1) : 'General';
        
        html += '<tr>';
        html += '<td>' + escapeHtml(category) + '</td>';
        html += '<td>' + escapeHtml(item.item_name) + '</td>';
        html += '<td>';
        html += '<select name="scores[' + i + '][score]" class="form-control score-select" data-max="' + item.max_score + '" data-weight="' + item.weight + '" required>';
        html += '<option value="">Select</option>';
        html += '<option value="0">0 - Not applicable</option>';
        html += '<option value="1">1 - Very Poor</option>';
        html += '<option value="2">2 - Poor</option>';
        html += '<option value="3">3 - Average</option>';
        html += '<option value="4">4 - Good</option>';
        html += '<option value="5">5 - Excellent</option>';
        html += '</select>';
        html += '<input type="hidden" name="scores[' + i + '][template_item_id]" value="' + item.id + '">';
        html += '<input type="hidden" name="scores[' + i + '][max_score]" value="' + item.max_score + '">';
        html += '<input type="hidden" name="scores[' + i + '][weight]" value="' + item.weight + '">';
        html += '</td>';
        html += '<td><input type="text" name="scores[' + i + '][comments]" class="form-control" placeholder="Optional comments"></td>';
        html += '</tr>';
    }
    
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    html += '<div id="score_display" class="alert alert-info mt-md" style="display:none;">';
    html += '<strong>Current Score: <span id="current_score">0</span>%</strong>';
    html += '</div>';
    
    $('#checklist_items').html(html);
    
    // Attach score calculation event
    $('.score-select').on('change', function() {
        calculateTotalScore();
    });
    
    // Calculate initial score if any scores are selected
    calculateTotalScore();
}

// ========== CALCULATE TOTAL SCORE ==========
function calculateTotalScore() {
    var totalWeightedScore = 0;
    var totalWeight = 0;
    var itemsCount = 0;
    
    $('.score-select').each(function() {
        var score = parseInt($(this).val());
        if (!isNaN(score) && score >= 0) {
            var maxScore = parseInt($(this).data('max')) || 5;
            var weight = parseFloat($(this).data('weight')) || 1;
            
            var weighted = (score / maxScore) * weight;
            totalWeightedScore += weighted;
            totalWeight += weight;
            itemsCount++;
        }
    });
    
    if (itemsCount > 0 && totalWeight > 0) {
        var overall = (totalWeightedScore / totalWeight) * 100;
        overall = Math.min(100, Math.max(0, overall));
        
        $('#score_display').show();
        $('#current_score').text(overall.toFixed(1));
        
        // Add color coding
        var scoreColor = overall >= 75 ? 'green' : (overall >= 60 ? 'orange' : 'red');
        $('#current_score').css('color', scoreColor);
        $('#current_score').css('font-weight', 'bold');
        $('#current_score').css('font-size', '18px');
    } else {
        $('#score_display').hide();
    }
}

// ========== ADD ISSUE ==========
function addIssue() {
    issueCount++;
    var html = '<div class="row issue-item mb-sm" id="issue_' + issueCount + '">';
    html += '<div class="col-md-5">';
    html += '<input type="text" name="issues[' + issueCount + '][description]" class="form-control" placeholder="Issue Description" required>';
    html += '</div>';
    html += '<div class="col-md-3">';
    html += '<select name="issues[' + issueCount + '][priority]" class="form-control">';
    html += '<option value="low">Low</option>';
    html += '<option value="medium">Medium</option>';
    html += '<option value="high">High</option>';
    html += '<option value="emergency">Emergency</option>';
    html += '</select>';
    html += '</div>';
    html += '<div class="col-md-3">';
    html += '<input type="text" name="issues[' + issueCount + '][action]" class="form-control" placeholder="Action Required (optional)">';
    html += '</div>';
    html += '<div class="col-md-1">';
    html += '<button type="button" class="btn btn-danger btn-sm" onclick="$(\'#issue_' + issueCount + '\').remove()">';
    html += '<i class="fas fa-trash"></i>';
    html += '</button>';
    html += '</div>';
    html += '</div>';
    
    $('#issues_list').append(html);
}

// ========== HELPER FUNCTIONS ==========
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatDate(date) {
    var d = new Date(date);
    var month = '' + (d.getMonth() + 1);
    var day = '' + d.getDate();
    var year = d.getFullYear();
    var hours = d.getHours();
    var minutes = d.getMinutes();
    
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    if (hours.length < 2) hours = '0' + hours;
    if (minutes.length < 2) minutes = '0' + minutes;
    
    return year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
}
</script>