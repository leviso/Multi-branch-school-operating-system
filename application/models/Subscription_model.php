<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_model extends CI_Model
{
    private $modules_cache = [];
    
    public function __construct()
    {
        parent::__construct();
        $this->load->library('payment_handler');
    }

    /**
     * ============================================
     * PLAN MANAGEMENT METHODS
     * ============================================
     */

    /**
     * Get all subscription plans
     */
    public function get_plans($active_only = true, $include_modules = false)
    {
        $this->db->select('sp.*');
        $this->db->from('subscription_plans sp');
        
        if ($active_only) {
            $this->db->where('sp.is_active', 1);
        }
        
        $this->db->order_by('sp.sort_order', 'ASC');
        $this->db->order_by('sp.price_monthly', 'ASC');
        
        $query = $this->db->get();
        $plans = $query->result();
        
        if ($include_modules && !empty($plans)) {
            foreach ($plans as &$plan) {
                $plan->modules = $this->get_plan_modules($plan->id);
                $plan->module_ids = array_column($plan->modules, 'id');
            }
        }
        
        return $plans;
    }

    /**
     * Get single plan by ID
     */
    public function get_plan($id, $include_modules = true)
    {
        $this->db->where('id', $id);
        $plan = $this->db->get('subscription_plans')->row();
        
        if ($plan && $include_modules) {
            $plan->modules = $this->get_plan_modules($id);
            $plan->module_ids = array_column($plan->modules, 'id');
        }
        
        return $plan;
    }

    /**
     * Get modules assigned to a plan
     */
    public function get_plan_modules($plan_id)
    {
        $this->db->select('pm.*, spm.plan_id');
        $this->db->from('subscription_plan_modules spm');
        $this->db->join('permission_modules pm', 'pm.id = spm.module_id');
        $this->db->where('spm.plan_id', $plan_id);
        
        return $this->db->get()->result();
    }

    /**
     * Save or update plan
     */
    public function save_plan($data, $plan_id = null)
    {
        $this->db->trans_start();
        
        try {
            $plan_data = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'price_monthly' => $data['price_monthly'] ?? 0,
                'price_termly' => $data['price_termly'] ?? 0,
                'price_yearly' => $data['price_yearly'] ?? 0,
                'term_months' => $data['term_months'] ?? 3,
                'max_branches' => $data['max_branches'] ?? 1,
                'max_students' => $data['max_students'] ?? 0,
                'max_staff' => $data['max_staff'] ?? 0,
                'max_sms_units' => $data['max_sms_units'] ?? 0,
                'trial_days' => $data['trial_days'] ?? 0,
                'is_active' => isset($data['is_active']) ? 1 : 0,
                'sort_order' => $data['sort_order'] ?? 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (empty($plan_id)) {
                $plan_data['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('subscription_plans', $plan_data);
                $plan_id = $this->db->insert_id();
                
                $this->log_action('plan_created', "Created plan: {$data['name']}", null, $plan_data);
            } else {
                $this->db->where('id', $plan_id);
                $this->db->update('subscription_plans', $plan_data);
                
                $this->log_action('plan_updated', "Updated plan ID: {$plan_id}", null, $plan_data);
            }
            
            // Update plan modules
            if (isset($data['modules']) && is_array($data['modules'])) {
                $this->update_plan_modules($plan_id, $data['modules']);
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            return $plan_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            // log_message('error', 'Save plan failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update plan modules
     */
    private function update_plan_modules($plan_id, $module_ids)
    {
        // Delete existing modules
        $this->db->where('plan_id', $plan_id);
        $this->db->delete('subscription_plan_modules');
        
        // Insert new modules
        if (!empty($module_ids)) {
            foreach ($module_ids as $module_id) {
                $this->db->insert('subscription_plan_modules', [
                    'plan_id' => $plan_id,
                    'module_id' => $module_id,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    /**
     * Delete plan (soft delete or hard delete)
     */
    public function delete_plan($plan_id, $hard_delete = false)
    {
        // Check if plan is in use
        $this->db->where('plan_id', $plan_id);
        $in_use = $this->db->count_all_results('branch_subscriptions');
        
        if ($in_use > 0) {
            // Soft delete - just deactivate
            $this->db->where('id', $plan_id);
            return $this->db->update('subscription_plans', [
                'is_active' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        if ($hard_delete) {
            // Hard delete if not in use
            $this->db->where('plan_id', $plan_id);
            $this->db->delete('subscription_plan_modules');
            
            $this->db->where('id', $plan_id);
            return $this->db->delete('subscription_plans');
        }
        
        return false;
    }

    /**
     * ============================================
     * BRANCH SUBSCRIPTION METHODS
     * ============================================
     */

    /**
     * Get branch current active subscription
     */
    public function get_branch_subscription($branch_id)
    {
        $this->db->select('bs.*, sp.name as plan_name, sp.price_monthly, sp.price_termly, sp.price_yearly, sp.max_sms_units, sp.billing_cycle as plan_billing_cycle');
        $this->db->from('branch_subscriptions bs');
        $this->db->join('subscription_plans sp', 'sp.id = bs.plan_id');
        $this->db->where('bs.branch_id', $branch_id);
        $this->db->where('bs.status', 'active');
        $this->db->order_by('bs.id', 'DESC');
        $this->db->limit(1);
        
        $query = $this->db->get();
        return $query->row();
    }

    /**
     * Get branch subscription history
     */
    public function get_branch_subscription_history($branch_id, $limit = 10)
    {
        $this->db->select('bs.*, sp.name as plan_name');
        $this->db->from('branch_subscriptions bs');
        $this->db->join('subscription_plans sp', 'sp.id = bs.plan_id');
        $this->db->where('bs.branch_id', $branch_id);
        $this->db->order_by('bs.created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    /**
     * Assign subscription to branch (admin only)
     */
    public function assign_subscription($branch_id, $plan_id, $billing_cycle = 'monthly', $start_date = null)
    {
        $this->db->trans_start();
        
        try {
            $plan = $this->get_plan($plan_id);
            if (!$plan) {
                throw new Exception('Invalid plan selected');
            }
            
            $start_date = $start_date ?: date('Y-m-d');
            $end_date = $this->calculate_end_date($start_date, $billing_cycle, $plan->term_months);
            
            // Deactivate current active subscription
            $this->db->where('branch_id', $branch_id);
            $this->db->where('status', 'active');
            $this->db->update('branch_subscriptions', [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create new subscription
            $subscription_data = [
                'branch_id' => $branch_id,
                'plan_id' => $plan_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'next_billing_date' => $end_date,
                'status' => 'active',
                'payment_method' => 'admin',
                'payment_status' => 'paid',
                'amount_paid' => $this->get_plan_price($plan, $billing_cycle),
                'billing_cycle' => $billing_cycle,
                'auto_renew' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('branch_subscriptions', $subscription_data);
            $subscription_id = $this->db->insert_id();
            
            // Update branch
            $this->db->where('id', $branch_id);
            $this->db->update('branch', [
                'subscription_id' => $subscription_id,
                'subscription_status' => 'active',
                'subscription_updated_at' => date('Y-m-d H:i:s'),
                'trial_start_date' => null,
                'trial_end_date' => null
            ]);
            
            // Initialize SMS credits based on plan
            $this->initialize_sms_credits($branch_id, $plan_id, $billing_cycle);
            
            // Enable modules according to plan
            $this->update_branch_modules($branch_id, $plan_id);
            
            $this->log_action('subscription_assigned', "Assigned plan ID {$plan_id} to branch {$branch_id}", null, $subscription_data);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            return $subscription_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            // log_message('error', 'Assign subscription failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Initialize pending subscription after M-Pesa payment
     */
    public function create_pending_subscription($branch_id, $plan_id, $billing_cycle, $amount, $checkout_id)
    {
        $plan = $this->get_plan($plan_id);
        if (!$plan) {
            return false;
        }
        
        $start_date = date('Y-m-d');
        $end_date = $this->calculate_end_date($start_date, $billing_cycle, $plan->term_months);
        
        $subscription_data = [
            'branch_id' => $branch_id,
            'plan_id' => $plan_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => 'pending',
            'payment_method' => 'mpesa',
            'payment_status' => 'unpaid',
            'amount_paid' => $amount,
            'checkout_request_id' => $checkout_id,
            'billing_cycle' => $billing_cycle,
            'auto_renew' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('branch_subscriptions', $subscription_data);
        return $this->db->insert_id();
    }

  /**
 * Activate subscription after successful payment
 */
public function activate_subscription($checkout_id, $mpesa_receipt, $callback_data)
{
    // log_message('info', "activate_subscription called - Checkout: {$checkout_id}, Receipt: {$mpesa_receipt}");
    
    $this->db->trans_start();
    
    try {
        // Find pending subscription
        $this->db->where('checkout_request_id', $checkout_id);
        $this->db->where('status', 'pending');
        $subscription = $this->db->get('branch_subscriptions')->row();
        
        if (!$subscription) {
            // log_message('error', 'Pending subscription not found for checkout: ' . $checkout_id);
            throw new Exception('Pending subscription not found');
        }
        
        // log_message('info', "Found subscription ID: {$subscription->id} for branch: {$subscription->branch_id}");
        
        // ✅ STEP 1: Create payment record FIRST (before updating subscription)
        $this->db->where('checkout_request_id', $checkout_id);
        $existing_payment = $this->db->get('subscription_payments')->row();
        
        if (!$existing_payment) {
            $payment_data = [
                'branch_id' => $subscription->branch_id,
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'amount' => $subscription->amount_paid,
                'payment_method' => 'mpesa',
                'mpesa_receipt' => $mpesa_receipt,
                'checkout_request_id' => $checkout_id,
                'payment_date' => date('Y-m-d H:i:s'),
                'status' => 'completed',
                'raw_callback_data' => $callback_data,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('subscription_payments', $payment_data);
            // log_message('info', "✅ Payment record created with ID: " . $this->db->insert_id());
        } else {
            $this->db->where('id', $existing_payment->id);
            $this->db->update('subscription_payments', [
                'status' => 'completed',
                'mpesa_receipt' => $mpesa_receipt,
                'raw_callback_data' => $callback_data,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            // log_message('info', "✅ Payment record updated for ID: {$existing_payment->id}");
        }
        
        // ✅ STEP 2: Update subscription to active
        $this->db->where('id', $subscription->id);
        $this->db->update('branch_subscriptions', [
            'status' => 'active',
            'payment_status' => 'paid',
            'mpesa_receipt' => $mpesa_receipt,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // ✅ STEP 3: Update branch
        $this->db->where('id', $subscription->branch_id);
        $this->db->update('branch', [
            'subscription_id' => $subscription->id,
            'subscription_status' => 'active',
            'subscription_updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // ✅ STEP 4: Initialize SMS credits
        $this->initialize_sms_credits($subscription->branch_id, $subscription->plan_id, $subscription->billing_cycle);
        
        // ✅ STEP 5: Enable modules according to plan
        $this->update_branch_modules($subscription->branch_id, $subscription->plan_id);
        
        // ✅ STEP 6: Clear cache
        $this->clear_branch_cache($subscription->branch_id);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        // log_message('info', "✅ Subscription {$subscription->id} activated successfully");
        return $subscription->id;
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        // log_message('error', '❌ activate_subscription failed: ' . $e->getMessage());
        return false;
    }
}


    /**
     * Fail subscription payment
     */
    public function fail_subscription_payment($checkout_id, $callback_data)
    {
        $this->db->where('checkout_request_id', $checkout_id);
        $this->db->update('branch_subscriptions', [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->log_action('payment_failed', "Payment failed for checkout ID {$checkout_id}", null, ['callback' => $callback_data]);
    }

    /**
     * ============================================
     * UPGRADE/DOWNGRADE FLOW
     * ============================================
     */

    /**
     * Change subscription plan (upgrade/downgrade)
     */
    public function change_plan($branch_id, $new_plan_id, $billing_cycle = null, $prorate = true)
    {
        $this->db->trans_start();
        
        try {
            $current_sub = $this->get_branch_subscription($branch_id);
            $new_plan = $this->get_plan($new_plan_id);
            
            if (!$current_sub || !$new_plan) {
                throw new Exception('Invalid subscription or plan');
            }
            
            $old_plan = $this->get_plan($current_sub->plan_id);
            
            // Calculate dates
            $start_date = date('Y-m-d');
            $billing_cycle = $billing_cycle ?: $current_sub->billing_cycle;
            $end_date = $this->calculate_end_date($start_date, $billing_cycle, $new_plan->term_months);
            
            // Calculate prorated amount if applicable
            $amount = $this->get_plan_price($new_plan, $billing_cycle);
            $prorated_amount = $amount;
            
            if ($prorate && $current_sub->payment_status == 'paid') {
                $prorated_amount = $this->calculate_prorated_amount($current_sub, $new_plan, $billing_cycle);
            }
            
            // Expire current subscription
            $this->db->where('id', $current_sub->id);
            $this->db->update('branch_subscriptions', [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create new subscription
            $new_sub_data = [
                'branch_id' => $branch_id,
                'plan_id' => $new_plan_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'next_billing_date' => $end_date,
                'status' => 'active',
                'payment_method' => $current_sub->payment_method,
                'payment_status' => $prorated_amount > 0 ? 'unpaid' : 'paid',
                'amount_paid' => $prorated_amount,
                'billing_cycle' => $billing_cycle,
                'auto_renew' => 1,
                'notes' => "Changed from plan ID {$current_sub->plan_id}",
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('branch_subscriptions', $new_sub_data);
            $new_sub_id = $this->db->insert_id();
            
            // Update branch
            $this->db->where('id', $branch_id);
            $this->db->update('branch', [
                'subscription_id' => $new_sub_id,
                'subscription_status' => 'active',
                'subscription_updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update SMS credits based on new plan
            $this->adjust_sms_credits_for_plan_change($branch_id, $old_plan, $new_plan, $current_sub);
            
            // Update modules
            $this->update_branch_modules($branch_id, $new_plan_id);
            
            $log_data = [
                'old_plan_id' => $current_sub->plan_id,
                'new_plan_id' => $new_plan_id,
                'old_sub_id' => $current_sub->id,
                'new_sub_id' => $new_sub_id,
                'prorated_amount' => $prorated_amount
            ];
            
            $this->log_action('plan_changed', "Changed branch {$branch_id} from plan {$current_sub->plan_id} to {$new_plan_id}", null, $log_data);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            return [
                'subscription_id' => $new_sub_id,
                'amount_due' => $prorated_amount,
                'old_plan' => $old_plan,
                'new_plan' => $new_plan
            ];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            // log_message('error', 'Change plan failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate prorated amount for plan change
     */
    private function calculate_prorated_amount($current_sub, $new_plan, $new_billing_cycle)
    {
        $days_remaining = max(0, (strtotime($current_sub->end_date) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
        $total_days = (strtotime($current_sub->end_date) - strtotime($current_sub->start_date)) / (60 * 60 * 24);
        
        $old_daily_rate = $current_sub->amount_paid / $total_days;
        $new_daily_rate = $this->get_plan_price($new_plan, $new_billing_cycle) / $total_days;
        
        $credit = $old_daily_rate * $days_remaining;
        $new_cost = $new_daily_rate * $days_remaining;
        
        return max(0, $new_cost - $credit);
    }

    /**
     * ============================================
     * MODULE ACCESS CONTROL
     * ============================================
     */

  /**
 * Check if branch has active subscription (including trial)
 */
public function has_active_subscription($branch_id)
{
    // Superadmin always has access
    if (is_superadmin_loggedin()) {
        return true;
    }
    
    // Check for active OR trial subscription
    $sql = "SELECT id FROM branch_subscriptions 
            WHERE branch_id = ? 
            AND (status = 'active' OR status = 'trial')
            AND end_date >= CURDATE()
            LIMIT 1";
    
    $subscription = $this->db->query($sql, [$branch_id])->row();
    
    return !empty($subscription);
}

  /**
 * Check if module is accessible for branch - ENHANCED VERSION
 */
public function can_access_module($branch_id, $module_prefix)
{
    // Superadmin always has access
    if (is_superadmin_loggedin()) {
        return true;
    }
    
    // log_message('debug', "can_access_module: Checking {$module_prefix} for branch {$branch_id}");
    
    // Step 1: Check if branch has active or trial subscription
    $sql = "SELECT id, status, end_date FROM branch_subscriptions 
            WHERE branch_id = ? 
            AND (status = 'active' OR status = 'trial')
            AND end_date >= CURDATE()
            ORDER BY id DESC
            LIMIT 1";
    
    $subscription = $this->db->query($sql, [$branch_id])->row();
    
    if (!$subscription) {
        // log_message('debug', "can_access_module: No active/trial subscription for branch {$branch_id}");
        return false;
    }
    
    // log_message('debug', "can_access_module: Subscription found - Status: {$subscription->status}, End: {$subscription->end_date}");
    
    // Step 2: Get module ID from prefix
    $this->db->select('id');
    $this->db->where('prefix', $module_prefix);
    $module = $this->db->get('permission_modules')->row();
    
    if (!$module) {
        // log_message('debug', "can_access_module: Module prefix '{$module_prefix}' not found in permission_modules table");
        return false;
    }
    
    // log_message('debug', "can_access_module: Module ID found: {$module->id}");
    
    // Step 3: DIRECT check in modules_manage table
    $this->db->where('branch_id', $branch_id);
    $this->db->where('modules_id', $module->id);
    $this->db->where('isEnabled', 1);
    $enabled = $this->db->get('modules_manage')->row();
    
    $result = !empty($enabled);
    // log_message('debug', "can_access_module: Branch {$branch_id}, Module {$module_prefix} ({$module->id}) = " . ($result ? 'ENABLED ✓' : 'DISABLED ✗'));
    
    return $result;
}

    /**
     * Get all accessible modules for branch
     */
    public function get_accessible_modules($branch_id)
    {
        if (isset($this->modules_cache['all_modules'][$branch_id])) {
            return $this->modules_cache['all_modules'][$branch_id];
        }
        
        $sql = "SELECT pm.* 
                FROM branch b
                JOIN branch_subscriptions bs ON b.subscription_id = bs.id
                JOIN subscription_plan_modules spm ON bs.plan_id = spm.plan_id
                JOIN permission_modules pm ON pm.id = spm.module_id
                WHERE b.id = ? AND bs.status = 'active'";
        
        $modules = $this->db->query($sql, [$branch_id])->result();
        $this->modules_cache['all_modules'][$branch_id] = $modules;
        
        return $modules;
    }

    /**
 * Update branch modules based on plan
 */
public function update_branch_modules($branch_id, $plan_id)
{
    // log_message('info', "Updating modules for branch {$branch_id} with plan {$plan_id}");
    
    // Get modules from plan
    $modules = $this->get_plan_modules($plan_id);
    $module_ids = array_column($modules, 'id');
    
    // log_message('info', "Plan has " . count($module_ids) . " modules: " . implode(',', $module_ids));
    
    // First, ensure all modules exist in modules_manage for this branch
    $all_modules = $this->db->get('permission_modules')->result();
    foreach ($all_modules as $module) {
        $this->db->where('branch_id', $branch_id);
        $this->db->where('modules_id', $module->id);
        $exists = $this->db->get('modules_manage')->row();
        
        if (!$exists) {
            $this->db->insert('modules_manage', [
                'branch_id' => $branch_id,
                'modules_id' => $module->id,
                'isEnabled' => 0
            ]);
            // log_message('info', "Created missing module entry for module ID {$module->id}");
        }
    }
    
    // Disable ALL modules first
    $this->db->where('branch_id', $branch_id);
    $this->db->update('modules_manage', ['isEnabled' => 0]);
    // log_message('info', "Disabled all modules for branch {$branch_id}");
    
    // Enable modules in the plan
    foreach ($module_ids as $module_id) {
        $this->db->where('branch_id', $branch_id);
        $this->db->where('modules_id', $module_id);
        $this->db->update('modules_manage', ['isEnabled' => 1]);
        
        if ($this->db->affected_rows() > 0) {
            // log_message('info', "Enabled module ID {$module_id} for branch {$branch_id}");
        } else {
            // log_message('error', "Failed to enable module ID {$module_id} for branch {$branch_id}");
        }
    }
    
    // Verify the update
    $this->db->where('branch_id', $branch_id);
    $this->db->where('isEnabled', 1);
    $enabled_count = $this->db->count_all_results('modules_manage');
    // log_message('info', "After update: Branch {$branch_id} has {$enabled_count} enabled modules");
}

/**
 * Clear module cache for a branch
 */
public function clear_branch_cache($branch_id)
{
    // Clear any cached module data
    if (isset($this->modules_cache['access'])) {
        foreach ($this->modules_cache['access'] as $key => $value) {
            if (strpos($key, $branch_id . '_') === 0) {
                unset($this->modules_cache['access'][$key]);
            }
        }
    }
    
    // Clear session data if needed
    $this->session->unset_userdata('modules_cache');
    
    // log_message('info', "Cleared cache for branch {$branch_id}");
}
    /**
     * ============================================
     * USAGE TRACKING METHODS
     * ============================================
     */

    /**
     * Track module usage
     */
    public function track_usage($branch_id, $module_prefix, $action, $quantity = 1, $reference_id = null, $metadata = null)
    {
        // Get module ID
        $this->db->select('id');
        $this->db->where('prefix', $module_prefix);
        $module = $this->db->get('permission_modules')->row();
        
        if (!$module) {
            return false;
        }
        
        $billing_month = date('Y-m');
        
        $data = [
            'branch_id' => $branch_id,
            'module_id' => $module->id,
            'action' => $action,
            'quantity' => $quantity,
            'reference_id' => $reference_id,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'billing_month' => $billing_month,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('module_usage', $data);
    }

    /**
     * Track SMS usage specifically
     */
    public function track_sms_usage($branch_id, $sms_units, $reference_id, $message_type, $metadata = [])
    {
        // Track in module_usage
        $this->track_usage(
            $branch_id, 
            'bulk_sms_and_email', 
            'sms_sent', 
            $sms_units, 
            $reference_id, 
            array_merge(['message_type' => $message_type], $metadata)
        );
        
        // Also track in detailed SMS table if exists
        if ($this->db->table_exists('sms_usage_detailed')) {
            $detailed_data = [
                'branch_id' => $branch_id,
                'bulk_sms_email_id' => $reference_id,
                'message_type' => $message_type,
                'recipient_count' => $metadata['recipient_count'] ?? 1,
                'sms_units_used' => $sms_units,
                'message_length' => $metadata['message_length'] ?? null,
                'is_unicode' => $metadata['is_unicode'] ?? 0,
                'billing_month' => date('Y-m'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('sms_usage_detailed', $detailed_data);
        }
        
        // Check if overage and track
        $this->check_sms_overage($branch_id, $sms_units);
    }

    /**
     * Track student addition
     */
    public function track_student_addition($branch_id, $student_id, $metadata = [])
    {
        return $this->track_usage($branch_id, 'student', 'student_added', 1, $student_id, $metadata);
    }

    /**
     * Get usage summary for branch
     */
    public function get_usage_summary($branch_id, $billing_month = null)
    {
        $billing_month = $billing_month ?: date('Y-m');
        
        $this->db->select('module_id, action, SUM(quantity) as total_quantity, COUNT(*) as usage_count');
        $this->db->from('module_usage');
        $this->db->where('branch_id', $branch_id);
        $this->db->where('billing_month', $billing_month);
        $this->db->group_by('module_id, action');
        
        return $this->db->get()->result();
    }

    /**
     * ============================================
     * SMS CREDIT MANAGEMENT
     * ============================================
     */

    /**
 * Initialize SMS credits for new subscription
 */
private function initialize_sms_credits($branch_id, $plan_id, $billing_cycle)
{
    $plan = $this->get_plan($plan_id);
    
    if (!$plan) {
        // log_message('error', "Plan not found for SMS credits initialization: {$plan_id}");
        return;
    }
    
    $cycle_start = date('Y-m-d');
    $cycle_end = $this->calculate_end_date($cycle_start, $billing_cycle, $plan->term_months);
    
    // Check if SMS credit record exists
    $this->db->where('branch_id', $branch_id);
    $credit = $this->db->get('sms_credit')->row();
    
    $data = [
        'subscription_plan_id' => $plan_id,
        'monthly_allowance' => $plan->max_sms_units,
        'last_reset_date' => $cycle_start,
        'next_reset_date' => $cycle_end,
        'billing_cycle' => $billing_cycle,
        'cycle_start_date' => $cycle_start,
        'cycle_end_date' => $cycle_end,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($credit) {
        $data['carry_forward'] = $credit->credits > $plan->max_sms_units ? 
            min($credit->credits - $plan->max_sms_units, $plan->max_sms_units * 0.5) : 0;
        
        $this->db->where('branch_id', $branch_id);
        $this->db->update('sms_credit', $data);
    } else {
        $data['branch_id'] = $branch_id;
        $data['credits'] = $plan->max_sms_units;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert('sms_credit', $data);
    }
    
    // log_message('info', "SMS credits initialized for branch {$branch_id}: {$plan->max_sms_units} units");
}
    /**
     * Adjust SMS credits when changing plan
     */
    private function adjust_sms_credits_for_plan_change($branch_id, $old_plan, $new_plan, $current_sub)
    {
        $this->db->where('branch_id', $branch_id);
        $credit = $this->db->get('sms_credit')->row();
        
        if (!$credit) {
            return;
        }
        
        // Calculate prorated allowance
        $days_remaining = max(0, (strtotime($current_sub->end_date) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
        $total_days = (strtotime($current_sub->end_date) - strtotime($current_sub->start_date)) / (60 * 60 * 24);
        
        $old_monthly = $old_plan->max_sms_units;
        $new_monthly = $new_plan->max_sms_units;
        
        $prorated_old = ($old_monthly / 30) * $days_remaining;
        $prorated_new = ($new_monthly / 30) * $days_remaining;
        
        $adjustment = $prorated_new - $prorated_old;
        
        // Update credits
        $new_credits = max(0, $credit->credits + $adjustment);
        
        $this->db->where('branch_id', $branch_id);
        $this->db->update('sms_credit', [
            'credits' => $new_credits,
            'subscription_plan_id' => $new_plan->id,
            'monthly_allowance' => $new_plan->max_sms_units,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check SMS overage
     */
    private function check_sms_overage($branch_id, $sms_used)
    {
        $this->db->where('branch_id', $branch_id);
        $credit = $this->db->get('sms_credit')->row();
        
        if (!$credit) {
            return;
        }
        
        $total_available = $credit->credits + $credit->carry_forward;
        
        if ($total_available < 0) {
            // Overage detected
            $overage = abs($total_available);
            
            $this->db->where('branch_id', $branch_id);
            $this->db->update('sms_credit', [
                'overage_units' => $credit->overage_units + $overage,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Check branch settings for overage handling
            $this->db->select('sms_overage_allowed, sms_overage_rate');
            $this->db->where('id', $branch_id);
            $branch = $this->db->get('branch')->row();
            
            if ($branch && $branch->sms_overage_allowed && $branch->sms_overage_rate > 0) {
                $charge = $overage * $branch->sms_overage_rate;
                
                $this->db->where('branch_id', $branch_id);
                $this->db->update('sms_credit', [
                    'overage_charges' => $credit->overage_charges + $charge,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Log overage for billing
                $this->log_action('sms_overage', "SMS overage for branch {$branch_id}: {$overage} units, charge {$charge}");
            }
        }
    }

    /**
     * Reset SMS credits for billing cycle
     */
    public function reset_sms_credits($branch_id = null)
    {
        $this->db->select('sc.*, b.sms_overage_allowed');
        $this->db->from('sms_credit sc');
        $this->db->join('branch b', 'b.id = sc.branch_id');
        $this->db->where('sc.next_reset_date <=', date('Y-m-d'));
        
        if ($branch_id) {
            $this->db->where('sc.branch_id', $branch_id);
        }
        
        $credits = $this->db->get()->result();
        
        foreach ($credits as $credit) {
            $this->db->trans_start();
            
            try {
                $carry_forward = 0;
                if ($credit->carry_forward_limit > 0) {
                    $carry_forward = min($credit->credits, $credit->carry_forward_limit);
                }
                
                $new_credits = $credit->monthly_allowance + $carry_forward;
                
                // Calculate new cycle dates
                $cycle_end = $this->calculate_end_date(
                    $credit->cycle_end_date ?: date('Y-m-d'),
                    $credit->billing_cycle,
                    3 // default term months
                );
                
                $this->db->where('id', $credit->id);
                $this->db->update('sms_credit', [
                    'credits' => $new_credits,
                    'carry_forward' => $carry_forward,
                    'last_reset_date' => date('Y-m-d'),
                    'next_reset_date' => $cycle_end,
                    'cycle_start_date' => $credit->cycle_end_date ?: date('Y-m-d'),
                    'cycle_end_date' => $cycle_end,
                    'overage_units' => 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Generate invoice for overage charges if any
                if ($credit->overage_charges > 0) {
                    $this->generate_overage_invoice($credit->branch_id, $credit->overage_units, $credit->overage_charges);
                }
                
                $this->db->trans_complete();
                
            } catch (Exception $e) {
                $this->db->trans_rollback();
                // log_message('error', 'Reset SMS credits failed for branch ' . $credit->branch_id . ': ' . $e->getMessage());
            }
        }
        
        return count($credits);
    }

    /**
     * ============================================
     * EXPIRY AND CRON JOBS
     * ============================================
     */

    /**
 * Check expired subscriptions (cron job)
 */
public function check_expired_subscriptions()
{
    $today = date('Y-m-d');
    
    // Log for debugging
    // log_message('info', 'Running expired subscriptions check for date: ' . $today);
    
    $this->db->where('end_date <', $today);
    $this->db->where('status', 'active');
    $expired = $this->db->get('branch_subscriptions')->result();
    
    // log_message('info', 'Found ' . count($expired) . ' expired subscriptions');
    
    $count = 0;
    
    foreach ($expired as $sub) {
        $this->db->trans_start();
        
        try {
            // log_message('info', "Processing expired subscription ID: {$sub->id} for branch: {$sub->branch_id}");
            
            // Mark subscription as expired
            $this->db->where('id', $sub->id);
            $this->db->update('branch_subscriptions', [
                'status' => 'expired',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
          // Update branch status
            $this->db->where('id', $sub->branch_id);
            $this->db->update('branch', [
                'subscription_status' => 'expired',
                'subscription_updated_at' => date('Y-m-d H:i:s')
            ]);

            // Clear any cached session data for this branch
            $this->db->where('id', $sub->branch_id);
            $branch = $this->db->get('branch')->row();
            // If you store branch data in session, you might want to clear it
            // This depends on your session structure

            // Disable all modules for this branch
            $this->db->where('branch_id', $sub->branch_id);
            $this->db->update('modules_manage', ['isEnabled' => 0]);
            
            // Check if auto-renew is enabled
            if ($sub->auto_renew) {
                // Create renewal invoice
                $this->create_renewal_invoice($sub);
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() !== FALSE) {
                $count++;
                // log_message('info', "Successfully expired subscription ID: {$sub->id}");
            } else {
                // log_message('error', "Transaction failed for subscription ID: {$sub->id}");
            }
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            // log_message('error', 'Expire subscription failed for ID ' . $sub->id . ': ' . $e->getMessage());
        }
    }
    
    // log_message('info', "Expired subscriptions processed: {$count}");
    return $count;
}

    /**
     * Send expiry reminders
     */
    public function send_expiry_reminders()
    {
        $reminder_days = [7, 3, 1]; // Days before expiry to remind
        
        foreach ($reminder_days as $days) {
            $target_date = date('Y-m-d', strtotime("+{$days} days"));
            
            $this->db->select('bs.*, b.name as branch_name, b.email, b.mobileno');
            $this->db->from('branch_subscriptions bs');
            $this->db->join('branch b', 'b.id = bs.branch_id');
            $this->db->where('bs.end_date', $target_date);
            $this->db->where('bs.status', 'active');
            
            $expiring = $this->db->get()->result();
            
            foreach ($expiring as $sub) {
                // Send email/SMS reminder
                $this->send_expiry_notification($sub, $days);
                
                $this->log_action('expiry_reminder', "Sent {$days}-day expiry reminder to branch {$sub->branch_id}");
            }
        }
    }

    /**
     * ============================================
     * HELPER METHODS
     * ============================================
     */

    /**
     * Calculate end date based on billing cycle
     */
    private function calculate_end_date($start_date, $billing_cycle, $term_months = 3)
    {
        switch ($billing_cycle) {
            case 'monthly':
                return date('Y-m-d', strtotime($start_date . ' +1 month'));
            case 'termly':
                return date('Y-m-d', strtotime($start_date . " +{$term_months} months"));
            case 'yearly':
                return date('Y-m-d', strtotime($start_date . ' +1 year'));
            default:
                return date('Y-m-d', strtotime($start_date . ' +1 month'));
        }
    }

    /**
     * Get plan price based on billing cycle
     */
    private function get_plan_price($plan, $billing_cycle)
    {
        switch ($billing_cycle) {
            case 'monthly':
                return $plan->price_monthly;
            case 'termly':
                return $plan->price_termly;
            case 'yearly':
                return $plan->price_yearly;
            default:
                return $plan->price_monthly;
        }
    }

    /**
     * Generate unique invoice number
     */
    private function generate_invoice_no()
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        $this->db->select('COUNT(*) as count');
        $this->db->where('invoice_no LIKE', "{$prefix}-{$year}{$month}%");
        $result = $this->db->get('subscription_invoices')->row();
        
        $sequence = str_pad($result->count + 1, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}{$month}{$sequence}";
    }

    /**
     * Create renewal invoice
     */
    private function create_renewal_invoice($subscription)
    {
        $plan = $this->get_plan($subscription->plan_id);
        
        $invoice_data = [
            'invoice_no' => $this->generate_invoice_no(),
            'branch_id' => $subscription->branch_id,
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'amount' => $plan->price_monthly,
            'tax_amount' => 0,
            'total_amount' => $plan->price_monthly,
            'billing_period_start' => $subscription->end_date,
            'billing_period_end' => $this->calculate_end_date($subscription->end_date, $subscription->billing_cycle, $plan->term_months),
            'due_date' => date('Y-m-d', strtotime($subscription->end_date . ' -5 days')),
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('subscription_invoices', $invoice_data);
        return $this->db->insert_id();
    }

    /**
     * Generate overage invoice
     */
    private function generate_overage_invoice($branch_id, $overage_units, $overage_charges)
    {
        $this->db->where('branch_id', $branch_id);
        $subscription = $this->db->get('branch_subscriptions')->row();
        
        if (!$subscription) {
            return;
        }
        
        $invoice_data = [
            'invoice_no' => $this->generate_invoice_no(),
            'branch_id' => $branch_id,
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'amount' => $overage_charges,
            'tax_amount' => 0,
            'total_amount' => $overage_charges,
            'billing_period_start' => $subscription->start_date,
            'billing_period_end' => $subscription->end_date,
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('subscription_invoices', $invoice_data);
    }

    /**
     * Send expiry notification
     */
    private function send_expiry_notification($subscription, $days_remaining)
    {
        // Email notification
        $email_data = [
            'branch_name' => $subscription->branch_name,
            'days_remaining' => $days_remaining,
            'end_date' => $subscription->end_date,
            'renewal_link' => base_url('subscription/renew/' . $subscription->branch_id)
        ];
        
        // Load email library and send
        $this->load->library('email');
        // ... email sending logic
        
        // SMS notification if configured
        if (!empty($subscription->mobileno)) {
            $message = "Your subscription expires in {$days_remaining} days. Renew now: " . base_url('subscription/renew');
            $this->load->library('bulksmsbd');
            $this->bulksmsbd->send($subscription->mobileno, $message);
        }
    }

    /**
     * ============================================
     * LOGGING METHODS
     * ============================================
     */

    /**
     * Log subscription action
     */
    private function log_action($action, $description, $old_data = null, $new_data = null)
    {
        if (!$this->db->table_exists('subscription_logs')) {
            return;
        }
        
        $log_data = [
            'action' => $action,
            'description' => $description,
            'old_data' => $old_data ? json_encode($old_data) : null,
            'new_data' => $new_data ? json_encode($new_data) : null,
            'user_id' => $this->session->userdata('login_user_id'),
            'ip_address' => $this->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('subscription_logs', $log_data);
    }

    /**
     * ============================================
     * ADMIN DASHBOARD METHODS
     * ============================================
     */

    /**
     * Get subscription revenue report
     */
    public function get_revenue_report($year = null)
    {
        $year = $year ?: date('Y');
        
        $sql = "SELECT 
                    MONTH(payment_date) as month,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_revenue,
                    AVG(amount) as average_payment
                FROM subscription_payments
                WHERE YEAR(payment_date) = ? 
                    AND status = 'completed'
                GROUP BY MONTH(payment_date)
                ORDER BY MONTH(payment_date)";
        
        $results = $this->db->query($sql, [$year])->result();
        
        // Format for chart
        $months = [];
        $revenue = [];
        $counts = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $found = false;
            foreach ($results as $row) {
                if ($row->month == $i) {
                    $months[] = date('M', mktime(0, 0, 0, $i, 1));
                    $revenue[] = (float)$row->total_revenue;
                    $counts[] = (int)$row->payment_count;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $months[] = date('M', mktime(0, 0, 0, $i, 1));
                $revenue[] = 0;
                $counts[] = 0;
            }
        }
        
        return [
            'months' => $months,
            'revenue' => $revenue,
            'counts' => $counts,
            'total' => array_sum($revenue)
        ];
    }

    /**
     * Get subscription statistics
     */
    public function get_statistics()
    {
        $stats = new stdClass();
        
        // Active subscriptions
        $this->db->where('status', 'active');
        $stats->active = $this->db->count_all_results('branch_subscriptions');
        
        // Expiring soon (next 7 days)
        $this->db->where('status', 'active');
        $this->db->where('end_date >=', date('Y-m-d'));
        $this->db->where('end_date <=', date('Y-m-d', strtotime('+7 days')));
        $stats->expiring_soon = $this->db->count_all_results('branch_subscriptions');
        
        // Expired
        $this->db->where('status', 'expired');
        $stats->expired = $this->db->count_all_results('branch_subscriptions');
        
        // Total branches
        $stats->total_branches = $this->db->count_all('branch');
        
        // Most popular plan
        $this->db->select('sp.name, COUNT(*) as count');
        $this->db->from('branch_subscriptions bs');
        $this->db->join('subscription_plans sp', 'sp.id = bs.plan_id');
        $this->db->where('bs.status', 'active');
        $this->db->group_by('bs.plan_id');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(1);
        $popular = $this->db->get()->row();
        
        $stats->most_popular_plan = $popular ? $popular->name : 'N/A';
        $stats->most_popular_count = $popular ? $popular->count : 0;
        
        return $stats;
    }

  public function get_pending_transactions($status = null)
{
    $this->db->select('sp.*, b.name as branch_name, b.email as branch_email, b.mobileno as branch_phone, bs.status as subscription_status');
    $this->db->from('subscription_payments sp');
    $this->db->join('branch b', 'b.id = sp.branch_id', 'left');
    $this->db->join('branch_subscriptions bs', 'bs.id = sp.subscription_id', 'left');
    
    if ($status) {
        $this->db->where('sp.status', $status);
    } else {
        // ✅ FIXED: Include both 'pending' and 'failed' statuses
        $this->db->where_in('sp.status', ['pending', 'failed']);
    }
    
    $this->db->order_by('sp.created_at', 'DESC');
    return $this->db->get()->result();
}

/**
 * Get a specific transaction for reconciliation
 */
public function get_transaction_for_reconciliation($transaction_id)
{
    $this->db->select('sp.*, b.name as branch_name, b.email as branch_email, b.mobileno as branch_phone');
    $this->db->from('subscription_payments sp');
    $this->db->join('branch b', 'b.id = sp.branch_id', 'left');
    $this->db->where('sp.id', $transaction_id);
    return $this->db->get()->row();
}

/**
 * Manually verify and activate a subscription
 */
public function manual_verify_subscription($transaction_id, $receipt_number = null, $notes = null)
{
    $this->db->trans_start();
    
    try {
        // Get the transaction
        $transaction = $this->get_transaction_for_reconciliation($transaction_id);
        
        if (!$transaction) {
            // log_message('error', "Transaction not found: {$transaction_id}");
            return false;
        }
        
        // Check if already completed
        if ($transaction->status == 'completed') {
            // log_message('info', "Transaction already completed: {$transaction_id}");
            return ['error' => 'Transaction already completed'];
        }
        
        // Get the subscription
        $this->db->where('id', $transaction->subscription_id);
        $subscription = $this->db->get('branch_subscriptions')->row();
        
        if (!$subscription) {
            // log_message('error', "Subscription not found for transaction: {$transaction_id}");
            return false;
        }
        
        // Update transaction status
        $receipt = $receipt_number ?: $transaction->mpesa_receipt ?: 'MANUAL_' . date('YmdHis');
        
        $this->db->where('id', $transaction_id);
        $this->db->update('subscription_payments', [
            'status' => 'completed',
            'mpesa_receipt' => $receipt,
            'reconciled_by' => get_loggedin_user_id(),
            'reconciliation_note' => $notes,
            'reconciled_at' => date('Y-m-d H:i:s')
        ]);
        
        // Check if subscription needs activation
        if ($subscription->status == 'pending') {
            // Activate subscription
            $this->db->where('id', $subscription->id);
            $this->db->update('branch_subscriptions', [
                'status' => 'active',
                'payment_status' => 'paid',
                'mpesa_receipt' => $receipt,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update branch
            $this->db->where('id', $subscription->branch_id);
            $this->db->update('branch', [
                'subscription_id' => $subscription->id,
                'subscription_status' => 'active',
                'subscription_updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Initialize SMS credits
            $this->initialize_sms_credits($subscription->branch_id, $subscription->plan_id, $subscription->billing_cycle);
            
            // Enable modules
            $this->update_branch_modules($subscription->branch_id, $subscription->plan_id);
            
            // log_message('info', "Subscription activated via manual verification: {$subscription->id}");
        }
        
        // Log the reconciliation
        $this->log_action('manual_verification', 
            "Manually verified transaction {$transaction_id} for branch {$subscription->branch_id}", 
            null, 
            ['transaction_id' => $transaction_id, 'receipt' => $receipt, 'notes' => $notes]
        );
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Transaction failed');
        }
        
        return ['success' => true, 'subscription_id' => $subscription->id];
        
    } catch (Exception $e) {
        $this->db->trans_rollback();
        // log_message('error', 'Manual verification failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get reconciliation statistics
 */
public function get_reconciliation_stats()
{
    $stats = new stdClass();
    
    // Pending transactions
    $this->db->where('status', 'pending');
    $stats->pending = $this->db->count_all_results('subscription_payments');
    
    // Failed transactions
    $this->db->where('status', 'failed');
    $stats->failed = $this->db->count_all_results('subscription_payments');
    
    // Total reconciled
    $this->db->where('status', 'completed');
    $this->db->where('reconciled_by IS NOT NULL');
    $stats->reconciled = $this->db->count_all_results('subscription_payments');
    
    return $stats;
}
    /**
 * Track emergency module usage for billing
 */
public function track_emergency_usage($branch_id, $incident_id, $sms_count = 0)
{
    $billing_month = date('Y-m');
    
    // Check if already tracked for this incident
    $exists = $this->db->where('reference_id', $incident_id)
        ->where('module_id', 10) // Hostel module ID
        ->where('action', 'emergency_incident')
        ->get('module_usage')
        ->num_rows();
    
    if ($exists == 0) {
        $data = array(
            'branch_id' => $branch_id,
            'module_id' => 10, // Hostel module
            'action' => 'emergency_incident',
            'quantity' => 1,
            'reference_id' => $incident_id,
            'metadata' => json_encode(array('sms_count' => $sms_count)),
            'billing_month' => $billing_month,
            'created_at' => date('Y-m-d H:i:s')
        );
        
        $this->db->insert('module_usage', $data);
    }
}
}