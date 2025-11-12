<?php
// -----------------------------------------------------
// Logout Script
// -----------------------------------------------------
session_start();     // Start session to access variables
session_unset();     // Clear all session variables
session_destroy();   // Destroy the session completely

// Redirect back to the employee login page
header("Location: login.php");
exit;
?>
