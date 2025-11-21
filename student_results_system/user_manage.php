<?php
include "config.php";
include "includes/auth.php";
// include "includes/header.php";

if ($_SESSION['role'] !== 'admin') {
    die("Access denied!");
}

// Handle deactivate action
if (isset($_GET['deactivate'])) {
    $uid = (int)$_GET['deactivate'];
    $stmt = $conn->prepare("UPDATE users SET active = 0 WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    header("Location: user_manage.php?msg=deactivated");
    exit;
}

// Handle reset password (admin sets new password)
if (isset($_GET['reset'])) {
    $uid = (int)$_GET['reset'];
    $newpass = 'password123'; // or generate random
    $hash = password_hash($newpass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $uid);
    $stmt->execute();
    header("Location: user_manage.php?msg=reset");
    exit;
}

// Handle bulk import from CSV (form posting CSV)
if (isset($_POST['bulk_import'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        $row = 0;
        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $row++;
            if ($row == 1) continue; // skip header
            // adjust according to your CSV columns: username, full_name, role, email, etc.
            if (count($data) >= 4) {
                $username = trim($data[0]);
                $full_name = trim($data[1]);
                $role = trim($data[2]);
                $email = trim($data[3]);
                $password = password_hash('default123', PASSWORD_DEFAULT);
                // Insert
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email, active) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("sssss", $username, $password, $role, $full_name, $email);
                if ($stmt->execute()) {
                    $count++;
                }
            }
        }
        fclose($handle);
        $msg = "Imported $count users.";
    }
}

// Fetch user list
$result = $conn->query("SELECT id, username, full_name, role, email, active FROM users ORDER BY id ASC");

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Student Results Management System</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .courses-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .courses-header {
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .courses-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .course-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #1abc9c;
        }
        
        .course-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .course-table {
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .course-item {
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .course-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .course-title {
            font-size: 1.4rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .course-code {
            background: #1abc9c;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .course-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1abc9c;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .course-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .subject-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .subject-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: white;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 3px solid #1abc9c;
        }
        
        .year-term-badge {
            background: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        
        .mandatory-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        
        .optional-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        @media (max-width: 768px) {
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .course-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .course-details {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>📊 SRMS</h2>
        <a href="index.php">🏠 Dashboard</a>
        <a href="user_manage.php">👥 Manage Users</a>
        <a href="student_manage.php">👨‍🎓 Manage Students</a>
        <a href="subjects_manage.php">📚 Manage Subjects</a>
        <a href="courses_manage.php" class="active">📖 Manage Courses</a>
        <a href="results_list.php">📊 All Results</a>
        <a href="report_class.php">📈 Performance Reports</a>
        <a href="parent_manage.php">👨‍👩‍👧 Parent-Student Link</a>
        <a href="settings.php">⚙️ System Settings</a>
        <a href="logout.php" style="margin-top: auto; background: #e74c3c;">🚪 Logout</a>
    </div>

    <!-- <form method="post" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit" name="bulk_import">Bulk Import Users</button>
    </form> -->

    <table border="1" cellpadding="8" cellspacing="0" style="margin-top:20px;">
        <thead><tr>
            <th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Active</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td><?= $row['active'] ? 'Yes' : 'No' ?></td>
                <td>
                    <a href="user_edit.php?id=<?= $row['id'] ?>">Edit</a> |
                    <?php if ($row['active']): ?>
                        <a href="user_manage.php?deactivate=<?= $row['id'] ?>"
                        onclick="return confirm('Deactivate this user?')">Deactivate</a>
                    <?php else: ?>
                        <span>Inactive</span>
                    <?php endif; ?>
                    | <a href="user_manage.php?reset=<?= $row['id'] ?>">Reset Password</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include "includes/footer.php"; ?>
