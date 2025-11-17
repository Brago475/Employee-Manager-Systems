<!-- layout/header.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Management System</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        header {
            background-color: #007BFF;
            color: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h2 {
            margin: 0;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }
        nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<header>
    <h2>Employee Management System</h2>
    <nav>
        <a href="/views/index.php">Home</a>
        <a href="/views/view_employees.php">View Employees</a>
        <?php if (($_SESSION['role'] ?? '') === 'manager'): ?>
        <a href="/views/add_employee.php">Add Employee</a>
        <a href="/views/change_title.php">Change Title</a>
        <a href="/views/update_salary.php">Update Salary</a>
        <a href="/views/manager_info.php">Manager Info</a>
        <?php endif; ?>
        <a href="/views/change_department.php">Change Department</a>
        <a href="/views/department_summary.php">Department Summary</a>
        <a href="/views/title_summary.php">Title Summary</a>
        <a href="/views/managers_list.php">Managers</a>
        <a href="/views/logout.php">Logout</a>
    </nav>
</header>
<main style="padding: 20px;">
