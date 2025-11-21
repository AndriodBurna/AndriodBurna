<?php
include "config.php";
include "includes/auth.php";

// Only admins and teachers can edit subjects
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    die("Access denied!");
}

$message = "";

// Validate subject ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid subject ID.");
}
$subject_id = intval($_GET['id']);

// Fetch subject
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$subject) {
    die("Subject not found.");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    $description  = trim($_POST['description']);

    if (!empty($subject_name)) {
        $stmt = $conn->prepare("
            UPDATE subjects 
            SET subject_name=?, description=? 
            WHERE id=?
        ");
        $stmt->bind_param("ssi", $subject_name, $description, $subject_id);
        if ($stmt->execute()) {
            $message = "✅ Subject updated successfully!";
            // Refresh subject
            $stmt2 = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
            $stmt2->bind_param("i", $subject_id);
            $stmt2->execute();
            $subject = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        } else {
            $message = "❌ Error updating subject: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "⚠️ Subject name cannot be empty.";
    }
}

include "includes/header.php";
?>

<h2>Edit Subject</h2>

<?php if ($message): ?>
    <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="post">
    <label for="subject_name">Subject Name:</label><br>
    <input type="text" name="subject_name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required><br><br>

    <label for="description">Description (optional):</label><br>
    <textarea name="description" rows="3" cols="40"><?php echo htmlspecialchars($subject['description'] ?? ''); ?></textarea><br><br>

    <button type="submit" name="update_subject">Update Subject</button>
</form>

<?php include "includes/footer.php"; ?>
