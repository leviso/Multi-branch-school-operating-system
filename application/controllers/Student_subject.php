<?php
// ===== DEBUG: Log at the very top of file =====
error_log("=== [FILE] Student_subject.php loaded ===");
defined('BASEPATH') or exit('No direct script access allowed');
error_log("=== [FILE] After BASEPATH check ===");

class Student_subject extends Admin_Controller
{

   
    public function __construct()
{
    error_log("=== [CONSTRUCTOR] Student_subject constructor START ===");
   
    parent::__construct();
        
        error_log("=== [CONSTRUCTOR] After parent::__construct() ===");
        
        $this->load->model('student_subject_model');
        $this->load->model('subject_model');
        
        error_log("=== [CONSTRUCTOR] Student_subject constructor END ===");
    }

 public function index()
    {
        error_log("=== [INDEX] Student_subject->index() START ===");
        
        echo "<h1>Debug Mode</h1>";
        echo "<p>If you see this, the controller is working!</p>";
        echo "<p>Branch ID: " . $this->application_model->get_branch_id() . "</p>";
        die();
        
        error_log("=== [INDEX] Student_subject->index() END ===");
    }
    
    public function test()
    {
        error_log("=== [TEST] Student_subject->test() called ===");
        echo "Test method is working!";
    }
  
  

    /**
     * Get subjects for selected class/section (AJAX)
     */
    public function get_subjects()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $branch_id = $this->input->post('branch_id');
        
        if (empty($class_id) || empty($section_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select class and section']);
            return;
        }
        
        // Get subjects assigned to this class/section
        $subjects = $this->subject_model->getSubjectList($class_id, $section_id)->result_array();
        
        // Get enrollment stats for each subject
        foreach ($subjects as &$subject) {
            $stats = $this->student_subject_model->get_enrollment_stats(
                $subject['subject_id'], $class_id, $section_id, $branch_id
            );
            $subject['enrolled_count'] = $stats['enrolled_students'];
            $subject['total_students'] = $stats['total_students'];
            $subject['enrollment_percentage'] = $stats['enrollment_percentage'];
        }
        
        echo json_encode([
            'status' => 'success',
            'subjects' => $subjects
        ]);
    }

    /**
     * Get enrolled students for a subject (AJAX)
     */
    public function get_enrolled_students()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $subject_id = $this->input->post('subject_id');
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $branch_id = $this->input->post('branch_id');
        
        if (empty($subject_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select a subject']);
            return;
        }
        
        $students = $this->student_subject_model->get_subject_students(
            $subject_id, $class_id, $section_id, $branch_id
        );
        
        echo json_encode([
            'status' => 'success',
            'students' => $students
        ]);
    }

    /**
     * Get unenrolled students for a subject (AJAX)
     */
    public function get_unenrolled_students()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        $subject_id = $this->input->post('subject_id');
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $branch_id = $this->input->post('branch_id');
        
        if (empty($subject_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Please select a subject']);
            return;
        }
        
        $students = $this->student_subject_model->get_unenrolled_students(
            $subject_id, $class_id, $section_id, $branch_id
        );
        
        echo json_encode([
            'status' => 'success',
            'students' => $students
        ]);
    }

    /**
     * Enroll a student in a subject (AJAX)
     */
    public function enroll_student()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        if (!get_permission('student_subject_enrollment', 'is_add')) {
            echo json_encode(['status' => 'error', 'message' => translate('access_denied')]);
            return;
        }
        
        $student_id = $this->input->post('student_id');
        $subject_id = $this->input->post('subject_id');
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $branch_id = $this->input->post('branch_id');
        
        $result = $this->student_subject_model->enroll_student(
            $student_id, $subject_id, $class_id, $section_id, $branch_id
        );
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => translate('student_enrolled_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => translate('enrollment_failed')]);
        }
    }

    /**
     * Remove a student from a subject (AJAX)
     */
    public function remove_student()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        if (!get_permission('student_subject_enrollment', 'is_delete')) {
            echo json_encode(['status' => 'error', 'message' => translate('access_denied')]);
            return;
        }
        
        $student_id = $this->input->post('student_id');
        $subject_id = $this->input->post('subject_id');
        
        $result = $this->student_subject_model->delete_student_subject($student_id, $subject_id);
        
        if ($result) {
            echo json_encode(['status' => 'success', 'message' => translate('student_removed_successfully')]);
        } else {
            echo json_encode(['status' => 'error', 'message' => translate('removal_failed')]);
        }
    }

    /**
     * Bulk enroll students (AJAX)
     */
    public function bulk_enroll()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        if (!get_permission('student_subject_enrollment', 'is_add')) {
            echo json_encode(['status' => 'error', 'message' => translate('access_denied')]);
            return;
        }
        
        $student_ids = $this->input->post('student_ids');
        $subject_id = $this->input->post('subject_id');
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $branch_id = $this->input->post('branch_id');
        
        if (empty($student_ids) || !is_array($student_ids)) {
            echo json_encode(['status' => 'error', 'message' => translate('no_students_selected')]);
            return;
        }
        
        $count = $this->student_subject_model->bulk_enroll(
            $student_ids, $subject_id, $class_id, $section_id, $branch_id
        );
        
        echo json_encode([
            'status' => 'success', 
            'message' => sprintf(translate('students_enrolled_successfully'), $count),
            'count' => $count
        ]);
    }

    /**
     * Bulk remove students (AJAX)
     */
    public function bulk_remove()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        if (!get_permission('student_subject_enrollment', 'is_delete')) {
            echo json_encode(['status' => 'error', 'message' => translate('access_denied')]);
            return;
        }
        
        $student_ids = $this->input->post('student_ids');
        $subject_id = $this->input->post('subject_id');
        $branch_id = $this->input->post('branch_id');
        
        if (empty($student_ids) || !is_array($student_ids)) {
            echo json_encode(['status' => 'error', 'message' => translate('no_students_selected')]);
            return;
        }
        
        $count = $this->student_subject_model->bulk_remove($student_ids, $subject_id, $branch_id);
        
        echo json_encode([
            'status' => 'success', 
            'message' => sprintf(translate('students_removed_successfully'), $count),
            'count' => $count
        ]);
    }

    /**
     * Auto-enroll all students (AJAX)
     */
    public function auto_enroll_all()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied();
        }
        
        if (!get_permission('student_subject_enrollment', 'is_add')) {
            echo json_encode(['status' => 'error', 'message' => translate('access_denied')]);
            return;
        }
        
        $subject_id = $this->input->post('subject_id');
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $branch_id = $this->input->post('branch_id');
        
        $count = $this->student_subject_model->auto_enroll_all(
            $subject_id, $class_id, $section_id, $branch_id
        );
        
        echo json_encode([
            'status' => 'success', 
            'message' => sprintf(translate('all_students_enrolled_successfully'), $count),
            'count' => $count
        ]);
    }
}