<!DOCTYPE html>
<html>
<head>
    <title><?=translate('thermal_barcode_label')?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #fff; font-family: Arial, sans-serif; }
        
        .thermal-label {
            width: 50mm;
            height: 30mm;
            padding: 2mm;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: #ffffff;
        }
        
        .thermal-label .barcode-img {
            max-width: 44mm;
            max-height: 18mm;
            width: auto;
            height: auto;
        }
        
        .thermal-label .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #000;
        }
        
        .thermal-label .book-title {
            font-size: 7pt;
            color: #333;
            max-width: 46mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        @media print {
            @page {
                size: 50mm 30mm;
                margin: 0;
                padding: 0;
            }
            
            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
            }
            
            .thermal-label {
                width: 50mm;
                height: 30mm;
                padding: 1.5mm;
                page-break-after: avoid;
            }
        }
        
        @media screen {
            body {
                background: #f0f0f0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            
            .thermal-label {
                border: 1px dashed #ccc;
                border-radius: 4px;
                background: #fff;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .no-print {
                text-align: center;
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <?php foreach ($copies as $copy): ?>
    <div class="thermal-label">
        <img 
            src="<?=base_url('library/generate_barcode_image/' . urlencode($copy['copy_number']))?>" 
            class="barcode-img"
            alt="Barcode"
        >
        <div class="barcode-number"><?=$copy['copy_number']?></div>
        <div class="book-title"><?=htmlspecialchars($copy['title'])?></div>
    </div>
    <?php endforeach; ?>
    
    <div class="no-print">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>