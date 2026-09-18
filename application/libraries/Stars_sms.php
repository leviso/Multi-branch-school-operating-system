<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Stars_sms
{
    private $ci;
    
    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('stars_model');
        $this->ci->load->model('sendsmsmail_model');
    }
    
    /**
     * Send IARP created SMS (Template 22)
     */
    public function send_iarp_created($iarp_id, $branch_id)
    {
         log_message('info', 'Stars_sms::send_iarp_created - IARP ID: ' . $iarp_id . ', Branch ID: ' . $branch_id);
    
        if (empty($branch_id)) {
            log_message('error', 'Branch ID is empty, cannot send SMS');
            return false;
        }
        
        // Get student and parent details
        $student_parent = $this->get_student_parent($iarp_id);
        
        if (empty($student_parent) || empty($student_parent['parent_mobile'])) {
            log_message('info', 'No parent mobile for IARP ' . $iarp_id);
            return false;
        }
        
        // Get IARP details
        $iarp = $this->get_iarp_details($iarp_id);
        
        // Get SMS template (ID 22)
        $template = $this->get_template(22, $branch_id);
        
        if (empty($template)) {
            log_message('error', 'Template 22 not found for branch ' . $branch_id);
            return false;
        }
        
        if ($template['notify_parent'] != 1) {
            log_message('info', 'Parent notification disabled for template 22');
            return false;
        }
        
        // Prepare message
        $message = $template['template_body'];
        $message = str_replace('{student_name}', $student_parent['student_name'], $message);
        $message = str_replace('{guardian_name}', $student_parent['parent_name'], $message);
        $message = str_replace('{plan_code}', $iarp['plan_code'], $message);
        $message = str_replace('{start_date}', date('d M Y', strtotime($iarp['start_date'])), $message);
        $message = str_replace('{target_end_date}', date('d M Y', strtotime($iarp['target_end_date'])), $message);
        $message = str_replace('{recovery_hours}', $iarp['recovery_hours'], $message);
        
        // Get school phone
        $branch = $this->get_branch($branch_id);
        $message = str_replace('{school_phone}', $branch['mobileno'] ?? 'School Office', $message);
        
        // Send SMS
        return $this->send($branch_id, $student_parent['parent_mobile'], $message, $template['dlt_template_id'] ?? '');
    }
    
    /**
     * Send recovery completion SMS (Template 24)
     */
    public function send_completion($iarp_id, $branch_id)
    {
        log_message('info', 'Stars_sms::send_completion - IARP ID: ' . $iarp_id . ', Branch: ' . $branch_id);
        
        // Get student and parent details
        $student_parent = $this->get_student_parent($iarp_id);
        
        if (empty($student_parent) || empty($student_parent['parent_mobile'])) {
            log_message('info', 'No parent mobile for IARP ' . $iarp_id);
            return false;
        }
        
        // Get completion stats
        $stats = $this->get_completion_stats($iarp_id);
        
        // Get SMS template (ID 24)
        $template = $this->get_template(24, $branch_id);
        
        if (empty($template)) {
            log_message('error', 'Template 24 not found for branch ' . $branch_id);
            return false;
        }
        
        if ($template['notify_parent'] != 1) {
            log_message('info', 'Parent notification disabled for template 24');
            return false;
        }
        
        // Prepare message
        $message = $template['template_body'];
        $message = str_replace('{student_name}', $student_parent['student_name'], $message);
        $message = str_replace('{guardian_name}', $student_parent['parent_name'], $message);
        $message = str_replace('{completion_date}', date('d M Y'), $message);
        $message = str_replace('{achievement_summary}', "Completed {$stats['completed']} of {$stats['total']} topics", $message);
        
        $branch = $this->get_branch($branch_id);
        $message = str_replace('{school_name}', $branch['name'] ?? 'School', $message);
        
        // Send SMS
        return $this->send($branch_id, $student_parent['parent_mobile'], $message, $template['dlt_template_id'] ?? '');
    }
    
    /**
     * Send SMS with credit check and deduction
     */
    private function send($branch_id, $mobile, $message, $dlt_template_id = '')
    {
        // Calculate credits needed (160 characters per credit)
        $credits_needed = ceil(strlen($message) / 160);
        
        // Check credits
        $available_credits = $this->ci->sendsmsmail_model->get_sms_credit($branch_id);
        
        if ($available_credits < $credits_needed) {
            log_message('error', "Insufficient SMS credits. Need {$credits_needed}, Available {$available_credits}");
            return false;
        }
        
        // Check SMS gateway
        $sms_api = $this->ci->application_model->smsServiceProvider($branch_id);
        if ($sms_api == 'disabled') {
            log_message('error', 'SMS gateway disabled');
            return false;
        }
        
        // Clean mobile number
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        // Send SMS using your existing bulksmsbd library
        $this->ci->load->library('bulksmsbd', ['branch_id' => $branch_id], 'sms_lib');
        $response = $this->ci->sms_lib->send($mobile, $message);
        
        // Check if successful
        $success = $this->is_successful($response);
        
        if ($success) {
            // Deduct credits
            $this->ci->sendsmsmail_model->deduct_sms_units($branch_id, $credits_needed);
            log_message('info', "SMS sent to {$mobile}, credits deducted: {$credits_needed}");
        } else {
            log_message('error', "SMS failed: {$response}");
        }
        
        return $success;
    }
    
    /**
     * Get student and parent details for IARP
     */
    private function get_student_parent($iarp_id)
    {
        $sql = "SELECT s.id as student_id, 
                       CONCAT(s.first_name, ' ', s.last_name) as student_name,
                       p.name as parent_name, 
                       p.mobileno as parent_mobile
                FROM iarp_plans i
                JOIN student s ON s.id = i.student_id
                LEFT JOIN parent p ON p.id = s.parent_id
                WHERE i.id = ?";
        
        $query = $this->ci->db->query($sql, array($iarp_id));
        return $query->row_array();
    }
    
    /**
     * Get IARP details
     */
    private function get_iarp_details($iarp_id)
    {
        $sql = "SELECT plan_code, start_date, target_end_date,
                       (SELECT COUNT(*) FROM iarp_missing_topics WHERE iarp_id = i.id) * 1.5 as recovery_hours
                FROM iarp_plans i
                WHERE i.id = ?";
        
        $query = $this->ci->db->query($sql, array($iarp_id));
        return $query->row_array();
    }
    
    /**
     * Get completion statistics
     */
    private function get_completion_stats($iarp_id)
    {
        $total = $this->ci->db->where('iarp_id', $iarp_id)->count_all_results('iarp_missing_topics');
        $completed = $this->ci->db->where('iarp_id', $iarp_id)
                              ->where('status', 'completed')
                              ->count_all_results('iarp_missing_topics');
        
        return array('total' => $total, 'completed' => $completed);
    }
    
    /**
     * Get SMS template
     */
   private function get_template($template_id, $branch_id)
    {
        log_message('info', 'Getting template - Template ID: ' . $template_id . ', Branch ID: ' . $branch_id);
        
        $this->ci->db->where('template_id', $template_id);
        $this->ci->db->where('branch_id', $branch_id);
        $query = $this->ci->db->get('sms_template_details');
        
        log_message('info', 'Template found: ' . $query->num_rows());
        
        return $query->row_array();
    }
        
    /**
     * Get branch details
     */
    private function get_branch($branch_id)
    {
        $this->ci->db->select('name, mobileno, email');
        $this->ci->db->where('id', $branch_id);
        $query = $this->ci->db->get('branch');
        return $query->row_array();
    }
    
    /**
     * Check if SMS response is successful
     */
    private function is_successful($response)
    {
        if (is_string($response)) {
            $success_indicators = ['success', 'sent', 'delivered', 'accepted', '200', '202', 'MessageID'];
            foreach ($success_indicators as $indicator) {
                if (stripos($response, $indicator) !== false) {
                    return true;
                }
            }
        }
        
        if (is_array($response)) {
            return isset($response['success']) && $response['success'] == true;
        }
        
        return false;
    }
}