<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 1.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Feespayment.php
 * @copyright : Reserved Synobix Team
 */

class Feespayment extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('feespayment_model');
        $this->load->model('userrole_model');
        $this->load->model('fees_model');
        $this->load->library('paypal_payment');
        $this->load->library('stripe_payment');
        $this->load->library('razorpay_payment');
        $this->load->library('sslcommerz');
        $this->load->library('midtrans_payment');
        $this->load->library('paytm_kit_lib');
    }

    public function index()
    {
        if (is_student_loggedin() || is_parent_loggedin()) {
            redirect(base_url('userrole/invoice'), 'refresh');
        } else {
            redirect(base_url(), 'refresh');
        }
    }

   public function checkout()
{
    if (!is_student_loggedin() && !is_parent_loggedin()) {
        ajax_access_denied();
    }
    
    if ($_POST) {
        $payVia = $this->input->post('pay_via');
        
        // ========== PERMISSION CHECKS FOR ONLINE PAYMENTS ==========
        // Only check permissions for admin/accountant roles (not students/parents)
        $is_staff_user = (is_admin_loggedin() || is_accountant_loggedin());
        
        if ($is_staff_user) {
            if (!get_permission('collect_fees', 'is_add')) {
                $array = array('status' => 'fail', 'error' => array('general' => 'You do not have permission to collect fees'));
                echo json_encode($array);
                return;
            }
            
            // Check specific payment gateway permissions for staff
            if ($payVia == 'mpesa_stk' && !get_permission('mpesa_stk', 'is_add')) {
                $array = array('status' => 'fail', 'error' => array('general' => 'M-Pesa STK payment not available'));
                echo json_encode($array);
                return;
            }
            
            if ($payVia == 'mpesa_paybill' && !get_permission('mpesa_paybill', 'is_add')) {
                $array = array('status' => 'fail', 'error' => array('general' => 'M-Pesa Paybill not available'));
                echo json_encode($array);
                return;
            }
            
            if ($payVia == 'pesapal' && !get_permission('pesapal', 'is_add')) {
                $array = array('status' => 'fail', 'error' => array('general' => 'Pesapal payment not available'));
                echo json_encode($array);
                return;
            }
        }
        // ========== END PERMISSION CHECKS ==========
        
        $this->form_validation->set_rules('fees_type', translate('fees_type'), 'trim|required');
        $this->form_validation->set_rules('fee_amount', translate('amount'), array('trim', 'required', 'numeric', 'greater_than[0]', array('deposit_verify', array($this->fees_model, 'depositAmountVerify'))));
        $this->form_validation->set_rules('pay_via', translate('payment_method'), 'trim|required');
        
        // M-Pesa validation
        if ($payVia == 'mpesa_stk' || $payVia == 'mpesa_paybill') {
            $this->form_validation->set_rules('mpesa_phone', 'M-Pesa Phone Number', 'trim|required|regex_match[/^[0-9]{9,12}$/]');
        }
        
        // Pesapal validation
        if ($payVia == 'pesapal') {
            $this->form_validation->set_rules('pesapal_email', 'Email Address', 'trim|required|valid_email');
        }
        
        if ($this->form_validation->run() !== false) {
            $stu = $this->userrole_model->getStudentDetails();
            $feesType = explode("|", $this->input->post('fees_type'));
            
            $params = array(
            'student_id' => $stu['student_id'],
            'student_name' => $stu['fullname'],
            'student_email' => $stu['student_email'],
            'student_mobile' => $stu['mobileno'],
            'invoice_no' => $this->input->post('invoice_no'),
            'allocation_id' => $feesType[0],
            'type_id' => $feesType[1],
            'amount' => $this->input->post('fee_amount'),
            'fine' => $this->input->post('fine_amount'),
            'currency' => $this->data['global_config']['currency'],
            'branch_id' => get_loggedin_branch_id(),
        );

        // ========== ADD PHONE TO PARAMS HERE ==========
        if ($payVia == 'mpesa_stk' || $payVia == 'mpesa_paybill') {
            $phone = $this->input->post('mpesa_phone');
            
            // Format phone number
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
                $phone = '254' . substr($phone, 1);
            } elseif (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
                $phone = '254' . $phone;
            }
            
            $params['phone'] = $phone;
        }
        // ========== END PHONE ADD ==========

        // Store in session
        $this->session->set_userdata("payment_params", $params);

            // Determine redirect URL based on payment method
            if ($payVia == 'mpesa_stk') {
                $url = base_url("feespayment/initiate_mpesa_stk");
            } elseif ($payVia == 'mpesa_paybill') {
                $url = base_url("feespayment/mpesa_paybill_instructions");
            } elseif ($payVia == 'pesapal') {
                $url = base_url("feespayment/initiate_pesapal");
            } else {
                $url = base_url("userrole/invoice");
            }
            
            $array = array('status' => 'success', 'url' => $url);
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'url' => '', 'error' => $error);
        }
        echo json_encode($array);
    }
}
    public function paypal()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['paypal_username'] == "" || $config['paypal_password'] == "" || $config['paypal_signature'] == "") {
                set_alert('error', 'Paypal config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $data = array(
                    'cancelUrl' => base_url('feespayment/getsuccesspayment'),
                    'returnUrl' => base_url('feespayment/getsuccesspayment'),
                    'fees_allocation_id' => $params['allocation_id'],
                    'fees_type_id' => $params['type_id'],
                    'name' => $params['student_name'],
                    'description' => "Online Student fees deposit. Invoice No - " . $params['invoice_no'],
                    'amount' => floatval($params['amount'] + $params['fine']),
                    'currency' => $params['currency'],
                );
                $response = $this->paypal_payment->payment($data);
                if ($response->isSuccessful()) {

                } elseif ($response->isRedirect()) {
                    $response->redirect();
                } else {
                    echo $response->getMessage();
                }
            }
        }
    }

    /* paypal successpayment redirect */
    public function getsuccesspayment()
    {
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            // null session data
            $this->session->set_userdata("params", "");
            $data = array(
                'fees_allocation_id' => $params['allocation_id'],
                'fees_type_id' => $params['type_id'],
                'name' => $params['student_name'],
                'description' => "Online Student fees deposit. Invoice No - " . $params['invoice_no'],
                'amount' => floatval($params['amount'] + $params['fine']),
                'currency' => $params['currency'],
            );
            $response = $this->paypal_payment->success($data);
            $paypalResponse = $response->getData();
            if ($response->isSuccessful()) {
                $purchaseId = $_GET['PayerID'];
                if (isset($paypalResponse['PAYMENTINFO_0_ACK']) && $paypalResponse['PAYMENTINFO_0_ACK'] === 'Success') {
                    if ($purchaseId) {
                        $ref_id = $paypalResponse['PAYMENTINFO_0_TRANSACTIONID'];
                        // payment info update in invoice
                        $arrayFees = array(
                            'allocation_id' => $params['allocation_id'],
                            'type_id' => $params['type_id'],
                            'collect_by' => "",
                            'amount' => floatval($paypalResponse['PAYMENTINFO_0_AMT'] - $params['fine']),
                            'discount' => 0,
                            'fine' => $params['fine'],
                            'pay_via' => 6,
                            'collect_by' => 'online',
                            'remarks' => "Fees deposits online via Paypal Ref ID: " . $ref_id,
                            'date' => date("Y-m-d"),
                        );
                        $this->savePaymentData($arrayFees);

                        set_alert('success', translate('payment_successfull'));
                        redirect(base_url('userrole/invoice'));
                    }
                }
            } elseif ($response->isRedirect()) {
                $response->redirect();
            } else {
                set_alert('error', translate('payment_cancelled'));
                redirect(base_url('userrole/invoice'));
            }
        }
    }

    // stripe payment gateway script start
    public function stripe()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['stripe_secret'] == "") {
                set_alert('error', 'Stripe config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $data = array(
                    'imagesURL' => $this->application_model->getBranchImage(get_loggedin_branch_id(), 'logo-small'),
                    'success_url' => base_url("feespayment/stripe_success?session_id={CHECKOUT_SESSION_ID}"),
                    'cancel_url' => base_url("feespayment/stripe_success?session_id={CHECKOUT_SESSION_ID}"),
                    'fees_allocation_id' => $params['allocation_id'],
                    'fees_type_id' => $params['type_id'],
                    'name' => $params['student_name'],
                    'description' => "Online Student fees deposit. Invoice No - " . $params['invoice_no'],
                    'amount' => ($params['amount'] + $params['fine']),
                    'currency' => $params['currency'],
                );
                $response = $this->stripe_payment->payment($data);
                $data['sessionId'] = $response['id'];
                $data['stripe_publishiable'] = $config['stripe_publishiable'];
                $this->load->view('layout/stripe', $data);
            }
        }
    }

    public function stripe_success()
    {
        $sessionId = $this->input->get('session_id');
        $params = $this->session->userdata('params');
        if (!empty($sessionId) && !empty($params)) {
            try {
                $response = $this->stripe_payment->verify($sessionId);
                if (isset($response->payment_status) && $response->payment_status == 'paid') {
                    $amount = floatval($response->amount_total) / 100;
                    $ref_id = $response->payment_intent;
                    // payment info update in invoice
                    $arrayFees = array(
                        'allocation_id' => $params['allocation_id'],
                        'type_id' => $params['type_id'],
                        'collect_by' => "",
                        'amount' => ($amount - floatval($params['fine'])),
                        'discount' => 0,
                        'fine' => $params['fine'],
                        'pay_via' => 7,
                        'collect_by' => 'online',
                        'remarks' => "Fees deposits online via Stripe Ref ID: " . $ref_id,
                        'date' => date("Y-m-d"),
                    );
                    $this->savePaymentData($arrayFees);
                    set_alert('success', translate('payment_successfull'));
                    redirect(base_url('userrole/invoice'));
                } else {
                    // payment failed: display message to customer
                    set_alert('error', "Something went wrong!");
                    redirect(base_url('userrole/invoice'));
                }
            } catch (\Exception $ex) {
                set_alert('error', $ex->getMessage());
                redirect(site_url('userrole/invoice'));
            }
        }
    }

    // paystack payment gateway script start
    public function paystack()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['paystack_secret_key'] == "") {
                set_alert('error', 'Paystack config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $result = array();
                $amount = ($params['amount'] + $params['fine']) * 100;
                $ref = app_generate_hash();
                $callback_url = base_url() . 'feespayment/verify_paystack_payment/' . $ref;
                $postdata = array('email' => $params['student_email'], 'amount' => $amount, "reference" => $ref, "callback_url" => $callback_url);
                $url = "https://api.paystack.co/transaction/initialize";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata)); //Post Fields
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $headers = [
                    'Authorization: Bearer ' . $config['paystack_secret_key'],
                    'Content-Type: application/json',
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $request = curl_exec($ch);
                curl_close($ch);
                //
                if ($request) {
                    $result = json_decode($request, true);
                }

                $redir = $result['data']['authorization_url'];
                header("Location: " . $redir);
            }
        }
    }

    public function verify_paystack_payment($ref)
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        // null session data
        $this->session->set_userdata("params", "");
        $result = array();
        $url = 'https://api.paystack.co/transaction/verify/' . $ref;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $config['paystack_secret_key']]
        );
        $request = curl_exec($ch);
        curl_close($ch);
        //
        if ($request) {
            $result = json_decode($request, true);
            // print_r($result);
            if ($result) {
                if ($result['data']) {
                    //something came in
                    if ($result['data']['status'] == 'success') {
                        // payment info update in invoice
                        $arrayFees = array(
                            'allocation_id' => $params['allocation_id'],
                            'type_id' => $params['type_id'],
                            'collect_by' => "",
                            'amount' => $params['amount'],
                            'discount' => 0,
                            'fine' => $params['fine'],
                            'pay_via' => 9,
                            'collect_by' => 'online',
                            'remarks' => "Fees deposits online via Paystack Ref ID: " . $ref,
                            'date' => date("Y-m-d"),
                        );
                        $this->savePaymentData($arrayFees);

                        set_alert('success', translate('payment_successfull'));
                        redirect(base_url('userrole/invoice'));

                    } else {
                        // the transaction was not successful, do not deliver value'
                        // print_r($result);  //uncomment this line to inspect the result, to check why it failed.
                        set_alert('error', "Transaction Failed");
                        redirect(base_url('userrole/invoice'));
                    }
                } else {
                    //echo $result['message'];
                    set_alert('error', "Transaction Failed");
                    redirect(base_url('userrole/invoice'));
                }
            } else {
                //print_r($result);
                //die("Something went wrong while trying to convert the request variable to json. Uncomment the print_r command to see what is in the result variable.");
                set_alert('error', "Transaction Failed");
                redirect(base_url('userrole/invoice'));
            }
        } else {
            //var_dump($request);
            //die("Something went wrong while executing curl. Uncomment the var_dump line above this line to see what the issue is. Please check your CURL command to make sure everything is ok");
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/invoice'));
        }
    }

    /* PayUmoney Payment */
    public function payumoney()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['payumoney_key'] == "" || $config['payumoney_salt'] == "") {
                set_alert('error', 'PayUmoney config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                // api config
                if ($config['payumoney_demo'] == 1) {
                    $api_link = "https://test.payu.in/_payment";
                } else {
                    $api_link = "https://secure.payu.in/_payment";
                }
                $key = $config['payumoney_key'];
                $salt = $config['payumoney_salt'];

                // payumoney details
                $invoiceNo = $params['invoice_no'];
                $amount = floatval($params['amount'] + $params['fine']);
                $payer_name = $params['payer_data']['name'];
                $payer_email = $params['payer_data']['email'];
                $payer_phone = $params['payer_data']['phone'];
                $product_info = "Online Student fees deposit. Invoice No - " . $invoiceNo;
                // redirect url
                $success = base_url('feespayment/payumoney_success');
                $fail = base_url('feespayment/payumoney_success');
                // generate transaction id
                $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                $params['txn_id'] = $txnid;
                $this->session->set_userdata("params", $params);

                // optional udf values
                $udf1 = '';
                $udf2 = '';
                $udf3 = '';
                $udf4 = '';
                $udf5 = '';

                $hashstring = $key . '|' . $txnid . '|' . $amount . '|' . $product_info . '|' . $payer_name . '|' . $payer_email . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $salt;
                $hash = strtolower(hash('sha512', $hashstring));
                $data = array(
                    'salt' => $salt,
                    'key' => $key,
                    'payu_base_url' => $api_link,
                    'action' => $api_link,
                    'surl' => $success,
                    'furl' => $fail,
                    'txnid' => $txnid,
                    'amount' => $amount,
                    'firstname' => $payer_name,
                    'email' => $payer_email,
                    'phone' => $payer_phone,
                    'productinfo' => $product_info,
                    'hash' => $hash,
                );
                $this->load->view('layout/payumoney', $data);
            }
        }
    }

    /* payumoney successpayment redirect */
    public function payumoney_success()
    {
        if ($this->input->server('REQUEST_METHOD') == 'POST') {
            $params = $this->session->userdata('params');
            // null session data
            $this->session->set_userdata("params", "");
            if ($this->input->post('status') == "success") {
                $txn_id = $params['txn_id'];
                $mihpayid = $this->input->post('mihpayid');
                $transactionid = $this->input->post('txnid');
                if ($txn_id == $transactionid) {
                    // payment info update in invoice
                    $arrayFees = array(
                        'allocation_id' => $params['allocation_id'],
                        'type_id' => $params['type_id'],
                        'collect_by' => "",
                        'amount' => ($this->input->post('amount') - $params['fine']),
                        'discount' => 0,
                        'fine' => $params['fine'],
                        'pay_via' => 8,
                        'collect_by' => 'online',
                        'remarks' => "Fees deposits online via PayU TXN ID: " . $txn_id . " / PayU Ref ID: " . $mihpayid,
                        'date' => date("Y-m-d"),
                    );
                    $this->savePaymentData($arrayFees);

                    set_alert('success', translate('payment_successfull'));
                    redirect(base_url('userrole/invoice'));
                } else {
                    set_alert('error', translate('invalid_transaction'));
                    redirect(base_url('userrole/invoice'));
                }
            } else {
                set_alert('error', "Transaction Failed");
                redirect(base_url('userrole/invoice'));
            }
        }
    }

    // razorpay payment gateway script start
    public function razorpay()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['razorpay_key_id'] == "" || $config['razorpay_key_secret'] == "") {
                set_alert('error', 'Razorpay config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $response = $this->razorpay_payment->payment($params);
                $params['razorpay_order_id'] = $response;
                $this->session->set_userdata("params", $params);
                $arrayData = array(
                    'key' => $config['razorpay_key_id'],
                    'amount' => ($params['amount'] + $params['fine']) * 100,
                    'name' => $params['student_name'],
                    'description' => "Submitting student fees online. Invoice No - " . $params['invoice_no'],
                    'image' => base_url('uploads/app_image/logo-small.png'),
                    'currency' => 'INR',
                    'order_id' => $params['razorpay_order_id'],
                    'theme' => ["color" => "#F37254"],
                );
                $data['return_url'] = base_url('userrole/invoice');
                $data['pay_data'] = json_encode($arrayData);
                $this->load->view('layout/razorpay', $data);
            }
        }
    }

    public function razorpay_verify()
    {
        $params = $this->session->userdata('params');
        if ($this->input->post('razorpay_payment_id')) {
            // null session data
            $this->session->set_userdata("params", "");
            $attributes = array(
                'razorpay_order_id' => $params['razorpay_order_id'],
                'razorpay_payment_id' => $this->input->post('razorpay_payment_id'),
                'razorpay_signature' => $this->input->post('razorpay_signature'),
            );
            $response = $this->razorpay_payment->verify($attributes);
            if ($response == true) {
                // payment info update in invoice
                $arrayFees = array(
                    'allocation_id' => $params['allocation_id'],
                    'type_id' => $params['type_id'],
                    'collect_by' => "",
                    'amount' => ($params['amount']),
                    'discount' => 0,
                    'fine' => $params['fine'],
                    'pay_via' => 10,
                    'collect_by' => 'online',
                    'remarks' => "Fees deposits online via Razorpay TxnID: " . $attributes['razorpay_payment_id'],
                    'date' => date("Y-m-d"),
                );
                $this->savePaymentData($arrayFees);
                set_alert('success', translate('payment_successfull'));
                redirect(base_url('userrole/invoice'));
            } else {
                set_alert('error', $response);
                redirect(base_url('userrole/invoice'));
            }
        }
    }

    // sslcommerz payment gateway script start
    public function sslcommerz()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['sslcz_store_id'] == "" || $config['sslcz_store_passwd'] == "") {
                set_alert('error', 'SSLcommerz config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {

                $post_data = array();
                $post_data['total_amount'] = floatval($params['amount'] + $params['fine']);
                $post_data['currency'] = "BDT";
                $post_data['tran_id'] = $params['tran_id'];
                $post_data['success_url'] = base_url('feespayment/sslcommerz_success');
                $post_data['fail_url'] = base_url('feespayment/sslcommerz_success');
                $post_data['cancel_url'] = base_url('feespayment/sslcommerz_success');
                $post_data['ipn_url'] = base_url() . "ipn";

                # CUSTOMER INFORMATION
                $post_data['cus_name'] = $params['cus_name'];
                $post_data['cus_email'] = $params['cus_email'];
                $post_data['cus_add1'] = $params['cus_address'];
                $post_data['cus_city'] = $params['cus_state'];
                $post_data['cus_state'] = $params['cus_state'];
                $post_data['cus_postcode'] = $params['cus_postcode'];
                $post_data['cus_country'] = "Bangladesh";
                $post_data['cus_phone'] = $params['cus_phone'];

                $post_data['product_profile'] = "non-physical-goods";
                $post_data['shipping_method'] = "No";
                $post_data['num_of_item'] = "1";
                $post_data['product_name'] = "School Fee";
                $post_data['product_category'] = "SchoolFee";

                $this->sslcommerz->RequestToSSLC($post_data);
            }
        }
    }

    /* sslcommerz successpayment redirect */
    public function sslcommerz_success()
    {
        $params = $this->session->userdata('params');
        if (($_POST['status'] == 'VALID') && ($params['tran_id'] == $_POST['tran_id'])) {
            if ($this->sslcommerz->ValidateResponse($_POST['currency_amount'], "BDT", $_POST)) {
                $tran_id = $params['tran_id'];
                $arrayFees = array(
                    'allocation_id' => $params['allocation_id'],
                    'type_id' => $params['type_id'],
                    'collect_by' => "",
                    'amount' => floatval($_POST['currency_amount'] - $params['fine']),
                    'discount' => 0,
                    'fine' => $params['fine'],
                    'pay_via' => 11,
                    'collect_by' => 'online',
                    'remarks' => "Fees deposits online via SSLcommerz TXN ID: " . $tran_id,
                    'date' => date("Y-m-d"),
                );
                $this->savePaymentData($arrayFees);
                set_alert('success', translate('payment_successfull'));
                redirect(base_url('userrole/invoice'));
            }
        } else {
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/invoice'));
        }
    }

    // jazzcash payment gateway script start
    public function jazzcash()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['jazzcash_merchant_id'] == "" || $config['jazzcash_passwd'] == "" || $config['jazzcash_integerity_salt'] == "") {
                set_alert('error', 'Jazzcash config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $integeritySalt = $config['jazzcash_integerity_salt'];
                $pp_TxnRefNo = 'T' . date('YmdHis');
                $post_data = array(
                    "pp_Version" => "2.0",
                    "pp_TxnType" => "MPAY",
                    "pp_Language" => "EN",
                    "pp_IsRegisteredCustomer" => "Yes",
                    "pp_TokenizedCardNumber" => "",
                    "pp_CustomerEmail" => "",
                    "pp_CustomerMobile" => "",
                    "pp_CustomerID" => uniqid(),
                    "pp_MerchantID" => $config['jazzcash_merchant_id'],
                    "pp_Password" => $config['jazzcash_passwd'],
                    "pp_TxnRefNo" => $pp_TxnRefNo,
                    "pp_Amount" => floatval($params['amount'] + $params['fine']) * 100,
                    "pp_DiscountedAmount" => "",
                    "pp_DiscountBank" => "",
                    "pp_TxnCurrency" => "PKR",
                    "pp_TxnDateTime" => date('YmdHis'),
                    "pp_BillReference" => uniqid(),
                    "pp_Description" => "Submitting student fees online. Invoice No - " . $params['invoice_no'],
                    "pp_TxnExpiryDateTime" => date('YmdHis', strtotime("+1 hours")),
                    "pp_ReturnURL" => base_url('feespayment/jazzcash_success'),
                    "ppmpf_1" => "1",
                    "ppmpf_2" => "2",
                    "ppmpf_3" => "3",
                    "ppmpf_4" => "4",
                    "ppmpf_5" => "5",
                );

                $sorted_string = $integeritySalt . '&';
                $sorted_string .= $post_data['pp_Amount'] . '&';
                $sorted_string .= $post_data['pp_BillReference'] . '&';
                $sorted_string .= $post_data['pp_CustomerID'] . '&';
                $sorted_string .= $post_data['pp_Description'] . '&';
                $sorted_string .= $post_data['pp_IsRegisteredCustomer'] . '&';
                $sorted_string .= $post_data['pp_Language'] . '&';
                $sorted_string .= $post_data['pp_MerchantID'] . '&';
                $sorted_string .= $post_data['pp_Password'] . '&';
                $sorted_string .= $post_data['pp_ReturnURL'] . '&';
                $sorted_string .= $post_data['pp_TxnCurrency'] . '&';
                $sorted_string .= $post_data['pp_TxnDateTime'] . '&';
                $sorted_string .= $post_data['pp_TxnExpiryDateTime'] . '&';
                $sorted_string .= $post_data['pp_TxnRefNo'] . '&';
                $sorted_string .= $post_data['pp_TxnType'] . '&';
                $sorted_string .= $post_data['pp_Version'] . '&';
                $sorted_string .= $post_data['ppmpf_1'] . '&';
                $sorted_string .= $post_data['ppmpf_2'] . '&';
                $sorted_string .= $post_data['ppmpf_3'] . '&';
                $sorted_string .= $post_data['ppmpf_4'] . '&';
                $sorted_string .= $post_data['ppmpf_5'];

                //sha256 hash encoding
                $pp_SecureHash = hash_hmac('sha256', $sorted_string, $integeritySalt);
                $post_data['pp_SecureHash'] = $pp_SecureHash;
                if ($config['jazzcash_sandbox'] == 1) {
                    $data['api_url'] = "https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/";
                } else {
                    $data['api_url'] = "https://jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/";
                }
                $data['post_data'] = $post_data;
                $this->load->view('layout/jazzcash_pay', $data);
            }
        }
    }

    /* jazzcash successpayment redirect */
    public function jazzcash_success()
    {
        $params = $this->session->userdata('params');
        if ($_POST['pp_ResponseCode'] == '000') {
            $tran_id = $_POST['pp_TxnRefNo'];
            $arrayFees = array(
                'allocation_id' => $params['allocation_id'],
                'type_id' => $params['type_id'],
                'collect_by' => "",
                'amount' => floatval($params['amount']),
                'discount' => 0,
                'fine' => $params['fine'],
                'pay_via' => 12,
                'collect_by' => 'online',
                'remarks' => "Fees deposits online via JazzCash TXN ID: " . $tran_id,
                'date' => date("Y-m-d"),
            );
            $this->savePaymentData($arrayFees);
            set_alert('success', translate('payment_successfull'));
            redirect(base_url('userrole/invoice'));
        } elseif ($_POST['pp_ResponseCode'] == '112') {
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/invoice'));
        } else {
            set_alert('error', $_POST['pp_ResponseMessage']);
            redirect(base_url('userrole/invoice'));
        }
    }

    // midtrans payment gateway script start
    public function midtrans()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['midtrans_client_key'] == "" && $config['midtrans_server_key'] == "") {
                set_alert('error', 'Stripe config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $amount = number_format($params['amount'] + $params['fine'], 2, '.', '');
                $orderID = rand();
                $params['orderID'] = $orderID;
                $this->session->set_userdata("params", $params);
                $response = $this->midtrans_payment->get_SnapToken(round($amount), $orderID);
                $data['snapToken'] = $response;
                $data['midtrans_client_key'] = $config['midtrans_client_key'];
                $this->load->view('layout/midtrans', $data);
            }
        }
    }

    public function midtrans_success()
    {
        $params = $this->session->userdata('params');
        $response = json_decode($_POST['post_data']);
        if (!empty($params) && !empty($params['orderID']) && !empty($response)) {
            // null session data
            $this->session->set_userdata("params", "");
            if ($response->order_id == $params['orderID']) {
                $tran_id = $response->transaction_id;
                $arrayFees = array(
                    'allocation_id' => $params['allocation_id'],
                    'type_id' => $params['type_id'],
                    'collect_by' => "",
                    'amount' => $params['amount'],
                    'discount' => 0,
                    'fine' => $params['fine'],
                    'pay_via' => 13,
                    'collect_by' => 'online',
                    'remarks' => "Fees deposits online via Midtrans TXN ID: " . $tran_id,
                    'date' => date("Y-m-d"),
                );
                $this->savePaymentData($arrayFees);
                set_alert('success', translate('payment_successfull'));
            } else {
                set_alert('error', "Something went wrong!");
            }
            echo json_encode(array('url' => base_url('userrole/invoice')));
        }
    }

    // flutterwave payment gateway script start
    public function flutterwave()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['flutterwave_public_key'] == "" && $config['flutterwave_secret_key'] == "") {
                set_alert('error', 'Flutter Wave config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $amount = floatval($params['amount'] + $params['fine']);
                $txref = "rsm" . app_generate_hash();
                $params['txref'] = $txref;
                $this->session->set_userdata("params", $params);
                $callback_url = base_url('feespayment/verify_flutterwave_payment');
                $data = array(
                    'student_name' => $params['student_name'],
                    'amount' => $amount,
                    'customer_email' => $params['student_email'],
                    'currency' => $params['currency'],
                    "txref" => $txref,
                    "pubKey" => $config['flutterwave_public_key'],
                    "redirect_url" => $callback_url,
                );
                $this->load->view('layout/flutterwave', $data);
            }
        }
    }

    public function verify_flutterwave_payment()
    {
        if (isset($_GET['cancelled']) && $_GET['cancelled'] == 'true') {
            set_alert('error', "Payment Cancelled");
            redirect(base_url('userrole/invoice'));
        }

        if (isset($_GET['tx_ref'])) {
            $config = $this->get_payment_config();
            $params = $this->session->userdata('params');
            $this->session->set_userdata("params", "");
            $postdata = array(
                "SECKEY" => $config['flutterwave_secret_key'],
                "txref" => $params['txref'],
            );
            $url = 'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata)); //Post Fields
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $headers = [
                'content-type: application/json',
                'cache-control: no-cache',
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $request = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($request, true);
            if ($result['status'] == 'success' && isset($result['data']['chargecode']) && ($result['data']['chargecode'] == '00' || $result['data']['chargecode'] == '0')) {
                $arrayFees = array(
                    'allocation_id' => $params['allocation_id'],
                    'type_id' => $params['type_id'],
                    'amount' => $params['amount'],
                    'fine' => $params['fine'],
                    'collect_by' => "",
                    'discount' => 0,
                    'pay_via' => 14,
                    'collect_by' => 'online',
                    'remarks' => "Fees deposits online via FlutterWave TXREF: " . $params['txref'],
                    'date' => date("Y-m-d"),
                );
                $this->savePaymentData($arrayFees);
                set_alert('success', translate('payment_successfull'));
                redirect(base_url('userrole/invoice'));
            } else {
                set_alert('error', "Transaction Failed");
                redirect(base_url('userrole/invoice'));
            }
        } else {
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/invoice'));
        }
    }

    //Paytm payment gateway script start
    public function paytm()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['paytm_merchantmid'] == "" && $config['paytm_merchantkey'] == "") {
                set_alert('error', 'Paytm config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $PAYTM_MERCHANT_MID = $config['paytm_merchantmid'];
                $PAYTM_MERCHANT_KEY = $config['paytm_merchantkey'];
                $PAYTM_MERCHANT_WEBSITE = $config['paytm_merchant_website'];
                $PAYTM_INDUSTRY_TYPE = $config['paytm_industry_type'];
                $transactionURL = 'https://securegw.paytm.in/theia/processTransaction'; //For Production or LIVE Credentials
                // $transactionURL = 'https://securegw-stage.paytm.in/theia/processTransaction'; //TEST Credentials

                $orderID = time();
                $paytmParams = array();
                $paytmParams['ORDER_ID'] = $orderID;
                $paytmParams['TXN_AMOUNT'] = floatval($params['amount'] + $params['fine']);
                $paytmParams["CUST_ID"] = get_loggedin_user_id();
                $paytmParams["EMAIL"] = (!empty($params['email']) ? $params['email'] : "");
                $paytmParams["MID"] = $PAYTM_MERCHANT_MID;
                $paytmParams["CHANNEL_ID"] = "WEB";
                $paytmParams["WEBSITE"] = $PAYTM_MERCHANT_WEBSITE;
                $paytmParams["CALLBACK_URL"] = base_url('feespayment/paytm_success');
                $paytmParams["INDUSTRY_TYPE_ID"] = $PAYTM_INDUSTRY_TYPE;

                $paytmChecksum = $this->paytm_kit_lib->generateSignature($paytmParams, $PAYTM_MERCHANT_MID);
                $paytmParams["CHECKSUMHASH"] = $paytmChecksum;
                $data = array();
                $data['paytmParams'] = $paytmParams;
                $data['transactionURL'] = $transactionURL;
                $this->load->view('layout/paytm', $data);
            }
        }
    }

    public function paytm_success()
    {
        $params = $this->session->userdata('params');
        $this->session->set_userdata("params", "");
        $config = $this->get_payment_config();
        $PAYTM_MERCHANT_KEY = $config['paytm_merchantkey'];
        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";
        $paramList = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : "";
        $isValidChecksum = $this->paytm_kit_lib->verifySignature($paramList, $PAYTM_MERCHANT_KEY, $paytmChecksum);
        if ($isValidChecksum == "TRUE") {
            if ($_POST["STATUS"] == "TXN_SUCCESS") {
                $tran_id = $_POST['TXNID'];

                $arrayFees = array(
                    'allocation_id' => $params['allocation_id'],
                    'type_id' => $params['type_id'],
                    'amount' => $params['amount'],
                    'fine' => $params['fine'],
                    'collect_by' => "",
                    'discount' => 0,
                    'pay_via' => 15,
                    'collect_by' => 'online',
                    'remarks' => "Fees deposits online via Paytm TXREF: " . $tran_id,
                    'date' => date("Y-m-d"),
                );
                $this->savePaymentData($arrayFees);
                set_alert('success', translate('payment_successfull'));
                redirect(base_url('userrole/invoice'));
            } else {
                set_alert('error', "Something went wrong!");
                redirect(base_url('userrole/invoice'));
            }
        } else {
            set_alert('error', "Checksum mismatched.");
            redirect(base_url('userrole/invoice'));
        }
    }

    // toyyibpay payment gateway script start
    public function toyyibpay()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['toyyibpay_secretkey'] == "" && $config['toyyibpay_categorycode'] == "") {
                set_alert('error', 'toyyibPay config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $payment_data = array(
                    'userSecretKey' => $config['toyyibpay_secretkey'],
                    'categoryCode' => $config['toyyibpay_categorycode'],
                    'billName' => 'School Fee',
                    'billDescription' => 'Student Fee',
                    'billPriceSetting' => 1,
                    'billPayorInfo' => 1,
                    'billAmount' => floatval($params['amount'] + $params['fine']) * 100,
                    'billReturnUrl' => base_url('feespayment/toyyibpay_success'),
                    'billCallbackUrl' => base_url('feespayment/toyyibpay_callbackurl'),
                    'billExternalReferenceNo' => substr(hash('sha256', mt_rand() . microtime()), 0, 20),
                    'billTo' => $params['student_name'],
                    'billEmail' => $params['payer_email'],
                    'billPhone' => $params['payer_phone'],
                    'billSplitPayment' => 0,
                    'billSplitPaymentArgs' => '',
                    'billPaymentChannel' => '0',
                    'billContentEmail' => 'Thank you for pay school fees',
                    'billChargeToCustomer' => 1,
                );

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/createBill');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payment_data);
                $result = curl_exec($curl);
                $info = curl_getinfo($curl);
                curl_close($curl);
                $obj = json_decode($result);
                if (!empty($obj)) {
                    $url = "https://toyyibpay.com/" . $obj[0]->BillCode;
                    header("Location: $url");
                } else {
                    set_alert('error', "Transaction Failed");
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
        }
    }

    public function toyyibpay_success()
    {
        if ($_GET['status_id'] == 1) {
            set_alert('success', translate('payment_successfull'));
            redirect(base_url('userrole/invoice'));
        } else {
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/invoice'));
        }
    }

    public function toyyibpay_callbackurl()
    {
        if (!empty($_POST['status']) && $_POST['status'] == 1) {
            $refno = $_POST['refno'];
            $params = $this->session->userdata('params');
            $this->session->set_userdata("params", "");
            $arrayFees = array(
                'allocation_id' => $params['allocation_id'],
                'type_id' => $params['type_id'],
                'amount' => $params['amount'],
                'fine' => $params['fine'],
                'collect_by' => "",
                'discount' => 0,
                'pay_via' => 16,
                'collect_by' => 'online',
                'remarks' => "Fees deposits online via toyyibPay TXREF: " . $refno,
                'date' => date("Y-m-d"),
            );
            $this->savePaymentData($arrayFees);
        }
    }

    // payhere payment gateway script start
    public function payhere()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['payhere_merchant_id'] == "" && $config['payhere_merchant_secret'] == "") {
                set_alert('error', 'Payhere config not available.');
                redirect($_SERVER['HTTP_REFERER']);
            } else {

                $merchantID = $config['payhere_merchant_id'];
                $orderID = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                $currency = 'LKR';
                $merchant_secret = $config['payhere_merchant_secret'];
                $hash = strtoupper(
                    md5(
                        $merchantID .
                        $orderID .
                        number_format($params['amount'], 2, '.', '') .
                        $currency .
                        strtoupper(md5($merchant_secret))
                    )
                );
                $paytmParams = array();
                $paytmParams['merchant_id'] = $merchantID;
                $paytmParams['return_url'] = base_url('feespayment/payhere_return');
                $paytmParams["cancel_url"] = base_url('feespayment/payhere_cancel');
                $paytmParams["notify_url"] = base_url('feespayment/payhere_notify');
                $paytmParams["order_id"] = $orderID;
                $paytmParams["items"] = "School Fees";
                $paytmParams["currency"] = "LKR";
                $paytmParams["amount"] = floatval($params['amount']);
                $paytmParams["first_name"] = $params['student_name'];
                $paytmParams["last_name"] = '';
                $paytmParams["email"] = $params['payer_email'];
                $paytmParams["phone"] = $params['payer_phone'];
                $paytmParams["address"] = '';
                $paytmParams["city"] = '';
                $paytmParams["country"] = 'Sri Lanka';
                $paytmParams["hash"] = $hash;
                $data['paytmParams'] = $paytmParams;
                $this->load->view('layout/payhere', $data);
            }
        }
    }

    public function payhere_notify()
    {
        if ($_POST) {
            $config = $this->get_payment_config();
            $merchant_id = $_POST['merchant_id'];
            $order_id = $_POST['order_id'];
            $payhere_amount = $_POST['payhere_amount'];
            $payhere_currency = $_POST['payhere_currency'];
            $status_code = $_POST['status_code'];
            $md5sig = $_POST['md5sig'];
            $merchant_secret = $config['payhere_merchant_secret'];
            $local_md5sig = strtoupper(
                md5(
                    $merchant_id .
                    $order_id .
                    $payhere_amount .
                    $payhere_currency .
                    $status_code .
                    strtoupper(md5($merchant_secret))
                )
            );
            if (($local_md5sig === $md5sig) && ($status_code == 2)) {
                $params = $this->session->userdata('params');
                $this->session->set_userdata("params", "");
                $arrayFees = array(
                    'allocation_id' => $params['allocation_id'],
                    'type_id' => $params['type_id'],
                    'collect_by' => "",
                    'amount' => $params['amount'],
                    'discount' => 0,
                    'fine' => $params['fine'],
                    'pay_via' => 18,
                    'collect_by' => 'online',
                    'remarks' => "Fees deposits online via Payhere TXN ID: " . $order_id,
                    'date' => date("Y-m-d"),
                );
                $this->savePaymentData($arrayFees);
            }
        }
    }

    public function payhere_cancel()
    {
        $params = $this->session->userdata('params');
        $this->session->set_userdata("params", "");
        set_alert('error', "Something went wrong!");
        redirect(base_url('userrole/invoice'));
    }

    public function payhere_return()
    {
        set_alert('success', translate('payment_successfull'));
        redirect(base_url('userrole/invoice'));
    }

    public function nepalste()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['nepalste_public_key'] == "" && $config['nepalste_secret_key'] == "") {
                set_alert('error', 'Nepalste config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {

                $orderID = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                $params['myIdentifier'] = $orderID;
                $this->session->set_userdata("params", $params);
                $parameters = [
                    'identifier' => $orderID,
                    'currency' => 'NPR',
                    'amount' => number_format(floatval($params['amount'] + $params['fine']), 2, '.', ''),
                    'details' => "Online Student fees deposit. Invoice No - " . $params['invoice_no'],
                    'ipn_url' => base_url('feespayment/nepalste_notify'),
                    'cancel_url' => base_url('feespayment/payhere_cancel'),
                    'success_url' => base_url('feespayment/payhere_return'),
                    'public_key' => $config['nepalste_public_key'],
                    'site_logo' => $this->application_model->getBranchImage(get_loggedin_branch_id(), 'logo-small'),
                    'checkout_theme' => 'dark',
                    'customer_name' => $params['student_name'],
                    'customer_email' => (empty($params['student_email']) ? 'john@mail.com' : $params['student_email']),
                ]; 

                //live end point
                $url = "https://nepalste.com.np/payment/initiate";

                /* test end point
                $url = "https://nepalste.com.np/sandbox/payment/initiate";*/

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS,  $parameters);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch); 
                curl_close($ch);
                $obj = json_decode($result);
                if (!empty($obj)) {
                    $url = $obj->url;
                    header("Location: $url");
                } else {
                    set_alert('error', "Transaction Failed");
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
        }
    }

    public function nepalste_notify()
    {
        if ($_POST) {
            $params = $this->session->userdata('params');
            $this->session->set_userdata("params", "");
            $config = $this->get_payment_config();

            //Receive the response parameter
            $status = $_POST['status'];
            $signature = $_POST['signature'];
            $identifier = $_POST['identifier'];
            $data = $_POST['data'];

            // Generate your signature
            $customKey = $data['amount'].$identifier;
            $secret = $config['nepalste_secret_key'];
            $mySignature = strtoupper(hash_hmac('sha256', $customKey , $secret));
            $myIdentifier = $params['myIdentifier'];
            if($status == "success" && $signature == $mySignature &&  $identifier ==  $myIdentifier){
                $arrayFees = array(
                    'allocation_id' => $params['allocation_id'],
                    'type_id' => $params['type_id'],
                    'collect_by' => "",
                    'amount' => $params['amount'],
                    'discount' => 0,
                    'fine' => $params['fine'],
                    'pay_via' => 19,
                    'collect_by' => 'online',
                    'remarks' => "Fees deposits online via Nepalste TXN ID: " . $identifier,
                    'date' => date("Y-m-d"),
                );
                $this->savePaymentData($arrayFees);
            }
        }
    }

    private function savePaymentData($data)
    {
        // insert in DB
        $this->db->insert('fee_payment_history', $data);

        // transaction voucher save function
        $getSeeting = $this->fees_model->get('transactions_links', array('branch_id' => get_loggedin_branch_id()), true);
        if ($getSeeting['status']) {
            $arrayTransaction = array(
                'account_id' => $getSeeting['deposit'],
                'amount' => $data['amount'] + $data['fine'],
                'date' => $data['date'],
            );
            $this->fees_model->saveTransaction($arrayTransaction);
        }
    }
    /**
 * Initiate M-Pesa STK Push for fees payment
 */
public function initiate_mpesa_stk()
{
    $params = $this->session->userdata('payment_params');
    
    if (empty($params)) {
        set_alert('error', 'Payment session expired. Please try again.');
        redirect(base_url('userrole/invoice'));
    }
    
    // Clear session immediately to prevent duplicate submissions
    $this->session->unset_userdata('payment_params');
    
    // Get and sanitize data
    $phone = isset($params['phone']) ? $params['phone'] : '';
    $amount = floatval($params['amount'] + $params['fine']);
    $fine = floatval($params['fine']);
    $account_ref = 'FEE_' . $params['student_id'] . '_' . time();
    
    if (empty($phone)) {
        set_alert('error', 'Phone number not found. Please try again.');
        redirect(base_url('userrole/invoice'));
    }
    
    if ($amount <= 0) {
        set_alert('error', 'Invalid amount. Please try again.');
        redirect(base_url('userrole/invoice'));
    }
    
    // Load payment handler
    $this->load->library('Payment_handler');
    
    // Initiate STK push
    $result = $this->payment_handler->initiate_payment(
        $phone,
        $amount,
        $account_ref,
        'School Fees Payment - ' . $params['invoice_no'],
        'fees'
    );
    
    if ($result['success']) {
        // Save transaction record
        $stk_data = array(
            'branch_id' => $params['branch_id'],
            'student_id' => $params['student_id'],
            'allocation_id' => $params['allocation_id'],
            'type_id' => $params['type_id'],
            'amount' => $params['amount'],
            'fine' => $fine,
            'checkout_request_id' => $result['checkout_request_id'],
            'phone' => $phone,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        );
        
        $insert_result = $this->db->insert('mpesa_stk_transactions', $stk_data);
        
        if (!$insert_result) {
            // log_message('error', 'Failed to insert transaction: ' . json_encode($stk_data));
            set_alert('error', 'Failed to save transaction record.');
            redirect(base_url('userrole/invoice'));
        }
        
        $transaction_id = $this->db->insert_id();
        
        // Set data for the waiting view
        $this->data['title'] = 'M-Pesa Payment';
        $this->data['sub_page'] = 'fees/mpesa_waiting';
        $this->data['main_menu'] = 'fees';
        $this->data['checkout_request_id'] = $result['checkout_request_id'];
        $this->data['transaction_id'] = $transaction_id;
        $this->data['amount'] = $amount;
        $this->data['phone'] = $phone;
        $this->data['student_name'] = $params['student_name'];
        
        // Load the waiting view within the main layout
        $this->load->view('layout/index', $this->data);
        
    } else {
        set_alert('error', $result['message']);
        redirect(base_url('userrole/invoice'));
    }
}



/**
 * Check M-Pesa STK payment status (AJAX)
 */
public function check_mpesa_status()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    $this->output->set_content_type('application/json');
    
    $checkout_id = $this->input->post('checkout_request_id');
    $transaction_id = $this->input->post('transaction_id');
    
    // First check database
    $this->db->where('id', $transaction_id);
    $transaction = $this->db->get('mpesa_stk_transactions')->row();
    
    if ($transaction) {
        if ($transaction->status == 'completed') {
            echo json_encode(['status' => 'completed', 'message' => 'Payment successful!']);
            return;
        } elseif ($transaction->status == 'failed') {
            echo json_encode(['status' => 'failed', 'message' => 'Payment failed']);
            return;
        } elseif ($transaction->status == 'cancelled') {
            echo json_encode(['status' => 'cancelled', 'message' => 'Payment cancelled']);
            return;
        }
    }
    
    // If still pending, just return pending without calling Safaricom API
    // (to avoid Incapsula blocking issues)
    echo json_encode(['status' => 'pending', 'message' => 'Waiting for payment confirmation...']);
}

/**
 * M-Pesa STK Callback (webhook from Safaricom)
 */
public function mpesa_stk_callback()
{
    $callback_data = json_decode(file_get_contents('php://input'), true);
    
    // log_message('info', 'M-Pesa STK Callback: ' . json_encode($callback_data));
    
    if (isset($callback_data['Body']['stkCallback'])) {
        $callback = $callback_data['Body']['stkCallback'];
        $checkout_request_id = $callback['CheckoutRequestID'];
        $result_code = $callback['ResultCode'];
        
        // Update transaction status
        $update_data = array(
            'status' => ($result_code == 0) ? 'completed' : 'failed',
            'callback_data' => json_encode($callback),
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        if ($result_code == 0 && isset($callback['CallbackMetadata']['Item'])) {
            foreach ($callback['CallbackMetadata']['Item'] as $item) {
                if ($item['Name'] == 'MpesaReceiptNumber') {
                    $update_data['mpesa_receipt'] = $item['Value'];
                }
                if ($item['Name'] == 'TransactionDate') {
                    $update_data['transaction_date'] = $item['Value'];
                }
            }
        }
        
        $this->db->where('checkout_request_id', $checkout_request_id);
        $this->db->update('mpesa_stk_transactions', $update_data);
        
        // If payment successful, process the fees payment
        if ($result_code == 0) {
            $transaction = $this->db->where('checkout_request_id', $checkout_request_id)->get('mpesa_stk_transactions')->row();
            
            if ($transaction && $transaction->status == 'pending') {
                $this->_process_mpesa_payment($transaction);
            }
        }
    }
    
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
}

/**
 * Process M-Pesa payment after successful STK
 */
private function _process_mpesa_payment($transaction)
{
    // Get student details for SMS
    $student = $this->db->select('first_name, last_name, mobileno, parent_id')
        ->where('id', $transaction->student_id)
        ->get('student')
        ->row();
    
    // Calculate balance
    $b = $this->fees_model->getBalance($transaction->allocation_id, $transaction->type_id);
    $balance = isset($b['balance']) ? $b['balance'] - $transaction->amount : 0;
    
    // Save payment record
    $payment_data = array(
        'allocation_id' => $transaction->allocation_id,
        'type_id' => $transaction->type_id,
        'collect_by' => get_loggedin_user_id(),
        'amount' => $transaction->amount,
        'discount' => 0,
        'fine' => $transaction->fine,
        'pay_via' => 'mpesa_stk',
        'remarks' => 'M-Pesa STK Payment - Receipt: ' . $transaction->mpesa_receipt,
        'date' => date('Y-m-d')
    );
    
    $this->db->insert('fee_payment_history', $payment_data);
    
    // Send SMS notification
    $sms_data = array(
        'student_id' => $transaction->student_id,
        'amount' => $transaction->amount + $transaction->fine,
        'balance' => $balance,
        'paid_date' => date('Y-m-d H:i:s')
    );
    $this->load->model('sms_model');
    $this->sms_model->send_sms($sms_data, 2);
    
    // log_message('info', 'M-Pesa STK payment processed for student: ' . $transaction->student_id);
}

/**
 * Format phone number for M-Pesa
 */
private function _format_phone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
        return '254' . $phone;
    } elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
        return '254' . substr($phone, 1);
    } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
        return $phone;
    }
    return false;
}
/**
 * Show M-Pesa Paybill instructions
 */
public function mpesa_paybill_instructions()
{
    $params = $this->session->userdata('payment_params');
    
    if (empty($params)) {
        set_alert('error', 'Payment session expired. Please try again.');
        redirect(base_url('userrole/invoice'));
    }
    
    // Clear session
    $this->session->unset_userdata('payment_params');
    
    // Permission check
    if (!get_permission('mpesa_paybill', 'is_add')) {
        set_alert('error', 'You do not have permission to use M-Pesa Paybill');
        redirect(base_url('userrole/invoice'));
    }
    
    $data['amount'] = floatval($params['amount'] + $params['fine']);
    $data['account_number'] = $params['student_id'];
    $data['student_name'] = $params['student_name'];
    $data['invoice_no'] = $params['invoice_no'];
    $data['allocation_id'] = $params['allocation_id'];
    $data['type_id'] = $params['type_id'];
    
    $this->data = $data;
    $this->data['title'] = 'M-Pesa Paybill Payment';
    $this->load->view('fees/mpesa_paybill_instructions', $this->data);
}

/**
 * Confirm M-Pesa Paybill payment (manual entry)
 */
public function confirm_mpesa_paybill()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    $this->output->set_content_type('application/json');
    
    // Permission check
    if (!get_permission('mpesa_paybill', 'is_add')) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        return;
    }
    
    $this->form_validation->set_rules('amount', 'Amount', 'required|numeric');
    $this->form_validation->set_rules('transaction_id', 'Transaction ID', 'required');
    $this->form_validation->set_rules('allocation_id', 'Allocation ID', 'required');
    $this->form_validation->set_rules('type_id', 'Type ID', 'required');
    
    if ($this->form_validation->run() == false) {
        echo json_encode(['success' => false, 'message' => validation_errors()]);
        return;
    }
    
    $amount = $this->input->post('amount');
    $transaction_id = $this->input->post('transaction_id');
    $allocation_id = $this->input->post('allocation_id');
    $type_id = $this->input->post('type_id');
    $student_id = $this->input->post('student_id');
    $phone = $this->input->post('phone');
    
    // Save payment record
    $payment_data = array(
        'allocation_id' => $allocation_id,
        'type_id' => $type_id,
        'collect_by' => get_loggedin_user_id(),
        'amount' => $amount,
        'discount' => 0,
        'fine' => 0,
        'pay_via' => 'mpesa_paybill',
        'remarks' => 'M-Pesa Paybill - Transaction: ' . $transaction_id . ', Phone: ' . $phone,
        'date' => date('Y-m-d')
    );
    
    $this->db->insert('fee_payment_history', $payment_data);
    
    if ($this->db->affected_rows() > 0) {
        // Send SMS notification
        $this->_send_payment_sms($student_id, $amount, 0);
        
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save payment']);
    }
}
/**
 * Initiate Pesapal payment
 */
public function initiate_pesapal()
{
    $params = $this->session->userdata('payment_params');
    
    if (empty($params)) {
        set_alert('error', 'Payment session expired. Please try again.');
        redirect(base_url('userrole/invoice'));
    }
    
    // Permission check
    if (!get_permission('pesapal', 'is_add')) {
        set_alert('error', 'You do not have permission to use Pesapal');
        redirect(base_url('userrole/invoice'));
    }
    
    $amount = floatval($params['amount'] + $params['fine']);
    $order_id = 'FEE_' . $params['student_id'] . '_' . time();
    
    // Load Pesapal library
    $this->load->library('pesapal_lib');
    
    $payment_data = array(
        'amount' => $amount,
        'description' => 'School Fees Payment - Invoice ' . $params['invoice_no'],
        'type' => 'MERCHANT',
        'reference' => $order_id,
        'first_name' => $params['student_name'],
        'last_name' => '',
        'email' => $params['email'],
        'phonenumber' => $params['student_mobile'] ?? '',
        'currency' => $params['currency'],
        'order_id' => $order_id
    );
    
    // Store payment params for callback
    $this->session->set_userdata('pesapal_params', $params);
    $this->session->set_userdata('pesapal_order_id', $order_id);
    
    // Redirect to Pesapal
    $iframe_src = $this->pesapal_lib->getIframeSource($payment_data);
    
    $data['iframe_src'] = $iframe_src;
    $data['order_id'] = $order_id;
    $data['amount'] = $amount;
    $data['student_name'] = $params['student_name'];
    
    $this->data = $data;
    $this->data['title'] = 'Pesapal Payment';
    $this->load->view('fees/pesapal_payment', $this->data);
}

/**
 * Pesapal Payment Confirmation (IPN Callback)
 */
public function pesapal_confirmation()
{
    $this->load->library('pesapal_lib');
    
    $tracking_id = $this->input->get('pesapal_transaction_tracking_id');
    $merchant_reference = $this->input->get('pesapal_merchant_reference');
    
    if (empty($tracking_id) || empty($merchant_reference)) {
        // log_message('error', 'Pesapal callback missing parameters');
        redirect(base_url('userrole/invoice'));
    }
    
    // Verify payment status
    $status = $this->pesapal_lib->getPaymentStatus($tracking_id, $merchant_reference);
    
    if ($status == 'COMPLETED') {
        $params = $this->session->userdata('pesapal_params');
        
        if ($params && $merchant_reference == $this->session->userdata('pesapal_order_id')) {
            // Save payment record
            $payment_data = array(
                'allocation_id' => $params['allocation_id'],
                'type_id' => $params['type_id'],
                'collect_by' => get_loggedin_user_id(),
                'amount' => $params['amount'],
                'discount' => 0,
                'fine' => $params['fine'],
                'pay_via' => 'pesapal',
                'remarks' => 'Pesapal Payment - Tracking ID: ' . $tracking_id,
                'date' => date('Y-m-d')
            );
            
            $this->db->insert('fee_payment_history', $payment_data);
            
            // Send SMS notification
            $this->_send_payment_sms($params['student_id'], $params['amount'], $params['fine']);
            
            // Clear session
            $this->session->unset_userdata('pesapal_params');
            $this->session->unset_userdata('pesapal_order_id');
            
            set_alert('success', 'Payment successful!');
        } else {
            set_alert('error', 'Payment verification failed');
        }
    } else {
        set_alert('error', 'Payment status: ' . $status);
    }
    
    redirect(base_url('userrole/invoice'));
}

/**
 * Send payment confirmation SMS
 */
private function _send_payment_sms($student_id, $amount, $fine)
{
    $this->load->model('sms_model');
    
    $total_paid = $amount + $fine;
    
    $sms_data = array(
        'student_id' => $student_id,
        'amount' => $total_paid,
        'paid_date' => date('Y-m-d H:i:s'),
    );
    
    $this->sms_model->send_sms($sms_data, 2);
}


public function mpesa_waiting($checkout_id, $transaction_id)
{
    $params = $this->session->userdata('payment_params');
    
    // Set data for main layout
    $this->data['title'] = 'M-Pesa Payment';
    $this->data['sub_page'] = 'fees/mpesa_waiting';
    $this->data['main_menu'] = 'fees';
    $this->data['checkout_request_id'] = $checkout_id;
    $this->data['transaction_id'] = $transaction_id;
    $this->data['amount'] = $params['amount'] + $params['fine'];
    $this->data['phone'] = $params['phone'];
    
    // Load the main layout index
    $this->load->view('layout/index', $this->data);
}


public function manual_confirm($transaction_id)
{
    $transaction = $this->db->where('id', $transaction_id)->get('mpesa_stk_transactions')->row();
    
    if (!$transaction) {
        echo "Transaction not found!";
        return;
    }
    
    if ($transaction->status == 'completed') {
        echo "Transaction already completed!";
        return;
    }
    
    // Update transaction status
    $this->db->where('id', $transaction_id);
    $this->db->update('mpesa_stk_transactions', array(
        'status' => 'completed',
        'mpesa_receipt' => 'MANUAL_' . time(),
        'updated_at' => date('Y-m-d H:i:s')
    ));
    
    // Process the payment
    $this->_process_mpesa_payment($transaction);
    
    echo "Payment confirmed manually! Transaction ID: " . $transaction_id;
}

/**
 * M-Pesa Callback - Safaricom webhook
 * This method is public - no authentication required
 */
public function mpesa_callback()
{
    // Get callback data
    $callback_data = json_decode(file_get_contents('php://input'), true);
    
    // Log for debugging
    // log_message('info', 'MPESA CALLBACK RECEIVED: ' . json_encode($callback_data));
    
    // Check if valid callback
    if (!isset($callback_data['Body']['stkCallback'])) {
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback']);
        return;
    }
    
    $callback = $callback_data['Body']['stkCallback'];
    $checkout_id = $callback['CheckoutRequestID'];
    $result_code = $callback['ResultCode'];
    
    // Find transaction
    $transaction = $this->db->where('checkout_request_id', $checkout_id)->get('mpesa_stk_transactions')->row();
    
    if (!$transaction) {
        // log_message('error', 'MPESA Callback: Transaction not found for ID: ' . $checkout_id);
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Transaction not found']);
        return;
    }
    
    // Update transaction status
    $update = [
        'status' => ($result_code == 0) ? 'completed' : 'failed',
        'callback_raw' => json_encode($callback),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Get receipt number if successful
    if ($result_code == 0 && isset($callback['CallbackMetadata']['Item'])) {
        foreach ($callback['CallbackMetadata']['Item'] as $item) {
            if ($item['Name'] == 'MpesaReceiptNumber') {
                $update['mpesa_receipt'] = $item['Value'];
            }
        }
    }
    
    $this->db->where('id', $transaction->id)->update('mpesa_stk_transactions', $update);
    
    // Process payment only if successful and not already processed
    if ($result_code == 0 && $transaction->status != 'completed') {
        // Calculate balance
        $balance_data = $this->fees_model->getBalance($transaction->allocation_id, $transaction->type_id);
        $new_balance = ($balance_data['balance'] ?? 0) - $transaction->amount;
        
        // Add payment record
        $this->db->insert('fee_payment_history', [
            'allocation_id' => $transaction->allocation_id,
            'type_id' => $transaction->type_id,
            'collect_by' => $transaction->student_id,
            'amount' => $transaction->amount,
            'discount' => 0,
            'fine' => $transaction->fine,
            'pay_via' => 'mpesa_stk',
            'remarks' => 'M-Pesa STK - ' . ($update['mpesa_receipt'] ?? 'RECEIPT_' . time()),
            'date' => date('Y-m-d')
        ]);
        
        // log_message('info', 'MPESA Payment processed for student: ' . $transaction->student_id);
    }
    
    // Always respond with success to Safaricom
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
}
}
