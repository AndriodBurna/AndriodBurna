<?php include("./connection/navbar.php");?>
     <!-- main content section starts here -->
     <div class="main-content">
         <div class="wrapper">
            <h1>Manage Admin</h1>
            <br>

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
                
                if(isset($_SESSION['update']))
                {
                  echo $_SESSION['update'];
                  unset($_SESSION['update']);
                }
                if(isset($_SESSION['User-not-Found']))
                {
                  echo $_SESSION['User-not-Found'];
                  unset($_SESSION['User-not-Found']);
                }
                if(isset($_SESSION['Password-not-match']))
                {
                  echo $_SESSION['Password-not-match'];
                  unset($_SESSION['Password-not-match']);
                }
                if(isset($_SESSION['change-password'] ))
                {
                  echo $_SESSION['change-password'] ;
                  unset($_SESSION['change-password']);
                }
              
              ?><br><br>
             
            <a href="add-admin.php" class="btn-primary">Add Admin</a>
            <br><br>

            <table class="tbl_full">
               <tr>
                  <th>S.N.</th>
                  <th>Full Name</th>
                  <th>User Name</th>
                  <th>Actions</th>
               </tr> 


               <?php
                  // selecting data from our db to our page
                  $_sql = "SELECT * FROM tbl_admin";
                  // excuting the query
                  $_REQUEST = mysqli_query($conn,$_sql);

                  if($_REQUEST == TRUE)
                  {
                    $count = mysqli_num_rows($_REQUEST);

                    $num = 1;

                    if($count>0)
                    {
                      // we have data in db
                      while($rows = mysqli_fetch_assoc($_REQUEST))
                      {
                        $id = $rows['id'];
                        $Full_name = $rows['full_name'];
                        $User_name = $rows['user_name'];


                        ?>
                            <tr>
                                    <td><?php echo $num++; ?>.</td>
                                    <td><?php echo $Full_name; ?></td>
                                    <td> <?php echo $User_name; ?></td>
                                    <td>
                                      <a href="<?php echo SETURL;?>Admin/update_password.php?id=<?php echo $id;?>" class="btn-primary">Change Password</a>
                                      <a href="<?php echo SETURL;?>Admin/update_admin.php?id=<?php echo $id;?>" class="btn-secondary">Update Admin</a>
                                      <a href="<?php echo SETURL; ?>Admin/delete_admin.php?id=<?php echo $id;?>" class="btn-danger">Delete Admin</a>
                                    </td>
                            </tr>

                        <?php

                      }


                    }
                    else
                    {
                      // we dont have data in the db
                    }
                  }
               
               ?>

            </table>
            
            <!-- <div class="clearfix"></div> -->
         </div>
     </div>
    
    <!-- main content section ends here -->
<?php include("./connection/footer.php");?>