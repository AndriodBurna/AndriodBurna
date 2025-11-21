<?php
require_once "config.php";

// Create users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL
)";

if ($conn->query($sql_users) === TRUE) {
    echo "Table 'users' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'users': " . $conn->error . "<br>";
}

// Create students table
$sql_students = "CREATE TABLE IF NOT EXISTS students (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    photo VARCHAR(255),
    dob DATE,
    gender VARCHAR(10),
    current_class VARCHAR(50),
    section VARCHAR(50),
    class_teacher VARCHAR(100),
    house VARCHAR(50),
    address TEXT,
    parent_name VARCHAR(100),
    parent_phone VARCHAR(20),
    parent_email VARCHAR(100),
    allergies TEXT,
    conditions TEXT
)";

if ($conn->query($sql_students) === TRUE) {
    echo "Table 'students' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'students': " . $conn->error . "<br>";
}

// Create terms table
$sql_terms = "CREATE TABLE IF NOT EXISTS terms (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE
)";

if ($conn->query($sql_terms) === TRUE) {
    echo "Table 'terms' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'terms': " . $conn->error . "<br>";
}

// Create classes table
$sql_classes = "CREATE TABLE IF NOT EXISTS classes (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE
)";

if ($conn->query($sql_classes) === TRUE) {
    echo "Table 'classes' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'classes': " . $conn->error . "<br>";
}

// Create subjects table
$sql_subjects = "CREATE TABLE IF NOT EXISTS subjects (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE
)";

if ($conn->query($sql_subjects) === TRUE) {
    echo "Table 'subjects' created successfully or already exists.<br>
} else {
    echo "Error creating table 'subjects': " . $conn->error . "<br>";
}

// Create results table
$sql_results = "CREATE TABLE IF NOT EXISTS results (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    term_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    marks INT NOT NULL,
    grade VARCHAR(10) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (term_id) REFERENCES terms(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
)";

if ($conn->query($sql_results) === TRUE) {
    echo "Table 'results' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'results': " . $conn->error . "<br>";
}


// Insert sample users
$users = [
    ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin'],
    ['teacher1', password_hash('teacher123', PASSWORD_DEFAULT), 'teacher'],
    ['student1', password_hash('student123', PASSWORD_DEFAULT), 'student'],
    ['parent1', password_hash('parent123', PASSWORD_DEFAULT), 'parent']
];

$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");

foreach ($users as $user) {
    $stmt->bind_param("sss", $user[0], $user[1], $user[2]);
    $stmt->execute();
}

echo "Sample users inserted successfully.<br>";

$stmt->close();
$conn->close();
?>