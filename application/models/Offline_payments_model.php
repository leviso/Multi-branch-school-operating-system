<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Offline_payments_model extends CI_Model
{
    
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get list of offline payments with filters
     * @param array $filter - Optional filters (branch_id, status)
     * @return array
     */
   public function getOfflinePaymentsList($filter = array())
{
    $sql = "SELECT 
                op.*, 
                s.first_name, 
                s.last_name, 
                s.register_no,
                s.mobileno,
                s.email,
                CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) as fullname,
                e.class_id, 
                e.section_id,
                c.name as class_name,
                sec.name as section_name,
                opt.name as payment_type_name
            FROM offline_fees_payments op
            LEFT JOIN enroll e ON e.id = op.student_enroll_id
            LEFT JOIN student s ON s.id = e.student_id
            LEFT JOIN class c ON c.id = e.class_id
            LEFT JOIN section sec ON sec.id = e.section_id
            LEFT JOIN offline_payment_types opt ON opt.id = op.payment_method
            WHERE 1=1";
    
    // Apply branch filter - CRITICAL for admin/accountant
    if (isset($filter['branch_id']) && !empty($filter['branch_id'])) {
        $sql .= " AND op.branch_id = " . (int)$filter['branch_id'];
    }
    
    // Apply status filter
    if (isset($filter['status']) && $filter['status'] != '') {
        $sql .= " AND op.status = " . (int)$filter['status'];
    }
    
    $sql .= " ORDER BY op.id DESC";
    
    $query = $this->db->query($sql);
    
    if ($query && $query->num_rows() > 0) {
        return $query->result();
    }
    
    return array();
}

    /**
     * Get single offline payment details by ID
     * @param int $payment_id
     * @return object|null
     */
    public function getOfflinePaymentsDetails($payment_id)
    {
        $sql = "SELECT 
                    op.*, 
                    s.first_name, 
                    s.last_name, 
                    s.register_no,
                    s.mobileno,
                    s.email,
                    CONCAT(s.first_name, ' ', s.last_name) as fullname,
                    e.class_id, 
                    e.section_id,
                    c.name as class_name,
                    sec.name as section_name,
                    opt.name as payment_type_name
                FROM offline_fees_payments op
                LEFT JOIN enroll e ON e.id = op.student_enroll_id
                LEFT JOIN student s ON s.id = e.student_id
                LEFT JOIN class c ON c.id = e.class_id
                LEFT JOIN section sec ON sec.id = e.section_id
                LEFT JOIN offline_payment_types opt ON opt.id = op.payment_method
                WHERE op.id = " . $this->db->escape($payment_id) . "
                LIMIT 1";
        
        $query = $this->db->query($sql);
        
        if ($query && $query->num_rows() > 0) {
            return $query->row();
        }
        
        return null;
    }

    /**
     * Save/Update offline payment type
     * @param array $post
     * @return void
     */
    public function typeSave($post)
    {
        $arrayType = array(
            'name' => $this->input->post('type_name'),
            'note' => $this->input->post('note'),
            'branch_id' => $this->application_model->get_branch_id(),
        );
        
        if (is_superadmin_loggedin()) {
            $arrayType['branch_id'] = $this->input->post('branch_id');
        }
        
        $typeID = $this->input->post('type_id');
        if (empty($typeID)) {
            $this->db->insert('offline_payment_types', $arrayType);
        } else {
            $this->db->where('id', $typeID);
            $this->db->update('offline_payment_types', $arrayType);
        }
    }

    /**
     * Update fee payment after offline payment approval
     * @param int $id
     * @return void
     */
    public function update($id)
    {
        // Get payment details
        $this->db->where('id', $id);
        $payment = $this->db->get('offline_fees_payments')->row();
        
        if (!$payment) {
            return;
        }
        
        // Get enroll details
        $this->db->where('id', $payment->student_enroll_id);
        $enroll = $this->db->get('enroll')->row();
        
        if (!$enroll) {
            return;
        }
        
        // Insert into fee_payment_history
        $paymentData = array(
            'allocation_id' => $payment->fees_allocation_id,
            'type_id' => $payment->fees_type_id,
            'collect_by' => $payment->approved_by,
            'amount' => $payment->amount,
            'discount' => 0,
            'fine' => 0,
            'pay_via' => 'offline',
            'remarks' => 'Offline payment approved: ' . $payment->reference,
            'date' => date('Y-m-d'),
            'branch_id' => $payment->branch_id  // ADD THIS LINE
        );
        
        $this->db->insert('fee_payment_history', $paymentData);
        
        // Create transaction voucher
        $transactionData = array(
            'account_id' => 1, // Default cash account
            'voucher_head_id' => 1, // Fee collection head
            'type' => 'income',
            'category' => 'fee_collection',
            'ref' => $payment->invoice_no,
            'amount' => $payment->amount,
            'dr' => $payment->amount,
            'cr' => 0,
            'bal' => $payment->amount,
            'date' => date('Y-m-d'),
            'pay_via' => 'offline',
            'description' => 'Offline fee payment approval',
            'branch_id' => $enroll->branch_id,
            'system' => 1
        );
        
        $this->db->insert('transactions', $transactionData);
    }
}