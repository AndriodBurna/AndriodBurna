<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher']);

$database = new Database();
$pdo = $database->getConnection();

// Get subject ID
$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($subject_id <= 0) {
    $_SESSION['error_message'] = 'Invalid subject ID.';
    header('Location: index.php');
    exit;
}

// Fetch subject details
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    $_SESSION['error_message'] = 'Subject not found.';
    header('Location: index.php');
    exit;
}

// Get subject statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT cs.class_id) as total_classes,
        COUNT(DISTINCT cs.teacher_id) as total_teachers,
        COUNT(DISTINCT r.id) as total_results,
        COUNT(DISTINCT cs.id) as total_assignments,
        COUNT(DISTINCT r.student_id) as students_with_results
    FROM subjects s
    LEFT JOIN class_subjects cs ON s.id = cs.subject_id AND cs.is_active = 1
    LEFT JOIN results r ON s.id = r.subject_id
    WHERE s.id = ?
");
$statsStmt->execute([$subject_id]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get assigned classes and teachers
$assignmentsStmt = $pdo->prepare("
    SELECT cs.id, cs.class_id, cs.teacher_id, cs.is_active, cs.created_at,
           c.class_name, c.class_code, c.grade_level,
           CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
           (SELECT COUNT(*) FROM students st WHERE st.class_id = c.id AND st.status = 'active') as student_count
    FROM class_subjects cs
    JOIN classes c ON cs.class_id = c.id
    JOIN users u ON cs.teacher_id = u.id
    WHERE cs.subject_id = ?
    ORDER BY c.grade_level, c.class_name
");
$assignmentsStmt->execute([$subject_id]);
$assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent results (last 10)
$resultsStmt = $pdo->prepare("
    SELECT r.*, 
           CONCAT(s.first_name, ' ', s.last_name) as student_name,
           s.student_id as student_number,
           c.class_name,
           at.assessment_type_name,
           t.term_name,
           ay.academic_year_name
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN assessment_types at ON r.assessment_type_id = at.id
    JOIN academic_terms t ON r.academic_term_id = t.id
    JOIN academic_years ay ON t.academic_year_id = ay.id
    WHERE r.subject_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$resultsStmt->execute([$subject_id]);
$recent_results = $resultsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get performance summary by class
$performanceStmt = $pdo->prepare("
    SELECT c.class_name, c.class_code,
           COUNT(r.id) as total_results,
           AVG(r.marks_obtained) as average_score,
           MIN(r.marks_obtained) as lowest_score,
           MAX(r.marks_obtained) as highest_score,
           COUNT(CASE WHEN r.marks_obtained >= 80 THEN 1 END) as excellent_count,
           COUNT(CASE WHEN r.marks_obtained >= 60 AND r.marks_obtained < 80 THEN 1 END) as good_count,
           COUNT(CASE WHEN r.marks_obtained >= 40 AND r.marks_obtained < 60 THEN 1 END) as average_count,
           COUNT(CASE WHEN r.marks_obtained < 40 THEN 1 END) as below_average_count
    FROM classes c
    JOIN students s ON c.id = s.class_id
    JOIN results r ON s.id = r.student_id AND r.subject_id = ?
    GROUP BY c.id, c.class_name, c.class_code
    ORDER BY c.class_name
");
$performanceStmt->execute([$subject_id]);
$performance_data = $performanceStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Subject Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <?php if (hasRole(['admin', 'principal', 'hod'])): ?>
                        <a href="edit.php?id=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit Subject
                        </a>
                        <a href="assign.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-link"></i> Assign to Classes
                        </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Subjects
                        </a>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Subject Information -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Subject Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Subject Name:</strong> <?php echo htmlspecialchars($subject['subject_name']); ?></p>
                                    <p><strong>Subject Code:</strong> <?php echo htmlspecialchars($subject['subject_code']); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-<?php echo $subject['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $subject['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Created:</strong> <?php echo formatDate($subject['created_at']); ?></p>
                                    <p><strong>Last Updated:</strong> <?php echo formatDate($subject['updated_at']); ?></p>
                                </div>
                            </div>
                            <?php if ($subject['description']): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <p><strong>Description:</strong></p>
                                    <p><?php echo htmlspecialchars($subject['description']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <h4 class="text-primary"><?php echo $stats['total_classes']; ?></h4>
                                    <small class="text-muted">Classes</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <h4 class="text-success"><?php echo $stats['total_teachers']; ?></h4>
                                    <small class="text-muted">Teachers</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-info"><?php echo $stats['total_results']; ?></h4>
                                    <small class="text-muted">Results</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-warning"><?php echo $stats['students_with_results']; ?></h4>
                                    <small class="text-muted">Students</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Assignments -->
            <?php if (count($assignments) > 0): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Class Assignments</h5>
                    <span class="badge bg-primary"><?php echo count($assignments); ?> assignments</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Grade Level</th>
                                    <th>Teacher</th>
                                    <th>Students</th>
                                    <th>Status</th>
                                    <th>Assigned Date</th>
                                    <?php if (hasRole(['admin', 'principal', 'hod'])): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td>
                                        <a href="../classes/view.php?id=<?php echo $assignment['class_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($assignment['class_name'] . ' (' . $assignment['class_code'] . ')'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['grade_level']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $assignment['student_count']; ?> students</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $assignment['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $assignment['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($assignment['created_at']); ?></td>
                                    <?php if (hasRole(['admin', 'principal', 'hod'])): ?>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="assign.php?edit=<?php echo $assignment['id']; ?>" class="btn btn-outline-primary btn-sm" title="Edit Assignment">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="confirmRemoveAssignment(<?php echo $assignment['id']; ?>, '<?php echo htmlspecialchars(addslashes($assignment['class_name'])); ?>')" 
                                                    title="Remove Assignment">
                                                <i class="fas fa-unlink"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Performance by Class -->
            <?php if (count($performance_data) > 0): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Performance Summary by Class</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Results</th>
                                    <th>Average Score</th>
                                    <th>Range</th>
                                    <th>Excellent (80%+)</th>
                                    <th>Good (60-79%)</th>
                                    <th>Average (40-59%)</th>
                                    <th>Below Average (&lt;40%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($performance_data as $class): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($class['class_name'] . ' (' . $class['class_code'] . ')'); ?></td>
                                    <td><?php echo $class['total_results']; ?></td>
                                    <td>
                                        <strong><?php echo number_format($class['average_score'], 1); ?>%</strong>
                                    </td>
                                    <td>
                                        <small><?php echo number_format($class['lowest_score'], 1); ?>% - <?php echo number_format($class['highest_score'], 1); ?>%</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $class['excellent_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $class['good_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?php echo $class['average_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $class['below_average_count']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Results -->
            <?php if (count($recent_results) > 0): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Results</h5>
                    <a href="../results/index.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-primary">
                        View All Results
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Assessment</th>
                                    <th>Term</th>
                                    <th>Score</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_results as $result): 
                                    $grade = calculateGrade($result['marks_obtained']);
                                ?>
                                <tr>
                                    <td>
                                        <a href="../students/view.php?id=<?php echo $result['student_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($result['student_name'] . ' (' . $result['student_number'] . ')'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['assessment_type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['term_name'] . ' ' . $result['academic_year_name']); ?></td>
                                    <td>
                                        <strong><?php echo number_format($result['marks_obtained'], 1); ?>%</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $grade['color']; ?>"><?php echo $grade['grade']; ?></span>
                                    </td>
                                    <td><?php echo formatDate($result['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body text-center text-muted">
                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                    <h5>No Results Yet</h5>
                    <p>No results have been entered for this subject.</p>
                    <?php if (hasRole(['teacher', 'admin', 'principal', 'hod'])): ?>
                    <a href="../results/entry.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Enter Results
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function confirmRemoveAssignment(id, className) {
    confirmAction(
        'Remove Assignment',
        'Are you sure you want to remove this subject assignment from ' + className + '?',
        function() {
            // Redirect to assignment removal page
            window.location.href = 'assign.php?remove=' + id;
        }
    );
}
</script>