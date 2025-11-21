<?php
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/checklogin.php';


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: login.php');
    exit;
}

require_once 'includes/header.php';

$role = $_SESSION['role'];

switch ($role) {
    case 'admin':
        include 'admin_dashboard.php';
        break;
    case 'teacher':
        include 'teacher_dashboard.php';
        break;
    case 'student':
        include 'student_dashboard.php';
        break;
    case 'parent':
        include 'parent_dashboard.php';
        break;
    default:
        echo "<h1>Welcome</h1>";
        break;
}

require_once 'includes/footer.php';
?>