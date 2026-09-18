<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Hostels.php
 * @copyright : Reserved Synobix Team
 */

class Hostels extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hostel_model');
    }

    /* hostel form validation rules */
    protected function hostel_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('name', translate('hostel_name'), 'trim|required');
        $this->form_validation->set_rules('category_id', translate('category'), 'required');
        $this->form_validation->set_rules('watchman_name', translate('watchman_name'), 'trim|required');
    }

    public function index()
    {
        if (!get_permission('hostel', 'is_view')) {
            access_denied();
        }
        if ($_POST) {
            if (!get_permission('hostel', 'is_add')) {
                ajax_access_denied();
            }
            $this->hostel_validation();
            if ($this->form_validation->run() !== false) {
                $post = $this->input->post();
                //save all hostel information in the database file
                $this->hostel_model->hostel_save($post);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $url = base_url('hostels');
                $array = array('status' => 'success', 'url' => $url);
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $this->data['hostellist'] = $this->app_lib->getTable('hostel');
        $this->data['branch_id'] = $this->application_model->get_branch_id();
        $this->data['title'] = translate('hostel_master');
        $this->data['sub_page'] = 'hostels/index';
        $this->data['main_menu'] = 'hostels';
        $this->load->view('layout/index', $this->data);
    }

    // the hostel information is updated here
    public function edit($id = '')
    {
        if (!get_permission('hostel', 'is_edit')) {
            access_denied();
        }
        if ($_POST) {
            $this->hostel_validation();
            if ($this->form_validation->run() !== false) {
                $post = $this->input->post();
                //save all hostel information in the database file
                $this->hostel_model->hostel_save($post);
                set_alert('success', translate('information_has_been_updated_successfully'));
                $url = base_url('hostels');
                $array = array('status' => 'success', 'url' => $url);
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $this->data['hostel'] = $this->app_lib->getTable('hostel', array('t.id' => $id), true);
        $this->data['title'] = translate('hostel_master');
        $this->data['sub_page'] = 'hostels/edit';
        $this->data['main_menu'] = 'hostels';
        $this->load->view('layout/index', $this->data);
    }

    public function delete($id = '')
    {
        if (get_permission('hostel', 'is_delete')) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('hostel');
        }
    }

    /* category form validation rules */
    protected function category_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('category_name', translate('category'), 'trim|required|callback_unique_category');
        $this->form_validation->set_rules('type', translate('category_for'), 'required');
    }

    // category information are prepared and stored in the database here
    public function category()
    {
        if (isset($_POST['save'])) {
            if (!get_permission('hostel_category', 'is_add')) {
                access_denied();
            }
            $this->category_validation();
            if ($this->form_validation->run() !== false) {
                //save hostel type information in the database file
                $this->hostel_model->category_save($this->input->post());
                set_alert('success', translate('information_has_been_saved_successfully'));
                redirect(base_url('hostels/category'));
            }
        }
        $this->data['categorylist'] = $this->app_lib->getTable('hostel_category');
        $this->data['title'] = translate('category');
        $this->data['sub_page'] = 'hostels/category';
        $this->data['main_menu'] = 'hostels';
        $this->load->view('layout/index', $this->data);
    }

    public function category_edit()
    {
        if ($_POST) {
            if (!get_permission('hostel_category', 'is_edit')) {
                ajax_access_denied();
            }
            $this->category_validation();
            if ($this->form_validation->run() !== false) {
                //update exam term information in the database file
                $this->hostel_model->category_save($this->input->post());
                set_alert('success', translate('information_has_been_updated_successfully'));
                $url = base_url('hostels/category');
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    public function category_delete($id)
    {
        if (get_permission('hostel_category', 'is_delete')) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('hostel_category');
        }
    }

    // validate here, if the check type name
    public function unique_category($name)
    {
        $categoryID = $this->input->post('category_id');
        $type = $this->input->post('type');
        $branchID = $this->application_model->get_branch_id();
        if (!empty($categoryID)) {
            $this->db->where_not_in('id', $categoryID);
        }
        $this->db->where('name', $name);
        $this->db->where('type', $type);
        $this->db->where('branch_id', $branchID);
        $query = $this->db->get('hostel_category');
        if ($query->num_rows() > 0) {
            $this->form_validation->set_message("unique_category", translate('already_taken'));
            return false;
        } else {
            return true;
        }
    }

    // room information are prepared and stored in the database here
    public function room()
    {
        if (!get_permission('hostel_room', 'is_view')) {
            ajax_access_denied();
        }

        if ($_POST) {
            if (!get_permission('hostel_room', 'is_add')) {
                ajax_access_denied();
            }
            $this->room_validation();
            if ($this->form_validation->run() !== false) {
                $post = $this->input->post();
                //save all hostel information in the database file
                $this->hostel_model->room_save($post);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $array = array('status' => 'success', 'error' => '');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }

        $this->data['roomlist'] = $this->app_lib->getTable('hostel_room');
        $this->data['branch_id'] = $this->application_model->get_branch_id();
        $this->data['title'] = translate('hostel_room');
        $this->data['sub_page'] = 'hostels/room';
        $this->data['main_menu'] = 'hostels';
        $this->load->view('layout/index', $this->data);
    }

    // the room information is updated here
    public function edit_room($id = '')
    {
        if (!get_permission('hostel_room', 'is_edit')) {
            access_denied();
        }
        if ($_POST) {
            $this->room_validation();
            if ($this->form_validation->run() !== false) {
                $post = $this->input->post();
                //save all hostel information in the database file
                $this->hostel_model->room_save($post);
                set_alert('success', translate('information_has_been_updated_successfully'));
                $url = base_url('hostels/room');
                $array = array('status' => 'success', 'url' => $url);
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $this->data['room'] = $this->app_lib->getTable('hostel_room', array('t.id' => $id), true);
        $this->data['title'] = translate('hostels_room_edit');
        $this->data['sub_page'] = 'hostels/room_edit';
        $this->data['main_menu'] = 'hostels';
        $this->load->view('layout/index', $this->data);
    }

    public function delete_room($id = '')
    {
        if (get_permission('hostel_room', 'is_delete')) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('hostel_room');
        }
    }

    // validate here, if the check room name
    public function unique_room_name($name)
    {
        $room_id = $this->input->post('room_id');
        $branchID = $this->application_model->get_branch_id();
        if (!empty($room_id)) {
            $this->db->where_not_in('id', $room_id);
        }
        $this->db->where('name', $name);
        $this->db->where('branch_id', $branchID);
        $query = $this->db->get('hostel_room');
        if ($query->num_rows() > 0) {
            $this->form_validation->set_message("unique_room_name", translate('already_taken'));
            return false;
        } else {
            return true;
        }
    }

    // student allocation report is generated here
    public function allocation_report()
    {
        if (!get_permission('hostel_allocation', 'is_view')) {
            access_denied();
        }
        
        $branchID = $this->application_model->get_branch_id();
        if ($_POST) {
            $classID = $this->input->post('class_id');
            $sectionID = $this->input->post('section_id');
            $this->data['allocationlist'] = $this->hostel_model->allocation_report($classID, $sectionID, $branchID);
        }

        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('allocation_list');
        $this->data['sub_page'] = 'hostels/allocation';
        $this->data['main_menu'] = 'hostels';
        $this->load->view('layout/index', $this->data);
    }

    public function allocation_delete($id) {
        if (get_permission('hostel_allocation', 'is_delete')) {
            $this->db->select('student_id');
            $this->db->where('id', $id);
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $student_id = $this->db->get('enroll')->row()->student_id;
            if (!empty($student_id)) {
                $arrayData = array('hostel_id' => 0, 'room_id' => 0);
                $this->db->where('id', $student_id);
                $this->db->update('student', $arrayData);
            }
        }
    }

    // get a list of branch based information
    public function getCategoryByBranch()
    {
        $type = $this->input->post('type');
        $branchID = $this->application_model->get_branch_id();
        $html = '';
        if (!empty($branchID)) {
            $result = $this->db->select('id,name')->where(array('branch_id' => $branchID, 'type' => $type))->get('hostel_category')->result_array();
            if (count($result)) {
                echo '<option value="">' . translate('select') . '</option>';
                foreach ($result as $row) {
                    $html .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                }
            } else {
                $html .= '<option value="">' . translate('no_information_available') . '</option>';
            }
        } else {
            $html .= '<option value="">' . translate('select_branch_first') . '</option>';
        }
        echo $html;
    }

    /* get a list of branch based information */
    public function getRoomByHostel()
    {
        $html = '';
        $hostelID = $this->input->post('hostel_id');
        if (!empty($hostelID)) {
            $rooms = $this->db->select('id,name,category_id')->where('hostel_id', $hostelID)->get('hostel_room')->result_array();
            if (count($rooms)) {
                echo '<option value="">' . translate('select') . '</option>';
                foreach ($rooms as $row) {
                    $html .= '<option value="' . $row['id'] . '">' . $row['name'] . ' (' . get_type_name_by_id('hostel_category', $row['category_id']) . ')' . '</option>';
                }
            } else {
                $html .= '<option value="">' . translate('no_information_available') . '</option>';
            }
        } else {
            $html .= '<option value="">' . translate('select_hostel_first') . '</option>';
        }
        echo $html;
    }

    public function getCategoryDetails()
    {
        $id = $this->input->post('id');
        $this->db->where('id', $id);
        $query = $this->db->get('hostel_category');
        $result = $query->row_array();
        echo json_encode($result);
    }

    protected function room_validation()
    {

        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('name', translate('hostel_name'), 'trim|required|callback_unique_room_name');
        $this->form_validation->set_rules('hostel_id', translate('hostel_name'), 'required');
        $this->form_validation->set_rules('category_id', translate('category'), 'trim|required');
        $this->form_validation->set_rules('number_of_beds', translate('no_of_beds'), 'trim|required|numeric');
        $this->form_validation->set_rules('bed_fee', translate('cost_per_bed'), 'trim|required|numeric');
    }
    // ============================================
// INVENTORY METHODS WITH BRANCH FILTERING
// ============================================

/**
 * Display inventory items list
 */
public function inventory_items()
{
    if (!get_permission('hostel_inventory', 'is_view') && !get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    // Handle branch selection for superadmin
    if (is_superadmin_loggedin()) {
        // Get branch from GET parameter
        $selected_branch = $this->input->get('branch_id');
        
        if (!empty($selected_branch)) {
            $this->session->set_userdata('selected_inventory_branch', $selected_branch);
            $branchID = $selected_branch;
        } else {
            // Get from session
            $branchID = $this->session->userdata('selected_inventory_branch');
            
            // If still empty, get the first branch that has rooms
            if (empty($branchID)) {
                // Try to get branch with rooms first
                $branch_with_rooms = $this->db->select('branch_id')
                    ->from('hostel_room')
                    ->group_by('branch_id')
                    ->limit(1)
                    ->get()
                    ->row();
                    
                if ($branch_with_rooms) {
                    $branchID = $branch_with_rooms->branch_id;
                } else {
                    // Fallback to first branch
                    $first_branch = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('branch')->row();
                    $branchID = $first_branch ? $first_branch->id : 13;
                }
                
                // Save to session
                $this->session->set_userdata('selected_inventory_branch', $branchID);
            }
        }
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    // DEBUG: Log the branch ID being used
    error_log("Inventory Items - Using Branch ID: " . $branchID);
    
    // Get inventory items with branch filter
    $this->db->select('i.*, c.name as category_name, r.name as room_name, 
                      CONCAT(s.first_name, " ", s.last_name) as student_name,
                      b.name as branch_name');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    $this->db->join('hostel_room r', 'r.id = i.room_id', 'left');
    $this->db->join('student s', 's.id = i.student_id', 'left');
    $this->db->join('branch b', 'b.id = i.branch_id', 'left');
    $this->db->where('i.branch_id', $branchID);
    $this->db->order_by('i.item_code', 'ASC');
    $this->data['inventory_items'] = $this->db->get()->result_array();
    
    // Get categories with branch filter
    $this->db->where('type', 'inventory');
    $this->db->where('branch_id', $branchID);
    $this->data['categories'] = $this->db->get('hostel_category')->result_array();
    
    // ============================================
    // FIX: Get rooms with branch filter - ADD DEBUG
    // ============================================
    $this->db->where('branch_id', $branchID);
    $rooms_query = $this->db->get('hostel_room');
    $this->data['rooms'] = $rooms_query->result_array();
    
    // DEBUG: Log rooms count
    error_log("Inventory Items - Rooms found for branch {$branchID}: " . $rooms_query->num_rows());
    // ============================================
    
    // Get branches for superadmin dropdown
    if (is_superadmin_loggedin()) {
        $this->data['branches'] = $this->db->select('id, name')->get('branch')->result_array();
        $this->data['selected_branch_id'] = $branchID;
    }
    
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('inventory_items');
    $this->data['sub_page'] = 'hostels/inventory_items';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}

/**
 * Save inventory item - SIMPLIFIED WORKING VERSION
 */
public function save_inventory_item()
{
    if (!get_permission('hostel_inventory', 'is_add') && !get_permission('hostel', 'is_add')) {
        ajax_access_denied();
    }
    
    // Get branch ID
    if (is_superadmin_loggedin()) {
        $branchID = $this->input->post('branch_id');
        if (empty($branchID)) {
            echo json_encode(array('status' => 'fail', 'message' => 'Branch ID required'));
            exit();
        }
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    $item_id = $this->input->post('item_id');
    
    // Validation
    $this->form_validation->set_rules('item_code', 'Item Code', 'required');
    $this->form_validation->set_rules('category_id', 'Category', 'required');
    $this->form_validation->set_rules('name', 'Item Name', 'required');
    
    if ($this->form_validation->run() == false) {
        $error = $this->form_validation->error_array();
        echo json_encode(array('status' => 'fail', 'error' => $error));
        exit();
    }
    
    // Prepare data
    $data = array(
        'item_code' => $this->input->post('item_code'),
        'category_id' => $this->input->post('category_id'),
        'name' => $this->input->post('name'),
        'description' => $this->input->post('description'),
        'manufacturer' => $this->input->post('manufacturer'),
        'model_number' => $this->input->post('model_number'),
        'serial_number' => $this->input->post('serial_number'),
        'room_id' => $this->input->post('room_id') ?: NULL,
        'condition' => $this->input->post('condition') ?: 'good',
        'purchase_date' => $this->input->post('purchase_date') ?: date('Y-m-d'),
        'purchase_cost' => $this->input->post('purchase_cost') ?: 0,
        'current_value' => $this->input->post('purchase_cost') ?: 0,
        'supplier' => $this->input->post('supplier'),
        'warranty_expiry' => $this->input->post('warranty_expiry'),
        'notes' => $this->input->post('notes'),
        'branch_id' => $branchID,
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    // Remove empty values except 0
    foreach ($data as $key => $value) {
        if ($value === '' || $value === null) {
            unset($data[$key]);
        }
    }
    
    if (!empty($item_id)) {
        // UPDATE existing item
        unset($data['created_at']); // Don't update created_at
        
        // Don't change student_id for assigned items
        $this->db->select('status, student_id');
        $this->db->where('id', $item_id);
        $current = $this->db->get('hostel_inventory_items')->row();
        
        if ($current && $current->status == 'assigned') {
            unset($data['student_id']);
        } else {
            $data['student_id'] = NULL;
        }
        
        $this->db->where('id', $item_id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', $branchID);
        }
        $result = $this->db->update('hostel_inventory_items', $data);
        
        if ($result) {
            echo json_encode(array('status' => 'success', 'message' => 'Item updated successfully'));
        } else {
            echo json_encode(array('status' => 'fail', 'message' => 'Failed to update item'));
        }
    } else {
        // INSERT new item
        $data['student_id'] = NULL;
        $data['status'] = 'available';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert('hostel_inventory_items', $data);
        $insert_id = $this->db->insert_id();
        
        if ($insert_id) {
            echo json_encode(array('status' => 'success', 'message' => 'Item saved successfully'));
        } else {
            echo json_encode(array('status' => 'fail', 'message' => 'Failed to save item'));
        }
    }
    exit();
}
/**
 * Delete inventory item
 */
public function delete_inventory_item($id)
{
    if (!get_permission('hostel_inventory', 'is_delete') && !get_permission('hostel', 'is_delete')) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    // First check if item exists and belongs to this branch
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $item = $this->db->get('hostel_inventory_items')->row();
    
    if (empty($item)) {
        set_alert('error', 'Item not found or you do not have permission to delete it.');
        redirect(base_url('hostels/inventory_items'));
    }
    
    // Check if item is assigned to a student
    if ($item->status == 'assigned') {
        set_alert('error', 'Cannot delete item that is currently assigned to a student. Please return the item first.');
        redirect(base_url('hostels/inventory_items'));
    }
    
    // Delete the item
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $this->db->delete('hostel_inventory_items');
    
    if ($this->db->affected_rows() > 0) {
        set_alert('success', translate('information_has_been_deleted_successfully'));
    } else {
        set_alert('error', 'Failed to delete item.');
    }
    
    redirect(base_url('hostels/inventory_items'));
}

/**
 * Get inventory item for editing (AJAX) with branch check
 */
public function get_inventory_item($id)
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $item = $this->db->get('hostel_inventory_items')->row_array();
    
    echo json_encode($item);
    exit();
}

/**
 * Assign item to student with branch check
 */
public function assign_inventory_item()
{
    if (!get_permission('hostel', 'is_edit')) {
        ajax_access_denied();
    }
    
    // Fix: Superadmin needs branch from POST
    if (is_superadmin_loggedin()) {
        $branchID = $this->input->post('branch_id');
        if (empty($branchID)) {
            echo json_encode(array('status' => 'fail', 'message' => 'Branch ID required'));
            exit();
        }
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    $item_id = $this->input->post('item_id');
    $student_id = $this->input->post('student_id');
    $condition = $this->input->post('condition');
    $assigned_by = get_loggedin_user_id();
    
    // Validate inputs
    if (empty($item_id)) {
        echo json_encode(array('status' => 'fail', 'message' => 'Item ID required'));
        exit();
    }
    
    if (empty($student_id)) {
        echo json_encode(array('status' => 'fail', 'message' => 'Student ID required'));
        exit();
    }
    
    // Start transaction
    $this->db->trans_start();
    
    try {
        // Verify item exists and belongs to this branch
        $this->db->where('id', $item_id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', $branchID);
        }
        $item = $this->db->get('hostel_inventory_items')->row();
        
        if (empty($item)) {
            throw new Exception('Item not found or you do not have permission');
        }
        
        // Check if item is available
        if ($item->status != 'available') {
            throw new Exception('Item is not available for assignment');
        }
        
        // Verify student exists and belongs to this branch
        $this->db->select('s.id, s.hostel_id, s.room_id');
        $this->db->from('student s');
        $this->db->join('enroll e', 'e.student_id = s.id');
        $this->db->where('s.id', $student_id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('s.branch_id', $branchID);
        }
        $student = $this->db->get()->row();
        
        if (empty($student)) {
            throw new Exception('Student not found or you do not have permission');
        }
        
        // Check if student has a hostel assigned
        if (empty($student->hostel_id) || $student->hostel_id == 0) {
            throw new Exception('Student must be assigned to a hostel first');
        }
        
        // Create assignment record
        $assign_data = array(
            'item_id' => $item_id,
            'student_id' => $student_id,
            'assigned_by' => $assigned_by,
            'assigned_date' => date('Y-m-d'),
            'condition_on_assign' => $condition,
            'branch_id' => $branchID,
            'created_at' => date('Y-m-d H:i:s')
        );
        $this->db->insert('hostel_item_assignments', $assign_data);
        
        // Update item status
        $this->db->where('id', $item_id);
        $this->db->update('hostel_inventory_items', array(
            'status' => 'assigned',
            'student_id' => $student_id,
            'room_id' => $student->room_id,
            'updated_at' => date('Y-m-d H:i:s')
        ));
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        echo json_encode(array('status' => 'success', 'message' => 'Item assigned successfully'));
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        echo json_encode(array('status' => 'fail', 'message' => $e->getMessage()));
    }
    exit();
}

public function return_inventory_item()
{
    if (!get_permission('hostel', 'is_edit')) {
        echo json_encode(array('status' => 'fail', 'message' => 'Permission denied'));
        exit();
    }
    
    // Fix: Superadmin needs branch from POST
    if (is_superadmin_loggedin()) {
        $branchID = $this->input->post('branch_id');
        if (empty($branchID)) {
            echo json_encode(array('status' => 'fail', 'message' => 'Branch ID required'));
            exit();
        }
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    $item_id = $this->input->post('item_id');
    $student_id = $this->input->post('student_id');
    $return_condition = $this->input->post('return_condition');
    $damage_notes = $this->input->post('damage_notes');
    
    // Validate inputs
    if (empty($item_id)) {
        echo json_encode(array('status' => 'fail', 'message' => 'Item ID required'));
        exit();
    }
    
    if (empty($student_id)) {
        echo json_encode(array('status' => 'fail', 'message' => 'Student ID required'));
        exit();
    }
    
    if (empty($return_condition)) {
        echo json_encode(array('status' => 'fail', 'message' => 'Return condition required'));
        exit();
    }
    
    // Start transaction
    $this->db->trans_start();
    
    try {
        // Verify item exists and belongs to this branch
        $this->db->where('id', $item_id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', $branchID);
        }
        $item = $this->db->get('hostel_inventory_items')->row();
        
        if (empty($item)) {
            throw new Exception('Item not found or you do not have permission');
        }
        
        // Verify item is actually assigned to this student
        if ($item->student_id != $student_id) {
            throw new Exception('This item is not assigned to the selected student');
        }
        
        // Update assignment record
        $this->db->where('item_id', $item_id);
        $this->db->where('student_id', $student_id);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $this->db->update('hostel_item_assignments', array(
            'returned_date' => date('Y-m-d'),
            'return_condition' => $return_condition,
            'damage_notes' => $damage_notes
        ));
        
        // CRITICAL FIX: Use NULL, not 0
        $update_item = array(
            'status' => 'available',
            'student_id' => NULL,  // WAS: 0
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        if ($return_condition == 'damaged') {
            $update_item['condition'] = 'damaged';
            $update_item['status'] = 'maintenance';
        } elseif ($return_condition == 'lost') {
            $update_item['status'] = 'lost';
        }
        
        $this->db->where('id', $item_id);
        $this->db->update('hostel_inventory_items', $update_item);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        echo json_encode(array('status' => 'success', 'message' => 'Item returned successfully'));
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        echo json_encode(array('status' => 'fail', 'message' => $e->getMessage()));
    }
    exit();
}
/**
 * Get room inventory (AJAX) with branch check
 */
public function get_room_inventory()
{
    // Allow teachers (role_id=3) to access
    $user_role = $this->session->userdata('loggedin_role_id');
    if (!get_permission('hostel', 'is_view') && $user_role != 3) {
        echo json_encode(array('status' => 'access_denied'));
        exit();
    }
    
    // Allow teachers to access this method
    $user_role = $this->session->userdata('loggedin_role_id');
    if (!get_permission('hostel', 'is_view') && $user_role != 3) {
        echo json_encode(array('status' => 'access_denied'));
        exit();
    }
    
    $branchID = $this->application_model->get_branch_id();
    $room_id = $this->input->post('room_id');
    
    if (empty($room_id)) {
        echo json_encode([]);
        exit();
    }
    
    $this->db->select('i.*, c.name as category_name, 
                      CONCAT(s.first_name, " ", s.last_name) as student_name');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    $this->db->join('student s', 's.id = i.student_id', 'left');
    $this->db->where('i.room_id', $room_id);
    
    // Branch filter for non-superadmin
    if (!is_superadmin_loggedin()) {
        $this->db->where('i.branch_id', $branchID);
    }
    
    $inventory = $this->db->get()->result_array();
    
    echo json_encode($inventory);
    exit();
}
/**
 * Get student inventory (AJAX) with branch check
 */
public function get_student_inventory()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    $student_id = $this->input->post('student_id');
    
    $this->db->select('i.*, c.name as category_name, a.assigned_date, a.condition_on_assign');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    $this->db->join('hostel_item_assignments a', 'a.item_id = i.id', 'left');
    $this->db->where('i.student_id', $student_id);
    $this->db->where('i.status', 'assigned');
    
    // BRANCH FILTER
    if (!is_superadmin_loggedin()) {
        $this->db->where('i.branch_id', $branchID);
    }
    
    $inventory = $this->db->get()->result_array();
    
    echo json_encode($inventory);
    exit();
}

// ============================================
// REPAIR METHODS WITH BRANCH FILTERING
// ============================================

/**
 * Display repairs list
 */
public function repairs()
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    // Handle branch selection for superadmin
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->input->get('branch_id');
        
        if (!empty($selected_branch)) {
            $this->session->set_userdata('selected_repairs_branch', $selected_branch);
            $branchID = $selected_branch;
        } else {
            $branchID = $this->session->userdata('selected_repairs_branch');
            if (empty($branchID)) {
                $first_branch = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('branch')->row();
                $branchID = $first_branch ? $first_branch->id : 1;
            }
        }
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    // Get repairs with branch filter - FIXED to show both item AND room
    $this->db->select('r.*, i.name as item_name, i.item_code, 
                      hr.name as room_name, h.name as hostel_name,
                      s.name as reported_by_name,
                      st.name as assigned_to_name, b.name as branch_name');
    $this->db->from('hostel_repairs r');
    $this->db->join('hostel_inventory_items i', 'i.id = r.item_id', 'left');
    $this->db->join('hostel_room hr', 'hr.id = r.room_id', 'left');
    $this->db->join('hostel h', 'h.id = hr.hostel_id', 'left');
    $this->db->join('staff s', 's.id = r.reported_by', 'left');
    $this->db->join('staff st', 'st.id = r.assigned_to', 'left');
    $this->db->join('branch b', 'b.id = r.branch_id', 'left');
    
    $this->db->where('r.branch_id', $branchID);
    
    $this->db->order_by('FIELD(r.priority, "emergency", "high", "medium", "low")');
    $this->db->order_by('r.reported_date', 'DESC');
    $repairs = $this->db->get()->result_array();
    
    // Get technicians
    $this->db->select('s.id, s.name');
    $this->db->from('staff s');
    $this->db->join('login_credential lc', 'lc.user_id = s.id');
    $this->db->where('s.branch_id', $branchID);
    $this->db->where_in('lc.role', array(2, 3, 4, 5));
    $technicians = $this->db->get()->result_array();
    
    // Get rooms
    $this->db->select('id, name');
    $this->db->where('branch_id', $branchID);
    $rooms = $this->db->get('hostel_room')->result_array();
    
    // Get items
    $this->db->select('id, name, item_code');
    $this->db->where('branch_id', $branchID);
    $this->db->where('status', 'available');
    $items = $this->db->get('hostel_inventory_items')->result_array();
    
    // Get branches for superadmin dropdown
    if (is_superadmin_loggedin()) {
        $this->data['branches'] = $this->db->select('id, name')->get('branch')->result_array();
    }
    
    $this->data['repairs'] = $repairs;
    $this->data['technicians'] = $technicians;
    $this->data['rooms'] = $rooms;
    $this->data['items'] = $items;
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('repair_requests');
    $this->data['sub_page'] = 'hostels/repairs';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
/**
 * Add repair request
 */
/**
 * Add repair request
 */
public function add_repair_request()
{
    if (!get_permission('hostel_repairs', 'is_add') && !get_permission('hostel', 'is_add')) {
        ajax_access_denied();
    }
    
    // ========== BRANCH HANDLING FOR SUPERADMIN ==========
    if (is_superadmin_loggedin()) {
        $branchID = $this->input->post('branch_id');
        if (empty($branchID)) {
            // Try to get from session as fallback
            $branchID = $this->session->userdata('selected_repair_branch');
            if (empty($branchID)) {
                echo json_encode(array('status' => 'fail', 'message' => 'Branch selection is required'));
                exit();
            }
        }
        // Store in session for consistency
        $this->session->set_userdata('selected_repair_branch', $branchID);
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    // Debug log to verify branch_id
    error_log("=== add_repair_request ===");
    error_log("Branch ID: " . $branchID);
    error_log("Is Superadmin: " . (is_superadmin_loggedin() ? 'YES' : 'NO'));
    error_log("POST data: " . print_r($this->input->post(), true));
    
    // Validation
    $this->form_validation->set_rules('issue_description', 'Issue Description', 'trim|required');
    $this->form_validation->set_rules('priority', 'Priority', 'required');
    
    if ($this->form_validation->run() !== false) {
        $repair_code = 'RPR-' . strtoupper(uniqid());
        
        $data = array(
            'repair_code' => $repair_code,
            'item_id' => $this->input->post('item_id') ?: null,
            'room_id' => $this->input->post('room_id') ?: null,
            'issue_description' => $this->input->post('issue_description'),
            'priority' => $this->input->post('priority'),
            'status' => 'pending',
            'reported_by' => get_loggedin_user_id(),
            'reported_by_type' => 'staff',
            'reported_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'branch_id' => $branchID
        );
        
        $this->db->insert('hostel_repairs', $data);
        $repair_id = $this->db->insert_id();
        
        if ($repair_id) {
            // Return success with repair_id for any follow-up actions
            $array = array(
                'status' => 'success', 
                'message' => 'Repair request submitted successfully',
                'repair_id' => $repair_id,
                'repair_code' => $repair_code
            );
        } else {
            $array = array('status' => 'fail', 'message' => 'Failed to submit repair request');
        }
    } else {
        $error = $this->form_validation->error_array();
        $array = array('status' => 'fail', 'error' => $error);
    }
    
    echo json_encode($array);
    exit();
}
/**
 * Get repair details for modal (AJAX) with branch check
 */
public function get_repair_details($id)
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    // Determine branch ID for filtering
    if (is_superadmin_loggedin()) {
        // Get branch from session (set when superadmin selects branch)
        $branchID = $this->session->userdata('selected_branch_id');
        
        // If not in session, get from GET parameter
        if (empty($branchID)) {
            $branchID = $this->input->get('branch_id');
        }
        
        // If still empty, get first branch
        if (empty($branchID)) {
            $first_branch = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('branch')->row();
            $branchID = $first_branch ? $first_branch->id : 1;
        }
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    // Get repair details with branch filter
    $this->db->select('r.*, i.name as item_name, i.item_code, 
                      hr.name as room_name, s.name as reported_by_name,
                      st.name as assigned_to_name');
    $this->db->from('hostel_repairs r');
    $this->db->join('hostel_inventory_items i', 'i.id = r.item_id', 'left');
    $this->db->join('hostel_room hr', 'hr.id = r.room_id', 'left');
    $this->db->join('staff s', 's.id = r.reported_by', 'left');
    $this->db->join('staff st', 'st.id = r.assigned_to', 'left');
    $this->db->where('r.id', $id);
    $this->db->where('r.branch_id', $branchID);
    
    $repair = $this->db->get()->row_array();
    
    if (empty($repair)) {
        echo '<div class="alert alert-danger">Repair request not found or you do not have permission to view it.</div>';
        exit();
    }
    
    $html = '<table class="table table-bordered">';
    $html .= '<tr><th width="30%">' . translate('repair_code') . '</th><td>' . $repair['repair_code'] . '</td></tr>';
    $html .= '<tr><th>' . translate('item') . '</th><td>' . ($repair['item_name'] ?? 'N/A') . ' ' . ($repair['item_code'] ? '(' . $repair['item_code'] . ')' : '') . '</td></tr>';
    $html .= '<tr><th>' . translate('room') . '</th><td>' . ($repair['room_name'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><th>' . translate('issue_description') . '</th><td>' . nl2br(htmlspecialchars($repair['issue_description'])) . '</td></tr>';
    $html .= '<tr><th>' . translate('priority') . '</th><td>' . ucfirst($repair['priority']) . '</td></tr>';
    $html .= '<tr><th>' . translate('status') . '</th><td>' . ucfirst($repair['status']) . '</td></tr>';
    $html .= '<tr><th>' . translate('reported_by') . '</th><td>' . ($repair['reported_by_name'] ?? 'N/A') . '</td></tr>';
    $html .= '<tr><th>' . translate('reported_date') . '</th><td>' . date('d M Y H:i', strtotime($repair['reported_date'])) . '</td></tr>';
    $html .= '<tr><th>' . translate('assigned_to') . '</th><td>' . ($repair['assigned_to_name'] ?? 'Not Assigned') . '</td></tr>';
    if ($repair['assigned_date']) {
        $html .= '<tr><th>' . translate('assigned_date') . '</th><td>' . date('d M Y H:i', strtotime($repair['assigned_date'])) . '</td></tr>';
    }
    if ($repair['start_date']) {
        $html .= '<tr><th>' . translate('start_date') . '</th><td>' . date('d M Y H:i', strtotime($repair['start_date'])) . '</td></tr>';
    }
    if ($repair['completion_date']) {
        $html .= '<tr><th>' . translate('completion_date') . '</th><td>' . date('d M Y H:i', strtotime($repair['completion_date'])) . '</td></tr>';
        $html .= '<tr><th>' . translate('actual_cost') . '</th><td>' . number_format($repair['actual_cost'], 2) . '</td></tr>';
        $html .= '<tr><th>' . translate('completion_notes') . '</th><td>' . nl2br(htmlspecialchars($repair['completion_notes'])) . '</td></tr>';
    }
    $html .= '</table>';
    
    echo $html;
    exit();
}

/**
 * Update repair status (AJAX) with branch check
 */
public function update_repair_status()
{
    if (!get_permission('hostel', 'is_edit')) {
        ajax_access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $repair_id = $this->input->post('repair_id');
    $status = $this->input->post('status');
    $notes = $this->input->post('notes');
    
    // Verify repair belongs to this branch
    $this->db->where('id', $repair_id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $repair = $this->db->get('hostel_repairs')->row();
    
    if (empty($repair)) {
        echo json_encode(array('status' => 'fail', 'message' => 'Repair request not found'));
        exit();
    }
    
    $update_data = array(
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    if ($status == 'in_progress' && empty($repair->start_date)) {
        $update_data['start_date'] = date('Y-m-d H:i:s');
    }
    
    if ($status == 'completed') {
        $update_data['completion_date'] = date('Y-m-d H:i:s');
        $update_data['completion_notes'] = $notes;
        
        // Update item condition if this repair is for an item
        if ($repair->item_id) {
            $this->db->where('id', $repair->item_id);
            $this->db->update('hostel_inventory_items', array(
                'condition' => 'good',
                'updated_at' => date('Y-m-d H:i:s')
            ));
        }
    }
    
    $this->db->where('id', $repair_id);
    $this->db->update('hostel_repairs', $update_data);
    
    echo json_encode(array('status' => 'success', 'message' => 'Status updated successfully'));
    exit();
}

/**
 * Assign repair technician (AJAX) with branch check
 */
public function assign_repair_technician()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $repair_id = $this->input->post('repair_id');
    $technician_id = $this->input->post('technician_id');
    
    // Verify repair belongs to this branch
    $this->db->where('id', $repair_id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $repair = $this->db->get('hostel_repairs')->row();
    
    if (empty($repair)) {
        echo json_encode(array('status' => 'fail', 'message' => 'Repair request not found'));
        exit();
    }
    
    $update_data = array(
        'assigned_to' => $technician_id ?: null,
        'assigned_date' => $technician_id ? date('Y-m-d H:i:s') : null,
        'status' => $technician_id ? 'assigned' : 'pending',
        'updated_at' => date('Y-m-d H:i:s')
    );
    
    $this->db->where('id', $repair_id);
    $this->db->update('hostel_repairs', $update_data);
    
    echo json_encode(array('status' => 'success', 'message' => 'Technician assigned successfully'));
    exit();
}
/**
 * Hostel Dashboard - Fully Working Version
 */
public function dashboard()
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    // ========== GET DASHBOARD STATISTICS ==========
    
    // 1. Total Inventory Items
    $this->db->select('COUNT(*) as total');
    $this->db->from('hostel_inventory_items');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $total_items = $this->db->get()->row()->total ?? 0;
    
    // 2. Items by status
    $this->db->select('status, COUNT(*) as count');
    $this->db->from('hostel_inventory_items');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $this->db->group_by('status');
    $items_by_status = $this->db->get()->result_array();
    
    // 3. Items by condition
    $this->db->select('condition, COUNT(*) as count');
    $this->db->from('hostel_inventory_items');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $this->db->group_by('condition');
    $items_by_condition = $this->db->get()->result_array();
    
    // 4. Total Repair Requests
    $this->db->select('COUNT(*) as total');
    $this->db->from('hostel_repairs');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $total_repairs = $this->db->get()->row()->total ?? 0;
    
    // 5. Repairs by status
    $this->db->select('status, COUNT(*) as count');
    $this->db->from('hostel_repairs');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $this->db->group_by('status');
    $repairs_by_status = $this->db->get()->result_array();
    
    // 6. Pending urgent repairs
    $this->db->select('COUNT(*) as total');
    $this->db->from('hostel_repairs');
    $this->db->where('status', 'pending');
    $this->db->where_in('priority', array('high', 'emergency'));
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $pending_urgent = $this->db->get()->row()->total ?? 0;
    
    // 7. Total Hostels
    $this->db->select('COUNT(*) as total');
    $this->db->from('hostel');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $total_hostels = $this->db->get()->row()->total ?? 0;
    
    // 8. Total Rooms
    $this->db->select('COUNT(*) as total');
    $this->db->from('hostel_room');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $total_rooms = $this->db->get()->row()->total ?? 0;
    
    // 9. Total Beds
    $this->db->select('SUM(no_beds) as total');
    $this->db->from('hostel_room');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $total_beds = $this->db->get()->row()->total ?? 0;
    
    // 10. Students in Hostel
    $this->db->select('COUNT(*) as total');
    $this->db->from('student');
    $this->db->where('hostel_id !=', 0);
    $this->db->where('hostel_id IS NOT NULL');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $students_in_hostel = $this->db->get()->row()->total ?? 0;
    
    // 11. Recent Repairs (last 5)
    $this->db->select('r.*, i.name as item_name, hr.name as room_name');
    $this->db->from('hostel_repairs r');
    $this->db->join('hostel_inventory_items i', 'i.id = r.item_id', 'left');
    $this->db->join('hostel_room hr', 'hr.id = r.room_id', 'left');
    if (!is_superadmin_loggedin()) {
        $this->db->where('r.branch_id', $branchID);
    }
    $this->db->order_by('r.reported_date', 'DESC');
    $this->db->limit(5);
    $recent_repairs = $this->db->get()->result_array();
    
    // 12. Recently Added Items (last 5)
    $this->db->select('i.*, c.name as category_name');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    if (!is_superadmin_loggedin()) {
        $this->db->where('i.branch_id', $branchID);
    }
    $this->db->order_by('i.created_at', 'DESC');
    $this->db->limit(5);
    $recent_items = $this->db->get()->result_array();
    
    // 13. Monthly Repair Costs (last 6 months) - simplified
    $monthly_costs = array();
    for ($i = 5; $i >= 0; $i--) {
        $month_name = date('M Y', strtotime("-$i months"));
        
        $this->db->select('SUM(actual_cost) as total');
        $this->db->from('hostel_repairs');
        $this->db->where('MONTH(reported_date)', date('m', strtotime("-$i months")));
        $this->db->where('YEAR(reported_date)', date('Y', strtotime("-$i months")));
        $this->db->where('status', 'completed');
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', $branchID);
        }
        $cost = $this->db->get()->row()->total ?? 0;
        $monthly_costs[] = array('month' => $month_name, 'cost' => $cost);
    }
    
    // Pass data to view
    $this->data['total_items'] = $total_items;
    $this->data['items_by_status'] = $items_by_status;
    $this->data['items_by_condition'] = $items_by_condition;
    $this->data['total_repairs'] = $total_repairs;
    $this->data['repairs_by_status'] = $repairs_by_status;
    $this->data['pending_urgent'] = $pending_urgent;
    $this->data['total_hostels'] = $total_hostels;
    $this->data['total_rooms'] = $total_rooms;
    $this->data['total_beds'] = $total_beds;
    $this->data['students_in_hostel'] = $students_in_hostel;
    $this->data['recent_repairs'] = $recent_repairs;
    $this->data['recent_items'] = $recent_items;
    $this->data['monthly_costs'] = $monthly_costs;
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('hostel_dashboard');
    $this->data['sub_page'] = 'hostels/dashboard';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
/**
 * Preventive Maintenance Schedule
 */
public function maintenance()
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    // Get maintenance schedules
    $this->db->select('ms.*, c.name as category_name');
    $this->db->from('hostel_maintenance_schedules ms');
    $this->db->join('hostel_inventory_categories c', 'c.id = ms.category_id', 'left');
    if (!is_superadmin_loggedin()) {
        $this->db->where('ms.branch_id', $branchID);
    }
    $this->db->order_by('ms.next_due_date', 'ASC');
    $schedules = $this->db->get()->result_array();
    
    // Get maintenance logs
    $this->db->select('ml.*, ms.task_name, ms.maintenance_type, s.name as performed_by_name');
    $this->db->from('hostel_maintenance_logs ml');
    $this->db->join('hostel_maintenance_schedules ms', 'ms.id = ml.schedule_id', 'left');
    $this->db->join('staff s', 's.id = ml.performed_by', 'left');
    if (!is_superadmin_loggedin()) {
        $this->db->where('ml.branch_id', $branchID);
    }
    $this->db->order_by('ml.performed_date', 'DESC');
    $this->db->limit(10);
    $logs = $this->db->get()->result_array();
    
    $this->data['schedules'] = $schedules;
    $this->data['logs'] = $logs;
    $this->data['categories'] = $this->db->get('hostel_inventory_categories')->result_array();
    $this->data['title'] = translate('preventive_maintenance');
    $this->data['sub_page'] = 'hostels/maintenance';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
/**
 * Save Maintenance Schedule
 */
public function save_maintenance_schedule()
{
    if (!get_permission('hostel', 'is_add')) {
        ajax_access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $this->form_validation->set_rules('task_name', 'Task Name', 'required');
    $this->form_validation->set_rules('category_id', 'Category', 'required');
    $this->form_validation->set_rules('maintenance_type', 'Maintenance Type', 'required');
    
    if ($this->form_validation->run() !== false) {
        // Calculate next due date based on maintenance type
        $maintenance_type = $this->input->post('maintenance_type');
        $next_due_date = date('Y-m-d');
        
        switch($maintenance_type) {
            case 'weekly':
                $next_due_date = date('Y-m-d', strtotime('+7 days'));
                break;
            case 'monthly':
                $next_due_date = date('Y-m-d', strtotime('+1 month'));
                break;
            case 'termly':
                $next_due_date = date('Y-m-d', strtotime('+3 months'));
                break;
            case 'yearly':
                $next_due_date = date('Y-m-d', strtotime('+1 year'));
                break;
        }
        
        $data = array(
            'category_id' => $this->input->post('category_id'),
            'maintenance_type' => $this->input->post('maintenance_type'),
            'task_name' => $this->input->post('task_name'),
            'task_description' => $this->input->post('task_description'),
            'estimated_minutes' => $this->input->post('estimated_minutes') ?: 30,
            'assigned_role' => $this->input->post('assigned_role') ?: 'Technician',
            'next_due_date' => $next_due_date,
            'is_active' => 1,
            'branch_id' => $branchID
        );
        
        $this->db->insert('hostel_maintenance_schedules', $data);
        
        set_alert('success', 'Maintenance schedule added successfully');
        $array = array('status' => 'success');
    } else {
        $error = $this->form_validation->error_array();
        $array = array('status' => 'fail', 'error' => $error);
    }
    
    echo json_encode($array);
    exit();
}

/**
 * Complete Maintenance Task
 */
public function complete_maintenance_task()
{
    if (!get_permission('hostel', 'is_edit')) {
        ajax_access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    $schedule_id = $this->input->post('schedule_id');
    $performed_date = $this->input->post('performed_date');
    $hours_spent = $this->input->post('hours_spent') ?: 0;
    $notes = $this->input->post('notes');
    
    // Get schedule details to calculate next due date
    $schedule = $this->db->get_where('hostel_maintenance_schedules', array('id' => $schedule_id))->row();
    
    if ($schedule) {
        // Calculate next due date based on maintenance type
        $next_due_date = date('Y-m-d', strtotime($performed_date));
        switch($schedule->maintenance_type) {
            case 'weekly':
                $next_due_date = date('Y-m-d', strtotime($performed_date . ' +7 days'));
                break;
            case 'monthly':
                $next_due_date = date('Y-m-d', strtotime($performed_date . ' +1 month'));
                break;
            case 'termly':
                $next_due_date = date('Y-m-d', strtotime($performed_date . ' +3 months'));
                break;
            case 'yearly':
                $next_due_date = date('Y-m-d', strtotime($performed_date . ' +1 year'));
                break;
        }
        
        // Update schedule
        $this->db->where('id', $schedule_id);
        $this->db->update('hostel_maintenance_schedules', array(
            'last_performed_date' => $performed_date,
            'next_due_date' => $next_due_date
        ));
        
        // Insert log
        $log_data = array(
            'schedule_id' => $schedule_id,
            'performed_by' => get_loggedin_user_id(),
            'performed_date' => $performed_date,
            'notes' => $notes,
            'hours_spent' => $hours_spent,
            'branch_id' => $branchID
        );
        $this->db->insert('hostel_maintenance_logs', $log_data);
        
        set_alert('success', 'Maintenance task completed successfully');
        $array = array('status' => 'success');
    } else {
        $array = array('status' => 'fail', 'message' => 'Schedule not found');
    }
    
    echo json_encode($array);
    exit();
}

/**
 * Delete Maintenance Schedule
 */
public function delete_maintenance_schedule($id)
{
    if (get_permission('hostel', 'is_delete')) {
        $branchID = $this->application_model->get_branch_id();
        
        $this->db->where('id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', $branchID);
        }
        $this->db->delete('hostel_maintenance_schedules');
        
        set_alert('success', 'Maintenance schedule deleted successfully');
    }
    redirect(base_url('hostels/maintenance'));
}
/**
 * Get repair parts for a repair (AJAX)
 */
public function get_repair_parts($repair_id)
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('*');
    $this->db->from('hostel_repair_parts');
    $this->db->where('repair_id', $repair_id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $parts = $this->db->get()->result_array();
    
    echo json_encode($parts);
    exit();
}

/**
 * Add repair part (AJAX)
 */
public function add_repair_part()
{
    if (!get_permission('hostel_repairs', 'is_edit')) {
        ajax_access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $this->form_validation->set_rules('repair_id', 'Repair ID', 'required');
    $this->form_validation->set_rules('part_name', 'Part Name', 'required');
    $this->form_validation->set_rules('quantity', 'Quantity', 'required|numeric');
    $this->form_validation->set_rules('unit_cost', 'Unit Cost', 'required|numeric');
    
    if ($this->form_validation->run() !== false) {
        $data = array(
            'repair_id' => $this->input->post('repair_id'),
            'part_name' => $this->input->post('part_name'),
            'quantity' => $this->input->post('quantity'),
            'unit_cost' => $this->input->post('unit_cost'),
            'supplier' => $this->input->post('supplier'),
            'purchase_date' => $this->input->post('purchase_date') ?: date('Y-m-d'),
            'branch_id' => $branchID
        );
        
        $this->db->insert('hostel_repair_parts', $data);
        
        // Update repair actual cost
        $part_total = $data['quantity'] * $data['unit_cost'];
        $this->db->set('actual_cost', 'actual_cost + ' . $part_total, FALSE);
        $this->db->where('id', $data['repair_id']);
        $this->db->update('hostel_repairs');
        
        $array = array('status' => 'success', 'message' => 'Part added successfully');
    } else {
        $error = $this->form_validation->error_array();
        $array = array('status' => 'fail', 'error' => $error);
    }
    
    echo json_encode($array);
    exit();
}

/**
 * Delete repair part (AJAX)
 */
public function delete_repair_part($id)
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    if (!get_permission('hostel_repairs', 'is_delete')) {
        ajax_access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    // Get part details to subtract from repair cost
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $part = $this->db->get('hostel_repair_parts')->row();
    
    if ($part) {
        $part_total = $part->quantity * $part->unit_cost;
        
        // Subtract from repair actual cost
        $this->db->set('actual_cost', 'actual_cost - ' . $part_total, FALSE);
        $this->db->where('id', $part->repair_id);
        $this->db->update('hostel_repairs');
        
        // Delete the part
        $this->db->where('id', $id);
        $this->db->delete('hostel_repair_parts');
        
        $array = array('status' => 'success', 'message' => 'Part deleted successfully');
    } else {
        $array = array('status' => 'fail', 'message' => 'Part not found');
    }
    
    echo json_encode($array);
    exit();
}
/**
 * Inventory Reports
 */
/**
 * Inventory Reports
 */
public function inventory_reports()
{
    if (!get_permission('hostel_inventory', 'is_view')) {
        access_denied();
    }
    
    // ========== FIX: Handle superadmin branch selection ==========
    if (is_superadmin_loggedin()) {
        $branchID = $this->input->get('branch_id');
        if (empty($branchID)) {
            $branchID = $this->session->userdata('selected_report_branch');
        }
        if (empty($branchID)) {
            $first_branch = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('branch')->row();
            $branchID = $first_branch ? $first_branch->id : 1;
        }
        $this->session->set_userdata('selected_report_branch', $branchID);
        
        // Get branches for dropdown
        $this->data['branches'] = $this->db->select('id, name')->get('branch')->result_array();
        $this->data['selected_branch_id'] = $branchID;
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    // Filter by category
    $category_id = $this->input->get('category_id');
    
    // Get inventory items with filters
    $this->db->select('i.*, c.name as category_name, r.name as room_name,
                      CONCAT(s.first_name, " ", s.last_name) as student_name,
                      b.name as branch_name');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    $this->db->join('hostel_room r', 'r.id = i.room_id', 'left');
    $this->db->join('student s', 's.id = i.student_id', 'left');
    $this->db->join('branch b', 'b.id = i.branch_id', 'left');
    
    // ========== FIX: Apply branch filter based on role ==========
    if (is_superadmin_loggedin()) {
        // Superadmin - filter by selected branch
        $this->db->where('i.branch_id', $branchID);
    } else {
        // Admin - filter by their branch
        $this->db->where('i.branch_id', $branchID);
    }
    
    if ($category_id) {
        $this->db->where('i.category_id', $category_id);
    }
    
    $this->db->order_by('i.category_id, i.item_code');
    $items = $this->db->get()->result_array();
    
    // Get categories for filter
    $this->db->where('type', 'inventory');
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $categories = $this->db->get('hostel_category')->result_array();
    
    // Calculate summary statistics
    $total_value = array_sum(array_column($items, 'current_value'));
    $total_items = count($items);
    $assigned_items = count(array_filter($items, function($item) { return $item['status'] == 'assigned'; }));
    $available_items = count(array_filter($items, function($item) { return $item['status'] == 'available'; }));
    $damaged_items = count(array_filter($items, function($item) { return $item['condition'] == 'damaged'; }));
    
    $this->data['items'] = $items;
    $this->data['categories'] = $categories;
    $this->data['selected_category'] = $category_id;
    $this->data['total_value'] = $total_value;
    $this->data['total_items'] = $total_items;
    $this->data['assigned_items'] = $assigned_items;
    $this->data['available_items'] = $available_items;
    $this->data['damaged_items'] = $damaged_items;
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('inventory_reports');
    $this->data['sub_page'] = 'hostels/inventory_reports';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
/**
 * Get student assigned hostel items (AJAX)
 */
public function get_assigned_hostel_items()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $student_id = $this->input->post('student_id');
    
    $this->db->select('i.*, c.name as category_name, a.assigned_date, a.condition_on_assign');
    $this->db->from('hostel_inventory_items i');
    $this->db->join('hostel_category c', 'c.id = i.category_id', 'left');
    $this->db->join('hostel_item_assignments a', 'a.item_id = i.id', 'left');
    $this->db->where('i.student_id', $student_id);
    $this->db->where('i.status', 'assigned');
    
    $items = $this->db->get()->result_array();
    
    echo json_encode($items);
    exit();
}

// ============================================
// ROOM INSPECTION MANAGEMENT METHODS
// ============================================
public function inspections()
{
    // Remove any echo statements that might cause header issues
    $user_role = $this->session->userdata('loggedin_role_id');
    
    if (empty($user_role)) {
        $user_id = get_loggedin_user_id();
        $login = $this->db->select('role')
                         ->from('login_credential')
                         ->where('user_id', $user_id)
                         ->get()
                         ->row();
        $user_role = $login ? $login->role : 0;
    }
    
    // Permission check
    if (!get_permission('hostel', 'is_view') && $user_role != 3) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    $staff_id = get_loggedin_user_id();
    
    // Filter by assigned rooms for teachers
    $room_filter = array();
    if ($user_role == 3) {
        $assigned_rooms = $this->hostel_model->get_staff_assigned_rooms($staff_id, true);
        if (!empty($assigned_rooms) && is_array($assigned_rooms)) {
            $room_filter = array_column($assigned_rooms, 'room_id');
        }
    }
    
    // Get filters from request
    $filters = array();
    if ($this->input->get('room_id')) {
        $filters['room_id'] = $this->input->get('room_id');
    }
    if ($this->input->get('inspection_type')) {
        $filters['inspection_type'] = $this->input->get('inspection_type');
    }
    if ($this->input->get('start_date')) {
        $filters['start_date'] = $this->input->get('start_date');
        $filters['end_date'] = $this->input->get('end_date');
    }
    
    // Get inspections
    $this->db->select('i.*, r.name as room_name, h.name as hostel_name, 
                      s.name as inspector_name, b.name as branch_name');
    $this->db->from('hostel_room_inspections i');
    $this->db->join('hostel_room r', 'r.id = i.room_id', 'left');
    $this->db->join('hostel h', 'h.id = r.hostel_id', 'left');
    $this->db->join('staff s', 's.id = i.inspector_id', 'left');
    $this->db->join('branch b', 'b.id = i.branch_id', 'left');
    
    if (!is_superadmin_loggedin()) {
        $this->db->where('i.branch_id', $branchID);
    }
    
    if (!empty($room_filter)) {
        $this->db->where_in('i.room_id', $room_filter);
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
    $query = $this->db->get();
    $inspections = $query ? $query->result_array() : array();
    
    // Get rooms for filter
    if ($user_role == 3 && !empty($room_filter)) {
        $this->db->where_in('id', $room_filter);
    } else {
        $this->db->where('branch_id', $branchID);
    }
    $rooms_query = $this->db->get('hostel_room');
    $rooms = $rooms_query ? $rooms_query->result_array() : array();
    
    // Calculate statistics
    $stats = array();
    $stats['total'] = count($inspections);
    
    $total_score = 0;
    foreach ($inspections as $insp) {
        $total_score += $insp['overall_score'];
    }
    $stats['average_score'] = $stats['total'] > 0 ? round($total_score / $stats['total'], 1) : 0;
    
    $by_type = array();
    foreach ($inspections as $insp) {
        $type = $insp['inspection_type'];
        if (!isset($by_type[$type])) {
            $by_type[$type] = array('inspection_type' => $type, 'count' => 0);
        }
        $by_type[$type]['count']++;
    }
    $stats['by_type'] = array_values($by_type);
    
    $this->data['inspections'] = $inspections;
    $this->data['rooms'] = $rooms;
    $this->data['stats'] = $stats;
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('room_inspections');
    $this->data['sub_page'] = 'hostels/inspections';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}

public function create_inspection()
{
    // Allow teachers to create inspections
    $user_role = $this->session->userdata('loggedin_role_id');
    
    if (!get_permission('hostel', 'is_edit') && $user_role != 3) {
        access_denied();
    }
    
    $staff_id = get_loggedin_user_id();
    
    // ========== BRANCH HANDLING FOR SUPERADMIN ==========
    if (is_superadmin_loggedin()) {
        // Get branch from GET parameter, POST, or session
        $selected_branch = $this->input->get('branch_id');
        if (empty($selected_branch)) {
            $selected_branch = $this->input->post('branch_id');
        }
        if (empty($selected_branch)) {
            $selected_branch = $this->session->userdata('selected_inspection_branch');
        }
        if (empty($selected_branch)) {
            // Get first branch as default
            $first_branch = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('branch')->row();
            $selected_branch = $first_branch ? $first_branch->id : 1;
        }
        $this->session->set_userdata('selected_inspection_branch', $selected_branch);
        $branchID = $selected_branch;
        
        // Debug log
        error_log("Create Inspection - Superadmin selected branch: " . $branchID);
    } else {
        $branchID = $this->application_model->get_branch_id();
        error_log("Create Inspection - Admin branch: " . $branchID);
    }
    
    // ========== GET ROOMS BASED ON ROLE ==========
    if ($user_role == 3) {
        // Teacher: only assigned rooms
        $assigned_rooms = $this->hostel_model->get_staff_assigned_rooms($staff_id, true);
        $room_ids = array_column($assigned_rooms, 'room_id');
        if (!empty($room_ids)) {
            $this->db->where_in('id', $room_ids);
        } else {
            $this->db->where('1', '0');
            $this->data['warning'] = 'No rooms have been assigned to you. Please contact administrator.';
        }
    } else {
        // Admin or Superadmin: filter by branch
        $this->db->where('branch_id', $branchID);
    }
    
    $rooms = $this->db->get('hostel_room')->result_array();
    
    // Debug log
    error_log("Create Inspection - Rooms found: " . count($rooms));
    
    // For superadmin, also get branches for dropdown
    if (is_superadmin_loggedin()) {
        $this->data['branches'] = $this->db->select('id, name')->get('branch')->result_array();
        $this->data['selected_branch_id'] = $branchID;
    }
    
    $this->data['rooms'] = $rooms;
    $this->data['templates'] = $this->hostel_model->get_inspection_templates($branchID);
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('create_inspection');
    $this->data['sub_page'] = 'hostels/inspection_form';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}

public function save_inspection()
{
    $user_role = $this->session->userdata('loggedin_role_id');
    
    // Allow teachers to save inspections
    if (!get_permission('hostel', 'is_edit') && $user_role != 3) {
        ajax_access_denied();
    }
    
    // ========== BRANCH HANDLING ==========
    if ($user_role == 3) {
        // Teacher: get branch from selected room
        $room_id = $this->input->post('room_id');
        $this->db->select('branch_id');
        $this->db->from('hostel_room');
        $this->db->where('id', $room_id);
        $room = $this->db->get()->row();
        $branchID = $room ? $room->branch_id : 0;
        if (empty($branchID)) {
            echo json_encode(array('status' => 'fail', 'message' => 'Invalid room selected'));
            exit();
        }
    } else if (is_superadmin_loggedin()) {
        $branchID = $this->input->post('branch_id');
        if (empty($branchID)) {
            echo json_encode(array('status' => 'fail', 'message' => 'Branch selection required'));
            exit();
        }
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    // Validation
    $this->form_validation->set_rules('room_id', 'Room', 'required');
    $this->form_validation->set_rules('inspection_type', 'Inspection Type', 'required');
    $this->form_validation->set_rules('inspection_date', 'Inspection Date', 'required');
    $this->form_validation->set_rules('template_id', 'Template', 'required');
    
    if ($this->form_validation->run() !== false) {
        $inspection_date = date('Y-m-d', strtotime($this->input->post('inspection_date')));
        $room_id = $this->input->post('room_id');
        $inspection_type = $this->input->post('inspection_type');
        
        // Check for duplicate
        $this->db->where('room_id', $room_id);
        $this->db->where('DATE(inspection_date)', $inspection_date);
        $this->db->where('inspection_type', $inspection_type);
        $exists = $this->db->get('hostel_room_inspections')->num_rows();
        
        if ($exists > 0) {
            echo json_encode(array('status' => 'error', 'message' => 'Inspection already recorded today'));
            exit();
        }
        
        // Generate inspection code
        $inspection_code = 'INS-' . strtoupper(uniqid());
        
        $inspection_data = array(
            'inspection_code' => $inspection_code,
            'room_id' => $room_id,
            'inspector_id' => get_loggedin_user_id(),
            'inspection_date' => $this->input->post('inspection_date'),
            'inspection_type' => $inspection_type,
            'template_id' => $this->input->post('template_id'),
            'remarks' => $this->input->post('remarks'),
            'status' => 'completed',
            'branch_id' => $branchID
        );
        
        $this->db->insert('hostel_room_inspections', $inspection_data);
        $inspection_id = $this->db->insert_id();
        
        if ($inspection_id) {
            // Save scores
            $scores = $this->input->post('scores');
            if (!empty($scores)) {
                foreach ($scores as $score) {
                    if (!isset($score['score']) || $score['score'] === '') continue;
                    
                    $score_data = array(
                        'inspection_id' => $inspection_id,
                        'template_item_id' => $score['template_item_id'],
                        'score' => $score['score'],
                        'comments' => $score['comments'] ?? null,
                        'branch_id' => $branchID
                    );
                    $this->db->insert('hostel_inspection_scores', $score_data);
                }
                
               // Calculate overall score and grade using CBC system
                $score_result = $this->hostel_model->calculate_inspection_score($scores);

                // Update inspection with score and grade
                $this->db->where('id', $inspection_id);
                $this->db->update('hostel_room_inspections', array(
                    'overall_score' => $score_result['overall_score'],
                    'grade' => $score_result['grade'],
                    'grade_name' => $score_result['grade_name'],
                    'grade_points' => $score_result['points'],
                    'grade_descriptor' => $score_result['descriptor']
                ));
            }
            
            // ========== SAVE ISSUES AND AUTO-CREATE REPAIR TICKETS ==========
            $issues = $this->input->post('issues');
            if (!empty($issues) && is_array($issues)) {
                foreach ($issues as $issue) {
                    if (!empty($issue['description'])) {
                        // Save issue to inspection_issues table
                        $issue_data = array(
                            'inspection_id' => $inspection_id,
                            'issue_description' => $issue['description'],
                            'priority' => $issue['priority'],
                            'branch_id' => $branchID,
                            'created_at' => date('Y-m-d H:i:s')
                        );
                        $this->db->insert('hostel_inspection_issues', $issue_data);
                        $issue_id = $this->db->insert_id();
                        
                        // ========== AUTO-CREATE REPAIR TICKET ==========
                        // Create repair ticket for ALL issues (or only high/emergency - choose one)
                        // Option 1: Create for all issues
                        $repair_data = array(
                            'repair_code' => 'RPR-' . strtoupper(uniqid()),
                            'issue_description' => $issue['description'],
                            'priority' => $issue['priority'],
                            'status' => 'pending',
                            'reported_by' => get_loggedin_user_id(),
                            'reported_by_type' => 'staff',
                            'reported_date' => date('Y-m-d H:i:s'),
                            'room_id' => $room_id,
                            'branch_id' => $branchID,
                            'created_at' => date('Y-m-d H:i:s')
                        );
                        $this->db->insert('hostel_repairs', $repair_data);
                        $repair_id = $this->db->insert_id();
                        
                        // Link issue to repair
                        $this->db->where('id', $issue_id);
                        $this->db->update('hostel_inspection_issues', array('repair_id' => $repair_id));
                        
                        error_log("Auto-created repair ticket #{$repair_id} for inspection issue: {$issue['description']}");
                    }
                }
            }
            
            echo json_encode(array('status' => 'success', 'message' => 'Inspection saved successfully', 'inspection_id' => $inspection_id));
        } else {
            echo json_encode(array('status' => 'fail', 'message' => 'Failed to save inspection'));
        }
    } else {
        $error = $this->form_validation->error_array();
        echo json_encode(array('status' => 'fail', 'error' => $error));
    }
    exit();
}
/**
 * View inspection details
 */
public function view_inspection($id)
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    // First, check if inspection exists
    $this->db->select('id');
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $this->application_model->get_branch_id());
    }
    $exists = $this->db->get('hostel_room_inspections')->row();
    
    if (empty($exists)) {
        // Show helpful message and redirect
        set_alert('error', 'Inspection record not found. Please create an inspection first.');
        redirect(base_url('hostels/inspections'));
    }
    
    $this->data['inspection'] = $this->hostel_model->get_inspection($id);
    
    if (empty($this->data['inspection'])) {
        set_alert('error', 'Inspection details could not be loaded.');
        redirect(base_url('hostels/inspections'));
    }
    
    $this->data['title'] = translate('inspection_details');
    $this->data['sub_page'] = 'hostels/inspection_view';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
/**
 * Delete inspection
 */
public function delete_inspection($id)
{
    if (get_permission('hostel', 'is_delete')) {
        $branchID = $this->application_model->get_branch_id();
        
        $this->db->where('id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', $branchID);
        }
        $this->db->delete('hostel_room_inspections');
        
        // Also delete related scores and issues
        $this->db->where('inspection_id', $id);
        $this->db->delete('hostel_inspection_scores');
        
        $this->db->where('inspection_id', $id);
        $this->db->delete('hostel_inspection_issues');
        
        set_alert('success', 'Inspection deleted successfully');
    }
    redirect(base_url('hostels/inspections'));
}

/**
 * Inspection reports
 */
public function inspection_reports()
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $start_date = $this->input->get('start_date') ?: date('Y-m-01');
    $end_date = $this->input->get('end_date') ?: date('Y-m-t');
    
    $filters = array(
        'start_date' => $start_date,
        'end_date' => $end_date
    );
    
    if ($this->input->get('room_id')) {
        $filters['room_id'] = $this->input->get('room_id');
    }
    
    $inspections = $this->hostel_model->get_inspections($filters);
    
    // Prepare chart data
    $chart_data = array();
    $scores_by_room = array();
    
    foreach ($inspections as $insp) {
        $room_name = $insp['room_name'];
        if (!isset($scores_by_room[$room_name])) {
            $scores_by_room[$room_name] = array();
        }
        $scores_by_room[$room_name][] = $insp['overall_score'];
    }
    
    foreach ($scores_by_room as $room => $scores) {
        $chart_data[] = array(
            'room' => $room,
            'average' => array_sum($scores) / count($scores)
        );
    }
    
    $this->data['inspections'] = $inspections;
    $this->data['chart_data'] = $chart_data;
    $this->data['start_date'] = $start_date;
    $this->data['end_date'] = $end_date;
    $this->data['rooms'] = $this->db->get_where('hostel_room', array('branch_id' => $branchID))->result_array();
    $this->data['title'] = translate('inspection_reports');
    $this->data['sub_page'] = 'hostels/inspection_reports';
    $this->data['main_menu'] = 'hostels';
    $this->data['headerelements'] = array(
        'js' => array(
            'https://cdn.jsdelivr.net/npm/chart.js',
        ),
    );
    $this->load->view('layout/index', $this->data);
}

/**
 * Get template items for AJAX
 */
public function get_template_items()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $template_id = $this->input->post('template_id');
    
    if (empty($template_id)) {
        echo json_encode([]);
        exit();
    }
    
    $this->db->select('*');
    $this->db->from('hostel_inspection_template_items');
    $this->db->where('template_id', $template_id);
    $this->db->order_by('sort_order', 'ASC');
    $items = $this->db->get()->result_array();
    
    // Debug log
    error_log("Template ID: " . $template_id . ", Items found: " . count($items));
    
    echo json_encode($items);
    exit();
}

/**
 * Get room inspection history for AJAX
 */
public function get_room_inspection_history()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $room_id = $this->input->post('room_id');
    $history = $this->hostel_model->get_room_inspection_history($room_id);
    
    echo json_encode($history);
    exit();
}
// ============================================
// INSPECTION TEMPLATE MANAGEMENT METHODS
// ============================================

/**
 * Display inspection templates list
 */
public function inspection_templates()
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    // Get all templates for this branch
    $this->db->select('t.*, COUNT(ti.id) as item_count');
    $this->db->from('hostel_inspection_templates t');
    $this->db->join('hostel_inspection_template_items ti', 'ti.template_id = t.id', 'left');
    if (!is_superadmin_loggedin()) {
        $this->db->where('t.branch_id', $branchID);
    }
    $this->db->group_by('t.id');
    $this->db->order_by('t.name', 'ASC');
    $templates = $this->db->get()->result_array();
    
    $this->data['templates'] = $templates;
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('inspection_templates');
    $this->data['sub_page'] = 'hostels/inspection_templates';
    $this->data['main_menu'] = 'hostels';
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/sweetalert/sweetalert.css',
        ),
        'js' => array(
            'vendor/sweetalert/sweetalert.min.js',
        ),
    );
    $this->load->view('layout/index', $this->data);
}

/**
 * Add/Edit template form
 */
public function template_form($id = null)
{
    if (!get_permission('hostel', 'is_edit')) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $this->data['template'] = null;
    $this->data['template_items'] = array();
    
    if (!empty($id)) {
        // Get template details
        $this->db->where('id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', $branchID);
        }
        $this->data['template'] = $this->db->get('hostel_inspection_templates')->row_array();
        
        // Get template items
        $this->db->where('template_id', $id);
        $this->db->order_by('sort_order', 'ASC');
        $this->data['template_items'] = $this->db->get('hostel_inspection_template_items')->result_array();
    }
    
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('template_form');
    $this->data['sub_page'] = 'hostels/template_form';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}

/**
 * Save template (AJAX)
 */
public function save_template()
{
    if (!get_permission('hostel', 'is_edit')) {
        ajax_access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    $template_id = $this->input->post('template_id');
    
    $this->form_validation->set_rules('name', 'Template Name', 'trim|required');
    $this->form_validation->set_rules('inspection_type', 'Inspection Type', 'required');
    
    if ($this->form_validation->run() !== false) {
        $template_data = array(
            'name' => $this->input->post('name'),
            'description' => $this->input->post('description'),
            'inspection_type' => $this->input->post('inspection_type'),
            'branch_id' => $branchID,
            'is_active' => 1
        );
        
        if (empty($template_id)) {
            // Insert new template
            $this->db->insert('hostel_inspection_templates', $template_data);
            $template_id = $this->db->insert_id();
            $message = 'Template created successfully';
        } else {
            // Update existing template
            $this->db->where('id', $template_id);
            $this->db->update('hostel_inspection_templates', $template_data);
            $message = 'Template updated successfully';
        }
        
        // Save template items
        $items = $this->input->post('items');
        if (!empty($items)) {
            // Delete existing items if updating
            if (!empty($template_id)) {
                $this->db->where('template_id', $template_id);
                $this->db->delete('hostel_inspection_template_items');
            }
            
            // Insert new items
            foreach ($items as $item) {
                if (!empty($item['item_name'])) {
                    $item_data = array(
                        'template_id' => $template_id,
                        'category' => $item['category'],
                        'item_name' => $item['item_name'],
                        'max_score' => $item['max_score'] ?: 5,
                        'weight' => $item['weight'] ?: 1,
                        'sort_order' => $item['sort_order'] ?: 0,
                        'branch_id' => $branchID
                    );
                    $this->db->insert('hostel_inspection_template_items', $item_data);
                }
            }
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
 * Delete template
 */
public function delete_template($id)
{
    if (get_permission('hostel', 'is_delete')) {
        $branchID = $this->application_model->get_branch_id();
        
        // Delete template items first
        $this->db->where('template_id', $id);
        $this->db->delete('hostel_inspection_template_items');
        
        // Delete template
        $this->db->where('id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', $branchID);
        }
        $this->db->delete('hostel_inspection_templates');
        
        set_alert('success', 'Template deleted successfully');
    }
    redirect(base_url('hostels/inspection_templates'));
}
// ============================================
// STAFF ROOM ASSIGNMENT METHODS
// ============================================

/**
 * Display staff assignments page
 */
public function staff_assignments()
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    // ========== FIX: Handle superadmin branch selection ==========
    if (is_superadmin_loggedin()) {
        $branchID = $this->input->get('branch_id');
        if (empty($branchID)) {
            $branchID = $this->session->userdata('selected_assignment_branch');
        }
        if (empty($branchID)) {
            $first_branch = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('branch')->row();
            $branchID = $first_branch ? $first_branch->id : 1;
        }
        $this->session->set_userdata('selected_assignment_branch', $branchID);
        
        // Get branches for dropdown
        $this->data['branches'] = $this->db->select('id, name')->get('branch')->result_array();
        $this->data['selected_branch_id'] = $branchID;
    } else {
        $branchID = $this->application_model->get_branch_id();
    }
    
    $this->data['assignments'] = $this->hostel_model->get_all_staff_assignments($branchID);
    $this->data['rooms'] = $this->db->get_where('hostel_room', array('branch_id' => $branchID))->result_array();
    $this->data['staff_list'] = $this->hostel_model->get_available_staff_for_assignment();
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('staff_room_assignments');
    $this->data['sub_page'] = 'hostels/staff_assignments';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
/**
 * Save staff assignment (AJAX)
 */
/**
 * Save staff assignment (Add/Edit) - AJAX
 */
public function save_staff_assignment()
{
    if (!get_permission('hostel', 'is_edit')) {
        ajax_access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    $assignment_id = $this->input->post('assignment_id');
    
    $this->form_validation->set_rules('staff_id', 'Staff', 'required');
    $this->form_validation->set_rules('room_id', 'Room', 'required');
    $this->form_validation->set_rules('role', 'Role', 'required');
    
    if ($this->form_validation->run() !== false) {
        $data = array(
            'staff_id' => $this->input->post('staff_id'),
            'room_id' => $this->input->post('room_id'),
            'role' => $this->input->post('role'),
            'is_primary' => $this->input->post('is_primary') ? 1 : 0,
            'assigned_date' => $this->input->post('assigned_date') ?: date('Y-m-d'),
            'end_date' => $this->input->post('end_date'),
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        if (empty($assignment_id)) {
            // Check if already assigned
            $this->db->where('staff_id', $data['staff_id']);
            $this->db->where('room_id', $data['room_id']);
            $this->db->where('branch_id', $branchID);
            $this->db->where('is_active', 1);
            $exists = $this->db->get('hostel_staff_assignments')->num_rows();
            
            if ($exists > 0) {
                echo json_encode(array('status' => 'fail', 'message' => 'Staff already assigned to this room'));
                exit();
            }
            
            $data['branch_id'] = $branchID;
            $data['assigned_date'] = date('Y-m-d');
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('hostel_staff_assignments', $data);
            $message = 'Staff assigned successfully';
        } else {
            // UPDATE existing assignment
            $this->db->where('id', $assignment_id);
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', $branchID);
            }
            $this->db->update('hostel_staff_assignments', $data);
            $message = 'Assignment updated successfully';
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
 * Remove staff assignment (AJAX)
 */
public function remove_staff_assignment()
{
    if (!$this->input->is_ajax_request()) {
        echo json_encode(array('status' => 'fail', 'message' => 'Invalid request'));
        exit();
    }
    
    if (!get_permission('hostel', 'is_delete')) {
        echo json_encode(array('status' => 'fail', 'message' => 'Access denied'));
        exit();
    }
    
    $id = $this->input->post('id');
    
    if (empty($id)) {
        echo json_encode(array('status' => 'fail', 'message' => 'Assignment ID required'));
        exit();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    // Soft delete - set is_active to 0 and end_date to today
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $result = $this->db->update('hostel_staff_assignments', array(
        'is_active' => 0,
        'end_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s')
    ));
    
    if ($this->db->affected_rows() > 0) {
        echo json_encode(array('status' => 'success', 'message' => 'Assignment removed successfully'));
    } else {
        echo json_encode(array('status' => 'fail', 'message' => 'Failed to remove assignment'));
    }
    exit();
}

/**
 * Get staff assignments for a room (AJAX)
 */
public function get_room_staff_assignments()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $room_id = $this->input->post('room_id');
    $assignments = $this->hostel_model->get_room_assigned_staff($room_id);
    
    echo json_encode($assignments);
    exit();
}

/**
 * Get staff assignments for current user (for filtering)
 */
public function get_my_assigned_rooms()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $staff_id = get_loggedin_user_id();
    $rooms = $this->hostel_model->get_staff_assigned_rooms($staff_id);
    
    echo json_encode($rooms);
    exit();
}
/**
 * Get single staff assignment by ID (AJAX)
 */
/**
 * Get single staff assignment by ID (AJAX)
 */
public function get_staff_assignment()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $id = $this->input->post('id');
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('*');
    $this->db->from('hostel_staff_assignments');
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branchID);
    }
    $assignment = $this->db->get()->row_array();
    
    echo json_encode($assignment);
    exit();
}
/**
 * My Assigned Rooms Report
 */
public function my_assigned_rooms()
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    $staff_id = get_loggedin_user_id();
    $user_role = $this->session->userdata('loggedin_role_id');
    
    // Only teachers/staff can access this
    if ($user_role != 3 && !is_superadmin_loggedin()) {
        access_denied();
    }
    
    // Get assigned rooms for this staff member
    $assigned_rooms = $this->hostel_model->get_staff_assigned_rooms($staff_id, true);
    
    // For each assigned room, get additional details
    foreach ($assigned_rooms as $key => $room) {
        // Get students in this room
        $this->db->select('s.id, s.first_name, s.last_name, s.register_no, s.photo');
        $this->db->from('student s');
        $this->db->where('s.room_id', $room['room_id']);
        $this->db->where('s.branch_id', $room['branch_id']);
        $assigned_rooms[$key]['students'] = $this->db->get()->result_array();
        
        // Get pending repairs for this room
        $this->db->select('COUNT(*) as count');
        $this->db->from('hostel_repairs');
        $this->db->where('room_id', $room['room_id']);
        $this->db->where('status !=', 'completed');
        $assigned_rooms[$key]['pending_repairs'] = $this->db->get()->row()->count;
        
        // Get recent inspections (last 30 days)
        $this->db->select('overall_score, grade, inspection_date');
        $this->db->from('hostel_room_inspections');
        $this->db->where('room_id', $room['room_id']);
        $this->db->order_by('inspection_date', 'DESC');
        $this->db->limit(1);
        $last_inspection = $this->db->get()->row();
        $assigned_rooms[$key]['last_inspection'] = $last_inspection;
        
        // Get total beds and occupancy
        $this->db->select('no_beds');
        $this->db->from('hostel_room');
        $this->db->where('id', $room['room_id']);
        $total_beds = $this->db->get()->row()->no_beds ?? 0;
        $assigned_rooms[$key]['total_beds'] = $total_beds;
        $assigned_rooms[$key]['occupancy'] = count($assigned_rooms[$key]['students']);
    }
    
    $this->data['assigned_rooms'] = $assigned_rooms;
    $this->data['staff_name'] = $this->db->select('name')->from('staff')->where('id', $staff_id)->get()->row()->name ?? 'Staff';
    $this->data['title'] = translate('my_assigned_rooms');
    $this->data['sub_page'] = 'hostels/my_assigned_rooms';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}
/**
 * Comprehensive Reports Dashboard
 */
public function reports()
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    $user_role = $this->session->userdata('loggedin_role_id');
    
    // Handle branch for superadmin
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->input->get('branch_id');
        if (empty($selected_branch)) {
            $selected_branch = $this->session->userdata('selected_report_branch');
            if (empty($selected_branch)) {
                $first_branch = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('branch')->row();
                $selected_branch = $first_branch ? $first_branch->id : 1;
            }
        }
        $this->session->set_userdata('selected_report_branch', $selected_branch);
        $branchID = $selected_branch;
    }
    
    // Get filter parameters
    $report_type = $this->input->get('report_type') ?: 'repairs';
    $date_range = $this->input->get('date_range');
    $status_filter = $this->input->get('status');
    $priority_filter = $this->input->get('priority');
    $condition_filter = $this->input->get('condition');
    $category_filter = $this->input->get('category_id');
    $hostel_filter = $this->input->get('hostel_id');
    $room_filter = $this->input->get('room_id');
    $period = $this->input->get('period') ?: 'all'; // daily, weekly, monthly, termly, all
    
    // Parse date range
    $start_date = null;
    $end_date = null;
    if ($date_range) {
        $dates = explode(' - ', $date_range);
        if (count($dates) == 2) {
            $start_date = date('Y-m-d', strtotime($dates[0]));
            $end_date = date('Y-m-d', strtotime($dates[1]));
        }
    }
    
    // Calculate period dates
    switch ($period) {
        case 'daily':
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
            break;
        case 'weekly':
            $start_date = date('Y-m-d', strtotime('monday this week'));
            $end_date = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'monthly':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'termly':
            // Assuming term 1: Jan-Mar, Term 2: Apr-Jul, Term 3: Sep-Nov
            $month = date('n');
            if ($month <= 3) {
                $start_date = date('Y-01-01');
                $end_date = date('Y-03-31');
            } elseif ($month <= 7) {
                $start_date = date('Y-04-01');
                $end_date = date('Y-07-31');
            } else {
                $start_date = date('Y-09-01');
                $end_date = date('Y-11-30');
            }
            break;
    }
    
    // ========== 1. REPAIRS DATA ==========
    $repairs_query = $this->db->select('r.*, i.name as item_name, i.item_code, 
                                        hr.name as room_name, h.name as hostel_name,
                                        s.name as reported_by_name, st.name as assigned_to_name,
                                        b.name as branch_name')
                        ->from('hostel_repairs r')
                        ->join('hostel_inventory_items i', 'i.id = r.item_id', 'left')
                        ->join('hostel_room hr', 'hr.id = r.room_id', 'left')
                        ->join('hostel h', 'h.id = hr.hostel_id', 'left')
                        ->join('staff s', 's.id = r.reported_by', 'left')
                        ->join('staff st', 'st.id = r.assigned_to', 'left')
                        ->join('branch b', 'b.id = r.branch_id', 'left')
                        ->where('r.branch_id', $branchID);
    
    if ($start_date && $end_date) {
        $repairs_query->where('DATE(r.reported_date) >=', $start_date);
        $repairs_query->where('DATE(r.reported_date) <=', $end_date);
    }
    if ($status_filter) {
        $repairs_query->where('r.status', $status_filter);
    }
    if ($priority_filter) {
        $repairs_query->where('r.priority', $priority_filter);
    }
    if ($hostel_filter) {
        $repairs_query->where('h.id', $hostel_filter);
    }
    if ($room_filter) {
        $repairs_query->where('hr.id', $room_filter);
    }
    
    $repairs_query->order_by('r.reported_date', 'DESC');
    $repairs_data = $repairs_query->get()->result_array();
    
    // ========== 2. INVENTORY DATA ==========
    $inventory_query = $this->db->select('i.*, c.name as category_name, 
                                          hr.name as room_name, h.name as hostel_name,
                                          CONCAT(s.first_name, " ", s.last_name) as student_name,
                                          b.name as branch_name')
                            ->from('hostel_inventory_items i')
                            ->join('hostel_category c', 'c.id = i.category_id', 'left')
                            ->join('hostel_room hr', 'hr.id = i.room_id', 'left')
                            ->join('hostel h', 'h.id = hr.hostel_id', 'left')
                            ->join('student s', 's.id = i.student_id', 'left')
                            ->join('branch b', 'b.id = i.branch_id', 'left')
                            ->where('i.branch_id', $branchID);
    
    if ($condition_filter) {
        $inventory_query->where('i.condition', $condition_filter);
    }
    if ($status_filter && $report_type == 'inventory') {
        $inventory_query->where('i.status', $status_filter);
    }
    if ($category_filter) {
        $inventory_query->where('i.category_id', $category_filter);
    }
    if ($hostel_filter) {
        $inventory_query->where('h.id', $hostel_filter);
    }
    if ($room_filter) {
        $inventory_query->where('hr.id', $room_filter);
    }
    
    $inventory_query->order_by('i.created_at', 'DESC');
    $inventory_data = $inventory_query->get()->result_array();
    
    // ========== 3. SUMMARY STATISTICS ==========
    // Repairs Summary
    $repairs_summary = array(
        'total' => count($repairs_data),
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'high_priority' => 0,
        'emergency' => 0,
        'total_cost' => 0
    );
    
    foreach ($repairs_data as $repair) {
        if ($repair['status'] == 'pending') $repairs_summary['pending']++;
        if ($repair['status'] == 'in_progress') $repairs_summary['in_progress']++;
        if ($repair['status'] == 'completed') $repairs_summary['completed']++;
        if ($repair['priority'] == 'high') $repairs_summary['high_priority']++;
        if ($repair['priority'] == 'emergency') $repairs_summary['emergency']++;
        $repairs_summary['total_cost'] += $repair['actual_cost'];
    }
    
    // Inventory Summary
    $inventory_summary = array(
        'total' => count($inventory_data),
        'available' => 0,
        'assigned' => 0,
        'maintenance' => 0,
        'scrapped' => 0,
        'lost' => 0,
        'total_value' => 0,
        'new' => 0,
        'good' => 0,
        'fair' => 0,
        'poor' => 0,
        'damaged' => 0
    );
    
    foreach ($inventory_data as $item) {
        if ($item['status'] == 'available') $inventory_summary['available']++;
        if ($item['status'] == 'assigned') $inventory_summary['assigned']++;
        if ($item['status'] == 'maintenance') $inventory_summary['maintenance']++;
        if ($item['status'] == 'scrapped') $inventory_summary['scrapped']++;
        if ($item['status'] == 'lost') $inventory_summary['lost']++;
        if ($item['condition'] == 'new') $inventory_summary['new']++;
        if ($item['condition'] == 'good') $inventory_summary['good']++;
        if ($item['condition'] == 'fair') $inventory_summary['fair']++;
        if ($item['condition'] == 'poor') $inventory_summary['poor']++;
        if ($item['condition'] == 'damaged') $inventory_summary['damaged']++;
        $inventory_summary['total_value'] += $item['current_value'];
    }
    
    // ========== 4. CHARTS DATA ==========
    // Repairs by status chart
    $repairs_by_status = array();
    $status_counts = array('pending' => 0, 'approved' => 0, 'assigned' => 0, 'in_progress' => 0, 'completed' => 0);
    foreach ($repairs_data as $repair) {
        $status = $repair['status'];
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        }
    }
    
    // Repairs by priority chart
    $priority_counts = array('low' => 0, 'medium' => 0, 'high' => 0, 'emergency' => 0);
    foreach ($repairs_data as $repair) {
        $priority = $repair['priority'];
        if (isset($priority_counts[$priority])) {
            $priority_counts[$priority]++;
        }
    }
    
    // Repairs by month (last 6 months)
    $monthly_repairs = array();
    for ($i = 5; $i >= 0; $i--) {
        $month_name = date('M Y', strtotime("-$i months"));
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));
        
        $count = 0;
        foreach ($repairs_data as $repair) {
            $repair_date = date('Y-m-d', strtotime($repair['reported_date']));
            if ($repair_date >= $month_start && $repair_date <= $month_end) {
                $count++;
            }
        }
        $monthly_repairs[] = array('month' => $month_name, 'count' => $count);
    }
    
    // Inventory by condition chart
    $condition_counts = array('new' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0, 'damaged' => 0);
    foreach ($inventory_data as $item) {
        $condition = $item['condition'];
        if (isset($condition_counts[$condition])) {
            $condition_counts[$condition]++;
        }
    }
    
    // Inventory by status chart
    $inv_status_counts = array('available' => 0, 'assigned' => 0, 'maintenance' => 0);
    foreach ($inventory_data as $item) {
        $status = $item['status'];
        if (isset($inv_status_counts[$status])) {
            $inv_status_counts[$status]++;
        }
    }
    
    // ========== 5. GET FILTER OPTIONS ==========
    // Hostels for filter
    $this->db->where('branch_id', $branchID);
    $hostels = $this->db->get('hostel')->result_array();
    
    // Rooms for filter
    $this->db->select('hr.id, hr.name, h.name as hostel_name');
    $this->db->from('hostel_room hr');
    $this->db->join('hostel h', 'h.id = hr.hostel_id');
    $this->db->where('hr.branch_id', $branchID);
    $rooms = $this->db->get()->result_array();
    
    // Categories for filter
    $this->db->where('type', 'inventory');
    $this->db->where('branch_id', $branchID);
    $categories = $this->db->get('hostel_category')->result_array();
    
    // Branches for superadmin
    if (is_superadmin_loggedin()) {
        $branches = $this->db->select('id, name')->get('branch')->result_array();
        $this->data['branches'] = $branches;
        $this->data['selected_branch_id'] = $branchID;
    }
    
    $this->data['report_type'] = $report_type;
    $this->data['repairs_data'] = $repairs_data;
    $this->data['inventory_data'] = $inventory_data;
    $this->data['repairs_summary'] = $repairs_summary;
    $this->data['inventory_summary'] = $inventory_summary;
    $this->data['repairs_by_status'] = $status_counts;
    $this->data['repairs_by_priority'] = $priority_counts;
    $this->data['monthly_repairs'] = $monthly_repairs;
    $this->data['inventory_by_condition'] = $condition_counts;
    $this->data['inventory_by_status'] = $inv_status_counts;
    $this->data['hostels'] = $hostels;
    $this->data['rooms'] = $rooms;
    $this->data['categories'] = $categories;
    $this->data['branch_id'] = $branchID;
    $this->data['start_date'] = $start_date;
    $this->data['end_date'] = $end_date;
    $this->data['period'] = $period;
    $this->data['status_filter'] = $status_filter;
    $this->data['priority_filter'] = $priority_filter;
    $this->data['condition_filter'] = $condition_filter;
    $this->data['category_filter'] = $category_filter;
    $this->data['hostel_filter'] = $hostel_filter;
    $this->data['room_filter'] = $room_filter;
    $this->data['title'] = translate('hostel_reports');
    $this->data['sub_page'] = 'hostels/reports';
    $this->data['main_menu'] = 'hostels';
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/daterangepicker/daterangepicker.css',
        ),
        'js' => array(
            'vendor/moment/moment.js',
            'vendor/daterangepicker/daterangepicker.js',
            'https://cdn.jsdelivr.net/npm/chart.js',
        ),
    );
    $this->load->view('layout/index', $this->data);
}
 /* Room Occupancy Report - Students per room per hostel
 */
public function room_occupancy()
{
    if (!get_permission('hostel', 'is_view')) {
        access_denied();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    // Handle branch selection for superadmin
    if (is_superadmin_loggedin()) {
        $selected_branch = $this->input->get('branch_id');
        if (empty($selected_branch)) {
            $selected_branch = $this->session->userdata('selected_occupancy_branch');
        }
        if (empty($selected_branch)) {
            $first_branch = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('branch')->row();
            $selected_branch = $first_branch ? $first_branch->id : 1;
        }
        $this->session->set_userdata('selected_occupancy_branch', $selected_branch);
        $branchID = $selected_branch;
        
        // Get branches for dropdown
        $this->data['branches'] = $this->db->select('id, name')->get('branch')->result_array();
        $this->data['selected_branch_id'] = $branchID;
    }
    
    // Get all hostels in this branch
    $this->db->where('branch_id', $branchID);
    $hostels = $this->db->get('hostel')->result_array();
    
    $report_data = array();
    $total_students = 0;
    $total_beds = 0;
    $total_rooms = 0;
    
    foreach ($hostels as $hostel) {
        // Get rooms in this hostel
        $this->db->where('hostel_id', $hostel['id']);
        $this->db->where('branch_id', $branchID);
        $rooms = $this->db->get('hostel_room')->result_array();
        
        $hostel_data = array(
            'hostel' => $hostel,
            'rooms' => array(),
            'totals' => array(
                'rooms' => count($rooms),
                'beds' => 0,
                'students' => 0,
                'occupancy' => 0
            )
        );
        
        foreach ($rooms as $room) {
            // Get students in this room
            $this->db->select('s.id, s.first_name, s.last_name, s.register_no, s.photo, s.gender');
            $this->db->from('student s');
            $this->db->join('enroll e', 'e.student_id = s.id');
            $this->db->where('s.room_id', $room['id']);
            $this->db->where('s.branch_id', $branchID);
            $this->db->where('e.session_id', get_session_id());
            $this->db->order_by('s.first_name', 'ASC');
            $students = $this->db->get()->result_array();
            
            $room_capacity = $room['no_beds'];
            $student_count = count($students);
            $available_beds = $room_capacity - $student_count;
            $occupancy_percent = $room_capacity > 0 ? round(($student_count / $room_capacity) * 100) : 0;
            
            $room_data = array(
                'room' => $room,
                'students' => $students,
                'capacity' => $room_capacity,
                'occupied' => $student_count,
                'available' => $available_beds,
                'occupancy_percent' => $occupancy_percent,
                'status' => $available_beds > 0 ? ($available_beds <= 2 ? 'Almost Full' : 'Available') : 'Full'
            );
            
            $hostel_data['rooms'][] = $room_data;
            $hostel_data['totals']['beds'] += $room_capacity;
            $hostel_data['totals']['students'] += $student_count;
            $total_beds += $room_capacity;
            $total_students += $student_count;
            $total_rooms++;
        }
        
        $hostel_data['totals']['occupancy'] = $hostel_data['totals']['beds'] > 0 
            ? round(($hostel_data['totals']['students'] / $hostel_data['totals']['beds']) * 100) 
            : 0;
        
        $report_data[] = $hostel_data;
    }
    
    $this->data['report_data'] = $report_data;
    $this->data['total_students'] = $total_students;
    $this->data['total_beds'] = $total_beds;
    $this->data['total_rooms'] = $total_rooms;
    $this->data['overall_occupancy'] = $total_beds > 0 ? round(($total_students / $total_beds) * 100) : 0;
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('room_occupancy_report');
    $this->data['sub_page'] = 'hostels/room_occupancy';
    $this->data['main_menu'] = 'hostels';
    $this->load->view('layout/index', $this->data);
}


/**
 * Get student details for modal (AJAX)
 */
public function get_student_details($id)
{
    if (!$this->input->is_ajax_request()) {
        echo json_encode(array('error' => 'Invalid request'));
        exit();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('s.id, s.first_name, s.last_name, s.register_no, s.gender, s.mobileno, s.email, s.photo,
                      e.class_id, e.section_id, e.roll,
                      c.name as class_name, se.name as section_name,
                      p.name as parent_name, p.mobileno as parent_mobile, p.relation,
                      lc.active,
                      hr.name as room_name, h.name as hostel_name');
    $this->db->from('student s');
    $this->db->join('enroll e', 'e.student_id = s.id AND e.session_id = ' . $this->db->escape(get_session_id()), 'left');
    $this->db->join('class c', 'c.id = e.class_id', 'left');
    $this->db->join('section se', 'se.id = e.section_id', 'left');
    $this->db->join('parent p', 'p.id = s.parent_id', 'left');
    $this->db->join('hostel_room hr', 'hr.id = s.room_id', 'left');
    $this->db->join('hostel h', 'h.id = hr.hostel_id', 'left');
    $this->db->join('login_credential lc', 'lc.user_id = s.id AND lc.role = 7', 'left');
    $this->db->where('s.id', $id);
    
    if (!is_superadmin_loggedin()) {
        $this->db->where('s.branch_id', $branchID);
    }
    
    $student = $this->db->get()->row_array();
    
    if (empty($student)) {
        echo json_encode(array('error' => 'Student not found'));
        exit();
    }
    
    // Format fullname
    $student['fullname'] = $student['first_name'] . ' ' . $student['last_name'];
    
    echo json_encode($student);
    exit();
}

/**
 * Get all students in a room (AJAX)
 */
public function get_room_students($room_id)
{
    if (!$this->input->is_ajax_request()) {
        echo json_encode(array('error' => 'Invalid request'));
        exit();
    }
    
    $branchID = $this->application_model->get_branch_id();
    
    $this->db->select('s.id, CONCAT(s.first_name, " ", s.last_name) as fullname, s.register_no, s.gender, s.mobileno,
                      e.class_id, e.section_id, e.roll,
                      c.name as class_name, se.name as section_name,
                      p.mobileno as parent_mobile, p.name as parent_name');
    $this->db->from('student s');
    $this->db->join('enroll e', 'e.student_id = s.id AND e.session_id = ' . $this->db->escape(get_session_id()), 'left');
    $this->db->join('class c', 'c.id = e.class_id', 'left');
    $this->db->join('section se', 'se.id = e.section_id', 'left');
    $this->db->join('parent p', 'p.id = s.parent_id', 'left');
    $this->db->where('s.room_id', $room_id);
    $this->db->where('s.branch_id', $branchID);
    $this->db->order_by('s.first_name', 'ASC');
    
    $students = $this->db->get()->result_array();
    
    echo json_encode($students);
    exit();
}

}
