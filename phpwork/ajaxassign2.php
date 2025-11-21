<?php
$valid_users = ['alice', 'bob', 'charlie'];
if (isset($_GET['check_username'])) {
    $username = $_GET['check_username'];
    if (in_array($username, $valid_users)) {
        echo 'exists';
    } else {
        echo 'not_exists';
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple AJAX Login</title>
    <script>
    function checkUsername() {
        var username = document.getElementById('username').value;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajaxassign2.php?check_username=' + encodeURIComponent(username), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var msg = document.getElementById('userMsg');
                if (xhr.responseText == 'exists') {
                    msg.textContent = "Username exists. You can login.";
                    msg.style.color = "green";
                    document.getElementById('loginBtn').disabled = false;
                } else {
                    msg.textContent = "Username does not exist.";
                    msg.style.color = "red";
                    document.getElementById('loginBtn').disabled = true;
                }
            }
        };
        xhr.send();
    }
    </script>
</head>
<body>
    <form onsubmit="return false;">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" onkeyup="checkUsername()" autocomplete="off" required>
        <span id="userMsg"></span><br><br>
        <button type="submit" id="loginBtn" disabled>Login</button>
    </form>
</body>
</html>