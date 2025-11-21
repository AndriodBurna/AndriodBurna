<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher']);

$database = new Database();
$pdo = $database->getConnection();

// Get current user's role and ID
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get result ID
$result_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($result_id === 0) {
    $_SESSION['error_message'] = 'Invalid result ID.';
    header('Location: index.php');
    exit;
}

// Get result details with comprehensive information
$resultStmt = $pdo->prepare("
    SELECT r.*, s.student_id as student_code, s.first_name, s.last_name, s.gender,
           c.class_name, c.class_code, sub.subject_name, sub.subject_code,
           CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
           CONCAT(created_by.first_name, ' ', created_by.last_name) as created_by_name,
           CONCAT(updated_by.first_name, ' ', updated_by.last_name) as updated_by_name
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN classes c ON r.class_id = c.id
    JOIN subjects sub ON r.subject_id = sub.id
    JOIN users u ON r.teacher_id = u.id
    LEFT JOIN users created_by ON r.created_by = created_by.id
    LEFT JOIN users updated_by ON r.updated_at IS NOT NULL AND r.updated_by = updated_by.id
    WHERE r.id = ?
");
$resultStmt->execute([$result_id]);
$result = $resultStmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $_SESSION['error_message'] = 'Result not found.';
    header('Location: index.php');
    exit;
}

// Check if user has permission to view this result
if ($user_role === 'teacher' && $result['teacher_id'] != $user_id) {
    $_SESSION['error_message'] = 'You can only view results that you entered.';
    header('Location: index.php');
    exit;
}

// Calculate grade
function getGrade($score) {
    if ($score >= 80) return 'A';
    if ($score >= 70) return 'B';
    if ($score >= 60) return 'C';
    if ($score >= 50) return 'D';
    if ($score >= 40) return 'E';
    return 'F';
}

function getGradeDescription($grade) {
    $descriptions = [
        'A' => 'Excellent',
        'B' => 'Very Good',
        'C' => 'Good',
        'D' => 'Fair',
        'E' => 'Pass',
        'F' => 'Fail'
    ];
    return $descriptions[$grade] ?? 'Unknown';
}

$grade = getGrade($result['score']);
$grade_description = getGradeDescription($grade);

// Get student photo
$photo_path = '../uploads/students/' . $result['student_id'] . '.jpg';
if (!file_exists($photo_path)) {
    $photo_path = '../assets/img/default-student.png';
}

// Get result history (if this result was updated)
$historyStmt = $pdo->prepare("
    SELECT rh.*, CONCAT(u.first_name, ' ', u.last_name) as updated_by_name
    FROM result_history rh
    JOIN users u ON rh.updated_by = u.id
    WHERE rh.result_id = ?
    ORDER BY rh.updated_at DESC
");
$historyStmt->execute([$result_id]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get student's other results for the same exam type and term
$otherResultsStmt = $pdo->prepare("
    SELECT r.*, sub.subject_name, sub.subject_code,
           CASE 
                WHEN r.score >= 80 THEN 'A'
                WHEN r.score >= 70 THEN 'B'
                WHEN r.score >= 60 THEN 'C'
                WHEN r.score >= 50 THEN 'D'
                WHEN r.score >= 40 THEN 'E'
                ELSE 'F'
           END as grade
    FROM results r
    JOIN subjects sub ON r.subject_id = sub.id
    WHERE r.student_id = ? 
    AND r.exam_type = ? 
    AND r.term = ? 
    AND r.academic_year = ?
    AND r.id != ?
    ORDER BY sub.subject_name
");
$otherResultsStmt->execute([
    $result['student_id'], 
    $result['exam_type'], 
    $result['term'], 
    $result['academic_year'],
    $result_id
]);
$other_results = $otherResultsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate student's average for this exam period
$avgStmt = $pdo->prepare("
    SELECT AVG(score) as average_score, COUNT(*) as subject_count
    FROM results
    WHERE student_id = ? 
    AND exam_type = ? 
    AND term = ? 
    AND academic_year = ?
");
$avgStmt->execute([
    $result['student_id'], 
    $result['exam_type'], 
    $result['term'], 
    $result['academic_year']
]);
$student_average = $avgStmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">View Result Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Results
                        </a>
                        <?php if (in_array($user_role, ['admin', 'principal', 'hod', 'teacher'])): ?>
                            <a href="edit.php?id=<?php echo $result_id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                        <?php if (in_array($user_role, ['admin', 'principal', 'hod', 'teacher'])): ?>
                            <a href="delete.php?id=<?php echo $result_id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this result?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        <?php endif; ?>
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

            <!-- Student Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-graduate"></i> Student Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center">
                            <img src="<?php echo htmlspecialchars($photo_path); ?>" 
                                 alt="Student Photo" class="img-thumbnail" style="max-width: 120px;">
                        </div>
                        <div class="col-md-10">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Student Name:</strong><br>
                                    <span class="text-muted"><?php echo htmlspecialchars($result['last_name'] . ' ' . $result['first_name']); ?></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Student ID:</strong><br>
                                    <span class="text-muted"><?php echo htmlspecialchars($result['student_code']); ?></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Gender:</strong><br>
                                    <span class="text-muted"><?php echo ucfirst($result['gender']); ?></span>
                                </div>
                                <div class="col-md-2">
                                    <strong>Class:</strong><br>
                                    <span class="text-muted"><?php echo htmlspecialchars($result['class_name']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> Result Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Subject:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($result['subject_name'] . ' (' . $result['subject_code'] . ')'); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Exam Type:</strong><br>
                            <span class="text-muted"><?php echo ucfirst($result['exam_type']); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Term:</strong><br>
                            <span class="text-muted"><?php echo ucfirst($result['term']); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Academic Year:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($result['academic_year']); ?></span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Score:</strong><br>
                            <span class="text-primary fw-bold fs-4"><?php echo number_format($result['score'], 0); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Grade:</strong><br>
                            <span class="badge bg-<?php 
                                echo $grade === 'A' ? 'success' : 
                                    ($grade === 'B' ? 'primary' : 
                                        ($grade === 'C' ? 'warning' : 
                                            ($grade === 'D' ? 'info' : 
                                                ($grade === 'E' ? 'secondary' : 'danger')))); 
                            ?> fs-5">
                                <?php echo $grade; ?>
                            </span>
                            <small class="text-muted">(<?php echo $grade_description; ?>)</small>
                        </div>
                        <div class="col-md-3">
                            <strong>Entered By:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($result['teacher_name']); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Date Entered:</strong><br>
                            <span class="text-muted"><?php echo date('M d, Y H:i', strtotime($result['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($result['comments']): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <strong>Teacher's Comments:</strong><br>
                            <div class="alert alert-light">
                                <?php echo nl2br(htmlspecialchars($result['comments'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Student Performance Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Student Performance Summary for <?php echo ucfirst($result['term']) . ' ' . $result['academic_year']; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Current Average:</strong><br>
                            <span class="text-primary fw-bold fs-5">
                                <?php echo number_format($student_average['average_score'], 1); ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Total Subjects:</strong><br>
                            <span class="text-muted fs-5"><?php echo $student_average['subject_count']; ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>This Subject Rank:</strong><br>
                            <span class="text-muted fs-5">
                                <?php
                                // Calculate rank for this subject
                                $rank = 1;
                                foreach ($other_results as $other) {
                                    if ($other['score'] > $result['score']) {
                                        $rank++;
                                    }
                                }
                                echo $rank;
                                ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Performance:</strong><br>
                            <span class="badge bg-<?php 
                                echo $result['score'] >= 70 ? 'success' : 
                                    ($result['score'] >= 60 ? 'warning' : 'danger'); 
                            ?>">
                                <?php 
                                echo $result['score'] >= 70 ? 'Excellent' : 
                                    ($result['score'] >= 60 ? 'Good' : 'Needs Improvement'); 
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other Results for Same Exam Period -->
            <?php if (!empty($other_results)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Other Results for Same Exam Period
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Score</th>
                                    <th>Grade</th>
                                    <th>Teacher</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($other_results as $other): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($other['subject_name']); ?></td>
                                    <td><?php echo number_format($other['score'], 0); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $other['grade'] === 'A' ? 'success' : 
                                                ($other['grade'] === 'B' ? 'primary' : 
                                                    ($other['grade'] === 'C' ? 'warning' : 
                                                        ($other['grade'] === 'D' ? 'info' : 
                                                            ($other['grade'] === 'E' ? 'secondary' : 'danger')))); 
                                        ?>">
                                            <?php echo $other['grade']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['teacher_name']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $other['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Result History -->
            <?php if (!empty($history)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Result Update History
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date Updated</th>
                                    <th>Previous Score</th>
                                    <th>New Score</th>
                                    <th>Updated By</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $hist): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($hist['updated_at'])); ?></td>
                                    <td><?php echo number_format($hist['old_score'], 0); ?></td>
                                    <td><?php echo number_format($hist['new_score'], 0); ?></td>
                                    <td><?php echo htmlspecialchars($hist['updated_by_name']); ?></td>
                                    <td><?php echo htmlspecialchars($hist['reason'] ?: 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Audit Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Audit Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Created By:</strong> <?php echo htmlspecialchars($result['created_by_name']); ?><br>
                            <strong>Created At:</strong> <?php echo date('M d, Y H:i:s', strtotime($result['created_at'])); ?>
                        </div>
                        <?php if ($result['updated_at']): ?>
                        <div class="col-md-6">
                            <strong>Last Updated By:</strong> <?php echo htmlspecialchars($result['updated_by_name']); ?><br>
                            <strong>Last Updated At:</strong> <?php echo date('M d, Y H:i:s', strtotime($result['updated_at'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>