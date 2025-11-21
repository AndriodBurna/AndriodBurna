<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

function requireRole($roles = []) {
    if (!in_array($_SESSION['role'], $roles)) {
        echo "<h3 style='color:red;'>Access Denied</h3>";
        exit;
    }
}
?>