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

// Check if user has permission to delete this result
if ($user_role === 'teacher' && $result['teacher_id'] != $user_id) {
    $_SESSION['error_message'] = 'You can only delete results that you entered.';
    header('Location: index.php');
    exit;
}

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token. Please try again.';
    } else {
        try {
            // Delete the result
            $deleteStmt = $pdo->prepare("DELETE FROM results WHERE id = ?");
            $deleteStmt->execute([$result_id]);
            
            logAction('RESULT_DELETED', "Deleted result for {$result['last_name']} {$result['first_name']} in {$result['subject_name']} (Score: {$result['score']})");
            
            $_SESSION['success_message'] = 'Result deleted successfully!';
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Error deleting result: ' . $e->getMessage();
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
                <h1 class="h2">Delete Result</h1>
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
                    <strong>Error:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Warning Card -->
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Warning: Permanent Deletion
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-danger">
                        <strong>This action cannot be undone.</strong> Once you delete this result, it will be permanently removed from the system and cannot be recovered.
                    </p>
                    <p>
                        Please consider the following alternatives before proceeding:
                    </p>
                    <ul>
                        <li><strong>Edit the result:</strong> If you need to correct the score or other details, consider editing instead of deleting.</li>
                        <li><strong>Contact an administrator:</strong> If you're unsure about deleting this result, consult with your administrator.</li>
                        <li><strong>Document the reason:</strong> Make sure you have a valid reason for deleting this result.</li>
                    </ul>
                </div>
            </div>

            <!-- Result Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Result Details to be Deleted</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
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
                            <span class="text-primary fw-bold fs-5"><?php echo number_format($result['score'], 0); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Grade:</strong><br>
                            <span class="badge bg-<?php 
                                echo $grade === 'A' ? 'success' : 
                                    ($grade === 'B' ? 'primary' : 
                                        ($grade === 'C' ? 'warning' : 
                                            ($grade === 'D' ? 'info' : 
                                                ($grade === 'E' ? 'secondary' : 'danger')))); 
                            ?> fs-6">
                                <?php echo $grade; ?>
                            </span>
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
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Comments:</strong><br>
                            <div class="alert alert-light">
                                <?php echo nl2br(htmlspecialchars($result['comments'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Confirmation Form -->
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Confirm Deletion</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" required>
                                <label class="form-check-label text-danger" for="confirm_delete">
                                    <strong>I understand that this action is permanent and cannot be undone.</strong>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Deletion (Optional but Recommended)</label>
                            <textarea class="form-control" id="reason" name="reason" rows="2" 
                                      placeholder="Please provide a brief reason for deleting this result..."></textarea>
                            <div class="form-text">This will be logged for audit purposes.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger" onclick="return confirmFinalDeletion()">
                                <i class="fas fa-trash"></i> Delete Result Permanently
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function confirmFinalDeletion() {
    const confirmCheckbox = document.getElementById('confirm_delete');
    
    if (!confirmCheckbox.checked) {
        showAlert('error', 'Please confirm that you understand this action is permanent.');
        return false;
    }
    
    return confirm('Are you absolutely sure you want to delete this result? This action CANNOT be undone.');
}
</script>