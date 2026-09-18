<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-book"></i> Manage Curriculum Topics (Syllabus)
                </h4>
            </header>
            <div class="panel-body">
                
                <!-- Filter Section -->
                <div class="row">
                    <?php if (is_superadmin_loggedin()): ?>
                    <!-- Superadmin: Show Branch dropdown -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Branch</label>
                            <select id="topicBranchId" class="form-control">
                                <option value="">Select Branch</option>
                                <?php 
                                $branches = $this->db->order_by('name', 'ASC')->get('branch')->result_array();
                                $selected_branch = $this->session->userdata('selected_branch') ?: $branch_id;
                                foreach ($branches as $branch): 
                                ?>
                                <option value="<?=$branch['id']?>" <?=($selected_branch == $branch['id']) ? 'selected' : ''?>>
                                    <?=html_escape($branch['school_name'])?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <!-- Class column -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Class</label>
                            <select id="topicClassId" class="form-control">
                                <option value="">Select Class</option>
                            </select>
                        </div>
                    </div>
                    <!-- Subject column -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Subject</label>
                            <select id="topicSubjectId" class="form-control">
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                    </div>
                    <!-- Load button -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block" id="loadTopicsBtn">
                                <i class="fas fa-search"></i> Load Topics
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Admin: Branch field hidden, Class takes 4 columns, Subject takes 4, Button takes 4 -->
                    <input type="hidden" id="topicBranchId" value="<?=$branch_id?>">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Class</label>
                            <select id="topicClassId" class="form-control">
                                <option value="">Select Class</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Subject</label>
                            <select id="topicSubjectId" class="form-control">
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block" id="loadTopicsBtn">
                                <i class="fas fa-search"></i> Load Topics
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Add Topic Form -->
                <div class="headers-line mt-md">
                    <i class="fas fa-plus-circle"></i> Add New Topic
                </div>
                
                <form id="addTopicForm" class="row" style="margin-top: 15px;">
                    <div class="col-md-4">
                        <input type="text" name="topic_name" class="form-control" placeholder="Topic Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="topic_code" class="form-control" placeholder="Code (optional)">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="topic_order" class="form-control" placeholder="Order" value="0" step="1">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="expected_weeks" class="form-control" placeholder="Weeks" value="1.0" step="0.5">
                    </div>
                    <div class="col-md-2">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="is_competency_based" value="1"> Competency Based
                        </label>
                    </div>
                    <div class="col-md-12 mt-sm">
                        <button type="submit" class="btn btn-success pull-right">
                            <i class="fas fa-plus"></i> Add Topic
                        </button>
                    </div>
                </form>
                
                <!-- Topics Table -->
                <div class="table-responsive mt-lg">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="50">Order</th>
                                <th>Topic Name</th>
                                <th>Code</th>
                                <th width="80">Weeks</th>
                                <th width="80">CBC</th>
                                <th width="100">Actions</th>
                            </thead>
                        <tbody id="topicsTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Select class and subject, then click "Load Topics"
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </section>
    </div>
</div>

<script>
// Define CSRF token if not already defined
if (typeof csrf_token === 'undefined') {
    var csrf_token = '<?=$this->security->get_csrf_hash()?>';
}
if (typeof base_url === 'undefined') {
    var base_url = '<?=base_url()?>';
}
var is_superadmin = <?=is_superadmin_loggedin() ? 'true' : 'false'?>;

$(document).ready(function() {
    
    // ========== LOAD CLASSES WHEN BRANCH CHANGES ==========
    $('#topicBranchId').on('change', function() {
        var branchId = $(this).val();
        var $classSelect = $('#topicClassId');
        var $subjectSelect = $('#topicSubjectId');
        
        if (!branchId) {
            $classSelect.html('<option value="">Select Class</option>');
            $subjectSelect.html('<option value="">Select Subject</option>');
            $('#topicsTableBody').html('<tr><td colspan="6" class="text-center">Select class and subject</td></tr>');
            return;
        }
        
        $classSelect.html('<option value="">Loading classes...</option>');
        
        $.ajax({
            url: base_url + 'ajax/getClassByBranch',
            type: 'POST',
            data: { branch_id: branchId },
            success: function(data) {
                if (data && data.trim() !== '') {
                    $classSelect.html('<option value="">Select Class</option>' + data);
                } else {
                    $classSelect.html('<option value="">No classes found</option>');
                }
                $classSelect.val('');
            },
            error: function() {
                $classSelect.html('<option value="">Error loading classes</option>');
            }
        });
        
        // Reset dependent selects
        $subjectSelect.html('<option value="">Select Subject</option>');
        $('#topicsTableBody').html('<tr><td colspan="6" class="text-center">Select class and subject</td></tr>');
    });
    
   // ========== LOAD SUBJECTS WHEN CLASS CHANGES ==========
    $('#topicClassId').on('change', function() {
        var classId = $(this).val();
        var branchId = $('#topicBranchId').val();
        var $subjectSelect = $('#topicSubjectId');
        
        console.log('Class changed to:', classId, 'Branch:', branchId);
        
        if (!classId || classId == '') {
            $subjectSelect.html('<option value="">Select Subject</option>');
            return;
        }
        
        if (!branchId || branchId == '') {
            $subjectSelect.html('<option value="">Select Branch First</option>');
            return;
        }
        
        $subjectSelect.html('<option value="">Loading subjects...</option>');
        
        $.ajax({
            url: base_url + 'ajax/getSubjectByClass',
            type: 'POST',
            data: { 
                class_id: classId, 
                branch_id: branchId
            },
            dataType: 'text',
            success: function(data) {
                if (data && data.trim() !== '' && data.indexOf('option') > -1) {
                    $subjectSelect.html('<option value="">Select Subject</option>' + data);
                } else {
                    $subjectSelect.html('<option value="">' + data + '</option>');
                }
                $subjectSelect.val('');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $subjectSelect.html('<option value="">Error loading subjects</option>');
            }
        });
    });
    
    // ========== LOAD TOPICS ==========
$('#loadTopicsBtn').on('click', function() {
    var classId = $('#topicClassId').val();
    var subjectId = $('#topicSubjectId').val();
    var branchId = $('#topicBranchId').val();
    
    if (!classId || !subjectId) {
        alert('Please select both Class and Subject');
        return;
    }
    
    $('#topicsTableBody').html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
    
    $.ajax({
        url: base_url + 'stars/get_topics_ajax',
        type: 'POST',
        data: {
            class_id: classId,
            subject_id: subjectId,
            branch_id: branchId,
            csrf_test_name: csrf_token
        },
        dataType: 'json',
        success: function(topics) {
            console.log('Topics received:', topics);  // Debug: see what's coming back
            
            if (topics.error) {
                $('#topicsTableBody').html('<td><td colspan="6" class="text-center text-danger">' + topics.error + '</td></tr>');
                return;
            }
            
            if (topics.length === 0) {
                $('#topicsTableBody').html('<td><td colspan="6" class="text-center text-muted">No topics found. Add your first topic below.</td></tr>');
                return;
            }
            
            var html = '';
            for (var i = 0; i < topics.length; i++) {
                var t = topics[i];
                
                // Debug each topic
                console.log('Topic:', t.id, t.topic_name, 'Order:', t.topic_order, 'Weeks:', t.expected_weeks);
                
                // Get values with defaults
                var topicOrder = (t.topic_order !== null && t.topic_order !== undefined) ? t.topic_order : 0;
                var expectedWeeks = (t.expected_weeks !== null && t.expected_weeks !== undefined) ? t.expected_weeks : 1.0;
                var topicCode = (t.topic_code && t.topic_code !== '') ? escapeHtml(t.topic_code) : '-';
                var isCbc = (t.is_competency_based == 1) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>';
                
                html += '<tr id="topicRow_' + t.id + '">';
                html += '<td class="text-center">' + topicOrder + '</td>';
                html += '<td class="topic-name">' + escapeHtml(t.topic_name) + '</td>';
                html += '<td>' + topicCode + '</td>';
                html += '<td class="text-center">' + expectedWeeks + '</td>';
                html += '<td class="text-center">' + isCbc + '</td>';
                html += '<td class="text-center">';
                html += '<button class="btn btn-xs btn-info edit-topic" data-id="' + t.id + '" data-name="' + escapeHtml(t.topic_name) + '" data-order="' + topicOrder + '" data-weeks="' + expectedWeeks + '" data-code="' + escapeHtml(topicCode) + '" data-cbc="' + (t.is_competency_based || 0) + '"><i class="fas fa-edit"></i></button> ';
                html += '<button class="btn btn-xs btn-danger delete-topic" data-id="' + t.id + '"><i class="fas fa-trash"></i></button>';
                html += '</td>';
                html += '</tr>';
            }
            $('#topicsTableBody').html(html);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            $('#topicsTableBody').html('<tr><td colspan="6" class="text-center text-danger">Error loading topics. Please try again.<table></tr>');
        }
    });
});
    // ========== ADD TOPIC ==========
$('#addTopicForm').on('submit', function(e) {
    e.preventDefault();
    
    var classId = $('#topicClassId').val();
    var subjectId = $('#topicSubjectId').val();
    var branchId = $('#topicBranchId').val();
    var topicName = $('input[name="topic_name"]').val();
    
    if (!classId || !subjectId) {
        alert('Please select Class and Subject first');
        return;
    }
    
    if (!topicName) {
        alert('Please enter topic name');
        return;
    }
    
    // Get CSRF token from meta tag or form
    var csrfToken = $('meta[name="csrf_token"]').attr('content');
    if (!csrfToken) {
        csrfToken = $('input[name="csrf_test_name"]').val();
    }
    
    var formData = {
        topic_name: topicName,
        topic_code: $('input[name="topic_code"]').val(),
        topic_order: $('input[name="topic_order"]').val(),
        expected_weeks: $('input[name="expected_weeks"]').val(),
        is_competency_based: $('input[name="is_competency_based"]').is(':checked') ? 1 : 0,
        class_id: classId,
        subject_id: subjectId,
        branch_id: branchId,
        csrf_test_name: csrfToken
    };
    
    $.ajax({
        url: base_url + 'stars/add_topic_ajax',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            $('#addTopicForm button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
        },
        success: function(response) {
            $('#addTopicForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Topic');
            
            if (response.success) {
                alert('Topic added successfully');
                $('#addTopicForm')[0].reset();
                $('input[name="topic_name"]').focus();
                // Reload topics
                $('#loadTopicsBtn').click();
            } else {
                alert('Failed: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            $('#addTopicForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Topic');
            
            if (xhr.status === 403) {
                alert('Security token expired. Please refresh the page and try again.');
                location.reload();
            } else {
                alert('Server error: ' + xhr.status);
            }
        }
    });
});
    // Helper function
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    // Trigger initial load if branch is preselected
    // Trigger initial load if branch is preselected (for superadmin)
if (is_superadmin && $('#topicBranchId').val()) {
    $('#topicBranchId').trigger('change');
}

// ========== FOR ADMIN: Load classes on page load ==========
// FOR ADMIN: Load classes on page load
if (!is_superadmin) {
    var branchId = $('#topicBranchId').val();
    console.log('Admin: Checking branch ID:', branchId);
    
    if (branchId && branchId != '') {
        console.log('Admin: Loading classes for branch:', branchId);
        $('#topicClassId').html('<option value="">Loading classes...</option>');
        $.ajax({
            url: base_url + 'ajax/getClassByBranch',
            type: 'POST',
            data: { branch_id: branchId },
            success: function(data) {
                console.log('Admin: Classes loaded successfully');
                if (data && data.trim() !== '') {
                    $('#topicClassId').html('<option value="">Select Class</option>' + data);
                } else {
                    $('#topicClassId').html('<option value="">No classes found</option>');
                }
                $('#topicClassId').val('');
            },
            error: function(xhr, status, error) {
                console.error('Admin: Error loading classes:', error);
                console.error('Response:', xhr.responseText);
                $('#topicClassId').html('<option value="">Error loading classes</option>');
            }
        });
    } else {
        console.warn('Admin: No branch ID found');
    }
}
// ========== END ADMIN AUTO-LOAD ==========
});
</script>