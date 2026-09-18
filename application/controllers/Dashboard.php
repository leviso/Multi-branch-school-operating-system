<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 5.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Dashboard.php
 * @copyright : Reserved Synobix Team
 */

class Dashboard extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('dashboard_model');
    }

    public function index()
    {
        if (is_student_loggedin() || is_parent_loggedin()) {
            $studentID = 0;
            if (is_student_loggedin()) {
                $this->data['title'] = translate('welcome_to') . " " . $this->session->userdata('name');
                $studentID = get_loggedin_user_id();
            }elseif (is_parent_loggedin()) {
                $studentID = $this->session->userdata('myChildren_id');
                if (!empty($studentID)) {
                    $this->data['title'] = get_type_name_by_id('student', $studentID, 'first_name') . " - " . translate('dashboard');
                } else {
                    $this->data['title'] = translate('welcome_to') . " " . $this->session->userdata('name');
                }
            }
            $this->data['student_id'] = $studentID;
            $schoolID = get_loggedin_branch_id();
            $this->data['school_id'] = $schoolID;
            $this->data['sub_page'] = 'userrole/dashboard';
        } else {
            if (is_superadmin_loggedin()) {
                if ($this->input->get('school_id')) {
                    $schoolID = $this->input->get('school_id');
                    $this->data['title'] = get_type_name_by_id('branch', $schoolID) . " " . translate('branch_dashboard');
                } else {
                    $this->data['title'] = translate('all_branch_dashboard');
                    $schoolID = "";
                }
            } else {
                $schoolID = get_loggedin_branch_id();
                $this->data['title'] = get_type_name_by_id('branch', $schoolID) . " " . translate('branch_dashboard');
            }
            $getSQLMode = $this->application_model->getSQLMode();
            $this->data['school_id'] = $schoolID;
            $this->data['sqlMode'] = $getSQLMode;
            if ($getSQLMode == false) {
                $this->data['fees_summary'] = $this->dashboard_model->annualFeessummaryCharts($schoolID);
            } else {
                $this->data['fees_summary'] = array(
                    'total_fee' => 0,
                    'total_paid' => 0,
                    'total_due' => 0,
                );
            }
            $this->data['student_by_class'] = $this->dashboard_model->getStudentByClass($schoolID);
            $this->data['income_vs_expense'] = $this->dashboard_model->getIncomeVsExpense($schoolID);
            $this->data['weekend_attendance'] = $this->dashboard_model->getWeekendAttendance($schoolID);
            $this->data['get_monthly_admission'] = $this->dashboard_model->getMonthlyAdmission($schoolID);
            $this->data['get_voucher'] = $this->dashboard_model->getVoucher($schoolID);
            $this->data['get_transport_route'] = $this->dashboard_model->get_transport_route($schoolID);
            $this->data['get_total_student'] = $this->dashboard_model->get_total_student($schoolID);
            $this->data['sub_page'] = 'dashboard/index';
        }
        $language = 'en';
        $jsArray = array(
            'vendor/chartjs/chart.min.js',
            'vendor/echarts/echarts.common.min.js',
            'vendor/moment/moment.js',
            'vendor/fullcalendar/fullcalendar.js',
        ); 
        if ($this->session->userdata('set_lang') != 'english') {
            $language = $this->dashboard_model->languageShortCodes($this->session->userdata('set_lang'));
            $jsArray[] = "vendor/fullcalendar/locale/$language.js";
        }
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/fullcalendar/fullcalendar.css',
            ),
            'js' => $jsArray
        );
        $this->data['language'] = $language;
        $this->data['main_menu'] = 'dashboard';
        $this->load->view('layout/index', $this->data);
    }

    // Dashboard controller addition
public function emergency_widget() {
    $role_id = $this->session->userdata('role_id');
    $branch_id = ($role_id == 1) ? null : $this->session->userdata('branch_id');
    
    $data['active_incidents'] = $this->hostel_emergency_model->getActiveIncidents($branch_id);
    $data['critical_incidents'] = $this->hostel_emergency_model->getCriticalIncidents($branch_id);
    $data['recent_incidents'] = $this->hostel_emergency_model->getRecentIncidents($branch_id);
    
    $this->load->view('dashboard/emergency_widget', $data);
}
   /**
 * TIS Dashboard
 */
public function tis() {
    if (!get_permission('tis_dashboard', 'is_view')) {
        access_denied();
    }
    
    // Get branch_id - handle superadmin case
    $branch_id = $this->application_model->get_branch_id();
    
    // For superadmin, allow branch selection via GET parameter
    if (is_superadmin_loggedin() && $this->input->get('branch_id')) {
        $branch_id = $this->input->get('branch_id');
    }
    
    // If still no branch_id, show branch selector for superadmin
    if (!$branch_id) {
        if (is_superadmin_loggedin()) {
            $data['title'] = translate('teacher_intelligence');
            $data['sub_page'] = 'tis/dashboard';
            $data['main_menu'] = 'tis';
            $data['branches'] = $this->db->get('branch')->result();
            $data['show_branch_selector'] = true;
            $this->load->view('layout/index', $data);
            return;
        } else {
            show_error('Branch not selected', 400);
        }
    }
    
    $data['title'] = translate('teacher_intelligence');
    $data['sub_page'] = 'tis/dashboard';
    $data['main_menu'] = 'tis';
    $data['branch_id'] = $branch_id;
    $data['show_branch_selector'] = false;
    
    $session_id = get_session_id();
    
    // Get summary from model
    $data['summary'] = $this->employee_model->get_tis_dashboard_summary($branch_id);
    
    // Get teachers with workload and performance data
    $data['teachers'] = $this->db->select('s.id, s.name, s.staff_id, s.designation, sd.name as designation_name,
                                           tw.workload_score, tw.status,
                                           tps.total_score, tps.rating,
                                           trp.risk_level')
        ->from('staff s')
        ->join('login_credential lc', 'lc.user_id = s.id AND lc.role = 3')
        ->join('staff_designation sd', 'sd.id = s.designation', 'left')
        ->join('teacher_workload tw', 'tw.teacher_id = s.id AND tw.session_id = ' . $this->db->escape($session_id), 'left')
        ->join('teacher_performance_scores tps', 'tps.teacher_id = s.id AND tps.session_id = ' . $this->db->escape($session_id), 'left')
        ->join('teacher_risk_predictions trp', 'trp.teacher_id = s.id AND trp.session_id = ' . $this->db->escape($session_id), 'left')
        ->where('s.branch_id', $branch_id)
        ->order_by('s.name', 'ASC')
        ->get()
        ->result();
    
    $this->load->view('layout/index', $data);
}
}
