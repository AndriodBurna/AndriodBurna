<?php
// This file is included via index.php after session and header.
require_once 'config.php';
require_once 'includes/helpers.php';

// Core KPIs
$students_count = (int) mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM students"))['count'];
$teachers_count = (int) mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM teachers"))['count'];
$parents_count = (int) mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM parents"))['count'];
$classes_count = (int) mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as count FROM classes"))['count'];

$current_year = (int) date('Y');
$active_classes_count = (int) mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM classes WHERE year = $current_year"))['c'];
$results_this_year = (int) mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM results WHERE year = $current_year"))['c'];

// Pending actions
$students_without_parent = (int) mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM students WHERE parent_id IS NULL"))['c'];
$students_without_class = (int) mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM students WHERE class_id IS NULL"))['c'];
// Use assignment mapping table with legacy fallback so counts auto-update
$teachers_without_subject = (int) mysqli_fetch_assoc(mysqli_query($link, "
    SELECT COUNT(*) AS c
    FROM teachers t
    WHERE (t.subject_id IS NULL OR t.subject_id = 0)
      AND NOT EXISTS (
        SELECT 1 FROM teacher_subject_assignments tsa WHERE tsa.teacher_id = t.teacher_id
      )
"))['c'];
$unassigned_subjects = (int) mysqli_fetch_assoc(mysqli_query($link, "
    SELECT COUNT(*) AS c
    FROM subjects s
    WHERE NOT EXISTS (
        SELECT 1 FROM teacher_subject_assignments tsa WHERE tsa.subject_id = s.subject_id
    )
    AND NOT EXISTS (
        SELECT 1 FROM teachers t WHERE t.subject_id = s.subject_id
    )
"))['c'];

// Pending result approvals: results with no approval or latest status pending
$pending_approvals = 0;
$pa_sql = "SELECT COUNT(*) AS c FROM results r
LEFT JOIN (
  SELECT result_id, MAX(approval_id) AS latest_id FROM result_approvals GROUP BY result_id
) la ON la.result_id = r.result_id
LEFT JOIN result_approvals a ON a.approval_id = la.latest_id
WHERE a.approval_id IS NULL OR a.status = 'pending'";
$pending_approvals = (int) mysqli_fetch_assoc(mysqli_query($link, $pa_sql))['c'];

// Attendance summary: today's totals and recent entries
$today = date('Y-m-d');
$att_today = mysqli_fetch_assoc(mysqli_query($link, "SELECT COALESCE(SUM(present_count),0) AS present, COALESCE(SUM(absent_count),0) AS absent FROM attendance WHERE date='$today'"));
$att_recent = mysqli_query($link, "SELECT a.date, c.class_name, a.present_count, a.absent_count FROM attendance a JOIN classes c ON a.class_id=c.class_id ORDER BY a.date DESC, a.attendance_id DESC LIMIT 5");

// Chart data: Students per Class
$spc_labels = [];
$spc_counts = [];
$spc_res = mysqli_query($link, "SELECT c.class_name, c.year, COUNT(s.student_id) AS cnt FROM classes c LEFT JOIN students s ON s.class_id = c.class_id GROUP BY c.class_id ORDER BY c.year DESC, c.class_name ASC");
while ($row = mysqli_fetch_assoc($spc_res)) {
    $spc_labels[] = $row['class_name'] . ' (' . (int)$row['year'] . ')';
    $spc_counts[] = (int) $row['cnt'];
}

// Chart data: Results per Year
$rpy_labels = [];
$rpy_counts = [];
$rpy_res = mysqli_query($link, "SELECT year, COUNT(*) AS cnt FROM results GROUP BY year ORDER BY year ASC");
while ($row = mysqli_fetch_assoc($rpy_res)) {
    $rpy_labels[] = (string) $row['year'];
    $rpy_counts[] = (int) $row['cnt'];
}

// Chart data: Teachers per Subject
$tps_labels = [];
$tps_counts = [];
$tps_res = mysqli_query($link, "SELECT sb.subject_name, COUNT(t.teacher_id) AS cnt FROM subjects sb LEFT JOIN teachers t ON t.subject_id = sb.subject_id GROUP BY sb.subject_id ORDER BY sb.subject_name ASC");
while ($row = mysqli_fetch_assoc($tps_res)) {
    $tps_labels[] = $row['subject_name'];
    $tps_counts[] = (int) $row['cnt'];
}
?>

<div class="container">
    <div class="dashboard-header">
        <h3>Admin Dashboard</h3>
        <div class="dashboard-clock">
            <span id="liveDate"></span> <span id="liveTime"></span>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row">
        <div class="col-md-3">
            <div class="card dashboard-card"><div class="card-body">
                <h5 class="card-title">Total Students</h5>
                <p class="card-text"><?php echo $students_count; ?></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card"><div class="card-body">
                <h5 class="card-title">Total Teachers</h5>
                <p class="card-text"><?php echo $teachers_count; ?></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card"><div class="card-body">
                <h5 class="card-title">Active Classes (<?php echo $current_year; ?>)</h5>
                <p class="card-text"><?php echo $active_classes_count; ?></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card"><div class="card-body">
                <h5 class="card-title">Results Entered (<?php echo $current_year; ?>)</h5>
                <p class="card-text"><?php echo $results_this_year; ?></p>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card"><div class="card-body">
                <h5 class="card-title">Pending Result Approvals</h5>
                <p class="card-text"><?php echo $pending_approvals; ?></p>
            </div></div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <h5 class="card-title">Students per Class</h5>
                <canvas id="studentsPerClass"></canvas>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <h5 class="card-title">Results per Year</h5>
                <canvas id="resultsPerYear"></canvas>
            </div></div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <h5 class="card-title">Teachers per Subject</h5>
                <canvas id="teachersPerSubject"></canvas>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <h5 class="card-title">Attendance Summary</h5>
                <p>Today: Present <strong><?php echo (int)$att_today['present']; ?></strong>, Absent <strong><?php echo (int)$att_today['absent']; ?></strong></p>
                <table class="table table-sm">
                    <thead>
                        <tr><th>Date</th><th>Class</th><th>Present</th><th>Absent</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($att_recent)): ?>
                        <tr>
                            <td><?php echo sanitize($row['date']); ?></td>
                            <td><?php echo sanitize($row['class_name']); ?></td>
                            <td><?php echo (int)$row['present_count']; ?></td>
                            <td><?php echo (int)$row['absent_count']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="manage_attendance.php" class="btn btn-outline-primary btn-sm">View Attendance</a>
            </div></div>
        </div>
    </div>

    <!-- Management & Pending Actions -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card"><div class="card-body">
                <h5 class="card-title">Quick Links</h5>
                <a class="btn btn-primary mb-2" href="manage_students.php">Manage Students</a>
                <a class="btn btn-success mb-2 ml-2" href="student_add.php">Add Student</a>
                <a class="btn btn-primary mb-2 ml-2" href="manage_teachers.php">Manage Teachers</a>
                <a class="btn btn-success mb-2 ml-2" href="teacher_add.php">Add Teacher</a>
                <a class="btn btn-primary mb-2 ml-2" href="manage_classes.php">Manage Classes</a>
                <a class="btn btn-success mb-2 ml-2" href="class_add.php">Add Class</a>
                <a class="btn btn-primary mb-2 ml-2" href="manage_subjects.php">Manage Subjects</a>
                <a class="btn btn-success mb-2 ml-2" href="subject_add.php">Add Subject</a>
                <a class="btn btn-primary mb-2 ml-2" href="manage_results.php">Manage Results</a>
                <a class="btn btn-success mb-2 ml-2" href="result_add.php">Add Result</a>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h5 class="card-title">Pending Actions</h5>
                <ul class="list-unstyled">
                    <li>Students without parent: <strong><?php echo $students_without_parent; ?></strong> <a href="manage_students.php" class="ml-2">Review</a></li>
                    <li>Students without class: <strong><?php echo $students_without_class; ?></strong> <a href="manage_students.php" class="ml-2">Assign</a></li>
                    <li>Teachers without subject: <strong><?php echo $teachers_without_subject; ?></strong> <a href="teacher_assignments_admin.php" class="ml-2">Assign to Subjects</a></li>
                    <li>Unassigned subjects: <strong><?php echo $unassigned_subjects; ?></strong> <a href="teacher_assignments_admin.php" class="ml-2">Assign to Teachers</a></li>
                </ul>
            </div></div>
        </div>
    </div>
</div>

<!-- Charts JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function(){
        // Students per Class
        var spcCtx = document.getElementById('studentsPerClass');
        if (spcCtx) {
            new Chart(spcCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($spc_labels); ?>,
                    datasets: [{
                        label: 'Students',
                        data: <?php echo json_encode($spc_counts); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });
        }

        // Results per Year
        var rpyCtx = document.getElementById('resultsPerYear');
        if (rpyCtx) {
            new Chart(rpyCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($rpy_labels); ?>,
                    datasets: [{
                        label: 'Results',
                        data: <?php echo json_encode($rpy_counts); ?>,
                        fill: false,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        tension: 0.3
                    }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });
        }

        // Teachers per Subject
        var tpsCtx = document.getElementById('teachersPerSubject');
        if (tpsCtx) {
            new Chart(tpsCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($tps_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($tps_counts); ?>,
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)'
                        ]
                    }]
                }
            });
        }
    })();
    // Live date/time
    (function(){
        function tick(){
            var d = new Date();
            var dateStr = d.toLocaleDateString();
            var timeStr = d.toLocaleTimeString();
            var ld = document.getElementById('liveDate');
            var lt = document.getElementById('liveTime');
            if (ld) ld.textContent = dateStr;
            if (lt) lt.textContent = timeStr;
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
<!-- $ensureAttendance = "CREATE TABLE IF NOT EXISTS attendance (
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

$ensureApprovals = "CREATE TABLE IF NOT EXISTS result_approvals (
  approval_id INT(11) NOT NULL AUTO_INCREMENT,
  result_id INT(11) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  approved_by INT(11) DEFAULT NULL,
  approved_at TIMESTAMP NULL DEFAULT current_timestamp(),
  comments TEXT DEFAULT NULL,
  PRIMARY KEY (approval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($link, $ensureApprovals); -->