<?php
include 'includes/auth.php';
include 'includes/header.php';
require_once "config.php";

$sql = "SELECT r.id, s.name as student_name, t.name as term_name, c.name as class_name, sub.name as subject_name, r.marks, r.grade FROM results r 
        JOIN students s ON r.student_id = s.id
        JOIN terms t ON r.term_id = t.id
        JOIN classes c ON r.class_id = c.id
        JOIN subjects sub ON r.subject_id = sub.id";
$result = $conn->query($sql);
?>

<div class="container">
    <h2>Results List</h2>
    <a href="results_add.php" class="btn btn-primary">Add New Result</a>
    <table class="table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Term</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['student_name']; ?></td>
                <td><?php echo $row['term_name']; ?></td>
                <td><?php echo $row['class_name']; ?></td>
                <td><?php echo $row['subject_name']; ?></td>
                <td><?php echo $row['marks']; ?></td>
                <td><?php echo $row['grade']; ?></td>
                <td>
                    <a href="results_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-success">Edit</a>
                    <a href="results_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>