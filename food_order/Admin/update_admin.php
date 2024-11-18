<?php include('./connection/navbar.php');?>
    <div class="main-content">
        <div class="wrapper">
            <h1>Update Admin</h1>
            <br><br>

            <?php
            
                //1.get id from the selected admin
                $id = $_GET["id"];
                // 2.create sql query to display the details
                $sql = "SELECT * FROM tbl_admin WHERE id = $id";
                // 2.excute the query
                $_REQUEST = mysqli_query($conn,$sql); 

                if($_REQUEST == TRUE)
                {
                    $_count = mysqli_num_rows($_REQUEST);
                    if($_count==1)
                    {
                        // echo "Admin available";
                        $rows = mysqli_fetch_assoc(($_REQUEST));

                        $Full_name = $rows['full_name'];
                        $User_name = $rows['user_name'];
                    }
                    else
                    {
                        header('location:'.SETURL.'Admin/manage_admin.php');
                    }
                }
            
            
            ?>




            <form action="" method="POST">


                <table class="tbl_admin">
                    <tr>
                        <th>Full Name:</th>
                        <td>
                            <input type="text" name="Full_name" value="<?php echo $Full_name;?>">
                        </td>
                    </tr>

                    <tr>
                        <th>User Name:</th>
                        <td>
                            <input type="text" name="User_name" value="<?php echo $User_name;?>">
                        </td>
                    </tr>

                   
                    <tr>
                        <td colspan="2">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="submit" name="submit" value="update Admin" class="btn-secondary">
                        </td>
                    </tr>
                </table>


            </form>

        </div>
    </div>

    <?php
        if(isset($_POST['submit']))
        {
            $id = $_POST['id'];
            $Full_name = $_POST['Full_name'];
            $User_name = $_POST['User_name'];


            $sql = "UPDATE tbl_admin SET
            Full_name = '$Full_name',
            User_name = '$User_name'
            WHERE id = '$id'
            
            ";

            $res = mysqli_query($conn,$sql);


            if($res == TRUE)
            {
                $_SESSION['update'] = "Admin added successfully";
                header("location:".SETURL."Admin/manage_admin.php");
            }
            else
            {
                $_SESSION['update'] = "Failed to delete admin. please try again later.";
                header("location:".SETURL."Admin/manage_admin.php");
            }

        }
    
    
    
    
    ?>




<?php include('./connection/footer.php');?>