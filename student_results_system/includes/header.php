<?php if (isset($_SESSION['user_id'])): ?>
<link rel="stylesheet" href="assets/styles.css">


<nav>
  <a href="index.php">Dashboard</a> | 
  <a href="results_list.php">Results</a> | 
  <a href="report_class.php">Reports</a> | 
  <a href="export_csv.php">Export CSV</a> | 
  <a href="logout.php">Logout</a>
</nav>
<hr>



<?php endif; ?>
