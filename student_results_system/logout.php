<?php
session_start();
session_unset();
session_destroy();

// Store a flash message in the session before redirect
session_start();
$_SESSION['logout_message'] = "✅ You have been logged out successfully.";

// Redirect to login
header("Location: login.php");
exit;
