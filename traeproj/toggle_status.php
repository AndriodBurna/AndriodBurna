<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireRole(['admin', 'principal']);

header('Content-Type: application/json');

$database = new Database();
$pdo = $database->getConnection();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $response['message'] = 'Invalid request token. Please refresh the page and try again.';
        echo json_encode($response);
        exit();
    }
    
    // Get and validate input
    $classId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
    
    if ($classId === 0) {
        $response['message'] = 'Invalid class ID.';
        echo json_encode($response);
        exit();
    }
    
    if (!in_array($action, ['activate', 'deactivate'])) {
        $response['message'] = 'Invalid action.';
        echo json_encode($response);
        exit();
    }
    
    try {
        // Check if class exists
        $checkStmt = $pdo->prepare("SELECT id, class_name, is_active FROM classes WHERE id = ?");
        $checkStmt->execute([$classId]);
        $class = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            $response['message'] = 'Class not found.';
            echo json_encode($response);
            exit();
        }
        
        // Determine new status
        $newStatus = ($action === 'activate') ? 1 : 0;
        $currentStatus = (int)$class['is_active'];
        
        // Check if status is already the same
        if ($currentStatus === $newStatus) {
            $statusText = $newStatus ? 'active' : 'inactive';
            $response['message'] = 'Class is already ' . $statusText . '.';
            echo json_encode($response);
            exit();
        }
        
        // Update class status
        $updateStmt = $pdo->prepare("
            UPDATE classes 
            SET is_active = ?, updated_at = NOW(), updated_by = ?
            WHERE id = ?
        ");
        
        $updateStmt->execute([$newStatus, getCurrentUserId(), $classId]);
        
        // Log the action
        $actionType = $action . '_class';
        logAction(getCurrentUserId(), $actionType, 'classes', $classId);
        
        $response['success'] = true;
        $response['message'] = 'Class "' . htmlspecialchars($class['class_name']) . '" has been ' . 
                              ($newStatus ? 'activated' : 'deactivated') . ' successfully.';
        
    } catch (Exception $e) {
        $response['message'] = 'Error updating class status: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
?>