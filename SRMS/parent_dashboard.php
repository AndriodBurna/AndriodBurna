<?php
session_start();
require_once 'includes/auth.php';
require_role('parent');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

$username = $_SESSION['username'];
$parent_res = mysqli_query($link, "SELECT parent_id, name FROM parents WHERE email='" . mysqli_real_escape_string($link, $username) . "'");
$parent = mysqli_fetch_assoc($parent_res);

?>

<div class="container">
    <div class="dashboard-header">
        <h3>Parent Dashboard</h3>
        <div class="dashboard-clock"><span id="liveDate"></span> <span id="liveTime"></span></div>
    </div>
    <?php if (!$parent): ?>
        <div class="alert alert-warning">No parent record found for your account.</div>
    <?php else: ?>
        <p>Welcome, <?php echo sanitize($parent['name']); ?>. Your children's results:</p>
        <?php
        $pid = (int)$parent['parent_id'];
        $children = mysqli_query($link, "SELECT student_id, name FROM students WHERE parent_id = $pid ORDER BY name ASC");
        while ($child = mysqli_fetch_assoc($children)) {
            echo '<h5>' . sanitize($child['name']) . '</h5>';
            $sid = (int)$child['student_id'];
            $results = mysqli_query($link, "SELECT sb.subject_name, r.term, r.year, r.marks, r.grade, r.remarks
                                           FROM results r
                                           JOIN subjects sb ON r.subject_id = sb.subject_id
                                           WHERE r.student_id = $sid
                                           ORDER BY r.year DESC, r.term ASC, sb.subject_name ASC");
            echo '<table class="table table-bordered">'
                . '<thead><tr><th>Subject</th><th>Term</th><th>Year</th><th>Marks</th><th>Grade</th><th>Remarks</th></tr></thead><tbody>';
            while ($row = mysqli_fetch_assoc($results)) {
                echo '<tr>'
                    . '<td>' . sanitize($row['subject_name']) . '</td>'
                    . '<td>' . sanitize($row['term']) . '</td>'
                    . '<td>' . (int)$row['year'] . '</td>'
                    . '<td>' . (int)$row['marks'] . '</td>'
                    . '<td>' . sanitize($row['grade']) . '</td>'
                    . '<td>' . sanitize($row['remarks']) . '</td>'
                    . '</tr>';
            }
            echo '</tbody></table>';
        }
        ?>
    <?php endif; ?>
</div>

<script>
    (function(){
        function tick(){
            var d = new Date();
            var ld = document.getElementById('liveDate');
            var lt = document.getElementById('liveTime');
            if (ld) ld.textContent = d.toLocaleDateString();
            if (lt) lt.textContent = d.toLocaleTimeString();
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>

<?php require_once 'includes/footer.php'; ?>