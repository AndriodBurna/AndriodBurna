<?php
include "config.php";
include "includes/auth.php";

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$success_message = '';
$error_message = '';

// Create tables if they don't exist
// $conn->query("CREATE TABLE IF NOT EXISTS courses (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     course_name VARCHAR(100) NOT NULL,
//     course_code VARCHAR(20) UNIQUE NOT NULL,
//     description TEXT,
//     duration_years INT DEFAULT 1,
//     department VARCHAR(100),
//     credits INT DEFAULT 0,
//     status ENUM('active', 'inactive') DEFAULT 'active',
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
// )");

// $conn->query("CREATE TABLE IF NOT EXISTS course_subjects (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     course_id INT NOT NULL,
//     subject_id INT NOT NULL,
//     year_level INT DEFAULT 1,
//     term INT DEFAULT 1,
//     is_mandatory BOOLEAN DEFAULT TRUE,
//     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
//     FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
//     UNIQUE KEY unique_course_subject (course_id, subject_id, year_level, term)
// )");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['add_course'])) {
        // Add new course
        $course_name = trim($_POST['course_name']);
        $course_code = trim(strtoupper($_POST['course_code']));
        $description = trim($_POST['description']);
        $duration_years = (int)$_POST['duration_years'];
        $department = trim($_POST['department']);
        $credits = (int)$_POST['credits'];
        $status = $_POST['status'];
        
        try {
            $stmt = $conn->prepare("INSERT INTO courses (course_name, course_code, description, duration_years, department, credits, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssisis", $course_name, $course_code, $description, $duration_years, $department, $credits, $status);
            
            if ($stmt->execute()) {
                $success_message = "Course '{$course_name}' added successfully!";
            } else {
                $error_message = "Error adding course. Course code might already exist.";
            }
        } catch (Exception $e) {
            $error_message = "Error adding course: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['edit_course'])) {
        // Edit existing course
        $course_id = (int)$_POST['course_id'];
        $course_name = trim($_POST['course_name']);
        $course_code = trim(strtoupper($_POST['course_code']));
        $description = trim($_POST['description']);
        $duration_years = (int)$_POST['duration_years'];
        $department = trim($_POST['department']);
        $credits = (int)$_POST['credits'];
        $status = $_POST['status'];
        
        try {
            $stmt = $conn->prepare("UPDATE courses SET course_name=?, course_code=?, description=?, duration_years=?, department=?, credits=?, status=? WHERE id=?");
            $stmt->bind_param("sssisisi", $course_name, $course_code, $description, $duration_years, $department, $credits, $status, $course_id);
            
            if ($stmt->execute()) {
                $success_message = "Course updated successfully!";
            } else {
                $error_message = "Error updating course.";
            }
        } catch (Exception $e) {
            $error_message = "Error updating course: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_course'])) {
        // Delete course
        $course_id = (int)$_POST['course_id'];
        
        try {
            // Check if course has students enrolled
            $check = $conn->query("SELECT COUNT(*) as count FROM users WHERE course_id = $course_id");
            $enrolled = $check->fetch_assoc()['count'];
            
            if ($enrolled > 0) {
                $error_message = "Cannot delete course with enrolled students. Transfer students first.";
            } else {
                $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->bind_param("i", $course_id);
                
                if ($stmt->execute()) {
                    $success_message = "Course deleted successfully!";
                } else {
                    $error_message = "Error deleting course.";
                }
            }
        } catch (Exception $e) {
            $error_message = "Error deleting course: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['add_subject_to_course'])) {
        // Add subject to course
        $course_id = (int)$_POST['course_id'];
        $subject_id = (int)$_POST['subject_id'];
        $year_level = (int)$_POST['year_level'];
        $term = (int)$_POST['term'];
        $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
        
        try {
            $stmt = $conn->prepare("INSERT INTO course_subjects (course_id, subject_id, year_level, term, is_mandatory) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiii", $course_id, $subject_id, $year_level, $term, $is_mandatory);
            
            if ($stmt->execute()) {
                $success_message = "Subject added to course successfully!";
            } else {
                $error_message = "Error adding subject to course. Subject might already be assigned.";
            }
        } catch (Exception $e) {
            $error_message = "Error adding subject to course: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['remove_subject_from_course'])) {
        // Remove subject from course
        $course_subject_id = (int)$_POST['course_subject_id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM course_subjects WHERE id = ?");
            $stmt->bind_param("i", $course_subject_id);
            
            if ($stmt->execute()) {
                $success_message = "Subject removed from course successfully!";
            } else {
                $error_message = "Error removing subject from course.";
            }
        } catch (Exception $e) {
            $error_message = "Error removing subject from course: " . $e->getMessage();
        }
    }
}

// Get all courses
$courses = $conn->query("SELECT c.*, 
                        (SELECT COUNT(*) FROM users WHERE course_id = c.id) as student_count,
                        (SELECT COUNT(*) FROM course_subjects WHERE course_id = c.id) as subject_count
                        FROM courses c ORDER BY c.course_name");

// Get all subjects for dropdown
$subjects = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name");

// Get course for editing if specified
$edit_course = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM courses WHERE id = $edit_id");
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_course = $edit_result->fetch_assoc();
    }
}

// Get course subjects for management
$course_subjects = [];
if (isset($_GET['manage_subjects'])) {
    $manage_course_id = (int)$_GET['manage_subjects'];
    $course_subjects_result = $conn->query("
        SELECT cs.*, s.subject_name, c.course_name 
        FROM course_subjects cs 
        JOIN subjects s ON cs.subject_id = s.id 
        JOIN courses c ON cs.course_id = c.id 
        WHERE cs.course_id = $manage_course_id 
        ORDER BY cs.year_level, cs.term, s.subject_name
    ");
    
    if ($course_subjects_result) {
        while ($row = $course_subjects_result->fetch_assoc()) {
            $course_subjects[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Student Results Management System</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .courses-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .courses-header {
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .courses-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .course-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #1abc9c;
        }
        
        .course-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .course-table {
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .course-item {
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .course-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .course-title {
            font-size: 1.4rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .course-code {
            background: #1abc9c;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .course-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1abc9c;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .course-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .subject-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .subject-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: white;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 3px solid #1abc9c;
        }
        
        .year-term-badge {
            background: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        
        .mandatory-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        
        .optional-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        @media (max-width: 768px) {
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .course-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .course-details {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>📊 SRMS</h2>
        <a href="index.php">🏠 Dashboard</a>
        <a href="user_manage.php">👥 Manage Users</a>
        <a href="student_manage.php">👨‍🎓 Manage Students</a>
        <a href="subjects_manage.php">📚 Manage Subjects</a>
        <a href="courses_manage.php" class="active">📖 Manage Courses</a>
        <a href="results_list.php">📊 All Results</a>
        <a href="report_class.php">📈 Performance Reports</a>
        <a href="parent_manage.php">👨‍👩‍👧 Parent-Student Link</a>
        <a href="settings.php">⚙️ System Settings</a>
        <a href="logout.php" style="margin-top: auto; background: #e74c3c;">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="courses-container">
            <div class="courses-header">
                <h1>📖 Course Management</h1>
                <p>Create, edit, and manage academic courses and their associated subjects</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="courses-grid">
                <!-- Add/Edit Course Form -->
                <div class="course-card">
                    <h3>
                        <?= $edit_course ? '✏️ Edit Course' : '➕ Add New Course' ?>
                    </h3>
                    <form method="POST">
                        <?php if ($edit_course): ?>
                            <input type="hidden" name="course_id" value="<?= $edit_course['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="course_name">Course Name *</label>
                            <input type="text" id="course_name" name="course_name" 
                                   value="<?= $edit_course ? htmlspecialchars($edit_course['course_name']) : '' ?>" 
                                   placeholder="e.g., Computer Science" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="course_code">Course Code *</label>
                                <input type="text" id="course_code" name="course_code" 
                                       value="<?= $edit_course ? htmlspecialchars($edit_course['course_code']) : '' ?>" 
                                       placeholder="e.g., CS101" required>
                            </div>
                            <div class="form-group">
                                <label for="duration_years">Duration (Years)</label>
                                <select id="duration_years" name="duration_years">
                                    <option value="1" <?= ($edit_course && $edit_course['duration_years'] == 1) ? 'selected' : '' ?>>1 Year</option>
                                    <option value="2" <?= ($edit_course && $edit_course['duration_years'] == 2) ? 'selected' : '' ?>>2 Years</option>
                                    <option value="3" <?= ($edit_course && $edit_course['duration_years'] == 3) ? 'selected' : '' ?>>3 Years</option>
                                    <option value="4" <?= ($edit_course && $edit_course['duration_years'] == 4) ? 'selected' : '' ?>>4 Years</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" id="department" name="department" 
                                   value="<?= $edit_course ? htmlspecialchars($edit_course['department']) : '' ?>" 
                                   placeholder="e.g., Science & Technology">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Course description and objectives"><?= $edit_course ? htmlspecialchars($edit_course['description']) : '' ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="credits">Credits</label>
                                <input type="number" id="credits" name="credits" min="0" 
                                       value="<?= $edit_course ? $edit_course['credits'] : '0' ?>">
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="active" <?= ($edit_course && $edit_course['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($edit_course && $edit_course['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <?php if ($edit_course): ?>
                                <button type="submit" name="edit_course" class="btn">💾 Update Course</button>
                                <a href="courses_manage.php" class="btn btn-secondary">❌ Cancel</a>
                            <?php else: ?>
                                <button type="submit" name="add_course" class="btn">➕ Add Course</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Course Statistics -->
                <div class="course-card">
                    <h3>📊 Course Statistics</h3>
                    <?php
                    $total_courses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
                    $active_courses = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status='active'")->fetch_assoc()['count'];
                    $total_enrollments = $conn->query("SELECT COUNT(*) as count FROM users WHERE course_id IS NOT NULL")->fetch_assoc()['count'];
                    $total_course_subjects = $conn->query("SELECT COUNT(*) as count FROM course_subjects")->fetch_assoc()['count'];
                    ?>
                    
                    <div class="course-details">
                        <div class="detail-item">
                            <div class="detail-number"><?= $total_courses ?></div>
                            <div class="detail-label">Total Courses</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-number"><?= $active_courses ?></div>
                            <div class="detail-label">Active Courses</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-number"><?= $total_enrollments ?></div>
                            <div class="detail-label">Total Enrollments</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-number"><?= $total_course_subjects ?></div>
                            <div class="detail-label">Course-Subject Links</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course List -->
            <div class="course-table">
                <div style="padding: 20px; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                    <h3 style="margin: 0;">📚 All Courses</h3>
                </div>
                
                <?php if ($courses && $courses->num_rows > 0): ?>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <div class="course-item">
                            <div class="course-header">
                                <div>
                                    <div class="course-title"><?= htmlspecialchars($course['course_name']) ?></div>
                                    <span class="course-code"><?= htmlspecialchars($course['course_code']) ?></span>
                                    <span class="status-badge <?= $course['status'] == 'active' ? 'status-active' : 'status-inactive' ?>">
                                        <?= ucfirst($course['status']) ?>
                                    </span>
                                </div>
                                <div class="course-actions">
                                    <a href="?edit=<?= $course['id'] ?>" class="btn btn-small">✏️ Edit</a>
                                    <button onclick="openSubjectModal(<?= $course['id'] ?>, '<?= htmlspecialchars($course['course_name']) ?>')" 
                                            class="btn btn-small">📚 Subjects</button>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Delete this course? This action cannot be undone.')">
                                        <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                        <button type="submit" name="delete_course" class="btn btn-danger btn-small">🗑️ Delete</button>
                                    </form>
                                </div>
                            </div>
                            
                            <?php if ($course['description']): ?>
                                <p style="color: #6c757d; margin-bottom: 15px;"><?= htmlspecialchars($course['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="course-details">
                                <div class="detail-item">
                                    <div class="detail-number"><?= $course['duration_years'] ?></div>
                                    <div class="detail-label">Years</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-number"><?= $course['credits'] ?></div>
                                    <div class="detail-label">Credits</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-number"><?= $course['student_count'] ?></div>
                                    <div class="detail-label">Students</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-number"><?= $course['subject_count'] ?></div>
                                    <div class="detail-label">Subjects</div>
                                </div>
                            </div>
                            
                            <?php if ($course['department']): ?>
                                <div style="margin-top: 10px;">
                                    <strong>Department:</strong> <?= htmlspecialchars($course['department']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #6c757d;">
                        <h3>📚 No Courses Found</h3>
                        <p>Start by adding your first course using the form above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Subject Management Modal -->
<div id="subjectModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSubjectModal()">&times;</span>
        <h2 id="modalTitle">📚 Manage Course Subjects</h2>
        
        <!-- Add Subject to Course Form -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h4>➕ Add Subject to Course</h4>
            <form method="POST" id="addSubjectForm">
                <input type="hidden" id="modal_course_id" name="course_id" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject_id">Subject</label>
                        <select id="subject_id" name="subject_id" required>
                            <option value="">Select Subject...</option>
                            <?php 
                            $subjects->data_seek(0); // Reset pointer
                            while ($subject = $subjects->fetch_assoc()): 
                            ?>
                                <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level" required>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="term">Term</label>
                        <select id="term" name="term" required>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="is_mandatory" name="is_mandatory" checked> Mandatory Subject
                        </label>
                    </div>
                </div>
                
                <button type="submit" name="add_subject_to_course" class="btn">➕ Add Subject</button>
            </form>
        </div>
        
        <!-- Current Course Subjects -->
        <div id="courseSubjectsList">
            <!-- This will be populated by JavaScript -->
        </div>
    </div>
</div>

<script>
function openSubjectModal(courseId, courseName) {
    document.getElementById('modal_course_id').value = courseId;
    document.getElementById('modalTitle').innerHTML = '📚 Manage Subjects for: ' + courseName;
    document.getElementById('subjectModal').style.display = 'block';
    
    // Load current subjects
    loadCourseSubjects(courseId);
}

function closeSubjectModal() {
    document.getElementById('subjectModal').style.display = 'none';
}

function loadCourseSubjects(courseId) {
    // In a real implementation, you would use AJAX to load subjects
    // For now, we'll redirect to show subjects
    window.location.href = '?subjects_manage=' + courseId;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('subjectModal');
    if (event.target == modal) {
        closeSubjectModal();
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#e74c3c';
                    isValid = false;
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Auto-generate course code based on course name
    const courseNameInput = document.getElementById('course_name');
    const courseCodeInput = document.getElementById('course_code');
    
    if (courseNameInput && courseCodeInput) {
        courseNameInput.addEventListener('input', function() {
            if (!courseCodeInput.value) { // Only auto-generate if code is empty
                const name = this.value.trim();
                const words = name.split(' ');
                let code = '';
                
                words.forEach(word => {
                    if (word.length > 0) {
                        code += word.charAt(0).toUpperCase();
                    }
                });
                
                // Add numbers if code is too short
                if (code.length < 3) {
                    code += '101';
                } else if (code.length === 3) {
                    code += '1';
                }
                
                courseCodeInput.value = code.substring(0, 6); // Limit to 6 characters
            }
        });
    }
});
</script>

<?php if (isset($_GET['subjects_manage'])): ?>
<script>
// Show subjects for the selected course
document.addEventListener('DOMContentLoaded', function() {
    const courseId = <?= (int)$_GET['subjects_manage'] ?>;
    const courseName = '<?= $conn->query("SELECT course_name FROM courses WHERE id = " . (int)$_GET['subjects_manage'])->fetch_assoc()['course_name'] ?? 'Course' ?>';
    openSubjectModal(courseId, courseName);
});
</script>

<!-- Display current course subjects -->
<div style="margin-top: 30px;">
    <div class="course-card">
        <h3>📚 Course Subjects Management</h3>
        
        <?php if (!empty($course_subjects)): ?>
            <div class="subject-list">
                <h4>Current Subjects for: <?= htmlspecialchars($course_subjects[0]['course_name']) ?></h4>
                
                <?php 
                $current_year = 0;
                $current_term = 0;
                foreach ($course_subjects as $cs): 
                    if ($cs['year_level'] != $current_year || $cs['term'] != $current_term):
                        if ($current_year != 0): ?>
                            </div>
                        <?php endif; ?>
                        <h5 style="margin-top: 20px; color: #2c3e50;">
                            📅 Year <?= $cs['year_level'] ?> - Term <?= $cs['term'] ?>
                        </h5>
                        <div style="margin-left: 20px;">
                        <?php
                        $current_year = $cs['year_level'];
                        $current_term = $cs['term'];
                    endif;
                ?>
                    <div class="subject-item">
                        <div>
                            <strong><?= htmlspecialchars($cs['subject_name']) ?></strong>
                            <div style="display: flex; gap: 10px; margin-top: 5px;">
                                <span class="year-term-badge">Year <?= $cs['year_level'] ?> - Term <?= $cs['term'] ?></span>
                                <span class="<?= $cs['is_mandatory'] ? 'mandatory-badge' : 'optional-badge' ?>">
                                    <?= $cs['is_mandatory'] ? 'Mandatory' : 'Optional' ?>
                                </span>
                            </div>
                        </div>
                        <form method="POST" style="display: inline;" 
                              onsubmit="return confirm('Remove this subject from the course?')">
                            <input type="hidden" name="course_subject_id" value="<?= $cs['id'] ?>">
                            <button type="submit" name="remove_subject_from_course" 
                                    class="btn btn-danger btn-small">🗑️ Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #6c757d;">
                <h4>📚 No Subjects Assigned</h4>
                <p>Use the form above to add subjects to this course.</p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="courses_manage.php" class="btn btn-secondary">← Back to Courses</a>
        </div>
    </div>
</div>
<?php endif; ?>