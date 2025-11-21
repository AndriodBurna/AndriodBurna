<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher']);

$database = new Database();
$pdo = $database->getConnection();

// Get class ID from URL
$classId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($classId === 0) {
    $_SESSION['error_message'] = 'Invalid class ID.';
    header('Location: index.php');
    exit();
}

// Get class details with teacher information
$classStmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name as teacher_name, u.email as teacher_email
    FROM classes c
    LEFT JOIN users u ON c.class_teacher_id = u.id
    WHERE c.id = ?
");
$classStmt->execute([$classId]);
$class = $classStmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    $_SESSION['error_message'] = 'Class not found.';
    header('Location: index.php');
    exit();
}

// Get students in this class
$studentsStmt = $pdo->prepare("
    SELECT s.id, s.student_id, s.first_name, s.last_name, s.gender, s.date_of_birth, s.status,
           p.first_name as parent_first_name, p.last_name as parent_last_name, p.phone as parent_phone
    FROM students s
    LEFT JOIN users p ON s.parent_id = p.id
    WHERE s.class_id = ?
    ORDER BY s.first_name, s.last_name
");
$studentsStmt->execute([$classId]);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get subjects assigned to this class
$subjectsStmt = $pdo->prepare("
    SELECT cs.id as class_subject_id, sub.subject_name, sub.subject_code, sub.description,
           t.first_name as teacher_first_name, t.last_name as teacher_last_name
    FROM class_subjects cs
    JOIN subjects sub ON cs.subject_id = sub.id
    LEFT JOIN users t ON cs.teacher_id = t.id
    WHERE cs.class_id = ? AND cs.is_active = 1
    ORDER BY sub.subject_name
");
$subjectsStmt->execute([$classId]);
$subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent results for this class
$resultsStmt = $pdo->prepare("
    SELECT r.id, r.score, r.grade, r.comment, r.created_at,
           s.student_id, s.first_name, s.last_name,
           sub.subject_name, sub.subject_code,
           at.assessment_type_name, at.weightage
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN subjects sub ON r.subject_id = sub.id
    JOIN assessment_types at ON r.assessment_type_id = at.id
    WHERE s.class_id = ? AND r.is_approved = 1
    ORDER BY r.created_at DESC
    LIMIT 10
");
$resultsStmt->execute([$classId]);
$recentResults = $resultsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate class statistics
$totalStudents = count($students);
$activeStudents = count(array_filter($students, function($s) { return $s['status'] === 'active'; }));
$maleStudents = count(array_filter($students, function($s) { return $s['gender'] === 'Male'; }));
$femaleStudents = count(array_filter($students, function($s) { return $s['gender'] === 'Female'; }));

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Class Profile: <?php echo htmlspecialchars($class['class_name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="edit.php?id=<?php echo $classId; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit Class
                        </a>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Classes
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Class Information Card -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-chalkboard-teacher"></i> Class Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Class Name:</strong>
                                <span class="float-end"><?php echo htmlspecialchars($class['class_name']); ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Class Code:</strong>
                                <span class="float-end badge bg-secondary"><?php echo htmlspecialchars($class['class_code']); ?></span>
                            </div>
                            <?php if ($class['description']): ?>
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p class="mt-1"><?php echo htmlspecialchars($class['description']); ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <strong>Class Teacher:</strong>
                                <span class="float-end">
                                    <?php echo $class['teacher_name'] ? htmlspecialchars($class['teacher_name']) : '<em>Not Assigned</em>'; ?>
                                </span>
                            </div>
                            <?php if ($class['teacher_email']): ?>
                            <div class="mb-3">
                                <strong>Teacher Email:</strong>
                                <span class="float-end"><?php echo htmlspecialchars($class['teacher_email']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <strong>Grade Level:</strong>
                                <span class="float-end"><?php echo htmlspecialchars($class['grade_level'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Room Number:</strong>
                                <span class="float-end"><?php echo htmlspecialchars($class['room_number'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Maximum Students:</strong>
                                <span class="float-end"><?php echo htmlspecialchars($class['max_students'] ?? 'Unlimited'); ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="float-end">
                                    <span class="badge bg-<?php echo $class['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $class['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </span>
                            </div>
                            <div class="mb-3">
                                <strong>Created:</strong>
                                <span class="float-end"><?php echo date('M d, Y', strtotime($class['created_at'])); ?></span>
                            </div>
                            <?php if ($class['updated_at']): ?>
                            <div class="mb-3">
                                <strong>Last Updated:</strong>
                                <span class="float-end"><?php echo date('M d, Y', strtotime($class['updated_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Class Statistics Card -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Class Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-3">
                                        <h4 class="text-primary mb-1"><?php echo $totalStudents; ?></h4>
                                        <small class="text-muted">Total Students</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-3">
                                        <h4 class="text-success mb-1"><?php echo $activeStudents; ?></h4>
                                        <small class="text-muted">Active Students</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3">
                                        <h4 class="text-primary mb-1"><?php echo $maleStudents; ?></h4>
                                        <small class="text-muted">Male Students</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3">
                                        <h4 class="text-danger mb-1"><?php echo $femaleStudents; ?></h4>
                                        <small class="text-muted">Female Students</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subjects Card -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-book"></i> Assigned Subjects</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($subjects) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($subjects as $subject): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($subject['subject_code']); ?></small>
                                        </div>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($subject['teacher_first_name'] ? $subject['teacher_first_name'] . ' ' . $subject['teacher_last_name'] : 'No Teacher'); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-book fa-2x mb-2"></i>
                                <p>No subjects assigned to this class yet.</p>
                                <a href="../subjects/assign.php?class_id=<?php echo $classId; ?>" class="btn btn-sm btn-outline-primary">Assign Subjects</a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted">Total Subjects: <?php echo count($subjects); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students List -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Students in <?php echo htmlspecialchars($class['class_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($students) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="studentsTable">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th>Date of Birth</th>
                                            <th>Age</th>
                                            <th>Parent/Guardian</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td>
                                                <a href="../students/view.php?id=<?php echo $student['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($student['date_of_birth'])); ?></td>
                                            <td>
                                                <?php 
                                                $dob = new DateTime($student['date_of_birth']);
                                                $now = new DateTime();
                                                $age = $now->diff($dob)->y;
                                                echo $age . ' years';
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo $student['parent_first_name'] ? htmlspecialchars($student['parent_first_name'] . ' ' . $student['parent_last_name']) : '<em>Not Assigned</em>'; ?>
                                                <?php if ($student['parent_phone']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($student['parent_phone']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($student['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../students/view.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-primary btn-sm" title="View Student">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="../students/edit.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-secondary btn-sm" title="Edit Student">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <h5>No Students Assigned</h5>
                                <p>This class currently has no students assigned to it.</p>
                                <a href="../students/add.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Student
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Results -->
            <?php if (count($recentResults) > 0): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Recent Results</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Student</th>
                                            <th>Subject</th>
                                            <th>Assessment</th>
                                            <th>Score</th>
                                            <th>Grade</th>
                                            <th>Comment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentResults as $result): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($result['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['assessment_type_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['score']); ?>%</td>
                                            <td>
                                                <span class="badge bg-<?php echo getGradeColor($result['grade']); ?>">
                                                    <?php echo htmlspecialchars($result['grade']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($result['comment'] ?? 'No comment'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#studentsTable').DataTable({
        "pageLength": 25,
        "order": [[1, 'asc']], // Sort by name
        "columnDefs": [
            { "orderable": false, "targets": [7] } // Disable sorting for actions column
        ]
    });
});

// Function to get grade color (you might want to add this to your functions.php)
function getGradeColor(grade) {
    const gradeColors = {
        'A': 'success',
        'B': 'info',
        'C': 'warning',
        'D': 'secondary',
        'E': 'danger',
        'F': 'danger'
    };
    return gradeColors[grade] || 'secondary';
}
</script>