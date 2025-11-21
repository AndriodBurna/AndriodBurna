<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';

// Handle delete request
if (isset($_GET['delete'])) {
    $student_id = $_GET['delete'];
    $sql = "DELETE FROM students WHERE student_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Fetch students
$students = mysqli_query($link, "SELECT * FROM students");

?>

<div class="container">
    <h3>Manage Students</h3>
    <a href="student_add.php" class="btn btn-success mb-3">Add Student</a>
    <a href="student_import.php" class="btn btn-info mb-3 ml-2">Bulk Import</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>UID</th>
                <th>Name</th>
                <th>Gender</th>
                <th>DOB</th>
                <th>Class ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($students)): ?>
            <tr>
                <td><?php echo $row['student_id']; ?></td>
                <td><?php echo htmlspecialchars($row['student_uid'] ?? ''); ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['gender']; ?></td>
                <td><?php echo $row['dob']; ?></td>
                <td><?php echo $row['class_id']; ?></td>
                <td>
                    <a href="student_edit.php?id=<?php echo $row['student_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <a href="manage_students.php?delete=<?php echo $row['student_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>