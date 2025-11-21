<?php
require_once __DIR__ . '/../config/config.php';

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateStudentID() {
    $year = date('Y');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return 'STU' . $year . $random;
}

function generateTeacherID() {
    $year = date('Y');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return 'TCH' . $year . $random;
}

function calculateGrade($marks) {
    if ($marks >= 90) return 'A+';
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B+';
    if ($marks >= 60) return 'B';
    if ($marks >= 50) return 'C+';
    if ($marks >= 40) return 'C';
    if ($marks >= 30) return 'D';
    return 'F';
}

function calculateGradePoint($grade) {
    $gradePoints = [
        'A+' => 4.00,
        'A' => 3.75,
        'B+' => 3.50,
        'B' => 3.00,
        'C+' => 2.50,
        'C' => 2.00,
        'D' => 1.50,
        'F' => 0.00
    ];
    return $gradePoints[$grade] ?? 0.00;
}

function uploadFile($file, $uploadDir) {
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    $fileName = basename($file['name']);
    $fileSize = $file['size'];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
    }
    
    if ($fileSize > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit of 5MB'];
    }
    
    $newFileName = uniqid() . '.' . $fileType;
    $uploadPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'file_name' => $newFileName, 'file_path' => $uploadPath];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

function logAction($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    global $pdo;
    
    $sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $userId,
        $action,
        $tableName,
        $recordId,
        json_encode($oldValues),
        json_encode($newValues),
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function sendEmail($to, $subject, $body, $from = null) {
    if ($from === null) {
        $from = 'noreply@school.com';
    }
    
    $headers = "From: {$from}\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $body, $headers);
}

function formatDate($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

function getCurrentAcademicTerm($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM academic_terms WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function checkUserRole($allowedRoles) {
    if (!isset($_SESSION['user_role'])) {
        header('Location: login.php');
        exit();
    }
    
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        header('Location: unauthorized.php');
        exit();
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getPagination($page, $totalRecords, $recordsPerPage = 20) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $offset = ($page - 1) * $recordsPerPage;
    
    return [
        'offset' => $offset,
        'total_pages' => $totalPages,
        'current_page' => $page
    ];
}
?>