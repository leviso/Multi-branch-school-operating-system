<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.8
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Online_admission.php
 * @copyright : Reserved Synobix Team
 */

class Online_admission extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('custom_fields');
        $this->load->model('online_admission_model');
        $this->load->model('student_fields_model');
        $this->load->model('email_model');
        $this->load->model('sms_model');
        $this->load->model('sendsmsmail_model');
    }

    /**
 * Validate status transition according to workflow rules
 * @throws Exception if transition is invalid
 */
private function validate_status_transition($admission_id, $current_status, $target_status)
{
    $allowed_transitions = [
        1 => [2, 3, 4],  // Pending → Approved, Declined, Interview Scheduled
        4 => [5],        // Interview Scheduled → Interview Completed
        5 => [2, 3],     // Interview Completed → Approved, Declined
    ];
    
    if (!isset($allowed_transitions[$current_status])) {
        throw new Exception("Invalid current status: {$current_status}");
    }
    
    if (!in_array($target_status, $allowed_transitions[$current_status])) {
        throw new Exception("Cannot change status from {$current_status} to {$target_status}");
    }
}

/**
 * Get branch ID with validation
 */
private function get_validated_branch_id()
{
    $branchID = $this->application_model->get_branch_id();
    
    // Superadmin fallback
    if (empty($branchID) && is_superadmin_loggedin()) {
        $branchID = $this->session->userdata('loggedin_branch') ?: 1;
    }
    
    return (int)$branchID;
}

/**
 * Apply branch filter to query
 */
private function apply_branch_filter($table = 'online_admission_interviews', $alias = null)
{
    if (!is_superadmin_loggedin()) {
        $branchID = $this->get_validated_branch_id();
        $field = $alias ? "{$alias}.branch_id" : "branch_id";
        $this->db->where($field, $branchID);
    }
}

/**
 * Check if user can modify admission (not already approved/declined)
 */
private function can_modify_admission($admission_id)
{
    $this->db->select('status');
    $this->db->where('id', $admission_id);
    $this->apply_branch_filter('online_admission');
    $admission = $this->db->get('online_admission')->row_array();
    
    if (empty($admission)) {
        return ['can_modify' => false, 'message' => 'Admission not found'];
    }
    
    if (in_array($admission['status'], [2, 3])) {
        $status_text = $admission['status'] == 2 ? 'APPROVED' : 'DECLINED';
        return ['can_modify' => false, 'message' => "Admission has been {$status_text}"];
    }
    
    return ['can_modify' => true];
}

/**
 * Check for active interview
 */
private function has_active_interview($admission_id)
{
    $this->db->where('admission_id', $admission_id);
    $this->db->where_in('status', ['scheduled', 'rescheduled']);
    $this->apply_branch_filter();
    return $this->db->get('online_admission_interviews')->num_rows() > 0;
}

   public function index()
{
    // check access permission
    if (!get_permission('online_admission', 'is_view')) {
        access_denied();
    }

    $branchID = $this->application_model->get_branch_id();
    
    // CRITICAL FIX: Get interviewers for schedule interview modal
    $this->data['interviewers'] = $this->get_interviewers($branchID);
    
    if (isset($_POST['search'])) {
        $classID = $this->input->post('class_id');
        $sectionID = $this->input->post('section_id');
        $this->data['students'] = $this->online_admission_model->getOnlineAdmission($classID, $branchID);
    }
    
    // Pass branch_id to view
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('student_list');
    $this->data['main_menu'] = 'admission';
    $this->data['sub_page'] = 'online_admission/index';
    $this->data['headerelements'] = array(
        'js' => array(
            'js/student.js',
        ),
    );
    $this->load->view('layout/index', $this->data);
}
    

    // delete student from database
    public function delete($id)
    {
        if (get_permission('online_admission', 'is_delete')) {
            $branch_id = $this->db->select('branch_id')->where('id', $id)->get('online_admission')->row()->branch_id;

            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('online_admission');
            if ($this->db->affected_rows() > 0) {
                $result = $this->db
                    ->where(array('form_to' => 'online_admission', 'branch_id' => $branch_id))
                    ->get('custom_field')->result_array();
                foreach ($result as $key => $value) {
                    $this->db->where('relid', $id);
                    $this->db->where('field_id', $value['id']);
                    $this->db->delete('custom_fields_values');
                }
            }
        }
    }

 public function decline($id)
{
    if (!get_permission('online_admission', 'is_add')) {
        access_denied();
    }
    
    try {
        // Start transaction
        $this->db->trans_start();
        
        // Get admission with branch check
        $this->db->select('*');
        $this->db->where('id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $admission = $this->db->get('online_admission')->row_array();
        
        if (empty($admission)) {
            throw new Exception('Admission not found');
        }
        
        // ========== STATUS VALIDATION ==========
        // Only allowed: status 1→3 or 5→3
        if (!in_array($admission['status'], [1, 5])) {
            throw new Exception('Cannot decline admission with status: ' . $admission['status']);
        }

        // ========== VALIDATION FOR POST-INTERVIEW DECLINE ==========
        if ($admission['status'] == 5) {
            // Get the completed interview
            $interview = $this->db->where('admission_id', $admission['id'])
                                 ->where('status', 'completed')
                                 ->order_by('updated_at', 'DESC')
                                 ->limit(1)
                                 ->get('online_admission_interviews')
                                 ->row_array();
            
            if (!empty($interview)) {
                // Log for audit trail
                error_log("POST-INTERVIEW DECLINE: Admission ID {$admission['id']}, Interview ID {$interview['id']}, Outcome: {$interview['outcome']}");
                
                // Update interview notes (optional, for audit trail)
                $interview_notes = ($interview['notes'] ?? '') . "\n\n[DECLINE DECISION] " . date('d M Y H:i') . 
                                 ": Admission declined after interview.";
                
                $this->db->where('id', $interview['id']);
                $this->db->update('online_admission_interviews', [
                    'notes' => $interview_notes,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        // Validate transition
        $this->validate_status_transition($admission['id'], $admission['status'], 3);
        
        // Update status to declined
        $this->db->where('id', $id);
        $this->db->update('online_admission', array('status' => 3));
        
        // Send decline notification
        $sms_result = $this->send_decline_notification($admission);
        
        // Commit transaction
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        // STANDARDIZED RESPONSE
        $message = 'Admission declined successfully' . 
                  ($sms_result['success'] ? ' and notification sent' : ' but notification failed');
        
        if ($this->input->is_ajax_request()) {
            echo json_encode([
                'success' => true,
                'message' => $message,
                'redirect' => base_url('online_admission')
            ]);
        } else {
            set_alert('success', $message);
            redirect(base_url('online_admission'));
        }
        
    } catch (Exception $e) {
        // Rollback on error
        $this->db->trans_rollback();
        
        $error_msg = 'Error: ' . $e->getMessage();
        
        if ($this->input->is_ajax_request()) {
            echo json_encode([
                'success' => false,
                'message' => $error_msg
            ]);
        } else {
            set_alert('error', $error_msg);
            redirect(base_url('online_admission'));
        }
    }
}

private function send_decline_notification($admission)
{
    $branchID = $admission['branch_id'];
    
    // Get decline SMS template (template_id = 17 for rejection)
    $template = $this->db->get_where('sms_template_details', [
        'template_id' => 17, // Decline template
        'branch_id' => $branchID
    ])->row_array();
    
    if (empty($template)) {
        return ['success' => false, 'message' => 'Decline template not configured'];
    }
    
    // ========== FIX: GET BRANCH CONTACT INFO PROPERLY ==========
    // Get branch details with ALL contact fields
    $branch = $this->db->select('name, mobileno')
                      ->where('id', $branchID)
                      ->get('branch')
                      ->row_array();
    
    if (empty($branch)) {
        return ['success' => false, 'message' => 'Branch not found'];
    }
    
    // Debug log
    error_log("Branch contact check - Mobile: " . ($branch['mobileno'] ?? 'empty') . 
              ", Email: " . ($branch['email'] ?? 'empty') . 
              ", Phone: " . ($branch['phone'] ?? 'empty'));
    
    // Determine best contact info
    $contact_info = '';
    
    // Try different fields in order of preference
    if (!empty($branch['mobileno']) && $branch['mobileno'] != '0') {
        $contact_info = $branch['mobileno'];
    } elseif (!empty($branch['phone'])) {
        $contact_info = $branch['phone'];
    } elseif (!empty($branch['telephone'])) {
        $contact_info = $branch['telephone'];
    } elseif (!empty($branch['contact_number'])) {
        $contact_info = $branch['contact_number'];
    } elseif (!empty($branch['office_phone'])) {
        $contact_info = $branch['office_phone'];
    } elseif (!empty($branch['email'])) {
        $contact_info = $branch['email'];
    } else {
        $contact_info = 'School Office';
    }
    
    // Prepare message
    $message = $template['template_body'];
    $message = str_replace('{guardian_name}', $admission['guardian_name'], $message);
    $message = str_replace('{student_name}', $admission['first_name'] . ' ' . $admission['last_name'], $message);
    $message = str_replace('{contact_info}', $contact_info, $message); // FIXED
    $message = str_replace('{school_name}', $branch['name'] ?? '', $message);
    $message = str_replace('{contact_number}', $contact_info, $message); // Add this too
    $message = str_replace('{school_phone}', $contact_info, $message); // And this
    
    // Check SMS credits
    $recipient_count = 0;
    if ($template['notify_student'] == 1 && !empty($admission['mobile_no'])) $recipient_count++;
    if ($template['notify_parent'] == 1 && !empty($admission['grd_mobile_no'])) $recipient_count++;
    
    if ($recipient_count == 0) {
        return ['success' => false, 'message' => 'No recipients configured'];
    }
    
    $required_credits = $this->calculate_admission_sms_cost($message, $recipient_count);
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branchID);
    
    if ($current_credits < $required_credits) {
        return [
            'success' => false,
            'message' => "Insufficient SMS credits. Needed: {$required_credits}, Available: {$current_credits}"
        ];
    }
    
    // Send SMS
    try {
        $this->load->library('bulksmsbd', ['branch_id' => $branchID], 'sms_lib');
        
        // Send to student if configured
        if ($template['notify_student'] == 1 && !empty($admission['mobile_no'])) {
            $student_message = str_replace('{name}', $admission['first_name'] . ' ' . $admission['last_name'], $message);
            $this->sms_lib->send($admission['mobile_no'], $student_message);
        }
        
        // Send to parent if configured
        if ($template['notify_parent'] == 1 && !empty($admission['grd_mobile_no'])) {
            $parent_message = str_replace('{name}', $admission['guardian_name'], $message);
            $this->sms_lib->send($admission['grd_mobile_no'], $parent_message);
        }
        
        return ['success' => true, 'message' => 'Notification sent'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'SMS sending failed: ' . $e->getMessage()];
    }
}

/**
 * Standardized response handler
 */
private function handle_response($success, $message, $data = [], $redirect = null)
{
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    
    if ($this->input->is_ajax_request()) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        if ($success) {
            set_alert('success', $message);
        } else {
            set_alert('error', $message);
        }
        
        if ($redirect) {
            redirect($redirect);
        } else {
            redirect(base_url('online_admission'));
        }
    }
}

public function approve($id)
{
    if (!get_permission('online_admission', 'is_add')) {
        access_denied();
    }

    // CHECK IF THIS IS POST-INTERVIEW APPROVAL
    $post_interview_data = $this->session->userdata('post_interview_approval');
    $is_post_interview = !empty($post_interview_data) && 
                        $post_interview_data['admission_id'] == $id &&
                        $post_interview_data['is_post_interview'] == true;
    
    // Clear the session flag after use
    if ($is_post_interview) {
        $this->session->unset_userdata('post_interview_approval');
        $interview_id = $post_interview_data['interview_id'];
        
        // Log this as post-interview approval
        error_log("=== POST-INTERVIEW APPROVAL ===");
    } else {
        error_log("=== DIRECT APPROVAL ===");
    }

    if ($is_post_interview) {
        error_log("Interview ID: " . $interview_id);
    }
    
    // ========== DEBUG: Log the start ==========
    error_log("=== APPROVE METHOD STARTED ===");
    
    // Start transaction
    $this->db->trans_begin();
    
    try {
        error_log("Transaction started");
        
        // Get admission with branch check
        $this->db->select('*');
        $this->db->where('id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $admission = $this->db->get('online_admission')->row_array();
        
        if (empty($admission)) {
            error_log("ADMISSION NOT FOUND");
            throw new Exception('Admission not found');
        }
        
        error_log("Admission found - Status: " . $admission['status'] . ", Branch: " . $admission['branch_id']);
        
        $branchID = $admission['branch_id'];
        
        // ========== STATUS VALIDATION ==========
        // Only allowed: status 1→2 or 5→2
        if (!in_array($admission['status'], [1, 5])) {
            error_log("INVALID STATUS: " . $admission['status']);
            throw new Exception('Cannot approve admission with status: ' . $admission['status']);
        }
        
        // Validate transition
        try {
            $this->validate_status_transition($admission['id'], $admission['status'], 2);
        } catch (Exception $e) {
            error_log("STATUS TRANSITION ERROR: " . $e->getMessage());
            throw $e;
        }

        // ========== CRITICAL VALIDATION FOR POST-INTERVIEW APPROVAL ==========
        if ($admission['status'] == 5) {
            // 1. Get the completed interview
            $interview = $this->db->where('admission_id', $admission['id'])
                                 ->where('status', 'completed')
                                 ->order_by('updated_at', 'DESC')
                                 ->limit(1)
                                 ->get('online_admission_interviews')
                                 ->row_array();
            
            if (empty($interview)) {
                throw new Exception('Cannot approve: No completed interview found for post-interview approval');
            }
            
            // 2. Validate interview outcome matches decision
            if ($interview['outcome'] != 'recommended') {
                $outcome_text = $interview['outcome'] ?: 'not set';
                throw new Exception("Interview outcome is '{$outcome_text}'. Only interviews with 'recommended' outcome can be approved.");
            }
            
            // 3. Log for audit trail
            error_log("POST-INTERVIEW APPROVAL: Admission ID {$admission['id']}, Interview ID {$interview['id']}, Outcome: {$interview['outcome']}");
            
            // 4. Update interview notes (optional, for audit trail)
            $interview_notes = ($interview['notes'] ?? '') . "\n\n[APPROVAL DECISION] " . date('d M Y H:i') . 
                             ": Admission approved based on interview recommendation.";
            
            $this->db->where('id', $interview['id']);
            $this->db->update('online_admission_interviews', [
                'notes' => $interview_notes,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // ========== GET BRANCH CONFIG ==========
        $getBranch = $this->db->where('id', $branchID)->get('branch')->row_array();
        if (empty($getBranch)) {
            error_log("BRANCH NOT FOUND: " . $branchID);
            throw new Exception('Branch configuration not found');
        }
        error_log("Branch config loaded");
        
        // ========== CHECK SMS CREDITS ==========
        $sms_check = $this->check_admission_sms_credits($branchID, $admission, []);
        error_log("SMS check: " . ($sms_check['success'] ? 'PASS' : 'FAIL'));

        // ========== FIX: Better error message for insufficient credits ==========
        if (!$sms_check['success']) {
            $error_message = "❌ Cannot Approve: " . $sms_check['message'];
            
            // Add purchase link information
            $error_message .= " Please purchase more SMS credits from the SMS Credit section.";
            
            // Log the error
            error_log("APPROVAL BLOCKED: " . $error_message);
            
            // Throw exception with clear message
            throw new Exception($error_message);
        }
        
        $required_credits = $sms_check['required_credits'];
        $template = $sms_check['template'] ?? null;
        
        // ========== RESERVE SMS CREDITS ==========
        $campaign_name = "Direct Approval - " . $admission['first_name'] . ' ' . $admission['last_name'];
        $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
            $branchID, 
            $required_credits, 
            $campaign_name, 
            null
        );
        
        if (!$reservation_id) {
            error_log("FAILED TO RESERVE SMS CREDITS");
            throw new Exception('Failed to reserve SMS credits');
        }
        error_log("SMS credits reserved: " . $reservation_id);
        
        // ========== PREPARE STUDENT DATA ==========
        $studentData = $this->prepare_student_data_from_admission($admission);
        error_log("Student data prepared, keys: " . implode(', ', array_keys($studentData)));
        
        // ========== CREATE STUDENT RECORD ==========
        error_log("Calling save_direct_approval...");
        $studentResult = $this->online_admission_model->save_direct_approval($studentData, $getBranch);

        if (empty($studentResult) || !is_array($studentResult)) {
            error_log("SAVE DIRECT APPROVAL FAILED - Returned: " . print_r($studentResult, true));
            throw new Exception('Failed to create student record. Model returned invalid data.');
        }

        if (empty($studentResult['student_id'])) {
            error_log("SAVE DIRECT APPROVAL FAILED - No student_id in result");
            throw new Exception('Failed to create student record. No student ID returned.');
        }

        $studentID = $studentResult['student_id'];
        error_log("Student created: " . $studentID);
        
        // ========== ENROLL STUDENT ==========
        $arrayEnroll = array(
            'student_id' => $studentID,
            'class_id' => $admission['class_id'],
            'section_id' => $admission['section_id'] ?? 0,
            'roll' => 0,
            'session_id' => get_session_id(),
            'branch_id' => $branchID,
        );
        
        if (!$this->db->insert('enroll', $arrayEnroll)) {
            error_log("ENROLL FAILED: " . $this->db->error()['message']);
            throw new Exception('Failed to enroll student');
        }
        error_log("Student enrolled");
        
        // ========== UPDATE ADMISSION STATUS ==========
        $this->db->where('id', $id);
        if (!$this->db->update('online_admission', ['status' => 2])) {
            error_log("UPDATE ADMISSION STATUS FAILED");
            throw new Exception('Failed to update admission status');
        }
        error_log("Admission status updated to 2");
        
        // ========== SEND SMS NOTIFICATION ==========
        $sms_result = $this->send_admission_notification_sms_inside_transaction(
            $studentID,
            $studentResult,
            $arrayEnroll,
            $admission,
            $studentData,
            $required_credits,
            $reservation_id,
            $template,
            $branchID
        );
        error_log("SMS sent: " . ($sms_result['sent'] ?? 0) . " messages");
        
        // ========== SEND EMAIL NOTIFICATION ==========
        $this->email_model->studentAdmission($studentResult);
        error_log("Email sent");
        
        // ========== MARK RESERVATION AS USED ==========
        // ========== FIX: MARK RESERVATION AS USED USING MESSAGE ID ==========
if ($sms_result['sent'] > 0) {
    // Calculate credits used
    $credits_used = ceil(($required_credits * $sms_result['sent']) / ($sms_check['recipient_count'] ?? 1));
    
    // ========== FIX: Get the message_id from the database ==========
        $this->db->select('message_id');
        $this->db->where('id', $reservation_id);
        $reservation = $this->db->get('sms_credit_reservations')->row_array();
        
        if (!empty($reservation) && !empty($reservation['message_id'])) {
            // Use message_id to mark as used
            $this->sendsmsmail_model->mark_reservation_used($reservation['message_id'], $credits_used);
            error_log("SMS credits used via message_id: " . $reservation['message_id'] . " - Credits: " . $credits_used);
        } else {
            // Fallback: update reservation directly
            $this->db->where('id', $reservation_id);
            $this->db->update('sms_credit_reservations', [
                'status' => 'used',
                'used_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            error_log("SMS credits updated directly for reservation: " . $reservation_id);
        }
    } else {
        // No SMS sent, return credits
        $this->sendsmsmail_model->return_reserved_credits($reservation_id);
        error_log("SMS credits returned: " . $reservation_id);
    }
        
        // ========== LOG COMMUNICATION ==========
     
        error_log("Communication logged");
        
       // ========== COMMIT TRANSACTION ==========
        $this->db->trans_commit();
        error_log("Transaction committed successfully");
        
        // STANDARDIZED RESPONSE: Use set_alert() + redirect
        $success_message = 'Admission approved successfully' . 
                        ($sms_result['sent'] > 0 ? ' and notification sent' : ' but SMS failed');

        set_alert('success', $success_message);

        // Redirect based on source
        if ($this->input->is_ajax_request()) {
            echo json_encode([
                'success' => true,
                'message' => $success_message, // ← USE THE MESSAGE DIRECTLY
                'redirect' => base_url('online_admission')
            ]);
        } else {
            redirect(base_url('online_admission'));
        }
        
        } catch (Exception $e) {
        // ========== ROLLBACK ON ERROR ==========
        $this->db->trans_rollback();
        
        if (isset($reservation_id)) {
            $this->sendsmsmail_model->return_reserved_credits($reservation_id);
        }
        
        // ========== FIX: Better error handling ==========
        $error_msg = $e->getMessage();
        
        // Log the full error
        // log_message('error', "Approval failed: " . $error_msg);
        
        if ($this->input->is_ajax_request()) {
            echo json_encode([
                'success' => false,
                'message' => $error_msg
            ]);
        } else {
            set_alert('error', $error_msg);
            redirect(base_url('online_admission'));
        }
    }
}


/**
 * Test endpoint for direct approval debugging
 */
public function test_approve($id)
{
    error_log("=== TEST APPROVE ===");
    
    // Return simple success to test AJAX
    echo json_encode([
        'success' => true,
        'message' => 'Test endpoint works! ID: ' . $id
    ]);
}

/**
 * Prepare student data from admission record - FIXED VERSION
 */
private function prepare_student_data_from_admission($admission)
{
    // Format previous school details for student table
    $previous_details = '';
    if (!empty($admission['previous_school_details'])) {
        // If it's already JSON, use it; otherwise, create JSON
        if (json_decode($admission['previous_school_details']) !== null) {
            $previous_details = $admission['previous_school_details'];
        } else {
            $previous_details = json_encode([
                'school_name' => $admission['previous_school_details'],
                'qualification' => '',
                'remarks' => ''
            ]);
        }
    } else {
        $previous_details = json_encode([
            'school_name' => '',
            'qualification' => '',
            'remarks' => ''
        ]);
    }
    
    return [
        'first_name' => $admission['first_name'] ?? '',
        'last_name' => $admission['last_name'] ?? '',
        'gender' => $admission['gender'] ?? '',
        'birthday' => $admission['birthday'] ?? '',
        'religion' => $admission['religion'] ?? '',
        'caste' => $admission['caste'] ?? '',
        'blood_group' => $admission['blood_group'] ?? '',
        'mobile_no' => $admission['mobile_no'] ?? '',
        'mother_tongue' => $admission['mother_tongue'] ?? '',
        'present_address' => $admission['present_address'] ?? '',
        'permanent_address' => $admission['permanent_address'] ?? '',
        'city' => $admission['city'] ?? '',
        'state' => $admission['state'] ?? '',
        'student_photo' => $admission['student_photo'] ?? 'defualt.png',
        'email' => $admission['email'] ?? '',
        
        // Guardian information
        'guardian_name' => $admission['guardian_name'] ?? '',
        'guardian_relation' => $admission['guardian_relation'] ?? '',
        'father_name' => $admission['father_name'] ?? '',
        'mother_name' => $admission['mother_name'] ?? '',
        'grd_occupation' => $admission['grd_occupation'] ?? '',
        'grd_income' => $admission['grd_income'] ?? '',
        'grd_education' => $admission['grd_education'] ?? '',
        'grd_email' => $admission['grd_email'] ?? '',
        'grd_mobile_no' => $admission['grd_mobile_no'] ?? '',
        'grd_address' => $admission['grd_address'] ?? '',
        'grd_city' => $admission['grd_city'] ?? '',
        'grd_state' => $admission['grd_state'] ?? '',
        'grd_photo' => $admission['grd_photo'] ?? 'defualt.png',
        
        // System fields
        'branch_id' => $admission['branch_id'],
        'class_id' => $admission['class_id'],
        'section_id' => $admission['section_id'] ?? null,
        'register_no' => $this->online_admission_model->regSerNumber($admission['branch_id']),
        'admission_date' => date('Y-m-d'),
        'category_id' => $admission['category_id'] ?? 0,
        'previous_school_details' => $previous_details  // This will be saved as previous_details in student table
    ];
}


   public function approved($student_id = '')
{
    error_log("=== APPROVED METHOD CALLED ===");
    error_log("Student ID: " . $student_id);
    error_log("POST data: " . print_r($_POST, true));
    
    // Check execution time
    set_time_limit(60); // 60 seconds max
    ini_set('max_execution_time', 60);

    // Check access permission
    if (!get_permission('online_admission', 'is_add')) {
        access_denied();
    }

    // Check saas student add limit
    if($this->app_lib->isExistingAddon('saas')) {
        if (!checkSaasLimit('student')) {
            access_denied();
        }
    }

   // ========== GET ADMISSION WITH BRANCH CHECK ==========
    $stuDetails = $this->online_admission_model->get_admission($student_id, 2, true);
    $branchID = $stuDetails['branch_id'];
    // log_message('debug', 'Branch ID from admission: ' . $branchID);

    // For superadmin, ensure we have a valid branch ID
    if (is_superadmin_loggedin() && empty($branchID)) {
        // Try to get branch from session or use default
        $branchID = $this->session->userdata('loggedin_branch') ?: 1;
        // log_message('debug', 'Superadmin branch fallback: ' . $branchID);
    }

    // Ensure branch ID is set
    if (empty($branchID)) {
        // log_message('error', 'No branch ID found for admission: ' . $student_id);
        access_denied();
    }
    // log_message('debug', 'Admission details: ' . print_r($stuDetails, true));

    if (empty($stuDetails['id'])) {
        // log_message('error', 'Admission not found - ID: ' . $student_id . ', User Branch: ' . get_loggedin_branch_id());
        access_denied();
    }

    $branchID = $stuDetails['branch_id'];
    // log_message('debug', 'Branch ID: ' . $branchID);
    $getBranch = $this->db->where('id', $branchID)->get('branch')->row_array();
    $guardian = false;

    if ($_POST) {
        // ========== STEP 1: VALIDATE STATUS TRANSITION ==========
        // Only allow approval from status 1 (direct) or 5 (post-interview)
        if (!in_array($stuDetails['status'], [1, 5])) {
            $response = [
                'status' => 'fail',
                'message' => 'Cannot approve admission with status: ' . $stuDetails['status']
            ];
            echo json_encode($response);
            exit();
        }
        
        // Validate transition
        try {
            $this->validate_status_transition($stuDetails['id'], $stuDetails['status'], 2);
        } catch (Exception $e) {
            $response = ['status' => 'fail', 'message' => $e->getMessage()];
            echo json_encode($response);
            exit();
        }
        
        // ========== STEP 2: PREPARE FORM VALIDATION ==========
        $newStudent_photo = 0;
        $newGuardian_photo = 0;
        $existStudent_photo = $this->input->post('exist_student_photo');
        $existGuardian_photo = $this->input->post('exist_guardian_photo');
        
        if (isset($_FILES["student_photo"]) && empty($_FILES["student_photo"]['name'])) {
            $newStudent_photo = 1;
        }
        if (isset($_FILES["guardian_photo"]) && empty($_FILES["guardian_photo"]['name'])) {
            $newGuardian_photo = 1;
        }

        // ========== STEP 3: FORM VALIDATION RULES ==========
        $this->form_validation->set_rules('first_name', translate('first_name'), 'trim|required');
        $this->form_validation->set_rules('year_id', translate('academic_year'), 'trim|required');
        $this->form_validation->set_rules('register_no', translate('register_no'), 'trim|required');
        $this->form_validation->set_rules('class_id', translate('class'), 'trim|required');
        $this->form_validation->set_rules('section_id', translate('section'), 'trim|required');
        $this->form_validation->set_rules('student_photo', translate('profile_picture'), 'callback_photoHandleUpload[student_photo]');
        $this->form_validation->set_rules('guardian_photo', translate('profile_picture'), 'callback_photoHandleUpload[guardian_photo]');

        // ========== STEP 4: SYSTEM FIELDS VALIDATION ==========
        $validArr = array();
        $validationArr = $this->student_fields_model->getStatusArr($branchID);
        foreach ($validationArr as $key => $value) {
            if ($value->status && $value->required) {
                $validArr[$value->prefix] = 1;
            }
        }
        
        if (isset($validArr['roll'])) {
            $this->form_validation->set_rules('roll', translate('roll'), 'trim|numeric|required|callback_unique_roll');
        } else {
            $this->form_validation->set_rules('roll', translate('roll'), 'trim|numeric|callback_unique_roll');
        }
        
        if (isset($validArr['last_name'])) {
            $this->form_validation->set_rules('last_name', translate('last_name'), 'trim|required');
        }
        
        if (isset($validArr['gender'])) {
            $this->form_validation->set_rules('gender', translate('gender'), 'trim|required');
        }
        
        if (isset($validArr['birthday'])) {
            $this->form_validation->set_rules('birthday', translate('birthday'), 'trim|required');
        }
        
        if (isset($validArr['category'])) {
            $this->form_validation->set_rules('category_id', translate('category'), 'trim|required');
        }

        // ========== STEP 5: GUARDIAN VALIDATION ==========
        if ($this->input->post('grd_name') || $this->input->post('father_name')) {
            $guardian = true;
            
            if (isset($validArr['guardian_name'])) {
                $this->form_validation->set_rules('grd_name', translate('name'), 'trim|required');
            }
            
            if (isset($validArr['guardian_relation'])) {
                $this->form_validation->set_rules('grd_relation', translate('relation'), 'trim|required');
            }
            
            if (isset($validArr['father_name'])) {
                $this->form_validation->set_rules('father_name', translate('father_name'), 'trim|required');
            }
            
            if (isset($validArr['mother_name'])) {
                $this->form_validation->set_rules('mother_name', translate('mother_name'), 'trim|required');
            }
            
            if ($getBranch['grd_generate'] == 0 && $guardian == true) {
                $this->form_validation->set_rules('grd_username', translate('username'), 'trim|required|callback_get_valid_guardian_username');
                $this->form_validation->set_rules('grd_password', translate('password'), 'trim|required');
                $this->form_validation->set_rules('grd_retype_password', translate('retype_password'), 'trim|required|matches[grd_password]');
            }
        }

        // ========== STEP 6: STUDENT LOGIN VALIDATION ==========
        if ($getBranch['stu_generate'] == 0) {
            $this->form_validation->set_rules('username', translate('username'), 'trim|required|callback_unique_username');
            $this->form_validation->set_rules('password', translate('password'), 'trim|required|min_length[4]');
            $this->form_validation->set_rules('retype_password', translate('retype_password'), 'trim|required|matches[password]');
        }

        // ========== STEP 7: RUN FORM VALIDATION ==========
        if ($this->form_validation->run() == true) {
            $post = $this->input->post();
            
            // ========== START DB TRANSACTION ==========
            $this->db->trans_start();
            
            try {
                // ========== STEP 8: CHECK SMS CREDITS ==========
                $sms_check = $this->check_admission_sms_credits($branchID, $stuDetails, $post);
                
                if (!$sms_check['success']) {
                    throw new Exception($sms_check['message']);
                }
                
                $required_credits = $sms_check['required_credits'];
                $template = $sms_check['template'];
                
                // ========== STEP 9: RESERVE SMS CREDITS ==========
                $campaign_name = "Admission Approved - " . $stuDetails['first_name'] . ' ' . $stuDetails['last_name'];
                $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
                    $branchID, 
                    $required_credits, 
                    $campaign_name, 
                    null
                );
                
                if (!$reservation_id) {
                    throw new Exception('Failed to reserve SMS credits');
                }
                
                // ========== STEP 10: CREATE STUDENT RECORD ==========
                $studentData = $this->online_admission_model->save($post, $getBranch);
                
                if (empty($studentData['student_id'])) {
                    throw new Exception('Failed to create student record');
                }
                
                $studentID = $studentData['student_id'];
                
                // ========== STEP 11: ENROLL STUDENT ==========
                $arrayEnroll = array(
                    'student_id' => $studentID,
                    'class_id' => $post['class_id'],
                    'section_id' => (isset($post['section_id']) ? $post['section_id'] : 0),
                    'roll' => (isset($post['roll']) ? $post['roll'] : 0),
                    'session_id' => $post['year_id'],
                    'branch_id' => $branchID,
                );
                
                if (!$this->db->insert('enroll', $arrayEnroll)) {
                    throw new Exception('Failed to enroll student');
                }
                
                // ========== STEP 12: UPDATE ADMISSION STATUS ==========
                $this->db->where('id', $stuDetails['id']);
                if (!$this->db->update('online_admission', array('status' => 2))) {
                    throw new Exception('Failed to update admission status');
                }
                
                // ========== STEP 13: SAVE CUSTOM FIELDS ==========
                $class_slug = "student";
                $customField = $this->input->post("custom_fields[$class_slug]");
                if (!empty($customField)) {
                    saveCustomFields($customField, $studentID);
                }
                
                // ========== STEP 14: SEND EMAIL NOTIFICATION ==========
                $email_sent = $this->email_model->studentAdmission($studentData);
                if (!$email_sent) {
                    // log_message('warning', 'Email notification failed for student ID: ' . $studentID);
                }
                
                // ========== STEP 15: SEND SMS NOTIFICATION ==========
                $sms_result = $this->send_admission_notification_sms_inside_transaction(
                    $studentID,
                    $studentData,
                    $arrayEnroll,
                    $stuDetails,
                    $post,
                    $required_credits,
                    $reservation_id,
                    $template,
                    $branchID
                );
                
                // ========== STEP 16: MARK RESERVATION AS USED ==========
                if ($sms_result['sent'] > 0) {
                    $credits_used = ceil(($required_credits * $sms_result['sent']) / $sms_check['recipient_count']);
                    $this->sendsmsmail_model->mark_reservation_used($reservation_id, $credits_used);
                } else {
                    $this->sendsmsmail_model->return_reserved_credits($reservation_id);
                }
                
                // ========== STEP 17: LOG COMMUNICATION ==========
                $this->log_admission_communication(
                    $studentID,
                    $stuDetails['id'],
                    'approval',
                    $sms_result['sent'] > 0 ? 'sms_sent' : 'sms_failed',
                    'Form-based approval',
                    $branchID
                );
                
                // ========== STEP 18: COMMIT TRANSACTION ==========
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    // Get database error
                    $error = $this->db->error();
                    throw new Exception('Transaction failed: ' . $error['message']);
                }
                
                // ========== STEP 19: RETURN SUCCESS RESPONSE ==========
                $response = [
                    'status' => 'success',
                    'message' => translate('information_has_been_saved_successfully')
                ];
                
                if ($sms_result['sent'] < $sms_check['recipient_count']) {
                    $response['message'] .= ' ' . translate('partial_sms_notification_failed');
                    $response['sms_warning'] = true;
                    $response['sent_count'] = $sms_result['sent'];
                    $response['total_count'] = $sms_check['recipient_count'];
                }
                
                echo json_encode($response);
                exit();
                
            } catch (Exception $e) {
                // ========== ROLLBACK ON ERROR ==========
                $this->db->trans_rollback();
                
                // Return reserved credits on error
                if (isset($reservation_id)) {
                    $this->sendsmsmail_model->return_reserved_credits($reservation_id);
                }
                
                // log_message('error', 'Admission approval failed: ' . $e->getMessage());
                
                $response = [
                    'status' => 'fail',
                    'message' => $e->getMessage()
                ];
                echo json_encode($response);
                exit();
            }              
        } else {
            // ========== VALIDATION FAILED ==========
            $error = $this->form_validation->error_array();
            $response = [
                'status' => 'fail',
                'message' => implode('<br>', $error)
            ];
            echo json_encode($response);
            exit();
        }
        error_log("=== APPROVAL PROCESSING COMPLETED ===");
    }
    
    // ========== LOAD VIEW DATA ==========
    $this->data['interviewers'] = $this->get_interviewers($branchID);
    $this->data['stuDetails'] = $stuDetails;
    $this->data['getBranch'] = $getBranch;
    $this->data['sub_page'] = 'online_admission/approved';
    $this->data['main_menu'] = 'admission';
    $this->data['register_id'] = $this->online_admission_model->regSerNumber($branchID);
    $this->data['title'] = translate('online_admission');
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/dropify/css/dropify.min.css',
            'vendor/sweetalert/sweetalert.css',
            'vendor/bootstrap-datepicker/css/bootstrap-datepicker.css',
            'vendor/bootstrap-timepicker/css/bootstrap-timepicker.css',
        ),
        'js' => array(
            'vendor/sweetalert/sweetalert.min.js',
            'js/student.js',
            'vendor/dropify/js/dropify.min.js',
            'vendor/bootstrap-datepicker/js/bootstrap-datepicker.js',
            'vendor/bootstrap-timepicker/bootstrap-timepicker.js',
        ),
    );
    $this->load->view('layout/index', $this->data);
}

/**
 * Log admission communication
 */
private function log_admission_communication($student_id, $admission_id, $type, $status, $message, $branch_id)
{
    $log_data = [
        'student_id' => $student_id,
        'admission_id' => $admission_id,
        'type' => $type, // 'approval', 'decline', 'interview_invitation', etc.
        'status' => $status, // 'sms_sent', 'sms_failed', 'email_sent', 'email_failed'
        'message' => $message,
        'branch_id' => $branch_id,
        'created_by' => $this->session->userdata('loggedin_userid'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Check if table exists, if not create it
    $this->db->insert('interview_communications', $log_data);
    
    // Also log in the interview_communications table for consistency
    if ($this->db->table_exists('interview_communications')) {
        $interview_log = [
            'type' => 'admission_approval',
            'direction' => 'outgoing',
            'message' => $message,
            'status' => $status,
            'sent_by' => $this->session->userdata('loggedin_userid'),
            'created_at' => date('Y-m-d H:i:s'),
            'branch_id' => $branch_id
        ];
        $this->db->insert('interview_communications', $interview_log);
    }
}
/**
 * Check if interview exists for admission
 */
public function check_existing_interview($admission_id)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->where('admission_id', $admission_id);
    $this->db->where('branch_id', $branchID);
    $this->db->where_in('status', ['scheduled', 'rescheduled']);
    $exists = $this->db->get('online_admission_interviews')->num_rows() > 0;
    
    echo json_encode(['exists' => $exists]);
}

 public function schedule_interview($admission_id = '')
{
    // Check permission
    if (!get_permission('online_admission', 'is_add')) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    // Get admission with branch check
    $this->db->where('id', $admission_id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', get_loggedin_branch_id());
    }
    $admission = $this->db->get('online_admission')->row_array();
    
    if (empty($admission)) {
        echo json_encode(['status' => 'error', 'message' => 'Admission not found']);
        exit();
    }
    
    // ========== STATUS VALIDATION ==========
    // Only allow scheduling from status 1 (Pending)
    if ($admission['status'] != 1) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Interview can only be scheduled for pending admissions'
        ]);
        exit();
    }
    
    if ($this->has_active_interview($admission_id)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Active interview already exists for this admission'
    ]);
    exit();
}
    
    // Validate required fields
    $required = ['interview_date', 'interview_time', 'interview_type', 'interviewer_id', 'location'];
    foreach ($required as $field) {
        if (empty($this->input->post($field))) {
            echo json_encode([
                'status' => 'error',
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
            ]);
            exit();
        }
    }
    
    // Start transaction
    $this->db->trans_start();
    
    try {
        // Create interview record
        $interview_data = [
            'admission_id' => $admission_id,
            'interview_date' => $this->input->post('interview_date'),
            'interview_time' => $this->input->post('interview_time'),
            'interview_type' => $this->input->post('interview_type'),
            'interviewer_id' => $this->input->post('interviewer_id'),
            'location' => $this->input->post('location'),
            'notes' => $this->input->post('interview_notes'),
            'status' => 'scheduled',
            'outcome' => 'pending',
            'created_by' => $this->session->userdata('loggedin_userid') ?: 1,
            'branch_id' => $admission['branch_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('online_admission_interviews', $interview_data);
        $interview_id = $this->db->insert_id();
        
        // ========== UPDATE ADMISSION STATUS (1→4) ==========
        $this->db->where('id', $admission_id);
        $this->db->update('online_admission', ['status' => 4]);
        
        // ========== SMS CREDIT CHECK & SEND ==========
        $sms_result = ['success' => false, 'message' => 'SMS not configured'];
        
        // Check if template exists and send SMS
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 11, // Interview invitation template
            'branch_id' => $admission['branch_id']
        ])->row_array();
        
        if (!empty($template)) {
            // Use the model method (which does credit checking)
            $this->load->model('sms_model');
            $sms_result = $this->sms_model->sendInterviewInvitation($interview_id, $admission, $admission['branch_id']);
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        $response = [
            'status' => 'success',
            'message' => 'Interview scheduled successfully',
            'interview_id' => $interview_id,
            'sms_sent' => $sms_result['success'] ?? false
        ];
        
        if (!$sms_result['success']) {
            $response['message'] .= ' (SMS notification failed: ' . ($sms_result['message'] ?? 'no template') . ')';
        }
        
        echo json_encode($response);
        exit();
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        exit();
    }
}
/**
 * Send admission notification SMS INSIDE TRANSACTION
 */
private function send_admission_notification_sms_inside_transaction($studentID, $studentData, $enrollData, $stuDetails, $postData, $required_credits, $reservation_id, $template, $branchID)
{
    // Get branch details for school_name
    $branch = $this->db->select('name')->where('id', $branchID)->get('branch')->row_array();
    $school_name = $branch['name'] ?? 'School';
    
    // Get student details with parent
    $student = $this->db->select('s.first_name, s.last_name, s.mobileno, s.email, s.register_no, 
                                 p.id as parent_id, p.mobileno as parent_mobile, p.name as parent_name')
                       ->from('student s')
                       ->join('parent p', 'p.id = s.parent_id', 'left')
                       ->where('s.id', $studentID)
                       ->get()
                       ->row_array();
    
    if (empty($student)) {
        return ['success' => false, 'message' => 'Student not found', 'sent' => 0];
    }
    
    if (empty($template) || empty($template['template_body'])) {
        return ['success' => false, 'message' => 'SMS template not configured', 'sent' => 0];
    }
    
    // Prepare message with ALL template variables
    $student_message = $template['template_body'];
    $student_message = str_replace('{name}', $student['first_name'] . ' ' . $student['last_name'], $student_message);
    $student_message = str_replace('{guardian_name}', $student['parent_name'] ?? '', $student_message);
    $student_message = str_replace('{student_name}', $student['first_name'] . ' ' . $student['last_name'], $student_message);
    $student_message = str_replace('{register_no}', $student['register_no'], $student_message);
    $student_message = str_replace('{username}', $studentData['username'], $student_message);
    $student_message = str_replace('{password}', $studentData['password'], $student_message);
    $student_message = str_replace('{class}', get_type_name_by_id('class', $enrollData['class_id']), $student_message);
    $student_message = str_replace('{section}', get_type_name_by_id('section', $enrollData['section_id']), $student_message);
    $student_message = str_replace('{roll}', $enrollData['roll'], $student_message);
    $student_message = str_replace('{school_name}', $school_name, $student_message); // <-- THIS IS CRITICAL
    $student_message = str_replace('{admission_date}', date('d M Y'), $student_message);
    

    
    if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
        $recipients[] = [
            'mobile' => $student['mobileno'],
            'message' => $student_message,
            'type' => 'student'
        ];
    }
    
    // Parent message
    if ($template['notify_parent'] == 1 && !empty($student['parent_mobile'])) {
        $parent_message = $student_message;
        $parent_message = str_replace('{name}', $student['parent_name'], $parent_message);
        $recipients[] = [
            'mobile' => $student['parent_mobile'],
            'message' => $parent_message,
            'type' => 'parent'
        ];
    }
    
    if (empty($recipients)) {
        return ['success' => false, 'message' => 'No valid recipients', 'sent' => 0];
    }
    
    // Send SMS to each recipient
    $sent_count = 0;
    $failed_recipients = [];
    
    $this->load->library('bulksmsbd', ['branch_id' => $branchID], 'sms_lib');
    
    foreach ($recipients as $recipient) {
    $mobile = preg_replace('/[^0-9]/', '', $recipient['mobile']);
    
    if (!empty($mobile)) {
        try {
            // ========== FIX: Set a shorter timeout ==========
            // You need to modify the library or add timeout parameter
            $response = $this->sms_lib->send($mobile, $recipient['message']);
            
            if ($this->is_successful_sms_response($response)) {
                $sent_count++;
                
                // Log successful delivery
                $this->db->insert('sms_email_delivery_logs', [
                    'recipient_contact' => $mobile,
                    'status' => 'sent',
                    'gateway_response' => substr($response, 0, 500),
                    'sent_at' => date('Y-m-d H:i:s'),
                    'branch_id' => $branchID
                ]);
                
            } else {
                $failed_recipients[] = $mobile;
                
                // Log failed delivery
                $this->db->insert('sms_email_delivery_logs', [
                    'recipient_contact' => $mobile,
                    'status' => 'failed',
                    'gateway_response' => substr($response, 0, 500),
                    'sent_at' => date('Y-m-d H:i:s'),
                    'branch_id' => $branchID
                ]);
            }
            
        } catch (Exception $e) {
            $failed_recipients[] = $mobile;
            
            // ========== FIX: Log timeout specifically ==========
            $error_message = $e->getMessage();
            if (strpos($error_message, 'timed out') !== false) {
                // log_message('error', "SMS Gateway timeout for {$mobile}. The gateway is slow but the approval succeeded.");
            } else {
                // log_message('error', "Exception sending SMS to {$mobile}: " . $error_message);
            }
            
            // Log the timeout in delivery logs
            $this->db->insert('sms_email_delivery_logs', [
                'recipient_contact' => $mobile,
                'status' => 'timeout',
                'gateway_response' => 'Gateway timeout - SMS may still be delivered',
                'sent_at' => date('Y-m-d H:i:s'),
                'branch_id' => $branchID
            ]);
        }
        
        usleep(50000);
    }
}
    
    // Create bulk SMS record INSIDE TRANSACTION
    $duplicate_hash = md5('admission_' . $studentID . '_' . date('Y-m-d'));
    
    $message_data = [
        'campaign_name' => "Admission Approved - " . $student['first_name'] . ' ' . $student['last_name'],
        'message' => $student_message,
        'message_type' => 1,
        'recipient_type' => 8, // Online admission
        'recipients_details' => json_encode([
            'student_id' => $studentID,
            'template_id' => 1,
            'notify_student' => $template['notify_student'],
            'notify_parent' => $template['notify_parent'],
            'reservation_id' => $reservation_id
        ]),
        'additional' => json_encode($recipients),
        'schedule_time' => date('Y-m-d H:i:s'),
        'posting_status' => ($sent_count == count($recipients) ? 2 : ($sent_count > 0 ? 4 : 3)),
        'total_thread' => count($recipients),
        'successfully_sent' => $sent_count,
        'sms_gateway' => 'bulksmsbd',
        'credits_used' => ($sent_count > 0 ? $required_credits : 0),
        'branch_id' => $branchID,
        'duplicate_hash' => $duplicate_hash,
        'send_type' => 'immediate',
        'transaction_id' => 'ADM_' . $studentID . '_' . time(),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $this->db->insert('bulk_sms_email', $message_data);
    $message_id = $this->db->insert_id();
    
    // Update reservation with message ID INSIDE TRANSACTION
    $this->db->where('id', $reservation_id);
    $this->db->update('sms_credit_reservations', [
        'message_id' => $message_id,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    // log_message('info', "Admission SMS completed: {$sent_count}/" . count($recipients) . " sent");
    
    return [
        'success' => ($sent_count > 0),
        'sent' => $sent_count,
        'total' => count($recipients),
        'failed' => $failed_recipients,
        'message' => ($sent_count > 0 ? 'SMS sent successfully' : 'SMS sending failed')
    ];
}

    /**
     * Send interview invitation SMS
     */
private function send_interview_invitation($interview_id, $admission, $branch)
{
    // Just call the model method
    return $this->sms_model->sendInterviewInvitation($interview_id, $admission, $branch['id']);
}

    /**
     * Default interview invitation SMS (if template not configured)
     */
    private function send_interview_invitation_default($interview_id, $admission, $branch)
    {
        $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
        $interviewer = $this->db->select('name')->where('id', $interview['interviewer_id'])->get('staff')->row();
        
        $message = "Dear " . $admission['guardian_name'] . ",\n";
        $message .= "Interview scheduled for " . $admission['first_name'] . " " . $admission['last_name'] . " admission.\n";
        $message .= "Date: " . date('d M Y', strtotime($interview['interview_date'])) . "\n";
        $message .= "Time: " . date('h:i A', strtotime($interview['interview_time'])) . "\n";
        $message .= "Location: " . $interview['location'] . "\n";
        $message .= "Interviewer: " . ($interviewer ? $interviewer->name : 'Admissions Office') . "\n";
        $message .= "Please confirm attendance by replying YES.";
        
        return ['success' => true, 'message' => 'Default SMS sent'];
    }
    
public function cancel_interview()
{
    if (!$this->input->is_ajax_request()) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        return;
    }

    // ========== CRITICAL FIX: GET INTERVIEW_ID FROM POST ==========
    $interview_id = $this->input->post('interview_id'); // <-- ADD THIS LINE (Fixes line 1460 error)
    $reason = $this->input->post('reason');
    
    if (empty($interview_id)) {
        echo json_encode(['success' => false, 'message' => 'Interview ID required']);
        return;
    }
    
    $this->db->trans_start();
    
    try {
        // Get interview WITH BRANCH FILTER
        $this->apply_branch_filter('online_admission_interviews', '');
        $this->db->where('id', $interview_id); // <-- NOW $interview_id IS DEFINED (Fixes line 1461,1463)
        $interview = $this->db->get('online_admission_interviews')->row_array();
        
        if (empty($interview)) {
            throw new Exception('Interview not found');
        }

        // Check if admission can be modified
        $modify_check = $this->can_modify_admission($interview['admission_id']);
        if (!$modify_check['can_modify']) {
            throw new Exception($modify_check['message']);
        }
        
        // Update interview status
        $update_data = [
            'status' => 'cancelled',
            'notes' => ($interview['notes'] ?? '') . "\n\n[CANCELLED] " . date('d M Y H:i') . ": " . $reason,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->where('id', $interview_id);
        $this->db->where('branch_id', $interview['branch_id']); // Extra safety
        $this->db->update('online_admission_interviews', $update_data);
        
        // Update admission status back to pending
        $this->db->where('id', $interview['admission_id']);
        $this->db->where('branch_id', $interview['branch_id']);
        $this->db->update('online_admission', ['status' => 1]); // Back to pending
        
        // ========== SEND CANCELLATION SMS ==========
        $admission = $this->db->where('id', $interview['admission_id'])->get('online_admission')->row_array();
        $branch_id = $interview['branch_id'];

        // Load SMS model
        $this->load->model('sms_model');

        // Check if template exists for cancellation (template 19)
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 19,
            'branch_id' => $branch_id
        ])->row_array();

        if (!empty($template)) {
            // ========== FIX: Prepare data in the format sendAdmissionOutcome expects ==========
            $student_data = [
                'student_name' => $admission['first_name'] . ' ' . $admission['last_name'],
                'interview_date' => date('d M Y', strtotime($interview['interview_date'])),
                'interview_time' => date('h:i A', strtotime($interview['interview_time'])),
                'location' => $interview['location'],
                'reason' => $reason
            ];
            
            $parent_data = [
                'guardian_name' => $admission['guardian_name'],
                'contact_info' => 'Please contact admissions office: ' . ($this->db->select('mobileno')->where('id', $branch_id)->get('branch')->row()->mobileno ?? 'School Office')
            ];
            
            // Add branch details
            $branch = $this->db->select('name, mobileno, email')->where('id', $branch_id)->get('branch')->row_array();
            if (!empty($branch)) {
                $student_data['school_name'] = $branch['name'];
                $student_data['school_phone'] = $branch['mobileno'] ?? '';
                $parent_data['school_name'] = $branch['name'];
                $parent_data['school_phone'] = $branch['mobileno'] ?? '';
            }
            
            $sms_result = $this->sms_model->sendAdmissionOutcome(
                $admission['id'],
                19,
                $branch_id,
                $student_data,  // Now properly structured
                $parent_data     // Now properly structured
            );
            
            // Log the result
            // log_message('debug', 'Cancellation SMS result: ' . print_r($sms_result, true));
            
            if ($sms_result['success']) {
                // Log communication
                $this->log_interview_communication(
                    $interview_id, 
                    'sms', 
                    'to_parent', 
                    'sent',
                    "Interview cancelled: " . $reason, 
                    $admission['grd_mobile_no'] ?? $admission['mobile_no']
                );
            } else {
                // log_message('error', 'Failed to send cancellation SMS: ' . ($sms_result['message'] ?? 'Unknown error'));
            }
        } else {
            // log_message('warning', 'Cancellation template (ID 19) not configured for branch ' . $branch_id);
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Interview cancelled successfully' . 
                       (isset($sms_result['success']) && $sms_result['success'] ? ' and notification sent' : '')
        ]);
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Resend invitation (AJAX)
 */
public function resend_invitation()
{
    if (!get_permission('online_admission', 'is_edit')) {
        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        access_denied();
    }
    
    if (!$this->input->is_ajax_request()) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        return;
    }
    
    $interview_id = $this->input->post('interview_id');
    
    if (empty($interview_id)) {
        echo json_encode(['success' => false, 'message' => 'Interview ID required']);
        return;
    }
    
    // Get interview details
    $this->db->select('i.*, oa.*');
    $this->db->from('online_admission_interviews i');
    $this->db->join('online_admission oa', 'oa.id = i.admission_id', 'left');
    $this->db->where('i.id', $interview_id);
    $interview = $this->db->get()->row_array();
    
    if (empty($interview)) {
        echo json_encode(['success' => false, 'message' => 'Interview not found']);
        return;
    }
    
    // Load SMS model and send invitation
    $this->load->model('sms_model');
    $result = $this->sms_model->sendInterviewInvitation($interview_id, $interview, $interview['branch_id']);
    
    if ($result['success']) {
        // Update interview record
        $this->db->where('id', $interview_id);
        $this->db->update('online_admission_interviews', [
            'sms_invitation_sent' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    echo json_encode($result);
}

private function get_interviewers($branchID)
{
    $this->db->select('staff.id, staff.name, staff.designation');
    $this->db->from('staff');
    $this->db->join('login_credential lc', 'lc.user_id = staff.id', 'left');
    $this->db->where('staff.branch_id', (int) $branchID);
    $this->db->where_in('lc.role', [1, 2, 3]); // Admin, Teacher, Staff
    $this->db->order_by('staff.name', 'ASC');
    
    $query = $this->db->get();
    return $query->result_array();
}

    
 /**
 * Interviews management page - FINAL FIXED VERSION
 */
public function interviews()
{    
    // IMPORTANT: First ensure user is logged in
    if (!is_loggedin()) {
        redirect(base_url('authentication'));
    }
    

    // FIX 1: If role is empty, get it from database
    if (empty($user_role) && !empty($user_id)) {
        $login = $this->db->select('role')->where('id', $user_id)->get('login_credential')->row();
        if ($login) {
            $user_role = $login->role;
            $this->session->set_userdata('loggedin_role', $user_role);
            // log_message('debug', "Fixed: Set loggedin_role to $user_role from database");
        }
    }
    
    // FIX 2: Permission check with superadmin bypass
    $has_permission = false;
    
    // Superadmin has all permissions
    if (is_superadmin_loggedin()) {
        $has_permission = true;
        // log_message('debug', 'User is superadmin - automatic permission');
    } 
    // Regular user - check permission
    elseif (!empty($user_role)) {
        $permission = $this->db->where('permission', 'online_admission')
                              ->where('role_id', $user_role)
                              ->get('permission')
                              ->row_array();
        
        if ($permission && isset($permission['is_view']) && $permission['is_view'] == 1) {
            $has_permission = true;
            // log_message('debug', 'User has online_admission/is_view permission');
        } else {
            // log_message('debug', 'User does NOT have online_admission/is_view permission');
        }
    }
    
    // If no permission, redirect
    if (!$has_permission) {
        // log_message('debug', 'ACCESS DENIED - Redirecting to dashboard');
        access_denied();
        return; // Important: Stop execution
    }
    
    // log_message('debug', 'Permission granted, loading interviews...');
    
    // FIX 3: Get branch ID reliably
    $branchID = $this->application_model->get_branch_id();
    
    // If branchID is NULL, use fallback methods
    if ($branchID === NULL || $branchID === '') {
        // Try session
        $branchID = $this->session->userdata('loggedin_branch');
        
        // Try user's default branch
        if (empty($branchID) && !empty($user_id)) {
            $login = $this->db->select('branch_id')->where('id', $user_id)->get('login_credential')->row();
            $branchID = $login ? $login->branch_id : null;
        }
        
        // Superadmin - use first branch
        if (empty($branchID) && is_superadmin_loggedin()) {
            $first_branch = $this->db->select('id')->order_by('id', 'ASC')->get('branch')->row();
            $branchID = $first_branch ? $first_branch->id : null;
        }
        
        // Final fallback
        if (empty($branchID)) {
            $branchID = 1;
        }
    }
    
    $branchID = (int)$branchID;
    // log_message('debug', "Final branch ID: $branchID");
    
    // Get interviews
    $this->db->select('i.*, 
                      oa.first_name, oa.last_name, oa.guardian_name, 
                      oa.mobile_no, oa.grd_mobile_no,
                      oa.status as admission_status,
                      s.name as interviewer_name, 
                      c.name as class_name');
    $this->db->from('online_admission_interviews i');
    $this->db->join('online_admission oa', 'oa.id = i.admission_id', 'left');
    $this->db->join('staff s', 's.id = i.interviewer_id', 'left');
    $this->db->join('class c', 'c.id = oa.class_id', 'left');
    $this->db->where('i.branch_id', $branchID);
    
    // Filter by status
    $status = $this->input->get('status');
    if ($status && in_array($status, ['scheduled', 'completed', 'cancelled', 'no_show', 'rescheduled'])) {
        $this->db->where('i.status', $status);
    }
    
    $this->db->order_by('i.interview_date, i.interview_time', 'ASC');
    
    $query = $this->db->get();
    $interviews = $query->result_array();
    
    // log_message('debug', "Found " . count($interviews) . " interviews");
    
    $this->data['interviews'] = $interviews;
    $this->data['title'] = 'Admission Interviews';
    $this->data['sub_page'] = 'online_admission/interviews';
    $this->data['main_menu'] = 'admission';
    
        $this->data['interviews'] = $interviews;
        $this->data['title'] = 'Admission Interviews';
        $this->data['sub_page'] = 'online_admission/interviews';
        $this->data['main_menu'] = 'admission';
        
        // ========== CRITICAL FIX ==========
        // Load libraries in CORRECT ORDER with proper dependencies
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/daterangepicker/daterangepicker.css',
            ),
            'js' => array(
                // 1. First load moment.js (REQUIRED for daterangepicker)
                'vendor/moment/moment.js',
                // 2. Then load daterangepicker (depends on moment)
                'vendor/daterangepicker/daterangepicker.js',
            ),
        );
    
    $this->load->view('layout/index', $this->data);   
    
    // log_message('debug', '=== interviews() END ===');
}



/**
 * View/Edit interview
 */
public function interview($id = '')
{
    // Check access permission
    if (!get_permission('online_admission', 'is_view')) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('i.*, 
                      oa.first_name, oa.last_name, oa.guardian_name, 
                      oa.mobile_no, oa.grd_mobile_no, oa.email, oa.grd_email,
                      oa.status as admission_status,
                      s.name as interviewer_name, 
                      c.name as class_name, 
                      se.name as section_name, 
                      b.name as branch_name, b.mobileno as branch_phone');
    $this->db->from('online_admission_interviews i');
    $this->db->join('online_admission oa', 'oa.id = i.admission_id', 'left');
    $this->db->join('staff s', 's.id = i.interviewer_id', 'left');
    $this->db->join('class c', 'c.id = oa.class_id', 'left');
    $this->db->join('section se', 'se.id = oa.section_id', 'left');
    $this->db->join('branch b', 'b.id = oa.branch_id', 'left');
    $this->db->where('i.id', $id);
    
    // Branch isolation for non-superadmin
    if (!is_superadmin_loggedin()) {
        $this->db->where('i.branch_id', $branchID);
    }
    
    $interview = $this->db->get()->row_array();
    
    if (empty($interview)) {
        access_denied();
    }
    
    // Get communication history
    $this->db->where('interview_id', $id);
    $this->db->order_by('created_at', 'DESC');
    $communications = $this->db->get('interview_communications')->result_array();
    
    // Get interviewers for this branch (for reschedule dropdown)
    $interviewers = $this->get_interviewers($interview['branch_id']);
    
    $this->data['interview'] = $interview;
    $this->data['communications'] = $communications;
    $this->data['interviewers'] = $interviewers;
    $this->data['title'] = 'Interview - ' . $interview['first_name'] . ' ' . $interview['last_name'];
    $this->data['sub_page'] = 'online_admission/interview_view';
    $this->data['main_menu'] = 'admission';
    
    // Add JS/CSS for datepicker/timepicker
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/bootstrap-datepicker/css/bootstrap-datepicker.css',
            'vendor/bootstrap-timepicker/css/bootstrap-timepicker.css',
        ),
        'js' => array(
            'vendor/bootstrap-datepicker/js/bootstrap-datepicker.js',
            'vendor/bootstrap-timepicker/bootstrap-timepicker.js',
        ),
    );
    
    $this->load->view('layout/index', $this->data);
}

/**
 * Helper function to validate date
 */
private function isValidDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Complete an interview (scheduled/rescheduled → completed) - FIXED VERSION
 * Updates admission status to 5
 */
public function complete_interview($interview_id)
{
    if (!$this->input->is_ajax_request()) {
        echo json_encode(['success' => false, 'message' => 'Invalid request type']);
        exit();
    }
    
    if (!get_permission('online_admission', 'is_edit')) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    if (empty($interview)) {
        // Handle error
    }

    $modify_check = $this->can_modify_admission($interview['admission_id']);
    if (!$modify_check['can_modify']) {
        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => $modify_check['message']]);
        } else {
            set_alert('error', $modify_check['message']);
            redirect(base_url('online_admission/interviews'));
        }
        return;
    }

    // CRITICAL: Check if admission is already approved/declined
        $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
        $admission = $this->db->where('id', $interview['admission_id'])->get('online_admission')->row_array();

        if (in_array($admission['status'], [2, 3])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot modify interview. Admission has been ' . 
                            ($admission['status'] == 2 ? 'APPROVED' : 'DECLINED') . '.'
            ]);
            exit();
        }
            
    $this->db->trans_start();
    
    try {
        // Get interview with branch check
        $this->db->where('id', $interview_id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $interview = $this->online_admission_model->query_with_branch('online_admission_interviews', ['id' => $interview_id])->row_array();
        
        if (empty($interview)) {
            throw new Exception('Interview not found');
        }
        
        // Validate: Only scheduled/rescheduled interviews can be completed
        if (!in_array($interview['status'], ['scheduled', 'rescheduled'])) {
            throw new Exception('Only scheduled or rescheduled interviews can be marked as completed');
        }
        
        // Get outcome from POST data if available
        $outcome = $this->input->post('outcome') ?: $interview['outcome'];
        $notes = $this->input->post('notes') ?: $interview['notes'];
        $recommendation_notes = $this->input->post('recommendation_notes') ?: $interview['recommendation_notes'];
        
        // Update interview status to completed
        $update_data = [
            'status' => 'completed',
            'outcome' => $outcome,
            'notes' => $notes,
            'recommendation_notes' => $recommendation_notes,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->where('id', $interview_id);
        $this->db->update('online_admission_interviews', $update_data);
        
        // CRITICAL: Update admission status to 5 (Interview Completed)
        $this->db->where('id', $interview['admission_id']);
        $this->db->update('online_admission', [
            'status' => 5  // Interview completed, awaiting decision
        ]);
        
        // Log communication - WITH PROPER BRANCH_ID
        $this->log_interview_communication(
            $interview_id, 
            'note', 
            'internal', 
            'sent',
            "Interview marked as completed. Outcome: " . $outcome,
            null
        );
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            $error = $this->db->error();
            throw new Exception('Transaction failed: ' . $error['message']);
        }
        
        // Use standardized handler
        $this->handle_response(
            true, 
            'Interview completed successfully. Admission is now ready for final decision.',
            [],
            base_url('online_admission/interview/' . $interview_id)
        );
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        $this->handle_response(false, 'Error: ' . $e->getMessage());
    }
}
/**
 * Fix admission status when interview is completed but admission status not updated
 */
public function fix_admission_status($admission_id)
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    if (!get_permission('online_admission', 'is_edit')) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    try {
        // Get admission
        $admission = $this->db->where('id', $admission_id)->get('online_admission')->row_array();
        
        if (empty($admission)) {
            throw new Exception('Admission not found');
        }
        
        // Get completed interview
        $interview = $this->db->where('admission_id', $admission_id)
                             ->where('status', 'completed')
                             ->get('online_admission_interviews')
                             ->row_array();
        
        if (empty($interview)) {
            throw new Exception('No completed interview found');
        }
        
        // Update admission status to 5
        $this->db->where('id', $admission_id);
        $this->db->update('online_admission', ['status' => 5]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Admission status fixed. Now ready for decision.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

public function approve_after_interview($interview_id)
{
    if (!get_permission('online_admission', 'is_add')) {
        access_denied();
    }
    
    // CRITICAL: Check if interview is completed
    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    
    if (empty($interview) || $interview['status'] != 'completed') {
        set_alert('error', 'Interview must be completed first');
        redirect(base_url('online_admission/interview/' . $interview_id));
    }
    
    // Check admission status is 5
    $admission = $this->db->where('id', $interview['admission_id'])->get('online_admission')->row_array();
    
    if ($admission['status'] != 5) {
        set_alert('error', 'Admission must be in "Interview Completed" status (5)');
        redirect(base_url('online_admission/interview/' . $interview_id));
    }
    
    // SET SESSION FLAG TO INDICATE POST-INTERVIEW APPROVAL
    $this->session->set_userdata('post_interview_approval', [
        'interview_id' => $interview_id,
        'admission_id' => $interview['admission_id'],
        'is_post_interview' => true
    ]);
    
    // Now use existing approve() method with flag
    redirect(base_url('online_admission/approve/' . $interview['admission_id']));
}

/**
 * Decline admission after interview (status 5 → 3)
 */
public function decline_after_interview($interview_id)
{
    if (!get_permission('online_admission', 'is_add')) {
        access_denied();
    }
    
    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    
    if (empty($interview) || $interview['status'] != 'completed') {
        set_alert('error', 'Interview must be completed first');
        redirect(base_url('online_admission/interview/' . $interview_id));
    }
    
    // Check admission status is 5
    $admission = $this->db->where('id', $interview['admission_id'])->get('online_admission')->row_array();
    
    if ($admission['status'] != 5) {
        set_alert('error', 'Admission must be in "Interview Completed" status (5)');
        redirect(base_url('online_admission/interview/' . $interview_id));
    }
    
    // Use existing decline() method
    redirect(base_url('online_admission/decline/' . $interview['admission_id']));
}

/**
 * Helper: Check SMS credits for post-interview notifications
 */
private function check_post_interview_sms_credits($branchID, $interview, $template_id)
{
    $template = $this->db->get_where('sms_template_details', [
        'template_id' => $template_id,
        'branch_id' => $branchID
    ])->row_array();
    
    if (empty($template)) {
        return ['success' => false, 'message' => 'SMS template not configured'];
    }
    
    // Determine recipients count
    $recipient_count = 0;
    if ($template['notify_student'] == 1 && !empty($interview['mobile_no'])) {
        $recipient_count++;
    }
    if ($template['notify_parent'] == 1 && !empty($interview['grd_mobile_no'])) {
        $recipient_count++;
    }
    
    if ($recipient_count == 0) {
        return ['success' => false, 'message' => 'No recipients configured'];
    }
    
    // Prepare sample message for credit calculation
    $sample_message = $template['template_body'];
    $sample_message = str_replace('{guardian_name}', $interview['guardian_name'], $sample_message);
    $sample_message = str_replace('{student_name}', $interview['first_name'] . ' ' . $interview['last_name'], $sample_message);
    $sample_message = str_replace('{interview_date}', date('d M Y', strtotime($interview['interview_date'])), $sample_message);
    
    $required_credits = $this->calculate_admission_sms_cost($sample_message, $recipient_count);
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branchID);
    
    if ($current_credits < $required_credits) {
        return [
            'success' => false,
            'message' => "Insufficient SMS credits. Needed: {$required_credits}, Available: {$current_credits}"
        ];
    }
    
    return [
        'success' => true,
        'required_credits' => $required_credits,
        'recipient_count' => $recipient_count,
        'template' => $template
    ];
}

/**
 * Helper: Send post-interview SMS
 */
private function send_post_interview_sms($interview_id, $interview, $studentData, $enrollData, $required_credits, $reservation_id, $template, $template_id, $branchID)
{
    // Prepare recipients
    $recipients = [];
    
    // Student recipient
    if ($template['notify_student'] == 1 && !empty($interview['mobile_no'])) {
        $student_message = $template['template_body'];
        $student_message = str_replace('{name}', $interview['first_name'] . ' ' . $interview['last_name'], $student_message);
        
        if ($template_id == 14 && $studentData) { // Approval
            $student_message = str_replace('{register_no}', $studentData['register_no'] ?? '', $student_message);
            $student_message = str_replace('{username}', $studentData['username'] ?? '', $student_message);
            $student_message = str_replace('{password}', $studentData['password'] ?? '', $student_message);
        }
        
        $student_message = str_replace('{interview_date}', date('d M Y', strtotime($interview['interview_date'])), $student_message);
        
        $recipients[] = [
            'mobile' => $interview['mobile_no'],
            'message' => $student_message,
            'type' => 'student'
        ];
    }
    
    // Parent recipient
    if ($template['notify_parent'] == 1 && !empty($interview['grd_mobile_no'])) {
        $parent_message = $template['template_body'];
        $parent_message = str_replace('{name}', $interview['guardian_name'], $parent_message);
        $parent_message = str_replace('{student_name}', $interview['first_name'] . ' ' . $interview['last_name'], $parent_message);
        
        if ($template_id == 14 && $studentData) { // Approval
            $parent_message = str_replace('{register_no}', $studentData['register_no'] ?? '', $parent_message);
        }
        
        $parent_message = str_replace('{interview_date}', date('d M Y', strtotime($interview['interview_date'])), $parent_message);
        
        $recipients[] = [
            'mobile' => $interview['grd_mobile_no'],
            'message' => $parent_message,
            'type' => 'parent'
        ];
    }
    
    if (empty($recipients)) {
        return ['success' => false, 'message' => 'No valid recipients', 'sent' => 0];
    }
    
    // Send SMS to each recipient
    $sent_count = 0;
    $this->load->library('bulksmsbd', ['branch_id' => $branchID], 'sms_lib');
    
    foreach ($recipients as $recipient) {
        $mobile = preg_replace('/[^0-9]/', '', $recipient['mobile']);
        
        if (!empty($mobile)) {
            try {
                $response = $this->sms_lib->send($mobile, $recipient['message']);
                
                if ($this->is_successful_sms_response($response)) {
                    $sent_count++;
                    
                    // Log delivery
                    $this->db->insert('sms_email_delivery_logs', [
                        'recipient_contact' => $mobile,
                        'status' => 'sent',
                        'gateway_response' => substr($response, 0, 500),
                        'sent_at' => date('Y-m-d H:i:s'),
                        'branch_id' => $branchID
                    ]);
                    
                } else {
                    // Log failed delivery
                    $this->db->insert('sms_email_delivery_logs', [
                        'recipient_contact' => $mobile,
                        'status' => 'failed',
                        'gateway_response' => substr($response, 0, 500),
                        'sent_at' => date('Y-m-d H:i:s'),
                        'branch_id' => $branchID
                    ]);
                }
                
            } catch (Exception $e) {
                // log_message('error', "Exception sending SMS to {$mobile}: " . $e->getMessage());
            }
            
            usleep(50000); // 0.05s delay
        }
    }
    
    // Create bulk SMS record
    $campaign_name = ($template_id == 14 ? "Post-Interview Approval" : "Post-Interview Decline") . 
                     " - " . $interview['first_name'] . ' ' . $interview['last_name'];
    
    $message_data = [
        'campaign_name' => $campaign_name,
        'message' => $recipients[0]['message'], // Use first message as sample
        'message_type' => 1,
        'recipient_type' => ($template_id == 14 ? 14 : 15), // Custom type
        'recipients_details' => json_encode([
            'interview_id' => $interview_id,
            'template_id' => $template_id,
            'reservation_id' => $reservation_id
        ]),
        'additional' => json_encode($recipients),
        'schedule_time' => date('Y-m-d H:i:s'),
        'posting_status' => ($sent_count == count($recipients) ? 2 : ($sent_count > 0 ? 4 : 3)),
        'total_thread' => count($recipients),
        'successfully_sent' => $sent_count,
        'sms_gateway' => 'bulksmsbd',
        'credits_used' => ($sent_count > 0 ? $required_credits : 0),
        'branch_id' => $branchID,
        'duplicate_hash' => md5('post_interview_' . $interview_id . '_' . $template_id),
        'send_type' => 'immediate',
        'transaction_id' => 'PINT_' . $interview_id . '_' . time(),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $this->db->insert('bulk_sms_email', $message_data);
    $message_id = $this->db->insert_id();
    
    // Update reservation with message ID
    $this->db->where('id', $reservation_id);
    $this->db->update('sms_credit_reservations', [
        'message_id' => $message_id,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    // log_message('info', "Post-interview SMS (Template {$template_id}): {$sent_count}/" . count($recipients) . " sent");
    
    return [
        'success' => ($sent_count > 0),
        'sent' => $sent_count,
        'total' => count($recipients),
        'message' => ($sent_count > 0 ? 'SMS sent successfully' : 'SMS sending failed')
    ];
}
  /**
 * Update interview status/outcome - FIXED VERSION
 */
public function update_interview($id)
{
    if (!get_permission('online_admission', 'is_edit')) {
        access_denied();
    }
    // Get interview first
    $interview = $this->db->where('id', $id)->get('online_admission_interviews')->row_array();
    
    if (empty($interview)) {
        set_alert('error', 'Interview not found');
        redirect(base_url('online_admission/interviews'));
    }
    
    // ========== CRITICAL: CHECK IF FINAL DECISION ALREADY MADE ==========
    $admission = $this->db->where('id', $interview['admission_id'])->get('online_admission')->row_array();
    
    if (in_array($admission['status'], [2, 3])) {
        // Admission already approved/declined - interview is read-only
        set_alert('error', 'Cannot update interview. Admission has already been ' . 
                  ($admission['status'] == 2 ? 'APPROVED' : 'DECLINED') . '.');
        redirect(base_url('online_admission/interview/' . $id));
    }
    if ($_POST) {
        $this->form_validation->set_rules('status', 'Status', 'required');
        $this->form_validation->set_rules('outcome', 'Outcome', 'required');
        
        if ($this->form_validation->run() == true) {
            $this->db->trans_start();
            
            try {
                $interview = $this->db->where('id', $id)->get('online_admission_interviews')->row_array();
                
                if (empty($interview)) {
                    throw new Exception('Interview not found');
                }
                
                $old_status = $interview['status'];
                $new_status = $this->input->post('status');
                $outcome = $this->input->post('outcome');
                
                $update_data = [
                    'status' => $new_status,
                    'outcome' => $outcome,
                    'notes' => $this->input->post('notes'),
                    'recommendation_notes' => $this->input->post('recommendation_notes'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->where('id', $id);
                $this->db->update('online_admission_interviews', $update_data);
                
                // ========== HANDLE STATUS CHANGES ==========
                
                // If interview completed
                if ($new_status == 'completed' && $old_status != 'completed') {
                    $admission_update = ['status' => 5]; // 5 = Interview Completed
                    $this->db->where('id', $interview['admission_id']);
                    $this->db->update('online_admission', $admission_update);
                    
                    // Log completion
                    $this->log_interview_communication($id, 'note', 'internal', 'sent', 
                        "Interview marked as completed. Outcome: " . $outcome, null);
                        
                    // Send follow-up SMS if requested (use existing method)
                    if ($this->input->post('send_followup') == 1) {
                        $branch = $this->db->where('id', $interview['branch_id'])->get('branch')->row_array();
                        $admission = $this->db->where('id', $interview['admission_id'])->get('online_admission')->row_array();
                        $this->send_interview_followup($id, $admission, $branch); // This method exists
                    }
                }
                
                // If marked as no-show
                if ($new_status == 'no_show' && $old_status != 'no_show') {
                    // Log no-show
                    $this->log_interview_communication($id, 'note', 'internal', 'sent', 
                        "Interview marked as No-Show. Candidate did not attend.", null);
                    
                    // Handle no-show decision
                    $no_show_action = $this->input->post('no_show_action');
                    if ($no_show_action == 'reschedule') {
                        // Reschedule logic will be handled separately
                        $this->db->where('id', $id);
                        $this->db->update('online_admission_interviews', [
                            'notes' => $interview['notes'] . "\n\n[NO-SHOW] To be rescheduled.",
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    
                    } elseif ($no_show_action == 'decline') {
                        // Decline admission due to no-show
                        $this->db->where('id', $admission_id);
                        $this->db->update('online_admission', ['status' => 3]); // Declined
                        
                        $sms_result = $this->sms_model->sendAdmissionOutcome(
                            $admission_id,
                            17, // Template ID for decline
                            $branchID,
                            [], // student_data
                            []  // parent_data
                        );
                        
                        set_alert('warning', 'Admission declined due to no-show. ' . 
                                ($sms_result['success'] ? 'Notification sent.' : 'Notification failed.'));
                    }
                }
                
                // If rescheduled - USE EXISTING METHOD
                if ($new_status == 'rescheduled' && $old_status != 'rescheduled') {
                    // Use the existing reschedule_interview method
                    // The modal should handle this separately
                    // Just log it
                    $this->log_interview_communication($id, 'note', 'internal', 'sent', 
                        "Interview marked as rescheduled", null);
                }
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    throw new Exception('Transaction failed');
                }
                
                set_alert('success', 'Interview updated successfully');
                redirect(base_url('online_admission/interview/' . $id));
                
            } catch (Exception $e) {
                $this->db->trans_rollback();
                set_alert('error', $e->getMessage());
                redirect(base_url('online_admission/interview/' . $id));
            }
        }
    }
}

/**
 * Log interview communication - FIXED VERSION
 */
private function log_interview_communication($interview_id, $type, $direction, $status, $message, $recipient = null)
{
    // First get the interview to get branch_id
    $interview = $this->db->select('branch_id')->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    
    if (empty($interview)) {
        // log_message('error', 'Cannot log communication: Interview not found - ID ' . $interview_id);
        return false;
    }
    
    $branch_id = $interview['branch_id'];
    
    if (empty($branch_id)) {
        // Fallback: try to get from session
        $branch_id = get_loggedin_branch_id();
        // log_message('warning', 'Using session branch_id for communication log');
    }
    
    $log_data = [
        'interview_id' => $interview_id,
        'type' => $type,
        'direction' => $direction,
        'message' => $message,
        'recipient' => $recipient,
        'sent_by' => $this->session->userdata('loggedin_userid') ?: 1,
        'status' => $status,
        'created_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id // NOW THIS IS GUARANTEED
    ];
    
    $this->db->insert('interview_communications', $log_data);
    return $this->db->insert_id();
}
    
    /**
     * Create bulk SMS record for interview
     */
    private function create_interview_sms_record($interview_id, $message, $recipient, $credits, $branch_id, $type)
    {
        $campaign_name = "Interview " . ucfirst($type) . " - " . date('d M Y');
        
        $sms_data = [
            'campaign_name' => $campaign_name,
            'message' => $message,
            'message_type' => 1,
            'recipient_type' => 10, // Interview notification
            'recipients_details' => json_encode(['interview_id' => $interview_id, 'type' => $type]),
            'additional' => json_encode([['mobile' => $recipient]]),
            'schedule_time' => date('Y-m-d H:i:s'),
            'posting_status' => 2, // Completed
            'total_thread' => 1,
            'successfully_sent' => 1,
            'sms_gateway' => 'bulksmsbd',
            'credits_used' => $credits,
            'branch_id' => $branch_id,
            'duplicate_hash' => md5('interview_' . $interview_id . '_' . $type . '_' . date('Y-m-d')),
            'send_type' => 'immediate',
            'transaction_id' => 'INT_' . $interview_id . '_' . time(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('bulk_sms_email', $sms_data);
    }

    /**
 * Get interview details for AJAX
 */
public function get_interview_details($id)
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('i.*, oa.first_name, oa.last_name, oa.guardian_name, 
                      oa.grd_mobile_no, oa.mobile_no');
    $this->db->from('online_admission_interviews i');
    $this->db->join('online_admission oa', 'oa.id = i.admission_id', 'left');
    $this->db->where('i.id', $id);
    $this->db->where('i.branch_id', $branchID);
    
    $interview = $this->db->get()->row_array();
    
    if (empty($interview)) {
        echo json_encode(['success' => false, 'message' => 'Interview not found']);
        return;
    }
    
    $data = [
        'student_name' => $interview['first_name'] . ' ' . $interview['last_name'],
        'guardian_name' => $interview['guardian_name'],
        'guardian_mobile' => $interview['grd_mobile_no'] ?: $interview['mobile_no'],
        'interview_date' => date('d M Y', strtotime($interview['interview_date'])),
        'interview_time' => date('h:i A', strtotime($interview['interview_time']))
    ];
    
    echo json_encode(['success' => true, 'data' => $data]);
}


public function add_communication_note($interview_id)
{
    if (!get_permission('online_admission', 'is_add')) {
        access_denied();
    }
    
    $note = $this->input->post('note');
    
    if (!empty($note)) {
        // Get interview to get branch_id
        $interview = $this->db->select('branch_id')->where('id', $interview_id)->get('online_admission_interviews')->row_array();
        
        if ($interview) {
            $log_data = [
                'interview_id' => $interview_id,
                'type' => 'note',
                'direction' => 'internal',
                'message' => $note,
                'sent_by' => $this->session->userdata('loggedin_userid'),
                'status' => 'sent',
                'created_at' => date('Y-m-d H:i:s'),
                'branch_id' => $interview['branch_id'] // FIXED: Add branch_id
            ];
            
            $this->db->insert('interview_communications', $log_data);
            set_alert('success', 'Note added');
        }
    }
    
    redirect(base_url('online_admission/interview_view/' . $interview_id));
}


/**
 * Check SMS credits for admission notification
 */
private function check_admission_sms_credits($branchID, $stuDetails, $postData)
{
    // Get SMS template for admission approval (template_id = 1)
    $template = $this->db->get_where('sms_template_details', [
        'template_id' => 1,
        'branch_id' => $branchID
    ])->row_array();
    
    if (empty($template)) {
        return [
            'success' => false,
            'message' => 'SMS template not configured. Please configure SMS template ID 1 for this branch.'
        ];
    }
    
    // Determine recipients count
    $recipient_count = 0;
    if ($template['notify_student'] == 1 && !empty($stuDetails['mobile_no'])) {
        $recipient_count++;
    }
    if ($template['notify_parent'] == 1 && !empty($stuDetails['grd_mobile_no'])) {
        $recipient_count++;
    }
    
    if ($recipient_count == 0) {
        return [
            'success' => false,
            'message' => 'No valid recipients with mobile numbers found. Please check applicant contact information.'
        ];
    }
    
    // Prepare sample message for credit calculation
    $sample_message = $template['template_body'];
    $sample_message = str_replace('{name}', $stuDetails['first_name'] . ' ' . $stuDetails['last_name'], $sample_message);
    $sample_message = str_replace('{register_no}', 'REG12345', $sample_message);
    $sample_message = str_replace('{username}', 'sampleuser', $sample_message);
    $sample_message = str_replace('{password}', 'samplepass', $sample_message);
    
    // Calculate required credits
    $required_credits = $this->calculate_admission_sms_cost($sample_message, $recipient_count);
    
    // Get current credits
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branchID);
    
    // ========== FIX: Clear, actionable error messages ==========
    if ($current_credits < $required_credits) {
        $shortfall = $required_credits - $current_credits;
        
        // Log the issue
        // log_message('error', "Insufficient SMS credits for branch {$branchID}. Required: {$required_credits}, Available: {$current_credits}");
        
        return [
            'success' => false,
            'required_credits' => $required_credits,
            'available_credits' => $current_credits,
            'shortfall' => $shortfall,
            'recipient_count' => $recipient_count,
            'message' => "Insufficient SMS Credits!\n\n" .
                         "Required: {$required_credits} credits\n" .
                         "Available: {$current_credits} credits\n" .
                         "Shortfall: {$shortfall} credits\n\n" .
                         "Please purchase more SMS credits before approving this admission.\n" .
                         "Go to: SMS → Purchase Credits"
        ];
    }
    
    return [
        'success' => true,
        'required_credits' => $required_credits,
        'available_credits' => $current_credits,
        'remaining' => $current_credits - $required_credits,
        'recipient_count' => $recipient_count,
        'template' => $template,
        'message' => "SMS credits sufficient: {$current_credits} available, {$required_credits} needed."
    ];
}
    
    /**
     * Send admission notification SMS with credit deduction
     */
    private function send_admission_notification_sms($studentData, $enrollData, $stuDetails, $postData, $required_credits)
    {
        $branchID = $postData['branch_id'];
        $studentID = $studentData['student_id'];
        
        // Get SMS template
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 1,
            'branch_id' => $branchID
        ])->row_array();
        
        if (empty($template) || empty($template['template_body'])) {
            return ['success' => false, 'message' => 'SMS template not configured'];
        }
        
        if ($template['notify_student'] == 0 && $template['notify_parent'] == 0) {
            return ['success' => false, 'message' => 'SMS notifications disabled'];
        }
        
        // Get student details with parent
        $student = $this->db->select('s.first_name, s.last_name, s.mobileno, s.email, s.register_no, 
                                     p.id as parent_id, p.mobileno as parent_mobile, p.name as parent_name')
                           ->from('student s')
                           ->join('parent p', 'p.id = s.parent_id', 'left')
                           ->where('s.id', $studentID)
                           ->get()
                           ->row_array();
        
        if (empty($student)) {
            return ['success' => false, 'message' => 'Student not found'];
        }
        
        // Prepare recipients
        $recipients = [];
        $messages = [];
        
        // Student message
        $student_message = $template['template_body'];
        $student_message = str_replace('{name}', $student['first_name'] . ' ' . $student['last_name'], $student_message);
        $student_message = str_replace('{register_no}', $student['register_no'], $student_message);
        $student_message = str_replace('{username}', $studentData['username'], $student_message);
        $student_message = str_replace('{password}', $studentData['password'], $student_message);
        $student_message = str_replace('{class}', get_type_name_by_id('class', $enrollData['class_id']), $student_message);
        $student_message = str_replace('{section}', get_type_name_by_id('section', $enrollData['section_id']), $student_message);
        $student_message = str_replace('{roll}', $enrollData['roll'], $student_message);
        
        if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
            $recipients[] = [
                'mobile' => $student['mobileno'],
                'message' => $student_message,
                'type' => 'student'
            ];
        }
        
        // Parent message (same as student message for now)
        if ($template['notify_parent'] == 1 && !empty($student['parent_mobile'])) {
            $parent_message = $student_message;
            // You could customize parent message differently if needed
            $recipients[] = [
                'mobile' => $student['parent_mobile'],
                'message' => $parent_message,
                'type' => 'parent'
            ];
        }
        
        if (empty($recipients)) {
            return ['success' => false, 'message' => 'No valid recipients'];
        }
        
        // Calculate actual credits needed (re-calculate with actual message)
        $actual_required_credits = $this->calculate_admission_sms_cost($student_message, count($recipients));
        
        // Reserve credits
        $campaign_name = "Admission Approved - " . $student['first_name'] . ' ' . $student['last_name'];
        $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
            $branchID, 
            $actual_required_credits, 
            $campaign_name,
            null // Will be updated after message insert
        );
        
        if (!$reservation_id) {
            return ['success' => false, 'message' => 'Failed to reserve SMS credits'];
        }
        
        // Send SMS to each recipient
        $sent_count = 0;
        $this->load->library('bulksmsbd', ['branch_id' => $branchID], 'sms_lib');
        
        foreach ($recipients as $recipient) {
            $mobile = preg_replace('/[^0-9]/', '', $recipient['mobile']);
            
            if (!empty($mobile)) {
                try {
                    $response = $this->sms_lib->send($mobile, $recipient['message']);
                    
                    if ($this->is_successful_sms_response($response)) {
                        $sent_count++;
                        // log_message('info', "Admission SMS sent to {$mobile} ({$recipient['type']})");
                    } else {
                        // log_message('error', "Admission SMS failed for {$mobile}: " . substr($response, 0, 200));
                    }
                    
                    // Log delivery
                    $this->log_admission_delivery($studentID, $mobile, $response, $recipient['type'], $branchID);
                    
                } catch (Exception $e) {
                    // log_message('error', "Exception sending SMS to {$mobile}: " . $e->getMessage());
                }
                
                usleep(50000); // 0.05s delay
            }
        }
        
        // Create bulk SMS record
        $duplicate_hash = md5('admission_' . $studentID . '_' . date('Y-m-d'));
        
        $message_data = [
            'campaign_name' => $campaign_name,
            'message' => $student_message,
            'message_type' => 1,
            'recipient_type' => 8, // Online admission
            'recipients_details' => json_encode([
                'student_id' => $studentID,
                'template_id' => 1,
                'reservation_id' => $reservation_id
            ]),
            'additional' => json_encode($recipients),
            'schedule_time' => date('Y-m-d H:i:s'),
            'posting_status' => ($sent_count == count($recipients) ? 2 : ($sent_count > 0 ? 4 : 3)),
            'total_thread' => count($recipients),
            'successfully_sent' => $sent_count,
            'sms_gateway' => 'bulksmsbd',
            'credits_used' => ($sent_count > 0 ? $actual_required_credits : 0),
            'branch_id' => $branchID,
            'duplicate_hash' => $duplicate_hash,
            'send_type' => 'immediate',
            'transaction_id' => 'ADM_' . $studentID . '_' . time(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('bulk_sms_email', $message_data);
        $message_id = $this->db->insert_id();
        
        // Update reservation with message ID
        $this->db->where('id', $reservation_id);
        $this->db->update('sms_credit_reservations', [
            'message_id' => $message_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Mark reservation as used
        $this->sendsmsmail_model->mark_reservation_used($message_id, $actual_required_credits);
        
        // log_message('info', "Admission SMS completed: {$sent_count}/" . count($recipients) . " sent, Credits: {$actual_required_credits}");
        
        return [
            'success' => ($sent_count > 0),
            'sent' => $sent_count,
            'total' => count($recipients),
            'credits_used' => ($sent_count > 0 ? $actual_required_credits : 0),
            'message' => ($sent_count > 0 ? 'SMS sent successfully' : 'SMS sending failed')
        ];
    }
  /**
 * Prepare admission data from interview for student creation
 */
private function prepare_admission_data_from_interview($interview)
{
    return [
        'first_name' => $interview['first_name'],
        'last_name' => $interview['last_name'],
        'gender' => $interview['gender'],
        'birthday' => $interview['birthday'],
        'religion' => $interview['religion'],
        'caste' => $interview['caste'],
        'blood_group' => $interview['blood_group'],
        'mobile_no' => $interview['mobile_no'],
        'mother_tongue' => $interview['mother_tongue'],
        'present_address' => $interview['present_address'],
        'permanent_address' => $interview['permanent_address'],
        'admission_date' => date('Y-m-d'),
        'city' => $interview['city'],
        'state' => $interview['state'],
        'student_photo' => $interview['student_photo'],
        'email' => $interview['email'],
        'guardian_name' => $interview['guardian_name'],
        'guardian_relation' => $interview['guardian_relation'],
        'father_name' => $interview['father_name'],
        'mother_name' => $interview['mother_name'],
        'grd_occupation' => $interview['grd_occupation'],
        'grd_income' => $interview['grd_income'],
        'grd_education' => $interview['grd_education'],
        'grd_email' => $interview['grd_email'],
        'grd_mobile_no' => $interview['grd_mobile_no'],
        'grd_address' => $interview['grd_address'],
        'grd_city' => $interview['grd_city'],
        'grd_state' => $interview['grd_state'],
        'grd_photo' => $interview['grd_photo'],
        'branch_id' => $interview['branch_id'],
        'class_id' => $interview['class_id'],
        'section_id' => $interview['section_id'],
        'register_no' => $this->online_admission_model->regSerNumber($interview['branch_id']),
        'year_id' => get_session_id(),
        'category_id' => $interview['category_id'] ?? 0
    ];
}

    /**
     * Calculate SMS cost for admission messages
     */
    private function calculate_admission_sms_cost($message, $recipient_count)
    {
        return $this->application_model->calculate_sms_credits($message, $recipient_count);
    }
    
    private function is_successful_sms_response($response)
    {
        return $this->sendsmsmail_model->is_sms_successful($response);
    }
    /**
     * Log delivery to database
     */
    private function log_admission_delivery($student_id, $mobile, $response, $recipient_type, $branch_id)
    {
        $status = $this->is_successful_sms_response($response) ? 'sent' : 'failed';
        
        $log_data = [
            'recipient_contact' => $mobile,
            'status' => $status,
            'gateway_response' => substr($response, 0, 500),
            'sent_at' => date('Y-m-d H:i:s'),
            'branch_id' => $branch_id
        ];
        
        $this->db->insert('sms_email_delivery_logs', $log_data);
    }
    
    /**
     * AJAX method to check SMS credits before submission
     */
    public function check_sms_credits()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $branchID = $this->input->post('branch_id');
        $studentID = $this->input->post('student_id');
        
        if (empty($branchID) || empty($studentID)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        // Get student details
        $stuDetails = $this->online_admission_model->get('online_admission', 
            array('id' => $studentID, 'status !=' => 2), true, true);
        
        if (empty($stuDetails)) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            return;
        }
        
        // Check SMS credits
        $sms_check = $this->check_admission_sms_credits($branchID, $stuDetails, []);
        
        echo json_encode($sms_check);
    }

    
    
   
   
    // unique valid username verification is done here
    public function unique_username($username)
    {
        if ($this->input->post('student_id')) {
            $student_id = $this->input->post('student_id');
            $login_id = $this->app_lib->get_credential_id($student_id, 'student');
            $this->db->where_not_in('id', $login_id);
        }
        $this->db->where('username', $username);
        $query = $this->db->get('login_credential');
        if ($query->num_rows() > 0) {
            $this->form_validation->set_message("unique_username", translate('already_taken'));
            return false;
        } else {
            return true;
        }
    }

    /* unique valid guardian email address verification is done here */
    public function get_valid_guardian_username($username)
    {
        $this->db->where('username', $username);
        $query = $this->db->get('login_credential');
        if ($query->num_rows() > 0) {
            $this->form_validation->set_message("get_valid_guardian_username", translate('username_has_already_been_used'));
            return false;
        } else {
            return true;
        }
    }

    /* unique valid student roll verification is done here */
    public function unique_roll($roll)
    {
        if (empty($roll)) {
            return true;
        }
        $branchID = $this->application_model->get_branch_id();
        $schoolSettings = $this->online_admission_model->get('branch', array('id' => $branchID), true, false, 'unique_roll');
        $unique_roll = $schoolSettings['unique_roll'];
        if (empty($unique_roll) && $unique_roll == 0) {
            return true;
        }

        $classID = $this->input->post('class_id');
        $sectionID = $this->input->post('section_id');
        if ($this->uri->segment(3)) {
            $this->db->where_not_in('student_id', $this->uri->segment(3));
        }
        if ($unique_roll == 2) {
            $this->db->where('section_id', $sectionID);
        }
        $this->db->where(array('roll' => $roll, 'class_id' => $classID, 'branch_id' => $branchID));
        $q = $this->db->get('enroll')->num_rows();
        if ($q == 0) {
            return true;
        } else {
            $this->form_validation->set_message("unique_roll", translate('already_taken'));
            return false;
        }
    }


    /* unique valid register ID verification is done here */
    public function unique_registerid($register)
    {
        $branchID = $this->application_model->get_branch_id();
        if ($this->uri->segment(3)) {
            $this->db->where_not_in('id', $this->uri->segment(3));
        }
        $this->db->where('register_no', $register);
        $query = $this->db->get('student')->num_rows();
        if ($query == 0) {
            return true;
        } else {
            $this->form_validation->set_message("unique_registerid", translate('already_taken'));
            return false;
        }
    }

    /**
 * Download uploaded document
 */
public function download_doc($id)
{
    // Check permission
    if (!get_permission('online_admission', 'is_view')) {
        access_denied();
    }
    
    // Get admission record with branch filter
    $this->db->select('doc, first_name, last_name, branch_id');
    $this->db->where('id', $id);
    
    // Apply branch filter
    if (!is_superadmin_loggedin()) {
        $branchID = $this->application_model->get_branch_id();
        $this->db->where('branch_id', $branchID);
    }
    
    $admission = $this->db->get('online_admission')->row_array();
    
    if (empty($admission) || empty($admission['doc'])) {
        set_alert('error', 'Document not found');
        redirect($_SERVER['HTTP_REFERER'] ?? base_url('online_admission'));
    }
    
    $file_path = 'uploads/online_ad_documents/' . $admission['doc'];
    
    if (!file_exists($file_path)) {
        set_alert('error', 'File does not exist on server');
        redirect($_SERVER['HTTP_REFERER'] ?? base_url('online_admission'));
    }
    
    // Get file extension
    $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
    
    // Create a clean filename
    $student_name = str_replace(' ', '_', $admission['first_name'] . '_' . $admission['last_name']);
    $download_name = $student_name . '_document_' . date('Y-m-d') . '.' . $file_ext;
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read file and output
    readfile($file_path);
    exit;
}

    // send online admission sms with credit checking
    /**
 * Send online admission approval SMS with credit checking
 */
private function send_online_admission_sms($studentData, $enrollData, $postData)
{
    $branchID = $postData['branch_id'];
    $studentID = $studentData['student_id'];
    
    // ========== GET STUDENT DETAILS ==========
    $student = $this->db->select('s.first_name, s.last_name, s.mobileno, s.email, s.register_no, 
                                 p.id as parent_id, p.mobileno as parent_mobile, p.name as parent_name')
                       ->from('student s')
                       ->leftJoin('parent p', 'p.id = s.parent_id')
                       ->where('s.id', $studentID)
                       ->get()
                       ->row_array();
    
    if (empty($student)) {
        return ['success' => false, 'message' => 'Student not found'];
    }
    
    // ========== GET SMS TEMPLATE ==========
    $template = $this->db->get_where('sms_template_details', [
        'template_id' => 1, // Template ID 1 is for "Account Activation"
        'branch_id' => $branchID
    ])->row_array();
    
    if (empty($template) || empty($template['template_body'])) {
        return ['success' => false, 'message' => 'SMS template not configured'];
    }
    
    if ($template['notify_student'] == 0 && $template['notify_parent'] == 0) {
        return ['success' => false, 'message' => 'SMS notifications disabled for this template'];
    }
    
    // ========== PREPARE RECIPIENTS ==========
    $recipients = [];
    
    // Student recipient
    if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
        $recipients[] = [
            'id' => $studentID,
            'name' => $student['first_name'] . ' ' . $student['last_name'],
            'mobileno' => $student['mobileno'],
            'email' => $student['email'],
            'type' => 'student',
            'register_no' => $student['register_no']
        ];
    }
    
    // Parent recipient
    if ($template['notify_parent'] == 1 && !empty($student['parent_mobile'])) {
        $recipients[] = [
            'id' => $student['parent_id'] . '_parent',
            'name' => $student['parent_name'],
            'mobileno' => $student['parent_mobile'],
            'email' => '',
            'type' => 'parent',
            'student_name' => $student['first_name'] . ' ' . $student['last_name'],
            'student_register_no' => $student['register_no']
        ];
    }
    
    if (empty($recipients)) {
        return ['success' => false, 'message' => 'No recipients with valid mobile numbers'];
    }
    
    // ========== PREPARE MESSAGE ==========
    $base_message = $template['template_body'];
    $base_message = str_replace('{name}', $student['first_name'] . ' ' . $student['last_name'], $base_message);
    $base_message = str_replace('{register_no}', $student['register_no'], $base_message);
    $base_message = str_replace('{username}', $studentData['username'], $base_message);
    $base_message = str_replace('{password}', $studentData['password'], $base_message);
    $base_message = str_replace('{class}', get_type_name_by_id('class', $enrollData['class_id']), $base_message);
    $base_message = str_replace('{section}', get_type_name_by_id('section', $enrollData['section_id']), $base_message);
    $base_message = str_replace('{roll}', $enrollData['roll'], $base_message);
    
    // For parent messages, replace name with parent name
    $parent_message = $base_message;
    $parent_message = str_replace('{name}', $student['parent_name'], $parent_message);
    
    // ========== CALCULATE CREDITS NEEDED ==========
    $this->load->model('sendsmsmail_model');
    $total_credits_needed = $this->calculate_admission_sms_cost($base_message, count($recipients));
    
    // ========== CHECK CREDITS ==========
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branchID);
    
    if ($current_credits < $total_credits_needed) {
        return [
            'success' => false, 
            'message' => "Insufficient SMS credits. Needed: {$total_credits_needed}, Available: {$current_credits}"
        ];
    }
    
    // ========== CREATE BULK SMS ENTRY ==========
    $campaign_name = "Online Admission Approved - " . $student['first_name'] . ' ' . $student['last_name'] . " - " . date('d M Y');
    
    // Generate duplicate hash to prevent duplicates
    $duplicate_hash = $this->generate_admission_duplicate_hash($studentID, $branchID);
    
    // Check for existing duplicate
    $this->db->where('duplicate_hash', $duplicate_hash);
    $this->db->where('DATE(created_at)', date('Y-m-d'));
    $existing = $this->db->get('bulk_sms_email')->row();
    
    if ($existing) {
        return ['success' => false, 'message' => 'Admission SMS already sent today'];
    }
    
    // ========== RESERVE CREDITS ==========
    $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
        $branchID, 
        $total_credits_needed, 
        $campaign_name, 
        null // Message ID will be set after insert
    );
    
    if (!$reservation_id) {
        return ['success' => false, 'message' => 'Failed to reserve SMS credits'];
    }
    
    // ========== CREATE SMS ENTRY ==========
    $message_data = [
        'campaign_name' => $campaign_name,
        'message' => $base_message,
        'message_type' => 1,
        'recipient_type' => 8, // 8 = online admission (you can define this)
        'recipients_details' => json_encode([
            'student_id' => $studentID,
            'template_id' => 1,
            'notify_student' => $template['notify_student'],
            'notify_parent' => $template['notify_parent'],
            'reservation_id' => $reservation_id
        ]),
        'additional' => json_encode($recipients),
        'schedule_time' => date('Y-m-d H:i:s'),
        'posting_status' => 1, // Scheduled (will send immediately)
        'total_thread' => count($recipients),
        'successfully_sent' => 0,
        'sms_gateway' => 'bulksmsbd',
        'credits_used' => 0,
        'branch_id' => $branchID,
        'duplicate_hash' => $duplicate_hash,
        'send_type' => 'online_admission_approved',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $this->db->insert('bulk_sms_email', $message_data);
    $message_id = $this->db->insert_id();
    
    // Update reservation with message ID
    $this->db->where('id', $reservation_id);
    $this->db->update('sms_credit_reservations', [
        'message_id' => $message_id,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    // ========== SEND SMS IMMEDIATELY ==========
    $sent_count = 0;
    $failed_count = 0;
    
    $this->load->library('bulksmsbd', ['branch_id' => $branchID], 'sms_lib');
    
    foreach ($recipients as $recipient) {
        // Personalize message
        $personalized_msg = ($recipient['type'] == 'parent') ? $parent_message : $base_message;
        
        // Clean mobile number
        $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
        
        if (!empty($mobile)) {
            // Send SMS
            $response = $this->sms_lib->send($mobile, $personalized_msg);
            
            // Log delivery
            $this->log_admission_delivery($message_id, $recipient, $response, $branchID);
            
            // Check if successful
            if ($this->is_successful_sms_response($response)) {
                $sent_count++;
                // log_message('info', "Admission SMS sent to {$mobile}");
            } else {
                $failed_count++;
                // log_message('error', "Admission SMS failed for {$mobile}: " . substr($response, 0, 200));
            }
        }
        
        usleep(50000); // 0.05s delay
    }
    
    // ========== UPDATE STATUS ==========
    $final_status = ($sent_count == count($recipients)) ? 2 : ($sent_count > 0 ? 4 : 3);
    
    $this->db->where('id', $message_id);
    $this->db->update('bulk_sms_email', [
        'posting_status' => $final_status,
        'successfully_sent' => $sent_count,
        'credits_used' => $total_credits_needed,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    // Mark reservation as used
    $this->sendsmsmail_model->mark_reservation_used($message_id, $total_credits_needed);
    
    // log_message('info', "Online admission SMS sent: {$sent_count}/" . count($recipients) . " successful");
    
    return [
        'success' => true,
        'sent' => $sent_count,
        'total' => count($recipients),
        'credits_used' => $total_credits_needed
    ];
    
}

/**
 * Generate duplicate hash for admission SMS
 */
private function generate_admission_duplicate_hash($student_id, $branch_id)
{
    $data = [
        'type' => 'online_admission',
        'student_id' => $student_id,
        'branch_id' => $branch_id,
        'date' => date('Y-m-d'),
        'template_id' => 1
    ];
    return md5(serialize($data));
}

/**
 * Record interview outcome (AJAX)
 */
public function record_interview_outcome($interview_id)
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $outcome = $this->input->post('outcome');
    $notes = $this->input->post('notes');
    
    $this->db->where('id', $interview_id);
    $this->db->update('online_admission_interviews', [
        'outcome' => $outcome,
        'recommendation_notes' => $notes,
        'status' => 'completed',
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Outcome recorded']);
}


/**
 * Send interview reminder (FIXED VERSION)
 */
public function send_reminder()
{
    if (!get_permission('online_admission', 'is_edit')) {
        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit();
        }
        access_denied();
    }
    
    if (!$this->input->is_ajax_request()) {
        echo json_encode(['success' => false, 'message' => 'Invalid request type']);
        exit();
    }
    
    $interview_id = $this->input->post('interview_id');
    
    if (empty($interview_id)) {
        echo json_encode(['success' => false, 'message' => 'Interview ID required']);
        exit();
    }
    // Get interview details with branch filter
        $this->db->select('i.*, oa.*, b.mobileno as branch_phone');
        $this->db->from('online_admission_interviews i');
        $this->db->join('online_admission oa', 'oa.id = i.admission_id', 'left');
        $this->db->join('branch b', 'b.id = i.branch_id', 'left');
        $this->db->where('i.id', $interview_id);

        // Apply branch filter
        if (!is_superadmin_loggedin()) {
            $this->db->where('i.branch_id', $this->get_validated_branch_id());
        }
    
    $interview = $this->db->get()->row_array();
    
    if (empty($interview)) {
        echo json_encode(['success' => false, 'message' => 'Interview not found or access denied']);
        exit();
    }
    
    // Get branch ID from interview (more reliable)
    $branchID = $interview['branch_id'];
    
    // Check SMS credits
    $message = "Reminder: Interview for " . $interview['first_name'] . " " . $interview['last_name'] . 
           " tomorrow at " . date('h:i A', strtotime($interview['interview_time'])) . 
           ". Location: " . $interview['location'] . ". Contact: " . $interview['branch_phone'];
    
    $required_credits = $this->calculate_admission_sms_cost($message, 1);
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branchID);
    
    if ($current_credits < $required_credits) {
        echo json_encode([
            'success' => false, 
            'message' => "Insufficient SMS credits. Needed: {$required_credits}, Available: {$current_credits}"
        ]);
        exit();
    }
    
    // Send SMS
    $mobile = $interview['grd_mobile_no'] ?: $interview['mobile_no'];
    if (empty($mobile)) {
        echo json_encode(['success' => false, 'message' => 'No mobile number available']);
        exit();
    }
    
    $this->load->library('bulksmsbd', ['branch_id' => $branchID], 'sms_lib');
    $response = $this->sms_lib->send($mobile, $message);
    
    if ($this->is_successful_sms_response($response)) {
        // Reserve and use credits properly
        $campaign_name = "Interview Reminder - " . $interview['first_name'] . ' ' . $interview['last_name'];
        $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
            $branchID, 
            $required_credits, 
            $campaign_name, 
            null
        );
        
        if ($reservation_id) {
            // Create SMS record
            $sms_data = [
                'campaign_name' => $campaign_name,
                'message' => $message,
                'message_type' => 1,
                'recipient_type' => 11,
                'recipients_details' => json_encode([
                    'interview_id' => $interview_id,
                    'template_id' => 0,
                    'reservation_id' => $reservation_id
                ]),
                'additional' => json_encode([[
                    'mobile' => $mobile,
                    'name' => $interview['guardian_name'],
                    'type' => 'parent'
                ]]),
                'schedule_time' => date('Y-m-d H:i:s'),
                'posting_status' => 2,
                'total_thread' => 1,
                'successfully_sent' => 1,
                'sms_gateway' => 'bulksmsbd',
                'credits_used' => $required_credits,
                'branch_id' => $branchID,
                'duplicate_hash' => md5('reminder_' . $interview_id . '_' . date('Y-m-d')),
                'send_type' => 'immediate',
                'transaction_id' => 'REM_' . $interview_id . '_' . time(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('bulk_sms_email', $sms_data);
            $message_id = $this->db->insert_id();
            
            // Update reservation and mark as used
            $this->sendsmsmail_model->mark_reservation_used($message_id, $required_credits);
        }
        
        // Update interview
        $this->db->where('id', $interview_id);
        $this->db->update('online_admission_interviews', [
            'reminder_sent' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Log communication
        $this->log_interview_communication($interview_id, 'sms', 'to_parent', 'sent',
            "Reminder sent for interview", $mobile);
        
        echo json_encode(['success' => true, 'message' => 'Reminder sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send reminder. Gateway response: ' . substr($response, 0, 100)]);
    }
}

/**
 * Update interview via AJAX
 */
public function update_interview_ajax($interview_id)
{
    if (!$this->input->is_ajax_request()) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        return;
    }

    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    if (empty($interview)) {
        // Handle error
    }

    $modify_check = $this->can_modify_admission($interview['admission_id']);
    if (!$modify_check['can_modify']) {
        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => $modify_check['message']]);
        } else {
            set_alert('error', $modify_check['message']);
            redirect(base_url('online_admission/interviews'));
        }
        return;
    }
    
    $status = $this->input->post('status');
    $outcome = $this->input->post('outcome');
    
    if (empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Status required']);
        return;
    }
    
    $this->db->where('id', $interview_id);
    $this->db->update('online_admission_interviews', [
        'status' => $status,
        'outcome' => $outcome ?: 'pending',
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Updated']);
}

public function reschedule_interview($interview_id)
{
    if (!get_permission('online_admission', 'is_edit')) {
        access_denied();
    }

    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    if (empty($interview)) {
        // Handle error
    }

    $modify_check = $this->can_modify_admission($interview['admission_id']);
    if (!$modify_check['can_modify']) {
        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => $modify_check['message']]);
        } else {
            set_alert('error', $modify_check['message']);
            redirect(base_url('online_admission/interviews'));
        }
        return;
    }
    
    $branchID = $this->application_model->get_branch_id();
    // Add at the beginning of each method:
    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    $admission = $this->db->where('id', $interview['admission_id'])->get('online_admission')->row_array();

    if (in_array($admission['status'], [2, 3])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot modify interview. Admission has been ' . 
                        ($admission['status'] == 2 ? 'APPROVED' : 'DECLINED') . '.'
        ]);
        exit();
    }
    
    // Get current interview
    $this->db->where('id', $interview_id); // WAS: $id
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $interview = $this->db->get('online_admission_interviews')->row_array();
    
    if (empty($interview)) {
        set_alert('error', 'Interview not found');
        redirect(base_url('online_admission/interviews'));
    }
    
    $this->db->trans_start();
    
    try {
        // FIXED INTERVIEWER LOGIC
        $update_data = [
            'interview_date' => $this->input->post('interview_date'),
            'interview_time' => $this->input->post('interview_time'),
            'status' => 'rescheduled',
            'notes' => ($interview['notes'] ?? '') . "\n\n[RESCHEDULED] " . date('d M Y H:i') . 
                      ": " . $this->input->post('reschedule_reason'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // ALWAYS check for interviewer_id in POST, not just checkbox
        $new_interviewer_id = $this->input->post('interviewer_id');
        
        // Only update interviewer if a valid ID is provided AND it's different from current
        if (!empty($new_interviewer_id) && is_numeric($new_interviewer_id)) {
            $new_interviewer_id = (int)$new_interviewer_id;
            
            // Verify interviewer exists in same branch
            $interviewer_exists = $this->db->where('id', $new_interviewer_id)
                                         ->where('branch_id', $interview['branch_id'])
                                         ->get('staff')
                                         ->num_rows();
            
            if ($interviewer_exists) {
                $update_data['interviewer_id'] = $new_interviewer_id;
                
                // Get interviewer name for logging
                $interviewer = $this->db->select('name')
                                    ->where('id', $new_interviewer_id)
                                    ->get('staff')
                                    ->row();
                
                if ($interviewer) {
                    $update_data['notes'] .= "\nInterviewer changed to: " . $interviewer->name;
                }
            } else {
                // log_message('warning', 'Interviewer ID ' . $new_interviewer_id . 
                  //         ' not found in branch ' . $interview['branch_id']);
            }
        }
        
        $this->db->where('id', $interview_id);
        $this->db->update('online_admission_interviews', $update_data);
        
        // Send reschedule SMS
        $admission = $this->db->where('id', $interview['admission_id'])->get('online_admission')->row_array();
        $branch_id_to_use = $admission['branch_id'] ?? $interview['branch_id'] ?? $branchID;
        
        // Get OLD date/time BEFORE updating
        $old_date = $interview['interview_date'];
        $old_time = $interview['interview_time'];
        $reason = $this->input->post('reschedule_reason') ?? 'Rescheduled by admissions office';

        $this->load->model('sms_model');
       $sms_result = $this->sms_model->sendInterviewReschedule($interview_id, $admission, $branch_id_to_use, $old_date,$old_time, $reason);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        // Prepare response message
        $message = 'Interview rescheduled successfully';
        
        if (isset($update_data['interviewer_id']) && $update_data['interviewer_id'] != $interview['interviewer_id']) {
            $message .= ' (Interviewer updated)';
        }
        
        if ($sms_result['success'] ?? false) {
            $message .= '. SMS notification sent.';
            set_alert('success', $message);
        } else {
            $message .= '. SMS notification failed: ' . ($sms_result['message'] ?? 'Unknown error');
            set_alert('warning', $message);
        }
        
        redirect(base_url('online_admission/interview/' . $interview_id));
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        set_alert('error', 'Error: ' . $e->getMessage());
        redirect(base_url('online_admission/interview/' . $interview_id));
    }
}
/**
 * Handle no-show interview with options
 */
public function handle_no_show($interview_id)
{
    if (!get_permission('online_admission', 'is_edit')) {
        access_denied();
    }

    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    if (empty($interview)) {
        // Handle error
    }

    $modify_check = $this->can_modify_admission($interview['admission_id']);
    if (!$modify_check['can_modify']) {
        if ($this->input->is_ajax_request()) {
            echo json_encode(['success' => false, 'message' => $modify_check['message']]);
        } else {
            set_alert('error', $modify_check['message']);
            redirect(base_url('online_admission/interviews'));
        }
        return;
    }
        
    if ($_POST) {
        $action = $this->input->post('no_show_action');
        
        $this->db->trans_start();
        
        try {
            // Get interview details
            $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
            
            if (empty($interview)) {
                throw new Exception('Interview not found');
            }
            
            $admission_id = $interview['admission_id'];
            $branchID = $interview['branch_id'];
            
            // Update interview status to no_show
            $this->db->where('id', $interview_id);
            $this->db->update('online_admission_interviews', [
                'status' => 'no_show',
                'notes' => $interview['notes'] . "\n\n[NO-SHOW] " . date('d M Y H:i') . 
                          ": Candidate did not attend the interview.",
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($action == 'reschedule') {
                // Update admission status back to interview scheduled
                $this->db->where('id', $admission_id);
                $this->db->update('online_admission', ['status' => 4]);
                
                // Create new interview record
                $new_interview_date = $this->input->post('reschedule_date');
                $new_interview_time = $this->input->post('reschedule_time');
                
                if (empty($new_interview_date) || empty($new_interview_time)) {
                    throw new Exception('Reschedule date and time are required');
                }
                
                $new_interview_data = [
                    'admission_id' => $admission_id,
                    'interview_date' => $new_interview_date,
                    'interview_time' => $new_interview_time,
                    'interview_type' => $interview['interview_type'],
                    'interviewer_id' => $interview['interviewer_id'],
                    'location' => $interview['location'],
                    'status' => 'scheduled',
                    'outcome' => 'pending',
                    'notes' => "Rescheduled due to no-show on " . date('d M Y', strtotime($interview['interview_date'])),
                    'created_by' => $this->session->userdata('loggedin_userid'),
                    'branch_id' => $branchID,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->insert('online_admission_interviews', $new_interview_data);
                $new_interview_id = $this->db->insert_id();
                
                // Send reschedule SMS
                $admission = $this->db->where('id', $admission_id)->get('online_admission')->row_array();
                $this->load->model('sms_model');
                
                // Use template 12 for reschedule notification
                $sms_data = [
                    'old_date' => date('d M Y', strtotime($interview['interview_date'])),
                    'old_time' => date('h:i A', strtotime($interview['interview_time'])),
                    'new_date' => date('d M Y', strtotime($new_interview_date)),
                    'new_time' => date('h:i A', strtotime($new_interview_time)),
                    'reason' => 'No-show reschedule'
                ];
                
                // Send using Sms_model's interview reschedule method (template 18)
                $sms_result = $this->sms_model->sendInterviewReschedule(
                    $new_interview_id, 
                    $admission, 
                    $branchID, 
                    $interview['interview_date'], // Old date
                    $interview['interview_time'], // Old time
                    'No-show reschedule'
                );
                
                if (!$sms_result['success']) {
                    // log_message('error', 'No-show reschedule SMS failed: ' . $sms_result['message']);
                }
                
                set_alert('success', 'Interview marked as no-show and rescheduled. ' . 
                         ($sms_result['success'] ? 'Notification sent.' : 'Notification failed.'));
                         
            } elseif ($action == 'decline') {
                // Decline admission due to no-show
                $this->db->where('id', $admission_id);
                $this->db->update('online_admission', ['status' => 3]); // Declined
                
                // Send decline SMS using template 15 (post-interview decline)
                $this->load->model('sms_model');
                $sms_result = $this->sms_model->sendAdmissionOutcome(
                    $admission_id,
                    15, // Template ID for post-interview decline
                    $branchID,
                    [], // student_data
                    []  // parent_data
                );
                
                set_alert('warning', 'Admission declined due to no-show. ' . 
                         ($sms_result['success'] ? 'Notification sent.' : 'Notification failed.'));
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            redirect(base_url('online_admission/interview/' . $interview_id));
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            set_alert('error', $e->getMessage());
            redirect(base_url('online_admission/interview/' . $interview_id));
        }
    }
}

private function send_interview_followup($interview_id, $admission, $branch)
{
    // log_message('debug', 'send_interview_followup called for interview ID: ' . $interview_id);
    
    // Get SMS template for follow-up (template_id = 13 if exists)
    $template = $this->db->get_where('sms_template_details', [
        'template_id' => 13, // Follow-up template
        'branch_id' => $branch['id']
    ])->row_array();
    
    if (empty($template)) {
        // log_message('debug', 'Follow-up template not found, using default');
        return $this->send_interview_followup_default($interview_id, $admission, $branch);
    }
    
    // Get interview details
    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    
    if (empty($interview)) {
        // log_message('error', 'Interview not found: ' . $interview_id);
        return ['success' => false, 'message' => 'Interview not found'];
    }
    
    // Get branch details fresh with phone number
    $branch_details = $this->db->select('name, mobileno')->where('id', $branch['id'])->get('branch')->row_array();
    
    // Prepare message with ALL placeholders
    $message = $template['template_body'];
    $message = str_replace('{guardian_name}', $admission['guardian_name'], $message);
    $message = str_replace('{student_name}', $admission['first_name'] . ' ' . $admission['last_name'], $message);
    $message = str_replace('{interview_date}', date('d M Y', strtotime($interview['interview_date'])), $message);
    $message = str_replace('{school_name}', $branch_details['name'], $message);
    $message = str_replace('{school_phone}', $branch_details['mobileno'] ?? 'School Office', $message);
    
    // DEBUG: Log the message
    // log_message('debug', 'Follow-up SMS message: ' . $message);
    
    // Determine recipient
    $recipient_mobile = $admission['grd_mobile_no'] ?: $admission['mobile_no'];
    
    if (empty($recipient_mobile)) {
        // log_message('error', 'No mobile number available for admission ID: ' . $admission['id']);
        return ['success' => false, 'message' => 'No mobile number available'];
    }
    
    // Clean mobile number
    $recipient_mobile = preg_replace('/[^0-9]/', '', $recipient_mobile);
    
    // Check SMS credits
    $required_credits = $this->calculate_admission_sms_cost($message, 1);
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branch['id']);
    
    // log_message('debug', 'SMS credit check - Required: ' . $required_credits . ', Available: ' . $current_credits);
    
    if ($current_credits < $required_credits) {
        $error_message = "Insufficient SMS credits. Needed: {$required_credits}, Available: {$current_credits}";
        // log_message('error', $error_message);
        return ['success' => false, 'message' => $error_message];
    }
    
    // Send SMS using existing system
    try {
        $this->load->library('bulksmsbd', ['branch_id' => $branch['id']], 'sms_lib');
        
        // log_message('debug', 'Sending follow-up SMS to: ' . $recipient_mobile);
        $response = $this->sms_lib->send($recipient_mobile, $message);
        
        $success = $this->is_successful_sms_response($response);
        
        if ($success) {
            // Update interview record
            $this->db->where('id', $interview_id);
            $this->db->update('online_admission_interviews', [
                'followup_sent' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Log communication
            $this->log_interview_communication($interview_id, 'sms', 'to_parent', 'sent',
                "Follow-up message sent after interview completion", $recipient_mobile);
            
            // log_message('info', 'Follow-up SMS sent successfully to ' . $recipient_mobile);
            
            return [
                'success' => true,
                'message' => 'Follow-up SMS sent successfully',
                'response' => $response
            ];
            
        } else {
            // log_message('error', 'Follow-up SMS failed for interview ID: ' . $interview_id . ' - Response: ' . $response);
            
            return [
                'success' => false,
                'message' => 'Follow-up SMS sending failed',
                'response' => $response
            ];
        }
        
    } catch (Exception $e) {
        // log_message('error', 'Exception in send_interview_followup: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}

/**
 * Default follow-up SMS (if template not configured)
 */
private function send_interview_followup_default($interview_id, $admission, $branch)
{
    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    
    $message = "Dear " . $admission['guardian_name'] . ",\n";
    $message .= "Thank you for attending the interview for " . $admission['first_name'] . " " . $admission['last_name'] . ".\n";
    $message .= "The admissions committee will review and contact you within 48 hours.\n";
    $message .= "For queries: " . ($branch['mobileno'] ?? 'School Office');
    
    // Send SMS
    $recipient_mobile = $admission['grd_mobile_no'] ?: $admission['mobile_no'];
    $recipient_mobile = preg_replace('/[^0-9]/', '', $recipient_mobile);
    
    if (!empty($recipient_mobile)) {
        $this->load->library('bulksmsbd', ['branch_id' => $branch['id']], 'sms_lib');
        $response = $this->sms_lib->send($recipient_mobile, $message);
        
        if ($this->is_successful_sms_response($response)) {
            // Update interview record
            $this->db->where('id', $interview_id);
            $this->db->update('online_admission_interviews', [
                'followup_sent' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Log communication
            $this->log_interview_communication($interview_id, 'sms', 'to_parent', 'sent',
                "Default follow-up message sent", $recipient_mobile);
            
            return ['success' => true, 'message' => 'Default follow-up SMS sent'];
        }
    }
    
    return ['success' => false, 'message' => 'Failed to send default follow-up SMS'];
}

/**
 * Check SMS credits for interview reschedule
 */
private function check_interview_reschedule_sms_credits($branchID, $interview)
{
    // Get reschedule SMS template (template_id = 18)
    $template = $this->db->get_where('sms_template_details', [
        'template_id' => 18, // Reschedule template
        'branch_id' => $branchID
    ])->row_array();
    
    if (empty($template)) {
        return ['success' => false, 'message' => 'Reschedule SMS template not configured'];
    }
    
    // Get admission details
    $admission = $this->db->where('id', $interview['admission_id'])->get('online_admission')->row_array();
    
    // Prepare sample message
    $message = $template['template_body'];
    $message = str_replace('{guardian_name}', $admission['guardian_name'], $message);
    $message = str_replace('{student_name}', $admission['first_name'] . ' ' . $admission['last_name'], $message);
    $message = str_replace('{old_date}', date('d M Y', strtotime($interview['interview_date'])), $message);
    $message = str_replace('{old_time}', date('h:i A', strtotime($interview['interview_time'])), $message);
    
    $recipient_count = 0;
    if ($template['notify_student'] == 1 && !empty($admission['mobile_no'])) $recipient_count++;
    if ($template['notify_parent'] == 1 && !empty($admission['grd_mobile_no'])) $recipient_count++;
    
    if ($recipient_count == 0) {
        return ['success' => false, 'message' => 'No recipients configured'];
    }
    
    $required_credits = $this->calculate_admission_sms_cost($message, $recipient_count);
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branchID);
    
    if ($current_credits < $required_credits) {
        return [
            'success' => false,
            'message' => "Insufficient SMS credits. Needed: {$required_credits}, Available: {$current_credits}"
        ];
    }
    
    return [
        'success' => true,
        'required_credits' => $required_credits,
        'template' => $template
    ];
}

/**
 * Send interview reschedule SMS
 */
private function send_interview_reschedule_sms($interview_id, $admission, $branch, $reservation_id)
{
    // Get updated interview details
    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    
    // Get template
    $template = $this->db->get_where('sms_template_details', [
        'template_id' => 18,
        'branch_id' => $branch['id']
    ])->row_array();
    
    if (empty($template)) {
        return ['success' => false, 'message' => 'Reschedule template not configured'];
    }
    
    // Prepare message
    $message = $template['template_body'];
    $message = str_replace('{guardian_name}', $admission['guardian_name'], $message);
    $message = str_replace('{student_name}', $admission['first_name'] . ' ' . $admission['last_name'], $message);
    $message = str_replace('{old_date}', date('d M Y', strtotime($admission['interview_date'])), $message);
    $message = str_replace('{old_time}', date('h:i A', strtotime($admission['interview_time'])), $message);
    $message = str_replace('{new_date}', date('d M Y', strtotime($interview['interview_date'])), $message);
    $message = str_replace('{new_time}', date('h:i A', strtotime($interview['interview_time'])), $message);
    $message = str_replace('{location}', $interview['location'], $message);
    $message = str_replace('{school_name}', $branch['name'], $message);
    
    // Determine recipient
    $recipient_mobile = $admission['grd_mobile_no'] ?: $admission['mobile_no'];
    $recipient_mobile = preg_replace('/[^0-9]/', '', $recipient_mobile);
    
    if (empty($recipient_mobile)) {
        return ['success' => false, 'message' => 'No mobile number available'];
    }
    
    // Send SMS
    $this->load->library('bulksmsbd', ['branch_id' => $branch['id']], 'sms_lib');
    $response = $this->sms_lib->send($recipient_mobile, $message);
    
    $success = $this->is_successful_sms_response($response);
    
    if ($success) {
        // Create SMS record
        $sms_data = [
            'campaign_name' => "Interview Reschedule - " . $admission['first_name'] . ' ' . $admission['last_name'],
            'message' => $message,
            'message_type' => 1,
            'recipient_type' => 11,
            'recipients_details' => json_encode([
                'interview_id' => $interview_id,
                'template_id' => 18,
                'reservation_id' => $reservation_id
            ]),
            'additional' => json_encode([[
                'mobile' => $recipient_mobile,
                'name' => $admission['guardian_name'],
                'type' => 'parent'
            ]]),
            'schedule_time' => date('Y-m-d H:i:s'),
            'posting_status' => 2,
            'total_thread' => 1,
            'successfully_sent' => 1,
            'sms_gateway' => 'bulksmsbd',
            'credits_used' => $this->calculate_admission_sms_cost($message, 1),
            'branch_id' => $branch['id'],
            'duplicate_hash' => md5('reschedule_' . $interview_id),
            'send_type' => 'immediate',
            'transaction_id' => 'RSCH_' . $interview_id . '_' . time(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('bulk_sms_email', $sms_data);
        $message_id = $this->db->insert_id();
        
        // Update reservation
        $this->db->where('id', $reservation_id);
        $this->db->update('sms_credit_reservations', [
            'message_id' => $message_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Log communication
        $this->log_interview_communication($interview_id, 'sms', 'to_parent', 'sent', 
            "Reschedule notification sent", $recipient_mobile);
        
        return ['success' => true, 'message' => 'Reschedule SMS sent'];
    }
    
    return ['success' => false, 'message' => 'SMS sending failed: ' . $response];
}

/**
 * Controlled logging - only in development
 */
private function debug_log($message, $data = null, $level = 'debug')
{
    if (ENVIRONMENT === 'development') {
        $log_message = $message;
        if ($data) {
            $log_message .= ' - ' . print_r($data, true);
        }
        // log_message($level, $log_message);
    }
}

}
