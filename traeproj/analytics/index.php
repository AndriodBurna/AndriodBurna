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

// Build query conditions
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

// Get overall statistics
$statsQuery = "
    SELECT 
        COUNT(DISTINCT r.student_id) as total_students,
        COUNT(r.id) as total_results,
        AVG(r.score) as average_score,
        MAX(r.score) as highest_score,
        MIN(r.score) as lowest_score,
        COUNT(CASE WHEN r.score >= 70 THEN 1 END) as excellent_count,
        COUNT(CASE WHEN r.score >= 50 AND r.score < 70 THEN 1 END) as good_count,
        COUNT(CASE WHEN r.score < 50 THEN 1 END) as needs_improvement_count
    FROM results r
    $whereClause
";

$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute($params);
$overall_stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get grade distribution
$gradeQuery = "
    SELECT 
        CASE 
            WHEN score >= 80 THEN 'A'
            WHEN score >= 70 THEN 'B'
            WHEN score >= 60 THEN 'C'
            WHEN score >= 50 THEN 'D'
            WHEN score >= 40 THEN 'E'
            ELSE 'F'
        END as grade,
        COUNT(*) as count
    FROM results r
    $whereClause
    GROUP BY grade
    ORDER BY grade
";

$gradeStmt = $pdo->prepare($gradeQuery);
$gradeStmt->execute($params);
$grade_distribution = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);

// Get top performing students
$topStudentsQuery = "
    SELECT s.student_id, s.first_name, s.last_name, c.class_name, AVG(r.score) as average_score, COUNT(r.id) as subject_count
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN classes c ON r.class_id = c.id
    $whereClause
    GROUP BY s.id, s.student_id, s.first_name, s.last_name, c.class_name
    HAVING COUNT(r.id) >= 3
    ORDER BY average_score DESC
    LIMIT 10
";

$topStudentsStmt = $pdo->prepare($topStudentsQuery);
$topStudentsStmt->execute($params);
$top_students = $topStudentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get subject performance
$subjectPerformanceQuery = "
    SELECT sub.subject_name, sub.subject_code, COUNT(r.id) as result_count,
           AVG(r.score) as average_score, MAX(r.score) as highest_score, MIN(r.score) as lowest_score
    FROM results r
    JOIN subjects sub ON r.subject_id = sub.id
    $whereClause
    GROUP BY sub.id, sub.subject_name, sub.subject_code
    ORDER BY average_score DESC
";

$subjectPerformanceStmt = $pdo->prepare($subjectPerformanceQuery);
$subjectPerformanceStmt->execute($params);
$subject_performance = $subjectPerformanceStmt->fetchAll(PDO::FETCH_ASSOC);

// Get class performance
$classPerformanceQuery = "
    SELECT c.class_name, c.class_code, COUNT(DISTINCT r.student_id) as student_count,
           COUNT(r.id) as result_count, AVG(r.score) as average_score
    FROM results r
    JOIN classes c ON r.class_id = c.id
    $whereClause
    GROUP BY c.id, c.class_name, c.class_code
    ORDER BY average_score DESC
";

$classPerformanceStmt = $pdo->prepare($classPerformanceQuery);
$classPerformanceStmt->execute($params);
$class_performance = $classPerformanceStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Analytics Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
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
                                <i class="fas fa-search"></i> Apply
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Overall Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Total Students</h5>
                            <h2 class="text-primary"><?php echo number_format($overall_stats['total_students']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Total Results</h5>
                            <h2 class="text-info"><?php echo number_format($overall_stats['total_results']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Average Score</h5>
                            <h2 class="text-success"><?php echo number_format($overall_stats['average_score'], 1); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Pass Rate</h5>
                            <h2 class="text-warning"><?php 
                                $pass_rate = $overall_stats['total_results'] > 0 ? 
                                    ($overall_stats['excellent_count'] + $overall_stats['good_count']) / $overall_stats['total_results'] * 100 : 0;
                                echo number_format($pass_rate, 1) . '%';
                            ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Categories -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5>Excellent (70%+)</h5>
                            <h3><?php echo number_format($overall_stats['excellent_count']); ?></h3>
                            <small><?php 
                                echo $overall_stats['total_results'] > 0 ? 
                                    number_format($overall_stats['excellent_count'] / $overall_stats['total_results'] * 100, 1) . '%' : '0%';
                            ?> of results</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h5>Good (50-69%)</h5>
                            <h3><?php echo number_format($overall_stats['good_count']); ?></h3>
                            <small><?php 
                                echo $overall_stats['total_results'] > 0 ? 
                                    number_format($overall_stats['good_count'] / $overall_stats['total_results'] * 100, 1) . '%' : '0%';
                            ?> of results</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h5>Needs Improvement (<50%)</h5>
                            <h3><?php echo number_format($overall_stats['needs_improvement_count']); ?></h3>
                            <small><?php 
                                echo $overall_stats['total_results'] > 0 ? 
                                    number_format($overall_stats['needs_improvement_count'] / $overall_stats['total_results'] * 100, 1) . '%' : '0%';
                            ?> of results</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Grade Distribution -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie"></i> Grade Distribution
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="gradeChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Students -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-trophy"></i> Top Performing Students
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Student</th>
                                            <th>Class</th>
                                            <th>Average</th>
                                            <th>Subjects</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_students as $index => $student): ?>
                                        <tr>
                                            <td><span class="badge bg-<?php echo $index < 3 ? 'warning' : 'secondary'; ?>">
                                                <?php echo $index + 1; ?>
                                            </span></td>
                                            <td><?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $student['average_score'] >= 70 ? 'success' : 
                                                        ($student['average_score'] >= 60 ? 'warning' : 'danger'); ?>">
                                                    <?php echo number_format($student['average_score'], 1); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $student['subject_count']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Subject Performance -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-book"></i> Subject Performance
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Results</th>
                                            <th>Average</th>
                                            <th>Range</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subject_performance as $subject): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo number_format($subject['result_count']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $subject['average_score'] >= 70 ? 'success' : 
                                                        ($subject['average_score'] >= 60 ? 'warning' : 'danger'); ?>">
                                                    <?php echo number_format($subject['average_score'], 1); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($subject['lowest_score'], 0) . '-' . number_format($subject['highest_score'], 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Class Performance -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-users"></i> Class Performance
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Class</th>
                                            <th>Students</th>
                                            <th>Results</th>
                                            <th>Average</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($class_performance as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td><?php echo number_format($class['student_count']); ?></td>
                                            <td><?php echo number_format($class['result_count']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $class['average_score'] >= 70 ? 'success' : 
                                                        ($class['average_score'] >= 60 ? 'warning' : 'danger'); ?>">
                                                    <?php echo number_format($class['average_score'], 1); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Grade Distribution Chart
const ctx = document.getElementById('gradeChart').getContext('2d');
const gradeData = {
    labels: [<?php 
        $labels = array_map(function($grade) {
            return "'" . $grade['grade'] . "'";
        }, $grade_distribution);
        echo implode(', ', $labels);
    ?>],
    datasets: [{
        data: [<?php 
            $data = array_map(function($grade) {
                return $grade['count'];
            }, $grade_distribution);
            echo implode(', ', $data);
        ?>],
        backgroundColor: [
            '#28a745', // A - Success
            '#007bff', // B - Primary
            '#ffc107', // C - Warning
            '#17a2b8', // D - Info
            '#6c757d', // E - Secondary
            '#dc3545'  // F - Danger
        ]
    }]
};

new Chart(ctx, {
    type: 'doughnut',
    data: gradeData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label;
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

function exportToPDF() {
    // Simple PDF export functionality
    window.print();
}
</script>