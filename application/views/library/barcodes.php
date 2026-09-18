<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-qrcode"></i> <?=translate('barcodes')?> - <?=htmlspecialchars($book->title)?></h4>
                <div class="panel-btn">
                    <a href="<?=base_url('library/book')?>" class="btn btn-default btn-circle">
                        <i class="fas fa-arrow-left"></i> <?=translate('back')?>
                    </a>
                </div>
            </header>
            <div class="panel-body">
                <!-- Book Information -->
                <div class="row mb-lg">
                    <div class="col-md-6">
                        <table class="table table-condensed table-borderless">
                            <tr><th width="120"><?=translate('book_title')?>:</th><td><?=htmlspecialchars($book->title)?></td></tr>
                            <tr><th><?=translate('author')?>:</th><td><?=htmlspecialchars($book->author)?></td></tr>
                            <tr><th>ISBN:</th><td><?=$book->isbn_no?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-condensed table-borderless">
                            <tr><th width="120"><?=translate('publisher')?>:</th><td><?=htmlspecialchars($book->publisher)?></td></tr>
                            <tr><th><?=translate('edition')?>:</th><td><?=htmlspecialchars($book->edition)?></td></tr>
                            <tr><th><?=translate('total_copies')?>:</th><td><span class="label label-primary-custom"><?=count($copies)?></span></td></tr>
                        </table>
                    </div>
                </div>

                <!-- Barcodes Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="barcodesTable">
                        <thead>
                            <tr class="bg-light">
                                <th width="30">
                                    <input type="checkbox" id="select_all" onchange="toggleAllCheckboxes(this)">
                                </th>
                                <th class="text-center" width="50">#</th>
                                <th><?=translate('copy_number')?></th>
                                <th><?=translate('barcode')?></th>
                                <th class="text-center" width="100"><?=translate('status')?></th>
                                <th class="text-center" width="100"><?=translate('condition')?></th>
                                <th class="text-center" width="180"><?=translate('barcode_image')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($copies) && count($copies) > 0): ?>
                                <?php foreach ($copies as $index => $copy): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="copy-checkbox" name="selected_copies[]" value="<?=$copy['id']?>">
                                    </td>
                                    <td class="text-center"><?=($index + 1)?></td>
                                    <td><strong><?=$copy['copy_number']?></strong></td>
                                    <td><code><?=$copy['barcode']?></code></td>
                                    <td class="text-center">
                                        <?php
                                        $status_color = match($copy['status']) {
                                            'available' => 'success',
                                            'issued' => 'warning',
                                            'lost' => 'danger',
                                            'damaged' => 'danger',
                                            default => 'default'
                                        };
                                        ?>
                                        <span class="label label-<?=$status_color?>-custom"><?=ucfirst($copy['status'])?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $condition_color = in_array($copy['condition'], ['new', 'good']) ? 'success' : 'warning';
                                        ?>
                                        <span class="label label-<?=$condition_color?>-custom"><?=ucfirst($copy['condition'])?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="barcode-wrapper">
                                            <!-- Barcode Image -->
                                            <img src="<?=base_url('library/generate_barcode_image/' . urlencode($copy['copy_number']))?>" 
                                                class="barcode-image"
                                                alt="Barcode">
                                            <!-- Human-readable number below the barcode -->
                                            <div class="barcode-number"><?=$copy['copy_number']?></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center"><?=translate('no_copies_found')?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Print Actions - NEW SECTION (Does not affect existing functionality) -->
                    <div class="row mt-lg">
                        <div class="col-md-12">
                            <div class="well well-sm" style="background: #f8f9fa; border: 1px solid #dee2e6;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-print"></i> <?=translate('thermal_label_printing')?></strong>
                                        <br>
                                        <small class="text-muted"><?=translate('select_copies_to_print_thermal_labels')?></small>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <button type="button" class="btn btn-success" id="print_selected_thermal">
                                            <i class="fas fa-print"></i> <?=translate('print_thermal_labels')?>
                                        </button>
                                        <span id="selected_count" class="badge badge-info" style="margin-left: 10px;">0 <?=translate('selected')?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Buttons -->
                <div class="row mt-lg">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-success" onclick="exportTableToExcel()">
                            <i class="fas fa-file-excel"></i> <?=translate('export_excel')?>
                        </button>
                        <button type="button" class="btn btn-info" onclick="window.print();">
                            <i class="fas fa-print"></i> <?=translate('print')?>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<style>
.barcode-wrapper {
    display: inline-block;
    background: #fff;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-align: center;
    min-width: 180px;
}

.barcode-image {
    display: block;
    margin: 0 auto;
    /* Do NOT set width/height - keep original dimensions */
}

.barcode-number {
    margin-top: 8px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    font-weight: bold;
    letter-spacing: 1px;
    color: #000;
    text-align: center;
}

.table-bordered td, .table-bordered th {
    vertical-align: middle !important;
}
code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 12px;
}
.label {
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 3px;
    display: inline-block;
}
.label-success-custom { background-color: #28a745; color: #fff; }
.label-warning-custom { background-color: #ffc107; color: #212529; }
.label-danger-custom { background-color: #dc3545; color: #fff; }
.label-info-custom { background-color: #17a2b8; color: #fff; }
.label-primary-custom { background-color: #007bff; color: #fff; }
.label-default-custom { background-color: #6c757d; color: #fff; }
.bg-light { background-color: #f8f9fa; }
.mb-lg { margin-bottom: 20px; }
.mt-lg { margin-top: 20px; }
.text-center { text-align: center; }

@media print {
    .panel-heading, .panel-btn, .btn, .mt-lg {
        display: none !important;
    }
    .barcode-image {
        height: 35px;
    }
    .label {
        border: 1px solid #ccc;
        background: none !important;
        color: #000 !important;
    }
}
@media print {
    .barcode-wrapper {
        border: none;
        padding: 0;
    }
    .barcode-number {
        font-size: 11px;
    }
}
</style>

<script>
function exportTableToExcel() {
    var table = document.getElementById('barcodesTable');
    var html = table.outerHTML;
    var blob = new Blob([html], {type: 'application/vnd.ms-excel'});
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'barcodes_<?=date('Y-m-d')?>.xls';
    link.click();
    URL.revokeObjectURL(link.href);
}
// ========== THERMAL LABEL PRINTING ==========
$(document).ready(function() {
    // Select all checkbox
    $('#select_all').on('change', function() {
        var checked = $(this).prop('checked');
        $('.copy-checkbox').prop('checked', checked);
        updateSelectedCount();
    });
    
    // Individual checkbox change
    $(document).on('change', '.copy-checkbox', function() {
        updateSelectedCount();
        var total = $('.copy-checkbox').length;
        var checked = $('.copy-checkbox:checked').length;
        $('#select_all').prop('checked', total > 0 && checked === total);
    });
    
    // Update selected count
    function updateSelectedCount() {
        var count = $('.copy-checkbox:checked').length;
        $('#selected_count').text(count + ' <?=translate('selected')?>');
    }
    
    // Print selected thermal labels
$('#print_selected_thermal').on('click', function() {
    var selected = [];
    $('.copy-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    
    if (selected.length === 0) {
        alert('<?=translate('please_select_at_least_one_copy')?>');
        return;
    }
    
    // Submit selected copies to print controller
    var form = $('<form>', {
        'method': 'POST',
        'action': '<?=base_url('library/print_labels')?>'
    });
    
    $.each(selected, function(i, val) {
        $('<input>', {
            'type': 'hidden',
            'name': 'copy_ids[]',
            'value': val
        }).appendTo(form);
    });
    
    // ========== FIXED CSRF TOKEN ==========
    var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
    $('<input>', {
        'type': 'hidden',
        'name': csrfName,
        'value': csrfHash
    }).appendTo(form);
    // ========== END CSRF FIX ==========
    
    $('body').append(form);
    form.submit();
});
    
    // Initialize count
    updateSelectedCount();
});
</script>