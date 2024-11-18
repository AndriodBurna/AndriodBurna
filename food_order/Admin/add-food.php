<?php include('./connection/navbar.php');?>
    <div class="main-content">
        <div class="wrapper">
            <h1>Add Food</h1>

            <br><br>
            <?php
                if(isset($_SESSION['upload']))
                {
                    echo $_SESSION['upload'];
                    unset($_SESSION['upload']);
                }
            ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <table class="tbl_full">
                    <tr>
                        <td>title:</td>
                        <td>
                            <input type="text" name="title" placeholder="title of the food">
                        </td>
                    </tr>

                    <tr>
                        <td>Description:</td>
                        <td>
                            <textarea name="description" cols="20" rows="5" placeholder="Type here the description"></textarea>
                        </td>
                    </tr>

                    <tr>
                        <td>Price:</td>
                        <td>
                            <input type="number" name="price">
                        </td>
                    </tr>

                    <tr>
                        <td>Select Image:</td>
                        <td>
                            <input type="file" name="image">
                        </td>
                    </tr>

                    <tr>
                        <td>Category:</td>
                        <td>
                            <select name="category">

                                <!-- code for displaying the categories -->
                                 <?php
                                    // 1.sql query to get categories from category db
                                    $sql = "SELECT * FROM tbl_category WHERE active = 'Yes'";
                                    // excuting the query
                                    $res = mysqli_query($conn, $sql);
                                    // 2.counting the number of rows in the db
                                    $count = mysqli_num_rows($res);

                                    // if count is greater than zero, means we have categories else we dont
                                    if($count > 0)
                                    {
                                        // we have categories
                                        while($row = mysqli_fetch_array($res))
                                        {
                                            // getting the category details
                                            $id = $row['id'];
                                            $title = $row['title'];

                                            ?>
                                               <option value="<?php echo $id; ?>"><?php echo $title; ?></option> 
                                            <?php
                                        }
                                    }
                                    else
                                    {
                                        // we dont have categories
                                        ?>
                                            <option value="0">No Categories Found</option>
                                        <?php
                                    }
                                 ?>
                                
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Featured:</td>
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
                        <td cols="2">
                            <input type="submit" name="submit" value="Add Food" class="btn-secondary">
                        </td>
                    </tr>
                </table>
            </form>

            <!-- displaying our data in our db -->
            <?php
                // checking whether the button is clicked or not
                if(isset($_POST['submit']))
                {
                    // echo "clcked";
                    // Adding Food to the db
                    // 1.getting data from the form
                    $title = $_POST['title'];
                    $description = $_POST['description'];
                    $price = $_POST['price'];
                    $category = $_POST['category'];

                    // check whther the radio btn is checked or not
                    if(isset($_POST['feature']))
                    {
                        $feature = $_POST['feature'];
                    }
                    else
                    {
                        // set it to the default value
                        $feature = "No";
                    }

                    if(isset($_POST['active']))
                    {
                        $active = $_POST['active'];
                    }
                    else
                    {
                        // set it to the default value
                        $active = "No";
                    }
                    // 2.upload image
                    // check whether the selected image is clicked or not and only upload when selected
                    if(isset($_FILES['image']['name']))
                    {
                        // getting the details
                        $image_name = $_FILES['image']['name'];
                        // check whether the image is clicked or not
                       if($image_name != "");
                       {
                            // image details
                            // A rename image
                            $ext = end(explode('.', $image_name));
                            // creating a new name for the image
                            $image_name = "Food-Name-".rand(0000,9999).".".$ext;
                            // B upload image
                            // creating the src and destination path

                            // 1. creating the src path
                            $source_path = $_FILES['image']['tmp_name'];
                            // 2.creating the destination path
                            $destination_path = "../images/food/".$image_name;

                            //3. finally uploading the image
                            $upload = move_uploaded_file($source_path, $destination_path);

                            // check whether our image is uploaded or not
                            if($upload == false)
                            {
                                // failed to upload
                                // redirect back to add food page
                                $_SESSION['upload'] = '<div class="error">Failed to Upload the Image</div>';
                                header('Location:'.SETURL.'Admin/add-food.php');
                                // stop the process
                                die();
                            }
                       }
                    }
                    else
                    {
                        // setting the defualt value
                        $image_name = "";
                    }
                    // 4.insert into db
                    // create an sql query
                    $sql2 = "INSERT INTO tbl_food SET
                    title = '$title',
                    description = '$description',
                    price = $price,
                    image_name = '$image_name',
                    category_id = $category,
                    feature = '$feature',
                    active = '$active'
                    ";
                    // excuting the query
                    $res2 = mysqli_query($conn,$sql2);
                    // check whether the query is excuted or not
                    if($res == true)
                    {
                        // data added successfully
                        $_SESSION['add'] = "<div class='success'>Data added successfully to database</div>";
                        // redirect
                        header('Location:'.SETURL.'Admin/manage_food.php');
                    }
                    else
                    {
                        // data failed to be added
                        $_SESSION['add'] = "<div class='error'>Failed to add data to database</div>";
                        // redirect
                        header('Location:'.SETURL.'Admin/manage_food.php');
                    }
                }
            
            
            
            ?>
        </div>
    </div>


<?php include('./connection/footer.php');?>