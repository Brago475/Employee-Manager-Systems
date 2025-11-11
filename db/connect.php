<?php
// -----------------------------------------------------
// Enable error reporting for debugging
// -----------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -----------------------------------------------------
// Database connection settings
// -----------------------------------------------------
$servername = "localhost";
$username   = "root";
$password   = ""; // no password by default in XAMPP
$database   = "employee_dashboard_db";
$port       = 3306;

// -----------------------------------------------------
// Create connection
// -----------------------------------------------------
$conn = new mysqli($servername, $username, $password, $database, $port);

// -----------------------------------------------------
// Check connection
// -----------------------------------------------------
if ($conn->connect_error) {
    die("<h2 style='color:red; text-align:center;'>❌ Connection failed: " . $conn->connect_error . "</h2>");
}
?>
