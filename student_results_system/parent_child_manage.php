<?php
include "config.php";
include "includes/auth.php";

// Only admins can access
if ($_SESSION['role'] !== 'admin') {
    die("Access denied!");
}

// Handle new assignment
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $parent_id = intval($_POST['parent_id']);
    $student_id = intval($_POST['student_id']);

    if ($parent_id && $student_id) {
        $sql = "INSERT INTO parent_child (parent_id, student_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $parent_id, $student_id);
        $stmt->execute();
        header("Location: parent_child_manage.php?success=1");
        exit;
    }
}

// Fetch parents
$parents = $conn->query("SELECT id, username FROM users WHERE role='parent' ORDER BY username ASC");

// Fetch students
$students = $conn->query("SELECT id, username FROM users WHERE role='student' ORDER BY username ASC");
$sql = "SELECT COUNT(DISTINCT student_id) AS total 
        FROM parent_child WHERE parent_id=$user_id";
$totalChildren = $conn->query($sql)->fetch_assoc()['total'];

// Get children’s results
$sql = "SELECT u.username AS student_name, r.subject, r.marks, r.grade
        FROM parent_child pc
        JOIN users u ON pc.student_id = u.id
        JOIN results r ON r.student_id = u.id
        WHERE pc.parent_id=$user_id";
$childrenResults = $conn->query($sql);


// Fetch existing links
$links = $conn->query("
    SELECT pc.id, p.username AS parent_name, s.username AS student_name
    FROM parent_child pc
    JOIN users p ON pc.parent_id = p.id
    JOIN users s ON pc.student_id = s.id
    ORDER BY p.username ASC, s.username ASC
");

include "includes/header.php";
?>

<h2>Manage Parent–Child Links</h2>

<?php if (isset($_GET['success'])): ?>
    <p style="color: green;">Link created successfully!</p>
<?php endif; ?>

<form method="POST">
    <label for="parent_id">Select Parent:</label><br>
    <select name="parent_id" required>
        <option value="">-- Choose Parent --</option>
        <?php while ($p = $parents->fetch_assoc()): ?>
            <option value="<?php echo $p['id']; ?>">
                <?php echo htmlspecialchars($p['username']); ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="student_id">Select Student:</label><br>
    <select name="student_id" required>
        <option value="">-- Choose Student --</option>
        <?php while ($s = $students->fetch_assoc()): ?>
            <option value="<?php echo $s['id']; ?>">
                <?php echo htmlspecialchars($s['username']); ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <button type="submit">Assign Student to Parent</button>
</form>

<h3>Existing Links</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Parent</th>
            <th>Student</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($l = $links->fetch_assoc()): ?>
        <tr>
            <td><?php echo $l['id']; ?></td>
            <td><?php echo htmlspecialchars($l['parent_name']); ?></td>
            <td><?php echo htmlspecialchars($l['student_name']); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include "includes/footer.php"; ?>
