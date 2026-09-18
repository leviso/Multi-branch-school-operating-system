<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Subject_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }
    
    // get subjects assign list
    public function getAssignList()
    {
        $this->db->select('sa.class_id,sa.section_id,sa.branch_id,b.name as branch_name,c.name as class_name,s.name as section_name');
        $this->db->from('subject_assign as sa');
        $this->db->join('branch as b', 'b.id = sa.branch_id', 'left');
        $this->db->join('class as c', 'c.id = sa.class_id', 'left');
        $this->db->join('section as s', 's.id = sa.section_id', 'left');
        $this->db->group_by(array('sa.class_id', 'sa.section_id', 'sa.branch_id'));
        $this->db->where('sa.session_id', get_session_id());
        if (!is_superadmin_loggedin()) {
            $this->db->where('sa.branch_id', get_loggedin_branch_id());
        }
        $result = $this->db->get()->result_array();
        return $result;
    }

    // get subject list by class id and section id
    public function get_subject_list($class_id = '', $section_id = '')
    {
        $this->db->select('sa.subject_id,s.name');
        $this->db->from('subject_assign as sa');
        $this->db->join('subject as s', 's.id = sa.subject_id', 'left');
        $this->db->where('sa.class_id', $class_id);
        $this->db->where('sa.section_id', $section_id);
        $this->db->where('sa.session_id', get_session_id());
        $subjects = $this->db->get()->result();
        $name_list = '';
        foreach ($subjects as $row) {
            $name_list .= '- ' . $row->name . '<br>';
        }
        return $name_list;
    }

    // get teacher assign list
    public function getTeacherAssignList()
    {
        $sql = "SELECT sa.*, c.name as class_name, s.name as section_name, sb.name as subject_name, t.name as teacher_name, t.department, sd.name as department_name FROM subject_assign as sa LEFT JOIN class as c ON c.id = sa.class_id LEFT JOIN section as s ON s.id = sa.section_id LEFT JOIN subject as sb ON sb.id = sa.subject_id LEFT JOIN staff as t ON t.id = sa.teacher_id LEFT JOIN staff_department as sd ON sd.id = t.department WHERE sa.teacher_id != 0";
        if (!is_superadmin_loggedin()) {
            $sql .= " AND sa.branch_id = " . $this->db->escape(get_loggedin_branch_id());
        }
        $sql .= " ORDER BY sa.id ASC";
        $result = $this->db->query($sql)->result();
        return $result;
    }

    public function getSubjectByClassSection($classID = '', $sectionID = '')
    {
        if (loggedin_role_id() == 3) {
            $restricted = $this->getSingle('branch', get_loggedin_branch_id(), true)->teacher_restricted;
            if ($restricted == 1) {
                $getClassTeacher = $this->getClassTeacherByClassSection($classID, $sectionID);
                if ($getClassTeacher == true) {
                    $query = $this->getSubjectList($classID, $sectionID);
                } else {
                    $this->db->select('timetable_class.subject_id,subject.name as subjectname');
                    $this->db->from('timetable_class');
                    $this->db->join('section', 'section.id = timetable_class.section_id', 'left');
                    $this->db->join('subject', 'subject.id = timetable_class.subject_id', 'left');
                    $this->db->where(array('timetable_class.teacher_id' => get_loggedin_user_id(), 'timetable_class.session_id' => get_session_id(), 'timetable_class.class_id' => $classID, 'timetable_class.section_id' => $sectionID));
                    $this->db->group_by('timetable_class.subject_id');
                    $query = $this->db->get();
                }
            } else {
                $query = $this->getSubjectList($classID, $sectionID);
            }
        } else {
            $query = $this->getSubjectList($classID, $sectionID);
        }
        return $query;
    }


    public function getSubjectList($classID = '', $sectionID = '')
    {
        $this->db->select('subject_assign.subject_id, subject.name as subjectname');
        $this->db->from('subject_assign');
        $this->db->join('subject', 'subject.id = subject_assign.subject_id', 'left');
        $this->db->where('class_id', $classID);
        $this->db->where('section_id', $sectionID);
        $this->db->where('session_id', get_session_id());
        $query = $this->db->get();
        return $query;
    }

    public function getClassTeacherByClassSection($classID = '', $sectionID = '')
    {
        $this->db->select('teacher_allocation.id');
        $this->db->from('teacher_allocation');
        $this->db->where('teacher_allocation.teacher_id', get_loggedin_user_id());
        $this->db->where('teacher_allocation.session_id', get_session_id());
        $this->db->where('teacher_allocation.class_id', $classID);
        $this->db->where('teacher_allocation.section_id', $sectionID);
        $q = $this->db->get()->num_rows();
        if ($q > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
 * Get existing subject assignments for a class-section
 * @param int $class_id
 * @param int $section_id
 * @param int $session_id
 * @param int $branch_id
 * @return array
 */
public function get_existing_assignments($class_id, $section_id, $session_id, $branch_id)
{
    $this->db->select('subject_id')
             ->from('subject_assign')
             ->where('class_id', $class_id)
             ->where('section_id', $section_id)
             ->where('session_id', $session_id)
             ->where('branch_id', $branch_id);
    
    $result = $this->db->get()->result_array();
    return array_column($result, 'subject_id');
}

/**
 * Add subject assignments for a class-section
 * @param array $subjects_to_add
 * @param int $class_id
 * @param int $section_id
 * @param int $session_id
 * @param int $branch_id
 * @param int $teacher_id
 * @return int Number of assignments added
 */
public function add_subject_assignments($subjects_to_add, $class_id, $section_id, $session_id, $branch_id, $teacher_id = 0)
{
    if (empty($subjects_to_add)) {
        return 0;
    }
    
    $inserted = 0;
    foreach ($subjects_to_add as $subject_id) {
        // Cast to integer
        $subject_id = (int)$subject_id;
        
        $exists = $this->db->where('class_id', (int)$class_id)
            ->where('section_id', (int)$section_id)
            ->where('subject_id', $subject_id)
            ->where('session_id', (int)$session_id)
            ->where('branch_id', (int)$branch_id)
            ->count_all_results('subject_assign');
        
        if ($exists == 0) {
            $data = array(
                'class_id' => (int)$class_id,
                'section_id' => (int)$section_id,
                'subject_id' => $subject_id,
                'session_id' => (int)$session_id,
                'branch_id' => (int)$branch_id,
                'teacher_id' => (int)$teacher_id,
                'created_at' => date('Y-m-d H:i:s')
            );
            $this->db->insert('subject_assign', $data);
            $inserted++;
        }
    }
    
    return $inserted;
}

/**
 * Remove subject assignments from a class-section
 * @param array $subjects_to_remove
 * @param int $class_id
 * @param int $section_id
 * @param int $session_id
 * @param int $branch_id
 * @return int Number of assignments removed
 */
public function remove_subject_assignments($subjects_to_remove, $class_id, $section_id, $session_id, $branch_id)
{
    if (empty($subjects_to_remove)) {
        return 0;
    }
    
    $this->db->where('class_id', $class_id)
        ->where('section_id', $section_id)
        ->where('session_id', $session_id)
        ->where('branch_id', $branch_id)
        ->where_in('subject_id', $subjects_to_remove)
        ->delete('subject_assign');
    
    return $this->db->affected_rows();
}

/**
 * Delete all subject assignments for a class-section
 * @param int $class_id
 * @param int $section_id
 * @param int $session_id
 * @param int $branch_id
 */
public function delete_all_assignments($class_id, $section_id, $session_id, $branch_id)
{
    $this->db->where('class_id', $class_id)
        ->where('section_id', $section_id)
        ->where('session_id', $session_id)
        ->where('branch_id', $branch_id)
        ->delete('subject_assign');
}

/**
 * Get class teacher for a class-section
 * @param int $class_id
 * @param int $section_id
 * @param int $session_id
 * @param int $branch_id
 * @return int Teacher ID (0 if none)
 */
public function get_class_teacher_id($class_id, $section_id, $session_id, $branch_id)
{
    $teacher = $this->db->select('teacher_id')
        ->from('teacher_allocation')
        ->where('class_id', $class_id)
        ->where('section_id', $section_id)
        ->where('session_id', $session_id)
        ->where('branch_id', $branch_id)
        ->get()
        ->row_array();
    
    return $teacher ? $teacher['teacher_id'] : 0;
}

/**
 * Clean duplicate assignments (keep only one per combination)
 * @param int $branch_id
 * @return int Number of duplicates removed
 */
public function clean_duplicate_assignments($branch_id = null)
{
    $this->db->select('MIN(id) as keep_id, class_id, section_id, subject_id, session_id, branch_id')
        ->from('subject_assign')
        ->group_by('class_id, section_id, subject_id, session_id, branch_id')
        ->having('COUNT(*) > 1');
    
    $duplicates = $this->db->get()->result_array();
    $removed = 0;
    
    foreach ($duplicates as $dup) {
        $this->db->where('class_id', $dup['class_id'])
            ->where('section_id', $dup['section_id'])
            ->where('subject_id', $dup['subject_id'])
            ->where('session_id', $dup['session_id'])
            ->where('branch_id', $dup['branch_id'])
            ->where('id !=', $dup['keep_id'])
            ->delete('subject_assign');
        
        $removed += $this->db->affected_rows();
    }
    
    return $removed;
}

}
