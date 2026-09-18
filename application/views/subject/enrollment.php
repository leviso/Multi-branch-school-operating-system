<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-user-graduate"></i> <?=translate('student_subject_enrollment')?></h4>
            </header>
            <div class="panel-body">
                <!-- Filter Section -->
                <div class="row mb-lg">
                    <?php if ($is_superadmin): ?>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('branch')?></label>
                            <select name="branch_id" id="branch_id" class="form-control">
                                <option value=""><?=translate('select')?></option>
                                <?php
                                $branches = $this->db->get('branch')->result();
                                foreach($branches as $b){
                                    echo '<option value="'.$b->id.'">'.$b->name.'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('class')?></label>
                            <select name="class_id" id="class_id" class="form-control">
                                <option value=""><?=translate('select')?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('section')?></label>
                            <select name="section_id" id="section_id" class="form-control" disabled>
                                <option value=""><?=translate('select_class_first')?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('subject')?></label>
                            <select name="subject_id" id="subject_id" class="form-control" disabled>
                                <option value=""><?=translate('select_section_first')?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Enrollment Section -->
                <div id="enrollment_section" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title"><i class="fas fa-user-check text-success"></i> <?=translate('enrolled_students')?></h4>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr><th><?=translate('student_name')?></th><th><?=translate('register_no')?></th><th width="80"><?=translate('action')?></th></tr>
                                            </thead>
                                            <tbody id="enrolled_list"><tr><td colspan="3" class="text-center"><?=translate('select_subject')?></td></tr></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title"><i class="fas fa-user-plus text-primary"></i> <?=translate('available_students')?></h4>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr><th><?=translate('student_name')?></th><th><?=translate('register_no')?></th><th width="80"><?=translate('action')?></th></tr>
                                            </thead>
                                            <tbody id="available_list"><tr><td colspan="3" class="text-center"><?=translate('select_subject')?></td></tr></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
var branch_id = '<?=$branch_id?>';
var is_superadmin = <?=$is_superadmin ? 'true' : 'false'?>;

// Safe notification function
function showMessage(message, type) {
    if (typeof toastr !== 'undefined') {
        if (type === 'success') toastr.success(message);
        else if (type === 'error') toastr.error(message);
        else if (type === 'warning') toastr.warning(message);
        else toastr.info(message);
    } else {
        alert(message);
    }
}

$(document).ready(function() {
    <?php if($is_superadmin): ?>
    $('#branch_id').on('change', function() {
        branch_id = $(this).val();
        loadClasses();
        $('#section_id').html('<option value=""><?=translate('select_class_first')?></option>').prop('disabled', true);
        $('#subject_id').html('<option value=""><?=translate('select_section_first')?></option>').prop('disabled', true);
        $('#enrollment_section').hide();
    });
    <?php endif; ?>
    
    loadClasses();
    
    $('#class_id').on('change', function() {
        var class_id = $(this).val();
        if(class_id) {
            $.ajax({
                url: base_url + 'ajax/getSectionByClass',
                type: 'POST',
                data: {class_id: class_id},
                success: function(data) {
                    $('#section_id').html(data).prop('disabled', false);
                    $('#subject_id').html('<option value=""><?=translate('select_section_first')?></option>').prop('disabled', true);
                    $('#enrollment_section').hide();
                }
            });
        }
    });
    
    $('#section_id').on('change', function() {
        var class_id = $('#class_id').val();
        var section_id = $(this).val();
        if(class_id && section_id) {
            $.ajax({
                url: base_url + 'subject/getByClassSection',
                type: 'POST',
                data: {classID: class_id, sectionID: section_id},
                success: function(data) {
                    $('#subject_id').html('<option value=""><?=translate('select')?></option>' + data).prop('disabled', false);
                    $('#enrollment_section').hide();
                }
            });
        }
    });
    
    $('#subject_id').on('change', function() {
        var subject_id = $(this).val();
        if(subject_id) {
            loadEnrolledStudents();
            loadAvailableStudents();
            $('#enrollment_section').show();
        } else {
            $('#enrollment_section').hide();
        }
    });
});

function loadClasses() {
    $.ajax({
        url: base_url + 'ajax/getClassByBranch',
        type: 'POST',
        data: {branch_id: branch_id},
        success: function(data) {
            $('#class_id').html(data);
        }
    });
}

function loadEnrolledStudents() {
    var subject_id = $('#subject_id').val();
    var class_id = $('#class_id').val();
    var section_id = $('#section_id').val();
    
    $.ajax({
        url: base_url + 'subject/get_enrolled_students',
        type: 'POST',
        data: {subject_id: subject_id, class_id: class_id, section_id: section_id, branch_id: branch_id},
        dataType: 'json',
        success: function(response) {
            var html = '';
            if(response.students.length === 0) {
                html = '<tr><td colspan="3" class="text-center"><?=translate("no_students")?></td></tr>';
            } else {
                $.each(response.students, function(i, s) {
                    html += '<tr>' +
                        '<td>' + s.first_name + ' ' + s.last_name + '</td>' +
                        '<td>' + s.register_no + '</td>' +
                        '<td><button class="btn btn-xs btn-danger remove-student" data-id="' + s.id + '"><i class="fas fa-trash"></i></button></td>' +
                        '</tr>';
                });
            }
            $('#enrolled_list').html(html);
            attachRemoveEvents();
        },
        error: function(xhr) {
            console.error('Error loading enrolled students:', xhr);
            showMessage('Error loading enrolled students', 'error');
        }
    });
}

function loadAvailableStudents() {
    var subject_id = $('#subject_id').val();
    var class_id = $('#class_id').val();
    var section_id = $('#section_id').val();
    
    $.ajax({
        url: base_url + 'subject/get_unenrolled_students',
        type: 'POST',
        data: {subject_id: subject_id, class_id: class_id, section_id: section_id, branch_id: branch_id},
        dataType: 'json',
        success: function(response) {
            var html = '';
            if(response.students.length === 0) {
                html = '<tr><td colspan="3" class="text-center"><?=translate("all_students_enrolled")?></td></tr>';
            } else {
                $.each(response.students, function(i, s) {
                    html += '<tr>' +
                        '<td>' + s.first_name + ' ' + s.last_name + '</td>' +
                        '<td>' + s.register_no + '</td>' +
                        '<td><button class="btn btn-xs btn-success enroll-student" data-id="' + s.id + '"><i class="fas fa-plus"></i></button></td>' +
                        '</tr>';
                });
            }
            $('#available_list').html(html);
            attachEnrollEvents();
        },
        error: function(xhr) {
            console.error('Error loading available students:', xhr);
            showMessage('Error loading available students', 'error');
        }
    });
}

function attachEnrollEvents() {
    $('.enroll-student').off('click').on('click', function() {
        var student_id = $(this).data('id');
        var subject_id = $('#subject_id').val();
        var class_id = $('#class_id').val();
        var section_id = $('#section_id').val();
        
        $.ajax({
            url: base_url + 'subject/enroll_student',
            type: 'POST',
            data: {student_id: student_id, subject_id: subject_id, class_id: class_id, section_id: section_id, branch_id: branch_id},
            dataType: 'json',
            success: function(response) {
                if(response.status == 'success') {
                    showMessage(response.message, 'success');
                    loadEnrolledStudents();
                    loadAvailableStudents();
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function(xhr) {
                console.error('Enrollment error:', xhr);
                showMessage('Enrollment failed. Please try again.', 'error');
            }
        });
    });
}

function attachRemoveEvents() {
    $('.remove-student').off('click').on('click', function() {
        var student_id = $(this).data('id');
        var subject_id = $('#subject_id').val();
        
        if(confirm('<?=translate("confirm_remove_student")?>')) {
            $.ajax({
                url: base_url + 'subject/remove_student',
                type: 'POST',
                data: {student_id: student_id, subject_id: subject_id},
                dataType: 'json',
                success: function(response) {
                    if(response.status == 'success') {
                        showMessage(response.message, 'success');
                        loadEnrolledStudents();
                        loadAvailableStudents();
                    } else {
                        showMessage(response.message, 'error');
                    }
                },
                error: function(xhr) {
                    console.error('Remove error:', xhr);
                    showMessage('Remove failed. Please try again.', 'error');
                }
            });
        }
    });
}
// Initialize toastr notifications
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": false,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};
</script>