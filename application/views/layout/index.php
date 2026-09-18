<!doctype html>
<html class="fixed sidebar-left-sm <?php echo ($theme_config['dark_skin'] == 'true' ? 'dark' : 'sidebar-light');?>">
<!-- html header -->
<?php $this->load->view('layout/header.php');?>

<!-- <body class="loading-overlay-showing" data-loading-overlay> -->
<?php if (isset($global_config['preloader_backend']) && $global_config['preloader_backend'] == 1) { ?>
<body class="loading-overlay-showing" data-loading-overlay>
	<!-- page preloader -->
	<div class="loading-overlay dark">
		<div class="ring-loader">
			Loading <span></span>
		</div>
	</div>
<?php } else { ?>
<body>
<?php } ?>
	<section class="body">
		<!-- top navbar-->
		<?php $this->load->view('layout/topbar.php');?>

<!-- >>> TRIAL/EXPIRY BANNER - SIMPLIFIED VERSION <<< -->
<?php 
if (!is_superadmin_loggedin()): 
    // Debug - remove after fixing
    // log_message('debug', 'Banner check - is_trial: ' . (isset($is_trial) ? ($is_trial ? 'YES' : 'NO') : 'NOT SET'));
    // log_message('debug', 'Banner check - days: ' . (isset($subscription_days_remaining) ? $subscription_days_remaining : 'NOT SET'));
    
    // TRIAL BANNER
    if (isset($is_trial) && $is_trial === true && isset($subscription_days_remaining) && $subscription_days_remaining >= 0):
        $days = $subscription_days_remaining;
        $end_date = isset($subscription_end_date) ? _d($subscription_end_date) : '';
        
        if ($days <= 3):
            // Urgent trial banner (red)
?>
    <div style="background-color: #f8d7da; color: #721c24; padding: 12px 20px; border-bottom: 2px solid #f5c6cb; width: 100%; text-align: center; font-family: inherit; position: relative; z-index: 9999;">
        <i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i>
        <strong>Trial Mode:</strong> Your trial will expire in <strong><?php echo $days; ?> day<?php echo ($days > 1 ? 's' : ''); ?></strong> on <strong><?php echo $end_date; ?></strong>. 
        <a href="<?php echo base_url('subscription/purchase'); ?>" style="color: #721c24; font-weight: bold; text-decoration: underline; margin-left: 10px;">Upgrade Now</a>
        <button type="button" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #721c24; float: right; margin-right: 10px;" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php 
        elseif ($days <= 7):
            // Warning trial banner (yellow)
?>
    <div style="background-color: #fff3cd; color: #856404; padding: 12px 20px; border-bottom: 2px solid #ffeeba; width: 100%; text-align: center; font-family: inherit; position: relative; z-index: 9999;">
        <i class="fas fa-clock" style="margin-right: 10px;"></i>
        <strong>Trial Mode:</strong> <?php echo $days; ?> days remaining until <strong><?php echo $end_date; ?></strong>. 
        <a href="<?php echo base_url('subscription/purchase'); ?>" style="color: #856404; font-weight: bold; text-decoration: underline; margin-left: 10px;">Choose a Plan</a>
        <button type="button" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #856404; float: right; margin-right: 10px;" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php 
        else:
            // Info trial banner (blue)
?>
    <div style="background-color: #cce5ff; color: #004085; padding: 12px 20px; border-bottom: 2px solid #b8daff; width: 100%; text-align: center; font-family: inherit; position: relative; z-index: 9999;">
        <i class="fas fa-info-circle" style="margin-right: 10px;"></i>
        <strong>Trial Mode:</strong> You have <?php echo $days; ?> days left in your trial. Expires on <strong><?php echo $end_date; ?></strong>. 
        <a href="<?php echo base_url('subscription/purchase'); ?>" style="color: #004085; font-weight: bold; text-decoration: underline; margin-left: 10px;">Upgrade</a>
        <button type="button" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #004085; float: right; margin-right: 10px;" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php 
        endif;
    endif;
    
    // EXPIRED BANNER (only if not trial)
    if (isset($subscription_is_expired) && $subscription_is_expired === true && (!isset($is_trial) || $is_trial !== true)):
?>
    <div style="background-color: #f8d7da; color: #721c24; padding: 12px 20px; border-bottom: 2px solid #f5c6cb; width: 100%; text-align: center; font-family: inherit; position: relative; z-index: 9999;">
        <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i>
        <strong>Subscription Expired!</strong> Your subscription expired on <strong><?php echo isset($subscription_end_date) ? _d($subscription_end_date) : ''; ?></strong>.
        <a href="<?php echo base_url('subscription/purchase'); ?>" style="color: #721c24; font-weight: bold; text-decoration: underline; margin-left: 10px;">Renew Now</a>
    </div>
<?php 
    endif; 
endif; 
?>
<!-- >>> BANNER ENDS HERE <<< -->

<!-- >>> FLASH MESSAGES - <<< -->
<?php if ($this->session->flashdata('alert-message-success')) { ?>
    <div class="alert alert-success alert-dismissible" style="margin: 10px 20px 0 20px; border-radius: 4px;">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <i class="fa fa-check-circle"></i> 
        <?php echo $this->session->flashdata('alert-message-success'); ?>
    </div>
<?php } ?>

<?php if ($this->session->flashdata('alert-message-error')) { ?>
    <div class="alert alert-danger alert-dismissible" style="margin: 10px 20px 0 20px; border-radius: 4px;">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <i class="fa fa-exclamation-circle"></i> 
        <?php echo $this->session->flashdata('alert-message-error'); ?>
    </div>
<?php } ?>

<?php if ($this->session->flashdata('alert-message-info')) { ?>
    <div class="alert alert-info alert-dismissible" style="margin: 10px 20px 0 20px; border-radius: 4px;">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <i class="fa fa-info-circle"></i> 
        <?php echo $this->session->flashdata('alert-message-info'); ?>
    </div>
<?php } ?>

<?php if ($this->session->flashdata('alert-message-warning')) { ?>
    <div class="alert alert-warning alert-dismissible" style="margin: 10px 20px 0 20px; border-radius: 4px;">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <i class="fa fa-exclamation-triangle"></i> 
        <?php echo $this->session->flashdata('alert-message-warning'); ?>
    </div>
<?php } ?>
<!-- >>> FLASH MESSAGES END HERE <<< -->

		<div class="inner-wrapper">
			<!-- sidebar -->
			<?php 
			if (is_student_loggedin() || is_parent_loggedin()) {
				$this->load->view('userrole/sidebar'); 
			} else {
				$this->load->view('layout/sidebar'); 
			} 
			?>
			<!-- page main content -->
			<section role="main" class="content-body">
				<header class="page-header">
					<a class="page-title-icon" href="<?php echo base_url('dashboard');?>"><i class="fas fa-home"></i></a>
					<h2><?php echo $title;?></h2>
				</header>
				<?php $this->load->view($sub_page); ?>
			</section>
		</div>
	</section>

	<!-- JS Script -->
	<?php $this->load->view('layout/script.php');?>
	
	<?php
	$alertclass = "";
	if($this->session->flashdata('alert-message-success')){
		$alertclass = "success";
	} else if ($this->session->flashdata('alert-message-error')){
		$alertclass = "error";
	} else if ($this->session->flashdata('alert-message-info')){
		$alertclass = "info";
	}
	if($alertclass != ''):
		$alert_message = $this->session->flashdata('alert-message-'. $alertclass);
	?>
		<script type="text/javascript">
			swal({
				toast: true,
				position: 'top-end',
				type: '<?php echo $alertclass?>',
				title: '<?php echo $alert_message?>',
				confirmButtonClass: 'btn btn-default',
				buttonsStyling: false,
				timer: 8000
			})
		</script>
	<?php endif; ?>

	<!-- sweetalert box -->
	<script type="text/javascript">
		function confirm_modal(delete_url) {
			swal({
				title: "<?php echo translate('are_you_sure')?>",
				text: "<?php echo translate('delete_this_information')?>",
				type: "warning",
				showCancelButton: true,
				confirmButtonClass: "btn btn-default swal2-btn-default",
				cancelButtonClass: "btn btn-default swal2-btn-default",
				confirmButtonText: "<?php echo translate('yes_continue')?>",
				cancelButtonText: "<?php echo translate('cancel')?>",
				buttonsStyling: false,
				footer: "<?php echo translate('deleted_note')?>"
			}).then((result) => {
				if (result.value) {
					$.ajax({
						url: delete_url,
						type: "POST",
						success:function(data) {
							swal({
							title: "<?php echo translate('deleted')?>",
							text: "<?php echo translate('information_deleted')?>",
							buttonsStyling: false,
							showCloseButton: true,
							focusConfirm: false,
							confirmButtonClass: "btn btn-default swal2-btn-default",
							type: "success"
							}).then((result) => {
								if (result.value) {
									location.reload();
								}
							});
						}
					});
				}
			});
		}
	</script>
   <?php 
    $config = $this->application_model->whatsappChat();
    if ($config && isset($config['backend_enable_chat']) && $config['backend_enable_chat'] == 1) {
    ?>
    <div class="whatsapp-popup">
        <div class="whatsapp-button">
            <i class="fab fa-whatsapp i-open"></i>
            <i class="far fa-times-circle fa-fw i-close"></i>
        </div>
        <div class="popup-content">
            <div class="popup-content-header">
                <i class="fab fa-whatsapp"></i>
                <h5><?php echo $config['header_title'] ?><span><?php echo $config['subtitle'] ?></span></h5>
            </div>
            <div class="whatsapp-content">
                <ul>
                <?php $whatsappAgent = $this->application_model->whatsappAgent(); 
                    foreach ($whatsappAgent as $key => $value) {
                        $online = "offline";
                        if (strtolower($value->weekend) != strtolower(date('l'))) {
                            $now = time();
                            $starttime = strtotime($value->start_time);
                            $endtime = strtotime($value->end_time);
                            if ($now >= $starttime && $now <= $endtime) {
                                $online = "online";
                            }
                        }
                ?>
                    <li class="<?php echo $online ?>">
                        <a class="whatsapp-agent" href="javascript:void(0)" data-number="<?php echo $value->whataspp_number; ?>">
                            <div class="whatsapp-img">
                                <img src="<?php echo get_image_url('whatsapp_agent', $value->agent_image); ?>" class="whatsapp-avatar" width="60" height="60">
                            </div>
                            <div>
                                <span class="whatsapp-text">
                                    <span class="whatsapp-label"><?php echo $value->agent_designation; ?> - <span class="status"><?php echo ucfirst($online) ?></span></span> <?php echo $value->agent_name; ?>
                                </span>
                            </div>
                        </a>
                    </li>
                <?php } ?>
                </ul>
            </div>
            <div class="content-footer">
                <p><?php echo $config['footer_text'] ?></p>
            </div>
        </div>
    </div>
    <?php } ?>
</body>
</html>