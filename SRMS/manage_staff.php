<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure staff table exists to avoid fatal errors on fresh databases
ensure_table($link, 'staff', "CREATE TABLE IF NOT EXISTS `staff` (\n  `staff_id` int(11) NOT NULL AUTO_INCREMENT,\n  `name` varchar(255) NOT NULL,\n  `role` varchar(100) DEFAULT NULL,\n  `phone` varchar(50) DEFAULT NULL,\n  `email` varchar(255) DEFAULT NULL,\n  `address` varchar(255) DEFAULT NULL,\n  PRIMARY KEY (`staff_id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $role = sanitize($_POST['role'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    if ($name !== '') {
        $stmt = mysqli_prepare($link, "INSERT INTO staff (name, role, phone, email, address) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssss', $name, $role, $phone, $email, $address);
        mysqli_stmt_execute($stmt);
        $msg = '<div class="alert alert-success">Staff added.</div>';
    } else {
        $msg = '<div class="alert alert-warning">Name is required.</div>';
    }
}

$staff = mysqli_query($link, "SELECT staff_id, name, role, phone, email FROM staff ORDER BY name ASC");
?>

<div class="container">
    <h3>Staff Management</h3>
    <?php echo $msg; ?>
    <form method="post" class="card card-body mb-4">
        <div class="form-row">
            <div class="form-group col-md-3"><label>Name</label><input name="name" class="form-control" required></div>
            <div class="form-group col-md-3"><label>Role</label><input name="role" class="form-control"></div>
            <div class="form-group col-md-2"><label>Phone</label><input name="phone" class="form-control"></div>
            <div class="form-group col-md-2"><label>Email</label><input name="email" class="form-control"></div>
            <div class="form-group col-md-2"><label>Address</label><input name="address" class="form-control"></div>
        </div>
        <button type="submit" class="btn btn-primary">Add Staff</button>
    </form>

    <table class="table table-bordered table-sm">
        <thead><tr><th>Name</th><th>Role</th><th>Phone</th><th>Email</th></tr></thead>
        <tbody>
            <?php if ($staff && $staff instanceof mysqli_result): ?>
                <?php while ($s = mysqli_fetch_assoc($staff)): ?>
                    <tr>
                        <td><?php echo sanitize($s['name']); ?></td>
                        <td><?php echo sanitize($s['role']); ?></td>
                        <td><?php echo sanitize($s['phone']); ?></td>
                        <td><?php echo sanitize($s['email']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No staff found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>