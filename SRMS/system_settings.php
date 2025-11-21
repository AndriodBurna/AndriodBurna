<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure settings table
if (function_exists('ensure_table')) {
    $ensureSettings = "CREATE TABLE IF NOT EXISTS settings (
        id INT PRIMARY KEY DEFAULT 1,
        school_name VARCHAR(255) DEFAULT NULL,
        current_session VARCHAR(50) DEFAULT NULL,
        grading_scale TEXT DEFAULT NULL,
        notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    ensure_table($link, 'settings', $ensureSettings);
}

$message = ''; $errors = [];

// Helpers to support legacy schema (srms_db.sql uses `setting_id` primary key)
function column_exists($link, $table, $column) {
    $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='" . mysqli_real_escape_string($link, $table) . "' AND COLUMN_NAME='" . mysqli_real_escape_string($link, $column) . "'";
    $res = mysqli_query($link, $sql);
    if ($res && ($row = mysqli_fetch_assoc($res))) { return (int)$row['c'] > 0; }
    return false;
}
function get_settings_row($link) {
    $hasId = column_exists($link, 'settings', 'id');
    $hasSettingId = column_exists($link, 'settings', 'setting_id');
    if ($hasId) {
        $res = mysqli_query($link, "SELECT * FROM settings WHERE id=1");
        return $res ? mysqli_fetch_assoc($res) : null;
    } elseif ($hasSettingId) {
        $res = mysqli_query($link, "SELECT * FROM settings ORDER BY setting_id ASC LIMIT 1");
        return $res ? mysqli_fetch_assoc($res) : null;
    } else {
        $res = mysqli_query($link, "SELECT * FROM settings LIMIT 1");
        return $res ? mysqli_fetch_assoc($res) : null;
    }
}
function save_settings($link, $data, $existing) {
    // Build dynamic SET clause using available columns
    $setParts = [];
    if (column_exists($link, 'settings', 'school_name')) { $setParts[] = "school_name='" . mysqli_real_escape_string($link, $data['school_name']) . "'"; }
    if (column_exists($link, 'settings', 'current_session')) { $setParts[] = "current_session='" . mysqli_real_escape_string($link, $data['current_session']) . "'"; }
    if (column_exists($link, 'settings', 'grading_scale')) { $setParts[] = "grading_scale='" . mysqli_real_escape_string($link, $data['grading_scale']) . "'"; }
    if (column_exists($link, 'settings', 'notifications_enabled')) { $setParts[] = "notifications_enabled=" . (int)$data['notifications_enabled']; }
    if (empty($setParts)) { return false; }

    $hasId = column_exists($link, 'settings', 'id');
    $hasSettingId = column_exists($link, 'settings', 'setting_id');

    if ($existing) {
        $where = '';
        if ($hasId && isset($existing['id'])) { $where = 'WHERE id=1'; }
        elseif ($hasSettingId && isset($existing['setting_id'])) { $where = 'WHERE setting_id=' . (int)$existing['setting_id']; }
        else { $where = 'LIMIT 1'; }
        $sql = "UPDATE settings SET " . implode(', ', $setParts) . ' ' . $where;
        return mysqli_query($link, $sql) !== false;
    } else {
        // Insert row using available columns
        $cols = [];
        $vals = [];
        if (column_exists($link, 'settings', 'id')) { $cols[] = 'id'; $vals[] = '1'; }
        if (column_exists($link, 'settings', 'school_name')) { $cols[] = 'school_name'; $vals[] = "'" . mysqli_real_escape_string($link, $data['school_name']) . "'"; }
        if (column_exists($link, 'settings', 'current_session')) { $cols[] = 'current_session'; $vals[] = "'" . mysqli_real_escape_string($link, $data['current_session']) . "'"; }
        if (column_exists($link, 'settings', 'grading_scale')) { $cols[] = 'grading_scale'; $vals[] = "'" . mysqli_real_escape_string($link, $data['grading_scale']) . "'"; }
        if (column_exists($link, 'settings', 'notifications_enabled')) { $cols[] = 'notifications_enabled'; $vals[] = (int)$data['notifications_enabled']; }
        if (empty($cols)) { return false; }
        $sql = "INSERT INTO settings (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
        return mysqli_query($link, $sql) !== false;
    }
}

// Load current settings (single-row, compatible with legacy schema)
$settings = get_settings_row($link);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = trim($_POST['school_name'] ?? '');
    $current_session = trim($_POST['current_session'] ?? '');
    $grading_scale = trim($_POST['grading_scale'] ?? '');
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;

    $payload = [
        'school_name' => $school_name,
        'current_session' => $current_session,
        'grading_scale' => $grading_scale,
        'notifications_enabled' => $notifications_enabled
    ];
    if (save_settings($link, $payload, $settings)) { $message = 'Settings updated successfully.'; }
    $settings = get_settings_row($link);
}

require_once 'includes/header.php';
?>

<div class="container">
    <h3>System Settings</h3>
    <p>Configure school details, academic session, grading scales, and notification preferences.</p>
    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <form method="post" class="card card-body">
        <div class="form-group">
            <label>School Name</label>
            <input type="text" name="school_name" class="form-control" value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Current Academic Session</label>
            <input type="text" name="current_session" class="form-control" placeholder="e.g., 2024/2025" value="<?php echo htmlspecialchars($settings['current_session'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Grading Scale (JSON or Text)</label>
            <textarea name="grading_scale" class="form-control" rows="4" placeholder='e.g., {"A": "80-100", "B": "65-79"}'><?php echo htmlspecialchars($settings['grading_scale'] ?? ''); ?></textarea>
        </div>
        <div class="form-group form-check">
            <input type="checkbox" name="notifications_enabled" id="notif" class="form-check-input" <?php echo !empty($settings['notifications_enabled']) ? 'checked' : ''; ?>>
            <label for="notif" class="form-check-label">Enable Email/SMS Notifications</label>
        </div>
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>