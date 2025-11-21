<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/auth.php';
require_role('teacher');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

$username = $_SESSION['username'];
$teacher_res = mysqli_query($link, "SELECT teacher_id, name, profile_picture FROM teachers WHERE email='" . mysqli_real_escape_string($link, $username) . "'");
$teacher = mysqli_fetch_assoc($teacher_res);
$teacher_id = $teacher ? (int)$teacher['teacher_id'] : 0;
$teacher_name = $teacher ? $teacher['name'] : 'Teacher';
$profile_picture = $teacher ? $teacher['profile_picture'] : 'default_avatar.png';

// Get comprehensive teacher data
$teacher_data = getTeacherDashboardData($teacher_id, $link);

// Get active tab from URL parameter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

function getTeacherDashboardData($teacher_id, $link) {
    $data = [
        'classes' => [],
        'subjects' => [],
        'pending_results' => 0,
        'unmarked_assignments' => 7, // Static for demo
        'unread_messages' => 2, // Static for demo
        'results_count' => 0,
        'total_students' => 0,
        'today_schedule' => []
    ];
    
    if ($teacher_id > 0) {
        // Get classes assigned to teacher via teacher_class_assignments
        $classes_query = "SELECT c.class_id, c.class_name, c.stream, COUNT(s.student_id) as student_count 
                         FROM classes c 
                         JOIN teacher_class_assignments tca ON c.class_id = tca.class_id
                         LEFT JOIN students s ON c.class_id = s.class_id 
                         WHERE tca.teacher_id = $teacher_id 
                         GROUP BY c.class_id, c.class_name, c.stream";
        $classes_result = mysqli_query($link, $classes_query);
        while ($row = mysqli_fetch_assoc($classes_result)) {
            $data['classes'][] = $row;
            $data['total_students'] += $row['student_count'];
        }
        
        // Get subjects taught by teacher via teacher_subject_assignments, include class_id for downstream counts
        $subjects_query = "SELECT DISTINCT sub.subject_id, sub.subject_name, c.class_name, c.class_id 
                          FROM teacher_subject_assignments tsa 
                          JOIN subjects sub ON tsa.subject_id = sub.subject_id 
                          JOIN classes c ON sub.class_id = c.class_id 
                          WHERE tsa.teacher_id = $teacher_id";
        $subjects_result = mysqli_query($link, $subjects_query);
        while ($row = mysqli_fetch_assoc($subjects_result)) {
            $data['subjects'][] = $row;
        }
        
        // Get results count
        $results_query = "SELECT COUNT(*) as c FROM results WHERE teacher_id = $teacher_id";
        $results_result = mysqli_query($link, $results_query);
        if ($row = mysqli_fetch_assoc($results_result)) {
            $data['results_count'] = (int)$row['c'];
        }
        
        // Get pending results count
        $current_term = 'Term 1';
        $current_year = date('Y');
        $pending_query = "SELECT COUNT(DISTINCT s.student_id) as pending 
                         FROM students s 
                         JOIN classes c ON s.class_id = c.class_id 
                         JOIN teacher_class_assignments tca ON c.class_id = tca.class_id
                         WHERE tca.teacher_id = $teacher_id 
                         AND NOT EXISTS (
                             SELECT 1 FROM results r 
                             WHERE r.student_id = s.student_id 
                             AND r.term = '$current_term' 
                             AND r.year = $current_year
                             AND r.teacher_id = $teacher_id
                         )";
        $pending_result = mysqli_query($link, $pending_query);
        if ($row = mysqli_fetch_assoc($pending_result)) {
            $data['pending_results'] = (int)$row['pending'];
        }
        
        // Get today's schedule from timetables for this teacher
        $day_short = date('D'); // Mon/Tue/Wed/Thu/Fri/Sat/Sun
        $day_full = date('l');  // Monday/Tuesday/... 

        // Detect legacy vs new column names for start/end time
        $has_period_start = false; $has_period_end = false; $has_start_time = false; $has_end_time = false;
        $colRes = mysqli_query($link, "SHOW COLUMNS FROM timetables");
        if ($colRes && $colRes instanceof mysqli_result) {
            while ($c = mysqli_fetch_assoc($colRes)) {
                if ($c['Field'] === 'period_start') $has_period_start = true;
                if ($c['Field'] === 'period_end') $has_period_end = true;
                if ($c['Field'] === 'start_time') $has_start_time = true;
                if ($c['Field'] === 'end_time') $has_end_time = true;
            }
        }
        $startCol = ($has_period_start ? 'period_start' : ($has_start_time ? 'start_time' : null));
        $endCol = ($has_period_end ? 'period_end' : ($has_end_time ? 'end_time' : null));

        // Build query safely depending on available columns
        $schedule_query = "SELECT c.class_name, c.stream, sub.subject_name";
        if ($startCol && $endCol) {
            $schedule_query .= ", t.$startCol AS start_col, t.$endCol AS end_col";
        } else {
            // Fallback: use period text only
            $schedule_query .= ", t.period AS period_text";
        }
        $schedule_query .= " FROM timetables t
                              JOIN classes c ON t.class_id = c.class_id
                              JOIN subjects sub ON t.subject_id = sub.subject_id
                              WHERE t.teacher_id = $teacher_id
                              AND (t.day_of_week = '$day_short' OR t.day_of_week = '$day_full')";
        if ($startCol) { $schedule_query .= " ORDER BY t.$startCol ASC"; }
        $schedule_result = mysqli_query($link, $schedule_query);
        while ($row = mysqli_fetch_assoc($schedule_result)) {
            $time_slot = 'TBA';
            if (isset($row['start_col']) && isset($row['end_col'])) {
                $time_slot = date('H:i', strtotime($row['start_col'])) . '-' . date('H:i', strtotime($row['end_col']));
            } elseif (isset($row['period_text'])) {
                $time_slot = $row['period_text'];
            }
            $end_time_val = isset($row['end_col']) ? strtotime($row['end_col']) : null;
            $data['today_schedule'][] = [
                'class_name' => $row['class_name'],
                'stream' => $row['stream'],
                'subject_name' => $row['subject_name'],
                'room' => 'TBA',
                'time_slot' => $time_slot,
                'status' => ($end_time_val && $end_time_val < time()) ? 'present' : 'upcoming'
            ];
        }
    }
    
    return $data;
}

// Handle tab-specific data loading
$tab_data = [];
switch ($active_tab) {
    case 'classes':
        $tab_data = getTeacherClassesData($teacher_id, $link);
        break;
    case 'results':
        $tab_data = getTeacherResultsData($teacher_id, $link);
        break;
    case 'gradebook':
        $tab_data = getTeacherGradebookData($teacher_id, $link);
        break;
    case 'attendance':
        $tab_data = getTeacherAttendanceData($teacher_id, $link);
        break;
    case 'assignments':
        $tab_data = getTeacherAssignmentsData($teacher_id, $link);
        break;
    case 'reports':
        $tab_data = getTeacherReportsData($teacher_id, $link);
        break;
    case 'messages':
        $tab_data = getTeacherMessagesData($teacher_id, $link);
        break;
    case 'profile':
        $tab_data = getTeacherProfileData($teacher_id, $link);
        break;
}

function getTeacherClassesData($teacher_id, $link) {
    $data = ['classes' => []];
    $query = "SELECT c.*, COUNT(s.student_id) as student_count 
              FROM classes c 
              JOIN teacher_class_assignments tca ON c.class_id = tca.class_id
              LEFT JOIN students s ON c.class_id = s.class_id 
              WHERE tca.teacher_id = $teacher_id 
              GROUP BY c.class_id";
    $result = mysqli_query($link, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $data['classes'][] = $row;
    }
    return $data;
}

function getTeacherResultsData($teacher_id, $link) {
    $data = ['results' => [], 'pending_count' => 0];
    $query = "SELECT r.result_id, r.student_id, r.subject_id, r.term, r.year, r.marks, r.grade, r.remarks, r.teacher_id, s.name as student_name, sub.subject_name, c.class_name 
              FROM results r 
              JOIN students s ON r.student_id = s.student_id 
              JOIN subjects sub ON r.subject_id = sub.subject_id 
              JOIN classes c ON s.class_id = c.class_id 
              WHERE r.teacher_id = $teacher_id 
              ORDER BY r.result_id DESC LIMIT 20";
    $result = mysqli_query($link, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $data['results'][] = $row;
    }
    return $data;
}

function getTeacherGradebookData($teacher_id, $link) {
    $data = ['gradebook' => []];
    return $data;
}

function getTeacherAttendanceData($teacher_id, $link) {
    $data = ['attendance' => [], 'today_stats' => []];
    return $data;
}

function getTeacherAssignmentsData($teacher_id, $link) {
    $data = ['assignments' => [], 'pending_submissions' => 0];
    return $data;
}

function getTeacherReportsData($teacher_id, $link) {
    $data = ['reports' => [], 'analytics' => []];
    return $data;
}

function getTeacherMessagesData($teacher_id, $link) {
    $data = ['messages' => [], 'unread_count' => 0];
    return $data;
}

function getTeacherProfileData($teacher_id, $link) {
    $data = ['profile' => []];
    $query = "SELECT * FROM teachers WHERE teacher_id = $teacher_id";
    $result = mysqli_query($link, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $data['profile'] = $row;
    }
    return $data;
}
?>



    <!-- Teacher Dashboard Wrapper -->
    <div class="teacher-dashboard-wrapper">
        <!-- Navigation Menu -->
        <nav class="teacher-nav">
            <div class="nav-brand">
                <div class="d-flex align-items-center mb-3">
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="rounded-circle me-3" width="50" height="50" style="object-fit: cover;">
                    <div>
                        <h4 class="mb-1">👨‍🏫 Teacher Panel</h4>
                        <span class="teacher-name"><?php echo htmlspecialchars($teacher_name); ?></span>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="?tab=dashboard" class="<?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a></li>
                <li><a href="?tab=classes" class="<?php echo $active_tab == 'classes' ? 'active' : ''; ?>">🏫 My Classes</a></li>
                <li><a href="?tab=results" class="<?php echo $active_tab == 'results' ? 'active' : ''; ?>">📝 Results Entry</a></li>
                <li><a href="?tab=gradebook" class="<?php echo $active_tab == 'gradebook' ? 'active' : ''; ?>">💯 Gradebook</a></li>
                <li><a href="?tab=attendance" class="<?php echo $active_tab == 'attendance' ? 'active' : ''; ?>">出席 Attendance</a></li>
                <li><a href="?tab=assignments" class="<?php echo $active_tab == 'assignments' ? 'active' : ''; ?>">📚 Assignments</a></li>
                <li><a href="?tab=reports" class="<?php echo $active_tab == 'reports' ? 'active' : ''; ?>">📈 Reports</a></li>
                <li><a href="?tab=messages" class="<?php echo $active_tab == 'messages' ? 'active' : ''; ?>">✉️ Messages</a></li>
                <li><a href="?tab=profile" class="<?php echo $active_tab == 'profile' ? 'active' : ''; ?>">👤 Profile</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </nav>

        <?php if ($active_tab == 'dashboard'): ?>
            <!-- Dashboard Overview -->
            <div class="dashboard-header">
                <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $teacher_name)[0]); ?>! 👋</h2>
                <div class="dashboard-clock">
                    <span id="liveDate"></span> | <span id="liveTime"></span>
                </div>
            </div>
            
            <!-- Quick Stats Widget -->
            <div class="quick-stats">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🏫</div>
                        <div class="stat-content">
                            <h4><?php echo count($teacher_data['classes']); ?></h4>
                            <p>Classes Assigned</p>
                            <small><?php echo count($teacher_data['classes']) > 0 ? $teacher_data['classes'][0]['class_name'] . ' (' . ($teacher_data['classes'][0]['stream'] ?? '') . ')' : 'No classes'; ?></small>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h4><?php echo $teacher_data['total_students']; ?></h4>
                            <p>Total Students</p>
                            <small>Across all classes</small>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📚</div>
                        <div class="stat-content">
                            <h4><?php echo count($teacher_data['subjects']); ?></h4>
                            <p>Subjects Teaching</p>
                            <small><?php echo count($teacher_data['subjects']) > 0 ? $teacher_data['subjects'][0]['subject_name'] : 'No subjects'; ?></small>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <h4><?php echo $teacher_data['pending_results']; ?></h4>
                            <p>Pending Results</p>
                            <small>Need grading attention</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Main Content Column -->
                <div class="col-lg-12">
                    <div class="schedule-section">
                        <h4 class="section-title">📅 Today's Schedule</h4>
                        <div class="schedule-list">
                            <?php if (!empty($teacher_data['today_schedule'])):
                                foreach ($teacher_data['today_schedule'] as $schedule): ?>
                                    <div class="schedule-item">
                                        <div class="time-slot"><?php echo htmlspecialchars($schedule['time_slot']); ?></div>
                                        <div class="class-info">
                                            <strong><?php echo htmlspecialchars($schedule['subject_name'] . ' - ' . $schedule['class_name']); ?></strong>
                                            <span><?php echo htmlspecialchars($schedule['room']); ?></span>
                                        </div>
                                        <div class="status <?php echo $schedule['status']; ?>">
                                            <?php echo ucfirst($schedule['status']); ?>
                                        </div>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No classes scheduled for today</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="notifications-section mt-4">
                        <h4 class="section-title">🔔 Recent Notifications</h4>
                        <div class="notification-list">
                            <div class="notification-item alert-warning">
                                <div class="notification-icon">📅</div>
                                <div class="notification-content">
                                    <strong>Results Due Soon</strong>
                                    <p>Term 1 results submission deadline: 3 days remaining</p>
                                    <small>Today at 09:30 AM</small>
                                </div>
                            </div>
                            <div class="notification-item alert-info">
                                <div class="notification-icon">📢</div>
                                <div class="notification-content">
                                    <strong>Staff Meeting</strong>
                                    <p>Monthly staff meeting scheduled for Friday at 3:00 PM in Conference Room</p>
                                    <small>Yesterday at 02:15 PM</small>
                                </div>
                            </div>
                            <div class="notification-item alert-success">
                                <div class="notification-icon">✅</div>
                                <div class="notification-content">
                                    <strong>Results Approved</strong>
                                    <p>Your Form 4 Science mid-term results have been approved by administration</p>
                                    <small>2 days ago</small>
                                </div>
                            </div>
                            <div class="notification-item alert-info">
                                <div class="notification-icon">🎯</div>
                                <div class="notification-content">
                                    <strong>Performance Update</strong>
                                    <p>Class average for Mathematics improved by 12% this term</p>
                                    <small>3 days ago</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="combined-sidebar-section">
                        <div class="quick-actions">
                            <h4 class="section-title">⚡ Quick Actions</h4>
                            <div class="action-buttons">
                                <a href="?tab=results" class="btn btn-success">
                                    <i class="icon">📝</i>
                                    Enter Results
                                </a>
                                <a href="?tab=attendance" class="btn btn-primary">
                                    <i class="icon">✅</i>
                                    Mark Attendance
                                </a>
                                <a href="?tab=assignments" class="btn btn-success" style="background: linear-gradient(135deg, #20c997 0%, #3bd5b0 100%);">
                                    <i class="icon">📋</i>
                                    Create Assignment
                                </a>
                                <a href="?tab=messages" class="btn btn-primary" style="background: linear-gradient(135deg, #2c5e3f 0%, #3a7d52 100%);">
                                    <i class="icon">💬</i>
                                    View Messages
                                    <?php if ($teacher_data['unread_messages'] > 0): ?>
                                        <span class="badge badge-danger ms-1"><?php echo $teacher_data['unread_messages']; ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                        <div class="schedule-section">
                            <h4 class="section-title">⏰ Upcoming Deadlines</h4>
                            <div class="schedule-list">
                                <div class="schedule-item">
                                    <div class="time-slot">Mar 15</div>
                                    <div class="class-info">
                                        <strong>Term 1 Results Submission</strong>
                                        <span>All classes</span>
                                    </div>
                                    <div class="status upcoming">3 days</div>
                                </div>
                                <div class="schedule-item">
                                    <div class="time-slot">Mar 20</div>
                                    <div class="class-info">
                                        <strong>Progress Reports</strong>
                                        <span>Form 4 Science</span>
                                    </div>
                                    <div class="status upcoming">8 days</div>
                                </div>
                                <div class="schedule-item">
                                    <div class="time-slot">Mar 25</div>
                                    <div class="class-info">
                                        <strong>Parent-Teacher Meetings</strong>
                                        <span>Schedule preparation</span>
                                    </div>
                                    <div class="status upcoming">13 days</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Tab-specific content -->
            <div class="tab-content">
                <?php 
                $tab_file = "teacher_{$active_tab}.php";
                if (file_exists($tab_file)) {
                    include $tab_file;
                } else {
                    echo '<div class="alert alert-info">';
                    echo '<h4>'.ucfirst($active_tab).' Module</h4>';
                    echo '<p>This section is currently under development.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div> <!-- Close teacher-dashboard-wrapper -->

<script>
    // Live clock functionality
    function updateClock() {
        const now = new Date();
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
        
        const dateString = now.toLocaleDateString('en-US', dateOptions);
        const timeString = now.toLocaleTimeString('en-US', timeOptions);
        
        const dateElement = document.getElementById('liveDate');
        const timeElement = document.getElementById('liveTime');
        
        if (dateElement) dateElement.textContent = dateString;
        if (timeElement) timeElement.textContent = timeString;
    }
    
    // Update clock immediately and every second
    updateClock();
    setInterval(updateClock, 1000);
    
    // Add smooth animations to cards on load
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.stat-card, .schedule-item, .notification-item');
        cards.forEach((card, index) => {
            card.style.animation = `fadeInUp 0.5s ease-out ${index * 100}ms forwards`;
        });
    });
</script>

<?php include('includes/footer.php'); ?>

