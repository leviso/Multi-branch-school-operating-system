<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_callback extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('feespayment_model');
        $this->load->model('sendsmsmail_model');
    }

    /**
     * Unified callback endpoint for all M-Pesa payments
     */
    public function index()
    {
        // Get the raw callback data
        $callback_data = file_get_contents('php://input');
        // log_message('info', 'Unified Callback Received: ' . $callback_data);
        
        header('Content-Type: application/json');
        
        try {
            $data = json_decode($callback_data, true);
            
            if (!isset($data['Body']['stkCallback'])) {
                echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback']);
                return;
            }
            
            $checkout_id = $data['Body']['stkCallback']['CheckoutRequestID'];
            // log_message('info', 'Processing callback for CheckoutID: ' . $checkout_id);
            
            // Route based on checkout_id prefix
            if (strpos($checkout_id, 'SUB_') !== false) {
                // Subscription payment
                // log_message('info', 'Routing to subscription handler');
                $this->load->controller('subscription/payment_callback');
                
            } elseif (strpos($checkout_id, 'SMS_') !== false) {
                // SMS credit purchase
                // log_message('info', 'Routing to SMS purchase handler');
                $this->load->controller('sendsmsmail/payment_callback');
                
            } elseif (strpos($checkout_id, 'FEE_') !== false) {
                // Fees payment - NEW
                // log_message('info', 'Routing to fees STK handler');
                $this->handle_fees_stk_callback($data, $callback_data);
                
            } else {
                // log_message('warning', 'Unknown callback type for CheckoutID: ' . $checkout_id);
            }
            
        } catch (Exception $e) {
            // log_message('error', 'Callback error: ' . $e->getMessage());
        }
        
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }
    
    /**
     * Handle fees STK callback
     */
    private function handle_fees_stk_callback($data, $callback_data)
    {
        $callback = $data['Body']['stkCallback'];
        $checkout_id = $callback['CheckoutRequestID'];
        $result_code = $callback['ResultCode'];
        $result_desc = $callback['ResultDesc'];
        
        // log_message('info', "Fees STK Callback - CheckoutID: {$checkout_id}, Code: {$result_code}");
        
        // Find pending transaction
        $this->db->where('checkout_request_id', $checkout_id);
        $transaction = $this->db->get('mpesa_stk_transactions')->row();
        
        if (!$transaction) {
            // log_message('error', "No pending STK transaction found for: {$checkout_id}");
            return;
        }
        
        $update_data = [
            'callback_raw' => $callback_data,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($result_code == 0) {
            // Payment successful
            $receipt = null;
            
            if (isset($callback['CallbackMetadata']['Item'])) {
                foreach ($callback['CallbackMetadata']['Item'] as $item) {
                    if ($item['Name'] == 'MpesaReceiptNumber') {
                        $receipt = $item['Value'];
                        break;
                    }
                }
            }
            
            if (empty($receipt)) {
                $receipt = 'FEE_' . date('YmdHis') . '_' . rand(1000, 9999);
            }
            
            $update_data['status'] = 'completed';
            $update_data['mpesa_receipt'] = $receipt;
            
            // Process the payment
            $this->process_fees_payment($transaction, $receipt, $callback_data);
            
        } else {
            // Payment failed
            $status = 'failed';
            if ($result_code == '1032') $status = 'cancelled';
            if ($result_code == '1037') $status = 'timeout';
            $update_data['status'] = $status;
            // log_message('info', "Fees payment failed: {$status}");
        }
        
        $this->db->where('id', $transaction->id);
        $this->db->update('mpesa_stk_transactions', $update_data);
    }
    
    /**
     * Process successful fees payment
     */
    private function process_fees_payment($transaction, $receipt, $callback_data)
    {
        $this->db->trans_start();
        
        try {
            // Get student details
            $student = $this->db->select('s.id, s.first_name, s.last_name, s.email, s.mobileno, s.parent_id, p.mobileno as parent_mobile, p.name as parent_name')
                ->from('student s')
                ->join('parent p', 'p.id = s.parent_id', 'left')
                ->where('s.id', $transaction->student_id)
                ->get()->row();
            
            // Calculate total amount
            $total_amount = $transaction->amount + $transaction->fine;
            
            // Insert into fee_payment_history
            $payment_data = [
                'allocation_id' => $transaction->allocation_id,
                'type_id' => $transaction->type_id,
                'collect_by' => 'online',
                'amount' => $transaction->amount,
                'discount' => 0,
                'fine' => $transaction->fine,
                'pay_via' => $this->get_payment_type_id('M-Pesa STK Push'),
                'remarks' => "M-Pesa Fees Payment - Receipt: {$receipt}",
                'date' => date('Y-m-d')
            ];
            
            $this->db->insert('fee_payment_history', $payment_data);
            $payment_id = $this->db->insert_id();
            
            // Update STK transaction with payment_id
            $this->db->where('id', $transaction->id);
            $this->db->update('mpesa_stk_transactions', ['payment_id' => $payment_id]);
            
            // Send SMS notification to parent
            $this->send_fees_payment_sms($student, $total_amount, $receipt);
            
            $this->db->trans_complete();
            
            // log_message('info', "Fees payment processed - Student: {$transaction->student_id}, Amount: {$total_amount}, Receipt: {$receipt}");
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            // log_message('error', 'Failed to process fees payment: ' . $e->getMessage());
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
        return $type ? $type->id : 5; // Default to 5 (Cash)
    }
    
    /**
     * Send SMS notification for fees payment
     */
    private function send_fees_payment_sms($student, $amount, $receipt)
    {
        $mobile = !empty($student->parent_mobile) ? $student->parent_mobile : $student->mobileno;
        
        if (empty($mobile)) {
            // log_message('warning', "No mobile number for student {$student->id}");
            return;
        }
        
        $message = "Dear Parent, KES " . number_format($amount, 2) . " fees payment received for {$student->first_name} {$student->last_name}. Receipt: {$receipt}. Thank you.";
        
        $this->load->library('bulksmsbd', ['branch_id' => $transaction->branch_id ?? 1], 'sms_lib');
        $response = $this->sms_lib->send($mobile, $message);
        
        // log_message('info', "Fees SMS sent to {$mobile}: " . substr($response, 0, 100));
    }
}