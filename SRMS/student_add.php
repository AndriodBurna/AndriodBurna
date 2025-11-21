<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

$name = $gender = $dob = $class_id = '';
$email = $phone = $address = $medical_info = '';
$admission_date = date('Y-m-d');
$admission_status = 'active';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['dob']);
    $class_id = (int) trim($_POST['class_id']);
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $medical_info = trim($_POST['medical_info'] ?? '');
    $admission_date = trim($_POST['admission_date'] ?? date('Y-m-d'));
    $admission_status = trim($_POST['admission_status'] ?? 'active');

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($errors)) {
        // Handle photo upload
        $photo_path = null;
        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = __DIR__ . '/assets/uploads/students';
            if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $safeName = 'st_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
            $target = $uploadDir . '/' . $safeName;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $photo_path = 'assets/uploads/students/' . $safeName;
            }
        }

        $uid = generate_student_uid($link);
        $year_joined = (int) date('Y');
        $sql = "INSERT INTO students (student_uid, name, gender, dob, class_id, email, phone, address, medical_info, photo, year_joined, admission_date, admission_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssissssssss", $uid, $name, $gender, $dob, $class_id, $email, $phone, $address, $medical_info, $photo_path, $year_joined, $admission_date, $admission_status);
            if (mysqli_stmt_execute($stmt)) {
                header('location: manage_students.php');
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container">
    <h3>Add Student</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="student_add.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control">
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="dob" class="form-control">
        </div>
        <div class="form-group">
            <label>Class ID</label>
            <input type="text" name="class_id" class="form-control">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control">
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group">
            <label>Medical Info</label>
            <textarea name="medical_info" class="form-control" rows="3" placeholder="Allergies, conditions, medications"></textarea>
        </div>
        <div class="form-group">
            <label>Photo</label>
            <input type="file" name="photo" class="form-control-file" accept="image/*">
        </div>
        <div class="form-group">
            <label>Admission Date</label>
            <input type="date" name="admission_date" class="form-control" value="<?php echo $admission_date; ?>">
        </div>
        <div class="form-group">
            <label>Admission Status</label>
            <select name="admission_status" class="form-control">
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="suspended">Suspended</option>
                <option value="withdrawn">Withdrawn</option>
                <option value="graduated">Graduated</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Add Student</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>