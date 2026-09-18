<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li>
				<a href="<?=base_url('library/book')?>"><i class="fas fa-list-ul"></i> <?=translate('books_list')?></a>
			</li>
			<li class="active">
				<a href="#update" data-toggle="tab"><i class="far fa-edit"></i> <?=translate('edit_book')?></a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="update">
				<?php echo form_open_multipart($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered frm-submit-data'));?>
				<input type="hidden" name="book_id" value="<?=$book['id']?>" >
					<?php if (is_superadmin_loggedin()): ?>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('branch')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
								$arrayBranch = $this->app_lib->getSelectList('branch');
								echo form_dropdown("branch_id", $arrayBranch, $book['branch_id'], "class='form-control' id='branch_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
					<?php endif; ?>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('book_title')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="book_title"  value="<?=$book['title']?>" />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('book_isbn_no')?></label>
						<div class="col-md-6"><input type="text" class="form-control" name="isbn_no" value="<?=$book['isbn_no']?>" /></div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('author')?></label>
						<div class="col-md-6"><input type="text" class="form-control" name="author" value="<?=$book['author']?>" /></div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('edition')?></label>
						<div class="col-md-6"><input type="text" class="form-control" name="edition" value="<?=$book['edition']?>" /></div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('grade_level')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<select name="grade_level" class="form-control" required>
								<option value=""><?=translate('select')?></option>
								
								<!-- ========== CBC (Competency Based Curriculum) ========== -->
								<optgroup label="<?=translate('cbc_curriculum')?>">
									<!-- Lower Primary (Grade 1-3) -->
									<option value="G1">Grade 1 (CBC)</option>
									<option value="G2">Grade 2 (CBC)</option>
									<option value="G3">Grade 3 (CBC)</option>
									
									<!-- Upper Primary (Grade 4-6) -->
									<option value="G4">Grade 4 (CBC)</option>
									<option value="G5">Grade 5 (CBC)</option>
									<option value="G6">Grade 6 (CBC)</option>
									
									<!-- Junior School (Grade 7-9) -->
									<option value="G7">Grade 7 (CBC - Junior School)</option>
									<option value="G8">Grade 8 (CBC - Junior School)</option>
									<option value="G9">Grade 9 (CBC - Junior School)</option>
									
									<!-- Senior School (Grade 10-12) -->
									<option value="G10">Grade 10 (CBC - Senior School)</option>
									<option value="G11">Grade 11 (CBC - Senior School)</option>
									<option value="G12">Grade 12 (CBC - Senior School)</option>
								</optgroup>
								
								<!-- ========== 8-4-4 System ========== -->
								<optgroup label="<?=translate('eight_four_four_curriculum')?>">
									<!-- Primary (Standard 1-8) -->
									<option value="STD1">Standard 1</option>
									<option value="STD2">Standard 2</option>
									<option value="STD3">Standard 3</option>
									<option value="STD4">Standard 4</option>
									<option value="STD5">Standard 5</option>
									<option value="STD6">Standard 6</option>
									<option value="STD7">Standard 7</option>
									<option value="STD8">Standard 8</option>
									
									<!-- Secondary (Form 1-4) -->
									<option value="F1">Form 1</option>
									<option value="F2">Form 2</option>
									<option value="F3">Form 3</option>
									<option value="F4">Form 4</option>
								</optgroup>
								
								<!-- ========== Early Childhood ========== -->
								<optgroup label="<?=translate('early_childhood')?>">
									<option value="PP1">Pre-Primary 1 (PP1)</option>
									<option value="PP2">Pre-Primary 2 (PP2)</option>
									<option value="KG">Kindergarten</option>
									<option value="NUR">Nursery</option>
									<option value="PLAY">Play Group</option>
									<option value="DAYCARE">Daycare</option>
									<option value="BABY">Baby Class</option>
								</optgroup>
							</select>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('purchase_date')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="purchase_date" value="<?=$book['purchase_date']?>" data-plugin-datepicker
							data-plugin-options='{ "todayHighlight" : true }' />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('book_category')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
								$array = $this->app_lib->getSelectByBranch('book_category', $book['branch_id']);
								echo form_dropdown("category_id", $array, $book['category_id'], "class='form-control' id='book_category_holder' data-plugin-selectTwo
								data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('publisher')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<input type="text" class="form-control" name="publisher" value="<?=$book['publisher']?>" />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('description')?></label>
						<div class="col-md-6"><input type="text" class="form-control" name="description" value="<?=$book['description']?>"/></div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('price')?> <span class="required">*</span></label>
						<div class="col-md-6">
							<input type="number" class="form-control" name="price" value="<?=$book['price']?>" />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('cover_image')?></label>
						<input type="hidden" name="old_file" value="<?=$book['cover']?>">
						<div class="col-md-6"><input type="file" name="cover_image" class="dropify" data-allowed-file-extensions="jpg png" data-default-file="<?=$this->application_model->get_book_cover_image($book['cover']);?>" /></div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"> <?=translate('total_stock')?> <span class="required">*</span></label>
						<div class="col-md-6  mb-md">
							<div data-plugin-spinner data-plugin-options='{ "value":0, "min": 0 }'>
								<div class="input-group">
									<input type="text" class="spinner-input form-control" name="total_stock" value="<?=$book['total_stock']?>" maxlength="3">
									<div class="spinner-buttons input-group-btn">
										<button type="button" class="btn btn-default spinner-up">
											<i class="fas fa-angle-up"></i>
										</button>
										<button type="button" class="btn btn-default spinner-down">
											<i class="fas fa-angle-down"></i>
										</button>
									</div>
								</div>
							</div>
							<span class="error"></span>
						</div>
					</div>
					<footer class="panel-footer">
						<div class="row">
							<div class="col-md-offset-3 col-md-2">
								<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
									<i class="fas fa-plus-circle"></i> <?=translate('update')?>
								</button>
							</div>
						</div>
					</footer>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</section>
<script type="text/javascript">
	$(document).ready(function () {
		$('#branch_id').on('change', function(){
			var branchID = $(this).val();
			$.ajax({
				url: "<?=base_url('ajax/getDataByBranch')?>",
				type: 'POST',
				data: {
					table : 'book_category',
					branch_id : branchID
				},
				success: function (data) {
					$('#book_category_holder').html(data);
				}
			});
		});
	});
</script>