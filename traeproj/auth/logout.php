<?php
require_once '../includes/session.php';

if (isLoggedIn()) {
    logAction(getCurrentUserId(), 'logout', 'users', getCurrentUserId());
}

logout();
?>