<?php $widget = (is_superadmin_loggedin() ? 3 : 4); ?>
<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <div class="panel-heading">
                <div class="panel-btn">
                    <a href="javascript:void(0);" onclick="mfp_modal('#multipleImport')" class="btn btn-circle btn-default mb-sm">
                        <i class="fas fa-plus-circle"></i> <?=translate('multiple_import')?>
                    </a>
                </div>
                <h4 class="panel-title">
                    <i class="far fa-user-circle"></i> <?=translate('add_parent')?>
                </h4>
            </div>
            <?php echo form_open_multipart($this->uri->uri_string()); ?>
            <!-- Your existing form content here -->
            <?php echo form_close(); ?>
        </section>
    </div>
</div>

<!-- multiple import modal -->
<div id="multipleImport" class="zoom-anim-dialog modal-block modal-block-lg mfp-hide">
    <section class="panel">
        <div class="panel-heading">
            <h4 class="panel-title"><i class="fas fa-plus-circle"></i> <?php echo translate('multiple_import'); ?></h4>
        </div>
        <?php echo form_open_multipart('parents/csv_import', array('class' => 'form-horizontal', 'id' => 'importCSV')); ?>
            <div class="panel-body">
                <div class="alert-danger" id="errorList" style="display: none; padding: 8px;"></div>
                <div class="form-group mt-md">
                    <div class="col-md-12 mb-md">
                        <a class="btn btn-default pull-right" href="<?=base_url('parents/csv_Sampledownloader')?>">
                            <i class='fas fa-file-download'></i> <?=translate('download_sample_file')?>
                        </a>
                    </div>
                    <div class="col-md-12">
                        <div class="alert alert-subl">
                            <strong><?=translate('instructions')?> :</strong><br/>
                            1. <?=translate('download_sample_file_first')?><br/>
                            2. <?=translate('fill_parent_details_carefully')?><br/>
                            3. <?=translate('required_fields', 'Name, Relation, MobileNo, Email, Username, Password')?><br/>
                        </div>
                    </div>
                </div>
<?php if (is_superadmin_loggedin()) { ?>
                <div class="form-group">
                    <label class="col-md-3 control-label"><?=translate('branch')?> <span class="required">*</span></label>
                    <div class="col-md-9">
                        <?php
                            $arrayBranch = $this->app_lib->getSelectList('branch');
                            echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' id='branchID_mod'
                            data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
                        ?>
                        <span class="error"></span>
                    </div>
                </div>
<?php } ?>
                <div class="form-group mb-xs">
                    <label class="control-label col-md-3"><?=translate('select_csv_file')?> <span class="required">*</span></label>
                    <div class="col-md-9">
                        <input type="file" name="userfile" class="dropify" data-height="70" data-allowed-file-extensions="csv" />
                        <span class="error"></span>
                    </div>
                </div>
            </div>
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-default mr-xs" id="importBtn" data-loading-text="<i class='fas fa-spinner fa-spin'></i> <?=translate('processing')?>">
                            <i class="fas fa-plus-circle"></i> <?php echo translate('import'); ?>
                        </button>
                        <button class="btn btn-default modal-dismiss"><?php echo translate('close'); ?></button>
                    </div>
                </div>
            </footer>
        <?php echo form_close(); ?>
    </section>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#importCSV').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            
            $('#importBtn').button('loading');
            $('#errorList').hide().html('');
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#importBtn').button('reset');
                    if (response.status == 'success') {
                        toastr.success(response.message);
                        setTimeout(function() {
                            $.magnificPopup.close();
                            location.reload();
                        }, 2000);
                    } else if (response.status == 'partial') {
                        $('#errorList').html('<strong>Partial Import:</strong><br>' + response.message).show();
                        toastr.warning('Import completed with errors');
                    } else {
                        $('#errorList').html('<strong>Error:</strong> ' + response.message).show();
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    $('#importBtn').button('reset');
                    $('#errorList').html('Server error occurred. Please try again.').show();
                    toastr.error('Server error occurred');
                }
            });
        });
    });
</script>