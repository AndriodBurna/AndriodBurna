<?php
    // Authorization
    // Check whether the user is logged in or out
    if(!isset($_SESSION['user']))
    {
        // user is logged in or out
        // Redirect login page message
        $_SESSION['no-login-message'] = "<div class='error'>login to access the Admin page</div>";
        // Redirect to login page
        header('location:'.SETURL.'Admin/login.php');
    }



?>