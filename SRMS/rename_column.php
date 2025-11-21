<?php
require_once 'config.php';

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

$sql = "ALTER TABLE students CHANGE photo profile_picture VARCHAR(255) DEFAULT NULL;";

if (mysqli_query($conn, $sql)) {
    echo "Column 'photo' renamed to 'profile_picture' successfully.";
} else {
    echo "ERROR: Could not execute $sql. " . mysqli_error($conn);
}

mysqli_close($conn);
?>