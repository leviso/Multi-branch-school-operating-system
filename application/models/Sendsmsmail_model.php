<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Sendsmsmail_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getStaff($branch_id, $role_id = '', $staff_id = '')
    {
        $this->db->select('staff.id,staff.name,staff.mobileno,staff.email');
        $this->db->from('staff');
        $this->db->join('login_credential', 'login_credential.user_id = staff.id and login_credential.role != "6" and login_credential.role != "7"', 'inner');
        $this->db->where('staff.branch_id', $branch_id);
        if (!empty($role_id)) {
            $method = 'result_array';
            $this->db->where('login_credential.role', $role_id);
            $this->db->order_by('staff.id', 'ASC');
        }
        if (!empty($staff_id)) {
            $this->db->where('staff.id', $staff_id);
            $method = 'row_array';
        }
        return $this->db->get()->$method();
    }

    public function getParent($branch_id, $parent_id = '')
    {
        $this->db->select('id,name,email,mobileno');
        $this->db->where('branch_id', $branch_id);
        if (empty($parent_id)) {
            $method = 'result_array';
        } else {
            $this->db->where('id', $parent_id);
            $method = 'row_array';
        }
        return $this->db->get('parent')->$method();
    }

    public function getStudent($branch_id, $student_id = '')
    {
        $this->db->select('e.student_id,CONCAT_WS(" ",s.first_name, s.last_name) as name,s.mobileno,s.email');
        $this->db->from('enroll as e');
        $this->db->join('student as s', 'e.student_id = s.id', 'inner');
        $this->db->where('e.branch_id', $branch_id);
        if (empty($student_id)) {
            $method = 'result_array';
            $this->db->where('e.session_id', get_session_id());
            $this->db->order_by('s.id', 'ASC');
        } else {
            $this->db->where('s.id', $student_id);
            $method = 'row_array';
        }
        return $this->db->get()->$method();
    }

    public function getStudentBySection($class_id, $section_id, $branch_id)
    {
        $this->db->select('e.student_id,CONCAT_WS(" ",s.first_name, s.last_name) as name,s.mobileno,s.email');
        $this->db->from('enroll as e');
        $this->db->join('student as s', 'e.student_id = s.id', 'inner');
        $this->db->where('e.class_id', $class_id);
        $this->db->where('e.section_id', $section_id);
        $this->db->where('e.branch_id', $branch_id);
        $this->db->where('e.session_id', get_session_id());
        $this->db->order_by('s.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function saveTemplate($data)
    {
        $insertData = array(
            'branch_id' => $this->application_model->get_branch_id(),
            'name' => $data['template_name'],
            'body' => $this->input->post('message', false),
            'type' => $data['type'],
        );

        if (!isset($data['template_id'])) {
            $this->db->insert('bulk_msg_category', $insertData);
        } else {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $data['template_id']);
            $this->db->update('bulk_msg_category', $insertData);
        }
    }

    public function sendEmail($sendTo, $message, $name, $mobileNo, $emailSubject)
    {
        $message = str_replace('{name}', $name, $message);
        $message = str_replace('{email}', $sendTo, $message);
        $message = str_replace('{mobile_no}', $mobileNo, $message);
        $branchID = $this->application_model->get_branch_id();
        $data = array(
            'branch_id' => $branchID, 
            'recipient' => $sendTo, 
            'subject' => $emailSubject, 
            'message' => $message, 
        );
        if ($this->mailer->send($data)) {
            return true;
        } else {
            return false;
        }
    }

    public function sendSMS($sendTo, $message, $name, $eMail, $smsGateway, $dlt_templateID)
    {
      // ADD DEBUG TRACING
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    $caller = '';
    foreach ($backtrace as $trace) {
        if (isset($trace['class']) && $trace['class'] != 'Sendsmsmail_model') {
            $caller = $trace['class'] . '::' . $trace['function'];
            break;
        }
    }
    
    // log_message('info', '==== SMS SEND ATTEMPT ====');
    // log_message('info', 'Called from: ' . $caller);
    // log_message('info', 'To: ' . $sendTo);
    // log_message('info', 'Gateway: ' . $smsGateway);
    
        $message = str_replace('{name}', $name, $message);
        $message = str_replace('{email}', $eMail, $message);
        $message = str_replace('{mobile_no}', $sendTo, $message);
        if ($smsGateway == 'twilio') {
            $this->load->library("twilio");
            $get = $this->twilio->get_twilio();
            $from = $get['number'];
            $response = $this->twilio->sms($from, $sendTo, $message);
            if ($response->IsError) {
                return false;
            } else {
                return true;
            }
        }
        if ($smsGateway == 'clickatell') {
            $this->load->library("clickatell");
            return $this->clickatell->send_message($sendTo, $message);
        }
        if ($smsGateway == 'msg91') {
            $this->load->library("msg91");
            return $this->msg91->send($sendTo, $message, $dlt_templateID);
        }
        if ($smsGateway == 'bulksms') {
            $this->load->library("bulk");
            return $this->bulk->send($sendTo, $message);
        }
        if ($smsGateway == 'textlocal') {
            $this->load->library("textlocal");
            return $this->textlocal->sendSms($sendTo, $message);
        }
        if ($smsGateway == 'smscountry') {
            $this->load->library("smscountry");
            return $this->smscountry->send($sendTo, $message);
        }
        if ($smsGateway == 'bulksmsbd') {
            // log_message('info', 'Using BulkSMSBD gateway for: ' . $sendTo);
            
            // Try to determine branch ID
            $branch_id = null;
            
            // Method 1: Check if we're in a controller context
            $ci = &get_instance();
            if (isset($ci->branch_id)) {
                $branch_id = $ci->branch_id;
            }
            // Method 2: Use get_loggedin_branch_id if available
            elseif (function_exists('get_loggedin_branch_id')) {
                $branch_id = get_loggedin_branch_id();
            }
            // Method 3: Default to 1
            else {
                $branch_id = 1;
                // log_message('info', 'Using default branch ID 1 for BulkSMSBD');
            }
            
            // Load library with branch ID
            $this->load->library('bulksmsbd', array('branch_id' => $branch_id), 'bulksmsbd_instance');
            
            $response = $this->bulksmsbd_instance->send($sendTo, $message);
            
            // Parse JSON response
            $response_array = json_decode($response, true);
            
            if (is_array($response_array) && isset($response_array['responses'][0])) {
                $resp = $response_array['responses'][0];
                if (isset($resp['response-code']) && $resp['response-code'] == 200) {
                    // log_message('info', 'BulkSMSBD: SMS sent successfully. Message ID: ' . ($resp['messageid'] ?? 'N/A'));
                    return true;
                } else {
                    // log_message('error', 'BulkSMSBD Error: Response code ' . ($resp['response-code'] ?? 'Unknown') . ' - ' . ($resp['response-description'] ?? 'No description'));
                    return false;
                }
            } elseif (strpos($response, 'Success') !== false || strpos($response, 'success') !== false) {
                // log_message('info', 'BulkSMSBD: SMS sent successfully (text success)');
                return true;
            } else {
                // log_message('error', 'BulkSMSBD Error Response: ' . $response);
                return false;
            }
        }
        if ($smsGateway == 'customsms') {
            $this->load->library("custom_sms");
            $res = $this->custom_sms->send($sendTo, $message, $dlt_templateID);
        }
    }

    /**
     * Get SMS credit balance for a branch
     */
    // Replace the existing get_sms_credit() method with this improved version:
public function get_sms_credit($branch_id) {
    $this->db->select('credits');
    $this->db->from('sms_credit');
    $this->db->where('branch_id', $branch_id);
    $query = $this->db->get();
    
    if ($query->num_rows() > 0) {
        $result = $query->row();
        return (int)$result->credits;
    }
    
    // Initialize if not exists - but only if branch exists
    $this->db->where('id', $branch_id);
    $branch_exists = $this->db->get('branch')->num_rows() > 0;
    
    if ($branch_exists) {
        $data = array(
            'branch_id' => $branch_id,
            'credits' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        if ($this->db->insert('sms_credit', $data)) {
            // log_message('info', "Initialized SMS credits for branch $branch_id");
            return 0;
        }
    }
    
    // log_message('error', "Failed to get or initialize SMS credits for branch $branch_id");
    return 0; // Return 0 instead of false for consistency
}
    
    /**
     * Initialize SMS credit for a branch
     */
    public function initialize_sms_credit($branch_id) {
        $data = array(
            'branch_id' => $branch_id,
            'credits' => 0
        );
        $this->db->insert('sms_credit', $data);
    }
    
    /**
     * Create a new SMS transaction
     */
    /**
 * Create a new SMS transaction with error handling
 */
public function create_transaction($data) {
    try {
        // Log the data being inserted
        // log_message('debug', 'Inserting transaction data: ' . print_r($data, true));
        
        $this->db->insert('sms_transaction', $data);
        
        $insert_id = $this->db->insert_id();
        $affected_rows = $this->db->affected_rows();
        
        // Log results
        // log_message('debug', 'Insert ID: ' . $insert_id . ', Affected rows: ' . $affected_rows);
        
        if ($insert_id) {
            return $insert_id;
        } else {
            $error = $this->db->error();
            // log_message('error', 'Database insert failed: ' . $error['message']);
            return false;
        }
    } catch (Exception $e) {
        // log_message('error', 'Exception in create_transaction: ' . $e->getMessage());
        return false;
    }
}
    
   /**
 * Update transaction with callback data
 */
public function update_transaction($checkout_request_id, $data) {
    // Ensure callback_raw is properly set
    if (isset($data['callback_raw'])) {
        // If it's an array, encode it
        if (is_array($data['callback_raw'])) {
            $data['callback_raw'] = json_encode($data['callback_raw']);
        }
        // Log the size for debugging
        // log_message('debug', 'Saving callback_raw, size: ' . strlen($data['callback_raw']) . ' bytes');
    }
    
    $this->db->where('checkout_request_id', $checkout_request_id);
    $result = $this->db->update('sms_transaction', $data);
    
    if ($result) {
        // log_message('debug', "Transaction updated for checkout ID: {$checkout_request_id}");
        // log_message('debug', "Update data: " . print_r($data, true));
    }
    
    return $result;
}
    
    /**
     * Get transaction by checkout request ID
     */
    public function get_transaction_by_checkout_id($checkout_request_id) {
        $this->db->select('*');
        $this->db->from('sms_transaction');
        $this->db->where('checkout_request_id', $checkout_request_id);
        $query = $this->db->get();
        
        return $query->num_rows() > 0 ? $query->row() : false;
    }
    
    // Add this method to your Sendsmsmail_model
    public function get_completed_transactions_without_receipt() {
    $this->db->select('*');
    $this->db->from('sms_transaction');
    $this->db->where('status', 'completed');
    $this->db->where('receipt_number IS NULL OR receipt_number = ""');
    $this->db->order_by('created_at', 'DESC');
    $query = $this->db->get();
    
    return $query->result();
}


public function log_api_call($endpoint, $response_code) {
    $data = array(
        'endpoint' => $endpoint,
        'response_code' => $response_code,
        'created_at' => date('Y-m-d H:i:s')
    );
    $this->db->insert('api_call_logs', $data);
}

    public function get_recent_api_calls($minutes = 10) {
    $this->db->select('endpoint, response_code, COUNT(*) as call_count');
    $this->db->from('api_call_logs');
    $this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime("-{$minutes} minutes")));
    $this->db->group_by('endpoint, response_code');
    $this->db->order_by('call_count', 'DESC');
    return $this->db->get()->result();
}
    
    /**
     * Credit SMS units to branch
     */
    public function credit_sms_units($branch_id, $sms_units) {
        // Check if branch exists in sms_credit table
        $this->db->select('id, credits');
        $this->db->from('sms_credit');
        $this->db->where('branch_id', $branch_id);
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            // Update existing
            $current_credits = $query->row()->credits;
            $this->db->where('branch_id', $branch_id);
            $this->db->set('credits', $current_credits + $sms_units);
            $this->db->update('sms_credit');
        } else {
            // Insert new
            $data = array(
                'branch_id' => $branch_id,
                'credits' => $sms_units
            );
            $this->db->insert('sms_credit', $data);
        }
        
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Get billing history for a branch
     */
    public function get_billing_history($branch_id, $limit = 10) {
        $this->db->select('*');
        $this->db->from('sms_transaction');
        $this->db->where('branch_id', $branch_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        $query = $this->db->get();
        
        return $query->result();
    }
    
    /**
     * Get all branches SMS credits (for superadmin)
     */
    public function get_all_branches_credits() {
        $this->db->select('b.name as branch_name, sc.credits, sc.updated_at');
        $this->db->from('sms_credit sc');
        $this->db->join('branch b', 'b.id = sc.branch_id', 'left');
        $this->db->order_by('b.name', 'ASC');
        $query = $this->db->get();
        
        return $query->result();
    }
    
    /**
     * Deduct SMS units when sending SMS
     */
    public function deduct_sms_units($branch_id, $units) {
        $this->db->where('branch_id', $branch_id);
        $this->db->set('credits', 'credits - ' . (int)$units, FALSE);
        $this->db->update('sms_credit');
        
        return $this->db->affected_rows() > 0;
    }

    
          /**
     * Send SMS with credit checking wrapper
     * This wraps the original sendSMS method with credit validation
     */
    public function sendSMS_with_credit_check($sendTo, $message, $name, $eMail, $smsGateway, $dlt_templateID, $branch_id)
    {
        // Load SMS config with error handling
        $ci =& get_instance();
        $sms_config = array();
        
        // Try to load config, use defaults if fails
        try {
            $ci->config->load('smsconfig', TRUE);
            $sms_config = $ci->config->item('sms_credit');
        } catch (Exception $e) {
            // log_message('error', 'Failed to load smsconfig: ' . $e->getMessage());
        }
        
        // Set default values if config is missing
        if (empty($sms_config) || !isset($sms_config['credit_calculation'])) {
            // log_message('warning', 'SMS config missing, using default values');
            $sms_config = array(
                'credit_calculation' => array(
                    'chars_per_sms' => 160,
                    'credits_per_sms' => 1,
                    'unicode_chars_per_sms' => 70,
                    'unicode_credits_multiplier' => 2,
                    'gsm_characters' => '@£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà',
                    'extended_gsm' => '^{}\[~]|€'
                )
            );
        }
        
        // Check if message contains Unicode
        $is_unicode = false;
        $gsm_chars = isset($sms_config['credit_calculation']['gsm_characters']) ? 
                     $sms_config['credit_calculation']['gsm_characters'] : 
                     '@£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';
        
        $extended_gsm = isset($sms_config['credit_calculation']['extended_gsm']) ? 
                        $sms_config['credit_calculation']['extended_gsm'] : 
                        '^{}\[~]|€';
        
        $full_gsm = $gsm_chars . $extended_gsm;
        
        $length = mb_strlen($message, 'UTF-8');
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($message, $i, 1, 'UTF-8');
            if (mb_strpos($full_gsm, $char) === false) {
                $is_unicode = true;
                break;
            }
        }
        
        // Calculate SMS parts
        $chars_per_sms = $is_unicode ? 70 : 160;
        
        // Override with config if available
        if ($is_unicode && isset($sms_config['credit_calculation']['unicode_chars_per_sms'])) {
            $chars_per_sms = (int)$sms_config['credit_calculation']['unicode_chars_per_sms'];
        } elseif (!$is_unicode && isset($sms_config['credit_calculation']['chars_per_sms'])) {
            $chars_per_sms = (int)$sms_config['credit_calculation']['chars_per_sms'];
        }
        
        // Ensure we don't divide by zero
        if ($chars_per_sms <= 0) {
            $chars_per_sms = $is_unicode ? 70 : 160;
            // log_message('warning', 'chars_per_sms was invalid, reset to: ' . $chars_per_sms);
        }
        
        $sms_parts = ceil(mb_strlen($message, 'UTF-8') / $chars_per_sms);
        
        // Calculate credits needed
        $credits_per_sms = 1;
        if ($is_unicode && isset($sms_config['credit_calculation']['unicode_credits_multiplier'])) {
            $credits_per_sms = (int)$sms_config['credit_calculation']['unicode_credits_multiplier'];
        } elseif (isset($sms_config['credit_calculation']['credits_per_sms'])) {
            $credits_per_sms = (int)$sms_config['credit_calculation']['credits_per_sms'];
        }
        
        $credits_needed = $sms_parts * $credits_per_sms;
        
        // Check available credits
        $available_credits = $this->get_sms_credit($branch_id);
        
        if ($available_credits < $credits_needed) {
            // log_message('error', "Insufficient credits to send SMS. Needed: $credits_needed, Available: $available_credits");
            return false;
        }
        
        // Send SMS using original method
        $result = $this->sendSMS($sendTo, $message, $name, $eMail, $smsGateway, $dlt_templateID);
        
        if ($result) {
            // Deduct credits only if send was successful
            $this->deduct_sms_units($branch_id, $credits_needed);
            // log_message('info', "Deducted $credits_needed credits for SMS to $sendTo");
        }
        
        return $result;
    }

public function get_scheduled_messages($branch_id = null)
{
    $this->db->select('*');
    $this->db->from('bulk_sms_email');
    $this->db->where('posting_status', 1); // Only SCHEDULED messages
    
    if ($branch_id) {
        $this->db->where('branch_id', $branch_id);
    } elseif (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', get_loggedin_branch_id());
    }
    
    $this->db->order_by('schedule_time', 'ASC');
    return $this->db->get()->result_array();
}

public function get_message_by_id($id, $branch_id = null)
{
    $this->db->where('id', $id);
    if ($branch_id) {
        $this->db->where('branch_id', $branch_id);
    } elseif (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', get_loggedin_branch_id());
    }
    return $this->db->get('bulk_sms_email')->row_array();
}

public function update_scheduled_status($id, $status)
{
    $this->db->where('id', $id);
    $this->db->set('posting_status', $status);
    
    if ($status == 0) { // cancelled
        $this->db->set('additional', ''); // clear recipient data
    }
    
    return $this->db->update('bulk_sms_email');
}

public function process_scheduled_message($message_id)
{
    // Get the scheduled message
    $message = $this->db->where('id', $message_id)->get('bulk_sms_email')->row_array();
    
    if (!$message || $message['posting_status'] != 1) {
        return false; // Not scheduled or already processed
    }
    
    // Update status to processing (0 = processing)
    $this->db->where('id', $message_id)->update('bulk_sms_email', ['posting_status' => 0]);
    
    $usersList = json_decode($message['additional'], true);
    $success_count = 0;
    $failed_count = 0;
    
    foreach ($usersList as $user) {
        $result = false;
        
        if ($message['message_type'] == 1) { // SMS
            // Check credits before sending
            $available_credits = $this->get_sms_credit($message['branch_id']);
            $credits_needed = $this->calculate_sms_cost($message['message'], 1);
            
            if ($available_credits >= $credits_needed) {
                $result = $this->sendSMS_with_credit_check(
                    $user['mobileno'], 
                    $message['message'], 
                    $user['name'], 
                    $user['email'], 
                    $message['sms_gateway'], 
                    '', // DLT template ID (you might want to store this)
                    $message['branch_id']
                );
                
                if ($result) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
            } else {
                $failed_count++; // Insufficient credits
            }
        } else { // Email
            $result = $this->sendEmail(
                $user['email'], 
                $message['message'], 
                $user['name'], 
                $user['mobileno'], 
                $message['email_subject']
            );
            
            if ($result) {
                $success_count++;
            } else {
                $failed_count++;
            }
        }
    }
    
    // Update final status
    $final_status = 2; // completed
    if ($success_count == 0 && $failed_count > 0) {
        $final_status = 3; // failed
    } elseif ($success_count > 0 && $failed_count > 0) {
        $final_status = 4; // partially sent
    }
    
    $update_data = [
        'posting_status' => $final_status,
        'successfully_sent' => $success_count,
        'additional' => '', // Clear after processing
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($message['message_type'] == 1) {
        $update_data['credits_used'] = $this->calculate_sms_cost($message['message'], count($usersList));
    }
    
    $this->db->where('id', $message_id)->update('bulk_sms_email', $update_data);
    
    return [
        'success' => $success_count,
        'failed' => $failed_count,
        'total' => count($usersList)
    ];
}

/**
 * Reserve SMS credits for a scheduled message - FIXED VERSION
 */
public function reserve_sms_credits($branch_id, $credits_needed, $campaign_name, $message_id = null) {
    // Start transaction
    $this->db->trans_start();
    
    try {
        // Check current credits
        $current_credits = $this->get_sms_credit($branch_id);
        
        if ($current_credits < $credits_needed) {
            throw new Exception("Insufficient credits to reserve. Needed: $credits_needed, Available: $current_credits");
        }
        
        // Deduct credits immediately for scheduled message
        $this->db->where('branch_id', $branch_id);
        $this->db->set('credits', 'credits - ' . (int)$credits_needed, FALSE);
        $deducted = $this->db->update('sms_credit');
        
        if (!$deducted) {
            throw new Exception("Failed to deduct credits from branch $branch_id");
        }
        
        // Log the reservation
        $reservation_data = [
            'branch_id' => $branch_id,
            'message_id' => $message_id,
            'credits_reserved' => $credits_needed,
            'campaign_name' => $campaign_name,
            'reservation_type' => 'scheduled_sms',
            'status' => 'reserved',
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ];
        
        $this->db->insert('sms_credit_reservations', $reservation_data);
        $reservation_id = $this->db->insert_id();
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            // log_message('error', "Transaction failed for reserving credits in branch $branch_id");
            return false;
        }
        
        // log_message('info', "✅ Reserved $credits_needed credits for scheduled SMS. Branch: $branch_id, Message: $message_id, Reservation ID: $reservation_id");
        return $reservation_id;
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        // log_message('error', "❌ Error reserving credits: " . $e->getMessage());
        return false;
    }
}

/**
 * Return reserved credits when scheduled message is cancelled - FIXED VERSION
 */
public function return_reserved_credits($message_id, $reason = 'cancelled') {
    $this->db->trans_start();
    
    try {
        // Get the reservation by message_id
        $this->db->where('message_id', $message_id);
        $this->db->where('status', 'reserved'); // Only return RESERVED credits
        $reservation = $this->db->get('sms_credit_reservations')->row();
        
        if (!$reservation) {
            // No reservation found - maybe credits were deducted directly
            // Check if credits were deducted without reservation
            $this->db->select('credits_used, branch_id');
            $this->db->where('id', $message_id);
            $message = $this->db->get('bulk_sms_email')->row();
            
            if ($message && $message->credits_used > 0) {
                // Direct deduction without reservation - return credits directly
                $this->db->where('branch_id', $message->branch_id);
                $this->db->set('credits', 'credits + ' . (int)$message->credits_used, FALSE);
                $this->db->update('sms_credit');
                
                // log_message('info', "✅ Returned {$message->credits_used} directly deducted credits for cancelled message {$message_id}");
                
                $this->db->trans_complete();
                return true;
            }
            
            // log_message('warning', "No reservation found for message $message_id");
            $this->db->trans_complete();
            return false;
        }
        
        // Return reserved credits to branch
        $this->db->where('branch_id', $reservation->branch_id);
        $this->db->set('credits', 'credits + ' . (int)$reservation->credits_reserved, FALSE);
        $this->db->update('sms_credit');
        
        // log_message('info', "✅ Returned {$reservation->credits_reserved} reserved credits to branch {$reservation->branch_id}");
        
        // Update reservation status
        $update_data = [
            'status' => $reason,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($reason == 'cancelled') {
            $update_data['cancelled_at'] = date('Y-m-d H:i:s');
        } elseif ($reason == 'returned') {
            $update_data['returned_at'] = date('Y-m-d H:i:s');
        }
        
        $this->db->where('id', $reservation->id);
        $this->db->update('sms_credit_reservations', $update_data);
        
        $this->db->trans_complete();
        
        $success = $this->db->trans_status();
        
        if ($success) {
            // log_message('info', "✅ Reservation {$reservation->id} marked as $reason for message {$message_id}");
        }
        
        return $success;
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        // log_message('error', "❌ Error returning credits: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark reserved credits as used when message is successfully sent - NEW METHOD
 */
public function mark_reservation_used($message_id, $actual_credits_used = null) {
    $this->db->trans_start();
    
    try {
        // Get the reservation
        $this->db->where('message_id', $message_id);
        $this->db->where('status', 'reserved');
        $reservation = $this->db->get('sms_credit_reservations')->row();
        
        if (!$reservation) {
            throw new Exception("No reserved credits found for message $message_id");
        }
        
        $update_data = [
            'status' => 'used',
            'used_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($actual_credits_used !== null) {
            $update_data['credits_used'] = $actual_credits_used;
            
            // If partial send, return unused credits
            if ($actual_credits_used < $reservation->credits_reserved) {
                $unused_credits = $reservation->credits_reserved - $actual_credits_used;
                $this->db->where('branch_id', $reservation->branch_id);
                $this->db->set('credits', 'credits + ' . (int)$unused_credits, FALSE);
                $this->db->update('sms_credit');
                // log_message('info', "✅ Returned {$unused_credits} unused credits for partial send");
            }
        }
        
        $this->db->where('id', $reservation->id);
        $this->db->update('sms_credit_reservations', $update_data);
        
        $this->db->trans_complete();
        
        $success = $this->db->trans_status();
        
        if ($success) {
            // log_message('info', "✅ Reservation {$reservation->id} marked as USED for message {$message_id}");
        }
        
        return $success;
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        // log_message('error', "❌ Error marking reservation as used: " . $e->getMessage());
        return false;
    }
}

/**
 * Get reservation by message ID
 */
public function get_reservation_by_message($message_id) {
    $this->db->where('message_id', $message_id);
    return $this->db->get('sms_credit_reservations')->row();
}

public function log_delivery($message_id, $recipient_id, $recipient_type, $status, $gateway_response = '')
{
    $data = [
        'message_id' => $message_id,
        'recipient_id' => $recipient_id,
        'recipient_type' => $recipient_type,
        'status' => $status,
        'gateway_response' => $gateway_response,
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $this->get_current_branch_id()
    ];
    
    $this->db->insert('sms_email_delivery_logs', $data);
    return $this->db->insert_id();
}

public function get_delivery_logs($message_id = null, $branch_id = null)
{
    $this->db->select('l.*, b.name as branch_name');
    $this->db->from('sms_email_delivery_logs l');
    $this->db->join('branch b', 'b.id = l.branch_id', 'left');
    
    if ($message_id) {
        $this->db->where('l.message_id', $message_id);
    }
    
    if ($branch_id) {
        $this->db->where('l.branch_id', $branch_id);
    } elseif (!is_superadmin_loggedin()) {
        $this->db->where('l.branch_id', get_loggedin_branch_id());
    }
    
    $this->db->order_by('l.sent_at', 'DESC');
    return $this->db->get()->result_array();
}

public function log_sms_send($message_id, $recipient_count, $branch_id, $credits_used)
{
    $log_data = [
        'message_id' => $message_id,
        'recipient_count' => $recipient_count,
        'branch_id' => $branch_id,
        'credits_used' => $credits_used,
        'sent_at' => date('Y-m-d H:i:s')
    ];
    
    // Optional: Create a quick send log table
    // $this->db->insert('sms_send_logs', $log_data);
    
    // Or log to existing delivery logs
    if ($recipient_count > 0) {
        $this->db->insert('sms_email_delivery_logs', [
            'message_id' => $message_id,
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'branch_id' => $branch_id
        ]);
    }
}
// Add to Sendsmsmail_model.php
/**
 * Check if interview SMS template exists
 */
public function get_interview_sms_template($template_id, $branch_id)
{
    return $this->db->get_where('sms_template_details', [
        'template_id' => $template_id,
        'branch_id' => $branch_id
    ])->row_array();
}

/**
 * Get interview communication history
 */
public function get_interview_communications($interview_id)
{
    $this->db->where('interview_id', $interview_id);
    $this->db->order_by('created_at', 'DESC');
    return $this->db->get('interview_communications')->result_array();
}

/**
 * Log interview communication
 */
public function log_interview_communication($data)
{
    return $this->db->insert('interview_communications', $data);
}
// In sendsmsmail_model.php
public function is_sms_successful($response)
{
    if (empty($response)) return false;
    
    // JSON response (BulkSMSBD)
    if (strpos($response, '{') === 0) {
        $response_array = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($response_array)) {
            if (isset($response_array['responses'][0]['response-code'])) {
                return $response_array['responses'][0]['response-code'] == 200;
            }
        }
    }
    
    // String checks
    $success_indicators = ['200', 'Success', 'success', 'messageid', 'sent', 'ok'];
    foreach ($success_indicators as $indicator) {
        if (stripos($response, $indicator) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get credit purchase report data
 */
public function get_credit_purchase_report($branch_id, $date_from, $date_to, $report_type = 'detailed')
{
    $this->db->select('t.*, b.name as branch_name');
    $this->db->from('sms_transaction t');
    $this->db->join('branch b', 'b.id = t.branch_id', 'left');
    $this->db->where('DATE(t.created_at) >=', $date_from);
    $this->db->where('DATE(t.created_at) <=', $date_to);
    
    // Branch filtering based on user role
    if (!is_superadmin_loggedin()) {
        $this->db->where('t.branch_id', $branch_id);
    } elseif (!empty($branch_id) && $branch_id != 'all') {
        $this->db->where('t.branch_id', $branch_id);
    }
    
    $this->db->order_by('t.created_at', 'DESC');
    
    $query = $this->db->get();
    return $query->result();
}

/**
 * Get credit purchase summary
 */
public function get_credit_purchase_summary($branch_id, $date_from, $date_to)
{
    $this->db->select('
        COUNT(*) as total_transactions,
        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_transactions,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_transactions,
        SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_transactions,
        SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount,
        SUM(CASE WHEN status = "completed" THEN sms_units ELSE 0 END) as total_units
    ');
    $this->db->from('sms_transaction');
    $this->db->where('DATE(created_at) >=', $date_from);
    $this->db->where('DATE(created_at) <=', $date_to);
    
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branch_id);
    } elseif (!empty($branch_id) && $branch_id != 'all') {
        $this->db->where('branch_id', $branch_id);
    }
    
    $query = $this->db->get();
    return $query->row();
}

/**
 * Get monthly credit purchase trend
 */
public function get_monthly_credit_trend($branch_id, $year)
{
    $this->db->select('
        MONTH(created_at) as month,
        COUNT(*) as transaction_count,
        SUM(amount) as total_amount,
        SUM(sms_units) as total_units
    ');
    $this->db->from('sms_transaction');
    $this->db->where('YEAR(created_at)', $year);
    $this->db->where('status', 'completed');
    
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branch_id);
    } elseif (!empty($branch_id) && $branch_id != 'all') {
        $this->db->where('branch_id', $branch_id);
    }
    
    $this->db->group_by('MONTH(created_at)');
    $this->db->order_by('MONTH(created_at)', 'ASC');
    
    $query = $this->db->get();
    $results = $query->result_array();
    
    // Format for chart
    $months = [];
    $amounts = [];
    $units = [];
    
    for ($i = 1; $i <= 12; $i++) {
        $found = false;
        foreach ($results as $row) {
            if ($row['month'] == $i) {
                $months[] = date('M', mktime(0, 0, 0, $i, 1));
                $amounts[] = (float)$row['total_amount'];
                $units[] = (int)$row['total_units'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $months[] = date('M', mktime(0, 0, 0, $i, 1));
            $amounts[] = 0;
            $units[] = 0;
        }
    }
    
    return [
        'months' => $months,
        'amounts' => $amounts,
        'units' => $units
    ];
}
}
