<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher', 'student', 'parent']);

$database = new Database();
$pdo = $database->getConnection();

// Get student ID from URL
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($studentId === 0) {
    $_SESSION['error_message'] = 'Invalid student ID.';
    header('Location: index.php');
    exit();
}

// Check if user has permission to view this student
$currentUser = getCurrentUser();
$canViewAll = in_array($currentUser['role'], ['admin', 'principal', 'hod', 'teacher']);

if (!$canViewAll && $currentUser['role'] === 'student') {
    // Students can only view their own profile
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$currentUser['id']]);
    $ownStudentId = $stmt->fetchColumn();
    
    if ($ownStudentId != $studentId) {
        $_SESSION['error_message'] = 'You do not have permission to view this student.';
        header('Location: index.php');
        exit();
    }
}

if (!$canViewAll && $currentUser['role'] === 'parent') {
    // Parents can only view their children's profiles
    $stmt = $pdo->prepare("SELECT id FROM students WHERE parent_user_id = ?");
    $stmt->execute([$currentUser['id']]);
    $childIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array($studentId, $childIds)) {
        $_SESSION['error_message'] = 'You do not have permission to view this student.';
        header('Location: index.php');
        exit();
    }
}

// Get student data
$stmt = $pdo->prepare("
    SELECT s.*, u.username, u.email as user_email, u.created_at as user_created_at,
           c.id as current_class_id, c.class_name, c.class_code,
           sc.academic_term_id, at.term_name, at.academic_year
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_classes sc ON s.id = sc.student_id AND sc.is_current = 1
    LEFT JOIN classes c ON sc.class_id = c.id
    LEFT JOIN academic_terms at ON sc.academic_term_id = at.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error_message'] = 'Student not found.';
    header('Location: index.php');
    exit();
}

// Get student's academic history
$historyStmt = $pdo->prepare("
    SELECT sc.*, c.class_name, c.class_code, at.term_name, at.academic_year
    FROM student_classes sc
    JOIN classes c ON sc.class_id = c.id
    JOIN academic_terms at ON sc.academic_term_id = at.id
    WHERE sc.student_id = ?
    ORDER BY at.academic_year DESC, at.term_order DESC
");
$historyStmt->execute([$studentId]);
$academicHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get student's recent results
$resultsStmt = $pdo->prepare("
    SELECT r.*, s.subject_name, s.subject_code, at.assessment_type_name,
           at.weightage, at.max_marks, c.class_name
    FROM results r
    JOIN subjects s ON r.subject_id = s.id
    JOIN assessment_types at ON r.assessment_type_id = at.id
    JOIN classes c ON r.class_id = c.id
    JOIN academic_terms act ON r.academic_term_id = act.id
    WHERE r.student_id = ?
    ORDER BY act.academic_year DESC, act.term_order DESC, s.subject_name, at.assessment_order
    LIMIT 20
");
$resultsStmt->execute([$studentId]);
$recentResults = $resultsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get student's attendance summary
$attendanceStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 1) as attendance_rate
    FROM attendance 
    WHERE student_id = ? AND academic_term_id = (
        SELECT id FROM academic_terms WHERE is_current = 1 LIMIT 1
    )
");
$attendanceStmt->execute([$studentId]);
$attendanceSummary = $attendanceStmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Student Profile</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Students
                    </a>
                    <?php if (in_array($currentUser['role'], ['admin', 'principal', 'hod'])): ?>
                    <a href="edit.php?id=<?php echo $studentId; ?>" class="btn btn-sm btn-primary ms-2">
                        <i class="fas fa-edit"></i> Edit Student
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <!-- Student Information Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Student Information</h5>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($student['photo']): ?>
                                <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                                     alt="Student Photo" class="img-fluid rounded mb-3" style="max-height: 200px;">
                            <?php else: ?>
                                <div class="bg-light rounded p-4 mb-3">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h5><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                            <p class="text-muted">ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
                            
                            <div class="text-start">
                                <p><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></p>
                                <p><strong>Date of Birth:</strong> <?php echo $student['date_of_birth'] ? date('F j, Y', strtotime($student['date_of_birth'])) : 'N/A'; ?></p>
                                <p><strong>Current Class:</strong> 
                                    <?php echo $student['class_name'] ? htmlspecialchars($student['class_name'] . ' (' . $student['class_code'] . ')') : 'Not Assigned'; ?>
                                </p>
                                <p><strong>Academic Term:</strong> 
                                    <?php echo $student['term_name'] ? htmlspecialchars($student['term_name'] . ' ' . $student['academic_year']) : 'N/A'; ?>
                                </p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo ($student['status'] === 'active') ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Contact Information</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-primary">Address</h6>
                            <p><?php echo nl2br(htmlspecialchars($student['address'] ?? 'Not provided')); ?></p>
                            
                            <h6 class="text-primary mt-4">Parent/Guardian</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['parent_guardian_name']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['parent_guardian_phone'] ?? 'N/A'); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['parent_guardian_email'] ?? 'N/A'); ?></p>
                            
                            <h6 class="text-primary mt-4">Medical Information</h6>
                            <p><?php echo nl2br(htmlspecialchars($student['medical_info'] ?? 'No medical conditions recorded.')); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-primary"><?php echo count($academicHistory); ?></h4>
                                        <small class="text-muted">Terms Completed</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success"><?php echo count($recentResults); ?></h4>
                                    <small class="text-muted">Recent Results</small>
                                </div>
                            </div>
                            
                            <?php if ($attendanceSummary && $attendanceSummary['total_days'] > 0): ?>
                                <hr>
                                <h6 class="text-primary">Current Term Attendance</h6>
                                <div class="progress mb-2" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $attendanceSummary['attendance_rate']; ?>%">
                                        <?php echo $attendanceSummary['attendance_rate']; ?>%
                                    </div>
                                </div>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <strong><?php echo $attendanceSummary['present_days']; ?></strong><br>
                                        <small class="text-muted">Present</small>
                                    </div>
                                    <div class="col-4">
                                        <strong><?php echo $attendanceSummary['absent_days']; ?></strong><br>
                                        <small class="text-muted">Absent</small>
                                    </div>
                                    <div class="col-4">
                                        <strong><?php echo $attendanceSummary['late_days']; ?></strong><br>
                                        <small class="text-muted">Late</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Results Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Results</h5>
                            <a href="../results/student_results.php?student_id=<?php echo $studentId; ?>" class="btn btn-sm btn-outline-primary">
                                View All Results
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (count($recentResults) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Assessment</th>
                                                <th>Marks</th>
                                                <th>Grade</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentResults as $result): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($result['subject_name'] . ' (' . $result['subject_code'] . ')'); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($result['assessment_type_name']); ?></td>
                                                    <td>
                                                        <?php echo $result['marks_obtained']; ?>/<?php echo $result['max_marks']; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getGradeColor($result['grade']); ?>">
                                                            <?php echo $result['grade']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($result['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                                    <p>No results available yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Academic History Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Academic History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($academicHistory) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Academic Year</th>
                                                <th>Term</th>
                                                <th>Class</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($academicHistory as $history): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($history['academic_year']); ?></td>
                                                    <td><?php echo htmlspecialchars($history['term_name']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($history['class_name'] . ' (' . $history['class_code'] . ')'); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($history['is_current']): ?>
                                                            <span class="badge bg-primary">Current</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Completed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-history fa-3x mb-3"></i>
                                    <p>No academic history available.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
function getGradeColor($grade) {
    switch (strtoupper($grade)) {
        case 'A':
        case 'A+':
        case 'A-':
            return 'success';
        case 'B':
        case 'B+':
        case 'B-':
            return 'info';
        case 'C':
        case 'C+':
        case 'C-':
            return 'warning';
        case 'D':
        case 'F':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<script>
// Add any student-specific JavaScript here
</script>