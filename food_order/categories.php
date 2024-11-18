<?php include('./Partials/menue.php');?>



    <!-- CAtegories Section Starts Here -->
    <section class="categories">
        <div class="container">
            <h2 class="text-center">Explore Foods</h2>


            <?php
                // create a querry
                $sql = "SELECT * FROM tbl_category WHERE active='Yes'";
                // excute the query
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
                        $image_name = $row['image_name'];

                        // create a link for each category
                       ?>
                            <a href="<?php echo SETURL; ?>category-foods.php?category_id=<?php echo $id; ?>">
                                <div class="box-3 float-container">

                                <?php
                                if($image_name=="")
                                {
                                    echo "<div class='error'>Image Not Added</div>";
                                }
                                else
                                {
                                    ?>
                                     <img src="<?php echo SETURL; ?>images/Category/<?php echo $image_name; ?>" alt="Pizza" class="img-responsive img-curve">
                                    <?php
                                }
                                
                                
                                ?>
                                    

                                    <h3 class="float-text text-white"><?php echo $title; ?></h3>
                                </div>
                            </a>
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
    <!-- Categories Section Ends Here -->
<?php include('./Partials/footer.php');?>