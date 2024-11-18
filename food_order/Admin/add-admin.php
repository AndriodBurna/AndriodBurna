<?php include("./connection/navbar.php");?>

     <!-- main content section starts here -->
     <div class="main-content">
     <div class="wrapper">


                <?php 
                    if(isset($_SESSION['add']))
                    {
                    echo $_SESSION['add'];
                    unset($_SESSION['add']);
                    }
              
                ?>

            <h1>Add Admin</h1>
           <br><br>
            <form action="" method="post">
                <table class="tbl_admin">
                    <tr>
                        <th>Full Name:</th>
                        <td>
                            <input type="text" name="Full_name" placeholder="Enter your name">
                        </td>
                    </tr>

                    <tr>
                        <th>User Name:</th>
                        <td>
                            <input type="text" name="User_name" placeholder="Enter your user name">
                        </td>
                    </tr>

                    <tr>
                        <th>Password:</th>
                        <td>
                            <input type="password" name="password" placeholder="Enter your password">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" value="Add Admin" name="submit" class="btn-secondary">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
     </div>
    <!-- main content section ends here -->
<?php include("./connection/footer.php");?>

<?php
    // process to pick a value from form to be saved in the database
    // check the btn

    if(isset($_POST['submit']))
    {
        // button clicked
        // echo"Button clicked";

        // 1.get data from the form
        $Full_name = $_POST['Full_name'];
        $User_name = $_POST['User_name'];
        // password is encrypted using md5
        $password = md5($_POST['password']);
        // 2.save data into database
        $sql = "INSERT INTO tbl_admin SET
            Full_name = '$Full_name',
            User_name = '$User_name',
            password = '$password'

        
        ";

       

        // 3. Excuting data into our db table
       $_REQUEST = mysqli_query($conn, $sql) or die(mysqli_error());

        // checking whether is inserted
        if($_REQUEST == TRUE)
        {
            // echo"Data inserted";
            // creating the session
            $_SESSION['add'] = 'Admin added successfully';

            header("Location:".SETURL."Admin/manage_admin.php");
        }
        else
        {
            // echo"Data not inserted";
             // creating the session
             $_SESSION['add'] = 'Failed to add admin';

             header("Location:".SETURL."Admin/add-admin.php");
        }
        

    }
    



?>