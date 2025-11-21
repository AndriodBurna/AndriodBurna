<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher']);

$database = new Database();
$pdo = $database->getConnection();

$errors = [];
$success = false;
$students = [];

// Get current user's role and ID
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get form data
$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : (isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0);
$subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : (isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0);
$exam_type = isset($_POST['exam_type']) ? $_POST['exam_type'] : '';
$academic_year = isset($_POST['academic_year']) ? $_POST['academic_year'] : date('Y');
$term = isset($_POST['term']) ? $_POST['term'] : '';

// Get available classes (based on user role)
if ($user_role === 'teacher') {
    $classesStmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.class_name, c.class_code
        FROM classes c
        JOIN class_subjects cs ON c.id = cs.class_id
        WHERE cs.teacher_id = ? AND cs.is_active = 1
        ORDER BY c.class_name
    ");
    $classesStmt->execute([$user_id]);
} else {
    $classesStmt = $pdo->prepare("SELECT id, class_name, class_code FROM classes WHERE is_active = 1 ORDER BY class_name");
    $classesStmt->execute();
}
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get available subjects (based on user role and selected class)
if ($class_id > 0) {
    if ($user_role === 'teacher') {
        $subjectsStmt = $pdo->prepare("
            SELECT DISTINCT s.id, s.subject_name, s.subject_code
            FROM subjects s
            JOIN class_subjects cs ON s.id = cs.subject_id
            WHERE cs.class_id = ? AND cs.teacher_id = ? AND cs.is_active = 1
            ORDER BY s.subject_name
        ");
        $subjectsStmt->execute([$class_id, $user_id]);
    } else {
        $subjectsStmt = $pdo->prepare("
            SELECT DISTINCT s.id, s.subject_name, s.subject_code
            FROM subjects s
            JOIN class_subjects cs ON s.id = cs.subject_id
            WHERE cs.class_id = ? AND cs.is_active = 1
            ORDER BY s.subject_name
        ");
        $subjectsStmt->execute([$class_id]);
    }
    $subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $subjects = [];
}

// Get students for the selected class
if ($class_id > 0) {
    $studentsStmt = $pdo->prepare("
        SELECT s.id, s.student_id, s.first_name, s.last_name, s.gender, s.photo
        FROM students s
        WHERE s.class_id = ? AND s.status = 'active'
        ORDER BY s.last_name, s.first_name
    ");
    $studentsStmt->execute([$class_id]);
    $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_results'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token. Please try again.';
    } else {
        // Validate required fields
        if (empty($class_id)) {
            $errors[] = 'Please select a class.';
        }
        if (empty($subject_id)) {
            $errors[] = 'Please select a subject.';
        }
        if (empty($exam_type)) {
            $errors[] = 'Please select an exam type.';
        }
        if (empty($academic_year)) {
            $errors[] = 'Please enter an academic year.';
        }
        if (empty($term)) {
            $errors[] = 'Please select a term.';
        }

        // Check if teacher is authorized to teach this subject in this class
        if (empty($errors) && $user_role === 'teacher') {
            $checkStmt = $pdo->prepare("
                SELECT id FROM class_subjects 
                WHERE class_id = ? AND subject_id = ? AND teacher_id = ? AND is_active = 1
            ");
            $checkStmt->execute([$class_id, $subject_id, $user_id]);
            if (!$checkStmt->fetch()) {
                $errors[] = 'You are not authorized to enter results for this subject in the selected class.';
            }
        }

        // Process results
        if (empty($errors)) {
            $results_data = $_POST['results'] ?? [];
            $processed_count = 0;
            $error_count = 0;
            
            foreach ($results_data as $student_id => $data) {
                $score = isset($data['score']) ? (float)$data['score'] : null;
                $comments = isset($data['comments']) ? trim($data['comments']) : '';
                
                // Validate score
                if ($score !== null && $score !== '') {
                    if ($score < 0 || $score > 100) {
                        $errors[] = "Invalid score for student ID {$student_id}: Score must be between 0 and 100.";
                        $error_count++;
                        continue;
                    }
                    
                    try {
                        // Check if result already exists
                        $checkStmt = $pdo->prepare("
                            SELECT id FROM results 
                            WHERE student_id = ? AND class_id = ? AND subject_id = ? 
                            AND exam_type = ? AND academic_year = ? AND term = ?
                        ");
                        $checkStmt->execute([$student_id, $class_id, $subject_id, $exam_type, $academic_year, $term]);
                        
                        if ($checkStmt->fetch()) {
                            // Update existing result
                            $updateStmt = $pdo->prepare("
                                UPDATE results 
                                SET score = ?, comments = ?, teacher_id = ?, updated_at = NOW()
                                WHERE student_id = ? AND class_id = ? AND subject_id = ? 
                                AND exam_type = ? AND academic_year = ? AND term = ?
                            ");
                            $updateStmt->execute([$score, $comments, $user_id, $student_id, $class_id, $subject_id, $exam_type, $academic_year, $term]);
                        } else {
                            // Insert new result
                            $insertStmt = $pdo->prepare("
                                INSERT INTO results (student_id, class_id, subject_id, exam_type, academic_year, term, score, comments, teacher_id, created_at, updated_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                            ");
                            $insertStmt->execute([$student_id, $class_id, $subject_id, $exam_type, $academic_year, $term, $score, $comments, $user_id]);
                        }
                        
                        $processed_count++;
                        
                    } catch (PDOException $e) {
                        $errors[] = "Error processing result for student ID {$student_id}: " . $e->getMessage();
                        $error_count++;
                    }
                }
            }
            
            if ($processed_count > 0) {
                // Get class and subject names for logging
                $className = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?")->execute([$class_id])->fetch(PDO::FETCH_ASSOC);
                $subjectName = $pdo->prepare("SELECT subject_name FROM subjects WHERE id = ?")->execute([$subject_id])->fetch(PDO::FETCH_ASSOC);
                
                logAction('RESULTS_ENTERED', "Entered {$processed_count} results for {$subjectName['subject_name']} in {$className['class_name']} ({$exam_type}, {$term} {$academic_year})");
                
                $_SESSION['success_message'] = "Successfully processed {$processed_count} results.";
                if ($error_count > 0) {
                    $_SESSION['error_message'] = "{$error_count} results had errors and were not processed.";
                }
                
                // Redirect to results index
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'No valid results were submitted. Please check your input.';
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
                <h1 class="h2">Enter Results</h1>
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
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Selection Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Select Class, Subject, and Exam Details</h5>
                </div>
                <div class="card-body">
                    <form method="GET" id="selectionForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                                    <select class="form-select" id="class_id" name="class_id" required onchange="this.form.submit()">
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name'] . ' (' . $class['class_code'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <select class="form-select" id="subject_id" name="subject_id" required onchange="this.form.submit()">
                                        <option value="">Select Subject</option>
                                        <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>" <?php echo $subject_id == $subject['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="exam_type" class="form-label">Exam Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="exam_type" name="exam_type" required onchange="this.form.submit()">
                                        <option value="">Select Type</option>
                                        <option value="cat1" <?php echo $exam_type === 'cat1' ? 'selected' : ''; ?>>CAT 1</option>
                                        <option value="cat2" <?php echo $exam_type === 'cat2' ? 'selected' : ''; ?>>CAT 2</option>
                                        <option value="main" <?php echo $exam_type === 'main' ? 'selected' : ''; ?>>Main Exam</option>
                                        <option value="retake" <?php echo $exam_type === 'retake' ? 'selected' : ''; ?>>Retake</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                    <select class="form-select" id="academic_year" name="academic_year" required onchange="this.form.submit()">
                                        <?php for ($year = date('Y') + 1; $year >= date('Y') - 5; $year--): ?>
                                        <option value="<?php echo $year; ?>" <?php echo $academic_year == $year ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="term" class="form-label">Term <span class="text-danger">*</span></label>
                                    <select class="form-select" id="term" name="term" required onchange="this.form.submit()">
                                        <option value="">Select Term</option>
                                        <option value="term1" <?php echo $term === 'term1' ? 'selected' : ''; ?>>Term 1</option>
                                        <option value="term2" <?php echo $term === 'term2' ? 'selected' : ''; ?>>Term 2</option>
                                        <option value="term3" <?php echo $term === 'term3' ? 'selected' : ''; ?>>Term 3</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Entry Form -->
            <?php if ($class_id > 0 && $subject_id > 0 && !empty($exam_type) && !empty($academic_year) && !empty($term)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        Enter Results for 
                        <?php 
                        $class_name = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?")->execute([$class_id])->fetch(PDO::FETCH_ASSOC);
                        $subject_name = $pdo->prepare("SELECT subject_name FROM subjects WHERE id = ?")->execute([$subject_id])->fetch(PDO::FETCH_ASSOC);
                        echo htmlspecialchars($class_name['class_name'] . ' - ' . $subject_name['subject_name'] . ' (' . ucfirst($exam_type) . ', ' . ucfirst($term) . ' ' . $academic_year . ')');
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($students) > 0): ?>
                    <form method="POST" id="resultsForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo htmlspecialchars($exam_type); ?>">
                        <input type="hidden" name="academic_year" value="<?php echo htmlspecialchars($academic_year); ?>">
                        <input type="hidden" name="term" value="<?php echo htmlspecialchars($term); ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="resultsTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Gender</th>
                                        <th>Score (0-100)</th>
                                        <th>Grade</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): ?>
                                    <?php
                                    // Get existing result if any
                                    $existingStmt = $pdo->prepare("
                                        SELECT score, comments FROM results 
                                        WHERE student_id = ? AND class_id = ? AND subject_id = ? 
                                        AND exam_type = ? AND academic_year = ? AND term = ?
                                    ");
                                    $existingStmt->execute([$student['id'], $class_id, $subject_id, $exam_type, $academic_year, $term]);
                                    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($student['photo']): ?>
                                                <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>" 
                                                     class="rounded-circle me-2" width="32" height="32">
                                                <?php else: ?>
                                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 32px; height: 32px;">
                                                    <span class="text-white fw-bold">
                                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $student['gender'] === 'male' ? 'primary' : 'pink'; ?>">
                                                <?php echo strtoupper(substr($student['gender'], 0, 1)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control score-input" 
                                                   name="results[<?php echo $student['id']; ?>][score]" 
                                                   value="<?php echo $existing ? $existing['score'] : ''; ?>" 
                                                   min="0" max="100" step="0.1"
                                                   onchange="updateGrade(this, 'grade_<?php echo $index; ?>')">
                                        </td>
                                        <td>
                                            <span class="badge" id="grade_<?php echo $index; ?>">
                                                <?php 
                                                if ($existing && $existing['score'] !== null) {
                                                    $grade = getGrade($existing['score']);
                                                    echo $grade;
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <textarea class="form-control" rows="1" 
                                                      name="results[<?php echo $student['id']; ?>][comments]"
                                                      placeholder="Optional comments"><?php echo $existing ? htmlspecialchars($existing['comments']) : ''; ?></textarea>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearAllScores()">
                                    <i class="fas fa-eraser"></i> Clear All
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="submit_results" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Results
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h5>No Active Students</h5>
                        <p>There are no active students in the selected class.</p>
                        <a href="../students/add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Student
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function updateGrade(scoreInput, gradeElementId) {
    const score = parseFloat(scoreInput.value);
    const gradeElement = document.getElementById(gradeElementId);
    
    if (isNaN(score) || score === '') {
        gradeElement.innerHTML = '-';
        gradeElement.className = 'badge';
    } else {
        let grade, gradeClass;
        
        if (score >= 80) {
            grade = 'A';
            gradeClass = 'bg-success';
        } else if (score >= 70) {
            grade = 'B';
            gradeClass = 'bg-primary';
        } else if (score >= 60) {
            grade = 'C';
            gradeClass = 'bg-warning';
        } else if (score >= 50) {
            grade = 'D';
            gradeClass = 'bg-info';
        } else if (score >= 40) {
            grade = 'E';
            gradeClass = 'bg-secondary';
        } else {
            grade = 'F';
            gradeClass = 'bg-danger';
        }
        
        gradeElement.innerHTML = grade;
        gradeElement.className = 'badge ' + gradeClass;
    }
}

function clearAllScores() {
    if (confirm('Are you sure you want to clear all scores? This action cannot be undone.')) {
        document.querySelectorAll('.score-input').forEach(input => {
            input.value = '';
            // Trigger the change event to update grades
            input.dispatchEvent(new Event('change'));
        });
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.value = '';
        });
    }
}

// Auto-submit form when class or subject changes
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('selectionForm');
    const classSelect = document.getElementById('class_id');
    const subjectSelect = document.getElementById('subject_id');
    
    // Only auto-submit if both class and subject are selected
    if (classSelect.value && subjectSelect.value) {
        // Form will auto-submit on change
    }
});

// Add keyboard navigation for score inputs
document.addEventListener('DOMContentLoaded', function() {
    const scoreInputs = document.querySelectorAll('.score-input');
    
    scoreInputs.forEach((input, index) => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === 'Tab') {
                // Move to next input
                const nextInput = scoreInputs[index + 1];
                if (nextInput) {
                    nextInput.focus();
                    nextInput.select();
                } else {
                    // Focus the first textarea
                    const firstTextarea = document.querySelector('textarea');
                    if (firstTextarea) {
                        firstTextarea.focus();
                    }
                }
                e.preventDefault();
            }
        });
    });
});
</script>