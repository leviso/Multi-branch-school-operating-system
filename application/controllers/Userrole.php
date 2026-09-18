<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Lsquare solutions school management system
 * @version : 1.0
 * @developed by : lsquare solutions
 * @support : lebeezsquare.com
 * @author url : https://studportal.co.ke/school
 * @filename : Userrole.php
 * @copyright : Reserved Synobix Team
 */

class Userrole extends User_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('userrole_model');
        $this->load->model('leave_model');
        $this->load->model('fees_model');
        $this->load->model('exam_model');
    }

    public function index()
    {
        redirect(base_url(), 'refresh');
    }

    /* getting all teachers list */
    public function teacher()
    {
        $this->data['title'] = translate('teachers');
        $this->data['sub_page'] = 'userrole/teachers';
        $this->data['main_menu'] = 'teachers';
        $this->load->view('layout/index', $this->data);
    }

    public function subject()
    {
        $this->data['title'] = translate('subject');
        $this->data['sub_page'] = 'userrole/subject';
        $this->data['main_menu'] = 'academic';
        $this->load->view('layout/index', $this->data);
    }

    /*student or parent timetable preview page*/
    public function class_schedule()
    {
        $stu = $this->userrole_model->getStudentDetails();
        $arrayTimetable = array(
            'class_id' => $stu['class_id'],
            'section_id' => $stu['section_id'],
            'session_id' => get_session_id(),
        );
        $this->db->order_by('time_start', 'asc');
        $this->data['timetables'] = $this->db->get_where('timetable_class', $arrayTimetable)->result();
        $this->data['student'] = $stu;
        $this->data['title'] = translate('class') . " " . translate('schedule');
        $this->data['sub_page'] = 'userrole/class_schedule';
        $this->data['main_menu'] = 'academic';
        $this->load->view('layout/index', $this->data);
    }

    public function leave_request()
    {
        $stu = $this->userrole_model->getStudentDetails();
        if (isset($_POST['save'])) {
            $this->form_validation->set_rules('leave_category', translate('leave_category'), 'required|callback_leave_check');
            $this->form_validation->set_rules('daterange', translate('leave_date'), 'trim|required|callback_date_check');
            $this->form_validation->set_rules('attachment_file', translate('attachment'), 'callback_fileHandleUpload[attachment_file]');
            if ($this->form_validation->run() !== false) {
                $leave_type_id = $this->input->post('leave_category');
                $branch_id = $this->application_model->get_branch_id();
                $daterange = explode(' - ', $this->input->post('daterange'));
                $start_date = date("Y-m-d", strtotime($daterange[0]));
                $end_date = date("Y-m-d", strtotime($daterange[1]));
                $reason = $this->input->post('reason');
                $apply_date = date("Y-m-d H:i:s");
                $datetime1 = new DateTime($start_date);
                $datetime2 = new DateTime($end_date);
                $leave_days = $datetime2->diff($datetime1)->format("%a") + 1;
                $orig_file_name = '';
                $enc_file_name = '';
                // upload attachment file
                if (isset($_FILES["attachment_file"]) && !empty($_FILES['attachment_file']['name'])) {
                    $config['upload_path'] = './uploads/attachments/leave/';
                    $config['allowed_types'] = "*";
                    $config['max_size'] = '2024';
                    $config['encrypt_name'] = true;
                    $this->upload->initialize($config);
                    $this->upload->do_upload("attachment_file");
                    $orig_file_name = $this->upload->data('orig_name');
                    $enc_file_name = $this->upload->data('file_name');
                }
                $arrayData = array(
                    'user_id' => $stu['student_id'],
                    'role_id' => 7,
                    'session_id' => get_session_id(),
                    'category_id' => $leave_type_id,
                    'reason' => $reason,
                    'branch_id' => $branch_id,
                    'start_date' => date("Y-m-d", strtotime($start_date)),
                    'end_date' => date("Y-m-d", strtotime($end_date)),
                    'leave_days' => $leave_days,
                    'status' => 1,
                    'orig_file_name' => $orig_file_name,
                    'enc_file_name' => $enc_file_name,
                    'apply_date' => $apply_date,
                );
                $this->db->insert('leave_application', $arrayData);
                $insert_id = $this->db->insert_id();

                // Send SMS notification to Admin and Parent
                $this->_send_new_leave_alert_sms($insert_id);

                set_alert('success', translate('information_has_been_saved_successfully'));
                redirect(base_url('userrole/leave_request'));
            }
        }
        $where = array('la.user_id' => $stu['student_id'], 'la.role_id' => 7);
        $this->data['leavelist'] = $this->leave_model->getLeaveList($where);
        $this->data['title'] = translate('leaves');
        $this->data['sub_page'] = 'userrole/leave_request';
        $this->data['main_menu'] = 'leave';
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/dropify/css/dropify.min.css',
                'vendor/daterangepicker/daterangepicker.css',
            ),
            'js' => array(
                'vendor/dropify/js/dropify.min.js',
                'vendor/moment/moment.js',
                'vendor/daterangepicker/daterangepicker.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }

    // date check for leave request
    public function date_check($daterange)
    {
        $daterange = explode(' - ', $daterange);
        $start_date = date("Y-m-d", strtotime($daterange[0]));
        $end_date = date("Y-m-d", strtotime($daterange[1]));
        $today = date('Y-m-d');
        if ($today == $start_date) {
            $this->form_validation->set_message('date_check', "You can not leave the current day.");
            return false;
        }
        if ($this->input->post('applicant_id')) {
            $applicant_id = $this->input->post('applicant_id');
            $role_id = $this->input->post('user_role');
        } else {
            $applicant_id = get_loggedin_user_id();
            $role_id = loggedin_role_id();
        }
        $getUserLeaves = $this->db->get_where('leave_application', array('user_id' => $applicant_id, 'role_id' => $role_id))->result();
        if (!empty($getUserLeaves)) {
            foreach ($getUserLeaves as $user_leave) {
                $get_dates = $this->user_leave_days($user_leave->start_date, $user_leave->end_date);
                $result_start = in_array($start_date, $get_dates);
                $result_end = in_array($end_date, $get_dates);
                if (!empty($result_start) || !empty($result_end)) {
                    $this->form_validation->set_message('date_check', 'Already have leave in the selected time.');
                    return false;
                }
            }
        }
        return true;
    }

    public function leave_check($type_id)
    {
        if (!empty($type_id)) {
            $daterange = explode(' - ', $this->input->post('daterange'));
            $start_date = date("Y-m-d", strtotime($daterange[0]));
            $end_date = date("Y-m-d", strtotime($daterange[1]));

            if ($this->input->post('applicant_id')) {
                $applicant_id = $this->input->post('applicant_id');
                $role_id = $this->input->post('user_role');
            } else {
                $applicant_id = get_loggedin_user_id();
                $role_id = loggedin_role_id();
            }
            if (!empty($start_date) && !empty($end_date)) {
                $leave_total = get_type_name_by_id('leave_category', $type_id, 'days');
                $total_spent = $this->db->select('IFNULL(SUM(leave_days), 0) as total_days')
                    ->where(array('user_id' => $applicant_id, 'role_id' => $role_id, 'category_id' => $type_id, 'status' => '2'))
                    ->get('leave_application')->row()->total_days;

                $datetime1 = new DateTime($start_date);
                $datetime2 = new DateTime($end_date);
                $leave_days = $datetime2->diff($datetime1)->format("%a") + 1;
                $left_leave = ($leave_total - $total_spent);
                if ($left_leave < $leave_days) {
                    $this->form_validation->set_message('leave_check', "Applyed for $leave_days days, get maximum $left_leave Days days.");
                    return false;
                } else {
                    return true;
                }
            } else {
                $this->form_validation->set_message('leave_check', "Select all required field.");
                return false;
            }
        }
    }

    public function user_leave_days($start_date, $end_date)
    {
        $dates = array();
        $current = strtotime($start_date);
        $end_date = strtotime($end_date);
        while ($current <= $end_date) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }
        return $dates;
    }

    public function attachments()
    {
        $this->data['title'] = translate('attachments');
        $this->data['sub_page'] = 'userrole/attachments';
        $this->data['main_menu'] = 'attachments';
        $this->load->view('layout/index', $this->data);
    }

    public function playVideo()
    {
        $id = $this->input->post('id');
        $file = get_type_name_by_id('attachments', $id, 'enc_name');
        echo '<video width="560" controls id="attachment_video">';
        echo '<source src="' . base_url('uploads/attachments/' . $file) . '" type="video/mp4">';
        echo 'Your browser does not support HTML video.';
        echo '</video>';
    }

    // file downloader
    public function download()
    {
        $encrypt_name = urldecode($this->input->get('file'));
        if (preg_match('/^[^.][-a-z0-9_.]+[a-z]$/i', $encrypt_name)) {
            $file_name = $this->db->select('file_name')->where('enc_name', $encrypt_name)->get('attachments')->row()->file_name;
            if (!empty($file_name)) {
                $this->load->helper('download');
                force_download($file_name, file_get_contents('uploads/attachments/' . $encrypt_name));
            }
        }
    }

    /* exam timetable preview page */
    public function exam_schedule()
    {
        $stu = $this->userrole_model->getStudentDetails();
        $this->data['student'] = $stu;
        $this->db->select('*');
        $this->db->from('timetable_exam');
        $this->db->where('class_id', $stu['class_id']);
        $this->db->where('section_id', $stu['section_id']);
        $this->db->where('session_id', get_session_id());
        $this->db->group_by('exam_id');
        $this->db->order_by('exam_id', 'asc');
        $results = $this->db->get()->result_array();
        $this->data['exams'] = $results;
        $this->data['title'] = translate('exam') . " " . translate('schedule');
        $this->data['sub_page'] = 'userrole/exam_schedule';
        $this->data['main_menu'] = 'exam';
        $this->load->view('layout/index', $this->data);
    }

    /* hostels user interface */
    public function hostels()
    {
        $this->data['student'] = $this->userrole_model->getStudentDetails();
        $this->data['title'] = translate('hostels');
        $this->data['sub_page'] = 'userrole/hostels';
        $this->data['main_menu'] = 'supervision';
        $this->load->view('layout/index', $this->data);
    }

    /* route user interface */
    public function route()
    {
        $stu = $this->userrole_model->getStudentDetails();
        $this->data['route'] = $this->userrole_model->getRouteDetails($stu['route_id'], $stu['vehicle_id']);
        $this->data['title'] = translate('route_master');
        $this->data['sub_page'] = 'userrole/transport_route';
        $this->data['main_menu'] = 'supervision';
        $this->load->view('layout/index', $this->data);
    }

    /* after login students or parents produced reports here */
    public function attendance()
    {
        $this->load->model('attendance_model');
        if ($this->input->post('submit') == 'search') {
            $this->data['month'] = date('m', strtotime($this->input->post('timestamp')));
            $this->data['year'] = date('Y', strtotime($this->input->post('timestamp')));
            $this->data['days'] = cal_days_in_month(CAL_GREGORIAN, $this->data['month'], $this->data['year']);
            $this->data['student'] = $this->userrole_model->getStudentDetails();
        }
        $this->data['title'] = translate('student_attendance');
        $this->data['sub_page'] = 'userrole/attendance';
        $this->data['main_menu'] = 'attendance';
        $this->load->view('layout/index', $this->data);
    }

    // book page
    public function book()
    {
        $this->data['booklist'] = $this->app_lib->getTable('book');
        $this->data['title'] = translate('books');
        $this->data['sub_page'] = 'userrole/book';
        $this->data['main_menu'] = 'library';
        $this->load->view('layout/index', $this->data);
    }

    public function book_request()
    {
        $stu = $this->userrole_model->getStudentDetails();
        if ($_POST) {
            $this->form_validation->set_rules('book_id', translate('book_title'), 'required|callback_validation_stock');
            $this->form_validation->set_rules('date_of_issue', translate('date_of_issue'), 'trim|required');
            $this->form_validation->set_rules('date_of_expiry', translate('date_of_expiry'), 'trim|required|callback_validation_date');
            if ($this->form_validation->run() !== false) {
                $arrayIssue = array(
                    'branch_id' => $stu['branch_id'],
                    'book_id' => $this->input->post('book_id'),
                    'user_id' => $stu['student_id'],
                    'role_id' => 7,
                    'date_of_issue' => date("Y-m-d", strtotime($this->input->post('date_of_issue'))),
                    'date_of_expiry' => date("Y-m-d", strtotime($this->input->post('date_of_expiry'))),
                    'issued_by' => get_loggedin_user_id(),
                    'status' => 0,
                    'session_id' => get_session_id(),
                );
                $this->db->insert('book_issues', $arrayIssue);
                set_alert('success', translate('information_has_been_saved_successfully'));
                $url = base_url('userrole/book_request');
                $array = array('status' => 'success', 'url' => $url, 'error' => '');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $this->data['stu'] = $stu;
        $this->data['title'] = translate('library');
        $this->data['sub_page'] = 'userrole/book_request';
        $this->data['main_menu'] = 'library';
        $this->load->view('layout/index', $this->data);
    }

    // book date validation
    public function validation_date($date)
    {
        if ($date) {
            $date = strtotime($date);
            $today = strtotime(date('Y-m-d'));
            if ($today >= $date) {
                $this->form_validation->set_message("validation_date", translate('today_or_the_previous_day_can_not_be_issued'));
                return false;
            } else {
                return true;
            }
        }
    }

    // validation book stock
    public function validation_stock($book_id)
    {
        $query = $this->db->select('total_stock,issued_copies')->where('id', $book_id)->get('book')->row_array();
        $stock = $query['total_stock'];
        $issued = $query['issued_copies'];
        if ($stock == 0 || $issued >= $stock) {
            $this->form_validation->set_message("validation_stock", translate('the_book_is_not_available_in_stock'));
            return false;
        } else {
            return true;
        }
    }

    public function event()
    {
        $branchID = $this->application_model->get_branch_id();
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('events');
        $this->data['sub_page'] = 'userrole/event';
        $this->data['main_menu'] = 'event';
        $this->load->view('layout/index', $this->data);
    }

    /* invoice user interface with information are controlled here */
    public function invoice()
    {
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/dropify/css/dropify.min.css',
            ),
            'js' => array(
                'vendor/dropify/js/dropify.min.js',
            ),
        );
        $stu = $this->userrole_model->getStudentDetails();
        $this->data['config'] = $this->get_payment_config();
        $this->data['getUser'] = $this->userrole_model->getUserDetails();
        $this->data['getOfflinePaymentsConfig'] = $this->userrole_model->getOfflinePaymentsConfig();
        $this->data['invoice'] = $this->fees_model->getInvoiceStatus($stu['student_id']);
        $this->data['basic'] = $this->fees_model->getInvoiceBasic($stu['student_id']);
        $this->data['title'] = translate('fees_history');
        $this->data['main_menu'] = 'fees';
        $this->data['sub_page'] = 'userrole/collect';
        $this->load->view('layout/index', $this->data);
    }

    /* invoice user interface with information are controlled here */
    public function report_card()
    {
        $this->data['stu'] = $this->userrole_model->getStudentDetails();
        $this->data['title'] = translate('exam_master');
        $this->data['main_menu'] = 'exam';
        $this->data['sub_page'] = 'userrole/report_card';
        $this->load->view('layout/index', $this->data);
    }

    public function homework()
{
    $stu = $this->userrole_model->getStudentDetails();
    
    // Get homework only for subjects the student is enrolled in
    $this->load->model('homework_model');
    $this->data['homeworklist'] = $this->homework_model->getStudentHomeworkList($stu['student_id'], $stu['branch_id']);
    
    $this->data['title'] = translate('homework');
    $this->data['headerelements'] = array(
        'css' => array(
            'vendor/bootstrap-fileupload/bootstrap-fileupload.min.css',
        ),
        'js' => array(
            'vendor/bootstrap-fileupload/bootstrap-fileupload.min.js',
        ),
    );
    $this->data['main_menu'] = 'homework';
    $this->data['sub_page'] = 'userrole/homework';
    $this->load->view('layout/index', $this->data);
}

    public function getHomeworkAssignment()
    {
        if (!is_student_loggedin()) {
            access_denied();
        }
        $id = $this->input->post('id');
        $r = $this->db->where(array('homework_id' => $id, 'student_id' => get_loggedin_user_id()))->get('homework_submit')->row_array();
        $array = array(
            'id' => $r['id'],
            'message' => $r['message'],
            'file_name' => $r['enc_name'],
        );
        echo json_encode($array);
    }

    /* homework form validation rules */
    protected function homework_validation()
    {
        $this->form_validation->set_rules('message', translate('message'), 'trim|required');
        $this->form_validation->set_rules('attachment_file', translate('attachment'), 'callback_assignment_handle_upload');
    }

    // upload file form validation
    public function assignment_handle_upload()
    {
        if (isset($_FILES["attachment_file"]) && !empty($_FILES['attachment_file']['name'])) {
            $allowedExts = array_map('trim', array_map('strtolower', explode(',', $this->data['global_config']['file_extension'])));
            $allowedSizeKB = $this->data['global_config']['file_size'];
            $allowedSize = floatval(1024 * $allowedSizeKB);
            $file_size = $_FILES["attachment_file"]["size"];
            $file_name = $_FILES["attachment_file"]["name"];
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            if ($files = filesize($_FILES["attachment_file"]['tmp_name'])) {
                if (!in_array(strtolower($extension), $allowedExts)) {
                    $this->form_validation->set_message('handle_upload', translate('this_file_type_is_not_allowed'));
                    return false;
                }
                if ($file_size > $allowedSize) {
                    $this->form_validation->set_message('handle_upload', translate('file_size_shoud_be_less_than') . " $allowedSizeKB KB.");
                    return false;
                }
            } else {
                $this->form_validation->set_message('handle_upload', translate('error_reading_the_file'));
                return false;
            }
            return true;
        } else {
            if (!empty($_POST['old_file'])) {
                return true;
            }

            $this->form_validation->set_message('assignment_handle_upload', "The Attachment field is required.");
            return false;
        }
    }

    public function assignment_upload()
    {
        if ($_POST) {
            $this->homework_validation();
            if ($this->form_validation->run() !== false) {
                $message = $this->input->post('message');
                $homeworkID = $this->input->post('homework_id');
                $assigmentID = $this->input->post('assigment_id');
                $arrayDB = array(
                    'homework_id' => $homeworkID,
                    'student_id' => get_loggedin_user_id(),
                    'message' => $message,
                );

                if (isset($_FILES["attachment_file"]) && !empty($_FILES['attachment_file']['name'])) {
                    $config = array();
                    $config['upload_path'] = 'uploads/attachments/homework_submit/';
                    $config['encrypt_name'] = true;
                    $config['allowed_types'] = '*';
                    $this->upload->initialize($config);
                    if ($this->upload->do_upload("attachment_file")) {
                        $encrypt_name = $this->input->post('old_file');
                        if (!empty($encrypt_name)) {
                            $file_name = $config['upload_path'] . $encrypt_name;
                            if (file_exists($file_name)) {
                                unlink($file_name);
                            }
                        }

                        $orig_name = $this->upload->data('orig_name');
                        $enc_name = $this->upload->data('file_name');
                        $arrayDB['enc_name'] = $enc_name;
                        $arrayDB['file_name'] = $orig_name;
                    } else {
                        set_alert('error', $this->upload->display_errors());
                    }
                }

                if (empty($assigmentID)) {
                    $this->db->insert('homework_submit', $arrayDB);
                } else {
                    $this->db->where('id', $assigmentID);
                    $this->db->update('homework_submit', $arrayDB);
                }
                set_alert('success', translate('information_has_been_saved_successfully'));
                $url = base_url('userrole/homework');
                $array = array('status' => 'success', 'url' => $url);
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
    }

    public function live_class()
    {
        if (!is_student_loggedin()) {
            access_denied();
        }
        $this->data['branch_id'] = $this->application_model->get_branch_id();
        $this->data['title'] = translate('live_class_rooms');
        $this->data['sub_page'] = 'userrole/live_class';
        $this->data['main_menu'] = 'live_class';
        $this->load->view('layout/index', $this->data);
    }

    public function joinModal()
    {
        if (!is_student_loggedin()) {
            access_denied();
        }
        $this->data['meetingID'] = $this->input->post('meeting_id');
        echo $this->load->view('userrole/live_classModal', $this->data, true);
    }

    public function livejoin()
    {
        if (!is_student_loggedin()) {
            access_denied();
        }
        $meetingID = $this->input->get('meeting_id', true);
        $liveID = $this->input->get('live_id', true);
        if (empty($meetingID) || empty($liveID)) {
            access_denied();
        }

        $getMeeting = $this->userrole_model->get('live_class', array('id' => $liveID, 'meeting_id' => $meetingID), true);
        if ($getMeeting['live_class_method'] == 1) {
            $this->load->view('userrole/livejoin', $this->data);
        } else {
            $getStudent = $this->application_model->getStudentDetails(get_loggedin_user_id());
            $bbb_config = json_decode($getMeeting['bbb'], true);
            // get BBB api config
            $getConfig = $this->userrole_model->get('live_class_config', array('branch_id' => $getMeeting['branch_id']), true);
            $api_keys = array(
                'bbb_security_salt' => $getConfig['bbb_salt_key'],
                'bbb_server_base_url' => $getConfig['bbb_server_base_url'],
            );
            $this->load->library('bigbluebutton_lib', $api_keys);

            $arrayBBB = array(
                'meeting_id' => $getMeeting['meeting_id'],
                'title' => $getMeeting['title'],
                'attendee_password' => $bbb_config['attendee_password'],
                'presen_name' => $getStudent['first_name'] . ' ' . $getStudent['last_name'] . ' (Roll - ' . $getStudent['roll'] . ')',
            );

            $response = $this->bigbluebutton_lib->joinMeeting($arrayBBB);
            redirect($response);
        }
    }

    public function live_atten()
    {
        $stu_id = get_loggedin_user_id();
        $id = $this->input->post('live_id');
        $arrayInsert = array(
            'live_class_id' => $id,
            'student_id' => $stu_id,
        );

        $this->db->where($arrayInsert);
        $query = $this->db->get('live_class_reports');
        if ($query->num_rows() > 0) {
            $arrayInsert['created_at'] = date("Y-m-d H:i:s");
            $this->db->where('id', $query->row()->id);
            $this->db->update('live_class_reports', $arrayInsert);
        } else {
            $this->db->insert('live_class_reports', $arrayInsert);
        }
        $array = array('status' => 1);
        echo json_encode($array);
    }

    /* Online exam controller */
    public function online_exam()
    {
        if (!is_student_loggedin()) {
            access_denied();
        }

        $this->load->model('onlineexam_model');
        $this->data['headerelements'] = array(
            'js' => array(
                'js/online-exam.js',
            ),
        );
        $this->data['title'] = translate('online_exam');
        $this->data['sub_page'] = 'userrole/online_exam';
        $this->data['main_menu'] = 'onlineexam';
        $this->load->view('layout/index', $this->data);
    }

    public function getExamListDT()
    {
        if ($_POST) {
            $this->load->model('onlineexam_model');
            $postData = $this->input->post();
            $currencySymbol = $this->data['global_config']['currency_symbol'];
            echo $this->userrole_model->examListDT($postData, $currencySymbol);
        }
    }

    /* Online exam controller */
    public function onlineexam_take($id = '')
    {
        if (!is_student_loggedin()) {
            access_denied();
        }
        $this->load->model('onlineexam_model');
        $this->data['headerelements'] = array(
            'js' => array(
                'js/online-exam.js',
            ),
        );
        $exam = $this->userrole_model->getExamDetails($id);
        if (empty($exam)) {
            redirect(base_url('userrole/online_exam'));
        }

        if ($exam->exam_type == 1 && $exam->payment_status == 0) {
            set_alert('error', "You have to make payment to attend this exam !");
            redirect(base_url('userrole/online_exam'));
        }

        $this->data['studentSubmitted'] = $this->onlineexam_model->getStudentSubmitted($exam->id);
        $this->data['exam'] = $exam;
        $this->data['title'] = translate('online_exam');
        $this->data['sub_page'] = 'onlineexam/take';
        $this->data['main_menu'] = 'onlineexam';
        $this->load->view('layout/index', $this->data);
    }

    public function ajaxQuestions()
    {
        $status = 0;
        $totalQuestions = 0;
        $message = "";
        $this->load->model('onlineexam_model');
        $examID = $this->input->post('exam_id');
        $exam = $this->userrole_model->getExamDetails($examID);
        $totalQuestions = $exam->questions_qty;
        $studentAttempt = $this->onlineexam_model->getStudentAttempt($exam->id);
        $examSubmitted = $this->onlineexam_model->getStudentSubmitted($exam->id);
        if (!empty($exam)) {
            $startTime = strtotime($exam->exam_start);
            $endTime = strtotime($exam->exam_end);
            $now = strtotime("now");
            if (($startTime <= $now && $now <= $endTime) && (empty($examSubmitted)) && $exam->publish_status == 1) {
                if ($exam->limits_participation > $studentAttempt) {
                    $this->onlineexam_model->addStudentAttemts($exam->id);
                    $message = "";
                    $status = 1;
                } else {
                    $status = 0;
                    $message = "You already reach max exam attempt.";
                }
            } else {
                $message = "Maybe the test has expired or something wrong.";
            }
        }
        $data['exam'] = $exam;
        $data['questions'] = $this->onlineexam_model->getExamQuestions($exam->id, $exam->question_type);
        $pag_content = $this->load->view('onlineexam/ajax_take', $data, true);
        echo json_encode(array('status' => $status, 'total_questions' => $totalQuestions, 'message' => $message, 'page' => $pag_content));
    }

    public function getStudent_result()
    {
        if ($_POST) {
            $examID = $this->input->post('id');
            $this->load->model('onlineexam_model');
            $exam = $this->onlineexam_model->getExamDetails($examID);
            $data['exam'] = $exam;
            echo $this->load->view('userrole/onlineexam_result', $data, true);
        }
    }

    public function getExamPaymentForm()
    {
        if ($_POST) {
            $this->load->model('onlineexam_model');
            $status = 1;
            $page_data = "";
            $examID = $this->input->post('examID');
            $exam = $this->userrole_model->getExamDetails($examID);
            $message = "";
            if (empty($exam)) {
                $status = 0;
                $message = 'Exam not found.';
                echo json_encode(array('status' => $status, 'message' => $message));
                exit;
            }
            $data['config'] = $this->get_payment_config();
            $data['global_config'] = $this->data['global_config'];
            $data['getUser'] = $this->userrole_model->getUserDetails();
            $data['exam'] = $exam;
            if ($exam->payment_status == 0) {
                $status = 1;
                $page_data = $this->load->view('userrole/getExamPaymentForm', $data, true);
            } else {
                $status = 0;
                $message = 'The fee has already been paid.';
            }
            echo json_encode(array('status' => $status, 'message' => $message, 'data' => $page_data));
        }
    }

    public function onlineexam_submit_answer()
    {
        if ($_POST) {
            if (!is_student_loggedin()) {
                access_denied();
            }
            $studentID = get_loggedin_user_id();
            $online_examID = $this->input->post('online_exam_id');
            $variable = $this->input->post('answer');
            if (!empty($variable)) {
                $saveAnswer = array();
                foreach ($variable as $key => $value) {
                    if (isset($value[1])) {
                        $saveAnswer[] = array(
                            'student_id' => $studentID,
                            'online_exam_id' => $online_examID,
                            'question_id' => $key,
                            'answer' => $value[1],
                            'created_at' => date('Y-m-d H:i:s'),
                        );
                    }
                    if (isset($value[2])) {
                        $saveAnswer[] = array(
                            'student_id' => $studentID,
                            'online_exam_id' => $online_examID,
                            'question_id' => $key,
                            'answer' => json_encode($value[2]),
                            'created_at' => date('Y-m-d H:i:s'),
                        );
                    }
                    if (isset($value[3])) {
                        $saveAnswer[] = array(
                            'student_id' => $studentID,
                            'online_exam_id' => $online_examID,
                            'question_id' => $key,
                            'answer' => $value[3],
                            'created_at' => date('Y-m-d H:i:s'),
                        );
                    }
                    if (isset($value[4])) {
                        $saveAnswer[] = array(
                            'student_id' => $studentID,
                            'online_exam_id' => $online_examID,
                            'question_id' => $key,
                            'answer' => $value[4],
                            'created_at' => date('Y-m-d H:i:s'),
                        );
                    }
                }
                $this->db->insert_batch('online_exam_answer', $saveAnswer);
                $this->db->insert('online_exam_submitted', ['student_id' => get_loggedin_user_id(), 'online_exam_id' => $online_examID, 'created_at' => date('Y-m-d H:i:s')]);
            }
            set_alert('success', translate('your_exam_has_been_successfully_submitted'));
            redirect(base_url('userrole/online_exam'));
        }
    }

   public function offline_payments()
{
    if ($_POST) {
        $this->form_validation->set_rules('fees_type', translate('fees_type'), 'trim|required');
        $this->form_validation->set_rules('date_of_payment', translate('date_of_payment'), 'trim|required');
        $this->form_validation->set_rules('fee_amount', translate('amount'), array('trim', 'required', 'numeric', 'greater_than[0]', array('deposit_verify', array($this->fees_model, 'depositAmountVerify'))));
        $this->form_validation->set_rules('payment_method', translate('payment_method'), 'trim|required');
        $this->form_validation->set_rules('note', translate('note'), 'trim|required');
        $this->form_validation->set_rules('proof_of_payment', translate('proof_of_payment'), 'callback_fileHandleUpload[proof_of_payment]');
        
        if ($this->form_validation->run() !== false) {
            $feesType = explode("|", $this->input->post('fees_type'));
            $date_of_payment = $this->input->post('date_of_payment');
            $payment_method = $this->input->post('payment_method');
            $invoice_no = $this->input->post('invoice_no');
            
            // Get branch_id and student_enroll_id from POST or fallback to session
            $branch_id = $this->input->post('branch_id');
            $student_enroll_id = $this->input->post('student_enroll_id');
            
            // If student_enroll_id not in POST, get from session
            if (empty($student_enroll_id)) {
                $student_enroll_id = get_loggedin_user_id();
            }
            
            // If branch_id not in POST, get from session
            if (empty($branch_id)) {
                $branch_id = get_loggedin_branch_id();
            }

            $enc_name = null;
            $orig_name = null;
            
            // Handle file upload
            if (!empty($_FILES['proof_of_payment']['name'])) {
                $config = array();
                $config['upload_path'] = 'uploads/attachments/offline_payments/';
                $config['encrypt_name'] = true;
                $config['allowed_types'] = '*';
                $this->upload->initialize($config);
                
                if ($this->upload->do_upload("proof_of_payment")) {
                    $orig_name = $this->upload->data('orig_name');
                    $enc_name = $this->upload->data('file_name');
                } else {
                    // Upload failed - return error
                    $error = array('proof_of_payment' => $this->upload->display_errors('', ''));
                    $array = array('status' => 'fail', 'error' => $error);
                    echo json_encode($array);
                    return;
                }
            }

            $arrayFees = array(
                'fees_allocation_id' => $feesType[0],
                'fees_type_id' => $feesType[1],
                'invoice_no' => $invoice_no,
                'student_enroll_id' => $student_enroll_id,
                'amount' => $this->input->post('fee_amount'),
                'payment_method' => $payment_method,
                'reference' => $this->input->post('reference'),
                'note' => $this->input->post('note'),
                'payment_date' => date('Y-m-d', strtotime($date_of_payment)),
                'submit_date' => date('Y-m-d H:i:s'),
                'enc_file_name' => $enc_name,
                'orig_file_name' => $orig_name,
                'branch_id' => $branch_id,
                'status' => 1,
            );
            
            $this->db->insert('offline_fees_payments', $arrayFees);
            
            if ($this->db->affected_rows() > 0) {
                set_alert('success', "We will review and notify you of your payment.");
                // Return success with redirect URL
                $array = array('status' => 'success', 'url' => base_url('userrole/invoice'));
            } else {
                $array = array('status' => 'fail', 'error' => array('general' => 'Failed to save payment'));
            }
        } else {
            $error = $this->form_validation->error_array();
            $array = array('status' => 'fail', 'error' => $error);
        }
        echo json_encode($array);
    }
}

    // get payments details modal
    public function getOfflinePaymentslDetails()
    {
        if ($_POST) {
            $this->data['payments_id'] = $this->input->post('id');
            $this->load->view('userrole/getOfflinePaymentslDetails', $this->data);
        }
    }

    public function getBalanceByType()
    {
        $input = $this->input->post('typeID');
        if (empty($input)) {
            $balance = 0;
            $fine = 0;
        } else {
            $feesType = explode("|", $input);
            $fine = $this->fees_model->feeFineCalculation($feesType[0], $feesType[1]);
            $b = $this->fees_model->getBalance($feesType[0], $feesType[1]);
            $balance = $b['balance'];
            $fine = abs($fine - $b['fine']);
        }
        echo json_encode(array('balance' => $balance, 'fine' => $fine));
    }
        /**
     * Send SMS alert when student applies for leave
     * 
     * @param int $leave_id
     */
    private function _send_new_leave_alert_sms($leave_id)
    {
        // Get leave details with student name
        $leave = $this->db->select('l.*, CONCAT_WS(" ", s.first_name, s.last_name) as applicant_name, l.branch_id')
            ->from('leave_application l')
            ->join('student s', 's.id = l.user_id')
            ->where('l.id', $leave_id)
            ->get()
            ->row();
        
        if (!$leave) {
            return;
        }
        
        $start_date = date('d M Y', strtotime($leave->start_date));
        $end_date = date('d M Y', strtotime($leave->end_date));
        
        // Message for Admin
        $admin_message = "New Leave Request: Student {$leave->applicant_name} applied for leave from {$start_date} to {$end_date}. Reason: {$leave->reason}";
        
        // Get Admin mobile (role 2)
        $admin = $this->db->select('staff.mobileno')
            ->from('staff')
            ->join('login_credential', 'login_credential.user_id = staff.id')
            ->where('staff.branch_id', $leave->branch_id)
            ->where('login_credential.role', 2)
            ->where('staff.mobileno !=', '')
            ->get()
            ->row();
        
        // Send to Admin if exists
        if ($admin && !empty($admin->mobileno)) {
            $this->_send_sms($admin->mobileno, $admin_message, $leave->branch_id);
        }
        
        // Send to Parent
        $parent = $this->db->select('p.mobileno, p.name')
            ->from('student s')
            ->join('parent p', 'p.id = s.parent_id')
            ->where('s.id', $leave->user_id)
            ->where('p.mobileno !=', '')
            ->get()
            ->row();
        
        if ($parent && !empty($parent->mobileno)) {
            $parent_message = "Dear {$parent->name}, Your child {$leave->applicant_name} applied for leave from {$start_date} to {$end_date}. Reason: {$leave->reason}";
            $this->_send_sms($parent->mobileno, $parent_message, $leave->branch_id);
        }
    }
    
    /**
     * Core SMS sending function
     */
    private function _send_sms($mobile, $message, $branch_id)
    {
        if (empty($mobile) || empty($message)) {
            return false;
        }
        
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        if (strlen($mobile) < 9) {
            return false;
        }
        
        $this->load->model('sendsmsmail_model');
        
        $sms_credential = $this->db->select('sms_api.name as gateway')
            ->from('sms_credential')
            ->join('sms_api', 'sms_api.id = sms_credential.sms_api_id')
            ->where('sms_credential.branch_id', $branch_id)
            ->where('sms_credential.is_active', 1)
            ->get()
            ->row();
        
        $gateway = ($sms_credential && $sms_credential->gateway) ? $sms_credential->gateway : 'bulksmsbd';
        
        try {
            return $this->sendsmsmail_model->sendSMS_with_credit_check(
                $mobile, $message, '', '', $gateway, '', $branch_id
            );
        } catch (Exception $e) {
            return false;
        }
    }
        /**
     * View submitted homework (My Submissions)
     */
    public function homework_submissions()
    {
        if (!is_student_loggedin() && !is_parent_loggedin()) {
            access_denied();
        }
        
        $student_id = null;
        if (is_student_loggedin()) {
            $student_id = get_loggedin_user_id();
        } elseif (is_parent_loggedin()) {
            $student_id = get_activeChildren_id();
            if (empty($student_id)) {
                redirect(base_url('parents/my_children'));
                return;
            }
        }
        
        $this->data['submissions'] = $this->userrole_model->getStudentSubmissions($student_id);
        $this->data['title'] = translate('my_submissions');
        $this->data['sub_page'] = 'userrole/homework_submissions';
        $this->data['main_menu'] = 'homework';
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * View graded homework (with feedback)
     */
    public function homework_graded()
    {
        if (!is_student_loggedin() && !is_parent_loggedin()) {
            access_denied();
        }
        
        $student_id = null;
        if (is_student_loggedin()) {
            $student_id = get_loggedin_user_id();
        } elseif (is_parent_loggedin()) {
            $student_id = get_activeChildren_id();
            if (empty($student_id)) {
                redirect(base_url('parents/my_children'));
                return;
            }
        }
        
        $this->data['graded_homework'] = $this->userrole_model->getStudentGradedHomework($student_id);
        $this->data['title'] = translate('graded_homework');
        $this->data['sub_page'] = 'userrole/homework_graded';
        $this->data['main_menu'] = 'homework';
        $this->load->view('layout/index', $this->data);
    }
        /**
     * Download submitted homework file
     */
    public function download_submitted()
    {
        $encrypt_name = urldecode($this->input->get('file'));
        if (preg_match('/^[^.][-a-z0-9_.]+[a-z]$/i', $encrypt_name)) {
            $file_name = $this->db->select('file_name')->where('enc_name', $encrypt_name)->get('homework_submit')->row();
            if (!empty($file_name->file_name)) {
                $this->load->helper('download');
                force_download($file_name->file_name, file_get_contents('uploads/attachments/homework_submit/' . $encrypt_name));
            }
        }
    }
    /**
 * Parent view - All children's homework
 */
public function parent_homework()
{
    if (!is_parent_loggedin()) {
        access_denied();
    }
    
    $children = $this->userrole_model->getMyChildren();
    $all_homework = [];
    
    foreach ($children as $child) {
        $homework = $this->userrole_model->getStudentHomeworkWithStatus($child['student_id']);
        if (!empty($homework)) {
            $all_homework[$child['name']] = $homework;
        }
    }
    
    $this->data['children_homework'] = $all_homework;
    $this->data['title'] = translate('children_homework');
    $this->data['sub_page'] = 'userrole/parent_homework';
    $this->data['main_menu'] = 'homework';
    $this->load->view('layout/index', $this->data);
}

/**
 * Student/Parent - My IARP Recovery Plan
 */
public function my_iarp()
{
    // Check if user is student or parent
    if (!is_student_loggedin() && !is_parent_loggedin()) {
        access_denied();
        return;
    }
    
    // Load STARS model
    $this->load->model('stars_model');
    
    // Get student ID
    $student_id = null;
    
    if (is_student_loggedin()) {
        $student = $this->userrole_model->getStudentDetails();
        $student_id = $student['student_id'];
    } elseif (is_parent_loggedin()) {
        $student_id = get_activeChildren_id();
    }
    
    if (empty($student_id)) {
        set_alert('error', 'Student not found');
        redirect('userrole/dashboard');
        return;
    }
    
    // Get complete IARP data from model
    $iarp_data = $this->stars_model->get_complete_iarp_for_student($student_id);
    
    // If no IARP found, show a message instead of redirecting
    if (empty($iarp_data)) {
        $this->data['has_iarp'] = false;
        $this->data['title'] = 'My Recovery Plan';
        $this->data['sub_page'] = 'stars/my_iarp';
        $this->data['main_menu'] = 'stars';
        $this->load->view('layout/index', $this->data);
        return;
    }
    
    // Get weekly progress with detailed items
    $progress_records = $this->stars_model->get_weekly_progress_with_details($iarp_data['iarp']['id']);
    
    // Get resources and mentors
    $resources = $this->stars_model->get_resources_for_student($student_id);
    $mentors = $this->stars_model->get_mentors_for_student($student_id);
    
    // Pass data to view
    $this->data['has_iarp'] = true;
    $this->data['iarp'] = $iarp_data['iarp'];
    $this->data['all_topics'] = $iarp_data['all_topics'];
    $this->data['total_topics'] = $iarp_data['total_topics'];
    $this->data['completed_topics'] = $iarp_data['completed_topics'];
    $this->data['in_progress_topics'] = $iarp_data['in_progress_topics'];
    $this->data['pending_topics'] = $iarp_data['pending_topics'];
    $this->data['progress_percent'] = $iarp_data['progress_percent'];
    $this->data['progress_records'] = $progress_records;
    $this->data['resources'] = $resources;
    $this->data['mentors'] = $mentors;
    $this->data['title'] = 'My Recovery Plan';
    $this->data['sub_page'] = 'stars/my_iarp';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}
/**
 * Student/Parent - My Learning Resources
 */
public function my_resources()
{
    $student_id = $this->userrole_model->getStudentDetails()['student_id'];
    
    $this->load->model('stars_model');
    $resources = $this->stars_model->get_resources_for_student($student_id);
    
    $this->data['resources'] = $resources;
    $this->data['title'] = 'Learning Resources';
    $this->data['sub_page'] = 'stars/my_resources';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

/**
 * Student/Parent - My Peer Mentor
 */
public function my_mentor()
{
    $student_id = $this->userrole_model->getStudentDetails()['student_id'];
    
    $this->load->model('stars_model');
    $mentors = $this->stars_model->get_mentors_for_student($student_id);
    
    $this->data['mentors'] = $mentors;
    $this->data['title'] = 'My Mentor';
    $this->data['sub_page'] = 'stars/my_mentor';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

/**
 * Student/Parent - My Weekly Progress
 */
public function my_progress()
{
    if (!is_student_loggedin() && !is_parent_loggedin()) {
        access_denied();
        return;
    }
    
    $this->load->model('stars_model');
    
    $student_id = null;
    
    if (is_student_loggedin()) {
        $student = $this->userrole_model->getStudentDetails();
        $student_id = $student['student_id'];
    } elseif (is_parent_loggedin()) {
        $student_id = get_activeChildren_id();
    }
    
    if (empty($student_id)) {
        set_alert('error', 'Student not found');
        redirect('userrole/dashboard');
        return;
    }
    
    $iarp_data = $this->stars_model->get_complete_iarp_for_student($student_id);
    
    if (empty($iarp_data)) {
        $this->data['has_iarp'] = false;
        $this->data['title'] = 'My Weekly Progress';
        $this->data['sub_page'] = 'stars/my_progress';
        $this->data['main_menu'] = 'stars';
        $this->load->view('layout/index', $this->data);
        return;
    }
    
    $progress_records = $this->stars_model->get_weekly_progress_with_details($iarp_data['iarp']['id']);
    
    $this->data['has_iarp'] = true;
    $this->data['iarp'] = $iarp_data['iarp'];
    $this->data['total_topics'] = $iarp_data['total_topics'];
    $this->data['completed_topics'] = $iarp_data['completed_topics'];
    $this->data['progress_percent'] = $iarp_data['progress_percent'];
    $this->data['progress_records'] = $progress_records;
    $this->data['title'] = 'My Weekly Progress';
    $this->data['sub_page'] = 'stars/my_progress';
    $this->data['main_menu'] = 'stars';
    $this->load->view('layout/index', $this->data);
}

 public function download_resource($resource_id)
{
    // Check if user is logged in as student or parent
    if (!is_student_loggedin() && !is_parent_loggedin()) {
        set_alert('error', 'Access denied');
        redirect('userrole/my_resources');
        return;
    }
    
    // Load STARS model
    $this->load->model('stars_model');
    
    // Get resource details
    $resource = $this->stars_model->get_resource_by_id($resource_id);
    
    if (empty($resource)) {
        set_alert('error', 'Resource not found');
        redirect('userrole/my_resources');
        return;
    }
    
    if (empty($resource['enc_file_name'])) {
        set_alert('error', 'File not found');
        redirect('userrole/my_resources');
        return;
    }
    
    // Verify this resource belongs to the logged-in student
    $student_id = null;
    
    if (is_student_loggedin()) {
        $student = $this->userrole_model->getStudentDetails();
        $student_id = $student['student_id'];
    } elseif (is_parent_loggedin()) {
        $student_id = get_activeChildren_id();
    }
    
    if ($resource['student_id'] != $student_id) {
        set_alert('error', 'You do not have permission to download this file');
        redirect('userrole/my_resources');
        return;
    }
    
    // Build file path
    $file_path = FCPATH . 'uploads/stars_resources/' . $resource['enc_file_name'];
    $file_path = str_replace('\\', '/', $file_path);
    
    // Check if file exists
    if (!file_exists($file_path)) {
        set_alert('error', 'File does not exist on server');
        redirect('userrole/my_resources');
        return;
    }
    
    // Get original file name
    $original_name = $resource['file_name'];
    
    // Clear output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Force download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $original_name . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Read file and output
    readfile($file_path);
    exit;
}
/**
 * Student/Parent - Request Academic Support
 */
/**
 * Student/Parent - Request Academic Support
 */
public function request_recovery()
{
    // Check if user is student or parent
    if (!is_student_loggedin() && !is_parent_loggedin()) {
        access_denied();
        return;
    }
    
    // Load STARS model
    $this->load->model('stars_model');
    
    // Get student ID
    $student_id = null;
    
    if (is_student_loggedin()) {
        $student = $this->userrole_model->getStudentDetails();
        $student_id = $student['student_id'];
    } elseif (is_parent_loggedin()) {
        $student_id = get_activeChildren_id();
    }
    
    if (empty($student_id)) {
        set_alert('error', 'Student not found');
        redirect('userrole/dashboard');
        return;
    }
    
    // Check if student already has an active assessment
    if ($this->stars_model->has_active_assessment($student_id)) {
        set_alert('warning', 'You already have an active recovery assessment. Please check your recovery plan.');
        // FIX: Redirect to my_iarp instead of dashboard
        redirect('userrole/my_iarp');
        return;
    }
    
    // Get student details from model
    $student = $this->stars_model->get_student_for_recovery($student_id);
    
    if (empty($student)) {
        set_alert('error', 'Student not found');
        redirect('userrole/dashboard');
        return;
    }
    
    // Check if student has a parent
    if (empty($student['parent_id'])) {
        set_alert('error', 'No parent/guardian associated with this account. Please contact the school.');
        redirect('userrole/dashboard');
        return;
    }
    
    // Get branch ID from model
    $branch_id = $this->stars_model->get_student_branch_id($student_id);
    if (empty($branch_id)) {
        $branch_id = $this->application_model->get_branch_id();
    }
    
    // Create transfer assessment
    $transfer_id = $this->stars_model->create_for_existing_student($student_id, $this->session->userdata('loggedin_userid'));
    
    if ($transfer_id) {
        log_message('info', "Student {$student_id} requested academic support. Assessment ID: {$transfer_id}");
        set_alert('success', 'Your academic support request has been submitted. A teacher will review your assessment shortly.');
        redirect('userrole/my_iarp');
    } else {
        set_alert('error', 'Failed to create assessment. Please contact the school.');
        redirect('userrole/dashboard');
    }
}
}