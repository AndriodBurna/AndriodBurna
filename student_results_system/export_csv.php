<?php
include "config.php";
include "includes/auth.php";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=results.csv');
$output = fopen('php://output', 'w');

fputcsv($output, ['ID', 'Student Name', 'Class', 'Subject', 'Marks', 'Grade']);

$sql = "SELECT * FROM results";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    fputcsv($output, $row);
}
fclose($output);
exit;
?>
