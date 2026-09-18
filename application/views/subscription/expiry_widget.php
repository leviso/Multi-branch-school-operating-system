<?php 
// Debug - remove after fixing
// log_message('debug', 'expiry_widget loaded - is_trial: ' . (isset($is_trial) ? ($is_trial ? 'YES' : 'NO') : 'NOT SET'));
// log_message('debug', 'expiry_widget - days: ' . (isset($subscription_days_remaining) ? $subscription_days_remaining : 'NOT SET'));

if (!is_superadmin_loggedin()): 
    $is_trial = isset($is_trial) ? $is_trial : false;
    $status = isset($subscription_status) ? $subscription_status : 'unknown';
    $days = isset($subscription_days_remaining) ? (int)$subscription_days_remaining : -1;
    $end_date = isset($subscription_end_date) ? $subscription_end_date : '';
    
    // Only show if we have valid data
    if ($days >= -1): 
?>
<div class="widget widget-expiry">
    <div class="widget-simple">
        
        <?php if ($is_trial && $days > 0): ?>
            <!-- TRIAL MODE -->
            <div class="widget-value text-info">
                <i class="fas fa-star fa-2x"></i>
            </div>
            <div class="widget-title">Trial Mode</div>
            <div class="widget-desc">
                <strong><?=$days?> day<?=($days > 1 ? 's' : '')?></strong> remaining<br>
                Ends on <?=_d($end_date)?><br>
                <a href="<?=base_url('subscription/purchase')?>" class="btn btn-info btn-xs">Upgrade Now</a>
            </div>
        
        <?php elseif ($status == 'active' && $days > 7): ?>
            <!-- ACTIVE SUBSCRIPTION - LONG TIME -->
            <div class="widget-value text-success">
                <i class="fas fa-check-circle fa-2x"></i>
            </div>
            <div class="widget-title">Subscription Active</div>
            <div class="widget-desc">Expires on <?=_d($end_date)?></div>
        
        <?php elseif ($status == 'active' && $days > 3 && $days <= 7): ?>
            <!-- EXPIRING SOON -->
            <div class="widget-value text-warning">
                <i class="fas fa-clock fa-2x"></i>
            </div>
            <div class="widget-title">Expiring Soon</div>
            <div class="widget-desc">
                <strong><?=$days?> days</strong> remaining<br>
                <a href="<?=base_url('subscription/purchase')?>" class="btn btn-warning btn-xs">Renew Now</a>
            </div>
        
        <?php elseif ($status == 'active' && $days > 0 && $days <= 3): ?>
            <!-- EXPIRING VERY SOON -->
            <div class="widget-value text-danger">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
            </div>
            <div class="widget-title">Expiring!</div>
            <div class="widget-desc">
                Only <strong><?=$days?> days</strong> left!<br>
                <a href="<?=base_url('subscription/purchase')?>" class="btn btn-danger btn-xs">Renew Now</a>
            </div>
        
        <?php elseif ($days <= 0 || $status == 'expired'): ?>
            <!-- EXPIRED -->
            <div class="widget-value text-danger">
                <i class="fas fa-times-circle fa-2x"></i>
            </div>
            <div class="widget-title">Expired</div>
            <div class="widget-desc">
                Expired on <?=_d($end_date)?><br>
                <a href="<?=base_url('subscription/purchase')?>" class="btn btn-danger btn-xs">Renew</a>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
.widget-expiry {
    background: #fff;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
}
.widget-value {
    font-size: 24px;
    margin-bottom: 10px;
}
.widget-value.text-info {
    color: #17a2b8;
}
.widget-title {
    font-weight: bold;
    margin-bottom: 5px;
}
.widget-desc {
    color: #666;
    font-size: 12px;
}
.widget-desc .btn-xs {
    margin-top: 5px;
}
.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: white;
}
.btn-info:hover {
    background-color: #138496;
    border-color: #117a8b;
}
</style>
<?php 
    endif; 
endif; 
?>