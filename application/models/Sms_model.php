<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Sms_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library("clickatell");
        $this->load->library("twilio");
        $this->load->library("msg91");
        $this->load->library("bulk");
        $this->load->library("textlocal");
        $this->load->library("smscountry");
        $this->load->library("bulksmsbd");
        $this->load->library("custom_sms");
        $this->load->model('sendsmsmail_model');
        $this->load->model('application_model');
    }

    // common function for sending sms
public function send_sms($data = '', $id = '')
{
    $branchID = $this->application_model->get_branch_id();
    $sms_api = $this->application_model->smsServiceProvider($branchID);
    
    // Check if SMS gateway is enabled
    if ($sms_api == 'disabled') {
        // log_message('error', "SMS gateway disabled for branch {$branchID}");
        return false;
    }
    
    $template = $this->db->get_where('sms_template_details', 
        array('template_id' => $id, 'branch_id' => $branchID)
    )->row_array();
    
    if (empty($template)) {
        // log_message('error', "No SMS template found for template ID {$id}");
        return false;
    }
    
    // For fees payment (template_id = 2), use the specialized method with balance
    if ($id == 2) { // Fees payment notification
        return $this->send_fees_payment_sms_improved($data, $template, $branchID, $sms_api);
    }

    // Existing code for other template types (keep this part unchanged)
    if (($template['notify_student'] == 1 || $template['notify_parent'] == 1) && $sms_api != 'disabled') {
        $student = $this->application_model->getstudentdetails($data['student_id']);
        $text = str_replace('{name}', $student['first_name'] . ' ' . $student['last_name'], $template['template_body']);
        $text = str_replace('{register_no}', $student['register_no'], $text);
        $text = str_replace('{admission_date}', $student['admission_date'], $text);
        $text = str_replace('{class}', $student['class_name'], $text);
        $text = str_replace('{section}', $student['section_name'], $text);
        $text = str_replace('{roll}', $student['roll'], $text);

        if ($id == 2) {
            $text = str_replace('{paid_amount}', $data['amount'], $text);
            $text = str_replace('{paid_date}', _d($data['paid_date']), $text);
            // NEW: Add balance replacement
            $text = str_replace('{balance}', isset($data['balance']) ? number_format($data['balance'], 2) : '0.00', $text);
        }

        if ($id == 4 || $id == 5) {
            $exam = $this->db->select('name,term_id')->where('id', $data['exam_id'])->get('exam')->row();
            $subject_name = $this->db->select('name')->where('id', $data['subject_id'])->get('subject')->row()->name;
            if (!empty($exam->term_id)) {
                $term_name = $this->db->select('name')->where('id', $exam->term_id)->get('exam_term')->row()->name;
            }
            $text = str_replace('{exam_name}', $exam->name, $text);
            $text = str_replace('{term_name}', $term_name, $text);
            $text = str_replace('{subject}', $subject_name, $text);
            if (!empty($data['mark'])) {
                $text = str_replace('{marks}', $data['mark'], $text);
            }
        }

        if ($template['notify_student'] == 1) {
            if (!empty($student['mobileno'])) {
                $this->_send($sms_api, $student['mobileno'], $text, $template['dlt_template_id']);
            }
        }

        if ($template['notify_parent'] == 1) {
            if (!empty($student['parent_id'])) {
                $parent = $this->db->select('mobileno')->where('id', $student['parent_id'])->get('parent')->row_array();
                if (!empty($parent['mobileno'])) {
                    $this->_send($sms_api, $parent['mobileno'], $text, $template['dlt_template_id']);
                }
            }
        }
    }
    
    return true;
}

  /**
 * Improved method for sending fees payment SMS with credit checking and balance
 */
private function send_fees_payment_sms_improved($data, $template, $branchID, $sms_api)
{
    // log_message('debug', 'send_fees_payment_sms_improved() called - Data: ' . json_encode($data) . ', Branch: ' . $branchID);
    
    if (empty($data['student_id'])) {
        // log_message('error', 'No student_id in fees payment SMS data');
        return false;
    }
    
    $student = $this->application_model->getstudentdetails($data['student_id']);
    
    if (empty($student)) {
        // log_message('error', "Student not found for ID: " . $data['student_id']);
        return false;
    }
    
    // ========== CRITICAL: Calculate balance ==========
    $balance = isset($data['balance']) ? $data['balance'] : 0;
    
    // If balance not provided in data, calculate it
    if ($balance == 0 && isset($data['allocation_id']) && isset($data['type_id'])) {
        $ci =& get_instance();
        $ci->load->model('fees_model');
        $fee_balance = $ci->fees_model->getBalance($data['allocation_id'], $data['type_id']);
        if (isset($fee_balance['balance'])) {
            $balance = $fee_balance['balance'];
        }
    }
    
    // log_message('debug', 'Balance calculated: ' . $balance);
    
    // Prepare message with balance variable
    $text = str_replace('{name}', $student['first_name'] . ' ' . $student['last_name'], $template['template_body']);
    $text = str_replace('{register_no}', $student['register_no'], $text);
    $text = str_replace('{admission_date}', $student['admission_date'], $text);
    $text = str_replace('{class}', $student['class_name'], $text);
    $text = str_replace('{section}', $student['section_name'], $text);
    $text = str_replace('{roll}', $student['roll'], $text);
    $text = str_replace('{paid_amount}', isset($data['amount']) ? $data['amount'] : 0, $text);
    $text = str_replace('{paid_date}', isset($data['paid_date']) ? $data['paid_date'] : date('Y-m-d H:i:s'), $text);
    $text = str_replace('{balance}', number_format($balance, 2), $text);
    
    // log_message('debug', 'SMS message with balance: ' . substr($text, 0, 150) . '...');
    
    // Prepare recipients
    $recipients = [];
    $recipient_count = 0;
    
    // Student
    if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
        $recipients[] = [
            'name' => $student['first_name'] . ' ' . $student['last_name'],
            'mobileno' => $student['mobileno'],
            'type' => 'student'
        ];
        $recipient_count++;
    }
    
    // Parent
    if ($template['notify_parent'] == 1 && !empty($student['parent_id'])) {
        $parent = $this->db->select('id, name, mobileno')
            ->where('id', $student['parent_id'])
            ->where('branch_id', $branchID)
            ->get('parent')
            ->row_array();
        
        if (!empty($parent['mobileno'])) {
            $recipients[] = [
                'name' => $parent['name'],
                'mobileno' => $parent['mobileno'],
                'type' => 'parent'
            ];
            $recipient_count++;
        }
    }
    
    if ($recipient_count == 0) {
        // log_message('warning', "No recipients for fees payment SMS");
        return false;
    }
    
    // log_message('debug', 'Total recipients: ' . $recipient_count);
    
    // Calculate credits needed
    $credits_needed = $this->calculate_sms_cost_fees($text, $recipient_count);
    
    // Check credits
    $ci =& get_instance();
    $ci->load->model('sendsmsmail_model');
    $current_credits = $ci->sendsmsmail_model->get_sms_credit($branchID);
    
    // log_message('debug', 'Credits check - Needed: ' . $credits_needed . ', Available: ' . $current_credits);
    
    if ($current_credits < $credits_needed) {
        // log_message('error', "Insufficient credits for fees payment SMS. Needed: {$credits_needed}, Available: {$current_credits}");
        return false; // CRITICAL: Return false to prevent saving
    }
    
    // Actually send SMS
    $success_count = 0;
    $failed_recipients = [];
    
    foreach ($recipients as $recipient) {
        $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
        
        if (!empty($mobile)) {
            // log_message('debug', 'Sending SMS to: ' . $mobile . ' - Type: ' . $recipient['type']);
            
            // Send SMS using the _send method
            try {
                $response = $this->_send($sms_api, $mobile, $text, $template['dlt_template_id']);
                
                if ($this->is_successful_response($response)) {
                    $success_count++;
                    // log_message('info', 'SMS sent successfully to ' . $mobile);
                } else {
                    $failed_recipients[] = $mobile;
                    // log_message('error', 'SMS failed for ' . $mobile . ' - Response: ' . $response);
                }
                
            } catch (Exception $e) {
                $failed_recipients[] = $mobile;
                // log_message('error', 'Exception sending SMS to ' . $mobile . ': ' . $e->getMessage());
            }
        }
    }
    
    // Deduct credits if any SMS was sent
    if ($success_count > 0) {
        $credits_to_deduct = ceil(($credits_needed * $success_count) / $recipient_count);
        $deducted = $ci->sendsmsmail_model->deduct_sms_units($branchID, $credits_to_deduct);
        
        if ($deducted) {
            // log_message('info', 'Deducted ' . $credits_to_deduct . ' SMS credits for branch ' . $branchID);
        } else {
            // log_message('error', 'Failed to deduct SMS credits for branch ' . $branchID);
        }
    }
    
    // log_message('info', "Fees payment SMS sent: {$success_count}/{$recipient_count} successful");
    
    return $success_count > 0;
}

    public function feeReminder($stuData, $remData)
    {
        $sms_api = $this->application_model->smsServiceProvider($remData['branch_id']);
        if ($sms_api != 'disabled') {
            $text = str_replace('{guardian_name}', $stuData['guardian_name'], $remData['message']);
            $text = str_replace('{child_name}', $stuData['child_name'], $text);
            $text = str_replace('{due_date}', $stuData['due_date'], $text);
            $text = str_replace('{due_amount}', $stuData['balance_amount'], $text);
            $text = str_replace('{fee_type}', $stuData['type_name'], $text);
            if ($remData['student'] == 1) {
                if (!empty($stuData['child_mobileno'])) {
                    $this->_send($sms_api, $stuData['child_mobileno'], $text, $remData['dlt_template_id']);
                }
            }
            if ($remData['guardian'] == 1) {
                if (!empty($stuData['guardian_mobileno'])) {
                    $this->_send($sms_api, $stuData['guardian_mobileno'], $text, $remData['dlt_template_id']);
                }
            }
        }
    }

    public function sendHomework($data)
    {
        $template = $this->db->get_where('sms_template_details', array('template_id' => 6, 'branch_id' => $data['branch_id']))->row_array();
        $sms_api = $this->application_model->smsServiceProvider($data['branch_id']);
        if (($template['notify_student'] == 1 || $template['notify_parent'] == 1) && $sms_api != 'disabled') {
            $text = str_replace('{name}', $data['fullname'], $template['template_body']);
            $text = str_replace('{register_no}', $data['register_no'], $text);
            $text = str_replace('{admission_date}', $data['admission_date'], $text);
            $text = str_replace('{class}', $data['class_name'], $text);
            $text = str_replace('{section}', $data['section_name'], $text);
            $text = str_replace('{date_of_homework}', $data['date_of_homework'], $text);
            $text = str_replace('{date_of_submission}', $data['date_of_submission'], $text);
            $text = str_replace('{subject}', get_type_name_by_id('subject', $data['subject_id']), $text);
            if ($template['notify_student'] == 1) {
                if (!empty($data['mobileno'])) {
                    $this->_send($sms_api, $data['mobileno'], $text, $template['dlt_template_id']);
                }
            }
            if ($template['notify_parent'] == 1) {
                if (!empty($data['parent_id'])) {
                    $parent = $this->db->select('mobileno')->where('id', $data['parent_id'])->get('parent')->row_array();
                    if (!empty($parent['mobileno'])) {
                        $this->_send($sms_api, $parent['mobileno'], $text, $template['dlt_template_id']);
                    }
                }
            }
        }
    }

    public function sendLiveClass($data)
    {
        $template = $this->db->get_where('sms_template_details', array('template_id' => 7, 'branch_id' => $data['branch_id']))->row_array();
        $sms_api = $this->application_model->smsServiceProvider($data['branch_id']);
        if (($template['notify_student'] == 1 || $template['notify_parent'] == 1) && $sms_api != 'disabled') {
            $text = str_replace('{name}', $data['fullname'], $template['template_body']);
            $text = str_replace('{roll}', $data['roll'], $text);
            $text = str_replace('{register_no}', $data['register_no'], $text);
            $text = str_replace('{admission_date}', $data['admission_date'], $text);
            $text = str_replace('{class}', $data['class_name'], $text);
            $text = str_replace('{section}', $data['section_name'], $text);
            $text = str_replace('{date_of_live_class}', $data['date_of_live_class'], $text);
            $text = str_replace('{start_time}', $data['start_time'], $text);
            $text = str_replace('{end_time}', $data['end_time'], $text);
            $text = str_replace('{host_by}', $data['host_by'], $text);
            if ($template['notify_student'] == 1) {
                if (!empty($data['mobileno'])) {
                    $this->_send($sms_api, $data['mobileno'], $text, $template['dlt_template_id']);
                }
            }
            if ($template['notify_parent'] == 1) {
                if (!empty($data['parent_id'])) {
                    $parent = $this->db->select('mobileno')->where('id', $data['parent_id'])->get('parent')->row_array();
                    if (!empty($parent['mobileno'])) {
                        $this->_send($sms_api, $parent['mobileno'], $text, $template['dlt_template_id']);
                    }
                }
            }
        }
    }

    public function sendOnlineExam($data)
    {
        $template = $this->db->get_where('sms_template_details', array('template_id' => 8, 'branch_id' => $data['branch_id']))->row_array();
        $sms_api = $this->application_model->smsServiceProvider($data['branch_id']);
        if (($template['notify_student'] == 1 || $template['notify_parent'] == 1) && $sms_api != 'disabled') {
            $text = str_replace('{name}', $data['fullname'], $template['template_body']);
            $text = str_replace('{roll}', $data['roll'], $text);
            $text = str_replace('{register_no}', $data['register_no'], $text);
            $text = str_replace('{admission_date}', $data['admission_date'], $text);
            $text = str_replace('{class}', $data['class_name'], $text);
            $text = str_replace('{section}', $data['section_name'], $text);
            $text = str_replace('{exam_title}', $data['exam_title'], $text);
            $text = str_replace('{start_time}', $data['start_time'], $text);
            $text = str_replace('{end_time}', $data['end_time'], $text);
            $text = str_replace('{time_duration}', $data['time_duration'], $text);
            $text = str_replace('{attempt}', $data['attempt'], $text);
            $text = str_replace('{passing_mark}', $data['passing_mark'], $text);
            $text = str_replace('{exam_fee}', $data['exam_fee'], $text);
            if ($template['notify_student'] == 1) {
                if (!empty($data['mobileno'])) {
                    $this->_send($sms_api, $data['mobileno'], $text, $template['dlt_template_id']);
                }
            }
            if ($template['notify_parent'] == 1) {
                if (!empty($data['parent_id'])) {
                    $parent = $this->db->select('mobileno')->where('id', $data['parent_id'])->get('parent')->row_array();
                    if (!empty($parent['mobileno'])) {
                        $this->_send($sms_api, $parent['mobileno'], $text, $template['dlt_template_id']);
                    }
                }
            }
        }
    }

    public function sendBirthdayStudentWishes($data)
    {
        $student = $this->application_model->getstudentdetails($data['student_id']);
        if (!empty($student)) {
            $template = $this->db->get_where('sms_template_details', array('template_id' => 9, 'branch_id' => $student['branch_id']))->row_array();
            $sms_api = $this->application_model->smsServiceProvider($student['branch_id']);
            if (!empty($template) && ($template['notify_student'] == 1 || $template['notify_parent'] == 1) && $sms_api != 'disabled') {
                $text = str_replace('{name}', $student['first_name'] . ' ' . $student['last_name'], $template['template_body']);
                $text = str_replace('{register_no}', $student['register_no'], $text);
                $text = str_replace('{admission_date}', $student['admission_date'], $text);
                $text = str_replace('{class}', $student['class_name'], $text);
                $text = str_replace('{section}', $student['section_name'], $text);
                $text = str_replace('{roll}', $student['roll'], $text);
                $text = str_replace('{birthday}', _d($student['birthday']), $text);
                if ($template['notify_student'] == 1) {
                    if (!empty($student['mobileno'])) {
                        $this->_send($sms_api, $student['mobileno'], $text, $template['dlt_template_id']);
                    }
                }
                if ($template['notify_parent'] == 1) {
                    if (!empty($student['parent_id'])) {
                        $parent = $this->db->select('mobileno')->where('id', $student['parent_id'])->get('parent')->row_array();
                        if (!empty($parent['mobileno'])) {
                            $this->_send($sms_api, $parent['mobileno'], $text, $template['dlt_template_id']);
                        }
                    }
                }
            }
        }
    }

    public function sendBirthdayStaffWishes($data)
    {
        $branchID = $this->application_model->get_branch_id();
        $sql = "SELECT `name`,`birthday`,`joining_date`,`mobileno`,`branch_id` FROM `staff` WHERE `id` = " . $this->db->escape($data['staff_id']) . " AND `branch_id` = " . $this->db->escape($branchID);
        $staff = $this->db->query($sql)->row_array();
        if (!empty($staff)) {
            $template = $this->db->get_where('sms_template_details', array('template_id' => 10, 'branch_id' => $staff['branch_id']))->row_array();
            $sms_api = $this->application_model->smsServiceProvider($staff['branch_id']);
            if (!empty($template) && ($template['notify_student'] == 1 || $template['notify_parent'] == 1) && $sms_api != 'disabled') {
                $text = str_replace('{name}', $staff['name'], $template['template_body']);
                $text = str_replace('{joining_date}', $staff['joining_date'], $text);
                $text = str_replace('{birthday}', _d($staff['birthday']), $text);
                if ($template['notify_student'] == 1) {
                    if (!empty($staff['mobileno'])) {
                        $this->_send($sms_api, $staff['mobileno'], $text, $template['dlt_template_id']);
                    }
                }
            }
        }
    }

    public function _send($sms_api, $receiver, $text, $dlt_template_id = '')
        {
            
            $response = ''; // Initialize response
            
            if ($sms_api == 2) {
                $response = $this->clickatell->send_message($receiver, $text);
            } elseif ($sms_api == 1) {
                $get = $this->twilio->get_twilio();
                $from = $get['number'];
                $response = $this->twilio->sms($from, $receiver, $text);
            } elseif ($sms_api == 4) {
                $response = $this->bulk->send($receiver, $text);
            } elseif ($sms_api == 3) {
                $response = $this->msg91->send($receiver, $text, $dlt_template_id);
            } elseif ($sms_api == 5) {
                $response = $this->textlocal->sendSms($receiver, $text);
            } elseif ($sms_api == 6) {
                $response = $this->smscountry->send($receiver, $text);
            } elseif ($sms_api == 7) {
                $response = $this->bulksmsbd->send($receiver, $text);
            } elseif ($sms_api == 8) {
                $response = $this->custom_sms->send($receiver, $text, $dlt_template_id);
            }
            
            // Log the response for debugging
            // log_message('debug', 'SMS _send() response: ' . substr($response, 0, 200));
            
            // RETURN the response
            return $response;
        }

    // ADDITIONAL HELPER FUNCTIONS 
    /**
 * Send SMS with credit deduction
 */
private function sendWithCreditDeduction($branch_id, $mobile, $message, $dlt_template_id = '')
{
    // Get SMS gateway for this branch
    $sms_api = $this->application_model->smsServiceProvider($branch_id);
    
    if ($sms_api == 'disabled') {
        // log_message('error', "SMS gateway disabled for branch {$branch_id}");
        return false;
    }
    
    // Check if mobile number is valid
    if (empty($mobile)) {
        // log_message('warning', "Empty mobile number for branch {$branch_id}");
        return false;
    }
    
    // Clean mobile number
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    
    if (empty($mobile)) {
        // log_message('warning', "Invalid mobile number for branch {$branch_id}");
        return false;
    }
    
    // Check SMS credits
    $ci =& get_instance();
    $ci->load->model('sendsmsmail_model');
    $current_credits = $ci->sendsmsmail_model->get_sms_credit($branch_id);
    
    // Calculate required credits (1 credit per SMS for simplicity)
    $required_credits = 1; // Simplified calculation
    
    if ($current_credits < $required_credits) {
        // log_message('error', "Insufficient SMS credits for branch {$branch_id}. Available: {$current_credits}, Needed: {$required_credits}");
        return false;
    }
    
    // Send SMS
    $result = $this->_send($sms_api, $mobile, $message, $dlt_template_id);
    
    // Deduct credits if successful
    if ($result) {
        $deducted = $ci->sendsmsmail_model->deduct_sms_units($branch_id, $required_credits);
        if ($deducted) {
            // log_message('info', "Deducted {$required_credits} SMS credits for branch {$branch_id}");
        }
    }
    
    return $result;
}

private function send_fees_payment_sms($data, $template, $branchID, $sms_api)
{
    // log_message('debug', 'send_fees_payment_sms() called - Data: ' . json_encode($data) . ', Branch: ' . $branchID);
    
    // Check if we have student_id in data
    if (empty($data['student_id'])) {
        // log_message('error', 'No student_id in fees payment SMS data');
        return false;
    }
    
    $student = $this->application_model->getstudentdetails($data['student_id']);
    
    if (empty($student)) {
        // log_message('error', "Student not found for ID: " . $data['student_id']);
        return false;
    }
    
    // Calculate balance
    $balance = isset($data['balance']) ? $data['balance'] : 0;
    
    // log_message('debug', 'Student found: ' . $student['first_name'] . ' ' . $student['last_name']);
    
    // Prepare message with balance
    $text = str_replace('{name}', $student['first_name'] . ' ' . $student['last_name'], $template['template_body']);
    $text = str_replace('{register_no}', $student['register_no'], $text);
    $text = str_replace('{admission_date}', $student['admission_date'], $text);
    $text = str_replace('{class}', $student['class_name'], $text);
    $text = str_replace('{section}', $student['section_name'], $text);
    $text = str_replace('{roll}', $student['roll'], $text);
    $text = str_replace('{paid_amount}', $data['amount'], $text);
    $text = str_replace('{paid_date}', $data['paid_date'], $text);
    $text = str_replace('{balance}', number_format($balance, 2), $text);
    
    // log_message('debug', 'SMS message prepared: ' . substr($text, 0, 100) . '...');
    
    // Prepare recipients
    $recipients = [];
    
    // Student
    if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
        $recipients[] = [
            'name' => $student['first_name'] . ' ' . $student['last_name'],
            'mobileno' => $student['mobileno'],
            'type' => 'student',
            'student_id' => $student['id']
        ];
        // log_message('debug', 'Added student recipient: ' . $student['mobileno']);
    }
    
    // Parent
    if ($template['notify_parent'] == 1 && !empty($student['parent_id'])) {
        $parent = $this->db->select('id, name, mobileno')
            ->where('id', $student['parent_id'])
            ->get('parent')
            ->row_array();
        
        if (!empty($parent['mobileno'])) {
            $recipients[] = [
                'name' => $parent['name'],
                'mobileno' => $parent['mobileno'],
                'type' => 'parent',
                'parent_id' => $parent['id']
            ];
            // log_message('debug', 'Added parent recipient: ' . $parent['mobileno']);
        }
    }
    
    if (empty($recipients)) {
        // log_message('warning', "No recipients for fees payment SMS");
        return false;
    }
    
    // log_message('debug', 'Total recipients: ' . count($recipients));
    
    // Calculate credits
    $credits_needed = $this->calculate_sms_cost_fees($text, count($recipients));
    
    // Check credits
    $ci =& get_instance();
    $ci->load->model('sendsmsmail_model');
    $current_credits = $ci->sendsmsmail_model->get_sms_credit($branchID);
    
    // log_message('debug', 'Credits needed: ' . $credits_needed . ', Current credits: ' . $current_credits);
    
    if ($current_credits < $credits_needed) {
        // log_message('error', "Insufficient credits for fees payment SMS. Needed: {$credits_needed}, Available: {$current_credits}");
        return false;
    }
    
    // Actually send SMS
    $success_count = 0;
    
    foreach ($recipients as $recipient) {
        $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
        
        if (!empty($mobile)) {
            // log_message('debug', 'Sending SMS to: ' . $mobile);
            
            // Check if SMS gateway is enabled
            if ($sms_api == 'disabled') {
                // log_message('error', 'SMS gateway disabled for branch ' . $branchID);
                continue;
            }
            
            // Send SMS using the _send method
            try {
                $response = $this->_send($sms_api, $mobile, $text);
                // log_message('debug', 'SMS API response: ' . substr($response, 0, 200));
                
                // Check if successful
                if ($this->is_successful_response($response)) {
                    $success_count++;
                    // log_message('info', 'SMS sent successfully to ' . $mobile);
                } else {
                    // log_message('error', 'SMS failed for ' . $mobile . ' - Response: ' . $response);
                }
                
                // Log delivery
                $this->log_delivery($recipient, 
                    ($this->is_successful_response($response) ? 'sent' : 'failed'), 
                    $response, $branchID);
                    
            } catch (Exception $e) {
                // log_message('error', 'Exception sending SMS to ' . $mobile . ': ' . $e->getMessage());
            }
        }
    }
    
    // Deduct credits if any SMS was sent
    if ($success_count > 0) {
        $credits_to_deduct = ceil(($credits_needed * $success_count) / count($recipients));
        $deducted = $ci->sendsmsmail_model->deduct_sms_units($branchID, $credits_to_deduct);
        
        if ($deducted) {
            // log_message('info', 'Deducted ' . $credits_to_deduct . ' SMS credits for branch ' . $branchID);
        } else {
            // log_message('error', 'Failed to deduct SMS credits for branch ' . $branchID);
        }
    }
    
    // log_message('info', "Fees payment SMS processing complete. Sent: {$success_count}/" . count($recipients));
    
    return $success_count > 0;
}
/**
 * Send interview invitation SMS with credit checking
 */
public function sendInterviewInvitation($interview_id, $admission_data, $branch_id)
{
    // Validate branch_id
    if (empty($branch_id) || !is_numeric($branch_id)) {
        // Fallback to admission branch_id
        $branch_id = $admission_data['branch_id'] ?? $this->application_model->get_branch_id();
    }
    
    // Ensure branch_id is valid
    if (empty($branch_id)) {
        return ['success' => false, 'message' => 'Branch ID not found'];
    }
    // Load required models
    $CI =& get_instance();
    $CI->load->model('sendsmsmail_model');
    
    // Get interview details
    $interview = $CI->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    if (empty($interview)) {
        return ['success' => false, 'message' => 'Interview not found'];
    }
    
    // Get SMS template for interview invitation (template_id = 11)
    $template = $CI->db->get_where('sms_template_details', [
        'template_id' => 11,
        'branch_id' => $branch_id
    ])->row_array();
    
    if (empty($template) || empty($template['template_body'])) {
        // Fallback to default message
        return $this->sendInterviewInvitationDefault($interview_id, $admission_data, $branch_id);
    }
    
    // Get interviewer name
    $interviewer = $CI->db->select('name')->where('id', $interview['interviewer_id'])->get('staff')->row();
    
    // Prepare message with placeholders
    $message = $template['template_body'];
    $message = str_replace('{guardian_name}', $admission_data['guardian_name'], $message);
    $message = str_replace('{student_name}', $admission_data['first_name'] . ' ' . $admission_data['last_name'], $message);
    $message = str_replace('{interview_date}', date('d M Y', strtotime($interview['interview_date'])), $message);
    $message = str_replace('{interview_time}', date('h:i A', strtotime($interview['interview_time'])), $message);
    $message = str_replace('{location}', $interview['location'], $message);
    $message = str_replace('{interviewer_name}', $interviewer ? $interviewer->name : 'Admissions Office', $message);
    
    // Get school/branch details
    $branch = $CI->db->where('id', $branch_id)->get('branch')->row_array();
    $message = str_replace('{school_name}', $branch['name'], $message);
    $message = str_replace('{school_phone}', $branch['mobileno'] ?? '', $message);
    
    // Determine recipient
    $recipient_mobile = $admission_data['grd_mobile_no'] ?? $admission_data['mobile_no'];
    if (empty($recipient_mobile)) {
        return ['success' => false, 'message' => 'No mobile number available'];
    }
    
    // Clean mobile number
    $recipient_mobile = preg_replace('/[^0-9]/', '', $recipient_mobile);
    
    // Calculate SMS credits needed
    $required_credits = $this->calculateSmsCredits($message, 1);
    
    // Check SMS credits
    $current_credits = $CI->sendsmsmail_model->get_sms_credit($branch_id);
    
    if ($current_credits < $required_credits) {
        return [
            'success' => false,
            'message' => "Insufficient SMS credits. Needed: {$required_credits}, Available: {$current_credits}",
            'required_credits' => $required_credits,
            'available_credits' => $current_credits
        ];
    }
    
    // Get SMS gateway for this branch
    $sms_api = $this->application_model->smsServiceProvider($branch_id);
    if ($sms_api == 'disabled') {
        return ['success' => false, 'message' => 'SMS gateway disabled'];
    }
    
    // Send SMS using existing _send method
    $response = $this->_send($sms_api, $recipient_mobile, $message, $template['dlt_template_id']);
    
    // Check if successful
    $success = $this->is_successful_response($response);
    
    if ($success) {
        // Deduct credits
        $CI->sendsmsmail_model->deduct_sms_units($branch_id, $required_credits);
        
        // Log delivery
       
        $this->logInterviewDelivery($interview_id, $recipient_mobile, $response, 'invitation', $branch['id'], $message);
        
        return [
            'success' => true,
            'message' => 'Interview invitation SMS sent successfully',
            'credits_used' => $required_credits,
            'response' => $response
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send SMS: ' . substr($response, 0, 200),
            'response' => $response
        ];
    }
}

/**
 * Send interview reminder SMS
 */
public function sendInterviewReminder($interview_id, $branch_id)
{
    $CI =& get_instance();
    $CI->load->model('sendsmsmail_model');
    
    // Get interview with admission details
    $CI->db->select('i.*, oa.first_name, oa.last_name, oa.guardian_name, oa.grd_mobile_no, oa.mobile_no');
    $CI->db->from('online_admission_interviews i');
    $CI->db->join('online_admission oa', 'oa.id = i.admission_id', 'left');
    $CI->db->where('i.id', $interview_id);
    $interview = $CI->db->get()->row_array();
    
    if (empty($interview)) {
        return ['success' => false, 'message' => 'Interview not found'];
    }
    
    // Get template ID 12 for reminder
    $template = $CI->db->get_where('sms_template_details', [
        'template_id' => 12,
        'branch_id' => $branch_id
    ])->row_array();
    
    if (empty($template) || empty($template['template_body'])) {
        // Fallback default reminder
        $message = "Reminder: Interview for {$interview['first_name']} {$interview['last_name']} ";
        $message .= "tomorrow at " . date('h:i A', strtotime($interview['interview_time'])) . ". ";
        $message .= "Location: {$interview['location']}";
    } else {
        $message = $template['template_body'];
        $message = str_replace('{guardian_name}', $interview['guardian_name'], $message);
        $message = str_replace('{student_name}', $interview['first_name'] . ' ' . $interview['last_name'], $message);
        $message = str_replace('{interview_date}', date('d M Y', strtotime($interview['interview_date'])), $message);
        $message = str_replace('{interview_time}', date('h:i A', strtotime($interview['interview_time'])), $message);
        $message = str_replace('{location}', $interview['location'], $message);
        
        $branch = $CI->db->where('id', $branch_id)->get('branch')->row_array();
        $message = str_replace('{school_phone}', $branch['mobileno'] ?? '', $message);
    }
    
    // Determine recipient
    $recipient_mobile = $interview['grd_mobile_no'] ?? $interview['mobile_no'];
    if (empty($recipient_mobile)) {
        return ['success' => false, 'message' => 'No mobile number available'];
    }
    
    $recipient_mobile = preg_replace('/[^0-9]/', '', $recipient_mobile);
    
    // Check credits
    $required_credits = $this->calculateSmsCredits($message, 1);
    $current_credits = $CI->sendsmsmail_model->get_sms_credit($branch_id);
    
    if ($current_credits < $required_credits) {
        return ['success' => false, 'message' => "Insufficient credits for reminder"];
    }
    
    // Send SMS
    $sms_api = $this->application_model->smsServiceProvider($branch_id);
    if ($sms_api == 'disabled') {
        return ['success' => false, 'message' => 'SMS gateway disabled'];
    }
    
    $response = $this->_send($sms_api, $recipient_mobile, $message, $template['dlt_template_id'] ?? '');
    $success = $this->is_successful_response($response);
    
    if ($success) {
        $CI->sendsmsmail_model->deduct_sms_units($branch_id, $required_credits);
       $this->logInterviewDelivery($interview_id, $recipient_mobile, $response, 'reminder', $branch_id, $message);
        
        // Update interview record
        $CI->db->where('id', $interview_id);
        $CI->db->update('online_admission_interviews', [
            'reminder_sent' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    return [
        'success' => $success,
        'message' => $success ? 'Reminder sent' : 'Reminder failed',
        'credits_used' => $success ? $required_credits : 0
    ];
}

/**
 * Send admission outcome SMS (approval/decline)
 */
public function sendAdmissionOutcome($admission_id, $template_id, $branch_id, $student_data = [], $parent_data = []) // Line ~847
{
    $CI =& get_instance();
    $CI->load->model('sendsmsmail_model');
    
    // ADD THIS ARRAY DEFINITION:
    $valid_admission_templates = [1, 11, 12, 14, 15, 16, 17, 18, 19, 20];

if (!in_array($template_id, $valid_admission_templates)) { // FIX: Use correct variable name
    return ['success' => false, 'message' => 'Invalid template ID'];
}
    
    // Get admission details
    $admission = $CI->db->where('id', $admission_id)->get('online_admission')->row_array();
    if (empty($admission)) {
        return ['success' => false, 'message' => 'Admission not found'];
    }
    
    // Get template
    $template = $CI->db->get_where('sms_template_details', [
        'template_id' => $template_id,
        'branch_id' => $branch_id
    ])->row_array();
    
    if (empty($template) || empty($template['template_body'])) {
        return ['success' => false, 'message' => 'Template not configured'];
    }
    
    // Determine recipients based on template settings
    $recipients = [];
    $message = $template['template_body'];
    
    // Replace common placeholders
    $branch = $CI->db->where('id', $branch_id)->get('branch')->row_array();
    $message = str_replace('{school_name}', $branch['name'], $message);
    $message = str_replace('{school_phone}', $branch['mobileno'] ?? '', $message);
    $message = str_replace('{contact_info}', $branch['mobileno'] ?? $branch['email'] ?? 'School Office', $message);
    
    // Student recipient
    if ($template['notify_student'] == 1 && !empty($admission['mobile_no'])) {
        $student_message = $message;
        $student_message = str_replace('{name}', $admission['first_name'] . ' ' . $admission['last_name'], $student_message);
        $student_message = str_replace('{student_name}', $admission['first_name'] . ' ' . $admission['last_name'], $student_message);
        
        // Add student-specific data if provided
        if (!empty($student_data)) {
            foreach ($student_data as $key => $value) {
                $student_message = str_replace('{' . $key . '}', $value, $student_message);
            }
        }
        
        $recipients[] = [
            'mobile' => $admission['mobile_no'],
            'message' => $student_message,
            'type' => 'student'
        ];
    }
    
    // Parent recipient
    if ($template['notify_parent'] == 1 && !empty($admission['grd_mobile_no'])) {
        $parent_message = $message;
        $parent_message = str_replace('{name}', $admission['guardian_name'], $parent_message);
        $parent_message = str_replace('{guardian_name}', $admission['guardian_name'], $parent_message);
        $parent_message = str_replace('{student_name}', $admission['first_name'] . ' ' . $admission['last_name'], $parent_message);
        
        // Add parent-specific data if provided
        if (!empty($parent_data)) {
            foreach ($parent_data as $key => $value) {
                $parent_message = str_replace('{' . $key . '}', $value, $parent_message);
            }
        }
        
        $recipients[] = [
            'mobile' => $admission['grd_mobile_no'],
            'message' => $parent_message,
            'type' => 'parent'
        ];
    }
    
    if (empty($recipients)) {
        return ['success' => false, 'message' => 'No recipients configured'];
    }
    
    // Calculate total credits needed
    $total_credits = 0;
    foreach ($recipients as $recipient) {
        $total_credits += $this->calculateSmsCredits($recipient['message'], 1);
    }
    
    // Check credits
    $current_credits = $CI->sendsmsmail_model->get_sms_credit($branch_id);
    if ($current_credits < $total_credits) {
        return ['success' => false, 'message' => "Insufficient credits. Needed: {$total_credits}, Available: {$current_credits}"];
    }
    
    // Send to all recipients
    $sms_api = $this->application_model->smsServiceProvider($branch_id);
    if ($sms_api == 'disabled') {
        return ['success' => false, 'message' => 'SMS gateway disabled'];
    }
    
    $sent_count = 0;
    $failed_count = 0;
    
    foreach ($recipients as $recipient) {
        $mobile = preg_replace('/[^0-9]/', '', $recipient['mobile']);
        $response = $this->_send($sms_api, $mobile, $recipient['message'], $template['dlt_template_id'] ?? '');
        
        if ($this->is_successful_response($response)) {
            $sent_count++;
        } else {
            $failed_count++;
        }
        
        // Small delay to prevent rate limiting
        usleep(50000);
    }
    
    // Deduct credits for successful sends
    if ($sent_count > 0) {
        $credits_to_deduct = ceil(($total_credits * $sent_count) / count($recipients));
        $CI->sendsmsmail_model->deduct_sms_units($branch_id, $credits_to_deduct);
    }
    
    return [
        'success' => $sent_count > 0,
        'sent' => $sent_count,
        'total' => count($recipients),
        'credits_used' => $sent_count > 0 ? $credits_to_deduct : 0,
        'message' => "Sent {$sent_count}/" . count($recipients) . " messages"
    ];
}
/**
 * Check if SMS response is successful
 */
private function is_successful_response($response)
{
    $CI =& get_instance();
    return $CI->sendsmsmail_model->is_sms_successful($response);
}
/**
 * Calculate SMS credits needed
 */
private function calculateSmsCredits($message, $recipient_count)
{
    $CI =& get_instance();
    return $CI->application_model->calculate_sms_credits($message, $recipient_count);
}

/**
 * Log interview SMS delivery - UPDATED to accept actual message
 */
private function logInterviewDelivery($interview_id, $mobile, $response, $type, $branch_id, $sms_message = '')
{
    $CI =& get_instance();
    
    $status = $this->is_successful_response($response) ? 'sent' : 'failed';
    
    // If no message provided, try to get from bulk_sms_email
    if (empty($sms_message)) {
        $sms_record = $CI->db->select('message')
                            ->where('transaction_id', 'LIKE', 'RSCH_' . $interview_id . '_%')
                            ->or_where('transaction_id', 'LIKE', 'REM_' . $interview_id . '_%')
                            ->or_where('transaction_id', 'LIKE', 'INT_' . $interview_id . '_%')
                            ->order_by('id', 'DESC')
                            ->limit(1)
                            ->get('bulk_sms_email')
                            ->row_array();
        
        if (!empty($sms_record)) {
            $sms_message = $sms_record['message'];
        } else {
            // Fallback descriptive message
            $sms_message = 'Interview ' . $type . ' notification sent to ' . $mobile;
        }
    }
    
    $log_data = [
        'interview_id' => $interview_id,
        'type' => 'sms',
        'direction' => 'to_parent',
        'message' => substr($sms_message, 0, 500), // ACTUAL SMS TEXT HERE
        'recipient' => $mobile,
        'status' => $status,
        'gateway_response' => substr($response, 0, 500), // Gateway technical response
        'credits_used' => 1, // Adjust based on your calculation
        'created_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id,
        'sent_by' => $CI->session->userdata('loggedin_userid') ?: 1
    ];
    
    // Insert into interview_communications
    if ($CI->db->table_exists('interview_communications')) {
        $CI->db->insert('interview_communications', $log_data);
    }
    
    // Also update bulk_sms_email record with actual message
    if ($CI->db->table_exists('bulk_sms_email')) {
        // Find the recent record for this interview
        $CI->db->where('transaction_id', 'LIKE', strtoupper(substr($type, 0, 4)) . '_' . $interview_id . '_%')
               ->order_by('id', 'DESC')
               ->limit(1)
               ->update('bulk_sms_email', ['message' => $sms_message]);
    }
}

/**
 * Fallback default interview invitation
 */
private function sendInterviewInvitationDefault($interview_id, $admission_data, $branch_id)
{
    $CI =& get_instance();
    
    $interview = $CI->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    $interviewer = $CI->db->select('name')->where('id', $interview['interviewer_id'])->get('staff')->row();
    
    $message = "Dear " . $admission_data['guardian_name'] . ",\n";
    $message .= "Interview scheduled for " . $admission_data['first_name'] . " " . $admission_data['last_name'] . " admission.\n";
    $message .= "Date: " . date('d M Y', strtotime($interview['interview_date'])) . "\n";
    $message .= "Time: " . date('h:i A', strtotime($interview['interview_time'])) . "\n";
    $message .= "Location: " . $interview['location'] . "\n";
    $message .= "Interviewer: " . ($interviewer ? $interviewer->name : 'Admissions Office') . "\n";
    $message .= "Please confirm attendance.";
    
    $recipient_mobile = $admission_data['grd_mobile_no'] ?? $admission_data['mobile_no'];
    if (empty($recipient_mobile)) {
        return ['success' => false, 'message' => 'No mobile number available'];
    }
    
    $recipient_mobile = preg_replace('/[^0-9]/', '', $recipient_mobile);
    
    // Send via default gateway
    $sms_api = $this->application_model->smsServiceProvider($branch_id);
    if ($sms_api == 'disabled') {
        return ['success' => false, 'message' => 'SMS gateway disabled'];
    }
    
    $response = $this->_send($sms_api, $recipient_mobile, $message);
    $success = $this->is_successful_response($response);
    
    return [
        'success' => $success,
        'message' => $success ? 'Default SMS sent' : 'Default SMS failed',
        'response' => $response
    ];
}

private function calculate_sms_cost_fees($message, $recipient_count)
{
    $message_length = mb_strlen($message, 'UTF-8');
    
    // Check if Unicode
    $is_unicode = false;
    for ($i = 0; $i < $message_length; $i++) {
        $char = mb_substr($message, $i, 1, 'UTF-8');
        if (ord($char) > 127) {
            $is_unicode = true;
            break;
        }
    }
    
    $chars_per_sms = $is_unicode ? 70 : 160;
    $sms_parts = ceil($message_length / $chars_per_sms);
    $credits_per_part = $is_unicode ? 2 : 1;
    
    // CORRECTED FORMULA: SMS parts × recipients × credits per part
    $total_credits = $sms_parts * $recipient_count * $credits_per_part;
    
    return $total_credits;
}

private function log_delivery($recipient, $status, $response, $branch_id)
{
    $log_data = [
        'recipient_contact' => $recipient['mobileno'],
        'status' => $status,
        'gateway_response' => substr($response, 0, 500),
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id
    ];
    
    // Check if table exists
    if ($this->db->table_exists('sms_email_delivery_logs')) {
        $this->db->insert('sms_email_delivery_logs', $log_data);
    }
    
    // log_message('debug', 'Logged delivery: ' . $recipient['mobileno'] . ' - ' . $status);
}

public function sendInterviewReschedule($interview_id, $admission, $branch_id, $old_date = null, $old_time = null, $reason = null)
{
    // log_message('debug', '=== sendInterviewReschedule START ===');
    // log_message('debug', 'Interview ID: ' . $interview_id);
    // log_message('debug', 'Branch ID: ' . $branch_id);

    // Store old date/time BEFORE any update
    $old_interview_data = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();

    // Validate branch ID
    if (empty($branch_id) || !is_numeric($branch_id)) {
        // log_message('error', 'Invalid branch ID: ' . $branch_id);
        return ['success' => false, 'message' => 'Invalid branch ID'];
    }
    
    // Get branch details
    $branch = $this->db->select('id, name, mobileno, email, school_name')
                       ->where('id', $branch_id)
                       ->get('branch')
                       ->row_array();
    
    if (empty($branch)) {
        // log_message('error', 'Branch not found with ID: ' . $branch_id);
        return ['success' => false, 'message' => 'Branch not found'];
    }
    
    // log_message('debug', 'Branch retrieved: ' . $branch['name']);
    
    // Load required models
    $this->load->model('sendsmsmail_model');
    
    // ========== CRITICAL FIX: ALWAYS USE TEMPLATE 18 FOR RESCHEDULE ==========
    // Get template for interview RESCHEDULE (template_id = 18)
    $template = $this->db->get_where('sms_template_details', [
        'template_id' => 18,  // FIXED: Always use 18 for reschedule
        'branch_id' => $branch['id']
    ])->row_array();
    
    // Debug log
    // log_message('debug', 'Reschedule Template (ID 18) found: ' . (!empty($template) ? 'YES' : 'NO'));
    
    // ========== NEW: CREATE DEFAULT TEMPLATE IF NOT CONFIGURED ==========
    if (empty($template)) {
        // log_message('warning', 'No reschedule template (ID 18) found. Creating default.');
        
        // Create default reschedule template
        $default_template_body = "Dear {guardian_name},\n\n" .
                                "The interview for {student_name} has been rescheduled.\n\n" .
                                "OLD DATE/TIME: {old_date} at {old_time}\n" .
                                "NEW DATE/TIME: {new_date} at {new_time}\n" .
                                "LOCATION: {location}\n\n" .
                                "INTERVIEWER: {interviewer_name}\n" .
                                "Contact: {school_phone}\n" .
                                "{school_name}";
        
        // Insert default template
        $template_data = [
            'template_id' => 18,
            'dlt_template_id' => '',
            'notify_student' => 0,
            'notify_parent' => 1,
            'template_body' => $default_template_body,
            'branch_id' => $branch['id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('sms_template_details', $template_data);
        $template_id = $this->db->insert_id();
        
        // Get the newly created template
        $template = $this->db->where('id', $template_id)->get('sms_template_details')->row_array();
        
        // log_message('debug', 'Created default reschedule template with ID: ' . $template_id);
    }
    
    // Get interview details
    $interview = $this->db->where('id', $interview_id)->get('online_admission_interviews')->row_array();
    if (empty($interview)) {
        // log_message('error', 'Interview not found: ' . $interview_id);
        return ['success' => false, 'message' => 'Interview not found'];
    }
    
    // log_message('debug', 'Interview retrieved - Date: ' . $interview['interview_date']);   
    
    // log_message('debug', 'Previous interview found: ' . (!empty($old_interview) ? 'YES' : 'NO'));
    
    // Prepare safe values
    $guardian_name = $admission['guardian_name'] ?? 'Parent/Guardian';
    $student_first_name = $admission['first_name'] ?? '';
    $student_last_name = $admission['last_name'] ?? '';

// ========== USE OLD VALUES PASSED FROM CONTROLLER ==========
    if (!empty($old_date) && !empty($old_time)) {
        // Use values passed from controller
        $old_date_formatted = date('d M Y', strtotime($old_date));
        $old_time_formatted = date('h:i A', strtotime($old_time));
        // log_message('debug', 'Using OLD values from controller: ' . $old_date_formatted . ' at ' . $old_time_formatted);
    } else {
        // Fallback: try to get from database
        $old_date_formatted = !empty($old_interview_data['interview_date']) ? date('d M Y', strtotime($old_interview_data['interview_date'])) : 'Date not set';
        $old_time_formatted = !empty($old_interview_data['interview_time']) ? date('h:i A', strtotime($old_interview_data['interview_time'])) : 'Time not set';
        // log_message('debug', 'Using OLD values from database: ' . $old_date_formatted . ' at ' . $old_time_formatted);
    }

    // NEW date/time = What is being rescheduled TO (from POST data)
    // Get new values from POST (these come from the reschedule form)
    $new_date = !empty($this->input->post('interview_date')) ? date('d M Y', strtotime($this->input->post('interview_date'))) : 
            (isset($interview['interview_date']) ? date('d M Y', strtotime($interview['interview_date'])) : $old_date_formatted);
            
    $new_time = !empty($this->input->post('interview_time')) ? date('h:i A', strtotime($this->input->post('interview_time'))) : 
            (isset($interview['interview_time']) ? date('h:i A', strtotime($interview['interview_time'])) : $old_time_formatted);

    // log_message('debug', 'Reschedule: OLD=' . $old_date_formatted . ' ' . $old_time_formatted . ', NEW=' . $new_date . ' ' . $new_time);

    $location = $interview['location'] ?? 'School Campus';
    $school_name = $branch['school_name'] ?? $branch['name'] ?? 'School';
    $school_phone = $branch['mobileno'] ?? '';
  
    // Get reason from parameter or use default
    $reason = !empty($reason) ? $reason : 'Rescheduled by admissions office';
    // OR get from POST if available
    if (empty($reason) && !empty($this->input->post('reschedule_reason'))) {
        $reason = $this->input->post('reschedule_reason');
    }
    
    // Add interviewer to placeholders - FIXED
    $interviewer_name = '';
    if (!empty($interview['interviewer_id'])) {
        $interviewer = $this->db->select('name')->where('id', $interview['interviewer_id'])->get('staff')->row();
        $interviewer_name = $interviewer ? $interviewer->name : '';
    }

    // ========== REPLACE PLACEHOLDERS IN TEMPLATE ==========
    $message = $template['template_body'];

    // Define all possible placeholders with their values
    $placeholders = [
        '{guardian_name}' => $guardian_name,
        '{student_name}' => trim($student_first_name . ' ' . $student_last_name),
        '{old_date}' => $old_date_formatted,  // Use formatted version
        '{old_time}' => $old_time_formatted,  // Use formatted version
        '{new_date}' => $new_date,
        '{new_time}' => $new_time,
        '{location}' => $location,
        '{school_name}' => $school_name,
        '{school_phone}' => $school_phone,
        '{contact_number}' => $school_phone,
        '{contact_info}' => $school_phone,
        '{reason}' => $reason,
        '{interviewer_name}' => $interviewer_name,
        '{interviewer}' => $interviewer_name,
        '{school_email}' => $branch['email'] ?? '',
        '{school_address}' => $branch['address'] ?? '',
        '{date}' => date('d M Y'),
        '{time}' => date('h:i A')
    ];
    
    // Replace all placeholders
    foreach ($placeholders as $placeholder => $value) {
        if (strpos($message, $placeholder) !== false) {
            $message = str_replace($placeholder, $value, $message);
            // log_message('debug', 'Replaced placeholder: ' . $placeholder . ' -> ' . $value);
        }
    }
    
    // log_message('debug', 'Final message: ' . substr($message, 0, 200) . '...');
    
    // ========== DETERMINE RECIPIENT ==========
    $recipient_mobile = $admission['grd_mobile_no'] ?? $admission['mobile_no'] ?? '';
    
    if (empty($recipient_mobile)) {
        // log_message('error', 'No mobile number available');
        return ['success' => false, 'message' => 'No mobile number available'];
    }
    
    // Clean mobile number
    $recipient_mobile = preg_replace('/[^0-9]/', '', $recipient_mobile);
    
    if (empty($recipient_mobile) || strlen($recipient_mobile) < 10) {
        // log_message('error', 'Invalid mobile number: ' . $recipient_mobile);
        return ['success' => false, 'message' => 'Invalid mobile number'];
    }
    
    // ========== CREDIT CHECK ==========
    $required_credits = $this->calculateSmsCredits($message, 1);
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branch['id']);
    
    // log_message('debug', 'Credits - Required: ' . $required_credits . ', Available: ' . $current_credits);
    
    if ($current_credits < $required_credits) {
        return [
            'success' => false,
            'message' => "Insufficient SMS credits. Needed: {$required_credits}, Available: {$current_credits}"
        ];
    }
    
    // ========== SEND SMS ==========
    $sms_api = $this->application_model->smsServiceProvider($branch['id']);
    
    if ($sms_api == 'disabled') {
        return ['success' => false, 'message' => 'SMS gateway disabled'];
    }
    
    $dlt_template_id = $template['dlt_template_id'] ?? '';
    $response = $this->_send($sms_api, $recipient_mobile, $message, $dlt_template_id);
    
    // log_message('debug', 'SMS Response: ' . substr($response, 0, 200));
    
    $success = $this->is_successful_response($response);
    
    if ($success) {
        // ========== RESERVE AND USE CREDITS ==========
        $campaign_name = "Interview Reschedule - " . $student_first_name . ' ' . $student_last_name;
        
        // Create SMS record first
        $sms_data = [
            'campaign_name' => $campaign_name,
            'message' => $message,
            'message_type' => 1,
            'recipient_type' => 11, // Interview notification
            'recipients_details' => json_encode([
                'interview_id' => $interview_id,
                'template_id' => 18, // IMPORTANT: Use 18 here
                'admission_id' => $admission['id'] ?? 0
            ]),
            'additional' => json_encode([[
                'mobile' => $recipient_mobile,
                'name' => $guardian_name,
                'type' => 'parent'
            ]]),
            'schedule_time' => date('Y-m-d H:i:s'),
            'posting_status' => 2, // Completed
            'total_thread' => 1,
            'successfully_sent' => 1,
            'sms_gateway' => $sms_api,
            'credits_used' => 0, // Will be updated
            'branch_id' => $branch['id'],
            'duplicate_hash' => md5('reschedule_18_' . $interview_id . '_' . date('Y-m-d')),
            'send_type' => 'immediate',
            'transaction_id' => 'RSCH18_' . $interview_id . '_' . time(), // Changed prefix to include 18
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('bulk_sms_email', $sms_data);
        $message_id = $this->db->insert_id();
        
        // Now reserve credits with message_id
        $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
            $branch['id'], 
            $required_credits, 
            $campaign_name, 
            $message_id
        );
        
        if ($reservation_id) {
            // Update SMS record with reservation_id and credits
            $this->db->where('id', $message_id);
            $this->db->update('bulk_sms_email', [
                'credits_used' => $required_credits,
                'recipients_details' => json_encode([
                    'interview_id' => $interview_id,
                    'template_id' => 18,
                    'reservation_id' => $reservation_id,
                    'admission_id' => $admission['id'] ?? 0
                ])
            ]);
            
            // Mark reservation as used
            $this->sendsmsmail_model->mark_reservation_used($message_id, $required_credits);
            
            // Log to interview_communications
            $this->logInterviewDelivery(
                $interview_id, 
                $recipient_mobile, 
                $response, 
                'reschedule', 
                $branch['id'], 
                $message
            );
            
            // log_message('info', 'Reschedule SMS sent successfully using Template 18');
            
            return [
                'success' => true,
                'message' => 'Reschedule SMS sent successfully',
                'credits_used' => $required_credits,
                'template_id' => 18,
                'reservation_id' => $reservation_id,
                'message_id' => $message_id
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to reserve credits'];
    }
    
    return [
        'success' => false,
        'message' => 'SMS sending failed: ' . substr($response, 0, 100),
        'template_id' => 18
    ];
}

/**
 * Log emergency SMS delivery to hostel_emergency_sms_logs table
 */
private function _log_emergency_sms_delivery($incident_id, $branch_id, $mobile, $name, $type, $message, $credits, $response)
{
    if (!$this->db->table_exists('hostel_emergency_sms_logs')) {
        return;
    }
    
    $log_data = [
        'incident_id' => $incident_id,
        'recipient_name' => $name,
        'recipient_phone' => $mobile,
        'recipient_type' => $type,
        'message_sent' => substr($message, 0, 500),
        'status' => 'sent',
        'gateway_response' => substr($response, 0, 500),
        'credits_used' => $credits,
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $this->db->insert('hostel_emergency_sms_logs', $log_data);
}

/**
 * Log emergency SMS failure
 */
private function _log_emergency_sms_failure($incident_id, $branch_id, $mobile, $error_message)
{
    if (!$this->db->table_exists('hostel_emergency_sms_logs')) {
        return;
    }
    
    $log_data = [
        'incident_id' => $incident_id,
        'recipient_phone' => $mobile,
        'status' => 'failed',
        'gateway_response' => substr($error_message, 0, 500),
        'branch_id' => $branch_id,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $this->db->insert('hostel_emergency_sms_logs', $log_data);
}
/**
 * Send emergency SMS for hostel incidents
 */
public function sendEmergencySms($data)
{
    // Initialize return structure
    $return = array(
        'success' => false,
        'message' => '',
        'credits_used' => 0
    );
    
    // Validate required data
    if (empty($data['branch_id']) || empty($data['recipient_mobile']) || empty($data['message'])) {
        $return['message'] = 'Missing required parameters';
        return $return;
    }
    
    $branch_id = $data['branch_id'];
    $recipient_mobile = preg_replace('/[^0-9]/', '', $data['recipient_mobile']);
    $message = $data['message'];
    
    // Load required model
    $this->load->model('sendsmsmail_model');
    
    // Calculate required credits
    $message_length = mb_strlen($message, 'UTF-8');
    $is_unicode = preg_match('/[^\x00-\x7F]/', $message);
    $chars_per_sms = $is_unicode ? 70 : 160;
    $sms_parts = ceil($message_length / $chars_per_sms);
    $credits_needed = $sms_parts * ($is_unicode ? 2 : 1);
    
    // Check available credits
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branch_id);
    
    if ($current_credits < $credits_needed) {
        $return['message'] = "Insufficient SMS credits. Needed: {$credits_needed}, Available: {$current_credits}";
        return $return;
    }
    
    // Get SMS gateway for this branch
    $sms_api = $this->application_model->smsServiceProvider($branch_id);
    
    if ($sms_api == 'disabled') {
        $return['message'] = 'SMS gateway disabled for this branch';
        return $return;
    }
    
    // Send SMS using existing _send method
    $response = $this->_send($sms_api, $recipient_mobile, $message);
    
    // Check if successful
    $success = $this->is_successful_response($response);
    
    if ($success) {
        // Deduct credits
        $this->sendsmsmail_model->deduct_sms_units($branch_id, $credits_needed);
        
        $return['success'] = true;
        $return['message'] = 'SMS sent successfully';
        $return['credits_used'] = $credits_needed;
        $return['response'] = $response;
    } else {
        $return['message'] = 'Failed to send SMS: ' . substr($response, 0, 100);
        $return['response'] = $response;
    }
    
    return $return;
}
/**
 * Send incident resolution notification
 * @param array $data Contains: incident_id, branch_id, resolution_notes, resolved_by_name
 * @return array ['success' => bool, 'message' => string, 'credits_used' => int]
 */
/**
 * Send resolution notification SMS when incident is resolved
 */
public function sendResolutionNotification($data)
{
    $return = array('success' => false, 'message' => '', 'credits_used' => 0);
    
    error_log("=== sendResolutionNotification START ===");
    error_log("Received data: " . print_r($data, true));
    
    // Validate required data
    if (empty($data['incident_id'])) {
        $return['message'] = 'Incident ID is required';
        error_log($return['message']);
        return $return;
    }
    
    if (empty($data['branch_id'])) {
        $return['message'] = 'Branch ID is required';
        error_log($return['message']);
        return $return;
    }
    
    $ci = &get_instance();
    $ci->load->model('hostel_emergency_model');
    
    // Get incident details
    $incident = $ci->hostel_emergency_model->get_incident($data['incident_id']);
    
    if (empty($incident)) {
        $return['message'] = 'Incident not found';
        error_log($return['message']);
        return $return;
    }
    
    error_log("Incident found: " . $incident['incident_code']);
    
    // Get emergency type
    $emergency_type = $ci->hostel_emergency_model->get_emergency_type($incident['emergency_type_id']);
    $emergency_type_name = $emergency_type ? $emergency_type['name'] : 'Emergency';
    
    // Get recipients (parents of affected students + assigned staff)
    $recipients = $this->_get_resolution_recipients($incident);
    error_log("Recipients found: " . count($recipients));
    
    if (empty($recipients)) {
        $return['message'] = 'No recipients found';
        error_log($return['message']);
        return $return;
    }
    
    // Get resolved by name
    $resolved_by_name = $data['resolved_by_name'] ?? 'System';
    $resolution_notes = $data['resolution_notes'] ?? 'No additional notes';
    
    // Get school details
    $school = $ci->db->select('name, mobileno')->where('id', $data['branch_id'])->get('branch')->row();
    $school_phone = $school->mobileno ?? 'School Office';
    
    // Prepare resolution message
    $message = "✅ INCIDENT RESOLVED ✅\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Reference: {$incident['incident_code']}\n";
    $message .= "Type: {$emergency_type_name}\n";
    $message .= "Status: RESOLVED\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Resolution: {$resolution_notes}\n";
    $message .= "Resolved by: {$resolved_by_name}\n";
    $message .= "Resolved on: " . date('d M Y H:i') . "\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Contact: {$school_phone}";
    
    error_log("Message prepared: " . substr($message, 0, 100) . "...");
    
    // Calculate credits
    $credits_needed = $this->_calculate_message_credits($message, count($recipients));
    error_log("Credits needed: " . $credits_needed);
    
    // Check credits
    $ci->load->model('sendsmsmail_model');
    $current_credits = $ci->sendsmsmail_model->get_sms_credit($data['branch_id']);
    error_log("Current credits: " . $current_credits);
    
    if ($current_credits < $credits_needed) {
        $return['message'] = "Insufficient credits. Needed: {$credits_needed}, Available: {$current_credits}";
        error_log($return['message']);
        return $return;
    }
    
    // Get SMS gateway
    $sms_api = $ci->application_model->smsServiceProvider($data['branch_id']);
    error_log("SMS API: " . ($sms_api == 'disabled' ? 'DISABLED' : $sms_api));
    
    if ($sms_api == 'disabled') {
        $return['message'] = 'SMS gateway disabled';
        error_log($return['message']);
        return $return;
    }
    
    // Load SMS library
    $ci->load->library('bulksmsbd', ['branch_id' => $data['branch_id']], 'resolution_sms_lib');
    
    // Send to all recipients
    $sent_count = 0;
    $failed_count = 0;
    
    foreach ($recipients as $recipient) {
        if (empty($recipient['phone'])) {
            $failed_count++;
            continue;
        }
        
        $mobile = preg_replace('/[^0-9]/', '', $recipient['phone']);
        
        // Format Kenyan number
        if (substr($mobile, 0, 1) == '0') {
            $mobile = '254' . substr($mobile, 1);
        }
        if (substr($mobile, 0, 3) == '+254') {
            $mobile = substr($mobile, 1);
        }
        
        $personalized_message = $message;
        if ($recipient['type'] == 'parent') {
            $personalized_message = "Dear Parent/Guardian,\n\n" . $message;
        } elseif ($recipient['type'] == 'staff') {
            $personalized_message = "Dear " . ($recipient['name'] ?? 'Staff') . ",\n\n" . $message;
        }
        
        error_log("Sending resolution SMS to: " . $mobile);
        
        $response = $ci->resolution_sms_lib->send($mobile, $personalized_message);
        
        // Parse response
        $response_array = json_decode($response, true);
        if (isset($response_array['responses'][0]) && $response_array['responses'][0]['response-code'] == 200) {
            $sent_count++;
            error_log("✓ Resolution SMS sent to: " . $mobile);
        } else {
            $failed_count++;
            error_log("✗ Resolution SMS failed to: " . $mobile . " - Response: " . substr($response, 0, 100));
        }
        
        usleep(100000); // 0.1 second delay
    }
    
    error_log("Resolution SMS complete - Sent: {$sent_count}, Failed: {$failed_count}");
    
    // Deduct credits
    if ($sent_count > 0) {
        $credits_to_deduct = ceil(($credits_needed * $sent_count) / count($recipients));
        $ci->sendsmsmail_model->deduct_sms_units($data['branch_id'], $credits_to_deduct);
        error_log("Deducted {$credits_to_deduct} credits");
        
        // Log to incident SMS logs
        $this->_log_resolution_sms($data['incident_id'], $data['branch_id'], $sent_count, $failed_count, $message);
    }
    
    $return['success'] = $sent_count > 0;
    $return['message'] = "Resolution SMS sent to {$sent_count}/" . count($recipients) . " recipients";
    $return['credits_used'] = $credits_to_deduct ?? 0;
    
    error_log("=== sendResolutionNotification END: " . $return['message']);
    
    return $return;
}

/**
 * Get recipients for resolution notification (parents + assigned staff)
 */
/**
 * Get recipients for resolution notification (parents + assigned staff)
 */
private function _get_resolution_recipients($incident)
{
    $recipients = array();
    $ci = &get_instance();
    
    error_log("Getting resolution recipients for incident: " . $incident['incident_code']);
    
    // Get parents of affected students
    if (!empty($incident['student_id'])) {
        $student_ids = explode(',', $incident['student_id']);
        error_log("Student IDs: " . print_r($student_ids, true));
        
        foreach ($student_ids as $sid) {
            $sid = trim($sid);
            error_log("Processing student ID: " . $sid);
            
            $parents = $ci->hostel_emergency_model->get_student_parents($sid);
            
            if (!empty($parents)) {
                foreach ($parents as $parent) {
                    if (!empty($parent['mobileno'])) {
                        $recipients[$parent['mobileno']] = array(
                            'name' => $parent['name'],
                            'phone' => $parent['mobileno'],
                            'type' => 'parent'
                        );
                        error_log("Added parent: " . $parent['name'] . " - " . $parent['mobileno']);
                    } else {
                        error_log("Parent found but no mobile number for student: " . $sid);
                    }
                }
            } else {
                error_log("No parent found for student ID: " . $sid);
            }
        }
    } else {
        error_log("No student_id in incident");
    }
    
    // Get assigned staff for the room
    if (!empty($incident['room_id'])) {
        $staff = $ci->hostel_emergency_model->get_room_assigned_staff($incident['room_id']);
        if (!empty($staff)) {
            foreach ($staff as $s) {
                if (!empty($s['mobileno']) && !isset($recipients[$s['mobileno']])) {
                    $recipients[$s['mobileno']] = array(
                        'name' => $s['staff_name'],
                        'phone' => $s['mobileno'],
                        'type' => 'staff'
                    );
                    error_log("Added staff: " . $s['staff_name'] . " - " . $s['mobileno']);
                }
            }
        } else {
            error_log("No assigned staff for room: " . $incident['room_id']);
        }
    }
    
    // If no recipients found, add the reporter as fallback
    if (empty($recipients) && !empty($incident['reported_by'])) {
        $reporter = $ci->db->select('name, mobileno')->where('id', $incident['reported_by'])->get('staff')->row();
        if ($reporter && !empty($reporter->mobileno)) {
            $recipients[$reporter->mobileno] = array(
                'name' => $reporter->name,
                'phone' => $reporter->mobileno,
                'type' => 'reporter'
            );
            error_log("Added reporter as fallback: " . $reporter->name . " - " . $reporter->mobileno);
        }
    }
    
    error_log("Total resolution recipients: " . count($recipients));
    return array_values($recipients);
}

/**
 * Calculate message credits
 */
private function _calculate_message_credits($message, $recipient_count)
{
    $message_length = mb_strlen($message, 'UTF-8');
    $is_unicode = preg_match('/[^\x00-\x7F]/', $message);
    $chars_per_sms = $is_unicode ? 70 : 160;
    $sms_parts = ceil($message_length / $chars_per_sms);
    $credits_per_part = $is_unicode ? 2 : 1;
    
    return $sms_parts * $recipient_count * $credits_per_part;
}

/**
 * Log resolution SMS to database
 */
private function _log_resolution_sms($incident_id, $branch_id, $sent, $failed, $message)
{
    $ci = &get_instance();
    
    $log_data = array(
        'incident_id' => $incident_id,
        'recipient_name' => 'Resolution Notification',
        'recipient_phone' => "Sent: {$sent}, Failed: {$failed}",
        'recipient_type' => 'resolution',
        'message_sent' => substr($message, 0, 500),
        'status' => $sent > 0 ? 'sent' : 'failed',
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $ci->db->insert('hostel_emergency_sms_logs', $log_data);
    error_log("Resolution SMS logged to database");
}

/**
 * Send mass emergency alert (Fire, Missing Student, Security Threat)
 * @param array $data Contains: incident_id, branch_id, message_type
 * @return array ['success' => bool, 'message' => string, 'credits_used' => int]
 */
/**
 * Send mass emergency alert (Fire, Missing Student, Security Threat)
 */
public function sendMassEmergencyAlert($data)
{
    $return = array('success' => false, 'message' => '', 'credits_used' => 0);
    
    // Debug log
    error_log("=== sendMassEmergencyAlert called ===");
    error_log("Received data: " . print_r($data, true));
    
    // Validate required data
    if (empty($data['incident_id'])) {
        $return['message'] = 'Incident ID is required';
        error_log($return['message']);
        return $return;
    }
    
    if (empty($data['branch_id'])) {
        $return['message'] = 'Branch ID is required';
        error_log($return['message']);
        return $return;
    }
    
    $ci = &get_instance();
    $ci->load->model('hostel_emergency_model');
    
    $incident = $ci->hostel_emergency_model->get_incident($data['incident_id']);
    
    if (empty($incident)) {
        $return['message'] = 'Incident not found';
        error_log($return['message']);
        return $return;
    }
    
    error_log("Incident found: " . $incident['incident_code']);
    
    // Get emergency type
    $emergency_type = $ci->hostel_emergency_model->get_emergency_type($incident['emergency_type_id']);
    $emergency_type_name = $emergency_type ? $emergency_type['name'] : 'Emergency';
    
    error_log("Emergency type: " . $emergency_type_name);
    error_log("Severity: " . $incident['severity']);
    
    // Only send for critical emergencies
    $critical_types = array('Fire Emergency', 'Missing Student', 'Security Threat', 'Natural Disaster');
    
    if (!in_array($emergency_type_name, $critical_types) && $incident['severity'] != 'critical') {
        $return['message'] = 'Not a critical emergency. Mass alerts only for Fire, Missing Student, Security Threat, or Natural Disaster.';
        error_log($return['message']);
        return $return;
    }
    
    // Get ALL parents and staff in this branch
    $recipients = $this->_get_mass_alert_recipients($data['branch_id']);
    
    error_log("Recipients found: " . count($recipients));
    
    if (empty($recipients)) {
        $return['message'] = 'No recipients found in this branch';
        error_log($return['message']);
        return $return;
    }
    
    // Prepare emergency message
    $message = $this->_get_emergency_message($emergency_type_name, $incident);
    error_log("Message prepared: " . substr($message, 0, 100) . "...");
    
    // Calculate credits
    $credits_needed = $this->_calculate_message_credits($message, count($recipients));
    error_log("Credits needed: " . $credits_needed);
    
    // Check credits
    $ci->load->model('sendsmsmail_model');
    $current_credits = $ci->sendsmsmail_model->get_sms_credit($data['branch_id']);
    error_log("Current credits: " . $current_credits);
    
    if ($current_credits < $credits_needed) {
        $return['message'] = "Insufficient credits for mass alert. Needed: {$credits_needed}, Available: {$current_credits}";
        error_log($return['message']);
        return $return;
    }
    
    // Get SMS gateway
    $sms_api = $ci->application_model->smsServiceProvider($data['branch_id']);
    error_log("SMS API: " . ($sms_api == 'disabled' ? 'DISABLED' : $sms_api));
    
    if ($sms_api == 'disabled') {
        $return['message'] = 'SMS gateway disabled for this branch';
        error_log($return['message']);
        return $return;
    }
    
    // Send in batches
    $batch_size = 20; // Smaller batch for testing
    $recipient_chunks = array_chunk($recipients, $batch_size);
    $sent_count = 0;
    $failed_count = 0;
    
    foreach ($recipient_chunks as $chunk_index => $chunk) {
        error_log("Processing batch " . ($chunk_index + 1) . " of " . count($recipient_chunks));
        
        foreach ($chunk as $recipient) {
            if (empty($recipient['phone'])) {
                $failed_count++;
                continue;
            }
            
            $mobile = preg_replace('/[^0-9]/', '', $recipient['phone']);

            error_log("Original mobile: " . $mobile);

            // Format Kenyan number correctly
            if (strlen($mobile) == 9) {
                // Local number like 707377945 -> add 254
                $mobile = '254' . $mobile;
                error_log("Formatted 9-digit: " . $mobile);
            } elseif (strlen($mobile) == 10 && substr($mobile, 0, 1) == '0') {
                // Number like 0707377945 -> replace 0 with 254
                $mobile = '254' . substr($mobile, 1);
                error_log("Formatted 10-digit starting with 0: " . $mobile);
            } elseif (strlen($mobile) == 12 && substr($mobile, 0, 3) == '254') {
                // Already correct format like 254707377945
                error_log("Already correct format: " . $mobile);
            } elseif (strlen($mobile) == 13 && substr($mobile, 0, 4) == '2547') {
                // Already correct
                error_log("Already correct format (13 digit): " . $mobile);
            } else {
                // Default: ensure 254 prefix
                if (substr($mobile, 0, 3) != '254') {
                    $mobile = '254' . ltrim($mobile, '0');
                }
                error_log("Default formatted: " . $mobile);
            }
            
            if ($this->is_successful_response($response)) {
                $sent_count++;
            } else {
                $failed_count++;
            }
            
            usleep(200000); // 0.2 second delay
        }
        
        // Wait between batches
        if ($chunk_index < count($recipient_chunks) - 1) {
            sleep(1);
        }
    }
    
    error_log("Mass alert completed - Sent: {$sent_count}, Failed: {$failed_count}");
    
    // Deduct credits
    if ($sent_count > 0) {
        $credits_to_deduct = ceil(($credits_needed * $sent_count) / count($recipients));
        $ci->sendsmsmail_model->deduct_sms_units($data['branch_id'], $credits_to_deduct);
        
        $this->_log_mass_alert($data['incident_id'], $data['branch_id'], $sent_count, $failed_count, $message);
    }
    
    $return['success'] = $sent_count > 0;
    $return['message'] = "Mass alert sent to {$sent_count}/" . count($recipients) . " recipients";
    $return['credits_used'] = $credits_to_deduct ?? 0;
    
    return $return;
}

/**
 * Get all recipients for mass alert
 */
/**
 * Get all recipients for mass alert
 */
private function _get_mass_alert_recipients($branch_id)
{
    $recipients = array();
    $ci = &get_instance();
    
    error_log("Getting mass alert recipients for branch: " . $branch_id);
    
    // Get all parents with valid mobile numbers
    $parents = $ci->db->select('p.id, p.name, p.mobileno')
        ->from('parent p')
        ->join('student s', 's.parent_id = p.id')
        ->where('s.branch_id', $branch_id)
        ->where('p.mobileno IS NOT NULL')
        ->where('p.mobileno !=', '')
        ->group_by('p.id')
        ->get()
        ->result_array();
    
    error_log("Parents found: " . count($parents));
    
    foreach ($parents as $parent) {
        if (!empty($parent['mobileno'])) {
            $recipients[$parent['mobileno']] = array(
                'name' => $parent['name'],
                'phone' => $parent['mobileno'],
                'type' => 'parent'
            );
        }
    }
    
    // Get all staff with valid mobile numbers
    $staff = $ci->db->select('id, name, mobileno')
        ->where('branch_id', $branch_id)
        ->where('mobileno IS NOT NULL')
        ->where('mobileno !=', '')
        ->get('staff')
        ->result_array();
    
    error_log("Staff found: " . count($staff));
    
    foreach ($staff as $s) {
        if (!empty($s['mobileno']) && !isset($recipients[$s['mobileno']])) {
            $recipients[$s['mobileno']] = array(
                'name' => $s['name'],
                'phone' => $s['mobileno'],
                'type' => 'staff'
            );
        }
    }
    
    error_log("Total unique recipients: " . count($recipients));
    
    return array_values($recipients);
}
/**
 * Get emergency message based on type
 */
private function _get_emergency_message($type, $incident)
{
    $school = $this->db->select('name, mobileno')->where('id', $incident['branch_id'])->get('branch')->row();
    
    switch($type) {
        case 'Fire Emergency':
            return "FIRE EMERGENCY at " . ($incident['hostel_name'] ?? 'hostel') . ".\n" .
                   "Location: " . ($incident['location_details'] ?? $incident['room_name']) . "\n" .
                   "Evacuate immediately to assembly point.\n" .
                   "Do not use elevators.\n" .
                   "Contact: " . ($school->mobileno ?? 'School Office');
        
        case 'Missing Student':
            return "MISSING STUDENT ALERT.\n" .
                   "Student: " . ($incident['first_name'] ?? 'Unknown') . " " . ($incident['last_name'] ?? '') . "\n" .
                   "Last seen: " . date('d M H:i', strtotime($incident['reported_at'])) . "\n" .
                   "Location: " . ($incident['location_details'] ?? $incident['room_name']) . "\n" .
                   "Please report any information to security immediately.\n" .
                   "Contact: " . ($school->mobileno ?? 'School Office');
        
        case 'Security Threat':
            return "SECURITY ALERT at " . ($incident['hostel_name'] ?? 'hostel') . ".\n" .
                   "Location: " . ($incident['location_details'] ?? $incident['room_name']) . "\n" .
                   "Remain in your rooms. Lock doors.\n" .
                   "Follow instructions from security personnel.\n" .
                   "Contact: " . ($school->mobileno ?? 'School Office');
        
        default:
            return "EMERGENCY ALERT: " . $incident['title'] . "\n" .
                   "Location: " . ($incident['location_details'] ?? $incident['room_name']) . "\n" .
                   "Follow instructions from staff.\n" .
                   "Contact: " . ($school->mobileno ?? 'School Office');
    }
}

/**
 * Log mass alert
 */
private function _log_mass_alert($incident_id, $branch_id, $sent, $failed, $message)
{
    $ci = &get_instance();
    
    $log_data = array(
        'incident_id' => $incident_id,
        'recipient_name' => 'MASS ALERT',
        'recipient_phone' => "Sent: {$sent}, Failed: {$failed}",
        'recipient_type' => 'mass_alert',
        'message_sent' => substr($message, 0, 500),
        'status' => $sent > 0 ? 'sent' : 'failed',
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $ci->db->insert('hostel_emergency_sms_logs', $log_data);
}

/**
 * Log detailed mass alert delivery
 */
private function _log_mass_alert_detailed($incident_id, $branch_id, $sent, $failed, $delivery_details, $message)
{
    $ci = &get_instance();
    
    // Log summary
    $log_data = array(
        'incident_id' => $incident_id,
        'recipient_name' => 'MASS ALERT',
        'recipient_phone' => "Sent: {$sent}, Failed: {$failed}",
        'recipient_type' => 'mass_alert',
        'message_sent' => substr($message, 0, 500),
        'status' => $sent > 0 ? 'sent' : 'failed',
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $ci->db->insert('hostel_emergency_sms_logs', $log_data);
    
    // Log each recipient separately for tracking
    foreach ($delivery_details as $detail) {
        $detail_log = array(
            'incident_id' => $incident_id,
            'recipient_name' => $detail['phone'],
            'recipient_phone' => $detail['phone'],
            'recipient_type' => 'mass_alert_recipient',
            'message_sent' => substr($message, 0, 200),
            'status' => $detail['status'],
            'gateway_response' => $detail['message_id'] ?? $detail['error'] ?? '',
            'sent_at' => $detail['time'],
            'branch_id' => $branch_id,
            'created_at' => date('Y-m-d H:i:s')
        );
        $ci->db->insert('hostel_emergency_sms_logs', $detail_log);
    }
}

}