
<!DOCTYPE html>
<html>
<head>
    <title>AJAX Get Server Time</title>
    <script>
        function fetchServerTime() {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "getTime.php", true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("timeDisplay").innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
    </script>
</head>
<body>
    <button onclick="fetchServerTime()">Get Server Time</button>
    <div id="timeDisplay"></div>
</body>
</html>