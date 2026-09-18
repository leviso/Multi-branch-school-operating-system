<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 1.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Parents.php
 * @copyright : Reserved Synobix Team
 */

class Parents extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('custom_fields');
        $this->load->model('email_model');
        $this->load->model('parents_model');
    }

    public function index()
    {
        redirect(base_url('parents/view'));
    }

    /* parent form validation rules */
    protected function parent_validation()
    {
        $getBranch = $this->getBranchDetails();
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'trim|required');
        }
        $this->form_validation->set_rules('name', translate('name'), 'trim|required');
        $this->form_validation->set_rules('relation', translate('relation'), 'trim|required');
        $this->form_validation->set_rules('occupation', translate('occupation'), 'trim|required');
        $this->form_validation->set_rules('income', translate('income'), 'trim|numeric');
        $this->form_validation->set_rules('mobileno', translate('mobile_no'), 'trim|required');
        $this->form_validation->set_rules('email', translate('email'), 'trim|valid_email');
        $this->form_validation->set_rules('user_photo', translate('profile_picture'), 'callback_photoHandleUpload[user_photo]');
        $this->form_validation->set_rules('facebook', 'Facebook', 'valid_url');
        $this->form_validation->set_rules('twitter', 'Twitter', 'valid_url');
        $this->form_validation->set_rules('linkedin', 'Linkedin', 'valid_url');
        if ($getBranch['grd_generate'] == 0 || isset($_POST['parent_id'])) {
            $this->form_validation->set_rules('username', translate('username'), 'trim|required|callback_unique_username');
            if (!isset($_POST['parent_id'])) {
                $this->form_validation->set_rules('password', translate('password'), 'trim|required|min_length[4]');
                $this->form_validation->set_rules('retype_password', translate('retype_password'), 'trim|required|matches[password]');
            }
        }
        // custom fields validation rules
        $class_slug = $this->router->fetch_class();
        $customFields = getCustomFields($class_slug);
        foreach ($customFields as $fields_key => $fields_value) {
            if ($fields_value['required']) {
                $fieldsID = $fields_value['id'];
                $fieldLabel = $fields_value['field_label'];
                $this->form_validation->set_rules("custom_fields[parents][" . $fieldsID . "]", $fieldLabel, 'trim|required');
            }
        }
    }

    /* parents list user interface  */
    public function view()
    {
        // check access permission
        if (!get_permission('parent', 'is_view')) {
            access_denied();
        }
        $this->data['branch_id'] = $this->application_model->get_branch_id();
        $this->data['title'] = translate('parents_list');
        $this->data['sub_page'] = 'parents/view';
        $this->data['main_menu'] = 'parents';
        $this->load->view('layout/index', $this->data);
    }

    /* user all information are prepared and stored in the database here */
    public function add()
    {
        if (!get_permission('parent', 'is_add')) {
            access_denied();
        }

        $getBranch = $this->getBranchDetails();
        if ($this->input->post('submit') == 'save') {

            // check saas parents add limit
            if (!checkSaasLimit('parent')) {
                set_alert('error', translate('update_your_package'));
                redirect(site_url('dashboard'));
            }
            
            $this->parent_validation();
            if ($this->form_validation->run() == true) {
                $post = $this->input->post();
                //save all employee information in the database
                $parentID = $this->parents_model->save($post, $getBranch);

                // handle custom fields data
                $class_slug = $this->router->fetch_class();
                $customField = $this->input->post("custom_fields[$class_slug]");
                if (!empty($customField)) {
                    saveCustomFields($customField, $parentID);
                }
                set_alert('success', translate('information_has_been_saved_successfully'));
                redirect(base_url('parents/add'));
            }
        }
        $this->data['getBranch'] = $getBranch;
        $this->data['branch_id'] = $this->application_model->get_branch_id();
        $this->data['title'] = translate('add_parent');
        $this->data['sub_page'] = 'parents/add';
        $this->data['main_menu'] = 'parents';
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/dropify/css/dropify.min.css',
            ),
            'js' => array(
                'vendor/dropify/js/dropify.min.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }

    /* parents deactivate list user interface  */
    public function disable_authentication()
    {
        // check access permission
        if (!get_permission('parent_disable_authentication', 'is_view')) {
            access_denied();
        }
        if (isset($_POST['auth'])) {
            if (!get_permission('parent_disable_authentication', 'is_add')) {
                access_denied();
            }
            $stafflist = $this->input->post('views_bulk_operations');
            if (isset($stafflist)) {
                foreach ($stafflist as $id) {
                    $this->db->where(array('role' => 6, 'user_id' => $id));
                    $this->db->update('login_credential', array('active' => 1));
                }
                set_alert('success', translate('information_has_been_updated_successfully'));
            } else {
                set_alert('error', 'Please select at least one item');
            }
            redirect(base_url('parents/disable_authentication'));
        }
        $this->data['parentslist'] = $this->parents_model->getParentList('', 0);
        $this->data['title'] = translate('deactivate_account');
        $this->data['sub_page'] = 'parents/disable_authentication';
        $this->data['main_menu'] = 'parents';
        $this->load->view('layout/index', $this->data);
    }

    /* profile preview and information are controlled here */
    public function profile($id = '')
    {
        if (!get_permission('parent', 'is_edit')) {
            access_denied();
        }
        if (isset($_POST['update'])) {
            $this->parent_validation();
            if ($this->form_validation->run() == true) {
                $post = $this->input->post();
                //save all employee information in the database
                $this->parents_model->save($post);

                // handle custom fields data
                $class_slug = $this->router->fetch_class();
                $customField = $this->input->post("custom_fields[$class_slug]");
                if (!empty($customField)) {
                    saveCustomFields($customField, $id);
                }
                set_alert('success', translate('information_has_been_saved_successfully'));
                $this->session->set_flashdata('profile_tab', 1);
                redirect(base_url('parents/profile/' . $id));
            } else {
                $this->session->set_flashdata('profile_tab', 1);
            }
        }
        $this->data['student_id'] = $id;
        $this->data['parent'] = $this->parents_model->getSingleParent($id);
        $this->data['title'] = translate('parents_profile');
        $this->data['main_menu'] = 'parents';
        $this->data['sub_page'] = 'parents/profile';
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/dropify/css/dropify.min.css',
            ),
            'js' => array(
                'vendor/dropify/js/dropify.min.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }

    /* parents delete  */
    public function delete($id = '')
    {
        // check access permission
        if (!get_permission('parent', 'is_delete')) {
            access_denied();
        }

        // delete from parent table
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $this->db->where('id', $id);
        $this->db->delete('parent');
        if ($this->db->affected_rows() > 0) {
            $this->db->where(array('user_id' => $id, 'role' => 6));
            $this->db->delete('login_credential');
        }
    }

    // unique valid username verification is done here
    public function unique_username($username)
    {
        if (empty($username)) {
            return true;
        }
        $parent_id = $this->input->post('parent_id');
        if (!empty($parent_id)) {
            $login_id = $this->app_lib->get_credential_id($parent_id, 'parent');
            $this->db->where_not_in('id', $login_id);
        }
        $this->db->where('username', $username);
        $query = $this->db->get('login_credential');
        if ($query->num_rows() > 0) {
            $this->form_validation->set_message("unique_username", translate('already_taken'));
            return false;
        } else {
            return true;
        }
    }

    /* password change here */
    public function change_password()
    {
        if (!get_permission('parent', 'is_edit')) {
            ajax_access_denied();
        }
        if (!isset($_POST['authentication'])) {
            $this->form_validation->set_rules('password', translate('password'), 'trim|required|min_length[4]');
        } else {
            $this->form_validation->set_rules('password', translate('password'), 'trim');
        }
        if ($this->form_validation->run() !== false) {
            $parentID = $this->input->post('parent_id');
            $password = $this->input->post('password');
            if (!isset($_POST['authentication'])) {
                $this->db->where('role', 6);
                $this->db->where('user_id', $parentID);
                $this->db->update('login_credential', array('password' => $this->app_lib->pass_hashed($password)));
            } else {
                $this->db->where('role', 6);
                $this->db->where('user_id', $parentID);
                $this->db->update('login_credential', array('active' => 0));
            }
            set_alert('success', translate('information_has_been_updated_successfully'));
            $array = array('status' => 'success');
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'error' => $error);
        }
        echo json_encode($array);
    }

    /* to set the children id in the session after the parent login */
    public function select_child($id = '')
    {
        if (is_parent_loggedin()) {
            $query = $this->db->select('id')->where(array('id' => $id, 'parent_id' => get_loggedin_user_id()))->get('student');
            if ($query->num_rows() == 1) {
                $this->session->set_userdata('myChildren_id', $id);
            }
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->session->set_userdata('last_page', current_url());
            redirect(base_url(), 'refresh');
        }
    }

    public function my_children($id = '')
    {
        if (is_parent_loggedin()) {
            $this->session->set_userdata('myChildren_id', '');
            redirect(base_url('dashboard'));
        } else {
            $this->session->set_userdata('last_page', current_url());
            redirect(base_url(), 'refresh');
        }
    }
  /* parent csv importer */
public function csv_import()
{
    // Force JSON response for AJAX
    $this->output->set_content_type('application/json');
    
    // check access permission
    if (!get_permission('parent', 'is_add')) {
        echo json_encode(array('status' => 'fail', 'message' => translate('access_denied')));
        return;
    }

    $branchID = $this->application_model->get_branch_id();
    
    // Check if file was uploaded
    if (!isset($_FILES['userfile']) || $_FILES['userfile']['error'] != UPLOAD_ERR_OK) {
        echo json_encode(array('status' => 'fail', 'message' => 'Please select a valid CSV file to upload'));
        return;
    }
    
    // Get branch ID from form if superadmin
    if (is_superadmin_loggedin() == true && $this->input->post('branch_id')) {
        $branchID = $this->input->post('branch_id');
    }
    
    // Check saas parents add limit
    if ($this->app_lib->isExistingAddon('saas')) {
        if (!checkSaasLimit('parent')) {
            echo json_encode(array('status' => 'fail', 'message' => translate('update_your_package')));
            return;
        }
    }
    
    $this->load->library('csvimport');
    $csv_array = $this->csvimport->get_array($_FILES["userfile"]["tmp_name"]);
    
    if (!$csv_array || count($csv_array) == 0) {
        echo json_encode(array('status' => 'fail', 'message' => 'Could not read CSV file. Please check file format.'));
        return;
    }
    
    // Define required column headers
    $requiredHeaders = array('Name', 'Relation', 'MobileNo', 'Email');
    $allHeaders = array('Name','Relation','FatherName','MotherName','Occupation','Income','Education','MobileNo','Email','Address','City','State','Username','Password');
    
    // Get first row to check headers
    $firstRow = reset($csv_array);
    $csvHeaders = array_keys($firstRow);
    
    // Check for required headers
    $missingRequired = array_diff($requiredHeaders, $csvHeaders);
    if (!empty($missingRequired)) {
        echo json_encode(array('status' => 'fail', 'message' => 'CSV missing required columns: ' . implode(', ', $missingRequired)));
        return;
    }
    
    // Get branch settings
    $branchSettings = $this->db->select('grd_generate, grd_username_prefix, grd_default_password')
        ->where('id', $branchID)
        ->get('branch')
        ->row_array();
    
    $err_msg = "";
    $successCount = 0;
    
    foreach ($csv_array as $index => $row) {
        // Skip header row if needed (check if row looks like data)
        if ($index == 0 && (strtolower($row['Name']) == 'name' || empty($row['Name']))) {
            continue;
        }
        
        // Skip empty rows
        if (empty($row['Name']) && empty($row['Email'])) {
            continue;
        }
        
        // Check required fields
        if (empty($row['Name']) || empty($row['Relation']) || empty($row['MobileNo']) || empty($row['Email'])) {
            $err_msg .= "Row " . ($index + 1) . " - Missing required fields (Name, Relation, MobileNo, Email)<br>";
            continue;
        }
        
        // Check if email already exists in this branch
        $this->db->where('email', $row['Email']);
        $this->db->where('branch_id', $branchID);
        $existingParent = $this->db->get('parent')->row();
        
        if ($existingParent) {
            $err_msg .= $row['Name'] . " - Email '{$row['Email']}' already exists.<br>";
            continue;
        }
        
        // Check if username already exists (if provided)
        if (!empty($row['Username'])) {
            $this->db->where('username', $row['Username']);
            $existingUser = $this->db->get('login_credential')->row();
            if ($existingUser) {
                $err_msg .= $row['Name'] . " - Username '{$row['Username']}' already exists.<br>";
                continue;
            }
        }
        
        // Import the parent
        $result = $this->parents_model->csvImport($row, $branchID, $branchSettings);
        if ($result) {
            $successCount++;
        } else {
            $err_msg .= $row['Name'] . " - Failed to import.<br>";
        }
    }
    
    // Return response
    if ($successCount > 0 && $err_msg != '') {
        echo json_encode(array('status' => 'partial', 'message' => $successCount . ' Parents added successfully.<br><br>Errors:<br>' . $err_msg));
    } elseif ($successCount > 0) {
        echo json_encode(array('status' => 'success', 'message' => $successCount . ' Parents have been successfully added.'));
    } else {
        echo json_encode(array('status' => 'fail', 'message' => 'No parents were imported.<br>' . $err_msg));
    }
}

/* sample csv downloader */
public function csv_Sampledownloader()
{
    $this->load->helper('download');
    $data = file_get_contents('uploads/multi_parent_sample.csv');
    force_download("multi_parent_sample.csv", $data);
}
}
