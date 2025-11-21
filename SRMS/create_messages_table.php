<?php
require_once 'config.php';

$sql = "
-- Table structure for table `messages`
CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('admin','teacher','student','parent') NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `receiver_role` enum('admin','teacher','student','parent') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message_content` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_status` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (mysqli_multi_query($link, $sql)) {
    echo "Messages table created successfully.";
} else {
    echo "Error creating table: " . mysqli_error($link);
}

mysqli_close($link);
?>