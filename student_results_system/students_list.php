<?php
include "config.php";
include "includes/auth.php";

// Only allow teachers (admins can see all students if needed)
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['user_id']; 
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$class      = isset($_GET['class']) ? trim($_GET['class']) : "";

// Build query
$sql = "
    SELECT DISTINCT u.id, u.full_name, u.username
    FROM users u
    JOIN results r ON r.student_id = u.id
    JOIN subjects s ON s.id = r.subject_id
    WHERE 1=1
";

$params = [];
$types  = "";

// If teacher, filter by teacher_id
if ($_SESSION['role'] === 'teacher') {
    $sql .= " AND r.id = ? ";
    $params[] = $teacher_id;
    $types .= "i";
}

// Filter by subject if provided
if ($subject_id > 0) {
    $sql .= " AND r.subject_id = ? ";
    $params[] = $subject_id;
    $types .= "i";
}

// Filter by class if provided
if (!empty($class)) {
    $sql .= " AND u.class = ? ";
    $params[] = $class;
    $types .= "s";
}

$sql .= " ORDER BY u.full_name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();
?>

<?php include "includes/header.php"; ?>

    <div class="main-content">
        <h1>👨‍🎓 Students List</h1>

        <form method="get" style="margin-bottom:20px;">
            <label>Subject:
                <select name="subject_id">
                    <option value="">-- All Subjects --</option>
                    <?php
                    $subjQuery = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name");
                    while ($sub = $subjQuery->fetch_assoc()): ?>
                        <option value="<?= $sub['id'] ?>" <?= $subject_id == $sub['id'] ? "selected" : "" ?>>
                            <?= htmlspecialchars($sub['subject_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>
            <label>Class:
                <input type="text" name="class" value="<?= htmlspecialchars($class) ?>" placeholder="e.g. S1">
            </label>
            <button type="submit">🔍 Filter</button>
        </form>

        <?php if ($students && $students->num_rows > 0): ?>
            <table border="1" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Class</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['class']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No students found for this filter.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
