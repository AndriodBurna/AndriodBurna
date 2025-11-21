<?php
// Common helper functions for SRMS

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function compute_grade($marks) {
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    if ($marks >= 50) return 'D';
    return 'E';
}

function grade_remarks($grade) {
    switch ($grade) {
        case 'A': return 'Excellent';
        case 'B': return 'Very Good';
        case 'C': return 'Good';
        case 'D': return 'Pass';
        default: return 'Needs Improvement';
    }
}

function selected($a, $b) {
    return $a == $b ? 'selected' : '';
}

/**
 * Generate the next student UID in the format SRMS-YYYY-####
 * Sequence resets per year.
 */
function generate_student_uid(mysqli $link, int $year = null) {
    if ($year === null) { $year = (int) date('Y'); }
    $prefix = 'SRMS-' . $year . '-';
    $sql = "SELECT student_uid FROM students WHERE student_uid LIKE '" . mysqli_real_escape_string($link, $prefix) . "%' ORDER BY student_uid DESC LIMIT 1";
    $res = mysqli_query($link, $sql);
    $next = 1;
    if ($row = mysqli_fetch_assoc($res)) {
        $last = $row['student_uid']; // e.g., SRMS-2024-0012
        $parts = explode('-', $last);
        $seq = (int) ($parts[2] ?? 0);
        $next = $seq + 1;
    }
    return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

/**
 * Ensure a column exists on a table; if missing, try to add it.
 * Safe to call before inserts that rely on newer columns.
 */
function ensure_column(mysqli $link, string $table, string $column, string $definitionSql) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $check = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && mysqli_num_rows($check) === 0) {
        @mysqli_query($link, "ALTER TABLE `$table` ADD COLUMN $definitionSql");
    }
}

/**
 * Ensure a table exists; if missing, create it using provided SQL.
 */
function ensure_table(mysqli $link, string $table, string $createSql) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $check = mysqli_query($link, "SHOW TABLES LIKE '$table'");
    if ($check && mysqli_num_rows($check) === 0) {
        @mysqli_query($link, $createSql);
    }
}

?>