<?php
// Connect the constants page
include('../config/constants.php');
// destory the session
session_destroy();
// REdirect to login page
header('location:'.SETURL.'Admin/login.php');





?>