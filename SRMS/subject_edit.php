<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

$subject_id = (int) ($_GET['id'] ?? 0);
$subject_name = '';
$class_id = 0;
$errors = [];

// classes for dropdown
$class_options = mysqli_query($link, "SELECT class_id, class_name FROM classes ORDER BY class_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = sanitize($_POST['subject_name'] ?? '');
    $class_id = (int) ($_POST['class_id'] ?? 0);

    if ($subject_name === '') {
        $errors[] = 'Subject name is required';
    }

    if (empty($errors)) {
        $sql = "UPDATE subjects SET subject_name = ?, class_id = ? WHERE subject_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sii", $subject_name, $class_id, $subject_id);
            if (mysqli_stmt_execute($stmt)) {
                header('location: manage_subjects.php');
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    $sql = "SELECT subject_name, class_id FROM subjects WHERE subject_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $subject_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $subject_name = $row['subject_name'];
            $class_id = (int) $row['class_id'];
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container">
    <h3>Edit Subject</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <p><?php echo $e; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="subject_edit.php?id=<?php echo $subject_id; ?>">
        <div class="form-group">
            <label>Subject Name</label>
            <input type="text" name="subject_name" class="form-control" value="<?php echo $subject_name; ?>">
        </div>
        <div class="form-group">
            <label>Class</label>
            <select name="class_id" class="form-control">
                <?php while ($c = mysqli_fetch_assoc($class_options)): ?>
                    <option value="<?php echo $c['class_id']; ?>" <?php echo selected($class_id, $c['class_id']); ?>><?php echo sanitize($c['class_name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Subject</button>
        <a href="manage_subjects.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>