<?php


     // creating a session 
     session_start();


     //creating constants to store repeating values
     define('SETURL','http://localhost/food_order/');
     define('LOCALHOST', 'localhost');
     define('DB_USERNAME', 'root');
     define('DB_PASSWORD', '');
     define('DB_NAME', 'food_order');

     //3. Excuting the data to b save to db
     $conn = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD);
     $db_select = mysqli_select_db($conn, DB_NAME);

?>