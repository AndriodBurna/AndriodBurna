<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure quotas table exists to prevent runtime SQL errors
ensure_table($link, 'teacher_leave_quotas', "CREATE TABLE IF NOT EXISTS `teacher_leave_quotas` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `teacher_id` int(11) NOT NULL,\n  `year` int(11) NOT NULL,\n  `annual_quota` int(11) DEFAULT 0,\n  `sick_quota` int(11) DEFAULT 0,\n  `unpaid_quota` int(11) DEFAULT 0,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $year = (int)($_POST['year'] ?? date('Y'));
    $annual_quota = (int)($_POST['annual_quota'] ?? 0);
    $sick_quota = (int)($_POST['sick_quota'] ?? 0);
    $unpaid_quota = (int)($_POST['unpaid_quota'] ?? 0);
    if ($teacher_id > 0) {
        mysqli_query($link, "INSERT INTO teacher_leave_quotas (teacher_id, year, annual_quota, sick_quota, unpaid_quota) VALUES ($teacher_id, $year, $annual_quota, $sick_quota, $unpaid_quota)");
        $msg = '<div class="alert alert-success">Leave quotas saved.</div>';
    } else {
        $msg = '<div class="alert alert-warning">Select a teacher.</div>';
    }
}

$teachers = mysqli_query($link, "SELECT teacher_id, name FROM teachers ORDER BY name ASC");
$quotas = mysqli_query($link, "SELECT tlq.id, t.name, tlq.year, tlq.annual_quota, tlq.sick_quota, tlq.unpaid_quota FROM teacher_leave_quotas tlq JOIN teachers t ON tlq.teacher_id=t.teacher_id ORDER BY tlq.year DESC, t.name ASC");
?>

<div class="container">
    <h3>Teacher Leave Quotas</h3>
    <?php echo $msg; ?>
    <form method="post" class="card card-body mb-4">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Teacher</label>
                <select name="teacher_id" class="form-control" required>
                    <option value="">-- Select --</option>
                    <?php if ($teachers && $teachers instanceof mysqli_result): ?>
                        <?php while ($t = mysqli_fetch_assoc($teachers)): ?>
                            <option value="<?php echo (int)$t['teacher_id']; ?>"><?php echo sanitize($t['name']); ?></option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="">No teachers found</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group col-md-2"><label>Year</label><input type="number" name="year" class="form-control" value="<?php echo date('Y'); ?>" required></div>
            <div class="form-group col-md-2"><label>Annual</label><input type="number" name="annual_quota" class="form-control" value="10"></div>
            <div class="form-group col-md-2"><label>Sick</label><input type="number" name="sick_quota" class="form-control" value="5"></div>
            <div class="form-group col-md-2"><label>Unpaid</label><input type="number" name="unpaid_quota" class="form-control" value="0"></div>
        </div>
        <button type="submit" class="btn btn-primary">Save Quotas</button>
    </form>

    <table class="table table-bordered table-sm">
        <thead><tr><th>Teacher</th><th>Year</th><th>Annual</th><th>Sick</th><th>Unpaid</th></tr></thead>
        <tbody>
            <?php if ($quotas && $quotas instanceof mysqli_result): ?>
                <?php while ($q = mysqli_fetch_assoc($quotas)): ?>
                    <tr>
                        <td><?php echo sanitize($q['name']); ?></td>
                        <td><?php echo (int)$q['year']; ?></td>
                        <td><?php echo (int)$q['annual_quota']; ?></td>
                        <td><?php echo (int)$q['sick_quota']; ?></td>
                        <td><?php echo (int)$q['unpaid_quota']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No quotas recorded yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>