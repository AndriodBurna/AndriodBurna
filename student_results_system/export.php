<?php
include 'includes/auth.php';
require_once "config.php";

if (isset($_GET['type'])) {
    $type = $_GET['type'];

    if ($type == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=data.csv');
        $output = fopen('php://output', 'w');

        if (isset($_GET['report'])) {
            $report = $_GET['report'];

            if ($report == 'class' && isset($_GET['class_id']) && isset($_GET['term_id'])) {
                // Export class report
                $class_id = $_GET['class_id'];
                $term_id = $_GET['term_id'];

                $sql = "SELECT s.name as student_name, sub.name as subject_name, r.marks, r.grade 
                        FROM results r
                        JOIN students s ON r.student_id = s.id
                        JOIN subjects sub ON r.subject_id = sub.id
                        WHERE r.class_id = ? AND r.term_id = ?";

                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ii", $class_id, $term_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    // Add headers
                    fputcsv($output, array('Student', 'Subject', 'Marks', 'Grade'));

                    while ($row = $result->fetch_assoc()) {
                        fputcsv($output, $row);
                    }
                }
            } elseif ($report == 'student' && isset($_GET['student_id']) && isset($_GET['term_id'])) {
                // Export student report
                $student_id = $_GET['student_id'];
                $term_id = $_GET['term_id'];

                $sql = "SELECT sub.name as subject_name, r.marks, r.grade 
                        FROM results r
                        JOIN subjects sub ON r.subject_id = sub.id
                        WHERE r.student_id = ? AND r.term_id = ?";

                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ii", $student_id, $term_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    // Add headers
                    fputcsv($output, array('Subject', 'Marks', 'Grade'));

                    while ($row = $result->fetch_assoc()) {
                        fputcsv($output, $row);
                    }
                }
            }
        }
        fclose($output);
        exit;
    }
}
?>