<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-upload"></i> <?=translate('bulk_import_books')?></h4>
            </header>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <?=translate('csv_format_instructions')?><br>
                    <strong><?=translate('required_columns')?>:</strong> title, isbn_no, author, category, publisher, edition, purchase_date, price, total_copies
                </div>
                
                <?php echo form_open_multipart('library/bulk_import', array('class' => 'form-horizontal')); ?>
                <div class="form-group">
                    <label class="col-md-3 control-label"><?=translate('csv_file')?> <span class="required">*</span></label>
                    <div class="col-md-6">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <span class="error"></span>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-3 col-md-6">
                        <a href="<?=base_url('library/download_sample_csv')?>" class="btn btn-info">
                            <i class="fas fa-download"></i> <?=translate('download_sample_csv')?>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> <?=translate('import_books')?>
                        </button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </section>
    </div>
</div>

<script>
function downloadSampleCSV() {
    window.location.href = base_url + 'library/download_sample_csv';
}
</script>