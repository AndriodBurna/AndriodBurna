<?php
if(isset($_POST["id"]) && !empty($_POST["id"])){
    require_once "config.php";

    $sql = "DELETE FROM students WHERE id = ?";

    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $param_id);

        $param_id = trim($_POST["id"]);

        if($stmt->execute()){
            header("location: student_list.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }

    $stmt->close();

    $conn->close();
} else{
    if(empty(trim($_GET["id"]))){
        header("location: error.php");
        exit();
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h2>Delete Student</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="alert alert-danger" role="alert">
            <input type="hidden" name="id" value="<?php echo trim($_GET["id"]); ?>"/>
            <p>Are you sure you want to delete this record?</p><br>
            <p>
                <input type="submit" value="Yes" class="btn btn-danger">
                <a href="student_list.php" class="btn btn-default">No</a>
            </p>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>