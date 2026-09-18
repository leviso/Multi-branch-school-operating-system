<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Online_admission_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function save($data = array(), $getBranch = array())
{
    // log_message('debug', '=== SAVE METHOD STARTED ===');
    // log_message('debug', 'Branch data: ' . print_r($getBranch, true));
    // log_message('debug', 'Form data: ' . print_r($data, true));
    // log_message('debug', 'POST data: ' . print_r($_POST, true));
    
    // Check for required data
    if (empty($data['first_name']) || empty($data['class_id'])) {
        // log_message('error', 'Missing required data in save()');
        return false;
    }
    
    $existStudent_photo = isset($data['exist_student_photo']) ? $data['exist_student_photo'] : '';
    $existGuardian_photo = isset($data['exist_guardian_photo']) ? $data['exist_guardian_photo'] : '';
    
    // Handle student photo
    if (empty($existStudent_photo)) {
        $studentPhoto = $this->uploadImage('student', 'student_photo');
        if ($studentPhoto === false) {
            $studentPhoto = 'defualt.png';
        }
    } else {
        $studentPhoto = $existStudent_photo;
    }
    
    // Handle guardian photo
    if (empty($existGuardian_photo)) {
        $guardianPhoto = $this->uploadImage('parent', 'guardian_photo');
        if ($guardianPhoto === false) {
            $guardianPhoto = 'defualt.png';
        }
    } else {
        $guardianPhoto = $existGuardian_photo;
    }
    
    // Set default values for required fields
    $hostelID = isset($data['hostel_id']) ? $data['hostel_id'] : 0;
    $roomID = isset($data['room_id']) ? $data['room_id'] : 0;
    $category_id = isset($data['category_id']) ? $data['category_id'] : 0;
    
    // Previous details
    $previous_details = array(
        'school_name' => isset($data['school_name']) ? $data['school_name'] : '',
        'qualification' => isset($data['qualification']) ? $data['qualification'] : '',
        'remarks' => isset($data['previous_remarks']) ? $data['previous_remarks'] : '',
    );
    $previous_details = json_encode($previous_details);
    
    // CRITICAL: Add branch_id to student record
    $branchID = $getBranch['id'] ?? $data['branch_id'] ?? 1;
    
    // Prepare student data with ALL required fields
    $inser_data1 = array(
        'register_no' => $data['register_no'] ?? $this->regSerNumber($branchID),
        'admission_date' => isset($data['admission_date']) ? date("Y-m-d", strtotime($data['admission_date'])) : date('Y-m-d'),
        'first_name' => $data['first_name'] ?? '',
        'last_name' => $data['last_name'] ?? '',
        'gender' => $data['gender'] ?? '',
        'birthday' => isset($data['birthday']) ? date("Y-m-d", strtotime($data['birthday'])) : NULL,
        'religion' => $data['religion'] ?? NULL,
        'caste' => $data['caste'] ?? NULL,
        'blood_group' => $data['blood_group'] ?? NULL,
        'mother_tongue' => $data['mother_tongue'] ?? NULL,
        'current_address' => $data['current_address'] ?? NULL,
        'permanent_address' => $data['permanent_address'] ?? NULL,
        'city' => $data['city'] ?? NULL,
        'state' => $data['state'] ?? NULL,
        'mobileno' => $data['mobileno'] ?? NULL,
        'category_id' => $category_id,
        'email' => $data['email'] ?? NULL,
        'parent_id' => 0, // Will be updated if guardian exists
        'branch_id' => $branchID, // CRITICAL: Add branch_id
        'route_id' => isset($data['route_id']) ? $data['route_id'] : 0,
        'vehicle_id' => isset($data['vehicle_id']) ? $data['vehicle_id'] : 0,
        'hostel_id' => $hostelID,
        'room_id' => $roomID,
        'previous_details' => $previous_details,
        'photo' => $studentPhoto,
    );
    
    // log_message('debug', 'Student data to insert: ' . print_r($inser_data1, true));
    
    // Add guardian if data exists
    $parentID = 0;
    if (!empty($data['grd_name']) || !empty($data['father_name'])) {
        $arrayParent = array(
            'name' => $data['grd_name'] ?? '',
            'relation' => $data['grd_relation'] ?? '',
            'father_name' => $data['father_name'] ?? '',
            'mother_name' => $data['mother_name'] ?? '',
            'occupation' => $data['grd_occupation'] ?? '',
            'income' => $data['grd_income'] ?? '',
            'education' => $data['grd_education'] ?? '',
            'email' => $data['grd_email'] ?? '',
            'mobileno' => $data['grd_mobileno'] ?? '',
            'address' => $data['grd_address'] ?? '',
            'city' => $data['grd_city'] ?? '',
            'state' => $data['grd_state'] ?? '',
            'branch_id' => $branchID,
            'photo' => $guardianPhoto,
        );
        
        // log_message('debug', 'Parent data to insert: ' . print_r($arrayParent, true));
        
        $this->db->insert('parent', $arrayParent);
        $parentID = $this->db->insert_id();
        
        // Create guardian login credentials
        if (isset($getBranch['grd_generate']) && $getBranch['grd_generate'] == 1) {
            $grd_username = $getBranch['grd_username_prefix'] . $parentID;
            $grd_password = $getBranch['grd_default_password'];
        } else {
            $grd_username = $data['grd_username'] ?? 'parent' . $parentID;
            $grd_password = $data['grd_password'] ?? 'password123';
        }
        
        $parent_credential = array(
            'username' => $grd_username,
            'role' => 6,
            'user_id' => $parentID,
            'password' => $this->app_lib->pass_hashed($grd_password),
        );
        
        // log_message('debug', 'Parent credential: ' . print_r($parent_credential, true));
        
        $this->db->insert('login_credential', $parent_credential);
        
        // Update student with parent ID
        $inser_data1['parent_id'] = $parentID;
    }
    
    // log_message('debug', 'Inserting student with data: ' . print_r($inser_data1, true));
    
    // Insert student
    $this->db->insert('student', $inser_data1);
    $student_id = $this->db->insert_id();
    
    if (!$student_id) {
        // log_message('error', 'Failed to insert student. Error: ' . $this->db->error()['message']);
        return false;
    }
    
    // log_message('debug', 'Student created with ID: ' . $student_id);
    
    // Create student login credentials
    if (isset($getBranch['stu_generate']) && $getBranch['stu_generate'] == 1) {
        $stu_username = $getBranch['stu_username_prefix'] . $student_id;
        $stu_password = $getBranch['stu_default_password'];
    } else {
        $stu_username = $data['username'] ?? 'student' . $student_id;
        $stu_password = $data['password'] ?? 'password123';
    }
    
    $inser_data2 = array(
        'user_id' => $student_id,
        'username' => $stu_username,
        'role' => 7,
        'password' => $this->app_lib->pass_hashed($stu_password),
    );
    
    // log_message('debug', 'Student credential: ' . print_r($inser_data2, true));
    
    $this->db->insert('login_credential', $inser_data2);
    
    // Return student information
    $studentData = array(
        'student_id' => $student_id,
        'email' => $data['email'] ?? '',
        'username' => $stu_username,
        'password' => $stu_password,
        'register_no' => $inser_data1['register_no'],
    );
    
    // log_message('debug', 'Returning student data: ' . print_r($studentData, true));
    // log_message('debug', '=== SAVE METHOD COMPLETED ===');
    
    return $studentData;
}
    public function getOnlineAdmission($class_id = '', $branch_id = '')
    {
        $this->db->select('oa.*, c.name as class_name, se.name as section_name');
        $this->db->from('online_admission as oa');
        $this->db->join('class as c', 'oa.class_id = c.id', 'left');
        $this->db->join('section as se', 'oa.section_id = se.id', 'left');
        
        if (!empty($class_id)) {
            $this->db->where('oa.class_id', $class_id);
        }
        
        $this->db->where('oa.branch_id', $branch_id);
        $this->db->where('oa.status', 1); // Only pending admissions
        $this->db->order_by('oa.id', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    public function regSerNumber($school_id = '')
    {
        $registerNoPrefix = '';
        if (!empty($school_id)) {
            $schoolconfig = $this->db->select('reg_prefix_enable,reg_start_from,institution_code,reg_prefix_digit')->where(array('id' => $school_id))->get('branch')->row();
            if ($schoolconfig->reg_prefix_enable == 1) {
                $registerNoPrefix = $schoolconfig->institution_code . $schoolconfig->reg_start_from;
                $last_registerNo = $this->app_lib->studentLastRegID($school_id);
                if (!empty($last_registerNo)) {
                    $last_registerNo_digit = str_replace($schoolconfig->institution_code, "", $last_registerNo->register_no);
                    if (!is_numeric($last_registerNo_digit)) {
                        $last_registerNo_digit = $schoolconfig->reg_start_from;
                    } else {
                        $last_registerNo_digit = $last_registerNo_digit + 1;
                    }
                    $registerNoPrefix = $schoolconfig->institution_code . sprintf("%0" . $schoolconfig->reg_prefix_digit . "d", $last_registerNo_digit);
                } else {
                    $registerNoPrefix = $schoolconfig->institution_code . sprintf("%0" . $schoolconfig->reg_prefix_digit . "d", $schoolconfig->reg_start_from);
                }
            }
            return $registerNoPrefix;
        } else {
            $config = $this->db->select('institution_code,reg_prefix')->where(array('id' => 1))->get('global_settings')->row();
            if ($config->reg_prefix == 'on') {
                $prefix = $config->institution_code;
            }
            $result = $this->db->select("max(id) as id")->get('student')->row_array();
            $id = $result["id"];
            if (!empty($id)) {
                $maxNum = str_pad($id + 1, 5, '0', STR_PAD_LEFT);
            } else {
                $maxNum = '00001';
            }
            return ($prefix . $maxNum);
        }
    }

    /**
 * Save student from direct approval (without form) - FIXED VERSION
 */
public function save_direct_approval($data = array(), $getBranch = array())
{
    error_log("=== SAVE DIRECT APPROVAL STARTED ===");
    error_log("Data received: " . print_r($data, true));
    error_log("Branch config: " . print_r($getBranch, true));
    
    // Validate required data
    if (empty($data['first_name']) || empty($data['branch_id'])) {
        error_log("ERROR: Missing required data in save_direct_approval()");
        return false;
    }
    
    $branchID = $data['branch_id'];
    $parentID = 0;
    
    error_log("Processing for branch: " . $branchID);
    
    // Prepare student data - FIXED VERSION
    $inser_data1 = array(
        'register_no' => $data['register_no'] ?? $this->regSerNumber($branchID),
        'admission_date' => isset($data['admission_date']) ? date("Y-m-d", strtotime($data['admission_date'])) : date('Y-m-d'),
        'first_name' => $data['first_name'] ?? '',
        'last_name' => $data['last_name'] ?? '',
        'gender' => $data['gender'] ?? '',
        'birthday' => isset($data['birthday']) ? date("Y-m-d", strtotime($data['birthday'])) : NULL,
        'religion' => $data['religion'] ?? NULL,
        'caste' => $data['caste'] ?? NULL,
        'blood_group' => $data['blood_group'] ?? NULL,
        'mother_tongue' => $data['mother_tongue'] ?? NULL,
        'current_address' => $data['present_address'] ?? NULL,
        'permanent_address' => $data['permanent_address'] ?? NULL,
        'city' => $data['city'] ?? NULL,
        'state' => $data['state'] ?? NULL,
        'mobileno' => $data['mobile_no'] ?? NULL,
        'category_id' => $data['category_id'] ?? 0,
        'email' => $data['email'] ?? NULL,
        'parent_id' => 0, // Will be updated if guardian exists
        'branch_id' => $branchID, // CRITICAL: Add branch_id
        'route_id' => 0,
        'vehicle_id' => 0,
        'hostel_id' => 0,
        'room_id' => 0,
        'previous_details' => $data['previous_school_details'] ?? '',
        'photo' => $data['student_photo'] ?? 'defualt.png',
    );
    
    error_log("Student data to insert: " . print_r($inser_data1, true));
    
    // Create guardian if data exists
    if (!empty($data['guardian_name']) || !empty($data['father_name'])) {
        $arrayParent = array(
            'name' => $data['guardian_name'] ?? '',
            'relation' => $data['guardian_relation'] ?? '',
            'father_name' => $data['father_name'] ?? '',
            'mother_name' => $data['mother_name'] ?? '',
            'occupation' => $data['grd_occupation'] ?? '',
            'income' => $data['grd_income'] ?? '',
            'education' => $data['grd_education'] ?? '',
            'email' => $data['grd_email'] ?? '',
            'mobileno' => $data['grd_mobile_no'] ?? '',
            'address' => $data['grd_address'] ?? '',
            'city' => $data['grd_city'] ?? '',
            'state' => $data['grd_state'] ?? '',
            'branch_id' => $branchID,
            'photo' => $data['grd_photo'] ?? 'defualt.png',
        );
        
        error_log("Parent data to insert: " . print_r($arrayParent, true));
        
        $this->db->insert('parent', $arrayParent);
        $parentID = $this->db->insert_id();
        
        error_log("Parent created with ID: " . $parentID);
        
        // Create guardian login credentials
        if (isset($getBranch['grd_generate']) && $getBranch['grd_generate'] == 1) {
            $grd_username = $getBranch['grd_username_prefix'] . $parentID;
            $grd_password = $getBranch['grd_default_password'];
        } else {
            // Generate random credentials
            $grd_username = 'parent' . $parentID;
            $grd_password = substr(md5(time()), 0, 8);
        }
        
        $parent_credential = array(
            'username' => $grd_username,
            'role' => 6,
            'user_id' => $parentID,
            'password' => $this->app_lib->pass_hashed($grd_password),
        );
        
        error_log("Parent credential: " . print_r($parent_credential, true));
        
        $this->db->insert('login_credential', $parent_credential);
        
        // Update student with parent ID
        $inser_data1['parent_id'] = $parentID;
    }
    
    // Insert student
    error_log("Inserting student record...");
    $this->db->insert('student', $inser_data1);
    $student_id = $this->db->insert_id();
    
    if (!$student_id) {
        $error = $this->db->error();
        error_log("ERROR: Failed to insert student. Database error: " . $error['message']);
        return false;
    }
    
    error_log("Student created with ID: " . $student_id);
    
    // Create student login credentials
    if (isset($getBranch['stu_generate']) && $getBranch['stu_generate'] == 1) {
        $stu_username = $getBranch['stu_username_prefix'] . $student_id;
        $stu_password = $getBranch['stu_default_password'];
    } else {
        $stu_username = 'student' . $student_id;
        $stu_password = substr(md5(time() . $student_id), 0, 8);
    }
    
    $inser_data2 = array(
        'user_id' => $student_id,
        'username' => $stu_username,
        'role' => 7,
        'password' => $this->app_lib->pass_hashed($stu_password),
    );
    
    error_log("Student credential: " . print_r($inser_data2, true));
    
    $this->db->insert('login_credential', $inser_data2);
    
    // Return student information for notification
    $studentData = array(
        'student_id' => $student_id,
        'email' => $data['email'] ?? '',
        'username' => $stu_username,
        'password' => $stu_password,
        'register_no' => $inser_data1['register_no'],
        'grd_username' => $grd_username ?? null,
        'grd_password' => $grd_password ?? null
    );
    
    error_log("Returning student data: " . print_r($studentData, true));
    error_log("=== SAVE DIRECT APPROVAL COMPLETED ===");
    
    return $studentData;
}
/**
 * Validate interview can be completed
 */
public function can_complete_interview($interview_id, $branch_id)
{
    $this->db->select('*');
    $this->db->from('online_admission_interviews');
    $this->db->where('id', $interview_id);
    $this->db->where('branch_id', $branch_id);
    $this->db->where_in('status', ['scheduled', 'rescheduled']);
    
    return $this->db->get()->row_array();
}

/**
 * Get interview for post-interview decision
 */
public function get_completed_interview($admission_id)
{
    $this->db->select('i.*, oa.status as admission_status');
    $this->db->from('online_admission_interviews i');
    $this->db->join('online_admission oa', 'oa.id = i.admission_id');
    $this->db->where('i.admission_id', $admission_id);
    $this->db->where('i.status', 'completed');
    $this->db->order_by('i.updated_at', 'DESC');
    
    return $this->db->get()->row_array();
}

    /**
 * Create interview record
 */
public function create_interview($data)
{
    $this->db->insert('online_admission_interviews', $data);
    return $this->db->insert_id();
}

/**
 * Update interview record
 */
public function update_interview($interview_id, $data)
{
    $this->db->where('id', $interview_id);
    $this->db->update('online_admission_interviews', $data);
    return $this->db->affected_rows();
}

/**
 * Get interview with admission details
 */
public function get_interview_details($interview_id, $branch_id = null)
{
    $this->db->select('i.*, oa.*, s.name as interviewer_name, 
                      b.name as branch_name, b.mobileno as branch_phone');
    $this->db->from('online_admission_interviews i');
    $this->db->join('online_admission oa', 'oa.id = i.admission_id', 'left');
    $this->db->join('staff s', 's.id = i.interviewer_id', 'left');
    $this->db->join('branch b', 'b.id = i.branch_id', 'left');
    $this->db->where('i.id', $interview_id);
    
    if ($branch_id) {
        $this->db->where('i.branch_id', $branch_id);
    }
    
    return $this->db->get()->row_array();
}

/**
 * Get admission with interview status
 */
public function get_admission_with_interview($admission_id)
{
    $this->db->select('oa.*, i.id as interview_id, i.status as interview_status');
    $this->db->from('online_admission oa');
    $this->db->join('online_admission_interviews i', 'i.admission_id = oa.id AND i.status != "cancelled"', 'left');
    $this->db->where('oa.id', $admission_id);
    return $this->db->get()->row_array();
}

/**
 * Update admission status
 */
public function update_admission_status($admission_id, $status)
{
    $this->db->where('id', $admission_id);
    $this->db->update('online_admission', ['status' => $status]);
    return $this->db->affected_rows();
}

/**
 * Check if admission has pending interview
 */
public function has_pending_interview($admission_id)
{
    $this->db->where('admission_id', $admission_id);
    $this->db->where_in('status', ['scheduled', 'rescheduled']);
    return $this->db->get('online_admission_interviews')->num_rows() > 0;
}


/**
 * Standardized query with branch filter
 */
public function query_with_branch($table, $conditions = [], $branch_field = 'branch_id')
{
    $this->db->from($table);
    
    foreach ($conditions as $field => $value) {
        $this->db->where($field, $value);
    }
    
    // Apply branch filter for non-superadmin
    if (!is_superadmin_loggedin()) {
        $branchID = $this->get_validated_branch_id();
        $this->db->where($branch_field, $branchID);
    }
    
    return $this->db->get();
}

// Update methods to use this:
public function get_admission($student_id, $exclude_status = 2, $branch_check = true)
{
    $conditions = [
        'id' => $student_id,
        'status !=' => $exclude_status
    ];
    
    $query = $this->query_with_branch('online_admission', $conditions);
    return $query->row_array();
}
}

