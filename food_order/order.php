<?php include('./Partials/menue.php');?>

<?php
// check whetherthe food is set or not
if(isset($_GET['food_id']))
{
    // get the id and the details 
    $food_id = $_GET['food_id'];
    // create the query
    $sql = "SELECT * FROM tbl_food WHERE id = $food_id";
    // excuting the query
    $res = mysqli_query($conn,$sql);
    // count the number of rows
    $count = mysqli_num_rows($res);
    // check whether there is data in db
    if($count==1)
    {
        // we have data
        // get the data from the database
        $row = mysqli_fetch_assoc($res);

        $title = $row['title'];
        $price = $row['price'];
        $image_name = $row['image_name'];
    }
    else
    {
        // we dont have food in db
        // redirect back to home
        header('Location:'.SETURL);
    }
}
else
{
    // redirect back to the home page
    header('Location:'.SETURL);
}


?>

    <!-- fOOD sEARCH Section Starts Here -->
    <section class="food-search">
        <div class="container">
            
            <h2 class="text-center text-white">Fill this form to confirm your order.</h2>

            <form action="" method="POST" class="order">
                <fieldset>
                    <legend>Selected Food</legend>

                    <div class="food-menu-img">
                        <?php
                        // check whether the image is available or not
                        if($image_name=="")
                        {
                            // no image is available
                            echo "<div class='error'>No image available</div>";
                        }
                        else
                        {
                            ?>
                            <!-- writing the html code -->
                            <img src="<?php echo SETURL; ?>images/food/<?php echo $image_name; ?>" alt="Chicke Hawain Pizza" class="img-responsive img-curve">

                            <?php
                        }
                        ?>
                       
                    </div>
    
                    <div class="food-menu-desc">
                        <h3><?php echo $title; ?></h3>
                        <input type="hidden" name="food" value="<?php echo $title; ?>">
                        <p class="food-price">$<?php echo $price; ?></p>
                        <input type="hidden" name="price" value="<?php echo $price; ?>">

                        <div class="order-label">Quantity</div>
                        <input type="number" name="qty" class="input-responsive" value="1" required>
                        
                    </div>

                </fieldset>
                
                <fieldset>
                    <legend>Delivery Details</legend>
                    <div class="order-label">Full Name</div>
                    <input type="text" name="full-name" placeholder="Enter name here" class="input-responsive" required>

                    <div class="order-label">Phone Number</div>
                    <input type="tel" name="contact" placeholder="+256XXXXXXX" class="input-responsive" required>

                    <div class="order-label">Email</div>
                    <input type="email" name="email" placeholder="Enter email here" class="input-responsive" required>

                    <div class="order-label">Address</div>
                    <textarea name="address" rows="10" placeholder="E.g. Street, City, Country" class="input-responsive" required></textarea>

                    <input type="submit" name="submit" value="Confirm Order" class="btn btn-primary">
                </fieldset>

            </form>

            <?php
            if(isset($_POST['submit']))
            {
                // getting all the details from the form
                $food = $_POST['food'];
                $price = $_POST['price'];
                $qty = $_POST['qty'];

                $total = $price * $qty;

                $order_date = date('Y-m-d H:i:sa');

                $status = "ordered";
                $customer_name = $_POST['full-name'];
                $customer_contact = $_POST['contact'];
                $customer_email = $_POST['email'];
                $customer_address = $_POST['address'];

                // save data in db
                // 1.create the query
                $sql2 = "INSERT INTO tbl_order SET
                food = '$food',
                price = '$price',
                qty = '$qty',
                total = '$total',
                order_date = '$order_date',
                status = '$status',
                customer_name = '$customer_name',
                customer_contact = '$customer_contact',
                customer_email = '$customer_email',
                customer_address = '$customer_address'
                ";
                // 2.excuting the query
                $res2 = mysqli_query($conn,$sql2);
                // check whether the query is excuted or not
                if($res2==true)
                {
                    // food ordered
                    $_SESSION['order'] = "<div class='success'>Successful Order</div>";
                    // redirection
                    header('Location:'.SETURL);
                }
                else
                {
                    // food not ordered
                    $_SESSION['order'] = "<div class='error'>Failed to send Order</div>";
                    // redirection
                    header('Location:'.SETURL);
                }
            }
            
            
            ?>

        </div>
    </section>
    <!-- fOOD sEARCH Section Ends Here -->

   <?php include('./Partials/footer.php');?>