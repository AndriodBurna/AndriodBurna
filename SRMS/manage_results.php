<?php
session_start();
require_once 'includes/auth.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Shared page: admin and teacher can view results
require_any_role(['admin', 'teacher']);

$is_admin = $_SESSION['role'] === 'admin';

// Optional filtering: for teacher, show only their results
if ($is_admin) {
    $results = mysqli_query($link, "SELECT r.result_id, st.name AS student_name, sb.subject_name, r.term, r.year, r.marks, r.grade, r.remarks
                                   FROM results r
                                   JOIN students st ON r.student_id = st.student_id
                                   JOIN subjects sb ON r.subject_id = sb.subject_id
                                   ORDER BY r.year DESC, r.term ASC");
} else {
    // map teacher by email==username
    $username = $_SESSION['username'];
    $teacher_res = mysqli_query($link, "SELECT teacher_id FROM teachers WHERE email='" . mysqli_real_escape_string($link, $username) . "'");
    $teacher_id = 0;
    if ($t = mysqli_fetch_assoc($teacher_res)) { $teacher_id = (int) $t['teacher_id']; }
    $results = mysqli_query($link, "SELECT r.result_id, st.name AS student_name, sb.subject_name, r.term, r.year, r.marks, r.grade, r.remarks
                                   FROM results r
                                   JOIN students st ON r.student_id = st.student_id
                                   JOIN subjects sb ON r.subject_id = sb.subject_id
                                   WHERE r.teacher_id = $teacher_id
                                   ORDER BY r.year DESC, r.term ASC");
}
require_once 'includes/header.php';
?>

<div class="container">
    <h3>Manage Results</h3>
    <div class="mb-3">
        <a href="result_add.php" class="btn btn-success">Add Result</a>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Subject</th>
                <th>Term</th>
                <th>Year</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($results)): ?>
            <tr>
                <td><?php echo $row['result_id']; ?></td>
                <td><?php echo sanitize($row['student_name']); ?></td>
                <td><?php echo sanitize($row['subject_name']); ?></td>
                <td><?php echo sanitize($row['term']); ?></td>
                <td><?php echo (int)$row['year']; ?></td>
                <td><?php echo (int)$row['marks']; ?></td>
                <td><?php echo sanitize($row['grade']); ?></td>
                <td><?php echo sanitize($row['remarks']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>