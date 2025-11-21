<?php
// Gradebook tab content
$gradebook_data = $tab_data['gradebook_data'] ?? [];
$performance_summary = $tab_data['performance_summary'] ?? [];

// Get gradebook data for this teacher
$gradebook_query = "SELECT 
    r.result_id,
    r.student_id,
    s.name as student_name,
    s.student_uid as admission_number,
    c.class_name,
    c.stream,
    sub.subject_name,
    r.term,
    r.year,
    r.marks,
    r.grade,
    r.remarks
FROM results r
JOIN students s ON r.student_id = s.student_id
JOIN classes c ON s.class_id = c.class_id
JOIN subjects sub ON r.subject_id = sub.subject_id
WHERE r.teacher_id = $teacher_id
ORDER BY c.class_name, sub.subject_name, s.name, r.year DESC, r.term DESC";

$gradebook_result = mysqli_query($link, $gradebook_query);
$gradebook_data = [];
while ($row = mysqli_fetch_assoc($gradebook_result)) {
    $gradebook_data[] = $row;
}

// Get performance summary
$performance_query = "SELECT 
    c.class_name,
    sub.subject_name,
    r.term,
    r.year,
    COUNT(r.result_id) as total_students,
    AVG(r.marks) as average_marks,
    MAX(r.marks) as highest_marks,
    MIN(r.marks) as lowest_marks,
    COUNT(CASE WHEN r.grade = 'A' THEN 1 END) as grade_a,
    COUNT(CASE WHEN r.grade = 'B' THEN 1 END) as grade_b,
    COUNT(CASE WHEN r.grade = 'C' THEN 1 END) as grade_c,
    COUNT(CASE WHEN r.grade = 'D' THEN 1 END) as grade_d,
    COUNT(CASE WHEN r.grade = 'E' THEN 1 END) as grade_e,
    COUNT(CASE WHEN r.grade = 'F' THEN 1 END) as grade_f
FROM results r
JOIN students s ON r.student_id = s.student_id
JOIN classes c ON s.class_id = c.class_id
JOIN subjects sub ON r.subject_id = sub.subject_id
WHERE r.teacher_id = $teacher_id
GROUP BY c.class_name, sub.subject_name, r.term, r.year
ORDER BY c.class_name, sub.subject_name, r.year DESC, r.term DESC";

$performance_result = mysqli_query($link, $performance_query);
$performance_summary = [];
while ($row = mysqli_fetch_assoc($performance_result)) {
    $performance_summary[] = $row;
}

// Handle gradebook actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['export_gradebook'])) {
        // Export to CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="gradebook_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Student Name', 'Admission No.', 'Class', 'Subject', 'Term', 'Year', 'Marks', 'Grade', 'Remarks']);
        
        foreach ($gradebook_data as $row) {
            fputcsv($output, [
                $row['student_name'],
                $row['admission_number'],
                $row['class_name'] . ' ' . $row['section'],
                $row['subject_name'],
                $row['term'],
                $row['year'],
                $row['marks'],
                $row['grade'],
                $row['remarks']
            ]);
        }
        fclose($output);
        exit;
    }
}

function getGradeColor($grade) {
    switch ($grade) {
        case 'A': return '#28a745';
        case 'B': return '#17a2b8';
        case 'C': return '#ffc107';
        case 'D': return '#fd7e14';
        case 'E': return '#dc3545';
        case 'F': return '#6f42c1';
        default: return '#6c757d';
    }
}

function getPerformanceColor($average) {
    if ($average >= 80) return 'success';
    if ($average >= 70) return 'info';
    if ($average >= 60) return 'warning';
    if ($average >= 50) return 'orange';
    return 'danger';
}
?>

<div class="gradebook-content">
    <div class="section-header">
        <h3>📖 My Gradebook</h3>
        <p>Comprehensive view of student performance across all classes and subjects</p>
    </div>

    <!-- Performance Summary Cards -->
    <div class="performance-summary">
        <div class="row">
            <?php foreach (array_slice($performance_summary, 0, 6) as $summary): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="performance-card">
                        <div class="card-header">
                            <h5><?php echo htmlspecialchars($summary['class_name']); ?></h5>
                            <h6><?php echo htmlspecialchars($summary['subject_name']); ?></h6>
                            <small><?php echo $summary['term']; ?> <?php echo $summary['year']; ?></small>
                        </div>
                        <div class="card-body">
                            <div class="performance-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Average</span>
                                    <span class="stat-value text-<?php echo getPerformanceColor($summary['average_marks']); ?>">
                                        <?php echo number_format($summary['average_marks'], 1); ?>%
                                    </span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Students</span>
                                    <span class="stat-value"><?php echo $summary['total_students']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Range</span>
                                    <span class="stat-value"><?php echo $summary['lowest_marks']; ?>-<?php echo $summary['highest_marks']; ?></span>
                                </div>
                            </div>
                            
                            <div class="grade-distribution">
                                <h6>Grade Distribution</h6>
                                <div class="grade-bars">
                                    <?php 
                                    $grades = ['A' => $summary['grade_a'], 'B' => $summary['grade_b'], 'C' => $summary['grade_c'], 
                                              'D' => $summary['grade_d'], 'E' => $summary['grade_e'], 'F' => $summary['grade_f']];
                                    foreach ($grades as $grade => $count): 
                                        $percentage = $summary['total_students'] > 0 ? ($count / $summary['total_students']) * 100 : 0;
                                    ?>
                                        <div class="grade-bar">
                                            <span class="grade-label" style="color: <?php echo getGradeColor($grade); ?>">
                                                <?php echo $grade; ?>
                                            </span>
                                            <div class="progress">
                                                <div class="progress-bar" 
                                                     style="width: <?php echo $percentage; ?>%; background-color: <?php echo getGradeColor($grade); ?>">
                                                    <?php echo $count; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Gradebook Table -->
    <div class="gradebook-table-section">
        <div class="table-header">
            <h4>Detailed Gradebook</h4>
            <div class="table-actions">
                <button type="button" class="btn btn-outline-primary" onclick="filterTable()">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="sortTable()">
                    <i class="fas fa-sort"></i> Sort
                </button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="export_gradebook" class="btn btn-outline-success">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped gradebook-table" id="gradebookTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Term</th>
                        <th>Year</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gradebook_data as $row): ?>
                        <tr data-student-id="<?php echo $row['student_id']; ?>"
                            data-class="<?php echo $row['class_name']; ?>"
                            data-subject="<?php echo $row['subject_name']; ?>"
                            data-term="<?php echo $row['term']; ?>"
                            data-year="<?php echo $row['year']; ?>">
                            <td>
                                <div class="student-info">
                                    <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($row['admission_number']); ?></small>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['class_name'] . ' ' . $row['stream']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo $row['term']; ?></td>
                            <td><?php echo $row['year']; ?></td>
                            <td>
                                <span class="marks-cell <?php echo getPerformanceColor($row['marks']); ?>">
                                    <?php echo $row['marks']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="grade-badge" style="background-color: <?php echo getGradeColor($row['grade']); ?>">
                                    <?php echo $row['grade']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                            <td>
                                <small><?php echo htmlspecialchars($row['term'] . ' ' . $row['year']); ?></small>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-warning" onclick="editResult(<?php echo $row['result_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="viewResult(<?php echo $row['result_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="viewTrend(<?php echo $row['student_id']; ?>)">
                                        <i class="fas fa-chart-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Performance Insights -->
    <div class="performance-insights">
        <h4>Performance Insights</h4>
        <div class="row">
            <div class="col-md-6">
                <div class="insight-card">
                    <h5>Top Performers</h5>
                    <div class="top-performers">
                        <?php 
                        // Get top performers from gradebook data
                        $top_performers = [];
                        foreach ($gradebook_data as $row) {
                            $key = $row['student_id'] . '_' . $row['subject_name'];
                            if (!isset($top_performers[$key]) || $row['marks'] > $top_performers[$key]['marks']) {
                                $top_performers[$key] = $row;
                            }
                        }
                        usort($top_performers, function($a, $b) {
                            return $b['marks'] - $a['marks'];
                        });
                        
                        foreach (array_slice($top_performers, 0, 5) as $performer): 
                        ?>
                            <div class="performer-item">
                                <div class="performer-info">
                                    <strong><?php echo htmlspecialchars($performer['student_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($performer['class_name'] . ' - ' . $performer['subject_name']); ?></small>
                                </div>
                                <div class="performer-score">
                                    <span class="score"><?php echo $performer['marks']; ?>%</span>
                                    <span class="grade" style="color: <?php echo getGradeColor($performer['grade']); ?>">
                                        <?php echo $performer['grade']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="insight-card">
                    <h5>Students Needing Attention</h5>
                    <div class="attention-students">
                        <?php 
                        // Get students with low performance
                        $low_performers = [];
                        foreach ($gradebook_data as $row) {
                            if ($row['marks'] < 50) {
                                $key = $row['student_id'] . '_' . $row['subject_name'];
                                if (!isset($low_performers[$key]) || $row['marks'] < $low_performers[$key]['marks']) {
                                    $low_performers[$key] = $row;
                                }
                            }
                        }
                        usort($low_performers, function($a, $b) {
                            return $a['marks'] - $b['marks'];
                        });
                        
                        foreach (array_slice($low_performers, 0, 5) as $student): 
                        ?>
                            <div class="attention-item">
                                <div class="student-info">
                                    <strong><?php echo htmlspecialchars($student['student_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['subject_name']); ?></small>
                                </div>
                                <div class="performance-indicator">
                                    <span class="score text-danger"><?php echo $student['marks']; ?>%</span>
                                    <span class="grade" style="color: <?php echo getGradeColor($student['grade']); ?>">
                                        <?php echo $student['grade']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($low_performers)): ?>
                            <div class="no-attention">
                                <div class="no-attention-icon">🎉</div>
                                <p>All students are performing well!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterTable() {
    // Implementation for table filtering
    const filter = prompt('Filter by (student/class/subject/term/year):');
    if (filter) {
        // Simple filter implementation
        const rows = document.querySelectorAll('#gradebookTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(filter.toLowerCase())) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
}

function sortTable() {
    // Implementation for table sorting
    const sortBy = prompt('Sort by (marks/grade/student):');
    if (sortBy) {
        alert('Sorting by ' + sortBy + ' will be implemented');
    }
}

function editResult(resultId) {
    // Implementation for editing result
    alert('Edit result ' + resultId + ' will be implemented');
}

function viewResult(resultId) {
    // Implementation for viewing result
    alert('View result ' + resultId + ' will be implemented');
}

function viewTrend(studentId) {
    // Implementation for viewing student trend
    alert('View trend for student ' + studentId + ' will be implemented');
}
</script>

<style>
.gradebook-content {
    padding: 0;
}

.section-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.section-header h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-weight: 600;
}

.section-header p {
    margin: 0;
    color: #666;
}

.performance-summary {
    margin-bottom: 2rem;
}

.performance-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    height: 100%;
    transition: transform 0.3s ease;
}

.performance-card:hover {
    transform: translateY(-2px);
}

.card-header {
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #e9ecef;
}

.card-header h5 {
    margin: 0 0 0.25rem 0;
    color: #333;
    font-weight: 600;
}

.card-header h6 {
    margin: 0 0 0.25rem 0;
    color: #666;
}

.card-header small {
    color: #999;
}

.card-body {
    padding: 1.5rem;
}

.performance-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.stat-item {
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.stat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 600;
}

.grade-distribution h6 {
    margin: 0 0 1rem 0;
    color: #333;
}

.grade-bar {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.grade-label {
    width: 20px;
    font-weight: 600;
    margin-right: 10px;
}

.grade-bar .progress {
    flex: 1;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.grade-bar .progress-bar {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
}

.gradebook-table-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #e9ecef;
}

.table-header h4 {
    margin: 0;
    color: #333;
    font-weight: 600;
}

.table-actions {
    display: flex;
    gap: 0.5rem;
}

.gradebook-table {
    margin: 0;
}

.gradebook-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    border: none;
    padding: 1rem 0.75rem;
}

.gradebook-table td {
    padding: 0.75rem;
    vertical-align: middle;
}

.student-info strong {
    display: block;
    color: #333;
    margin-bottom: 0.25rem;
}

.student-info small {
    color: #666;
    font-size: 0.875rem;
}

.marks-cell {
    font-weight: 600;
    font-size: 1.1rem;
}

.marks-cell.success { color: #28a745; }
.marks-cell.info { color: #17a2b8; }
.marks-cell.warning { color: #ffc107; }
.marks-cell.orange { color: #fd7e14; }
.marks-cell.danger { color: #dc3545; }

.grade-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.action-buttons .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.performance-insights {
    margin-bottom: 2rem;
}

.performance-insights h4 {
    margin: 0 0 1.5rem 0;
    color: #333;
    font-weight: 600;
}

.insight-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 1.5rem;
    height: 100%;
}

.insight-card h5 {
    margin: 0 0 1rem 0;
    color: #333;
    font-weight: 600;
}

.performer-item, .attention-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.performer-item:last-child, .attention-item:last-child {
    border-bottom: none;
}

.performer-info strong, .student-info strong {
    display: block;
    color: #333;
    margin-bottom: 0.25rem;
}

.performer-info small, .student-info small {
    color: #666;
    font-size: 0.875rem;
}

.performer-score, .performance-indicator {
    text-align: right;
}

.score {
    display: block;
    font-weight: 600;
    font-size: 1.1rem;
}

.grade {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
}

.no-attention {
    text-align: center;
    padding: 2rem 0;
    color: #666;
}

.no-attention-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .performance-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .stat-item {
        text-align: left;
        display: flex;
        justify-content: space-between;
    }
    
    .table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .table-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>