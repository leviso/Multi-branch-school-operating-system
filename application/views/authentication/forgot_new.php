
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width,initial-scale=1" name="viewport">

    <title><?php echo translate('password_restoration');?></title>

    <link rel="shortcut icon" href="<?php echo base_url('assets/images/favicon.png');?>">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.css');?>">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/font-awesome/css/all.min.css'); ?>">

    <!-- jQuery -->
    <script src="<?php echo base_url('assets/vendor/jquery/jquery.js');?>"></script>

    <!-- Sweet Alert -->
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/sweetalert/sweetalert-custom.css');?>">
    <script src="<?php echo base_url('assets/vendor/sweetalert/sweetalert.min.js');?>"></script>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:'Poppins', sans-serif;
            background:linear-gradient(rgba(13,27,42,0.88), rgba(13,27,42,0.88)),
            url('https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=1600&auto=format&fit=crop');
            background-size:cover;
            background-position:center;
            min-height:100vh;
            color:#fff;
        }

        .topbar{
            background:#0d1b2a;
            padding:15px 5%;
            display:flex;
            justify-content:space-between;
            align-items:center;
            box-shadow:0 2px 10px rgba(0,0,0,0.2);
        }

        .brand{
            display:flex;
            align-items:center;
            gap:12px;
        }

        .brand img{
            width:50px;
            height:50px;
            border-radius:50%;
            background:#fff;
            object-fit:cover;
        }

        .brand h2{
            margin:0;
            font-size:1.5rem;
            font-weight:700;
        }

        .top-links{
            display:flex;
            gap:20px;
        }

        .top-links a{
            color:#fff;
            text-decoration:none;
            transition:0.3s;
            font-size:14px;
        }

        .top-links a:hover{
            color:#00b4d8;
        }

        .main-wrapper{
            min-height:calc(100vh - 140px);
            display:flex;
            justify-content:center;
            align-items:center;
            padding:40px 20px;
        }

        .forgot-wrapper{
            width:100%;
            max-width:1100px;
            display:grid;
            grid-template-columns:1fr 450px;
            gap:40px;
            align-items:center;
        }

        .welcome-panel h1{
            font-size:3rem;
            margin-bottom:20px;
            line-height:1.2;
            font-weight:700;
        }

        .welcome-panel p{
            color:#ddd;
            margin-bottom:35px;
            font-size:1.05rem;
        }

        .feature-list{
            display:flex;
            flex-direction:column;
            gap:18px;
        }

        .feature-item{
            display:flex;
            align-items:center;
            gap:15px;
        }

        .feature-item i{
            width:45px;
            height:45px;
            background:#00b4d8;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .forgot-card{
            background:#fff;
            border-radius:20px;
            padding:40px;
            box-shadow:0 10px 30px rgba(0,0,0,0.25);
            color:#333;
        }

        .forgot-header{
            text-align:center;
            margin-bottom:30px;
        }

        .forgot-header img{
            width:70px;
            height:70px;
            border-radius:50%;
            object-fit:cover;
            margin-bottom:15px;
        }

        .forgot-header h3{
            color:#0d1b2a;
            font-weight:700;
            margin-bottom:10px;
        }

        .forgot-header p{
            color:#777;
            font-size:14px;
        }

        .form-group{
            margin-bottom:20px;
        }

        .input-group{
            position:relative;
        }

        .input-group i{
            position:absolute;
            left:15px;
            top:50%;
            transform:translateY(-50%);
            color:#777;
            z-index:10;
        }

        .form-control{
            width:100%;
            height:50px;
            border-radius:10px;
            border:1px solid #ddd;
            padding-left:45px;
            font-size:15px;
        }

        .form-control:focus{
            border-color:#00b4d8;
            outline:none;
            box-shadow:0 0 0 3px rgba(0,180,216,0.15);
        }

        .btn-primary-custom{
            width:100%;
            background:#00b4d8;
            border:none;
            color:#fff;
            padding:14px;
            border-radius:10px;
            font-size:16px;
            font-weight:600;
            transition:0.3s;
        }

        .btn-primary-custom:hover{
            background:#0096c7;
            transform:translateY(-2px);
        }

        .footer-links{
            text-align:center;
            margin-top:20px;
        }

        .footer-links a{
            color:#00b4d8;
            text-decoration:none;
            font-size:14px;
        }

        .footer-links a:hover{
            text-decoration:underline;
        }

        .otp-section,
        .reset-section{
            display:none;
        }

        .countdown{
            text-align:center;
            margin-top:10px;
            color:#666;
            font-size:13px;
        }

        .loader{
            display:inline-block;
            width:16px;
            height:16px;
            border:2px solid #fff;
            border-top-color:transparent;
            border-radius:50%;
            animation:spin 1s linear infinite;
            margin-left:8px;
        }

        @keyframes spin{
            to{ transform:rotate(360deg); }
        }

        .footer{
            background:#0d1b2a;
            text-align:center;
            padding:18px;
            color:#ccc;
            font-size:14px;
        }

        @media(max-width:992px){

            .forgot-wrapper{
                grid-template-columns:1fr;
            }

            .welcome-panel{
                text-align:center;
            }

            .welcome-panel h1{
                font-size:2.2rem;
            }
        }

        @media(max-width:768px){

            .topbar{
                flex-direction:column;
                gap:15px;
                text-align:center;
            }

            .top-links{
                flex-wrap:wrap;
                justify-content:center;
            }

            .forgot-card{
                padding:30px 25px;
            }
        }

    </style>
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">

    <div class="brand">
        <img src="<?=$this->application_model->getBranchImage($branch_id, 'logo')?>" alt="Logo">
        <h2>StudPortal</h2>
    </div>

    <div class="top-links">

        <a href="https://studportal.co.ke">
            <i class="fas fa-home"></i> Main Portal
        </a>

        <a href="<?=base_url('authentication')?>">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>

    </div>

</div>

<!-- MAIN -->
<div class="main-wrapper">

    <div class="forgot-wrapper">

        <!-- LEFT PANEL -->
        <div class="welcome-panel">

            <h1>
                Secure Password Recovery
            </h1>

            <p>
                Reset your account password securely using
                OTP verification through your registered mobile number.
            </p>

            <div class="feature-list">

                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i>
                    <span>OTP Verification via SMS</span>
                </div>

                <div class="feature-item">
                    <i class="fas fa-lock"></i>
                    <span>Secure Password Recovery</span>
                </div>

                <div class="feature-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Protected Account Access</span>
                </div>

                <div class="feature-item">
                    <i class="fas fa-shield-halved"></i>
                    <span>Reliable Authentication Process</span>
                </div>

            </div>

        </div>

        <!-- FORGOT CARD -->
        <div class="forgot-card">

            <div class="forgot-header">

                <img src="<?=$this->application_model->getBranchImage($branch_id, 'logo')?>" alt="Logo">

                <h3>Password Restoration</h3>

                <p>
                    Recover your account access securely
                </p>

            </div>

            <!-- YOUR EXISTING OTP / RESET FORMS GO HERE -->

            <div id="mobileSection">

                <div class="form-group">

                    <div class="input-group">

                        <i class="fas fa-phone"></i>

                        <input type="text"
                               id="phone"
                               class="form-control"
                               placeholder="Enter registered phone number">

                    </div>

                </div>

                <button class="btn-primary-custom" id="sendOtpBtn">
                    <i class="fas fa-paper-plane"></i>
                    Send OTP
                </button>

            </div>

            <div class="otp-section" id="otpSection">

                <div class="form-group" style="margin-top:20px;">

                    <div class="input-group">

                        <i class="fas fa-key"></i>

                        <input type="text"
                               id="otp"
                               class="form-control"
                               placeholder="Enter OTP">

                    </div>

                </div>

                <button class="btn-primary-custom" id="verifyOtpBtn">
                    <i class="fas fa-check-circle"></i>
                    Verify OTP
                </button>

            </div>

            <div class="reset-section" id="resetSection">

                <div class="form-group" style="margin-top:20px;">

                    <div class="input-group">

                        <i class="fas fa-lock"></i>

                        <input type="password"
                               id="newPassword"
                               class="form-control"
                               placeholder="New Password">

                    </div>

                </div>

                <button class="btn-primary-custom" id="resetPasswordBtn">
                    <i class="fas fa-save"></i>
                    Reset Password
                </button>

            </div>

            <div class="footer-links">

                <a href="<?=base_url('authentication')?>">
                    <i class="fas fa-arrow-left"></i>
                    Back to Login
                </a>

            </div>

        </div>

    </div>

</div>

<!-- FOOTER -->
<div class="footer">
    © <?php echo date('Y'); ?> StudPortal. All Rights Reserved.
</div>

<script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.js');?>"></script>

</body>
</html>
