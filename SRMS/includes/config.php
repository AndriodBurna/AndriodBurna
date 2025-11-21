<?php

// Database connection parameters
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'srms_db');

// Attempt to connect to MySQL database
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

// Function to calculate overall average (placeholder - needs actual implementation)
function get_overall_average($student_id, $mysqli) {
    // This is a placeholder. You'll need to implement the actual logic
    // to fetch and calculate the overall average from your results table.
    // For now, it returns a dummy value.
    return 85.5; 
}

?>