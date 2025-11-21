<?php
include "config.php";
include "includes/auth.php";
include "includes/header.php";

// Only admin and teachers can manage students
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    die("Access denied!");
}

// Fetch all students (without course_id)
$sql = "SELECT id, username, full_name, email, admission_year 
        FROM users WHERE role='student' ORDER BY id ASC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<h2>Manage Students</h2>
<a class="btn" href="student_add.php">➕ Add New Student</a>

<?php if ($result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Admission Year</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['admission_year']); ?></td>
            <td>
                <a href="student_edit.php?id=<?php echo $row['id']; ?>">✏️ Edit</a> |
                <a href="student_delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this student?');">🗑 Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p>No students found.</p>
<?php endif; ?>

<?php include "includes/footer.php"; ?>