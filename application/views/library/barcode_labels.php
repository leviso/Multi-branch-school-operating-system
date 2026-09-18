<!DOCTYPE html>
<html>
<head>
    <title><?=translate('barcode_labels')?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .label { 
            width: 200px; 
            height: 100px; 
            border: 1px solid #ccc; 
            float: left; 
            margin: 10px; 
            padding: 10px;
            text-align: center;
        }
        .barcode { margin: 10px 0; }
        .title { font-size: 12px; font-weight: bold; }
        @media print {
            .no-print { display: none; }
            .label { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary"><?=translate('print')?></button>
        <button onclick="window.close()" class="btn btn-default"><?=translate('close')?></button>
    </div>
    
    <?php foreach ($copies as $copy): ?>
    <div class="label">
        <div class="title"><?=htmlspecialchars($copy['title'])?></div>
        <div class="barcode">
            <?php 
            $this->load->library('barcode');
            echo $this->barcode->generate($copy['barcode'], 'html');
            ?>
        </div>
        <div class="code"><?=$copy['barcode']?></div>
    </div>
    <?php endforeach; ?>
</body>
</html>