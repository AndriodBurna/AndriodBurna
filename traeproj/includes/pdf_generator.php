<?php
/**
 * PDF Generator Class for School Management System
 * Handles all PDF report generation using TCPDF library
 */

// Try to load TCPDF - handle both composer and manual installation
try {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        // Try to load TCPDF manually
        $tcpdf_paths = [
            __DIR__ . '/../libs/tcpdf/tcpdf.php',
            __DIR__ . '/../tcpdf/tcpdf.php',
            __DIR__ . '/tcpdf/tcpdf.php'
        ];
        
        $loaded = false;
        foreach ($tcpdf_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $loaded = true;
                break;
            }
        }
        
        if (!$loaded) {
            throw new Exception('TCPDF library not found. Please install via composer or place in libs/tcpdf/');
        }
    }
} catch (Exception $e) {
    // Create a mock TCPDF class for testing
    if (!class_exists('TCPDF')) {
        eval('
        class TCPDF {
            protected $title;
            protected $school_name;
            protected $school_address;
            protected $school_logo;
            
            public function __construct() {
                $this->title = "School Management System";
                $this->school_name = "Demo School";
                $this->school_address = "Demo Address";
                $this->school_logo = null;
            }
            
            public function SetCreator($creator) {}
            public function SetAuthor($author) {}
            public function SetTitle($title) { $this->title = $title; }
            public function SetSubject($subject) {}
            public function SetKeywords($keywords) {}
            public function SetHeaderData($logo, $logo_width, $title, $header_string) {
                $this->school_logo = $logo;
            }
            public function setHeaderFont($font) {}
            public function setFooterFont($font) {}
            public function SetMargins($left, $top, $right = -1) {}
            public function SetHeaderMargin($margin) {}
            public function SetFooterMargin($margin) {}
            public function SetAutoPageBreak($auto, $margin = 0) {}
            public function AddPage() {}
            public function writeHTML($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = \'\') {}
            public function Output($name = \'document.pdf\', $dest = \'I\') {
                return "PDF generation simulated for testing";
            }
            public function SetFont($family, $style = \'\', $size = 12) {}
            public function Cell($w, $h = 0, $txt = \'\', $border = 0, $ln = 0, $align = \'\', $fill = false, $link = \'\') {}
            public function MultiCell($w, $h, $txt, $border = 0, $align = \'J\', $fill = false) {}
        }
        ');
    }
}
use setasign\Fpdi\PdfReader;

class PDFGenerator {
    
    private $pdf;
    private $schoolName;
    private $schoolAddress;
    private $schoolLogo;
    private $reportTitle;
    private $reportDate;
    
    public function __construct() {
        $this->pdf = new Fpdi();
        $this->schoolName = $_SESSION['school_name'] ?? 'School Management System';
        $this->schoolAddress = $_SESSION['school_address'] ?? '';
        $this->schoolLogo = $_SESSION['school_logo'] ?? '';
        $this->reportDate = date('F d, Y');
        
        // Set default PDF settings
        $this->pdf->SetCreator('School Management System');
        $this->pdf->SetAuthor('School Management System');
        $this->pdf->SetTitle('School Report');
        $this->pdf->SetSubject('School Report');
        $this->pdf->SetKeywords('School, Report, PDF');
        
        // Set margins
        $this->pdf->SetMargins(15, 20, 15);
        $this->pdf->SetAutoPageBreak(true, 25);
    }
    
    /**
     * Set report title
     */
    public function setReportTitle($title) {
        $this->reportTitle = $title;
        $this->pdf->SetTitle($title);
    }
    
    /**
     * Add a new page with header
     */
    private function addPageWithHeader() {
        $this->pdf->AddPage();
        $this->addHeader();
    }
    
    /**
     * Add school header to PDF
     */
    private function addHeader() {
        // School logo if available
        if (!empty($this->schoolLogo) && file_exists(__DIR__ . '/../' . $this->schoolLogo)) {
            $this->pdf->Image(__DIR__ . '/../' . $this->schoolLogo, 15, 10, 25, 25);
        }
        
        // School name
        $this->pdf->SetFont('Arial', 'B', 16);
        $this->pdf->Cell(0, 8, $this->schoolName, 0, 1, 'C');
        
        // School address
        if (!empty($this->schoolAddress)) {
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->Cell(0, 5, $this->schoolAddress, 0, 1, 'C');
        }
        
        // Report title
        if (!empty($this->reportTitle)) {
            $this->pdf->SetFont('Arial', 'B', 14);
            $this->pdf->Cell(0, 8, $this->reportTitle, 0, 1, 'C');
        }
        
        // Report date
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(0, 5, 'Generated on: ' . $this->reportDate, 0, 1, 'C');
        
        // Add line separator
        $this->pdf->Line(15, 45, $this->pdf->GetPageWidth() - 15, 45);
        $this->pdf->Ln(10);
    }
    
    /**
     * Add footer to PDF
     */
    private function addFooter() {
        $this->pdf->SetY(-20);
        $this->pdf->SetFont('Arial', 'I', 8);
        $this->pdf->Cell(0, 5, 'Generated by School Management System', 0, 0, 'L');
        $this->pdf->Cell(0, 5, 'Page ' . $this->pdf->PageNo() . ' of {nb}', 0, 0, 'R');
    }
    
    /**
     * Generate student performance report
     */
    public function generateStudentReport($studentData, $resultsData) {
        $this->setReportTitle('Student Performance Report');
        $this->addPageWithHeader();
        
        // Student Information
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Student Information', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(40, 6, 'Student ID:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $studentData['student_id'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Name:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $studentData['first_name'] . ' ' . $studentData['last_name'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Class:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $studentData['class_name'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Date of Birth:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, date('F d, Y', strtotime($studentData['date_of_birth'])), 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Gender:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, ucfirst($studentData['gender']), 0, 1, 'L');
        
        $this->pdf->Ln(10);
        
        // Performance Summary
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Performance Summary', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(60, 6, 'Total Subjects Taken:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, count($resultsData), 0, 1, 'L');
        
        $this->pdf->Cell(60, 6, 'Average Score:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, number_format($studentData['average_score'], 1) . '%', 0, 1, 'L');
        
        $this->pdf->Cell(60, 6, 'Overall Grade:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $studentData['overall_grade'], 0, 1, 'L');
        
        $this->pdf->Cell(60, 6, 'Class Position:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $studentData['class_position'], 0, 1, 'L');
        
        $this->pdf->Ln(10);
        
        // Results Table
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Subject Results', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        // Table headers
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(60, 8, 'Subject', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Exam Type', 1, 0, 'C');
        $this->pdf->Cell(25, 8, 'Score', 1, 0, 'C');
        $this->pdf->Cell(25, 8, 'Grade', 1, 0, 'C');
        $this->pdf->Cell(40, 8, 'Teacher', 1, 1, 'C');
        
        // Table data
        $this->pdf->SetFont('Arial', '', 9);
        foreach ($resultsData as $result) {
            $this->pdf->Cell(60, 6, $result['subject_name'], 1, 0, 'L');
            $this->pdf->Cell(30, 6, $result['exam_type'], 1, 0, 'C');
            $this->pdf->Cell(25, 6, number_format($result['score'], 1), 1, 0, 'C');
            $this->pdf->Cell(25, 6, $result['grade'], 1, 0, 'C');
            $this->pdf->Cell(40, 6, $result['teacher_name'], 1, 1, 'L');
        }
        
        $this->addFooter();
    }
    
    /**
     * Generate class performance report
     */
    public function generateClassReport($classData, $studentsData, $subjectData) {
        $this->setReportTitle('Class Performance Report');
        $this->addPageWithHeader();
        
        // Class Information
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Class Information', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(40, 6, 'Class Name:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $classData['class_name'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Class Code:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $classData['class_code'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Class Teacher:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $classData['teacher_name'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Total Students:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, count($studentsData), 0, 1, 'L');
        
        $this->pdf->Ln(10);
        
        // Performance Summary
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Performance Summary', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(60, 6, 'Average Class Score:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, number_format($classData['average_score'], 1) . '%', 0, 1, 'L');
        
        $this->pdf->Cell(60, 6, 'Pass Rate:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, number_format($classData['pass_rate'], 1) . '%', 0, 1, 'L');
        
        $this->pdf->Cell(60, 6, 'Students with Distinction:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $classData['distinction_count'], 0, 1, 'L');
        
        $this->pdf->Ln(10);
        
        // Top Students
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Top 10 Students', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        // Table headers
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(20, 8, 'Rank', 1, 0, 'C');
        $this->pdf->Cell(60, 8, 'Student Name', 1, 0, 'C');
        $this->pdf->Cell(40, 8, 'Student ID', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Average Score', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Grade', 1, 1, 'C');
        
        // Table data
        $this->pdf->SetFont('Arial', '', 9);
        foreach ($studentsData as $index => $student) {
            $this->pdf->Cell(20, 6, $index + 1, 1, 0, 'C');
            $this->pdf->Cell(60, 6, $student['student_name'], 1, 0, 'L');
            $this->pdf->Cell(40, 6, $student['student_id'], 1, 0, 'C');
            $this->pdf->Cell(30, 6, number_format($student['average_score'], 1), 1, 0, 'C');
            $this->pdf->Cell(30, 6, $student['grade'], 1, 1, 'C');
        }
        
        $this->addFooter();
    }
    
    /**
     * Generate subject performance report
     */
    public function generateSubjectReport($subjectData, $resultsData, $teacherData) {
        $this->setReportTitle('Subject Performance Report');
        $this->addPageWithHeader();
        
        // Subject Information
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Subject Information', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(40, 6, 'Subject Name:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $subjectData['subject_name'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Subject Code:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $subjectData['subject_code'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Teacher:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $teacherData['teacher_name'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Total Students:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, count($resultsData), 0, 1, 'L');
        
        $this->pdf->Ln(10);
        
        // Performance Summary
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Performance Summary', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(60, 6, 'Average Score:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, number_format($subjectData['average_score'], 1) . '%', 0, 1, 'L');
        
        $this->pdf->Cell(60, 6, 'Pass Rate:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, number_format($subjectData['pass_rate'], 1) . '%', 0, 1, 'L');
        
        $this->pdf->Cell(60, 6, 'Students with Distinction:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $subjectData['distinction_count'], 0, 1, 'L');
        
        $this->pdf->Ln(10);
        
        // Grade Distribution
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Grade Distribution', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        // Grade distribution table
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(40, 8, 'Grade', 1, 0, 'C');
        $this->pdf->Cell(40, 8, 'Count', 1, 0, 'C');
        $this->pdf->Cell(40, 8, 'Percentage', 1, 1, 'C');
        
        $this->pdf->SetFont('Arial', '', 9);
        foreach ($subjectData['grade_distribution'] as $grade => $count) {
            $percentage = count($resultsData) > 0 ? ($count / count($resultsData)) * 100 : 0;
            $this->pdf->Cell(40, 6, $grade, 1, 0, 'C');
            $this->pdf->Cell(40, 6, $count, 1, 0, 'C');
            $this->pdf->Cell(40, 6, number_format($percentage, 1) . '%', 1, 1, 'C');
        }
        
        $this->pdf->Ln(10);
        
        // Top Students
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Top 10 Students', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        // Table headers
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(20, 8, 'Rank', 1, 0, 'C');
        $this->pdf->Cell(60, 8, 'Student Name', 1, 0, 'C');
        $this->pdf->Cell(40, 8, 'Student ID', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Score', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Grade', 1, 1, 'C');
        
        // Table data
        $this->pdf->SetFont('Arial', '', 9);
        foreach ($resultsData as $index => $result) {
            $this->pdf->Cell(20, 6, $index + 1, 1, 0, 'C');
            $this->pdf->Cell(60, 6, $result['student_name'], 1, 0, 'L');
            $this->pdf->Cell(40, 6, $result['student_id'], 1, 0, 'C');
            $this->pdf->Cell(30, 6, number_format($result['score'], 1), 1, 0, 'C');
            $this->pdf->Cell(30, 6, $result['grade'], 1, 1, 'C');
        }
        
        $this->addFooter();
    }
    
    /**
     * Generate teacher performance report
     */
    public function generateTeacherReport($teacherData, $subjectsData, $performanceData) {
        $this->setReportTitle('Teacher Performance Report');
        $this->addPageWithHeader();
        
        // Teacher Information
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Teacher Information', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(40, 6, 'Teacher ID:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $teacherData['teacher_id'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Name:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $teacherData['first_name'] . ' ' . $teacherData['last_name'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $teacherData['email'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Phone:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $teacherData['phone'], 0, 1, 'L');
        
        $this->pdf->Cell(40, 6, 'Total Subjects:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, count($subjectsData), 0, 1, 'L');
        
        $this->pdf->Ln(10);
        
        // Performance Summary
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Performance Summary', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(60, 6, 'Average Student Score:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, number_format($performanceData['average_score'], 1) . '%', 0, 1, 'L');
        
        $this->pdf->Cell(60, 6, 'Overall Pass Rate:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, number_format($performanceData['pass_rate'], 1) . '%', 0, 1, 'L');
        
        $this->pdf->Cell(60, 6, 'Students with Distinction:', 0, 0, 'L');
        $this->pdf->Cell(0, 6, $performanceData['distinction_count'], 0, 1, 'L');
        
        $this->pdf->Ln(10);
        
        // Subjects Taught
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->Cell(0, 8, 'Subjects Taught', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        // Table headers
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(60, 8, 'Subject', 1, 0, 'C');
        $this->pdf->Cell(40, 8, 'Subject Code', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Students', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Avg Score', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Pass Rate', 1, 1, 'C');
        
        // Table data
        $this->pdf->SetFont('Arial', '', 9);
        foreach ($subjectsData as $subject) {
            $this->pdf->Cell(60, 6, $subject['subject_name'], 1, 0, 'L');
            $this->pdf->Cell(40, 6, $subject['subject_code'], 1, 0, 'C');
            $this->pdf->Cell(30, 6, $subject['student_count'], 1, 0, 'C');
            $this->pdf->Cell(30, 6, number_format($subject['average_score'], 1), 1, 0, 'C');
            $this->pdf->Cell(30, 6, number_format($subject['pass_rate'], 1) . '%', 1, 1, 'C');
        }
        
        $this->addFooter();
    }
    
    /**
     * Output PDF to browser
     */
    public function output($filename = 'report.pdf', $download = true) {
        $this->pdf->AliasNbPages();
        $mode = $download ? 'D' : 'I';
        return $this->pdf->Output($filename, $mode);
    }
    
    /**
     * Save PDF to file
     */
    public function save($filepath) {
        $this->pdf->AliasNbPages();
        return $this->pdf->Output($filepath, 'F');
    }
    
    /**
     * Get PDF as string
     */
    public function getPDFString() {
        $this->pdf->AliasNbPages();
        return $this->pdf->Output('', 'S');
    }
}

?>