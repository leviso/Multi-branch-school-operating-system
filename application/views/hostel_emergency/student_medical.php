<div class="row">
    <div class="col-md-4">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-7">
                        <h4 class="panel-title"><i class="fa fa-users"></i> <?=translate('students_in_hostel')?></h4>
                    </div>
                    <div class="col-md-5">
                        <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                        <select id="branch_filter" class="form-control input-sm">
                            <option value=""><?=translate('all_branches')?></option>
                            <?php foreach($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" 
                                    <?php echo (isset($selected_branch_id) && $selected_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo html_escape($branch['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row mt-sm">
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="text" id="student_search" class="form-control" placeholder="<?=translate('search_student_by_name_or_reg_no')?>">
                        </div>
                    </div>
                </div>
            </header>
            <div class="panel-body" style="max-height: 500px; overflow-y: auto; padding: 0;">
                <div class="list-group" id="student_list">
                    <?php if(!empty($students)): ?>
                        <?php foreach($students as $student): ?>
                        <?php 
                        $is_active = (isset($student_id) && $student_id == $student['id']);
                        ?>
                        <a href="javascript:void(0);" 
                           class="list-group-item student-item <?php echo $is_active ? 'active' : ''; ?>"
                           data-student-id="<?php echo $student['id']; ?>"
                           data-student-name="<?php echo html_escape($student['first_name'] . ' ' . $student['last_name']); ?>"
                           data-student-regno="<?php echo html_escape($student['register_no']); ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <strong><?php echo html_escape($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo html_escape($student['register_no']); ?></small>
                                </div>
                                <div class="col-md-4 text-right">
                                    <?php if(!empty($student['room_name'])): ?>
                                    <span class="label label-info"><?php echo html_escape($student['room_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted" style="padding: 30px;">
                            <i class="fa fa-info-circle fa-2x"></i>
                            <p><?=translate('no_students_in_hostel')?></p>
                            <small><?=translate('make_sure_students_are_assigned_to_hostel_rooms')?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
    
    <div class="col-md-8" id="medical-panel">
        <div class="text-center" style="padding: 50px;">
            <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
            <p class="mt-md"><?=translate('loading')?>...</p>
        </div>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var csrf_token = '<?=$this->security->get_csrf_hash();?>';

function loadMedicalForm(studentId) {
    $('#medical-panel').html('<div class="text-center" style="padding: 50px;"><i class="fa fa-spinner fa-spin fa-3x text-muted"></i><p class="mt-md"><?=translate('loading')?>...</p></div>');
    
    $.ajax({
        url: base_url + 'hostel_emergency/get_medical_form',
        type: 'POST',
        data: {
            student_id: studentId,
            branch_id: '<?php echo $branch_id ?? ''; ?>',
            '<?=$this->security->get_csrf_token_name();?>': csrf_token
        },
        dataType: 'html',
        success: function(html) {
            $('#medical-panel').html(html);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#medical-panel').html('<div class="alert alert-danger"><?=translate('error_loading_data')?><br>' + error + '</div>');
        }
    });
}

function setActiveStudent(studentId) {
    $('.student-item').removeClass('active');
    $('.student-item[data-student-id="' + studentId + '"]').addClass('active');
}

$(document).ready(function() {
    // On initial load, if a student is already selected (from URL), load its form
    <?php if(isset($student_id) && $student_id): ?>
    loadMedicalForm('<?php echo $student_id; ?>');
    setActiveStudent('<?php echo $student_id; ?>');
    <?php endif; ?>
    
    // When clicking on a student, load via AJAX
    $('.student-item').click(function(e) {
        e.preventDefault();
        var studentId = $(this).data('student-id');
        var studentName = $(this).data('student-name');
        var branchId = '<?php echo $branch_id ?? ''; ?>';
        
        // Update URL without page reload
        var newUrl = base_url + 'hostel_emergency/student_medical/' + studentId + '?branch_id=' + branchId;
        window.history.pushState({student_id: studentId, branch_id: branchId}, studentName, newUrl);
        
        // Load the medical form
        loadMedicalForm(studentId);
        
        // Update active class
        setActiveStudent(studentId);
    });
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.student_id) {
            loadMedicalForm(event.state.student_id);
            setActiveStudent(event.state.student_id);
        } else {
            // Reload page to get default state
            location.reload();
        }
    });
    
    // Branch filter change
    $('#branch_filter').change(function() {
        var branch_id = $(this).val();
        if(branch_id) {
            window.location.href = base_url + 'hostel_emergency/student_medical?branch_id=' + branch_id;
        } else {
            window.location.href = base_url + 'hostel_emergency/student_medical';
        }
    });
    
    // Search functionality
    $('#student_search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.student-item').each(function() {
            var studentName = $(this).data('student-name').toLowerCase();
            var studentRegNo = $(this).data('student-regno').toLowerCase();
            if (studentName.indexOf(searchTerm) !== -1 || studentRegNo.indexOf(searchTerm) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>