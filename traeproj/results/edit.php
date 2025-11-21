<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher']);

$database = new Database();
$pdo = $database->getConnection();

$errors = [];
$success = false;

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

// Get result details
$resultStmt = $pdo->prepare("
    SELECT r.*, s.student_id as student_code, s.first_name, s.last_name, s.gender,
           c.class_name, c.class_code, sub.subject_name, sub.subject_code,
           CONCAT(u.first_name, ' ', u.last_name) as teacher_name
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN classes c ON r.class_id = c.id
    JOIN subjects sub ON r.subject_id = sub.id
    JOIN users u ON r.teacher_id = u.id
    WHERE r.id = ?
");
$resultStmt->execute([$result_id]);
$result = $resultStmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $_SESSION['error_message'] = 'Result not found.';
    header('Location: index.php');
    exit;
}

// Check if user has permission to edit this result
if ($user_role === 'teacher' && $result['teacher_id'] != $user_id) {
    $_SESSION['error_message'] = 'You can only edit results that you entered.';
    header('Location: index.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token. Please try again.';
    } else {
        // Get form data
        $score = isset($_POST['score']) ? (float)$_POST['score'] : null;
        $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
        
        // Validate score
        if ($score === null || $score === '') {
            $errors[] = 'Score is required.';
        } elseif ($score < 0 || $score > 100) {
            $errors[] = 'Score must be between 0 and 100.';
        }
        
        // If no errors, update the result
        if (empty($errors)) {
            try {
                $updateStmt = $pdo->prepare("
                    UPDATE results 
                    SET score = ?, comments = ?, teacher_id = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$score, $comments, $user_id, $result_id]);
                
                logAction('RESULT_UPDATED', "Updated result for {$result['last_name']} {$result['first_name']} in {$result['subject_name']} (Score: {$score})");
                
                $_SESSION['success_message'] = 'Result updated successfully!';
                header('Location: index.php');
                exit;
                
            } catch (PDOException $e) {
                $errors[] = 'Error updating result: ' . $e->getMessage();
            }
        }
    }
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

$grade = getGrade($result['score']);

// Generate CSRF token
$csrf_token = generateCSRFToken();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Result</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Results
                    </a>
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

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Result Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Result Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Student:</strong><br>
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
                        <div class="col-md-3">
                            <strong>Class:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($result['class_name'] . ' (' . $result['class_code'] . ')'); ?></span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
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
                    <hr>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Entered By:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($result['teacher_name']); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Date Entered:</strong><br>
                            <span class="text-muted"><?php echo date('M d, Y H:i', strtotime($result['created_at'])); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Last Updated:</strong><br>
                            <span class="text-muted"><?php echo date('M d, Y H:i', strtotime($result['updated_at'])); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong><br>
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Result</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="editResultForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="score" class="form-label">Score (0-100) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="score" name="score" 
                                           value="<?php echo isset($_POST['score']) ? htmlspecialchars($_POST['score']) : $result['score']; ?>" 
                                           min="0" max="100" step="0.1" required
                                           onchange="updateGrade()">
                                    <div class="form-text">Enter a score between 0 and 100</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="grade_display" class="form-label">Grade</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <span class="badge bg-<?php 
                                                echo $grade === 'A' ? 'success' : 
                                                    ($grade === 'B' ? 'primary' : 
                                                        ($grade === 'C' ? 'warning' : 
                                                            ($grade === 'D' ? 'info' : 
                                                                ($grade === 'E' ? 'secondary' : 'danger')))); 
                                            ?>">
                                                <?php echo $grade; ?>
                                            </span>
                                        </span>
                                        <input type="text" class="form-control" id="grade_display" value="<?php echo getGradeDescription($grade); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comments" class="form-label">Comments (Optional)</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3" 
                                      placeholder="Enter any additional comments about this result..."><?php echo isset($_POST['comments']) ? htmlspecialchars($_POST['comments']) : htmlspecialchars($result['comments']); ?></textarea>
                            <div class="form-text">Maximum 500 characters</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                                <i class="fas fa-trash"></i> Delete Result
                            </button>
                            <div class="d-flex gap-2">
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Result
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Result History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Result History</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get previous results for this student in this subject
                    $historyStmt = $pdo->prepare("
                        SELECT r.*, c.class_name, sub.subject_name,
                               CASE 
                                   WHEN r.score >= 80 THEN 'A'
                                   WHEN r.score >= 70 THEN 'B'
                                   WHEN r.score >= 60 THEN 'C'
                                   WHEN r.score >= 50 THEN 'D'
                                   WHEN r.score >= 40 THEN 'E'
                                   ELSE 'F'
                               END as grade
                        FROM results r
                        JOIN classes c ON r.class_id = c.id
                        JOIN subjects sub ON r.subject_id = sub.id
                        WHERE r.student_id = ? AND r.subject_id = ? AND r.id != ?
                        ORDER BY r.academic_year DESC, r.term DESC, r.exam_type
                        LIMIT 10
                    ");
                    $historyStmt->execute([$result['student_id'], $result['subject_id'], $result_id]);
                    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($history) > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Exam Type</th>
                                    <th>Term</th>
                                    <th>Year</th>
                                    <th>Class</th>
                                    <th>Score</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $history_result): ?>
                                <tr>
                                    <td><?php echo ucfirst($history_result['exam_type']); ?></td>
                                    <td><?php echo ucfirst($history_result['term']); ?></td>
                                    <td><?php echo htmlspecialchars($history_result['academic_year']); ?></td>
                                    <td><?php echo htmlspecialchars($history_result['class_name']); ?></td>
                                    <td><?php echo number_format($history_result['score'], 0); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $history_result['grade'] === 'A' ? 'success' : 
                                                ($history_result['grade'] === 'B' ? 'primary' : 
                                                    ($history_result['grade'] === 'C' ? 'warning' : 
                                                        ($history_result['grade'] === 'D' ? 'info' : 
                                                            ($history_result['grade'] === 'E' ? 'secondary' : 'danger')))); 
                                        ?>">
                                            <?php echo $history_result['grade']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($history_result['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <p>No previous results found for this student in this subject.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function updateGrade() {
    const score = parseFloat(document.getElementById('score').value);
    const gradeBadge = document.querySelector('#grade_display .badge');
    const gradeDescription = document.getElementById('grade_display');
    
    if (isNaN(score) || score === '') {
        gradeBadge.innerHTML = '-';
        gradeBadge.className = 'badge';
        gradeDescription.value = '';
    } else {
        let grade, gradeClass, description;
        
        if (score >= 80) {
            grade = 'A';
            gradeClass = 'bg-success';
            description = 'Excellent';
        } else if (score >= 70) {
            grade = 'B';
            gradeClass = 'bg-primary';
            description = 'Very Good';
        } else if (score >= 60) {
            grade = 'C';
            gradeClass = 'bg-warning';
            description = 'Good';
        } else if (score >= 50) {
            grade = 'D';
            gradeClass = 'bg-info';
            description = 'Average';
        } else if (score >= 40) {
            grade = 'E';
            gradeClass = 'bg-secondary';
            description = 'Below Average';
        } else {
            grade = 'F';
            gradeClass = 'bg-danger';
            description = 'Fail';
        }
        
        gradeBadge.innerHTML = grade;
        gradeBadge.className = 'badge ' + gradeClass;
        gradeDescription.value = description;
    }
}

function confirmDelete() {
    confirmAction(
        'Delete Result',
        'Are you sure you want to delete this result? This action cannot be undone.',
        function() {
            window.location.href = 'delete.php?id=<?php echo $result_id; ?>';
        }
    );
}

// Initialize grade on page load
document.addEventListener('DOMContentLoaded', function() {
    updateGrade();
});
</script>

<?php
function getGradeDescription($grade) {
    switch ($grade) {
        case 'A': return 'Excellent';
        case 'B': return 'Very Good';
        case 'C': return 'Good';
        case 'D': return 'Average';
        case 'E': return 'Below Average';
        case 'F': return 'Fail';
        default: return '';
    }
}
?>