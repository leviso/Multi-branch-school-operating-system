<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Subscription_admin extends Admin_Controller
{
    protected $requires_subscription = false; // Superadmin only
    
    public function __construct()
    {
        parent::__construct();
        
        // Ensure only superadmin can access
        if (!is_superadmin_loggedin()) {
            access_denied();
        }
        
        $this->load->model('subscription_model');
    }

    /**
     * Plans management page
     */
    public function plans()
    {
        $this->data['plans'] = $this->subscription_model->get_plans(false, true);
        $this->data['modules'] = $this->db->get('permission_modules')->result();
        
        $this->data['title'] = translate('subscription_plans');
        $this->data['sub_page'] = 'subscription/plans';
        $this->data['main_menu'] = 'subscription';
        $this->load->view('layout/index', $this->data);
    }

    /**
     * Add/Edit plan form
     */
    public function plan_form($id = null)
    {
        if ($this->input->post('submit') == 'save') {
            $this->form_validation->set_rules('name', translate('plan_name'), 'required');
            $this->form_validation->set_rules('price_monthly', translate('monthly_price'), 'required|numeric');
            $this->form_validation->set_rules('price_termly', translate('termly_price'), 'numeric');
            $this->form_validation->set_rules('price_yearly', translate('yearly_price'), 'numeric');
            $this->form_validation->set_rules('max_students', translate('max_students'), 'integer');
            $this->form_validation->set_rules('max_staff', translate('max_staff'), 'integer');
            $this->form_validation->set_rules('max_sms_units', translate('max_sms_units'), 'integer');
            $this->form_validation->set_rules('modules[]', translate('modules'), 'required');

            if ($this->form_validation->run() == true) {
                $post = $this->input->post();
                $result = $this->subscription_model->save_plan($post, $id);
                
                if ($result) {
                    set_alert('success', $id ? translate('information_has_been_updated_successfully') : translate('information_has_been_saved_successfully'));
                    redirect(base_url('subscription_admin/plans'));
                } else {
                    set_alert('error', 'Failed to save plan');
                }
            }
        }

        $this->data['plan'] = $id ? $this->subscription_model->get_plan($id, true) : null;
        $this->data['modules'] = $this->db->order_by('sort_order', 'ASC')->get('permission_modules')->result();
        $this->data['title'] = $id ? translate('edit_plan') : translate('add_plan');
        $this->data['sub_page'] = 'subscription/plan_form';
        $this->data['main_menu'] = 'subscription';
        $this->load->view('layout/index', $this->data);
    }

    /**
     * Delete plan
     */
    public function plan_delete($id)
    {
        if (!is_superadmin_loggedin()) {
            access_denied();
        }
        
        $result = $this->subscription_model->delete_plan($id);
        
        if ($result) {
            set_alert('success', translate('information_deleted_successfully'));
        } else {
            set_alert('error', 'Plan is in use and cannot be deleted');
        }
        
        redirect(base_url('subscription_admin/plans'));
    }

    /**
     * Branch subscriptions overview
     */
    public function branch_subscriptions()
    {
        $branch_id = $this->input->get('branch_id');
        
        $this->data['branches'] = $this->db->select('id, name, school_name')->order_by('name', 'ASC')->get('branch')->result();
        $this->data['plans'] = $this->subscription_model->get_plans();
        $this->data['selected_branch'] = $branch_id;
        
        if ($branch_id) {
            $this->data['current_subscription'] = $this->subscription_model->get_branch_subscription($branch_id);
            $this->data['subscription_history'] = $this->subscription_model->get_branch_subscription_history($branch_id, 20);
            $this->data['branch_info'] = $this->db->get_where('branch', ['id' => $branch_id])->row();
        } else {
            $this->data['current_subscription'] = null;
            $this->data['subscription_history'] = [];
            $this->data['branch_info'] = null;
        }
        
        $this->data['title'] = translate('branch_subscriptions');
        $this->data['sub_page'] = 'subscription/branch_assign';
        $this->data['main_menu'] = 'subscription';
        $this->load->view('layout/index', $this->data);
    }

    /**
     * Assign subscription to branch (AJAX)
     */
    public function assign_subscription()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $this->output->set_content_type('application/json');
        
        if (!is_superadmin_loggedin()) {
            echo json_encode(['success' => false, 'message' => translate('access_denied')]);
            return;
        }

        $branch_id = $this->input->post('branch_id');
        $plan_id = $this->input->post('plan_id');
        $billing_cycle = $this->input->post('billing_cycle') ?: 'monthly';
        $start_date = $this->input->post('start_date') ?: date('Y-m-d');

        if (empty($branch_id) || empty($plan_id)) {
            echo json_encode(['success' => false, 'message' => 'Branch and plan are required']);
            return;
        }

        $result = $this->subscription_model->assign_subscription($branch_id, $plan_id, $billing_cycle, $start_date);

        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Subscription assigned successfully',
                'subscription_id' => $result
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to assign subscription']);
        }
    }

    /**
     * Revenue report page
     */
    public function revenue()
    {
        $year = $this->input->get('year') ?: date('Y');
        
        $this->data['revenue'] = $this->subscription_model->get_revenue_report($year);
        $this->data['stats'] = $this->subscription_model->get_statistics();
        $this->data['year'] = $year;
        
        $this->data['title'] = translate('subscription_revenue');
        $this->data['sub_page'] = 'subscription/revenue';
        $this->data['main_menu'] = 'subscription';
        $this->load->view('layout/index', $this->data);
    }

    /**
     * Usage reports page
     */
    public function usage()
    {
        $branch_id = $this->input->get('branch_id');
        $month = $this->input->get('month') ?: date('Y-m');
        
        $this->data['branches'] = $this->db->select('id, name')->order_by('name', 'ASC')->get('branch')->result();
        $this->data['selected_branch'] = $branch_id;
        $this->data['selected_month'] = $month;
        
        if ($branch_id) {
            $this->data['usage'] = $this->subscription_model->get_usage_summary($branch_id, $month);
            $this->data['branch_info'] = $this->db->get_where('branch', ['id' => $branch_id])->row();
        } else {
            $this->data['usage'] = [];
            $this->data['branch_info'] = null;
        }
        
        $this->data['title'] = translate('usage_reports');
        $this->data['sub_page'] = 'subscription/usage';
        $this->data['main_menu'] = 'subscription';
        $this->load->view('layout/index', $this->data);
    }
    /**
 * One-time setup for trial plan - Accessible only to superadmin
 */
public function setup_trial_plan()
{
    // Only superadmin can run this
    if (!is_superadmin_loggedin()) {
        access_denied();
    }
    
    // Check if already exists
    $this->db->where('name', 'Trial');
    $exists = $this->db->get('subscription_plans')->row();
    
    if ($exists) {
        $this->session->set_flashdata('error', 'Trial plan already exists!');
        redirect(base_url('subscription_admin/plans'));
    }
    
    // Create trial plan
    $plan_id = $this->subscription_model->create_default_trial_plan();
    
    if ($plan_id) {
        $this->session->set_flashdata('success', 'Trial plan created successfully!');
    } else {
        $this->session->set_flashdata('error', 'Failed to create trial plan');
    }
    
    redirect(base_url('subscription_admin/plans'));
}
}