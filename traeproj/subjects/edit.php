<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod']);

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

// Get subject statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT cs.class_id) as total_classes,
        COUNT(DISTINCT cs.teacher_id) as total_teachers,
        COUNT(DISTINCT r.id) as total_results,
        COUNT(DISTINCT cs.id) as total_assignments
    FROM subjects s
    LEFT JOIN class_subjects cs ON s.id = cs.subject_id AND cs.is_active = 1
    LEFT JOIN results r ON s.id = r.subject_id
    WHERE s.id = ?
");
$statsStmt->execute([$subject_id]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get assigned classes and teachers
$assignmentsStmt = $pdo->prepare("
    SELECT cs.id, cs.class_id, cs.teacher_id, cs.is_active,
           c.class_name, c.class_code,
           CONCAT(u.first_name, ' ', u.last_name) as teacher_name
    FROM class_subjects cs
    JOIN classes c ON cs.class_id = c.id
    JOIN users u ON cs.teacher_id = u.id
    WHERE cs.subject_id = ?
    ORDER BY c.class_name, u.last_name
");
$assignmentsStmt->execute([$subject_id]);
$assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token. Please try again.';
    } else {
        // Get and validate form data
        $subject_name = sanitizeInput($_POST['subject_name'] ?? '');
        $subject_code = sanitizeInput($_POST['subject_code'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validate required fields
        if (empty($subject_name)) {
            $errors[] = 'Subject name is required.';
        }

        if (empty($subject_code)) {
            $errors[] = 'Subject code is required.';
        } else {
            // Validate subject code format (alphanumeric, max 10 characters)
            if (!preg_match('/^[A-Za-z0-9]{2,10}$/', $subject_code)) {
                $errors[] = 'Subject code must be 2-10 alphanumeric characters.';
            }
        }

        // Check if subject code already exists (excluding current subject)
        if (empty($errors)) {
            $checkStmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
            $checkStmt->execute([$subject_code, $subject_id]);
            if ($checkStmt->fetch()) {
                $errors[] = 'Another subject with this code already exists.';
            }
        }

        // Check if subject name already exists (excluding current subject)
        if (empty($errors)) {
            $checkStmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_name = ? AND id != ?");
            $checkStmt->execute([$subject_name, $subject_id]);
            if ($checkStmt->fetch()) {
                $errors[] = 'Another subject with this name already exists.';
            }
        }

        // Check if trying to deactivate subject with active assignments
        if (empty($errors) && !$is_active && $subject['is_active']) {
            if ($stats['total_assignments'] > 0) {
                $errors[] = 'Cannot deactivate subject while it has active class assignments. Please remove all assignments first.';
            }
        }

        // If no errors, update the subject
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$subject_name, $subject_code, $description, $is_active, $subject_id]);
                
                // Log the action
                $action = $subject['subject_name'] !== $subject_name ? "Renamed subject from {$subject['subject_name']} to $subject_name" : "Updated subject: $subject_name";
                logAction('SUBJECT_UPDATED', $action, $subject_id);
                
                // Refresh subject data
                $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
                $stmt->execute([$subject_id]);
                $subject = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $_SESSION['success_message'] = 'Subject updated successfully!';
                header('Location: index.php');
                exit;
                
            } catch (PDOException $e) {
                $errors[] = 'Error updating subject: ' . $e->getMessage();
            }
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
                <h1 class="h2">Edit Subject</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="view.php?id=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> View Subject
                        </a>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Subjects
                        </a>
                    </div>
                </div>
            </div>

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

            <!-- Subject Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo $stats['total_classes']; ?></h5>
                            <p class="card-text">Assigned Classes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo $stats['total_teachers']; ?></h5>
                            <p class="card-text">Assigned Teachers</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo $stats['total_results']; ?></h5>
                            <p class="card-text">Results Records</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo $stats['total_assignments']; ?></h5>
                            <p class="card-text">Active Assignments</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Subject Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="subjectForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="subject_name" name="subject_name" 
                                           value="<?php echo htmlspecialchars($_POST['subject_name'] ?? $subject['subject_name']); ?>" 
                                           required maxlength="100">
                                    <div class="form-text">Enter the full name of the subject</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="subject_code" name="subject_code" 
                                           value="<?php echo htmlspecialchars($_POST['subject_code'] ?? $subject['subject_code']); ?>" 
                                           required maxlength="10" pattern="[A-Za-z0-9]{2,10}">
                                    <div class="form-text">Unique code for the subject (2-10 alphanumeric characters)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" maxlength="500"><?php echo htmlspecialchars($_POST['description'] ?? $subject['description']); ?></textarea>
                            <div class="form-text">Brief description of the subject (optional, max 500 characters)</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               value="1" <?php echo (isset($_POST['is_active']) ? $_POST['is_active'] : $subject['is_active']) ? 'checked' : ''; ?>
                                               <?php echo $stats['total_assignments'] > 0 && $subject['is_active'] ? '' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active Subject
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        <?php if ($stats['total_assignments'] > 0 && $subject['is_active']): ?>
                                            <span class="text-warning">Cannot deactivate while assigned to classes</span>
                                        <?php else: ?>
                                            Active subjects can be assigned to classes and used for results entry
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Subject
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset Changes
                            </button>
                            <a href="index.php" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Assignments -->
            <?php if (count($assignments) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Current Class Assignments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Teacher</th>
                                    <th>Status</th>
                                    <th>Actions</th>
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
                                    <td>
                                        <?php echo htmlspecialchars($assignment['teacher_name']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $assignment['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $assignment['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
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
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Help Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Editing Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Subject Code:</strong> Cannot be changed if the subject has results records</li>
                        <li><strong>Active Status:</strong> Cannot deactivate subjects with active class assignments</li>
                        <li><strong>Assignments:</strong> Use the "Assign Subjects" page to manage class assignments</li>
                        <li><strong>Results:</strong> Changes to subject details do not affect existing results</li>
                    </ul>
                </div>
            </div>
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

document.addEventListener('DOMContentLoaded', function() {
    const subjectCodeInput = document.getElementById('subject_code');
    const subjectNameInput = document.getElementById('subject_name');
    
    // Auto-generate subject code from subject name if empty
    subjectNameInput.addEventListener('blur', function() {
        if (subjectCodeInput.value.trim() === '' && subjectNameInput.value.trim() !== '') {
            // Generate code from name (first 3-4 characters, uppercase, no spaces)
            let code = subjectNameInput.value.trim()
                .substring(0, 4)
                .toUpperCase()
                .replace(/[^A-Z]/g, '');
            
            if (code.length < 2) {
                code = subjectNameInput.value.trim()
                    .split(' ')
                    .map(word => word.charAt(0))
                    .join('')
                    .toUpperCase()
                    .substring(0, 4);
            }
            
            subjectCodeInput.value = code;
        }
    });
    
    // Validate subject code format
    subjectCodeInput.addEventListener('input', function() {
        const value = this.value;
        if (value.length > 10) {
            this.value = value.substring(0, 10);
        }
        // Remove non-alphanumeric characters
        this.value = value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
    });
    
    // Form validation
    document.getElementById('subjectForm').addEventListener('submit', function(e) {
        const subjectCode = subjectCodeInput.value.trim();
        const subjectName = subjectNameInput.value.trim();
        
        if (subjectCode.length < 2) {
            e.preventDefault();
            showAlert('error', 'Subject code must be at least 2 characters long.');
            subjectCodeInput.focus();
            return false;
        }
        
        if (subjectName.length < 2) {
            e.preventDefault();
            showAlert('error', 'Subject name must be at least 2 characters long.');
            subjectNameInput.focus();
            return false;
        }
    });
});
</script>