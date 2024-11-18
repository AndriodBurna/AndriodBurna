<?php

    // include the constants
    include('../config/constants.php');

    //   echo "deleted";
    // checking whether the id and image_name value is set or not
    if(isset($_GET['id']) && isset($_GET['image_name']))
    {
        // get the value and delete id
        // echo ' deleted';
        $id = $_GET['id'];
        $image_name = $_GET['image_name'];

        // remove the physical image
        if($image_name!="")
        {
            $path = "../images/Category".$image_name;
            // remove ima
            $remove = unlink($path);

            // if failed to remove image the display error and stop the process
            if($remove == false)
            {
                // createb the error session message
                $_SESSION['remove'] = '<div class="error">Failed to remove image</div>';
                // redirect the message
                header('location:'.SETURL.'Admin/manage_category.php');
                // stop the process  
                die();
            }

        }
        // SQL query to delete
        $sql = "DELETE FROM tbl_category WHERE id = $id";

        // excute the query
        $res = mysqli_query($conn, $sql);
        // check whether the query is true of false
        if($res==true)
        {
            $_SESSION['delete'] = '<div class="success">category deleted successfully</div>';
            // redirect bac to
            header('location:'.SETURL. 'Admin/manage_category.php');
        }
        else
        {
            $_SESSION['delete'] = '<div class="error">Failed to delete category</div>';
            // redirect bac to
            header('location:'.SETURL. 'Admin/manage_category.php');
        }
    } 
    else
    {
        // redirect to manage page
        header('location:'.SETURL.'Admin/manage_category.php');
    }
?>