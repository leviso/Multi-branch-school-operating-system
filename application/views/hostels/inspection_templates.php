<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="panel-title"><i class="fas fa-clipboard-list"></i> <?=translate('inspection_templates')?></h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?=base_url('hostels/template_form')?>" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> <?=translate('new_template')?>
                        </a>
                    </div>
                </div>
            </header>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-export">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('template_name')?></th>
                                <th><?=translate('description')?></th>
                                <th><?=translate('inspection_type')?></th>
                                <th><?=translate('items')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach($templates as $template): ?>
                            <tr>
                                <td><?php echo $count++; ?>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td><?php echo get_type_name_by_id('branch', $template['branch_id']); ?>
                                <?php endif; ?>
                                <td><?php echo $template['name']; ?>
                                <td><?php echo substr($template['description'], 0, 50); ?>
                                <td>
                                    <span class="label label-<?php 
                                        echo $template['inspection_type'] == 'daily' ? 'info' : 
                                            ($template['inspection_type'] == 'weekly' ? 'warning' : 'primary'); 
                                    ?>">
                                        <?php echo ucfirst($template['inspection_type']); ?>
                                    </span>
                                
                                <td><?php echo $template['item_count']; ?> items
                                <td>
                                    <?php if($template['is_active']): ?>
                                    <span class="label label-success">Active</span>
                                    <?php else: ?>
                                    <span class="label label-danger">Inactive</span>
                                    <?php endif; ?>
                                
                                <td>
                                    <a href="<?=base_url('hostels/template_form/'.$template['id'])?>" class="btn btn-default btn-circle icon">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?=base_url('hostels/delete_template/'.$template['id'])?>" class="btn btn-danger btn-circle icon" onclick="return confirm('Delete this template? All associated checklist items will be deleted.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                
                             
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>