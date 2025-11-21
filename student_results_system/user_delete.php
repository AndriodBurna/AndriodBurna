<?php
include "config.php";
include "includes/auth.php";

// Only admins can delete users
if ($_SESSION['role'] !== 'admin') {
    die("Access denied!");
}

if (!isset($_GET['id'])) {
    die("User ID not provided!");
}

$user_id = intval($_GET['id']);

// Prevent admin from deleting themselves (optional but recommended)
if ($user_id == $_SESSION['user_id']) {
    die("⚠️ You cannot delete your own account!");
}

// Delete user
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    header("Location: user_manage.php?deleted=1");
    exit;
} else {
    echo "Error deleting user.";
}
?>
