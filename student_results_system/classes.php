<?php
include 'includes/auth.php';
include 'includes/header.php';
require_once "config.php";

$name = "";
$name_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['add'])){
        if(empty(trim($_POST["name"]))){
            $name_err = "Please enter a class name.";
        } else{
            $name = trim($_POST["name"]);
        }

        if(empty($name_err)){
            $sql = "INSERT INTO classes (name) VALUES (?)";

            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("s", $param_name);

                $param_name = $name;

                if($stmt->execute()){
                    header("location: classes.php");
                } else{
                    echo "Something went wrong. Please try again later.";
                }
            }
        }
    }

    if(isset($_POST['update'])){
        $id = $_POST['id'];
        $name = trim($_POST["name"]);

        if(empty($name)){
            $name_err = "Please enter a class name.";
        } else {
            $sql = "UPDATE classes SET name=? WHERE id=?";

            if($stmt = $conn->prepare($sql)){
                $stmt->bind_param("si", $param_name, $param_id);

                $param_name = $name;
                $param_id = $id;

                if($stmt->execute()){
                    header("location: classes.php");
                } else{
                    echo "Something went wrong. Please try again later.";
                }
            }
        }
    }

    if(isset($_POST['delete'])){
        $id = $_POST['id'];
        $sql = "DELETE FROM classes WHERE id=?";

        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $param_id);

            $param_id = $id;

            if($stmt->execute()){
                header("location: classes.php");
            } else{
                echo "Something went wrong. Please try again later.";
            }
        }
    }
}

$sql = "SELECT * FROM classes";
$result = $conn->query($sql);
?>

<div class="container">
    <h2>Manage Classes</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group <?php echo (!empty($name_err)) ? 'has-error' : ''; ?>">
            <label>Class Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo $name; ?>">
            <span class="help-block"><?php echo $name_err;?></span>
        </div>
        <div class="form-group">
            <input type="submit" name="add" class="btn btn-primary" value="Add Class">
        </div>
    </form>

    <h3>Existing Classes</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <td><input type="text" name="name" class="form-control" value="<?php echo $row['name']; ?>"></td>
                    <td>
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <input type="submit" name="update" class="btn btn-success" value="Update">
                        <input type="submit" name="delete" class="btn btn-danger" value="Delete">
                    </td>
                </form>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>