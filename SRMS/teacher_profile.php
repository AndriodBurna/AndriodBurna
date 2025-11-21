<?php
// Profile tab content for teachers
$teacher_id = isset($teacher_id) ? (int)$teacher_id : 0;
if ($teacher_id <= 0) {
    echo "<div class='alert alert-warning'>No teacher selected.</div>";
    return;
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = mysqli_real_escape_string($link, $_POST['email'] ?? '');
    $phone = mysqli_real_escape_string($link, $_POST['phone'] ?? '');

    // Update only allowed fields
    $upd = mysqli_query($link, "UPDATE teachers SET email='$email', phone='$phone' WHERE teacher_id=$teacher_id");
    if ($upd) {
        // Also sync login username to updated email for the current user session
        if (isset($_SESSION['id'])) {
            $uid = (int)$_SESSION['id'];
            mysqli_query($link, "UPDATE users SET username='".mysqli_real_escape_string($link, $email)."' WHERE user_id=$uid");
            $_SESSION['username'] = $email; // keep session consistent
        }
        echo "<div class='alert alert-success'>Profile updated successfully. Login email synchronized.</div>";
    } else {
        echo "<div class='alert alert-danger'>Failed to update profile.</div>";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $pw_error = '';

    if ($new_password === '' || $confirm_password === '' || $current_password === '') {
        $pw_error = 'Please fill all password fields.';
    } elseif ($new_password !== $confirm_password) {
        $pw_error = 'New password and confirmation do not match.';
    } elseif (strlen($new_password) < 6) {
        $pw_error = 'New password must be at least 6 characters.';
    } else {
        // Verify current password against users table
        $username = $_SESSION['username'] ?? '';
        $stmt = mysqli_prepare($link, 'SELECT user_id, password FROM users WHERE username = ?');
        mysqli_stmt_bind_param($stmt, 's', $username);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $uid, $hashed_pw);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($current_password, $hashed_pw)) {
                        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        $upd_stmt = mysqli_prepare($link, 'UPDATE users SET password = ? WHERE user_id = ?');
                        mysqli_stmt_bind_param($upd_stmt, 'si', $new_hashed, $uid);
                        if (mysqli_stmt_execute($upd_stmt)) {
                            echo "<div class='alert alert-success'>Password changed successfully.</div>";
                        } else {
                            echo "<div class='alert alert-danger'>Failed to update password.</div>";
                        }
                        mysqli_stmt_close($upd_stmt);
                    } else {
                        $pw_error = 'Current password is incorrect.';
                    }
                }
            } else {
                $pw_error = 'User account not found.';
            }
        } else {
            $pw_error = 'Error verifying current password.';
        }
        mysqli_stmt_close($stmt);
    }

    if ($pw_error !== '') {
        echo "<div class='alert alert-danger'>".htmlspecialchars($pw_error)."</div>";
    }
}

// Fetch current profile
$prof = [];
$q = mysqli_query($link, "SELECT teacher_id, name, gender, email, phone, class_id, subject_id FROM teachers WHERE teacher_id=$teacher_id");
if ($row = mysqli_fetch_assoc($q)) { $prof = $row; }

// Resolve class and subject names
$class_name = '';
if (!empty($prof['class_id'])) {
    $cq = mysqli_query($link, "SELECT class_name, stream FROM classes WHERE class_id=".(int)$prof['class_id']);
    if ($cr = mysqli_fetch_assoc($cq)) { $class_name = $cr['class_name'].' '.$cr['stream']; }
}
$subject_name = '';
if (!empty($prof['subject_id'])) {
    $sq = mysqli_query($link, "SELECT subject_name FROM subjects WHERE subject_id=".(int)$prof['subject_id']);
    if ($sr = mysqli_fetch_assoc($sq)) { $subject_name = $sr['subject_name']; }
}
?>

<div class="profile-tab">
    <h3>My Profile</h3>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4>Personal Information</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($prof['name'] ?? ''); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($prof['gender'] ?? ''); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($prof['email'] ?? ''); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($prof['phone'] ?? ''); ?></p>
                    <p><strong>Class:</strong> <?php echo htmlspecialchars($class_name); ?></p>
                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($subject_name); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4>Update Contact Details</h4>
                    <form method="post">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($prof['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($prof['phone'] ?? ''); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        <small class="text-muted d-block mt-2">Changing email updates your login username.</small>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4>Change Password</h4>
                    <form method="post">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-secondary">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>