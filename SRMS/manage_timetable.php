<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure timetables table exists to avoid fatal errors on fresh databases
ensure_table($link, 'timetables', "CREATE TABLE IF NOT EXISTS `timetables` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `class_id` int(11) NOT NULL,\n  `day_of_week` varchar(20) NOT NULL,\n  `period` varchar(20) NOT NULL,\n  `subject_id` int(11) NOT NULL,\n  `teacher_id` int(11) NOT NULL,\n  `start_time` varchar(8) DEFAULT NULL,\n  `end_time` varchar(8) DEFAULT NULL,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = (int)($_POST['class_id'] ?? 0);
    $day_of_week = sanitize($_POST['day_of_week'] ?? 'Monday');
    $period = sanitize($_POST['period'] ?? '');
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $start_time = sanitize($_POST['start_time'] ?? '');
    $end_time = sanitize($_POST['end_time'] ?? '');
    if ($class_id>0 && $subject_id>0 && $teacher_id>0 && $period!=='') {
        $stmt = mysqli_prepare($link, "INSERT INTO timetables (class_id, day_of_week, period, subject_id, teacher_id, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issiiss', $class_id, $day_of_week, $period, $subject_id, $teacher_id, $start_time, $end_time);
        mysqli_stmt_execute($stmt);
        $msg = '<div class="alert alert-success">Timetable entry saved.</div>';
    } else {
        $msg = '<div class="alert alert-warning">Please fill required fields.</div>';
    }
}

$classes = mysqli_query($link, "SELECT class_id, class_name FROM classes ORDER BY class_name ASC");
$subjects = mysqli_query($link, "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
$teachers = mysqli_query($link, "SELECT teacher_id, name FROM teachers ORDER BY name ASC");
$entries = mysqli_query($link, "SELECT t.id, c.class_name, t.day_of_week, t.period, s.subject_name, te.name, t.start_time, t.end_time FROM timetables t JOIN classes c ON t.class_id=c.class_id JOIN subjects s ON t.subject_id=s.subject_id JOIN teachers te ON t.teacher_id=te.teacher_id ORDER BY c.class_name ASC, t.day_of_week ASC, t.period ASC");
?>

<div class="container">
    <h3>Timetable Management</h3>
    <?php echo $msg; ?>
    <form method="post" class="card card-body mb-4">
        <div class="form-row">
            <div class="form-group col-md-3"><label>Class</label><select name="class_id" class="form-control" required><option value="">-- Select --</option><?php if($classes && $classes instanceof mysqli_result): while($c=mysqli_fetch_assoc($classes)): ?><option value="<?php echo (int)$c['class_id']; ?>"><?php echo sanitize($c['class_name']); ?></option><?php endwhile; else: ?><option value="">No classes found</option><?php endif; ?></select></div>
            <div class="form-group col-md-2"><label>Day</label><select name="day_of_week" class="form-control"><option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option></select></div>
            <div class="form-group col-md-2"><label>Period</label><input name="period" class="form-control" placeholder="e.g., P1" required></div>
            <div class="form-group col-md-2"><label>Start</label><input type="time" name="start_time" class="form-control"></div>
            <div class="form-group col-md-2"><label>End</label><input type="time" name="end_time" class="form-control"></div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-3"><label>Subject</label><select name="subject_id" class="form-control" required><option value="">-- Select --</option><?php if($subjects && $subjects instanceof mysqli_result): while($s=mysqli_fetch_assoc($subjects)): ?><option value="<?php echo (int)$s['subject_id']; ?>"><?php echo sanitize($s['subject_name']); ?></option><?php endwhile; else: ?><option value="">No subjects found</option><?php endif; ?></select></div>
            <div class="form-group col-md-3"><label>Teacher</label><select name="teacher_id" class="form-control" required><option value="">-- Select --</option><?php if($teachers && $teachers instanceof mysqli_result): while($t=mysqli_fetch_assoc($teachers)): ?><option value="<?php echo (int)$t['teacher_id']; ?>"><?php echo sanitize($t['name']); ?></option><?php endwhile; else: ?><option value="">No teachers found</option><?php endif; ?></select></div>
        </div>
        <button type="submit" class="btn btn-primary">Add Entry</button>
    </form>

    <table class="table table-bordered table-sm">
        <thead><tr><th>Class</th><th>Day</th><th>Period</th><th>Subject</th><th>Teacher</th><th>Start</th><th>End</th></tr></thead>
        <tbody>
            <?php if($entries && $entries instanceof mysqli_result): ?>
                <?php while ($e = mysqli_fetch_assoc($entries)): ?>
                    <tr>
                        <td><?php echo sanitize($e['class_name']); ?></td>
                        <td><?php echo sanitize($e['day_of_week']); ?></td>
                        <td><?php echo sanitize($e['period']); ?></td>
                        <td><?php echo sanitize($e['subject_name']); ?></td>
                        <td><?php echo sanitize($e['name']); ?></td>
                        <td><?php echo sanitize($e['start_time']); ?></td>
                        <td><?php echo sanitize($e['end_time']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No timetable entries found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>