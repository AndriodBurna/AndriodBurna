<?php
include "config.php";
include "includes/auth.php";

// Only admins and teachers can edit results
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    die("Access denied!");
}

$message = "";

// Grade calculation function
function calculateGrade($marks) {
    if ($marks >= 90) return "A+";
    if ($marks >= 80) return "A";
    if ($marks >= 70) return "B";
    if ($marks >= 60) return "C";
    if ($marks >= 50) return "D";
    return "F";
}

// Get result ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid result ID.");
}
$result_id = intval($_GET['id']);

// Fetch result
$stmt = $conn->prepare("
    SELECT r.*, u.username AS student, s.subject_name, t.full_name AS teacher_name
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN subjects s ON r.subject_id = s.id
    LEFT JOIN users t ON r.teacher_id = t.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $result_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    die("Result not found.");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_result'])) {
    $marks         = intval($_POST['marks']);
    $term          = intval($_POST['term']);
    $academic_year = trim($_POST['academic_year']);
    $exam_type     = trim($_POST['exam_type']);
    $remarks       = trim($_POST['remarks']);

    // Auto calculate grade
    $grade = calculateGrade($marks);

    $stmt = $conn->prepare("
        UPDATE results 
        SET marks=?, grade=?, term=?, academic_year=?, exam_type=?, remarks=?, updated_at=NOW() 
        WHERE id=?
    ");
    $stmt->bind_param("isisssi",
        $marks, $grade, $term, $academic_year, $exam_type, $remarks, $result_id
    );
    if ($stmt->execute()) {
        $message = "✅ Result updated successfully! Grade recalculated: $grade";
        // Refresh result from DB
        $stmt2 = $conn->prepare("
            SELECT r.*, u.username AS student, s.subject_name, t.full_name AS teacher_name
            FROM results r
            JOIN users u ON r.student_id = u.id
            JOIN subjects s ON r.subject_id = s.id
            LEFT JOIN users t ON r.teacher_id = t.id
            WHERE r.id = ?
        ");
        $stmt2->bind_param("i", $result_id);
        $stmt2->execute();
        $result = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
    } else {
        $message = "❌ Error updating result: " . $conn->error;
    }
    $stmt->close();
}

include "includes/header.php";
?>

<h2>Edit Result</h2>

<?php if ($message): ?>
    <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="post">
    <p><strong>Student:</strong> <?php echo htmlspecialchars($result['student']); ?></p>
    <p><strong>Subject:</strong> <?php echo htmlspecialchars($result['subject_name']); ?></p>

    <label for="marks">Marks:</label><br>
    <input type="number" name="marks" min="0" max="100" value="<?php echo htmlspecialchars($result['marks']); ?>" required><br><br>

    <label for="term">Term:</label><br>
    <select name="term" required>
        <option value="1" <?php if ($result['term']==1) echo "selected"; ?>>Term 1</option>
        <option value="2" <?php if ($result['term']==2) echo "selected"; ?>>Term 2</option>
        <option value="3" <?php if ($result['term']==3) echo "selected"; ?>>Term 3</option>
        <option value="4" <?php if ($result['term']==4) echo "selected"; ?>>Term 4</option>
    </select><br><br>

    <label for="academic_year">Academic Year:</label><br>
    <input type="text" name="academic_year" value="<?php echo htmlspecialchars($result['academic_year']); ?>" required><br><br>

    <label for="exam_type">Exam Type:</label><br>
    <select name="exam_type" required>
        <option value="Test" <?php if ($result['exam_type']=="Test") echo "selected"; ?>>Test</option>
        <option value="Coursework" <?php if ($result['exam_type']=="Coursework") echo "selected"; ?>>Coursework</option>
        <option value="Final" <?php if ($result['exam_type']=="Final") echo "selected"; ?>>Final</option>
    </select><br><br>

    <label for="remarks">Remarks:</label><br>
    <textarea name="remarks" rows="3" cols="40"><?php echo htmlspecialchars($result['remarks']); ?></textarea><br><br>

    <button type="submit" name="update_result">Update Result</button>
</form>

<?php include "includes/footer.php"; ?>
