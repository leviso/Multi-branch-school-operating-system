<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Employee_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    // moderator employee all information
    public function save($data, $role = null, $id = null)
    {
        $inser_data1 = array(
            'branch_id' => $this->application_model->get_branch_id(),
            'name' => $data['name'],
            'sex' => $data['sex'],
            'religion' => $data['religion'],
            'blood_group' => $data['blood_group'],
            'birthday' => $data["birthday"],
            'mobileno' => $data['mobile_no'],
            'present_address' => $data['present_address'],
            'permanent_address' => $data['permanent_address'],
            'photo' => $this->uploadImage('staff'),
            'designation' => $data['designation_id'],
            'department' => $data['department_id'],
            'joining_date' => date("Y-m-d", strtotime($data['joining_date'])),
            'qualification' => $data['qualification'],
            'experience_details' => $data['experience_details'],
            'total_experience' => $data['total_experience'],
            'email' => $data['email'],
            'facebook_url' => $data['facebook'],
            'linkedin_url' => $data['linkedin'],
            'twitter_url' => $data['twitter'],
        );

        $inser_data2 = array(
            'username' => $data["username"],
            'role' => $data["user_role"],
        );

        if (!isset($data['staff_id']) && empty($data['staff_id'])) {
            // RANDOM STAFF ID GENERATE
            $inser_data1['staff_id'] = substr(app_generate_hash(), 3, 7);
            // SAVE EMPLOYEE INFORMATION IN THE DATABASE
            $this->db->insert('staff', $inser_data1);
            $employeeID = $this->db->insert_id();

            // SAVE EMPLOYEE LOGIN CREDENTIAL INFORMATION IN THE DATABASE
            $inser_data2['active'] = 1;
            $inser_data2['user_id'] = $employeeID;
            $inser_data2['password'] = $this->app_lib->pass_hashed($data["password"]);
            $this->db->insert('login_credential', $inser_data2);

            // SAVE USER BANK INFORMATION IN THE DATABASE
            if (!isset($data['chkskipped'])) {
                $data['staff_id'] = $employeeID;
                $this->bankSave($data);
            }
            return $employeeID;
        } else {
            $inser_data1['staff_id'] = $data['staff_id_no'];
            // UPDATE ALL INFORMATION IN THE DATABASE
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $data['staff_id']);
            $this->db->update('staff', $inser_data1);
            // UPDATE LOGIN CREDENTIAL INFORMATION IN THE DATABASE
            $this->db->where('user_id', $data['staff_id']);
            $this->db->where_not_in('role', array(6,7));
            $this->db->update('login_credential', $inser_data2);
        }
    }


    // GET SINGLE EMPLOYEE DETAILS
    public function getSingleStaff($id = '')
    {
        $this->db->select('staff.*,staff_designation.name as designation_name,staff_department.name as department_name,login_credential.role as role_id,login_credential.active,login_credential.username, roles.name as role');
        $this->db->from('staff');
        $this->db->join('login_credential', 'login_credential.user_id = staff.id and login_credential.role != "6" and login_credential.role != "7"', 'inner');
        $this->db->join('roles', 'roles.id = login_credential.role', 'left');
        $this->db->join('staff_designation', 'staff_designation.id = staff.designation', 'left');
        $this->db->join('staff_department', 'staff_department.id = staff.department', 'left');
        $this->db->where('staff.id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('staff.branch_id', get_loggedin_branch_id());
        }
        $query = $this->db->get();
        if ($query->num_rows() == 0) {
            show_404();
        }
        return $query->row_array();
    }

    // get staff all list
    public function getStaffList($branchID = '', $role_id = '', $active = 1)
    {
        $this->db->select('staff.*,staff_designation.name as designation_name,staff_department.name as department_name,login_credential.role as role_id, roles.name as role');
        $this->db->from('staff');
        $this->db->join('login_credential', 'login_credential.user_id = staff.id and login_credential.role != "6" and login_credential.role != "7"', 'inner');
        $this->db->join('roles', 'roles.id = login_credential.role', 'left');
        $this->db->join('staff_designation', 'staff_designation.id = staff.designation', 'left');
        $this->db->join('staff_department', 'staff_department.id = staff.department', 'left');
        if ($branchID != "") {
            $this->db->where('staff.branch_id', $branchID);
        }
        $this->db->where('login_credential.role', $role_id);
        $this->db->where('login_credential.active', $active);
        $this->db->order_by('staff.id', 'ASC');
        return $this->db->get()->result();
    }

    public function get_schedule_by_id($id)
    {
        $this->db->select('timetable_class.*,subject.name as subject_name,class.name as class_name,section.name as section_name');
        $this->db->from('timetable_class');
        $this->db->join('subject', 'subject.id = timetable_class.subject_id', 'inner');
        $this->db->join('class', 'class.id = timetable_class.class_id', 'inner');
        $this->db->join('section', 'section.id = timetable_class.section_id', 'inner');
        $this->db->where('timetable_class.teacher_id', $id);
        $this->db->where('timetable_class.session_id', get_session_id());
        return $this->db->get();
    }

    public function bankSave($data)
    {
        $inser_data = array(
            'staff_id' => $data['staff_id'],
            'bank_name' => $data['bank_name'],
            'holder_name' => $data['holder_name'],
            'bank_branch' => $data['bank_branch'],
            'bank_address' => $data['bank_address'],
            'ifsc_code' => $data['ifsc_code'],
            'account_no' => $data['account_no'],
        );
        if (isset($data['bank_id'])) {
            $this->db->where('id', $data['bank_id']);
            $this->db->update('staff_bank_account', $inser_data);
        } else {
            $this->db->insert('staff_bank_account', $inser_data);
        }  
    }

    public function csvImport($row, $branchID, $userRole, $designationID, $departmentID)
    {
        $inser_data1 = array(
            'name' => $row['Name'],
            'sex' => $row['Gender'],
            'religion' => $row['Religion'],
            'blood_group' => $row['BloodGroup'],
            'birthday' => date("Y-m-d", strtotime($row['DateOfBirth'])),
            'joining_date' => date("Y-m-d", strtotime($row['JoiningDate'])),
            'qualification' => $row['Qualification'],
            'mobileno' => $row['MobileNo'],
            'present_address' => $row['PresentAddress'],
            'permanent_address' => $row['PermanentAddress'],
            'email' => $row['Email'],
            'designation' => $designationID,
            'department' => $departmentID,
            'branch_id' => $branchID,
            'photo' => 'defualt.png',
        );

        $inser_data2 = array(
            'username' => $row["Email"],
            'role' => $userRole,
        );

        // RANDOM STAFF ID GENERATE
        $inser_data1['staff_id'] = substr(app_generate_hash(), 3, 7);
        // SAVE EMPLOYEE INFORMATION IN THE DATABASE
        $this->db->insert('staff', $inser_data1);
        $employeeID = $this->db->insert_id();

        // SAVE EMPLOYEE LOGIN CREDENTIAL INFORMATION IN THE DATABASE
        $inser_data2['active'] = 1;
        $inser_data2['user_id'] = $employeeID;
        $inser_data2['password'] = $this->app_lib->pass_hashed($row["Password"]);
        $this->db->insert('login_credential', $inser_data2);
        return true;
    }
        // ========== TEACHER INTELLIGENCE SYSTEM METHODS ==========
    // ALL DATABASE OPERATIONS ARE IN THE MODEL - NONE IN CONTROLLERS
    
    /**
     * Get workload distribution for a branch
     */
    public function get_workload_distribution($branch_id, $session_id) {
        if (!$branch_id) return null;
        
        $query = $this->db->select("
                COUNT(CASE WHEN status = 'normal' THEN 1 END) as normal_count,
                COUNT(CASE WHEN status = 'overloaded' THEN 1 END) as overloaded_count,
                COUNT(CASE WHEN status = 'underutilized' THEN 1 END) as underutilized_count,
                AVG(workload_score) as avg_score
            ")
            ->from('teacher_workload')
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->get();
        
        return $query->row();
    }
    
    /**
     * Get performance data for teachers
     */
    public function get_performance_data($branch_id, $session_id, $term_id, $rating_filter = null) {
        if (!$branch_id) return array();
        
        $this->db->select('teacher_id, total_score, rating, ranking, lesson_completion_score, attendance_compliance_score, student_impact_score, duty_completion_score, marking_speed_score')
            ->from('teacher_performance_scores')
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('term_id', $term_id)
            ->order_by('total_score', 'DESC');
        
        if ($rating_filter) {
            $this->db->where('rating', $rating_filter);
        }
        
        $query = $this->db->get();
        $result = array();
        
        foreach ($query->result() as $row) {
            $result[$row->teacher_id] = $row;
        }
        
        return $result;
    }
    
    /**
     * Get performance distribution stats
     */
    public function get_performance_distribution($branch_id, $session_id, $term_id) {
        if (!$branch_id) {
            return array('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0);
        }
        
        $query = $this->db->select("rating, COUNT(*) as count")
            ->from('teacher_performance_scores')
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('term_id', $term_id)
            ->group_by('rating')
            ->get();
        
        $stats = array('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0);
        foreach ($query->result() as $row) {
            $stats[$row->rating] = $row->count;
        }
        
        return $stats;
    }
    
    /**
     * Get top performers
     */
    public function get_top_performers($branch_id, $session_id, $term_id, $limit = 5) {
        if (!$branch_id) return array();
        
        $query = $this->db->select('s.name, tps.total_score, tps.rating')
            ->from('teacher_performance_scores tps')
            ->join('staff s', 's.id = tps.teacher_id')
            ->where('tps.branch_id', $branch_id)
            ->where('tps.session_id', $session_id)
            ->where('tps.term_id', $term_id)
            ->order_by('tps.total_score', 'DESC')
            ->limit($limit)
            ->get();
        
        return $query->result();
    }
    
    /**
     * Get risk distribution for a branch
     */
    public function get_risk_distribution($branch_id, $session_id, $term_id) {
        if (!$branch_id) {
            $result = new stdClass();
            $result->low_count = 0;
            $result->medium_count = 0;
            $result->high_count = 0;
            return $result;
        }
        
        $query = $this->db->select("
                COUNT(CASE WHEN risk_level = 'low' THEN 1 END) as low_count,
                COUNT(CASE WHEN risk_level = 'medium' THEN 1 END) as medium_count,
                COUNT(CASE WHEN risk_level = 'high' THEN 1 END) as high_count
            ")
            ->from('teacher_risk_predictions')
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('term_id', $term_id)
            ->get();
        
        $result = $query->row();
        if (!$result) {
            $result = new stdClass();
            $result->low_count = 0;
            $result->medium_count = 0;
            $result->high_count = 0;
        }
        
        return $result;
    }
    
    /**
     * Get all teacher risks data
     */
    public function get_all_teacher_risks($branch_id, $session_id, $term_id, $teachers) {
        if (!$branch_id) return array();
        
        $result = array();
        
        foreach ($teachers as $teacher) {
            // Get risk
            $risk_query = $this->db->where('teacher_id', $teacher->id)
                ->where('branch_id', $branch_id)
                ->where('session_id', $session_id)
                ->where('term_id', $term_id)
                ->get('teacher_risk_predictions');
            
            // Get performance
            $perf_query = $this->db->where('teacher_id', $teacher->id)
                ->where('branch_id', $branch_id)
                ->where('session_id', $session_id)
                ->where('term_id', $term_id)
                ->get('teacher_performance_scores');
            
            // Get workload
            $work_query = $this->db->where('teacher_id', $teacher->id)
                ->where('branch_id', $branch_id)
                ->where('session_id', $session_id)
                ->get('teacher_workload');
            
            $result[$teacher->id] = array(
                'risk' => $risk_query->row(),
                'performance' => $perf_query->row(),
                'workload' => $work_query->row()
            );
        }
        
        return $result;
    }
    
    /**
     * Get high risk teachers
     */
    public function get_high_risk_teachers($branch_id, $session_id, $term_id) {
        if (!$branch_id) return array();
        
        $query = $this->db->select('trp.*, s.name')
            ->from('teacher_risk_predictions trp')
            ->join('staff s', 's.id = trp.teacher_id')
            ->where('trp.branch_id', $branch_id)
            ->where('trp.session_id', $session_id)
            ->where('trp.term_id', $term_id)
            ->where('trp.risk_level', 'high')
            ->get();
        
        return $query->result();
    }
    
    /**
     * Get teacher duties
     */
    public function get_teacher_duties($teacher_id, $branch_id, $session_id) {
        if (!$branch_id) return array();
        
        $query = $this->db->where('teacher_id', $teacher_id)
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->order_by('due_date', 'ASC')
            ->get('teacher_duties');
        
        return $query->result();
    }
    
    /**
     * Get duty statistics for a teacher
     */
    public function get_duty_statistics($teacher_id, $branch_id, $session_id) {
        if (!$branch_id) {
            return array('pending' => 0, 'completed' => 0, 'overdue' => 0);
        }
        
        // Pending count
        $pending_query = $this->db->where('teacher_id', $teacher_id)
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('status', 'pending')
            ->get('teacher_duties');
        $pending = $pending_query->num_rows();
        
        // Completed count
        $completed_query = $this->db->where('teacher_id', $teacher_id)
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('status', 'completed')
            ->get('teacher_duties');
        $completed = $completed_query->num_rows();
        
        // Overdue count
        $overdue_query = $this->db->where('teacher_id', $teacher_id)
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('status', 'pending')
            ->where('due_date <', date('Y-m-d'))
            ->get('teacher_duties');
        $overdue = $overdue_query->num_rows();
        
        return array(
            'pending' => $pending,
            'completed' => $completed,
            'overdue' => $overdue
        );
    }
    
    /**
     * Get total duty points for a teacher
     */
    public function get_total_duty_points($teacher_id, $branch_id, $session_id) {
        if (!$branch_id) return 0;
        
        $query = $this->db->select('SUM(points) as total')
            ->from('teacher_duties')
            ->where('teacher_id', $teacher_id)
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('status', 'completed')
            ->get();
        
        $result = $query->row();
        return $result ? $result->total : 0;
    }
    
    /**
     * Update duty status
     */
    public function update_duty_status($duty_id, $teacher_id, $status) {
        // Verify duty belongs to this teacher
        $query = $this->db->where('id', $duty_id)
            ->where('teacher_id', $teacher_id)
            ->get('teacher_duties');
        
        $duty = $query->row();
        
        if (!$duty) {
            return array('status' => 'error', 'message' => 'Unauthorized');
        }
        
        $update_data = array(
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        if ($status == 'completed') {
            $update_data['completed_date'] = date('Y-m-d');
        }
        
        $this->db->where('id', $duty_id)->update('teacher_duties', $update_data);
        
        return array('status' => 'success');
    }
    
    /**
     * Get teacher workload data for a teacher
     */
    public function get_teacher_workload($teacher_id, $session_id = null) {
        if (!$session_id) $session_id = get_session_id();
        
        $branch_id = get_loggedin_branch_id();
        if (!$branch_id) return null;
        
        $query = $this->db->where('teacher_id', $teacher_id)
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->get('teacher_workload');
        
        return $query->row();
    }
    
    /**
     * Get teacher performance score
     */
    public function get_teacher_performance($teacher_id, $branch_id, $session_id = null, $term_id = null) {
        if (!$branch_id) return null;
        if (!$session_id) $session_id = get_session_id();
        if (!$term_id) $term_id = $this->get_current_term_id($branch_id);
        
        $query = $this->db->where('teacher_id', $teacher_id)
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('term_id', $term_id)
            ->get('teacher_performance_scores');
        
        return $query->row();
    }
    
    /**
     * Get teacher risk prediction
     */
    public function get_teacher_risk($teacher_id, $branch_id, $session_id = null, $term_id = null) {
        if (!$branch_id) return null;
        if (!$session_id) $session_id = get_session_id();
        if (!$term_id) $term_id = $this->get_current_term_id($branch_id);
        
        $query = $this->db->where('teacher_id', $teacher_id)
            ->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('term_id', $term_id)
            ->get('teacher_risk_predictions');
        
        return $query->row();
    }
    
    /**
     * Get current active term for a branch
     */
    public function get_current_term_id($branch_id) {
        if (!$branch_id) return null;
        
        $query = $this->db->select('id')
            ->where('is_active', 1)
            ->where('branch_id', $branch_id)
            ->order_by('id', 'DESC')
            ->get('exam_term');
        
        $term = $query->row();
        
        if ($term) return $term->id;
        
        $query = $this->db->select('id')
            ->where('branch_id', $branch_id)
            ->order_by('id', 'DESC')
            ->get('exam_term');
        
        $term = $query->row();
        return $term ? $term->id : null;
    }
    }
