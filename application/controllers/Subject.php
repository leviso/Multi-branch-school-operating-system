<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Subject.php
 * @copyright : Reserved Synobix Team
 */

class Subject extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('subject_model');
    }

    public function index()
    {
        if (!get_permission('subject', 'is_view')) {
            access_denied();
        }
        $this->data['subjectlist'] = $this->app_lib->getTable('subject');
        $this->data['title'] = translate('subject');
        $this->data['sub_page'] = 'subject/index';
        $this->data['main_menu'] = 'subject';
        $this->load->view('layout/index', $this->data);
    }

    // subject edit page
    public function edit($id = '')
    {
        if (!get_permission('subject', 'is_edit')) {
            access_denied();
        }

        $this->data['subject'] = $this->app_lib->getTable('subject', array('t.id' => $id), true);
        $this->data['title'] = translate('subject');
        $this->data['sub_page'] = 'subject/edit';
        $this->data['main_menu'] = 'subject';
        $this->load->view('layout/index', $this->data);
    }

    // moderator subject all information
    public function save()
    {
        if ($_POST) {
            if (is_superadmin_loggedin()) {
                $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
            }
            $this->form_validation->set_rules('name', translate('subject_name'), 'trim|required');
            $this->form_validation->set_rules('subject_code', translate('subject_code'), 'trim|required');
            $this->form_validation->set_rules('subject_type', translate('subject_type'), 'trim|required');
            if ($this->form_validation->run() !== false) {
                $arraySubject = array(
                    'name' => $this->input->post('name'),
                    'subject_code' => $this->input->post('subject_code'),
                    'subject_type' => $this->input->post('subject_type'),
                    'subject_author' => $this->input->post('subject_author'),
                    'branch_id' => $this->application_model->get_branch_id(),
                );
                $subjectID = $this->input->post('subject_id');
                if (empty($subjectID)) {
                    if (get_permission('subject', 'is_add')) {
                        $this->db->insert('subject', $arraySubject);
                    }
                    set_alert('success', translate('information_has_been_saved_successfully'));
                } else {
                    if (get_permission('subject', 'is_edit')) {
                        if (!is_superadmin_loggedin()) {
                            $this->db->where('branch_id', get_loggedin_branch_id());
                        }
                        $this->db->where('id', $subjectID);
                        $this->db->update('subject', $arraySubject);
                    }
                    set_alert('success', translate('information_has_been_updated_successfully'));
                }
                $url = base_url('subject/index');
                $array = array('status' => 'success', 'url' => $url);
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    public function delete($id = '')
    {
        if (get_permission('subject', 'is_delete')) {
            $this->app_lib->check_branch_restrictions('subject', $id);
            $this->db->where('id', $id);
            $this->db->delete('subject');
            $this->db->where('subject_id', $id);
            $this->db->delete('subject_assign');
        }
    }

    // add subject assign information and delete
    public function class_assign()
    {
        if (!get_permission('subject_class_assign', 'is_view')) {
            access_denied();
        }

        $this->data['branch_id'] = $this->application_model->get_branch_id();
        $this->data['assignlist'] = $this->subject_model->getAssignList();
        $this->data['title'] = translate('class_assign');
        $this->data['sub_page'] = 'subject/class_assign';
        $this->data['main_menu'] = 'subject';
        $this->load->view('layout/index', $this->data);
    }

    // moderator class assign save all information
   public function class_assign_save()
{
    if ($_POST) {
        if (!get_permission('subject_class_assign', 'is_add')) {
            ajax_access_denied();
        }
        
        if (is_superadmin_loggedin()) {
            $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
        }
        $this->form_validation->set_rules('class_id', translate('class'), 'trim|required');
        $this->form_validation->set_rules('section_id', translate('section'), 'trim|required');
        $this->form_validation->set_rules('subjects[]', translate('subject'), 'trim|required');
        
        if ($this->form_validation->run() !== false) {
            $branch_id = $this->application_model->get_branch_id();
            $class_id = $this->input->post('class_id');
            $section_id = $this->input->post('section_id');
            $session_id = get_session_id();
            $subjects = $this->input->post('subjects');
            
            // Get class teacher from model
            $teacher_id = $this->subject_model->get_class_teacher_id($class_id, $section_id, $session_id, $branch_id);
            
            // Add assignments via model
            $inserted = $this->subject_model->add_subject_assignments($subjects, $class_id, $section_id, $session_id, $branch_id, $teacher_id);
            
            set_alert('success', translate('information_has_been_saved_successfully') . " ($inserted subjects added)");
            $url = base_url('subject/class_assign');
            $array = array('status' => 'success', 'url' => $url);
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'error' => $error);
        }
        echo json_encode($array);
    }
}

    // subject assign information edit
    public function class_assign_edit()
{
    if ($_POST) {
        if (!get_permission('subject_class_assign', 'is_edit')) {
            ajax_access_denied();
        }
        
        $this->form_validation->set_rules('subjects[]', translate('subject'), 'trim|required');
        
        if ($this->form_validation->run() !== false) {
            $branch_id = $this->application_model->get_branch_id();
            $class_id = $this->input->post('class_id');
            $section_id = $this->input->post('section_id');
            $session_id = get_session_id();
            $new_subjects = $this->input->post('subjects');
            
            // Get existing assignments from model
            $existing_subjects = $this->subject_model->get_existing_assignments($class_id, $section_id, $session_id, $branch_id);
            
            // Calculate differences
            $to_add = array_diff($new_subjects, $existing_subjects);
            $to_remove = array_diff($existing_subjects, $new_subjects);
            
            // Get class teacher from model
            $teacher_id = $this->subject_model->get_class_teacher_id($class_id, $section_id, $session_id, $branch_id);
            
            // Add new assignments via model
            $added = $this->subject_model->add_subject_assignments($to_add, $class_id, $section_id, $session_id, $branch_id, $teacher_id);
            
            // Remove unassigned subjects via model
            $removed = $this->subject_model->remove_subject_assignments($to_remove, $class_id, $section_id, $session_id, $branch_id);
            
            set_alert('success', translate('information_has_been_updated_successfully') . " ($added added, $removed removed)");
            $url = base_url('subject/class_assign');
            $array = array('status' => 'success', 'url' => $url);
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'error' => $error);
        }
        echo json_encode($array);
    }
}

    public function class_assign_delete($class_id = '', $section_id = '')
{
    if (!get_permission('subject_class_assign', 'is_delete')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    $session_id = get_session_id();
    
    if (!is_superadmin_loggedin()) {
        $branch_id = get_loggedin_branch_id();
    }
    
    // Delete via model
    $this->subject_model->delete_all_assignments($class_id, $section_id, $session_id, $branch_id);
    
    set_alert('success', translate('information_has_been_deleted_successfully'));
    redirect(base_url('subject/class_assign'));
}

    // validate here, if the check class assign
    public function unique_subject_assign($class_id)
    {
        $where = array(
            'class_id' => $class_id,
            'section_id' => $this->input->post('section_id'),
            'session_id' => get_session_id(),
        );
        $q = $this->db->get_where('subject_assign', $where)->num_rows();
        if ($q == 0) {
            return true;
        } else {
            $this->form_validation->set_message('unique_subject_assign', 'This class and section is already assigned.');
            return false;
        }
    }

    // teacher assign view page
    public function teacher_assign()
    {
        if (!get_permission('subject_teacher_assign', 'is_view')) {
            access_denied();
        }
        if ($_POST) {
            if (get_permission('subject_teacher_assign', 'is_add')) {
                if (is_superadmin_loggedin()) {
                    $this->form_validation->set_rules('branch_id', translate('branch'), 'required');
                }
                $this->form_validation->set_rules('staff_id', translate('teacher'), 'trim|required');
                $this->form_validation->set_rules('class_id', translate('class'), 'trim|required');
                $this->form_validation->set_rules('section_id', translate('section'), 'trim|required');
                $this->form_validation->set_rules('subject_id', translate('subject'), 'trim|required');
                if ($this->form_validation->run() !== false) {
                    $sessionID = get_session_id();
                    $branchID = $this->application_model->get_branch_id();
                    $classID = $this->input->post('class_id');
                    $sectionID = $this->input->post('section_id');
                    $subjectID = $this->input->post('subject_id');
                    $teacherID = $this->input->post('staff_id');
                    $query = $this->db->get_where("subject_assign", array(
                        'class_id' => $classID,
                        'section_id' => $sectionID,
                        'subject_id' => $subjectID,
                        'session_id' => $sessionID,
                        'branch_id' => $branchID,
                    ));
                    if ($query->num_rows() != 0) {
                        $this->db->where('id', $query->row()->id);
                        $this->db->update('subject_assign', array('teacher_id' => $teacherID));
                    }
                    set_alert('success', translate('information_has_been_updated_successfully'));
                    $url = base_url('subject/teacher_assign');
                    $array = array('status' => 'success', 'url' => $url, 'error' => '');
                } else {
                    $error = $this->form_validation->error_array();
                    $array = array('status' => 'fail', 'url' => '', 'error' => $error);
                }
                echo json_encode($array);
                exit();
            }
        }

        $this->data['branch_id'] = $this->application_model->get_branch_id();
        $this->data['assignlist'] = $this->subject_model->getTeacherAssignList();
        $this->data['title'] = translate('teacher_assign');
        $this->data['sub_page'] = 'subject/teacher_assign';
        $this->data['main_menu'] = 'subject';
        $this->load->view('layout/index', $this->data);
    }

    // teacher assign information moderator
    public function teacher_assign_delete($id = '')
    {
        if (get_permission('subject_teacher_assign', 'is_delete')) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->update('subject_assign', array('teacher_id' => 0));
        }
    }

    // get subject list based on class section
    public function getByClassSection()
    {
        $html = '';
        $classID = $this->input->post('classID');
        $sectionID = $this->input->post('sectionID');
        if (!empty($classID)) {
            $query = $this->subject_model->getSubjectByClassSection($classID, $sectionID);
            if ($query->num_rows() > 0) {
                $html .= '<option value="">' . translate('select') . '</option>';
                $subjects = $query->result_array();
                foreach ($subjects as $row) {
                    $html .= '<option value="' . $row['subject_id'] . '">' . $row['subjectname'] . '</option>';
                }
            } else {
                $html .= '<option value="">' . translate('no_information_available') . '</option>';
            }
        } else {
            $html .= '<option value="">' . translate('select') . '</option>';
        }
        echo $html;
    }
    /**
 * Student Subject Enrollment page - using existing controller
 */
public function enrollment()
{
    if (!get_permission('subject_class_assign', 'is_view')) {
        access_denied();
    }
    
    $branch_id = $this->application_model->get_branch_id();
    $is_superadmin = is_superadmin_loggedin();
    
    $this->data['branch_id'] = $branch_id;
    $this->data['is_superadmin'] = $is_superadmin;
    $this->data['title'] = translate('student_subject_enrollment');
    $this->data['sub_page'] = 'subject/enrollment';
    $this->data['main_menu'] = 'subject';
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/select2/select2.css',
            'vendor/toastr/toastr.min.css',  // ADD THIS
        ),
        'js' => array(
            'vendor/select2/select2.js',
            'vendor/toastr/toastr.min.js',   // ADD THIS
        ),
    );
    $this->load->view('layout/index', $this->data);
}

/**
 * Get enrolled students for a subject (AJAX)
 */
public function get_enrolled_students()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $this->load->model('student_subject_model');
    
    $subject_id = $this->input->post('subject_id');
    $class_id = $this->input->post('class_id');
    $section_id = $this->input->post('section_id');
    $branch_id = $this->input->post('branch_id');
    
    $students = $this->student_subject_model->get_subject_students(
        $subject_id, $class_id, $section_id, $branch_id
    );
    
    echo json_encode(['status' => 'success', 'students' => $students]);
}

/**
 * Get unenrolled students for a subject (AJAX)
 */
public function get_unenrolled_students()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $this->load->model('student_subject_model');
    
    $subject_id = $this->input->post('subject_id');
    $class_id = $this->input->post('class_id');
    $section_id = $this->input->post('section_id');
    $branch_id = $this->input->post('branch_id');
    
    $students = $this->student_subject_model->get_unenrolled_students(
        $subject_id, $class_id, $section_id, $branch_id
    );
    
    echo json_encode(['status' => 'success', 'students' => $students]);
}

/**
 * Enroll a student in a subject (AJAX)
 */
public function enroll_student()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $this->load->model('student_subject_model');
    
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
    
    $this->load->model('student_subject_model');
    
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
 * Auto-enroll all students in a subject (AJAX)
 */
public function auto_enroll_all()
{
    if (!$this->input->is_ajax_request()) {
        access_denied();
    }
    
    $this->load->model('student_subject_model');
    
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
