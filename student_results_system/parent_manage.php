<?php
include "config.php";
include "includes/auth.php";

if ($_SESSION['role'] !== 'admin') {
    die("Access denied!");
}

// Handle linking
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parent_id = $_POST['parent_id'];
    $student_id = $_POST['student_id'];

    $stmt = $conn->prepare("INSERT INTO parent_child (parent_id, student_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $parent_id, $student_id);
    $stmt->execute();
    $success = "Parent linked to student successfully!";
}

// Fetch parents & students
$parents = $conn->query("SELECT id, username, full_name FROM users WHERE role='parent'");
$students = $conn->query("SELECT id, username, full_name FROM users WHERE role='student'");

// Fetch existing links
$links = $conn->query("SELECT pc.id, p.full_name AS parent_name, s.full_name AS student_name
                       FROM parent_child pc
                       JOIN users p ON pc.parent_id = p.id
                       JOIN users s ON pc.student_id = s.id
                       ORDER BY p.full_name");

include "includes/header.php";
?>

<h2>Parent–Student Linking</h2>

<?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>

<form method="post">
    <label>Parent:</label><br>
    <select name="parent_id" required>
        <option value="">Select Parent</option>
        <?php while ($p = $parents->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name'] ?: $p['username']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Student:</label><br>
    <select name="student_id" required>
        <option value="">Select Student</option>
        <?php while ($s = $students->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name'] ?: $s['username']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <button type="submit">Link Parent to Student</button>
</form>

<hr>

<h3>Existing Links</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Parent</th>
        <th>Student</th>
    </tr>
    <?php while ($link = $links->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($link['parent_name']) ?></td>
            <td><?= htmlspecialchars($link['student_name']) ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<?php include "includes/footer.php"; ?>
