<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-calendar-alt"></i> <?=translate('book_reservations')?></h4>
            </header>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-export">
                        <thead>
                            <tr>
                                <th><?=translate('sl')?></th>
                                <?php if (is_superadmin_loggedin()): ?>
                                <th><?=translate('branch')?></th>
                                <?php endif; ?>
                                <th><?=translate('book_title')?></th>
                                <th><?=translate('reserved_by')?></th>
                                <th><?=translate('reservation_date')?></th>
                                <th><?=translate('expiry_date')?></th>
                                <th><?=translate('status')?></th>
                                <th><?=translate('action')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            foreach ($reservations as $row): 
                                $branch_name = get_type_name_by_id('branch', $row['branch_id']);
                                $status_class = [
                                    'pending' => 'warning',
                                    'notified' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    'expired' => 'default'
                                ];
                            ?>
                            <tr>
                                <td class="text-center"><?=$count++?></td>
                                <?php if (is_superadmin_loggedin()): ?>
                                <td><?=htmlspecialchars($branch_name)?></td>
                                <?php endif; ?>
                                <td><?=htmlspecialchars($row['title'])?> (<?=$row['book_code']?>)</td>
                                <td><?=htmlspecialchars($row['user_name'] ?? 'Student')?></td>
                                <td><?=_d($row['reservation_date'])?></td>
                                <td><?=_d($row['expiry_date'])?></td>
                                <td>
                                    <span class="label label-<?=$status_class[$row['status']]?>-custom">
                                        <?=ucfirst($row['status'])?>
                                    </span>
                                </td>
                                <td class="min-w-lg">
                                    <?php if ($row['status'] == 'pending'): ?>
                                    <a href="<?=base_url('library/notify_reservation/'.$row['id'])?>" class="btn btn-info btn-circle">
                                        <i class="fas fa-bell"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($row['status'] != 'cancelled' && $row['status'] != 'completed'): ?>
                                    <a href="<?=base_url('library/cancel_reservation/'.$row['id'])?>" class="btn btn-danger btn-circle" onclick="return confirm('<?=translate('cancel_reservation_confirmation')?>')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>