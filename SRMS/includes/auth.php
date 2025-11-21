<?php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: login.php');
    exit;
}

function require_role($role) {
    if ($_SESSION['role'] !== $role) {
        // Redirect to a generic access denied page or back to the dashboard
        header('location: index.php');
        exit;
    }
}

function require_any_role($roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
        header('location: index.php');
        exit;
    }
}
?>