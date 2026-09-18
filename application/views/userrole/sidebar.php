<aside id="sidebar-left" class="sidebar-left">
	<div class="sidebar-header">
		<div class="sidebar-title">
			Main
		</div>
	</div>

	<div class="nano">
        <div class="nano-content">
            <nav id="menu" class="nav-main" role="navigation">
                <ul class="nav nav-main">
<?php if (is_student_loggedin()) {
    ?>
                    <!-- dashboard -->
                    <li class="<?php if ($sub_page == 'userrole/dashboard') echo 'nav-active'; ?>">
                        <a href="<?=base_url('dashboard')?>">
                            <i class="icons icon-grid"></i><span><?=translate('dashboard')?></span>
                        </a>
                    </li>
<?php } elseif (is_parent_loggedin()) {  ?>

                    <li class="nav-parent <?php if ($main_menu == 'dashboard') echo 'nav-expanded nav-active'; ?>">
                        <a>
                            <i class="icons icon-grid"></i><span><?=translate('dashboard')?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'userrole/dashboard' && empty(get_activeChildren_id())) echo 'nav-active'; ?>">
                                <a href="<?=base_url('parents/my_children')?>">
                                    <i class="fab fa-slideshare"></i><span><?=translate('my_children')?></span>
                                </a>
                            </li>
                            <?php if (!empty(get_activeChildren_id())): ?>
                                <li class="<?php if ($sub_page == 'userrole/dashboard') echo 'nav-active'; ?>">
                                    <a href="<?=base_url('dashboard'); ?>">
                                        <i class="fas fa-tachometer-alt"></i><span><?=translate('dashboard')?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
<?php } if (is_student_loggedin()) { ?>
                    <!-- student profile -->
                    <li class="<?php if ($main_menu == 'profile') echo 'nav-active'; ?>">
                        <a href="<?=base_url('profile')?>">
                            <i class="far fa-user-circle"></i><span><?=translate('profile')?></span>
                        </a>
                    </li>
<?php
}
if ((is_parent_loggedin() && !empty(get_activeChildren_id())) || is_student_loggedin()) {
    ?>
                    <!-- teachers -->
                    <li class="<?php if ($main_menu == 'teachers') echo 'nav-active'; ?>">
                        <a href="<?=base_url('userrole/teacher')?>">
                            <i class="icon-people icons"></i><span><?=translate('teachers')?></span>
                        </a>
                    </li>

                    <!-- academic -->
                    <li class="nav-parent <?php if ($main_menu == 'academic') echo 'nav-expanded nav-active'; ?>">
                        <a>
                            <i class="icons icon-home" aria-hidden="true"></i><span><?=translate('academic')?></span>
                        </a>
                        <ul class="nav nav-children">
                            <!-- subject -->
                            <li class="<?php if ($sub_page == 'userrole/subject') echo 'nav-active'; ?>">
                                <a href="<?=base_url('userrole/subject')?>">
                                    <i class="fas fa-book-reader"></i><?=translate('subject')?>
                                </a>
                            </li>
							
							<!-- class schedule -->
							<li class="<?php if ($sub_page == 'userrole/class_schedule') echo 'nav-active'; ?> ">
								<a href="<?=base_url('userrole/class_schedule')?>">
									<i class="fas fa-dna"></i><span><?=translate('class') . " " . translate('schedule')?></span>
								</a>
							</li>
                        </ul>
                    </li>
<?php if (is_student_loggedin()) { ?>
                    <li class="<?php if ($main_menu == 'live_class') echo 'nav-active';?>">
                        <a href="<?=base_url('userrole/live_class')?>">
                            <i class="icons icon-earphones-alt"></i><span><?=translate('live_class_rooms')?></span>
                        </a>
                    </li>
<?php } ?>
                        <!-- ============================================ -->
                    <!-- STARS - Student Transition & Academic Recovery -->
                    <!-- Only for Students and Parents -->
                    <!-- ============================================ -->
                    <?php if (is_student_loggedin() || (is_parent_loggedin() && !empty(get_activeChildren_id()))): ?>
                    <li class="nav-parent <?php if ($main_menu == 'stars') echo 'nav-expanded nav-active';?>">
                        <a>
                            <i class="fas fa-chalkboard-user" style="color: #10b981;"></i>
                            <span>Academic Recovery (STARS)</span>
                            <?php 
                            // Show badge if there's an active IARP
                            $student_id = is_student_loggedin() ? get_loggedin_user_id() : get_activeChildren_id();
                            $active_iarp = $this->db->select('i.id')
                                ->from('iarp_plans i')
                                ->join('student s', 's.id = i.student_id')
                                ->where('i.student_id', $student_id)
                                ->where('i.status', 'active')
                                ->get()
                                ->row();
                            if ($active_iarp): ?>
                            <span class="badge badge-success pull-right">Active</span>
                            <?php endif; ?>
                        </a>
                        <ul class="nav nav-children">
                            <!-- My IARP Progress -->
                            <li class="<?php if ($sub_page == 'userrole/my_iarp') echo 'nav-active';?>">
                                <a href="<?=base_url('userrole/my_iarp')?>">
                                    <span><i class="fas fa-chart-line"></i> My Recovery Plan</span>
                                </a>
                            </li>
                           <!-- Request Academic Support -->
                            <li class="<?php if ($sub_page == 'userrole/request_recovery') echo 'nav-active';?>">
                                <a href="<?=base_url('userrole/request_recovery')?>">
                                    <span><i class="fas fa-hand-holding-heart"></i> Request Academic Support</span>
                                </a>
                            </li>
                                                        <!-- Resources -->
                            <li class="<?php if ($sub_page == 'userrole/my_resources') echo 'nav-active';?>">
                                <a href="<?=base_url('userrole/my_resources')?>">
                                    <span><i class="fas fa-file-alt"></i> Learning Resources</span>
                                    <?php 
                                    // Show badge for new resources
                                    $new_resources = $this->db->select('COUNT(*) as count')
                                        ->from('resource_recovery')
                                        ->where('student_id', $student_id)
                                        ->where('created_at >', date('Y-m-d H:i:s', strtotime('-7 days')))
                                        ->get()
                                        ->row()
                                        ->count ?? 0;
                                    if ($new_resources > 0): ?>
                                    <span class="badge badge-info pull-right"><?=$new_resources?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            
                            <!-- Peer Mentorship -->
                            <li class="<?php if ($sub_page == 'userrole/my_mentor') echo 'nav-active';?>">
                                <a href="<?=base_url('userrole/my_mentor')?>">
                                    <span><i class="fas fa-users"></i> My Mentor</span>
                                </a>
                            </li>
                            
                            <!-- Weekly Progress Report -->
                            <li class="<?php if ($sub_page == 'userrole/my_progress') echo 'nav-active';?>">
                                <a href="<?=base_url('userrole/my_progress')?>">
                                    <span><i class="fas fa-calendar-week"></i> Weekly Progress</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <!-- leave -->
                    <li class="<?php if ($main_menu == 'leave') echo 'nav-active'; ?>">
                        <a href="<?=base_url('userrole/leave_request')?>">
                            <i class="icons icon-docs"></i><span><?=translate('leave_application')?></span>
                        </a>
                    </li>

                    <!-- attachments upload -->
                    <li class="<?php if ($main_menu == 'attachments') echo 'nav-active'; ?> ">
                        <a href="<?=base_url('userrole/attachments')?>">
                            <i class="icons icon-cloud-upload"></i><span><?=translate('attachments_book')?></span>
                        </a>
                    </li>
                    
                    <!-- homework -->
                    
                    <li class="nav-parent <?php if ($main_menu == 'homework') echo 'nav-expanded nav-active'; ?>">
                        <a>
                            <i class="icons icon-note"></i><span><?=translate('homework')?></span>
                            <?php 
                            // Show pending homework badge for student/parent
                            if (is_student_loggedin()) {
                                $student_id = get_loggedin_user_id();
                                $branch_id = $this->application_model->get_branch_id();
                                
                                // Count pending homework (not submitted or returned)
                                $pending_count = $this->db->select('COUNT(DISTINCT h.id) as count')
                                    ->from('homework h')
                                    ->join('enroll e', 'e.class_id = h.class_id AND e.section_id = h.section_id')
                                    ->join('homework_submit hs', 'hs.homework_id = h.id AND hs.student_id = e.student_id', 'left')
                                    ->where('e.student_id', $student_id)
                                    ->where('e.branch_id', $branch_id)
                                    ->where('e.session_id', get_session_id())
                                    ->where('h.session_id', get_session_id())
                                    ->where('h.date_of_submission >=', date('Y-m-d'))
                                    ->where('(hs.id IS NULL OR hs.submission_status = "returned")')
                                    ->get()
                                    ->row()
                                    ->count ?? 0;
                                
                                if ($pending_count > 0): ?>
                                <span class="badge badge-danger pull-right"><?php echo $pending_count; ?></span>
                                <?php endif;
                            } elseif (is_parent_loggedin() && !empty(get_activeChildren_id())) {
                                $student_id = get_activeChildren_id();
                                $branch_id = $this->application_model->get_branch_id();
                                
                                $pending_count = $this->db->select('COUNT(DISTINCT h.id) as count')
                                    ->from('homework h')
                                    ->join('enroll e', 'e.class_id = h.class_id AND e.section_id = h.section_id')
                                    ->join('homework_submit hs', 'hs.homework_id = h.id AND hs.student_id = e.student_id', 'left')
                                    ->where('e.student_id', $student_id)
                                    ->where('e.branch_id', $branch_id)
                                    ->where('e.session_id', get_session_id())
                                    ->where('h.date_of_submission >=', date('Y-m-d'))
                                    ->where('(hs.id IS NULL OR hs.submission_status = "returned")')
                                    ->get()
                                    ->row()
                                    ->count ?? 0;
                                
                                if ($pending_count > 0): ?>
                                <span class="badge badge-danger pull-right"><?php echo $pending_count; ?></span>
                                <?php endif;
                            } ?>
                        </a>
                        <ul class="nav nav-children">
                            <!-- My Homework (Assignments to complete) -->
                            <li class="<?php if ($sub_page == 'userrole/homework') echo 'nav-active'; ?>">
                                <a href="<?=base_url('userrole/homework')?>">
                                    <span><i class="fas fa-caret-right"></i> <?=translate('my_homework')?></span>
                                    <?php if (isset($pending_count) && $pending_count > 0): ?>
                                    <span class="badge badge-danger pull-right"><?php echo $pending_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            
                            <!-- My Submissions (Submitted & Graded work) -->
                            <li class="<?php if ($sub_page == 'userrole/homework_submissions') echo 'nav-active'; ?>">
                                <a href="<?=base_url('userrole/homework_submissions')?>">
                                    <span><i class="fas fa-caret-right"></i> <i class="fas fa-check-circle"></i> <?=translate('my_submissions')?></span>
                                </a>
                            </li>
                            
                            <!-- Graded Homework (Feedback & Grades) -->
                            <li class="<?php if ($sub_page == 'userrole/homework_graded') echo 'nav-active'; ?>">
                                <a href="<?=base_url('userrole/homework_graded')?>">
                                    <span><i class="fas fa-caret-right"></i> <i class="fas fa-star"></i> <?=translate('graded_homework')?></span>
                                    <?php 
                                    // Count unread feedback
                                    if (is_student_loggedin() || (is_parent_loggedin() && !empty(get_activeChildren_id()))) {
                                        $sid = is_student_loggedin() ? get_loggedin_user_id() : get_activeChildren_id();
                                        $unread_feedback = $this->db->select('COUNT(*) as count')
                                            ->from('homework_submit')
                                            ->where('student_id', $sid)
                                            ->where('feedback IS NOT NULL')
                                            ->where('feedback_date >', date('Y-m-d H:i:s', strtotime('-7 days')))
                                            ->get()
                                            ->row()
                                            ->count ?? 0;
                                        if ($unread_feedback > 0): ?>
                                        <span class="badge badge-info pull-right"><?php echo $unread_feedback; ?></span>
                                        <?php endif;
                                    } ?>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- exam master -->
                    <li class="nav-parent <?php if ($main_menu == 'exam') echo 'nav-expanded nav-active'; ?>">
                            <a>
                            <i class="icons icon-book-open" aria-hidden="true"></i><span><?=translate('exam_master')?></span>
                        </a>
                        <ul class="nav nav-children">
							<!-- exam schedule -->
							<li class="<?php if ($sub_page == 'userrole/exam_schedule') echo 'nav-active'; ?> ">
								<a href="<?=base_url('userrole/exam_schedule')?>">
									<i class="fas fa-dna"></i><span><?=translate('exam') . " " . translate('schedule')?></span>
								</a>
							</li>
					
                            <!-- marks -->
                            <li class="<?php if ($sub_page == 'userrole/report_card') echo 'nav-active'; ?>">
                                <a href="<?=base_url('userrole/report_card')?>">
                                    <i class="fas fa-marker"></i><span><?=translate('report_card')?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
<?php if (is_student_loggedin()) { ?>
                    <!-- online exam master -->
                    <li class="<?php if ($main_menu == 'onlineexam') echo ' nav-active'; ?>">
                        <a href="<?=base_url('userrole/online_exam')?>">
                            <i class="icon-screen-desktop"></i><span><?=translate('online_exam')?></span>
                        </a>
                    </li>
<?php } ?>
                    <!-- supervision -->
                    <li class="nav-parent <?php if ($main_menu == 'supervision')  echo 'nav-expanded nav-active'; ?>">
                        <a>
                            <i class="icons icon-feed" aria-hidden="true"></i><span><?=translate('supervision')?></span>
                        </a>
                        <ul class="nav nav-children">
                            <!-- hostels -->
                            <li class="<?php if ($sub_page == 'userrole/hostels') echo 'nav-active';?>">
                                <a href="<?=base_url('userrole/hostels')?>">
                                    <i class="fas fa-store-alt"></i><span><?=translate('hostel')?></span>
                                </a>
                            </li>

                            <!-- transport -->
                            <li class="<?php if ($sub_page == 'userrole/transport_route') echo 'nav-active'; ?>">
                                <a href="<?=base_url('userrole/route')?>">
                                    <i class="fas fa-bus"></i><span><?=translate('transport')?></span>
                                </a>
                            </li>

                        </ul>
                    </li>

                    <!-- attendance control -->
                    <li class="<?php if ($main_menu == 'attendance') echo ' nav-active'; ?>">
                        <a href="<?=base_url('userrole/attendance')?>">
                            <i class="icons icon-chart"></i><span><?=translate('attendance')?></span>
                        </a>
                    </li>

                    <li class="nav-parent <?php if ($main_menu == 'library') echo 'nav-expanded nav-active';?>">
                        <a>
                            <i class="icons icon-notebook"></i><span><?=translate('library')?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'userrole/book') echo 'nav-active';?>">
                                <a href="<?=base_url('userrole/book')?>">
                                    <span><i class="fas fa-caret-right"></i><?=translate('books') . " " . translate('list')?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'userrole/book_request') echo 'nav-active';?>">
                                <a href="<?=base_url('userrole/book_request')?>">
                                    <span><i class="fas fa-caret-right"></i>Issued Book</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- events -->
                    <li class="<?php if ($sub_page == 'userrole/event') echo 'nav-active'; ?> ">
                        <a href="<?=base_url('userrole/event')?>">
                            <i class="icons icon-speech"></i><span><?=translate('events')?></span>
                        </a>
                    </li>

                   <!-- fees history -->
                    <li class="<?php if ($main_menu == 'fees') echo 'nav-active';?> ">
                        <a href="<?=base_url('userrole/invoice')?>">
                            <i class="icons icon-calculator"></i><span><?=translate('fees_history')?></span>
                        </a>
                    </li>

                    <!-- message -->
                    <li class="<?php if ($main_menu == 'message') echo 'nav-active'; ?> ">
                        <a href="<?=base_url('communication/mailbox/inbox')?>">
                            <i class="icons icon-envelope-open"></i><span><?=translate('message')?></span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
<?php } ?>
		<script>
			// maintain scroll position
			if (typeof localStorage !== 'undefined') {
				if (localStorage.getItem('sidebar-left-position') !== null) {
					var initialPosition = localStorage.getItem('sidebar-left-position'),
						sidebarLeft = document.querySelector('#sidebar-left .nano-content');
					sidebarLeft.scrollTop = initialPosition;
				}
			}
		</script>
	</div>
</aside>
<!-- end sidebar -->