<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod']);

$database = new Database();
$pdo = $database->getConnection();

$errors = [];
$success = false;

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

        // Check if subject code already exists
        if (empty($errors)) {
            $checkStmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ?");
            $checkStmt->execute([$subject_code]);
            if ($checkStmt->fetch()) {
                $errors[] = 'A subject with this code already exists.';
            }
        }

        // Check if subject name already exists
        if (empty($errors)) {
            $checkStmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_name = ?");
            $checkStmt->execute([$subject_name]);
            if ($checkStmt->fetch()) {
                $errors[] = 'A subject with this name already exists.';
            }
        }

        // If no errors, insert the subject
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, description, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$subject_name, $subject_code, $description, $is_active]);
                
                $subjectId = $pdo->lastInsertId();
                
                // Log the action
                logAction('SUBJECT_CREATED', "Created subject: $subject_name ($subject_code)", $subjectId);
                
                $_SESSION['success_message'] = 'Subject created successfully!';
                header('Location: index.php');
                exit;
                
            } catch (PDOException $e) {
                $errors[] = 'Error creating subject: ' . $e->getMessage();
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
                <h1 class="h2">Add New Subject</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
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

            <!-- Subject Form -->
            <div class="card">
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
                                           value="<?php echo htmlspecialchars($_POST['subject_name'] ?? ''); ?>" 
                                           required maxlength="100">
                                    <div class="form-text">Enter the full name of the subject (e.g., Mathematics, English Literature)</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="subject_code" name="subject_code" 
                                           value="<?php echo htmlspecialchars($_POST['subject_code'] ?? ''); ?>" 
                                           required maxlength="10" pattern="[A-Za-z0-9]{2,10}">
                                    <div class="form-text">Unique code for the subject (2-10 alphanumeric characters, e.g., MATH, ENG101)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" maxlength="500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <div class="form-text">Brief description of the subject (optional, max 500 characters)</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               value="1" <?php echo isset($_POST['is_active']) || !isset($_POST['subject_name']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active Subject
                                        </label>
                                    </div>
                                    <div class="form-text">Active subjects can be assigned to classes and used for results entry</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Subject
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                            <a href="index.php" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Subject Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Subject Name:</strong> Use clear, descriptive names that students and parents will understand</li>
                        <li><strong>Subject Code:</strong> Keep codes short, consistent, and easy to remember</li>
                        <li><strong>Active Status:</strong> Only active subjects can be assigned to classes and used for results</li>
                        <li><strong>Assignments:</strong> After creating a subject, you can assign it to classes and teachers</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
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