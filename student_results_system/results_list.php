<?php
include "config.php";
include "includes/auth.php";

if (!in_array($_SESSION['role'], ['admin', 'teacher', 'student', 'parent'])) {
    die("Access denied!");
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// DEBUG: Show current user info
echo "<!-- Debug: User ID: $user_id, Role: $user_role -->";

// Grade calculation function
function calculateGrade($marks) {
    if ($marks >= 90) return "A+";
    if ($marks >= 80) return "A";
    if ($marks >= 70) return "B";
    if ($marks >= 60) return "C";
    if ($marks >= 50) return "D";
    return "F";
}

// Base SQL
$sql = "
    SELECT r.*,
           u1.full_name AS student_name,
           s.subject_name,
           u2.full_name AS teacher_name
    FROM results r
    JOIN users u1 ON r.student_id = u1.id
    JOIN subjects s ON r.subject_id = s.id
    LEFT JOIN users u2 ON r.teacher_id = u2.id
";

$params = [];
$types  = "";

// Role-based filtering
if ($user_role === 'admin' || $user_role === 'teacher') {
    echo "<!-- Debug: Admin/Teacher view - showing all results -->";
    // No filter

} elseif ($user_role === 'student') {
    echo "<!-- Debug: Student view - showing only student_id: $user_id -->";
    $sql .= " WHERE r.student_id = ?";
    $params[] = $user_id;
    $types   .= "i";

} elseif ($user_role === 'parent') {
    echo "<!-- Debug: Parent view - checking parent_students table for parent_id: $user_id -->";
    
    // First, let's check if parent_students table exists and has data
    $check_sql = "SELECT student_id FROM parent_students WHERE parent_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if ($check_stmt) {
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $student_ids_result = $check_stmt->get_result();
        $student_ids = [];
        
        while ($row = $student_ids_result->fetch_assoc()) {
            $student_ids[] = $row['student_id'];
        }
        $check_stmt->close();
        
        echo "<!-- Debug: Found " . count($student_ids) . " students for this parent -->";
        
        if (!empty($student_ids)) {
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $sql .= " WHERE r.student_id IN ($placeholders)";
            $params = array_merge($params, $student_ids);
            $types .= str_repeat('i', count($student_ids));
        } else {
            echo "<!-- Debug: No students linked to this parent -->";
            $sql .= " WHERE 1=0"; // No results
        }
    } else {
        echo "<!-- Debug: parent_students table doesn't exist or has error -->";
        // If table doesn't exist, use temporary manual mapping
        $sql .= " WHERE 1=0"; // No results until table is fixed
    }
}

$sql .= " ORDER BY r.academic_year DESC, r.term DESC, r.id ASC";

echo "<!-- Debug: Final SQL: " . htmlspecialchars($sql) . " -->";
echo "<!-- Debug: Params: " . implode(', ', $params) . " -->";

// Execute query
try {
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $results = $stmt->get_result();
        }
    } else {
        $results = $conn->query($sql);
    }
} catch (Exception $e) {
    echo "<!-- Debug: Error: " . $e->getMessage() . " -->";
    die("Database error: " . $e->getMessage());
}

include "includes/header.php";
?>

<div class="container">
    <h2>📊 Results</h2>
    
    <?php if ($user_role === 'parent'): ?>
        <div class="alert alert-info">
           
            <?php
            // Show which students are linked to this parent
            $check_sql = "SELECT ps.student_id, u.full_name 
                         FROM parent_students ps 
                         JOIN users u ON ps.student_id = u.id 
                         WHERE ps.parent_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            if ($check_stmt) {
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $linked_students = $check_stmt->get_result();
                
                if ($linked_students->num_rows > 0) {
                    echo "<br><small>Your children: ";
                    $children = [];
                    while ($child = $linked_students->fetch_assoc()) {
                        $children[] = $child['full_name'];
                    }
                    echo implode(', ', $children) . "</small>";
                } else {
                   
                }
                $check_stmt->close();
            } else {
                
            }
            ?>
        </div>
    <?php endif; ?>
    
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse; margin-top:20px;">
        <thead style="background:#1abc9c; color:white;">
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>Sem</th>
                <th>Academic Year</th>
                <th>Exam Type</th>
                <th>Teacher</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (isset($results) && $results && $results->num_rows > 0) {
                while ($r = $results->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($r['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($r['student_name'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($r['subject_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($r['marks']) . "</td>";
                    echo "<td>" . htmlspecialchars($r['grade'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($r['term'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($r['academic_year'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($r['exam_type'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($r['teacher_name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($r['created_at']) . "</td>";
                    echo "<td>" . htmlspecialchars($r['updated_at'] ?? '-') . "</td>";
                    echo "<td>" . htmlspecialchars($r['remarks'] ?? '') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='12'>No results found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php include "includes/footer.php"; ?>