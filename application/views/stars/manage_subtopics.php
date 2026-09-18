<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-layer-group"></i> Manage Subtopics (Optional Feature)
                </h4>
                <div class="panel-btn">
                    <a href="<?=base_url('stars/manage_topics')?>" class="btn btn-circle btn-default">
                        <i class="fas fa-book"></i> Back to Topics
                    </a>
                </div>
            </header>
            <div class="panel-body">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Optional Feature:</strong> Subtopics allow granular tracking of progress within a topic.
                    If you don't use subtopics, the system will continue working with topics only.
                </div>
                
               <!-- Select Filters: Branch -> Class -> Subject -> Topic -->
<div class="row">
    <?php if (is_superadmin_loggedin()): ?>
    <!-- Superadmin: Show Branch dropdown -->
    <div class="col-md-3">
        <div class="form-group">
            <label>Branch</label>
            <select id="subtopicBranchId" class="form-control">
                <option value="">Select Branch</option>
                <?php 
                $branches = $this->db->order_by('name', 'ASC')->get('branch')->result_array();
                foreach ($branches as $branch): 
                ?>
                <option value="<?=$branch['id']?>"><?=html_escape($branch['school_name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <!-- Class column -->
    <div class="col-md-3">
        <div class="form-group">
            <label>Class</label>
            <select id="subtopicClassId" class="form-control">
                <option value="">Select Class</option>
            </select>
        </div>
    </div>
    <!-- Subject column -->
    <div class="col-md-3">
        <div class="form-group">
            <label>Subject</label>
            <select id="subtopicSubjectId" class="form-control">
                <option value="">Select Subject</option>
            </select>
        </div>
    </div>
    <!-- Topic column -->
    <div class="col-md-3">
        <div class="form-group">
            <label>Topic</label>
            <select id="subtopicTopicId" class="form-control">
                <option value="">Select Topic</option>
            </select>
        </div>
    </div>
    <?php else: ?>
    <!-- Admin: Branch field hidden, 3 columns: Class, Subject, Topic -->
    <input type="hidden" id="subtopicBranchId" value="<?=$branch_id?>">
    <div class="col-md-4">
        <div class="form-group">
            <label>Class</label>
            <select id="subtopicClassId" class="form-control">
                <option value="">Select Class</option>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Subject</label>
            <select id="subtopicSubjectId" class="form-control">
                <option value="">Select Subject</option>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Topic</label>
            <select id="subtopicTopicId" class="form-control">
                <option value="">Select Topic</option>
            </select>
        </div>
    </div>
    <?php endif; ?>
</div>
                
                <div class="headers-line mt-md">
                    <i class="fas fa-plus-circle"></i> Add New Subtopic
                </div>
                
                <form id="addSubtopicForm" class="row">
                    <div class="col-md-4">
                        <input type="text" name="subtopic_name" class="form-control" placeholder="Subtopic Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="subtopic_code" class="form-control" placeholder="Code (optional)">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="subtopic_order" class="form-control" placeholder="Order" value="0">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="expected_hours" class="form-control" placeholder="Hours" value="1.0" step="0.5">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-plus"></i> Add Subtopic
                        </button>
                    </div>
                </form>
                
                <div class="table-responsive mt-lg">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="10%">Order</th>
                                <th width="40%">Subtopic Name</th>
                                <th width="15%">Code</th>
                                <th width="15%">Hours</th>
                                <th width="20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="subtopicsTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Select a topic to view subtopics</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
var base_url = '<?=base_url()?>';
var csrf_token = '<?=$this->security->get_csrf_hash()?>';

$(document).ready(function() {
    
    // ========== LOAD CLASSES WHEN BRANCH CHANGES ==========
    $('#subtopicBranchId').on('change', function() {
        var branchId = $(this).val();
        if (branchId) {
            $('#subtopicClassId').html('<option value="">Loading classes...</option>');
            $.ajax({
                url: base_url + 'ajax/getClassByBranch',
                type: 'POST',
                data: { branch_id: branchId },
                success: function(data) {
                    $('#subtopicClassId').html('<option value="">Select Class</option>' + data);
                    $('#subtopicSubjectId').html('<option value="">Select Subject</option>');
                    $('#subtopicTopicId').html('<option value="">Select Topic</option>');
                    $('#subtopicsTableBody').html('<td><td colspan="5" class="text-center text-muted">Select a topic to view subtopics</td</tr>');
                },
                error: function() {
                    $('#subtopicClassId').html('<option value="">Error loading classes</option>');
                }
            });
        }
    });
    
 // ========== LOAD SUBJECTS WHEN CLASS CHANGES ==========
    $('#subtopicClassId').on('change', function() {
        var classId = $(this).val();
        var branchId = $('#subtopicBranchId').val();
        
        if (!classId || !branchId) {
            $('#subtopicSubjectId').html('<option value="">Select Subject</option>');
            $('#subtopicTopicId').html('<option value="">Select Topic</option>');
            return;
        }
        
        $('#subtopicSubjectId').html('<option value="">Loading subjects...</option>');
        
        $.ajax({
            url: base_url + 'stars/get_subjects_by_class_ajax',
            type: 'POST',
            data: { 
                class_id: classId, 
                branch_id: branchId,
                csrf_test_name: csrf_token 
            },
            dataType: 'json',
            success: function(response) {
                console.log('Subjects response:', response);
                
                // Check if response has error property
                if (response && response.error) {
                    $('#subtopicSubjectId').html('<option value="">' + response.error + '</option>');
                    return;
                }
                
                var html = '<option value="">Select Subject</option>';
                
                // Check if response is an array and has length
                if (response && Array.isArray(response) && response.length > 0) {
                    for (var i = 0; i < response.length; i++) {
                        html += '<option value="' + response[i].id + '">' + escapeHtml(response[i].name) + '</option>';
                    }
                } else {
                    html = '<option value="">No subjects assigned to this class</option>';
                }
                
                $('#subtopicSubjectId').html(html);
                $('#subtopicTopicId').html('<option value="">Select Topic</option>');
                $('#subtopicsTableBody').html('<tr><td colspan="5" class="text-center text-muted">Select a topic to view subtopics</td</tr>');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                $('#subtopicSubjectId').html('<option value="">Error loading subjects</option>');
            }
        });
    });
    // ========== LOAD TOPICS WHEN SUBJECT CHANGES ==========
    $('#subtopicSubjectId').on('change', function() {
        var classId = $('#subtopicClassId').val();
        var subjectId = $(this).val();
        var branchId = $('#subtopicBranchId').val();
        
        if (!classId || !subjectId || !branchId) {
            $('#subtopicTopicId').html('<option value="">Select Topic</option>');
            $('#subtopicsTableBody').html('<tr><td colspan="5" class="text-center text-muted">Select a topic to view subtopics</td</tr>');
            return;
        }
        
        $('#subtopicTopicId').html('<option value="">Loading topics...</option>');
        
        $.ajax({
            url: base_url + 'stars/get_topics_by_class_subject_ajax',
            type: 'POST',
            data: { 
                class_id: classId, 
                subject_id: subjectId,
                branch_id: branchId,
                csrf_test_name: csrf_token 
            },
            dataType: 'json',
            success: function(topics) {
                console.log('Topics loaded:', topics);
                
                if (topics.error) {
                    $('#subtopicTopicId').html('<option value="">' + topics.error + '</option>');
                    return;
                }
                
                var html = '<option value="">Select Topic</option>';
                if (topics && topics.length > 0) {
                    for (var i = 0; i < topics.length; i++) {
                        html += '<option value="' + topics[i].id + '">' + escapeHtml(topics[i].topic_name) + '</option>';
                    }
                } else {
                    html = '<option value="">No topics found for this subject</option>';
                }
                $('#subtopicTopicId').html(html);
                $('#subtopicsTableBody').html('<tr><td colspan="5" class="text-center text-muted">Select a topic to view subtopics</td</tr>');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#subtopicTopicId').html('<option value="">Error loading topics</option>');
            }
        });
    });
    
    // ========== LOAD SUBTOPICS WHEN TOPIC CHANGES ==========
    $('#subtopicTopicId').on('change', function() {
        var topicId = $(this).val();
        var branchId = $('#subtopicBranchId').val();
        
        if (!topicId) {
            $('#subtopicsTableBody').html('<tr><td colspan="5" class="text-center text-muted">Select a topic to view subtopics</td</tr>');
            return;
        }
        
        $('#subtopicsTableBody').html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading subtopics...</td</tr>');
        
        $.ajax({
            url: base_url + 'stars/get_subtopics_ajax',
            type: 'POST',
            data: { 
                topic_id: topicId, 
                branch_id: branchId, 
                csrf_test_name: csrf_token 
            },
            dataType: 'json',
            success: function(subtopics) {
                console.log('Subtopics loaded:', subtopics);
                
                if (!subtopics || subtopics.length === 0) {
                    $('#subtopicsTableBody').html('<tr><td colspan="5" class="text-center text-muted">No subtopics. Add some above!</td</tr>');
                    return;
                }
                
                var html = '';
                for (var i = 0; i < subtopics.length; i++) {
                    var s = subtopics[i];
                    var subtopicOrder = (s.subtopic_order !== null && s.subtopic_order !== undefined) ? s.subtopic_order : 0;
                    var expectedHours = (s.expected_hours !== null && s.expected_hours !== undefined) ? s.expected_hours : 1.0;
                    var subtopicCode = (s.subtopic_code && s.subtopic_code !== '') ? escapeHtml(s.subtopic_code) : '-';
                    
                    html += '<tr id="subtopicRow_' + s.id + '">';
                    html += '<td class="text-center">' + subtopicOrder + '</td>';
                    html += '<td>' + escapeHtml(s.subtopic_name) + '</td>';
                    html += '<td>' + subtopicCode + '</td>';
                    html += '<td class="text-center">' + expectedHours + '</td>';
                    html += '<td class="text-center">';
                    html += '<button class="btn btn-xs btn-danger delete-subtopic" data-id="' + s.id + '"><i class="fas fa-trash"></i> Delete</button>';
                    html += '</td>';
                    html += '</tr>';
                }
                $('#subtopicsTableBody').html(html);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#subtopicsTableBody').html('</td><td colspan="5" class="text-center text-danger">Error loading subtopics</td</td>');
            }
        });
    });
    
    // ========== ADD SUBTOPIC ==========
    $('#addSubtopicForm').on('submit', function(e) {
        e.preventDefault();
        
        var topicId = $('#subtopicTopicId').val();
        var branchId = $('#subtopicBranchId').val();
        
        if (!topicId) {
            alert('Please select a topic first');
            return;
        }
        
        var subtopicName = $('input[name="subtopic_name"]').val();
        if (!subtopicName) {
            alert('Please enter subtopic name');
            return;
        }
        
        var formData = {
            topic_id: topicId,
            subtopic_name: subtopicName,
            subtopic_code: $('input[name="subtopic_code"]').val(),
            subtopic_order: $('input[name="subtopic_order"]').val() || 0,
            expected_hours: $('input[name="expected_hours"]').val() || 1.0,
            branch_id: branchId,
            csrf_test_name: csrf_token
        };
        
        $.ajax({
            url: base_url + 'stars/add_subtopic_ajax',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Subtopic added successfully');
                    $('#addSubtopicForm')[0].reset();
                    $('#subtopicTopicId').trigger('change');
                } else {
                    alert('Failed to add subtopic: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr.responseText);
                alert('Server error. Please try again.');
            }
        });
    });
    
    // ========== DELETE SUBTOPIC ==========
    $(document).on('click', '.delete-subtopic', function() {
        if (!confirm('Delete this subtopic? This action cannot be undone.')) return;
        
        var subtopicId = $(this).data('id');
        
        $.ajax({
            url: base_url + 'stars/delete_subtopic_ajax',
            type: 'POST',
            data: { 
                subtopic_id: subtopicId, 
                csrf_test_name: csrf_token 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Subtopic deleted');
                    $('#subtopicTopicId').trigger('change');
                } else {
                    alert(response.message || 'Delete failed');
                }
            },
            error: function() {
                alert('Server error. Please try again.');
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
    if ($('#subtopicBranchId').val()) {
        $('#subtopicBranchId').trigger('change');
    }
});
</script>