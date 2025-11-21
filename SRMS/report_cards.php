<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'config.php';
require_once 'includes/helpers.php';
require_once 'includes/header.php';

// Fetch classes for selection
$classes = mysqli_query($link, "SELECT class_id, class_name, stream FROM classes ORDER BY class_name, stream");

// Params
$selected_class = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$selected_term = isset($_GET['term']) ? trim($_GET['term']) : '';
$selected_year = isset($_GET['year']) ? (int) $_GET['year'] : (int)date('Y');

// When class+term+year provided, load report data
$report_data = [];
if ($selected_class && $selected_term && $selected_year) {
    $sql = "SELECT st.student_id, st.name AS student_name, sb.subject_name, r.marks, r.grade, r.remarks
            FROM students st
            JOIN results r ON r.student_id = st.student_id
            JOIN subjects sb ON sb.subject_id = r.subject_id
            WHERE st.class_id = $selected_class AND r.term='" . mysqli_real_escape_string($link, $selected_term) . "' AND r.year = $selected_year
            ORDER BY st.name, sb.subject_name";
    $res = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $sid = (int) $row['student_id'];
        if (!isset($report_data[$sid])) {
            $report_data[$sid] = [
                'student_name' => $row['student_name'],
                'subjects' => []
            ];
        }
        $report_data[$sid]['subjects'][] = $row;
    }
}
?>

<div class="container">
    <h3>Automated Report Cards</h3>
    <p>Generate highly customizable report cards. Select a class, term, and year, then batch-generate for the entire class. Use the browser print dialog to save as PDF.</p>

    <form method="get" class="card card-body mb-3">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Class</label>
                <select name="class_id" class="form-control" required>
                    <option value="">Select class</option>
                    <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                        <option value="<?php echo (int)$c['class_id']; ?>" <?php echo $selected_class == (int)$c['class_id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($c['class_name'] . ($c['stream'] ? ' - ' . $c['stream'] : '')); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Term</label>
                <select name="term" class="form-control" required>
                    <?php foreach (['Term 1','Term 2','Term 3'] as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo $selected_term === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label>Year</label>
                <input type="number" name="year" class="form-control" value="<?php echo (int)$selected_year; ?>" required>
            </div>
            <div class="form-group col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-block">Generate</button>
            </div>
        </div>
    </form>

    <?php if ($selected_class && $selected_term && $selected_year): ?>
        <div class="mb-3">
            <button class="btn btn-secondary" onclick="window.print()">Print / Save as PDF</button>
        </div>
        <?php if (empty($report_data)): ?>
            <div class="alert alert-info">No results found for the selected class and term.</div>
        <?php else: ?>
            <?php foreach ($report_data as $sid => $data): ?>
                <div class="card mb-4 report-card">
                    <div class="card-body">
                        <div class="report-header d-flex justify-content-between">
                            <div>
                                <h5>Report Card</h5>
                                <p><strong>Student:</strong> <?php echo sanitize($data['student_name']); ?></p>
                                <p><strong>Term:</strong> <?php echo sanitize($selected_term); ?> &nbsp; <strong>Year:</strong> <?php echo (int)$selected_year; ?></p>
                            </div>
                            <div class="text-right">
                                <p><strong>Class:</strong> <?php
                                    $cn = mysqli_fetch_assoc(mysqli_query($link, "SELECT class_name, stream FROM classes WHERE class_id = $selected_class"));
                                    echo sanitize($cn['class_name'] . ($cn['stream'] ? ' - ' . $cn['stream'] : ''));
                                ?></p>
                                <p><strong>Date:</strong> <?php echo date('Y-m-d'); ?></p>
                            </div>
                        </div>
                        <table class="table table-sm table-bordered mt-3">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Teacher Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0; $count = 0; 
                                foreach ($data['subjects'] as $sub): 
                                    $total += (int)$sub['marks']; $count++;
                                ?>
                                    <tr>
                                        <td><?php echo sanitize($sub['subject_name']); ?></td>
                                        <td><?php echo (int)$sub['marks']; ?></td>
                                        <td><?php echo sanitize($sub['grade']); ?></td>
                                        <td><?php echo sanitize($sub['remarks']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Average</th>
                                    <th colspan="3"><?php echo $count ? round($total / $count, 2) : 0; ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        <?php 
                            // principal remarks if any
                            $pr = mysqli_fetch_assoc(mysqli_query($link, "SELECT principal_remarks FROM results WHERE student_id = $sid AND term='" . mysqli_real_escape_string($link, $selected_term) . "' AND year = $selected_year AND principal_remarks IS NOT NULL LIMIT 1"));
                        ?>
                        <?php if ($pr && !empty($pr['principal_remarks'])): ?>
                            <p><strong>Principal's Remarks:</strong> <?php echo sanitize($pr['principal_remarks']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
@media print {
  .app-sidebar, .app-content .btn, nav, header, footer { display: none !important; }
  .app-content { margin: 0; padding: 0; }
  .report-card { page-break-inside: avoid; }
}
</style>

<?php require_once 'includes/footer.php'; ?>