
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>


    <button type="button" id="fetchUser">Fetch User</button>
    <div id="userData"></div>

    <script>
        setInterval(refresh, 5000);
        // document.getElementById("fetchUser").addEventListener("click", refresh);

        function refresh() {
            let xhr = new XMLHttpRequest();
            xhr.open("GET", "user.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");
            // Display the response in body
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("userData").innerHTML = xhr.responseText;

                }
            };

            xhr.send();
        }
    </script>
</body>

</html>