<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=translate('thermal_barcode_labels')?></title>
    
    <style>
        /* ========== RESET ALL MARGINS & PADDING ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
        }
        
        /* ========== THERMAL LABEL CONTAINER ========== */
        .label-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 0;
            padding: 0;
            margin: 0;
        }
        
        /* ========== INDIVIDUAL LABEL ========== */
        .thermal-label {
            width: 50mm;      /* 50mm width */
            height: 30mm;     /* 30mm height */
            padding: 2mm 2mm 2mm 2mm;
            margin: 0;
            border: none;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            page-break-after: always;
            page-break-inside: avoid;
            overflow: hidden;
        }
        
        /* ========== BARCODE IMAGE ========== */
        .thermal-label .barcode-img {
            display: block;
            max-width: 44mm;
            max-height: 18mm;
            width: auto;
            height: auto;
            margin: 0 auto 1mm auto;
            image-rendering: auto;
            image-rendering: crisp-edges;
        }
        
        /* ========== BARCODE NUMBER (HUMAN READABLE) ========== */
        .thermal-label .barcode-number {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #000000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        
        /* ========== BOOK TITLE (OPTIONAL) ========== */
        .thermal-label .book-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            font-weight: normal;
            color: #333333;
            line-height: 1.2;
            margin-top: 0.5mm;
            max-width: 46mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* ========== COPY NUMBER (OPTIONAL) ========== */
        .thermal-label .copy-info {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 6pt;
            color: #666666;
            margin-top: 0.5mm;
        }
        
        /* ========== PRINT STYLES - CRITICAL FOR THERMAL ========== */
        @media print {
            /* Remove all non-label elements */
            .no-print {
                display: none !important;
            }
            
            /* Page setup for 50mm x 30mm labels */
            @page {
                size: 50mm 30mm;
                margin: 0;
                padding: 0;
            }
            
            html, body {
                margin: 0;
                padding: 0;
                background: #ffffff;
                width: 100%;
                height: 100%;
            }
            
            .label-container {
                display: block;
                padding: 0;
                margin: 0;
            }
            
            .thermal-label {
                width: 50mm;
                height: 30mm;
                padding: 1.5mm 1.5mm 1.5mm 1.5mm;
                margin: 0;
                border: none;
                page-break-after: always;
                page-break-inside: avoid;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }
            
            /* Ensure barcode is crisp and sharp */
            .thermal-label .barcode-img {
                max-width: 44mm;
                max-height: 18mm;
                width: auto;
                height: auto;
                image-rendering: auto;
                image-rendering: crisp-edges;
            }
            
            .thermal-label .barcode-number {
                font-size: 10pt;
                letter-spacing: 1px;
            }
            
            .thermal-label .book-title {
                font-size: 7pt;
                max-width: 46mm;
            }
            
            .thermal-label .copy-info {
                font-size: 6pt;
            }
            
            /* Avoid scaling */
            .thermal-label img {
                transform: scale(1);
            }
            
            /* Fix for Firefox */
            .thermal-label {
                page-break-after: always;
            }
            
            /* Last label - no extra page */
            .thermal-label:last-child {
                page-break-after: avoid;
            }
        }
        
        /* ========== SCREEN PREVIEW STYLES ========== */
        @media screen {
            body {
                background: #f0f0f0;
                padding: 20px;
            }
            
            .no-print {
                text-align: center;
                margin-bottom: 20px;
                padding: 15px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .no-print .btn {
                padding: 10px 25px;
                margin: 0 5px;
                border: none;
                border-radius: 4px;
                font-size: 14px;
                cursor: pointer;
                transition: background 0.3s;
            }
            
            .no-print .btn-primary {
                background: #007bff;
                color: #fff;
            }
            
            .no-print .btn-primary:hover {
                background: #0069d9;
            }
            
            .no-print .btn-default {
                background: #6c757d;
                color: #fff;
            }
            
            .no-print .btn-default:hover {
                background: #5a6268;
            }
            
            .no-print .print-info {
                display: inline-block;
                margin-left: 15px;
                color: #666;
                font-size: 13px;
            }
            
            .label-container {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-start;
                gap: 5px;
                background: #fff;
                padding: 10px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .thermal-label {
                width: 50mm;
                height: 30mm;
                padding: 2mm;
                border: 1px dashed #ccc;
                background: #fff;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                border-radius: 2px;
            }
            
            .thermal-label .barcode-img {
                max-width: 44mm;
                max-height: 18mm;
                width: auto;
                height: auto;
            }
        }
    </style>
</head>
<body>

    <!-- ========== SCREEN CONTROLS (HIDDEN WHEN PRINTING) ========== -->
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> <?=translate('print_labels')?>
        </button>
        <button onclick="window.close()" class="btn btn-default">
            <i class="fas fa-times"></i> <?=translate('close')?>
        </button>
        <span class="print-info">
            <i class="fas fa-info-circle"></i> 
            <?=count($copies)?> <?=translate('labels_ready')?> | 
            <?=translate('select_baxlon_printer_in_dialog')?>
        </span>
    </div>

    <!-- ========== LABELS CONTAINER ========== -->
    <div class="label-container">
        <?php 
        $this->load->library('barcode_generator');
        $count = 0;
        foreach ($copies as $copy): 
            $count++;
            // Shorten title if needed (max 35 chars)
            $short_title = strlen($copy['title']) > 35 ? substr($copy['title'], 0, 32) . '...' : $copy['title'];
        ?>
        <div class="thermal-label">
            <!-- Barcode Image -->
            <img 
                src="<?=base_url('library/generate_barcode_image/' . urlencode($copy['copy_number']))?>" 
                class="barcode-img"
                alt="Barcode <?=$copy['copy_number']?>"
                loading="lazy"
            >
            
            <!-- Human-readable barcode number -->
            <div class="barcode-number"><?=$copy['copy_number']?></div>
            
            <!-- Book Title (optional - comment out if not needed) -->
            <?php if (!empty($short_title)): ?>
            <div class="book-title" title="<?=htmlspecialchars($copy['title'])?>"><?=htmlspecialchars($short_title)?></div>
            <?php endif; ?>
            
            <!-- Copy info (optional) -->
            <?php if (!empty($copy['copy_number'])): ?>
            <div class="copy-info">Copy: <?=$copy['copy_number']?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Auto-print when page loads (optional - uncomment if desired)
        /*
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        */
    </script>
</body>
</html>