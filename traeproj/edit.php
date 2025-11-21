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

// Get available teachers for class teacher assignment
$teachersStmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email 
    FROM users u 
    WHERE u.role = 'teacher' AND u.is_active = 1 
    ORDER BY u.first_name, u.last_name
");
$teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get current students in this class
$studentsStmt = $pdo->prepare("
    SELECT s.id, s.student_id, s.first_name, s.last_name, s.gender, s.date_of_birth
    FROM students s
    WHERE s.class_id = ? AND s.status = 'active'
    ORDER BY s.first_name, s.last_name
");
$studentsStmt->execute([$classId]);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request token. Please try again.';
    } else {
        // Sanitize and validate input
        $className = sanitizeInput($_POST['class_name']);
        $classCode = sanitizeInput($_POST['class_code']);
        $description = sanitizeInput($_POST['description']);
        $classTeacherId = !empty($_POST['class_teacher_id']) ? (int)$_POST['class_teacher_id'] : null;
        $maxStudents = isset($_POST['max_students']) ? (int)$_POST['max_students'] : null;
        $roomNumber = sanitizeInput($_POST['room_number']);
        $gradeLevel = sanitizeInput($_POST['grade_level']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if (empty($className) || empty($classCode)) {
            $error = 'Please fill in all required fields (Class Name and Class Code).';
        } elseif (!preg_match('/^[A-Z0-9-]+$/', $classCode)) {
            $error = 'Class code can only contain uppercase letters, numbers, and hyphens.';
        } else {
            try {
                // Check if class code already exists (excluding current class)
                $checkStmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = ? AND id != ?");
                $checkStmt->execute([$classCode, $classId]);
                if ($checkStmt->fetch()) {
                    $error = 'A class with this code already exists. Please choose a different code.';
                } else {
                    $pdo->beginTransaction();
                    
                    // Update class
                    $stmt = $pdo->prepare("
                        UPDATE classes 
                        SET class_name = ?, class_code = ?, description = ?, class_teacher_id = ?, 
                            max_students = ?, room_number = ?, grade_level = ?, is_active = ?, 
                            updated_at = NOW(), updated_by = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $className, $classCode, $description, $classTeacherId, 
                        $maxStudents, $roomNumber, $gradeLevel, $isActive, 
                        getCurrentUserId(), $classId
                    ]);
                    
                    $pdo->commit();
                    
                    logAction(getCurrentUserId(), 'update_class', 'classes', $classId);
                    
                    $success = "Class updated successfully!";
                    
                    // Refresh class data
                    $classStmt->execute([$classId]);
                    $class = $classStmt->fetch(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Error updating class: " . $e->getMessage();
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
                <h1 class="h2">Edit Class: <?php echo htmlspecialchars($class['class_name']); ?></h1>
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

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-edit"></i> Class Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="classForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Basic Information</h6>
                                        
                                        <div class="mb-3">
                                            <label for="class_name" class="form-label">Class Name *</label>
                                            <input type="text" class="form-control" id="class_name" name="class_name" required
                                                   value="<?php echo htmlspecialchars($_POST['class_name'] ?? $class['class_name']); ?>"
                                                   placeholder="e.g., Senior One, Grade 10, Form 4">
                                            <div class="form-text">Enter the full name of the class.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="class_code" class="form-label">Class Code *</label>
                                            <input type="text" class="form-control" id="class_code" name="class_code" required
                                                   value="<?php echo htmlspecialchars($_POST['class_code'] ?? $class['class_code']); ?>"
                                                   placeholder="e.g., S1, G10, F4" maxlength="10">
                                            <div class="form-text">Unique code for the class (uppercase letters, numbers, and hyphens only).</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                      placeholder="Brief description of the class"><?php echo htmlspecialchars($_POST['description'] ?? $class['description']); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="grade_level" class="form-label">Grade Level</label>
                                            <input type="text" class="form-control" id="grade_level" name="grade_level"
                                                   value="<?php echo htmlspecialchars($_POST['grade_level'] ?? $class['grade_level']); ?>"
                                                   placeholder="e.g., 10, 11, 12">
                                            <div class="form-text">Numeric grade level if applicable.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Class Details</h6>
                                        
                                        <div class="mb-3">
                                            <label for="class_teacher_id" class="form-label">Class Teacher</label>
                                            <select class="form-select" id="class_teacher_id" name="class_teacher_id">
                                                <option value="">Select Class Teacher (Optional)</option>
                                                <?php foreach ($teachers as $teacher): ?>
                                                <option value="<?php echo $teacher['id']; ?>" 
                                                        <?php echo (($_POST['class_teacher_id'] ?? $class['class_teacher_id']) == $teacher['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['email'] . ')'); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">The teacher responsible for this class.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="max_students" class="form-label">Maximum Students</label>
                                            <input type="number" class="form-control" id="max_students" name="max_students"
                                                   value="<?php echo htmlspecialchars($_POST['max_students'] ?? $class['max_students']); ?>"
                                                   placeholder="e.g., 40" min="1" max="100">
                                            <div class="form-text">Maximum number of students allowed in this class.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="room_number" class="form-label">Room Number</label>
                                            <input type="text" class="form-control" id="room_number" name="room_number"
                                                   value="<?php echo htmlspecialchars($_POST['room_number'] ?? $class['room_number']); ?>"
                                                   placeholder="e.g., 101, Lab-1">
                                            <div class="form-text">Classroom or room number where this class is held.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                                       <?php echo isset($_POST['is_active']) ? (($_POST['is_active'] == 1) ? 'checked' : '') : ($class['is_active'] == 1 ? 'checked' : ''); ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Active Class
                                                </label>
                                            </div>
                                            <div class="form-text">Active classes can be assigned students and subjects.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Class
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
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Class Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted">Current Students:</span>
                                        <span class="badge bg-primary"><?php echo count($students); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted">Maximum Capacity:</span>
                                        <span class="badge bg-secondary"><?php echo $class['max_students'] ?? 'Unlimited'; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted">Class Status:</span>
                                        <span class="badge bg-<?php echo $class['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $class['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted">Created:</span>
                                        <span class="text-muted small"><?php echo date('M d, Y', strtotime($class['created_at'])); ?></span>
                                    </div>
                                    <?php if ($class['updated_at']): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Last Updated:</span>
                                        <span class="text-muted small"><?php echo date('M d, Y', strtotime($class['updated_at'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (count($students) > 0): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-users"></i> Current Students</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($students as $student): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></small>
                                    </div>
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($student['gender']); ?></span>
                                </div>
                                <?php endforeach; ?>
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
document.getElementById('classForm').addEventListener('submit', function(e) {
    const classCode = document.getElementById('class_code').value;
    
    // Validate class code format
    if (!/^[A-Z0-9-]+$/.test(classCode)) {
        e.preventDefault();
        showAlert('Class code can only contain uppercase letters, numbers, and hyphens.', 'error');
        return false;
    }
    
    // Validate max students
    const maxStudents = document.getElementById('max_students').value;
    if (maxStudents && (maxStudents < 1 || maxStudents > 100)) {
        e.preventDefault();
        showAlert('Maximum students must be between 1 and 100.', 'error');
        return false;
    }
});

// Auto-convert class code to uppercase
$('#class_code').on('input', function() {
    this.value = this.value.toUpperCase();
});
</script>