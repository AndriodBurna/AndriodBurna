<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher']);

$database = new Database();
$pdo = $database->getConnection();

// Get current user's role
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get filter parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'class_performance';
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_subject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$selected_term = isset($_GET['term']) ? $_GET['term'] : '';
$selected_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$selected_exam_type = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';

// Get available options for filters
$classes = $pdo->query("SELECT id, class_name FROM classes WHERE status = 'active' ORDER BY class_name");
$subjects = $pdo->query("SELECT id, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_name");
$terms = ['first', 'second', 'third'];
$exam_types = ['exam', 'test', 'assignment'];

// Get current academic year if not selected
if (empty($selected_year)) {
    $currentYear = date('Y');
    $selected_year = $currentYear . '/' . ($currentYear + 1);
}

// Build query conditions based on report type
$conditions = [];
$params = [];

if ($selected_class > 0) {
    $conditions[] = "r.class_id = ?";
    $params[] = $selected_class;
}

if ($selected_subject > 0) {
    $conditions[] = "r.subject_id = ?";
    $params[] = $selected_subject;
}

if (!empty($selected_term)) {
    $conditions[] = "r.term = ?";
    $params[] = $selected_term;
}

if (!empty($selected_year)) {
    $conditions[] = "r.academic_year = ?";
    $params[] = $selected_year;
}

if (!empty($selected_exam_type)) {
    $conditions[] = "r.exam_type = ?";
    $params[] = $selected_exam_type;
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Generate reports based on type
$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'class_performance':
        $report_title = 'Class Performance Report';
        $report_data = generateClassPerformanceReport($pdo, $whereClause, $params);
        break;
    
    case 'subject_performance':
        $report_title = 'Subject Performance Report';
        $report_data = generateSubjectPerformanceReport($pdo, $whereClause, $params);
        break;
    
    case 'student_performance':
        $report_title = 'Student Performance Report';
        $report_data = generateStudentPerformanceReport($pdo, $whereClause, $params);
        break;
    
    case 'grade_distribution':
        $report_title = 'Grade Distribution Report';
        $report_data = generateGradeDistributionReport($pdo, $whereClause, $params);
        break;
    
    case 'teacher_performance':
        $report_title = 'Teacher Performance Report';
        $report_data = generateTeacherPerformanceReport($pdo, $whereClause, $params);
        break;
}

function generateClassPerformanceReport($pdo, $whereClause, $params) {
    $query = "
        SELECT c.class_name, c.class_code, COUNT(DISTINCT r.student_id) as student_count,
               COUNT(r.id) as result_count, AVG(r.score) as average_score,
               MAX(r.score) as highest_score, MIN(r.score) as lowest_score,
               COUNT(CASE WHEN r.score >= 70 THEN 1 END) as excellent_count,
               COUNT(CASE WHEN r.score >= 50 AND r.score < 70 THEN 1 END) as good_count,
               COUNT(CASE WHEN r.score < 50 THEN 1 END) as needs_improvement_count
        FROM results r
        JOIN classes c ON r.class_id = c.id
        $whereClause
        GROUP BY c.id, c.class_name, c.class_code
        ORDER BY average_score DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateSubjectPerformanceReport($pdo, $whereClause, $params) {
    $query = "
        SELECT sub.subject_name, sub.subject_code, COUNT(r.id) as result_count,
               AVG(r.score) as average_score, MAX(r.score) as highest_score, MIN(r.score) as lowest_score,
               COUNT(CASE WHEN r.score >= 70 THEN 1 END) as excellent_count,
               COUNT(CASE WHEN r.score >= 50 AND r.score < 70 THEN 1 END) as good_count,
               COUNT(CASE WHEN r.score < 50 THEN 1 END) as needs_improvement_count,
               COUNT(DISTINCT r.class_id) as classes_taught
        FROM results r
        JOIN subjects sub ON r.subject_id = sub.id
        $whereClause
        GROUP BY sub.id, sub.subject_name, sub.subject_code
        ORDER BY average_score DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateStudentPerformanceReport($pdo, $whereClause, $params) {
    $query = "
        SELECT s.student_id, s.first_name, s.last_name, c.class_name,
               COUNT(r.id) as subject_count, AVG(r.score) as average_score,
               COUNT(CASE WHEN r.score >= 70 THEN 1 END) as excellent_count,
               COUNT(CASE WHEN r.score >= 50 AND r.score < 70 THEN 1 END) as good_count,
               COUNT(CASE WHEN r.score < 50 THEN 1 END) as needs_improvement_count,
               MAX(r.score) as highest_score, MIN(r.score) as lowest_score
        FROM results r
        JOIN students s ON r.student_id = s.id
        JOIN classes c ON r.class_id = c.id
        $whereClause
        GROUP BY s.id, s.student_id, s.first_name, s.last_name, c.class_name
        HAVING COUNT(r.id) >= 3
        ORDER BY average_score DESC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateGradeDistributionReport($pdo, $whereClause, $params) {
    $query = "
        SELECT 
            CASE 
                WHEN r.score >= 80 THEN 'A'
                WHEN r.score >= 70 THEN 'B'
                WHEN r.score >= 60 THEN 'C'
                WHEN r.score >= 50 THEN 'D'
                WHEN r.score >= 40 THEN 'E'
                ELSE 'F'
            END as grade,
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM results r $whereClause), 1) as percentage
        FROM results r
        $whereClause
        GROUP BY grade
        ORDER BY grade
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateTeacherPerformanceReport($pdo, $whereClause, $params) {
    $query = "
        SELECT CONCAT(u.first_name, ' ', u.last_name) as teacher_name, u.email,
               COUNT(r.id) as result_count, COUNT(DISTINCT r.subject_id) as subjects_taught,
               COUNT(DISTINCT r.class_id) as classes_taught, AVG(r.score) as average_score,
               COUNT(CASE WHEN r.score >= 70 THEN 1 END) as excellent_count,
               COUNT(CASE WHEN r.score >= 50 AND r.score < 70 THEN 1 END) as good_count,
               COUNT(CASE WHEN r.score < 50 THEN 1 END) as needs_improvement_count
        FROM results r
        JOIN users u ON r.teacher_id = u.id
        $whereClause
        GROUP BY u.id, u.first_name, u.last_name, u.email
        ORDER BY average_score DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 text-gradient">
                    <i class="fas fa-chart-bar me-2"></i>Reports
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-danger" onclick="generatePDF()">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Type Selection -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Select Report Type
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="btn-group flex-wrap" role="group">
                                <a href="?report_type=class_performance" class="btn btn-outline-primary <?php echo $report_type === 'class_performance' ? 'active' : ''; ?>">
                                    <i class="fas fa-users"></i> Class Performance
                                </a>
                                <a href="?report_type=subject_performance" class="btn btn-outline-primary <?php echo $report_type === 'subject_performance' ? 'active' : ''; ?>">
                                    <i class="fas fa-book"></i> Subject Performance
                                </a>
                                <a href="?report_type=student_performance" class="btn btn-outline-primary <?php echo $report_type === 'student_performance' ? 'active' : ''; ?>">
                                    <i class="fas fa-user-graduate"></i> Student Performance
                                </a>
                                <a href="?report_type=grade_distribution" class="btn btn-outline-primary <?php echo $report_type === 'grade_distribution' ? 'active' : ''; ?>">
                                    <i class="fas fa-chart-pie"></i> Grade Distribution
                                </a>
                                <a href="?report_type=teacher_performance" class="btn btn-outline-primary <?php echo $report_type === 'teacher_performance' ? 'active' : ''; ?>">
                                    <i class="fas fa-chalkboard-teacher"></i> Teacher Performance
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter"></i> Filters
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
                        
                        <div class="col-md-2">
                            <label for="class_id" class="form-label">Class</label>
                            <select class="form-select" name="class_id" id="class_id">
                                <option value="">All Classes</option>
                                <?php while ($class = $classes->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $class['id']; ?>" 
                                            <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" name="subject_id" id="subject_id">
                                <option value="">All Subjects</option>
                                <?php while ($subject = $subjects->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $subject['id']; ?>" 
                                            <?php echo $selected_subject == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="term" class="form-label">Term</label>
                            <select class="form-select" name="term" id="term">
                                <option value="">All Terms</option>
                                <?php foreach ($terms as $term): ?>
                                    <option value="<?php echo $term; ?>" 
                                            <?php echo $selected_term == $term ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($term); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" name="academic_year" id="academic_year" 
                                   value="<?php echo htmlspecialchars($selected_year); ?>" 
                                   placeholder="e.g., 2023/2024">
                        </div>
                        <div class="col-md-2">
                            <label for="exam_type" class="form-label">Exam Type</label>
                            <select class="form-select" name="exam_type" id="exam_type">
                                <option value="">All Types</option>
                                <?php foreach ($exam_types as $type): ?>
                                    <option value="<?php echo $type; ?>" 
                                            <?php echo $selected_exam_type == $type ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Generate
                            </button>
                            <a href="?report_type=<?php echo htmlspecialchars($report_type); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Header -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($report_title); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Report Generated:</strong> <?php echo date('M d, Y H:i:s'); ?><br>
                            <strong>Filters Applied:</strong>
                            <?php
                            $filters = [];
                            if ($selected_class > 0) $filters[] = "Class";
                            if ($selected_subject > 0) $filters[] = "Subject";
                            if (!empty($selected_term)) $filters[] = "Term";
                            if (!empty($selected_year)) $filters[] = "Academic Year";
                            if (!empty($selected_exam_type)) $filters[] = "Exam Type";
                            echo !empty($filters) ? implode(', ', $filters) : 'None';
                            ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <strong>Total Records:</strong> <?php echo count($report_data); ?><br>
                            <strong>Generated By:</strong> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Content -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($report_data)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No data found for the selected filters.
                        </div>
                    <?php else: ?>
                        <?php displayReport($report_type, $report_data); ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
function displayReport($report_type, $data) {
    switch ($report_type) {
        case 'class_performance':
            displayClassPerformanceReport($data);
            break;
        case 'subject_performance':
            displaySubjectPerformanceReport($data);
            break;
        case 'student_performance':
            displayStudentPerformanceReport($data);
            break;
        case 'grade_distribution':
            displayGradeDistributionReport($data);
            break;
        case 'teacher_performance':
            displayTeacherPerformanceReport($data);
            break;
    }
}

function displayClassPerformanceReport($data) {
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover" id="reportTable">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Students</th>
                    <th>Results</th>
                    <th>Average Score</th>
                    <th>Highest</th>
                    <th>Lowest</th>
                    <th>Excellent (70%+)</th>
                    <th>Good (50-69%)</th>
                    <th>Needs Improvement (<50%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['class_name'] . ' (' . $row['class_code'] . ')'); ?></td>
                    <td><?php echo number_format($row['student_count']); ?></td>
                    <td><?php echo number_format($row['result_count']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $row['average_score'] >= 70 ? 'success' : ($row['average_score'] >= 60 ? 'warning' : 'danger'); ?>">
                            <?php echo number_format($row['average_score'], 1); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($row['highest_score'], 0); ?></td>
                    <td><?php echo number_format($row['lowest_score'], 0); ?></td>
                    <td><?php echo number_format($row['excellent_count']); ?></td>
                    <td><?php echo number_format($row['good_count']); ?></td>
                    <td><?php echo number_format($row['needs_improvement_count']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function displaySubjectPerformanceReport($data) {
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover" id="reportTable">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Results</th>
                    <th>Average Score</th>
                    <th>Highest</th>
                    <th>Lowest</th>
                    <th>Classes Taught</th>
                    <th>Excellent (70%+)</th>
                    <th>Good (50-69%)</th>
                    <th>Needs Improvement (<50%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['subject_name'] . ' (' . $row['subject_code'] . ')'); ?></td>
                    <td><?php echo number_format($row['result_count']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $row['average_score'] >= 70 ? 'success' : ($row['average_score'] >= 60 ? 'warning' : 'danger'); ?>">
                            <?php echo number_format($row['average_score'], 1); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($row['highest_score'], 0); ?></td>
                    <td><?php echo number_format($row['lowest_score'], 0); ?></td>
                    <td><?php echo number_format($row['classes_taught']); ?></td>
                    <td><?php echo number_format($row['excellent_count']); ?></td>
                    <td><?php echo number_format($row['good_count']); ?></td>
                    <td><?php echo number_format($row['needs_improvement_count']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function displayStudentPerformanceReport($data) {
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover" id="reportTable">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Subjects</th>
                    <th>Average Score</th>
                    <th>Highest</th>
                    <th>Lowest</th>
                    <th>Excellent (70%+)</th>
                    <th>Good (50-69%)</th>
                    <th>Needs Improvement (<50%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_name'] . ' ' . $row['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                    <td><?php echo number_format($row['subject_count']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $row['average_score'] >= 70 ? 'success' : ($row['average_score'] >= 60 ? 'warning' : 'danger'); ?>">
                            <?php echo number_format($row['average_score'], 1); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($row['highest_score'], 0); ?></td>
                    <td><?php echo number_format($row['lowest_score'], 0); ?></td>
                    <td><?php echo number_format($row['excellent_count']); ?></td>
                    <td><?php echo number_format($row['good_count']); ?></td>
                    <td><?php echo number_format($row['needs_improvement_count']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function displayGradeDistributionReport($data) {
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover" id="reportTable">
            <thead>
                <tr>
                    <th>Grade</th>
                    <th>Count</th>
                    <th>Percentage</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grade_descriptions = [
                    'A' => 'Excellent (80-100%)',
                    'B' => 'Very Good (70-79%)',
                    'C' => 'Good (60-69%)',
                    'D' => 'Fair (50-59%)',
                    'E' => 'Pass (40-49%)',
                    'F' => 'Fail (0-39%)'
                ];
                foreach ($data as $row): 
                ?>
                <tr>
                    <td>
                        <span class="badge bg-<?php 
                            echo $row['grade'] === 'A' ? 'success' : 
                                ($row['grade'] === 'B' ? 'primary' : 
                                    ($row['grade'] === 'C' ? 'warning' : 
                                        ($row['grade'] === 'D' ? 'info' : 
                                            ($row['grade'] === 'E' ? 'secondary' : 'danger')))); 
                        ?> fs-5">
                            <?php echo $row['grade']; ?>
                        </span>
                    </td>
                    <td><?php echo number_format($row['count']); ?></td>
                    <td><?php echo $row['percentage']; ?>%</td>
                    <td><?php echo $grade_descriptions[$row['grade']] ?? 'Unknown'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function displayTeacherPerformanceReport($data) {
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover" id="reportTable">
            <thead>
                <tr>
                    <th>Teacher Name</th>
                    <th>Email</th>
                    <th>Results</th>
                    <th>Subjects Taught</th>
                    <th>Classes Taught</th>
                    <th>Average Score</th>
                    <th>Excellent (70%+)</th>
                    <th>Good (50-69%)</th>
                    <th>Needs Improvement (<50%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo number_format($row['result_count']); ?></td>
                    <td><?php echo number_format($row['subjects_taught']); ?></td>
                    <td><?php echo number_format($row['classes_taught']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $row['average_score'] >= 70 ? 'success' : ($row['average_score'] >= 60 ? 'warning' : 'danger'); ?>">
                            <?php echo number_format($row['average_score'], 1); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($row['excellent_count']); ?></td>
                    <td><?php echo number_format($row['good_count']); ?></td>
                    <td><?php echo number_format($row['needs_improvement_count']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>

<script>
function generatePDF() {
    const reportType = document.querySelector('select[name="report_type"]').value;
    const classId = document.querySelector('select[name="class_id"]').value;
    const subjectId = document.querySelector('select[name="subject_id"]').value;
    const term = document.querySelector('select[name="term"]').value;
    const academicYear = document.querySelector('select[name="academic_year"]').value;
    const examType = document.querySelector('select[name="exam_type"]').value;
    
    // Build PDF generation URL
    let pdfUrl = 'generate_pdf.php?type=' + reportType;
    
    if (classId) pdfUrl += '&class_id=' + classId;
    if (subjectId) pdfUrl += '&subject_id=' + subjectId;
    if (term) pdfUrl += '&term=' + term;
    if (academicYear) pdfUrl += '&academic_year=' + academicYear;
    if (examType) pdfUrl += '&exam_type=' + examType;
    
    // Open PDF in new window
    window.open(pdfUrl, '_blank');
}

function exportToExcel() {
    // Simple CSV export
    const table = document.getElementById('reportTable');
    if (!table) {
        alert('No data table found to export');
        return;
    }
    
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => {
            // Remove any HTML tags and extra whitespace
            return cell.textContent.trim().replace(/\s+/g, ' ');
        }).join(',');
        csv.push(rowData);
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'report_<?php echo date('Y-m-d'); ?>.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const filterElements = document.querySelectorAll('select[name="report_type"], select[name="class_id"], select[name="subject_id"], select[name="term"], select[name="academic_year"], select[name="exam_type"]');
    
    filterElements.forEach(element => {
        element.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>