<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Shared page: admin and teacher can add results
require_any_role(['admin', 'teacher']);
$is_admin = $_SESSION['role'] === 'admin';

// Load dropdowns
$students = mysqli_query($link, "SELECT student_id, name FROM students ORDER BY name ASC");
$subjects = mysqli_query($link, "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");

// Teacher mapping by username=email
$teacher_id = null;
if (!$is_admin) {
    $username = $_SESSION['username'];
    $res = mysqli_query($link, "SELECT teacher_id FROM teachers WHERE email='" . mysqli_real_escape_string($link, $username) . "'");
    if ($row = mysqli_fetch_assoc($res)) { $teacher_id = (int) $row['teacher_id']; }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $subject_id = (int) ($_POST['subject_id'] ?? 0);
    $term = sanitize($_POST['term'] ?? '');
    $year = (int) ($_POST['year'] ?? date('Y'));
    $marks = (int) ($_POST['marks'] ?? 0);

    if ($student_id <= 0) $errors[] = 'Select a student';
    if ($subject_id <= 0) $errors[] = 'Select a subject';
    if ($term === '') $errors[] = 'Select a term';
    if ($marks < 0 || $marks > 100) $errors[] = 'Marks must be between 0 and 100';

    // Determine teacher_id
    $final_teacher_id = $teacher_id;
    if ($is_admin) {
        $final_teacher_id = (int) ($_POST['teacher_id'] ?? 0);
        if ($final_teacher_id <= 0) $errors[] = 'Select a teacher';
    }

    if (empty($errors)) {
        $grade = compute_grade($marks);
        $remarks = grade_remarks($grade);
        $sql = "INSERT INTO results (student_id, subject_id, term, year, marks, grade, remarks, teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iisiiisi", $student_id, $subject_id, $term, $year, $marks, $grade, $remarks, $final_teacher_id);
            if (mysqli_stmt_execute($stmt)) {
                header('location: manage_results.php');
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Teachers dropdown for admin
$teachers = null;
if ($is_admin) {
    $teachers = mysqli_query($link, "SELECT teacher_id, name FROM teachers ORDER BY name ASC");
}
?>

<div class="container">
    <h3>Add Result</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <p><?php echo $e; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="result_add.php">
        <div class="form-group">
            <label>Student</label>
            <select name="student_id" class="form-control">
                <option value="">-- Select Student --</option>
                <?php while ($s = mysqli_fetch_assoc($students)): ?>
                    <option value="<?php echo $s['student_id']; ?>"><?php echo sanitize($s['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Subject</label>
            <select name="subject_id" class="form-control">
                <option value="">-- Select Subject --</option>
                <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
                    <option value="<?php echo $s['subject_id']; ?>"><?php echo sanitize($s['subject_name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Term</label>
            <select name="term" class="form-control">
                <option value="Term 1">Term 1</option>
                <option value="Term 2">Term 2</option>
                <option value="Term 3">Term 3</option>
            </select>
        </div>
        <div class="form-group">
            <label>Year</label>
            <input type="number" name="year" class="form-control" value="<?php echo date('Y'); ?>">
        </div>
        <div class="form-group">
            <label>Marks</label>
            <input type="number" name="marks" class="form-control" min="0" max="100">
        </div>
        <?php if ($is_admin): ?>
        <div class="form-group">
            <label>Teacher</label>
            <select name="teacher_id" class="form-control">
                <option value="">-- Select Teacher --</option>
                <?php while ($t = mysqli_fetch_assoc($teachers)): ?>
                    <option value="<?php echo $t['teacher_id']; ?>"><?php echo sanitize($t['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Save Result</button>
        <a href="manage_results.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>