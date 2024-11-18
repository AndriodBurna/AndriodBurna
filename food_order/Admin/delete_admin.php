<?php 
    // Include the conn constant in our delete php page
    include('../config/constants.php');


    // 1.Get the id of an admin to be deleted
    echo $id = $_GET['id'];
    // 2. create a SQL query to delete admin from db
    $sql ="DELETE FROM tbl_admin WHERE id = $id";

    // Excute the query
    $_REQUEST = mysqli_query($conn,$sql);

    // check whether the query is excuted or not
    if($_REQUEST == TRUE)
    {
        // Admin is DEleted
        // echo "Admin deleted";
        // creating session variable to display message
        $_SESSION['delete'] = "<div class='success'>Admin deleted successfully.</div>";
        // redirect to admin page
        header('location:' .SETURL.'Admin/manage_admin.php'); 
    }
    else
    {
        // Failed to delete admin
        // echo "Failed to delete admin";

        $_SESSION['delete'] = "<div class='error'>Failed to delete admin. Try again later.</div>";
        header('location:' .SETURL.'Admin/manage_admin.php'); 
        
    }




?>