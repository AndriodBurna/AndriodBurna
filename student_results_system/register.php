<?php
session_start();
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $user, $pass, $role);

    if ($stmt->execute()) {
        $success = "User registered successfully! You can login now.";
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Student Results System</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <h2>📝 Register</h2>
        <?php 
            if (!empty($error)) { echo "<div class='alert alert-error'>$error</div>"; } 
            if (!empty($success)) { echo "<div class='alert alert-success'>$success</div>"; } 
        ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="">-- Select Role --</option>
                <option value="admin">Admin</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
                <option value="parent">Parent</option>
            </select>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>
</body>
</html>
