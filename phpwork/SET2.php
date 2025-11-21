<?php

$file = "expenses2.txt";

$expenses = [];
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        list($date, $category, $amount) = explode(",", $line);
        $expenses[] = ["date" => $date, "category" => $category, "amount" => $amount];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];

    $expenses[] = ["date" => $date, "category" => $category, "amount" => $amount];

    $fp = fopen($file, "a");
    fwrite($fp, "$date,$category,$amount\n");
    fclose($fp);
}

$totalOverall = 0;
$categoryTotals = [];
foreach ($expenses as $expense) {
    $totalOverall += $expense['amount'];
    if (!isset($categoryTotals[$expense['category']])) {
        $categoryTotals[$expense['category']] = 0;
    }
    $categoryTotals[$expense['category']] += $expense['amount'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expense Tracker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; }
        .message { color: green; }
    </style>
</head>
<body>
    <h2>Record Daily Expense</h2>
    <?php if (isset($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post">
        Date: <input type="date" name="date" required><br><br>
        Category: 
        <select name="category" required>
            <option value="Food">Food</option>
            <option value="Transport">Transport</option>
            <option value="Other">Other</option>
        </select><br><br>
        Amount: <input type="number" name="amount" required><br><br>
        <input type="submit" value="Add Expense">
    </form>

    <h2>Expense Report</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Date</th>
            <th>Category</th>
            <th>Amount</th>
        </tr>
        <?php foreach ($expenses as $expense) { ?>
            <tr>
                <td><?php echo $expense['date']; ?></td>
                <td><?php echo $expense['category']; ?></td>
                <td><?php echo $expense['amount']; ?></td>
            </tr>
        <?php } ?>
    </table>

    <h3>Total Spent Overall: <?php echo $totalOverall; ?></h3>

    <h3>Total Spent Per Category</h3>
    <ul>
        <?php foreach ($categoryTotals as $category => $total) { ?>
            <li><?php echo $category . ": " . $total; ?></li>
        <?php } ?>
    </ul>
</body>
</html>
