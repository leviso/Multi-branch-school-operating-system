<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Birthday.php
 * @copyright : Reserved Synobix Team
 */

class Birthday extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('birthday_model');
        $this->load->model('sms_model');
        $this->load->model('sendsmsmail_model');
    }

    public function index()
    {
        redirect(base_url('birthday/student'));
    }

    /* showing student list by birthday */
    public function student()
    {
        // check access permission
        if (!get_permission('student_birthday_wishes', 'is_view')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();
        if (isset($_POST['search'])) {
            $daterange = explode(' - ', $this->input->post('daterange'));
            $start = date("Y-m-d", strtotime($daterange[0]));
            $end = date("Y-m-d", strtotime($daterange[1]));
            $this->data['students'] = $this->birthday_model->getStudentListByBirthday($branchID, $start, $end);
        }
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('student') . " " . translate('birthday') . " " . translate('list');
        $this->data['main_menu'] = 'sendsmsmail';
        $this->data['sub_page'] = 'birthday/student';
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/daterangepicker/daterangepicker.css',
            ),
            'js' => array(
                'vendor/moment/moment.js',
                'vendor/daterangepicker/daterangepicker.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }

     

    /* showing staff list by birthday */
    public function staff()
    {
        // check access permission
        if (!get_permission('staff_birthday_wishes', 'is_view')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();
        if (isset($_POST['search'])) {
            $daterange = explode(' - ', $this->input->post('daterange'));
            $start = date("Y-m-d", strtotime($daterange[0]));
            $end = date("Y-m-d", strtotime($daterange[1]));
            $this->data['students'] = $this->birthday_model->getStaffListByBirthday($branchID, $start, $end);
        }
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('staff') . " " . translate('birthday') . " " . translate('list');
        $this->data['main_menu'] = 'sendsmsmail';
        $this->data['sub_page'] = 'birthday/staff';
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/daterangepicker/daterangepicker.css',
            ),
            'js' => array(
                'vendor/moment/moment.js',
                'vendor/daterangepicker/daterangepicker.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }


    public function studentWishes()
    {
        if ($_POST) {
            $status = 'success';
            $message = "All birthday wishes sent via SMS.";
            
            if (get_permission('student_birthday_wishes', 'is_view')) {
                $arrayID = $this->input->post('array_id');
                $branchID = $this->input->post('branch_id');
                
                if (!empty($arrayID) && !empty($branchID)) {
                    // Create bulk SMS entry
                    $result = $this->create_birthday_sms_entry('student', $arrayID, $branchID);
                    
                    if ($result['success']) {
                        $message = $result['message'];
                        $status = 'success';
                    } else {
                        $message = $result['message'];
                        $status = 'error';
                    }
                } else {
                    $message = "No students selected or branch not specified.";
                    $status = 'error';
                }
            } else {
                $message = translate('access_denied');
                $status = 'error';
            }
            echo json_encode(array('status' => $status, 'message' => $message));
        }
    }

    public function staffWishes()
    {
        if ($_POST) {
            $status = 'success';
            $message = "All birthday wishes sent via SMS.";
            
            if (get_permission('staff_birthday_wishes', 'is_view')) {
                $arrayID = $this->input->post('array_id');
                $branchID = $this->input->post('branch_id');
                
                if (!empty($arrayID) && !empty($branchID)) {
                    // Create bulk SMS entry
                    $result = $this->create_birthday_sms_entry('staff', $arrayID, $branchID);
                    
                    if ($result['success']) {
                        $message = $result['message'];
                        $status = 'success';
                    } else {
                        $message = $result['message'];
                        $status = 'error';
                    }
                } else {
                    $message = "No staff selected or branch not specified.";
                    $status = 'error';
                }
            } else {
                $message = translate('access_denied');
                $status = 'error';
            }
            echo json_encode(array('status' => $status, 'message' => $message));
        }
    }

    /**
     * NEW METHOD: Create birthday SMS entry in bulk_sms_email
     */
    private function create_birthday_sms_entry($type, $ids, $branch_id)
    {
        try {
            // Get recipients based on type
            $recipients = [];
            
            if ($type == 'student') {
                $recipients = $this->get_student_recipients($ids, $branch_id);
                $template_id = 9; // Student birthday template
            } else {
                $recipients = $this->get_staff_recipients($ids, $branch_id);
                $template_id = 10; // Staff birthday template
            }
            
            if (empty($recipients)) {
                return ['success' => false, 'message' => 'No recipients with valid mobile numbers'];
            }
            
            // Get SMS template
            $template = $this->db->get_where('sms_template_details', [
                'template_id' => $template_id,
                'branch_id' => $branch_id
            ])->row_array();
            
            if (empty($template) || empty($template['template_body'])) {
                return ['success' => false, 'message' => 'SMS template not configured for this branch'];
            }
            
            // Prepare base message
            $base_message = $template['template_body'];
            
            // Calculate credits needed
            $credits_needed = $this->calculate_birthday_sms_cost($base_message, count($recipients));
            
            // Check credits
            $current_credits = $this->sendsmsmail_model->get_sms_credit($branch_id);
            
            if ($current_credits < $credits_needed) {
                return [
                    'success' => false, 
                    'message' => "Insufficient SMS credits. Needed: {$credits_needed}, Available: {$current_credits}"
                ];
            }

            // After calculating credits_needed

            // ========== DUPLICATE CHECK ==========
            $duplicate_hash = $this->generate_birthday_duplicate_hash($type, $ids, $branch_id);

            // Check for existing duplicate
            $this->db->where('duplicate_hash', $duplicate_hash);
            $this->db->where('DATE(created_at)', date('Y-m-d'));
            $this->db->where_in('posting_status', [0, 1, 2]); // processing, scheduled, completed
            $existing = $this->db->get('bulk_sms_email')->row();

            if ($existing) {
                return [
                    'success' => false, 
                    'message' => 'Birthday wishes already sent to these recipients today'
                ];
            }

            // ========== RESERVE CREDITS FIRST ==========
            $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
                $branch_id, 
                $credits_needed, 
                $campaign_name, 
                null // Message ID will be set after insert
            );

            if (!$reservation_id) {
                return [
                    'success' => false, 
                    'message' => 'Failed to reserve SMS credits. Please check your balance.'
                ];
            }
            
            // Create campaign name
            $campaign_name = ucfirst($type) . " Birthday Wishes - " . date('d M Y');
            
            // Store in bulk_sms_email table
            $message_data = [
                'campaign_name' => $campaign_name,
                'message' => $base_message,
                'message_type' => 1,
                'recipient_type' => ($type == 'student' ? 6 : 7),
                'recipients_details' => json_encode([
                    'type' => $type,
                    'template_id' => $template_id,
                    'notify_student' => $template['notify_student'] ?? 0,
                    'notify_parent' => $template['notify_parent'] ?? 0,
                    'recipient_ids' => $ids,
                    'reservation_id' => $reservation_id // Add reservation ID
                ]),
                'additional' => json_encode($recipients),
                'schedule_time' => date('Y-m-d H:i:s'),
                'posting_status' => 1,
                'total_thread' => count($recipients),
                'successfully_sent' => 0,
                'sms_gateway' => 'bulksmsbd',
                'credits_used' => 0,
                'branch_id' => $branch_id,
                'duplicate_hash' => $duplicate_hash, // ADD THIS LINE
                'send_type' => 'manual_birthday',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('bulk_sms_email', $message_data);
            $message_id = $this->db->insert_id();
            
            // Reserve credits
            $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
                $branch_id, 
                $credits_needed, 
                $campaign_name, 
                $message_id
            );
            
            if (!$reservation_id) {
                throw new Exception('Failed to reserve SMS credits');
            }
            
            // Send immediately instead of waiting for cron
            $send_result = $this->send_birthday_sms_immediately($message_id, $recipients, $base_message, $branch_id, $type);
            
            if ($send_result['success']) {
                // Update message status
                $status = ($send_result['sent'] == count($recipients)) ? 2 : ($send_result['sent'] > 0 ? 4 : 3);
                
                $this->db->where('id', $message_id);
                $this->db->update('bulk_sms_email', [
                    'posting_status' => $status,
                    'successfully_sent' => $send_result['sent'],
                    'credits_used' => $credits_needed,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Mark reservation as used
                $this->sendsmsmail_model->mark_reservation_used($message_id, $credits_needed);
                
                // log_message('info', "Birthday SMS sent. Type: {$type}, Sent: {$send_result['sent']}/" . count($recipients));
                
                return [
                    'success' => true, 
                    'message' => "Birthday wishes sent to {$send_result['sent']} out of " . count($recipients) . " recipients."
                ];
            } else {
                throw new Exception($send_result['message']);
            }
            
        } catch (Exception $e) {
            // log_message('error', 'Birthday SMS error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function get_student_recipients($student_ids, $branch_id)
{
    $recipients = [];
    
    if (empty($student_ids)) {
        return $recipients;
    }
    
    // Get current session ID safely
    $session_id = $this->get_session_id_safe(); // Use safe method
    
    // Get students with their details - FIXED QUERY
    $this->db->select('s.id as student_id, 
                      CONCAT(s.first_name, " ", s.last_name) as name,
                      s.mobileno, s.email, s.birthday, s.register_no,
                      s.parent_id, p.name as parent_name, p.mobileno as parent_mobile,
                      e.class_id, e.section_id, e.roll,
                      c.name as class_name, sec.name as section_name');
    $this->db->from('student s');
    $this->db->join('enroll e', "e.student_id = s.id AND e.session_id = {$session_id}", 'left');
    $this->db->join('class c', 'c.id = e.class_id', 'left');
    $this->db->join('section sec', 'sec.id = e.section_id', 'left');
    $this->db->join('parent p', 'p.id = s.parent_id', 'left');
    $this->db->where_in('s.id', $student_ids);
    $this->db->where('e.branch_id', $branch_id);
    $students = $this->db->get()->result_array();
    
    // Get SMS template to check who to notify
    $template = $this->db->get_where('sms_template_details', [
        'template_id' => 9,
        'branch_id' => $branch_id
    ])->row_array();
    
    foreach ($students as $student) {
        // Notify student
        if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
            $recipients[] = [
                'id' => $student['student_id'],
                'name' => $student['name'],
                'mobileno' => $student['mobileno'],
                'email' => $student['email'],
                'type' => 'student',
                'birthday' => $student['birthday'],
                'register_no' => $student['register_no'],
                'class_name' => $student['class_name'] ?? '',
                'section_name' => $student['section_name'] ?? '',
                'roll' => $student['roll'] ?? ''
            ];
        }
        
        // Notify parent
        if ($template['notify_parent'] == 1 && !empty($student['parent_mobile'])) {
            $recipients[] = [
                'id' => $student['student_id'] . '_parent',
                'name' => $student['parent_name'],
                'mobileno' => $student['parent_mobile'],
                'email' => '',
                'type' => 'parent',
                'student_name' => $student['name'],
                'student_birthday' => $student['birthday'],
                'student_register_no' => $student['register_no'] ?? '',
                'class_name' => $student['class_name'] ?? '',
                'section_name' => $student['section_name'] ?? '',
                'roll' => $student['roll'] ?? ''
            ];
        }
    }
    
    return $recipients;
}

/**
 * Get session ID safely without triggering ambiguous queries
 */
private function get_session_id_safe()
{
    // Direct query without any joins
    $sql = "SELECT session_id FROM global_settings WHERE id = 1 LIMIT 1";
    $query = $this->db->query($sql);
    
    if ($query->num_rows() > 0) {
        return $query->row()->session_id;
    }
    
    return 1; // Default fallback
}

    /**
 * CORRECTED: Get staff recipients with their details
 * Based on your actual table structure
 */
private function get_staff_recipients($staff_ids, $branch_id)
{
    $recipients = [];
    
    if (empty($staff_ids)) {
        return $recipients;
    }
    
    // CORRECTED QUERY based on your table structure:
    // - staff.designation is an INT (not designation_id)
    // - Need to join with staff_designation to get name
    $this->db->select('s.id, s.name, s.mobileno, s.email, s.birthday, s.joining_date, s.designation, d.name as designation_name');
    $this->db->from('staff s');
    $this->db->join('staff_designation d', 'd.id = s.designation', 'left'); // Join on designation column (not designation_id)
    $this->db->where_in('s.id', $staff_ids);
    $this->db->where('s.branch_id', $branch_id);
    $staff_members = $this->db->get()->result_array();
    
    if (empty($staff_members)) {
        // log_message('debug', 'No staff found for IDs: ' . implode(',', $staff_ids));
        return $recipients;
    }
    
    // Get role for each staff from login_credential
    foreach ($staff_members as $staff) {
        if (!empty($staff['mobileno'])) {
            // Get role from login_credential
            $role_info = $this->db->select('role')
                                 ->from('login_credential')
                                 ->where('user_id', $staff['id'])
                                 ->where('role !=', 6)
                                 ->where('role !=', 7)
                                 ->get()
                                 ->row_array();
            
            $role_id = $role_info['role'] ?? 0;
            $role_name = $this->get_role_name($role_id);
            
            $recipients[] = [
                'id' => $staff['id'],
                'name' => $staff['name'],
                'mobileno' => $staff['mobileno'],
                'email' => $staff['email'],
                'type' => 'staff',
                'birthday' => $staff['birthday'],
                'joining_date' => $staff['joining_date'],
                'designation' => $staff['designation_name'] ?? 'N/A',
                'role' => $role_name
            ];
        }
    }
    
    return $recipients;
}

    /**
     * Get role name from role ID
     */
    private function get_role_name($role_id)
    {
        $role_names = [
            1 => 'Super Admin',
            2 => 'Admin',
            3 => 'Accountant',
            4 => 'Librarian',
            5 => 'Teacher',
            6 => 'Parent',
            7 => 'Student'
        ];
        
        return isset($role_names[$role_id]) ? $role_names[$role_id] : 'N/A';
    }

    /**
     * Calculate SMS cost for birthday messages
     */
    private function calculate_birthday_sms_cost($base_message, $recipient_count)
    {
        $message_length = mb_strlen($base_message, 'UTF-8');
        
        // Check if Unicode
        $is_unicode = false;
        for ($i = 0; $i < $message_length; $i++) {
            $char = mb_substr($base_message, $i, 1, 'UTF-8');
            if (ord($char) > 127) {
                $is_unicode = true;
                break;
            }
        }
        
        $chars_per_sms = $is_unicode ? 70 : 160;
        $sms_parts = ceil($message_length / $chars_per_sms);
        $credits_per_part = $is_unicode ? 2 : 1;
        
        return $sms_parts * $recipient_count * $credits_per_part;
    }

    private function send_birthday_sms_immediately($message_id, $recipients, $base_message, $branch_id, $type)
{
    try {

        // CHECK: Verify message hasn't already been sent
        $this->db->select('posting_status, credits_used');
        $this->db->from('bulk_sms_email');
        $this->db->where('id', $message_id);
        $message_check = $this->db->get()->row_array();
        
        if (empty($message_check)) {
            throw new Exception("Message {$message_id} not found");
        }
        
        // If already completed/failed, don't resend
        if ($message_check['posting_status'] != 1 && $message_check['posting_status'] != 0) {
            // log_message('warning', "Message {$message_id} already processed with status {$message_check['posting_status']}. Skipping.");
            return [
                'success' => false,
                'message' => 'Message already processed',
                'sent' => 0,
                'failed' => 0,
                'total' => count($recipients)
            ];
        }
        
        $sent_count = 0;
        $failed_count = 0;
        
        $this->load->library('bulksmsbd', ['branch_id' => $branch_id], 'sms_lib');
        
        foreach ($recipients as $recipient) {
            // Personalize message
            $personalized_msg = $base_message;
            
            if ($recipient['type'] == 'student') {
                $personalized_msg = str_replace('{name}', $recipient['name'], $personalized_msg);
                $personalized_msg = str_replace('{register_no}', $recipient['register_no'] ?? '', $personalized_msg);
                $personalized_msg = str_replace('{birthday}', $recipient['birthday'] ?? '', $personalized_msg);
                $personalized_msg = str_replace('{class}', $recipient['class_name'] ?? '', $personalized_msg);
                $personalized_msg = str_replace('{section}', $recipient['section_name'] ?? '', $personalized_msg);
                $personalized_msg = str_replace('{roll}', $recipient['roll'] ?? '', $personalized_msg);
            } elseif ($recipient['type'] == 'parent') {
                // For parent, use student details in the message
                $personalized_msg = str_replace('{name}', $recipient['name'], $personalized_msg);
                $personalized_msg = str_replace('{register_no}', $recipient['student_register_no'] ?? '', $personalized_msg);
                $personalized_msg = str_replace('{birthday}', $recipient['student_birthday'] ?? '', $personalized_msg);
                $personalized_msg = str_replace('{class}', $recipient['class_name'] ?? '', $personalized_msg);
                $personalized_msg = str_replace('{section}', $recipient['section_name'] ?? '', $personalized_msg);
                $personalized_msg = str_replace('{roll}', $recipient['roll'] ?? '', $personalized_msg);
            } elseif ($recipient['type'] == 'staff') {
                $personalized_msg = str_replace('{name}', $recipient['name'], $personalized_msg);
                $personalized_msg = str_replace('{joining_date}', $recipient['joining_date'] ?? '', $personalized_msg);
                $personalized_msg = str_replace('{birthday}', $recipient['birthday'] ?? '', $personalized_msg);
            }
            
            // Clean mobile number
            $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
            
            if (!empty($mobile) && strlen($mobile) >= 10) {
                // Send SMS
                $response = $this->sms_lib->send($mobile, $personalized_msg);
                
                // Log delivery
                $this->log_delivery($message_id, $recipient, $response, $branch_id);
                
                // Check if successful
                if ($this->is_successful_response($response)) {
                    $sent_count++;
                    // log_message('info', "Birthday SMS sent to {$mobile}");
                } else {
                    $failed_count++;
                    // log_message('error', "Birthday SMS failed for {$mobile}: " . substr($response, 0, 200));
                }
            } else {
                $failed_count++;
                // log_message('warning', "Invalid mobile number for {$recipient['name']}: {$recipient['mobileno']}");
            }
            
            // Small delay to avoid rate limiting
            usleep(50000); // 0.05 second
        }
        
        return [
            'success' => true,
            'sent' => $sent_count,
            'failed' => $failed_count,
            'total' => count($recipients)
        ];
        
    } catch (Exception $e) {
        // log_message('error', 'Error sending birthday SMS: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
    /**
     * Check if SMS response is successful
     */
    private function is_successful_response($response)
    {
        if (empty($response)) return false;
        
        $response_array = json_decode($response, true);
        if (is_array($response_array) && isset($response_array['responses'][0])) {
            $resp = $response_array['responses'][0];
            return (isset($resp['response-code']) && $resp['response-code'] == 200);
        }
        
        return (strpos($response, 'Success') !== false || 
                strpos($response, 'success') !== false ||
                strpos($response, '200') !== false);
    }

    /**
     * Log delivery to database
     */
    private function log_delivery($message_id, $recipient, $response, $branch_id)
    {
        $status = $this->is_successful_response($response) ? 'sent' : 'failed';
        
        $log_data = [
            'message_id' => $message_id,
            'recipient_contact' => $recipient['mobileno'],
            'status' => $status,
            'gateway_response' => substr($response, 0, 500),
            'sent_at' => date('Y-m-d H:i:s'),
            'branch_id' => $branch_id
        ];
        
        $this->db->insert('sms_email_delivery_logs', $log_data);
    }

    /**
     * Check SMS credits before sending (AJAX endpoint)
     */
    public function check_credits()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $type = $this->input->post('type');
        $branch_id = $this->input->post('branch_id');
        $recipient_count = (int)$this->input->post('recipient_count');
        $recipient_ids = $this->input->post('recipient_ids');
        
        // Validate inputs
        if (empty($branch_id) || $recipient_count < 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid parameters'
            ]);
            return;
        }
        
        // Get SMS template for this type
        $template_id = ($type == 'student') ? 9 : 10;
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => $template_id,
            'branch_id' => $branch_id
        ])->row_array();
        
        if (empty($template)) {
            echo json_encode([
                'success' => false,
                'message' => 'SMS template not configured for this branch'
            ]);
            return;
        }
        
        // Get base message
        $base_message = $template['template_body'];
        
        // Calculate estimated cost (simplified - 1 credit per recipient)
        $estimated_cost = $recipient_count; // Simplified calculation
        
        // For students, check if parents should also receive
        if ($type == 'student' && $template['notify_parent'] == 1) {
            // Estimate 50% of students have parents with mobile
            $estimated_cost = ceil($recipient_count * 1.5);
        }
        
        // Get current balance
        $current_balance = $this->sendsmsmail_model->get_sms_credit($branch_id);
        $remaining = $current_balance - $estimated_cost;
        
        echo json_encode([
            'success' => true,
            'estimated_cost' => $estimated_cost,
            'current_balance' => $current_balance,
            'remaining' => $remaining,
            'branch_id' => $branch_id
        ]);
    }
   

/**
 * Generate duplicate hash for birthday messages
 */
private function generate_birthday_duplicate_hash($type, $person_ids, $branch_id) {
    // Sort IDs for consistent hashing
    sort($person_ids);
    
    $data = [
        'type' => $type,
        'person_ids' => $person_ids,
        'branch_id' => $branch_id,
        'date' => date('Y-m-d'),
        'template_id' => ($type == 'student' ? 9 : 10)
    ];
    return md5(serialize($data));
}
}

