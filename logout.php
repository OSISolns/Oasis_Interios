<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Database.php';

$database = new Database();

// Remove the session from database
$database->removeUserSession();

// Clear session data
session_unset();
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?> 