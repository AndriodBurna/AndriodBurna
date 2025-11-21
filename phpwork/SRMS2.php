<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendence_management_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$results = [];
$message = "";
$error = "";

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM srms WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Record deleted successfully!";
    } else {
        $error = "Error deleting record: " . $stmt->error;
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Update Request
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $subject1 = intval($_POST['subject1']);
    $subject2 = intval($_POST['subject2']);
    $subject3 = intval($_POST['subject3']);

    $total = $subject1 + $subject2 + $subject3;
    $average = $total / 3;

    if ($average >= 80) {
        $grade = "A";
    } elseif ($average >= 70) {
        $grade = "B";
    } elseif ($average >= 60) {
        $grade = "C";
    } elseif ($average >= 50) {
        $grade = "D";
    } else {
        $grade = "F";
    }

    $sql = "UPDATE srms SET name=?, subject1=?, subject2=?, subject3=?, total=?, average=?, grade=?, updated_at=CURRENT_TIMESTAMP WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siiidsi", $name, $subject1, $subject2, $subject3, $total, $average, $grade, $id);
    
    if ($stmt->execute()) {
        $message = "Record updated successfully!";
    } else {
        $error = "Error updating record: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Insert Request
if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $subject1 = intval($_POST['subject1']);
    $subject2 = intval($_POST['subject2']);
    $subject3 = intval($_POST['subject3']);

    // Validation
    if (empty($name)) {
        $error = "Name is required!";
    } elseif ($subject1 < 0 || $subject1 > 100 || $subject2 < 0 || $subject2 > 100 || $subject3 < 0 || $subject3 > 100) {
        $error = "Marks must be between 0 and 100!";
    } else {
        $total = $subject1 + $subject2 + $subject3;
        $average = $total / 3;

        if ($average >= 80) {
            $grade = "A";
        } elseif ($average >= 70) {
            $grade = "B";
        } elseif ($average >= 60) {
            $grade = "C";
        } elseif ($average >= 50) {
            $grade = "D";
        } else {
            $grade = "F";
        }

        $results = [
            "name" => $name,
            "subject1" => $subject1,
            "subject2" => $subject2,
            "subject3" => $subject3,
            "total" => $total,
            "average" => $average,
            "grade" => $grade
        ];

        $sql = "INSERT INTO srms (name, subject1, subject2, subject3, total, average, grade) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiiids", $name, $subject1, $subject2, $subject3, $total, $average, $grade);
        
        if ($stmt->execute()) {
            $message = "Student result saved successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$all_results = [];

if (!empty($search)) {
    $sql = "SELECT * FROM srms WHERE name LIKE ? OR id = ? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $search_id = is_numeric($search) ? intval($search) : 0;
    $stmt->bind_param("si", $search_param, $search_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $all_results[] = $row;
    }
    $stmt->close();
} else {
    $sql = "SELECT * FROM srms ORDER BY id ASC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $all_results[] = $row;
        }
    }
}

// Get statistics
$stats_sql = "SELECT COUNT(*) as total_students, AVG(average) as overall_avg, MAX(average) as highest_avg, MIN(average) as lowest_avg FROM srms";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 0.9em;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
        }
        
        .search-bar {
            margin-bottom: 20px;
        }
        
        .search-bar input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .grade-A { color: #28a745; font-weight: bold; }
        .grade-B { color: #17a2b8; font-weight: bold; }
        .grade-C { color: #ffc107; font-weight: bold; }
        .grade-D { color: #fd7e14; font-weight: bold; }
        .grade-F { color: #dc3545; font-weight: bold; }
        
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        input[type="text"]:focus, input[type="number"]:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #000;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 6px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error {
            padding: 15px;
            margin: 15px 0;
            border-radius: 6px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎓 Student Result Management System</h1>
        <p>Manage and track student performance efficiently</p>
    </div>

    <?php if ($stats['total_students'] > 0): ?>
    <div class="container">
        <h2>📊 Dashboard Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <div class="value"><?php echo $stats['total_students']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Overall Average</h3>
                <div class="value"><?php echo number_format($stats['overall_avg'], 1); ?>%</div>
            </div>
            <div class="stat-card">
                <h3>Highest Average</h3>
                <div class="value"><?php echo number_format($stats['highest_avg'], 1); ?>%</div>
            </div>
            <div class="stat-card">
                <h3>Lowest Average</h3>
                <div class="value"><?php echo number_format($stats['lowest_avg'], 1); ?>%</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="container">
        <h2>➕ Enter Student Marks</h2>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-grid">
                <div class="form-group">
                    <label>Student Name:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Subject 1 (0-100):</label>
                    <input type="number" name="subject1" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label>Subject 2 (0-100):</label>
                    <input type="number" name="subject2" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label>Subject 3 (0-100):</label>
                    <input type="number" name="subject3" min="0" max="100" required>
                </div>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Submit Results</button>
        </form>
    </div>

    <?php if (!empty($all_results)): ?>
        <div class="container">
            <h2>📚 All Student Results</h2>
            
            <div class="search-bar">
                <form method="get">
                    <input type="text" name="search" placeholder="🔍 Search by name or ID..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            
            <div style="overflow-x: auto;">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Subject 1</th>
                        <th>Subject 2</th>
                        <th>Subject 3</th>
                        <th>Total</th>
                        <th>Average</th>
                        <th>Grade</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($all_results as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['subject1']; ?></td>
                            <td><?php echo $row['subject2']; ?></td>
                            <td><?php echo $row['subject3']; ?></td>
                            <td><?php echo $row['total']; ?></td>
                            <td><?php echo number_format($row['average'], 2); ?>%</td>
                            <td class="grade-<?php echo $row['grade']; ?>"><?php echo $row['grade']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td class="actions">
                                <button onclick="editRecord(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', <?php echo $row['subject1']; ?>, <?php echo $row['subject2']; ?>, <?php echo $row['subject3']; ?>)" class="btn btn-warning">Edit</button>
                                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; margin:50px auto; padding:30px; max-width:600px; border-radius:12px;">
            <h2>✏️ Edit Student Record</h2>
            <form method="post">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Student Name:</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Subject 1:</label>
                    <input type="number" name="subject1" id="edit_subject1" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label>Subject 2:</label>
                    <input type="number" name="subject2" id="edit_subject2" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label>Subject 3:</label>
                    <input type="number" name="subject3" id="edit_subject3" min="0" max="100" required>
                </div>
                <button type="submit" name="update" class="btn btn-primary">Update Record</button>
                <button type="button" onclick="closeModal()" class="btn btn-danger">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function editRecord(id, name, sub1, sub2, sub3) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_subject1').value = sub1;
            document.getElementById('edit_subject2').value = sub2;
            document.getElementById('edit_subject3').value = sub3;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>