<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Leave.php
 * @copyright : Reserved Synobix Team
 */

class Leave extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('leave_model');
        $this->load->model('email_model');
        $this->load->model('sendsmsmail_model');  // Load SMS model
        $this->load->model('sms_model');          // Load SMS dispatcher
    }

    public function index()
{
    if (!get_permission('leave_manage', 'is_view')) {
        access_denied();
    }

   
    // Keep only the filter and display logic

    $where = array();
    $branch_id = $this->application_model->get_branch_id();
    if (!empty($branch_id)) 
        $where['la.branch_id'] = $branch_id;
    if (isset($_POST['search'])) {
        $user_role = $this->input->post('role_id');
        $where['la.role_id'] = $user_role;
    }
    $this->data['title']            = translate('leave');
    $this->data['sub_page']         = 'leave/index';
    $this->data['leavelist']        = $this->leave_model->getLeaveList($where);
    $this->data['main_menu']        = 'leave';
    $this->data['headerelements']   = array(
        'css' => array(
            'vendor/dropify/css/dropify.min.css',
            'vendor/daterangepicker/daterangepicker.css',
        ),
        'js' => array(
            'vendor/dropify/js/dropify.min.js',
            'vendor/moment/moment.js',
            'vendor/daterangepicker/daterangepicker.js',
        ),
    );
    $this->load->view('layout/index', $this->data);
}

    // Save new leave application (Admin adds for staff/student)
    public function save()
    {
        if ($_POST) {
            if (!get_permission('leave_manage', 'is_add')) {
                access_denied();
            }
            if (is_superadmin_loggedin()) {
                $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
            }
            $this->form_validation->set_rules('user_role', translate('role'), 'trim|required');
            $this->form_validation->set_rules('applicant_id', translate('applicant'), 'trim|required');
            $this->form_validation->set_rules('leave_category', translate('leave_category'), 'required|callback_leave_check');
            $this->form_validation->set_rules('daterange', translate('leave_date'), 'trim|required|callback_date_check');
            $this->form_validation->set_rules('attachment_file', translate('attachment'), 'callback_handle_upload');
            if ($this->form_validation->run() !== false) {
                $applicant_id   = $this->input->post('applicant_id');
                $role_id        = $this->input->post('user_role');
                $leave_type_id  = $this->input->post('leave_category');
                $branch_id      = $this->application_model->get_branch_id();
                $daterange      = explode(' - ', $this->input->post('daterange'));
                $start_date     = date("Y-m-d", strtotime($daterange[0]));
                $end_date       = date("Y-m-d", strtotime($daterange[1]));
                $reason         = $this->input->post('reason');
                $comments       = $this->input->post('comments');
                $apply_date     = date("Y-m-d H:i:s");
                $datetime1      = new DateTime($start_date);
                $datetime2      = new DateTime($end_date);
                $leave_days     = $datetime2->diff($datetime1)->format("%a") + 1;
                $orig_file_name = '';
                $enc_file_name  = '';
                
                // upload attachment file
                if (isset($_FILES["attachment_file"]) && !empty($_FILES['attachment_file']['name'])) {
                    $config['upload_path']      = './uploads/attachments/leave/';
                    $config['allowed_types']    = "*";
                    $config['max_size']         = '2024';
                    $config['encrypt_name']     = true;
                    $this->upload->initialize($config);
                    $this->upload->do_upload("attachment_file");
                    $orig_file_name = $this->upload->data('orig_name');
                    $enc_file_name  = $this->upload->data('file_name');
                }
                
                $arrayData = array(
                    'user_id'           => $applicant_id,
                    'role_id'           => $role_id,
                    'branch_id'         => $branch_id,
                    'session_id'        => get_session_id(),
                    'category_id'       => $leave_type_id,
                    'reason'            => $reason,
                    'start_date'        => date("Y-m-d", strtotime($start_date)),
                    'end_date'          => date("Y-m-d", strtotime($end_date)),
                    'leave_days'        => $leave_days,
                    'status'            => 2, // Auto-approved when admin adds
                    'orig_file_name'    => $orig_file_name,
                    'enc_file_name'     => $enc_file_name,
                    'apply_date'        => $apply_date,
                    'approved_by'       => get_loggedin_user_id(),
                    'comments'          => $comments,
                );
                $this->db->insert('leave_application', $arrayData);
                
                // Send SMS notification for auto-approved leave
                $insert_id = $this->db->insert_id();
                $this->_send_leave_status_sms($insert_id, 2, $comments);
                
                set_alert('success', translate('information_has_been_saved_successfully'));
                $url    = base_url('leave');
                $array  = array('status' => 'success', 'url' => $url, 'error' => '');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    // Staff/Student submits their own leave request
    public function request()
    {
        if (!get_permission('leave_request', 'is_view')) {
            access_denied();
        }
        
        if (isset($_POST['save'])) {
            if (!get_permission('leave_request', 'is_add')) {
                access_denied();
            }
            $this->form_validation->set_rules('leave_category', translate('leave_category'), 'required|callback_leave_check');
            $this->form_validation->set_rules('daterange', translate('leave_date'), 'trim|required|callback_date_check');
            $this->form_validation->set_rules('attachment_file', translate('attachment'), 'callback_handle_upload');
            if ($this->form_validation->run() !== false) {
                $leave_type_id  = $this->input->post('leave_category');
                $branch_id      = $this->application_model->get_branch_id();
                $daterange      = explode(' - ', $this->input->post('daterange'));
                $start_date     = date("Y-m-d", strtotime($daterange[0]));
                $end_date       = date("Y-m-d", strtotime($daterange[1]));
                $reason         = $this->input->post('reason');
                $apply_date     = date("Y-m-d H:i:s");
                $datetime1      = new DateTime($start_date);
                $datetime2      = new DateTime($end_date);
                $leave_days     = $datetime2->diff($datetime1)->format("%a") + 1;
                $orig_file_name = '';
                $enc_file_name  = '';
                
                // upload attachment file
                if (isset($_FILES["attachment_file"]) && !empty($_FILES['attachment_file']['name'])) {
                    $config['upload_path']      = './uploads/attachments/leave/';
                    $config['allowed_types']    = "*";
                    $config['max_size']         = '2024';
                    $config['encrypt_name']     = true;
                    $this->upload->initialize($config);
                    $this->upload->do_upload("attachment_file");
                    $orig_file_name = $this->upload->data('orig_name');
                    $enc_file_name  = $this->upload->data('file_name');
                }
                
                $arrayData = array(
                    'user_id'           => get_loggedin_user_id(),
                    'role_id'           => loggedin_role_id(),
                    'session_id'        => get_session_id(),
                    'category_id'       => $leave_type_id,
                    'reason'            => $reason,
                    'branch_id'         => $branch_id,
                    'start_date'        => date("Y-m-d", strtotime($start_date)),
                    'end_date'          => date("Y-m-d", strtotime($end_date)),
                    'leave_days'        => $leave_days,
                    'status'            => 1, // Pending
                    'orig_file_name'    => $orig_file_name,
                    'enc_file_name'     => $enc_file_name,
                    'apply_date'        => $apply_date,
                );
                $this->db->insert('leave_application', $arrayData);
                $insert_id = $this->db->insert_id();
                
                // Send SMS notification to Admin about new leave request
                $this->_send_new_leave_alert_sms($insert_id);
                
                set_alert('success', translate('information_has_been_saved_successfully'));
                redirect(base_url('leave/request'));
            }
        }
        
        $where = array('la.user_id' => get_loggedin_user_id(), 'la.role_id' => loggedin_role_id());
        $this->data['leavelist'] = $this->leave_model->getLeaveList($where);
        $this->data['title'] = translate('leaves');
        $this->data['sub_page'] = 'leave/request';
        $this->data['main_menu'] = 'leave';
        $this->data['headerelements']   = array(
            'css' => array(
                'vendor/dropify/css/dropify.min.css',
                'vendor/daterangepicker/daterangepicker.css',
            ),
            'js' => array(
                'vendor/dropify/js/dropify.min.js',
                'vendor/moment/moment.js',
                'vendor/daterangepicker/daterangepicker.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }

    /**
     * Send SMS notification for leave status change (Approved/Rejected)
     * 
     * @param int $leave_id
     * @param int $status (2=approved, 3=rejected)
     * @param string $comments
     */
    private function _send_leave_status_sms($leave_id, $status, $comments = '')
    {
        $leave = $this->db->select('user_id, role_id, start_date, end_date, leave_days, reason, branch_id')
            ->where('id', $leave_id)
            ->get('leave_application')
            ->row();
        
        if (!$leave) {
            return;
        }
        
        $start_date = date('d M Y', strtotime($leave->start_date));
        $end_date = date('d M Y', strtotime($leave->end_date));
        $status_text = ($status == 2) ? 'APPROVED' : 'REJECTED';
        
        if ($leave->role_id == 7) {
            // ========== STUDENT LEAVE ==========
            // Send SMS to Parent (and optionally student)
            $studentData = $this->db->select('s.first_name, s.last_name, s.mobileno as student_mobile, 
                    p.name as parent_name, p.mobileno as parent_mobile, s.branch_id')
                ->from('student s')
                ->join('parent p', 'p.id = s.parent_id', 'left')
                ->where('s.id', $leave->user_id)
                ->get()
                ->row();
            
            if (!$studentData) {
                return;
            }
            
            $student_name = $studentData->first_name . ' ' . $studentData->last_name;
            
            // Message for Parent
            if ($status == 2) {
                $parent_message = "Dear {$studentData->parent_name}, Your child {$student_name}'s leave request from {$start_date} to {$end_date} has been APPROVED.";
            } else {
                $comment_text = !empty($comments) ? " Reason: {$comments}" : "";
                $parent_message = "Dear {$studentData->parent_name}, Your child {$student_name}'s leave request from {$start_date} to {$end_date} has been REJECTED.{$comment_text}";
            }
            
            // Send to Parent
            if (!empty($studentData->parent_mobile)) {
                $this->_send_sms($studentData->parent_mobile, $parent_message, $studentData->branch_id);
            }
            
            // Also send to Student if they have mobile
            if (!empty($studentData->student_mobile)) {
                if ($status == 2) {
                    $student_message = "Dear {$student_name}, Your leave request from {$start_date} to {$end_date} has been APPROVED.";
                } else {
                    $comment_text = !empty($comments) ? " Reason: {$comments}" : "";
                    $student_message = "Dear {$student_name}, Your leave request from {$start_date} to {$end_date} has been REJECTED.{$comment_text}";
                }
                $this->_send_sms($studentData->student_mobile, $student_message, $studentData->branch_id);
            }
            
        } else {
            // ========== STAFF LEAVE ==========
            $staffData = $this->db->select('name, mobileno, email, branch_id')
                ->where('id', $leave->user_id)
                ->get('staff')
                ->row();
            
            if (!$staffData || empty($staffData->mobileno)) {
                return;
            }
            
            if ($status == 2) {
                $message = "Dear {$staffData->name}, Your leave request from {$start_date} to {$end_date} ({$leave->leave_days} days) has been APPROVED.";
            } else {
                $comment_text = !empty($comments) ? " Reason: {$comments}" : "";
                $message = "Dear {$staffData->name}, Your leave request from {$start_date} to {$end_date} ({$leave->leave_days} days) has been REJECTED.{$comment_text}";
            }
            
            $this->_send_sms($staffData->mobileno, $message, $staffData->branch_id);
        }
    }

    /**
     * Send SMS alert to Admin when new leave is applied
     * 
     * @param int $leave_id
     */
    private function _send_new_leave_alert_sms($leave_id)
    {
        $leave = $this->db->select('l.*, 
                CASE 
                    WHEN l.role_id = 7 THEN CONCAT_WS(" ", s.first_name, s.last_name)
                    ELSE st.name
                END as applicant_name,
                l.role_id,
                l.branch_id')
            ->from('leave_application l')
            ->join('staff st', 'st.id = l.user_id AND l.role_id != 7', 'left')
            ->join('student s', 's.id = l.user_id AND l.role_id = 7', 'left')
            ->where('l.id', $leave_id)
            ->get()
            ->row();
        
        if (!$leave) {
            return;
        }
        
        $start_date = date('d M Y', strtotime($leave->start_date));
        $end_date = date('d M Y', strtotime($leave->end_date));
        $applicant_type = ($leave->role_id == 7) ? 'Student' : 'Staff';
        
        $message = "New Leave Request: {$applicant_type} {$leave->applicant_name} applied for leave from {$start_date} to {$end_date}. Reason: {$leave->reason}";
        
        // Get Admin/HR mobile numbers for this branch
        $admins = $this->db->select('staff.id, staff.name, staff.mobileno')
            ->from('staff')
            ->join('login_credential', 'login_credential.user_id = staff.id')
            ->where('staff.branch_id', $leave->branch_id)
            ->where('login_credential.role', 2) // Admin/Manager role
            ->where('staff.mobileno !=', '')
            ->get()
            ->result();
        
        foreach ($admins as $admin) {
            if (!empty($admin->mobileno)) {
                $this->_send_sms($admin->mobileno, $message, $leave->branch_id);
            }
        }
    }

    /**
     * Send upcoming leave reminder (to be called by cron job)
     * 
     * @param int $branch_id
     */
    public function send_upcoming_leave_reminders($branch_id = null)
    {
        $upcoming_leaves = $this->leave_model->get_upcoming_leaves($branch_id);
        
        foreach ($upcoming_leaves as $leave) {
            $start_date = date('d M Y', strtotime($leave['start_date']));
            $message = "Reminder: Your leave starts tomorrow ({$start_date}). Please ensure all handover is complete.";
            
            if ($leave['role_id'] == 7) {
                // Student - send to parent
                $studentData = $this->db->select('s.first_name, s.last_name, p.mobileno as parent_mobile')
                    ->from('student s')
                    ->join('parent p', 'p.id = s.parent_id', 'left')
                    ->where('s.id', $leave['user_id'])
                    ->get()
                    ->row();
                
                if ($studentData && !empty($studentData->parent_mobile)) {
                    $student_name = $studentData->first_name . ' ' . $studentData->last_name;
                    $parent_message = "Reminder: Your child {$student_name}'s leave starts tomorrow ({$start_date}).";
                    $this->_send_sms($studentData->parent_mobile, $parent_message, $leave['branch_id']);
                }
            } else {
                // Staff - send to staff
                $staffData = $this->db->select('name, mobileno')
                    ->where('id', $leave['user_id'])
                    ->get('staff')
                    ->row();
                
                if ($staffData && !empty($staffData->mobileno)) {
                    $staff_message = "Reminder: Dear {$staffData->name}, your leave starts tomorrow ({$start_date}).";
                    $this->_send_sms($staffData->mobileno, $staff_message, $leave['branch_id']);
                }
            }
        }
    }

    /**
     * Send return from leave reminder (to be called by cron job)
     * 
     * @param int $branch_id
     */
    public function send_return_reminders($branch_id = null)
    {
        $returning_leaves = $this->leave_model->get_today_returning_leaves($branch_id);
        
        foreach ($returning_leaves as $leave) {
            $message = "Reminder: You are expected to resume duty today as your leave ends today.";
            
            if ($leave['role_id'] == 7) {
                // Student - send to parent
                $studentData = $this->db->select('s.first_name, s.last_name, p.mobileno as parent_mobile')
                    ->from('student s')
                    ->join('parent p', 'p.id = s.parent_id', 'left')
                    ->where('s.id', $leave['user_id'])
                    ->get()
                    ->row();
                
                if ($studentData && !empty($studentData->parent_mobile)) {
                    $student_name = $studentData->first_name . ' ' . $studentData->last_name;
                    $parent_message = "Reminder: Your child {$student_name} is expected to return to school today as leave ends.";
                    $this->_send_sms($studentData->parent_mobile, $parent_message, $leave['branch_id']);
                }
            } else {
                // Staff - send to staff
                $staffData = $this->db->select('name, mobileno')
                    ->where('id', $leave['user_id'])
                    ->get('staff')
                    ->row();
                
                if ($staffData && !empty($staffData->mobileno)) {
                    $staff_message = "Reminder: Dear {$staffData->name}, you are expected to resume duty today as your leave ends.";
                    $this->_send_sms($staffData->mobileno, $staff_message, $leave['branch_id']);
                }
            }
        }
    }

    /**
     * Core SMS sending function with credit checking
     * 
     * @param string $mobile
     * @param string $message
     * @param int $branch_id
     * @return bool
     */
    private function _send_sms($mobile, $message, $branch_id)
    {
        if (empty($mobile) || empty($message)) {
            // log_message('error', "Leave SMS: Empty mobile or message");
            return false;
        }
        
        // Clean mobile number
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        if (strlen($mobile) < 9) {
            // log_message('error', "Leave SMS: Invalid mobile number: {$mobile}");
            return false;
        }
        
        // Get active SMS gateway for this branch
        $sms_credential = $this->db->select('sms_api.name as gateway')
            ->from('sms_credential')
            ->join('sms_api', 'sms_api.id = sms_credential.sms_api_id')
            ->where('sms_credential.branch_id', $branch_id)
            ->where('sms_credential.is_active', 1)
            ->get()
            ->row();
        
        $gateway = ($sms_credential && $sms_credential->gateway) ? $sms_credential->gateway : 'bulksmsbd';
        
        // Calculate required credits
        $message_length = strlen($message);
        $is_unicode = preg_match('/[^\x00-\x7F]/', $message);
        $chars_per_sms = $is_unicode ? 70 : 160;
        $sms_parts = ceil($message_length / $chars_per_sms);
        $credits_needed = $sms_parts * ($is_unicode ? 2 : 1);
        
        // Check available credits
        $current_credits = $this->sendsmsmail_model->get_sms_credit($branch_id);
        
        if ($current_credits < $credits_needed) {
            // log_message('warning', "Leave SMS: Insufficient credits. Branch: {$branch_id}, Needed: {$credits_needed}, Available: {$current_credits}");
            return false;
        }
        
        // Get applicant name for template replacement
        $name = '';
        if (strpos($message, '{name}') !== false) {
            // Extract name from message or use default
            $name = 'User';
        }
        
        // Send SMS using credit-checked method
        try {
            $result = $this->sendsmsmail_model->sendSMS_with_credit_check(
                $mobile,
                $message,
                $name,
                '', // email not needed
                $gateway,
                '', // DLT template ID
                $branch_id
            );
            
            if ($result) {
                // log_message('info', "Leave SMS sent successfully to: {$mobile}");
                
                // Log delivery
                $this->db->insert('sms_email_delivery_logs', array(
                    'recipient_contact' => $mobile,
                    'status' => 'sent',
                    'sent_at' => date('Y-m-d H:i:s'),
                    'branch_id' => $branch_id,
                    'gateway_response' => 'Leave module notification'
                ));
            } else {
                // log_message('error', "Leave SMS failed to: {$mobile}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            // log_message('error', "Leave SMS Exception: " . $e->getMessage());
            return false;
        }
    }

    // Other existing methods (delete, date_check, leave_check, getRequestDetails, 
    // request_delete, category, category_edit, category_delete, getCategory, 
    // unique_category, handle_upload, download, user_leave_days, reports)
    // Keep all your existing methods below exactly as they are...
    
    public function delete($id = '')
    {
        if (get_permission('leave_manage', 'is_delete')) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('leave_application');
        }
    }

    public function date_check($daterange)
    {
        $daterange = explode(' - ', $daterange);
        $start_date = date("Y-m-d", strtotime($daterange[0]));
        $end_date = date("Y-m-d", strtotime($daterange[1]));

        $today = date('Y-m-d');
        if ($today == $start_date) {
            $this->form_validation->set_message('date_check', "You can not leave the current day.");
            return false;
        }
        if ($this->input->post('applicant_id')) {
            $applicant_id = $this->input->post('applicant_id');
            $role_id = $this->input->post('user_role');
        } else {
            $applicant_id = get_loggedin_user_id();
            $role_id = loggedin_role_id();
        }
        $getUserLeaves = $this->db->get_where('leave_application', array('user_id' => $applicant_id, 'role_id' => $role_id))->result();
        if (!empty($getUserLeaves)) {
            foreach ($getUserLeaves as $user_leave) {
                $get_dates = $this->user_leave_days($user_leave->start_date, $user_leave->end_date);
                $result_start = in_array($start_date, $get_dates);
                $result_end = in_array($end_date, $get_dates);
                if (!empty($result_start) || !empty($result_end)) {
                    $this->form_validation->set_message('date_check', 'Already have leave in the selected time.');
                    return false;
                }
            }
        }
        return true;
    }

    public function leave_check($type_id)
    {
        if (!empty($type_id)) {
            $daterange = explode(' - ', $this->input->post('daterange'));
            $start_date = date("Y-m-d", strtotime($daterange[0]));
            $end_date = date("Y-m-d", strtotime($daterange[1]));

            if ($this->input->post('applicant_id')) {
                $applicant_id = $this->input->post('applicant_id');
                $role_id = $this->input->post('user_role');
            } else {
                $applicant_id = get_loggedin_user_id();
                $role_id = loggedin_role_id();
            }
            if (!empty($start_date) && !empty($end_date)) {
                $leave_total = get_type_name_by_id('leave_category', $type_id, 'days');
                $total_spent = $this->db->select('IFNULL(SUM(leave_days), 0) as total_days')
                    ->where(array('user_id' => $applicant_id, 'role_id' => $role_id, 'category_id' => $type_id, 'status' => '2'))
                    ->get('leave_application')->row()->total_days;

                $datetime1 = new DateTime($start_date);
                $datetime2 = new DateTime($end_date);
                $leave_days = $datetime2->diff($datetime1)->format("%a") + 1;
                $left_leave = ($leave_total - $total_spent);
                if ($left_leave < $leave_days) {
                    $this->form_validation->set_message('leave_check', "Applyed for $leave_days days, get maximum $left_leave Days days.");
                    return false;
                } else {
                    return true;
                }
            } else {
                $this->form_validation->set_message('leave_check', "Select all required field.");
                return false;
            }
        }
    }

    public function getRequestDetails()
    {
        $this->data['leave_id'] = $this->input->post('id');
        $this->load->view('leave/modal_request_details', $this->data);
    }

    public function request_delete($id = '')
    {
        $where = array(
            'status' => 1,
            'user_id' => get_loggedin_user_id(),
            'role_id' => loggedin_role_id(),
            'id' => $id,
        );
        $app = $this->db->where($where)->get('leave_application')->row_array();
        $file_name = FCPATH . 'uploads/attachments/leave/' . $app['enc_file_name'];
        if (file_exists($file_name)) {
            unlink($file_name);
        }
        $this->db->where($where)->delete('leave_application');
    }

    protected function category_validation()
    {
        if (is_superadmin_loggedin()){
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('leave_category', translate('leave_category'), 'trim|required|callback_unique_category');
        $this->form_validation->set_rules('leave_days', translate('leave_days'), 'trim|required');
        $this->form_validation->set_rules('role_id', translate('role'), 'trim|required');
    }

    public function category()
    {
        if (isset($_POST['save'])) {
            if (!get_permission('leave_category', 'is_add')) {
                access_denied();
            }
            $this->category_validation();
            if ($this->form_validation->run() !== false) {
                $arrayData = array(
                    'branch_id' => $this->application_model->get_branch_id(),
                    'name' => $this->input->post('leave_category'),
                    'role_id' => $this->input->post('role_id'),
                    'days' => $this->input->post('leave_days'),
                );
                $this->db->insert('leave_category', $arrayData);
                set_alert('success', translate('information_has_been_saved_successfully'));
                redirect(base_url('leave/category'));
            }
        }
        $this->data['title'] = translate('leave');
        $this->data['category'] = $this->app_lib->getTable('leave_category');
        $this->data['sub_page'] = 'leave/category';
        $this->data['main_menu'] = 'leave';
        $this->load->view('layout/index', $this->data);
    }

    public function category_edit()
    {
        if (!get_permission('leave_category', 'is_edit')) {
            ajax_access_denied();
        }
        $this->category_validation();
        if ($this->form_validation->run() !== false) {
            $category_id = $this->input->post('category_id');
            $arrayData = array(
                'branch_id' => $this->application_model->get_branch_id(),
                'name' => $this->input->post('leave_category'),
                'role_id' => $this->input->post('role_id'),
                'days' => $this->input->post('leave_days'),
            );
            $this->db->where('id', $category_id);
            $this->db->update('leave_category', $arrayData);
            set_alert('success', translate('information_has_been_updated_successfully'));
            $array  = array('status' => 'success');
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail','error' => $error);
        }
        echo json_encode($array);
    }

    public function category_delete($id = '')
    {
        if (!get_permission('leave_category', 'is_delete')) {
            access_denied();
        }
        if (!is_superadmin_loggedin()){
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $this->db->where('id', $id);
        $this->db->delete('leave_category');
    }

    public function getCategory()
    {
        $html = "";
        $roleID = $this->input->post("role_id");
        $branchID = $this->application_model->get_branch_id();
        if (!empty($roleID) && !empty($branchID)) {
            $query = $this->db->select('id,name,days')
            ->where(array('branch_id' => $branchID, 'role_id' => $roleID))
            ->get('leave_category');
            if ($query->num_rows() != 0) {
                $html .= '<option value="">' . translate('select') . '</option>';
                $sections = $query->result_array();
                foreach ($sections as $row) {
                    $html .= '<option value="' . $row['id'] . '">' . $row['name'] . ' (' . $row['days'] . ')' . '</option>';
                }
            } else {
                $html .= '<option value="">' . translate('no_information_available') . '</option>';
            }
        } else {
            $html .= '<option value="">' . translate('select') . '</option>';
        }
        echo $html;
    }

    public function unique_category($name)
    {
        $category_id = $this->input->post('category_id');
        $role_id = $this->input->post('role_id');
        $branch_id = $this->application_model->get_branch_id();
        if (!empty($category_id)) {
            $this->db->where_not_in('id', $category_id);
        }
        $this->db->where('name', $name);
        $this->db->where('role_id', $role_id);
        $this->db->where('branch_id', $branch_id);
        $query = $this->db->get('leave_category');
        if ($query->num_rows() > 0) {
            if (!empty($category_id)) {
                set_alert('error', "The Category name are already used");
            } else {
                $this->form_validation->set_message("unique_category", translate('already_taken'));
            }
            return false;
        } else {
            return true;
        }
    }

    public function handle_upload()
    {
        if (isset($_FILES["attachment_file"]) && !empty($_FILES['attachment_file']['name'])) {
            $file_type      = $_FILES["attachment_file"]['type'];
            $file_size      = $_FILES["attachment_file"]["size"];
            $file_name      = $_FILES["attachment_file"]["name"];
            $allowedExts    = array('pdf','doc','xls','docx','xlsx','jpg','jpeg','png','gif','bmp');
            $upload_size    = 2097152;
            $extension      = pathinfo($file_name, PATHINFO_EXTENSION);
            if ($files = filesize($_FILES['attachment_file']['tmp_name'])) {
                if (!in_array(strtolower($extension), $allowedExts)) {
                    $this->form_validation->set_message('handle_upload', translate('this_file_type_is_not_allowed'));
                    return false;
                }
                if ($file_size > $upload_size) {
                    $this->form_validation->set_message('handle_upload', translate('file_size_shoud_be_less_than') . " " . ($upload_size / 1024) . " KB");
                    return false;
                }
            } else {
                $this->form_validation->set_message('handle_upload', translate('error_reading_the_file'));
                return false;
            }
            return true;
        } else {
            return true;
        }
    }

    public function download($id = '', $file = '')
    {
        if (!empty($id) && !empty($file)) {
            $this->db->select('orig_file_name,enc_file_name');
            $this->db->where('id', $id);
            $leave = $this->db->get('leave_application')->row();
            if ($file != $leave->enc_file_name) {
                access_denied();
            }
            $this->load->helper('download');
            $fileData = file_get_contents('./uploads/attachments/leave/' . $leave->enc_file_name);
            force_download($leave->orig_file_name, $fileData);
        }
    }

    public function user_leave_days($start_date, $end_date)
    {
        $dates      = array();
        $current    = strtotime($start_date);
        $end_date   = strtotime($end_date);
        while ($current <= $end_date) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }
        return $dates;
    }

    public function reports()
    {
        if (!get_permission('leave_reports', 'is_view')) {
            access_denied();
        }

        $where = array();
        $branch_id = $this->application_model->get_branch_id();
        if (!empty($branch_id)) 
            $where['la.branch_id'] = $branch_id;
        if (isset($_POST['search'])) {
            $userRole = $this->input->post('role_id');
            $daterange = explode(' - ', $this->input->post('daterange'));
            $start = date("Y-m-d", strtotime($daterange[0]));
            $end = date("Y-m-d", strtotime($daterange[1]));
            $where['la.start_date >='] = $start;
            $where['la.start_date <='] = $end;
            $where['la.role_id'] = $userRole;
            $this->data['leavelist'] = $this->leave_model->getLeaveList($where);
        }

        $this->data['title'] = translate('leave');
        $this->data['sub_page'] = 'leave/reports';
        $this->data['main_menu'] = 'leave_reports';
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

    // get add leave modal
    public function getApprovelLeaveDetails()
    {
        if (get_permission('leave_manage', 'is_add')) {
            $this->data['leave_id'] = $this->input->post('id');
            $this->load->view('leave/approvel_modalView', $this->data);
        }
    }
           public function update_leave_status()
    {
        // Set JSON header
        $this->output->set_content_type('application/json');
        
        // Check CSRF token
        $csrf_token = $this->input->post($this->security->get_csrf_token_name());
        if ($csrf_token !== $this->security->get_csrf_hash()) {
            echo json_encode(['status' => 'error', 'message' => 'Security token validation failed']);
            return;
        }
        
        // Check permission
        if (!get_permission('leave_manage', 'is_add')) {
            echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
            return;
        }
        
        $id = $this->input->post('id');
        $status = $this->input->post('status');
        $comments = $this->input->post('comments');
        
        if (empty($id) || empty($status)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
            return;
        }
        
        // Update leave application
        $arrayLeave = array(
            'approved_by' => get_loggedin_user_id(),
            'status' => $status,
            'comments' => $comments,
        );
        
        $this->db->where('id', $id);
        $this->db->update('leave_application', $arrayLeave);
        
        // Get leave data for notifications
        $leaveData = $this->db->select('user_id, role_id, start_date, end_date, comments, reason, leave_days')
            ->where('id', $id)
            ->get('leave_application')
            ->row();
        
        if ($leaveData) {
            // Send email notification
            if ($leaveData->role_id == 7) {
                $getApplicant = $this->db->select('email, concat(first_name," ", last_name) as name')
                    ->where('id', $leaveData->user_id)
                    ->get('student')
                    ->row();
            } else {
                $getApplicant = $this->db->select('email, name')
                    ->where('id', $leaveData->user_id)
                    ->get('staff')
                    ->row();
            }
            
            if ($getApplicant) {
                $emailData = array(
                    'applicant'    => $getApplicant->name,
                    'email'        => $getApplicant->email,
                    'start_date'   => $leaveData->start_date,
                    'end_date'     => $leaveData->end_date,
                    'comments'     => $leaveData->comments,
                    'reason'       => $leaveData->reason,
                    'leave_days'   => $leaveData->leave_days,
                    'status'       => $status,
                );
                $this->email_model->sentLeaveRequest($emailData);
            }
            
            // Send SMS notification
            if (method_exists($this, '_send_leave_status_sms')) {
                $this->_send_leave_status_sms($id, $status, $comments);
            }
        }
        
        // Regenerate CSRF token for next request
        $this->security->get_csrf_hash();
        
        echo json_encode(['status' => 'success', 'message' => 'Leave status updated successfully']);
    }
}