<?php
session_start();
require_once 'includes/auth.php';
require_role('teacher');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

$message = '';
$errors = [];

// Ensure attendance table exists
$ensureAttendance = "CREATE TABLE IF NOT EXISTS attendance (
  attendance_id INT(11) NOT NULL AUTO_INCREMENT,
  class_id INT(11) NOT NULL,
  date DATE NOT NULL,
  present_count INT(11) NOT NULL,
  absent_count INT(11) NOT NULL,
  teacher_id INT(11) NOT NULL,
  notes TEXT DEFAULT NULL,
  PRIMARY KEY (attendance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($link, $ensureAttendance);

// Map teacher id by email==username
$username = $_SESSION['username'];
$teacher_id = 0;
$tres = mysqli_query($link, "SELECT teacher_id FROM teachers WHERE email='" . mysqli_real_escape_string($link, $username) . "'");
if ($t = mysqli_fetch_assoc($tres)) { $teacher_id = (int)$t['teacher_id']; }

// Load classes
$classes = mysqli_query($link, "SELECT class_id, class_name, year FROM classes ORDER BY year DESC, class_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = (int) trim($_POST['class_id']);
    $date = trim($_POST['date']);
    $present = (int) trim($_POST['present_count']);
    $absent = (int) trim($_POST['absent_count']);
    $notes = trim($_POST['notes'] ?? '');

    if ($teacher_id <= 0) { $errors[] = 'Teacher record not found for your account.'; }
    if ($class_id <= 0) { $errors[] = 'Class is required.'; }
    if (!$date) { $errors[] = 'Date is required.'; }
    if ($present < 0 || $absent < 0) { $errors[] = 'Counts must be non-negative.'; }

    if (empty($errors)) {
        $sql = "INSERT INTO attendance (class_id, date, present_count, absent_count, teacher_id, notes) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, 'isiiis', $class_id, $date, $present, $absent, $teacher_id, $notes);
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Attendance recorded successfully.';
            } else {
                $errors[] = 'Failed to record attendance.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="container">
    <h3>Mark Attendance</h3>
    <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo sanitize($message); ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?><div><?php echo sanitize($e); ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="attendance_mark.php" class="mb-4">
        <div class="form-group">
            <label>Class</label>
            <select name="class_id" class="form-control">
                <option value="">Select Class</option>
                <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                    <option value="<?php echo (int)$c['class_id']; ?>"><?php echo sanitize($c['class_name']) . ' (' . (int)$c['year'] . ')'; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Date</label>
            <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Present</label>
                <input type="number" name="present_count" class="form-control" min="0" value="0">
            </div>
            <div class="form-group col-md-6">
                <label>Absent</label>
                <input type="number" name="absent_count" class="form-control" min="0" value="0">
            </div>
        </div>
        <div class="form-group">
            <label>Notes (optional)</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Save Attendance</button>
    </form>

    <h5>Your Recent Attendance</h5>
    <?php
    $recent = mysqli_query($link, "SELECT a.date, c.class_name, a.present_count, a.absent_count FROM attendance a JOIN classes c ON a.class_id=c.class_id WHERE a.teacher_id=$teacher_id ORDER BY a.date DESC, a.attendance_id DESC LIMIT 10");
    ?>
    <table class="table table-bordered">
        <thead><tr><th>Date</th><th>Class</th><th>Present</th><th>Absent</th></tr></thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                <tr>
                    <td><?php echo sanitize($row['date']); ?></td>
                    <td><?php echo sanitize($row['class_name']); ?></td>
                    <td><?php echo (int)$row['present_count']; ?></td>
                    <td><?php echo (int)$row['absent_count']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>