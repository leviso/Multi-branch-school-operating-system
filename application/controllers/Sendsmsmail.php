<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 1.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Sendsmsmail.php
 * @copyright : Reserved Synobix Team
 */

class Sendsmsmail extends Admin_Controller
{

    public function __construct()
    {

        parent::__construct();
        $this->load->library('mailer');
        $this->load->library('Bulksmsbd');
        $this->load->model('application_model');
        $this->load->helper('array');
        $this->load->helper('general_helper');
        // ADD THESE LINES
        $this->load->helper(['url', 'file']);
        $this->load->model('sendsmsmail_model');
        $this->load->library('payment_handler');
        $this->config->load('payment_gateway'); // Our new config
        $this->config->load('smsconfig');
        $this->load->library('session');
        $this->load->library('form_validation');

        if (!moduleIsEnabled('bulk_sms_and_email')) {
            access_denied();
        }
        $this->output->set_header('Content-Security-Policy: upgrade-insecure-requests');
    }

   public function sms()
{
    if (!get_permission('sendsmsmail', 'is_add')) {
        access_denied();
    }

    // Use our custom method that handles branch switching
    $branchID = $this->get_current_branch_id();
    
    //log_message('debug', 'SMS Page - Branch ID: ' . $branchID . ' (Superadmin: ' . (is_superadmin_loggedin() ? 'Yes' : 'No') . ')');
    
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/bootstrap-timepicker/css/bootstrap-timepicker.css',
        ),
        'js' => array(
            'vendor/bootstrap-timepicker/bootstrap-timepicker.js',
        ),
    );
    
    $this->data['branch_id'] = $branchID;
    $this->data['current_balance'] = $this->sendsmsmail_model->get_sms_credit($branchID);
    $this->data['title'] = translate('bulk_sms_and_email');
    $this->data['sub_page'] = 'sendsmsmail/sms';
    $this->data['main_menu'] = 'sendsmsmail';
    
    // ========== PRG PATTERN: DISPLAY FLASH MESSAGES ==========
    // Check for success message
    if ($this->session->flashdata('success_message')) {
        $this->data['success_message'] = $this->session->flashdata('success_message');
        $this->data['sent_count'] = $this->session->flashdata('sent_count');
        $this->data['total_recipients'] = $this->session->flashdata('total_recipients');
    }
    
    // Check for error message
    if ($this->session->flashdata('error_message')) {
        $this->data['error_message'] = $this->session->flashdata('error_message');
    }
    
    // Restore old POST data for form repopulation
    if ($this->session->flashdata('old_post')) {
        $this->data['old_post'] = $this->session->flashdata('old_post');
    }
    
    $this->load->view('layout/index', $this->data);
}

    public function email()
{
    if (!get_permission('sendsmsmail', 'is_add')) {
        access_denied();
    }
    $branchID = $this->application_model->get_branch_id();
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/summernote/summernote.css',
            'vendor/bootstrap-timepicker/css/bootstrap-timepicker.css',
        ),
        'js' => array(
            'vendor/bootstrap-timepicker/bootstrap-timepicker.js',
            'vendor/summernote/summernote.js',
        ),
    );
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('bulk_sms_and_email');
    $this->data['sub_page'] = 'sendsmsmail/email';
    $this->data['main_menu'] = 'sendsmsmail';
    
    // ========== PRG PATTERN: DISPLAY FLASH MESSAGES ==========
    // Check for success message
    if ($this->session->flashdata('success_message')) {
        $this->data['success_message'] = $this->session->flashdata('success_message');
        $this->data['sent_count'] = $this->session->flashdata('sent_count');
        $this->data['total_recipients'] = $this->session->flashdata('total_recipients');
    }
    
    // Check for error message
    if ($this->session->flashdata('error_message')) {
        $this->data['error_message'] = $this->session->flashdata('error_message');
    }
    
    // Restore old POST data for form repopulation
    if ($this->session->flashdata('old_post')) {
        $this->data['old_post'] = $this->session->flashdata('old_post');
    }
    
    $this->load->view('layout/index', $this->data);
}
    // Added  mpesa sms purchase methods here 
  /**
     * Loads the purchase view. Determines the initial branch_id to populate the view.
     */
    public function purchase() {
        if (!get_permission('sendsmsmail', 'is_view')) {
            access_denied();
        }
        
        
        // Superadmins can select, regular users are locked to their branch.
        $branch_id = $this->application_model->get_branch_id(); // Uses logic for superadmin/user

        $this->data['title'] = translate('Purchase SMS Credits');
        $this->data['branch_id'] = $branch_id;
        $this->data['sub_page'] = 'sendsmsmail/purchase';
        $this->data['main_menu'] = 'sendsmsmail';
        $this->load->view('layout/index', $this->data);
    }

    /**
 * Manual verification by receipt number or checkout ID
 * Available for superadmin/finance roles
 */
public function manual_verify()
{
    // Check permission - only superadmin or finance
    if (!$this->is_superadmin_loggedin() && !get_permission('finance', 'is_view')) {
        access_denied();
    }
    
    $this->data['title'] = 'Manual Transaction Verification';
    $this->data['sub_page'] = 'sendsmsmail/manual_verify';
    $this->data['main_menu'] = 'sendsmsmail';
    
    // Load branch list for superadmin filter
    if ($this->is_superadmin_loggedin()) {
        $this->data['branches'] = $this->db->get('branch')->result_array();
    }
    
    $this->load->view('layout/index', $this->data);
}
    /**
     * Get user data for dashboard (AJAX)
     */
    public function get_user_data() {
        // Check if it's an AJAX request
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(400)->set_output('Direct access not allowed');
        }
        
        $this->output->set_content_type('application/json');
        
        // Determine branch_id based on user role
        if ($this->is_superadmin_loggedin()) {
            $branch_id = $this->input->post('branch_id');
            // If superadmin didn't select a branch, use their default branch
            if (!$branch_id) {
                $branch_id = $this->get_loggedin_branch_id();
            }
        } else {
            // Regular users are locked to their own branch
            $branch_id = $this->get_loggedin_branch_id();
        }

        // Final validation - ensure we have a branch_id
        if (empty($branch_id)) {
            echo json_encode(['success' => false, 'message' => 'Branch not identified or not selected.']);
            return;
        }

        // Verify branch access
        if (!$this->has_branch_access($branch_id)) {
            echo json_encode(array('success' => false, 'message' => 'Access denied to this branch'));
            return;
        }

        try {
            $sms_credit = $this->sendsmsmail_model->get_sms_credit($branch_id);
            $billing_history = $this->sendsmsmail_model->get_billing_history($branch_id, 10);

            echo json_encode([
                'success' => true,
                'sms_credit' => $sms_credit, // Fixed variable name
                'billing_history' => $billing_history // Fixed variable name
            ]);

        } catch (Exception $e) {
            //log_message('error', 'SMS Purchase: Error fetching data for branch ' . $branch_id . '. Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Internal server error fetching data.']);
        }
    }
    
    /**
 * Initiate payment (AJAX)
 */
public function initiate_payment() {
    // Check if it's an AJAX request
    if (!$this->input->is_ajax_request()) {
        return $this->output->set_status_header(400)->set_output('Direct access not allowed');
    }
    
    $this->output->set_content_type('application/json');
    
    // Validate CSRF token
    if (!$this->check_csrf()) {
        echo json_encode(array('success' => false, 'message' => 'Security token validation failed'));
        return;
    }
    
    $branch_id = $this->input->post('branch_id');
    $amount = $this->input->post('amount');
    $phone = $this->input->post('phone');
    
    // Validate inputs
    if (!$branch_id || !$amount || !$phone) {
        echo json_encode(array('success' => false, 'message' => 'All fields are required'));
        return;
    }
    
    // Verify branch access
    if (!$this->has_branch_access($branch_id)) {
        echo json_encode(array('success' => false, 'message' => 'Access denied to this branch'));
        return;
    }
    
    // Validate amount is numeric and positive
    if (!is_numeric($amount) || $amount < 1) {
        echo json_encode(array('success' => false, 'message' => 'Invalid amount specified'));
        return;
    }
    
    // Calculate SMS units
    $sms_units = $this->payment_handler->calculate_sms_units($amount);
    
    if ($sms_units < 1) {
        echo json_encode(array('success' => false, 'message' => 'Amount is too low to purchase any SMS units'));
        return;
    }
    
    // Generate account reference
    $account_reference = 'SMS_' . $branch_id . '_' . time();
    
    // Log before payment initiation
    //log_message('debug', 'Initiating payment for branch: ' . $branch_id . ', amount: ' . $amount . ', phone: ' . $phone);
    
    // Initiate payment
   $payment_result = $this->payment_handler->initiate_payment(
    $phone, 
    $amount, 
    $account_reference,
    'SMS Purchase',
    'sms'  // Add this - specifies SMS callback
);
    
    if ($payment_result['success']) {
        // Save transaction to database
        $transaction_data = array(
            'branch_id' => $branch_id,
            'user_phone' => $phone,
            'amount' => $amount,
            'sms_units' => $sms_units,
            'checkout_request_id' => $payment_result['checkout_request_id'],
            'status' => 'pending',
            'is_credited' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        // Log transaction data
        //log_message('debug', 'Transaction data to save: ' . print_r($transaction_data, true));
        
        $transaction_id = $this->sendsmsmail_model->create_transaction($transaction_data);
        
        if ($transaction_id) {
            //log_message('info', 'Transaction saved successfully. ID: ' . $transaction_id);
            echo json_encode(array(
                'success' => true,
                'checkout_request_id' => $payment_result['checkout_request_id'],
                'message' => $payment_result['message']
            ));
        } else {
            // Get database error for debugging
            $db_error = $this->db->error();
            //log_message('error', 'Failed to save transaction. Database error: ' . print_r($db_error, true));
            //log_message('error', 'Last query: ' . $this->db->last_query());
            
            echo json_encode(array(
                'success' => false,
                'message' => 'Failed to save transaction. Database error: ' . $db_error['message']
            ));
        }
    } else {
        //log_message('error', 'Payment initiation failed: ' . $payment_result['message']);
        echo json_encode(array(
            'success' => false,
            'message' => $payment_result['message']
        ));
    }
}
    
/**
 * Check payment status - FIXED receipt generation
 */
public function check_payment_status() {
    if (!$this->input->is_ajax_request()) {
        return $this->output->set_status_header(400)->set_output('Direct access not allowed');
    }
    
    $this->output->set_content_type('application/json');
    
    if (!$this->check_csrf()) {
        echo json_encode(array('success' => false, 'message' => 'Security token validation failed'));
        return;
    }
    
    $checkout_request_id = $this->input->post('checkout_request_id');
    $poll_count = $this->input->post('poll_count') ?: 1;
    
    if (!$checkout_request_id) {
        echo json_encode(array('success' => false, 'message' => 'Invalid checkout request ID'));
        return;
    }
    
    $transaction = $this->sendsmsmail_model->get_transaction_by_checkout_id($checkout_request_id);
    
    if (!$transaction) {
        echo json_encode(array('success' => false, 'message' => 'Transaction not found'));
        return;
    }
    
    // If transaction already has a receipt number, return immediately
    if (!empty($transaction->receipt_number)) {
        echo json_encode(array(
            'success' => true,
            'status' => 'completed',
            'message' => "Payment completed with receipt: {$transaction->receipt_number}"
        ));
        return;
    }
    
    // Check payment status
    $status_result = $this->payment_handler->check_payment_status($checkout_request_id, $poll_count);

    if ($status_result['success']) {
        $update_data = array(
            'status' => $status_result['status'],
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        if ($status_result['status'] == 'completed') {
            // ALWAYS generate a receipt number for completed transactions
            if (empty($transaction->receipt_number)) {
                // Check if we have a real receipt from the API
                if (isset($status_result['receipt_number']) && !empty($status_result['receipt_number'])) {
                    $update_data['receipt_number'] = $status_result['receipt_number'];
                    //log_message('info', "✅ REAL M-Pesa receipt from API: {$status_result['receipt_number']}");
                } else {
                    // Generate sandbox receipt as fallback
                    $sandbox_receipt = 'SBX_' . date('YmdHis') . '_' . $transaction->id;
                    $update_data['receipt_number'] = $sandbox_receipt;
                    //log_message('info', "🔄 Generated sandbox receipt: {$sandbox_receipt}");
                }
            }
            
            // Credit SMS units if not already credited
            if (!$transaction->is_credited) {
                $credit_result = $this->sendsmsmail_model->credit_sms_units($transaction->branch_id, $transaction->sms_units);
                if ($credit_result) {
                    $update_data['is_credited'] = 1;
                    //log_message('info', "SMS units credited to branch {$transaction->branch_id}: {$transaction->sms_units} units");
                } else {
                    //log_message('error', "Failed to credit SMS units to branch {$transaction->branch_id}");
                }
            }
        }
        
        // Update transaction
        $update_result = $this->sendsmsmail_model->update_transaction($checkout_request_id, $update_data);
        
        if ($update_result) {
            //log_message('info', "Transaction updated successfully - Status: {$status_result['status']}, Receipt: " . ($update_data['receipt_number'] ?? 'Not set'));
        }
        
        echo json_encode(array(
            'success' => true,
            'status' => $status_result['status'],
            'message' => $status_result['message'],
            'poll_count' => $poll_count
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'status' => 'error',
            'message' => $status_result['message']
        ));
    }
}

/**
 * Get new CSRF hash for AJAX requests
 */
public function get_csrf_hash()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $this->output->set_content_type('application/json');
    echo json_encode([
        'csrf_hash' => $this->security->get_csrf_hash()
    ]);
}

/**
 * AJAX handler for manual verification search and process
 */
public function verify_transaction()
{
    // Set JSON header first thing
    header('Content-Type: application/json');
    
    try {
        if (!$this->input->is_ajax_request()) {
            throw new Exception('Direct access not allowed');
        }
        
        // Check permission
        if (!$this->is_superadmin_loggedin() && !get_permission('finance', 'is_view')) {
            throw new Exception('Access denied');
        }
        
        // Validate CSRF
        if (!$this->check_csrf()) {
            throw new Exception('Security token validation failed');
        }
        
        $identifier = $this->input->post('identifier');
        $action = $this->input->post('action');
        $branch_filter = $this->input->post('branch_id');
        
        if (empty($identifier)) {
            throw new Exception('Please enter receipt number or checkout ID');
        }
        
        // Search for transaction
        $this->db->from('sms_transaction');
        $this->db->group_start();
        $this->db->where('receipt_number', $identifier);
        $this->db->or_where('checkout_request_id', $identifier);
        $this->db->group_end();
        
        // Apply branch filter if superadmin selected one
        if ($this->is_superadmin_loggedin() && !empty($branch_filter)) {
            $this->db->where('branch_id', $branch_filter);
        } elseif (!$this->is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        
        $transaction = $this->db->get()->row();
        
        if (!$transaction) {
            throw new Exception('Transaction not found');
        }
        
        // If just searching, return transaction details
        if ($action == 'search') {
            // Get branch name
            $branch = $this->db->select('name')->get_where('branch', ['id' => $transaction->branch_id])->row();
            
            echo json_encode([
                'success' => true,
                'action' => 'search',
                'transaction' => [
                    'id' => $transaction->id,
                    'branch_id' => $transaction->branch_id,
                    'branch_name' => $branch ? $branch->name : 'Unknown',
                    'user_phone' => $transaction->user_phone,
                    'amount' => $transaction->amount,
                    'sms_units' => $transaction->sms_units,
                    'receipt_number' => $transaction->receipt_number,
                    'checkout_request_id' => $transaction->checkout_request_id,
                    'status' => $transaction->status,
                    'is_credited' => $transaction->is_credited,
                    'created_at' => $transaction->created_at
                ]
            ]);
            return;
        }
        
        // If verifying, process the transaction
        if ($action == 'verify') {
            // Check if already completed
            if ($transaction->status == 'completed' && $transaction->is_credited == 1) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Transaction already completed and credited',
                    'transaction' => $transaction
                ]);
                return;
            }
            
            // Call STK Query API to verify with M-Pesa
            $status_result = $this->payment_handler->check_payment_status(
                $transaction->checkout_request_id, 
                1 // Poll count
            );
            
            if (!$status_result['success']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to query M-Pesa: ' . $status_result['message']
                ]);
                return;
            }
            
            // Check if payment is confirmed
            if ($status_result['status'] != 'completed') {
                echo json_encode([
                    'success' => false,
                    'message' => 'M-Pesa status: ' . $status_result['message'],
                    'status' => $status_result['status']
                ]);
                return;
            }
            
            // Payment confirmed - process the credit
            // Start transaction for atomic operation
            $this->db->trans_begin();
            
            try {
                // Get the correct user ID from session
                $user_id = null;
                
                // Try different possible session keys from your project
                if ($this->session->userdata('login_user_id')) {
                    $user_id = $this->session->userdata('login_user_id');
                } elseif ($this->session->userdata('id')) {
                    $user_id = $this->session->userdata('id');
                } elseif ($this->session->userdata('user_id')) {
                    $user_id = $this->session->userdata('user_id');
                } elseif ($this->session->userdata('staff_id')) {
                    $user_id = $this->session->userdata('staff_id');
                } elseif ($this->session->userdata('loggedin_userid')) {
                    $user_id = $this->session->userdata('loggedin_userid');
                } else {
                    $user_id = 0; // System
                    //log_message('warning', 'No user ID found in session for reconciliation log');
                }
                
                // Prepare update data
                $update_data = [
                    'status' => 'completed',
                    'is_credited' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Extract receipt number from status_result
                $receipt_number = null;
                
                // Check if receipt_number exists directly in status_result
                if (isset($status_result['receipt_number']) && !empty($status_result['receipt_number'])) {
                    $receipt_number = $status_result['receipt_number'];
                    //log_message('info', 'Receipt from status_result: ' . $receipt_number);
                }
                
                // Check for callback_data which contains the full M-Pesa response
                if (isset($status_result['callback_data']) && is_array($status_result['callback_data'])) {
                    $callback_data = $status_result['callback_data'];
                    
                    // Save the FULL callback response to callback_raw
                    $update_data['callback_raw'] = json_encode($callback_data);
                    
                    // Try to extract receipt from callback_data if not already found
                    if (empty($receipt_number)) {
                        // Method 1: From CallbackMetadata
                        if (isset($callback_data['CallbackMetadata']['Item'])) {
                            foreach ($callback_data['CallbackMetadata']['Item'] as $item) {
                                if (isset($item['Name']) && $item['Name'] == 'MpesaReceiptNumber') {
                                    $receipt_number = isset($item['Value']) ? $item['Value'] : null;
                                    //log_message('info', 'Receipt from CallbackMetadata: ' . $receipt_number);
                                    break;
                                }
                            }
                        }
                        
                        // Method 2: From TransactionReceipt
                        if (empty($receipt_number) && isset($callback_data['TransactionReceipt'])) {
                            $receipt_number = $callback_data['TransactionReceipt'];
                            //log_message('info', 'Receipt from TransactionReceipt: ' . $receipt_number);
                        }
                        
                        // Method 3: From MpesaReceiptNumber at top level
                        if (empty($receipt_number) && isset($callback_data['MpesaReceiptNumber'])) {
                            $receipt_number = $callback_data['MpesaReceiptNumber'];
                            //log_message('info', 'Receipt from MpesaReceiptNumber: ' . $receipt_number);
                        }
                    }
                } else {
                    // If no callback_data, save the status_result itself
                    $update_data['callback_raw'] = json_encode($status_result);
                }
                
                // If still no receipt and in sandbox mode, generate one
                if (empty($receipt_number)) {
                    $payment_config = $this->config->item('payment_gateway');
                    if (isset($payment_config['sandbox']) && $payment_config['sandbox'] == true) {
                        $receipt_number = 'SBX_MANUAL_' . date('YmdHis') . '_' . $transaction->id;
                        //log_message('info', 'Generated sandbox receipt: ' . $receipt_number);
                    }
                }
                
                // Add receipt number to update data if found
                if (!empty($receipt_number)) {
                    $update_data['receipt_number'] = $receipt_number;
                }
                
                // Add reconciliation fields if we have a valid user ID
                if ($user_id > 0) {
                    $update_data['reconciled_by'] = $user_id;
                    $update_data['reconciled_at'] = date('Y-m-d H:i:s');
                    $update_data['reconciliation_note'] = 'Manually verified via ' . 
                        (strlen($identifier) > 20 ? 'Checkout ID' : 'Receipt Number');
                }
                
                // Log what we're about to update
                //log_message('info', 'Updating transaction with data: ' . print_r($update_data, true));
                
                // Update transaction
                $this->db->where('id', $transaction->id);
                $this->db->update('sms_transaction', $update_data);
                
                // Check if update was successful
                if ($this->db->affected_rows() == 0) {
                    //log_message('error', 'No rows affected when updating transaction ' . $transaction->id);
                }
                
                // Credit SMS units if not already credited
                if (!$transaction->is_credited) {
                    $credit_result = $this->sendsmsmail_model->credit_sms_units(
                        $transaction->branch_id, 
                        $transaction->sms_units
                    );
                    
                    if (!$credit_result) {
                        throw new Exception('Failed to credit SMS units');
                    }
                    
                }
                
                // Log the manual reconciliation (only if we have a user ID)
                if ($user_id > 0) {
                    // Check if table exists before inserting
                    if (!$this->db->table_exists('sms_reconciliation_log')) {
                        $this->load->dbforge();
                        $fields = [
                            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => TRUE],
                            'transaction_id' => ['type' => 'INT', 'constraint' => 11],
                            'branch_id' => ['type' => 'INT', 'constraint' => 11],
                            'reconciled_by' => ['type' => 'INT', 'constraint' => 11],
                            'identifier_used' => ['type' => 'VARCHAR', 'constraint' => 100],
                            'mpesa_response' => ['type' => 'TEXT'],
                            'created_at' => ['type' => 'DATETIME']
                        ];
                        $this->dbforge->add_field($fields);
                        $this->dbforge->add_key('id', TRUE);
                        $this->dbforge->create_table('sms_reconciliation_log', TRUE);
                    }
                    
                    $log_data = [
                        'transaction_id' => $transaction->id,
                        'branch_id' => $transaction->branch_id,
                        'reconciled_by' => $user_id,
                        'identifier_used' => $identifier,
                        'mpesa_response' => json_encode($status_result),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $this->db->insert('sms_reconciliation_log', $log_data);
                }
                
                if ($this->db->trans_status() === FALSE) {
                    throw new Exception('Database transaction failed');
                }
                
                $this->db->trans_commit();
                
                // Verify the update was saved
                $updated_tx = $this->sendsmsmail_model->get_transaction_by_checkout_id($transaction->checkout_request_id);
                
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaction verified and credited successfully',
                    'receipt_number' => $receipt_number ?? $transaction->receipt_number,
                    'sms_units' => $transaction->sms_units,
                    'branch_id' => $transaction->branch_id
                ]);
                
            } catch (Exception $e) {
                $this->db->trans_rollback();
                //log_message('error', 'Manual verification failed: ' . $e->getMessage());
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}


/**
 * Get recent pending transactions for manual verification page
 */
public function get_pending_transactions()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $this->output->set_content_type('application/json');
    
    if (!$this->is_superadmin_loggedin() && !get_permission('finance', 'is_view')) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $branch_id = $this->input->post('branch_id');
    $limit = $this->input->post('limit') ?: 20;
    
    $this->db->from('sms_transaction');
    $this->db->where('status !=', 'completed');
    $this->db->or_where('is_credited', 0);
    $this->db->order_by('created_at', 'DESC');
    $this->db->limit($limit);
    
    if ($this->is_superadmin_loggedin() && !empty($branch_id)) {
        $this->db->where('branch_id', $branch_id);
    } elseif (!$this->is_superadmin_loggedin()) {
        $this->db->where('branch_id', get_loggedin_branch_id());
    }
    
    $transactions = $this->db->get()->result_array();
    
    // Get branch names
    $branch_names = [];
    foreach ($transactions as &$tx) {
        if (!isset($branch_names[$tx['branch_id']])) {
            $branch = $this->db->select('name')->get_where('branch', ['id' => $tx['branch_id']])->row();
            $branch_names[$tx['branch_id']] = $branch ? $branch->name : 'Unknown';
        }
        $tx['branch_name'] = $branch_names[$tx['branch_id']];
    }
    
    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);
}

/**
 * Manual status check with cooldown - for testing without rate limits
 */
public function manual_status_check($checkout_request_id) {
    if (!$this->is_superadmin_loggedin()) {
        show_error('Access denied', 403);
    }
    
    echo "<h2>Manual Status Check (Bypassing Rate Limits)</h2>";
    echo "<p>Checkout Request ID: <strong>{$checkout_request_id}</strong></p>";
    
    $transaction = $this->sendsmsmail_model->get_transaction_by_checkout_id($checkout_request_id);
    
    if (!$transaction) {
        echo "<p style='color: red;'>Transaction not found</p>";
        return;
    }
    
    echo "<h3>Current Transaction Status</h3>";
    echo "<pre>" . print_r($transaction, true) . "</pre>";
    
    // Wait 30 seconds to avoid rate limiting
    echo "<p>Waiting 30 seconds to avoid rate limiting...</p>";
    ob_flush();
    flush();
    sleep(30);
    
    echo "<h3>Checking Status Now</h3>";
    
    $status_result = $this->payment_handler->check_payment_status($checkout_request_id);
    
    echo "<h4>Status Check Result:</h4>";
    echo "<pre>" . print_r($status_result, true) . "</pre>";
    
    if ($status_result['success']) {
        $update_data = array(
            'status' => $status_result['status'],
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        if ($status_result['status'] == 'completed' && !$transaction->is_credited) {
            $this->sendsmsmail_model->credit_sms_units($transaction->branch_id, $transaction->sms_units);
            $update_data['is_credited'] = 1;
            
            if (empty($transaction->receipt_number)) {
                $update_data['receipt_number'] = 'SBX_MANUAL_' . date('YmdHis');
            }
        }
        
        $this->sendsmsmail_model->update_transaction($checkout_request_id, $update_data);
        echo "<p style='color: green;'>Transaction updated successfully</p>";
    }
    
    echo '<p><a href="' . base_url('sendsmsmail/purchase') . '">Return to Purchase Page</a></p>';
}

/**
 * Manual status recovery for stuck transactions
 */
public function recover_transaction($checkout_request_id) {
    if (!$this->is_superadmin_loggedin()) {
        show_error('Access denied', 403);
    }
    
    $transaction = $this->sendsmsmail_model->get_transaction_by_checkout_id($checkout_request_id);
    
    if (!$transaction) {
        echo "Transaction not found";
        return;
    }
    
    echo "<h2>Recovering Transaction: {$checkout_request_id}</h2>";
    echo "<pre>Current status: " . print_r($transaction, true) . "</pre>";
    
    // Force check status
    $status_result = $this->payment_handler->check_payment_status($checkout_request_id);
    
    echo "<h3>Status Check Result:</h3>";
    echo "<pre>" . print_r($status_result, true) . "</pre>";
    
    if ($status_result['success']) {
        $update_data = array(
            'status' => $status_result['status'],
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        if ($status_result['status'] == 'completed' && !$transaction->is_credited) {
            $this->sendsmsmail_model->credit_sms_units($transaction->branch_id, $transaction->sms_units);
            $update_data['is_credited'] = 1;
        }
        
        $this->sendsmsmail_model->update_transaction($checkout_request_id, $update_data);
        echo "<p style='color: green;'>Transaction updated successfully</p>";
    }
}

/**
 * M-Pesa callback for SMS credit purchases
 * THIS IS A PUBLIC METHOD - No authentication required
 */
public function payment_callback() {
    // ✅ Set proper headers FIRST
    header('Content-Type: application/json');
    http_response_code(200);
    
    // Get the raw callback data
    $callback_data = file_get_contents('php://input');
    
    // Log EVERYTHING for debugging
    //log_message('info', '========== SMS PAYMENT CALLBACK RECEIVED ==========');
    //log_message('info', 'Raw Callback Data Length: ' . strlen($callback_data) . ' bytes');
    //log_message('info', 'Raw Callback Data: ' . $callback_data);
    
    try {
        // Validate we have data
        if (empty($callback_data)) {
            //log_message('error', 'Empty callback data received');
            echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Empty callback']);
            return;
        }
        
        // Decode JSON
        $data = json_decode($callback_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            //log_message('error', 'Invalid JSON: ' . json_last_error_msg());
            echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON']);
            return;
        }
        
        // Validate callback structure
        if (!isset($data['Body']['stkCallback'])) {
            //log_message('error', 'Invalid callback structure: ' . print_r($data, true));
            echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid structure']);
            return;
        }
        
        $callback = $data['Body']['stkCallback'];
        $checkout_request_id = $callback['CheckoutRequestID'];
        $result_code = $callback['ResultCode'];
        $result_desc = $callback['ResultDesc'];
        
        //log_message('info', "Processing - CheckoutID: {$checkout_request_id}, Code: {$result_code}, Desc: {$result_desc}");
        
        // Load model if not already loaded
        if (!isset($this->sendsmsmail_model)) {
            $this->load->model('sendsmsmail_model');
        }
        
        // Find pending transaction
        $this->db->where('checkout_request_id', $checkout_request_id);
        $this->db->where('status', 'pending');
        $transaction = $this->db->get('sms_transaction')->row();
        
        if (!$transaction) {
            //log_message('error', "No pending transaction found for checkout ID: {$checkout_request_id}");
            echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
            return;
        }
        
        //log_message('info', "Found transaction ID: {$transaction->id} for branch: {$transaction->branch_id}");
        
        // Prepare update data
        $update_data = [
            'callback_raw' => $callback_data,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($result_code == 0) {
            // Payment successful
            $receipt_number = null;
            $amount = null;
            $phone = null;
            
            // Extract receipt from metadata
            if (isset($callback['CallbackMetadata']['Item'])) {
                foreach ($callback['CallbackMetadata']['Item'] as $item) {
                    if (isset($item['Name']) && isset($item['Value'])) {
                        if ($item['Name'] == 'MpesaReceiptNumber') {
                            $receipt_number = $item['Value'];
                            //log_message('info', "✅ Extracted REAL receipt: {$receipt_number}");
                        } elseif ($item['Name'] == 'Amount') {
                            $amount = $item['Value'];
                        } elseif ($item['Name'] == 'PhoneNumber') {
                            $phone = $item['Value'];
                        }
                    }
                }
            }
            
            // Use the receipt number from callback (no fake generation)
            if (!empty($receipt_number)) {
                $update_data['receipt_number'] = $receipt_number;
                $update_data['status'] = 'completed';
                //log_message('info', "✅ Using REAL receipt number: {$receipt_number}");
            } else {
                //log_message('warning', "No receipt found in callback, marking as completed without receipt");
                $update_data['status'] = 'completed';
                // Don't generate fake receipts - leave as NULL
            }
            
            // Credit SMS units if not already credited
            if (!$transaction->is_credited) {
                $credit_result = $this->sendsmsmail_model->credit_sms_units($transaction->branch_id, $transaction->sms_units);
                
                if ($credit_result) {
                    $update_data['is_credited'] = 1;
                    //log_message('info', "✅ SMS units credited to branch {$transaction->branch_id}: {$transaction->sms_units} units");
                } else {
                    //log_message('error', "❌ Failed to credit SMS units to branch {$transaction->branch_id}");
                }
            }
            
        } else {
            // Payment failed
            if ($result_code == '1032') {
                $update_data['status'] = 'cancelled';
                //log_message('info', "Payment CANCELLED by user");
            } elseif ($result_code == '1037') {
                $update_data['status'] = 'cancelled';
                //log_message('info', "Payment TIMEOUT");
            } else {
                $update_data['status'] = 'failed';
                //log_message('error', "Payment FAILED - Code: {$result_code}, Desc: {$result_desc}");
            }
        }
        
        // Update transaction
        $this->db->where('id', $transaction->id);
        $result = $this->db->update('sms_transaction', $update_data);
        
        if ($result) {
            //log_message('info', "✅ Transaction {$transaction->id} updated successfully");
            //log_message('info', "   - Status: {$update_data['status']}");
            //log_message('info', "   - Receipt: " . ($update_data['receipt_number'] ?? 'NULL'));
            //log_message('info', "   - Callback saved: " . (strlen($callback_data) . ' bytes'));
        } else {
            //log_message('error', "❌ Failed to update transaction {$transaction->id}");
        }
        
    } catch (Exception $e) {
        //log_message('error', 'EXCEPTION in callback: ' . $e->getMessage());
        //log_message('error', 'Exception trace: ' . $e->getTraceAsString());
    }
    
    // ALWAYS return success to M-Pesa
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
}
 

/**
 * Manual receipt update for transactions missing receipt numbers
 */
public function update_missing_receipts() {
    if (!$this->is_superadmin_loggedin()) {
        show_error('Access denied', 403);
    }
    
    // Get completed transactions with null receipt numbers
    $transactions = $this->sendsmsmail_model->get_completed_transactions_without_receipt();
    
    echo "<h2>Updating Missing Receipt Numbers</h2>";
    echo "<p>Found " . count($transactions) . " transactions without receipt numbers</p>";
    
    foreach ($transactions as $transaction) {
        echo "<h3>Transaction ID: {$transaction->id}</h3>";
        echo "<pre>" . print_r($transaction, true) . "</pre>";
        
        // Generate a receipt number for sandbox
        if ($this->config->item('payment_gateway')['sandbox']) {
            $receipt_number = 'SBX_' . date('Ymd', strtotime($transaction->created_at)) . '_' . $transaction->id;
            
            $update_data = array(
                'receipt_number' => $receipt_number,
                'updated_at' => date('Y-m-d H:i:s')
            );
            
            $result = $this->sendsmsmail_model->update_transaction($transaction->checkout_request_id, $update_data);
            
            if ($result) {
                echo "<p style='color: green;'>✓ Updated receipt number: {$receipt_number}</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to update receipt number</p>";
            }
        }
    }
}

    /**
     * View all branches SMS credits (Superadmin only)
     */
    public function view_all_credits() {
        if (!$this->is_superadmin_loggedin()) {
            show_error('Access denied', 403);
        }
        
        $data['title'] = 'All Branches SMS Credits';
        $data['branches_credits'] = $this->sendsmsmail_model->get_all_branches_credits();
        
        $this->load->view('backend/admin/sms_credits_overview.php', $data);
    }
    
    /**
     * Helper method to get user's branch ID
     */
    private function get_loggedin_branch_id() {
        // Use your existing helper function if available, otherwise use session
        if (function_exists('get_loggedin_branch_id')) {
            return get_loggedin_branch_id();
        }
        
        // Fallback to session data
        if ($this->is_superadmin_loggedin()) {
            return $this->session->userdata('branch_id') ?: 1; // Default branch
        }
        return $this->session->userdata('branch_id');
    }
    
    /**
     * Check if user has access to branch
     */
    private function has_branch_access($branch_id) {
        if ($this->is_superadmin_loggedin()) {
            return true;
        }
        
        $user_branch_id = $this->get_loggedin_branch_id();
        return $user_branch_id == $branch_id;
    }
    
    /**
     * Check if user is superadmin - uses your existing helper function
     */
    private function is_superadmin_loggedin() {
        // Use your existing helper function
        if (function_exists('is_superadmin_loggedin')) {
            return is_superadmin_loggedin();
        }
        
        // Fallback to session check
        return $this->session->userdata('role') == 'superadmin';
    }
    
    /**
 * Debug CSRF - Remove after fixing
 */
public function debug_csrf()
{
    echo '<pre>';
    echo 'CSRF Token Name: ' . $this->security->get_csrf_token_name() . "\n";
    echo 'CSRF Token Hash: ' . $this->security->get_csrf_hash() . "\n";
    echo 'POST Data: ';
    print_r($_POST);
    echo '</pre>';
}

    /**
 * Check CSRF token with better error handling
 */
private function check_csrf() {
    $csrf_token_name = $this->security->get_csrf_token_name();
    
    // Try to get token from POST first, then from php://input for FormData
    $csrf_token = $this->input->post($csrf_token_name);
    
    // If not in POST, try to get from raw input (for FormData)
    if (empty($csrf_token)) {
        $raw_input = file_get_contents('php://input');
        if (!empty($raw_input)) {
            parse_str($raw_input, $post_data);
            $csrf_token = isset($post_data[$csrf_token_name]) ? $post_data[$csrf_token_name] : '';
        }
    }
    
    $current_csrf_hash = $this->security->get_csrf_hash();
    
    // Log for debugging
    //log_message('debug', 'CSRF Check - Token Name: ' . $csrf_token_name);
    //log_message('debug', 'CSRF Check - Received Token: ' . ($csrf_token ?: 'EMPTY'));
    //log_message('debug', 'CSRF Check - Expected Hash: ' . $current_csrf_hash);
    
    if (empty($csrf_token)) {
        //log_message('error', 'CSRF token is empty');
        return false;
    }
    
    $is_valid = ($csrf_token === $current_csrf_hash);
    
    if (!$is_valid) {
        //log_message('error', 'CSRF token validation failed. Expected: ' . $current_csrf_hash . ' Got: ' . $csrf_token);
    }
    
    return $is_valid;
}
    //End mpesa sms purchase

    public function delete($id)
    {
        if (get_permission('sendsmsmail', 'is_delete')) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('bulk_sms_email');
        }
    }

    public function delete_message($id = '')
{
    if (!get_permission('sendsmsmail', 'is_delete')) {
        access_denied();
    }
    
    // Check if ID is valid
    if (empty($id) || !is_numeric($id)) {
        $error_message = 'Invalid request';
        
        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => $error_message]);
            return;
        } else {
            set_alert('error', $error_message);
            redirect(base_url('sendsmsmail/scheduled'));
            return;
        }
    }
    
    $branchID = $this->get_current_branch_id();
    $is_ajax = $this->input->is_ajax_request();
    
    // Verify the message belongs to this branch
    $message = $this->db->get_where('bulk_sms_email', [
        'id' => $id,
        'branch_id' => $branchID
    ])->row_array();
    
    if (!$message) {
        $error_message = translate('message_not_found');
        
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_message]);
            return;
        } else {
            set_alert('error', $error_message);
            redirect(base_url('sendsmsmail/scheduled'));
            return;
        }
    }
    
    // Check if message can be deleted
    $deletable_statuses = [2, 3, 4, 5];
    if (!in_array($message['posting_status'], $deletable_statuses)) {
        $error_message = 'Cannot delete this message. Only completed, failed, or cancelled messages can be deleted.';
        
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_message]);
            return;
        } else {
            set_alert('error', $error_message);
            redirect(base_url('sendsmsmail/scheduled'));
            return;
        }
    }
    
    // Start transaction
    $this->db->trans_start();
    
    try {
        // Delete related records
        $this->db->where('message_id', $id);
        $this->db->delete('sms_email_delivery_logs');
        
        if ($this->db->table_exists('sms_schedule_logs')) {
            $this->db->where('message_id', $id);
            $this->db->delete('sms_schedule_logs');
        }
        
        if ($this->db->table_exists('sms_credit_reservations')) {
            $this->db->where('message_id', $id);
            $this->db->delete('sms_credit_reservations');
        }
        
        // Delete the main message
        $this->db->where('id', $id);
        $this->db->where('branch_id', $branchID);
        $deleted = $this->db->delete('bulk_sms_email');
        
        $this->db->trans_complete();
        
        if ($deleted) {
            $success_message = 'Message deleted successfully';
            
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => $success_message]);
                return;
            } else {
                set_alert('success', $success_message);
            }
        } else {
            $error_message = 'Failed to delete message';
            
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => $error_message]);
                return;
            } else {
                set_alert('error', $error_message);
            }
        }
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        $error_message = 'Error deleting message: ' . $e->getMessage();
        
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_message]);
            return;
        } else {
            set_alert('error', $error_message);
        }
    }
    
    // For non-AJAX requests, redirect
    if (!$is_ajax) {
        redirect(base_url('sendsmsmail/scheduled'));
    }
}

public function campaign_reports()
{
    if (!get_permission('sendsmsmail_reports', 'is_view')) {
        access_denied();
    }
    
    // FIXED: Use our custom method for branch ID
    $branchID = $this->get_current_branch_id();
    
    // DEBUG: Log the branch being used
    //log_message('debug', "campaign_reports - Branch ID: $branchID");
    //log_message('debug', "Superadmin: " . (is_superadmin_loggedin() ? 'Yes' : 'No'));
    
    // ADDED: Define recipient types array
    $this->data['recipient_types'] = [
        1 => 'Group (Role)',
        2 => 'Individual',
        3 => 'Class',
        4 => 'Homework',
        5 => 'Fee Reminder',
        6 => 'Student Birthday',
        7 => 'Staff Birthday'
    ];
    
    // ADDED: Define status labels
    $this->data['statuses'] = [
        0 => 'Processing',
        1 => 'Scheduled',
        2 => 'Completed',
        3 => 'Failed',
        4 => 'Partial',
        5 => 'Cancelled'
    ];
    
    // ADDED: Define message types
    $this->data['message_types'] = [
        1 => 'SMS',
        2 => 'Email'
    ];
    
    if ($_POST) {
        $sendType = $this->input->post('send_type');
        $campaignType = $this->input->post('campaign_type');
        $daterange = explode(' - ', $this->input->post('daterange'));
        $start = date("Y-m-d", strtotime($daterange[0]));
        $end = date("Y-m-d", strtotime($daterange[1]));
        
        // DEBUG: Log filter criteria
        //log_message('debug', "Report filters - Type: $campaignType, SendType: $sendType, Date: $start to $end");
        
        // Build query
        $this->db->where('DATE(created_at) >=', $start);
        $this->db->where('DATE(created_at) <=', $end);
        $this->db->where('message_type', $campaignType);
        
        // FIXED: Always filter by branch_id
        $this->db->where('branch_id', $branchID);
        
        if ($sendType != 'both') {
            $this->db->where('posting_status', $sendType);
        }
        
        // DEBUG: Show the query
        $query = $this->db->get('bulk_sms_email');
        //log_message('debug', "Campaign report SQL: " . $this->db->last_query());
        //log_message('debug', "Campaign report rows found: " . $query->num_rows());
        
        $this->data['campaignlist'] = $query->result_array();
        $this->data['startdate'] = $start;
        $this->data['enddate'] = $end;
        
        // DEBUG: Show sample data
        if (!empty($this->data['campaignlist'])) {
            //log_message('debug', "First campaign record: " . print_r($this->data['campaignlist'][0], true));
        }
    }

    $this->data['headerelements']   = array(
        'css' => array(
            'vendor/daterangepicker/daterangepicker.css',
        ),
        'js' => array(
            'vendor/moment/moment.js',
            'vendor/daterangepicker/daterangepicker.js',
        ),
    );
    $this->data['title'] = translate('bulk_sms_and_email');
    $this->data['sub_page'] = 'sendsmsmail/campaign_reports';
    $this->data['main_menu'] = 'sendsmsmail';
    $this->load->view('layout/index', $this->data);
}

function save() 
{
    if (!get_permission('sendsmsmail', 'is_add')) {
        access_denied();
    }

    // Check if this is an AJAX request
    $is_ajax = $this->input->is_ajax_request();
    
    // Debug logging
    //log_message('debug', 'SMS save() called. AJAX: ' . ($is_ajax ? 'Yes' : 'No') . ', POST: ' . print_r($_POST, true));
    
    if ($_POST) {
        // ========== DUPLICATE SUBMISSION PREVENTION ==========
        $submission_id = $this->input->post('submission_id');
        $duplicate_prevented = false;
        
        if ($submission_id) {
            // Check for duplicate within 5 seconds
            $this->db->where('submission_id', $submission_id);
            $existing = $this->db->get('sms_submission_logs')->row();
            
            if ($existing) {
                //log_message('debug', 'Duplicate submission prevented: ' . $submission_id);
                
                if ($is_ajax) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Message already sent (duplicate prevented)',
                        'submission_id' => $submission_id,
                        'duplicate' => true
                    ]);
                    return;
                } else {
                    $this->session->set_flashdata('error_message', 'Duplicate submission prevented.');
                    redirect(base_url('sendsmsmail/sms'));
                    return;
                }
            }
            
            // Log this submission
            $this->db->insert('sms_submission_logs', [
                'submission_id' => $submission_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // ========== FORM VALIDATION ==========
        $messageType = ($this->input->post('message_type') == 'sms' ? 1 : 2);
        $branchID = $this->get_current_branch_id();
        $recipientType = $this->input->post('recipient_type');
        $campaignName = $this->input->post('campaign_name');
        $message = $this->input->post('message', false);
        $sendLater = (isset($_POST['send_later']) ? 1 : 2);
        $smsGateway = $this->input->post('sms_gateway');
        
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('campaign_name', translate('campaign_name'), 'trim|required');
        $this->form_validation->set_rules('message', translate('message'), 'trim|required');
        
        if ($messageType == 1) {
            $this->form_validation->set_rules('sms_gateway', translate('sms_gateway'), 'trim|required');
        } else {
            $this->form_validation->set_rules('email_subject', translate('email_subject'), 'trim|required');
        }
        
        $this->form_validation->set_rules('recipient_type', translate('type'), 'trim|required');
        
        if ($recipientType == 1) {
            $this->form_validation->set_rules('role_group[]', translate('role'), 'trim|required');
        }

        if ($recipientType == 2) {
            $this->form_validation->set_rules('role_id', translate('role'), 'trim|required');
            $this->form_validation->set_rules('recipients[]', translate('name'), 'trim|required');
        }
        
        if ($recipientType == 3) {
            $this->form_validation->set_rules('class_id', translate('class'), 'trim|required');
            $this->form_validation->set_rules('section[]', translate('section'), 'trim|required');
        }
        
        if (isset($_POST['send_later'])) {
            $this->form_validation->set_rules('schedule_date', translate('schedule_date'), 'trim|required');
            $this->form_validation->set_rules('schedule_time', translate('schedule_time'), 'trim|required');
        }

        if ($this->form_validation->run() !== false) {
            // Get user array based on recipient type
            $user_array = $this->get_user_array($branchID, $recipientType);
            
            if (empty($user_array)) {
                $error_message = 'No recipients selected.';
                
                if ($is_ajax) {
                    echo json_encode([
                        'success' => false,
                        'message' => $error_message
                    ]);
                    return;
                } else {
                    $this->session->set_flashdata('error_message', $error_message);
                    redirect(base_url('sendsmsmail/sms'));
                    return;
                }
            }
            
           // ========== SMS CREDIT CHECKING ==========
            if ($messageType == 1) { // SMS only
                $recipients_count = count($user_array);
                
                // Calculate required credits
                $required_credits = $this->calculate_sms_cost($message, $recipients_count);
                
                // Get current credits
                $current_credits = $this->sendsmsmail_model->get_sms_credit($branchID);
                
                // Check if sufficient credits
                if ($current_credits < $required_credits) {
                    $error_message = "Insufficient SMS credits. ";
                    $error_message .= "You need $required_credits credits but only have $current_credits available. ";
                    $error_message .= "Please purchase more credits before " . ($sendLater == 1 ? "scheduling" : "sending") . " SMS.";
                    
                    if ($is_ajax) {
                        echo json_encode([
                            'success' => false,
                            'message' => $error_message
                        ]);
                        return;
                    } else {
                        $this->session->set_flashdata('error_message', $error_message);
                        redirect(base_url('sendsmsmail/sms'));
                        return;
                    }
                }
                
                // FOR SCHEDULED SMS: Reserve credits immediately
                if ($sendLater == 1) {
                    // Store reservation data temporarily - we'll update with message_id after insert
                    $this->session->set_flashdata('pending_reservation', [
                        'branch_id' => $branchID,
                        'credits_needed' => $required_credits,
                        'campaign_name' => $campaignName
                    ]);
                }
            }
            
           // ========== DUPLICATE MESSAGE CHECK (for both immediate AND scheduled) ==========
            // Always generate duplicate hash to prevent duplicates
            $duplicate_hash = $this->generate_duplicate_hash($branchID, $messageType, $message, $user_array, $campaignName, $recipientType);

            // For scheduled messages, also include schedule time in hash
            if ($sendLater == 1) {
                // Include schedule time in hash for scheduled messages
                $scheduleDate = $this->input->post('schedule_date');
                $scheduleTime = $this->input->post('schedule_time');
                $schedule_timestamp = strtotime($scheduleDate . ' ' . $scheduleTime);
                $duplicate_hash = md5($duplicate_hash . '_' . $schedule_timestamp);
                
                //log_message('debug', 'Scheduled message duplicate hash (with time): ' . $duplicate_hash);
            } else {
                //log_message('debug', 'Immediate message duplicate hash: ' . $duplicate_hash);
            }

            // Check for existing duplicate (different time limits based on type)
            $this->db->where('duplicate_hash', $duplicate_hash);

            if ($sendLater == 2) {
                // For immediate sends: check last 2 minutes
                $this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime('-2 minutes')));
                $error_message = 'This identical message was already sent in the last 2 minutes.';
            } else {
                // For scheduled sends: check if already scheduled for same time
                // No time limit - don't allow duplicate scheduled messages at all
                $error_message = 'This identical message is already scheduled.';
            }

            $existing_duplicate = $this->db->get('bulk_sms_email')->row();

            if ($existing_duplicate) {
                //log_message('warning', 'Duplicate message prevented. Hash: ' . $duplicate_hash . 
                //            ', Type: ' . ($sendLater == 1 ? 'scheduled' : 'immediate') . 
                //            ', Existing ID: ' . $existing_duplicate->id);
                
                if ($is_ajax) {
                    echo json_encode([
                        'success' => false,
                        'message' => $error_message,
                        'duplicate' => true
                    ]);
                    return;
                } else {
                    $this->session->set_flashdata('error_message', $error_message);
                    redirect(base_url('sendsmsmail/sms'));
                    return;
                }
            }
            
            // ========== PROCESS THE SEND ==========
            $result = $this->process_send($messageType, $branchID, $sendLater, $user_array, $message, $smsGateway, $campaignName, $recipientType, $duplicate_hash);
            
            if ($result['success']) {
                // SUCCESS - Return response based on request type
                if ($is_ajax) {
                    echo json_encode([
                        'success' => true,
                        'message' => $result['message'],
                        'sent_count' => $result['sent_count'],
                        'total_recipients' => $result['total_recipients'],
                        'transaction_id' => $result['transaction_id'] ?? null
                    ]);
                    return;
                } else {
                    // Non-AJAX fallback
                    $this->session->set_flashdata('success_message', $result['message']);
                    $this->session->set_flashdata('sent_count', $result['sent_count']);
                    $this->session->set_flashdata('total_recipients', $result['total_recipients']);
                    
                    $redirect_url = ($messageType == 1) ? base_url('sendsmsmail/sms') : base_url('sendsmsmail/email');
                    redirect($redirect_url);
                    return;
                }
            } else {
                // FAILURE
                if ($is_ajax) {
                    echo json_encode([
                        'success' => false,
                        'message' => $result['message']
                    ]);
                    return;
                } else {
                    $this->session->set_flashdata('error_message', $result['message']);
                    $this->session->set_flashdata('old_post', $_POST);
                    
                    $redirect_url = ($messageType == 1) ? base_url('sendsmsmail/sms') : base_url('sendsmsmail/email');
                    redirect($redirect_url);
                    return;
                }
            }
            
        } else {
            // VALIDATION FAILED
            $error_message = validation_errors();
            
            if ($is_ajax) {
                echo json_encode([
                    'success' => false,
                    'message' => $error_message
                ]);
                return;
            } else {
                $this->session->set_flashdata('error_message', $error_message);
                $this->session->set_flashdata('old_post', $_POST);
                
                $redirect_url = ($messageType == 1) ? base_url('sendsmsmail/sms') : base_url('sendsmsmail/email');
                redirect($redirect_url);
                return;
            }
        }
        
    } else {
        // NO POST DATA
        if ($is_ajax) {
            echo json_encode([
                'success' => false,
                'message' => 'No data received'
            ]);
            return;
        } else {
            redirect(base_url('sendsmsmail/sms'));
            return;
        }
    }
}

// ========== HELPER FUNCTIONS ==========

private function get_user_array($branchID, $recipientType)
{
    $user_array = [];
    
    if ($recipientType == 1) {
        $roleGroup = $this->input->post('role_group[]');
        foreach ($roleGroup as $users_value) {
            if ($users_value != 6 && $users_value != 7) {
                $staff = $this->sendsmsmail_model->getStaff($branchID, $users_value);
                if (count($staff)) {
                    foreach ($staff as $value) {
                        $user_array[] = [
                            'name' => $value['name'],
                            'email' => $value['email'],
                            'mobileno' => $value['mobileno'],
                        ];
                    }
                }
            }
            if ($users_value == 6) {
                $parents = $this->sendsmsmail_model->getParent($branchID);
                if (count($parents)) {
                    foreach ($parents as $value) {
                        $user_array[] = [
                            'name' => $value['name'],
                            'email' => $value['email'],
                            'mobileno' => $value['mobileno'],
                        ];
                    }
                }
            }
            if ($users_value == 7) {
                $students = $this->sendsmsmail_model->getStudent($branchID);
                if (count($students)) {
                    foreach ($students as $value) {
                        $user_array[] = [
                            'name' => $value['name'],
                            'email' => $value['email'],
                            'mobileno' => $value['mobileno'],
                        ];
                    }
                }
            }
        }
    } elseif ($recipientType == 2) {
        $roleID = $this->input->post('role_id');
        $recipients = $this->input->post('recipients[]');
        foreach ($recipients as $value) {
            if ($roleID != 6 && $roleID != 7) {
                $staff = $this->sendsmsmail_model->getStaff($branchID, '', $value);
                if (!empty($staff)) {
                    $user_array[] = [
                        'name' => $staff['name'],
                        'email' => $staff['email'],
                        'mobileno' => $staff['mobileno'],
                    ];
                }
            }
            if ($roleID == 6) {
                $parent = $this->sendsmsmail_model->getParent($branchID, $value);
                if (!empty($parent)) {
                    $user_array[] = [
                        'name' => $parent['name'],
                        'email' => $parent['email'],
                        'mobileno' => $parent['mobileno'],
                    ];
                }
            }
            if ($roleID == 7) {
                $student = $this->sendsmsmail_model->getStudent($branchID, $value);
                if (!empty($student)) {
                    $user_array[] = [
                        'name' => $student['name'],
                        'email' => $student['email'],
                        'mobileno' => $student['mobileno'],
                    ];
                }
            }
        }
    } elseif ($recipientType == 3) {
        $classID = $this->input->post('class_id');
        $sections = $this->input->post('section[]');
        foreach ($sections as $value) {
            $students = $this->sendsmsmail_model->getStudentBySection($classID, $value, $branchID);
            if (count($students)) {
                foreach ($students as $student) {
                    $user_array[] = [
                        'name' => $student['name'],
                        'email' => $student['email'],
                        'mobileno' => $student['mobileno'],
                    ];
                }
            }
        }
    }
    
    return $user_array;
}
public function check_scheduled_duplicate()
{
    $fingerprint = $this->input->post('fingerprint');
    $schedule_date = $this->input->post('schedule_date');
    $schedule_time = $this->input->post('schedule_time');
    
    $schedule_timestamp = strtotime($schedule_date . ' ' . $schedule_time);
    $full_fingerprint = md5($fingerprint . '_' . $schedule_timestamp);
    
    $this->db->where('duplicate_hash', $full_fingerprint);
    $this->db->where('send_type', 'scheduled');
    $this->db->where('posting_status', 1); // Only check pending scheduled
    
    $exists = $this->db->get('bulk_sms_email')->row();
    
    echo json_encode([
        'duplicate' => ($exists !== null),
        'message' => $exists ? 'Duplicate found' : 'No duplicate'
    ]);
}

private function generate_duplicate_hash($branchID, $messageType, $message, $user_array, $campaignName, $recipientType)
    {
        // Sort user array for consistent hashing
        $user_array_sorted = $user_array;
        usort($user_array_sorted, function($a, $b) {
            return strcmp($a['mobileno'] ?? $a['email'], $b['mobileno'] ?? $b['email']);
        });
        
        $data = [
            'branch_id' => $branchID,
            'message_type' => $messageType,
            'message_hash' => md5($message),
            'recipient_count' => count($user_array),
            'campaign_name' => $campaignName,
            'recipient_type' => $recipientType,
            'recipients_hash' => md5(serialize($user_array_sorted))
        ];
        
        // Add recipient-specific data
        if ($recipientType == 1) {
            $roleGroup = $this->input->post('role_group[]');
            sort($roleGroup); // Sort for consistency
            $data['recipients'] = implode(',', $roleGroup);
        } elseif ($recipientType == 2) {
            $data['role_id'] = $this->input->post('role_id');
            $recipients = $this->input->post('recipients[]');
            sort($recipients); // Sort for consistency
            $data['recipients'] = implode(',', $recipients);
        } elseif ($recipientType == 3) {
            $data['class_id'] = $this->input->post('class_id');
            $sections = $this->input->post('section[]');
            sort($sections); // Sort for consistency
            $data['sections'] = implode(',', $sections);
        }
        
        $hash = md5(serialize($data));
        //log_message('debug', 'Generated duplicate hash: ' . $hash . ' from data: ' . json_encode($data));
        
        return $hash;
    }

private function process_send($messageType, $branchID, $sendLater, $user_array, $message, $smsGateway, $campaignName, $recipientType, $duplicate_hash = null)
{
    //log_message('debug', 'process_send() called. Type: ' . $messageType . ', Later: ' . $sendLater . ', Recipients: ' . count($user_array));
    
    $success_count = 0;
    $total_credits_deducted = 0;
    $transaction_id = null;
    $gateway_error = false;
    
    // Generate transaction ID for immediate sends
    if ($sendLater == 2) {
        $transaction_id = 'IMM_' . $branchID . '_' . time() . '_' . rand(1000, 9999);
    }
    
    // Start transaction for atomic operations
    $this->db->trans_start();
    
    try {
        if ($sendLater == 1) {
            // Scheduled send - just store the data
            $additional = json_encode($user_array);
            $success_count = 0; // Will be processed later by cron
            
            // ========== CRITICAL: RESERVE CREDITS FOR SCHEDULED SMS ==========
    
            if ($messageType == 1) {
                $total_recipients = count($user_array);
                $total_credits_needed = $this->calculate_sms_cost($message, $total_recipients);
                
                // Check if we have enough credits
                $current_credits = $this->sendsmsmail_model->get_sms_credit($branchID);
                
                if ($current_credits < $total_credits_needed) {
                    throw new Exception('Insufficient credits to schedule SMS. Needed: ' . $total_credits_needed . ', Available: ' . $current_credits);
                }
                
                // RESERVE credits instead of directly deducting
                $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
                    $branchID, 
                    $total_credits_needed, 
                    $campaignName, 
                    null // Message ID will be set after insert
                );
                
                if (!$reservation_id) {
                    throw new Exception('Failed to reserve credits for scheduled SMS');
                }
                
                // Store reservation ID temporarily (we'll update after message insert)
                $this->session->set_flashdata('pending_reservation_id', $reservation_id);
                
                $total_credits_deducted = $total_credits_needed;
                //log_message('info', '✅ Reserved ' . $total_credits_needed . ' credits for scheduled SMS. Reservation ID: ' . $reservation_id);
            }
            
        } else {
            // Immediate send
            $individual_logs = [];
            $dlt_templateID = $this->input->post('dlt_template_id');
            $emailSubject = $this->input->post('email_subject');
            
            // ========== CRITICAL: DEDUCT CREDITS UPFRONT FOR IMMEDIATE SMS ==========
            if ($messageType == 1) {
                $total_recipients = count($user_array);
                $total_credits_needed = $this->calculate_sms_cost($message, $total_recipients);
                
                // Check if we have enough credits
                $current_credits = $this->sendsmsmail_model->get_sms_credit($branchID);
                
                if ($current_credits < $total_credits_needed) {
                    throw new Exception('Insufficient credits to send SMS. Needed: ' . $total_credits_needed . ', Available: ' . $current_credits);
                }
                
                // Deduct credits upfront
                $deducted = $this->sendsmsmail_model->deduct_sms_units($branchID, $total_credits_needed);
                if (!$deducted) {
                    throw new Exception('Failed to deduct credits for SMS');
                }
                
                $total_credits_deducted = $total_credits_needed;
                //log_message('info', 'Deducted ' . $total_credits_needed . ' credits upfront for immediate SMS');
            }
            
            foreach ($user_array as $value) {
                if ($messageType == 1) { // SMS
                    $response = $this->sendsmsmail_model->sendSMS(
                        $value['mobileno'], 
                        $message, 
                        $value['name'], 
                        $value['email'], 
                        $smsGateway, 
                        $dlt_templateID
                    );
                    
                    $status = 'sent';
                    $response_text = '';
                    
                    // Check if response indicates gateway error
                    if (is_string($response)) {
                        $response_text = substr($response, 0, 500);
                        
                        // Check for gateway credit error
                        if (strpos($response, 'Low credit units') !== false || 
                            strpos($response, 'insufficient credits') !== false ||
                            strpos($response, 'gateway has insufficient credits') !== false) {
                            $status = 'failed';
                            $gateway_error = true;
                            //log_message('error', 'Gateway credit error: ' . $response_text);
                        } elseif (strpos($response, 'Success') === false && strpos($response, 'success') === false) {
                            $status = 'failed';
                        } else {
                            $success_count++;
                        }
                    } elseif ($response === false) {
                        $status = 'failed';
                        $response_text = 'Send function returned false';
                    } else {
                        $success_count++;
                    }
                    
                    $individual_logs[] = [
                        'transaction_id' => $transaction_id,
                        'recipient_contact' => $value['mobileno'],
                        'status' => $status,
                        'gateway_response' => $response_text,
                        'sent_at' => date('Y-m-d H:i:s'),
                        'branch_id' => $branchID
                    ];
                    
                } else { // Email
                    $response = $this->sendsmsmail_model->sendEmail(
                        $value['email'], 
                        $message, 
                        $value['name'], 
                        $value['mobileno'], 
                        $emailSubject
                    );
                    
                    if ($response == true) {
                        $success_count++;
                    }
                }
                
                // Small delay to prevent rate limiting
                usleep(50000); // 0.05 second
            }
            
            // Batch insert delivery logs for SMS
            if (!empty($individual_logs)) {
                $this->db->insert_batch('sms_email_delivery_logs', $individual_logs);
            }
            
            $additional = '';
        }
        
        // ========== SAVE TO MAIN TABLE ==========
        $receivedDetails = '';
        if ($recipientType == 1) {
            $receivedDetails = json_encode(['role' => $this->input->post('role_group[]')]);
        } elseif ($recipientType == 3) {
            $receivedDetails = json_encode([
                'class' => $this->input->post('class_id'),
                'sections' => $this->input->post('section[]')
            ]);
        }
        
        $scheduleDate = $this->input->post('schedule_date');
        $scheduleTime = $this->input->post('schedule_time');
        
        $arrayData = [
            'campaign_name'         => $campaignName,
            'message'               => $message,
            'message_type'          => $messageType,
            'recipient_type'        => $recipientType,
            'recipients_details'    => $receivedDetails,
            'additional'            => $additional,
            'schedule_time'         => ($sendLater == 1) 
                ? date('Y-m-d H:i:s', strtotime($scheduleDate . ' ' . $scheduleTime))
                : date('Y-m-d H:i:s'),
            'posting_status'        => $sendLater, // 1 = scheduled, 2 = immediate
            'total_thread'          => count($user_array),
            'successfully_sent'     => $success_count,
            'branch_id'             => $branchID,
            'duplicate_hash'        => $duplicate_hash,
            'send_type'             => ($sendLater == 2) ? 'immediate' : 'scheduled',
            'transaction_id'        => $transaction_id,
            'processed_at'          => ($sendLater == 2) ? date('Y-m-d H:i:s') : null,
        ];
        
        if ($messageType == 1) {
            $arrayData['sms_gateway'] = $smsGateway;
            $arrayData['credits_used'] = $total_credits_deducted;
        } else {
            $arrayData['email_subject'] = $this->input->post('email_subject');
        }
        
        //log_message('debug', 'Inserting into bulk_sms_email: ' . print_r($arrayData, true));
        
        $this->db->insert('bulk_sms_email', $arrayData);
        $message_id = $this->db->insert_id();
        
        //log_message('info', 'Message inserted with ID: ' . $message_id);

            // Update reservation with message_id if we have a pending reservation
            if ($sendLater == 1 && $messageType == 1) {
                $pending_reservation_id = $this->session->flashdata('pending_reservation_id');
                if ($pending_reservation_id) {
                    $this->db->where('id', $pending_reservation_id);
                    $this->db->update('sms_credit_reservations', [
                        'message_id' => $message_id,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    //log_message('info', '✅ Updated reservation ' . $pending_reservation_id . ' with message ID: ' . $message_id);
                }
            }
        
        // Update delivery logs with message_id
        if (!empty($individual_logs) && $message_id) {
            $this->db->where('transaction_id', $transaction_id);
            $this->db->update('sms_email_delivery_logs', ['message_id' => $message_id]);
        }
        
        // ========== HANDLE GATEWAY ERRORS ==========
        if ($gateway_error && $messageType == 1 && $success_count == 0) {
            // If gateway failed completely, return credits
            if ($total_credits_deducted > 0) {
                $this->sendsmsmail_model->credit_sms_units($branchID, $total_credits_deducted);
                //log_message('info', 'Returned ' . $total_credits_deducted . ' credits due to gateway failure');
                
                // Update credits_used to 0
                $this->db->where('id', $message_id);
                $this->db->update('bulk_sms_email', ['credits_used' => 0]);
            }
            
            $this->db->trans_complete();
            
            return [
                'success' => false,
                'message' => 'SMS gateway has insufficient credits. Please contact administrator.',
                'sent_count' => 0,
                'total_recipients' => count($user_array),
                'gateway_error' => true
            ];
        }
        
        // Complete transaction
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            //log_message('error', 'Transaction failed in process_send()');
            return [
                'success' => false,
                'message' => 'Transaction failed. Please try again.',
                'sent_count' => 0,
                'total_recipients' => count($user_array)
            ];
        }
        
        // Success response
        if ($sendLater == 2) {
            return [
                'success' => true,
                'message' => translate('message_sent_successfully'),
                'sent_count' => $success_count,
                'total_recipients' => count($user_array),
                'transaction_id' => $transaction_id
            ];
        } else {
            return [
                'success' => true,
                'message' => 'Message scheduled successfully for ' . date('d M Y H:i', strtotime($scheduleDate . ' ' . $scheduleTime)),
                'sent_count' => 0,
                'total_recipients' => count($user_array)
            ];
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $this->db->trans_rollback();
        
        //log_message('error', 'Exception in process_send(): ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'sent_count' => 0,
            'total_recipients' => count($user_array)
        ];
    }
}

public function cleanup_old_immediate_sends()
{
    // Clean up immediate sends older than 30 days
    $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
    
    $this->db->where('send_type', 'immediate');
    $this->db->where('created_at <', $thirty_days_ago);
    $this->db->where('posting_status', 2); // completed
    $deleted = $this->db->delete('bulk_sms_email');
    
    //log_message('info', "Cleaned up {$deleted} old immediate sends");
    return $deleted;
}

// Add this helper method to the same controller
private function log_individual_delivery_immediate($transaction_id, $recipient_contact, $response, $branch_id)
{
    $status = 'sent';
    $response_text = '';
    
    // Parse response to determine status
    if (is_string($response)) {
        $response_text = substr($response, 0, 500);
        if (strpos($response, 'Success') === false && strpos($response, 'success') === false) {
            $status = 'failed';
        }
    } elseif ($response === false) {
        $status = 'failed';
        $response_text = 'Send function returned false';
    }
    
    $log_data = [
        'message_id' => 0, // Will be updated after insert
        'transaction_id' => $transaction_id,
        'recipient_contact' => $recipient_contact,
        'status' => $status,
        'gateway_response' => $response_text,
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id
    ];
    
    // Store in session to be logged after message insert

}

function scheduled()
{
    if (!get_permission('sendsmsmail', 'is_view')) {
        access_denied();
    }
    
    $branch_id = $this->get_current_branch_id();
    
    // For superadmin: check if branch filter is applied
    $selected_branch = $branch_id;
    if (is_superadmin_loggedin() && $this->input->get('branch')) {
        $selected_branch = $this->input->get('branch');
    }
    
    // Get all branches for superadmin filter
    if (is_superadmin_loggedin()) {
        $this->db->select('id, name');
        $this->db->from('branch');
        $this->db->order_by('name', 'ASC');
        $this->data['all_branches'] = $this->db->get()->result_array();
        $this->data['selected_branch'] = $selected_branch;
    }
    
    // Get ONLY pending scheduled messages (posting_status = 1)
    $this->db->from('bulk_sms_email');
    
    // Apply branch filter
    if (is_superadmin_loggedin() && $selected_branch != 'all') {
        $this->db->where('branch_id', $selected_branch);
    } elseif (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branch_id);
    }
    
    // Only show scheduled messages that are pending (status 1)
    $this->db->where('posting_status', 1);
    $this->db->order_by('schedule_time', 'ASC');
    
    $this->data['scheduled_messages'] = $this->db->get()->result_array();
    
    // Calculate stats
    $stats = [
        'sms' => 0,
        'email' => 0
    ];
    
    foreach ($this->data['scheduled_messages'] as $msg) {
        if ($msg['message_type'] == 1) {
            $stats['sms']++;
        } else {
            $stats['email']++;
        }
    }
    
    $this->data['stats'] = $stats;
    $this->data['title'] = 'Scheduled Messages (Pending)';
    $this->data['sub_page'] = 'sendsmsmail/scheduled';
    $this->data['main_menu'] = 'sendsmsmail';
    
    $this->load->view('layout/index', $this->data);
}

public function cancel_scheduled($id) {
    if (!get_permission('sendsmsmail', 'is_delete')) {
        access_denied();
    }
    
    $branchID = $this->get_current_branch_id();
    
    // Verify the message belongs to this branch
    $message = $this->sendsmsmail_model->get_message_by_id($id, $branchID);
    
    if (!$message) {
        set_alert('error', translate('message_not_found'));
        redirect(base_url('sendsmsmail/scheduled'));
    }
    
    if ($message['posting_status'] != 1) {
        set_alert('error', translate('cannot_cancel_non_scheduled_message'));
        redirect(base_url('sendsmsmail/scheduled'));
    }
    
    // Return reserved credits
    $credit_returned = false;
    if ($message['message_type'] == 1) { // SMS only
        $credit_returned = $this->sendsmsmail_model->return_reserved_credits($id, 'cancelled');
    }
    
    // Update message status
    $result = $this->sendsmsmail_model->update_scheduled_status($id, 5); // 5 = cancelled
    
    if ($result) {
        if ($credit_returned && $message['message_type'] == 1) {
            set_alert('success', translate('scheduled_message_cancelled_and_credits_returned'));
        } else {
            set_alert('success', translate('scheduled_message_cancelled'));
        }
    } else {
        set_alert('error', translate('failed_to_cancel_message'));
    }
    
    redirect(base_url('sendsmsmail/scheduled'));
}

public function view_scheduled($id = '')
{
    if (!get_permission('sendsmsmail', 'is_view')) {
        access_denied();
    }
    
    if (empty($id)) {
        redirect(base_url('sendsmsmail/scheduled'));
        return;
    }
    
    $this->db->where('id', $id);
    $message = $this->db->get('bulk_sms_email')->row_array();
    
    if (empty($message)) {
        $this->session->set_flashdata('error_message', 'Message not found');
        redirect(base_url('sendsmsmail/scheduled'));
        return;
    }
    
    $this->data['message_data'] = $message;
    $this->data['title'] = 'View Scheduled Message - ' . htmlspecialchars($message['campaign_name']);
    $this->data['sub_page'] = 'sendsmsmail/view_scheduled';
    $this->data['main_menu'] = 'sendsmsmail';
    
    $this->load->view('layout/index', $this->data);
}

public function check_message_status($id)
{
    $this->db->where('id', $id);
    $message = $this->db->get('bulk_sms_email')->row_array();
    
    $status_changed = false;
    if ($message && $message['posting_status'] != 1) {
        $status_changed = true;
    }
    
    echo json_encode([
        'status_changed' => $status_changed,
        'current_status' => $message['posting_status'] ?? null
    ]);
}
public function view_delivery_logs($message_id = null)
{
    if (!get_permission('sendsmsmail_reports', 'is_view')) {
        access_denied();
    }
    
    $branchID = $this->get_current_branch_id();
    
    if ($message_id) {
        $this->data['message'] = $this->sendsmsmail_model->get_message_by_id($message_id, $branchID);
    }
    
    $this->data['delivery_logs'] = $this->sendsmsmail_model->get_delivery_logs($message_id, $branchID);
    $this->data['title'] = translate('delivery_logs');
    $this->data['sub_page'] = 'sendsmsmail/delivery_logs';
    $this->data['main_menu'] = 'sendsmsmail';
    
    $this->load->view('layout/index', $this->data);
}

public function sent_messages()
{
    if (!get_permission('sendsmsmail', 'is_view')) {
        access_denied();
    }
    
    $branchID = $this->get_current_branch_id();
    
    // For superadmin: check if branch filter is applied
    $selected_branch = $branchID;
    if (is_superadmin_loggedin() && $this->input->get('branch')) {
        $selected_branch = $this->input->get('branch');
    }
    
    // Get all branches for superadmin filter
    if (is_superadmin_loggedin()) {
        $this->db->select('id, name');
        $this->db->from('branch');
        $this->db->order_by('name', 'ASC');
        $this->data['all_branches'] = $this->db->get()->result_array();
        $this->data['selected_branch'] = $selected_branch;
    }
    
    // Get sent messages for selected branch
    $this->db->select('*');
    $this->db->from('bulk_sms_email');
    $this->db->where_in('posting_status', [2, 3, 4]);
    
    if (is_superadmin_loggedin() && $selected_branch != 'all') {
        $this->db->where('branch_id', $selected_branch);
    } elseif (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    
    $this->db->order_by('updated_at', 'DESC');
    $this->db->limit(100); // Increased limit
    
    $this->data['sent_messages'] = $this->db->get()->result_array();
    
    // Get branch names
    $branch_names = [];
    foreach ($this->data['sent_messages'] as $msg) {
        if (!isset($branch_names[$msg['branch_id']])) {
            $branch = $this->db->get_where('branch', ['id' => $msg['branch_id']])->row();
            $branch_names[$msg['branch_id']] = $branch ? $branch->name : 'N/A';
        }
    }
    $this->data['branch_names'] = $branch_names;
    
    // Get sender information
    $sender_info = [];
    foreach ($this->data['sent_messages'] as $msg) {
        if (!empty($msg['created_by'])) {
            if (!isset($sender_info[$msg['created_by']])) {
                $this->db->select('name, role');
                $this->db->from('staff');
                $this->db->where('id', $msg['created_by']);
                $user = $this->db->get()->row();
                if ($user) {
                    $sender_info[$msg['created_by']] = [
                        'name' => $user->name,
                        'role' => $user->role
                    ];
                }
            }
        }
    }
    $this->data['sender_info'] = $sender_info;
    
    // Get recipient details
    $recipient_lists = [];
    $recipient_counts = [];
    foreach ($this->data['sent_messages'] as $msg) {
        $message_id = $msg['id'];
        
        $this->db->select('recipient_contact, status');
        $this->db->from('sms_email_delivery_logs');
        $this->db->where('message_id', $message_id);
        $this->db->limit(20);
        $logs = $this->db->get()->result_array();
        
        $recipients = [];
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($logs as $log) {
            $recipients[] = [
                'contact' => $log['recipient_contact'],
                'status' => $log['status']
            ];
            
            if ($log['status'] == 'sent') {
                $sent_count++;
            } else {
                $failed_count++;
            }
        }
        
        if (!empty($recipients)) {
            $recipient_lists[$message_id] = $recipients;
            $recipient_counts[$message_id] = [
                'sent' => $sent_count,
                'failed' => $failed_count
            ];
        }
    }
    $this->data['recipient_lists'] = $recipient_lists;
    $this->data['recipient_counts'] = $recipient_counts;
    
    // Calculate stats
    $stats = [
        'sms_count' => 0,
        'email_count' => 0,
        'scheduled_count' => 0,
        'immediate_count' => 0
    ];
    
    foreach ($this->data['sent_messages'] as $msg) {
        if ($msg['message_type'] == 1) {
            $stats['sms_count']++;
        } else {
            $stats['email_count']++;
        }
        
        if ($msg['send_type'] == 'scheduled') {
            $stats['scheduled_count']++;
        } else {
            $stats['immediate_count']++;
        }
    }
    $this->data['stats'] = $stats;
    
    $this->data['title'] = 'Sent Messages';
    $this->data['sub_page'] = 'sendsmsmail/sent_messages';
    $this->data['main_menu'] = 'sendsmsmail';
    
    $this->load->view('layout/index', $this->data);
}
public function check_scheduled_status()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $branchID = $this->get_current_branch_id();
    
    // Check if any scheduled messages have been sent
    $this->db->select('COUNT(*) as count');
    $this->db->from('bulk_sms_email');
    $this->db->where('branch_id', $branchID);
    $this->db->where('posting_status', 1); // scheduled
    $this->db->where('schedule_time <=', date('Y-m-d H:i:s'));
    $result = $this->db->get()->row();
    
    echo json_encode([
        'has_updates' => ($result->count > 0),
        'due_count' => $result->count
    ]);
}

public function edit_scheduled($id = '')
{
    if (!get_permission('sendsmsmail', 'is_edit')) {
        access_denied();
    }
    
    if (empty($id) || !is_numeric($id)) {
        set_alert('error', 'Invalid request');
        redirect(base_url('sendsmsmail/scheduled'));
        return;
    }
    
    // Get message with branch check
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', get_loggedin_branch_id());
    }
    $message = $this->db->get('bulk_sms_email')->row_array();
    
    if (empty($message)) {
        set_alert('error', 'Message not found');
        redirect(base_url('sendsmsmail/scheduled'));
        return;
    }
    
    // Only allow editing of scheduled messages (status 1)
    if ($message['posting_status'] != 1) {
        set_alert('error', 'Only scheduled messages can be edited');
        redirect(base_url('sendsmsmail/view_scheduled/' . $id));
        return;
    }
    
    // Check if fields are already decoded or need decoding
    // Only decode if they're strings, not arrays
    if (isset($message['recipients_details']) && is_string($message['recipients_details'])) {
        $message['recipients_details'] = json_decode($message['recipients_details'] ?? '[]', true);
    }
    
    if (isset($message['additional']) && is_string($message['additional'])) {
        $message['additional'] = json_decode($message['additional'] ?? '[]', true);
    }
    
    // Ensure they're arrays even if null
    $message['recipients_details'] = $message['recipients_details'] ?? [];
    $message['additional'] = $message['additional'] ?? [];
    
    $this->data['message_data'] = $message;
    $this->data['title'] = 'Edit Scheduled Message - ' . htmlspecialchars($message['campaign_name']);
    $this->data['sub_page'] = 'sendsmsmail/edit_scheduled';
    $this->data['main_menu'] = 'sendsmsmail';
    
    $this->load->view('layout/index', $this->data);
}

public function update_scheduled($id = '')
{
    if (!get_permission('sendsmsmail', 'is_edit')) {
        access_denied();
    }
    
    // Check if ID is valid
    if (empty($id) || !is_numeric($id)) {
        set_alert('error', 'Invalid request');
        redirect(base_url('sendsmsmail/scheduled'));
        return;
    }
    
    if ($_POST) {
        $this->form_validation->set_rules('campaign_name', translate('campaign_name'), 'trim|required');
        $this->form_validation->set_rules('schedule_date', translate('schedule_date'), 'trim|required');
        $this->form_validation->set_rules('schedule_time', translate('schedule_time'), 'trim|required');
        $this->form_validation->set_rules('confirm_edit', 'Confirmation', 'required');
        
        if ($this->form_validation->run() !== false) {
            try {
                // Get original message with branch check
                $this->db->where('id', $id);
                if (!is_superadmin_loggedin()) {
                    $this->db->where('branch_id', get_loggedin_branch_id());
                }
                $original_message = $this->db->get('bulk_sms_email')->row_array();
                
                if (empty($original_message)) {
                    throw new Exception('Message not found');
                }
                
                if ($original_message['posting_status'] != 1) {
                    throw new Exception('Cannot update this message - it may have been sent or cancelled');
                }
                
                // Check schedule is at least 5 minutes in future
                $schedule_date = $this->input->post('schedule_date');
                $schedule_time = $this->input->post('schedule_time');
                $new_schedule = date('Y-m-d H:i:s', strtotime($schedule_date . ' ' . $schedule_time));
                
                if (strtotime($new_schedule) < (time() + 300)) { // 300 seconds = 5 minutes
                    throw new Exception('Schedule must be at least 5 minutes in the future');
                }
                
                // Get original recipients for duplicate check
                $original_recipients = $original_message['additional'];
                if (is_string($original_recipients)) {
                    $original_recipients = json_decode($original_recipients, true);
                }
                
                // Generate new duplicate hash using YOUR existing function
                // Note: We need to simulate the POST data for your function
                // Since we can't change the original POST data, we'll create a modified version
                $this->load->helper('array');
                
                // Prepare data for duplicate hash generation
                $user_array = $original_recipients;
                
                // Create a hash using your existing function's logic
                // Since we can't call it directly with different parameters,
                // we'll replicate its logic here
                
                // Sort user array for consistent hashing
                $user_array_sorted = $user_array;
                usort($user_array_sorted, function($a, $b) {
                    return strcmp($a['mobileno'] ?? $a['email'], $b['mobileno'] ?? $b['email']);
                });
                
                $data = [
                    'branch_id' => $original_message['branch_id'],
                    'message_type' => $original_message['message_type'],
                    'message_hash' => md5($original_message['message']),
                    'recipient_count' => count($user_array),
                    'campaign_name' => $this->input->post('campaign_name'),
                    'recipient_type' => $original_message['recipient_type'],
                    'recipients_hash' => md5(serialize($user_array_sorted))
                ];
                
                // Add recipient-specific data from recipients_details
                $recipients_details = $original_message['recipients_details'];
                if (is_string($recipients_details)) {
                    $recipients_details = json_decode($recipients_details, true);
                }
                
                if ($original_message['recipient_type'] == 1 && !empty($recipients_details['role'])) {
                    $roleGroup = $recipients_details['role'];
                    sort($roleGroup); // Sort for consistency
                    $data['recipients'] = implode(',', $roleGroup);
                } elseif ($original_message['recipient_type'] == 2 && !empty($recipients_details['role_id'])) {
                    $data['role_id'] = $recipients_details['role_id'];
                    if (!empty($recipients_details['recipients'])) {
                        $recipients = $recipients_details['recipients'];
                        sort($recipients);
                        $data['recipients'] = implode(',', $recipients);
                    }
                } elseif ($original_message['recipient_type'] == 3 && !empty($recipients_details['class'])) {
                    $data['class_id'] = $recipients_details['class'];
                    if (!empty($recipients_details['sections'])) {
                        $sections = $recipients_details['sections'];
                        sort($sections);
                        $data['sections'] = implode(',', $sections);
                    }
                }
                
                $new_duplicate_hash = md5(serialize($data));
                
                // Include schedule time in hash
                $new_duplicate_hash = md5($new_duplicate_hash . '_' . strtotime($new_schedule));
                
                // Check for duplicate with new schedule
                $this->db->where('duplicate_hash', $new_duplicate_hash);
                $this->db->where('id !=', $id);
                $this->db->where('send_type', 'scheduled');
                $this->db->where_in('posting_status', [0, 1]); // Processing or scheduled
                
                $duplicate = $this->db->get('bulk_sms_email')->row();
                
                if ($duplicate) {
                    throw new Exception('This message is already scheduled for the new time');
                }
                
                // Update the message
                $update_data = [
                    'campaign_name' => $this->input->post('campaign_name'),
                    'schedule_time' => $new_schedule,
                    'duplicate_hash' => $new_duplicate_hash,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->where('id', $id);
                if (!is_superadmin_loggedin()) {
                    $this->db->where('branch_id', get_loggedin_branch_id());
                }
                $this->db->update('bulk_sms_email', $update_data);
                
                // Log the update if table exists
                if ($this->db->table_exists('sms_schedule_logs')) {
                    $log_data = [
                        'message_id' => $id,
                        'action' => 'rescheduled',
                        'old_schedule' => $original_message['schedule_time'],
                        'new_schedule' => $new_schedule,
                        'old_campaign_name' => $original_message['campaign_name'],
                        'new_campaign_name' => $this->input->post('campaign_name'),
                        'updated_by' => $this->session->userdata('loggedin_userid'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $this->db->insert('sms_schedule_logs', $log_data);
                }
                
                $this->session->set_flashdata('success', 'Scheduled message updated successfully');
                redirect(base_url('sendsmsmail/view_scheduled/' . $id));
                
            } catch (Exception $e) {
                $this->session->set_flashdata('error', $e->getMessage());
                redirect(base_url('sendsmsmail/edit_scheduled/' . $id));
            }
            
        } else {
            // Validation failed
            $this->session->set_flashdata('error', validation_errors());
            redirect(base_url('sendsmsmail/edit_scheduled/' . $id));
        }
    } else {
        redirect(base_url('sendsmsmail/edit_scheduled/' . $id));
    }
}

public function check_duplicate_schedule()
{
    $message_id = $this->input->post('message_id');
    $schedule_date = $this->input->post('schedule_date');
    $schedule_time = $this->input->post('schedule_time');
    $campaign_name = $this->input->post('campaign_name');
    
    // Get original message to compare
    $this->db->where('id', $message_id);
    $original = $this->db->get('bulk_sms_email')->row_array();
    
    if (!$original) {
        echo json_encode(['duplicate' => false, 'message' => 'Message not found']);
        return;
    }
    
    $new_schedule = date('Y-m-d H:i:s', strtotime($schedule_date . ' ' . $schedule_time));
    
    // Check if identical message already scheduled for this time (excluding current)
    $this->db->where('branch_id', $original['branch_id']);
    $this->db->where('message', $original['message']);
    $this->db->where('recipient_type', $original['recipient_type']);
    $this->db->where('schedule_time', $new_schedule);
    $this->db->where('id !=', $message_id);
    $this->db->where('send_type', 'scheduled');
    $this->db->where_in('posting_status', [0, 1]);
    
    $duplicate = $this->db->get('bulk_sms_email')->row();
    
    echo json_encode([
        'duplicate' => ($duplicate !== null),
        'message' => $duplicate ? 'Duplicate schedule found' : 'Schedule available'
    ]);
}

    // add send sms mail template
    public function template()
    {
        $type = html_escape($this->uri->segment(3));
        $typeA = array('email', 'sms');
        $result = in_array($type, $typeA);
        $type_n = ($type == 'sms' ? 1 : 2);
        if (!get_permission('sendsmsmail_template', 'is_view') || !$result) {
            access_denied();
        }
        if ($_POST) {
            if (get_permission('sendsmsmail_template', 'is_add')) {
                // validate inputs
                if (is_superadmin_loggedin()) {
                    $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
                }
                $this->form_validation->set_rules('template_name', translate('name'), 'required');
                $this->form_validation->set_rules('message', translate('message'), 'required');
                if ($this->form_validation->run() == true) {
                    $post = $this->input->post();
                    $post['type'] = $type_n;
                    $this->sendsmsmail_model->saveTemplate($post);
                    $url = current_url();
                    $array = array('status' => 'success', 'url' => $url, 'error' => '');
                    set_alert('success', translate('information_has_been_saved_successfully'));
                } else {
                    $error = $this->form_validation->error_array();
                    $array = array('status' => 'fail', 'url' => '', 'error' => $error);
                }
                echo json_encode($array);
                exit();
            }
        }
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/summernote/summernote.css',
            ),
            'js' => array(
                'vendor/summernote/summernote.js',
            ),
        );
        $this->data['type'] = $type;
        $this->data['templetelist'] = $this->app_lib->getTable('bulk_msg_category', array('type' => $type_n));
        $this->data['title'] = translate('bulk_sms_and_email');
        $this->data['sub_page'] = 'sendsmsmail/template_' . $type;
        $this->data['main_menu'] = 'sendsmsmail';
        $this->load->view('layout/index', $this->data);
    }

    // edit send sms mail template
        public function template_edit($type, $id = '')
    {
        $typeA = array('email', 'sms');
        $result = in_array($type, $typeA);
        $type_n = ($type == 'sms' ? 1 : 2);

        if (!get_permission('sendsmsmail_template', 'is_edit') || !$result) {
            access_denied();
        }

        if ($_POST) {
            // validate inputs
            if (is_superadmin_loggedin()) {
                $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
            }
            $this->form_validation->set_rules('template_name', translate('name'), 'required');
            $this->form_validation->set_rules('message', translate('message'), 'required');
            if ($this->form_validation->run() == true) {
                $post = $this->input->post();
                $post['type'] = $type_n;
                $this->sendsmsmail_model->saveTemplate($post);
                $url = base_url('sendsmsmail/template/' . $type);
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
                set_alert('success', translate('information_has_been_updated_successfully'));
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/summernote/summernote.css',
            ),
            'js' => array(
                'vendor/summernote/summernote.js',
            ),
        );
        $this->data['type'] = $type;
        $this->data['templete'] = $this->app_lib->getTable('bulk_msg_category', array('t.id' => $id, 't.type' => $type_n), true);
        $this->data['title'] = translate('bulk_sms_and_email');
        $this->data['sub_page'] = 'sendsmsmail/template_edit_' . $type;
        $this->data['main_menu'] = 'sendsmsmail';
        $this->load->view('layout/index', $this->data);
    }
    public function template_delete($id)
    {
        if (!get_permission('sendsmsmail_template', 'is_delete')) {
            access_denied();
        }
        $this->db->where('id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $this->db->delete('bulk_msg_category');
    }

    public function getRecipientsByRole()
    {
        $html = "";
        $branchID = $this->application_model->get_branch_id();
        $roleID = $this->input->post('role_id');
        if (!empty($branchID)) {
            if ($roleID != 6 && $roleID != 7) {
                $this->db->select('staff.id,staff.name,staff.staff_id,lc.role');
                $this->db->from('staff');
                $this->db->join('login_credential as lc', 'lc.user_id = staff.id AND lc.role != 6 AND lc.role != 7', 'inner');
                $this->db->where('lc.role', $roleID);
                $this->db->where('staff.branch_id', $branchID);
                $this->db->order_by('staff.id', 'asc');
                $result = $this->db->get()->result_array();
                foreach ($result as $staff) {
                    $html .= "<option value='" . $staff['id'] . "'>" . $staff['name'] . " (" . $staff['staff_id'] . ")</option>";
                }
            }
            if ($roleID == 6) {
                $this->db->where('branch_id', $branchID);
                $result = $this->db->get('parent')->result_array();
                foreach ($result as $row) {
                    $html .= "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                }
            }
            if ($roleID == 7) {
                $this->db->select('e.student_id,e.roll,CONCAT(s.first_name, " ", s.last_name) as name');
                $this->db->from('enroll as e');
                $this->db->join('student as s', 's.id = e.student_id', 'inner');
                $this->db->where('e.branch_id', $branchID);
                $this->db->where('e.session_id', get_session_id());
                $students = $this->db->get()->result_array();
                foreach ($students as $row) {
                    $html .= "<option value='" . $row['student_id'] . "'>" . $row['name'] . " (Roll" . $row['roll'] . ")</option>";
                }
            }
        }
        echo $html;
    }

    public function getSectionByClass()
    {
        $html = "";
        $classID = $this->input->post("class_id");
        if (!empty($classID)) {
            $result = $this->db->select('sections_allocation.section_id,section.name')
                ->from('sections_allocation')
                ->join('section', 'section.id = sections_allocation.section_id', 'left')
                ->where('sections_allocation.class_id', $classID)
                ->get()->result_array();
            if (count($result)) {
                foreach ($result as $row) {
                    $html .= '<option value="' . $row['section_id'] . '">' . $row['name'] . '</option>';
                }
            }
        }
        echo $html;
    }

    public function getSmsGateway()
    {
        $html = "";
        $branchID = $this->application_model->get_branch_id();
        if (!empty($branchID)) {
            $this->db->select('sms_api.name');
            $this->db->from('sms_api');
            $this->db->join('sms_credential', 'sms_credential.sms_api_id = sms_api.id', 'inner');
            $this->db->where('sms_credential.branch_id', $branchID);
            $this->db->where('sms_credential.is_active', 1);
            $this->db->order_by('sms_api.id', 'asc');
            $result = $this->db->get()->result_array();
            if (count($result)) {
                $html .= '<option value="">' . translate('select') . '</option>';
                foreach ($result as $row) {
                    $html .= '<option value="' . $row['name'] . '">' . ucfirst($row['name']) . '</option>';
                }
            } else {
                $html .= '<option value="">' . translate('no_sms_gateway_available') . '</option>';
            }
        } else {
            $html .= '<option value="">' . translate('select_branch_first') . '</option>';
        }
        echo $html;
    }

    public function getTemplateByBranch()
    {
        $html = "";
        $type = $this->input->post('type');
        $type = ($type == 'sms' ? 1 : 2);
        $branch_id = $this->application_model->get_branch_id();
        if (!empty($branch_id)) {
            $result = $this->db->select('id,name')->where(array('branch_id' => $branch_id, 'type' => $type))->get('bulk_msg_category')->result_array();
            if (count($result)) {
                $html .= "<option value=''>" . translate('select') . "</option>";
                foreach ($result as $row) {
                    $html .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                }
            } else {
                $html .= '<option value="">' . translate('no_information_available') . '</option>';
            }
        } else {
            $html .= '<option value="">' . translate('select_branch_first') . '</option>';
        }
        echo $html;
    }

    public function getSmsTemplateText()
    {
        $id = $this->input->post('id');
        $row = $this->db->where(array('id' => $id))->get('bulk_msg_category')->row_array();
        echo $row['body'];
    }

    public function getDetails()
    {
        if (get_permission('sendsmsmail', 'is_view')) {
            $id = $this->input->post('id');
            $this->db->where('id', $id);
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->data['bulkdata'] = $this->db->get('bulk_sms_email')->row_array();
            $this->load->view('sendsmsmail/messageModal', $this->data);
        }
    }

   
        /**
     * Check SMS credits before sending
     * This will be called from the save() method
     */
   private function check_sms_credits($branch_id, $recipients_count, $message) {
    // Load SMS config
    $this->config->load('smsconfig', TRUE);
    $sms_config = $this->config->item('sms_credit');
    
    // Get current credit balance
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branch_id);
    
    // Calculate required credits
    $required_credits = $this->calculate_sms_cost($message, $recipients_count);
    
    // Check if sufficient credits
    if ($current_credits < $required_credits) {
        $shortfall = $required_credits - $current_credits;
        
        // Return error with purchase suggestion
        return array(
            'success' => false,
            'message' => 'Insufficient SMS credits. You need ' . $required_credits . 
                       ' credits but only have ' . $current_credits . ' available.',
            'shortfall' => $shortfall,
            'required' => $required_credits,
            'available' => $current_credits
        );
    }
    
    // NEW: Check gateway balance for BulkSMSBD
    $sms_gateway = $this->input->post('sms_gateway');
    if ($sms_gateway == 'bulksmsbd') {
        $gateway_balance = $this->sendsmsmail_model->check_bulksmsbd_balance($branch_id);
        if ($gateway_balance !== false && $gateway_balance < 1) {
            return array(
                'success' => false,
                'message' => 'SMS gateway has insufficient credits. Current gateway balance: ' . $gateway_balance . ' credits.',
                'gateway_error' => true
            );
        }
    }
    
    // Check warning thresholds
    $warning_level = 'none';
    if ($current_credits <= $sms_config['warning_thresholds']['critical_credit_warning']) {
        $warning_level = 'critical';
    } elseif ($current_credits <= $sms_config['warning_thresholds']['low_credit_warning']) {
        $warning_level = 'low';
    }
    
    return array(
        'success' => true,
        'available' => $current_credits,
        'required' => $required_credits,
        'remaining' => $current_credits - $required_credits,
        'warning_level' => $warning_level
    );
}

    /**
 * Calculate SMS cost based on message length and recipients - FIXED VERSION
 */
private function calculate_sms_cost($message, $recipients_count) {
    // Load SMS config with error handling
    $sms_config = array();
    
    try {
        $this->config->load('smsconfig', TRUE);
        $sms_config = $this->config->item('sms_credit');
        
        if (empty($sms_config)) {
            //log_message('error', 'SMS config is empty, using defaults');
            // Set default values
            $sms_config = array(
                'credit_calculation' => array(
                    'chars_per_sms' => 160,
                    'credits_per_sms' => 1, // FIXED: This should be 1, not 4
                    'unicode_chars_per_sms' => 70,
                    'unicode_credits_multiplier' => 2,
                    'gsm_characters' => '@£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà',
                    'extended_gsm' => '^{}\[~]|€'
                )
            );
        }
    } catch (Exception $e) {
        //log_message('error', 'Failed to load SMS config: ' . $e->getMessage());
        // Use safe defaults
        $sms_config = array(
            'credit_calculation' => array(
                'chars_per_sms' => 160,
                'credits_per_sms' => 1, // FIXED: This should be 1, not 4
                'unicode_chars_per_sms' => 70,
                'unicode_credits_multiplier' => 2
            )
        );
    }
    
    // Check if message contains Unicode characters
    $is_unicode = $this->contains_unicode($message);
    
    // Get appropriate character limit with safety checks
    $chars_per_sms = 160; // Default
    
    if ($is_unicode) {
        $chars_per_sms = isset($sms_config['credit_calculation']['unicode_chars_per_sms']) ? 
            (int)$sms_config['credit_calculation']['unicode_chars_per_sms'] : 70;
    } else {
        $chars_per_sms = isset($sms_config['credit_calculation']['chars_per_sms']) ? 
            (int)$sms_config['credit_calculation']['chars_per_sms'] : 160;
    }
    
    // Ensure we don't divide by zero
    if ($chars_per_sms <= 0) {
        $chars_per_sms = 160;
        //log_message('error', 'Invalid chars_per_sms, using default: 160');
    }
    
    // Calculate number of SMS parts
    $message_length = mb_strlen($message, 'UTF-8');
    $sms_parts = ceil($message_length / $chars_per_sms);
    
    // Calculate credits per SMS part
    $credits_per_sms_part = 1; // FIXED: Base is always 1 credit per SMS part
    
    // Apply Unicode multiplier if needed
    if ($is_unicode) {
        $multiplier = isset($sms_config['credit_calculation']['unicode_credits_multiplier']) ? 
            (int)$sms_config['credit_calculation']['unicode_credits_multiplier'] : 2;
        $credits_per_sms_part = $multiplier; // FIXED: Unicode = 2 credits per part
    }
    
    // Ensure minimum values
    if ($sms_parts < 1) $sms_parts = 1;
    if ($credits_per_sms_part < 1) $credits_per_sms_part = 1;
    if ($recipients_count < 1) $recipients_count = 1;
    
    // CORRECTED FORMULA: Total credits = SMS parts × recipients × credits per SMS part
    $total_credits = $sms_parts * $recipients_count * $credits_per_sms_part;
    
    //log_message('debug', "SMS Cost Calculation - FIXED: Length=$message_length, Parts=$sms_parts, " . 
    //           "Recipients=$recipients_count, CreditsPerPart=$credits_per_sms_part, Total=$total_credits, " . 
    //           "Unicode=" . ($is_unicode ? 'Yes' : 'No'));

                //log_message('debug', "FINAL COST CALCULATION DEBUG:");
                ////log_message('debug', "  Message: '$message'");
                //log_message('debug', "  Length: $message_length");
                //log_message('debug', "  Unicode: " . ($is_unicode ? 'Yes' : 'No'));
                //log_message('debug', "  Parts: $sms_parts");
                //log_message('debug', "  Recipients: $recipients_count");
                //log_message('debug', "  Credits per part: $credits_per_sms_part");
                //log_message('debug', "  TOTAL: $total_credits");
                ////log_message('debug', "  Formula: $sms_parts × $recipients_count × $credits_per_sms_part = $total_credits");
                    
    return $total_credits;
}
    
        /**
     * Check if message contains Unicode characters
     */
    private function contains_unicode($message) {
    // Use the SAME logic as JavaScript
    // Only detect characters above 127 (outside ASCII)
    
    $length = mb_strlen($message, 'UTF-8');
    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($message, $i, 1, 'UTF-8');
        $charCode = ord($char);
        
        // Simple check: ASCII characters are 0-127
        if ($charCode > 127) {
            //log_message('debug', "Unicode character '$char' detected at position $i (charCode: $charCode)");
            return true;
        }
    }
    
    return false;
}
    
    /**
     * Deduct SMS credits after successful send
     */
    private function deduct_sms_credits($branch_id, $credits) {
        return $this->sendsmsmail_model->deduct_sms_units($branch_id, $credits);
    }
    
    /**
     * Get SMS credit balance for display
     */
    
       public function get_sms_balance() {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        // Use our custom method
        $branch_id = $this->get_current_branch_id();
        
        // Handle AJAX branch switching specifically
        if (is_superadmin_loggedin()) {
            $ajax_branch = $this->input->post('branch_id');
            if (!empty($ajax_branch)) {
                $branch_id = $ajax_branch;
                // Update session
                $this->session->set_userdata('branch_id', $branch_id);
                $this->session->set_userdata('current_branch_id', $branch_id);
                $this->session->unset_userdata('get_loggedin_branch_id');
            }
        }
        
        if (empty($branch_id)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Branch not determined',
                'balance' => 0
            ));
            return;
        }
        
        $balance = $this->sendsmsmail_model->get_sms_credit($branch_id);
        
        echo json_encode(array(
            'success' => true,
            'balance' => $balance,
            'branch_id' => $branch_id,
            'is_superadmin' => is_superadmin_loggedin(),
            'message' => 'Balance loaded successfully'
        ));
    }
    
        public function calculate_estimated_cost() {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        // Set JSON header
        header('Content-Type: application/json');
        
        try {
            $message = $this->input->post('message');
            $recipients_count = (int)$this->input->post('recipients_count');
            
            // Use our custom method
            $branch_id = $this->get_current_branch_id();
            
            // Handle AJAX branch parameter
            if (is_superadmin_loggedin()) {
                $ajax_branch = $this->input->post('branch_id');
                if (!empty($ajax_branch)) {
                    $branch_id = $ajax_branch;
                }
            }
            
            if (empty($branch_id)) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Branch not selected',
                    'estimated_cost' => 0,
                    'current_balance' => 0
                ));
                return;
            }
            
            if (empty($message)) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Message is empty',
                    'estimated_cost' => 0,
                    'current_balance' => 0
                ));
                return;
            }
            
            if ($recipients_count < 1) {
                $recipients_count = 1; // Minimum 1 recipient
            }
            
            $estimated_cost = $this->calculate_sms_cost($message, $recipients_count);
            $current_balance = $this->sendsmsmail_model->get_sms_credit($branch_id);
            
            echo json_encode(array(
                'success' => true,
                'estimated_cost' => $estimated_cost,
                'current_balance' => $current_balance,
                'remaining' => $current_balance - $estimated_cost,
                'can_send' => ($current_balance >= $estimated_cost),
                'branch_id' => $branch_id
            ));
            
        } catch (Exception $e) {
            //log_message('error', 'Error in calculate_estimated_cost: ' . $e->getMessage());
            
            echo json_encode(array(
                'success' => false,
                'message' => 'Calculation error: ' . $e->getMessage(),
                'estimated_cost' => 0,
                'current_balance' => 0
            ));
        }
    }

public function initialize_all_credits() {
    if (!$this->is_superadmin_loggedin()) {
        access_denied();
    }
    
    echo "<h2>Initialize SMS Credits for All Branches</h2>";
    
    // Get all branches
    $branches = $this->db->get('branch')->result();
    
    echo "<p>Found " . count($branches) . " branches</p>";
    
    foreach ($branches as $branch) {
        echo "<h3>Processing Branch: {$branch->name} (ID: {$branch->id})</h3>";
        
        // Check if credit record exists
        $this->db->where('branch_id', $branch->id);
        $exists = $this->db->get('sms_credit')->row();
        
        if ($exists) {
            echo "<p style='color: blue;'>✓ Already has credit record with {$exists->credits} credits</p>";
        } else {
            // Create new credit record
            $data = array(
                'branch_id' => $branch->id,
                'credits' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );
            
            if ($this->db->insert('sms_credit', $data)) {
                echo "<p style='color: green;'>✓ Created new credit record with 0 credits</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to create credit record</p>";
            }
        }
    }
    
    echo "<hr><p><strong>Initialization Complete!</strong></p>";
    echo "<p><a href='" . base_url('sendsmsmail/debug_credits') . "'>Check Credits</a> | ";
    echo "<a href='" . base_url('sendsmsmail/sms') . "'>Back to SMS Page</a></p>";
}


protected function get_current_branch_id() {
    // If superadmin and has selected a branch, use the selected branch
    if (is_superadmin_loggedin()) {
        // Check POST first (form submission)
        $post_branch = $this->input->post('branch_id');
        if (!empty($post_branch)) {
            // Update session to reflect selection
            $this->session->set_userdata('branch_id', $post_branch);
            $this->session->set_userdata('current_branch_id', $post_branch);
            // Remove the cached value to force refresh
            $this->session->unset_userdata('get_loggedin_branch_id');
            return $post_branch;
        }
        
        // Check GET (URL parameter)
        $get_branch = $this->input->get('branch_id');
        if (!empty($get_branch)) {
            $this->session->set_userdata('branch_id', $get_branch);
            $this->session->set_userdata('current_branch_id', $get_branch);
            $this->session->unset_userdata('get_loggedin_branch_id');
            return $get_branch;
        }
        
        // Check session for previously selected branch
        $session_branch = $this->session->userdata('current_branch_id');
        if (!empty($session_branch)) {
            return $session_branch;
        }
    }
    
    // For regular users or superadmin without selection, use get_loggedin_branch_id()
    return get_loggedin_branch_id();
}

  /**
 * Get actual recipient count (AJAX) - SIMPLIFIED FIXED VERSION
 */
public function get_recipient_count() {
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $branch_id = $this->get_current_branch_id();
    $type = $this->input->post('type');
    $data = $this->input->post('data');
    
    // Debug logging
    //log_message('debug', "get_recipient_count called - Type: $type, Branch: $branch_id");
    //log_message('debug', "Data received: " . print_r($data, true));
    //log_message('debug', "POST data: " . print_r($_POST, true));
    
    $count = 0;
    
    switch($type) {
        case '1': // Group
            if (!empty($data['roles'])) {
                foreach ($data['roles'] as $role_id) {
                    if ($role_id != '6' && $role_id != '7') {
                        // Staff count - SIMPLIFIED: Count all staff in role
                        $this->db->select('COUNT(staff.id) as count');
                        $this->db->from('staff');
                        $this->db->join('login_credential as lc', 'lc.user_id = staff.id', 'inner');
                        $this->db->where('lc.role', $role_id);
                        $this->db->where('staff.branch_id', $branch_id);
                        $result = $this->db->get()->row();
                        $count += $result ? $result->count : 0;
                        //log_message('debug', "Group staff role $role_id: " . ($result ? $result->count : 0));
                    }
                    if ($role_id == '6') {
                        // Parent count - SIMPLIFIED
                        $this->db->where('branch_id', $branch_id);
                        $count += $this->db->count_all_results('parent');
                        //log_message('debug', "Group parents: " . $this->db->count_all_results('parent'));
                    }
                    if ($role_id == '7') {
                        // Student count - SIMPLIFIED
                        $this->db->select('COUNT(e.student_id) as count');
                        $this->db->from('enroll as e');
                        $this->db->join('student as s', 's.id = e.student_id', 'inner');
                        $this->db->where('e.branch_id', $branch_id);
                        $this->db->where('e.session_id', get_session_id());
                        $result = $this->db->get()->row();
                        $count += $result ? $result->count : 0;
                        //log_message('debug', "Group students: " . ($result ? $result->count : 0));
                    }
                }
            }
            break;
            
        case '2': // Individual
            if (!empty($data['recipients'])) {
                // SIMPLIFIED: Just count the selected recipients
                $count = count($data['recipients']);
                //log_message('debug', "Individual recipients selected: " . $count);
            }
            break;
            
        case '3': // Class
            if (!empty($data['sections'])) {
                // SIMPLIFIED: Count all students in selected sections
                $this->db->select('COUNT(e.student_id) as count');
                $this->db->from('enroll as e');
                $this->db->where_in('e.section_id', $data['sections']);
                $this->db->where('e.branch_id', $branch_id);
                $this->db->where('e.session_id', get_session_id());
                $result = $this->db->get()->row();
                $count = $result ? $result->count : 0;
                //log_message('debug', "Class sections count: " . $count);
            }
            break;
    }
    
    // Log final count
    //log_message('debug', "Final recipient count for type '$type': $count");
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'type' => $type
    ]);
}

/**
 * SMS Credit Purchase Transactions Report
 * Printable report for branch SMS credit purchases
 */
/**
 * SMS Credit Purchase Transactions Report
 * Printable report for branch SMS credit purchases
 */
public function credit_purchase_report()
{
    // Check permission
    if (!get_permission('sendsmsmail_reports', 'is_view')) {
        access_denied();
    }
    
    $branch_id = $this->get_current_branch_id();
    $date_from = $this->input->get('date_from');
    $date_to = $this->input->get('date_to');
    $report_type = $this->input->get('report_type'); // summary or detailed
    $output_type = $this->input->get('output'); // html, pdf, excel
    
    // Set default dates if not provided
    if (empty($date_from)) {
        $date_from = date('Y-m-01'); // First day of current month
    }
    if (empty($date_to)) {
        $date_to = date('Y-m-d'); // Today
    }
    if (empty($report_type)) {
        $report_type = 'detailed';
    }
    
    // Get report data
    $report_data = $this->sendsmsmail_model->get_credit_purchase_report($branch_id, $date_from, $date_to, $report_type);
    
    // Get branch info
    $branch = $this->db->select('name, email, mobileno, address')
                      ->get_where('branch', ['id' => $branch_id])
                      ->row();
    
    $this->data['report_data'] = $report_data;
    $this->data['branch'] = $branch;
    $this->data['branch_id'] = $branch_id;
    $this->data['date_from'] = $date_from;
    $this->data['date_to'] = $date_to;
    $this->data['report_type'] = $report_type;
    $this->data['generated_by'] = $this->session->userdata('name');
    $this->data['generated_at'] = date('Y-m-d H:i:s');
    
    // Get summary statistics
    $this->data['summary'] = $this->sendsmsmail_model->get_credit_purchase_summary($branch_id, $date_from, $date_to);
    
    // Get all branches for superadmin
    if (is_superadmin_loggedin()) {
        $this->data['all_branches'] = $this->db->select('id, name')
                                               ->from('branch')
                                               ->order_by('name', 'ASC')
                                               ->get()
                                               ->result_array();
    }
    
    $this->data['title'] = 'SMS Credit Purchase Report';
    $this->data['sub_page'] = 'sendsmsmail/credit_purchase_report';
    $this->data['main_menu'] = 'sendsmsmail';
    
    // Handle different output types
    if ($output_type == 'pdf') {
        $this->generate_pdf_report();
    } elseif ($output_type == 'excel') {
        $this->export_credit_report_excel($report_data, $date_from, $date_to, $branch);
    } else {
        // HTML view for on-screen display and printing
        $this->load->view('layout/index', $this->data);
    }
}

/**
 * Generate PDF report using browser print functionality
 */
private function generate_pdf_report()
{
    $this->data['print_mode'] = true;
    $html = $this->load->view('sendsmsmail/reports/credit_purchase_print', $this->data, true);
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>SMS Credit Report - ' . date('Y-m-d') . '</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px;
                font-size: 12pt;
            }
            .school-header { 
                text-align: center; 
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .school-name {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .report-title {
                font-size: 18px;
                font-weight: bold;
                margin: 10px 0;
            }
            .summary-section {
                margin: 20px 0;
                border: 1px solid #333;
                padding: 15px;
                background-color: #f9f9f9;
            }
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            .summary-item {
                padding: 5px;
            }
            .summary-item strong {
                display: block;
                color: #333;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0; 
            }
            th { 
                background-color: #333; 
                color: white;
                padding: 10px; 
                text-align: left;
                border: 1px solid #333;
            }
            td { 
                padding: 8px; 
                border: 1px solid #ddd; 
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .status-completed { color: #28a745; font-weight: bold; }
            .status-pending { color: #ffc107; font-weight: bold; }
            .status-failed { color: #dc3545; font-weight: bold; }
            .footer {
                margin-top: 30px;
                padding-top: 10px;
                border-top: 1px solid #333;
                font-size: 10pt;
                color: #666;
                text-align: center;
            }
            .print-button {
                text-align: center;
                margin: 20px 0;
            }
            .print-button button {
                background-color: #007bff;
                color: white;
                border: none;
                padding: 12px 30px;
                font-size: 16px;
                border-radius: 5px;
                cursor: pointer;
                margin: 0 10px;
            }
            .print-button button:hover {
                background-color: #0056b3;
            }
            .print-button button.close-btn {
                background-color: #6c757d;
            }
            .print-button button.close-btn:hover {
                background-color: #545b62;
            }
            @media print {
                .print-button { display: none; }
                th { background-color: #333 !important; color: white !important; }
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        </style>
    </head>
    <body>
        <div class="print-button">
            <button onclick="window.print()">
                <i class="fa fa-print"></i> Print / Save as PDF
            </button>
            <button class="close-btn" onclick="window.close()">
                <i class="fa fa-times"></i> Close
            </button>
        </div>
        
        <div class="school-header">
            <div class="school-name">' . ($this->data['branch'] ? $this->data['branch']->name . ' Branch' : 'Multi-Branch School System') . '</div>
            <div class="report-title">SMS CREDIT PURCHASE REPORT</div>
            <p>
                <strong>Branch:</strong> ' . ($this->data['branch'] ? $this->data['branch']->name : 'All Branches') . ' | 
                <strong>Period:</strong> ' . date('d M Y', strtotime($this->data['date_from'])) . ' - ' . date('d M Y', strtotime($this->data['date_to'])) . '
            </p>
            <p>
                <strong>Generated:</strong> ' . date('d M Y H:i', strtotime($this->data['generated_at'])) . ' | 
                <strong>By:</strong> ' . $this->data['generated_by'] . '
            </p>
        </div>
        
        <div class="summary-section">
            <h3 style="margin-top: 0;">SUMMARY</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <strong>Completed Transactions:</strong>
                    ' . ($this->data['summary']->completed_transactions ?? 0) . '
                </div>
                <div class="summary-item">
                    <strong>Pending Transactions:</strong>
                    ' . ($this->data['summary']->pending_transactions ?? 0) . '
                </div>
                <div class="summary-item">
                    <strong>Failed Transactions:</strong>
                    ' . ($this->data['summary']->failed_transactions ?? 0) . '
                </div>
                <div class="summary-item">
                    <strong>Total Amount:</strong>
                    KES ' . number_format($this->data['summary']->total_amount ?? 0, 2) . '
                </div>
                <div class="summary-item">
                    <strong>Total SMS Units:</strong>
                    ' . ($this->data['summary']->total_units ?? 0) . '
                </div>
                <div class="summary-item">
                    <strong>Total Transactions:</strong>
                    ' . ($this->data['summary']->total_transactions ?? 0) . '
                </div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    ' . (is_superadmin_loggedin() && $this->data['branch_id'] == 'all' ? '<th width="10%">Branch</th>' : '') . '
                    <th width="15%">Date & Time</th>
                    <th width="15%">Receipt No</th>
                    <th width="12%">Phone</th>
                    <th width="10%">Amount</th>
                    <th width="8%">Units</th>
                    <th width="10%">Status</th>
                    <th width="8%">Credited</th>
                </tr>
            </thead>
            <tbody>';
    
    $total_amount = 0;
    $total_units = 0;
    $count = 1;
    
    foreach ($this->data['report_data'] as $tx) {
        echo '<tr>';
        echo '<td class="text-center">' . $count++ . '</td>';
        
        if (is_superadmin_loggedin() && $this->data['branch_id'] == 'all') {
            echo '<td>' . $tx->branch_name . '</td>';
        }
        
        echo '<td>' . date('d/m/Y H:i', strtotime($tx->created_at)) . '</td>';
        echo '<td>' . ($tx->receipt_number ?? '<span style="color: #ffc107;">Pending</span>') . '</td>';
        echo '<td>' . $tx->user_phone . '</td>';
        echo '<td class="text-right">' . number_format($tx->amount, 2) . '</td>';
        echo '<td class="text-center">' . $tx->sms_units . '</td>';
        echo '<td>';
        
        if ($tx->status == 'completed') {
            $total_amount += $tx->amount;
            $total_units += $tx->sms_units;
            echo '<span class="status-completed">✓ Completed</span>';
        } elseif ($tx->status == 'pending') {
            echo '<span class="status-pending">⏳ Pending</span>';
        } elseif ($tx->status == 'failed') {
            echo '<span class="status-failed">✗ Failed</span>';
        } else {
            echo ucfirst($tx->status);
        }
        
        echo '</td>';
        echo '<td class="text-center">' . ($tx->is_credited ? '✓ Yes' : '✗ No') . '</td>';
        echo '</tr>';
    }
    
    if (empty($this->data['report_data'])) {
        echo '<tr>';
        echo '<td colspan="' . (is_superadmin_loggedin() && $this->data['branch_id'] == 'all' ? '9' : '8') . '" class="text-center">';
        echo 'No transactions found for the selected period';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '      </tbody>
            <tfoot>
                <tr style="font-weight: bold; background-color: #f2f2f2;">
                    <td colspan="' . (is_superadmin_loggedin() && $this->data['branch_id'] == 'all' ? '5' : '4') . '" class="text-right">GRAND TOTAL:</td>
                    <td class="text-right">KES ' . number_format($total_amount, 2) . '</td>
                    <td class="text-center">' . $total_units . '</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="footer">
            <p>This is a computer-generated report. No signature is required.</p>
            <p>Generated on ' . date('d M Y H:i', strtotime($this->data['generated_at'])) . ' by ' . $this->data['generated_by'] . '</p>
        </div>
        
        <script>
            // Auto-open print dialog? Uncomment the line below if you want
            // window.onload = function() { window.print(); }
        </script>
    </body>
    </html>';
    exit;
}

/**
 * Export credit report to Excel (CSV format)
 */
private function export_credit_report_excel($report_data, $date_from, $date_to, $branch)
{
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sms_credit_report_' . date('Ymd_His') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // Report Header
    fputcsv($output, ['SMS CREDIT PURCHASE REPORT']);
    fputcsv($output, ['School:', $this->data['school_name']]);
    fputcsv($output, ['Branch:', $branch ? $branch->name : 'All Branches']);
    fputcsv($output, ['Period:', date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to))]);
    fputcsv($output, ['Generated:', date('d M Y H:i:s')]);
    fputcsv($output, ['Generated By:', $this->session->userdata('name')]);
    fputcsv($output, []); // Empty line
    
    // Summary Section
    fputcsv($output, ['SUMMARY']);
    $summary = $this->data['summary'];
    fputcsv($output, ['Completed Transactions:', $summary->completed_transactions ?? 0]);
    fputcsv($output, ['Pending Transactions:', $summary->pending_transactions ?? 0]);
    fputcsv($output, ['Failed Transactions:', $summary->failed_transactions ?? 0]);
    fputcsv($output, ['Total Amount:', 'KES ' . number_format($summary->total_amount ?? 0, 2)]);
    fputcsv($output, ['Total SMS Units:', $summary->total_units ?? 0]);
    fputcsv($output, ['Total Transactions:', $summary->total_transactions ?? 0]);
    fputcsv($output, []); // Empty line
    
    // Column Headers
    $headers = ['Date', 'Receipt No', 'Phone', 'Amount (KES)', 'SMS Units', 'Status', 'Credited', 'Checkout ID'];
    
    // Add Branch column for superadmin
    if (is_superadmin_loggedin() && $this->data['branch_id'] == 'all') {
        array_unshift($headers, 'Branch');
    }
    
    fputcsv($output, $headers);
    
    // Data Rows
    $total_amount = 0;
    $total_units = 0;
    
    foreach ($report_data as $tx) {
        $row = [
            date('Y-m-d H:i', strtotime($tx->created_at)),
            $tx->receipt_number ?? 'PENDING',
            $tx->user_phone,
            $tx->amount,
            $tx->sms_units,
            strtoupper($tx->status),
            $tx->is_credited ? 'YES' : 'NO',
            $tx->checkout_request_id
        ];
        
        // Add Branch column for superadmin
        if (is_superadmin_loggedin() && $this->data['branch_id'] == 'all') {
            array_unshift($row, $tx->branch_name);
        }
        
        fputcsv($output, $row);
        
        if ($tx->status == 'completed') {
            $total_amount += $tx->amount;
            $total_units += $tx->sms_units;
        }
    }
    
    // Empty line
    fputcsv($output, []);
    
    // Grand Total
    $total_row = ['GRAND TOTAL:', '', '', number_format($total_amount, 2), $total_units, '', '', ''];
    if (is_superadmin_loggedin() && $this->data['branch_id'] == 'all') {
        array_unshift($total_row, '');
    }
    fputcsv($output, $total_row);
    
    fclose($output);
    exit;
}
/**
 * Track SMS usage after sending
 */
public function track_sms_after_send($message_id, $branch_id, $sms_units, $recipient_count)
{
    // Get message details
    $this->db->where('id', $message_id);
    $message = $this->db->get('bulk_sms_email')->row();
    
    $metadata = [
        'campaign_name' => $message ? $message->campaign_name : 'SMS Campaign',
        'recipient_count' => $recipient_count,
        'message_length' => strlen($message->message ?? ''),
        'is_unicode' => $this->contains_unicode($message->message ?? '')
    ];
    
    // Track usage
    $this->subscription_model->track_sms_usage(
        $branch_id,
        $sms_units,
        $message_id,
        'campaign',
        $metadata
    );
}

/**
 * Track SMS purchase
 */
public function track_sms_purchase($transaction_id, $branch_id, $sms_units, $amount)
{
    $metadata = [
        'amount' => $amount,
        'receipt' => $this->db->where('id', $transaction_id)->get('sms_transaction')->row()->receipt_number ?? null
    ];
    
    $this->subscription_model->track_usage(
        $branch_id,
        'bulk_sms_and_email',
        'sms_purchased',
        $sms_units,
        $transaction_id,
        $metadata
    );
}



}
