<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure approvals table exists
$ensureApprovals = "CREATE TABLE IF NOT EXISTS result_approvals (
  approval_id INT(11) NOT NULL AUTO_INCREMENT,
  result_id INT(11) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  approved_by INT(11) DEFAULT NULL,
  approved_at TIMESTAMP NULL DEFAULT current_timestamp(),
  comments TEXT DEFAULT NULL,
  PRIMARY KEY (approval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($link, $ensureApprovals);

// Ensure principal remarks column exists on results
if (function_exists('ensure_column')) {
    ensure_column($link, 'results', 'principal_remarks', '`principal_remarks` text DEFAULT NULL');
}

$message = '';
$errors = [];

// Actions: approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $admin_user_id = isset($_SESSION['id']) ? (int) $_SESSION['id'] : null;
    $comments = trim($_POST['comments'] ?? '');
    $principal_remarks = trim($_POST['principal_remarks'] ?? '');

    if (in_array($action, ['bulk_approve','bulk_reject'])) {
        $selected = isset($_POST['selected_ids']) && is_array($_POST['selected_ids']) ? array_map('intval', $_POST['selected_ids']) : [];
        if (empty($selected)) { $errors[] = 'No results selected.'; }
        if (empty($errors)) {
            $status = $action === 'bulk_approve' ? 'approved' : 'rejected';
            $sql = "INSERT INTO result_approvals (result_id, status, approved_by, comments) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                foreach ($selected as $rid) {
                    mysqli_stmt_bind_param($stmt, 'isis', $rid, $status, $admin_user_id, $comments);
                    mysqli_stmt_execute($stmt);
                    if ($principal_remarks !== '') {
                        mysqli_query($link, "UPDATE results SET principal_remarks='" . mysqli_real_escape_string($link, $principal_remarks) . "' WHERE result_id=" . (int)$rid);
                    }
                }
                $message = 'Bulk ' . htmlspecialchars($status) . ' completed.';
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = 'Failed to prepare bulk operation.';
            }
        }
    } elseif (in_array($action, ['approve','reject'])) {
        $result_id = (int) ($_POST['result_id'] ?? 0);
        if ($result_id <= 0) { $errors[] = 'Invalid result.'; }
        if (empty($errors)) {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $sql = "INSERT INTO result_approvals (result_id, status, approved_by, comments) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, 'isis', $result_id, $status, $admin_user_id, $comments);
                if (mysqli_stmt_execute($stmt)) {
                    if ($principal_remarks !== '') {
                        mysqli_query($link, "UPDATE results SET principal_remarks='" . mysqli_real_escape_string($link, $principal_remarks) . "' WHERE result_id=" . (int)$result_id);
                    }
                    $message = 'Result ' . htmlspecialchars($status) . ' successfully.';
                } else {
                    $errors[] = 'Failed to update approval status.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } else {
        $errors[] = 'Invalid action.';
    }
}

// List results with approval status (latest)
$results = mysqli_query($link, "SELECT r.result_id, st.name AS student_name, sb.subject_name, r.term, r.year, r.marks, r.grade, r.remarks,
la.latest_id, a.status
FROM results r
JOIN students st ON r.student_id = st.student_id
JOIN subjects sb ON r.subject_id = sb.subject_id
LEFT JOIN (
  SELECT result_id, MAX(approval_id) AS latest_id FROM result_approvals GROUP BY result_id
) la ON la.result_id = r.result_id
LEFT JOIN result_approvals a ON a.approval_id = la.latest_id
ORDER BY a.status IS NULL DESC, a.status ASC, r.year DESC, r.term ASC, sb.subject_name ASC");
?>

<div class="container">
    <h3>Result Approvals</h3>
    <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) { echo '<div>' . htmlspecialchars($e) . '</div>'; } ?></div>
    <?php endif; ?>

    <form method="post" class="card card-body mb-3">
        <div class="form-row align-items-end">
            <div class="form-group col-md-6">
                <label>Principal's Remarks (optional)</label>
                <input type="text" name="principal_remarks" class="form-control" placeholder="Enter remarks applied to selected results">
            </div>
            <div class="form-group col-md-6">
                <label>Comments (audit log)</label>
                <input type="text" name="comments" class="form-control" placeholder="Optional comments saved in approval log">
            </div>
        </div>
        <div class="form-row">
            <div class="col">
                <button class="btn btn-success" name="action" value="bulk_approve" type="submit">Bulk Approve Selected</button>
                <button class="btn btn-danger ml-2" name="action" value="bulk_reject" type="submit">Bulk Reject Selected</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th><input type="checkbox" onclick="document.querySelectorAll('.selbox').forEach(cb=>cb.checked=this.checked)"></th>
                <th>Student</th>
                <th>Subject</th>
                <th>Term</th>
                <th>Year</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($results)): ?>
            <?php $status = $row['status'] ?? null; ?>
            <tr>
                <td><input type="checkbox" class="selbox" name="selected_ids[]" value="<?php echo (int)$row['result_id']; ?>"></td>
                <td><?php echo sanitize($row['student_name']); ?></td>
                <td><?php echo sanitize($row['subject_name']); ?></td>
                <td><?php echo sanitize($row['term']); ?></td>
                <td><?php echo (int)$row['year']; ?></td>
                <td><?php echo (int)$row['marks']; ?></td>
                <td><?php echo sanitize($row['grade']); ?></td>
                <td>
                    <?php if (!$status): ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php elseif ($status === 'approved'): ?>
                        <span class="badge badge-success">Approved</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Rejected</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" class="form-inline">
                        <input type="hidden" name="result_id" value="<?php echo (int)$row['result_id']; ?>">
                        <input type="text" name="principal_remarks" class="form-control form-control-sm mr-2" placeholder="Principal remarks">
                        <input type="text" name="comments" class="form-control form-control-sm mr-2" placeholder="Comments">
                        <button name="action" value="approve" class="btn btn-sm btn-success mr-1" type="submit">Approve</button>
                        <button name="action" value="reject" class="btn btn-sm btn-danger" type="submit">Reject</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <p class="text-muted">Approving or rejecting creates an immutable log entry linked to each result.</p>
</div>

<?php require_once 'includes/footer.php'; ?>