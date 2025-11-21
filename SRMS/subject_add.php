<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

$subject_name = '';
$class_id = '';
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
        $sql = "INSERT INTO subjects (subject_name, class_id) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $subject_name, $class_id);
            if (mysqli_stmt_execute($stmt)) {
                header('location: manage_subjects.php');
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container">
    <h3>Add Subject</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <p><?php echo $e; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="subject_add.php">
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
        <button type="submit" class="btn btn-primary">Add Subject</button>
        <a href="manage_subjects.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>