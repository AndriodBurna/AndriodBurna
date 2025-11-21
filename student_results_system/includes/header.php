<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Results Management System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav>
        <ul class="nav navbar-nav navbar-right">
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <li><a href="student_list.php">Students</a></li>
                        <li><a href="terms.php">Terms</a></li>
                        <li><a href="classes.php">Classes</a></li>
                        <li><a href="subjects.php">Subjects</a></li>
                        <li><a href="results_list.php">Results</a></li>
                        <li><a href="report_class.php">Class Report</a></li>
                        <li><a href="report_student.php">Student Report</a></li>
                    <?php elseif($_SESSION['role'] == 'teacher'): ?>
                        <li><a href="student_list.php">Students</a></li>
                        <li><a href="results_list.php">Results</a></li>
                        <li><a href="report_class.php">Class Report</a></li>
                        <li><a href="report_student.php">Student Report</a></li>
                    <?php elseif($_SESSION['role'] == 'student' || $_SESSION['role'] == 'parent'): ?>
                        <li><a href="report_student.php">View Report</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
    </nav>