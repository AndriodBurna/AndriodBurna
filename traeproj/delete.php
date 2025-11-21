<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal']);

$database = new Database();
$pdo = $database->getConnection();

$error = '';
$success = '';

// Get class ID from URL
$classId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($classId === 0) {
    $_SESSION['error_message'] = 'Invalid class ID.';
    header('Location: index.php');
    exit();
}

// Get class details
$classStmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name as teacher_name
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

// Check for existing dependencies
$dependencies = [];

// Check for students in this class
$studentsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
$studentsStmt->execute([$classId]);
$studentCount = $studentsStmt->fetch(PDO::FETCH_ASSOC)['count'];
if ($studentCount > 0) {
    $dependencies['students'] = $studentCount;
}

// Check for class subjects
$subjectsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM class_subjects WHERE class_id = ?");
$subjectsStmt->execute([$classId]);
$subjectCount = $subjectsStmt->fetch(PDO::FETCH_ASSOC)['count'];
if ($subjectCount > 0) {
    $dependencies['subjects'] = $subjectCount;
}

// Check for results
$resultsStmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM results r 
    JOIN students s ON r.student_id = s.id 
    WHERE s.class_id = ?
");
$resultsStmt->execute([$classId]);
$resultsCount = $resultsStmt->fetch(PDO::FETCH_ASSOC)['count'];
if ($resultsCount > 0) {
    $dependencies['results'] = $resultsCount;
}

// Check for attendance records
$attendanceStmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    WHERE s.class_id = ?
");
$attendanceStmt->execute([$classId]);
$attendanceCount = $attendanceStmt->fetch(PDO::FETCH_ASSOC)['count'];
if ($attendanceCount > 0) {
    $dependencies['attendance'] = $attendanceCount;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request token. Please try again.';
    } elseif (count($dependencies) > 0) {
        $error = 'Cannot delete class with existing dependencies. Please remove all students, subjects, results, and attendance records first.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Delete class
            $deleteStmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $deleteStmt->execute([$classId]);
            
            $pdo->commit();
            
            logAction(getCurrentUserId(), 'delete_class', 'classes', $classId);
            
            $_SESSION['success_message'] = "Class '{$class['class_name']}' deleted successfully!";
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error deleting class: " . $e->getMessage();
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
                <h1 class="h2">Delete Class: <?php echo htmlspecialchars($class['class_name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Classes
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
                <div class="col-lg-8">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Class Deletion</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-warning"></i> Warning!</h6>
                                <p class="mb-0">Deleting a class is a permanent action that cannot be undone. Please review the information below carefully.</p>
                            </div>

                            <h6 class="text-danger mb-3">Class Information:</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Class Name:</strong> <?php echo htmlspecialchars($class['class_name']); ?><br>
                                    <strong>Class Code:</strong> <?php echo htmlspecialchars($class['class_code']); ?><br>
                                    <strong>Class Teacher:</strong> <?php echo $class['teacher_name'] ? htmlspecialchars($class['teacher_name']) : 'Not Assigned'; ?><br>
                                    <strong>Grade Level:</strong> <?php echo htmlspecialchars($class['grade_level'] ?? 'N/A'); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Room Number:</strong> <?php echo htmlspecialchars($class['room_number'] ?? 'N/A'); ?><br>
                                    <strong>Maximum Students:</strong> <?php echo htmlspecialchars($class['max_students'] ?? 'Unlimited'); ?><br>
                                    <strong>Status:</strong> <?php echo $class['is_active'] ? 'Active' : 'Inactive'; ?><br>
                                    <strong>Created:</strong> <?php echo date('M d, Y', strtotime($class['created_at'])); ?>
                                </div>
                            </div>

                            <?php if (count($dependencies) > 0): ?>
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-times-circle"></i> Cannot Delete Class</h6>
                                <p>This class cannot be deleted because it has the following dependencies:</p>
                                <ul class="mb-3">
                                    <?php foreach ($dependencies as $type => $count): ?>
                                    <li><strong><?php echo ucfirst($type); ?>:</strong> <?php echo $count; ?> record(s)</li>
                                    <?php endforeach; ?>
                                </ul>
                                <p class="mb-0">Please remove all dependencies before attempting to delete this class.</p>
                            </div>

                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb"></i> Suggested Actions:</h6>
                                <ol class="mb-0">
                                    <?php if (isset($dependencies['students'])): ?>
                                    <li>Reassign or graduate all students from this class</li>
                                    <?php endif; ?>
                                    <?php if (isset($dependencies['subjects'])): ?>
                                    <li>Remove all subject assignments from this class</li>
                                    <?php endif; ?>
                                    <?php if (isset($dependencies['results'])): ?>
                                    <li>Archive or delete all results for students in this class</li>
                                    <?php endif; ?>
                                    <?php if (isset($dependencies['attendance'])): ?>
                                    <li>Archive or delete all attendance records for students in this class</li>
                                    <?php endif; ?>
                                    <li>Consider changing the class status to "Inactive" instead of deleting</li>
                                </ol>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle"></i> Safe to Delete</h6>
                                <p class="mb-0">This class has no dependencies and can be safely deleted.</p>
                            </div>

                            <form method="POST" id="deleteForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" required>
                                        <label class="form-check-label" for="confirm_delete">
                                            I understand that this action is permanent and cannot be undone.
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete this class? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete Class
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="edit.php?id=<?php echo $classId; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-edit"></i> Edit Class
                                </a>
                                <a href="view.php?id=<?php echo $classId; ?>" class="btn btn-outline-info">
                                    <i class="fas fa-eye"></i> View Class Profile
                                </a>
                                <?php if ($class['is_active']): ?>
                                <button type="button" class="btn btn-outline-warning" onclick="toggleClassStatus(<?php echo $classId; ?>, false)">
                                    <i class="fas fa-pause"></i> Deactivate Class
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-outline-success" onclick="toggleClassStatus(<?php echo $classId; ?>, true)">
                                    <i class="fas fa-play"></i> Activate Class
                                </button>
                                <?php endif; ?>
                                <a href="../students/add.php?class_id=<?php echo $classId; ?>" class="btn btn-outline-success">
                                    <i class="fas fa-user-plus"></i> Add Student to Class
                                </a>
                                <a href="../subjects/assign.php?class_id=<?php echo $classId; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-book"></i> Manage Subjects
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if (count($dependencies) > 0): ?>
                    <div class="card mt-3">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Dependencies</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-2">
                                    <div class="border rounded p-2">
                                        <h5 class="text-primary mb-1"><?php echo $dependencies['students'] ?? 0; ?></h5>
                                        <small class="text-muted">Students</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="border rounded p-2">
                                        <h5 class="text-info mb-1"><?php echo $dependencies['subjects'] ?? 0; ?></h5>
                                        <small class="text-muted">Subjects</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2">
                                        <h5 class="text-warning mb-1"><?php echo $dependencies['results'] ?? 0; ?></h5>
                                        <small class="text-muted">Results</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2">
                                        <h5 class="text-danger mb-1"><?php echo $dependencies['attendance'] ?? 0; ?></h5>
                                        <small class="text-muted">Attendance</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function toggleClassStatus(classId, activate) {
    const action = activate ? 'activate' : 'deactivate';
    const message = activate ? 'Are you sure you want to activate this class?' : 'Are you sure you want to deactivate this class?';
    
    if (confirm(message)) {
        $.ajax({
            url: 'toggle_status.php',
            type: 'POST',
            data: {
                id: classId,
                action: action,
                csrf_token: '<?php echo generateCSRFToken(); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(response.message, 'error');
                }
            },
            error: function() {
                showAlert('An error occurred while updating class status.', 'error');
            }
        });
    }
}

// Form validation
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    if (!document.getElementById('confirm_delete').checked) {
        e.preventDefault();
        showAlert('Please confirm that you understand this action is permanent.', 'error');
        return false;
    }
    
    if (!confirm('Are you absolutely sure you want to delete this class? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});
</script>