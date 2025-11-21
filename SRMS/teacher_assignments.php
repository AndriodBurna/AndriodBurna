<?php
// Teacher Assignments Hub
require_once 'config.php';
require_once 'includes/helpers.php';

$teacher_id = isset($teacher_id) ? (int)$teacher_id : 0;
if ($teacher_id <= 0) {
    echo "<div class='alert alert-warning'>No teacher selected.</div>";
    return;
}

// Ensure tables
ensure_table($link, 'assignments', "CREATE TABLE IF NOT EXISTS `assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `term` varchar(50) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

ensure_table($link, 'assignment_submissions', "CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `submission_id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('submitted','late','missing') DEFAULT 'missing',
  PRIMARY KEY (`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Fetch classes and subjects for teacher
$classes = [];
$cq = mysqli_query($link, "SELECT c.class_id, c.class_name, c.stream FROM classes c JOIN teacher_class_assignments tca ON c.class_id=tca.class_id WHERE tca.teacher_id=$teacher_id ORDER BY c.class_name");
while ($row = mysqli_fetch_assoc($cq)) { $classes[] = $row; }

$subjects = [];
$sq = mysqli_query($link, "SELECT sub.subject_id, sub.subject_name, c.class_id FROM teacher_subject_assignments tsa JOIN subjects sub ON tsa.subject_id=sub.subject_id JOIN classes c ON sub.class_id=c.class_id WHERE tsa.teacher_id=$teacher_id ORDER BY sub.subject_name");
while ($row = mysqli_fetch_assoc($sq)) { $subjects[] = $row; }

$message = '';

// Handle create assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $title = mysqli_real_escape_string($link, $_POST['title'] ?? '');
    $description = mysqli_real_escape_string($link, $_POST['description'] ?? '');
    $due_date = mysqli_real_escape_string($link, $_POST['due_date'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $term = mysqli_real_escape_string($link, $_POST['term'] ?? '');
    $year = (int)($_POST['year'] ?? date('Y'));
    $attachment_path = NULL;
    if (!empty($_FILES['attachment']['name'])) {
        $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assignments';
        if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
        $fname = time().'_'.basename($_FILES['attachment']['name']);
        $dest = $upload_dir . DIRECTORY_SEPARATOR . $fname;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
            $attachment_path = 'assets/uploads/assignments/' . $fname;
        }
    }
    if ($class_id > 0 && $subject_id > 0 && $title && $due_date) {
        mysqli_query($link, "INSERT INTO assignments (teacher_id, class_id, subject_id, title, description, due_date, attachment_path, term, year) VALUES ($teacher_id, $class_id, $subject_id, '$title', '$description', '$due_date', " . ($attachment_path?"'".mysqli_real_escape_string($link,$attachment_path)."'":"NULL") . ", '$term', $year)");
        $message = "<div class='alert alert-success'>Assignment created.</div>";
    } else {
        $message = "<div class='alert alert-warning'>Please fill required fields.</div>";
    }
}

// Handle copy assignment (reuse)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['copy_assignment'])) {
    $src_id = (int)($_POST['assignment_id'] ?? 0);
    $new_due = mysqli_real_escape_string($link, $_POST['new_due_date'] ?? '');
    if ($src_id > 0 && $new_due) {
        $src = mysqli_query($link, "SELECT teacher_id, class_id, subject_id, title, description, attachment_path, term, year FROM assignments WHERE assignment_id=$src_id AND teacher_id=$teacher_id");
        if ($row = mysqli_fetch_assoc($src)) {
            mysqli_query($link, "INSERT INTO assignments (teacher_id, class_id, subject_id, title, description, due_date, attachment_path, term, year) VALUES ($teacher_id, {$row['class_id']}, {$row['subject_id']}, '".mysqli_real_escape_string($link,$row['title'])."', '".mysqli_real_escape_string($link,$row['description'])."', '$new_due', " . ($row['attachment_path']?"'".mysqli_real_escape_string($link,$row['attachment_path'])."'":"NULL") . ", '".mysqli_real_escape_string($link,$row['term'])."', {$row['year']})");
            $message = "<div class='alert alert-success'>Assignment copied.</div>";
        }
    }
}

// Fetch assignments for teacher
$assignments = [];
$aq = mysqli_query($link, "SELECT a.assignment_id, a.title, a.due_date, a.attachment_path, c.class_name, c.stream, sub.subject_name FROM assignments a JOIN classes c ON a.class_id=c.class_id JOIN subjects sub ON a.subject_id=sub.subject_id WHERE a.teacher_id=$teacher_id ORDER BY a.due_date DESC");
while ($row = mysqli_fetch_assoc($aq)) { $assignments[] = $row; }

// Helper: compute submission status counts for an assignment
function get_submission_status_counts($link, $assignment_id, $class_id) {
    $counts = ['submitted'=>0,'late'=>0,'missing'=>0];
    // count existing submissions
    $sq = mysqli_query($link, "SELECT status FROM assignment_submissions WHERE assignment_id=$assignment_id");
    while ($r = mysqli_fetch_assoc($sq)) { $counts[$r['status']] = ($counts[$r['status']] ?? 0) + 1; }
    // compute missing as total students - known submissions
    $students_res = mysqli_query($link, "SELECT COUNT(*) as c FROM students WHERE class_id=$class_id");
    $total_students = 0; if ($sr = mysqli_fetch_assoc($students_res)) { $total_students = (int)$sr['c']; }
    $known = $counts['submitted'] + $counts['late'];
    $counts['missing'] = max(0, $total_students - $known);
    return $counts;
}

?>

<div class="assignments-tab">
    <h3>Assignments Hub</h3>
    <?php echo $message; ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5>Create New Assignment</h5>
            <form method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required />
                    </div>
                    <div class="form-group col-md-4">
                        <label>Class</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo (int)$c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['stream']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Subject</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo (int)$s['subject_id']; ?>"><?php echo htmlspecialchars($s['subject_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" required />
                    </div>
                    <div class="form-group col-md-4">
                        <label>Term</label>
                        <input type="text" name="term" class="form-control" placeholder="e.g., Term 1" />
                    </div>
                    <div class="form-group col-md-4">
                        <label>Year</label>
                        <input type="number" name="year" class="form-control" value="<?php echo date('Y'); ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Attachment</label>
                    <input type="file" name="attachment" class="form-control-file" />
                </div>
                <button type="submit" name="create_assignment" class="btn btn-primary">Create Assignment</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5>My Assignments</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th><th>Class</th><th>Subject</th><th>Due</th><th>Attachment</th><th>Submissions</th><th>Actions</th><th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assignments)): ?>
                            <?php foreach ($assignments as $a): ?>
                                <?php $counts = get_submission_status_counts($link, (int)$a['assignment_id'], get_class_id_by_name($link, $a['class_name'])); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['title']); ?></td>
                                    <td><?php echo htmlspecialchars($a['class_name'].' '.$a['stream']); ?></td>
                                    <td><?php echo htmlspecialchars($a['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($a['due_date']); ?></td>
                                    <td><?php if ($a['attachment_path']): ?><a href="<?php echo htmlspecialchars($a['attachment_path']); ?>" target="_blank">Download</a><?php else: ?>-<?php endif; ?></td>
                                    <td>
                                        <span class="badge badge-success">Submitted: <?php echo (int)$counts['submitted']; ?></span>
                                        <span class="badge badge-warning">Late: <?php echo (int)$counts['late']; ?></span>
                                        <span class="badge badge-danger">Missing: <?php echo (int)$counts['missing']; ?></span>
                                    </td>
                                    <td>
                                        <form method="post" class="form-inline">
                                            <input type="hidden" name="assignment_id" value="<?php echo (int)$a['assignment_id']; ?>" />
                                            <input type="date" name="new_due_date" class="form-control form-control-sm mr-2" />
                                            <button type="submit" name="copy_assignment" class="btn btn-sm btn-secondary">Re-use</button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="teacher_grade_submission.php?assignment_id=<?php echo (int)$a['assignment_id']; ?>" class="btn btn-sm btn-info">Grade</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No assignments yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Helper to find class_id by name; used for status counts association.
function get_class_id_by_name($link, $class_name) {
    $res = mysqli_query($link, "SELECT class_id FROM classes WHERE class_name='".mysqli_real_escape_string($link, $class_name)."' LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res))) return (int)$row['class_id'];
    return 0;
}
?>