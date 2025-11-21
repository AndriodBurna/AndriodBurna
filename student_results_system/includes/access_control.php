<?php
function can_access($role, $page) {
    $permissions = [
        'admin' => ['student_add.php', 'student_edit.php', 'student_delete.php', 'student_list.php', 'terms.php', 'classes.php', 'subjects.php', 'results_add.php', 'results_edit.php', 'results_delete.php', 'results_list.php', 'report_class.php', 'report_student.php'],
        'teacher' => ['student_list.php', 'results_add.php', 'results_edit.php', 'results_list.php', 'report_class.php', 'report_student.php'],
        'student' => ['report_student.php'],
        'parent' => ['report_student.php']
    ];

    if (isset($permissions[$role])) {
        return in_array($page, $permissions[$role]);
    } else {
        return false;
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
if (isset($_SESSION['role']) && !can_access($_SESSION['role'], $current_page)) {
    header("location: error.php?message=Access Denied");
    exit;
}
?>