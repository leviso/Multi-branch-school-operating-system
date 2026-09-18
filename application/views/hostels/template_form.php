<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-clipboard-list"></i> 
                    <?php echo empty($template) ? translate('create_template') : translate('edit_template'); ?>
                </h4>
            </header>
            <div class="panel-body">
                <?php echo form_open('hostels/save_template', array('class' => 'form-horizontal form-bordered', 'id' => 'templateForm')); ?>
                    
                    <?php if (!empty($template)): ?>
                    <input type="hidden" name="template_id" value="<?=$template['id']?>">
                    <?php endif; ?>
                    
                    <?php if (is_superadmin_loggedin()): ?>
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('branch')?> <span class="required">*</span></label>
                        <div class="col-md-6">
                            <?php
                                $arrayBranch = $this->app_lib->getSelectList('branch');
                                $selected_branch = !empty($template) ? $template['branch_id'] : $branch_id;
                                echo form_dropdown("branch_id", $arrayBranch, $selected_branch, "class='form-control' id='branch_id'
                                data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
                            ?>
                            <span class="error"></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('template_name')?> <span class="required">*</span></label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="name" value="<?php echo !empty($template) ? $template['name'] : ''; ?>" required>
                            <span class="error"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('description')?></label>
                        <div class="col-md-6">
                            <textarea class="form-control" name="description" rows="3"><?php echo !empty($template) ? $template['description'] : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-3 control-label"><?=translate('inspection_type')?> <span class="required">*</span></label>
                        <div class="col-md-6">
                            <select name="inspection_type" class="form-control" required>
                                <option value="daily" <?php echo (!empty($template) && $template['inspection_type'] == 'daily') ? 'selected' : ''; ?>>Daily Inspection</option>
                                <option value="weekly" <?php echo (!empty($template) && $template['inspection_type'] == 'weekly') ? 'selected' : ''; ?>>Weekly Inspection</option>
                                <option value="monthly" <?php echo (!empty($template) && $template['inspection_type'] == 'monthly') ? 'selected' : ''; ?>>Monthly Inspection</option>
                                <option value="termly" <?php echo (!empty($template) && $template['inspection_type'] == 'termly') ? 'selected' : ''; ?>>Termly Inspection</option>
                            </select>
                            <span class="error"></span>
                        </div>
                    </div>
                    
                    <!-- Checklist Items Section -->
                    <div class="headers-line mt-md">
                        <i class="fas fa-check-double"></i> <?=translate('checklist_items')?>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Add items that will be checked during inspection. Each item can have a weight (importance) from 1-10.
                    </div>
                    
                    <div id="items_container">
                        <?php if (!empty($template_items)): ?>
                            <?php foreach($template_items as $index => $item): ?>
                            <div class="item-row panel panel-default">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label><?=translate('category')?></label>
                                                <select name="items[<?=$index?>][category]" class="form-control">
                                                    <option value="cleanliness" <?=$item['category'] == 'cleanliness' ? 'selected' : ''?>>Cleanliness</option>
                                                    <option value="bedding" <?=$item['category'] == 'bedding' ? 'selected' : ''?>>Bedding</option>
                                                    <option value="bathroom" <?=$item['category'] == 'bathroom' ? 'selected' : ''?>>Bathroom</option>
                                                    <option value="storage" <?=$item['category'] == 'storage' ? 'selected' : ''?>>Storage</option>
                                                    <option value="maintenance" <?=$item['category'] == 'maintenance' ? 'selected' : ''?>>Maintenance</option>
                                                    <option value="safety" <?=$item['category'] == 'safety' ? 'selected' : ''?>>Safety</option>
                                                    <option value="general" <?=$item['category'] == 'general' ? 'selected' : ''?>>General</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label><?=translate('item_name')?> <span class="required">*</span></label>
                                                <input type="text" name="items[<?=$index?>][item_name]" class="form-control" value="<?=$item['item_name']?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label><?=translate('max_score')?></label>
                                                <input type="number" name="items[<?=$index?>][max_score]" class="form-control" value="<?=$item['max_score']?>" min="1" max="10">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label><?=translate('weight')?></label>
                                                <input type="number" name="items[<?=$index?>][weight]" class="form-control" value="<?=$item['weight']?>" min="0" max="100" step="1">
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button type="button" class="btn btn-danger btn-block" onclick="removeItem(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="items[<?=$index?>][sort_order]" value="<?=$index?>">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row mb-md">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-default" onclick="addItem()">
                                <i class="fas fa-plus-circle"></i> <?=translate('add_checklist_item')?>
                            </button>
                        </div>
                    </div>
                    
                    <footer class="panel-footer">
                        <div class="row">
                            <div class="col-md-offset-10 col-md-2">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> <?=translate('save_template')?>
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
var itemCount = <?php echo !empty($template_items) ? count($template_items) : 0; ?>;

$(document).ready(function() {
    $('#templateForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.html();
        
        // Validate at least one item exists
        if ($('.item-row').length === 0) {
            alert('Please add at least one checklist item');
            return false;
        }
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalText);
                
                if (response.status === 'success') {
                    alert(response.message);
                    window.location.href = base_url + 'hostels/inspection_templates';
                } else {
                    var errorMsg = response.message || 'Failed to save template';
                    if (response.error) {
                        errorMsg = Object.values(response.error).join('\n');
                    }
                    alert('Error: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalText);
                alert('Server error: ' + error);
            }
        });
    });
});

function addItem() {
    var html = '<div class="item-row panel panel-default">';
    html += '<div class="panel-body">';
    html += '<div class="row">';
    html += '<div class="col-md-3">';
    html += '<div class="form-group">';
    html += '<label>Category</label>';
    html += '<select name="items[' + itemCount + '][category]" class="form-control">';
    html += '<option value="cleanliness">Cleanliness</option>';
    html += '<option value="bedding">Bedding</option>';
    html += '<option value="bathroom">Bathroom</option>';
    html += '<option value="storage">Storage</option>';
    html += '<option value="maintenance">Maintenance</option>';
    html += '<option value="safety">Safety</option>';
    html += '<option value="general">General</option>';
    html += '</select>';
    html += '</div>';
    html += '</div>';
    html += '<div class="col-md-4">';
    html += '<div class="form-group">';
    html += '<label>Item Name <span class="required">*</span></label>';
    html += '<input type="text" name="items[' + itemCount + '][item_name]" class="form-control" required>';
    html += '</div>';
    html += '</div>';
    html += '<div class="col-md-2">';
    html += '<div class="form-group">';
    html += '<label>Max Score (1-10)</label>';
    html += '<input type="number" name="items[' + itemCount + '][max_score]" class="form-control" value="5" min="1" max="10">';
    html += '</div>';
    html += '</div>';
    html += '<div class="col-md-2">';
    html += '<div class="form-group">';
    html += '<label>Weight (0-100)</label>';
    html += '<input type="number" name="items[' + itemCount + '][weight]" class="form-control" value="10" min="0" max="100" step="1">';
    html += '</div>';
    html += '</div>';
    html += '<div class="col-md-1">';
    html += '<div class="form-group">';
    html += '<label>&nbsp;</label>';
    html += '<button type="button" class="btn btn-danger btn-block" onclick="removeItem(this)"><i class="fas fa-trash"></i></button>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '<input type="hidden" name="items[' + itemCount + '][sort_order]" value="' + itemCount + '">';
    html += '</div>';
    html += '</div>';
    
    $('#items_container').append(html);
    itemCount++;
}

function removeItem(btn) {
    $(btn).closest('.item-row').remove();
}
</script>