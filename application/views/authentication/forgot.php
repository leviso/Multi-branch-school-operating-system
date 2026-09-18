<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width,initial-scale=1" name="viewport">
    <title><?php echo translate('password_restoration');?></title>
    <link rel="shortcut icon" href="<?php echo base_url('assets/images/favicon.png');?>">
    <link href="https://fonts.googleapis.com/css?family=Signika:300,400,600,700" rel="stylesheet"> 
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.css');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/font-awesome/css/all.min.css'); ?>">
    <script src="<?php echo base_url('assets/vendor/jquery/jquery.js');?>"></script>
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/sweetalert/sweetalert-custom.css');?>">
    <script src="<?php echo base_url('assets/vendor/sweetalert/sweetalert.min.js');?>"></script>
    <link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/style.css');?>">
    <style>
        .otp-section, .reset-section { display: none; }
        .loader { display: inline-block; width: 16px; height: 16px; border: 2px solid #fff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; margin-left: 8px; vertical-align: middle; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .countdown { font-size: 12px; color: #666; margin-top: 5px; text-align: center; }
        .resend-link { background: none; border: none; color: #1e88e5; cursor: pointer; padding: 0; margin-left: 5px; }
    </style>
</head>
<body>
<div class="auth-main">
    <div class="container">
        <div class="slideIn">
            <div class="col-lg-4 col-lg-offset-1 col-md-4 col-md-offset-1 col-sm-12 col-xs-12 no-padding fitxt-center">
                <div class="image-area">
                    <div class="content">
                        <div class="image-hader"><h2><?php echo translate('welcome_to');?></h2></div>
                        <div class="center img-hol-p">
                            <img src="<?=$this->application_model->getBranchImage($branch_id, 'logo')?>" height="60" alt="School">
                        </div>
                        <div class="address"><p><?php echo $global_config['address'];?></p></div>
                        <div class="f-social-links center">
                            <a href="<?php echo $global_config['facebook_url'];?>" target="_blank"><span class="fab fa-facebook-f"></span></a>
                            <a href="<?php echo $global_config['twitter_url'];?>" target="_blank"><span class="fab fa-twitter"></span></a>
                            <a href="<?php echo $global_config['linkedin_url'];?>" target="_blank"><span class="fab fa-linkedin-in"></span></a>
                            <a href="<?php echo $global_config['youtube_url'];?>" target="_blank"><span class="fab fa-youtube"></span></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-lg-offset-right-1 col-md-6 col-md-offset-right-1 col-sm-12 col-xs-12 no-padding">
                <div class="sign-area">
                    <div class="sign-hader pt-md">
                        <img src="<?=$this->application_model->getBranchImage($branch_id, 'logo')?>" height="54" alt="">
                        <h2><?=$global_config['institute_name']?></h2>
                    </div>

                    <!-- Step 1: Enter Mobile Number -->
                    <div id="mobileSection">
                        <div class="forgot-header">
                            <h4><i class="fas fa-mobile-alt"></i> Reset Password</h4>
                            <p>Enter your registered mobile number to receive OTP</p>
                        </div>
                        <div class="form-group">
                            <div class="input-group input-group-icon">
                                <span class="input-group-addon"><span class="icon"><i class="fas fa-phone"></i></span></span>
                                <input type="tel" id="mobile" class="form-control" placeholder="e.g., 07XXXXXXXX or 2547XXXXXXXX">
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="button" id="send_btn" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i> Send OTP
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Enter OTP -->
                    <div id="otpSection" class="otp-section">
                        <div class="forgot-header">
                            <h4><i class="fas fa-key"></i> Enter OTP</h4>
                            <p>Enter the 6-digit code sent to your phone</p>
                        </div>
                        <div class="form-group">
                            <div class="input-group input-group-icon">
                                <span class="input-group-addon"><span class="icon"><i class="fas fa-shield-alt"></i></span></span>
                                <input type="text" id="otp" class="form-control" placeholder="Enter 6-digit OTP" maxlength="6">
                            </div>
                            <div class="countdown">
                                <span id="timer_text"></span>
                                <button type="button" id="resend_btn" class="resend-link" style="display:none;">Resend OTP</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="button" id="verify_btn" class="btn btn-primary btn-block">
                                <i class="fas fa-check"></i> Verify OTP
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Reset Password -->
                    <div id="resetSection" class="reset-section">
                        <div class="forgot-header">
                            <h4><i class="fas fa-lock"></i> New Password</h4>
                            <p>Create a new password for your account</p>
                        </div>
                        <div class="form-group">
                            <div class="input-group input-group-icon">
                                <span class="input-group-addon"><span class="icon"><i class="fas fa-key"></i></span></span>
                                <input type="password" id="password" class="form-control" placeholder="New Password (min 4 characters)">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="input-group input-group-icon">
                                <span class="input-group-addon"><span class="icon"><i class="fas fa-key"></i></span></span>
                                <input type="password" id="c_password" class="form-control" placeholder="Confirm Password">
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="button" id="reset_btn" class="btn btn-primary btn-block">
                                <i class="fas fa-check-circle"></i> Reset Password
                            </button>
                        </div>
                    </div>

                    <div class="text-center mt-md">
                        <a href="<?php echo base_url('authentication/index') . $this->authentication_model->getSegment(3); ?>">
                            <i class="fas fa-long-arrow-alt-left"></i> Back to Login
                        </a>
                    </div>
                    <div class="sign-footer"><p><?php echo $global_config['footer_text'];?></p></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var SITE_URL = '<?php echo rtrim(base_url(), '/'); ?>';
    var countdownInterval;
    var canResend = false;

    // Helper function to show loader on button
    function setButtonLoading($btn, isLoading, originalHtml) {
        if (isLoading) {
            $btn.data('original-html', $btn.html());
            $btn.prop('disabled', true).html(originalHtml + ' <span class="loader"></span>');
        } else {
            $btn.prop('disabled', false).html($btn.data('original-html'));
        }
    }

    // Function to start countdown for resend
    function startCountdown(seconds) {
        if (countdownInterval) clearInterval(countdownInterval);
        canResend = false;
        $('#resend_btn').hide();
        var timer = seconds;
        $('#timer_text').text('Resend available in ' + timer + 's');
        
        countdownInterval = setInterval(function() {
            timer--;
            if (timer <= 0) {
                clearInterval(countdownInterval);
                canResend = true;
                $('#timer_text').text('');
                $('#resend_btn').show().prop('disabled', false).text('Resend OTP');
            } else {
                $('#timer_text').text('Resend available in ' + timer + 's');
            }
        }, 1000);
    }

    // 1. Send OTP
    $('#send_btn').click(function() {
        var mobile = $('#mobile').val().trim();
        if (!mobile) {
            swal('Error', 'Please enter your mobile number', 'error');
            return;
        }

        var $btn = $(this);
        var originalHtml = $btn.html();
        setButtonLoading($btn, true, '<i class="fas fa-paper-plane"></i> Sending');

        $.ajax({
            url: SITE_URL + '/authentication/forgot',
            type: 'POST',
            data: { action: 'request_otp', mobile: mobile },
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if (response.status) {
                    $('#mobileSection').hide();
                    $('#otpSection').show();
                    startCountdown(60);
                    swal('Success', response.message, 'success');
                } else {
                    swal('Error', response.message, 'error');
                }
                setButtonLoading($btn, false, originalHtml);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                swal('Error', 'Network error. Please try again.', 'error');
                setButtonLoading($btn, false, originalHtml);
            }
        });
    });

    // 2. Verify OTP
    $('#verify_btn').click(function() {
        var otp = $('#otp').val().trim();
        if (!otp || otp.length !== 6) {
            swal('Error', 'Please enter the 6-digit OTP', 'error');
            return;
        }

        var $btn = $(this);
        var originalHtml = $btn.html();
        setButtonLoading($btn, true, '<i class="fas fa-check"></i> Verifying');

        $.ajax({
            url: SITE_URL + '/authentication/forgot',
            type: 'POST',
            data: { action: 'verify_otp', otp: otp },
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if (response.status) {
                    $('#otpSection').hide();
                    $('#resetSection').show();
                    swal('Success', response.message, 'success');
                } else {
                    swal('Error', response.message, 'error');
                }
                setButtonLoading($btn, false, originalHtml);
            },
            error: function() {
                swal('Error', 'Network error. Please try again.', 'error');
                setButtonLoading($btn, false, originalHtml);
            }
        });
    });

    // 3. Reset Password
    $('#reset_btn').click(function() {
        var password = $('#password').val();
        var c_password = $('#c_password').val();

        if (!password || password.length < 4) {
            swal('Error', 'Password must be at least 4 characters', 'error');
            return;
        }
        if (password !== c_password) {
            swal('Error', 'Passwords do not match', 'error');
            return;
        }

        var $btn = $(this);
        var originalHtml = $btn.html();
        setButtonLoading($btn, true, '<i class="fas fa-check-circle"></i> Resetting');

        $.ajax({
            url: SITE_URL + '/authentication/forgot',
            type: 'POST',
            data: { action: 'reset_password', password: password, c_password: c_password },
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if (response.status) {
                    swal('Success', response.message, 'success').then(function() {
                        window.location.href = SITE_URL + '/authentication';
                    });
                } else {
                    swal('Error', response.message, 'error');
                }
                setButtonLoading($btn, false, originalHtml);
            },
            error: function() {
                swal('Error', 'Network error. Please try again.', 'error');
                setButtonLoading($btn, false, originalHtml);
            }
        });
    });

    // 4. Resend OTP
    $('#resend_btn').click(function() {
        if (!canResend) return;

        var mobile = $('#mobile').val();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: SITE_URL + '/authentication/forgot',
            type: 'POST',
            data: { action: 'request_otp', mobile: mobile },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    swal('Success', 'New OTP sent to your phone', 'success');
                    startCountdown(60);
                } else {
                    swal('Error', response.message, 'error');
                    $btn.prop('disabled', false).text('Resend OTP');
                }
            },
            error: function() {
                swal('Error', 'Network error', 'error');
                $btn.prop('disabled', false).text('Resend OTP');
            }
        });
    });
});
</script>
</body>
</html>