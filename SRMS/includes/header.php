\u003c!DOCTYPE html\u003e
\u003chtml lang="en"\u003e
\u003chead\u003e
    \u003cmeta charset="UTF-8"\u003e
    \u003cmeta name="viewport" content="width=device-width, initial-scale=1.0"\u003e
    \u003ctitle\u003eSRMS\u003c/title\u003e
    \u003clink rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"\u003e
    \u003clink rel="stylesheet" href="assets/style.css"\u003e
\u003c/head\u003e
\u003cbody\u003e
    \u003c?php 
        $current_page = basename($_SERVER['PHP_SELF']);
        $show_sidebar = true;
        if (isset($_SESSION['role']) \u0026\u0026 $_SESSION['role'] === 'teacher' \u0026\u0026 $current_page === 'teacher_dashboard.php') {
            $show_sidebar = false; // Avoid duplicate sidebar on teacher dashboard, which has its own
        }
    ?\u003e
    \u003cbody class="\u003c?php echo str_replace('.php', '', $current_page); ?\u003e-page"\u003e
    \u003c?php if($show_sidebar): ?\u003e
        \u003caside class="app-sidebar"\u003e
            \u003cdiv class="sidebar-brand"\u003e\u003ca href="index.php"\u003eSRMS\u003c/a\u003e\u003c/div\u003e
            \u003cul class="sidebar-nav"\u003e
                \u003c?php if(isset($_SESSION['loggedin']) \u0026\u0026 $_SESSION['loggedin'] === true): ?\u003e
                    \u003c?php if(isset($_SESSION['role']) \u0026\u0026 $_SESSION['role'] === 'admin'): ?\u003e
                        \u003cli\u003e\u003ca href="index.php"\u003eDashboard\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_students.php"\u003eStudents\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="student_import.php"\u003eBulk Import Students\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_teachers.php"\u003eTeachers\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="teacher_assignments_admin.php"\u003eTeacher Assignments\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="teacher_leave_quotas.php"\u003eTeacher Leave Quotas\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_parents.php"\u003eParents\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="parent_link_bulk.php"\u003eLink Students to Parent\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_classes.php"\u003eClasses\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_staff.php"\u003eStaff\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_timetable.php"\u003eTimetable\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_academic_calendar.php"\u003eAcademic Calendar\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_grading.php"\u003eGrading Systems\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_subjects.php"\u003eSubjects\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_results.php"\u003eResults\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_result_approvals.php"\u003eApprovals\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="report_cards.php"\u003eReport Cards\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="analytics.php"\u003eAnalytics\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="data_export.php"\u003eData Export\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_announcements.php"\u003eAnnouncements\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="system_settings.php"\u003eSystem Settings\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_attendance.php"\u003eAttendance\u003c/a\u003e\u003c/li\u003e
                    \u003c?php elseif(isset($_SESSION['role']) \u0026\u0026 $_SESSION['role'] === 'teacher'): ?\u003e
                        \u003cli\u003e\u003ca href="index.php"\u003eDashboard\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="manage_results.php"\u003eMy Results\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="result_add.php"\u003eAdd Result\u003c/a\u003e\u003c/li\u003e
                        \u003cli\u003e\u003ca href="attendance_mark.php"\u003eMark Attendance\u003c/a\u003e\u003c/li\u003e
                    \u003c?php elseif(isset($_SESSION['role']) \u0026\u0026 $_SESSION['role'] === 'student'): ?\u003e
                        \u003cli\u003e\u003ca href="index.php"\u003eDashboard\u003c/a\u003e\u003c/li\u003e
                    \u003c?php elseif(isset($_SESSION['role']) \u0026\u0026 $_SESSION['role'] === 'parent'): ?\u003e
                        \u003cli\u003e\u003ca href="index.php"\u003eDashboard\u003c/a\u003e\u003c/li\u003e
                    \u003c?php endif; ?\u003e
                    \u003cli\u003e\u003ca href="logout.php"\u003eLogout\u003c/a\u003e\u003c/li\u003e
                \u003c?php endif; ?\u003e
            \u003c/ul\u003e
        \u003c/aside\u003e
    \u003c?php endif; ?\u003e
    \u003cdiv class="app-content container-fluid mt-4 \u003c?php echo !$show_sidebar ? 'no-sidebar' : ''; ?\u003e"\u003e