<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal', 'hod', 'teacher']);

$database = new Database();
$pdo = $database->getConnection();

// Search and filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'name_asc';

// Build query
$query = "
    SELECT s.*, 
           COUNT(cs.id) as classes_count,
           GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name SEPARATOR ', ') as assigned_classes,
           GROUP_CONCAT(DISTINCT u.first_name, ' ', u.last_name ORDER BY u.first_name SEPARATOR ', ') as assigned_teachers
    FROM subjects s
    LEFT JOIN class_subjects cs ON s.id = cs.subject_id AND cs.is_active = 1
    LEFT JOIN classes c ON cs.class_id = c.id
    LEFT JOIN users u ON cs.teacher_id = u.id
    WHERE 1=1
";

$params = [];

// Apply search filter
if ($search) {
    $query .= " AND (s.subject_name LIKE ? OR s.subject_code LIKE ? OR s.description LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

// Apply status filter
if ($status !== 'all') {
    $query .= " AND s.is_active = ?";
    $params[] = ($status === 'active') ? 1 : 0;
}

// Group by subject
$query .= " GROUP BY s.id";

// Apply sorting
$orderBy = [
    'name_asc' => 's.subject_name ASC',
    'name_desc' => 's.subject_name DESC',
    'code_asc' => 's.subject_code ASC',
    'code_desc' => 's.subject_code DESC',
    'classes_asc' => 'classes_count ASC',
    'classes_desc' => 'classes_count DESC',
    'created_desc' => 's.created_at DESC',
    'created_asc' => 's.created_at ASC'
];

if (isset($orderBy[$sort])) {
    $query .= " ORDER BY " . $orderBy[$sort];
} else {
    $query .= " ORDER BY s.subject_name ASC";
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$countQuery = str_replace("SELECT s.*, COUNT(cs.id) as classes_count, GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name SEPARATOR ', ') as assigned_classes, GROUP_CONCAT(DISTINCT u.first_name, ' ', u.last_name ORDER BY u.first_name SEPARATOR ', ') as assigned_teachers", "SELECT COUNT(DISTINCT s.id) as total", $query);
$countQuery = str_replace(" GROUP BY s.id", "", $countQuery);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = max(1, ceil($totalRecords / $limit));

// Apply pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Subject Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="add.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus"></i> Add Subject
                        </a>
                        <a href="assign.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-link"></i> Assign Subjects
                        </a>
                    </div>
                </div>
            </div>

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

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search subjects...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="code_asc" <?php echo $sort === 'code_asc' ? 'selected' : ''; ?>>Code (A-Z)</option>
                                <option value="code_desc" <?php echo $sort === 'code_desc' ? 'selected' : ''; ?>>Code (Z-A)</option>
                                <option value="classes_desc" <?php echo $sort === 'classes_desc' ? 'selected' : ''; ?>>Classes (Most)</option>
                                <option value="classes_asc" <?php echo $sort === 'classes_asc' ? 'selected' : ''; ?>>Classes (Least)</option>
                                <option value="created_desc" <?php echo $sort === 'created_desc' ? 'selected' : ''; ?>>Created (Newest)</option>
                                <option value="created_asc" <?php echo $sort === 'created_asc' ? 'selected' : ''; ?>>Created (Oldest)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subjects Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Subjects List</h5>
                    <small class="text-muted">Total: <?php echo $totalRecords; ?> subjects</small>
                </div>
                <div class="card-body">
                    <?php if (count($subjects) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="subjectsTable">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Description</th>
                                    <th>Assigned Classes</th>
                                    <th>Assigned Teachers</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $subject['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($subject['description'], 0, 50)) . (strlen($subject['description']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td>
                                        <?php if ($subject['classes_count'] > 0): ?>
                                            <span class="badge bg-primary"><?php echo $subject['classes_count']; ?> class(es)</span>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($subject['assigned_classes'], 0, 30)) . (strlen($subject['assigned_classes']) > 30 ? '...' : ''); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($subject['assigned_teachers']): ?>
                                            <?php echo htmlspecialchars(substr($subject['assigned_teachers'], 0, 30)) . (strlen($subject['assigned_teachers']) > 30 ? '...' : ''); ?>
                                        <?php else: ?>
                                            <span class="text-muted">No teachers</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $subject['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $subject['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view.php?id=<?php echo $subject['id']; ?>" class="btn btn-outline-primary btn-sm" title="View Subject">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $subject['id']; ?>" class="btn btn-outline-secondary btn-sm" title="Edit Subject">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($subject['classes_count'] == 0): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="confirmDelete(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars(addslashes($subject['subject_name'])); ?>')" 
                                                    title="Delete Subject">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                    <nav aria-label="Subjects pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-book fa-3x mb-3"></i>
                        <h5>No Subjects Found</h5>
                        <p><?php echo $search ? 'No subjects match your search criteria.' : 'No subjects have been added yet.'; ?></p>
                        <?php if (!$search): ?>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Subject
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
function confirmDelete(id, name) {
    confirmAction(
        'Delete Subject',
        'Are you sure you want to delete the subject "' + name + '"? This action cannot be undone.',
        function() {
            window.location.href = 'delete.php?id=' + id;
        }
    );
}

$(document).ready(function() {
    // Auto-submit form when filter values change
    $('#status, #sort').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>