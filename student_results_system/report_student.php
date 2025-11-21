<?php
include 'includes/auth.php';
include 'includes/header.php';
require_once "config.php";

$student_id = $term_id = "";
$student_id_err = $term_id_err = "";
$results = [];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["student_id"]))){
        $student_id_err = "Please select a student.";
    } else{
        $student_id = trim($_POST["student_id"]);
    }

    if(empty(trim($_POST["term_id"]))){
        $term_id_err = "Please select a term.";
    } else{
        $term_id = trim($_POST["term_id"]);
    }

    if(empty($student_id_err) && empty($term_id_err)){
        $sql = "SELECT sub.name as subject_name, r.marks, r.grade 
                FROM results r
                JOIN subjects sub ON r.subject_id = sub.id
                WHERE r.student_id = ? AND r.term_id = ?";

        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("ii", $param_student_id, $param_term_id);

            $param_student_id = $student_id;
            $param_term_id = $term_id;

            if($stmt->execute()){
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()){
                    $results[$row['subject_name']] = [
                        'marks' => $row['marks'],
                        'grade' => $row['grade']
                    ];
                }
            }
        }
    }
}

$sql_students = "SELECT * FROM students";
$result_students = $conn->query($sql_students);

$sql_terms = "SELECT * FROM terms";
$result_terms = $conn->query($sql_terms);

?>

<div class="container">
    <h2>Student Performance Report</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group <?php echo (!empty($student_id_err)) ? 'has-error' : ''; ?>">
            <label>Student</label>
            <select name="student_id" class="form-control">
                <option value="">Select Student</option>
                <?php while($row = $result_students->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>" <?php echo ($student_id == $row['id']) ? 'selected' : ''; ?>><?php echo $row['name']; ?></option>
                <?php endwhile; ?>
            </select>
            <span class="help-block"><?php echo $student_id_err;?></span>
        </div>
        <div class="form-group <?php echo (!empty($term_id_err)) ? 'has-error' : ''; ?>">
            <label>Term</label>
            <select name="term_id" class="form-control">
                <option value="">Select Term</option>
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
    <a href="export.php?type=csv&report=student&student_id=<?php echo $student_id; ?>&term_id=<?php echo $term_id; ?>" class="btn btn-success">Export to CSV</a>
    <table class="table">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Marks</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($results as $subject_name => $result_data): ?>
            <tr>
                <td><?php echo $subject_name; ?></td>
                <td><?php echo $result_data['marks']; ?></td>
                <td><?php echo $result_data['grade']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>