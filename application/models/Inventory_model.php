<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inventory_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

   public function save_product($data)
{
    $insert_product = array(
        'name' => $data['product_name'],
        'code' => $data['product_code'],
        'category_id' => $data['product_category'],
        'purchase_unit_id' => $data['purchase_unit'],
        'sales_unit_id' => $data['sales_unit'],
        'unit_ratio' => $data['unit_ratio'],
        'purchase_price' => $data['purchase_price'],
        'sales_price' => $data['sales_price'],
        'remarks' => $data['remarks'],
        'reorder_point' => $data['reorder_point'] ?? 0,
        'is_returnable' => isset($data['is_returnable']) ? 1 : 0,  // This is the key line
        'branch_id' => $this->application_model->get_branch_id(),
    );
    
    if (isset($data['product_id']) && !empty($data['product_id'])) {
        unset($insert_product['branch_id']);
        $this->db->where('id', $data['product_id']);
        $this->db->update('product', $insert_product);
    } else {
        $this->db->insert('product', $insert_product);
    }
}

    public function save_supplier($data)
    {
        $insertSupplier = array(
            'name' => $data['supplier_name'],
            'email' => $data['email_address'],
            'mobileno' => $data['contact_number'],
            'company_name' => $data['company_name'],
            'product_list' => $data['product_list'],
            'address' => $data['address'],
            'branch_id' => $this->application_model->get_branch_id(),
        );
        if (isset($data['supplier_id']) && !empty($data['supplier_id'])) {
            $this->db->where('id', $data['supplier_id']);
            $this->db->update('product_supplier', $insertSupplier);
        } else {
            $this->db->insert('product_supplier', $insertSupplier);
        }
    }

    public function get_product_list()
    {
        $this->db->select('product.*,product_category.name as category_name,p_unit.name as p_unit_name,s_unit.name as s_unit_name');
        $this->db->from('product');
        $this->db->join('product_category', 'product_category.id = product.category_id', 'left');
        $this->db->join('product_unit as p_unit', 'p_unit.id = product.purchase_unit_id', 'left');
        $this->db->join('product_unit as s_unit', 's_unit.id = product.sales_unit_id', 'left');
        $this->db->order_by('product.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_purchase_list()
    {
        $sql = "SELECT purchase_bill.*,product_supplier.name as supplier_name,staff.name as biller_name FROM purchase_bill LEFT JOIN product_supplier ON product_supplier.id = purchase_bill.supplier_id LEFT JOIN staff ON staff.id = purchase_bill.prepared_by";
        if (!is_superadmin_loggedin()) {
            $sql .= " WHERE product_supplier.branch_id = " . $this->db->escape(get_loggedin_branch_id());
        }
        $sql .= " ORDER BY purchase_bill.id ASC";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function get_invoice($id)
    {
        $this->db->select('purchase_bill.*,product_supplier.name as supplier_name,product_supplier.address as supplier_address,product_supplier.company_name as supplier_company_name,product_supplier.mobileno as supplier_mobileno,staff.name as biller_name');
        $this->db->from('purchase_bill');
        $this->db->join('product_supplier', 'product_supplier.id = purchase_bill.supplier_id', 'left');
        $this->db->join('staff', 'staff.id = purchase_bill.prepared_by', 'left');
        $this->db->where('purchase_bill.id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('purchase_bill.branch_id', get_loggedin_branch_id());
        }
        return $this->db->get()->row_array();
    }

    public function save_purchase($data)
    {
        $arrayInvoice = array(
            'supplier_id' => $data['supplier_id'],
            'bill_no' => $data['bill_no'],
            'store_id' => $data['store_id'],
            'remarks' => $data['remarks'],
            'total' => $data['grand_total'],
            'discount' => $data['total_discount'],
            'due' => $data['net_grand_total'],
            'paid' => 0,
            'payment_status' => 1,
            'purchase_status' => $data['purchase_status'],
            'date' => date('Y-m-d', strtotime($data['date'])),
            'prepared_by' => get_loggedin_user_id(),
            'modifier_id' => get_loggedin_user_id(),
            'branch_id' => $this->application_model->get_branch_id(),
        );
        $this->db->insert('purchase_bill', $arrayInvoice);
        $purchase_bill_id = $this->db->insert_id();

        $arrayData = array();
        $purchases = $data['purchases'];
        foreach ($purchases as $key => $value) {
            $arrayproduct = array(
                'purchase_bill_id' => $purchase_bill_id,
                'product_id' => $value['product'],
                'unit_price' => $value['unit_price'],
                'discount' => $value['discount'],
                'quantity' => $value['quantity'],
                'sub_total' => $value['sub_total'],
            );
            $arrayData[] = $arrayproduct;
            //update product available stock
            if ($data['purchase_status'] == 2) {
                $unit_ratio = $this->db->select('unit_ratio')->where('id', $value['product'])->get('product')->row()->unit_ratio;
                $stockQuantity = ($value['quantity'] * $unit_ratio);
                $this->stock_upgrade($stockQuantity, $value['product']);
            }
        }
        $this->db->insert_batch('purchase_bill_details', $arrayData);
    }

    // add partly of the purchase payment
    public function save_payment($data)
    {
        $payment_status = 1;
        $attach_orig_name = "";
        $attach_file_name = "";
        $purchase_bill_id = $data['purchase_bill_id'];
        $payment_amount = $data['payment_amount'];
        $paid_date = $data['paid_date'];
        // uploading file using codeigniter upload library
        if (isset($_FILES['attach_document']['name']) && !empty($_FILES['attach_document']['name'])) {
            $config['upload_path'] = './uploads/attachments/inventory_payment/';
            $config['allowed_types'] = '*';
            $config['encrypt_name'] = true;
            $this->upload->initialize($config);
            if ($this->upload->do_upload("attach_document")) {
                $attach_orig_name = $this->upload->data('orig_name');
                $attach_file_name = $this->upload->data('file_name');
            }
        }

        $array_history = array(
            'purchase_bill_id' => $purchase_bill_id,
            'payment_by' => get_loggedin_user_id(),
            'amount' => $payment_amount,
            'pay_via' => $this->input->post('pay_via'),
            'remarks' => $this->input->post('remarks'),
            'attach_orig_name' => $attach_orig_name,
            'attach_file_name' => $attach_file_name,
            'coll_type' => 1,
            'paid_on' => date("Y-m-d", strtotime($paid_date)),
        );
        $this->db->insert('purchase_payment_history', $array_history);
        if ($data['getbill']['due'] <= $payment_amount) {
            $payment_status = 3;
        } else {
            $payment_status = 2;
        }
        $sql = "UPDATE `purchase_bill` SET `payment_status` = " . $payment_status . ", `paid` = `paid` + " . $payment_amount . ", `due` = `due` - " . $payment_amount . " WHERE `id` = " . $this->db->escape($purchase_bill_id);
        $this->db->query($sql);
    }

    public function get_stock_product_wisereport($branch_id, $category_id = '')
    {
        $this->db->select('product.*,product_store.name as store_name,product_supplier.name as supplier_name,product_category.name as category_name, (SELECT sum(quantity) from product_issues_details JOIN product_issues ON product_issues.id = product_issues_details.issues_id where product.id=product_issues_details.product_id AND product_issues.status = 0) as total_issued, (SELECT sum(quantity) from sales_bill_details where product.id=sales_bill_details.product_id) as total_sales, IFNULL(SUM(purchase_bill_details.quantity),0) as in_stock');
        $this->db->from('purchase_bill');
        $this->db->join('purchase_bill_details', 'purchase_bill_details.purchase_bill_id = purchase_bill.id', 'inner');
        $this->db->join('product', 'product.id = purchase_bill_details.product_id', 'inner');
        $this->db->join('product_category', 'product_category.id = product.category_id', 'left');
        $this->db->join('product_store', 'purchase_bill.store_id = product_store.id', 'left');
        $this->db->join('product_supplier', 'purchase_bill.supplier_id = product_supplier.id', 'left');
        $this->db->order_by('purchase_bill.id', 'ASC');
        $this->db->where('purchase_bill.branch_id', $branch_id);
        if ($category_id != 'all') {
            $this->db->where('product.category_id', $category_id);
        }
        $this->db->group_by('purchase_bill_details.product_id');
        return $this->db->get()->result_array();
    }

    public function get_purchase_report($branch_id, $supplier_id = '', $payment_status = '', $start = '', $end = '')
    {
        $this->db->select('purchase_bill.*,product_store.name as store_name,IFNULL(SUM(purchase_bill.total - purchase_bill.discount),0) as net_payable,product_supplier.name as supplier_name');
        $this->db->from('purchase_bill');
        $this->db->join('product_supplier', 'product_supplier.id = purchase_bill.supplier_id', 'left');
         $this->db->join('product_store', 'purchase_bill.store_id = product_store.id', 'left');
        if ($supplier_id != 'all') {
            $this->db->where('purchase_bill.supplier_id', $supplier_id);
        }
        if ($payment_status != 'all') {
            $this->db->where('purchase_bill.payment_status', $payment_status);
        }
        $this->db->where('purchase_bill.date >=', $start);
        $this->db->where('purchase_bill.date <=', $end);
        $this->db->where('purchase_bill.branch_id', $branch_id);
        $this->db->group_by('purchase_bill.id');
        $this->db->order_by('purchase_bill.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_sales_report($branch_id, $payment_status = '', $start = '', $end = '')
    {
        $this->db->select('sales_bill.*,roles.name as role_name,IFNULL(SUM(sales_bill.total - sales_bill.discount),0) as net_payable');
        $this->db->from('sales_bill');
        $this->db->join('roles', 'roles.id = sales_bill.role_id', 'left');
        if ($payment_status != 'all') {
            $this->db->where('purchase_bill.payment_status', $payment_status);
        }
        $this->db->where('sales_bill.date >=', $start);
        $this->db->where('sales_bill.date <=', $end);
        $this->db->where('sales_bill.branch_id', $branch_id);
        $this->db->group_by('sales_bill.id');
        $this->db->order_by('sales_bill.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function getIssuesreport($branchID = '', $start = '', $end = '')
    {
        $this->db->select('product_issues.*,product.name as product_name,roles.name as role_name,product_issues_details.quantity,product_category.name as category_name');
        $this->db->from('product_issues_details');
        $this->db->join('product_issues', 'product_issues.id = product_issues_details.issues_id', 'inner');
        $this->db->join('product', 'product.id = product_issues_details.product_id', 'left');
        $this->db->join('product_category', 'product_category.id = product.category_id', 'left');
        $this->db->join('roles', 'roles.id = product_issues.role_id', 'left');
        $this->db->where('product_issues.date_of_issue >=', $start);
        $this->db->where('product_issues.date_of_issue <=', $end);
        $this->db->where('product_issues.branch_id', $branchID);
        $this->db->order_by('product_issues.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function save_store($data)
    {
        $insertStore = array(
            'name' => $data['store_name'],
            'code' => $data['store_code'],
            'mobileno' => $data['mobileno'],
            'address' => $data['address'],
            'description' => $data['description'],
            'branch_id' => $this->application_model->get_branch_id(),
        );
        if (isset($data['store_id']) && !empty($data['store_id'])) {
            $this->db->where('id', $data['store_id']);
            $this->db->update('product_store', $insertStore);
        } else {
            $this->db->insert('product_store', $insertStore);
        }
    }

    public function getProductByBranch($branch_id = '')
    {
        if (!empty($branch_id)) {
            $this->db->where('branch_id', $branch_id);
            $result = $this->db->get('product')->result_array();
            return $result;
        }
        return "";
    }

    public function save_sales($data)
    {
        $paid = 0;
        $paymentStatus = 1;
        $dueAmount = $data['net_amount'];
        if (!empty($data['payment_amount'])) {
            $paymentStatus = 2;
            $paid = $data['payment_amount'];
            $dueAmount = ($data['net_amount'] - $paid);
            if ($data['net_amount'] == $paid) {
                $paymentStatus = 3;
            }
        }

        $arrayInvoice = array(
            'bill_no' => $data['bill_no'],
            'role_id' => $data['role_id'],
            'user_id' => $data['sale_to'],
            'remarks' => $data['payment_remarks'],
            'total' => $data['grand_total'],
            'discount' => $data['total_discount'],
            'due' => $dueAmount,
            'paid' => $paid,
            'payment_status' => $paymentStatus,
            'date' => date('Y-m-d', strtotime($data['date'])),
            'prepared_by' => get_loggedin_user_id(),
            'modifier_id' => get_loggedin_user_id(),
            'branch_id' => $this->application_model->get_branch_id(),
        );
        $this->db->insert('sales_bill', $arrayInvoice);
        $sales_bill_id = $this->db->insert_id();

        $arrayData = array();
        $sales = $data['sales'];
        foreach ($sales as $key => $value) {
            $arrayproduct = array(
                'sales_bill_id' => $sales_bill_id,
                'product_id' => $value['product'],
                'unit_price' => $value['unit_price'],
                'discount' => $value['discount'],
                'quantity' => $value['quantity'],
                'sub_total' => $value['sub_total'],
            );
            $arrayData[] = $arrayproduct;

            //update product available stock
            $this->stock_upgrade($value['quantity'], $value['product'], false);
        }
        $this->db->insert_batch('sales_bill_details', $arrayData);

        if (!empty($data['payment_amount'])) {
            $arrayInvoice = array(
                'sales_bill_id' => $sales_bill_id,
                'amount' => $data['payment_amount'],
                'pay_via' => $data['pay_via'],
                'payment_by' => get_loggedin_user_id(),
                'remarks' => $data['payment_remarks'],
                'coll_type' => 1,
                'attach_orig_name' => '',
                'attach_file_name' => '',
                'paid_on' => date("Y-m-d"),
            );
            $this->db->insert('sales_payment_history', $arrayInvoice);
        }
    }

    public function save_issue($data)
{
    $arrayInvoice = array(
        'role_id' => $data['role_id'],
        'user_id' => $data['sale_to'],
        'remarks' => $data['remarks'],
        'date_of_issue' => date('Y-m-d', strtotime($data['date_of_issue'])),
        'due_date' => date('Y-m-d', strtotime($data['due_date'])),
        'prepared_by' => get_loggedin_user_id(),
        'branch_id' => $this->application_model->get_branch_id(),
    );
    $this->db->insert('product_issues', $arrayInvoice);
    $issues_id = $this->db->insert_id();
    
    $arrayData = array();
    $sales = $data['sales'];
    foreach ($sales as $key => $value) {
        // Check if product is returnable
        $product = $this->db->select('is_returnable')->where('id', $value['product'])->get('product')->row();
        $is_returnable = $product ? $product->is_returnable : 1;
        
        $arrayproduct = array(
            'issues_id' => $issues_id,
            'product_id' => $value['product'],
            'quantity' => $value['quantity'],
            'is_returnable' => $is_returnable, // Add this field
        );
        $arrayData[] = $arrayproduct;
        
        // Always deduct stock (whether returnable or not)
        $this->stock_upgrade($value['quantity'], $value['product'], false);
    }
    $this->db->insert_batch('product_issues_details', $arrayData);
}

    public function getSalesList()
    {
        $this->db->select('sales_bill.*,roles.name as role_name');
        $this->db->from('sales_bill');
        $this->db->join('roles', 'roles.id = sales_bill.role_id', 'left');
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $this->db->order_by('sales_bill.id', 'asc');
        $result = $this->db->get()->result_array();
        return $result;
    }

    public function getSalesInvoice($id)
    {
        $this->db->select('sales_bill.*,staff.name as biller_name,roles.name as role_name');
        $this->db->from('sales_bill');
        $this->db->join('roles', 'roles.id = sales_bill.role_id', 'left');
        $this->db->join('staff', 'staff.id = sales_bill.prepared_by', 'left');
        $this->db->where('sales_bill.id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('sales_bill.branch_id', get_loggedin_branch_id());
        }
        return $this->db->get()->row_array();
    }


    // add partly of the sales payment
    public function save_sales_payment($data)
    {
        $payment_status = 1;
        $attach_orig_name = "";
        $attach_file_name = "";
        $sales_bill_id = $data['sales_bill_id'];
        $payment_amount = $data['payment_amount'];
        $paid_date = $data['paid_date'];
        // uploading file using codeigniter upload library
        if (isset($_FILES['attach_document']['name']) && !empty($_FILES['attach_document']['name'])) {
            $config['upload_path'] = './uploads/attachments/inventory_payment/';
            $config['allowed_types'] = '*';
            $config['encrypt_name'] = true;
            $this->upload->initialize($config);
            if ($this->upload->do_upload("attach_document")) {
                $attach_orig_name = $this->upload->data('orig_name');
                $attach_file_name = $this->upload->data('file_name');
            }
        }

        $array_history = array(
            'sales_bill_id' => $sales_bill_id,
            'payment_by' => get_loggedin_user_id(),
            'amount' => $payment_amount,
            'pay_via' => $this->input->post('pay_via'),
            'remarks' => $this->input->post('remarks'),
            'attach_orig_name' => $attach_orig_name,
            'attach_file_name' => $attach_file_name,
            'coll_type' => 1,
            'paid_on' => date("Y-m-d", strtotime($paid_date)),
        );
        $this->db->insert('sales_payment_history', $array_history);
        if ($data['getbill']['due'] <= $payment_amount) {
            $payment_status = 3;
        } else {
            $payment_status = 2;
        }
        $sql = "UPDATE `sales_bill` SET `payment_status` = " . $payment_status . ", `paid` = `paid` + " . $payment_amount . ", `due` = `due` - " . $payment_amount . " WHERE `id` = " . $this->db->escape($sales_bill_id);
        $this->db->query($sql);
    }

    public function stock_upgrade($quantity, $productID, $add = true)
    {
        if ($add == true) {
            $sql = "UPDATE `product` SET `available_stock` = `available_stock` + " . $quantity . " WHERE `id` = " . $this->db->escape($productID);
        } else {
            $sql = "UPDATE `product` SET `available_stock` = `available_stock` - " . $quantity . " WHERE `id` = " . $this->db->escape($productID);
        }
        $this->db->query($sql);
    }

    public function getIssueList()
    {
        $this->db->select('product_issues.*,roles.name as role_name');
        $this->db->from('product_issues');
        $this->db->join('roles', 'roles.id = product_issues.role_id', 'left');
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $this->db->order_by('product_issues.id', 'asc');
        $result = $this->db->get()->result_array();
        return $result;
    }
    /**
     * Get products with low stock for a branch
     * 
     * @param int $branch_id
     * @return array
     */
    public function get_low_stock_products($branch_id = null)
    {
        $this->db->select('p.id, p.name, p.code, p.available_stock, p.reorder_point, 
                          pc.name as category_name, p.branch_id')
            ->from('product p')
            ->join('product_category pc', 'pc.id = p.category_id', 'left');
        
        if ($branch_id) {
            $this->db->where('p.branch_id', $branch_id);
        }
        
        // Show products where stock is below or equal to reorder point, but not zero
        $this->db->where('p.reorder_point >', 0);
        $this->db->where('p.available_stock <=', 'p.reorder_point', false);
        $this->db->where('p.available_stock >', 0);
        $this->db->order_by('p.available_stock', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get products with zero stock
     * 
     * @param int $branch_id
     * @return array
     */
    public function get_out_of_stock_products($branch_id = null)
    {
        $this->db->select('p.id, p.name, p.code, p.available_stock, p.reorder_point, 
                          pc.name as category_name, p.branch_id')
            ->from('product p')
            ->join('product_category pc', 'pc.id = p.category_id', 'left');
        
        if ($branch_id) {
            $this->db->where('p.branch_id', $branch_id);
        }
        
        $this->db->where('p.available_stock', 0);
        $this->db->order_by('p.name', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Update last stock alert timestamp
     * 
     * @param int $product_id
     * @return bool
     */
    public function update_last_stock_alert($product_id)
    {
        $this->db->where('id', $product_id);
        return $this->db->update('product', [
            'last_stock_alert_sent' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Check if alert should be sent (prevents spam)
     * Default: only send once per 24 hours per product
     * 
     * @param int $product_id
     * @param int $hours_threshold
     * @return bool
     */
    public function should_send_stock_alert($product_id, $hours_threshold = 24)
    {
        $product = $this->db->select('last_stock_alert_sent, available_stock, reorder_point')
            ->where('id', $product_id)
            ->get('product')
            ->row();
        
        if (!$product) {
            return false;
        }
        
        // Don't send alert if stock is no longer low
        if ($product->available_stock > $product->reorder_point) {
            return false;
        }
        
        // No alert sent before
        if (empty($product->last_stock_alert_sent)) {
            return true;
        }
        
        // Check if enough time has passed
        $last_alert = strtotime($product->last_stock_alert_sent);
        $next_allowed = strtotime("+{$hours_threshold} hours", $last_alert);
        
        return time() >= $next_allowed;
    }


/**
 * Get uniform stock report with dynamic sections
 * @param int $branch_id
 * @return array Grouped by dynamic report sections
 */
public function get_uniform_stock_report_dynamic($branch_id)
{
    // Get all uniform product groups for this branch
    $groups = $this->db->select('rg.id, rg.group_name, rg.display_order')
        ->from('report_groups rg')
        ->where('rg.branch_id', $branch_id)
        ->where('rg.group_type', 'uniform_section')
        ->where('rg.is_active', 1)
        ->order_by('rg.display_order', 'ASC')
        ->get()
        ->result_array();
    
    $report_data = [];
    
    foreach ($groups as $group) {
        // Get products assigned to this group
        $products = $this->db->select('p.id, p.name, p.has_variants, p.description as product_desc')
            ->from('product p')
            ->join('product_report_groups prg', 'prg.product_id = p.id')
            ->where('prg.group_id', $group['id'])
            ->where('prg.branch_id', $branch_id)
            ->where('p.product_type', 'uniform')
            ->where('p.branch_id', $branch_id)
            ->order_by('p.name', 'ASC')
            ->get()
            ->result_array();
        
        $group_data = [];
        $counter = 1;
        
        foreach ($products as $product) {
            // Get variants (sizes) for this product
            $variants = $this->db->select('pv.id, pv.variant_value as size, usb.quantity, usb.description')
                ->from('product_variants pv')
                ->join('uniform_stock_balances usb', 'usb.variant_id = pv.id AND usb.product_id = pv.product_id', 'left')
                ->where('pv.product_id', $product['id'])
                ->where('pv.branch_id', $branch_id)
                ->order_by('pv.display_order', 'ASC')
                ->order_by('pv.variant_value', 'ASC')
                ->get()
                ->result_array();
            
            if (!empty($variants) && $product['has_variants'] == 1) {
                // Product has multiple sizes/variants
                $first_variant = true;
                foreach ($variants as $variant) {
                    $group_data[] = [
                        'serial_no' => $first_variant ? $counter : '',
                        'product_name' => $first_variant ? $product['name'] : '',
                        'description' => $variant['description'] ?? '',
                        'size' => $variant['size'],
                        'quantity' => $variant['quantity'] ?? 0,
                        'is_first' => $first_variant
                    ];
                    $first_variant = false;
                }
                $counter++;
            } else {
                // Product without variants
                $stock = $this->db->select('quantity, description')
                    ->from('uniform_stock_balances')
                    ->where('product_id', $product['id'])
                    ->where('branch_id', $branch_id)
                    ->get()
                    ->row();
                
                $group_data[] = [
                    'serial_no' => $counter,
                    'product_name' => $product['name'],
                    'description' => $stock ? $stock->description : ($product['product_desc'] ?? ''),
                    'size' => '',
                    'quantity' => $stock ? $stock->quantity : 0,
                    'is_first' => true
                ];
                $counter++;
            }
        }
        
        $report_data[$group['group_name']] = $group_data;
    }
    
    return $report_data;
}


/**
 * Calculate weekly consumption for a specific product
 * @param int $branch_id
 * @param int $product_id
 * @param int $term_id
 * @param int $week_number
 * @param int $year
 * @return bool
 */
public function calculate_weekly_consumption($branch_id, $product_id, $term_id, $week_number, $year)
{
    // Get term details
    $term = $this->db->select('term_start_date, term_end_date')
        ->where('id', $term_id)
        ->get('exam_term')
        ->row();
    
    if (!$term || !$term->term_start_date) {
        return false;
    }
    
    // Calculate week start and end dates
    $week_start = date('Y-m-d', strtotime($term->term_start_date . ' + ' . ($week_number - 1) . ' weeks'));
    $week_end = date('Y-m-d', strtotime($week_start . ' + 6 days'));
    
    // Get opening stock (previous week's closing or initial stock)
    $previous_week = $this->db->select('closing_stock')
        ->from('weekly_stock_consumption')
        ->where('product_id', $product_id)
        ->where('term_id', $term_id)
        ->where('year', $year)
        ->where('week_number', $week_number - 1)
        ->where('branch_id', $branch_id)
        ->get()
        ->row();
    
    $opening_stock = $previous_week ? $previous_week->closing_stock : $this->get_product_current_stock($product_id, $branch_id);
    
    // Get received quantity from purchase bills
    $received = $this->db->select_sum('pbd.quantity')
        ->from('purchase_bill pb')
        ->join('purchase_bill_details pbd', 'pbd.purchase_bill_id = pb.id')
        ->where('pb.branch_id', $branch_id)
        ->where('pbd.product_id', $product_id)
        ->where('pb.purchase_status', 2) // Received status
        ->where('pb.date >=', $week_start)
        ->where('pb.date <=', $week_end)
        ->get()
        ->row()
        ->quantity;
    
    // Get consumed quantity from sales
    $sales_consumed = $this->db->select_sum('sbd.quantity')
        ->from('sales_bill sb')
        ->join('sales_bill_details sbd', 'sbd.sales_bill_id = sb.id')
        ->where('sb.branch_id', $branch_id)
        ->where('sbd.product_id', $product_id)
        ->where('sb.date >=', $week_start)
        ->where('sb.date <=', $week_end)
        ->get()
        ->row()
        ->quantity;
    
    // Get consumed quantity from issues
    $issued_consumed = $this->db->select_sum('pid.quantity')
        ->from('product_issues pi')
        ->join('product_issues_details pid', 'pid.issues_id = pi.id')
        ->where('pi.branch_id', $branch_id)
        ->where('pid.product_id', $product_id)
        ->where('pi.date_of_issue >=', $week_start)
        ->where('pi.date_of_issue <=', $week_end)
        ->get()
        ->row()
        ->quantity;
    
    $received_val = floatval($received);
    $consumed = floatval($sales_consumed) + floatval($issued_consumed);
    $closing_stock = $opening_stock + $received_val - $consumed;
    
    // Get product unit
    $product = $this->db->select('unit_name')->where('id', $product_id)->get('product')->row();
    $unit = $product ? $product->unit_name : '';
    
    // Save or update weekly record
    $data = [
        'product_id' => $product_id,
        'term_id' => $term_id,
        'year' => $year,
        'week_number' => $week_number,
        'week_start_date' => $week_start,
        'week_end_date' => $week_end,
        'opening_stock' => $opening_stock,
        'received_quantity' => $received_val,
        'consumed_quantity' => $consumed,
        'closing_stock' => $closing_stock,
        'unit' => $unit,
        'branch_id' => $branch_id,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $exists = $this->db->where('product_id', $product_id)
        ->where('term_id', $term_id)
        ->where('year', $year)
        ->where('week_number', $week_number)
        ->where('branch_id', $branch_id)
        ->get('weekly_stock_consumption')
        ->num_rows();
    
    if ($exists) {
        $this->db->where('product_id', $product_id)
            ->where('term_id', $term_id)
            ->where('year', $year)
            ->where('week_number', $week_number)
            ->where('branch_id', $branch_id)
            ->update('weekly_stock_consumption', $data);
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('weekly_stock_consumption', $data);
    }
    
    return true;
}

/**
 * Calculate all weekly consumption for a term
 * @param int $branch_id
 * @param int $term_id
 * @param int $year
 * @return array
 */
public function calculate_all_weekly_consumption($branch_id, $term_id, $year)
{
    // Get all consumable products
    $products = $this->db->select('id, name')
        ->from('product')
        ->where('branch_id', $branch_id)
        ->where('product_type', 'consumable')
        ->get()
        ->result();
    
    $term = $this->db->select('total_weeks')
        ->where('id', $term_id)
        ->get('exam_term')
        ->row();
    
    $total_weeks = $term ? (int)$term->total_weeks : 14;
    $success_count = 0;
    $fail_count = 0;
    $results = [];
    
    foreach ($products as $product) {
        for ($week = 1; $week <= $total_weeks; $week++) {
            $result = $this->calculate_weekly_consumption($branch_id, $product->id, $term_id, $week, $year);
            if ($result) {
                $success_count++;
            } else {
                $fail_count++;
            }
        }
        $results[] = [
            'product_name' => $product->name,
            'success' => $result
        ];
    }
    
    return [
        'total_products' => count($products),
        'total_weeks' => $total_weeks,
        'success_count' => $success_count,
        'fail_count' => $fail_count,
        'details' => $results
    ];
}

/**
 * Get current stock for a product
 * @param int $product_id
 * @param int $branch_id
 * @return float
 */
private function get_product_current_stock($product_id, $branch_id)
{
    $product = $this->db->select('available_stock')
        ->where('id', $product_id)
        ->where('branch_id', $branch_id)
        ->get('product')
        ->row();
    
    return $product ? floatval($product->available_stock) : 0;
}

/**
 * Save uniform stock balance
 * @param array $data
 * @return bool
 */
public function save_uniform_stock($data)
{
    $exists = $this->db->where('product_id', $data['product_id'])
        ->where('variant_id', $data['variant_id'])
        ->where('branch_id', $data['branch_id'])
        ->get('uniform_stock_balances')
        ->num_rows();
    
    $save_data = [
        'product_id' => $data['product_id'],
        'variant_id' => $data['variant_id'],
        'description' => $data['description'] ?? '',
        'quantity' => $data['quantity'],
        'minimum_stock' => $data['minimum_stock'] ?? 0,
        'reorder_level' => $data['reorder_level'] ?? 0,
        'branch_id' => $data['branch_id'],
        'updated_by' => get_loggedin_user_id(),
        'updated_at' => date('Y-m-d H:i:s'),
        'last_updated' => date('Y-m-d')
    ];
    
    if ($exists) {
        $this->db->where('product_id', $data['product_id'])
            ->where('variant_id', $data['variant_id'])
            ->where('branch_id', $data['branch_id'])
            ->update('uniform_stock_balances', $save_data);
    } else {
        $save_data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('uniform_stock_balances', $save_data);
    }
    
    return $this->db->affected_rows() > 0;
}

/**
 * Get all report groups for a branch
 * @param int $branch_id
 * @param string $type
 * @return array
 */
public function get_report_groups($branch_id, $type = null)
{
    $this->db->select('*')
        ->from('report_groups')
        ->where('branch_id', $branch_id)
        ->where('is_active', 1);
    
    if ($type) {
        $this->db->where('group_type', $type);
    }
    
    return $this->db->order_by('display_order', 'ASC')->get()->result_array();
}

/**
 * Get products not assigned to any group
 * @param int $branch_id
 * @param string $product_type
 * @return array
 */
public function get_unassigned_products($branch_id, $product_type)
{
    return $this->db->select('p.id, p.name, p.code, pc.name as category')
        ->from('product p')
        ->join('product_category pc', 'pc.id = p.category_id', 'left')
        ->where('p.branch_id', $branch_id)
        ->where('p.product_type', $product_type)
        ->where("p.id NOT IN (SELECT product_id FROM product_report_groups WHERE branch_id = {$branch_id})", NULL, FALSE)
        ->get()
        ->result_array();
}
/**
 * Get weekly consumption report data
 * @param int $branch_id
 * @param int $term_id
 * @param int $year
 * @return array
 */
public function get_weekly_consumption_report($branch_id, $term_id, $year)
{
    // Get term details
    $term = $this->db->select('id, name, total_weeks')
        ->where('id', $term_id)
        ->get('exam_term')
        ->row();
    
    $total_weeks = $term ? (int)$term->total_weeks : 14;
    
    // Get all consumable products with their weekly data
    $products = $this->db->select('id, name, unit_name')
        ->from('product')
        ->where('branch_id', $branch_id)
        ->where('product_type', 'consumable')
        ->order_by('name', 'ASC')
        ->get()
        ->result_array();
    
    // Define section mappings
    $spices_items = array('pilau masala', 'garam masala', 'white pepper', 'turmeric', 
                          'red paprika', 'royco mchuzi', 'royco cubes', 'soy sauce', 'tomato paste');
    $cereals_items = array('beans', 'peas', 'lentils', 'green grams');
    $farm_items = array('tomatoes', 'potatoes', 'onions', 'carrots', 'butternuts', 'capsicum');
    
    $sections = array();
    $counters = array();
    
    foreach ($products as $product) {
        $name = strtolower($product['name']);
        
        // Determine section
        if (in_array($name, $spices_items)) {
            $section = 'SPICES';
        } elseif (in_array($name, $cereals_items)) {
            $section = 'CEREALS';
        } elseif (in_array($name, $farm_items)) {
            $section = 'FARM ITEMS';
        } else {
            $section = 'STOCK';
        }
        
        // Initialize counter
        if (!isset($counters[$section])) {
            $counters[$section] = 1;
        }
        
        // Get weekly data
        $weekly_data = array();
        for ($week = 1; $week <= $total_weeks; $week++) {
            $week_record = $this->db->select('closing_stock, unit')
                ->from('weekly_stock_consumption')
                ->where('product_id', $product['id'])
                ->where('term_id', $term_id)
                ->where('year', $year)
                ->where('week_number', $week)
                ->where('branch_id', $branch_id)
                ->get()
                ->row();
            
            if ($week_record && $week_record->closing_stock > 0) {
                $unit = $week_record->unit ?: $product['unit_name'];
                $weekly_data[$week] = $week_record->closing_stock . ' ' . $unit;
            } elseif ($week_record && $week_record->closing_stock == 0) {
                $weekly_data[$week] = '0';
            } else {
                $weekly_data[$week] = '-';
            }
        }
        
        $sections[$section][] = array(
            'serial_no' => $counters[$section]++,
            'product_name' => $product['name'],
            'weekly_data' => $weekly_data
        );
    }
    
    // Remove empty sections
    foreach ($sections as $key => $value) {
        if (empty($value)) {
            unset($sections[$key]);
        }
    }
    
    return array(
        'sections' => $sections,
        'total_weeks' => $total_weeks,
        'term' => $term
    );
}
/**
 * Get uniform stock report data
 * @param int $branch_id
 * @return array
 */
public function get_uniform_stock_report($branch_id)
{
    $report_data = array(
        'SENIOR' => array(),
        'JUNIOR' => array()
    );
    
    // Query to get all uniform products with their variants and stock
    $this->db->select('
        p.id as product_id,
        p.name as product_name,
        pv.id as variant_id,
        pv.variant_value as size,
        usb.quantity,
        usb.description
    ');
    $this->db->from('product p');
    $this->db->join('product_variants pv', 'pv.product_id = p.id', 'left');
    $this->db->join('uniform_stock_balances usb', 'usb.variant_id = pv.id AND usb.product_id = p.id', 'left');
    $this->db->where('p.branch_id', $branch_id);
    $this->db->where('p.product_type', 'uniform');
    $this->db->order_by('p.name', 'ASC');
    $this->db->order_by('pv.variant_value', 'ASC');
    
    $query = $this->db->get();
    $results = $query->result_array();
    
    // Junior products list
    $junior_products = array('BLAZER', 'T-SHIRTS', 'TRUCK SUITS', 'TIES', 'POLO T-SHIRTS', 'SHIRTS', 'TROUSERS');
    
    $senior_counter = 1;
    $junior_counter = 1;
    $last_senior = '';
    $last_junior = '';
    
    foreach ($results as $row) {
        $is_junior = in_array($row['product_name'], $junior_products);
        
        if ($is_junior) {
            if ($last_junior != $row['product_name']) {
                $junior_counter = 1;
                $last_junior = $row['product_name'];
            }
            $report_data['JUNIOR'][] = array(
                'serial_no' => $junior_counter++,
                'product_name' => $row['product_name'],
                'description' => $row['description'] ?: '',
                'size' => $row['size'] ?: '',
                'quantity' => (int)($row['quantity'] ?: 0)
            );
        } else {
            if ($last_senior != $row['product_name']) {
                $senior_counter = 1;
                $last_senior = $row['product_name'];
            }
            $report_data['SENIOR'][] = array(
                'serial_no' => $senior_counter++,
                'product_name' => $row['product_name'],
                'description' => $row['description'] ?: '',
                'size' => $row['size'] ?: '',
                'quantity' => (int)($row['quantity'] ?: 0)
            );
        }
    }
    
    return $report_data;
}
public function get_weekly_consumption_report_dynamic($branch_id, $term_id, $year)
{
    // Get term details
    $term = $this->db->select('id, name, total_weeks')
        ->where('id', $term_id)
        ->get('exam_term')
        ->row();
    
    $total_weeks = $term ? (int)$term->total_weeks : 14;
    
    // Get all consumable products
    $products = $this->db->select('id, name, unit_name')
        ->from('product')
        ->where('branch_id', $branch_id)
        ->where('product_type', 'consumable')
        ->order_by('name', 'ASC')
        ->get()
        ->result_array();
    
    $sections = array();
    $counter = 1;
    
    foreach ($products as $product) {
        $weekly_data = array();
        for ($week = 1; $week <= $total_weeks; $week++) {
            $week_record = $this->db->select('closing_stock, unit')
                ->from('weekly_stock_consumption')
                ->where('product_id', $product['id'])
                ->where('term_id', $term_id)
                ->where('year', $year)
                ->where('week_number', $week)
                ->where('branch_id', $branch_id)
                ->get()
                ->row();
            
            if ($week_record && $week_record->closing_stock > 0) {
                $unit = $week_record->unit ?: $product['unit_name'];
                $weekly_data[$week] = $week_record->closing_stock . ' ' . $unit;
            } else {
                $weekly_data[$week] = '-';
            }
        }
        
        // You can categorize products by their category_id or name
        $section = 'STOCK'; // Default section
        $sections[$section][] = array(
            'serial_no' => $counter++,
            'product_name' => $product['name'],
            'weekly_data' => $weekly_data
        );
    }
    
    return array(
        'sections' => $sections,
        'total_weeks' => $total_weeks,
        'term' => $term
    );
}
/**
 * Check if all products in an issue are returnable
 * @param int $issue_id
 * @return bool
 */
/**
 * Check if all products in an issue are returnable
 * @param int $issue_id
 * @return bool
 */
public function is_issue_returnable($issue_id)
{
    $count = $this->db->where('issues_id', $issue_id)
        ->where('is_returnable', 0)
        ->count_all_results('product_issues_details');
    
    return $count == 0;
}

/**
 * Get non-returnable product names for an issue
 * @param int $issue_id
 * @return array
 */
public function get_non_returnable_items($issue_id)
{
    return $this->db->select('p.name')
        ->from('product_issues_details pid')
        ->join('product p', 'p.id = pid.product_id')
        ->where('pid.issues_id', $issue_id)
        ->where('pid.is_returnable', 0)
        ->get()
        ->result_array();
}

/**
 * Process return of items and restore stock
 * @param int $issue_id
 * @return bool
 */
public function process_return($issue_id)
{
    // Get all items and restore stock
    $items = $this->db->get_where('product_issues_details', array('issues_id' => $issue_id))->result();
    foreach ($items as $item) {
        $this->stock_upgrade($item->quantity, $item->product_id);
    }
    
    // Update issue status
    $this->db->where('id', $issue_id);
    return $this->db->update('product_issues', ['status' => 1, 'return_date' => date("Y-m-d")]);
}
}
