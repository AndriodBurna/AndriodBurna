<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';

$teacher_id = $_GET['id'];
$name = $gender = $email = $phone = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($errors)) {
        $sql = "UPDATE teachers SET name = ?, gender = ?, email = ?, phone = ? WHERE teacher_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $gender, $email, $phone, $teacher_id);
            if (mysqli_stmt_execute($stmt)) {
                header('location: manage_teachers.php');
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    $sql = "SELECT * FROM teachers WHERE teacher_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $teacher_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $teacher = mysqli_fetch_assoc($result);
        $name = $teacher['name'];
        $gender = $teacher['gender'];
        $email = $teacher['email'];
        $phone = $teacher['phone'];
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container">
    <h3>Edit Teacher</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="teacher_edit.php?id=<?php echo $teacher_id; ?>" method="post">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo $name; ?>">
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="Male" <?php if ($gender == 'Male') echo 'selected'; ?>>Male</option>
                <option value="Female" <?php if ($gender == 'Female') echo 'selected'; ?>>Female</option>
                <option value="Other" <?php if ($gender == 'Other') echo 'selected'; ?>>Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo $phone; ?>">
        </div>
        <button type="submit" class="btn btn-primary">Update Teacher</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>