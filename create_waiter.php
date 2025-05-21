<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'cubana_pos_sys');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id int(11) NOT NULL AUTO_INCREMENT,
    username varchar(50) NOT NULL,
    password varchar(255) NOT NULL,
    role enum('admin','manager','supervisor','cashier','bartender','waiter') NOT NULL,
    full_name varchar(100) NOT NULL,
    status tinyint(1) DEFAULT 1,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    last_login timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    die("Error creating table: " . $conn->error);
}

// Add admin user
$username = 'Mimi';
$password = password_hash('Mimi@2025', PASSWORD_DEFAULT);
$role = 'waiter';
$full_name = 'System Administrator';

$sql = "INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $username, $password, $role, $full_name);

if ($stmt->execute()) {
    echo "Waiter created successfully!<br>";
    echo "Username: Mimi<br>";
    echo "Password: Mimi@2025<br>";
} else {
    echo "Error: " . $stmt->error;
}
?> 