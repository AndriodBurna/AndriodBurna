<?php
$host = "localhost";
$user = "root"; // default user
$pass = "";     // default password for XAMPP
$dbname = "auth";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
