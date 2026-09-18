INFO - 2026-06-17 05:22:33 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 05:22:33 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 05:23:06 --> get_iarp_details - IARP ID: 8
INFO - 2026-06-17 05:23:06 --> Missing topics count: 3
INFO - 2026-06-17 05:23:06 --> get_teachers_by_branch - No branch filter (showing all)
INFO - 2026-06-17 05:23:06 --> get_teachers_by_branch - Found 6 teachers
INFO - 2026-06-17 05:23:06 --> get_subjects_by_branch - No branch filter (showing all)
INFO - 2026-06-17 05:25:26 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 05:25:26 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 05:25:36 --> === stars_dashboard controller called ===
INFO - 2026-06-17 05:25:36 --> === get_dashboard_stats START ===
INFO - 2026-06-17 05:25:36 --> Branch ID: NULL
INFO - 2026-06-17 05:25:36 --> Active recoveries: 1
INFO - 2026-06-17 05:25:36 --> Pending assessments: 2
INFO - 2026-06-17 05:25:36 --> Gap summary: green=0, amber=1, red=4
INFO - 2026-06-17 05:25:36 --> At-risk students count: 3
INFO - 2026-06-17 05:25:36 --> === get_dashboard_stats END ===
INFO - 2026-06-17 05:25:58 --> === stars_dashboard controller called ===
INFO - 2026-06-17 05:25:58 --> === get_dashboard_stats START ===
INFO - 2026-06-17 05:25:58 --> Branch ID: NULL
INFO - 2026-06-17 05:25:58 --> Active recoveries: 1
INFO - 2026-06-17 05:25:58 --> Pending assessments: 2
INFO - 2026-06-17 05:25:58 --> Gap summary: green=0, amber=1, red=4
INFO - 2026-06-17 05:25:58 --> At-risk students count: 3
INFO - 2026-06-17 05:25:58 --> === get_dashboard_stats END ===
INFO - 2026-06-17 05:29:52 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 05:29:52 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 05:32:13 --> STARS: Created recovery assessment for existing student 661 (Transfer ID: 19)
INFO - 2026-06-17 05:32:51 --> === STARS: run_gap_analysis called ===
INFO - 2026-06-17 05:32:51 --> Transfer ID received: 19
INFO - 2026-06-17 05:32:51 --> Branch ID: 13
INFO - 2026-06-17 05:32:51 --> Final Branch ID: 13
INFO - 2026-06-17 05:32:51 --> Topic coverage count: 4
INFO - 2026-06-17 05:32:51 --> STARS Model: run_gap_analysis called
INFO - 2026-06-17 05:32:51 --> Transfer ID: 19, Student ID: 661, Class ID: 4, Branch ID: 13
INFO - 2026-06-17 05:32:51 --> Gap analysis results: 2 subjects processed
INFO - 2026-06-17 05:32:51 --> STARS: Gap analysis completed, redirecting to gap_report/19
INFO - 2026-06-17 05:33:02 --> generate_iarp - Branch ID: 13
INFO - 2026-06-17 05:33:02 --> STARS Model: generate_iarp called for transfer_id: 19
INFO - 2026-06-17 05:33:02 --> STARS Model: IARP created with ID: 11, Plan Code: IARP-2026-00019
INFO - 2026-06-17 05:33:02 --> Stars_sms::send_iarp_created - IARP ID: 11, Branch ID: 13
INFO - 2026-06-17 05:33:02 --> Getting template - Template ID: 22, Branch ID: 13
INFO - 2026-06-17 05:33:02 --> Template found: 1
INFO - 2026-06-17 05:33:03 --> SMS sent to 254707377945, credits deducted: 2
INFO - 2026-06-17 05:33:03 --> get_iarp_details - IARP ID: 11
INFO - 2026-06-17 05:33:03 --> Missing topics count: 3
INFO - 2026-06-17 05:33:03 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:33:03 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 05:33:03 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:34:44 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 05:34:44 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 05:34:59 --> get_iarp_details - IARP ID: 11
INFO - 2026-06-17 05:34:59 --> Missing topics count: 3
INFO - 2026-06-17 05:34:59 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:34:59 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 05:34:59 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:36:39 --> POST max size: 40M
INFO - 2026-06-17 05:36:39 --> Upload max size: 40M
INFO - 2026-06-17 05:36:39 --> File error: 0
INFO - 2026-06-17 05:36:39 --> Final upload path: C:\xampp\htdocs\mbsms\uploads\stars_resources/
INFO - 2026-06-17 05:36:39 --> File uploaded successfully: 8c39ab4d5ee2d79e0b142a6908de47fb.pdf
INFO - 2026-06-17 05:36:42 --> get_iarp_details - IARP ID: 11
INFO - 2026-06-17 05:36:42 --> Missing topics count: 3
INFO - 2026-06-17 05:36:42 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:36:42 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 05:36:42 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:37:26 --> get_subjects_by_class - Class: 4, Branch: 13
INFO - 2026-06-17 05:37:26 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 4
            AND sa.branch_id = 13
            ORDER BY s.name ASC
INFO - 2026-06-17 05:37:26 --> Rows: 2
INFO - 2026-06-17 05:37:26 --> Result: Array
(
    [0] => Array
        (
            [id] => 4
            [name] => Computer studies
            [subject_code] => 451/1
        )

    [1] => Array
        (
            [id] => 5
            [name] => English
            [subject_code] => 231/2
        )

)

INFO - 2026-06-17 05:39:04 --> get_iarp_details - IARP ID: 11
INFO - 2026-06-17 05:39:04 --> Missing topics count: 3
INFO - 2026-06-17 05:39:04 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:39:04 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 05:39:04 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:40:05 --> get_available_mentors AJAX - Class ID: 4, Transfer Student: 661
INFO - 2026-06-17 05:40:05 --> Branch ID from student: 13
INFO - 2026-06-17 05:40:05 --> get_available_mentors - Class ID: 4, Branch ID: 13, Exclude Student: 661
INFO - 2026-06-17 05:40:05 --> Applying branch filter: 13
INFO - 2026-06-17 05:40:05 --> get_available_mentors - Found 2 students
INFO - 2026-06-17 05:40:15 --> get_available_mentors AJAX - Class ID: 4, Transfer Student: 661
INFO - 2026-06-17 05:40:15 --> Branch ID from student: 13
INFO - 2026-06-17 05:40:15 --> get_available_mentors - Class ID: 4, Branch ID: 13, Exclude Student: 661
INFO - 2026-06-17 05:40:15 --> Applying branch filter: 13
INFO - 2026-06-17 05:40:15 --> get_available_mentors - Found 2 students
INFO - 2026-06-17 05:40:21 --> add_mentorship - Data received: Array
(
    [transfer_student_id] => 661
    [mentor_student_id] => 663
    [iarp_id] => 11
    [subject_id] => 
    [assigned_by] => 36
)

INFO - 2026-06-17 05:40:21 --> add_mentorship - Branch ID retrieved from IARP: 13
INFO - 2026-06-17 05:40:21 --> add_mentorship - Insert data: Array
(
    [transfer_student_id] => 661
    [mentor_student_id] => 663
    [iarp_id] => 11
    [subject_id] => 
    [assigned_by] => 36
    [assigned_date] => 2026-06-17
    [meetings_count] => 0
    [next_meeting_date] => 
    [status] => active
    [end_date] => 
    [notes] => 
    [branch_id] => 13
    [created_at] => 2026-06-17 05:40:21
)

INFO - 2026-06-17 05:40:21 --> add_mentorship - Success, ID: 4
INFO - 2026-06-17 05:40:23 --> get_iarp_details - IARP ID: 11
INFO - 2026-06-17 05:40:23 --> Missing topics count: 3
INFO - 2026-06-17 05:40:23 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:40:23 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 05:40:23 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:44:26 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 05:44:26 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 05:44:31 --> === stars_dashboard controller called ===
INFO - 2026-06-17 05:44:31 --> === get_dashboard_stats START ===
INFO - 2026-06-17 05:44:31 --> Branch ID: NULL
INFO - 2026-06-17 05:44:31 --> Active recoveries: 0
INFO - 2026-06-17 05:44:31 --> Pending assessments: 0
INFO - 2026-06-17 05:44:31 --> Gap summary: green=0, amber=0, red=0
INFO - 2026-06-17 05:44:31 --> At-risk students count: 0
INFO - 2026-06-17 05:44:31 --> === get_dashboard_stats END ===
INFO - 2026-06-17 05:44:38 --> Branch filter set to: 13
INFO - 2026-06-17 05:44:38 --> === stars_dashboard controller called ===
INFO - 2026-06-17 05:44:38 --> === get_dashboard_stats START ===
INFO - 2026-06-17 05:44:38 --> Branch ID: 13
INFO - 2026-06-17 05:44:38 --> Active recoveries: 0
INFO - 2026-06-17 05:44:38 --> Pending assessments: 0
INFO - 2026-06-17 05:44:38 --> Gap summary: green=0, amber=0, red=0
INFO - 2026-06-17 05:44:38 --> At-risk students count: 0
INFO - 2026-06-17 05:44:38 --> === get_dashboard_stats END ===
INFO - 2026-06-17 05:44:43 --> Branch filter set to: 1
INFO - 2026-06-17 05:44:44 --> === stars_dashboard controller called ===
INFO - 2026-06-17 05:44:44 --> === get_dashboard_stats START ===
INFO - 2026-06-17 05:44:44 --> Branch ID: 1
INFO - 2026-06-17 05:44:44 --> Active recoveries: 0
INFO - 2026-06-17 05:44:44 --> Pending assessments: 0
INFO - 2026-06-17 05:44:44 --> Gap summary: green=0, amber=0, red=0
INFO - 2026-06-17 05:44:44 --> At-risk students count: 0
INFO - 2026-06-17 05:44:44 --> === get_dashboard_stats END ===
INFO - 2026-06-17 05:45:05 --> get_subjects_by_class - Class: 4, Branch: 1
INFO - 2026-06-17 05:45:05 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 4
            AND sa.branch_id = 1
            ORDER BY s.name ASC
INFO - 2026-06-17 05:45:05 --> Rows: 0
INFO - 2026-06-17 05:45:05 --> Result: Array
(
)

INFO - 2026-06-17 05:45:22 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 05:45:22 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 05:45:48 --> get_topics_ajax - Found 0 topics
INFO - 2026-06-17 05:45:49 --> get_topics_ajax - Found 0 topics
INFO - 2026-06-17 05:46:16 --> get_topics_ajax - Found 1 topics
INFO - 2026-06-17 05:46:16 --> Topic: Introduction to computers - Order: 0 - Weeks: 1.0
INFO - 2026-06-17 05:46:52 --> get_topics_ajax - Found 2 topics
INFO - 2026-06-17 05:46:52 --> Topic: Introduction to computers - Order: 0 - Weeks: 1.0
INFO - 2026-06-17 05:46:52 --> Topic: System Development  - Order: 1 - Weeks: 1.0
INFO - 2026-06-17 05:46:56 --> get_topics_ajax - Found 0 topics
INFO - 2026-06-17 05:47:20 --> get_topics_ajax - Found 1 topics
INFO - 2026-06-17 05:47:20 --> Topic: Speaking - Order: 0 - Weeks: 1.0
INFO - 2026-06-17 05:47:39 --> get_topics_ajax - Found 2 topics
INFO - 2026-06-17 05:47:39 --> Topic: Speaking - Order: 0 - Weeks: 1.0
INFO - 2026-06-17 05:47:39 --> Topic: Reading - Order: 1 - Weeks: 1.0
INFO - 2026-06-17 05:47:49 --> get_subjects_by_class - Class: 4, Branch: 13
INFO - 2026-06-17 05:47:49 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 4
            AND sa.branch_id = 13
            ORDER BY s.name ASC
INFO - 2026-06-17 05:47:49 --> Rows: 2
INFO - 2026-06-17 05:47:49 --> Result: Array
(
    [0] => Array
        (
            [id] => 4
            [name] => Computer studies
            [subject_code] => 451/1
        )

    [1] => Array
        (
            [id] => 5
            [name] => English
            [subject_code] => 231/2
        )

)

INFO - 2026-06-17 05:50:09 --> STARS: Created recovery assessment for existing student 664 (Transfer ID: 20)
INFO - 2026-06-17 05:50:47 --> === STARS: run_gap_analysis called ===
INFO - 2026-06-17 05:50:47 --> Transfer ID received: 20
INFO - 2026-06-17 05:50:47 --> Branch ID: 13
INFO - 2026-06-17 05:50:47 --> Final Branch ID: 13
INFO - 2026-06-17 05:50:47 --> Topic coverage count: 4
INFO - 2026-06-17 05:50:47 --> STARS Model: run_gap_analysis called
INFO - 2026-06-17 05:50:47 --> Transfer ID: 20, Student ID: 664, Class ID: 4, Branch ID: 13
INFO - 2026-06-17 05:50:47 --> Gap analysis results: 2 subjects processed
INFO - 2026-06-17 05:50:47 --> STARS: Gap analysis completed, redirecting to gap_report/20
INFO - 2026-06-17 05:50:54 --> generate_iarp - Branch ID: 13
INFO - 2026-06-17 05:50:54 --> STARS Model: generate_iarp called for transfer_id: 20
INFO - 2026-06-17 05:50:54 --> STARS Model: IARP created with ID: 12, Plan Code: IARP-2026-00020
INFO - 2026-06-17 05:50:54 --> Stars_sms::send_iarp_created - IARP ID: 12, Branch ID: 13
INFO - 2026-06-17 05:50:54 --> Getting template - Template ID: 22, Branch ID: 13
INFO - 2026-06-17 05:50:54 --> Template found: 1
INFO - 2026-06-17 05:50:55 --> SMS sent to 254707377945, credits deducted: 2
INFO - 2026-06-17 05:50:55 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 05:50:55 --> Missing topics count: 2
INFO - 2026-06-17 05:50:55 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:50:55 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 05:50:55 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:51:21 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 05:51:21 --> Missing topics count: 2
INFO - 2026-06-17 05:51:21 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:51:21 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 05:51:21 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:52:26 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 05:52:26 --> Missing topics count: 2
INFO - 2026-06-17 05:52:26 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:52:26 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 05:52:26 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:53:25 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 05:53:25 --> Missing topics count: 2
INFO - 2026-06-17 05:53:25 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:53:25 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 05:53:25 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 05:56:25 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 05:56:25 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 05:56:36 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 05:56:36 --> Missing topics count: 2
INFO - 2026-06-17 05:56:36 --> get_teachers_by_branch - No branch filter (showing all)
INFO - 2026-06-17 05:56:36 --> get_teachers_by_branch - Found 6 teachers
INFO - 2026-06-17 05:56:36 --> get_subjects_by_branch - No branch filter (showing all)
INFO - 2026-06-17 05:59:41 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 05:59:41 --> Missing topics count: 2
INFO - 2026-06-17 05:59:41 --> get_teachers_by_branch - No branch filter (showing all)
INFO - 2026-06-17 05:59:41 --> get_teachers_by_branch - Found 6 teachers
INFO - 2026-06-17 05:59:41 --> get_subjects_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:00:03 --> Topic 17: Total subtopics = 2, Completed = 1
INFO - 2026-06-17 06:00:03 --> auto_close_if_complete - Checking IARP ID: 12
INFO - 2026-06-17 06:00:03 --> Topics - Total: 2, Completed: 0
INFO - 2026-06-17 06:00:06 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 06:00:06 --> Missing topics count: 2
INFO - 2026-06-17 06:00:06 --> get_teachers_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:00:06 --> get_teachers_by_branch - Found 6 teachers
INFO - 2026-06-17 06:00:06 --> get_subjects_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:01:49 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 06:01:49 --> Missing topics count: 2
INFO - 2026-06-17 06:01:49 --> get_teachers_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:01:49 --> get_teachers_by_branch - Found 6 teachers
INFO - 2026-06-17 06:01:49 --> get_subjects_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:03:41 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 06:03:41 --> Missing topics count: 2
INFO - 2026-06-17 06:03:41 --> get_teachers_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:03:41 --> get_teachers_by_branch - Found 6 teachers
INFO - 2026-06-17 06:03:41 --> get_subjects_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:04:02 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 06:04:02 --> Missing topics count: 2
INFO - 2026-06-17 06:04:02 --> get_teachers_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:04:02 --> get_teachers_by_branch - Found 6 teachers
INFO - 2026-06-17 06:04:02 --> get_subjects_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:04:51 --> Topic 17: Total subtopics = 2, Completed = 2
INFO - 2026-06-17 06:04:51 --> Topic 17 marked as completed
INFO - 2026-06-17 06:04:51 --> auto_close_if_complete - Checking IARP ID: 12
INFO - 2026-06-17 06:04:51 --> Topics - Total: 2, Completed: 1
INFO - 2026-06-17 06:04:54 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 06:04:54 --> Missing topics count: 2
INFO - 2026-06-17 06:04:54 --> get_teachers_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:04:54 --> get_teachers_by_branch - Found 6 teachers
INFO - 2026-06-17 06:04:54 --> get_subjects_by_branch - No branch filter (showing all)
INFO - 2026-06-17 06:07:05 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 06:07:05 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 06:07:31 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 06:07:31 --> Missing topics count: 2
INFO - 2026-06-17 06:07:31 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 06:07:31 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 06:07:31 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 06:12:26 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 06:12:26 --> Missing topics count: 2
INFO - 2026-06-17 06:12:26 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 06:12:26 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 06:12:26 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 06:23:36 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 06:23:36 --> Missing topics count: 2
INFO - 2026-06-17 06:23:36 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 06:23:36 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 06:23:36 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 06:25:46 --> Topic 19: Total subtopics = 1, Completed = 1
INFO - 2026-06-17 06:25:46 --> Topic 19 marked as completed
INFO - 2026-06-17 06:25:46 --> auto_close_if_complete - Checking IARP ID: 12
INFO - 2026-06-17 06:25:46 --> Topics - Total: 2, Completed: 2
INFO - 2026-06-17 06:25:46 --> IARP 12 auto-closed successfully
INFO - 2026-06-17 06:25:46 --> Stars_sms::send_completion - IARP ID: 12, Branch: 13
INFO - 2026-06-17 06:25:46 --> Getting template - Template ID: 24, Branch ID: 13
INFO - 2026-06-17 06:25:46 --> Template found: 1
INFO - 2026-06-17 06:25:47 --> SMS sent to 254707377945, credits deducted: 2
INFO - 2026-06-17 06:25:47 --> Completion SMS sent for IARP: 12
INFO - 2026-06-17 06:25:50 --> get_iarp_details - IARP ID: 12
INFO - 2026-06-17 06:25:50 --> Missing topics count: 2
INFO - 2026-06-17 06:25:50 --> get_teachers_by_branch - Filtering by branch: 13
INFO - 2026-06-17 06:25:50 --> get_teachers_by_branch - Found 3 teachers
INFO - 2026-06-17 06:25:50 --> get_subjects_by_branch - Filtering by branch: 13
INFO - 2026-06-17 06:27:10 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 06:27:10 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 06:28:44 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 06:28:44 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 06:28:58 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 06:28:58 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 06:29:38 --> STARS: Created recovery assessment for existing student 664 (Transfer ID: 21)
INFO - 2026-06-17 06:31:49 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 06:31:49 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 06:32:06 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 06:32:06 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 06:51:46 --> get_subjects_by_class - Class: 4, Branch: 13
INFO - 2026-06-17 06:51:46 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 4
            AND sa.branch_id = 13
            ORDER BY s.name ASC
INFO - 2026-06-17 06:51:46 --> Rows: 2
INFO - 2026-06-17 06:51:46 --> Result: Array
(
    [0] => Array
        (
            [id] => 4
            [name] => Computer studies
            [subject_code] => 451/1
        )

    [1] => Array
        (
            [id] => 5
            [name] => English
            [subject_code] => 231/2
        )

)

INFO - 2026-06-17 06:52:31 --> get_subjects_by_class - Class: 4, Branch: 13
INFO - 2026-06-17 06:52:31 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 4
            AND sa.branch_id = 13
            ORDER BY s.name ASC
INFO - 2026-06-17 06:52:31 --> Rows: 2
INFO - 2026-06-17 06:52:31 --> Result: Array
(
    [0] => Array
        (
            [id] => 4
            [name] => Computer studies
            [subject_code] => 451/1
        )

    [1] => Array
        (
            [id] => 5
            [name] => English
            [subject_code] => 231/2
        )

)

INFO - 2026-06-17 06:52:33 --> get_subjects_by_class - Class: 5, Branch: 13
INFO - 2026-06-17 06:52:33 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 5
            AND sa.branch_id = 13
            ORDER BY s.name ASC
INFO - 2026-06-17 06:52:33 --> Rows: 0
INFO - 2026-06-17 06:52:33 --> Result: Array
(
)

INFO - 2026-06-17 06:52:56 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 06:52:57 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 06:53:33 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 06:53:33 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:01:41 --> get_subjects_by_class - Class: 4, Branch: 13
INFO - 2026-06-17 07:01:41 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 4
            AND sa.branch_id = 13
            ORDER BY s.name ASC
INFO - 2026-06-17 07:01:41 --> Rows: 2
INFO - 2026-06-17 07:01:41 --> Result: Array
(
    [0] => Array
        (
            [id] => 4
            [name] => Computer studies
            [subject_code] => 451/1
        )

    [1] => Array
        (
            [id] => 5
            [name] => English
            [subject_code] => 231/2
        )

)

INFO - 2026-06-17 07:02:40 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:02:40 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:03:26 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:03:26 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:07:08 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:07:08 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:09:06 --> Admin manage_topics - Branch ID: 13
INFO - 2026-06-17 07:09:29 --> get_topics_ajax - Found 2 topics
INFO - 2026-06-17 07:09:29 --> Topic: Introduction to computers - Order: 0 - Weeks: 1.0
INFO - 2026-06-17 07:09:29 --> Topic: System Development  - Order: 1 - Weeks: 1.0
INFO - 2026-06-17 07:09:31 --> get_topics_ajax - Found 2 topics
INFO - 2026-06-17 07:09:31 --> Topic: Speaking - Order: 0 - Weeks: 1.0
INFO - 2026-06-17 07:09:31 --> Topic: Reading - Order: 1 - Weeks: 1.0
INFO - 2026-06-17 07:09:35 --> get_topics_ajax - Found 0 topics
INFO - 2026-06-17 07:09:37 --> get_topics_ajax - Found 0 topics
INFO - 2026-06-17 07:09:39 --> get_topics_ajax - Found 0 topics
INFO - 2026-06-17 07:09:42 --> get_topics_ajax - Found 0 topics
INFO - 2026-06-17 07:10:25 --> get_topics_ajax - Found 2 topics
INFO - 2026-06-17 07:10:25 --> Topic: Speaking - Order: 0 - Weeks: 1.0
INFO - 2026-06-17 07:10:25 --> Topic: Reading - Order: 1 - Weeks: 1.0
INFO - 2026-06-17 07:10:47 --> get_topics_ajax - Found 3 topics
INFO - 2026-06-17 07:10:47 --> Topic: Speaking - Order: 0 - Weeks: 1.0
INFO - 2026-06-17 07:10:47 --> Topic: Reading - Order: 1 - Weeks: 1.0
INFO - 2026-06-17 07:10:47 --> Topic: Writing - Order: 2 - Weeks: 1.0
INFO - 2026-06-17 07:11:01 --> get_subjects_by_class - Class: 4, Branch: 13
INFO - 2026-06-17 07:11:01 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 4
            AND sa.branch_id = 13
            ORDER BY s.name ASC
INFO - 2026-06-17 07:11:01 --> Rows: 2
INFO - 2026-06-17 07:11:01 --> Result: Array
(
    [0] => Array
        (
            [id] => 4
            [name] => Computer studies
            [subject_code] => 451/1
        )

    [1] => Array
        (
            [id] => 5
            [name] => English
            [subject_code] => 231/2
        )

)

INFO - 2026-06-17 07:12:50 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:12:50 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:12:57 --> Admin manage_topics - Branch ID: 13
INFO - 2026-06-17 07:13:30 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:13:30 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:13:37 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:13:37 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:16:04 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:16:04 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:20:21 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:20:21 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:20:27 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:20:27 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:20:37 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:20:37 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:21:45 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:21:45 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:21:50 --> Admin manage_topics - Branch ID: 13
INFO - 2026-06-17 07:21:58 --> get_topics_ajax - Found 2 topics
INFO - 2026-06-17 07:21:58 --> Topic: Introduction to computers - Order: 0 - Weeks: 1.0
INFO - 2026-06-17 07:21:58 --> Topic: System Development  - Order: 1 - Weeks: 1.0
INFO - 2026-06-17 07:22:02 --> get_topics_ajax - Found 0 topics
INFO - 2026-06-17 07:22:09 --> get_subjects_by_class - Class: 4, Branch: 13
INFO - 2026-06-17 07:22:09 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 4
            AND sa.branch_id = 13
            ORDER BY s.name ASC
INFO - 2026-06-17 07:22:09 --> Rows: 2
INFO - 2026-06-17 07:22:09 --> Result: Array
(
    [0] => Array
        (
            [id] => 4
            [name] => Computer studies
            [subject_code] => 451/1
        )

    [1] => Array
        (
            [id] => 5
            [name] => English
            [subject_code] => 231/2
        )

)

INFO - 2026-06-17 07:22:11 --> get_subjects_by_class - Class: 6, Branch: 13
INFO - 2026-06-17 07:22:11 --> SQL: SELECT DISTINCT s.id, s.name, s.subject_code
            FROM subject_assign sa
            INNER JOIN subject s ON s.id = sa.subject_id
            WHERE sa.class_id = 6
            AND sa.branch_id = 13
            ORDER BY s.name ASC
INFO - 2026-06-17 07:22:11 --> Rows: 0
INFO - 2026-06-17 07:22:11 --> Result: Array
(
)

INFO - 2026-06-17 07:22:25 --> get_report_stats - Branch ID: 13
INFO - 2026-06-17 07:22:25 --> get_severity_stats - Branch ID: 13
INFO - 2026-06-17 07:22:25 --> get_monthly_closures - Branch ID: 13
INFO - 2026-06-17 07:22:25 --> get_recent_closures - Branch ID: 13
INFO - 2026-06-17 07:22:52 --> === stars_dashboard controller called ===
INFO - 2026-06-17 07:22:52 --> === get_dashboard_stats START ===
INFO - 2026-06-17 07:22:52 --> Branch ID: 13
INFO - 2026-06-17 07:22:52 --> Active recoveries: 0
INFO - 2026-06-17 07:22:52 --> Pending assessments: 1
INFO - 2026-06-17 07:22:52 --> Gap summary: green=0, amber=2, red=0
INFO - 2026-06-17 07:22:52 --> At-risk students count: 0
INFO - 2026-06-17 07:22:52 --> === get_dashboard_stats END ===
INFO - 2026-06-17 07:23:05 --> === stars_dashboard controller called ===
INFO - 2026-06-17 07:23:05 --> === get_dashboard_stats START ===
INFO - 2026-06-17 07:23:05 --> Branch ID: 13
INFO - 2026-06-17 07:23:05 --> Active recoveries: 0
INFO - 2026-06-17 07:23:05 --> Pending assessments: 1
INFO - 2026-06-17 07:23:05 --> Gap summary: green=0, amber=2, red=0
INFO - 2026-06-17 07:23:05 --> At-risk students count: 0
INFO - 2026-06-17 07:23:05 --> === get_dashboard_stats END ===
INFO - 2026-06-17 07:24:07 --> get_report_stats - Branch ID: 13
INFO - 2026-06-17 07:24:07 --> get_severity_stats - Branch ID: 13
INFO - 2026-06-17 07:24:07 --> get_monthly_closures - Branch ID: 13
INFO - 2026-06-17 07:24:07 --> get_recent_closures - Branch ID: 13
INFO - 2026-06-17 07:25:43 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:25:43 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:34:57 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 07:34:57 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 07:35:01 --> === stars_dashboard controller called ===
INFO - 2026-06-17 07:35:01 --> === get_dashboard_stats START ===
INFO - 2026-06-17 07:35:01 --> Branch ID: 1
INFO - 2026-06-17 07:35:01 --> Active recoveries: 0
INFO - 2026-06-17 07:35:01 --> Pending assessments: 0
INFO - 2026-06-17 07:35:01 --> Gap summary: green=0, amber=0, red=0
INFO - 2026-06-17 07:35:01 --> At-risk students count: 0
INFO - 2026-06-17 07:35:01 --> === get_dashboard_stats END ===
INFO - 2026-06-17 07:35:11 --> Admin manage_topics - Branch ID: 1
INFO - 2026-06-17 07:35:22 --> Admin manage_topics - Branch ID: 1
INFO - 2026-06-17 07:35:29 --> get_topics_ajax - Found 0 topics
INFO - 2026-06-17 07:35:31 --> get_report_stats - Branch ID: 1
INFO - 2026-06-17 07:35:31 --> get_severity_stats - Branch ID: 1
INFO - 2026-06-17 07:35:31 --> get_monthly_closures - Branch ID: 1
INFO - 2026-06-17 07:35:31 --> get_recent_closures - Branch ID: 1
INFO - 2026-06-17 08:12:58 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 08:12:58 --> === DASHBOARD INDEX VIEW END ===
INFO - 2026-06-17 11:00:30 --> === DASHBOARD INDEX VIEW START ===
INFO - 2026-06-17 11:00:30 --> === DASHBOARD INDEX VIEW END ===
