<?php

$file = "books2.txt";

$books = [];
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        list($id, $title, $author, $status) = explode(",", $line);
        $books[$id] = ["title" => $title, "author" => $author, "status" => $status];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == "add") {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $author = $_POST['author'];
        if (!isset($books[$id])) {
            $books[$id] = ["title" => $title, "author" => $author, "status" => "Available"];
        }
    }

    if ($action == "borrow") {
        $id = $_POST['id'];
        if (isset($books[$id]) && $books[$id]['status'] == "Available") {
            $books[$id]['status'] = "Borrowed";
        }
    }

    if ($action == "return") {
        $id = $_POST['id'];
        if (isset($books[$id]) && $books[$id]['status'] == "Borrowed") {
            $books[$id]['status'] = "Available";
        }
    }

    $fp = fopen($file, "w");
    foreach ($books as $id => $book) {
        fwrite($fp, "$id,{$book['title']},{$book['author']},{$book['status']}\n");
    }
    fclose($fp);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Library Book Borrowing Tracker</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 60%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Library Menu</h2>

    <h3>Add New Book</h3>
    <form method="post">
        <input type="hidden" name="action" value="add">
        ID: <input type="text" name="id" required><br>
        Title: <input type="text" name="title" required><br>
        Author: <input type="text" name="author" required><br>
        <input type="submit" value="Add Book">
    </form>

    <h3>Borrow a Book</h3>
    <form method="post">
        <input type="hidden" name="action" value="borrow">
        Book ID: <input type="text" name="id" required>
        <input type="submit" value="Borrow">
    </form>

    <h3>Return a Book</h3>
    <form method="post">
        <input type="hidden" name="action" value="return">
        Book ID: <input type="text" name="id" required>
        <input type="submit" value="Return">
    </form>

    <h3>Available Books</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>Status</th>
        </tr>
        <?php foreach ($books as $id => $book) { ?>
            <tr>
                <td><?php echo $id; ?></td>
                <td><?php echo $book['title']; ?></td>
                <td><?php echo $book['author']; ?></td>
                <td><?php echo $book['status']; ?></td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
