$(document).ready(function() {
    // Load topics when class/subject changes
    $('#topicClassId, #topicSubjectId, #topicBranchId').on('change', function() {
        loadTopics();
    });
    
    // Add topic form submit
    $('#addTopicForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: base_url + 'stars/add_topic_ajax',
            type: 'POST',
            data: $(this).serialize() + '&csrf_test_name=' + csrf_token,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Topic added successfully', 'success');
                    $('#addTopicForm')[0].reset();
                    loadTopics();
                } else {
                    showNotification(response.message || 'Failed to add topic', 'error');
                }
            }
        });
    });
    
    // Edit topic
    $(document).on('click', '.edit-topic', function() {
        var topicId = $(this).data('id');
        var row = $(this).closest('tr');
        
        var currentName = row.find('.topic-name').text();
        var currentOrder = row.find('.topic-order').text();
        var currentWeeks = row.find('.topic-weeks').text();
        var currentCbc = row.find('.topic-cbc').data('cbc');
        
        var newName = prompt('Enter topic name:', currentName);
        if (newName && newName !== currentName) {
            $.ajax({
                url: base_url + 'stars/update_topic_ajax',
                type: 'POST',
                data: {
                    topic_id: topicId,
                    topic_name: newName,
                    topic_order: currentOrder,
                    expected_weeks: currentWeeks,
                    is_competency_based: currentCbc,
                    csrf_test_name: csrf_token
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        row.find('.topic-name').text(newName);
                        showNotification('Topic updated', 'success');
                    }
                }
            });
        }
    });
    
    // Delete topic
    $(document).on('click', '.delete-topic', function() {
        if (!confirm('Delete this topic? This action cannot be undone.')) return;
        
        var topicId = $(this).data('id');
        var row = $(this).closest('tr');
        
        $.ajax({
            url: base_url + 'stars/delete_topic_ajax',
            type: 'POST',
            data: {
                topic_id: topicId,
                csrf_test_name: csrf_token
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.remove();
                    showNotification('Topic deleted', 'success');
                } else {
                    showNotification(response.message || 'Failed to delete topic', 'error');
                }
            }
        });
    });
});

function loadTopics() {
    var classId = $('#topicClassId').val();
    var subjectId = $('#topicSubjectId').val();
    var branchId = $('#topicBranchId').val();
    
    if (!classId || !subjectId) {
        $('#topicsTableBody').html('<tr><td colspan="6" class="text-center">Select class and subject</td></tr>');
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
            if (topics.error) {
                $('#topicsTableBody').html('<tr><td colspan="6" class="text-center text-danger">' + topics.error + '</td></tr>');
                return;
            }
            
            if (topics.length === 0) {
                $('#topicsTableBody').html('<tr><td colspan="6" class="text-center">No topics found. Add some!</td></tr>');
                return;
            }
            
            var html = '';
            for (var i = 0; i < topics.length; i++) {
                var t = topics[i];
                html += '<tr>';
                html += '<td class="topic-order">' + t.topic_order + '</td>';
                html += '<td class="topic-name">' + escapeHtml(t.topic_name) + '</td>';
                html += '<td>' + (t.topic_code || '-') + '</td>';
                html += '<td class="topic-weeks">' + t.expected_weeks + '</td>';
                html += '<td class="topic-cbc" data-cbc="' + t.is_competency_based + '">' + (t.is_competency_based ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-xs btn-info edit-topic" data-id="' + t.id + '"><i class="fas fa-edit"></i></button> ';
                html += '<button class="btn btn-xs btn-danger delete-topic" data-id="' + t.id + '"><i class="fas fa-trash"></i></button>';
                html += '</td>';
                html += '</tr>';
            }
            $('#topicsTableBody').html(html);
        },
        error: function() {
            $('#topicsTableBody').html('<tr><td colspan="6" class="text-center text-danger">Error loading topics</td></tr>');
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