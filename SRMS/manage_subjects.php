<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Handle delete
if (isset($_GET['delete'])) {
    $subject_id = (int) $_GET['delete'];
    $sql = "DELETE FROM subjects WHERE subject_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $subject_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

$subjects = mysqli_query($link, "SELECT s.subject_id, s.subject_name, c.class_name FROM subjects s LEFT JOIN classes c ON s.class_id = c.class_id ORDER BY s.subject_id DESC");
?>

<div class="container">
    <h3>Manage Subjects</h3>
    <a href="subject_add.php" class="btn btn-success mb-3">Add Subject</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Class</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($subjects)): ?>
            <tr>
                <td><?php echo (int)$row['subject_id']; ?></td>
                <td><?php echo sanitize($row['subject_name']); ?></td>
                <td><?php echo sanitize($row['class_name']); ?></td>
                <td>
                    <a href="subject_edit.php?id=<?php echo $row['subject_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <a href="manage_subjects.php?delete=<?php echo $row['subject_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>