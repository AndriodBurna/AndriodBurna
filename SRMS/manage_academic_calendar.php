<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure required tables exist on fresh databases
ensure_table($link, 'academic_terms', "CREATE TABLE IF NOT EXISTS `academic_terms` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `term_name` varchar(255) NOT NULL,\n  `start_date` date NOT NULL,\n  `end_date` date NOT NULL,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
ensure_table($link, 'academic_events', "CREATE TABLE IF NOT EXISTS `academic_events` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `event_title` varchar(255) NOT NULL,\n  `event_date` date NOT NULL,\n  `event_type` varchar(100) DEFAULT NULL,\n  `description` text DEFAULT NULL,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['term_name'])) {
        $name = sanitize($_POST['term_name']);
        $start_date = sanitize($_POST['term_start']);
        $end_date = sanitize($_POST['term_end']);
        if ($name !== '' && $start_date !== '' && $end_date !== '') {
            $stmt = mysqli_prepare($link, "INSERT INTO academic_terms (term_name, start_date, end_date) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sss', $name, $start_date, $end_date);
            mysqli_stmt_execute($stmt);
            $msg = '<div class="alert alert-success">Term added.</div>';
        } else { $msg = '<div class="alert alert-warning">Fill all term fields.</div>'; }
    } elseif (isset($_POST['event_title'])) {
        $title = sanitize($_POST['event_title']);
        $date = sanitize($_POST['event_date']);
        $type = sanitize($_POST['event_type']);
        $description = sanitize($_POST['event_description']);
        if ($title !== '' && $date !== '') {
            $stmt = mysqli_prepare($link, "INSERT INTO academic_events (event_title, event_date, event_type, description) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssss', $title, $date, $type, $description);
            mysqli_stmt_execute($stmt);
            $msg = '<div class="alert alert-success">Event added.</div>';
        } else { $msg = '<div class="alert alert-warning">Title and date are required.</div>'; }
    }
}

$terms = mysqli_query($link, "SELECT id, term_name, start_date, end_date FROM academic_terms ORDER BY start_date DESC");
$events = mysqli_query($link, "SELECT id, event_title, event_date, event_type FROM academic_events ORDER BY event_date DESC");
?>

<div class="container">
    <h3>Academic Calendar</h3>
    <?php echo $msg; ?>
    <div class="row">
        <div class="col-md-6">
            <form method="post" class="card card-body mb-4">
                <h5>Add Term</h5>
                <div class="form-row">
                    <div class="form-group col-md-5"><label>Name</label><input name="term_name" class="form-control" required></div>
                    <div class="form-group col-md-3"><label>Start</label><input type="date" name="term_start" class="form-control" required></div>
                    <div class="form-group col-md-3"><label>End</label><input type="date" name="term_end" class="form-control" required></div>
                </div>
                <button type="submit" class="btn btn-primary">Add Term</button>
            </form>
            <table class="table table-bordered table-sm">
                <thead><tr><th>Name</th><th>Start</th><th>End</th></tr></thead>
                <tbody>
                    <?php if ($terms && $terms instanceof mysqli_result): ?>
                        <?php while ($t = mysqli_fetch_assoc($terms)): ?>
                            <tr><td><?php echo sanitize($t['term_name']); ?></td><td><?php echo sanitize($t['start_date']); ?></td><td><?php echo sanitize($t['end_date']); ?></td></tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No terms found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <form method="post" class="card card-body mb-4">
                <h5>Add Event</h5>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Title</label><input name="event_title" class="form-control" required></div>
                    <div class="form-group col-md-3"><label>Date</label><input type="date" name="event_date" class="form-control" required></div>
                    <div class="form-group col-md-3"><label>Type</label><input name="event_type" class="form-control" placeholder="Holiday/Exam"></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="event_description" class="form-control" rows="3"></textarea></div>
                <button type="submit" class="btn btn-primary">Add Event</button>
            </form>
            <table class="table table-bordered table-sm">
                <thead><tr><th>Title</th><th>Date</th><th>Type</th></tr></thead>
                <tbody>
                    <?php if ($events && $events instanceof mysqli_result): ?>
                        <?php while ($e = mysqli_fetch_assoc($events)): ?>
                            <tr><td><?php echo sanitize($e['event_title']); ?></td><td><?php echo sanitize($e['event_date']); ?></td><td><?php echo sanitize($e['event_type']); ?></td></tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No events found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>