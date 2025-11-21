<?php
// profile.php
include "config.php";
include "includes/auth.php"; // ensures session is started and user is logged in
include "includes/header.php";

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success = '';
$error = '';

// Fetch existing profile
$stmt = $conn->prepare("SELECT username, full_name, email, contact, admission_year FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $contact   = trim($_POST['contact'] ?? '');
    $student_class = trim($_POST['student_class'] ?? null);
    $admission_year = !empty($_POST['admission_year']) ? intval($_POST['admission_year']) : null;
    $new_password = trim($_POST['new_password'] ?? '');

    // validate email if provided
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        if ($new_password !== '') {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET full_name = ?, email = ?, contact = ?, student_class = ?, admission_year = ?, password = ? WHERE id = ?";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("ssssisi", $full_name, $email, $contact, $student_class, $admission_year, $hashed, $user_id);
        } else {
            $sql = "UPDATE users SET full_name = ?, email = ?, contact = ?, student_class = ?, admission_year = ? WHERE id = ?";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("sssiis", $full_name, $email, $contact, $student_class, $admission_year, $user_id);
            // Note: bind types corrected below if needed
        }

        // Because bind types above vary by param, bind correctly with conditional
        if ($new_password !== '') {
            // already bound
        } else {
            // The earlier bind was incorrect for types; re-prepare correct statement:
            $sql = "UPDATE users SET full_name = ?, email = ?, contact = ?, admission_year = ? WHERE id = ?";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("ssssii", $full_name, $email, $contact, $admission_year ? $admission_year : 0, $user_id);
            // use 0 for null admission_year (or adjust as desired)
        }

        if ($stmt2->execute()) {
            $success = "Profile updated successfully.";
            // refresh profile values
            $stmt = $conn->prepare("SELECT username, full_name, email, contact, admission_year FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Update failed: " . $stmt2->error;
        }
    }
}
?>
<div class="container">
    <h2>My Profile</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Username (read-only)</label>
        <input type="text" value="<?php echo htmlspecialchars($profile['username']); ?>" disabled>

        <label>Full name</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>">

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">

        <label>Contact</label>
        <input type="text" name="contact" value="<?php echo htmlspecialchars($profile['contact'] ?? ''); ?>">

        <?php if ($role === 'student'): ?>
            <label>Class</label>
            <input type="text" name="student_class" value="<?php echo htmlspecialchars($profile['student_class'] ?? ''); ?>">

            <label>Admission Year</label>
            <input type="number" min="1900" max="<?php echo date('Y'); ?>" name="admission_year" value="<?php echo htmlspecialchars($profile['admission_year'] ?? ''); ?>">
        <?php endif; ?>

        <hr>
        <h4>Change password (optional)</h4>
        <label>New password</label>
        <input type="password" name="new_password" placeholder="Leave blank to keep current password">

        <button type="submit" class="btn">Save Profile</button>
    </form>
</div>

<?php include "includes/footer.php"; ?>
