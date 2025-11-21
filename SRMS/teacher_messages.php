<?php
// Enhanced Messages & Announcements tab for teachers
require_once 'includes/helpers.php';

$teacher_id = isset($teacher_id) ? (int)$teacher_id : 0;
if ($teacher_id <= 0) {
    echo "<div class='alert alert-warning'>No teacher selected.</div>";
    return;
}

// Detect advanced announcements schema (with audience targeting)
$has_audience = function_exists('column_exists') ? column_exists($link, 'announcements', 'audience_type') : false;
$has_class_id = function_exists('column_exists') ? column_exists($link, 'announcements', 'class_id') : false;
$has_created_at = function_exists('column_exists') ? column_exists($link, 'announcements', 'created_at') : false;
$has_posted_by = function_exists('column_exists') ? column_exists($link, 'announcements', 'posted_by') : false;

// Load classes assigned to teacher for targeting
$teacher_classes = [];
$tclasses_q = mysqli_query($link, "SELECT c.class_id, c.class_name, c.stream FROM classes c JOIN teacher_class_assignments tca ON c.class_id=tca.class_id WHERE tca.teacher_id=$teacher_id ORDER BY c.class_name, c.stream");
while ($row = mysqli_fetch_assoc($tclasses_q)) { $teacher_classes[] = $row; }

// Handle send message
$feedback_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $title = trim($_POST['title'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    $audience = $has_audience ? ($_POST['audience'] ?? 'everyone') : 'everyone';
    $class_id = $has_class_id ? (int)($_POST['class_id'] ?? 0) : null;

    if ($title === '' || $messageText === '') {
        $feedback_msg = "<div class='alert alert-warning'>Title and message are required.</div>";
    } else {
        if ($has_audience || $has_class_id) {
            $stmt = mysqli_prepare($link, "INSERT INTO announcements (title, message, audience_type, class_id" . ($has_posted_by?", posted_by":"") . ") VALUES (?, ?, ?, ?" . ($has_posted_by?", ?":"") . ")");
            if ($has_posted_by) {
                mysqli_stmt_bind_param($stmt, 'sssii', $title, $messageText, $audience, $class_id, $teacher_id);
            } else {
                mysqli_stmt_bind_param($stmt, 'sssi', $title, $messageText, $audience, $class_id);
            }
            if (mysqli_stmt_execute($stmt)) { $feedback_msg = "<div class='alert alert-success'>Announcement sent.</div>"; }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($link, "INSERT INTO announcements (title, message" . ($has_posted_by?", posted_by":"") . ") VALUES (?, ?" . ($has_posted_by?", ?":"") . ")");
            if ($has_posted_by) {
                mysqli_stmt_bind_param($stmt, 'ssi', $title, $messageText, $teacher_id);
            } else {
                mysqli_stmt_bind_param($stmt, 'ss', $title, $messageText);
            }
            if (mysqli_stmt_execute($stmt)) { $feedback_msg = "<div class='alert alert-success'>Announcement sent.</div>"; }
            mysqli_stmt_close($stmt);
        }
    }
}

// Retrieve messages relevant to this teacher
$messages = [];
if ($has_audience || $has_class_id) {
    // Show everyone and class-targeted announcements for teacher's classes
    $class_ids = array_map(function($c){ return (int)$c['class_id']; }, $teacher_classes);
    $class_ids_list = !empty($class_ids) ? implode(',', $class_ids) : 'NULL';
    $msg_query = "SELECT a.title, a.message, COALESCE(a.created_at, a.date_posted) AS posted_at, a.audience_type, a.class_id, c.class_name, c.stream
                  FROM announcements a
                  LEFT JOIN classes c ON a.class_id=c.class_id
                  WHERE a.audience_type='everyone' " . (!empty($class_ids) ? " OR (a.audience_type='class' AND a.class_id IN ($class_ids_list))" : "") . "
                  ORDER BY posted_at DESC LIMIT 50";
} else {
    // Legacy: just show recent announcements
    $msg_query = "SELECT title, message, date_posted AS posted_at FROM announcements ORDER BY date_posted DESC LIMIT 50";
}
$msg_res = mysqli_query($link, $msg_query);
while ($row = mysqli_fetch_assoc($msg_res)) { $messages[] = $row; }
?>

<div class="messages-tab">
    <h3>Messages & Announcements</h3>
    <?php echo $feedback_msg; ?>

    <div class="card mb-3">
        <div class="card-body">
            <h5>Send Announcement</h5>
            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <?php if ($has_audience): ?>
                    <div class="form-group col-md-3">
                        <label>Audience</label>
                        <select name="audience" id="audience" class="form-control" required>
                            <option value="everyone">Everyone</option>
                            <option value="class">Specific Class</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($has_class_id): ?>
                    <div class="form-group col-md-3" id="classSelect" style="display:none;">
                        <label>Class</label>
                        <select name="class_id" class="form-control">
                            <option value="">Select class</option>
                            <?php foreach ($teacher_classes as $c): ?>
                                <option value="<?php echo (int)$c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ($c['stream'] ? ' - ' . $c['stream'] : '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control" rows="3" required></textarea>
                </div>
                <button type="submit" name="send_message" class="btn btn-primary">Send</button>
            </form>
        </div>
    </div>

    <div class="notification-list">
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $m): ?>
                <div class="notification-item alert-info">
                    <div class="notification-icon">📢</div>
                    <div class="notification-content">
                        <strong><?php echo htmlspecialchars($m['title']); ?></strong>
                        <p><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                        <small>
                            <?php echo date('M d, Y H:i', strtotime($m['posted_at'])); ?>
                            <?php if (!empty($m['audience_type'])): ?>
                                • Audience: <?php echo htmlspecialchars($m['audience_type']); ?>
                                <?php if (!empty($m['class_id'])): ?>
                                    (<?php echo htmlspecialchars(($m['class_name'] ?? '') . (($m['stream'] ?? '') ? ' - ' . $m['stream'] : '')); ?>)
                                <?php endif; ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="notification-item alert-warning">
                <div class="notification-icon">ℹ️</div>
                <div class="notification-content">
                    <strong>No messages</strong>
                    <p>There are no recent announcements.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var audienceSel = document.getElementById('audience');
    var classSel = document.getElementById('classSelect');
    if (audienceSel && classSel) {
        function toggleClass() {
            classSel.style.display = audienceSel.value === 'class' ? 'block' : 'none';
        }
        audienceSel.addEventListener('change', toggleClass);
        toggleClass();
    }
});
</script>