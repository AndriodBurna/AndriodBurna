<?php include('./Partials/menue.php');?>


<?php
// check whethear the id is passed or not
if(isset($_GET['category_id']))
{
    // id is passed
    $category_id = $_GET['category_id'];
    // get the category title
    $sql = "SELECT title FROM tbl_category WHERE id = $category_id";
    // excuting the query
    $res = mysqli_query($conn, $sql);
    // get values from db
    $row = mysqli_fetch_assoc($res);
    // get the db title
    $category_title = $row['title'];
}
else
{
    // redirect to home page
    header('Location:'.SETURL);
}



?>

    <!-- fOOD sEARCH Section Starts Here -->
    <section class="food-search text-center">
        <div class="container">
            
            <h2>Foods on <a href="#" class="text-white">"<?php echo $category_title; ?>"</a></h2>

        </div>
    </section>
    <!-- fOOD sEARCH Section Ends Here -->



    <!-- fOOD MEnu Section Starts Here -->
    <section class="food-menu">
        <div class="container">
            <h2 class="text-center">Food Menu</h2>

            <?php
            // create a query to pick data from db
            $sql2 = "SELECT * FROM tbl_food WHERE category_id=$category_id";
            // excuting it
            $res2 = mysqli_query($conn,$sql2);
            // count the number of rows
            $count2 = mysqli_num_rows($res2);
            // checking whether food is available or not
            if($count2 > 0)
            {
                // food available
                while($row2 = mysqli_fetch_assoc($res2))
                {
                    // we need to get title,image,price,description
                    $title = $row2['title'];
                    $price = $row2['price'];
                    $description = $row2['description'];
                    $image_name = $row2['image_name'];
                    ?>
                    <!-- inserting the html -->
                    <div class="food-menu-box">
                        <div class="food-menu-img">
                            <!-- check whether the image is available or not -->
                             <?php
                             if($image_name =="")
                             {
                                echo "<div class='error'>Image not available</div>";
                             }
                             else
                             {
                                ?>
                                <img src="<?php echo SETURL; ?>images/food/<?php echo $image_name; ?>" alt="Chicke Hawain Pizza" class="img-responsive img-curve">
                                <?php
                             }
                             
                             
                             ?>
                            
                        </div>

                        <div class="food-menu-desc">
                            <h4><?php echo $title; ?></h4>
                            <p class="food-price">$<?php echo $price; ?></p>
                            <p class="food-detail">
                                <?php echo $description; ?>
                            </p>
                            <br>

                            <a href="<?php echo SETURL; ?>order.php?food_id=<?php echo $id; ?>" class="btn btn-primary">Order Now</a>
                        </div>
                    </div>


                    <?php
                }
            }
            else
            {
                // food not available
                echo "<div class='error'>food not available</div>";
            }
            
            ?>


            <div class="clearfix"></div>

            

        </div>

    </section>
    <!-- fOOD Menu Section Ends Here -->

<?php include('./Partials/footer.php');?>