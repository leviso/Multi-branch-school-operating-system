<?php
defined('BASEPATH') or exit('No direct script access allowed');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * @package : Lsquare solutions school management system
 * @version : 1.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Inventory.php
 * @copyright : Reserved Synobix Team
 */

class Inventory extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('inventory_model');
    }

    public function index()
    {
        $this->product();
    }

    /* product form validation rules */
    protected function product_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('product_name', translate('product') . " " . translate('name'), 'trim|required');
        $this->form_validation->set_rules('product_code', translate('product') . " " . translate('code'), 'trim|required');
        $this->form_validation->set_rules('product_category', translate('product') . " " . translate('category'), 'trim|required');
        $this->form_validation->set_rules('purchase_unit', translate('purchase_unit'), 'trim|required|numeric');
        $this->form_validation->set_rules('sales_unit', translate('sales_unit'), 'trim|required|numeric');
        $this->form_validation->set_rules('unit_ratio', translate('unit_ratio'), 'trim|required|numeric');
        $this->form_validation->set_rules('purchase_price', translate('purchase_price'), 'trim|required|numeric');
        $this->form_validation->set_rules('sales_price', translate('sales_price'), 'trim|required|numeric');
        $this->form_validation->set_rules('reorder_point', translate('reorder_point'), 'trim|numeric');
    }

    // add new product
    public function product()
    {
        // check access permission
        if (!get_permission('product', 'is_view')) {
            access_denied();
        }
        if ($_POST) {
            if (!get_permission('product', 'is_add')) {
                ajax_access_denied();
            }
            $this->product_validation();
            if ($this->form_validation->run() == true) {
                // save product information in the database
                $post = $this->input->post();
                $this->inventory_model->save_product($post);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $array = array('status' => 'success');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit;
        }

        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['productlist'] = $this->inventory_model->get_product_list();
        $this->data['unitlist'] = $this->app_lib->getSelectByBranch('product_category', $branchID);
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/product';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    // update existing product
public function product_edit($id)
{
    if (!get_permission('product', 'is_edit')) {
        access_denied();
    }
    
    if ($_POST) {
        $this->product_validation();
        if ($this->form_validation->run() == true) {
            $post = $this->input->post();
            $this->inventory_model->save_product($post);
            set_alert('success', translate('information_has_been_updated_successfully'));
            $array = array('status' => 'success', 'url' => base_url('inventory/product'));
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'error' => $error);
        }
        echo json_encode($array);
        exit;
    }

    $this->data['product'] = $this->app_lib->getTable('product', array('t.id' => $id), true);
    $this->data['categorylist'] = $this->app_lib->getSelectByBranch('product_category', $this->data['product']['branch_id']);
    $this->data['unitlist'] = $this->app_lib->getSelectByBranch('product_unit', $this->data['product']['branch_id']);
    $this->data['title'] = translate('inventory');
    $this->data['sub_page'] = 'inventory/product_edit';
    $this->data['main_menu'] = 'inventory';
    $this->load->view('layout/index', $this->data);
}

/**
 * Get product category details for editing via AJAX
 */
public function getProductCategoryDetails()
{
    if (!get_permission('product_category', 'is_edit')) {
        echo json_encode(array('error' => 'Permission denied'));
        exit;
    }
    
    $id = $this->input->post('id');
    if (empty($id)) {
        echo json_encode(array('error' => 'Invalid ID'));
        exit;
    }
    
    // Get category details
    $category = $this->db->select('pc.*, b.name as branch_name')
        ->from('product_category pc')
        ->join('branch b', 'b.id = pc.branch_id', 'left')
        ->where('pc.id', $id)
        ->get()
        ->row_array();
    
    if (empty($category)) {
        echo json_encode(array('error' => 'Category not found'));
        exit;
    }
    
    echo json_encode($category);
}

    // delete product from database
    public function product_delete($id)
    {
        // check access permission
        if (!get_permission('product', 'is_delete')) {
            access_denied();
        }

        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $this->db->where('id', $id);
        $this->db->delete('product');
    }

    // add category from database
    public function category()
    {
        if (isset($_POST['category'])) {
            if (!get_permission('product_category', 'is_add')) {
                access_denied();
            }
            if (is_superadmin_loggedin()) {
                $this->form_validation->set_rules('branch_id', translate('branch'), 'trim|required');
            }
            $this->form_validation->set_rules('category_name', 'Category Name', 'trim|required|callback_unique_category');
            if ($this->form_validation->run() !== false) {
                $arrayCategory = array(
                    'name' => $this->input->post('category_name'),
                    'branch_id' => $this->application_model->get_branch_id(),
                );
                $this->db->insert('product_category', $arrayCategory);
                set_alert('success', translate('information_has_been_saved_successfully'));
                redirect(base_url('inventory/category'));
            }
        }
        $this->data['categorylist'] = $this->app_lib->getTable('product_category');
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/category';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    public function category_edit()
{
    // check access permission
    if (!get_permission('product_category', 'is_edit')) {
        access_denied();
    }
    
    if (is_superadmin_loggedin()) {
        $this->form_validation->set_rules('branch_id', translate('branch'), 'trim|required');
    }
    $this->form_validation->set_rules('category_name', 'Category Name', 'trim|required|callback_unique_category');
    
    if ($this->form_validation->run() !== false) {
        $category_id = $this->input->post('category_id');
        $arrayCategory = array(
            'name' => $this->input->post('category_name'),
            'branch_id' => $this->application_model->get_branch_id(),
        );
        
        $this->db->where('id', $category_id);
        $this->db->update('product_category', $arrayCategory);
        
        set_alert('success', translate('information_has_been_updated_successfully'));
    } else {
        set_alert('error', validation_errors());
    }
    
    redirect(base_url('inventory/category'));
}

    // delete category from database
    public function category_delete($id)
    {
        // check access permission
        if (!get_permission('product_category', 'is_delete')) {
            access_denied();
        }
        $this->db->where('id', $id);
        $this->db->delete('product_category');
    }

   // duplicate category name check in db
public function unique_category($name)
{
    $branch_id = $this->application_model->get_branch_id();
    $category_id = $this->input->post('category_id');
    
    if (!empty($category_id)) {
        $this->db->where_not_in('id', $category_id);
    }
    $this->db->where('name', $name);
    $this->db->where('branch_id', $branch_id);
    $query = $this->db->get('product_category');
    
    if ($query->num_rows() > 0) {
        if (!empty($category_id)) {
            set_alert('error', "The Category name is already used");
            return false;
        } else {
            $this->form_validation->set_message("unique_category", "The %s name is already used.");
            return false;
        }
    } else {
        return true;
    }
}

    // add new supplier member
    public function supplier()
    {
        // check access permission
        if (!get_permission('product_supplier', 'is_view')) {
            access_denied();
        }
        if ($_POST) {
            if (!get_permission('product_supplier', 'is_add')) {
                ajax_access_denied();
            }
            if (is_superadmin_loggedin()) {
                $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
            }
            $this->form_validation->set_rules('supplier_name', translate('supplier_name'), 'trim|required');
            $this->form_validation->set_rules('contact_number', translate('contact_number'), 'trim|required|numeric');
            if ($this->form_validation->run() == true) {
                $post = $this->input->post();
                $this->inventory_model->save_supplier($post);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $array = array('status' => 'success');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }

        $this->data['supplierlist'] = $this->app_lib->getTable('product_supplier');
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/supplier';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    // update existing supplier member
    public function supplier_edit($id)
    {
        // check access permission
        if (!get_permission('product_supplier', 'is_edit')) {
            access_denied();
        }
        if ($_POST) {
            if (is_superadmin_loggedin()) {
                $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
            }
            $this->form_validation->set_rules('supplier_name', translate('supplier_name'), 'trim|required');
            $this->form_validation->set_rules('contact_number', translate('contact_number'), 'trim|required');
            if ($this->form_validation->run() == true) {
                $post = $this->input->post();
                $this->inventory_model->save_supplier($post);
                set_alert('success', translate('information_has_been_updated_successfully'));
                $array = array('status' => 'success', 'url' => base_url('inventory/supplier'));
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }

        $this->data['supplier'] = $this->app_lib->getTable('product_supplier', array('t.id' => $id), true);
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/supplier_edit';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    // delete existing supplier member
    public function supplier_delete($id)
    {
        // check access permission
        if (!get_permission('product_supplier', 'is_delete')) {
            access_denied();
        }
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $this->db->where('id', $id);
        $this->db->delete('product_supplier');
    }

    public function unit()
    {
        if (isset($_POST['unit'])) {
            if (!get_permission('product_unit', 'is_add')) {
                access_denied();
            }
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'trim|required');
        }
            $this->form_validation->set_rules('unit_name', 'Unit Name', 'trim|required|callback_unique_unit');
            if ($this->form_validation->run() !== false) {
                $arrayUnit = array(
                    'name' => $this->input->post('unit_name'), 
                    'branch_id' => $this->application_model->get_branch_id(), 
                );
                $this->db->insert('product_unit', $arrayUnit);
                set_alert('success', translate('information_has_been_saved_successfully'));
                redirect(base_url('inventory/unit'));
            }
        }
        $this->data['unitlist'] = $this->inventory_model->get('product_unit');
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/unit';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    public function unit_edit()
    {
        if (!get_permission('product_unit', 'is_edit')) {
            access_denied();
        }
    if (is_superadmin_loggedin()) {
        $this->form_validation->set_rules('branch_id', translate('branch'), 'trim|required');
    }
        $this->form_validation->set_rules('unit_name', 'Unit Name', 'trim|required|callback_unique_unit');
        if ($this->form_validation->run() !== false) {
            $unit_id = $this->input->post('unit_id');
            $arrayUnit = array(
                'name' => $this->input->post('unit_name'), 
                'branch_id' => $this->application_model->get_branch_id(), 
            );
            $this->db->where('id', $unit_id);
            $this->db->update('product_unit', $arrayUnit);
            set_alert('success', translate('information_has_been_updated_successfully'));
        }
        redirect(base_url('inventory/unit'));
    }

    public function unit_delete($id)
    {
        if (!get_permission('product_unit', 'is_delete')) {
            access_denied();
        }
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $this->db->where('id', $id);
        $this->db->delete('product_unit');
    }

    public function unitDetails()
    {
        if (get_permission('product_unit', 'is_edit')) {
            $id = $this->input->post('id');
            $this->db->where('id', $id);
            $query = $this->db->get('product_unit');
            $result = $query->row_array();
            echo json_encode($result);
        }
    }

    public function unique_unit($name)
    {
        $unit_id = $this->input->post('unit_id');
        if (!empty($unit_id)) {
            $this->db->where_not_in('id', $unit_id);
        }
        $this->db->where('name', $name);
        $query = $this->db->get('product_unit');
        if ($query->num_rows() > 0) {
            if (!empty($unit_id)) {
                set_alert('error', "The Category name are already used");
            } else {
                $this->form_validation->set_message("unique_unit", "The %s name are already used.");
            }
            return false;
        } else {
            return true;
        }
    }

    // add new product purchase bill
    public function purchase()
    {
        if (!get_permission('product_purchase', 'is_view')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['purchaselist'] = $this->inventory_model->get_purchase_list();
        $this->data['productlist'] = $this->inventory_model->getProductByBranch($branchID);
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/purchase';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    public function purchaseItems()
    {
        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['productlist'] = $this->inventory_model->getProductByBranch($branchID);
        echo $this->load->view('inventory/purchaseItems', $this->data, true);
    }

    public function getPurchasePrice()
    {
        $id = $this->input->post('id');
        $price = $this->db->select('IFNULL(purchase_price,0) as price,purchase_unit_id')->where('id', $id)->get('product')->row_array();
        $unit = $this->db->select('name')->where('id', $price['purchase_unit_id'])->get('product_unit')->row();
        echo json_encode(['price' => $price['price'], 'unit' => $unit->name]);
    }

    /* purchase form validation rules */
    protected function purchase_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('supplier_id', 'Supplier', 'trim|required');
        $this->form_validation->set_rules('store_id', 'Store', 'trim|required');
        $this->form_validation->set_rules('bill_no', 'Bill No', 'trim|required');
        $this->form_validation->set_rules('purchase_status', 'Purchase Status', 'trim|required');
        $this->form_validation->set_rules('date', 'Date', 'trim|required');
        $items = $this->input->post('purchases');
        if (!empty($items)) {
            foreach ($items as $key => $value) {
                $this->form_validation->set_rules('purchases[' . $key . '][product]', 'Product', 'trim|required');
                $this->form_validation->set_rules('purchases[' . $key . '][quantity]', 'Quantity', 'trim|required');
            }
        }
    }

    public function purchase_save()
    {
        if (!get_permission('product_purchase', 'is_add')) {
            access_denied();
        }
        if ($_POST) {
            $this->purchase_validation();
            if ($this->form_validation->run() == false) {
                $msg = array(
                    'supplierID' => form_error('supplier_id'),
                    'storeID' => form_error('store_id'),
                    'bill_no' => form_error('bill_no'),
                    'purchase_status' => form_error('purchase_status'),
                    'date' => form_error('date'),
                    'delivery_time' => form_error('delivery_time'),
                    'payment_amount' => form_error('payment_amount'),
                );
                if (is_superadmin_loggedin()) {
                    $msg['branch_id'] = form_error('branch_id');
                }
                $items = $this->input->post('purchases');
                if (!empty($items)) {
                    foreach ($items as $key => $value) {
                        $msg['product' . $key] = form_error('purchases[' . $key . '][product]');
                        $msg['quantity' . $key] = form_error('purchases[' . $key . '][quantity]');
                    }
                }
                $array = array('status' => 'fail', 'url' => '', 'error' => $msg);
            } else {
                $data = $this->input->post();
                $this->inventory_model->save_purchase($data);
                $url = base_url('inventory/purchase');
                set_alert('success', translate('information_has_been_saved_successfully'));
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
            }
            echo json_encode($array);
        }
    }

    public function purchaseMakeReceived($id = '')
    {
        if (!get_permission('product_purchase', 'is_eit')) {
            access_denied();
        }
        if (!empty($id)) {
            $r = $this->db->select('count(id) as cid')->where(['id' => $id, 'purchase_status !=' => 2])->get('purchase_bill')->row()->cid;
            if ($r > 0) {
                $bill_details = $this->db->where('purchase_bill_id', $id)->get('purchase_bill_details')->result();
                foreach ($bill_details as $key => $value) {
                    $unit_ratio = $this->db->select('unit_ratio')->where('id', $value->product_id)->get('product')->row()->unit_ratio;
                    $sql = "UPDATE `product` SET `available_stock` = `available_stock` + " . ($value->quantity * $unit_ratio) . " WHERE `id` = " . $this->db->escape($value->product_id);
                    $this->db->query($sql);
                }
                $this->db->where('id', $id);
                $this->db->update('purchase_bill', ['purchase_status' => 2]);
            }
        }
    }

    public function purchase_edit_save()
    {
        if (!get_permission('product_purchase', 'is_edit')) {
            access_denied();
        }
        if ($_POST) {
            // validate inputs
            $this->form_validation->set_rules('supplier_id', 'Supplier', 'trim|required');
            $this->form_validation->set_rules('store_id', 'Store', 'trim|required');
            $this->form_validation->set_rules('bill_no', 'Bill No', 'trim|required');
            $this->form_validation->set_rules('purchase_status', 'Purchase Status', 'trim|required');
            $this->form_validation->set_rules('date', 'Date', 'trim|required');
            $items = $this->input->post('purchases');
            foreach ($items as $key => $value) {
                $this->form_validation->set_rules('purchases[' . $key . '][product]', 'Product', 'trim|required');
                $this->form_validation->set_rules('purchases[' . $key . '][quantity]', 'Quantity', 'trim|required');
            }
            if ($this->form_validation->run() == false) {
                $msg = array(
                    'supplierID' => form_error('supplier_id'),
                    'storeID' => form_error('store_id'),
                    'bill_no' => form_error('bill_no'),
                    'purchase_status' => form_error('purchase_status'),
                    'date' => form_error('date'),
                    'delivery_time' => form_error('delivery_time'),
                    'payment_amount' => form_error('payment_amount'),
                );
                foreach ($items as $key => $value) {
                    $msg['product' . $key] = form_error('purchases[' . $key . '][product]');
                    $msg['quantity' . $key] = form_error('purchases[' . $key . '][quantity]');
                }
                $array = array('status' => 'fail', 'url' => '', 'error' => $msg);
            } else {

                $purchase_bill_id = $this->input->post('purchase_bill_id');
                $supplier_id = $this->input->post('supplier_id');
                $store_id = $this->input->post('store_id');
                $bill_no = $this->input->post('bill_no');
                $purchase_status = $this->input->post('purchase_status');
                $grand_total = $this->input->post('grand_total');
                $discount = $this->input->post('total_discount');
                $purchase_paid = $this->input->post('purchase_paid');
                $net_total = $this->input->post('net_grand_total');
                $date = $this->input->post('date');
                $remarks = $this->input->post('remarks');
                if ($net_total <= $purchase_paid) {
                    $payment_status = 3;
                } else {
                    $payment_status = 2;
                }
                $array_invoice = array(
                    'supplier_id' => $supplier_id,
                    'store_id' => $store_id,
                    'bill_no' => $bill_no,
                    'remarks' => $remarks,
                    'total' => $grand_total,
                    'discount' => $discount,
                    'due' => ($net_total - $purchase_paid),
                    'purchase_status' => $purchase_status,
                    'payment_status' => $payment_status,
                    'date' => date('Y-m-d', strtotime($date)),
                    'modifier_id' => get_loggedin_user_id(),
                );
                $this->db->where('id', $purchase_bill_id);
                $this->db->update('purchase_bill', $array_invoice);

                $purchases = $this->input->post('purchases');
                foreach ($purchases as $key => $value) {
                    $array_product = array(
                        'purchase_bill_id' => $purchase_bill_id,
                        'product_id' => $value['product'],
                        'unit_price' => $value['unit_price'],
                        'discount' => $value['discount'],
                        'quantity' => $value['quantity'],
                        'sub_total' => $value['sub_total'],
                    );

                    if (isset($value['old_product_id'])) {
                        if ($value['old_product_id'] == $value['product']) {
                            $unit_ratio = $this->db->select('unit_ratio')->where('id', $value['old_product_id'])->get('product')->row()->unit_ratio;
                            if (isset($value['old_quantity'])) {
                                if ($value['quantity'] >= $value['old_quantity']) {
                                    $stock = floatval(($value['quantity'] * $unit_ratio) - ($value['old_quantity'] * $unit_ratio));
                                    $this->inventory_model->stock_upgrade($stock, $value['product']);
                                } else {
                                    $stock = floatval(($value['old_quantity'] * $unit_ratio) - ($value['quantity'] * $unit_ratio));
                                    $this->inventory_model->stock_upgrade($stock, $value['product'], false);
                                }
                            }
                        } else {
                            $unit_ratio = $this->db->select('unit_ratio')->where('id', $value['old_product_id'])->get('product')->row()->unit_ratio;
                            $newunit_ratio = $this->db->select('unit_ratio')->where('id', $value['product'])->get('product')->row()->unit_ratio;
                            $this->inventory_model->stock_upgrade(($value['old_quantity'] * $unit_ratio), $value['old_product_id'], false);
                            $this->inventory_model->stock_upgrade(($value['quantity'] * $newunit_ratio), $value['product']);
                        }
                    }

                    if (isset($value['old_bill_details_id'])) {
                        $this->db->where('id', $value['old_bill_details_id']);
                        $this->db->update('purchase_bill_details', $array_product);
                    } else {
                        $unit_ratio = $this->db->select('unit_ratio')->where('id', $value['product'])->get('product')->row()->unit_ratio;
                        $this->inventory_model->stock_upgrade(($value['quantity'] * $unit_ratio), $value['product']);
                        $this->db->insert('purchase_bill_details', $array_product);
                    }
                }
                $url = base_url('inventory/purchase');
                set_alert('success', translate('information_has_been_updated_successfully'));
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
            }
            echo json_encode($array);
        }
    }

    // update existing product purchase bill
    public function purchase_edit($id)
    {
        if (!get_permission('product_purchase', 'is_edit')) {
            access_denied();
        }

        $this->data['purchaselist'] = $this->app_lib->getTable('purchase_bill', array('t.id' => $id), true);
        $branchID = $this->data['purchaselist']['branch_id'];
        $this->data['branch_id'] = $branchID;
        $this->data['productlist'] = $this->inventory_model->getProductByBranch($branchID);
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/purchase_edit';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    // delete product purchase bill from database
    public function purchase_delete($id)
    {
        if (!get_permission('product_purchase', 'is_delete')) {
            access_denied();
        }

        $getStock = $this->db->get_where('purchase_bill_details', array('purchase_bill_id' => $id))->result();
        foreach ($getStock as $key => $value) {
            $unit_ratio = $this->db->select('unit_ratio')->where('id', $value->product_id)->get('product')->row()->unit_ratio;
            $this->inventory_model->stock_upgrade(($value->quantity * $unit_ratio), $value->product_id, false);
        }

        $this->db->where('id', $id);
        $this->db->delete('purchase_bill');

        $this->db->where('purchase_bill_id', $id);
        $this->db->delete('purchase_bill_details');

        //delete purchase payment history from database
        $this->db->where('purchase_bill_id', $id);
        $this->db->delete('purchase_payment_history');
    }

    public function purchase_bill($id = '')
    {
        if (!get_permission('purchase_payment', 'is_add')) {
            access_denied();
        }
        $this->data['billdata'] = $this->inventory_model->get_invoice($id);
        if (empty($this->data['billdata'])) {
            access_denied();
        }
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/dropify/css/dropify.min.css',
            ),
            'js' => array(
                'vendor/dropify/js/dropify.min.js',
            ),
        );
        $this->data['payvia_list'] = $this->app_lib->getSelectList('payment_types');
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/purchase_bill';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    // purchase partially payment add
    public function add_payment()
    {
        if (!get_permission('purchase_payment', 'is_add')) {
            access_denied();
        }
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['getbill'] = $this->db->select('id,due')->where('id', $data['purchase_bill_id'])->get('purchase_bill')->row_array();
            $this->form_validation->set_rules('paid_date', 'Paid Date', 'trim|required');
            $this->form_validation->set_rules('payment_amount', 'Payment Amount', 'trim|required|numeric|greater_than[1]|callback_payment_validation');
            $this->form_validation->set_rules('pay_via', 'Pay Via', 'trim|required');
            $this->form_validation->set_rules('attach_document', translate('attach_document'), 'callback_fileHandleUpload[attach_document]');
            if ($this->form_validation->run() !== false) {
                $this->inventory_model->save_payment($data);
                set_alert('success', translate('payment_successfull'));
                if (get_permission('purchase_payment', 'is_view')) {
                    $this->session->set_flashdata('active_tab', 2);
                }
                $array = array('status' => 'success');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    // payment amount validation
    public function payment_validation($amount)
    {
        $bill_id = $this->input->post('purchase_bill_id');
        $due_amount = $this->db->select('due')->where('id', $bill_id)->get('purchase_bill')->row()->due;
        if ($amount <= $due_amount) {
            return true;
        } else {
            $this->form_validation->set_message("payment_validation", "Payment Amount Is More Than The Due Amount.");
            return false;
        }
    }

    /* store form validation rules */
    protected function store_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('store_name', translate('name'), 'trim|required');
        $this->form_validation->set_rules('store_code', translate('store_code'), 'trim|required');
        $this->form_validation->set_rules('mobileno', translate('mobile_no'), 'trim|required|numeric');
    }

    /* add new store member */
    public function store()
    {
        // check access permission
        if (!get_permission('product_store', 'is_view')) {
            access_denied();
        }
        if ($_POST) {
            if (!get_permission('product_store', 'is_add')) {
                ajax_access_denied();
            }
            $this->store_validation();
            if ($this->form_validation->run() == true) {
                $post = $this->input->post();
                $this->inventory_model->save_store($post);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $array = array('status' => 'success');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }

        $this->data['storelist'] = $this->app_lib->getTable('product_store');
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/store';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    // update existing store member
    public function store_edit($id)
    {
        // check access permission
        if (!get_permission('product_store', 'is_edit')) {
            access_denied();
        }
        if ($_POST) {
            $this->store_validation();
            if ($this->form_validation->run() == true) {
                $post = $this->input->post();
                $this->inventory_model->save_store($post);
                set_alert('success', translate('information_has_been_updated_successfully'));
                $array = array('status' => 'success', 'url' => base_url('inventory/store'));
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }

        $this->data['store'] = $this->app_lib->getTable('product_store', array('t.id' => $id), true);
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/store_edit';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    // delete existing store
    public function store_delete($id)
    {
        // check access permission
        if (!get_permission('product_store', 'is_delete')) {
            access_denied();
        }
        if (!is_superadmin_loggedin()) {
            $this->db->where('branch_id', get_loggedin_branch_id());
        }
        $this->db->where('id', $id);
        $this->db->delete('product_store');
    }

    /* sales form validation rules */
    protected function sales_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('role_id', translate('role'), 'trim|required');
        $this->form_validation->set_rules('sale_to', translate('sale_to'), 'trim|required');
        $this->form_validation->set_rules('date', translate('date'), 'trim|required');
        $this->form_validation->set_rules('bill_no', translate('bill_no'), 'trim|required|numeric');
        $this->form_validation->set_rules('payment_amount', translate('payment_amount'), 'trim|numeric|callback_sales_amount');
        $payment_amount = $this->input->post('payment_amount');
        if (!empty($payment_amount)) {
            $this->form_validation->set_rules('pay_via', translate('pay_via'), 'trim|required');
        }
        $items = $this->input->post('sales');
        if (!empty($items)) {
            foreach ($items as $key => $value) {
                $this->form_validation->set_rules('sales[' . $key . '][category]', translate('category'), 'trim|required');
                $this->form_validation->set_rules('sales[' . $key . '][product]', translate('product'), 'trim|required');
                $this->form_validation->set_rules('sales[' . $key . '][quantity]', translate('quantity'), 'trim|required');
            }
        }
    }

    public function sales()
    {
        if (!get_permission('product_sales', 'is_view')) {
            access_denied();
        }
        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['saleslist'] = $this->inventory_model->getSalesList();
        $this->data['categorylist'] = $this->app_lib->getSelectByBranch('product_category', $branchID);
        $this->data['payvia_list'] = $this->app_lib->getSelectList('payment_types');
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/sales';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    public function sales_save()
    {
        if (!get_permission('product_purchase', 'is_add')) {
            access_denied();
        }
        if ($_POST) {
            $this->sales_validation();
            if ($this->form_validation->run() == false) {
                $msg = array(
                    'bill_no' => form_error('bill_no'),
                    'payment_amount' => form_error('payment_amount'),
                    'pay_via' => form_error('pay_via'),
                    'roleID' => form_error('role_id'),
                    'receiverID' => form_error('sale_to'),
                    'date' => form_error('date'),
                );
                if (is_superadmin_loggedin()) {
                    $msg['branchID'] = form_error('branch_id');
                }
                $items = $this->input->post('sales');
                if (!empty($items)) {
                    foreach ($items as $key => $value) {
                        $msg['category' . $key] = form_error('sales[' . $key . '][category]');
                        $msg['product' . $key] = form_error('sales[' . $key . '][product]');
                        $msg['quantity' . $key] = form_error('sales[' . $key . '][quantity]');
                    }
                }
                $array = array('status' => 'fail', 'url' => '', 'error' => $msg);
            } else {
                $data = $this->input->post();
                $this->inventory_model->save_sales($data);
                $url = base_url('inventory/sales');
                set_alert('success', translate('information_has_been_saved_successfully'));
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
            }
            echo json_encode($array);
        }
    }

    public function getSaleprice()
    {
        $id = $this->input->post('id');
        $price = $this->db->select('IFNULL(sales_price,0) as salesprice,available_stock,sales_unit_id')->where('id', $id)->get('product')->row_array();
        $unit = $this->db->select('name')->where('id', $price['sales_unit_id'])->get('product_unit')->row();
        echo json_encode(['price' => $price['salesprice'], 'unit' => $unit->name, 'availablestock' => translate('available_stock_quantity') . " : " . $price['available_stock']]);
    }

    public function saleItems()
    {
        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['categorylist'] = $this->app_lib->getSelectByBranch('product_category', $branchID);
        echo $this->load->view('inventory/saleItems', $this->data, true);
    }

    public function getProductByCategory()
    {
        $category_id = $this->input->post('category_id');
        $selected_id = $this->input->post('selected_id');
        $productlist = $this->db->select('id,name,code')->where('category_id', $category_id)->get('product')->result_array();
        $html = "<option value=''>" . translate('select') . "</option>";
        foreach ($productlist as $product) {
            $selected = ($product['id'] == $selected_id ? 'selected' : '');
            $html .= "<option value='" . $product['id'] . "' " . $selected . ">" . $product['name'] . " (" . $product['code'] . ")</option>";
        }
        echo $html;
    }

    // check valid received amount
    public function sales_amount($amount)
    {
        if (!empty($amount)) {
            $net_payable = $this->input->post('net_payable_amount');
            if ($net_payable < $amount) {
                $this->form_validation->set_message('sales_amount', "Invalid Received Amount.");
                return false;
            }
        }
        return true;
    }

    public function sales_invoice($id = '')
    {
        if (!get_permission('product_sales', 'is_view')) {
            access_denied();
        }
        $this->data['billdata'] = $this->inventory_model->getSalesInvoice($id);
        if (empty($this->data['billdata'])) {
            access_denied();
        }
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/dropify/css/dropify.min.css',
            ),
            'js' => array(
                'vendor/dropify/js/dropify.min.js',
            ),
        );
        $this->data['payvia_list'] = $this->app_lib->getSelectList('payment_types');
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/sales_invoice';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    // sales partially payment add
    public function add_sales_payment()
    {
        if (!get_permission('sales_payment', 'is_add')) {
            access_denied();
        }
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['getbill'] = $this->db->select('id,due')->where('id', $data['sales_bill_id'])->get('sales_bill')->row_array();
            $this->form_validation->set_rules('paid_date', 'Paid Date', 'trim|required');
            $this->form_validation->set_rules('payment_amount', 'Payment Amount', 'trim|required|numeric|greater_than[1]|callback_sales_amount_validation');
            $this->form_validation->set_rules('pay_via', 'Pay Via', 'trim|required');
            $this->form_validation->set_rules('attach_document', translate('attach_document'), 'callback_fileHandleUpload[attach_document]');
            if ($this->form_validation->run() !== false) {
                $this->inventory_model->save_sales_payment($data);
                set_alert('success', translate('payment_successfull'));
                if (get_permission('purchase_payment', 'is_view')) {
                    $this->session->set_flashdata('active_tab', 2);
                }
                $array = array('status' => 'success');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    // payment amount validation
    public function sales_amount_validation($amount)
    {
        $bill_id = $this->input->post('sales_bill_id');
        $due_amount = $this->db->select('due')->where('id', $bill_id)->get('sales_bill')->row()->due;
        if ($amount <= $due_amount) {
            return true;
        } else {
            $this->form_validation->set_message("sales_amount_validation", "Payment Amount Is More Than The Due Amount.");
            return false;
        }
    }

    // delete product sales bill from database
    public function sales_delete($id)
    {
        if (!get_permission('product_sales', 'is_delete')) {
            access_denied();
        }
        $getStock = $this->db->get_where('sales_bill_details', array('sales_bill_id' => $id))->result();
        foreach ($getStock as $key => $value) {
            $this->inventory_model->stock_upgrade(($value->quantity), $value->product_id);
        }

        $this->db->where('id', $id);
        $this->db->delete('sales_bill');

        $this->db->where('sales_bill_id', $id);
        $this->db->delete('sales_bill_details');

        $this->db->where('sales_bill_id', $id);
        $this->db->delete('sales_bill_details');
    }

    /* issue form validation rules */
    protected function issue_validation()
    {
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('role_id', translate('role'), 'trim|required');
        $this->form_validation->set_rules('sale_to', translate('sale_to'), 'trim|required');
        $this->form_validation->set_rules('date_of_issue', translate('date_of_issue'), 'trim|required');
        $this->form_validation->set_rules('due_date', translate('due_date'), 'trim|required');
        $items = $this->input->post('sales');
        if (!empty($items)) {
            foreach ($items as $key => $value) {
                $this->form_validation->set_rules('sales[' . $key . '][category]', translate('category'), 'trim|required');
                $this->form_validation->set_rules('sales[' . $key . '][product]', translate('product'), 'trim|required');
                $this->form_validation->set_rules('sales[' . $key . '][quantity]', translate('quantity'), 'trim|required');
            }
        }
    }

    public function issue()
    {
        if (!get_permission('product_issue', 'is_view')) {
            access_denied();
        }
        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['saleslist'] = $this->inventory_model->getIssueList();
        $this->data['categorylist'] = $this->app_lib->getSelectByBranch('product_category', $branchID);
        $this->data['title'] = translate('inventory');
        $this->data['sub_page'] = 'inventory/issue';
        $this->data['main_menu'] = 'inventory';
        $this->load->view('layout/index', $this->data);
    }

    public function issue_save()
    {
        if (!get_permission('product_issue', 'is_add')) {
            access_denied();
        }
        if ($_POST) {
            $this->issue_validation();
            if ($this->form_validation->run() == false) {
                $msg = array(
                    'date_of_issue' => form_error('date_of_issue'),
                    'due_date' => form_error('due_date'),
                    'roleID' => form_error('role_id'),
                    'receiverID' => form_error('sale_to'),
                );
                if (is_superadmin_loggedin()) {
                    $msg['branchID'] = form_error('branch_id');
                }
                $items = $this->input->post('sales');
                if (!empty($items)) {
                    foreach ($items as $key => $value) {
                        $msg['category' . $key] = form_error('sales[' . $key . '][category]');
                        $msg['product' . $key] = form_error('sales[' . $key . '][product]');
                        $msg['quantity' . $key] = form_error('sales[' . $key . '][quantity]');
                    }
                }
                $array = array('status' => 'fail', 'url' => '', 'error' => $msg);
            } else {
                $data = $this->input->post();
                $this->inventory_model->save_issue($data);
                $url = base_url('inventory/issue');
                set_alert('success', translate('information_has_been_saved_successfully'));
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
            }
            echo json_encode($array);
        }
    }

    public function issueItems()
    {
        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['categorylist'] = $this->app_lib->getSelectByBranch('product_category', $branchID);
        echo $this->load->view('inventory/issueItems', $this->data, true);
    }

    // delete product issue from database
    public function issue_delete($id)
    {
        if (!get_permission('product_issue', 'is_delete')) {
            access_denied();
        }
        $getStock = $this->db->get_where('product_issues_details', array('issues_id' => $id))->result();
        foreach ($getStock as $key => $value) {
            $this->inventory_model->stock_upgrade(($value->quantity), $value->product_id);
        }

        $this->db->where('id', $id);
        $this->db->delete('product_issues');

        $this->db->where('issues_id', $id);
        $this->db->delete('product_issues_details');
    }

    public function returnProduct()
{
    if ($_POST) {
        if (!get_permission('product_issue', 'is_add')) {
            ajax_access_denied();
        }
        
        $issue_id = $this->input->post('issue_id');
        
        // Check if returnable
        if (!$this->inventory_model->is_issue_returnable($issue_id)) {
            $non_returnable = $this->inventory_model->get_non_returnable_items($issue_id);
            $item_names = array_column($non_returnable, 'name');
            $array = array(
                'status' => 'fail', 
                'message' => 'Cannot return. Non-returnable items: ' . implode(', ', $item_names)
            );
            echo json_encode($array);
            return;
        }
        
        // Process return
        $this->inventory_model->process_return($issue_id);
        
        set_alert('success', translate('information_has_been_saved_successfully'));
        echo json_encode(array('status' => 'success'));
    }
}

    public function getIssueDetails()
    {
        if (get_permission('product_issue', 'is_view')) {
            $this->data['salary_id'] = $this->input->post('id');
            $this->load->view('inventory/issue_modalView', $this->data);
        }
    }

    // inventory reports
    public function stockreport()
    {
        if (!get_permission('inventory_report', 'is_view')) {
            access_denied();
        }
        $branchID = $this->application_model->get_branch_id();
        if (isset($_POST['search'])) {
            $category_id = $this->input->post('category_id');
           
            $this->data['results'] = $this->inventory_model->get_stock_product_wisereport($branchID, $category_id);
        }
        $this->data['title'] = translate('inventory');
        $this->data['categorylist'] = $this->app_lib->getSelectByBranch('product_category',  $branchID, true);
        $this->data['sub_page'] = 'inventory/stockreport';
        $this->data['main_menu'] = 'inventory_report';
        $this->load->view('layout/index', $this->data);
    }

    public function purchase_report()
    {
        if (!get_permission('inventory_report', 'is_view')) {
            access_denied();
        }
        $branchID = $this->application_model->get_branch_id();
        if (isset($_POST['search'])) {
            $supplier_id = $this->input->post('supplier_id');
            $payment_status = $this->input->post('payment_status');
            $daterange = explode(' - ', $this->input->post('daterange'));
            $start = date("Y-m-d", strtotime($daterange[0]));
            $end = date("Y-m-d", strtotime($daterange[1]));
            $this->data['daterange'] = $daterange;
            $this->data['results'] = $this->inventory_model->get_purchase_report($branchID, $supplier_id, $payment_status, $start, $end);
        }
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/daterangepicker/daterangepicker.css',
            ),
            'js' => array(
                'vendor/moment/moment.js',
                'vendor/daterangepicker/daterangepicker.js',
            ),
        );
        $this->data['title'] = translate('inventory');
        $this->data['supplierlist'] = $this->app_lib->getSelectByBranch('product_supplier', $branchID, true);
        $this->data['sub_page'] = 'inventory/purchase_report';
        $this->data['main_menu'] = 'inventory_report';
        $this->load->view('layout/index', $this->data);
    }

    public function sales_report()
    {
        if (!get_permission('inventory_report', 'is_view')) {
            access_denied();
        }
        $branchID = $this->application_model->get_branch_id();
        if (isset($_POST['search'])) {
            $supplier_id = $this->input->post('supplier_id');
            $payment_status = $this->input->post('payment_status');
            $daterange = explode(' - ', $this->input->post('daterange'));
            $start = date("Y-m-d", strtotime($daterange[0]));
            $end = date("Y-m-d", strtotime($daterange[1]));
            $this->data['daterange'] = $daterange;
            $this->data['results'] = $this->inventory_model->get_sales_report($branchID, $payment_status, $start, $end);
        }
        $this->data['title'] = translate('inventory');
        $this->data['supplierlist'] = $this->app_lib->getSelectByBranch('product_supplier', $branchID, true);
        $this->data['sub_page'] = 'inventory/sales_report';
        $this->data['main_menu'] = 'inventory_report';
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

    public function issues_report()
    {
        if (!get_permission('inventory_report', 'is_view')) {
            access_denied();
        }
        $branchID = $this->application_model->get_branch_id();
        if (isset($_POST['search'])) {
            $supplier_id = $this->input->post('supplier_id');
            $payment_status = $this->input->post('payment_status');
            $daterange = explode(' - ', $this->input->post('daterange'));
            $start = date("Y-m-d", strtotime($daterange[0]));
            $end = date("Y-m-d", strtotime($daterange[1]));
            $this->data['daterange'] = $daterange;
            $this->data['results'] = $this->inventory_model->getIssuesreport($branchID, $start, $end);
        }
        $this->data['title'] = translate('inventory');
        $this->data['supplierlist'] = $this->app_lib->getSelectByBranch('product_supplier', $branchID, true);
        $this->data['sub_page'] = 'inventory/issues_report';
        $this->data['main_menu'] = 'inventory_report';
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

    public function getDataByBranch()
    {
        $html = "";
        $table = $this->input->post('table');
        $branch_id = $this->application_model->get_branch_id();
        if (!empty($branch_id)) {
            $result = $this->db->select('id,name')->where('branch_id', $branch_id)->get($table)->result_array();
            if (count($result)) {
                $html .= "<option value=''>" . translate('select') . "</option>";
                $html .= "<option value='all'>" . translate('all_select') . "</option>";
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

    public function getProductUnitDetails()
    {
        if (get_permission('product_unit', 'is_edit')) {
            $id = $this->input->post('id');
            $this->db->where('id', $id);
            $query = $this->db->get('product_unit');
            $result = $query->row_array();
            echo json_encode($result);
        }
    }
        /**
     * Send low stock alert SMS to admin
     * 
     * @param array $low_stock_products
     * @param int $branch_id
     * @return int Number of SMS sent
     */
    private function _send_low_stock_alert_sms($low_stock_products, $branch_id)
    {
        if (empty($low_stock_products)) {
            return 0;
        }
        
        $this->load->model('sendsmsmail_model');
        
        // Build message with top 5 low stock products
        $message = "LOW STOCK ALERT: The following products need reordering:\n";
        $count = 0;
        
        foreach ($low_stock_products as $product) {
            if ($count >= 5) break;
            $message .= "- {$product['name']}: {$product['available_stock']} left (Min: {$product['reorder_point']})\n";
            $count++;
        }
        
        if (count($low_stock_products) > 5) {
            $message .= "And " . (count($low_stock_products) - 5) . " more products.\n";
        }
        
        $message .= "Please check inventory.";
        
        // Get admin mobile for this branch (role = 2)
        $admin = $this->db->select('staff.mobileno')
            ->from('staff')
            ->join('login_credential', 'login_credential.user_id = staff.id')
            ->where('staff.branch_id', $branch_id)
            ->where('login_credential.role', 2)
            ->where('staff.mobileno !=', '')
            ->get()
            ->row();
        
        if (!$admin || empty($admin->mobileno)) {
            return 0;
        }
        
        // Get SMS gateway
        $sms_credential = $this->db->select('sms_api.name as gateway')
            ->from('sms_credential')
            ->join('sms_api', 'sms_api.id = sms_credential.sms_api_id')
            ->where('sms_credential.branch_id', $branch_id)
            ->where('sms_credential.is_active', 1)
            ->get()
            ->row();
        
        $gateway = ($sms_credential && $sms_credential->gateway) ? $sms_credential->gateway : 'bulksmsbd';
        
        // Send SMS
        try {
            $mobile = preg_replace('/[^0-9]/', '', $admin->mobileno);
            if (strlen($mobile) >= 9) {
                $result = $this->sendsmsmail_model->sendSMS_with_credit_check(
                    $mobile,
                    $message,
                    '',
                    '',
                    $gateway,
                    '',
                    $branch_id
                );
                
                if ($result) {
                    // Update last alert timestamp
                    foreach ($low_stock_products as $product) {
                        $this->inventory_model->update_last_stock_alert($product['id']);
                    }
                    return 1;
                }
            }
        } catch (Exception $e) {
            // log_message('error', 'Low stock SMS failed: ' . $e->getMessage());
        }
        
        return 0;
    }
    
    /**
     * Check low stock and send alerts (can be called by cron)
     * 
     * @param int $branch_id Optional branch ID
     */
    public function check_low_stock_alerts($branch_id = null)
    {
        // Set execution time limit for cron
        set_time_limit(300);
        
        if ($branch_id === null && !is_superadmin_loggedin()) {
            $branch_id = get_loggedin_branch_id();
        }
        
        if ($branch_id) {
            // Single branch
            $low_stock = $this->inventory_model->get_low_stock_products($branch_id);
            $this->_send_low_stock_alert_sms($low_stock, $branch_id);
            echo "Checked branch {$branch_id}: " . count($low_stock) . " low stock products\n";
        } else {
            // All branches (superadmin)
            $branches = $this->db->select('id')->where('status', 1)->get('branch')->result();
            foreach ($branches as $branch) {
                $low_stock = $this->inventory_model->get_low_stock_products($branch->id);
                if (!empty($low_stock)) {
                    $this->_send_low_stock_alert_sms($low_stock, $branch->id);
                    echo "Branch {$branch->id}: " . count($low_stock) . " low stock products - Alert sent\n";
                } else {
                    echo "Branch {$branch->id}: No low stock products\n";
                }
            }
        }
    }

          /**
     * Get low stock products for dashboard widget (AJAX)
     */
    public function get_low_stock_widget()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        // Get low stock and out of stock products
        $low_stock = $this->db->select('id, name, available_stock, reorder_point')
            ->from('product')
            ->where('branch_id', $branch_id)
            ->where('reorder_point >', 0)
            ->where('available_stock <=', 'reorder_point', false)
            ->where('available_stock >', 0)
            ->order_by('available_stock', 'ASC')
            ->limit(10)
            ->get()
            ->result_array();
        
        $out_of_stock = $this->db->select('id, name, available_stock, reorder_point')
            ->from('product')
            ->where('branch_id', $branch_id)
            ->where('reorder_point >', 0)
            ->where('available_stock', 0)
            ->order_by('name', 'ASC')
            ->limit(10)
            ->get()
            ->result_array();
        
        $total_low = count($low_stock);
        $total_out = count($out_of_stock);
        $total_alerts = $total_low + $total_out;
        
        $html = '';
        
        if ($total_alerts == 0) {
            $html .= '<div class="alert alert-success text-center" style="margin: 15px;">
                        <i class="fas fa-check-circle fa-2x"></i>
                        <h4>All Stock Levels Are Healthy</h4>
                        <p>No products need reordering at this time.</p>
                      </div>';
        } else {
            // Out of Stock Section
            if ($total_out > 0) {
                $html .= '<div class="panel-heading border-bottom" style="background:#f8d7da;">
                            <h4 class="panel-title text-danger">
                                <i class="fas fa-times-circle"></i> Out of Stock (' . $total_out . ')
                            </h4>
                          </div>';
                $html .= '<ul class="list-group">';
                foreach ($out_of_stock as $product) {
                    $html .= '<li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-8">
                                        <strong>' . htmlspecialchars($product['name']) . '</strong>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <span class="label label-danger">0 left</span>
                                    </div>
                                </div>
                              </li>';
                }
                $html .= '</ul>';
            }
            
            // Low Stock Section
            if ($total_low > 0) {
                $html .= '<div class="panel-heading border-bottom" style="background:#fff3cd;">
                            <h4 class="panel-title text-warning">
                                <i class="fas fa-exclamation-triangle"></i> Low Stock (' . $total_low . ')
                            </h4>
                          </div>';
                $html .= '<ul class="list-group">';
                foreach ($low_stock as $product) {
                    $percentage = round(($product['available_stock'] / $product['reorder_point']) * 100);
                    $html .= '<li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-8">
                                        <strong>' . htmlspecialchars($product['name']) . '</strong>
                                        <small class="text-muted d-block">Min: ' . $product['reorder_point'] . '</small>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <span class="label label-warning">' . $product['available_stock'] . ' left</span>
                                        <div class="progress" style="height: 4px; margin-top: 5px;">
                                            <div class="progress-bar progress-bar-warning" style="width: ' . $percentage . '%;"></div>
                                        </div>
                                    </div>
                                </div>
                              </li>';
                }
                $html .= '</ul>';
            }
            
            // View All Button
            $html .= '<div class="panel-footer">
                        <a href="' . base_url('inventory/stockreport') . '" class="btn btn-default btn-sm btn-block">
                            <i class="fas fa-chart-line"></i> View Full Stock Report
                        </a>
                      </div>';
        }
        
        echo $html;
    }
        /**
     * Stock Alerts Page - Shows low stock and out of stock products
     */
    public function stock_alerts()
    {
        if (!get_permission('inventory_report', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        // Get low stock products (available_stock > 0 but <= reorder_point)
        $low_stock = $this->db->select('p.id, p.name, p.code, p.available_stock, p.reorder_point, 
                                        pc.name as category_name, p.purchase_price, p.sales_price')
            ->from('product p')
            ->join('product_category pc', 'pc.id = p.category_id', 'left')
            ->where('p.branch_id', $branch_id)
            ->where('p.reorder_point >', 0)
            ->where('p.available_stock <=', 'p.reorder_point', false)
            ->where('p.available_stock >', 0)
            ->order_by('p.available_stock', 'ASC')
            ->get()
            ->result_array();
        
        // Get out of stock products (available_stock = 0)
        $out_of_stock = $this->db->select('p.id, p.name, p.code, p.available_stock, p.reorder_point, 
                                           pc.name as category_name, p.purchase_price, p.sales_price')
            ->from('product p')
            ->join('product_category pc', 'pc.id = p.category_id', 'left')
            ->where('p.branch_id', $branch_id)
            ->where('p.reorder_point >', 0)
            ->where('p.available_stock', 0)
            ->order_by('p.name', 'ASC')
            ->get()
            ->result_array();
        
        // Get healthy stock products (for overview)
        $healthy_stock = $this->db->select('COUNT(*) as count')
            ->from('product')
            ->where('branch_id', $branch_id)
            ->where('reorder_point >', 0)
            ->where('available_stock >', 'reorder_point', false)
            ->get()
            ->row()
            ->count;
        
        $this->data['low_stock'] = $low_stock;
        $this->data['out_of_stock'] = $out_of_stock;
        $this->data['healthy_count'] = $healthy_stock;
        $this->data['total_low'] = count($low_stock);
        $this->data['total_out'] = count($out_of_stock);
        $this->data['branch_id'] = $branch_id;
        $this->data['title'] = translate('stock_alerts');
        $this->data['sub_page'] = 'inventory/stock_alerts';
        $this->data['main_menu'] = 'inventory';
        
        $this->load->view('layout/index', $this->data);
    }
        /**
     * Product Report Page - Filterable product list with stock status
     */
    public function product_report()
    {
        if (!get_permission('inventory_report', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        // Get filter values
        $category_id = $this->input->get('category_id');
        $stock_filter = $this->input->get('stock_filter'); // all, low, out, healthy
        $search = $this->input->get('search');
        
        // Build query
        $this->db->select('p.id, p.name, p.code, p.available_stock, p.reorder_point, 
                          p.purchase_price, p.sales_price, p.created_at,
                          pc.name as category_name, pu.name as unit_name')
            ->from('product p')
            ->join('product_category pc', 'pc.id = p.category_id', 'left')
            ->join('product_unit pu', 'pu.id = p.sales_unit_id', 'left')
            ->where('p.branch_id', $branch_id);
        
        // Apply category filter
        if (!empty($category_id) && $category_id != 'all') {
            $this->db->where('p.category_id', $category_id);
        }
        
        // Apply stock status filter
        if ($stock_filter == 'low') {
            $this->db->where('p.reorder_point >', 0);
            $this->db->where('p.available_stock <=', 'p.reorder_point', false);
            $this->db->where('p.available_stock >', 0);
        } elseif ($stock_filter == 'out') {
            $this->db->where('p.reorder_point >', 0);
            $this->db->where('p.available_stock', 0);
        } elseif ($stock_filter == 'healthy') {
            $this->db->where('p.reorder_point >', 0);
            $this->db->where('p.available_stock >', 'p.reorder_point', false);
        }
        // 'all' shows everything, including products without reorder point
        
        // Apply search
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('p.name', $search);
            $this->db->or_like('p.code', $search);
            $this->db->group_end();
        }
        
        $this->db->order_by('p.name', 'ASC');
        $products = $this->db->get()->result_array();
        
        // Calculate stock status for each product
        foreach ($products as &$product) {
            if ($product['reorder_point'] > 0) {
                if ($product['available_stock'] <= 0) {
                    $product['stock_status'] = 'out';
                    $product['stock_status_text'] = 'Out of Stock';
                    $product['stock_status_class'] = 'danger';
                } elseif ($product['available_stock'] <= $product['reorder_point']) {
                    $product['stock_status'] = 'low';
                    $product['stock_status_text'] = 'Low Stock';
                    $product['stock_status_class'] = 'warning';
                } else {
                    $product['stock_status'] = 'healthy';
                    $product['stock_status_text'] = 'Healthy';
                    $product['stock_status_class'] = 'success';
                }
            } else {
                $product['stock_status'] = 'unknown';
                $product['stock_status_text'] = 'No Reorder Point';
                $product['stock_status_class'] = 'default';
            }
        }
        
        // Get categories for filter dropdown
        $categories = $this->db->select('id, name')
            ->where('branch_id', $branch_id)
            ->get('product_category')
            ->result_array();
        
        $this->data['products'] = $products;
        $this->data['categories'] = $categories;
        $this->data['category_id'] = $category_id;
        $this->data['stock_filter'] = $stock_filter;
        $this->data['search'] = $search;
        $this->data['branch_id'] = $branch_id;
        $this->data['title'] = translate('product_report');
        $this->data['sub_page'] = 'inventory/product_report';
        $this->data['main_menu'] = 'inventory_report';
        
        $this->load->view('layout/index', $this->data);
    }

/**
 * Uniform Stock Balance Report
 */
public function uniform_stock_report()
{
    if (!get_permission('inventory_report', 'is_view')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    // Use POST method like your working report
    if ($_POST) {
        $branch_id = $this->input->post('branch_id');
        $term_id = $this->input->post('term_id');
        
        if (!empty($branch_id) && !empty($term_id)) {
            $this->data['report_data'] = $this->inventory_model->get_uniform_stock_report($branch_id);
            $this->data['selected_term'] = $term_id;
            $this->data['search'] = 1;
        }
    }
    
    // Get terms for dropdown
    $this->data['terms'] = $this->db->select('id, name')->get('exam_term')->result();
    $this->data['branch_id'] = $branch_id;
    $this->data['title'] = translate('uniform_stock_balance_report');
    $this->data['sub_page'] = 'inventory/uniform_stock_report';
    $this->data['main_menu'] = 'inventory_report';
    
    $this->load->view('layout/index', $this->data);
}
/**
 * Fallback method to get uniform stock data directly from database
 */
private function get_uniform_stock_fallback($branch_id)
{
    // Get all uniform products with their variants and stock
    $products = $this->db->select('p.id, p.name, pv.variant_value as size, usb.quantity, usb.description')
        ->from('product p')
        ->join('product_variants pv', 'pv.product_id = p.id', 'left')
        ->join('uniform_stock_balances usb', 'usb.variant_id = pv.id AND usb.product_id = p.id', 'left')
        ->where('p.branch_id', $branch_id)
        ->where('p.product_type', 'uniform')
        ->order_by('p.name', 'ASC')
        ->order_by('pv.variant_value', 'ASC')
        ->get()
        ->result_array();
    
    // Separate SENIOR and JUNIOR based on naming convention
    $senior_items = array();
    $junior_items = array();
    $senior_counter = 1;
    $junior_counter = 1;
    $last_senior = '';
    $last_junior = '';
    
    foreach ($products as $product) {
        // Check if this is a senior or junior product based on name or code
        // You can customize this logic based on your actual data
        $is_senior = !in_array($product['name'], ['BLAZER', 'T-SHIRTS', 'TRUCK SUITS', 'TIES', 'POLO T-SHIRTS']);
        
        if ($is_senior) {
            if ($last_senior != $product['name']) {
                $senior_counter = 1;
                $last_senior = $product['name'];
            }
            $senior_items[] = array(
                'serial_no' => $senior_counter++,
                'product_name' => $product['name'],
                'description' => $product['description'] ?: '',
                'size' => $product['size'] ?: '',
                'quantity' => $product['quantity'] ?: 0
            );
        } else {
            if ($last_junior != $product['name']) {
                $junior_counter = 1;
                $last_junior = $product['name'];
            }
            $junior_items[] = array(
                'serial_no' => $junior_counter++,
                'product_name' => $product['name'],
                'description' => $product['description'] ?: '',
                'size' => $product['size'] ?: '',
                'quantity' => $product['quantity'] ?: 0
            );
        }
    }
    
    $report_data = array();
    if (!empty($senior_items)) {
        $report_data['SENIOR'] = $senior_items;
    }
    if (!empty($junior_items)) {
        $report_data['JUNIOR'] = $junior_items;
    }
    
    return $report_data;
}


public function weekly_consumption_report()
{
    if (!get_permission('inventory_report', 'is_view')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    // Initialize variables
    $report_data = array(
        'sections' => array(),
        'total_weeks' => 14,
        'term' => null
    );
    
    if ($_POST) {
        $branch_id = $this->input->post('branch_id');
        $term_id = $this->input->post('term_id');
        $year = $this->input->post('year') ?: date('Y');
        
        if (!empty($branch_id) && !empty($term_id)) {
            // Check if calculate button was clicked
            if ($this->input->post('calculate') == '1') {
                if (method_exists($this->inventory_model, 'calculate_all_weekly_consumption')) {
                    $this->inventory_model->calculate_all_weekly_consumption($branch_id, $term_id, $year);
                    set_alert('success', 'Weekly consumption calculated successfully');
                }
                redirect(current_url());
            }
            
            // Fetch report data
            if (method_exists($this->inventory_model, 'get_weekly_consumption_report')) {
                $report_data = $this->inventory_model->get_weekly_consumption_report($branch_id, $term_id, $year);
            }
            
            $this->data['selected_term'] = $term_id;
            $this->data['selected_year'] = $year;
            $this->data['search'] = 1;
        }
    }
    
    // Get terms for dropdown
    $this->data['terms'] = $this->db->select('id, name, total_weeks, is_active')
        ->order_by('is_active DESC, id DESC')
        ->get('exam_term')
        ->result();
    
    // Get years for dropdown
    $current_year = date('Y');
    $years = array();
    for ($i = $current_year - 2; $i <= $current_year + 1; $i++) {
        $years[$i] = $i;
    }
    
    $this->data['report_data'] = $report_data;
    $this->data['branch_id'] = $branch_id;
    $this->data['year_list'] = $years;
    $this->data['title'] = translate('weekly_stock_consumption_report');
    $this->data['sub_page'] = 'inventory/weekly_consumption_report';
    $this->data['main_menu'] = 'inventory_report';
    
    $this->load->view('layout/index', $this->data);
}
/**
 * Fallback method to get weekly consumption data directly from database
 */
private function get_weekly_consumption_fallback($branch_id, $term_id, $year)
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
    $stock_items = array();
    $spices_items = array();
    $cereals_items = array();
    $farm_items = array();
    
    foreach ($products as $product) {
        $section = 'STOCK';
        $sections[$section][] = array(
            'serial_no' => 1,
            'product_name' => $product['name'],
            'weekly_data' => array()
        );
    }
    
    return array(
        'sections' => $sections,
        'total_weeks' => $total_weeks,
        'term' => $term
    );
}

/**
 * Export Uniform Stock Report to Excel
 */
public function export_uniform_stock_excel()
{
    if (!get_permission('inventory_report', 'is_view')) {
        access_denied();
    }
    
    $this->load->library('excel');
    $branch_id = $this->application_model->get_branch_id();
    $report_data = $this->inventory_model->get_uniform_stock_report_dynamic($branch_id);
    $term = $this->db->select('name')->where('is_active', 1)->get('exam_term')->row();
    
    $objPHPExcel = new PHPExcel();
    $sheet = $objPHPExcel->getActiveSheet();
    
    // Set title
    $branch = $this->db->select('school_name')->where('id', $branch_id)->get('branch')->row();
    $sheet->setCellValue('B2', strtoupper($branch ? $branch->school_name : 'SCHOOL'));
    $sheet->setCellValue('B3', 'UNIFORMS STOCK BALANCE ' . date('Y'));
    $sheet->setCellValue('B4', ($term ? $term->name . ' AS AT ' : '') . date('dS F, Y'));
    
    // Start building the report
    $row = 7;
    $sections = array_keys($report_data);
    
    foreach ($sections as $index => $section) {
        // Section header
        $sheet->setCellValue('A' . $row, strtoupper($section));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row++;
        
        // Column headers
        $sheet->setCellValue('A' . $row, 'NO:');
        $sheet->setCellValue('B' . $row, 'ITEMS');
        $sheet->setCellValue('C' . $row, 'DESCRIPTION');
        $sheet->setCellValue('D' . $row, 'SIZE');
        $sheet->setCellValue('E' . $row, 'QUANTITY');
        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
        
        $row++;
        
        // Data rows
        foreach ($report_data[$section] as $item) {
            $sheet->setCellValue('A' . $row, $item['serial_no']);
            $sheet->setCellValue('B' . $row, $item['product_name']);
            $sheet->setCellValue('C' . $row, $item['description']);
            $sheet->setCellValue('D' . $row, $item['size']);
            $sheet->setCellValue('E' . $row, $item['quantity']);
            $row++;
        }
        
        $row += 2; // Add spacing between sections
    }
    
    // Auto-size columns
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $filename = 'uniform_stock_report_' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}

/**
 * Export Weekly Consumption Report to Excel
 */
public function export_weekly_consumption_excel()
{
    if (!get_permission('inventory_report', 'is_view')) {
        access_denied();
    }
    
    $this->load->library('excel');
    $branch_id = $this->application_model->get_branch_id();
    $term_id = $this->input->get('term_id');
    $year = $this->input->get('year') ?: date('Y');
    
    if (!$term_id) {
        show_error('Please select a term');
    }
    
    $report_data = $this->inventory_model->get_weekly_consumption_report_dynamic($branch_id, $term_id, $year);
    
    if (!$report_data || empty($report_data['sections'])) {
        show_error('No data available for the selected term');
    }
    
    $objPHPExcel = new PHPExcel();
    $sheet = $objPHPExcel->getActiveSheet();
    
    // Set title
    $branch = $this->db->select('school_name')->where('id', $branch_id)->get('branch')->row();
    $sheet->setCellValue('B2', strtoupper($branch ? $branch->school_name : 'SCHOOL'));
    $sheet->setCellValue('B3', ($report_data['term'] ? $report_data['term']->name . ' ' : '') . $year);
    $sheet->setCellValue('B4', 'STOCK CONSUMPTION REPORT');
    
    $row = 7;
    
    foreach ($report_data['sections'] as $section_name => $products) {
        // Section header
        $sheet->setCellValue('A' . $row, strtoupper($section_name));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        // Build week headers
        $sheet->setCellValue('A' . $row, 'NO');
        $sheet->setCellValue('B' . $row, 'ITEMS');
        for ($week = 1; $week <= $report_data['total_weeks']; $week++) {
            $sheet->setCellValue(chr(66 + $week) . $row, 'WEEK ' . $week);
        }
        $sheet->getStyle('A' . $row . ':' . chr(66 + $report_data['total_weeks']) . $row)->getFont()->setBold(true);
        $row++;
        
        // Data rows
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product['serial_no']);
            $sheet->setCellValue('B' . $row, $product['product_name']);
            
            for ($week = 1; $week <= $report_data['total_weeks']; $week++) {
                $value = isset($product['weekly_data'][$week]) ? $product['weekly_data'][$week] : '';
                $sheet->setCellValue(chr(66 + $week) . $row, $value);
            }
            $row++;
        }
        
        $row += 2; // Spacing between sections
    }
    
    // Auto-size columns
    foreach (range('A', chr(66 + $report_data['total_weeks'])) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $filename = 'weekly_consumption_report_' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}

/**
 * Manage Report Groups (Admin Panel)
 */
public function manage_report_groups()
{
    if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
        access_denied();
    }
    
    // Get branch ID - handle Superadmin branch selection
    $branch_id = $this->input->get('branch_id');
    if (empty($branch_id)) {
        $branch_id = $this->session->userdata('selected_branch');
    }
    if (empty($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
    }
    // For Superadmin with no branch selected, use first branch
    if (empty($branch_id) && is_superadmin_loggedin()) {
        $first_branch = $this->db->select('id')->get('branch')->row();
        $branch_id = $first_branch ? $first_branch->id : 1;
    }
    
    // Save selected branch to session
    if (!empty($branch_id)) {
        $this->session->set_userdata('selected_branch', $branch_id);
    }
    
    if ($_POST) {
        $this->form_validation->set_rules('group_name', 'Group Name', 'trim|required');
        $this->form_validation->set_rules('group_type', 'Group Type', 'trim|required');
        
        // Get branch_id from POST if Superadmin
        $post_branch_id = $this->input->post('branch_id');
        if (!empty($post_branch_id)) {
            $branch_id = $post_branch_id;
        }
        
        if ($this->form_validation->run()) {
            $data = array(
                'group_name' => $this->input->post('group_name'),
                'group_type' => $this->input->post('group_type'),
                'display_order' => $this->input->post('display_order') ?: 0,
                'branch_id' => $branch_id,
                'is_active' => 1
            );
            
            if ($this->input->post('group_id')) {
                $this->db->where('id', $this->input->post('group_id'))->update('report_groups', $data);
                set_alert('success', 'Report group updated successfully');
            } else {
                $this->db->insert('report_groups', $data);
                set_alert('success', 'Report group created successfully');
            }
        } else {
            set_alert('error', validation_errors());
        }
        redirect(base_url('inventory/manage_report_groups?branch_id=' . $branch_id));
    }
    
    // Get groups for current branch
    $this->data['groups'] = $this->db->order_by('group_type', 'ASC')
        ->order_by('display_order', 'ASC')
        ->get_where('report_groups', array('branch_id' => $branch_id))
        ->result();
    
    // Get branches for Superadmin dropdown
    if (is_superadmin_loggedin()) {
        $this->data['branches'] = $this->db->select('id, name')->where('status', 1)->get('branch')->result();
    }
    
    $this->data['branch_id'] = $branch_id;
    $this->data['title'] = translate('manage_report_groups');
    $this->data['sub_page'] = 'inventory/manage_report_groups';
    $this->data['main_menu'] = 'inventory_settings';
    $this->load->view('layout/index', $this->data);
}

/**
 * Delete Report Group
 */
public function delete_report_group($id)
{
    if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    
    // First delete product assignments
    $this->db->where('group_id', $id)->delete('product_report_groups');
    
    // Then delete the group
    $this->db->where('id', $id);
    if (!is_superadmin_loggedin()) {
        $this->db->where('branch_id', $branch_id);
    }
    $this->db->delete('report_groups');
    
    set_alert('success', 'Report group deleted successfully');
    redirect(base_url('inventory/manage_report_groups'));
}

/**
 * Assign Products to Report Groups
 */
public function assign_product_to_group()
{
    if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
        access_denied();
    }
    
    // Get branch ID - handle Superadmin branch selection
    $branch_id = $this->input->get('branch_id');
    if (empty($branch_id)) {
        $branch_id = $this->session->userdata('selected_branch');
    }
    if (empty($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
    }
    // For Superadmin with no branch selected, use first branch
    if (empty($branch_id) && is_superadmin_loggedin()) {
        $first_branch = $this->db->select('id')->get('branch')->row();
        $branch_id = $first_branch ? $first_branch->id : 1;
    }
    
    // Save selected branch to session
    if (!empty($branch_id)) {
        $this->session->set_userdata('selected_branch', $branch_id);
    }
    
    if ($_POST) {
        $product_id = $this->input->post('product_id');
        $group_ids = $this->input->post('group_ids') ?: array();
        $post_branch_id = $this->input->post('branch_id');
        
        if (!empty($post_branch_id)) {
            $branch_id = $post_branch_id;
        }
        
        // Delete existing assignments
        $this->db->where('product_id', $product_id)
            ->where('branch_id', $branch_id)
            ->delete('product_report_groups');
        
        // Insert new assignments
        foreach ($group_ids as $group_id) {
            $this->db->insert('product_report_groups', array(
                'product_id' => $product_id,
                'group_id' => $group_id,
                'branch_id' => $branch_id
            ));
        }
        
        set_alert('success', 'Product assignments saved successfully');
        redirect(base_url('inventory/assign_product_to_group?branch_id=' . $branch_id));
    }
    
    // Get products for current branch
    $this->data['uniform_products'] = $this->db->select('p.id, p.name, p.code')
        ->from('product p')
        ->where('p.branch_id', $branch_id)
        ->where('p.product_type', 'uniform')
        ->get()
        ->result();
    
    $this->data['consumable_products'] = $this->db->select('p.id, p.name, p.code')
        ->from('product p')
        ->where('p.branch_id', $branch_id)
        ->where('p.product_type', 'consumable')
        ->get()
        ->result();
    
    $this->data['uniform_groups'] = $this->db->get_where('report_groups', array(
        'branch_id' => $branch_id,
        'group_type' => 'uniform_section',
        'is_active' => 1
    ))->result();
    
    $this->data['consumable_groups'] = $this->db->get_where('report_groups', array(
        'branch_id' => $branch_id,
        'group_type' => 'consumable_section',
        'is_active' => 1
    ))->result();
    
    // Get existing assignments
    $assignments = $this->db->select('product_id, group_id')
        ->get_where('product_report_groups', array('branch_id' => $branch_id))
        ->result();
    
    $assigned = array();
    foreach ($assignments as $a) {
        $assigned[$a->product_id][] = $a->group_id;
    }
    $this->data['assigned_groups'] = $assigned;
    
    // Get branches for Superadmin dropdown
    if (is_superadmin_loggedin()) {
        $this->data['branches'] = $this->db->select('id, name')->where('status', 1)->get('branch')->result();
    }
    
    $this->data['branch_id'] = $branch_id;
    $this->data['title'] = translate('assign_products_to_report_groups');
    $this->data['sub_page'] = 'inventory/assign_product_group';
    $this->data['main_menu'] = 'inventory_settings';
    $this->load->view('layout/index', $this->data);
}
public function debug_branch()
{
    echo "<h2>Branch Debug</h2>";
    
    $branch_id = $this->application_model->get_branch_id();
    echo "get_branch_id() returns: " . ($branch_id ?: 'NULL') . "<br>";
    
    echo "<h3>Session Data:</h3>";
    echo "<pre>"; print_r($this->session->all_userdata()); echo "</pre>";
    
    echo "<h3>All Branches in Database:</h3>";
    $branches = $this->db->get('branch')->result_array();
    echo "<pre>"; print_r($branches); echo "</pre>";
    
    die();
}

}
