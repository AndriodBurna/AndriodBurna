<?php
include "config.php";
include "includes/auth.php";

// Only admins and teachers can access
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'parent') {
    die("Access denied!");
}

// Debug: Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Function to calculate grade based on marks
function calculateGrade($marks) {
    if ($marks >= 90) return 'A+';
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B+';
    if ($marks >= 60) return 'B';
    if ($marks >= 50) return 'C+';
    if ($marks >= 40) return 'C';
    if ($marks >= 30) return 'D';
    return 'F';
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_results_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Email', 'Class', 'Subject', 'Marks', 'Grade', 'Result Date']);
    
    // Build query for CSV export
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $class_filter = isset($_GET['class_filter']) ? trim($_GET['class_filter']) : '';
    
    $sql = "
        SELECT 
            u.username AS student_name,
            u.email AS student_email,
            cl.class_name AS class,
            s.subject_name,
            r.marks,
            r.grade,
            r.created_at AS result_date
        FROM results r
        JOIN users u ON r.student_id = u.id
        JOIN classes cl ON u.class_id = cl.id
        JOIN subjects s ON r.subject_id = s.id
        WHERE u.role = 'student'
    ";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR s.subject_name LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= 'sss';
    }
    
    if (!empty($class_filter)) {
        $sql .= " AND cl.class_name = ?";
        $params[] = $class_filter;
        $types .= 's';
    }
    
    $sql .= " ORDER BY u.username, s.subject_name, r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $grade = !empty($row['grade']) ? $row['grade'] : calculateGrade($row['marks']);
        fputcsv($output, [
            $row['student_name'],
            $row['student_email'],
            $row['class'] ?? 'N/A',
            $row['subject_name'],
            $row['marks'],
            $grade,
            date('Y-m-d H:i', strtotime($row['result_date']))
        ]);
    }
    fclose($output);
    exit;
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$class_filter = isset($_GET['class_filter']) ? trim($_GET['class_filter']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Get all classes for filter dropdown
$classes_sql = "SELECT DISTINCT class_name FROM classes ORDER BY class_name";
$classes_result = $conn->query($classes_sql);

// Build main query with filters
$sql = "
    SELECT 
        u.username AS student_name,
        u.email AS student_email,
        cl.class_name AS class,
        s.subject_name,
        r.marks,
        r.grade,
        r.created_at AS result_date
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN classes cl ON u.class_id = cl.id
    JOIN subjects s ON r.subject_id = s.id
    WHERE u.role = 'student'
";

$count_sql = "
    SELECT COUNT(*) as total
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN classes cl ON u.class_id = cl.id
    JOIN subjects s ON r.subject_id = s.id
    WHERE u.role = 'student'
";

// Separate parameters for count query
$count_params = [];
$count_types = '';

if (!empty($search)) {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR s.subject_name LIKE ?)";
    $count_sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR s.subject_name LIKE ?)";
    $search_param = "%$search%";
    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param]);
    $count_types .= 'sss';
}

if (!empty($class_filter)) {
    $sql .= " AND cl.class_name = ?";
    $count_sql .= " AND cl.class_name = ?";
    $count_params[] = $class_filter;
    $count_types .= 's';
}

// Get total count for pagination
$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query parameters (separate from count)
$main_params = [];
$main_types = '';

if (!empty($search)) {
    $search_param = "%$search%";
    $main_params = array_merge($main_params, [$search_param, $search_param, $search_param]);
    $main_types .= 'sss';
}

if (!empty($class_filter)) {
    $main_params[] = $class_filter;
    $main_types .= 's';
}

// Add pagination to main query
$sql .= " ORDER BY u.username, s.subject_name, r.created_at DESC LIMIT ? OFFSET ?";
$main_params[] = $records_per_page;
$main_params[] = $offset;
$main_types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($main_params)) {
    $stmt->bind_param($main_types, ...$main_params);
}
$stmt->execute();
$result = $stmt->get_result();

// Debug info
// $debug_info = "";
// if (isset($_GET['debug'])) {
//     $debug_info = "
//     <div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 4px;'>
//         <strong>Debug Info:</strong><br>
//         Total Records: $total_records<br>
//         SQL Query: " . htmlspecialchars($sql) . "<br>
//         Parameters: " . json_encode($main_params) . "<br>
//         Error: " . ($stmt->error ?? 'None') . "<br>
//         Results Found: " . ($result ? $result->num_rows : 0) . "
//     </div>
//     ";
// }
?>
<?php include "includes/header.php"; ?>

<style>
.filters-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #dee2e6;
}

.filter-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: bold;
    color: #495057;
}

.filter-group input, .filter-group select {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
    text-align: center;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn:hover {
    opacity: 0.8;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
}

.pagination a, .pagination span {
    padding: 8px 12px;
    text-decoration: none;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.pagination .current {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.results-summary {
    margin: 15px 0;
    padding: 10px;
    background: #e9ecef;
    border-radius: 4px;
}

.grade-A, .grade-A\+ {
    background: #d4edda;
    color: #155724;
    font-weight: bold;
}

.grade-B, .grade-B\+ {
    background: #d1ecf1;
    color: #0c5460;
    font-weight: bold;
}

.grade-C, .grade-C\+ {
    background: #fff3cd;
    color: #856404;
    font-weight: bold;
}

.grade-D, .grade-F {
    background: #f8d7da;
    color: #721c24;
    font-weight: bold;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

table th, table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

table tr:hover {
    background-color: #f8f9fa;
}
</style>

<h2>📋 Student Results Report</h2>


<!-- Filters Section -->
<div class="filters-container">
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Student name, email, or subject">
            </div>
            
            <div class="filter-group">
                <label for="class_filter">Class:</label>
                <select id="class_filter" name="class_filter">
                    <option value="">All Classes</option>
                    <?php if ($classes_result && $classes_result->num_rows > 0): ?>
                        <?php while ($class = $classes_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($class['class_name']) ?>" 
                                    <?= $class_filter === $class['class_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class['class_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="">No classes found</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">🔍 Search</button>
            </div>
            
            <div class="filter-group">
                <a href="?" class="btn btn-secondary">🔄 Clear</a>
            </div>
            
            <div class="filter-group">
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" 
                   class="btn btn-success">📥 Export CSV</a>
            </div>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <strong>Results:</strong> 
    <?php if ($total_records > 0): ?>
        Showing <?= min($offset + 1, $total_records) ?>-<?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> total records
    <?php else: ?>
        No records found
    <?php endif; ?>
    <?php if (!empty($search) || !empty($class_filter)): ?>
        (filtered)
    <?php endif; ?>
</div>

<!-- Results Table -->
<table>
    <thead>
        <tr>
            <th>Student Name</th>
            <th>Email</th>
            <th>Class</th>
            <th>Subject</th>
            <th>Marks</th>
            <th>Grade</th>
            <th>Result Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                $grade = !empty($row['grade']) ? $row['grade'] : calculateGrade($row['marks']);
                $grade_class = 'grade-' . str_replace('+', '\+', $grade);
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['student_email'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['class'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['subject_name'] ?? 'N/A') ?></td>
                    <td><?= $row['marks'] ?? 'N/A' ?></td>
                    <td class="<?= $grade_class ?>"><?= $grade ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($row['result_date'] ?? 'now')) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center; color: #6c757d; padding: 40px;">
                    <?php if ($total_records == 0): ?>
                        No student results found in the database.
                    <?php else: ?>
                        No results match your current filters. Try adjusting your search criteria.
                    <?php endif; ?>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">« First</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹ Previous</a>
        <?php endif; ?>
        
        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        
        for ($i = $start; $i <= $end; $i++):
        ?>
            <?php if ($i == $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next ›</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">Last »</a>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; color: #6c757d; margin: 10px 0;">
        Page <?= $page ?> of <?= $total_pages ?>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>