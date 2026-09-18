<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa extends CI_Controller
{
    private $shortcode;
    private $consumer_key;
    private $consumer_secret;
    private $passkey;
    private $environment;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('fees_model');
        $this->load->model('sendsmsmail_model');
        $this->load->model('userrole_model');
        
        // Load M-Pesa configuration
        $this->load->config('payment_gateway');
        $config = $this->config->item('payment_gateway');
        
        $this->shortcode = $config['shortcode'];
        $this->consumer_key = $config['consumer_key'];
        $this->consumer_secret = $config['consumer_secret'];
        $this->passkey = $config['passkey'];
        $this->environment = $config['environment'] ?? 'sandbox';
    }

    /**
     * Get OAuth Token
     */
    private function get_access_token()
    {
        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        
        $url = ($this->environment == 'sandbox') 
            ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            $result = json_decode($response, true);
            return $result['access_token'] ?? null;
        }
        
        // log_message('error', 'Failed to get M-Pesa token: ' . $response);
        return null;
    }

    /**
     * C2B Validation endpoint
     * M-Pesa calls this to validate if transaction can proceed
     */
    public function validation()
    {
        $callback_data = file_get_contents('php://input');
        // log_message('info', 'C2B Validation Received: ' . $callback_data);
        
        // Always return success to allow transaction
        $response = [
            'ResultCode' => 0,
            'ResultDesc' => 'Success'
        ];
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * C2B Confirmation endpoint
     * M-Pesa calls this after successful payment
     */
    public function confirmation()
    {
        $callback_data = file_get_contents('php://input');
        // log_message('info', 'C2B Confirmation Received: ' . $callback_data);
        
        $data = json_decode($callback_data, true);
        
        if (!$data) {
            // log_message('error', 'Invalid C2B confirmation data');
            $response = ['ResultCode' => 1, 'ResultDesc' => 'Invalid data'];
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }
        
        // Extract transaction details
        $trans_id = $data['TransID'] ?? null;
        $trans_time = $data['TransTime'] ?? null;
        $amount = $data['TransAmount'] ?? 0;
        $bill_ref = $data['BillRefNumber'] ?? null;
        $msisdn = $data['MSISDN'] ?? null;
        $first_name = $data['FirstName'] ?? '';
        $last_name = $data['LastName'] ?? '';
        
        if (!$trans_id || !$bill_ref || !$amount) {
            // log_message('error', 'Missing required C2B fields');
            $response = ['ResultCode' => 1, 'ResultDesc' => 'Missing fields'];
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }
        
        // Check if transaction already processed
        $this->db->where('trans_id', $trans_id);
        $exists = $this->db->get('mpesa_c2b_transactions')->row();
        
        if ($exists) {
            // log_message('info', 'C2B Transaction already processed: ' . $trans_id);
            $response = ['ResultCode' => 0, 'ResultDesc' => 'Success'];
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }
        
        // Find student by admission number (BillRefNumber)
        $this->db->where('register_no', $bill_ref);
        $student = $this->db->get('student')->row();
        
        if (!$student) {
            // log_message('error', 'Student not found for admission number: ' . $bill_ref);
            $this->save_c2b_transaction($data, 'invalid_account', null);
            $response = ['ResultCode' => 0, 'ResultDesc' => 'Success'];
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }
        
        // Get student's fee allocation for current session
        $this->db->where('student_id', $student->id);
        $this->db->where('session_id', get_session_id());
        $allocation = $this->db->get('fee_allocation')->row();
        
        if (!$allocation) {
            // log_message('error', 'No fee allocation for student: ' . $student->id);
            $this->save_c2b_transaction($data, 'failed', $student->id);
            $response = ['ResultCode' => 0, 'ResultDesc' => 'Success'];
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }
        
        // Save C2B transaction
        $c2b_id = $this->save_c2b_transaction($data, 'pending', $student->id);
        
        // Process payment
        $result = $this->process_c2b_payment($student, $allocation, $amount, $trans_id);
        
        if ($result['success']) {
            $this->db->where('id', $c2b_id);
            $this->db->update('mpesa_c2b_transactions', [
                'status' => 'processed',
                'payment_id' => $result['payment_id'],
                'processed_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send SMS notification
            $this->send_payment_sms($student, $amount, $trans_id);
            // log_message('info', "C2B Payment processed successfully: {$trans_id} for student {$student->id}");
        } else {
            $this->db->where('id', $c2b_id);
            $this->db->update('mpesa_c2b_transactions', [
                'status' => 'failed',
                'processed_at' => date('Y-m-d H:i:s')
            ]);
            // log_message('error', "C2B Payment failed: {$trans_id}");
        }
        
        $response = ['ResultCode' => 0, 'ResultDesc' => 'Success'];
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }
    
    /**
     * Save C2B transaction to database
     */
    private function save_c2b_transaction($data, $status, $student_id = null)
    {
        $branch_id = null;
        if ($student_id) {
            $this->db->select('branch_id');
            $this->db->where('id', $student_id);
            $student = $this->db->get('student')->row();
            $branch_id = $student ? $student->branch_id : null;
        }
        
        $insert = [
            'branch_id' => $branch_id,
            'student_id' => $student_id,
            'transaction_type' => $data['TransactionType'] ?? null,
            'trans_id' => $data['TransID'] ?? null,
            'trans_time' => isset($data['TransTime']) ? date('Y-m-d H:i:s', strtotime($data['TransTime'])) : null,
            'trans_amount' => $data['TransAmount'] ?? 0,
            'business_shortcode' => $data['BusinessShortCode'] ?? null,
            'bill_ref_number' => $data['BillRefNumber'] ?? null,
            'invoice_number' => $data['InvoiceNumber'] ?? null,
            'org_account_balance' => $data['OrgAccountBalance'] ?? null,
            'third_party_trans_id' => $data['ThirdPartyTransID'] ?? null,
            'msisdn' => $data['MSISDN'] ?? null,
            'first_name' => $data['FirstName'] ?? null,
            'middle_name' => $data['MiddleName'] ?? null,
            'last_name' => $data['LastName'] ?? null,
            'status' => $status,
            'callback_raw' => json_encode($data),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('mpesa_c2b_transactions', $insert);
        return $this->db->insert_id();
    }
    
    /**
     * Process C2B payment and update fee records
     */
    private function process_c2b_payment($student, $allocation, $amount, $trans_id)
    {
        $this->db->trans_start();
        
        try {
            // Get all fee types for this allocation that have balance
            $this->db->select('fgd.fee_type_id, fgd.amount, fgd.fee_groups_id, ft.name');
            $this->db->from('fee_groups_details fgd');
            $this->db->join('fees_type ft', 'ft.id = fgd.fee_type_id');
            $this->db->where('fgd.fee_groups_id', $allocation->group_id);
            $fee_types = $this->db->get()->result();
            
            $remaining = floatval($amount);
            $payment_ids = [];
            
            foreach ($fee_types as $fee) {
                if ($remaining <= 0) break;
                
                // Get paid amount for this fee type
                $this->db->select('SUM(amount) as paid, SUM(discount) as discount, SUM(fine) as fine');
                $this->db->where('allocation_id', $allocation->id);
                $this->db->where('type_id', $fee->fee_type_id);
                $paid_data = $this->db->get('fee_payment_history')->row();
                
                $paid_amount = floatval($paid_data->paid ?? 0);
                $balance = floatval($fee->amount) - $paid_amount;
                
                if ($balance > 0) {
                    $pay_amount = min($remaining, $balance);
                    
                    $payment_data = [
                        'allocation_id' => $allocation->id,
                        'type_id' => $fee->fee_type_id,
                        'collect_by' => 'online',
                        'amount' => $pay_amount,
                        'discount' => 0,
                        'fine' => 0,
                        'pay_via' => $this->get_payment_type_id('M-Pesa Paybill'),
                        'remarks' => "M-Pesa Paybill Payment - Ref: {$trans_id}",
                        'date' => date('Y-m-d')
                    ];
                    
                    $this->db->insert('fee_payment_history', $payment_data);
                    $payment_ids[] = $this->db->insert_id();
                    $remaining -= $pay_amount;
                }
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            return [
                'success' => true,
                'payment_id' => implode(',', $payment_ids)
            ];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            // log_message('error', 'C2B Payment processing failed: ' . $e->getMessage());
            return ['success' => false];
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
        return $type ? $type->id : 5;
    }
    
    /**
     * Send SMS notification for successful payment
     */
    private function send_payment_sms($student, $amount, $trans_id)
    {
        // Get parent mobile
        $this->db->select('p.mobileno');
        $this->db->from('parent p');
        $this->db->where('p.id', $student->parent_id);
        $parent = $this->db->get()->row();
        
        $mobile = $parent ? $parent->mobileno : $student->mobileno;
        
        if ($mobile) {
            $message = "Payment received: KES " . number_format($amount, 2) . " for {$student->first_name} {$student->last_name}. Transaction ID: {$trans_id}. Thank you.";
            
            $this->load->library('bulksmsbd', ['branch_id' => $student->branch_id], 'sms_lib');
            $this->sms_lib->send($mobile, $message);
        }
    }
}