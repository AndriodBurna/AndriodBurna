<?php
// Results entry tab content
$results = $tab_data['results'] ?? [];
$pending_count = $tab_data['pending_count'] ?? 0;

// Get available classes and subjects for this teacher
$available_classes = [];
$available_subjects = [];

if ($teacher_id > 0) {
    // Get classes taught by this teacher
    $classes_query = "SELECT DISTINCT c.class_id, c.class_name, c.stream 
                     FROM classes c 
                     JOIN teachers t ON c.class_id = t.class_id
                     WHERE t.teacher_id = $teacher_id 
                     ORDER BY c.class_name";
    $classes_result = mysqli_query($link, $classes_query);
    while ($row = mysqli_fetch_assoc($classes_result)) {
        $available_classes[] = $row;
    }
    
    // Get subjects taught by this teacher
    $subjects_query = "SELECT DISTINCT s.subject_id, s.subject_name, c.class_name, c.class_id 
                      FROM subjects s 
                      JOIN classes c ON s.class_id = c.class_id 
                      JOIN teachers t ON s.subject_id = t.subject_id
                      WHERE t.teacher_id = $teacher_id 
                      ORDER BY s.subject_name";
    $subjects_result = mysqli_query($link, $subjects_query);
    while ($row = mysqli_fetch_assoc($subjects_result)) {
        $available_subjects[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_results'])) {
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $exam_type = mysqli_real_escape_string($link, $_POST['exam_type']);
    $term = mysqli_real_escape_string($link, $_POST['term']);
    $year = (int)$_POST['year'];
    $max_marks = (int)$_POST['max_marks'];
    
    // Process each student's results
    if (isset($_POST['students']) && is_array($_POST['students'])) {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($_POST['students'] as $student_id => $data) {
            $marks = isset($data['marks']) ? (float)$data['marks'] : 0;
            $grade = calculateGrade($marks, $max_marks);
            $remarks = mysqli_real_escape_string($link, $data['remarks'] ?? $grade);
            
            // Check if result already exists
            $check_query = "SELECT result_id FROM results 
                           WHERE student_id = $student_id 
                           AND subject_id = $subject_id 
                           AND term = '$term' 
                           AND year = $year";
            $check_result = mysqli_query($link, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                // Update existing result
                $update_query = "UPDATE results 
                               SET marks = $marks, grade = '$grade', remarks = '$remarks', 
                                   teacher_id = $teacher_id
                               WHERE student_id = $student_id 
                               AND subject_id = $subject_id 
                               AND term = '$term' 
                               AND year = $year";
                if (mysqli_query($link, $update_query)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                // Insert new result
                $insert_query = "INSERT INTO results (student_id, subject_id, term, year, marks, grade, remarks, teacher_id)
                                VALUES ($student_id, $subject_id, '$term', $year, $marks, '$grade', '$remarks', $teacher_id)";
                if (mysqli_query($link, $insert_query)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        $message = "Results saved successfully! $success_count students updated.";
        if ($error_count > 0) {
            $message .= " $error_count errors occurred.";
        }
        echo "<div class='alert alert-info'>$message</div>";
    }
}

function calculateGrade($marks, $max_marks) {
    $percentage = ($marks / $max_marks) * 100;
    
    if ($percentage >= 80) return 'A';
    elseif ($percentage >= 70) return 'B';
    elseif ($percentage >= 60) return 'C';
    elseif ($percentage >= 50) return 'D';
    elseif ($percentage >= 40) return 'E';
    else return 'F';
}

// Get students for selected class and subject
$students = [];
$selected_class = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$selected_subject = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;

if ($selected_class > 0) {
    $students_query = "SELECT s.student_id, s.name, s.admission_number 
                      FROM students s 
                      WHERE s.class_id = $selected_class 
                      ORDER BY s.name";
    $students_result = mysqli_query($link, $students_query);
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}
?>

<div class="results-entry-content">
    <div class="section-header">
        <h3>📝 Enter Results</h3>
        <p>Record student performance with spreadsheet-like interface</p>
    </div>

    <!-- Selection Form -->
    <div class="selection-form">
        <form method="POST" id="resultsForm" class="form-row">
            <div class="col-md-3">
                <label for="class_id">Class</label>
                <select name="class_id" id="class_id" class="form-control" required>
                    <option value="">Select Class</option>
                    <?php foreach ($available_classes as $class): ?>
                        <option value="<?php echo $class['class_id']; ?>" 
                                <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="subject_id">Subject</label>
                <select name="subject_id" id="subject_id" class="form-control" required>
                    <option value="">Select Subject</option>
                    <?php foreach ($available_subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>" 
                                data-class="<?php echo $subject['class_id']; ?>"
                                <?php echo $selected_subject == $subject['subject_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['subject_name'] . ' - ' . $subject['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="exam_type">Exam Type</label>
                <select name="exam_type" id="exam_type" class="form-control" required>
                    <option value="">Select Type</option>
                    <option value="Mid-term">Mid-term</option>
                    <option value="End-term">End-term</option>
                    <option value="Continuous Assessment">Continuous Assessment</option>
                    <option value="Practical">Practical</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="term">Term</label>
                <select name="term" id="term" class="form-control" required>
                    <option value="Term 1">Term 1</option>
                    <option value="Term 2">Term 2</option>
                    <option value="Term 3">Term 3</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="year">Year</label>
                <input type="number" name="year" id="year" class="form-control" value="<?php echo date('Y'); ?>" required>
            </div>
            <div class="col-md-2">
                <label for="max_marks">Max Marks</label>
                <input type="number" name="max_marks" id="max_marks" class="form-control" value="100" required>
            </div>
            <div class="col-md-12 mt-3">
                <button type="submit" name="load_students" class="btn btn-primary">Load Students</button>
                <?php if (!empty($students)): ?>
                    <button type="button" id="autoCalculate" class="btn btn-info">Auto Calculate Grades</button>
                    <button type="submit" name="save_results" class="btn btn-success">Save All Results</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Results Entry Table -->
    <?php if (!empty($students)): ?>
        <div class="results-table-section">
            <h4>Student Results Entry</h4>
            <div class="table-responsive">
                <table class="table table-striped results-table" id="resultsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Admission No.</th>
                            <th>Student Name</th>
                            <th>Marks (<?php echo $_POST['max_marks'] ?? '100'; ?>)</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): 
                            // Check if result already exists
                            $existing_result = null;
                            if ($selected_subject > 0 && isset($_POST['term']) && isset($_POST['year'])) {
                                $check_query = "SELECT * FROM results 
                                               WHERE student_id = {$student['student_id']} 
                                               AND subject_id = $selected_subject 
                                               AND term = '{$_POST['term']}' 
                                               AND year = {$_POST['year']}";
                                $check_result = mysqli_query($link, $check_query);
                                if ($row = mysqli_fetch_assoc($check_result)) {
                                    $existing_result = $row;
                                }
                            }
                        ?>
                            <tr data-student-id="<?php echo $student['student_id']; ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td>
                                    <input type="number" 
                                           name="students[<?php echo $student['student_id']; ?>][marks]" 
                                           class="form-control marks-input" 
                                           min="0" 
                                           max="<?php echo $_POST['max_marks'] ?? '100'; ?>" 
                                           step="0.1"
                                           value="<?php echo $existing_result['marks'] ?? ''; ?>"
                                           required>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="students[<?php echo $student['student_id']; ?>][grade]" 
                                           class="form-control grade-input" 
                                           value="<?php echo $existing_result['grade'] ?? ''; ?>"
                                           readonly>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="students[<?php echo $student['student_id']; ?>][remarks]" 
                                           class="form-control remarks-input" 
                                           value="<?php echo $existing_result['remarks'] ?? ''; ?>"
                                           placeholder="Auto-generated or custom remarks">
                                </td>
                                <td>
                                    <?php if ($existing_result): ?>
                                        <span class="badge badge-warning">Existing</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">New</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions">
            <button type="button" id="selectAll" class="btn btn-secondary">Select All</button>
            <button type="button" id="deselectAll" class="btn btn-secondary">Deselect All</button>
            <button type="button" id="applyCommonGrade" class="btn btn-info">Apply Common Grade</button>
            <button type="button" id="exportTemplate" class="btn btn-warning">Export Template</button>
        </div>
    <?php endif; ?>

    <!-- Recent Results -->
    <div class="recent-results">
        <h4>Recent Results Entered</h4>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Term/Year</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Exam Type</th>
                        <th>Term</th>
                        <th>Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                        <?php foreach (array_slice($results, 0, 10) as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['term'] . ' ' . $result['year']); ?></td>
                                <td><?php echo htmlspecialchars($result['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['term']); ?></td>
                                <td><?php echo $result['year']; ?></td>
                                <td>1</td>
                                <td>
                                    <a href="edit_result.php?result_id=<?php echo $result['result_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="view_result.php?result_id=<?php echo $result['result_id']; ?>" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="no-data">
                                    <div class="no-data-icon">📝</div>
                                    <p>No results entered yet</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate grades
    function calculateGrade(marks, maxMarks) {
        const percentage = (marks / maxMarks) * 100;
        if (percentage >= 80) return 'A';
        if (percentage >= 70) return 'B';
        if (percentage >= 60) return 'C';
        if (percentage >= 50) return 'D';
        if (percentage >= 40) return 'E';
        return 'F';
    }

    // Auto-calculate grades when marks change
    document.querySelectorAll('.marks-input').forEach(input => {
        input.addEventListener('input', function() {
            const row = this.closest('tr');
            const maxMarks = parseFloat(document.getElementById('max_marks').value) || 100;
            const marks = parseFloat(this.value) || 0;
            const grade = calculateGrade(marks, maxMarks);
            
            row.querySelector('.grade-input').value = grade;
            
            // Auto-generate remarks if empty
            const remarksInput = row.querySelector('.remarks-input');
            if (!remarksInput.value || remarksInput.value === '') {
                remarksInput.value = grade;
            }
        });
    });

    // Auto-calculate all grades
    document.getElementById('autoCalculate')?.addEventListener('click', function() {
        document.querySelectorAll('.marks-input').forEach(input => {
            input.dispatchEvent(new Event('input'));
        });
    });

    // Filter subjects based on selected class
    document.getElementById('class_id').addEventListener('change', function() {
        const selectedClass = this.value;
        const subjectSelect = document.getElementById('subject_id');
        
        Array.from(subjectSelect.options).forEach(option => {
            if (option.value === '' || option.dataset.class === selectedClass) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Reset subject selection
        subjectSelect.value = '';
    });

    // Bulk select/deselect
    document.getElementById('selectAll')?.addEventListener('click', function() {
        document.querySelectorAll('.marks-input').forEach(input => {
            input.closest('tr').classList.add('selected');
        });
    });

    document.getElementById('deselectAll')?.addEventListener('click', function() {
        document.querySelectorAll('.marks-input').forEach(input => {
            input.closest('tr').classList.remove('selected');
        });
    });

    // Apply common grade to selected
    document.getElementById('applyCommonGrade')?.addEventListener('click', function() {
        const commonMarks = prompt('Enter common marks for selected students:');
        if (commonMarks !== null) {
            document.querySelectorAll('tr.selected .marks-input').forEach(input => {
                input.value = commonMarks;
                input.dispatchEvent(new Event('input'));
            });
        }
    });

    // Export template
    document.getElementById('exportTemplate')?.addEventListener('click', function() {
        // Implementation for exporting template
        alert('Export template functionality will be implemented');
    });

    // Row selection
    document.querySelectorAll('#resultsTable tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            if (!e.target.classList.contains('marks-input') && 
                !e.target.classList.contains('remarks-input')) {
                this.classList.toggle('selected');
            }
        });
    });
});
</script>

<style>
.results-entry-content {
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

.selection-form {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.results-table-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.results-table {
    margin: 0;
}

.results-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    border: none;
    padding: 1rem 0.75rem;
}

.results-table td {
    padding: 0.75rem;
    vertical-align: middle;
}

.results-table tbody tr {
    transition: all 0.3s ease;
}

.results-table tbody tr:hover {
    background-color: #f8f9fa;
}

.results-table tbody tr.selected {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.marks-input, .remarks-input {
    border: 2px solid #e9ecef;
    border-radius: 6px;
    padding: 0.5rem;
    transition: border-color 0.3s ease;
}

.marks-input:focus, .remarks-input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.grade-input {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    padding: 0.5rem;
    text-align: center;
    font-weight: 600;
    color: #333;
}

.bulk-actions {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.recent-results {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.recent-results h4 {
    margin: 0 0 1.5rem 0;
    color: #333;
    font-weight: 600;
}

.no-data {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.no-data-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .selection-form .form-row {
        display: block;
    }
    
    .selection-form .col-md-3,
    .selection-form .col-md-2,
    .selection-form .col-md-12 {
        margin-bottom: 1rem;
        padding: 0;
    }
    
    .bulk-actions {
        flex-direction: column;
    }
    
    .bulk-actions .btn {
        width: 100%;
    }
}
</style>