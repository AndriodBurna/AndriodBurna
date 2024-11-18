<?php include('../config/constants.php');?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./css/admin.css">
</head>
<body>
    <div class="login">
        <h1 class="text-cont">Login</h1>
        <!-- login form starts here -->

        <?php
         if(isset($_SESSION['login']))
         {
           echo $_SESSION['login'];
           unset($_SESSION['login']);
         }
         if(isset($_SESSION['no-login-message']))
         {
            echo $_SESSION['no-login-message'];
            unset($_SESSION['no-login-message']);
         }
        
        
        ?>
         <br>
         <form action="" method="POST" class="text-cont">
            Username:
            <input type="text" name="user_name" placeholder="Enter username"><br><br>
            Password:
            <input type="password" name="password" placeholder="Enter your password"><br><br>

            <input type="submit" name="submit" value="Login" class="btn-primary"><br><br>
         </form>
        <!-- login form ends here -->
        <p class="text-cont">Created By <a href="#">Kalibbala Emmanuel</a></p>
    </div>
</body>
</html>


<?php
    // Chech whether the submit button works or not
    if(isset($_POST['submit']))
    {
        // Process for login
        // 1.getting data frrom the form
        $user_name = $_POST['user_name'];
        $password = md5($_POST['password']);

        // 2.Check whether the user with the username and password exist
        $sql = "SELECT * FROM tbl_admin WHERE user_name= '$user_name' AND password = '$password'";


        // 3.Excute the query
        $res = mysqli_query($conn,$sql);

        // 4.Checking the number of rows
        $count = mysqli_num_rows($res);

        if($count==1)
        {
            // user found
            $_SESSION['login'] = "<div class='success'>Logged in Successfully.</div>";
            $_SESSION['user'] = $user_name;

            header('location:'.SETURL.'Admin/index.php');
        }
        else
        {
            // user not found
            $_SESSION['login'] = "<div class='error'>Failed to login.</div>";
            header('location:'.SETURL.'Admin/login.php');
        }
    }





?>