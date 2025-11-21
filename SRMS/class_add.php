<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';

$class_name = $stream = $year = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_name = trim($_POST['class_name']);
    $stream = trim($_POST['stream']);
    $year = (int) trim($_POST['year']);

    if (empty($class_name)) {
        $errors[] = 'Class name is required';
    }

    if (empty($errors)) {
        $sql = "INSERT INTO classes (class_name, stream, year) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $class_name, $stream, $year);
            if (mysqli_stmt_execute($stmt)) {
                header('location: manage_classes.php');
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container">
    <h3>Add Class</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="class_add.php" method="post">
        <div class="form-group">
            <label>Class Name</label>
            <input type="text" name="class_name" class="form-control">
        </div>
        <div class="form-group">
            <label>Stream</label>
            <input type="text" name="stream" class="form-control">
        </div>
        <div class="form-group">
            <label>Year</label>
            <input type="number" name="year" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Add Class</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>