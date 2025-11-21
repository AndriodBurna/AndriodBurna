Users.php
<?php

include 'config.php';
include 'functions.php';

// Fetch users from the Database
$sql = "SELECT * FROM users";
$result = $db->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_array()) {
        echo json_encode($row);
    }
} else {
    return json_encode(["message" => "0 results"]);
}
$db->close();
?>