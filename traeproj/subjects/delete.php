<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal']);

$database = new Database();
$pdo = $database->getConnection();

$errors = [];
$success = false;

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

// Check for dependencies
$dependencyChecks = [
    'class_subjects' => 'Class assignments',
    'results' => 'Results records',
    'subject_teachers' => 'Teacher assignments'
];

$dependencies = [];
foreach ($dependencyChecks as $table => $description) {
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE subject_id = ?");
    $checkStmt->execute([$subject_id]);
    $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($count > 0) {
        $dependencies[$description] = $count;
    }
}

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token. Please try again.';
    } elseif (!empty($dependencies)) {
        $errors[] = 'Cannot delete subject with existing dependencies. Please remove all assignments and results first.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete the subject
            $deleteStmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            $deleteStmt->execute([$subject_id]);
            
            // Log the action
            logAction('SUBJECT_DELETED', "Deleted subject: {$subject['subject_name']} ({$subject['subject_code']})", $subject_id);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Subject deleted successfully!';
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            // Rollback transaction
            $pdo->rollBack();
            $errors[] = 'Error deleting subject: ' . $e->getMessage();
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Delete Subject</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php?id=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i> View Subject
                    </a>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
                </div>
            </div>

            <!-- Warning Alert -->
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Warning!</strong> You are about to delete a subject. This action cannot be undone.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Deletion failed:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Subject Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Subject to be Deleted</h5>
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

            <!-- Dependencies Check -->
            <?php if (!empty($dependencies)): ?>
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Cannot Delete - Dependencies Found</h5>
                </div>
                <div class="card-body">
                    <p>This subject cannot be deleted because it has the following dependencies:</p>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($dependencies as $type => $count): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($type); ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $count; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Alternative Actions:</strong>
                        <ul class="mb-0">
                            <li><strong>Deactivate the subject:</strong> Go to <a href="edit.php?id=<?php echo $subject_id; ?>" class="alert-link">Edit Subject</a> and set status to "Inactive"</li>
                            <li><strong>Remove dependencies:</strong> Remove all class assignments and results for this subject first</li>
                            <li><strong>Keep the subject:</strong> Consider keeping the subject for historical records</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Deletion Confirmation -->
            <?php if (empty($dependencies)): ?>
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="alert alert-info">
                            <strong>Subject:</strong> <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                            <br><strong>Action:</strong> This subject will be permanently deleted from the system.
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" required>
                            <label class="form-check-label" for="confirm_delete">
                                I understand that this action cannot be undone and I want to proceed with deleting this subject.
                            </label>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger" onclick="return confirmFinalDeletion();">
                                <i class="fas fa-trash"></i> Delete Subject Permanently
                            </button>
                            <a href="view.php?id=<?php echo $subject_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Help Information -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Deletion Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Permanent Action:</strong> Once deleted, subject data cannot be recovered</li>
                        <li><strong>Historical Records:</strong> Consider deactivating instead of deleting for audit purposes</li>
                        <li><strong>Dependencies:</strong> All related data (assignments, results) must be removed first</li>
                        <li><strong>Alternative:</strong> You can deactivate the subject instead of deleting it</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function confirmFinalDeletion() {
    const subjectName = '<?php echo htmlspecialchars(addslashes($subject['subject_name'] . ' (' . $subject['subject_code'] . ')')); ?>';
    
    return confirm(
        'FINAL CONFIRMATION\n\n' +
        'You are about to PERMANENTLY DELETE the following subject:\n' +
        subjectName + '\n\n' +
        'This action CANNOT be undone.\n\n' +
        'Are you absolutely sure you want to proceed?'
    );
}

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    document.getElementById('deleteForm').addEventListener('submit', function(e) {
        const confirmCheckbox = document.getElementById('confirm_delete');
        
        if (!confirmCheckbox.checked) {
            e.preventDefault();
            showAlert('error', 'Please confirm that you understand the deletion cannot be undone.');
            confirmCheckbox.focus();
            return false;
        }
        
        // Additional confirmation
        if (!confirmFinalDeletion()) {
            e.preventDefault();
            return false;
        }
    });
});
</script>