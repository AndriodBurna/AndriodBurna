<?php 
// include the constants
include('../config/constants.php');
// echo "deleted";
if(isset($_GET['id']) AND isset($_GET['image_name']))
{
    // process to delete
    // echo "Process to delete";
    // 1. Get the id and image_name from the form
    $id = $_GET['id'];
    $image_name = $_GET['image_name'];
    // 2.remove image if available
    // check whether the image is available or not
    if($image_name != "")
    {
        // get the image from ts folder
        // use path
        $path = "../images/food/".$image_name;
        // remove the image from folder
        $remove = unlink($path);
        // check whether the image is removed or not
        if($remove == false)
        {
            // failed to remove
            $_SESSION['upload'] = "<div class='error'>Failed to remove image.</div>";
            // redirect the process
            header('Location:'.SETURL.'Admin/manage_food.php');
            // stop the process
            die();
        }
    }
    // 3.delete food from db
    $sql = "DELETE FROM tbl_food WHERE id = $id";
    // excute query
    $res = mysqli_query($conn, $sql);
    // check whether the query is excuted or not
    // 4.redirect to manage page using session
    if($res == true)
    {
        // food deleted
        $_SESSION['delete'] = "<div class='success'>Food deleted Successfully.</div>";
        header('Location:'.SETURL.'Admin/manage_food.php');
    }
    else
    {
        // failed to delete food
        $_SESSION['delete'] = "<div class='error'>Failed to delete food.</div>";
        header('Location:'.SETURL.'Admin/manage_food.php');
    }
    
}
else
{
    // echo "redirect";
    // redirect to manage food page
    $_SESSION['unauthorise'] = "<div class='error'>Unauthorised access.</div>";
    header('Location:'.SETURL.'Admin/manage_food.php');
}

?>