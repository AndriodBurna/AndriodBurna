<?php
include 'includes/auth.php';
include 'includes/header.php'; 
require_once "config.php";

$student_id = $name = "";
$student_id_err = $name_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["student_id"]))){
        $student_id_err = "Please enter student ID.";
    } else{
        $student_id = trim($_POST["student_id"]);
    }

    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter name.";
    } else{
        $name = trim($_POST["name"]);
    }

    if(empty($student_id_err) && empty($name_err)){
        $sql = "INSERT INTO students (student_id, name) VALUES (?, ?)";

        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("ss", $param_student_id, $param_name);

            $param_student_id = $student_id;
            $param_name = $name;

            if($stmt->execute()){
                header("location: student_list.php");
            } else{
                echo "Something went wrong. Please try again later.";
            }

            $stmt->close();
        }
    }

    $conn->close();
}
?>

<div class="container">
    <h2>Add Student</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group <?php echo (!empty($student_id_err)) ? 'has-error' : ''; ?>">
            <label>Student ID</label>
            <input type="text" name="student_id" class="form-control" value="<?php echo $student_id; ?>">
            <span class="help-block"><?php echo $student_id_err; ?></span>
        </div>
        <div class="form-group <?php echo (!empty($name_err)) ? 'has-error' : ''; ?>">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo $name; ?>">
            <span class="help-block"><?php echo $name_err; ?></span>
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary" value="Add Student">
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>