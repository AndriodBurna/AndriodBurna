<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'config.php';
require_once 'includes/helpers.php';
require_once 'includes/header.php';

$current_year = (int)date('Y');
$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;

// Trend analysis: average marks per subject across terms in selected year
$trend_labels = [];
$trend_datasets = [];

// Get all subjects
$subjects = [];
$sub_res = mysqli_query($link, "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name");
while ($s = mysqli_fetch_assoc($sub_res)) { $subjects[(int)$s['subject_id']] = $s['subject_name']; }

// Terms list
$terms = ['Term 1','Term 2','Term 3'];
$trend_labels = $terms;

foreach ($subjects as $sid => $sname) {
    $series = [];
    foreach ($terms as $t) {
        $q = "SELECT AVG(marks) AS avgm FROM results WHERE subject_id = $sid AND year = $year AND term='" . mysqli_real_escape_string($link, $t) . "'";
        $row = mysqli_fetch_assoc(mysqli_query($link, $q));
        $series[] = $row && $row['avgm'] !== null ? round((float)$row['avgm'], 2) : 0;
    }
    $trend_datasets[] = [
        'label' => $sname,
        'data' => $series
    ];
}

// Identify at-risk students (average < threshold) for selected term/year
$risk_threshold = 40;
$at_risk = [];
if ($term) {
    $risk_q = "SELECT st.student_id, st.name AS student_name, AVG(r.marks) AS avg_marks
               FROM results r
               JOIN students st ON st.student_id = r.student_id
               WHERE r.year = $year AND r.term='" . mysqli_real_escape_string($link, $term) . "'
               GROUP BY st.student_id, st.name
               HAVING AVG(r.marks) < $risk_threshold
               ORDER BY avg_marks ASC";
    $risk_res = mysqli_query($link, $risk_q);
    while ($row = mysqli_fetch_assoc($risk_res)) { $at_risk[] = $row; }
}
?>

<div class="container">
    <h3>Advanced Analytics</h3>
    <p>Explore trends and identify at-risk students based on configurable thresholds.</p>

    <form method="get" class="card card-body mb-3">
        <div class="form-row">
            <div class="form-group col-md-2">
                <label>Year</label>
                <input type="number" class="form-control" name="year" value="<?php echo (int)$year; ?>">
            </div>
            <div class="form-group col-md-3">
                <label>Term (for at-risk)</label>
                <select class="form-control" name="term">
                    <option value="">All</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo $term === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2 d-flex align-items-end">
                <button class="btn btn-primary" type="submit">Update</button>
            </div>
        </div>
    </form>

    <div class="card mb-4"><div class="card-body">
        <h5 class="card-title">Subject Performance Trends (<?php echo (int)$year; ?>)</h5>
        <canvas id="subjectTrend" height="120"></canvas>
    </div></div>

    <div class="card"><div class="card-body">
        <h5 class="card-title">At-Risk Students<?php echo $term ? ' — ' . htmlspecialchars($term) . ' ' . (int)$year : ''; ?></h5>
        <?php if ($term && empty($at_risk)): ?>
            <div class="alert alert-success">No students flagged as at-risk for the selected term.</div>
        <?php elseif (!$term): ?>
            <div class="alert alert-info">Select a term to compute at-risk students.</div>
        <?php else: ?>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Average Marks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($at_risk as $r): ?>
                        <tr>
                            <td><?php echo sanitize($r['student_name']); ?></td>
                            <td><?php echo round((float)$r['avg_marks'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    var ctx = document.getElementById('subjectTrend');
    if (!ctx) return;
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trend_labels); ?>,
            datasets: <?php echo json_encode(array_map(function($ds){
                return [
                    'label' => $ds['label'],
                    'data' => $ds['data'],
                    'tension' => 0.3,
                    'borderWidth' => 2
                ];
            }, $trend_datasets)); ?>
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Average Marks' } } }
        }
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>