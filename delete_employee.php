<?php
require_once('db/connect.php');
$emp_no = $_GET['emp_no'] ?? null;

if (!$emp_no) die("<h3 style='color:red;'>No employee selected for deletion.</h3>");

$sql = "DELETE FROM employees WHERE emp_no='$emp_no'";
if ($conn->query($sql)) {
    echo "<h2 style='color:green; text-align:center;'>✅ Employee #$emp_no deleted successfully!</h2>";
} else {
    echo "<h2 style='color:red; text-align:center;'>❌ Error deleting record: {$conn->error}</h2>";
}

$conn->close();
?>
