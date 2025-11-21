<?php
include 'includes/auth.php';
include 'includes/header.php';
require_once "config.php";

$student_id = $name = "";
$student_id_err = $name_err = "";

if(isset($_POST["id"]) && !empty($_POST["id"])){
    $id = $_POST["id"];

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
        $sql = "UPDATE students SET student_id=?, name=? WHERE id=?";

        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("ssi", $param_student_id, $param_name, $param_id);

            $param_student_id = $student_id;
            $param_name = $name;
            $param_id = $id;

            if($stmt->execute()){
                header("location: student_list.php");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }

            $stmt->close();
        }
    }

    $conn->close();
} else{
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id =  trim($_GET["id"]);

        $sql = "SELECT * FROM students WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $param_id);

            $param_id = $id;

            if($stmt->execute()){
                $result = $stmt->get_result();

                if($result->num_rows == 1){
                    $row = $result->fetch_array(MYSQLI_ASSOC);

                    $student_id = $row["student_id"];
                    $name = $row["name"];
                } else{
                    header("location: error.php");
                    exit();
                }

            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            $stmt->close();
        }

        $conn->close();
    }  else{
        header("location: error.php");
        exit();
    }
}
?>

<div class="container">
    <h2>Edit Student</h2>
    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">
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
        <input type="hidden" name="id" value="<?php echo $id; ?>"/>
        <input type="submit" class="btn btn-primary" value="Submit">
        <a href="student_list.php" class="btn btn-default">Cancel</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>