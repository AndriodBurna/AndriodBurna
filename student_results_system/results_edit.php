<?php
include 'includes/auth.php';
include 'includes/header.php';
require_once "config.php";

$student_id = $term_id = $class_id = $subject_id = $marks = "";
$student_id_err = $term_id_err = $class_id_err = $subject_id_err = $marks_err = "";

if(isset($_POST["id"]) && !empty($_POST["id"])){
    $id = $_POST["id"];

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

    if(empty(trim($_POST["class_id"]))){
        $class_id_err = "Please select a class.";
    } else{
        $class_id = trim($_POST["class_id"]);
    }

    if(empty(trim($_POST["subject_id"]))){
        $subject_id_err = "Please select a subject.";
    } else{
        $subject_id = trim($_POST["subject_id"]);
    }

    if(empty(trim($_POST["marks"]))){
        $marks_err = "Please enter the marks.";
    } else{
        $marks = trim($_POST["marks"]);
    }

    if(empty($student_id_err) && empty($term_id_err) && empty($class_id_err) && empty($subject_id_err) && empty($marks_err)){
        $grade = "";
        if ($marks >= 90) {
            $grade = "A+";
        } elseif ($marks >= 80) {
            $grade = "A";
        } elseif ($marks >= 70) {
            $grade = "B";
        } elseif ($marks >= 60) {
            $grade = "C";
        } elseif ($marks >= 50) {
            $grade = "D";
        } else {
            $grade = "F";
        }

        $sql = "UPDATE results SET student_id=?, term_id=?, class_id=?, subject_id=?, marks=?, grade=? WHERE id=?";

        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("iiiissi", $param_student_id, $param_term_id, $param_class_id, $param_subject_id, $param_marks, $param_grade, $param_id);

            $param_student_id = $student_id;
            $param_term_id = $term_id;
            $param_class_id = $class_id;
            $param_subject_id = $subject_id;
            $param_marks = $marks;
            $param_grade = $grade;
            $param_id = $id;

            if($stmt->execute()){
                header("location: results_list.php");
            } else{
                echo "Something went wrong. Please try again later.";
            }
        }
    }

} else{
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){    
        $id =  trim($_GET["id"]);
        $sql = "SELECT * FROM results WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $param_id);
            $param_id = $id;
            if($stmt->execute()){
                $result = $stmt->get_result();
                if($result->num_rows == 1){
                    $row = $result->fetch_array(MYSQLI_ASSOC);
                    $student_id = $row["student_id"];
                    $term_id = $row["term_id"];
                    $class_id = $row["class_id"];
                    $subject_id = $row["subject_id"];
                    $marks = $row["marks"];
                } else{
                    header("location: error.php");
                    exit();
                }
                
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
        $stmt->close();
    }  else{
        header("location: error.php");
        exit();
    }
}

$sql_students = "SELECT * FROM students";
$result_students = $conn->query($sql_students);

$sql_terms = "SELECT * FROM terms";
$result_terms = $conn->query($sql_terms);

$sql_classes = "SELECT * FROM classes";
$result_classes = $conn->query($sql_classes);

$sql_subjects = "SELECT * FROM subjects";
$result_subjects = $conn->query($sql_subjects);

?>

<div class="container">
    <h2>Edit Result</h2>
    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">
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
        <div class="form-group <?php echo (!empty($class_id_err)) ? 'has-error' : ''; ?>">
            <label>Class</label>
            <select name="class_id" class="form-control">
                <option value="">Select Class</option>
                <?php while($row = $result_classes->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>" <?php echo ($class_id == $row['id']) ? 'selected' : ''; ?>><?php echo $row['name']; ?></option>
                <?php endwhile; ?>
            </select>
            <span class="help-block"><?php echo $class_id_err;?></span>
        </div>
        <div class="form-group <?php echo (!empty($subject_id_err)) ? 'has-error' : ''; ?>">
            <label>Subject</label>
            <select name="subject_id" class="form-control">
                <option value="">Select Subject</option>
                <?php while($row = $result_subjects->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>" <?php echo ($subject_id == $row['id']) ? 'selected' : ''; ?>><?php echo $row['name']; ?></option>
                <?php endwhile; ?>
            </select>
            <span class="help-block"><?php echo $subject_id_err;?></span>
        </div>
        <div class="form-group <?php echo (!empty($marks_err)) ? 'has-error' : ''; ?>">
            <label>Marks</label>
            <input type="text" name="marks" class="form-control" value="<?php echo $marks; ?>">
            <span class="help-block"><?php echo $marks_err;?></span>
        </div>
        <input type="hidden" name="id" value="<?php echo $id; ?>"/>
        <div class="form-group">
            <input type="submit" class="btn btn-primary" value="Update Result">
            <a href="results_list.php" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>