<?php
session_start();
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && password_verify($pass, $row['password'])) {
        $_SESSION['user_id']  = $row['id'];
        $_SESSION['role']     = $row['role'];
        $_SESSION['username'] = $row['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid login!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Student Results System</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <h2>🔑 Login</h2>
        <?php 
            if (!empty($_SESSION['logout_message'])) {
                echo "<div class='alert alert-success'>" . $_SESSION['logout_message'] . "</div>";
                unset($_SESSION['logout_message']); // remove after showing once
            }
            if (!empty($error)) {
                echo "<div class='alert alert-error'>$error</div>";
            }
        ?>

        <?php if (!empty($error)) { echo "<div class='alert alert-error'>$error</div>"; } ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don’t have an account? <a href="register.php">Register</a></p>
    </div>
</div>
</body>
</html>
