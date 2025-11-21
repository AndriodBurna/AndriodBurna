<?php
include "config.php";
include "includes/auth.php";

// Only admins and teachers can access results management
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

// Handle Add Result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_result'])) {
    $student_id    = intval($_POST['student_id']);
    $subject_id    = intval($_POST['subject_id']);
    $marks         = intval($_POST['marks']);
    $term          = intval($_POST['term']);
    $academic_year = trim($_POST['academic_year']);
    $exam_type     = trim($_POST['exam_type']);
    $remarks       = trim($_POST['remarks']);
    $teacher_id    = $_SESSION['user_id']; // logged-in teacher

    // Auto calculate grade
    $grade = calculateGrade($marks);

    if ($student_id && $subject_id && $marks >= 0 && $term && $academic_year && $exam_type) {
        $stmt = $conn->prepare("
            INSERT INTO results (student_id, subject_id, marks, grade, term, academic_year, exam_type, teacher_id, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiisissis",
            $student_id, $subject_id, $marks, $grade, $term,
            $academic_year, $exam_type, $teacher_id, $remarks
        );
        if ($stmt->execute()) {
            $message = "✅ Result added successfully! Grade: $grade";
        } else {
            $message = "❌ Error: " . $conn->error;
        }
    } else {
        $message = "⚠️ Please fill all required fields.";
    }
}

// Fetch Students
$students = $conn->query("SELECT id, username FROM users WHERE role='student' ORDER BY username ASC");

// Fetch Subjects
$subjects = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name ASC");

// Fetch Results
$results = $conn->query("
    SELECT r.*, u.username AS student, s.subject_name, t.full_name AS teacher_name
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN subjects s ON r.subject_id = s.id
    LEFT JOIN users t ON r.teacher_id = t.id
    ORDER BY u.username, s.subject_name
");

include "includes/header.php";
?>

<h2>Manage Results</h2>

<?php if ($message): ?>
    <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<!-- Add Result Form -->
<form method="post" style="margin-bottom: 20px;">
    <label for="student_id">Student:</label><br>
    <select name="student_id" required>
        <option value="">-- Select Student --</option>
        <?php while ($s = $students->fetch_assoc()): ?>
            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['username']); ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="subject_id">Subject:</label><br>
    <select name="subject_id" required>
        <option value="">-- Select Subject --</option>
        <?php while ($sub = $subjects->fetch_assoc()): ?>
            <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="marks">Marks:</label><br>
    <input type="number" name="marks" min="0" max="100" required><br><br>

    <label for="sem">Term:</label><br>
    <select name="term" required>
        <option value="">-- Select sem --</option>
        <option value="1">Term 1</option>
        <option value="2">Term 2</option>
        <option value="3">Term 3</option>
        <option value="4">Term 4</option>
    </select><br><br>

    <label for="academic_year">Academic Year:</label><br>
    <input type="text" name="academic_year" placeholder="e.g., 2025" required><br><br>

    <label for="exam_type">Exam Type:</label><br>
    <select name="exam_type" required>
        <option value="">-- Select Exam Type --</option>
        <option value="Test">Test</option>
        <option value="Coursework">Coursework</option>
        <option value="Final">Final</option>
    </select><br><br>

    <label for="remarks">Remarks:</label><br>
    <textarea name="remarks" rows="3" cols="40"></textarea><br><br>

    <button type="submit" name="add_result">Add Result</button>
</form>

<!-- Results Table -->
<h3>All Results</h3>
<?php if ($results && $results->num_rows > 0): ?>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead style="background:#1abc9c; color:white;">
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>sem</th>
                <th>Academic Year</th>
                <th>Exam Type</th>
                <th>Teacher</th>
                <th>Remarks</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $results->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['student']); ?></td>
                <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                <td><?php echo htmlspecialchars($row['marks']); ?></td>
                <td><?php echo htmlspecialchars($row['grade'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['term'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['academic_year'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['exam_type'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['teacher_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                <td>
                    <a href="results_edit.php?id=<?php echo $row['id']; ?>">Edit</a> | 
                    <a href="results_delete.php?id=<?php echo $row['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this result?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No results found.</p>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
