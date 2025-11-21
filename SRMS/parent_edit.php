<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';

$parent_id = $_GET['id'];
$name = $phone = $email = $address = $contact_preferences = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $contact_preferences = trim($_POST['contact_preferences']);

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($errors)) {
        $sql = "UPDATE parents SET name = ?, phone = ?, email = ?, address = ?, contact_preferences = ? WHERE parent_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssi", $name, $phone, $email, $address, $contact_preferences, $parent_id);
            if (mysqli_stmt_execute($stmt)) {
                header('location: manage_parents.php');
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    $sql = "SELECT * FROM parents WHERE parent_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $parent_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $parent = mysqli_fetch_assoc($result);
        $name = $parent['name'];
        $phone = $parent['phone'];
        $email = $parent['email'];
        $address = $parent['address'];
        $contact_preferences = $parent['contact_preferences'] ?? '';
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container">
    <h3>Edit Parent</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form action="parent_edit.php?id=<?php echo $parent_id; ?>" method="post">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo $name; ?>">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo $phone; ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" class="form-control" value="<?php echo $address; ?>">
        </div>
        <div class="form-group">
            <label>Contact Preferences</label>
            <textarea name="contact_preferences" class="form-control" rows="4" placeholder="e.g., SMS for urgent, email for general"><?php echo htmlspecialchars($contact_preferences); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update Parent</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>