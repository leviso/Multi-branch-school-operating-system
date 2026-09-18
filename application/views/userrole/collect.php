<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
$currency_symbol = $global_config['currency_symbol'];
$allocations = $this->fees_model->getInvoiceDetails($basic['id']);
$extINTL = extension_loaded('intl');
if ($extINTL == true) {
    $spellout = new NumberFormatter("en", NumberFormatter::SPELLOUT);
}

// Get the correct enroll_id from database for offline payment form
$current_session_id = get_session_id();
$enroll_query = $this->db->select('id')
    ->where('student_id', $basic['id'])
    ->where('session_id', $current_session_id)
    ->get('enroll');
$enroll_data = $enroll_query->row();
$student_enroll_id = $enroll_data ? $enroll_data->id : 0;

if (count($allocations)) {
    ?>
    <section class="panel">
        <div class="tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#invoice" data-toggle="tab"><i class="far fa-credit-card"></i> <?=translate('invoice')?></a>
                </li>
    <?php if ($invoice['status'] != 'unpaid'): ?>
                <li>
                    <a href="#history" data-toggle="tab"><i class="fas fa-dollar-sign"></i> <?=translate('payment_history')?></a>
                </li>
    <?php endif; ?>
    <?php if ($invoice['status'] != 'total'): ?>
                <li>
                    <a href="#collect_fees" data-toggle="tab"><i class="far fa-credit-card"></i> <?=translate('online_pay')?></a>
                </li>
            <?php if ($getOfflinePaymentsConfig == 1) { ?>
                <li>
                    <a href="#offline_payments" data-toggle="tab"><i class="far fa-credit-card"></i> <?=translate('offline_payments')?></a>
                </li>
            <?php } ?>
    <?php endif; ?>
            </ul>
            <div class="tab-content">
                <!-- INVOICE TAB -->
                <div id="invoice" class="tab-pane <?=empty($this->session->flashdata('pay_tab')) ? 'active' : ''; ?>">
                    <div id="invoice_print">
                        <div class="invoice">
                            <header class="clearfix">
                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="ib">
                                            <img src="<?=$this->application_model->getBranchImage($basic['branch_id'], 'printing-logo')?>" alt="School Logo" />
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <h4 class="mt-none mb-none text-dark">Invoice No #<?=$invoice['invoice_no']?></h4>
                                        <p class="mb-none">
                                            <span class="text-dark"><?=translate('date')?> : </span>
                                            <span class="value"><?=_d(date('Y-m-d'))?></span>
                                        </p>
                                        <p class="mb-none">
                                            <span class="text-dark"><?=translate('status')?> : </span>
                                            <?php
                                                $labelmode = '';
                                                $status = translate('pending'); // DEFAULT VALUE - FIXES THE ERROR
                                                
                                                if($invoice['status'] == 'unpaid') {
                                                    $status = translate('unpaid');
                                                    $labelmode = 'label-danger-custom';
                                                } elseif($invoice['status'] == 'partly') {
                                                    $status = translate('partly_paid');
                                                    $labelmode = 'label-info-custom';
                                                } elseif($invoice['status'] == 'total') {
                                                    $status = translate('total_paid');
                                                    $labelmode = 'label-success-custom';
                                                }
                                                echo "<span class='value label " . $labelmode . " '>" . $status . "</span>";
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </header>
                            <div class="bill-info">
                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="bill-data">
                                            <p class="h5 mb-xs text-dark text-weight-semibold">Invoice To :</p>
                                            <address>
                                                <?php 
                                                echo $basic['first_name'] . ' ' . $basic['last_name'] . '<br>';
                                                echo $basic['student_address'] . '<br>';
                                                echo translate('class') . ' : ' . $basic['class_name'] . '<br>';
                                                echo translate('email') . ' : ' . $basic['student_email']; 
                                                ?>
                                            </address>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="bill-data text-right">
                                            <p class="h5 mb-xs text-dark text-weight-semibold">Academic :</p>
                                            <address>
                                                <?php 
                                                echo $basic['school_name'] . "<br/>";
                                                echo $basic['school_address'] . "<br/>";
                                                echo $basic['school_mobileno'] . "<br/>";
                                                echo $basic['school_email'] . "<br/>";
                                                ?>
                                            </address>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive br-none">
                                <table class="table invoice-items table-hover mb-none">
                                    <thead>
                                        <tr class="text-dark">
                                            <th id="cell-id" class="text-weight-semibold">#</th>
                                            <th id="cell-item" class="text-weight-semibold"><?=translate("fees_type")?></th>
                                            <th id="cell-id" class="text-weight-semibold"><?=translate("due_date")?></th>
                                            <th id="cell-price" class="text-weight-semibold"><?=translate("status")?></th>
                                            <th id="cell-price" class="text-weight-semibold"><?=translate("amount")?></th>
                                            <th id="cell-price" class="text-weight-semibold"><?=translate("discount")?></th>
                                            <th id="cell-price" class="text-weight-semibold"><?=translate("fine")?></th>
                                            <th id="cell-price" class="text-weight-semibold"><?=translate("paid")?></th>
                                            <th id="cell-total" class="text-center text-weight-semibold"><?=translate("balance")?></th>
                                            </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $group = array();
                                            $count = 1;
                                            $total_fine = 0;
                                            $total_discount = 0;
                                            $total_paid = 0;
                                            $total_balance = 0;
                                            $total_amount = 0;
                                            $typeData = array('' => translate('select'));
                                            foreach ($allocations as $row) {
                                                $deposit = $this->fees_model->getStudentFeeDeposit($row['allocation_id'], $row['fee_type_id']);
                                                $type_discount = $deposit['total_discount'];
                                                $type_fine = $deposit['total_fine'];
                                                $type_amount = $deposit['total_amount'];
                                                $balance = $row['amount'] - ($type_amount + $type_discount);
                                                $total_discount += $type_discount;
                                                $total_fine += $type_fine;
                                                $total_paid += $type_amount;
                                                $total_balance += $balance;
                                                $total_amount += $row['amount'];
                                                if ($balance != 0) {
                                                    $typeData[$row['allocation_id'] . "|" . $row['fee_type_id']] = $row['name'];
                                                }
                                            ?>
                                        <?php if(!in_array($row['group_id'], $group)) { 
                                            $group[] = $row['group_id'];
                                            ?>
                                            <tr>
                                            <td class="group" colspan="9"><strong><?php echo get_type_name_by_id('fee_groups', $row['group_id']) ?></strong><img class="group" src="<?php echo base_url('assets/images/arrow.png') ?>"></td>
                                            </tr>
                                        <?php } ?>
                                            <tr>
                                                <td><?php echo $count++;?></td>
                                            <td class="text-dark"><?=$row['name']?></td>
                                                <td><?=_d($row['due_date'])?></td>
                                                <td><?php 
                                                $status = 0;
                                                $labelmode = '';
                                                if($type_amount == 0) {
                                                    $status = translate('unpaid');
                                                    $labelmode = 'label-danger-custom';
                                                } elseif($balance == 0) {
                                                    $status = translate('total_paid');
                                                    $labelmode = 'label-success-custom';
                                                } else {
                                                    $status = translate('partly_paid');
                                                    $labelmode = 'label-info-custom';
                                                }
                                                echo "<span class='label ".$labelmode." '>".$status."</span>";
                                            ?></td>
                                                <td><?php echo $currency_symbol . $row['amount'];?></td>
                                                <td><?php echo $currency_symbol . $type_discount;?></td>
                                                <td><?php echo $currency_symbol . $type_fine;?></td>
                                                <td><?php echo $currency_symbol . $type_amount;?></td>
                                            <td class="text-center"><?php echo $currency_symbol . number_format($balance, 2, '.', '');?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="invoice-summary text-right mt-lg">
                                <div class="row">
                                    <div class="col-md-5 col-xs-12 pull-right">
                                        <ul class="amounts">
                                            <li><strong><?=translate('grand_total')?> :</strong> <?=$currency_symbol . number_format($total_amount, 2, '.', ''); ?></li>
                                            <li><strong><?=translate('paid')?> :</strong> <?=$currency_symbol . number_format($total_paid, 2, '.', ''); ?></li>
                                            <li><strong><?=translate('discount')?> :</strong> <?=$currency_symbol . number_format($total_discount, 2, '.', ''); ?></li>
                                            <li><strong><?=translate('fine')?> :</strong> <?=$currency_symbol . number_format($total_fine, 2, '.', ''); ?></li>
                                            <?php if ($total_balance != 0): ?>
                                            <li>
                                                <strong><?=translate('balance')?> : </strong> 
                                                <?php
                                                $numberSPELL = "";
                                                if ($extINTL == true) {
                                                    $numberSPELL = ' </br>( ' . ucwords($spellout->format($total_balance)) . ' )';
                                                }
                                                echo $currency_symbol . number_format($total_balance, 2, '.', '') . $numberSPELL;
                                                ?>
                                            </li>
                                            <?php else: ?>
                                            <li>
                                                <strong><?=translate('total_paid')?> : </strong> 
                                                <?php
                                                $numberSPELL = "";
                                                if ($extINTL == true) {
                                                    $numberSPELL = ' </br>( ' . ucwords($spellout->format(($total_paid + $total_fine))) . ' )';
                                                }
                                                echo $currency_symbol . number_format(($total_paid + $total_fine), 2, '.', '') . $numberSPELL;
                                                ?>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right mr-lg hidden-print">
                            <button onClick="fn_printElem('invoice_print')" class="btn btn-default ml-sm"><i class="fas fa-print"></i> <?=translate('print')?></button>
                        </div>
                    </div>
                </div>
                
                <!-- PAYMENT HISTORY TAB -->
                <?php if ($invoice['status'] != 'unpaid'): ?>
                <div class="tab-pane" id="history">
                    <div id="payment_print">
                        <div class="invoice payment">
                            <header class="clearfix">
                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="ib">
                                            <img src="<?=$this->application_model->getBranchImage($basic['branch_id'], 'printing-logo')?>" alt="School Logo" />
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <h4 class="mt-none mb-none text-dark">Invoice No #<?= $invoice['invoice_no']?></h4>
                                        <p class="mb-none">
                                            <span class="text-dark"><?=translate('date')?> : </span>
                                            <span class="value"><?php echo _d(date('Y-m-d'));?></span>
                                        </p>
                                        <p class="mb-none">
                                            <span class="text-dark"><?=translate('status')?> : </span>
                                            <?php
                                                $labelmode = '';
                                                if($invoice['status'] == 'unpaid') {
                                                    $status = translate('unpaid');
                                                    $labelmode = 'label-danger-custom';
                                                } elseif($invoice['status'] == 'partly') {
                                                    $status = translate('partly_paid');
                                                    $labelmode = 'label-info-custom';
                                                } elseif($invoice['status'] == 'total') {
                                                    $status = translate('total_paid');
                                                    $labelmode = 'label-success-custom';
                                                }
                                                echo "<span class='value label ".$labelmode." '>".$status."</span>";
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </header>
                            <div class="bill-info">
                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="bill-data">
                                            <p class="h5 mb-xs text-dark text-weight-semibold">Invoice To :</p>
                                            <address>
                                                <?php 
                                                echo $basic['first_name'] . ' '. $basic['last_name'] . '<br>';
                                                echo $basic['student_address'] . '<br>';
                                                echo translate('class').' : '. $basic['class_name'] . '<br>';
                                                echo translate('email').' : '. $basic['student_email']; 
                                                ?>
                                            </address>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="bill-data text-right">
                                            <p class="h5 mb-xs text-dark text-weight-semibold">Academic :</p>
                                            <address>
                                                <?php 
                                                echo $basic['school_name'] . "<br/>";
                                                echo $basic['school_address'] . "<br/>";
                                                echo $basic['school_mobileno'] . "<br/>";
                                                echo $basic['school_email'] . "<br/>";
                                                ?>
                                            </address>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive br-none">
                                <table class="table invoice-items">
                                    <thead>
                                        <tr class="h5 text-dark">
                                            <th id="cell-item" class="text-weight-semibold"><?=translate('fees_type')?></th>
                                            <th id="cell-item" class="text-weight-semibold"><?=translate('fees_code')?></th>
                                            <th id="cell-item" class="text-weight-semibold"><?=translate('date')?></th>
                                            <th id="cell-desc" class="text-weight-semibold"><?=translate('remarks')?></th>
                                            <th id="cell-qty" class="text-weight-semibold"><?=translate('method')?></th>
                                            <th id="cell-price" class="text-weight-semibold"><?=translate('amount')?></th>
                                            <th id="cell-price" class="text-weight-semibold"><?=translate('discount')?></th>
                                            <th id="cell-price" class="text-weight-semibold"><?=translate('fine')?></th>
                                            <th id="cell-price" class="text-weight-semibold"><?=translate('paid')?></th>
                                            </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $allocations = $this->db->where(array('student_id' => $basic['id'], 'session_id' => get_session_id()))->get('fee_allocation')->result_array();
                                        foreach ($allocations as $allRow) {
                                            $historys = $this->fees_model->getPaymentHistory($allRow['id'], $allRow['group_id']);
                                            foreach ($historys as $row) {
                                        ?>
                                            <tr>
                                            <td class="text-weight-semibold text-dark"><?=$row['name']?></td>
                                                <td><?=$row['fee_code']?></td>
                                                <td><?=_d($row['date'])?></td>
                                                <td><?=$row['remarks']?></td>
                                                <td><?=$row['payvia']?></td>
                                                <td><?=$currency_symbol . $row['amount']?></td>
                                                <td><?=$currency_symbol . $row['discount']?></td>
                                                <td><?=$currency_symbol . $row['fine']?></td>
                                                <td><?=$currency_symbol . $row['amount']?></td>
                                                </tr>
                                         <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="invoice-summary text-right mt-lg">
                                <div class="row">
                                    <div class="col-lg-5 pull-right">
                                        <ul class="amounts">
                                            <li><strong><?=translate('grand_total')?> :</strong> <?=$currency_symbol . number_format($total_amount, 2, '.', ''); ?></li>
                                            <li><strong><?=translate('paid')?> :</strong> <?=$currency_symbol . number_format($total_paid, 2, '.', ''); ?></li>
                                            <li><strong><?=translate('discount')?> :</strong> <?=$currency_symbol . number_format($total_discount, 2, '.', ''); ?></li>
                                            <li><strong><?=translate('fine')?> :</strong> <?=$currency_symbol . number_format($total_fine, 2, '.', ''); ?></li>
                                            <?php if ($total_balance != 0): ?>
                                            <li>
                                                <strong><?=translate('balance')?> : </strong> 
                                                <?php
                                                $numberSPELL = "";
                                                if ($extINTL == true) {
                                                    $numberSPELL = ' </br>( ' . ucwords($spellout->format($total_balance)) . ' )';
                                                }
                                                echo $currency_symbol . number_format($total_balance, 2, '.', '') . $numberSPELL;
                                                ?>
                                            </li>
                                            <?php else: ?>
                                            <li>
                                                <strong><?=translate('total_paid')?> : </strong> 
                                                <?php
                                                $numberSPELL = "";
                                                if ($extINTL == true) {
                                                    $numberSPELL = ' </br>( ' . ucwords($spellout->format(($total_paid + $total_fine))) . ' )';
                                                }
                                                echo $currency_symbol . number_format(($total_paid + $total_fine), 2, '.', '') . $numberSPELL;
                                                ?>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right mr-lg hidden-print">
                            <button onClick="fn_printElem('payment_print')" class="btn btn-default"><i class="fas fa-print"></i> <?=translate('print')?></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- ONLINE PAY TAB - ONLY KENYAN PAYMENT METHODS -->
                <?php if($invoice['status'] != 'total'): ?>
                    <div id="collect_fees" class="tab-pane">
                        <div class="mb-xlg">
                          <?php echo form_open('feespayment/checkout', array('id' => 'onlinePayForm', 'method' => 'post')); ?>
                                <input type="hidden" name="invoice_no" value="<?=$invoice['invoice_no']?>">
                                
                                <div class="form-group">
                                    <label class="col-md-3 control-label"><?=translate('fees_type')?> <span class="required">*</span></label>
                                    <div class="col-md-6">
                                        <?php
                                            echo form_dropdown("fees_type", $typeData, set_value('fees_type'), "class='form-control' onchange='getBalanceByType(this)' 
                                            data-plugin-selectTwo data-width='100%' id='fees_type_select'");
                                        ?>
                                        <span class="error"></span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="col-md-3 control-label"><?=translate('amount')?> <span class="required">*</span></label>
                                    <div class="col-md-6">
                                        <input type="number" class="form-control" name="fee_amount" id="feeAmount" value="" autocomplete="off" step="0.01" min="1" />
                                        <span class="help-block">Enter amount to pay (minimum KES 1). Balance: <strong id="balanceDisplay">0.00</strong></span>
                                        <span class="error"></span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="col-md-3 control-label"><?=translate('fine')?></label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="fine_amount" id="fineAmount" value="0" autocomplete="off" readonly />
                                        <span class="error"></span>
                                    </div>
                                </div>
                                
                                <!-- Payment Method Dropdown - Only Kenyan Methods -->
                                <div class="form-group">
                                    <label class="col-md-3 control-label"><?=translate('payment_method')?> <span class="required">*</span></label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="pay_via" id="pay_via" data-plugin-selectTwo data-width="100%">
                                            <option value=""><?=translate('select_payment_method')?></option>
                                            <option value="mpesa_stk">M-Pesa STK Push (Lipa Na M-Pesa)</option>
                                            <option value="mpesa_paybill">M-Pesa Paybill (Buy Goods)</option>
                                            <option value="pesapal">Pesapal (Card / Bank / M-Pesa)</option>
                                        </select>
                                        <span class="error"></span>
                                    </div>
                                </div>
                                
                                <!-- M-Pesa Phone Field -->
                                <div class="form-group" id="mpesa_phone_group" style="display: none;">
                                    <label class="col-md-3 control-label">M-Pesa Phone Number <span class="required">*</span></label>
                                    <div class="col-md-6">
                                       <input type="tel" class="form-control" name="mpesa_phone" id="mpesa_phone" placeholder="0712345678 or 254712345678" />
                                        <span class="help-block">Enter the M-Pesa registered phone number (e.g., 2547XXXXXXXX)</span>
                                        <span class="error"></span>
                                    </div>
                                </div>
                                
                                <!-- Pesapal Email Field -->
                                <div class="form-group" id="pesapal_email_group" style="display: none;">
                                    <label class="col-md-3 control-label">Email Address <span class="required">*</span></label>
                                    <div class="col-md-6">
                                        <input type="email" class="form-control" name="pesapal_email" id="pesapal_email" value="<?php echo $basic['student_email']; ?>" />
                                        <span class="help-block">Receipt will be sent to this email address</span>
                                        <span class="error"></span>
                                    </div>
                                </div>
                                
                                <footer class="panel-footer">
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-3">
                                            <button type="submit" class="btn btn-default" id="payNowBtn">
                                                <i class="fas fa-credit-card"></i> <?=translate('fees_pay_now')?>
                                            </button>
                                        </div>
                                    </div>
                                </footer>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- OFFLINE PAYMENTS TAB - FIXED VERSION -->
                <?php if ($getOfflinePaymentsConfig == 1 && $invoice['status'] != 'total') { ?>
                <div id="offline_payments" class="tab-pane">
                    <div class="mb-xlg">
                        <section class="panel pg-fw">
                            <div class="panel-body">
                                <h5 class="chart-title mb-xs"><i class="fas fa-credit-card"></i> <?=translate('payment')?></h5>
                                <div class="mt-lg">
                                   <?php echo form_open_multipart('userrole/offline_payments', array('class' => 'form-horizontal frm-submit-data' )); ?>
                                        <input type="hidden" class="form-control" name="fine_amount" value="0" readonly />
                                        <input type="hidden" name="invoice_no" value="<?=$invoice['invoice_no']?>">
                                        <!-- FIXED: Added hidden fields for branch_id and student_enroll_id -->
                                        <input type="hidden" name="branch_id" value="<?=$basic['branch_id']?>">
                                        <input type="hidden" name="student_enroll_id" value="<?=$student_enroll_id?>">
                                        <input type="hidden" name="student_id" value="<?=$basic['id']?>">
                                        
                                        <div class="form-group">
                                            <label class="col-md-3 control-label"><?=translate('fees_type')?> <span class="required">*</span></label>
                                            <div class="col-md-5">
                                            <?php
                                                echo form_dropdown("fees_type", $typeData, set_value('fees_type'), "class='form-control' onchange='getBalanceByType(this)'
                                                data-plugin-selectTwo data-width='100%' ");
                                            ?>
                                            <span class="error"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label"><?=translate('payment_method')?> <span class="required">*</span></label>
                                            <div class="col-md-5">
                                                <?php
                                                    $payvia_list = array('' => translate('select_payment_method'));
                                                    $paymentTypes = $this->db->where('branch_id', $basic['branch_id'])->get('offline_payment_types')->result();
                                                    foreach ($paymentTypes as $key => $value) {
                                                        $payvia_list[$value->id] = $value->name;
                                                    }
                                                    echo form_dropdown("payment_method", $payvia_list, set_value('payment_method'), "class='form-control' data-plugin-selectTwo data-width='100%' id='paymentMethod'
                                                    data-minimum-results-for-search='Infinity' ");
                                                ?>
                                                <span class="error"></span>
                                            </div>
                                        </div>
                                        <div class="form-group hidden-div" id="instructionDiv" style="display:none;">
                                            <label class="col-md-3 control-label"><?=translate('instructions')?></label>
                                            <div class="col-md-5">
                                                <div class="alert alert-info mb-none" id="instruction"></div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label"><?=translate('date_of_payment')?> <span class="required">*</span></label>
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" name="date_of_payment" data-plugin-datepicker data-plugin-options='{ "todayHighlight" : true }' />
                                                <span class="error"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label"><?=translate('amount')?> <span class="required">*</span></label>
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" name="fee_amount" id="feeAmountOffline" value="" />
                                                <span class="error"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label"><?=translate('reference')?></label>
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" name="reference" />
                                                <span class="error"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label"><?=translate('note')?> <span class="required">*</span></label>
                                            <div class="col-md-5">
                                                <textarea class="form-control" name="note" rows="3"></textarea>
                                                <span class="error"></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label"><?php echo translate('proof_of_payment');?></label>
                                            <div class="col-md-4">
                                                <input type="file" name="proof_of_payment" class="dropify" data-height="150" />
                                                <span class="error"></span>
                                            </div>
                                        </div>
                                        <div class="form-group mb-lg">
                                            <div class="col-md-offset-3 col-md-3">
                                                <button type="submit" class="btn btn-default">
                                                    <i class="fas fa-credit-card"></i> <?=translate('pay')?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php echo form_close();?>    
                                </div>
                            </div>
                        </section>
                        <section class="panel pg-fw">
                            <div class="panel-body">
                                <h5 class="chart-title mb-xs"><?=translate('offline_payments')?></h5>
                                <div class="mt-lg">
                                    <table class="table table-bordered table-condensed table-hover mb-none tbr-top table-export">
                                        <thead>
                                            <tr>
                                                <th><?=translate('trx_id')?></th>
                                                <th><?=translate('student')?></th>
                                                <th><?=translate('payment_date')?></th>
                                                <th><?=translate('submit_date')?></th>
                                                <th><?=translate('amount')?></th>
                                                <th><?=translate('status')?></th>
                                                <th><?=translate('action')?></th>
                                                </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $paymentslist = $this->userrole_model->getOfflinePaymentsList();
                                            foreach($paymentslist as $row):
                                                ?>
                                                    <tr>
                                                        <td><?php echo $row->id;?></td>
                                                        <td><?php echo $row->fullname;?></td>
                                                        <td><?php echo _d($row->payment_date);?></td>
                                                        <td><?php echo _d($row->submit_date);?></td>
                                                        <td><?php echo $currency_symbol . $row->amount;?></td>
                                                        <td>
                                                            <?php
                                                                $labelmode = '';
                                                                $status = $row->status;
                                                                if($status == 1) {
                                                                    $status = translate('pending');
                                                                    $labelmode = 'label-info-custom';
                                                                } elseif($status == 2) {
                                                                    $status = translate('approved');
                                                                    $labelmode = 'label-success-custom';
                                                                } elseif($status == 3) {
                                                                    $status = translate('suspended');
                                                                    $labelmode = 'label-danger-custom';
                                                                }
                                                                echo "<span class='value label " . $labelmode . " '>" . $status . "</span>";
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getApprovelOfflinePayments('<?=$row->id ?>')">
                                                                <i class="fas fa-bars"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
<?php }else{ ?>
    <section class="panel">
        <div class="panel-body">
            <div class="alert alert-subl mb-none text-center">
                <i class="fas fa-exclamation-triangle"></i> <?=translate('no_fees_have_been_allocated')?>
            </div>
        </div>
    </section>
<?php } ?>

<?php if ($getOfflinePaymentsConfig == 1) { ?>
<div class="zoom-anim-dialog modal-block modal-block-lg mfp-hide" id="modal">
    <section class="panel" id='quick_view'></section>
</div>

<script type="text/javascript">
    function getApprovelOfflinePayments(id) {
        $.ajax({
            url: base_url + 'userrole/getOfflinePaymentslDetails',
            type: 'POST',
            data: {'id': id},
            dataType: "html",
            success: function (data) {
                $('#quick_view').html(data);
                mfp_modal('#modal');
            }
        });
    }
</script>
<?php } ?>

<script type="text/javascript">
var base_url = '<?php echo base_url(); ?>';
var studentID = '<?php echo $basic['id']; ?>';

function getBalanceByType(sel) {
    var typeID = $(sel).val();
    $.ajax({
        url: base_url + 'userrole/getBalanceByType',
        type: 'POST',
        data: {'typeID': typeID},
        dataType: "json",
        success: function (data) {
            var balance = parseFloat(data.balance).toFixed(2);
            $('#feeAmount').val(balance);
            $('#fineAmount').val(data.fine.toFixed(2));
            $('#balanceDisplay').text(balance);
            $('#feeAmountOffline').val(balance);
            $('#feeAmount').attr('max', balance);
        }
    });
}

$('#paymentMethod').on("change", function(){
    var typeID = $(this).val();
    $.ajax({
        url: base_url + 'offline_payments/getTypeInstruction',
        type: 'POST',
        data: {'typeID': typeID},
        dataType: "html",
        success: function (str) {
            if (!str || str.length === 0) {
                $('#instructionDiv').hide(500);
            } else {
                $('#instruction').html(str);
                $('#instructionDiv').show(500);
            }
        }
    });
});

$(document).ready(function () {
    // Toggle payment method fields
    $('#pay_via').on('change', function() {
        var method = $(this).val();
        $('#mpesa_phone_group').hide();
        $('#pesapal_email_group').hide();
        
        if (method == 'mpesa_stk' || method == 'mpesa_paybill') {
            $('#mpesa_phone_group').show();
        } else if (method == 'pesapal') {
            $('#pesapal_email_group').show();
        }
    });
    
    // Form submission handler - NORMAL POST (not AJAX)
   $('#onlinePayForm').on('submit', function(e) {
    var paymentMethod = $('#pay_via').val();
    
    // Validate payment method
    if (!paymentMethod) {
        e.preventDefault();
        swal('Error', 'Please select a payment method', 'error');
        return false;
    }
    
    // M-Pesa STK Push - Use AJAX to handle redirect
    // M-Pesa STK Push
// M-Pesa STK Push
if (paymentMethod == 'mpesa_stk') {
    var phone = $('#mpesa_phone').val();
    console.log('Raw phone input:', phone);
    
    if (!phone) {
        e.preventDefault();
        swal('Error', 'Please enter M-Pesa phone number', 'error');
        return false;
    }
    
    // Format phone number
    var formattedPhone = phone.replace(/[^0-9]/g, '');
    console.log('After removing non-digits:', formattedPhone);
    
    if (formattedPhone.length == 10 && formattedPhone.charAt(0) == '0') {
        formattedPhone = '254' + formattedPhone.substr(1);
        console.log('After 0 conversion:', formattedPhone);
    } else if (formattedPhone.length == 9 && formattedPhone.charAt(0) == '7') {
        formattedPhone = '254' + formattedPhone;
        console.log('After 7 conversion:', formattedPhone);
    } else if (formattedPhone.length == 12 && formattedPhone.substring(0, 3) == '254') {
        formattedPhone = formattedPhone;
        console.log('Already correct:', formattedPhone);
    } else {
        console.log('Invalid length:', formattedPhone.length);
        swal('Error', 'Invalid phone number. Please use 0712345678 or 254712345678', 'error');
        $('#payNowBtn').prop('disabled', false).html('<i class="fas fa-credit-card"></i> <?=translate('fees_pay_now')?>');
        return false;
    }
    
    console.log('Final phone being sent:', formattedPhone);
       
    // Show loading
    $('#payNowBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    
    // Get form data and add formatted phone
    var formData = $(this).serialize();
    formData += '&mpesa_phone=' + formattedPhone;
    
    // Submit via AJAX
$.ajax({
    url: base_url + 'feespayment/checkout',
    type: 'POST',
    data: formData,
    dataType: 'json',
    success: function(response) {
        console.log('Response received:', response);
        
        // Handle when checkout returns redirect URL directly
        if (response.status === 'success' && response.url) {
            window.location.href = response.url;
        }
        // Handle when initiate_mpesa_stk returns the transaction data
        else if (response.success === true && response.checkout_request_id) {
            // Redirect to waiting page
            window.location.href = base_url + 'feespayment/mpesa_waiting/' + response.checkout_request_id + '/' + response.transaction_id;
        }
        else if (response.status === 'fail' || response.success === false) {
            $('#payNowBtn').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Pay Now');
            if (response.error) {
                $.each(response.error, function(key, value) {
                    $('[name="' + key + '"]').parent().find('.error').html('<span class="text-danger">' + value + '</span>');
                });
            } else {
                swal('Error', response.message || 'Payment failed', 'error');
            }
        }
    },
    error: function(xhr, status, error) {
        console.error('AJAX Error:', error);
        console.error('Response:', xhr.responseText);
        $('#payNowBtn').prop('disabled', false).html('<i class="fas fa-credit-card"></i> <?=translate('fees_pay_now')?>');
        swal('Error', 'Network error. Please try again.', 'error');
    }
});
    
    return false;
}
    
    // M-Pesa Paybill (Manual) - Keep as is
    if (paymentMethod == 'mpesa_paybill') {
        e.preventDefault();
        var phone = $('#mpesa_phone').val();
        var amount = $('#feeAmount').val();
        
        swal({
            title: "M-Pesa Paybill Payment",
            text: "Paybill Number: 174379\nAccount Number: " + studentID + "\n\nAmount: KES " + amount + "\n\n1. Go to M-Pesa menu\n2. Select Lipa Na M-Pesa\n3. Select Paybill\n4. Enter Business No: 174379\n5. Enter Account No: " + studentID + "\n6. Enter Amount: KES " + amount + "\n7. Enter PIN\n8. Confirm",
            type: "info",
            confirmButtonText: "OK"
        });
        return false;
    }
    
    // Pesapal - Keep as is (normal form submission)
    if (paymentMethod == 'pesapal') {
        var email = $('#pesapal_email').val();
        if (!email) {
            e.preventDefault();
            swal('Error', 'Please enter email address', 'error');
            return false;
        }
        
        $('#onlinePayForm').attr('action', base_url + 'feespayment/checkout');
        $('#onlinePayForm').attr('method', 'POST');
        $('#payNowBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Redirecting...');
        
        return true;
    }
});
    
    // Trigger change on page load
    $('#pay_via').trigger('change');
});
</script>