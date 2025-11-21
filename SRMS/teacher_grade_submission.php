<?php
session_start();
require_once 'includes/auth.php';
require_role('teacher');
require_once 'config.php';
require_once 'includes/helpers.php';
require_once 'includes/header.php';

// Handle assignment ID parameter from teacher_assignments.php
if (!isset($_GET['assignment_id']) || !is_numeric($_GET['assignment_id'])) {
    echo '<div class="alert alert-danger">Invalid or missing assignment ID.</div>';
    require_once 'includes/footer.php';
    exit;
}

$assignment_id = (int)$_GET['assignment_id'];

// Fetch assignment details from database
$assignment_query = "SELECT a.*, sub.subject_name, c.class_name
FROM assignments a
JOIN subjects sub ON a.subject_id = sub.subject_id
JOIN classes c ON a.class_id = c.class_id
WHERE a.assignment_id = ? AND a.teacher_id = ?";
$stmt = mysqli_prepare($link, $assignment_query);
mysqli_stmt_bind_param($stmt, 'ii', $assignment_id, $teacher_id);
mysqli_stmt_execute($stmt);
$assignment_result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($assignment_result);

if (!$assignment) {
    echo '<div class="alert alert-danger">Assignment not found or you do not have permission to grade it.</div>';
    require_once 'includes/footer.php';
    exit;
}

// Fetch student submissions for this assignment
$submissions_query = "SELECT 
    ss.submission_id,
    ss.student_id,
    s.name AS student_name,
    s.student_uid AS admission_number,
    ss.submission_path,
    ss.submitted_at,
    ss.status,
    ss.grade,
    ss.feedback
FROM assignment_submissions ss
JOIN students s ON ss.student_id = s.student_id
WHERE ss.assignment_id = ?
ORDER BY s.name ASC";
$stmt_submissions = mysqli_prepare($link, $submissions_query);
mysqli_stmt_bind_param($stmt_submissions, 'i', $assignment_id);
mysqli_stmt_execute($stmt_submissions);
$submissions_result = mysqli_stmt_get_result($stmt_submissions);
$submissions = mysqli_fetch_all($submissions_result, MYSQLI_ASSOC);

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grades'])) {
    foreach ($_POST['students'] as $student_id => $data) {
        $grade = (int)$data['grade'];
        $feedback = sanitize($data['feedback']);
        $submission_id = (int)$data['submission_id'];

        // Update submission record
        $update_query = "UPDATE assignment_submissions SET grade = ?, feedback = ? WHERE submission_id = ? AND student_id = ? AND assignment_id = ?";
        $stmt_update = mysqli_prepare($link, $update_query);
        mysqli_stmt_bind_param($stmt_update, 'isiii', $grade, $feedback, $submission_id, $student_id, $assignment_id);
        mysqli_stmt_execute($stmt_update);
    }
    $_SESSION['success_message'] = 'Grades and feedback submitted successfully!';
    header('Location: teacher_grade_submission.php?assignment_id=' . $assignment_id);
    exit;
}
?>

<div class="container mt-4">
    <div class="breadcrumbs mb-3">
        <a href="teacher_dashboard.php">Dashboard</a> &raquo;
        <a href="teacher_assignments.php">Assignments</a> &raquo;
        <span>Grade Assignment: <?php echo htmlspecialchars($assignment['title']); ?></span>
    </div>

    <h2>Grade Assignment: <?php echo htmlspecialchars($assignment['title']); ?></h2>
    <p class="text-muted">Subject: <?php echo htmlspecialchars($assignment['subject_name']); ?> | Class: <?php echo htmlspecialchars($assignment['class_name']); ?></p>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Student Submissions & Grading</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Admission No.</th>
                            <th>Submission Status</th>
                            <th>Submitted At</th>
                            <th>Submission File</th>
                            <th>Grade (0-100)</th>
                            <th>Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($submissions)): ?>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($submission['admission_number']); ?></td>
                                    <td>
                                        <?php
                                            $status_class = '';
                                            switch ($submission['status']) {
                                                case 'submitted': $status_class = 'badge-success'; break;
                                                case 'late': $status_class = 'badge-warning'; break;
                                                case 'missing': $status_class = 'badge-danger'; break;
                                                default: $status_class = 'badge-info'; break;
                                            }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($submission['status']); ?></span>
                                    </td>
                                    <td><?php echo $submission['submitted_at'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($submission['submitted_at']))) : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($submission['submission_path']): ?>
                                            <a href="<?php echo htmlspecialchars($submission['submission_path']); ?>" target="_blank" class="btn btn-sm btn-primary">View Submission</a>
                                        <?php else: ?>
                                            No File
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="hidden" name="students[<?php echo $submission['student_id']; ?>][submission_id]" value="<?php echo $submission['submission_id']; ?>">
                                        <input type="number" name="students[<?php echo $submission['student_id']; ?>][grade]" class="form-control" min="0" max="100" value="<?php echo htmlspecialchars($submission['grade'] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <textarea name="students[<?php echo $submission['student_id']; ?>][feedback]" class="form-control" rows="2"><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No student submissions found for this assignment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="submit" name="submit_grades" class="btn btn-success mt-3">Submit Grades & Feedback</button>
            </form>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>