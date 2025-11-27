<?php
// index.php
session_start();

// redirect logged users directly
if (isset($_SESSION['emp_no'])) {
    if (!empty($_SESSION['is_manager']) && $_SESSION['is_manager'] === true) {
        header("Location: manager_dashboard.php");
    } else {
        header("Location: employee_dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Employee Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            text-align: center;
        }
        header {
            background-color: #007BFF;
            color: white;
            padding: 25px 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        header h1 {
            margin: 0;
            font-size: 26px;
        }
        .container {
            margin-top: 120px;
        }
        a.button {
            display: inline-block;
            background-color: #007BFF;
            color: white;
            padding: 15px 30px;
            margin: 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
            transition: background 0.3s;
        }
        a.button:hover {
            background-color: #0056b3;
        }
        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: #222;
            color: white;
            padding: 10px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
<header>
    <h1>Welcome to Employee Management System</h1>
</header>

<div class="container">
    <p style="font-size:18px;">Please select your login type:</p>
    <!-- Both buttons lead to the same login page -->
    <a href="login.php" class="button">Employee Login</a>
    <a href="login.php" class="button">Manager Login</a>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Employee Management System</p>
</footer>
</body>
</html>
