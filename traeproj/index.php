<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher']);

$database = new Database();
$pdo = $database->getConnection();

$error = '';
$success = '';

// Handle status toggle
if (isset($_POST['toggle_status']) && isset($_POST['class_id'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request token.';
    } else {
        $classId = (int)$_POST['class_id'];
        $currentStatus = $_POST['current_status'];
        $newStatus = ($currentStatus === '1') ? '0' : '1';
        
        try {
            $stmt = $pdo->prepare("UPDATE classes SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $classId]);
            
            logAction(getCurrentUserId(), 'toggle_class_status', 'classes', $classId);
            $success = 'Class status updated successfully!';
        } catch (Exception $e) {
            $error = 'Error updating class status: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'class_name';
$sortOrder = $_GET['order'] ?? 'ASC';

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(c.class_name LIKE ? OR c.class_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status !== 'all') {
    $whereConditions[] = "c.is_active = ?";
    $params[] = ($status === 'active') ? 1 : 0;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Validate sort parameters
$allowedSortColumns = ['class_name', 'class_code', 'is_active', 'teacher_name', 'created_at'];
$sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'class_name';
$sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM classes c 
               LEFT JOIN users u ON c.class_teacher_id = u.id 
               $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();

// Pagination
$recordsPerPage = 20;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalPages = max(1, ceil($totalRecords / $recordsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $recordsPerPage;

// Get classes
$query = "SELECT c.*, u.first_name as teacher_first_name, u.last_name as teacher_last_name,
                 COUNT(sc.student_id) as student_count
          FROM classes c
          LEFT JOIN users u ON c.class_teacher_id = u.id
          LEFT JOIN student_classes sc ON c.id = sc.class_id AND sc.is_current = 1
          $whereClause
          GROUP BY c.id
          ORDER BY $sortBy $sortOrder
          LIMIT $recordsPerPage OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Class Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (in_array($currentUser['role'], ['admin', 'principal'])): ?>
                    <a href="add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add New Class
                    </a>
                    <?php endif; ?>
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

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by name, code, or teacher..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo ($status === 'all') ? 'selected' : ''; ?>>All Classes</option>
                                <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active Only</option>
                                <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Inactive Only</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="class_name" <?php echo ($sortBy === 'class_name') ? 'selected' : ''; ?>>Class Name</option>
                                <option value="class_code" <?php echo ($sortBy === 'class_code') ? 'selected' : ''; ?>>Class Code</option>
                                <option value="teacher_name" <?php echo ($sortBy === 'teacher_name') ? 'selected' : ''; ?>>Class Teacher</option>
                                <option value="is_active" <?php echo ($sortBy === 'is_active') ? 'selected' : ''; ?>>Status</option>
                                <option value="created_at" <?php echo ($sortBy === 'created_at') ? 'selected' : ''; ?>>Date Created</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Classes Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Classes List</h5>
                    <span class="badge bg-secondary"><?php echo $totalRecords; ?> classes</span>
                </div>
                <div class="card-body">
                    <?php if (count($classes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Code</th>
                                        <th>Class Teacher</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($class['description'] ?? ''); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                                            <td>
                                                <?php if ($class['class_teacher_id']): ?>
                                                    <?php echo htmlspecialchars($class['teacher_first_name'] . ' ' . $class['teacher_last_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not Assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $class['student_count']; ?> students</span>
                                            </td>
                                            <td>
                                                <?php if ($class['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="view.php?id=<?php echo $class['id']; ?>" 
                                                       class="btn btn-outline-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if (in_array($currentUser['role'], ['admin', 'principal'])): ?>
                                                    <a href="edit.php?id=<?php echo $class['id']; ?>" 
                                                       class="btn btn-outline-secondary" title="Edit Class">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to toggle the status of this class?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="toggle_status" value="1">
                                                        <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                                        <input type="hidden" name="current_status" value="<?php echo $class['is_active']; ?>">
                                                        <button type="submit" class="btn btn-outline-warning" title="Toggle Status">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Class pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($currentPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>">
                                                Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                        <li class="page-item <?php echo ($i === $currentPage) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($currentPage < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>">
                                                Next
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-school fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No classes found</h5>
                            <p class="text-muted">No classes match your search criteria.</p>
                            <?php if (in_array($currentUser['role'], ['admin', 'principal'])): ?>
                                <a href="add.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-plus"></i> Add Your First Class
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
$(document).ready(function() {
    // Auto-submit form when filters change (optional)
    $('#status, #sort').change(function() {
        $(this).closest('form').submit();
    });
});
</script>