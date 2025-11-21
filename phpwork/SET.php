<?php
// SET.php

// File to store expenses
$filename = 'expenses.txt';

// Initialize expenses array
$expenses = [];

// Load existing expenses from file
if (file_exists($filename)) {
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) === 3) {
            $expenses[] = [
                'date' => $parts[0],
                'category' => $parts[1],
                'amount' => (float)$parts[2]
            ];
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $category = $_POST['category'] ?? '';
    $amount = $_POST['amount'] ?? '';

    // Simple validation
    if ($date && $category && is_numeric($amount)) {
        $entry = "$date|$category|$amount\n";
        file_put_contents($filename, $entry, FILE_APPEND);
        // Reload page to show updated report
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Please fill all fields correctly.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Expense Tracker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 60%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #eee; }
        .error { color: red; }
    </style>
</head>
<body>
    <h2>Expense Entry</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
        <label>Date: <input type="date" name="date" required></label><br><br>
        <label>Category:
            <select name="category" required>
                <option value="">Select</option>
                <option value="Food">Food</option>
                <option value="Transport">Transport</option>
                <option value="Other">Other</option>
            </select>
        </label><br><br>
        <label>Amount: <input type="number" step="0.01" name="amount" required></label><br><br>
        <button type="submit">Add Expense</button>
    </form>

    <h2>Expense Report</h2>
    <?php if (count($expenses) === 0): ?>
        <p>No expenses recorded yet.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Amount</th>
            </tr>
            <?php
            $total = 0;
            $categoryTotals = [];
            foreach ($expenses as $exp) {
                echo "<tr>
                        <td>{$exp['date']}</td>
                        <td>{$exp['category']}</td>
                        <td>" . number_format($exp['amount'], 2) . "</td>
                      </tr>";
                $total += $exp['amount'];
                if (!isset($categoryTotals[$exp['category']])) {
                    $categoryTotals[$exp['category']] = 0;
                }
                $categoryTotals[$exp['category']] += $exp['amount'];
            }
            ?>
        </table>
        <p><strong>Total Spent Overall:</strong> <?php echo number_format($total, 2); ?></p>
        <h3>Total Spent Per Category:</h3>
        <ul>
            <?php foreach ($categoryTotals as $cat => $amt): ?>
                <li><?php echo htmlspecialchars($cat) . ": " . number_format($amt, 2); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>