<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'config.php';
require_once 'includes/helpers.php';

// Handle CSV export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_type'])) {
    $type = $_POST['export_type'];
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
    $term = isset($_POST['term']) ? trim($_POST['term']) : '';

    $filename = 'export_' . $type . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');
    if ($type === 'students') {
        $q = "SELECT s.student_id, s.name, c.class_name, c.stream, p.name AS parent_name
              FROM students s
              LEFT JOIN classes c ON s.class_id = c.class_id
              LEFT JOIN parents p ON s.parent_id = p.parent_id";
        if ($class_id) { $q .= " WHERE s.class_id = $class_id"; }
        $res = mysqli_query($link, $q);
        fputcsv($out, ['Student ID','Name','Class','Stream','Parent']);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($out, [$row['student_id'], $row['name'], $row['class_name'], $row['stream'], $row['parent_name']]);
        }
    } elseif ($type === 'results') {
        $q = "SELECT r.result_id, st.name AS student, sb.subject_name, r.term, r.year, r.marks, r.grade
              FROM results r
              JOIN students st ON r.student_id = st.student_id
              JOIN subjects sb ON r.subject_id = sb.subject_id";
        $conds = [];
        if ($class_id) { $conds[] = "st.class_id = $class_id"; }
        if ($year) { $conds[] = "r.year = $year"; }
        if ($term) { $conds[] = "r.term='" . mysqli_real_escape_string($link, $term) . "'"; }
        if ($conds) { $q .= ' WHERE ' . implode(' AND ', $conds); }
        $res = mysqli_query($link, $q);
        fputcsv($out, ['Result ID','Student','Subject','Term','Year','Marks','Grade']);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($out, [$row['result_id'],$row['student'],$row['subject_name'],$row['term'],$row['year'],$row['marks'],$row['grade']]);
        }
    } elseif ($type === 'fees') {
        // If fees table exists
        $exists = mysqli_query($link, "SHOW TABLES LIKE 'fees'");
        if (mysqli_num_rows($exists)) {
            $q = "SELECT * FROM fees";
            $res = mysqli_query($link, $q);
            $headers = [];
            if ($res) {
                $first = mysqli_fetch_assoc($res);
                if ($first) {
                    $headers = array_keys($first);
                    fputcsv($out, $headers);
                    fputcsv($out, array_values($first));
                }
                while ($row = mysqli_fetch_assoc($res)) { fputcsv($out, array_values($row)); }
            }
        } else {
            fputcsv($out, ['No fees table']);
        }
    } else {
        fputcsv($out, ['Unsupported export type']);
    }
    fclose($out);
    exit;
}

require_once 'includes/header.php';
// Get classes for filtering
$classes = mysqli_query($link, "SELECT class_id, class_name, stream FROM classes ORDER BY class_name, stream");
?>

<div class="container">
    <h3>Data Export</h3>
    <p>Export datasets to CSV and print views to PDF. Apply filters before exporting.</p>

    <form method="post" class="card card-body mb-4">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Dataset</label>
                <select name="export_type" class="form-control" required>
                    <option value="students">Students</option>
                    <option value="results">Results</option>
                    <option value="fees">Fees</option>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Class (optional)</label>
                <select name="class_id" class="form-control">
                    <option value="">All</option>
                    <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                        <option value="<?php echo (int)$c['class_id']; ?>"><?php echo sanitize($c['class_name'] . ($c['stream'] ? ' - ' . $c['stream'] : '')); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label>Year</label>
                <input type="number" name="year" class="form-control" value="<?php echo (int)date('Y'); ?>">
            </div>
            <div class="form-group col-md-2">
                <label>Term</label>
                <select name="term" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['Term 1','Term 2','Term 3'] as $t): ?>
                        <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2 d-flex align-items-end">
                <button class="btn btn-primary btn-block" type="submit">Export CSV</button>
            </div>
        </div>
    </form>

    <div class="card"><div class="card-body">
        <h5 class="card-title">Print View (use browser print to save as PDF)</h5>
        <p class="text-muted">This sample shows recent results. Use it for quick PDF exports.</p>
        <?php 
            $recent = mysqli_query($link, "SELECT st.name AS student, sb.subject_name, r.term, r.year, r.marks, r.grade FROM results r JOIN students st ON r.student_id = st.student_id JOIN subjects sb ON r.subject_id = sb.subject_id ORDER BY r.year DESC, r.term ASC LIMIT 50");
        ?>
        <div class="mb-2"><button class="btn btn-secondary" onclick="window.print()">Print / Save as PDF</button></div>
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Term</th>
                    <th>Year</th>
                    <th>Marks</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                    <tr>
                        <td><?php echo sanitize($row['student']); ?></td>
                        <td><?php echo sanitize($row['subject_name']); ?></td>
                        <td><?php echo sanitize($row['term']); ?></td>
                        <td><?php echo (int)$row['year']; ?></td>
                        <td><?php echo (int)$row['marks']; ?></td>
                        <td><?php echo sanitize($row['grade']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div></div>
</div>

<?php require_once 'includes/footer.php'; ?>