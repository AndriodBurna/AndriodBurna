<?php include('./connection/navbar.php'); ?>

<div class="main-content">
    <div class="wrapper">
        <h1>Change password</h1>
        <br><br>
        <?php
        if(isset($_GET['id']))
        {
            $id = $_GET['id'];
        }
        
        ?>

        <form action="" method="post">
            <table class="tbl_full">
                <tr>
                    <td>Old Password: </td>
                    <td>
                        <input type="password" name="old_password" placeholder="old password">
                    </td>
                </tr>

                <tr>
                    <td>New Password: </td>
                    <td>
                        <input type="password" name="new_password" placeholder="new password">
                    </td>
                </tr>

                <tr>
                    <td>Comfirm Password: </td>
                    <td>
                        <input type="password" name="comfirm_password" placeholder="comfirm password">
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="submit" name="submit" value="change password" class="btn-secondary">
                    </td>
                </tr>
            </table>

        </form>

    </div>
</div>

<?php
    if(isset($_POST['submit']))
    {
        // echo 'btn clicked';
        // 1.getting data from the form
        $id = $_POST['id'];
        $old_password = md5($_POST['old_password']);
        $new_password = md5($_POST['new_password']);
        $comfirm_password = md5($_POST['comfirm_password']);
        // var_dump($_POST['id']); exit();
        // 2.check whether current id and current password are the same
        $sql = "SELECT * FROM tbl_admin WHERE id = $id AND password = '$old_password'";
        // excuting the query
        $res = mysqli_query($conn,$sql);

        if($res == TRUE)
        {
            $count = mysqli_num_rows($res);

            if($count == 1)
            {
                // echo "User Found";
                // Check whether new password and comfirm password match
                if($new_password == $comfirm_password)
                {
                    $sql2 = "UPDATE tbl_admin SET
                        password = '$new_password'
                        WHERE id = $id
                    ";
                    // excute error
                    $res2 = mysqli_query($conn,$sql2);
                    // check whether query is excuted or not
                    if($res2 == TRUE)
                    {
                        // display the query
                        $_SESSION['change-password'] = "<div class='success'>Password changed Successfully.</div>";
                        header('location:'.SETURL.'Admin/manage_admin.php');
                    }
                    else
                    {
                        $_SESSION['change-password'] = "<div class='error'>Failed to change password.</div>";
                        header('location:'.SETURL.'Admin/manage_admin.php');
                    }

                }
                else
                {
                    $_SESSION['Password-not-match'] = "<div class='error'>Password didnot match.</div>";
                    header('location:'.SETURL.'Admin/manage_admin.php');
                }
            }
            else
            {
                $_SESSION['User-not-Found'] = "<div class='error'>User not Found.</div>";
                header('location:'.SETURL.'Admin/manage_admin.php');
            }
        }
    }






?>






<?php include('./connection/footer.php');?>