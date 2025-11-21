<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure optional class columns exist for richer assignment context
ensure_column($link, 'classes', 'section', '`section` varchar(50) DEFAULT NULL');
ensure_column($link, 'classes', 'stream', '`stream` varchar(255) DEFAULT NULL');
ensure_column($link, 'classes', 'year', '`year` int(11) DEFAULT NULL');

// Ensure assignment tables exist
ensure_table($link, 'teacher_class_assignments', "CREATE TABLE IF NOT EXISTS `teacher_class_assignments` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `teacher_id` int(11) NOT NULL,\n  `class_id` int(11) NOT NULL,\n  `is_class_teacher` tinyint(1) DEFAULT 0,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
ensure_table($link, 'teacher_subject_assignments', "CREATE TABLE IF NOT EXISTS `teacher_subject_assignments` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `teacher_id` int(11) NOT NULL,\n  `subject_id` int(11) NOT NULL,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$teachers = mysqli_query($link, "SELECT teacher_id, name FROM teachers ORDER BY name ASC");
$classes = mysqli_query($link, "SELECT class_id, class_name, section, stream, year FROM classes ORDER BY year DESC, class_name ASC");
$subjects = mysqli_query($link, "SELECT subject_id, subject_name, class_id FROM subjects ORDER BY subject_name ASC");

$selected_teacher = (int) ($_GET['teacher_id'] ?? 0);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_teacher = (int) ($_POST['teacher_id'] ?? 0);
    $class_id = (int) ($_POST['class_id'] ?? 0);
    $subject_id = (int) ($_POST['subject_id'] ?? 0);
    $is_class_teacher = isset($_POST['is_class_teacher']) ? 1 : 0;

    if ($selected_teacher > 0) {
        if ($class_id > 0) {
            mysqli_query($link, "INSERT INTO teacher_class_assignments (teacher_id, class_id, is_class_teacher) VALUES ($selected_teacher, $class_id, $is_class_teacher)");
            $msg = '<div class="alert alert-success">Class assignment saved.</div>';
        }
        if ($subject_id > 0) {
            mysqli_query($link, "INSERT INTO teacher_subject_assignments (teacher_id, subject_id) VALUES ($selected_teacher, $subject_id)");
            $msg .= '<div class="alert alert-success">Subject assignment saved.</div>';
        }
    } else {
        $msg = '<div class="alert alert-warning">Select a teacher before assigning.</div>';
    }
}

$class_assignments = null;
$subject_assignments = null;
if ($selected_teacher > 0) {
    $class_assignments = mysqli_query($link, "SELECT tca.id, c.class_name, c.section, c.stream, c.year, tca.is_class_teacher FROM teacher_class_assignments tca JOIN classes c ON tca.class_id=c.class_id WHERE tca.teacher_id=$selected_teacher ORDER BY c.year DESC, c.class_name ASC");
    $subject_assignments = mysqli_query($link, "SELECT tsa.id, s.subject_name FROM teacher_subject_assignments tsa JOIN subjects s ON tsa.subject_id=s.subject_id WHERE tsa.teacher_id=$selected_teacher ORDER BY s.subject_name ASC");
}
?>

<div class="container">
    <h3>Teacher Assignments</h3>
    <?php echo $msg; ?>
    <form method="get" class="form-inline mb-3">
        <label class="mr-2">Select Teacher</label>
        <select name="teacher_id" class="form-control mr-2" onchange="this.form.submit()">
            <option value="">-- Choose --</option>
            <?php while ($t = mysqli_fetch_assoc($teachers)): ?>
                <option value="<?php echo (int)$t['teacher_id']; ?>" <?php echo $selected_teacher===(int)$t['teacher_id']?'selected':''; ?>><?php echo sanitize($t['name']); ?></option>
            <?php endwhile; ?>
        </select>
    </form>

    <form method="post" class="card card-body mb-4">
        <input type="hidden" name="teacher_id" value="<?php echo $selected_teacher; ?>">
        <div class="form-row">
            <div class="form-group col-md-5">
                <label>Assign Class</label>
                <select name="class_id" class="form-control">
                    <option value="">-- Select Class --</option>
                    <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                        <option value="<?php echo (int)$c['class_id']; ?>"><?php echo sanitize($c['class_name']); ?> <?php echo sanitize($c['section']); ?> (<?php echo (int)$c['year']; ?>)</option>
                    <?php endwhile; ?>
                </select>
                <div class="form-check mt-2">
                    <input type="checkbox" class="form-check-input" id="isct" name="is_class_teacher">
                    <label class="form-check-label" for="isct">Set as Class Teacher</label>
                </div>
            </div>
            <div class="form-group col-md-5">
                <label>Assign Subject</label>
                <select name="subject_id" class="form-control">
                    <option value="">-- Select Subject --</option>
                    <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
                        <option value="<?php echo (int)$s['subject_id']; ?>"><?php echo sanitize($s['subject_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary btn-block">Save Assignments</button>
            </div>
        </div>
    </form>

    <?php if ($selected_teacher > 0): ?>
    <div class="row">
        <div class="col-md-6">
            <h5>Class Assignments</h5>
            <table class="table table-sm table-bordered">
                <thead><tr><th>Class</th><th>Year</th><th>Class Teacher</th></tr></thead>
                <tbody>
                <?php if ($class_assignments && $class_assignments instanceof mysqli_result): ?>
                    <?php while ($ca = mysqli_fetch_assoc($class_assignments)): ?>
                        <tr>
                            <td><?php echo sanitize($ca['class_name'] . ' ' . ($ca['section'] ?? '')); ?></td>
                            <td><?php echo (int)$ca['year']; ?></td>
                            <td><?php echo ((int)$ca['is_class_teacher']) ? 'Yes' : 'No'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No assignments yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <h5>Subject Assignments</h5>
            <table class="table table-sm table-bordered">
                <thead><tr><th>Subject</th></tr></thead>
                <tbody>
                <?php if ($subject_assignments && $subject_assignments instanceof mysqli_result): ?>
                    <?php while ($sa = mysqli_fetch_assoc($subject_assignments)): ?>
                        <tr>
                            <td><?php echo sanitize($sa['subject_name']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td>No assignments yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>