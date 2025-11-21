<?php
// File to store book records
$booksFile = 'books.txt';

// Load books from file
function loadBooks($booksFile) {
    if (!file_exists($booksFile)) return [];
    $lines = file($booksFile, FILE_IGNORE_NEW_LINES);
    $books = [];
    foreach ($lines as $line) {
        list($id, $title, $author, $status) = explode('|', $line);
        $books[$id] = ['id'=>$id, 'title'=>$title, 'author'=>$author, 'status'=>$status];
    }
    return $books;
}

// Save books to file
function saveBooks($books, $booksFile) {
    $lines = [];
    foreach ($books as $book) {
        $lines[] = implode('|', [$book['id'], $book['title'], $book['author'], $book['status']]);
    }
    file_put_contents($booksFile, implode("\n", $lines));
}

// Handle form submissions
$books = loadBooks($booksFile);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $id = trim($_POST['id']);
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        if ($id && $title && $author && !isset($books[$id])) {
            $books[$id] = ['id'=>$id, 'title'=>$title, 'author'=>$author, 'status'=>'Available'];
            saveBooks($books, $booksFile);
            $message = "Book added successfully.";
        } else {
            $message = "Invalid input or duplicate ID.";
        }
    }
    if (isset($_POST['borrow'])) {
        $id = $_POST['book_id'];
        if (isset($books[$id])) {
            if ($books[$id]['status'] === 'Available') {
                $books[$id]['status'] = 'Borrowed';
                saveBooks($books, $booksFile);
                $message = "Book borrowed.";
            } else {
                $message = "Book is already borrowed.";
            }
        }
    }
    if (isset($_POST['return'])) {
        $id = $_POST['book_id'];
        if (isset($books[$id])) {
            if ($books[$id]['status'] === 'Borrowed') {
                $books[$id]['status'] = 'Available';
                saveBooks($books, $booksFile);
                $message = "Book returned.";
            } else {
                $message = "Book is not borrowed.";
            }
        }
    }
    // Reload books after changes
    $books = loadBooks($booksFile);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Library System</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 60%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
        .msg { color: green; }
    </style>
</head>
<body>
    <h2>Library Menu</h2>
    <?php if ($message): ?><p class="msg"><?= htmlspecialchars($message) ?></p><?php endif; ?>

    <h3>a) Add New Book</h3>
    <form method="post">
        ID: <input type="text" name="id" required>
        Title: <input type="text" name="title" required>
        Author: <input type="text" name="author" required>
        <button type="submit" name="add">Add Book</button>
    </form>

    <h3>b) View All Available Books</h3>
    <table>
        <tr><th>ID</th><th>Title</th><th>Author</th><th>Status</th><th>Actions</th></tr>
        <?php foreach ($books as $book): ?>
        <tr>
            <td><?= htmlspecialchars($book['id']) ?></td>
            <td><?= htmlspecialchars($book['title']) ?></td>
            <td><?= htmlspecialchars($book['author']) ?></td>
            <td><?= $book['status'] ?></td>
            <td>
                <?php if ($book['status'] === 'Available'): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <button type="submit" name="borrow">Borrow</button>
                    </form>
                <?php else: ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <button type="submit" name="return">Return</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>