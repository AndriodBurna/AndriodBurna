
<!DOCTYPE html>
<html>
<head>
    <title>AJAX Search Bar Example</title>
    <script>
        function searchQuery(str) {
            if (str.length == 0) {
                document.getElementById("results").innerHTML = "";
                return;
            }
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("results").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET", "search.php?q=" + encodeURIComponent(str), true);
            xmlhttp.send();
        }
    </script>
</head>
<body>
    <h2>AJAX Search Bar</h2>
    <input type="text" onkeyup="searchQuery(this.value)" placeholder="Type to search...">
    <div id="results"></div>
</body>
</html>