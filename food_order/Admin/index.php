<?php include("./connection/navbar.php");?>
    <!-- main content section starts here -->
     <div class="main-content">
     <div class="wrapper">
            <h1>Dashboard</h1>
            <br><br>
            <?php
                if(isset($_SESSION['login']))
                {
                echo $_SESSION['login'];
                unset($_SESSION['login']);
                }
            ?>
            <br><br>
            <div class="col-4">
                <?php
                // create a query
                $sql = "SELECT * FROM tbl_category";
                // excute the query
                $res = mysqli_query($conn, $sql);
                // count the number of categories
                $count = mysqli_num_rows($res);
                ?>
                <h1><?php echo $count; ?></h1>
                Categories
            </div>
            <div class="col-4">

            <?php
                // create a query
                $sql2 = "SELECT * FROM tbl_food";
                // excute the query
                $res2 = mysqli_query($conn, $sql2);
                // count the number of categories
                $count2 = mysqli_num_rows($res2);
                ?>
                <h1><?php echo $count2; ?></h1>
                Foods
            </div>
            <div class="col-4">

            <?php
                // create a query
                $sql3 = "SELECT * FROM tbl_order";
                // excute the query
                $res3 = mysqli_query($conn, $sql3);
                // count the number of categories
                $count3 = mysqli_num_rows($res3);
                ?>
                <h1><?php echo $count3; ?></h1>
                Total Orders
            </div>
            <div class="col-4">
                <!-- create a quuery  -->
                <?php
                $sql4 = "SELECT SUM(total) AS total FROM tbl_order WHERE status ='Delivered'";
                // excute t
                $res4 = mysqli_query($conn,$sql4);
                // get the value
                $row4 = mysqli_fetch_assoc($res4);
                // get the total revenue
                $total_revenue = $row4['total'];
                ?>
                <h1>$<?php echo $total_revenue; ?></h1>
                Revenue Generated
            </div>
            <div class="clearfix"></div>
        </div>
     </div>
    <!-- main content section ends here -->
<?php include("./connection/footer.php");?>