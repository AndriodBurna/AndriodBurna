<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';

// Handle delete request
if (isset($_GET['delete'])) {
    $teacher_id = $_GET['delete'];
    $sql = "DELETE FROM teachers WHERE teacher_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $teacher_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Fetch teachers
$teachers = mysqli_query($link, "SELECT * FROM teachers");

?>

<div class="container">
    <h3>Manage Teachers</h3>
    <a href="teacher_add.php" class="btn btn-success mb-3">Add Teacher</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($teachers)): ?>
            <tr>
                <td><?php echo $row['teacher_id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['gender']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['phone']; ?></td>
                <td>
                    <a href="teacher_edit.php?id=<?php echo $row['teacher_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <a href="manage_teachers.php?delete=<?php echo $row['teacher_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>