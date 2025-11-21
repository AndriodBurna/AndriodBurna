<?php
include "config.php";
include "includes/auth.php";

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// ================== MARK ATTENDANCE ==================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_attendance'])) {
    $subject_id = intval($_POST['subject_id']);
    $date = $_POST['date'];

    if (empty($subject_id) || empty($date)) {
        $error = "Please select both subject and date.";
    } else {
        $marked_count = 0;
        foreach ($_POST['attendance'] as $student_id => $status) {
            $stmt = $conn->prepare("
                INSERT INTO attendance (student_id, subject_id, date, status, teacher_id)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), teacher_id = VALUES(teacher_id)
            ");
            $stmt->bind_param("iissi", $student_id, $subject_id, $date, $status, $current_user_id);
            if ($stmt->execute()) {
                $marked_count++;
            }
            $stmt->close();
        }
        $success = "Attendance marked successfully for $marked_count student(s) on $date!";
    }
}

// ================== FETCH STUDENTS ==================
$students = [];
$result = $conn->query("SELECT id, username FROM users WHERE role = 'student' ORDER BY username");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// ================== FETCH SUBJECTS ==================
$subjects = [];
$subj_result = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name");
if ($subj_result && $subj_result->num_rows > 0) {
    while ($row = $subj_result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// ================== REPORT FILTERS ==================
$report_date = $_GET['report_date'] ?? null;
$filter_subject = $_GET['filter_subject'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$attendance_report = [];
$summary_report = [];

// ================== FETCH DAILY REPORT ==================
if ($report_date) {
    $sql = "
        SELECT a.*, u.username, s.subject_name 
        FROM attendance a
        JOIN users u ON a.student_id = u.id
        JOIN subjects s ON a.subject_id = s.id
        WHERE a.date = ?
    ";
    $params = [$report_date];
    $types = "s";
    
    if ($filter_subject !== '') {
        $sql .= " AND a.subject_id = ?";
        $params[] = intval($filter_subject);
        $types .= "i";
    }
    $sql .= " ORDER BY u.username";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $attendance_report = $stmt->get_result();
    }
    $stmt->close();
}

// ================== FETCH SUMMARY REPORT ==================
if ($date_from && $date_to) {
    $summary_sql = "
        SELECT u.username,
               s.subject_name,
               COUNT(*) AS total_days,
               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
               SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
               SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) AS late_days,
               ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) AS attendance_percentage
        FROM attendance a
        JOIN users u ON a.student_id = u.id
        JOIN subjects s ON a.subject_id = s.id
        WHERE a.date BETWEEN ? AND ?
    ";
    $params = [$date_from, $date_to];
    $types = "ss";
    
    if ($filter_subject !== '') {
        $summary_sql .= " AND a.subject_id = ?";
        $params[] = intval($filter_subject);
        $types .= "i";
    }
    $summary_sql .= " GROUP BY a.student_id, a.subject_id ORDER BY u.username, s.subject_name";

    $stmt = $conn->prepare($summary_sql);
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $summary_report = $stmt->get_result();
    }
    $stmt->close();
}

// Calculate stats for mark attendance
$stats_present = 0;
$stats_absent = 0;
$stats_late = 0;
?>

<?php include "includes/header.php"; ?>

<style>
    * {
        box-sizing: border-box;
    }
    
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .main-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-header h1 {
        font-size: 32px;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .header-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 15px;
        border-radius: 15px;
        color: white;
        font-size: 24px;
    }
    
    .date-display {
        text-align: right;
    }
    
    .date-display .label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .date-display .date {
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }
    
    .tab-navigation {
        background: white;
        border-radius: 20px;
        padding: 10px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        gap: 10px;
    }
    
    .tab-btn {
        flex: 1;
        padding: 15px 30px;
        border: none;
        background: transparent;
        color: #666;
        font-size: 16px;
        font-weight: 600;
        border-radius: 15px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .tab-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .tab-btn:hover:not(.active) {
        background: #f5f5f5;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        font-size: 20px;
        font-weight: bold;
        color: #333;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        padding: 25px;
        border-radius: 15px;
        color: white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .stat-card.total {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .stat-card.present {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    
    .stat-card.absent {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    
    .stat-card.late {
        background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
    }
    
    .stat-icon {
        font-size: 32px;
        margin-bottom: 10px;
        opacity: 0.8;
    }
    
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 5px;
    }
    
    .stat-value {
        font-size: 36px;
        font-weight: bold;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
        font-size: 14px;
    }
    
    .form-group input,
    .form-group select {
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    
    .search-box input {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 14px;
    }
    
    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 12px 25px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
    }
    
    .btn-export {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .table-container {
        overflow-x: auto;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }
    
    thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
    }
    
    td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }
    
    tbody tr:hover {
        background: #f8f9ff;
    }
    
    .status-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    
    .status-btn {
        padding: 8px 16px;
        border: 2px solid #e0e0e0;
        background: white;
        color: #666;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .status-btn.active.present {
        background: #43e97b;
        color: white;
        border-color: #43e97b;
        box-shadow: 0 3px 10px rgba(67, 233, 123, 0.4);
    }
    
    .status-btn.active.absent {
        background: #fa709a;
        color: white;
        border-color: #fa709a;
        box-shadow: 0 3px 10px rgba(250, 112, 154, 0.4);
    }
    
    .status-btn.active.late {
        background: #ffd89b;
        color: #333;
        border-color: #ffd89b;
        box-shadow: 0 3px 10px rgba(255, 216, 155, 0.4);
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
    }
    
    .status-badge.present {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.absent {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-badge.late {
        background: #fff3cd;
        color: #856404;
    }
    
    .progress-bar-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .progress-bar {
        flex: 1;
        height: 8px;
        background: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s;
    }
    
    .progress-fill.good {
        background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
    }
    
    .progress-fill.poor {
        background: linear-gradient(90deg, #fa709a 0%, #fee140 100%);
    }
    
    .progress-text {
        font-weight: bold;
        min-width: 50px;
    }
    
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .status-indicator.good {
        background: #d4edda;
        color: #155724;
    }
    
    .status-indicator.poor {
        background: #f8d7da;
        color: #721c24;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 2px solid #c3e6cb;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 2px solid #f5c6cb;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .status-buttons {
            flex-direction: column;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="main-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <span class="header-icon">📅</span>
            Attendance Management System
        </h1>
        <div class="date-display">
            <div class="label">Today's Date</div>
            <div class="date"><?= date('F d, Y') ?></div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            ✕ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-btn active" onclick="switchTab('mark')">
            ✓ Mark Attendance
        </button>
        <button class="tab-btn" onclick="switchTab('reports')">
            📊 View Reports
        </button>
    </div>

    <!-- Mark Attendance Tab -->
    <div id="mark-tab" class="tab-content active">
        <!-- Filters Card -->
        <div class="card">
            <div class="card-header">
                🔍 Select Class & Subject
            </div>
            <form method="POST" action="" id="attendanceForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="date">Date *</label>
                        <input type="date" name="date" id="date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="subject_id">Subject *</label>
                        <select name="subject_id" id="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            
                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon">👥</div>
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value" id="stat-total"><?= count($students) ?></div>
                    </div>
                    <div class="stat-card present">
                        <div class="stat-icon">✓</div>
                        <div class="stat-label">Present</div>
                        <div class="stat-value" id="stat-present">0</div>
                    </div>
                    <div class="stat-card absent">
                        <div class="stat-icon">✕</div>
                        <div class="stat-label">Absent</div>
                        <div class="stat-value" id="stat-absent">0</div>
                    </div>
                    <div class="stat-card late">
                        <div class="stat-icon">🕐</div>
                        <div class="stat-label">Late</div>
                        <div class="stat-value" id="stat-late">0</div>
                    </div>
                </div>
        </div>

        <!-- Students List Card -->
        <div class="card">
            <div class="card-header">
                📝 Student Attendance
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="button" class="btn btn-success" onclick="markAllStatus('present')">
                    ✓ Mark All Present
                </button>
                <button type="button" class="btn btn-danger" onclick="markAllStatus('absent')">
                    ✕ Mark All Absent
                </button>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchStudent" placeholder="Search by student name..." onkeyup="filterStudents()">
            </div>

            <?php if (!empty($students)): ?>
                <div class="table-container">
                    <table id="studentTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th style="text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr class="student-row">
                                    <td class="student-name"><?= htmlspecialchars($student['username']) ?></td>
                                    <td>
                                        <div class="status-buttons">
                                            <button type="button" class="status-btn active present" 
                                                    onclick="setStatus(this, <?= $student['id'] ?>, 'present')">
                                                Present
                                            </button>
                                            <button type="button" class="status-btn absent" 
                                                    onclick="setStatus(this, <?= $student['id'] ?>, 'absent')">
                                                Absent
                                            </button>
                                            <button type="button" class="status-btn late" 
                                                    onclick="setStatus(this, <?= $student['id'] ?>, 'late')">
                                                Late
                                            </button>
                                            <input type="hidden" name="attendance[<?= $student['id'] ?>]" value="present" class="status-input">
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 30px; text-align: right;">
                    <button type="submit" name="mark_attendance" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">
                        💾 Submit Attendance
                    </button>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <p>No students found in the system.</p>
                </div>
            <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Reports Tab -->
    <div id="reports-tab" class="tab-content">
        <!-- Filters Card -->
        <div class="card">
            <div class="card-header">
                🔍 Report Filters
            </div>
            <form method="GET" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="date_from">From Date *</label>
                        <input type="date" name="date_from" id="date_from" value="<?= $date_from ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date_to">To Date *</label>
                        <input type="date" name="date_to" id="date_to" value="<?= $date_to ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="filter_subject">Subject</label>
                        <select name="filter_subject" id="filter_subject">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>" <?= ($filter_subject == $sub['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            📊 View Report
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($date_from && $date_to): ?>
            <!-- Summary Report Card -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>📈 Attendance Summary Report</span>
                    <button type="button" class="btn btn-export" onclick="exportReport()">
                        📥 Export Report
                    </button>
                </div>

                <?php if (!empty($summary_report) && $summary_report->num_rows > 0): ?>
                    <p style="color: #666; margin-bottom: 20px;">
                        Showing results from <strong><?= date('M d, Y', strtotime($date_from)) ?></strong> 
                        to <strong><?= date('M d, Y', strtotime($date_to)) ?></strong>
                    </p>

                    <!-- Search Box -->
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="searchReport" placeholder="Search by student name..." onkeyup="filterReport()">
                    </div>

                    <div class="table-container">
                        <table id="reportTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th style="text-align: center;">Total Days</th>
                                    <th style="text-align: center;">Present</th>
                                    <th style="text-align: center;">Absent</th>
                                    <th style="text-align: center;">Late</th>
                                    <th style="text-align: center;">Attendance %</th>
                                    <th style="text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $summary_report->fetch_assoc()): ?>
                                    <tr class="report-row">
                                        <td class="report-student"><?= htmlspecialchars($row['username']) ?></td>
                                        <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                        <td style="text-align: center;"><strong><?= $row['total_days'] ?></strong></td>
                                        <td style="text-align: center;">
                                            <span class="status-badge present"><?= $row['present_days'] ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="status-badge absent"><?= $row['absent_days'] ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="status-badge late"><?= $row['late_days'] ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="progress-bar-container">
                                                <div class="progress-bar">
                                                    <div class="progress-fill <?= $row['attendance_percentage'] >= 75 ? 'good' : 'poor' ?>" 
                                                         style="width: <?= $row['attendance_percentage'] ?>%;"></div>
                                                </div>
                                                <span class="progress-text"><?= $row['attendance_percentage'] ?>%</span>
                                            </div>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($row['attendance_percentage'] >= 75): ?>
                                                <span class="status-indicator good">
                                                    ✓ Good
                                                </span>
                                            <?php else: ?>
                                                <span class="status-indicator poor">
                                                    ⚠ Poor
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📊</div>
                        <p>No attendance records found for the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Tab Switching
function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + '-tab').classList.add('active');
}

// Set individual status
function setStatus(button, studentId, status) {
    const row = button.closest('tr');
    const buttons = row.querySelectorAll('.status-btn');
    const input = row.querySelector('.status-input');
    
    // Remove active class from all buttons in this row
    buttons.forEach(btn => {
        btn.classList.remove('active', 'present', 'absent', 'late');
        const btnText = btn.textContent.trim().toLowerCase();
        btn.classList.add(btnText);
    });
    
    // Add active class to clicked button
    button.classList.add('active', status);
    
    // Update hidden input
    input.value = status;
    
    // Update stats
    updateStats();
}

// Mark all students with a specific status
function markAllStatus(status) {
    const buttons = document.querySelectorAll('.status-btn.' + status);
    buttons.forEach(btn => {
        btn.click();
    });
}

// Update statistics
function updateStats() {
    let present = 0, absent = 0, late = 0;
    
    document.querySelectorAll('.status-input').forEach(input => {
        const value = input.value;
        if (value === 'present') present++;
        else if (value === 'absent') absent++;
        else if (value === 'late') late++;
    });
    
    document.getElementById('stat-present').textContent = present;
    document.getElementById('stat-absent').textContent = absent;
    document.getElementById('stat-late').textContent = late;
}

// Filter students in mark attendance
function filterStudents() {
    const searchTerm = document.getElementById('searchStudent').value.toLowerCase();
    const rows = document.querySelectorAll('#studentTable .student-row');
    
    rows.forEach(row => {
        const name = row.querySelector('.student-name').textContent.toLowerCase();
        if (name.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Filter students in report
function filterReport() {
    const searchTerm = document.getElementById('searchReport').value.toLowerCase();
    const rows = document.querySelectorAll('#reportTable .report-row');
    
    rows.forEach(row => {
        const name = row.querySelector('.report-student').textContent.toLowerCase();
        if (name.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Export report to CSV
function exportReport() {
    const table = document.getElementById('reportTable');
    if (!table) {
        alert('No report data to export');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            // Get text content and clean it
            let text = col.textContent.trim();
            // Remove extra whitespace and newlines
            text = text.replace(/\s+/g, ' ');
            // Escape quotes and wrap in quotes if contains comma
            text = text.replace(/"/g, '""');
            if (text.includes(',')) {
                text = '"' + text + '"';
            }
            rowData.push(text);
        });
        csv.push(rowData.join(','));
    });
    
    // Create download link
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'attendance_report_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Initialize stats on page load
document.addEventListener('DOMContentLoaded', function() {
    updateStats();
});
</script>

<?php include "includes/footer.php"; ?>