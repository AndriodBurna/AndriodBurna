<?php include('./Partials/menue.php');?>

    <!-- fOOD sEARCH Section Starts Here -->
    <section class="food-search text-center">
        <div class="container">
            
            <h2>Foods on Your Search <a href="#" class="text-white">"<?= $_POST['search']; ?>"</a></h2>

        </div>
    </section>
    <!-- fOOD sEARCH Section Ends Here -->



    <!-- fOOD MEnu Section Starts Here -->
    <section class="food-menu">
        <div class="container">
            <h2 class="text-center">Food Menu</h2>

            <?php
            // get the search keyword
            $search = $_POST['search'];
            // creating the sql query
            $sql = "SELECT * FROM tbl_food WHERE title LIKE '%$search%' OR description LIKE '%$search%'";
            // excuting the query
            $res = mysqli_query($conn, $sql);
            
             // check the number of rows
             $count = mysqli_num_rows($res);

             // if rows are greater than zero, display the categories
             if($count > 0)
             {
                 while($row = mysqli_fetch_assoc($res))
                 {
                     $id = $row['id'];
                     $title = $row['title'];
                     $price = $row['price'];
                     $description = $row['description'];
                     $image_name = $row['image_name'];

                     // create a link for each category
                    ?>
                        <div class="food-menu-box">
                            <div class="food-menu-img">
                            <?php
                                if($image_name=="")
                                {
                                    echo "<div class='error'>Image Not Available</div>";
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

                                <a href="order.html" class="btn btn-primary">Order Now</a>
                            </div>
                        </div>
                    <?php
                 }
             }
             else
             {
                 echo "<div class='error'>No categories found.</div>";
             }
            
            
            
            ?>

            

           
            <div class="clearfix"></div>

            

        </div>

    </section>
    <!-- fOOD Menu Section Ends Here -->

   <?php include('./Partials/footer.php');?>