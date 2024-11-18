<?php include("./connection/navbar.php");?>
     <!-- main content section starts here -->
     <div class="main-content">
     <div class="wrapper">
            <h1>Manage Category</h1>


            <br><br>

            <?php
              if(isset($_SESSION['add']))
              {
                  echo $_SESSION['add'];
                  unset($_SESSION['add']);
              }
              if(isset($_SESSION['remove']))
              {
                  echo $_SESSION['remove'];
                  unset($_SESSION['remove']);
              }
              if(isset($_SESSION['delete']))
              {
                  echo $_SESSION['delete'];
                  unset($_SESSION['delete']);
              }
              if(isset($_SESSION['no-category-food']))
              {
                  echo $_SESSION['no-category-food'];
                  unset($_SESSION['no-category-food']);
              }
              if(isset($_SESSION['update']))
              {
                  echo $_SESSION['update'];
                  unset($_SESSION['update']);
              }
              if(isset($_SESSION['upload']))
              {
                  echo $_SESSION['upload'];
                  unset($_SESSION['upload']);
              }
              if(isset($_SESSION['failed-remove']))
              {
                  echo $_SESSION['failed-remove'];
                  unset($_SESSION['failed-remove']);
              }
        
        
            ?><br><br>
            <a href="<?php echo SETURL;?>Admin/add-category.php" class="btn-primary">Add Category</a>
            <br><br>

            <table class="tbl_full">
               <tr>
                  <th>S.N.</th>
                  <th>title</th>
                  <th>Image</th>
                  <th>Feature</th>
                  <th>Active</th>
                  <th>Actions</th>
               </tr> 

               <?php
               // getting data from the db
                $sql = "SELECT * FROM tbl_category";
                
               //  excute the query
                $res = mysqli_query($conn,$sql);

                $count = mysqli_num_rows($res);
               //  create a serial number variable
               $sn = 1;

               //  check whether we have data in db or not
                if ($count>0)
                {
                  // we have data in db
                  // get data and display t
                  while ($row = mysqli_fetch_assoc($res))
                  {
                     $id = $row['id'];
                     $title = $row['title'];
                     $image_name = $row['image_name'];
                     $feature = $row['feature'];
                     $active = $row['active'];

                     ?>
                     <tr>
                        <td><?php echo $sn++;?>.</td>
                        <td><?php echo $title; ?></td>

                        <td>
                           <?php 
                              // check whether the image name is available
                              if($image_name!="")
                              {
                                 // display the image
                                 ?>
                                 <img src="<?php echo SETURL;?>images/Category/<?php echo $image_name;?>" width="100px" >
                                 <?php
                              }
                              else
                              {
                                 // display message
                                 echo "<div class='error'>Image not added.</div>";
                              }
                           
                           ?>
                        </td>

                        <td><?php echo $feature; ?></td>
                        <td><?php echo $active; ?></td>
                        <td>
                           <a href="<?php echo SETURL; ?>Admin/update-category.php?id=<?php echo $id;?>" class="btn-secondary">Update Category</a>
                           <a href="<?php echo SETURL; ?>Admin/delete-category.php?id=<?php echo $id;?>&image_name=<?php echo $image_name;?>" class="btn-danger">Delete Category</a>
                        </td>
                     </tr>

                     <?php
                  }
                  

                }
                else
                {
                  // we dont have data in db
                  // display the error message
                  ?>

                  <tr>
                     <td colspan="6"><div class="error">No Category Added</div></td>
                  </tr>

                  <?php
                }
               
               
               ?>

               
              
                
            </table>
           
            <div class="clearfix"></div>
        </div>
     </div>
    <!-- main content section ends here -->
<?php include("./connection/footer.php");?>