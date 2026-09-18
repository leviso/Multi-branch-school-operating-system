<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Parents_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    // moderator parents all information
    public function save($data, $getBranch = array())
    {
        $inser_data1 = array(
            'branch_id' => $this->application_model->get_branch_id(),
            'name' => $data['name'],
            'relation' => $data['relation'],
            'father_name' => $data['father_name'],
            'mother_name' => $data['mother_name'],
            'occupation' => $data['occupation'],
            'income' => $data['income'],
            'education' => $data['education'],
            'email' => $data['email'],
            'mobileno' => $data['mobileno'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'photo' => $this->uploadImage('parent'),
            'facebook_url' => $data['facebook'],
            'linkedin_url' => $data['linkedin'],
            'twitter_url' => $data['twitter'],
        );
        
        if (!isset($data['parent_id']) && empty($data['parent_id'])) {
            // save employee information in the database
            $this->db->insert('parent', $inser_data1);
            $parent_id = $this->db->insert_id();
            // save guardian login credential information in the database
            if ($getBranch['grd_generate'] == 1) {
                $username = $getBranch['grd_username_prefix'] . $parent_id;
                $password = $getBranch['grd_default_password'];
            } else {
                $username = $data['username'];
                $password = $data['password'];
            }

            $inser_data2 = array(
                'username' => $username,
                'role' => 6,
                'active' => 1,
                'user_id' => $parent_id,
                'password' => $this->app_lib->pass_hashed($password),
            );
            $this->db->insert('login_credential', $inser_data2);
            
            // send account activate email
            $emailData = array(
                'name' => $data['name'],
                'username' => $username,
                'password' => $password,
                'user_role' => 6,
                'email' => $data['email'],
            );
            $this->email_model->sentStaffRegisteredAccount($emailData);
            return $parent_id;
        } else {
            $this->db->where('id', $data['parent_id']);
            $this->db->update('parent', $inser_data1);
            // update login credential information in the database
            $this->db->where(array('role' => 6, 'user_id' => $data['parent_id']));
            $this->db->update('login_credential', array('username' => $data['username']));
        }

        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getSingleParent($id)
    {
        $this->db->select('parent.*,login_credential.role as role_id,login_credential.active,login_credential.username,login_credential.id as login_id, roles.name as role');
        $this->db->from('parent');
        $this->db->join('login_credential', 'login_credential.user_id = parent.id and login_credential.role = "6"', 'inner');
        $this->db->join('roles', 'roles.id = login_credential.role', 'left');
        $this->db->where('parent.id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('parent.branch_id', get_loggedin_branch_id());
        }
        $query = $this->db->get();
        if ($query->num_rows() == 0) {
            show_404();
        }
        return $query->row_array();
    }

    public function childsResult($parent_id)
    {
        $this->db->select('s.id,s.photo, CONCAT_WS(" ",s.first_name, s.last_name) as fullname,c.name as class_name,se.name as section_name');
        $this->db->from('enroll as e');
        $this->db->join('student as s', 'e.student_id = s.id', 'inner');
        $this->db->join('login_credential as l', 'l.user_id = s.id and l.role = 7', 'inner');
        $this->db->join('class as c', 'e.class_id = c.id', 'left');
        $this->db->join('section as se', 'e.section_id=se.id', 'left');
        $this->db->where('s.parent_id', $parent_id);
        $this->db->where('l.active', 1);
        $this->db->where('e.session_id', get_session_id());
        return $this->db->get()->result_array();
    }

    // get parent all details
    public function getParentList($branchID = null, $active = 1)
    {
        $this->db->select('parent.*,login_credential.active as active');
        $this->db->from('parent');
        $this->db->join('login_credential', 'login_credential.user_id = parent.id and login_credential.role = "6"', 'inner');
        $this->db->where('login_credential.active', $active);
        if (!empty($branchID)) {
           $this->db->where('parent.branch_id', $branchID);
        }
        $this->db->order_by('parent.id', 'ASC');
        return $this->db->get()->result();
    }
 // CSV import for parents
public function csvImport($row = array(), $branchID = '', $branchSettings = array())
{
    // Check if parent already exists by email
    $existingParent = $this->db->select('id')
        ->where('email', $row['Email'])
        ->where('branch_id', $branchID)
        ->get('parent')
        ->row_array();
    
    if (!empty($existingParent)) {
        return $existingParent['id'];
    }
    
    // Prepare parent data
    $parentData = array(
        'branch_id' => $branchID,
        'name' => $row['Name'],
        'relation' => $row['Relation'],
        'father_name' => isset($row['FatherName']) ? $row['FatherName'] : '',
        'mother_name' => isset($row['MotherName']) ? $row['MotherName'] : '',
        'occupation' => isset($row['Occupation']) ? $row['Occupation'] : '',
        'income' => isset($row['Income']) && !empty($row['Income']) ? $row['Income'] : 0,
        'education' => isset($row['Education']) ? $row['Education'] : '',
        'email' => $row['Email'],
        'mobileno' => $row['MobileNo'],
        'address' => isset($row['Address']) ? $row['Address'] : '',
        'city' => isset($row['City']) ? $row['City'] : '',
        'state' => isset($row['State']) ? $row['State'] : '',
        'photo' => 'defualt.png'
    );
    
    $this->db->insert('parent', $parentData);
    $parentID = $this->db->insert_id();
    
    if (!$parentID) {
        return false;
    }
    
    // Generate or use provided credentials
    if ($branchSettings['grd_generate'] == 1) {
        $username = $branchSettings['grd_username_prefix'] . $parentID;
        $password = $branchSettings['grd_default_password'];
    } else {
        $username = !empty($row['Username']) ? $row['Username'] : $row['Email'];
        $password = !empty($row['Password']) ? $row['Password'] : substr(md5(uniqid()), 0, 8);
    }
    
    // Create login credential
    $loginData = array(
        'username' => $username,
        'role' => 6,
        'active' => 1,
        'user_id' => $parentID,
        'password' => $this->app_lib->pass_hashed($password),
        'branch_id' => $branchID
    );
    $this->db->insert('login_credential', $loginData);
    
    // Send welcome email (optional - remove if causing issues)
    // $emailData = array(
    //     'name' => $row['Name'],
    //     'username' => $username,
    //     'password' => $password,
    //     'user_role' => 6,
    //     'email' => $row['Email'],
    // );
    // $this->email_model->sentStaffRegisteredAccount($emailData);
    
    return $parentID;
}
}
