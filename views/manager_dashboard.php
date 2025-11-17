<?php
session_start();
if (!isset($_SESSION['emp_no']) || !$_SESSION['is_manager']) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manager Dashboard</title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    text-align: center;
    padding: 50px;
}
a {
    display: inline-block;
    background-color: #007bff;
    color: white;
    padding: 12px 24px;
    border-radius: 6px;
    margin: 10px;
    text-decoration: none;
}
a:hover {
    background-color: #0056b3;
}
</style>
</head>
<body>
<h1>Welcome Manager, <?= htmlspecialchars($_SESSION['first_name']); ?>!</h1>
<p>Use the options below to manage employees:</p>
<a href="../views/manager_profile.php" style="background-color:#007bff;;">View My information</a>
<a href="../views/view_employees.php">View All Employees</a>
<a href="../views/add_employee.php">Add New Employee</a>
<a href="../views/department_summary.php">View Department Summary</a>
<a href="../views/logout.php">Logout</a>
</body>
</html>
