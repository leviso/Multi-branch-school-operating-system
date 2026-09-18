<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Homework.php
 * @copyright : Reserved Synobix Team
 */

class Homework extends Admin_Controller
{

    public function __construct()
        {
            parent::__construct();
            $this->load->model('homework_model');
            $this->load->model('subject_model');
            $this->load->model('sms_model'); // Keep for backward compatibility
            $this->load->model('sendsmsmail_model'); // ADD THIS for credit system
            $this->load->model('application_model');
            if (!moduleIsEnabled('homework')) {
                access_denied();
            }
        }

    public function index()
    {
        // check access permission
        if (!get_permission('homework', 'is_view')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();
        if ($_POST) {
            $classID = $this->input->post('class_id');
            $sectionID = $this->input->post('section_id');
            $subjectID = $this->input->post('subject_id');
            $this->data['homeworklist'] = $this->homework_model->getList($classID, $sectionID, $subjectID, $branchID);
        }
        
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('homework');
        $this->data['sub_page'] = 'homework/index';
        $this->data['main_menu'] = 'homework';
        $this->load->view('layout/index', $this->data);
    }

public function add()
{
    if (!get_permission('homework', 'is_add')) {
        access_denied();
    }

    if ($_POST) {
        $this->homework_validation();
        if ($this->form_validation->run() !== false) {
            $post = $this->input->post();
            
            // AJAX credit check already done in view, so we proceed
            // Let the model handle any SMS credit issues internally
            
            // Save homework (model will handle SMS if notification_sms is set)
            $this->homework_model->save($post);
            
            // Check if there's a session error from model (for insufficient credits)
            if ($this->session->flashdata('homework_sms_error')) {
                $error_message = $this->session->flashdata('homework_sms_error');
                $array = array(
                    'status' => 'fail',
                    'error' => array('sms_credits' => $error_message)
                );
                echo json_encode($array);
                exit();
            }
            
            set_alert('success', translate('information_has_been_saved_successfully'));
            $url = base_url('homework');
            $array = array('status' => 'success', 'url' => $url);
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail','error' => $error);
        }
        echo json_encode($array);
        exit();
    }
    
    $this->data['branch_id'] = $this->application_model->get_branch_id();
    $this->data['title'] = translate('homework');
    $this->data['sub_page'] = 'homework/add';
    $this->data['main_menu'] = 'homework';
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/summernote/summernote.css',
            'vendor/bootstrap-fileupload/bootstrap-fileupload.min.css',
        ),
        'js' => array(
            'vendor/summernote/summernote.js',
            'vendor/bootstrap-fileupload/bootstrap-fileupload.min.js',
        ),
    );
    $this->load->view('layout/index', $this->data);
}

/**
 * Internal credit check method (not for AJAX)
 */
private function check_homework_sms_credits_internal($branch_id, $class_id, $section_id)
{
    // Get student count
    $students = $this->application_model->getStudentListByClassSection($class_id, $section_id, $branch_id);
    $student_count = count($students);
    
    if ($student_count == 0) {
        return ['success' => true];
    }
    
    // Get SMS template
    $template = $this->db->get_where('sms_template_details', array(
        'template_id' => 6,
        'branch_id' => $branch_id
    ))->row_array();
    
    if (empty($template)) {
        return ['success' => false, 'message' => 'SMS template not configured'];
    }
    
    // Calculate total recipients
    $total_recipients = 0;
    if ($template['notify_student'] == 1) {
        $total_recipients += $student_count;
    }
    if ($template['notify_parent'] == 1) {
        $total_recipients += $student_count;
    }
    
    // Calculate required credits
    $credits_needed = $this->calculate_homework_sms_cost($template['template_body'], $total_recipients);
    
    // Check current credits
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branch_id);
    
    if ($current_credits < $credits_needed) {
        return [
            'success' => false,
            'message' => 'Insufficient SMS credits. You need ' . $credits_needed . ' credits but only have ' . $current_credits . ' available.'
        ];
    }
    
    return ['success' => true];
}

    public function edit($id='')
    {
        if (!get_permission('homework', 'is_edit')) {
            access_denied();
        }
        
        if ($_POST) {
            $this->homework_validation();
            if ($this->form_validation->run() !== false) {
                $post = $this->input->post();
                $response = $this->homework_model->save($post);
                set_alert('success', translate('information_has_been_updated_successfully'));
                $url = base_url('homework');
                $array = array('status' => 'success', 'url' => $url);
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail','error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        
        $this->data['homework'] = $this->app_lib->getTable('homework', array('t.id' => $id), true);
        $this->data['branch_id'] = $this->application_model->get_branch_id();;
        $this->data['title'] = translate('homework');
        $this->data['sub_page'] = 'homework/edit';
        $this->data['main_menu'] = 'homework';
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/summernote/summernote.css',
                'vendor/bootstrap-fileupload/bootstrap-fileupload.min.css',
            ),
            'js' => array(
                'vendor/summernote/summernote.js',
                'vendor/bootstrap-fileupload/bootstrap-fileupload.min.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }

    public function evaluate($id='')
    {
        // check access permission
        if (!get_permission('homework_evaluate', 'is_view')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();
        $this->data['homeworklist'] = $this->homework_model->getEvaluate($id);
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('homework');
        $this->data['sub_page'] = 'homework/evaluate_list';
        $this->data['main_menu'] = 'homework';
        $this->load->view('layout/index', $this->data);
    }

    function evaluate_save()
    {
        // check access permission
        if (!get_permission('homework_evaluate', 'is_add')) {
            ajax_access_denied();
        }
        if ($_POST) {
            $this->form_validation->set_rules('date', translate('date'), 'trim|required');
            if ($this->form_validation->run() !== false) {
                $evaluate = $this->input->post('evaluate');
                $homeworkID = $this->input->post('homework_id');
                $date = date("Y-m-d", strtotime($this->input->post('date')));
                foreach ($evaluate as $key => $value) {
                    $attStatus = (isset($value['status']) ? $value['status'] : "");
                    $arrayAttendance = array(
                        'homework_id' => $homeworkID,
                        'student_id' => $value['student_id'],
                        'status' => $attStatus,
                        'rank' => $value['rank'],
                        'remark' => $value['remark'],
                        'date' => $date,
                    );
                    if (empty($value['evaluation_id'])) {
                        $this->db->insert('homework_evaluation', $arrayAttendance);
                    } else {
                        $this->db->where('id', $value['evaluation_id']);
                        $this->db->update('homework_evaluation', array('rank' => $value['rank'], 'status' => $attStatus, 'remark' => $value['remark'], 'date' => $date));
                    }
                }
                $this->db->where('id', $homeworkID);
                $this->db->update('homework', array('evaluation_date' => $date, 'evaluated_by' => get_loggedin_user_id()));
                set_alert('success', translate('information_has_been_saved_successfully'));
                $array  = array('status' => 'success', 'message' => translate('information_has_been_saved_successfully'));
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }  
    }

    public function evaluateModal()
    {
        $this->data['homeworkID'] = $this->input->post('homework_id');
        echo $this->load->view('homework/evaluateModal', $this->data, true);
    }

    public function report()
    {
        // check access permission
        if (!get_permission('evaluation_report', 'is_view')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();
        if ($_POST) {
            $classID = $this->input->post('class_id');
            $sectionID = $this->input->post('section_id');
            $subjectID = $this->input->post('subject_id');
            $this->data['homeworklist'] = $this->homework_model->getList($classID, $sectionID, $subjectID, $branchID);
        }
        
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('homework');
        $this->data['sub_page'] = 'homework/report';
        $this->data['main_menu'] = 'homework';
        $this->load->view('layout/index', $this->data);
    }

    public function evaluateDetails()
    {
        $id = $this->input->post('homework_id');
        $this->data['homeworklist'] = $this->homework_model->getEvaluate($id);
        echo $this->load->view('homework/evaluateDetails', $this->data, true);
    }


    public function download($id)
    {
        $this->load->helper('download');
        $name     = get_type_name_by_id('homework', $id, 'document');
        $ext      = explode(".", $name);
        $filepath = "./uploads/attachments/homework/" . $id . "." . $ext[1];
        $data     = file_get_contents($filepath);
        force_download($name, $data);
    }

    public function download_submitted()
    {
        $this->load->helper('download');
        $encrypt_name = urldecode($this->input->get('file'));
        if(preg_match('/^[^.][-a-z0-9_.]+[a-z]$/i', $encrypt_name)) {
            $file_name = $this->db->select('file_name')->where('enc_name', $encrypt_name)->get('homework_submit')->row()->file_name;
            if (!empty($file_name)) {
                force_download($file_name, file_get_contents('uploads/attachments/homework_submit/' . $encrypt_name));
            }
        }
    }

    public function delete($id = '')
    {
        if (get_permission('homework', 'is_delete') && !empty($id)) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $name = get_type_name_by_id('homework', $id, 'document');
            $ext = explode(".", $name);
            $this->db->where('id', $id);
            $this->db->delete('homework');
            $filepath = "./uploads/attachments/homework/" . $id . "." . $ext[1];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    /* homework form validation rules */
    protected function homework_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('class_id', translate('class'), 'trim|required');
        $this->form_validation->set_rules('section_id', translate('section'), 'trim|required');
        $this->form_validation->set_rules('subject_id', translate('subject'), 'trim|required');
        $this->form_validation->set_rules('date_of_homework', translate('date_of_homework'), 'trim|required');
        $this->form_validation->set_rules('date_of_submission', translate('date_of_submission'), 'trim|required');
        if (isset($_POST['published_later'])) {
            $this->form_validation->set_rules('schedule_date', translate('schedule_date'), 'trim|required');
        }
        $this->form_validation->set_rules('homework', translate('homework'), 'trim|required');
        $this->form_validation->set_rules('attachment_file', translate('attachment'), 'callback_handle_upload');
    }

    // upload file form validation
    public function handle_upload()
    {
        if (isset($_FILES["attachment_file"]) && !empty($_FILES['attachment_file']['name'])) {
            $allowedExts = array_map('trim', array_map('strtolower', explode(',', $this->data['global_config']['file_extension'])));
            $allowedSizeKB = $this->data['global_config']['file_size'];
            $allowedSize = floatval(1024 * $allowedSizeKB);
            
            $file_size = $_FILES["attachment_file"]["size"];
            $file_name = $_FILES["attachment_file"]["name"];
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            if ($files = filesize($_FILES["attachment_file"]['tmp_name'])) {
                if (!in_array(strtolower($extension), $allowedExts)) {
                    $this->form_validation->set_message('handle_upload', translate('this_file_type_is_not_allowed'));
                    return false;
                }
                if ($file_size > $allowedSize) {
                    $this->form_validation->set_message('handle_upload', translate('file_size_shoud_be_less_than') . " $allowedSizeKB KB.");
                    return false;
                }
            } else {
                $this->form_validation->set_message('handle_upload', translate('error_reading_the_file'));
                return false;
            }
            return true;
        } else {
            if (isset($_POST['homework_id'])) {
                return true;
            }
            $this->form_validation->set_message('handle_upload', "The Attachment field is required.");
            return false;
        }
    }

    
  public function check_homework_sms_credits()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $branch_id = $this->input->post('branch_id');
    $class_id = $this->input->post('class_id');
    $section_id = $this->input->post('section_id');
    $subject_id = $this->input->post('subject_id');
    
    // Get targeting parameters
    $enable_targeting = $this->input->post('enable_targeting');
    $target_group = $this->input->post('target_group');
    $target_students = $this->input->post('target_students');
    
    // Get all students in class/section
    $all_students = $this->application_model->getStudentListByClassSection($class_id, $section_id, $branch_id);
    
    // Default values
    $student_count = 0;
    $targeting_enabled = false;
    
    // Filter students based on targeting
    if ($enable_targeting == '1') {
        $targeting_enabled = true;
        
        if ($target_group == 'individual' && !empty($target_students)) {
            // Filter by selected individual students
            $targeted_student_ids = is_array($target_students) ? $target_students : [$target_students];
            $student_count = count($targeted_student_ids);
        } elseif ($target_group == 'all') {
            $student_count = count($all_students);
        } elseif (in_array($target_group, ['remedial', 'standard', 'advanced'])) {
            // For group-based targeting, use all students for now
            $student_count = count($all_students);
        } else {
            $student_count = count($all_students);
        }
    } else {
        // No targeting - get students enrolled in this subject
        $this->load->model('student_subject_model');
        $enrolled_students = $this->student_subject_model->get_subject_students($subject_id, $class_id, $section_id, $branch_id);
        $student_count = count($enrolled_students);
        
        // If no enrolled students, fall back to all students in class/section
        if ($student_count == 0) {
            $student_count = count($all_students);
        }
    }
    
    if ($student_count == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No students to notify',
            'student_count' => 0,
            'credits_needed' => 0,
            'credits_available' => 0,
            'targeting_enabled' => $targeting_enabled
        ]);
        return;
    }
    
    // Get SMS template
    $template = $this->db->get_where('sms_template_details', array(
        'template_id' => 6,
        'branch_id' => $branch_id
    ))->row_array();
    
    if (empty($template)) {
        echo json_encode([
            'success' => false,
            'message' => 'SMS template not configured for homework notifications',
            'credits_available' => 0,
            'credits_needed' => 0,
            'student_count' => 0
        ]);
        return;
    }
    
    // Calculate total recipients (students + parents based on template settings)
    $total_recipients = 0;
    if ($template['notify_student'] == 1) {
        $total_recipients += $student_count;
    }
    if ($template['notify_parent'] == 1) {
        $total_recipients += $student_count;
    }
    
    // Get message template for cost calculation
    $message = $template['template_body'];
    $message = str_replace('{subject}', 'Test Subject', $message);
    
    // Calculate required credits
    $credits_needed = $this->calculate_homework_sms_cost($message, $total_recipients);
    
    // Check current credits
    $current_credits = $this->sendsmsmail_model->get_sms_credit($branch_id);
    
    echo json_encode([
        'success' => true,
        'credits_needed' => (int)$credits_needed,
        'credits_available' => (int)$current_credits,
        'student_count' => (int)$student_count,
        'total_recipients' => (int)$total_recipients,
        'targeting_enabled' => $targeting_enabled,
        'target_group' => $target_group
    ]);
}

    /**
     * Get targeted students list based on group or individual selection
     * @param array $all_students
     * @param string $target_group
     * @param array $target_students
     * @return array
     */
        /**
     * Get targeted students list based on group or individual selection
     * @param array $all_students
     * @param string $target_group
     * @param array $target_student_ids
     * @return array
     */
    public function getTargetedStudentsList($all_students, $target_group, $target_student_ids = [])
    {
        if (empty($all_students)) {
            return [];
        }
        
        if ($target_group == 'all') {
            return $all_students;
        }
        
        if ($target_group == 'individual' && !empty($target_student_ids)) {
            $targeted = [];
            foreach ($all_students as $student) {
                if (in_array($student['student_id'], $target_student_ids)) {
                    $targeted[] = $student;
                }
            }
            return $targeted;
        }
        
        // For remedial, standard, advanced - return all for now
        // You can expand this with performance-based filtering
        return $all_students;
    }

/**
 * Calculate SMS cost for homework notifications
 */
private function calculate_homework_sms_cost($template_body, $recipient_count)
{
    // Use the same logic as your sendsmsmail controller
    $this->config->load('smsconfig', TRUE);
    $sms_config = $this->config->item('sms_credit');
    
    if (empty($sms_config)) {
        // Default values
        $sms_config = array(
            'credit_calculation' => array(
                'chars_per_sms' => 160,
                'credits_per_sms' => 1,
                'unicode_chars_per_sms' => 70,
                'unicode_credits_multiplier' => 2
            )
        );
    }
    
    // Check if message contains Unicode
    $is_unicode = false;
    $length = mb_strlen($template_body, 'UTF-8');
    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($template_body, $i, 1, 'UTF-8');
        if (ord($char) > 127) {
            $is_unicode = true;
            break;
        }
    }
    
    $chars_per_sms = $is_unicode ? 
        ($sms_config['credit_calculation']['unicode_chars_per_sms'] ?? 70) : 
        ($sms_config['credit_calculation']['chars_per_sms'] ?? 160);
    
    $sms_parts = ceil($length / $chars_per_sms);
    $credits_per_part = $is_unicode ? 
        ($sms_config['credit_calculation']['unicode_credits_multiplier'] ?? 2) : 1;
    
    return $sms_parts * $recipient_count * $credits_per_part;
    // DEBUG - Remove after fixing
    log_message('debug', "SMS Cost Calculation: Parts={$sms_parts}, Recipients={$recipient_count}, CreditsPerPart={$credits_per_part}, Total={$total_credits}");
    
    return $total_credits;
}

/**
 * View and manage submissions for a homework (Teacher)
 */
public function submissions($homework_id = null) {
    if (!get_permission('homework_evaluate', 'is_view')) {
        access_denied();
    }
    
    if (!$homework_id) {
        redirect('homework');
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    // Verify homework belongs to teacher's subject
    $homework = $this->db->get_where('homework', ['id' => $homework_id, 'branch_id' => $branch_id])->row();
    if (!$homework) {
        set_alert('error', 'Homework not found');
        redirect('homework');
    }
    
    $this->data['homework'] = $homework;
    $this->data['submissions'] = $this->homework_model->getSubmissionsForTeacher($homework_id);
    $this->data['title'] = translate('homework_submissions');
    $this->data['sub_page'] = 'homework/submissions';
    $this->data['main_menu'] = 'homework';
    
    $this->load->view('layout/index', $this->data);
}

/**
 * Save feedback for a submission (AJAX)
 */
public function save_submission_feedback() {
    if (!get_permission('homework_evaluate', 'is_add')) {
        ajax_access_denied();
    }
    
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $submission_id = $this->input->post('submission_id');
    $feedback = $this->input->post('feedback');
    $grade = $this->input->post('grade');
    $status = $this->input->post('submission_status');
    
    $feedback_data = [
        'feedback' => $feedback,
        'grade' => $grade,
        'submission_status' => $status,
        'feedback_date' => date('Y-m-d H:i:s'),
        'feedback_by' => get_loggedin_user_id()
    ];
    
    $result = $this->homework_model->save_submission_feedback($submission_id, $feedback_data);
    
    if ($result) {
        // Also update homework_evaluation for backward compatibility
        $submission = $this->db->get_where('homework_submit', ['id' => $submission_id])->row();
        if ($submission) {
            $this->db->where('homework_id', $submission->homework_id)
                     ->where('student_id', $submission->student_id);
            $exists = $this->db->get('homework_evaluation')->row();
            
            if ($exists) {
                $this->db->where('id', $exists->id);
                $this->db->update('homework_evaluation', [
                    'rank' => $grade,
                    'remark' => $feedback,
                    'date' => date('Y-m-d')
                ]);
            }
        }
        
        // Send notification to student/parent
        $this->load->library('Notification_service');
        // Get student details for notification
        $student = $this->db->select('s.first_name, s.last_name, p.mobileno as parent_phone, s.mobileno as student_phone')
                            ->from('student s')
                            ->join('parent p', 'p.id = s.parent_id', 'left')
                            ->where('s.id', $submission->student_id)
                            ->get()->row();
        
        $recipients = [];
        if ($student->student_phone) {
            $recipients[] = ['mobileno' => $student->student_phone, 'name' => $student->first_name];
        }
        if ($student->parent_phone) {
            $recipients[] = ['mobileno' => $student->parent_phone, 'name' => 'Parent'];
        }
        
        $this->notification_service->send_homework_notification('graded', [
            'id' => $submission->homework_id,
            'branch_id' => $branch_id,
            'student_name' => $student->first_name . ' ' . $student->last_name,
            'grade' => $grade,
            'feedback' => $feedback
        ], $recipients);
        
        echo json_encode(['status' => 'success', 'message' => 'Feedback saved successfully']);
    } else {
        echo json_encode(['status' => 'fail', 'message' => 'Failed to save feedback']);
    }
}



/**
 * Run nightly job to mark late submissions
 * Call via cron: 0 0 * * * wget -q -O /dev/null https://school.studportal.co.ke/homework/mark_late
 */
public function mark_late() {
    // Allow cron access (no session check)
    if (php_sapi_name() !== 'cli' && !$this->input->is_ajax_request()) {
        // Optional: check cron secret key
        if ($this->input->get('key') != $this->config->item('cron_secret_key')) {
            show_404();
        }
    }
    
    $count = $this->homework_model->check_and_mark_late_submissions();
    log_message('info', "Marked {$count} submissions as late");
    echo "Done. {$count} submissions marked as late.";
}
    /**
     * Student submission page - view and submit homework
     */
    public function student_submission($homework_id = null)
    {
        if (!get_permission('homework', 'is_view')) {
            access_denied();
        }
        
        $student_id = get_loggedin_user_id();
        $branch_id = $this->application_model->get_branch_id();
        
        // Get homework details
        $homework = $this->db->get_where('homework', [
            'id' => $homework_id,
            'branch_id' => $branch_id,
            'session_id' => get_session_id()
        ])->row();
        
        if (!$homework) {
            set_alert('error', 'Homework not found');
            redirect('userrole/homework');
        }
        
        // Get student's submission if exists
        $submission = $this->homework_model->getStudentSubmission($homework_id, $student_id);
        
        // Get subject, class, section details
        $subject = $this->db->get_where('subject', ['id' => $homework->subject_id])->row();
        $class = $this->db->get_where('class', ['id' => $homework->class_id])->row();
        $section = $this->db->get_where('section', ['id' => $homework->section_id])->row();
        
        $this->data['homework'] = $homework;
        $this->data['submission'] = $submission;
        $this->data['subject'] = $subject;
        $this->data['class'] = $class;
        $this->data['section'] = $section;
        $this->data['branch_id'] = $branch_id;
        $this->data['title'] = translate('submit_homework');
        $this->data['sub_page'] = 'homework/student_submission';
        $this->data['main_menu'] = 'userrole';
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/bootstrap-fileupload/bootstrap-fileupload.min.css',
            ),
            'js' => array(
                'vendor/bootstrap-fileupload/bootstrap-fileupload.min.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * Save student homework submission (AJAX)
     */
    public function save_submission()
    {
        if (!get_permission('homework', 'is_add')) {
            ajax_access_denied();
        }
        
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $homework_id = $this->input->post('homework_id');
        $student_id = get_loggedin_user_id();
        $message = $this->input->post('message');
        
        // Validate
        $this->form_validation->set_rules('homework_id', 'Homework ID', 'trim|required');
        
        if ($this->form_validation->run() == false) {
            echo json_encode(['status' => 'fail', 'error' => $this->form_validation->error_array()]);
            return;
        }
        
        // Check if homework exists and not past due date
        $homework = $this->db->get_where('homework', ['id' => $homework_id])->row();
        if (!$homework) {
            echo json_encode(['status' => 'fail', 'message' => 'Homework not found']);
            return;
        }
        
        // Check if past due date
        if (strtotime($homework->date_of_submission) < strtotime(date('Y-m-d'))) {
            $submission_status = 'late';
        } else {
            $submission_status = 'submitted';
        }
        
        // Handle file upload
        $enc_name = null;
        $file_name = null;
        
        if (isset($_FILES["attachment_file"]) && !empty($_FILES['attachment_file']['name'])) {
            $uploaddir = './uploads/attachments/homework_submit/';
            if (!is_dir($uploaddir) && !mkdir($uploaddir, 0777, true)) {
                echo json_encode(['status' => 'fail', 'message' => 'Failed to create upload directory']);
                return;
            }
            
            $fileInfo = pathinfo($_FILES["attachment_file"]["name"]);
            $enc_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileInfo['extension'];
            $file_name = $_FILES["attachment_file"]["name"];
            
            if (!move_uploaded_file($_FILES["attachment_file"]["tmp_name"], $uploaddir . $enc_name)) {
                echo json_encode(['status' => 'fail', 'message' => 'Failed to upload file']);
                return;
            }
        }
        
        // Save submission
        $submission_data = [
            'homework_id' => $homework_id,
            'student_id' => $student_id,
            'message' => $message,
            'enc_name' => $enc_name,
            'file_name' => $file_name,
            'submission_status' => $submission_status
        ];
        
        $result = $this->homework_model->saveStudentSubmission($submission_data);
        
        if ($result) {
            // Mark homework as viewed
            $this->homework_model->markSubmissionAsViewed($homework_id, $student_id);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Homework submitted successfully',
                'submission_status' => $submission_status
            ]);
        } else {
            echo json_encode(['status' => 'fail', 'message' => 'Failed to save submission']);
        }
    }
    
    /**
     * Teacher view - manage submissions for a homework
     */
    public function manage_submissions($homework_id = null)
    {
        if (!get_permission('homework_evaluate', 'is_view')) {
            access_denied();
        }
        
        if (!$homework_id) {
            redirect('homework');
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        // Verify homework belongs to teacher's subject
        $homework = $this->db->get_where('homework', [
            'id' => $homework_id,
            'branch_id' => $branch_id
        ])->row();
        
        if (!$homework) {
            set_alert('error', 'Homework not found');
            redirect('homework');
        }
        
        // Get all students and their submissions
        $submissions = $this->homework_model->getSubmissionsForTeacher($homework_id);
        
        // Get subject, class, section details
        $subject = $this->db->get_where('subject', ['id' => $homework->subject_id])->row();
        $class = $this->db->get_where('class', ['id' => $homework->class_id])->row();
        $section = $this->db->get_where('section', ['id' => $homework->section_id])->row();
        
        $this->data['homework'] = $homework;
        $this->data['submissions'] = $submissions;
        $this->data['subject'] = $subject;
        $this->data['class'] = $class;
        $this->data['section'] = $section;
        $this->data['title'] = translate('manage_submissions');
        $this->data['sub_page'] = 'homework/manage_submissions';
        $this->data['main_menu'] = 'homework';
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * Save teacher feedback for a submission (AJAX)
     */
    public function save_feedback()
    {
        if (!get_permission('homework_evaluate', 'is_add')) {
            ajax_access_denied();
        }
        
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $submission_id = $this->input->post('submission_id');
        $feedback = $this->input->post('feedback');
        $grade = $this->input->post('grade');
        $submission_status = $this->input->post('submission_status');
        
        $this->form_validation->set_rules('submission_id', 'Submission ID', 'trim|required');
        
        if ($this->form_validation->run() == false) {
            echo json_encode(['status' => 'fail', 'error' => $this->form_validation->error_array()]);
            return;
        }
        
        $feedback_data = [
            'feedback' => $feedback,
            'grade' => $grade,
            'submission_status' => $submission_status,
            'feedback_date' => date('Y-m-d H:i:s'),
            'feedback_by' => get_loggedin_user_id(),
            'reviewed_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $this->homework_model->saveSubmissionFeedback($submission_id, $feedback_data);
        
        if ($result) {
            // Also update homework_evaluation table for backward compatibility
            $submission = $this->homework_model->getSubmissionById($submission_id);
            if ($submission) {
                $existing = $this->db->get_where('homework_evaluation', [
                    'homework_id' => $submission->homework_id,
                    'student_id' => $submission->student_id
                ])->row();
                
                if ($existing) {
                    $this->db->where('id', $existing->id);
                    $this->db->update('homework_evaluation', [
                        'rank' => $grade,
                        'remark' => $feedback,
                        'status' => 'c',
                        'date' => date('Y-m-d')
                    ]);
                } else {
                    $this->db->insert('homework_evaluation', [
                        'homework_id' => $submission->homework_id,
                        'student_id' => $submission->student_id,
                        'rank' => $grade,
                        'remark' => $feedback,
                        'status' => 'c',
                        'date' => date('Y-m-d')
                    ]);
                }
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Feedback saved successfully']);
        } else {
            echo json_encode(['status' => 'fail', 'message' => 'Failed to save feedback']);
        }
    }
    
    /**
     * Get submission details for modal (AJAX)
     */
    public function get_submission_details()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $submission_id = $this->input->post('submission_id');
        $submission = $this->homework_model->getSubmissionById($submission_id);
        
        if ($submission) {
            echo json_encode([
                'status' => 'success',
                'submission' => $submission
            ]);
        } else {
            echo json_encode(['status' => 'fail', 'message' => 'Submission not found']);
        }
    }
    /**
     * Teacher analytics dashboard - homework completion statistics
     */
public function analytics()
{
    if (!get_permission('homework', 'is_view')) {
        access_denied();
    }
    
    $user_id = get_loggedin_user_id();
    $user_role = loggedin_role_id();
    $is_superadmin = is_superadmin_loggedin();
    $branch_id = $this->application_model->get_branch_id();
    
    if (empty($branch_id) && !$is_superadmin) {
        $branch_id = get_loggedin_branch_id();
    }
    
    // Get data from model - NO database operations in controller
    $this->data['all_branches'] = $is_superadmin ? $this->homework_model->getAllBranches() : [];
    $this->data['subjects'] = $this->homework_model->getTeacherSubjects($user_id, $branch_id, $is_superadmin);
    $this->data['stats'] = $this->homework_model->getTeacherHomeworkStats($user_id, $branch_id, null, $is_superadmin);
    $this->data['is_superadmin'] = $is_superadmin;
    $this->data['title'] = translate('homework_analytics');
    $this->data['sub_page'] = 'homework/analytics';
    $this->data['main_menu'] = 'homework';
    $this->data['headerelements'] = array(
        'js' => array('https://cdn.jsdelivr.net/npm/chart.js'),
    );
    $this->load->view('layout/index', $this->data);
}
    
    /**
     * Get analytics data for AJAX charts (AJAX only)
     */
      public function get_analytics_data()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $subject_id = $this->input->post('subject_id');
    $period = $this->input->post('period') ?: 'month';
    $branch_id = $this->input->post('branch_id');
    $is_superadmin = is_superadmin_loggedin();
    
    if (empty($subject_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject ID required']);
        return;
    }
    
    if ($branch_id == 'all' || empty($branch_id)) {
        $branch_id = null;
    }
    
    // NO database operations here - all in model
    $chart_data = $this->homework_model->getChartData($branch_id, $subject_id, $period);
    
    echo json_encode([
        'status' => 'success',
        'labels' => $chart_data['labels'],
        'submission' => $chart_data['submission'],
        'grading' => $chart_data['grading']
    ]);
}
/**
 * Get subjects by branch for AJAX (Superadmin branch filtering)
 */
public function get_subjects_by_branch()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $branch_id = $this->input->post('branch_id');
    $user_id = get_loggedin_user_id();
    $user_role = loggedin_role_id();
    $is_superadmin = is_superadmin_loggedin();
    
    // NO database operations here - all in model
    $subjects = $this->homework_model->getSubjectsByBranch($branch_id, $user_id, $user_role, $is_superadmin);
    
    echo json_encode([
        'status' => 'success',
        'subjects' => $subjects,
        'is_superadmin' => $is_superadmin
    ]);
}
    /**
     * Homework completion reports page
     */
  public function completion_report()
{
    if (!get_permission('evaluation_report', 'is_view')) {
        access_denied();
    }
    
    $is_superadmin = is_superadmin_loggedin();
    $branch_id = $this->application_model->get_branch_id();
    
    // For superadmin, allow branch selection from URL parameter
    if ($is_superadmin) {
        $selected_branch = $this->input->get('branch_id');
        if ($selected_branch) {
            $branch_id = $selected_branch;
        }
    }
    
    // Get filter values
    $class_id = $this->input->get('class_id');
    $section_id = $this->input->get('section_id');
    $date_from = $this->input->get('date_from');
    $date_to = $this->input->get('date_to');
    $report_type = $this->input->get('report_type') ?: 'homework';
    
    // Get data from model (pass branch_id for filtering)
    if ($report_type == 'student') {
        $this->data['report_data'] = $this->homework_model->getStudentCompletionReport($branch_id, $class_id, $section_id);
    } else {
        $this->data['report_data'] = $this->homework_model->getCompletionReport($branch_id, $class_id, $section_id, $date_from, $date_to);
    }
    
    $this->data['summary'] = $this->homework_model->getReportSummary($branch_id, $class_id, $section_id, $date_from, $date_to);
    $this->data['classes'] = $this->homework_model->getClasses($branch_id);
    $this->data['sections'] = ($class_id) ? $this->homework_model->getSections($class_id, $branch_id) : [];
    $this->data['report_type'] = $report_type;
    $this->data['class_id'] = $class_id;
    $this->data['section_id'] = $section_id;
    $this->data['date_from'] = $date_from;
    $this->data['date_to'] = $date_to;
    $this->data['branch_id'] = $branch_id;
    $this->data['is_superadmin'] = $is_superadmin;
    $this->data['title'] = translate('homework_completion_report');
    $this->data['sub_page'] = 'homework/completion_report';
    $this->data['main_menu'] = 'homework';
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
    
    /**
     * Export report to Excel
     */
    public function export_report_excel()
    {
        if (!get_permission('evaluation_report', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $class_id = $this->input->get('class_id');
        $section_id = $this->input->get('section_id');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $report_type = $this->input->get('report_type') ?: 'homework';
        
        if ($report_type == 'student') {
            $data = $this->homework_model->getStudentCompletionReport($branch_id, $class_id, $section_id);
            $filename = 'student_completion_report_' . date('Y-m-d') . '.xls';
            $headers = ['Student Name', 'Register No', 'Class', 'Section', 'Total Homework', 'Submitted', 'Late', 'Graded', 'Completion Rate', 'Average Grade'];
        } else {
            $data = $this->homework_model->getCompletionReport($branch_id, $class_id, $section_id, $date_from, $date_to);
            $filename = 'homework_completion_report_' . date('Y-m-d') . '.xls';
            $headers = ['Subject', 'Due Date', 'Total Students', 'Submissions', 'Submission Rate', 'Graded', 'Grading Rate', 'Late'];
        }
        
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        echo implode("\t", $headers) . "\n";
        
        foreach ($data as $row) {
            if ($report_type == 'student') {
                echo implode("\t", [
                    $row['student_name'],
                    $row['register_no'],
                    $row['class_name'],
                    $row['section_name'],
                    $row['total_homework'],
                    $row['submitted_count'],
                    $row['late_count'],
                    $row['graded_count'],
                    $row['completion_rate'] . '%',
                    round($row['avg_grade'] ?? 0, 1)
                ]) . "\n";
            } else {
                echo implode("\t", [
                    $row['subject_name'],
                    date('d/m/Y', strtotime($row['date_of_homework'])),
                    $row['total_students'],
                    $row['submissions_count'],
                    $row['submission_rate'] . '%',
                    $row['graded_count'],
                    $row['grading_rate'] . '%',
                    $row['late_count']
                ]) . "\n";
            }
        }
        exit;
    }
    
    /**
     * Print report
     */
    public function print_report()
    {
        if (!get_permission('evaluation_report', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $class_id = $this->input->get('class_id');
        $section_id = $this->input->get('section_id');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $report_type = $this->input->get('report_type') ?: 'homework';
        
        if ($report_type == 'student') {
            $this->data['report_data'] = $this->homework_model->getStudentCompletionReport($branch_id, $class_id, $section_id);
        } else {
            $this->data['report_data'] = $this->homework_model->getCompletionReport($branch_id, $class_id, $section_id, $date_from, $date_to);
        }
        
        $this->data['summary'] = $this->homework_model->getReportSummary($branch_id, $class_id, $section_id, $date_from, $date_to);
        $this->data['report_type'] = $report_type;
        $this->data['class_id'] = $class_id;
        $this->data['section_id'] = $section_id;
        $this->data['date_from'] = $date_from;
        $this->data['date_to'] = $date_to;
        $this->data['branch'] = $this->db->get_where('branch', ['id' => $branch_id])->row();
        $this->data['title'] = translate('homework_completion_report');
        
        $this->load->view('homework/print_report', $this->data);
    }
    /**
 * Get sections by class for AJAX
 */
public function get_sections_by_class()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $class_id = $this->input->post('class_id');
    $branch_id = $this->input->post('branch_id');
    
    $sections = $this->homework_model->getSections($class_id, $branch_id);
    
    echo json_encode([
        'status' => 'success',
        'sections' => $sections
    ]);
}
/**
 * Get students by class/section for targeting (AJAX)
 */
public function get_students_by_class_section()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $class_id = $this->input->post('class_id');
    $section_id = $this->input->post('section_id');
    $branch_id = $this->input->post('branch_id');
    
    $students = $this->application_model->getStudentListByClassSection($class_id, $section_id, $branch_id);
    
    $student_list = [];
    foreach ($students as $student) {
        $student_list[] = [
            'id' => $student['student_id'],
            'name' => $student['fullname'],
            'register_no' => $student['register_no']
        ];
    }
    
    echo json_encode(['status' => 'success', 'students' => $student_list]);
}
        /**
     * Homework discussion - list view (MVC compliant - no DB in controller)
     */
    public function comments($homework_id = null)
    {
        if (!get_permission('homework', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $is_superadmin = is_superadmin_loggedin();
        $filter_branch_id = $this->input->post('branch_id');
        
        // If homework_id is provided, show individual discussion
        if ($homework_id && is_numeric($homework_id)) {
            $this->show_discussion($homework_id, $branch_id);
            return;
        }
        
        // Get filter values
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $subject_id = $this->input->post('subject_id');
        
        // Get data from model - NO database queries in controller
        $this->data['homeworklist'] = $this->homework_model->getDiscussionHomeworkList(
            $branch_id, $class_id, $section_id, $subject_id, $is_superadmin, $filter_branch_id
        );
        
        $this->data['classes'] = $this->homework_model->getDiscussionClasses($branch_id, $is_superadmin);
        $this->data['branch_id'] = $branch_id;
        $this->data['is_superadmin'] = $is_superadmin;
        $this->data['title'] = translate('homework_discussion');
        $this->data['sub_page'] = 'homework/discussion_list';
        $this->data['main_menu'] = 'homework';
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * Show individual homework discussion (MVC compliant - no DB in controller)
     */
    private function show_discussion($homework_id, $branch_id)
    {
        $is_superadmin = is_superadmin_loggedin();
        
        // Get homework from model
        $homework = $this->homework_model->getHomeworkForDiscussion($homework_id, $branch_id, $is_superadmin);
        
        if (!$homework) {
            set_alert('error', 'Homework not found or you do not have access');
            redirect('homework/comments');
        }
        
        // Determine user type
        if (is_student_loggedin()) {
            $user_type = 'student';
        } elseif (is_teacher_loggedin()) {
            $user_type = 'teacher';
        } elseif (is_parent_loggedin()) {
            $user_type = 'parent';
        } else {
            $user_type = 'admin';
        }
        
        $user_id = get_loggedin_user_id();
        
        // Get comments from model
        $comments = $this->homework_model->getHomeworkCommentsWithReplies($homework_id, $branch_id, $is_superadmin);
        $comment_count = $this->homework_model->getTotalCommentCount($homework_id, $branch_id, $is_superadmin);
        
        $this->data['homework'] = $homework;
        $this->data['comments'] = $comments;
        $this->data['comment_count'] = $comment_count;
        $this->data['user_id'] = $user_id;
        $this->data['user_type'] = $user_type;
        $this->data['is_superadmin'] = $is_superadmin;
        $this->data['title'] = translate('homework_discussion') . ' - ' . $homework->subject_name;
        $this->data['sub_page'] = 'homework/comments';
        $this->data['main_menu'] = 'homework';
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/bootstrap-fileupload/bootstrap-fileupload.min.css',
            ),
            'js' => array(
                'vendor/bootstrap-fileupload/bootstrap-fileupload.min.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }
    
          /**
     * Add comment to homework (AJAX) - MVC compliant
     */
    public function add_comment()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $homework_id = $this->input->post('homework_id');
        $comment = $this->input->post('comment');
        $parent_id = $this->input->post('parent_id');
        $branch_id = $this->application_model->get_branch_id();
        
        $this->form_validation->set_rules('comment', 'Comment', 'trim|required');
        
        if ($this->form_validation->run() == false) {
            echo json_encode(['status' => 'fail', 'message' => validation_errors()]);
            return;
        }
        
        // Determine user type and get author name via model
        if (is_student_loggedin()) {
            $user_type = 'student';
            $user_id = get_loggedin_user_id();
            $author_name = $this->homework_model->getStudentName($user_id);
        } elseif (is_teacher_loggedin()) {
            $user_type = 'teacher';
            $user_id = get_loggedin_user_id();
            $author_name = $this->homework_model->getTeacherName($user_id);
        } elseif (is_parent_loggedin()) {
            $user_type = 'parent';
            $user_id = get_loggedin_user_id();
            $author_name = $this->homework_model->getParentName($user_id);
        } else {
            $user_type = 'admin';
            $user_id = get_loggedin_user_id();
            $author_name = 'Admin';
        }
        
        // Handle file upload
        $attachments = null;
        if (isset($_FILES["attachment"]) && !empty($_FILES['attachment']['name'])) {
            $uploaddir = './uploads/attachments/homework_comments/';
            if (!is_dir($uploaddir) && !mkdir($uploaddir, 0777, true)) {
                // log error
            }
            $ext = pathinfo($_FILES["attachment"]["name"], PATHINFO_EXTENSION);
            $attachments = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            move_uploaded_file($_FILES["attachment"]["tmp_name"], $uploaddir . $attachments);
        }
        
        $comment_data = [
            'homework_id' => $homework_id,
            'comment' => nl2br(htmlspecialchars($comment)),
            'attachments' => $attachments,
            'created_by' => $user_id,
            'created_by_type' => $user_type,
            'branch_id' => $branch_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($parent_id) {
            $comment_data['parent_id'] = $parent_id;
        }
        
        $result = $this->homework_model->addHomeworkComment($comment_data);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Comment added successfully',
                'comment_id' => $result,
                'author_name' => $author_name,
                'author_type' => $user_type,
                'comment' => nl2br(htmlspecialchars($comment)),
                'created_at' => date('d/m/Y H:i'),
                'attachments' => $attachments
            ]);
        } else {
            echo json_encode(['status' => 'fail', 'message' => 'Failed to add comment']);
        }
    }
    
    /**
     * Get author name by ID and type (helper for MVC)
     */
   
    
    /**
     * Delete comment (AJAX)
     */
    public function delete_comment()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $comment_id = $this->input->post('comment_id');
        $user_id = get_loggedin_user_id();
        
        if (is_student_loggedin()) {
            $user_type = 'student';
        } elseif (is_teacher_loggedin()) {
            $user_type = 'teacher';
        } elseif (is_parent_loggedin()) {
            $user_type = 'parent';
        } else {
            $user_type = 'admin';
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        $result = $this->homework_model->deleteHomeworkComment($comment_id, $user_id, $user_type, $branch_id);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Comment deleted successfully']);
        } else {
            echo json_encode(['status' => 'fail', 'message' => 'Failed to delete comment']);
        }
    }
    
    /**
     * Download comment attachment
     */
    public function download_comment_attachment()
    {
        $file = urldecode($this->input->get('file'));
        
        if (preg_match('/^[^.][-a-z0-9_.]+[a-z]$/i', $file)) {
            $comment = $this->db->select('attachments')->where('attachments', $file)->get('homework_comments')->row();
            if ($comment && !empty($comment->attachments)) {
                $this->load->helper('download');
                force_download($comment->attachments, file_get_contents('uploads/attachments/homework_comments/' . $file));
            }
        }
    }
}
