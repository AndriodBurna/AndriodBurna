<?php
function check_login() {
    if(strlen($_SESSION['std_id']) == 0) {
        $host = $_SERVER['HTTP_HOST'];
        $uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/' . DIRECTORY_SEPARATOR);
        $extra = 'login.php';
        $_SESSION["std_id"] = "";
        header("Location: http://$host$uri/$extra");
        exit();
    }
}
?>