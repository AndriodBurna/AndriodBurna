<?php
include "config.php";
include "includes/auth.php";

if ($_SESSION['role'] !== 'admin') die("Access denied!");

// Fetch courses
$courses = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'];
    $subjects  = $_POST['subjects']; // array of subject IDs

    // Clear old assignments
    $conn->query("DELETE FROM course_subject WHERE course_id=$course_id");

    // Insert new
    $stmt = $conn->prepare("INSERT INTO course_subject (course_id, subject_id) VALUES (?, ?)");
    foreach ($subjects as $sub_id) {
        $stmt->bind_param("ii", $course_id, $sub_id);
        $stmt->execute();
    }
    $success = "Subjects updated for this course!";
}

// If a course is selected, load its assigned subjects
$selected_course = $_GET['course_id'] ?? null;
$assigned = [];
if ($selected_course) {
    $res = $conn->query("SELECT subject_id FROM course_subject WHERE course_id=$selected_course");
    while ($r = $res->fetch_assoc()) {
        $assigned[] = $r['subject_id'];
    }
}

// Fetch all subjects
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name ASC");

include "includes/header.php";
?>

<h2>Assign Subjects to Courses</h2>

<?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>

<form method="get">
    <label>Select Course:</label>
    <select name="course_id" onchange="this.form.submit()">
        <option value="">-- Choose a course --</option>
        <?php while ($c = $courses->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= ($selected_course==$c['id']?'selected':'') ?>>
                <?= htmlspecialchars($c['course_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<?php if ($selected_course): ?>
<form method="post">
    <input type="hidden" name="course_id" value="<?= $selected_course ?>">

    <h3>Assign Subjects</h3>
    <?php while ($s = $subjects->fetch_assoc()): ?>
        <label>
            <input type="checkbox" name="subjects[]" value="<?= $s['id'] ?>"
                <?= in_array($s['id'], $assigned) ? 'checked' : '' ?>>
            <?= htmlspecialchars($s['subject_name']) ?> (<?= htmlspecialchars($s['subject_code']) ?>)
        </label><br>
    <?php endwhile; ?>

    <br>
    <button type="submit">Save Assignments</button>
</form>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
