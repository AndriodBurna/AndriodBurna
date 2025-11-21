<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod']);

$database = new Database();
$pdo = $database->getConnection();

$errors = [];
$success = false;

// Handle assignment removal
if (isset($_GET['remove'])) {
    $assignment_id = (int)$_GET['remove'];
    
    // Get assignment details for logging
    $stmt = $pdo->prepare("
        SELECT cs.*, s.subject_name, s.subject_code, c.class_name, 
               CONCAT(u.first_name, ' ', u.last_name) as teacher_name
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        JOIN classes c ON cs.class_id = c.id
        JOIN users u ON cs.teacher_id = u.id
        WHERE cs.id = ?
    ");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assignment) {
        try {
            $deleteStmt = $pdo->prepare("DELETE FROM class_subjects WHERE id = ?");
            $deleteStmt->execute([$assignment_id]);
            
            logAction('SUBJECT_ASSIGNMENT_REMOVED', 
                     "Removed {$assignment['subject_name']} assignment from {$assignment['class_name']} (Teacher: {$assignment['teacher_name']})");
            
            $_SESSION['success_message'] = 'Subject assignment removed successfully!';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?subject_id=' . $assignment['subject_id']);
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error removing assignment: ' . $e->getMessage();
            header('Location: ' . $_SERVER['PHP_SELF'] . '?subject_id=' . $assignment['subject_id']);
            exit;
        }
    }
}

// Handle assignment editing
$edit_assignment = null;
if (isset($_GET['edit'])) {
    $assignment_id = (int)$_GET['edit'];
    
    $stmt = $pdo->prepare("
        SELECT cs.*, s.subject_name, s.subject_code, c.class_name,
               CONCAT(u.first_name, ' ', u.last_name) as teacher_name
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        JOIN classes c ON cs.class_id = c.id
        JOIN users u ON cs.teacher_id = u.id
        WHERE cs.id = ?
    ");
    $stmt->execute([$assignment_id]);
    $edit_assignment = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get filters
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// Get subjects for dropdown
$subjectsStmt = $pdo->prepare("SELECT id, subject_name, subject_code FROM subjects WHERE is_active = 1 ORDER BY subject_name");
$subjectsStmt->execute();
$subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get classes for dropdown
$classesStmt = $pdo->prepare("SELECT id, class_name, class_code FROM classes WHERE is_active = 1 ORDER BY class_name");
$classesStmt->execute();
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get teachers for dropdown
$teachersStmt = $pdo->prepare("
    SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as teacher_name, u.email
    FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r ON ur.role_id = r.id
    WHERE r.role_name IN ('teacher', 'hod') AND u.is_active = 1
    ORDER BY u.last_name, u.first_name
");
$teachersStmt->execute();
$teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token. Please try again.';
    } else {
        // Get form data
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $class_id = (int)($_POST['class_id'] ?? 0);
        $teacher_id = (int)($_POST['teacher_id'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validate required fields
        if (empty($subject_id)) {
            $errors[] = 'Please select a subject.';
        }
        if (empty($class_id)) {
            $errors[] = 'Please select a class.';
        }
        if (empty($teacher_id)) {
            $errors[] = 'Please select a teacher.';
        }

        // Check if assignment already exists
        if (empty($errors)) {
            $checkStmt = $pdo->prepare("SELECT id FROM class_subjects WHERE subject_id = ? AND class_id = ? AND teacher_id = ?");
            $checkStmt->execute([$subject_id, $class_id, $teacher_id]);
            if ($checkStmt->fetch()) {
                $errors[] = 'This subject is already assigned to the selected class and teacher.';
            }
        }

        // Check if teacher is already assigned to teach this subject in another class (optional validation)
        if (empty($errors)) {
            $checkStmt = $pdo->prepare("SELECT c.class_name FROM class_subjects cs JOIN classes c ON cs.class_id = c.id WHERE cs.subject_id = ? AND cs.teacher_id = ? AND cs.class_id != ?");
            $checkStmt->execute([$subject_id, $teacher_id, $class_id]);
            $existingAssignment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingAssignment) {
                // This is just a warning, not an error
                $warnings[] = "Note: This teacher is already assigned to teach this subject in {$existingAssignment['class_name']}.";
            }
        }

        // If no errors, create the assignment
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO class_subjects (subject_id, class_id, teacher_id, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$subject_id, $class_id, $teacher_id, $is_active]);
                
                // Get details for logging
                $subjectName = $pdo->prepare("SELECT subject_name, subject_code FROM subjects WHERE id = ?")->execute([$subject_id])->fetch(PDO::FETCH_ASSOC);
                $className = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?")->execute([$class_id])->fetch(PDO::FETCH_ASSOC);
                $teacherName = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id = ?")->execute([$teacher_id])->fetch(PDO::FETCH_ASSOC);
                
                logAction('SUBJECT_ASSIGNED', "Assigned {$subjectName['subject_name']} to {$className['class_name']} (Teacher: {$teacherName['name']})");
                
                $_SESSION['success_message'] = 'Subject assigned successfully!';
                header('Location: ' . $_SERVER['PHP_SELF'] . '?subject_id=' . $subject_id);
                exit;
                
            } catch (PDOException $e) {
                $errors[] = 'Error creating assignment: ' . $e->getMessage();
            }
        }
    }
}

// Get current assignments based on filters
$query = "
    SELECT cs.*, s.subject_name, s.subject_code, c.class_name, c.class_code,
           CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
           (SELECT COUNT(*) FROM students st WHERE st.class_id = c.id AND st.status = 'active') as student_count
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id = s.id
    JOIN classes c ON cs.class_id = c.id
    JOIN users u ON cs.teacher_id = u.id
    WHERE 1=1
";

$params = [];

if ($subject_id > 0) {
    $query .= " AND cs.subject_id = ?";
    $params[] = $subject_id;
}

if ($class_id > 0) {
    $query .= " AND cs.class_id = ?";
    $params[] = $class_id;
}

if ($teacher_id > 0) {
    $query .= " AND cs.teacher_id = ?";
    $params[] = $teacher_id;
}

$query .= " ORDER BY s.subject_name, c.class_name";

$assignmentsStmt = $pdo->prepare($query);
$assignmentsStmt->execute($params);
$assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Generate CSRF token
$csrf_token = generateCSRFToken();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Assign Subjects to Classes</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
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

            <!-- Warning Messages -->
            <?php if (!empty($warnings)): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Please note:</strong>
                    <ul class="mb-0">
                        <?php foreach ($warnings as $warning): ?>
                            <li><?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Assignment Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo $edit_assignment ? 'Edit Assignment' : 'Create New Assignment'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="assignmentForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <select class="form-select" id="subject_id" name="subject_id" required>
                                        <option value="">Select Subject</option>
                                        <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>" 
                                                <?php echo ($edit_assignment && $edit_assignment['subject_id'] == $subject['id']) || (isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                                    <select class="form-select" id="class_id" name="class_id" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                                <?php echo ($edit_assignment && $edit_assignment['class_id'] == $class['id']) || (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['class_code'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                                    <select class="form-select" id="teacher_id" name="teacher_id" required>
                                        <option value="">Select Teacher</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" 
                                                <?php echo ($edit_assignment && $edit_assignment['teacher_id'] == $teacher['id']) || (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['teacher_name'] . ' (' . $teacher['email'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               value="1" <?php echo ($edit_assignment && $edit_assignment['is_active']) || (!isset($edit_assignment) && !isset($_POST['subject_id'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active Assignment
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $edit_assignment ? 'Update Assignment' : 'Create Assignment'; ?>
                            </button>
                            <?php if ($edit_assignment): ?>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Assignments -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Current Assignments</h5>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="filter_subject" class="form-label">Filter by Subject</label>
                            <select class="form-select" id="filter_subject" name="subject_id">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo $subject_id == $subject['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filter_class" class="form-label">Filter by Class</label>
                            <select class="form-select" id="filter_class" name="class_id">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filter_teacher" class="form-label">Filter by Teacher</label>
                            <select class="form-select" id="filter_teacher" name="teacher_id">
                                <option value="">All Teachers</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php echo $teacher_id == $teacher['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    </form>

                    <?php if (count($assignments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Teacher</th>
                                    <th>Students</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td>
                                        <a href="view.php?id=<?php echo $assignment['subject_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($assignment['subject_name'] . ' (' . $assignment['subject_code'] . ')'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="../classes/view.php?id=<?php echo $assignment['class_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($assignment['class_name'] . ' (' . $assignment['class_code'] . ')'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $assignment['student_count']; ?> students</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $assignment['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $assignment['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?edit=<?php echo $assignment['id']; ?>" class="btn btn-outline-primary btn-sm" title="Edit Assignment">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="confirmRemoveAssignment(<?php echo $assignment['id']; ?>, '<?php echo htmlspecialchars(addslashes($assignment['subject_name'] . ' from ' . $assignment['class_name'])); ?>')" 
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
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-link fa-3x mb-3"></i>
                        <h5>No Assignments Found</h5>
                        <p>No subject assignments match your current filters.</p>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create First Assignment
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function confirmRemoveAssignment(id, description) {
    confirmAction(
        'Remove Assignment',
        'Are you sure you want to remove the subject assignment: ' + description + '?',
        function() {
            window.location.href = '?remove=' + id;
        }
    );
}

document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit filter form when dropdowns change
    document.getElementById('filter_subject').addEventListener('change', function() {
        this.closest('form').submit();
    });
    document.getElementById('filter_class').addEventListener('change', function() {
        this.closest('form').submit();
    });
    document.getElementById('filter_teacher').addEventListener('change', function() {
        this.closest('form').submit();
    });
});
</script>