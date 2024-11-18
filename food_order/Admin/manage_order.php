<?php include("./connection/navbar.php");?>
     <!-- main content section starts here -->
     <div class="main-content">
     <div class="wrapper">
            <h1>Manage Order</h1>
            <br><br><br>

            <?php
            if(isset($_SESSION['update']))
            {
              echo $_SESSION['update'];
              unset($_SESSION['update']);
            }
            
            ?>


            <!-- <br><br>
            <a href="add-admin.php" class="btn-primary">Add Admin</a>
            <br><br> -->

            <table class="tbl_full">
               <tr>
                  <th>S.N.</th>
                  <th>Food</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Total</th>
                  <th>Order_date</th>
                  <th>Status</th>
                  <th>Customer-name</th>
                  <th>Contact</th>
                  <th>Email</th>
                  <th>Address</th>
                  <th>Actions</th>
               </tr> 
               <?php
                // get the data from the db
                $sql = "SELECT * FROM tbl_order ORDER BY id DESC";
                // excute the query
                $res = mysqli_query($conn,$sql);
                // count the number of rows
                $count = mysqli_num_rows($res);
                // assign serial number to increament it
                $sn = 1;
                // check if the query is excuted or not
                if($count>0)
                {
                  // order available
                  while($row = mysqli_fetch_array($res))
                  {
                    $id = $row['id'];
                    $food = $row['food'];
                    $price = $row['price'];
                    $qty = $row['qty'];
                    $total = $row['total'];
                    $order_date = $row['order_date'];
                    $status = $row['status'];
                    $customer_name = $row['customer_name'];
                    $customer_contact = $row['customer_contact'];
                    $customer_email = $row['customer_email'];
                    $customer_address = $row['customer_address'];
                    ?>
                       <tr>
                        <td><?php echo $sn++; ?>.</td>
                        <td><?php echo $food; ?></td>
                        <td><?php echo $price; ?></td>
                        <td><?php echo $qty; ?></td>
                        <td><?php echo $total; ?></td>
                        <td><?php echo $order_date; ?></td>

                        <td>
                        <?php
                        if($status=="ordered")
                        {
                          echo "<label>$status</label>";
                        }
                        elseif($status=="on-delivery")
                        {
                          echo "<label style='color: orange;'>$status</label>";
                        }
                        elseif($status=="delivered")
                        {
                          echo "<label style='color: green;'>$status</label>";
                        }
                        elseif($status=="cancelled")
                        {
                          echo "<label style='color: red;'>$status</label>";
                        }
                        ?>  

                        </td>

                        <td><?php echo $customer_name; ?></td>
                        <td><?php echo $customer_contact; ?></td>
                        <td><?php echo $customer_email; ?></td>
                        <td><?php echo $customer_address; ?></td>
                        <td>
                          <a href="<?php echo SETURL; ?>Admin/update-order.php?id=<?php echo $id; ?>" class="btn-secondary">Update Order</a>
                          <a href="#" class="btn-danger">Delete Order</a>
                        </td>
                      </tr>

                    <?php

                  }
                
                }
                else
                {
                  echo "<tr><td colspan='12' class='error'>Orders not available</td></tr>";
                }
               
               ?>

              
               
            </table>
           
            <div class="clearfix"></div>
        </div>
     </div>
    <!-- main content section ends here -->
<?php include("./connection/footer.php");?>