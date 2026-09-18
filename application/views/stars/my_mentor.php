<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="fas fa-users"></i> My Peer Mentor
                </h4>
            </header>
            <div class="panel-body">
                
                <?php if (empty($mentors)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x"></i>
                    <h4>No Mentor Assigned</h4>
                    <p>A peer mentor will be assigned to support your academic recovery.</p>
                </div>
                <?php else: ?>
                
                <?php foreach ($mentors as $mentor): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <i class="fas fa-user-graduate"></i> 
                            <?=html_escape($mentor['first_name'])?> <?=html_escape($mentor['last_name'])?>
                            <span class="label label-success pull-right">Active</span>
                        </h4>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Register No:</strong> <?=html_escape($mentor['register_no'])?><br>
                                <strong>Subject:</strong> <?=html_escape($mentor['subject_name'] ?? 'General Support')?><br>
                                <strong>Assigned Date:</strong> <?=date('d M Y', strtotime($mentor['assigned_date']))?>
                            </div>
                            <div class="col-md-4">
                                <strong>Meetings Held:</strong> 
                                <span class="badge badge-info"><?=$mentor['meetings_count']?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Status:</strong>
                                <span class="label label-<?=($mentor['status'] == 'active') ? 'success' : 'default'?>">
                                    <?=strtoupper(html_escape($mentor['status']))?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="alert alert-info mt-md">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note:</strong> Your mentor is a fellow student who will help you with your studies. 
                    Please attend scheduled meetings and communicate with your mentor regularly.
                </div>
                
                <?php endif; ?>
                
            </div>
        </section>
    </div>
</div>