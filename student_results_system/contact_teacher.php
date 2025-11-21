<?php
include "config.php";
include "includes/auth.php";

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// ================== DELETE MESSAGE ==================
if (isset($_GET['delete']) && isset($_GET['msg_id'])) {
    $msg_id = intval($_GET['msg_id']);
    
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND (receiver_id = ? OR sender_id = ?)");
    $stmt->bind_param("iii", $msg_id, $current_user_id, $current_user_id);
    
    if ($stmt->execute()) {
        $success = "Message deleted successfully!";
    } else {
        $error = "Error deleting message.";
    }
    $stmt->close();
    
    header("Location: contact_teacher.php");
    exit;
}

// ================== TOGGLE MESSAGE STATUS ==================
if (isset($_GET['toggle']) && isset($_GET['msg_id'])) {
    $msg_id = intval($_GET['msg_id']);
    $new_status = ($_GET['toggle'] === 'read') ? 'read' : 'unread';

    $stmt = $conn->prepare("UPDATE messages SET status = ? WHERE id = ? AND receiver_id = ?");
    $stmt->bind_param("sii", $new_status, $msg_id, $current_user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: contact_teacher.php");
    exit;
}

// ================== MARK ALL AS READ ==================
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE messages SET status = 'read' WHERE receiver_id = ? AND status = 'unread'");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: contact_teacher.php");
    exit;
}

// ================== SEND MESSAGE ==================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['receiver_id'], $_POST['message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $subject = !empty($_POST['subject']) ? trim($_POST['subject']) : 'No Subject';
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $current_user_id, $receiver_id, $subject, $message);
        
        if ($stmt->execute()) {
            $success = "Message sent successfully!";
        } else {
            $error = "Error sending message: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Message cannot be empty.";
    }
}

// ================== FETCH CURRENT USER INFO ==================
$current_user_role = '';
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $current_user_id);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $current_user_role = $row['role'];
    }
}
$stmt->close();

// ================== FETCH ALL USERS ==================
$users = [];
$result = $conn->query("SELECT id, username, role FROM users ORDER BY role, username");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['id'] != $current_user_id) {
            $users[] = $row;
        }
    }
}

// ================== FETCH UNREAD MESSAGE COUNT ==================
$unread_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM messages WHERE receiver_id = ? AND status = 'unread'");
$stmt->bind_param("i", $current_user_id);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $unread_count = $row['total'];
    }
}
$stmt->close();

// ================== FETCH TOTAL MESSAGE COUNTS ==================
$total_inbox = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM messages WHERE receiver_id = ?");
$stmt->bind_param("i", $current_user_id);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $total_inbox = $row['total'];
    }
}
$stmt->close();

$total_sent = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM messages WHERE sender_id = ?");
$stmt->bind_param("i", $current_user_id);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $total_sent = $row['total'];
    }
}
$stmt->close();

// ================== FETCH INBOX ==================
$inbox = false;
$stmt = $conn->prepare("SELECT m.*, u.username AS sender_name, u.role AS sender_role
                        FROM messages m
                        JOIN users u ON m.sender_id = u.id
                        WHERE m.receiver_id = ?
                        ORDER BY m.created_at DESC
                        LIMIT 50");
if ($stmt) {
    $stmt->bind_param("i", $current_user_id);
    if ($stmt->execute()) {
        $inbox = $stmt->get_result();
    }
    $stmt->close();
}

// ================== FETCH OUTBOX ==================
$outbox = false;
$stmt = $conn->prepare("SELECT m.*, u.username AS receiver_name, u.role AS receiver_role
                        FROM messages m
                        JOIN users u ON m.receiver_id = u.id
                        WHERE m.sender_id = ?
                        ORDER BY m.created_at DESC
                        LIMIT 50");
if ($stmt) {
    $stmt->bind_param("i", $current_user_id);
    if ($stmt->execute()) {
        $outbox = $stmt->get_result();
    }
    $stmt->close();
}
?>

<?php include "includes/header.php"; ?>

<style>
    * {
        box-sizing: border-box;
    }
    
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .main-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .page-title {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .header-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 15px;
        border-radius: 15px;
        color: white;
        font-size: 24px;
    }
    
    .page-title h1 {
        font-size: 32px;
        color: #333;
        margin: 0;
    }
    
    .page-title p {
        color: #666;
        margin: 5px 0 0 0;
        font-size: 14px;
    }
    
    .header-stats {
        display: flex;
        gap: 20px;
    }
    
    .stat-badge {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 20px;
        border-radius: 12px;
        background: #f8f9fa;
    }
    
    .stat-badge .label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .stat-badge .value {
        font-size: 24px;
        font-weight: bold;
        color: #667eea;
    }
    
    .stat-badge.unread .value {
        color: #fa709a;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 2px solid #c3e6cb;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 2px solid #f5c6cb;
    }
    
    .tab-navigation {
        background: white;
        border-radius: 20px;
        padding: 10px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        gap: 10px;
    }
    
    .tab-btn {
        flex: 1;
        padding: 15px 30px;
        border: none;
        background: transparent;
        color: #666;
        font-size: 16px;
        font-weight: 600;
        border-radius: 15px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .tab-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .tab-btn:hover:not(.active) {
        background: #f5f5f5;
    }
    
    .tab-badge {
        background: rgba(255,255,255,0.3);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .tab-btn.active .tab-badge {
        background: rgba(255,255,255,0.2);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 22px;
        font-weight: bold;
        color: #333;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
        font-size: 14px;
    }
    
    .form-group input[type="text"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-group textarea {
        min-height: 120px;
        resize: vertical;
    }
    
    .btn {
        padding: 12px 25px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
    
    .btn-success {
        background: #43e97b;
        color: white;
    }
    
    .btn-danger {
        background: #fa709a;
        color: white;
        padding: 8px 15px;
        font-size: 12px;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    
    .search-box input {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 14px;
    }
    
    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
    
    .message-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .message-item {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        border-left: 4px solid #667eea;
        transition: all 0.3s;
        position: relative;
    }
    
    .message-item:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .message-item.unread {
        background: #fff3e0;
        border-left-color: #fa709a;
    }
    
    .message-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 10px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .message-from {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 16px;
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
    }
    
    .user-name {
        font-weight: 600;
        color: #333;
        font-size: 15px;
    }
    
    .user-role {
        font-size: 12px;
        color: #666;
        text-transform: capitalize;
    }
    
    .message-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 5px;
    }
    
    .message-time {
        font-size: 12px;
        color: #999;
    }
    
    .message-status {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .message-status.unread {
        background: #fa709a;
        color: white;
    }
    
    .message-status.read {
        background: #43e97b;
        color: white;
    }
    
    .message-subject {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        font-size: 15px;
    }
    
    .message-body {
        color: #555;
        line-height: 1.6;
        margin-bottom: 15px;
        font-size: 14px;
    }
    
    .message-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .action-link {
        font-size: 13px;
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .action-link:hover {
        color: #764ba2;
        text-decoration: underline;
    }
    
    .reply-form {
        margin-top: 15px;
        padding: 15px;
        background: white;
        border-radius: 10px;
        border: 2px solid #e0e0e0;
        display: none;
    }
    
    .reply-form.active {
        display: block;
    }
    
    .reply-form textarea {
        width: 100%;
        padding: 10px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        min-height: 80px;
        resize: vertical;
        font-family: inherit;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .empty-state h3 {
        color: #333;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #999;
    }
    
    .filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .header-stats {
            width: 100%;
            justify-content: space-between;
        }
        
        .tab-navigation {
            flex-direction: column;
        }
        
        .message-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .message-meta {
            align-items: flex-start;
        }
        
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="main-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <span class="header-icon">💬</span>
            <div>
                <h1>Messaging System</h1>
                <p>Communicate with teachers and students</p>
            </div>
        </div>
        <div class="header-stats">
            <div class="stat-badge unread">
                <span class="label">Unread</span>
                <span class="value"><?= $unread_count ?></span>
            </div>
            <div class="stat-badge">
                <span class="label">Total Inbox</span>
                <span class="value"><?= $total_inbox ?></span>
            </div>
            <div class="stat-badge">
                <span class="label">Sent</span>
                <span class="value"><?= $total_sent ?></span>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            ✕ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-btn active" onclick="switchTab('compose')">
            ✉️ Compose
        </button>
        <button class="tab-btn" onclick="switchTab('inbox')">
            📥 Inbox
            <?php if ($unread_count > 0): ?>
                <span class="tab-badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </button>
        <button class="tab-btn" onclick="switchTab('sent')">
            📤 Sent Messages
        </button>
    </div>

    <!-- Compose Tab -->
    <div id="compose-tab" class="tab-content active">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    ✉️ Compose New Message
                </div>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="receiver_id">Select Recipient *</label>
                    <select name="receiver_id" id="receiver_id" required>
                        <option value="">-- Select User --</option>
                        <?php 
                        $current_role = '';
                        foreach ($users as $user): 
                            if ($current_role !== $user['role']) {
                                if ($current_role !== '') echo '</optgroup>';
                                echo '<optgroup label="' . ucfirst($user['role']) . 's">';
                                $current_role = $user['role'];
                            }
                        ?>
                            <option value="<?= $user['id'] ?>">
                                <?= htmlspecialchars($user['username']) ?>
                            </option>
                        <?php 
                        endforeach; 
                        if ($current_role !== '') echo '</optgroup>';
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" placeholder="Enter message subject" maxlength="100">
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea name="message" id="message" required placeholder="Type your message here..."></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        📤 Send Message
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        🔄 Clear Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inbox Tab -->
    <div id="inbox-tab" class="tab-content">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    📥 Your Inbox
                </div>
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_all_read=1" class="btn btn-success btn-sm">
                        ✓ Mark All as Read
                    </a>
                <?php endif; ?>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInbox" placeholder="Search messages..." onkeyup="filterMessages('inbox')">
            </div>

            <?php if ($inbox && $inbox->num_rows > 0): ?>
                <div class="message-list" id="inboxList">
                    <?php while ($msg = $inbox->fetch_assoc()): ?>
                        <div class="message-item <?= $msg['status'] === 'unread' ? 'unread' : '' ?> inbox-message" data-content="<?= htmlspecialchars(strtolower($msg['sender_name'] . ' ' . $msg['subject'] . ' ' . $msg['message'])) ?>">
                            <div class="message-header">
                                <div class="message-from">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($msg['sender_name'], 0, 1)) ?>
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name"><?= htmlspecialchars($msg['sender_name']) ?></span>
                                        <span class="user-role"><?= htmlspecialchars($msg['sender_role']) ?></span>
                                    </div>
                                </div>
                                <div class="message-meta">
                                    <span class="message-time"><?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?></span>
                                    <span class="message-status <?= $msg['status'] ?>">
                                        <?= ucfirst($msg['status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if (!empty($msg['subject'])): ?>
                                <div class="message-subject">
                                    📌 <?= htmlspecialchars($msg['subject']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="message-body">
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            </div>
                            
                            <div class="message-actions">
                                <?php 
                                $toggleAction = ($msg['status'] === 'unread') ? 'read' : 'unread';
                                $toggleText = ($msg['status'] === 'unread') ? 'Mark as Read' : 'Mark as Unread';
                                ?>
                                <a href="?toggle=<?= $toggleAction ?>&msg_id=<?= $msg['id'] ?>" class="action-link">
                                    <?= $toggleText ?>
                                </a>
                                <a href="#" class="action-link" onclick="toggleReply(event, <?= $msg['id'] ?>)">
                                    💬 Reply
                                </a>
                                <a href="?delete=1&msg_id=<?= $msg['id'] ?>" class="action-link" style="color: #fa709a;" onclick="return confirm('Are you sure you want to delete this message?')">
                                    🗑️ Delete
                                </a>
                            </div>
                            
                            <!-- Reply Form -->
                            <div class="reply-form" id="reply-<?= $msg['id'] ?>">
                                <form method="POST" action="">
                                    <input type="hidden" name="receiver_id" value="<?= $msg['sender_id'] ?>">
                                    <input type="hidden" name="subject" value="RE: <?= htmlspecialchars($msg['subject']) ?>">
                                    <textarea name="message" required placeholder="Write your reply..."></textarea>
                                    <div style="margin-top: 10px; display: flex; gap: 10px;">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            📤 Send Reply
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleReply(event, <?= $msg['id'] ?>)">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>No Messages</h3>
                    <p>Your inbox is empty. You'll see new messages here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sent Messages Tab -->
    <div id="sent-tab" class="tab-content">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    📤 Sent Messages
                </div>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchSent" placeholder="Search sent messages..." onkeyup="filterMessages('sent')">
            </div>

            <?php if ($outbox && $outbox->num_rows > 0): ?>
                <div class="message-list" id="sentList">
                    <?php while ($msg = $outbox->fetch_assoc()): ?>
                        <div class="message-item sent-message" data-content="<?= htmlspecialchars(strtolower($msg['receiver_name'] . ' ' . $msg['subject'] . ' ' . $msg['message'])) ?>">
                            <div class="message-header">
                                <div class="message-from">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($msg['receiver_name'], 0, 1)) ?>
                                    </div>
                                    <div class="user-info">
                                        <span class="user-name">To: <?= htmlspecialchars($msg['receiver_name']) ?></span>
                                        <span class="user-role"><?= htmlspecialchars($msg['receiver_role']) ?></span>
                                    </div>
                                </div>
                                <div class="message-meta">
                                    <span class="message-time"><?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?></span>
                                    <span class="message-status read">Sent</span>
                                </div>
                            </div>
                            
                            <?php if (!empty($msg['subject'])): ?>
                                <div class="message-subject">
                                    📌 <?= htmlspecialchars($msg['subject']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="message-body">
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            </div>
                            
                            <div class="message-actions">
                                <a href="?delete=1&msg_id=<?= $msg['id'] ?>" class="action-link" style="color: #fa709a;" onclick="return confirm('Are you sure you want to delete this message?')">
                                    🗑️ Delete
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📮</div>
                    <h3>No Sent Messages</h3>
                    <p>You haven't sent any messages yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Tab Switching
function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.tab-btn').classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + '-tab').classList.add('active');
}

// Toggle Reply Form
function toggleReply(event, messageId) {
    event.preventDefault();
    const replyForm = document.getElementById('reply-' + messageId);
    if (replyForm.classList.contains('active')) {
        replyForm.classList.remove('active');
    } else {
        // Close all other reply forms
        document.querySelectorAll('.reply-form').forEach(form => {
            form.classList.remove('active');
        });
        replyForm.classList.add('active');
    }
}

// Filter Messages
function filterMessages(type) {
    const searchInput = type === 'inbox' ? 'searchInbox' : 'searchSent';
    const messageList = type === 'inbox' ? 'inboxList' : 'sentList';
    const messageClass = type === 'inbox' ? 'inbox-message' : 'sent-message';
    
    const searchTerm = document.getElementById(searchInput).value.toLowerCase();
    const messages = document.querySelectorAll('.' + messageClass);
    
    messages.forEach(message => {
        const content = message.getAttribute('data-content');
        if (content.includes(searchTerm)) {
            message.style.display = '';
        } else {
            message.style.display = 'none';
        }
    });
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// Character counter for message textarea
const messageTextarea = document.getElementById('message');
if (messageTextarea) {
    const charCounter = document.createElement('div');
    charCounter.style.cssText = 'text-align: right; font-size: 12px; color: #999; margin-top: 5px;';
    messageTextarea.parentNode.appendChild(charCounter);
    
    const updateCounter = () => {
        const length = messageTextarea.value.length;
        charCounter.textContent = `${length} characters`;
        if (length > 500) {
            charCounter.style.color = '#fa709a';
        } else {
            charCounter.style.color = '#999';
        }
    };
    
    messageTextarea.addEventListener('input', updateCounter);
    updateCounter();
}

// Confirm before leaving page with unsaved message
let formChanged = false;
const composeForm = document.querySelector('#compose-tab form');
if (composeForm) {
    composeForm.addEventListener('input', () => {
        formChanged = true;
    });
    
    composeForm.addEventListener('submit', () => {
        formChanged = false;
    });
    
    window.addEventListener('beforeunload', (e) => {
        if (formChanged && messageTextarea.value.trim() !== '') {
            e.preventDefault();
            e.returnValue = '';
        }
    });
}

// Auto-resize textareas
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

// Add smooth scroll behavior
document.documentElement.style.scrollBehavior = 'smooth';

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const activeTab = document.querySelector('.tab-content.active');
        const searchInput = activeTab.querySelector('input[type="text"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + Enter to submit form
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        const activeForm = document.querySelector('.tab-content.active form');
        if (activeForm && document.activeElement.tagName === 'TEXTAREA') {
            e.preventDefault();
            activeForm.submit();
        }
    }
});

// Add tooltip for shortcuts
const composeTab = document.querySelector('#compose-tab');
if (composeTab) {
    const tip = document.createElement('div');
    tip.style.cssText = 'font-size: 12px; color: #999; margin-top: 10px; text-align: center;';
    tip.innerHTML = '💡 <strong>Tip:</strong> Press Ctrl+Enter to send message quickly';
    composeTab.querySelector('form').appendChild(tip);
}

// Notification sound (optional - can be disabled)
function playNotificationSound() {
    // Uncomment if you want to add sound notification
    // const audio = new Audio('notification.mp3');
    // audio.play().catch(e => console.log('Audio play failed'));
}

// Mark message as read when clicked (for better UX)
document.querySelectorAll('.message-item.unread').forEach(item => {
    item.addEventListener('click', function(e) {
        // Don't mark as read if clicking on links or buttons
        if (!e.target.closest('a') && !e.target.closest('button')) {
            const messageId = this.querySelector('a[href*="msg_id"]')?.href.match(/msg_id=(\d+)/)?.[1];
            if (messageId && this.classList.contains('unread')) {
                // Visually update immediately
                this.classList.remove('unread');
                this.querySelector('.message-status').textContent = 'Read';
                this.querySelector('.message-status').classList.remove('unread');
                this.querySelector('.message-status').classList.add('read');
                
                // Update in background
                fetch(`?toggle=read&msg_id=${messageId}`, { method: 'GET' })
                    .catch(e => console.log('Failed to update status'));
            }
        }
    });
});

// Add loading state to buttons
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Sending...';
            
            // Re-enable after 3 seconds (in case of issues)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 3000);
        }
    });
});

// Show success animation
const successAlert = document.querySelector('.alert-success');
if (successAlert) {
    successAlert.style.animation = 'slideIn 0.5s ease-out';
}

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Add unread count to page title
function updatePageTitle() {
    const unreadCount = <?= $unread_count ?>;
    if (unreadCount > 0) {
        document.title = `(${unreadCount}) Messages - Student Management System`;
    } else {
        document.title = 'Messages - Student Management System';
    }
}
updatePageTitle();

// Quick reply feature - expand textarea on focus
document.querySelectorAll('.reply-form textarea').forEach(textarea => {
    textarea.addEventListener('focus', function() {
        this.style.minHeight = '120px';
    });
});

// Add "New Message" indicator animation
const unreadMessages = document.querySelectorAll('.message-item.unread');
unreadMessages.forEach((msg, index) => {
    msg.style.animation = `slideIn 0.3s ease-out ${index * 0.1}s both`;
});

// Show confirmation before deleting
document.querySelectorAll('a[href*="delete"]').forEach(link => {
    link.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});

// Prevent accidental form submission
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('keydown', function(e) {
        // Prevent Enter key from submitting form unless Ctrl/Cmd is held
        if (e.key === 'Enter' && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
            // Allow normal line break
            return;
        }
    });
});

// Add real-time validation
const receiverSelect = document.getElementById('receiver_id');
const subjectInput = document.getElementById('subject');

if (receiverSelect) {
    receiverSelect.addEventListener('change', function() {
        if (this.value) {
            this.style.borderColor = '#43e97b';
        } else {
            this.style.borderColor = '#e0e0e0';
        }
    });
}

if (subjectInput) {
    subjectInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            this.style.borderColor = '#43e97b';
        } else {
            this.style.borderColor = '#e0e0e0';
        }
    });
}

// Auto-save draft to localStorage (optional feature)
const DRAFT_KEY = 'message_draft';
const draftTextarea = document.getElementById('message');
const draftReceiver = document.getElementById('receiver_id');
const draftSubject = document.getElementById('subject');

if (draftTextarea && draftReceiver && draftSubject) {
    // Load draft on page load
    const savedDraft = localStorage.getItem(DRAFT_KEY);
    if (savedDraft) {
        try {
            const draft = JSON.parse(savedDraft);
            if (confirm('You have an unsaved draft. Would you like to restore it?')) {
                draftReceiver.value = draft.receiver || '';
                draftSubject.value = draft.subject || '';
                draftTextarea.value = draft.message || '';
            } else {
                localStorage.removeItem(DRAFT_KEY);
            }
        } catch (e) {
            console.log('Error loading draft');
        }
    }
    
    // Save draft on input
    let saveTimeout;
    function saveDraft() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const draft = {
                receiver: draftReceiver.value,
                subject: draftSubject.value,
                message: draftTextarea.value
            };
            if (draft.message.trim() !== '') {
                localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
            }
        }, 1000);
    }
    
    draftTextarea.addEventListener('input', saveDraft);
    draftReceiver.addEventListener('change', saveDraft);
    draftSubject.addEventListener('input', saveDraft);
    
    // Clear draft on successful submission
    composeForm.addEventListener('submit', () => {
        localStorage.removeItem(DRAFT_KEY);
    });
}

console.log('✉️ Messaging System loaded successfully!');
</script>

<?php include "includes/footer.php"; ?>