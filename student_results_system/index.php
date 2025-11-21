<?php
include 'includes/auth.php';
include 'includes/header.php'; 
?>

<div class="container">
    <h2>Dashboard</h2>
    <p>Welcome, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. You are logged in as a <b><?php echo htmlspecialchars($_SESSION["role"]); ?></b>.</p>
</div>

<?php include 'includes/footer.php'; ?>