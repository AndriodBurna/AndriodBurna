<?php
// Start session to maintain data across requests
session_start();

// Initialize expenses array if not exists
if (!isset($_SESSION['expenses'])) {
    $_SESSION['expenses'] = array();
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_expense'])) {
        // Add new expense
        $date = $_POST['date'];
        $category = $_POST['category'];
        $amount = (float)$_POST['amount'];
        
        // Validate inputs
        if (!empty($date) && !empty($category) && $amount > 0) {
            $expense = array(
                'date' => $date,
                'category' => $category,
                'amount' => $amount
            );
            $_SESSION['expenses'][] = $expense;
            
            // Also save to text file
            $file_data = $date . "," . $category . "," . $amount . "\n";
            file_put_contents('expenses.txt', $file_data, FILE_APPEND);
            
            $message = "Expense added successfully!";
        } else {
            $error = "Please fill all fields correctly!";
        }
    }
    
    if (isset($_POST['clear_all'])) {
        // Clear all expenses
        $_SESSION['expenses'] = array();
        if (file_exists('expenses.txt')) {
            unlink('expenses.txt');
        }
        $message = "All expenses cleared!";
    }
}

// Calculate totals
$total_overall = 0;
$category_totals = array();

foreach ($_SESSION['expenses'] as $expense) {
    $total_overall += $expense['amount'];
    
    if (isset($category_totals[$expense['category']])) {
        $category_totals[$expense['category']] += $expense['amount'];
    } else {
        $category_totals[$expense['category']] = $expense['amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Expense Tracker</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 200px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        button:hover { background-color: #0056b3; }
        .clear-btn { background-color: #dc3545; }
        .clear-btn:hover { background-color: #c82333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .summary { background-color: #e9f4ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .message { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>💰 Daily Expense Tracker</h1>
    
    <?php if (isset($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Expense Entry Form -->
    <h2>Add New Expense</h2>
    <form method="POST">
        <div class="form-group">
            <label for="date">Date:</label>
            <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="">Select Category</option>
                <option value="Food">Food</option>
                <option value="Transport">Transport</option>
                <option value="Shopping">Shopping</option>
                <option value="Bills">Bills</option>
                <option value="Entertainment">Entertainment</option>
                <option value="Healthcare">Healthcare</option>
                <option value="Other">Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="amount">Amount ($):</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
        </div>
        
        <button type="submit" name="add_expense">Add Expense</button>
        <button type="submit" name="clear_all" class="clear-btn" onclick="return confirm('Are you sure you want to clear all expenses?')">Clear All</button>
    </form>

    <!-- Summary Section -->
    <?php if (count($_SESSION['expenses']) > 0): ?>
    <div class="summary">
        <h2>📊 Expense Summary</h2>
        <p><strong>Total Overall Spending: $<?php echo number_format($total_overall, 2); ?></strong></p>
        
        <h3>Spending by Category:</h3>
        <?php foreach ($category_totals as $category => $total): ?>
            <p><?php echo $category; ?>: $<?php echo number_format($total, 2); ?></p>
        <?php endforeach; ?>
    </div>

    <!-- All Expenses Table -->
    <h2>📋 All Expenses</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['expenses'] as $expense): ?>
            <tr>
                <td><?php echo $expense['date']; ?></td>
                <td><?php echo $expense['category']; ?></td>
                <td>$<?php echo number_format($expense['amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No expenses recorded yet. Add your first expense above!</p>
    <?php endif; ?>

    <hr style="margin-top: 30px;">
    <p><small>💡 <strong>Tip:</strong> Your expenses are saved in both session memory and a text file called 'expenses.txt'</small></p>
</body>
</html>