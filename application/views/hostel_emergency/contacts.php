<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fa fa-phone-alt"></i> <?=translate('emergency_contacts')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php if(get_permission('emergency_contacts', 'can_add')): ?>
                        <button class="btn btn-primary" onclick="showContactModal()">
                            <i class="fas fa-plus-circle"></i> <?=translate('add_emergency_contact')?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?=translate('branch')?></label>
                            <select id="branch_filter" class="form-control">
                                <option value=""><?=translate('all_branches')?></option>
                                <?php foreach($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" 
                                        <?php echo (isset($selected_branch_id) && $selected_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                        <?php echo html_escape($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('contact_name')?></th>
                                <th><?=translate('role')?></th>
                                <th><?=translate('phone_number')?></th>
                                <th><?=translate('alternate_phone')?></th>
                                <th><?=translate('priority')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach($contacts as $contact): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <?php if (is_superadmin_loggedin() && isset($branches)): ?>
                                <td><?php echo html_escape($contact['branch_name'] ?? get_type_name_by_id('branch', $contact['branch_id'])); ?></td>
                                <?php endif; ?>
                                <td><?php echo html_escape($contact['contact_name']); ?></td>
                                <td><?php echo html_escape($contact['contact_role']); ?></td>
                                <td><?php echo html_escape($contact['phone_number']); ?></td>
                                <td><?php echo html_escape($contact['alternate_phone']); ?></td>
                                <td><?php echo $contact['priority_order']; ?></td>
                                <td>
                                    <?php if($contact['is_active']): ?>
                                    <span class="label label-success"><?=translate('active')?></span>
                                    <?php else: ?>
                                    <span class="label label-danger"><?=translate('inactive')?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(get_permission('emergency_contacts', 'can_edit')): ?>
                                    <button class="btn btn-default btn-circle icon" onclick="editContact(<?=$contact['id']?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if(get_permission('emergency_contacts', 'can_delete')): ?>
                                    <button class="btn btn-danger btn-circle icon" onclick="deleteContact(<?=$contact['id']?>, '<?=htmlspecialchars($contact['contact_name'])?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($contacts)): ?>
                            <tr>
                                <td colspan="<?php echo (is_superadmin_loggedin() && isset($branches)) ? '9' : '8'; ?>" class="text-center"><?=translate('no_records_found')?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?=translate('add_emergency_contact')?></h4>
            </div>
            <?php echo form_open('hostel_emergency/contact_save', array('class' => 'frm-submit', 'id' => 'contactForm')); ?>
            <input type="hidden" name="contact_id" id="contact_id">
                <div class="modal-body">
                    <?php if(is_superadmin_loggedin() && isset($branches)): ?>
                    <div class="form-group">
                        <label><?=translate('branch')?> <span class="required">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            <option value=""><?=translate('select_branch')?></option>
                            <?php foreach($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>">
                                    <?php echo html_escape($branch['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('contact_name')?> <span class="required">*</span></label>
                                <input type="text" name="contact_name" id="contact_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('role')?></label>
                                <input type="text" name="contact_role" id="contact_role" class="form-control" placeholder="<?=translate('e_g_police_ambulance_fire')?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('phone_number')?> <span class="required">*</span></label>
                                <input type="text" name="phone_number" id="contact_phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('alternate_phone')?></label>
                                <input type="text" name="alternate_phone" id="contact_alternate" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('email')?></label>
                                <input type="email" name="email" id="contact_email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?=translate('priority_order')?></label>
                                <input type="number" name="priority_order" id="contact_priority" class="form-control" value="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?=translate('address')?></label>
                        <textarea name="address" id="contact_address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><?=translate('notes')?></label>
                        <textarea name="notes" id="contact_notes" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="for_student" id="contact_for_student" value="1" checked>
                            <?=translate('available_for_student_emergency')?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="is_active" id="contact_active" value="1" checked>
                            <?=translate('active')?>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=translate('cancel')?></button>
                    <button type="submit" class="btn btn-primary"><?=translate('save')?></button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
var base_url = '<?=base_url();?>';
var csrf_token = '<?=$this->security->get_csrf_hash();?>';

function showContactModal() {
    $('#contact_id').val('');
    $('#contactForm')[0].reset();
    $('#contact_priority').val('1');
    $('#contact_for_student').prop('checked', true);
    $('#contact_active').prop('checked', true);
    $('#contactModal .modal-title').text('<?=translate('add_emergency_contact')?>');
    $('#contactModal').modal('show');
}

function editContact(id) {
    console.log('Editing contact ID:', id);
    
    $.ajax({
        url: base_url + 'hostel_emergency/get_emergency_contact',
        type: 'POST',
        data: {
            id: id,
            '<?=$this->security->get_csrf_token_name();?>': csrf_token
        },
        dataType: 'json',
        success: function(response) {
            console.log('Contact data:', response);
            
            if (response && response.id) {
                $('#contact_id').val(response.id);
                $('#contact_name').val(response.contact_name);
                $('#contact_role').val(response.contact_role);
                $('#contact_phone').val(response.phone_number);
                $('#contact_alternate').val(response.alternate_phone);
                $('#contact_email').val(response.email);
                $('#contact_address').val(response.address);
                $('#contact_priority').val(response.priority_order);
                $('#contact_notes').val(response.notes);
                $('#contact_for_student').prop('checked', response.for_student == 1);
                $('#contact_active').prop('checked', response.is_active == 1);
                
                if (response.branch_id) {
                    $('select[name="branch_id"]').val(response.branch_id);
                }
                
                $('#contactModal .modal-title').text('<?=translate('edit_emergency_contact')?>');
                $('#contactModal').modal('show');
            } else {
                alert('Failed to load contact data');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('Error loading contact: ' + error);
        }
    });
}

function deleteContact(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?')) {
        $.ajax({
            url: base_url + 'hostel_emergency/contact_delete',
            type: 'POST',
            data: {
                id: id,
                '<?=$this->security->get_csrf_token_name();?>': csrf_token
            },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message || 'Failed to delete');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Error deleting contact: ' + error);
            }
        });
    }
}

$(document).ready(function() {
    $('#branch_filter').change(function() {
        var branch_id = $(this).val();
        var url = base_url + 'hostel_emergency/contacts';
        if(branch_id) {
            window.location.href = url + '?branch_id=' + branch_id;
        } else {
            window.location.href = url;
        }
    });
});
</script>