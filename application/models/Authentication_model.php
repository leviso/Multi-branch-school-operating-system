<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Authentication_model extends MY_Model
{

    // checking login credential
    public function login_credential($username, $password)
    {
        $this->db->select('*');
        $this->db->from('login_credential');
        $this->db->where('username', $username);
        $this->db->limit(1);
        $query = $this->db->get();
        if ($query->num_rows() == 1) {
            $verify_password = $this->app_lib->verify_password($password, $query->row()->password);
            if ($verify_password) {
                return $query->row();
            }
        }
        return false;
    }

    // password forgotten
    public function lose_password($username)
    {
        if (!empty($username)) {
            $this->db->select('*');
            $this->db->from('login_credential');
            $this->db->where('username', $username);
            $this->db->limit(1);
            $query = $this->db->get();

            if ($query->num_rows() > 0) {
                $login_credential = $query->row();
                $getUser = $this->application_model->getUserNameByRoleID($login_credential->role, $login_credential->user_id);
                $key = hash('sha512', $login_credential->role . $login_credential->username . app_generate_hash());
                $query = $this->db->get_where('reset_password', array('login_credential_id' => $login_credential->id));
                if ($query->num_rows() > 0) {
                    $this->db->where('login_credential_id', $login_credential->id);
                    $this->db->delete('reset_password');
                }
                $arrayReset = array(
                    'key' => $key,
                    'login_credential_id' => $login_credential->id,
                    'username' => $login_credential->username,
                );
                $this->db->insert('reset_password', $arrayReset);
                // send email for forgot password
                $this->load->model('email_model');
                $arrayData = array(
                    'role' => $login_credential->role,
                    'branch_id' => $getUser['branch_id'],
                    'username' => $login_credential->username,
                    'name' => $getUser['name'],
                    'reset_url' => base_url('authentication/pwreset?key=' . $key),
                    'email' => $getUser['email'],
                );
                $this->email_model->sentForgotPassword($arrayData);
                return true;
            }
        }
        return false;
    }

  /**
 * Find user by mobile number across all user types
 */
public function find_user_by_mobile($mobile)
{
    // Clean mobile number
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    
    // Format variations to search
    $formats = [];
    $formats[] = $mobile;
    
    if (strlen($mobile) == 10 && substr($mobile, 0, 1) == '0') {
        $formats[] = '254' . substr($mobile, 1);
    }
    if (strlen($mobile) == 9 && substr($mobile, 0, 1) == '7') {
        $formats[] = '254' . $mobile;
    }
    if (strlen($mobile) == 12 && substr($mobile, 0, 3) == '254') {
        $formats[] = '0' . substr($mobile, 3);
        $formats[] = substr($mobile, 3);
    }
    $formats = array_unique($formats);
    
    // 1. Search in staff table (all roles, not just role 4)
    $this->db->select('lc.id as login_id, lc.user_id, lc.role, s.name, s.email, s.mobileno, s.branch_id');
    $this->db->from('staff s');
    $this->db->join('login_credential lc', 'lc.user_id = s.id', 'inner');
    $this->db->group_start();
    foreach ($formats as $format) {
        $this->db->or_where('s.mobileno', $format);
    }
    $this->db->group_end();
    $staff = $this->db->get()->row_array();
    
    if ($staff) {
        return $staff;
    }
    
    // 2. Search in student table
    $this->db->select('lc.id as login_id, lc.user_id, lc.role, CONCAT(s.first_name, " ", s.last_name) as name, s.email, s.mobileno, s.branch_id');
    $this->db->from('student s');
    $this->db->join('login_credential lc', 'lc.user_id = s.id', 'inner');
    $this->db->group_start();
    foreach ($formats as $format) {
        $this->db->or_where('s.mobileno', $format);
    }
    $this->db->group_end();
    $student = $this->db->get()->row_array();
    
    if ($student) {
        return $student;
    }
    
    // 3. Search in parent table
    $this->db->select('lc.id as login_id, lc.user_id, lc.role, p.name, p.email, p.mobileno, p.branch_id');
    $this->db->from('parent p');
    $this->db->join('login_credential lc', 'lc.user_id = p.id', 'inner');
    $this->db->group_start();
    foreach ($formats as $format) {
        $this->db->or_where('p.mobileno', $format);
    }
    $this->db->group_end();
    $parent = $this->db->get()->row_array();
    
    if ($parent) {
        return $parent;
    }
    
    // 4. Search directly in login_credential by username (if user enters email as username)
    $this->db->select('lc.id as login_id, lc.user_id, lc.role, lc.username as email, "" as mobileno, "" as branch_id');
    $this->db->from('login_credential lc');
    $this->db->where('lc.username', $mobile);
    $login = $this->db->get()->row_array();
    
    if ($login) {
        // Get user details based on role
        if ($login['role'] == 7) {
            $student = $this->db->select('first_name, last_name, branch_id')->from('student')->where('id', $login['user_id'])->get()->row();
            if ($student) {
                $login['name'] = $student->first_name . ' ' . $student->last_name;
                $login['branch_id'] = $student->branch_id;
            }
        } elseif ($login['role'] == 6) {
            $parent = $this->db->select('name, branch_id')->from('parent')->where('id', $login['user_id'])->get()->row();
            if ($parent) {
                $login['name'] = $parent->name;
                $login['branch_id'] = $parent->branch_id;
            }
        } else {
            $staff = $this->db->select('name, branch_id')->from('staff')->where('id', $login['user_id'])->get()->row();
            if ($staff) {
                $login['name'] = $staff->name;
                $login['branch_id'] = $staff->branch_id;
            }
        }
        return $login;
    }
    
    return false;
}

/**
 * Send OTP via SMS using mobile number
 */
public function send_otp_by_mobile($mobile)
{
    // Find user by mobile
    $user = $this->find_user_by_mobile($mobile);
    
    if (!$user) {
        return ['status' => false, 'message' => 'Mobile number not found in our records'];
    }
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Delete old OTP entries for this user
    $this->db->where('login_credential_id', $user['login_id']);
    $this->db->delete('reset_password');
    
    // Store OTP in database
    $reset_data = [
        'key' => hash('sha512', $user['role'] . $user['mobile'] . time()),
        'login_credential_id' => $user['login_id'],
        'username' => $user['email'],
        'otp' => $otp,
        'otp_expires_at' => $expires_at,
        'verified' => 0,
        'method' => 'sms',
        'created_at' => date('Y-m-d H:i:s')
    ];
    $this->db->insert('reset_password', $reset_data);
    
    // Store mobile in session
    $ci = &get_instance();
    $ci->session->set_userdata('reset_mobile', $mobile);
    
    // Send SMS
    return $this->send_otp_sms_by_mobile($user, $otp);
}

/**
 * Send OTP SMS
 */
private function send_otp_sms_by_mobile($user, $otp)
{
    $mobile = $user['mobileno'];
    
    // Clean and format phone number
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    if (strlen($mobile) == 10 && substr($mobile, 0, 1) == '0') {
        $mobile = '254' . substr($mobile, 1);
    }
    if (strlen($mobile) == 9 && substr($mobile, 0, 1) == '7') {
        $mobile = '254' . $mobile;
    }
    
    $message = "Your password reset OTP is: {$otp}. Valid for 10 minutes. DO NOT share with anyone.";
    
    try {
        $ci = &get_instance();
        
        // Get branch ID from user
        $branch_id = isset($user['branch_id']) && $user['branch_id'] ? $user['branch_id'] : 13;
        
        // Load SMS library directly (no second parameter alias)
        $ci->load->library('bulksmsbd', ['branch_id' => $branch_id]);
        
        // Send SMS
        $response = $ci->bulksmsbd->send($mobile, $message);
        
        // Log for debugging
        log_message('error', 'SMS Send - Mobile: ' . $mobile . ', Branch: ' . $branch_id . ', Response: ' . $response);
        
        if (strpos($response, 'Success') !== false || strpos($response, '200') !== false) {
            return [
                'status' => true, 
                'message' => 'OTP sent to ' . $this->mask_number($user['mobileno']) . '. Valid for 10 minutes.'
            ];
        } else {
            return [
                'status' => false, 
                'message' => 'Failed to send SMS. Response: ' . substr($response, 0, 100)
            ];
        }
        
    } catch (Exception $e) {
        log_message('error', 'SMS Exception: ' . $e->getMessage());
        return [
            'status' => false, 
            'message' => 'SMS service error: ' . $e->getMessage()
        ];
    }
}

/**
 * Mask phone number for display
 */
private function mask_number($number)
{
    $number = preg_replace('/[^0-9]/', '', $number);
    $length = strlen($number);
    if ($length > 6) {
        return substr($number, 0, 3) . '****' . substr($number, -3);
    }
    return $number;
}

/**
 * Verify OTP by mobile
 */
public function verify_otp_by_mobile($mobile, $otp)
{
    if (empty($mobile)) {
        return ['status' => false, 'message' => 'Session expired. Please try again.'];
    }
    
    // Find user to get login_id
    $user = $this->find_user_by_mobile($mobile);
    if (!$user) {
        return ['status' => false, 'message' => 'User not found'];
    }
    
    $this->db->where('login_credential_id', $user['login_id']);
    $this->db->where('otp', $otp);
    $this->db->where('verified', 0);
    $this->db->where('otp_expires_at >', date('Y-m-d H:i:s'));
    $query = $this->db->get('reset_password');
    
    if ($query->num_rows() > 0) {
        $this->db->where('login_credential_id', $user['login_id']);
        $this->db->update('reset_password', ['verified' => 1]);
        
        return ['status' => true, 'message' => 'OTP verified successfully.'];
    } else {
        return ['status' => false, 'message' => 'Invalid or expired OTP. Please request a new one.'];
    }
}

/**
 * Reset password by mobile
 */
public function reset_password_by_mobile($mobile, $new_password)
{
    if (empty($mobile)) {
        return ['status' => false, 'message' => 'Session expired. Please try again.'];
    }
    
    // Find user
    $user = $this->find_user_by_mobile($mobile);
    if (!$user) {
        return ['status' => false, 'message' => 'User not found'];
    }
    
    // Check if OTP was verified
    $this->db->where('login_credential_id', $user['login_id']);
    $this->db->where('verified', 1);
    $reset = $this->db->get('reset_password')->row();
    
    if (!$reset) {
        return ['status' => false, 'message' => 'OTP not verified. Please request a new OTP.'];
    }
    
    // Update password
    $hashed_password = $this->app_lib->pass_hashed($new_password);
    
    $this->db->where('id', $user['login_id']);
    $this->db->update('login_credential', ['password' => $hashed_password]);
    
    // Delete reset record
    $this->db->where('login_credential_id', $user['login_id']);
    $this->db->delete('reset_password');
    
    return ['status' => true, 'message' => 'Password reset successfully. You can now login.'];
}

    /**
 * Send OTP via SMS or Email
 */
public function send_otp($username, $method = 'sms')
{
    // Find login credential
    $this->db->select('lc.*');
    $this->db->from('login_credential lc');
    $this->db->where('lc.username', $username);
    $query = $this->db->get();
    
    if ($query->num_rows() == 0) {
        return ['status' => false, 'message' => 'Account not found'];
    }
    
    $login_credential = $query->row();
    $getUser = $this->application_model->getUserNameByRoleID($login_credential->role, $login_credential->user_id);
    
    // Check if user has mobile for SMS
    if ($method == 'sms' && empty($getUser['mobileno'])) {
        return ['status' => false, 'message' => 'No mobile number registered. Please use email option.'];
    }
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Delete old OTP entries for this user
    $this->db->where('login_credential_id', $login_credential->id);
    $this->db->delete('reset_password');
    
    // Store OTP in database
    $reset_data = [
        'key' => hash('sha512', $login_credential->role . $login_credential->username . time()),
        'login_credential_id' => $login_credential->id,
        'username' => $login_credential->username,
        'otp' => $otp,
        'otp_expires_at' => $expires_at,
        'verified' => 0,
        'method' => $method,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $this->db->insert('reset_password', $reset_data);
    
    // Store username in session for later verification
    $ci = &get_instance();
    $ci->session->set_userdata('reset_username', $username);
    $ci->session->set_userdata('reset_method', $method);
    
    // Send OTP based on method
    if ($method == 'sms') {
        return $this->send_otp_sms($getUser, $otp);
    } else {
        return $this->send_otp_email($getUser, $otp);
    }
}

/**
 * Send OTP via SMS
 */
private function send_otp_sms($user, $otp)
{
    $mobile = $user['mobileno'];
    
    // Format phone number (ensure it starts with 254)
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    if (substr($mobile, 0, 1) == '0') {
        $mobile = '254' . substr($mobile, 1);
    }
    if (substr($mobile, 0, 1) == '7') {
        $mobile = '254' . $mobile;
    }
    
    $message = "Your password reset OTP is: {$otp}. Valid for 10 minutes. DO NOT share with anyone.";
    
    try {
        $ci = &get_instance();
        $ci->load->library('bulksmsbd', ['branch_id' => $user['branch_id']], 'sms_lib');
        
        // Check if SMS is configured
        if (!$ci->sms_lib->is_configured()) {
            // log_message('error', 'SMS not configured for branch: ' . $user['branch_id']);
            return [
                'status' => false, 
                'message' => 'SMS service not configured. Please use email option.',
                'fallback_to_email' => true
            ];
        }
        
        $response = $ci->sms_lib->send($mobile, $message);
        
        // Check if response indicates success
        if (strpos($response, 'Success') !== false || strpos($response, '200') !== false) {
            // log_message('info', 'OTP sent via SMS to: ' . $mobile);
            return [
                'status' => true, 
                'message' => 'OTP sent to your mobile number. Valid for 10 minutes.',
                'requires_verification' => true
            ];
        } else {
            // log_message('error', 'SMS send failed: ' . $response);
            return [
                'status' => false, 
                'message' => 'Failed to send SMS. Please use email option.',
                'fallback_to_email' => true
            ];
        }
        
    } catch (Exception $e) {
        // log_message('error', 'SMS exception: ' . $e->getMessage());
        return [
            'status' => false, 
            'message' => 'SMS service error. Please use email option.',
            'fallback_to_email' => true
        ];
    }
}

/**
 * Send OTP via Email
 */
private function send_otp_email($user, $otp)
{
    $email = $user['email'];
    $name = $user['name'];
    
    $subject = "Password Reset OTP - " . date('Y-m-d');
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .otp-code { font-size: 24px; font-weight: bold; color: #2c3e50; background: #ecf0f1; padding: 10px; text-align: center; letter-spacing: 5px; }
            .footer { font-size: 12px; color: #7f8c8d; margin-top: 20px; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Password Reset Request</h2>
            <p>Dear {$name},</p>
            <p>You requested to reset your password. Use the OTP code below to verify your identity:</p>
            <div class='otp-code'>{$otp}</div>
            <p>This OTP is valid for <strong>10 minutes</strong>.</p>
            <p>If you did not request this, please ignore this email.</p>
            <div class='footer'>
                &copy; " . date('Y') . " School Management System
            </div>
        </div>
    </body>
    </html>
    ";
    
    $this->load->library('email');
    $this->email->from($this->data['global_config']['email'], $this->data['global_config']['institute_name']);
    $this->email->to($email);
    $this->email->subject($subject);
    $this->email->message($message);
    
    if ($this->email->send()) {
        // log_message('info', 'OTP sent via email to: ' . $email);
        return [
            'status' => true, 
            'message' => 'OTP sent to your email. Valid for 10 minutes.',
            'requires_verification' => true
        ];
    } else {
        // log_message('error', 'Email send failed to: ' . $email);
        return [
            'status' => false, 
            'message' => 'Failed to send email. Please try again.'
        ];
    }
}

/**
 * Verify OTP
 */
public function verify_otp($username, $otp)
{
    if (empty($username)) {
        return ['status' => false, 'message' => 'Session expired. Please try again.'];
    }
    
    $this->db->where('username', $username);
    $this->db->where('otp', $otp);
    $this->db->where('verified', 0);
    $this->db->where('otp_expires_at >', date('Y-m-d H:i:s'));
    $query = $this->db->get('reset_password');
    
    if ($query->num_rows() > 0) {
        // Mark as verified
        $this->db->where('username', $username);
        $this->db->update('reset_password', ['verified' => 1]);
        
        return ['status' => true, 'message' => 'OTP verified successfully.'];
    } else {
        return ['status' => false, 'message' => 'Invalid or expired OTP. Please request a new one.'];
    }
}

/**
 * Reset password after verification
 */
public function reset_password($username, $new_password)
{
    if (empty($username)) {
        return ['status' => false, 'message' => 'Session expired. Please try again.'];
    }
    
    // Check if OTP was verified
    $this->db->where('username', $username);
    $this->db->where('verified', 1);
    $reset = $this->db->get('reset_password')->row();
    
    if (!$reset) {
        return ['status' => false, 'message' => 'OTP not verified. Please request a new OTP.'];
    }
    
    // Update password
    $hashed_password = $this->app_lib->pass_hashed($new_password);
    
    $this->db->where('username', $username);
    $this->db->update('login_credential', ['password' => $hashed_password]);
    
    // Delete reset record
    $this->db->where('username', $username);
    $this->db->delete('reset_password');
    
    // log_message('info', 'Password reset successfully for user: ' . $username);
    
    return ['status' => true, 'message' => 'Password reset successfully. You can now login.'];
}

    public function urlaliasToBranch($url_alias)
    {
        $saasExisting = $this->app_lib->isExistingAddon('saas');
        if ($saasExisting && $this->db->table_exists("custom_domain")) {
            $getDomain = $this->getCurrentDomain();
            if(!empty($getDomain)) {
                return $getDomain->school_id;
            }
        }

        $get = $this->db->select('branch_id')
            ->where('url_alias', $url_alias)
            ->get('front_cms_setting')
            ->row_array();
        if (empty($url_alias) || empty($get)) {
            return null;
        } else {
            return $get['branch_id'];
        }
    }

    public function getSegment($id = '')
    {
        $segment = $this->uri->segment($id);
        if (empty($segment)) {
            return '';
        } else {
            return '/' . $segment;
        }
    }

    public function getCurrentDomain()
    {
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $url = rtrim($url, '/');
        $domain =  parse_url($url, PHP_URL_HOST);
        $getDomain = $this->db->select('school_id')->get_where('custom_domain', array('status' => 1, 'url' => $domain))->row();
        return $getDomain;
    }
}
