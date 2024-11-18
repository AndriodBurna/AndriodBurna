<?php include('./connection/navbar.php');?>

<div class="main-content">
    <div class="wrapper">
        <h1>Update Order</h1>
        <br><br>

        <?php 
        // check whether the id is set or not
        if(isset($_GET['id']))
        {
            // get the detail id
            $id = $_GET['id'];
            // get all the details based on the selected id
            // 1.create a query
            $sql = "SELECT * FROM tbl_order WHERE id =$id";
            // 2.excute the query
            $res = mysqli_query($conn, $sql);
            // count the number of rows
            $count = mysqli_num_rows($res);
            if ($count==1)
            {
                $row = mysqli_fetch_assoc($res);

                $food = $row['food'];
                $price = $row['price'];
                $qty = $row['qty'];
                $status = $row['status'];
                $customer_name = $row['customer_name'];
                $customer_contact = $row['customer_contact'];
                $customer_email = $row['customer_email'];
                $customer_address = $row['customer_address'];
            }
            else
            {
                header('Location:'.SETURL.'Admin/manage_order.php');
            }
        }
        else
        {
            // redirect
            header('Location:'.SETURL.'Admin/manage_order.php');
        }
        
        ?>

        <form action="" method="post">
            <table class="tbl_full">
                <tr>
                    <td>Food Name</td>
                    <td><b><?php echo $food; ?></b></td>
                </tr>
                <tr>
                    <td>Price</td>
                    <td><b>$<?php echo $price; ?></b></td>
                </tr>
                <tr>
                    <td>Qty</td>
                    <td>
                        <input type="number" name="qty" value="<?php echo $qty; ?>">
                    </td>
                </tr>

                <tr>
                    <td>Status</td>
                    <td>
                        <select name="status">
                            <option <?php if($status == "order") {echo "selected";} ?> value="order">Ordered</option>
                            <option <?php if($status == "on-delivery") {echo "selected";} ?> value="on-delivery">On-Delivery</option>
                            <option <?php if($status == "delivered") {echo "selected";} ?> value="delivered">Delivered</option>
                            <option <?php if($status == "cancelled") {echo "selected";} ?> value="cancelled">Cancelled</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td>Customer Name:</td>
                    <td>
                        <input type="text" name="customer_name" value="<?php echo $customer_name; ?>">
                    </td>
                </tr>

                <tr>
                    <td>Customer contact:</td>
                    <td>
                        <input type="text" name="customer_contact" value="<?php echo $customer_contact; ?>">
                    </td>
                </tr>
                <tr>
                    <td>Customer Email:</td>
                    <td>
                        <input type="text" name="customer_email" value="<?php echo $customer_email; ?>">
                    </td>
                </tr>
                <tr>
                    <td>Customer Address:</td>
                    <td>
                       <textarea name="customer_address" cols="30" rows="5"><?php echo $customer_address; ?></textarea>
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="price" value="<?php echo $price; ?>">
                        <input type="submit" name="submit" value="Update Order" class="btn-secondary">
                    </td>
                </tr>

            </table>
        </form>

        <?php
        if(isset($_POST['submit']))
        {
            // get all values from form
                $id = $_POST['id'];
                
                $price = $_POST['price'];
                $qty = $_POST['qty'];

                $total = $price * $qty;

                $status = $_POST['status'];

                $customer_name = $_POST['customer_name'];
                $customer_contact = $_POST['customer_contact'];
                $customer_email = $_POST['customer_email'];
                $customer_address = $_POST['customer_address'];

                // update the table
                $sql2 = "UPDATE tbl_order SET
                qty = $qty,
                total = $total,
                status = '$status',
                customer_name = '$customer_name',
                customer_contact = '$customer_contact',
                customer_email = '$customer_email',
                customer_address = '$customer_address'
                WHERE id=$id
                ";

                // excution
                $res2 = mysqli_query($conn,$sql2); 

                if($res2 == true)
                {
                    $_SESSION['update'] = '<div class="success">Successful Update</div>';
                    header('Locatin:'.SETURL.'Admin/manage_order.php');
                }
                else
                {
                    $_SESSION['update'] = '<div class="error">Failed Update</div>';
                    header('Locatin:'.SETURL.'Admin/manage_order.php');
                }


        }
        ?>
    </div>
</div>
<?php include('./connection/footer.php');?>