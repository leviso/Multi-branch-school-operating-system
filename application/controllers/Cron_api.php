<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron_api extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('fees_model');
        $this->load->model('sms_model');
        $this->load->model('sendsmsmail_model');
        $this->load->model('application_model');
        $this->load->model('homework_model');
        $this->api_key = $this->data['global_config']['cron_secret_key'];
        $this->load->model('subscription_model');
        
        
    }
     public function index()
    {
        if (!is_loggedin() || !get_permission('cron_job', 'is_view')) {
            access_denied();
        }

        if ($_POST) {
            if (!get_permission('cron_job', 'is_edit')) {
                access_denied();
            }
            $this->db->where('id', 1);
            $this->db->update('global_settings', array('cron_secret_key' => generate_encryption_key()));
            set_alert('success', "Successfully Created The New Secret Key.");
            redirect(current_url());
        }

        $this->data['title'] = translate('cron_job');
        $this->data['sub_page'] = 'cron_api/index';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    /**
     * MAIN CRON ENTRY POINT - Processes ALL scheduled messages
     */
     public function send_smsemail_command($api_key = '')
        {
            // log_message('info', '=== MAIN CRON STARTED === ' . date('Y-m-d H:i:s'));
            
            if ($api_key != "" && $this->api_key != $api_key) {
                // log_message('error', 'API Key mismatch');
                echo "API Key mismatch.\n";
                exit();
            }

            echo "=== Processing ALL Scheduled Messages ===\n";
            echo "Time: " . date('Y-m-d H:i:s') . "\n";
            
            // Step 1: Generate homework reminders
            echo "\n1. Generating Homework Reminders:\n";
            $homework_count = $this->generate_homework_reminders();
            echo "   Scheduled: {$homework_count} homework reminders\n";
            
            // Step 2: Generate fees reminders
            echo "\n2. Generating Fees Reminders:\n";
            $fees_count = $this->generate_fees_reminders();
            echo "   Scheduled: {$fees_count} fees reminders\n";
            
            // NEW STEP 3: Generate birthday reminders
            echo "\n3. Generating Birthday Reminders:\n";
            $birthday_count = $this->generate_birthday_reminders();
            echo "   Scheduled: {$birthday_count} birthday wishes\n";

             // Step 4: NEW - Send upcoming leave reminders
           echo "\n4. Sending Upcoming Leave Reminders:\n";
            $this->upcoming_leave_reminders($api_key);
            
            // Step 5: NEW - Send return reminders
           echo "\n5. Sending Return Leave Reminders:\n";
           $this->return_reminders($api_key);
            
            // Step 4: Process ALL due messages
            echo "\n4. Processing Due Messages:\n";
            $this->process_all_due_messages();
            
            echo "\n=== CRON COMPLETE ===\n";
            echo "Time: " . date('Y-m-d H:i:s') . "\n";
        }

    /**
     * Generate homework reminders for today
     */
    private function generate_homework_reminders()
    {
        $scheduled_count = 0;
        $today = date('Y-m-d');
        
        // Get homework scheduled for today with SMS enabled
        $sql = "SELECT h.*, s.name as subject_name, 
                       c.name as class_name, sec.name as section_name
                FROM homework h
                LEFT JOIN subject s ON s.id = h.subject_id
                LEFT JOIN class c ON c.id = h.class_id
                LEFT JOIN section sec ON sec.id = h.section_id
                WHERE h.sms_notification = 1  -- SMS enabled
                AND h.status = 1  -- Active
                AND DATE(h.schedule_date) <= ?  -- Due today or before
                AND NOT EXISTS (
                    SELECT 1 FROM bulk_sms_email b 
                    WHERE b.recipient_type = 4 
                    AND JSON_EXTRACT(b.recipients_details, '$.homework_id') = h.id
                    AND b.posting_status IN (1, 0, 2)  -- Already scheduled/processing/sent
                )
                ORDER BY h.branch_id, h.class_id, h.section_id";
        
        $homework_list = $this->db->query($sql, [$today])->result_array();
        
        foreach ($homework_list as $homework) {
            // Check if already sent in last 24 hours
            $already_sent = $this->db->where('homework_id', $homework['id'])
                ->where('sent_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
                ->get('homework_sms_logs')
                ->num_rows();
            
            if ($already_sent > 0) {
                echo "     Skip Homework ID {$homework['id']} - already sent within 24 hours\n";
                continue;
            }
            // ========== END OF CHECK ==========
        
        $message_id = $this->create_homework_sms_entry($homework);
            
            if ($message_id) {
                // Mark as "scheduled for SMS" - NOT sent yet!
                $this->db->where('id', $homework['id']);
                $this->db->update('homework', [
                    'sms_notification' => 2, // 2 = scheduled for SMS
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $scheduled_count++;
                echo "   ✓ Homework ID {$homework['id']}: Scheduled for SMS\n";
            }
        }
        
        return $scheduled_count;
    }

    /**
     * Create homework SMS entry in bulk_sms_email
     */
    private function create_homework_sms_entry($homework)
    {
        // Get students
        $students = $this->application_model->getStudentListByClassSection(
            $homework['class_id'], 
            $homework['section_id'], 
            $homework['branch_id']
        );
        
        if (empty($students)) {
            // log_message('warning', "No students found for homework ID {$homework['id']}");
            return false;
        }
        
        // Get SMS template
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 6,
            'branch_id' => $homework['branch_id']
        ])->row_array();
        
        if (empty($template) || empty($template['template_body'])) {
            // log_message('error', "No SMS template for homework in branch {$homework['branch_id']}");
            return false;
        }
        
        // Prepare recipients
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
                    'roll' => $student['roll'] ?? ''
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
            // log_message('warning', "No recipients for homework ID {$homework['id']}");
            return false;
        }
        
        // Prepare message template (with placeholders)
        $message = $template['template_body'];
        $message = str_replace('{date_of_homework}', $homework['date_of_homework'], $message);
        $message = str_replace('{date_of_submission}', $homework['date_of_submission'], $message);
        $message = str_replace('{subject}', $homework['subject_name'], $message);
        // Keep other placeholders for per-recipient replacement
        
        // Calculate credits
        $credits_needed = $this->calculate_sms_cost_cron($message, count($recipients));
        
        // Check credits
        $current_credits = $this->sendsmsmail_model->get_sms_credit($homework['branch_id']);
        if ($current_credits < $credits_needed) {
            // log_message('error', "Insufficient credits for homework {$homework['id']}. Needed: {$credits_needed}, Available: {$current_credits}");
            
            // Mark as failed due to insufficient credits
            $this->db->where('id', $homework['id']);
            $this->db->update('homework', [
                'sms_notification' => 0, // Disable SMS
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return false;
        }
        
        // Create bulk_sms_email entry
        $campaign_name = "Homework: " . $homework['subject_name'] . " - Class " . 
                        $homework['class_name'] . " (" . date('d/m', strtotime($homework['date_of_submission'])) . ")";
        
        $message_data = [
            'campaign_name' => $campaign_name,
            'message' => $message, // Template with placeholders
            'message_type' => 1, // SMS
            'recipient_type' => 4, // 4 = homework
            'recipients_details' => json_encode([
                'homework_id' => $homework['id'],
                'class_id' => $homework['class_id'],
                'section_id' => $homework['section_id'],
                'subject_id' => $homework['subject_id'],
                'template_id' => 6,
                'notify_student' => $template['notify_student'],
                'notify_parent' => $template['notify_parent']
            ]),
            'additional' => json_encode($recipients), // Full recipient list
            'schedule_time' => date('Y-m-d H:i:s'), // Send now
            'posting_status' => 1, // Scheduled
            'total_thread' => count($recipients),
            'successfully_sent' => 0,
            'sms_gateway' => 'bulksmsbd',
            'credits_used' => 0, // Will update after sending
            'branch_id' => $homework['branch_id'],
            'send_type' => 'auto_homework',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('bulk_sms_email', $message_data);
        $message_id = $this->db->insert_id();
        
        // Reserve credits
        $this->sendsmsmail_model->reserve_sms_credits(
            $homework['branch_id'], 
            $credits_needed, 
            $campaign_name, 
            $message_id
        );
        
        // log_message('info', "Homework {$homework['id']} scheduled for SMS. Message ID: {$message_id}, Credits: {$credits_needed}");
        return $message_id;
    }

    /**
     * Generate fees reminders
     */
    private function generate_fees_reminders()
    {
        $scheduled_count = 0;
        $today = date('Y-m-d');
        
        // Get all active fee reminders
        $reminders = $this->db->get('fees_reminder')->result_array();
        
        foreach ($reminders as $reminder) {
            // Calculate target date based on frequency
            $days = (int)$reminder['days'];
            
            if ($reminder['frequency'] == 'before') {
                $target_date = date('Y-m-d', strtotime("+{$days} days"));
            } elseif ($reminder['frequency'] == 'after') {
                $target_date = date('Y-m-d', strtotime("-{$days} days"));
            } else {
                continue;
            }
            
            // Get fees due on target date
            $fee_types = $this->fees_model->getFeeReminderByDate($target_date, $reminder['branch_id']);
            
            foreach ($fee_types as $fee_type) {
                // Get students who owe this fee
                $students = $this->fees_model->getStudentsListReminder(
                    $fee_type['fee_groups_id'], 
                    $fee_type['fee_type_id']
                );
                
                foreach ($students as $student) {
                    // Calculate balance
                    $balance = (float)($fee_type['amount'] - 
                        ($student['payment']['total_paid'] + $student['payment']['total_discount']));
                    
                        if ($balance > 0) {
                    
                    // Check if reminder already sent for this student + fee type this week
                    $already_sent = $this->db->select('fr.id')
                        ->from('fees_sms_logs fr')
                        ->where('fr.student_id', $student['student_id'])
                        ->where('fr.fee_type_id', $fee_type['fee_type_id'])
                        ->where('fr.sent_at >', date('Y-m-d H:i:s', strtotime('-7 days')))
                        ->get()
                        ->num_rows();
                    
                    if ($already_sent > 0) {
                        echo "     Skip fee reminder for student {$student['student_id']} - already sent this week\n";
                        continue;
                    }
                    // ========== END OF CHECK ==========
                    
                    $scheduled = $this->create_fee_reminder_sms($student, $fee_type, $reminder, $balance);
                        if ($scheduled) {
                            $scheduled_count++;
                        }
                    }
                }
            }
        }
        
        return $scheduled_count;
    }

    /**
     * Create fee reminder SMS entry
     */
    private function create_fee_reminder_sms($student, $fee_type, $reminder, $balance)
    {
        $recipients = [];
        
        // Student recipient
        if ($reminder['student'] == 1 && !empty($student['child_mobileno'])) {
            $recipients[] = [
                'name' => $student['child_name'],
                'mobileno' => $student['child_mobileno'],
                'type' => 'student',
                'student_id' => $student['student_id'] ?? null
            ];
        }
        
        // Guardian recipient
        if ($reminder['guardian'] == 1 && !empty($student['guardian_mobileno'])) {
            $recipients[] = [
                'name' => $student['guardian_name'],
                'mobileno' => $student['guardian_mobileno'],
                'type' => 'parent',
                'parent_id' => $student['parent_id'] ?? null
            ];
        }
        
        if (empty($recipients)) {
            return false;
        }
        
        // Prepare message template
        $message = $reminder['message'];
        $message = str_replace('{due_date}', $fee_type['due_date'], $message);
        $message = str_replace('{due_amount}', number_format($balance, 2), $message);
        $message = str_replace('{fee_type}', $fee_type['name'], $message);
        // Keep name placeholders for per-recipient replacement
        
        // Calculate credits
        $credits_needed = $this->calculate_sms_cost_cron($message, count($recipients));
        
        // Check credits
        $current_credits = $this->sendsmsmail_model->get_sms_credit($reminder['branch_id']);
        if ($current_credits < $credits_needed) {
            // log_message('error', "Insufficient credits for fee reminder. Branch: {$reminder['branch_id']}, Needed: {$credits_needed}");
            return false;
        }
        
        // Create bulk_sms_email entry
        $campaign_name = "Fee Reminder: " . $fee_type['name'] . " - Due " . $fee_type['due_date'];
        
        $message_data = [
            'campaign_name' => $campaign_name,
            'message' => $message,
            'message_type' => 1,
            'recipient_type' => 5, // 5 = fee reminder
            'recipients_details' => json_encode([
                'fee_groups_id' => $fee_type['fee_groups_id'],
                'fee_type_id' => $fee_type['fee_type_id'],
                'due_date' => $fee_type['due_date'],
                'total_amount' => $fee_type['amount'],
                'balance_amount' => $balance,
                'student_id' => $student['student_id'] ?? null
            ]),
            'additional' => json_encode($recipients),
            'schedule_time' => date('Y-m-d H:i:s'),
            'posting_status' => 1,
            'total_thread' => count($recipients),
            'successfully_sent' => 0,
            'sms_gateway' => 'bulksmsbd',
            'credits_used' => 0,
            'branch_id' => $reminder['branch_id'],
            'send_type' => 'auto_fee_reminder',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('bulk_sms_email', $message_data);
        $message_id = $this->db->insert_id();
        
        // Reserve credits
        $this->sendsmsmail_model->reserve_sms_credits(
            $reminder['branch_id'], 
            $credits_needed, 
            $campaign_name, 
            $message_id
        );
        
        echo "   ✓ Fee reminder scheduled for {$student['child_name']}\n";
        return true;
    }

    /**
     * Process ALL due messages in bulk_sms_email
     */
    private function process_all_due_messages()
{
    $current_time = date('Y-m-d H:i:s');
    $ten_minutes_ago = date('Y-m-d H:i:s', strtotime('-10 minutes'));
    
    // ✅ FIX: Initialize counters
    $processed = 0;
    $failed = 0;
    
    // Get due messages that aren't being processed
    $sql = "SELECT * FROM bulk_sms_email 
            WHERE posting_status IN (0, 1)  -- Only processing(0) or scheduled(1)
            AND schedule_time <= ? 
            AND (processing_lock = 0 OR processing_started < ?)
            ORDER BY schedule_time ASC 
            LIMIT 10";
    
    $messages = $this->db->query($sql, [$current_time, $ten_minutes_ago])->result_array();
    
    foreach ($messages as $message) {
        // Immediately mark as processing (status 0)
        $this->db->where('id', $message['id']);
        $this->db->update('bulk_sms_email', [
            'posting_status' => 0, // Processing
            'processing_lock' => 1,
            'processing_started' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
            
           
            // Process the message
            $result = $this->process_single_message($message);
            
            if ($result['success']) {
                $processed++;
                echo "     ✓ Success: {$result['sent']}/{$result['total']} sent\n";
            } else {
                $failed++;
                echo "     ✗ Failed: {$result['error']}\n";
            }
        }
        
        echo "   Result: {$processed} processed, {$failed} failed\n";
        
        // Clean up stale locks
        $this->cleanup_stale_locks();
    }

    /**
     * Process a single scheduled message
     */
    private function process_single_message($message)
    {
        try {
            $recipients = json_decode($message['additional'], true);
            
            if (empty($recipients) || !is_array($recipients)) {
                throw new Exception('No valid recipients');
            }
            
            $total = count($recipients);
            $sent = 0;
            $failed = 0;
            $credits_used = 0;
            
            // Check for credit reservation
            $reservation = $this->sendsmsmail_model->get_reservation_by_message($message['id']);
            $using_reserved_credits = ($reservation && $reservation->status == 'reserved');
            
            // Load SMS library if needed
            if ($message['message_type'] == 1) {
                $this->load->library('bulksmsbd', ['branch_id' => $message['branch_id']], 'sms_lib');
            }
            
            // Process each recipient
            foreach ($recipients as $recipient) {
                $result = false;
                
                if ($message['message_type'] == 1) { // SMS
                    // Personalize message
                    $personalized_msg = $this->personalize_message($message['message'], $recipient, $message['recipient_type']);
                    
                    // Send SMS
                    $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
                    if (!empty($mobile)) {
                        $response = $this->sms_lib->send($mobile, $personalized_msg);
                        $result = $this->is_successful_response($response);
                        
                        // Log delivery
                        $this->log_delivery(
                            $message['id'],
                            $recipient,
                            $result ? 'sent' : 'failed',
                            $response,
                            $message['branch_id']
                        );
                    }
                } else { // Email
                    // Email sending logic here
                    $result = true; // Placeholder
                }
                
                if ($result) {
                    $sent++;
                    // Calculate credits for this SMS
                    if ($message['message_type'] == 1) {
                        $credits_used += $this->calculate_single_sms_cost_cron(
                            $this->personalize_message($message['message'], $recipient, $message['recipient_type'])
                        );
                    }
                } else {
                    $failed++;
                }
                
                usleep(100000); // 0.1s delay
            }
            
            // Determine final status
            if ($sent == 0 && $failed > 0) {
                $final_status = 3; // failed
            } elseif ($sent == $total) {
                $final_status = 2; // completed
            } else {
                $final_status = 4; // partial
            }
            
            // Update message
            $update_data = [
                'posting_status' => $final_status,
                'successfully_sent' => $sent,
                'credits_used' => $credits_used,
                'processing_lock' => 0,
                'processing_started' => NULL,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->where('id', $message['id']);
            $this->db->update('bulk_sms_email', $update_data);
            
            // Update credit reservation
            if ($using_reserved_credits && $sent > 0) {
                $this->sendsmsmail_model->mark_reservation_used($message['id'], $credits_used);
                
                // Return unused credits if partial send
                if ($sent < $total && $reservation) {
                    $unused = $reservation->credits_reserved - $credits_used;
                    if ($unused > 0) {
                        $this->sendsmsmail_model->return_unused_reserved_credits(
                            $message['id'], 
                            $unused, 
                            $message['branch_id']
                        );
                    }
                }
            }
            
            // Update homework status if applicable
            if ($message['recipient_type'] == 4) { // Homework
                $details = json_decode($message['recipients_details'], true);
                if (isset($details['homework_id'])) {
                    $this->db->where('id', $details['homework_id']);
                    $this->db->update('homework', [
                        'sms_notification' => ($sent > 0 ? 3 : 0), // 3 = sent, 0 = disabled
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            return [
                'success' => true,
                'sent' => $sent,
                'failed' => $failed,
                'total' => $total
            ];
            
        } catch (Exception $e) {
            // Return reserved credits on error
            if (isset($message['id']) && $message['message_type'] == 1) {
                $this->sendsmsmail_model->return_reserved_credits($message['id'], 'failed_exception');
            }
            
            // Unlock message
            $this->db->where('id', $message['id']);
            $this->db->update('bulk_sms_email', [
                'posting_status' => 3, // failed
                'processing_lock' => 0,
                'processing_started' => NULL,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate SMS cost for cron
     */
    private function calculate_sms_cost_cron($message, $recipient_count)
    {
        // Use same logic as sendsmsmail controller
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
    
    private function calculate_single_sms_cost_cron($message)
    {
        $message_length = mb_strlen($message, 'UTF-8');
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
        
        return $sms_parts * $credits_per_part;
    }

        /**
     * Log delivery to database
     */
    private function log_delivery($message_id, $recipient, $status, $response, $branch_id)
    {
        $log_data = [
            'message_id' => $message_id,
            'recipient_contact' => $recipient['mobileno'] ?? $recipient['email'],
            'status' => $status,
            'gateway_response' => substr($response, 0, 500),
            'sent_at' => date('Y-m-d H:i:s'),
            'branch_id' => $branch_id
        ];
        
        $this->db->insert('sms_email_delivery_logs', $log_data);
        
        // Also log to homework_sms_logs if it's homework
        if (isset($recipient['type']) && in_array($recipient['type'], ['student', 'parent'])) {
            $homework_log = [
                'homework_id' => 0, // Will be updated if we can find it
                'recipient_contact' => $recipient['mobileno'],
                'recipient_name' => $recipient['name'],
                'recipient_type' => $recipient['type'],
                'status' => $status,
                'sent_at' => date('Y-m-d H:i:s'),
                'branch_id' => $branch_id
            ];
            
            // Try to find homework ID from message
            $msg = $this->db->select('recipients_details')
                          ->where('id', $message_id)
                          ->get('bulk_sms_email')
                          ->row();
            
            if ($msg) {
                $details = json_decode($msg->recipients_details, true);
                if (isset($details['homework_id'])) {
                    $homework_log['homework_id'] = $details['homework_id'];
                }
            }
            
            $this->db->insert('homework_sms_logs', $homework_log);
        }
    }

    /**
     * Clean up stale locks
     */
    private function cleanup_stale_locks()
    {
        $ten_minutes_ago = date('Y-m-d H:i:s', strtotime('-10 minutes'));
        
        $this->db->where('processing_lock', 1);
        $this->db->where('processing_started <', $ten_minutes_ago);
        $this->db->update('bulk_sms_email', [
            'processing_lock' => 0,
            'processing_started' => NULL,
            'posting_status' => 1 // Reset to scheduled
        ]);
        
        $affected = $this->db->affected_rows();
        if ($affected > 0) {
            // log_message('info', "Cleaned up {$affected} stale locks");
        }
    }

    // Add to the process_single_message method in personalize_message section:
private function personalize_message($template, $recipient, $type)
    {
        $message = $template;
        
        // Common replacements
        $message = str_replace('{name}', $recipient['name'] ?? '', $message);
        
        if ($type == 4) { // Homework
            $message = str_replace('{register_no}', $recipient['register_no'] ?? '', $message);
            $message = str_replace('{roll}', $recipient['roll'] ?? '', $message);
        } elseif ($type == 5) { // Fee reminder
            $message = str_replace('{guardian_name}', $recipient['type'] == 'parent' ? $recipient['name'] : '', $message);
            $message = str_replace('{child_name}', $recipient['type'] == 'student' ? $recipient['name'] : '', $message);
        } elseif ($type == 6) { // Student birthday (NEW)
            $message = str_replace('{name}', $recipient['name'], $message);
            $message = str_replace('{register_no}', $recipient['register_no'] ?? '', $message);
            $message = str_replace('{birthday}', $recipient['birthday'] ?? '', $message);
            $message = str_replace('{class}', $recipient['class_name'] ?? '', $message);
            $message = str_replace('{section}', $recipient['section_name'] ?? '', $message);
            $message = str_replace('{roll}', $recipient['roll'] ?? '', $message);
        } elseif ($type == 7) { // Staff birthday (NEW)
            $message = str_replace('{name}', $recipient['name'], $message);
            $message = str_replace('{joining_date}', $recipient['joining_date'] ?? '', $message);
            $message = str_replace('{birthday}', $recipient['birthday'] ?? '', $message);
        }
        
        return $message;
    }

/**
 * FIXED: Generate birthday reminders with PROPER duplicate prevention
 */
private function generate_birthday_reminders()
{
    $scheduled_count = 0;
    $today = date('m-d'); // Match month and day only
    $today_date = date('Y-m-d'); // Full date
    
    echo "\n3. Generating Birthday Reminders:\n";
    
    // Load SMS model for credit checking
    $this->load->model('sendsmsmail_model');
    
    // Get all branches
    $branches = $this->db->get('branch')->result_array();
    
    foreach ($branches as $branch) {
        echo "   Processing Branch: {$branch['name']}\n";
        
        // ========== STUDENT BIRTHDAYS ==========
        $student_template = $this->db->get_where('sms_template_details', [
            'template_id' => 9,
            'branch_id' => $branch['id']
        ])->row_array();
        
        if ($student_template && ($student_template['notify_student'] == 1 || $student_template['notify_parent'] == 1)) {
            // Get birthdays that haven't been sent today - USING birthday_sms_tracking TABLE
            $sql = "SELECT s.id 
                    FROM student s
                    INNER JOIN enroll e ON e.student_id = s.id AND e.session_id = ? AND e.branch_id = ?
                    WHERE DATE_FORMAT(STR_TO_DATE(s.birthday, '%Y-%m-%d'), '%m-%d') = ?
                    AND s.id NOT IN (
                        SELECT person_id 
                        FROM birthday_sms_tracking 
                        WHERE person_type = 'student' 
                        AND sent_date = ?
                        AND branch_id = ?
                    )
                    LIMIT 10";
            
            $birthday_students = $this->db->query($sql, [
                get_session_id(),
                $branch['id'],
                $today,
                $today_date,
                $branch['id']
            ])->result_array();
            
            if (!empty($birthday_students)) {
                echo "     Found " . count($birthday_students) . " students with birthdays\n";
                
                foreach ($birthday_students as $student) {
                    $student_id = $student['id'];
                    
                    // Create SMS (with duplicate prevention and credit checking)
                    $message_id = $this->create_student_birthday_sms($student_id, $branch['id']);
                    if ($message_id) {
                        $scheduled_count++;
                        echo "     ✓ Scheduled birthday for student ID: {$student_id}\n";
                    }
                }
            }
        }
        
        // ========== STAFF BIRTHDAYS ==========
        $staff_template = $this->db->get_where('sms_template_details', [
            'template_id' => 10,
            'branch_id' => $branch['id']
        ])->row_array();
        
        if ($staff_template) {
            // Get staff birthdays not sent today - USING birthday_sms_tracking TABLE
            $sql = "SELECT id 
                    FROM staff
                    WHERE branch_id = ?
                    AND DATE_FORMAT(STR_TO_DATE(birthday, '%Y-%m-%d'), '%m-%d') = ?
                    AND id NOT IN (
                        SELECT person_id 
                        FROM birthday_sms_tracking 
                        WHERE person_type = 'staff' 
                        AND sent_date = ?
                        AND branch_id = ?
                    )
                    LIMIT 5";
            
            $birthday_staff = $this->db->query($sql, [
                $branch['id'],
                $today,
                $today_date,
                $branch['id']
            ])->result_array();
            
            if (!empty($birthday_staff)) {
                echo "     Found " . count($birthday_staff) . " staff with birthdays\n";
                
                foreach ($birthday_staff as $staff) {
                    $staff_id = $staff['id'];
                    
                    // Create SMS (with duplicate prevention and credit checking)
                    $message_id = $this->create_staff_birthday_sms($staff_id, $branch['id']);
                    if ($message_id) {
                        $scheduled_count++;
                        echo "     ✓ Scheduled birthday for staff ID: {$staff_id}\n";
                    }
                }
            }
        }
    }
    
    echo "   Scheduled: {$scheduled_count} NEW birthday wishes\n";
    return $scheduled_count;
}

/**
 * DEBUG: Show birthday duplicate statistics
 */
private function debug_birthday_duplicates()
{
    $today = date('Y-m-d');
    
    $this->db->select("
        COUNT(*) as total_today,
        SUM(CASE WHEN send_type LIKE 'auto_%' THEN 1 ELSE 0 END) as auto_messages,
        SUM(CASE WHEN send_type LIKE 'manual_%' THEN 1 ELSE 0 END) as manual_messages,
        GROUP_CONCAT(DISTINCT send_type) as send_types
    ");
    $this->db->from('bulk_sms_email');
    $this->db->where_in('recipient_type', [6, 7]);
    $this->db->where('DATE(created_at)', $today);
    $stats = $this->db->get()->row_array();
    
    echo "\n   DEBUG: Today's birthday messages - Total: {$stats['total_today']}, Auto: {$stats['auto_messages']}, Manual: {$stats['manual_messages']}\n";
    
    // Show duplicate student messages
    $this->db->select("
        JSON_UNQUOTE(JSON_EXTRACT(recipients_details, '$.student_id')) as student_id,
        COUNT(*) as count
    ");
    $this->db->from('bulk_sms_email');
    $this->db->where('recipient_type', 6);
    $this->db->where('DATE(created_at)', $today);
    $this->db->where('send_type', 'auto_student_birthday');
    $this->db->group_by('student_id');
    $this->db->having('count > 1');
    $student_dupes = $this->db->get()->result_array();
    
    if (!empty($student_dupes)) {
        echo "   WARNING: Found " . count($student_dupes) . " students with duplicate auto messages\n";
        foreach ($student_dupes as $dupe) {
            echo "      Student ID {$dupe['student_id']}: {$dupe['count']} messages\n";
        }
    }
    
    // Show duplicate staff messages  
    $this->db->select("
        JSON_UNQUOTE(JSON_EXTRACT(recipients_details, '$.staff_id')) as staff_id,
        COUNT(*) as count
    ");
    $this->db->from('bulk_sms_email');
    $this->db->where('recipient_type', 7);
    $this->db->where('DATE(created_at)', $today);
    $this->db->where('send_type', 'auto_staff_birthday');
    $this->db->group_by('staff_id');
    $this->db->having('count > 1');
    $staff_dupes = $this->db->get()->result_array();
    
    if (!empty($staff_dupes)) {
        echo "   WARNING: Found " . count($staff_dupes) . " staff with duplicate auto messages\n";
        foreach ($staff_dupes as $dupe) {
            echo "      Staff ID {$dupe['staff_id']}: {$dupe['count']} messages\n";
        }
    }
}

// IN: Cron_api.php - REPLACE THE EXISTING METHODS

/**
 * Create student birthday SMS entry - FIXED VERSION
 */
private function create_student_birthday_sms($student_id, $branch_id)
{
    try {
        // ========== STEP 1: CHECK FOR EXISTING DUPLICATE ==========
        $today = date('Y-m-d');
        
        // Generate duplicate hash BEFORE creating anything
        $duplicate_hash = $this->generate_birthday_duplicate_hash('student', $student_id, $branch_id, $today);
        
        // Check if identical message already exists today
        $this->db->where('duplicate_hash', $duplicate_hash);
        $this->db->where('DATE(created_at)', $today);
        $this->db->where_in('posting_status', [0, 1, 2]); // processing, scheduled, completed
        $existing = $this->db->get('bulk_sms_email')->row();
        
        if ($existing) {
            // log_message('info', "Duplicate birthday SMS prevented for student {$student_id}. Existing ID: {$existing->id}");
            return false; // Don't create duplicate
        }
        
        // ========== STEP 2: GET STUDENT DETAILS ==========
        $student = $this->db->select('s.*, p.mobileno as parent_mobile, p.name as parent_name')
                           ->from('student s')
                           ->join('parent p', 'p.id = s.parent_id', 'left')
                           ->where('s.id', $student_id)
                           ->get()
                           ->row_array();
        
        if (empty($student)) {
            // log_message('error', "Student not found: {$student_id}");
            return false;
        }
        
        // ========== STEP 3: GET SMS TEMPLATE ==========
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 9,
            'branch_id' => $branch_id
        ])->row_array();
        
        if (empty($template) || empty($template['template_body'])) {
            // log_message('error', "No SMS template for student birthday in branch {$branch_id}");
            return false;
        }
        
        // ========== STEP 4: PREPARE RECIPIENTS ==========
        $recipients = [];
        $recipient_count = 0;
        
        // Student recipient
        if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
            $recipients[] = [
                'id' => $student_id,
                'name' => $student['first_name'] . ' ' . $student['last_name'],
                'mobileno' => $student['mobileno'],
                'email' => $student['email'],
                'type' => 'student',
                'birthday' => $student['birthday'],
                'register_no' => $student['register_no']
            ];
            $recipient_count++;
        }
        
        // Parent recipient
        if ($template['notify_parent'] == 1 && !empty($student['parent_mobile'])) {
            $recipients[] = [
                'id' => $student_id . '_parent',
                'name' => $student['parent_name'],
                'mobileno' => $student['parent_mobile'],
                'email' => '',
                'type' => 'parent',
                'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                'student_birthday' => $student['birthday'],
                'student_register_no' => $student['register_no']
            ];
            $recipient_count++;
        }
        
        if (empty($recipients)) {
            // log_message('warning', "No recipients for student birthday {$student_id}");
            return false;
        }
        
        // ========== STEP 5: CALCULATE CREDITS NEEDED ==========
        $credits_needed = $this->calculate_sms_cost_cron($template['template_body'], $recipient_count);
        
        // ========== STEP 6: RESERVE CREDITS FIRST ==========
        $campaign_name = "Auto: Student Birthday - " . $student['first_name'] . ' ' . $student['last_name'] . " - " . date('d M Y');
        
        // RESERVE CREDITS BEFORE creating message
        $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
            $branch_id, 
            $credits_needed, 
            $campaign_name, 
            null // Message ID will be set after insert
        );
        
        if (!$reservation_id) {
            // log_message('error', "Failed to reserve credits for student birthday {$student_id}. Branch: {$branch_id}, Credits: {$credits_needed}");
            return false; // Don't create message if credits can't be reserved
        }
        
        // ========== STEP 7: CREATE SMS ENTRY ==========
        $message_data = [
            'campaign_name' => $campaign_name,
            'message' => $template['template_body'],
            'message_type' => 1,
            'recipient_type' => 6,
            'recipients_details' => json_encode([
                'student_id' => $student_id,
                'template_id' => 9,
                'notify_student' => $template['notify_student'],
                'notify_parent' => $template['notify_parent'],
                'reservation_id' => $reservation_id
            ]),
            'additional' => json_encode($recipients),
            'schedule_time' => date('Y-m-d H:i:s'),
            'posting_status' => 1,
            'total_thread' => $recipient_count,
            'successfully_sent' => 0,
            'sms_gateway' => 'bulksmsbd',
            'credits_used' => 0,
            'branch_id' => $branch_id,
            'duplicate_hash' => $duplicate_hash, // CRITICAL: Add duplicate hash
            'send_type' => 'auto_student_birthday',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('bulk_sms_email', $message_data);
        $message_id = $this->db->insert_id();
        
        // ========== STEP 8: UPDATE RESERVATION WITH MESSAGE ID ==========
        $this->db->where('id', $reservation_id);
        $this->db->update('sms_credit_reservations', [
            'message_id' => $message_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // ========== STEP 9: LOG TO birthday_sms_tracking TABLE ==========
        $tracking_data = [
            'person_id' => $student_id,
            'person_type' => 'student',
            'birthday_date' => $student['birthday'],
            'sent_date' => $today,
            'recipient_type' => 'student',
            'mobile_number' => $student['mobileno'] ?? '',
            'message_id' => $message_id,
            'branch_id' => $branch_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('birthday_sms_tracking', $tracking_data);
        
        // log_message('info', "✅ Auto student birthday SMS scheduled: Student {$student_id}, Message ID: {$message_id}, Reservation: {$reservation_id}, Credits: {$credits_needed}");
        
        return $message_id;
        
    } catch (Exception $e) {
        // log_message('error', 'Error creating student birthday SMS: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create staff birthday SMS entry - FIXED VERSION
 */
private function create_staff_birthday_sms($staff_id, $branch_id)
{
    try {
        // ========== STEP 1: CHECK FOR EXISTING DUPLICATE ==========
        $today = date('Y-m-d');
        
        // Generate duplicate hash BEFORE creating anything
        $duplicate_hash = $this->generate_birthday_duplicate_hash('staff', $staff_id, $branch_id, $today);
        
        // Check if identical message already exists today
        $this->db->where('duplicate_hash', $duplicate_hash);
        $this->db->where('DATE(created_at)', $today);
        $this->db->where_in('posting_status', [0, 1, 2]); // processing, scheduled, completed
        $existing = $this->db->get('bulk_sms_email')->row();
        
        if ($existing) {
            // log_message('info', "Duplicate birthday SMS prevented for staff {$staff_id}. Existing ID: {$existing->id}");
            return false; // Don't create duplicate
        }
        
        // ========== STEP 2: GET STAFF DETAILS ==========
        $staff = $this->db->select('s.*, d.name as designation_name')
                         ->from('staff s')
                         ->join('staff_designation d', 'd.id = s.designation', 'left')
                         ->where('s.id', $staff_id)
                         ->where('s.branch_id', $branch_id)
                         ->get()
                         ->row_array();
        
        if (empty($staff)) {
            // log_message('error', "Staff not found: {$staff_id}");
            return false;
        }
        
        // ========== STEP 3: GET SMS TEMPLATE ==========
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 10,
            'branch_id' => $branch_id
        ])->row_array();
        
        if (empty($template) || empty($template['template_body'])) {
            // log_message('error', "No SMS template for staff birthday in branch {$branch_id}");
            return false;
        }
        
        // ========== STEP 4: PREPARE RECIPIENT ==========
        if (empty($staff['mobileno'])) {
            // log_message('warning', "Staff {$staff_id} has no mobile number");
            return false;
        }
        
        $recipients = [[
            'id' => $staff_id,
            'name' => $staff['name'],
            'mobileno' => $staff['mobileno'],
            'email' => $staff['email'],
            'type' => 'staff',
            'birthday' => $staff['birthday'],
            'joining_date' => $staff['joining_date'],
            'designation' => $staff['designation_name'] ?? 'N/A'
        ]];
        
        $recipient_count = 1;
        
        // ========== STEP 5: CALCULATE CREDITS NEEDED ==========
        $credits_needed = $this->calculate_sms_cost_cron($template['template_body'], $recipient_count);
        
        // ========== STEP 6: RESERVE CREDITS FIRST ==========
        $campaign_name = "Auto: Staff Birthday - " . $staff['name'] . " - " . date('d M Y');
        
        // RESERVE CREDITS BEFORE creating message
        $reservation_id = $this->sendsmsmail_model->reserve_sms_credits(
            $branch_id, 
            $credits_needed, 
            $campaign_name, 
            null // Message ID will be set after insert
        );
        
        if (!$reservation_id) {
            // log_message('error', "Failed to reserve credits for staff birthday {$staff_id}. Branch: {$branch_id}, Credits: {$credits_needed}");
            return false; // Don't create message if credits can't be reserved
        }
        
        // ========== STEP 7: CREATE SMS ENTRY ==========
        $message_data = [
            'campaign_name' => $campaign_name,
            'message' => $template['template_body'],
            'message_type' => 1,
            'recipient_type' => 7,
            'recipients_details' => json_encode([
                'staff_id' => $staff_id,
                'template_id' => 10,
                'reservation_id' => $reservation_id
            ]),
            'additional' => json_encode($recipients),
            'schedule_time' => date('Y-m-d H:i:s'),
            'posting_status' => 1,
            'total_thread' => $recipient_count,
            'successfully_sent' => 0,
            'sms_gateway' => 'bulksmsbd',
            'credits_used' => 0,
            'branch_id' => $branch_id,
            'duplicate_hash' => $duplicate_hash, // CRITICAL: Add duplicate hash
            'send_type' => 'auto_staff_birthday',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('bulk_sms_email', $message_data);
        $message_id = $this->db->insert_id();
        
        // ========== STEP 8: UPDATE RESERVATION WITH MESSAGE ID ==========
        $this->db->where('id', $reservation_id);
        $this->db->update('sms_credit_reservations', [
            'message_id' => $message_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // ========== STEP 9: LOG TO birthday_sms_tracking TABLE ==========
        $tracking_data = [
            'person_id' => $staff_id,
            'person_type' => 'staff',
            'birthday_date' => $staff['birthday'],
            'sent_date' => $today,
            'recipient_type' => 'staff',
            'mobile_number' => $staff['mobileno'] ?? '',
            'message_id' => $message_id,
            'branch_id' => $branch_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('birthday_sms_tracking', $tracking_data);
        
        // log_message('info', "✅ Auto staff birthday SMS scheduled: Staff {$staff_id}, Message ID: {$message_id}, Reservation: {$reservation_id}, Credits: {$credits_needed}");
        
        return $message_id;
        
    } catch (Exception $e) {
        // log_message('error', 'Error creating staff birthday SMS: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate birthday duplicate hash - NEW METHOD
 */
private function generate_birthday_duplicate_hash($type, $person_id, $branch_id, $date) {
    $data = [
        'type' => $type,
        'person_id' => $person_id,
        'branch_id' => $branch_id,
        'date' => $date,
        'template_type' => ($type == 'student' ? 9 : 10) // template ID
    ];
    return md5(serialize($data));
}


/**
 * Create automated birthday SMS entry
 */
private function create_auto_birthday_sms($type, $ids, $branch_id)
{
    // Similar to manual method but with auto prefix
    // You can reuse the logic from the Birthday controller
    // Or call the model method directly
    
    // For now, just log and return true
    // log_message('info', "Auto birthday SMS for {$type}s: " . implode(',', $ids));
    return true;
}

    
     /**
     * Check expired subscriptions - called by cron
     */
    public function check_subscriptions()
    {
        $expired_count = $this->subscription_model->check_expired_subscriptions();
        // log_message('info', "Cron: Checked subscriptions - {$expired_count} expired");
        echo "Checked subscriptions - {$expired_count} expired\n";
    }

    public function cleanup_subscriptions()
{
    $this->load->model('subscription_model');
    $cleaned = $this->subscription_model->cleanup_stuck_pending_subscriptions();
    echo "Cleaned up {$cleaned} stuck subscriptions";
}

public function some_method()
{
    // After sending SMS, track it
    $this->track_usage('bulk_sms_and_email', 'sms_sent', $sms_units, $message_id, [
        'recipient_count' => $count,
        'message_length' => $length
    ]);
}
 /**
     * Check expired subscriptions (run daily)
     * Usage: php index.php cron check_expired
     */
    public function check_expired()
    {
        $count = $this->subscription_model->check_expired_subscriptions();
        echo "[" . date('Y-m-d H:i:s') . "] Checked expired subscriptions: {$count} expired\n";
    }
    
    /**
     * Send expiry reminders (run daily)
     */
    public function send_reminders()
    {
        $this->subscription_model->send_expiry_reminders();
        echo "[" . date('Y-m-d H:i:s') . "] Expiry reminders sent\n";
    }
    
    /**
     * Reset SMS credits for new billing cycle (run daily)
     */
    public function reset_sms_credits()
    {
        $count = $this->subscription_model->reset_sms_credits();
        echo "[" . date('Y-m-d H:i:s') . "] Reset SMS credits for {$count} branches\n";
    }
    
    /**
     * Generate renewal invoices (run weekly)
     */
    public function generate_invoices()
    {
        $this->load->model('subscription_invoice_model');
        $count = $this->subscription_invoice_model->generate_renewal_invoices();
        echo "[" . date('Y-m-d H:i:s') . "] Generated {$count} renewal invoices\n";
    }
    
    /**
     * Run all maintenance tasks
     */
    public function all()
    {
        $this->check_expired();
        $this->send_reminders();
        $this->reset_sms_credits();
        $this->generate_invoices();
    }
        /**
     * Send upcoming leave reminders (run daily at 8 AM)
     * 
     * Usage in crontab:
     * 0 8 * * * wget -q -O- "http://yourdomain.com/cron_api/upcoming_leave_reminders/YOUR_API_KEY"
     */
    public function upcoming_leave_reminders($api_key = '')
    {
        // Verify API key
        if ($api_key != "" && $this->api_key != $api_key) {
            // log_message('error', 'Upcoming Leave Reminders: API Key mismatch');
            echo "API Key mismatch.\n";
            return;
        }
        
        echo "=== Sending Upcoming Leave Reminders ===\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        
        $this->load->model('leave_model');
        $upcoming_leaves = $this->leave_model->get_upcoming_leaves();
        
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($upcoming_leaves as $leave) {
            // Check if reminder already sent for this leave
            $already_sent = $this->db->where('leave_id', $leave['id'])
                ->where('sent_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
                ->get('leave_sms_logs')
                ->num_rows();
            
            // If leave_sms_logs table doesn't exist, check sms_email_delivery_logs instead
            if ($already_sent == 0) {
                $already_sent = $this->db->where('message LIKE', '%leave starts tomorrow%')
                    ->where('recipient_contact', $leave['user_mobile'] ?? '')
                    ->where('sent_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
                    ->get('sms_email_delivery_logs')
                    ->num_rows();
            }
            
            if ($already_sent > 0) {
                echo "     Skip leave ID {$leave['id']} - reminder already sent\n";
                continue;
            }
            // ========== END OF CHECK ==========
    
    
            $start_date = date('d M Y', strtotime($leave['start_date']));
            
            if ($leave['role_id'] == 7) {
                // Student - send to parent
                $studentData = $this->db->select('s.first_name, s.last_name, p.mobileno as parent_mobile, p.name as parent_name')
                    ->from('student s')
                    ->join('parent p', 'p.id = s.parent_id', 'left')
                    ->where('s.id', $leave['user_id'])
                    ->get()
                    ->row();
                
                if ($studentData && !empty($studentData->parent_mobile)) {
                    $student_name = $studentData->first_name . ' ' . $studentData->last_name;
                    $message = "Reminder: Your child {$student_name}'s leave starts tomorrow ({$start_date}).";
                    
                    $result = $this->_send_leave_reminder_sms($studentData->parent_mobile, $message, $leave['branch_id']);
                    if ($result) {
                        $sent_count++;
                        echo "  ✓ Sent to parent of {$student_name}\n";
                    } else {
                        $failed_count++;
                        echo "  ✗ Failed to send to parent of {$student_name}\n";
                    }
                }
            } else {
                // Staff - send to staff
                $staffData = $this->db->select('name, mobileno')
                    ->where('id', $leave['user_id'])
                    ->get('staff')
                    ->row();
                
                if ($staffData && !empty($staffData->mobileno)) {
                    $message = "Reminder: Dear {$staffData->name}, your leave starts tomorrow ({$start_date}). Please ensure all handover is complete.";
                    
                    $result = $this->_send_leave_reminder_sms($staffData->mobileno, $message, $leave['branch_id']);
                    if ($result) {
                        $sent_count++;
                        echo "  ✓ Sent to staff {$staffData->name}\n";
                    } else {
                        $failed_count++;
                        echo "  ✗ Failed to send to staff {$staffData->name}\n";
                    }
                }
            }
        }
        
        echo "\n=== Summary: {$sent_count} sent, {$failed_count} failed ===\n";
        // log_message('info', "Upcoming leave reminders: {$sent_count} sent, {$failed_count} failed");
    }

    /**
     * Send return from leave reminders (run daily at 8 AM)
     * 
     * Usage in crontab:
     * 0 8 * * * wget -q -O- "http://yourdomain.com/cron_api/return_reminders/YOUR_API_KEY"
     */
    public function return_reminders($api_key = '')
    {
        // Verify API key
        if ($api_key != "" && $this->api_key != $api_key) {
            // log_message('error', 'Return Reminders: API Key mismatch');
            echo "API Key mismatch.\n";
            return;
        }
        
        echo "=== Sending Return from Leave Reminders ===\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        
        $this->load->model('leave_model');
        $returning_leaves = $this->leave_model->get_today_returning_leaves();
        
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($returning_leaves as $leave) {
            // Check if return reminder already sent for this leave
            $already_sent = $this->db->where('leave_id', $leave['id'])
                ->where('sent_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
                ->get('leave_sms_logs')
                ->num_rows();
            
            if ($already_sent == 0) {
                $already_sent = $this->db->where('message LIKE', '%expected to return%')
                    ->where('recipient_contact', $leave['user_mobile'] ?? '')
                    ->where('sent_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
                    ->get('sms_email_delivery_logs')
                    ->num_rows();
            }
            
            if ($already_sent > 0) {
                echo "     Skip leave ID {$leave['id']} - return reminder already sent\n";
                continue;
            }
            // ========== END OF CHECK ==========

            if ($leave['role_id'] == 7) {
                // Student - send to parent
                $studentData = $this->db->select('s.first_name, s.last_name, p.mobileno as parent_mobile, p.name as parent_name')
                    ->from('student s')
                    ->join('parent p', 'p.id = s.parent_id', 'left')
                    ->where('s.id', $leave['user_id'])
                    ->get()
                    ->row();
                
                if ($studentData && !empty($studentData->parent_mobile)) {
                    $student_name = $studentData->first_name . ' ' . $studentData->last_name;
                    $message = "Reminder: Your child {$student_name} is expected to return to school today as leave ends.";
                    
                    $result = $this->_send_leave_reminder_sms($studentData->parent_mobile, $message, $leave['branch_id']);
                    if ($result) {
                        $sent_count++;
                        echo "  ✓ Sent to parent of {$student_name}\n";
                    } else {
                        $failed_count++;
                        echo "  ✗ Failed to send to parent of {$student_name}\n";
                    }
                }
            } else {
                // Staff - send to staff
                $staffData = $this->db->select('name, mobileno')
                    ->where('id', $leave['user_id'])
                    ->get('staff')
                    ->row();
                
                if ($staffData && !empty($staffData->mobileno)) {
                    $message = "Reminder: Dear {$staffData->name}, you are expected to resume duty today as your leave ends.";
                    
                    $result = $this->_send_leave_reminder_sms($staffData->mobileno, $message, $leave['branch_id']);
                    if ($result) {
                        $sent_count++;
                        echo "  ✓ Sent to staff {$staffData->name}\n";
                    } else {
                        $failed_count++;
                        echo "  ✗ Failed to send to staff {$staffData->name}\n";
                    }
                }
            }
        }
        
        echo "\n=== Summary: {$sent_count} sent, {$failed_count} failed ===\n";
        // log_message('info', "Return reminders: {$sent_count} sent, {$failed_count} failed");
    }

    /**
     * Core SMS sending function for leave reminders (with credit checking)
     * 
     * @param string $mobile
     * @param string $message
     * @param int $branch_id
     * @return bool
     */
    private function _send_leave_reminder_sms($mobile, $message, $branch_id)
    {
        if (empty($mobile) || empty($message)) {
            return false;
        }
        
        // Clean mobile number
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        if (strlen($mobile) < 9) {
            // log_message('error', "Leave Reminder: Invalid mobile number: {$mobile}");
            return false;
        }
        
        // Get active SMS gateway
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
            // log_message('warning', "Leave Reminder: Insufficient credits. Branch: {$branch_id}, Needed: {$credits_needed}, Available: {$current_credits}");
            return false;
        }
        
        // Send SMS using existing method
        try {
            // Load the SMS library
            $this->load->library('bulksmsbd', ['branch_id' => $branch_id], 'sms_lib');
            
            $response = $this->sms_lib->send($mobile, $message);
            
            // Check if successful
            $success = $this->_is_successful_response($response);
            
            if ($success) {
                // Deduct credits
                $this->sendsmsmail_model->deduct_sms_units($branch_id, $credits_needed);
                
                // Log delivery
                $this->db->insert('sms_email_delivery_logs', [
                    'recipient_contact' => $mobile,
                    'status' => 'sent',
                    'gateway_response' => substr($response, 0, 500),
                    'sent_at' => date('Y-m-d H:i:s'),
                    'branch_id' => $branch_id
                ]);
                
                // log_message('info', "Leave Reminder SMS sent successfully to: {$mobile}");
            } else {
                // log_message('error', "Leave Reminder SMS failed to: {$mobile} - Response: {$response}");
            }
            
            return $success;
            
        } catch (Exception $e) {
            // log_message('error', "Leave Reminder SMS Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if SMS response is successful
     */
    private function _is_successful_response($response)
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
     * Check low stock and send alerts (run daily at 9 AM)
     * 
     * Usage: wget -q -O- "http://yourdomain.com/cron_api/low_stock/YOUR_API_KEY"
     */
    public function low_stock($api_key = '')
    {
        if ($api_key != "" && $this->api_key != $api_key) {
            // log_message('error', 'Low Stock Cron: API Key mismatch');
            echo "API Key mismatch.\n";
            return;
        }
        
        echo "=== Checking Low Stock Alerts ===\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        
        $this->load->model('inventory_model');
        $this->load->controller('inventory');
        
        $branches = $this->db->select('id')->where('status', 1)->get('branch')->result();
        $alert_count = 0;
        
        foreach ($branches as $branch) {
            $low_stock = $this->inventory_model->get_low_stock_products($branch->id);
            
            if (!empty($low_stock)) {
                // Only send if any product should get an alert (not recently alerted)
                $products_to_alert = [];
                foreach ($low_stock as $product) {
                    if ($this->inventory_model->should_send_stock_alert($product['id'], 24)) {
                        $products_to_alert[] = $product['id'];
                    }
                }
                
                if (!empty($products_to_alert)) {
                    $this->inventory->check_low_stock_alerts($branch->id);
                    $alert_count++;
                    echo "Branch {$branch->id}: Alert sent for " . count($products_to_alert) . " products\n";
                } else {
                    echo "Branch {$branch->id}: Low stock but alerts already sent recently\n";
                }
            } else {
                echo "Branch {$branch->id}: No low stock products\n";
            }
        }
        
        echo "=== Completed: {$alert_count} alerts sent ===\n";
    }
 

/**
 * Send emergency follow-up SMS to assigned staff
 */
private function _send_emergency_followup_sms($incident)
{
    // Get assigned staff for this room
    $staff_recipients = array();
    
    if (!empty($incident['room_id'])) {
        $assigned_staff = $this->db->select('s.id, s.name, s.mobileno')
            ->from('hostel_staff_assignments a')
            ->join('staff s', 's.id = a.staff_id')
            ->where('a.room_id', $incident['room_id'])
            ->where('a.is_active', 1)
            ->get()
            ->result_array();
        
        foreach ($assigned_staff as $staff) {
            if (!empty($staff['mobileno'])) {
                $staff_recipients[] = $staff;
            }
        }
    }
    
    // Also notify the original reporter if different
    if (!empty($incident['reported_by'])) {
        $reporter = $this->db->select('id, name, mobileno')
            ->where('id', $incident['reported_by'])
            ->get('staff')
            ->row_array();
        
        if ($reporter && !empty($reporter['mobileno'])) {
            // Check if already in list
            $exists = false;
            foreach ($staff_recipients as $sr) {
                if ($sr['id'] == $reporter['id']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $staff_recipients[] = $reporter;
            }
        }
    }
    
    if (empty($staff_recipients)) {
        // log_message('warning', "No staff recipients for incident follow-up: {$incident['incident_code']}");
        return false;
    }
    
    // Prepare message
    $student_name = ($incident['first_name'] ? $incident['first_name'] . ' ' . $incident['last_name'] : 'A student');
    $days_ago = $this->get_days_since($incident['reported_at']);
    
    $message = "FOLLOW-UP REQUIRED: Incident {$incident['incident_code']} ({$incident['emergency_type']}) reported {$days_ago} day(s) ago.\n";
    $message .= "Student: {$student_name}\n";
    $message .= "Status: " . ucfirst($incident['status']) . "\n";
    $message .= "Please update status or take action. Login to system to update.\n";
    $message .= "School: " . ($incident['branch_id'] ? $this->_get_branch_phone($incident['branch_id']) : '');
    
    // Check credits before sending
    $credits_needed = $this->_calculate_sms_credits($message, count($staff_recipients));
    $current_credits = $this->sendsmsmail_model->get_sms_credit($incident['branch_id']);
    
    if ($current_credits < $credits_needed) {
        // log_message('error', "Insufficient credits for follow-up SMS. Branch: {$incident['branch_id']}, Needed: {$credits_needed}");
        return false;
    }
    
    // Send to each recipient
    $sent_count = 0;
    foreach ($staff_recipients as $recipient) {
        $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
        if (!empty($mobile)) {
            $personalized_msg = $message;
            $personalized_msg = str_replace('{staff_name}', $recipient['name'], $personalized_msg);
            
            $result = $this->_send_sms_via_gateway($mobile, $personalized_msg, $incident['branch_id']);
            
            if ($result) {
                $sent_count++;
                $this->_log_emergency_followup_sms($incident['id'], $recipient, $personalized_msg, $incident['branch_id']);
            }
        }
        usleep(100000); // 0.1s delay between sends
    }
    
    // Deduct credits
    if ($sent_count > 0) {
        $credits_to_deduct = ceil(($credits_needed * $sent_count) / count($staff_recipients));
        $this->sendsmsmail_model->deduct_sms_units($incident['branch_id'], $credits_to_deduct);
    }
    
    return $sent_count > 0;
}

/**
 * Log emergency follow-up SMS
 */
private function _log_emergency_followup_sms($incident_id, $recipient, $message, $branch_id)
{
    if (!$this->db->table_exists('hostel_emergency_sms_logs')) {
        return;
    }
    
    $log_data = array(
        'incident_id' => $incident_id,
        'recipient_name' => $recipient['name'],
        'recipient_phone' => $recipient['mobileno'],
        'recipient_type' => 'staff',
        'message_sent' => substr($message, 0, 500),
        'status' => 'sent',
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->insert('hostel_emergency_sms_logs', $log_data);
}

/**
 * Calculate SMS credits for follow-up messages
 */
private function _calculate_sms_credits($message, $recipient_count)
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

/**
 * Send SMS via configured gateway
 */
private function _send_sms_via_gateway($mobile, $message, $branch_id)
{
    if (empty($mobile) || empty($message)) {
        return false;
    }
    
    try {
        // Load the SMS library with branch credentials
        $this->load->library('bulksmsbd', ['branch_id' => $branch_id], 'emergency_sms_lib');
        
        $response = $this->emergency_sms_lib->send($mobile, $message);
        
        // Check if successful
        return $this->_is_successful_response($response);
        
    } catch (Exception $e) {
        // log_message('error', 'Emergency follow-up SMS Exception: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get days since a date
 */
private function get_days_since($date)
{
    $datetime1 = new DateTime($date);
    $datetime2 = new DateTime(date('Y-m-d H:i:s'));
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

/**
 * Get branch phone number
 */
private function _get_branch_phone($branch_id)
{
    $branch = $this->db->select('mobileno')->where('id', $branch_id)->get('branch')->row();
    return $branch ? $branch->mobileno : 'School Office';
}

/**
 * Auto-resolve stale incidents (run daily)
 * Incidents older than 7 days with status 'reported' or 'investigating' are auto-resolved
 * 
 * Usage: wget -q -O- "http://yourdomain.com/cron_api/auto_resolve_incidents/YOUR_API_KEY"
 */
public function auto_resolve_incidents($api_key = '')
{
    if ($api_key != "" && $this->api_key != $api_key) {
        echo "API Key mismatch.\n";
        return;
    }
    
    echo "=== Auto-resolving Stale Emergency Incidents ===\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    
    // Get incidents that are reported/investigating and older than 7 days
    $sql = "SELECT id, incident_code, branch_id, title, reported_at 
            FROM hostel_emergency_incidents 
            WHERE status IN ('reported', 'investigating')
            AND DATE(reported_at) <= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND resolved_at IS NULL";
    
    $stale_incidents = $this->db->query($sql)->result_array();
    
    if (empty($stale_incidents)) {
        echo "No stale incidents to auto-resolve.\n";
        return;
    }
    
    echo "Found " . count($stale_incidents) . " stale incidents.\n";
    $resolved_count = 0;
    
    foreach ($stale_incidents as $incident) {
        $update_data = array(
            'status' => 'resolved',
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolved_by' => 1, // System user (superadmin)
            'action_taken' => 'Auto-resolved by system after 7 days of no activity.',
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        $this->db->where('id', $incident['id']);
        $result = $this->db->update('hostel_emergency_incidents', $update_data);
        
        if ($result) {
            $resolved_count++;
            echo "  ✓ Auto-resolved: {$incident['incident_code']} - {$incident['title']}\n";
            
            // Log auto-resolution
            $this->log_auto_resolution($incident);
        }
    }
    
    echo "\n=== Auto-resolved {$resolved_count} incidents ===\n";
    // log_message('info', "Auto-resolved {$resolved_count} stale emergency incidents");
}

/**
 * Log auto-resolution
 */
private function log_auto_resolution($incident)
{
    $log_data = array(
        'incident_id' => $incident['id'],
        'message_sent' => 'Auto-resolved by system after 7 days',
        'recipient_type' => 'system',
        'status' => 'sent',
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $incident['branch_id'],
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->insert('hostel_emergency_sms_logs', $log_data);
}

/**
 * Send weekly emergency summary to admins (run weekly on Monday)
 * 
 * Usage: wget -q -O- "http://yourdomain.com/cron_api/emergency_summary/YOUR_API_KEY"
 */
public function emergency_summary($api_key = '')
{
    if ($api_key != "" && $this->api_key != $api_key) {
        echo "API Key mismatch.\n";
        return;
    }
    
    echo "=== Sending Weekly Emergency Summary ===\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    
    $branches = $this->db->select('id, name, school_name, mobileno')
                          ->where('status', 1)
                          ->get('branch')
                          ->result_array();
    
    $summary_count = 0;
    
    foreach ($branches as $branch) {
        // Get last 7 days stats
        $sql = "SELECT 
                    COUNT(*) as total_week,
                    SUM(CASE WHEN status = 'reported' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low,
                    AVG(TIMESTAMPDIFF(HOUR, reported_at, resolved_at)) as avg_resolution_hours
                FROM hostel_emergency_incidents
                WHERE branch_id = ? 
                AND DATE(reported_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        
        $stats = $this->db->query($sql, array($branch['id']))->row_array();
        
        if ($stats && $stats['total_week'] > 0) {
            // Prepare summary message
            $message = "📊 WEEKLY EMERGENCY SUMMARY - {$branch['school_name']}\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📅 Last 7 days: {$stats['total_week']} incidents\n";
            $message .= "🟢 Resolved: {$stats['resolved']}\n";
            $message .= "🟡 Active: {$stats['active']}\n";
            $message .= "🔴 Critical: {$stats['critical']}\n";
            $message .= "🟠 High: {$stats['high']}\n";
            $message .= "🔵 Medium: {$stats['medium']}\n";
            $message .= "⚪ Low: {$stats['low']}\n";
            if ($stats['avg_resolution_hours']) {
                $message .= "⏱️ Avg resolution: " . round($stats['avg_resolution_hours']) . " hours\n";
            }
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "Login to system for details.";
            
            // Get admin mobile for this branch
            $admin = $this->db->select('s.mobileno')
                ->from('staff s')
                ->join('login_credential lc', 'lc.user_id = s.id')
                ->where('lc.role', 2)
                ->where('s.branch_id', $branch['id'])
                ->where('lc.active', 1)
                ->limit(1)
                ->get()
                ->row();
            
            if ($admin && !empty($admin->mobileno)) {
                $result = $this->send_summary_sms($admin->mobileno, $message, $branch['id']);
                
                if ($result) {
                    $summary_count++;
                    echo "  ✓ Summary sent to {$branch['school_name']}\n";
                } else {
                    echo "  ✗ Failed to send summary to {$branch['school_name']}\n";
                }
            }
            
            usleep(500000); // 0.5 second delay
        }
    }
    
    echo "\n=== Summary sent to {$summary_count} branches ===\n";
}

/**
 * Send summary SMS
 */
private function send_summary_sms($mobile, $message, $branch_id)
{
    if (empty($mobile) || empty($message)) {
        return false;
    }
    
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    
    if (strlen($mobile) < 9) {
        return false;
    }
    
    // Load SMS library
    $this->load->library('bulksmsbd', ['branch_id' => $branch_id], 'summary_sms_lib');
    
    try {
        $response = $this->summary_sms_lib->send($mobile, $message);
        
        // Check if successful
        return $this->is_successful_response($response);
        
    } catch (Exception $e) {
        error_log("Summary SMS Exception: " . $e->getMessage());
        return false;
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

// added after 
/**
 * Send follow-up reminders for unresolved incidents (run daily)
 * 
 * Usage: wget -q -O- "http://yourdomain.com/cron_api/emergency_followups/YOUR_API_KEY"
 */
public function emergency_followups($api_key = '')
{
    if ($api_key != "" && $this->api_key != $api_key) {
        echo "API Key mismatch.\n";
        return;
    }
    
    echo "=== Sending Emergency Incident Follow-ups ===\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    
    $this->load->model('hostel_emergency_model');
    $this->load->model('sendsmsmail_model');
    $this->load->library('bulksmsbd', ['branch_id' => 1], 'followup_sms_lib');
    
    // Get unresolved incidents that need follow-up
    $sql = "SELECT hei.*, het.name as emergency_type, 
                   DATEDIFF(NOW(), hei.reported_at) as days_unresolved
            FROM hostel_emergency_incidents hei
            JOIN hostel_emergency_types het ON het.id = hei.emergency_type_id
            WHERE hei.status IN ('reported', 'investigating')
            AND hei.resolved_at IS NULL
            AND hei.follow_up_required = 1
            AND DATEDIFF(NOW(), hei.reported_at) >= 1
            AND (hei.reminder_count < 3 OR hei.reminder_count IS NULL)
            ORDER BY hei.reported_at ASC";
    
    $incidents = $this->db->query($sql)->result_array();
    
    if (empty($incidents)) {
        echo "No incidents require follow-up.\n";
        return;
    }
    
    echo "Found " . count($incidents) . " incidents needing follow-up.\n";
    
    $sent_count = 0;
    $failed_count = 0;
    
    foreach ($incidents as $incident) {
        $days = $incident['days_unresolved'];
        
        // Determine message based on days unresolved
        $message = $this->_get_followup_message($incident, $days);
        
        // Get recipients (assigned staff and reporter)
        $recipients = $this->_get_followup_recipients($incident);
        
        if (empty($recipients)) {
            echo "  No recipients for incident {$incident['incident_code']}\n";
            continue;
        }
        
        // Calculate credits
        $credits_needed = $this->_calculate_followup_credits($message, count($recipients));
        
        // Check credits
        $current_credits = $this->sendsmsmail_model->get_sms_credit($incident['branch_id']);
        
        if ($current_credits < $credits_needed) {
            echo "  Insufficient credits for incident {$incident['incident_code']}\n";
            continue;
        }
        
        // Get SMS gateway
        $sms_api = $this->application_model->smsServiceProvider($incident['branch_id']);
        
        if ($sms_api == 'disabled') {
            echo "  SMS gateway disabled for branch {$incident['branch_id']}\n";
            continue;
        }
        
        // Send to recipients
        $incident_sent = 0;
        foreach ($recipients as $recipient) {
            $mobile = preg_replace('/[^0-9]/', '', $recipient['phone']);
            $personalized_msg = str_replace('{staff_name}', $recipient['name'], $message);
            
            $response = $this->_send_followup_sms($sms_api, $mobile, $personalized_msg, $incident['branch_id']);
            
            if ($response) {
                $incident_sent++;
                $sent_count++;
            } else {
                $failed_count++;
            }
            
            usleep(100000);
        }
        
        // Update reminder count
        if ($incident_sent > 0) {
            $this->db->where('id', $incident['id']);
            $this->db->set('reminder_count', 'reminder_count + 1', FALSE);
            $this->db->update('hostel_emergency_incidents');
            
            // Deduct credits
            $credits_to_deduct = ceil(($credits_needed * $incident_sent) / count($recipients));
            $this->sendsmsmail_model->deduct_sms_units($incident['branch_id'], $credits_to_deduct);
            
            // Log follow-up
            $this->_log_followup_sms($incident['id'], $incident['branch_id'], $incident_sent, $message);
            
            echo "  ✓ Follow-up sent for {$incident['incident_code']} (Day {$days})\n";
        }
    }
    
    echo "\n=== Summary: {$sent_count} sent, {$failed_count} failed ===\n";
}

/**
 * Get follow-up message based on days unresolved
 */
private function _get_followup_message($incident, $days)
{
    $school = $this->db->select('name, mobileno')->where('id', $incident['branch_id'])->get('branch')->row();
    
    if ($days == 1) {
        $message = "📋 FOLLOW-UP REMINDER\n";
        $message .= "Incident: {$incident['incident_code']}\n";
        $message .= "Type: {$incident['emergency_type']}\n";
        $message .= "Status: Still under review\n";
        $message .= "Please update the incident status.\n";
        $message .= "Login to system: " . base_url();
    } elseif ($days == 3) {
        $message = "⚠️ ESCALATION REMINDER\n";
        $message .= "Incident: {$incident['incident_code']} is unresolved for 3 days.\n";
        $message .= "Please take necessary action.\n";
        $message .= "Contact: " . ($school->mobileno ?? 'School Office');
    } else {
        $message = "🚨 URGENT FOLLOW-UP\n";
        $message .= "Incident: {$incident['incident_code']} - {$days} days unresolved.\n";
        $message .= "Please resolve immediately or escalate.\n";
        $message .= "Contact: " . ($school->mobileno ?? 'School Office');
    }
    
    return $message;
}

/**
 * Get recipients for follow-up (assigned staff + reporter)
 */
private function _get_followup_recipients($incident)
{
    $recipients = array();
    $ci = &get_instance();
    
    // Get assigned staff
    if (!empty($incident['room_id'])) {
        $staff = $ci->hostel_emergency_model->get_room_assigned_staff($incident['room_id']);
        foreach ($staff as $s) {
            if (!empty($s['mobileno'])) {
                $recipients[$s['mobileno']] = array(
                    'name' => $s['staff_name'],
                    'phone' => $s['mobileno'],
                    'type' => 'staff'
                );
            }
        }
    }
    
    // Get reporter
    $reporter = $ci->db->select('name, mobileno')->where('id', $incident['reported_by'])->get('staff')->row();
    if ($reporter && !empty($reporter->mobileno) && !isset($recipients[$reporter->mobileno])) {
        $recipients[$reporter->mobileno] = array(
            'name' => $reporter->name,
            'phone' => $reporter->mobileno,
            'type' => 'reporter'
        );
    }
    
    return array_values($recipients);
}

/**
 * Calculate follow-up credits
 */
private function _calculate_followup_credits($message, $recipient_count)
{
    $message_length = mb_strlen($message, 'UTF-8');
    $is_unicode = preg_match('/[^\x00-\x7F]/', $message);
    $chars_per_sms = $is_unicode ? 70 : 160;
    $sms_parts = ceil($message_length / $chars_per_sms);
    $credits_per_part = $is_unicode ? 2 : 1;
    
    return $sms_parts * $recipient_count * $credits_per_part;
}

/**
 * Send follow-up SMS
 */
private function _send_followup_sms($sms_api, $mobile, $message, $branch_id)
{
    if (empty($mobile)) return false;
    
    try {
        $this->load->library('bulksmsbd', ['branch_id' => $branch_id], 'followup_lib');
        $response = $this->followup_lib->send($mobile, $message);
        return $this->_is_successful_response($response);
    } catch (Exception $e) {
        error_log("Follow-up SMS Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Log follow-up SMS
 */
private function _log_followup_sms($incident_id, $branch_id, $sent_count, $message)
{
    $log_data = array(
        'incident_id' => $incident_id,
        'recipient_name' => 'Follow-up',
        'recipient_phone' => "Sent: {$sent_count}",
        'recipient_type' => 'followup',
        'message_sent' => substr($message, 0, 500),
        'status' => $sent_count > 0 ? 'sent' : 'failed',
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $branch_id,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->insert('hostel_emergency_sms_logs', $log_data);
}
    // ========== HOMEWORK CRON METHODS ==========
    
    /**
     * Send homework due tomorrow reminders
     * Run daily at 6 PM
     * 
     * Usage in crontab:
     * 0 18 * * * wget -q -O- "https://school.studportal.co.ke/cron_api/homework_due_tomorrow/YOUR_API_KEY"
     */
    public function homework_due_tomorrow($api_key = '')
    {
        if ($api_key != "" && $this->api_key != $api_key) {
            log_message('error', 'Homework Due Tomorrow Cron: API Key mismatch');
            echo "API Key mismatch.\n";
            return;
        }
        
        echo "=== Sending Homework Due Tomorrow Reminders ===\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // Get homework due tomorrow
        $homeworks = $this->db->select('h.*, s.name as subject_name, c.name as class_name, sec.name as section_name')
                              ->from('homework h')
                              ->join('subject s', 's.id = h.subject_id')
                              ->join('class c', 'c.id = h.class_id')
                              ->join('section sec', 'sec.id = h.section_id')
                              ->where('h.date_of_submission', $tomorrow)
                              ->where('h.assignment_status !=', 'closed')
                              ->where('h.branch_id >', 0)
                              ->get()
                              ->result_array();
        
        if (empty($homeworks)) {
            echo "No homework due tomorrow.\n";
            return;
        }
        
        echo "Found " . count($homeworks) . " homework assignments due tomorrow.\n";
        
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($homeworks as $homework) {
            // Get students in this class/section
            $students = $this->application_model->getStudentListByClassSection(
                $homework['class_id'],
                $homework['section_id'],
                $homework['branch_id']
            );
            
            if (empty($students)) {
                echo "  No students for homework ID {$homework['id']}\n";
                continue;
            }
            
            // Get SMS template
            $template = $this->db->get_where('sms_template_details', [
                'template_id' => 6,
                'branch_id' => $homework['branch_id']
            ])->row_array();
            
            if (empty($template) || empty($template['template_body'])) {
                echo "  No SMS template for branch {$homework['branch_id']}\n";
                continue;
            }
            
            // Prepare message
            $message = "⏰ REMINDER: Homework due tomorrow!\n\n";
            $message .= "Subject: {$homework['subject_name']}\n";
            $message .= "Due Date: " . date('d M Y', strtotime($tomorrow)) . "\n";
            $message .= "Please complete and submit on time.\n\n";
            $message .= $template['template_body'];
            $message = str_replace('{subject}', $homework['subject_name'], $message);
            $message = str_replace('{date_of_submission}', date('d M Y', strtotime($tomorrow)), $message);
            
            // Prepare recipients (students who haven't submitted)
            $recipients = [];
            foreach ($students as $student) {
                // Check if already submitted
                $submission = $this->homework_model->getStudentSubmission($homework['id'], $student['student_id']);
                
                if (!$submission || in_array($submission->submission_status, ['returned', 'draft'])) {
                    // Student
                    if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
                        $recipients[] = [
                            'name' => $student['fullname'],
                            'mobileno' => $student['mobileno'],
                            'type' => 'student'
                        ];
                    }
                    
                    // Parent
                    if ($template['notify_parent'] == 1 && !empty($student['parent_id'])) {
                        $parent = $this->db->select('name, mobileno')
                                          ->where('id', $student['parent_id'])
                                          ->get('parent')
                                          ->row();
                        if ($parent && !empty($parent->mobileno)) {
                            $recipients[] = [
                                'name' => $parent->name,
                                'mobileno' => $parent->mobileno,
                                'type' => 'parent'
                            ];
                        }
                    }
                }
            }
            
            if (empty($recipients)) {
                echo "  No pending recipients for homework ID {$homework['id']}\n";
                continue;
            }
            
            // Check credits
            $credits_needed = $this->calculate_sms_cost_cron($message, count($recipients));
            $current_credits = $this->sendsmsmail_model->get_sms_credit($homework['branch_id']);
            
            if ($current_credits < $credits_needed) {
                echo "  Insufficient credits for homework {$homework['id']}. Needed: {$credits_needed}, Available: {$current_credits}\n";
                $failed_count++;
                continue;
            }
            
            // Send SMS
            $this->load->library('bulksmsbd', ['branch_id' => $homework['branch_id']], 'cron_sms_lib');
            $success_recipients = 0;
            
            foreach ($recipients as $recipient) {
                $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
                $personalized_msg = str_replace('{name}', $recipient['name'], $message);
                
                $response = $this->cron_sms_lib->send($mobile, $personalized_msg);
                
                if ($this->is_successful_response_cron($response)) {
                    $success_recipients++;
                    
                    // Log delivery
                    $this->db->insert('sms_email_delivery_logs', [
                        'message_id' => 0,
                        'recipient_contact' => $mobile,
                        'status' => 'sent',
                        'gateway_response' => substr($response, 0, 500),
                        'sent_at' => date('Y-m-d H:i:s'),
                        'branch_id' => $homework['branch_id']
                    ]);
                    
                    // Log to homework_sms_logs
                    $this->db->insert('homework_sms_logs', [
                        'homework_id' => $homework['id'],
                        'recipient_contact' => $mobile,
                        'recipient_name' => $recipient['name'],
                        'recipient_type' => $recipient['type'],
                        'status' => 'sent',
                        'sent_at' => date('Y-m-d H:i:s'),
                        'branch_id' => $homework['branch_id']
                    ]);
                } else {
                    log_message('error', "Homework due tomorrow SMS failed to {$mobile}: " . substr($response, 0, 200));
                }
                
                usleep(200000); // 0.2 second delay
            }
            
            if ($success_recipients > 0) {
                $sent_count++;
                $credits_used = ceil(($credits_needed * $success_recipients) / count($recipients));
                $this->sendsmsmail_model->deduct_sms_units($homework['branch_id'], $credits_used);
                echo "  ✓ Sent reminders for homework ID {$homework['id']} to {$success_recipients} recipients\n";
            } else {
                $failed_count++;
                echo "  ✗ Failed to send reminders for homework ID {$homework['id']}\n";
            }
        }
        
        echo "\n=== Summary: {$sent_count} homework reminders sent, {$failed_count} failed ===\n";
        log_message('info', "Homework due tomorrow reminders: {$sent_count} sent, {$failed_count} failed");
    }
    
    /**
     * Send homework overdue alerts
     * Run daily at 8 AM
     * 
     * Usage in crontab:
     * 0 8 * * * wget -q -O- "https://school.studportal.co.ke/cron_api/homework_overdue/YOUR_API_KEY"
     */
    public function homework_overdue($api_key = '')
    {
        if ($api_key != "" && $this->api_key != $api_key) {
            log_message('error', 'Homework Overdue Cron: API Key mismatch');
            echo "API Key mismatch.\n";
            return;
        }
        
        echo "=== Sending Homework Overdue Alerts ===\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $three_days_ago = date('Y-m-d', strtotime('-3 days'));
        
        // Get homework that became overdue yesterday (not already alerted)
        $homeworks = $this->db->select('h.*, s.name as subject_name, c.name as class_name, sec.name as section_name')
                              ->from('homework h')
                              ->join('subject s', 's.id = h.subject_id')
                              ->join('class c', 'c.id = h.class_id')
                              ->join('section sec', 'sec.id = h.section_id')
                              ->where('h.date_of_submission', $yesterday)
                              ->where('h.assignment_status !=', 'closed')
                              ->where('h.branch_id >', 0)
                              ->get()
                              ->result_array();
        
        if (empty($homeworks)) {
            echo "No newly overdue homework.\n";
            return;
        }
        
        echo "Found " . count($homeworks) . " overdue homework assignments.\n";
        
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($homeworks as $homework) {
            // Get students in this class/section
            $students = $this->application_model->getStudentListByClassSection(
                $homework['class_id'],
                $homework['section_id'],
                $homework['branch_id']
            );
            
            if (empty($students)) {
                echo "  No students for homework ID {$homework['id']}\n";
                continue;
            }
            
            // Get SMS template
            $template = $this->db->get_where('sms_template_details', [
                'template_id' => 6,
                'branch_id' => $homework['branch_id']
            ])->row_array();
            
            if (empty($template) || empty($template['template_body'])) {
                echo "  No SMS template for branch {$homework['branch_id']}\n";
                continue;
            }
            
            // Prepare message
            $message = "⚠️ HOMEWORK OVERDUE ⚠️\n\n";
            $message .= "Subject: {$homework['subject_name']}\n";
            $message .= "Was due: " . date('d M Y', strtotime($homework['date_of_submission'])) . "\n";
            $message .= "Please submit as soon as possible.\n\n";
            $message .= $template['template_body'];
            $message = str_replace('{subject}', $homework['subject_name'], $message);
            $message = str_replace('{date_of_submission}', date('d M Y', strtotime($homework['date_of_submission'])), $message);
            
            // Prepare recipients (students who haven't submitted)
            $recipients = [];
            foreach ($students as $student) {
                $submission = $this->homework_model->getStudentSubmission($homework['id'], $student['student_id']);
                
                if (!$submission || in_array($submission->submission_status, ['returned', 'draft'])) {
                    if ($template['notify_student'] == 1 && !empty($student['mobileno'])) {
                        $recipients[] = [
                            'name' => $student['fullname'],
                            'mobileno' => $student['mobileno'],
                            'type' => 'student'
                        ];
                    }
                    
                    if ($template['notify_parent'] == 1 && !empty($student['parent_id'])) {
                        $parent = $this->db->select('name, mobileno')
                                          ->where('id', $student['parent_id'])
                                          ->get('parent')
                                          ->row();
                        if ($parent && !empty($parent->mobileno)) {
                            $recipients[] = [
                                'name' => $parent->name,
                                'mobileno' => $parent->mobileno,
                                'type' => 'parent'
                            ];
                        }
                    }
                }
            }
            
            if (empty($recipients)) {
                echo "  No pending recipients for homework ID {$homework['id']}\n";
                continue;
            }
            
            // Check credits
            $credits_needed = $this->calculate_sms_cost_cron($message, count($recipients));
            $current_credits = $this->sendsmsmail_model->get_sms_credit($homework['branch_id']);
            
            if ($current_credits < $credits_needed) {
                echo "  Insufficient credits for homework {$homework['id']}. Needed: {$credits_needed}, Available: {$current_credits}\n";
                $failed_count++;
                continue;
            }
            
            // Send SMS
            $this->load->library('bulksmsbd', ['branch_id' => $homework['branch_id']], 'cron_sms_lib');
            $success_recipients = 0;
            
            foreach ($recipients as $recipient) {
                $mobile = preg_replace('/[^0-9]/', '', $recipient['mobileno']);
                $personalized_msg = str_replace('{name}', $recipient['name'], $message);
                
                $response = $this->cron_sms_lib->send($mobile, $personalized_msg);
                
                if ($this->is_successful_response_cron($response)) {
                    $success_recipients++;
                    
                    $this->db->insert('homework_sms_logs', [
                        'homework_id' => $homework['id'],
                        'recipient_contact' => $mobile,
                        'recipient_name' => $recipient['name'],
                        'recipient_type' => $recipient['type'],
                        'status' => 'sent',
                        'sent_at' => date('Y-m-d H:i:s'),
                        'branch_id' => $homework['branch_id']
                    ]);
                }
                
                usleep(200000);
            }
            
            if ($success_recipients > 0) {
                $sent_count++;
                $credits_used = ceil(($credits_needed * $success_recipients) / count($recipients));
                $this->sendsmsmail_model->deduct_sms_units($homework['branch_id'], $credits_used);
                echo "  ✓ Sent overdue alerts for homework ID {$homework['id']} to {$success_recipients} recipients\n";
                
                // Mark submissions as late
                $this->db->where('homework_id', $homework['id']);
                $this->db->where('submission_status', 'submitted');
                $this->db->update('homework_submit', ['submission_status' => 'late']);
            } else {
                $failed_count++;
                echo "  ✗ Failed to send overdue alerts for homework ID {$homework['id']}\n";
            }
        }
        
        echo "\n=== Summary: {$sent_count} overdue alerts sent, {$failed_count} failed ===\n";
        log_message('info', "Homework overdue alerts: {$sent_count} sent, {$failed_count} failed");
    }
    
    /**
     * Send weekly homework summary to parents
     * Run every Monday at 7 AM
     * 
     * Usage in crontab:
     * 0 7 * * 1 wget -q -O- "https://school.studportal.co.ke/cron_api/homework_weekly_summary/YOUR_API_KEY"
     */
    public function homework_weekly_summary($api_key = '')
    {
        if ($api_key != "" && $this->api_key != $api_key) {
            log_message('error', 'Homework Weekly Summary Cron: API Key mismatch');
            echo "API Key mismatch.\n";
            return;
        }
        
        echo "=== Sending Weekly Homework Summary to Parents ===\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $today = date('Y-m-d');
        
        // Get all branches
        $branches = $this->db->select('id, name')->get('branch')->result_array();
        $total_sent = 0;
        
        foreach ($branches as $branch) {
            // Get homework from last week for this branch
            $homeworks = $this->db->select('h.*, s.name as subject_name')
                                  ->from('homework h')
                                  ->join('subject s', 's.id = h.subject_id')
                                  ->where('h.branch_id', $branch['id'])
                                  ->where('h.date_of_submission >=', $week_ago)
                                  ->where('h.date_of_submission <=', $today)
                                  ->get()
                                  ->result_array();
            
            if (empty($homeworks)) {
                echo "  No homework last week for branch {$branch['name']}\n";
                continue;
            }
            
            // Get all students with parents
            $students = $this->db->select('s.id, s.first_name, s.last_name, s.register_no, p.id as parent_id, p.name as parent_name, p.mobileno as parent_mobile')
                                 ->from('student s')
                                 ->join('parent p', 'p.id = s.parent_id', 'left')
                                 ->where('s.branch_id', $branch['id'])
                                 ->where('p.mobileno IS NOT NULL')
                                 ->where('p.mobileno !=', '')
                                 ->get()
                                 ->result_array();
            
            foreach ($students as $student) {
                $pending_homeworks = [];
                $completed_homeworks = [];
                $missed_homeworks = [];
                
                foreach ($homeworks as $homework) {
                    $submission = $this->homework_model->getStudentSubmission($homework['id'], $student['id']);
                    
                    if ($submission && $submission->submission_status == 'submitted') {
                        $completed_homeworks[] = $homework['subject_name'];
                    } elseif ($submission && $submission->submission_status == 'late') {
                        $pending_homeworks[] = $homework['subject_name'];
                    } else {
                        $missed_homeworks[] = $homework['subject_name'];
                    }
                }
                
                // Only send if there are pending or missed homework
                if (empty($pending_homeworks) && empty($missed_homeworks)) {
                    continue;
                }
                
                // Prepare summary message
                $message = "📚 WEEKLY HOMEWORK SUMMARY\n";
                $message .= "Student: {$student['first_name']} {$student['last_name']}\n";
                $message .= "━━━━━━━━━━━━━━━━━━━━\n";
                
                if (!empty($completed_homeworks)) {
                    $message .= "✅ Completed: " . count($completed_homeworks) . "\n";
                }
                if (!empty($pending_homeworks)) {
                    $message .= "⏳ Pending: " . count($pending_homeworks) . " - " . implode(", ", array_slice($pending_homeworks, 0, 3)) . "\n";
                }
                if (!empty($missed_homeworks)) {
                    $message .= "❌ Missing: " . count($missed_homeworks) . " - " . implode(", ", array_slice($missed_homeworks, 0, 3)) . "\n";
                }
                $message .= "━━━━━━━━━━━━━━━━━━━━\n";
                $message .= "Login to the portal for details.";
                
                // Check credits
                $credits_needed = $this->calculate_sms_cost_cron($message, 1);
                $current_credits = $this->sendsmsmail_model->get_sms_credit($branch['id']);
                
                if ($current_credits < $credits_needed) {
                    echo "    Insufficient credits for student {$student['id']}\n";
                    continue;
                }
                
                // Send to parent
                if (!empty($student['parent_mobile'])) {
                    $mobile = preg_replace('/[^0-9]/', '', $student['parent_mobile']);
                    $personalized_msg = str_replace('{student_name}', $student['first_name'] . ' ' . $student['last_name'], $message);
                    
                    $this->load->library('bulksmsbd', ['branch_id' => $branch['id']], 'summary_sms_lib');
                    $response = $this->summary_sms_lib->send($mobile, $personalized_msg);
                    
                    if ($this->is_successful_response_cron($response)) {
                        $total_sent++;
                        $this->sendsmsmail_model->deduct_sms_units($branch['id'], $credits_needed);
                        echo "  ✓ Summary sent to parent of {$student['first_name']} {$student['last_name']}\n";
                    }
                    
                    usleep(300000);
                }
            }
        }
        
        echo "\n=== Summary: {$total_sent} weekly summaries sent ===\n";
        log_message('info', "Homework weekly summaries: {$total_sent} sent");
    }
    
    /**
     * Helper: Check if SMS response is successful
     */
    private function is_successful_response_cron($response)
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
     * Calculate all teacher workloads (cron job)
     */
    public function calculate_teacher_workload() {
        $this->load->model('employee_model');
        
        $session_id = get_session_id();
        $term_id = $this->employee_model->get_current_term_id();
        
        $teachers = $this->employee_model->getStaffList(null, 3, 1);
        $count = 0;
        
        foreach ($teachers as $teacher) {
            $this->employee_model->calculate_teacher_workload($teacher->id, $session_id, $term_id);
            $count++;
        }
        
        log_message('info', "TIS Workload Calculation: $count teachers processed");
        echo "Workload calculation completed for $count teachers.\n";
    }
    
    /**
     * Calculate all teacher performances (cron job - weekly)
     */
    public function calculate_teacher_performance() {
        $this->load->model('employee_model');
        
        $session_id = get_session_id();
        $term_id = $this->employee_model->get_current_term_id();
        
        $teachers = $this->employee_model->getStaffList(null, 3, 1);
        $count = 0;
        
        foreach ($teachers as $teacher) {
            $this->employee_model->calculate_teacher_performance($teacher->id, $session_id, $term_id);
            $count++;
        }
        
        log_message('info', "TIS Performance Calculation: $count teachers processed");
        echo "Performance calculation completed for $count teachers.\n";
    }

    public function stars_weekly_progress()
{
    if ($this->input->get('key') != $this->config->item('cron_secret_key')) {
        die('Invalid key');
    }
    
    $this->load->model('stars_model');
    
    // Get all active IARPs
    $this->db->where('status', 'active');
    $iarps = $this->db->get('iarp_plans')->result_array();
    
    foreach ($iarps as $iarp) {
        // Calculate current week number
        $start = new DateTime($iarp['start_date']);
        $now = new DateTime();
        $week_number = floor($start->diff($now)->days / 7) + 1;
        
        // Trigger SMS
        $this->stars_model->update_weekly_progress(
            $iarp['id'], 
            $iarp['student_id'], 
            $week_number, 
            0, 
            'Weekly auto-check'
        );
    }
    
    echo "STARS weekly progress completed. Processed " . count($iarps) . " active plans.";
}
}