<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Leave_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }


    // get leave list
    public function getLeaveList($where = '', $single = false)
    {
        $this->db->select('la.*,c.name as category_name,r.name as role');
        $this->db->from('leave_application as la');
        $this->db->join('leave_category as c', 'c.id = la.category_id', 'left');
        $this->db->join('roles as r', 'r.id = la.role_id', 'left');
        $this->db->where('session_id', get_session_id());
        if (!empty($where)) {
            $this->db->where($where);
        }
        if ($single == false) {
            $this->db->order_by('la.id', 'DESC');
            return $this->db->get()->result_array();
        } else {
            return $this->db->get()->row_array();
        }
    }
        /**
     * Get leave application with staff contact details for SMS
     * 
     * @param int $leave_id
     * @return array|false
     */
    public function get_leave_with_contact($leave_id)
    {
        $this->db->select('la.*, s.name as staff_name, s.mobileno, s.email, s.branch_id')
            ->from('leave_application la')
            ->join('staff s', 's.id = la.user_id AND la.role_id != 7', 'inner')
            ->where('la.id', $leave_id);
        
        $result = $this->db->get()->row_array();
        
        // Skip if no staff found (student leave)
        if (empty($result) || empty($result['mobileno'])) {
            return false;
        }
        
        return $result;
    }
        /**
     * Get leave application with staff contact details
     * 
     * @param int $leave_id
     * @return array|false
     */
    public function get_leave_with_staff_contact($leave_id)
    {
        $this->db->select('la.*, s.name as staff_name, s.mobileno, s.email, s.branch_id, s.staff_id')
            ->from('leave_application la')
            ->join('staff s', 's.id = la.user_id AND la.role_id != 7', 'inner')
            ->where('la.id', $leave_id);
        
        $result = $this->db->get()->row_array();
        
        if (empty($result) || empty($result['mobileno'])) {
            return false;
        }
        
        return $result;
    }

    /**
     * Get leave application with student and parent contact details
     * 
     * @param int $leave_id
     * @return array|false
     */
    public function get_leave_with_student_contact($leave_id)
    {
        $this->db->select('la.*, 
            s.id as student_id, 
            CONCAT_WS(" ", s.first_name, s.last_name) as student_name, 
            s.mobileno as student_mobileno,
            s.email as student_email,
            p.id as parent_id,
            p.name as parent_name,
            p.mobileno as parent_mobileno,
            p.email as parent_email,
            s.branch_id')
            ->from('leave_application la')
            ->join('student s', 's.id = la.user_id AND la.role_id = 7', 'inner')
            ->join('parent p', 'p.id = s.parent_id', 'left')
            ->where('la.id', $leave_id);
        
        $result = $this->db->get()->row_array();
        
        if (empty($result)) {
            return false;
        }
        
        return $result;
    }

    /**
     * Get pending leave requests for admin notification
     * 
     * @param int $branch_id
     * @return array
     */
    public function get_pending_leaves_for_notification($branch_id = null)
    {
        $this->db->select('la.*, 
            CASE 
                WHEN la.role_id = 7 THEN CONCAT_WS(" ", s.first_name, s.last_name)
                ELSE st.name
            END as applicant_name,
            la.role_id,
            la.branch_id')
            ->from('leave_application la');
        
        // Join based on role
        $this->db->join('staff st', 'st.id = la.user_id AND la.role_id != 7', 'left');
        $this->db->join('student s', 's.id = la.user_id AND la.role_id = 7', 'left');
        
        $this->db->where('la.status', 1); // Pending
        $this->db->where('DATE(la.apply_date) >=', date('Y-m-d', strtotime('-1 day')));
        
        if ($branch_id) {
            $this->db->where('la.branch_id', $branch_id);
        }
        
        $this->db->order_by('la.apply_date', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get leave applications that start tomorrow (for reminder)
     * 
     * @param int $branch_id
     * @return array
     */
    public function get_upcoming_leaves($branch_id = null)
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $this->db->select('la.*, 
            CASE 
                WHEN la.role_id = 7 THEN CONCAT_WS(" ", s.first_name, s.last_name)
                ELSE st.name
            END as applicant_name,
            la.role_id,
            la.branch_id')
            ->from('leave_application la');
        
        // Join based on role
        $this->db->join('staff st', 'st.id = la.user_id AND la.role_id != 7', 'left');
        $this->db->join('student s', 's.id = la.user_id AND la.role_id = 7', 'left');
        
        $this->db->where('la.status', 2); // Approved
        $this->db->where('la.start_date', $tomorrow);
        
        if ($branch_id) {
            $this->db->where('la.branch_id', $branch_id);
        }
        
        return $this->db->get()->result_array();
    }

    /**
     * Get leave applications ending today (for return reminder)
     * 
     * @param int $branch_id
     * @return array
     */
    public function get_today_returning_leaves($branch_id = null)
    {
        $today = date('Y-m-d');
        
        $this->db->select('la.*, 
            CASE 
                WHEN la.role_id = 7 THEN CONCAT_WS(" ", s.first_name, s.last_name)
                ELSE st.name
            END as applicant_name,
            la.role_id,
            la.branch_id')
            ->from('leave_application la');
        
        // Join based on role
        $this->db->join('staff st', 'st.id = la.user_id AND la.role_id != 7', 'left');
        $this->db->join('student s', 's.id = la.user_id AND la.role_id = 7', 'left');
        
        $this->db->where('la.status', 2); // Approved
        $this->db->where('la.end_date', $today);
        
        if ($branch_id) {
            $this->db->where('la.branch_id', $branch_id);
        }
        
        return $this->db->get()->result_array();
    }
}
