<?php
defined('BASEPATH') or exit('No direct script access allowed');
       // ========== TEMPORARY DEBUG ==========
error_log("=== Stars.php controller loaded ===");
// =====================================

class Stars extends Admin_Controller
{
    
    public function __construct()
    {  
         error_log("=== Stars controller constructor called ===");      
        parent::__construct();
        $this->load->model('stars_model');
        $this->load->model('online_admission_model');
        $this->load->model('sms_model');
        $this->load->model('email_model');
        $this->load->library('stars_sms');

        error_log("=== Stars controller constructor completed ===");
        
        if (!get_permission('stars', 'is_view')) {
            access_denied();
        }
    }
    
    /**
     * Main STARS dashboard
     */
   public function index()
    {
        redirect('stars/stars_dashboard');
    }

   public function stars_dashboard()
{
    log_message('info', '=== stars_dashboard controller called ===');
    
    // Use a more specific permission check
    if (!get_permission('stars_dashboard', 'is_view') && !get_permission('stars', 'is_view')) {
        log_message('error', 'Permission denied for stars_dashboard');
        access_denied();
        return;
    }
    
    // Get branch ID with superadmin filter
    $branch_id = null;
    
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->session->userdata('selected_branch');
        if (!empty($selected_branch)) {
            $branch_id = $selected_branch;
        }
    } else {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    // Get dashboard stats from model
    $stats = $this->stars_model->get_dashboard_stats($branch_id);
    
    $this->data['stats'] = $stats;
    $this->data['title'] = 'STARS Dashboard';
    $this->data['sub_page'] = 'stars/dashboard';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}


public function assessments()
{
    if (!get_permission('stars', 'is_view')) {
        access_denied();
    }
    
    // Get effective branch ID for filtering
    $filter_branch_id = null;
    
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->session->userdata('selected_branch');
        if (!empty($selected_branch)) {
            $filter_branch_id = $selected_branch;
        }
    } else {
        $filter_branch_id = $this->application_model->get_branch_id();
    }
    
    // Build query
    $this->db->select('ta.*, 
                      s.first_name, s.last_name, s.register_no, s.mobileno,
                      c.name as class_name, sec.name as section_name,
                      i.plan_code, i.id as iarp_id, i.status as iarp_status,
                      (SELECT COUNT(*) FROM iarp_missing_topics imt WHERE imt.iarp_id = i.id AND imt.status = "completed") as covered_topics,
                      (SELECT COUNT(*) FROM iarp_missing_topics imt WHERE imt.iarp_id = i.id) as total_topics');
    $this->db->from('transfer_assessments ta');
    $this->db->join('student s', 's.id = ta.student_id', 'left');
    $this->db->join('class c', 'c.id = ta.joining_class_id', 'left');
    $this->db->join('section sec', 'sec.id = ta.joining_section_id', 'left');
    $this->db->join('iarp_plans i', 'i.transfer_assessment_id = ta.id', 'left');
    
    // Branch isolation
    if (!empty($filter_branch_id)) {
        $this->db->where('ta.branch_id', $filter_branch_id);
    }
    
    $this->db->order_by('ta.created_at', 'DESC');
    
    $query = $this->db->get();
    $assessments = $query->result_array();
    
    // Get guardian names for manual admissions
    foreach ($assessments as &$a) {
        if (empty($a['guardian_name']) && !empty($a['student_id'])) {
            $this->db->select('p.name as guardian_name, p.mobileno as grd_mobile_no');
            $this->db->from('student s');
            $this->db->join('parent p', 'p.id = s.parent_id', 'left');
            $this->db->where('s.id', $a['student_id']);
            $parent = $this->db->get()->row_array();
            if ($parent) {
                $a['guardian_name'] = $parent['guardian_name'];
                $a['grd_mobile_no'] = $parent['grd_mobile_no'];
            }
        }
    }
    
    $this->data['assessments'] = $assessments;
    $this->data['title'] = 'Transfer Assessments';
    $this->data['sub_page'] = 'stars/assessments';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

    
    /**
     * Start a new transfer assessment from online admission
     */
    public function start_assessment($admission_id)
    {
        if (!get_permission('stars', 'is_add')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        // Get admission
        $admission = $this->db->get_where('online_admission', ['id' => $admission_id])->row_array();
        if (!$admission) {
            set_alert('error', 'Admission not found');
            redirect('online_admission');
        }
        
        // Create transfer assessment
        $transfer_id = $this->stars_model->get_or_create_transfer_assessment($admission_id, $branch_id);
        
        redirect('stars/assessment_detail/' . $transfer_id);
    }
    
    /**
 * Save topic coverage via AJAX
 */
public function save_coverage()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    $transfer_id = $this->input->post('transfer_id');
    $topic_id = $this->input->post('topic_id');
    $status = $this->input->post('status');
    $notes = $this->input->post('notes');
    
    // Get student ID from transfer assessment
    $this->db->select('student_id');
    $assessment = $this->db->get_where('transfer_assessments', ['id' => $transfer_id])->row_array();
    $student_id = $assessment['student_id'];
    
    // Check if record exists
    $this->db->where('student_id', $student_id);
    $this->db->where('topic_id', $topic_id);
    $existing = $this->db->get('student_topic_coverage')->row();
    
    $data = [
        'student_id' => $student_id,
        'transfer_assessment_id' => $transfer_id,
        'topic_id' => $topic_id,
        'coverage_status' => $status,
        'assessment_notes' => $notes,
        'verified_by' => $this->session->userdata('loggedin_userid'),
        'verified_at' => date('Y-m-d H:i:s')
    ];
    
    if ($existing) {
        $this->db->where('id', $existing->id);
        $result = $this->db->update('student_topic_coverage', $data);
    } else {
        $result = $this->db->insert('student_topic_coverage', $data);
    }
    
    echo json_encode(['success' => $result]);
}
    
 public function run_gap_analysis($transfer_id)
{
    // Debug logging
    log_message('info', '=== STARS: run_gap_analysis called ===');
    log_message('info', 'Transfer ID received: ' . $transfer_id);
    
    if (!get_permission('stars', 'is_edit')) {
        log_message('error', 'STARS: Permission denied for run_gap_analysis');
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    // If branch_id is empty, get it from session or assessment
    if (empty($branch_id)) {
        $branch_id = $this->session->userdata('loggedin_branch');
    }
    
    log_message('info', 'Branch ID: ' . $branch_id);
    
    // Get assessment - include branch_id in query
    $this->db->where('id', $transfer_id);
    if (!is_superadmin_loggedin() && !empty($branch_id)) {
        $this->db->where('branch_id', $branch_id);
    }
    $assessment = $this->db->get('transfer_assessments')->row_array();
    
    if (!$assessment) {
        log_message('error', 'STARS: Assessment not found for ID: ' . $transfer_id);
        set_alert('error', 'Assessment not found');
        redirect('stars/assessments');
        return;
    }
    
    // Get branch_id from assessment if still empty
    if (empty($branch_id) && isset($assessment['branch_id'])) {
        $branch_id = $assessment['branch_id'];
    }
    
    log_message('info', 'Final Branch ID: ' . $branch_id);
    
    $student_id = $assessment['student_id'];
    $class_id = $assessment['joining_class_id'];
    
    if (!$student_id) {
        log_message('error', 'STARS: No student linked to assessment ID: ' . $transfer_id);
        set_alert('error', 'No student linked to this assessment');
        redirect('stars/assessment_detail/' . $transfer_id);
        return;
    }
    
    // Check if any topics have been assessed
    $this->db->where('transfer_assessment_id', $transfer_id);
    $topic_count = $this->db->count_all_results('student_topic_coverage');
    log_message('info', 'Topic coverage count: ' . $topic_count);
    
    if ($topic_count == 0) {
        log_message('warning', 'STARS: No topics assessed for transfer_id: ' . $transfer_id);
        set_alert('warning', 'Please mark topic coverage first before running gap analysis.');
        redirect('stars/assessment_detail/' . $transfer_id);
        return;
    }
    
    // Run gap analysis - PASS THE BRANCH_ID
    $gaps = $this->stars_model->run_gap_analysis(
        $transfer_id,
        $student_id,
        $class_id,
        $branch_id  // Make sure this is passed
    );
    
    log_message('info', 'Gap analysis results: ' . count($gaps) . ' subjects processed');
    
    if (empty($gaps)) {
        log_message('warning', 'STARS: No gaps found');
        set_alert('warning', 'No gaps found. All topics are covered.');
        redirect('stars/assessment_detail/' . $transfer_id);
        return;
    }
    
    // Update assessment status
    $this->db->where('id', $transfer_id);
    $this->db->update('transfer_assessments', [
        'status' => 'gap_analysis',
        'assessed_by' => $this->session->userdata('loggedin_userid'),
        'assessed_at' => date('Y-m-d H:i:s')
    ]);
    
    log_message('info', 'STARS: Gap analysis completed, redirecting to gap_report/' . $transfer_id);
    set_alert('success', 'Gap analysis completed successfully.');
    redirect('stars/gap_report/' . $transfer_id);
}
    
    /**
 * Display gap analysis report
 */
public function gap_report($transfer_id)
{
    if (!get_permission('stars', 'is_view')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    // Get assessment
    $this->db->select('ta.*, oa.first_name, oa.last_name, oa.guardian_name, oa.grd_mobile_no, oa.mobile_no,
                      c.name as class_name');
    $this->db->from('transfer_assessments ta');
    $this->db->join('online_admission oa', 'oa.id = ta.admission_id', 'left');
    $this->db->join('class c', 'c.id = ta.joining_class_id', 'left');
    $this->db->where('ta.id', $transfer_id);
    
    if (!is_superadmin_loggedin()) {
        $this->db->where('ta.branch_id', $branch_id);
    }
    
    $assessment = $this->db->get()->row_array();
    
    if (!$assessment) {
        set_alert('error', 'Assessment not found');
        redirect('stars/assessments');
        return;
    }
    
    // If guardian name is missing (manual admission), get from student parent
    if (empty($assessment['guardian_name']) && !empty($assessment['student_id'])) {
        $this->db->select('p.name as guardian_name, p.mobileno as grd_mobile_no');
        $this->db->from('student s');
        $this->db->join('parent p', 'p.id = s.parent_id', 'left');
        $this->db->where('s.id', $assessment['student_id']);
        $parent = $this->db->get()->row_array();
        if ($parent) {
            $assessment['guardian_name'] = $parent['guardian_name'];
            $assessment['grd_mobile_no'] = $parent['grd_mobile_no'];
        }
    }
    
    // Get gap results
    $this->db->select('g.*, s.name as subject_name');
    $this->db->from('gap_analysis_results g');
    $this->db->join('subject s', 's.id = g.subject_id');
    $this->db->where('g.transfer_assessment_id', $transfer_id);
    $this->db->order_by('g.gap_percentage', 'DESC');
    $gaps = $this->db->get()->result_array();
    
    // Calculate overall gap
    $total_gap = 0;
    foreach ($gaps as $gap) {
        $total_gap += $gap['gap_percentage'];
    }
    $overall_gap = count($gaps) > 0 ? round($total_gap / count($gaps), 1) : 0;
    
    $this->data['assessment'] = $assessment;
    $this->data['gaps'] = $gaps;
    $this->data['overall_gap'] = $overall_gap;
    $this->data['transfer_id'] = $transfer_id;
    $this->data['title'] = 'Gap Analysis Report - ' . $assessment['first_name'] . ' ' . $assessment['last_name'];
    $this->data['sub_page'] = 'stars/gap_report';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}
    
    /**
 * Generate IARP (Individual Academic Recovery Plan)
 */
public function generate_iarp($transfer_id)
{
    if (!get_permission('stars', 'is_add')) {
        access_denied();
    }
    
    // Get branch ID at the beginning
    $branch_id = $this->application_model->get_branch_id();
    
    // For superadmin, use selected branch from session
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->session->userdata('selected_branch');
        if (!empty($selected_branch)) {
            $branch_id = $selected_branch;
        }
    }
    
    log_message('info', 'generate_iarp - Branch ID: ' . $branch_id);
    
    // Get assessment
    $this->db->where('id', $transfer_id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branch_id);
    }
    $assessment = $this->db->get('transfer_assessments')->row_array();
    
    if (!$assessment) {
        set_alert('error', 'Assessment not found');
        redirect('stars/assessments');
        return;
    }
    
    // Check if IARP already exists
    $this->db->where('transfer_assessment_id', $transfer_id);
    $existing = $this->db->get('iarp_plans')->row();
    
    if ($existing) {
        set_alert('warning', 'IARP already exists for this student');
        redirect('stars/view_iarp/' . $existing->id);
        return;
    }
    
    // Generate IARP
    $iarp_id = $this->stars_model->generate_iarp(
        $transfer_id,
        $assessment['student_id'],
        $this->session->userdata('loggedin_userid')
    );
    
    if ($iarp_id) {
        // Update assessment status
        $this->db->where('id', $transfer_id);
        $this->db->update('transfer_assessments', ['status' => 'recovery_plan']);
        
        // ========== SEND IARP CREATED SMS ==========
        $this->load->library('stars_sms');
        $this->stars_sms->send_iarp_created($iarp_id, $branch_id);  // Pass branch_id here
        // ========== END SMS ==========
        
        set_alert('success', 'IARP generated successfully.');
        redirect('stars/view_iarp/' . $iarp_id);
    } else {
        set_alert('warning', 'No gaps found. Student is on track!');
        redirect('stars/gap_report/' . $transfer_id);
    }
}

 public function view_iarp($iarp_id)
{
    if (!get_permission('stars', 'is_view')) {
        access_denied();
    }
    
    $iarp = $this->stars_model->get_iarp_details($iarp_id);
    
    if (empty($iarp)) {
        show_404();
        return;
    }
    
    // Get weekly progress with detailed subtopic information
    $weekly_progress = $this->stars_model->get_weekly_progress_with_details($iarp_id);
    
    $branch_id = $this->application_model->get_branch_id();
    $filter_branch_id = null;
    
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->session->userdata('selected_branch');
        if (!empty($selected_branch)) {
            $filter_branch_id = $selected_branch;
        }
    } else {
        $filter_branch_id = $branch_id;
    }
    
    $teachers = $this->stars_model->get_teachers_by_branch($filter_branch_id);
    $subjects = $this->stars_model->get_subjects_by_branch($filter_branch_id);
    
    $this->data['iarp'] = $iarp;
    $this->data['teachers'] = $teachers;
    $this->data['subjects'] = $subjects;
    $this->data['weekly_progress'] = $weekly_progress;  // New data
    $this->data['title'] = 'IARP - ' . $iarp['first_name'] . ' ' . $iarp['last_name'];
    $this->data['sub_page'] = 'stars/view_iarp';
    $this->data['main_menu'] = 'stars';
    
    $this->load->view('layout/index', $this->data);
}
    
    /**
     * Assign teacher to missing topic (AJAX)
     */
    public function assign_teacher()
{
    // Check if AJAX request
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    // Check permission
    if (!get_permission('stars', 'is_edit')) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => 'Access denied']));
        return;
    }
    
    // Get input
    $missing_topic_id = $this->input->post('missing_topic_id');
    $teacher_id = $this->input->post('teacher_id');
    
    // Validate
    if (empty($missing_topic_id) || empty($teacher_id)) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => 'Missing required fields']));
        return;
    }
    
    // Update topic - set teacher AND change status to in_progress
    $data = array(
        'assigned_teacher_id' => $teacher_id,
        'status' => 'in_progress',
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->where('id', $missing_topic_id);
    $result = $this->db->update('iarp_missing_topics', $data);
    
    if ($result) {
        // Get teacher name for response
        $teacher_name = $this->db->select('name')->where('id', $teacher_id)->get('staff')->row()->name;
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'teacher_name' => $teacher_name,
                'message' => 'Teacher assigned successfully'
            ]));
    } else {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => 'Database update failed']));
    }
}
    
    /**
     * Mark topic as completed (AJAX)
     */
  public function complete_topic()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!get_permission('stars', 'is_edit')) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $missing_topic_id = $this->input->post('missing_topic_id');
    
    if (empty($missing_topic_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing topic ID']);
        return;
    }
    
    // Update topic to completed
    $data = array(
        'status' => 'completed',
        'completed_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->where('id', $missing_topic_id);
    $result = $this->db->update('iarp_missing_topics', $data);
    
    if ($result) {
        // Get iarp_id
        $this->db->select('iarp_id');
        $this->db->where('id', $missing_topic_id);
        $topic = $this->db->get('iarp_missing_topics')->row();
        
        if ($topic) {
            // Check if all topics are completed and auto-close
            $close_result = $this->stars_model->auto_close_if_complete($topic->iarp_id);
            
            // If auto-closed, send completion SMS
            if ($close_result && is_array($close_result) && $close_result['success']) {
                $this->load->library('stars_sms');
                $this->stars_sms->send_completion($close_result['iarp_id'], $close_result['branch_id']);
                log_message('info', 'Completion SMS sent for IARP: ' . $close_result['iarp_id']);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Topic marked as completed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
}
    
    /**
     * Activate IARP (start recovery)
     */
    public function activate_iarp($iarp_id)
    {
        if (!get_permission('stars', 'is_edit')) {
            access_denied();
        }
        
        $this->db->where('id', $iarp_id);
        $this->db->update('iarp_plans', [
            'status' => 'active',
            'status_updated_by' => $this->session->userdata('loggedin_userid'),
            'status_updated_at' => date('Y-m-d H:i:s')
        ]);
        
        set_alert('success', 'IARP activated. Recovery monitoring started.');
        redirect('stars/view_iarp/' . $iarp_id);
    }
    
    /**
     * Update weekly progress
     */
    public function update_progress()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $iarp_id = $this->input->post('iarp_id');
        $student_id = $this->input->post('student_id');
        $week_number = $this->input->post('week_number');
        $topics_covered = $this->input->post('topics_covered');
        $teacher_comments = $this->input->post('teacher_comments');
        
        $result = $this->stars_model->update_weekly_progress(
            $iarp_id, $student_id, $week_number, $topics_covered, $teacher_comments
        );
        
        if ($result) {
            // Send weekly SMS to parent
            $this->send_weekly_progress_sms($iarp_id, $week_number);
        }
        
        echo json_encode(['success' => $result]);
    }
    
    /**
     * Close recovery plan
     */
    public function close_recovery($iarp_id)
{
    if (!get_permission('stars', 'is_delete')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    // Get IARP details
    $iarp = $this->stars_model->get_iarp_details($iarp_id);
    
    if (!$iarp) {
        set_alert('error', 'IARP not found');
        redirect('stars/assessments');
        return;
    }
    
    // Close the recovery
    $result = $this->stars_model->close_recovery(
        $iarp_id,
        $iarp['student_id'],
        $this->session->userdata('loggedin_userid'),
        'goals_achieved',
        $this->input->post('notes')
    );
    
    if ($result) {
        // ========== ADD THIS LINE TO SEND SMS ==========
        $this->stars_sms->send_completion($iarp_id, $branch_id);
        // ========== END ==========
        
        set_alert('success', 'Recovery plan closed successfully!');
    } else {
        set_alert('error', 'Failed to close recovery plan');
    }
    
    redirect('stars/assessments');
}
    
    /**
     * Resource management (upload notes, textbooks)
     */
 public function add_resource()
{

    // Add this temporarily at the beginning of the method
    log_message('info', 'POST max size: ' . ini_get('post_max_size'));
    log_message('info', 'Upload max size: ' . ini_get('upload_max_filesize'));
    log_message('info', 'File error: ' . $_FILES['resource_file']['error']);

    // Check if AJAX request
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    // Check permission
    if (!get_permission('stars', 'is_edit')) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => 'Access denied']));
        return;
    }
    
    // Get form data
    $iarp_id = $this->input->post('iarp_id');
    $student_id = $this->input->post('student_id');
    $resource_type = $this->input->post('resource_type');
    $subject_id = $this->input->post('subject_id');
    $title = $this->input->post('title');
    $description = $this->input->post('description');
    
    // Get branch_id
    $branch_id = null;
    if (!empty($iarp_id)) {
        $this->db->select('ta.branch_id');
        $this->db->from('iarp_plans i');
        $this->db->join('transfer_assessments ta', 'ta.id = i.transfer_assessment_id');
        $this->db->where('i.id', $iarp_id);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            $branch_id = $query->row()->branch_id;
        }
    }
    if (empty($branch_id) && !empty($student_id)) {
        $this->db->select('branch_id');
        $this->db->where('id', $student_id);
        $query = $this->db->get('student');
        if ($query->num_rows() > 0) {
            $branch_id = $query->row()->branch_id;
        }
    }
    if (empty($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    // Validate
    if (empty($iarp_id) || empty($student_id) || empty($resource_type) || empty($title)) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => 'Missing required fields']));
        return;
    }
    
   // Handle file upload
    $file_name = null;
    $enc_file_name = null;

    if (!empty($_FILES['resource_file']['name'])) {
        // Get the absolute path using realpath
        $upload_path = realpath(FCPATH . 'uploads/stars_resources/');
        
        // If realpath fails, try alternative path
        if (!$upload_path) {
            $upload_path = FCPATH . 'uploads/stars_resources/';
            $upload_path = str_replace('\\', '/', $upload_path);
        }
        
        // Add trailing slash
        $upload_path = rtrim($upload_path, '/') . '/';
        
        log_message('info', 'Final upload path: ' . $upload_path);
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        // Verify directory exists and is writable
        if (!is_dir($upload_path)) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Upload directory does not exist']));
            return;
        }
        
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'pdf|doc|docx|jpg|png|jpeg|gif|txt';
        $config['max_size'] = 10240;
        $config['encrypt_name'] = true;
        $config['remove_spaces'] = true;
        
        $this->load->library('upload', $config);
        
        // Clear any previous upload errors
        $this->upload->initialize($config);
        
        if ($this->upload->do_upload('resource_file')) {
            $upload_data = $this->upload->data();
            $file_name = $upload_data['orig_name'];
            $enc_file_name = $upload_data['file_name'];
            log_message('info', 'File uploaded successfully: ' . $enc_file_name);
        } else {
            $error = $this->upload->display_errors();
            log_message('error', 'Upload error: ' . $error);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => strip_tags($error)]));
            return;
        }
    }
        
    // Insert data
    $data = array(
        'student_id' => $student_id,
        'iarp_id' => $iarp_id,
        'resource_type' => $resource_type,
        'subject_id' => !empty($subject_id) ? $subject_id : null,
        'topic_id' => null,
        'title' => $title,
        'description' => $description,
        'file_name' => $file_name,
        'enc_file_name' => $enc_file_name,
        'supplied_by' => $this->session->userdata('loggedin_userid'),
        'supplied_at' => date('Y-m-d H:i:s'),
        'acknowledged_by_student' => 0,
        'acknowledged_at' => null,
        'branch_id' => $branch_id,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $result = $this->db->insert('resource_recovery', $data);
    
    if ($result) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Resource added successfully']));
    } else {
        $error = $this->db->error();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => 'Database error: ' . $error['message']]));
    }
}

/**
 * Download resource file
 */
public function download_resource($resource_id)
{
    // Check permission
    if (!get_permission('stars', 'is_view')) {
        access_denied();
    }
    
    // Get resource details
    $this->db->select('file_name, enc_file_name, iarp_id');
    $this->db->where('id', $resource_id);
    $resource = $this->db->get('resource_recovery')->row_array();
    
    if (empty($resource) || empty($resource['enc_file_name'])) {
        set_alert('error', 'File not found');
        redirect($_SERVER['HTTP_REFERER'] ?? base_url('stars/assessments'));
        return;
    }
    
    // Build file path
    $file_path = FCPATH . 'uploads/stars_resources/' . $resource['enc_file_name'];
    $file_path = str_replace('\\', '/', $file_path);
    
    // Check if file exists
    if (!file_exists($file_path)) {
        set_alert('error', 'File does not exist on server');
        redirect($_SERVER['HTTP_REFERER'] ?? base_url('stars/assessments'));
        return;
    }
    
    // Get original file name
    $original_name = $resource['file_name'];
    
    // Clear output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $original_name . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    
    // Read file and output
    readfile($file_path);
    exit;
}
    
    /**
     * Peer mentorship management
     */
   public function add_mentorship()
    {
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!get_permission('stars', 'is_edit')) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $transfer_student_id = $this->input->post('transfer_student_id');
    $mentor_student_id = $this->input->post('mentor_student_id');
    $iarp_id = $this->input->post('iarp_id');
    $subject_id = $this->input->post('subject_id');
    
    if (empty($transfer_student_id) || empty($mentor_student_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    $data = array(
        'transfer_student_id' => $transfer_student_id,
        'mentor_student_id' => $mentor_student_id,
        'iarp_id' => $iarp_id,
        'subject_id' => !empty($subject_id) ? $subject_id : null,
        'assigned_by' => $this->session->userdata('loggedin_userid')
        // branch_id will be determined by the model
    );
    
    $result = $this->stars_model->add_mentorship($data);
    
    echo json_encode($result);
}
    
    /**
     * Log mentorship meeting
     */
    public function log_meeting()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        if (!get_permission('stars', 'is_edit')) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $mentorship_id = $this->input->post('mentorship_id');
        $topics_covered = $this->input->post('topics_covered');
        
        log_message('info', 'log_meeting controller - Mentorship ID: ' . $mentorship_id);
        
        if (empty($mentorship_id) || empty($topics_covered)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        $created_by = $this->session->userdata('loggedin_userid');
        
        $result = $this->stars_model->log_meeting($mentorship_id, $topics_covered, $created_by);
        
        echo json_encode($result);
    }
    
    /**
 * Get available mentors for dropdown (AJAX)
 */
public function get_available_mentors()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!get_permission('stars', 'is_view')) {
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $class_id = $this->input->post('class_id');
    $transfer_student_id = $this->input->post('transfer_student_id');
    
    log_message('info', 'get_available_mentors AJAX - Class ID: ' . $class_id . ', Transfer Student: ' . $transfer_student_id);
    
    if (empty($class_id)) {
        echo json_encode([]);
        return;
    }
    
    // Get branch ID from the IARP
    $branch_id = null;
    
    // Try to get branch_id from the transfer student
    if (!empty($transfer_student_id)) {
        $this->db->select('s.branch_id');
        $this->db->from('student s');
        $this->db->where('s.id', $transfer_student_id);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            $branch_id = $query->row()->branch_id;
            log_message('info', 'Branch ID from student: ' . $branch_id);
        }
    }
    
    // If still empty, get from application model
    if (empty($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
        log_message('info', 'Branch ID from application model: ' . $branch_id);
    }
    
    // For superadmin, use selected branch from session
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->session->userdata('selected_branch');
        if (!empty($selected_branch)) {
            $branch_id = $selected_branch;
            log_message('info', 'Superadmin using selected branch: ' . $branch_id);
        }
    }
    
    // Final fallback - if still empty, try to get from enroll table
    if (empty($branch_id) && !empty($class_id)) {
        $this->db->select('branch_id');
        $this->db->from('enroll');
        $this->db->where('class_id', $class_id);
        $this->db->limit(1);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            $branch_id = $query->row()->branch_id;
            log_message('info', 'Branch ID from enroll table: ' . $branch_id);
        }
    }
    
    $students = $this->stars_model->get_available_mentors($class_id, $transfer_student_id, $branch_id);
    
    echo json_encode($students);
}
    
    // ========== SMS SENDING METHODS ==========
    
    private function send_gap_analysis_sms($assessment)
    {
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 30,
            'branch_id' => $assessment['branch_id']
        ])->row_array();
        
        if (!$template) return;
        
        // Get top gap subject
        $this->db->select('g.*, s.name as subject_name');
        $this->db->from('gap_analysis_results g');
        $this->db->join('subject s', 's.id = g.subject_id');
        $this->db->where('g.transfer_assessment_id', $assessment['id']);
        $this->db->order_by('g.gap_percentage', 'DESC');
        $this->db->limit(1);
        $top_gap = $this->db->get()->row_array();
        
        $message = $template['template_body'];
        $message = str_replace('{student_name}', $assessment['first_name'] . ' ' . $assessment['last_name'], $message);
        $message = str_replace('{guardian_name}', $assessment['guardian_name'], $message);
        $message = str_replace('{subject}', $top_gap['subject_name'] ?? 'Multiple Subjects', $message);
        $message = str_replace('{gap_percentage}', round($top_gap['gap_percentage'] ?? 0, 1), $message);
        $message = str_replace('{missing_topics}', $top_gap['missing_topics'] ?? 0, $message);
        
        $branch = $this->db->get_where('branch', ['id' => $assessment['branch_id']])->row();
        $message = str_replace('{school_name}', $branch->name ?? 'School', $message);
        
        if ($template['notify_parent'] && !empty($assessment['grd_mobile_no'])) {
            $this->send_sms($assessment['grd_mobile_no'], $message, $assessment['branch_id']);
        }
    }
    
    private function send_iarp_created_sms($assessment, $iarp_id)
    {
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 31,
            'branch_id' => $assessment['branch_id']
        ])->row_array();
        
        if (!$template) return;
        
        $iarp = $this->stars_model->get_iarp_details($iarp_id);
        
        $message = $template['template_body'];
        $message = str_replace('{student_name}', $assessment['first_name'] . ' ' . $assessment['last_name'], $message);
        $message = str_replace('{guardian_name}', $assessment['guardian_name'], $message);
        $message = str_replace('{plan_code}', $iarp['plan_code'], $message);
        $message = str_replace('{start_date}', date('d M Y', strtotime($iarp['start_date'])), $message);
        $message = str_replace('{target_end_date}', date('d M Y', strtotime($iarp['target_end_date'])), $message);
        
        $total_hours = 0;
        foreach ($iarp['missing_topics'] as $topic) {
            $total_hours += $topic['estimated_hours'];
        }
        $message = str_replace('{recovery_hours}', round($total_hours, 1), $message);
        
        $branch = $this->db->get_where('branch', ['id' => $assessment['branch_id']])->row();
        $message = str_replace('{school_phone}', $branch->mobileno ?? 'School Office', $message);
        
        if ($template['notify_parent'] && !empty($assessment['grd_mobile_no'])) {
            $this->send_sms($assessment['grd_mobile_no'], $message, $assessment['branch_id']);
        }
    }
    
    private function send_weekly_progress_sms($iarp_id, $week_number)
    {
        $iarp = $this->stars_model->get_iarp_details($iarp_id);
        
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 32,
            'branch_id' => $iarp['branch_id'] ?? $this->application_model->get_branch_id()
        ])->row_array();
        
        if (!$template) return;
        
        // Get latest progress
        $this->db->where('iarp_id', $iarp_id);
        $this->db->where('week_number', $week_number);
        $progress = $this->db->get('recovery_progress')->row_array();
        
        $message = $template['template_body'];
        $message = str_replace('{student_name}', $iarp['first_name'] . ' ' . $iarp['last_name'], $message);
        $message = str_replace('{guardian_name}', $iarp['guardian_name'] ?? 'Parent', $message);
        $message = str_replace('{week_number}', $week_number, $message);
        $message = str_replace('{topics_covered}', $progress['topics_covered_count'] ?? 0, $message);
        $message = str_replace('{remaining_topics}', $progress['topics_remaining_count'] ?? 0, $message);
        $message = str_replace('{improvement}', round($progress['improvement_percentage'] ?? 0, 1), $message);
        $message = str_replace('{teacher_comment}', $progress['teacher_comments'] ?? 'No comments', $message);
        
        // Get parent mobile from transfer assessment
        $this->db->select('grd_mobile_no');
        $this->db->from('transfer_assessments ta');
        $this->db->join('online_admission oa', 'oa.id = ta.admission_id');
        $this->db->where('ta.id', $iarp['transfer_assessment_id']);
        $assessment = $this->db->get()->row_array();
        
        if ($template['notify_parent'] && !empty($assessment['grd_mobile_no'])) {
            $this->send_sms($assessment['grd_mobile_no'], $message, $iarp['branch_id']);
            
            // Mark SMS as sent
            $this->db->where('iarp_id', $iarp_id);
            $this->db->where('week_number', $week_number);
            $this->db->update('recovery_progress', [
                'sms_sent_to_parent' => 1,
                'sms_sent_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    private function send_completion_sms($iarp)
    {
        $template = $this->db->get_where('sms_template_details', [
            'template_id' => 33,
            'branch_id' => $iarp['branch_id'] ?? $this->application_model->get_branch_id()
        ])->row_array();
        
        if (!$template) return;
        
        $message = $template['template_body'];
        $message = str_replace('{student_name}', $iarp['first_name'] . ' ' . $iarp['last_name'], $message);
        $message = str_replace('{guardian_name}', $iarp['guardian_name'] ?? 'Parent', $message);
        $message = str_replace('{completion_date}', date('d M Y'), $message);
        $message = str_replace('{achievement_summary}', $iarp['notes'] ?? 'Successfully completed the Academic Recovery Plan', $message);
        
        $branch = $this->db->get_where('branch', ['id' => $iarp['branch_id']])->row();
        $message = str_replace('{school_name}', $branch->name ?? 'School', $message);
        
        // Get parent mobile
        $this->db->select('grd_mobile_no');
        $this->db->from('transfer_assessments ta');
        $this->db->join('online_admission oa', 'oa.id = ta.admission_id');
        $this->db->where('ta.id', $iarp['transfer_assessment_id']);
        $assessment = $this->db->get()->row_array();
        
        if ($template['notify_parent'] && !empty($assessment['grd_mobile_no'])) {
            $this->send_sms($assessment['grd_mobile_no'], $message, $iarp['branch_id']);
        }
    }
    
    private function send_sms($mobile, $message, $branch_id)
    {
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        if (empty($mobile)) return false;
        
        // Check credits
        $credits = $this->sendsmsmail_model->get_sms_credit($branch_id);
        $required = ceil(strlen($message) / 160);
        
        if ($credits < $required) {
            log_message('error', "STARS SMS failed: Insufficient credits for branch {$branch_id}");
            return false;
        }
        
        $this->load->library('bulksmsbd', ['branch_id' => $branch_id], 'sms_lib');
        return $this->sms_lib->send($mobile, $message);
    }

    /**
 * Set branch filter for superadmin
 */
public function set_branch_filter()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!is_superadmin_loggedin()) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $branch_id = $this->input->post('branch_id');
    
    if (!empty($branch_id)) {
        $this->session->set_userdata('selected_branch', $branch_id);
        log_message('info', 'Branch filter set to: ' . $branch_id);
    } else {
        $this->session->unset_userdata('selected_branch');
        log_message('info', 'Branch filter cleared');
    }
    
    echo json_encode(['success' => true]);
}

public function get_topics()
{
    $class_id = $this->input->post('class_id');
    $subject_id = $this->input->post('subject_id');
    $branch_id = $this->input->post('branch_id');
    
    $this->db->where('class_id', $class_id);
    $this->db->where('subject_id', $subject_id);
    $this->db->where('branch_id', $branch_id);
    $this->db->order_by('topic_order', 'ASC');
    $topics = $this->db->get('curriculum_topics')->result_array();
    
    $html = '';
    foreach ($topics as $topic) {
        $html .= '<tr>
            <td>' . $topic['topic_order'] . '</td>
            <td>' . $topic['topic_name'] . '</td>
            <td>' . ($topic['topic_code'] ?: '-') . '</td>
            <td>' . $topic['expected_weeks'] . '</td>
            <td>' . ($topic['is_competency_based'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>') . '</td>
            <td>
                <button class="btn btn-xs btn-danger delete-topic" data-id="' . $topic['id'] . '">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>';
    }
    
    echo $html ?: '<tr><td colspan="6" class="text-center">No topics found. Add some!</td></tr>';
}

public function add_topic()
{
    $data = [
        'branch_id' => $this->input->post('branch_id'),
        'class_id' => $this->input->post('class_id'),
        'subject_id' => $this->input->post('subject_id'),
        'topic_name' => $this->input->post('topic_name'),
        'topic_code' => $this->input->post('topic_code'),
        'topic_order' => $this->input->post('topic_order'),
        'expected_weeks' => $this->input->post('expected_weeks'),
        'is_competency_based' => $this->input->post('is_competency_based') ? 1 : 0
    ];
    
    $result = $this->db->insert('curriculum_topics', $data);
    echo json_encode(['success' => $result]);
}
/**
 * Active IARPs list page - OPTIMIZED VERSION
 */
public function active_iarp()
{
    $branch_id = $this->application_model->get_branch_id();
    
    // OPTIMIZED QUERY - removed potentially problematic joins
    $this->db->select('i.*, s.first_name, s.last_name, s.register_no');
    $this->db->from('iarp_plans i');
    $this->db->join('student s', 's.id = i.student_id');
    
    if (!is_superadmin_loggedin()) {
        // Get student IDs in this branch via enroll table
        $this->db->where('s.branch_id', $branch_id);
    }
    
    $this->db->where_in('i.status', ['active', 'draft']);
    $this->db->order_by('i.created_at', 'DESC');
    $this->db->limit(100); // Add limit to prevent timeout
    
    $query = $this->db->get();
    $active_iarps = $query->result_array();
    
    // Load additional data separately (avoid joins that might cause issues)
    foreach ($active_iarps as &$iarp) {
        // Get transfer assessment data
        $ta = $this->db->select('joining_class_id, joining_section_id')
                      ->where('id', $iarp['transfer_assessment_id'])
                      ->get('transfer_assessments')
                      ->row_array();
        
        if ($ta) {
            // Get class name
            $class = $this->db->select('name')
                              ->where('id', $ta['joining_class_id'])
                              ->get('class')
                              ->row_array();
            $iarp['class_name'] = $class ? $class['name'] : 'N/A';
            
            // Get section name
            if ($ta['joining_section_id']) {
                $section = $this->db->select('name')
                                    ->where('id', $ta['joining_section_id'])
                                    ->get('section')
                                    ->row_array();
                $iarp['section_name'] = $section ? $section['name'] : '';
            } else {
                $iarp['section_name'] = '';
            }
        } else {
            $iarp['class_name'] = 'N/A';
            $iarp['section_name'] = '';
        }
        
        // Get missing topics count
        $iarp['missing_topics_count'] = $this->db->where('iarp_id', $iarp['id'])
                                                  ->where('status !=', 'completed')
                                                  ->count_all_results('iarp_missing_topics');
        
        // Get completed topics count
        $iarp['completed_topics_count'] = $this->db->where('iarp_id', $iarp['id'])
                                                   ->where('status', 'completed')
                                                   ->count_all_results('iarp_missing_topics');
    }
    
    $this->data['active_iarps'] = $active_iarps;
    $this->data['title'] = 'Active Recovery Plans';
    $this->data['sub_page'] = 'stars/active_iarp';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

/**
 * Manage curriculum topics (Syllabus manager)
 */
public function manage_topics()
{
    if (!get_permission('stars', 'is_view') && !is_admin_loggedin() && !is_superadmin_loggedin()) {
        access_denied();
    }
    
    // Get branch ID
    $branch_id = null;
    
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->session->userdata('selected_branch');
        if (!empty($selected_branch)) {
            $branch_id = $selected_branch;
        }
    } else {
        // Admin: get their assigned branch
        $branch_id = $this->application_model->get_branch_id();
        
        // If still empty, try session
        if (empty($branch_id)) {
            $branch_id = $this->session->userdata('loggedin_branch');
        }
        
        // Log for debugging
        log_message('info', 'Admin manage_topics - Branch ID: ' . ($branch_id ?? 'NULL'));
    }
    
    // Get branches for dropdown (superadmin only)
    if (is_superadmin_loggedin()) {
        $this->data['branches'] = $this->db->order_by('name', 'ASC')->get('branch')->result_array();
    } else {
        // Admin: only their branch
        if (!empty($branch_id)) {
            $this->data['branches'] = $this->db->where('id', $branch_id)->get('branch')->result_array();
        } else {
            $this->data['branches'] = array();
        }
    }
    
    // Get classes for selected branch
    if (!empty($branch_id)) {
        $this->data['classes'] = $this->db->where('branch_id', $branch_id)->order_by('name_numeric', 'ASC')->get('class')->result_array();
        $this->data['subjects'] = $this->db->where('branch_id', $branch_id)->order_by('name', 'ASC')->get('subject')->result_array();
    } else {
        $this->data['classes'] = array();
        $this->data['subjects'] = array();
    }
    
    // Pass branch_id to view (CRITICAL)
    $this->data['branch_id'] = $branch_id;
    $this->data['title'] = 'Manage Curriculum Topics';
    $this->data['sub_page'] = 'stars/manage_topics';
    $this->data['main_menu'] = 'stars';
    $this->data['headerelements'] = [
        'js' => ['js/stars.js']
    ];
    $this->load->view('layout/index', $this->data);
}

/**
 * Get topics via AJAX
 */
public function get_topics_ajax()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    $class_id = $this->input->post('class_id');
    $subject_id = $this->input->post('subject_id');
    $branch_id = $this->input->post('branch_id');
    
    if (empty($class_id) || empty($subject_id)) {
        echo json_encode(['error' => 'Class and Subject are required']);
        return;
    }
    
    $this->db->select('id, topic_name, topic_code, topic_order, expected_weeks, is_competency_based')
             ->from('curriculum_topics')
             ->where('class_id', $class_id)
             ->where('subject_id', $subject_id)
             ->where('branch_id', $branch_id)
             ->order_by('topic_order', 'ASC');
    
    $topics = $this->db->get()->result_array();
    
    // Debug log
    log_message('info', 'get_topics_ajax - Found ' . count($topics) . ' topics');
    foreach ($topics as $topic) {
        log_message('info', 'Topic: ' . $topic['topic_name'] . ' - Order: ' . ($topic['topic_order'] ?? 'NULL') . ' - Weeks: ' . ($topic['expected_weeks'] ?? 'NULL'));
    }
    
    echo json_encode($topics);
}

/**
 * Get subjects by class (AJAX) - for manage_subtopics
 */
public function get_subjects_by_class_ajax()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!get_permission('stars', 'is_view')) {
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $class_id = $this->input->post('class_id');
    $branch_id = $this->input->post('branch_id');
    
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->session->userdata('selected_branch');
        if (!empty($selected_branch)) {
            $branch_id = $selected_branch;
        }
    }
    
    if (empty($class_id)) {
        echo json_encode(['error' => 'Class ID required']);
        return;
    }
    
    if (empty($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    $subjects = $this->stars_model->get_subjects_by_class($class_id, $branch_id);
    
    // Always return array, even if empty
    echo json_encode($subjects);
}

/**
 * Get topics by class and subject (AJAX) - for manage_subtopics
 */
public function get_topics_by_class_subject_ajax()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!get_permission('stars', 'is_view')) {
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $class_id = $this->input->post('class_id');
    $subject_id = $this->input->post('subject_id');
    $branch_id = $this->input->post('branch_id');
    
    // For superadmin, use selected branch from session
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->session->userdata('selected_branch');
        if (!empty($selected_branch)) {
            $branch_id = $selected_branch;
        }
    }
    
    if (empty($class_id) || empty($subject_id)) {
        echo json_encode(['error' => 'Class and Subject are required']);
        return;
    }
    
    $topics = $this->stars_model->get_topics_by_class_subject($class_id, $subject_id, $branch_id);
    
    echo json_encode($topics);
}

/**
 * Add topic via AJAX
 */
public function add_topic_ajax()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    $data = array(
        'branch_id' => $this->input->post('branch_id'),
        'class_id' => $this->input->post('class_id'),
        'subject_id' => $this->input->post('subject_id'),
        'topic_name' => $this->input->post('topic_name'),
        'topic_code' => $this->input->post('topic_code'),
        'topic_order' => $this->input->post('topic_order') ?: 0,
        'expected_weeks' => $this->input->post('expected_weeks') ?: 1.0,
        'is_competency_based' => $this->input->post('is_competency_based') ? 1 : 0
    );
    
    $result = $this->db->insert('curriculum_topics', $data);
    echo json_encode(['success' => $result, 'id' => $result ? $this->db->insert_id() : null]);
}
/**
 * Delete topic via AJAX
 */
public function delete_topic_ajax()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    $topic_id = $this->input->post('topic_id');
    
    // Check if topic is used in any assessment
    $this->db->where('topic_id', $topic_id);
    $used = $this->db->get('student_topic_coverage')->num_rows();
    
    if ($used > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete: Topic is already used in student assessments']);
        return;
    }
    
    $result = $this->db->delete('curriculum_topics', ['id' => $topic_id]);
    echo json_encode(['success' => $result]);
}
/**
 * Update topic via AJAX
 */
public function update_topic_ajax()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    $topic_id = $this->input->post('topic_id');
    $data = array(
        'topic_name' => $this->input->post('topic_name'),
        'topic_order' => $this->input->post('topic_order'),
        'expected_weeks' => $this->input->post('expected_weeks'),
        'is_competency_based' => $this->input->post('is_competency_based') ? 1 : 0
    );
    
    $this->db->where('id', $topic_id);
    $result = $this->db->update('curriculum_topics', $data);
    echo json_encode(['success' => $result]);
}
/**
 * Recovery reports page - FIXED VERSION
 */
public function reports()
{
    if (!get_permission('stars', 'is_view')) {
        access_denied();
    }
    
    // Get branch ID with superadmin filter
    $branch_id = null;
    
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->session->userdata('selected_branch');
        if (!empty($selected_branch)) {
            $branch_id = $selected_branch;
        }
    } else {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    // Date filters
    $start_date = $this->input->get('start_date') ?: date('Y-m-d', strtotime('-30 days'));
    $end_date = $this->input->get('end_date') ?: date('Y-m-d');
    
    // Get data from model with branch filter
    $stats = $this->stars_model->get_report_stats($branch_id);
    $severity_stats = $this->stars_model->get_severity_stats($branch_id);
    $monthly_closures = $this->stars_model->get_monthly_closures($branch_id, $start_date, $end_date);
    $recent_closures = $this->stars_model->get_recent_closures($branch_id);
    
    // Pass data to view
    $this->data['stats'] = $stats;
    $this->data['severity_stats'] = $severity_stats;
    $this->data['monthly_closures'] = $monthly_closures;
    $this->data['recent_closures'] = $recent_closures;
    $this->data['start_date'] = $start_date;
    $this->data['end_date'] = $end_date;
    $this->data['title'] = 'Recovery Reports';
    $this->data['sub_page'] = 'stars/reports';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

private function load_report_view()
{
    $this->data['title'] = 'Recovery Reports';
    $this->data['sub_page'] = 'stars/reports';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

/**
 * Export recovery report to CSV - FIXED VERSION
 */
public function export_report()
{
    $branch_id = $this->application_model->get_branch_id();
    
    // Check if iarp_plans table exists
    if (!$this->db->table_exists('iarp_plans')) {
        show_error('STARS tables not installed. Please run migration first.', 500);
    }
    
    $this->db->select('i.plan_code, i.start_date, i.target_end_date, i.actual_end_date, i.status,
                      s.first_name, s.last_name, s.register_no,
                      c.name as class_name,
                      cl.final_gap_percentage, cl.topics_mastered, cl.total_topics, cl.closure_date');
    $this->db->from('iarp_plans i');
    $this->db->join('student s', 's.id = i.student_id', 'left');
    $this->db->join('transfer_assessments ta', 'ta.id = i.transfer_assessment_id', 'left');
    $this->db->join('class c', 'c.id = ta.joining_class_id', 'left');
    $this->db->join('recovery_closure_logs cl', 'cl.iarp_id = i.id', 'left');
    
    if (!is_superadmin_loggedin() && $branch_id) {
        $this->db->where('s.branch_id', $branch_id);
    }
    
    $this->db->order_by('i.created_at', 'DESC');
    $results = $this->db->get()->result_array();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stars_recovery_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Plan Code', 'Student Name', 'Register No', 'Class', 'Start Date', 'Target End Date', 'Actual End Date', 'Status', 'Final Gap %', 'Topics Mastered', 'Total Topics', 'Closure Date']);
    
    foreach ($results as $row) {
        fputcsv($output, [
            $row['plan_code'] ?? '',
            trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            $row['register_no'] ?? '',
            $row['class_name'] ?? '',
            $row['start_date'] ?? '',
            $row['target_end_date'] ?? '',
            $row['actual_end_date'] ?? '',
            $row['status'] ?? '',
            $row['final_gap_percentage'] ?? '',
            $row['topics_mastered'] ?? '',
            $row['total_topics'] ?? '',
            $row['closure_date'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}
public function assessment_detail($transfer_id)
{
    // Check permission
    if (!get_permission('stars', 'is_view')) {
        access_denied();
    }
    
    // Use the same working query pattern as view_iarp
    $sql = "SELECT ta.*, 
                   s.first_name, s.last_name, s.register_no, s.mobileno,
                   c.name as class_name, sec.name as section_name,
                   p.name as guardian_name, p.mobileno as grd_mobile_no
            FROM transfer_assessments ta
            LEFT JOIN student s ON s.id = ta.student_id
            LEFT JOIN parent p ON p.id = s.parent_id
            LEFT JOIN class c ON c.id = ta.joining_class_id
            LEFT JOIN section sec ON sec.id = ta.joining_section_id
            WHERE ta.id = ?";
    
    $query = $this->db->query($sql, array($transfer_id));
    
    if ($query->num_rows() == 0) {
        show_404();
        return;
    }
    
    $assessment = $query->row_array();
    
    // Get syllabus for this class
    $syllabus_sql = "SELECT ct.*, s.name as subject_name
                     FROM curriculum_topics ct
                     LEFT JOIN subject s ON s.id = ct.subject_id
                     WHERE ct.class_id = ? 
                     AND ct.branch_id = ?
                     ORDER BY ct.subject_id, ct.topic_order";
    
    $syllabus_query = $this->db->query($syllabus_sql, array($assessment['joining_class_id'], $assessment['branch_id']));
    $syllabus = $syllabus_query->result_array();
    
    // Get existing topic coverage
    $coverage_sql = "SELECT * FROM student_topic_coverage WHERE transfer_assessment_id = ?";
    $coverage_query = $this->db->query($coverage_sql, array($transfer_id));
    $coverage = $coverage_query->result_array();
    
    $covered_topics = array();
    foreach ($coverage as $c) {
        $covered_topics[$c['topic_id']] = $c;
    }
    
    // Pass data to view
    $this->data['assessment'] = $assessment;
    $this->data['syllabus'] = $syllabus;
    $this->data['covered_topics'] = $covered_topics;
    $this->data['transfer_id'] = $transfer_id;
    $this->data['title'] = 'Transfer Assessment - ' . $assessment['first_name'] . ' ' . $assessment['last_name'];
    $this->data['sub_page'] = 'stars/assessment_detail';
    $this->data['main_menu'] = 'stars';
    
    $this->load->view('layout/index', $this->data);
}

public function reassign_teacher()
{
    // Check if AJAX request
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    // Only admin or superadmin can reassign
    if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => 'Access denied. Admin only.']));
        return;
    }
    
    // Get input
    $missing_topic_id = $this->input->post('missing_topic_id');
    $teacher_id = $this->input->post('teacher_id');
    $reason = $this->input->post('reason');
    
    // Validate
    if (empty($missing_topic_id) || empty($teacher_id)) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => 'Missing required fields']));
        return;
    }
    
    // Get old teacher name for logging
    $old_teacher = $this->db->select('stf.name')
        ->from('iarp_missing_topics imt')
        ->join('staff stf', 'stf.id = imt.assigned_teacher_id', 'left')
        ->where('imt.id', $missing_topic_id)
        ->get()
        ->row_array();
    
    // Update with new teacher
    $data = array(
        'assigned_teacher_id' => $teacher_id,
        'status' => 'in_progress',
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->where('id', $missing_topic_id);
    $result = $this->db->update('iarp_missing_topics', $data);
    
    // Log the change for audit trail
    $new_teacher = $this->db->select('name')->where('id', $teacher_id)->get('staff')->row_array();
    
    $log_message = 'Teacher reassigned for topic ID: ' . $missing_topic_id . 
                   '. Old: ' . ($old_teacher['name'] ?? 'None') . 
                   ', New: ' . ($new_teacher['name'] ?? 'Unknown') . 
                   '. Reason: ' . ($reason ?: 'Not provided');
    
    log_message('info', $log_message);
    
    // Store in audit log if you have an audit table
    // $this->db->insert('audit_logs', array('action' => 'reassign_teacher', 'details' => $log_message, 'user_id' => get_loggedin_user_id()));
    
    if ($result) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Teacher reassigned successfully']));
    } else {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => false, 'message' => 'Database update failed']));
    }
}
/**
 * Update subtopic progress (AJAX)
 */
public function update_subtopic_progress()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!get_permission('stars', 'is_edit')) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $iarp_id = $this->input->post('iarp_id');
    $subtopic_ids = $this->input->post('subtopics_completed');
    $week_number = $this->input->post('week_number');
    $teacher_comments = $this->input->post('teacher_comments');
    
    if (empty($iarp_id) || empty($subtopic_ids)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    // Update each subtopic as completed
    $updated = 0;
    foreach ($subtopic_ids as $subtopic_id) {
        $subtopic = $this->db->select('topic_id')->where('id', $subtopic_id)->get('curriculum_subtopics')->row();
        if (!$subtopic) continue;
        
        $data = array(
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'week_number' => $week_number,
            'notes' => $teacher_comments,
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        $this->db->where('iarp_id', $iarp_id);
        $this->db->where('subtopic_id', $subtopic_id);
        
        if ($this->db->count_all_results('iarp_subtopic_progress') > 0) {
            $this->db->where('iarp_id', $iarp_id);
            $this->db->where('subtopic_id', $subtopic_id);
            $this->db->update('iarp_subtopic_progress', $data);
        } else {
            $data['iarp_id'] = $iarp_id;
            $data['subtopic_id'] = $subtopic_id;
            $data['topic_id'] = $subtopic->topic_id;
            $this->db->insert('iarp_subtopic_progress', $data);
        }
        $updated++;
        
        // After updating subtopics, check if this completed a topic
        $this->stars_model->check_and_complete_topic($iarp_id, $subtopic->topic_id);

        // Then check if all topics are completed (auto-close)
        $close_result = $this->stars_model->auto_close_if_complete($iarp_id);

        if ($close_result && is_array($close_result) && $close_result['success']) {
            $this->load->library('stars_sms');
            $this->stars_sms->send_completion($close_result['iarp_id'], $close_result['branch_id']);
            log_message('info', 'Completion SMS sent for IARP: ' . $close_result['iarp_id']);
        }
    }
    
    // ========== RECALCULATE PROGRESS BASED ON TOPICS ==========
    $student_id = $this->db->select('student_id')->where('id', $iarp_id)->get('iarp_plans')->row()->student_id;
    
    // Get total topics for this IARP
    $total_topics = $this->db->where('iarp_id', $iarp_id)->count_all_results('iarp_missing_topics');
    
    // Get completed topics count (status = 'completed')
    $completed_topics = $this->db->where('iarp_id', $iarp_id)
                                  ->where('status', 'completed')
                                  ->count_all_results('iarp_missing_topics');
    
    // Calculate remaining topics
    $remaining_topics = $total_topics - $completed_topics;
    
    // Calculate improvement percentage
    $improvement_percentage = $total_topics > 0 ? ($completed_topics / $total_topics) * 100 : 0;
    
    // Determine status
    $progress_status = 'on_track';
    if ($remaining_topics > $total_topics / 2) {
        $progress_status = 'significantly_behind';
    } elseif ($remaining_topics > $total_topics / 4) {
        $progress_status = 'slightly_behind';
    }
    
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('sunday this week'));
    
    $progress_data = array(
        'iarp_id' => $iarp_id,
        'student_id' => $student_id,
        'week_number' => $week_number,
        'week_start_date' => $week_start,
        'week_end_date' => $week_end,
        'topics_covered_count' => $completed_topics,
        'topics_remaining_count' => $remaining_topics,
        'improvement_percentage' => $improvement_percentage,
        'status' => $progress_status,
        'teacher_comments' => $teacher_comments,
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->where('iarp_id', $iarp_id);
    $this->db->where('week_number', $week_number);
    $existing = $this->db->get('recovery_progress')->num_rows();
    
    if ($existing > 0) {
        $this->db->where('iarp_id', $iarp_id)->where('week_number', $week_number)->update('recovery_progress', $progress_data);
    } else {
        $progress_data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('recovery_progress', $progress_data);
    }
    // ========== END PROGRESS CALCULATION ==========
    
    echo json_encode(['success' => true, 'updated' => $updated, 'completed_topics' => $completed_topics, 'remaining_topics' => $remaining_topics]);
}

/**
 * Manage subtopics page
 */
public function manage_subtopics()
{
    if (!get_permission('stars', 'is_view') && !is_admin_loggedin() && !is_superadmin_loggedin()) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    $this->data['branch_id'] = $branch_id;
    $this->data['title'] = 'Manage Subtopics';
    $this->data['sub_page'] = 'stars/manage_subtopics';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

/**
 * Get subtopics for a topic (AJAX)
 */
public function get_subtopics_ajax()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    $topic_id = $this->input->post('topic_id');
    $branch_id = $this->input->post('branch_id');
    
    $subtopics = $this->stars_model->get_subtopics_by_topic($topic_id, $branch_id);
    
    echo json_encode($subtopics);
}

/**
 * Add subtopic (AJAX)
 */
public function add_subtopic_ajax()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $data = array(
        'topic_id' => $this->input->post('topic_id'),
        'subtopic_name' => $this->input->post('subtopic_name'),
        'subtopic_code' => $this->input->post('subtopic_code'),
        'subtopic_order' => $this->input->post('subtopic_order') ?: 0,
        'expected_hours' => $this->input->post('expected_hours') ?: 1.0,
        'branch_id' => $this->input->post('branch_id'),
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $result = $this->db->insert('curriculum_subtopics', $data);
    echo json_encode(['success' => $result, 'id' => $result ? $this->db->insert_id() : null]);
}

/**
 * Delete subtopic (AJAX)
 */
public function delete_subtopic_ajax()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }
    
    if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $subtopic_id = $this->input->post('subtopic_id');
    
    // Check if used in any IARP
    $used = $this->db->where('subtopic_id', $subtopic_id)->count_all_results('iarp_subtopic_progress');
    
    if ($used > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete: Subtopic is already used in recovery plans']);
        return;
    }
    
    $result = $this->db->delete('curriculum_subtopics', array('id' => $subtopic_id));
    echo json_encode(['success' => $result]);
}
public function check_and_close_iarp($iarp_id)
{
    if (!get_permission('stars', 'is_edit')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    // Get completion stats from model
    $stats = $this->stars_model->get_iarp_completion_stats($iarp_id);
    
    // If all topics completed
    if ($stats['total'] > 0 && $stats['completed'] >= $stats['total']) {
        
        // Close the IARP
        $result = $this->stars_model->close_recovery(
            $iarp_id,
            null, // student_id will be fetched in model
            $this->session->userdata('loggedin_userid'),
            'goals_achieved',
            'Auto-closed: All topics completed'
        );
        
        if ($result) {
            // Send completion SMS
            $this->stars_sms->send_completion($iarp_id, $branch_id);
            
            return true;
        }
    }
    
    return false;
}
/**
 * Student/Parent view - My IARP
 */
public function my_iarp()
{
    // Check permission
    if (!is_student_loggedin() && !is_parent_loggedin()) {
        access_denied();
    }
    
    // Get student ID (no DB in controller)
    $student_id = is_student_loggedin() ? get_loggedin_user_id() : get_activeChildren_id();
    
    // Call model (no DB in controller)
    $iarp = $this->stars_model->get_active_iarp_for_student($student_id);
    
    if (empty($iarp)) {
        set_alert('info', 'No active recovery plan found.');
        redirect('dashboard');
        return;
    }
    
    // Get full IARP details from model
    $iarp_details = $this->stars_model->get_iarp_details($iarp['id']);
    
    // Pass data to view
    $this->data['iarp'] = $iarp_details;
    $this->data['title'] = 'My Recovery Plan';
    $this->data['sub_page'] = 'stars/my_iarp';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

/**
 * Student/Parent view - My Resources
 */
public function my_resources()
{
    if (!is_student_loggedin() && !is_parent_loggedin()) {
        access_denied();
    }
    
    $student_id = is_student_loggedin() ? get_loggedin_user_id() : get_activeChildren_id();
    
    // Call model (no DB in controller)
    $resources = $this->stars_model->get_resources_for_student($student_id);
    
    $this->data['resources'] = $resources;
    $this->data['title'] = 'Learning Resources';
    $this->data['sub_page'] = 'stars/my_resources';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

/**
 * Student/Parent view - My Mentor
 */
public function my_mentor()
{
    if (!is_student_loggedin() && !is_parent_loggedin()) {
        access_denied();
    }
    
    $student_id = is_student_loggedin() ? get_loggedin_user_id() : get_activeChildren_id();
    
    // Call model (no DB in controller)
    $mentors = $this->stars_model->get_mentors_for_student($student_id);
    
    $this->data['mentors'] = $mentors;
    $this->data['title'] = 'My Mentor';
    $this->data['sub_page'] = 'stars/my_mentor';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}
/**
 * Student/Parent view - My Weekly Progress
 */
public function my_progress()
{
    if (!is_student_loggedin() && !is_parent_loggedin()) {
        access_denied();
    }
    
    $student_id = is_student_loggedin() ? get_loggedin_user_id() : get_activeChildren_id();
    
    $iarp = $this->stars_model->get_active_iarp_for_student($student_id);
    
    if (!empty($iarp)) {
        $iarp_details = $this->stars_model->get_iarp_details($iarp['id']);
        $this->data['iarp'] = $iarp_details;
    } else {
        $this->data['iarp'] = null;
    }
    
    $this->data['title'] = 'My Weekly Progress';
    $this->data['sub_page'] = 'stars/my_progress';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}
/**
 * Create recovery assessment for existing student
 */
public function create_for_existing($student_id)
{
    if (!get_permission('stars', 'is_add')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    // Verify student exists and belongs to this branch
    $this->db->where('id', $student_id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branch_id);
    }
    $student = $this->db->get('student')->row_array();
    
    if (empty($student)) {
        $this->session->set_flashdata('error', 'Student not found');
        redirect('student/view');
        return;
    }
    
    // Create transfer assessment
    $transfer_id = $this->stars_model->create_for_existing_student($student_id, $this->session->userdata('loggedin_userid'));
    
    if ($transfer_id) {
        $this->session->set_flashdata('success', '✅ Recovery assessment created successfully!');
        redirect('stars/assessments');
    } else {
        $this->session->set_flashdata('warning', '⚠️ This student already has an active recovery assessment.');
        redirect('stars/assessments');
    }
}

}