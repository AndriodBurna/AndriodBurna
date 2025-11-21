<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$database = new Database();
$pdo = $database->getConnection();

$userRole = getCurrentUserRole();
$userName = getCurrentUserName();
$currentTerm = getCurrentAcademicTerm($pdo);

// Get dashboard data based on user role
try {
    $dashboardData = [];
    
    if (in_array($userRole, ['admin', 'principal', 'hod'])) {
        // Get overall statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students WHERE status = 'active'");
        $dashboardData['total_students'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_teachers FROM teachers WHERE status = 'active'");
        $dashboardData['total_teachers'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_classes FROM classes WHERE is_active = 1");
        $dashboardData['total_classes'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_subjects FROM subjects WHERE is_active = 1");
        $dashboardData['total_subjects'] = $stmt->fetchColumn();
        
        // Get recent results
        $stmt = $pdo->query("
            SELECT r.*, s.first_name, s.last_name, sub.subject_name, at.type_name 
            FROM results r 
            JOIN students s ON r.student_id = s.id 
            JOIN class_subjects cs ON r.class_subject_id = cs.id 
            JOIN subjects sub ON cs.subject_id = sub.id 
            JOIN assessment_types at ON r.assessment_type_id = at.id 
            ORDER BY r.submission_date DESC 
            LIMIT 10
        ");
        $dashboardData['recent_results'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($userRole === 'teacher') {
        // Get teacher's classes and subjects
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(sc.student_id) as student_count 
            FROM classes c 
            JOIN class_subjects cs ON c.id = cs.class_id 
            JOIN teachers t ON cs.teacher_id = t.id 
            LEFT JOIN student_classes sc ON c.id = sc.class_id AND sc.status = 'active' 
            WHERE t.user_id = ? AND cs.is_active = 1 
            GROUP BY c.id
        ");
        $stmt->execute([getCurrentUserId()]);
        $dashboardData['teacher_classes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($userRole === 'student') {
        // Get student's results
        $stmt = $pdo->prepare("
            SELECT r.*, sub.subject_name, at.type_name, t.first_name as teacher_first_name, t.last_name as teacher_last_name 
            FROM results r 
            JOIN students s ON r.student_id = s.id 
            JOIN class_subjects cs ON r.class_subject_id = cs.id 
            JOIN subjects sub ON cs.subject_id = sub.id 
            JOIN assessment_types at ON r.assessment_type_id = at.id 
            LEFT JOIN teachers t ON cs.teacher_id = t.id 
            WHERE s.user_id = ? AND r.status = 'approved' 
            ORDER BY r.submission_date DESC 
            LIMIT 10
        ");
        $stmt->execute([getCurrentUserId()]);
        $dashboardData['student_results'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($userRole === 'parent') {
        // Get parent's children and their results
        $stmt = $pdo->prepare("
            SELECT s.*, u.first_name, u.last_name 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.parent_guardian_email = (SELECT email FROM users WHERE id = ?) 
            AND s.status = 'active'
        ");
        $stmt->execute([getCurrentUserId()]);
        $dashboardData['children'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-calendar"></i> <?php echo htmlspecialchars($currentTerm['term_name'] ?? 'No Active Term'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Admin/Principal/HOD Dashboard -->
            <?php if (in_array($userRole, ['admin', 'principal', 'hod'])): ?>
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Students
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($dashboardData['total_students']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Teachers
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($dashboardData['total_teachers']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Classes
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($dashboardData['total_classes']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-school fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Total Subjects
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($dashboardData['total_subjects']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Results -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Results</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Assessment</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboardData['recent_results'] as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['type_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['marks_obtained']); ?></td>
                                        <td><?php echo htmlspecialchars($result['grade']); ?></td>
                                        <td><?php echo formatDate($result['submission_date']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Teacher Dashboard -->
            <?php if ($userRole === 'teacher'): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">My Classes</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($dashboardData['teacher_classes'] as $class): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo $class['student_count']; ?> Students
                                                </div>
                                                <div class="text-xs text-muted">
                                                    Room: <?php echo htmlspecialchars($class['room_number']); ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-users fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <a href="results_entry.php?class_id=<?php echo $class['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus"></i> Enter Results
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Student Dashboard -->
            <?php if ($userRole === 'student'): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">My Recent Results</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Assessment</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Teacher</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboardData['student_results'] as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['type_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['marks_obtained']); ?></td>
                                        <td><?php echo htmlspecialchars($result['grade']); ?></td>
                                        <td><?php echo htmlspecialchars($result['teacher_first_name'] . ' ' . $result['teacher_last_name']); ?></td>
                                        <td><?php echo formatDate($result['submission_date']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Parent Dashboard -->
            <?php if ($userRole === 'parent'): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">My Children</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($dashboardData['children'] as $child): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                                </div>
                                                <div class="text-xs text-muted">
                                                    Student ID: <?php echo htmlspecialchars($child['student_id']); ?>
                                                </div>
                                                <div class="text-xs text-muted">
                                                    Class: <?php echo htmlspecialchars($child['current_class'] ?? 'Not Assigned'); ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-child fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <a href="student_results.php?student_id=<?php echo $child['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-chart-bar"></i> View Results
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>