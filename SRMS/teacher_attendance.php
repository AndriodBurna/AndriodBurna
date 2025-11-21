<?php
// Teacher Attendance Module
require_once 'config.php';
require_once 'includes/helpers.php';

$teacher_id = isset($teacher_id) ? (int)$teacher_id : 0;
if ($teacher_id <= 0) {
    echo "<div class='alert alert-warning'>No teacher selected.</div>";
    return;
}

// Ensure attendance table exists (compatible with srms_db.sql)
if (function_exists('ensure_table')) {
    ensure_table($link, 'attendance', "CREATE TABLE IF NOT EXISTS `attendance` (
      `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
      `class_id` int(11) NOT NULL,
      `date` date NOT NULL,
      `present_count` int(11) NOT NULL,
      `absent_count` int(11) NOT NULL,
      `teacher_id` int(11) NOT NULL,
      `notes` text DEFAULT NULL,
      PRIMARY KEY (`attendance_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// Fetch teacher classes
$classes = [];
$cq = mysqli_query($link, "SELECT c.class_id, c.class_name, c.stream FROM classes c JOIN teacher_class_assignments tca ON c.class_id=tca.class_id WHERE tca.teacher_id=$teacher_id ORDER BY c.class_name");
while ($row = mysqli_fetch_assoc($cq)) { $classes[] = $row; }

// Selected class and date
$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : (isset($classes[0]['class_id']) ? (int)$classes[0]['class_id'] : 0);
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch students for selected class
$students = [];
if ($selected_class_id > 0) {
    $sq = mysqli_query($link, "SELECT student_id, name FROM students WHERE class_id=$selected_class_id ORDER BY name");
    while ($s = mysqli_fetch_assoc($sq)) { $students[] = $s; }
}

// Handle submit attendance
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $selected_class_id = (int)($_POST['class_id'] ?? 0);
    $selected_date = $_POST['date'] ?? date('Y-m-d');
    $statuses = $_POST['status'] ?? [];
    // Build JSON notes map: student_id => status
    $notes = json_encode($statuses);
    $present = 0; $absent = 0; $late = 0;
    foreach ($statuses as $sid => $st) {
        if ($st === 'Present') $present++;
        elseif ($st === 'Absent') $absent++;
        elseif ($st === 'Late') $late++;
    }
    // Upsert attendance record for class & date
    $check = mysqli_query($link, "SELECT attendance_id FROM attendance WHERE class_id=$selected_class_id AND date='".mysqli_real_escape_string($link, $selected_date)."' AND teacher_id=$teacher_id");
    if ($check && mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $aid = (int)$row['attendance_id'];
        mysqli_query($link, "UPDATE attendance SET present_count=$present, absent_count=$absent, notes='".mysqli_real_escape_string($link, $notes)."' WHERE attendance_id=$aid");
        $message = "<div class='alert alert-success'>Attendance updated.</div>";
    } else {
        mysqli_query($link, "INSERT INTO attendance (class_id, date, present_count, absent_count, teacher_id, notes) VALUES ($selected_class_id, '".mysqli_real_escape_string($link, $selected_date)."', $present, $absent, $teacher_id, '".mysqli_real_escape_string($link, $notes)."')");
        $message = "<div class='alert alert-success'>Attendance saved.</div>";
    }
}

// Load existing statuses for the selected date
$existing_statuses = [];
if ($selected_class_id > 0) {
    $att = mysqli_query($link, "SELECT notes FROM attendance WHERE class_id=$selected_class_id AND date='".mysqli_real_escape_string($link, $selected_date)."' AND teacher_id=$teacher_id");
    if ($att && ($row = mysqli_fetch_assoc($att)) && !empty($row['notes'])) {
        $existing_statuses = json_decode($row['notes'], true) ?: [];
    }
}

// Monthly summary for selected class
$month_start = date('Y-m-01', strtotime($selected_date));
$month_end = date('Y-m-t', strtotime($selected_date));
$summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'days' => 0];
$sumq = mysqli_query($link, "SELECT present_count, absent_count, notes FROM attendance WHERE class_id=$selected_class_id AND date BETWEEN '$month_start' AND '$month_end' AND teacher_id=$teacher_id");
while ($r = mysqli_fetch_assoc($sumq)) {
    $summary['present'] += (int)$r['present_count'];
    $summary['absent'] += (int)$r['absent_count'];
    $n = json_decode($r['notes'], true) ?: [];
    $summary['late'] += count(array_filter($n, function($st){ return $st==='Late'; }));
    $summary['days']++;
}
$total_marks = $summary['present'] + $summary['absent'] + $summary['late'];
$percent_present = $total_marks > 0 ? round(($summary['present'] / $total_marks) * 100, 1) : 0;

    // Fetch student attendance history for the selected class
    $student_attendance_history = [];
    if ($selected_class_id > 0) {
        foreach ($students as $student) {
            $student_id = (int)$student['student_id'];
            $history_query = mysqli_query($link, "SELECT date, notes FROM attendance WHERE class_id=$selected_class_id AND teacher_id=$teacher_id ORDER BY date DESC");
            $student_history = [];
            while ($h_row = mysqli_fetch_assoc($history_query)) {
                $notes = json_decode($h_row['notes'], true) ?: [];
                if (isset($notes[$student_id])) {
                    $student_history[] = ['date' => $h_row['date'], 'status' => $notes[$student_id]];
                }
            }
            $student_attendance_history[$student_id] = $student_history;
        }
    }

// Export CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_attendance'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_'.($selected_class_id).'_'.date('Y-m').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Present', 'Absent', 'Late']);
    $expq = mysqli_query($link, "SELECT date, present_count, absent_count, notes FROM attendance WHERE class_id=$selected_class_id AND teacher_id=$teacher_id ORDER BY date DESC");
    while ($e = mysqli_fetch_assoc($expq)) {
        $n = json_decode($e['notes'], true) ?: [];
        $late_cnt = count(array_filter($n, function($st){ return $st==='Late'; }));
        fputcsv($out, [$e['date'], (int)$e['present_count'], (int)$e['absent_count'], $late_cnt]);
    }
    fclose($out);
    exit;
}
?>

<div class="attendance-tab">
    <h3>Attendance</h3>
    <?php echo $message; ?>
    <?php if (empty($classes)): ?>
        <div class="alert alert-info">No assigned classes found. Please contact admin to assign your classes.</div>
    <?php else: ?>
    <form method="get" class="form-inline mb-3">
        <label class="mr-2">Class</label>
        <select name="class_id" class="form-control mr-2" onchange="this.form.submit()">
            <?php foreach ($classes as $c): ?>
                <option value="<?php echo (int)$c['class_id']; ?>" <?php echo $selected_class_id===(int)$c['class_id']?'selected':''; ?>>
                    <?php echo htmlspecialchars($c['class_name'].' '.$c['stream']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label class="mr-2">Date</label>
        <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" class="form-control mr-2" onchange="this.form.submit()" />
    </form>

    <form method="post" class="card card-body">
        <input type="hidden" name="class_id" value="<?php echo (int)$selected_class_id; ?>" />
        <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" />
        <table class="table table-sm">
            <thead><tr><th>Student</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                        <td>
                            <?php $cur = $existing_statuses[$s['student_id']] ?? 'Present'; ?>
                            <select name="status[<?php echo (int)$s['student_id']; ?>]" class="form-control form-control-sm">
                                <option <?php echo $cur==='Present'?'selected':''; ?>>Present</option>
                                <option <?php echo $cur==='Absent'?'selected':''; ?>>Absent</option>
                                <option <?php echo $cur==='Late'?'selected':''; ?>>Late</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <button type="submit" name="save_attendance" class="btn btn-primary">Save Attendance</button>
                <button type="button" class="btn btn-outline-success btn-sm ml-2" onclick="markAll('Present')">Mark All Present</button>
                <button type="button" class="btn btn-outline-danger btn-sm ml-1" onclick="markAll('Absent')">Mark All Absent</button>
                <button type="button" class="btn btn-outline-warning btn-sm ml-1" onclick="markAll('Late')">Mark All Late</button>
            </div>
            <form method="post" class="m-0">
                <input type="hidden" name="export_attendance" value="1" />
                <button type="submit" class="btn btn-secondary">Export Attendance CSV</button>
            </form>
        </div>
    </form>

    <div class="card mt-3">
        <div class="card-body">
            <h5>Class Summary (<?php echo date('F Y', strtotime($selected_date)); ?>)</h5>
            <p>Present: <?php echo (int)$summary['present']; ?> | Absent: <?php echo (int)$summary['absent']; ?> | Late: <?php echo (int)$summary['late']; ?></p>
            <p>Attendance Rate: <strong><?php echo $percent_present; ?>%</strong></p>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h5>Student Attendance History</h5>
            <?php if (!empty($students)): ?>
                <table class="table table-sm table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <?php if (!empty($student_attendance_history[$s['student_id']])): ?>
                                <?php foreach ($student_attendance_history[$s['student_id']] as $history_item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                                        <td><?php echo htmlspecialchars($history_item['date']); ?></td>
                                        <td><?php echo htmlspecialchars($history_item['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                                    <td colspan="2">No attendance record found.</td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No students in this class to display attendance history.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
function markAll(status) {
    var selects = document.querySelectorAll('select[name^="status["]');
    selects.forEach(function(sel){ sel.value = status; });
}
</script>