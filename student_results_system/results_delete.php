<?php
include "config.php";
include "includes/auth.php";
requireRole(['admin','teacher']);

$id = $_GET['id'];
$sql = "DELETE FROM results WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: results_list.php");
exit;
?>
