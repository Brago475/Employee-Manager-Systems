<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db_connect.php'; // gives you $pdo (PDO)

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_no = trim($_POST['emp_no'] ?? '');

    if (!empty($emp_no) && is_numeric($emp_no)) {
        try {
            // Fetch employee info
            $stmt = $pdo->prepare('SELECT emp_no, first_name, last_name FROM employees WHERE emp_no = ?');
            $stmt->execute([$emp_no]);
            $employee = $stmt->fetch();

            if ($employee) {
                // Store basic info in session
                $_SESSION['emp_no'] = $employee['emp_no'];
                $_SESSION['first_name'] = $employee['first_name'];
                $_SESSION['last_name'] = $employee['last_name'];

                // Check if the employee is a manager
                $mgr_check = $pdo->prepare('SELECT * FROM dept_manager WHERE emp_no = ?');
                $mgr_check->execute([$emp_no]);
                $is_mgr = $mgr_check->fetch() !== false;

                $_SESSION['is_manager'] = $is_mgr;

                // Redirect based on role
                if ($is_mgr) {
                    header("Location: manager_dashboard.php");
                } else {
                    header("Location: employee_dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid Employee Number. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error. Please try again later.";
        }
    } else {
        $error = "Please enter a valid numeric Employee Number.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Login</title>
<style>
body {
  font-family: Arial, sans-serif;
  background-color: #f4f4f4;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}
.container {
  background-color: #fff;
  padding: 40px;
  border-radius: 8px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  width: 350px;
  text-align: center;
}
h2 {
  color: #333;
  margin-bottom: 20px;
}
input[type=number] {
  width: 90%;
  padding: 10px;
  margin: 15px 0;
  border: 1px solid #ccc;
  border-radius: 5px;
}
button {
  background-color: #007bff;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  width: 100%;
  cursor: pointer;
  font-size: 16px;
}
button:hover {
  background-color: #0056b3;
}
.error {
  color: red;
  margin-top: 10px;
}
.back-link {
  display: block;
  margin-top: 15px;
  text-decoration: none;
  color: #007bff;
}
.back-link:hover {
  text-decoration: underline;
}
</style>
</head>
<body>
<div class="container">
  <h2>Employee Login</h2>
  <form method="POST">
    <input type="number" name="emp_no" placeholder="Enter Employee Number" required>
    <button type="submit">Login</button>
  </form>
  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
</div>
</body>
</html>
