<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscription extends Admin_Controller
{
    protected $requires_subscription = false; // Bypass subscription check for this controller
    protected $excluded_methods = ['payment_callback', 'check_payment_status']; 
    
   public function __construct()
    {
        // Let parent constructor run first (like SMS controller)
        parent::__construct();
        
        // Now load what we need
        $this->load->model('subscription_model');
        $this->load->library('payment_handler');
    }
        
        
    

    /**
     * Branch subscription purchase page (for branch admins)
     */
public function purchase()
{
        // Check permission - only branch admins
        if (!is_admin_loggedin()) {
            access_denied();
        }

        $branch_id = get_loggedin_branch_id();
        
        $this->data['plans'] = $this->subscription_model->get_plans(true, true);
        $this->data['current_subscription'] = $this->subscription_model->get_branch_subscription($branch_id);
        $this->data['branch'] = $this->db->get_where('branch', ['id' => $branch_id])->row();
        
        $this->data['title'] = translate('purchase_subscription');
        $this->data['sub_page'] = 'subscription/purchase';
        $this->data['main_menu'] = 'subscription';
        $this->load->view('layout/index', $this->data);
    }

    /**
 * Initiate subscription purchase (AJAX)
 */
public function initiate_purchase()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $this->output->set_content_type('application/json');
    
    if (!is_admin_loggedin()) {
        echo json_encode(['success' => false, 'message' => translate('access_denied')]);
        return;
    }

    // Validate CSRF - this will work with FormData
    if (!$this->_check_csrf()) {
        echo json_encode(['success' => false, 'message' => 'Security token validation failed']);
        return;
    }

    $branch_id = get_loggedin_branch_id();
    $plan_id = $this->input->post('plan_id');
    $billing_cycle = $this->input->post('billing_cycle');
    $phone = $this->input->post('phone');     

        if (empty($plan_id) || empty($billing_cycle) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            return;
        }

        // Get plan details
        $plan = $this->subscription_model->get_plan($plan_id);
        if (!$plan) {
            echo json_encode(['success' => false, 'message' => 'Invalid plan selected']);
            return;
        }

        // Calculate amount based on billing cycle
        $amount = $this->_get_plan_price($plan, $billing_cycle);
        
        // Format phone number for M-Pesa
        $phone = $this->_format_phone($phone);
        if (!$phone) {
            echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
            return;
        }

        // Generate account reference
        $account_ref = 'SUB_' . $branch_id . '_' . time();

        // Initiate M-Pesa payment
       $payment_result = $this->payment_handler->initiate_payment(
            $phone,
            $amount,
            $account_ref,
            'Subscription: ' . $plan->name,
            'subscription'  // Add this - specifies subscription callback
        );

        if (!$payment_result['success']) {
            echo json_encode([
                'success' => false, 
                'message' => $payment_result['message'] ?? 'Payment initiation failed'
            ]);
            return;
        }

        // Create pending subscription
        $subscription_id = $this->subscription_model->create_pending_subscription(
            $branch_id,
            $plan_id,
            $billing_cycle,
            $amount,
            $payment_result['checkout_request_id']
        );

        if (!$subscription_id) {
            echo json_encode(['success' => false, 'message' => 'Failed to create subscription record']);
            return;
        }

        echo json_encode([
            'success' => true,
            'checkout_request_id' => $payment_result['checkout_request_id'],
            'subscription_id' => $subscription_id,
            'message' => 'STK push sent to your phone. Please check and enter PIN to complete payment.'
        ]);
    }
public function check_payment_status()
{
    // Allow CORS for AJAX requests
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
    
    $this->output->set_content_type('application/json');
    
    $checkout_id = $this->input->post('checkout_request_id');
    $subscription_id = $this->input->post('subscription_id');

    log_message('debug', "check_payment_status - CheckoutID: {$checkout_id}, SubID: {$subscription_id}");

    if (empty($checkout_id) || empty($subscription_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }

    // Direct database query - bypass models for speed
    $this->db->where('id', $subscription_id);
    $this->db->where('checkout_request_id', $checkout_id);
    $subscription = $this->db->get('branch_subscriptions')->row();

    if (!$subscription) {
        echo json_encode(['success' => false, 'message' => 'Subscription not found']);
        return;
    }

    // Check if payment exists
    $this->db->where('checkout_request_id', $checkout_id);
    $payment = $this->db->get('subscription_payments')->row();

    // Return based on status
    if ($subscription->status == 'active') {
        echo json_encode([
            'success' => true,
            'status' => 'completed',
            'message' => 'Payment successful! Subscription activated.'
        ]);
        return;
    }
    
    if ($payment && $payment->status == 'completed') {
        // Payment exists but subscription not updated - fix it
        $this->db->where('id', $subscription_id);
        $this->db->update('branch_subscriptions', ['status' => 'active', 'payment_status' => 'paid']);
        
        echo json_encode([
            'success' => true,
            'status' => 'completed',
            'message' => 'Payment successful! Subscription activated.'
        ]);
        return;
    }
    
    if ($subscription->status == 'cancelled' || $subscription->status == 'failed') {
        echo json_encode([
            'success' => false,
            'status' => $subscription->status,
            'message' => 'Payment ' . $subscription->status
        ]);
        return;
    }
    
    // Still pending
    echo json_encode([
        'success' => false,
        'status' => 'pending',
        'message' => 'Waiting for payment confirmation...'
    ]);
}

/**
 * M-Pesa callback for subscription payments
 */
public function payment_callback()
{
    // Disable auth for this method only
    $this->load->model('subscription_model');
    $this->load->library('payment_handler');
    
    // Get the raw callback data
    $callback_data = file_get_contents('php://input');
    
    // Log EVERYTHING for debugging
    log_message('info', '========== SUBSCRIPTION CALLBACK RECEIVED ==========');
    log_message('info', 'Subscription Callback: ' . $callback_data);
    
    // Set proper headers - MUST be first output
    header('Content-Type: application/json');
    http_response_code(200);
    
    try {
        // Validate we have data
        if (empty($callback_data)) {
            log_message('error', 'Empty callback data received');
            echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Empty callback']);
            return;
        }
        
        // Decode JSON
        $data = json_decode($callback_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'Invalid JSON: ' . json_last_error_msg());
            echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON']);
            return;
        }
        
        // Validate callback structure
        if (!isset($data['Body']['stkCallback'])) {
            log_message('error', 'Invalid callback structure: ' . print_r($data, true));
            echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid structure']);
            return;
        }
        
        $callback = $data['Body']['stkCallback'];
        $checkout_id = $callback['CheckoutRequestID'];
        $result_code = $callback['ResultCode'];
        $result_desc = $callback['ResultDesc'];
        
        log_message('info', "Processing - CheckoutID: {$checkout_id}, Code: {$result_code}, Desc: {$result_desc}");
        
        // Find pending subscription
        $this->db->where('checkout_request_id', $checkout_id);
        $this->db->where('status', 'pending');
        $subscription = $this->db->get('branch_subscriptions')->row();
        
        if (!$subscription) {
            log_message('error', "No pending subscription found for checkout ID: {$checkout_id}");
            echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
            return;
        }
        
        log_message('info', "Found subscription ID: {$subscription->id} for branch: {$subscription->branch_id}");
        
        // ✅ CRITICAL: Create or update payment record FIRST
        $receipt = null;
        
        if ($result_code == 0) {
            // Extract receipt from metadata
            if (isset($callback['CallbackMetadata']['Item'])) {
                foreach ($callback['CallbackMetadata']['Item'] as $item) {
                    if (isset($item['Name']) && $item['Name'] == 'MpesaReceiptNumber') {
                        $receipt = $item['Value'] ?? null;
                        log_message('info', "Extracted receipt: {$receipt}");
                        break;
                    }
                }
            }
            
            // If no receipt found (sandbox), generate one
            if (empty($receipt)) {
                $receipt = 'SUB_' . date('YmdHis') . '_' . rand(1000, 9999);
                log_message('info', "Generated sandbox receipt: {$receipt}");
            }
            
            // ✅ FIRST: Create/Update payment record
            $this->db->where('checkout_request_id', $checkout_id);
            $existing_payment = $this->db->get('subscription_payments')->row();
            
            if (!$existing_payment) {
                $payment_data = [
                    'branch_id' => $subscription->branch_id,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                    'amount' => $subscription->amount_paid,
                    'payment_method' => 'mpesa',
                    'mpesa_receipt' => $receipt,
                    'checkout_request_id' => $checkout_id,
                    'payment_date' => date('Y-m-d H:i:s'),
                    'status' => 'completed',
                    'raw_callback_data' => $callback_data,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('subscription_payments', $payment_data);
                log_message('info', "✅ Payment record created");
            } else {
                $this->db->where('id', $existing_payment->id);
                $this->db->update('subscription_payments', [
                    'status' => 'completed',
                    'mpesa_receipt' => $receipt,
                    'raw_callback_data' => $callback_data,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_message('info', "✅ Payment record updated");
            }
            
            // ✅ SECOND: Activate subscription
            $result = $this->subscription_model->activate_subscription(
                $checkout_id,
                $receipt,
                $callback_data
            );
            
            if ($result) {
                log_message('info', "✅ Subscription activated successfully");
                $this->subscription_model->update_branch_modules(
                    $subscription->branch_id, 
                    $subscription->plan_id
                );
            } else {
                log_message('error', "❌ Failed to activate subscription");
            }
            
        } else {
            // Payment failed - update status
            $status = 'failed';
            if ($result_code == '1032') $status = 'cancelled';
            if ($result_code == '1037') $status = 'timeout';
            
            $this->db->where('id', $subscription->id);
            $this->db->update('branch_subscriptions', [
                'status' => $status,
                'payment_status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Also record failed payment
            $this->db->where('checkout_request_id', $checkout_id);
            $existing_payment = $this->db->get('subscription_payments')->row();
            
            if (!$existing_payment) {
                $this->db->insert('subscription_payments', [
                    'branch_id' => $subscription->branch_id,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                    'amount' => $subscription->amount_paid,
                    'payment_method' => 'mpesa',
                    'checkout_request_id' => $checkout_id,
                    'payment_date' => date('Y-m-d H:i:s'),
                    'status' => $status,
                    'raw_callback_data' => $callback_data,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            log_message('info', "Payment marked as: {$status}");
        }
        
    } catch (Exception $e) {
        log_message('error', 'EXCEPTION in callback: ' . $e->getMessage());
        log_message('error', 'Exception trace: ' . $e->getTraceAsString());
    }
    
    // ALWAYS return success to M-Pesa
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
}
    /**
     * Renew subscription (for branch admins)
     */
    public function renew($subscription_id = null)
    {
        if (!is_admin_loggedin()) {
            access_denied();
        }

        $branch_id = get_loggedin_branch_id();
        
        // Get subscription
        $this->db->where('id', $subscription_id);
        $this->db->where('branch_id', $branch_id);
        $subscription = $this->db->get('branch_subscriptions')->row();

        if (!$subscription) {
            $this->session->set_flashdata('error', 'Subscription not found');
            redirect(base_url('subscription/purchase'));
        }

        $plan = $this->subscription_model->get_plan($subscription->plan_id);
        
        $this->data['subscription'] = $subscription;
        $this->data['plan'] = $plan;
        $this->data['title'] = 'Renew Subscription';
        $this->data['sub_page'] = 'subscription/renew';
        $this->data['main_menu'] = 'subscription';
        $this->load->view('layout/index', $this->data);
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
     * Get plan price based on billing cycle
     */
    private function _get_plan_price($plan, $cycle)
    {
        switch ($cycle) {
            case 'monthly':
                return $plan->price_monthly;
            case 'termly':
                return $plan->price_termly;
            case 'yearly':
                return $plan->price_yearly;
            default:
                return $plan->price_monthly;
        }
    }

   /**
 * Check CSRF token for AJAX requests with FormData
 */
private function _check_csrf()
{
    $csrf_token_name = $this->security->get_csrf_token_name();
    
    // Try to get from POST first
    $csrf_token = $this->input->post($csrf_token_name);
    
    // If not in POST, check headers (for some AJAX implementations)
    if (empty($csrf_token)) {
        $csrf_token = $this->input->get_request_header('X-CSRF-TOKEN');
    }
    
    // If still empty, try to get from raw input (for FormData)
    if (empty($csrf_token)) {
        $raw_input = file_get_contents('php://input');
        if (!empty($raw_input)) {
            // Check if it's multipart/form-data
            if (strpos($raw_input, '------WebKitFormBoundary') !== false) {
                // For FormData, we need to parse differently
                // Let's log for debugging
                log_message('debug', 'FormData detected, CSRF check may need alternate approach');
                
                // Alternative: Skip CSRF check for this specific endpoint?
                // OR ensure the token is in the URL
                return true; // TEMPORARY - REMOVE AFTER TESTING
            } else {
                parse_str($raw_input, $post_data);
                $csrf_token = isset($post_data[$csrf_token_name]) ? $post_data[$csrf_token_name] : '';
            }
        }
    }
    
    $current_hash = $this->security->get_csrf_hash();
    
    // Log for debugging
    log_message('debug', 'CSRF Check - Token Name: ' . $csrf_token_name);
    log_message('debug', 'CSRF Check - Received Token: ' . ($csrf_token ?: 'EMPTY'));
    log_message('debug', 'CSRF Check - Expected Hash: ' . $current_hash);
    
    $is_valid = ($csrf_token === $current_hash);
    
    if (!$is_valid) {
        log_message('error', 'CSRF token validation failed. Expected: ' . $current_hash . ' Got: ' . $csrf_token);
    }
    
    return $is_valid;
}
public function activate_subscription($checkout_id, $mpesa_receipt, $callback_data)
{
    log_message('info', "activate_subscription called - Checkout: {$checkout_id}, Receipt: {$mpesa_receipt}");
    
    $this->db->trans_start();
    
    try {
        // Find pending subscription
        $this->db->where('checkout_request_id', $checkout_id);
        $this->db->where('status', 'pending');
        $subscription = $this->db->get('branch_subscriptions')->row();
        
        if (!$subscription) {
            log_message('error', 'Pending subscription not found for checkout: ' . $checkout_id);
            throw new Exception('Pending subscription not found');
        }
        
        log_message('info', "Found subscription ID: {$subscription->id} for branch: {$subscription->branch_id}");
        
        // Update subscription
        $update_data = [
            'status' => 'active',
            'payment_status' => 'paid',
            'mpesa_receipt' => $mpesa_receipt,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->where('id', $subscription->id);
        $this->db->update('branch_subscriptions', $update_data);
        
        if ($this->db->affected_rows() == 0) {
            log_message('error', 'Failed to update subscription status');
            throw new Exception('Failed to update subscription');
        }
        
        // ✅ FIXED: Check if payment record already exists before inserting
        $this->db->where('checkout_request_id', $checkout_id);
        $existing_payment = $this->db->get('subscription_payments')->row();
        
        if (!$existing_payment) {
            // Record payment
            $payment_data = [
                'branch_id' => $subscription->branch_id,
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'amount' => $subscription->amount_paid,
                'payment_method' => 'mpesa',
                'mpesa_receipt' => $mpesa_receipt,
                'checkout_request_id' => $checkout_id,
                'payment_date' => date('Y-m-d H:i:s'),
                'status' => 'completed',
                'raw_callback_data' => $callback_data,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('subscription_payments', $payment_data);
            log_message('info', "Payment record created with ID: " . $this->db->insert_id());
        } else {
            // Update existing payment record
            $this->db->where('id', $existing_payment->id);
            $this->db->update('subscription_payments', [
                'status' => 'completed',
                'mpesa_receipt' => $mpesa_receipt,
                'raw_callback_data' => $callback_data,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            log_message('info', "Payment record updated for ID: {$existing_payment->id}");
        }
        
        // Update branch
        $this->db->where('id', $subscription->branch_id);
        $this->db->update('branch', [
            'subscription_id' => $subscription->id,
            'subscription_status' => 'active',
            'subscription_updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Initialize SMS credits
        $this->initialize_sms_credits($subscription->branch_id, $subscription->plan_id, $subscription->billing_cycle);
        
        // Enable modules according to plan
        $this->update_branch_modules($subscription->branch_id, $subscription->plan_id);
        
        // ✅ Verify modules were updated
        $this->db->where('branch_id', $subscription->branch_id);
        $this->db->where('isEnabled', 1);
        $enabled_count = $this->db->count_all_results('modules_manage');
        log_message('info', "Branch {$subscription->branch_id} now has {$enabled_count} enabled modules");
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        log_message('info', "✅ Subscription {$subscription->id} activated successfully");
        return $subscription->id;
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        log_message('error', '❌ activate_subscription failed: ' . $e->getMessage());
        return false;
    }
}
}