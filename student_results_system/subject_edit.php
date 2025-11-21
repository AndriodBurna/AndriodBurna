<?php
include "config.php";
include "includes/auth.php";

if ($_SESSION['role'] !== 'admin') die("Access denied!");

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['subject_name'];
    $code = $_POST['subject_code'];
    $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, subject_code=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $code, $id);
    $stmt->execute();
    header("Location: subjects_manage.php");
    exit;
}

include "includes/header.php";
?>
<h2>Edit Subject</h2>
<form method="post">
    <label>Subject Name:</label><br>
    <input type="text" name="subject_name" value="<?= htmlspecialchars($subject['subject_name']) ?>" required><br>
    <label>Subject Code:</label><br>
    <input type="text" name="subject_code" value="<?= htmlspecialchars($subject['subject_code']) ?>" required><br><br>
    <button type="submit">Update</button>
</form>
<?php include "includes/footer.php"; ?>
