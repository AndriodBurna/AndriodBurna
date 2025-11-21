<?php
include "config.php";
include "includes/auth.php";

// Only students or parents can access
if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'parent') {
    die("Access denied!");
}

if ($_SESSION['role'] === 'student') {
    $student_id = $_SESSION['user_id'];
} else {
    // Parent case: expect ?student_id param, but verify link
    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    // TODO: verify parent-child link
}

// Fetch student name
$stmt = $conn->prepare("SELECT full_name, username FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
    die("Student not found");
}

// Fetch transcript results
$sql = "
    SELECT r.id, s.subject_name, r.marks, r.max_marks, r.grade,
           r.term, r.academic_year, r.exam_type, t.username AS teacher
    FROM results r
    JOIN subjects s ON r.subject_id = s.id
    LEFT JOIN users t ON r.teacher_id = t.id
    WHERE r.student_id = ?
    ORDER BY r.academic_year ASC, r.term ASC, s.subject_name ASC
";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("i", $student_id);
$stmt2->execute();
$results = $stmt2->get_result();

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=transcript_' . $student_id . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Subject','Marks','Max Marks','Percentage','Grade','Term','Year','Exam Type','Teacher']);
    while ($row = $results->fetch_assoc()) {
        $perc = ($row['max_marks'] > 0) ? round(($row['marks'] / $row['max_marks'])*100,2) : 0;
        fputcsv($out, [
            $row['subject_name'],
            $row['marks'],
            $row['max_marks'],
            $perc . '%',
            $row['grade'],
            $row['term'],
            $row['academic_year'],
            $row['exam_type'],
            $row['teacher']
        ]);
    }
    fclose($out);
    exit;
}

// Handle PDF export (using FPDF for example)
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require_once "fpdf/fpdf.php";  // adjust path to your FPDF library
    
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10, "Transcript for " . $student['full_name'], 0,1,'C');
    $pdf->Ln(5);
    
    // Table header
    $pdf->SetFont('Arial','B',10);
    $header = ['Subject','Marks','Max','%','Grade','Term','Year','Exam','Teacher'];
    foreach ($header as $col) {
        $pdf->Cell(22,7, $col,1);
    }
    $pdf->Ln();
    
    // Data rows
    $pdf->SetFont('Arial','',10);
    // rewind results
    $results->data_seek(0);
    while ($row = $results->fetch_assoc()) {
        $perc = ($row['max_marks'] > 0) ? round(($row['marks'] / $row['max_marks'])*100,2) : 0;
        $pdf->Cell(22,6, $row['subject_name'],1);
        $pdf->Cell(22,6, $row['marks'],1);
        $pdf->Cell(22,6, $row['max_marks'],1);
        $pdf->Cell(22,6, $perc . '%',1);
        $pdf->Cell(22,6, $row['grade'],1);
        $pdf->Cell(22,6, $row['term'],1);
        $pdf->Cell(22,6, $row['academic_year'],1);
        $pdf->Cell(22,6, ucfirst($row['exam_type']),1);
        $pdf->Cell(22,6, $row['teacher'],1);
        $pdf->Ln();
    }
    
    $pdf->Output('D', "transcript_{$student_id}.pdf");
    exit;
}

include "includes/header.php";
?>

<div class="container">
    <h2>📜 Transcript: <?= htmlspecialchars($student['full_name']) ?></h2>
    
    <div>
        <a href="transcript.php?export=csv&student_id=<?= $student_id ?>">📥 Download CSV</a> |
        <a href="transcript.php?export=pdf&student_id=<?= $student_id ?>">📄 Download PDF</a>
    </div>
    
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse; margin-top: 20px;">
        <thead style="background: #1abc9c; color: white;">
            <tr>
                <th>Subject</th>
                <th>Marks</th>
                <th>Max Marks</th>
                <th>Percentage</th>
                <th>Grade</th>
                <th>Term</th>
                <th>Academic Year</th>
                <th>Exam Type</th>
                <th>Teacher</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($results && $results->num_rows > 0): ?>
                <?php while ($r = $results->fetch_assoc()): ?>
                    <?php
                    $perc = ($r['max_marks'] > 0) ? round(($r['marks'] / $r['max_marks'])*100,2) : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($r['subject_name']) ?></td>
                        <td><?= $r['marks'] ?></td>
                        <td><?= $r['max_marks'] ?></td>
                        <td><?= $perc ?>%</td>
                        <td><?= htmlspecialchars($r['grade']) ?></td>
                        <td><?= htmlspecialchars($r['term']) ?></td>
                        <td><?= htmlspecialchars($r['academic_year']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($r['exam_type'])) ?></td>
                        <td><?= htmlspecialchars($r['teacher'] ?? "N/A") ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9">No results available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include "includes/footer.php"; ?>
