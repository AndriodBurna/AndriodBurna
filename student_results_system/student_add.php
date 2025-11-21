<?php
include "config.php";
include "includes/auth.php";
include "includes/header.php";

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    die("Access denied!");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $class_id = trim($_POST['class_id']);
    $stream = trim($_POST['stream']);

    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $error = "Username already exists!";
    } else {
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email, class_id, stream) VALUES (?, ?, 'student', ?, ?, ?, ?)");
    // There are 6 placeholders (?), so type string should have 6 characters
    $stmt->bind_param("ssssss", $username, $password, $full_name, $email, $class_id, $stream);

        if ($stmt->execute()) {
            $success = "Student added successfully!";
            // Clear form fields
            $_POST = array();
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}
?>

<h2>Add Student</h2>
<?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>

<form method="post">
    <label>Assign Class:</label><br>
    <select name="class_id" required>
        <option value="">-- Select Class --</option>
        <option value="Mathematics" <?= (isset($_POST['class_id']) && $_POST['class_id'] == 'Mathematics') ? 'selected' : '' ?>>Mathematics</option>
        <option value="English" <?= (isset($_POST['class_id']) && $_POST['class_id'] == 'English') ? 'selected' : '' ?>>English</option>
        <option value="Biology" <?= (isset($_POST['class_id']) && $_POST['class_id'] == 'Biology') ? 'selected' : '' ?>>Biology</option>
        <option value="Chemistry" <?= (isset($_POST['class_id']) && $_POST['class_id'] == 'Chemistry') ? 'selected' : '' ?>>Chemistry</option>
        <option value="Physics" <?= (isset($_POST['class_id']) && $_POST['class_id'] == 'Physics') ? 'selected' : '' ?>>Physics</option>
        <option value="History" <?= (isset($_POST['class_id']) && $_POST['class_id'] == 'History') ? 'selected' : '' ?>>History</option>
        <option value="English & Literature" <?= (isset($_POST['class_id']) && $_POST['class_id'] == 'English & Literature') ? 'selected' : '' ?>>English & Literature</option>
        <?php
        // If you have a courses table, uncomment this section
        /*
        $cRes = $conn->query("SELECT * FROM courses ORDER BY class_name ASC");
        while ($c = $cRes->fetch_assoc()):
        ?>
            <option value="<?= $c['id'] ?>" <?= (isset($_POST['class_id']) && $_POST['class_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['class_name']) ?>
            </option>
        <?php endwhile; */
        ?>
    </select>
    <br><br>

    <label>Username:</label>
    <input type="text" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
    <br><br>

    <label>Password:</label>
    <input type="password" name="password" required>
    <br><br>

    <label>Full Name:</label>
    <input type="text" name="full_name" value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>">
    <br><br>

    <label>Email:</label>
    <input type="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
    <br><br>

    <label>Stream:</label>
    <select name="stream" required>
        <option value="">-- Select Stream --</option>
        <option value="Science" <?= (isset($_POST['stream']) && $_POST['stream'] == 'Science') ? 'selected' : '' ?>>Science</option>
        <option value="Arts" <?= (isset($_POST['stream']) && $_POST['stream'] == 'Arts') ? 'selected' : '' ?>>Arts</option>
        <option value="Commerce" <?= (isset($_POST['stream']) && $_POST['stream'] == 'Commerce') ? 'selected' : '' ?>>Commerce</option>
    </select>
    <br><br>

    <button type="submit" class="btn">Save Student</button>
    <a href="student_manage.php" class="btn" style="background-color: #6c757d; margin-left: 10px;">Cancel</a>
</form>

<?php include "includes/footer.php"; ?>