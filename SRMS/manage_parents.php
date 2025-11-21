<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';

// Handle delete request
if (isset($_GET['delete'])) {
    $parent_id = $_GET['delete'];
    $sql = "DELETE FROM parents WHERE parent_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $parent_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Fetch parents
$parents = mysqli_query($link, "SELECT * FROM parents");

?>

<div class="container">
    <h3>Manage Parents</h3>
    <div class="mb-3">
        <a href="parent_add.php" class="btn btn-success">Add Parent</a>
        <a href="parent_link_bulk.php" class="btn btn-info ml-2">Bulk Link Students</a>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Address</th>
                <th>Contact Prefs</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($parents)): ?>
            <tr>
                <td><?php echo $row['parent_id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['phone']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['address']; ?></td>
                <td><?php echo htmlspecialchars($row['contact_preferences'] ?? ''); ?></td>
                <td>
                    <a href="parent_edit.php?id=<?php echo $row['parent_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <a href="manage_parents.php?delete=<?php echo $row['parent_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>