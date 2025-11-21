<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal']);

$database = new Database();
$pdo = $database->getConnection();

$error = '';
$success = '';

// Get student ID from URL
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($studentId === 0) {
    $_SESSION['error_message'] = 'Invalid student ID.';
    header('Location: index.php');
    exit();
}

// Get student data for confirmation
$stmt = $pdo->prepare("
    SELECT s.*, u.username, u.email as user_email,
           c.class_name, c.class_code
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_classes sc ON s.id = sc.student_id AND sc.is_current = 1
    LEFT JOIN classes c ON sc.class_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error_message'] = 'Student not found.';
    header('Location: index.php');
    exit();
}

// Check if student has any results or attendance records
$checkStmt = $pdo->prepare("
    SELECT COUNT(*) as result_count FROM results WHERE student_id = ?
    UNION ALL
    SELECT COUNT(*) FROM attendance WHERE student_id = ?
    UNION ALL
    SELECT COUNT(*) FROM student_classes WHERE student_id = ?
");
$checkStmt->execute([$studentId, $studentId, $studentId]);
$records = $checkStmt->fetchAll(PDO::FETCH_COLUMN);

$hasResults = $records[0] > 0;
$hasAttendance = $records[1] > 0;
$hasClassHistory = $records[2] > 0;

$canDelete = !$hasResults && !$hasAttendance;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request token. Please try again.';
    } elseif (!$canDelete) {
        $error = 'Cannot delete student with existing academic records. Please contact the system administrator.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Delete student's photo if exists
            if ($student['photo'] && file_exists('../uploads/students/' . $student['photo'])) {
                unlink('../uploads/students/' . $student['photo']);
            }
            
            // Delete student_classes records (if any)
            $deleteClassesStmt = $pdo->prepare("DELETE FROM student_classes WHERE student_id = ?");
            $deleteClassesStmt->execute([$studentId]);
            
            // Delete student record
            $deleteStudentStmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $deleteStudentStmt->execute([$studentId]);
            
            // Delete user account
            $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $deleteUserStmt->execute([$student['user_id']]);
            
            $pdo->commit();
            
            logAction(getCurrentUserId(), 'delete_student', 'students', $studentId);
            
            $_SESSION['success_message'] = 'Student deleted successfully!';
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error deleting student: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Delete Student</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Students
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Student Deletion</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-danger">
                                <strong>Warning:</strong> This action cannot be undone. Please review the student information carefully before proceeding.
                            </p>
                            
                            <div class="alert alert-info">
                                <h6>Student Details:</h6>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                                <p><strong>Current Class:</strong> 
                                    <?php echo $student['class_name'] ? htmlspecialchars($student['class_name'] . ' (' . $student['class_code'] . ')') : 'Not Assigned'; ?>
                                </p>
                                <p><strong>Parent/Guardian:</strong> <?php echo htmlspecialchars($student['parent_guardian_name']); ?></p>
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
                            </div>
                            
                            <?php if (!$canDelete): ?>
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-info-circle"></i> Deletion Restricted</h6>
                                    <p>This student cannot be deleted because they have the following academic records:</p>
                                    <ul>
                                        <?php if ($hasResults): ?>
                                            <li>Academic results recorded</li>
                                        <?php endif; ?>
                                        <?php if ($hasAttendance): ?>
                                            <li>Attendance records</li>
                                        <?php endif; ?>
                                        <?php if ($hasClassHistory): ?>
                                            <li>Class assignment history</li>
                                        <?php endif; ?>
                                    </ul>
                                    <p class="mb-0"><strong>Recommended Action:</strong> Change student status to "Inactive" or "Transferred" instead of deleting.</p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Important Information</h6>
                                    <p>This student has no academic records and can be safely deleted. The following will be permanently removed:</p>
                                    <ul>
                                        <li>Student profile and personal information</li>
                                        <li>User account and login credentials</li>
                                        <li>Student photo (if uploaded)</li>
                                        <li>Any class assignments</li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" id="deleteForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="confirm_delete" value="1">
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirmCheckbox" required>
                                        <label class="form-check-label" for="confirmCheckbox">
                                            I understand that this action is permanent and cannot be undone.
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-danger" <?php echo !$canDelete ? 'disabled' : ''; ?>>
                                        <i class="fas fa-trash"></i> Delete Student
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Alternative Actions</h5>
                        </div>
                        <div class="card-body">
                            <p>If you want to remove this student from active records but preserve their academic history, consider these alternatives:</p>
                            
                            <div class="list-group">
                                <a href="edit.php?id=<?php echo $studentId; ?>" class="list-group-item list-group-item-action">
                                    <h6><i class="fas fa-edit"></i> Edit Student Status</h6>
                                    <p class="mb-0 small">Change status to "Inactive", "Graduated", or "Transferred" to remove from active records while preserving data.</p>
                                </a>
                                
                                <a href="view.php?id=<?php echo $studentId; ?>" class="list-group-item list-group-item-action">
                                    <h6><i class="fas fa-eye"></i> View Student Profile</h6>
                                    <p class="mb-0 small">Review the student's complete profile and academic records before making a decision.</p>
                                </a>
                                
                                <?php if (in_array($currentUser['role'], ['admin'])): ?>
                                <a href="../reports/student_report.php?student_id=<?php echo $studentId; ?>" class="list-group-item list-group-item-action">
                                    <h6><i class="fas fa-file-pdf"></i> Generate Report</h6>
                                    <p class="mb-0 small">Create a comprehensive report of the student's academic performance for records.</p>
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <h6><i class="fas fa-lightbulb"></i> Best Practice</h6>
                                <p class="mb-0 small">For students with academic records, it's recommended to change their status rather than delete them. This preserves the integrity of historical data and reports.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    if (!document.getElementById('confirmCheckbox').checked) {
        e.preventDefault();
        showAlert('Please confirm that you understand this action is permanent.', 'error');
        return false;
    }
    
    if (!confirm('Are you absolutely sure you want to delete this student? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});
</script>