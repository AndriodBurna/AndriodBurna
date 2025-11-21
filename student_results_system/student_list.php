<?php
include 'includes/auth.php';
include 'includes/header.php';
require_once "config.php";
?>

<div class="container">
    <h2>Student List</h2>
    <a href="student_add.php" class="btn btn-primary">Add New Student</a>
    <table class="table">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM students";
            if($result = $conn->query($sql)){
                if($result->num_rows > 0){
                    while($row = $result->fetch_assoc()){
                        echo "<tr>";
                            echo "<td>" . $row['student_id'] . "</td>";
                            echo "<td>" . $row['name'] . "</td>";
                            echo "<td>";
                                echo "<a href='student_edit.php?id=". $row['id'] ."' title='Update Record' data-toggle='tooltip'><span class='glyphicon glyphicon-pencil'></span></a>";
                                echo "<a href='student_delete.php?id=". $row['id'] ."' title='Delete Record' data-toggle='tooltip'><span class='glyphicon glyphicon-trash'></span></a>";
                            echo "</td>";
                        echo "</tr>";
                    }
                    $result->free();
                } else{
                    echo "<p class='lead'><em>No records were found.</em></p>";
                }
            } else{
                echo "ERROR: Could not able to execute $sql. " . $conn->error;
            }
            $conn->close();
            ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>