<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tis extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Check if TIS module is enabled
        $branch_id = $this->application_model->get_branch_id();
        $tis_module = $this->db->where('modules_id', 26)->where('branch_id', $branch_id)->get('modules_manage')->row();
        if (empty($tis_module) || $tis_module->isEnabled != 1) {
            show_404();
        }
        
        $this->load->model('employee_model');
        $this->load->model('teacher_intelligence/Teacher_workload_model');
        $this->load->model('teacher_intelligence/Teacher_performance_model');
        $this->load->model('teacher_intelligence/Duty_model');
        $this->load->model('teacher_intelligence/Teacher_risk_model');
        $this->load->model('teacher_intelligence/Recommendation_engine');
    }
    
    public function dashboard() {
        if (!get_permission('tis_dashboard', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $session_id = get_session_id();
        $term_id = get_current_term_id();
        
        $data['title'] = translate('tis_dashboard');
        $data['sub_page'] = 'tis/dashboard';
        $data['main_menu'] = 'tis';
        
        // Get summary statistics with branch isolation
        $data['summary'] = $this->get_dashboard_summary($branch_id, $session_id, $term_id);
        
        // Get workload distribution
        $data['workload_stats'] = $this->Teacher_workload_model->get_workload_distribution($branch_id, $session_id);
        
        // Get performance distribution
        $data['performance_stats'] = $this->Teacher_performance_model->get_performance_distribution($branch_id, $session_id, $term_id);
        
        // Get risk distribution
        $data['risk_stats'] = $this->Teacher_risk_model->get_risk_distribution($branch_id, $session_id, $term_id);
        
        // Get recent recommendations
        $data['recent_recommendations'] = $this->Recommendation_engine->get_recent_recommendations($branch_id, 5);
        
        // Get top performers
        $data['top_performers'] = $this->Teacher_performance_model->get_top_performers($branch_id, $session_id, $term_id, 5);
        
        // Get at-risk teachers
        $data['at_risk_teachers'] = $this->Teacher_risk_model->get_high_risk_teachers($branch_id, $session_id, $term_id, 5);
        
        $this->load->view('layout/index', $data);
    }
    
    public function workload() {
        if (!get_permission('tis_workload', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $session_id = get_session_id();
        
        $data['title'] = translate('workload_management');
        $data['sub_page'] = 'tis/workload';
        $data['main_menu'] = 'tis';
        
        $data['teachers'] = $this->employee_model->getStaffList($branch_id, 3, 1);
        $data['workload_data'] = [];
        
        foreach ($data['teachers'] as $teacher) {
            $workload = $this->Teacher_workload_model->get_teacher_workload($teacher->id, $session_id);
            $data['workload_data'][$teacher->id] = $workload;
        }
        
        $this->load->view('layout/index', $data);
    }
    
    public function workload_balance() {
        if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $session_id = get_session_id();
        
        $data['title'] = translate('balance_workload');
        $data['sub_page'] = 'tis/workload/balance';
        $data['main_menu'] = 'tis_workload';
        
        $data['teachers'] = $this->employee_model->getStaffList($branch_id, 3, 1);
        $data['workload_data'] = [];
        
        foreach ($data['teachers'] as $teacher) {
            $workload = $this->Teacher_workload_model->get_teacher_workload($teacher->id, $session_id);
            $data['workload_data'][$teacher->id] = $workload;
        }
        
        $this->load->view('layout/index', $data);
    }
    
    public function workload_config() {
        if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        if ($_POST) {
            $this->form_validation->set_rules('max_weekly_hours', 'Max Weekly Hours', 'required|numeric');
            $this->form_validation->set_rules('max_class_size', 'Max Class Size', 'required|numeric');
            
            if ($this->form_validation->run()) {
                $data = [
                    'max_weekly_hours' => $this->input->post('max_weekly_hours'),
                    'max_class_size' => $this->input->post('max_class_size'),
                    'class_hour_weight' => $this->input->post('class_hour_weight'),
                    'responsibility_weight' => $this->input->post('responsibility_weight'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $existing = $this->db->get_where('teacher_workload_config', ['branch_id' => $branch_id])->row();
                
                if ($existing) {
                    $this->db->where('id', $existing->id)->update('teacher_workload_config', $data);
                } else {
                    $data['branch_id'] = $branch_id;
                    $this->db->insert('teacher_workload_config', $data);
                }
                
                $this->session->set_flashdata('success', translate('settings_updated'));
                redirect('tis/workload/config');
            }
        }
        
        $data['title'] = translate('workload_settings');
        $data['sub_page'] = 'tis/workload/config';
        $data['main_menu'] = 'tis_workload';
        
        $data['config'] = $this->db->get_where('teacher_workload_config', ['branch_id' => $branch_id])->row();
        if (!$data['config']) {
            $data['config'] = (object)['max_weekly_hours' => 30, 'max_class_size' => 45, 'class_hour_weight' => 1, 'responsibility_weight' => 0.25];
        }
        
        $this->load->view('layout/index', $data);
    }
    
    public function performance() {
        if (!get_permission('tis_performance', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $session_id = get_session_id();
        $term_id = get_current_term_id();
        
        $data['title'] = translate('performance_scores');
        $data['sub_page'] = 'tis/performance';
        $data['main_menu'] = 'tis';
        
        $data['teachers'] = $this->employee_model->getStaffList($branch_id, 3, 1);
        $data['performance_data'] = [];
        
        foreach ($data['teachers'] as $teacher) {
            $performance = $this->Teacher_performance_model->get_teacher_performance($teacher->id, $session_id, $term_id);
            $data['performance_data'][$teacher->id] = $performance;
        }
        
        $this->load->view('layout/index', $data);
    }
    
    public function performance_rankings() {
        if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $session_id = get_session_id();
        $term_id = get_current_term_id();
        
        $data['title'] = translate('teacher_rankings');
        $data['sub_page'] = 'tis/performance/rankings';
        $data['main_menu'] = 'tis_performance';
        
        $data['rankings'] = $this->Teacher_performance_model->get_teacher_rankings($branch_id, $session_id, $term_id);
        
        $this->load->view('layout/index', $data);
    }
    
    public function duties() {
        if (!get_permission('tis_duties', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $teacher_id = get_loggedin_user_id();
        
        $data['title'] = translate('my_duties');
        $data['sub_page'] = 'tis/duties';
        $data['main_menu'] = 'tis';
        
        $data['duties'] = $this->Duty_model->get_teacher_duties($teacher_id, $branch_id);
        
        $this->load->view('layout/index', $data);
    }
    
    public function assign_duties() {
        if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $session_id = get_session_id();
        $term_id = get_current_term_id();
        
        if ($_POST) {
            $this->form_validation->set_rules('teacher_id', 'Teacher', 'required');
            $this->form_validation->set_rules('duty_type_id', 'Duty Type', 'required');
            $this->form_validation->set_rules('duty_name', 'Duty Name', 'required');
            $this->form_validation->set_rules('due_date', 'Due Date', 'required');
            
            if ($this->form_validation->run()) {
                $data = [
                    'branch_id' => $branch_id,
                    'teacher_id' => $this->input->post('teacher_id'),
                    'duty_type_id' => $this->input->post('duty_type_id'),
                    'duty_name' => $this->input->post('duty_name'),
                    'description' => $this->input->post('description'),
                    'assigned_by' => get_loggedin_user_id(),
                    'assigned_date' => date('Y-m-d'),
                    'due_date' => date('Y-m-d', strtotime($this->input->post('due_date'))),
                    'status' => 'pending',
                    'points' => $this->input->post('points') ?: 1,
                    'session_id' => $session_id,
                    'term_id' => $term_id
                ];
                
                $this->Duty_model->assign_duty($data);
                $this->session->set_flashdata('success', translate('information_has_been_saved_successfully'));
                redirect('tis/duties/assign');
            }
        }
        
        $data['title'] = translate('assign_duties');
        $data['sub_page'] = 'tis/duties/assign';
        $data['main_menu'] = 'tis_duties';
        
        $data['teachers'] = $this->employee_model->getStaffList($branch_id, 3, 1);
        $data['duty_types'] = $this->db->get_where('duty_types', ['branch_id' => $branch_id, 'is_active' => 1])->result();
        
        $this->load->view('layout/index', $data);
    }
    
    public function duty_types() {
        if (!is_admin_loggedin() && !is_superadmin_loggedin()) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        if ($_POST) {
            $this->form_validation->set_rules('name', 'Duty Name', 'required');
            $this->form_validation->set_rules('category', 'Category', 'required');
            
            if ($this->form_validation->run()) {
                $data = [
                    'branch_id' => $branch_id,
                    'name' => $this->input->post('name'),
                    'category' => $this->input->post('category'),
                    'default_points' => $this->input->post('default_points') ?: 1,
                    'duration_hours' => $this->input->post('duration_hours'),
                    'is_active' => 1
                ];
                
                $this->db->insert('duty_types', $data);
                $this->session->set_flashdata('success', translate('information_has_been_saved_successfully'));
                redirect('tis/duties/types');
            }
        }
        
        $data['title'] = translate('duty_types');
        $data['sub_page'] = 'tis/duties/types';
        $data['main_menu'] = 'tis_duties';
        
        $data['duty_types'] = $this->db->get_where('duty_types', ['branch_id' => $branch_id])->result();
        
        $this->load->view('layout/index', $data);
    }
    
    public function update_duty_status() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $duty_id = $this->input->post('duty_id');
        $status = $this->input->post('status');
        $teacher_id = get_loggedin_user_id();
        
        $duty = $this->db->get_where('teacher_duties', ['id' => $duty_id, 'teacher_id' => $teacher_id])->row();
        
        if ($duty) {
            $update_data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            
            if ($status == 'completed') {
                $update_data['completed_date'] = date('Y-m-d');
            }
            
            $this->db->where('id', $duty_id)->update('teacher_duties', $update_data);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        }
    }
    
    public function risks() {
        if (!get_permission('tis_risks', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        $session_id = get_session_id();
        $term_id = get_current_term_id();
        
        $data['title'] = translate('risk_analysis');
        $data['sub_page'] = 'tis/risks';
        $data['main_menu'] = 'tis';
        
        $data['teachers'] = $this->employee_model->getStaffList($branch_id, 3, 1);
        $data['risk_data'] = [];
        
        foreach ($data['teachers'] as $teacher) {
            $risk = $this->Teacher_risk_model->get_teacher_risk($teacher->id, $session_id, $term_id);
            $performance = $this->Teacher_performance_model->get_teacher_performance($teacher->id, $session_id, $term_id);
            $workload = $this->Teacher_workload_model->get_teacher_workload($teacher->id, $session_id);
            
            $data['risk_data'][$teacher->id] = [
                'risk' => $risk,
                'performance' => $performance,
                'workload' => $workload
            ];
        }
        
        $this->load->view('layout/index', $data);
    }
    
    public function recommendations() {
        if (!get_permission('tis_recommendations', 'is_view')) {
            access_denied();
        }
        
        $branch_id = $this->application_model->get_branch_id();
        
        $data['title'] = translate('recommendations');
        $data['sub_page'] = 'tis/recommendations';
        $data['main_menu'] = 'tis';
        
        $data['recommendations'] = $this->Recommendation_engine->get_recommendations($branch_id, 'pending');
        
        $this->load->view('layout/index', $data);
    }
    
    public function update_recommendation_status() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $rec_id = $this->input->post('rec_id');
        $status = $this->input->post('status');
        
        $update_data = [
            'status' => $status,
            'reviewed_by' => get_loggedin_user_id(),
            'reviewed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status == 'implemented') {
            $update_data['implemented_at'] = date('Y-m-d H:i:s');
        }
        
        $this->db->where('id', $rec_id)->update('system_recommendations', $update_data);
        
        echo json_encode(['status' => 'success']);
    }
    
    private function get_dashboard_summary($branch_id, $session_id, $term_id) {
        $total_teachers = $this->db->where('branch_id', $branch_id)
            ->where('role', 3)
            ->count_all_results('login_credential');
        
        $overloaded = $this->db->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('status', 'overloaded')
            ->count_all_results('teacher_workload');
        
        $high_performers = $this->db->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('term_id', $term_id)
            ->where('rating', 'A')
            ->count_all_results('teacher_performance_scores');
        
        $high_risk = $this->db->where('branch_id', $branch_id)
            ->where('session_id', $session_id)
            ->where('term_id', $term_id)
            ->where('risk_level', 'high')
            ->count_all_results('teacher_risk_predictions');
        
        $pending_duties = $this->db->where('branch_id', $branch_id)
            ->where('status', 'pending')
            ->count_all_results('teacher_duties');
        
        $overdue_duties = $this->db->where('branch_id', $branch_id)
            ->where('status', 'pending')
            ->where('due_date <', date('Y-m-d'))
            ->count_all_results('teacher_duties');
        
        return [
            'total_teachers' => $total_teachers,
            'overloaded' => $overloaded,
            'high_performers' => $high_performers,
            'high_risk' => $high_risk,
            'pending_duties' => $pending_duties,
            'overdue_duties' => $overdue_duties
        ];
    }
}