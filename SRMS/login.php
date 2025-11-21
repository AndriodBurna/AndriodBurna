<?php
session_start();
require_once 'config.php';

// If user is already logged in, redirect to index
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('location: index.php');
    exit;
}

$username = $password = '';
$username_err = $password_err = $login_err = '';
$register_success = '';

// Show success message after registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $register_success = 'Account created successfully. Please log in.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate username
    if (empty(trim($_POST['username']))) {
        $username_err = 'Please enter username.';
    } else {
        $username = trim($_POST['username']);
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter your password.';
    } else {
        $password = trim($_POST['password']);
    }

    // Check credentials
    if (empty($username_err) && empty($password_err)) {
        $sql = 'SELECT user_id, username, password, role FROM users WHERE username = ?';

        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                mysqli_stmt_bind_param($stmt, 's', $param_username);
                $param_username = $username;

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);

                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role);
                        if (mysqli_stmt_fetch($stmt)) {
                            if (password_verify($password, $hashed_password)) {
                                // session_start(); // Already started at the top of the file

                                $_SESSION['loggedin'] = true;
                                $_SESSION['std_id'] = $id;
                                $_SESSION['username'] = $username;
                                $_SESSION['role'] = $role;

                                header('location: index.php');
                            } else {
                                $login_err = 'Invalid username or password.';
                            }
                        }
                    } else {
                        $login_err = 'Invalid username or password.';
                    }
                } else {
                    echo 'Oops! Something went wrong. Please try again later.';
                }
                mysqli_stmt_close($stmt);
            }
        }
        mysqli_close($mysqli);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - SRMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <h2>SRMS Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        if(!empty($register_success)){
            echo '<div class="alert alert-success">' . $register_success . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <div class="form-group">
                <small>Don\'t have an account? <a href="register.php">Register</a></small>
            </div>
        </form>
    </div>
</body>
</html>