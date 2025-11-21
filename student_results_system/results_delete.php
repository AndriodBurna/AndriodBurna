<?php
include 'includes/auth.php';

// Process delete operation after confirmation
if(isset($_POST["id"]) && !empty($_POST["id"])){
    // Include config file
    require_once "config.php";
    
    // Prepare a delete statement
    $sql = "DELETE FROM results WHERE id = ?";
    
    if($stmt = $conn->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("i", $param_id);
        
        // Set parameters
        $param_id = trim($_POST["id"]);
        
        // Attempt to execute the prepared statement
        if($stmt->execute()){
            // Records deleted successfully. Redirect to landing page
            header("location: results_list.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
     
    // Close statement
    $stmt->close();
    
    // Close connection
    $conn->close();
} else{
    // Check existence of id parameter
    if(empty(trim($_GET["id"])){
        // URL doesn't contain id parameter. Redirect to error page
        header("location: error.php");
        exit();
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h2>Delete Result</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="alert alert-danger" role="alert">
            <input type="hidden" name="id" value="<?php echo trim($_GET["id"]); ?>"/>
            <p>Are you sure you want to delete this result?</p><br>
            <p>
                <input type="submit" value="Yes" class="btn btn-danger">
                <a href="results_list.php" class="btn btn-default">No</a>
            </p>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>