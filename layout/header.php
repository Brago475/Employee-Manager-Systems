<!-- layout/header.php -->
<?php
// Start the session if not already started
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
        /* Header bar style */
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
        /* Navigation links */
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
        <!-- Everyone sees Home -->
        <a href="/Employee-Manager-Systems/views/index.php">Home</a>

        <!-- MANAGERS ONLY LINKS -->
        <?php if (!empty($_SESSION['is_manager']) && $_SESSION['is_manager'] === true): ?>

            <a href="/Employee-Manager-Systems/views/view_employees.php">View Employees</a>
            <a href="/Employee-Manager-Systems/views/add_employee.php">Add Employee</a>
            <a href="/Employee-Manager-Systems/views/change_department.php">Change Department</a>
            <a href="/Employee-Manager-Systems/views/change_title.php">Change Title</a>
            <a href="/Employee-Manager-Systems/views/update_salary.php">Update Salary</a>
            <a href="/Employee-Manager-Systems/views/department_summary.php">Department Summary</a>
            <a href="/Employee-Manager-Systems/views/title_summary.php">Title Summary</a>

        <?php endif; ?>

        <!-- EVERYONE CAN SEE MANAGERS -->
        <a href="/Employee-Manager-Systems/views/managers_list.php">Managers</a>

        <!-- Everyone sees Logout -->
        <a href="/Employee-Manager-Systems/views/logout.php">Logout</a>
    </nav>
</header>

<main style="padding: 20px;">
