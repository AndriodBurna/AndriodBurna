<?php
include "config.php";
include "includes/auth.php";
include "includes/header.php";

if ($_SESSION['role'] !== 'admin') {
    die("Access denied!");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT username, full_name, email, role, active FROM users WHERE id = ?");
$stmt->bind_param("i",$id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) die("User not found");

$success = $error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $active = isset($_POST['active']) ? 1 : 0;

    $stmt2 = $conn->prepare("UPDATE users SET full_name=?, email=?, role=?, active=? WHERE id=?");
    $stmt2->bind_param("sssis", $full_name, $email, $role, $active, $id);
    if ($stmt2->execute()) {
        $success = "Updated successfully";
    } else {
        $error = "Error: " . $stmt2->error;
    }
}

?>
<h2>Edit User</h2>
<?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>

<form method="post">
    <label>Username (readonly)</label><br>
    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled><br>

    <label>Full Name</label><br>
    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>"><br>

    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"><br>

    <label>Role</label><br>
    <select name="role">
        <?php 
        $roles = ['admin','teacher','student','parent'];
        foreach($roles as $r): ?>
            <option value="<?= $r ?>" <?= ($user['role']==$r) ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
    </select><br>

    <label>Active?</label>
    <input type="checkbox" name="active" <?= $user['active'] ? 'checked' : '' ?>><br>

    <a href="user_manage.php"><button type="submit">Save Changes</button></a>
</form>

<?php include "includes/footer.php"; ?>
