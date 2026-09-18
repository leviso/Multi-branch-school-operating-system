<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Homework_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getList($classID, $sectionID, $subjectID, $branchID)
    {
        $this->db->select('homework.*,subject.name as subject_name,class.name as class_name,section.name as section_name,staff.name as creator_name');
        $this->db->from('homework');
        $this->db->join('subject', 'subject.id = homework.subject_id', 'left');
        $this->db->join('class', 'class.id = homework.class_id', 'left');
        $this->db->join('section', 'section.id = homework.section_id', 'left');
        $this->db->join('staff', 'staff.id = homework.created_by', 'left');
        $this->db->where('homework.class_id', $classID);
        $this->db->where('homework.section_id', $sectionID);
        $this->db->where('homework.subject_id', $subjectID);
        $this->db->where('homework.branch_id', $branchID);
        $this->db->where('homework.session_id', get_session_id());
        $this->db->order_by('homework.id', 'desc');
        return $this->db->get()->result_array();
    }
           /**
     * Get homework list for a specific student (respecting subject enrollment)
     * @param int $student_id
     * @param int $branch_id
     * @return array
     */
    public function getStudentHomeworkList($student_id, $branch_id)
    {
        $this->load->model('student_subject_model');
        
        // Get subjects the student is enrolled in
        $enrolled_subjects = $this->student_subject_model->get_enrolled_subject_ids($student_id, $branch_id);
        
        // Get student's class and section from enrollment
        $enroll = $this->db->select('class_id, section_id')
                           ->where('student_id', $student_id)
                           ->where('session_id', get_session_id())
                           ->get('enroll')
                           ->row_array();
        
        // If no subject enrollments, fall back to all subjects in class/section
        if (empty($enrolled_subjects) && !empty($enroll)) {
            log_message('debug', "No subject enrollments for student {$student_id}, showing all homework");
            return $this->db->select('h.*, sub.name as subject_name, c.name as class_name, sec.name as section_name,
                                     hs.id as submission_id, hs.submission_status, hs.feedback, hs.grade')
                            ->from('homework h')
                            ->join('subject sub', 'sub.id = h.subject_id')
                            ->join('class c', 'c.id = h.class_id')
                            ->join('section sec', 'sec.id = h.section_id')
                            ->join('homework_submit hs', 'hs.homework_id = h.id AND hs.student_id = ' . $this->db->escape($student_id), 'left')
                            ->where('h.class_id', $enroll['class_id'])
                            ->where('h.section_id', $enroll['section_id'])
                            ->where('h.branch_id', $branch_id)
                            ->where('h.session_id', get_session_id())
                            ->order_by('h.date_of_submission', 'ASC')
                            ->get()
                            ->result_array();
        }
        
        // Get homework only for enrolled subjects
        return $this->db->select('h.*, sub.name as subject_name, c.name as class_name, sec.name as section_name,
                                 hs.id as submission_id, hs.submission_status, hs.feedback, hs.grade')
                        ->from('homework h')
                        ->join('subject sub', 'sub.id = h.subject_id')
                        ->join('class c', 'c.id = h.class_id')
                        ->join('section sec', 'sec.id = h.section_id')
                        ->join('homework_submit hs', 'hs.homework_id = h.id AND hs.student_id = ' . $this->db->escape($student_id), 'left')
                        ->where_in('h.subject_id', $enrolled_subjects)
                        ->where('h.branch_id', $branch_id)
                        ->where('h.session_id', get_session_id())
                        ->order_by('h.date_of_submission', 'ASC')
                        ->get()
                        ->result_array();
    }

    public function evaluationCounter($classID, $sectionID, $homeworkID)
    {
        $countStu = $this->db->where(array('class_id' => $classID, 'section_id' => $sectionID, 'session_id' => get_session_id()))->get('enroll')->num_rows();
        $countEva = $this->db->where(array('homework_id' => $homeworkID, 'status' => 'c'))->get('homework_evaluation')->num_rows();
        $incomplete = ($countStu - $countEva);
        return array('total' => $countStu, 'complete' => $countEva, 'incomplete' => $incomplete);
    }

    public function getEvaluate($homeworkID)
    {
        $this->db->select('homework.*,CONCAT_WS(" ",s.first_name, s.last_name) as fullname,s.register_no,e.student_id, e.roll,subject.name as subject_name,class.name as class_name,section.name as section_name,he.id as ev_id,he.status as ev_status,he.remark as ev_remarks,he.rank,hs.message,hs.enc_name');
        $this->db->from('homework');
        $this->db->join('enroll as e', 'e.class_id=homework.class_id and e.section_id = homework.section_id and e.session_id = homework.session_id', 'inner');
        $this->db->join('student as s', 'e.student_id = s.id', 'inner');
        $this->db->join('homework_evaluation as he', 'he.homework_id = homework.id and he.student_id = e.student_id', 'left');
        $this->db->join('homework_submit as hs', 'hs.homework_id = homework.id and hs.student_id = e.student_id', 'left');
        $this->db->join('subject', 'subject.id = homework.subject_id', 'left');
        $this->db->join('class', 'class.id = homework.class_id', 'left');
        $this->db->join('section', 'section.id = homework.section_id', 'left');
        $this->db->where('homework.id', $homeworkID);
        if (!is_superadmin_loggedin()) {
            $this->db->where('homework.branch_id', get_loggedin_branch_id());
        }
        $this->db->where('homework.session_id', get_session_id());
        $this->db->order_by('homework.id', 'desc');
        return $this->db->get()->result_array();
    }

    // save student homework in DB
    public function save($data)
    {
        $status = isset($data['published_later']) ? TRUE : FALSE;
        $sms_notification = isset($data['notification_sms']) ? TRUE : FALSE;
        $branch_id = $this->application_model->get_branch_id();
        
        // For superadmin, use selected branch
        if (is_superadmin_loggedin() && isset($data['branch_id']) && !empty($data['branch_id'])) {
            $branch_id = $data['branch_id'];
        }
        
        $arrayHomework = array(
            'branch_id' => $branch_id,
            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'], 
            'session_id' => get_session_id(), 
            'subject_id' => $data['subject_id'], 
            'date_of_homework' => date("Y-m-d", strtotime($data['date_of_homework'])), 
            'date_of_submission' => date("Y-m-d", strtotime($data['date_of_submission'])), 
            'description' => $data['homework'], 
            'created_by' => get_loggedin_user_id(), 
            'create_date' => date("Y-m-d"), 
            'status' => $status, 
            'sms_notification' => $sms_notification, 
            'assignment_status' => isset($data['enable_targeting']) ? 'targeted' : 'assigned'
        );
        
        if ($status == TRUE) {
            $arrayHomework['schedule_date'] = date("Y-m-d", strtotime($data['schedule_date']));
        } else {
            $arrayHomework['schedule_date'] = null;
        }
        
        if (isset($data['homework_id'])) {
            if (!is_superadmin_loggedin()) 
                $this->db->where('branch_id', $branch_id);
            $this->db->where('id', $data['homework_id']);
            $this->db->update('homework', $arrayHomework);
            $insert_id = $data['homework_id'];
        } else {
            $this->db->insert('homework', $arrayHomework);
            $insert_id = $this->db->insert_id();
        }

        // Handle file upload
        if (isset($_FILES["attachment_file"]) && !empty($_FILES['attachment_file']['name'])) {
            $uploaddir = './uploads/attachments/homework/';
            if (!is_dir($uploaddir) && !mkdir($uploaddir, 0777, true)) {
                // log_message('error', "Failed to create upload directory: {$uploaddir}");
            }
            $fileInfo = pathinfo($_FILES["attachment_file"]["name"]);
            $document = basename($_FILES['attachment_file']['name']);

            $file_name = $insert_id . '.' . $fileInfo['extension'];
            move_uploaded_file($_FILES["attachment_file"]["tmp_name"], $uploaddir . $file_name);
        } else {
            if (isset($data['old_document'])) {
               $document = $data['old_document'];
            } else {
                $document = "";
            }
        }

        $this->db->where('id', $insert_id);
        $this->db->update('homework', array('document' => $document));

        // ========== SAVE TARGETING DATA (Differentiated Learning) ==========
        if (isset($data['enable_targeting']) && $data['enable_targeting'] == 1) {
            $target_group = $data['target_group'];
            $target_students = isset($data['target_students']) ? $data['target_students'] : [];
            
            $target_data = [];
            
            if ($target_group == 'individual' && !empty($target_students)) {
                foreach ($target_students as $student_id) {
                    $target_data[] = [
                        'student_id' => $student_id,
                        'target_group' => 'individual'
                    ];
                }
            } elseif ($target_group != 'all') {
                $target_data[] = [
                    'student_id' => null,
                    'target_group' => $target_group
                ];
            }
            
            $this->saveHomeworkTargets($insert_id, $target_data, $branch_id);
        } else {
            // Remove any existing targets
            $this->saveHomeworkTargets($insert_id, [], $branch_id);
        }
        // ========== END TARGETING DATA ==========

                // ========== SMS NOTIFICATION ==========
        if (isset($data['notification_sms']) && !$status) {
            // Check if targeting is enabled (differentiated learning)
            if (isset($data['enable_targeting']) && $data['enable_targeting'] == 1) {
                // Use targeted students
                $students = $this->getTargetedStudents($insert_id, $arrayHomework['class_id'], $arrayHomework['section_id'], $branch_id);
                log_message('info', "Using targeted students: " . count($students));
            } else {
                // Use subject enrollment to filter students who take this subject
                $students = $this->get_enrolled_students_for_subject(
                    $arrayHomework['subject_id'],
                    $arrayHomework['class_id'],
                    $arrayHomework['section_id'],
                    $branch_id
                );
                log_message('info', "Using subject-enrolled students: " . count($students));
            }
            
            log_message('info', "Students found for SMS: " . count($students));
            
            // Schedule immediate SMS notification
            $result = $this->schedule_immediate_homework_sms($insert_id, $arrayHomework, $students);
            log_message('info', "SMS scheduling result: " . ($result ? 'SUCCESS' : 'FAILED'));
        }
        // ========== END SMS CODE ==========
    }

    /**
     * Schedule immediate SMS for homework - Respects targeting
     */
    private function schedule_immediate_homework_sms($homework_id, $homework_data, $students)
    {
        // Get SMS template
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 6,
            'branch_id' => $homework_data['branch_id']
        ])->row_array();
        
        if (empty($template)) {
            log_message('error', "No SMS template found for immediate homework notification");
            return false;
        }
        
        // $students is already filtered by targeting in the save() method
        // So we use it directly - no need to refetch
        if (empty($students)) {
            log_message('warning', "No students found for homework ID {$homework_id}");
            return true;
        }
        
        // Prepare recipients (only from the filtered student list)
        $recipients = [];
        foreach ($students as $student) {
            // Student
            if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
                $recipients[] = [
                    'name' => $student['fullname'],
                    'email' => $student['email'] ?? '',
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
                $parent = $this->db->select('id, name, mobileno, email')
                    ->where('id', $student['parent_id'])
                    ->get('parent')
                    ->row_array();
                
                if (!empty($parent['mobileno'])) {
                    $recipients[] = [
                        'name' => $parent['name'],
                        'email' => $parent['email'] ?? '',
                        'mobileno' => $parent['mobileno'],
                        'type' => 'parent',
                        'parent_id' => $parent['id']
                    ];
                }
            }
        }
        
        if (empty($recipients)) {
            log_message('warning', "No recipients for immediate homework SMS - Homework ID: {$homework_id}");
            return true;
        }
        
        // Prepare message template (with placeholders)
        $message_template = $template['template_body'];
        $message_template = str_replace('{date_of_homework}', $homework_data['date_of_homework'], $message_template);
        $message_template = str_replace('{date_of_submission}', $homework_data['date_of_submission'], $message_template);
        $message_template = str_replace('{subject}', get_type_name_by_id('subject', $homework_data['subject_id']), $message_template);
        
        // Calculate credits based on actual recipients count
        $credits_needed = $this->calculate_sms_cost_immediate($message_template, count($recipients));
        
        // Check credits
        $ci =& get_instance();
        $ci->load->model('sendsmsmail_model');
        $current_credits = $ci->sendsmsmail_model->get_sms_credit($homework_data['branch_id']);
        
        if ($current_credits < $credits_needed) {
            log_message('error', "Insufficient credits for immediate homework SMS. Needed: {$credits_needed}, Available: {$current_credits}");
            
            // Mark homework as SMS failed
            $this->db->where('id', $homework_id);
            $this->db->update('homework', [
                'sms_notification' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return false;
        }
        
        // Send SMS only to targeted recipients
        $success_count = 0;
        $failed_count = 0;
        
        // Load SMS library
        $ci->load->library('bulksmsbd', array('branch_id' => $homework_data['branch_id']), 'sms_lib');
        
        // Check if library is configured
        if (!$ci->sms_lib->is_configured()) {
            log_message('error', "SMS gateway not configured for branch {$homework_data['branch_id']}");
            return false;
        }
        
        // Process each recipient (only targeted ones)
        foreach ($recipients as $recipient) {
            // Personalize message for this recipient
            $personalized_message = $message_template;
            $personalized_message = str_replace('{name}', $recipient['name'], $personalized_message);
            $personalized_message = str_replace('{register_no}', $recipient['register_no'] ?? '', $personalized_message);
            $personalized_message = str_replace('{roll}', $recipient['roll'] ?? '', $personalized_message);
            $personalized_message = str_replace('{class}', $recipient['class_name'] ?? '', $personalized_message);
            $personalized_message = str_replace('{section}', $recipient['section_name'] ?? '', $personalized_message);
            
            // Clean mobile number
            $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
            
            if (empty($mobile)) {
                log_message('warning', "Empty mobile number for recipient: " . $recipient['name']);
                $failed_count++;
                continue;
            }
            
            // Format Kenyan number
            if (strlen($mobile) == 9) {
                $mobile = '254' . $mobile;
            } elseif (strlen($mobile) == 10 && substr($mobile, 0, 1) == '0') {
                $mobile = '254' . substr($mobile, 1);
            }
            
            // Send SMS
            log_message('info', "Sending homework SMS to: {$mobile}, Name: " . $recipient['name']);
            $response = $ci->sms_lib->send($mobile, $personalized_message);
            
            // Check response
            $success = $this->is_successful_sms_response($response);
            
            if ($success) {
                $success_count++;
                log_message('info', "✅ SMS sent successfully to {$mobile}");
            } else {
                $failed_count++;
                log_message('error', "❌ SMS failed to {$mobile}. Response: {$response}");
            }
            
            // Log to homework_sms_logs
            $log_data = [
                'homework_id' => $homework_id,
                'recipient_contact' => $recipient['mobileno'],
                'recipient_name' => $recipient['name'],
                'recipient_type' => $recipient['type'],
                'status' => $success ? 'sent' : 'failed',
                'sent_at' => date('Y-m-d H:i:s'),
                'branch_id' => $homework_data['branch_id']
            ];
            $this->db->insert('homework_sms_logs', $log_data);
            
            // Small delay to prevent rate limiting
            usleep(300000); // 0.3 second
        }
        
        // Create bulk_sms_email entry for record keeping
        $campaign_name = "Homework: " . get_type_name_by_id('subject', $homework_data['subject_id']) . 
                        " - " . date('d/m H:i') . " (Targeted)";
        
        $message_data = [
            'campaign_name' => $campaign_name,
            'message' => $message_template,
            'message_type' => 1,
            'recipient_type' => 4,
            'recipients_details' => json_encode([
                'homework_id' => $homework_id,
                'immediate' => true,
                'targeted' => true,
                'targeted_count' => count($recipients),
                'created_by' => $homework_data['created_by'],
                'success_count' => $success_count,
                'failed_count' => $failed_count
            ]),
            'additional' => json_encode($recipients),
            'schedule_time' => date('Y-m-d H:i:s'),
            'posting_status' => ($success_count > 0 ? 2 : 3), // 2=sent, 3=failed
            'total_thread' => count($recipients),
            'successfully_sent' => $success_count,
            'sms_gateway' => 'bulksmsbd',
            'credits_used' => $credits_needed,
            'branch_id' => $homework_data['branch_id'],
            'send_type' => 'immediate_homework_targeted',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('bulk_sms_email', $message_data);
        $message_id = $this->db->insert_id();
        
        // Deduct credits immediately
        $ci->sendsmsmail_model->deduct_sms_units($homework_data['branch_id'], $credits_needed);
        
        // Update homework status
        $sms_status = ($success_count > 0) ? 3 : 0; // 3=sent, 0=failed
        $this->db->where('id', $homework_id);
        $this->db->update('homework', [
            'sms_notification' => $sms_status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        log_message('info', "Immediate homework SMS completed. Targeted recipients: " . count($recipients) . ", Success: {$success_count}, Failed: {$failed_count}, Message ID: {$message_id}, Credits: {$credits_needed}");
        
        // Return true if at least one SMS was sent
        return $success_count > 0;
    }

// Add this helper method to check SMS response
private function is_successful_sms_response($response)
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

private function calculate_sms_cost_immediate($message, $recipient_count)
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
    
    return $sms_parts * $recipient_count * $credits_per_part;
}

    // ========== ENHANCED HOMEWORK METHODS (Phase 1) ==========
    // Add these before the closing } of the class

    /**
     * Get homework with submission status for student/parent view
     */
    public function getStudentHomeworkWithSubmission($student_id, $branch_id, $class_id = null)
    {
        $this->db->select('h.*, s.name as subject_name, c.name as class_name, sec.name as section_name,
                          hs.id as submission_id, hs.message as submission_message, hs.file_name, hs.enc_name,
                          hs.submission_status, hs.feedback, hs.grade, hs.feedback_date, hs.viewed_at,
                          hs.created_at as submitted_at, he.rank as evaluation_rank, he.remark as evaluation_remark')
                 ->from('homework h')
                 ->join('subject s', 's.id = h.subject_id')
                 ->join('class c', 'c.id = h.class_id')
                 ->join('section sec', 'sec.id = h.section_id')
                 ->join('enroll e', 'e.class_id = h.class_id AND e.section_id = h.section_id AND e.session_id = h.session_id')
                 ->join('student st', 'st.id = e.student_id')
                 ->join('homework_submit hs', 'hs.homework_id = h.id AND hs.student_id = st.id', 'left')
                 ->join('homework_evaluation he', 'he.homework_id = h.id AND he.student_id = st.id', 'left')
                 ->where('st.id', $student_id)
                 ->where('h.branch_id', $branch_id)
                 ->where('h.session_id', get_session_id())
                 ->order_by('h.date_of_homework', 'DESC');

        if ($class_id) {
            $this->db->where('h.class_id', $class_id);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Get all submissions for a specific homework (Teacher view)
     */
    public function getSubmissionsForTeacher($homework_id)
    {
        $this->db->select('hs.*, s.first_name, s.last_name, s.register_no, s.mobileno,
                          p.name as parent_name, p.mobileno as parent_mobile,
                          he.rank as evaluation_rank, he.remark as evaluation_remark,
                          he.status as evaluation_status')
                 ->from('homework_submit hs')
                 ->join('student s', 's.id = hs.student_id')
                 ->join('parent p', 'p.id = s.parent_id', 'left')
                 ->join('homework_evaluation he', 'he.homework_id = hs.homework_id AND he.student_id = hs.student_id', 'left')
                 ->where('hs.homework_id', $homework_id)
                 ->order_by('hs.submission_status', 'ASC')
                 ->order_by('s.first_name', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Save feedback for a student submission (with audit log)
     */
    public function saveSubmissionFeedback($submission_id, $feedback_data)
    {
        // Get current submission data for audit
        $current = $this->getSubmissionById($submission_id);
        
        if (!$current) {
            return false;
        }
        
        // Update submission
        $this->db->where('id', $submission_id);
        $result = $this->db->update('homework_submit', $feedback_data);
        
        if ($result && ($current->feedback != ($feedback_data['feedback'] ?? '') || $current->grade != ($feedback_data['grade'] ?? ''))) {
            // Log the change for audit
            $log_data = [
                'submission_id' => $submission_id,
                'previous_feedback' => $current->feedback,
                'new_feedback' => $feedback_data['feedback'] ?? $current->feedback,
                'previous_grade' => $current->grade,
                'new_grade' => $feedback_data['grade'] ?? $current->grade,
                'changed_by' => get_loggedin_user_id(),
                'branch_id' => $current->branch_id ?? get_loggedin_branch_id()
            ];
            
            if ($this->db->table_exists('homework_feedback_logs')) {
                $this->db->insert('homework_feedback_logs', $log_data);
            }
        }
        
        return $result;
    }

    /**
     * Get submission by ID
     */
    public function getSubmissionById($submission_id)
    {
        return $this->db->select('hs.*, s.first_name, s.last_name, s.register_no, s.parent_id,
                                 h.subject_id, h.class_id, h.section_id, h.date_of_submission, h.branch_id')
                        ->from('homework_submit hs')
                        ->join('student s', 's.id = hs.student_id')
                        ->join('homework h', 'h.id = hs.homework_id')
                        ->where('hs.id', $submission_id)
                        ->get()
                        ->row();
    }

    /**
     * Mark submission as viewed by student/parent
     */
    public function markSubmissionAsViewed($homework_id, $student_id)
    {
        $submission = $this->db->where('homework_id', $homework_id)
                               ->where('student_id', $student_id)
                               ->get('homework_submit')
                               ->row();
        
        if ($submission && empty($submission->viewed_at)) {
            $this->db->where('id', $submission->id);
            return $this->db->update('homework_submit', ['viewed_at' => date('Y-m-d H:i:s')]);
        }
        
        return true;
    }

    /**
     * Check and mark submissions as late if past due date
     */
    public function checkAndMarkLateSubmissions()
    {
        $this->db->select('hs.id, hs.homework_id, hs.student_id, h.date_of_submission')
                 ->from('homework_submit hs')
                 ->join('homework h', 'h.id = hs.homework_id')
                 ->where('hs.submission_status', 'submitted')
                 ->where('h.date_of_submission <', date('Y-m-d'));
        
        $late_submissions = $this->db->get()->result_array();
        
        $count = 0;
        foreach ($late_submissions as $submission) {
            $this->db->where('id', $submission['id']);
            $this->db->update('homework_submit', ['submission_status' => 'late']);
            $count++;
        }
        
        return $count;
    }

    /**
     * Get pending submissions count for teacher dashboard
     */
    public function getPendingSubmissionsCount($teacher_id, $branch_id)
    {
        // Get subjects taught by teacher
        $subjects = $this->db->select('subject_id')
                            ->where('teacher_id', $teacher_id)
                            ->where('branch_id', $branch_id)
                            ->get('subject_assign')
                            ->result_array();
        
        if (empty($subjects)) {
            return 0;
        }
        
        $subject_ids = array_column($subjects, 'subject_id');
        
        // Count submissions with no feedback
        $this->db->select('COUNT(DISTINCT hs.id) as pending')
                 ->from('homework_submit hs')
                 ->join('homework h', 'h.id = hs.homework_id')
                 ->where_in('h.subject_id', $subject_ids)
                 ->where('h.branch_id', $branch_id)
                 ->where('hs.feedback IS NULL', null, false)
                 ->where('hs.submission_status !=', 'returned');
        
        $result = $this->db->get()->row();
        return (int)($result->pending ?? 0);
    }

    /**
     * Get homework completion statistics for analytics
     */
    public function getHomeworkCompletionStats($branch_id, $class_id = null, $date_from = null, $date_to = null)
    {
        $this->db->select('h.id, h.subject_id, sub.name as subject_name,
                          COUNT(DISTINCT e.student_id) as total_students,
                          COUNT(DISTINCT hs.id) as submissions_count,
                          COUNT(DISTINCT CASE WHEN hs.submission_status = "late" THEN hs.id END) as late_count,
                          COUNT(DISTINCT CASE WHEN hs.feedback IS NOT NULL THEN hs.id END) as graded_count')
                 ->from('homework h')
                 ->join('subject sub', 'sub.id = h.subject_id')
                 ->join('enroll e', 'e.class_id = h.class_id AND e.section_id = h.section_id AND e.session_id = h.session_id')
                 ->join('homework_submit hs', 'hs.homework_id = h.id AND hs.student_id = e.student_id', 'left')
                 ->where('h.branch_id', $branch_id)
                 ->where('h.session_id', get_session_id())
                 ->group_by('h.id');

        if ($class_id) {
            $this->db->where('h.class_id', $class_id);
        }
        if ($date_from) {
            $this->db->where('h.date_of_homework >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('h.date_of_homework <=', $date_to);
        }

        $result = $this->db->get()->result_array();
        
        // Calculate completion percentages
        foreach ($result as &$row) {
            $row['submission_rate'] = $row['total_students'] > 0 
                ? round(($row['submissions_count'] / $row['total_students']) * 100, 1) 
                : 0;
            $row['grading_rate'] = $row['submissions_count'] > 0 
                ? round(($row['graded_count'] / $row['submissions_count']) * 100, 1) 
                : 0;
        }
        
        return $result;
    }

    /**
     * Update homework assignment status (deprecating old 'status' field)
     */
    public function updateAssignmentStatus($homework_id, $status)
    {
        $allowed_status = ['assigned', 'in_progress', 'reviewed', 'closed'];
        if (!in_array($status, $allowed_status)) {
            return false;
        }
        
        $this->db->where('id', $homework_id);
        return $this->db->update('homework', [
            'assignment_status' => $status,
            'status' => ($status == 'closed' ? '2' : ($status == 'assigned' ? '0' : '1')) // Maintain backward compatibility
        ]);
    }

    /**
     * Get student submission for a specific homework
     */
    public function getStudentSubmission($homework_id, $student_id)
    {
        return $this->db->where('homework_id', $homework_id)
                        ->where('student_id', $student_id)
                        ->get('homework_submit')
                        ->row();
    }

    /**
     * Save or update student submission
     */
    public function saveStudentSubmission($data)
    {
        // Check if submission already exists
        $existing = $this->getStudentSubmission($data['homework_id'], $data['student_id']);
        
        if ($existing) {
            // Update existing
            $this->db->where('id', $existing->id);
            $this->db->update('homework_submit', [
                'message' => $data['message'] ?? '',
                'enc_name' => $data['enc_name'] ?? $existing->enc_name,
                'file_name' => $data['file_name'] ?? $existing->file_name,
                'submission_status' => $data['submission_status'] ?? 'submitted',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return $existing->id;
        } else {
            // Insert new
            $this->db->insert('homework_submit', [
                'homework_id' => $data['homework_id'],
                'student_id' => $data['student_id'],
                'message' => $data['message'] ?? '',
                'enc_name' => $data['enc_name'] ?? null,
                'file_name' => $data['file_name'] ?? null,
                'submission_status' => $data['submission_status'] ?? 'submitted',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return $this->db->insert_id();
        }
    }

    /**
     * Get targeted homework for a specific student (differentiated learning)
     */
    public function getTargetedHomework($student_id, $branch_id, $class_id = null)
    {
        $this->db->select('h.*, s.name as subject_name, c.name as class_name, sec.name as section_name,
                          ht.target_group,
                          hs.id as submission_id, hs.submission_status, hs.grade, hs.feedback')
                 ->from('homework h')
                 ->join('subject s', 's.id = h.subject_id')
                 ->join('class c', 'c.id = h.class_id')
                 ->join('section sec', 'sec.id = h.section_id')
                 ->join('homework_targets ht', 'ht.homework_id = h.id', 'left')
                 ->join('homework_submit hs', 'hs.homework_id = h.id AND hs.student_id = ' . $this->db->escape($student_id), 'left')
                 ->where('h.branch_id', $branch_id)
                 ->where('h.session_id', get_session_id())
                 ->group_start()
                 ->where('ht.student_id', $student_id)
                 ->or_where('ht.target_group', 'all')
                 ->or_where('ht.target_group', 'standard')
                 ->group_end();

        if ($class_id) {
            $this->db->where('h.class_id', $class_id);
        }

        $this->db->order_by('h.date_of_homework', 'DESC');
        return $this->db->get()->result_array();
    }
    // ========== ANALYTICS METHODS ==========
    
 /**
 * Get subjects for user (admin/teacher/superadmin)
 * @param int $user_id
 * @param int|null $branch_id
 * @param bool $is_superadmin
 * @return array
 */
public function getTeacherSubjects($user_id, $branch_id = null, $is_superadmin = false)
{
    $user_role = loggedin_role_id();
    
    if ($is_superadmin) {
        // Superadmin: Get ALL subjects from ALL branches
        $this->db->select('s.id, s.name, s.branch_id, b.name as branch_name')
                 ->from('subject s')
                 ->join('branch b', 'b.id = s.branch_id', 'left')
                 ->order_by('b.name', 'ASC')
                 ->order_by('s.name', 'ASC');
        
        if ($branch_id !== null && $branch_id != 'all') {
            $this->db->where('s.branch_id', $branch_id);
        }
    } elseif ($user_role == 2) {
        // Admin: Get ALL subjects from their branch
        $this->db->select('s.id, s.name, s.branch_id, b.name as branch_name')
                 ->from('subject s')
                 ->join('branch b', 'b.id = s.branch_id', 'left')
                 ->where('s.branch_id', $branch_id)
                 ->order_by('s.name', 'ASC');
    } else {
        // Teacher: Get subjects assigned to this teacher
        $this->db->select('s.id, s.name, s.branch_id, b.name as branch_name')
                 ->from('subject_assign sa')
                 ->join('subject s', 's.id = sa.subject_id')
                 ->join('branch b', 'b.id = sa.branch_id', 'left')
                 ->where('sa.teacher_id', $user_id)
                 ->group_by('s.id')
                 ->order_by('s.name', 'ASC');
        
        if ($branch_id !== null && $branch_id != 'all') {
            $this->db->where('sa.branch_id', $branch_id);
        }
    }
    
    $query = $this->db->get();
    
    // Debug log
    log_message('debug', 'getTeacherSubjects - Role: ' . $user_role . ', Rows: ' . $query->num_rows());
    
    return $query->result_array();
}
    
  /**
 * Get homework completion statistics for user
 */
public function getTeacherHomeworkStats($user_id, $branch_id = null, $subject_id = null, $is_superadmin = false)
{
    $user_role = loggedin_role_id();
    
    // Get subjects first
    $subjects = $this->getTeacherSubjects($user_id, $branch_id, $is_superadmin);
    
    if ($subject_id) {
        $subjects = array_filter($subjects, function($s) use ($subject_id) {
            return $s['id'] == $subject_id;
        });
    }
    
    $stats = [];
    
    foreach ($subjects as $subject) {
        // Get all homework for this subject
        $this->db->select('id, date_of_submission, class_id, section_id, branch_id')
                 ->from('homework')
                 ->where('subject_id', $subject['id'])
                 ->where('session_id', get_session_id());
        
        // Filter by branch
        if ($branch_id !== null && $branch_id != 'all') {
            $this->db->where('branch_id', $branch_id);
        } elseif (!$is_superadmin && $branch_id === null) {
            $this->db->where('branch_id', $subject['branch_id']);
        }
        
        $homeworks = $this->db->get()->result_array();
        
        $total_homeworks = count($homeworks);
        $total_assignments = 0;
        $total_submissions = 0;
        $total_graded = 0;
        
        foreach ($homeworks as $hw) {
            // Get student count for this homework's class/section
            $student_count = $this->db->select('COUNT(*) as count')
                                     ->from('enroll')
                                     ->where('class_id', $hw['class_id'])
                                     ->where('section_id', $hw['section_id'])
                                     ->where('session_id', get_session_id())
                                     ->where('branch_id', $hw['branch_id'])
                                     ->get()
                                     ->row()
                                     ->count ?? 0;
            
            $total_assignments += $student_count;
            
            // Count submissions for this homework
            $submissions = $this->db->select('COUNT(*) as count')
                                   ->from('homework_submit')
                                   ->where('homework_id', $hw['id'])
                                   ->get()
                                   ->row()
                                   ->count ?? 0;
            $total_submissions += $submissions;
            
            // Count graded (has evaluation entry)
            $graded = $this->db->select('COUNT(*) as count')
                               ->from('homework_evaluation')
                               ->where('homework_id', $hw['id'])
                               ->get()
                               ->row()
                               ->count ?? 0;
            $total_graded += $graded;
        }
        
        // Calculate percentages
        $submission_rate = $total_assignments > 0 ? round(($total_submissions / $total_assignments) * 100, 1) : 0;
        $grading_rate = $total_submissions > 0 ? round(($total_graded / $total_submissions) * 100, 1) : 0;
        
        $stats[] = [
            'subject_id' => $subject['id'],
            'subject_name' => $subject['name'],
            'branch_id' => $subject['branch_id'],
            'branch_name' => $subject['branch_name'] ?? 'Unknown',
            'homework_count' => $total_homeworks,
            'total_assignments' => $total_assignments,
            'total_submissions' => $total_submissions,
            'total_graded' => $total_graded,
            'submission_rate' => $submission_rate,
            'grading_rate' => $grading_rate
        ];
    }
    
    return $stats;
}
    
    /**
 * Get chart data for homework analytics
 * @param int|null $branch_id - If null, get from all branches
 * @param int $subject_id
 * @param string $period
 * @return array
 */
public function getChartData($branch_id, $subject_id, $period = 'month')
{
    // Get date range based on period
    if ($period == 'month') {
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-t');
    } elseif ($period == 'term') {
        $current_term = $this->db->where('is_active', 1);
        if ($branch_id !== null) {
            $current_term->where('branch_id', $branch_id);
        }
        $current_term = $current_term->get('exam_term')->row();
        if ($current_term) {
            $date_from = $current_term->term_start_date;
            $date_to = $current_term->term_end_date;
        } else {
            $date_from = date('Y-m-01', strtotime('-3 months'));
            $date_to = date('Y-m-d');
        }
    } else {
        $date_from = date('Y-01-01');
        $date_to = date('Y-12-31');
    }
    
    // Get homework for the subject
    $this->db->select('id, date_of_submission, date_of_homework, branch_id')
             ->from('homework')
             ->where('subject_id', $subject_id)
             ->where('date_of_homework >=', $date_from)
             ->where('date_of_homework <=', $date_to)
             ->order_by('date_of_homework', 'ASC');
    
    // Only filter by branch if specific branch is provided
    if ($branch_id !== null) {
        $this->db->where('branch_id', $branch_id);
    }
    
    $homeworks = $this->db->get()->result_array();
    
    $labels = [];
    $submission_data = [];
    $grading_data = [];
    
    foreach ($homeworks as $hw) {
        // Get class and section
        $class_section = $this->db->select('class_id, section_id')
                                  ->where('id', $hw['id'])
                                  ->get('homework')
                                  ->row();
        
        if ($class_section) {
            $student_count = $this->db->select('COUNT(*) as count')
                                     ->from('enroll')
                                     ->where('class_id', $class_section->class_id)
                                     ->where('section_id', $class_section->section_id)
                                     ->where('session_id', get_session_id())
                                     ->where('branch_id', $hw['branch_id'])
                                     ->get()
                                     ->row()
                                     ->count ?? 0;
            
            $submissions = $this->db->select('COUNT(*) as count')
                                   ->from('homework_submit')
                                   ->where('homework_id', $hw['id'])
                                   ->get()
                                   ->row()
                                   ->count ?? 0;
            
            $graded = $this->db->select('COUNT(*) as count')
                               ->from('homework_evaluation')
                               ->where('homework_id', $hw['id'])
                               ->get()
                               ->row()
                               ->count ?? 0;
            
            $submission_rate = $student_count > 0 ? round(($submissions / $student_count) * 100, 1) : 0;
            $grading_rate = $submissions > 0 ? round(($graded / $submissions) * 100, 1) : 0;
            
            $labels[] = date('d/m', strtotime($hw['date_of_homework']));
            $submission_data[] = $submission_rate;
            $grading_data[] = $grading_rate;
        }
    }
    
    return [
        'labels' => $labels,
        'submission' => $submission_data,
        'grading' => $grading_data
    ];
}
/**
 * Get subjects by branch for AJAX (No DB in controller)
 * @param int|null $branch_id
 * @param int $user_id
 * @param int $user_role
 * @param bool $is_superadmin
 * @return array
 */
public function getSubjectsByBranch($branch_id, $user_id, $user_role, $is_superadmin)
{
    if ($is_superadmin) {
        // Superadmin: Get subjects from selected branch or all
        $this->db->select('s.id, s.name, s.branch_id, b.name as branch_name')
                 ->from('subject s')
                 ->join('branch b', 'b.id = s.branch_id', 'left')
                 ->order_by('s.name', 'ASC');
        
        if ($branch_id !== 'all' && !empty($branch_id)) {
            $this->db->where('s.branch_id', $branch_id);
        }
    } elseif ($user_role == 2) {
        // Admin: Get subjects from their branch only
        $admin_branch = get_loggedin_branch_id();
        $this->db->select('s.id, s.name, s.branch_id, b.name as branch_name')
                 ->from('subject s')
                 ->join('branch b', 'b.id = s.branch_id', 'left')
                 ->where('s.branch_id', $admin_branch)
                 ->order_by('s.name', 'ASC');
    } else {
        // Teacher: Get assigned subjects
        $this->db->select('s.id, s.name, s.branch_id, b.name as branch_name')
                 ->from('subject_assign sa')
                 ->join('subject s', 's.id = sa.subject_id')
                 ->join('branch b', 'b.id = sa.branch_id', 'left')
                 ->where('sa.teacher_id', $user_id)
                 ->group_by('s.id')
                 ->order_by('s.name', 'ASC');
    }
    
    return $this->db->get()->result_array();
}

/**
 * Get all branches for superadmin
 * @return array
 */
public function getAllBranches()
{
    return $this->db->select('id, name')->order_by('name')->get('branch')->result_array();
}
    // ========== COMPLETION REPORTS METHODS ==========
    
    /**
     * Get homework completion report by class/section with proper RBAC
     */
    public function getCompletionReport($branch_id, $class_id = null, $section_id = null, $date_from = null, $date_to = null)
    {
        $is_superadmin = is_superadmin_loggedin();
        $user_role = loggedin_role_id();
        $teacher_id = get_loggedin_user_id();
        
        $this->db->select('h.id, h.subject_id, sub.name as subject_name, 
                          h.date_of_homework, h.date_of_submission, h.class_id, h.section_id,
                          c.name as class_name, sec.name as section_name,
                          COUNT(DISTINCT e.student_id) as total_students,
                          COUNT(DISTINCT hs.id) as submissions_count,
                          COUNT(DISTINCT he.id) as graded_count,
                          COUNT(DISTINCT CASE WHEN hs.submission_status = "late" THEN hs.id END) as late_count')
                 ->from('homework h')
                 ->join('subject sub', 'sub.id = h.subject_id')
                 ->join('class c', 'c.id = h.class_id')
                 ->join('section sec', 'sec.id = h.section_id')
                 ->join('enroll e', 'e.class_id = h.class_id AND e.section_id = h.section_id AND e.session_id = h.session_id')
                 ->join('homework_submit hs', 'hs.homework_id = h.id AND hs.student_id = e.student_id', 'left')
                 ->join('homework_evaluation he', 'he.homework_id = h.id AND he.student_id = e.student_id', 'left')
                 ->where('h.session_id', get_session_id())
                 ->group_by('h.id');
        
        // Branch filtering with RBAC
        if ($is_superadmin) {
            // Superadmin: can see all branches, filter only if branch_id provided
            if ($branch_id) {
                $this->db->where('h.branch_id', $branch_id);
            }
        } else {
            // Non-superadmin: only their branch
            $this->db->where('h.branch_id', $branch_id);
        }
        
        // Teacher restriction: only subjects they teach
        if ($user_role == 3) { // Teacher role
            $subjects = $this->db->select('subject_id')
                                ->where('teacher_id', $teacher_id)
                                ->where('branch_id', $branch_id)
                                ->get('subject_assign')
                                ->result_array();
            $subject_ids = array_column($subjects, 'subject_id');
            if (!empty($subject_ids)) {
                $this->db->where_in('h.subject_id', $subject_ids);
            } else {
                return []; // No subjects assigned
            }
        }
        
        if ($class_id) {
            $this->db->where('h.class_id', $class_id);
        }
        if ($section_id) {
            $this->db->where('h.section_id', $section_id);
        }
        if ($date_from) {
            $this->db->where('h.date_of_homework >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('h.date_of_homework <=', $date_to);
        }
        
        $this->db->order_by('h.date_of_homework', 'DESC');
        
        $results = $this->db->get()->result_array();
        
        // Calculate percentages
        foreach ($results as &$row) {
            $row['submission_rate'] = $row['total_students'] > 0 
                ? round(($row['submissions_count'] / $row['total_students']) * 100, 1) 
                : 0;
            $row['grading_rate'] = $row['submissions_count'] > 0 
                ? round(($row['graded_count'] / $row['submissions_count']) * 100, 1) 
                : 0;
        }
        
        return $results;
    }
    
    /**
     * Get student-wise homework completion report with proper RBAC
     */
    public function getStudentCompletionReport($branch_id, $class_id = null, $section_id = null)
    {
        $is_superadmin = is_superadmin_loggedin();
        $user_role = loggedin_role_id();
        $teacher_id = get_loggedin_user_id();
        
        $this->db->select('s.id as student_id, CONCAT(s.first_name, " ", s.last_name) as student_name,
                          s.register_no, c.name as class_name, sec.name as section_name,
                          COUNT(DISTINCT h.id) as total_homework,
                          COUNT(DISTINCT hs.id) as submitted_count,
                          COUNT(DISTINCT CASE WHEN hs.submission_status = "late" THEN hs.id END) as late_count,
                          COUNT(DISTINCT he.id) as graded_count,
                          AVG(he.rank) as avg_grade')
                 ->from('student s')
                 ->join('enroll e', 'e.student_id = s.id AND e.session_id = ' . get_session_id())
                 ->join('class c', 'c.id = e.class_id')
                 ->join('section sec', 'sec.id = e.section_id')
                 ->join('homework h', 'h.class_id = e.class_id AND h.section_id = e.section_id AND h.branch_id = e.branch_id AND h.session_id = e.session_id')
                 ->join('homework_submit hs', 'hs.homework_id = h.id AND hs.student_id = s.id', 'left')
                 ->join('homework_evaluation he', 'he.homework_id = h.id AND he.student_id = s.id', 'left')
                 ->group_by('s.id');
        
        // Branch filtering with RBAC
        if ($is_superadmin) {
            // Superadmin: can see all branches, filter only if branch_id provided
            if ($branch_id) {
                $this->db->where('s.branch_id', $branch_id);
            }
        } else {
            // Non-superadmin: only their branch
            $this->db->where('s.branch_id', $branch_id);
        }
        
        // Teacher restriction: only students in classes they teach
        if ($user_role == 3) { // Teacher role
            $subjects = $this->db->select('subject_id')
                                ->where('teacher_id', $teacher_id)
                                ->where('branch_id', $branch_id)
                                ->get('subject_assign')
                                ->result_array();
            $subject_ids = array_column($subjects, 'subject_id');
            
            if (!empty($subject_ids)) {
                // Get classes the teacher teaches based on subjects assigned
                $this->db->select('DISTINCT h.class_id')
                         ->from('homework h')
                         ->where_in('h.subject_id', $subject_ids)
                         ->where('h.branch_id', $branch_id);
                $classes = $this->db->get()->result_array();
                $class_ids = array_column($classes, 'class_id');
                
                if (!empty($class_ids)) {
                    $this->db->where_in('e.class_id', $class_ids);
                } else {
                    return []; // No classes assigned
                }
            } else {
                return []; // No subjects assigned
            }
        }
        
        if ($class_id) {
            $this->db->where('e.class_id', $class_id);
        }
        if ($section_id) {
            $this->db->where('e.section_id', $section_id);
        }
        
        $this->db->order_by('c.name', 'ASC')
                 ->order_by('sec.name', 'ASC')
                 ->order_by('s.first_name', 'ASC');
        
        $results = $this->db->get()->result_array();
        
        // Calculate percentages
        foreach ($results as &$row) {
            $row['completion_rate'] = $row['total_homework'] > 0 
                ? round(($row['submitted_count'] / $row['total_homework']) * 100, 1) 
                : 0;
        }
        
        return $results;
    }
    
    /**
     * Get classes for dropdown with RBAC
     */
    public function getClasses($branch_id)
    {
        $is_superadmin = is_superadmin_loggedin();
        $user_role = loggedin_role_id();
        $teacher_id = get_loggedin_user_id();
        
        $this->db->select('id, name')
                 ->from('class')
                 ->order_by('name', 'ASC');
        
        // Branch filtering with RBAC
        if ($is_superadmin) {
            if ($branch_id) {
                $this->db->where('branch_id', $branch_id);
            }
        } else {
            $this->db->where('branch_id', $branch_id);
        }
        
        // Teacher restriction: only classes they teach
        if ($user_role == 3) { // Teacher role
            $subjects = $this->db->select('subject_id')
                                ->where('teacher_id', $teacher_id)
                                ->where('branch_id', $branch_id)
                                ->get('subject_assign')
                                ->result_array();
            $subject_ids = array_column($subjects, 'subject_id');
            
            if (!empty($subject_ids)) {
                $this->db->select('DISTINCT h.class_id')
                         ->from('homework h')
                         ->where_in('h.subject_id', $subject_ids);
                $classes = $this->db->get()->result_array();
                $class_ids = array_column($classes, 'class_id');
                
                if (!empty($class_ids)) {
                    $this->db->where_in('id', $class_ids);
                } else {
                    return [];
                }
            } else {
                return [];
            }
        }
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get sections for dropdown with RBAC
     */
    public function getSections($class_id, $branch_id)
    {
        $is_superadmin = is_superadmin_loggedin();
        $user_role = loggedin_role_id();
        $teacher_id = get_loggedin_user_id();
        
        $this->db->select('sec.id, sec.name')
                 ->from('sections_allocation sa')
                 ->join('section sec', 'sec.id = sa.section_id')
                 ->where('sa.class_id', $class_id)
                 ->order_by('sec.name', 'ASC');
        
        // Branch filtering with RBAC
        if ($is_superadmin) {
            if ($branch_id) {
                $this->db->where('sec.branch_id', $branch_id);
            }
        } else {
            $this->db->where('sec.branch_id', $branch_id);
        }
        
        // Teacher restriction: only sections they teach
        if ($user_role == 3) { // Teacher role
            $subjects = $this->db->select('subject_id')
                                ->where('teacher_id', $teacher_id)
                                ->where('branch_id', $branch_id)
                                ->get('subject_assign')
                                ->result_array();
            $subject_ids = array_column($subjects, 'subject_id');
            
            if (!empty($subject_ids)) {
                $this->db->select('DISTINCT h.section_id')
                         ->from('homework h')
                         ->where_in('h.subject_id', $subject_ids)
                         ->where('h.class_id', $class_id);
                $sections = $this->db->get()->result_array();
                $section_ids = array_column($sections, 'section_id');
                
                if (!empty($section_ids)) {
                    $this->db->where_in('sec.id', $section_ids);
                } else {
                    return [];
                }
            } else {
                return [];
            }
        }
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get report summary statistics with proper RBAC
     */
    public function getReportSummary($branch_id, $class_id = null, $section_id = null, $date_from = null, $date_to = null)
    {
        $data = $this->getCompletionReport($branch_id, $class_id, $section_id, $date_from, $date_to);
        
        $total_homework = count($data);
        $total_submissions = array_sum(array_column($data, 'submissions_count'));
        $total_graded = array_sum(array_column($data, 'graded_count'));
        $total_students = array_sum(array_column($data, 'total_students'));
        
        $overall_submission_rate = $total_students > 0 ? round(($total_submissions / $total_students) * 100, 1) : 0;
        $overall_grading_rate = $total_submissions > 0 ? round(($total_graded / $total_submissions) * 100, 1) : 0;
        
        return [
            'total_homework' => $total_homework,
            'total_submissions' => $total_submissions,
            'total_graded' => $total_graded,
            'total_students' => $total_students,
            'overall_submission_rate' => $overall_submission_rate,
            'overall_grading_rate' => $overall_grading_rate
        ];
    }
        // ========== DIFFERENTIATED LEARNING METHODS ==========
    
    /**
     * Save homework targets (for differentiated learning)
     * @param int $homework_id
     * @param array $target_data
     * @param int $branch_id
     * @return bool
     */
    public function saveHomeworkTargets($homework_id, $target_data, $branch_id)
    {
        // Delete existing targets for this homework
        $this->db->where('homework_id', $homework_id);
        $this->db->delete('homework_targets');
        
        if (empty($target_data)) {
            return true;
        }
        
        $insert_data = [];
        foreach ($target_data as $target) {
            $insert_data[] = [
                'homework_id' => $homework_id,
                'student_id' => $target['student_id'] ?? null,
                'target_group' => $target['target_group'],
                'branch_id' => $branch_id,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        if (!empty($insert_data)) {
            return $this->db->insert_batch('homework_targets', $insert_data);
        }
        
        return true;
    }
    
    /**
     * Get homework targets for a specific homework
     * @param int $homework_id
     * @return array
     */
    public function getHomeworkTargets($homework_id)
    {
        return $this->db->select('ht.*, CONCAT(s.first_name, " ", s.last_name) as student_name, s.register_no')
                        ->from('homework_targets ht')
                        ->join('student s', 's.id = ht.student_id', 'left')
                        ->where('ht.homework_id', $homework_id)
                        ->get()
                        ->result_array();
    }
    
    /**
     * Get students eligible for a homework (based on targeting)
     * @param int $homework_id
     * @param int $class_id
     * @param int $section_id
     * @param int $branch_id
     * @return array
     */
    public function getTargetedStudents($homework_id, $class_id, $section_id, $branch_id)
    {
        // Get homework targets
        $targets = $this->getHomeworkTargets($homework_id);
        
        // Get all students in class/section
        $all_students = $this->application_model->getStudentListByClassSection($class_id, $section_id, $branch_id);
        
        if (empty($targets)) {
            // No targeting means all students
            return $all_students;
        }
        
        // Check if there's an 'all' target
        $has_all = false;
        $targeted_student_ids = [];
        $targeted_groups = [];
        
        foreach ($targets as $target) {
            if ($target['target_group'] == 'all') {
                $has_all = true;
            } elseif ($target['target_group'] == 'individual' && $target['student_id']) {
                $targeted_student_ids[] = $target['student_id'];
            } else {
                $targeted_groups[] = $target['target_group'];
            }
        }
        
        if ($has_all) {
            return $all_students;
        }
        
        // Filter students by targeted groups or individual IDs
        $filtered_students = [];
        foreach ($all_students as $student) {
            // Check if student is individually targeted
            if (in_array($student['student_id'], $targeted_student_ids)) {
                $filtered_students[] = $student;
                continue;
            }
            
            // Check if student belongs to targeted group (based on group column in student table)
            // Note: You may need to add a 'student_group' column to student table
            // For now, we'll use a simple approach - you can customize based on your needs
            if (!empty($targeted_groups)) {
                // You can implement group logic here (e.g., based on student performance, etc.)
                // For demonstration, we'll include all students if groups are specified
                $filtered_students[] = $student;
            }
        }
        
        return !empty($filtered_students) ? $filtered_students : $all_students;
    }
    
    /**
     * Get student groups for dropdown (remedial, standard, advanced)
     * @return array
     */
    public function getStudentGroups()
    {
        return [
            'remedial' => translate('remedial'),
            'standard' => translate('standard'),
            'advanced' => translate('advanced'),
            'individual' => translate('individual_students'),
            'all' => translate('all_students')
        ];
    }
    
    /**
     * Get students by performance level for targeting
     * @param int $class_id
     * @param int $section_id
     * @param int $subject_id
     * @param int $branch_id
     * @param string $group
     * @return array
     */
    public function getStudentsByPerformanceLevel($class_id, $section_id, $subject_id, $branch_id, $group)
    {
        $all_students = $this->application_model->getStudentListByClassSection($class_id, $section_id, $branch_id);
        
        if ($group == 'all') {
            return $all_students;
        }
        
        // Get average marks for students in this subject from last exam
        $this->db->select('m.student_id, AVG(m.mark) as avg_mark')
                 ->from('mark m')
                 ->join('exam e', 'e.id = m.exam_id')
                 ->where('m.class_id', $class_id)
                 ->where('m.section_id', $section_id)
                 ->where('m.subject_id', $subject_id)
                 ->where('m.branch_id', $branch_id)
                 ->where('e.session_id', get_session_id())
                 ->group_by('m.student_id');
        
        $marks = $this->db->get()->result_array();
        $student_marks = [];
        foreach ($marks as $mark) {
            $student_marks[$mark['student_id']] = $mark['avg_mark'];
        }
        
        $filtered_students = [];
        foreach ($all_students as $student) {
            $avg_mark = $student_marks[$student['student_id']] ?? 0;
            
            if ($group == 'remedial' && $avg_mark < 40) {
                $filtered_students[] = $student;
            } elseif ($group == 'standard' && $avg_mark >= 40 && $avg_mark < 70) {
                $filtered_students[] = $student;
            } elseif ($group == 'advanced' && $avg_mark >= 70) {
                $filtered_students[] = $student;
            }
        }
        
        return $filtered_students;
    }
        // ========== HOMEWORK COMMENTS METHODS ==========
    
    /**
     * Get all comments for a homework
     * @param int $homework_id
     * @param int $branch_id
     * @return array
     */
    public function getHomeworkComments($homework_id, $branch_id)
    {
        // Get parent comments (not replies)
        $this->db->select('hc.*, 
                          CASE 
                              WHEN hc.created_by_type = "student" THEN CONCAT(s.first_name, " ", s.last_name)
                              WHEN hc.created_by_type = "teacher" THEN st.name
                              WHEN hc.created_by_type = "parent" THEN p.name
                              WHEN hc.created_by_type = "admin" THEN a.name
                              ELSE "System"
                          END as author_name,
                          CASE 
                              WHEN hc.created_by_type = "student" THEN s.photo
                              WHEN hc.created_by_type = "teacher" THEN st.photo
                              WHEN hc.created_by_type = "parent" THEN p.photo
                              ELSE NULL
                          END as author_avatar')
                 ->from('homework_comments hc')
                 ->join('student s', 's.id = hc.created_by AND hc.created_by_type = "student"', 'left')
                 ->join('staff st', 'st.id = hc.created_by AND hc.created_by_type = "teacher"', 'left')
                 ->join('parent p', 'p.id = hc.created_by AND hc.created_by_type = "parent"', 'left')
                 ->join('staff a', 'a.id = hc.created_by AND hc.created_by_type = "admin"', 'left')
                 ->where('hc.homework_id', $homework_id)
                 ->where('hc.branch_id', $branch_id)
                 ->where('hc.parent_id IS NULL')
                 ->order_by('hc.created_at', 'ASC');
        
        $comments = $this->db->get()->result_array();
        
        // Get replies for each comment
        foreach ($comments as &$comment) {
            $comment['replies'] = $this->getCommentReplies($comment['id'], $branch_id);
        }
        
        return $comments;
    }
    
    /**
     * Get replies for a specific comment
     * @param int $comment_id
     * @param int $branch_id
     * @return array
     */
    public function getCommentReplies($comment_id, $branch_id)
    {
        $this->db->select('hc.*,
                          CASE 
                              WHEN hc.created_by_type = "student" THEN CONCAT(s.first_name, " ", s.last_name)
                              WHEN hc.created_by_type = "teacher" THEN st.name
                              WHEN hc.created_by_type = "parent" THEN p.name
                              WHEN hc.created_by_type = "admin" THEN a.name
                              ELSE "System"
                          END as author_name,
                          CASE 
                              WHEN hc.created_by_type = "student" THEN s.photo
                              WHEN hc.created_by_type = "teacher" THEN st.photo
                              WHEN hc.created_by_type = "parent" THEN p.photo
                              ELSE NULL
                          END as author_avatar')
                 ->from('homework_comments hc')
                 ->join('student s', 's.id = hc.created_by AND hc.created_by_type = "student"', 'left')
                 ->join('staff st', 'st.id = hc.created_by AND hc.created_by_type = "teacher"', 'left')
                 ->join('parent p', 'p.id = hc.created_by AND hc.created_by_type = "parent"', 'left')
                 ->join('staff a', 'a.id = hc.created_by AND hc.created_by_type = "admin"', 'left')
                 ->where('hc.parent_id', $comment_id)
                 ->where('hc.branch_id', $branch_id)
                 ->order_by('hc.created_at', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Add a comment to homework
     * @param array $data
     * @return int|bool
     */
    public function addHomeworkComment($data)
    {
        $this->db->insert('homework_comments', $data);
        return $this->db->insert_id();
    }
    
    /**
     * Delete a comment (only if user is author or admin/teacher)
     * @param int $comment_id
     * @param int $user_id
     * @param string $user_type
     * @param int $branch_id
     * @return bool
     */
    public function deleteHomeworkComment($comment_id, $user_id, $user_type, $branch_id)
    {
        // Check if user is authorized
        $comment = $this->db->get_where('homework_comments', ['id' => $comment_id])->row();
        
        if (!$comment) {
            return false;
        }
        
        // Author can delete own comment, teachers/admins can delete any
        $is_author = ($comment->created_by == $user_id && $comment->created_by_type == $user_type);
        $is_teacher_or_admin = in_array($user_type, ['teacher', 'admin']);
        
        if ($is_author || $is_teacher_or_admin) {
            // Also delete replies
            $this->db->where('parent_id', $comment_id);
            $this->db->delete('homework_comments');
            
            $this->db->where('id', $comment_id);
            $this->db->where('branch_id', $branch_id);
            return $this->db->delete('homework_comments');
        }
        
        return false;
    }
    
    /**
     * Get comment count for a homework
     * @param int $homework_id
     * @return int
     */
    public function getCommentCount($homework_id)
    {
        return $this->db->where('homework_id', $homework_id)
                        ->count_all_results('homework_comments');
    }
        // ========== HOMEWORK DISCUSSION MODEL METHODS ==========
    
    /**
     * Get homework discussion list with filters
     * @param int $branch_id
     * @param int|null $class_id
     * @param int|null $section_id
     * @param int|null $subject_id
     * @param bool $is_superadmin
     * @param int|null $filter_branch_id
     * @return array
     */
    public function getDiscussionHomeworkList($branch_id, $class_id = null, $section_id = null, $subject_id = null, $is_superadmin = false, $filter_branch_id = null)
    {
        $this->db->select('h.id, h.subject_id, h.class_id, h.section_id, h.date_of_submission, h.branch_id,
                          sub.name as subject_name, c.name as class_name, sec.name as section_name,
                          (SELECT COUNT(*) FROM homework_comments WHERE homework_id = h.id) as comment_count')
                 ->from('homework h')
                 ->join('subject sub', 'sub.id = h.subject_id', 'left')
                 ->join('class c', 'c.id = h.class_id', 'left')
                 ->join('section sec', 'sec.id = h.section_id', 'left')
                 ->where('h.session_id', get_session_id());
        
        // Apply branch filtering
        if ($is_superadmin) {
            if (!empty($filter_branch_id)) {
                $this->db->where('h.branch_id', $filter_branch_id);
            }
        } else {
            if (!empty($branch_id)) {
                $this->db->where('h.branch_id', $branch_id);
            }
        }
        
        if (!empty($class_id)) {
            $this->db->where('h.class_id', $class_id);
        }
        if (!empty($section_id)) {
            $this->db->where('h.section_id', $section_id);
        }
        if (!empty($subject_id)) {
            $this->db->where('h.subject_id', $subject_id);
        }
        
        $this->db->order_by('h.id', 'DESC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get classes for dropdown (with branch filtering)
     * @param int $branch_id
     * @param bool $is_superadmin
     * @return array
     */
    public function getDiscussionClasses($branch_id, $is_superadmin = false)
    {
        $this->db->select('id, name');
        
        if (!$is_superadmin && !empty($branch_id)) {
            $this->db->where('branch_id', $branch_id);
        }
        
        return $this->db->get('class')->result_array();
    }
    
    /**
     * Get homework by ID for discussion (with branch isolation)
     * @param int $homework_id
     * @param int $branch_id
     * @param bool $is_superadmin
     * @return object|null
     */
    public function getHomeworkForDiscussion($homework_id, $branch_id, $is_superadmin = false)
    {
        $this->db->select('h.*, sub.name as subject_name')
                 ->from('homework h')
                 ->join('subject sub', 'sub.id = h.subject_id', 'left')
                 ->where('h.id', $homework_id);
        
        if (!$is_superadmin) {
            $this->db->where('h.branch_id', $branch_id);
        }
        
        return $this->db->get()->row();
    }
    
    /**
     * Get all comments for a homework with replies (branch isolated)
     * @param int $homework_id
     * @param int $branch_id
     * @param bool $is_superadmin
     * @return array
     */
    public function getHomeworkCommentsWithReplies($homework_id, $branch_id, $is_superadmin = false)
    {
        // Get parent comments
        $this->db->select('hc.*, 
                          CASE 
                              WHEN hc.created_by_type = "student" THEN CONCAT(s.first_name, " ", s.last_name)
                              WHEN hc.created_by_type = "teacher" THEN st.name
                              WHEN hc.created_by_type = "parent" THEN p.name
                              WHEN hc.created_by_type = "admin" THEN a.name
                              ELSE "System"
                          END as author_name')
                 ->from('homework_comments hc')
                 ->join('student s', 's.id = hc.created_by AND hc.created_by_type = "student"', 'left')
                 ->join('staff st', 'st.id = hc.created_by AND hc.created_by_type = "teacher"', 'left')
                 ->join('parent p', 'p.id = hc.created_by AND hc.created_by_type = "parent"', 'left')
                 ->join('staff a', 'a.id = hc.created_by AND hc.created_by_type = "admin"', 'left')
                 ->where('hc.homework_id', $homework_id)
                 ->where('hc.parent_id IS NULL')
                 ->order_by('hc.created_at', 'ASC');
        
        if (!$is_superadmin) {
            $this->db->where('hc.branch_id', $branch_id);
        }
        
        $comments = $this->db->get()->result_array();
        
        // Get replies for each comment
        foreach ($comments as &$comment) {
            $this->db->select('hc.*, 
                              CASE 
                                  WHEN hc.created_by_type = "student" THEN CONCAT(s.first_name, " ", s.last_name)
                                  WHEN hc.created_by_type = "teacher" THEN st.name
                                  WHEN hc.created_by_type = "parent" THEN p.name
                                  WHEN hc.created_by_type = "admin" THEN a.name
                                  ELSE "System"
                              END as author_name')
                       ->from('homework_comments hc')
                       ->join('student s', 's.id = hc.created_by AND hc.created_by_type = "student"', 'left')
                       ->join('staff st', 'st.id = hc.created_by AND hc.created_by_type = "teacher"', 'left')
                       ->join('parent p', 'p.id = hc.created_by AND hc.created_by_type = "parent"', 'left')
                       ->join('staff a', 'a.id = hc.created_by AND hc.created_by_type = "admin"', 'left')
                       ->where('hc.parent_id', $comment['id'])
                       ->order_by('hc.created_at', 'ASC');
            
            if (!$is_superadmin) {
                $this->db->where('hc.branch_id', $branch_id);
            }
            
            $comment['replies'] = $this->db->get()->result_array();
        }
        
        return $comments;
    }
    
    /**
     * Get total comment count for a homework
     * @param int $homework_id
     * @param int $branch_id
     * @param bool $is_superadmin
     * @return int
     */
    public function getTotalCommentCount($homework_id, $branch_id, $is_superadmin = false)
    {
        $this->db->where('homework_id', $homework_id);
        
        if (!$is_superadmin) {
            $this->db->where('branch_id', $branch_id);
        }
        
        return $this->db->count_all_results('homework_comments');
    }
        /**
     * Get student name by ID
     */
    public function getStudentName($student_id)
    {
        $student = $this->db->select('first_name, last_name')
                            ->where('id', $student_id)
                            ->get('student')
                            ->row();
        return $student ? $student->first_name . ' ' . $student->last_name : 'Student';
    }
    
    /**
     * Get teacher name by ID
     */
    public function getTeacherName($teacher_id)
    {
        $teacher = $this->db->select('name')
                            ->where('id', $teacher_id)
                            ->get('staff')
                            ->row();
        return $teacher ? $teacher->name : 'Teacher';
    }
    
    /**
     * Get parent name by ID
     */
    public function getParentName($parent_id)
    {
        $parent = $this->db->select('name')
                           ->where('id', $parent_id)
                           ->get('parent')
                           ->row();
        return $parent ? $parent->name : 'Parent';
    }
        /**
     * Get enrolled students for a specific subject (based on student_subject table)
     * Falls back to all students in class/section if no enrollments exist
     * @param int $subject_id
     * @param int $class_id
     * @param int $section_id
     * @param int $branch_id
     * @return array
     */
    public function get_enrolled_students_for_subject($subject_id, $class_id, $section_id, $branch_id)
    {
        // Get students enrolled in this subject from student_subject table
        $this->db->select('s.id as student_id, s.first_name, s.last_name, s.register_no, s.mobileno, s.email, s.parent_id,
                          CONCAT(s.first_name, " ", s.last_name) as fullname')
                 ->from('student_subject ss')
                 ->join('student s', 's.id = ss.student_id')
                 ->where('ss.subject_id', $subject_id)
                 ->where('ss.class_id', $class_id)
                 ->where('ss.section_id', $section_id)
                 ->where('ss.session_id', get_session_id())
                 ->where('ss.branch_id', $branch_id)
                 ->where('ss.status', 'active')
                 ->order_by('s.first_name', 'ASC');
        
        $enrolled_students = $this->db->get()->result_array();
        
        // If no students are enrolled in student_subject, fall back to all students in class/section
        if (empty($enrolled_students)) {
            log_message('debug', "No subject enrollments found for subject {$subject_id}, falling back to all students");
            $enrolled_students = $this->application_model->getStudentListByClassSection($class_id, $section_id, $branch_id);
        }
        
        return $enrolled_students;
    }
    
    /**
     * Get student IDs enrolled in a specific subject
     * @param int $subject_id
     * @param int $class_id
     * @param int $section_id
     * @param int $branch_id
     * @return array
     */
    public function get_enrolled_student_ids_for_subject($subject_id, $class_id, $section_id, $branch_id)
    {
        $students = $this->get_enrolled_students_for_subject($subject_id, $class_id, $section_id, $branch_id);
        return array_column($students, 'student_id');
    }
    
}
