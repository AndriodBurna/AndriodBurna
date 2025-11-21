<?php
include "config.php";
include "includes/auth.php";

if ($_SESSION['role'] !== 'admin') die("Access denied!");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['subject_name'];
    $code = $_POST['subject_code'];
    $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $code);
    $stmt->execute();
    header("Location: subjects_manage.php");
    exit;
}

include "includes/header.php";
?>
<h2>Add Subject</h2>
<form method="post">
    <label>Subject Name:</label><br>
    <input type="text" name="subject_name" required><br>
    <label>Subject Code:</label><br>
    <input type="text" name="subject_code" required><br><br>
    <button type="submit">Save</button>
</form>
<?php include "includes/footer.php"; ?>
