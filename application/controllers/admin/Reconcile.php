<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reconcile extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Only superadmin can access reconciliation
        if (!is_superadmin_loggedin()) {
            access_denied();
        }
        
        $this->load->model('subscription_model');
    }
    
    /**
     * Main reconciliation dashboard
     */
    public function index()
    {
        $status = $this->input->get('status');
        
        // ✅ FIXED: Get transactions with plan names included
        $this->data['transactions'] = $this->get_transactions_with_plan_names($status);
        $this->data['stats'] = $this->subscription_model->get_reconciliation_stats();
        $this->data['title'] = translate('payment_reconciliation');
        $this->data['sub_page'] = 'subscription/reconcile';
        $this->data['main_menu'] = 'subscription';
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * Get transactions with plan names (fix for view)
     */
    private function get_transactions_with_plan_names($status = null)
    {
        $this->db->select('sp.*, b.name as branch_name, b.email as branch_email, b.mobileno as branch_phone, 
                           pl.name as plan_name, bs.status as subscription_status');
        $this->db->from('subscription_payments sp');
        $this->db->join('branch b', 'b.id = sp.branch_id', 'left');
        $this->db->join('subscription_plans pl', 'pl.id = sp.plan_id', 'left');
        $this->db->join('branch_subscriptions bs', 'bs.id = sp.subscription_id', 'left');
        
        if ($status) {
            $this->db->where('sp.status', $status);
        } else {
            $this->db->where_in('sp.status', ['pending', 'failed']);
        }
        
        $this->db->order_by('sp.created_at', 'DESC');
        return $this->db->get()->result();
    }
    
    /**
     * View transaction details
     */
    public function view($id)
    {
        // ✅ FIXED: Get complete transaction with plan name
        $this->db->select('sp.*, b.name as branch_name, b.email as branch_email, b.mobileno as branch_phone,
                           pl.name as plan_name, bs.status as subscription_status');
        $this->db->from('subscription_payments sp');
        $this->db->join('branch b', 'b.id = sp.branch_id', 'left');
        $this->db->join('subscription_plans pl', 'pl.id = sp.plan_id', 'left');
        $this->db->join('branch_subscriptions bs', 'bs.id = sp.subscription_id', 'left');
        $this->db->where('sp.id', $id);
        $transaction = $this->db->get()->row();
        
        if (!$transaction) {
            set_alert('error', 'Transaction not found');
            redirect(base_url('admin/reconcile'));
        }
        
        $this->data['transaction'] = $transaction;
        $this->data['subscription'] = (object)['status' => $transaction->subscription_status ?? 'unknown'];
        $this->data['plan'] = (object)['name' => $transaction->plan_name ?? 'N/A'];
        
        $this->data['title'] = translate('transaction_details');
        $this->data['sub_page'] = 'subscription/reconcile_view';
        $this->data['main_menu'] = 'subscription';
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * Process manual verification (AJAX)
     */
    public function verify()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $this->output->set_content_type('application/json');
        
        $transaction_id = $this->input->post('transaction_id');
        $receipt_number = $this->input->post('receipt_number');
        $notes = $this->input->post('notes');
        
        if (empty($transaction_id)) {
            echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
            return;
        }
        
        $result = $this->subscription_model->manual_verify_subscription(
            $transaction_id,
            $receipt_number,
            $notes
        );
        
        if ($result && isset($result['success'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Subscription activated successfully',
                'redirect' => base_url('admin/reconcile')
            ]);
        } elseif ($result && isset($result['error'])) {
            echo json_encode(['success' => false, 'message' => $result['error']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Verification failed']);
        }
    }
    
    /**
     * Reject/Delete pending transaction (GET request with confirmation)
     */
    public function reject($id)
    {
        if (!is_superadmin_loggedin()) {
            access_denied();
        }
        
        $transaction = $this->subscription_model->get_transaction_for_reconciliation($id);
        
        if (!$transaction) {
            set_alert('error', 'Transaction not found');
            redirect(base_url('admin/reconcile'));
        }
        
        // ✅ FIXED: Get reason from POST or use default
        $reason = $this->input->post('reason');
        if (empty($reason)) {
            $reason = 'Manually rejected by superadmin';
        }
        
        // Update transaction as rejected
        $this->db->where('id', $id);
        $this->db->update('subscription_payments', [
            'status' => 'failed',
            'reconciled_by' => get_loggedin_user_id(),
            'reconciliation_note' => 'Rejected: ' . $reason,
            'reconciled_at' => date('Y-m-d H:i:s')
        ]);
        
        // Delete pending subscription if exists
        $this->db->where('id', $transaction->subscription_id);
        $this->db->where('status', 'pending');
        $this->db->delete('branch_subscriptions');
        
        set_alert('success', 'Transaction rejected and pending subscription removed');
        redirect(base_url('admin/reconcile'));
    }
}