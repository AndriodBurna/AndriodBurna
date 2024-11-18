<?php
ob_start(); // Start output buffering
include('../Admin/connection/navbar.php');
?>

<?php
// Check whether the id is set or not
if (isset($_GET['id'])) {
    // Get details
    $id = $_GET['id'];
    // Query to get the specific food item
    $sql2 = "SELECT * FROM tbl_food WHERE id = $id";
    $res2 = mysqli_query($conn, $sql2);
    $row2 = mysqli_fetch_array($res2);

    // Assign values if row is found
    $title = $row2['title'];
    $description = $row2['description'];
    $price = $row2['price'];
    $current_image = $row2['image_name'];
    $current_category = $row2['category_id'];
    $feature = $row2['feature'];
    $active = $row2['active'];
} else {
    // Redirect if id is not set
    header('Location:' . SETURL . 'Admin/manage_food.php');
    exit();
}
?>

<div class="main-content">
    <div class="wrapper">
        <h1>Update Food</h1>
        <br><br>

        <form action="" method="POST" enctype="multipart/form-data">
            <table class="tbl_full">
                <tr>
                    <td>Title:</td>
                    <td>
                        <input type="text" name="title" value="<?php echo $title; ?>">
                    </td>

                </tr>

                <tr>
                    <td>Description:</td>
                    <td>
                        <textarea name="description" cols="20" rows="5"><?php echo $description; ?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>Price:</td>
                    <td>
                        <input type="number" name="price" value="<?php echo $price; ?>">
                    </td>
                </tr>

                <tr>
                    <td>Current Image:</td>
                    <td>
                        <?php
                        if ($current_image == "") {
                            echo "<div class='error'>No image Found</div>";
                        } else {
                        ?>
                            <img src="<?php echo SETURL; ?>images/food/<?php echo $current_image; ?>" width="100px">
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Select New Image:</td>
                    <td>
                        <input type="file" name="image">
                    </td>
                </tr>

                <tr>
                    <td>Category</td>
                    <td>
                        <select name="category">
                            <?php
                            $sql = "SELECT * FROM tbl_category WHERE active = 'Yes'";
                            $res = mysqli_query($conn, $sql);
                            $count = mysqli_num_rows($res);

                            if ($count > 0) {
                                while ($row = mysqli_fetch_assoc($res)) {
                                    $category_id = $row['id'];
                                    $category_title = $row['title'];
                                    echo "<option " . ($current_category == $category_id ? "selected" : "") . " value='$category_id'>$category_title</option>";
                                }
                            } else {
                                echo "<option value='0'>No Categories Found</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                    <tr>
                        <td>Featured:</td>
                        <td>
                            <input <?php if($feature == "Yes") { echo "Checked";} ?> type="radio" name="feature" value="Yes">Yes
                            <input <?php if($feature == "No") { echo "Checked";} ?> type="radio" name="feature" value="No">No
                        </td>
                    </tr>

                    <tr>
                        <td>Active:</td>
                        <td>
                            <input <?php if($active == "Yes") { echo "Checked";} ?>  type="radio" name="active" value="Yes">Yes
                            <input <?php if($active == "No") { echo "Checked";} ?>  type="radio" name="active" value="No">No
                        </td>
                    </tr>


                <!-- Additional form fields here -->

                <tr>
                    <td>
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="current_image" value="<?php echo $current_image; ?>">
                        <input type="submit" name="submit" value="Update Food" class="btn-secondary">
                    </td>
                </tr>
            </table>
        </form>

        <?php
        if (isset($_POST['submit'])) {
            // echo "clicked";
            // 1.get all details from the form
            $id = $_POST['id'];
            $title = $_POST['title'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $current_image = $_POST['current_image'];
            $category = $_POST['category'];

            $feature = $_POST['feature'];
            $active = $_POST['active'];
            // 2.upload image if selected
            // check whether the submit btn is c;licked or not
            if (isset($_FILES['image']['name'])) {
                // upload if clicked
                $image_name = $_FILES['image']['name'];
                // check whether file name is available
                if ($image_name != "") {
                    // image details
                    // A. uploading new image
                    // rename image
                    $ext = end(explode('.', $image_name)); //gets the extension of the image

                    $image_name = "Food-Name-" . rand(0000, 9999) . "." . $ext; //this is the renamed image

                    // getting the source path 
                    $src_path = $_FILES['image']['tmp_name'];

                    // destination path of the image
                    $dest_path = "../images/food/" . $image_name;

                    $upload = move_uploaded_file($src_path, $dest_path);

                    // check whethe the upload is true or false
                    if ($upload == false) {
                        // session
                        $_SESSION['upload'] = "<div class='error'>Failed to upload new image</div>";
                        // redirection
                        header('Location:' . SETURL . 'Admin/manage_food.php');
                        // stop the process
                        die();
                    }
                    // B. removing current image if available
                    if ($current_image !== "") {
                        // image available
                        // remove the path
                        $remove_path = "../images/food/" . $current_image;

                        $remove = unlink($remove_path);

                        // check whether the current image is removed 
                        if ($remove == false) {
                            // failed 
                            $_SESSION['remove-failed'] = "<div class='error'>Failed to remove</div>";
                            // redirection
                            header('Location:' . SETURL . 'Admin/manage_food.php');
                            // stop the process
                            die();
                        }
                    }
                }
                else
                {
                    $image_name = $current_image;
                }
            } else {
                $image_name = $current_image;
            }

            $sql3 = "UPDATE tbl_food SET
                title = '$title',
                description = '$description',
                price = $price,
                image_name = '$image_name',
                category_id = '$category',
                feature = '$feature',
                active = '$active'
                WHERE id = $id";

            $res3 = mysqli_query($conn, $sql3);

            if ($res3) {
                $_SESSION['update'] = "<div class='success'>Food Updated Successfully.</div>";
            } else {
                $_SESSION['update'] = "<div class='error'>Failed to update food.</div>";
            }
            header('Location:' . SETURL . 'Admin/manage_food.php');
            exit();
        }
        ?>
    </div>
</div>

<?php
include('../Admin/connection/footer.php');
ob_end_flush(); 
// End output buffering