<?php
session_start();
require_once('../database/db_connect.php');

// Only managers should delete
if (!isset($_SESSION['emp_no']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../views/employee_login.php");
    exit;
}

$emp_no = $_GET['emp_no'] ?? null;
$message = "";

// No ID provided
if (!$emp_no) {
    $message = "<p style='color:red; text-align:center;'>No employee selected for deletion.</p>";
} else {
    // Use prepared statement for safety
    $stmt = $conn->prepare("DELETE FROM employees WHERE emp_no = ?");
    $stmt->bind_param("i", $emp_no);

    if ($stmt->execute()) {
        $message = "<p style='color:green; text-align:center;'>Employee #{$emp_no} deleted successfully!</p>";
    } else {
        $message = "<p style='color:red; text-align:center;'>Error deleting record: {$conn->error}</p>";
    }

    $stmt->close();
}

// Include layout header
include('../layout/header.php');
?>

<div style="width:60%; max-width:600px; margin:50px auto; background-color:white; padding:30px 40px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1); text-align:center;">
    <h2>Delete Employee</h2>
    <?= $message ?>
    <br>
    <a href="view_employees.php" style="color:#007BFF; text-decoration:none;">← Back to Employee List</a>
</div>

<?php
include('../layout/footer.php');
$conn->close();
?>
