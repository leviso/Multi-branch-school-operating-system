<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pesapal extends CI_Controller
{
    private $consumer_key;
    private $consumer_secret;
    private $is_demo;
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('fees_model');
        $this->load->model('userrole_model');
        $this->load->library('Pesapal_OAuth');
        
        // Load Pesapal configuration
        $this->load_pesapal_config();
    }
    
    private function load_pesapal_config()
    {
        // Get branch ID for configuration
        $branch_id = 1;
        if (function_exists('get_loggedin_branch_id') && get_loggedin_branch_id()) {
            $branch_id = get_loggedin_branch_id();
        }
        
        // Try to get from payment_config table
        $this->db->where('branch_id', $branch_id);
        $config = $this->db->get('payment_config')->row();
        
        if ($config && isset($config->pesapal_status) && $config->pesapal_status == 1) {
            $this->consumer_key = $config->pesapal_consumer_key ?? '';
            $this->consumer_secret = $config->pesapal_consumer_secret ?? '';
            $this->is_demo = $config->pesapal_sandbox ?? true;
        } else {
            // Fallback to config file or hardcoded for demo (replace with your actual keys)
            $this->consumer_key = 'YOUR_PESAPAL_CONSUMER_KEY';
            $this->consumer_secret = 'YOUR_PESAPAL_CONSUMER_SECRET';
            $this->is_demo = true; // Set to false for production
        }
    }
    
    /**
     * Initiate Pesapal payment (called via AJAX)
     */
    public function initiate()
    {
        $this->output->set_content_type('application/json');
        
        $allocation_id = $this->input->post('allocation_id');
        $type_id = $this->input->post('type_id');
        $amount = $this->input->post('amount');
        $fine = $this->input->post('fine', 0);
        $email = $this->input->post('email');
        $phone = $this->input->post('phone');
        
        // Get student details
        $student = $this->userrole_model->getStudentDetails();
        $student_id = $student['student_id'];
        $student_name = $student['fullname'];
        $student_email = $student['student_email'];
        
        $total_amount = floatval($amount) + floatval($fine);
        
        // Use student email if not provided
        if (empty($email)) {
            $email = $student_email;
        }
        
        // Generate unique merchant reference
        $merchant_reference = 'FEE_' . $student_id . '_' . time() . '_' . rand(100, 999);
        
        // Save transaction
        $transaction_data = [
            'branch_id' => get_loggedin_branch_id(),
            'student_id' => $student_id,
            'allocation_id' => $allocation_id,
            'type_id' => $type_id,
            'amount' => $amount,
            'fine' => $fine,
            'pesapal_merchant_reference' => $merchant_reference,
            'payment_status' => 'PENDING',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('pesapal_transactions', $transaction_data);
        
        // Build POST data for Pesapal
        $post_xml = $this->build_payment_request_xml($merchant_reference, $total_amount, $email, $phone, $student_name);
        
        $url = $this->is_demo 
            ? 'https://demo.pesapal.com/api/PostPesapalDirectOrderV4'
            : 'https://www.pesapal.com/api/PostPesapalDirectOrderV4';
        
        // Send request to Pesapal
        $result = $this->send_pesapal_request($url, $post_xml);
        
        if ($result['success']) {
            // Update transaction with tracking ID
            $this->db->where('pesapal_merchant_reference', $merchant_reference);
            $this->db->update('pesapal_transactions', [
                'pesapal_transaction_tracking_id' => $result['tracking_id']
            ]);
            
            echo json_encode([
                'success' => true,
                'redirect_url' => $result['redirect_url']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
    }
    
    /**
     * Send request to Pesapal using manual OAuth
     */
    private function send_pesapal_request($url, $post_xml)
    {
        // Generate OAuth Authorization header
        $callback_url = base_url('pesapal/ipn');
        $auth_header = Pesapal_OAuth::getAuthorizationHeader('POST', $url, $this->consumer_key, $this->consumer_secret, $callback_url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml',
            $auth_header
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // log_message('info', 'Pesapal Response HTTP: ' . $http_code);
        // log_message('info', 'Pesapal Response Body: ' . $response);
        
        if ($curl_error) {
            return ['success' => false, 'message' => 'CURL Error: ' . $curl_error];
        }
        
        if ($http_code != 200) {
            return ['success' => false, 'message' => 'HTTP Error: ' . $http_code];
        }
        
        // Parse XML response
        $xml = simplexml_load_string($response);
        
        if ($xml === false) {
            return ['success' => false, 'message' => 'Invalid response from Pesapal'];
        }
        
        if (isset($xml->Error)) {
            return ['success' => false, 'message' => (string)$xml->Error];
        }
        
        if (isset($xml->RedirectURL)) {
            return [
                'success' => true,
                'redirect_url' => (string)$xml->RedirectURL,
                'tracking_id' => (string)$xml->pesapal_transaction_tracking_id
            ];
        }
        
        return ['success' => false, 'message' => 'Unknown response from Pesapal'];
    }
    
    /**
     * Build payment request XML
     */
    private function build_payment_request_xml($merchant_reference, $amount, $email, $phone, $student_name)
    {
        $name_parts = explode(' ', $student_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        $amount_formatted = number_format($amount, 2, '.', '');
        
        return '<?xml version="1.0" encoding="utf-8"?>
        <PesapalDirectOrderInfo 
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
            xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
            Amount="' . $amount_formatted . '" 
            Description="School Fees Payment" 
            Type="MERCHANT" 
            Reference="' . htmlspecialchars($merchant_reference) . '" 
            FirstName="' . htmlspecialchars($first_name) . '" 
            LastName="' . htmlspecialchars($last_name) . '" 
            Email="' . htmlspecialchars($email) . '" 
            PhoneNumber="' . htmlspecialchars($phone) . '" 
            Currency="KES" 
            xmlns="http://www.pesapal.com" />';
    }
    
    /**
     * IPN (Instant Payment Notification) endpoint
     */
    public function ipn()
    {
        $pesapal_notification = file_get_contents('php://input');
        // log_message('info', 'Pesapal IPN Received: ' . $pesapal_notification);
        
        // Parse notification
        parse_str($pesapal_notification, $notification);
        
        $pesapal_tracking_id = $notification['pesapal_transaction_tracking_id'] ?? null;
        $merchant_reference = $notification['pesapal_merchant_reference'] ?? null;
        
        if (!$pesapal_tracking_id || !$merchant_reference) {
            // log_message('error', 'Invalid Pesapal IPN - missing tracking ID or merchant reference');
            echo "Invalid parameters";
            return;
        }
        
        // Verify payment status
        $status = $this->query_payment_status($pesapal_tracking_id);
        
        if ($status === 'COMPLETED') {
            $this->complete_payment($merchant_reference, $pesapal_tracking_id);
            // log_message('info', "Pesapal IPN: Payment completed for {$merchant_reference}");
        } else {
            // log_message('info', "Pesapal IPN: Payment status {$status} for {$merchant_reference}");
        }
        
        echo "OK";
    }
    
    /**
     * Return URL after payment (User redirects here)
     */
    public function return_url()
    {
        $pesapal_tracking_id = $this->input->get('pesapal_transaction_tracking_id');
        $merchant_reference = $this->input->get('pesapal_merchant_reference');
        
        if ($pesapal_tracking_id && $merchant_reference) {
            $status = $this->query_payment_status($pesapal_tracking_id);
            
            if ($status === 'COMPLETED') {
                $this->complete_payment($merchant_reference, $pesapal_tracking_id);
                $this->session->set_flashdata('alert-message-success', 'Payment completed successfully!');
            } else {
                $this->session->set_flashdata('alert-message-error', 'Payment was not completed. Please try again.');
            }
        }
        
        redirect(base_url('userrole/invoice'));
    }
    
    /**
     * Query payment status from Pesapal
     */
    private function query_payment_status($pesapal_tracking_id)
    {
        $url = $this->is_demo 
            ? 'https://demo.pesapal.com/api/QueryPaymentDetails'
            : 'https://www.pesapal.com/api/QueryPaymentDetails';
        
        $post_xml = '<?xml version="1.0" encoding="utf-8"?>
        <PesapalDirectOrderInfo 
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
            xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
            pesapal_transaction_tracking_id="' . htmlspecialchars($pesapal_tracking_id) . '" 
            xmlns="http://www.pesapal.com" />';
        
        $result = $this->send_pesapal_query($url, $post_xml);
        
        if ($result['success'] && isset($result['status'])) {
            return $result['status'];
        }
        
        return 'PENDING';
    }
    
    /**
     * Send query to Pesapal
     */
    private function send_pesapal_query($url, $post_xml)
    {
        // Generate OAuth Authorization header for query
        $auth_header = Pesapal_OAuth::getAuthorizationHeader('POST', $url, $this->consumer_key, $this->consumer_secret, null);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml',
            $auth_header
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $xml = simplexml_load_string($response);
        
        if ($xml === false) {
            return ['success' => false];
        }
        
        return [
            'success' => true,
            'status' => isset($xml->PaymentStatusDetail) ? (string)$xml->PaymentStatusDetail : 'PENDING',
            'amount' => isset($xml->Amount) ? (string)$xml->Amount : null,
            'mpesa_receipt' => isset($xml->MpesaReceiptNumber) ? (string)$xml->MpesaReceiptNumber : null
        ];
    }
    
    /**
     * Complete payment after verification
     */
    private function complete_payment($merchant_reference, $pesapal_tracking_id)
    {
        $this->db->trans_start();
        
        $transaction = $this->db->get_where('pesapal_transactions', [
            'pesapal_merchant_reference' => $merchant_reference
        ])->row();
        
        if (!$transaction) {
            // log_message('error', "Pesapal transaction not found: {$merchant_reference}");
            return false;
        }
        
        if ($transaction->payment_status == 'COMPLETED') {
            // log_message('info', "Pesapal transaction already completed: {$merchant_reference}");
            return true;
        }
        
        // Insert payment record
        $payment_data = [
            'allocation_id' => $transaction->allocation_id,
            'type_id' => $transaction->type_id,
            'collect_by' => 'online',
            'amount' => $transaction->amount,
            'discount' => 0,
            'fine' => $transaction->fine,
            'pay_via' => $this->get_payment_type_id('Pesapal'),
            'remarks' => "Pesapal Payment - Ref: {$pesapal_tracking_id}",
            'date' => date('Y-m-d')
        ];
        
        $this->db->insert('fee_payment_history', $payment_data);
        $payment_id = $this->db->insert_id();
        
        // Update transaction
        $this->db->where('id', $transaction->id);
        $this->db->update('pesapal_transactions', [
            'payment_status' => 'COMPLETED',
            'payment_id' => $payment_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            // log_message('error', "Pesapal payment completion failed: {$merchant_reference}");
            return false;
        }
        
        // Send SMS notification
        $this->send_pesapal_sms($transaction, $payment_id);
        
        // log_message('info', "Pesapal payment completed: {$merchant_reference}");
        return true;
    }
    
    /**
     * Send SMS notification for Pesapal payment
     */
    private function send_pesapal_sms($transaction, $payment_id)
    {
        // Get student details
        $this->db->select('s.first_name, s.last_name, s.mobileno, p.mobileno as parent_mobile');
        $this->db->from('student s');
        $this->db->join('parent p', 'p.id = s.parent_id', 'left');
        $this->db->where('s.id', $transaction->student_id);
        $student = $this->db->get()->row();
        
        $mobile = $student->parent_mobile ?: $student->mobileno;
        
        if ($mobile) {
            $total_amount = $transaction->amount + $transaction->fine;
            $message = "Payment received: KES " . number_format($total_amount, 2) . " for {$student->first_name} {$student->last_name}. Payment via Pesapal. Thank you.";
            
            $this->load->library('bulksmsbd', ['branch_id' => $transaction->branch_id], 'sms_lib');
            $this->sms_lib->send($mobile, $message);
        }
    }
    
    /**
     * Get payment type ID by name
     */
    private function get_payment_type_id($name)
    {
        $this->db->select('id');
        $this->db->where('name', $name);
        $type = $this->db->get('payment_types')->row();
        
        if (!$type) {
            // Insert if not exists
            $this->db->insert('payment_types', ['name' => $name, 'branch_id' => 1]);
            return $this->db->insert_id();
        }
        
        return $type->id;
    }
}