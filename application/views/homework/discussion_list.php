<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <?php echo form_open($this->uri->uri_string(), array('class' => 'validate'));?>
            <header class="panel-heading">
                <h4 class="panel-title"><?=translate('select_ground')?></h4>
            </header>
            <div class="panel-body">
                <div class="row mb-sm">
                    <?php if (is_superadmin_loggedin()): ?>
                    <div class="col-md-3 mb-sm">
                        <div class="form-group">
                            <label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
                            <?php
                                $arrayBranch = $this->app_lib->getSelectList('branch');
                                echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' id='branch_id'
                                data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3 mb-sm">
                        <div class="form-group">
                            <label class="control-label"><?=translate('class')?> <span class="required">*</span></label>
                            <?php
                                $arrayClass = $this->app_lib->getClass($branch_id);
                                echo form_dropdown("class_id", $arrayClass, set_value('class_id'), "class='form-control' id='class_id' onchange='getSectionByClass(this.value,0)'
                                data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
                            ?>
                        </div>
                    </div>
                    <div class="col-md-3 mb-sm">
                        <div class="form-group">
                            <label class="control-label"><?=translate('section')?> <span class="required">*</span></label>
                            <?php
                                $arraySection = $this->app_lib->getSections(set_value('class_id'), false);
                                echo form_dropdown("section_id", $arraySection, set_value('section_id'), "class='form-control' id='section_id'
                                data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
                            ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label"><?=translate('subject')?> <span class="required">*</span></label>
                            <?php
                                if(!empty(set_value('class_id'))) {
                                    $arraySubject = array("" => translate('select'));
                                    $query = $this->subject_model->getSubjectByClassSection(set_value('class_id'), set_value('section_id'));
                                    $subjects = $query->result_array();
                                    foreach ($subjects as $row){
                                        $subjectID = $row['subject_id'];
                                        $arraySubject[$subjectID] = $row['subjectname'];
                                    }
                                } else {
                                    $arraySubject = array("" => translate('select_class_first'));
                                }
                                echo form_dropdown("subject_id", $arraySubject, set_value('subject_id'), "class='form-control' id='subject_id'
                                data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-offset-10 col-md-2">
                        <button type="submit" name="search" value="1" class="btn btn btn-default btn-block"> <i class="fas fa-filter"></i> <?=translate('filter')?></button>
                    </div>
                </div>
            </footer>
            <?php echo form_close();?>
        </section>
        
        <?php if (isset($homeworklist) && !empty($homeworklist)): ?>
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-comments"></i> <?=translate('homework_discussion')?></h4>
            </header>
            <div class="panel-body">
                <table class="table table-bordered table-striped table-export">
                    <thead>
                        <tr>
                            <th><?=translate('subject')?></th>
                            <th><?=translate('class')?></th>
                            <th><?=translate('section')?></th>
                            <th><?=translate('due_date')?></th>
                            <th><?=translate('comments')?></th>
                            <th><?=translate('action')?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($homeworklist as $row): ?>
                        <tr>
                            <td><?=$row['subject_name']?></td>
                            <td><?=$row['class_name']?></td>
                            <td><?=$row['section_name']?></td>
                            <td><?=_d($row['date_of_submission'])?></td>
                            <td class="text-center">
                                <span class="badge badge-info"><?=$row['comment_count']?></span>
                            </td>
                            <td class="action">
                                <a href="<?=base_url('homework/comments/' . $row['id'])?>" class="btn btn-circle btn-default icon">
                                    <i class="fas fa-comments"></i> <?=translate('view_discussion')?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php elseif (isset($homeworklist)): ?>
        <div class="alert alert-info"><?=translate('no_homework_found')?></div>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    $('#branch_id').on('change', function() {
        var branchID = $(this).val();
        getClassByBranch(branchID);
        $('#subject_id').html('').append('<option value=""><?=translate("select")?></option>');
    });

    $('#section_id').on('change', function() {
        var classID = $('#class_id').val();
        var sectionID =$(this).val();
        $.ajax({
            url: base_url + 'subject/getByClassSection',
            type: 'POST',
            data: {
                classID: classID,
                sectionID: sectionID
            },
            success: function (data) {
                $('#subject_id').html(data);
            }
        });
    });
});

function getClassByBranch(branchID) {
    if (branchID != "") {
        $.ajax({
            url: base_url + 'ajax/getClassByBranch',
            type: 'POST',
            data: {'branch_id': branchID},
            success: function (data) {
                $('#class_id').html(data);
                getSectionByClass($('#class_id').val(), 0);
            }
        });
    } else {
        $('#class_id').html('<option value=""><?=translate("select")?></option>');
        $('#section_id').html('<option value=""><?=translate("select")?></option>');
    }
}

function getSectionByClass(classID, sectionID) {
    if (classID != "") {
        $.ajax({
            url: base_url + 'ajax/getSectionByClass',
            type: 'POST',
            data: {'class_id': classID},
            success: function (data) {
                $('#section_id').html(data);
                if (sectionID != 0) {
                    $('#section_id').val(sectionID);
                }
                $('#section_id').trigger('change');
            }
        });
    } else {
        $('#section_id').html('<option value=""><?=translate("select")?></option>');
    }
}
</script>