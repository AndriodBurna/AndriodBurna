<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';

// Handle delete request
if (isset($_GET['delete'])) {
    $class_id = $_GET['delete'];
    $sql = "DELETE FROM classes WHERE class_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $class_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Fetch classes
$classes = mysqli_query($link, "SELECT * FROM classes");

?>

<div class="container">
    <h3>Manage Classes</h3>
    <a href="class_add.php" class="btn btn-success mb-3">Add Class</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Class Name</th>
                <th>Stream</th>
                <th>Year</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($classes)): ?>
            <tr>
                <td><?php echo $row['class_id']; ?></td>
                <td><?php echo $row['class_name']; ?></td>
                <td><?php echo $row['stream']; ?></td>
                <td><?php echo $row['year']; ?></td>
                <td>
                    <a href="class_edit.php?id=<?php echo $row['class_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <a href="manage_classes.php?delete=<?php echo $row['class_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>