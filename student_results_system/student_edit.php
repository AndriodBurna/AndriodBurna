<?php
include "config.php";
include "includes/auth.php";
include "includes/header.php";

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    die("Access denied!");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT username, full_name, email, course_id, admission_year FROM users WHERE id=? AND role='student'");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student not found!");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $course_id = isset($_POST['course_id']) && $_POST['course_id'] !== '' ? (int)$_POST['course_id'] : null;
    $admission_year = intval($_POST['admission_year']);
    $new_password = trim($_POST['new_password']);

    // Validate that course_id exists (if not null)
    if ($course_id !== null) {
        $cq = $conn->prepare("SELECT id FROM courses WHERE id = ?");
        $cq->bind_param("i", $course_id);
        $cq->execute();
        $cqres = $cq->get_result();
        if ($cqres->num_rows === 0) {
            // invalid course_id, set to null
            $course_id = null;
        }
        $cq->close();
    }

    if ($new_password !== '') {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name=?, email=?, course_id=?, admission_year=?, password=? WHERE id=?";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("ssisis", $full_name, $email, $course_id, $admission_year, $hashed, $id);
    } else {
        $sql = "UPDATE users SET full_name=?, email=?, course_id=?, admission_year=? WHERE id=?";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("ssisi", $full_name, $email, $course_id, $admission_year, $id);
    }

    if ($stmt2->execute()) {
        $success = "Student updated successfully!";
        // Refresh student data
        $stmt = $conn->prepare("SELECT username, full_name, email, course_id, admission_year FROM users WHERE id=? AND role='student'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Error: " . $stmt2->error;
    }
}
?>

<h2>Edit Student</h2>
<?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>

<form method="post">
    <label>Assign Course:</label><br>
    <select name="course_id">
        <option value="">-- None --</option>
        <?php
        $cRes = $conn->query("SELECT id, course_name FROM courses ORDER BY course_name ASC");
        while ($c = $cRes->fetch_assoc()):
        ?>
            <option value="<?= $c['id'] ?>"
                <?= ($student['course_id'] !== null && $student['course_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['course_name']) ?>
            </option>
        <?php endwhile; ?>
    </select><br>

    <label>Username (cannot change)</label><br>
    <input type="text" value="<?= htmlspecialchars($student['username']) ?>" disabled><br>

    <label>Full Name</label><br>
    <input type="text" name="full_name" value="<?= htmlspecialchars($student['full_name']) ?>"><br>

    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>"><br>

    <label>Admission Year</label><br>
    <input type="number" name="admission_year" min="1900" max="<?= date('Y') ?>"
           value="<?= htmlspecialchars($student['admission_year']) ?>"><br>

    <h4>Change password (optional)</h4>
    <input type="password" name="new_password" placeholder="Leave blank to keep old password"><br>

    <button type="submit" class="btn">Update</button>
</form>

<?php include "includes/footer.php"; ?>
