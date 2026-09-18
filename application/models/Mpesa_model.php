<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa_model extends CI_Model {

    /**
     * Retrieves a specific user's details from the login_credential table.
     * FIX: Corrected table name from 'login_crdential' to 'login_credential'.
     * NOTE: The duplicate, more complex get_user function was removed to prevent a fatal error.
     * This is the correct function for fetching the currently logged-in user's data.
     *
     * @param int $user_id The unique ID of the user.
     * @return array|null User data as an array or null if not found.
     */
    public function get_user($user_id) {
        return $this->db->get_where('login_credential', ['id' => $user_id])->row_array();
    }

    /**
     * Inserts a new transaction record into the database with a 'pending' status.
     *
     * @param int    $user_id             The ID of the user initiating the transaction.
     * @param float  $amount              The transaction amount.
     * @param string $phone               The phone number used for the transaction.
     * @param string $checkout_request_id The unique ID from Safaricom for the STK push.
     * @return int The ID of the newly inserted transaction record.
     */
    public function log_transaction($user_id, $amount, $phone, $checkout_request_id) {
        $data = [
            'user_id'             => $user_id,
            'amount'              => $amount,
            'phone_number'        => $phone,
            'checkout_request_id' => $checkout_request_id,
            'status'              => 'pending',
            // 'created_at' is assumed to be handled by the database timestamp default
        ];
        $this->db->insert('sms_transactions', $data);
        return $this->db->insert_id();
    }
    
    /**
     * Fetches a transaction record using the CheckoutRequestID.
     *
     * @param string $checkout_request_id The unique ID from Safaricom.
     * @return array|null The transaction data or null if not found.
     */
    public function get_transaction_by_checkout_id($checkout_request_id) {
        return $this->db->get_where('sms_transactions', ['checkout_request_id' => $checkout_request_id])->row_array();
    }

    /**
     * Updates the status of a transaction (e.g., to 'failed').
     *
     * @param string $checkout_request_id The unique ID from Safaricom.
     * @param string $status              The new status ('failed', 'cancelled', etc.).
     * @param string|null $receipt_number The M-Pesa receipt number, if available.
     * @return void
     */
    public function update_transaction_status($checkout_request_id, $status, $receipt_number) {
        $this->db->where('checkout_request_id', $checkout_request_id);
        $this->db->update('sms_transactions', [
            'status' => $status, 
            'receipt_number' => $receipt_number
            // 'updated_at' is assumed to be handled by the database timestamp default
        ]);
    }

    /**
     * Handles a successful transaction by updating its status and crediting the user's account.
     * This function uses a database transaction to ensure data integrity.
     *
     * @param string $checkout_request_id The unique ID from Safaricom.
     * @param string $receipt_number      The official M-Pesa receipt number from the callback.
     * @return bool True on success, False on failure.
     */
    public function update_successful_transaction($checkout_request_id, $receipt_number) {
        $this->db->trans_start(); // Start a database transaction

        // 1. Get transaction details to ensure it exists and is pending
        $transaction = $this->get_transaction_by_checkout_id($checkout_request_id);

        // Only proceed if the transaction exists and has not been processed before
        if ($transaction && $transaction['status'] === 'pending') {
            
            // 2. Update the transaction record to 'completed'
            $this->db->where('id', $transaction['id']);
            $this->db->update('sms_transactions', [
                'status'           => 'completed',
                'receipt_number'   => $receipt_number,
                'transaction_date' => date('Y-m-d H:i:s') // Set completion date to now
            ]);

            // 3. Add credits to the user's account (e.g., 1 KES = 1 SMS credit)
            $amount_to_credit = (int) $transaction['amount'];
            $user_id = $transaction['user_id'];
            
            // Use CodeIgniter's set() method with the third parameter as FALSE
            // to prevent escaping, allowing the SQL function to run.
            $this->db->where('id', $user_id);
            $this->db->set('sms_credit', "sms_credit + {$amount_to_credit}", FALSE);
            $this->db->update('login_credential');
        }

        $this->db->trans_complete(); // Complete the transaction

        // Check if the transaction was successful
        return $this->db->trans_status();
    }
    
    /**
     * ADDED: Decrements the SMS credit for a user.
     *
     * @param int $user_id The ID of the user.
     * @param int $amount_to_deduct The number of credits to deduct.
     * @return bool
     */
    public function deduct_sms_credit($user_id, $amount_to_deduct = 1) {
        $this->db->where('id', $user_id);
        $this->db->set('sms_credit', "sms_credit - {$amount_to_deduct}", FALSE);
        return $this->db->update('login_credential');
    }


    /**
     * Retrieves the completed billing history for a specific user.
     *
     * @param int $user_id The user's ID.
     * @return array An array of completed transaction records.
     */
    public function get_billing_history($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('status', 'completed');
        $this->db->order_by('transaction_date', 'DESC');
        return $this->db->get('sms_transactions')->result_array();
    }
}