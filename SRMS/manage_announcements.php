<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure announcements table exists
if (function_exists('ensure_table')) {
    $ensureAnnouncements = "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        audience_type ENUM('everyone','all_parents','class') NOT NULL DEFAULT 'everyone',
        class_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    ensure_table($link, 'announcements', $ensureAnnouncements);
}

// Runtime migration: ensure required columns exist even if table was created with an older schema
function column_exists($link, $table, $column) {
    $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . mysqli_real_escape_string($link, $table) . "' AND COLUMN_NAME = '" . mysqli_real_escape_string($link, $column) . "'";
    $res = mysqli_query($link, $sql);
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        return (int)$row['c'] > 0;
    }
    return false;
}

// Add audience_type if missing
if (!column_exists($link, 'announcements', 'audience_type')) {
    @mysqli_query($link, "ALTER TABLE announcements ADD COLUMN audience_type ENUM('everyone','all_parents','class') NOT NULL DEFAULT 'everyone'");
}
// Add class_id if missing
if (!column_exists($link, 'announcements', 'class_id')) {
    @mysqli_query($link, "ALTER TABLE announcements ADD COLUMN class_id INT NULL");
}
// Add created_at if missing; if date_posted exists, backfill created_at from it
if (!column_exists($link, 'announcements', 'created_at')) {
    $hasDatePosted = column_exists($link, 'announcements', 'date_posted');
    if ($hasDatePosted) {
        @mysqli_query($link, "ALTER TABLE announcements ADD COLUMN created_at TIMESTAMP NULL DEFAULT NULL");
        @mysqli_query($link, "UPDATE announcements SET created_at = date_posted WHERE created_at IS NULL");
        @mysqli_query($link, "ALTER TABLE announcements MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    } else {
        @mysqli_query($link, "ALTER TABLE announcements ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }
}

$errors = []; $message = '';

// Create announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    $audience = $_POST['audience'] ?? 'everyone';
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : null;

    if ($title === '' || $messageText === '') { $errors[] = 'Title and message are required.'; }
    if (!in_array($audience, ['everyone','all_parents','class'])) { $errors[] = 'Invalid audience type.'; }
    if ($audience === 'class' && !$class_id) { $errors[] = 'Select a class for class-targeted announcement.'; }

    if (empty($errors)) {
        $stmt = mysqli_prepare($link, "INSERT INTO announcements (title, message, audience_type, class_id) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssi', $title, $messageText, $audience, $class_id);
        if (mysqli_stmt_execute($stmt)) { $message = 'Announcement posted successfully.'; }
        mysqli_stmt_close($stmt);
    }
}

// Load classes for targeting
$classes = mysqli_query($link, "SELECT class_id, class_name, stream FROM classes ORDER BY class_name, stream");
// Load recent announcements (compatible with legacy schemas):
// - Use COALESCE(created_at, date_posted) as created_at
// - LEFT JOIN classes only if class_id exists
$hasClassId = column_exists($link, 'announcements', 'class_id');
$joinSql = $hasClassId ? " LEFT JOIN classes c ON a.class_id = c.class_id " : " ";
$annSql = "SELECT a.*, COALESCE(a.created_at, a.date_posted) AS created_at" . ($hasClassId ? ", c.class_name, c.stream" : "") . " FROM announcements a" . $joinSql . " ORDER BY created_at DESC LIMIT 50";
$ann = mysqli_query($link, $annSql);

require_once 'includes/header.php';
?>

<div class="container">
    <h3>Announcements</h3>
    <p>Broadcast announcements to everyone, all parents, or a specific class.</p>

    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach($errors as $e){ echo '<div>' . htmlspecialchars($e) . '</div>'; } ?></div><?php endif; ?>

    <form method="post" class="card card-body mb-4">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group col-md-3">
                <label>Audience</label>
                <select name="audience" id="audience" class="form-control" required>
                    <option value="everyone">Everyone</option>
                    <option value="all_parents">All Parents</option>
                    <option value="class">Specific Class</option>
                </select>
            </div>
            <div class="form-group col-md-3" id="classSelect" style="display:none;">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">Select class</option>
                    <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                        <option value="<?php echo (int)$c['class_id']; ?>"><?php echo sanitize($c['class_name'] . ($c['stream'] ? ' - ' . $c['stream'] : '')); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Message</label>
            <textarea name="message" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Post Announcement</button>
    </form>

    <div class="card"><div class="card-body">
        <h5 class="card-title">Recent Announcements</h5>
        <?php if (!$ann || mysqli_num_rows($ann) === 0): ?>
            <div class="alert alert-info">No announcements yet.</div>
        <?php else: ?>
            <table class="table table-sm table-bordered">
                <thead><tr><th>Title</th><th>Audience</th><th>Class</th><th>Posted</th></tr></thead>
                <tbody>
                    <?php while ($a = mysqli_fetch_assoc($ann)): ?>
                        <tr>
                            <td><?php echo sanitize($a['title']); ?></td>
                            <td><?php echo sanitize(isset($a['audience_type']) && $a['audience_type'] !== null ? $a['audience_type'] : 'everyone'); ?></td>
                            <td><?php echo sanitize(isset($a['class_name']) && $a['class_name'] ? ($a['class_name'] . (isset($a['stream']) && $a['stream'] ? ' - ' . $a['stream'] : '')) : '-'); ?></td>
                            <td><?php echo sanitize($a['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div></div>
</div>

<script>
document.getElementById('audience').addEventListener('change', function(){
    var show = this.value === 'class';
    document.getElementById('classSelect').style.display = show ? 'block' : 'none';
});
</script>

<?php require_once 'includes/footer.php'; ?>