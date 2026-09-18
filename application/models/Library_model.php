<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Library_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

   public function book_save($data)
{
    $branch_id = $this->application_model->get_branch_id();
    
    // Get category code
    $category_code = $this->get_category_code($data['category_id']);
    $grade_level = $data['grade_level'];
    
    $arraybook = array(
        'branch_id' => $branch_id,
        'title' => $data['book_title'],
        'isbn_no' => $data['isbn_no'],
        'author' => $data['author'],
        'edition' => $data['edition'],
        'purchase_date' => date('Y-m-d', strtotime($data['purchase_date'])),
        'category_id' => $data['category_id'],
        'grade_level' => $grade_level,
        'publisher' => $data['publisher'],
        'description' => $data['description'],
        'price' => $data['price'],
        'total_stock' => $data['total_stock'],
        'total_copies' => $data['total_stock'],
        'available_copies' => $data['total_stock'],
    );
    
    if ($_FILES['cover_image']['name'] != "") {
        $config['upload_path'] = 'uploads/book_cover/';
        $config['allowed_types'] = 'jpg|png';
        $config['overwrite'] = false;
        $config['file_name'] = 'cover_image_' . app_generate_hash();
        $this->upload->initialize($config);
        if ($this->upload->do_upload("cover_image")) {
            $arraybook['cover'] = $this->upload->data('file_name');
        }
    }
    
    if (!isset($data['book_id'])) {
        // ========== INSERT NEW BOOK ==========
        $this->db->insert('book', $arraybook);
        $book_id = $this->db->insert_id();
        
        // Get the sequence number for this category and grade
        $sequence = $this->get_next_sequence($category_code, $grade_level, $branch_id);
        
        // ========== GENERATE COPIES WITH NEW FORMAT ==========
        $total_copies = (int)$data['total_stock'];
        
        for ($i = 1; $i <= $total_copies; $i++) {
            // Format: [CATEGORY_CODE][GRADE]-[SEQUENCE]-C[COPY]
            // Example: SCI7-001-C01
            $copy_number = $category_code . $grade_level . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT) . '-C' . str_pad($i, 2, '0', STR_PAD_LEFT);
            
            // Generate barcode
            $barcode = $this->generate_barcode_from_copy_number($copy_number, $branch_id, $book_id, $i);
            
           $copy_data = array(
                'book_id' => $book_id,
                'copy_number' => $copy_number,
                'barcode' => $copy_number,  // ← USE THE SAME VALUE FOR BARCODE
                'status' => 'available',
                'condition' => 'new',
                'acquisition_date' => date('Y-m-d'),
                'price' => $data['price'],
                'branch_id' => $branch_id
            );
            
            $this->db->insert('book_copies', $copy_data);
        }
        // ========== END BARCODE GENERATION ==========
        
    } else {
        // ========== UPDATE EXISTING BOOK ==========
        if ($_FILES['cover_image']['name'] != "") {
            if (!empty($data['old_file'])) {
                $file = 'uploads/book_cover/' . $data['old_file'];
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        }
        $this->db->where('id', $data['book_id']);
        $this->db->update('book', $arraybook);
        $book_id = $data['book_id'];
        
        // Update copies if needed (handle additional copies)
        $current_copies = $this->db->select('COUNT(*) as count')
                                   ->where('book_id', $book_id)
                                   ->get('book_copies')
                                   ->row()
                                   ->count ?? 0;
        
        $total_copies = (int)$data['total_stock'];
        
        if ($total_copies > $current_copies) {
            $sequence = $this->get_next_sequence($category_code, $grade_level, $branch_id);
            for ($i = $current_copies + 1; $i <= $total_copies; $i++) {
                $copy_number = $category_code . $grade_level . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT) . '-C' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $barcode = $this->generate_barcode_from_copy_number($copy_number, $branch_id, $book_id, $i);
                
                $copy_data = array(
                    'book_id' => $book_id,
                    'copy_number' => $copy_number,
                    'barcode' => $barcode,
                    'status' => 'available',
                    'condition' => 'new',
                    'acquisition_date' => date('Y-m-d'),
                    'price' => $data['price'],
                    'branch_id' => $branch_id
                );
                $this->db->insert('book_copies', $copy_data);
            }
        }
        
        // Update available_copies
        $this->db->set('total_copies', $total_copies);
        $this->db->set('available_copies', $total_copies - ($this->db->select('COUNT(*)')->where('status', 'issued')->get('book_copies')->row()->count ?? 0));
        $this->db->where('id', $book_id);
        $this->db->update('book');
    }
    
    return true;
}

/**
 * Get category code from category ID
 */
private function get_category_code($category_id)
{
    $category_name = $this->db->select('name')->where('id', $category_id)->get('book_category')->row()->name ?? '';
    
    $codes = array(
        // ========== CORE SUBJECTS (CBC & 8-4-4) ==========
        'English' => 'ENG',
        'Kiswahili' => 'KIS',
        'Mathematics' => 'MAT',
        'Maths' => 'MAT',
        
        // ========== CBC SPECIFIC ==========
        'Integrated Science' => 'SCI',
        'Science' => 'SCI',
        'Sciences' => 'SCI',
        'Health Education' => 'HED',
        'Social Studies' => 'SST',
        'Religious Education' => 'CRE',
        'Christian Religious Education' => 'CRE',
        'Islamic Religious Education' => 'IRE',
        'Hindu Religious Education' => 'HRE',
        'Business Studies' => 'BUS',
        'Agriculture' => 'AGR',
        'Life Skills' => 'LSK',
        'Sports and Physical Education' => 'PHE',
        
        // ========== CBC JUNIOR SCHOOL ELECTIVES ==========
        'Visual Arts' => 'ART',
        'Performing Arts' => 'PER',
        'Home Science' => 'HSC',
        'Computer Science' => 'COM',
        'Computer Studies' => 'COM',
        'Information Technology' => 'ICT',
        'Foreign Languages' => 'FOR',
        'French' => 'FRN',
        'German' => 'GRM',
        'Mandarin' => 'MAN',
        'Arabic' => 'ARB',
        'Kenya Sign Language' => 'KSL',
        'Indigenous Languages' => 'INL',
        
        // ========== CBC SENIOR SCHOOL - STEM ==========
        'Physics' => 'PHY',
        'Chemistry' => 'CHE',
        'Biology' => 'BIO',
        'Additional Mathematics' => 'AMT',
        'Computer Technology' => 'COT',
        
        // ========== CBC SENIOR SCHOOL - HUMANITIES ==========
        'History and Citizenship' => 'HIS',
        'Geography' => 'GEO',
        'History' => 'HIS',
        
        // ========== CBC SENIOR SCHOOL - ARTS & SPORTS ==========
        'Music' => 'MUS',
        'Dance' => 'DAN',
        'Theatre and Film' => 'THF',
        'Fine Art' => 'FIA',
        'Design' => 'DSN',
        
        // ========== CBC SENIOR SCHOOL - VOCATIONAL ==========
        'Aviation Technology' => 'AVI',
        'Building and Construction' => 'BCN',
        'Electrical Technology' => 'ELT',
        'Metal Technology' => 'MET',
        'Wood Technology' => 'WDT',
        'Power Mechanics' => 'POM',
        'Automotive Technology' => 'AUT',
        'Fashion and Design' => 'FAD',
        'Food and Beverage' => 'FBV',
        'Hospitality' => 'HOS',
        'Media Studies' => 'MED',
        'Journalism' => 'JRN',
        'Banking and Finance' => 'BNK',
        'Insurance' => 'INS',
        
        // ========== 8-4-4 PRIMARY SUBJECTS ==========
        'Science and Agriculture' => 'SAG',
        'Social Studies and Religion' => 'SSR',
        'Creative Arts' => 'CAR',
        'Physical Education' => 'PHE',
        
        // ========== 8-4-4 SECONDARY SUBJECTS ==========
        'Biology' => 'BIO',
        'Physics' => 'PHY',
        'Chemistry' => 'CHE',
        'History and Government' => 'HIS',
        'Geography' => 'GEO',
        'Christian Religious Education' => 'CRE',
        'Islamic Religious Education' => 'IRE',
        'Hindu Religious Education' => 'HRE',
        'Business Studies' => 'BUS',
        'Agriculture' => 'AGR',
        'Home Science' => 'HSC',
        'Art and Design' => 'ART',
        'Music' => 'MUS',
        'French' => 'FRN',
        'German' => 'GRM',
        'Arabic' => 'ARB',
        'Kenya Sign Language' => 'KSL',
        'Computer Studies' => 'COM',
        'Electricity' => 'ELE',
        'Metal Work' => 'MET',
        'Wood Work' => 'WDT',
        'Power Mechanics' => 'POM',
        'Drawing and Design' => 'DRW',
        'Aviation Technology' => 'AVI',
        'Building Construction' => 'BCN',
        
        // ========== ADDITIONAL ==========
        'Environmental Education' => 'ENV',
        'Guidance and Counselling' => 'GUC',
        'Entrepreneurship' => 'ENT',
        'Financial Literacy' => 'FNL',
        'Community Service Learning' => 'CSL',
        'Pasteur' => 'PAS',
    );
    
    return $codes[$category_name] ?? substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $category_name)), 0, 3);
}

/**
 * Get next sequence number for category and grade
 */
private function get_next_sequence($category_code, $grade_level, $branch_id)
{
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(copy_number, '-', 2), '-', -1) AS UNSIGNED)) as max_seq
            FROM book_copies bc
            JOIN book b ON b.id = bc.book_id
            WHERE bc.copy_number LIKE '{$category_code}{$grade_level}-%'
            AND b.branch_id = {$branch_id}";
    
    $result = $this->db->query($sql)->row();
    $next_seq = ($result->max_seq ?? 0) + 1;
    
    return $next_seq;
}

/**
 * Generate barcode from copy number
 */
private function generate_barcode_from_copy_number($copy_number, $branch_id, $book_id, $copy_num)
{
    // Simple barcode: LB + Branch + Book ID + Copy
    return 'LB' . str_pad($branch_id, 3, '0', STR_PAD_LEFT) . 
                str_pad($book_id, 5, '0', STR_PAD_LEFT) . 
                str_pad($copy_num, 2, '0', STR_PAD_LEFT);
}
    
        /**
     * Generate unique barcode for a book copy
     */
    private function generate_unique_barcode($branch_id, $book_id, $copy_number)
    {
        // Format: LB + Branch ID (3 digits) + Book ID (5 digits) + Copy Number (3 digits) + Checksum
        $prefix = 'LB';
        $branch_padded = str_pad($branch_id, 3, '0', STR_PAD_LEFT);
        $book_padded = str_pad($book_id, 5, '0', STR_PAD_LEFT);
        $copy_padded = str_pad($copy_number, 3, '0', STR_PAD_LEFT);
        
        $barcode = $prefix . $branch_padded . $book_padded . $copy_padded;
        
        // Add checksum digit for validation
        $checksum = $this->calculate_checksum($barcode);
        
        return $barcode . $checksum;
    }
    
    /**
     * Calculate simple checksum for barcode validation
     */
    private function calculate_checksum($barcode)
    {
        $sum = 0;
        for ($i = 0; $i < strlen($barcode); $i++) {
            $sum += ord($barcode[$i]) * ($i + 1);
        }
        return $sum % 10;
    }
    
    /**
     * Generate copy number (e.g., BK0001-C001)
     */
    private function generate_copy_number($book_id, $copy_number)
    {
        return 'BK' . str_pad($book_id, 4, '0', STR_PAD_LEFT) . '-C' . str_pad($copy_number, 3, '0', STR_PAD_LEFT);
    }

    public function category_save($data)
    {
        $arrayData = array(
            'name' => $data['name'],
            'branch_id' => $this->application_model->get_branch_id(),
        );
        if (!isset($arrayData['category_id'])) {
            $this->db->insert('book_category', $arrayData);
        } else {
            $this->db->where('id', $arrayData['category_id']);
            $this->db->update('book_category', $arrayData);
        }
    }

 public function issued_save($data)
{
    $branch_id = $this->application_model->get_branch_id();
    
    // ========== STEP 1: GET THE SCANNED BARCODE ==========
    $scanned_barcode = isset($data['barcode']) ? $data['barcode'] : '';
    
    // Also check for scanned_copy_number from the form
    if (empty($scanned_barcode) && isset($data['scanned_copy_number'])) {
        $scanned_barcode = $data['scanned_copy_number'];
    }
    
    log_message('debug', '=== ISSUED_SAVE ===');
    log_message('debug', 'Scanned Barcode: ' . $scanned_barcode);
    log_message('debug', 'Book ID from form: ' . $data['book_id']);
    
    // ========== STEP 2: FIND THE SPECIFIC COPY BY BARCODE ==========
    $copy = null;
    
    if (!empty($scanned_barcode)) {
        // Try to find by barcode first
        $copy = $this->db->select('id, copy_number, status, book_id, branch_id')
                         ->where('barcode', $scanned_barcode)
                         ->get('book_copies')
                         ->row();
        
        // If not found by barcode, try by copy_number
        if (!$copy) {
            $copy = $this->db->select('id, copy_number, status, book_id, branch_id')
                             ->where('copy_number', $scanned_barcode)
                             ->get('book_copies')
                             ->row();
        }
    }
    
    // ========== STEP 3: VERIFY THE FOUND COPY ==========
    if ($copy) {
        log_message('debug', 'Found copy: ID=' . $copy->id . ', Copy Number=' . $copy->copy_number . ', Status=' . $copy->status);
        
        // Verify branch isolation
        if (!is_superadmin_loggedin() && $copy->branch_id != $branch_id) {
            $this->session->set_flashdata('error', 'This book copy belongs to a different branch.');
            return false;
        }
        
        // Verify the copy belongs to the selected book
        if ($copy->book_id != $data['book_id']) {
            $this->session->set_flashdata('error', 'Barcode does not match the selected book.');
            return false;
        }
        
        // Check if copy is available
        if ($copy->status != 'available') {
            $this->session->set_flashdata('error', 'This copy ('.$copy->copy_number.') is already issued or unavailable.');
            return false;
        }
        
        $copy_id = $copy->id;
        $copy_number = $copy->copy_number;
        
    } else {
        // ========== STEP 4: FALLBACK - No barcode scanned ==========
        log_message('debug', 'No barcode provided or copy not found, using first available copy');
        
        $copy = $this->db->select('id, copy_number')
                         ->where('book_id', $data['book_id'])
                         ->where('status', 'available')
                         ->limit(1)
                         ->get('book_copies')
                         ->row();
        
        if (!$copy) {
            $this->session->set_flashdata('error', 'No available copies for this book.');
            return false;
        }
        
        $copy_id = $copy->id;
        $copy_number = $copy->copy_number;
    }
    
    // ========== STEP 5: CREATE ISSUE RECORD ==========
    $arrayIssue = array(
        'branch_id' => $branch_id,
        'book_id' => $data['book_id'],
        'copy_id' => $copy_id,
        'user_id' => $data['user_id'],
        'role_id' => $data['role_id'],
        'date_of_issue' => date("Y-m-d"),
        'date_of_expiry' => date("Y-m-d", strtotime($data['date_of_expiry'])),
        'issued_by' => get_loggedin_user_id(),
        'status' => 1,
        'session_id' => get_session_id(),
    );
    
    $this->db->insert('book_issues', $arrayIssue);
    $issue_id = $this->db->insert_id();
    
    log_message('debug', 'Issue created - ID: ' . $issue_id . ', Copy: ' . $copy_number);
    
    // ========== STEP 6: UPDATE COPY STATUS ==========
    $this->db->where('id', $copy_id);
    $this->db->update('book_copies', array('status' => 'issued'));
    
    // ========== STEP 7: UPDATE BOOK COUNTS ==========
    $this->db->set('issued_copies', 'issued_copies+1', FALSE);
    $this->db->set('available_copies', 'available_copies-1', FALSE);
    $this->db->where('id', $arrayIssue['book_id']);
    $this->db->update('book');
    
    return true;
}

    // get book issue list
    public function getBookIssueList($id = '')
    {
        $this->db->select('bi.*, b.title, b.cover, b.isbn_no, b.edition, b.author, 
                      br.name as branch_name, c.name as category_name, roles.name as role_name,
                      bc.copy_number')  // ← MUST include this
             ->from('book_issues as bi')
             ->join('book as b', 'b.id = bi.book_id', 'left')
             ->join('branch as br', 'br.id = bi.branch_id', 'left')
             ->join('roles', 'roles.id = bi.role_id', 'left')
             ->join('book_category as c', 'c.id = b.category_id', 'left')
             ->join('book_copies bc', 'bc.id = bi.copy_id', 'left');  // ← MUST include this join

        if (!is_superadmin_loggedin()) {
            $this->db->where('bi.branch_id', get_loggedin_branch_id());
        }
        $this->db->where('bi.session_id', get_session_id());
        if ($id != '') {
            $this->db->where('bi.id', $id);
            return $this->db->get()->row_array();
        } else {
            $this->db->order_by('bi.id', 'desc');
            return $this->db->get()->result_array();
        }
    }

    // get book issue list
    public function get_book_issue_list()
    {
        $this->db->select('bi.*,b.title,b.cover,b.isbn_no,b.edition,c.name as category_name');
        $this->db->from('book_issues as bi');
        $this->db->join('book as b', 'b.id = bi.book_id', 'left');
        $this->db->join('book_category as c', 'c.id = b.category_id', 'left');
        if (is_parent_loggedin()) {
            $this->db->where('bi.user_role', 'student');
            $this->db->where('bi.user_id', get_activeChildren_id());
        } else {
            $this->db->where('bi.user_role', get_loggedin_user_type());
            $this->db->where('bi.user_id', get_loggedin_user_id());
        }
        $this->db->where('bi.session_id', get_session_id());
        $this->db->order_by('bi.id', 'desc');
        return $this->db->get();
    }

        // ========== ENHANCED LIBRARY METHODS ==========
    
    /**
     * Generate unique barcode for a book copy
     */
    public function generate_barcode($book_id, $branch_id)
    {
        $prefix = 'LB';
        $branch_code = str_pad($branch_id, 3, '0', STR_PAD_LEFT);
        $timestamp = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $branch_code . $timestamp . $random;
    }
    
    /**
     * Add a new book copy
     */
    public function add_book_copy($book_id, $copy_number, $branch_id, $condition = 'good', $price = 0)
    {
        $barcode = $this->generate_barcode($book_id, $branch_id);
        
        $data = array(
            'book_id' => $book_id,
            'copy_number' => $copy_number,
            'barcode' => $barcode,
            'status' => 'available',
            'condition' => $condition,
            'acquisition_date' => date('Y-m-d'),
            'price' => $price,
            'branch_id' => $branch_id
        );
        
        $this->db->insert('book_copies', $data);
        $copy_id = $this->db->insert_id();
        
        // Update total_copies and available_copies in book table
        $this->db->set('total_copies', 'total_copies + 1', FALSE);
        $this->db->set('available_copies', 'available_copies + 1', FALSE);
        $this->db->where('id', $book_id);
        $this->db->update('book');
        
        $this->log_audit('book_copy_added', 'book_copies', $copy_id, null, $data);
        
        return $copy_id;
    }
    
    /**
     * Calculate overdue fine for a book issue
     */
    public function calculate_fine($issue_id)
    {
        $issue = $this->db->get_where('book_issues', array('id' => $issue_id))->row();
        
        if (!$issue || $issue->status != 1) {
            return 0;
        }
        
        $settings = $this->get_library_settings($issue->branch_id);
        $fine_per_day = $settings['fine_per_day'] ?? 5.00;
        
        $today = date('Y-m-d');
        $expiry = $issue->date_of_expiry;
        
        if ($today <= $expiry) {
            return 0;
        }
        
        $days_overdue = floor((strtotime($today) - strtotime($expiry)) / 86400);
        $calculated_fine = $days_overdue * $fine_per_day;
        
        // Update the issue record
        $this->db->where('id', $issue_id);
        $this->db->update('book_issues', array(
            'days_overdue' => $days_overdue,
            'calculated_fine' => $calculated_fine
        ));
        
        return $calculated_fine;
    }
    
    /**
     * Check if user can borrow more books
     */
        /**
     * Check if user can borrow more books
     */
    public function check_borrowing_limit($user_id, $role_id, $branch_id)
    {
        // Get library settings for this branch
        $settings = $this->get_library_settings($branch_id);
        
        // Determine limit based on role
        switch($role_id) {
            case 7: // Student
                $limit = isset($settings['max_books_student']) ? (int)$settings['max_books_student'] : 3;
                break;
            case 3: // Teacher
                $limit = isset($settings['max_books_teacher']) ? (int)$settings['max_books_teacher'] : 5;
                break;
            default: // Staff or other roles
                $limit = isset($settings['max_books_staff']) ? (int)$settings['max_books_staff'] : 3;
        }
        
        // Count currently issued books (status = 1 means issued, not returned)
        $current_borrowed = $this->db->select('COUNT(*) as count')
                                     ->from('book_issues')
                                     ->where('user_id', $user_id)
                                     ->where('role_id', $role_id)
                                     ->where('branch_id', $branch_id)
                                     ->where('status', 1) // Only count issued books (not returned/rejected)
                                     ->get()
                                     ->row()
                                     ->count ?? 0;
        
        return array(
            'can_borrow' => $current_borrowed < $limit,
            'current_borrowed' => $current_borrowed,
            'limit' => $limit,
            'remaining' => $limit - $current_borrowed
        );
    }
    
    /**
     * Reserve a book
     */
      public function reserve_book($book_id, $user_id, $role_id, $branch_id)
    {
        // Verify book belongs to the user's branch
        $book = $this->db->select('branch_id, available_copies')
                         ->where('id', $book_id)
                         ->get('book')
                         ->row();
        
        if (!$book) {
            return array('success' => false, 'message' => translate('book_not_found'));
        }
        
        // Branch isolation check
        if ($book->branch_id != $branch_id && !is_superadmin_loggedin()) {
            return array('success' => false, 'message' => translate('book_not_available_in_your_branch'));
        }
        
        // Check if book is available
        if ($book->available_copies > 0) {
            return array('success' => false, 'message' => translate('book_available_issue_directly'));
        }
        
        // Check for existing reservation
        $existing = $this->db->where('book_id', $book_id)
                              ->where('user_id', $user_id)
                              ->where('branch_id', $branch_id)
                              ->where('status', 'pending')
                              ->get('book_reservations')
                              ->num_rows();
        
        if ($existing > 0) {
            return array('success' => false, 'message' => translate('already_reserved'));
        }
        
        $settings = $this->get_library_settings($branch_id);
        $expiry_days = $settings['reservation_expiry_days'] ?? 3;
        
        $data = array(
            'book_id' => $book_id,
            'user_id' => $user_id,
            'role_id' => $role_id,
            'reservation_date' => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime("+{$expiry_days} days")),
            'status' => 'pending',
            'branch_id' => $branch_id
        );
        
        $this->db->insert('book_reservations', $data);
        
        if ($this->db->affected_rows() > 0) {
            return array('success' => true, 'message' => translate('book_reserved_successfully'));
        }
        
        return array('success' => false, 'message' => translate('reservation_failed'));
    }
    
    /**
     * Get library settings for a branch
     */
    public function get_library_settings($branch_id)
    {
        $settings = $this->db->get_where('library_settings', array('branch_id' => $branch_id))->row_array();
        
        if (empty($settings)) {
            // Default settings
            $settings = array(
                'fine_per_day' => 5.00,
                'max_borrow_days' => 14,
                'max_books_student' => 3,
                'max_books_teacher' => 5,
                'max_books_staff' => 3,
                'reservation_expiry_days' => 3,
                'auto_calculate_fine' => 1,
                'sms_notification_enabled' => 0
            );
        }
        
        return $settings;
    }
    
    /**
     * Update library settings
     */
        /**
     * Update library settings for a branch
     */
    public function update_library_settings($branch_id, $data)
    {
        // Check if settings already exist for this branch
        $existing = $this->db->where('branch_id', $branch_id)->get('library_settings')->row();
        
        if ($existing) {
            // Update existing record
            $this->db->where('branch_id', $branch_id);
            $result = $this->db->update('library_settings', $data);
        } else {
            // Insert new record
            $data['branch_id'] = $branch_id;
            $result = $this->db->insert('library_settings', $data);
        }
        
        // Log the action for audit
        $this->log_audit('settings_updated', 'library_settings', $branch_id, null, $data);
        
        return $result;
    }
    /**
     * Log audit trail
     */
    public function log_audit($action, $table_name, $record_id, $old_data = null, $new_data = null)
    {
        $log_data = array(
            'action' => $action,
            'table_name' => $table_name,
            'record_id' => $record_id,
            'old_data' => $old_data ? json_encode($old_data) : null,
            'new_data' => $new_data ? json_encode($new_data) : null,
            'user_id' => get_loggedin_user_id(),
            'user_role' => get_loggedin_user_type(),
            'ip_address' => $this->input->ip_address(),
            'branch_id' => get_loggedin_branch_id()
        );
        
        $this->db->insert('library_audit_logs', $log_data);
    }
    
    /**
     * Get overdue books list
     */
    public function get_overdue_books($branch_id = null)
    {
        $this->db->select('bi.*, b.title, b.book_code, u.name as user_name, 
                          DATEDIFF(CURDATE(), bi.date_of_expiry) as days_overdue')
                 ->from('book_issues bi')
                 ->join('book b', 'b.id = bi.book_id')
                 ->join('staff u', 'u.id = bi.user_id AND bi.role_id != 7', 'left')
                 ->where('bi.status', 1)
                 ->where('bi.date_of_expiry <', date('Y-m-d'));
        
        if ($branch_id) {
            $this->db->where('bi.branch_id', $branch_id);
        } elseif (!is_superadmin_loggedin()) {
            $this->db->where('bi.branch_id', get_loggedin_branch_id());
        }
        
        $this->db->order_by('days_overdue', 'DESC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get popular books (most borrowed)
     */
    public function get_popular_books($branch_id = null, $limit = 10)
    {
        $this->db->select('b.id, b.title, b.book_code, b.cover, COUNT(bi.id) as borrow_count')
                 ->from('book_issues bi')
                 ->join('book b', 'b.id = bi.book_id')
                 ->group_by('bi.book_id')
                 ->order_by('borrow_count', 'DESC')
                 ->limit($limit);
        
        if ($branch_id) {
            $this->db->where('bi.branch_id', $branch_id);
        } elseif (!is_superadmin_loggedin()) {
            $this->db->where('bi.branch_id', get_loggedin_branch_id());
        }
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get circulation statistics
     */
    public function get_circulation_stats($branch_id = null, $start_date = null, $end_date = null)
    {
        if (!$start_date) {
            $start_date = date('Y-m-01');
        }
        if (!$end_date) {
            $end_date = date('Y-m-t');
        }
        
        $this->db->select("
            COUNT(CASE WHEN bi.status = 1 AND bi.date_of_issue BETWEEN '{$start_date}' AND '{$end_date}' THEN 1 END) as total_issued,
            COUNT(CASE WHEN bi.status = 3 AND bi.return_date BETWEEN '{$start_date}' AND '{$end_date}' THEN 1 END) as total_returned,
            SUM(CASE WHEN bi.return_date BETWEEN '{$start_date}' AND '{$end_date}' THEN bi.fine_amount ELSE 0 END) as total_fines_collected,
            COUNT(DISTINCT bi.user_id) as active_borrowers
        ");
        
        $this->db->from('book_issues bi');
        
        if ($branch_id) {
            $this->db->where('bi.branch_id', $branch_id);
        } elseif (!is_superadmin_loggedin()) {
            $this->db->where('bi.branch_id', get_loggedin_branch_id());
        }
        
        return $this->db->get()->row_array();
    }
    
    /**
     * Get all available copies for a book
     */
    public function get_available_copies($book_id, $branch_id)
    {
        return $this->db->select('*')
                        ->from('book_copies')
                        ->where('book_id', $book_id)
                        ->where('branch_id', $branch_id)
                        ->where('status', 'available')
                        ->get()
                        ->result_array();
    }
    
    /**
     * Issue a specific copy of a book
     */
    public function issue_book_copy($book_id, $copy_id, $user_id, $role_id, $branch_id, $due_date)
    {
        // Check if copy is available
        $copy = $this->db->get_where('book_copies', array('id' => $copy_id, 'status' => 'available'))->row();
        
        if (!$copy) {
            return array('success' => false, 'message' => 'This copy is not available.');
        }
        
        // Check borrowing limit
        $limit_check = $this->check_borrowing_limit($user_id, $role_id, $branch_id);
        if (!$limit_check['can_borrow']) {
            return array('success' => false, 'message' => 'Borrowing limit reached. Maximum ' . $limit_check['limit'] . ' books.');
        }
        
        // Update copy status
        $this->db->where('id', $copy_id);
        $this->db->update('book_copies', array('status' => 'issued'));
        
        // Create issue record
        $issue_data = array(
            'book_id' => $book_id,
            'copy_id' => $copy_id,
            'user_id' => $user_id,
            'role_id' => $role_id,
            'date_of_issue' => date('Y-m-d'),
            'date_of_expiry' => $due_date,
            'issued_by' => get_loggedin_user_id(),
            'status' => 1,
            'session_id' => get_session_id(),
            'branch_id' => $branch_id
        );
        
        $this->db->insert('book_issues', $issue_data);
        $issue_id = $this->db->insert_id();
        
        // Update book available_copies
        $this->db->set('available_copies', 'available_copies - 1', FALSE);
        $this->db->set('issued_copies', 'issued_copies + 1', FALSE);
        $this->db->where('id', $book_id);
        $this->db->update('book');
        
        $this->log_audit('book_issued', 'book_issues', $issue_id, null, $issue_data);
        
        return array('success' => true, 'message' => 'Book issued successfully.', 'issue_id' => $issue_id);
    }
    
    /**
     * Return a book copy and calculate fine
     */
    public function return_book_copy($issue_id, $return_date = null, $fine_amount = null, $condition = null)
    {
        if (!$return_date) {
            $return_date = date('Y-m-d');
        }
        
        $issue = $this->db->get_where('book_issues', array('id' => $issue_id))->row();
        
        if (!$issue || $issue->status != 1) {
            return array('success' => false, 'message' => 'Invalid issue record.');
        }
        
        // Calculate fine if not provided
        if ($fine_amount === null) {
            $fine_amount = $this->calculate_fine($issue_id);
        }
        
        // Update copy condition if provided
        if ($condition && $issue->copy_id) {
            $this->db->where('id', $issue->copy_id);
            $this->db->update('book_copies', array('condition' => $condition, 'status' => 'available'));
        } elseif ($issue->copy_id) {
            $this->db->where('id', $issue->copy_id);
            $this->db->update('book_copies', array('status' => 'available'));
        }
        
        // Update issue record
        $update_data = array(
            'return_date' => $return_date,
            'actual_return_date' => $return_date,
            'status' => 3,
            'fine_amount' => $fine_amount,
            'return_by' => get_loggedin_user_id()
        );
        
        $this->db->where('id', $issue_id);
        $this->db->update('book_issues', $update_data);
        
        // Update book available_copies
        $this->db->set('available_copies', 'available_copies + 1', FALSE);
        $this->db->set('issued_copies', 'issued_copies - 1', FALSE);
        $this->db->where('id', $issue->book_id);
        $this->db->update('book');
        
        $this->log_audit('book_returned', 'book_issues', $issue_id, null, $update_data);
        
        return array('success' => true, 'message' => 'Book returned successfully.', 'fine_amount' => $fine_amount);
    }
    
    /**
     * Mark a book copy as lost
     */
    public function mark_copy_lost($copy_id, $reason)
    {
        $copy = $this->db->get_where('book_copies', array('id' => $copy_id))->row();
        
        if (!$copy) {
            return array('success' => false, 'message' => 'Copy not found.');
        }
        
        $this->db->where('id', $copy_id);
        $this->db->update('book_copies', array('status' => 'lost', 'notes' => $reason));
        
        // Update book available_copies
        $this->db->set('available_copies', 'available_copies - 1', FALSE);
        $this->db->where('id', $copy->book_id);
        $this->db->update('book');
        
        $this->log_audit('copy_marked_lost', 'book_copies', $copy_id, null, array('reason' => $reason));
        
        return array('success' => true, 'message' => 'Book copy marked as lost.');
    }
        /**
     * Generate barcode for a book copy
     */
    public function generate_barcode_image($barcode_number)
    {
        $this->load->library('barcode');
        return $this->barcode->generate($barcode_number);
    }
    
    /**
     * Get book copy by barcode
     */
    public function get_copy_by_barcode($barcode)
    {
        return $this->db->select('bc.*, b.title, b.book_code, b.author')
                        ->from('book_copies bc')
                        ->join('book b', 'b.id = bc.book_id')
                        ->where('bc.barcode', $barcode)
                        ->where('bc.branch_id', get_loggedin_branch_id())
                        ->get()
                        ->row();
    }
    
    /**
     * Get issue record by copy ID
     */
    public function get_active_issue_by_copy($copy_id)
    {
        return $this->db->select('bi.*, u.name as user_name')
                        ->from('book_issues bi')
                        ->join('staff u', 'u.id = bi.user_id AND bi.role_id != 7', 'left')
                        ->where('bi.copy_id', $copy_id)
                        ->where('bi.status', 1)
                        ->get()
                        ->row();
    }

        /**
     * Send due date reminder SMS
     */
    public function send_due_reminders()
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $due_books = $this->db->select('bi.*, b.title, u.mobileno, u.name as user_name')
                              ->from('book_issues bi')
                              ->join('book b', 'b.id = bi.book_id')
                              ->join('staff u', 'u.id = bi.user_id AND bi.role_id != 7', 'left')
                              ->where('bi.date_of_expiry', $tomorrow)
                              ->where('bi.status', 1)
                              ->get()
                              ->result_array();
        
        $this->load->model('sendsmsmail_model');
        $sent_count = 0;
        
        foreach ($due_books as $book) {
            $mobile = $book['mobileno'];
            if (empty($mobile)) {
                // Try to get student mobile if role is student
                if ($book['role_id'] == 7) {
                    $student = $this->db->get_where('student', ['id' => $book['user_id']])->row();
                    $mobile = $student->mobileno ?? '';
                }
            }
            
            if (!empty($mobile)) {
                $message = "REMINDER: The book '{$book['title']}' is due for return tomorrow. Please return it to the library to avoid fines.";
                $this->sendsmsmail_model->send_sms($mobile, $message);
                $sent_count++;
            }
        }
        
        return $sent_count;
    }
    
    /**
     * Send overdue alert SMS
     */
    public function send_overdue_alerts()
    {
        $overdue_books = $this->get_overdue_books();
        $this->load->model('sendsmsmail_model');
        $sent_count = 0;
        
        foreach ($overdue_books as $book) {
            // Get user mobile
            $mobile = '';
            if ($book['role_id'] == 7) {
                $student = $this->db->get_where('student', ['id' => $book['user_id']])->row();
                $mobile = $student->mobileno ?? '';
            } else {
                $staff = $this->db->get_where('staff', ['id' => $book['user_id']])->row();
                $mobile = $staff->mobileno ?? '';
            }
            
            if (!empty($mobile)) {
                $days = $book['days_overdue'] ?? 1;
                $fine = $days * 5;
                $message = "OVERDUE ALERT: The book '{$book['title']}' is {$days} day(s) overdue. Fine: KES {$fine}. Please return immediately.";
                $this->sendsmsmail_model->send_sms($mobile, $message);
                $sent_count++;
            }
        }
        
        return $sent_count;
    }
    
    // ========== ADVANCED LIBRARY REPORTS ==========
    
    /**
     * Get books issued by class
     */
     public function get_books_issued_by_class($branch_id, $class_id = null, $section_id = null, $date_from = null, $date_to = null)
{
    $is_superadmin = is_superadmin_loggedin();
    
    $sql = "SELECT bi.*, b.title, b.book_code, b.author, b.isbn_no,
                   s.id as student_id, s.first_name, s.last_name, s.register_no,
                   c.name as class_name, sec.name as section_name,
                   bi.copy_id, bi.branch_id,
                   DATEDIFF(CURDATE(), bi.date_of_expiry) as days_overdue
            FROM book_issues bi
            INNER JOIN book b ON b.id = bi.book_id
            INNER JOIN student s ON s.id = bi.user_id AND bi.role_id = 7
            INNER JOIN enroll e ON e.student_id = s.id AND e.session_id = bi.session_id
            INNER JOIN class c ON c.id = e.class_id
            INNER JOIN section sec ON sec.id = e.section_id
            WHERE bi.status = 1 AND bi.role_id = 7";
    
    // Branch isolation - CRITICAL
    if (!$is_superadmin) {
        $sql .= " AND bi.branch_id = " . intval($branch_id);
        $sql .= " AND b.branch_id = " . intval($branch_id);
    } elseif ($branch_id && $branch_id != 'all') {
        $sql .= " AND bi.branch_id = " . intval($branch_id);
    }
    
    if ($class_id) {
        $sql .= " AND c.id = " . intval($class_id);
    }
    if ($section_id) {
        $sql .= " AND sec.id = " . intval($section_id);
    }
    if ($date_from) {
        $sql .= " AND DATE(bi.date_of_issue) >= '" . $this->db->escape_str($date_from) . "'";
    }
    if ($date_to) {
        $sql .= " AND DATE(bi.date_of_issue) <= '" . $this->db->escape_str($date_to) . "'";
    }
    
    $sql .= " ORDER BY c.name ASC, sec.name ASC, s.first_name ASC";
    
    $query = $this->db->query($sql);
    
    return $query ? $query->result_array() : array();
}
    
    
        /**
     * Get books issued by category with branch isolation
     */
    public function get_books_issued_by_category($branch_id, $category_id = null, $date_from = null, $date_to = null)
    {
        $is_superadmin = is_superadmin_loggedin();
        
        $sql = "SELECT bi.*, b.title, b.book_code, b.author, b.isbn_no, bc.name as category_name,
                       s.id as student_id, s.first_name, s.last_name, s.register_no,
                       c.name as class_name, sec.name as section_name,
                       DATEDIFF(CURDATE(), bi.date_of_expiry) as days_overdue
                FROM book_issues bi
                INNER JOIN book b ON b.id = bi.book_id
                INNER JOIN book_category bc ON bc.id = b.category_id
                INNER JOIN student s ON s.id = bi.user_id AND bi.role_id = 7
                LEFT JOIN enroll e ON e.student_id = s.id AND e.session_id = bi.session_id
                LEFT JOIN class c ON c.id = e.class_id
                LEFT JOIN section sec ON sec.id = e.section_id
                WHERE bi.status = 1
                AND bi.role_id = 7";
        
        // Branch isolation
        if (!$is_superadmin) {
            $sql .= " AND bi.branch_id = " . intval($branch_id);
            $sql .= " AND b.branch_id = " . intval($branch_id);
        } else {
            if ($branch_id) {
                $sql .= " AND bi.branch_id = " . intval($branch_id);
            }
        }
        
        if ($category_id) {
            $sql .= " AND bc.id = " . intval($category_id);
        }
        
        if ($date_from) {
            $sql .= " AND DATE(bi.date_of_issue) >= '" . $this->db->escape_str($date_from) . "'";
        }
        
        if ($date_to) {
            $sql .= " AND DATE(bi.date_of_issue) <= '" . $this->db->escape_str($date_to) . "'";
        }
        
        $sql .= " ORDER BY bc.name ASC, bi.date_of_issue DESC";
        
        $query = $this->db->query($sql);
        
        return $query ? $query->result_array() : array();
    }
 public function get_books_not_returned($branch_id)
{
    $is_superadmin = is_superadmin_loggedin();
    
    $sql = "SELECT bi.*, 
                   b.title, 
                   b.book_code, 
                   b.author, 
                   b.isbn_no,
                   bc.copy_number,
                   bc.id as copy_id,
                   bi.user_id,
                   bi.role_id,
                   bi.branch_id,
                   DATEDIFF(CURDATE(), bi.date_of_expiry) as days_overdue
            FROM book_issues bi
            LEFT JOIN book b ON b.id = bi.book_id
            LEFT JOIN book_copies bc ON bc.id = bi.copy_id
            WHERE bi.status = 1";
    
    // Branch isolation
    if (!$is_superadmin) {
        $sql .= " AND bi.branch_id = " . intval($branch_id);
        $sql .= " AND b.branch_id = " . intval($branch_id);
    } elseif ($branch_id && $branch_id != 'all') {
        $sql .= " AND bi.branch_id = " . intval($branch_id);
    }
    
    $sql .= " ORDER BY days_overdue DESC";
    
    $query = $this->db->query($sql);
    
    $results = $query ? $query->result_array() : array();
    
    // Debug log to file
    if (!empty($results)) {
        file_put_contents('debug_copy.txt', "First row copy_number: " . ($results[0]['copy_number'] ?? 'NULL') . "\n", FILE_APPEND);
        file_put_contents('debug_copy.txt', "First row keys: " . implode(', ', array_keys($results[0])) . "\n", FILE_APPEND);
    }
    
    return $results;
}
    public function get_borrower_history($branch_id, $date_from = null, $date_to = null)
{
    $sql = "SELECT bi.*, 
                   b.title, 
                   b.book_code, 
                   b.author, 
                   b.isbn_no,
                   bi.copy_id,
                   bi.branch_id,
                   CASE 
                       WHEN bi.status = 0 THEN 'Pending'
                       WHEN bi.status = 1 THEN 'Issued'
                       WHEN bi.status = 2 THEN 'Rejected'
                       WHEN bi.status = 3 THEN 'Returned'
                       ELSE 'Unknown'
                   END as status_text
            FROM book_issues bi
            LEFT JOIN book b ON b.id = bi.book_id
            WHERE 1=1";
    
    if (!is_superadmin_loggedin()) {
        $sql .= " AND bi.branch_id = " . intval($branch_id);
    }
    
    if ($date_from) {
        $sql .= " AND DATE(bi.date_of_issue) >= '" . $this->db->escape_str($date_from) . "'";
    }
    
    if ($date_to) {
        $sql .= " AND DATE(bi.date_of_issue) <= '" . $this->db->escape_str($date_to) . "'";
    }
    
    $sql .= " ORDER BY bi.id DESC LIMIT 1000";
    
    $query = $this->db->query($sql);
    
    $results = $query ? $query->result_array() : array();
    
    // Add fine_per_day to each result
    foreach ($results as &$row) {
        $fine_per_day = $this->get_fine_per_day($row['branch_id']);
        $row['fine_per_day'] = $fine_per_day;
        
        // Calculate fine if overdue
        if ($row['status'] == 1 && !empty($row['date_of_expiry'])) {
            $due_date = strtotime($row['date_of_expiry']);
            $today = time();
            if ($today > $due_date) {
                $days_overdue = floor(($today - $due_date) / 86400);
                $row['calculated_fine'] = $days_overdue * $fine_per_day;
                $row['days_overdue'] = $days_overdue;
            } else {
                $row['calculated_fine'] = 0;
                $row['days_overdue'] = 0;
            }
        } else {
            $row['calculated_fine'] = $row['fine_amount'] ?? 0;
            $row['days_overdue'] = 0;
        }
    }
    
    return $results;
}

           /**
     * Get section-wise library statistics (issued, returned, lost, damaged)
     * STRICT BRANCH ISOLATION - COMPLETE REWRITE
     */
    public function get_section_wise_library_stats($branch_id, $class_id = null, $section_id = null)
    {
        $is_superadmin = is_superadmin_loggedin();
        $session_id = get_session_id();
        
        // Build the base query with STRICT branch isolation
        $sql = "SELECT 
                    c.id as class_id,
                    c.name as class_name,
                    sec.id as section_id,
                    sec.name as section_name,
                    COUNT(DISTINCT s.id) as total_students,
                    COUNT(DISTINCT CASE WHEN bi.status = 1 THEN bi.id END) as books_issued,
                    COUNT(DISTINCT CASE WHEN bi.status = 3 THEN bi.id END) as books_returned,
                    COUNT(DISTINCT CASE WHEN bc.status = 'lost' THEN bc.id END) as books_lost,
                    COUNT(DISTINCT CASE WHEN bc.status = 'damaged' OR bc.condition IN ('poor', 'damaged') THEN bc.id END) as books_damaged,
                    COUNT(DISTINCT CASE WHEN bi.status = 1 AND bi.date_of_expiry < CURDATE() THEN bi.id END) as books_overdue,
                    ROUND(COUNT(DISTINCT CASE WHEN bi.status = 1 THEN bi.id END) / NULLIF(COUNT(DISTINCT s.id), 0), 2) as avg_books_per_student
                FROM class c
                INNER JOIN sections_allocation sa ON sa.class_id = c.id
                INNER JOIN section sec ON sec.id = sa.section_id
                INNER JOIN enroll e ON e.class_id = c.id AND e.section_id = sec.id AND e.session_id = $session_id
                INNER JOIN student s ON s.id = e.student_id
                LEFT JOIN book_issues bi ON bi.user_id = s.id AND bi.role_id = 7
                LEFT JOIN book_copies bc ON bc.id = bi.copy_id
                WHERE 1=1";
        
        // ========== STRICT BRANCH ISOLATION ==========
        // Force branch restriction on ALL related tables
        if (!$is_superadmin) {
            // Non-superadmin: ONLY their branch across ALL tables
            $sql .= " AND c.branch_id = " . intval($branch_id);
            $sql .= " AND sec.branch_id = " . intval($branch_id);
            $sql .= " AND e.branch_id = " . intval($branch_id);
            $sql .= " AND s.branch_id = " . intval($branch_id);
            
            // For book_issues, ensure it exists and belongs to the branch
            $sql .= " AND (bi.id IS NULL OR bi.branch_id = " . intval($branch_id) . ")";
        } else {
            // Superadmin: filter by selected branch if provided
            if ($branch_id && $branch_id != 'all') {
                $sql .= " AND c.branch_id = " . intval($branch_id);
                $sql .= " AND sec.branch_id = " . intval($branch_id);
                $sql .= " AND e.branch_id = " . intval($branch_id);
                $sql .= " AND s.branch_id = " . intval($branch_id);
                $sql .= " AND (bi.id IS NULL OR bi.branch_id = " . intval($branch_id) . ")";
            }
        }
        // ========== END BRANCH ISOLATION ==========
        
        // Apply class filter
        if ($class_id) {
            $sql .= " AND c.id = " . intval($class_id);
        }
        
        // Apply section filter
        if ($section_id) {
            $sql .= " AND sec.id = " . intval($section_id);
        }
        
        $sql .= " GROUP BY c.id, sec.id
                 ORDER BY c.name ASC, sec.name ASC";
        
        $query = $this->db->query($sql);
        
        // Debug logging - remove after testing
        log_message('debug', 'Section Wise SQL: ' . $sql);
        log_message('debug', 'Branch ID: ' . $branch_id);
        log_message('debug', 'Is Superadmin: ' . ($is_superadmin ? 'Yes' : 'No'));
        log_message('debug', 'Results count: ' . ($query ? $query->num_rows() : 0));
        
        return $query ? $query->result_array() : array();
    }
    
    /**
     * Get sections by class for dropdown (with branch isolation)
     */
    public function get_sections_by_class($class_id, $branch_id)
    {
        $is_superadmin = is_superadmin_loggedin();
        
        $this->db->select('sec.id, sec.name')
                 ->from('sections_allocation sa')
                 ->join('section sec', 'sec.id = sa.section_id')
                 ->where('sa.class_id', $class_id);
        
        // Branch isolation
        if (!$is_superadmin) {
            $this->db->where('sec.branch_id', $branch_id);
        }
        
        $this->db->order_by('sec.name', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get lost/damaged books
     */
    public function get_lost_damaged_books($branch_id)
    {
        // Get from book_copies where status is lost or damaged
        $copies = $this->db->select('bc.*, b.title, b.book_code, b.author, b.isbn_no,
                                    bc.condition, bc.notes as damage_reason')
                          ->from('book_copies bc')
                          ->join('book b', 'b.id = bc.book_id')
                          ->where_in('bc.status', ['lost', 'damaged'])
                          ->order_by('bc.updated_at', 'DESC')
                          ->get()
                          ->result_array();
        
        // Also get from book_issues where marked as lost/damaged
        $issues = $this->db->select('bi.*, b.title, b.book_code, b.author, b.isbn_no,
                                    bi.fine_amount as penalty, "issued" as source')
                          ->from('book_issues bi')
                          ->join('book b', 'b.id = bi.book_id')
                          ->where('bi.fine_amount >', 0)
                          ->where('bi.status', 3)
                          ->order_by('bi.return_date', 'DESC')
                          ->get()
                          ->result_array();
        
        return array('copies' => $copies, 'issues' => $issues);
    }
    
    /**
     * Get inventory summary
     */
    public function get_inventory_summary($branch_id)
    {
        // Summary by category
        $by_category = $this->db->select('bc.name as category_name,
                                         COUNT(b.id) as total_books,
                                         SUM(b.total_copies) as total_copies,
                                         SUM(b.issued_copies) as total_issued,
                                         SUM(b.available_copies) as total_available')
                                 ->from('book b')
                                 ->join('book_category bc', 'bc.id = b.category_id')
                                 ->group_by('b.category_id')
                                 ->order_by('total_books', 'DESC')
                                 ->get()
                                 ->result_array();
        
        // Summary by condition
        $by_condition = $this->db->select('bc.condition, COUNT(*) as count')
                                 ->from('book_copies bc')
                                 ->group_by('bc.condition')
                                 ->get()
                                 ->result_array();
        
        // Total counts
        $totals = $this->db->select('COUNT(DISTINCT b.id) as unique_titles,
                                    SUM(b.total_copies) as total_copies,
                                    SUM(b.issued_copies) as total_issued,
                                    SUM(b.available_copies) as total_available')
                          ->from('book b')
                          ->get()
                          ->row_array();
        
        return array(
            'by_category' => $by_category,
            'by_condition' => $by_condition,
            'totals' => $totals
        );
    }
    
    
       /**
     * Get student borrowing history - SIMPLEST VERSION
     */
    public function get_student_borrowing_history($branch_id, $search_term = null)
    {
        $is_superadmin = is_superadmin_loggedin();
        
        // Start with a very simple query
        $this->db->select('bi.*, b.title, b.book_code, b.author, b.isbn_no,
                          s.id as student_id, s.first_name, s.last_name, s.register_no')
                 ->from('book_issues bi')
                 ->join('book b', 'b.id = bi.book_id', 'inner')
                 ->join('student s', 's.id = bi.user_id', 'inner')
                 ->where('bi.role_id', 7);
        
        // Branch isolation
        if (!$is_superadmin) {
            $this->db->where('bi.branch_id', $branch_id);
        } elseif ($branch_id && $branch_id != 'all') {
            $this->db->where('bi.branch_id', $branch_id);
        }
        
        // Search condition
        if ($search_term && !empty($search_term)) {
            if (is_numeric($search_term)) {
                $this->db->where('s.id', $search_term);
            } else {
                $this->db->group_start()
                         ->like('s.first_name', $search_term)
                         ->or_like('s.last_name', $search_term)
                         ->or_like('s.register_no', $search_term)
                         ->group_end();
            }
        }
        
        $this->db->order_by('bi.id', 'DESC');
        
        $query = $this->db->get();
        
        // Debug
        log_message('debug', 'Student Borrowing SQL: ' . $this->db->last_query());
        log_message('debug', 'Results found: ' . $query->num_rows());
        
        if ($query->num_rows() == 0) {
            return array();
        }
        
        $results = $query->result_array();
        
        // Add status text
        foreach ($results as &$row) {
            switch($row['status']) {
                case 0: $row['status_text'] = 'Pending'; break;
                case 1: $row['status_text'] = 'Issued'; break;
                case 2: $row['status_text'] = 'Rejected'; break;
                case 3: $row['status_text'] = 'Returned'; break;
                default: $row['status_text'] = 'Unknown';
            }
            
            $row['borrow_status'] = ($row['status'] == 1 && strtotime($row['date_of_expiry']) < time()) ? 'Overdue' : $row['status_text'];
            $row['days_overdue'] = ($row['status'] == 1 && strtotime($row['date_of_expiry']) < time()) ? floor((time() - strtotime($row['date_of_expiry'])) / 86400) : 0;
        }
        
        return $results;
    }
    /**
     * Get all students for dropdown (with branch isolation)
     */
    public function get_students_for_dropdown($branch_id, $search_term = null)
    {
        $is_superadmin = is_superadmin_loggedin();
        
        $this->db->select('s.id, CONCAT(s.first_name, " ", s.last_name) as full_name, s.register_no')
                 ->from('student s')
                 ->join('enroll e', 'e.student_id = s.id AND e.session_id = ' . get_session_id())
                 ->group_by('s.id')
                 ->order_by('s.first_name', 'ASC')
                 ->limit(50);
        
        // Branch isolation
        if (!$is_superadmin) {
            $this->db->where('s.branch_id', $branch_id);
        } elseif ($branch_id && $branch_id != 'all') {
            $this->db->where('s.branch_id', $branch_id);
        }
        
        if ($search_term) {
            $this->db->group_start()
                     ->like('s.first_name', $search_term)
                     ->or_like('s.last_name', $search_term)
                     ->or_like('s.register_no', $search_term)
                     ->group_end();
        }
        
        return $this->db->get()->result_array();
    }
        /**
     * Get book by barcode (Strict MVC - No DB in controller)
     * @param string $barcode
     * @param int $branch_id
     * @return array|false
     */
    public function get_book_by_barcode($barcode, $branch_id)
{
    $is_superadmin = is_superadmin_loggedin();
    
    // Search by copy_number (what your scanner reads)
    $this->db->select('bc.id as copy_id, bc.barcode, bc.copy_number, bc.status as copy_status, 
                      b.id as book_id, b.title, b.author, b.category_id, b.book_code, 
                      b.total_stock, b.available_copies, cat.name as category_name')
             ->from('book_copies bc')
             ->join('book b', 'b.id = bc.book_id')
             ->join('book_category cat', 'cat.id = b.category_id', 'left')
             ->where('bc.copy_number', $barcode);
    
    if (!$is_superadmin) {
        $this->db->where('bc.branch_id', $branch_id);
    }
    
    $copy = $this->db->get()->row();
    
    if ($copy) {
        return [
            'source' => 'copy',
            'id' => $copy->copy_id,
            'book_id' => $copy->book_id,
            'title' => $copy->title,
            'author' => $copy->author,
            'copy_number' => $copy->copy_number,  // ← CRITICAL: Add this
            'barcode' => $copy->barcode,
            'status' => $copy->copy_status,
            'is_available' => ($copy->copy_status == 'available'),
            'category_id' => $copy->category_id,
            'category_name' => $copy->category_name,
            'book_code' => $copy->book_code
        ];
    }
    
    // Fallback: Search by barcode (old format)
    $this->db->select('bc.id as copy_id, bc.barcode, bc.copy_number, bc.status as copy_status, 
                      b.id as book_id, b.title, b.author, b.category_id, b.book_code, 
                      b.total_stock, b.available_copies, cat.name as category_name')
             ->from('book_copies bc')
             ->join('book b', 'b.id = bc.book_id')
             ->join('book_category cat', 'cat.id = b.category_id', 'left')
             ->where('bc.barcode', $barcode);
    
    if (!$is_superadmin) {
        $this->db->where('bc.branch_id', $branch_id);
    }
    
    $copy = $this->db->get()->row();
    
    if ($copy) {
        return [
            'source' => 'copy',
            'id' => $copy->copy_id,
            'book_id' => $copy->book_id,
            'title' => $copy->title,
            'author' => $copy->author,
            'copy_number' => $copy->copy_number,  // ← CRITICAL: Add this
            'barcode' => $copy->barcode,
            'status' => $copy->copy_status,
            'is_available' => ($copy->copy_status == 'available'),
            'category_id' => $copy->category_id,
            'category_name' => $copy->category_name,
            'book_code' => $copy->book_code
        ];
    }
    
    return false;
}
}
