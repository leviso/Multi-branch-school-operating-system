<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system (Saas)
 * @version : 1.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Offline_payments.php
 * @copyright : Reserved Synobix Team
 */

class Offline_payments extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('offline_payments_model');
        $this->load->model('fees_model');
        $this->load->library('bulksmsbd');
    }

    /* offline payments type form validation rules */
    protected function type_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('type_name', translate('name'), 'trim|required|callback_unique_type');
        $this->form_validation->set_rules('note', translate('note'), 'trim');
    }

    /* offline payments type control - SUPERADMIN ONLY */
    public function type()
    {
        if (!get_permission('offline_payments_type', 'is_view')) {
            access_denied();
        }
        if ($_POST) {
            if (!get_permission('offline_payments_type', 'is_add')) {
                ajax_access_denied();
            }
            $this->type_validation();
            if ($this->form_validation->run() !== false) {
                $post = $this->input->post();
                $this->offline_payments_model->typeSave($post);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $array = array('status' => 'success');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/summernote/summernote.css',
            ),
            'js' => array(
                'vendor/summernote/summernote.js',
            ),
        );
        $this->data['categorylist'] = $this->app_lib->getTable('offline_payment_types');
        $this->data['title'] = translate('offline_payments') . " " . translate('type');
        $this->data['sub_page'] = 'offline_payments/type';
        $this->data['main_menu'] = 'offline_payments';
        $this->load->view('layout/index', $this->data);
    }

    public function type_edit($id = '')
{
    if (!get_permission('offline_payments_type', 'is_edit')) {
        access_denied();
    }

    if ($_POST) {
        $this->type_validation();
        if ($this->form_validation->run() !== false) {
            $post = $this->input->post();
            $this->offline_payments_model->typeSave($post);
            set_alert('success', translate('information_has_been_updated_successfully'));
            $url = base_url('offline_payments/type');
            $array = array('status' => 'success', 'url' => $url);
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'error' => $error);
        }
        echo json_encode($array);
        exit();
    }
    
    // Ensure global config is set for views
    if (!isset($this->data['global_config'])) {
        $this->data['global_config'] = $this->global_config;
    }
    if (!isset($this->data['theme_config'])) {
        $this->data['theme_config'] = $this->theme_config;
    }
    
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/summernote/summernote.css',
        ),
        'js' => array(
            'vendor/summernote/summernote.js',
        ),
    );
    $this->data['category'] = $this->app_lib->getTable('offline_payment_types', array('t.id' => $id), true);
    $this->data['title'] = translate('offline_payments') . " " . translate('type');
    $this->data['sub_page'] = 'offline_payments/type_edit';
    $this->data['main_menu'] = 'offline_payments';
    $this->load->view('layout/index', $this->data);
}

    public function type_delete($id = '')
    {
        if (get_permission('offline_payments_type', 'is_delete')) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('offline_payment_types');
        }
    }

    public function unique_type($name)
    {
        $branchID = $this->application_model->get_branch_id();
        $typeID = $this->input->post('type_id');
        if (!empty($typeID)) {
            $this->db->where_not_in('id', $typeID);
        }
        $this->db->where(array('name' => $name, 'branch_id' => $branchID));
        $uniform_row = $this->db->get('offline_payment_types')->num_rows();
        if ($uniform_row == 0) {
            return true;
        } else {
            $this->form_validation->set_message("unique_type", translate('already_taken'));
            return false;
        }
    }

 public function payments()
{
    // DO NOT reset the entire data array - only set defaults for missing keys
    if (!isset($this->data['global_config'])) {
        $this->data['global_config'] = $this->global_config;
    }
    if (!isset($this->data['theme_config'])) {
        $this->data['theme_config'] = $this->theme_config;
    }
    if (!isset($this->data['headerelements'])) {
        $this->data['headerelements'] = array('css' => array(), 'js' => array());
    }
    
    // Check permission
    if (!get_permission('offline_payments', 'is_view')) {
        access_denied();
        return;
    }

    $branchID = $this->application_model->get_branch_id();
    $filter = array();
    
    // For non-superadmin, only show their branch
    if (!is_superadmin_loggedin()) {
        $filter['branch_id'] = $branchID;
    }
    
    if ($this->input->post('search')) {
        if (!empty($this->input->post('payments_status'))) {
            $filter['status'] = $this->input->post('payments_status');
        }
        if (!empty($this->input->post('branch_id')) && is_superadmin_loggedin()) {
            $filter['branch_id'] = $this->input->post('branch_id');
        }
    }
    
    // Get payments list from model
    $payments_list = $this->offline_payments_model->getOfflinePaymentsList($filter);
    
    $this->data['paymentslist'] = $payments_list ? $payments_list : array();
    $this->data['branch_id'] = $branchID;
    $this->data['title'] = translate('offline_payments');
    $this->data['sub_page'] = 'offline_payments/history';
    $this->data['main_menu'] = 'offline_payments';
    
    $this->load->view('layout/index', $this->data);
}

// get payments details modal - ACCESSIBLE BY ADMIN/ACCOUNTANT
public function getApprovelDetails()
{
    if (!get_permission('offline_payments', 'is_view')) {
        echo '<div class="alert alert-danger">Access Denied</div>';
        return;
    }
    
    $payment_id = $this->input->post('id');
    if (empty($payment_id)) {
        echo '<div class="alert alert-danger">Invalid payment ID</div>';
        return;
    }
    
    // Get payment details from model
    $payment = $this->offline_payments_model->getOfflinePaymentsDetails($payment_id);
    
    if (!$payment) {
        echo '<div class="alert alert-danger">Payment record not found</div>';
        return;
    }
    
    // Branch isolation check
    if (!is_superadmin_loggedin() && $payment->branch_id != get_loggedin_branch_id()) {
        echo '<div class="alert alert-danger">Access denied for this branch</div>';
        return;
    }
    
    $this->data['payments_id'] = $payment_id;
    $this->load->view('offline_payments/approvel_modalView', $this->data);
}

    public function download($id = '', $file = '')
    {
        if (!empty($id) && !empty($file)) {
            // Check permission first
            if (!get_permission('offline_payments', 'is_view')) {
                access_denied();
            }
            
            $this->db->select('orig_file_name,enc_file_name, branch_id');
            $this->db->where('id', $id);
            $payments = $this->db->get('offline_fees_payments')->row();
            
            if (!$payments) {
                access_denied();
            }
            
            // Branch isolation check for non-superadmin
            if (!is_superadmin_loggedin() && $payments->branch_id != get_loggedin_branch_id()) {
                access_denied();
            }
            
            if ($file != $payments->enc_file_name) {
                access_denied();
            }
            $this->load->helper('download');
            $fileData = file_get_contents('./uploads/attachments/offline_payments/' . $payments->enc_file_name);
            force_download($payments->orig_file_name, $fileData);
        }
    }
public function approved()
{
    // Only Accountant (role_id=4) or Superadmin can approve/reject
    $logged_in_role = loggedin_role_id();
    
    if ($logged_in_role != 4 && !is_superadmin_loggedin()) {
        set_alert('error', 'Only Accountant can approve or reject payments');
        redirect(base_url('offline_payments/payments'));
    }

    $id = $this->input->post('id');
    
    if (empty($id)) {
        set_alert('error', 'Invalid payment ID');
        redirect(base_url('offline_payments/payments'));
    }
    
    $payment = $this->db->where('id', $id)->get('offline_fees_payments')->row();
    
    if (!$payment) {
        set_alert('error', 'Payment record not found');
        redirect(base_url('offline_payments/payments'));
    }
    
    if (!is_superadmin_loggedin() && $payment->branch_id != get_loggedin_branch_id()) {
        access_denied();
    }
    
    if ($payment->status != 1) {
        set_alert('error', 'This payment has already been processed');
        redirect(base_url('offline_payments/payments'));
    }
    
    $status = $this->input->post('status');
    
    if (!in_array($status, array(2, 3))) {
        set_alert('error', 'Invalid status selected');
        redirect(base_url('offline_payments/payments'));
    }
    
    $update_data = array(
        'approved_by' => get_loggedin_user_id(),
        'status' => $status,
        'comments' => $this->input->post('comments'),
        'approve_date' => date('Y-m-d H:i:s'),
    );
    
    $this->db->where('id', $id);
    $update_result = $this->db->update('offline_fees_payments', $update_data);
    
    if (!$update_result) {
        set_alert('error', 'Failed to update payment status');
        redirect(base_url('offline_payments/payments'));
    }
    
    // Set main status message
    if ($status == 2) {
        if (method_exists($this->offline_payments_model, 'update')) {
            $this->offline_payments_model->update($id);
        }
        set_alert('success', 'Payment has been approved successfully');
    } elseif ($status == 3) {
        set_alert('success', 'Payment has been rejected');
    }
    
    // Send SMS and get status
    $sms_result = $this->_send_sms($id, $status);
    
    // Add SMS status alert
    if ($sms_result['success']) {
        set_alert('info', $sms_result['message']);
    } else {
        set_alert('warning', 'Payment processed but ' . $sms_result['message']);
    }
    
    redirect(base_url('offline_payments/payments'));
}

private function _send_sms($payment_id, $status)
{
    try {
        $sql = "SELECT op.*, s.mobileno as student_mobile, p.mobileno as parent_mobile,
                       CONCAT(s.first_name, ' ', s.last_name) as student_name
                FROM offline_fees_payments op
                LEFT JOIN enroll e ON e.id = op.student_enroll_id
                LEFT JOIN student s ON s.id = e.student_id
                LEFT JOIN parent p ON p.id = s.parent_id
                WHERE op.id = ?";
        
        $payment = $this->db->query($sql, array($payment_id))->row();
        
        if (!$payment) {
            return array('success' => false, 'message' => 'Payment record not found');
        }
        
        $mobile = !empty($payment->parent_mobile) ? $payment->parent_mobile : $payment->student_mobile;
        
        if (empty($mobile)) {
            return array('success' => false, 'message' => 'No mobile number found for this student');
        }
        
        // Format mobile
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($mobile) == 10 && substr($mobile, 0, 1) == '0') {
            $mobile = '254' . substr($mobile, 1);
        } elseif (strlen($mobile) == 9 && substr($mobile, 0, 1) == '7') {
            $mobile = '254' . $mobile;
        }
        
        $amount = number_format($payment->amount, 2);
        $student_name = $payment->student_name;
        
        if ($status == 2) {
            $message = "Dear Parent, payment of KES {$amount} for {$student_name} has been APPROVED.";
        } else {
            $message = "Dear Parent, payment of KES {$amount} for {$student_name} has been REJECTED.";
        }
        
        // Check credits
        $this->load->model('sendsmsmail_model');
        $available_credits = $this->sendsmsmail_model->get_sms_credit($payment->branch_id);
        $credits_needed = 1;
        
        if ($available_credits < $credits_needed) {
            return array('success' => false, 'message' => "Insufficient SMS credits. Available: {$available_credits}, Needed: {$credits_needed}");
        }
        
        // Send SMS
        $this->load->library('bulksmsbd', array('branch_id' => $payment->branch_id));
        $response = $this->bulksmsbd->send($mobile, $message);
        
        // Check if successful
        $sms_sent = false;
        if (strpos($response, 'Success') !== false || strpos($response, 'success') !== false || strpos($response, '200') !== false) {
            $sms_sent = true;
        }
        
        if ($sms_sent) {
            // Deduct credits
            $this->sendsmsmail_model->deduct_sms_units($payment->branch_id, $credits_needed);
            
            // Log the SMS
            $this->db->insert('fees_sms_logs', array(
                'student_id' => $payment->student_id,
                'fee_type_id' => $payment->fees_type_id,
                'recipient_contact' => $mobile,
                'recipient_name' => $payment->parent_name ?? $student_name,
                'recipient_type' => !empty($payment->parent_mobile) ? 'parent' : 'student',
                'message_type' => 'payment',
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'branch_id' => $payment->branch_id
            ));
            
            return array('success' => true, 'message' => 'SMS notification sent to parent');
        } else {
            return array('success' => false, 'message' => 'SMS sending failed. Gateway error.');
        }
        
    } catch (Exception $e) {
        return array('success' => false, 'message' => 'SMS error: ' . $e->getMessage());
    }
}
 
       
    /**
     * Format phone number for SMS sending (Kenyan format)
     * Converts 07XXXXXXXX to 2547XXXXXXXX
     * Converts 7XXXXXXXX to 2547XXXXXXXX
     */
    private function format_phone_number($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a Kenyan number
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            // Format: 07XXXXXXXX -> 2547XXXXXXXX
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
            // Format: 7XXXXXXXX -> 2547XXXXXXXX
            $phone = '254' . $phone;
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            // Already in correct format
            $phone = $phone;
        } elseif (strlen($phone) == 13 && substr($phone, 0, 4) == '+254') {
            // Remove the '+'
            $phone = substr($phone, 1);
        }
        
        return $phone;
    }

    public function getTypeInstruction()
    {
        if ($_POST) {
            $typeID = $this->input->post('typeID');
            if (empty($typeID)) {
                echo null;
                exit;
            }
            $r = $this->db->where('id', $typeID)->get('offline_payment_types')->row();
            if (!empty($r->note)) {
                echo $r->note;
            } else {
                echo "";
            }
        }
    }
}