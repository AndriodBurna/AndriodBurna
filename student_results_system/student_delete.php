<?php
include "config.php";
include "includes/auth.php";

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    die("Access denied!");
}

$id = intval($_GET['id']);

// Only delete students
$stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='student'");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: student_manage.php?deleted=1");
    exit;
} else {
    die("Error deleting student: " . $stmt->error);
}
