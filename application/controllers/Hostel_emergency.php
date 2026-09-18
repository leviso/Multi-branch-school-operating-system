<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Hostel_emergency.php
 * @copyright : Reserved Synobix Team
 */

class Hostel_emergency extends Admin_Controller
{
    public function __construct()
{
    parent::__construct();
    $this->load->model('hostel_emergency_model');
    $this->load->model('sms_model');
    $this->load->model('sendsmsmail_model');
    
    // Get current user role
    $this->user_role = $this->session->userdata('role_id');
    $this->user_id = get_loggedin_user_id();
    
    // Branch isolation
    if ($this->user_role != 1) {
        $this->branch_id = $this->session->userdata('branch_id');
    } else {
        $this->branch_id = $this->input->get('branch_id') ?? $this->session->userdata('branch_id');
    }
}

    /* emergency incident form validation rules */
    protected function incident_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('emergency_type_id', translate('emergency_type'), 'required');
        $this->form_validation->set_rules('title', translate('title'), 'trim|required');
        $this->form_validation->set_rules('description', translate('description'), 'trim|required');
        $this->form_validation->set_rules('severity', translate('severity'), 'required');
    }

    /* emergency type form validation rules */
    protected function type_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('name', translate('name'), 'trim|required|callback_unique_type');
        $this->form_validation->set_rules('priority', translate('priority'), 'required');
    }

    /* emergency contact form validation rules */
    protected function contact_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('contact_name', translate('contact_name'), 'trim|required');
        $this->form_validation->set_rules('phone_number', translate('phone_number'), 'trim|required');
        $this->form_validation->set_rules('priority_order', translate('priority_order'), 'numeric');
    }

    /* student medical form validation rules */
    protected function medical_validation()
    {
        $this->form_validation->set_rules('blood_group', translate('blood_group'), 'required');
    }

    // ============================================
    // EMERGENCY INCIDENTS
    // ============================================

    public function incidents()
{
    if (!get_permission('hostel_emergency', 'is_view')) {
        access_denied();
    }

    $branchID = $this->application_model->get_branch_id();

    // Superadmin branch filtering
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->input->get('branch_id');
        if (!empty($selected_branch)) {
            $this->session->set_userdata('selected_emergency_branch', $selected_branch);
            $branchID = $selected_branch;
        } else {
            $branchID = $this->session->userdata('selected_emergency_branch');
            if (empty($branchID)) {
                $first_branch = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('branch')->row();
                $branchID = $first_branch ? $first_branch->id : 1;
            }
        }
        
        // Get full branch details for dropdown
        $this->data['branches'] = $this->db->select('id, name, school_name')->get('branch')->result_array();
        $this->data['selected_branch_id'] = $branchID;
    }

    // Get filters
    $status = $this->input->get('status');
    $severity = $this->input->get('severity');

    $this->data['incidents'] = $this->hostel_emergency_model->get_incidents($branchID, $status, $severity);
    
    // Debug: Log incidents with branch info
    error_log("Incidents count: " . count($this->data['incidents']));
    foreach($this->data['incidents'] as $inc) {
        error_log("Incident {$inc['incident_code']} - Branch ID: " . ($inc['branch_id'] ?? 'NULL'));
    }
    
    $this->data['stats'] = $this->hostel_emergency_model->get_incident_stats($branchID);
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('emergency_incidents');
    $this->data['sub_page'] = 'hostel_emergency/incidents';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
 public function report()
{
    if (!get_permission('hostel_emergency', 'is_add')) {
        access_denied();
    }

    $branchID = $this->application_model->get_branch_id();
    
    if (is_superadmin_loggedin()) {
        $url_branch = $this->input->get('branch_id');
        if (!empty($url_branch)) {
            $branchID = $url_branch;
        }
        $this->data['branches'] = $this->db->select('id, name, school_name')->get('branch')->result_array();
        $this->data['selected_branch_id'] = $branchID;
    }

    // Handle form submission
    if ($this->input->server('REQUEST_METHOD') === 'POST') {
        
        $this->form_validation->set_rules('emergency_type_id', 'Emergency Type', 'required');
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->form_validation->set_rules('description', 'Description', 'required');
        $this->form_validation->set_rules('severity', 'Severity', 'required');

        if ($this->form_validation->run() == TRUE) {
            $post = $this->input->post();

            if (is_superadmin_loggedin() && !empty($post['branch_id'])) {
                $branchID = $post['branch_id'];
            }

            // Handle multiple students
            $student_ids = $this->input->post('student_ids');
            $student_id = !empty($student_ids) ? implode(',', $student_ids) : null;

            $incident_data = array(
                'incident_code' => 'EMG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'emergency_type_id' => $post['emergency_type_id'],
                'student_id' => $student_id,
                'room_id' => !empty($post['room_id']) ? $post['room_id'] : null,
                'hostel_id' => !empty($post['hostel_id']) ? $post['hostel_id'] : null,
                'title' => $post['title'],
                'description' => $post['description'],
                'reported_at' => date('Y-m-d H:i:s'),
                'severity' => $post['severity'],
                'location_details' => $post['location_details'] ?? null,
                'action_taken' => $post['action_taken'] ?? null,
                'estimated_duration' => $post['estimated_duration'] ?? null,
                'branch_id' => $branchID,
                'reported_by' => get_loggedin_user_id(),
                'status' => 'reported'
            );

            $this->db->insert('hostel_emergency_incidents', $incident_data);
            $incident_id = $this->db->insert_id();

            if ($incident_id) {
                // SMS disabled for now
                $this->session->set_flashdata('msg', '<div class="alert alert-success">Incident reported successfully. Code: ' . $incident_data['incident_code'] . '</div>');
                redirect(base_url('hostel_emergency/incidents?branch_id=' . $branchID));
            } else {
                $this->session->set_flashdata('msg', '<div class="alert alert-danger">Failed to report incident.</div>');
                redirect(base_url('hostel_emergency/report?branch_id=' . $branchID));
            }
        } else {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . validation_errors() . '</div>');
            redirect(base_url('hostel_emergency/report?branch_id=' . $branchID));
        }
    }

    // Get data for dropdowns
    $this->data['emergency_types'] = $this->hostel_emergency_model->get_emergency_types($branchID);
    $this->data['hostels'] = $this->db->get_where('hostel', array('branch_id' => $branchID))->result_array();
    $this->data['rooms'] = $this->db->get_where('hostel_room', array('branch_id' => $branchID))->result_array();
    $this->data['students'] = $this->hostel_emergency_model->get_hostel_students($branchID);
    $this->data['branch_id'] = $branchID;
    
    $this->data['title'] = translate('report_emergency');
    $this->data['sub_page'] = 'hostel_emergency/report';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
public function add_incident($data)
{
    // Debug: Log the data being inserted
    error_log("add_incident - Data: " . print_r($data, true));
    
    $this->db->insert('hostel_emergency_incidents', $data);
    $insert_id = $this->db->insert_id();
    
    error_log("add_incident - Insert ID: " . $insert_id);
    
    return $insert_id;
}
   public function update_status()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!get_permission('hostel_emergency', 'is_edit')) {
        echo json_encode(array('status' => 'fail', 'message' => 'Access denied'));
        exit();
    }
    
    $id = $this->input->post('id');
    $status = $this->input->post('status');
    $resolution_notes = $this->input->post('resolution_notes');
    
    // DEBUG: Log what we received
    error_log("=== UPDATE STATUS DEBUG ===");
    error_log("Received status value: '" . $status . "'");
    error_log("Status length: " . strlen($status));
    error_log("Status trim: '" . trim($status) . "'");
    
    // Normalize status - trim whitespace
    $status = trim($status);
    
    $update_data = array(
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    // Check for resolution - use exact string match
    $is_resolved = ($status == 'resolved' || $status == 'closed');
    error_log("Is resolved? " . ($is_resolved ? 'YES' : 'NO'));
    
    if ($is_resolved) {
        $update_data['resolved_at'] = date('Y-m-d H:i:s');
        $update_data['resolved_by'] = get_loggedin_user_id();
        $update_data['action_taken'] = $resolution_notes;
        error_log("Adding resolution data - Resolved by: " . get_loggedin_user_id());
    }
    
    $result = $this->hostel_emergency_model->update_incident($id, $update_data);
    error_log("Database update result: " . ($result ? 'SUCCESS' : 'FAILED'));
    
    if ($result) {
        // Send resolution SMS ONLY if status is resolved or closed
        if ($is_resolved) {
            error_log("Attempting to send resolution SMS...");
            
            // Get resolved by name
            $resolved_by = $this->db->select('name')->where('id', get_loggedin_user_id())->get('staff')->row();
            $resolved_by_name = $resolved_by ? $resolved_by->name : 'System';
            
            $sms_data = array(
                'incident_id' => $id,
                'branch_id' => $this->branch_id,
                'resolution_notes' => $resolution_notes,
                'resolved_by_name' => $resolved_by_name
            );
            
            // Check if method exists
            if (method_exists($this->sms_model, 'sendResolutionNotification')) {
                $sms_result = $this->sms_model->sendResolutionNotification($sms_data);
                error_log("Resolution SMS Result: " . print_r($sms_result, true));
                
                if ($sms_result['success']) {
                    echo json_encode(array('status' => 'success', 'message' => 'Status updated and resolution SMS sent'));
                } else {
                    echo json_encode(array('status' => 'success', 'message' => 'Status updated but SMS failed: ' . $sms_result['message']));
                }
            } else {
                error_log("sendResolutionNotification method not found!");
                echo json_encode(array('status' => 'success', 'message' => 'Status updated but SMS method not available'));
            }
        } else {
            echo json_encode(array('status' => 'success', 'message' => 'Status updated successfully'));
        }
    } else {
        error_log("Failed to update incident");
        echo json_encode(array('status' => 'fail', 'message' => 'Failed to update status'));
    }
    exit();
}
/**
 * Send SMS notification when incident is resolved
 */
private function send_resolution_sms($incident_id, $resolution_notes)
{
    $incident = $this->hostel_emergency_model->get_incident($incident_id);
    
    if (empty($incident)) {
        error_log("Resolution SMS: Incident not found - ID: " . $incident_id);
        return false;
    }
    
    $emergency_type = $this->hostel_emergency_model->get_emergency_type($incident['emergency_type_id']);
    
    // Get recipients (parents, staff, admins)
    $recipients = $this->get_emergency_recipients($incident);
    
    if (empty($recipients)) {
        error_log("Resolution SMS: No recipients found for incident - ID: " . $incident_id);
        return false;
    }
    
    // Get resolved by staff name
    $resolved_by = $this->db->select('name')->where('id', $incident['resolved_by'])->get('staff')->row();
    $resolved_by_name = $resolved_by ? $resolved_by->name : 'System Administrator';
    
    // Prepare resolution message
    $message = "✅ INCIDENT RESOLVED ✅\n";
    $message .= "Incident: {$incident['incident_code']}\n";
    $message .= "Type: {$emergency_type['name']}\n";
    $message .= "Status: RESOLVED\n";
    $message .= "Resolution: {$resolution_notes}\n";
    $message .= "Resolved by: {$resolved_by_name}\n";
    $message .= "Resolved on: " . date('d M Y H:i') . "\n";
    $message .= "Thank you for your cooperation.\n";
    $message .= "School: " . $this->get_school_phone($incident['branch_id']);
    
    $sent_count = 0;
    $failed_count = 0;
    
    foreach ($recipients as $recipient) {
        $sms_data = array(
            'branch_id' => $incident['branch_id'],
            'recipient_mobile' => $recipient['phone'],
            'message' => $message,
            'incident_id' => $incident_id,
            'recipient_name' => $recipient['name'],
            'recipient_type' => $recipient['type']
        );
        
        try {
            $result = $this->sms_model->sendEmergencySms($sms_data);
            
            if ($result['success']) {
                $sent_count++;
                error_log("Resolution SMS sent to: " . $recipient['phone']);
            } else {
                $failed_count++;
                error_log("Resolution SMS failed to: " . $recipient['phone'] . " - " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $failed_count++;
            error_log("Resolution SMS exception: " . $e->getMessage());
        }
        
        usleep(100000); // 0.1 second delay
    }
    
    // Log resolution SMS
    $this->log_resolution_sms($incident_id, $sent_count, $failed_count);
    
    return array('sent' => $sent_count, 'failed' => $failed_count);
}

/**
 * Log resolution SMS to database
 */
private function log_resolution_sms($incident_id, $sent_count, $failed_count)
{
    $log_data = array(
        'incident_id' => $incident_id,
        'message_sent' => "Resolution SMS sent to {$sent_count} recipients. Failed: {$failed_count}",
        'recipient_type' => 'system',
        'status' => $sent_count > 0 ? 'sent' : 'failed',
        'sent_at' => date('Y-m-d H:i:s'),
        'branch_id' => $this->branch_id ?? 1,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->insert('hostel_emergency_sms_logs', $log_data);
}

/**
 * Get school phone number
 */
private function get_school_phone($branch_id)
{
    $branch = $this->db->select('mobileno')->where('id', $branch_id)->get('branch')->row();
    return $branch ? $branch->mobileno : 'Contact school office';
}

    public function delete($id)
    {
        if (!get_permission('hostel_emergency', 'can_delete')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();

        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', $branchID);
        }
        $this->db->where('id', $id);
        $this->db->delete('hostel_emergency_incidents');

        set_alert('success', translate('information_has_been_deleted_successfully'));
        redirect(base_url('hostel_emergency/incidents'));
    }

    // ============================================
    // EMERGENCY TYPES MANAGEMENT
    // ============================================

    public function types()
    {
        if (!get_permission('emergency_types', 'is_view')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();

        if (is_superadmin_loggedin()) {
            $selected_branch = $this->input->get('branch_id');
            if (!empty($selected_branch)) {
                $branchID = $selected_branch;
            }
            $this->data['branches'] = $this->db->select('id, name')->get('branch')->result_array();
            $this->data['selected_branch_id'] = $branchID;
        }

        $this->data['types'] = $this->hostel_emergency_model->get_emergency_types($branchID);
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('emergency_types');
        $this->data['sub_page'] = 'hostel_emergency/types';
        $this->data['main_menu'] = 'hostels';
        $this->load->view('layout/index', $this->data);
    }

    public function type_save()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }

        if (!get_permission('emergency_types', 'can_add') && !$this->input->post('type_id')) {
            ajax_access_denied();
        }

        $this->type_validation();

        if ($this->form_validation->run() !== false) {
            $post = $this->input->post();

            $branchID = $this->application_model->get_branch_id();
            if (is_superadmin_loggedin() && !empty($post['branch_id'])) {
                $branchID = $post['branch_id'];
            }

            $data = array(
                'name' => $post['name'],
                'icon' => $post['icon'] ?? 'fa-exclamation-triangle',
                'priority' => $post['priority'],
                'requires_immediate_sms' => isset($post['requires_immediate_sms']) ? 1 : 0,
                'sms_recipient_roles' => isset($post['sms_recipient_roles']) ? implode(',', $post['sms_recipient_roles']) : '',
                'is_active' => isset($post['is_active']) ? 1 : 1,
                'branch_id' => $branchID,
                'updated_at' => date('Y-m-d H:i:s')
            );

            if (empty($post['type_id'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->hostel_emergency_model->add_emergency_type($data);
                $message = translate('information_has_been_saved_successfully');
            } else {
                $this->hostel_emergency_model->update_emergency_type($post['type_id'], $data);
                $message = translate('information_has_been_updated_successfully');
            }

            $array = array('status' => 'success', 'message' => $message);
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'error' => $error);
        }

        echo json_encode($array);
        exit();
    }

    /**
 * Delete emergency type (AJAX)
 */
public function type_delete()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    if (!get_permission('emergency_types', 'can_delete')) {
        echo json_encode(array('status' => 'fail', 'message' => 'Access denied'));
        exit();
    }
    
    $id = $this->input->post('id');
    $branchID = $this->application_model->get_branch_id();
    
    // Check if type has incidents
    $this->db->where('emergency_type_id', $id);
    $incident_count = $this->db->count_all_results('hostel_emergency_incidents');
    
    if ($incident_count > 0) {
        echo json_encode(array('status' => 'fail', 'message' => 'Cannot delete: This type has ' . $incident_count . ' existing incidents'));
        exit();
    }
    
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $this->db->where('id', $id);
    $result = $this->db->delete('hostel_emergency_types');
    
    if ($result) {
        echo json_encode(array('status' => 'success', 'message' => 'Emergency type deleted successfully'));
    } else {
        echo json_encode(array('status' => 'fail', 'message' => 'Failed to delete'));
    }
    exit();
}


    public function unique_type($name)
    {
        $type_id = $this->input->post('type_id');
        $branchID = $this->application_model->get_branch_id();

        if (is_superadmin_loggedin() && !empty($this->input->post('branch_id'))) {
            $branchID = $this->input->post('branch_id');
        }

        if (!empty($type_id)) {
            $this->db->where_not_in('id', $type_id);
        }
        $this->db->where('name', $name);
        $this->db->where('branch_id', $branchID);
        $query = $this->db->get('hostel_emergency_types');

        if ($query->num_rows() > 0) {
            $this->form_validation->set_message("unique_type", translate('already_taken'));
            return false;
        } else {
            return true;
        }
    }

    // ============================================
    // EMERGENCY CONTACTS MANAGEMENT
    // ============================================

    public function contacts()
    {
        if (!get_permission('emergency_contacts', 'is_view')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();

        if (is_superadmin_loggedin()) {
            $selected_branch = $this->input->get('branch_id');
            if (!empty($selected_branch)) {
                $branchID = $selected_branch;
            }
            $this->data['branches'] = $this->db->select('id, name')->get('branch')->result_array();
            $this->data['selected_branch_id'] = $branchID;
        }

        $this->data['contacts'] = $this->hostel_emergency_model->get_emergency_contacts($branchID);
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('emergency_contacts');
        $this->data['sub_page'] = 'hostel_emergency/contacts';
        $this->data['main_menu'] = 'hostels';
        $this->load->view('layout/index', $this->data);
    }

    public function contact_save()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }

        if (!get_permission('emergency_contacts', 'can_add') && !$this->input->post('contact_id')) {
            ajax_access_denied();
        }

        $this->contact_validation();

        if ($this->form_validation->run() !== false) {
            $post = $this->input->post();

            $branchID = $this->application_model->get_branch_id();
            if (is_superadmin_loggedin() && !empty($post['branch_id'])) {
                $branchID = $post['branch_id'];
            }

            $data = array(
                'branch_id' => $branchID,
                'contact_name' => $post['contact_name'],
                'contact_role' => $post['contact_role'],
                'phone_number' => $post['phone_number'],
                'alternate_phone' => $post['alternate_phone'] ?? null,
                'email' => $post['email'] ?? null,
                'address' => $post['address'] ?? null,
                'priority_order' => $post['priority_order'] ?? 1,
                'for_student' => isset($post['for_student']) ? 1 : 0,
                'notes' => $post['notes'] ?? null,
                'is_active' => isset($post['is_active']) ? 1 : 1,
                'updated_at' => date('Y-m-d H:i:s')
            );

            if (empty($post['contact_id'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->hostel_emergency_model->add_emergency_contact($data);
                $message = translate('information_has_been_saved_successfully');
            } else {
                $this->hostel_emergency_model->update_emergency_contact($post['contact_id'], $data);
                $message = translate('information_has_been_updated_successfully');
            }

            $array = array('status' => 'success', 'message' => $message);
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'error' => $error);
        }

        echo json_encode($array);
        exit();
    }

    /**
 * Delete emergency contact (AJAX)
 */
public function contact_delete()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    if (!get_permission('emergency_contacts', 'can_delete')) {
        echo json_encode(array('status' => 'fail', 'message' => 'Access denied'));
        exit();
    }
    
    $id = $this->input->post('id');
    $branchID = $this->application_model->get_branch_id();
    
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $this->db->where('id', $id);
    $result = $this->db->delete('hostel_emergency_contacts');
    
    if ($result) {
        echo json_encode(array('status' => 'success', 'message' => 'Emergency contact deleted successfully'));
    } else {
        echo json_encode(array('status' => 'fail', 'message' => 'Failed to delete'));
    }
    exit();
}

    // ============================================
    // STUDENT MEDICAL INFORMATION
    // ============================================

    /**
 * Student medical information management - FIXED VERSION
 */
public function student_medical($student_id = null)
{
    // FORCE NO CACHE - MUST be before any output
    $this->output->set_header('HTTP/1.0 200 OK');
    $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
    $this->output->set_header('Pragma: no-cache');
    $this->output->set_header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    $this->output->set_header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    
    if (!get_permission('student_medical', 'is_view')) {
        access_denied();
    }

    $branchID = $this->application_model->get_branch_id();
    
    // For superadmin branch selection
    if (is_superadmin_loggedin()) {
        $url_branch = $this->input->get('branch_id');
        if (!empty($url_branch)) {
            $branchID = $url_branch;
            $this->session->set_userdata('selected_medical_branch', $branchID);
        } else {
            $session_branch = $this->session->userdata('selected_medical_branch');
            if (!empty($session_branch)) {
                $branchID = $session_branch;
            }
        }
        
        if (empty($branchID)) {
            $branchID = 13;
        }
        
        $this->data['branches'] = $this->db->select('id, name, school_name')->get('branch')->result_array();
        $this->data['selected_branch_id'] = $branchID;
    }

    error_log("=== STUDENT MEDICAL ===");
    error_log("URL Student ID: " . ($student_id ?? 'NULL'));
    error_log("Branch ID: " . $branchID);

    // Get all students for left panel
    $this->data['students'] = $this->hostel_emergency_model->get_hostel_students($branchID);
    $this->data['branch_id'] = $branchID;
    
    // ============================================
    // HANDLE FORM SUBMISSION
    // ============================================
    if ($_POST && isset($_POST['save_medical'])) {
        
        error_log("=== PROCESSING FORM SUBMISSION ===");
        
        // Get student_id from POST
        $post_student_id = $this->input->post('student_id');
        if (!empty($post_student_id)) {
            $student_id = $post_student_id;
        }
        
        if (empty($student_id)) {
            set_alert('error', 'Student ID is required');
            redirect(base_url('hostel_emergency/student_medical?branch_id=' . $branchID));
        }
        
        // NO CSRF VALIDATION - Let's skip it for now to make it work
        // We'll add it back later
        
        // Validate
        $this->form_validation->set_rules('blood_group', 'Blood Group', 'required');
        
        if ($this->form_validation->run() == TRUE) {
            $post = $this->input->post();

            $medical_data = array(
                'student_id' => $student_id,
                'blood_group' => $post['blood_group'],
                'allergies' => $post['allergies'] ?? null,
                'chronic_conditions' => $post['chronic_conditions'] ?? null,
                'medications' => $post['medications'] ?? null,
                'emergency_contact_name' => $post['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $post['emergency_contact_phone'] ?? null,
                'emergency_contact_relation' => $post['emergency_contact_relation'] ?? null,
                'insurance_provider' => $post['insurance_provider'] ?? null,
                'insurance_policy_no' => $post['insurance_policy_no'] ?? null,
                'doctor_name' => $post['doctor_name'] ?? null,
                'doctor_phone' => $post['doctor_phone'] ?? null,
                'doctor_address' => $post['doctor_address'] ?? null,
                'last_updated' => date('Y-m-d H:i:s'),
                'updated_by' => get_loggedin_user_id(),
                'branch_id' => $branchID
            );
            
            error_log("Medical data to save: " . print_r($medical_data, true));
            
            $existing = $this->hostel_emergency_model->get_student_medical($student_id);
            
            if ($existing) {
                $result = $this->hostel_emergency_model->update_student_medical($student_id, $medical_data);
                error_log("Updated medical record - Result: " . ($result ? 'Success' : 'Failed'));
            } else {
                $medical_data['created_at'] = date('Y-m-d H:i:s');
                $result = $this->hostel_emergency_model->add_student_medical($medical_data);
                error_log("Created medical record - Result: " . ($result ? 'Success' : 'Failed'));
            }
            
            if ($result) {
                $this->session->set_flashdata('success_msg', translate('information_has_been_saved_successfully'));
            } else {
                $this->session->set_flashdata('error_msg', 'Failed to save medical information');
            }
        } else {
            $errors = $this->form_validation->error_array();
            error_log("Validation errors: " . print_r($errors, true));
            $this->session->set_flashdata('error_msg', implode('<br>', $errors));
        }
        
        // Redirect to the same student after save
        redirect(base_url('hostel_emergency/student_medical/' . $student_id . '?branch_id=' . $branchID));
    }
    
    // ============================================
    // LOAD SELECTED STUDENT DATA FOR DISPLAY
    // ============================================
    $this->data['student'] = null;
    $this->data['medical'] = null;
    $this->data['student_id'] = $student_id;
    
    if ($student_id) {
        // Load student details
        $student_query = $this->db->select('s.*, e.class_id, e.section_id, c.name as class_name, sec.name as section_name')
                                 ->from('student s')
                                 ->join('enroll e', 'e.student_id = s.id AND e.session_id = ' . get_session_id(), 'left')
                                 ->join('class c', 'c.id = e.class_id', 'left')
                                 ->join('section sec', 'sec.id = e.section_id', 'left')
                                 ->where('s.id', $student_id)
                                 ->where('s.branch_id', $branchID)
                                 ->get();
        
        // After loading student data, add this debug
        if ($student_query->num_rows() > 0) {
            $this->data['student'] = $student_query->row_array();
            $this->data['medical'] = $this->hostel_emergency_model->get_student_medical($student_id);
            error_log("Loaded student: " . $this->data['student']['first_name'] . ' ' . $this->data['student']['last_name']);
            error_log("Student ID in data: " . $this->data['student']['id']);
            error_log("Medical data: " . json_encode($this->data['medical']));
        } else {
            error_log("Student not found with ID: " . $student_id);
            // List all students in this branch for debugging
            $all_students = $this->db->select('id, first_name, last_name')
                ->where('branch_id', $branchID)
                ->get('student')
                ->result_array();
            error_log("All students in branch: " . json_encode($all_students));
        }
    }
    
    $this->data['title'] = translate('student_medical_info');
    $this->data['sub_page'] = 'hostel_emergency/student_medical';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}

    // ============================================
    // SMS FUNCTIONS
    // ============================================

    public function resend_sms()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }

        if (!get_permission('hostel_emergency', 'can_edit')) {
            echo json_encode(array('status' => 'fail', 'message' => 'Access denied'));
            exit();
        }

        $incident_id = $this->input->post('incident_id');
        $result = $this->send_emergency_sms($incident_id);

        echo json_encode($result);
        exit();
    }

    private function send_emergency_sms($incident_id)
{
    $incident = $this->hostel_emergency_model->get_incident($incident_id);
    
    if (empty($incident)) {
        error_log("send_emergency_sms: Incident not found - ID: " . $incident_id);
        return false;
    }
    
    $emergency_type = $this->hostel_emergency_model->get_emergency_type($incident['emergency_type_id']);
    
    if (empty($emergency_type)) {
        error_log("send_emergency_sms: Emergency type not found - ID: " . $incident['emergency_type_id']);
        return false;
    }
    
    // Get recipients
    $recipients = $this->get_emergency_recipients($incident);
    
    if (empty($recipients)) {
        error_log("send_emergency_sms: No recipients found for incident - ID: " . $incident_id);
        return false;
    }
    
    // Get SMS template
    $template = $this->hostel_emergency_model->get_emergency_sms_template($incident['emergency_type_id'], $incident['branch_id']);
    
    if (empty($template) || empty($template['template_body'])) {
        error_log("send_emergency_sms: No SMS template for emergency type - ID: " . $incident['emergency_type_id']);
        return false;
    }
    
    $sent_count = 0;
    $failed_count = 0;
    
    foreach ($recipients as $recipient) {
        // Parse template
        $message = $this->parse_emergency_template($template['template_body'], $incident, $recipient, $emergency_type);
        
        // Check if SMS model method exists
        if (!method_exists($this->sms_model, 'sendEmergencySms')) {
            error_log("send_emergency_sms: sendEmergencySms method not found");
            continue;
        }
        
        $sms_data = array(
            'branch_id' => $incident['branch_id'],
            'recipient_mobile' => $recipient['phone'],
            'message' => $message,
            'incident_id' => $incident_id,
            'recipient_name' => $recipient['name'],
            'recipient_type' => $recipient['type']
        );
        
        try {
            $result = $this->sms_model->sendEmergencySms($sms_data);
            
            if ($result['success']) {
                $sent_count++;
            } else {
                $failed_count++;
                error_log("send_emergency_sms: Failed to send to " . $recipient['phone'] . " - " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $failed_count++;
            error_log("send_emergency_sms: Exception - " . $e->getMessage());
        }
        
        usleep(100000);
    }
    
    // Update incident SMS status
    $this->hostel_emergency_model->update_incident($incident_id, array(
        'sms_sent' => 1,
        'sms_sent_at' => date('Y-m-d H:i:s')
    ));
    
    error_log("send_emergency_sms: Sent: $sent_count, Failed: $failed_count");
    return array('sent' => $sent_count, 'failed' => $failed_count);
}

    private function get_emergency_recipients($incident)
    {
        $recipients = array();

        // 1. Get assigned staff for the room
        if (!empty($incident['room_id'])) {
            $assigned_staff = $this->hostel_emergency_model->get_room_assigned_staff($incident['room_id']);
            foreach ($assigned_staff as $staff) {
                if (!empty($staff['mobileno'])) {
                    $recipients[$staff['mobileno']] = array(
                        'id' => $staff['staff_id'],
                        'name' => $staff['staff_name'],
                        'phone' => $staff['mobileno'],
                        'type' => 'staff'
                    );
                }
            }
        }

        // 2. Get admins for high/critical incidents
        if (in_array($incident['severity'], array('high', 'critical'))) {
            $admins = $this->hostel_emergency_model->get_branch_admins($incident['branch_id']);
            foreach ($admins as $admin) {
                if (!empty($admin['mobileno']) && !isset($recipients[$admin['mobileno']])) {
                    $recipients[$admin['mobileno']] = array(
                        'id' => $admin['id'],
                        'name' => $admin['name'],
                        'phone' => $admin['mobileno'],
                        'type' => 'admin'
                    );
                }
            }
        }

        // Get parents for all affected students
        if (!empty($incident['student_id'])) {
            $student_ids = explode(',', $incident['student_id']);
            foreach ($student_ids as $student_id) {
                $parents = $this->hostel_emergency_model->get_student_parents($student_id);
                foreach ($parents as $parent) {
                    if (!empty($parent['mobileno']) && !isset($recipients[$parent['mobileno']])) {
                        $recipients[$parent['mobileno']] = array(
                            'id' => $parent['id'],
                            'name' => $parent['name'],
                            'phone' => $parent['mobileno'],
                            'type' => 'parent'
                        );
                    }
                }
            }
        }

        // 4. Get emergency contacts for medical emergencies
        $emergency_type = $this->hostel_emergency_model->get_emergency_type($incident['emergency_type_id']);
        if ($emergency_type && $emergency_type['name'] == 'Medical Emergency' && !empty($incident['student_id'])) {
            $medical = $this->hostel_emergency_model->get_student_medical($incident['student_id']);
            if ($medical && !empty($medical['emergency_contact_phone']) && !isset($recipients[$medical['emergency_contact_phone']])) {
                $recipients[$medical['emergency_contact_phone']] = array(
                    'id' => null,
                    'name' => $medical['emergency_contact_name'] ?: 'Emergency Contact',
                    'phone' => $medical['emergency_contact_phone'],
                    'type' => 'emergency'
                );
            }
        }

        // 5. Get external emergency contacts for critical incidents
        if (in_array($incident['severity'], array('high', 'critical'))) {
            $external = $this->hostel_emergency_model->get_emergency_contacts($incident['branch_id']);
            foreach ($external as $contact) {
                if (!empty($contact['phone_number']) && !isset($recipients[$contact['phone_number']])) {
                    $recipients[$contact['phone_number']] = array(
                        'id' => $contact['id'],
                        'name' => $contact['contact_name'],
                        'phone' => $contact['phone_number'],
                        'type' => 'external'
                    );
                }
            }
        }

        return array_values($recipients);
    }

    private function parse_emergency_template($template, $incident, $recipient, $emergency_type)
    {
        // Get student info
        $student = array();
        $medical = array();
        if (!empty($incident['student_id'])) {
            $student = $this->db->get_where('student', array('id' => $incident['student_id']))->row_array();
            $medical = $this->hostel_emergency_model->get_student_medical($incident['student_id']);
        }

        // Get branch info
        $branch = $this->db->get_where('branch', array('id' => $incident['branch_id']))->row_array();

        // Get room/hostel names
        $room_name = '';
        $hostel_name = '';
        if (!empty($incident['room_id'])) {
            $room = $this->db->get_where('hostel_room', array('id' => $incident['room_id']))->row();
            $room_name = $room ? $room->name : '';
            if ($room && $room->hostel_id) {
                $hostel = $this->db->get_where('hostel', array('id' => $room->hostel_id))->row();
                $hostel_name = $hostel ? $hostel->name : '';
            }
        }

        $reported_staff = $this->db->select('name')->where('id', $incident['reported_by'])->get('staff')->row();

        $replacements = array(
            '{student_name}' => ($student ? $student['first_name'] . ' ' . $student['last_name'] : 'N/A'),
            '{register_no}' => ($student ? $student['register_no'] : 'N/A'),
            '{blood_group}' => ($medical ? $medical['blood_group'] : 'Unknown'),
            '{allergies}' => ($medical ? $medical['allergies'] : 'None reported'),
            '{condition}' => ($medical ? $medical['chronic_conditions'] : 'None reported'),
            '{medications}' => ($medical ? $medical['medications'] : 'None'),
            '{description}' => $incident['description'],
            '{location}' => $incident['location_details'] ?: $room_name,
            '{hostel_name}' => $hostel_name,
            '{room_name}' => $room_name,
            '{staff_name}' => $reported_staff ? $reported_staff->name : 'School Staff',
            '{school_name}' => $branch['school_name'] ?? $branch['name'] ?? 'School',
            '{school_phone}' => $branch['mobileno'] ?? '',
            '{incident_code}' => $incident['incident_code'],
            '{reported_date}' => date('d M Y H:i', strtotime($incident['reported_at'])),
            '{guardian_name}' => $recipient['name']
        );

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function generate_incident_code()
    {
        return 'EMG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
     
/**
 * Get emergency type for editing (AJAX)
 */
public function get_emergency_type()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $id = $this->input->post('id');
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('*');
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $type = $this->db->get('hostel_emergency_types')->row_array();
    
    echo json_encode($type);
    exit();
}

 /**
 * Get emergency contact for editing (AJAX)
 */
public function get_emergency_contact()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $id = $this->input->post('id');
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('*');
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $contact = $this->db->get('hostel_emergency_contacts')->row_array();
    
    echo json_encode($contact);
    exit();
}

/**
 * Get students by room (AJAX)
 */
public function get_students_by_room()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $room_id = $this->input->post('room_id');
    $branch_id = $this->input->post('branch_id');
    
    if (empty($room_id)) {
        echo json_encode(array());
        exit();
    }
    
    // Get branch ID
    if (empty($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    $session_id = get_session_id();
    
    $this->db->select('s.id, s.first_name, s.last_name, s.register_no')
             ->from('student s')
             ->join('enroll e', 'e.student_id = s.id AND e.session_id = ' . $this->db->escape($session_id), 'left')
             ->where('s.room_id', $room_id)
             ->where('s.branch_id', $branch_id)
             ->order_by('s.first_name', 'ASC');
    
    $students = $this->db->get()->result_array();
    
    echo json_encode($students);
    exit();
}
public function view($id)
{
    if (!get_permission('hostel_emergency', 'is_view')) {
        access_denied();
    }

    $branchID = $this->application_model->get_branch_id();
    
    // Debug: Log the ID being requested
    error_log("View incident ID: " . $id);
    
    // Get incident without branch filter first to see if it exists
    $incident = $this->hostel_emergency_model->get_incident($id);
    
    if (empty($incident)) {
        error_log("Incident not found: " . $id);
        show_404();
    }
    
    error_log("Incident found - Branch ID: " . ($incident['branch_id'] ?? 'NULL'));
    
    // Branch access check for non-superadmin
    if (!is_superadmin_loggedin() && $incident['branch_id'] != $branchID) {
        error_log("Access denied - User branch: $branchID, Incident branch: " . $incident['branch_id']);
        access_denied();
    }
    
    $this->data['incident'] = $incident;
    $this->data['sms_logs'] = $this->hostel_emergency_model->get_incident_sms_logs($id);
    $this->data['medical_info'] = $this->hostel_emergency_model->get_student_medical($incident['student_id']);
    
    $this->data['title'] = translate('incident_details');
    $this->data['sub_page'] = 'hostel_emergency/view';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
/**
 * AJAX method to load medical form for a specific student
 */
public function get_medical_form()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    $student_id = $this->input->post('student_id');
    $branch_id = $this->input->post('branch_id');
    
    if (empty($student_id)) {
        echo '<div class="alert alert-danger">Student ID required</div>';
        return;
    }
    
    // Load student details
    $student = $this->db->select('s.*, e.class_id, e.section_id, c.name as class_name, sec.name as section_name')
                       ->from('student s')
                       ->join('enroll e', 'e.student_id = s.id AND e.session_id = ' . get_session_id(), 'left')
                       ->join('class c', 'c.id = e.class_id', 'left')
                       ->join('section sec', 'sec.id = e.section_id', 'left')
                       ->where('s.id', $student_id)
                       ->where('s.branch_id', $branch_id)
                       ->get()
                       ->row_array();
    
    if (empty($student)) {
        echo '<div class="alert alert-danger">Student not found</div>';
        return;
    }
    
    // Load medical data
    $medical = $this->hostel_emergency_model->get_student_medical($student_id);
    
    // Load the view fragment
    $data['student'] = $student;
    $data['medical'] = $medical;
    $data['branch_id'] = $branch_id;
    
    $this->load->view('hostel_emergency/_medical_form', $data);
}
/**
 * AJAX method to save medical form
 */
public function save_medical_form()
{
    $this->output->set_content_type('application/json');
    
    if (!$this->input->is_ajax_request()) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        return;
    }
    
    $student_id = $this->input->post('student_id');
    $branch_id = $this->input->post('branch_id');
    
    if (empty($student_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Student ID required']);
        return;
    }
    
    $this->form_validation->set_rules('blood_group', 'Blood Group', 'required');
    
    if ($this->form_validation->run() == FALSE) {
        echo json_encode(['status' => 'error', 'message' => strip_tags(validation_errors())]);
        return;
    }
    
    $medical_data = array(
        'student_id' => $student_id,
        'blood_group' => $this->input->post('blood_group'),
        'allergies' => $this->input->post('allergies'),
        'chronic_conditions' => $this->input->post('chronic_conditions'),
        'medications' => $this->input->post('medications'),
        'emergency_contact_name' => $this->input->post('emergency_contact_name'),
        'emergency_contact_phone' => $this->input->post('emergency_contact_phone'),
        'emergency_contact_relation' => $this->input->post('emergency_contact_relation'),
        'insurance_provider' => $this->input->post('insurance_provider'),
        'insurance_policy_no' => $this->input->post('insurance_policy_no'),
        'doctor_name' => $this->input->post('doctor_name'),
        'doctor_phone' => $this->input->post('doctor_phone'),
        'doctor_address' => $this->input->post('doctor_address'),
        'last_updated' => date('Y-m-d H:i:s'),
        'updated_by' => get_loggedin_user_id(),
        'branch_id' => $branch_id
    );
    
    $existing = $this->hostel_emergency_model->get_student_medical($student_id);
    
    if ($existing) {
        $result = $this->hostel_emergency_model->update_student_medical($student_id, $medical_data);
    } else {
        $medical_data['created_at'] = date('Y-m-d H:i:s');
        $result = $this->hostel_emergency_model->add_student_medical($medical_data);
    }
    
    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Medical information saved successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save medical information']);
    }
}
/**
 * Student Medical Report with Export
 */
/**
 * Student Medical Report with Export - Only students with medical issues
 */
public function medical_report()
{
    if (!get_permission('student_medical', 'is_view')) {
        access_denied();
    }

    $branchID = $this->application_model->get_branch_id();
    
    // For superadmin branch selection
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->input->get('branch_id');
        if (!empty($selected_branch)) {
            $branchID = $selected_branch;
        }
        $this->data['branches'] = $this->db->select('id, name, school_name')->get('branch')->result_array();
        $this->data['selected_branch_id'] = $branchID;
    }

    // Get ONLY students with medical issues (blood_group, allergies, chronic_conditions, or medications not empty)
    $sql = "SELECT 
                s.id,
                s.first_name,
                s.last_name,
                s.register_no,
                s.gender,
                c.name as class_name,
                sec.name as section_name,
                h.name as hostel_name,
                hr.name as room_name,
                sm.blood_group,
                sm.allergies,
                sm.chronic_conditions,
                sm.medications,
                sm.emergency_contact_name,
                sm.emergency_contact_phone,
                sm.emergency_contact_relation,
                sm.insurance_provider,
                sm.insurance_policy_no,
                sm.doctor_name,
                sm.doctor_phone,
                sm.doctor_address,
                sm.last_updated,
                p.name as parent_name,
                p.mobileno as parent_mobile
            FROM student s
            LEFT JOIN enroll e ON e.student_id = s.id AND e.session_id = " . get_session_id() . "
            LEFT JOIN class c ON c.id = e.class_id
            LEFT JOIN section sec ON sec.id = e.section_id
            LEFT JOIN hostel h ON h.id = s.hostel_id
            LEFT JOIN hostel_room hr ON hr.id = s.room_id
            INNER JOIN hostel_student_medical sm ON sm.student_id = s.id AND sm.branch_id = s.branch_id
            LEFT JOIN parent p ON p.id = s.parent_id
            WHERE s.branch_id = " . $this->db->escape($branchID) . "
            AND (sm.blood_group IS NOT NULL AND sm.blood_group != ''
                 OR sm.allergies IS NOT NULL AND sm.allergies != ''
                 OR sm.chronic_conditions IS NOT NULL AND sm.chronic_conditions != ''
                 OR sm.medications IS NOT NULL AND sm.medications != '')
            ORDER BY s.first_name ASC";
    
    $this->data['students'] = $this->db->query($sql)->result_array();
    $this->data['branch_id'] = $branchID;
    $this->data['branch_name'] = $this->db->select('name, school_name')->where('id', $branchID)->get('branch')->row();
    $this->data['generated_date'] = date('d M Y H:i:s');
    
    $this->data['title'] = translate('student_medical_report');
    $this->data['sub_page'] = 'hostel_emergency/medical_report';
    $this->data['main_menu'] = 'hostels';
    
    $this->load->view('layout/index', $this->data);
}
public function send_mass_alert()
{
    // Enable error logging for debugging
    error_log("=== send_mass_alert called ===");
    
    if (!$this->input->is_ajax_request()) {
        error_log("Not an AJAX request");
        echo json_encode(array('status' => 'fail', 'message' => 'Invalid request type'));
        exit();
    }
    
    if (!get_permission('hostel_emergency', 'is_edit')) {
        error_log("Permission denied");
        echo json_encode(array('status' => 'fail', 'message' => 'Access denied'));
        exit();
    }
    
    $incident_id = $this->input->post('incident_id');
    error_log("Incident ID received: " . ($incident_id ?? 'NULL'));
    
    if (empty($incident_id)) {
        error_log("Incident ID is empty");
        echo json_encode(array('status' => 'fail', 'message' => 'Incident ID is required'));
        exit();
    }
    
    // Get branch ID from incident
    $incident = $this->hostel_emergency_model->get_incident($incident_id);
    if (empty($incident)) {
        error_log("Incident not found for ID: " . $incident_id);
        echo json_encode(array('status' => 'fail', 'message' => 'Incident not found'));
        exit();
    }
    
    $branch_id = $incident['branch_id'];
    error_log("Branch ID: " . $branch_id);
    
    $sms_data = array(
        'incident_id' => $incident_id,
        'branch_id' => $branch_id
    );
    
    // Check if method exists
    if (!method_exists($this->sms_model, 'sendMassEmergencyAlert')) {
        error_log("Method sendMassEmergencyAlert not found in Sms_model");
        echo json_encode(array('status' => 'fail', 'message' => 'SMS method not available'));
        exit();
    }
    
    $result = $this->sms_model->sendMassEmergencyAlert($sms_data);
    error_log("Mass alert result: " . print_r($result, true));
    
    echo json_encode(array('status' => $result['success'] ? 'success' : 'fail', 'message' => $result['message']));
    exit();
}

/**
 * Test SMS delivery to a single number
 * Usage: Call via browser: /hostel_emergency/test_sms
 */
public function test_sms()
{
    if (!is_superadmin_loggedin()) {
        show_404();
    }
    
    $test_number = '254707377945'; // Your test number
    $test_message = "TEST SMS from Hostel Management System. Time: " . date('Y-m-d H:i:s');
    
    $branch_id = $this->application_model->get_branch_id();
    $sms_api = $this->application_model->smsServiceProvider($branch_id);
    
    $this->load->library('bulksmsbd', ['branch_id' => $branch_id], 'test_lib');
    $response = $this->test_lib->send($test_number, $test_message);
    
    echo "<pre>";
    echo "Test SMS sent to: " . $test_number . "\n\n";
    echo "Response: " . $response . "\n\n";
    
    $response_array = json_decode($response, true);
    echo "Parsed Response:\n";
    print_r($response_array);
    echo "</pre>";
}
}