<?php include('./connection/navbar.php');?>
    <div class="main-content">
        <div class="wrapper">
            <h1>Update Category</h1>
            <br><br>
            <?php
                // checking whether id is set or not
                if(isset($_GET['id']))
                {
                    // getting the id and all other details
                    // echo "Getting data";
                    $id = $_GET['id'];
                    // creating a SQL query to get data from table
                    $sql = "SELECT * FROM tbl_category WHERE id = $id";
                    // executing the query
                    $res = mysqli_query($conn, $sql);

                    // count the num of rows to check whether the id is vail or not
                    $count = mysqli_num_rows($res);

                    if($count==1)
                    {
                        // getting the category
                        $row = mysqli_fetch_assoc($res);
                        $title = $row['title'];
                        $current_image = $row['image_name'];
                        $feature = $row['feature'];
                        $active = $row['active'];

                    }
                    else
                    {
                        // redirect back to manage page with session
                        $_SESSION['no-category-food'] = '<div class="error">Category not found</div>';
                        header('location:'.SETURL.'Admin/manage_category.php');
                    }
                }
                else
                {
                    // redirect back to manange page
                    header('location:'.SETURL.'Admin/manage_category.php');
                }
            
            ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <table class="tbl_full">
                    <tr>
                        <td>Title: </td>
                        <td>
                            <input type="text" name="title" value="<?php echo $title;?>">
                        </td>
                    </tr>

                    <tr>
                        <td>Current Image: </td>
                        <td>
                            <?php
                                if($current_image != "")
                                {
                                    // Display image
                                    ?>
                                    <img src="<?php echo SETURL; ?>images/Category/<?php echo $current_image; ?>" width="150px">
                                    <?php
                                }
                                else
                                {
                                    // display message
                                    echo "<div class='error'>Failed to display current image</div>";
                                }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>New Image: </td>
                        <td>
                            <input type="file" name="image">
                        </td>
                    </tr>

                    <tr>
                        <td>Featured: </td>
                        <td>
                            <input <?php if($feature=="Yes"){echo "checked";}?> type="radio" name="feature" value="Yes">Yes

                            <input <?php if($feature=="No"){echo "checked";}?> type="radio" name="feature" value="No">No
                        </td>
                    </tr>
                    <tr>
                        <td>Active: </td>
                        <td>
                            <input <?php if($active=="Yes"){echo "checked";}?> type="radio" name="active" value="Yes">Yes

                            <input <?php if($active=="No"){echo "checked";}?> type="radio" name="active" value="No">No
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <input type="hidden" name="current_image" value="<?php echo $current_image; ?>">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="submit" name="submit" value="Update Category" class="btn-secondary">
                        </td>
                    </tr>
                </table>
            </form>
            <?php
                if(isset($_POST['submit']))
                {
                    //1 getting the value from the form
                    $id = $_POST['id'];
                    $title = $_POST['title'];
                    $current_image = $_POST['current_image'];
                    $feature = $_POST['feature'];
                    $active = $_POST['active'];
                    
                    // 2.uploading the new image
                    // Check whether the image is selected or not
                    if(isset($_FILES['image']['name']))
                    {
                        // getting the image details
                        $image_name = $_FILES['image']['name'];
                        // check whether the image is there or not
                        if($image_name !="")
                        {
                            // image available

                            // upload the new image
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
                            // remove the old image if available
                            if($current_image != "")
                            {
                                $remove_path = "../images/Category/".$current_image;
                                $remove = unlink($remove_path);
                                // check whether the image is removed or not
                                // if removed successfully , stop the process
                                if($remove==false)
                                {
                                    // failed to remove image
                                    $_SESSION['failed-remove'] = "<div class='error'>Failed to remove current image</div>";
                                    header('location:'.SETURL.'Admin/manage_category.php');
                                    // stop process
                                    die();
                                }
                            }
                            
                        }
                        else
                        {
                            $image_name = $current_image;
                        }
                    }
                    else
                    {
                        $image_name = $current_image;
                    }
                    // 3.updating the db
                    // writing the query
                    $sql2 = "UPDATE tbl_category SET
                    title = '$title',
                    image_name = '$image_name',
                    feature = '$feature',
                    active = '$active'
                    WHERE id = $id
                    ";
                    // excuting the query
                    $res2 = mysqli_query($conn,$sql2);
                    // 4.redirecting back to manage admin page
                    // checking whether the query is exucted or not
                    if($res2==true)
                    {
                        $_SESSION['update'] = "<div class='success'>Category Updated Successfully</div>";
                        header('location:'.SETURL.'Admin/manage_category.php');
                    }
                    else
                    {
                        $_SESSION['update'] = "<div class='error'>Failed to update Category</div>";
                        header('location:'.SETURL.'Admin/manage_category.php');
                    }
                }
            ?>
        </div>
    </div>




<?php include('./connection/footer.php');?>