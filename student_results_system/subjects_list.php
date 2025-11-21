<?php
// subjects_list.php
include "config.php";
include "includes/auth.php";

// Only admins & teachers allowed
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher') {
    die("Access denied!");
}

// detect if optional columns exist
$credits_exists     = $conn->query("SHOW COLUMNS FROM `subjects` LIKE 'credits'")->num_rows > 0;
$description_exists = $conn->query("SHOW COLUMNS FROM `subjects` LIKE 'description'")->num_rows > 0;

// build select list dynamically so we don't try to select missing columns
$selectCols = "id, subject_name, subject_code";
if ($credits_exists)     $selectCols .= ", credits";
if ($description_exists) $selectCols .= ", description";
$selectCols .= ", created_at";

$sql = "SELECT {$selectCols} FROM subjects ORDER BY subject_name DESC";
$result = $conn->query($sql);
if ($result === false) {
    die("Database query error: " . $conn->error);
}

include "includes/header.php";
?>

<div class="container">
    <h2>📚 Subjects List</h2>
    <p>Below is a list of all subjects available in the system.</p>

    <table style="width:100%; border-collapse: collapse; margin-top: 15px;" border="1" cellpadding="8" cellspacing="0">
        <thead style="background: #1abc9c; color: white;">
            <tr>
                <th>ID</th>
                <th>Subject Name</th>
                <th>Code</th>
                <?php if ($credits_exists): ?>
                    <th>Credits</th>
                <?php endif; ?>
                <?php if ($description_exists): ?>
                    <th>Description</th>
                <?php endif; ?>
                <th>Created At</th>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['subject_name']) ?></td>
                        <td><?= htmlspecialchars($row['subject_code'] ?? '') ?></td>

                        <?php if ($credits_exists): ?>
                            <td><?= isset($row['credits']) ? htmlspecialchars($row['credits']) : '-' ?></td>
                        <?php endif; ?>

                        <?php if ($description_exists): ?>
                            <td><?= isset($row['description']) ? htmlspecialchars($row['description']) : '-' ?></td>
                        <?php endif; ?>

                        <td><?= htmlspecialchars($row['created_at']) ?></td>

                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <td>
                                <a href="subjects_edit.php?id=<?= (int)$row['id'] ?>">✏️ Edit</a>
                                |
                                <a href="subjects_list.php?delete=<?= (int)$row['id'] ?>"
                                   onclick="return confirm('Delete this subject?');">🗑️ Delete</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="<?= 5 + ($credits_exists?1:0) + ($description_exists?1:0) ?>">No subjects found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include "includes/footer.php"; ?>
