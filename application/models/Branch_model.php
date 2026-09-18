<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Branch_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function save($data)
{
    $arrayBranch = array(
        'name' => $data['branch_name'],
        'school_name' => $data['school_name'],
        'email' => $data['email'],
        'mobileno' => $data['mobileno'],
        'currency' => $data['currency'],
        'symbol' => $data['currency_symbol'],
        'city' => $data['city'],
        'state' => $data['state'],
        'address' => $data['address'],
    );
    
    if (!isset($data['branch_id'])) {
        $this->db->insert('branch', $arrayBranch);
        $id = $this->db->insert_id();
        
        // ===== NEW: Create trial subscription =====
        $this->create_trial_subscription($id);
        // ==========================================
        
    } else {
        $id = $data['branch_id'];
        $this->db->where('id', $data['branch_id']);
        $this->db->update('branch', $arrayBranch);
    }

        $file_upload = false;
        if (isset($_FILES["logo_file"]) && !empty($_FILES['logo_file']['name'])) {
            $fileInfo = pathinfo($_FILES["logo_file"]["name"]);
            $img_name = $id . '.' . $fileInfo['extension'];
            move_uploaded_file($_FILES["logo_file"]["tmp_name"], "uploads/app_image/logo-" . $img_name);
            $file_upload = true;
        }
        if (isset($_FILES["text_logo"]) && !empty($_FILES['text_logo']['name'])) {
            $fileInfo = pathinfo($_FILES["text_logo"]["name"]);
            $img_name = $id . '.' . $fileInfo['extension'];
            move_uploaded_file($_FILES["text_logo"]["tmp_name"], "uploads/app_image/logo-small-" . $img_name);
            $file_upload = true;
        }

        if (isset($_FILES["print_file"]) && !empty($_FILES['print_file']['name'])) {
            $fileInfo = pathinfo($_FILES["print_file"]["name"]);
            $img_name = $id . '.' . $fileInfo['extension'];
            move_uploaded_file($_FILES["print_file"]["tmp_name"], "uploads/app_image/printing-logo-" . $img_name);
            $file_upload = true;
        }

        if (isset($_FILES["report_card"]) && !empty($_FILES['report_card']['name'])) {
            $fileInfo = pathinfo($_FILES["report_card"]["name"]);
            $img_name = $id . '.' . $fileInfo['extension'];
            move_uploaded_file($_FILES["report_card"]["tmp_name"], "uploads/app_image/report-card-logo-" . $img_name);
            $file_upload = true;
        }

        if ($this->db->affected_rows() > 0 || $file_upload == true) {
            return true;
        } else {
            return false;
        }
    }

   /**
 * Create trial subscription for new branch
 */
public function create_trial_subscription($branch_id) {
    $this->load->model('subscription_model');
    
    // Get trial plan (create if doesn't exist)
    $this->db->where('name', 'Trial');
    $trial_plan = $this->db->get('subscription_plans')->row();
    
    if (!$trial_plan) {
        $trial_plan_id = $this->create_default_trial_plan();
    } else {
        $trial_plan_id = $trial_plan->id;
    }
    
    $trial_end = date('Y-m-d', strtotime('+14 days'));
    
    // Create trial subscription
    $subscription_data = [
        'branch_id' => $branch_id,
        'plan_id' => $trial_plan_id,
        'start_date' => date('Y-m-d'),
        'end_date' => $trial_end,
        'status' => 'trial',
        'payment_method' => 'trial',
        'payment_status' => 'trial',
        'amount_paid' => 0,
        'billing_cycle' => 'monthly',
        'auto_renew' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $this->db->insert('branch_subscriptions', $subscription_data);
    $subscription_id = $this->db->insert_id();
    
    // Update branch with subscription ID
    $this->db->where('id', $branch_id);
    $this->db->update('branch', [
        'subscription_id' => $subscription_id,
        'subscription_status' => 'trial',
        'trial_start_date' => date('Y-m-d'),
        'trial_end_date' => $trial_end
    ]);
    
    // Enable basic trial modules
    $this->enable_trial_modules($branch_id);
    
    // ===== NEW: Copy SMS settings from default branch =====
    $this->copy_sms_settings($branch_id);
    
    return $subscription_id;
}
/**
 * Copy SMS settings from default branch (branch 1) - FIXED for your table structure
 */
private function copy_sms_settings($new_branch_id) {
    // Get all SMS settings from branch 1
    $this->db->where('branch_id', 1);
    $sms_settings = $this->db->get('sms_credential')->result_array();
    
    if (empty($sms_settings)) {
        // log_message('info', "No SMS settings found for branch 1 to copy");
        return;
    }
    
    foreach ($sms_settings as $setting) {
        // Check if entry already exists for this branch with same sms_api_id
        $this->db->where('branch_id', $new_branch_id);
        $this->db->where('sms_api_id', $setting['sms_api_id']);
        $exists = $this->db->get('sms_credential')->row();
        
        if (!$exists) {
            // Prepare insert data for new branch
            $insert_data = [
                'branch_id' => $new_branch_id,
                'sms_api_id' => $setting['sms_api_id'],
                'field_one' => $setting['field_one'],
                'field_two' => $setting['field_two'],
                'field_three' => $setting['field_three'],
                'field_four' => $setting['field_four'],
                'is_active' => $setting['is_active'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('sms_credential', $insert_data);
            // log_message('info', "SMS settings copied for sms_api_id {$setting['sms_api_id']} to branch {$new_branch_id}");
        }
    }
    
    // log_message('info', "SMS settings copy completed for branch {$new_branch_id}");
}

/**
 * Enable basic modules for trial - FIXED to ensure ALL trial modules exist
 */
private function enable_trial_modules($branch_id) {
    // Define trial modules
    $trial_modules = [
        'dashboard', 
        'student', 
        'academic', 
        'attendance', 
        'homework',
        'bulk_sms_and_email'  // ✅ Ensure SMS is included
    ];
    
    // Get ALL trial module IDs
    $this->db->where_in('prefix', $trial_modules);
    $trial_modules_data = $this->db->get('permission_modules')->result();
    
    // For EACH trial module, ensure entry exists in modules_manage
    foreach ($trial_modules_data as $module) {
        // Check if entry exists
        $this->db->where('branch_id', $branch_id);
        $this->db->where('modules_id', $module->id);
        $exists = $this->db->get('modules_manage')->row();
        
        if ($exists) {
            // Update existing entry to enabled
            $this->db->where('id', $exists->id);
            $this->db->update('modules_manage', ['isEnabled' => 1]);
        } else {
            // Create new entry
            $this->db->insert('modules_manage', [
                'branch_id' => $branch_id,
                'modules_id' => $module->id,
                'isEnabled' => 1
            ]);
        }
        
        // log_message('info', "Enabled module: {$module->prefix} for branch {$branch_id}");
    }
    
    // Disable ALL other modules (ones not in trial)
    $this->db->where('branch_id', $branch_id);
    $this->db->where_not_in('modules_id', array_column($trial_modules_data, 'id'));
    $this->db->update('modules_manage', ['isEnabled' => 0]);
    
    // Log final state
    $this->db->where('branch_id', $branch_id);
    $this->db->where('isEnabled', 1);
    $enabled_count = $this->db->count_all_results('modules_manage');
    // log_message('info', "Branch {$branch_id} has {$enabled_count} enabled trial modules");
}
    /**
     * Create default trial plan if it doesn't exist
     */
    private function create_default_trial_plan() {
        // Check if trial plan exists
        $this->db->where('name', 'Trial');
        $exists = $this->db->get('subscription_plans')->row();
        
        if ($exists) {
            return $exists->id;
        }
        
        // Get basic module IDs
        $this->db->where_in('prefix', ['dashboard', 'student', 'academic', 'attendance', 'homework']);
        $modules = $this->db->get('permission_modules')->result();
        $module_ids = array_column($modules, 'id');
        
        // Create trial plan
        $plan_data = [
            'name' => 'Trial',
            'description' => '14-day free trial with basic features',
            'price_monthly' => 0,
            'price_termly' => 0,
            'price_yearly' => 0,
            'max_branches' => 1,
            'max_students' => 50,
            'max_staff' => 10,
            'max_sms_units' => 50,
            'trial_days' => 14,
            'is_active' => 1,
            'sort_order' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('subscription_plans', $plan_data);
        $plan_id = $this->db->insert_id();
        
        // Assign modules
        foreach ($module_ids as $module_id) {
            $this->db->insert('subscription_plan_modules', [
                'plan_id' => $plan_id,
                'module_id' => $module_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $plan_id;
    }
}
