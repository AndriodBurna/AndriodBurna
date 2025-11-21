<?php
include "config.php";
include "includes/auth.php";
include "includes/header.php";

if ($_SESSION['role'] !== 'admin') {
    die("Access denied!");
}

$success = $error = "";

// Add subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $name = trim($_POST['subject_name']);
    $code = trim($_POST['subject_code']);
    $credits = intval($_POST['credits']);
    $desc = trim($_POST['description']);
    if ($name === "") {
        $error = "Subject name required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, credits, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $name, $code, $credits, $desc);
        if ($stmt->execute()) {
            $success = "Subject added.";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $sid = (int)$_GET['delete'];
    $conn->query("DELETE FROM subjects WHERE id = $sid");
    header("Location: subjects_manage.php?msg=deleted");
    exit;
}

// List
$result = $conn->query("SELECT id, subject_name, subject_code, credits, description FROM subjects ORDER BY subject_name");

?>
<h2>Manage Subjects</h2>
<?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>

<form method="post">
    <label>Subject Name</label><br>
    <input type="text" name="subject_name" required><br>
    <label>Subject Code</label><br>
    <input type="text" name="subject_code"><br>
    <label>Credits</label><br>
    <input type="number" name="credits" value="3" min="1"><br>
    <label>Description</label><br>
    <input type="text" name="description"><br>
    <button type="submit" name="add_subject">Add Subject</button>
</form>

<table border="1" cellpadding="8" cellspacing="0" style="margin-top:20px;">
    <thead><tr>
        <th>ID</th><th>Name</th><th>Code</th><th>Credits</th><th>Description</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['subject_name']) ?></td>
            <td><?= htmlspecialchars($row['subject_code']) ?></td>
            <td><?= htmlspecialchars($row['credits']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td>
                <a href="subjects_edit.php?id=<?= $row['id'] ?>">Edit</a> |
                <a href="subjects_manage.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this subject?')">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php include "includes/footer.php"; ?>
