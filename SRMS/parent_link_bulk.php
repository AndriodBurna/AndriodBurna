<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $student_ids = $_POST['student_ids'] ?? [];
    if ($parent_id > 0 && is_array($student_ids) && count($student_ids) > 0) {
        foreach ($student_ids as $sid) {
            $sid = (int)$sid;
            mysqli_query($link, "UPDATE students SET parent_id=$parent_id WHERE student_id=$sid");
        }
        $msg = '<div class="alert alert-success">Linked ' . count($student_ids) . ' students to the parent.</div>';
    } else {
        $msg = '<div class="alert alert-warning">Select a parent and at least one student.</div>';
    }
}

$parents = mysqli_query($link, "SELECT parent_id, name FROM parents ORDER BY name ASC");
$students_no_parent = mysqli_query($link, "SELECT student_id, name FROM students WHERE parent_id IS NULL ORDER BY name ASC");
?>

<div class="container">
    <h3>Bulk Link Students to Parent</h3>
    <?php echo $msg; ?>
    <form method="post" class="card card-body">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Parent</label>
                <select name="parent_id" class="form-control" required>
                    <option value="">-- Select Parent --</option>
                    <?php while ($p = mysqli_fetch_assoc($parents)): ?>
                        <option value="<?php echo (int)$p['parent_id']; ?>"><?php echo sanitize($p['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group col-md-8">
                <label>Students without parent</label>
                <select name="student_ids[]" class="form-control" multiple size="10" required>
                    <?php while ($s = mysqli_fetch_assoc($students_no_parent)): ?>
                        <option value="<?php echo (int)$s['student_id']; ?>"><?php echo sanitize($s['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <small class="form-text text-muted">Hold Ctrl/Command to select multiple.</small>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Link Selected Students</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>