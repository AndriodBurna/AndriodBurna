<?php
session_start();
require_once 'includes/auth.php';
require_role('admin');
require_once 'includes/header.php';
require_once 'config.php';
require_once 'includes/helpers.php';

// Ensure grading table exists
ensure_table($link, 'grading_systems', "CREATE TABLE IF NOT EXISTS `grading_systems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `scheme_type` enum('letters','points','percentages') NOT NULL DEFAULT 'letters',
  `pass_mark` decimal(5,2) NOT NULL DEFAULT 50.00,
  `definition_json` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $scheme_type = sanitize($_POST['scheme_type'] ?? 'letters');
    $pass_mark = (float)($_POST['pass_mark'] ?? 0);
    $definition_json = $_POST['definition_json'] ?? '';
    if ($name !== '' && $definition_json !== '') {
        $stmt = mysqli_prepare($link, "INSERT INTO grading_systems (name, scheme_type, pass_mark, definition_json) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssds', $name, $scheme_type, $pass_mark, $definition_json);
        mysqli_stmt_execute($stmt);
        $msg = '<div class="alert alert-success">Grading system saved.</div>';
    } else {
        $msg = '<div class="alert alert-warning">Name and definition are required.</div>';
    }
}

$systems = mysqli_query($link, "SELECT id, name, scheme_type, pass_mark FROM grading_systems ORDER BY name ASC");
?>

<div class="container">
    <h3>Grading Systems</h3>
    <?php echo $msg; ?>
    <form method="post" class="card card-body mb-4">
        <div class="form-row">
            <div class="form-group col-md-4"><label>Name</label><input name="name" class="form-control" required></div>
            <div class="form-group col-md-3">
                <label>Scheme</label>
                <select name="scheme_type" class="form-control">
                    <option value="letters">Letters</option>
                    <option value="points">Points</option>
                    <option value="percentages">Percentages</option>
                </select>
            </div>
            <div class="form-group col-md-2"><label>Pass Mark</label><input type="number" step="0.01" name="pass_mark" class="form-control" value="50"></div>
        </div>
        <div class="form-group">
            <label>Definition (JSON)</label>
            <textarea name="definition_json" class="form-control" rows="6" placeholder='{"A": "80-100", "B": "70-79"}' required></textarea>
            <small class="form-text text-muted">Provide ranges or point mappings as JSON.</small>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>

    <table class="table table-bordered table-sm">
        <thead><tr><th>Name</th><th>Scheme</th><th>Pass Mark</th></tr></thead>
        <tbody>
            <?php if ($systems && $systems instanceof mysqli_result): ?>
                <?php while ($gs = mysqli_fetch_assoc($systems)): ?>
                    <tr>
                        <td><?php echo sanitize($gs['name']); ?></td>
                        <td><?php echo sanitize($gs['scheme_type']); ?></td>
                        <td><?php echo sanitize($gs['pass_mark']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">No grading systems found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>