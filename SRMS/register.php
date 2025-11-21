<?php
session_start();
require_once 'config.php';
require_once 'includes/helpers.php';

// If user is already logged in, redirect to index
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('location: index.php');
    exit;
}

$username = $password = $role = '';
$username_err = $password_err = $role_err = '';
$register_err = $register_success = '';
// Admin invite code (override via env SRMS_ADMIN_INVITE)
$ADMIN_INVITE_CODE = getenv('SRMS_ADMIN_INVITE') ?: 'SRMS-ADMIN-INVITE';
// Admin fields
$admin_secret = '';
$admin_errors = [];

// Allowed roles
$allowed_roles = ['student', 'teacher', 'parent', 'admin'];

// Preload dropdown data for role-specific forms
$classes = mysqli_query($link, "SELECT class_id, class_name, stream, year FROM classes ORDER BY year DESC, class_name ASC");
$subjects = mysqli_query($link, "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");

// Role-specific fields
// Student
$st_name = $st_gender = $st_dob = $st_stream = '';
$st_class_id = 0;
$st_year_joined = (int) date('Y');
$st_parent_email = '';
$st_errors = [];
// Teacher
$tc_name = $tc_gender = $tc_phone = '';
$tc_subject_id = 0;
$tc_class_id = 0;
$tc_errors = [];
// Parent
$pr_name = $pr_phone = $pr_address = '';
$pr_errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate username (use email to align with dashboards mapping email==username)
    if (empty(trim($_POST['username']))) {
        $username_err = 'Please enter your email.';
    } else {
        $username = trim($_POST['username']);
        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $username_err = 'Please enter a valid email address.';
        }
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter a password.';
    } else {
        $password = trim($_POST['password']);
        if (strlen($password) < 6) {
            $password_err = 'Password must be at least 6 characters.';
        }
    }

    // Validate role
    if (empty($_POST['role'])) {
        $role_err = 'Please select a role.';
    } else {
        $role = $_POST['role'];
        if (!in_array($role, $allowed_roles, true)) {
            $role_err = 'Invalid role selected.';
        }
    }

    // Role-specific value collection and validation
    if ($role === 'student') {
        $st_name = sanitize($_POST['st_name'] ?? '');
        $st_gender = sanitize($_POST['st_gender'] ?? '');
        $st_dob = sanitize($_POST['st_dob'] ?? '');
        $st_class_id = (int) ($_POST['st_class_id'] ?? 0);
        $st_stream = sanitize($_POST['st_stream'] ?? '');
        $st_year_joined = (int) ($_POST['st_year_joined'] ?? date('Y'));
        $st_parent_email = sanitize($_POST['st_parent_email'] ?? '');

        if ($st_name === '') $st_errors[] = 'Student name is required';
        if (!in_array($st_gender, ['Male','Female','Other'], true)) $st_errors[] = 'Select a valid gender';
        if ($st_dob === '') $st_errors[] = 'Date of Birth is required';
        if ($st_year_joined < 1990 || $st_year_joined > (int) date('Y') + 1) $st_errors[] = 'Enter a valid year joined';
    } elseif ($role === 'teacher') {
        $tc_name = sanitize($_POST['tc_name'] ?? '');
        $tc_gender = sanitize($_POST['tc_gender'] ?? '');
        $tc_subject_id = (int) ($_POST['tc_subject_id'] ?? 0);
        $tc_class_id = (int) ($_POST['tc_class_id'] ?? 0);
        $tc_phone = sanitize($_POST['tc_phone'] ?? '');

        if ($tc_name === '') $tc_errors[] = 'Teacher name is required';
        if (!in_array($tc_gender, ['Male','Female','Other'], true)) $tc_errors[] = 'Select a valid gender';
    } elseif ($role === 'parent') {
        $pr_name = sanitize($_POST['pr_name'] ?? '');
        $pr_phone = sanitize($_POST['pr_phone'] ?? '');
        $pr_address = sanitize($_POST['pr_address'] ?? '');
        if ($pr_name === '') $pr_errors[] = 'Parent name is required';
    } elseif ($role === 'admin') {
        $admin_secret = trim($_POST['admin_secret'] ?? '');
        if ($admin_secret === '') {
            $admin_errors[] = 'Admin secret code is required';
        } elseif ($admin_secret !== $ADMIN_INVITE_CODE) {
            $admin_errors[] = 'Invalid admin secret code';
        }
    }

    // Proceed if no validation errors
    if (empty($username_err) && empty($password_err) && empty($role_err)) {
        // Check if username already exists
        $sql_check = 'SELECT user_id FROM users WHERE username = ?';
        if ($stmt = mysqli_prepare($link, $sql_check)) {
            mysqli_stmt_bind_param($stmt, 's', $param_username);
            $param_username = $username;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $register_err = 'An account with this email already exists.';
                } else {
                    // Validate role-specific requireds
                    $role_has_errors = false;
                    if ($role === 'student' && !empty($st_errors)) { $register_err = implode('<br>', $st_errors); $role_has_errors = true; }
                    if ($role === 'teacher' && !empty($tc_errors)) { $register_err = implode('<br>', $tc_errors); $role_has_errors = true; }
                    if ($role === 'parent' && !empty($pr_errors)) { $register_err = implode('<br>', $pr_errors); $role_has_errors = true; }
                    if ($role === 'admin' && !empty($admin_errors)) { $register_err = implode('<br>', $admin_errors); $role_has_errors = true; }

                    if (!$role_has_errors) {
                        mysqli_begin_transaction($link);
                        $domain_ok = false;

                        if ($role === 'student') {
                            // Optional parent mapping by email
                            $parent_id = null;
                            if ($st_parent_email !== '') {
                                $sql_pe = 'SELECT parent_id FROM parents WHERE email = ?';
                                if ($stp = mysqli_prepare($link, $sql_pe)) {
                                    mysqli_stmt_bind_param($stp, 's', $st_parent_email);
                                    if (mysqli_stmt_execute($stp)) {
                                        $res = mysqli_stmt_get_result($stp);
                                        if ($row = mysqli_fetch_assoc($res)) { $parent_id = (int) $row['parent_id']; }
                                    }
                                    mysqli_stmt_close($stp);
                                }
                            }
                            $sql_insert_st = 'INSERT INTO students (name, gender, dob, class_id, stream, parent_id, year_joined, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                            if ($st_stmt = mysqli_prepare($link, $sql_insert_st)) {
                                $cid = $st_class_id > 0 ? $st_class_id : null;
                                $pid = $parent_id !== null ? $parent_id : null;
                                mysqli_stmt_bind_param($st_stmt, 'sssissis', $st_name, $st_gender, $st_dob, $cid, $st_stream, $pid, $st_year_joined, $username);
                                if (mysqli_stmt_execute($st_stmt)) { $domain_ok = true; }
                                mysqli_stmt_close($st_stmt);
                            }
                        } elseif ($role === 'teacher') {
                            $sql_insert_tc = 'INSERT INTO teachers (name, gender, subject_id, class_id, email, phone) VALUES (?, ?, ?, ?, ?, ?)';
                            if ($tc_stmt = mysqli_prepare($link, $sql_insert_tc)) {
                                $subj = $tc_subject_id > 0 ? $tc_subject_id : null;
                                $cid = $tc_class_id > 0 ? $tc_class_id : null;
                                mysqli_stmt_bind_param($tc_stmt, 'ssiiss', $tc_name, $tc_gender, $subj, $cid, $username, $tc_phone);
                                if (mysqli_stmt_execute($tc_stmt)) { $domain_ok = true; }
                                mysqli_stmt_close($tc_stmt);
                            }
                        } elseif ($role === 'parent') {
                            $sql_insert_pr = 'INSERT INTO parents (name, phone, email, address) VALUES (?, ?, ?, ?)';
                            if ($pr_stmt = mysqli_prepare($link, $sql_insert_pr)) {
                                mysqli_stmt_bind_param($pr_stmt, 'ssss', $pr_name, $pr_phone, $username, $pr_address);
                                if (mysqli_stmt_execute($pr_stmt)) { $domain_ok = true; }
                                mysqli_stmt_close($pr_stmt);
                            }
                        } elseif ($role === 'admin') {
                            // No domain table for admin; allow account creation once secret is verified
                            $domain_ok = true;
                        }

                        if ($domain_ok) {
                            // Create account
                            $sql_insert = 'INSERT INTO users (username, password, role) VALUES (?, ?, ?)';
                            if ($stmt2 = mysqli_prepare($link, $sql_insert)) {
                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                mysqli_stmt_bind_param($stmt2, 'sss', $username, $hashed_password, $role);
                                if (mysqli_stmt_execute($stmt2)) {
                                    mysqli_commit($link);
                                    header('Location: login.php?registered=1');
                                    exit;
                                } else {
                                    $register_err = 'Registration failed. Please try again later.';
                                    mysqli_rollback($link);
                                }
                                mysqli_stmt_close($stmt2);
                            } else {
                                $register_err = 'Unable to prepare registration statement.';
                                mysqli_rollback($link);
                            }
                        } else {
                            $register_err = 'Could not create profile for selected role.';
                            mysqli_rollback($link);
                        }
                    }
                }
            } else {
                $register_err = 'Oops! Something went wrong. Please try again later.';
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_close($link);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - SRMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .register-container {
            max-width: 420px;
            margin: 60px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Create Account</h2>
        <p>Sign up to access the SRMS portal.</p>

        <?php 
        if(!empty($register_err)){
            echo '<div class="alert alert-danger">' . $register_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select Role</option>
                    <option value="student" <?php echo ($role==='student') ? 'selected' : ''; ?>>Student</option>
                    <option value="teacher" <?php echo ($role==='teacher') ? 'selected' : ''; ?>>Teacher</option>
                    <option value="parent" <?php echo ($role==='parent') ? 'selected' : ''; ?>>Parent</option>
                    <option value="admin" <?php echo ($role==='admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
                <span class="invalid-feedback"><?php echo $role_err; ?></span>
            </div>
            <div id="admin-fields" style="display: <?php echo ($role==='admin') ? 'block' : 'none'; ?>;">
                <h5 class="mt-4">Admin Verification</h5>
                <div class="form-group">
                    <label>Admin Secret Code</label>
                    <input type="text" name="admin_secret" class="form-control" value="<?php echo htmlspecialchars($admin_secret); ?>" placeholder="Enter your admin invite code">
                </div>
                <div class="alert alert-info">Use the configured invite code to create an admin.</div>
            </div>
            <div id="student-fields" style="display: <?php echo ($role==='student') ? 'block' : 'none'; ?>;">
                <h5 class="mt-4">Student Details</h5>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="st_name" class="form-control" value="<?php echo htmlspecialchars($st_name); ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="st_gender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo selected($st_gender, 'Male'); ?>>Male</option>
                        <option value="Female" <?php echo selected($st_gender, 'Female'); ?>>Female</option>
                        <option value="Other" <?php echo selected($st_gender, 'Other'); ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="st_dob" class="form-control" value="<?php echo htmlspecialchars($st_dob); ?>">
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select name="st_class_id" class="form-control">
                        <option value="0">Select Class (optional)</option>
                        <?php if ($classes): while ($c = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo (int)$c['class_id']; ?>" <?php echo ($st_class_id == (int)$c['class_id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($c['class_name'] . ($c['stream'] ? ' - ' . $c['stream'] : '') . ' (' . $c['year'] . ')'); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stream</label>
                    <input type="text" name="st_stream" class="form-control" value="<?php echo htmlspecialchars($st_stream); ?>">
                </div>
                <div class="form-group">
                    <label>Year Joined</label>
                    <input type="number" name="st_year_joined" class="form-control" min="1990" max="<?php echo (int)date('Y')+1; ?>" value="<?php echo (int)$st_year_joined; ?>">
                </div>
                <div class="form-group">
                    <label>Parent Email (optional)</label>
                    <input type="email" name="st_parent_email" class="form-control" value="<?php echo htmlspecialchars($st_parent_email); ?>">
                </div>
            </div>

            <div id="teacher-fields" style="display: <?php echo ($role==='teacher') ? 'block' : 'none'; ?>;">
                <h5 class="mt-4">Teacher Details</h5>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="tc_name" class="form-control" value="<?php echo htmlspecialchars($tc_name); ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="tc_gender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo selected($tc_gender, 'Male'); ?>>Male</option>
                        <option value="Female" <?php echo selected($tc_gender, 'Female'); ?>>Female</option>
                        <option value="Other" <?php echo selected($tc_gender, 'Other'); ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <select name="tc_subject_id" class="form-control">
                        <option value="0">Select Subject (optional)</option>
                        <?php if ($subjects): while ($s = mysqli_fetch_assoc($subjects)): ?>
                            <option value="<?php echo (int)$s['subject_id']; ?>" <?php echo ($tc_subject_id == (int)$s['subject_id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($s['subject_name']); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select name="tc_class_id" class="form-control">
                        <option value="0">Select Class (optional)</option>
                        <?php if ($classes): mysqli_data_seek($classes, 0); while ($c = mysqli_fetch_assoc($classes)): ?>
                            <option value="<?php echo (int)$c['class_id']; ?>" <?php echo ($tc_class_id == (int)$c['class_id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($c['class_name'] . ($c['stream'] ? ' - ' . $c['stream'] : '') . ' (' . $c['year'] . ')'); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="tc_phone" class="form-control" value="<?php echo htmlspecialchars($tc_phone); ?>">
                </div>
            </div>

            <div id="parent-fields" style="display: <?php echo ($role==='parent') ? 'block' : 'none'; ?>;">
                <h5 class="mt-4">Parent Details</h5>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="pr_name" class="form-control" value="<?php echo htmlspecialchars($pr_name); ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="pr_phone" class="form-control" value="<?php echo htmlspecialchars($pr_phone); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="pr_address" class="form-control" rows="2"><?php echo htmlspecialchars($pr_address); ?></textarea>
                </div>
            </div>
            <div class="form-group d-flex justify-content-between align-items-center">
                <input type="submit" class="btn btn-primary" value="Register">
                <a href="login.php">Already have an account? Login</a>
            </div>
        </form>
    </div>
    <script>
        (function(){
            var roleSel = document.querySelector('select[name="role"]');
            function sync(){
                var r = roleSel.value;
                document.getElementById('student-fields').style.display = (r==='student')?'block':'none';
                document.getElementById('teacher-fields').style.display = (r==='teacher')?'block':'none';
                document.getElementById('parent-fields').style.display = (r==='parent')?'block':'none';
                document.getElementById('admin-fields').style.display = (r==='admin')?'block':'none';
            }
            if (roleSel) { roleSel.addEventListener('change', sync); sync(); }
        })();
    </script>
</body>
</html>