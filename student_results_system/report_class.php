<?php
include 'includes/auth.php';
include 'includes/header.php';
require_once "config.php";

$class_id = $term_id = "";
$class_id_err = $term_id_err = "";
$results = [];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["class_id"]))){
        $class_id_err = "Please select a class.";
    } else{
        $class_id = trim($_POST["class_id"]);
    }

    if(empty(trim($_POST["term_id"]))){
        $term_id_err = "Please select a term.";
    } else{
        $term_id = trim($_POST["term_id"]);
    }

    if(empty($class_id_err) && empty($term_id_err)){
        $sql = "SELECT s.name as student_name, sub.name as subject_name, r.marks, r.grade 
                FROM results r
                JOIN students s ON r.student_id = s.id
                JOIN subjects sub ON r.subject_id = sub.id
                WHERE r.class_id = ? AND r.term_id = ?";

        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("ii", $param_class_id, $param_term_id);

            $param_class_id = $class_id;
            $param_term_id = $term_id;

            if($stmt->execute()){
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()){
                    $results[$row['student_name']][$row['subject_name']] = [
                        'marks' => $row['marks'],
                        'grade' => $row['grade']
                    ];
                }
            }
        }
    }
}

$sql_classes = "SELECT * FROM classes";
$result_classes = $conn->query($sql_classes);

$sql_terms = "SELECT * FROM terms";
$result_terms = $conn->query($sql_terms);

$sql_subjects = "SELECT * FROM subjects";
$result_subjects = $conn->query($sql_subjects);
$subjects = [];
while($row = $result_subjects->fetch_assoc()){
    $subjects[] = $row['name'];
}

?>

<div class="container">
    <h2>Class Performance Report</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group <?php echo (!empty($class_id_err)) ? 'has-error' : ''; ?>">
            <label>Class</label>
            <select name="class_id" class="form-control">
                <option value="">Select Class</option>
                <?php mysqli_data_seek($result_classes, 0); ?>
                <?php while($row = $result_classes->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>" <?php echo ($class_id == $row['id']) ? 'selected' : ''; ?>><?php echo $row['name']; ?></option>
                <?php endwhile; ?>
            </select>
            <span class="help-block"><?php echo $class_id_err;?></span>
        </div>
        <div class="form-group <?php echo (!empty($term_id_err)) ? 'has-error' : ''; ?>">
            <label>Term</label>
            <select name="term_id" class="form-control">
                <option value="">Select Term</option>
                <?php mysqli_data_seek($result_terms, 0); ?>
                <?php while($row = $result_terms->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>" <?php echo ($term_id == $row['id']) ? 'selected' : ''; ?>><?php echo $row['name']; ?></option>
                <?php endwhile; ?>
            </select>
            <span class="help-block"><?php echo $term_id_err;?></span>
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary" value="Generate Report">
        </div>
    </form>

    <?php if(!empty($results)): ?>
    <h3>Report</h3>
    <a href="export.php?type=csv&report=class&class_id=<?php echo $class_id; ?>&term_id=<?php echo $term_id; ?>" class="btn btn-success">Export to CSV</a>
    <table class="table">
        <thead>
            <tr>
                <th>Student</th>
                <?php foreach($subjects as $subject): ?>
                <th><?php echo $subject; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($results as $student_name => $subject_results): ?>
            <tr>
                <td><?php echo $student_name; ?></td>
                <?php foreach($subjects as $subject): ?>
                <td>
                    <?php if(isset($subject_results[$subject])):
                        echo $subject_results[$subject]['marks'] . ' (' . $subject_results[$subject]['grade'] . ')';
                    else:
                        echo 'N/A';
                    endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>