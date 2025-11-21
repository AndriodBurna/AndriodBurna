<?php
include "config.php";
include "includes/auth.php";

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get current date and time
$current_date = date('F j, Y');
$current_time = date('g:i A');

// Initialize statistics with default values
$stats = [
    'total_students' => 0,
    'total_teachers' => 0,
    'total_parents' => 0,
    'total_subjects' => 0,
    'total_results' => 0,
    'total_courses' => 0,
    'my_results' => 0,
    'my_average' => 0,
    'latest_result' => 0,
    'subjects_count' => 0
];

try {
    // Get statistics based on user role
    if ($role === 'admin') {
        // Admin sees all statistics
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student'");
        if ($result) {
            $stats['total_students'] = $result->fetch_assoc()['count'];
        }
        
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'");
        if ($result) {
            $stats['total_teachers'] = $result->fetch_assoc()['count'];
        }
        
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='parent'");
        if ($result) {
            $stats['total_parents'] = $result->fetch_assoc()['count'];
        }
        
        // Check if subjects table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'subjects'");
        if ($table_check && $table_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM subjects");
            if ($result) {
                $stats['total_subjects'] = $result->fetch_assoc()['count'];
            }
        }
        
        // Check if results table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'results'");
        if ($table_check && $table_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM results");
            if ($result) {
                $stats['total_results'] = $result->fetch_assoc()['count'];
            }
        }
        
        // Check if courses table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'courses'");
        if ($table_check && $table_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM courses");
            if ($result) {
                $stats['total_courses'] = $result->fetch_assoc()['count'];
            }
        }
        
    } elseif ($role === 'teacher') {
        // Teacher statistics
        $result = $conn->query("SELECT COUNT(*) as count FROM results");
        if ($result) {
            $stats['total_results'] = $result->fetch_assoc()['count'];
        }
        
        // Average score
        $result = $conn->query("SELECT ROUND(AVG(score),2) AS avg_score FROM results WHERE score IS NOT NULL");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['avg_score'] = $row['avg_score'] ?: 0;
        }
        
        // Best student
        $best_student_query = "
            SELECT u.username, AVG(r.score) AS avg_score 
            FROM results r 
            JOIN users u ON r.student_id = u.id 
            WHERE u.role='student' AND r.score IS NOT NULL
            GROUP BY r.student_id 
            ORDER BY avg_score DESC LIMIT 1";
        
        $result = $conn->query($best_student_query);
        $best_student = null;
        if ($result && $result->num_rows > 0) {
            $best_student = $result->fetch_assoc();
        }
        
        // Weak student
        $weak_student_query = "
            SELECT u.username, AVG(r.score) AS avg_score 
            FROM results r 
            JOIN users u ON r.student_id = u.id 
            WHERE u.role='student' AND r.score IS NOT NULL
            GROUP BY r.student_id 
            ORDER BY avg_score ASC LIMIT 1";
        
        $result = $conn->query($weak_student_query);
        $weak_student = null;
        if ($result && $result->num_rows > 0) {
            $weak_student = $result->fetch_assoc();
        }
        
    } elseif ($role === 'student') {
        // Student statistics - use proper variable
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM results WHERE student_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $stats['my_results'] = $result->fetch_assoc()['count'];
        }
        
        // My average
        $stmt = $conn->prepare("SELECT ROUND(AVG(score),2) AS avg_score FROM results WHERE student_id = ? AND score IS NOT NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['my_average'] = $row['avg_score'] ?: 0;
        }
        
        // Subjects count
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT subject_id) AS count FROM results WHERE student_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $stats['subjects_count'] = $result->fetch_assoc()['count'];
        }
        
        // Latest result
        $stmt = $conn->prepare("SELECT score FROM results WHERE student_id = ? AND score IS NOT NULL ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $stats['latest_result'] = $result->fetch_assoc()['score'];
        }
    }
    
} catch (Exception $e) {
    // Log error and continue with default values
    error_log("Dashboard stats error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Results Management System</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>📊 SRMS</h2>
        <a href="index.php" class="active">🏠 Dashboard</a>

        <?php if ($role === 'admin'): ?>
            <a href="user_manage.php">👥 Manage Users</a>
            <a href="student_manage.php">👨‍🎓 Manage Students</a>
            <a href="subjects_manage.php">📚 Manage Subjects</a>
            <a href="subjects_list.php">📚 Subjects List</a>
            <a href="courses_manage.php">📖 Manage Courses</a>
            <a href="results_list.php">📊 All Results</a>
            <a href="report_class.php">📈 Performance Reports</a>
            <a href="attendance.php">📅 Attendance</a>
            <a href="parent_manage.php">👨‍👩‍👧 Parent-Student Link</a>
            <a href="contact_teacher.php">📞 Contact Users</a>
            <a href="settings.php">⚙️ System Settings</a>
        <?php elseif ($role === 'teacher'): ?>
            <a href="results_manage.php">➕ Enter Results</a>
            <a href="results_list.php">📊 View Results</a>
            <a href="report_class.php">📈 Class Reports</a>
            <a href="students_list.php">👥 My Students</a>
            <a href="subjects_list.php">📚 My Subjects</a>
            <a href="attendance.php">📅 Attendance</a>
            <a href="contact_teacher.php">📞 Contact Users</a>
        <?php elseif ($role === 'student'): ?>
            <a href="results_list.php">📄 My Results</a>
            <a href="report_class.php">📈 My Performance</a>
            <a href="profile.php">👤 My Profile</a>
            <a href="transcript.php">📑 Transcript</a>
            <a href="contact_teacher.php">📞 Contact Teachers</a>
        <?php elseif ($role === 'parent'): ?>
            <a href="results_list.php">👨‍👩‍👧 Child Results</a>
            <a href="report_class.php">📈 Performance Track</a>
            <!-- <a href="attendance.php">📅 Attendance</a> -->
            <a href="contact_teacher.php">📞 Contact Teachers</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher'): ?>
            <a href="students_list.php">👨‍🎓 Students List</a>
        <?php endif; ?>
        



        <a href="logout.php" style="margin-top: auto; background: #e74c3c;">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1>👋 Welcome, <?= htmlspecialchars($username) ?>!</h1>
            <p>You are logged in as: <strong><?= ucfirst($role) ?></strong></p>
            <div class="welcome-info">
                <div class="welcome-date">📅 <?= $current_date ?></div>
                <div class="welcome-time">🕒 <?= $current_time ?></div>
            </div>
        </div>

        <?php if ($role === 'admin'): ?>
            <!-- Admin Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">👨‍🎓</span>
                    <div class="stat-number" data-target="<?= $stats['total_students'] ?>"><?= $stats['total_students'] ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">👩‍🏫</span>
                    <div class="stat-number" data-target="<?= $stats['total_teachers'] ?>"><?= $stats['total_teachers'] ?></div>
                    <div class="stat-label">Teachers</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">👨‍👩‍👧</span>
                    <div class="stat-number" data-target="<?= $stats['total_parents'] ?>"><?= $stats['total_parents'] ?></div>
                    <div class="stat-label">Parents</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📚</span>
                    <div class="stat-number" data-target="<?= $stats['total_subjects'] ?>"><?= $stats['total_subjects'] ?></div>
                    <div class="stat-label">Subjects</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📊</span>
                    <div class="stat-number" data-target="<?= $stats['total_results'] ?>"><?= $stats['total_results'] ?></div>
                    <div class="stat-label">Results Entered</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="role-info">
                    <h2>📋 Admin Dashboard Overview</h2>
                    <p>You have full system permissions and can manage all aspects of the system.</p>
                    <ul>
                        <li>Manage Teachers, Students, and Parents</li>
                        <li>Configure subjects and courses</li>
                        <li>Access system-wide reports and analytics</li>
                        <li>Export data (CSV/PDF) for external use</li>
                        <li>System configuration and maintenance</li>
                        <li>User role management and permissions</li>
                    </ul>
                </div>

                <div class="system-info">
                    <h2>🔧 System Information</h2>
                    <div class="info-item">
                        <span class="info-label">Your Role</span>
                        <span class="info-value">Administrator</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">User ID</span>
                        <span class="info-value">#<?= $user_id ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">System Status</span>
                        <span class="status-badge">Online</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Login</span>
                        <span class="info-value"><?= $current_time ?></span>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <a href="user_manage.php" class="action-card">
                    <span class="action-icon">👥</span>
                    <div class="action-title">Manage Users</div>
                    <div class="action-desc">Add, edit, or remove system users</div>
                </a>
                <a href="student_manage.php" class="action-card">
                    <span class="action-icon">👨‍🎓</span>
                    <div class="action-title">Manage Students</div>
                    <div class="action-desc">Student enrollment and profiles</div>
                </a>
                <a href="subjects_manage.php" class="action-card">
                    <span class="action-icon">📚</span>
                    <div class="action-title">Manage Subjects</div>
                    <div class="action-desc">Configure subjects and curriculum</div>
                </a>
                <a href="results_list.php" class="action-card">
                    <span class="action-icon">📊</span>
                    <div class="action-title">View All Results</div>
                    <div class="action-desc">Complete results overview</div>
                </a>
                <a href="report_class.php" class="action-card">
                    <span class="action-icon">📈</span>
                    <div class="action-title">Performance Reports</div>
                    <div class="action-desc">Generate detailed analytics</div>
                </a>
                <a href="parent_manage.php" class="action-card">
                    <span class="action-icon">👨‍👩‍👧</span>
                    <div class="action-title">Parent Links</div>
                    <div class="action-desc">Manage parent-student relationships</div>
                </a>
            </div>

        <?php elseif ($role === 'teacher'): ?>
            <!-- Teacher Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">📝</span>
                    <div class="stat-number" data-target="<?= $stats['total_results'] ?>"><?= $stats['total_results'] ?></div>
                    <div class="stat-label">Results Entered</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📊</span>
                    <div class="stat-number"><?= isset($stats['avg_score']) ? $stats['avg_score'] : 'N/A' ?></div>
                    <div class="stat-label">Average Score</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🏆</span>
                    <div class="stat-number"><?= isset($best_student) ? round($best_student['avg_score'], 1) : 'N/A' ?></div>
                    <div class="stat-label">Top Student Score</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📉</span>
                    <div class="stat-number"><?= isset($weak_student) ? round($weak_student['avg_score'], 1) : 'N/A' ?></div>
                    <div class="stat-label">Lowest Score</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="role-info">
                    <h2>📚 Teacher Dashboard</h2>
                    <p>Manage your students' results and track their academic performance effectively.</p>
                    <ul>
                        <li>Enter and edit student results</li>
                        <li>View class performance analytics</li>
                        <li>Generate individual student reports</li>
                        <li>Upload results in bulk using CSV files</li>
                        <li>Track student progress over time</li>
                        <li>Communicate with parents about student performance</li>
                    </ul>
                </div>

                <div class="recent-activity">
                    <h2>🎯 Student Performance Highlights</h2>
                    <?php if (isset($best_student)): ?>
                    <div class="activity-item">
                        <div class="activity-icon">🏆</div>
                        <div class="activity-content">
                            <h4>Top Performer</h4>
                            <div class="activity-time"><?= htmlspecialchars($best_student['username']) ?> - <?= round($best_student['avg_score'], 1) ?>%</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($weak_student)): ?>
                    <div class="activity-item">
                        <div class="activity-icon">📈</div>
                        <div class="activity-content">
                            <h4>Needs Attention</h4>
                            <div class="activity-time"><?= htmlspecialchars($weak_student['username']) ?> - <?= round($weak_student['avg_score'], 1) ?>%</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="activity-item">
                        <div class="activity-icon">📊</div>
                        <div class="activity-content">
                            <h4>Class Average</h4>
                            <div class="activity-time"><?= isset($stats['avg_score']) ? $stats['avg_score'] : 'No data' ?>%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <a href="results_manage.php" class="action-card">
                    <span class="action-icon">➕</span>
                    <div class="action-title">Enter Results</div>
                    <div class="action-desc">Add new student results</div>
                </a>
                <a href="results_list.php" class="action-card">
                    <span class="action-icon">📊</span>
                    <div class="action-title">View Results</div>
                    <div class="action-desc">Browse and edit existing results</div>
                </a>
                <a href="report_class.php" class="action-card">
                    <span class="action-icon">📈</span>
                    <div class="action-title">Class Reports</div>
                    <div class="action-desc">Generate performance analytics</div>
                </a>
            </div>

        <?php elseif ($role === 'student'): ?>
            <!-- Student Dashboard -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">📊</span>
                    <div class="stat-number" data-target="<?= $stats['my_results'] ?>"><?= $stats['my_results'] ?></div>
                    <div class="stat-label">My Results</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🎯</span>
                    <div class="stat-number"><?= $stats['my_average'] ?: 'N/A' ?></div>
                    <div class="stat-label">My Average</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📚</span>
                    <div class="stat-number" data-target="<?= $stats['subjects_count'] ?>"><?= $stats['subjects_count'] ?></div>
                    <div class="stat-label">Subjects</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📈</span>
                    <div class="stat-number"><?= $stats['latest_result'] ?: 'N/A' ?></div>
                    <div class="stat-label">Latest Score</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="role-info">
                    <h2>🎓 Student Portal</h2>
                    <p>Track your academic progress and stay updated with your performance.</p>
                    <ul>
                        <li>View all your subject results and grades</li>
                        <li>Track your performance trends over time</li>
                        <li>Download official transcripts and reports</li>
                        <li>Monitor attendance records</li>
                        <li>Update your personal profile information</li>
                        <li>Access study materials and resources</li>
                    </ul>
                </div>

                <div class="system-info">
                    <h2>📋 My Information</h2>
                    <div class="info-item">
                        <span class="info-label">Student ID</span>
                        <span class="info-value">#<?= $user_id ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?= htmlspecialchars($username) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="status-badge">Active</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Results Count</span>
                        <span class="info-value"><?= $stats['my_results'] ?></span>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <a href="results_list.php" class="action-card">
                    <span class="action-icon">📄</span>
                    <div class="action-title">My Results</div>
                    <div class="action-desc">View all your grades</div>
                </a>
                <a href="report_class.php" class="action-card">
                    <span class="action-icon">📈</span>
                    <div class="action-title">Performance</div>
                    <div class="action-desc">Track your progress</div>
                </a>
                <a href="transcript.php" class="action-card">
                    <span class="action-icon">📑</span>
                    <div class="action-title">Transcript</div>
                    <div class="action-desc">Download official transcript</div>
                </a>
                <a href="profile.php" class="action-card">
                    <span class="action-icon">👤</span>
                    <div class="action-title">My Profile</div>
                    <div class="action-desc">Update personal information</div>
                </a>
            </div>

        <?php elseif ($role === 'parent'): ?>
            <!-- Parent Dashboard -->
            <div class="dashboard-grid">
                <div class="role-info">
                    <h2>👨‍👩‍👧 Parent Dashboard</h2>
                    <p>Monitor your child's academic progress and stay connected with their education.</p>
                    
                    <?php
                    try {
                        // Check if parent_child table exists
                        $table_check = $conn->query("SHOW TABLES LIKE 'parent_child'");
                        if ($table_check && $table_check->num_rows > 0) {
                            $stmt = $conn->prepare("SELECT u.id, u.full_name, u.username 
                                                   FROM parent_child pc
                                                   JOIN users u ON pc.student_id = u.id
                                                   WHERE pc.parent_id = ?");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $children = $stmt->get_result();
                        } else {
                            $children = null;
                        }

                        if ($children && $children->num_rows > 0) {
                            echo '<h3>📚 Your Children\'s Results</h3>';
                            while ($student = $children->fetch_assoc()) {
                                echo '<div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 15px 0; border-left: 4px solid #1abc9c;">';
                                echo '<h4>👤 ' . htmlspecialchars($student['full_name'] ?: $student['username']) . '</h4>';
                                
                                $sid = $student['id'];
                                // Check if results table exists and get results
                                $table_check_results = $conn->query("SHOW TABLES LIKE 'results'");
                                if ($table_check_results && $table_check_results->num_rows > 0) {
                                    $results = $conn->query("SELECT s.subject_name, r.marks 
                                                           FROM results r 
                                                           JOIN subjects s ON r.subject_id = s.id 
                                                           WHERE r.student_id = $sid 
                                                           LIMIT 10");
                                } else {
                                    $results = null;
                                }
                                
                                if ($results && $results->num_rows > 0) {
                                    echo '<table style="width: 100%; margin-top: 10px;">';
                                    echo '<tr><th style="background: #34495e; color: white; padding: 8px;">Subject</th>';
                                    echo '<th style="background: #34495e; color: white; padding: 8px;">Marks</th></tr>';
                                    
                                    while ($result = $results->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($result['subject_name']) . '</td>';
                                        echo '<td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($result['marks']) . '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</table>';
                                } else {
                                    echo '<p style="color: #7f8c8d; font-style: italic;">No results available yet.</p>';
                                }
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-error">';
                            echo '<p>No students are linked to your account yet. Please contact the administrator to link your child\'s account.</p>';
                            echo '</div>';
                        }
                    } catch (Exception $e) {
                        error_log("Parent dashboard error: " . $e->getMessage());
                        echo '<div class="alert alert-error"><p>Unable to load child information at this time.</p></div>';
                    }
                    ?>
                </div>

                <div class="system-info">
                    <h2>👨‍👩‍👧 Parent Information</h2>
                    <div class="info-item">
                        <span class="info-label">Parent ID</span>
                        <span class="info-value">#<?= $user_id ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?= htmlspecialchars($username) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Children Linked</span>
                        <span class="info-value"><?= isset($children) && $children ? $children->num_rows : 0 ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Access Status</span>
                        <span class="status-badge">Active</span>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <a href="results_list.php" class="action-card">
                    <span class="action-icon">👨‍👩‍👧</span>
                    <div class="action-title">Child Results</div>
                    <div class="action-desc">View detailed results</div>
                </a>
                <a href="report_class.php" class="action-card">
                    <span class="action-icon">📈</span>
                    <div class="action-title">Performance</div>
                    <div class="action-desc">Track progress trends</div>
                </a>
                <a href="attendance.php" class="action-card">
                    <span class="action-icon">📅</span>
                    <div class="action-title">Attendance</div>
                    <div class="action-desc">Monitor attendance records</div>
                </a>
                <a href="contact_teacher.php" class="action-card">
                    <span class="action-icon">📞</span>
                    <div class="action-title">Contact Teachers</div>
                    <div class="action-desc">Communicate with educators</div>
                </a>
            </div>

        <?php else: ?>
            <div class="alert alert-error">
                <p>Unknown role. Please contact the administrator.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate counter numbers
    const counters = document.querySelectorAll('.stat-number[data-target]');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target')) || 0;
        const increment = Math.max(target / 100, 1);
        let current = 0;
        
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                if (current > target) current = target;
                counter.textContent = Math.ceil(current);
                setTimeout(updateCounter, 20);
            } else {
                counter.textContent = target;
            }
        };
        
        updateCounter();
    });

    // Add active class to current page
    const currentPage = window.location.pathname.split('/').pop();
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    sidebarLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage || 
           (currentPage === 'index.php' && link.getAttribute('href') === 'index.php')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    // Add hover effects to action cards
    const actionCards = document.querySelectorAll('.action-card');
    actionCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Add click animation to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
    
    // Auto-refresh dashboard every 5 minutes (optional)
    setTimeout(() => {
        if (confirm('Refresh dashboard to get latest data?')) {
            window.location.reload();
        }
    }, 300000); // 5 minutes
});

// Add greeting based on time of day
window.addEventListener('load', function() {
    const hour = new Date().getHours();
    let greeting = '';
    
    if (hour < 12) {
        greeting = 'Good morning';
    } else if (hour < 17) {
        greeting = 'Good afternoon';  
    } else {
        greeting = 'Good evening';
    }
    
    const welcomeText = document.querySelector('.welcome-banner h1');
    if (welcomeText) {
        welcomeText.innerHTML = welcomeText.innerHTML.replace('Welcome', greeting);
    }
});
</script>

</body>
</html>