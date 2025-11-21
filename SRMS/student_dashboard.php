\u003c?php
// session_start(); // Session is likely started in includes/config.php or includes/header.php
// include(__DIR__ . '/includes/config.php');
// include(__DIR__ . '/includes/checklogin.php');
include(__DIR__ . '/includes/helpers.php');
// check_login();

// Fetch student details
$student_id = $_SESSION['std_id'];
error_log("Student ID from session: " . $student_id); // Debugging line
$stmt = $mysqli-\u003eprepare("SELECT s.*, c.class_name FROM students s JOIN classes c ON s.class_id = c.class_id WHERE s.student_id = ?");
$stmt-\u003ebind_param('i', $student_id);
$stmt-\u003eexecute();
$result = $stmt-\u003eget_result();
$student = $result-\u003efetch_object();
if ($student) { // Debugging block
    error_log("Student object fetched: " . print_r($student, true));
} else {
    error_log("Student object is null. No student found for ID: " . $student_id);
}
$stmt-\u003eclose();

// Fetch academic statistics
// Overall Average (example - needs actual calculation based on results)
$overall_average = get_overall_average($student_id, $mysqli); // You\'ll need to implement this function

// Attendance (example - needs actual calculation)
$total_classes = 100; // Example total classes
$attended_classes = 85; // Example attended classes
$attendance_percentage = ($total_classes \u003e 0) ? ($attended_classes / $total_classes) * 100 : 0;

// Pending Assignments (example)
$pending_assignments = 5; // Example count

// Unread Messages (example)
$unread_messages = 2; // Example count

// Recent Activity (example data)
$recent_activities = [
    ['icon' =\u003e 'fas fa-book-open', 'title' =\u003e 'New Assignment Posted', 'description' =\u003e 'Mathematics - Algebra II', 'time' =\u003e '2 hours ago'],
    ['icon' =\u003e 'fas fa-bell', 'title' =\u003e 'Announcement: School Holiday', 'description' =\u003e 'No classes on Monday, Oct 26th', 'time' =\u003e '1 day ago'],
    ['icon' =\u003e 'fas fa-check-circle', 'title' =\u003e 'Grade Updated', 'description' =\u003e 'Science - Physics (B+)', 'time' =\u003e '3 days ago'],
];

?\u003e

\u003c!doctype html\u003e
\u003chtml lang="en" class="no-js"\u003e

\u003chead\u003e
    \u003cmeta charset="UTF-8"\u003e
    \u003cmeta http-equiv="X-UA-Compatible" content="IE=edge"\u003e
    \u003cmeta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1"\u003e
    \u003cmeta name="description" content=""\u003e
    \u003cmeta name="author" content=""\u003e
    \u003cmeta name="theme-color" content="#3e454c"\u003e

    \u003ctitle\u003eStudent Dashboard\u003c/title\u003e

    \u003c?php include('includes/header.php'); ?\u003e
    \u003clink rel="stylesheet" href="assets/style.css"\u003e
    \u003cscript src="https://cdn.jsdelivr.net/npm/chart.js"\u003e\u003c/script\u003e
\u003c/head\u003e

\u003cbody\u003e
    \u003c?php include('includes/navbar.php'); ?\u003e
    \u003cdiv class="ts-main-content"\u003e
        \u003c?php include('includes/sidebar.php'); ?\u003e
        \u003cdiv class="content-wrapper"\u003e
            \u003cdiv class="container-fluid"\u003e
                \u003cdiv class="row"\u003e
                    \u003cdiv class="col-md-12"\u003e
                        \u003ch2 class="page-title"\u003eStudent Dashboard\u003c/h2\u003e

                        \u003cdiv class="row student-dashboard"\u003e
                            \u003cdiv class="col-md-12"\u003e
                                \u003c!-- Student Profile Card --\u003e
                                \u003cdiv class="profile-card"\u003e
                                    \u003cimg src="assets/uploads/students/\u003c?php echo htmlentities($student-\u003eprofile_picture); ?\u003e" alt="Profile Picture"\u003e
                                    \u003cdiv class="profile-info"\u003e
                                        \u003ch3\u003e\u003c?php echo htmlentities($student-\u003efirst_name . ' ' . $student-\u003elast_name); ?\u003e\u003c/h3\u003e
                                        \u003cp\u003eStudent ID: \u003c?php echo htmlentities($student-\u003estudent_id); ?\u003e\u003c/p\u003e
                                        \u003cp\u003eClass: \u003c?php echo htmlentities($student-\u003eclass_name); ?\u003e\u003c/p\u003e
                                        \u003cp\u003eEmail: \u003c?php echo htmlentities($student-\u003eemail); ?\u003e\u003c/p\u003e
                                    \u003c/div\u003e
                                \u003c/div\u003e
                            \u003c/div\u003e

                            \u003cdiv class="col-md-12 stats-overview"\u003e
                                \u003ch4 class="section-title"\u003eAcademic Overview\u003c/h4\u003e
                                \u003cdiv class="row dashboard-grid"\u003e
                                    \u003cdiv class="col-md-3"\u003e
                                        \u003cdiv class="card stat-card"\u003e
                                            \u003cdiv class="card-body"\u003e
                                                \u003cdiv class="stat-icon"\u003e\u003ci class="fas fa-percent"\u003e\u003c/i\u003e\u003c/div\u003e
                                                \u003cdiv class="stat-content"\u003e
                                                    \u003ch4\u003e\u003c?php echo round($overall_average, 2); ?\u003e%\u003c/h4\u003e
                                                    \u003cp\u003eOverall Average\u003c/p\u003e
                                                \u003c/div\u003e
                                            \u003c/div\u003e
                                        \u003c/div\u003e
                                    \u003c/div\u003e
                                    \u003cdiv class="col-md-3"\u003e
                                        \u003cdiv class="card stat-card"\u003e
                                            \u003cdiv class="card-body"\u003e
                                                \u003cdiv class="stat-icon"\u003e\u003ci class="fas fa-user-check"\u003e\u003c/i\u003e\u003c/div\u003e
                                                \u003cdiv class="stat-content"\u003e
                                                    \u003ch4\u003e\u003c?php echo round($attendance_percentage, 2); ?\u003e%\u003c/h4\u003e
                                                    \u003cp\u003eAttendance\u003c/p\u003e
                                                \u003c/div\u003e
                                            \u003c/div\u003e
                                        \u003c/div\u003e
                                    \u003c/div\u003e
                                    \u003cdiv class="col-md-3"\u003e
                                        \u003cdiv class="card stat-card"\u003e
                                            \u003cdiv class="card-body"\u003e
                                                \u003cdiv class="stat-icon"\u003e\u003ci class="fas fa-tasks"\u003e\u003c/i\u003e\u003c/div\u003e
                                                \u003cdiv class="stat-content"\u003e
                                                    \u003ch4\u003e\u003c?php echo htmlentities($pending_assignments); ?\u003e\u003c/h4\u003e
                                                    \u003cp\u003ePending Assignments\u003c/p\u003e
                                                \u003c/div\u003e
                                            \u003c/div\u003e
                                        \u003c/div\u003e
                                    \u003c/div\u003e
                                    \u003cdiv class="col-md-3"\u003e
                                        \u003cdiv class="card stat-card"\u003e
                                            \u003cdiv class="card-body"\u003e
                                                \u003cdiv class="stat-icon"\u003e\u003ci class="fas fa-envelope"\u003e\u003c/i\u003e\u003c/div\u003e
                                                \u003cdiv class="stat-content"\u003e
                                                    \u003ch4\u003e\u003c?php echo htmlentities($unread_messages); ?\u003e\u003c/h4\u003e
                                                    \u003cp\u003eUnread Messages\u003c/p\u003e
                                                \u003c/div\u003e
                                            \u003c/div\u003e
                                        \u003c/div\u003e
                                    \u003c/div\u003e
                                \u003c/div\u003e
                            \u003c/div\u003e

                            \u003cdiv class="col-md-12 recent-activity"\u003e
                                \u003ch4 class="section-title"\u003eRecent Activity\u003c/h4\u003e
                                \u003cdiv class="card dashboard-card"\u003e
                                    \u003cdiv class="card-body"\u003e
                                        \u003cdiv class="notification-list"\u003e
                                            \u003c?php foreach ($recent_activities as $activity) : ?\u003e
                                                \u003cdiv class="activity-item"\u003e
                                                    \u003cdiv class="activity-icon"\u003e\u003ci class="\u003c?php echo htmlentities($activity['icon']); ?\u003e"\u003e\u003c/i\u003e\u003c/div\u003e
                                                    \u003cdiv class="activity-content"\u003e
                                                        \u003ch6\u003e\u003c?php echo htmlentities($activity['title']); ?\u003e\u003c/h6\u003e
                                                        \u003cp\u003e\u003c?php echo htmlentities($activity['description']); ?\u003e\u003c/p\u003e
                                                    \u003c/div\u003e
                                                    \u003cdiv class="activity-time"\u003e\u003c?php echo htmlentities($activity['time']); ?\u003e\u003c/div\u003e
                                                \u003c/div\u003e
                                            \u003c?php endforeach; ?\u003e
                                        \u003c/div\u003e
                                    \u003c/div\u003e
                                \u003c/div\u003e
                            \u003c/div\u003e
                        \u003c/div\u003e
                    \u003c/div\u003e
                \u003c/div\u003e
            \u003c/div\u003e
        \u003c/div\u003e
    \u003c/div\u003e
    \u003c?php include('includes/footer.php'); ?\u003e
\u003c/body\u003e

\u003c/html\u003e