<?php
// File to store student results
$results_file = 'student_results.txt';

// Function to calculate grade based on average
function getGrade($average) {
    if ($average >= 90) return 'A';
    elseif ($average >= 80) return 'B';
    elseif ($average >= 70) return 'C';
    elseif ($average >= 60) return 'D';
    else return 'F';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['student_name'];
    $id = $_POST['student_id'];
    $subject1 = (int)$_POST['subject1'];
    $subject2 = (int)$_POST['subject2'];
    $subject3 = (int)$_POST['subject3'];

    $total = $subject1 + $subject2 + $subject3;
    $average = round($total / 3, 2);
    $grade = getGrade($average);

    // Prepare result line
    $result = [
        'name' => $name,
        'id' => $id,
        'subject1' => $subject1,
        'subject2' => $subject2,
        'subject3' => $subject3,
        'total' => $total,
        'average' => $average,
        'grade' => $grade
    ];

    // Save to file (append as JSON line)
    file_put_contents($results_file, json_encode($result) . PHP_EOL, FILE_APPEND);
}

// Read all results
$students = [];
if (file_exists($results_file)) {
    $lines = file($results_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $students[] = json_decode($line, true);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Results Management System</title>
    <style>
        table { border-collapse: collapse; width: 80%; margin: 20px auto;}
        th, td { border: 1px solid #333; padding: 8px; text-align: center;}
        th { background-color: #eee;}
        form { width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #333;}
        label { display: block; margin-top: 10px;}
    </style>
</head>
<body>
    <h2 style="text-align:center;">Student Marks Entry</h2>
    <form method="post">
        <label>Student Name: <input type="text" name="student_name" required></label>
        <label>Student ID: <input type="text" name="student_id" required></label>
        <label>Subject 1 Marks: <input type="number" name="subject1" min="0" max="100" required></label>
        <label>Subject 2 Marks: <input type="number" name="subject2" min="0" max="100" required></label>
        <label>Subject 3 Marks: <input type="number" name="subject3" min="0" max="100" required></label>
        <br>
        <input type="submit" value="Submit">
    </form>

    <h2 style="text-align:center;">All Student Results</h2>
    <table>
        <tr>
            <th>Name</th>
            <th>ID</th>
            <th>Subject 1</th>
            <th>Subject 2</th>
            <th>Subject 3</th>
            <th>Total</th>
            <th>Average</th>
            <th>Grade</th>
        </tr>
        <?php foreach ($students as $student): ?>
        <tr>
            <td><?= htmlspecialchars($student['name']) ?></td>
            <td><?= htmlspecialchars($student['id']) ?></td>
            <td><?= $student['subject1'] ?></td>
            <td><?= $student['subject2'] ?></td>
            <td><?= $student['subject3'] ?></td>
            <td><?= $student['total'] ?></td>
            <td><?= $student['average'] ?></td>
            <td><?= $student['grade'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>