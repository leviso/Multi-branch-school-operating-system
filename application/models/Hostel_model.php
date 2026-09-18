<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Hostel_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function hostel_save($data)
    {
        $arrayData = array(
            'name' => $data['name'],
            'category_id' => $data['category_id'],
            'address' => $data['hostel_address'],
            'watchman' => $data['watchman_name'],
            'remarks' => $data['remarks'],
            'branch_id' => $this->application_model->get_branch_id(),
        );
        if (!isset($data['hostel_id'])) {
            $this->db->insert('hostel', $arrayData);
        } else {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $data['hostel_id']);
            $this->db->update('hostel', $arrayData);
        }
    }

    public function category_save($data)
    {
        $arrayData = array(
            'name' => $data['category_name'],
            'type' => $data['type'],
            'description' => $data['description'],
            'branch_id' => $this->application_model->get_branch_id(),
        );
        if (!isset($data['category_id'])) {
            $this->db->insert('hostel_category', $arrayData);
        } else {
            $this->db->where('id', $data['category_id']);
            $this->db->update('hostel_category', $arrayData);
        }
    }

    public function room_save($data)
    {
        $arrayData = array(
            'name' => $data['name'],
            'hostel_id' => $data['hostel_id'],
            'category_id' => $data['category_id'],
            'no_beds' => $data['number_of_beds'],
            'bed_fee' => $data['bed_fee'],
            'remarks' => $data['remarks'],
            'branch_id' => $this->application_model->get_branch_id(),
        );
        if (!isset($data['room_id'])) {
            $this->db->insert('hostel_room', $arrayData);
        } else {
            $this->db->where('id', $data['room_id']);
            $this->db->update('hostel_room', $arrayData);
        }
    }

    // allocation report with student name
    public function allocation_report($classID, $sectionID, $branchID)
    {
        $sql = "SELECT s.first_name, s.last_name, s.register_no, e.branch_id, e.id as enroll_id, h.name as hostel_name, r.name as room_name, r.bed_fee, rc.name as room_category
        FROM student as s INNER JOIN enroll as e ON e.student_id = s.id INNER JOIN hostel as h ON h.id = s.hostel_id INNER JOIN hostel_room as r ON r.id = s.room_id LEFT JOIN
        hostel_category as rc ON rc.id = r.category_id WHERE s.hostel_id != 0 AND s.room_id != 0 AND e.branch_id = " .
        $this->db->escape($branchID) . " AND e.class_id = " . $this->db->escape($classID) . " AND e.section_id = ". $this->db->escape($sectionID);
        $query = $this->db->query($sql)->result_array();
        return $query;
    }

    // get hostel information by hostel id and room id
    public function get_student_hostel($hostel_id, $room_id)
    {
        $this->db->select('h.name as hostel_name,h.watchman,h.category_id,h.address,hc.name as hcategory_name,rc.name as rcategory_name,hr.name as room_name,hr.no_beds,hr.bed_fee');
        $this->db->from('hostel as h');
        $this->db->join('hostel_category as hc', 'hc.id = h.category_id', 'left');
        $this->db->join('hostel_room as hr', 'hr.hostel_id = h.id', 'left');
        $this->db->join('hostel_category as rc', 'rc.id = hr.category_id', 'left');
        $this->db->where('hr.id', $room_id);
        $this->db->where('h.id', $hostel_id);
        return $this->db->get()->row();
    }
    // ============================================
// INVENTORY METHODS
// ============================================

public function get_inventory_items($filters = array())
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('i.*, c.name as category_name, r.name as room_name, 
                      CONCAT(s.first_name, " ", s.last_name) as student_name');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    $this->db->join('hostel_room r', 'r.id = i.room_id', 'left');
    $this->db->join('student s', 's.id = i.student_id', 'left');
    $this->db->where('i.branch_id', $branchID);
    
    if (!empty($filters['category_id'])) {
        $this->db->where('i.category_id', $filters['category_id']);
    }
    if (!empty($filters['status'])) {
        $this->db->where('i.status', $filters['status']);
    }
    if (!empty($filters['room_id'])) {
        $this->db->where('i.room_id', $filters['room_id']);
    }
    
    $this->db->order_by('i.item_code', 'ASC');
    return $this->db->get()->result_array();
}

public function get_inventory_item($id)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('i.*, c.name as category_name');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    $this->db->where('i.id', $id);
    $this->db->where('i.branch_id', $branchID);
    
    return $this->db->get()->row_array();
}

public function add_inventory_item($data)
{
    $branchID = $this->application_model->get_branch_id();
    
    $insert_data = array(
        'item_code' => $data['item_code'],
        'category_id' => $data['category_id'],
        'name' => $data['name'],
        'description' => $data['description'] ?? '',
        'manufacturer' => $data['manufacturer'] ?? '',
        'model_number' => $data['model_number'] ?? '',
        'serial_number' => $data['serial_number'] ?? '',
        'room_id' => $data['room_id'] ?? 0,
        'student_id' => $data['student_id'] ?? 0,
        'condition' => $data['condition'] ?? 'good',
        'status' => $data['status'] ?? 'available',
        'purchase_date' => $data['purchase_date'] ?? date('Y-m-d'),
        'purchase_cost' => $data['purchase_cost'] ?? 0,
        'current_value' => $data['purchase_cost'] ?? 0,
        'supplier' => $data['supplier'] ?? '',
        'warranty_expiry' => $data['warranty_expiry'] ?? null,
        'notes' => $data['notes'] ?? '',
        'branch_id' => $branchID,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->insert('hostel_inventory_items', $insert_data);
    return $this->db->insert_id();
}

public function update_inventory_item($id, $data)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->where('id', $id);
    $this->db->where('branch_id', $branchID);
    $this->db->update('hostel_inventory_items', $data);
    return $this->db->affected_rows();
}

public function delete_inventory_item($id)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->where('id', $id);
    $this->db->where('branch_id', $branchID);
    $this->db->delete('hostel_inventory_items');
    return $this->db->affected_rows();
}

public function assign_item_to_student($item_id, $student_id, $condition, $assigned_by)
{
    $branchID = $this->application_model->get_branch_id();
    
    $data = array(
        'item_id' => $item_id,
        'student_id' => $student_id,
        'assigned_by' => $assigned_by,
        'assigned_date' => date('Y-m-d'),
        'condition_on_assign' => $condition,
        'branch_id' => $branchID
    );
    $this->db->insert('hostel_item_assignments', $data);
    
    // Update item status
    $this->db->where('id', $item_id);
    $this->db->update('hostel_inventory_items', array(
        'status' => 'assigned',
        'student_id' => $student_id
    ));
    
    return $this->db->insert_id();
}

public function return_item_from_student($item_id, $student_id, $return_condition, $damage_notes = null)
{
    $branchID = $this->application_model->get_branch_id();
    
    // Update assignment record
    $this->db->where('item_id', $item_id);
    $this->db->where('student_id', $student_id);
    $this->db->where('branch_id', $branchID);
    $this->db->order_by('id', 'DESC');
    $this->db->limit(1);
    $this->db->update('hostel_item_assignments', array(
        'returned_date' => date('Y-m-d'),
        'return_condition' => $return_condition,
        'damage_notes' => $damage_notes
    ));
    
    // Update item status
    $this->db->where('id', $item_id);
    $this->db->update('hostel_inventory_items', array(
        'status' => 'available',
        'student_id' => 0,
        'condition' => $return_condition
    ));
    
    return true;
}

public function get_room_inventory($room_id)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('i.*, c.name as category_name');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    $this->db->where('i.room_id', $room_id);
    $this->db->where('i.branch_id', $branchID);
    $this->db->order_by('i.item_code', 'ASC');
    
    return $this->db->get()->result_array();
}

public function get_student_inventory($student_id)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('i.*, c.name as category_name, a.assigned_date, a.condition_on_assign');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    $this->db->join('hostel_item_assignments a', 'a.item_id = i.id', 'left');
    $this->db->where('i.student_id', $student_id);
    $this->db->where('i.branch_id', $branchID);
    $this->db->where('i.status', 'assigned');
    
    return $this->db->get()->result_array();
}

// ============================================
// REPAIR METHODS
// ============================================

public function add_repair_request($data)
{
    $branchID = $this->application_model->get_branch_id();
    
    $data['branch_id'] = $branchID;
    $data['reported_date'] = date('Y-m-d H:i:s');
    $data['repair_code'] = 'RPR-' . strtoupper(uniqid());
    $data['created_at'] = date('Y-m-d H:i:s');
    
    $this->db->insert('hostel_repairs', $data);
    return $this->db->insert_id();
}

public function get_repair_requests($status = null)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('r.*, i.name as item_name, i.item_code, 
                      i.room_id, hr.name as room_name,
                      CONCAT(s.first_name, " ", s.last_name) as reported_by_name');
    $this->db->from('hostel_repairs r');
    $this->db->join('hostel_inventory_items i', 'i.id = r.item_id', 'left');
    $this->db->join('hostel_room hr', 'hr.id = COALESCE(r.room_id, i.room_id)', 'left');
    $this->db->join('staff s', 's.id = r.reported_by', 'left');
    $this->db->where('r.branch_id', $branchID);
    
    if ($status) {
        $this->db->where('r.status', $status);
    }
    
    $this->db->order_by('FIELD(r.priority, "emergency", "high", "medium", "low")');
    $this->db->order_by('r.reported_date', 'DESC');
    
    return $this->db->get()->result_array();
}

public function update_repair_status($id, $status, $notes = null)
{
    $branchID = $this->application_model->get_branch_id();
    
    $update_data = array(
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    if ($status == 'in_progress' && empty($this->db->select('start_date')->where('id', $id)->get('hostel_repairs')->row()->start_date)) {
        $update_data['start_date'] = date('Y-m-d H:i:s');
    }
    
    if ($status == 'completed') {
        $update_data['completion_date'] = date('Y-m-d H:i:s');
    }
    
    if ($notes) {
        $update_data['completion_notes'] = $notes;
    }
    
    $this->db->where('id', $id);
    $this->db->where('branch_id', $branchID);
    $this->db->update('hostel_repairs', $update_data);
    
    // If completed, update item condition
    if ($status == 'completed') {
        $repair = $this->db->select('item_id')->where('id', $id)->get('hostel_repairs')->row();
        if ($repair && $repair->item_id) {
            $this->db->where('id', $repair->item_id);
            $this->db->update('hostel_inventory_items', array(
                'condition' => 'good',
                'updated_at' => date('Y-m-d H:i:s')
            ));
        }
    }
    
    return $this->db->affected_rows();
}
// ============================================
// ROOM INSPECTION METHODS
// ============================================

/**
 * Get all inspections with filters
 */
public function get_inspections($filters = array())
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('i.*, r.name as room_name, h.name as hostel_name, 
                      s.name as inspector_name,
                      CONCAT(st.first_name, " ", st.last_name) as student_name');
    $this->db->from('hostel_room_inspections i');
    $this->db->join('hostel_room r', 'r.id = i.room_id', 'left');
    $this->db->join('hostel h', 'h.id = r.hostel_id', 'left');
    $this->db->join('staff s', 's.id = i.inspector_id', 'left');
    $this->db->join('student st', 'st.room_id = r.id', 'left');
    $this->db->group_by('i.id');
    
    if (!is_superadmin_loggedin()) {
        $this->db->where('i.branch_id', $branchID);
    }
    
    if (!empty($filters['room_id'])) {
        $this->db->where('i.room_id', $filters['room_id']);
    }
    if (!empty($filters['inspection_type'])) {
        $this->db->where('i.inspection_type', $filters['inspection_type']);
    }
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $this->db->where('DATE(i.inspection_date) >=', $filters['start_date']);
        $this->db->where('DATE(i.inspection_date) <=', $filters['end_date']);
    }
    
    $this->db->order_by('i.inspection_date', 'DESC');
    return $this->db->get()->result_array();
}
/**
 * Get single inspection with all details - DEBUG VERSION
 */
public function get_inspection($id)
{
    $branchID = $this->application_model->get_branch_id();
    
    error_log("=== get_inspection START ===");
    error_log("Inspection ID: " . $id);
    
    // Get inspection main details
    $this->db->select('i.*, r.name as room_name, h.name as hostel_name, s.name as inspector_name');
    $this->db->from('hostel_room_inspections i');
    $this->db->join('hostel_room r', 'r.id = i.room_id', 'left');
    $this->db->join('hostel h', 'h.id = r.hostel_id', 'left');
    $this->db->join('staff s', 's.id = i.inspector_id', 'left');
    $this->db->where('i.id', $id);
    
    if (!is_superadmin_loggedin()) {
        $this->db->where('i.branch_id', $branchID);
    }
    
    $inspection = $this->db->get()->row_array();
    
    if (empty($inspection)) {
        error_log("Inspection NOT found!");
        return array();
    }
    
    error_log("Inspection found: " . print_r($inspection, true));
    
    // Get scores - check what's being returned
    $this->db->select('*');
    $this->db->from('hostel_inspection_scores');
    $this->db->where('inspection_id', $id);
    $scores_query = $this->db->get();
    
    error_log("Scores query SQL: " . $this->db->last_query());
    error_log("Scores count: " . $scores_query->num_rows());
    
    $raw_scores = $scores_query->result_array();
    error_log("Raw scores: " . print_r($raw_scores, true));
    
    // Now try to join with template items
    $this->db->select('s.*, ti.category, ti.item_name, ti.max_score, ti.weight');
    $this->db->from('hostel_inspection_scores s');
    $this->db->join('hostel_inspection_template_items ti', 'ti.id = s.template_item_id', 'left');
    $this->db->where('s.inspection_id', $id);
    $joined_scores = $this->db->get()->result_array();
    
    error_log("Joined scores count: " . count($joined_scores));
    error_log("Joined scores: " . print_r($joined_scores, true));
    
    $inspection['scores'] = $joined_scores;
    
    // Get issues
    $this->db->select('*');
    $this->db->from('hostel_inspection_issues');
    $this->db->where('inspection_id', $id);
    $inspection['issues'] = $this->db->get()->result_array();
    
    // Get photos
    $this->db->select('*');
    $this->db->from('hostel_inspection_photos');
    $this->db->where('inspection_id', $id);
    $inspection['photos'] = $this->db->get()->result_array();
    
    error_log("=== get_inspection END ===");
    
    return $inspection;
}
/**
 * Helper to get grade color
 */
private function getGradeColor($grade)
{
    switch($grade) {
        case 'A': return 'success';
        case 'B': return 'info';
        case 'C': return 'warning';
        case 'D': return 'danger';
        case 'F': return 'danger';
        default: return 'default';
    }
}
/**
 * Save inspection
 */
public function save_inspection($data)
{
    $branchID = $this->application_model->get_branch_id();
    
    // Generate inspection code
    $data['inspection_code'] = 'INS-' . strtoupper(uniqid());
    $data['branch_id'] = $branchID;
    $data['created_at'] = date('Y-m-d H:i:s');
    
    $this->db->insert('hostel_room_inspections', $data);
    $inspection_id = $this->db->insert_id();
    
    return $inspection_id;
}

/**
 * Save inspection scores
 */
public function save_inspection_scores($inspection_id, $scores)
{
    if (empty($scores)) {
        return false;
    }
    
    foreach ($scores as $score) {
        // Skip if no score selected
        if (!isset($score['score']) || $score['score'] === '') {
            continue;
        }
        
        $score_data = array(
            'inspection_id' => $inspection_id,
            'template_item_id' => $score['template_item_id'],
            'score' => $score['score'],
            'comments' => isset($score['comments']) ? $score['comments'] : null,
            'branch_id' => $this->application_model->get_branch_id()
        );
        $this->db->insert('hostel_inspection_scores', $score_data);
    }
    
    // Debug log
    error_log("Saved " . count($scores) . " scores for inspection ID: $inspection_id");
    
    return true;
}
/**
 * Save inspection issues and auto-create repair tickets
 */
public function save_inspection_issues($inspection_id, $issues)
{
    $branchID = $this->application_model->get_branch_id();
    
    foreach ($issues as $issue) {
        $issue_data = array(
            'inspection_id' => $inspection_id,
            'scores_id' => $issue['scores_id'] ?? null,
            'issue_description' => $issue['description'],
            'priority' => $issue['priority'],
            'branch_id' => $branchID,
            'created_at' => date('Y-m-d H:i:s')
        );
        
        $this->db->insert('hostel_inspection_issues', $issue_data);
        $issue_id = $this->db->insert_id();
        
        // Auto-create repair ticket if issue is high priority
        if (in_array($issue['priority'], array('high', 'emergency'))) {
            $repair_data = array(
                'repair_code' => 'RPR-' . strtoupper(uniqid()),
                'issue_description' => $issue['description'],
                'priority' => $issue['priority'],
                'status' => 'pending',
                'reported_by' => get_loggedin_user_id(),
                'reported_by_type' => 'staff',
                'reported_date' => date('Y-m-d H:i:s'),
                'branch_id' => $branchID,
                'created_at' => date('Y-m-d H:i:s')
            );
            $this->db->insert('hostel_repairs', $repair_data);
            $repair_id = $this->db->insert_id();
            
            // Link issue to repair
            $this->db->where('id', $issue_id);
            $this->db->update('hostel_inspection_issues', array('repair_id' => $repair_id));
        }
    }
    
    return true;
}

/**
 * Calculate overall score and grade
 */
/**
 * Calculate inspection score and grade using Kenyan CBC system
 */
public function calculate_inspection_score($scores)
{
    if (empty($scores)) {
        return array('overall_score' => 0, 'grade' => 'BE2', 'grade_name' => 'Below Expectations', 'points' => 1, 'descriptor' => 'Minimal Performance');
    }
    
    $total_weighted_score = 0;
    $total_weight = 0;
    
    foreach ($scores as $score) {
        if (!isset($score['score']) || $score['score'] === '') {
            continue;
        }
        
        $score_value = (int)$score['score'];
        $max_score = isset($score['max_score']) ? (int)$score['max_score'] : 5;
        $weight = isset($score['weight']) ? (float)$score['weight'] : 1;
        
        $weighted = ($score_value / $max_score) * $weight;
        $total_weighted_score += $weighted;
        $total_weight += $weight;
    }
    
    if ($total_weight == 0) {
        return array('overall_score' => 0, 'grade' => 'BE2', 'grade_name' => 'Below Expectations', 'points' => 1, 'descriptor' => 'Minimal Performance');
    }
    
    $percentage = ($total_weighted_score / $total_weight) * 100;
    $percentage = round($percentage, 2);
    
    // ========== KENYAN CBC GRADING SYSTEM ==========
    // Exceeding Expectations (EE)
    if ($percentage >= 90 && $percentage <= 100) {
        $grade = 'EE1';
        $grade_name = 'Exceeding Expectations';
        $points = 8;
        $descriptor = 'Exceptional';
    } elseif ($percentage >= 75 && $percentage <= 89) {
        $grade = 'EE2';
        $grade_name = 'Exceeding Expectations';
        $points = 7;
        $descriptor = 'Very Good';
    }
    // Meeting Expectations (ME)
    elseif ($percentage >= 58 && $percentage <= 74) {
        $grade = 'ME1';
        $grade_name = 'Meeting Expectations';
        $points = 6;
        $descriptor = 'Good';
    } elseif ($percentage >= 41 && $percentage <= 57) {
        $grade = 'ME2';
        $grade_name = 'Meeting Expectations';
        $points = 5;
        $descriptor = 'Fair';
    }
    // Approaching Expectations (AE)
    elseif ($percentage >= 31 && $percentage <= 40) {
        $grade = 'AE1';
        $grade_name = 'Approaching Expectations';
        $points = 4;
        $descriptor = 'Needs Improvement';
    } elseif ($percentage >= 21 && $percentage <= 30) {
        $grade = 'AE2';
        $grade_name = 'Approaching Expectations';
        $points = 3;
        $descriptor = 'Below Average';
    }
    // Below Expectations (BE)
    elseif ($percentage >= 11 && $percentage <= 20) {
        $grade = 'BE1';
        $grade_name = 'Below Expectations';
        $points = 2;
        $descriptor = 'Well Below Average';
    } else {
        $grade = 'BE2';
        $grade_name = 'Below Expectations';
        $points = 1;
        $descriptor = 'Minimal Performance';
    }
    
    return array(
        'overall_score' => $percentage,
        'grade' => $grade,
        'grade_name' => $grade_name,
        'points' => $points,
        'descriptor' => $descriptor
    );
}

/**
 * Get room inspection history
 */
public function get_room_inspection_history($room_id, $limit = 10)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('i.*, s.name as inspector_name');
    $this->db->from('hostel_room_inspections i');
    $this->db->join('staff s', 's.id = i.inspector_id', 'left');
    $this->db->where('i.room_id', $room_id);
    $this->db->where('i.branch_id', $branchID);
    $this->db->order_by('i.inspection_date', 'DESC');
    $this->db->limit($limit);
    
    return $this->db->get()->result_array();
}

/**
 * Get inspection statistics
 */
public function get_inspection_stats($branch_id = null)
{
    if (is_null($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    // Total inspections
    $this->db->where('branch_id', $branch_id);
    $total = $this->db->count_all_results('hostel_room_inspections');
    
    // Average score
    $this->db->select_avg('overall_score');
    $this->db->where('branch_id', $branch_id);
    $avg_score = $this->db->get('hostel_room_inspections')->row()->overall_score ?? 0;
    
    // Inspections by type
    $this->db->select('inspection_type, COUNT(*) as count');
    $this->db->where('branch_id', $branch_id);
    $this->db->group_by('inspection_type');
    $by_type = $this->db->get('hostel_room_inspections')->result_array();
    
    // Top performing rooms
    $this->db->select('r.name, AVG(i.overall_score) as avg_score');
    $this->db->from('hostel_room_inspections i');
    $this->db->join('hostel_room r', 'r.id = i.room_id');
    $this->db->where('i.branch_id', $branch_id);
    $this->db->group_by('i.room_id');
    $this->db->order_by('avg_score', 'DESC');
    $this->db->limit(5);
    $top_rooms = $this->db->get()->result_array();
    
    return array(
        'total' => $total,
        'average_score' => round($avg_score, 2),
        'by_type' => $by_type,
        'top_rooms' => $top_rooms
    );
}

/**
 * Get inspection templates
 */
public function get_inspection_templates($branch_id = null)
{
    if (is_null($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    // For superadmin, get all branches or filter by selected branch
    if (is_superadmin_loggedin()) {
        // You can add branch filter if needed
        $this->db->select('t.*, COUNT(ti.id) as item_count');
        $this->db->from('hostel_inspection_templates t');
        $this->db->join('hostel_inspection_template_items ti', 'ti.template_id = t.id', 'left');
        $this->db->group_by('t.id');
    } else {
        $this->db->select('t.*, COUNT(ti.id) as item_count');
        $this->db->from('hostel_inspection_templates t');
        $this->db->join('hostel_inspection_template_items ti', 'ti.template_id = t.id', 'left');
        $this->db->where('t.branch_id', $branch_id);
        $this->db->group_by('t.id');
    }
    
    $result = $this->db->get()->result_array();
    
    // Debug log
    error_log("Templates found: " . count($result));
    
    return $result;
}

/**
 * Get template items
 */
public function get_template_items($template_id)
{
    $this->db->select('*');
    $this->db->from('hostel_inspection_template_items');
    $this->db->where('template_id', $template_id);
    $this->db->order_by('sort_order', 'ASC');
    
    return $this->db->get()->result_array();
}
// ============================================
// STAFF ROOM ASSIGNMENT METHODS
// ============================================

/**
 * Assign staff to room
 */
public function assign_staff_to_room($data)
{
    $branchID = $this->application_model->get_branch_id();
    
    $insert_data = array(
        'staff_id' => $data['staff_id'],
        'room_id' => $data['room_id'],
        'role' => $data['role'],
        'is_primary' => isset($data['is_primary']) ? $data['is_primary'] : 0,
        'assigned_date' => $data['assigned_date'] ?: date('Y-m-d'),
        'end_date' => $data['end_date'] ?? null,
        'is_active' => 1,
        'branch_id' => $branchID,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->insert('hostel_staff_assignments', $insert_data);
    return $this->db->insert_id();
}

/**
 * Update staff assignment
 */
public function update_staff_assignment($id, $data)
{
    $branchID = $this->application_model->get_branch_id();
    
    $update_data = array(
        'role' => $data['role'],
        'is_primary' => isset($data['is_primary']) ? $data['is_primary'] : 0,
        'end_date' => $data['end_date'] ?? null,
        'is_active' => isset($data['is_active']) ? $data['is_active'] : 1,
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->where('id', $id);
    $this->db->where('branch_id', $branchID);
    $this->db->update('hostel_staff_assignments', $update_data);
    return $this->db->affected_rows();
}

/**
 * Remove staff assignment (soft delete)
 */
public function remove_staff_assignment($id)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->where('id', $id);
    $this->db->where('branch_id', $branchID);
    $this->db->update('hostel_staff_assignments', array(
        'is_active' => 0,
        'end_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s')
    ));
    return $this->db->affected_rows();
}

/**
 * Get staff assigned rooms
 */
public function get_staff_assigned_rooms($staff_id, $active_only = true)
{
    $branchID = $this->application_model->get_branch_id();
    
    error_log("=== get_staff_assigned_rooms ===");
    error_log("Staff ID: " . $staff_id);
    error_log("Branch ID: " . $branchID);
    error_log("Active only: " . ($active_only ? 'Yes' : 'No'));
    
    $this->db->select('a.*, r.name as room_name');
    $this->db->from('hostel_staff_assignments a');
    $this->db->join('hostel_room r', 'r.id = a.room_id', 'left');
    $this->db->where('a.staff_id', $staff_id);
    $this->db->where('a.branch_id', $branchID);
    
    if ($active_only) {
        $this->db->where('a.is_active', 1);
    }
    
    $query = $this->db->get();
    error_log("SQL: " . $this->db->last_query());
    error_log("Rows found: " . $query->num_rows());
    
    return $query->result_array();
}

/**
 * Get staff assigned to a room
 */
public function get_room_assigned_staff($room_id, $role = null, $active_only = true)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('a.*, s.name as staff_name, s.staff_id as staff_code, s.designation');
    $this->db->from('hostel_staff_assignments a');
    $this->db->join('staff s', 's.id = a.staff_id', 'left');
    $this->db->where('a.room_id', $room_id);
    $this->db->where('a.branch_id', $branchID);
    
    if ($active_only) {
        $this->db->where('a.is_active', 1);
    }
    
    if ($role) {
        $this->db->where('a.role', $role);
    }
    
    $this->db->order_by('a.is_primary', 'DESC');
    
    return $this->db->get()->result_array();
}

/**
 * Get all staff assignments for a branch
 */
public function get_all_staff_assignments($branch_id = null)
{
    if (is_null($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    $this->db->select('a.*, s.name as staff_name, s.staff_id as staff_code,
                      r.name as room_name, h.name as hostel_name');
    $this->db->from('hostel_staff_assignments a');
    $this->db->join('staff s', 's.id = a.staff_id', 'left');
    $this->db->join('hostel_room r', 'r.id = a.room_id', 'left');
    $this->db->join('hostel h', 'h.id = r.hostel_id', 'left');
    $this->db->where('a.branch_id', $branch_id);
    
    // ========== ADD THIS LINE HERE ==========
    $this->db->where('a.is_active', 1);  // <-- ADD THIS
    // =======================================
    
    $this->db->order_by('h.name', 'ASC');
    $this->db->order_by('r.name', 'ASC');
    $this->db->order_by('a.role', 'ASC');
    
    return $this->db->get()->result_array();
}

/**
 * Get available staff for assignment (not already assigned to a room)
 */
public function get_available_staff_for_assignment($role_filter = null)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('s.id, s.name, s.staff_id, s.designation');
    $this->db->from('staff s');
    $this->db->where('s.branch_id', $branchID);
    
    if ($role_filter) {
        $this->db->where('s.designation', $role_filter);
    }
    
    $this->db->order_by('s.name', 'ASC');
    
    return $this->db->get()->result_array();
}

/**
 * Check if staff is assigned to a room
 */
public function is_staff_assigned_to_room($staff_id, $room_id)
{
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('id');
    $this->db->from('hostel_staff_assignments');
    $this->db->where('staff_id', $staff_id);
    $this->db->where('room_id', $room_id);
    $this->db->where('branch_id', $branchID);
    $this->db->where('is_active', 1);
    
    return $this->db->get()->num_rows() > 0;
}
}
