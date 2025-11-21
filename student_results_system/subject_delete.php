<?php
include "config.php";
include "includes/auth.php";

if ($_SESSION['role'] !== 'admin') die("Access denied!");

$id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: subjects_manage.php");
exit;
?>
