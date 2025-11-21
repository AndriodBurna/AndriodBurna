<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher']);

$database = new Database();
$pdo = $database->getConnection();

// Get current user's role and ID
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get filters
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : date('Y');
$term = isset($_GET['term']) ? $_GET['term'] : '';

// Build query based on user role
$query = "
    SELECT r.*, s.student_id, s.first_name, s.last_name, s.gender,
           c.class_name, c.class_code, sub.subject_name, sub.subject_code,
           CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
           CASE 
               WHEN r.score >= 80 THEN 'A'
               WHEN r.score >= 70 THEN 'B'
               WHEN r.score >= 60 THEN 'C'
               WHEN r.score >= 50 THEN 'D'
               WHEN r.score >= 40 THEN 'E'
               ELSE 'F'
           END as grade
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN classes c ON r.class_id = c.id
    JOIN subjects sub ON r.subject_id = sub.id
    JOIN users u ON r.teacher_id = u.id
";

// Add role-based filtering
if ($user_role === 'teacher') {
    // Teachers can only see results for subjects they teach
    $query .= "
        JOIN class_subjects cs ON r.subject_id = cs.subject_id AND r.class_id = cs.class_id
        WHERE cs.teacher_id = ? AND cs.is_active = 1
    ";
    $params = [$user_id];
} else {
    $query .= " WHERE 1=1";
    $params = [];
}

// Add filters
if ($class_id > 0) {
    $query .= " AND r.class_id = ?";
    $params[] = $class_id;
}

if ($subject_id > 0) {
    $query .= " AND r.subject_id = ?";
    $params[] = $subject_id;
}

if (!empty($exam_type)) {
    $query .= " AND r.exam_type = ?";
    $params[] = $exam_type;
}

if (!empty($academic_year)) {
    $query .= " AND r.academic_year = ?";
    $params[] = $academic_year;
}

if (!empty($term)) {
    $query .= " AND r.term = ?";
    $params[] = $term;
}

$query .= " ORDER BY r.academic_year DESC, r.term DESC, r.exam_type, c.class_name, s.last_name, s.first_name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Get available subjects (based on user role)
if ($user_role === 'teacher') {
    $subjectsStmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.subject_name, s.subject_code
        FROM subjects s
        JOIN class_subjects cs ON s.id = cs.subject_id
        WHERE cs.teacher_id = ? AND cs.is_active = 1
        ORDER BY s.subject_name
    ");
    $subjectsStmt->execute([$user_id]);
} else {
    $subjectsStmt = $pdo->prepare("SELECT id, subject_name, subject_code FROM subjects WHERE is_active = 1 ORDER BY subject_name");
    $subjectsStmt->execute();
}
$subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct exam types, academic years, and terms
$examTypesStmt = $pdo->prepare("SELECT DISTINCT exam_type FROM results ORDER BY exam_type");
$examTypesStmt->execute();
$exam_types = $examTypesStmt->fetchAll(PDO::FETCH_COLUMN);

$academicYearsStmt = $pdo->prepare("SELECT DISTINCT academic_year FROM results ORDER BY academic_year DESC");
$academicYearsStmt->execute();
$academic_years = $academicYearsStmt->fetchAll(PDO::FETCH_COLUMN);

$termsStmt = $pdo->prepare("SELECT DISTINCT term FROM results ORDER BY term");
$termsStmt->execute();
$terms = $termsStmt->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Results Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="add.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus"></i> Add Results
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter"></i> Filter Results
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="class_id" class="form-label">Class</label>
                            <select class="form-select" id="class_id" name="class_id">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo $subject_id == $subject['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="exam_type" class="form-label">Exam Type</label>
                            <select class="form-select" id="exam_type" name="exam_type">
                                <option value="">All Types</option>
                                <?php foreach ($exam_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $exam_type === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($type)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <select class="form-select" id="academic_year" name="academic_year">
                                <?php foreach ($academic_years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $academic_year == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="term" class="form-label">Term</label>
                            <select class="form-select" id="term" name="term">
                                <option value="">All Terms</option>
                                <?php foreach ($terms as $term_value): ?>
                                <option value="<?php echo htmlspecialchars($term_value); ?>" <?php echo $term === $term_value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($term_value)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Results List
                        <span class="badge bg-secondary ms-2"><?php echo count($results); ?> records</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($results) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="resultsTable">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Gender</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Exam Type</th>
                                    <th>Term</th>
                                    <th>Year</th>
                                    <th>Score</th>
                                    <th>Grade</th>
                                    <th>Teacher</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['student_id']); ?></td>
                                    <td>
                                        <a href="../students/view.php?id=<?php echo $result['student_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($result['last_name'] . ' ' . $result['first_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $result['gender'] === 'male' ? 'primary' : 'pink'; ?>">
                                            <?php echo strtoupper(substr($result['gender'], 0, 1)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($result['exam_type'])); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars(ucfirst($result['term'])); ?></td>
                                    <td><?php echo htmlspecialchars($result['academic_year']); ?></td>
                                    <td>
                                        <strong><?php echo number_format($result['score'], 0); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $result['grade'] === 'A' ? 'success' : 
                                                ($result['grade'] === 'B' ? 'primary' : 
                                                    ($result['grade'] === 'C' ? 'warning' : 
                                                        ($result['grade'] === 'D' ? 'info' : 
                                                            ($result['grade'] === 'E' ? 'secondary' : 'danger')))); 
                                        ?>">
                                            <?php echo $result['grade']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['teacher_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($result['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit.php?id=<?php echo $result['id']; ?>" class="btn btn-outline-primary" title="Edit Result">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="confirmDeleteResult(<?php echo $result['id']; ?>, '<?php echo htmlspecialchars(addslashes($result['last_name'] . ' ' . $result['first_name'] . ' - ' . $result['subject_name'])); ?>')" 
                                                    title="Delete Result">
                                                <i class="fas fa-trash"></i>
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
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <h5>No Results Found</h5>
                        <p>No results match your current filters.</p>
                        <?php if (in_array($user_role, ['admin', 'principal', 'hod', 'teacher'])): ?>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Result
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function confirmDeleteResult(id, description) {
    confirmAction(
        'Delete Result',
        'Are you sure you want to delete the result for: ' + description + '?',
        function() {
            window.location.href = 'delete.php?id=' + id;
        }
    );
}

document.addEventListener('DOMContentLoaded', function() {
    $('#resultsTable').DataTable({
        "order": [[7, 'desc'], [6, 'desc'], [5, 'asc']], // Sort by year, term, exam type
        "pageLength": 25,
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": [12] } // Disable sorting for actions column
        ]
    });
});
</script>