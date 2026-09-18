<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $title; ?></title>
    <link href="<?=base_url('assets/css/bootstrap.min.css')?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="#">STARS Module</a>
            </div>
            <ul class="nav navbar-nav">
                <li><a href="<?=base_url('stars/stars_dashboard')?>">Dashboard</a></li>
                <li><a href="<?=base_url('stars/assessments')?>">Assessments</a></li>
                <li><a href="<?=base_url('stars/active_iarp')?>">Active Plans</a></li>
                <li><a href="<?=base_url('stars/reports')?>">Reports</a></li>
            </ul>
        </div>
    </nav>
    <div class="container-fluid">
        <?php $this->load->view($sub_page); ?>
    </div>
</body>
</html>