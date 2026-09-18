<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-file-alt"></i> Learning Resources
                </h4>
            </header>
            <div class="panel-body">
                
                <?php if (empty($resources)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x"></i>
                    <h4>No Resources Available</h4>
                    <p>Your teachers will upload learning materials here as needed.</p>
                </div>
                <?php else: ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Subject</th>
                                <th>Description</th>
                                <th>Date Added</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resources as $res): ?>
                            <tr>
                                <td>
                                    <span class="label label-info"><?=ucfirst(html_escape($res['resource_type']))?></span>
                                 </td>
                                <td><strong><?=html_escape($res['title'])?></strong></td>
                                <td><?=html_escape($res['subject_name'] ?? 'General')?></td>
                                <td><?=html_escape($res['description'])?></td>
                                <td><?=date('d M Y', strtotime($res['supplied_at']))?></td>
                                <td>
                                    <?php if ($res['enc_file_name']): ?>
                                    <a href="<?=base_url('userrole/download_resource/' . $res['id'])?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">No file</span>
                                    <?php endif; ?>
                                    </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php endif; ?>
                
            </div>
        </section>
    </div>
</div>