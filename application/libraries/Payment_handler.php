<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_handler {
    private $ci;
    private $config;
    private $last_request_time;
    private $min_request_interval;
    
    public function __construct() {
        $this->ci =& get_instance();
        $this->ci->load->config('payment_gateway');
        $this->config = $this->ci->config->item('payment_gateway');
        
        // Initialize properties
        $this->last_request_time = 0;
        $this->min_request_interval = 2; // Minimum 2 seconds between requests
    }
    
        
    /**
     * Get access token with rate limiting
     */
    public function get_access_token() {
        // Rate limiting: Ensure minimum time between requests
        $time_since_last_request = time() - $this->last_request_time;
        if ($time_since_last_request < $this->min_request_interval) {
            $sleep_time = $this->min_request_interval - $time_since_last_request;
            // log_message('debug', "Rate limiting: Sleeping for {$sleep_time} seconds");
            sleep($sleep_time);
        }
        
        $credentials = base64_encode($this->config['consumer_key'] . ':' . $this->config['consumer_secret']);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['auth_url'],
            CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'MBSMS-Payment-System/1.0'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        $this->last_request_time = time();
        
        if ($http_code == 200) {
            $result = json_decode($response, true);
            return isset($result['access_token']) ? $result['access_token'] : null;
        } else if ($http_code == 429) {
            // log_message('warning', 'Rate limited while getting access token');
            return null;
        } else if ($http_code == 403) {
            // log_message('error', 'Blocked by Incapsula while getting access token');
            return null;
        }
        
        // log_message('error', "Failed to get access token - HTTP: {$http_code}");
        return null;
    }
    
    
    /**
     * Generate password for STK push
     */
    private function generate_password($timestamp) {
        $data = $this->config['shortcode'] . $this->config['passkey'] . $timestamp;
        return base64_encode($data);
    }
    
    /**
 * Initiate STK push payment
 */
public function initiate_payment($phone, $amount, $account_reference, $transaction_desc = 'SMS Purchase', $callback_type = 'sms') {
    // log_message('info', '=== CALLBACK URL BEING SENT TO M-PESA ===');
    // log_message('info', 'Subscription Callback: ' . base_url('subscription/payment_callback'));
    // log_message('info', 'SMS Callback: ' . base_url('sendsmsmail/payment_callback'));
    // log_message('info', '==========================================');

// Validate phone number format
    $phone = $this->format_phone_number($phone);
    if (!$phone) {
        return array('success' => false, 'message' => 'Invalid phone number format. Use 2547XXXXXXXX');
    }
    
    // Validate amount
    if ($amount < $this->config['min_amount'] || $amount > $this->config['max_amount']) {
        return array(
            'success' => false, 
            'message' => 'Amount must be between KES ' . $this->config['min_amount'] . ' and KES ' . $this->config['max_amount']
        );
    }
    
    $access_token = $this->get_access_token();
    if (!$access_token) {
        return array('success' => false, 'message' => 'Unable to authenticate with payment gateway. Please try again.');
    }
    
    $timestamp = date('YmdHis');
    $password = $this->generate_password($timestamp);
    
    // Select the correct callback URL based on payment type
    if ($callback_type == 'subscription') {
        $callback_url = $this->config['callback_url_subscription'];
    } elseif ($callback_type == 'fees') {
        $callback_url = $this->config['callback_url_fees'];
    } else {
        $callback_url = $this->config['callback_url_sms'];
    }

    // log_message('info', 'Using callback URL for type: ' . $callback_type . ' -> ' . $callback_url);
        
    $payload = array(
        'BusinessShortCode' => $this->config['shortcode'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $this->config['shortcode'],
        'PhoneNumber' => $phone,
        'CallBackURL' => $callback_url,
        'AccountReference' => $account_reference,
        'TransactionDesc' => $transaction_desc
    );
    
    // log_message('debug', 'STK Push Payload: ' . json_encode($payload));
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->config['stk_push_url']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Force IPv4
    curl_setopt($ch, CURLOPT_VERBOSE, true); // Add verbose output for debugging
    
    $host = parse_url($this->config['stk_push_url'], PHP_URL_HOST);
    $ips = gethostbynamel($host);
    // log_message('debug', 'DNS resolution for ' . $host . ': ' . print_r($ips, true));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // log_message('debug', 'STK Push Response - HTTP: ' . $http_code . ' - Response: ' . $response);
    
    if ($http_code == 200) {
        $result = json_decode($response, true);
        
        if (isset($result['ResponseCode']) && $result['ResponseCode'] == '0') {
            return array(
                'success' => true,
                'checkout_request_id' => $result['CheckoutRequestID'],
                'message' => 'Payment request sent to your phone. Please check your phone to complete the payment.'
            );
        } else {
            $error_message = isset($result['errorMessage']) ? $result['errorMessage'] : 'Unknown error occurred';
            // log_message('error', 'STK Push failed: ' . $error_message);
            return array('success' => false, 'message' => 'Payment initiation failed: ' . $error_message);
        }
    } else {
        // log_message('error', 'STK Push HTTP Error: ' . $http_code . ' - Curl Error: ' . $curl_error . ' - Response: ' . $response);
        return array('success' => false, 'message' => 'Network error occurred while initiating payment. Please try again.');
    }
}
    
 /**
 * Check payment status with proper rate limiting and backoff
 */
public function check_payment_status($checkout_request_id, $attempt = 1) {
    // log_message('debug', "Payment status check attempt {$attempt} for: {$checkout_request_id}");
    
    // Enhanced rate limiting - ensure minimum time between requests
    $min_interval = 5; // Minimum 5 seconds between queries
    $time_since_last = time() - ($this->last_request_time ?? 0);
    
    if ($time_since_last < $min_interval && $attempt > 1) {
        $sleep_time = $min_interval - $time_since_last;
        // log_message('debug', "Rate limiting: Waiting {$sleep_time} seconds between queries");
        sleep($sleep_time);
    }
    
    // Aggressive backoff for rate limiting (keep your existing logic)
    if ($attempt > 1) {
        $backoff_time = min(pow(3, $attempt - 1), 60);
        // log_message('debug', "Aggressive backoff: Waiting {$backoff_time} seconds");
        sleep($backoff_time);
    }
    
    $access_token = $this->get_access_token();
    if (!$access_token) {
        if ($attempt < 2) {
            sleep(10);
            return $this->check_payment_status($checkout_request_id, $attempt + 1);
        }
        return array('success' => false, 'message' => 'Unable to authenticate with payment gateway.');
    }
    
    $timestamp = date('YmdHis');
    $password = $this->generate_password($timestamp);
    
    $payload = array(
        'BusinessShortCode' => $this->config['shortcode'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'CheckoutRequestID' => $checkout_request_id
    );
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $this->config['query_url'],
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'MBSMS-Payment-System/1.0'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    $this->last_request_time = time();
    
    // log_message('debug', "STK Query - HTTP: {$http_code}");
    
    // Handle rate limiting with exponential backoff
    if ($http_code == 429) {
        // log_message('warning', "Rate limited on attempt {$attempt}");
        
        // Calculate exponential backoff time
        $backoff_time = min(pow(2, $attempt), 60); // 2,4,8,16,32,60 max
        
        return array(
            'success' => false,  // Changed from true to false to indicate rate limited
            'status' => 'pending',
            'message' => "Payment gateway is busy. Will retry in {$backoff_time} seconds.",
            'retry_after' => $backoff_time
        );
    }
    
    // Handle Incapsula blocking
    if ($http_code == 403) {
        // log_message('error', "Blocked by Incapsula on attempt {$attempt}");
        return array(
            'success' => false,
            'status' => 'error', 
            'message' => 'Temporarily unable to check payment status due to security restrictions. Please try again in a few minutes.'
        );
    }
    
    if ($curl_error) {
        // log_message('error', "cURL Error: {$curl_error}");
        return array(
            'success' => false,
            'status' => 'error', 
            'message' => 'Network connection failed'
        );
    }
    
    if ($http_code != 200) {
        // log_message('error', "HTTP Error {$http_code} for STK query");
        return array(
            'success' => false,
            'status' => 'error', 
            'message' => 'Payment gateway returned error'
        );
    }
    
    $result = json_decode($response, true);
    // log_message('debug', 'STK Query Result: ' . print_r($result, true));
    
    if (isset($result['ResponseCode']) && $result['ResponseCode'] == '0') {
        if (isset($result['ResultCode'])) {
            switch ($result['ResultCode']) {
                case '0':
                    // Payment successful - Try to get receipt from database first
                    $receipt_number = null;
                    
                    // FIRST: Check if we already have this transaction in database with callback data
                    $ci = &get_instance();
                    $ci->load->model('sendsmsmail_model');
                    $transaction = $ci->sendsmsmail_model->get_transaction_by_checkout_id($checkout_request_id);
                    
                    if ($transaction && !empty($transaction->callback_raw)) {
                        // We already have callback data from payment_callback()
                        $callback_data = json_decode($transaction->callback_raw, true);
                        
                        // Extract receipt from stored callback
                        if (isset($callback_data['Body']['stkCallback']['CallbackMetadata']['Item'])) {
                            foreach ($callback_data['Body']['stkCallback']['CallbackMetadata']['Item'] as $item) {
                                if ($item['Name'] == 'MpesaReceiptNumber') {
                                    $receipt_number = $item['Value'];
                                    // log_message('info', "✅ Found receipt from stored callback: {$receipt_number}");
                                    break;
                                }
                            }
                        }
                        
                        return array(
                            'success' => true,
                            'status' => 'completed',
                            'receipt_number' => $receipt_number,
                            'message' => 'Payment completed successfully!',
                            'callback_data' => $callback_data // Return the full callback data
                        );
                    }
                    
                    // If no stored callback, generate sandbox receipt
                    $receipt_number = 'SBX_' . date('YmdHis') . '_' . substr($checkout_request_id, -4);
                    // log_message('info', "Generated sandbox receipt: {$receipt_number}");
                    
                    return array(
                        'success' => true,
                        'status' => 'completed',
                        'receipt_number' => $receipt_number,
                        'message' => 'Payment completed successfully!',
                        'callback_data' => $result // Return query result as fallback
                    );
                    
                    
                case '1':
                    return array(
                        'success' => true,
                        'status' => 'failed', 
                        'message' => 'Insufficient balance'
                    );
                    
                case '1032':
                    return array(
                        'success' => true,
                        'status' => 'cancelled',
                        'message' => 'Payment cancelled'
                    );
                    
                case '1037':
                    return array(
                        'success' => true, 
                        'status' => 'timeout',
                        'message' => 'Payment timed out'
                    );
                    
                case '2001':
                case '2006':
                case '4999':
                    return array(
                        'success' => true,
                        'status' => 'pending',
                        'message' => 'Waiting for payment completion...'
                    );
                    
                default:
                    $result_desc = isset($result['ResultDesc']) ? $result['ResultDesc'] : 'Processing';
                    return array(
                        'success' => true,
                        'status' => 'pending',
                        'message' => $result_desc
                    );
            }
        }
    }
    
    return array(
        'success' => false,
        'status' => 'error', 
        'message' => 'Unexpected response from payment gateway'
    );
}
    
    /**
     * Format phone number to 2547XXXXXXXX
     */
    private function format_phone_number($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to 254 format
        if (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
            return '254' . $phone;
        } elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            return '254' . substr($phone, 1);
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            return $phone;
        }
        
        return false;
    }
    
    /**
     * Calculate SMS units based on amount
     */
    public function calculate_sms_units($amount) {
        return floor($amount / $this->config['sms_rate']);
    }
}