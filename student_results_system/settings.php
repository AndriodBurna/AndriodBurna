<?php
include "config.php";
include "includes/auth.php";

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['update_system_info'])) {
        // Update system information
        $system_name = trim($_POST['system_name']);
        $academic_year = trim($_POST['academic_year']);
        $term = trim($_POST['term']);
        $school_address = trim($_POST['school_address']);
        $school_phone = trim($_POST['school_phone']);
        $school_email = trim($_POST['school_email']);
        
        try {
            // Create or update system settings table
            $conn->query("CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_name VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $settings = [
                'system_name' => $system_name,
                'academic_year' => $academic_year,
                'term' => $term,
                'school_address' => $school_address,
                'school_phone' => $school_phone,
                'school_email' => $school_email
            ];
            
            foreach ($settings as $name => $value) {
                $stmt = $conn->prepare("INSERT INTO system_settings (setting_name, setting_value) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $name, $value, $value);
                $stmt->execute();
            }
            
            $success_message = "System information updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating system information: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_grading_system'])) {
        // Update grading system
        $grade_a_min = (int)$_POST['grade_a_min'];
        $grade_b_min = (int)$_POST['grade_b_min'];
        $grade_c_min = (int)$_POST['grade_c_min'];
        $grade_d_min = (int)$_POST['grade_d_min'];
        $pass_mark = (int)$_POST['pass_mark'];
        
        try {
            $grades = [
                'grade_a_min' => $grade_a_min,
                'grade_b_min' => $grade_b_min,
                'grade_c_min' => $grade_c_min,
                'grade_d_min' => $grade_d_min,
                'pass_mark' => $pass_mark
            ];
            
            foreach ($grades as $name => $value) {
                $stmt = $conn->prepare("INSERT INTO system_settings (setting_name, setting_value) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sis", $name, $value, $value);
                $stmt->execute();
            }
            
            $success_message = "Grading system updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating grading system: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['backup_database'])) {
        // Create database backup
        try {
            $backup_dir = 'backups/';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Get database name from config (assuming it's defined)
            $db_name = 'student_results_system'; // Replace with your actual database name
            
            $command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . $db_name . " > " . $backup_file;
            
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                $success_message = "Database backup created successfully: " . $backup_file;
            } else {
                $error_message = "Error creating database backup.";
            }
        } catch (Exception $e) {
            $error_message = "Backup error: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['clear_old_data'])) {
        // Clear old data
        $clear_type = $_POST['clear_type'];
        
        try {
            switch ($clear_type) {
                case 'results':
                    $conn->query("DELETE FROM results WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)");
                    $success_message = "Old results data cleared successfully!";
                    break;
                case 'logs':
                    // Create logs table if it doesn't exist
                    $conn->query("CREATE TABLE IF NOT EXISTS system_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        action VARCHAR(255),
                        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    $conn->query("DELETE FROM system_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 6 MONTH)");
                    $success_message = "Old log data cleared successfully!";
                    break;
                case 'sessions':
                    // Clear old session data (if stored in database)
                    $conn->query("CREATE TABLE IF NOT EXISTS user_sessions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        session_id VARCHAR(255),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    $conn->query("DELETE FROM user_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
                    $success_message = "Old session data cleared successfully!";
                    break;
            }
        } catch (Exception $e) {
            $error_message = "Error clearing data: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['reset_user_password'])) {
        // Reset user password
        $user_id = (int)$_POST['user_id'];
        $new_password = 'password123'; // Default password
        $hashed_password = md5($new_password);
        
        try {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success_message = "User password reset to 'password123' successfully!";
            } else {
                $error_message = "User not found or password not changed.";
            }
        } catch (Exception $e) {
            $error_message = "Error resetting password: " . $e->getMessage();
        }
    }
}

// Get current system settings
function getSetting($conn, $setting_name, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_name = ?");
    $stmt->bind_param("s", $setting_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

// Get system statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'")->fetch_assoc()['count'];
$total_results = $conn->query("SELECT COUNT(*) as count FROM results")->fetch_assoc()['count'];

// Get database size
$db_size_query = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'db_size' 
                              FROM information_schema.tables 
                              WHERE table_schema = DATABASE()");
$db_size = $db_size_query ? $db_size_query->fetch_assoc()['db_size'] : 'N/A';

// Get all users for password reset
$users = $conn->query("SELECT id, username, role FROM users ORDER BY username");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Student Results Management System</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .settings-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #1abc9c;
        }
        
        .settings-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .system-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .danger-zone {
            border-left: 5px solid #e74c3c;
            background: #fdf2f2;
        }
        
        .danger-zone h3 {
            color: #e74c3c;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        .btn-warning:hover {
            background: #d68910;
        }
        
        .backup-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #17a2b8;
        }
        
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>📊 SRMS</h2>
        <a href="index.php">🏠 Dashboard</a>
        <a href="user_manage.php">👥 Manage Users</a>
        <a href="student_manage.php">👨‍🎓 Manage Students</a>
        <a href="subjects_manage.php">📚 Manage Subjects</a>
        <a href="courses_manage.php">📖 Manage Courses</a>
        <a href="results_list.php">📊 All Results</a>
        <a href="report_class.php">📈 Performance Reports</a>
        <a href="parent_manage.php">👨‍👩‍👧 Parent-Student Link</a>
        <a href="settings.php" class="active">⚙️ System Settings</a>
        <a href="logout.php" style="margin-top: auto; background: #e74c3c;">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="settings-container">
            <h1>⚙️ System Settings</h1>
            <p>Configure and manage your Student Results Management System</p>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- System Statistics -->
            <div class="system-stats">
                <div class="stat-box">
                    <div class="stat-number"><?= $total_users ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $total_students ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $total_teachers ?></div>
                    <div class="stat-label">Teachers</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $total_results ?></div>
                    <div class="stat-label">Results</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $db_size ?> MB</div>
                    <div class="stat-label">Database Size</div>
                </div>
            </div>

            <div class="settings-grid">
                <!-- System Information -->
                <div class="settings-card">
                    <h3>🏫 System Information</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="system_name">System Name</label>
                            <input type="text" id="system_name" name="system_name" 
                                   value="<?= htmlspecialchars(getSetting($conn, 'system_name', 'Student Results Management System')) ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="academic_year">Academic Year</label>
                                <input type="text" id="academic_year" name="academic_year" 
                                       value="<?= htmlspecialchars(getSetting($conn, 'academic_year', '2024/2025')) ?>" 
                                       placeholder="e.g., 2024/2025" required>
                            </div>
                            <div class="form-group">
                                <label for="term">Current Term</label>
                                <select id="term" name="term" required>
                                    <option value="1" <?= getSetting($conn, 'term') == '1' ? 'selected' : '' ?>>Term 1</option>
                                    <option value="2" <?= getSetting($conn, 'term') == '2' ? 'selected' : '' ?>>Term 2</option>
                                    <option value="3" <?= getSetting($conn, 'term') == '3' ? 'selected' : '' ?>>Term 3</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="school_address">School Address</label>
                            <textarea id="school_address" name="school_address" rows="3"><?= htmlspecialchars(getSetting($conn, 'school_address')) ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="school_phone">Phone Number</label>
                                <input type="tel" id="school_phone" name="school_phone" 
                                       value="<?= htmlspecialchars(getSetting($conn, 'school_phone')) ?>">
                            </div>
                            <div class="form-group">
                                <label for="school_email">Email Address</label>
                                <input type="email" id="school_email" name="school_email" 
                                       value="<?= htmlspecialchars(getSetting($conn, 'school_email')) ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_system_info" class="btn">💾 Update System Info</button>
                    </form>
                </div>

                <!-- Grading System -->
                <div class="settings-card">
                    <h3>📊 Grading System</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="grade_a_min">Grade A Minimum (%)</label>
                            <input type="number" id="grade_a_min" name="grade_a_min" min="0" max="100" 
                                   value="<?= getSetting($conn, 'grade_a_min', '80') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="grade_b_min">Grade B Minimum (%)</label>
                            <input type="number" id="grade_b_min" name="grade_b_min" min="0" max="100" 
                                   value="<?= getSetting($conn, 'grade_b_min', '70') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="grade_c_min">Grade C Minimum (%)</label>
                            <input type="number" id="grade_c_min" name="grade_c_min" min="0" max="100" 
                                   value="<?= getSetting($conn, 'grade_c_min', '60') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="grade_d_min">Grade D Minimum (%)</label>
                            <input type="number" id="grade_d_min" name="grade_d_min" min="0" max="100" 
                                   value="<?= getSetting($conn, 'grade_d_min', '50') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="pass_mark">Pass Mark (%)</label>
                            <input type="number" id="pass_mark" name="pass_mark" min="0" max="100" 
                                   value="<?= getSetting($conn, 'pass_mark', '40') ?>" required>
                        </div>
                        
                        <button type="submit" name="update_grading_system" class="btn">📊 Update Grading System</button>
                    </form>
                </div>

                <!-- Database Management -->
                <div class="settings-card">
                    <h3>💾 Database Management</h3>
                    
                    <div class="backup-info">
                        <strong>🔒 Backup Information</strong>
                        <p>Regular backups are essential for data safety. Backup files are stored in the 'backups' directory.</p>
                    </div>
                    
                    <form method="POST" style="margin-bottom: 20px;">
                        <button type="submit" name="backup_database" class="btn" 
                                onclick="return confirm('Create a database backup? This may take a few moments.')">
                            💾 Create Backup
                        </button>
                    </form>
                    
                    <h4>🧹 Clear Old Data</h4>
                    <form method="POST">
                        <div class="form-group">
                            <label for="clear_type">Data Type to Clear</label>
                            <select id="clear_type" name="clear_type" required>
                                <option value="results">Old Results (2+ years)</option>
                                <option value="logs">System Logs (6+ months)</option>
                                <option value="sessions">Old Sessions (7+ days)</option>
                            </select>
                        </div>
                        <button type="submit" name="clear_old_data" class="btn btn-warning"
                                onclick="return confirm('Are you sure you want to clear this data? This action cannot be undone.')">
                            🗑️ Clear Data
                        </button>
                    </form>
                </div>

                <!-- User Management -->
                <div class="settings-card danger-zone">
                    <h3>👤 User Management</h3>
                    
                    <h4>🔄 Reset User Password</h4>
                    <form method="POST">
                        <div class="form-group">
                            <label for="user_id">Select User</label>
                            <select id="user_id" name="user_id" required>
                                <option value="">Choose a user...</option>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['username']) ?> (<?= ucfirst($user['role']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <p style="color: #e74c3c; font-size: 0.9rem; margin-bottom: 15px;">
                            ⚠️ This will reset the user's password to "password123"
                        </p>
                        <button type="submit" name="reset_user_password" class="btn btn-danger"
                                onclick="return confirm('Reset this user\'s password to default? They will need to change it on next login.')">
                            🔄 Reset Password
                        </button>
                    </form>
                </div>

                <!-- System Status -->
                <div class="settings-card">
                    <h3>📈 System Status</h3>
                    
                    <div class="info-item" style="margin-bottom: 15px;">
                        <span style="font-weight: bold;">PHP Version:</span>
                        <span style="float: right;"><?= PHP_VERSION ?></span>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 15px;">
                        <span style="font-weight: bold;">MySQL Version:</span>
                        <span style="float: right;"><?= $conn->server_info ?></span>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 15px;">
                        <span style="font-weight: bold;">Server Time:</span>
                        <span style="float: right;"><?= date('Y-m-d H:i:s') ?></span>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 15px;">
                        <span style="font-weight: bold;">System Status:</span>
                        <span style="float: right; color: #1abc9c; font-weight: bold;">🟢 Online</span>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 15px;">
                        <span style="font-weight: bold;">Last Backup:</span>
                        <span style="float: right;">
                            <?php
                            $backup_files = glob('backups/*.sql');
                            if ($backup_files) {
                                $latest_backup = max($backup_files);
                                echo date('Y-m-d H:i', filemtime($latest_backup));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation for dangerous actions
    const dangerButtons = document.querySelectorAll('.btn-danger, .btn-warning');
    dangerButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!this.onclick) { // Only if no onclick already defined
                if (!confirm('Are you sure you want to perform this action?')) {
                    e.preventDefault();
                }
            }
        });
    });
    
    // Auto-save form data to localStorage (optional enhancement)
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Load saved value
            const savedValue = localStorage.getItem('settings_' + input.id);
            if (savedValue && input.value === '') {
                input.value = savedValue;
            }
            
            // Save on change
            input.addEventListener('change', function() {
                localStorage.setItem('settings_' + this.id, this.value);
            });
        });
    });
});
</script>

</body>
</html>