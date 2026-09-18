<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Stars_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create or get transfer assessment for an admission
     */
    public function get_or_create_transfer_assessment($admission_id, $branch_id, $student_id = null)
    {
        $this->db->where('admission_id', $admission_id);
        $existing = $this->db->get('transfer_assessments')->row_array();
        
        if ($existing) {
            return $existing;
        }
        
        // Get admission details
        $admission = $this->db->get_where('online_admission', ['id' => $admission_id])->row_array();
        
        $data = [
            'admission_id' => $admission_id,
            'student_id' => $student_id,
            'branch_id' => $branch_id,
            'previous_school_name' => $admission['previous_school_details'] ?? null,
            'transfer_date' => date('Y-m-d'),
            'joining_class_id' => $admission['class_id'],
            'joining_section_id' => $admission['section_id'] ?? null,
            'joining_term_id' => $this->get_current_term_id($branch_id),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('transfer_assessments', $data);
        return $this->db->insert_id();
    }
    
   
    
    /**
     * Get class syllabus (all topics)
     */
    public function get_class_syllabus($class_id, $branch_id)
    {
        $this->db->select('ct.*, s.name as subject_name');
        $this->db->from('curriculum_topics ct');
        $this->db->join('subject s', 's.id = ct.subject_id');
        $this->db->where('ct.class_id', $class_id);
        $this->db->where('ct.branch_id', $branch_id);
        $this->db->order_by('ct.subject_id', 'ASC');
        $this->db->order_by('ct.topic_order', 'ASC');
        return $this->db->get()->result_array();
    }
    
    /**
     * Save student topic coverage (what they already know)
     */
    public function save_topic_coverage($transfer_id, $student_id, $topic_id, $status, $notes = null, $verified_by = null)
    {
        $data = [
            'student_id' => $student_id,
            'transfer_assessment_id' => $transfer_id,
            'topic_id' => $topic_id,
            'coverage_status' => $status,
            'assessment_notes' => $notes,
            'verified_by' => $verified_by,
            'verified_at' => $verified_by ? date('Y-m-d H:i:s') : null
        ];
        
        $this->db->where('student_id', $student_id);
        $this->db->where('topic_id', $topic_id);
        $existing = $this->db->get('student_topic_coverage')->row();
        
        if ($existing) {
            $this->db->where('id', $existing->id);
            return $this->db->update('student_topic_coverage', $data);
        } else {
            return $this->db->insert('student_topic_coverage', $data);
        }
    }
    
    /**
     * Run gap analysis for a student
     */
    public function run_gap_analysis($transfer_id, $student_id, $class_id, $branch_id)
    {
        log_message('info', 'STARS Model: run_gap_analysis called');
    log_message('info', 'Transfer ID: ' . $transfer_id . ', Student ID: ' . $student_id . ', Class ID: ' . $class_id . ', Branch ID: ' . $branch_id);
        // Get all topics for this class
        $topics = $this->get_class_syllabus($class_id, $branch_id);
        
        // Group by subject
        $subjects = [];
        foreach ($topics as $topic) {
            if (!isset($subjects[$topic['subject_id']])) {
                $subjects[$topic['subject_id']] = [
                    'subject_name' => $topic['subject_name'],
                    'total_topics' => 0,
                    'covered_topics' => 0,
                    'missing_topic_ids' => []
                ];
            }
            $subjects[$topic['subject_id']]['total_topics']++;
            
            // Check if student has covered this topic
            $this->db->where('student_id', $student_id);
            $this->db->where('topic_id', $topic['id']);
            $coverage = $this->db->get('student_topic_coverage')->row();
            
            if ($coverage && in_array($coverage->coverage_status, ['mastered', 'partial'])) {
                $subjects[$topic['subject_id']]['covered_topics']++;
            } else {
                $subjects[$topic['subject_id']]['missing_topic_ids'][] = $topic['id'];
            }
        }
        
        // Calculate gaps and save
        $results = [];
        foreach ($subjects as $subject_id => $data) {
            $missing = $data['total_topics'] - $data['covered_topics'];
            $percentage = $data['total_topics'] > 0 ? ($missing / $data['total_topics']) * 100 : 0;
            
            $severity = 'green';
            if ($percentage > 50) $severity = 'red';
            elseif ($percentage > 25) $severity = 'amber';
            
            $gap_data = [
                'transfer_assessment_id' => $transfer_id,
                'student_id' => $student_id,
                'subject_id' => $subject_id,
                'total_topics_class' => $data['total_topics'],
                'covered_topics_student' => $data['covered_topics'],
                'missing_topics' => $missing,
                'gap_percentage' => $percentage,
                'severity' => $severity,
                'missing_topic_ids' => json_encode($data['missing_topic_ids'])
            ];
            
            // Check if exists
            $this->db->where('transfer_assessment_id', $transfer_id);
            $this->db->where('subject_id', $subject_id);
            $existing = $this->db->get('gap_analysis_results')->row();
            
            if ($existing) {
                $this->db->where('id', $existing->id);
                $this->db->update('gap_analysis_results', $gap_data);
            } else {
                $this->db->insert('gap_analysis_results', $gap_data);
            }
            
            $results[] = $gap_data;
        }
        
        return $results;
    }
    
    public function generate_iarp($transfer_id, $student_id, $created_by)
{
    log_message('info', 'STARS Model: generate_iarp called for transfer_id: ' . $transfer_id);
    
    // Get gap analysis results
    $this->db->where('transfer_assessment_id', $transfer_id);
    $this->db->where('severity !=', 'green');
    $gaps = $this->db->get('gap_analysis_results')->result_array();
    
    if (empty($gaps)) {
        log_message('info', 'STARS Model: No gaps found, cannot generate IARP');
        return false;
    }
    
    // Calculate total missing topics
    $missing_topics = [];
    foreach ($gaps as $gap) {
        $topic_ids = json_decode($gap['missing_topic_ids'], true);
        if (is_array($topic_ids)) {
            foreach ($topic_ids as $tid) {
                $missing_topics[] = $tid;
            }
        }
    }
    
    $total_missing = count($missing_topics);
    $estimated_hours = $total_missing * 1.5;
    
    // Generate plan code
    $plan_code = 'IARP-' . date('Y') . '-' . str_pad($transfer_id, 5, '0', STR_PAD_LEFT);
    
    // Calculate dates
    $start_date = date('Y-m-d');
    $target_end_date = date('Y-m-d', strtotime("+{$estimated_hours} days"));
    
    $iarp_data = [
        'transfer_assessment_id' => $transfer_id,
        'student_id' => $student_id,
        'plan_code' => $plan_code,
        'start_date' => $start_date,
        'target_end_date' => $target_end_date,
        'status' => 'draft',
        'created_by' => $created_by,
        'notes' => "Auto-generated IARP with {$total_missing} missing topics across " . count($gaps) . " subjects.",
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $this->db->insert('iarp_plans', $iarp_data);
    $iarp_id = $this->db->insert_id();
    
    log_message('info', 'STARS Model: IARP created with ID: ' . $iarp_id . ', Plan Code: ' . $plan_code);
    
    // Add missing topics to IARP
    foreach ($gaps as $gap) {
        $topic_ids = json_decode($gap['missing_topic_ids'], true);
        if (is_array($topic_ids)) {
            foreach ($topic_ids as $topic_id) {
                $this->db->insert('iarp_missing_topics', [
                    'iarp_id' => $iarp_id,
                    'topic_id' => $topic_id,
                    'subject_id' => $gap['subject_id'],
                    'priority' => $gap['severity'] == 'red' ? 'high' : ($gap['severity'] == 'amber' ? 'medium' : 'low'),
                    'estimated_hours' => 1.5,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }
    
    return $iarp_id;
}
public function get_teachers_by_branch($branch_id = null)
{
    $this->db->select('staff.id, staff.name, staff.branch_id')
             ->from('staff')
             ->join('login_credential lc', 'lc.user_id = staff.id', 'inner')
             ->where('lc.role', 3)
             ->order_by('staff.name', 'ASC');
    
    // Apply filter ONLY if branch_id is provided (not null and not empty)
    if (!empty($branch_id)) {
        $this->db->where('staff.branch_id', $branch_id);
        log_message('info', 'get_teachers_by_branch - Filtering by branch: ' . $branch_id);
    } else {
        log_message('info', 'get_teachers_by_branch - No branch filter (showing all)');
    }
    
    $query = $this->db->get();
    $result = $query->result_array();
    log_message('info', 'get_teachers_by_branch - Found ' . count($result) . ' teachers');
    
    return $result;
}
/**
 * Get subjects by branch (with branch isolation)
 * @param int $branch_id
 * @return array
 */
public function get_subjects_by_branch($branch_id = null)
{
    $this->db->select('id, name, subject_code')
             ->from('subject')
             ->order_by('name', 'ASC');
    
    // Apply filter only if branch_id is provided
    if (!empty($branch_id)) {
        $this->db->where('branch_id', $branch_id);
        log_message('info', 'get_subjects_by_branch - Filtering by branch: ' . $branch_id);
    } else {
        log_message('info', 'get_subjects_by_branch - No branch filter (showing all)');
    }
    
    $query = $this->db->get();
    return $query->result_array();
}
public function get_iarp_details($iarp_id)
{
    log_message('info', 'get_iarp_details - IARP ID: ' . $iarp_id);
    
    // Get IARP basic info
    $sql = "SELECT i.*, 
                   s.id as student_id, s.first_name, s.last_name, s.register_no,
                   c.id as class_id, c.name as class_name,
                   ta.branch_id
            FROM iarp_plans i
            LEFT JOIN student s ON s.id = i.student_id
            LEFT JOIN transfer_assessments ta ON ta.id = i.transfer_assessment_id
            LEFT JOIN class c ON c.id = ta.joining_class_id
            WHERE i.id = ?";
    
    $query = $this->db->query($sql, array($iarp_id));
    
    if ($query->num_rows() == 0) {
        log_message('error', 'No IARP found for ID: ' . $iarp_id);
        return null;
    }
    
    $iarp = $query->row_array();
    
    // Initialize arrays
    $iarp['missing_topics'] = array();
    $iarp['resources'] = array();
    $iarp['progress'] = array();
    
    // Get missing topics (only if iarp_id exists)
    if (!empty($iarp['id'])) {
        $missing_sql = "SELECT imt.*, 
                               COALESCE(ct.topic_name, 'Unknown Topic') as topic_name, 
                               COALESCE(s.name, 'Unknown Subject') as subject_name,
                               stf.name as teacher_name
                        FROM iarp_missing_topics imt
                        LEFT JOIN curriculum_topics ct ON ct.id = imt.topic_id
                        LEFT JOIN subject s ON s.id = imt.subject_id
                        LEFT JOIN staff stf ON stf.id = imt.assigned_teacher_id
                        WHERE imt.iarp_id = ?
                        ORDER BY FIELD(imt.status, 'pending', 'in_progress', 'completed'), 
                                 FIELD(imt.priority, 'high', 'medium', 'low')";
        
        $missing_query = $this->db->query($missing_sql, array($iarp['id']));
        $iarp['missing_topics'] = $missing_query->result_array();
        log_message('info', 'Missing topics count: ' . count($iarp['missing_topics']));
    }
    
    // Get resources
    $resource_sql = "SELECT * FROM resource_recovery WHERE iarp_id = ?";
    $resource_query = $this->db->query($resource_sql, array($iarp_id));
    $iarp['resources'] = $resource_query->result_array();
    
    // Get progress
    $progress_sql = "SELECT * FROM recovery_progress WHERE iarp_id = ? ORDER BY week_number ASC";
    $progress_query = $this->db->query($progress_sql, array($iarp_id));
    $iarp['progress'] = $progress_query->result_array();
    
    // Get guardian info if student exists
    if (!empty($iarp['student_id'])) {
        $guardian_sql = "SELECT p.name as guardian_name, p.mobileno as guardian_mobile
                         FROM parent p
                         INNER JOIN student s ON s.parent_id = p.id
                         WHERE s.id = ?";
        $guardian_query = $this->db->query($guardian_sql, array($iarp['student_id']));
        if ($guardian_query->num_rows() > 0) {
            $guardian = $guardian_query->row_array();
            $iarp['guardian_name'] = $guardian['guardian_name'];
            $iarp['guardian_mobile'] = $guardian['guardian_mobile'];
        } else {
            $iarp['guardian_name'] = 'Not available';
            $iarp['guardian_mobile'] = 'N/A';
        }
    }
    
    // Get created by name
    if (!empty($iarp['created_by'])) {
        $staff_sql = "SELECT name FROM staff WHERE id = ?";
        $staff_query = $this->db->query($staff_sql, array($iarp['created_by']));
        if ($staff_query->num_rows() > 0) {
            $iarp['created_by_name'] = $staff_query->row()->name;
        } else {
            $iarp['created_by_name'] = 'System';
        }
    }
    
    return $iarp;
}

/**
 * Get branch ID from student ID
 * @param int $student_id
 * @return int|null
 */
public function get_branch_id_by_student($student_id)
{
    if (empty($student_id)) {
        return null;
    }
    
    $this->db->select('branch_id');
    $this->db->from('student');
    $this->db->where('id', $student_id);
    $query = $this->db->get();
    
    if ($query->num_rows() > 0) {
        return $query->row()->branch_id;
    }
    
    return null;
}
/**
 * Get available mentor students for a class
 * @param int $class_id
 * @param int $transfer_student_id
 * @param int $branch_id
 * @return array
 */
public function get_available_mentors($class_id, $transfer_student_id, $branch_id = null)
{
    log_message('info', 'get_available_mentors - Class ID: ' . $class_id . ', Branch ID: ' . $branch_id . ', Exclude Student: ' . $transfer_student_id);
    
    if (empty($class_id)) {
        return array();
    }
    
    // If branch_id is not provided, try to get it from the student
    if (empty($branch_id) && !empty($transfer_student_id)) {
        $branch_id = $this->get_branch_id_by_student($transfer_student_id);
        log_message('info', 'Branch ID retrieved from student: ' . $branch_id);
    }
    
    // If still empty, try to get from enroll table using class_id
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
    
    $session_id = get_session_id();
    
    // Build query
    $this->db->select('s.id, s.first_name, s.last_name, e.roll')
             ->from('student s')
             ->join('enroll e', 'e.student_id = s.id')
             ->where('e.class_id', $class_id)
             ->where('e.session_id', $session_id)
             ->where('s.id !=', $transfer_student_id)
             ->order_by('e.roll', 'ASC');
    
    // Apply branch filter only if branch_id is provided
    if (!empty($branch_id)) {
        $this->db->where('e.branch_id', $branch_id);
        log_message('info', 'Applying branch filter: ' . $branch_id);
    }
    
    $query = $this->db->get();
    $students = $query->result_array();
    
    log_message('info', 'get_available_mentors - Found ' . count($students) . ' students');
    
    return $students;
}
    /**
 * Add peer mentorship
 */
    /**
 * Add peer mentorship
 */
public function add_mentorship($data)
{
    log_message('info', 'add_mentorship - Data received: ' . print_r($data, true));
    
    // Get branch_id from IARP if not provided
    if (empty($data['branch_id']) && !empty($data['iarp_id'])) {
        $data['branch_id'] = $this->get_branch_id_from_iarp($data['iarp_id']);
        log_message('info', 'add_mentorship - Branch ID retrieved from IARP: ' . $data['branch_id']);
    }
    
    // Validate branch_id
    if (empty($data['branch_id'])) {
        log_message('error', 'add_mentorship - branch_id is missing');
        return array('success' => false, 'message' => 'Branch ID is missing');
    }
    
    // Check if mentorship already exists
    $exists = $this->db->where('transfer_student_id', $data['transfer_student_id'])
                       ->where('mentor_student_id', $data['mentor_student_id'])
                       ->where('status', 'active')
                       ->get('peer_mentorships')
                       ->num_rows();
    
    if ($exists > 0) {
        return array('success' => false, 'message' => 'Mentorship already exists');
    }
    
    // Prepare insert data
    $insert_data = array(
        'transfer_student_id' => $data['transfer_student_id'],
        'mentor_student_id' => $data['mentor_student_id'],
        'iarp_id' => !empty($data['iarp_id']) ? $data['iarp_id'] : null,
        'subject_id' => !empty($data['subject_id']) ? $data['subject_id'] : null,
        'assigned_by' => $data['assigned_by'],
        'assigned_date' => date('Y-m-d'),
        'meetings_count' => 0,
        'next_meeting_date' => null,
        'status' => 'active',
        'end_date' => null,
        'notes' => null,
        'branch_id' => $data['branch_id'],
        'created_at' => date('Y-m-d H:i:s')
    );
    
    log_message('info', 'add_mentorship - Insert data: ' . print_r($insert_data, true));
    
    $result = $this->db->insert('peer_mentorships', $insert_data);
    
    if ($result) {
        $insert_id = $this->db->insert_id();
        log_message('info', 'add_mentorship - Success, ID: ' . $insert_id);
        return array('success' => true, 'message' => 'Mentor assigned successfully');
    } else {
        $error = $this->db->error();
        log_message('error', 'add_mentorship - Database error: ' . print_r($error, true));
        return array('success' => false, 'message' => 'Database error: ' . $error['message']);
    }
}
    
    /**
 * Get branch ID from IARP
 */
public function get_branch_id_from_iarp($iarp_id)
{
    if (empty($iarp_id)) {
        return null;
    }
    
    $this->db->select('ta.branch_id');
    $this->db->from('iarp_plans i');
    $this->db->join('transfer_assessments ta', 'ta.id = i.transfer_assessment_id');
    $this->db->where('i.id', $iarp_id);
    
    $query = $this->db->get();
    
    if ($query->num_rows() > 0) {
        return $query->row()->branch_id;
    }
    
    return null;
}
    /**
 * Log mentorship meeting
 */
    /**
 * Log mentorship meeting
 */
public function log_meeting($mentorship_id, $topics_covered, $created_by)
{
    log_message('info', 'log_meeting - Mentorship ID: ' . $mentorship_id . ', Topics: ' . $topics_covered);
    
    // Check if mentorship exists
    $mentorship = $this->db->where('id', $mentorship_id)->get('peer_mentorships')->row_array();
    
    if (empty($mentorship)) {
        log_message('error', 'log_meeting - Mentorship not found: ' . $mentorship_id);
        return array('success' => false, 'message' => 'Mentorship not found');
    }
    
    // Insert meeting record
    $data = array(
        'mentorship_id' => $mentorship_id,
        'meeting_date' => date('Y-m-d H:i:s'),
        'topics_covered' => $topics_covered,
        'created_by' => $created_by,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $result = $this->db->insert('mentorship_meetings', $data);
    
    if ($result) {
        // Update meeting count in peer_mentorships
        $this->db->set('meetings_count', 'meetings_count + 1', false);
        $this->db->where('id', $mentorship_id);
        $this->db->update('peer_mentorships');
        
        log_message('info', 'log_meeting - Success, meeting logged for mentorship: ' . $mentorship_id);
        return array('success' => true, 'message' => 'Meeting logged successfully');
    } else {
        $error = $this->db->error();
        log_message('error', 'log_meeting - Database error: ' . print_r($error, true));
        return array('success' => false, 'message' => 'Database error: ' . $error['message']);
    }
}

    private function get_total_weeks_estimate($total_topics)
    {
        return ceil($total_topics / 3); // 3 topics per week average
    }
    
    /**
     * Close recovery plan
     */
    public function close_recovery($iarp_id, $student_id, $closed_by, $reason = 'goals_achieved', $notes = null)
    {
        // Get IARP details
        $iarp = $this->get_iarp_details($iarp_id);
        
        // Get final gap analysis
        $this->db->where('student_id', $student_id);
        $gaps = $this->db->get('gap_analysis_results')->result_array();
        
        $total_gap = 0;
        $gap_count = 0;
        foreach ($gaps as $gap) {
            $total_gap += $gap['gap_percentage'];
            $gap_count++;
        }
        $final_gap = $gap_count > 0 ? $total_gap / $gap_count : 0;
        
        // Get total topics mastered
        $this->db->where('iarp_id', $iarp_id);
        $this->db->where('status', 'completed');
        $mastered = $this->db->count_all_results('iarp_missing_topics');
        
        // Get total topics in plan
        $this->db->where('iarp_id', $iarp_id);
        $total = $this->db->count_all_results('iarp_missing_topics');
        
        // Create closure log
        $closure_data = [
            'iarp_id' => $iarp_id,
            'student_id' => $student_id,
            'closure_date' => date('Y-m-d H:i:s'),
            'closure_reason' => $reason,
            'final_gap_percentage' => $final_gap,
            'topics_mastered' => $mastered,
            'total_topics' => $total,
            'achievement_summary' => $notes ?: "Recovery plan completed. {$mastered} of {$total} topics mastered.",
            'closed_by' => $closed_by
        ];
        $this->db->insert('recovery_closure_logs', $closure_data);
        
        // Update IARP status
        $this->db->where('id', $iarp_id);
        $this->db->update('iarp_plans', [
            'status' => 'completed',
            'actual_end_date' => date('Y-m-d'),
            'status_updated_by' => $closed_by,
            'status_updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Update transfer assessment status
        $this->db->where('id', $iarp['transfer_assessment_id']);
        $this->db->update('transfer_assessments', ['status' => 'closed']);
        
        return true;
    }
    
   /**
 * Get dashboard stats for DOS/Principal - OPTIMIZED VERSION
 */
public function get_dashboard_stats($branch_id = null)
{
    log_message('info', '=== get_dashboard_stats START ===');
    log_message('info', 'Branch ID: ' . ($branch_id ?? 'NULL'));
    
    $result = array();
    
    // QUERY 1: Active recoveries - FIXED (use transfer_assessments for branch)
    $sql1 = "SELECT COUNT(*) as count 
             FROM iarp_plans i
             JOIN transfer_assessments ta ON ta.id = i.transfer_assessment_id
             WHERE i.status = 'active'";
    
    if (!empty($branch_id)) {
        $sql1 .= " AND ta.branch_id = " . (int)$branch_id;
    }
    
    $query1 = $this->db->query($sql1);
    $result['active_recoveries'] = $query1->row()->count;
    log_message('info', 'Active recoveries: ' . $result['active_recoveries']);
    
    // QUERY 2: Pending assessments
    $sql2 = "SELECT COUNT(*) as count 
             FROM transfer_assessments ta
             WHERE ta.status = 'pending'";
    
    if (!empty($branch_id)) {
        $sql2 .= " AND ta.branch_id = " . (int)$branch_id;
    }
    
    $query2 = $this->db->query($sql2);
    $result['pending_assessments'] = $query2->row()->count;
    log_message('info', 'Pending assessments: ' . $result['pending_assessments']);
    
    // QUERY 3: Gap summary
    $green = 0;
    $amber = 0;
    $red = 0;
    
    $sql3 = "SELECT g.severity, COUNT(*) as count
             FROM gap_analysis_results g
             JOIN transfer_assessments ta ON ta.id = g.transfer_assessment_id
             GROUP BY g.severity";
    
    if (!empty($branch_id)) {
        $sql3 = "SELECT g.severity, COUNT(*) as count
                 FROM gap_analysis_results g
                 JOIN transfer_assessments ta ON ta.id = g.transfer_assessment_id
                 WHERE ta.branch_id = " . (int)$branch_id . "
                 GROUP BY g.severity";
    }
    
    $query3 = $this->db->query($sql3);
    $gaps = $query3->result_array();
    
    foreach ($gaps as $gap) {
        switch($gap['severity']) {
            case 'green': $green = $gap['count']; break;
            case 'amber': $amber = $gap['count']; break;
            case 'red': $red = $gap['count']; break;
        }
    }
    $result['gap_summary'] = array('green' => $green, 'amber' => $amber, 'red' => $red);
    log_message('info', 'Gap summary: green=' . $green . ', amber=' . $amber . ', red=' . $red);
    
    // QUERY 4: At-risk students - FIXED
    $sql4 = "SELECT DISTINCT s.id, s.first_name, s.last_name, s.register_no, 
                    c.name as class_name, g.gap_percentage, ta.id as transfer_assessment_id
             FROM gap_analysis_results g
             JOIN transfer_assessments ta ON ta.id = g.transfer_assessment_id
             LEFT JOIN student s ON s.id = g.student_id
             LEFT JOIN class c ON c.id = ta.joining_class_id
             WHERE g.severity = 'red'";
    
    if (!empty($branch_id)) {
        $sql4 .= " AND ta.branch_id = " . (int)$branch_id;
    }
    
    $sql4 .= " ORDER BY g.gap_percentage DESC LIMIT 10";
    
    $query4 = $this->db->query($sql4);
    $result['at_risk_students'] = $query4->result_array();
    log_message('info', 'At-risk students count: ' . count($result['at_risk_students']));
    
    log_message('info', '=== get_dashboard_stats END ===');
    
    return $result;
}
/**
 * Get current active term ID
 */
public function get_current_term_id($branch_id)
{
    $this->db->select('id');
    $this->db->where('branch_id', $branch_id);
    $this->db->where('is_active', 1);
    $term = $this->db->get('exam_term')->row_array();
    
    if ($term) {
        return $term['id'];
    }
    
    // Fallback: get first term
    $this->db->select('id');
    $this->db->where('branch_id', $branch_id);
    $this->db->limit(1);
    $term = $this->db->get('exam_term')->row_array();
    return $term ? $term['id'] : 1;
}
/**
 * Get subtopics for a topic
 */
public function get_subtopics_by_topic($topic_id, $branch_id = null)
{
    $this->db->select('cs.*')
             ->from('curriculum_subtopics cs')
             ->where('cs.topic_id', $topic_id);
    
    if (!empty($branch_id)) {
        $this->db->where('cs.branch_id', $branch_id);
    }
    
    $this->db->order_by('cs.subtopic_order', 'ASC');
    return $this->db->get()->result_array();
}

/**
 * Get all subtopics for an IARP (pending only)
 */
public function get_pending_subtopics_for_iarp($iarp_id)
{
    $sql = "SELECT cs.*, ct.topic_name, ct.id as topic_id,
                   COALESCE(isp.status, 'pending') as progress_status,
                   isp.id as progress_id, isp.week_number as completed_week
            FROM curriculum_subtopics cs
            JOIN curriculum_topics ct ON ct.id = cs.topic_id
            JOIN iarp_missing_topics imt ON imt.topic_id = cs.topic_id
            LEFT JOIN iarp_subtopic_progress isp ON isp.subtopic_id = cs.id AND isp.iarp_id = imt.iarp_id
            WHERE imt.iarp_id = ? AND imt.status != 'completed'
            ORDER BY ct.topic_name, cs.subtopic_order";
    
    return $this->db->query($sql, array($iarp_id))->result_array();
}

/**
 * Initialize subtopics for IARP when generated
 */
public function initialize_iarp_subtopics($iarp_id, $topic_ids)
{
    if (empty($topic_ids)) {
        return 0;
    }
    
    $inserted = 0;
    foreach ($topic_ids as $topic_id) {
        $subtopics = $this->get_subtopics_by_topic($topic_id);
        foreach ($subtopics as $subtopic) {
            $exists = $this->db->where('iarp_id', $iarp_id)
                               ->where('subtopic_id', $subtopic['id'])
                               ->get('iarp_subtopic_progress')
                               ->num_rows();
            
            if ($exists == 0) {
                $this->db->insert('iarp_subtopic_progress', array(
                    'iarp_id' => $iarp_id,
                    'subtopic_id' => $subtopic['id'],
                    'topic_id' => $topic_id,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ));
                $inserted++;
            }
        }
    }
    return $inserted;
}

/**
 * Update subtopic progress
 */
public function update_subtopic_progress($iarp_id, $subtopic_ids, $week_number, $notes)
{
    $updated = 0;
    foreach ($subtopic_ids as $subtopic_id) {
        // Get topic_id for this subtopic
        $subtopic = $this->db->select('topic_id')->where('id', $subtopic_id)->get('curriculum_subtopics')->row();
        if (!$subtopic) continue;
        
        $data = array(
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'week_number' => $week_number,
            'notes' => $notes,
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
        
        // Check if all subtopics for this topic are completed
        $this->check_and_complete_topic($iarp_id, $subtopic->topic_id);
    }
    return $updated;
}

/**
 * Check if all subtopics of a topic are completed, then mark topic complete
 */
public function check_and_complete_topic($iarp_id, $topic_id)
{
    // Get total subtopics for this topic
    $total_subtopics = $this->db->where('topic_id', $topic_id)->count_all_results('curriculum_subtopics');
    
    // If no subtopics, skip (use existing topic logic)
    if ($total_subtopics == 0) {
        return false;
    }
    
    // Get completed subtopics count for this topic in this IARP
    $completed_subtopics = $this->db
        ->select('isp.id')
        ->from('iarp_subtopic_progress isp')
        ->join('curriculum_subtopics cs', 'cs.id = isp.subtopic_id')
        ->where('cs.topic_id', $topic_id)
        ->where('isp.iarp_id', $iarp_id)
        ->where('isp.status', 'completed')
        ->count_all_results();
    
    log_message('info', "Topic $topic_id: Total subtopics = $total_subtopics, Completed = $completed_subtopics");
    
    // If all subtopics completed, mark the topic as completed
    if ($completed_subtopics >= $total_subtopics) {
        $this->db->where('iarp_id', $iarp_id)
                 ->where('topic_id', $topic_id)
                 ->update('iarp_missing_topics', array(
                     'status' => 'completed',
                     'completed_at' => date('Y-m-d H:i:s'),
                     'updated_at' => date('Y-m-d H:i:s')
                 ));
        log_message('info', "Topic $topic_id marked as completed");
        return true;
    }
    
    return false;
}

/**
 * Check if all topics in IARP are completed, then auto-close
 */
/**
 * Auto-close IARP when all topics are completed
 */
public function auto_close_if_complete($iarp_id)
{
    log_message('info', 'auto_close_if_complete - Checking IARP ID: ' . $iarp_id);
    
    // Get total topics for this IARP
    $total_topics = $this->db->where('iarp_id', $iarp_id)->count_all_results('iarp_missing_topics');
    
    // Get completed topics count
    $completed_topics = $this->db->where('iarp_id', $iarp_id)
                                  ->where('status', 'completed')
                                  ->count_all_results('iarp_missing_topics');
    
    log_message('info', "Topics - Total: {$total_topics}, Completed: {$completed_topics}");
    
    // If not all topics completed, return false
    if ($total_topics == 0 || $completed_topics < $total_topics) {
        return false;
    }
    
    // Get IARP details for closure
    $iarp = $this->db->select('i.*, s.first_name, s.last_name, s.register_no, ta.branch_id, ta.id as transfer_assessment_id')
                     ->from('iarp_plans i')
                     ->join('student s', 's.id = i.student_id')
                     ->join('transfer_assessments ta', 'ta.id = i.transfer_assessment_id')
                     ->where('i.id', $iarp_id)
                     ->get()
                     ->row_array();
    
    if (empty($iarp)) {
        log_message('error', 'IARP not found for closure: ' . $iarp_id);
        return false;
    }
    
    // Start transaction
    $this->db->trans_start();
    
    // Update IARP status to completed
    $this->db->where('id', $iarp_id);
    $this->db->update('iarp_plans', array(
        'status' => 'completed',
        'actual_end_date' => date('Y-m-d'),
        'status_updated_by' => 1, // System auto-close
        'status_updated_at' => date('Y-m-d H:i:s')
    ));
    
    // Update transfer assessment status to closed
    $this->db->where('id', $iarp['transfer_assessment_id']);
    $this->db->update('transfer_assessments', array(
        'status' => 'closed',
        'updated_at' => date('Y-m-d H:i:s')
    ));
    
    // Create closure log
    $closure_data = array(
        'iarp_id' => $iarp_id,
        'student_id' => $iarp['student_id'],
        'closure_date' => date('Y-m-d H:i:s'),
        'closure_reason' => 'goals_achieved',
        'final_gap_percentage' => 0,
        'topics_mastered' => $total_topics,
        'total_topics' => $total_topics,
        'closed_by' => 1, // System
        'achievement_summary' => 'Auto-closed: All topics completed successfully.',
        'created_at' => date('Y-m-d H:i:s')
    );
    $this->db->insert('recovery_closure_logs', $closure_data);
    
    $this->db->trans_complete();
    
    if ($this->db->trans_status() === FALSE) {
        log_message('error', 'Transaction failed for auto-closing IARP: ' . $iarp_id);
        return false;
    }
    
    log_message('info', "IARP {$iarp_id} auto-closed successfully");
    
    // Return branch_id and iarp_id for SMS sending
    return array('success' => true, 'branch_id' => $iarp['branch_id'], 'iarp_id' => $iarp_id);
}

/**
 * Send completion SMS to parent
 */
private function send_completion_sms($iarp)
{
    // Get parent mobile from student
    $parent = $this->db->select('p.mobileno')
                       ->from('student s')
                       ->join('parent p', 'p.id = s.parent_id')
                       ->where('s.id', $iarp['student_id'])
                       ->get()
                       ->row_array();
    
    if (empty($parent) || empty($parent['mobileno'])) {
        log_message('info', "No parent mobile for student {$iarp['student_id']}");
        return;
    }
    
    // Get SMS template (ID 24 for completion)
    $template = $this->db->get_where('sms_template_details', array(
        'template_id' => 24,
        'branch_id' => $iarp['branch_id']
    ))->row_array();
    
    if (empty($template)) {
        log_message('info', "No completion SMS template found for branch {$iarp['branch_id']}");
        return;
    }
    
    // Prepare message
    $message = $template['template_body'];
    $message = str_replace('{student_name}', $iarp['first_name'] . ' ' . $iarp['last_name'], $message);
    $message = str_replace('{guardian_name}', 'Parent', $message);
    $message = str_replace('{completion_date}', date('d M Y'), $message);
    $message = str_replace('{achievement_summary}', 'All topics mastered successfully!', $message);
    
    $branch = $this->db->select('name')->where('id', $iarp['branch_id'])->get('branch')->row();
    $message = str_replace('{school_name}', $branch->name ?? 'School', $message);
    
    // Send SMS
    $this->load->library('bulksmsbd', array('branch_id' => $iarp['branch_id']), 'sms_lib');
    $this->sms_lib->send($parent['mobileno'], $message);
    
    log_message('info', "Completion SMS3 sent to {$parent['mobileno']}");
}
/**
 * Get parent details for SMS
 */
public function get_parent_for_sms($student_id)
{
    return $this->db->select('p.name, p.mobileno')
                    ->from('student s')
                    ->join('parent p', 'p.id = s.parent_id')
                    ->where('s.id', $student_id)
                    ->get()
                    ->row_array();
}

/**
 * Get IARP details for SMS
 */
public function get_iarp_for_sms($iarp_id)
{
    return $this->db->select('plan_code, start_date, target_end_date')
                    ->where('id', $iarp_id)
                    ->get('iarp_plans')
                    ->row_array();
}

/**
 * Get SMS template
 */
public function get_sms_template($template_id, $branch_id)
{
    return $this->db->get_where('sms_template_details', [
        'template_id' => $template_id,
        'branch_id' => $branch_id
    ])->row_array();
}

/**
 * Get branch contact info
 */
public function get_branch_contact($branch_id)
{
    return $this->db->select('name, mobileno, email')
                    ->where('id', $branch_id)
                    ->get('branch')
                    ->row_array();
}

/**
 * Get student and parent details for IARP SMS
 */
public function get_student_parent_for_iarp($iarp_id)
{
    $sql = "SELECT s.id as student_id, s.first_name, s.last_name, s.parent_id,
                   p.name as parent_name, p.mobileno as parent_mobile,
                   ta.branch_id
            FROM iarp_plans i
            JOIN student s ON s.id = i.student_id
            LEFT JOIN parent p ON p.id = s.parent_id
            JOIN transfer_assessments ta ON ta.id = i.transfer_assessment_id
            WHERE i.id = ?";
    
    return $this->db->query($sql, array($iarp_id))->row_array();
}

/**
 * Get IARP completion stats
 */
public function get_iarp_completion_stats($iarp_id)
{
    $total = $this->db->where('iarp_id', $iarp_id)->count_all_results('iarp_missing_topics');
    $completed = $this->db->where('iarp_id', $iarp_id)
                          ->where('status', 'completed')
                          ->count_all_results('iarp_missing_topics');
    
    return array('total' => $total, 'completed' => $completed);
}

/**
 * Prepare IARP created message
 */
public function prepare_iarp_created_message($template_body, $student, $parent, $iarp, $branch)
{
    $message = $template_body;
    $message = str_replace('{student_name}', $student['first_name'] . ' ' . $student['last_name'], $message);
    $message = str_replace('{guardian_name}', $parent['parent_name'], $message);
    $message = str_replace('{plan_code}', $iarp['plan_code'], $message);
    $message = str_replace('{start_date}', date('d M Y', strtotime($iarp['start_date'])), $message);
    $message = str_replace('{target_end_date}', date('d M Y', strtotime($iarp['target_end_date'])), $message);
    $message = str_replace('{school_phone}', $branch['mobileno'] ?? 'School Office', $message);
    
    return $message;
}

/**
 * Prepare completion message
 */
public function prepare_completion_message($template_body, $student, $parent, $stats, $branch)
{
    $message = $template_body;
    $message = str_replace('{student_name}', $student['first_name'] . ' ' . $student['last_name'], $message);
    $message = str_replace('{guardian_name}', $parent['parent_name'], $message);
    $message = str_replace('{completion_date}', date('d M Y'), $message);
    $message = str_replace('{achievement_summary}', "Completed {$stats['completed']} of {$stats['total']} topics", $message);
    $message = str_replace('{school_name}', $branch['name'] ?? 'School', $message);
    
    return $message;
}
/**
 * Get topics by class and subject (with branch isolation)
 * @param int $class_id
 * @param int $subject_id
 * @param int $branch_id
 * @return array
 */
public function get_topics_by_class_subject($class_id, $subject_id, $branch_id)
{
    if (empty($class_id) || empty($subject_id)) {
        return array();
    }
    
    $this->db->select('id, topic_name, topic_code, topic_order, expected_weeks, is_competency_based')
             ->from('curriculum_topics')
             ->where('class_id', $class_id)
             ->where('subject_id', $subject_id)
             ->where('branch_id', $branch_id)
             ->order_by('topic_order', 'ASC');
    
    $query = $this->db->get();
    $topics = $query->result_array();
    
    // Ensure numeric values are properly typed
    foreach ($topics as &$topic) {
        $topic['topic_order'] = (int)($topic['topic_order'] ?? 0);
        $topic['expected_weeks'] = (float)($topic['expected_weeks'] ?? 1.0);
        $topic['is_competency_based'] = (int)($topic['is_competency_based'] ?? 0);
    }
    
    return $topics;
}
/**
 * Get subjects by class - only subjects actually assigned to the class
 * Respects branch isolation. No fallback. Returns empty array if none.
 * @param int $class_id
 * @param int $branch_id
 * @return array
 */
public function get_subjects_by_class($class_id, $branch_id)
{
    log_message('info', 'get_subjects_by_class - Class: ' . $class_id . ', Branch: ' . $branch_id);
    
    if (empty($class_id)) {
        return array();
    }
    
    // Simplified query using raw SQL
    $sql = "SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = " . (int)$class_id . "
            AND sa.branch_id = " . (int)$branch_id . "
            ORDER BY s.name ASC";
    
    $query = $this->db->query($sql);
    
    log_message('info', 'SQL: ' . $sql);
    log_message('info', 'Rows: ' . $query->num_rows());
    
    $result = $query->result_array();
    
    log_message('info', 'Result: ' . print_r($result, true));
    
    return $result;
}
/**
 * Get active IARP for a student
 * @param int $student_id
 * @return array|null
 */
public function get_active_iarp_for_student($student_id)
{
    $this->db->select('i.*')
             ->from('iarp_plans i')
             ->where('i.student_id', $student_id)
             ->where_in('i.status', array('active', 'completed'))
             ->order_by('i.id', 'DESC')
             ->limit(1);
    
    $query = $this->db->get();
    return $query->num_rows() > 0 ? $query->row_array() : null;
}

/**
 * Get resources for a student
 * @param int $student_id
 * @return array
 */
public function get_resources_for_student($student_id)
{
    $this->db->select('r.*, s.name as subject_name')
             ->from('resource_recovery r')
             ->join('subject s', 's.id = r.subject_id', 'left')
             ->where('r.student_id', $student_id)
             ->order_by('r.created_at', 'DESC');
    
    $query = $this->db->get();
    return $query->result_array();
}

/**
 * Get mentors for a student
 * @param int $student_id
 * @return array
 */
public function get_mentors_for_student($student_id)
{
    $this->db->select('pm.*, s.first_name, s.last_name, s.register_no, sub.name as subject_name')
             ->from('peer_mentorships pm')
             ->join('student s', 's.id = pm.mentor_student_id')
             ->join('subject sub', 'sub.id = pm.subject_id', 'left')
             ->where('pm.transfer_student_id', $student_id)
             ->where('pm.status', 'active');
    
    $query = $this->db->get();
    return $query->result_array();
}
/**
 * Get complete IARP details for a student (including completed topics)
 * @param int $student_id
 * @return array|null
 */
public function get_complete_iarp_for_student($student_id)
{
    // Get the latest IARP
    $this->db->select('i.*')
             ->from('iarp_plans i')
             ->where('i.student_id', $student_id)
             ->order_by('i.id', 'DESC')
             ->limit(1);
    
    $query = $this->db->get();
    
    if ($query->num_rows() == 0) {
        return null;
    }
    
    $iarp = $query->row_array();
    
    // Get ALL missing topics (INCLUDING completed)
    $this->db->select('imt.*, ct.topic_name, ct.topic_code, s.name as subject_name, 
                      stf.name as teacher_name')
             ->from('iarp_missing_topics imt')
             ->join('curriculum_topics ct', 'ct.id = imt.topic_id', 'left')
             ->join('subject s', 's.id = imt.subject_id', 'left')
             ->join('staff stf', 'stf.id = imt.assigned_teacher_id', 'left')
             ->where('imt.iarp_id', $iarp['id'])
             ->order_by('imt.status', 'ASC')
             ->order_by('imt.priority', 'ASC');
    
    $all_topics = $this->db->get()->result_array();
    
    // Calculate statistics
    $total_topics = count($all_topics);
    $completed_topics = 0;
    $in_progress_topics = 0;
    $pending_topics = 0;
    
    foreach ($all_topics as $topic) {
        if ($topic['status'] == 'completed') {
            $completed_topics++;
        } elseif ($topic['status'] == 'in_progress') {
            $in_progress_topics++;
        } else {
            $pending_topics++;
        }
    }
    
    $progress_percent = $total_topics > 0 ? round(($completed_topics / $total_topics) * 100, 1) : 0;
    
    // Get progress records
    $this->db->where('iarp_id', $iarp['id']);
    $this->db->order_by('week_number', 'ASC');
    $progress_records = $this->db->get('recovery_progress')->result_array();
    
    return array(
        'iarp' => $iarp,
        'all_topics' => $all_topics,
        'total_topics' => $total_topics,
        'completed_topics' => $completed_topics,
        'in_progress_topics' => $in_progress_topics,
        'pending_topics' => $pending_topics,
        'progress_percent' => $progress_percent,
        'progress_records' => $progress_records
    );
}
/**
 * Get weekly progress with detailed completed and remaining items
 * @param int $iarp_id
 * @return array
 */
public function get_weekly_progress_with_details($iarp_id)
{
    // Get weekly progress records
    $this->db->where('iarp_id', $iarp_id);
    $this->db->order_by('week_number', 'ASC');
    $progress = $this->db->get('recovery_progress')->result_array();
    
    // For each week, get completed and remaining items
    foreach ($progress as &$week) {
        // Get completed subtopics for this week
        $this->db->select('cs.subtopic_name, ct.topic_name')
                 ->from('iarp_subtopic_progress isp')
                 ->join('curriculum_subtopics cs', 'cs.id = isp.subtopic_id')
                 ->join('curriculum_topics ct', 'ct.id = cs.topic_id')
                 ->where('isp.iarp_id', $iarp_id)
                 ->where('isp.week_number', $week['week_number'])
                 ->where('isp.status', 'completed');
        
        $week['completed_items'] = $this->db->get()->result_array();
        $week['completed_count'] = count($week['completed_items']);
        
        // Get remaining subtopics (not yet completed)
        $this->db->select('cs.subtopic_name, ct.topic_name')
                 ->from('curriculum_subtopics cs')
                 ->join('curriculum_topics ct', 'ct.id = cs.topic_id')
                 ->join('iarp_missing_topics imt', 'imt.topic_id = ct.id')
                 ->where('imt.iarp_id', $iarp_id)
                 ->where('cs.id NOT IN (SELECT subtopic_id FROM iarp_subtopic_progress WHERE iarp_id = ' . $iarp_id . ' AND status = "completed")');
        
        $week['remaining_items'] = $this->db->get()->result_array();
        $week['remaining_count'] = count($week['remaining_items']);
    }
    
    return $progress;
}
/**
 * Format progress items for display (shared across views)
 * @param array $items
 * @param string $type (completed or remaining)
 * @return string
 */
public function format_progress_items($items, $type = 'completed')
{
    if (empty($items)) {
        return '<span class="text-muted">None</span>';
    }
    
    $html = '<ul class="list-unstyled progress-items">';
    $current_topic = '';
    
    foreach ($items as $item) {
        $display = ($item['topic_name'] != $current_topic) ? '<strong>' . html_escape($item['topic_name']) . ':</strong> ' : '';
        $current_topic = $item['topic_name'];
        $icon = ($type == 'completed') ? '✅' : '⏳';
        $html .= '<li>' . $display . html_escape($item['subtopic_name']) . ' ' . $icon . '</li>';
    }
    
    $html .= '</ul>';
    return $html;
}
public function get_resource_by_id($resource_id)
{
    $this->db->select('*')
             ->from('resource_recovery')
             ->where('id', $resource_id);
    
    $query = $this->db->get();
    
    if ($query->num_rows() > 0) {
        return $query->row_array();
    }
    
    return null;
}
/**
 * Get report stats with branch filter
 */
public function get_report_stats($branch_id = null)
{
    log_message('info', 'get_report_stats - Branch ID: ' . ($branch_id ?? 'NULL'));
    
    try {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN i.status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN i.status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN i.status = 'draft' THEN 1 ELSE 0 END) as draft
                FROM iarp_plans i
                LEFT JOIN transfer_assessments ta ON ta.id = i.transfer_assessment_id";
        
        if (!empty($branch_id)) {
            $sql .= " WHERE ta.branch_id = " . (int)$branch_id;
        }
        
        $query = $this->db->query($sql);
        $result = $query->row_array();
        
        return array(
            'total' => (int)($result['total'] ?? 0),
            'completed' => (int)($result['completed'] ?? 0),
            'active' => (int)($result['active'] ?? 0),
            'draft' => (int)($result['draft'] ?? 0)
        );
        
    } catch (Exception $e) {
        log_message('error', 'get_report_stats error: ' . $e->getMessage());
        return array('total' => 0, 'completed' => 0, 'active' => 0, 'draft' => 0);
    }
}

/**
 * Get severity stats with branch filter
 */
public function get_severity_stats($branch_id = null)
{
    log_message('info', 'get_severity_stats - Branch ID: ' . ($branch_id ?? 'NULL'));
    
    try {
        $sql = "SELECT g.severity, COUNT(*) as count
                FROM gap_analysis_results g
                LEFT JOIN transfer_assessments ta ON ta.id = g.transfer_assessment_id";
        
        if (!empty($branch_id)) {
            $sql .= " WHERE ta.branch_id = " . (int)$branch_id;
        }
        
        $sql .= " GROUP BY g.severity";
        
        $query = $this->db->query($sql);
        $results = $query->result_array();
        
        $stats = array('green' => 0, 'amber' => 0, 'red' => 0);
        foreach ($results as $row) {
            if (isset($row['severity']) && isset($stats[$row['severity']])) {
                $stats[$row['severity']] = (int)$row['count'];
            }
        }
        
        return $stats;
        
    } catch (Exception $e) {
        log_message('error', 'get_severity_stats error: ' . $e->getMessage());
        return array('green' => 0, 'amber' => 0, 'red' => 0);
    }
}

/**
 * Get monthly closures with branch filter
 */
public function get_monthly_closures($branch_id = null, $start_date = null, $end_date = null)
{
    log_message('info', 'get_monthly_closures - Branch ID: ' . ($branch_id ?? 'NULL'));
    
    try {
        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (empty($end_date)) {
            $end_date = date('Y-m-d');
        }
        
        $sql = "SELECT DATE_FORMAT(cl.closure_date, '%Y-%m') as month, COUNT(*) as count
                FROM recovery_closure_logs cl
                LEFT JOIN iarp_plans i ON i.id = cl.iarp_id
                LEFT JOIN transfer_assessments ta ON ta.id = i.transfer_assessment_id
                WHERE cl.closure_date >= ? AND cl.closure_date <= ?";
        
        $params = array($start_date, $end_date);
        
        if (!empty($branch_id)) {
            $sql .= " AND ta.branch_id = ?";
            $params[] = $branch_id;
        }
        
        $sql .= " GROUP BY DATE_FORMAT(cl.closure_date, '%Y-%m')
                  ORDER BY month ASC";
        
        $query = $this->db->query($sql, $params);
        return $query->result_array();
        
    } catch (Exception $e) {
        log_message('error', 'get_monthly_closures error: ' . $e->getMessage());
        return array();
    }
}

/**
 * Get recent closures with branch filter
 */
/**
 * Get recent closures with branch filter and mastered topics
 */
public function get_recent_closures($branch_id = null)
{
    log_message('info', 'get_recent_closures - Branch ID: ' . ($branch_id ?? 'NULL'));
    
    try {
        $sql = "SELECT cl.*, 
                       s.first_name, s.last_name, s.register_no,
                       c.name as class_name,
                       i.plan_code,
                       CASE 
                           WHEN i.status = 'completed' THEN 'completed'
                           WHEN i.status = 'active' THEN 'active'
                           WHEN i.status = 'draft' THEN 'draft'
                           ELSE 'completed'
                       END as status
                FROM recovery_closure_logs cl
                LEFT JOIN iarp_plans i ON i.id = cl.iarp_id
                LEFT JOIN student s ON s.id = cl.student_id
                LEFT JOIN transfer_assessments ta ON ta.id = i.transfer_assessment_id
                LEFT JOIN class c ON c.id = ta.joining_class_id";
        
        if (!empty($branch_id)) {
            $sql .= " WHERE ta.branch_id = " . (int)$branch_id;
        }
        
        $sql .= " ORDER BY cl.closure_date DESC LIMIT 20";
        
        $query = $this->db->query($sql);
        $results = $query->result_array();
        
        // Get mastered topics for each closure
        foreach ($results as &$row) {
            $row['mastered_topics_list'] = $this->get_mastered_topics_for_iarp($row['iarp_id']);
        }
        
        return $results;
        
    } catch (Exception $e) {
        log_message('error', 'get_recent_closures error: ' . $e->getMessage());
        return array();
    }
}

/**
 * Get mastered topics list for an IARP
 */
public function get_mastered_topics_for_iarp($iarp_id)
{
    // Get completed topics
    $sql = "SELECT ct.topic_name, s.name as subject_name
            FROM iarp_missing_topics imt
            JOIN curriculum_topics ct ON ct.id = imt.topic_id
            JOIN subject s ON s.id = imt.subject_id
            WHERE imt.iarp_id = ? AND imt.status = 'completed'
            ORDER BY s.name, ct.topic_name";
    
    $topics = $this->db->query($sql, array($iarp_id))->result_array();
    
    // Get completed subtopics
    $sql2 = "SELECT cs.subtopic_name, ct.topic_name, s.name as subject_name
             FROM iarp_subtopic_progress isp
             JOIN curriculum_subtopics cs ON cs.id = isp.subtopic_id
             JOIN curriculum_topics ct ON ct.id = cs.topic_id
             JOIN subject s ON s.id = ct.subject_id
             WHERE isp.iarp_id = ? AND isp.status = 'completed'
             ORDER BY s.name, ct.topic_name, cs.subtopic_name";
    
    $subtopics = $this->db->query($sql2, array($iarp_id))->result_array();
    
    $mastered = array();
    
    // Add topics
    foreach ($topics as $topic) {
        $mastered[] = $topic['subject_name'] . ': ' . $topic['topic_name'] . ' (Topic)';
    }
    
    // Add subtopics
    foreach ($subtopics as $subtopic) {
        $mastered[] = $subtopic['subject_name'] . ': ' . $subtopic['topic_name'] . ' - ' . $subtopic['subtopic_name'];
    }
    
    return $mastered;
}
/**
 * Get sidebar badge counts
 * @param int|null $branch_id
 * @return array
 */
public function get_sidebar_badges($branch_id = null)
{
    $badges = array(
        'pending_assessments' => 0,
        'active_iARPs' => 0
    );
    
    // Get pending assessments count
    $this->db->select('COUNT(*) as count');
    $this->db->from('transfer_assessments');
    if (!empty($branch_id)) {
        $this->db->where('branch_id', $branch_id);
    }
    $this->db->where('status', 'pending');
    $query = $this->db->get();
    $badges['pending_assessments'] = (int)$query->row()->count;
    
    // Get active IARPs count
    $this->db->select('COUNT(*) as count');
    $this->db->from('iarp_plans i');
    $this->db->join('transfer_assessments ta', 'ta.id = i.transfer_assessment_id');
    if (!empty($branch_id)) {
        $this->db->where('ta.branch_id', $branch_id);
    }
    $this->db->where('i.status', 'active');
    $query = $this->db->get();
    $badges['active_iARPs'] = (int)$query->row()->count;
    
    return $badges;
}
/**
 * Create a recovery assessment for an existing student
 * @param int $student_id
 * @param int $created_by
 * @return int|bool
 */
public function create_for_existing_student($student_id, $created_by)
{
    // Get student details with current enrollment
    $student = $this->db->select('s.*, e.class_id, e.section_id, e.session_id')
                        ->from('student s')
                        ->join('enroll e', 'e.student_id = s.id')
                        ->where('s.id', $student_id)
                        ->where('e.session_id', get_session_id())
                        ->get()
                        ->row_array();
    
    if (empty($student)) {
        log_message('error', "STARS: Student not found for ID: {$student_id}");
        return false;
    }
    
    // Check if already has an active assessment
    $existing = $this->db->where('student_id', $student_id)
                        ->where_in('status', ['pending', 'assessing', 'gap_analysis', 'recovery_plan', 'monitoring'])
                        ->get('transfer_assessments')
                        ->num_rows();
    
    if ($existing > 0) {
        log_message('info', "STARS: Student {$student_id} already has an active assessment");
        return false;
    }
    
    // Get current term
    $term = $this->db->select('id')
                     ->where('branch_id', $student['branch_id'])
                     ->where('is_active', 1)
                     ->get('exam_term')
                     ->row();
    
    // Create transfer assessment
    $data = array(
        'student_id' => $student_id,
        'branch_id' => $student['branch_id'],
        'previous_school_name' => 'Continuing Student - Gap Recovery',
        'transfer_date' => date('Y-m-d'),
        'joining_class_id' => $student['class_id'],
        'joining_section_id' => $student['section_id'],
        'joining_term_id' => $term ? $term->id : 1,
        'status' => 'pending',
        'is_manual_admission' => 1,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->insert('transfer_assessments', $data);
    $transfer_id = $this->db->insert_id();
    
    log_message('info', "STARS: Created recovery assessment for existing student {$student_id} (Transfer ID: {$transfer_id})");
    
    return $transfer_id;
}
/**
 * Get student details for recovery request
 * @param int $student_id
 * @return array|null
 */
public function get_student_for_recovery($student_id)
{
    $this->db->select('id, first_name, last_name, parent_id, branch_id');
    $this->db->where('id', $student_id);
    $query = $this->db->get('student');
    
    if ($query->num_rows() > 0) {
        return $query->row_array();
    }
    
    return null;
}

/**
 * Check if student has active assessment
 * @param int $student_id
 * @return bool
 */
public function has_active_assessment($student_id)
{
    $count = $this->db->where('student_id', $student_id)
                      ->where_in('status', ['pending', 'assessing', 'gap_analysis', 'recovery_plan', 'monitoring'])
                      ->get('transfer_assessments')
                      ->num_rows();
    
    return $count > 0;
}

/**
 * Get branch ID for student
 * @param int $student_id
 * @return int|null
 */
public function get_student_branch_id($student_id)
{
    $this->db->select('branch_id');
    $this->db->where('id', $student_id);
    $query = $this->db->get('student');
    
    if ($query->num_rows() > 0) {
        return $query->row()->branch_id;
    }
    
    return null;
}
}