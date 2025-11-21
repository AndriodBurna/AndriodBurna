<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod']);

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

// Get student data
$stmt = $pdo->prepare("
    SELECT s.*, u.username, u.email as user_email, 
           c.id as current_class_id, c.class_name, c.class_code,
           sc.academic_term_id
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

// Get classes for dropdown
$classesStmt = $pdo->query("SELECT id, class_name, class_code FROM classes WHERE is_active = 1 ORDER BY class_name");
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get current academic term
$currentTerm = getCurrentAcademicTerm($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request token. Please try again.';
    } else {
        // Sanitize and validate input
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $dateOfBirth = sanitizeInput($_POST['date_of_birth']);
        $gender = sanitizeInput($_POST['gender']);
        $address = sanitizeInput($_POST['address']);
        $parentGuardianName = sanitizeInput($_POST['parent_guardian_name']);
        $parentGuardianPhone = sanitizeInput($_POST['parent_guardian_phone']);
        $parentGuardianEmail = sanitizeInput($_POST['parent_guardian_email']);
        $medicalInfo = sanitizeInput($_POST['medical_info']);
        $classId = isset($_POST['class_id']) ? (int)$_POST['class_id'] : null;
        $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
        
        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($parentGuardianName)) {
            $error = 'Please fill in all required fields.';
        } elseif (!empty($parentGuardianEmail) && !validateEmail($parentGuardianEmail)) {
            $error = 'Please enter a valid parent/guardian email address.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Handle photo upload
                $photoName = $student['photo']; // Keep existing photo by default
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadFile($_FILES['photo'], '../uploads/students/');
                    if ($uploadResult['success']) {
                        // Delete old photo if exists
                        if ($student['photo'] && file_exists('../uploads/students/' . $student['photo'])) {
                            unlink('../uploads/students/' . $student['photo']);
                        }
                        $photoName = $uploadResult['file_name'];
                    } else {
                        throw new Exception($uploadResult['message']);
                    }
                }
                
                // Update user account
                $userStmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
                $userStmt->execute([$firstName, $lastName, $student['user_id']]);
                
                // Update student record
                $studentStmt = $pdo->prepare("
                    UPDATE students 
                    SET first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, photo = ?, 
                        address = ?, parent_guardian_name = ?, parent_guardian_phone = ?, 
                        parent_guardian_email = ?, medical_info = ?, status = ?
                    WHERE id = ?
                ");
                
                $studentStmt->execute([
                    $firstName, $lastName, $dateOfBirth, $gender, $photoName, 
                    $address, $parentGuardianName, $parentGuardianPhone, $parentGuardianEmail, 
                    $medicalInfo, $status, $studentId
                ]);
                
                // Handle class assignment
                if ($classId && $currentTerm) {
                    // Remove current class assignment
                    $removeStmt = $pdo->prepare("UPDATE student_classes SET is_current = 0 WHERE student_id = ?");
                    $removeStmt->execute([$studentId]);
                    
                    // Add new class assignment
                    $classStmt = $pdo->prepare("
                        INSERT INTO student_classes (student_id, class_id, academic_term_id, is_current) 
                        VALUES (?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE is_current = 1
                    ");
                    $classStmt->execute([$studentId, $classId, $currentTerm['id']]);
                }
                
                $pdo->commit();
                
                logAction(getCurrentUserId(), 'update_student', 'students', $studentId);
                
                $success = "Student updated successfully!";
                
                // Refresh student data
                $stmt->execute([$studentId]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error updating student: " . $e->getMessage();
            }
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
                <h1 class="h2">Edit Student: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
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

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Student Photo</h5>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($student['photo']): ?>
                                <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                                     alt="Student Photo" class="img-fluid rounded mb-3" style="max-height: 200px;">
                            <?php else: ?>
                                <div class="bg-light rounded p-4 mb-3">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <p class="text-muted">Student ID: <strong><?php echo htmlspecialchars($student['student_id']); ?></strong></p>
                            <p class="text-muted">Status: 
                                <span class="badge bg-<?php echo ($student['status'] === 'active') ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-edit"></i> Edit Student Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="studentForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Personal Information</h6>
                                        
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name *</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" required
                                                   value="<?php echo htmlspecialchars($student['first_name']); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name *</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" required
                                                   value="<?php echo htmlspecialchars($student['last_name']); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                                   value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="gender" class="form-label">Gender</label>
                                            <select class="form-select" id="gender" name="gender">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php echo ($student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo ($student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?php echo ($student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="photo" class="form-label">Change Photo</label>
                                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                            <div class="form-text">Leave blank to keep current photo</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Parent/Guardian Information</h6>
                                        
                                        <div class="mb-3">
                                            <label for="parent_guardian_name" class="form-label">Parent/Guardian Name *</label>
                                            <input type="text" class="form-control" id="parent_guardian_name" name="parent_guardian_name" required
                                                   value="<?php echo htmlspecialchars($student['parent_guardian_name']); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="parent_guardian_phone" class="form-label">Parent/Guardian Phone</label>
                                            <input type="tel" class="form-control" id="parent_guardian_phone" name="parent_guardian_phone"
                                                   value="<?php echo htmlspecialchars($student['parent_guardian_phone'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="parent_guardian_email" class="form-label">Parent/Guardian Email</label>
                                            <input type="email" class="form-control" id="parent_guardian_email" name="parent_guardian_email"
                                                   value="<?php echo htmlspecialchars($student['parent_guardian_email'] ?? ''); ?>">
                                        </div>
                                        
                                        <h6 class="text-primary mb-3 mt-4">Class Assignment</h6>
                                        
                                        <div class="mb-3">
                                            <label for="class_id" class="form-label">Current Class</label>
                                            <select class="form-select" id="class_id" name="class_id">
                                                <option value="">No Class Assigned</option>
                                                <?php foreach ($classes as $class): ?>
                                                <option value="<?php echo $class['id']; ?>" 
                                                        <?php echo ($student['current_class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['class_code'] . ')'); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if ($student['class_name']): ?>
                                                <div class="form-text">Currently in: <?php echo htmlspecialchars($student['class_name']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h6 class="text-primary mb-3 mt-4">Medical Information</h6>
                                        
                                        <div class="mb-3">
                                            <label for="medical_info" class="form-label">Medical Conditions/Allergies</label>
                                            <textarea class="form-control" id="medical_info" name="medical_info" rows="3" 
                                                      placeholder="Enter any medical conditions, allergies, or special requirements"><?php echo htmlspecialchars($student['medical_info'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="active" <?php echo ($student['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo ($student['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                <option value="graduated" <?php echo ($student['status'] === 'graduated') ? 'selected' : ''; ?>>Graduated</option>
                                                <option value="transferred" <?php echo ($student['status'] === 'transferred') ? 'selected' : ''; ?>>Transferred</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Student
                                        </button>
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.getElementById('studentForm').addEventListener('submit', function(e) {
    const email = document.getElementById('parent_guardian_email').value;
    if (email && !validateEmail(email)) {
        e.preventDefault();
        showAlert('Please enter a valid email address.', 'error');
        return false;
    }
});

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
</script>