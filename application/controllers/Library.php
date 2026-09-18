<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Library.php
 * @copyright : Reserved Synobix Team
 */

class Library extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('library_model');
    }

    public function index()
    {
        if (is_loggedin()) {
            redirect(base_url('dashboard'));
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    /* book form validation rules */
    protected function book_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('book_title', translate('book_title'), 'trim|required');
        $this->form_validation->set_rules('purchase_date', translate('purchase_date'), 'trim|required');
        $this->form_validation->set_rules('category_id', translate('book_category'), 'trim|required');
        $this->form_validation->set_rules('publisher', translate('publisher'), 'trim|required');
        $this->form_validation->set_rules('price', translate('price'), 'trim|required|numeric');
        $this->form_validation->set_rules('total_stock', translate('total_stock'), 'trim|required');
    }

    /* category form validation rules */
    protected function category_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('name', translate('category'), 'trim|required|callback_unique_category');
    }

    // book page
    public function book()
    {
        if (!get_permission('book', 'is_view')) {
            access_denied();
        }

        if ($_POST) {
            if (!get_permission('book', 'is_add')) {
                ajax_access_denied();
            }
            $this->book_validation();
            if ($this->form_validation->run() !== false) {
                $post = $this->input->post();
                //save all route information in the database file
                $this->library_model->book_save($post);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $url = base_url('library/book');
                $array = array('status' => 'success', 'url' => $url);
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['booklist'] = $this->app_lib->getTable('book');
        $this->data['title'] = translate('books');
        $this->data['sub_page'] = 'library/book';
        $this->data['main_menu'] = 'library';
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

    /* the book information is updated here */
    public function book_edit($id = '')
    {
        if (!get_permission('book', 'is_edit')) {
            access_denied();
        }

        if ($_POST) {
            $this->book_validation();
            if ($this->form_validation->run() !== false) {
                $post = $this->input->post();
                //save all route information in the database file
                $this->library_model->book_save($post);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $url = base_url('library/book');
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $this->data['book'] = $this->app_lib->getTable('book', array('t.id' => $id), true);
        $this->data['branch_id'] = $this->application_model->get_branch_id();
        $this->data['booklist'] = $this->app_lib->getTable('book');
        $this->data['title'] = translate('books_entry');
        $this->data['sub_page'] = 'library/book_edit';
        $this->data['main_menu'] = 'library_book';
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

    public function book_delete($id = '')
    {
        if (get_permission('book', 'is_delete')) {
            $file = 'uploads/book_cover/' . get_type_name_by_id('book', $id, 'cover');
            if (file_exists($file)) {
                @unlink($file);
            }
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('book');
        }
    }

    // category information are prepared and stored in the database here
    public function category()
    {
        if (isset($_POST['save'])) {
            if (!get_permission('book_category', 'is_add')) {
                access_denied();
            }
            $this->category_validation();
            if ($this->form_validation->run() !== false) {
                //save hostel type information in the database file
                $this->library_model->category_save($this->input->post());
                set_alert('success', translate('information_has_been_saved_successfully'));
                redirect(base_url('library/category'));
            }
        }
        $this->data['categorylist'] = $this->app_lib->getTable('book_category');
        $this->data['title'] = translate('category');
        $this->data['sub_page'] = 'library/category';
        $this->data['main_menu'] = 'library';
        $this->load->view('layout/index', $this->data);
    }

    public function category_edit()
{
    if ($_POST) {
        if (!get_permission('book_category', 'is_edit')) {
            ajax_access_denied();
        }
        
        $this->category_validation();
        
        if ($this->form_validation->run() !== false) {
            $category_id = $this->input->post('category_id');
            $branch_id = $this->application_model->get_branch_id();
            
            // For superadmin, use selected branch
            if (is_superadmin_loggedin() && $this->input->post('branch_id')) {
                $branch_id = $this->input->post('branch_id');
            }
            
            $arrayData = array(
                'name' => $this->input->post('name'),
                'branch_id' => $branch_id,
            );
            
            // UPDATE existing record, NOT insert
            $this->db->where('id', $category_id);
            $this->db->update('book_category', $arrayData);
            
            set_alert('success', translate('information_has_been_updated_successfully'));
            $url = base_url('library/category');
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
        if (get_permission('book_category', 'is_delete')) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('book_category');
        }
    }

    /* book issue information are prepared and stored in the database here */
    public function book_manage($action = '', $id = '')
    {
        if (!get_permission('book_manage', 'is_view')) {
            access_denied();
        }

        if (isset($_POST['update'])) {
            if (!get_permission('book_manage', 'is_add')) {
                access_denied();
            }
            $arrayLeave = array(
                'issued_by' => get_loggedin_user_id(),
                'status' => $this->input->post('status'),
            );
            $id = $this->input->post('id');
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->update('book_issues', $arrayLeave);
            set_alert('success', translate('information_has_been_updated_successfully'));
            redirect(current_url());
        }
        if ($action == "delete") {
            $this->db->where('id', $id);
            $this->db->delete('book_issues');
        }
        $this->data['branch_id'] = $this->application_model->get_branch_id();
        $this->data['booklist'] = $this->library_model->getBookIssueList();
        $this->data['title'] = translate('book_manage');
        $this->data['sub_page'] = 'library/book_manage';
        $this->data['main_menu'] = 'library';
        $this->load->view('layout/index', $this->data);
    }

   public function bookIssued()
{
    if ($_POST) {
        if (!get_permission('book_manage', 'is_add')) {
            ajax_access_denied();
        }

        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('category_id', translate('book_category'), 'required');
        $this->form_validation->set_rules('book_id', translate('book_title'), 'trim|required|callback_validation_stock');
        $this->form_validation->set_rules('role_id', translate('role'), 'required');
        
        $roleID = $this->input->post('role_id');
        if ($roleID == 7) {
            $this->form_validation->set_rules('class_id', translate('class'), 'trim|required');
        }
        $this->form_validation->set_rules('user_id', translate('user_name'), 'required');
        $this->form_validation->set_rules('date_of_expiry', 'Date Of Expiry', 'trim|required|callback_validation_date');
        
        if ($this->form_validation->run() !== false) {
            $data = $this->input->post();
            
            // CRITICAL: Get the scanned barcode from the hidden field
            $scanned_barcode = $this->input->post('scanned_barcode');
            if (!empty($scanned_barcode)) {
                $data['barcode'] = $scanned_barcode;
            }
            
            // Also check for scanned_copy_number
            $scanned_copy = $this->input->post('scanned_copy_number');
            if (!empty($scanned_copy) && empty($data['barcode'])) {
                $data['barcode'] = $scanned_copy;
            }
            
            log_message('debug', 'BookIssued - Barcode received: ' . ($data['barcode'] ?? 'NULL'));
            
            // Call model with barcode
            $result = $this->library_model->issued_save($data);
            
            if ($result) {
                set_alert('success', translate('information_has_been_saved_successfully'));
                $url = base_url('library/book_manage');
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
            } else {
                $error_message = $this->session->flashdata('error');
                $array = array('status' => 'fail', 'error' => array('barcode' => $error_message ?: 'Failed to issue book'));
            }
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'url' => '', 'error' => $error);
        }
        echo json_encode($array);
    }
}
    public function issued_book_delete($id)
    {
        if (get_permission('book_manage', 'is_delete')) {
            $status = get_type_name_by_id('book_issues', $id, 'status');
            if ($status == 2 || $status == 3) {
                if (!is_superadmin_loggedin()) {
                    $this->db->where('branch_id', get_loggedin_branch_id());
                }
                $this->db->where('id', $id);
                $this->db->delete('book_issues');
            }
        }
    }

    public function request()
    {
        // check access permission
        if (!get_permission('book_request', 'is_view')) {
            access_denied();
        }
        if ($_POST) {
            if (!get_permission('book_request', 'is_add')) {
                access_denied();
            }
            $this->form_validation->set_rules('book_id', translate('book_title'), 'required|callback_validation_stock');
            $this->form_validation->set_rules('date_of_issue', translate('date_of_issue'), 'trim|required');
            $this->form_validation->set_rules('date_of_expiry', translate('date_of_expiry'), 'trim|required|callback_validation_date');
            if ($this->form_validation->run() !== false) {
                $arrayIssue = array(
                    'branch_id' => get_loggedin_branch_id(),
                    'book_id' => $this->input->post('book_id'),
                    'user_id' => get_loggedin_user_id(),
                    'role_id' => loggedin_role_id(),
                    'date_of_issue' => date("Y-m-d", strtotime($this->input->post('date_of_issue'))),
                    'date_of_expiry' => date("Y-m-d", strtotime($this->input->post('date_of_expiry'))),
                    'issued_by' => get_loggedin_user_id(),
                    'status' => 0,
                    'session_id' => get_session_id(),
                );
                $this->db->insert('book_issues', $arrayIssue);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $url = base_url('library/request');
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('library');
        $this->data['sub_page'] = 'library/request';
        $this->data['main_menu'] = 'library';
        $this->load->view('layout/index', $this->data);
    }

    public function request_delete($id)
    {
        if (get_permission('book_request', 'is_delete')) {
            $status = get_type_name_by_id('book_issues', $id, 'status');
            if ($status == 0) {
                $this->db->where('id', $id);
                $this->db->where('user_id', get_loggedin_user_id());
                $this->db->where('role_id', loggedin_role_id());
                $this->db->delete('book_issues');
            }
        }
    }

    // validation book stock
    public function validation_stock($book_id)
    {
        $query = $this->db->select('total_stock,issued_copies')->where('id', $book_id)->get('book')->row_array();
        $stock = $query['total_stock'];
        $issued = $query['issued_copies'];
        if ($stock == 0 || $issued >= $stock) {
            $this->form_validation->set_message("validation_stock", translate('the_book_is_not_available_in_stock'));
            return false;
        } else {
            return true;
        }
    }

    public function getBookApprovelDetails()
    {
        if (get_permission('book_manage', 'is_add')) {
            $this->data['book_id'] = $this->input->post('id');
            $this->load->view('library/bookDetailsModal', $this->data);
        }
    }

        /**
     * Book return processing
     */
        /**
     * Book return processing
     */
    public function bookReturn()
    {
        if ($_POST) {
            if (!get_permission('book_manage', 'is_add')) {
                ajax_access_denied();
            }
            
            $this->form_validation->set_rules('date', translate('date'), 'trim|required|callback_return_validation');
            $this->form_validation->set_rules('fine_amount', translate('fine_amount'), 'trim|numeric');
            
            if ($this->form_validation->run() !== false) {
                $id = $this->input->post('issue_id');
                $type = $this->input->post('type');
                $date = strtotime($this->input->post('date'));
                $fine_amount = $this->input->post('fine_amount');
                $condition = $this->input->post('condition');
                $branch_id = $this->application_model->get_branch_id();
                
                // Get the issue record with branch check
                $issue = $this->db->select('bi.*, b.branch_id as book_branch_id, b.issued_copies as current_issued_copies')
                                 ->from('book_issues bi')
                                 ->join('book b', 'b.id = bi.book_id')
                                 ->where('bi.id', $id)
                                 ->get()
                                 ->row();
                
                if (!$issue) {
                    $array = array('status' => 'fail', 'message' => 'Issue record not found');
                    echo json_encode($array);
                    return;
                }
                
                // Branch isolation check
                if (!is_superadmin_loggedin() && $issue->branch_id != $branch_id) {
                    $array = array('status' => 'fail', 'message' => 'Access denied: Branch mismatch');
                    echo json_encode($array);
                    return;
                }
                
                // Check if already returned
                if ($issue->status == 3) {
                    $array = array('status' => 'fail', 'message' => 'Book already returned');
                    echo json_encode($array);
                    return;
                }
                
               if ($type == '1') { // Return
                // Update copy status back to available
                if ($issue->copy_id) {
                    $this->db->where('id', $issue->copy_id);
                    $this->db->update('book_copies', array('status' => 'available'));
                }
                    // ========== ADD VALIDATION ==========
                    // Prevent negative issued_copies
                    if ($issue->current_issued_copies <= 0) {
                        $array = array('status' => 'fail', 'message' => 'Cannot return: Issued copies count is already zero. Please check inventory.');
                        echo json_encode($array);
                        return;
                    }
                    // ========== END VALIDATION ==========
                    
                    // Update book issued copies
                    $this->db->set('issued_copies', 'issued_copies-1', false);
                    $this->db->set('available_copies', 'available_copies+1', false);
                    $this->db->where('id', $issue->book_id);
                    $this->db->update('book');
                    
                    // Update issue record
                    $update_data = array(
                        'return_by' => get_loggedin_user_id(),
                        'status' => 3,
                        'fine_amount' => $fine_amount,
                        'return_date' => date("Y-m-d", $date),
                        'actual_return_date' => date("Y-m-d", $date)
                    );
                    
                    // Update copy condition if copy_id exists
                    if ($issue->copy_id && $condition) {
                        $this->db->where('id', $issue->copy_id);
                        $this->db->update('book_copies', array('condition' => $condition, 'status' => 'available'));
                    }
                    
                } elseif ($type == '2') { // Renewal
                    $update_data = array(
                        'fine_amount' => $fine_amount,
                        'date_of_expiry' => date("Y-m-d", $date),
                        'updated_at' => date('Y-m-d H:i:s')
                    );
                } else {
                    $array = array('status' => 'fail', 'message' => 'Invalid operation type');
                    echo json_encode($array);
                    return;
                }
                
                // Apply branch isolation for update
                if (!is_superadmin_loggedin()) {
                    $this->db->where('branch_id', $branch_id);
                }
                $this->db->where('id', $id);
                $result = $this->db->update('book_issues', $update_data);
                
                if ($result) {
                    // Log the action
                    $this->library_model->log_audit('book_' . ($type == '1' ? 'returned' : 'renewed'), 'book_issues', $id, null, $update_data);
                    
                    set_alert('success', translate('information_has_been_saved_successfully'));
                    $url = base_url('library/book_manage');
                    $array = array('status' => 'success', 'url' => $url, 'message' => 'Book ' . ($type == '1' ? 'returned' : 'renewed') . ' successfully');
                } else {
                    $array = array('status' => 'fail', 'message' => 'Failed to update record');
                }
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    // validation date
    public function validation_date($date)
    {
        if ($date) {
            $date = strtotime($date);
            $today = strtotime(date('Y-m-d'));
            if ($today >= $date) {
                $this->form_validation->set_message("validation_date", translate('today_or_the_previous_day_can_not_be_issued'));
                return false;
            } else {
                return true;
            }
        }
    }

    public function return_validation($date)
    {
        $date = strtotime($date);
        $id = $this->input->post('issue_id');
        $get = $this->db->select('date_of_issue,date_of_expiry')->get_where('book_issues', array('id' => $id))->row_array();
        if (strtotime($get['date_of_issue']) >= $date) {
            $this->form_validation->set_message("return_validation", translate('invalid_return_date_entered'));
            return false;
        } else {
            return true;
        }
    }

    /* book category exists validation */
    public function unique_category($name)
    {
        $category_id = $this->input->post('category_id');
        $branch_id = $this->application_model->get_branch_id();
        if (!empty($category_id)) {
            $this->db->where_not_in('id', $category_id);
        }
        $this->db->where('name', $name);
        $this->db->where('branch_id', $branch_id);
        $query = $this->db->get('book_category');
        if ($query->num_rows() > 0) {
            $this->form_validation->set_message("unique_category", translate('already_taken'));
            return false;
        } else {
            return true;
        }
    }

    /* get book list based on the category */
    public function getBooksByCategory()
    {
        $categoryID = $this->input->post('category_id');
        $html = "";
        if (!empty($categoryID)) {
            $books = $this->db->select('id,title')->get_where('book', array('category_id' => $categoryID))->result_array();
            if (count($books)) {
                $html .= '<option value = "">' . translate('select') . '</option>';
                foreach ($books as $row) {
                    $html .= '<option value="' . $row['id'] . '">' . $row['title'] . '</option>';
                }
            } else {
                $html .= '<option value="">' . translate('no_information_available') . '</option>';
            }
        } else {
            $html .= '<option value="">' . translate('select_category_first') . '</option>';
        }
        echo $html;
    }

        /**
     * Library reports page
     */
    public function reports()
    {
        if (!get_permission('book_manage', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $report_type = $this->input->get('report_type') ?: 'overdue';
        
        $this->data['report_type'] = $report_type;
        $this->data['branch_id'] = $branch_id;
        $this->data['is_superadmin'] = is_superadmin_loggedin();
        
        switch($report_type) {
            case 'overdue':
                $this->data['report_data'] = $this->library_model->get_overdue_books($branch_id);
                break;
            case 'popular':
                $this->data['report_data'] = $this->library_model->get_popular_books($branch_id, 20);
                break;
            case 'circulation':
                $this->data['stats'] = $this->library_model->get_circulation_stats($branch_id);
                break;
            default:
                $this->data['report_data'] = $this->library_model->get_overdue_books($branch_id);
        }
        
        $this->data['title'] = translate('library_reports');
        $this->data['sub_page'] = 'library/reports';
        $this->data['main_menu'] = 'library';
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * Library settings page
     */
        /**
     * Library settings page
     */
    public function settings()
    {
        if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        // For superadmin, allow branch selection from GET parameter
        if (is_superadmin_loggedin() && $this->input->get('branch_id')) {
            $branch_id = $this->input->get('branch_id');
        }
        
        // Handle form submission
        if ($_POST) {
            if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
                ajax_access_denied();
            }
            
            // For superadmin, get branch_id from POST
            if (is_superadmin_loggedin() && $this->input->post('branch_id')) {
                $branch_id = $this->input->post('branch_id');
            }
            
            $settings_data = array(
                'fine_per_day' => $this->input->post('fine_per_day'),
                'max_borrow_days' => $this->input->post('max_borrow_days'),
                'max_books_student' => $this->input->post('max_books_student'),
                'max_books_teacher' => $this->input->post('max_books_teacher'),
                'max_books_staff' => $this->input->post('max_books_staff'),
                'reservation_expiry_days' => $this->input->post('reservation_expiry_days'),
                'auto_calculate_fine' => isset($_POST['auto_calculate_fine']) ? 1 : 0,
                'sms_notification_enabled' => isset($_POST['sms_notification_enabled']) ? 1 : 0
            );
            
            // Call model method to save settings
            $result = $this->library_model->update_library_settings($branch_id, $settings_data);
            
            if ($result) {
                set_alert('success', translate('settings_updated_successfully'));
                $array = array('status' => 'success', 'url' => base_url('library/settings'));
            } else {
                set_alert('error', translate('settings_update_failed'));
                $array = array('status' => 'fail', 'message' => translate('settings_update_failed'));
            }
            
            echo json_encode($array);
            return;
        }
        
        // Get settings from model
        $this->data['settings'] = $this->library_model->get_library_settings($branch_id);
        $this->data['branch_id'] = $branch_id;
        $this->data['is_superadmin'] = is_superadmin_loggedin();
        
        // Get all branches for superadmin dropdown
        if (is_superadmin_loggedin()) {
            $this->data['all_branches'] = $this->db->select('id, name')->order_by('name')->get('branch')->result_array();
        }
        
        $this->data['title'] = translate('library_settings');
        $this->data['sub_page'] = 'library/settings';
        $this->data['main_menu'] = 'library';
        $this->load->view('layout/index', $this->data);
    }
    
    
        /**
     * Check borrowing limit via AJAX (for real-time validation)
     */
    public function check_borrowing_limit_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $user_id = $this->input->post('user_id');
        $role_id = $this->input->post('role_id');
        $branch_id = $this->input->post('branch_id');
        
        if (empty($branch_id)) {
            $branch_id = $this->application_model->get_branch_id();
        }
        
        $result = $this->library_model->check_borrowing_limit($user_id, $role_id, $branch_id);
        
        echo json_encode($result);
    }
    
    /**
     * Calculate fine via AJAX
     */
    public function calculate_fine_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $issue_id = $this->input->post('issue_id');
        $fine = $this->library_model->calculate_fine($issue_id);
        
        echo json_encode(['fine' => $fine]);
    }
    
    /**
     * Get overdue books via AJAX
     */
    public function get_overdue_books_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $branch_id = $this->input->post('branch_id');
        $overdue_books = $this->library_model->get_overdue_books($branch_id);
        
        echo json_encode(['status' => 'success', 'data' => $overdue_books]);
    }
        /**
     * Get monthly circulation data for chart (AJAX)
     */
    public function get_monthly_circulation()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $branch_id = $this->input->post('branch_id');
        
        $months = [];
        $issued_data = [];
        $returned_data = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $month_name = date('M Y', strtotime("-$i months"));
            $months[] = $month_name;
            
            $start_date = date('Y-m-01', strtotime($month));
            $end_date = date('Y-m-t', strtotime($month));
            
            $this->db->select("
                COUNT(CASE WHEN status = 1 AND date_of_issue BETWEEN '{$start_date}' AND '{$end_date}' THEN 1 END) as issued,
                COUNT(CASE WHEN status = 3 AND return_date BETWEEN '{$start_date}' AND '{$end_date}' THEN 1 END) as returned
            ");
            
            if ($branch_id && $branch_id != 'all') {
                $this->db->where('branch_id', $branch_id);
            } elseif (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            
            $result = $this->db->get('book_issues')->row();
            
            $issued_data[] = (int)($result->issued ?? 0);
            $returned_data[] = (int)($result->returned ?? 0);
        }
        
        echo json_encode([
            'months' => $months,
            'issued' => $issued_data,
            'returned' => $returned_data
        ]);
    }

         /**
     * Get book by barcode (AJAX) - Strict MVC, NO database operations
     */
    public function get_book_by_barcode()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        if (!get_permission('book_manage', 'is_view') && !get_permission('book_manage', 'is_add')) {
            echo json_encode(['status' => 'error', 'message' => translate('access_denied')]);
            return;
        }
        
        $barcode = $this->input->post('barcode');
        $branch_id = $this->application_model->get_branch_id();
        
        if (empty($barcode)) {
            echo json_encode(['status' => 'error', 'message' => translate('please_enter_barcode')]);
            return;
        }
        
        // Call model method - NO database queries in controller
        $result = $this->library_model->get_book_by_barcode($barcode, $branch_id);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'book' => $result
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => translate('book_not_found')]);
        }
    }
    
    /**
     * Generate barcode for a book copy
     */
    public function generate_barcode($copy_id)
    {
        if (!get_permission('book', 'is_add')) {
            access_denied();
        }
        
        $copy = $this->db->get_where('book_copies', ['id' => $copy_id])->row();
        
        if (!$copy) {
            set_alert('error', translate('copy_not_found'));
            redirect('library/book');
        }
        
        // Generate barcode using barcode library
        $this->load->library('barcode');
        $barcode_image = $this->barcode->generate($copy->barcode);
        
        // Output as PDF or image for printing
        header('Content-Type: image/png');
        echo $barcode_image;
        exit;
    }
    
    /**
     * Print barcode labels for multiple copies
     */
    public function print_barcodes()
    {
        if (!get_permission('book', 'is_add')) {
            access_denied();
        }
        
        $copy_ids = $this->input->post('copy_ids');
        if (empty($copy_ids)) {
            set_alert('error', translate('select_copies_to_print'));
            redirect('library/book');
        }
        
        $copies = $this->db->select('bc.*, b.title')
                           ->from('book_copies bc')
                           ->join('book b', 'b.id = bc.book_id')
                           ->where_in('bc.id', $copy_ids)
                           ->get()
                           ->result_array();
        
        $this->data['copies'] = $copies;
        $this->load->view('library/barcode_labels', $this->data);
    }
        /**
     * Reserve a book
     */
        /**
     * Reserve a book (Strict MVC - No DB queries)
     */
        /**
     * Reserve a book
     */
    public function reserve_book()
    {
        // Check permission
        if (!get_permission('book_request', 'is_add')) {
            ajax_access_denied();
        }
        
        // For AJAX form submission (frm-submit class uses AJAX)
        if ($this->input->is_ajax_request()) {
            $book_id = $this->input->post('book_id');
            $branch_id = $this->application_model->get_branch_id();
            $user_id = get_loggedin_user_id();
            $role_id = loggedin_role_id();
            
            // Validate
            if (empty($book_id)) {
                echo json_encode(['status' => 'fail', 'message' => translate('invalid_book')]);
                return;
            }
            
            // Call model method
            $result = $this->library_model->reserve_book($book_id, $user_id, $role_id, $branch_id);
            
            if ($result['success']) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => $result['message'],
                    'url' => base_url('library/book')
                ]);
            } else {
                echo json_encode(['status' => 'fail', 'message' => $result['message']]);
            }
            return;
        }
        
        // Fallback for non-AJAX (should not happen)
        redirect('library/book');
    }

    // to remove after 
     public function test_reserve()
    {
        echo "Test method working";
        die();
    }
    
    /**
     * View reservations page
     */
    public function reservations()
    {
        if (!get_permission('book_manage', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        $this->db->select('br.*, b.title, b.book_code, u.name as user_name')
                 ->from('book_reservations br')
                 ->join('book b', 'b.id = br.book_id')
                 ->join('staff u', 'u.id = br.user_id AND br.role_id != 7', 'left')
                 ->order_by('br.reservation_date', 'DESC');
        
        if (!is_superadmin_loggedin()) {
            $this->db->where('br.branch_id', $branch_id);
        }
        
        $this->data['reservations'] = $this->db->get()->result_array();
        $this->data['title'] = translate('book_reservations');
        $this->data['sub_page'] = 'library/reservations';
        $this->data['main_menu'] = 'library';
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * Cancel reservation
     */
    public function cancel_reservation($id)
    {
        if (!get_permission('book_manage', 'is_edit')) {
            access_denied();
        }
        
        $this->db->where('id', $id);
        $this->db->update('book_reservations', ['status' => 'cancelled']);
        
        set_alert('success', translate('reservation_cancelled'));
        redirect('library/reservations');
    }
    
    /**
     * Notify user that reserved book is available
     */
    public function notify_reservation($id)
    {
        if (!get_permission('book_manage', 'is_edit')) {
            access_denied();
        }
        
        $reservation = $this->db->get_where('book_reservations', ['id' => $id])->row();
        
        if ($reservation) {
            // Update status
            $this->db->where('id', $id);
            $this->db->update('book_reservations', ['status' => 'notified', 'notified_at' => date('Y-m-d H:i:s')]);
            
            // TODO: Send SMS/Email notification
            set_alert('success', translate('user_notified'));
        }
        
        redirect('library/reservations');
    }
        /**
     * Cron job - Send due reminders (call via cron daily)
     * Usage: wget -q -O- "https://school.studportal.co.ke/library/send_due_reminders"
     */
    public function send_due_reminders()
    {
        // Allow cron access (no session check)
        if (php_sapi_name() !== 'cli' && $this->input->get('key') != $this->config->item('cron_secret_key')) {
            show_404();
        }
        
        $sent = $this->library_model->send_due_reminders();
        echo "Sent {$sent} due reminders\n";
    }
    
    /**
     * Cron job - Send overdue alerts
     */
    public function send_overdue_alerts()
    {
        if (php_sapi_name() !== 'cli' && $this->input->get('key') != $this->config->item('cron_secret_key')) {
            show_404();
        }
        
        $sent = $this->library_model->send_overdue_alerts();
        echo "Sent {$sent} overdue alerts\n";
    }
        /**
     * Bulk import books from CSV
     */
    public function bulk_import()
    {
        if (!get_permission('book', 'is_add')) {
            access_denied();
        }
        
        if ($_POST) {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
                set_alert('error', translate('please_select_csv_file'));
                redirect('library/bulk_import');
            }
            
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $header = fgetcsv($file);
            
            $imported = 0;
            $skipped = 0;
            $branch_id = $this->application_model->get_branch_id();
            
            while (($row = fgetcsv($file)) !== false) {
                $data = array_combine($header, $row);
                
                // Check if book already exists (by ISBN or title)
                $exists = $this->db->where('isbn_no', $data['isbn_no'])
                                   ->where('branch_id', $branch_id)
                                   ->get('book')
                                   ->num_rows();
                
                if ($exists > 0) {
                    $skipped++;
                    continue;
                }
                
                $book_data = array(
                    'branch_id' => $branch_id,
                    'title' => $data['title'],
                    'isbn_no' => $data['isbn_no'] ?? '',
                    'author' => $data['author'] ?? '',
                    'category_id' => $this->get_category_id($data['category'] ?? '', $branch_id),
                    'publisher' => $data['publisher'] ?? '',
                    'edition' => $data['edition'] ?? '',
                    'purchase_date' => $data['purchase_date'] ?? date('Y-m-d'),
                    'price' => $data['price'] ?? 0,
                    'total_stock' => $data['total_copies'] ?? 1,
                    'total_copies' => $data['total_copies'] ?? 1,
                    'available_copies' => $data['total_copies'] ?? 1,
                    'description' => $data['description'] ?? ''
                );
                
                $this->db->insert('book', $book_data);
                $book_id = $this->db->insert_id();
                
                // Create copies
                for ($i = 1; $i <= ($data['total_copies'] ?? 1); $i++) {
                    $this->library_model->add_book_copy($book_id, "C-{$book_id}-{$i}", $branch_id);
                }
                
                $imported++;
            }
            
            fclose($file);
            set_alert('success', "Imported {$imported} books, skipped {$skipped} duplicates");
            redirect('library/book');
        }
        
        $this->data['title'] = translate('bulk_import_books');
        $this->data['sub_page'] = 'library/bulk_import';
        $this->data['main_menu'] = 'library';
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * Get or create category ID by name
     */
    private function get_category_id($category_name, $branch_id)
    {
        $category = $this->db->select('id')
                             ->where('name', $category_name)
                             ->where('branch_id', $branch_id)
                             ->get('book_category')
                             ->row();
        
        if ($category) {
            return $category->id;
        }
        
        $this->db->insert('book_category', ['name' => $category_name, 'branch_id' => $branch_id]);
        return $this->db->insert_id();
    }
    
    /**
     * Export books to CSV
     */
    public function export_books()
    {
        if (!get_permission('book', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        $books = $this->db->select('b.*, bc.name as category_name')
                          ->from('book b')
                          ->join('book_category bc', 'bc.id = b.category_id')
                          ->where('b.branch_id', $branch_id)
                          ->get()
                          ->result_array();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="books_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['title', 'isbn_no', 'author', 'category', 'publisher', 'edition', 'purchase_date', 'price', 'total_copies', 'description']);
        
        // Data
        foreach ($books as $book) {
            fputcsv($output, [
                $book['title'],
                $book['isbn_no'],
                $book['author'],
                $book['category_name'],
                $book['publisher'],
                $book['edition'],
                $book['purchase_date'],
                $book['price'],
                $book['total_copies'],
                $book['description']
            ]);
        }
        
        fclose($output);
        exit;
    }
        /**
     * Advanced Library Reports Page
     */
 public function advanced_reports()
{
    if (!get_permission('book_manage', 'is_view')) {
        access_denied();
    }
    
    $is_superadmin = is_superadmin_loggedin();
    $branch_id = $this->application_model->get_branch_id();
    
    // Debug logging
    error_log("=== advanced_reports() ===");
    error_log("Is Superadmin: " . ($is_superadmin ? 'Yes' : 'No'));
    error_log("Branch ID from model: " . $branch_id);
    
    // For superadmin, allow branch selection from GET
    $selected_branch = $this->input->get('branch_id');
    if ($is_superadmin && $selected_branch) {
        $branch_id = $selected_branch;
        error_log("Superadmin selected branch: " . $branch_id);
    }
    
    // For non-superadmin, ensure branch_id is set
    if (!$is_superadmin && empty($branch_id)) {
        $branch_id = get_loggedin_branch_id();
        error_log("Non-superadmin fallback branch: " . $branch_id);
    }
    
    $report_type = $this->input->get('report_type') ?: 'class_issued';
    $class_id = $this->input->get('class_id');
    $section_id = $this->input->get('section_id');
    $category_id = $this->input->get('category_id');
    $date_from = $this->input->get('date_from');
    $date_to = $this->input->get('date_to');
    
    // Get data based on report type
    switch($report_type) {
        case 'class_issued':
            $report_data = $this->library_model->get_books_issued_by_class($branch_id, $class_id, $section_id, $date_from, $date_to);
            break;
        case 'category_issued':
            $report_data = $this->library_model->get_books_issued_by_category($branch_id, $category_id, $date_from, $date_to);
            break;
        case 'section_wise':
            $report_data = $this->library_model->get_section_wise_library_stats($branch_id, $class_id, $section_id);
            break;
        case 'not_returned':
            $report_data = $this->library_model->get_books_not_returned($branch_id);
            break;
        case 'lost_damaged':
            $report_data = $this->library_model->get_lost_damaged_books($branch_id);
            break;
        case 'inventory_summary':
            $report_data = $this->library_model->get_inventory_summary($branch_id);
            break;
        case 'borrower_history':
            $report_data = $this->library_model->get_borrower_history($branch_id, $date_from, $date_to);
            break;
        default:
            $report_data = $this->library_model->get_books_issued_by_class($branch_id, $class_id, $section_id, $date_from, $date_to);
    }
    
    error_log("Report data count: " . count($report_data));
    
    // Get classes for dropdown (with branch isolation)
    $this->db->select('id, name')->from('class');
    if (!$is_superadmin) {
        $this->db->where('branch_id', $branch_id);
    }
    $this->data['classes'] = $this->db->order_by('name')->get()->result_array();
    
    // Get sections if class is selected (with branch isolation)
    if ($class_id) {
        $this->db->select('sec.id, sec.name')
                 ->from('sections_allocation sa')
                 ->join('section sec', 'sec.id = sa.section_id');
        if (!$is_superadmin) {
            $this->db->where('sec.branch_id', $branch_id);
        }
        $this->db->where('sa.class_id', $class_id);
        $this->data['sections'] = $this->db->order_by('sec.name')->get()->result_array();
    } else {
        $this->data['sections'] = array();
    }
    
    // Get categories for dropdown (with branch isolation)
    $this->db->select('id, name')->from('book_category');
    if (!$is_superadmin) {
        $this->db->where('branch_id', $branch_id);
    }
    $this->data['categories'] = $this->db->order_by('name')->get()->result_array();
    
    $this->data['report_type'] = $report_type;
    $this->data['report_data'] = $report_data;
    $this->data['branch_id'] = $branch_id;
    $this->data['class_id'] = $class_id;
    $this->data['section_id'] = $section_id;
    $this->data['category_id'] = $category_id;
    $this->data['date_from'] = $date_from;
    $this->data['date_to'] = $date_to;
    $this->data['is_superadmin'] = $is_superadmin;
    $this->data['title'] = translate('advanced_library_reports');
    $this->data['sub_page'] = 'library/advanced_reports';
    $this->data['main_menu'] = 'library';
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/daterangepicker/daterangepicker.css',
        ),
        'js' => array(
            'vendor/moment/moment.js',
            'vendor/daterangepicker/daterangepicker.js',
        ),
    );
    $this->load->view('layout/index', $this->data);
}

/**
 * AJAX: Get sections by class for dropdown
 */
public function get_sections_for_report()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $class_id = $this->input->post('class_id');
    $branch_id = $this->application_model->get_branch_id();
    
    $sections = $this->library_model->get_sections_by_class($class_id, $branch_id);
    
    echo json_encode(['status' => 'success', 'sections' => $sections]);
}
    /**
     * Student borrowing history report
     */
        /**
     * Student borrowing history report - FIXED
     */
    public function student_borrowing_history()
    {
        if (!get_permission('book_manage', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $is_superadmin = is_superadmin_loggedin();
        $search_term = $this->input->get('search_term');
        $student_id = $this->input->get('student_id');
        
        // If student_id is provided, use it as search term
        if ($student_id && is_numeric($student_id)) {
            $search_term = $student_id;
        }
        
        // Get borrowing history
        $report_data = array();
        $student_info = null;
        
        // DEBUG - Add this after getting report_data
error_log("=== STUDENT BORROWING HISTORY DEBUG ===");
error_log("Search Term: " . $search_term);
error_log("Report Data Count: " . count($report_data));
error_log("Report Data: " . print_r($report_data, true));

        if ($search_term) {
            $report_data = $this->library_model->get_student_borrowing_history($branch_id, $search_term);
            
            // Get student info for display (if records exist)
            if (!empty($report_data)) {
                $student_info = $report_data[0];
            } elseif (is_numeric($search_term)) {
                // Try to get student info directly
                $student_info = $this->db->select('id, first_name, last_name, register_no')
                                         ->where('id', $search_term)
                                         ->get('student')
                                         ->row_array();
            }
        }
        
        // Get students for dropdown (for search)
        $students = $this->library_model->get_students_for_dropdown($branch_id, $search_term);
        
        $this->data['report_data'] = $report_data;
        $this->data['student_info'] = $student_info;
        $this->data['students'] = $students;
        $this->data['search_term'] = $search_term;
        $this->data['branch_id'] = $branch_id;
        $this->data['is_superadmin'] = $is_superadmin;
        $this->data['title'] = translate('student_borrowing_history');
        $this->data['sub_page'] = 'library/student_borrowing_history';
        $this->data['main_menu'] = 'library';
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/select2/select2.css',
            ),
            'js' => array(
                'vendor/select2/select2.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * AJAX: Get students for select2 dropdown
     */
    public function ajax_get_students()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $search_term = $this->input->get('q');
        $branch_id = $this->application_model->get_branch_id();
        
        $students = $this->library_model->get_students_for_dropdown($branch_id, $search_term);
        
        $results = array();
        foreach ($students as $student) {
            $results[] = array(
                'id' => $student['id'],
                'text' => $student['full_name'] . ' (' . $student['register_no'] . ')'
            );
        }
        
        echo json_encode(['results' => $results]);
    }
        /**
     * View and print barcodes for a book
     */
    public function view_barcodes($book_id)
{
    if (!get_permission('book', 'is_view')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    $book = $this->db->select('b.*, bc.name as category_name')
                     ->from('book b')
                     ->join('book_category bc', 'bc.id = b.category_id', 'left')
                     ->where('b.id', $book_id)
                     ->get()
                     ->row();
    
    // Branch isolation check
    if (!is_superadmin_loggedin() && $book->branch_id != $branch_id) {
        access_denied();
    }
    
    // Generate book code if not exists
    if (empty($book->book_code)) {
        $book_code = 'BK' . str_pad($book_id, 6, '0', STR_PAD_LEFT);
        $this->db->where('id', $book_id);
        $this->db->update('book', array('book_code' => $book_code));
        $book->book_code = $book_code;
    }
    
    $copies = $this->db->get_where('book_copies', array('book_id' => $book_id))->result_array();
    
    $this->data['book'] = $book;
    $this->data['copies'] = $copies;
    $this->data['title'] = translate('barcodes') . ' - ' . $book->title;
    $this->data['sub_page'] = 'library/barcodes';
    $this->data['main_menu'] = 'library';
    $this->load->view('layout/index', $this->data);
}
    
 /**
 * Generate full-width, centered, clear barcode image
 */
public function generate_barcode_image($barcode)
{
    $this->load->library('barcode_generator');
    $barcode = urldecode($barcode);
    
    if (empty($barcode)) {
        show_error('Invalid barcode parameter');
    }
    
     $this->barcode_generator->output($barcode, 2.5, 70);
}
/**
 * Print thermal labels for selected copies
 * NEW METHOD - Does not affect existing functionality
 */
public function print_labels()
{
    if (!get_permission('book', 'is_view')) {
        access_denied();
    }
    
    $copy_ids = $this->input->post('copy_ids');
    $branch_id = $this->application_model->get_branch_id();
    $is_superadmin = is_superadmin_loggedin();
    
    // If no POST data, check session (for AJAX redirects)
    if (empty($copy_ids)) {
        $copy_ids = $this->session->userdata('thermal_print_ids');
        $this->session->unset_userdata('thermal_print_ids');
    }
    
    if (empty($copy_ids) || !is_array($copy_ids)) {
        set_alert('error', 'Please select at least one book copy.');
        redirect('library/book');
    }
    
    // Validate and filter copy IDs with branch isolation
    $this->db->select('bc.*, b.title, b.book_code')
             ->from('book_copies bc')
             ->join('book b', 'b.id = bc.book_id')
             ->where_in('bc.id', $copy_ids);
    
    if (!$is_superadmin) {
        $this->db->where('bc.branch_id', $branch_id);
    }
    
    $copies = $this->db->get()->result_array();
    
    if (empty($copies)) {
        set_alert('error', 'No valid copies found.');
        redirect('library/book');
    }
    
    $this->data['copies'] = $copies;
    $this->data['branch_id'] = $branch_id;
    $this->data['is_superadmin'] = $is_superadmin;
    $this->data['title'] = 'Print Thermal Labels';
    $this->load->view('library/thermal_labels', $this->data);
}

/**
 * Print single thermal label
 */
public function print_single_label($copy_id)
{
    if (!get_permission('book', 'is_view')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    $is_superadmin = is_superadmin_loggedin();
    
    $this->db->select('bc.*, b.title, b.book_code')
             ->from('book_copies bc')
             ->join('book b', 'b.id = bc.book_id')
             ->where('bc.id', $copy_id);
    
    if (!$is_superadmin) {
        $this->db->where('bc.branch_id', $branch_id);
    }
    
    $copy = $this->db->get()->row_array();
    
    if (empty($copy)) {
        show_404();
    }
    
    $this->data['copies'] = array($copy);
    $this->data['is_superadmin'] = $is_superadmin;
    $this->data['title'] = 'Print Label - ' . $copy['copy_number'];
    $this->load->view('library/thermal_labels', $this->data);
}


}
