<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod']);

$database = new Database();
$pdo = $database->getConnection();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'active';

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR s.parent_guardian_name LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "s.status = ?";
    $params[] = $status;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM students s $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();

// Get pagination data
$pagination = getPagination($page, $totalRecords);

// Get students
$sql = "SELECT s.*, c.class_name, c.class_code, 
        (SELECT COUNT(*) FROM student_classes sc WHERE sc.student_id = s.id AND sc.status = 'active') as active_classes
        FROM students s 
        LEFT JOIN student_classes sc ON s.id = sc.student_id 
        LEFT JOIN classes c ON sc.class_id = c.id 
        $whereClause 
        GROUP BY s.id 
        ORDER BY s.first_name, s.last_name 
        LIMIT {$pagination['offset']}, 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Students Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="add.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus"></i> Add Student
                        </a>
                        <a href="import.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-upload"></i> Import Students
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by name, ID, or parent name">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="graduated" <?php echo $status === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                                <option value="transferred" <?php echo $status === 'transferred' ? 'selected' : ''; ?>>Transferred</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students Table -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Students List</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($students)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No students found matching your criteria.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered data-table" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Date of Birth</th>
                                        <th>Class</th>
                                        <th>Parent/Guardian</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td>
                                            <?php if ($student['photo']): ?>
                                                <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                                                     alt="Student Photo" class="img-thumbnail" width="50" height="50">
                                            <?php else: ?>
                                                <i class="fas fa-user fa-2x text-secondary"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                        <td><?php echo $student['date_of_birth'] ? formatDate($student['date_of_birth']) : 'N/A'; ?></td>
                                        <td>
                                            <?php echo $student['class_name'] ? htmlspecialchars($student['class_name'] . ' (' . $student['class_code'] . ')') : 'Not Assigned'; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['parent_guardian_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : ($student['status'] === 'graduated' ? 'primary' : 'secondary'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($student['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="view.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')" 
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $pagination['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">Next</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function deleteStudent(id, name) {
    confirmAction('Are you sure you want to delete student: ' + name + '?', function() {
        $.post('delete.php', {id: id, csrf_token: '<?php echo $_SESSION["csrf_token"]; ?>'})
            .done(function(response) {
                if (response.success) {
                    showAlert('Student deleted successfully!', 'success');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showAlert(response.message || 'Error deleting student', 'error');
                }
            })
            .fail(function() {
                showAlert('Error deleting student', 'error');
            });
    });
}
</script>