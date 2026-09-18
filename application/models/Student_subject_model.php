<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Student_subject_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Enroll a student in a subject
     */
    public function enroll_student($student_id, $subject_id, $class_id, $section_id, $branch_id)
    {
        // Check if already enrolled
        $exists = $this->db->where('student_id', $student_id)
                           ->where('subject_id', $subject_id)
                           ->where('session_id', get_session_id())
                           ->get('student_subject')
                           ->num_rows();

        if ($exists > 0) {
            // Update status to active if it was inactive
            $this->db->where('student_id', $student_id)
                     ->where('subject_id', $subject_id)
                     ->where('session_id', get_session_id())
                     ->update('student_subject', ['status' => 'active']);
            return true;
        }

        $data = array(
            'student_id' => $student_id,
            'subject_id' => $subject_id,
            'class_id' => $class_id,
            'section_id' => $section_id,
            'session_id' => get_session_id(),
            'branch_id' => $branch_id,
            'status' => 'active'
        );

        return $this->db->insert('student_subject', $data);
    }

    /**
     * Remove student from a subject (soft delete - set status to dropped)
     */
    public function remove_student_subject($student_id, $subject_id)
    {
        $this->db->where('student_id', $student_id)
                 ->where('subject_id', $subject_id)
                 ->where('session_id', get_session_id())
                 ->update('student_subject', ['status' => 'dropped']);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Hard delete student subject enrollment
     */
    public function delete_student_subject($student_id, $subject_id)
    {
        $this->db->where('student_id', $student_id)
                 ->where('subject_id', $subject_id)
                 ->where('session_id', get_session_id())
                 ->delete('student_subject');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Get all subjects a student is enrolled in
     */
    public function get_student_subjects($student_id, $branch_id = null)
    {
        $this->db->select('ss.*, sub.name as subject_name, sub.subject_code, sub.subject_type')
                 ->from('student_subject ss')
                 ->join('subject sub', 'sub.id = ss.subject_id')
                 ->where('ss.student_id', $student_id)
                 ->where('ss.session_id', get_session_id())
                 ->where('ss.status', 'active');

        if ($branch_id) {
            $this->db->where('ss.branch_id', $branch_id);
        }

        $this->db->order_by('sub.name', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Get enrolled subject IDs for a student (for quick lookups)
     */
    public function get_enrolled_subject_ids($student_id, $branch_id = null)
    {
        $this->db->select('subject_id')
                 ->from('student_subject')
                 ->where('student_id', $student_id)
                 ->where('session_id', get_session_id())
                 ->where('status', 'active');

        if ($branch_id) {
            $this->db->where('branch_id', $branch_id);
        }

        $result = $this->db->get()->result_array();
        return array_column($result, 'subject_id');
    }

    /**
     * Get all students enrolled in a specific subject (class/section)
     */
    public function get_subject_students($subject_id, $class_id, $section_id, $branch_id)
    {
        $this->db->select('s.id, s.first_name, s.last_name, s.register_no, s.mobileno, ss.status')
                 ->from('student s')
                 ->join('student_subject ss', 'ss.student_id = s.id')
                 ->join('enroll e', 'e.student_id = s.id')
                 ->where('ss.subject_id', $subject_id)
                 ->where('ss.class_id', $class_id)
                 ->where('ss.section_id', $section_id)
                 ->where('ss.session_id', get_session_id())
                 ->where('ss.branch_id', $branch_id)
                 ->where('ss.status', 'active')
                 ->where('e.class_id', $class_id)
                 ->where('e.section_id', $section_id)
                 ->where('e.session_id', get_session_id())
                 ->group_by('s.id')
                 ->order_by('s.first_name', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Get students NOT enrolled in a subject (for enrollment UI)
     */
    public function get_unenrolled_students($subject_id, $class_id, $section_id, $branch_id)
    {
        $sql = "SELECT s.id, s.first_name, s.last_name, s.register_no, s.mobileno
                FROM student s
                INNER JOIN enroll e ON e.student_id = s.id 
                    AND e.class_id = ? 
                    AND e.section_id = ?
                    AND e.session_id = ?
                    AND e.branch_id = ?
                WHERE s.id NOT IN (
                    SELECT student_id FROM student_subject 
                    WHERE subject_id = ? 
                    AND session_id = ? 
                    AND branch_id = ?
                    AND status = 'active'
                )
                ORDER BY s.first_name ASC";

        return $this->db->query($sql, [
            $class_id, $section_id, get_session_id(), $branch_id,
            $subject_id, get_session_id(), $branch_id
        ])->result_array();
    }

    /**
     * Bulk enroll students in a subject
     */
    public function bulk_enroll($student_ids, $subject_id, $class_id, $section_id, $branch_id)
    {
        $success = 0;
        foreach ($student_ids as $student_id) {
            if ($this->enroll_student($student_id, $subject_id, $class_id, $section_id, $branch_id)) {
                $success++;
            }
        }
        return $success;
    }

    /**
     * Bulk remove students from a subject
     */
    public function bulk_remove($student_ids, $subject_id, $branch_id)
    {
        $success = 0;
        foreach ($student_ids as $student_id) {
            if ($this->remove_student_subject($student_id, $subject_id)) {
                $success++;
            }
        }
        return $success;
    }

    /**
     * Get enrollment statistics for a subject
     */
    public function get_enrollment_stats($subject_id, $class_id, $section_id, $branch_id)
    {
        $total_students = $this->db->select('COUNT(*) as total')
                                  ->from('enroll')
                                  ->where('class_id', $class_id)
                                  ->where('section_id', $section_id)
                                  ->where('session_id', get_session_id())
                                  ->where('branch_id', $branch_id)
                                  ->get()
                                  ->row()
                                  ->total ?? 0;

        $enrolled_students = $this->db->select('COUNT(*) as enrolled')
                                    ->from('student_subject')
                                    ->where('subject_id', $subject_id)
                                    ->where('class_id', $class_id)
                                    ->where('section_id', $section_id)
                                    ->where('session_id', get_session_id())
                                    ->where('branch_id', $branch_id)
                                    ->where('status', 'active')
                                    ->get()
                                    ->row()
                                    ->enrolled ?? 0;

        return [
            'total_students' => $total_students,
            'enrolled_students' => $enrolled_students,
            'unenrolled_students' => $total_students - $enrolled_students,
            'enrollment_percentage' => $total_students > 0 ? round(($enrolled_students / $total_students) * 100, 1) : 0
        ];
    }

    /**
     * Get available subjects for a student (subjects assigned to their class/section)
     */
    public function get_available_subjects_for_student($student_id, $class_id, $section_id, $branch_id)
    {
        $sql = "SELECT s.id, s.name, s.subject_code, s.subject_type,
                CASE WHEN ss.id IS NOT NULL THEN 'enrolled' ELSE 'not_enrolled' END as enrollment_status,
                ss.status as enrollment_status_detail
                FROM subject_assign sa
                INNER JOIN subject s ON s.id = sa.subject_id
                LEFT JOIN student_subject ss ON ss.subject_id = s.id 
                    AND ss.student_id = ? 
                    AND ss.session_id = ?
                    AND ss.branch_id = ?
                WHERE sa.class_id = ? 
                    AND sa.section_id = ? 
                    AND sa.session_id = ?
                    AND sa.branch_id = ?
                ORDER BY s.name ASC";

        return $this->db->query($sql, [
            $student_id, get_session_id(), $branch_id,
            $class_id, $section_id, get_session_id(), $branch_id
        ])->result_array();
    }

    /**
     * Get all subjects with enrollment counts for a class/section
     */
    public function get_subject_enrollment_summary($class_id, $section_id, $branch_id)
    {
        $sql = "SELECT s.id, s.name, s.subject_code,
                COUNT(DISTINCT ss.student_id) as enrolled_count,
                (SELECT COUNT(*) FROM enroll WHERE class_id = ? AND section_id = ? AND session_id = ? AND branch_id = ?) as total_students
                FROM subject_assign sa
                INNER JOIN subject s ON s.id = sa.subject_id
                LEFT JOIN student_subject ss ON ss.subject_id = s.id 
                    AND ss.class_id = sa.class_id
                    AND ss.section_id = sa.section_id
                    AND ss.session_id = sa.session_id
                    AND ss.branch_id = sa.branch_id
                    AND ss.status = 'active'
                WHERE sa.class_id = ? 
                    AND sa.section_id = ? 
                    AND sa.session_id = ?
                    AND sa.branch_id = ?
                GROUP BY s.id
                ORDER BY s.name ASC";

        return $this->db->query($sql, [
            $class_id, $section_id, get_session_id(), $branch_id,
            $class_id, $section_id, get_session_id(), $branch_id
        ])->result_array();
    }

    /**
     * Auto-enroll all students in a section for a specific subject
     */
    public function auto_enroll_all($subject_id, $class_id, $section_id, $branch_id)
    {
        // Get all students in this class/section
        $students = $this->db->select('student_id')
                             ->from('enroll')
                             ->where('class_id', $class_id)
                             ->where('section_id', $section_id)
                             ->where('session_id', get_session_id())
                             ->where('branch_id', $branch_id)
                             ->get()
                             ->result_array();

        $success = 0;
        foreach ($students as $student) {
            if ($this->enroll_student($student['student_id'], $subject_id, $class_id, $section_id, $branch_id)) {
                $success++;
            }
        }
        return $success;
    }

    /**
     * Check if a student is enrolled in a specific subject
     */
    public function is_student_enrolled($student_id, $subject_id, $branch_id = null)
    {
        $this->db->where('student_id', $student_id)
                 ->where('subject_id', $subject_id)
                 ->where('session_id', get_session_id())
                 ->where('status', 'active');

        if ($branch_id) {
            $this->db->where('branch_id', $branch_id);
        }

        return $this->db->get('student_subject')->num_rows() > 0;
    }
}