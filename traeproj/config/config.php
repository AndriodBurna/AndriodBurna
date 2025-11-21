<?php
define('SITE_URL', 'http://localhost/school-management/');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf');

if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 3600);
    ini_set('session.cookie_lifetime', 3600);
    ini_set('session.save_path', 'C:/xampp/tmp');
    session_start();
}

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Nairobi');

// Database configuration
$DB_HOST = 'localhost';
$DB_NAME = 'school_management';
$DB_USER = 'root';
$DB_PASS = '';

// Pagination
$RECORDS_PER_PAGE = 20;

// Email configuration
$SMTP_HOST = 'smtp.gmail.com';
$SMTP_PORT = 587;
$SMTP_USERNAME = '';
$SMTP_PASSWORD = '';

// Security settings
$PASSWORD_MIN_LENGTH = 8;
$MAX_LOGIN_ATTEMPTS = 5;
$LOCKOUT_TIME = 900; // 15 minutes in seconds
?>