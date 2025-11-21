<?php
require_once 'includes/auth.php';
require_once 'config.php';
require_once 'includes/helpers.php';
// Excel support is optional; if library present, we'll use it
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isAdmin()) {
    header('Location: login.php');
    exit();
}

$message = '';

if (isset($_POST['import'])) {
    $tmp = $_FILES['file']['tmp_name'] ?? '';
    $name = $_FILES['file']['name'] ?? '';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $rows = [];

    // Build rows from CSV or Excel
    if ($ext === 'csv') {
        if (($h = fopen($tmp, 'r')) !== false) {
            $header = fgetcsv($h);
            $map = [];
            if ($header) {
                foreach ($header as $i => $col) {
                    $key = strtolower(trim($col));
                    $map[$key] = $i;
                }
            }
            while (($data = fgetcsv($h)) !== false) {
                $rows[] = $data;
            }
            fclose($h);
            // Mark that first line was header
            $hasHeader = true;
        }
    } elseif (in_array($ext, ['xlsx','xls'])) {
        if (class_exists(IOFactory::class)) {
            try {
                $spreadsheet = IOFactory::load($tmp);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
            } catch (\Throwable $e) {
                $message = "<div class='alert alert-danger'>Failed to read Excel file: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            $message = "<div class='alert alert-warning'>Excel import requires PhpSpreadsheet. Please install via Composer: <code>composer require phpoffice/phpspreadsheet</code>. You can import CSV files without additional libraries.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Unsupported file type. Please upload a CSV or Excel (xlsx/xls) file.</div>";
    }

    $successCount = 0;
    $errorCount = 0;

    // Ensure student_uid column exists for UID generation
    ensure_column($link, 'students', 'student_uid', '`student_uid` varchar(20) DEFAULT NULL');

    // Skip header row if Excel/array includes header
    if (!empty($rows)) {
        // Heuristically detect header: if first row has strings matching our expected header names
        $first = $rows[0];
        $first_join = strtolower(implode('|', $first));
        if (strpos($first_join, 'name') !== false && (strpos($first_join, 'email') !== false || strpos($first_join, 'class') !== false)) {
            array_shift($rows);
        }
    }

    foreach ($rows as $row) {
        // Try to map by index default: name, email, class_id
        $col_name = trim($row[0] ?? '');
        $col_email = trim($row[1] ?? '');
        $col_class = trim($row[2] ?? '');

        $nameVal = $col_name;
        $emailVal = $col_email;
        $classIdVal = is_numeric($col_class) ? (int)$col_class : 0;
        $genderVal = trim($row[3] ?? 'Other');
        if ($genderVal === '') { $genderVal = 'Other'; }
        $dobVal = trim($row[4] ?? '');
        if ($dobVal === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dobVal)) { $dobVal = '2000-01-01'; }

        // Basic validation
        if (!empty($nameVal) && !empty($emailVal) && $classIdVal > 0) {
            // Check if student already exists
            $stmt = $link->prepare("SELECT student_id FROM students WHERE email = ?");
            $stmt->bind_param("s", $emailVal);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $uid = generate_student_uid($link);
                $year_joined = (int) date('Y');
                // Insert student
                $stmt = $link->prepare("INSERT INTO students (student_uid, name, gender, dob, class_id, email, year_joined) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssisi", $uid, $nameVal, $genderVal, $dobVal, $classIdVal, $emailVal, $year_joined);
                if ($stmt->execute()) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        } else {
            $errorCount++;
        }
    }

    $message = "<div class='alert alert-success'>Import complete. {$successCount} students imported successfully, {$errorCount} errors.</div>";
}

$pageTitle = 'Bulk Import Students';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Bulk Import Students</h4>
                    <p>Upload a CSV or Excel file with student data. The file should contain the following columns: `name`, `email`, `class_id`.</p>
                    
                    <?php if ($message) echo $message; ?>

                    <form action="student_import.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="file">Select file</label>
                            <input type="file" name="file" id="file" class="form-control-file" required>
                        </div>
                        <button type="submit" name="import" class="btn btn-primary">Import</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>