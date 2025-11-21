<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

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

$filter_date = isset($_GET['date']) ? trim($_GET['date']) : '';
$where = $filter_date ? "WHERE a.date='" . mysqli_real_escape_string($link, $filter_date) . "'" : '';

$res = mysqli_query($link, "SELECT a.attendance_id, a.date, c.class_name, a.present_count, a.absent_count, t.name AS teacher_name, a.notes
FROM attendance a
JOIN classes c ON a.class_id=c.class_id
LEFT JOIN teachers t ON a.teacher_id=t.teacher_id
$where
ORDER BY a.date DESC, a.attendance_id DESC");

// Quick summary totals
$sum_sql = "SELECT COALESCE(SUM(present_count),0) AS present, COALESCE(SUM(absent_count),0) AS absent FROM attendance" . ($filter_date ? " WHERE date='" . mysqli_real_escape_string($link, $filter_date) . "'" : "");
$sum = mysqli_fetch_assoc(mysqli_query($link, $sum_sql));
?>

<div class="container">
    <h3>Attendance Management</h3>
    <form method="get" class="form-inline mb-3">
        <label class="mr-2">Filter by date</label>
        <input type="date" name="date" class="form-control mr-2" value="<?php echo htmlspecialchars($filter_date); ?>">
        <button class="btn btn-primary" type="submit">Apply</button>
        <a class="btn btn-link ml-2" href="manage_attendance.php">Reset</a>
    </form>

    <div class="row">
        <div class="col-md-4">
            <div class="card dashboard-card"><div class="card-body">
                <h5 class="card-title">Total Present</h5>
                <p class="card-text"><?php echo (int)$sum['present']; ?></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card"><div class="card-body">
                <h5 class="card-title">Total Absent</h5>
                <p class="card-text"><?php echo (int)$sum['absent']; ?></p>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card"><div class="card-body">
                <h5 class="card-title">Records</h5>
                <?php
                $count = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM attendance" . ($filter_date ? " WHERE date='" . mysqli_real_escape_string($link, $filter_date) . "'" : "")));
                echo '<p class="card-text">' . (int)$count['c'] . '</p>';
                ?>
            </div></div>
        </div>
    </div>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Date</th>
                <th>Class</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Teacher</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($res)): ?>
            <tr>
                <td><?php echo sanitize($row['date']); ?></td>
                <td><?php echo sanitize($row['class_name']); ?></td>
                <td><?php echo (int)$row['present_count']; ?></td>
                <td><?php echo (int)$row['absent_count']; ?></td>
                <td><?php echo sanitize($row['teacher_name']); ?></td>
                <td><?php echo sanitize($row['notes']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>