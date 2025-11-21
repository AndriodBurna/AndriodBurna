<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure newer columns exist to avoid SQL errors on update
ensure_column($link, 'students', 'phone', '`phone` varchar(20) DEFAULT NULL');
ensure_column($link, 'students', 'address', '`address` text DEFAULT NULL');
ensure_column($link, 'students', 'medical_info', '`medical_info` text DEFAULT NULL');
ensure_column($link, 'students', 'photo', '`photo` varchar(255) DEFAULT NULL');
ensure_column($link, 'students', 'admission_date', '`admission_date` date DEFAULT NULL');
ensure_column($link, 'students', 'admission_status', "`admission_status` enum('active','pending','suspended','withdrawn','graduated') DEFAULT 'active'");
ensure_column($link, 'students', 'student_uid', '`student_uid` varchar(20) DEFAULT NULL');

$student_id = $_GET['id'];
$name = $gender = $dob = $class_id = '';
$email = $phone = $address = $medical_info = '';
$photo = '';
$admission_date = '';
$admission_status = 'active';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['dob']);
    $class_id = trim($_POST['class_id']);
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $medical_info = trim($_POST['medical_info'] ?? '');
    $admission_date = trim($_POST['admission_date'] ?? '');
    $admission_status = trim($_POST['admission_status'] ?? 'active');

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($errors)) {
        // Photo upload handling
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

        $sql = "UPDATE students SET name = ?, gender = ?, dob = ?, class_id = ?, email = ?, phone = ?, address = ?, medical_info = ?, admission_date = ?, admission_status = ?" . ($photo_path ? ", photo = ?" : "") . " WHERE student_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            if ($photo_path) {
                mysqli_stmt_bind_param($stmt, "sssisssssssi", $name, $gender, $dob, $class_id, $email, $phone, $address, $medical_info, $admission_date, $admission_status, $photo_path, $student_id);
            } else {
                mysqli_stmt_bind_param($stmt, "sssissssssi", $name, $gender, $dob, $class_id, $email, $phone, $address, $medical_info, $admission_date, $admission_status, $student_id);
            }
            if (mysqli_stmt_execute($stmt)) {
                header('location: manage_students.php');
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    $sql = "SELECT * FROM students WHERE student_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);
        $name = $student['name'] ?? '';
        $gender = $student['gender'] ?? '';
        $dob = $student['dob'] ?? '';
        $class_id = $student['class_id'] ?? '';
        $email = $student['email'] ?? '';
        $phone = $student['phone'] ?? '';
        $address = $student['address'] ?? '';
        $medical_info = $student['medical_info'] ?? '';
        $photo = $student['photo'] ?? '';
        $admission_date = $student['admission_date'] ?? '';
        $admission_status = $student['admission_status'] ?? 'active';
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container">
    <h3>Edit Student</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="student_edit.php?id=<?php echo $student_id; ?>" method="post" enctype="multipart/form-data">
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
            <label>Date of Birth</label>
            <input type="date" name="dob" class="form-control" value="<?php echo $dob; ?>">
        </div>
        <div class="form-group">
            <label>Class ID</label>
            <input type="text" name="class_id" class="form-control" value="<?php echo $class_id; ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>">
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
        </div>
        <div class="form-group">
            <label>Medical Info</label>
            <textarea name="medical_info" class="form-control" rows="3"><?php echo htmlspecialchars($medical_info); ?></textarea>
        </div>
        <div class="form-group">
            <label>Photo</label>
            <?php if ($photo): ?><div class="mb-2"><img src="<?php echo htmlspecialchars($photo); ?>" alt="Photo" style="max-height:80px;border-radius:6px;"></div><?php endif; ?>
            <input type="file" name="photo" class="form-control-file" accept="image/*">
        </div>
        <div class="form-group">
            <label>Admission Date</label>
            <input type="date" name="admission_date" class="form-control" value="<?php echo htmlspecialchars($admission_date); ?>">
        </div>
        <div class="form-group">
            <label>Admission Status</label>
            <select name="admission_status" class="form-control">
                <?php $statuses = ['active','pending','suspended','withdrawn','graduated']; foreach($statuses as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo $admission_status===$st? 'selected':''; ?>><?php echo ucfirst($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Student</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>