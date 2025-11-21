<?php
$xml = simplexml_load_file('students.xml');

echo "<h2>Student Names</h2>";
echo "<ul>";

foreach ($xml->student as $student) {
    echo "<li>" . htmlspecialchars($student->name) . "</li>";
}
echo "</ul>";
?>