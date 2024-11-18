<?php include('./connection/navbar.php');?>
 <div class="main-content">
    <div class="wrapper">
        <h1>Add Category</h1>
        <br><br>

        <?php
            if(isset($_SESSION['add']))
            {
                echo $_SESSION['add'];
                unset($_SESSION['add']);
            }
            if(isset($_SESSION['upload']))
            {
                echo $_SESSION['upload'];
                unset($_SESSION['upload']);
            }
        
        
        ?><br><br>
        <!-- form starts here -->
         <form action="" method="POST" enctype="multipart/form-data">

         <table class="tbl_full">
            <tr>
                <td>Title:</td>
                <td>
                    <input type="text" name="title" placeholder="Category title">
                </td>
            </tr>
            <tr>
                <td>Select Image:</td>
                <td>
                    <input type="file" name="image">
                </td>
            </tr>
            <tr>
                <td>Feature:</td>
                <td>
                    <input type="radio" name="feature" value="Yes">Yes
                    <input type="radio" name="feature" value="No">No
                </td>
            </tr>
            <tr>
                <td>Active:</td>
                <td>
                    <input type="radio" name="active" value="Yes">Yes
                    <input type="radio" name="active" value="No">No
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <input type="submit" name="submit" value="Add category" class="btn-secondary">
                </td>
            </tr>

         </table>
         </form>
        <!-- form ends here -->

        <?php
        // Check whether the btn iis clicked
        if(isset($_POST['submit']))
        {
            // echo "clicked";
            // 1.getting the value from the form
            $title = $_POST['title'];
            // checking for the radio btn if they r clicked or not
            if(isset($_POST['feature']))
            {
                $feature = $_POST['feature'];

            }
            else
            {
                $feature = "No";
            }
            if(isset($_POST['active']))
            {
                $active = $_POST['active'];

            }
            else
            {
                $active = "No";
            }

            // Make the file and the image functioning
            // print_r($_FILES['image']);
            // die();
            if(isset($_FILES['image']['name']))
            {
                $image_name = $_FILES['image']['name'];

                // upload image if image name is selected
                if($image_name != "")
                {

               

                    $ext = end(explode('.', $image_name));

                    // rename the image
                    $image_name = "Food_Category_".rand(000,999).'.'.$ext;
                    
                    $source_path = $_FILES['image']['tmp_name'];

                    $destination_path = "../images/Category/".$image_name;
                    
                    // finally upload the image
                    $upload = move_uploaded_file($source_path, $destination_path);
                    // chheck whether the image is uploaded successfully
                    if ($upload == false)
                    {
                        $_SESSION['upload'] = "<div class='error'>Failed to upload image</div>";
                        // redirect the user back to category page
                        header('location:'.SETURL.'Admin/add-category.php');
                        // stop the progress 
                        die();

                    }
                }
                

            }
            else
            {
                $image_name ="";

            }

            $sql = "INSERT INTO tbl_category SET 
            title = '$title',
            image_name = '$image_name',
            feature = '$feature',
            active = '$active'
            ";
            $res = mysqli_query($conn,$sql);
            // checking whether the query is working 
            if($res == TRUE)
            {
                $_SESSION['add'] = "<div class='success'>Category added Successfully</div>";
                header('location:'.SETURL.'Admin/manage_category.php');  // redirect to the manage category page
            }
            else
            {
                $_SESSION['add'] = "<div class='error'>Failed to add Category</div>";
                header('location:'.SETURL.'Admin/add-category.php');  // redirect to the manage category page
            }
        }
        
        
        ?>
    </div>
 </div>


<?php include('./connection/footer.php');?>