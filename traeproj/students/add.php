<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod']);

$database = new Database();
$pdo = $database->getConnection();

$error = '';
$success = '';

// Get classes for dropdown
$classesStmt = $pdo->query("SELECT id, class_name, class_code FROM classes WHERE is_active = 1 ORDER BY class_name");
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

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
        
        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($parentGuardianName)) {
            $error = 'Please fill in all required fields.';
        } elseif (!empty($parentGuardianEmail) && !validateEmail($parentGuardianEmail)) {
            $error = 'Please enter a valid parent/guardian email address.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Generate student ID
                $studentId = generateStudentID();
                
                // Handle photo upload
                $photoName = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadFile($_FILES['photo'], '../uploads/students/');
                    if ($uploadResult['success']) {
                        $photoName = $uploadResult['file_name'];
                    } else {
                        throw new Exception($uploadResult['message']);
                    }
                }
                
                // Create user account for student
                $username = strtolower($firstName . '.' . $lastName);
                $password = password_hash('student123', PASSWORD_DEFAULT); // Default password
                $email = $username . '@school.local'; // Default email
                
                $userStmt = $pdo->prepare("INSERT INTO users (username, password, email, role, first_name, last_name) VALUES (?, ?, ?, 'student', ?, ?)");
                $userStmt->execute([$username, $password, $email, $firstName, $lastName]);
                $userId = $pdo->lastInsertId();
                
                // Insert student record
                $studentStmt = $pdo->prepare("
                    INSERT INTO students (student_id, user_id, first_name, last_name, date_of_birth, gender, photo, address, 
                                        parent_guardian_name, parent_guardian_phone, parent_guardian_email, medical_info)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $studentStmt->execute([
                    $studentId, $userId, $firstName, $lastName, $dateOfBirth, $gender, $photoName, 
                    $address, $parentGuardianName, $parentGuardianPhone, $parentGuardianEmail, $medicalInfo
                ]);
                
                $studentId = $pdo->lastInsertId();
                
                // Assign to class if selected
                if ($classId) {
                    $currentTerm = getCurrentAcademicTerm($pdo);
                    if ($currentTerm) {
                        $classStmt = $pdo->prepare("INSERT INTO student_classes (student_id, class_id, academic_term_id) VALUES (?, ?, ?)");
                        $classStmt->execute([$studentId, $classId, $currentTerm['id']]);
                    }
                }
                
                $pdo->commit();
                
                logAction(getCurrentUserId(), 'create_student', 'students', $studentId);
                
                $success = "Student added successfully! Student ID: {$studentId}";
                
                // Redirect after success
                $_SESSION['success_message'] = $success;
                header('Location: index.php');
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error adding student: " . $e->getMessage();
                
                // Delete uploaded photo if student creation failed
                if ($photoName && file_exists('../uploads/students/' . $photoName)) {
                    unlink('../uploads/students/' . $photoName);
                }
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
                <h1 class="h2">Add New Student</h1>
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

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Student Information</h5>
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
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Student Photo</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                    <div class="form-text">Accepted formats: JPG, JPEG, PNG, GIF. Max size: 5MB</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Parent/Guardian Information</h6>
                                
                                <div class="mb-3">
                                    <label for="parent_guardian_name" class="form-label">Parent/Guardian Name *</label>
                                    <input type="text" class="form-control" id="parent_guardian_name" name="parent_guardian_name" required
                                           value="<?php echo htmlspecialchars($_POST['parent_guardian_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="parent_guardian_phone" class="form-label">Parent/Guardian Phone</label>
                                    <input type="tel" class="form-control" id="parent_guardian_phone" name="parent_guardian_phone"
                                           value="<?php echo htmlspecialchars($_POST['parent_guardian_phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="parent_guardian_email" class="form-label">Parent/Guardian Email</label>
                                    <input type="email" class="form-control" id="parent_guardian_email" name="parent_guardian_email"
                                           value="<?php echo htmlspecialchars($_POST['parent_guardian_email'] ?? ''); ?>">
                                </div>
                                
                                <h6 class="text-primary mb-3 mt-4">Class Assignment</h6>
                                
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Assign to Class</label>
                                    <select class="form-select" id="class_id" name="class_id">
                                        <option value="">Select Class (Optional)</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                                <?php echo (($_POST['class_id'] ?? '') == $class['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['class_code'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <h6 class="text-primary mb-3 mt-4">Medical Information</h6>
                                
                                <div class="mb-3">
                                    <label for="medical_info" class="form-label">Medical Conditions/Allergies</label>
                                    <textarea class="form-control" id="medical_info" name="medical_info" rows="3" 
                                              placeholder="Enter any medical conditions, allergies, or special requirements"><?php echo htmlspecialchars($_POST['medical_info'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Add Student
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
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