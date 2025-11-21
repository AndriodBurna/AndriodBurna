<?php
// Classes and Subjects tab content
$classes = $tab_data['classes'] ?? [];
?>

<div class="classes-subjects-content">
    <div class="section-header">
        <h3>📚 My Classes & Subjects</h3>
        <p>Manage your assigned classes and teaching subjects</p>
    </div>

    <!-- Classes Section -->
    <div class="classes-section">
        <h4>Assigned Classes</h4>
        <div class="classes-grid">
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $class): ?>
                    <div class="class-card">
                        <div class="class-header">
                            <h5><?php echo htmlspecialchars($class['class_name']); ?> <?php echo htmlspecialchars($class['section']); ?></h5>
                            <span class="student-count"><?php echo $class['student_count']; ?> students</span>
                        </div>
                        <div class="class-details">
                            <p><strong>Class Teacher:</strong> <?php echo htmlspecialchars($teacher_name); ?></p>
                            <p><strong>Room:</strong> <?php echo htmlspecialchars($class['room'] ?? 'TBA'); ?></p>
                            <p><strong>Capacity:</strong> <?php echo $class['capacity'] ?? '30'; ?></p>
                        </div>
                        <div class="class-actions">
                            <a href="student_list.php?class_id=<?php echo $class['class_id']; ?>" class="btn btn-sm btn-primary">View Students</a>
                            <a href="class_performance.php?class_id=<?php echo $class['class_id']; ?>" class="btn btn-sm btn-info">Performance</a>
                            <a href="class_attendance.php?class_id=<?php echo $class['class_id']; ?>" class="btn btn-sm btn-success">Attendance</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📚</div>
                    <h5>No Classes Assigned</h5>
                    <p>You don't have any classes assigned yet. Contact the administration for class assignments.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subjects Section -->
    <div class="subjects-section">
        <h4>Teaching Subjects</h4>
        <div class="subjects-table">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Class</th>
                        <th>Students</th>
                        <th>Recent Results</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($teacher_data['subjects'])): ?>
                        <?php foreach ($teacher_data['subjects'] as $subject): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($subject['class_name']); ?></td>
                                <td>
                                    <?php 
                                    // Get student count for this subject's class
                                    $student_count = 0;
                                    $count_query = "SELECT COUNT(*) as count FROM students WHERE class_id = {$subject['class_id']}";
                                    $count_result = mysqli_query($link, $count_query);
                                    if ($count_row = mysqli_fetch_assoc($count_result)) {
                                        $student_count = $count_row['count'];
                                    }
                                    echo $student_count;
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    // Get recent results count for this subject
                                    $results_count = 0;
                                    $results_query = "SELECT COUNT(*) as count FROM results WHERE subject_id = {$subject['subject_id']} AND teacher_id = $teacher_id";
                                    $results_result = mysqli_query($link, $results_query);
                                    if ($results_row = mysqli_fetch_assoc($results_result)) {
                                        $results_count = $results_row['count'];
                                    }
                                    echo $results_count;
                                    ?>
                                </td>
                                <td>
                                    <a href="result_add.php?subject_id=<?php echo $subject['subject_id']; ?>" class="btn btn-sm btn-success">Add Results</a>
                                    <a href="subject_performance.php?subject_id=<?php echo $subject['subject_id']; ?>" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="no-data">
                                    <div class="no-data-icon">🎯</div>
                                    <h5>No Subjects Assigned</h5>
                                    <p>You don't have any subjects assigned yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Class Statistics -->
    <div class="class-statistics">
        <h4>Class Overview</h4>
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($classes); ?></div>
                <div class="stat-label">Total Classes</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($teacher_data['subjects']); ?></div>
                <div class="stat-label">Total Subjects</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    $total_students = 0;
                    foreach ($classes as $class) {
                        $total_students += $class['student_count'];
                    }
                    echo $total_students;
                    ?>
                </div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $teacher_data['results_count']; ?></div>
                <div class="stat-label">Results Entered</div>
            </div>
        </div>
    </div>
</div>

<style>
.classes-subjects-content {
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

.classes-section, .subjects-section, .class-statistics {
    margin-bottom: 2rem;
}

.classes-section h4, .subjects-section h4, .class-statistics h4 {
    margin: 0 0 1.5rem 0;
    color: #333;
    font-weight: 600;
}

.classes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.class-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.class-card:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.class-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.class-header h5 {
    margin: 0;
    color: #333;
    font-weight: 600;
}

.student-count {
    background: #007bff;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.class-details p {
    margin: 0.5rem 0;
    color: #666;
}

.class-actions {
    margin-top: 1rem;
    display: flex;
    gap: 0.5rem;
}

.class-actions .btn {
    flex: 1;
    text-align: center;
}

.subjects-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1.5rem;
}

.stat-item {
    text-align: center;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #666;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
}

.no-data {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-data-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-data h5 {
    margin: 0 0 1rem 0;
    color: #333;
}

@media (max-width: 768px) {
    .classes-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .class-actions {
        flex-direction: column;
    }
}
</style>