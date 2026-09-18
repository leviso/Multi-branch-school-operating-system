<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_service {
    
    private $ci;
    private $sms_lib;
    
    public function __construct() {
        $this->ci =& get_instance();
        $this->ci->load->model('sendsmsmail_model');
        $this->ci->load->model('homework_model');
        $this->ci->load->library('bulksmsbd', ['branch_id' => 0], 'sms_lib');
    }
    
    /**
     * Initialize SMS library for specific branch
     */
    private function init_sms_library($branch_id) {
        $this->ci->load->library('bulksmsbd', ['branch_id' => $branch_id], 'sms_lib');
        return $this->ci->sms_lib;
    }
    
    /**
     * Send homework assigned notification
     */
    public function homework_assigned($homework_id, $branch_id, $class_id, $section_id) {
        // Get template
        $template = $this->get_sms_template(6, $branch_id);
        if (!$template) {
            log_message('error', "Notification: No template found for homework assigned (ID:6, Branch:{$branch_id})");
            return ['success' => false, 'message' => 'Template not found'];
        }
        
        // Get students
        $students = $this->ci->application_model->getStudentListByClassSection($class_id, $section_id, $branch_id);
        
        // Get homework details
        $homework = $this->ci->db->get_where('homework', ['id' => $homework_id])->row();
        if (!$homework) {
            return ['success' => false, 'message' => 'Homework not found'];
        }
        
        $subject_name = get_type_name_by_id('subject', $homework->subject_id);
        
        // Prepare recipients
        $recipients = $this->prepare_recipients($students, $template);
        
        // Prepare message
        $message_template = $template['template_body'];
        $message_template = str_replace('{date_of_homework}', _d($homework->date_of_homework), $message_template);
        $message_template = str_replace('{date_of_submission}', _d($homework->date_of_submission), $message_template);
        $message_template = str_replace('{subject}', $subject_name, $message_template);
        
        // Send SMS
        return $this->send_bulk_sms($recipients, $message_template, $branch_id, $homework_id, 'assigned');
    }
    
    /**
     * Send homework due tomorrow reminder
     */
    public function homework_due_tomorrow($homework_id, $branch_id) {
        // Get template
        $template = $this->get_sms_template(6, $branch_id);
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }
        
        // Get homework details
        $homework = $this->ci->db->get_where('homework', ['id' => $homework_id])->row();
        if (!$homework) {
            return ['success' => false, 'message' => 'Homework not found'];
        }
        
        // Get students in class/section
        $students = $this->ci->application_model->getStudentListByClassSection(
            $homework->class_id, 
            $homework->section_id, 
            $branch_id
        );
        
        $subject_name = get_type_name_by_id('subject', $homework->subject_id);
        
        // Filter students who haven't submitted yet
        $pending_students = [];
        foreach ($students as $student) {
            $submission = $this->ci->homework_model->getStudentSubmission($homework_id, $student['student_id']);
            if (!$submission || $submission->submission_status == 'returned') {
                $pending_students[] = $student;
            }
        }
        
        if (empty($pending_students)) {
            return ['success' => true, 'message' => 'No pending submissions'];
        }
        
        // Prepare recipients
        $recipients = $this->prepare_recipients($pending_students, $template);
        
        // Prepare message with due tomorrow emphasis
        $message_template = "REMINDER: Homework due tomorrow!\n\n";
        $message_template .= $template['template_body'];
        $message_template = str_replace('{date_of_homework}', _d($homework->date_of_homework), $message_template);
        $message_template = str_replace('{date_of_submission}', _d($homework->date_of_submission), $message_template);
        $message_template = str_replace('{subject}', $subject_name, $message_template);
        
        // Send SMS
        return $this->send_bulk_sms($recipients, $message_template, $branch_id, $homework_id, 'due_tomorrow');
    }
    
    /**
     * Send homework overdue alert
     */
    public function homework_overdue($homework_id, $branch_id) {
        // Get template
        $template = $this->get_sms_template(6, $branch_id);
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }
        
        // Get homework details
        $homework = $this->ci->db->get_where('homework', ['id' => $homework_id])->row();
        if (!$homework) {
            return ['success' => false, 'message' => 'Homework not found'];
        }
        
        // Get students in class/section
        $students = $this->ci->application_model->getStudentListByClassSection(
            $homework->class_id, 
            $homework->section_id, 
            $branch_id
        );
        
        $subject_name = get_type_name_by_id('subject', $homework->subject_id);
        
        // Filter students who haven't submitted (excluding those already marked late)
        $missing_students = [];
        foreach ($students as $student) {
            $submission = $this->ci->homework_model->getStudentSubmission($homework_id, $student['student_id']);
            if (!$submission || ($submission->submission_status != 'submitted' && $submission->submission_status != 'late')) {
                $missing_students[] = $student;
            }
        }
        
        if (empty($missing_students)) {
            return ['success' => true, 'message' => 'No missing submissions'];
        }
        
        // Prepare recipients
        $recipients = $this->prepare_recipients($missing_students, $template);
        
        // Prepare message
        $days_overdue = floor((time() - strtotime($homework->date_of_submission)) / 86400);
        $message_template = "⚠️ HOMEWORK OVERDUE!\n\n";
        $message_template .= "Your {$subject_name} homework is {$days_overdue} day(s) overdue.\n";
        $message_template .= "Please submit as soon as possible.\n\n";
        $message_template .= $template['template_body'];
        $message_template = str_replace('{date_of_submission}', _d($homework->date_of_submission), $message_template);
        $message_template = str_replace('{subject}', $subject_name, $message_template);
        
        // Send SMS
        return $this->send_bulk_sms($recipients, $message_template, $branch_id, $homework_id, 'overdue');
    }
    
    /**
     * Send homework graded notification
     */
    public function homework_graded($submission_id, $branch_id) {
        // Get template
        $template = $this->get_sms_template(6, $branch_id);
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }
        
        // Get submission details
        $submission = $this->ci->homework_model->getSubmissionById($submission_id);
        if (!$submission) {
            return ['success' => false, 'message' => 'Submission not found'];
        }
        
        // Get student
        $student = $this->ci->db->get_where('student', ['id' => $submission->student_id])->row();
        if (!$student) {
            return ['success' => false, 'message' => 'Student not found'];
        }
        
        $subject_name = get_type_name_by_id('subject', $submission->subject_id);
        
        // Prepare recipients (student + parent)
        $recipients = [];
        
        // Student
        if (!empty($student->mobileno) && $template['notify_student'] == 1) {
            $recipients[] = [
                'name' => $student->first_name . ' ' . $student->last_name,
                'mobileno' => $student->mobileno,
                'type' => 'student',
                'student_id' => $student->id,
                'register_no' => $student->register_no ?? ''
            ];
        }
        
        // Parent
        if (!empty($student->parent_id) && $template['notify_parent'] == 1) {
            $parent = $this->ci->db->get_where('parent', ['id' => $student->parent_id])->row();
            if ($parent && !empty($parent->mobileno)) {
                $recipients[] = [
                    'name' => $parent->name,
                    'mobileno' => $parent->mobileno,
                    'type' => 'parent',
                    'parent_id' => $parent->id
                ];
            }
        }
        
        if (empty($recipients)) {
            return ['success' => false, 'message' => 'No recipients'];
        }
        
        // Prepare message
        $message_template = "✅ Homework Graded!\n\n";
        $message_template .= "Subject: {$subject_name}\n";
        $message_template .= "Grade: {$submission->grade}%\n";
        if (!empty($submission->feedback)) {
            $message_template .= "Feedback: " . substr($submission->feedback, 0, 100) . "\n";
        }
        $message_template .= "\nLogin to view full feedback.";
        
        // Send SMS
        return $this->send_bulk_sms($recipients, $message_template, $branch_id, $submission->homework_id, 'graded');
    }
    
    /**
     * Send missing homework reminder (weekly summary)
     */
    public function missing_homework_reminder($branch_id, $student_id = null) {
        // Get template
        $template = $this->get_sms_template(6, $branch_id);
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }
        
        // Get missing homework for past week
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $today = date('Y-m-d');
        
        $this->ci->db->select('h.*, s.name as subject_name')
                    ->from('homework h')
                    ->join('subject s', 's.id = h.subject_id')
                    ->where('h.branch_id', $branch_id)
                    ->where('h.date_of_submission >=', $week_ago)
                    ->where('h.date_of_submission <', $today);
        
        if ($student_id) {
            // For specific student
            $homeworks = $this->ci->db->get()->result();
            
            $missing_list = [];
            foreach ($homeworks as $homework) {
                $submission = $this->ci->homework_model->getStudentSubmission($homework->id, $student_id);
                if (!$submission || $submission->submission_status == 'returned') {
                    $missing_list[] = $homework->subject_name;
                }
            }
            
            if (empty($missing_list)) {
                return ['success' => true, 'message' => 'No missing homework'];
            }
            
            // Get student details
            $student = $this->ci->db->get_where('student', ['id' => $student_id])->row();
            if (!$student) {
                return ['success' => false, 'message' => 'Student not found'];
            }
            
            // Prepare message
            $message = "📚 Missing Homework Summary\n\n";
            $message .= "Dear " . ($student->first_name ?? 'Student') . ",\n";
            $message .= "You have " . count($missing_list) . " missing homework(s):\n";
            $message .= implode("\n", $missing_list);
            $message .= "\n\nPlease complete and submit.";
            
            // Send to student and parent
            $recipients = [];
            if (!empty($student->mobileno)) {
                $recipients[] = [
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'mobileno' => $student->mobileno,
                    'type' => 'student'
                ];
            }
            if (!empty($student->parent_id)) {
                $parent = $this->ci->db->get_where('parent', ['id' => $student->parent_id])->row();
                if ($parent && !empty($parent->mobileno)) {
                    $recipients[] = [
                        'name' => $parent->name,
                        'mobileno' => $parent->mobileno,
                        'type' => 'parent'
                    ];
                }
            }
            
            return $this->send_bulk_sms($recipients, $message, $branch_id, null, 'missing_reminder');
            
        } else {
            // For all students with missing homework (run via cron)
            // Get all enrollments
            $enrollments = $this->ci->db->select('e.student_id, e.class_id, e.section_id, s.first_name, s.last_name, s.mobileno, s.parent_id')
                                        ->from('enroll e')
                                        ->join('student s', 's.id = e.student_id')
                                        ->where('e.session_id', get_session_id())
                                        ->where('e.branch_id', $branch_id)
                                        ->get()
                                        ->result();
            
            $sent_count = 0;
            foreach ($enrollments as $enrollment) {
                $missing_list = [];
                foreach ($homeworks as $homework) {
                    if ($homework->class_id == $enrollment->class_id && $homework->section_id == $enrollment->section_id) {
                        $submission = $this->ci->homework_model->getStudentSubmission($homework->id, $enrollment->student_id);
                        if (!$submission || $submission->submission_status == 'returned') {
                            $missing_list[] = $homework->subject_name;
                        }
                    }
                }
                
                if (!empty($missing_list)) {
                    $message = "📚 Missing Homework Summary\n\n";
                    $message .= "Dear " . ($enrollment->first_name ?? 'Student') . ",\n";
                    $message .= "You have " . count($missing_list) . " missing homework(s) from last week.\n";
                    $message .= "Please check the portal and submit.";
                    
                    $recipients = [];
                    if (!empty($enrollment->mobileno)) {
                        $recipients[] = [
                            'name' => $enrollment->first_name . ' ' . $enrollment->last_name,
                            'mobileno' => $enrollment->mobileno,
                            'type' => 'student'
                        ];
                    }
                    if (!empty($enrollment->parent_id)) {
                        $parent = $this->ci->db->get_where('parent', ['id' => $enrollment->parent_id])->row();
                        if ($parent && !empty($parent->mobileno)) {
                            $recipients[] = [
                                'name' => $parent->name,
                                'mobileno' => $parent->mobileno,
                                'type' => 'parent'
                            ];
                        }
                    }
                    
                    $this->send_bulk_sms($recipients, $message, $branch_id, null, 'missing_reminder');
                    $sent_count++;
                    
                    // Delay to prevent rate limiting
                    usleep(500000);
                }
            }
            
            return ['success' => true, 'message' => "Sent to {$sent_count} students"];
        }
    }
    
    /**
     * Get SMS template from database
     */
    private function get_sms_template($template_id, $branch_id) {
        return $this->ci->db->get_where('sms_template_details', [
            'template_id' => $template_id,
            'branch_id' => $branch_id
        ])->row_array();
    }
    
    /**
     * Prepare recipients array from student list
     */
    private function prepare_recipients($students, $template) {
        $recipients = [];
        
        foreach ($students as $student) {
            // Student
            if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
                $recipients[] = [
                    'name' => $student['fullname'] ?? ($student['first_name'] . ' ' . ($student['last_name'] ?? '')),
                    'mobileno' => $student['mobileno'],
                    'type' => 'student',
                    'student_id' => $student['student_id'] ?? null,
                    'register_no' => $student['register_no'] ?? '',
                    'roll' => $student['roll'] ?? '',
                    'class_name' => $student['class_name'] ?? '',
                    'section_name' => $student['section_name'] ?? ''
                ];
            }
            
            // Parent
            if ($template['notify_parent'] == 1 && !empty($student['parent_id'])) {
                $parent = $this->ci->db->select('id, name, mobileno')
                                      ->where('id', $student['parent_id'])
                                      ->get('parent')
                                      ->row();
                if ($parent && !empty($parent->mobileno)) {
                    $recipients[] = [
                        'name' => $parent->name,
                        'mobileno' => $parent->mobileno,
                        'type' => 'parent',
                        'parent_id' => $parent->id
                    ];
                }
            }
        }
        
        return $recipients;
    }
    
    /**
     * Send bulk SMS to recipients
     */
    private function send_bulk_sms($recipients, $message, $branch_id, $homework_id, $notification_type) {
        if (empty($recipients)) {
            return ['success' => false, 'message' => 'No recipients'];
        }
        
        // Check credits
        $credits_needed = $this->calculate_sms_cost($message, count($recipients));
        $current_credits = $this->ci->sendsmsmail_model->get_sms_credit($branch_id);
        
        if ($current_credits < $credits_needed) {
            log_message('error', "Notification: Insufficient credits for {$notification_type}. Needed: {$credits_needed}, Available: {$current_credits}");
            return ['success' => false, 'message' => 'Insufficient credits', 'credits_needed' => $credits_needed];
        }
        
        // Initialize SMS library for this branch
        $sms_lib = $this->init_sms_library($branch_id);
        
        if (!$sms_lib->is_configured()) {
            log_message('error', "Notification: SMS gateway not configured for branch {$branch_id}");
            return ['success' => false, 'message' => 'SMS gateway not configured'];
        }
        
        // Send SMS to each recipient
        $success_count = 0;
        $failed_count = 0;
        $failed_recipients = [];
        
        foreach ($recipients as $recipient) {
            $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
            
            // Format Kenyan number
            if (strlen($mobile) == 9) {
                $mobile = '254' . $mobile;
            } elseif (strlen($mobile) == 10 && substr($mobile, 0, 1) == '0') {
                $mobile = '254' . substr($mobile, 1);
            }
            
            if (empty($mobile)) {
                $failed_count++;
                continue;
            }
            
            // Personalize message
            $personalized_message = $message;
            $personalized_message = str_replace('{name}', $recipient['name'], $personalized_message);
            $personalized_message = str_replace('{register_no}', $recipient['register_no'] ?? '', $personalized_message);
            $personalized_message = str_replace('{roll}', $recipient['roll'] ?? '', $personalized_message);
            $personalized_message = str_replace('{class}', $recipient['class_name'] ?? '', $personalized_message);
            $personalized_message = str_replace('{section}', $recipient['section_name'] ?? '', $personalized_message);
            
            $response = $sms_lib->send($mobile, $personalized_message);
            
            if ($this->is_successful_response($response)) {
                $success_count++;
            } else {
                $failed_count++;
                $failed_recipients[] = $mobile;
                log_message('error', "Notification: SMS failed to {$mobile}. Response: " . substr($response, 0, 200));
            }
            
            usleep(200000); // 0.2 second delay
        }
        
        // Deduct credits
        if ($success_count > 0) {
            $credits_to_deduct = ceil(($credits_needed * $success_count) / count($recipients));
            $this->ci->sendsmsmail_model->deduct_sms_units($branch_id, $credits_to_deduct);
        }
        
        // Log to database
        $this->log_notification($homework_id, $notification_type, $recipients, $success_count, $failed_count, $branch_id);
        
        return [
            'success' => $success_count > 0,
            'sent' => $success_count,
            'failed' => $failed_count,
            'total' => count($recipients),
            'credits_used' => $credits_to_deduct ?? 0,
            'failed_recipients' => $failed_recipients
        ];
    }
    
    /**
     * Calculate SMS cost
     */
    private function calculate_sms_cost($message, $recipient_count) {
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
        
        return $sms_parts * $recipient_count * $credits_per_part;
    }
    
    /**
     * Check if SMS response is successful
     */
    private function is_successful_response($response) {
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
     * Log notification to database
     */
    private function log_notification($homework_id, $notification_type, $recipients, $sent, $failed, $branch_id) {
        // Create table if not exists
        if (!$this->ci->db->table_exists('homework_notification_logs')) {
            $this->ci->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => TRUE],
                'homework_id' => ['type' => 'INT', 'constraint' => 11],
                'notification_type' => ['type' => 'VARCHAR', 'constraint' => 50],
                'recipient_count' => ['type' => 'INT', 'constraint' => 11],
                'sent_count' => ['type' => 'INT', 'constraint' => 11],
                'failed_count' => ['type' => 'INT', 'constraint' => 11],
                'branch_id' => ['type' => 'INT', 'constraint' => 11],
                'created_at' => ['type' => 'DATETIME']
            ]);
            $this->ci->dbforge->add_key('id', TRUE);
            $this->ci->dbforge->create_table('homework_notification_logs', TRUE);
        }
        
        $log_data = [
            'homework_id' => $homework_id,
            'notification_type' => $notification_type,
            'recipient_count' => count($recipients),
            'sent_count' => $sent,
            'failed_count' => $failed,
            'branch_id' => $branch_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->ci->db->insert('homework_notification_logs', $log_data);
    }
}