<?php include("./connection/navbar.php");?>
     <!-- main content section starts here -->
     <div class="main-content">
     <div class="wrapper">
            <h1>Manage Food</h1>


            <br><br>
            <a href="<?php echo SETURL; ?>Admin/add-food.php" class="btn-primary">Add Food</a>
            <br><br>

            <?php
                if(isset($_SESSION['add']))
                {
                    echo $_SESSION['add'];
                    unset($_SESSION['add']);
                }
                if(isset($_SESSION['delete']))
                {
                    echo $_SESSION['delete'];
                    unset($_SESSION['delete']);
                }
                if(isset($_SESSION['upload']))
                {
                    echo $_SESSION['upload'];
                    unset($_SESSION['upload']);
                }
                if(isset($_SESSION['unauthorise']))
                {
                    echo $_SESSION['unauthorise'];
                    unset($_SESSION['unauthorise']);
                }
                if(isset($_SESSION['update']))
                {
                    echo $_SESSION['update'];
                    unset($_SESSION['update']);
                }
            ?>

            <table class="tbl_full">
               <tr>
                  <th>S.N.</th>
                  <th>Title</th>
                  <th>Price</th>
                  <th>Image</th>
                  <th>Feature</th>
                  <th>Active</th>
                  <th>Actions</th>
               </tr> 

               <?php
                //  create query to gett all data from db for food
                $sql = "SELECT * FROM tbl_food";
                // excuting the query
                $res = mysqli_query($conn, $sql);
                // count the rows if food is available
                $count = mysqli_num_rows($res);
                // creating a serial number query by defualt is 1
                $sn = 1;
                 if($count >0)
                 {
                  // we have food in db
                  // get food and display from db
                  while($row = mysqli_fetch_assoc($res))
                  {
                    // get the values of an individual column
                    $id = $row['id'];
                    $title = $row['title'];
                    $price = $row['price'];
                    $image_name = $row['image_name'];
                    $feature = $row['feature'];
                    $active = $row['active'];
                    ?>
                      <tr>
                          <td><?php echo $sn++; ?>.</td>
                          <td><?php echo $title; ?></td>
                          <td>$<?php echo $price; ?></td>
                          <td>
                            <?php
                              // check whether we have image or not
                              if($image_name=="")
                              {
                                // no image to display
                                echo "<div class='error'>No Image is available.</div>";
                              }
                              else
                              {
                                // we have the image
                                ?>
                                <img src="<?php echo SETURL; ?>images/food/<?php echo $image_name; ?>" width="80px">
                                <?php
                              }
                            ?>
                          </td>
                          <td><?php echo $feature; ?></td>
                          <td><?php echo $active; ?></td>
                          <td>
                            <a href="<?php echo SETURL; ?>Admin/update-food.php?id=<?php echo $id; ?>" class="btn-secondary">Update Food</a>
                            <a href="<?php echo SETURL; ?>Admin/delete-food.php?id=<?php echo $id;?>&image_name=<?php echo $image_name;?>" class="btn-danger">Delete Food</a>
                          </td>
                      </tr>

                    <?php
                  }
                 }
                 else
                 {
                  // we lack food in db
                  echo "<tr><td cols='7' class='error'>Food not yet added</td></tr>";
                 }
               
               ?>

              
               
            </table>
           
            <div class="clearfix"></div>
        </div>
     </div>
    <!-- main content section ends here -->
<?php include("./connection/footer.php");?>