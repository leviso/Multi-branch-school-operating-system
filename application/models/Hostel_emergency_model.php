<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hostel_emergency_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ============================================
    // EMERGENCY INCIDENTS
    // ============================================

   public function get_incidents($branch_id = null, $status = null, $severity = null)
{
    $user_role = $this->session->userdata('role_id');
    $user_id = get_loggedin_user_id();
    
    $this->db->select('hei.*, het.name as emergency_type, het.icon, 
                      s.first_name, s.last_name, s.register_no,
                      hr.name as room_name, h.name as hostel_name,
                      st.name as reported_staff_name,
                      b.name as branch_name, b.school_name')
         ->from('hostel_emergency_incidents hei')
         ->join('hostel_emergency_types het', 'het.id = hei.emergency_type_id', 'left')
         ->join('student s', 's.id = hei.student_id', 'left')
         ->join('hostel_room hr', 'hr.id = hei.room_id', 'left')
         ->join('hostel h', 'h.id = hei.hostel_id', 'left')
         ->join('staff st', 'st.id = hei.reported_by', 'left')
         ->join('branch b', 'b.id = hei.branch_id', 'left')  // ADD THIS LINE
         ->order_by('hei.reported_at', 'DESC');

    // Branch filter
    if ($branch_id) {
        $this->db->where('hei.branch_id', $branch_id);
    } elseif (!is_superadmin_loggedin()) {
        $this->db->where('hei.branch_id', get_loggedin_branch_id());
    }
    
    // Role-based filters (keep your existing code)
    if ($user_role == 3) {
        $assigned_rooms = $this->db->select('room_id')
            ->from('hostel_staff_assignments')
            ->where('staff_id', $user_id)
            ->where('is_active', 1)
            ->get()
            ->result_array();
        
        $room_ids = array_column($assigned_rooms, 'room_id');
        if (!empty($room_ids)) {
            $this->db->where_in('hei.room_id', $room_ids);
        } else {
            $this->db->where('1', '0');
        }
    } elseif ($user_role == 7) {
        $this->db->where("FIND_IN_SET('" . $user_id . "', hei.student_id) >", 0, false);
    } elseif ($user_role == 6) {
        $children = $this->db->select('id')
            ->from('student')
            ->where('parent_id', $user_id)
            ->get()
            ->result_array();
        
        $child_ids = array_column($children, 'id');
        if (!empty($child_ids)) {
            $conditions = array();
            foreach ($child_ids as $cid) {
                $conditions[] = "FIND_IN_SET('{$cid}', hei.student_id) > 0";
            }
            $this->db->where('(' . implode(' OR ', $conditions) . ')');
        } else {
            $this->db->where('1', '0');
        }
    }
    
    // Status and severity filters
    if ($status && $status != 'all') {
        $this->db->where('hei.status', $status);
    }
    if ($severity && $severity != 'all') {
        $this->db->where('hei.severity', $severity);
    }
    
    return $this->db->get()->result_array();
}

    public function get_incident($id, $branch_id = null)
    {
        $this->db->select('hei.*, het.name as emergency_type, het.icon,
                          s.first_name, s.last_name, s.register_no, s.mobileno as student_mobile,
                          hr.name as room_name, hr.no_beds,
                          h.name as hostel_name,
                          st.name as reported_staff_name,
                          res.name as resolved_staff_name')
            ->from('hostel_emergency_incidents hei')
            ->join('hostel_emergency_types het', 'het.id = hei.emergency_type_id', 'left')
            ->join('student s', 's.id = hei.student_id', 'left')
            ->join('hostel_room hr', 'hr.id = hei.room_id', 'left')
            ->join('hostel h', 'h.id = hei.hostel_id', 'left')
            ->join('staff st', 'st.id = hei.reported_by', 'left')
            ->join('staff res', 'res.id = hei.resolved_by', 'left')
            ->where('hei.id', $id);

        if ($branch_id) {
            $this->db->where('hei.branch_id', $branch_id);
        } elseif (!is_superadmin_loggedin()) {
            $this->db->where('hei.branch_id', get_loggedin_branch_id());
        }

        return $this->db->get()->row_array();
    }

    public function add_incident($data)
    {
        $this->db->insert('hostel_emergency_incidents', $data);
        return $this->db->insert_id();
    }

    public function update_incident($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('hostel_emergency_incidents', $data);
    }

    public function get_incident_stats($branch_id = null)
    {
        $this->db->select("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'reported' THEN 1 ELSE 0 END) as reported,
            SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
            SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high
        ");

        if ($branch_id) {
            $this->db->where('branch_id', $branch_id);
        } elseif (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }

        return $this->db->get('hostel_emergency_incidents')->row_array();
    }

    // ============================================
    // EMERGENCY TYPES
    // ============================================

    public function get_emergency_types($branch_id = null)
    {
        if ($branch_id) {
            $this->db->where('branch_id', $branch_id);
        } elseif (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }

        $this->db->order_by('priority', 'ASC');
        return $this->db->get('hostel_emergency_types')->result_array();
    }

    public function get_emergency_type($id)
    {
        return $this->db->where('id', $id)->get('hostel_emergency_types')->row_array();
    }

    public function add_emergency_type($data)
    {
        $this->db->insert('hostel_emergency_types', $data);
        return $this->db->insert_id();
    }

    public function update_emergency_type($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('hostel_emergency_types', $data);
    }

    // ============================================
    // EMERGENCY CONTACTS
    // ============================================

    public function get_emergency_contacts($branch_id = null)
    {
        if ($branch_id) {
            $this->db->where('branch_id', $branch_id);
        } elseif (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }

        $this->db->where('is_active', 1);
        $this->db->order_by('priority_order', 'ASC');
        return $this->db->get('hostel_emergency_contacts')->result_array();
    }

    public function add_emergency_contact($data)
    {
        $this->db->insert('hostel_emergency_contacts', $data);
        return $this->db->insert_id();
    }

    public function update_emergency_contact($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('hostel_emergency_contacts', $data);
    }

    // ============================================
    // STUDENT MEDICAL
    // ============================================

    public function get_student_medical($student_id)
    {
        return $this->db->where('student_id', $student_id)
                        ->get('hostel_student_medical')
                        ->row_array();
    }

    public function add_student_medical($data)
    {
        $this->db->insert('hostel_student_medical', $data);
        return $this->db->insert_id();
    }

    public function update_student_medical($student_id, $data)
    {
        $this->db->where('student_id', $student_id);
        return $this->db->update('hostel_student_medical', $data);
    }

    // ============================================
    // SMS TEMPLATES
    // ============================================

    public function get_emergency_sms_template($emergency_type_id, $branch_id)
    {
        $template = $this->db->where('emergency_type_id', $emergency_type_id)
                             ->where('branch_id', $branch_id)
                             ->get('hostel_emergency_sms_templates')
                             ->row_array();

        if (!$template) {
            $template = $this->db->where('emergency_type_id', $emergency_type_id)
                                 ->where('branch_id', 0)
                                 ->get('hostel_emergency_sms_templates')
                                 ->row_array();
        }

        return $template;
    }

    public function get_incident_sms_logs($incident_id)
    {
        return $this->db->where('incident_id', $incident_id)
                        ->order_by('created_at', 'DESC')
                        ->get('hostel_emergency_sms_logs')
                        ->result_array();
    }

    // ============================================
    // RECIPIENT HELPERS
    // ============================================

    public function get_room_assigned_staff($room_id)
    {
        return $this->db->select('s.id as staff_id, s.name as staff_name, s.mobileno')
                        ->from('hostel_staff_assignments a')
                        ->join('staff s', 's.id = a.staff_id')
                        ->where('a.room_id', $room_id)
                        ->where('a.is_active', 1)
                        ->get()
                        ->result_array();
    }

    public function get_branch_admins($branch_id)
    {
        return $this->db->select('s.id, s.name, s.mobileno')
                        ->from('staff s')
                        ->join('login_credential lc', 'lc.user_id = s.id')
                        ->where('lc.role', 2)
                        ->where('s.branch_id', $branch_id)
                        ->where('lc.active', 1)
                        ->group_by('s.id')
                        ->get()
                        ->result_array();
    }

   /**
 * Get parents for multiple students
 */
    public function get_multiple_student_parents($student_ids_string)
    {
        if (empty($student_ids_string)) {
            return array();
        }
        
        $student_ids = explode(',', $student_ids_string);
        $parents = array();
        
        foreach ($student_ids as $student_id) {
            $student = $this->db->select('parent_id')->where('id', $student_id)->get('student')->row();
            if ($student && $student->parent_id) {
                $parent = $this->db->select('id, name, mobileno')
                    ->where('id', $student->parent_id)
                    ->get('parent')
                    ->row_array();
                if ($parent && !isset($parents[$parent['id']])) {
                    $parents[$parent['id']] = $parent;
                }
            }
        }
        
        return array_values($parents);
    }

    /**
 * Get hostel students - FIXED VERSION
 */
public function get_hostel_students($branch_id = null)
{
    if ($branch_id === null) {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    // Get current session ID
    $session_id = get_session_id();
    
    $this->db->select('s.id, s.first_name, s.last_name, s.register_no, s.admission_date, 
                      s.hostel_id, s.room_id, hr.name as room_name, h.name as hostel_name,
                      e.class_id, e.section_id')
             ->from('student s')
             ->join('enroll e', 'e.student_id = s.id AND e.session_id = ' . $this->db->escape($session_id), 'left')
             ->join('hostel_room hr', 'hr.id = s.room_id', 'left')
             ->join('hostel h', 'h.id = s.hostel_id', 'left')
             ->where('s.branch_id', $branch_id)
             ->where('s.hostel_id >', 0)  // Students assigned to hostel
             ->group_by('s.id')
             ->order_by('s.first_name', 'ASC');
    
    // For superadmin, no branch filter
    if (!is_superadmin_loggedin()) {
        $this->db->where('s.branch_id', $branch_id);
    }
    
    $result = $this->db->get()->result_array();
    
    // Debug: Log count
    error_log("get_hostel_students - Branch: {$branch_id}, Students found: " . count($result));
    
    return $result;
}
/**
 * Get student names by comma-separated IDs
 */
public function get_student_names($student_ids_string)
{
    if (empty($student_ids_string)) {
        return 'N/A';
    }
    
    $ids = explode(',', $student_ids_string);
    $names = array();
    
    foreach($ids as $id) {
        $student = $this->db->select('first_name, last_name, register_no')
            ->where('id', trim($id))
            ->get('student')
            ->row();
        if($student) {
            $names[] = $student->first_name . ' ' . $student->last_name . ' (' . $student->register_no . ')';
        }
    }
    
    return implode('<br>', $names);
}
/**
 * Get parents for a student - FIXED VERSION
 */
public function get_student_parents($student_id)
{
    error_log("get_student_parents called for student_id: " . $student_id);
    
    // First get the student to find parent_id
    $student = $this->db->select('parent_id')->where('id', $student_id)->get('student')->row();
    
    error_log("Student found: " . ($student ? 'YES' : 'NO'));
    
    if (!$student || !$student->parent_id) {
        error_log("No parent_id found for student: " . $student_id);
        return array();
    }
    
    error_log("Parent ID found: " . $student->parent_id);
    
    // Get parent details
    $parent = $this->db->select('id, name, mobileno')
                        ->where('id', $student->parent_id)
                        ->get('parent')
                        ->row_array();
    
    error_log("Parent found: " . ($parent ? 'YES' : 'NO'));
    
    if ($parent) {
        error_log("Parent name: " . ($parent['name'] ?? 'Unknown') . ", Mobile: " . ($parent['mobileno'] ?? 'Not set'));
        return array($parent);
    }
    
    return array();
}
}