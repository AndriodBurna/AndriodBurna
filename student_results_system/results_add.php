<?php
include "config.php";
include "includes/auth.php";

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    die("Access denied!");
}

// Step 1: Get students
$students = $conn->query("SELECT id, full_name, username, course_id FROM users WHERE role='student' ORDER BY full_name ASC");

$student_id = $_POST['student_id'] ?? null;
$course_id = null;
$subjects = [];

// Step 2: If a student is chosen, fetch their course + subjects
if ($student_id) {
    $stmt = $conn->prepare("SELECT course_id FROM users WHERE id=? AND role='student'");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $course_id = $row['course_id'];
    }

    if ($course_id) {
        $stmt = $conn->prepare("SELECT s.id, s.subject_name, s.subject_code 
                                FROM subjects s
                                JOIN course_subject cs ON cs.subject_id = s.id
                                WHERE cs.course_id=?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $subjects = $stmt->get_result();
    }
}

// Step 3: Insert result
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['marks'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $marks      = $_POST['marks'];

    $stmt = $conn->prepare("INSERT INTO results (student_id, subject_id, marks) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $student_id, $subject_id, $marks);
    $stmt->execute();

    $success = "Result added successfully!";
}

include "includes/header.php";
?>

<h2>Add Student Result</h2>

<?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>

<form method="post">
    <label>Select Student:</label><br>
    <select name="student_id" onchange="this.form.submit()">
        <option value="">-- Choose Student --</option>
        <?php while ($s = $students->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>" <?= ($student_id==$s['id']?'selected':'') ?>>
                <?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['username']) ?>)
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <?php if ($course_id && $subjects->num_rows > 0): ?>
        <label>Subject:</label><br>
        <select name="subject_id" required>
            <?php while ($sub = $subjects->fetch_assoc()): ?>
                <option value="<?= $sub['id'] ?>">
                    <?= htmlspecialchars($sub['subject_name']) ?> (<?= htmlspecialchars($sub['subject_code']) ?>)
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Marks:</label><br>
        <input type="number" name="marks" required><br><br>

        <button type="submit">Save Result</button>
    <?php elseif ($student_id): ?>
        <p style="color:red;">No subjects assigned for this student's course.</p>
    <?php endif; ?>
</form>

<?php include "includes/footer.php"; ?>
