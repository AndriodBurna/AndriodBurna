<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/pdf_generator.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

// Get current user role
$currentUserRole = getCurrentUserRole();

// Check permissions
if (!in_array($currentUserRole, ['admin', 'principal', 'hod', 'teacher'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Get report type and parameters
$reportType = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'pdf';

// Validate report type
$validReportTypes = ['student', 'class', 'subject', 'teacher', 'grade_distribution', 'performance_summary'];
if (!in_array($reportType, $validReportTypes)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid report type');
}

try {
    $pdf = new PDFGenerator();
    
    switch ($reportType) {
        case 'student':
            generateStudentReport($pdf, $_GET);
            break;
        case 'class':
            generateClassReport($pdf, $_GET);
            break;
        case 'subject':
            generateSubjectReport($pdf, $_GET);
            break;
        case 'teacher':
            generateTeacherReport($pdf, $_GET);
            break;
        case 'grade_distribution':
            generateGradeDistributionReport($pdf, $_GET);
            break;
        case 'performance_summary':
            generatePerformanceSummaryReport($pdf, $_GET);
            break;
    }
    
    // Generate filename
    $filename = generateReportFilename($reportType, $_GET);
    
    // Output PDF
    if ($format === 'download') {
        $pdf->output($filename, true);
    } else {
        $pdf->output($filename, false);
    }
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error generating report: ' . $e->getMessage());
}

/**
 * Generate student performance report
 */
function generateStudentReport($pdf, $params) {
    $studentId = isset($params['student_id']) ? (int)$params['student_id'] : 0;
    
    if ($studentId === 0) {
        throw new Exception('Student ID is required');
    }
    
    // Get student data
    $studentQuery = "
        SELECT s.*, c.name as class_name, c.class_code,
               AVG(r.score) as average_score,
               COUNT(DISTINCT r.subject_id) as total_subjects
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN results r ON s.id = r.student_id
        WHERE s.id = ?
        GROUP BY s.id
    ";
    
    $studentData = databaseQuery($studentQuery, [$studentId], 'i');
    
    if (empty($studentData)) {
        throw new Exception('Student not found');
    }
    
    $studentData = $studentData[0];
    
    // Get results data
    $resultsQuery = "
        SELECT r.*, sub.name as subject_name, sub.subject_code,
               et.name as exam_type, et.weight,
               t.first_name as teacher_first_name, t.last_name as teacher_last_name,
               CONCAT(t.first_name, ' ', t.last_name) as teacher_name
        FROM results r
        JOIN subjects sub ON r.subject_id = sub.id
        JOIN exam_types et ON r.exam_type_id = et.id
        JOIN teachers t ON r.teacher_id = t.id
        WHERE r.student_id = ?
        ORDER BY r.academic_year DESC, r.term DESC, sub.name, et.name
    ";
    
    $resultsData = databaseQuery($resultsQuery, [$studentId], 'i');
    
    // Calculate overall grade and position
    $studentData['overall_grade'] = calculateGrade($studentData['average_score']);
    $studentData['class_position'] = calculateClassPosition($studentId, $studentData['class_id']);
    
    // Generate PDF
    $pdf->generateStudentReport($studentData, $resultsData);
}

/**
 * Generate class performance report
 */
function generateClassReport($pdf, $params) {
    $classId = isset($params['class_id']) ? (int)$params['class_id'] : 0;
    $academicYear = isset($params['academic_year']) ? $params['academic_year'] : date('Y');
    $term = isset($params['term']) ? $params['term'] : '';
    
    if ($classId === 0) {
        throw new Exception('Class ID is required');
    }
    
    // Get class data
    $classQuery = "
        SELECT c.*, t.first_name as teacher_first_name, t.last_name as teacher_last_name,
               CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
               AVG(r.score) as average_score,
               COUNT(CASE WHEN r.score >= 50 THEN 1 END) * 100.0 / COUNT(r.id) as pass_rate,
               COUNT(CASE WHEN r.score >= 80 THEN 1 END) as distinction_count
        FROM classes c
        LEFT JOIN teachers t ON c.teacher_id = t.id
        LEFT JOIN students s ON c.id = s.class_id
        LEFT JOIN results r ON s.id = r.student_id
        WHERE c.id = ? AND r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        GROUP BY c.id
    ";
    
    $classParams = [$classId, $academicYear];
    if (!empty($term)) {
        $classParams[] = $term;
    }
    
    $classData = databaseQuery($classQuery, $classParams, str_repeat('s', count($classParams)));
    
    if (empty($classData)) {
        throw new Exception('Class not found');
    }
    
    $classData = $classData[0];
    
    // Get students data with rankings
    $studentsQuery = "
        SELECT s.id, s.student_id, s.first_name, s.last_name,
               CONCAT(s.first_name, ' ', s.last_name) as student_name,
               AVG(r.score) as average_score,
               calculateGrade(AVG(r.score)) as grade
        FROM students s
        LEFT JOIN results r ON s.id = r.student_id
        WHERE s.class_id = ? AND r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        GROUP BY s.id
        ORDER BY average_score DESC
    ";
    
    $studentsData = databaseQuery($studentsQuery, $classParams, str_repeat('s', count($classParams)));
    
    // Get subject data
    $subjectQuery = "
        SELECT sub.id, sub.name as subject_name, sub.subject_code,
               COUNT(DISTINCT r.student_id) as student_count,
               AVG(r.score) as average_score,
               COUNT(CASE WHEN r.score >= 50 THEN 1 END) * 100.0 / COUNT(r.id) as pass_rate
        FROM subjects sub
        LEFT JOIN results r ON sub.id = r.subject_id
        LEFT JOIN students s ON r.student_id = s.id
        WHERE s.class_id = ? AND r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        GROUP BY sub.id
        ORDER BY sub.name
    ";
    
    $subjectData = databaseQuery($subjectQuery, $classParams, str_repeat('s', count($classParams)));
    
    // Generate PDF
    $pdf->generateClassReport($classData, $studentsData, $subjectData);
}

/**
 * Generate subject performance report
 */
function generateSubjectReport($pdf, $params) {
    $subjectId = isset($params['subject_id']) ? (int)$params['subject_id'] : 0;
    $academicYear = isset($params['academic_year']) ? $params['academic_year'] : date('Y');
    $term = isset($params['term']) ? $params['term'] : '';
    $classId = isset($params['class_id']) ? (int)$params['class_id'] : 0;
    
    if ($subjectId === 0) {
        throw new Exception('Subject ID is required');
    }
    
    // Get subject data
    $subjectQuery = "
        SELECT sub.*, t.first_name as teacher_first_name, t.last_name as teacher_last_name,
               CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
               AVG(r.score) as average_score,
               COUNT(CASE WHEN r.score >= 50 THEN 1 END) * 100.0 / COUNT(r.id) as pass_rate,
               COUNT(CASE WHEN r.score >= 80 THEN 1 END) as distinction_count
        FROM subjects sub
        LEFT JOIN teachers t ON sub.teacher_id = t.id
        LEFT JOIN results r ON sub.id = r.subject_id
        WHERE sub.id = ? AND r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        " . ($classId > 0 ? " AND r.student_id IN (SELECT id FROM students WHERE class_id = ?)" : "") . "
        GROUP BY sub.id
    ";
    
    $subjectParams = [$subjectId, $academicYear];
    if (!empty($term)) {
        $subjectParams[] = $term;
    }
    if ($classId > 0) {
        $subjectParams[] = $classId;
    }
    
    $subjectData = databaseQuery($subjectQuery, $subjectParams, str_repeat('s', count($subjectParams)));
    
    if (empty($subjectData)) {
        throw new Exception('Subject not found');
    }
    
    $subjectData = $subjectData[0];
    
    // Get results data
    $resultsQuery = "
        SELECT r.*, s.student_id, s.first_name, s.last_name,
               CONCAT(s.first_name, ' ', s.last_name) as student_name,
               c.name as class_name, c.class_code,
               et.name as exam_type
        FROM results r
        JOIN students s ON r.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        JOIN exam_types et ON r.exam_type_id = et.id
        WHERE r.subject_id = ? AND r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        " . ($classId > 0 ? " AND s.class_id = ?" : "") . "
        ORDER BY r.score DESC
        LIMIT 50
    ";
    
    $resultsData = databaseQuery($resultsQuery, $subjectParams, str_repeat('s', count($subjectParams)));
    
    // Get grade distribution
    $gradeDistribution = [];
    foreach ($resultsData as $result) {
        $grade = calculateGrade($result['score']);
        $gradeDistribution[$grade] = ($gradeDistribution[$grade] ?? 0) + 1;
    }
    
    $subjectData['grade_distribution'] = $gradeDistribution;
    
    // Get teacher data
    $teacherData = [
        'teacher_name' => $subjectData['teacher_name'],
        'teacher_id' => $subjectData['teacher_id']
    ];
    
    // Generate PDF
    $pdf->generateSubjectReport($subjectData, $resultsData, $teacherData);
}

/**
 * Generate teacher performance report
 */
function generateTeacherReport($pdf, $params) {
    $teacherId = isset($params['teacher_id']) ? (int)$params['teacher_id'] : 0;
    $academicYear = isset($params['academic_year']) ? $params['academic_year'] : date('Y');
    $term = isset($params['term']) ? $params['term'] : '';
    
    if ($teacherId === 0) {
        throw new Exception('Teacher ID is required');
    }
    
    // Get teacher data
    $teacherQuery = "
        SELECT t.*, 
               AVG(r.score) as average_score,
               COUNT(CASE WHEN r.score >= 50 THEN 1 END) * 100.0 / COUNT(r.id) as pass_rate,
               COUNT(CASE WHEN r.score >= 80 THEN 1 END) as distinction_count
        FROM teachers t
        LEFT JOIN subjects sub ON t.id = sub.teacher_id
        LEFT JOIN results r ON sub.id = r.subject_id
        WHERE t.id = ? AND r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        GROUP BY t.id
    ";
    
    $teacherParams = [$teacherId, $academicYear];
    if (!empty($term)) {
        $teacherParams[] = $term;
    }
    
    $teacherData = databaseQuery($teacherQuery, $teacherParams, str_repeat('s', count($teacherParams)));
    
    if (empty($teacherData)) {
        throw new Exception('Teacher not found');
    }
    
    $teacherData = $teacherData[0];
    
    // Get subjects data
    $subjectsQuery = "
        SELECT sub.id, sub.name as subject_name, sub.subject_code,
               COUNT(DISTINCT r.student_id) as student_count,
               AVG(r.score) as average_score,
               COUNT(CASE WHEN r.score >= 50 THEN 1 END) * 100.0 / COUNT(r.id) as pass_rate
        FROM subjects sub
        LEFT JOIN results r ON sub.id = r.subject_id
        WHERE sub.teacher_id = ? AND r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        GROUP BY sub.id
        ORDER BY sub.name
    ";
    
    $subjectsData = databaseQuery($subjectsQuery, $teacherParams, str_repeat('s', count($teacherParams)));
    
    // Get performance data
    $performanceData = [
        'average_score' => $teacherData['average_score'],
        'pass_rate' => $teacherData['pass_rate'],
        'distinction_count' => $teacherData['distinction_count']
    ];
    
    // Generate PDF
    $pdf->generateTeacherReport($teacherData, $subjectsData, $performanceData);
}

/**
 * Generate grade distribution report
 */
function generateGradeDistributionReport($pdf, $params) {
    $academicYear = isset($params['academic_year']) ? $params['academic_year'] : date('Y');
    $term = isset($params['term']) ? $params['term'] : '';
    $classId = isset($params['class_id']) ? (int)$params['class_id'] : 0;
    $subjectId = isset($params['subject_id']) ? (int)$params['subject_id'] : 0;
    
    // Get grade distribution data
    $query = "
        SELECT 
            calculateGrade(r.score) as grade,
            COUNT(*) as count
        FROM results r
        JOIN students s ON r.student_id = s.id
        WHERE r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        " . ($classId > 0 ? " AND s.class_id = ?" : "") . "
        " . ($subjectId > 0 ? " AND r.subject_id = ?" : "") . "
        GROUP BY calculateGrade(r.score)
        ORDER BY 
            CASE calculateGrade(r.score)
                WHEN 'A' THEN 1
                WHEN 'B' THEN 2
                WHEN 'C' THEN 3
                WHEN 'D' THEN 4
                WHEN 'E' THEN 5
                WHEN 'F' THEN 6
            END
    ";
    
    $params = [$academicYear];
    if (!empty($term)) {
        $params[] = $term;
    }
    if ($classId > 0) {
        $params[] = $classId;
    }
    if ($subjectId > 0) {
        $params[] = $subjectId;
    }
    
    $gradeDistribution = databaseQuery($query, $params, str_repeat('s', count($params)));
    
    // Generate PDF (simplified for now)
    $pdf->setReportTitle('Grade Distribution Report');
    $pdf->addPageWithHeader();
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Grade Distribution', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 8, 'Grade', 1, 0, 'C');
    $pdf->Cell(40, 8, 'Count', 1, 0, 'C');
    $pdf->Cell(40, 8, 'Percentage', 1, 1, 'C');
    
    $pdf->SetFont('Arial', '', 10);
    $total = array_sum(array_column($gradeDistribution, 'count'));
    
    foreach ($gradeDistribution as $grade) {
        $percentage = $total > 0 ? ($grade['count'] / $total) * 100 : 0;
        $pdf->Cell(40, 6, $grade['grade'], 1, 0, 'C');
        $pdf->Cell(40, 6, $grade['count'], 1, 0, 'C');
        $pdf->Cell(40, 6, number_format($percentage, 1) . '%', 1, 1, 'C');
    }
    
    $pdf->addFooter();
}

/**
 * Generate performance summary report
 */
function generatePerformanceSummaryReport($pdf, $params) {
    $academicYear = isset($params['academic_year']) ? $params['academic_year'] : date('Y');
    $term = isset($params['term']) ? $params['term'] : '';
    
    // Get summary data
    $summaryQuery = "
        SELECT 
            'Overall' as category,
            COUNT(DISTINCT s.id) as total_students,
            COUNT(DISTINCT r.id) as total_results,
            AVG(r.score) as average_score,
            COUNT(CASE WHEN r.score >= 50 THEN 1 END) * 100.0 / COUNT(r.id) as pass_rate,
            COUNT(CASE WHEN r.score >= 80 THEN 1 END) as distinction_count
        FROM results r
        JOIN students s ON r.student_id = s.id
        WHERE r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        
        UNION ALL
        
        SELECT 
            'By Class' as category,
            COUNT(DISTINCT s.id) as total_students,
            COUNT(DISTINCT r.id) as total_results,
            AVG(r.score) as average_score,
            COUNT(CASE WHEN r.score >= 50 THEN 1 END) * 100.0 / COUNT(r.id) as pass_rate,
            COUNT(CASE WHEN r.score >= 80 THEN 1 END) as distinction_count
        FROM results r
        JOIN students s ON r.student_id = s.id
        JOIN classes c ON s.class_id = c.id
        WHERE r.academic_year = ?
        " . (!empty($term) ? " AND r.term = ?" : "") . "
        GROUP BY c.id
        ORDER BY average_score DESC
        LIMIT 10
    ";
    
    $params = [$academicYear];
    if (!empty($term)) {
        $params[] = $term;
        $params[] = $academicYear;
        $params[] = $term;
    }
    
    $summaryData = databaseQuery($summaryQuery, $params, str_repeat('s', count($params)));
    
    // Generate PDF (simplified for now)
    $pdf->setReportTitle('Performance Summary Report');
    $pdf->addPageWithHeader();
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Performance Summary', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Add summary data to PDF
    foreach ($summaryData as $summary) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $summary['category'], 0, 1, 'L');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 5, 'Total Students:', 0, 0, 'L');
        $pdf->Cell(0, 5, $summary['total_students'], 0, 1, 'L');
        
        $pdf->Cell(40, 5, 'Total Results:', 0, 0, 'L');
        $pdf->Cell(0, 5, $summary['total_results'], 0, 1, 'L');
        
        $pdf->Cell(40, 5, 'Average Score:', 0, 0, 'L');
        $pdf->Cell(0, 5, number_format($summary['average_score'], 1) . '%', 0, 1, 'L');
        
        $pdf->Cell(40, 5, 'Pass Rate:', 0, 0, 'L');
        $pdf->Cell(0, 5, number_format($summary['pass_rate'], 1) . '%', 0, 1, 'L');
        
        $pdf->Ln(3);
    }
    
    $pdf->addFooter();
}

/**
 * Generate report filename
 */
function generateReportFilename($reportType, $params) {
    $timestamp = date('Y-m-d_H-i-s');
    $academicYear = isset($params['academic_year']) ? $params['academic_year'] : date('Y');
    $term = isset($params['term']) ? $params['term'] : '';
    
    $filename = $reportType . '_report_' . $academicYear;
    
    if (!empty($term)) {
        $filename .= '_term_' . $term;
    }
    
    if (isset($params['student_id'])) {
        $filename .= '_student_' . $params['student_id'];
    } elseif (isset($params['class_id'])) {
        $filename .= '_class_' . $params['class_id'];
    } elseif (isset($params['subject_id'])) {
        $filename .= '_subject_' . $params['subject_id'];
    } elseif (isset($params['teacher_id'])) {
        $filename .= '_teacher_' . $params['teacher_id'];
    }
    
    $filename .= '_' . $timestamp . '.pdf';
    
    return $filename;
}

/**
 * Calculate class position for a student
 */
function calculateClassPosition($studentId, $classId) {
    $query = "
        SELECT s.id, AVG(r.score) as average_score
        FROM students s
        LEFT JOIN results r ON s.id = r.student_id
        WHERE s.class_id = ?
        GROUP BY s.id
        ORDER BY average_score DESC
    ";
    
    $students = databaseQuery($query, [$classId], 'i');
    
    foreach ($students as $index => $student) {
        if ($student['id'] == $studentId) {
            return $index + 1;
        }
    }
    
    return 'N/A';
}

?>