<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles)) {
        header('Location: unauthorized.php');
        exit();
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

function checkSessionTimeout() {
    $timeout = 1800; // 30 minutes
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}

if (isLoggedIn()) {
    checkSessionTimeout();
}
?>