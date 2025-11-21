<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'bookdb';

$conn = mysqli_connect($host, $user, $pass, $db);

// Check the connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Add a new book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $year = $_POST['year'];

    if ($title && $author && $year) {
        $title = mysqli_real_escape_string($conn, $title);
        $author = mysqli_real_escape_string($conn, $author);
        $year = (int)$year;
        $sql = "INSERT INTO books (title, author, year) VALUES ('$title', '$author', $year)";
        mysqli_query($conn, $sql);
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM books WHERE id = $id";
    mysqli_query($conn, $sql);
}

$sql = "SELECT * FROM books ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Management System</title>
    <style>
        table { border-collapse: collapse; width: 60%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>Book Management System</h1>
    <form method="post">
        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="author" placeholder="Author" required>
        <input type="number" name="year" placeholder="Year" required>
        <button type="submit" name="add">Add Book</button>
    </form>
    <h2>Book List</h2>
    <table>
        <tr>
            <th>ID</th><th>Title</th><th>Author</th><th>Year</th><th>Action</th>
        </tr>
        <?php while ($book = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo htmlspecialchars($book['id']); ?></td>
            <td><?php echo htmlspecialchars($book['title']); ?></td>
            <td><?php echo htmlspecialchars($book['author']); ?></td>
            <td><?php echo htmlspecialchars($book['year']); ?></td>
            <td>
                <a href="?delete=<?php echo $book['id']; ?>" onclick="return confirm('Delete this book?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
