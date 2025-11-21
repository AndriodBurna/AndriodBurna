<?php
// Reports & Analytics tab content
$teacher_id = isset($teacher_id) ? (int)$teacher_id : 0;

if ($teacher_id <= 0) {
    echo "<div class='alert alert-warning'>No teacher selected.</div>";
    return;
}

// Summary metrics
$summary = [
    'total_results' => 0,
    'average_marks' => 0,
    'highest_marks' => 0,
    'lowest_marks' => 0,
    'grade_counts' => ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0,'F'=>0]
];

// Total results and aggregate stats
$summary_query = "SELECT COUNT(r.result_id) as total_results, AVG(r.marks) as avg_marks, MAX(r.marks) as max_marks, MIN(r.marks) as min_marks
                  FROM results r
                  WHERE r.teacher_id = $teacher_id";
$summary_res = mysqli_query($link, $summary_query);
if ($row = mysqli_fetch_assoc($summary_res)) {
    $summary['total_results'] = (int)$row['total_results'];
    $summary['average_marks'] = round((float)$row['avg_marks'], 2);
    $summary['highest_marks'] = (int)$row['max_marks'];
    $summary['lowest_marks'] = (int)$row['min_marks'];
}

// Grade distribution
// Auto-calculate grade distribution from marks to ensure consistency
$grade_query = "SELECT 
    CASE 
        WHEN r.marks >= 80 THEN 'A'
        WHEN r.marks >= 70 THEN 'B'
        WHEN r.marks >= 60 THEN 'C'
        WHEN r.marks >= 50 THEN 'D'
        WHEN r.marks >= 40 THEN 'E'
        ELSE 'F'
    END AS grade_band, COUNT(*) as cnt
    FROM results r
    WHERE r.teacher_id = $teacher_id
    GROUP BY grade_band";
$grade_res = mysqli_query($link, $grade_query);
while ($row = mysqli_fetch_assoc($grade_res)) {
    $g = strtoupper($row['grade_band']);
    if (isset($summary['grade_counts'][$g])) { $summary['grade_counts'][$g] = (int)$row['cnt']; }
}

// Compute pass rate (marks >= 50)
$pass_rate = 0;
$pass_q = mysqli_query($link, "SELECT COUNT(*) AS total, SUM(CASE WHEN marks >= 50 THEN 1 ELSE 0 END) AS passed FROM results WHERE teacher_id = $teacher_id");
if ($pr = mysqli_fetch_assoc($pass_q)) {
    $total = (int)$pr['total'];
    $passed = (int)$pr['passed'];
    $pass_rate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
}

// Averages per class/subject for current year
$current_year = date('Y');
$breakdown = [];
$breakdown_query = "SELECT c.class_name, c.stream, sub.subject_name, r.term, r.year,
                           COUNT(r.result_id) as total_students, AVG(r.marks) as avg_marks
                    FROM results r
                    JOIN students s ON r.student_id = s.student_id
                    JOIN classes c ON s.class_id = c.class_id
                    JOIN subjects sub ON r.subject_id = sub.subject_id
                    WHERE r.teacher_id = $teacher_id AND r.year = $current_year
                    GROUP BY c.class_name, c.stream, sub.subject_name, r.term, r.year
                    ORDER BY c.class_name, sub.subject_name, r.term";
$bd_res = mysqli_query($link, $breakdown_query);
while ($row = mysqli_fetch_assoc($bd_res)) {
    $breakdown[] = $row;
}
?>

<div class="reports-tab">
    <h3>Reports & Analytics</h3>
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h4><?php echo $summary['total_results']; ?></h4>
                    <p>Total Results</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <h4><?php echo $summary['average_marks']; ?></h4>
                    <p>Average Marks</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-content">
                    <h4><?php echo $summary['highest_marks']; ?></h4>
                    <p>Highest Marks</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">🔻</div>
                <div class="stat-content">
                    <h4><?php echo $summary['lowest_marks']; ?></h4>
                    <p>Lowest Marks</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h4><?php echo $pass_rate; ?>%</h4>
                    <p>Pass Rate (>=50)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grade-distribution card">
        <h4>Grade Distribution</h4>
        <div class="row">
            <?php foreach ($summary['grade_counts'] as $g => $cnt): ?>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon"><?php echo $g; ?></div>
                        <div class="stat-content">
                            <h4><?php echo $cnt; ?></h4>
                            <p>Students</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="table-responsive card">
        <h4>Class/Subject Averages (<?php echo $current_year; ?>)</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Term</th>
                    <th>Year</th>
                    <th>Students</th>
                    <th>Average</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($breakdown)): ?>
                    <?php foreach ($breakdown as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['class_name'] . ' ' . $row['stream']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['term']); ?></td>
                            <td><?php echo htmlspecialchars($row['year']); ?></td>
                            <td><?php echo (int)$row['total_students']; ?></td>
                            <td><?php echo round((float)$row['avg_marks'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No data available</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="top-performers card mt-4">
        <h4>Top Performers (Overall)</h4>
        <?php
        $top_performers_query = "SELECT s.student_name, AVG(r.marks) as overall_average
                                FROM results r
                                JOIN students s ON r.student_id = s.student_id
                                WHERE r.teacher_id = $teacher_id
                                GROUP BY s.student_id, s.student_name
                                ORDER BY overall_average DESC
                                LIMIT 5";
        $top_performers_res = mysqli_query($link, $top_performers_query);
        ?>
        <?php if (mysqli_num_rows($top_performers_res) > 0): ?>
            <ul class="list-group list-group-flush">
                <?php while ($tp_row = mysqli_fetch_assoc($top_performers_res)): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo htmlspecialchars($tp_row['student_name']); ?>
                        <span class="badge bg-primary rounded-pill"><?php echo round((float)$tp_row['overall_average'], 2); ?>%</span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="text-center">No top performers data available.</p>
        <?php endif; ?>
    </div>

    <div class="subject-mastery card mt-4">
        <h4>Subject Mastery (Average Marks per Subject)</h4>
        <?php
        $subject_mastery_query = "SELECT sub.subject_name, AVG(r.marks) as subject_average
                                  FROM results r
                                  JOIN subjects sub ON r.subject_id = sub.subject_id
                                  WHERE r.teacher_id = $teacher_id
                                  GROUP BY sub.subject_name
                                  ORDER BY subject_average DESC";
        $subject_mastery_res = mysqli_query($link, $subject_mastery_query);
        ?>
        <?php if (mysqli_num_rows($subject_mastery_res) > 0): ?>
            <ul class="list-group list-group-flush">
                <?php while ($sm_row = mysqli_fetch_assoc($subject_mastery_res)): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo htmlspecialchars($sm_row['subject_name']); ?>
                        <span class="badge bg-info rounded-pill"><?php echo round((float)$sm_row['subject_average'], 2); ?>%</span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="text-center">No subject mastery data available.</p>
        <?php endif; ?>
    </div>

    <div class="export-actions">
        <form method="post" action="export_reports.php">
            <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
            <button type="submit" class="btn btn-secondary">Export CSV</button>
        </form>
    </div>
</div>